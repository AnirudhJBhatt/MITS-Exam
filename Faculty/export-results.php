<?php
require_once "../Connection/connection.php";

if (isset($_GET['Exam_ID'])) {
    $exam_id = $_GET['Exam_ID'];

    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=exam_' . $exam_id . '_results.csv');

    // Open PHP output stream
    $output = fopen('php://output', 'w');

    // Column headers
    fputcsv($output, ['Student ID', 'Student Name', 'Marks']);

    // Fetch results from database
    $query = "SELECT * FROM result r, student s WHERE r.Stud_ID = s.Stud_ID AND r.Exam_ID = $exam_id;";

    $run = mysqli_query($con, $query);

    if ($run && mysqli_num_rows($run) > 0) {
        while ($row = mysqli_fetch_assoc($run)) {
            fputcsv($output, [
                $row['Stud_ID'],
                $row['Stud_Name'],
                $row['Total_Marks']
            ]);
        }
    } else {
        fputcsv($output, ['No results found for this exam.']);
    }

    fclose($output);
    exit;
} else {
    echo "Invalid request.";
}
?>
