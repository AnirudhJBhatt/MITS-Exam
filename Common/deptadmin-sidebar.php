<div class="sidebar bg-dark shadow-lg border-end border-dark d-flex flex-column pt-4" id="sidebar" style="transition: all 0.3s ease; z-index: 1040;">
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    <ul class="nav flex-column mb-auto w-100 px-3 gap-3">
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'dashboard.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Dept_Admin/dashboard.php">
                <i class="bi bi-grid-1x2-fill fs-5"></i><span class="sidebar-text">Dashboard</span>
            </a>
        </li>

    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'manage-student.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
           href="../Dept_Admin/manage-student.php">
            <i class="bi bi-people-fill fs-5"></i><span class="sidebar-text"> Manage Students</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'manage-faculty.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
           href="../Dept_Admin/manage-faculty.php">
            <i class="bi bi-person-vcard-fill fs-5"></i><span class="sidebar-text"> Manage Faculty</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'manage-programmes.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
           href="../Dept_Admin/manage-programmes.php">
            <i class="bi bi-mortarboard-fill fs-5"></i><span class="sidebar-text"> Manage Programmes</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'manage-courses.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
           href="../Dept_Admin/manage-courses.php">
            <i class="bi bi-book-fill fs-5"></i><span class="sidebar-text"> Manage Courses</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'map-courses.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
           href="../Dept_Admin/map-courses.php">
            <i class="bi bi-diagram-2-fill fs-5"></i><span class="sidebar-text"> Course Mapping</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'view-exams.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
           href="../Dept_Admin/view-exams.php">
            <i class="bi bi-file-earmark-text-fill fs-5"></i><span class="sidebar-text"> View Exams</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'view-results.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
           href="../Dept_Admin/view-results.php">
            <i class="bi bi-bar-chart-fill fs-5"></i><span class="sidebar-text"> View Results</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'settings.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
           href="../Dept_Admin/settings.php">
            <i class="bi bi-gear-fill fs-5"></i><span class="sidebar-text"> Settings</span>
        </a>
    </li>
</ul>

    <style>
        /* Hover effect for inactive links */
        .sidebar-link.text-white-50:hover {
            color: white !important;
            background-color: rgba(255,255,255,0.05);
            transition: all 0.2s ease-in-out;
        }
    </style>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const sidebar = document.getElementById("sidebar");
        const menuToggle = document.getElementById("menuToggle");

        menuToggle.addEventListener("click", () => {
            if (window.innerWidth < 992) {
                // MOBILE → Slide in/out
                sidebar.classList.toggle("show");
            } else {
                // DESKTOP → Collapse width
                sidebar.classList.toggle("collapsed");
            }
        });

        // Auto-adjust when resizing
        window.addEventListener("resize", () => {
            if (window.innerWidth < 992) {
                sidebar.classList.remove("collapsed"); // remove desktop style
            } else {
                sidebar.classList.remove("show"); // remove mobile style
            }
        });
    });
</script>