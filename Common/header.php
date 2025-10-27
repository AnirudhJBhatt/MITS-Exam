<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Custom Styles -->
    <link rel="stylesheet" href="../Css/style.css">        
</head>
<body>
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
</body>
</html>
