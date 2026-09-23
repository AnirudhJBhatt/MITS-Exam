<div class="sidebar bg-dark shadow-lg border-end border-dark d-flex flex-column pt-4" id="sidebar" style="transition: all 0.3s ease; z-index: 1040;">
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    <ul class="nav flex-column mb-auto w-100 px-3 gap-2">
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'dashboard.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Admin/dashboard.php">
                <i class="bi bi-speedometer fs-5"></i><span class="sidebar-text">Dashboard</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'manage-students.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Admin/manage-students.php">
                <i class="bi bi-person-lines-fill fs-5"></i><span class="sidebar-text">Manage Students</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'manage-faculty.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Admin/manage-faculty.php">
                <i class="bi bi-person-workspace fs-5"></i><span class="sidebar-text">Manage Faculty</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'manage-department.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Admin/manage-department.php">
                <i class="bi bi-building fs-5"></i><span class="sidebar-text">Manage Department</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'view-exams.php' || $current_page == 'exam-list.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Admin/view-exams.php">
                <i class="bi bi-journal-check fs-5"></i><span class="sidebar-text">Exams</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'view-results.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Admin/view-results.php">
                <i class="bi bi-graph-up fs-5"></i><span class="sidebar-text">Results</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'academic-year.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Admin/academic-year.php">
                <i class="bi bi-calendar3 fs-5"></i><span class="sidebar-text">Academic Year</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'settings.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="#">
                <i class="bi bi-sliders fs-5"></i><span class="sidebar-text">Settings</span>
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