<?php
    session_start();
    require_once "../Connection/connection.php";

    if (!isset($_SESSION["LoginStudent"])) {
        echo "<script>alert('Unauthorized access!'); window.location='../Login/Login.php';</script>";
        exit;
    }

    if (!isset($_GET['Exam_ID']) || !ctype_digit($_GET['Exam_ID'])) {
        die("Invalid Exam ID");
    }

    $Stud_ID = $_SESSION['LoginStudent'];
    $studQuery = "SELECT * FROM student WHERE Stud_ID = '$Stud_ID'";
    $studRun = mysqli_query($con, $studQuery);
    $studRow = mysqli_fetch_assoc($studRun);
    $Stud_Name = $studRow['Stud_Name'];

    $Exam_ID = intval($_GET['Exam_ID']);

    $examQuery = "SELECT * FROM exam WHERE Exam_ID = $Exam_ID";
    $examRun = mysqli_query($con, $examQuery);
    $exam = mysqli_fetch_assoc($examRun);
    if (!$exam) die("Exam not found.");

    $durationMinutes = (int)$exam["Duration"];
    $qids = json_decode($exam['Q_IDs'], true);
    echo "<script>console.log('QIDs:', " . $durationMinutes . ");</script>";
    $qid_list = implode(",", $qids);

    // Fetch Questions
    $qQuery = "SELECT * FROM question_bank WHERE Q_ID IN ($qid_list) ORDER BY FIELD(Q_ID, $qid_list)";
    $qRun = mysqli_query($con, $qQuery);

    $questions = [];
    while ($q = mysqli_fetch_assoc($qRun)) {
        // Decode JSON for matching or options
        unset($q['Diagram_Image']);  // ← FIXED
        if ($q['Type_ID'] == 2) {
            $q['matching'] = json_decode($q['Options'], true);
        }
        if ($q['Type_ID'] == 3) {
            $q['options'] = json_decode($q['Options'], true);
        }
        $questions[] = $q;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $exam['Exam_Title']; ?> – Attempt</title>  
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Css/exam.css" >
    <link rel="stylesheet" href="../Css/style.css">

</head>
<body>

    <!-- Fullscreen Start Overlay -->
    <div id="startOverlay" class="overlay-fullscreen">
        <h2>Click below to start your exam in full-screen mode</h2>
        <button id="startExamBtn" class="btn btn-primary btn-lg mt-3">Start Exam</button>
    </div>

    <!-- Fullscreen Exit Warning -->
    <div id="fullscreenWarning" class="overlay-fullscreen" style="display:none;">
        <h3>You exited full-screen mode.</h3>
        <p>Please return to full-screen to continue.</p>
        <button id="returnFullscreenBtn" class="btn btn-warning mt-3">Return to Fullscreen</button>
    </div>
    <nav class="navbar navbar-expand-lg fixed-top px-4">
        <div class="d-flex align-items-center">
            <span class="fw-bold text-dark"><?php echo $Stud_Name;?></span>
        </div>
        <a class="navbar-brand position-absolute top-50 start-50 translate-middle" href="#">
            <img src="../Images/MITS Logo.png" alt="MITS Logo" height="75" class="me-2">
        </a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="timer" id="timer"></div>
        </div>
    </nav>

    <div class="exam-sidebar">
        <h6>Question Status</h6>
        <div class="question-status" id="questionStatus"></div>
    </div>

    <main>
        <form id="examForm" method="POST" action="">
            <input type="hidden" name="Exam_ID" value="<?php echo $Exam_ID; ?>">
            <div class="question-card" id="questionCard"></div>
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-success px-4 py-2"><i class="bi bi-check-circle"></i> Submit Exam</button>
            </div>
        </form>
    </main>

    <script>
        const questions = <?= json_encode($questions); ?>;
        const durationMinutes = <?= $durationMinutes; ?>;
    </script>

    <script src="../Css/exam.js"></script>
</body>
</html>
