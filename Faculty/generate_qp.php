<<<<<<< HEAD
<<<<<<< HEAD
<?php
require_once('../Connection/connection.php');
require_once('../vendor/autoload.php');

if (!isset($_POST['Exam_ID'])) {
    die("Invalid Request");
}

$Exam_ID = mysqli_real_escape_string($con, $_POST['Exam_ID']);

// =====================
// FETCH EXAM DETAILS
// =====================
$examQuery = "
    SELECT e.*, c.Course_Name, c.Course_ID, p.Prog_Name
    FROM exam e
    JOIN courses c ON e.Course_ID = c.Course_ID
    JOIN programmes p ON e.Prog_ID = p.Prog_ID
<<<<<<< HEAD
    WHERE e.Exam_ID = '$Exam_ID'
=======
    WHERE e.Exam_ID = '188'
>>>>>>> af63e72 (MITS-Exam)
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

// Store questions + extract COs
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
// CREATE PDF
// =====================
$pdf = new TCPDF();
$pdf->SetCreator('MITS');
$pdf->SetAuthor('MITS');
$pdf->SetTitle('Question Paper');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// OPTIONAL LOGO
$pdf->Image('../Images/MITS Logo.png', 80, 10, 40);
$pdf->Ln(20);

// =====================
// HEADER
// =====================
$html = '

<h3 style="text-align:center;">Muthoot Institute of Technology and Science</h3>
<h4 style="text-align:center;">DEPARTMENT OF '.$exam['Dept'].'</h4>
<h4 style="text-align:center;">SEMESTER '.$exam['Semester'].'</h4>
<h4 style="text-align:center;">'.$exam['Course_ID'].' - '.$exam['Course_Name'].'</h4>
<h4 style="text-align:center;">'.$exam['Exam_Title'].'</h4>


<br>

<h4>Course Outcomes (COs)</h4>

<table border="1" cellpadding="5">
<tr>
    <th width="20%">CO</th>
    <th width="80%">Description</th>
</tr>
';

// =====================
// CO TABLE (ONLY USED)
// =====================
foreach ($used_cos as $co) {
    $html .= "
    <tr>
        <td>CO$co</td>
        <td></td>
    </tr>
    ";
}

$html .= '</table><br><br>';

// =====================
// QUESTION TABLE
// =====================
$html .= '
<table border="1" cellpadding="5">
<tr style="background-color:#f2f2f2;">
    <th width="10%">No</th>
    <th width="60%">Question</th>
    <th width="15%">Marks</th>
    <th width="15%">CO</th>
</tr>
';

$qn = 1;

foreach ($questions as $row) {

    $question = htmlspecialchars($row['Question_Text']);
    $marks = $row['Marks'];
    $co = $row['CO'];

    $html .= "
    <tr>
        <td>$qn</td>
        <td>$question</td>
        <td>$marks</td>
        <td>$co</td>
    </tr>
    ";

    $qn++;
}

$html .= '</table>';

// =====================
// FOOTER
// =====================
$html .= '
<br><br>
<table border="1" cellpadding="10">
<tr>
    <td>Prepared By:</td>
    <td>Verified By:</td>
    <td>Approved By:</td>
</tr>
<tr>
    <td height="50"></td>
    <td></td>
    <td></td>
</tr>
</table>
';

// =====================
// OUTPUT PDF
// =====================
$pdf->writeHTML($html);

// IMPORTANT: No echo/print before this
=======
<?php
require_once('../Connection/connection.php');
require_once('../vendor/autoload.php');

if (!isset($_POST['Exam_ID'])) {
    die("Invalid Request");
}

$Exam_ID = mysqli_real_escape_string($con, $_POST['Exam_ID']);

// =====================
// FETCH EXAM DETAILS
// =====================
$examQuery = "
    SELECT e.*, c.Course_Name, c.Course_ID, p.Prog_Name
    FROM exam e
    JOIN courses c ON e.Course_ID = c.Course_ID
    JOIN programmes p ON e.Prog_ID = p.Prog_ID
<<<<<<< HEAD
    WHERE e.Exam_ID = '$Exam_ID'
=======
    WHERE e.Exam_ID = '188'
>>>>>>> af63e72 (MITS-Exam)
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

// Store questions + extract COs
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
// CREATE PDF
// =====================
$pdf = new TCPDF();
$pdf->SetCreator('MITS');
$pdf->SetAuthor('MITS');
$pdf->SetTitle('Question Paper');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// OPTIONAL LOGO
$pdf->Image('../Images/MITS Logo.png', 80, 10, 40);
$pdf->Ln(20);

// =====================
// HEADER
// =====================
$html = '

<h3 style="text-align:center;">Muthoot Institute of Technology and Science</h3>
<h4 style="text-align:center;">DEPARTMENT OF '.$exam['Dept'].'</h4>
<h4 style="text-align:center;">SEMESTER '.$exam['Semester'].'</h4>
<h4 style="text-align:center;">'.$exam['Course_ID'].' - '.$exam['Course_Name'].'</h4>
<h4 style="text-align:center;">'.$exam['Exam_Title'].'</h4>


<br>

