 <?php  
	session_start();
	if (!isset($_SESSION['LoginAdmin'])) {
        echo "<script>alert('You Are Not Authorize Person For This link'); window.location.href='../index.php';</script>";
        exit;
    }
	require_once "../Connection/connection.php";
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Dashboard</title>
</head>

<body>
    <?php include '../Common/header.php'; ?>
    <?php include '../Common/admin-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main>
        <div class="dashboard-header">
            <h4 class="mb-0 fw-bold">View Exams</h4>
            
        </div>

    </main>
    <?php include '../Common/footer.php'; ?>
</body>
</html>