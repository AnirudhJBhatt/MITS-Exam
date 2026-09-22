<div class="sidebar bg-white shadow-sm border-end d-flex flex-column pt-4" id="sidebar" style="transition: all 0.3s ease; z-index: 1040;">
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    
    <ul class="nav flex-column  mb-auto w-100 px-3 gap-1">
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 text-dark sidebar-link <?= ($current_page == 'dashboard.php') ? 'active bg-danger-subtle text-danger fw-bold' : ''; ?>"
            href="../Faculty/dashboard.php">
                <i class="bi bi-speedometer fs-5"></i><span class="sidebar-text">Dashboard</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 text-dark sidebar-link <?= ($current_page == 'manage-student.php') ? 'active bg-danger-subtle text-danger fw-bold' : ''; ?>"
            href="../Faculty/manage-student.php">
                <i class="bi bi-person-lines-fill fs-5"></i><span class="sidebar-text">View Students</span>
            </a>
        </li>        

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 text-dark sidebar-link <?= ($current_page == 'view-courses.php') ? 'active bg-danger-subtle text-danger fw-bold' : ''; ?>"
            href="../Faculty/view-courses.php">
                <i class="bi bi-journal-bookmark fs-5"></i><span class="sidebar-text">View Courses</span>
            </a>
        </li>

        <!-- <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 text-dark sidebar-link <?= ($current_page == 'manage-exam.php') ? 'active bg-danger-subtle text-danger fw-bold' : ''; ?>"
            href="../Faculty/manage-exam.php">
                <i class="bi bi-journal-text fs-5"></i><span class="sidebar-text">Exams</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 text-dark sidebar-link <?= ($current_page == 'view-results.php') ? 'active bg-danger-subtle text-danger fw-bold' : ''; ?>"
            href="../Faculty/view-results.php">
                <i class="bi bi-graph-up fs-5"></i><span class="sidebar-text">Results</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 text-dark sidebar-link <?= ($current_page == 'question-bank.php') ? 'active bg-danger-subtle text-danger fw-bold' : ''; ?>"
            href="../Faculty/question-bank.php">
                <i class="bi bi-collection fs-5"></i><span class="sidebar-text">Question Bank</span>
            </a>
        </li> -->

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 text-dark sidebar-link <?= ($current_page == 'settings.php') ? 'active bg-danger-subtle text-danger fw-bold' : ''; ?>"
            href="../Faculty/settings.php">
                <i class="bi bi-gear fs-5"></i><span class="sidebar-text">Settings</span>
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