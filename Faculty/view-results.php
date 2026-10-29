<?php
/**
 * MITS Assessment Portal - Faculty Evaluation & Results Workbench
 * Features:
 * - Parameterized PDO queries with Course & Exam cascade selectors
 * - Executive Class Aggregate KPIs (Cohort size, attendance, class average, pass rate)
 * - NBA / NAAC Course Outcome (CO1-CO5) Attainment Analytics Matrix
 * - Candidate Itemized Roster with proctor telemetry (Violations, Auto-submitted)
 * - Integrated Rubric Grading Workbench & Itemized Scorecard modals
 * - Instant Publish/Unpublish Results toggle & Official CO CSV Export
 */
session_start();
require_once __DIR__ . '/../Connection/connection.php';
require_once __DIR__ . '/../Common/auth.php';
require_once __DIR__ . '/../Common/csrf.php';

check_role(['Faculty', 'Admin', 'DeptAdmin']);

$fac_id = $_SESSION['LoginFaculty'] ?? $_SESSION['user_id'] ?? '';

// 1. Fetch Faculty Profile & Active Academic Year
$fStmt = $pdo->prepare("SELECT Fac_Name, Fac_Dept, Acad_Year FROM faculty WHERE Fac_ID = ? LIMIT 1");
$fStmt->execute([$fac_id]);
$faculty = $fStmt->fetch();
$activeAY = $faculty['Acad_Year'] ?? '';

if (empty($activeAY)) {
    $ayStmt = $pdo->query("SELECT AY_Name FROM academic_year WHERE Status = 1 OR Is_Active = 1 LIMIT 1");
    $activeAY = $ayStmt->fetchColumn() ?: '';
}

// 2. Resolve Filter Inputs (Supports GET from manage-exams.php or POST from form)
$selectedAY = $_POST['Acad_Year'] ?? $_GET['Acad_Year'] ?? $activeAY;
$selectedCourse = (int)($_POST['Course_ID'] ?? $_GET['Course_ID'] ?? 0);
$selectedExam = (int)($_POST['Exam_ID'] ?? $_GET['exam'] ?? $_GET['Exam_ID'] ?? 0);

// If Exam is passed directly via GET (?exam=123), automatically resolve its Course and Academic Year
if ($selectedExam > 0 && ($selectedCourse === 0 || empty($selectedAY))) {
    $eMetaStmt = $pdo->prepare("SELECT Course_ID, Acad_Year FROM exams WHERE Exam_ID = ? LIMIT 1");
    $eMetaStmt->execute([$selectedExam]);
    $eMeta = $eMetaStmt->fetch();
    if ($eMeta) {
        $selectedCourse = (int)$eMeta['Course_ID'];
        if (empty($selectedAY)) $selectedAY = $eMeta['Acad_Year'];
    }
}

// 3. Fetch Academic Years list
$ayList = $pdo->query("SELECT AY_Name FROM academic_year ORDER BY AY_Name DESC")->fetchAll(PDO::FETCH_COLUMN);

