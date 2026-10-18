<?php
/**
 * MITS Assessment Portal - Rubric Grading & Evaluation Workbench
 * Allows faculty to grade open-ended questions, review rubrics, add grader commentary,
 * and adjust scores with real-time recalculation of student totals.
 */
session_start();
require_once "../Connection/connection.php";
require_once "../Common/auth.php";

check_role(['Faculty', 'Admin']);

$fac_name = $_SESSION['Faculty_Name'] ?? $_SESSION['faculty_name'] ?? 'Faculty Evaluator';

$Stud_ID = $_POST['Stud_ID'] ?? $_GET['Stud_ID'] ?? '';
$Exam_ID = (int)($_POST['Exam_ID'] ?? $_GET['Exam_ID'] ?? 0);

if (empty($Stud_ID) || $Exam_ID <= 0) {
    http_response_code(400);
    echo '<div class="alert alert-danger">Invalid request. Student ID or Exam ID missing.</div>';
    exit;
}

// ==============================================================
// MODE B: PERSIST MARKS & RUBRIC EVALUATION
// ==============================================================
if (isset($_POST['action']) && $_POST['action'] === 'save' || (isset($_POST['save']) && $_POST['save'] == '1')) {
    header('Content-Type: application/json; charset=utf-8');

    // Fetch Result Header
    $rStmt = $pdo->prepare("SELECT Result_ID, Total_Marks FROM results WHERE Stud_ID = ? AND Exam_ID = ? LIMIT 1");
    $rStmt->execute([$Stud_ID, $Exam_ID]);
    $resHeader = $rStmt->fetch();

    if (!$resHeader) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No result attempt found for this student.']);
        exit;
    }

    $resultId = (int)$resHeader['Result_ID'];
    $examTotalMarks = (float)$resHeader['Total_Marks'];

    $marksInput = isset($_POST['marks']) && is_array($_POST['marks']) ? $_POST['marks'] : [];
    $commentsInput = isset($_POST['comments']) && is_array($_POST['comments']) ? $_POST['comments'] : [];

    try {
        $pdo->beginTransaction();

        $updateAnsStmt = $pdo->prepare("
            UPDATE result_answers 
            SET Marks_Awarded = ?, 
                Grader_Comment = ?, 
                Graded_By = ?, 
                Is_Correct = ?,
                Answer_Status = 'answered'
            WHERE Result_ID = ? AND Question_ID = ?
        ");

        // Fetch question max marks to prevent over-awarding
        $maxStmt = $pdo->prepare("SELECT Question_ID, Marks, Question_Type FROM exam_questions WHERE Exam_ID = ?");
        $maxStmt->execute([$Exam_ID]);
        $questionsMeta = [];
        while ($qm = $maxStmt->fetch()) {
            $questionsMeta[$qm['Question_ID']] = [
                'marks' => (float)$qm['Marks'],
                'type' => $qm['Question_Type']
            ];
        }

        foreach ($marksInput as $key => $val) {
            $qid = (int)str_replace('marks_', '', $key);
            if (!isset($questionsMeta[$qid])) continue;

            $maxAllowed = $questionsMeta[$qid]['marks'];
            $awarded = max(0.0, min((float)$val, $maxAllowed));
            $comment = trim($commentsInput['comment_' . $qid] ?? '');

            // Determine Is_Correct flag
            $isCorrect = null;
            if ($questionsMeta[$qid]['type'] !== 'open') {
                $isCorrect = ($awarded >= $maxAllowed) ? 1 : ($awarded > 0 ? null : 0);
            } else {
                $isCorrect = ($awarded >= ($maxAllowed * 0.5)) ? 1 : 0;
            }

            $updateAnsStmt->execute([
                $awarded,
                $comment ?: null,
                $fac_name,
                $isCorrect,
                $resultId,
                $qid
            ]);
        }

        // Recalculate total obtained marks
        $calcStmt = $pdo->prepare("SELECT SUM(Marks_Awarded) FROM result_answers WHERE Result_ID = ?");
        $calcStmt->execute([$resultId]);
        $newObtained = (float)$calcStmt->fetchColumn();

        $pct = $examTotalMarks > 0 ? round(($newObtained / $examTotalMarks) * 100, 2) : 0.0;

        // Check if all questions are now evaluated
        $pendingCheck = $pdo->prepare("
            SELECT COUNT(*) FROM result_answers 
            WHERE Result_ID = ? AND Question_Type = 'open' AND Marks_Awarded IS NULL
        ");
        $pendingCheck->execute([$resultId]);
        $pendingOpen = (int)$pendingCheck->fetchColumn();
        $newStatus = ($pendingOpen > 0) ? 'pending_review' : 'graded';

        $updateResStmt = $pdo->prepare("
            UPDATE results 
            SET Marks_Obtained = ?, Status = ?, Graded_At = NOW() 
            WHERE Result_ID = ?
        ");
        $updateResStmt->execute([$newObtained, $newStatus, $resultId]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Evaluation successfully saved and scores updated.',
            'marks_obtained' => $newObtained,
            'percentage' => $pct,
            'status' => $newStatus
        ]);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save evaluation: ' . $e->getMessage()]);
        exit;
    }
}

// ==============================================================
// MODE A: LOAD INTERACTIVE RUBRIC EVALUATION WORKBENCH
// ==============================================================

// 1. Fetch Student & Exam Details
$sStmt = $pdo->prepare("SELECT Stud_Name, Stud_Email FROM student WHERE Stud_ID = ? LIMIT 1");
$sStmt->execute([$Stud_ID]);
$stud = $sStmt->fetch();
$stud_name = $stud['Stud_Name'] ?? 'Unknown Student';

$eStmt = $pdo->prepare("SELECT Exam_Name, Total_Marks FROM exams WHERE Exam_ID = ? LIMIT 1");
$eStmt->execute([$Exam_ID]);
$exam = $eStmt->fetch();
$exam_name = $exam['Exam_Name'] ?? 'Examination';

// 2. Fetch Result Attempt
$rStmt = $pdo->prepare("SELECT Result_ID, Marks_Obtained, Total_Marks, Status FROM results WHERE Stud_ID = ? AND Exam_ID = ? LIMIT 1");
$rStmt->execute([$Stud_ID, $Exam_ID]);
$resRow = $rStmt->fetch();

if (!$resRow) {
    echo '<div class="alert alert-warning text-center py-4">
        <i class="bi bi-exclamation-triangle-fill fs-2 d-block text-warning mb-2"></i>
        <strong>No submission found.</strong> This candidate has not attempted this examination yet.
    </div>';
    exit;
}

$resultId = (int)$resRow['Result_ID'];

// 3. Fetch All Question Answers with Rubrics and CO mappings
$ansStmt = $pdo->prepare("
    SELECT 
        ra.Answer_ID, ra.Question_ID, ra.Question_Type, ra.Student_Answer, 
        ra.Marks_Awarded, ra.Max_Marks, ra.Grader_Comment, ra.Is_Correct, ra.Answer_Status,
        eq.Question_Text, eq.Rubric, eq.Word_Limit, eq.CO, eq.Correct_Opt, eq.Answers_JSON, eq.Pairs_JSON, eq.Options_JSON
    FROM result_answers ra
    JOIN exam_questions eq ON ra.Question_ID = eq.Question_ID
    WHERE ra.Result_ID = ?
    ORDER BY eq.Sort_Order ASC, eq.Question_ID ASC
");
$ansStmt->execute([$resultId]);
$answers = $ansStmt->fetchAll();

if (empty($answers)) {
    echo '<div class="alert alert-info text-center py-4">No itemized question records found for this attempt.</div>';
    exit;
}
?>

<div class="evaluation-workbench p-1">
    <!-- Header Banner -->
    <div class="card border-0 bg-light rounded-4 mb-4 shadow-sm">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="badge bg-danger bg-opacity-10 text-danger fw-bold px-2 py-1 mb-1">CANDIDATE EVALUATION</span>
                    <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($stud_name) ?> <span class="text-muted fw-normal font-monospace">(<?= htmlspecialchars($Stud_ID) ?>)</span></h5>
                    <p class="text-secondary small mb-0"><i class="bi bi-file-earmark-text me-1 text-danger"></i><?= htmlspecialchars($exam_name) ?></p>
                </div>
                <div class="text-md-end">
                    <span class="small text-muted d-block text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Current Score</span>
                    <h4 class="fw-bold text-danger mb-0 font-monospace" id="workbench-live-total">
                        <?= (float)$resRow['Marks_Obtained'] ?> <span class="text-muted fs-6 fw-normal">/ <?= (float)$resRow['Total_Marks'] ?></span>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Evaluation Form -->
    <form id="evaluationForm" method="POST">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="Stud_ID" value="<?= htmlspecialchars($Stud_ID) ?>">
        <input type="hidden" name="Exam_ID" value="<?= $Exam_ID ?>">

        <div class="d-flex flex-column gap-3">
            <?php foreach ($answers as $idx => $ans): 
                $qNum = $idx + 1;
                $qType = $ans['Question_Type'];
                $maxMarks = (float)$ans['Max_Marks'];
                $awarded = $ans['Marks_Awarded'] !== null ? (float)$ans['Marks_Awarded'] : 0.0;
                $comment = $ans['Grader_Comment'] ?? '';
                $rubric = $ans['Rubric'] ?? '';
                $studentRaw = $ans['Student_Answer'];
                $studentDecoded = json_decode($studentRaw ?? 'null', true);
                if ($studentDecoded === null && is_string($studentRaw)) {
                    $studentDecoded = $studentRaw;
                }
                $isOpen = ($qType === 'open');
            ?>
                <div class="card border rounded-4 shadow-sm <?= $isOpen ? 'border-primary border-opacity-50' : 'border-light-subtle' ?>">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-danger font-monospace fs-6">Q<?= $qNum ?></span>
                            <span class="badge bg-secondary-subtle text-secondary text-uppercase fw-bold" style="font-size: 0.7rem;"><?= htmlspecialchars($qType) ?></span>
                            <?php if (!empty($ans['CO'])): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger fw-bold">CO<?= $ans['CO'] ?></span>
                            <?php endif; ?>
                            <?php if ($isOpen): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold"><i class="bi bi-pencil-square me-1"></i>Manual Rubric Grading</span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted">Max Marks: <strong class="text-dark font-monospace"><?= $maxMarks ?></strong></span>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <!-- Question Text -->
                        <div class="fw-medium text-dark mb-3" style="font-size: 0.98rem; line-height: 1.6;">
                            <?= nl2br(htmlspecialchars($ans['Question_Text'])) ?>
                        </div>

                        <!-- Rubric note if open-ended -->
                        <?php if ($isOpen && !empty($rubric)): ?>
                            <div class="p-3 mb-3 rounded-3 bg-light border-start border-4 border-primary text-secondary small">
                                <strong class="text-primary d-block mb-1"><i class="bi bi-journal-check me-1"></i>Official Grading Rubric:</strong>
                                <?= nl2br(htmlspecialchars($rubric)) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Candidate's Response Box -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted mb-1 text-uppercase" style="letter-spacing: 0.5px;">
                                <i class="bi bi-person-fill me-1"></i>Candidate's Response:
                            </label>
                            <div class="p-3 rounded-3 bg-light border text-break font-monospace" style="font-size: 0.92rem; white-space: pre-wrap; min-height: 50px;">
                                <?php 
                                    if ($studentDecoded === null || $studentDecoded === '') {
                                        echo '<span class="text-muted fst-italic">No response submitted for this question.</span>';
                                    } elseif (is_array($studentDecoded)) {
                                        echo htmlspecialchars(json_encode($studentDecoded, JSON_PRETTY_PRINT));
                                    } else {
                                        echo htmlspecialchars($studentDecoded);
                                    }
                                ?>
                            </div>
                            <?php if ($isOpen && !empty($ans['Word_Limit'])): 
                                $words = is_string($studentDecoded) ? str_word_count($studentDecoded) : 0;
                            ?>
                                <small class="text-muted mt-1 d-block">
                                    Word count: <strong><?= $words ?></strong> / max <?= $ans['Word_Limit'] ?> words
                                </small>
                            <?php endif; ?>
                        </div>

                        <!-- Grading Inputs Row -->
                        <div class="row g-3 align-items-center bg-white p-3 rounded-3 border">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    Marks Awarded <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-award"></i></span>
                                    <input type="number" 
                                           step="0.5" 
                                           min="0" 
                                           max="<?= $maxMarks ?>" 
                                           class="form-control fw-bold font-monospace rub-marks-input" 
                                           name="marks[marks_<?= $ans['Question_ID'] ?>]" 
                                           value="<?= $awarded ?>" 
                                           data-max="<?= $maxMarks ?>"
                                           required>
                                    <span class="input-group-text bg-light small text-muted">/ <?= $maxMarks ?></span>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    Faculty Evaluator Commentary / Rubric Notes
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-chat-left-text"></i></span>
                                    <input type="text" 
                                           class="form-control" 
                                           name="comments[comment_<?= $ans['Question_ID'] ?>]" 
                                           placeholder="e.g. Excellent explanation of the core principles (+4). Minor diagram omission (-1)." 
                                           value="<?= htmlspecialchars($comment) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Sticky Footer Action -->
        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" id="btn-save-evaluation">
                <i class="bi bi-check2-circle me-1"></i> Save & Commit Evaluation
            </button>
        </div>
    </form>
</div>

<script>
    // Real-time score tally calculation inside modal
    $(document).ready(function() {
        function recalcTally() {
            var sum = 0;
            $('.rub-marks-input').each(function() {
                var v = parseFloat($(this).val()) || 0;
                var max = parseFloat($(this).data('max')) || 0;
                if (v > max) {
                    $(this).val(max);
                    v = max;
                }
                if (v < 0) {
                    $(this).val(0);
                    v = 0;
                }
                sum += v;
            });
            $('#workbench-live-total').html(sum.toFixed(1) + ' <span class="text-muted fs-6 fw-normal">/ <?= (float)$resRow['Total_Marks'] ?></span>');
        }

        $(document).on('input', '.rub-marks-input', function() {
            recalcTally();
        });

        // AJAX Form Submission
        $('#evaluationForm').on('submit', function(e) {
            e.preventDefault();
            var $btn = $('#btn-save-evaluation');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

            $.ajax({
                url: 'enter-marks.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(resp) {
                    if (resp.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Evaluation Saved!',
                            text: resp.message + ' New Score: ' + resp.marks_obtained,
                            confirmButtonColor: '#d1202d'
                        }).then(() => {
                            // Close modal and refresh parent page
                            var modalEl = document.getElementById('marksModal');
                            if (modalEl) {
                                var modal = bootstrap.Modal.getInstance(modalEl);
                                if (modal) modal.hide();
                            }
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Save Failed',
                            text: resp.message || 'Error saving marks.',
                            confirmButtonColor: '#d1202d'
                        });
                        $btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Save & Commit Evaluation');
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Failed',
                        text: 'Server communication error: ' + xhr.responseText,
                        confirmButtonColor: '#d1202d'
                    });
                    $btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Save & Commit Evaluation');
                }
            });
        });
    });
</script>
