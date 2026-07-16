

<?php
session_start();
header('Content-Type: application/json');

$con = mysqli_connect("localhost", "root", "", "exam_db");
if (!$con) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

/* ----------------------------------------------------------
    READ & VALIDATE PAYLOAD
    ---------------------------------------------------------- */

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!$payload || !isset($payload['exam_id'], $payload['answers'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Malformed request payload']);
    exit;
}

$exam_id      = (int)$payload['exam_id'];
$auto_submit  = !empty($payload['auto_submit']) ? 1 : 0;
$time_taken   = isset($payload['time_taken']) ? (int)$payload['time_taken'] : 0;
$violations   = isset($payload['violations']) ? (int)$payload['violations'] : 0;
$answersIn    = is_array($payload['answers']) ? $payload['answers'] : [];

// Student identity comes from the session, not the client payload, to prevent spoofing.
$Stud_ID = $_SESSION['Stud_ID'] ?? 123456; // Default roll number for testing; replace with actual session value in production.
$student_name = $_SESSION['student_name'] ?? null;

if (!$exam_id || !$Stud_ID) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid session or exam reference']);
    exit;
}

/* ----------------------------------------------------------
    PREVENT DUPLICATE SUBMISSION
    ---------------------------------------------------------- */

$dupCheck = $con->prepare("SELECT Result_ID FROM results WHERE Exam_ID = ? AND Stud_ID = ? LIMIT 1");
$dupCheck->bind_param("is", $exam_id, $Stud_ID);
$dupCheck->execute();
if ($dupCheck->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Exam already submitted for this student']);
    exit;
}

/* ----------------------------------------------------------
    FETCH QUESTIONS (server-side source of truth — never trust
    correctness data from the client)
    ---------------------------------------------------------- */

$qstmt = $con->prepare("SELECT * FROM exam_questions WHERE Exam_ID = ?");
$qstmt->bind_param("i", $exam_id);
$qstmt->execute();
$qResult = $qstmt->get_result();

$questions = [];
while ($row = $qResult->fetch_assoc()) {
    $questions[$row['Question_ID']] = $row;
}

if (empty($questions)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No questions found for this exam']);
    exit;
}

$totalMarks = 0.0;
foreach ($questions as $q) {
    $totalMarks += (float)$q['Marks'];
}

/* ----------------------------------------------------------
    GRADING HELPERS
    ---------------------------------------------------------- */

/**
 * MCQ: exact match against Correct_Opt.
 */
function gradeMcq($question, $studentAnswer) {
    $maxMarks = (float)$question['Marks'];
    $correct  = ($studentAnswer !== null) && ($studentAnswer === $question['Correct_Opt']);
    return [
        'is_correct'     => $correct,
        'marks_awarded'  => $correct ? $maxMarks : 0.0,
    ];
}

/**
 * Fill: case-insensitive, trimmed match against any accepted answer
 * in Answers_JSON (a JSON array of acceptable strings).
 */
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

/**
 * Match: per-pair partial scoring against Pairs_JSON
 * (array of {a, b} objects, keyed positionally to match
 * the row index sent from attempt-exam.php's match dropdowns).
 */
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

/**
 * Open: not auto-gradable. Flagged for manual review.
 */
function gradeOpen($question, $studentAnswer) {
    return [
        'is_correct'    => null,
        'marks_awarded' => 0.0,
    ];
}

/* ----------------------------------------------------------
    GRADE EVERY QUESTION
    ---------------------------------------------------------- */

$gradedRows       = [];
$marksObtained    = 0.0;
$hasOpenQuestions = false;

foreach ($questions as $qid => $question) {
    $qtype     = $question['Question_Type'];
    $maxMarks  = (float)$question['Marks'];
    $submitted = $answersIn[$qid] ?? null;

    $studentAnswer = $submitted['answer'] ?? null;
    $answerStatus  = $submitted['status'] ?? 'not';

    if ($submitted === null || $studentAnswer === null || $studentAnswer === '') {
        // Unattempted — zero marks, no grading function needed.
        $gradedRows[] = [
            'question_id'    => $qid,
            'question_type'  => $qtype,
            'student_answer' => null,
            'is_correct'     => ($qtype === 'open') ? null : false,
            'marks_awarded'  => 0.0,
            'max_marks'      => $maxMarks,
            'answer_status'  => $answerStatus,
        ];
        if ($qtype === 'open') $hasOpenQuestions = true;
        continue;
    }

    switch ($qtype) {
        case 'mcq':
            $result = gradeMcq($question, $studentAnswer);
            break;
        case 'fill':
            $result = gradeFill($question, $studentAnswer);
            break;
        case 'match':
            $result = gradeMatch($question, $studentAnswer);
            break;
        case 'open':
            $result = gradeOpen($question, $studentAnswer);
            $hasOpenQuestions = true;
            break;
        default:
            $result = ['is_correct' => null, 'marks_awarded' => 0.0];
    }

    $marksObtained += $result['marks_awarded'];

    $gradedRows[] = [
        'question_id'    => $qid,
        'question_type'  => $qtype,
        'student_answer' => $studentAnswer,
        'is_correct'     => $result['is_correct'],
        'marks_awarded'  => $result['marks_awarded'],
        'max_marks'      => $maxMarks,
        'answer_status'  => $answerStatus,
    ];
}

