<?php
/**
 * MITS Assessment Portal - Outcome-Based Education (OBE) CSV Export
 * Exports candidate examination performance mapped to NBA/NAAC Course Outcomes (CO1-CO5).
 */
session_start();
require_once __DIR__ . '/../Connection/connection.php';
require_once __DIR__ . '/../Common/auth.php';

check_role(['Faculty', 'Admin', 'DeptAdmin']);

$Exam_ID = (int)($_GET['Exam_ID'] ?? 0);

if ($Exam_ID <= 0) {
    die("Invalid Examination Identifier.");
}

// 1. Fetch Exam Details
$eStmt = $pdo->prepare("
    SELECT e.Exam_ID, e.Exam_Name, e.Course_ID, e.Acad_Year, e.Total_Marks, c.Course_Code, c.Course_Name
    FROM exams e
    LEFT JOIN courses c ON e.Course_ID = c.Course_ID
    WHERE e.Exam_ID = ? LIMIT 1
");
$eStmt->execute([$Exam_ID]);
$exam = $eStmt->fetch();

if (!$exam) {
    die("Examination record not found.");
}

$examName = $exam['Exam_Name'];
$safeCourseCode = preg_replace('/[^A-Za-z0-9_-]/', '_', $exam['Course_Code']);
$safeExamName = preg_replace('/[^A-Za-z0-9_-]/', '_', $examName);

$safeFileName = "{$safeCourseCode}_{$safeExamName}_Results.csv";

// 2. Identify all active Course Outcomes (COs) in this exam
$coStmt = $pdo->prepare("SELECT DISTINCT CO FROM exam_questions WHERE Exam_ID = ? ORDER BY CO ASC");
$coStmt->execute([$Exam_ID]);
$cos = $coStmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($cos)) {
    $cos = [1, 2, 3];
}

// 3. Fetch Enrolled Students and Attempts
$studStmt = $pdo->prepare("
    SELECT 
        scm.Stud_ID, 
        s.Stud_Name, 
        r.Result_ID, 
        r.Marks_Obtained, 
        r.Total_Marks AS Exam_Total, 
        r.Percentage, 
        r.Status, 
        r.Violations, 
        r.Auto_Submitted, 
        r.Submitted_At
    FROM student_course_mapping scm
    JOIN student s ON scm.Stud_ID = s.Stud_ID
    JOIN exams e ON scm.Course_ID = e.Course_ID AND scm.Acad_Year = e.Acad_Year
    LEFT JOIN results r ON r.Exam_ID = e.Exam_ID AND r.Stud_ID = scm.Stud_ID
    WHERE e.Exam_ID = ?
    GROUP BY scm.Stud_ID, s.Stud_Name, r.Result_ID
    ORDER BY scm.Stud_ID ASC
");
$studStmt->execute([$Exam_ID]);
$candidates = $studStmt->fetchAll();

// 4. Preload CO marks per result attempt
$coMarksMap = [];
if (!empty($candidates)) {
    $resultIds = array_filter(array_column($candidates, 'Result_ID'));
    if (!empty($resultIds)) {
        $inPlaceholders = implode(',', array_fill(0, count($resultIds), '?'));
        $coScoresStmt = $pdo->prepare("
            SELECT ra.Result_ID, eq.CO, SUM(ra.Marks_Awarded) AS Awarded_CO
            FROM result_answers ra
            JOIN exam_questions eq ON ra.Question_ID = eq.Question_ID
            WHERE ra.Result_ID IN ($inPlaceholders)
            GROUP BY ra.Result_ID, eq.CO
        ");
        $coScoresStmt->execute(array_values($resultIds));
        while ($row = $coScoresStmt->fetch()) {
            $coMarksMap[$row['Result_ID']][$row['CO']] = (float)$row['Awarded_CO'];
        }
    }
}

// 5. Build CSV Output
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $safeFileName . '"');

$output = fopen('php://output', 'w');

// File Header Metadata
fputcsv($output, ["MUTHOOT INSTITUTE OF TECHNOLOGY AND SCIENCE - ASSESSMENT REPORT"]);
fputcsv($output, ["Course:", $exam['Course_Code'] . ' - ' . $exam['Course_Name']]);
fputcsv($output, ["Assessment:", $examName]);
fputcsv($output, ["Academic Year:", $exam['Acad_Year']]);
fputcsv($output, ["Exported On:", date('Y-m-d H:i:s')]);
fputcsv($output, []); // Empty line

// Columns Header
$csvHeaders = ["SL No", "Roll No", "Candidate Name"];
foreach ($cos as $coNum) {
    $csvHeaders[] = "CO" . $coNum;
}
$csvHeaders[] = "Marks Obtained";
$csvHeaders[] = "Total Marks";
$csvHeaders[] = "Percentage (%)";
$csvHeaders[] = "Violations";
$csvHeaders[] = "Status";

fputcsv($output, $csvHeaders);

$sl = 1;
foreach ($candidates as $row) {
    $isAttempted = !empty($row['Result_ID']);
    $resId = $row['Result_ID'] ?? 0;

    $csvRow = [
        $sl++,
        $row['Stud_ID'],
        $row['Stud_Name']
    ];

    // Add CO Columns
    foreach ($cos as $coNum) {
        if (!$isAttempted) {
            $csvRow[] = "N/A";
        } else {
            $csvRow[] = $coMarksMap[$resId][$coNum] ?? 0.0;
        }
    }

    if (!$isAttempted) {
        $csvRow[] = "0";
        $csvRow[] = (float)$exam['Total_Marks'];
        $csvRow[] = "0.0%";
        $csvRow[] = "0";
        $csvRow[] = "Not Attempted";
    } else {
        $csvRow[] = (float)$row['Marks_Obtained'];
        $csvRow[] = (float)$row['Exam_Total'];
        $csvRow[] = round((float)$row['Percentage'], 1) . "%";
        $csvRow[] = (int)$row['Violations'];
        $csvRow[] = strtoupper(str_replace('_', ' ', $row['Status']));
    }

    fputcsv($output, $csvRow);
}

fclose($output);
exit;