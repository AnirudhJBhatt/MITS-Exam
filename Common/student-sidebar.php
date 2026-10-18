<div class="sidebar bg-dark shadow-lg border-end border-dark d-flex flex-column pt-4" id="sidebar" style="transition: all 0.3s ease; z-index: 1040;">
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    <ul class="nav flex-column mb-auto w-100 px-3 gap-3">
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'dashboard.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Student/dashboard.php">
                <i class="bi bi-grid-1x2-fill fs-5"></i><span class="sidebar-text">Dashboard</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'view-exams.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Student/view-exams.php">
                <i class="bi bi-file-earmark-text-fill fs-5"></i><span class="sidebar-text">Exams</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'view-courses.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Student/view-courses.php">
                <i class="bi bi-book-fill fs-5"></i><span class="sidebar-text"> My Courses</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'view-results.php' || $current_page == 'results.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Student/view-results.php">
                <i class="bi bi-bar-chart-fill fs-5"></i><span class="sidebar-text"> Results</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'settings.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Student/settings.php">
                <i class="bi bi-gear-fill fs-5"></i><span class="sidebar-text"> Settings</span>
            </a>
        </li>
    </ul>

    <style>
        .sidebar-link.text-white-50:hover {
            color: white !important;
            background-color: rgba(255,255,255,0.05);
            transition: all 0.2s ease-in-out;
        }
    </style>
</div>