<?php
/**
 * Reset Password Handler
 * Validates cryptographically secure token and updates password using Bcrypt
 */

require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/../Common/auth.php";
require_once __DIR__ . "/../Common/csrf.php";

$errorMessage = "";
$successMessage = "";

// STEP 1: Check token presence
$token = $_GET['token'] ?? '';
if (empty($token)) {
    die("Error: Missing password reset token.");
}

// STEP 2: Validate token in database
$row = null;
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? LIMIT 1");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
} elseif ($con) {
    $stmt = $con->prepare("SELECT * FROM password_resets WHERE token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
}

if (!$row) {
    die("Error: Invalid or previously used reset link.");
}

$userid = $row['user_id'];
$expiry = $row['expires_at'];

// STEP 3: Check expiry timestamp
if (strtotime($expiry) < time()) {
    die("Error: This password reset link has expired (valid for 15 minutes). Please request a new one.");
}

// STEP 4: Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf(false)) {
        $errorMessage = "Security session expired. Please refresh the page.";
    } else {
        $newpass  = (string)($_POST['new_password'] ?? '');
        $confpass = (string)($_POST['confirm_password'] ?? '');

        if (strlen($newpass) < 6) {
            $errorMessage = "Password must be at least 6 characters long.";
        } elseif ($newpass !== $confpass) {
            $errorMessage = "Passwords do not match.";
        } else {
            // Securely hash with Bcrypt
            $hashed = password_hash($newpass, PASSWORD_BCRYPT);

            // Update login record
            if ($pdo) {
                $update = $pdo->prepare("UPDATE login SET Password = ? WHERE User_ID = ?");
                $update->execute([$hashed, $userid]);

                $del = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                $del->execute([$token]);
            } elseif ($con) {
                $update = $con->prepare("UPDATE login SET Password = ? WHERE User_ID = ?");
                $update->bind_param("ss", $hashed, $userid);
                $update->execute();

                $del = $con->prepare("DELETE FROM password_resets WHERE token = ?");
                $del->bind_param("s", $token);
                $del->execute();
            }

            $_SESSION['flash_success'] = "Password successfully reset! Please sign in with your new password.";
            header("Location: ../index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - MITS Assessment Portal</title>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../Css/style.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100" style="background-color: var(--bg-canvas);">
    <div class="login-container shadow-lg border-0 rounded-4" style="max-width: 420px;">
        <div class="text-center mb-4">
            <h3 class="fw-bold mb-1" style="color: var(--brand-primary);">New Password</h3>
            <p class="text-secondary small">Enter a strong, secure password for your account</p>
        </div>

        <form method="POST" id="resetForm">
            <?= csrf_field() ?>

            <div class="form-floating mb-3 position-relative">
                <input type="password" class="form-control" name="new_password" id="new_password" placeholder="New Password" required minlength="6">
                <label for="new_password">New Password</label>
                <button type="button" id="togglePassword" class="password-toggle" aria-label="Toggle password visibility">
                    <i class="bi bi-eye"></i>
                </button>
            </div>

            <div class="form-floating mb-3 position-relative">
                <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required minlength="6">
                <label for="confirm_password">Confirm Password</label>
                <button type="button" id="toggleConfirmPassword" class="password-toggle" aria-label="Toggle password visibility">
                    <i class="bi bi-eye"></i>
                </button>
            </div>

            <div id="matchMessage" class="small mb-3 text-center fw-semibold"></div>

            <button type="submit" id="submitBtn" class="btn btn-primary w-100 py-2 fw-semibold" disabled>
                Update Password
            </button>
            
            <div class="text-center mt-3">
                <a href="../index.php" class="text-decoration-none small text-secondary">Back to Login</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const newPass = document.getElementById("new_password");
        const confirmPass = document.getElementById("confirm_password");
        const message = document.getElementById("matchMessage");
        const submitBtn = document.getElementById("submitBtn");

        function checkMatch() {
            if (newPass.value.length === 0 || confirmPass.value.length === 0) {
                message.textContent = "";
                submitBtn.disabled = true;
                return;
            }

            if (newPass.value === confirmPass.value) {
                message.textContent = "✓ Passwords match";
                message.style.color = "#10B981";
                submitBtn.disabled = false;
            } else {
                message.textContent = "✗ Passwords do not match";
                message.style.color = "#EF4444";
                submitBtn.disabled = true;
            }
        }
        newPass.addEventListener("keyup", checkMatch);
        confirmPass.addEventListener("keyup", checkMatch);

        // Password toggles
        document.getElementById('togglePassword').addEventListener('click', function () {
            const pass = document.getElementById('new_password');
            pass.type = pass.type === "password" ? "text" : "password";
            this.querySelector("i").classList.toggle("bi-eye-slash");
        });
        document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
            const pass = document.getElementById('confirm_password');
            pass.type = pass.type === "password" ? "text" : "password";
            this.querySelector("i").classList.toggle("bi-eye-slash");
        });

        <?php if (!empty($errorMessage)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: <?= json_encode($errorMessage) ?>,
                confirmButtonColor: '#d1202d'
            });
        <?php endif; ?>
    </script>
</body>
</html>
