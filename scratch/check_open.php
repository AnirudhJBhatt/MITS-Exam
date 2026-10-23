<?php
require __DIR__ . '/../Connection/connection.php';
// Check if any exam has NULL or future End_Time
$stmt = $pdo->query("SELECT Exam_ID, Exam_Name, Start_Time, End_Time FROM exams WHERE Status = 'published' AND (End_Time IS NULL OR End_Time > NOW())");
$open = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Open exams count: " . count($open) . "\n";
print_r($open);
