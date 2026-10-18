<?php
/**
 * Student CBT Autosave Endpoint
 * Saves real-time student answers and progress to prevent data loss.
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['LoginStudent'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../Connection/connection.php';

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!$payload || !isset($payload['exam_id'], $payload['question_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid autosave payload']);
    exit;
}

$stud_id = $_SESSION['LoginStudent'];
$exam_id = (int)$payload['exam_id'];
$question_id = (int)$payload['question_id'];
$answer_status = !empty($payload['answer_status']) ? substr(trim($payload['answer_status']), 0, 20) : 'answered';

$student_answer = null;
if (isset($payload['student_answer'])) {
    $student_answer = is_string($payload['student_answer']) ? $payload['student_answer'] : json_encode($payload['student_answer']);
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO `exam_drafts` (`Exam_ID`, `Stud_ID`, `Question_ID`, `Student_Answer`, `Answer_Status`, `Updated_At`)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            `Student_Answer` = VALUES(`Student_Answer`),
            `Answer_Status` = VALUES(`Answer_Status`),
            `Updated_At` = NOW()
    ");
    $stmt->execute([$exam_id, $stud_id, $question_id, $student_answer, $answer_status]);

    echo json_encode([
        'success' => true,
        'saved_at' => date('H:i:s'),
        'question_id' => $question_id
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error while saving draft: ' . $e->getMessage()
    ]);
}
