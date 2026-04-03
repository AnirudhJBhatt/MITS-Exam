<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Dashboard</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: "Solway", serif;
        }


        body {
            background-color: #f7f8fa;
            margin: 0;
            overflow-x: hidden;
        }

        /* NAVBAR */
        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e5e5e5;
            height: 70px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.05);
            z-index: 1001;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #d1202d !important;
            font-weight: 700;
            font-size: 22px;
            letter-spacing: 1px;
        }

        .navbar-brand img {
            transition: transform 0.3s ease;
        }

        .navbar-brand img:hover {
            transform: scale(1.05);
        }

        .menu-toggle {
            font-size: 24px;
            cursor: pointer;
            color: #d1202d;
            margin-right: 15px;
            transition: all 0.3s ease;
        }

        .menu-toggle:hover {
            color: #a01321;
        }

        .nav-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .nav-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #d1202d;
        }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            top: 70px;
            left: 0;
            width: 250px;
            height: calc(100vh - 70px);
            background-color: #181a1e;
            color: #fff;
            padding-top: 20px;
            transition: width 0.3s ease;
            overflow: hidden;
            z-index: 1000;
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar ul li {
            margin: 10px 0;
        }

        .sidebar .nav-link {
            color: #cfd2d6;
            font-size: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .sidebar .nav-link:hover {
            background-color: #d1202d;
            color: #fff;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background-color: #d1202d;
            color: #fff;
        }

        .sidebar.collapsed .nav-link span {
            display: none;
        }

        .sidebar i {
            font-size: 20px;
            min-width: 30px;
            text-align: center;
        }

        /* MAIN CONTENT */
        main {
            margin-left: 250px;
            padding: 90px 30px 40px 30px;
            transition: margin-left 0.3s ease;
        }

        .collapsed+main {
            margin-left: 80px;
        }

        .dashboard-header {
            background: #d1202d;
            color: #fff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }

        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .card i {
            font-size: 35px;
            color: #d1202d;
        }

        /* FOOTER */
        .footer {
            background-color: #ffffff;
            color: #555;
            text-align: center;
            font-size: 13px;
            padding: 10px;
            border-top: 1px solid #e5e5e5;
            position: fixed;
            bottom: 0;
            left: 250px;
            right: 0;
            font-style: italic;
            transition: left 0.3s ease;
        }

        .collapsed+main+.footer {
            left: 80px;
        }

        @media (max-width: 992px) {
            .sidebar {
                left: -250px;
            }

            .sidebar.show {
                left: 0;
            }

            main {
                margin-left: 0;
                padding: 20px;
            }

            .footer {
                left: 0;
            }
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg fixed-top px-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-list menu-toggle" id="menuToggle"></i>
            <a class="navbar-brand fw-bold fs-5 text-white me-3" href="#" style="letter-spacing: 1px;">MITS Exam Portal</a>
        </div>
        <a class="navbar-brand position-absolute top-50 start-50 translate-middle" href="#">
            <img src="https://mgmits.ac.in/frontend/images/logo1.png" alt="MITS Logo" height="75" class="me-2">
        </a>
        <div class="ms-auto nav-profile dropdown">
            <i class="bi bi-person-circle fs-4 me-2"></i>
            <span class="fw-bold text-dark dropdown-toggle" data-bs-toggle="dropdown">Admin</span>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Profile</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="../Login/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>
    </nav>
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <ul>
            <li><a class="nav-link <?= ($current_page == 'admin-dashboard.php') ? 'active' : ''; ?>" href="#"><i
                        class="bi bi-speedometer2"></i><span> Dashboard</span></a></li>
            <li><a class="nav-link <?= ($current_page == 'manage-students.php') ? 'active' : ''; ?>" href="#"><i
                        class="bi bi-people"></i><span> Manage Students</span></a></li>
            <li><a class="nav-link <?= ($current_page == 'exam-list.php') ? 'active' : ''; ?>" href="#"><i
                        class="bi bi-pencil-square"></i><span> Exams</span></a></li>
            <li><a class="nav-link <?= ($current_page == 'results.php') ? 'active' : ''; ?>" href="#"><i
                        class="bi bi-bar-chart"></i><span> Results</span></a></li>
            <li><a class="nav-link <?= ($current_page == 'reports.php') ? 'active' : ''; ?>" href="#"><i
                        class="bi bi-file-earmark-text"></i><span> Reports</span></a></li>
            <li><a class="nav-link <?= ($current_page == 'settings.php') ? 'active' : ''; ?>" href="#"><i
                        class="bi bi-gear-fill"></i><span> Settings</span></a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <main>
        <div class="dashboard-header">
            <h4 class="mb-0 fw-bold">Admin Dashboard</h4>
        </div>

    </main>
    <!-- FOOTER -->
    <div class="footer">
        © 2025 MITS Exam Portal
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById("sidebar");
        const menuToggle = document.getElementById("menuToggle");

        menuToggle.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
        });

        // Optional: Auto-collapse on small screens
        window.addEventListener("resize", () => {
            if (window.innerWidth < 992) {
                sidebar.classList.add("collapsed");
            } else {
                sidebar.classList.remove("collapsed");
            }
        });
    </script>
</body>
</html>