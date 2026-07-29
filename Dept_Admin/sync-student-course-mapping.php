<?php
require_once "../Connection/connection.php";

// Get all course mappings
$qMap = mysqli_query($con, "SELECT * FROM course_mapping");

while($map = mysqli_fetch_assoc($qMap)){

    $course = $map['Course_ID'];
    $faculty = $map['Fac_ID'];
    $dept = $map['Dept_ID'];
    $acadYear = $map['Acad_Year'];

    // Get course details
    $qCourse = mysqli_query($con,
        "SELECT Semester, Prog_ID
         FROM courses
         WHERE Course_ID='$course'");

    $courseData = mysqli_fetch_assoc($qCourse);

    $semester = $courseData['Semester'];
    $prog = $courseData['Prog_ID'];

    // Fetch students
    $qStudents = mysqli_query($con,
        "SELECT Stud_ID
         FROM student
         WHERE Stud_Dept='$dept'
         AND Stud_Sem='$semester'
         AND Stud_Prog='$prog'
         AND Curr_AY='$acadYear'");

    while($stud = mysqli_fetch_assoc($qStudents)){

        $sid = $stud['Stud_ID'];

        // Prevent duplicates
        $check = mysqli_query($con,
            "SELECT 1
             FROM student_course_mapping
             WHERE Stud_ID='$sid'
             AND Course_ID='$course'
             AND Acad_Year='$acadYear'");

        if(mysqli_num_rows($check)==0){

            mysqli_query($con,
                "INSERT INTO student_course_mapping
                (Stud_ID, Course_ID, Fac_ID, Dept_ID, Semester, Acad_Year)
                VALUES
                ('$sid','$course','$faculty','$dept','$semester','$acadYear')");
        }
    }
}

echo "Student course mapping synchronized successfully.";
?>