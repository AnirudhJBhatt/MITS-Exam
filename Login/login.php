<?php
    include("../Connection/connection.php");
    session_start();

    if (isset($_POST['submit'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Use prepared statement
        $stmt = $con->prepare("SELECT * FROM login WHERE User_ID = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            // Role-based session handling
            switch ($row["Role"]) {
                case "Admin":
                    $_SESSION['LoginAdmin'] = $row["User_ID"];
                    header('Location: ../Admin/dashboard.php');
                    break;
                case "Faculty":
                    $_SESSION['LoginFaculty'] = $row["User_ID"];
                    header('Location: ../Faculty/dashboard.php');
                    break;
                case "Student":
                    $_SESSION['LoginStudent'] = $row["User_ID"];
                    header('Location: ../Student/dashboard.php');
                    break;
                case "DeptAdmin":
                    $_SESSION['LoginDeptAdmin'] = $row["User_ID"];
                    header('Location: ../Dept_Admin/dashboard.php');
                    break;
            }
            exit();
        } else {
            echo "<script>alert('Invalid Credentials');</script>";
        }

        $stmt->close();
    }
?>


<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MITS Internal Assessment Portal - Login</title>
    <link rel="stylesheet" href="../Css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@400;500;700&display=swap" rel="stylesheet">
<body>
    <div class="wrapper">

        <!-- Left Panel -->
        <div class="left-panel">
            <div class="logo-box">
                <img src="../Images/MITS Logo.png" alt="MITS Logo">
            </div>
            <h3>MITS Assessment Portal</h3>
            <p>Empowering smart internal assessments with accuracy and transparency.</p>
        </div>

        <!-- Right Panel -->
        <div class="right-panel">
            <div class="login-container">
                <h3>Login</h3>

                <form action="" method="POST">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="username" placeholder="Username" required>
                        <label for="username">Username</label>
                    </div>

                    <div class="form-floating mb-3 position-relative">
                        <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                        <label for="password">Password</label>
                        <button type="button" id="togglePassword" class="password-toggle" aria-label="Show password">
                            <i class="fa fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>

                    <button type="submit" name="submit" class="btn btn-success btn-login">Login</button>

                    <div class="forgot-password">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot password?</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="forgot-password.php" method="POST">
                    <div class="modal-body">
                        <p class="text-muted mb-3">Enter your User Name</p>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" name="user_id" id="user_id" placeholder="User ID" required>
                            <label for="user_id">User Name</label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Send Reset Link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const pass = document.getElementById('password');
            const type = pass.type === "password" ? "text" : "password";
            pass.type = type;

            const icon = this.querySelector("i");
            icon.classList.toggle("fa-eye");
            icon.classList.toggle("fa-eye-slash");
        });
    </script>
</body>


</html>