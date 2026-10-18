<?php
/**
 * MITS Assessment Portal - Itemized Result Details Modal Provider
 * Fetches question-by-question responses, rubrics, proctor telemetry, and faculty comments.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/../Common/auth.php";

check_role(['Faculty', 'Admin', 'DeptAdmin', 'Student']);

$currentUserRole = $_SESSION['Role'] ?? '';
$currentUserId = $_SESSION['user_id'] ?? $_SESSION['LoginStudent'] ?? $_SESSION['LoginFaculty'] ?? '';

if (!isset($_POST['Stud_ID']) || !isset($_POST['Exam_ID'])) {
    http_response_code(400);
    echo '<div class="alert alert-danger">Invalid request. Student ID or Exam ID missing.</div>';
    exit;
}

$Stud_ID = trim($_POST['Stud_ID']);
$Exam_ID = (int)$_POST['Exam_ID'];

// Security check: If student, verify they are only requesting their own result
if ($currentUserRole === 'Student' && $Stud_ID !== $currentUserId) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Access denied. You can only view your own result scorecard.</div>';
    exit;
}

// 1. Fetch Student Profile
$studStmt = $pdo->prepare("
    SELECT s.Stud_Name, s.Stud_Email, d.Dept_Name, p.Prog_Name 
    FROM student s
    LEFT JOIN department d ON s.Stud_Dept = d.Dept_ID
    LEFT JOIN programmes p ON s.Stud_Branch = p.Prog_ID
    WHERE s.Stud_ID = ? LIMIT 1
");
$studStmt->execute([$Stud_ID]);
$student = $studStmt->fetch();
$Stud_Name = $student['Stud_Name'] ?? 'Candidate';

// 2. Fetch Exam Details
$examStmt = $pdo->prepare("
    SELECT e.Exam_Name, e.Total_Marks, e.Duration, c.Course_Code, c.Course_Name
    FROM exams e
    LEFT JOIN courses c ON e.Course_ID = c.Course_ID
    WHERE e.Exam_ID = ? LIMIT 1
");
$examStmt->execute([$Exam_ID]);
$exam = $examStmt->fetch();
$Exam_Name = $exam['Exam_Name'] ?? 'Examination';

// 3. Fetch Attempt Summary from results
$resStmt = $pdo->prepare("
    SELECT Result_ID, Total_Marks, Marks_Obtained, Percentage, Time_Taken_Sec, Violations, Auto_Submitted, Status, Submitted_At, Graded_At
    FROM results 
    WHERE Stud_ID = ? AND Exam_ID = ? LIMIT 1
");
$resStmt->execute([$Stud_ID, $Exam_ID]);
$resultData = $resStmt->fetch();

if (!$resultData) {
    echo '<div class="alert alert-warning text-center my-4">
        <i class="bi bi-info-circle fs-2 text-warning d-block mb-2"></i>
        No submission results found for this candidate.
    </div>';
    exit;
}

$Result_ID = (int)$resultData['Result_ID'];
$Obtained_Marks = (float)$resultData['Marks_Obtained'];
$Max_Marks_Exam = (float)$resultData['Total_Marks'];
$Percentage = round((float)$resultData['Percentage'], 1);
$Time_Taken = (int)$resultData['Time_Taken_Sec'];
$Violations = (int)$resultData['Violations'];
$Auto_Submitted = (bool)$resultData['Auto_Submitted'];
$Status = $resultData['Status'];
$Submitted_At = $resultData['Submitted_At'];

$minutes = floor($Time_Taken / 60);
$seconds = $Time_Taken % 60;
$durationStr = "{$minutes}m {$seconds}s";

// 4. Fetch Question & Answer Details
$qStmt = $pdo->prepare("
    SELECT 
        ra.Question_ID,
        ra.Question_Type,
        ra.Student_Answer,
        ra.Is_Correct,
        ra.Marks_Awarded,
        ra.Max_Marks,
        ra.Answer_Status,
        ra.Grader_Comment,
        ra.Graded_By,
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
    WHERE ra.Result_ID = ?
    ORDER BY eq.Sort_Order ASC, eq.Question_ID ASC
");
$qStmt->execute([$Result_ID]);
$answers = $qStmt->fetchAll();
?>

<div class="result-details-view">
    <!-- Header Executive Card -->
    <div class="card border-0 bg-light rounded-4 mb-4 shadow-sm">
        <div class="card-body p-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-7">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-danger bg-opacity-10 text-danger fw-bold font-monospace">ROLL: <?= htmlspecialchars($Stud_ID) ?></span>
                        <span class="badge bg-secondary-subtle text-secondary fw-semibold"><?= htmlspecialchars($exam['Course_Code'] ?? '') ?></span>
                    </div>
                    <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($Stud_Name) ?></h4>
                    <p class="text-secondary small mb-0">
                        <i class="bi bi-file-earmark-text-fill text-danger me-1"></i><?= htmlspecialchars($Exam_Name) ?> &bull; 
                        Submitted: <?= date('d M Y, h:i A', strtotime($Submitted_At)) ?>
                    </p>
                </div>
                <div class="col-md-5 text-md-end">
                    <div class="d-inline-flex flex-column align-items-md-end bg-white p-3 rounded-4 shadow-sm border">
                        <span class="text-uppercase text-muted fw-bold small" style="letter-spacing: 0.5px; font-size: 0.68rem;">Score Achieved</span>
                        <div class="d-flex align-items-baseline gap-2">
                            <h3 class="fw-bold text-danger mb-0 font-monospace"><?= $Obtained_Marks ?></h3>
                            <span class="text-muted fs-6 fw-semibold">/ <?= $Max_Marks_Exam ?></span>
                            <span class="badge <?= $Percentage >= 75 ? 'bg-success' : ($Percentage >= 40 ? 'bg-primary' : 'bg-danger') ?> rounded-pill px-2">
                                <?= $Percentage ?>%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Telemetry Badges Strip -->
            <div class="d-flex flex-wrap align-items-center gap-3 pt-3 mt-3 border-top text-muted small">
                <span><i class="bi bi-stopwatch text-danger me-1"></i>Duration: <strong><?= $durationStr ?></strong></span>
                <span>
                    <i class="bi bi-shield-shaded me-1 text-<?= $Violations > 0 ? 'danger' : 'success' ?>"></i>
                    Violations: <strong class="<?= $Violations > 0 ? 'text-danger' : 'text-success' ?>"><?= $Violations ?></strong>
                </span>
                <?php if ($Auto_Submitted): ?>
                    <span class="badge bg-danger bg-opacity-10 text-danger px-2"><i class="bi bi-exclamation-triangle me-1"></i>Auto-Submitted</span>
                <?php endif; ?>
                <span class="ms-md-auto">
                    Status: <span class="badge bg-<?= ($Status === 'graded') ? 'success' : 'warning text-dark' ?> fw-semibold text-uppercase"><?= str_replace('_', ' ', $Status) ?></span>
                </span>
            </div>
        </div>
    </div>

    <!-- Question Answers Breakdown -->
    <h6 class="fw-bold mb-3 text-dark text-uppercase small" style="letter-spacing: 0.5px;">
        <i class="bi bi-card-checklist me-2"></i>Itemized Response Breakdown (<?= count($answers) ?> Questions)
    </h6>

    <div class="d-flex flex-column gap-3">
        <?php foreach ($answers as $idx => $row): 
            $qNum = $idx + 1;
            $qType = $row['Question_Type'];
            $awarded = (float)$row['Marks_Awarded'];
            $maxMarks = (float)$row['Max_Marks'];
            $isCorrect = $row['Is_Correct'];
            $borderClass = ($isCorrect === 1) ? 'border-success' : ($isCorrect === 0 ? 'border-danger' : 'border-warning');
        ?>
            <div class="card border rounded-4 shadow-sm <?= $borderClass ?>" style="border-left-width: 5px !important;">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold text-dark font-monospace">Q<?= $qNum ?></span>
                        <span class="badge bg-secondary-subtle text-secondary text-uppercase fw-semibold" style="font-size: 0.7rem;"><?= htmlspecialchars($qType) ?></span>
                        <?php if (!empty($row['CO'])): ?>
                            <span class="badge bg-danger bg-opacity-10 text-danger fw-bold">CO<?= $row['CO'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold font-monospace <?= ($awarded >= $maxMarks) ? 'text-success' : ($awarded > 0 ? 'text-primary' : 'text-danger') ?>">
                            Score: <?= $awarded ?> / <?= $maxMarks ?>
                        </span>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Question Diagram if present -->
                    <?php if (!empty($row['Image_Path'])): ?>
                        <div class="mb-3 text-center">
                            <img src="../<?= htmlspecialchars($row['Image_Path']) ?>" class="img-fluid rounded border shadow-sm" style="max-height: 220px;" alt="Question Diagram">
                        </div>
                    <?php endif; ?>

                    <!-- Question Text -->
                    <div class="text-dark fw-medium mb-3" style="line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($row['Question_Text'])) ?>
                    </div>

                    <!-- MCQ View -->
                    <?php if ($qType === 'mcq' || $qType === 'multiselect'): 
                        $rawOptions = json_decode($row['Options_JSON'] ?? '{}', true) ?: [];
                        $normalizedOptions = [];
                        $defaultLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                        $idx = 0;
                        foreach ($rawOptions as $k => $v) {
                            if (is_array($v)) {
                                $let = $v['letter'] ?? $v['option'] ?? $v['key'] ?? ($defaultLetters[$idx] ?? (string)$idx);
                                $txt = $v['text'] ?? $v['value'] ?? $v['opt'] ?? '';
                                $normalizedOptions[(string)$let] = is_scalar($txt) ? (string)$txt : json_encode($txt);
                            } else {
                                $let = (is_string($k) && !is_numeric($k)) ? $k : ($defaultLetters[$idx] ?? (string)$k);
                                $normalizedOptions[(string)$let] = is_scalar($v) ? (string)$v : json_encode($v);
                            }
                            $idx++;
                        }

                        $studentSelected = json_decode($row['Student_Answer'] ?? 'null', true);
                        if ($studentSelected === null && is_string($row['Student_Answer'])) {
                            $studentSelected = $row['Student_Answer'];
                        }
                        $correctOpt = $row['Correct_Opt'] ?? '';
                        $acceptedArr = json_decode($row['Answers_JSON'] ?? '[]', true) ?: [];
                        if (empty($acceptedArr) && !empty($correctOpt)) {
                            $decodedCorr = json_decode($correctOpt, true);
                            $acceptedArr = is_array($decodedCorr) ? $decodedCorr : [$correctOpt];
                        }
                    ?>
                        <div class="options-display d-flex flex-column gap-2 mb-2">
                            <?php foreach ($normalizedOptions as $optKey => $optText): 
                                $optKeyStr = (string)$optKey;
                                $optTextStr = (string)$optText;

                                $isStudentChoice = false;
                                if (is_array($studentSelected)) {
                                    $isStudentChoice = in_array($optKeyStr, array_map('strval', $studentSelected)) || in_array($optTextStr, $studentSelected);
                                } elseif ($studentSelected !== null && $studentSelected !== '') {
                                    $isStudentChoice = ((string)$studentSelected === $optKeyStr || (string)$studentSelected === $optTextStr);
                                }

                                $isRight = false;
                                if ($qType === 'mcq') {
                                    $isRight = ($optKeyStr === (string)$correctOpt || $optTextStr === (string)$correctOpt);
                                } else {
                                    $isRight = in_array($optKeyStr, array_map('strval', $acceptedArr)) || in_array($optTextStr, $acceptedArr);
                                }

                                $bgStyle = 'bg-light text-secondary border';
                                $badge = '';
                                if ($isStudentChoice && $isRight) {
                                    $bgStyle = 'bg-success bg-opacity-10 border-success text-success fw-bold';
                                    $badge = '<span class="badge bg-success ms-auto"><i class="bi bi-check2"></i> Student Correct Choice</span>';
                                } elseif ($isStudentChoice && !$isRight) {
                                    $bgStyle = 'bg-danger bg-opacity-10 border-danger text-danger fw-bold';
                                    $badge = '<span class="badge bg-danger ms-auto"><i class="bi bi-x"></i> Student Choice</span>';
                                } elseif ($isRight) {
                                    $bgStyle = 'bg-success bg-opacity-10 border-success text-success';
                                    $badge = '<span class="badge bg-outline-success border border-success text-success ms-auto">Correct Answer</span>';
                                }
                            ?>
                                <div class="p-2 px-3 rounded-3 d-flex align-items-center gap-2 <?= $bgStyle ?>">
                                    <span class="badge bg-white text-dark border font-monospace"><?= htmlspecialchars($optKeyStr) ?></span>
                                    <span><?= htmlspecialchars($optTextStr) ?></span>
                                    <?= $badge ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <!-- Fill in Blanks -->
                    <?php elseif ($qType === 'fill'): 
                        $studentAnswer = json_decode($row['Student_Answer'] ?? 'null', true);
                        if ($studentAnswer === null && is_string($row['Student_Answer'])) $studentAnswer = $row['Student_Answer'];
                        $acceptedAnswers = json_decode($row['Answers_JSON'] ?? '[]', true) ?: [];
                    ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <span class="text-muted small d-block mb-1">Student's Input:</span>
                                <div class="p-2 px-3 rounded-3 bg-light border font-monospace fw-bold <?= $row['Is_Correct'] ? 'text-success border-success' : 'text-danger border-danger' ?>">
                                    <?= !empty($studentAnswer) ? (is_array($studentAnswer) ? implode(', ', array_map(function($x){ return is_scalar($x) ? (string)$x : json_encode($x); }, $studentAnswer)) : htmlspecialchars(is_scalar($studentAnswer) ? (string)$studentAnswer : json_encode($studentAnswer))) : '<span class="text-muted fst-italic">No input</span>' ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block mb-1">Accepted Answers:</span>
                                <div class="p-2 px-3 rounded-3 bg-white border text-dark font-monospace">
                                    <?= implode(', ', array_map(function($x){ return htmlspecialchars(is_scalar($x) ? (string)$x : json_encode($x)); }, $acceptedAnswers)) ?>
                                </div>
                            </div>
                        </div>

                    <!-- Match the Following -->
                    <?php elseif ($qType === 'match'): 
                        $pairs = json_decode($row['Pairs_JSON'] ?? '[]', true) ?: [];
                        $studentSelected = json_decode($row['Student_Answer'] ?? '[]', true) ?: [];
                    ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered text-center align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="text-start ps-3">Column A (Premise)</th>
                                        <th>Correct Match</th>
                                        <th>Student Choice</th>
                                        <th>Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pairs as $pIdx => $pair): 
                                        $left = $pair['left'] ?? $pair['a'] ?? '';
                                        $right = $pair['right'] ?? $pair['b'] ?? '';
                                        $userChoice = $studentSelected[(string)$pIdx] ?? $studentSelected[$pIdx] ?? null;
                                        $matchCorrect = ($userChoice !== null && (string)$userChoice === (string)$pIdx);
                                        $userChoiceText = $userChoice !== null ? ($pairs[$userChoice]['right'] ?? $pairs[$userChoice]['b'] ?? $userChoice) : '—';
                                    ?>
                                        <tr class="<?= $matchCorrect ? 'table-success' : ($userChoice === null ? '' : 'table-danger') ?>">
                                            <td class="text-start ps-3 fw-medium"><?= htmlspecialchars(is_scalar($left) ? (string)$left : json_encode($left)) ?></td>
                                            <td><?= htmlspecialchars(is_scalar($right) ? (string)$right : json_encode($right)) ?></td>
                                            <td class="font-monospace fw-bold"><?= htmlspecialchars(is_scalar($userChoiceText) ? (string)$userChoiceText : json_encode($userChoiceText)) ?></td>
                                            <td>
                                                <?= $matchCorrect ? '<i class="bi bi-check-circle-fill text-success fs-5"></i>' : '<i class="bi bi-x-circle-fill text-danger fs-5"></i>' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <!-- Open Ended -->
                    <?php elseif ($qType === 'open'): 
                        $studentAnswer = json_decode($row['Student_Answer'] ?? 'null', true);
                        if ($studentAnswer === null && is_string($row['Student_Answer'])) $studentAnswer = $row['Student_Answer'];
                        $rubric = $row['Rubric'];
                        $wordLimit = $row['Word_Limit'];
                        $words = is_string($studentAnswer) ? str_word_count($studentAnswer) : 0;
                    ?>
                        <div class="mb-3">
                            <label class="form-label small text-muted text-uppercase fw-bold mb-1">Student's Response:</label>
                            <div class="p-3 rounded-3 bg-white border text-dark font-monospace text-break" style="min-height: 80px; white-space: pre-wrap;">
                                <?= !empty($studentAnswer) ? htmlspecialchars(is_scalar($studentAnswer) ? (string)$studentAnswer : json_encode($studentAnswer, JSON_PRETTY_PRINT)) : '<span class="text-muted fst-italic">No answer submitted.</span>' ?>
                            </div>
                            <?php if ($wordLimit > 0): ?>
                                <small class="text-muted mt-1 d-block">
                                    Word count: <strong><?= $words ?></strong> / max <?= $wordLimit ?> words
                                </small>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($rubric)): ?>
                            <div class="p-3 rounded-3 bg-light border-start border-4 border-primary text-secondary small mb-2">
                                <strong class="text-primary d-block mb-1"><i class="bi bi-journal-bookmark me-1"></i>Grading Rubric Guidance:</strong>
                                <?= nl2br(htmlspecialchars($rubric)) ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Evaluator Comments -->
                    <?php if (!empty($row['Grader_Comment'])): ?>
                        <div class="mt-3 p-3 rounded-3 bg-light border text-secondary small">
                            <strong class="text-dark d-block mb-1"><i class="bi bi-chat-quote-fill me-1 text-danger"></i>Faculty Feedback:</strong>
                            <span class="text-dark fst-italic"><?= htmlspecialchars($row['Grader_Comment']) ?></span>
                            <?php if (!empty($row['Graded_By'])): ?>
                                <div class="text-end text-muted mt-1">— Evaluated by <?= htmlspecialchars($row['Graded_By']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
