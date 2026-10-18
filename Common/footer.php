    <!-- FOOTER -->
    <footer class="footer">
        <span>&copy; <?= date('Y') ?> Muthoot Institute of Technology & Science (MITS). All rights reserved.</span>
    </footer>

    <!-- SCRIPTS -->
    <!-- jQuery 3.7.1 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 5.3.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Unified Sidebar & Mobile Offcanvas Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');

            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (window.innerWidth < 992) {
                        // Mobile offcanvas drawer
                        sidebar.classList.toggle('show');
                        if (backdrop) backdrop.classList.toggle('active');
                    } else {
                        // Desktop collapsible sidebar
                        sidebar.classList.toggle('collapsed');
                        document.body.classList.toggle('sidebar-is-collapsed');
                    }
                });
            }

            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    if (sidebar) sidebar.classList.remove('show');
                    backdrop.classList.remove('active');
                });
            }

            // Window resize handler
            window.addEventListener('resize', function () {
                if (window.innerWidth >= 992 && sidebar) {
                    sidebar.classList.remove('show');
                    if (backdrop) backdrop.classList.remove('active');
                }
            });

            // Universal Toast Notification Function
            window.showToast = function(title, icon = 'success') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: icon,
                    title: title,
                    showConfirmButton: false,
                    timer: 3500,
                    timerProgressBar: true
                });
            };

            // Global Flash Messages Trigger
            <?php if (!empty($_SESSION['auth_flash_error'])): ?>
                showToast(<?= json_encode($_SESSION['auth_flash_error']) ?>, 'error');
                <?php unset($_SESSION['auth_flash_error']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['flash_success'])): ?>
                showToast(<?= json_encode($_SESSION['flash_success']) ?>, 'success');
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['flash_error'])): ?>
                showToast(<?= json_encode($_SESSION['flash_error']) ?>, 'error');
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>
