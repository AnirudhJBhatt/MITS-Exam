<?php
/**
 * Global Header Component
 * Provides unified navigation, brand identity, and asset imports
 */

require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/auth.php";

$username = "User";
$userRole = get_logged_in_role() ?? 'Guest';
$profileurl = "#";
$settingsurl = "#";

if (isset($_SESSION["LoginAdmin"])) {
    $userid = $_SESSION["LoginAdmin"];
    $username = "Administrator";
    $profileurl = "../Admin/profile.php";
    $settingsurl = "../Admin/settings.php";
} elseif (isset($_SESSION["LoginFaculty"])) {
    $Fac_ID = $_SESSION['LoginFaculty'];
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT Fac_Name FROM faculty WHERE Fac_ID = ? LIMIT 1");
        $stmt->execute([$Fac_ID]);
        $res = $stmt->fetch();
        if ($res) $username = $res['Fac_Name'];
    } elseif ($con) {
        $stmt = $con->prepare("SELECT Fac_Name FROM faculty WHERE Fac_ID = ? LIMIT 1");
        $stmt->bind_param("s", $Fac_ID);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res) $username = $res['Fac_Name'];
    }
    $profileurl = "../Faculty/profile.php";
    $settingsurl = "../Faculty/settings.php";
} elseif (isset($_SESSION["LoginStudent"])) {
    $Stud_ID = $_SESSION['LoginStudent'];
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT Stud_Name FROM student WHERE Stud_ID = ? LIMIT 1");
        $stmt->execute([$Stud_ID]);
        $res = $stmt->fetch();
        if ($res) $username = $res['Stud_Name'];
    } elseif ($con) {
        $stmt = $con->prepare("SELECT Stud_Name FROM student WHERE Stud_ID = ? LIMIT 1");
        $stmt->bind_param("s", $Stud_ID);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res) $username = $res['Stud_Name'];
    }
    $profileurl = "../Student/profile.php";
    $settingsurl = "../Student/settings.php";
} elseif (isset($_SESSION["LoginDeptAdmin"])) {
    $Dept_ID = $_SESSION["LoginDeptAdmin"];
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT Dept_Name FROM department WHERE Dept_ID = ? LIMIT 1");
        $stmt->execute([$Dept_ID]);
        $res = $stmt->fetch();
        if ($res) $username = $res['Dept_Name'];
    } elseif ($con) {
        $stmt = $con->prepare("SELECT Dept_Name FROM department WHERE Dept_ID = ? LIMIT 1");
        $stmt->bind_param("s", $Dept_ID);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res) $username = $res['Dept_Name'];
    }
    $profileurl = "../DeptAdmin/profile.php";
    $settingsurl = "../DeptAdmin/settings.php";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#d1202d">
    <title>MITS Internal Assessment Portal</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- MathLive LaTeX Module -->
    <script type="module" src="https://cdn.jsdelivr.net/npm/mathlive/dist/mathlive.min.js"></script>

    <!-- Custom Unified Stylesheet -->
    <link rel="stylesheet" href="../Css/style.css">
</head>
<body>

    <!-- TOP NAVIGATION NAVBAR -->
    <nav class="navbar navbar-expand-lg fixed-top bg-white border-bottom px-3 px-lg-4">
        <div class="d-flex align-items-center">
            <button class="btn btn-link p-1 me-2 text-decoration-none" id="menuToggle" type="button" aria-label="Toggle navigation">
                <i class="bi bi-list menu-toggle fs-2"></i>
            </button>
            <a class="navbar-brand me-3" href="#">
                <img src="../Images/MITS Logo.png" alt="MITS Logo" height="35" class="rounded d-none d-sm-inline">
                <span>MITS Internal Assessment Portal</span>
            </a>
        </div>

        <div class="ms-auto d-flex align-items-center gap-3">
            <!-- User Role Badge -->
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1 d-none d-md-inline">
                <?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?>
            </span>

            <!-- Profile Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light bg-transparent border-0 d-flex align-items-center gap-2 rounded-pill px-2 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; font-size: 0.95rem;">
                        <?= strtoupper(substr(htmlspecialchars($username, ENT_QUOTES, 'UTF-8'), 0, 1)) ?>
                    </div>
                    <span class="fw-semibold text-dark d-none d-sm-inline fs-6"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="bi bi-chevron-down text-muted small"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border border-slate-200 mt-2 rounded-3 px-2 py-2">
                    <li class="px-3 py-2 border-bottom mb-1">
                        <p class="mb-0 fw-semibold text-dark text-truncate" style="max-width: 180px;"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></p>
                        <small class="text-muted"><?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?></small>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-2 py-2" href="<?= htmlspecialchars($settingsurl, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-gear me-2 text-muted"></i> Settings
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item rounded-2 py-2 text-danger fw-semibold" href="../Login/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Mobile Offcanvas Backdrop Overlay -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
