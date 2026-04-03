<div class="sidebar" id="sidebar">
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    <ul>
    <li>
        <a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>"
           href="../Dept_Admin/dashboard.php">
            <i class="bi bi-speedometer"></i><span> Dashboard</span>
        </a>
    </li>

    <li>
        <a class="nav-link <?= ($current_page == 'manage-student.php') ? 'active' : ''; ?>"
           href="../Dept_Admin/manage-student.php">
            <i class="bi bi-person-lines-fill"></i><span> Manage Students</span>
        </a>
    </li>

    <li>
        <a class="nav-link <?= ($current_page == 'manage-faculty.php') ? 'active' : ''; ?>"
           href="../Dept_Admin/manage-faculty.php">
            <i class="bi bi-person-workspace"></i><span> Manage Faculty</span>
        </a>
    </li>

    <li>
        <a class="nav-link <?= ($current_page == 'manage-programmes.php') ? 'active' : ''; ?>"
           href="../Dept_Admin/manage-programmes.php">
            <i class="bi bi-person-workspace"></i><span> Manage Programmes</span>
        </a>
    </li>

    <li>
        <a class="nav-link <?= ($current_page == 'manage-courses.php') ? 'active' : ''; ?>"
           href="../Dept_Admin/manage-courses.php">
            <i class="bi bi-journal-bookmark"></i><span> Manage Courses</span>
        </a>
    </li>

    <li>
        <a class="nav-link <?= ($current_page == 'map-courses.php') ? 'active' : ''; ?>"
           href="../Dept_Admin/map-courses.php">
            <i class="bi bi-diagram-3"></i><span> Course Mapping</span>
        </a>
    </li>

    <li>
        <a class="nav-link <?= ($current_page == 'view-exams.php') ? 'active' : ''; ?>"
           href="../Dept_Admin/view-exams.php">
            <i class="bi bi-journal-check"></i><span> View Exams</span>
        </a>
    </li>

    <li>
        <a class="nav-link <?= ($current_page == 'view-results.php') ? 'active' : ''; ?>"
           href="../Dept_Admin/view-results.php">
            <i class="bi bi-graph-up"></i><span> View Results</span>
        </a>
    </li>

    <li>
        <a class="nav-link <?= ($current_page == 'settings.php') ? 'active' : ''; ?>"
           href="../Dept_Admin/settings.php">
            <i class="bi bi-sliders"></i><span> Settings</span>
        </a>
    </li>
</ul>

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