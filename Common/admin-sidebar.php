<div class="sidebar" id="sidebar">
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    <ul>
        <li><a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="../Admin/dashboard.php"><i
                    class="bi bi-speedometer2"></i><span> Dashboard</span></a></li>
        <li><a class="nav-link <?= ($current_page == 'manage-students.php') ? 'active' : ''; ?>" href="../Admin/manage-students.php"><i
                    class="bi bi-people"></i><span> Manage Students</span></a></li>
        <li><a class="nav-link <?= ($current_page == 'exam-list.php') ? 'active' : ''; ?>" href="../Admin/view-exams.php"><i
                    class="bi bi-pencil-square"></i><span> Exams</span></a></li>
        <li><a class="nav-link <?= ($current_page == 'results.php') ? 'active' : ''; ?>" href="#"><i
                    class="bi bi-bar-chart"></i><span> Results</span></a></li>
        <li><a class="nav-link <?= ($current_page == 'reports.php') ? 'active' : ''; ?>" href="#"><i
                    class="bi bi-file-earmark-text"></i><span> Reports</span></a></li>
        <li><a class="nav-link <?= ($current_page == 'settings.php') ? 'active' : ''; ?>" href="#"><i
                    class="bi bi-gear-fill"></i><span> Settings</span></a></li>
    </ul>
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