<?php  
	session_start();
	if (!isset($_SESSION['LoginFaculty'])) {
        echo "<script>alert('You Are Not Authorize Person For This link'); window.location.href='../index.php';</script>";
        exit;
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	require_once "../Connection/connection.php";
    $Fac_ID = $_SESSION['LoginFaculty'];
    if (isset($_POST['submit'])) {
        $new_pass = mysqli_real_escape_string($con, $_POST['new_pass']);
        $conf_pass = mysqli_real_escape_string($con, $_POST['conf_pass']);
        if ($new_pass === $conf_pass) {
            $update_query = "UPDATE `login` SET `Password` = '$new_pass' WHERE `ID` = '$Fac_ID'";
            if (mysqli_query($con, $update_query)) {
                echo "<script>alert('Password Updated Successfully'); window.location.href = 'settings.php';</script>";
            } else {
                echo "<script>alert('Error updating password. Please try again.'); window.location.href = 'settings.php';</script>";
            }
            echo "Password match. (Database update code is commented out.)";
        } else {
            echo "<script>alert('New Password and Confirm Password do not match.'); window.location.href = 'settings.php';</script>";
        }
    }
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student - Dashboard</title>
</head>

<body>

    <!-- NAVBAR -->
    <?php include '../Common/header.php'; ?>
    <!-- SIDEBAR -->
    <?php include '../Common/faculty-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main>
        <div class="dashboard-header">
            <h4 class="mb-0 fw-bold">Settings</h4>
        </div>
        <div class="sub-main">
            <div class="row">
                <div class="col-md-12">
                    <form action="" method="post">
                        <div class="row">
                            <div class="col">
                                <input type="password" name="new_pass" class="form-control" required
                                    placeholder="Enter New Password" id="new_pass">
                            </div>
                            <div class="col">
                                <input type="password" name="conf_pass" class="form-control" required
                                    placeholder="Confirm New Password" id="conf_pass">
                            </div>
                            <div class="col">
                                <input type="submit" name="submit" value="Update Password" class="btn btn-primary px-3">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" onclick="myFunction()"
                                        id="check">Show Password
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <?php include '../Common/footer.php'; ?>
    <script>
        (() => {
            'use strict';
            const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(tooltipTriggerEl => {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        })();
        function myFunction() {
            var checkBox = document.getElementById("check");
            var x = document.getElementById("new_pass");
            var y = document.getElementById("conf_pass");
            if (checkBox.checked == true) {
                x.type = "text";
                y.type = "text";
            } else {
                x.type = "password";
                y.type = "password";
            }
        }
    </script>
</body>

</html>