<?php 
    // Set timezone to IST for correct time comparison
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

	$con = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
	if(!$con){
		echo "Failed to connect";
	}

?>