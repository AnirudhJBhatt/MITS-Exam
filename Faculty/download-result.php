<<<<<<< HEAD
<<<<<<< HEAD
<?php
require_once('../Connection/connection.php');
require_once('../vendor/autoload.php');

if (!isset($_GET['Exam_ID']) || !isset($_GET['Stud_ID'])) {
    die("Invalid Request");
}

$Exam_ID = mysqli_real_escape_string($con, $_GET['Exam_ID']);
$Stud_ID = mysqli_real_escape_string($con, $_GET['Stud_ID']);

// =====================
// FETCH STUDENT
// =====================
$sQuery = mysqli_query($con, "SELECT Stud_Name FROM student WHERE Stud_ID='$Stud_ID'");
$sRow = mysqli_fetch_assoc($sQuery);
$Stud_Name = $sRow['Stud_Name'];

// =====================
// FETCH EXAM DETAILS
// =====================
$examQuery = "
    SELECT e.*, c.Course_Name, p.Prog_Name
    FROM exam e
    JOIN courses c ON e.Course_ID = c.Course_ID
    JOIN programmes p ON e.Prog_ID = p.Prog_ID
    WHERE e.Exam_ID = '$Exam_ID'
";

$examRun = mysqli_query($con, $examQuery);
$exam = mysqli_fetch_assoc($examRun);
$examDate = date("d-m-Y", strtotime($exam['Start_Time']));
$duration = $exam['Duration'];

// =====================
// FETCH RESULT
// =====================
$rQuery = "SELECT * FROM result WHERE Stud_ID='$Stud_ID' AND Exam_ID='$Exam_ID'";
$rRun = mysqli_query($con, $rQuery);
$result = mysqli_fetch_assoc($rRun);

if (!$result) {
    die("No result found");
}

$details = json_decode($result['Details'], true);

// =====================
// CREATE PDF
// =====================
$pdf = new TCPDF();
$pdf->SetCreator('MITS');
$pdf->SetAuthor('MITS');
$pdf->SetTitle('Student Result');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// LOGO
$pdf->Image('../Images/MITS Logo.png', 80, 10, 40);
$pdf->Ln(20);

// =====================
// HEADER
// =====================
$html = '

<h3 style="text-align:center;">Muthoot Institute of Technology and Science</h3>
<h4 style="text-align:center;">DEPARTMENT OF '.$exam['Dept'].'</h4>
<h4 style="text-align:center;">SEMESTER '.$exam['Semester'].'</h4>
<h4 style="text-align:center;">'.$exam['Course_Name'].'</h4>
<h4 style="text-align:center;">'.$exam['Exam_Title'].'</h4>

<br>

<table width="100%" cellpadding="5">
    <tr>
        <td width="50%"><strong>Name:</strong> '.$Stud_ID.' - '.$Stud_Name.'</td>
        <td width="25%" align="right"><strong>Date:</strong> '.$examDate.'</td>
        <td width="25%" align="right"><strong>Duration:</strong> '.$duration.' Min</td>
    </tr>
</table>

<br>
';

// =====================
// TABLE
// =====================
$html .= '
<table border="1" cellpadding="5">
<tr style="background-color:#f2f2f2;">
    <th width="8%">Q.No</th>
    <th width="42%">Question</th>
    <th width="12%">Correct</th>
    <th width="12%">Selected</th>
    <th width="10%">Marks</th>
    <th width="8%">CO</th>
</tr>
';

$qn = 1;
$total = 0;

foreach ($details as $d) {

    $Q_ID = $d['Q_ID'];
    $Selected = $d['Selected'];
    $Correct = $d['Correct'];
    $Marks = $d['Obtained_Marks'];

    // Fetch question + CO
    $qQuery = mysqli_query($con, "SELECT Question_Text, CO FROM question_bank WHERE Q_ID='$Q_ID'");
    $qRow = mysqli_fetch_assoc($qQuery);

    $Question = $qRow['Question_Text'] ?? 'N/A';
    $CO = $qRow['CO'] ?? '-';

    $total += $Marks;

    $html .= "
    <tr>
        <td>$qn</td>
        <td>$Question</td>
        <td>$Correct</td>
        <td>$Selected</td>
        <td>$Marks</td>
        <td>$CO</td>
    </tr>
    ";

    $qn++;
}


$html .= "</table>";

// =====================
// TOTAL
// =====================
$html .= "<br><h3>Total Marks: $total</h3>";

