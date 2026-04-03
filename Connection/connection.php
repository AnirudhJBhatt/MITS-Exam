<?php 
	$config = include __DIR__ . '/../env.php';
	

	$host = $config['DB_HOST'];
	$user = $config['DB_USER'];
	$pass = $config['DB_PASSWORD'];
	$db   = $config['DB_NAME'];

	$con = mysqli_connect("$host", $user, $pass, $db);

	if (!$con) {
		echo "Failed to connect";
	}
?>
