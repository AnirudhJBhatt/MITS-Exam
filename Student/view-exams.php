<?php
/**
 * MITS Assessment Portal - Student View Exams
 * Shows enrolled assessments categorized into Ongoing, Upcoming, and Completed/Attempted
 */
session_start();
require_once __DIR__ . '/../Common/auth.php';
check_role('Student');
require_once __DIR__ . "/../Connection/connection.php";

$Stud_ID = $_SESSION['LoginStudent'];

// Fetch all published exams for student's enrolled courses with attempt state
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        e.Exam_ID, e.Exam_Name, e.Duration, e.Total_Marks, e.Start_Time, e.End_Time, 
        e.Result_Published, c.Course_Name, c.Course_Code,
        r.Result_ID, r.Marks_Obtained, r.Status AS Result_Status, r.Submitted_At,
        CASE 
            WHEN r.Result_ID IS NOT NULL THEN 'Attempted'
            WHEN (e.Start_Time IS NOT NULL AND NOW() < e.Start_Time) THEN 'Upcoming'
            WHEN (e.End_Time IS NOT NULL AND NOW() > e.End_Time) THEN 'Over'
            ELSE 'Ongoing'
        END AS Exam_State
    FROM student_course_mapping scm
    JOIN exams e ON scm.Course_ID = e.Course_ID
    JOIN courses c ON e.Course_ID = c.Course_ID
    LEFT JOIN results r ON r.Exam_ID = e.Exam_ID AND r.Stud_ID = scm.Stud_ID
    WHERE scm.Stud_ID = ? AND e.Status = 'published'
    ORDER BY 
        CASE 
            WHEN r.Result_ID IS NULL AND (e.Start_Time IS NULL OR NOW() >= e.Start_Time) AND (e.End_Time IS NULL OR NOW() <= e.End_Time) THEN 1
            WHEN (e.Start_Time IS NOT NULL AND NOW() < e.Start_Time) THEN 2
            WHEN r.Result_ID IS NOT NULL THEN 3
            ELSE 4
        END,
        e.Start_Time ASC
");
$stmt->execute([$Stud_ID]);
$exams = $stmt->fetchAll();

