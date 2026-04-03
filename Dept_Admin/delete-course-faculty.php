<?php
    session_start();
    require_once "../Connection/connection.php";

    if (!isset($_SESSION['LoginDeptAdmin'])) {
        echo "<script>alert('You Are Not Authorize Person For This link'); window.location.href='../index.php';</script>";
        exit;
    }

    if(isset($_POST['course'], $_POST['faculty'], $_POST['acad_year'])){

        $course = $_POST['course'];
        $faculty = $_POST['faculty'];
        $acad_year = $_POST['acad_year'];

        // Delete from course_mapping
        mysqli_query($con, 
            "DELETE FROM course_mapping 
            WHERE Course_ID='$course' 
            AND Fac_ID='$faculty' 
            AND Acad_Year='$acad_year'");

        // Delete from student_course_mapping
        mysqli_query($con, 
            "DELETE FROM student_course_mapping 
            WHERE Course_ID='$course' 
            AND Fac_ID='$faculty' 
            AND Acad_Year='$acad_year'");

        echo "success";
    }
?>