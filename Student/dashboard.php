<?php
/**
 * MITS Assessment Portal - Student Dashboard
 * Parameterized PDO Queries, Modernized UI with Live Exam Schedule & Progress KPIs
 */
session_start();
require_once __DIR__ . '/../Common/auth.php';
check_role('Student');
require_once __DIR__ . "/../Connection/connection.php";

$Stud_ID = $_SESSION['LoginStudent'];

// 1. Fetch Student Profile with Department and Programme
$stmt = $pdo->prepare("
    SELECT s.*, d.Dept_Name, p.Prog_Name 
    FROM student s
    LEFT JOIN department d ON s.Stud_Dept = d.Dept_ID
    LEFT JOIN programmes p ON s.Stud_Branch = p.Prog_ID
    WHERE s.Stud_ID = ?
    LIMIT 1
");
$stmt->execute([$Stud_ID]);
$student = $stmt->fetch();

if (!$student) {
    echo "Student profile not found.";
    exit;
}

// 2. Enrolled Courses Count
$courseCountStmt = $pdo->prepare("SELECT COUNT(DISTINCT Course_ID) FROM student_course_mapping WHERE Stud_ID = ?");
$courseCountStmt->execute([$Stud_ID]);
$enrolledCount = (int)$courseCountStmt->fetchColumn();

// 3. Completed Assessments & Cumulative Score
$compStmt = $pdo->prepare("
    SELECT COUNT(*) AS total_completed, AVG(Percentage) AS avg_percentage
    FROM results
    WHERE Stud_ID = ?
");
$compStmt->execute([$Stud_ID]);
$compStats = $compStmt->fetch();
$completedCount = (int)($compStats['total_completed'] ?? 0);
$avgPct = round((float)($compStats['avg_percentage'] ?? 0), 1);

// 4. Currently Ongoing Exams Available to Attempt
$activeExamsStmt = $pdo->prepare("
    SELECT DISTINCT e.Exam_ID, e.Exam_Name, e.Duration, e.Total_Marks, e.Start_Time, e.End_Time, c.Course_Name, c.Course_Code
    FROM student_course_mapping scm
    JOIN exams e ON scm.Course_ID = e.Course_ID
    JOIN courses c ON e.Course_ID = c.Course_ID
    WHERE scm.Stud_ID = ? 
      AND e.Status = 'published'
      AND (e.Start_Time IS NULL OR e.Start_Time <= COALESCE(CONVERT_TZ(UTC_TIMESTAMP(), '+00:00', '+05:30'), NOW()))
      AND (e.End_Time IS NULL OR e.End_Time >= COALESCE(CONVERT_TZ(UTC_TIMESTAMP(), '+00:00', '+05:30'), NOW()))
      AND e.Exam_ID NOT IN (SELECT Exam_ID FROM results WHERE Stud_ID = ?)
    ORDER BY e.End_Time ASC
");
$activeExamsStmt->execute([$Stud_ID, $Stud_ID]);
$activeExams = $activeExamsStmt->fetchAll();
$activeCount = count($activeExams);

// 5. Upcoming Scheduled Exams
$upcomingStmt = $pdo->prepare("
    SELECT DISTINCT e.Exam_ID, e.Exam_Name, e.Duration, e.Total_Marks, e.Start_Time, e.End_Time, c.Course_Name, c.Course_Code
    FROM student_course_mapping scm
    JOIN exams e ON scm.Course_ID = e.Course_ID
    JOIN courses c ON e.Course_ID = c.Course_ID
    WHERE scm.Stud_ID = ? 
      AND e.Status = 'published'
      AND e.Start_Time > COALESCE(CONVERT_TZ(UTC_TIMESTAMP(), '+00:00', '+05:30'), NOW())
    ORDER BY e.Start_Time ASC
    LIMIT 4
");
$upcomingStmt->execute([$Stud_ID]);
$upcomingExams = $upcomingStmt->fetchAll();

// 6. Recent Submissions
$recentSubStmt = $pdo->prepare("
    SELECT r.Result_ID, r.Exam_ID, r.Marks_Obtained, r.Total_Marks, r.Percentage, r.Submitted_At, r.Status,
           e.Exam_Name, e.Result_Published, c.Course_Name, c.Course_Code
    FROM results r
    JOIN exams e ON r.Exam_ID = e.Exam_ID
    LEFT JOIN courses c ON e.Course_ID = c.Course_ID
    WHERE r.Stud_ID = ?
    ORDER BY r.Submitted_At DESC
    LIMIT 4
");
$recentSubStmt->execute([$Stud_ID]);
$recentSubmissions = $recentSubStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard — MITS Assessment Portal</title>
</head>

<body>
    <!-- Top Navigation Header -->
    <?php include '../Common/header.php'; ?>
    <!-- Student Sidebar -->
    <?php include '../Common/student-sidebar.php'; ?>

    <main>
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm mb-4 bg-white overflow-hidden">
            <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill mb-2 fw-semibold">
                        <i class="bi bi-mortarboard-fill me-1"></i> Student Portal
                    </span>
                    <h3 class="fw-bold mb-1 text-dark">Welcome back, <?= e($student['Stud_Name']) ?></h3>
                    <p class="text-muted mb-0 small">
                        Department: <strong><?= e($student['Dept_Name']) ?></strong> | Admission No: <code><?= e($student['Stud_ID']) ?></code>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="view-results.php" class="btn btn-outline-primary">
                        <i class="bi bi-journal-text me-1"></i>View Results
                    </a>
                    <a href="view-exams.php" class="btn btn-primary">
                        <i class="bi bi-journal-bookmark-fill me-1"></i>View Exams
                    </a>
                </div>
            </div>
        </div>  


        <!-- 4 Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                    <div class="card-body p-3 p-md-4 d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-primary bg-opacity-10 text-primary p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                            <i class="bi bi-book-half"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Enrolled Courses</span>
                            <h3 class="fw-bold mb-0"><?= $enrolledCount ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 <?= $activeCount > 0 ? 'border-danger border-2 bg-danger bg-opacity-10' : 'bg-white' ?>">
                    <div class="card-body p-3 p-md-4 d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-danger bg-opacity-25 text-danger p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Active Exams</span>
                            <h3 class="fw-bold mb-0 text-danger"><?= $activeCount ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                    <div class="card-body p-3 p-md-4 d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-success bg-opacity-10 text-success p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Completed Tests</span>
                            <h3 class="fw-bold mb-0"><?= $completedCount ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                    <div class="card-body p-3 p-md-4 d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-info bg-opacity-10 text-info p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Average Score</span>
                            <h3 class="fw-bold mb-0"><?= $avgPct ?>%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACTIVE EXAMS CALL-TO-ACTION (If any ongoing) -->
        <?php if (!empty($activeExams)): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4 text-white overflow-hidden" style="background: ">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-white bg-opacity-20 d-flex align-items-center justify-content-center p-3 fs-3">
                                <i class="bi bi-alarm-fill text-warning"></i>
                            </div>
                            <div>
                                <span class="badge bg-warning text-dark px-2 py-1 mb-1 fw-bold text-uppercase">Assessment In Progress</span>
                                <h4 class="fw-bold mb-1"><?= e($activeExams[0]['Exam_Name']) ?></h4>
                                <p class="mb-0 text-white-50 small">
                                    Course: <?= e($activeExams[0]['Course_Code'] . ' - ' . $activeExams[0]['Course_Name']) ?> &bull; 
                                    Duration: <?= $activeExams[0]['Duration'] ?> Mins &bull; 
                                    Max Marks: <?= $activeExams[0]['Total_Marks'] ?>
                                </p>
                            </div>
                        </div>
                        <div>
                            <a href="attempt-exam.php?Exam_ID=<?= $activeExams[0]['Exam_ID'] ?>" class="btn btn-light btn-lg px-4 fw-bold text-danger shadow rounded-pill">
                                <i class="bi bi-play-circle-fill me-1"></i> Start Exam Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- MAIN TWO-COLUMN SECTION -->
        <div class="row g-4">
            <!-- Left Column: Student Academic Profile Card -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-person-badge-fill me-2"></i>Student Profile
                        </h6>
                        <span class="badge bg-success bg-opacity-10 fw-semibold">Active Student</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="rounded-circle bg-danger bg-opacity-10 text-danger border border-2 border-danger d-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm" style="width: 90px; height: 90px; font-size: 2.2rem; font-weight: 700;">
                                <?= strtoupper(substr($student['Stud_Name'], 0, 2)) ?>
                            </div>
                            <h5 class="fw-bold mb-1 text-dark"><?= e($student['Stud_Name']) ?></h5>
                            <span class="text-muted small"><?= e($student['Stud_Email']) ?></span>
                        </div>

                        <div class="list-group list-group-flush rounded-3 border">
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <span class="text-muted small">Admission / Roll No</span>
                                <span class="fw-bold text-dark font-monospace"><?= e($student['Stud_ID']) ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <span class="text-muted small">Department</span>
                                <span class="fw-semibold text-dark"><?= e($student['Dept_Name'] ?? 'N/A') ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <span class="text-muted small">Programme</span>
                                <span class="fw-semibold text-dark"><?=  ($student['Prog_Name'] ?? 'N/A') ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <span class="text-muted small">Current Semester</span>
                                <span class="badge bg-dark">Semester <?= e($student['Stud_Sem']) ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <span class="text-muted small">Academic Batch</span>
                                <span class="fw-semibold text-muted"><?= e($student['Stud_Year']) ?></span>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <a href="settings.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                <i class="bi bi-shield-lock me-1"></i> Update Password
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Upcoming Schedule & Recent Submissions -->
            <div class="col-lg-7">
                <!-- Upcoming Exams Schedule -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-calendar-event-fill me-2"></i>Upcoming Examination Schedule
                        </h6>
                        <a href="view-exams.php" class="text-decoration-none small text-danger fw-semibold">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($upcomingExams)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-calendar-check fs-2 text-muted opacity-50"></i>
                                <p class="small mb-0 mt-2">No upcoming exams scheduled at the moment.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcomingExams as $up): ?>
                                    <div class="list-group-item p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <div>
                                            <h6 class="fw-bold mb-1 text-dark"><?= e($up['Exam_Name']) ?></h6>
                                            <p class="text-muted small mb-0">
                                                <i class="bi bi-book me-1"></i><?= e($up['Course_Code'] . ' - ' . $up['Course_Name']) ?> &bull; 
                                                <i class="bi bi-clock me-1"></i><?= $up['Duration'] ?> mins
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-warning bg-opacity-25 text-dark fw-bold mb-1 d-block">
                                                <?= date('d M Y, h:i A', strtotime($up['Start_Time'])) ?> IST
                                            </span>
                                            <small class="text-muted">Max: <?= $up['Total_Marks'] ?> Marks</small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Completed Assessments -->
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-card-checklist me-2"></i>Recent Completed Assessments
                        </h6>
                        <a href="view-results.php" class="text-decoration-none small text-danger fw-semibold">All Results</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentSubmissions)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-journal-x fs-2 opacity-50"></i>
                                <p class="small mb-0 mt-2">You haven't attempted any assessments yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentSubmissions as $sub): ?>
                                    <div class="list-group-item p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <div>
                                            <h6 class="fw-bold mb-1 text-dark"><?= e($sub['Exam_Name']) ?></h6>
                                            <p class="text-muted small mb-0">
                                                <?= e($sub['Course_Code'] ?? '') ?> &bull; Submitted <?= date('d M Y, h:i A', strtotime($sub['Submitted_At'])) ?>
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <?php if ($sub['Result_Published'] == 1): ?>
                                                <span class="badge bg-success fs-6 fw-bold">
                                                    <?= (float)$sub['Marks_Obtained'] ?> / <?= (float)$sub['Total_Marks'] ?>
                                                </span>
                                                <div class="small text-muted mt-1"><?= round((float)$sub['Percentage'], 1) ?>%</div>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Attempted</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../Common/footer.php'; ?>
</body>

</html>