<?php
    require_once "../Connection/connection.php";

    echo "<h5 class='mb-3'>Exam Details</h5>";

    echo "<table class='table table-bordered'>
    <tr><th>Title</th><td>{$_POST['Exam_Title']}</td></tr>
    <tr><th>Programme</th><td>{$_POST['Prog_ID']}</td></tr>
    <tr><th>Semester</th><td>S{$_POST['Semester']}</td></tr>
    <tr><th>Course</th><td>{$_POST['Course_ID']}</td></tr>
    <tr><th>Duration</th><td>{$_POST['Duration']} minutes</td></tr>
    <tr><th>Start Time</th><td>{$_POST['Start_Time']}</td></tr>
    <tr><th>End Time</th><td>{$_POST['End_Time']}</td></tr>
    </table>";

    echo "<hr><h5>Questions Preview</h5>";

    if (!empty($_POST['preview_qids'])) {

        echo "<ol class='list-group list-group-numbered'>";

        foreach ($_POST['preview_qids'] as $qid) {

            $qid = mysqli_real_escape_string($con, $qid);
            $q = mysqli_fetch_assoc(
                mysqli_query($con, "SELECT * FROM question_bank WHERE Q_ID = '$qid'")
            );

            echo "<li class='list-group-item'>";
            echo "<b>Question:</b> {$q['Question_Text']}<br>";

            // Diagram questions
            if ($q['Type_ID'] == 9 && !empty($q['Diagram_Image'])) {
                echo "<img src='data:image/jpeg;base64," .
                    base64_encode($q['Diagram_Image']) .
                    "' class='img-fluid mt-2'>";
            }

            echo "<div class='text-muted mt-1'>Marks: {$q['Marks']}</div>";
            echo "</li>";
        }

        echo "</ol>";

    } else {
        echo "<div class='alert alert-warning'>No questions selected.</div>";
    }
?>
