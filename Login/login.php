<?php
    include("../Connection/connection.php");
    session_start();
    if(isset($_POST['submit'])){
        $username = $_POST['username'];
        $password = $_POaST['password'];

        $query = "SELECT * FROM users WHERE email='$username' AND password='$password'";
        $result = mysqli_query($con, $query);

        if (mysqli_num_rows($result)>0) {
            while ($row=mysqli_fetch_array($result)) {
                if ($row["role"]=="admin"){
                    $_SESSION['LoginAdmin']=$row["user_id"];
                    header('Location: ../Admin/dashboard.php');
                }
                else if ($row["role"]=="faculty"){
                    $_SESSION['LoginFaculty']=$row['user_id'];
                    header('Location: ../Faculty/dashboard.php');
                }
                else if ($row["role"]=="student"){
                    $_SESSION['LoginStudent']=$row['user_id'];
                    header('Location: ../Student/dashboard.php');
                }
            }
        }
        else{ 
            echo "<script>alert('Invalid Credentials');</script>";
        }
    }
?>

<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MITS Examination System - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">   

    <style>
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            font-family: 'Montserrat', sans-serif;
            overflow: hidden;
        }
    </style>
</head>

<body>
    <div class="left-panel">
        <div class="logo-box">
            <img src="https://mgmits.ac.in/frontend/images/logo1.png" alt="MITS Logo">
        </div>
        <h3>MITS Internal Exam Portal</h3>
        <p>Empowering smart internal assessments with accuracy and transparency.</p>
    </div>

    <div class="right-panel">
        <div class="login-container">
            <h3>Login</h3>
            <form action="" method="POST">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="username" placeholder="Username" required>
                    <label for="username">Username</label>
                </div>
                <div class="form-floating mb-3 position-relative">
                    <input type="password" class="form-control" name="password" id="password" placeholder="Password"
                        required>
                    <label for="password">Password</label>
                    <button type="button"
                        class="btn btn-outline-secondary btn-sm position-absolute top-50 end-0 translate-middle-y"
                        id="togglePassword" aria-label="Show password" style="border: none; background: none;">
                        <i class="fa fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <button type="submit" name="submit" class="btn btn-success btn-login">Login</button>
                <div class="forgot-password">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot password?</a>
                </div>
                <!-- <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title text-danger" id="forgotPasswordModalLabel">Forgot Password</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php //include("./Login/forgot-password.php"); ?>
                        </div>
                    </div>
                </div>
            </div> -->
            </form>
        </div>
    </div>

    <!-- Forgot Password Modal (optional) -->
    <!--
  <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-danger" id="forgotPasswordModalLabel">Forgot Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <?php // include("./Login/forgot-password.php"); ?>
        </div>
      </div>
    </div>
  </div>
  -->

    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordField = document.getElementById('password');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>