<<<<<<< HEAD
<?php
/**
 * Exam Builder – API
 * Endpoint: api.php?action=<action>
 *
 * Actions
 * ───────
 *   POST save_exam        – create a new exam + questions
 *   POST update_exam      – update existing exam + questions
 *   GET  bank_questions   – list question-bank items (search / filter)
 *   POST upload_image     – upload a diagram image
 */

session_start();

// ── Auth guard ────────────────────────────────────────────────────────────────
if (empty($_SESSION['LoginFaculty'])) {
    jsonError('Unauthorized', 401);
}

$con = mysqli_connect("localhost", "root", "", "exam_db");

$FAC_ID = (int) $_SESSION['LoginFaculty'];
$action = $_GET['action'] ?? '';

// ── Route ─────────────────────────────────────────────────────────────────────
switch ($action) {
    case 'save_exam':
        handleSaveExam(false);
        break;
    case 'update_exam':
        handleSaveExam(true);
        break;
    case 'bank_questions':
        handleBankQuestions();
        break;
    case 'upload_image':
        handleUploadImage();
        break;
    default:
        jsonError('Unknown action');
}


// ══════════════════════════════════════════════════════════════════════════════
//  SAVE / UPDATE EXAM
// ══════════════════════════════════════════════════════════════════════════════
function handleSaveExam(bool $isUpdate): void
{
    global $con, $FAC_ID;

    $body = getJsonBody();

    // ── Validate required fields ──────────────────────────────────────────────
    $examName  = trim($body['name']      ?? '');
    $duration  = (int)  ($body['duration']  ?? 0);
    $courseId  = (int)  ($body['course_id'] ?? 0);
    $status    = in_array($body['status'] ?? '', ['draft', 'published'], true)
                    ? $body['status'] : 'draft';
    $startTime = sanitizeDatetime($body['start_time'] ?? null);
    $endTime   = sanitizeDatetime($body['end_time']   ?? null);
    $questions = $body['questions'] ?? [];

    if (!$examName)  jsonError('Exam name is required');
    if (!$duration)  jsonError('Duration is required');
    if (!$courseId)  jsonError('Course ID is required');
    if (!is_array($questions) || count($questions) === 0) jsonError('At least one question is required');

    // ── Validate ownership / existence on update ──────────────────────────────
    $examId = $isUpdate ? (int)($body['exam_id'] ?? 0) : 0;
    if ($isUpdate) {
        if (!$examId) jsonError('exam_id is required for update');
        $chk = $con->prepare("SELECT Exam_ID FROM exams WHERE Exam_ID = ? AND Fac_ID = ?");
        $chk->bind_param('ii', $examId, $FAC_ID);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) jsonError('Exam not found or access denied', 403);
        $chk->close();
    }

    // ── Wrap everything in a transaction ─────────────────────────────────────
    $con->begin_transaction();
    try {
        if ($isUpdate) {
            // Update exam header
            $stmt = $con->prepare(
                "UPDATE exams
                    SET Exam_Name=?, Duration=?, Start_Time=?, End_Time=?, Status=?, Updated_At=NOW()
                  WHERE Exam_ID=? AND Fac_ID=?"
            );
            $stmt->bind_param('sisssii',
                $examName, $duration, $startTime, $endTime, $status,
                $examId, $FAC_ID
            );
            $stmt->execute();
            $stmt->close();

            // Delete old questions – we re-insert fresh
            $del = $con->prepare("DELETE FROM exam_questions WHERE Exam_ID = ?");
            $del->bind_param('i', $examId);
            $del->execute();
            $del->close();

        } else {
            // Insert exam header
            $stmt = $con->prepare(
                "INSERT INTO exams (Course_ID, Fac_ID, Exam_Name, Duration, Start_Time, End_Time, Status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iisssss',
                $courseId, $FAC_ID, $examName, $duration, $startTime, $endTime, $status
            );
            $stmt->execute();
            $examId = (int) $con->insert_id;
            $stmt->close();
        }

        // ── Insert questions ──────────────────────────────────────────────────
        $qStmt = $con->prepare(
            "INSERT INTO exam_questions
                (Exam_ID, Question_Type, Question_Text, Marks, Sort_Order,
                 Options_JSON, Correct_Opt, Rubric, Word_Limit,
                 Answers_JSON, Pairs_JSON, Image_Path)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($questions as $q) {
            $type       = sanitizeType($q['type']          ?? '');
            $text       = trim($q['question_text']         ?? '');
            $marks      = round((float)($q['marks']        ?? 1), 1);
            $sortOrder  = (int)($q['sort_order']           ?? 0);

            if (!$type) continue;   // skip malformed

            // Type-specific fields
            $optionsJson  = null;
            $correctOpt   = null;
            $rubric       = null;
            $wordLimit    = null;
            $answersJson  = null;
            $pairsJson    = null;
            $imagePath    = null;

            switch ($type) {
                case 'mcq':
                    $opts = $q['options'] ?? [];
                    if (is_array($opts) && count($opts)) {
                        $cleanOpts = array_map(fn($o) => [
                            'letter' => strtoupper(substr($o['letter'] ?? '', 0, 1)),
                            'text'   => trim($o['text'] ?? ''),
                        ], $opts);
                        $optionsJson = json_encode($cleanOpts);
                    }
                    $raw = strtoupper(trim($q['correct_opt'] ?? ''));
                    $correctOpt = in_array($raw, ['A','B','C','D'], true) ? $raw : null;
                    break;

                case 'open':
                    $rubric    = trim($q['rubric']     ?? '') ?: null;
                    $wordLimit = isset($q['word_limit']) && $q['word_limit'] !== null
                                    ? (int)$q['word_limit'] : null;
                    break;

                case 'fill':
                    $answers = $q['answers'] ?? [];
                    $answersJson = is_array($answers) ? json_encode(array_values($answers)) : null;
                    break;

                case 'match':
                    $pairs = $q['pairs'] ?? [];
                    if (is_array($pairs) && count($pairs)) {
                        $cleanPairs = array_map(fn($p) => [
                            'a' => trim($p['a'] ?? ''),
                            'b' => trim($p['b'] ?? ''),
                        ], $pairs);
                        $pairsJson = json_encode($cleanPairs);
                    }
                    break;

                case 'diagram':
                    $imagePath = sanitizeImagePath($q['image_path'] ?? null);
                    $rubric    = trim($q['rubric'] ?? '') ?: null;
                    break;
            }

            $qStmt->bind_param('issdississss',
                $examId, $type, $text, $marks, $sortOrder,
                $optionsJson, $correctOpt, $rubric, $wordLimit,
                $answersJson, $pairsJson, $imagePath
            );
            $qStmt->execute();

            // ── Also add to question bank (upsert by text+type+fac) ───────────
            addToBank($con, $courseId, $FAC_ID, $type, $text, $marks,
                      $optionsJson, $correctOpt, $rubric, $wordLimit,
                      $answersJson, $pairsJson, $imagePath);
        }
        $qStmt->close();

        $con->commit();
        jsonSuccess(['exam_id' => $examId, 'message' => 'Exam saved successfully']);

    } catch (Throwable $e) {
        $con->rollback();
        jsonError('Database error: ' . $e->getMessage());
    }
}


