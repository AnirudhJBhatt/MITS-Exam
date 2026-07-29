<?php
    session_start();
    // Only LoginFaculty and LoginStudent has access
    // if (!isset($_SESSION["LoginFaculty"])) {
    //     http_response_code(403);
    //     echo '<div class="alert alert-danger">Unauthorized access. Please login.</div>';
    //     exit;
    // }

    require_once __DIR__ . "/../Connection/connection.php";

    if (!isset($_POST['Stud_ID']) || !isset($_POST['Exam_ID'])) {
        http_response_code(400);
        echo '<div class="alert alert-danger">Invalid request. Student ID or Exam ID missing.</div>';
        exit;
    }

    $Stud_ID = mysqli_real_escape_string($con, $_POST['Stud_ID']);
    $Exam_ID = mysqli_real_escape_string($con, $_POST['Exam_ID']);

    // 1. Fetch Student Name
    $studQuery = "SELECT Stud_Name FROM student WHERE Stud_ID = '$Stud_ID'";
    $studRun = mysqli_query($con, $studQuery);
    $studRow = mysqli_fetch_assoc($studRun);
    $Stud_Name = $studRow ? $studRow['Stud_Name'] : 'Unknown Student';

    // 2. Fetch Exam details
    $examQuery = "SELECT Exam_Name, Total_Marks FROM exams WHERE Exam_ID = '$Exam_ID'";
    $examRun = mysqli_query($con, $examQuery);
    $examRow = mysqli_fetch_assoc($examRun);
    $Exam_Name = $examRow ? $examRow['Exam_Name'] : 'Unknown Exam';

    // 3. Fetch Attempt summary from results table
    $resQuery = "SELECT Result_ID, Total_Marks, Marks_Obtained, Time_Taken_Sec, Violations, Auto_Submitted, Status, Submitted_At 
                FROM results 
                WHERE Stud_ID = '$Stud_ID' AND Exam_ID = '$Exam_ID'";
    $resRun = mysqli_query($con, $resQuery);
    $resultData = mysqli_fetch_assoc($resRun);

    if (!$resultData) {
        echo '<div class="alert alert-warning text-center my-4">No submission results found for this student.</div>';
        exit;
    }

    $Result_ID = $resultData['Result_ID'];
    $Obtained_Marks = $resultData['Marks_Obtained'];
    $Max_Marks_Exam = $resultData['Total_Marks'];
    $Time_Taken = $resultData['Time_Taken_Sec'];
    $Violations = $resultData['Violations'];
    $Auto_Submitted = $resultData['Auto_Submitted'];
    $Status = $resultData['Status'];
    $Submitted_At = $resultData['Submitted_At'];

    // Calculate percentage
    $percentage = $Max_Marks_Exam > 0 ? round(($Obtained_Marks / $Max_Marks_Exam) * 100, 1) : 0;

    // Format duration
    $minutes = floor($Time_Taken / 60);
    $seconds = $Time_Taken % 60;
    $durationStr = ($minutes > 0 ? $minutes . "m " : "") . $seconds . "s";

    // 4. Fetch Question & Answer details
    $qQuery = "SELECT 
                ra.Question_ID,
                ra.Question_Type,
                ra.Student_Answer,
                ra.Is_Correct,
                ra.Marks_Awarded,
                ra.Max_Marks,
                ra.Answer_Status,
                ra.Grader_Comment,
                eq.Question_Text,
                eq.Options_JSON,
                eq.Correct_Opt,
                eq.Rubric,
                eq.Word_Limit,
                eq.Answers_JSON,
                eq.Pairs_JSON,
                eq.Image_Path,
                eq.CO
            FROM result_answers ra
            JOIN exam_questions eq ON ra.Question_ID = eq.Question_ID
            WHERE ra.Result_ID = '$Result_ID'
            ORDER BY eq.Sort_Order, eq.Question_ID";
    $qRun = mysqli_query($con, $qQuery);
?>

