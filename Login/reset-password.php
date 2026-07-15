<?php
    include("../Connection/connection.php");
    session_start();

    $message="";
    
    // STEP 1: Check if token exists
    if (!isset($_GET['token'])) {
        die("Invalid token.");
    }

    $token = $_GET['token'];

    // STEP 2: Validate token
    $stmt = $con->prepare("SELECT * FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        die("Invalid or expired reset link.");
    }

    $row = $result->fetch_assoc();
    $userid = $row['user_id'];
    $expiry = $row['expires_at'];

    // STEP 3: Check expiry
    if (strtotime($expiry) < time()) {
        die("This password reset link has expired. Request a new one.");
    }

    // STEP 4: Process form submit
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $newpass = $_POST['new_password'];
        $confpass = $_POST['confirm_password'];

        if ($newpass != $confpass) {
            echo "<script>alert('Passwords do not match');</script>";
        } else {
            // Hash password
            // $hashed = password_hash($newpass, PASSWORD_DEFAULT);

            // Update login table
            $update = $con->prepare("UPDATE login SET Password = ? WHERE User_ID = ?");
            $update->bind_param("ss", $newpass, $userid);
            $update->execute();

            // Delete token
            $del = $con->prepare("DELETE FROM password_resets WHERE token = ?");
            $del->bind_param("s", $token);
            $del->execute();

            echo "<script>alert('Password successfully reset. Please login.'); window.location.href='../index.php';</script>";
            exit;
        }
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - MITS Internal Assessment Portal</title>
    <link rel="stylesheet" href="../Css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100" style="background-color: #f9eaea;">
    <div class="login-container">
        <h4 class="mb-3 text-center text-danger">Reset Password</h4>

        <form method="POST" id="resetForm">
            <div class="form-floating mb-3 position-relative">
                <input type="password" class="form-control" name="new_password" id="new_password" placeholder="New Password" required>
                <label for="new_password">New Password</label>
                <button type="button" id="togglePassword" class="password-toggle" aria-label="Show password"><i class="fa fa-eye" aria-hidden="true"></i></button>
            </div>
            <div class="form-floating mb-3 position-relative">
                <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                <label for="confirm_password">Confirm Password</label>
                <button type="button" id="toggleConfirmPassword" class="password-toggle" aria-label="Show password"><i class="fa fa-eye" aria-hidden="true"></i></button>
            </div>
            <!-- Live validation message -->
            <p id="matchMessage" class="text-center fw-bold"></p>
            <button type="submit" id="submitBtn" class="btn btn-success w-100" disabled>
                Update Password
            </button>
        </form>
    </div>

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
                message.textContent = "";
                // message.style.color = "green";
                submitBtn.disabled = false;
            } else {
                message.textContent = "Passwords do not match";
                message.style.color = "red";
                submitBtn.disabled = true;
            }
        }
        newPass.addEventListener("keyup", checkMatch);
        confirmPass.addEventListener("keyup", checkMatch);

        document.getElementById('togglePassword').addEventListener('click', function () {
            const pass = document.getElementById('new_password');
            const type = pass.type === "password" ? "text" : "password";
            pass.type = type;

            const icon = this.querySelector("i");
            icon.classList.toggle("fa-eye");
            icon.classList.toggle("fa-eye-slash");
        });
        document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
            const pass = document.getElementById('confirm_password');
            const type = pass.type === "password" ? "text" : "password";
            pass.type = type;

            const icon = this.querySelector("i");
            icon.classList.toggle("fa-eye");
            icon.classList.toggle("fa-eye-slash");
        });
    </script>
</body>

</body>
</html>