// ══════════════════════════════════════════════════════════════════════════════
//  QUESTION BANK – LIST
// ══════════════════════════════════════════════════════════════════════════════
function handleBankQuestions(): void
{
    global $con, $FAC_ID;

    $search    = trim($_GET['search']       ?? '');
    $type      = sanitizeType($_GET['type'] ?? '');
    $courseId  = (int)($_GET['course_id']   ?? 0);

    // Base query – faculty can see their own + course-wide questions
    $sql    = "SELECT Bank_ID, Question_Type AS `type`, Question_Text AS question_text,
                      Marks AS marks, Options_JSON, Correct_Opt, Rubric,
                      Word_Limit, Answers_JSON, Pairs_JSON, Image_Path
               FROM question_bank
               WHERE Fac_ID = ?";
    $params = [$FAC_ID];
    $types  = 'i';

    if ($courseId) {
        $sql    .= " AND (Course_ID = ? OR Course_ID IS NULL)";
        $params[] = $courseId;
        $types   .= 'i';
    }

    if ($type) {
        $sql    .= " AND Question_Type = ?";
        $params[] = $type;
        $types   .= 's';
    }

    if ($search) {
        $sql    .= " AND MATCH(Question_Text) AGAINST(? IN BOOLEAN MODE)";
        $params[] = $search . '*';
        $types   .= 's';
    }

    $sql .= " ORDER BY Created_At DESC LIMIT 100";

    $stmt = $con->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        // Decode JSON columns so the frontend gets plain arrays
        foreach (['Options_JSON', 'Answers_JSON', 'Pairs_JSON'] as $col) {
            if ($row[$col] !== null) {
                $key       = strtolower(str_replace('_JSON', '', $col));   // options / answers / pairs
                $row[$key] = json_decode($row[$col], true);
            }
            unset($row[$col]);
        }
        $row['correct_opt'] = $row['Correct_Opt'];
        $row['word_limit']  = $row['Word_Limit'];
        $row['image_path']  = $row['Image_Path'];
        $row['rubric']      = $row['Rubric'];
        $row['marks']       = (float) $row['marks'];
        // Remove Pascal-case duplicates
        foreach (['Correct_Opt','Word_Limit','Image_Path','Rubric'] as $k) unset($row[$k]);
        $rows[] = $row;
    }

    jsonSuccess(['questions' => $rows]);
}


