<?php
session_start();
if (!$_SESSION["LoginFaculty"]) {
    echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
    echo '<script>window.location="../Login/Login.php"</script>';
    exit;
}


$con = mysqli_connect("localhost", "root", "", "exam_db");

$Fac_ID = $_SESSION['LoginFaculty'];

/* ----------------------------------------------------------
    FETCH RESULTS — prepared statement (the original query
    interpolated $Fac_ID directly, which is an injection risk)
    ---------------------------------------------------------- */

$query = "SELECT
            r.Result_ID, r.Stud_ID, r.Exam_ID,
            r.Total_Marks, r.Marks_Obtained,
            r.Status AS Result_Status,
            r.Submitted_At,
            e.Exam_Name, e.Status AS Exam_Published,
            c.Course_Name,
            s.Stud_Name, s.Stud_ID
          FROM results r
          JOIN exams e   ON r.Exam_ID = e.Exam_ID
          JOIN courses c ON e.Course_ID = c.Course_ID
          JOIN student s ON r.Stud_ID = s.Stud_ID
          WHERE e.Fac_ID = ?
          ORDER BY e.Exam_Name, s.Stud_Name";

$stmt = $con->prepare($query);
$stmt->bind_param("i", $Fac_ID);
$stmt->execute();
$run = $stmt->get_result();

$results = [];
while ($row = $run->fetch_assoc()) {
    $results[] = $row;
}

/* Group by exam so the page can render one card-section per exam rather than one long flat table — this is what actually makes it scannable for a faculty member checking on a specific exam. */

