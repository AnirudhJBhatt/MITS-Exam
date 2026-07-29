<?php
session_start();
if (!isset($_SESSION["LoginFaculty"])) {
    die("Unauthorized access. Please login.");
}

require_once __DIR__ . '/../Connection/connection.php';
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_GET['Exam_ID'])) {
    die("Exam ID is required.");
}

$Exam_ID = mysqli_real_escape_string($con, $_GET['Exam_ID']);

// Fetch Exam & Course & Department Details
$examQuery = "
    SELECT e.*, c.Course_Name, c.Course_Code, c.Semester, d.Dept_Name, p.Prog_Name
    FROM exams e
    JOIN courses c ON e.Course_ID = c.Course_ID
    JOIN programmes p ON c.Prog_ID = p.Prog_ID
    LEFT JOIN department d ON c.Dept_ID = d.Dept_ID
    WHERE e.Exam_ID = '$Exam_ID'
";
$examRun = mysqli_query($con, $examQuery);
$exam = mysqli_fetch_assoc($examRun);

if (!$exam) {
    die("Exam not found.");
}

// Fetch Questions
$questionQuery = "
    SELECT Question_ID, Question_Type, Question_Text, Marks, CO, Options_JSON, Pairs_JSON, Image_Path
    FROM exam_questions
    WHERE Exam_ID = '$Exam_ID'
    ORDER BY Sort_Order ASC, Question_ID ASC
";
$qRun = mysqli_query($con, $questionQuery);
$questions = [];
$used_cos = [];
$total_marks = 0;

while ($row = mysqli_fetch_assoc($qRun)) {
    $questions[] = $row;
    $total_marks += $row['Marks'];
    if (!empty($row['CO'])) {
        $used_cos[] = $row['CO'];
    }
}
$used_cos = array_unique($used_cos);
sort($used_cos);

// Initialize PHPWord
$phpWord = new \PhpOffice\PhpWord\PhpWord();

// Set default font
$phpWord->setDefaultFontName('Calibri');
$phpWord->setDefaultFontSize(11);

// Add section with 1 inch margins
$section = $phpWord->addSection(array(
    'marginLeft'   => 1440,
    'marginRight'  => 1440,
    'marginTop'    => 1440,
    'marginBottom' => 1440,
));

// Define Styles
$titleStyle = array('name' => 'Calibri', 'size' => 14, 'bold' => true);
$subtitleStyle = array('name' => 'Calibri', 'size' => 12, 'bold' => true);
$headerStyle = array('name' => 'Calibri', 'size' => 11, 'bold' => true);
$bodyStyle = array('name' => 'Calibri', 'size' => 11);
$boldBodyStyle = array('name' => 'Calibri', 'size' => 11, 'bold' => true);
$italicStyle = array('name' => 'Calibri', 'size' => 10, 'italic' => true);

$centerAlign = array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER);
$leftAlign = array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT);
$rightAlign = array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT);

// Logo
$logoPath = __DIR__ . '/../Images/MITS Logo.png';
if (file_exists($logoPath)) {
    $section->addImage($logoPath, array(
        'width' => 120,
        'height' => 45,
        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
    ));
    $section->addTextBreak(1);
}

// Center Header details
$section->addText("MUTHOOT INSTITUTE OF TECHNOLOGY AND SCIENCE", $titleStyle, $centerAlign);
if (!empty($exam['Dept_Name'])) {
    $section->addText("DEPARTMENT OF " . strtoupper($exam['Dept_Name']), $subtitleStyle, $centerAlign);
}
$section->addText("SEMESTER " . $exam['Semester'] . " | " . strtoupper($exam['Prog_Name']), $subtitleStyle, $centerAlign);
$section->addText(strtoupper($exam['Course_Code']) . " - " . strtoupper($exam['Course_Name']), $subtitleStyle, $centerAlign);
$section->addText(strtoupper($exam['Exam_Name']), $titleStyle, $centerAlign);
$section->addTextBreak(1);

// Metadata table (Marks & Duration)
$metaTableStyle = array(
    'borderSize'  => 6,
    'borderColor' => 'D3D3D3',
    'cellMargin'  => 80,
    'width'       => 100 * 50
);
$metaTable = $section->addTable($metaTableStyle);
$metaTable->addRow();
$metaTable->addCell(5000)->addText("Duration: " . $exam['Duration'] . " Minutes", $boldBodyStyle, $leftAlign);
$metaTable->addCell(5000)->addText("Maximum Marks: " . round($total_marks, 1) . " Marks", $boldBodyStyle, $rightAlign);

$section->addTextBreak(1);

// Course Outcomes (COs) Mapping Table
if (!empty($used_cos)) {
    $section->addText("Course Outcomes (COs) addressed in this Exam:", $subtitleStyle, $leftAlign);
    
    $coTableStyle = array(
        'borderSize'  => 6,
        'borderColor' => '999999',
        'cellMargin'  => 80
    );
    $coFirstRowStyle = array('bgColor' => 'F2F2F2');
    $phpWord->addTableStyle('CoTable', $coTableStyle, $coFirstRowStyle);
    
    $coTable = $section->addTable('CoTable');
    $coTable->addRow();
    $coTable->addCell(2000)->addText("CO Code", $headerStyle, $centerAlign);
    $coTable->addCell(8000)->addText("Description / Details", $headerStyle, $leftAlign);
    
    foreach ($used_cos as $co) {
        $coTable->addRow();
        $coTable->addCell(2000)->addText("CO" . $co, $bodyStyle, $centerAlign);
        $coTable->addCell(8000)->addText("", $bodyStyle, $leftAlign);
    }
    
    $section->addTextBreak(1);
}