// =====================
// OUTPUT
// =====================
$pdf->writeHTML($html);
=======
<?php
require_once('../Connection/connection.php');
require_once('../vendor/autoload.php');

if (!isset($_GET['Exam_ID']) || !isset($_GET['Stud_ID'])) {
    die("Invalid Request");
}

$Exam_ID = mysqli_real_escape_string($con, $_GET['Exam_ID']);
$Stud_ID = mysqli_real_escape_string($con, $_GET['Stud_ID']);

// =====================
// FETCH STUDENT
// =====================
$sQuery = mysqli_query($con, "SELECT Stud_Name FROM student WHERE Stud_ID='$Stud_ID'");
$sRow = mysqli_fetch_assoc($sQuery);
$Stud_Name = $sRow['Stud_Name'];

// =====================
// FETCH EXAM DETAILS
// =====================
$examQuery = "
    SELECT e.*, c.Course_Name, p.Prog_Name
    FROM exam e
    JOIN courses c ON e.Course_ID = c.Course_ID
    JOIN programmes p ON e.Prog_ID = p.Prog_ID
    WHERE e.Exam_ID = '$Exam_ID'
";

$examRun = mysqli_query($con, $examQuery);
$exam = mysqli_fetch_assoc($examRun);
$examDate = date("d-m-Y", strtotime($exam['Start_Time']));
$duration = $exam['Duration'];

// =====================
// FETCH RESULT
// =====================
$rQuery = "SELECT * FROM result WHERE Stud_ID='$Stud_ID' AND Exam_ID='$Exam_ID'";
$rRun = mysqli_query($con, $rQuery);
$result = mysqli_fetch_assoc($rRun);

if (!$result) {
    die("No result found");
}

$details = json_decode($result['Details'], true);

// =====================
// CREATE PDF
// =====================
$pdf = new TCPDF();
$pdf->SetCreator('MITS');
$pdf->SetAuthor('MITS');
$pdf->SetTitle('Student Result');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// LOGO
$pdf->Image('../Images/MITS Logo.png', 80, 10, 40);
$pdf->Ln(20);

// =====================
// HEADER
// =====================
$html = '

<h3 style="text-align:center;">Muthoot Institute of Technology and Science</h3>
<h4 style="text-align:center;">DEPARTMENT OF '.$exam['Dept'].'</h4>
<h4 style="text-align:center;">SEMESTER '.$exam['Semester'].'</h4>
<h4 style="text-align:center;">'.$exam['Course_Name'].'</h4>
<h4 style="text-align:center;">'.$exam['Exam_Title'].'</h4>

<br>

<table width="100%" cellpadding="5">
    <tr>
        <td width="50%"><strong>Name:</strong> '.$Stud_ID.' - '.$Stud_Name.'</td>
        <td width="25%" align="right"><strong>Date:</strong> '.$examDate.'</td>
        <td width="25%" align="right"><strong>Duration:</strong> '.$duration.' Min</td>
    </tr>
</table>

<br>
';

// =====================
// TABLE
// =====================
$html .= '
<table border="1" cellpadding="5">
<tr style="background-color:#f2f2f2;">
    <th width="8%">Q.No</th>
    <th width="42%">Question</th>
    <th width="12%">Correct</th>
    <th width="12%">Selected</th>
    <th width="10%">Marks</th>
    <th width="8%">CO</th>
</tr>
';

$qn = 1;
$total = 0;

foreach ($details as $d) {

    $Q_ID = $d['Q_ID'];
    $Selected = $d['Selected'];
    $Correct = $d['Correct'];
    $Marks = $d['Obtained_Marks'];

    // Fetch question + CO
    $qQuery = mysqli_query($con, "SELECT Question_Text, CO FROM question_bank WHERE Q_ID='$Q_ID'");
    $qRow = mysqli_fetch_assoc($qQuery);

    $Question = $qRow['Question_Text'] ?? 'N/A';
    $CO = $qRow['CO'] ?? '-';

    $total += $Marks;

    $html .= "
    <tr>
        <td>$qn</td>
        <td>$Question</td>
        <td>$Correct</td>
        <td>$Selected</td>
        <td>$Marks</td>
        <td>$CO</td>
    </tr>
    ";

    $qn++;
}


$html .= "</table>";

// =====================
// TOTAL
// =====================
$html .= "<br><h3>Total Marks: $total</h3>";

// =====================
// OUTPUT
// =====================
$pdf->writeHTML($html);
>>>>>>> f1e265abf03ca415a8e766b8518d8c076d9bf836
=======
<?php
require_once('../Connection/connection.php');
require_once('../vendor/autoload.php');

