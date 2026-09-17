<?php
session_start();

// CLI Support or Web Auth Check
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    if (!isset($_SESSION['LoginDeptAdmin']) && !isset($_SESSION['LoginAdmin'])) {
        echo "<script>alert('You Are Not Authorized For This link'); window.location.href='../index.php';</script>";
        exit;
    }
    require_once "../Connection/connection.php";
} else {
    // If CLI, load connection dynamically
    $dir = dirname(__FILE__);
    require_once $dir . "/../Connection/connection.php";
}

$success_count = 0;
$status_msg = "";
$status_type = "";

// Decide target departments
$dept_filter = "";
if (!$is_cli) {
    if (isset($_SESSION['LoginDeptAdmin'])) {
        $Dept_ID = $_SESSION['LoginDeptAdmin'];
        $dept_filter = "AND cm.Dept_ID = '$Dept_ID'";
    }
}

// Perform synchronization
if ($is_cli || (isset($_POST['run_sync']))) {
    // Single optimized query to find and insert missing mappings matching Stud_Branch with Prog_ID
    $sync_query = "
        INSERT INTO student_course_mapping (Stud_ID, Course_ID, Fac_ID, Dept_ID, Semester, Acad_Year)
        SELECT DISTINCT s.Stud_ID, cm.Course_ID, cm.Fac_ID, cm.Dept_ID, c.Semester, cm.Acad_Year
        FROM course_mapping cm
        JOIN courses c ON c.Course_ID = cm.Course_ID
        JOIN student s ON s.Stud_Dept = cm.Dept_ID 
          AND s.Stud_Sem = c.Semester 
          AND s.Stud_Branch = c.Prog_ID 
          AND s.Curr_AY = cm.Acad_Year
        LEFT JOIN student_course_mapping scm ON scm.Stud_ID = s.Stud_ID 
          AND scm.Course_ID = cm.Course_ID 
          AND scm.Fac_ID = cm.Fac_ID 
          AND scm.Acad_Year = cm.Acad_Year
        WHERE scm.Stud_ID IS NULL
        $dept_filter
    ";

    $result = mysqli_query($con, $sync_query);
    if ($result) {
        $success_count = mysqli_affected_rows($con);
        $status_msg = "Synchronization completed successfully! Mapped $success_count missing student record(s).";
        $status_type = "success";
    } else {
        $status_msg = "Database query error: " . mysqli_error($con);
        $status_type = "error";
    }

    if ($is_cli) {
        echo "=== Student-Course Mapping Sync ===\n";
        echo "$status_msg\n";
        exit($result ? 0 : 1);
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Admin - Sync Student Course Mappings</title>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Bootstrap CSS (inherited or standard styling) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>

    <?php include '../Common/header.php'; ?>
    <?php include '../Common/deptadmin-sidebar.php'; ?>

    <main>
        <div class="dashboard-header">
            <h4 class="fw-bold text-dark">Sync Student Course Mappings</h4>
        </div>

        <div class="sub-main">
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="card-title text-dark">Synchronize Mappings</h5>
                    <p class="card-text text-muted">
                        This utility checks if students are mapped to their respective courses and faculties based on active course mappings in the current academic year. Any missing links will be created automatically in bulk.
                    </p>

                    <form method="POST">
                        <button type="submit" name="run_sync" class="btn btn-primary btn-lg mt-3">
                            <i class="bi bi-arrow-repeat me-2"></i> Run Synchronization Now
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include '../Common/footer.php'; ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if (!empty($status_msg)): ?>
    <script>
        Swal.fire({
            icon: '<?= $status_type ?>',
            title: '<?= $status_type === 'success' ? 'Sync Completed' : 'Sync Error' ?>',
            text: '<?= addslashes($status_msg) ?>',
            confirmButtonColor: '#3085d6'
        });
    </script>
    <?php endif; ?>

</body>

</html>
