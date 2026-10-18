<?php
/**
 * Student-Course Enrollment Synchronization Utility
 * Links students to enrolled subjects based on semester and active course mappings
 */

require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/../Common/auth.php";
require_once __DIR__ . "/../Common/csrf.php";

$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    check_role(['DeptAdmin', 'Admin']);
    $Dept_ID = $_SESSION['LoginDeptAdmin'] ?? null;
} else {
    $Dept_ID = null;
}

$status_msg = "";
$status_type = "info";
$success_count = 0;

if ($is_cli || (isset($_POST['run_sync']))) {
    if (!$is_cli && !verify_csrf(false)) {
        $status_msg = "Security session expired. Please refresh the page.";
        $status_type = "error";
    } else {
        $dept_filter = "";
        $params = [];

        if (!empty($Dept_ID)) {
            $dept_filter = "AND cm.Dept_ID = ?";
            $params[] = $Dept_ID;
        }

        $sync_query = "
            INSERT INTO student_course_mapping (Stud_ID, Course_ID, Fac_ID, Dept_ID, Semester, Acad_Year)
            SELECT DISTINCT s.Stud_ID, cm.Course_ID, cm.Fac_ID, cm.Dept_ID, c.Semester, cm.Acad_Year
            FROM course_mapping cm
            JOIN courses c ON c.Course_ID = cm.Course_ID
            JOIN student s ON s.Stud_Dept = cm.Dept_ID 
              AND s.Stud_Sem = c.Semester 
              AND s.Stud_Branch = c.Prog_ID 
              AND s.Curr_AY = cm.Acad_Year
            LEFT JOIN student_course_mapping scm 
              ON scm.Stud_ID = s.Stud_ID 
              AND scm.Course_ID = cm.Course_ID 
              AND scm.Fac_ID = cm.Fac_ID 
              AND scm.Acad_Year = cm.Acad_Year
            WHERE scm.Stud_ID IS NULL
            $dept_filter
        ";

        if ($pdo) {
            try {
                $stmt = $pdo->prepare($sync_query);
                $stmt->execute($params);
                $success_count = $stmt->rowCount();
                $status_msg = "Synchronization complete! {$success_count} missing student course enrollment(s) created.";
                $status_type = "success";
            } catch (Exception $e) {
                $status_msg = "Database synchronization error: " . $e->getMessage();
                $status_type = "error";
            }
        } elseif ($con) {
            $dept_filter_esc = !empty($Dept_ID) ? "AND cm.Dept_ID = '" . mysqli_real_escape_string($con, $Dept_ID) . "'" : "";
            $sqlEsc = str_replace("$dept_filter", $dept_filter_esc, $sync_query);
            $res = mysqli_query($con, $sqlEsc);
            if ($res) {
                $success_count = mysqli_affected_rows($con);
                $status_msg = "Synchronization complete! {$success_count} missing student course enrollment(s) created.";
                $status_type = "success";
            } else {
                $status_msg = "Database error: " . mysqli_error($con);
                $status_type = "error";
            }
        }

        if ($is_cli) {
            echo "=== MITS Course-Student Sync ===\n{$status_msg}\n";
            exit(0);
        }
    }
}

include '../Common/header.php';
include '../Common/deptadmin-sidebar.php';
?>

<!-- MAIN CONTENT -->
<main>
    <div class="dashboard-header">
        <div>
            <h4 class="mb-1">Student Course Enrollment Synchronization</h4>
            <p class="mb-0 text-white-50 small">Automatically map all registered students to active courses and teachers</p>
        </div>
        <a href="map-courses.php" class="btn btn-light rounded-pill px-4 fw-semibold text-danger shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Course Mapping
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary mx-auto mb-3" style="width: 64px; height: 64px; font-size: 2rem;">
                    <i class="bi bi-arrow-repeat"></i>
                </div>

                <h4 class="fw-bold text-dark mb-2">Automated Enrollment Sync</h4>
                <p class="text-secondary max-w-lg mx-auto mb-4" style="max-width: 500px;">
                    This process inspects active faculty-course allocations for the current academic session and enrolls any eligible students into their semester curriculum who have not yet been mapped.
                </p>

                <form method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" name="run_sync" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow">
                        <i class="bi bi-play-circle-fill me-2"></i> Run Synchronization Now
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include '../Common/footer.php'; ?>

<?php if (!empty($status_msg)): ?>
<script>
    Swal.fire({
        icon: '<?= $status_type ?>',
        title: '<?= $status_type === 'success' ? 'Sync Completed' : 'Sync Notice' ?>',
        text: <?= json_encode($status_msg) ?>,
        confirmButtonColor: '#d1202d'
    });
</script>
<?php endif; ?>
