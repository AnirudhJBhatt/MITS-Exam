<div class="sidebar" id="sidebar">
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    <ul>
    <li>
        <a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>" 
           href="../Admin/dashboard.php">
            <i class="bi bi-speedometer"></i><span> Dashboard</span>
        </a>
    </li>

    <li>
        <a class="nav-link <?= ($current_page == 'manage-students.php') ? 'active' : ''; ?>" 
           href="../Admin/manage-students.php">
            <i class="bi bi-person-lines-fill"></i><span> Manage Students</span>
        </a>
    </li>

    <li>
        <a class="nav-link <?= ($current_page == 'manage-faculty.php') ? 'active' : ''; ?>" 
           href="../Admin/manage-faculty.php">
            <i class="bi bi-person-workspace"></i><span> Manage Faculty</span>
        </a>
    </li>

    <li>
        <a class="nav-link <?= ($current_page == 'manage-department.php') ? 'active' : ''; ?>" 
           href="../Admin/manage-department.php">
            <i class="bi bi-building"></i><span> Manage Department</span>
        </a>
    </li>

    <li>
        <a class="nav-link <?= ($current_page == 'exam-list.php') ? 'active' : ''; ?>" 
           href="../Admin/view-exams.php">
            <i class="bi bi-journal-check"></i><span> Exams</span>
        </a>
    </li>

    <li>
        <a class="nav-link <?= ($current_page == 'view-results.php') ? 'active' : ''; ?>" 
           href="../Admin/view-results.php">
            <i class="bi bi-graph-up"></i><span> Results</span>
        </a>
    </li>

    <li>
        <a class="nav-link <?= ($current_page == 'academic-year.php') ? 'active' : ''; ?>" 
           href="../Admin/academic-year.php">
            <i class="bi bi-calendar3   "></i><span> Academic Year</span>
        </a>
    </li>

    <li>
        <a class="nav-link <?= ($current_page == 'settings.php') ? 'active' : ''; ?>" 
           href="#">
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