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

    require_once __DIR__ . "/../Connection/connection.php";
    if (!$con) {
        jsonError('Database connection failed', 500);
    }

    $FAC_ID = (string) $_SESSION['LoginFaculty'];
    $action = $_GET['action'] ?? '';

    // ── Route ─────────────────────────────────────────────────────────────────────
    switch ($action) {
        case 'get_exam':
            handleGetExam();
            break;
        case 'save_exam':
            handleSaveExam(false);
            break;
        case 'update_exam':
            handleSaveExam(true);
            break;
        case 'bank_questions':
            handleBankQuestions();
            break;
        case 'save_bank_questions':
            handleSaveBankQuestions();
            break;
        case 'upload_csv_questions':
            handleUploadCsvQuestions();
            break;
        case 'upload_image':
            handleUploadImage();
            break;
        default:
            jsonError('Unknown action');
    }

    // ══════════════════════════════════════════════════════════════════════════════
    //  GET EXAM DETAILS & QUESTIONS
    // ══════════════════════════════════════════════════════════════════════════════
    function handleGetExam(): void
    {
        global $con, $FAC_ID;
        $examId = (int)($_GET['exam_id'] ?? 0);
        if (!$examId) {
            jsonError('exam_id is required');
        }
        // Fetch exam header
        $stmt = $con->prepare("SELECT * FROM exams WHERE Exam_ID = ? AND Fac_ID = ?");
        $stmt->bind_param('is', $examId, $FAC_ID);
        $stmt->execute();
        $exam = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$exam) {
            jsonError('Exam not found or access denied', 404);
        }
        // Fetch exam questions
        $qStmt = $con->prepare("SELECT * FROM exam_questions WHERE Exam_ID = ? ORDER BY Sort_Order ASC");
        $qStmt->bind_param('i', $examId);
        $qStmt->execute();
        $qResult = $qStmt->get_result();
        $qStmt->close();
        $questions = [];
        while ($row = $qResult->fetch_assoc()) {
            foreach (['Options_JSON', 'Answers_JSON', 'Pairs_JSON'] as $col) {
                if ($row[$col] !== null) {
                    $key = strtolower(str_replace('_JSON', '', $col));
                    $row[$key] = json_decode($row[$col], true);
                }
                unset($row[$col]);
            }
            $row['type'] = $row['Question_Type'];
            $row['question_text'] = $row['Question_Text'];
            $row['correct_opt'] = $row['Correct_Opt'];
            $row['word_limit']  = $row['Word_Limit'];
            $row['image_path']  = $row['Image_Path'];
            $row['rubric']      = $row['Rubric'];
            $row['marks']       = (float) $row['Marks'];
            $row['co']          = (int) ($row['CO'] ?? 1);
            
            // Remove duplicates
            foreach (['Question_Type', 'Question_Text', 'Marks', 'Correct_Opt', 'Word_Limit', 'Image_Path', 'Rubric', 'CO'] as $k) {
                unset($row[$k]);
            }
            $questions[] = $row;
        }
        jsonSuccess([
            'exam' => [
                'exam_id' => (int) $exam['Exam_ID'],
                'name' => $exam['Exam_Name'],
                'duration' => (int) $exam['Duration'],
                'start_time' => !empty($exam['Start_Time']) ? date('Y-m-d\TH:i', strtotime($exam['Start_Time'])) : '',
                'end_time' => !empty($exam['End_Time']) ? date('Y-m-d\TH:i', strtotime($exam['End_Time'])) : '',
                'status' => $exam['Status']
            ],
            'questions' => $questions
        ]);
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
        $acad_year = trim($body['acad_year'] ?? '');
        $status    = in_array($body['status'] ?? '', ['draft', 'published'], true)
                        ? $body['status'] : 'draft';
        $startTime = sanitizeDatetime($body['start_time'] ?? null);
        $endTime   = sanitizeDatetime($body['end_time']   ?? null);
        $questions = $body['questions'] ?? [];

        if (!$examName)  jsonError('Exam name is required');
        if (!$duration)  jsonError('Duration is required');
        if (!$acad_year)  jsonError('Acad Year is required');
        if (!$courseId)  jsonError('Course ID is required');
        if (!is_array($questions) || count($questions) === 0) jsonError('At least one question is required');

        // ── Validate ownership / existence on update ──────────────────────────────
        $examId = $isUpdate ? (int)($body['exam_id'] ?? 0) : 0;
        if ($isUpdate) {
            if (!$examId) jsonError('exam_id is required for update');
            $chk = $con->prepare("SELECT Exam_ID FROM exams WHERE Exam_ID = ? AND Fac_ID = ?");
            $chk->bind_param('is', $examId, $FAC_ID);
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
                $stmt->bind_param('sisssis',
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
                    "INSERT INTO exams (Course_ID, Fac_ID, Acad_Year, Exam_Name, Duration, Start_Time, End_Time, Status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('isssssss',
                    $courseId, $FAC_ID, $acad_year, $examName, $duration, $startTime, $endTime, $status
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
                FROM new_question_bank
                WHERE Fac_ID = ?";
        $params = [$FAC_ID];
        $types  = 's';

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
        // return query also
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
    //  SAVE BANK QUESTIONS / UPLOAD CSV QUESTIONS
    // ══════════════════════════════════════════════════════════════════════════════

    function handleSaveBankQuestions(): void
    {
        global $con, $FAC_ID;

        $body = getJsonBody();
        $courseId  = (int)  ($body['course_id'] ?? 0);
        $questions = $body['questions'] ?? [];

        if (!$courseId) jsonError('Course ID is required');
        if (!is_array($questions) || count($questions) === 0) jsonError('At least one question is required');

        $con->begin_transaction();
        try {
            $inserted = 0;
            foreach ($questions as $q) {
                $type       = sanitizeType($q['type']          ?? '');
                $text       = trim($q['question_text']         ?? '');
                $marks      = round((float)($q['marks']        ?? 1), 1);
                $co         = (int)($q['co']                   ?? 1);
                
                if (!$type || !$text) continue; // skip malformed

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
                            $optionsJson = json_encode($cleanOpts, JSON_UNESCAPED_UNICODE);
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
                        $answersJson = is_array($answers) ? json_encode(array_values($answers), JSON_UNESCAPED_UNICODE) : null;
                        break;

                    case 'match':
                        $pairs = $q['pairs'] ?? [];
                        if (is_array($pairs) && count($pairs)) {
                            $cleanPairs = array_map(fn($p) => [
                                'a' => trim($p['a'] ?? ''),
                                'b' => trim($p['b'] ?? ''),
                            ], $pairs);
                            $pairsJson = json_encode($cleanPairs, JSON_UNESCAPED_UNICODE);
                        }
                        break;

                    case 'diagram':
                        $imagePath = sanitizeImagePath($q['image_path'] ?? null);
                        $rubric    = trim($q['rubric'] ?? '') ?: null;
                        break;
                }

                // Check duplicate (same faculty, same type, same text)
                $chk = $con->prepare(
                    "SELECT Bank_ID FROM new_question_bank WHERE Fac_ID = ? AND Question_Type = ? AND Question_Text = ? LIMIT 1"
                );
                $chk->bind_param('sss', $FAC_ID, $type, $text);
                $chk->execute();
                $exists = $chk->get_result()->fetch_assoc();
                $chk->close();

                if (!$exists) {
                    $ins = $con->prepare(
                        "INSERT INTO new_question_bank
                            (Course_ID, Fac_ID, Question_Type, Question_Text, Marks, CO,
                            Options_JSON, Correct_Opt, Rubric, Word_Limit,
                            Answers_JSON, Pairs_JSON, Image_Path)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $ins->bind_param('isssdississss',
                        $courseId, $FAC_ID, $type, $text, $marks, $co,
                        $optionsJson, $correctOpt, $rubric, $wordLimit,
                        $answersJson, $pairsJson, $imagePath
                    );
                    $ins->execute();
                    $ins->close();
                    $inserted++;
                }
            }

            $con->commit();
            jsonSuccess(['inserted' => $inserted, 'message' => "Successfully added $inserted questions to the bank!"]);
            console.log($ins);

        } catch (Throwable $e) {
            $con->rollback();
            jsonError('Database error: ' . $e->getMessage());
        }
    }

    function handleUploadCsvQuestions(): void
    {
        global $con, $FAC_ID;

        $courseId = (int)($_POST['course_id'] ?? 0);
        $type = sanitizeType($_POST['question_type'] ?? '');

        if (!$courseId) jsonError('Course ID is required');
        if (!$type) jsonError('Invalid or missing question type');

        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            jsonError('No file uploaded or upload error');
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) jsonError('Could not open CSV file');

        // Read and skip header if present
        $header = fgetcsv($handle, 4096, ',');
        if ($header) {
            $firstCol = strtolower(trim($header[0] ?? ''));
            if (!str_contains($firstCol, 'question') && !str_contains($firstCol, 'text') && !str_contains($firstCol, 'type') && !str_contains($firstCol, 'sr')) {
                // Not a header row, rewind so we parse it as data
                rewind($handle);
            }
        }

        $con->begin_transaction();
        try {
            $inserted = 0;
            while (($row = fgetcsv($handle, 4096, ',')) !== false) {
                // Skip empty rows
                if (empty($row) || (count($row) === 1 && $row[0] === null)) {
                    continue;
                }
                
                $text = trim($row[0] ?? '');
                if (!$text) continue; // skip row with empty question text

                $optionsJson  = null;
                $correctOpt   = null;
                $rubric       = null;
                $wordLimit    = null;
                $answersJson  = null;
                $pairsJson    = null;
                $imagePath    = null;
                $marks        = 1.0;
                $co           = 1;

                switch ($type) {
                    case 'mcq':
                        // Columns: Question Text, Option A, Option B, Option C, Option D, Correct Option (A/B/C/D), Marks, CO
                        $opts = [];
                        $letters = ['A', 'B', 'C', 'D'];
                        for ($i = 0; $i < 4; $i++) {
                            $opts[] = [
                                'letter' => $letters[$i],
                                'text' => trim($row[$i + 1] ?? '')
                            ];
                        }
                        $optionsJson = json_encode($opts, JSON_UNESCAPED_UNICODE);
                        
                        $raw = strtoupper(trim($row[5] ?? ''));
                        $correctOpt = in_array($raw, ['A','B','C','D'], true) ? $raw : null;
                        
                        $marks = round((float)($row[6] ?? 1.0), 1);
                        $co = (int)($row[7] ?? 1);
                        break;

                    case 'match':
                        // Columns: Question Text, Pairs (e.g. 1:a|2:b|3:c|4:d), Marks, CO
                        $pairsStr = trim($row[1] ?? '');
                        $pairs = [];
                        if ($pairsStr) {
                            foreach (explode('|', $pairsStr) as $pair) {
                                $p = explode(':', $pair);
                                if (count($p) === 2) {
                                    $pairs[] = [
                                        'a' => trim($p[0]),
                                        'b' => trim($p[1])
                                    ];
                                }
                            }
                        }
                        $pairsJson = json_encode($pairs, JSON_UNESCAPED_UNICODE);
                        $marks = round((float)($row[2] ?? 1.0), 1);
                        $co = (int)($row[3] ?? 1);
                        break;

                    case 'open':
                        // Columns: Question Text, Rubric, Word Limit, Marks, CO
                        $rubric = trim($row[1] ?? '') ?: null;
                        $wl = trim($row[2] ?? '');
                        $wordLimit = ($wl !== '' && is_numeric($wl)) ? (int)$wl : null;
                        $marks = round((float)($row[3] ?? 1.0), 1);
                        $co = (int)($row[4] ?? 1);
                        break;

                    case 'fill':
                        // Columns: Question Text, Answers (comma or pipe-separated, e.g. hydrogen|oxygen), Marks, CO
                        $ansStr = trim($row[1] ?? '');
                        $answers = [];
                        if ($ansStr !== '') {
                            $delimiters = [',', '|'];
                            $chosenDelim = ',';
                            foreach ($delimiters as $delim) {
                                if (str_contains($ansStr, $delim)) {
                                    $chosenDelim = $delim;
                                    break;
                                }
                            }
                            $answers = array_map('trim', explode($chosenDelim, $ansStr));
                        }
                        $answersJson = json_encode($answers, JSON_UNESCAPED_UNICODE);
                        $marks = round((float)($row[2] ?? 1.0), 1);
                        $co = (int)($row[3] ?? 1);
                        break;
                }

                // Check duplicate (same faculty, same type, same text)
                $chk = $con->prepare(
                    "SELECT Bank_ID FROM new_question_bank WHERE Fac_ID = ? AND Question_Type = ? AND Question_Text = ? LIMIT 1"
                );
                $chk->bind_param('sss', $FAC_ID, $type, $text);
                $chk->execute();
                $exists = $chk->get_result()->fetch_assoc();
                $chk->close();

                if (!$exists) {
                    $ins = $con->prepare(
                        "INSERT INTO new_question_bank
                            (Course_ID, Fac_ID, Question_Type, Question_Text, Marks, CO,
                            Options_JSON, Correct_Opt, Rubric, Word_Limit,
                            Answers_JSON, Pairs_JSON, Image_Path)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $ins->bind_param('isssdississss',
                        $courseId, $FAC_ID, $type, $text, $marks, $co,
                        $optionsJson, $correctOpt, $rubric, $wordLimit,
                        $answersJson, $pairsJson, $imagePath
                    );
                    $ins->execute();
                    $ins->close();
                    $inserted++;
                }
            }

            fclose($handle);
            $con->commit();
            jsonSuccess(['inserted' => $inserted, 'message' => "Successfully imported $inserted questions!"]);

        } catch (Throwable $e) {
            fclose($handle);
            $con->rollback();
            jsonError('Database error during CSV import: ' . $e->getMessage());
        }
    }


    // ══════════════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════════════════════

    /**
     * Silently upsert a question into the bank.
     * Skips if the exact text+type already exists for this faculty.
     */
    function addToBank(
        mysqli $con, int $courseId, string $facId,
        string $type, string $text, float $marks,
        ?string $optionsJson, ?string $correctOpt, ?string $rubric,
        ?int $wordLimit, ?string $answersJson, ?string $pairsJson, ?string $imagePath
    ): void {
        // Check duplicate (same faculty, same type, same text)
        $chk = $con->prepare(
            "SELECT Bank_ID FROM new_question_bank WHERE Fac_ID = ? AND Question_Type = ? AND Question_Text = ? LIMIT 1"
        );
        $chk->bind_param('sss', $facId, $type, $text);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        $chk->close();
        if ($exists) return;

        $ins = $con->prepare(
            "INSERT INTO new_question_bank
                (Course_ID, Fac_ID, Question_Type, Question_Text, Marks,
                Options_JSON, Correct_Opt, Rubric, Word_Limit,
                Answers_JSON, Pairs_JSON, Image_Path)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $ins->bind_param('isssdssissss',
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

    }
?>