$byExam = [];
foreach ($results as $row) {
    $byExam[$row['Exam_ID']]['title']       = $row['Exam_Name'] ? $row['Exam_Name'] : 'Untitled Exam';
    $byExam[$row['Exam_ID']]['course']      = $row['Course_Name'] ? $row['Course_Name'] : 'Unknown Course';
    $byExam[$row['Exam_ID']]['published']   = (int)$row['Exam_Published'] === 1;
    $byExam[$row['Exam_ID']]['rows'][]      = $row ? $row : null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty - View Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --red-900: #7f0000;
            --red-700: #b91c1c;
            --red-600: #dc2626;
            --red-100: #fee2e2;
            --red-50:  #fff5f5;
            --green-600: #16a34a;
            --green-100: #dcfce7;
            --amber-600: #d97706;
            --amber-100: #fef3c7;
            --gray-50:  #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-900: #111827;
            font-family: 'DM Sans', sans-serif;
        }

        body { background: var(--gray-50); }

        .sub-main { padding: 24px 28px 60px; }

        .results-intro {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .results-count {
            font-size: .85rem;
            color: var(--gray-500);
            font-family: 'JetBrains Mono', monospace;
        }

        /* EXAM CARD */
        .exam-card {
            background: #fff;
            border: 1.5px solid var(--gray-200);
            border-radius: 14px;
            margin-bottom: 22px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
        }

        .exam-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 22px;
            background: linear-gradient(90deg, var(--red-900), var(--red-700));
            color: #fff;
            flex-wrap: wrap;
        }

        .exam-title-block { min-width: 0; }

        .exam-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.3rem;
            letter-spacing: 1.5px;
            line-height: 1.1;
            margin: 0;
        }

        .exam-course {
            font-size: .78rem;
            color: rgba(255,255,255,.7);
            margin-top: 2px;
        }

        .exam-head-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .publish-pill {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            padding: 5px 12px;
            border-radius: 20px;
        }

        .publish-pill.is-published {
            background: var(--green-100);
            color: var(--green-600);
        }

        .publish-pill.is-draft {
            background: rgba(255,255,255,.15);
            color: rgba(255,255,255,.85);
        }

        .publish-btn {
            border: none;
            border-radius: 8px;
            padding: 7px 16px;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .3px;
            cursor: pointer;
            transition: opacity .15s;
        }

        .publish-btn:hover { opacity: .88; }
        .publish-btn:disabled { opacity: .5; cursor: not-allowed; }

        .publish-btn.btn-do-publish { background: #fff; color: var(--red-700); }
        .publish-btn.btn-do-unpublish { background: rgba(0,0,0,.25); color: #fff; }

        /* TABLE */
        .results-table { width: 100%; border-collapse: collapse; margin: 0; }

        .results-table thead th {
            background: var(--gray-50);
            color: var(--gray-500);
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .6px;
            font-weight: 700;
            padding: 10px 18px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .results-table tbody td {
            padding: 12px 18px;
            font-size: .87rem;
            color: var(--gray-900);
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }

        .results-table tbody tr:last-child td { border-bottom: none; }
        .results-table tbody tr:hover { background: var(--red-50); }

        .stud-name { font-weight: 600; }
        .stud-roll { font-size: .74rem; color: var(--gray-400); font-family: 'JetBrains Mono', monospace; }

        /* SCORE CELL — signature element: inline score bar */
        .score-cell { min-width: 170px; }

        .score-figures {
            display: flex;
            align-items: baseline;
            gap: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: .86rem;
            margin-bottom: 4px;
        }

        .score-obtained { font-weight: 700; color: var(--gray-900); }
        .score-total { color: var(--gray-400); }

        .score-bar-track {
            height: 5px;
            border-radius: 3px;
            background: var(--gray-200);
            overflow: hidden;
        }

        .score-bar-fill {
            height: 100%;
            border-radius: 3px;
            transition: width .3s ease;
        }

        .score-bar-fill.tier-high { background: var(--green-600); }
        .score-bar-fill.tier-mid  { background: var(--amber-600); }
        .score-bar-fill.tier-low  { background: var(--red-600); }

        /* STATUS BADGE */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: .72rem;
            font-weight: 700;
            padding: 4px 11px;
            border-radius: 20px;
            text-transform: capitalize;
        }

        .status-badge.st-graded { background: var(--green-100); color: var(--green-600); }
        .status-badge.st-pending_review { background: var(--amber-100); color: var(--amber-600); }
        .status-badge.st-published { background: var(--red-100); color: var(--red-700); }

        /* ACTIONS */
        .row-actions { display: flex; gap: 6px; flex-wrap: wrap; }

        .btn-action {
            font-size: .76rem;
            padding: 6px 12px;
            border-radius: 7px;
            border: 1.5px solid var(--gray-200);
            background: #fff;
            color: var(--gray-700);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all .15s;
        }

        .btn-action:hover { border-color: var(--red-600); color: var(--red-700); }
        .btn-action.btn-grade { border-color: var(--amber-600); color: var(--amber-600); }
        .btn-action.btn-grade:hover { background: var(--amber-100); }
        .btn-action.btn-download { border-color: var(--green-600); color: var(--green-600); }
        .btn-action.btn-download:hover { background: var(--green-100); }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 70px 20px;
            color: var(--gray-500);
        }

        .empty-state i { font-size: 2.6rem; color: var(--gray-200); display: block; margin-bottom: 14px; }
        .empty-state p { font-size: .95rem; margin: 0; }

        /* MODAL — detail drill-down */
        .modal-content { border: none; border-radius: 14px; overflow: hidden; }
        .modal-header { background: var(--gray-900); }
        .detail-qcard {
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 12px;
        }
        .detail-qcard.correct { border-left: 4px solid var(--green-600); }
        .detail-qcard.incorrect { border-left: 4px solid var(--red-600); }
        .detail-qcard.ungraded { border-left: 4px solid var(--amber-600); }
        .detail-marks {
            font-family: 'JetBrains Mono', monospace;
            font-size: .82rem;
            font-weight: 700;
            float: right;
        }

        @media (max-width: 720px) {
            .sub-main { padding: 16px; }
            .results-table thead { display: none; }
            .results-table, .results-table tbody, .results-table tr, .results-table td { display: block; width: 100%; }
            .results-table tbody tr { border-bottom: 8px solid var(--gray-50); padding: 10px 0; }
            .results-table tbody td { border-bottom: none; padding: 6px 18px; }
            .results-table tbody td::before {
                content: attr(data-label);
                display: block;
                font-size: .65rem;
                text-transform: uppercase;
                letter-spacing: .5px;
                color: var(--gray-400);
                font-weight: 700;
                margin-bottom: 2px;
            }
        }
    </style>
</head>

<body>
    <?php include '../Common/header.php'; ?>
    <?php include '../Common/faculty-sidebar.php'; ?>

    <main>
        <div class="dashboard-header">
            <h4 class="mb-0 fw-bold">Results</h4>
        </div>

        <div class="sub-main">
            <div class="results-intro">
                <span class="results-count">
                    <?= count($results) ?> result<?= count($results) == 1 ? '' : 's' ?> across <?= count($byExam) ?> exam<?= count($byExam) == 1 ? '' : 's' ?>
                </span>
            </div>

            <?php if (empty($byExam)): ?>
                <div class="empty-state">
                    <i class="bi bi-clipboard-x"></i>
                    <p>No results yet. Once students submit, their scores will show up here grouped by exam.</p>
                </div>
            <?php else: ?>
                <?php foreach ($byExam as $examId => $exam): ?>
                    <div class="exam-card">
                        <div class="exam-card-head">
                            <div class="exam-title-block">
                                <p class="exam-title"><?= htmlspecialchars($exam['title']) ?></p>
                                <div class="exam-course"><?= htmlspecialchars($exam['course']) ?> · <?= count($exam['rows']) ?> submission<?= count($exam['rows']) == 1 ? '' : 's' ?></div>
                            </div>
                            <div class="exam-head-actions">
                                <span class="publish-pill <?= $exam['published'] ? 'is-published' : 'is-draft' ?>">
                                    <?= $exam['published'] ? 'Published' : 'Draft' ?>
                                </span>
                                <button
                                    class="publish-btn <?= $exam['published'] ? 'btn-do-unpublish' : 'btn-do-publish' ?>"
                                    data-examid="<?= $examId ?>"
                                    data-status="<?= $exam['published'] ?>">
                                    <?= $exam['published'] ? 'Unpublish Results' : 'Publish Results' ?>
                                </button>
                            </div>
                        </div>

                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exam['rows'] as $row):
                                    $pct = $row['Total_Marks'] > 0
                                        ? round(($row['Marks_Obtained'] / $row['Total_Marks']) * 100, 1)
                                        : 0;
                                    $tier = $pct >= 75 ? 'high' : ($pct >= 40 ? 'mid' : 'low');
                                    $statusKey = strtolower(str_replace(' ', '_', $row['Result_Status']));
                                ?>
                                    <tr>
                                        <td data-label="Student">
                                            <div class="stud-name"><?= htmlspecialchars($row['Stud_Name']) ?></div>
                                            <div class="stud-roll"><?= htmlspecialchars($row['Stud_ID'] ?? '') ?></div>
                                        </td>
                                        <td data-label="Score" class="score-cell">
                                            <div class="score-figures">
                                                <span class="score-obtained"><?= htmlspecialchars($row['Marks_Obtained']) ?></span>
                                                <span class="score-total">/ <?= htmlspecialchars($row['Total_Marks']) ?> &middot; <?= $pct ?>%</span>
                                            </div>
                                            <div class="score-bar-track">
                                                <div class="score-bar-fill tier-<?= $tier ?>" style="width: <?= min($pct, 100) ?>%"></div>
                                            </div>
                                        </td>
                                        <td data-label="Status">
                                            <span class="status-badge st-<?= htmlspecialchars($statusKey) ?>">
                                                <?= htmlspecialchars(str_replace('_', ' ', $row['Result_Status'])) ?>
                                            </span>
                                        </td>
                                        <td data-label="Submitted">
                                            <?= $row['Submitted_At'] ? date('d M, g:i A', strtotime($row['Submitted_At'])) : '—' ?>
                                        </td>
                                        <td data-label="Actions">
                                            <div class="row-actions">
                                                <a href="#" class="btn-action view-details"
                                                   data-studid="<?= $row['Stud_ID'] ?>"
                                                   data-examid="<?= $row['Exam_ID'] ?>">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <?php if ($statusKey === 'pending_review'): ?>
                                                <a href="#" class="btn-action btn-grade enter-marks"
                                                   data-studid="<?= $row['Stud_ID'] ?>"
                                                   data-examid="<?= $row['Exam_ID'] ?>">
                                                    <i class="bi bi-pencil-square"></i> Grade
                                                </a>
                                                <?php endif; ?>
                                                <a href="download-result.php?Stud_ID=<?= $row['Stud_ID'] ?>&Exam_ID=<?= $row['Exam_ID'] ?>"
                                                   class="btn-action btn-download" target="_blank">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="resultModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="resultModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header text-white">
                        <h5 class="modal-title" id="resultModalLabel">Student Result Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="resultDetails">
                        <div class="text-center text-muted py-5">
                            <div class="spinner-border text-danger" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include '../Common/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function () {

            // View result detail (per-question breakdown)
            $(document).on('click', '.view-details', function (e) {
                e.preventDefault();
                var studID = $(this).data('studid');
                var examID = $(this).data('examid');

                $('#resultDetails').html('<div class="text-center text-muted py-5"><div class="spinner-border text-danger" role="status"></div></div>');
                var modal = new bootstrap.Modal(document.getElementById('resultModal'));
                modal.show();

                $.ajax({
                    url: 'fetch-result-details.php',
                    type: 'POST',
                    data: { Stud_ID: studID, Exam_ID: examID },
                    success: function (response) {
                        $('#resultDetails').html(response);
                    },
                    error: function () {
                        $('#resultDetails').html('<p class="text-danger text-center py-5">Could not load result details.</p>');
                    }
                });
            });

            // Open grading modal for pending_review (open-ended) answers
            $(document).on('click', '.enter-marks', function (e) {
                e.preventDefault();
                var studID = $(this).data('studid');
                var examID = $(this).data('examid');

                $('#resultDetails').html('<div class="text-center text-muted py-5"><div class="spinner-border text-danger" role="status"></div></div>');
                var modal = new bootstrap.Modal(document.getElementById('resultModal'));
                modal.show();

                $.post('enter-marks.php', { Stud_ID: studID, Exam_ID: examID }, function (response) {
                    $('#resultDetails').html(response);
                });
            });

            // Save manually-entered marks
            $(document).on('click', '.save-marks-btn', function () {
                var studID = $(this).data('studid');
                var examID = $(this).data('examid');
                var btn = $(this);

                var marksData = {};
                $('#resultDetails input[data-mark]').each(function () {
                    marksData[$(this).attr('name')] = $(this).val();
                });

                btn.prop('disabled', true).text('Saving…');

                $.post('enter-marks.php', {
                    Stud_ID: studID,
                    Exam_ID: examID,
                    save: '1',
                    marks: marksData
                }, function (response) {
                    if (response.trim() === 'success') {
                        location.reload();
                    } else {
                        alert('Could not save marks. Please try again.');
                        btn.prop('disabled', false).text('Save Marks');
                    }
                });
            });

            // Publish / unpublish exam results
            $(document).on('click', '.publish-btn', function () {
                const btn = $(this);
                const examId = btn.data('examid');
                const currentStatus = btn.data('status'); // 1 or 0

                btn.prop('disabled', true);

                $.ajax({
                    url: 'update-exam-status.php',
                    type: 'POST',
                    data: { exam_id: examId, status: currentStatus },
                    success: function (response) {
                        if (response.success) {
                            if (response.new_status == 1) {
                                btn.removeClass('btn-do-publish').addClass('btn-do-unpublish')
                                   .text('Unpublish Results').data('status', 1);
                                btn.closest('.exam-card-head').find('.publish-pill')
                                   .removeClass('is-draft').addClass('is-published').text('Published');
                            } else {
                                btn.removeClass('btn-do-unpublish').addClass('btn-do-publish')
                                   .text('Publish Results').data('status', 0);
                                btn.closest('.exam-card-head').find('.publish-pill')
                                   .removeClass('is-published').addClass('is-draft').text('Draft');
                            }
                        } else {
                            alert(response.message || 'Operation failed');
                        }
                    },
                    error: function () {
                        alert('Server error. Try again.');
                    },
                    complete: function () {
                        btn.prop('disabled', false);
                    }
                });
            });
        });
    </script>

</body>

</html>