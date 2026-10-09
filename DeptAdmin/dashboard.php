 <?php  
	session_start();
	if (!isset($_SESSION['LoginDeptAdmin'])) {
        echo "<script>alert('You Are Not Authorize Person For This link'); window.location.href='../index.php';</script>";
        exit;
    }
	require_once "../Connection/connection.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dept Admin - Dashboard</title>
    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../Common/style.css">  x
</head>
<body>
    <!-- NAVBAR -->
    <?php include '../Common/header.php'; ?>
    <!-- SIDEBAR -->
    <?php include '../Common/deptadmin-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main>
        <div class="container-fluid py-4">
            <div class="dashboard-header d-flex justify-content-between align-items-center mb-5 pb-3 border-bottom">
                <div>
                    <h2 class="mb-1">Department Dashboard</h2>
                    <p class="text-muted mb-0">Overview of department activities and statistics.</p>
                </div>
            </div>

            <!-- Dashboard Cards -->
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card p-4 text-center shadow-sm border-0 rounded-4 hover-elevate bg-white h-100">
                        <div class="icon-box bg-primary-subtle">
                            <i class="ti ti-users"></i>
                        </div>
                        <div class="stat-value text-primary mb-2">1,250</div>
                        <h6 class="fw-bold text-secondary text-uppercase" style="letter-spacing: 1px;">Total Students</h6>
                        <div class="mt-3 pt-3 border-top text-start">
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1"><i class="ti ti-trending-up me-1"></i>+5% this semester</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card p-4 text-center shadow-sm border-0 rounded-4 hover-elevate bg-white h-100">
                        <div class="icon-box bg-success-subtle">
                            <i class="ti ti-file-pencil"></i>
                        </div>
                        <div class="stat-value text-success mb-2">12</div>
                        <h6 class="fw-bold text-secondary text-uppercase" style="letter-spacing: 1px;">Ongoing Exams</h6>
                        <div class="mt-3 pt-3 border-top text-start">
                            <span class="text-muted small"><i class="ti ti-clock me-1"></i>Last updated just now</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card p-4 text-center shadow-sm border-0 rounded-4 hover-elevate bg-white h-100">
                        <div class="icon-box bg-warning-subtle">
                            <i class="ti ti-book-2"></i>
                        </div>
                        <div class="stat-value text-warning mb-2">24</div>
                        <h6 class="fw-bold text-secondary text-uppercase" style="letter-spacing: 1px;">Active Courses</h6>
                        <div class="mt-3 pt-3 border-top text-start">
                            <span class="text-muted small"><i class="ti ti-check me-1"></i>All mapped correctly</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card p-4 text-center shadow-sm border-0 rounded-4 hover-elevate bg-white h-100">
                        <div class="icon-box bg-info-subtle">
                            <i class="ti ti-school"></i>
                        </div>
                        <div class="stat-value text-info mb-2">45</div>
                        <h6 class="fw-bold text-secondary text-uppercase" style="letter-spacing: 1px;">Faculty Members</h6>
                        <div class="mt-3 pt-3 border-top text-start">
                            <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2 py-1"><i class="ti ti-activity me-1"></i>38 Active today</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4 mt-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                            <h5 class="fw-bold"><i class="ti ti-bell text-primary me-2"></i>Recent Activity</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start mb-4 pb-3 border-bottom">
                                <div class="bg-primary-subtle text-primary p-2 rounded-3 me-3"><i class="ti ti-file-plus fs-5"></i></div>
                                <div>
                                    <h6 class="mb-1 fw-bold">New Exam Scheduled</h6>
                                    <p class="text-muted small mb-0">Midterm for Computer Networks scheduled by Dr. Smith.</p>
                                </div>
                                <span class="ms-auto text-muted small">2 hrs ago</span>
                            </div>
                            <div class="d-flex align-items-start mb-4 pb-3 border-bottom">
                                <div class="bg-success-subtle text-success p-2 rounded-3 me-3"><i class="ti ti-user-check fs-5"></i></div>
                                <div>
                                    <h6 class="mb-1 fw-bold">Student Enrollments Updated</h6>
                                    <p class="text-muted small mb-0">15 new students enrolled in Data Structures.</p>
                                </div>
                                <span class="ms-auto text-muted small">5 hrs ago</span>
                            </div>
                            <div class="d-flex align-items-start">
                                <div class="bg-warning-subtle text-warning p-2 rounded-3 me-3"><i class="ti ti-alert-circle fs-5"></i></div>
                                <div>
                                    <h6 class="mb-1 fw-bold">Pending Approvals</h6>
                                    <p class="text-muted small mb-0">3 faculty requests pending for exam creation.</p>
                                </div>
                                <span class="ms-auto text-muted small">1 day ago</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 rounded-4 h-100 bg-primary text-white position-relative overflow-hidden">
                        <!-- Decorative background circle -->
                        <div class="position-absolute rounded-circle bg-white opacity-10" style="width: 200px; height: 200px; top: -50px; right: -50px;"></div>
                        <div class="card-body p-4 d-flex flex-column justify-content-center align-items-center text-center position-relative z-1">
                            <i class="ti ti-report-analytics display-2 mb-3 text-white opacity-75"></i>
                            <h4 class="fw-bold mb-3">Generate Reports</h4>
                            <p class="text-white opacity-75 mb-4">Need a detailed breakdown of department performance? Generate a comprehensive report now.</p>
                            <button class="btn btn-light rounded-pill px-4 fw-bold shadow-sm hover-elevate text-primary">Create Report <i class="ti ti-arrow-right ms-2"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../Common/footer.php'; ?>
</body>
</html>