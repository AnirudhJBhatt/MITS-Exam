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
<!DOCTYPE html>
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
        <div class="container-fluid">
            <!-- Header -->
            <div class="dashboard-header mb-4 border-bottom pb-3">
                <h4 class="mb-0 fw-bold">
                    <i class="bi bi-gear-fill me-2"></i>Account Settings
                </h4>
            </div>

            <div class="sub-main">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-header bg-white border-bottom-0 py-3 px-4">
                                <h6 class="text-uppercase fw-bold mb-0" style="letter-spacing: 0.5px;">
                                    <i class="bi bi-shield-lock me-2"></i>Update Password
                                </h6>
                            </div>
                            <div class="card-body px-4 py-4">
                                <form action="" method="post">
                                    <div class="mb-3">
                                        <label for="new_pass" class="form-label small fw-bold text-muted">New Password <span class="text-danger">*</span></label>
                                        <div class="input-group shadow-sm">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-key text-muted"></i></span>
                                            <input type="password" name="new_pass" class="form-control border-start-0 ps-0 focus-ring focus-ring-light" required placeholder="Enter New Password" id="new_pass" style="border-left: none;">
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label for="conf_pass" class="form-label small fw-bold text-muted">Confirm New Password <span class="text-danger">*</span></label>
                                        <div class="input-group shadow-sm">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-check2-circle text-muted"></i></span>
                                            <input type="password" name="conf_pass" class="form-control border-start-0 ps-0 focus-ring focus-ring-light" required placeholder="Confirm New Password" id="conf_pass" style="border-left: none;">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div class="form-check form-switch user-select-none">
                                            <input type="checkbox" class="form-check-input shadow-sm" style="cursor: pointer;" onclick="myFunction()" id="check">
                                            <label class="form-check-label text-muted small fw-semibold" style="cursor: pointer;" for="check">Show Passwords</label>
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm hover-elevate">
                                            <i class="bi bi-save me-1"></i> Update Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .hover-elevate {
                transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            }
            .hover-elevate:hover {
                transform: translateY(-2px);
                box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.15) !important;
            }
            .focus-ring-light:focus {
                box-shadow: none !important;
                border-color: #dee2e6 !important;
            }
            .input-group:focus-within {
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
                border-radius: 0.375rem;
            }
            .input-group:focus-within .input-group-text,
            .input-group:focus-within .form-control {
                border-color: #86b7fe !important;
            }
        </style>
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