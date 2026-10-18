<?php  
/**
 * Department Administrator - Account Settings & Password Update
 */

require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/../Common/auth.php";
require_once __DIR__ . "/../Common/csrf.php";

check_role('DeptAdmin');

$Dept_ID = $_SESSION['LoginDeptAdmin'];
$message = "";
$msgType = "info";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (!verify_csrf(false)) {
        $message = "Security session expired. Please try again.";
        $msgType = "error";
    } else {
        $new_pass  = (string)($_POST['new_pass'] ?? '');
        $conf_pass = (string)($_POST['conf_pass'] ?? '');

        if (strlen($new_pass) < 6) {
            $message = "Password must be at least 6 characters long.";
            $msgType = "warning";
        } elseif ($new_pass !== $conf_pass) {
            $message = "New password and confirm password do not match.";
            $msgType = "warning";
        } else {
            $hashed = password_hash($new_pass, PASSWORD_BCRYPT);

            if ($pdo) {
                $stmt = $pdo->prepare("UPDATE login SET Password = ? WHERE ID = ?");
                $stmt->execute([$hashed, $Dept_ID]);
                $message = "Password updated successfully!";
                $msgType = "success";
            } elseif ($con) {
                $stmt = $con->prepare("UPDATE login SET Password = ? WHERE ID = ?");
                $stmt->bind_param("ss", $hashed, $Dept_ID);
                if ($stmt->execute()) {
                    $message = "Password updated successfully!";
                    $msgType = "success";
                } else {
                    $message = "Database error: " . $stmt->error;
                    $msgType = "error";
                }
            }
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
            <h4 class="mb-1">Department Settings</h4>
            <p class="mb-0 text-white-50 small">Manage your account security and credentials for <?= htmlspecialchars($Dept_ID) ?></p>
        </div>
        <span class="badge bg-white text-danger px-3 py-2 rounded-pill fw-semibold shadow-sm">
            <i class="bi bi-gear-fill me-1"></i> Security
        </span>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger mb-0" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="bi bi-key-fill"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Change Password</h5>
                        <small class="text-secondary">Update your department portal access password</small>
                    </div>
                </div>

                <form action="settings.php" method="POST" id="settingsForm">
                    <?= csrf_field() ?>

                    <div class="form-floating mb-3">
                        <input type="password" name="new_pass" id="new_pass" class="form-control" required placeholder="New Password" minlength="6">
                        <label for="new_pass"><i class="bi bi-lock me-2 text-muted"></i>New Password</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" name="conf_pass" id="conf_pass" class="form-control" required placeholder="Confirm Password" minlength="6">
                        <label for="conf_pass"><i class="bi bi-lock-fill me-2 text-muted"></i>Confirm Password</label>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" name="submit" class="btn btn-primary btn-lg rounded-pill fw-semibold shadow-sm">
                            <i class="bi bi-shield-lock me-2"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include '../Common/footer.php'; ?>

<?php if (!empty($message)): ?>
<script>
    Swal.fire({
        icon: '<?= $msgType ?>',
        title: '<?= $msgType === 'success' ? 'Updated' : 'Notice' ?>',
        text: <?= json_encode($message) ?>,
        confirmButtonColor: '#d1202d'
    });
</script>
<?php endif; ?>