if (!isset($_GET['Exam_ID']) || !isset($_GET['Stud_ID'])) {
    die("Invalid Request");
}

$Exam_ID = mysqli_real_escape_string($con, $_GET['Exam_ID']);
$Stud_ID = mysqli_real_escape_string($con, $_GET['Stud_ID']);

// =====================
// FETCH STUDENT
// =====================
$sQuery = mysqli_query($con, "SELECT Stud_Name FROM student WHERE Stud_ID='$Stud_ID'");
$sRow = mysqli_fetch_assoc($sQuery);
$Stud_Name = $sRow['Stud_Name'];

// =====================
// FETCH EXAM DETAILS
// =====================
$examQuery = "
    SELECT e.*, c.Course_Name, p.Prog_Name
    FROM exam e
    JOIN courses c ON e.Course_ID = c.Course_ID
    JOIN programmes p ON e.Prog_ID = p.Prog_ID
    WHERE e.Exam_ID = '$Exam_ID'
";

$examRun = mysqli_query($con, $examQuery);
$exam = mysqli_fetch_assoc($examRun);
$examDate = date("d-m-Y", strtotime($exam['Start_Time']));
$duration = $exam['Duration'];

// =====================
// FETCH RESULT
// =====================
$rQuery = "SELECT * FROM result WHERE Stud_ID='$Stud_ID' AND Exam_ID='$Exam_ID'";
$rRun = mysqli_query($con, $rQuery);
$result = mysqli_fetch_assoc($rRun);

if (!$result) {
    die("No result found");
}

$details = json_decode($result['Details'], true);

// =====================
// CREATE PDF
// =====================
$pdf = new TCPDF();
$pdf->SetCreator('MITS');
$pdf->SetAuthor('MITS');
$pdf->SetTitle('Student Result');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// LOGO
$pdf->Image('../Images/MITS Logo.png', 80, 10, 40);
$pdf->Ln(20);

// =====================
// HEADER
// =====================
$html = '

<h3 style="text-align:center;">Muthoot Institute of Technology and Science</h3>
<h4 style="text-align:center;">DEPARTMENT OF '.$exam['Dept'].'</h4>
<h4 style="text-align:center;">SEMESTER '.$exam['Semester'].'</h4>
<h4 style="text-align:center;">'.$exam['Course_Name'].'</h4>
<h4 style="text-align:center;">'.$exam['Exam_Title'].'</h4>

<br>

<table width="100%" cellpadding="5">
    <tr>
        <td width="50%"><strong>Name:</strong> '.$Stud_ID.' - '.$Stud_Name.'</td>
        <td width="25%" align="right"><strong>Date:</strong> '.$examDate.'</td>
        <td width="25%" align="right"><strong>Duration:</strong> '.$duration.' Min</td>
    </tr>
</table>

<br>
';

// =====================
// TABLE
// =====================
$html .= '
<table border="1" cellpadding="5">
<tr style="background-color:#f2f2f2;">
    <th width="8%">Q.No</th>
    <th width="42%">Question</th>
    <th width="12%">Correct</th>
    <th width="12%">Selected</th>
    <th width="10%">Marks</th>
    <th width="8%">CO</th>
</tr>
';

$qn = 1;
$total = 0;

foreach ($details as $d) {

    $Q_ID = $d['Q_ID'];
    $Selected = $d['Selected'];
    $Correct = $d['Correct'];
    $Marks = $d['Obtained_Marks'];

    // Fetch question + CO
    $qQuery = mysqli_query($con, "SELECT Question_Text, CO FROM question_bank WHERE Q_ID='$Q_ID'");
    $qRow = mysqli_fetch_assoc($qQuery);

    $Question = $qRow['Question_Text'] ?? 'N/A';
    $CO = $qRow['CO'] ?? '-';

    $total += $Marks;

    $html .= "
    <tr>
        <td>$qn</td>
        <td>$Question</td>
        <td>$Correct</td>
        <td>$Selected</td>
        <td>$Marks</td>
        <td>$CO</td>
    </tr>
    ";

    $qn++;
}


$html .= "</table>";

// =====================
// TOTAL
// =====================
$html .= "<br><h3>Total Marks: $total</h3>";

// =====================
// OUTPUT
// =====================
$pdf->writeHTML($html);
>>>>>>> f1e265abf03ca415a8e766b8518d8c076d9bf836
$pdf->Output("Result_$Stud_Name.pdf", "I");