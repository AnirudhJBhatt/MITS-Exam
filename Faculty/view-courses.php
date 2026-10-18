<?php
/**
 * MITS Exam Portal - Faculty Assigned Courses
 * Parameterized queries, curriculum overview, enrolled student counts, & quick navigation hubs
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

check_role('Faculty');

$fac_id = $_SESSION['LoginFaculty'] ?? $_SESSION['user_id'] ?? '';

// Fetch Academic Years for filter
$ayStmt = $pdo->prepare("SELECT DISTINCT Acad_Year FROM course_mapping WHERE Fac_ID = ? ORDER BY Acad_Year DESC");
$ayStmt->execute([$fac_id]);
$academicYears = $ayStmt->fetchAll(PDO::FETCH_COLUMN);

$selectedYear = $_GET['year'] ?? ($academicYears[0] ?? '');
$selectedSem  = $_GET['sem'] ?? '';

// Build parameterized query for courses
$conditions = ["cm.Fac_ID = ?"];
$params = [$fac_id];

if ($selectedYear !== '') {
    $conditions[] = "cm.Acad_Year = ?";
    $params[] = $selectedYear;
}
if ($selectedSem !== '') {
    $conditions[] = "c.Semester = ?";
    $params[] = $selectedSem;
}

$whereSql = "WHERE " . implode(" AND ", $conditions);

$query = "
    SELECT cm.Map_ID, cm.Acad_Year, c.Course_ID, c.Course_Code, c.Course_Name, c.Semester, c.Credits, p.Prog_Name,
           (SELECT COUNT(*) FROM student_course_mapping scm WHERE scm.Course_ID = c.Course_ID AND scm.Acad_Year = cm.Acad_Year) AS enrolled_students,
           (SELECT COUNT(*) FROM exams e WHERE e.Course_ID = c.Course_ID AND e.Fac_ID = cm.Fac_ID) AS exams_count,
           (SELECT COUNT(*) FROM new_question_bank qb WHERE qb.Course_ID = c.Course_ID AND qb.Fac_ID = cm.Fac_ID) AS questions_count
    FROM course_mapping cm
    JOIN courses c ON cm.Course_ID = c.Course_ID
    LEFT JOIN programmes p ON c.Prog_ID = p.Prog_ID
    {$whereSql}
    ORDER BY c.Semester ASC, c.Course_Name ASC
";

$stmtCourses = $pdo->prepare($query);
$stmtCourses->execute($params);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

include '../Common/header.php';
include '../Common/faculty-sidebar.php';
?>

<main>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="bi bi-book-half text-danger me-2"></i>My Allocated Courses
            </h3>
        </div>
        <div class="d-flex gap-2">
            <a href="create-exam.php" class="btn btn-primary">
                <i class="bi bi-journal-plus me-1"></i> Create Exam
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-semibold small text-muted">Academic Year</label>
                    <select name="year" class="form-select" onchange="this.form.submit()">
                        <option value="">All Academic Years</option>
                        <?php foreach ($academicYears as $ay): ?>
                            <option value="<?= e($ay) ?>" <?= $selectedYear === $ay ? 'selected' : '' ?>>
                                Academic Year <?= e($ay) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-muted">Semester</label>
                    <select name="sem" class="form-select" onchange="this.form.submit()">
                        <option value="">All Semesters</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>" <?= $selectedSem == $i ? 'selected' : '' ?>>Semester S<?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <?php if ($selectedSem !== ''): ?>
                        <a href="view-courses.php?year=<?= urlencode($selectedYear) ?>" class="btn btn-outline-danger w-100">
                            <i class="bi bi-x-circle me-1"></i> Reset Semester
                        </a>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i> Filter Courses
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Course Cards Grid -->
    <?php if (empty($courses)): ?>
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-book-half fs-1 text-muted d-block mb-3"></i>
                <h5 class="fw-bold text-dark">No Courses Found</h5>
                <p class="text-muted small mb-0">No course mappings were found for the selected Academic Year.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($courses as $c): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm h-100 d-flex flex-column">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <span class="badge bg-light text-dark border font-monospace"><?= e($c['Course_Code']) ?></span>
                            <span class="badge bg-danger-subtle text-danger">Semester S<?= e($c['Semester']) ?></span>
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                            <h5 class="fw-bold text-dark mb-1"><?= e($c['Course_Name']) ?></h5>
                            <p class="text-muted small mb-3"><?= e($c['Prog_Name'] ?: 'Engineering Programme') ?> &bull; <?= e($c['Credits']) ?> Credits</p>

                            <div class="row g-2 mb-4 mt-auto">
                                <div class="col-4">
                                    <div class="p-2 border rounded text-center bg-light">
                                        <div class="fw-bold text-dark"><?= (int)$c['enrolled_students'] ?></div>
                                        <small class="text-muted" style="font-size: 0.7rem;">Students</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 border rounded text-center bg-light">
                                        <div class="fw-bold text-dark"><?= (int)$c['questions_count'] ?></div>
                                        <small class="text-muted" style="font-size: 0.7rem;">Questions</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 border rounded text-center bg-light">
                                        <div class="fw-bold text-dark"><?= (int)$c['exams_count'] ?></div>
                                        <small class="text-muted" style="font-size: 0.7rem;">Exams</small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="manage-exams.php?Course_ID=<?= e($c['Course_ID']) ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-journal-text me-1"></i> Manage Assessments
                                </a>
                                <div class="btn-group btn-group-sm">
                                    <a href="question-bank.php?course=<?= e($c['Course_ID']) ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-folder me-1"></i> Question Bank
                                    </a>
                                    <a href="create-exam.php?Course_ID=<?= e($c['Course_ID']) ?>" class="btn btn-outline-primary">
                                        <i class="bi bi-plus-circle me-1"></i> New Exam
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include '../Common/footer.php'; ?>