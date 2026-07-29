<?php 
	require_once __DIR__ . '/../vendor/autoload.php';

	if (file_exists(__DIR__ . '/../.env')) {
		try {
			$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
			$dotenv->safeLoad();
		} catch (Exception $e) {
			// Fallback if env file parsing fails
		}
	}

	$host = $_ENV['DB_HOST'];
	$user = $_ENV['DB_USER'];
	$pass = $_ENV['DB_PASSWORD'];
	$db   = $_ENV['DB_NAME'];

	$con = mysqli_connect("$host", $user, $pass, $db);

	if (!$con) {
		echo "Failed to connect";
	}
?>
