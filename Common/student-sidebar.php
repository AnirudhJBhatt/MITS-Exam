<div class="sidebar" id="sidebar">
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    <ul>
        <li>
            <a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>"
            href="../Student/dashboard.php">
                <i class="bi bi-speedometer"></i><span> Dashboard</span>
            </a>
        </li>

        <li>
            <a class="nav-link <?= ($current_page == 'exam.php') ? 'active' : ''; ?>"
            href="../Student/exam.php">
                <i class="bi bi-journal-text"></i><span> Exams</span>
            </a>
        </li>

        <li>
            <a class="nav-link <?= ($current_page == 'view-courses.php') ? 'active' : ''; ?>"
            href="../Student/view-courses.php">
                <i class="bi bi-journal-bookmark"></i><span> My Courses</span>
            </a>
        </li>

        <li>
            <a class="nav-link <?= ($current_page == 'results.php') ? 'active' : ''; ?>"
            href="#">
                <i class="bi bi-graph-up"></i><span> Results</span>
            </a>
        </li>
        
        <li>
            <a class="nav-link <?= ($current_page == 'settings.php') ? 'active' : ''; ?>"
            href="../Student/settings.php">
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