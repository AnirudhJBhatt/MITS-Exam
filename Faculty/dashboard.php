<?php
/**
 * MITS Exam Portal - Faculty Dashboard
 * Live aggregate statistics, quick action shortcuts, active exams, and assigned courses
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

check_role('Faculty');

$fac_id = $_SESSION['LoginFaculty'] ?? $_SESSION['user_id'] ?? '';

// Fetch Faculty Profile
$stmtFac = $pdo->prepare("
    SELECT f.*, d.Dept_Name 
    FROM faculty f
    LEFT JOIN department d ON f.Fac_Dept = d.Dept_ID
    WHERE f.Fac_ID = ? LIMIT 1
");
$stmtFac->execute([$fac_id]);
$faculty = $stmtFac->fetch(PDO::FETCH_ASSOC);

if (!$faculty) {
    // Fallback if ID mismatch
    $faculty = [
        'Fac_ID' => $fac_id,
        'Fac_Name' => 'Faculty Member',
        'Fac_Dept' => 'Academic Dept',
        'Dept_Name' => 'Academic Department'
    ];
}

// Live Metrics
// 1. Assigned Courses Count
$stmtCourses = $pdo->prepare("SELECT COUNT(*) FROM course_mapping WHERE Fac_ID = ?");
$stmtCourses->execute([$fac_id]);
$courseCount = (int)$stmtCourses->fetchColumn();

// 2. Question Bank Count
$stmtQB = $pdo->prepare("SELECT COUNT(*) FROM  new_question_bank WHERE Fac_ID = ?");
$stmtQB->execute([$fac_id]);
$questionCount = (int)$stmtQB->fetchColumn();

// 3. Exams Created Count
$stmtExams = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE Fac_ID = ?");
$stmtExams->execute([$fac_id]);
$examCount = (int)$stmtExams->fetchColumn();

// 4. Pending Review Submissions
$stmtPending = $pdo->prepare("
    SELECT COUNT(*) 
    FROM results r 
    JOIN exams e ON r.Exam_ID = e.Exam_ID 
    WHERE e.Fac_ID = ? AND r.Status = 'pending_review'
");
$stmtPending->execute([$fac_id]);
$pendingCount = (int)$stmtPending->fetchColumn();

// Fetch Active / Recent Exams
$stmtRecentExams = $pdo->prepare("
    SELECT e.*, c.Course_Name, c.Course_Code,
           (SELECT COUNT(*) FROM results r WHERE r.Exam_ID = e.Exam_ID) AS submission_count
    FROM exams e
    LEFT JOIN courses c ON e.Course_ID = c.Course_ID
    WHERE e.Fac_ID = ?
    ORDER BY e.Start_Time DESC
    LIMIT 5
");
$stmtRecentExams->execute([$fac_id]);
$recentExams = $stmtRecentExams->fetchAll(PDO::FETCH_ASSOC);

// Fetch Assigned Courses List
$stmtMyCourses = $pdo->prepare("
    SELECT cm.Map_ID, cm.Acad_Year, c.Course_ID, c.Course_Code, c.Course_Name, c.Semester,
           (SELECT COUNT(*) FROM student_course_mapping scm WHERE scm.Course_ID = c.Course_ID) AS enrolled_students
    FROM course_mapping cm
    JOIN courses c ON cm.Course_ID = c.Course_ID
    WHERE cm.Fac_ID = ?
    ORDER BY c.Semester ASC, c.Course_Name ASC
");
$stmtMyCourses->execute([$fac_id]);
$myCourses = $stmtMyCourses->fetchAll(PDO::FETCH_ASSOC);

include '../Common/header.php';
include '../Common/faculty-sidebar.php';
?>

<main>
    <!-- Welcome Header Banner -->
    <div class="card border-0 shadow-sm mb-4 bg-white overflow-hidden">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill mb-2 fw-semibold">
                    <i class="bi bi-mortarboard-fill me-1"></i> Faculty Portal
                </span>
                <h3 class="fw-bold mb-1 text-dark">Welcome back, <?= e($faculty['Fac_Name']) ?></h3>
                <p class="text-muted mb-0 small">
                    Department: <strong><?= e($faculty['Dept_Name'] ?: $faculty['Fac_Dept']) ?></strong> | Faculty ID: <code><?= e($faculty['Fac_ID']) ?></code>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="add-questions.php" class="btn btn-outline-primary">
                    <i class="bi bi-plus-circle me-1"></i> New Question
                </a>
                <a href="create-exam.php" class="btn btn-primary">
                    <i class="bi bi-journal-plus me-1"></i> Create Exam
                </a>
            </div>
        </div>
    </div>

    <!-- Live KPI Metrics Grid -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 me-3">
                        <i class="bi bi-book-half fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Assigned Courses</span>
                        <h4 class="fw-bold mb-0"><?= $courseCount ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success-subtle text-success p-3 me-3">
                        <i class="bi bi-folder-check fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Questions in Bank</span>
                        <h4 class="fw-bold mb-0"><?= $questionCount ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-info-subtle text-info-emphasis p-3 me-3">
                        <i class="bi bi-journal-text fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Assessments Built</span>
                        <h4 class="fw-bold mb-0"><?= $examCount ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning-subtle text-warning-emphasis p-3 me-3">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Pending Grading</span>
                        <h4 class="fw-bold mb-0"><?= $pendingCount ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Examinations -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-clock-history me-2"></i>My Recent Examinations
                    </h6>
                    <a href="manage-exams.php" class="text-decoration-none text-white small fw-semibold">View All <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Exam Title</th>
                                    <th>Course</th>
                                    <th>Schedule Window</th>
                                    <th class="text-center">Submissions</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentExams)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            No assessments created yet. Click "Create Exam" to schedule your first test.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentExams as $e): ?>
                                        <tr>
                                            <td class="ps-3 fw-semibold text-dark">
                                                <?= e($e['Exam_Name'] ?: $e['Exam_Name']) ?>
                                            </td>
                                            <td>
                                                <div class="small fw-semibold"><?= e($e['Course_Name']) ?></div>
                                                <small class="text-muted"><?= e($e['Course_Code'] ?: $e['Course_ID']) ?></small>
                                            </td>
                                            <td>
                                                <div class="small text-dark">
                                                    <?= $e['Start_Time'] ? date('M d, Y h:i A', strtotime($e['Start_Time'])) : 'TBD' ?>
                                                </div>
                                                <small class="text-muted"><?= (int)$e['Duration'] ?> mins</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill">
                                                    <?= (int)$e['submission_count'] ?> candidates
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if (($e['Status'] ?? '') === 'published'): ?>
                                                    <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill">Published</span>
                                                <?php elseif (($e['Status'] ?? '') === 'completed'): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill">Completed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis px-2 py-1 rounded-pill">Draft</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-3">
                                                <a href="view-results.php?exam=<?= e($e['Exam_ID']) ?>" class="btn btn-sm btn-outline-primary" title="View Results & Grading">
                                                    <i class="bi bi-bar-chart"></i>
                                                </a>
                                                <a href="download-qp.php?exam_id=<?= e($e['Exam_ID']) ?>" class="btn btn-sm btn-outline-secondary" title="Export Question Paper">
                                                    <i class="bi bi-file-earmark-arrow-down"></i>
                                                </a>
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

        <!-- Assigned Courses & Quick Shortcuts -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-book me-2"></i>Assigned Courses
                    </h6>
                    <a href="view-courses.php" class="text-decoration-none text-white small fw-semibold">All Courses</a>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($myCourses)): ?>
                        <p class="text-muted small text-center my-3">No courses allocated to you yet. Contact your Department Admin.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($myCourses as $mc): ?>
                                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center border-bottom">
                                    <div>
                                        <div class="fw-semibold text-dark small"><?= e($mc['Course_Name']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <?= e($mc['Course_Code']) ?> &bull; Semester S<?= e($mc['Semester']) ?> &bull; <?= e($mc['Acad_Year']) ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-dark border font-monospace small">
                                        <?= (int)$mc['enrolled_students'] ?> studs
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Action Hub Card -->
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body p-3">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-lightning-charge me-1 text-primary"></i>Quick Actions</h6>
                    <div class="d-grid gap-2">
                        <a href="question-bank.php" class="btn btn-white bg-white border text-start shadow-xs">
                            <i class="bi bi-folder2-open me-2"></i> Browse Question Bank
                        </a>
                        <a href="add-questions.php" class="btn btn-white bg-white border text-start shadow-xs">
                            <i class="bi bi-calculator me-2"></i> Create MathLive Equation
                        </a>
                        <a href="manage-student.php" class="btn btn-white bg-white border text-start shadow-xs">
                            <i class="bi bi-people me-2"></i> View Enrolled Students
                        </a>
                        <a href="download-qp.php" class="btn btn-white bg-white border text-start shadow-xs">
                            <i class="bi bi-file-earmark-pdf me-2"></i> Download Question Paper (PDF/Word)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../Common/footer.php'; ?>