<?php
session_start();
if (!isset($_SESSION["LoginFaculty"])) {
    echo "Unauthorized access.";
    exit;
}

require_once "../Connection/connection.php";
if (!$con) {
    die("Database connection failed");
}

$exam_id = $_GET['Exam_ID'] ?? null;
if (!$exam_id) {
    die("Exam_ID is required. Usage: recalculate-results.php?Exam_ID=123");
}

/* ----------------------------------------------------------
    GRADING HELPERS
    ---------------------------------------------------------- */

function gradeMcq($question, $studentAnswer) {
    $maxMarks = (float)$question['Marks'];
    $correct  = ($studentAnswer !== null) && ($studentAnswer === $question['Correct_Opt']);
    return [
        'is_correct'     => $correct,
        'marks_awarded'  => $correct ? $maxMarks : 0.0,
    ];
}

function gradeMultiselect($question, $studentAnswer) {
    $maxMarks = (float)$question['Marks'];
    $accepted = json_decode($question['Answers_JSON'] ?? '[]', true) ?: [];
    
    if (!is_array($studentAnswer)) {
        $studentAnswer = [];
    }
    
    $totalCorrectOptions = count($accepted);
    if ($totalCorrectOptions === 0) {
        return ['is_correct' => false, 'marks_awarded' => 0.0];
    }
    
    $correctlySelected = 0;
    $incorrectlySelected = 0;
    
    foreach ($studentAnswer as $ans) {
        if (in_array($ans, $accepted, true)) {
            $correctlySelected++;
        } else {
            $incorrectlySelected++;
        }
    }
    
    $netCorrect = max(0, $correctlySelected - $incorrectlySelected);
    $marksAwarded = round(($netCorrect / $totalCorrectOptions) * $maxMarks, 2);
    
    $allCorrect = ($netCorrect === $totalCorrectOptions && $incorrectlySelected === 0);
    
    return [
        'is_correct'    => $allCorrect,
        'marks_awarded' => $marksAwarded,
    ];
}

function gradeFill($question, $studentAnswer) {
    $maxMarks = (float)$question['Marks'];
    $accepted = json_decode($question['Answers_JSON'] ?? '[]', true) ?: [];

    $normalizedStudent = is_string($studentAnswer) ? trim(mb_strtolower($studentAnswer)) : '';
    $correct = false;

    foreach ($accepted as $acceptedAnswer) {
        if ($normalizedStudent !== '' && $normalizedStudent === trim(mb_strtolower($acceptedAnswer))) {
            $correct = true;
            break;
        }
    }

    return [
        'is_correct'    => $correct,
        'marks_awarded' => $correct ? $maxMarks : 0.0,
    ];
}

function gradeMatch($question, $studentAnswer) {
    $maxMarks = (float)$question['Marks'];
    $pairs    = json_decode($question['Pairs_JSON'] ?? '[]', true) ?: [];

    $totalPairs = count($pairs);
    if ($totalPairs === 0 || !is_array($studentAnswer)) {
        return ['is_correct' => false, 'marks_awarded' => 0.0];
    }

    $correctCount = 0;
    foreach ($pairs as $idx => $pair) {
        $given = $studentAnswer[(string)$idx] ?? $studentAnswer[$idx] ?? null;
        if ($given !== null && $given === $pair['b']) {
            $correctCount++;
        }
    }

    $marksAwarded = $totalPairs > 0 ? round(($correctCount / $totalPairs) * $maxMarks, 2) : 0.0;
    $allCorrect   = ($correctCount === $totalPairs);

    return [
        'is_correct'    => $allCorrect,
        'marks_awarded' => $marksAwarded,
    ];
}

function gradeOpen($question, $studentAnswer) {
    return [
        'is_correct'    => null,
        'marks_awarded' => 0.0, // Open ended questions retain 0 or might need manual grading handling
    ];
}

/* ----------------------------------------------------------
    FETCH EXAM QUESTIONS
    ---------------------------------------------------------- */

$qstmt = $con->prepare("SELECT * FROM exam_questions WHERE Exam_ID = ?");
$qstmt->bind_param("i", $exam_id);
$qstmt->execute();
$qResult = $qstmt->get_result();

$questions = [];
$totalMarks = 0.0;
while ($row = $qResult->fetch_assoc()) {
    $questions[$row['Question_ID']] = $row;
    $totalMarks += (float)$row['Marks'];
}

if (empty($questions)) {
    die("No questions found for this exam.");
}

/* ----------------------------------------------------------
    FETCH AND RECALCULATE RESULTS
    ---------------------------------------------------------- */