<h4>Course Outcomes (COs)</h4>

<table border="1" cellpadding="5">
<tr>
    <th width="20%">CO</th>
    <th width="80%">Description</th>
</tr>
';

// =====================
// CO TABLE (ONLY USED)
// =====================
foreach ($used_cos as $co) {
    $html .= "
    <tr>
        <td>CO$co</td>
        <td></td>
    </tr>
    ";
}

$html .= '</table><br><br>';

// =====================
// QUESTION TABLE
// =====================
$html .= '
<table border="1" cellpadding="5">
<tr style="background-color:#f2f2f2;">
    <th width="10%">No</th>
    <th width="60%">Question</th>
    <th width="15%">Marks</th>
    <th width="15%">CO</th>
</tr>
';

$qn = 1;

foreach ($questions as $row) {

    $question = htmlspecialchars($row['Question_Text']);
    $marks = $row['Marks'];
    $co = $row['CO'];

    $html .= "
    <tr>
        <td>$qn</td>
        <td>$question</td>
        <td>$marks</td>
        <td>$co</td>
    </tr>
    ";

    $qn++;
}

$html .= '</table>';

// =====================
// FOOTER
// =====================
$html .= '
<br><br>
<table border="1" cellpadding="10">
<tr>
    <td>Prepared By:</td>
    <td>Verified By:</td>
    <td>Approved By:</td>
</tr>
<tr>
    <td height="50"></td>
    <td></td>
    <td></td>
</tr>
</table>
';

// =====================
// OUTPUT PDF
// =====================
$pdf->writeHTML($html);

// IMPORTANT: No echo/print before this
>>>>>>> f1e265abf03ca415a8e766b8518d8c076d9bf836
=======
<?php
require_once('../Connection/connection.php');
require_once('../vendor/autoload.php');

if (!isset($_POST['Exam_ID'])) {
    die("Invalid Request");
}

$Exam_ID = mysqli_real_escape_string($con, $_POST['Exam_ID']);

// =====================
// FETCH EXAM DETAILS
// =====================
$examQuery = "
    SELECT e.*, c.Course_Name, c.Course_ID, p.Prog_Name
    FROM exam e
    JOIN courses c ON e.Course_ID = c.Course_ID
    JOIN programmes p ON e.Prog_ID = p.Prog_ID
<<<<<<< HEAD
    WHERE e.Exam_ID = '$Exam_ID'
=======
    WHERE e.Exam_ID = '188'
>>>>>>> af63e72 (MITS-Exam)
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

// Store questions + extract COs
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
// CREATE PDF
// =====================
$pdf = new TCPDF();
$pdf->SetCreator('MITS');
$pdf->SetAuthor('MITS');
$pdf->SetTitle('Question Paper');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// OPTIONAL LOGO
$pdf->Image('../Images/MITS Logo.png', 80, 10, 40);
$pdf->Ln(20);

// =====================
// HEADER
// =====================
$html = '

<h3 style="text-align:center;">Muthoot Institute of Technology and Science</h3>
<h4 style="text-align:center;">DEPARTMENT OF '.$exam['Dept'].'</h4>
<h4 style="text-align:center;">SEMESTER '.$exam['Semester'].'</h4>
<h4 style="text-align:center;">'.$exam['Course_ID'].' - '.$exam['Course_Name'].'</h4>
<h4 style="text-align:center;">'.$exam['Exam_Title'].'</h4>


<br>

<h4>Course Outcomes (COs)</h4>

<table border="1" cellpadding="5">
<tr>
    <th width="20%">CO</th>
    <th width="80%">Description</th>
</tr>
';

// =====================
// CO TABLE (ONLY USED)
// =====================
foreach ($used_cos as $co) {
    $html .= "
    <tr>
        <td>CO$co</td>
        <td></td>
    </tr>
    ";
}

$html .= '</table><br><br>';

// =====================
// QUESTION TABLE
// =====================
$html .= '
<table border="1" cellpadding="5">
<tr style="background-color:#f2f2f2;">
    <th width="10%">No</th>
    <th width="60%">Question</th>
    <th width="15%">Marks</th>
    <th width="15%">CO</th>
</tr>
';

$qn = 1;

foreach ($questions as $row) {

    $question = htmlspecialchars($row['Question_Text']);
    $marks = $row['Marks'];
    $co = $row['CO'];

    $html .= "
    <tr>
        <td>$qn</td>
        <td>$question</td>
        <td>$marks</td>
        <td>$co</td>
    </tr>
    ";

    $qn++;
}

$html .= '</table>';

// =====================
// FOOTER
// =====================
$html .= '
<br><br>
<table border="1" cellpadding="10">
<tr>
    <td>Prepared By:</td>
    <td>Verified By:</td>
    <td>Approved By:</td>
</tr>
<tr>
    <td height="50"></td>
    <td></td>
    <td></td>
</tr>
</table>
';

// =====================
// OUTPUT PDF
// =====================
$pdf->writeHTML($html);

// IMPORTANT: No echo/print before this
>>>>>>> f1e265abf03ca415a8e766b8518d8c076d9bf836
$pdf->Output("Question_Paper.pdf", "I");