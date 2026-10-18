<?php
/**
 * MITS Exam Portal - Admin Department Management
 * Parameterized PDO queries, CSRF security, Bcrypt credential generation
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

check_role('Admin');

$flashMessage = null;
$flashType = 'info';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $dept_id = trim($_POST['Dept_ID'] ?? '');
        $dept_name = trim($_POST['Dept_Name'] ?? '');
        $dept_code = trim($_POST['Dept_Username'] ?? '');

        if ($dept_id === '' || $dept_name === '' || $dept_code === '') {
            $flashMessage = 'All department fields are required.';
            $flashType = 'warning';
        } else {
            try {
                // Check if Dept_ID or Dept_Code already exists
                $checkStmt = $pdo->prepare("SELECT Dept_ID FROM department WHERE Dept_ID = ? OR Dept_Code = ?");
                $checkStmt->execute([$dept_id, $dept_code]);
                if ($checkStmt->fetch()) {
                    $flashMessage = 'Department ID or Code/Username already exists.';
                    $flashType = 'warning';
                } else {
                    $pdo->beginTransaction();

                    $stmt1 = $pdo->prepare("INSERT INTO department (Dept_ID, Dept_Name, Dept_Code) VALUES (?, ?, ?)");
                    $stmt1->execute([$dept_id, $dept_name, $dept_code]);

                    // Default Bcrypt password for Department Admin: Dept@123
                    $defaultHash = password_hash('Dept@123', PASSWORD_BCRYPT);
                    $stmt2 = $pdo->prepare("INSERT INTO login (ID, User_ID, Password, Role, Status) VALUES (?, ?, ?, 'DeptAdmin', 'Activate')");
                    $stmt2->execute([$dept_id, $dept_code, $defaultHash]);

                    $pdo->commit();
                    $flashMessage = "Department '{$dept_name}' created successfully with default password (Dept@123).";
                    $flashType = 'success';
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $flashMessage = "Failed to add department: " . $e->getMessage();
                $flashType = 'danger';
            }
        }
    } elseif ($action === 'edit') {
        $dept_id = trim($_POST['Dept_ID'] ?? '');
        $dept_name = trim($_POST['Dept_Name'] ?? '');
        $dept_code = trim($_POST['Dept_Username'] ?? '');

        if ($dept_id === '' || $dept_name === '' || $dept_code === '') {
            $flashMessage = 'All fields are required for updating.';
            $flashType = 'warning';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE department SET Dept_Name = ?, Dept_Code = ? WHERE Dept_ID = ?");
                $stmt->execute([$dept_name, $dept_code, $dept_id]);

                // Also update User_ID in login
                $stmtLogin = $pdo->prepare("UPDATE login SET User_ID = ? WHERE ID = ? AND Role = 'DeptAdmin'");
                $stmtLogin->execute([$dept_code, $dept_id]);

                $pdo->commit();
                $flashMessage = "Department '{$dept_id}' updated successfully.";
                $flashType = 'success';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $flashMessage = "Error updating department: " . $e->getMessage();
                $flashType = 'danger';
            }
        }
    } elseif ($action === 'delete') {
        $dept_id = trim($_POST['Dept_ID'] ?? '');
        if ($dept_id !== '') {
            try {
                // Check if faculty or students are linked
                $cntFac = $pdo->prepare("SELECT COUNT(*) FROM faculty WHERE Fac_Dept = ?");
                $cntFac->execute([$dept_id]);
                $facultyCount = $cntFac->fetchColumn();

                $cntStud = $pdo->prepare("SELECT COUNT(*) FROM student WHERE Stud_Dept = ?");
                $cntStud->execute([$dept_id]);
                $studentCount = $cntStud->fetchColumn();

                if ($facultyCount > 0 || $studentCount > 0) {
                    $flashMessage = "Cannot delete department: {$facultyCount} faculty member(s) and {$studentCount} student(s) are assigned to it.";
                    $flashType = 'warning';
                } else {
                    $pdo->beginTransaction();
                    $delDept = $pdo->prepare("DELETE FROM department WHERE Dept_ID = ?");
                    $delDept->execute([$dept_id]);

                    $delLogin = $pdo->prepare("DELETE FROM login WHERE ID = ? AND Role = 'DeptAdmin'");
                    $delLogin->execute([$dept_id]);

                    $pdo->commit();
                    $flashMessage = "Department '{$dept_id}' deleted successfully.";
                    $flashType = 'success';
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $flashMessage = "Delete failed: " . $e->getMessage();
                $flashType = 'danger';
            }
        }
    }
}

// Fetch all departments with faculty and student counts
$deptStmt = $pdo->query("
    SELECT d.Dept_ID, d.Dept_Name, d.Dept_Code,
           (SELECT COUNT(*) FROM faculty f WHERE f.Fac_Dept = d.Dept_ID) AS faculty_count,
           (SELECT COUNT(*) FROM student s WHERE s.Stud_Dept = d.Dept_ID) AS student_count
    FROM department d
    ORDER BY d.Dept_Name ASC
");
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

include '../Common/header.php';
include '../Common/admin-sidebar.php';
?>

<main>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--mits-primary);">Manage Academic Departments</h4>
            <p class="text-muted mb-0 small">Create and maintain institutional departments and administrator credentials</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeptModal">
            <i class="bi bi-plus-circle me-1"></i> Add Department
        </button>
    </div>

    <?php if ($flashMessage): ?>
        <div class="alert alert-<?= $flashType ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi <?= $flashType === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
            <?= e($flashMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-building me-2"></i>Configured Departments (<?= count($departments) ?>)
            </h6>
            <div class="input-group" style="max-width: 280px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="deptSearch" class="form-control border-start-0" placeholder="Filter departments...">
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="deptTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 60px;">#</th>
                            <th style="width: 120px;">Dept ID</th>
                            <th>Department Name</th>
                            <th>Login Username / Code</th>
                            <th class="text-center" style="width: 140px;">Faculty</th>
                            <th class="text-center" style="width: 140px;">Students</th>
                            <th class="text-end pe-3" style="width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($departments)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No academic departments found. Click "Add Department" to create one.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($departments as $idx => $d): ?>
                                <tr>
                                    <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?= e($d['Dept_ID']) ?></span></td>
                                    <td class="fw-semibold text-dark"><?= e($d['Dept_Name']) ?></td>
                                    <td><code><?= e($d['Dept_Code']) ?></code></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill">
                                            <?= (int)$d['faculty_count'] ?> Faculty
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info-emphasis px-2 py-1 rounded-pill">
                                            <?= (int)$d['student_count'] ?> Students
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-sm btn-outline-primary me-1 edit-dept-btn" 
                                                data-id="<?= e($d['Dept_ID']) ?>"
                                                data-name="<?= e($d['Dept_Name']) ?>"
                                                data-code="<?= e($d['Dept_Code']) ?>">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-dept-btn"
                                                data-id="<?= e($d['Dept_ID']) ?>"
                                                data-name="<?= e($d['Dept_Name']) ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Add Department Modal -->
<div class="modal fade" id="addDeptModal" tabindex="-1" aria-labelledby="addDeptModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="addDeptModalLabel">Add New Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Department ID <span class="text-danger">*</span></label>
                        <input type="text" name="Dept_ID" class="form-control" placeholder="e.g. CSE, EEE, ME" required>
                        <div class="form-text">Unique uppercase code representing the department.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Department Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="Dept_Name" class="form-control" placeholder="e.g. Computer Science and Engineering" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Dept Admin Username <span class="text-danger">*</span></label>
                        <input type="text" name="Dept_Username" class="form-control" placeholder="e.g. cse_admin" required>
                        <div class="form-text">Credentials created with default password: <code>Dept@123</code></div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Create Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDeptModal" tabindex="-1" aria-labelledby="editDeptModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="Dept_ID" id="editDeptID">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="editDeptModalLabel">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Department ID</label>
                        <input type="text" id="editDeptIDDisplay" class="form-control bg-light" disabled>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Department Name <span class="text-danger">*</span></label>
                        <input type="text" name="Dept_Name" id="editDeptName" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Login Username / Code <span class="text-danger">*</span></label>
                        <input type="text" name="Dept_Username" id="editDeptCode" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form Hidden -->
<form id="deleteDeptForm" method="POST" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="Dept_ID" id="deleteDeptID">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Client-side search filter
    const searchInput = document.getElementById('deptSearch');
    const table = document.getElementById('deptTable');
    if (searchInput && table) {
        searchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // Edit modal handler
    const editModal = new bootstrap.Modal(document.getElementById('editDeptModal'));
    document.querySelectorAll('.edit-dept-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('editDeptID').value = this.dataset.id;
            document.getElementById('editDeptIDDisplay').value = this.dataset.id;
            document.getElementById('editDeptName').value = this.dataset.name;
            document.getElementById('editDeptCode').value = this.dataset.code;
            editModal.show();
        });
    });

    // Delete confirmation handler
    document.querySelectorAll('.delete-dept-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const deptId = this.dataset.id;
            const deptName = this.dataset.name;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Department?',
                    text: `Are you sure you want to delete ${deptName} (${deptId})? Linked faculty and student associations will be checked.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d1202d',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('deleteDeptID').value = deptId;
                        document.getElementById('deleteDeptForm').submit();
                    }
                });
            } else {
                if (confirm(`Delete ${deptName} (${deptId})?`)) {
                    document.getElementById('deleteDeptID').value = deptId;
                    document.getElementById('deleteDeptForm').submit();
                }
            }
        });
    });
});
</script>

<?php include '../Common/footer.php'; ?>
