<div class="sidebar bg-dark shadow-lg border-end border-dark d-flex flex-column pt-3" id="sidebar" style="transition: all 0.3s ease; z-index: 1040;">
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    
    <ul class="nav flex-column mb-auto w-100 px-3 gap-2">
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'dashboard.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Faculty/dashboard.php">
                <i class="bi bi-grid-1x2-fill fs-5"></i><span class="sidebar-text">Dashboard</span>
            </a>
        </li>
        
        <!-- <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= (in_array($current_page, ['question-bank.php', 'add-questions.php', 'new_question-bank.php'])) ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Faculty/question-bank.php">
                <i class="bi bi-folder-check fs-5"></i><span class="sidebar-text">Question Bank</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= (in_array($current_page, ['manage-exams.php', 'create-exam.php', 'manage-exam.php'])) ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Faculty/manage-exams.php">
                <i class="bi bi-journal-plus fs-5"></i><span class="sidebar-text">Manage Exams</span>
            </a>
        </li> -->

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'view-courses.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Faculty/view-courses.php">
                <i class="bi bi-book-half fs-5"></i><span class="sidebar-text">My Courses</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'manage-student.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Faculty/manage-student.php">
                <i class="bi bi-people-fill fs-5"></i><span class="sidebar-text">Students</span>
            </a>
        </li>

        <!-- <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= (in_array($current_page, ['view-results.php', 'view-results-new.php', 'enter-marks.php'])) ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Faculty/view-results.php">
                <i class="bi bi-bar-chart-line-fill fs-5"></i><span class="sidebar-text">Evaluation & Results</span>
            </a>
        </li> -->

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 sidebar-link <?= ($current_page == 'settings.php') ? 'active bg-danger text-white fw-bold shadow-sm' : 'text-white-50'; ?>"
            href="../Faculty/settings.php">
                <i class="bi bi-gear-fill fs-5"></i><span class="sidebar-text">Settings</span>
            </a>
        </li>
    </ul>
    
    <style>
        .sidebar-link.text-white-50:hover {
            color: white !important;
            background-color: rgba(255,255,255,0.08);
            transition: all 0.2s ease-in-out;
        }
    </style>
</div>