$rstmt = $con->prepare("SELECT Result_ID FROM results WHERE Exam_ID = ?");
$rstmt->bind_param("i", $exam_id);
$rstmt->execute();
$results = $rstmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($results)) {
    die("No results found for this exam.");
}

$con->begin_transaction();

try {
    $updateAnswer = $con->prepare("UPDATE result_answers SET Is_Correct = ?, Marks_Awarded = ?, Max_Marks = ? WHERE Answer_ID = ?");
    $updateResult = $con->prepare("UPDATE results SET Total_Marks = ?, Marks_Obtained = ?, Status = ? WHERE Result_ID = ?");

    $recalculatedCount = 0;

    foreach ($results as $resRow) {
        $resultId = $resRow['Result_ID'];
        
        $ansStmt = $con->prepare("SELECT * FROM result_answers WHERE Result_ID = ?");
        $ansStmt->bind_param("i", $resultId);
        $ansStmt->execute();
        $answers = $ansStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $marksObtained = 0.0;
        $hasOpenQuestions = false;
        
        foreach ($answers as $ansRow) {
            $answerId = $ansRow['Answer_ID'];
            $qid = $ansRow['Question_ID'];
            $qtype = $ansRow['Question_Type'];
            $studentAnswerJson = $ansRow['Student_Answer'];
            $studentAnswer = $studentAnswerJson !== null ? json_decode($studentAnswerJson, true) : null;
            
            // If the question was removed from the exam, we skip it
            if (!isset($questions[$qid])) {
                continue; 
            }
            $question = $questions[$qid];
            $maxMarks = (float)$question['Marks'];
            
            if ($studentAnswer === null || $studentAnswer === '') {
                $isCorrect = ($qtype === 'open') ? null : 0;
                // If it's an open question and manual grading was already done, maybe we shouldn't reset it to 0.
                // But since the prompt is to recalculate an MCQ exam, we can just proceed.
                // For safety on open questions, we might want to keep the old marks if they were graded.
                $marksAwarded = ($qtype === 'open') ? (float)$ansRow['Marks_Awarded'] : 0.0;
                if ($qtype === 'open') $hasOpenQuestions = true;
            } else {
                switch ($qtype) {
                    case 'mcq':
                        $res = gradeMcq($question, $studentAnswer);
                        break;
                    case 'multiselect':
                        $res = gradeMultiselect($question, $studentAnswer);
                        break;
                    case 'fill':
                        $res = gradeFill($question, $studentAnswer);
                        break;
                    case 'match':
                        $res = gradeMatch($question, $studentAnswer);
                        break;
                    case 'open':
                        $res = gradeOpen($question, $studentAnswer);
                        // retain previous marks awarded for open questions
                        $res['marks_awarded'] = (float)$ansRow['Marks_Awarded'];
                        $res['is_correct'] = $ansRow['Is_Correct'];
                        $hasOpenQuestions = true;
                        break;
                    default:
                        $res = ['is_correct' => null, 'marks_awarded' => 0.0];
                }
                
                $isCorrect = $res['is_correct'] === null ? null : (int)$res['is_correct'];
                $marksAwarded = (float)$res['marks_awarded'];
            }
            
            $marksObtained += $marksAwarded;
            
            $updateAnswer->bind_param("iddi", $isCorrect, $marksAwarded, $maxMarks, $answerId);
            $updateAnswer->execute();
        }
        
        // If it was already published and had no open questions, keep it published.
        // Otherwise, pending_review or graded. We could fetch the old status to avoid reverting published exams.
        $oldStatusQuery = $con->prepare("SELECT Status FROM results WHERE Result_ID = ?");
        $oldStatusQuery->bind_param("i", $resultId);
        $oldStatusQuery->execute();
        $oldStatus = $oldStatusQuery->get_result()->fetch_assoc()['Status'];

        $resultStatus = $hasOpenQuestions ? 'pending_review' : 'graded';
        if ($oldStatus === 'published' && !$hasOpenQuestions) {
            $resultStatus = 'published';
        }
        
        $updateResult->bind_param("ddsi", $totalMarks, $marksObtained, $resultStatus, $resultId);
        $updateResult->execute();

        $recalculatedCount++;
    }
    
    $con->commit();
    echo "Successfully recalculated results for Exam_ID: " . htmlspecialchars($exam_id) . ". Total $recalculatedCount result(s) updated.";

} catch (Exception $e) {
    $con->rollback();
    echo "Error recalculating results: " . $e->getMessage();
}

?>
