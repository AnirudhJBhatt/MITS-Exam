<!-- Top Navbar -->
<nav class="bg-white shadow fixed w-full z-40">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">

            <!-- Left: Logo + Hamburger -->
            <div class="flex items-center space-x-3">
                <button id="menuBtn" class="lg:hidden p-2 rounded-md hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <img src="https://mgmits.ac.in/frontend/images/logo1.png"
                     class="h-10" alt="MITS Logo">
                <span class="font-semibold text-lg">MITS Exam Portal</span>
            </div>

            <!-- Right: User dropdown -->
            <div class="relative flex items-center">
                <button id="userMenuBtn" class="flex items-center space-x-3 p-2 rounded-md hover:bg-gray-100">
                    <img src="https://ui-avatars.com/api/?name=Admin" class="h-8 w-8 rounded-full">
                    <span class="hidden sm:block font-medium">Admin</span>
                </button>

                <!-- Dropdown -->
                <div id="userDropdown"
                     class="hidden absolute right-0 mt-2 w-48 bg-white shadow-lg rounded-lg border py-2">
                    <a href="#" class="block px-4 py-2 hover:bg-gray-100">Profile</a>
                    <a href="#" class="block px-4 py-2 hover:bg-gray-100">Settings</a>
                    <a href="../Login/logout.php" class="block px-4 py-2 hover:bg-gray-100">Logout</a>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Sidebar -->
<div id="sidebar"
    class="fixed top-16 left-0 h-full w-64 bg-white shadow-lg transform -translate-x-full lg:translate-x-0 transition-all duration-300">

    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>

    <ul class="mt-4 space-y-1 px-2">

        <li>
            <a href="../Admin/dashboard.php"
               class="flex items-center p-3 rounded-lg 
               <?= ($current_page == 'dashboard.php') ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'; ?>">
                <i class="bi bi-speedometer2 mr-3"></i> Dashboard
            </a>
        </li>

        <li>
            <a href="../Admin/manage-students.php"
               class="flex items-center p-3 rounded-lg
               <?= ($current_page == 'manage-students.php') ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'; ?>">
                <i class="bi bi-people mr-3"></i> Manage Students
            </a>
        </li>

        <li>
            <a href="../Admin/view-exams.php"
               class="flex items-center p-3 rounded-lg
               <?= ($current_page == 'view-exams.php') ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'; ?>">
                <i class="bi bi-pencil-square mr-3"></i> Exams
            </a>
        </li>

        <li>
            <a href="#"
               class="flex items-center p-3 rounded-lg hover:bg-gray-100">
                <i class="bi bi-bar-chart mr-3"></i> Results
            </a>
        </li>

        <li>
            <a href="#"
               class="flex items-center p-3 rounded-lg hover:bg-gray-100">
                <i class="bi bi-file-earmark-text mr-3"></i> Reports
            </a>
        </li>

        <li>
            <a href="#"
               class="flex items-center p-3 rounded-lg hover:bg-gray-100">
                <i class="bi bi-gear-fill mr-3"></i> Settings
            </a>
        </li>
    </ul>
</div>

<!-- JS -->
<script>
    const menuBtn = document.getElementById("menuBtn");
    const sidebar = document.getElementById("sidebar");
    const userMenuBtn = document.getElementById("userMenuBtn");
    const userDropdown = document.getElementById("userDropdown");

    // Mobile menu toggle
    menuBtn.addEventListener("click", () => {
        sidebar.classList.toggle("-translate-x-full");
    });

    // User dropdown toggle
    userMenuBtn.addEventListener("click", () => {
        userDropdown.classList.toggle("hidden");
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", (e) => {
        if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add("hidden");
        }
    });
</script>
