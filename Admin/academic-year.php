<?php
/**
 * MITS Exam Portal - Admin Academic Year Management
 * Parameterized PDO queries, CSRF validation, and clean institutional UI
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

check_role('Admin');

$flashMessage = null;
$flashType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $acad_year = trim($_POST['Acad_Year'] ?? '');

        if ($acad_year === '') {
            $flashMessage = 'Academic Year title is required.';
            $flashType = 'warning';
        } else {
            try {
                $check = $pdo->prepare("SELECT AY_Name FROM academic_year WHERE AY_Name = ?");
                $check->execute([$acad_year]);
                if ($check->fetch()) {
                    $flashMessage = "Academic Year '{$acad_year}' already exists.";
                    $flashType = 'warning';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO academic_year (AY_Name) VALUES (?)");
                    $stmt->execute([$acad_year]);
                    $flashMessage = "Academic Year '{$acad_year}' added successfully.";
                    $flashType = 'success';
                }
            } catch (Exception $e) {
                $flashMessage = "Error adding Academic Year: " . $e->getMessage();
                $flashType = 'danger';
            }
        }
    } elseif ($action === 'sync_current') {
        $acad_year = trim($_POST['Acad_Year'] ?? '');
        if ($acad_year !== '') {
            try {
                $pdo->beginTransaction();
                $stmtFac = $pdo->prepare("UPDATE faculty SET Acad_year = ?");
                $stmtFac->execute([$acad_year]);
                $stmtStud = $pdo->prepare("UPDATE student SET Curr_AY = ?");
                $stmtStud->execute([$acad_year]);
                $pdo->commit();

                $flashMessage = "Active Academic Year updated to '{$acad_year}' for all faculty and students.";
                $flashType = 'success';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $flashMessage = "Sync failed: " . $e->getMessage();
                $flashType = 'danger';
            }
        }
    } elseif ($action === 'delete') {
        $ay_name = trim($_POST['AY_Name'] ?? '');
        if ($ay_name !== '') {
            try {
                $stmt = $pdo->prepare("DELETE FROM academic_year WHERE AY_Name = ?");
                $stmt->execute([$ay_name]);
                $flashMessage = "Academic Year '{$ay_name}' removed.";
                $flashType = 'success';
            } catch (Exception $e) {
                $flashMessage = "Delete failed: " . $e->getMessage();
                $flashType = 'danger';
            }
        }
    }
}

// Fetch all academic years
$yearsStmt = $pdo->query("
    SELECT ay.AY_Name,
           (SELECT COUNT(*) FROM student s WHERE s.Curr_AY = ay.AY_Name OR s.Stud_Year = ay.AY_Name) AS student_count,
           (SELECT COUNT(*) FROM faculty f WHERE f.Acad_year = ay.AY_Name) AS faculty_count,
           (SELECT COUNT(*) FROM exams e WHERE e.Acad_Year = ay.AY_Name) AS exam_count
    FROM academic_year ay
    ORDER BY ay.AY_Name DESC
");
$years = $yearsStmt->fetchAll(PDO::FETCH_ASSOC);

include '../Common/header.php';
include '../Common/admin-sidebar.php';
?>

<main>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--mits-primary);">Academic Calendar & Cycles</h4>
            <p class="text-muted mb-0 small">Define institutional academic years and synchronize batch enrollments</p>
        </div>
    </div>

    <?php if ($flashMessage): ?>
        <div class="alert alert-<?= $flashType ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi <?= $flashType === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
            <?= e($flashMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Add Academic Year Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-calendar-plus me-2"></i>Add Academic Year
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Academic Year Title <span class="text-danger">*</span></label>
                            <input type="text" name="Acad_Year" class="form-control" placeholder="e.g. 2024-2025" required pattern="^[0-9]{4}-[0-9]{4}$" title="Format: YYYY-YYYY (e.g. 2024-2025)">
                            <div class="form-text">Standard institutional format: <code>YYYY-YYYY</code></div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle me-1"></i> Add Academic Year
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Sync Notice -->
            <div class="card border-0 shadow-sm mt-3 bg-light">
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-info-circle me-1 text-primary"></i>Batch Sync Tip</h6>
                    <p class="small text-muted mb-0">
                        Use the <strong>"Set as Active"</strong> button on any academic year in the list to synchronize student and faculty records to the current semester cycle.
                    </p>
                </div>
            </div>
        </div>

        <!-- Academic Years Directory -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-calendar-range me-2"></i>Configured Academic Years (<?= count($years) ?>)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 50px;">#</th>
                                    <th>Academic Year</th>
                                    <th class="text-center">Associated Students</th>
                                    <th class="text-center">Associated Faculty</th>
                                    <th class="text-center">Exams Conducted</th>
                                    <th class="text-end pe-3" style="width: 220px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($years)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No Academic Years defined yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($years as $idx => $y): ?>
                                        <tr>
                                            <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                            <td class="fw-bold text-dark">
                                                <span class="badge bg-light text-dark border px-2 py-1 font-monospace fs-6">
                                                    <?= e($y['AY_Name']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-2 py-1">
                                                    <?= (int)$y['student_count'] ?> Students
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1">
                                                    <?= (int)$y['faculty_count'] ?> Faculty
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1">
                                                    <?= (int)$y['exam_count'] ?> Exams
                                                </span>
                                            </td>
                                            <td class="text-end pe-3">
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Set <?= e($y['AY_Name']) ?> as active cycle for all students and faculty?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="sync_current">
                                                    <input type="hidden" name="Acad_Year" value="<?= e($y['AY_Name']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success me-1" title="Set as Active Institutional Year">
                                                        <i class="bi bi-arrow-repeat"></i> Set Active
                                                    </button>
                                                </form>

                                                <form method="POST" class="d-inline" onsubmit="return confirm('Remove academic year <?= e($y['AY_Name']) ?>?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="AY_Name" value="<?= e($y['AY_Name']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Academic Year">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../Common/footer.php'; ?>