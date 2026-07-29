<?php
    session_start();
    if (!$_SESSION["LoginFaculty"]) {
        echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../Login/Login.php"</script>';
        exit;
    }


    require_once __DIR__ . "/../Connection/connection.php";

    $Fac_ID = $_SESSION['LoginFaculty'];
    $Course_ID = $_GET['Course_ID'] ?? "123";

    $cq     = mysqli_query($con, "SELECT * FROM `courses` WHERE `Course_ID` = '$Course_ID'");
    $course = mysqli_fetch_array($cq);
    $Course_Name = $course['Course_Name'] ?? '';
    $Course_Code = $course['Course_Code'] ?? '';

    /* ----------------------------------------------------------
        FETCH RESULTS — prepared statement (the original query
        interpolated $Fac_ID directly, which is an injection risk)
        ---------------------------------------------------------- */

    // $query = "SELECT
    //             r.Result_ID, r.Stud_ID, r.Exam_ID,
    //             r.Total_Marks, r.Marks_Obtained,
    //             r.Status AS Result_Status,
    //             r.Submitted_At,
    //             e.Exam_Name, e.Status AS Exam_Result_Status,
    //             c.Course_Name,
    //             s.Stud_Name, s.Stud_ID
    //           FROM results r
    //           JOIN exams e   ON r.Exam_ID = e.Exam_ID
    //           JOIN courses c ON e.Course_ID = c.Course_ID
    //           JOIN student s ON r.Stud_ID = s.Stud_ID
    //           WHERE e.Fac_ID = ?
    //           ORDER BY e.Exam_Name, s.Stud_Name";

    // $stmt = $con->prepare($query);
    // $stmt->bind_param("i", $Fac_ID);
    // $stmt->execute();
    // $run = $stmt->get_result();

    // $results = [];
    // while ($row = $run->fetch_assoc()) {
    //     $results[] = $row;
    // }

    // /* Group by exam so the page can render one card-section per exam rather than one long flat table — this is what actually makes it scannable for a faculty member checking on a specific exam. */

    // $byExam = [];
    // foreach ($results as $row) {
    //     $byExam[$row['Exam_ID']]['title']       = $row['Exam_Name'] ? $row['Exam_Name'] : 'Untitled Exam';
    //     $byExam[$row['Exam_ID']]['course']      = $row['Course_Name'] ? $row['Course_Name'] : 'Unknown Course';
    //     $byExam[$row['Exam_ID']]['published']   = (int)$row['Exam_Result_Status'] === 1;
    //     $byExam[$row['Exam_ID']]['rows'][]      = $row ? $row : null;
    // }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty - View Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <!-- <style>
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
    </style> -->
</head>

