<?php
/**
 * MITS Exam Portal - Admin Faculty Management
 * Parameterized PDO queries, Bcrypt credential generation, Transactional CSV onboarding
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

check_role('Admin');

$flashMessage = null;
$flashType = 'info';
$previewRecords = [];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // Action 1: Upload CSV and Preview
    if ($action === 'preview_csv') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $tmpFile = $_FILES['csv_file']['tmp_name'];
            if (($handle = fopen($tmpFile, 'r')) !== false) {
                $isHeader = true;
                while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                    if ($isHeader) {
                        $isHeader = false;
                        continue;
                    }
                    if (count($row) >= 4 && !empty(trim($row[0]))) {
                        $previewRecords[] = [
                            'Fac_ID'    => trim($row[0]),
                            'Fac_Name'  => trim($row[1]),
                            'Fac_Email' => trim($row[2]),
                            'Fac_Dept'  => trim($row[3]),
                        ];
                    }
                }
                fclose($handle);
                if (empty($previewRecords)) {
                    $flashMessage = 'No valid faculty records found in the uploaded CSV.';
                    $flashType = 'warning';
                }
            }
        } else {
            $flashMessage = 'Please select a valid CSV file to upload.';
            $flashType = 'warning';
        }
    }

    // Action 2: Batch Commit CSV Records
    elseif ($action === 'commit_csv') {
        $jsonData = $_POST['json_data'] ?? '';
        $records = json_decode($jsonData, true);

        if (!empty($records) && is_array($records)) {
            $inserted = 0;
            $skipped = 0;
            $defaultHash = password_hash('Faculty123*', PASSWORD_BCRYPT);

            try {
                $pdo->beginTransaction();

                $stmtFac = $pdo->prepare("
                    INSERT INTO faculty (Fac_ID, Fac_Name, Fac_Email, Fac_Dept)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE Fac_Name = VALUES(Fac_Name), Fac_Email = VALUES(Fac_Email), Fac_Dept = VALUES(Fac_Dept)
                ");

                $stmtLogin = $pdo->prepare("
                    INSERT INTO login (ID, User_ID, Password, Role, Status)
                    VALUES (?, ?, ?, 'Faculty', 'Activate')
                    ON DUPLICATE KEY UPDATE User_ID = VALUES(User_ID)
                ");

                foreach ($records as $r) {
                    $facId = trim($r['Fac_ID'] ?? '');
                    $facName = trim($r['Fac_Name'] ?? '');
                    $facEmail = trim($r['Fac_Email'] ?? '');
                    $facDept = trim($r['Fac_Dept'] ?? '');

                    if ($facId && $facName && $facEmail) {
                        $stmtFac->execute([$facId, $facName, $facEmail, $facDept]);
                        $stmtLogin->execute([$facId, $facEmail, $defaultHash]);
                        $inserted++;
                    } else {
                        $skipped++;
                    }
                }

                $pdo->commit();
                $flashMessage = "Successfully imported/updated {$inserted} faculty member(s). Default password: Faculty123*";
                $flashType = 'success';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $flashMessage = "Import failed: " . $e->getMessage();
                $flashType = 'danger';
            }
        }
    }

    // Action 3: Add Single Faculty
    elseif ($action === 'add_single') {
        $facId = trim($_POST['Fac_ID'] ?? '');
        $facName = trim($_POST['Fac_Name'] ?? '');
        $facEmail = trim($_POST['Fac_Email'] ?? '');
        $facDept = trim($_POST['Fac_Dept'] ?? '');

        if (!$facId || !$facName || !$facEmail || !$facDept) {
            $flashMessage = 'All faculty fields including department are required.';
            $flashType = 'warning';
        } else {
            try {
                // Check duplicate
                $chk = $pdo->prepare("SELECT Fac_ID FROM faculty WHERE Fac_ID = ? OR Fac_Email = ?");
                $chk->execute([$facId, $facEmail]);
                if ($chk->fetch()) {
                    $flashMessage = "Faculty ID or Email already registered in system.";
                    $flashType = 'warning';
                } else {
                    $pdo->beginTransaction();
                    $stmtFac = $pdo->prepare("INSERT INTO faculty (Fac_ID, Fac_Name, Fac_Email, Fac_Dept) VALUES (?, ?, ?, ?)");
                    $stmtFac->execute([$facId, $facName, $facEmail, $facDept]);

                    $defaultHash = password_hash('Faculty123*', PASSWORD_BCRYPT);
                    $stmtLogin = $pdo->prepare("INSERT INTO login (ID, User_ID, Password, Role, Status) VALUES (?, ?, ?, 'Faculty', 'Activate')");
                    $stmtLogin->execute([$facId, $facEmail, $defaultHash]);

                    $pdo->commit();
                    $flashMessage = "Faculty member '{$facName}' registered successfully. Default password: Faculty123*";
                    $flashType = 'success';
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $flashMessage = "Error registering faculty: " . $e->getMessage();
                $flashType = 'danger';
            }
        }
    }

    // Action 4: Edit Faculty
    elseif ($action === 'edit_faculty') {
        $facId = trim($_POST['Fac_ID'] ?? '');
        $facName = trim($_POST['Fac_Name'] ?? '');
        $facEmail = trim($_POST['Fac_Email'] ?? '');
        $facDept = trim($_POST['Fac_Dept'] ?? '');

        if (!$facId || !$facName || !$facEmail || !$facDept) {
            $flashMessage = 'All fields are required to update faculty.';
            $flashType = 'warning';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE faculty SET Fac_Name = ?, Fac_Email = ?, Fac_Dept = ? WHERE Fac_ID = ?");
                $stmt->execute([$facName, $facEmail, $facDept, $facId]);

                $stmtLogin = $pdo->prepare("UPDATE login SET User_ID = ? WHERE ID = ? AND Role = 'Faculty'");
                $stmtLogin->execute([$facEmail, $facId]);

                $pdo->commit();
                $flashMessage = "Faculty '{$facName}' updated successfully.";
                $flashType = 'success';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $flashMessage = "Error updating faculty: " . $e->getMessage();
                $flashType = 'danger';
            }
        }
    }

    // Action 5: Delete Faculty
    elseif ($action === 'delete_faculty') {
        $facId = trim($_POST['Fac_ID'] ?? '');
        if ($facId) {
            try {
                // Check if faculty has exams or course mappings
                $chkMap = $pdo->prepare("SELECT COUNT(*) FROM course_mapping WHERE Fac_ID = ?");
                $chkMap->execute([$facId]);
                $mapCount = $chkMap->fetchColumn();

                if ($mapCount > 0) {
                    $flashMessage = "Cannot delete faculty: Faculty is assigned to {$mapCount} course(s). Reassign courses first.";
                    $flashType = 'warning';
                } else {
                    $pdo->beginTransaction();
                    $pdo->prepare("DELETE FROM faculty WHERE Fac_ID = ?")->execute([$facId]);
                    $pdo->prepare("DELETE FROM login WHERE ID = ? AND Role = 'Faculty'")->execute([$facId]);
                    $pdo->commit();

                    $flashMessage = "Faculty record '{$facId}' deleted successfully.";
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

// Fetch all departments for dropdowns
$deptList = $pdo->query("SELECT Dept_ID, Dept_Name FROM department ORDER BY Dept_Name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Department filter
$selectedDept = $_GET['dept'] ?? '';
$whereClause = '';
$params = [];
if ($selectedDept !== '') {
    $whereClause = "WHERE f.Fac_Dept = ?";
    $params[] = $selectedDept;
}

// Fetch faculty list
$query = "
    SELECT f.Fac_ID, f.Fac_Name, f.Fac_Email, f.Fac_Dept, d.Dept_Name,
           (SELECT COUNT(*) FROM course_mapping cm WHERE cm.Fac_ID = f.Fac_ID) AS course_count
    FROM faculty f
    LEFT JOIN department d ON f.Fac_Dept = d.Dept_ID
    {$whereClause}
    ORDER BY f.Fac_Name ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$faculties = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../Common/header.php';
include '../Common/admin-sidebar.php';
?>

<main>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--mits-primary);">Manage Institution Faculty</h4>
            <p class="text-muted mb-0 small">Onboard teaching staff, manage departmental assignments, and configure credentials</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#csvUploadSection">
                <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                <i class="bi bi-person-plus me-1"></i> Add Faculty
            </button>
        </div>
    </div>

    <?php if ($flashMessage): ?>
        <div class="alert alert-<?= $flashType ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi <?= $flashType === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
            <?= e($flashMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Collapsible CSV Upload & Preview Section -->
    <div class="collapse <?= !empty($previewRecords) ? 'show' : '' ?> mb-4" id="csvUploadSection">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-upload me-2"></i>Batch Onboard Faculty via CSV
                </h6>
                <a href="../Templates/Faculty_Template.csv" download class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-download me-1"></i> Download CSV Template
                </a>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="preview_csv">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Select CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <div class="form-text">Headers expected: <code>Fac_ID, Fac_Name, Fac_Email, Fac_Dept</code></div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-eye me-1"></i> Preview & Validate CSV
                        </button>
                    </div>
                </form>

                <?php if (!empty($previewRecords)): ?>
                    <div class="border rounded p-3 bg-light mt-3">
                        <h6 class="fw-bold text-dark mb-2">CSV Staging Preview (<?= count($previewRecords) ?> rows)</h6>
                        <div class="table-responsive" style="max-height: 250px;">
                            <table class="table table-sm table-bordered bg-white mb-0 text-center">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Faculty ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Dept ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($previewRecords as $idx => $r): ?>
                                        <tr>
                                            <td><?= $idx + 1 ?></td>
                                            <td><code><?= e($r['Fac_ID']) ?></code></td>
                                            <td><?= e($r['Fac_Name']) ?></td>
                                            <td><?= e($r['Fac_Email']) ?></td>
                                            <td><span class="badge bg-secondary"><?= e($r['Fac_Dept']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <form method="POST" class="mt-3 text-end">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="commit_csv">
                            <input type="hidden" name="json_data" value='<?= json_encode($previewRecords, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                            <button type="submit" class="btn btn-success px-4">
                                <i class="bi bi-check-circle me-1"></i> Commit & Create Accounts
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Faculty Directory Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-people me-2"></i>Faculty Registry (<?= count($faculties) ?>)
            </h6>
            <div class="d-flex flex-wrap gap-2">
                <!-- Department Filter Dropdown -->
                <form method="GET" class="d-flex align-items-center gap-1">
                    <select name="dept" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php foreach ($deptList as $d): ?>
                            <option value="<?= e($d['Dept_ID']) ?>" <?= $selectedDept === $d['Dept_ID'] ? 'selected' : '' ?>>
                                <?= e($d['Dept_Name']) ?> (<?= e($d['Dept_ID']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <!-- Search Filter Input -->
                <div class="input-group input-group-sm" style="max-width: 250px;">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="facultySearch" class="form-control border-start-0" placeholder="Filter faculty...">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="facultyTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 50px;">#</th>
                            <th style="width: 140px;">Faculty ID</th>
                            <th>Faculty Name</th>
                            <th>Email (Username)</th>
                            <th>Department</th>
                            <th class="text-center" style="width: 130px;">Courses</th>
                            <th class="text-end pe-3" style="width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($faculties)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No faculty members found for the selected filter.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($faculties as $idx => $f): ?>
                                <tr>
                                    <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?= e($f['Fac_ID']) ?></span></td>
                                    <td class="fw-semibold text-dark"><?= e($f['Fac_Name']) ?></td>
                                    <td><a href="mailto:<?= e($f['Fac_Email']) ?>" class="text-decoration-none"><?= e($f['Fac_Email']) ?></a></td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <?= e($f['Dept_Name'] ?: $f['Fac_Dept']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info-emphasis px-2 py-1 rounded-pill">
                                            <?= (int)$f['course_count'] ?> Courses
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-sm btn-outline-primary me-1 edit-fac-btn"
                                                data-id="<?= e($f['Fac_ID']) ?>"
                                                data-name="<?= e($f['Fac_Name']) ?>"
                                                data-email="<?= e($f['Fac_Email']) ?>"
                                                data-dept="<?= e($f['Fac_Dept']) ?>">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-fac-btn"
                                                data-id="<?= e($f['Fac_ID']) ?>"
                                                data-name="<?= e($f['Fac_Name']) ?>">
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

<!-- Add Faculty Modal -->
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-labelledby="addFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_single">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="addFacultyModalLabel">Add Faculty Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Faculty ID <span class="text-danger">*</span></label>
                        <input type="text" name="Fac_ID" class="form-control" placeholder="e.g. FAC101" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="Fac_Name" class="form-control" placeholder="e.g. Dr. Rajesh Kumar" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="Fac_Email" class="form-control" placeholder="e.g. rajesh@mits.ac.in" required>
                        <div class="form-text">Will serve as login username with default password: <code>Faculty123*</code></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                        <select name="Fac_Dept" class="form-select" required>
                            <option value="">Select Department</option>
                            <?php foreach ($deptList as $d): ?>
                                <option value="<?= e($d['Dept_ID']) ?>"><?= e($d['Dept_Name']) ?> (<?= e($d['Dept_ID']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-check me-1"></i> Register Faculty
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Faculty Modal -->
<div class="modal fade" id="editFacultyModal" tabindex="-1" aria-labelledby="editFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="edit_faculty">
                <input type="hidden" name="Fac_ID" id="editFacID">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="editFacultyModalLabel">Edit Faculty Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Faculty ID</label>
                        <input type="text" id="editFacIDDisplay" class="form-control bg-light" disabled>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="Fac_Name" id="editFacName" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="Fac_Email" id="editFacEmail" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                        <select name="Fac_Dept" id="editFacDept" class="form-select" required>
                            <?php foreach ($deptList as $d): ?>
                                <option value="<?= e($d['Dept_ID']) ?>"><?= e($d['Dept_Name']) ?> (<?= e($d['Dept_ID']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
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

<!-- Hidden Delete Form -->
<form id="deleteFacultyForm" method="POST" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete_faculty">
    <input type="hidden" name="Fac_ID" id="deleteFacID">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Client-side search filter
    const searchInput = document.getElementById('facultySearch');
    const table = document.getElementById('facultyTable');
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
    const editModal = new bootstrap.Modal(document.getElementById('editFacultyModal'));
    document.querySelectorAll('.edit-fac-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('editFacID').value = this.dataset.id;
            document.getElementById('editFacIDDisplay').value = this.dataset.id;
            document.getElementById('editFacName').value = this.dataset.name;
            document.getElementById('editFacEmail').value = this.dataset.email;
            document.getElementById('editFacDept').value = this.dataset.dept;
            editModal.show();
        });
    });

    // Delete confirmation handler
    document.querySelectorAll('.delete-fac-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const facId = this.dataset.id;
            const facName = this.dataset.name;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Faculty?',
                    text: `Remove faculty member ${facName} (${facId}) and associated login account?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d1202d',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('deleteFacID').value = facId;
                        document.getElementById('deleteFacultyForm').submit();
                    }
                });
            } else {
                if (confirm(`Delete faculty ${facName} (${facId})?`)) {
                    document.getElementById('deleteFacID').value = facId;
                    document.getElementById('deleteFacultyForm').submit();
                }
            }
        });
    });
});
</script>

<?php include '../Common/footer.php'; ?>