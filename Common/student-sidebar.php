<div class="sidebar" id="sidebar">
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    <ul class="nav flex-column  mb-auto w-100 px-3 gap-1">
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 text-dark sidebar-link <?= ($current_page == 'dashboard.php') ? 'active bg-danger-subtle text-danger fw-bold' : ''; ?>"
            href="../Student/dashboard.php">
                <i class="bi bi-speedometer fs-5"></i><span class="sidebar-text">Dashboard</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 text-dark sidebar-link <?= ($current_page == 'view-exams.php') ? 'active bg-danger-subtle text-danger fw-bold' : ''; ?>"
            href="../Student/view-exams.php">
                <i class="bi bi-journal-text fs-5"></i><span class="sidebar-text">Exams</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 text-dark sidebar-link <?= ($current_page == 'view-courses.php') ? 'active bg-danger-subtle text-danger fw-bold' : ''; ?>"
            href="../Student/view-courses.php">
                <i class="bi bi-journal-bookmark fs-5"></i><span class="sidebar-text"> My Courses</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 text-dark sidebar-link <?= ($current_page == 'results.php') ? 'active bg-danger-subtle text-danger fw-bold' : ''; ?>"
            href="#">
                <i class="bi bi-graph-up fs-5"></i><span class="sidebar-text"> Results</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 text-dark sidebar-link <?= ($current_page == 'settings.php') ? 'active bg-danger-subtle text-danger fw-bold' : ''; ?>"
            href="../Student/settings.php">
                <i class="bi bi-sliders fs-5"></i><span class="sidebar-text"> Settings</span>
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