// ══════════════════════════════════════════════════════════════════════════════
//  IMAGE UPLOAD
// ══════════════════════════════════════════════════════════════════════════════
function handleUploadImage(): void
{
    global $FAC_ID;

    if (empty($_FILES['image'])) jsonError('No file uploaded');

    $file    = $_FILES['image'];
    $allowed = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
    $maxSize = 5 * 1024 * 1024; // 5 MB

    if ($file['error'] !== UPLOAD_ERR_OK)       jsonError('Upload error: ' . $file['error']);
    if ($file['size'] > $maxSize)               jsonError('File too large (max 5 MB)');
    if (!in_array($file['type'], $allowed, true)) jsonError('File type not allowed');

    $uploadDir = __DIR__ . '/uploads/diagrams/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        jsonError('Could not create upload directory');
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'diag_' . $FAC_ID . '_' . uniqid() . '.' . $ext;
    $dest     = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) jsonError('Failed to move uploaded file');

    $publicPath = 'uploads/diagrams/' . $filename;
    jsonSuccess(['path' => $publicPath]);
}


// ══════════════════════════════════════════════════════════════════════════════
//  HELPERS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Silently upsert a question into the bank.
 * Skips if the exact text+type already exists for this faculty.
 */
function addToBank(
    mysqli $con, int $courseId, int $facId,
    string $type, string $text, float $marks,
    ?string $optionsJson, ?string $correctOpt, ?string $rubric,
    ?int $wordLimit, ?string $answersJson, ?string $pairsJson, ?string $imagePath
): void {
    // Check duplicate (same faculty, same type, same text)
    $chk = $con->prepare(
        "SELECT Bank_ID FROM question_bank WHERE Fac_ID = ? AND Question_Type = ? AND Question_Text = ? LIMIT 1"
    );
    $chk->bind_param('iss', $facId, $type, $text);
    $chk->execute();
    $exists = $chk->get_result()->fetch_assoc();
    $chk->close();
    if ($exists) return;

    $ins = $con->prepare(
        "INSERT INTO question_bank
            (Course_ID, Fac_ID, Question_Type, Question_Text, Marks,
             Options_JSON, Correct_Opt, Rubric, Word_Limit,
             Answers_JSON, Pairs_JSON, Image_Path)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $ins->bind_param('iissdssissss',
        $courseId, $facId, $type, $text, $marks,
        $optionsJson, $correctOpt, $rubric, $wordLimit,
        $answersJson, $pairsJson, $imagePath
    );
    $ins->execute();
    $ins->close();
}

/** Parse raw POST body as JSON */
function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) jsonError('Empty request body');
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) jsonError('Invalid JSON: ' . json_last_error_msg());
    return $data;
}

/** Whitelist question types */
function sanitizeType(string $t): string
{
    return in_array($t, ['mcq','diagram','match','open','fill'], true) ? $t : '';
}

