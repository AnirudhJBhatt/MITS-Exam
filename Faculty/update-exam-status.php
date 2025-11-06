<?php
    require_once "../Connection/connection.php";

    if (isset($_POST['Exam_ID'])) {
        $Exam_ID = $_POST['Exam_ID'];
        $query = "UPDATE exam SET Result_Status = 1 WHERE Exam_ID = '$Exam_ID'";
        $run = mysqli_query($con, $query);

        echo $run ? "success" : "error";
    }
?>
