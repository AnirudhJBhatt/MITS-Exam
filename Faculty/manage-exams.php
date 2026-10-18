<?php
/**
 * MITS Exam Portal - Faculty Examination Management
 * Master Exam Roster with Publishing Toggles, Paper Exports, Candidate Submissions, & Filtering
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

check_role('Faculty');

$fac_id = $_SESSION['LoginFaculty'] ?? $_SESSION['user_id'] ?? '';

$flashMessage = null;
$flashType = 'info';

// Handle POST actions (Toggle Status, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_status') {
        $exam_id = (int)($_POST['exam_id'] ?? 0);
        $new_status = trim($_POST['new_status'] ?? 'draft');
        if ($exam_id > 0 && in_array($new_status, ['draft', 'published', 'archived'])) {
            try {
                $stmt = $pdo->prepare("UPDATE exams SET Status = ? WHERE Exam_ID = ? AND Fac_ID = ?");
                $stmt->execute([$new_status, $exam_id, $fac_id]);
                $flashMessage = "Exam status updated to '{$new_status}'.";
                $flashType = 'success';
            } catch (Exception $e) {
                $flashMessage = "Status update failed: " . $e->getMessage();
                $flashType = 'danger';
            }
        }
    } elseif ($action === 'delete_exam') {
        $exam_id = (int)($_POST['exam_id'] ?? 0);
        if ($exam_id > 0) {
            try {
                $pdo->beginTransaction();
                // Check if any results already exist
                $chk = $pdo->prepare("SELECT COUNT(*) FROM results WHERE Exam_ID = ?");
                $chk->execute([$exam_id]);
                if ($chk->fetchColumn() > 0) {
                    $flashMessage = "Cannot delete exam: Student test submissions already exist.";
                    $flashType = 'warning';
                } else {
                    $delQ = $pdo->prepare("DELETE FROM exam_questions WHERE Exam_ID = ?");
                    $delQ->execute([$exam_id]);

                    $delE = $pdo->prepare("DELETE FROM exams WHERE Exam_ID = ? AND Fac_ID = ?");
                    $delE->execute([$exam_id, $fac_id]);

                    $pdo->commit();
                    $flashMessage = "Exam #{$exam_id} and its configured questions were removed.";
                    $flashType = 'success';
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $flashMessage = "Delete failed: " . $e->getMessage();
                $flashType = 'danger';
            }
        }
    }
}

// Fetch assigned courses for filter
$stmtCourses = $pdo->prepare("
    SELECT c.Course_ID, c.Course_Code, c.Course_Name, c.Semester, cm.Acad_Year 
    FROM course_mapping cm
    JOIN courses c ON cm.Course_ID = c.Course_ID
    WHERE cm.Fac_ID = ?
    ORDER BY c.Course_Name ASC
");
$stmtCourses->execute([$fac_id]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

// Filters
$filterCourse = $_GET['Course_ID'] ?? $_GET['course'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterSearch = trim($_GET['search'] ?? '');

$conditions = ["e.Fac_ID = ?"];
$params = [$fac_id];

if ($filterCourse !== '') {
    $conditions[] = "e.Course_ID = ?";
    $params[] = $filterCourse;
}
if ($filterStatus !== '') {
    $conditions[] = "e.Status = ?";
    $params[] = $filterStatus;
}
if ($filterSearch !== '') {
    $conditions[] = "(e.Exam_Name LIKE ? OR e.Exam_Name LIKE ?)";
    $params[] = "%{$filterSearch}%";
    $params[] = "%{$filterSearch}%";
}

$whereSql = "WHERE " . implode(" AND ", $conditions);

$query = "
    SELECT e.*, c.Course_Name, c.Course_Code, c.Semester,
           (SELECT COUNT(*) FROM exam_questions eq WHERE eq.Exam_ID = e.Exam_ID) AS questions_count,
           (SELECT COUNT(*) FROM results r WHERE r.Exam_ID = e.Exam_ID) AS submissions_count
    FROM exams e
    LEFT JOIN courses c ON e.Course_ID = c.Course_ID
    {$whereSql}
    ORDER BY e.Start_Time DESC, e.Exam_ID DESC
";

$stmtExams = $pdo->prepare($query);
$stmtExams->execute($params);
$exams = $stmtExams->fetchAll(PDO::FETCH_ASSOC);

include '../Common/header.php';
include '../Common/faculty-sidebar.php';
?>

<main>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--mits-primary);">Examinations & Assessment Manager</h4>
            <p class="text-muted mb-0 small">Schedule timed computer-based tests, publish windows, and download formatted question papers</p>
        </div>
        <div class="d-flex gap-2">
            <a href="question-bank.php" class="btn btn-outline-secondary">
                <i class="bi bi-folder-check me-1"></i> Question Bank
            </a>
            <a href="create-exam.php<?= $filterCourse ? '?Course_ID=' . urlencode($filterCourse) : '' ?>" class="btn btn-outline-secondary">
                <i class="bi bi-journal-plus me-1"></i> Create New Exam
            </a>
        </div>
    </div>

    <?php if ($flashMessage): ?>
        <div class="alert alert-<?= $flashType ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi <?= $flashType === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
            <?= e($flashMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Bar Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-muted">Filter by Course</label>
                    <select name="course" class="form-select" onchange="this.form.submit()">
                        <option value="">All Assigned Courses</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= e($c['Course_ID']) ?>" <?= $filterCourse == $c['Course_ID'] ? 'selected' : '' ?>>
                                <?= e($c['Course_Code'] ? $c['Course_Code'] . ' - ' : '') ?><?= e($c['Course_Name']) ?> (S<?= e($c['Semester']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted">Exam Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                        <option value="published" <?= $filterStatus === 'published' ? 'selected' : '' ?>>Published (Visible to Students)</option>
                        <option value="archived" <?= $filterStatus === 'archived' ? 'selected' : '' ?>>Archived</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted">Search Exam Title</label>
                    <input type="text" name="search" class="form-control" placeholder="Search title..." value="<?= e($filterSearch) ?>">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Examinations Directory Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-journal-text me-2"></i>Configured Assessments (<?= count($exams) ?>)
            </h6>
            <div class="input-group input-group-sm" style="max-width: 250px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="examFilterInput" class="form-control border-start-0" placeholder="Live search exams...">
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="examTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 50px;">#</th>
                            <th>Exam Title</th>
                            <th>Course</th>
                            <th>Testing Window</th>
                            <th class="text-center" style="width: 100px;">Duration</th>
                            <th class="text-center" style="width: 90px;">Questions</th>
                            <th class="text-center" style="width: 110px;">Submissions</th>
                            <th class="text-center" style="width: 110px;">Status</th>
                            <th class="text-end pe-3" style="width: 220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exams)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fs-2 d-block mb-2 text-secondary"></i>
                                    No examinations configured yet. Click "Create New Exam" to schedule an assessment.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($exams as $idx => $e): ?>
                                <tr>
                                    <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="fw-bold text-dark fs-6"><?= e($e['Exam_Name'] ?: $e['Exam_Name']) ?></div>
                                        <span class="badge bg-light text-secondary border font-monospace small">ID: <?= e($e['Exam_ID']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($e['Course_Name']) ?></div>
                                        <small class="text-muted"><?= e($e['Course_Code']) ?> &bull; S<?= e($e['Semester']) ?></small>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark">
                                            <i class="bi bi-clock me-1 text-primary"></i>
                                            <?= $e['Start_Time'] ? date('M d, Y h:i A', strtotime($e['Start_Time'])) : 'TBD' ?>
                                        </div>
                                        <small class="text-muted">
                                            to <?= $e['End_Time'] ? date('M d, Y h:i A', strtotime($e['End_Time'])) : 'TBD' ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">
                                            <?= (int)$e['Duration'] ?> mins
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info-emphasis px-2 py-1 rounded-pill">
                                            <?= (int)$e['questions_count'] ?> Qs
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="view-results.php?exam=<?= e($e['Exam_ID']) ?>" class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill text-decoration-none" title="View Candidate Roster">
                                            <?= (int)$e['submissions_count'] ?> candidates
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <?php if (($e['Status'] ?? '') === 'published'): ?>
                                            <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill">Published</span>
                                        <?php elseif (($e['Status'] ?? '') === 'archived'): ?>
                                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill">Archived</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis px-2 py-1 rounded-pill">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <!-- Edit Exam -->
                                        <a href="edit-exam.php?Exam_ID=<?= e($e['Exam_ID']) ?>&Course_ID=<?= e($e['Course_ID']) ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit Exam Questions & Config">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <!-- View Results -->
                                        <?php if ((int)($e['submissions_count'] ?? 0) > 0): ?>
                                            <a href="view-results.php?exam=<?= e($e['Exam_ID']) ?>" class="btn btn-sm btn-outline-success me-1" title="View Exam Results">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary me-1" disabled title="No Submissions Yet">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Toggle Status Form
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Toggle status for <?= e($e['Exam_Name'] ?: $e['Exam_Name']) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="exam_id" value="<?= e($e['Exam_ID']) ?>">
                                            <input type="hidden" name="new_status" value="<?= ($e['Status'] === 'published') ? 'draft' : 'published' ?>">
                                            <button type="submit" class="btn btn-sm <?= ($e['Status'] === 'published') ? 'btn-outline-warning' : 'btn-outline-success' ?> me-1" title="<?= ($e['Status'] === 'published') ? 'Unpublish to Draft' : 'Publish Live' ?>">
                                                <i class="bi <?= ($e['Status'] === 'published') ? 'bi-pause-circle' : 'bi-check-circle' ?>"></i>
                                            </button>
                                        </form> -->

                                        <!-- Download QP (Word DOCX) -->
                                        <a href="download-qp.php?Exam_ID=<?= e($e['Exam_ID']) ?>" class="btn btn-sm btn-outline-secondary me-1" title="Download Official Question Paper (Word DOCX)">
                                            <i class="bi bi-file-earmark-word"></i>
                                        </a>

                                        <!-- Delete Exam -->
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete exam <?= e($e['Exam_Name'] ?: $e['Exam_Name']) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_exam">
                                            <input type="hidden" name="exam_id" value="<?= e($e['Exam_ID']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Exam">
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
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('examFilterInput');
    const table = document.getElementById('examTable');
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
});
</script>

<?php include '../Common/footer.php'; ?>