<?php 
/**
 * Database Connection & Environment Configuration
 * Provides both $con (MySQLi - backward compatible) and $pdo (PDO - secure prepared statements)
 */

// Set timezone to IST for accurate academic scheduling
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/../vendor/autoload.php';

if (class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();
}

$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_port = $_ENV['DB_PORT'] ?? '3306';
$db_user = $_ENV['DB_USER'] ?? 'root';
$db_pass = $_ENV['DB_PASSWORD'] ?? '';
$db_name = $_ENV['DB_NAME'] ?? 'mits-exam';
$JWT_SECRET = $_ENV['JWT_SECRET'] ?? '';

// 1. Backward Compatible MySQLi Connection
$con = mysqli_connect($db_host, $db_user, $db_pass, $db_name, (int)$db_port);
if (!$con) {
    error_log("MySQLi Connection Failed: " . mysqli_connect_error());
} else {
    mysqli_set_charset($con, "utf8mb4");
    mysqli_query($con, "SET time_zone = '+05:30'");
}

// 2. High-Performance PDO Connection
$pdo = null;
try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    $pdo->exec("SET time_zone = '+05:30'");
} catch (PDOException $e) {
    error_log("PDO Connection Failed: " . $e->getMessage());
}

/**
 * Global helper to access PDO instance
 * @return PDO|null
 */
function get_pdo() {
    global $pdo;
    return $pdo;
}