<body>
    <?php include '../Common/header.php'; ?>
    <?php include '../Common/faculty-sidebar.php'; ?>

    <main>
        <div class="dashboard-header">
            <h4 class="mb-0 fw-bold">
                <i class="ti ti-clipboard-plus me-2"></i>View Results
                <?php if ($Course_Name): ?>
                    <small class="fw-normal opacity-75 ms-2">
                        <?= htmlspecialchars($Course_Name) ?> (<?= htmlspecialchars($Course_Code) ?>)
                    </small>
                <?php endif; ?>
            </h4>
        </div>

        <div class="sub-main">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-2">
                    <span class="text-uppercase small fw-semibold text-muted">Exam Search</span>
                </div>
                <div class="card-body">
                    
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Academic Year <span class="text-danger">*</span></label>
                            <select class="form-control" name="Acad_Year" id="Acad_Year" required onchange="this.form.submit()">
                                <option value="" selected disabled>Select Academic Year</option>
                                <?php
                                    $q=mysqli_query($con,"select * from academic_year");
                                    while($row=mysqli_fetch_array($q)){
                                        $selected = (isset($_POST['Acad_Year']) && $_POST['Acad_Year'] == $row['AY_Name']) ? "selected" : "";
                                        echo "<option value='".$row['AY_Name']."' ".$selected."> ".$row['AY_Name']."</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <!-- Fetch Acad_Year from above select-->                         
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Exam Name <span class="text-danger">*</span></label>
                            <select class="form-control" name="Exam_ID" id="Exam_ID" required>
                                <option value="" selected disabled>Select Exam Name</option>
                                <?php
                                    $Acad_Year = isset($_POST['Acad_Year']) ? $_POST['Acad_Year'] : "";
                                    $q=mysqli_query($con,"select * from exams WHERE Fac_ID = '$Fac_ID' AND Course_ID = '$Course_ID' AND Acad_Year = '$Acad_Year'");
                                    while($row=mysqli_fetch_array($q)){
                                        $selected = (isset($_POST['Exam_ID']) && $_POST['Exam_ID'] == $row['Exam_ID']) ? "selected" : "";
                                        echo "<option value='".$row['Exam_ID']."' ".$selected."> ".$row['Exam_Name']."</option>";
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <input type="submit" class="btn btn-primary" name="Add" value="View Results">
                    </div>
                </form>
                </div>
            </div>
            <?php 
                if(isset($_POST['Add'])){
                    $Acad_Year=$_POST['Acad_Year'];
                    $Exam_ID=$_POST['Exam_ID'];
                    $query = "SELECT
                            r.Result_ID, r.Stud_ID, r.Exam_ID,
                            r.Total_Marks, r.Marks_Obtained,
                            r.Status AS Result_Status,
                            r.Submitted_At,
                            e.Exam_Name, e.Result_Status AS Exam_Result_Status,
                            c.Course_Name,  
                            s.Stud_Name, s.Stud_ID
                        FROM results r
                        JOIN exams e   ON r.Exam_ID = e.Exam_ID
                        JOIN courses c ON e.Course_ID = c.Course_ID
                        JOIN student s ON r.Stud_ID = s.Stud_ID
                        WHERE e.Fac_ID = ? AND e.Exam_ID = ? AND e.Acad_Year = ? 
                        ORDER BY e.Exam_Name, s.Stud_Name";
                    // echo $query;
                    // $q=mysqli_query($con, $query);
                    $stmt = $con->prepare($query);
                    $stmt->bind_param("sis", $Fac_ID,$Exam_ID,$Acad_Year);
                    $stmt->execute();
                    $run = $stmt->get_result();
                    $results = $run->fetch_all(MYSQLI_ASSOC);
                    $Exam_Name = $results[0]['Exam_Name'];
                    $Exam_Result_Status = $results[0]['Exam_Result_Status'];
            ?>
                    <?php if (empty($results)): ?>
                        <div class="text-center py-5 text-secondary">
                            <i class="bi bi-clipboard-x display-3 text-muted d-block mb-3"></i>
                            <p class="fs-6">No results yet. Once students submit, their scores will show up here grouped by exam.</p>
                        </div>
                    <?php else: ?>
                        <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                            <div class="card-header d-flex align-items-center justify-content-between py-3 px-4 text-white" style="background: #D1202D; border: none;">
                                <div>
                                    <h5 class="mb-0 fw-bold">
                                        <?= htmlspecialchars($Exam_Name) ?>
                                        <div class="text-white-50 mt-1"><?= count($results) ?> submission<?= count($results) == 1 ? '' : 's' ?></div></h5>
                                </div>
                                <div class="exam-head-actions">
                                    <!-- <span class="badge <?= $Exam_Result_Status ? 'bg-success' : 'bg-secondary' ?> me-3">
                                        <?= $Exam_Result_Status ? 'Published' : 'Draft' ?>
                                    </span> -->
                                    
                                    <button
                                        class="btn btn-sm fw-bold px-3 download-btn btn-outline-light text-white bg-transparent border-white-50"
                                        data-examid="<?= $Exam_ID ?>"
                                        data-examname="<?= $Exam_Name ?>">
                                        Export Results
                                    </button>
                                    
                                    <button
                                        class="btn btn-sm fw-bold px-3 publish-btn <?= $Exam_Result_Status ? 'btn-outline-light text-white bg-transparent border-white-50 btn-do-unpublish' : 'btn-light text-danger btn-do-publish' ?>"
                                        data-examid="<?= $Exam_ID ?>"
                                        data-status="<?= $Exam_Result_Status ?>">
                                        <?= $Exam_Result_Status ? 'Unpublish Results' : 'Publish Results' ?>
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-secondary" style="letter-spacing: 0.5px;">
                                        <tr>
                                            <th class="ps-4 py-3">Student</th>
                                            <th class="py-3">Score</th>
                                            <th class="py-3">Status</th>
                                            <th class="py-3">Submitted</th>
                                            <th class="pe-4 py-3 text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $row):
                                            $pct = $row['Total_Marks'] > 0
                                                ? round(($row['Marks_Obtained'] / $row['Total_Marks']) * 100, 1)
                                                : 0;
                                            $tier = $pct >= 75 ? 'high' : ($pct >= 40 ? 'mid' : 'low');
                                            $statusKey = strtolower(str_replace(' ', '_', $row['Result_Status']));
                                        ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['Stud_Name']) ?></div>
                                                    <div class="text-muted small"><?= htmlspecialchars($row['Stud_ID'] ?? '') ?></div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-baseline gap-1 mb-1" style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem;">
                                                        <span class="fw-bold text-dark"><?= htmlspecialchars($row['Marks_Obtained']) ?></span>
                                                        <span class="text-muted">/ <?= htmlspecialchars($row['Total_Marks']) ?> &middot; <?= $pct ?>%</span>
                                                    </div>
                                                    <div class="progress" style="height: 6px; width: 160px; background-color: var(--gray-200);">
                                                        <?php 
                                                            $bgClass = $pct >= 75 ? 'bg-success' : ($pct >= 40 ? 'bg-warning' : 'bg-danger');
                                                        ?>
                                                        <div class="progress-bar <?= $bgClass ?>" role="progressbar" style="width: <?= min($pct, 100) ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                        $badgeClass = 'bg-secondary';
                                                        if ($statusKey === 'graded') $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                                                        elseif ($statusKey === 'pending_review') $badgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                                        elseif ($statusKey === 'published') $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                                    ?>
                                                    <span class="badge rounded-pill <?= $badgeClass ?> px-3 py-1 text-capitalize fw-bold" style="font-size: 0.72rem;">
                                                        <?= htmlspecialchars(str_replace('_', ' ', $row['Result_Status'])) ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted small">
                                                    <?= $row['Submitted_At'] ? date('d M, g:i A', strtotime($row['Submitted_At'])) : '—' ?>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <div class="d-flex gap-2 justify-content-end align-items-center">
                                                        <a href="#" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 view-details"
                                                            data-studid="<?= $row['Stud_ID'] ?>"
                                                            data-examid="<?= $row['Exam_ID'] ?>">
                                                                <i class="bi bi-eye"></i> View
                                                        </a>
                                                        <?php if ($statusKey === 'pending_review'): ?>
                                                        <a href="#" class="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-1 enter-marks"
                                                            data-studid="<?= $row['Stud_ID'] ?>"
                                                            data-examid="<?= $row['Exam_ID'] ?>">
                                                                <i class="bi bi-pencil-square"></i> Grade
                                                        </a>
                                                        <?php endif; ?>
                                                        <!-- <a href="download-result.php?Stud_ID=<?= $row['Stud_ID'] ?>&Exam_ID=<?= $row['Exam_ID'] ?>"
                                                        class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center" target="_blank">
                                                            <i class="bi bi-download"></i>
                                                        </a> -->
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
            <?php
                }
            ?>   
        </div>

        <!-- Modal -->
        <div class="modal fade" id="resultModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="resultModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
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
                                btn.removeClass('btn-do-publish btn-light text-danger')
                                   .addClass('btn-do-unpublish btn-outline-light text-white bg-transparent border-white-50')
                                   .text('Unpublish Results').data('status', 1);
                                btn.closest('.exam-card-head').find('.publish-pill')
                                   .removeClass('is-draft').addClass('is-published').text('Published');
                            } else {
                                btn.removeClass('btn-do-unpublish btn-outline-light text-white bg-transparent border-white-50')
                                   .addClass('btn-do-publish btn-light text-danger')
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

            // Export exam results
            $(document).on('click', '.download-btn', function () {
                const examId = $(this).data('examid');
                const examName = $(this).data('examname');
                
                window.location.href = "export-results.php?Exam_ID=" + encodeURIComponent(examId) + "&Exam_Name=" + encodeURIComponent(examName);
            });
        });
    </script>

</body>


</html>