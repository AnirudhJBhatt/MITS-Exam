<?php
/**
 * Asynchronous Course-Faculty Unmapping Handler
 */

require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/../Common/auth.php";

check_role('DeptAdmin');

if (isset($_POST['course'], $_POST['faculty'], $_POST['acad_year'])) {
    $course    = (int)$_POST['course'];
    $faculty   = trim($_POST['faculty']);
    $acad_year = trim($_POST['acad_year']);

    if ($pdo) {
        $stmt1 = $pdo->prepare("DELETE FROM course_mapping WHERE Course_ID = ? AND Fac_ID = ? AND Acad_Year = ?");
        $stmt1->execute([$course, $faculty, $acad_year]);

        $stmt2 = $pdo->prepare("DELETE FROM student_course_mapping WHERE Course_ID = ? AND Fac_ID = ? AND Acad_Year = ?");
        $stmt2->execute([$course, $faculty, $acad_year]);

        echo "success";
        exit;
    } elseif ($con) {
        $stmt1 = $con->prepare("DELETE FROM course_mapping WHERE Course_ID = ? AND Fac_ID = ? AND Acad_Year = ?");
        $stmt1->bind_param("iss", $course, $faculty, $acad_year);
        $stmt1->execute();

        $stmt2 = $con->prepare("DELETE FROM student_course_mapping WHERE Course_ID = ? AND Fac_ID = ? AND Acad_Year = ?");
        $stmt2->bind_param("iss", $course, $faculty, $acad_year);
        $stmt2->execute();

        echo "success";
        exit;
    }
}

echo "error";