// 4. Fetch Courses allocated to this faculty
$coursesStmt = $pdo->prepare("
    SELECT DISTINCT c.Course_ID, c.Course_Code, c.Course_Name, c.Semester
    FROM course_mapping cm
    JOIN courses c ON cm.Course_ID = c.Course_ID
    WHERE cm.Fac_ID = ? AND (? = '' OR cm.Acad_Year = ?)
    ORDER BY c.Course_Code ASC
");
$coursesStmt->execute([$fac_id, $selectedAY, $selectedAY]);
$assignedCourses = $coursesStmt->fetchAll();

// If no course explicitly selected but courses exist, select the first
if ($selectedCourse === 0 && !empty($assignedCourses)) {
    $selectedCourse = (int)$assignedCourses[0]['Course_ID'];
}

// 5. Fetch Exams configured for this course and academic year
$examsList = [];
if ($selectedCourse > 0) {
    $exStmt = $pdo->prepare("
        SELECT Exam_ID, Exam_Name, Total_Marks, Duration, Status, Result_Published, Start_Time
        FROM exams 
        WHERE Course_ID = ? AND Fac_ID = ? AND (? = '' OR Acad_Year = ?)
        ORDER BY Exam_ID DESC
    ");
    $exStmt->execute([$selectedCourse, $fac_id, $selectedAY, $selectedAY]);
    $examsList = $exStmt->fetchAll();

    // If no exam explicitly selected, default to the most recent one
    if ($selectedExam === 0 && !empty($examsList)) {
        $selectedExam = (int)$examsList[0]['Exam_ID'];
    }
}

// 6. Fetch Active Exam Information
$examInfo = null;
if ($selectedExam > 0) {
    $eiStmt = $pdo->prepare("
        SELECT e.*, c.Course_Code, c.Course_Name 
        FROM exams e
        JOIN courses c ON e.Course_ID = c.Course_ID
        WHERE e.Exam_ID = ? LIMIT 1
    ");
    $eiStmt->execute([$selectedExam]);
    $examInfo = $eiStmt->fetch();
}

// 7. Load Candidate Roster, Cohort Analytics & OBE CO Attainment Matrix
$candidates = [];
$stats = [
    'cohort' => 0,
    'attempted' => 0,
    'absent' => 0,
    'avg_score' => 0.0,
    'avg_pct' => 0.0,
    'pass_count' => 0,
    'pass_rate' => 0.0,
    'highest' => 0.0,
    'pending_review' => 0
];
$coAttainment = [];

if ($examInfo) {
    // 7.1 Fetch Enrolled Students for this course batch with their results
    $cStmt = $pdo->prepare("
        SELECT 
            scm.Stud_ID,
            s.Stud_Name,
            s.Stud_Email,
            r.Result_ID,
            r.Total_Marks AS Candidate_Total_Marks,
            r.Marks_Obtained,
            r.Percentage,
            r.Violations,
            r.Auto_Submitted,
            r.Submitted_At,
            r.Graded_At,
            COALESCE(r.Status, 'not_attempted') AS Attempt_Status
        FROM student_course_mapping scm
        JOIN student s ON scm.Stud_ID = s.Stud_ID
        LEFT JOIN results r ON r.Exam_ID = ? AND r.Stud_ID = scm.Stud_ID
        WHERE scm.Course_ID = ? AND scm.Acad_Year = ?
        ORDER BY s.Stud_Name ASC
    ");
    $cStmt->execute([$selectedExam, $selectedCourse, $selectedAY]);
    $candidates = $cStmt->fetchAll();

    $stats['cohort'] = count($candidates);

    $attemptScores = [];
    $attemptPcts = [];

    foreach ($candidates as $cand) {
        if (!empty($cand['Result_ID'])) {
            $stats['attempted']++;
            $score = (float)$cand['Marks_Obtained'];
            $pct = (float)$cand['Percentage'];
            $attemptScores[] = $score;
            $attemptPcts[] = $pct;

            if ($pct >= 40.0) $stats['pass_count']++;
            if ($cand['Attempt_Status'] === 'pending_review') $stats['pending_review']++;
        } else {
            $stats['absent']++;
        }
    }

    if ($stats['attempted'] > 0) {
        $stats['avg_score'] = round(array_sum($attemptScores) / $stats['attempted'], 1);
        $stats['avg_pct'] = round(array_sum($attemptPcts) / $stats['attempted'], 1);
        $stats['pass_rate'] = round(($stats['pass_count'] / $stats['attempted']) * 100, 1);
        $stats['highest'] = round(max($attemptScores), 1);
    }

    // 7.2 Compute NBA / NAAC Course Outcome (CO1–CO5) Attainment Matrix
    $coQuery = $pdo->prepare("
        SELECT 
            eq.CO,
            COUNT(DISTINCT eq.Question_ID) AS Q_Count,
            SUM(eq.Marks) AS Total_CO_Marks
        FROM exam_questions eq
        WHERE eq.Exam_ID = ?
        GROUP BY eq.CO
        ORDER BY eq.CO ASC
    ");
    $coQuery->execute([$selectedExam]);
    $rawCOs = $coQuery->fetchAll();

    if (!empty($rawCOs) && $stats['attempted'] > 0) {
        // Fetch actual marks awarded per CO across all attempts
        $awardedCOStmt = $pdo->prepare("
            SELECT 
                eq.CO,
                SUM(ra.Marks_Awarded) AS Total_Awarded,
                COUNT(ra.Answer_ID) AS Total_Responses
            FROM result_answers ra
            JOIN exam_questions eq ON ra.Question_ID = eq.Question_ID
            JOIN results r ON ra.Result_ID = r.Result_ID
            WHERE r.Exam_ID = ?
            GROUP BY eq.CO
        ");
        $awardedCOStmt->execute([$selectedExam]);
        $awardedCOMap = [];
        while ($aRow = $awardedCOStmt->fetch()) {
            $awardedCOMap[$aRow['CO']] = (float)$aRow['Total_Awarded'];
        }

        foreach ($rawCOs as $coRow) {
            $coNum = $coRow['CO'];
            $maxPossiblePerStudent = (float)$coRow['Total_CO_Marks'];
            $totalPossibleCohort = $maxPossiblePerStudent * $stats['attempted'];
            $totalAwardedCohort = $awardedCOMap[$coNum] ?? 0.0;

            $attainmentPct = $totalPossibleCohort > 0 ? round(($totalAwardedCohort / $totalPossibleCohort) * 100, 1) : 0.0;
            $avgPerStudent = $stats['attempted'] > 0 ? round($totalAwardedCohort / $stats['attempted'], 1) : 0.0;

            // NBA / NAAC Attainment Level definition:
            // Level 3: >= 70%
            // Level 2: 60% - 69.9%
            // Level 1: 50% - 59.9%
            // Level 0: < 50%
            $level = 0;
            $levelBadge = 'bg-danger';
            if ($attainmentPct >= 70.0) {
                $level = 3;
                $levelBadge = 'bg-success';
            } elseif ($attainmentPct >= 60.0) {
                $level = 2;
                $levelBadge = 'bg-primary';
            } elseif ($attainmentPct >= 50.0) {
                $level = 1;
                $levelBadge = 'bg-warning text-dark';
            }

            $coAttainment[] = [
                'co' => $coNum,
                'q_count' => (int)$coRow['Q_Count'],
                'max_marks' => $maxPossiblePerStudent,
                'total_awarded' => $totalAwardedCohort,
                'avg_per_student' => $avgPerStudent,
                'attainment_pct' => $attainmentPct,
                'level' => $level,
                'level_badge' => $levelBadge
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation & Results Workbench — MITS Portal</title>
</head>

<body>
    <!-- Top Header -->
    <?php include '../Common/header.php'; ?>
    <!-- Faculty Sidebar -->
    <?php include '../Common/faculty-sidebar.php'; ?>

    <main>
        <!-- Breadcrumb / Header Title -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark">
                    <i class="bi bi-bar-chart-line-fill text-danger me-2"></i>Evaluation & Results Workbench
                </h3>
                <p class="text-muted small mb-0">Grade student submissions, review proctor telemetry, and measure NBA/NAAC Course Outcome (CO) attainment.</p>
            </div>
            <?php if ($examInfo): ?>
                <div class="d-flex align-items-center gap-2">
                    <a href="export-results.php?Exam_ID=<?= $selectedExam ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold shadow-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Results
                    </a>
                    <button class="btn btn-sm rounded-pill px-3 fw-semibold shadow-sm toggle-publish-btn <?= $examInfo['Result_Published'] == 1 ? 'btn-outline-secondary' : 'btn-danger' ?>" 
                            data-examid="<?= $selectedExam ?>" 
                            data-current="<?= (int)$examInfo['Result_Published'] ?>">
                        <i class="bi <?= $examInfo['Result_Published'] == 1 ? 'bi-eye-slash-fill' : 'bi-megaphone-fill' ?> me-1"></i>
                        <?= $examInfo['Result_Published'] == 1 ? 'Unpublish Results' : 'Publish Results to Students' ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Filter Cascade Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="view-results.php" class="row g-3 align-items-end" id="resultsFilterForm">
                    <!-- Academic Year -->
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1 text-uppercase" style="letter-spacing: 0.5px;">Academic Year</label>
                        <select class="form-select shadow-none" name="Acad_Year" onchange="this.form.submit()">
                            <?php foreach ($ayList as $ay): ?>
                                <option value="<?= e($ay) ?>" <?= ($selectedAY === $ay) ? 'selected' : '' ?>>
                                    <?= e($ay) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Course / Subject -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1 text-uppercase" style="letter-spacing: 0.5px;">Course / Subject</label>
                        <select class="form-select shadow-none" name="Course_ID" onchange="this.form.submit()" <?= empty($assignedCourses) ? 'disabled' : '' ?>>
                            <?php if (empty($assignedCourses)): ?>
                                <option value="">No courses assigned in this cycle</option>
                            <?php else: ?>
                                <?php foreach ($assignedCourses as $c): ?>
                                    <option value="<?= $c['Course_ID'] ?>" <?= ($selectedCourse === (int)$c['Course_ID']) ? 'selected' : '' ?>>
                                        <?= e($c['Course_Code'] . ' - ' . $c['Course_Name']) ?> (S<?= $c['Semester'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Exam -->
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1 text-uppercase" style="letter-spacing: 0.5px;">Examination</label>
                        <select class="form-select shadow-none" name="Exam_ID" onchange="this.form.submit()" <?= empty($examsList) ? 'disabled' : '' ?>>
                            <?php if (empty($examsList)): ?>
                                <option value="">No exams available</option>
                            <?php else: ?>
                                <?php foreach ($examsList as $ex): ?>
                                    <option value="<?= $ex['Exam_ID'] ?>" <?= ($selectedExam === (int)$ex['Exam_ID']) ? 'selected' : '' ?>>
                                        <?= e($ex['Exam_Name']) ?> (<?= $ex['Total_Marks'] ?>M)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-danger w-100 rounded-3 fw-semibold shadow-sm">
                            <i class="bi bi-funnel-fill me-1"></i> Apply Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!$examInfo): ?>
            <div class="card border-0 shadow-sm rounded-4 text-center py-5 bg-white">
                <div class="card-body">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 76px; height: 76px;">
                        <i class="bi bi-bar-chart-steps fs-1 text-muted"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Select Course & Examination</h5>
                    <p class="text-muted small mb-0">Choose an academic year, subject, and examination from the selector above to load candidate evaluation data.</p>
                </div>
            </div>
        <?php else: ?>

            <!-- Executive Performance KPIs -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                        <div class="card-body p-3 text-center">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem;">Enrolled Cohort</span>
                            <h3 class="fw-bold mb-0"><?= $stats['cohort'] ?></h3>
                            <span class="small text-muted">Candidates</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                        <div class="card-body p-3 text-center">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem;">Attendance</span>
                            <h3 class="fw-bold mb-0 text-success"><?= $stats['attempted'] ?></h3>
                            <span class="small text-muted"><?= $stats['absent'] ?> Absent</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                        <div class="card-body p-3 text-center">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem;">Class Average</span>
                            <h3 class="fw-bold mb-0 text-primary font-monospace"><?= $stats['avg_score'] ?></h3>
                            <span class="small text-muted"><?= $stats['avg_pct'] ?>% of Total</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                        <div class="card-body p-3 text-center">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem;">Pass Rate</span>
                            <h3 class="fw-bold mb-0"><?= $stats['pass_rate'] ?>%</h3>
                            <span class="small text-success fw-semibold">&ge; 40% Threshold</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                        <div class="card-body p-3 text-center">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem;">Highest Score</span>
                            <h3 class="fw-bold mb-0 text-success font-monospace"><?= $stats['highest'] ?></h3>
                            <span class="small text-muted">/ <?= (float)$examInfo['Total_Marks'] ?> Max</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white <?= $stats['pending_review'] > 0 ? 'border-warning border-2' : '' ?>">
                        <div class="card-body p-3 text-center">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem;">Manual Review</span>
                            <h3 class="fw-bold mb-0 <?= $stats['pending_review'] > 0 ? 'text-warning' : 'text-muted' ?>">
                                <?= $stats['pending_review'] ?>
                            </h3>
                            <span class="small text-muted">Pending Open Qs</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NBA / NAAC COURSE OUTCOME (CO1-CO5) ATTAINMENT CARD -->
            <?php if (!empty($coAttainment)): ?>
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
                    <div class="card-header bg-white border-bottom p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <span class="badge bg-danger bg-opacity-10 text-danger fw-bold px-2 py-1 mb-1">OBE ACCREDITATION METRICS</span>
                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-award-fill text-danger me-2"></i>NBA / NAAC Course Outcome (CO) Attainment Matrix
                            </h6>
                        </div>
                        <div class="d-flex align-items-center gap-2 small text-muted">
                            <span class="badge bg-success">Level 3: &ge;70%</span>
                            <span class="badge bg-primary">Level 2: 60-69%</span>
                            <span class="badge bg-warning text-dark">Level 1: 50-59%</span>
                        </div>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3">
                            <?php foreach ($coAttainment as $co): ?>
                                <div class="col-md-6 col-lg-4 col-xl">
                                    <div class="p-3 rounded-4 bg-light border h-100 d-flex flex-column justify-content-between">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-danger fs-5">CO<?= $co['co'] ?></span>
                                            <span class="badge <?= $co['level_badge'] ?> fw-bold">Level <?= $co['level'] ?></span>
                                        </div>
                                        <div class="small text-muted mb-2">
                                            <span><?= $co['q_count'] ?> Question(s)</span> &bull; 
                                            <span>Max: <?= $co['max_marks'] ?>M</span>
                                        </div>
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between small fw-bold mb-1">
                                                <span>Attainment:</span>
                                                <span class="font-monospace text-dark"><?= $co['attainment_pct'] ?>%</span>
                                            </div>
                                            <div class="progress rounded-pill" style="height: 7px;">
                                                <div class="progress-bar <?= ($co['attainment_pct'] >= 70) ? 'bg-success' : (($co['attainment_pct'] >= 50) ? 'bg-primary' : 'bg-danger') ?>" 
                                                     style="width: <?= min(100, $co['attainment_pct']) ?>%;"></div>
                                            </div>
                                        </div>
                                        <div class="small text-muted border-top pt-2 mt-auto d-flex justify-content-between">
                                            <span>Cohort Avg:</span>
                                            <span class="fw-bold text-dark font-monospace"><?= $co['avg_per_student'] ?> / <?= $co['max_marks'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Candidate Roster Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                <div class="card-header bg-white border-bottom p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-people-fill text-danger me-2"></i>Candidate Evaluation Roster
                        </h6>
                        <small class="text-muted"><?= count($candidates) ?> Enrolled Candidates &bull; <?= $examInfo['Exam_Name'] ?></small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" class="form-control form-control-sm rounded-pill px-3" placeholder="Search candidate..." id="rosterSearchInput" style="max-width: 200px;">
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap" id="candidateRosterTable">
                            <thead class="table-light text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                                <tr>
                                    <th>#</th>
                                    <th class="ps-5">Name</th>
                                    <th>Submission Time</th>
                                    <th>Proctor Status</th>
                                    <th>Marks</th>
                                    <th>Evaluation Status</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $count = 0;
                                foreach ($candidates as $row): 
                                    $isAttempted = !empty($row['Result_ID']);
                                    $pct = $isAttempted ? (float)$row['Percentage'] : 0.0;
                                    $score = $isAttempted ? (float)$row['Marks_Obtained'] : 0.0;
                                    $status = $row['Attempt_Status'];
                                    $pctBadge = ($pct >= 75) ? 'bg-success' : (($pct >= 50) ? 'bg-primary' : (($pct >= 40) ? 'bg-warning text-dark' : 'bg-danger'));
                                    $count++;
                                ?>
                                    <tr>
                                        <td><?= $count ?></td>
                                        <td class="ps-5">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                                    <?= strtoupper(substr($row['Stud_Name'], 0, 2)) ?>
                                                </div>
                                                <div>
                                                    <span class="fw-bold text-dark d-block"><?= e($row['Stud_Name']) ?></span>
                                                    <small class="text-muted"><?= e($row['Stud_ID']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($isAttempted): ?>
                                                <div class="small fw-medium text-dark"><?= date('d M, h:i A', strtotime($row['Submitted_At'])) ?></div>
                                                <?php if ($row['Auto_Submitted']): ?>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger" style="font-size: 0.65rem;">Auto-Submitted</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted small fst-italic">Not Attempted</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isAttempted): ?>
                                                <?php if ($row['Violations'] > 0): ?>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger fw-semibold">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= $row['Violations'] ?> Violation(s)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success fw-semibold">
                                                        <i class="bi bi-shield-check me-1"></i>Clean
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isAttempted): ?>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-bold font-monospace text-dark fs-6"><?= $score ?></span>
                                                    <span class="badge <?= $pctBadge ?> rounded-pill px-2">
                                                        <?= round($pct, 1) ?>%
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">Absent</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($status === 'graded'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success fw-semibold">
                                                    <i class="bi bi-check-circle me-1"></i>Graded
                                                </span>
                                            <?php elseif ($status === 'pending_review'): ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning fw-semibold">
                                                    <i class="bi bi-pencil-square me-1"></i>Review Pending
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary fw-semibold">
                                                    Not Attempted
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if ($isAttempted): ?>
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Itemized Details Modal Button -->
                                                    <button type="button" 
                                                            class="btn btn-outline-secondary rounded-pill px-3 me-1 btn-view-details" 
                                                            data-studid="<?= e($row['Stud_ID']) ?>" 
                                                            data-examid="<?= $selectedExam ?>" 
                                                            title="View Question-by-Question Breakdown">
                                                        <i class="bi bi-eye"></i> Details
                                                    </button>

                                                    <!-- Rubric Evaluation Modal Button -->
                                                    <button type="button" 
                                                            class="btn btn-outline-danger rounded-pill px-3 me-1 btn-rubric-grade" 
                                                            data-studid="<?= e($row['Stud_ID']) ?>" 
                                                            data-examid="<?= $selectedExam ?>" 
                                                            title="Enter Marks & Rubric Commentary">
                                                        <i class="bi bi-pencil-square"></i> Grade
                                                    </button>

                                                    <!-- PDF Scorecard Download -->
                                                    <a href="download-result.php?Stud_ID=<?= urlencode($row['Stud_ID']) ?>&Exam_ID=<?= $selectedExam ?>" 
                                                       class="btn btn-light rounded-pill px-2 text-danger border" 
                                                       target="_blank" 
                                                       title="Download PDF Scorecard">
                                                        <i class="bi bi-file-earmark-pdf"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <span class="small text-muted fst-italic">No submission</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php endif; ?>

        <!-- Itemized Result Details Modal -->
        <div class="modal fade" id="resultModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="resultModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header bg-dark text-white rounded-top-4">
                        <h5 class="modal-title fw-bold" id="resultModalLabel">
                            <i class="bi bi-mortarboard-fill me-2"></i>Candidate Assessment Performance
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4" id="resultModalBody">
                        <div class="text-center text-muted py-5">
                            <div class="spinner-border text-danger" role="status"></div>
                            <p class="mt-2 small text-muted">Retrieving detailed candidate evaluation...</p>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rubric Grading Workbench Modal -->
        <div class="modal fade" id="marksModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="marksModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header bg-dark text-white rounded-top-4">
                        <h5 class="modal-title fw-bold" id="marksModalLabel">
                            <i class="bi bi-pencil-square me-2"></i>Rubric Grading & Manual Evaluation Workbench
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4" id="marksModalBody">
                        <div class="text-center text-muted py-5">
                            <div class="spinner-border text-danger" role="status"></div>
                            <p class="mt-2 small text-muted">Loading question rubrics and candidate responses...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../Common/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            // Live Roster Search
            $('#rosterSearchInput').on('keyup', function () {
                var value = $(this).val().toLowerCase();
                $('#candidateRosterTable tbody tr').filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });

            // View Details Modal Handler
            $('.btn-view-details').on('click', function (e) {
                e.preventDefault();
                var studID = $(this).data('studid');
                var examID = $(this).data('examid');

                $('#resultModalBody').html('<div class="text-center text-muted py-5"><div class="spinner-border text-danger" role="status"></div><p class="mt-2 small text-muted">Loading candidate scorecard...</p></div>');
                var modal = new bootstrap.Modal(document.getElementById('resultModal'));
                modal.show();

                $.ajax({
                    url: 'fetch-result-details.php',
                    type: 'POST',
                    data: { Stud_ID: studID, Exam_ID: examID },
                    success: function (response) {
                        $('#resultModalBody').html(response);
                    },
                    error: function () {
                        $('#resultModalBody').html('<p class="text-danger text-center py-5">Unable to load result details.</p>');
                    }
                });
            });

            // Rubric Grading Modal Handler
            $('.btn-rubric-grade').on('click', function (e) {
                e.preventDefault();
                var studID = $(this).data('studid');
                var examID = $(this).data('examid');

                $('#marksModalBody').html('<div class="text-center text-muted py-5"><div class="spinner-border text-danger" role="status"></div><p class="mt-2 small text-muted">Loading rubric workbench...</p></div>');
                var modal = new bootstrap.Modal(document.getElementById('marksModal'));
                modal.show();

                $.ajax({
                    url: 'enter-marks.php',
                    type: 'POST',
                    data: { Stud_ID: studID, Exam_ID: examID },
                    success: function (response) {
                        $('#marksModalBody').html(response);
                    },
                    error: function () {
                        $('#marksModalBody').html('<p class="text-danger text-center py-5">Unable to load rubric grading form.</p>');
                    }
                });
            });

            // Publish / Unpublish Results Toggle
            $('.toggle-publish-btn').on('click', function () {
                var $btn = $(this);
                var examId = $btn.data('examid');
                var currentStatus = parseInt($btn.data('current')) || 0;
                var actionWord = currentStatus === 1 ? 'Unpublish' : 'Publish';

                Swal.fire({
                    title: actionWord + ' Results?',
                    text: currentStatus === 1 
                        ? 'Students will no longer be able to see their scorecards until published again.' 
                        : 'Students will immediately be able to view their scores and itemized feedback.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, ' + actionWord,
                    confirmButtonColor: '#d1202d',
                    cancelButtonColor: '#6b7280'
                }).then((res) => {
                    if (res.isConfirmed) {
                        $.ajax({
                            url: 'update-exam-status.php',
                            type: 'POST',
                            data: { exam_id: examId, status: currentStatus, type: 'result' },
                            dataType: 'json',
                            success: function (data) {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Results ' + (data.new_status === 1 ? 'Published!' : 'Unpublished!'),
                                        confirmButtonColor: '#d1202d'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Action Failed',
                                        text: data.message || 'Error toggling publication.',
                                        confirmButtonColor: '#d1202d'
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Server Error',
                                    text: 'Could not connect to server.',
                                    confirmButtonColor: '#d1202d'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>