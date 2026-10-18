<?php
/**
 * MITS Internal Assessment Portal - Central Login Interface
 * Featuring Bcrypt Password Hashing, CSRF Protection, and Transparent Legacy Migration
 */

require_once __DIR__ . "/Connection/connection.php";
require_once __DIR__ . "/Common/auth.php";
require_once __DIR__ . "/Common/csrf.php";

// Redirect if already logged in
if (is_logged_in()) {
    $role = get_logged_in_role();
    switch ($role) {
        case "Admin":
            header('Location: ./Admin/dashboard.php');
            exit;
        case "Faculty":
            header('Location: ./Faculty/dashboard.php');
            exit;
        case "Student":
            header('Location: ./Student/dashboard.php');
            exit;
        case "DeptAdmin":
            header('Location: ./DeptAdmin/dashboard.php');
            exit;
    }
}

$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF
    if (!verify_csrf(false)) {
        $errorMessage = "Security token mismatch or session expired. Please refresh and try again.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $errorMessage = "Please enter both username and password.";
        } else {
            $row = null;

            // 2. Fetch User Record via Prepared Statement (supports Username, Roll No, or Email)
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT * FROM login WHERE (User_ID = ? OR ID = ?) LIMIT 1");
                $stmt->execute([$username, $username]);
                $row = $stmt->fetch();
            } elseif ($con) {
                $stmt = $con->prepare("SELECT * FROM login WHERE (User_ID = ? OR ID = ?) LIMIT 1");
                $stmt->bind_param("ss", $username, $username);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
            }

            if ($row) {
                $dbPass = $row['Password'];
                $authenticated = false;

                // 3. Password Verification (Bcrypt or Transparent Migration from Plaintext)
                if (password_verify($password, $dbPass)) {
                    $authenticated = true;
                } elseif ($password === $dbPass) {
                    // Legacy Plaintext Match -> Transparently Migrate to Bcrypt
                    $authenticated = true;
                    $newHash = password_hash($password, PASSWORD_BCRYPT);

                    if ($pdo) {
                        $upStmt = $pdo->prepare("UPDATE login SET Password = ? WHERE ID = ?");
                        $upStmt->execute([$newHash, $row['ID']]);
                    } elseif ($con) {
                        $upStmt = $con->prepare("UPDATE login SET Password = ? WHERE ID = ?");
                        $upStmt->bind_param("ss", $newHash, $row['ID']);
                        $upStmt->execute();
                    }
                }

                if ($authenticated) {
                    // Check if account is active
                    if (isset($row['Status']) && strtolower($row['Status']) === 'deactivate') {
                        $errorMessage = "Your account has been deactivated. Please contact the administrator.";
                    } else {
                        // 4. Secure Session Regeneration (Prevents Session Fixation)
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $row['ID'];
                        $_SESSION['user_role'] = $row['Role'];
                        $_SESSION['Role'] = $row['Role'];

                        // Maintain legacy session keys for full backward compatibility
                        switch ($row["Role"]) {
                            case "Admin":
                                $_SESSION['LoginAdmin'] = $row["ID"];
                                header('Location: ./Admin/dashboard.php');
                                break;
                            case "Faculty":
                                $_SESSION['LoginFaculty'] = $row["ID"];
                                header('Location: ./Faculty/dashboard.php');
                                break;
                            case "Student":
                                $_SESSION['LoginStudent'] = $row["ID"];
                                header('Location: ./Student/dashboard.php');
                                break;
                            case "DeptAdmin":
                                $_SESSION['LoginDeptAdmin'] = $row["ID"];
                                header('Location: ./DeptAdmin/dashboard.php');
                                break;
                            default:
                                header('Location: ./index.php');
                                break;
                        }
                        exit;
                    }
                } else {
                    $errorMessage = "Invalid username or password. Please try again.";
                }
            } else {
                $errorMessage = "Invalid username or password. Please try again.";
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
    <meta name="theme-color" content="#d1202d">
    <title>Login - MITS Internal Assessment Portal</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Custom Unified Stylesheet -->
    <link rel="stylesheet" href="Css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Left Branding Panel -->
        <div class="left-panel">
            <div class="logo-box">
                <img src="Images/MITS Logo.png" alt="MITS Logo">
            </div>
            <h3>MITS Internal Assessment Portal</h3>
            <p class="text-justify">Empowering Outcome-Based Education (OBE) with precision, security, and transparent evaluation.</p>
        </div>

        <!-- Right Login Panel -->
        <div class="right-panel">
            <div class="login-container">
                <h3 class="mb-4 text-center">Login</h3>

                <form action="index.php" method="POST" id="loginForm">
                    <?= csrf_field() ?>

                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username, Roll No, or Email" required autofocus>
                        <label for="username"><i class="bi bi-person-fill me-2 text-muted"></i>Username or ID</label>
                    </div>

                    <div class="form-floating mb-3 position-relative">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password"><i class="bi bi-lock-fill me-2 text-muted"></i>Password</label>
                        <button type="button" id="togglePassword" class="password-toggle" aria-label="Toggle password visibility">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>

                    <button type="submit" id="btnSubmit" class="btn btn-success btn-login">
                        <span id="btnText">Sign In</span>
                        <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>

                    <div class="forgot-password text-center mt-3">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot your password?</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Forgot Password Modal -->
        <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="forgotPasswordLabel">Reset Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="./Login/forgot-password.php" method="POST">
                        <?= csrf_field() ?>
                        <div class="modal-body py-4">
                            <p class="text-secondary small mb-3">Enter your registered username/email address. We will send you a secure link to reset your password.</p>
                            <div class="form-floating">
                                <input type="text" class="form-control" name="user_id" id="reset_user_id" placeholder="Username or Email" required>
                                <label for="reset_user_id">Username or Email</label>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0">
                            <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">Send Reset Link</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Password Visibility Toggle
        document.getElementById('togglePassword').addEventListener('click', function () {
            const pass = document.getElementById('password');
            const type = pass.type === "password" ? "text" : "password";
            pass.type = type;

            const icon = this.querySelector("i");
            icon.classList.toggle("bi-eye");
            icon.classList.toggle("bi-eye-slash");
        });

        // Form Submit Loading Feedback (Without disabling the button synchronously so POST data sends)
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('btnSubmit');
            const text = document.getElementById('btnText');
            const spinner = document.getElementById('btnSpinner');

            setTimeout(() => {
                btn.style.pointerEvents = 'none';
                btn.style.opacity = '0.8';
                text.textContent = 'Authenticating...';
                spinner.classList.remove('d-none');
            }, 10);
        });

        // Error Alert via SweetAlert2
        <?php if (!empty($errorMessage)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Authentication Failed',
                text: <?= json_encode($errorMessage) ?>,
                confirmButtonColor: '#d1202d'
            });
        <?php endif; ?>
    </script>
</body>
</html>