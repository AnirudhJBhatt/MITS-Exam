<?php
require_once "Connection/connection.php";

$cookieJar = __DIR__ . '/test_cookie_faculty.txt';

// Log in as FT0638
$ch = curl_init('http://localhost/MITS-Exam/index.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$html = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m);
$csrf = $m[1] ?? '';

$ch = curl_init('http://localhost/MITS-Exam/index.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['csrf_token' => $csrf, 'username' => 'FT0638', 'password' => 'Faculty123*']));
curl_exec($ch);
curl_close($ch);

// Fetch questions for FT0638
$stmt = $pdo->prepare("SELECT * FROM new_question_bank WHERE Fac_ID = 'FT0638' ORDER BY Bank_ID DESC LIMIT 8");
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Fetched " . count($questions) . " questions for FT0638.\n";
foreach ($questions as $q) {
    echo "ID: {$q['Bank_ID']} | Type: {$q['Question_Type']} | Text: {$q['Question_Text']}\n";
}