$resultStatus = $hasOpenQuestions ? 'pending_review' : 'graded';

/* ----------------------------------------------------------
    PERSIST — wrapped in a transaction so a partial failure
    never leaves a results row with no matching result_answers
    ---------------------------------------------------------- */

$con->begin_transaction();

try {
    $insertResult = $con->prepare("
        INSERT INTO results
            (Exam_ID, Stud_ID, Student_Name, Total_Marks, Marks_Obtained,
             Time_Taken_Sec, Violations, Auto_Submitted, Status, Submitted_At)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $insertResult->bind_param(
        "issddiiis",
        $exam_id,
        $Stud_ID,
        $student_name,
        $totalMarks,
        $marksObtained,
        $time_taken,
        $violations,
        $auto_submit,
        $resultStatus
    );
    $insertResult->execute();
    $resultId = $con->insert_id;

    $insertAnswer = $con->prepare("
        INSERT INTO result_answers
            (Result_ID, Question_ID, Question_Type, Student_Answer,
             Is_Correct, Marks_Awarded, Max_Marks, Answer_Status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($gradedRows as $row) {
        $studentAnswerJson = $row['student_answer'] !== null
            ? json_encode($row['student_answer'])
            : null;

        // Is_Correct: NULL for open/unattempted-open, 0/1 otherwise
        $isCorrect = $row['is_correct'] === null ? null : (int)$row['is_correct'];

        $insertAnswer->bind_param(
            "iissidds",
            $resultId,
            $row['question_id'],
            $row['question_type'],
            $studentAnswerJson,
            $isCorrect,
            $row['marks_awarded'],
            $row['max_marks'],
            $row['answer_status']
        );
        $insertAnswer->execute();
    }

    $con->commit();

} catch (Exception $e) {
    $con->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save submission: ' . $e->getMessage()
    ]);
    exit;
}

/* ----------------------------------------------------------
    RESPONSE
    ---------------------------------------------------------- */

echo json_encode([
    'success'         => true,
    'result_id'       => $resultId,
    'total_marks'     => $totalMarks,
    'marks_obtained'  => $marksObtained,
    'status'          => $resultStatus,
    'pending_review'  => $hasOpenQuestions,

<?php
session_start();
header('Content-Type: application/json');

$con = mysqli_connect("localhost", "root", "", "exam_db");
if (!$con) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

/* ----------------------------------------------------------
    READ & VALIDATE PAYLOAD
    ---------------------------------------------------------- */

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!$payload || !isset($payload['exam_id'], $payload['answers'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Malformed request payload']);
    exit;
}

$exam_id      = (int)$payload['exam_id'];
$auto_submit  = !empty($payload['auto_submit']) ? 1 : 0;
$time_taken   = isset($payload['time_taken']) ? (int)$payload['time_taken'] : 0;
$violations   = isset($payload['violations']) ? (int)$payload['violations'] : 0;
$answersIn    = is_array($payload['answers']) ? $payload['answers'] : [];

// Student identity comes from the session, not the client payload, to prevent spoofing.
$Stud_ID = $_SESSION['Stud_ID'] ?? 123456; // Default roll number for testing; replace with actual session value in production.
$student_name = $_SESSION['student_name'] ?? null;

if (!$exam_id || !$Stud_ID) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid session or exam reference']);
    exit;
}

/* ----------------------------------------------------------
    PREVENT DUPLICATE SUBMISSION
    ---------------------------------------------------------- */

$dupCheck = $con->prepare("SELECT Result_ID FROM results WHERE Exam_ID = ? AND Stud_ID = ? LIMIT 1");
$dupCheck->bind_param("is", $exam_id, $Stud_ID);
$dupCheck->execute();
if ($dupCheck->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Exam already submitted for this student']);
    exit;
}

/* ----------------------------------------------------------
    FETCH QUESTIONS (server-side source of truth — never trust
    correctness data from the client)
    ---------------------------------------------------------- */

$qstmt = $con->prepare("SELECT * FROM exam_questions WHERE Exam_ID = ?");
$qstmt->bind_param("i", $exam_id);
$qstmt->execute();
$qResult = $qstmt->get_result();

$questions = [];
while ($row = $qResult->fetch_assoc()) {
    $questions[$row['Question_ID']] = $row;
}

if (empty($questions)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No questions found for this exam']);
    exit;
}

$totalMarks = 0.0;
foreach ($questions as $q) {
    $totalMarks += (float)$q['Marks'];
}

/* ----------------------------------------------------------
    GRADING HELPERS
    ---------------------------------------------------------- */

/**
 * MCQ: exact match against Correct_Opt.
 */
function gradeMcq($question, $studentAnswer) {
    $maxMarks = (float)$question['Marks'];
    $correct  = ($studentAnswer !== null) && ($studentAnswer === $question['Correct_Opt']);
    return [
        'is_correct'     => $correct,
        'marks_awarded'  => $correct ? $maxMarks : 0.0,
    ];
}

/**
 * Fill: case-insensitive, trimmed match against any accepted answer
 * in Answers_JSON (a JSON array of acceptable strings).
 */
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

/**
 * Match: per-pair partial scoring against Pairs_JSON
 * (array of {a, b} objects, keyed positionally to match
 * the row index sent from attempt-exam.php's match dropdowns).
 */
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

/**
 * Open: not auto-gradable. Flagged for manual review.
 */
function gradeOpen($question, $studentAnswer) {
    return [
        'is_correct'    => null,
        'marks_awarded' => 0.0,
    ];
}

/* ----------------------------------------------------------
    GRADE EVERY QUESTION
    ---------------------------------------------------------- */

$gradedRows       = [];
$marksObtained    = 0.0;
$hasOpenQuestions = false;

foreach ($questions as $qid => $question) {
    $qtype     = $question['Question_Type'];
    $maxMarks  = (float)$question['Marks'];
    $submitted = $answersIn[$qid] ?? null;

    $studentAnswer = $submitted['answer'] ?? null;
    $answerStatus  = $submitted['status'] ?? 'not';

    if ($submitted === null || $studentAnswer === null || $studentAnswer === '') {
        // Unattempted — zero marks, no grading function needed.
        $gradedRows[] = [
            'question_id'    => $qid,
            'question_type'  => $qtype,
            'student_answer' => null,
            'is_correct'     => ($qtype === 'open') ? null : false,
            'marks_awarded'  => 0.0,
            'max_marks'      => $maxMarks,
            'answer_status'  => $answerStatus,
        ];
        if ($qtype === 'open') $hasOpenQuestions = true;
        continue;
    }

    switch ($qtype) {
        case 'mcq':
            $result = gradeMcq($question, $studentAnswer);
            break;
        case 'fill':
            $result = gradeFill($question, $studentAnswer);
            break;
        case 'match':
            $result = gradeMatch($question, $studentAnswer);
            break;
        case 'open':
            $result = gradeOpen($question, $studentAnswer);
            $hasOpenQuestions = true;
            break;
        default:
            $result = ['is_correct' => null, 'marks_awarded' => 0.0];
    }

    $marksObtained += $result['marks_awarded'];

    $gradedRows[] = [
        'question_id'    => $qid,
        'question_type'  => $qtype,
        'student_answer' => $studentAnswer,
        'is_correct'     => $result['is_correct'],
        'marks_awarded'  => $result['marks_awarded'],
        'max_marks'      => $maxMarks,
        'answer_status'  => $answerStatus,
    ];
}

$resultStatus = $hasOpenQuestions ? 'pending_review' : 'graded';

/* ----------------------------------------------------------
    PERSIST — wrapped in a transaction so a partial failure
    never leaves a results row with no matching result_answers
    ---------------------------------------------------------- */

$con->begin_transaction();

try {
    $insertResult = $con->prepare("
        INSERT INTO results
            (Exam_ID, Stud_ID, Student_Name, Total_Marks, Marks_Obtained,
             Time_Taken_Sec, Violations, Auto_Submitted, Status, Submitted_At)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $insertResult->bind_param(
        "issddiiis",
        $exam_id,
        $Stud_ID,
        $student_name,
        $totalMarks,
        $marksObtained,
        $time_taken,
        $violations,
        $auto_submit,
        $resultStatus
    );
    $insertResult->execute();
    $resultId = $con->insert_id;

    $insertAnswer = $con->prepare("
        INSERT INTO result_answers
            (Result_ID, Question_ID, Question_Type, Student_Answer,
             Is_Correct, Marks_Awarded, Max_Marks, Answer_Status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($gradedRows as $row) {
        $studentAnswerJson = $row['student_answer'] !== null
            ? json_encode($row['student_answer'])
            : null;

        // Is_Correct: NULL for open/unattempted-open, 0/1 otherwise
        $isCorrect = $row['is_correct'] === null ? null : (int)$row['is_correct'];

        $insertAnswer->bind_param(
            "iissidds",
            $resultId,
            $row['question_id'],
            $row['question_type'],
            $studentAnswerJson,
            $isCorrect,
            $row['marks_awarded'],
            $row['max_marks'],
            $row['answer_status']
        );
        $insertAnswer->execute();
    }

    $con->commit();

} catch (Exception $e) {
    $con->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save submission: ' . $e->getMessage()
    ]);
    exit;
}

/* ----------------------------------------------------------
    RESPONSE
    ---------------------------------------------------------- */

echo json_encode([
    'success'         => true,
    'result_id'       => $resultId,
    'total_marks'     => $totalMarks,
    'marks_obtained'  => $marksObtained,
    'status'          => $resultStatus,
    'pending_review'  => $hasOpenQuestions,


<?php
session_start();
header('Content-Type: application/json');

$con = mysqli_connect("localhost", "root", "", "exam_db");
if (!$con) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

/* ----------------------------------------------------------
    READ & VALIDATE PAYLOAD
    ---------------------------------------------------------- */

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!$payload || !isset($payload['exam_id'], $payload['answers'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Malformed request payload']);
    exit;
}

$exam_id      = (int)$payload['exam_id'];
$auto_submit  = !empty($payload['auto_submit']) ? 1 : 0;
$time_taken   = isset($payload['time_taken']) ? (int)$payload['time_taken'] : 0;
$violations   = isset($payload['violations']) ? (int)$payload['violations'] : 0;
$answersIn    = is_array($payload['answers']) ? $payload['answers'] : [];

// Student identity comes from the session, not the client payload, to prevent spoofing.
$Stud_ID = $_SESSION['Stud_ID'] ?? 123456; // Default roll number for testing; replace with actual session value in production.
$student_name = $_SESSION['student_name'] ?? null;

if (!$exam_id || !$Stud_ID) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid session or exam reference']);
    exit;
}

/* ----------------------------------------------------------
    PREVENT DUPLICATE SUBMISSION
    ---------------------------------------------------------- */

$dupCheck = $con->prepare("SELECT Result_ID FROM results WHERE Exam_ID = ? AND Stud_ID = ? LIMIT 1");
$dupCheck->bind_param("is", $exam_id, $Stud_ID);
$dupCheck->execute();
if ($dupCheck->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Exam already submitted for this student']);
    exit;
}

/* ----------------------------------------------------------
    FETCH QUESTIONS (server-side source of truth — never trust
    correctness data from the client)
    ---------------------------------------------------------- */

$qstmt = $con->prepare("SELECT * FROM exam_questions WHERE Exam_ID = ?");
$qstmt->bind_param("i", $exam_id);
$qstmt->execute();
$qResult = $qstmt->get_result();

$questions = [];
while ($row = $qResult->fetch_assoc()) {
    $questions[$row['Question_ID']] = $row;
}

if (empty($questions)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No questions found for this exam']);
    exit;
}

$totalMarks = 0.0;
foreach ($questions as $q) {
    $totalMarks += (float)$q['Marks'];
}

/* ----------------------------------------------------------
    GRADING HELPERS
    ---------------------------------------------------------- */

/**
 * MCQ: exact match against Correct_Opt.
 */
function gradeMcq($question, $studentAnswer) {
    $maxMarks = (float)$question['Marks'];
    $correct  = ($studentAnswer !== null) && ($studentAnswer === $question['Correct_Opt']);
    return [
        'is_correct'     => $correct,
        'marks_awarded'  => $correct ? $maxMarks : 0.0,
    ];
}

/**
 * Fill: case-insensitive, trimmed match against any accepted answer
 * in Answers_JSON (a JSON array of acceptable strings).
 */
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

/**
 * Match: per-pair partial scoring against Pairs_JSON
 * (array of {a, b} objects, keyed positionally to match
 * the row index sent from attempt-exam.php's match dropdowns).
 */
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

/**
 * Open: not auto-gradable. Flagged for manual review.
 */
function gradeOpen($question, $studentAnswer) {
    return [
        'is_correct'    => null,
        'marks_awarded' => 0.0,
    ];
}

/* ----------------------------------------------------------
    GRADE EVERY QUESTION
    ---------------------------------------------------------- */

$gradedRows       = [];
$marksObtained    = 0.0;
$hasOpenQuestions = false;

foreach ($questions as $qid => $question) {
    $qtype     = $question['Question_Type'];
    $maxMarks  = (float)$question['Marks'];
    $submitted = $answersIn[$qid] ?? null;

    $studentAnswer = $submitted['answer'] ?? null;
    $answerStatus  = $submitted['status'] ?? 'not';

    if ($submitted === null || $studentAnswer === null || $studentAnswer === '') {
        // Unattempted — zero marks, no grading function needed.
        $gradedRows[] = [
            'question_id'    => $qid,
            'question_type'  => $qtype,
            'student_answer' => null,
            'is_correct'     => ($qtype === 'open') ? null : false,
            'marks_awarded'  => 0.0,
            'max_marks'      => $maxMarks,
            'answer_status'  => $answerStatus,
        ];
        if ($qtype === 'open') $hasOpenQuestions = true;
        continue;
    }

    switch ($qtype) {
        case 'mcq':
            $result = gradeMcq($question, $studentAnswer);
            break;
        case 'fill':
            $result = gradeFill($question, $studentAnswer);
            break;
        case 'match':
            $result = gradeMatch($question, $studentAnswer);
            break;
        case 'open':
            $result = gradeOpen($question, $studentAnswer);
            $hasOpenQuestions = true;
            break;
        default:
            $result = ['is_correct' => null, 'marks_awarded' => 0.0];
    }

    $marksObtained += $result['marks_awarded'];

    $gradedRows[] = [
        'question_id'    => $qid,
        'question_type'  => $qtype,
        'student_answer' => $studentAnswer,
        'is_correct'     => $result['is_correct'],
        'marks_awarded'  => $result['marks_awarded'],
        'max_marks'      => $maxMarks,
        'answer_status'  => $answerStatus,
    ];
}

$resultStatus = $hasOpenQuestions ? 'pending_review' : 'graded';

/* ----------------------------------------------------------
    PERSIST — wrapped in a transaction so a partial failure
    never leaves a results row with no matching result_answers
    ---------------------------------------------------------- */

$con->begin_transaction();

try {
    $insertResult = $con->prepare("
        INSERT INTO results
            (Exam_ID, Stud_ID, Student_Name, Total_Marks, Marks_Obtained,
             Time_Taken_Sec, Violations, Auto_Submitted, Status, Submitted_At)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $insertResult->bind_param(
        "issddiiis",
        $exam_id,
        $Stud_ID,
        $student_name,
        $totalMarks,
        $marksObtained,
        $time_taken,
        $violations,
        $auto_submit,
        $resultStatus
    );
    $insertResult->execute();
    $resultId = $con->insert_id;

    $insertAnswer = $con->prepare("
        INSERT INTO result_answers
            (Result_ID, Question_ID, Question_Type, Student_Answer,
             Is_Correct, Marks_Awarded, Max_Marks, Answer_Status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($gradedRows as $row) {
        $studentAnswerJson = $row['student_answer'] !== null
            ? json_encode($row['student_answer'])
            : null;

        // Is_Correct: NULL for open/unattempted-open, 0/1 otherwise
        $isCorrect = $row['is_correct'] === null ? null : (int)$row['is_correct'];

        $insertAnswer->bind_param(
            "iissidds",
            $resultId,
            $row['question_id'],
            $row['question_type'],
            $studentAnswerJson,
            $isCorrect,
            $row['marks_awarded'],
            $row['max_marks'],
            $row['answer_status']
        );
        $insertAnswer->execute();
    }

    $con->commit();

} catch (Exception $e) {
    $con->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save submission: ' . $e->getMessage()
    ]);
    exit;
}

/* ----------------------------------------------------------
    RESPONSE
    ---------------------------------------------------------- */

echo json_encode([
    'success'         => true,
    'result_id'       => $resultId,
    'total_marks'     => $totalMarks,
    'marks_obtained'  => $marksObtained,
    'status'          => $resultStatus,
    'pending_review'  => $hasOpenQuestions,

]);