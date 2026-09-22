<?php
    if (isset($_SESSION["LoginAdmin"])) {
        $userid = $_SESSION["LoginAdmin"];
        $username = "Admin";
        $profileurl = "../Admin/profile.php";
        $settingsurl = "../Admin/settings.php";
    } elseif (isset($_SESSION["LoginFaculty"])) {
        $Fac_ID=$_SESSION['LoginFaculty'];
        $query = "SELECT * FROM `faculty` WHERE `Fac_ID` = '$Fac_ID' ";
        $run = mysqli_query($con, $query);
        $res = mysqli_fetch_array($run);
        $username = $res['Fac_Name'];
        $profileurl = "../Faculty/profile.php";
        $settingsurl = "../Faculty/settings.php";
    } elseif (isset($_SESSION["LoginStudent"])) {
        $Stud_ID=$_SESSION['LoginStudent'];
	    $query = "SELECT * FROM `student` WHERE `Stud_ID` = '$Stud_ID' ";
        $run = mysqli_query($con, $query);
        $res = mysqli_fetch_array($run);
        $username = $res['Stud_Name'];
        $profileurl = "../Student/profile.php";
        $settingsurl = "../Student/settings.php";
    } elseif (isset($_SESSION["LoginDeptAdmin"])) {
        $Dept_ID = $_SESSION["LoginDeptAdmin"];
        $query = "SELECT * FROM `department` WHERE `Dept_ID` = '$Dept_ID' ";
        $run = mysqli_query($con, $query);
        $res = mysqli_fetch_array($run);
        $username = $res['Dept_Name'];
        $profileurl = "../Dept_Admin/profile.php";
        $settingsurl = "../Dept_Admin/settings.php";
    }
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- MathLive -->
    <script type="module">
        import 'https://cdn.jsdelivr.net/npm/mathlive/dist/mathlive.min.js';
    </script>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Custom Styles -->
    <link rel="stylesheet" href="../Css/style.css">        
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top bg-white shadow-sm border-bottom px-4 py-2">
        <div class="d-flex align-items-center">
            <i class="bi bi-list menu-toggle fs-3 text-secondary me-3" id="menuToggle" style="cursor: pointer; transition: color 0.2s;" onmouseover="this.classList.replace('text-secondary', 'text-primary')" onmouseout="this.classList.replace('text-primary', 'text-secondary')"></i>
            <a class="navbar-brand fw-bold fs-5 text-primary me-3 mb-0" href="#" style="letter-spacing: 0.5px;">
                MITS Assessment Portal
            </a>
        </div>
        
        <a class="navbar-brand position-absolute top-50 start-50 translate-middle d-none d-md-block" href="#">
            <img src="../Images/MITS Logo.png" alt="MITS Logo" height="45" class="rounded">
        </a>
        
        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown">
                <button class="btn btn-light bg-transparent border-0 d-flex align-items-center gap-2 dropdown-toggle rounded-pill px-3 py-1 hover-shadow" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="transition: all 0.2s ease;">
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                        <i class="bi bi-person-fill fs-5"></i>
                    </div>
                    <span class="fw-semibold text-dark d-none d-sm-inline"><?php echo $username; ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 rounded-4 px-2 pb-2">

                    <li><a class="dropdown-item rounded-3 d-flex align-items-center gap-2 py-2 mb-1" href="<?php echo $profileurl; ?>"><i class="bi bi-person text-secondary fs-5"></i> My Profile</a></li>
                    <li><a class="dropdown-item rounded-3 d-flex align-items-center gap-2 py-2 mb-1" href="<?php echo $settingsurl; ?>"><i class="bi bi-gear text-secondary fs-5"></i> Settings</a></li>
                    <li><hr class="dropdown-divider my-2"></li>
                    <li><a class="dropdown-item rounded-3 d-flex align-items-center gap-2 py-2 text-danger fw-semibold dropdown-item-danger" href="../Login/logout.php"><i class="bi bi-box-arrow-right fs-5"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <style>
        .hover-shadow:hover {
            background-color: #f8f9fa !important;
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075) !important;
        }
        .navbar .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #0d6efd;
        }
        .navbar .dropdown-item.dropdown-item-danger:hover {
            background-color: #ffeef0;
            color: #dc3545 !important;
        }
        .navbar .dropdown-item i {
            transition: color 0.2s;
        }
        .navbar .dropdown-item:hover i {
            color: inherit !important;
        }
        .bg-primary-subtle {
            background-color: #cfe2ff !important;
        }
    </style>
</body>
</html>
