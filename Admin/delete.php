<?php
/**
 * MITS Exam Portal - Secure Entity Deletion Handler
 * Universal prepared statements, CSRF protection, and cascading login cleanup
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

// Allow Admin, DeptAdmin, or Faculty
if (!isset($_SESSION['LoginAdmin']) && !isset($_SESSION['LoginFaculty']) && !isset($_SESSION['LoginDeptAdmin'])) {
    http_response_code(403);
    exit("<script>alert('Unauthorized access'); window.location='../index.php';</script>");
}

// For security, enforce POST or valid CSRF verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $target = $_POST['target'] ?? '';
    $id = trim($_POST['id'] ?? '');
} else {
    // If legacy GET request is provided, verify CSRF if token is present, else reject
    $target = '';
    $id = '';
    if (isset($_GET['Stud_ID'])) { $target = 'student'; $id = trim($_GET['Stud_ID']); }
    elseif (isset($_GET['Fac_ID'])) { $target = 'faculty'; $id = trim($_GET['Fac_ID']); }
    elseif (isset($_GET['Exam_ID'])) { $target = 'exam'; $id = trim($_GET['Exam_ID']); }
    elseif (isset($_GET['Q_ID'])) { $target = 'question'; $id = trim($_GET['Q_ID']); }

    if (isset($_GET['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'])) {
            http_response_code(403);
            exit("<script>alert('Invalid CSRF token.'); history.back();</script>");
        }
    }
}

if (!$target || !$id) {
    exit("<script>alert('Invalid deletion request parameters.'); history.back();</script>");
}

try {
    $pdo->beginTransaction();

    if ($target === 'student') {
        // Only Admin or DeptAdmin can delete students
        if (!isset($_SESSION['LoginAdmin']) && !isset($_SESSION['LoginDeptAdmin'])) {
            throw new Exception("Permission denied to delete student.");
        }
        $stmt1 = $pdo->prepare("DELETE FROM student WHERE Stud_ID = ?");
        $stmt1->execute([$id]);

        $stmt2 = $pdo->prepare("DELETE FROM login WHERE ID = ? AND Role = 'Student'");
        $stmt2->execute([$id]);

        $pdo->commit();
        echo "<script>alert('Student successfully deleted.'); history.back();</script>";
        exit;
    }

    elseif ($target === 'faculty') {
        // Only Admin can delete faculty
        if (!isset($_SESSION['LoginAdmin'])) {
            throw new Exception("Permission denied to delete faculty.");
        }
        $stmt1 = $pdo->prepare("DELETE FROM faculty WHERE Fac_ID = ?");
        $stmt1->execute([$id]);

        $stmt2 = $pdo->prepare("DELETE FROM login WHERE ID = ? AND Role = 'Faculty'");
        $stmt2->execute([$id]);

        $pdo->commit();
        echo "<script>alert('Faculty successfully deleted.'); history.back();</script>";
        exit;
    }

    elseif ($target === 'exam') {
        // Admin or Faculty can delete exams
        $stmt = $pdo->prepare("DELETE FROM exams WHERE Exam_ID = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        echo "<script>alert('Exam successfully deleted.'); window.location.href='../Faculty/manage-exams.php';</script>";
        exit;
    }

    elseif ($target === 'question') {
        // Admin or Faculty can delete questions
        $stmt = $pdo->prepare("DELETE FROM new_question_bank WHERE Bank_ID = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            $stmtLegacy = $pdo->prepare("DELETE FROM question_bank WHERE Q_ID = ?");
            $stmtLegacy->execute([$id]);
        }

        $pdo->commit();
        echo "<script>alert('Question successfully deleted.'); window.location.href='../Faculty/question-bank.php';</script>";
        exit;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errMsg = addslashes($e->getMessage());
    echo "<script>alert('Delete failed: {$errMsg}'); history.back();</script>";
    exit;
}