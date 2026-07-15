<div class="sidebar" id="sidebar">
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    <ul>
        <li>
            <a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>"
            href="../Faculty/dashboard.php">
                <i class="bi bi-speedometer"></i><span> Dashboard</span>
            </a>
        </li>

        <li>
            <a class="nav-link <?= ($current_page == 'manage-student.php') ? 'active' : ''; ?>"
            href="../Faculty/manage-student.php">
                <i class="bi bi-person-lines-fill"></i><span> View Students</span>
            </a>
        </li>        

        <li>
            <a class="nav-link <?= ($current_page == 'view-courses.php') ? 'active' : ''; ?>"
            href="../Faculty/view-courses.php">
                <i class="bi bi-journal-bookmark"></i><span>View Courses</span>
            </a>
        </li>

<<<<<<< HEAD

=======
>>>>>>> af63e72 (MITS-Exam)
        <li>
            <a class="nav-link <?= ($current_page == 'manage-exam.php') ? 'active' : ''; ?>"
            href="../Faculty/manage-exam.php">
                <i class="bi bi-journal-text"></i><span> Exams</span>
            </a>
        </li>

        <li>
            <a class="nav-link <?= ($current_page == 'view-results.php') ? 'active' : ''; ?>"
            href="../Faculty/view-results.php">
                <i class="bi bi-graph-up"></i><span> Results</span>
            </a>
        </li>

        <li>
            <a class="nav-link <?= ($current_page == 'question-bank.php') ? 'active' : ''; ?>"
            href="../Faculty/question-bank.php">
                <i class="bi bi-collection"></i><span> Question Bank</span>
            </a>
        </li>

        <li>
            <a class="nav-link <?= ($current_page == 'settings.php') ? 'active' : ''; ?>"
            href="../Faculty/settings.php">
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