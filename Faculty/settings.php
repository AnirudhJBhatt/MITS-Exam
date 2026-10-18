<?php
/**
 * MITS Exam Portal - Faculty Account Settings
 * Parameterized queries, CSRF validation, and Bcrypt password updates
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

check_role('Faculty');

$fac_id = $_SESSION['LoginFaculty'] ?? $_SESSION['user_id'] ?? '';

$flashMessage = null;
$flashType = 'info';

// Fetch Faculty Profile
$stmt = $pdo->prepare("
    SELECT f.*, d.Dept_Name, l.User_ID, l.Last_Login
    FROM faculty f
    LEFT JOIN department d ON f.Fac_Dept = d.Dept_ID
    LEFT JOIN login l ON f.Fac_ID = l.ID
    WHERE f.Fac_ID = ? LIMIT 1
");
$stmt->execute([$fac_id]);
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current_pass = $_POST['current_pass'] ?? '';
    $new_pass = $_POST['new_pass'] ?? '';
    $conf_pass = $_POST['conf_pass'] ?? '';

    if (empty($current_pass) || empty($new_pass) || empty($conf_pass)) {
        $flashMessage = "All password fields are required.";
        $flashType = "warning";
    } elseif ($new_pass !== $conf_pass) {
        $flashMessage = "New password and confirmation password do not match.";
        $flashType = "danger";
    } elseif (strlen($new_pass) < 6) {
        $flashMessage = "New password must be at least 6 characters in length.";
        $flashType = "warning";
    } else {
        // Fetch current password hash from login
        $stmtLogin = $pdo->prepare("SELECT Password FROM login WHERE ID = ? AND Role = 'Faculty' LIMIT 1");
        $stmtLogin->execute([$fac_id]);
        $loginRow = $stmtLogin->fetch(PDO::FETCH_ASSOC);

        if (!$loginRow) {
            $flashMessage = "Login record not found for this faculty ID.";
            $flashType = "danger";
        } else {
            $dbHash = $loginRow['Password'];
            $passValid = false;

            if (password_verify($current_pass, $dbHash)) {
                $passValid = true;
            } elseif ($current_pass === $dbHash) {
                // Legacy plaintext match
                $passValid = true;
            }

            if (!$passValid) {
                $flashMessage = "Current password entered is incorrect.";
                $flashType = "danger";
            } else {
                $newHash = password_hash($new_pass, PASSWORD_BCRYPT);
                $updateStmt = $pdo->prepare("UPDATE login SET Password = ? WHERE ID = ? AND Role = 'Faculty'");
                $updateStmt->execute([$newHash, $fac_id]);

                $flashMessage = "Password updated successfully with enterprise Bcrypt encryption!";
                $flashType = "success";
            }
        }
    }
}

include '../Common/header.php';
include '../Common/faculty-sidebar.php';
?>

<main>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="bi bi-gear-fill text-danger me-2"></i>Account Settings & Security
            </h3>
        </div>
    </div>

    <?php if ($flashMessage): ?>
        <div class="alert alert-<?= $flashType ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi <?= $flashType === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
            <?= e($flashMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Faculty Profile Card -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-person-badge me-2"></i>Faculty Profile Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center py-3">
                        <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                            <i class="bi bi-person-fill fs-1"></i>
                        </div>
                        <h5 class="fw-bold mb-1 text-dark"><?= e($faculty['Fac_Name'] ?? 'Faculty Member') ?></h5>
                        <p class="text-muted small mb-0 font-monospace">ID: <?= e($faculty['Fac_ID'] ?? $fac_id) ?></p>
                    </div>

                    <ul class="list-group list-group-flush border-top small">
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                            <span class="text-muted">Email</span>
                            <span class="fw-semibold text-dark"><?= e($faculty['Fac_Email'] ?? '-') ?></span>
                        </li>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                            <span class="text-muted">Academic Department</span>
                            <span class="fw-semibold text-dark"><?= e($faculty['Dept_Name'] ?: ($faculty['Fac_Dept'] ?? '-')) ?></span>
                        </li>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                            <span class="text-muted">Designation</span>
                            <span class="fw-semibold text-dark"><?= e($faculty['Fac_Desg'] ?: 'Assistant Professor') ?></span>
                        </li>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                            <span class="text-muted">Contact Phone</span>
                            <span class="fw-semibold text-dark"><?= e($faculty['Fac_Mob'] ?: 'Not Provided') ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Change Password Form Card -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-shield-lock me-2"></i>Change Access Password
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
                            <input type="password" name="current_pass" class="form-control" required placeholder="Enter your current password">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                                <input type="password" name="new_pass" class="form-control" required minlength="6" placeholder="Minimum 6 characters">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" name="conf_pass" class="form-control" required minlength="6" placeholder="Repeat new password">
                            </div>
                        </div>

                        <div class="alert alert-light border small text-muted mb-4">
                            <i class="bi bi-info-circle me-1 text-primary"></i>
                            Your new password will be hashed with enterprise <strong>Bcrypt algorithm</strong> and salted securely before persisting.
                        </div>

                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-key me-1"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../Common/footer.php'; ?>