/** Convert JS datetime-local string → MySQL DATETIME or null */
function sanitizeDatetime(?string $dt): ?string
{
    if (!$dt) return null;
    $ts = strtotime($dt);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

/** Prevent path traversal in stored image paths */
function sanitizeImagePath(?string $path): ?string
{
    if (!$path) return null;
    // Allow only relative paths under uploads/
    $clean = preg_replace('/[^a-zA-Z0-9\/._-]/', '', $path);
    return str_starts_with($clean, 'uploads/') ? $clean : null;
}

/** Send a JSON success response and exit */
function jsonSuccess(array $data = []): void
{
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

/** Send a JSON error response and exit */
function jsonError(string $message, int $httpCode = 400): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
=======
<?php
/**
 * Exam Builder – API
 * Endpoint: api.php?action=<action>
 *
 * Actions
 * ───────
 *   POST save_exam        – create a new exam + questions
 *   POST update_exam      – update existing exam + questions
 *   GET  bank_questions   – list question-bank items (search / filter)
 *   POST upload_image     – upload a diagram image
 */

session_start();

// ── Auth guard ────────────────────────────────────────────────────────────────
if (empty($_SESSION['LoginFaculty'])) {
    jsonError('Unauthorized', 401);
}

$con = mysqli_connect("localhost", "root", "", "exam_db");

$FAC_ID = (int) $_SESSION['LoginFaculty'];
$action = $_GET['action'] ?? '';

// ── Route ─────────────────────────────────────────────────────────────────────
switch ($action) {
    case 'save_exam':
        handleSaveExam(false);
        break;
    case 'update_exam':
        handleSaveExam(true);
        break;
    case 'bank_questions':
        handleBankQuestions();
        break;
    case 'upload_image':
        handleUploadImage();
        break;
    default:
        jsonError('Unknown action');
}


// ══════════════════════════════════════════════════════════════════════════════
//  SAVE / UPDATE EXAM
// ══════════════════════════════════════════════════════════════════════════════
function handleSaveExam(bool $isUpdate): void
{
    global $con, $FAC_ID;

    $body = getJsonBody();

    // ── Validate required fields ──────────────────────────────────────────────
    $examName  = trim($body['name']      ?? '');
    $duration  = (int)  ($body['duration']  ?? 0);
    $courseId  = (int)  ($body['course_id'] ?? 0);
    $status    = in_array($body['status'] ?? '', ['draft', 'published'], true)
                    ? $body['status'] : 'draft';
    $startTime = sanitizeDatetime($body['start_time'] ?? null);
    $endTime   = sanitizeDatetime($body['end_time']   ?? null);
    $questions = $body['questions'] ?? [];

    if (!$examName)  jsonError('Exam name is required');
    if (!$duration)  jsonError('Duration is required');
    if (!$courseId)  jsonError('Course ID is required');
    if (!is_array($questions) || count($questions) === 0) jsonError('At least one question is required');

    // ── Validate ownership / existence on update ──────────────────────────────
    $examId = $isUpdate ? (int)($body['exam_id'] ?? 0) : 0;
    if ($isUpdate) {
        if (!$examId) jsonError('exam_id is required for update');
        $chk = $con->prepare("SELECT Exam_ID FROM exams WHERE Exam_ID = ? AND Fac_ID = ?");
        $chk->bind_param('ii', $examId, $FAC_ID);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) jsonError('Exam not found or access denied', 403);
        $chk->close();
    }

    // ── Wrap everything in a transaction ─────────────────────────────────────
    $con->begin_transaction();
    try {
        if ($isUpdate) {
            // Update exam header
            $stmt = $con->prepare(
                "UPDATE exams
                    SET Exam_Name=?, Duration=?, Start_Time=?, End_Time=?, Status=?, Updated_At=NOW()
                  WHERE Exam_ID=? AND Fac_ID=?"
            );
            $stmt->bind_param('sisssii',
                $examName, $duration, $startTime, $endTime, $status,
                $examId, $FAC_ID
            );
            $stmt->execute();
            $stmt->close();

            // Delete old questions – we re-insert fresh
            $del = $con->prepare("DELETE FROM exam_questions WHERE Exam_ID = ?");
            $del->bind_param('i', $examId);
            $del->execute();
            $del->close();

        } else {
            // Insert exam header
            $stmt = $con->prepare(
                "INSERT INTO exams (Course_ID, Fac_ID, Exam_Name, Duration, Start_Time, End_Time, Status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iisssss',
                $courseId, $FAC_ID, $examName, $duration, $startTime, $endTime, $status
            );
            $stmt->execute();
            $examId = (int) $con->insert_id;
            $stmt->close();
        }

        // ── Insert questions ──────────────────────────────────────────────────
        $qStmt = $con->prepare(
            "INSERT INTO exam_questions
                (Exam_ID, Question_Type, Question_Text, Marks, Sort_Order,
                 Options_JSON, Correct_Opt, Rubric, Word_Limit,
                 Answers_JSON, Pairs_JSON, Image_Path)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($questions as $q) {
            $type       = sanitizeType($q['type']          ?? '');
            $text       = trim($q['question_text']         ?? '');
            $marks      = round((float)($q['marks']        ?? 1), 1);
            $sortOrder  = (int)($q['sort_order']           ?? 0);

            if (!$type) continue;   // skip malformed

            // Type-specific fields
            $optionsJson  = null;
            $correctOpt   = null;
            $rubric       = null;
            $wordLimit    = null;
            $answersJson  = null;
            $pairsJson    = null;
            $imagePath    = null;

            switch ($type) {
                case 'mcq':
                    $opts = $q['options'] ?? [];
                    if (is_array($opts) && count($opts)) {
                        $cleanOpts = array_map(fn($o) => [
                            'letter' => strtoupper(substr($o['letter'] ?? '', 0, 1)),
                            'text'   => trim($o['text'] ?? ''),
                        ], $opts);
                        $optionsJson = json_encode($cleanOpts);
                    }
                    $raw = strtoupper(trim($q['correct_opt'] ?? ''));
                    $correctOpt = in_array($raw, ['A','B','C','D'], true) ? $raw : null;
                    break;

                case 'open':
                    $rubric    = trim($q['rubric']     ?? '') ?: null;
                    $wordLimit = isset($q['word_limit']) && $q['word_limit'] !== null
                                    ? (int)$q['word_limit'] : null;
                    break;

                case 'fill':
                    $answers = $q['answers'] ?? [];
                    $answersJson = is_array($answers) ? json_encode(array_values($answers)) : null;
                    break;

                case 'match':
                    $pairs = $q['pairs'] ?? [];
                    if (is_array($pairs) && count($pairs)) {
                        $cleanPairs = array_map(fn($p) => [
                            'a' => trim($p['a'] ?? ''),
                            'b' => trim($p['b'] ?? ''),
                        ], $pairs);
                        $pairsJson = json_encode($cleanPairs);
                    }
                    break;

                case 'diagram':
                    $imagePath = sanitizeImagePath($q['image_path'] ?? null);
                    $rubric    = trim($q['rubric'] ?? '') ?: null;
                    break;
            }

            $qStmt->bind_param('issdississss',
                $examId, $type, $text, $marks, $sortOrder,
                $optionsJson, $correctOpt, $rubric, $wordLimit,
                $answersJson, $pairsJson, $imagePath
            );
            $qStmt->execute();

            // ── Also add to question bank (upsert by text+type+fac) ───────────
            addToBank($con, $courseId, $FAC_ID, $type, $text, $marks,
                      $optionsJson, $correctOpt, $rubric, $wordLimit,
                      $answersJson, $pairsJson, $imagePath);
        }
        $qStmt->close();

        $con->commit();
        jsonSuccess(['exam_id' => $examId, 'message' => 'Exam saved successfully']);

    } catch (Throwable $e) {
        $con->rollback();
        jsonError('Database error: ' . $e->getMessage());
    }
}


// ══════════════════════════════════════════════════════════════════════════════
//  QUESTION BANK – LIST
// ══════════════════════════════════════════════════════════════════════════════
function handleBankQuestions(): void
{
    global $con, $FAC_ID;

    $search    = trim($_GET['search']       ?? '');
    $type      = sanitizeType($_GET['type'] ?? '');
    $courseId  = (int)($_GET['course_id']   ?? 0);

    // Base query – faculty can see their own + course-wide questions
    $sql    = "SELECT Bank_ID, Question_Type AS `type`, Question_Text AS question_text,
                      Marks AS marks, Options_JSON, Correct_Opt, Rubric,
                      Word_Limit, Answers_JSON, Pairs_JSON, Image_Path
               FROM question_bank
               WHERE Fac_ID = ?";
    $params = [$FAC_ID];
    $types  = 'i';

    if ($courseId) {
        $sql    .= " AND (Course_ID = ? OR Course_ID IS NULL)";
        $params[] = $courseId;
        $types   .= 'i';
    }

    if ($type) {
        $sql    .= " AND Question_Type = ?";
        $params[] = $type;
        $types   .= 's';
    }

    if ($search) {
        $sql    .= " AND MATCH(Question_Text) AGAINST(? IN BOOLEAN MODE)";
        $params[] = $search . '*';
        $types   .= 's';
    }

    $sql .= " ORDER BY Created_At DESC LIMIT 100";

    $stmt = $con->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        // Decode JSON columns so the frontend gets plain arrays
        foreach (['Options_JSON', 'Answers_JSON', 'Pairs_JSON'] as $col) {
            if ($row[$col] !== null) {
                $key       = strtolower(str_replace('_JSON', '', $col));   // options / answers / pairs
                $row[$key] = json_decode($row[$col], true);
            }
            unset($row[$col]);
        }
        $row['correct_opt'] = $row['Correct_Opt'];
        $row['word_limit']  = $row['Word_Limit'];
        $row['image_path']  = $row['Image_Path'];
        $row['rubric']      = $row['Rubric'];
        $row['marks']       = (float) $row['marks'];
        // Remove Pascal-case duplicates
        foreach (['Correct_Opt','Word_Limit','Image_Path','Rubric'] as $k) unset($row[$k]);
        $rows[] = $row;
    }

    jsonSuccess(['questions' => $rows]);
}


// ══════════════════════════════════════════════════════════════════════════════
//  IMAGE UPLOAD
// ══════════════════════════════════════════════════════════════════════════════
function handleUploadImage(): void
{
    global $FAC_ID;

    if (empty($_FILES['image'])) jsonError('No file uploaded');

    $file    = $_FILES['image'];
    $allowed = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
    $maxSize = 5 * 1024 * 1024; // 5 MB

    if ($file['error'] !== UPLOAD_ERR_OK)       jsonError('Upload error: ' . $file['error']);
    if ($file['size'] > $maxSize)               jsonError('File too large (max 5 MB)');
    if (!in_array($file['type'], $allowed, true)) jsonError('File type not allowed');

    $uploadDir = __DIR__ . '/uploads/diagrams/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        jsonError('Could not create upload directory');
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'diag_' . $FAC_ID . '_' . uniqid() . '.' . $ext;
    $dest     = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) jsonError('Failed to move uploaded file');

    $publicPath = 'uploads/diagrams/' . $filename;
    jsonSuccess(['path' => $publicPath]);
}