<div class="container-fluid px-0">
    <!-- Header Summary Card -->
    <div class="card border-0 bg-light mb-4 shadow-sm">
        <div class="card-body p-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-7">
                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($Stud_ID) ?> - <?= htmlspecialchars($Stud_Name) ?></h5>
                    <h6 class="text-secondary mb-0 fw-semibold"><?= htmlspecialchars($Exam_Name) ?></h6>
                </div>
                <div class="col-md-5 text-md-end">
                    <div class="d-inline-block text-center bg-white p-3 rounded shadow-sm border">
                        <span class="d-block text-uppercase text-muted fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Score Obtained</span>
                        <h3 class="fw-bold text-danger mb-0" style="font-family: 'JetBrains Mono', monospace;">
                            <?= htmlspecialchars(round($Obtained_Marks, 2)) ?> <span class="fs-5 text-muted">/ <?= htmlspecialchars(round($Max_Marks_Exam, 2)) ?></span>
                        </h3>
                        <span class="badge rounded-pill bg-danger-subtle text-danger px-3 py-1 mt-1 fw-bold"><?= $percentage ?>%</span>
                    </div>
                </div>
            </div>
            
            <hr class="my-3 opacity-50">
            
            <div class="row g-3 text-center text-md-start">
                <div class="col-6 col-md-3">
                    <span class="text-muted d-block small">Status</span>
                    <?php
                        $badgeClass = 'bg-secondary';
                        if ($Status === 'graded') $badgeClass = 'bg-success text-white';
                        elseif ($Status === 'pending_review') $badgeClass = 'bg-warning text-dark';
                        elseif ($Status === 'published') $badgeClass = 'bg-info text-dark';
                    ?>
                    <span class="badge <?= $badgeClass ?> text-capitalize px-3 py-1 fw-bold mt-1">
                        <?= str_replace('_', ' ', $Status) ?>
                    </span>
                </div>
                <div class="col-6 col-md-3">
                    <span class="text-muted d-block small">Time Taken</span>
                    <strong class="d-block text-dark mt-1"><?= $durationStr ?></strong>
                </div>
                <div class="col-6 col-md-3">
                    <span class="text-muted d-block small">Violations</span>
                    <?php if ($Violations > 0): ?>
                        <span class="badge bg-danger px-3 py-1 fw-bold mt-1">
                            <?= $Violations ?> Warn<?= $Violations == 1 ? '' : 's' ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success-subtle text-success px-3 py-1 fw-bold mt-1">
                            None
                        </span>
                    <?php endif; ?>
                </div>
                <div class="col-6 col-md-3">
                    <span class="text-muted d-block small">Submitted At</span>
                    <strong class="d-block text-dark mt-1" style="font-size: 0.85rem;">
                        <?= $Submitted_At ? date('d M, g:i A', strtotime($Submitted_At)) : 'N/A' ?>
                        <?php if ($Auto_Submitted): ?>
                            <span class="text-danger ms-1 small" title="Auto-submitted due to timer expiration">(Auto)</span>
                        <?php endif; ?>
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Questions Details Title -->
    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
        <span class="text-muted small">Total Questions: <strong><?= mysqli_num_rows($qRun) ?></strong></span>
    </div>

    <!-- Loop through questions -->
    <?php 
    $sl = 1;
    while ($row = mysqli_fetch_assoc($qRun)):
        $qType = $row['Question_Type'];
        $marksAwarded = (float)$row['Marks_Awarded'];
        $maxMarks = (float)$row['Max_Marks'];
        $isCorrect = $row['Is_Correct'];
        $ansStatus = $row['Answer_Status'];
        
        // Determine border & badge styles based on grading
        if ($qType === 'open' && $isCorrect === null && $ansStatus === 'attempted') {
            $borderClass = 'border-warning';
            $badgeBg = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
            $statusText = 'Pending Review';
        } elseif ($ansStatus === 'unattempted' || $ansStatus === 'not') {
            $borderClass = 'border-secondary-subtle';
            $badgeBg = 'bg-light text-secondary border';
            $statusText = 'Unattempted';
        } elseif ($marksAwarded == $maxMarks) {
            $borderClass = 'border-success';
            $badgeBg = 'bg-success-subtle text-success border border-success-subtle';
            $statusText = 'Correct';
        } elseif ($marksAwarded > 0) {
            $borderClass = 'border-warning';
            $badgeBg = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
            $statusText = 'Partially Correct';
        } else {
            $borderClass = 'border-danger';
            $badgeBg = 'bg-danger-subtle text-danger border border-danger-subtle';
            $statusText = 'Incorrect';
        }
    ?>
        <div class="card mb-3 border-0 border-start border-4 <?= $borderClass ?> shadow-sm">
            <div class="card-body p-4">
                <!-- Question Top Header Details -->
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-secondary-subtle text-secondary-emphasis fw-bold">Q<?= $sl ?></span>
                        <span class="badge bg-light text-dark border px-2 py-1 text-uppercase small" style="font-size:0.7rem; font-weight:600;">
                            <?= $qType === 'mcq' ? 'MCQ' : ($qType === 'fill' ? 'Fill Blank' : ($qType === 'match' ? 'Matching' : 'Subjective')) ?>
                        </span>
                        <?php if (!empty($row['CO'])): ?>
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 small" style="font-size:0.7rem;">CO<?= htmlspecialchars($row['CO']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill <?= $badgeBg ?> px-3 py-1 font-monospace" style="font-size: 0.75rem;">
                            <?= $statusText ?>
                        </span>
                        <span class="fw-bold text-dark px-2 py-1 bg-light rounded border small" style="font-family: 'JetBrains Mono', monospace; font-size: 0.8rem;">
                            Score: <?= round($marksAwarded, 2) ?> / <?= round($maxMarks, 2) ?>
                        </span>
                    </div>
                </div>

                <!-- Question Text -->
                <div class="question-text mb-3 text-dark fw-medium lh-base text-break" style="font-size: 1rem; white-space: pre-line;">
                    <?= htmlspecialchars($row['Question_Text']) ?>
                </div>

                <!-- Attached image if exists -->
                <?php if (!empty($row['Image_Path'])): ?>
                    <div class="mb-3">
                        <img src="<?= htmlspecialchars($row['Image_Path']) ?>" class="img-fluid rounded border shadow-sm" style="max-height: 250px;" alt="Attached Image">
                    </div>
                <?php endif; ?>

                <div class="student-response-block border-top pt-3 mt-3">
                    <?php if ($qType === 'mcq'): 
                        $options = json_decode($row['Options_JSON'] ?? '[]', true) ?: [];
                        $studentSelected = json_decode($row['Student_Answer'] ?? 'null', true);
                        $correctOpt = $row['Correct_Opt'];
                    ?>
                        <div class="options-container">
                            <div class="row g-2">
                                <?php foreach ($options as $opt): 
                                    $letter = $opt['letter'] ?? '';
                                    $text = $opt['text'] ?? '';
                                    
                                    $optClass = 'border bg-white text-dark';
                                    $badge = '';
                                    
                                    // If student selected this option
                                    if ($studentSelected === $letter) {
                                        if ($letter === $correctOpt) {
                                            $optClass = 'border-success bg-success-subtle text-success-emphasis fw-bold';
                                            $badge = '<span class="badge bg-success ms-auto">Your Answer & Correct</span>';
                                        } else {
                                            $optClass = 'border-danger bg-danger-subtle text-danger-emphasis fw-bold';
                                            $badge = '<span class="badge bg-danger ms-auto">Your Answer (Incorrect)</span>';
                                        }
                                    } elseif ($letter === $correctOpt) {
                                        // This is the correct option but was NOT selected by student
                                        $optClass = 'border-success text-success-emphasis fw-semibold bg-white';
                                        $badge = '<span class="badge bg-success-subtle text-success border border-success ms-auto">Correct Answer</span>';
                                    }
                                ?>
                                    <div class="col-12">
                                        <div class="p-2 px-3 rounded d-flex align-items-center <?= $optClass ?>" style="font-size: 0.92rem; min-height: 42px;">
                                            <div class="d-flex align-items-center gap-2 flex-grow-1">
                                                <strong class="text-center rounded-circle border d-inline-flex align-items-center justify-content-center bg-light text-dark fw-bold" style="width:24px; height:24px; font-size:0.8rem;"><?= $letter ?></strong>
                                                <span class="text-break"><?= htmlspecialchars($text) ?></span>
                                            </div>
                                            <?= $badge ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <?php elseif ($qType === 'fill'): 
                        $studentSelected = json_decode($row['Student_Answer'] ?? 'null', true);
                        $acceptedAnswers = json_decode($row['Answers_JSON'] ?? '[]', true) ?: [];
                        $isCorrect = $row['Is_Correct'];
                    ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <span class="text-muted d-block small mb-1">Student's Answer:</span>
                                <div class="p-2 px-3 rounded border text-break <?= ($isCorrect ? 'bg-success-subtle text-success-emphasis border-success fw-bold' : (!empty($studentSelected) ? 'bg-danger-subtle text-danger-emphasis border-danger fw-bold' : 'bg-light text-muted')) ?>" style="font-size:0.95rem;">
                                    <?= !empty($studentSelected) ? htmlspecialchars($studentSelected) : '<i>No answer submitted</i>' ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted d-block small mb-1">Accepted Answers:</span>
                                <div class="p-2 px-3 rounded bg-light border text-dark" style="font-size:0.95rem;">
                                    <?= implode(', ', array_map('htmlspecialchars', $acceptedAnswers)) ?>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($qType === 'match'): 
                        $pairs = json_decode($row['Pairs_JSON'] ?? '[]', true) ?: [];
                        $studentSelected = json_decode($row['Student_Answer'] ?? '[]', true) ?: [];
                    ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle text-center mb-0 mt-2" style="font-size: 0.9rem;">
                                <thead class="table-light text-secondary">
                                    <tr>
                                        <th class="text-start ps-3" style="width: 35%;">Left Item</th>
                                        <th style="width: 30%;">Correct Match</th>
                                        <th style="width: 30%;">Student Match</th>
                                        <th style="width: 5%;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pairs as $idx => $pair): 
                                        $leftVal = $pair['a'] ?? '';
                                        $rightVal = $pair['b'] ?? '';
                                        
                                        // Student choice for this index
                                        $studentVal = $studentSelected[(string)$idx] ?? $studentSelected[$idx] ?? null;
                                        
                                        $matchCorrect = ($studentVal !== null && $studentVal === $rightVal);
                                    ?>
                                        <tr class="<?= $matchCorrect ? 'table-success-subtle text-success-emphasis' : ($studentVal === null ? 'text-muted' : 'table-danger-subtle text-danger-emphasis') ?>">
                                            <td class="text-start ps-3 fw-medium text-dark"><?= htmlspecialchars($leftVal) ?></td>
                                            <td><?= htmlspecialchars($rightVal) ?></td>
                                            <td class="fw-semibold"><?= $studentVal !== null ? htmlspecialchars($studentVal) : '—' ?></td>
                                            <td>
                                                <?php if ($studentVal === null): ?>
                                                    <span class="text-muted">—</span>
                                                <?php elseif ($matchCorrect): ?>
                                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php elseif ($qType === 'open'): 
                        $studentSelected = json_decode($row['Student_Answer'] ?? 'null', true);
                        $rubric = $row['Rubric'];
                        $wordLimit = $row['Word_Limit'];
                        $wordCount = !empty($studentSelected) ? str_word_count($studentSelected) : 0;
                    ?>
                        <div class="mb-3">
                            <span class="text-muted d-block small mb-1">Student's Answer:</span>
                            <div class="p-3 rounded border text-dark bg-white shadow-inner text-break" style="font-size:0.95rem; white-space: pre-line; min-height: 80px; border-left: 3px solid #dee2e6 !important;">
                                <?= !empty($studentSelected) ? htmlspecialchars($studentSelected) : '<i>No answer submitted</i>' ?>
                            </div>
                            <?php if ($wordLimit > 0): ?>
                                <small class="text-muted mt-1 d-block">
                                    <i class="bi bi-file-earmark-font me-1"></i>Word Count: <strong><?= $wordCount ?></strong> / max <?= $wordLimit ?> words.
                                </small>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($rubric)): ?>
                            <div class="mb-2 p-3 bg-light rounded border text-secondary" style="font-size: 0.88rem;">
                                <strong class="text-dark small d-block mb-1"><i class="bi bi-clipboard2-check me-1"></i>Grading Rubric:</strong>
                                <?= htmlspecialchars($rubric) ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Grader Comments -->
                    <?php if (!empty($row['Grader_Comment'])): ?>
                        <div class="mt-3 p-2 px-3 rounded bg-light-subtle border border-light-subtle text-secondary" style="font-size: 0.9rem;">
                            <strong class="text-dark small d-block mb-1"><i class="bi bi-chat-right-text me-1 text-primary"></i>Faculty Comment:</strong>
                            <span class="fst-italic text-dark"><?= htmlspecialchars($row['Grader_Comment']) ?></span>
                            <?php if (!empty($row['Graded_By'])): ?>
                                <small class="text-muted d-block mt-1 text-end">— Graded by <?= htmlspecialchars($row['Graded_By']) ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php 
    $sl++;
    endwhile; 
    ?>
</div>
