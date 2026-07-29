<?php
require_once('../Connection/connection.php');
require_once('../vendor/autoload.php');


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

// if (!isset($_POST['Exam_ID'])) {
//     die("Invalid Request");
// }

$Exam_ID = 383;

// =====================
// FETCH EXAM DETAILS
// =====================
$examQuery = "
    SELECT e.*, c.Course_Name, c.Course_ID, p.Prog_Name
    FROM exam e
    JOIN courses c ON e.Course_ID = c.Course_ID
    JOIN programmes p ON e.Prog_ID = p.Prog_ID
    WHERE e.Exam_ID = '$Exam_ID'
";

$examRun = mysqli_query($con, $examQuery);
$exam = mysqli_fetch_assoc($examRun);

// =====================
// FETCH QUESTIONS
// =====================
$qids = json_decode($exam['Q_IDs'], true);

if (empty($qids)) {
    die("No Questions Found");
}

$qids_str = implode(',', $qids);

$questionQuery = "
    SELECT Q_ID, Question_Text, Marks, CO
    FROM question_bank
    WHERE Q_ID IN ($qids_str)
";

$qRun = mysqli_query($con, $questionQuery);

$questions = [];
$used_cos = [];

while ($row = mysqli_fetch_assoc($qRun)) {
    $questions[] = $row;
    if (!empty($row['CO'])) {
        $used_cos[] = $row['CO'];
    }
}

$used_cos = array_unique($used_cos);

// =====================
// CREATE WORD DOCUMENT
// =====================
$phpWord = new PhpWord();
$section = $phpWord->addSection();


// LOGO
$section->addImage(
    '../Images/MITS Logo.png',
    [
        'width' => 250, // only width specified, height auto-calculated
        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
    ]
);

$section->addTextBreak(1);

// =====================
// HEADER
// =====================
$section->addText("MUHTOOT INSTITUTE OF TECHNOLOGY AND SCIENCE", ['bold' => true], ['alignment' => 'center'], ['size' => 16]);
$section->addText("DEPARTMENT OF ".$exam['Dept'], ['bold' => true], ['alignment' => 'center'], ['size' => 14]);
$section->addText("SEMESTER ".$exam['Semester'], ['bold' => true], ['alignment' => 'center'], ['size' => 14]);
$section->addText($exam['Course_ID']." - ".$exam['Course_Name'], ['bold' => true], ['alignment' => 'center'], ['size' => 14]);
$section->addText($exam['Exam_Title'], ['bold' => true], ['alignment' => 'center'], ['size' => 14]);

$section->addTextBreak(1);

// =====================
// CO TABLE
// =====================
$section->addText("Course Outcomes (COs)", ['bold' => true]);

$table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);

$table->addRow();
$table->addCell(2000)->addText("CO");
$table->addCell(8000)->addText("Description");

foreach ($used_cos as $co) {
    $table->addRow();
    $table->addCell(2000)->addText("CO".$co);
    $table->addCell(8000)->addText("");
}

$section->addTextBreak(1);

// =====================
// QUESTION TABLE
// =====================
$qTable = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);

$qTable->addRow();
$qTable->addCell(1000)->addText("No");
$qTable->addCell(6000)->addText("Question");
$qTable->addCell(2000)->addText("Marks");
$qTable->addCell(2000)->addText("CO");

$qn = 1;

foreach ($questions as $row) {
    $qTable->addRow();
    $qTable->addCell(1000)->addText($qn);
    $qTable->addCell(6000)->addText($row['Question_Text']);
    $qTable->addCell(2000)->addText($row['Marks']);
    $qTable->addCell(2000)->addText($row['CO']);
    $qn++;
}

// =====================
// FOOTER TABLE
// =====================
$section->addTextBreak(2);

$fTable = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);

$fTable->addRow();
$fTable->addCell(4000)->addText("Prepared By:");
$fTable->addCell(4000)->addText("Verified By:");
$fTable->addCell(4000)->addText("Approved By:");

$fTable->addRow();
$fTable->addCell(4000)->addText("");
$fTable->addCell(4000)->addText("");
$fTable->addCell(4000)->addText("");

// =====================
// OUTPUT WORD FILE
// =====================
$filename = "Question_Paper.docx";

header("Content-Description: File Transfer");
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$objWriter = IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save("php://output");
exit;