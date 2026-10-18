<?php
/**
 * MITS Exam Portal - Secure Diagram Streamer
 * Parameterized PDO query with blob and image path fallback
 */
require_once "../Connection/connection.php";

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT Image_Path FROM exam_questions WHERE Question_ID = ? LIMIT 1");
        $stmt->execute([$id]);
        $path = $stmt->fetchColumn();

        if ($path && file_exists(__DIR__ . '/../' . $path)) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mimes = [
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml'
            ];
            header('Content-Type: ' . ($mimes[$ext] ?? 'image/jpeg'));
            readfile(__DIR__ . '/../' . $path);
            exit;
        }

        // Check new_question_bank
        $stmtQB = $pdo->prepare("SELECT Image_Path FROM new_question_bank WHERE Bank_ID = ? LIMIT 1");
        $stmtQB->execute([$id]);
        $pathQB = $stmtQB->fetchColumn();

        if ($pathQB && file_exists(__DIR__ . '/../' . $pathQB)) {
            $ext = strtolower(pathinfo($pathQB, PATHINFO_EXTENSION));
            $mimes = [
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml'
            ];
            header('Content-Type: ' . ($mimes[$ext] ?? 'image/jpeg'));
            readfile(__DIR__ . '/../' . $pathQB);
            exit;
        }
    } catch (Exception $e) {
        // Fallback
    }
}

http_response_code(404);
echo "Image not found";
exit;
