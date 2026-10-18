<?php
/**
 * MITS Exam Portal - Exam Status Toggle Handler
 * AJAX endpoint for toggling exam status or result publication
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

check_role(['Faculty', 'Admin']);

header('Content-Type: application/json; charset=utf-8');

$fac_id = $_SESSION['LoginFaculty'] ?? $_SESSION['user_id'] ?? '';
$exam_id = (int)($_POST['exam_id'] ?? 0);
$type = $_POST['type'] ?? 'result'; // 'result' or 'exam'

if ($exam_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam identifier.']);
    exit;
}

try {
    if ($type === 'exam') {
        $new_status = in_array($_POST['status'] ?? '', ['draft', 'published', 'archived']) ? $_POST['status'] : 'draft';
        $stmt = $pdo->prepare("UPDATE exams SET Status = ? WHERE Exam_ID = ? AND Fac_ID = ?");
        $stmt->execute([$new_status, $exam_id, $fac_id]);
        echo json_encode(['success' => true, 'new_status' => $new_status]);
    } else {
        $current_status = (int)($_POST['status'] ?? 0);
        $new_status = ($current_status === 1) ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE exams SET Result_Published = ? WHERE Exam_ID = ? AND Fac_ID = ?");
        $stmt->execute([$new_status, $exam_id, $fac_id]);
        echo json_encode(['success' => true, 'new_status' => $new_status]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
