<?php
/**
 * Student CBT - Start Timer Endpoint
 * Authoritatively marks the exam start timestamp in the student's session.
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['LoginStudent'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$stud_id = $_SESSION['LoginStudent'];
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
$exam_id = isset($payload['exam_id']) ? (int)$payload['exam_id'] : 0;

if (!$exam_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing exam_id']);
    exit;
}

require_once __DIR__ . '/../Connection/connection.php';

// Fetch exam duration
$stmt = $pdo->prepare("SELECT Duration FROM exams WHERE Exam_ID = ? LIMIT 1");
$stmt->execute([$exam_id]);
$examRow = $stmt->fetch();

if (!$examRow) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Exam not found']);
    exit;
}

$durationSec = (int)$examRow['Duration'] * 60;
$sessionStartKey = "exam_start_{$exam_id}_{$stud_id}";

// If not yet started, set start time to now; if already started, preserve original start time
if (!isset($_SESSION[$sessionStartKey])) {
    $_SESSION[$sessionStartKey] = time();
}

$startTime = (int)$_SESSION[$sessionStartKey];
$deadlineMs = ($startTime + $durationSec) * 1000;
$remainingSec = max(0, ($startTime + $durationSec) - time());

echo json_encode([
    'success' => true,
    'start_time' => $startTime,
    'deadline_ms' => $deadlineMs,
    'remaining_sec' => $remainingSec
]);