// ══════════════════════════════════════════════════════════════════════════════
//  HELPERS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Silently upsert a question into the bank.
 * Skips if the exact text+type already exists for this faculty.
 */
function addToBank(
    mysqli $con, int $courseId, int $facId,
    string $type, string $text, float $marks,
    ?string $optionsJson, ?string $correctOpt, ?string $rubric,
    ?int $wordLimit, ?string $answersJson, ?string $pairsJson, ?string $imagePath
): void {
    // Check duplicate (same faculty, same type, same text)
    $chk = $con->prepare(
        "SELECT Bank_ID FROM question_bank WHERE Fac_ID = ? AND Question_Type = ? AND Question_Text = ? LIMIT 1"
    );
    $chk->bind_param('iss', $facId, $type, $text);
    $chk->execute();
    $exists = $chk->get_result()->fetch_assoc();
    $chk->close();
    if ($exists) return;

    $ins = $con->prepare(
        "INSERT INTO question_bank
            (Course_ID, Fac_ID, Question_Type, Question_Text, Marks,
             Options_JSON, Correct_Opt, Rubric, Word_Limit,
             Answers_JSON, Pairs_JSON, Image_Path)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $ins->bind_param('iissdssissss',
        $courseId, $facId, $type, $text, $marks,
        $optionsJson, $correctOpt, $rubric, $wordLimit,
        $answersJson, $pairsJson, $imagePath
    );
    $ins->execute();
    $ins->close();
}

/** Parse raw POST body as JSON */
function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) jsonError('Empty request body');
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) jsonError('Invalid JSON: ' . json_last_error_msg());
    return $data;
}

/** Whitelist question types */
function sanitizeType(string $t): string
{
    return in_array($t, ['mcq','diagram','match','open','fill'], true) ? $t : '';
}

/** Convert JS datetime-local string → MySQL DATETIME or null */
function sanitizeDatetime(?string $dt): ?string
{
    if (!$dt) return null;
    $ts = strtotime($dt);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

/** Prevent path traversal in stored image paths */
function sanitizeImagePath(?string $path): ?string
{
    if (!$path) return null;
    // Allow only relative paths under uploads/
    $clean = preg_replace('/[^a-zA-Z0-9\/._-]/', '', $path);
    return str_starts_with($clean, 'uploads/') ? $clean : null;
}

/** Send a JSON success response and exit */
function jsonSuccess(array $data = []): void
{
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

/** Send a JSON error response and exit */
function jsonError(string $message, int $httpCode = 400): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
>>>>>>> f1e265abf03ca415a8e766b8518d8c076d9bf836
}