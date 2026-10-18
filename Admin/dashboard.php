<?php  
require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/../Common/auth.php";
check_role('Admin');

// Fetch Live System Statistics
$totalStudents = 0;
$totalFaculty = 0;
$totalExams = 0;
$totalDepts = 0;

if ($pdo) {
    try {
        $totalStudents = $pdo->query("SELECT COUNT(*) FROM student")->fetchColumn() ?: 0;
        $totalFaculty = $pdo->query("SELECT COUNT(*) FROM faculty")->fetchColumn() ?: 0;
        $totalDepts = $pdo->query("SELECT COUNT(*) FROM department")->fetchColumn() ?: 0;
        // Check exams table
        $totalExams = $pdo->query("SELECT COUNT(*) FROM exams WHERE Status = 'published'")->fetchColumn() ?: 0;
    } catch (Exception $e) {
        // Fallback silently if table variance exists
    }
} elseif ($con) {
    $resS = mysqli_query($con, "SELECT COUNT(*) AS c FROM student");
    if ($resS && $r = mysqli_fetch_assoc($resS)) $totalStudents = $r['c'];

    $resF = mysqli_query($con, "SELECT COUNT(*) AS c FROM faculty");
    if ($resF && $r = mysqli_fetch_assoc($resF)) $totalFaculty = $r['c'];

    $resD = mysqli_query($con, "SELECT COUNT(*) AS c FROM department");
    if ($resD && $r = mysqli_fetch_assoc($resD)) $totalDepts = $r['c'];

    $resE = mysqli_query($con, "SELECT COUNT(*) AS c FROM exams WHERE Status = 'published'");
    if ($resE && $r = mysqli_fetch_assoc($resE)) $totalExams = $r['c'];
}

include '../Common/header.php';
include '../Common/admin-sidebar.php';
?>

<!-- MAIN CONTENT -->
<main>
    <div class="dashboard-header">
        <div>
            <h4 class="mb-1">Administrator Dashboard</h4>
            <!-- <p class="mb-0 text-white-50 small">Muthoot Institute of Technology and Science Assessment Management</p> -->
        </div>
        <span class="badge bg-white text-danger px-3 py-2 rounded-pill fw-semibold shadow-sm">
            <i class="bi bi-shield-check me-1"></i> System Active
        </span>
    </div>

    <!-- Live KPI Stat Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card hover-elevate h-100">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-value text-dark"><?= number_format($totalStudents) ?></div>
                <div class="stat-label">Enrolled Students</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card hover-elevate h-100">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div class="stat-value text-dark"><?= number_format($totalFaculty) ?></div>
                <div class="stat-label">Faculty Members</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card hover-elevate h-100">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>
                <div class="stat-value text-dark"><?= number_format($totalExams) ?></div>
                <div class="stat-label">Published Assessments</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card hover-elevate h-100">
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-buildings-fill"></i>
                </div>
                <div class="stat-value text-dark"><?= number_format($totalDepts) ?></div>
                <div class="stat-label">Academic Departments</div>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Shortcuts -->
    <div class="card p-4 border-0 shadow-sm rounded-4">
        <h5 class="fw-bold mb-3 text-dark">Quick Administrative Actions</h5>
        <div class="row g-3">
            <div class="col-md-3">
                <a href="manage-students.php" class="btn btn-outline-secondary w-100 py-3 text-start d-flex align-items-center gap-3 rounded-3 hover-elevate">
                    <i class="bi bi-people fs-4 text-primary"></i>
                    <div>
                        <div class="fw-bold text-dark">Student Directory</div>
                        <small class="text-muted">Manage candidate records</small>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="manage-faculty.php" class="btn btn-outline-secondary w-100 py-3 text-start d-flex align-items-center gap-3 rounded-3 hover-elevate">
                    <i class="bi bi-person-gear fs-4 text-success"></i>
                    <div>
                        <div class="fw-bold text-dark">Faculty Directory</div>
                        <small class="text-muted">Onboard and allocate faculty</small>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="view-exams.php" class="btn btn-outline-secondary w-100 py-3 text-start d-flex align-items-center gap-3 rounded-3 hover-elevate">
                    <i class="bi bi-calendar3 fs-4 text-danger"></i>
                    <div>
                        <div class="fw-bold text-dark">Exam Schedule</div>
                        <small class="text-muted">Monitor active assessments</small>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="view-results.php" class="btn btn-outline-secondary w-100 py-3 text-start d-flex align-items-center gap-3 rounded-3 hover-elevate">
                    <i class="bi bi-graph-up fs-4 text-info"></i>
                    <div>
                        <div class="fw-bold text-dark">Performance Analytics</div>
                        <small class="text-muted">View institutional reports</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</main>

<?php include '../Common/footer.php'; ?>