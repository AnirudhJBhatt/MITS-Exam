<?php
/**
 * MITS Assessment Portal - Official Student PDF Scorecard Generator
 * Generates an official, printable PDF assessment scorecard with NBA CO breakdown.
 */
session_start();
require_once __DIR__ . '/../Connection/connection.php';
require_once __DIR__ . '/../Common/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

check_role(['Faculty', 'Admin', 'DeptAdmin', 'Student']);

$currentUserRole = $_SESSION['Role'] ?? '';
$currentUserId = $_SESSION['user_id'] ?? $_SESSION['LoginStudent'] ?? $_SESSION['LoginFaculty'] ?? '';

$Exam_ID = (int)($_GET['Exam_ID'] ?? 0);
$Stud_ID = trim($_GET['Stud_ID'] ?? '');

if ($Exam_ID <= 0 || empty($Stud_ID)) {
    die("Invalid Request: Missing Exam ID or Student Roll Number.");
}

// Security: Students can only download their own scorecard
if ($currentUserRole === 'Student' && $Stud_ID !== $currentUserId) {
    die("Unauthorized access to candidate scorecard.");
}

// 1. Fetch Student Details
$sStmt = $pdo->prepare("
    SELECT s.Stud_Name, s.Stud_Email, s.Stud_Dept, s.Stud_Prog, s.Stud_Branch, s.Stud_Sem, d.Dept_Name, p.Prog_Name 
    FROM student s
    LEFT JOIN department d ON s.Stud_Dept = d.Dept_ID
    LEFT JOIN programmes p ON s.Stud_Branch = p.Prog_ID
    WHERE s.Stud_ID = ? LIMIT 1
");
$sStmt->execute([$Stud_ID]);
$student = $sStmt->fetch();

if (!$student) {
    die("Student not found.");
}
$stud_name = $student['Stud_Name'];

// 2. Fetch Exam Details
$eStmt = $pdo->prepare("
    SELECT e.Exam_Name, e.Duration, e.Total_Marks, e.Start_Time, e.End_Time, e.Result_Published,
           c.Course_Code, c.Course_Name, d.Dept_Name
    FROM exams e
    LEFT JOIN courses c ON e.Course_ID = c.Course_ID
    LEFT JOIN department d ON c.Dept_ID = d.Dept_ID
    WHERE e.Exam_ID = ? LIMIT 1
");
$eStmt->execute([$Exam_ID]);
$exam = $eStmt->fetch();

if (!$exam) {
    die("Examination not found.");
}

// 3. Fetch Result Attempt
$rStmt = $pdo->prepare("
    SELECT Result_ID, Total_Marks, Marks_Obtained, Percentage, Time_Taken_Sec, Violations, Auto_Submitted, Status, Submitted_At
    FROM results 
    WHERE Stud_ID = ? AND Exam_ID = ? LIMIT 1
");
$rStmt->execute([$Stud_ID, $Exam_ID]);
$result = $rStmt->fetch();

if (!$result) {
    die("No examination submission found for this candidate.");
}

$Result_ID = (int)$result['Result_ID'];

// 4. Fetch Question Itemized Breakdown
$qStmt = $pdo->prepare("
    SELECT 
        ra.Question_ID, ra.Question_Type, ra.Marks_Awarded, ra.Max_Marks, ra.Is_Correct,
        eq.Question_Text, eq.CO
    FROM result_answers ra
    JOIN exam_questions eq ON ra.Question_ID = eq.Question_ID
    WHERE ra.Result_ID = ?
    ORDER BY eq.Sort_Order ASC, eq.Question_ID ASC
");
$qStmt->execute([$Result_ID]);
$questions = $qStmt->fetchAll();

// 5. Generate PDF Document using TCPDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator('MITS Assessment Portal');
$pdf->SetAuthor('Muthoot Institute of Technology and Science');
$pdf->SetTitle("Scorecard - {$stud_name} - {$exam['Exam_Name']}");
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();

// Header Logo & Institution Name
$logoPath = __DIR__ . '/../Images/MITS Logo.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 85, 12, 40, '', 'PNG', '', 'T', false, 300, 'C');
    $pdf->Ln(24);
} else {
    $pdf->Ln(6);
}

$deptTitle = !empty($exam['Dept_Name']) ? strtoupper($exam['Dept_Name']) : strtoupper($student['Dept_Name'] ?? 'DEPARTMENT');

$html = '
<div style="text-align: center; font-family: helvetica;">
    <h2 style="font-size: 15pt; color: #111827; margin: 0; padding: 0; font-weight: bold;">MUTHOOT INSTITUTE OF TECHNOLOGY AND SCIENCE</h2>
    <div style="font-size: 9pt; color: #6b7280; margin-top: 2px;">Varikoli P.O., Puthencruz, Ernakulam, Kerala - 682311</div>
    <div style="font-size: 10pt; color: #d1202d; font-weight: bold; margin-top: 4px;">DEPARTMENT OF ' . htmlspecialchars($deptTitle) . '</div>
    <h3 style="font-size: 12pt; color: #1f2937; margin-top: 6px; font-weight: bold; text-decoration: underline;">CANDIDATE EXAMINATION SCORECARD</h3>
</div>

<br><br>

<table cellpadding="4" cellspacing="0" border="1" style="border-collapse: collapse; border-color: #d1d5db; font-size: 9pt; width: 100%;">
    <tr style="background-color: #f9fafb;">
        <td width="20%"><strong>Candidate Name:</strong></td>
        <td width="40%"><strong>' . htmlspecialchars($stud_name) . '</strong></td>
        <td width="20%"><strong>Roll / Admission No:</strong></td>
        <td width="20%"><strong>' . htmlspecialchars($Stud_ID) . '</strong></td>
    </tr>
    <tr>
        <td><strong>Course / Subject:</strong></td>
        <td>' . htmlspecialchars($exam['Course_Code'] . ' - ' . $exam['Course_Name']) . '</td>
        <td><strong>Semester & Prog:</strong></td>
        <td>S' . htmlspecialchars($student['Stud_Sem']) . ' ' . htmlspecialchars($student['Stud_Prog']) . '</td>
    </tr>
    <tr style="background-color: #f9fafb;">
        <td><strong>Assessment Title:</strong></td>
        <td>' . htmlspecialchars($exam['Exam_Name']) . '</td>
        <td><strong>Submission Date:</strong></td>
        <td>' . date('d-m-Y, h:i A', strtotime($result['Submitted_At'])) . '</td>
    </tr>
    <tr>
        <td><strong>Allocated Duration:</strong></td>
        <td>' . $exam['Duration'] . ' Minutes</td>
        <td><strong>Time Taken:</strong></td>
        <td>' . floor($result['Time_Taken_Sec'] / 60) . 'm ' . ($result['Time_Taken_Sec'] % 60) . 's</td>
    </tr>
</table>

<br><br>
<div style="font-size: 10pt; font-weight: bold; color: #111827; margin-bottom: 6px;">ITEMIZED QUESTION PERFORMANCE & NBA COURSE OUTCOME (CO) MAPPING:</div>

<table cellpadding="4" cellspacing="0" border="1" style="border-collapse: collapse; border-color: #d1d5db; font-size: 8.5pt; width: 100%;">
    <thead>
        <tr style="background-color: #d1202d; color: #ffffff; font-weight: bold; text-align: center;">
            <th width="8%">Q.No</th>
            <th width="52%" align="left">Question Preview</th>
            <th width="10%">Type</th>
            <th width="10%">CO</th>
            <th width="10%">Max Marks</th>
            <th width="10%">Awarded</th>
        </tr>
    </thead>
    <tbody>';

$sl = 1;
foreach ($questions as $q) {
    $qText = strip_tags($q['Question_Text']);
    if (mb_strlen($qText) > 85) {
        $qText = mb_substr($qText, 0, 82) . '...';
    }
    $coTag = !empty($q['CO']) ? 'CO' . $q['CO'] : 'CO1';
    $bgColor = ($sl % 2 === 0) ? '#f9fafb' : '#ffffff';

    $html .= '
        <tr style="background-color: ' . $bgColor . ';">
            <td align="center"><strong>' . $sl++ . '</strong></td>
            <td>' . htmlspecialchars($qText) . '</td>
            <td align="center" style="font-size: 7.5pt; text-transform: uppercase;">' . htmlspecialchars($q['Question_Type']) . '</td>
            <td align="center"><strong>' . $coTag . '</strong></td>
            <td align="center">' . (float)$q['Max_Marks'] . '</td>
            <td align="center"><strong>' . (float)$q['Marks_Awarded'] . '</strong></td>
        </tr>';
}

$pct = round((float)$result['Percentage'], 1);
$resultRemark = ($pct >= 40.0) ? 'PASSED' : 'NEEDS IMPROVEMENT';
$remarkColor = ($pct >= 40.0) ? '#16a34a' : '#dc2626';

$html .= '
    </tbody>
    <tfoot>
        <tr style="background-color: #f3f4f6; font-weight: bold; font-size: 9pt;">
            <td colspan="4" align="right">TOTAL MARKS:</td>
            <td align="center">' . (float)$result['Total_Marks'] . '</td>
            <td align="center" style="color: #d1202d; font-size: 10pt;">' . (float)$result['Marks_Obtained'] . '</td>
        </tr>
    </tfoot>
</table>

<br><br>

<table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; border-color: #d1d5db; font-size: 9.5pt; width: 100%;">
    <tr style="background-color: #f9fafb;">
        <td width="33%" align="center">
            <span style="font-size: 8pt; color: #6b7280; text-transform: uppercase;">Percentage Score</span><br>
            <strong style="font-size: 13pt; color: #111827;">' . $pct . '%</strong>
        </td>
        <td width="34%" align="center">
            <span style="font-size: 8pt; color: #6b7280; text-transform: uppercase;">Assessment Outcome</span><br>
            <strong style="font-size: 12pt; color: ' . $remarkColor . ';">' . $resultRemark . '</strong>
        </td>
        <td width="33%" align="center">
            <span style="font-size: 8pt; color: #6b7280; text-transform: uppercase;">Proctor Telemetry</span><br>
            <strong style="font-size: 11pt; color: ' . ($result['Violations'] > 0 ? '#dc2626' : '#16a34a') . ';">' . $result['Violations'] . ' Violation(s)</strong>
        </td>
    </tr>
</table>

<br><br><br><br>

<table width="100%" cellpadding="0" cellspacing="0" style="font-size: 9pt;">
    <tr>
        <td width="40%" align="center">
            _______________________________<br>
            <strong>Evaluator / Course Faculty</strong><br>
            <span style="font-size: 8pt; color: #6b7280;">Department of ' . htmlspecialchars($deptTitle) . '</span>
        </td>
        <td width="20%"></td>
        <td width="40%" align="center">
            _______________________________<br>
            <strong>Head of the Department (HOD)</strong><br>
            <span style="font-size: 8pt; color: #6b7280;">Muthoot Institute of Technology & Science</span>
        </td>
    </tr>
</table>
';

$pdf->writeHTML($html, true, false, true, false, '');

// Clean file name
$safeFileName = preg_replace('/[^A-Za-z0-9_-]/', '_', "Scorecard_{$Stud_ID}_{$exam['Exam_Name']}.pdf");
$pdf->Output($safeFileName, 'I');
exit;