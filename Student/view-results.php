<?php
/**
 * MITS Assessment Portal - Student Results & Performance Scorecard
 * Displays itemized evaluation, percentage analytics, and detailed question-by-question reviews.
 */
session_start();
require_once __DIR__ . '/../Common/auth.php';
check_role('Student');
require_once __DIR__ . "/../Connection/connection.php";

$Stud_ID = $_SESSION['LoginStudent'];

// 1. Fetch all submissions for this student
$stmt = $pdo->prepare("
    SELECT 
        r.Result_ID, r.Exam_ID, r.Course_ID, r.Total_Marks, r.Marks_Obtained, r.Percentage, 
        r.Time_Taken_Sec, r.Violations, r.Auto_Submitted, r.Status, r.Submitted_At, r.Graded_At,
        e.Exam_Name, e.Duration, e.Result_Published,
        c.Course_Code, c.Course_Name
    FROM results r
    JOIN exams e ON r.Exam_ID = e.Exam_ID
    LEFT JOIN courses c ON (r.Course_ID = c.Course_ID OR e.Course_ID = c.Course_ID)
    WHERE r.Stud_ID = ?
    ORDER BY r.Submitted_At DESC
");
$stmt->execute([$Stud_ID]);
$results = $stmt->fetchAll();

// 2. Compute aggregate analytics
$totalAttempted = count($results);
$publishedResults = array_filter($results, function($r) {
    return ($r['Result_Published'] == 1 || $r['Status'] === 'graded' || $r['Status'] === 'published');
});

$publishedCount = count($publishedResults);
$avgPct = 0;
$highestPct = 0;
$passedCount = 0;

if ($publishedCount > 0) {
    $pctValues = array_column($publishedResults, 'Percentage');
    $avgPct = round(array_sum($pctValues) / $publishedCount, 1);
    $highestPct = round(max($pctValues), 1);
    foreach ($pctValues as $pct) {
        if ($pct >= 40.0) $passedCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Results — MITS Portal</title>
</head>

<body>
    <!-- Top Navigation Header -->
    <?php include '../Common/header.php'; ?>
    <!-- Student Sidebar -->
    <?php include '../Common/student-sidebar.php'; ?>

    <main>
        <!-- Title Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark">
                    <i class="bi bi-bar-chart-fill text-danger me-2"></i>My Results & Performance
                </h3>
            </div>
            <!-- <div>
                <a href="view-exams.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold">
                    <i class="bi bi-arrow-left me-1"></i> Back to Exams
                </a>
            </div> -->
        </div>

        <!-- 4 KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                    <div class="card-body p-3 p-md-4 d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-primary bg-opacity-10 text-primary p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                            <i class="bi bi-file-earmark-check"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Tests Taken</span>
                            <h3 class="fw-bold mb-0"><?= $totalAttempted ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                    <div class="card-body p-3 p-md-4 d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-info bg-opacity-10 text-info p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                            <i class="bi bi-speedometer2"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Average Score</span>
                            <h3 class="fw-bold mb-0"><?= $avgPct ?>%</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                    <div class="card-body p-3 p-md-4 d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-success bg-opacity-10 text-success p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                            <i class="bi bi-trophy-fill text-warning"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Highest Score</span>
                            <h3 class="fw-bold mb-0"><?= $highestPct ?>%</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                    <div class="card-body p-3 p-md-4 d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-warning bg-opacity-10 text-warning p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                            <i class="bi bi-patch-check-fill text-success"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Passing Rate</span>
                            <h3 class="fw-bold mb-0">
                                <?= $publishedCount > 0 ? round(($passedCount / $publishedCount) * 100, 1) : 0 ?>%
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scorecards Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-list-stars me-2"></i>Assessment History & Scorecards
                </h6>
                <span class="badge bg-light text-muted border">
                    <?= count($results) ?> Total Attempt(s)
                </span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($results)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-journal-x fs-1 opacity-50"></i>
                        <p class="small mt-2 mb-0">No examination submissions on record yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                                <tr>
                                    <th class="ps-4">Examination</th>
                                    <th>Course</th>
                                    <th>Submission Time</th>
                                    <th>Duration Taken</th>
                                    <th>Proctor Status</th>
                                    <th>Marks / Percentage</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $res): 
                                    $isPublished = ($res['Result_Published'] == 1 || $res['Status'] === 'graded' || $res['Status'] === 'published');
                                    $pct = (float)$res['Percentage'];
                                    $pctBadge = $pct >= 75 ? 'bg-success' : ($pct >= 50 ? 'bg-primary' : ($pct >= 40 ? 'bg-warning text-dark' : 'bg-danger'));
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold text-dark d-block"><?= e($res['Exam_Name']) ?></span>
                                            <small class="text-muted">Total: <?= $res['Total_Marks'] ?> Marks</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger bg-opacity-10 text-danger fw-semibold">
                                                <?= e($res['Course_Code'] ?? 'GEN') ?>
                                            </span>
                                            <span class="small text-muted ms-1"><?= e($res['Course_Name']) ?></span>
                                        </td>
                                        <td>
                                            <div class="small fw-medium text-dark"><?= date('d M Y, h:i A', strtotime($res['Submitted_At'])) ?></div>
                                            <?php if ($res['Auto_Submitted']): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger" style="font-size: 0.65rem;">Auto-Submitted</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="small text-muted font-monospace">
                                                <?= floor($res['Time_Taken_Sec'] / 60) ?>m <?= $res['Time_Taken_Sec'] % 60 ?>s
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($res['Violations'] > 0): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger fw-semibold">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i><?= $res['Violations'] ?> Violation(s)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success bg-opacity-10 text-success fw-semibold">
                                                    <i class="bi bi-shield-check me-1"></i>Clean
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isPublished): ?>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-bold text-dark fs-6"><?= (float)$res['Marks_Obtained'] ?></span>
                                                    <span class="badge <?= $pctBadge ?> rounded-pill px-2">
                                                        <?= round($pct, 1) ?>%
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small font-italic">— Pending —</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isPublished): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success fw-semibold">
                                                    <i class="bi bi-check2-circle me-1"></i>Graded
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning fw-semibold">
                                                    <i class="bi bi-hourglass-split me-1"></i>Under Review
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if ($isPublished): ?>
                                                <button class="btn btn-outline-danger btn-sm rounded-pill px-3 view-details" 
                                                        data-studid="<?= e($Stud_ID) ?>" 
                                                        data-examid="<?= $res['Exam_ID'] ?>">
                                                    <i class="bi bi-eye-fill me-1"></i> Scorecard
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-light btn-sm rounded-pill px-3 text-muted" disabled>
                                                    Pending
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Result Details Modal -->
        <div class="modal fade" id="resultModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="resultModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header bg-dark text-white rounded-top-4">
                        <h5 class="modal-title fw-bold" id="resultModalLabel">
                            <i class="bi bi-mortarboard-fill me-2"></i>Detailed Examination Scorecard
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4" id="resultDetails">
                        <div class="text-center text-muted py-5">
                            <div class="spinner-border text-danger" role="status"></div>
                            <p class="mt-2 small text-muted">Loading complete scorecard and grader commentary...</p>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../Common/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            $(document).on('click', '.view-details', function (e) {
                e.preventDefault();
                var studID = $(this).data('studid');
                var examID = $(this).data('examid');

                $('#resultDetails').html('<div class="text-center text-muted py-5"><div class="spinner-border text-danger" role="status"></div><p class="mt-2 small text-muted">Retrieving detailed evaluation...</p></div>');
                var modal = new bootstrap.Modal(document.getElementById('resultModal'));
                modal.show();

                $.ajax({
                    url: '../Faculty/fetch-result-details.php',
                    type: 'POST',
                    data: { Stud_ID: studID, Exam_ID: examID },
                    success: function (response) {
                        $('#resultDetails').html(response);
                    },
                    error: function () {
                        $('#resultDetails').html('<p class="text-danger text-center py-5">Unable to load result details. Please try again later.</p>');
                    }
                });
            });
        });
    </script>
</body>

</html>