// Questions Section Title
$section->addText("QUESTIONS", $subtitleStyle, $centerAlign);

$qTableStyle = array(
    'borderSize'  => 6,
    'borderColor' => '666666',
    'cellMargin'  => 100
);
$qFirstRowStyle = array('bgColor' => 'F2F2F2');
$phpWord->addTableStyle('QTable', $qTableStyle, $qFirstRowStyle);

$qTable = $section->addTable('QTable');
$qTable->addRow();
$qTable->addCell(1000)->addText("Q.No", $headerStyle, $centerAlign);
$qTable->addCell(6500)->addText("Question Details", $headerStyle, $leftAlign);
$qTable->addCell(1200)->addText("Marks", $headerStyle, $centerAlign);
$qTable->addCell(1300)->addText("CO", $headerStyle, $centerAlign);

$qn = 1;
foreach ($questions as $q) {
    $qTable->addRow();
    
    // Q.No Cell
    $qTable->addCell(1000)->addText($qn, $bodyStyle, $centerAlign);
    
    // Question Details Cell
    $detailsCell = $qTable->addCell(6500);
    
    // Add Question Text (preserving newlines by splitting and adding as separate paragraphs)
    $textParts = explode("\n", $q['Question_Text']);
    foreach ($textParts as $part) {
        $detailsCell->addText(htmlspecialchars(trim($part)), $bodyStyle);
    }
    
    // Add attached image if any
    if (!empty($q['Image_Path'])) {
        $possiblePaths = [
            __DIR__ . '/' . $q['Image_Path'],
            __DIR__ . '/../' . $q['Image_Path'],
            __DIR__ . '/../Faculty/' . $q['Image_Path']
        ];
        $finalImagePath = null;
        foreach ($possiblePaths as $p) {
            if (file_exists($p) && is_file($p)) {
                $finalImagePath = $p;
                break;
            }
        }
        if ($finalImagePath) {
            $detailsCell->addTextBreak(1);
            $detailsCell->addImage($finalImagePath, array(
                'width' => 200,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
            ));
        }
    }

    // MCQ specific layout
    if ($q['Question_Type'] === 'mcq' && !empty($q['Options_JSON'])) {
        $options = json_decode($q['Options_JSON'], true);
        if (is_array($options)) {
            $detailsCell->addTextBreak(1);
            foreach ($options as $opt) {
                $letter = $opt['letter'] ?? '';
                $text = $opt['text'] ?? '';
                $detailsCell->addText("   " . $letter . ") " . htmlspecialchars($text), $bodyStyle);
            }
        }
    }

    // Match generic layout (Shuffled columns)
    if ($q['Question_Type'] === 'match' && !empty($q['Pairs_JSON'])) {
        $pairs = json_decode($q['Pairs_JSON'], true);
        if (is_array($pairs)) {
            $detailsCell->addTextBreak(1);
            $detailsCell->addText("Match the following pairs:", $boldBodyStyle);
            
            $leftSide = [];
            $rightSide = [];
            foreach ($pairs as $pair) {
                if (isset($pair['a'])) $leftSide[] = $pair['a'];
                if (isset($pair['b'])) $rightSide[] = $pair['b'];
            }
            shuffle($rightSide);
            
            $innerTable = $detailsCell->addTable(array('borderSize' => 0, 'cellMargin' => 30));
            $totalPairs = max(count($leftSide), count($rightSide));
            for ($i = 0; $i < $totalPairs; $i++) {
                $leftText = isset($leftSide[$i]) ? htmlspecialchars($leftSide[$i]) : '';
                $rightText = isset($rightSide[$i]) ? htmlspecialchars($rightSide[$i]) : '';
                
                $innerTable->addRow();
                $innerTable->addCell(3000)->addText("  " . ($i + 1) . ". " . $leftText, $bodyStyle);
                $innerTable->addCell(3000)->addText("   " . chr(65 + $i) . ") " . $rightText, $bodyStyle);
            }
        }
    }

    // Open/Subjective specific layout
    if ($q['Question_Type'] === 'open' && !empty($q['Word_Limit'])) {
        $detailsCell->addTextBreak(1);
        $detailsCell->addText("   [Word Limit: " . $q['Word_Limit'] . " words]", $italicStyle);
    }
    
    // Marks Cell
    $qTable->addCell(1200)->addText(round($q['Marks'], 1), $bodyStyle, $centerAlign);
    
    // CO Cell
    $coText = !empty($q['CO']) ? "CO" . $q['CO'] : "—";
    $qTable->addCell(1300)->addText($coText, $bodyStyle, $centerAlign);
    
    $qn++;
}

// Bottom Signature Block
$section->addTextBreak(2);

$footerTableStyle = array('borderSize' => 0, 'cellMargin' => 50);
$footerTable = $section->addTable($footerTableStyle);
$footerTable->addRow();
$footerTable->addCell(3333)->addText("Prepared By:", $boldBodyStyle, $leftAlign);
$footerTable->addCell(3333)->addText("Verified By:", $boldBodyStyle, $centerAlign);
$footerTable->addCell(3334)->addText("Approved By (HOD):", $boldBodyStyle, $rightAlign);

$footerTable->addRow();
$footerTable->addCell(3333)->addText("\n\n__________________", $bodyStyle, $leftAlign);
$footerTable->addCell(3333)->addText("\n\n__________________", $bodyStyle, $centerAlign);
$footerTable->addCell(3334)->addText("\n\n__________________", $bodyStyle, $rightAlign);

// Send Output headers to download word file
header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
$sanitizedTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $exam['Exam_Name']);
header("Content-Disposition: attachment; filename=\"Question_Paper_{$sanitizedTitle}.docx\"");
header("Cache-Control: max-age=0");

$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save('php://output');
exit();
?>
