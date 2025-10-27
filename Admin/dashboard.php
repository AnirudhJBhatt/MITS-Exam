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

    <!-- NAVBAR -->
    <?php include '../Common/header.php'; ?>
    <!-- SIDEBAR -->
    <?php include '../Common/admin-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main>
        <div class="dashboard-header">
            <h4 class="mb-0 fw-bold">Admin Dashboard</h4>
        </div>

        <!-- Dashboard Cards -->
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card p-4 text-center">
                    <i class="bi bi-people-fill mb-3"></i>
                    <h5>Total Students</h5>
                    <p class="text-muted mb-0">1,250 Registered</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 text-center">
                    <i class="bi bi-pencil-square mb-3"></i>
                    <h5>Ongoing Exams</h5>
                    <p class="text-muted mb-0">3 Active Exams</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 text-center">
                    <i class="bi bi-bar-chart-fill mb-3"></i>
                    <h5>Reports Generated</h5>
                    <p class="text-muted mb-0">540 Reports</p>
                </div>
            </div>
        </div>
    </main>
    <?php include '../Common/footer.php'; ?>
</body>
</html>