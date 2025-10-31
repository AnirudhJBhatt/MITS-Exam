<?php  
	session_start();
	// if (!$_SESSION["LoginAdmin"]){
	// 	echo '<script> alert("You Are Not Authorized For This Link");</script>';
    //     echo '<script>window.location="../login/login.php"</script>';
	// }
	require_once "../connection/connection.php";

    // Fetch total students
    $student_query = mysqli_query($con, "SELECT COUNT(*) AS total_students FROM student");
    $student_row = mysqli_fetch_array($student_query);
    $total_students = $student_row['total_students'];

    // Fetch total drives
    $drive_query = mysqli_query($con, "SELECT COUNT(*) AS total_drives FROM drive");
    $drive_row = mysqli_fetch_array($drive_query);
    $total_drives = $drive_row['total_drives'];

    // Fetch total applications
    $app_query = mysqli_query($con, "SELECT COUNT(*) AS total_applications FROM application");
    $app_row = mysqli_fetch_array($app_query);
    $total_applications = $app_row['total_applications'];

    // Fetch total placed students (assuming a 'status' column in students table)
    $placed_query = mysqli_query($con, "SELECT COUNT(*) AS placed_students FROM placement");
    $placed_row = mysqli_fetch_array($placed_query);
    $total_placed = $placed_row['placed_students'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include('../Common/header.php'); ?>
    <?php include('../Common/admin-sidebar.php'); ?>

    <main role="main" class="mt-1 mb-3 px-md-4 page-content-index">
        <div class="sub-main">
            <div class="dashboard-header flex-wrap flex-md-no-wrap px-3 pt-2 pb-1 mb-3 text-white">
                <div class="d-flex flex-row">
                    <div class="p-2"><h4>Admin Dashboard</h4></div>
                </div>
            </div>
            <div class="row g-4 text-white mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-people-fill fs-1"></i>
                            <h5>Total Students</h5>
                            <p class="fs-4"><?php echo $total_students; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-briefcase-fill fs-1"></i>
                            <h5>Total Drives</h5>
                            <p class="fs-4"><?php echo $total_drives; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-file-earmark-check-fill fs-1"></i>
                            <h5>Applications</h5>
                            <p class="fs-4"><?php echo $total_applications; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-award-fill fs-1"></i>
                            <h5>Placed Students</h5>
                            <p class="fs-4"><?php echo $total_placed; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card p-3 shadow-sm">
                        <h5 class="text-center">Monthly Applications</h5>
                        <canvas id="applicationsChart" height="250"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3 shadow-sm">
                        <h5 class="text-center">Placement Status</h5>
                        <canvas id="placementChart" height="250"></canvas>
                    </div> 
                </div>
            </div>
        </div>
    </main>
    <main class="container mt-4 mb-5">

        <!-- Statistic Cards -->
        
    </main>

    <?php include('../Common/footer.php'); ?>

    <!-- Chart Scripts -->
    <script>
        const applicationsChart = new Chart(document.getElementById('applicationsChart'), {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                datasets: [{
                    label: 'Applications',
                    data: [120, 200, 150, 300, 250],
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Applications per Month' }
                }
            }
        });

        const placementChart = new Chart(document.getElementById('placementChart'), {
            type: 'pie',
            data: {
                labels: ['Placed', 'Not Placed'],
                datasets: [{
                    data: [900, 600],
                    backgroundColor: ['#28a745', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'Placement Statistics' }
                }
            }
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
