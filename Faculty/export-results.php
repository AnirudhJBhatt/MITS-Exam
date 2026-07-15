<?php
require_once "../Connection/connection.php";

if (!isset($_GET['Exam_ID'])) {
    exit("Invalid request.");
}

$exam_id = (int) $_GET['Exam_ID'];

/* ------------------ FETCH RESULTS (INCLUDING ABSENTEES) ------------------ */
$sql = "SELECT 
            s.Stud_ID, 
            s.Stud_Name, 
            r.Obtained_Marks, 
            r.CO_Details
        FROM student s
        INNER JOIN courses c 
            ON s.Stud_Branch = c.Prog_ID 
            AND s.Stud_Sem = c.Semester
        LEFT JOIN result r 
            ON s.Stud_ID = r.Stud_ID 
            AND r.Exam_ID = ?
        WHERE c.Course_ID = (
            SELECT Course_ID FROM exam WHERE Exam_ID = ?
        )
        ORDER BY s.Stud_Name ASC";

$stmt = $con->prepare($sql);
$stmt->bind_param("ii", $exam_id, $exam_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header('Content-Type: text/plain');
    exit("No students found for this exam.");
}

/* ------------------ COLLECT ALL COs ------------------ */
$rows = [];
$all_cos = [];
$slno = 1;
$present = 0;
$absent = 0;

while ($row = $res->fetch_assoc()) {

    // Count attendance
    if ($row['Obtained_Marks'] !== null) {
        $present++;
    } else {
        $absent++;
    }

    $co_details = !empty($row['CO_Details']) 
        ? json_decode($row['CO_Details'], true) 
        : [];

    if (is_array($co_details)) {
        foreach ($co_details as $co) {
            $co_key = 'CO' . $co['CO'];
            $all_cos[$co_key] = true;
        }
    }

    $rows[] = $row;
}

$co_columns = array_keys($all_cos);
sort($co_columns); // CO1, CO2, CO3...

/* ------------------ CSV HEADERS ------------------ */
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Exam_' . $exam_id . '_Results.csv');

$output = fopen('php://output', 'w');

/* ------------------ ATTENDANCE SUMMARY ------------------ */
// fputcsv($output, ["Present: $present", "Absent: $absent", "Total: " . ($present + $absent)]);
// fputcsv($output, []); // empty line

/* ------------------ CSV HEADER ------------------ */
$header = array_merge(
    ['Roll No', 'Admission No', 'Name'],
    $co_columns,
    ['Total Marks']
);

fputcsv($output, $header);

/* ------------------ CSV ROWS ------------------ */
foreach ($rows as $row) {

    // Initialize CO values as 0
    $co_values = array_fill_keys($co_columns, 0);

    $co_details = !empty($row['CO_Details']) 
        ? json_decode($row['CO_Details'], true) 
        : [];

    if (is_array($co_details)) {
        foreach ($co_details as $co) {
            $key = 'CO' . $co['CO'];
            if (isset($co_values[$key])) {
                $co_values[$key] = $co['CO_Total_Marks'];
            }
        }
    }

    fputcsv($output, array_merge(
        [
            $slno++,
            $row['Stud_ID'],
            $row['Stud_Name']
        ],
        array_values($co_values),
        [
            ($row['Obtained_Marks'] !== null) 
                ? $row['Obtained_Marks'] 
                : 'Absent'
        ]
    ));
}

fclose($output);
exit;
?>