$counts = [
    'all' => count($exams),
    'ongoing' => 0,
    'upcoming' => 0,
    'attempted' => 0
];
foreach ($exams as $ex) {
    if ($ex['Exam_State'] === 'Ongoing') $counts['ongoing']++;
    elseif ($ex['Exam_State'] === 'Upcoming') $counts['upcoming']++;
    elseif ($ex['Exam_State'] === 'Attempted') $counts['attempted']++;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examinations — MITS Assessment Portal</title>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Common/header.php'; ?>
    <!-- SIDEBAR -->
    <?php include '../Common/student-sidebar.php'; ?>

    <main>
        <!-- Title & Filter Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark">
                    <i class="bi bi-file-earmark-text-fill text-danger me-2"></i>My Examinations
                </h3>
            </div>
            <!-- Filter Pills -->
            <div class="btn-group shadow-sm rounded-pill" role="group" id="filterPills">
                <button type="button" class="btn btn-danger btn-sm px-3 filter-btn" data-filter="all">
                    All <span class="badge bg-white text-danger ms-1"><?= $counts['all'] ?></span>
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm px-3 filter-btn" data-filter="ongoing">
                    Ongoing <span class="badge bg-danger text-white ms-1"><?= $counts['ongoing'] ?></span>
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm px-3 filter-btn" data-filter="upcoming">
                    Upcoming <span class="badge bg-warning text-dark ms-1"><?= $counts['upcoming'] ?></span>
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm px-3 filter-btn" data-filter="attempted">
                    Attempted <span class="badge bg-secondary text-white ms-1"><?= $counts['attempted'] ?></span>
                </button>
            </div>
        </div>

        <!-- Examination Cards List -->
        <?php if (empty($exams)): ?>
            <div class="card border-0 shadow-sm rounded-4 text-center py-5">
                <div class="card-body">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-journal-x fs-1 text-muted"></i>
                    </div>
                    <h5 class="fw-bold text-dark">No Examinations Available</h5>
                    <p class="text-muted small mb-0">You do not have any published exams in your enrolled courses right now.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-3" id="examCardsContainer">
                <?php foreach ($exams as $ex): 
                    $filterClass = strtolower($ex['Exam_State']);
                ?>
                    <div class="col-12 exam-card-item" data-state="<?= $filterClass ?>">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden transition-all hover-elevate bg-white">
                            <div class="card-body p-3 p-md-4">
                                <div class="row align-items-center g-3">
                                    <!-- Left: Course & Title -->
                                    <div class="col-lg-5">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge bg-danger bg-opacity-10 text-danger fw-semibold px-2 py-1">
                                                <?= e($ex['Course_Code'] ?? 'GEN') ?>
                                            </span>
                                            <span class="text-muted small fw-medium"><?= e($ex['Course_Name']) ?></span>
                                        </div>
                                        <h5 class="fw-bold mb-1 text-dark">
                                            <?= e($ex['Exam_Name']) ?>
                                        </h5>
                                        <div class="d-flex align-items-center gap-3 text-muted small mt-2">
                                            <span><i class="bi bi-clock me-1 text-danger"></i><?= $ex['Duration'] ?> Mins</span>
                                            <span><i class="bi bi-award me-1 text-danger"></i>Max: <?= $ex['Total_Marks'] ?> Marks</span>
                                        </div>
                                    </div>

                                    <!-- Middle: Window Timings -->
                                    <div class="col-lg-4">
                                        <div class="p-2 px-3 rounded-3 bg-light border small">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">Start Window:</span>
                                                <span class="fw-semibold text-dark">
                                                    <?= !empty($ex['Start_Time']) ? date('d M Y, h:i A', strtotime($ex['Start_Time'])) : 'Immediate' ?>
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">End Window:</span>
                                                <span class="fw-semibold text-dark">
                                                    <?= !empty($ex['End_Time']) ? date('d M Y, h:i A', strtotime($ex['End_Time'])) : 'No Deadline' ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: Action CTA / Status Badge -->
                                    <div class="col-lg-3 text-lg-end">
                                        <?php if ($ex['Exam_State'] === 'Attempted'): ?>
                                            <div class="d-inline-flex flex-column align-items-lg-end">
                                                <?php if ($ex['Result_Published'] == 1 ): ?>
                                                    <div class="mb-2">
                                                        <span class="fw-bold text-success fs-6">
                                                            Score: <?= (float)$ex['Marks_Obtained'] ?> / <?= (float)$ex['Total_Marks'] ?>
                                                        </span>
                                                    </div>
                                                    <button class="btn btn-outline-danger btn-sm rounded-pill px-3 view-details" 
                                                            data-studid="<?= e($Stud_ID) ?>" 
                                                            data-examid="<?= $ex['Exam_ID'] ?>">
                                                        <i class="bi bi-card-text me-1"></i> View Scorecard
                                                    </button>
                                                <?php else: ?>
                                                    <div class="mb-2">
                                                        <span class="badge bg-secondary mb-2 px-3 py-1">
                                                            <i class="bi bi-check-circle me-1"></i>Attempted
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($ex['Exam_State'] === 'Ongoing'): ?>
                                            <div class="d-inline-flex flex-column align-items-lg-end">
                                                <span class="badge bg-success mb-2 px-3 py-1 animate-pulse">
                                                    <i class="bi bi-record-circle me-1"></i>Available Now
                                                </span>
                                                <a href="attempt-exam.php?Exam_ID=<?= $ex['Exam_ID'] ?>" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold shadow-sm">
                                                    <i class="bi bi-play-circle-fill me-1"></i> Attempt Exam
                                                </a>
                                            </div>
                                        <?php elseif ($ex['Exam_State'] === 'Upcoming'): ?>
                                            <div class="d-inline-flex flex-column align-items-lg-end">
                                                <span class="badge bg-warning text-dark px-3 py-2 fw-semibold">
                                                    <i class="bi bi-calendar-event me-1"></i>Upcoming
                                                </span>
                                                <small class="text-muted mt-1">Starts <?= date('d M, h:i A', strtotime($ex['Start_Time'])) ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border px-3 py-2">
                                                <i class="bi bi-dash-circle me-1"></i>Exam Over
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Student Result Details Modal -->
        <div class="modal fade" id="resultModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="resultModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header bg-dark text-white rounded-top-4">
                        <h5 class="modal-title fw-bold" id="resultModalLabel">
                            <i class="bi bi-mortarboard-fill me-2"></i>Assessment Result Details
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4" id="resultDetails">
                        <div class="text-center text-muted py-5">
                            <div class="spinner-border text-danger" role="status"></div>
                            <p class="mt-2 small text-muted">Retrieving detailed evaluation...</p>
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

    <style>
        .hover-elevate {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hover-elevate:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08) !important;
        }
        .animate-pulse {
            animation: pulse-badge 2s infinite;
        }
        @keyframes pulse-badge {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            // Filter pills logic
            $('.filter-btn').on('click', function () {
                $('.filter-btn').removeClass('btn-danger').addClass('btn-outline-danger');
                $(this).addClass('btn-danger').removeClass('btn-outline-danger');

                var filter = $(this).data('filter');
                if (filter === 'all') {
                    $('.exam-card-item').fadeIn(200);
                } else {
                    $('.exam-card-item').hide();
                    $('.exam-card-item[data-state="' + filter + '"]').fadeIn(200);
                }
            });

            // View Details Modal Handler
            $(document).on('click', '.view-details', function (e) {
                e.preventDefault();
                var studID = $(this).data('studid');
                var examID = $(this).data('examid');

                $('#resultDetails').html('<div class="text-center text-muted py-5"><div class="spinner-border text-danger" role="status"></div><p class="mt-2 small text-muted">Loading your scorecard...</p></div>');
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