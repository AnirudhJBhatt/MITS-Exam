<?php  
/**
 * Department Administrator Dashboard
 * Real-time Departmental Metrics, KPI Tracking, and Quick Administrative Shortcuts
 */

require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/../Common/auth.php";
check_role('DeptAdmin');

$Dept_ID = $_SESSION['LoginDeptAdmin'];

// Fetch Department Profile
$deptName = $Dept_ID;
$deptCode = "";
if ($pdo) {
    $stmt = $pdo->prepare("SELECT Dept_Name, Dept_Code FROM department WHERE Dept_ID = ? LIMIT 1");
    $stmt->execute([$Dept_ID]);
    if ($r = $stmt->fetch()) {
        $deptName = $r['Dept_Name'];
        $deptCode = $r['Dept_Code'] ?? '';
    }
} elseif ($con) {
    $stmt = $con->prepare("SELECT Dept_Name, Dept_Code FROM department WHERE Dept_ID = ? LIMIT 1");
    $stmt->bind_param("s", $Dept_ID);
    $stmt->execute();
    if ($r = $stmt->get_result()->fetch_assoc()) {
        $deptName = $r['Dept_Name'];
        $deptCode = $r['Dept_Code'] ?? '';
    }
}

// Fetch Live Department Statistics
$totalStudents = 0;
$totalFaculty  = 0;
$totalCourses  = 0;
$totalExams    = 0;
$totalProgrammes = 0;

if ($pdo) {
    try {
        $stS = $pdo->prepare("SELECT COUNT(*) FROM student WHERE Stud_Dept = ?");
        $stS->execute([$Dept_ID]);
        $totalStudents = (int)$stS->fetchColumn();

        $stF = $pdo->prepare("SELECT COUNT(*) FROM faculty WHERE Fac_Dept = ?");
        $stF->execute([$Dept_ID]);
        $totalFaculty = (int)$stF->fetchColumn();

        $stC = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE Dept_ID = ?");
        $stC->execute([$Dept_ID]);
        $totalCourses = (int)$stC->fetchColumn();

        $stP = $pdo->prepare("SELECT COUNT(*) FROM programmes WHERE Dept_ID = ?");
        $stP->execute([$Dept_ID]);
        $totalProgrammes = (int)$stP->fetchColumn();

        // Total published exams for this department's courses
        $stE = $pdo->prepare("SELECT COUNT(*) FROM exams e JOIN courses c ON e.Course_ID = c.Course_ID WHERE c.Dept_ID = ? AND e.Status = 'published'");
        $stE->execute([$Dept_ID]);
        $totalExams = (int)$stE->fetchColumn();
    } catch (Exception $e) {
        // Fallback silently
    }
} elseif ($con) {
    $q = mysqli_query($con, "SELECT COUNT(*) as c FROM student WHERE Stud_Dept = '$Dept_ID'");
    if ($q && $r = mysqli_fetch_assoc($q)) $totalStudents = (int)$r['c'];

    $q = mysqli_query($con, "SELECT COUNT(*) as c FROM faculty WHERE Fac_Dept = '$Dept_ID'");
    if ($q && $r = mysqli_fetch_assoc($q)) $totalFaculty = (int)$r['c'];

    $q = mysqli_query($con, "SELECT COUNT(*) as c FROM courses WHERE Dept_ID = '$Dept_ID'");
    if ($q && $r = mysqli_fetch_assoc($q)) $totalCourses = (int)$r['c'];

    $q = mysqli_query($con, "SELECT COUNT(*) as c FROM programmes WHERE Dept_ID = '$Dept_ID'");
    if ($q && $r = mysqli_fetch_assoc($q)) $totalProgrammes = (int)$r['c'];

    $q = mysqli_query($con, "SELECT COUNT(*) as c FROM exams e JOIN courses c ON e.Course_ID = c.Course_ID WHERE c.Dept_ID = '$Dept_ID' AND e.Status = 'published'");
    if ($q && $r = mysqli_fetch_assoc($q)) $totalExams = (int)$r['c'];
}

include '../Common/header.php';
include '../Common/deptadmin-sidebar.php';
?>

<!-- MAIN CONTENT -->
<main>
    <div class="dashboard-header">
        <div>
            <h4 class="mb-1"><?= htmlspecialchars($deptName, ENT_QUOTES, 'UTF-8') ?></h4>
            <p class="mb-0 text-white-50 small">Department Academic Administration & Assessment Overview</p>
        </div>
        <span class="badge bg-white text-danger px-3 py-2 rounded-pill fw-semibold shadow-sm">
            <i class="bi bi-building me-1"></i> Dept: <?= htmlspecialchars($Dept_ID, ENT_QUOTES, 'UTF-8') ?>
        </span>
    </div>

    <!-- Live Departmental KPI Stat Cards -->

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 me-3">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Total Students</span>
                        <h4 class="fw-bold mb-0"><?= number_format($totalStudents) ?></h4>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 me-3">
                        <i class="bi bi-person-video3 fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Total Faculty</span>
                        <h4 class="fw-bold mb-0"><?= number_format($totalFaculty) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 me-3">
                        <i class="bi bi-journal-bookmark-fill fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Active Courses</span>
                        <h4 class="fw-bold mb-0"><?= number_format($totalCourses) ?></h4>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 me-3">
                        <i class="bi bi-file-earmark-check-fill fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Published Assessments</span>
                        <h4 class="fw-bold mb-0"><?= number_format($totalExams) ?></h4>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Quick Departmental Actions -->
    <div class="card p-4 border-0 shadow-sm rounded-4 mb-4">
        <h5 class="fw-bold mb-3 text-dark">Quick Administrative Actions</h5>
        <div class="row g-3">
            <div class="col-md-3">
                <a href="manage-student.php" class="btn btn-outline-secondary w-100 py-3 text-start d-flex align-items-center gap-3 rounded-3 hover-elevate">
                    <i class="bi bi-cloud-arrow-up fs-4 text-primary"></i>
                    <div>
                        <div class="fw-bold text-dark">Student Batch Upload</div>
                        <small class="text-muted">Import Students via CSV</small>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="map-courses.php" class="btn btn-outline-secondary w-100 py-3 text-start d-flex align-items-center gap-3 rounded-3 hover-elevate">
                    <i class="bi bi-diagram-3 fs-4 text-success"></i>
                    <div>
                        <div class="fw-bold text-dark">Course-Faculty Mapping</div>
                        <small class="text-muted">Assign teachers to subjects</small>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="sync-student-courses.php" class="btn btn-outline-secondary w-100 py-3 text-start d-flex align-items-center gap-3 rounded-3 hover-elevate">
                    <i class="bi bi-arrow-repeat fs-4 text-warning"></i>
                    <div>
                        <div class="fw-bold text-dark">Sync Enrollments</div>
                        <small class="text-muted">Batch map student courses</small>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="view-results.php" class="btn btn-outline-secondary w-100 py-3 text-start d-flex align-items-center gap-3 rounded-3 hover-elevate">
                    <i class="bi bi-bar-chart-line fs-4 text-danger"></i>
                    <div>
                        <div class="fw-bold text-dark">Department Results</div>
                        <small class="text-muted">View performance analytics</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</main>

<?php include '../Common/footer.php'; ?>