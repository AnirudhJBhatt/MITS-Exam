<?php
/**
 * MITS Assessment Portal - Student Account Settings
 * Parameterized queries, CSRF validation, Bcrypt password updates, and session security
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

check_role('Student');

$stud_id = $_SESSION['LoginStudent'] ?? $_SESSION['user_id'] ?? '';

$flashMessage = null;
$flashType = 'info';

// Fetch Student Profile
$stmt = $pdo->prepare("
    SELECT s.*, d.Dept_Name, p.Prog_Name, l.Last_Login
    FROM student s
    LEFT JOIN department d ON s.Stud_Dept = d.Dept_ID
    LEFT JOIN programmes p ON s.Stud_Branch = p.Prog_ID
    LEFT JOIN login l ON s.Stud_ID = l.ID
    WHERE s.Stud_ID = ? LIMIT 1
");
$stmt->execute([$stud_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

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
        $stmtLogin = $pdo->prepare("SELECT Password FROM login WHERE ID = ? AND Role = 'Student' LIMIT 1");
        $stmtLogin->execute([$stud_id]);
        $loginRow = $stmtLogin->fetch(PDO::FETCH_ASSOC);

        if (!$loginRow) {
            $flashMessage = "Login record not found for this student account.";
            $flashType = "danger";
        } else {
            $dbHash = $loginRow['Password'];
            $passValid = false;

            if (password_verify($current_pass, $dbHash)) {
                $passValid = true;
            } elseif ($current_pass === $dbHash) {
                // Legacy plaintext fallback
                $passValid = true;
            }

            if (!$passValid) {
                $flashMessage = "Current password is incorrect. Please try again.";
                $flashType = "danger";
            } else {
                $newHash = password_hash($new_pass, PASSWORD_BCRYPT);
                $updateStmt = $pdo->prepare("UPDATE login SET Password = ? WHERE ID = ? AND Role = 'Student'");
                $updateStmt->execute([$newHash, $stud_id]);

                $flashMessage = "Your password has been successfully updated and secured with enterprise Bcrypt encryption.";
                $flashType = "success";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings — MITS Portal</title>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Common/header.php'; ?>
    <!-- SIDEBAR -->
    <?php include '../Common/student-sidebar.php'; ?>

    <main>
        <!-- Title -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark">
                    <i class="bi bi-gear-fill text-danger me-2"></i>Account Settings & Security
                </h3>
            </div>
            <!-- <div>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            </div> -->
        </div>

        <?php if ($flashMessage): ?>
            <div class="alert alert-<?= $flashType ?> alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi <?= ($flashType === 'success') ? 'bi-check-circle-fill text-success fs-5' : 'bi-exclamation-triangle-fill fs-5' ?>"></i>
                <div><?= e($flashMessage) ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 justify-content-center">
            <!-- Left: Account Details Summary -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                    <div class="card-header bg-white border-bottom p-3">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-shield-check text-danger me-2"></i>Account Information
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="rounded-circle bg-danger bg-opacity-10 text-danger border border-2 border-danger d-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm" style="width: 80px; height: 80px; font-size: 2rem; font-weight: 700;">
                                <?= strtoupper(substr($student['Stud_Name'] ?? 'ST', 0, 2)) ?>
                            </div>
                            <h5 class="fw-bold mb-1 text-dark"><?= e($student['Stud_Name'] ?? '') ?></h5>
                            <span class="text-muted small"><?= e($student['Stud_Email'] ?? '') ?></span>
                        </div>

                        <ul class="list-group list-group-flush rounded-3 border">
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <span class="text-muted small">Student Roll No</span>
                                <span class="fw-bold font-monospace text-dark"><?= e($student['Stud_ID'] ?? $stud_id) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <span class="text-muted small">Department</span>
                                <span class="fw-semibold text-dark"><?= e($student['Dept_Name'] ?? 'N/A') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <span class="text-muted small">Programme</span>
                                <span class="fw-semibold text-dark"><?= e(($student['Stud_Prog'] ?? '') . ' ' . ($student['Prog_Name'] ?? '')) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <span class="text-muted small">Semester</span>
                                <span class="badge bg-danger">S<?= e($student['Stud_Sem'] ?? '1') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <span class="text-muted small">Batch / Year</span>
                                <span class="fw-semibold text-muted"><?= e($student['Stud_Year'] ?? 'N/A') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <span class="text-muted small">Last Login</span>
                                <span class="small text-muted">
                                    <?= !empty($student['Last_Login']) ? date('d M Y, h:i A', strtotime($student['Last_Login'])) : 'Current Session' ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right: Change Password Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-header bg-white border-bottom p-3">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-key-fill text-danger me-2"></i>Change Portal Password
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="">
                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label for="current_pass" class="form-label small fw-bold text-muted">
                                    Current Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" name="current_pass" id="current_pass" class="form-control border-start-0 ps-0" placeholder="Enter your existing password" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="new_pass" class="form-label small fw-bold text-muted">
                                    New Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shield-lock-fill"></i></span>
                                    <input type="password" name="new_pass" id="new_pass" class="form-control border-start-0 ps-0" placeholder="Minimum 6 characters" minlength="6" required>
                                </div>
                                <div class="form-text small">Use a strong combination of letters, numbers, and symbols.</div>
                            </div>

                            <div class="mb-3">
                                <label for="conf_pass" class="form-label small fw-bold text-muted">
                                    Confirm New Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-check2-circle"></i></span>
                                    <input type="password" name="conf_pass" id="conf_pass" class="form-control border-start-0 ps-0" placeholder="Re-enter your new password" required>
                                </div>
                            </div>

                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" role="switch" id="togglePasswords" onclick="togglePasswordVisibility()">
                                <label class="form-check-label small text-muted user-select-none" for="togglePasswords">Show Passwords</label>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="reset" class="btn btn-light rounded-pill px-4">Reset</button>
                                <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold shadow-sm">
                                    <i class="bi bi-shield-check me-1"></i> Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../Common/footer.php'; ?>

    <script>
        function togglePasswordVisibility() {
            var check = document.getElementById("togglePasswords");
            var cp = document.getElementById("current_pass");
            var np = document.getElementById("new_pass");
            var cf = document.getElementById("conf_pass");
            var type = check.checked ? "text" : "password";
            if (cp) cp.type = type;
            if (np) np.type = type;
            if (cf) cf.type = type;
        }
    </script>
</body>

</html>