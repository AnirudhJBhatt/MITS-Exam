<?php
/**
 * MITS Exam Portal - Faculty Question Bank Workbench
 * Unified Question Repository with MathLive LaTeX Rendering, CO Tagging, Diagram Uploads, & CSV Import
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

check_role('Faculty');

$fac_id = $_SESSION['LoginFaculty'] ?? $_SESSION['user_id'] ?? '';

// Download Sample CSV Template (All types or specific type)
if (isset($_GET['action']) && $_GET['action'] === 'download_csv_template') {
    $templateType = $_GET['template_type'] ?? 'all';
    $filename = $templateType === 'all' ? "question_bank_all_types_template.csv" : "question_bank_{$templateType}_template.csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $out = fopen('php://output', 'w');
    // Output UTF-8 BOM so Excel opens it with proper UTF-8 encoding
    fputs($out, "\xEF\xBB\xBF");
    
    // Headers
    fputcsv($out, ['Sl', 'Question_Type', 'Question_Text', 'OptionA', 'OptionB', 'OptionC', 'OptionD', 'Answer', 'Marks', 'CO', 'Bloom_Level', 'Rubric', 'Word_Limit']);
    
    $samples = [
        [1, 'mcq', 'What is the primary function of an arithmetic logic unit (ALU)?', 'Perform arithmetic and logic operations', 'Store persistent data', 'Manage power distribution', 'Connect input and output devices', 'A', 1.0, 1, 'Understand', '', ''],
        [2, 'mcq', 'Evaluate the limit: \\lim_{x \\to 0} \\frac{\\sin(x)}{x}', '0', '1', '\\infty', 'Undefined', 'B', 1.0, 2, 'Apply', '', ''],
        [3, 'multiselect', 'Which of the following are valid linear data structures? (Select all that apply)', 'Array', 'Binary Search Tree', 'Queue', 'Graph', 'A,C', 2.0, 1, 'Understand', '', ''],
        [4, 'fill', 'In C programming, dynamic memory is allocated on the ___ and local variables are stored on the ___.', '', '', '', '', 'heap, stack', 2.0, 2, 'Remember', '', ''],
        [5, 'match', 'Match the following network layers with their protocol data units (PDUs)', 'Transport Layer = Segment', 'Network Layer = Packet', 'Data Link Layer = Frame', 'Physical Layer = Bit', '', 2.0, 3, 'Analyze', '', ''],
        [6, 'open', 'Explain the difference between synchronous and asynchronous counters with timing waveforms.', '', '', '', '', '', 5.0, 4, 'Analyze', '2 marks for circuit diagram, 2 marks for timing waveforms, 1 mark for explanation', 250]
    ];
    
    foreach ($samples as $s) {
        if ($templateType === 'all' || $s[1] === $templateType) {
            fputcsv($out, $s);
        }
    }
    fclose($out);
    exit;
}

$flashMessage = null;
$flashType = 'info';

// Handle POST actions (Single Add, CSV Import, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // Action 1: Delete Question
    if ($action === 'delete_question') {
        $q_id = (int)($_POST['q_id'] ?? 0);
        if ($q_id > 0) {
            try {
                $del = $pdo->prepare("DELETE FROM new_question_bank WHERE Bank_ID = ? AND Fac_ID = ?");
                $del->execute([$q_id, $fac_id]);
                $flashMessage = "Question #{$q_id} removed from your repository.";
                $flashType = 'success';
            } catch (Exception $e) {
                $flashMessage = "Error deleting question: " . $e->getMessage();
                $flashType = 'danger';
            }
        }
    }

    // Action 2: Add Single Question from Modal
    elseif ($action === 'add_single_question') {
        $course_id = (int)($_POST['Course_ID'] ?? 0);
        $q_type = trim($_POST['Question_Type'] ?? 'mcq');
        $q_text = trim($_POST['Question_Text'] ?? '');
        $marks = (float)($_POST['Marks'] ?? 1.0);
        $co = (int)($_POST['CO'] ?? 1);
        $bloom = trim($_POST['Bloom_Level'] ?? 'Understand');
        $rubric = trim($_POST['Rubric'] ?? '');
        $word_limit = !empty($_POST['Word_Limit']) ? (int)$_POST['Word_Limit'] : null;

        // Process options / answers based on type
        $options_json = null;
        $correct_opt = null;
        $answers_json = null;
        $pairs_json = null;

        if ($q_type === 'mcq' || $q_type === 'multiselect') {
            $opts = [];
            $letters = ['A', 'B', 'C', 'D'];
            foreach ($letters as $l) {
                $val = trim($_POST["opt_{$l}"] ?? '');
                if ($val !== '') {
                    $opts[] = ['letter' => $l, 'text' => $val];
                }
            }
            $options_json = json_encode($opts, JSON_UNESCAPED_UNICODE);
            if ($q_type === 'mcq') {
                $correct_opt = trim($_POST['correct_mcq'] ?? 'A');
            } else {
                $sel = $_POST['correct_multi'] ?? [];
                $correct_opt = json_encode($sel);
            }
        } elseif ($q_type === 'fill') {
            $accepted = array_filter(array_map('trim', explode(',', $_POST['fill_answers'] ?? '')));
            $answers_json = json_encode(array_values($accepted), JSON_UNESCAPED_UNICODE);
            $correct_opt = implode(', ', $accepted);
        } elseif ($q_type === 'match') {
            $pairs = [];
            $lefts = $_POST['match_left'] ?? [];
            $rights = $_POST['match_right'] ?? [];
            for ($i = 0; $i < count($lefts); $i++) {
                $l = trim($lefts[$i] ?? '');
                $r = trim($rights[$i] ?? '');
                if ($l !== '' && $r !== '') {
                    $pairs[] = ['left' => $l, 'right' => $r];
                }
            }
            $pairs_json = json_encode($pairs, JSON_UNESCAPED_UNICODE);
        }

        // Handle Image Upload
        $image_path = null;
        if (isset($_FILES['diagram_file']) && $_FILES['diagram_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['diagram_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'])) {
                $uploadDir = __DIR__ . '/../Uploads/questions/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filename = 'qb_' . uniqid() . '.' . $ext;
                $dest = $uploadDir . $filename;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $image_path = 'Uploads/questions/' . $filename;
                }
            }
        }

        if ($q_text === '' || $course_id <= 0) {
            $flashMessage = 'Course selection and question statement are required.';
            $flashType = 'warning';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO new_question_bank 
                    (Course_ID, Fac_ID, Question_Type, Question_Text, Marks, CO, Bloom_Level, Options_JSON, Correct_Opt, Rubric, Word_Limit, Answers_JSON, Pairs_JSON, Image_Path)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $course_id, $fac_id, $q_type, $q_text, $marks, $co, $bloom,
                    $options_json, $correct_opt, $rubric, $word_limit, $answers_json, $pairs_json, $image_path
                ]);
                $flashMessage = 'Question successfully added to your Question Bank!';
                $flashType = 'success';
            } catch (Exception $e) {
                $flashMessage = 'Error saving question: ' . $e->getMessage();
                $flashType = 'danger';
            }
        }
    }

    // Action 3: CSV Batch Import (All Question Types Supported)
    elseif ($action === 'import_csv') {
        $course_id = (int)($_POST['Course_ID'] ?? 0);
        if ($course_id <= 0) {
            $flashMessage = 'Please select a course to import questions into.';
            $flashType = 'warning';
        } elseif (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $tmpFile = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($tmpFile, 'r');
            if ($handle !== false) {
                $rawHeader = fgetcsv($handle, 4000, ',');
                if ($rawHeader === false) {
                    $flashMessage = 'The uploaded CSV file is empty.';
                    $flashType = 'warning';
                    fclose($handle);
                } else {
                    // Strip UTF-8 BOM if present
                    if (isset($rawHeader[0])) {
                        $rawHeader[0] = preg_replace('/^\xEF\xBB\xBF/', '', $rawHeader[0]);
                    }

                    // Map column headers to column indices
                    $colMap = [];
                    foreach ($rawHeader as $idx => $name) {
                        $clean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', trim($name)));
                        if ($clean !== '') {
                            $colMap[$clean] = $idx;
                        }
                    }

                    // Check if this row is actually a header row
                    $hasHeader = isset($colMap['questiontext']) || isset($colMap['question']) || isset($colMap['statement'])
                              || isset($colMap['qtext']) || isset($colMap['type']) || isset($colMap['questiontype'])
                              || isset($colMap['sl']) || isset($colMap['sno']) || isset($colMap['opta']) || isset($colMap['answer']);

                    if (!$hasHeader) {
                        // File has no header row, rewind to row 0
                        fseek($handle, 0);
                        $typeIdx  = null;
                        $qTextIdx = 1;
                        $optAIdx  = 2;
                        $optBIdx  = 3;
                        $optCIdx  = 4;
                        $optDIdx  = 5;
                        $ansIdx   = 6;
                        $marksIdx = 7;
                        $coIdx    = 8;
                        $bloomIdx = null;
                        $rubricIdx = null;
                        $wlIdx    = null;
                    } else {
                        $typeIdx  = $colMap['questiontype'] ?? $colMap['type'] ?? $colMap['qtype'] ?? $colMap['format'] ?? null;
                        $qTextIdx = $colMap['questiontext'] ?? $colMap['question'] ?? $colMap['statement'] ?? $colMap['qtext'] ?? $colMap['questionstatement'] ?? $colMap['prompt'] ?? null;
                        if ($qTextIdx === null && count($rawHeader) >= 2) {
                            $qTextIdx = 1; // Default to column 2 if column 1 was Sl
                        }
                        $optAIdx  = $colMap['opta'] ?? $colMap['optiona'] ?? $colMap['opt1'] ?? $colMap['option1'] ?? $colMap['a'] ?? null;
                        $optBIdx  = $colMap['optb'] ?? $colMap['optionb'] ?? $colMap['opt2'] ?? $colMap['option2'] ?? $colMap['b'] ?? null;
                        $optCIdx  = $colMap['optc'] ?? $colMap['optionc'] ?? $colMap['opt3'] ?? $colMap['option3'] ?? $colMap['c'] ?? null;
                        $optDIdx  = $colMap['optd'] ?? $colMap['optiond'] ?? $colMap['opt4'] ?? $colMap['option4'] ?? $colMap['d'] ?? null;
                        $ansIdx   = $colMap['answer'] ?? $colMap['correctopt'] ?? $colMap['correctanswer'] ?? $colMap['ans'] ?? $colMap['correct'] ?? $colMap['answers'] ?? null;
                        $marksIdx = $colMap['marks'] ?? $colMap['mark'] ?? $colMap['score'] ?? $colMap['points'] ?? null;
                        $coIdx    = $colMap['co'] ?? $colMap['courseoutcome'] ?? null;
                        $bloomIdx = $colMap['bloomlevel'] ?? $colMap['bloom'] ?? $colMap['bloomslevel'] ?? $colMap['level'] ?? null;
                        $rubricIdx = $colMap['rubric'] ?? $colMap['evaluationrubric'] ?? $colMap['markingscheme'] ?? $colMap['criteria'] ?? null;
                        $wlIdx    = $colMap['wordlimit'] ?? $colMap['words'] ?? $colMap['limit'] ?? null;
                    }

                    $imported = 0;
                    $typeCounts = ['mcq' => 0, 'multiselect' => 0, 'fill' => 0, 'match' => 0, 'open' => 0];
                    $rowNum = $hasHeader ? 1 : 0;

                    $pdo->beginTransaction();
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO new_question_bank 
                            (Course_ID, Fac_ID, Question_Type, Question_Text, Marks, CO, Bloom_Level, Options_JSON, Correct_Opt, Rubric, Word_Limit, Answers_JSON, Pairs_JSON, Image_Path)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)
                        ");

                        while (($row = fgetcsv($handle, 4000, ',')) !== false) {
                            $rowNum++;
                            // Check if row has any content
                            $hasContent = false;
                            foreach ($row as $val) {
                                if (trim($val) !== '') { $hasContent = true; break; }
                            }
                            if (!$hasContent) continue;

                            $q_text = $qTextIdx !== null ? trim($row[$qTextIdx] ?? '') : '';
                            if ($q_text === '') {
                                continue;
                            }

                            // 1. Determine Question Type
                            $rawType = $typeIdx !== null ? strtolower(trim($row[$typeIdx] ?? '')) : '';
                            if (in_array($rawType, ['mcq', 'single', 'single_choice', 'single choice', 'scq'], true)) {
                                $q_type = 'mcq';
                            } elseif (in_array($rawType, ['multiselect', 'multi', 'multiple', 'multiple_select', 'multiple choice', 'multi_select', 'msq', 'checkbox'], true)) {
                                $q_type = 'multiselect';
                            } elseif (in_array($rawType, ['fill', 'fill_blanks', 'fill in the blanks', 'fib', 'blank', 'blanks'], true)) {
                                $q_type = 'fill';
                            } elseif (in_array($rawType, ['match', 'match_following', 'matching', 'match the following', 'mtf'], true)) {
                                $q_type = 'match';
                            } elseif (in_array($rawType, ['open', 'open_ended', 'subjective', 'descriptive', 'rubric', 'essay'], true)) {
                                $q_type = 'open';
                            } else {
                                // Auto-detect question type
                                if (str_contains($q_text, '___')) {
                                    $q_type = 'fill';
                                } elseif ($rubricIdx !== null && !empty(trim($row[$rubricIdx] ?? ''))) {
                                    $q_type = 'open';
                                } else {
                                    $rawAnsCheck = $ansIdx !== null ? trim($row[$ansIdx] ?? '') : '';
                                    if (preg_match('/^[A-D](\s*[,;|\s]\s*[A-D])+$/i', $rawAnsCheck)) {
                                        $q_type = 'multiselect';
                                    } else {
                                        $q_type = 'mcq';
                                    }
                                }
                            }

                            // 2. Metadata (Marks, CO, Bloom_Level)
                            $marks = 1.0;
                            if ($marksIdx !== null && isset($row[$marksIdx]) && is_numeric(trim($row[$marksIdx]))) {
                                $mVal = (float)trim($row[$marksIdx]);
                                if ($mVal > 0) $marks = $mVal;
                            }

                            $co = 1;
                            if ($coIdx !== null && isset($row[$coIdx])) {
                                $cleanCo = preg_replace('/[^0-9]/', '', trim($row[$coIdx]));
                                if ($cleanCo !== '') {
                                    $coVal = (int)$cleanCo;
                                    if ($coVal >= 1 && $coVal <= 5) $co = $coVal;
                                }
                            }

                            $bloom = 'Understand';
                            if ($bloomIdx !== null && !empty(trim($row[$bloomIdx] ?? ''))) {
                                $rawBloom = ucfirst(strtolower(trim($row[$bloomIdx])));
                                $validBlooms = ['Remember', 'Understand', 'Apply', 'Analyze', 'Evaluate', 'Create'];
                                foreach ($validBlooms as $vb) {
                                    if (strcasecmp($vb, $rawBloom) === 0) {
                                        $bloom = $vb;
                                        break;
                                    }
                                }
                            }

                            // 3. Type-Specific Parsing
                            $options_json = null;
                            $correct_opt  = null;
                            $answers_json = null;
                            $pairs_json   = null;
                            $rubric       = null;
                            $word_limit   = null;

                            switch ($q_type) {
                                case 'mcq':
                                    $optA = $optAIdx !== null ? trim($row[$optAIdx] ?? '') : '';
                                    $optB = $optBIdx !== null ? trim($row[$optBIdx] ?? '') : '';
                                    $optC = $optCIdx !== null ? trim($row[$optCIdx] ?? '') : '';
                                    $optD = $optDIdx !== null ? trim($row[$optDIdx] ?? '') : '';
                                    $opts = [
                                        ['letter' => 'A', 'text' => $optA],
                                        ['letter' => 'B', 'text' => $optB],
                                        ['letter' => 'C', 'text' => $optC],
                                        ['letter' => 'D', 'text' => $optD]
                                    ];
                                    $options_json = json_encode($opts, JSON_UNESCAPED_UNICODE);
                                    
                                    $rawAns = $ansIdx !== null ? trim($row[$ansIdx] ?? 'A') : 'A';
                                    if (preg_match('/[A-D]/i', $rawAns, $m)) {
                                        $correct_opt = strtoupper($m[0]);
                                    } else {
                                        $correct_opt = 'A';
                                    }
                                    break;

                                case 'multiselect':
                                    $optA = $optAIdx !== null ? trim($row[$optAIdx] ?? '') : '';
                                    $optB = $optBIdx !== null ? trim($row[$optBIdx] ?? '') : '';
                                    $optC = $optCIdx !== null ? trim($row[$optCIdx] ?? '') : '';
                                    $optD = $optDIdx !== null ? trim($row[$optDIdx] ?? '') : '';
                                    $opts = [
                                        ['letter' => 'A', 'text' => $optA],
                                        ['letter' => 'B', 'text' => $optB],
                                        ['letter' => 'C', 'text' => $optC],
                                        ['letter' => 'D', 'text' => $optD]
                                    ];
                                    $options_json = json_encode($opts, JSON_UNESCAPED_UNICODE);

                                    $rawAns = $ansIdx !== null ? trim($row[$ansIdx] ?? '') : '';
                                    $letters = [];
                                    if (preg_match_all('/[A-D]/i', $rawAns, $matches)) {
                                        $letters = array_values(array_unique(array_map('strtoupper', $matches[0])));
                                        sort($letters);
                                    }
                                    if (empty($letters)) $letters = ['A'];
                                    $answers_json = json_encode($letters, JSON_UNESCAPED_UNICODE);
                                    $correct_opt = implode(',', $letters);
                                    break;

                                case 'fill':
                                    $rawAns = $ansIdx !== null ? trim($row[$ansIdx] ?? '') : '';
                                    $accepted = array_filter(array_map('trim', preg_split('/[,;|]+/', $rawAns)), fn($v) => $v !== '');
                                    if (empty($accepted) && $rawAns !== '') {
                                        $accepted = [$rawAns];
                                    }
                                    $answers_json = json_encode(array_values($accepted), JSON_UNESCAPED_UNICODE);
                                    $correct_opt = implode(', ', $accepted);
                                    break;

                                case 'match':
                                    $pairs = [];
                                    // 1. Check dedicated premise/match columns
                                    for ($i = 1; $i <= 4; $i++) {
                                        $pIdx = $colMap["premise{$i}"] ?? $colMap["left{$i}"] ?? null;
                                        $mIdx = $colMap["match{$i}"] ?? $colMap["right{$i}"] ?? null;
                                        if ($pIdx !== null && $mIdx !== null) {
                                            $pl = trim($row[$pIdx] ?? '');
                                            $mr = trim($row[$mIdx] ?? '');
                                            if ($pl !== '' || $mr !== '') {
                                                $pairs[] = ['left' => $pl, 'right' => $mr, 'a' => $pl, 'b' => $mr];
                                            }
                                        }
                                    }

                                    // 2. Check OptionA..D for delimiters "=" or "->" or ":"
                                    if (empty($pairs)) {
                                        $rawOpts = [
                                            $optAIdx !== null ? trim($row[$optAIdx] ?? '') : '',
                                            $optBIdx !== null ? trim($row[$optBIdx] ?? '') : '',
                                            $optCIdx !== null ? trim($row[$optCIdx] ?? '') : '',
                                            $optDIdx !== null ? trim($row[$optDIdx] ?? '') : ''
                                        ];
                                        $hasDelim = false;
                                        foreach ($rawOpts as $ro) {
                                            if (str_contains($ro, '=') || str_contains($ro, '->') || str_contains($ro, ':')) {
                                                $hasDelim = true;
                                                break;
                                            }
                                        }
                                        if ($hasDelim) {
                                            foreach ($rawOpts as $ro) {
                                                if ($ro === '') continue;
                                                $parts = preg_split('/(=|->|:)/', $ro, 2);
                                                $l = trim($parts[0] ?? '');
                                                $r = trim($parts[1] ?? '');
                                                if ($l !== '' || $r !== '') {
                                                    $pairs[] = ['left' => $l, 'right' => $r, 'a' => $l, 'b' => $r];
                                                }
                                            }
                                        } else {
                                            // 3. OptionA..D are premises, Answer has matches split by | or ;
                                            $rawAns = $ansIdx !== null ? trim($row[$ansIdx] ?? '') : '';
                                            $matches = array_map('trim', preg_split('/[|;]+/', $rawAns));
                                            for ($i = 0; $i < 4; $i++) {
                                                $premise = $rawOpts[$i] ?? '';
                                                $match = $matches[$i] ?? '';
                                                if ($premise !== '' || $match !== '') {
                                                    $pairs[] = ['left' => $premise, 'right' => $match, 'a' => $premise, 'b' => $match];
                                                }
                                            }
                                        }
                                    }
                                    $pairs_json = json_encode($pairs, JSON_UNESCAPED_UNICODE);
                                    break;

                                case 'open':
                                    $rubric = $rubricIdx !== null ? trim($row[$rubricIdx] ?? '') : '';
                                    if ($rubric === '' && $ansIdx !== null) {
                                        $rubric = trim($row[$ansIdx] ?? '');
                                    }
                                    if ($wlIdx !== null && !empty(trim($row[$wlIdx] ?? '')) && is_numeric(trim($row[$wlIdx]))) {
                                        $word_limit = (int)trim($row[$wlIdx]);
                                    }
                                    break;
                            }

                            $stmt->execute([
                                $course_id, $fac_id, $q_type, $q_text, $marks, $co, $bloom,
                                $options_json, $correct_opt, $rubric, $word_limit, $answers_json, $pairs_json
                            ]);
                            $imported++;
                            $typeCounts[$q_type]++;
                        }

                        $pdo->commit();

                        $breakdown = [];
                        foreach ($typeCounts as $t => $cnt) {
                            if ($cnt > 0) {
                                $breakdown[] = "{$cnt} " . strtoupper($t);
                            }
                        }
                        $breakdownStr = !empty($breakdown) ? " (" . implode(', ', $breakdown) . ")" : "";
                        $flashMessage = "Successfully imported {$imported} questions{$breakdownStr} from CSV.";
                        $flashType = 'success';
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        $flashMessage = "CSV Import failed on line {$rowNum}: " . $e->getMessage();
                        $flashType = 'danger';
                    }
                    fclose($handle);
                }
            } else {
                $flashMessage = 'Unable to read the uploaded CSV file.';
                $flashType = 'danger';
            }
        } else {
            $flashMessage = 'Please select a valid CSV file to upload.';
            $flashType = 'warning';
        }
    }
}

// Fetch assigned courses for this faculty
$stmtCourses = $pdo->prepare("
    SELECT c.Course_ID, c.Course_Code, c.Course_Name, c.Semester, cm.Acad_Year 
    FROM course_mapping cm
    JOIN courses c ON cm.Course_ID = c.Course_ID
    WHERE cm.Fac_ID = ?
    ORDER BY c.Course_Name ASC
");
$stmtCourses->execute([$fac_id]);
$myCourses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

// Filters
$filterCourse = $_GET['course'] ?? '';
$filterType   = $_GET['type'] ?? '';
$filterCO     = $_GET['co'] ?? '';
$filterSearch = trim($_GET['search'] ?? '');

$conditions = ["qb.Fac_ID = ?"];
$params = [$fac_id];

if ($filterCourse !== '') {
    $conditions[] = "qb.Course_ID = ?";
    $params[] = $filterCourse;
}
if ($filterType !== '') {
    $conditions[] = "qb.Question_Type = ?";
    $params[] = $filterType;
}
if ($filterCO !== '') {
    $conditions[] = "qb.CO = ?";
    $params[] = $filterCO;
}
if ($filterSearch !== '') {
    $conditions[] = "qb.Question_Text LIKE ?";
    $params[] = '%' . $filterSearch . '%';
}

$whereSql = "WHERE " . implode(" AND ", $conditions);

$query = "
    SELECT qb.*, c.Course_Name, c.Course_Code, c.Semester
    FROM new_question_bank qb
    LEFT JOIN courses c ON qb.Course_ID = c.Course_ID
    {$whereSql}
    ORDER BY qb.Bank_ID DESC
";

$stmtQB = $pdo->prepare($query);
$stmtQB->execute($params);
$questions = $stmtQB->fetchAll(PDO::FETCH_ASSOC);

/**
 * Format mathematical expressions for live rendering in Question Bank.
 * Handles MathLive LaTeX strings, standard LaTeX delimiters ($$, $, \(, \[), and plain text.
 */
function formatMath($text) {
    if ($text === null || $text === '') return '';
    $raw = trim($text);

    // 1. Check if it has LaTeX delimiters ($$, $, \(, \[)
    $hasDelim = (strpos($raw, '$$') !== false) ||
                (strpos($raw, '\\(') !== false) ||
                (strpos($raw, '\\[') !== false) ||
                preg_match('/(?<!\$)\$(?!\$)(.*?)(?<!\$)\$(?!\$)/', $raw);

    $hasLatex = preg_match('/\\\\[a-zA-Z]+|[\^_]/', $raw);

    if (!$hasDelim && !$hasLatex) {
        return nl2br(htmlspecialchars($raw, ENT_QUOTES, 'UTF-8'));
    }

    if ($hasDelim) {
        $res = $raw;
        // Replace $$ ... $$
        $res = preg_replace_callback('/\$\$([\s\S]*?)\$\$/', function($m) {
            return '<math-field read-only class="math-rendered math-block" style="border:none; background:transparent; padding:0; display:block; font-size:1.1em; text-align:center; outline:none; margin:6px 0;">' . htmlspecialchars(trim($m[1]), ENT_QUOTES, 'UTF-8') . '</math-field>';
        }, $res);

        // Replace \[ ... \]
        $res = preg_replace_callback('/\\\\\[([\s\S]*?)\\\\\]/', function($m) {
            return '<math-field read-only class="math-rendered math-block" style="border:none; background:transparent; padding:0; display:block; font-size:1.1em; text-align:center; outline:none; margin:6px 0;">' . htmlspecialchars(trim($m[1]), ENT_QUOTES, 'UTF-8') . '</math-field>';
        }, $res);

        // Replace \( ... \)
        $res = preg_replace_callback('/\\\\\(([\s\S]*?)\\\\\)/', function($m) {
            return '<math-field read-only class="math-rendered math-inline" style="border:none; background:transparent; padding:0; display:inline-block; font-size:1.05em; outline:none; vertical-align:middle;">' . htmlspecialchars(trim($m[1]), ENT_QUOTES, 'UTF-8') . '</math-field>';
        }, $res);

        // Replace $ ... $
        $res = preg_replace_callback('/(?<!\$)\$(?!\$)(.*?)(?<!\$)\$(?!\$)/', function($m) {
            return '<math-field read-only class="math-rendered math-inline" style="border:none; background:transparent; padding:0; display:inline-block; font-size:1.05em; outline:none; vertical-align:middle;">' . htmlspecialchars(trim($m[1]), ENT_QUOTES, 'UTF-8') . '</math-field>';
        }, $res);

        return nl2br($res);
    }

    // No delimiters, but has LaTeX
    // 2. If it contains \text (standard MathLive compound expression), or starts with a backslash
    if (str_starts_with($raw, '\\') || strpos($raw, '\\text') !== false) {
        return '<math-field read-only class="math-rendered math-inline" style="border:none; background:transparent; padding:0; display:inline-block; font-size:1.05em; outline:none; vertical-align:middle; max-width:100%;">' . htmlspecialchars($raw, ENT_QUOTES, 'UTF-8') . '</math-field>';
    }

    // 3. Check if there is plain text prefix before the first backslash (e.g. "Evaluate the limit: \lim...")
    $firstSlash = strpos($raw, '\\');
    if ($firstSlash !== false && $firstSlash > 0) {
        $prefix = substr($raw, 0, $firstSlash);
        $mathPart = substr($raw, $firstSlash);
        if (preg_match('/[a-zA-Z]{3,}\s*[:?]?\s*$/', $prefix)) {
            return htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . '<math-field read-only class="math-rendered math-inline" style="border:none; background:transparent; padding:0; display:inline-block; font-size:1.05em; outline:none; vertical-align:middle; max-width:100%;">' . htmlspecialchars($mathPart, ENT_QUOTES, 'UTF-8') . '</math-field>';
        } else {
            return '<math-field read-only class="math-rendered math-inline" style="border:none; background:transparent; padding:0; display:inline-block; font-size:1.05em; outline:none; vertical-align:middle; max-width:100%;">' . htmlspecialchars($raw, ENT_QUOTES, 'UTF-8') . '</math-field>';
        }
    }

    // 4. Has ^ or _ without backslash (e.g. x^2 + y^2 = 1)
    return '<math-field read-only class="math-rendered math-inline" style="border:none; background:transparent; padding:0; display:inline-block; font-size:1.05em; outline:none; vertical-align:middle; max-width:100%;">' . htmlspecialchars($raw, ENT_QUOTES, 'UTF-8') . '</math-field>';
}

include '../Common/header.php';
include '../Common/faculty-sidebar.php';
?>

<!-- MathLive & KaTeX CDN for live equations -->
<script defer src="https://unpkg.com/mathlive"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js" onload="renderMathInPage()"></script>

<style>
    math-field:not([read-only]) {
        width: 100%;
        min-height: 42px;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 6px 10px;
        font-size: 15px;
        background: #ffffff;
        display: block;
        transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }
    math-field:not([read-only]):focus-within {
        outline: none;
        border-color: #d1202d;
        box-shadow: 0 0 0 0.25rem rgba(209, 32, 45, 0.2);
    }
    math-field:not([read-only])::part(virtual-keyboard-toggle) {
        color: #d1202d;
    }
    math-field[read-only] {
        width: auto !important;
        min-height: unset !important;
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        padding: 0 !important;
        display: inline-block !important;
        font-size: 1.05em !important;
        vertical-align: middle !important;
        cursor: default !important;
        pointer-events: none !important;
    }
    math-field[read-only].math-block {
        display: block !important;
        text-align: center !important;
        margin: 6px 0 !important;
    }
    math-field[read-only]:focus-within {
        border-color: transparent !important;
        box-shadow: none !important;
    }
    math-field[read-only]::part(virtual-keyboard-toggle) {
        display: none !important;
    }
    .math-content {
        line-height: 1.6;
        word-break: break-word;
    }
    .math-content math-field {
        color: inherit;
    }
    :root {
        --keyboard-zindex: 2200;
    }
    .ML__keyboard, div.MLK__backdrop, .MLK__container, div[role="dialog"].ML__keyboard {
        z-index: 2200 !important;
    }
    .math-quick-bar button:hover {
        background-color: #e2e8f0;
    }
</style>

<main>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="bi bi-folder-check text-danger me-2"></i>Question Bank
            </h3>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-danger" onclick="window.history.back()">
                <i class="bi bi-arrow-left me-1"></i> Back
            </button>
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                <i class="bi bi-plus-circle me-1"></i> Add Question
            </button>
        </div>
    </div>

    <?php if ($flashMessage): ?>
        <div class="alert alert-<?= $flashType ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi <?= $flashType === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
            <?= e($flashMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Bar Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted">Subject / Course</label>
                    <select name="course" class="form-select" onchange="this.form.submit()">
                        <option value="">All Assigned Courses</option>
                        <?php foreach ($myCourses as $mc): ?>
                            <option value="<?= e($mc['Course_ID']) ?>" <?= $filterCourse == $mc['Course_ID'] ? 'selected' : '' ?>>
                                <?= e($mc['Course_Code'] ? $mc['Course_Code'] . ' - ' : '') ?><?= e($mc['Course_Name']) ?> (S<?= e($mc['Semester']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-muted">Question Format</label>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">All Formats</option>
                        <option value="mcq" <?= $filterType === 'mcq' ? 'selected' : '' ?>>Single Choice (MCQ)</option>
                        <option value="multiselect" <?= $filterType === 'multiselect' ? 'selected' : '' ?>>Multiple Select</option>
                        <option value="match" <?= $filterType === 'match' ? 'selected' : '' ?>>Match Following</option>
                        <option value="fill" <?= $filterType === 'fill' ? 'selected' : '' ?>>Fill Blanks</option>
                        <option value="open" <?= $filterType === 'open' ? 'selected' : '' ?>>Open-Ended (Rubric)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-muted">Course Outcome</label>
                    <select name="co" class="form-select" onchange="this.form.submit()">
                        <option value="">All COs (1-5)</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= $filterCO == $i ? 'selected' : '' ?>>CO<?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted">Search Statement</label>
                    <input type="text" name="search" class="form-control" placeholder="Search keywords..." value="<?= e($filterSearch) ?>">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Questions Directory List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-collection me-2"></i>Authored Questions (<?= count($questions) ?>)
            </h6>
        </div>

        <div class="card-body p-0">
            <?php if (empty($questions)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-folder2-open fs-1 text-muted d-block mb-3"></i>
                    <h5 class="fw-bold text-dark">No Questions Found</h5>
                    <p class="text-muted small mb-3">You haven't added questions matching this criteria yet.</p>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                        <i class="bi bi-plus-circle me-1"></i> Author Question Now
                    </button>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($questions as $idx => $q): ?>
                        <div class="list-group-item p-4 border-bottom">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="badge bg-light text-dark border font-monospace">#<?= e($q['Bank_ID']) ?></span>
                                    <span class="badge bg-primary-subtle text-primary fw-semibold text-uppercase px-2 py-1">
                                        <?= e($q['Question_Type']) ?>
                                    </span>
                                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                                        CO<?= (int)$q['CO'] ?>
                                    </span>
                                    <span class="badge bg-info-subtle text-info-emphasis px-2 py-1">
                                        <?= (float)$q['Marks'] ?> Mark<?= (float)$q['Marks'] > 1 ? 's' : '' ?>
                                    </span>
                                    <span class="badge bg-light text-muted border">
                                        <?= e($q['Bloom_Level'] ?? 'Understand') ?>
                                    </span>
                                    <?php if (!empty($q['Course_Name'])): ?>
                                        <span class="badge bg-dark-subtle text-dark">
                                            <?= e($q['Course_Code']) ?> &bull; <?= e($q['Course_Name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Remove question #<?= e($q['Bank_ID']) ?> from repository?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_question">
                                        <input type="hidden" name="q_id" value="<?= e($q['Bank_ID']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Question">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Question Statement -->
                            <div class="fw-semibold text-dark fs-6 mb-2 math-content">
                                <?= formatMath($q['Question_Text']) ?>
                            </div>

                            <!-- Attached Diagram -->
                            <?php if (!empty($q['Image_Path'])): ?>
                                <div class="mb-3">
                                    <img src="../<?= e($q['Image_Path']) ?>" alt="Diagram" class="img-thumbnail" style="max-height: 160px;">
                                </div>
                            <?php endif; ?>

                            <!-- Options / Answers Display -->
                            <?php if ($q['Question_Type'] === 'mcq' || $q['Question_Type'] === 'multiselect'): ?>
                                <?php 
                                    $opts = json_decode($q['Options_JSON'] ?? '[]', true); 
                                    $corr = $q['Correct_Opt'];
                                ?>
                                <div class="row g-2 mt-1">
                                    <?php foreach ($opts as $opt): ?>
                                        <?php 
                                                $isCorrect = false;
                                                if ($q['Question_Type'] === 'mcq') {
                                                    $isCorrect = ($corr === $opt['letter'] || $corr === $opt['text']);
                                                } else {
                                                    $corrArr = json_decode($corr ?? '[]', true);
                                                    if (!is_array($corrArr) || empty($corrArr)) {
                                                        $corrArr = json_decode($q['Answers_JSON'] ?? '[]', true);
                                                    }
                                                    if (!is_array($corrArr) && !empty($corr)) {
                                                        $corrArr = explode(',', $corr);
                                                    }
                                                    $corrArr = array_map('trim', (array)$corrArr);
                                                    $isCorrect = in_array($opt['letter'], $corrArr) || in_array($opt['text'], $corrArr);
                                                }
                                            ?>
                                            <div class="col-md-6">
                                                <div class="p-2 rounded border <?= $isCorrect ? 'bg-success-subtle border-success text-success-emphasis fw-semibold' : 'bg-light text-dark' ?> small math-content d-flex align-items-center">
                                                    <span class="badge <?= $isCorrect ? 'bg-success text-white' : 'bg-secondary text-white' ?> me-2 flex-shrink-0">
                                                        <?= e($opt['letter']) ?>
                                                    </span>
                                                    <div class="flex-grow-1"><?= formatMath($opt['text']) ?></div>
                                                    <?php if ($isCorrect): ?>
                                                        <i class="bi bi-check-circle-fill text-success ms-2 flex-shrink-0"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                <?php elseif ($q['Question_Type'] === 'fill'): ?>
                                    <div class="mt-2 small">
                                        <span class="text-muted fw-semibold">Accepted Answers:</span>
                                        <span class="badge bg-success-subtle text-success fs-7 ms-1 math-content">
                                            <?= formatMath($q['Correct_Opt'] ?: implode(', ', json_decode($q['Answers_JSON'] ?? '[]', true) ?: [])) ?>
                                        </span>
                                    </div>

                                <?php elseif ($q['Question_Type'] === 'match'): ?>
                                    <?php $pairs = json_decode($q['Pairs_JSON'] ?? '[]', true) ?: []; ?>
                                    <div class="mt-2 table-responsive">
                                        <table class="table table-sm table-bordered bg-light mb-0 small">
                                            <thead class="table-secondary">
                                                <tr><th>Premise</th><th>Correct Match</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pairs as $p): ?>
                                                    <tr>
                                                        <td class="math-content"><?= formatMath($p['left'] ?? $p['a'] ?? '') ?></td>
                                                        <td class="fw-semibold text-success math-content"><?= formatMath($p['right'] ?? $p['b'] ?? '') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                </div>

                            <?php elseif ($q['Question_Type'] === 'open'): ?>
                                <?php if (!empty($q['Rubric'])): ?>
                                    <div class="mt-2 p-2 bg-light border rounded small">
                                        <strong class="text-secondary"><i class="bi bi-card-checklist me-1"></i>Evaluation Rubric:</strong>
                                        <div class="text-muted mt-1 math-content"><?= formatMath($q['Rubric']) ?></div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" aria-labelledby="addQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_single_question">

                <div class="modal-header bg-light">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="modal-title fw-bold mb-0" id="addQuestionModalLabel">
                            <i class="bi bi-patch-question me-2"></i>Author Question
                        </h5>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle"><i class="bi bi-calculator me-1"></i>MathLive Enabled</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Course <span class="text-danger">*</span></label>
                        <select name="Course_ID" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php foreach ($myCourses as $mc): ?>
                                <option value="<?= e($mc['Course_ID']) ?>">
                                    <?= e($mc['Course_Code'] ? $mc['Course_Code'] . ' - ' : '') ?><?= e($mc['Course_Name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Question Type <span class="text-danger">*</span></label>
                        <select name="Question_Type" id="modalQType" class="form-select" required onchange="toggleTypeFields()">
                            <option value="mcq">Multiple Choice (Single Option)</option>
                            <option value="multiselect">Multiple Select (Checkboxes)</option>
                            <option value="fill">Fill in the Blanks</option>
                            <option value="match">Match the Following</option>
                            <option value="open">Open Ended / Subjective</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Course Outcome (CO)</label>
                        <select name="CO" class="form-select">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>">CO<?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Marks Allocated</label>
                        <input type="number" step="0.5" name="Marks" class="form-control" value="1.0" min="0.5" max="50" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Bloom's Level</label>
                        <select name="Bloom_Level" class="form-select">
                            <option value="Remember">Remember</option>
                            <option value="Understand" selected>Understand</option>
                            <option value="Apply">Apply</option>
                            <option value="Analyze">Analyze</option>
                            <option value="Evaluate">Evaluate</option>
                            <option value="Create">Create</option>
                        </select>
                    </div>

                    <!-- Question Statement with MathLive -->
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold mb-0">Question Statement <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 toggle-math-kb-btn" title="Toggle Virtual Math Keyboard">
                                <i class="bi bi-keyboard me-1"></i> Virtual Math Keyboard
                            </button>
                        </div>
                        <div class="math-field-wrapper position-relative">
                            <math-field id="modal_qText" style="min-height: 60px;" placeholder="Enter question statement or type equations using the MathLive keyboard…"></math-field>
                            <input type="hidden" name="Question_Text" id="qTextInput" required>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-1 math-quick-bar">
                            <small class="text-muted me-1 align-self-center" style="font-size: 11px;">Quick Math:</small>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('modal_qText', '\\frac{#@}{#?}')">½ frac</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('modal_qText', '\\sqrt{#?}')">√ sqrt</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('modal_qText', '#@^{#?}')">x² sup</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('modal_qText', '#@_{#?}')">x₁ sub</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('modal_qText', '\\pm')">±</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('modal_qText', '\\pi')">π</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('modal_qText', '\\theta')">θ</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('modal_qText', '\\sum_{#?}^{#?}')">∑</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('modal_qText', '\\int_{#?}^{#?}')">∫</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('modal_qText', '\\infty')">∞</button>
                        </div>
                    </div>

                    <!-- Diagram Upload -->
                    <div class="col-12">
                        <label class="form-label fw-semibold">Schematic / Diagram Image (Optional)</label>
                        <input type="file" name="diagram_file" class="form-control" accept=".png,.jpg,.jpeg,.webp,.svg">
                        <div class="form-text">Attach circuit diagrams, graphs, or schematics if required.</div>
                    </div>

                    <!-- MCQ Fields with MathLive -->
                    <div id="mcqFields" class="col-12 row g-2">
                        <div class="col-12 d-flex justify-content-between align-items-center mt-2 mb-1">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-ui-radios-grid me-1 text-danger"></i>Answer Options & Correct Choice</h6>
                            <small class="text-muted" style="font-size: 11px;">Select the radio/checkbox to mark correct answer</small>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2 p-1 border rounded bg-white">
                                <input type="radio" name="correct_mcq" value="A" class="form-check-input opt-mcq-radio mt-0 flex-shrink-0" checked title="Mark Option A as correct">
                                <input type="checkbox" name="correct_multi[]" value="A" class="form-check-input opt-multi-check mt-0 flex-shrink-0 d-none" title="Mark Option A as correct">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle fw-bold">A</span>
                                <math-field class="opt-math flex-grow-1" id="mf_opt_A" placeholder="Option A formula / text…"></math-field>
                                <input type="hidden" name="opt_A" id="hidden_opt_A">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2 p-1 border rounded bg-white">
                                <input type="radio" name="correct_mcq" value="B" class="form-check-input opt-mcq-radio mt-0 flex-shrink-0" title="Mark Option B as correct">
                                <input type="checkbox" name="correct_multi[]" value="B" class="form-check-input opt-multi-check mt-0 flex-shrink-0 d-none" title="Mark Option B as correct">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle fw-bold">B</span>
                                <math-field class="opt-math flex-grow-1" id="mf_opt_B" placeholder="Option B formula / text…"></math-field>
                                <input type="hidden" name="opt_B" id="hidden_opt_B">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2 p-1 border rounded bg-white">
                                <input type="radio" name="correct_mcq" value="C" class="form-check-input opt-mcq-radio mt-0 flex-shrink-0" title="Mark Option C as correct">
                                <input type="checkbox" name="correct_multi[]" value="C" class="form-check-input opt-multi-check mt-0 flex-shrink-0 d-none" title="Mark Option C as correct">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle fw-bold">C</span>
                                <math-field class="opt-math flex-grow-1" id="mf_opt_C" placeholder="Option C formula / text…"></math-field>
                                <input type="hidden" name="opt_C" id="hidden_opt_C">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2 p-1 border rounded bg-white">
                                <input type="radio" name="correct_mcq" value="D" class="form-check-input opt-mcq-radio mt-0 flex-shrink-0" title="Mark Option D as correct">
                                <input type="checkbox" name="correct_multi[]" value="D" class="form-check-input opt-multi-check mt-0 flex-shrink-0 d-none" title="Mark Option D as correct">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle fw-bold">D</span>
                                <math-field class="opt-math flex-grow-1" id="mf_opt_D" placeholder="Option D formula / text…"></math-field>
                                <input type="hidden" name="opt_D" id="hidden_opt_D">
                            </div>
                        </div>
                    </div>

                    <!-- Fill in Blanks with MathLive -->
                    <div id="fillFields" class="col-12 d-none">
                        <label class="form-label fw-semibold">Accepted Answers (MathLive Formula / Text)</label>
                        <math-field id="mf_fill_answers" placeholder="e.g. 9.8 or x^2 + 1 or oxygen…"></math-field>
                        <input type="hidden" name="fill_answers" id="hidden_fill_answers">
                        <div class="form-text">For multiple accepted answers, separate with commas (e.g. <code>9.8, 9.81, 9.8 m/s^2</code>).</div>
                    </div>

                    <!-- Match the Following with MathLive -->
                    <div id="matchFields" class="col-12 d-none">
                        <label class="form-label fw-semibold">Matching Pairs (MathLive Enabled)</label>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text fw-bold">1</span>
                                    <math-field class="opt-math form-control" id="mf_ml_0" placeholder="Premise 1"></math-field>
                                    <input type="hidden" name="match_left[]" id="hidden_ml_0">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text fw-bold">A</span>
                                    <math-field class="opt-math form-control" id="mf_mr_0" placeholder="Match A"></math-field>
                                    <input type="hidden" name="match_right[]" id="hidden_mr_0">
                                </div>
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text fw-bold">2</span>
                                    <math-field class="opt-math form-control" id="mf_ml_1" placeholder="Premise 2"></math-field>
                                    <input type="hidden" name="match_left[]" id="hidden_ml_1">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text fw-bold">B</span>
                                    <math-field class="opt-math form-control" id="mf_mr_1" placeholder="Match B"></math-field>
                                    <input type="hidden" name="match_right[]" id="hidden_mr_1">
                                </div>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text fw-bold">3</span>
                                    <math-field class="opt-math form-control" id="mf_ml_2" placeholder="Premise 3"></math-field>
                                    <input type="hidden" name="match_left[]" id="hidden_ml_2">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text fw-bold">C</span>
                                    <math-field class="opt-math form-control" id="mf_mr_2" placeholder="Match C"></math-field>
                                    <input type="hidden" name="match_right[]" id="hidden_mr_2">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Open Ended with MathLive -->
                    <div id="openFields" class="col-12 d-none">
                        <label class="form-label fw-semibold">Grading Rubric / Criteria (MathLive Enabled)</label>
                        <math-field id="mf_rubric" placeholder="Describe key points or marking scheme (e.g. 2 marks for derivation of \nabla \times E = 0)..." style="min-height: 60px;"></math-field>
                        <input type="hidden" name="Rubric" id="hidden_rubric">
                        <div class="mt-2">
                            <label class="form-label small text-muted">Word Limit (Optional)</label>
                            <input type="number" name="Word_Limit" class="form-control form-control-sm" placeholder="e.g. 250" min="10" style="max-width: 150px;">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-save me-1"></i> Save to Question Bank
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import CSV Modal -->
<div class="modal fade" id="importCsvModal" tabindex="-1" aria-labelledby="importCsvModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="import_csv">

                <div class="modal-header bg-light">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="modal-title fw-bold mb-0" id="importCsvModalLabel">
                            <i class="bi bi-file-earmark-arrow-up me-2"></i>Batch Import Questions (CSV)
                        </h5>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle">All Question Types Supported</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Course <span class="text-danger">*</span></label>
                        <select name="Course_ID" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php foreach ($myCourses as $mc): ?>
                                <option value="<?= e($mc['Course_ID']) ?>">
                                    <?= e($mc['Course_Code'] ? $mc['Course_Code'] . ' - ' : '') ?><?= e($mc['Course_Name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Select CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>

                    <!-- Template Download Banner -->
                    <div class="col-12">
                        <div class="d-flex flex-wrap justify-content-between align-items-center p-3 bg-light border rounded">
                            <div>
                                <strong class="d-block text-dark small"><i class="bi bi-filetype-csv me-1 text-primary"></i>Ready-to-use CSV Templates</strong>
                                <span class="text-muted" style="font-size: 12px;">Download a sample template with pre-filled examples for all 5 question types.</span>
                            </div>
                            <div class="dropdown mt-2 mt-sm-0">
                                <button class="btn btn-sm btn-outline-danger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-download me-1"></i> Download Template
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><a class="dropdown-item small py-2" href="question-bank.php?action=download_csv_template&template_type=all"><i class="bi bi-collection me-2 text-primary"></i>All Types (Universal Template)</a></li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li><a class="dropdown-item small" href="question-bank.php?action=download_csv_template&template_type=mcq"><i class="bi bi-ui-radios me-2"></i>Single Choice (MCQ)</a></li>
                                    <li><a class="dropdown-item small" href="question-bank.php?action=download_csv_template&template_type=multiselect"><i class="bi bi-ui-checks me-2"></i>Multi-Select</a></li>
                                    <li><a class="dropdown-item small" href="question-bank.php?action=download_csv_template&template_type=fill"><i class="bi bi-pencil me-2"></i>Fill in the Blanks</a></li>
                                    <li><a class="dropdown-item small" href="question-bank.php?action=download_csv_template&template_type=match"><i class="bi bi-arrows-exchange me-2"></i>Match the Following</a></li>
                                    <li><a class="dropdown-item small" href="question-bank.php?action=download_csv_template&template_type=open"><i class="bi bi-card-text me-2"></i>Open Ended (Rubric)</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Format Guide Accordion -->
                    <div class="col-12">
                        <div class="card border">
                            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#csvFormatGuide">
                                <span class="small fw-semibold text-secondary"><i class="bi bi-info-circle me-1 text-primary"></i>Supported CSV Column Formats & Instructions</span>
                                <i class="bi bi-chevron-down small text-muted"></i>
                            </div>
                            <div class="collapse show" id="csvFormatGuide">
                                <div class="card-body p-3 small text-secondary">
                                    <p class="mb-2"><strong>Standard Column Headers:</strong><br>
                                    <code>Sl, Question_Type, Question_Text, OptionA, OptionB, OptionC, OptionD, Answer, Marks, CO, Bloom_Level, Rubric, Word_Limit</code></p>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 11.5px;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Question_Text</th>
                                                    <th>OptionA - OptionD</th>
                                                    <th>Answer Column</th>
                                                    <th>Rubric / Word_Limit</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><span class="badge bg-success-subtle text-success">mcq</span></td>
                                                    <td>Statement or MathLive LaTeX</td>
                                                    <td>Choice A, B, C, D</td>
                                                    <td>Single letter (e.g. <code>A</code>)</td>
                                                    <td><em>(Leave empty)</em></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge bg-warning-subtle text-warning-emphasis">multiselect</span></td>
                                                    <td>Statement or MathLive LaTeX</td>
                                                    <td>Choice A, B, C, D</td>
                                                    <td>Multiple letters (e.g. <code>A,C</code> or <code>A, B, D</code>)</td>
                                                    <td><em>(Leave empty)</em></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge bg-info-subtle text-info-emphasis">fill</span></td>
                                                    <td>Include <code>___</code> for blanks</td>
                                                    <td><em>(Leave empty)</em></td>
                                                    <td>Accepted answer(s) (e.g. <code>9.8, 9.81</code>)</td>
                                                    <td><em>(Leave empty)</em></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge bg-primary-subtle text-primary">match</span></td>
                                                    <td>Matching prompt</td>
                                                    <td><code>Left = Right</code> in each option</td>
                                                    <td><em>(Or matches split by <code>|</code>)</em></td>
                                                    <td><em>(Leave empty)</em></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge bg-danger-subtle text-danger">open</span></td>
                                                    <td>Subjective question prompt</td>
                                                    <td><em>(Leave empty)</em></td>
                                                    <td><em>(Optional evaluation scheme)</em></td>
                                                    <td>Rubric text and optional numeric word limit</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-2 text-muted fst-italic" style="font-size: 11px;">
                                        * Note: Math formulas in LaTeX (e.g., <code>\frac{a}{b}</code>, <code>\sqrt{x}</code>) are supported in Question_Text and Options.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-file-earmark-arrow-up me-1"></i> Upload & Import Questions
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let activeMathField = null;

// Track active math-field for symbol inserts and virtual keyboard
document.addEventListener('focusin', function(e) {
    if (e.target && e.target.tagName && e.target.tagName.toLowerCase() === 'math-field') {
        activeMathField = e.target;
    }
});

function insertSymbol(targetId, latex) {
    const mf = activeMathField || document.getElementById(targetId) || document.getElementById('modal_qText');
    if (mf) {
        if (typeof mf.executeCommand === 'function') {
            mf.focus();
            mf.executeCommand(['insert', latex]);
        } else if ('value' in mf) {
            mf.value = (mf.value || '') + latex;
        }
    }
}

function toggleMathKeyboard() {
    if (window.mathVirtualKeyboard && typeof window.mathVirtualKeyboard.toggle === 'function') {
        window.mathVirtualKeyboard.toggle();
    } else {
        const mf = activeMathField || document.getElementById('modal_qText');
        if (mf) mf.focus();
    }
}

function toggleTypeFields() {
    const type = document.getElementById('modalQType').value;
    const mcq = document.getElementById('mcqFields');
    const fill = document.getElementById('fillFields');
    const match = document.getElementById('matchFields');
    const open = document.getElementById('openFields');

    mcq.classList.add('d-none');
    fill.classList.add('d-none');
    match.classList.add('d-none');
    open.classList.add('d-none');

    const radios = document.querySelectorAll('.opt-mcq-radio');
    const checks = document.querySelectorAll('.opt-multi-check');

    if (type === 'mcq') {
        mcq.classList.remove('d-none');
        radios.forEach(r => r.classList.remove('d-none'));
        checks.forEach(c => c.classList.add('d-none'));
    } else if (type === 'multiselect') {
        mcq.classList.remove('d-none');
        radios.forEach(r => r.classList.add('d-none'));
        checks.forEach(c => c.classList.remove('d-none'));
    } else if (type === 'fill') {
        fill.classList.remove('d-none');
    } else if (type === 'match') {
        match.classList.remove('d-none');
    } else if (type === 'open') {
        open.classList.remove('d-none');
    }
}

function syncMathFields() {
    // 1. Question Statement
    const qmf = document.getElementById('modal_qText');
    if (qmf) {
        document.getElementById('qTextInput').value = qmf.value || '';
    }

    // 2. MCQ Options A-D
    ['A', 'B', 'C', 'D'].forEach(l => {
        const mf = document.getElementById('mf_opt_' + l);
        const hid = document.getElementById('hidden_opt_' + l);
        if (mf && hid) {
            hid.value = mf.value || '';
        }
    });

    // 3. Fill in Blanks
    const fmf = document.getElementById('mf_fill_answers');
    const fhid = document.getElementById('hidden_fill_answers');
    if (fmf && fhid) {
        fhid.value = fmf.value || '';
    }

    // 4. Matching Pairs
    for (let i = 0; i < 3; i++) {
        const ml = document.getElementById('mf_ml_' + i);
        const mr = document.getElementById('mf_mr_' + i);
        const hml = document.getElementById('hidden_ml_' + i);
        const hmr = document.getElementById('hidden_mr_' + i);
        if (ml && hml) hml.value = ml.value || '';
        if (mr && hmr) hmr.value = mr.value || '';
    }

    // 5. Open-ended Rubric
    const rmf = document.getElementById('mf_rubric');
    const rhid = document.getElementById('hidden_rubric');
    if (rmf && rhid) {
        rhid.value = rmf.value || '';
    }
}

function renderMathInPage() {
    if (typeof renderMathInElement !== 'undefined') {
        renderMathInElement(document.body, {
            delimiters: [
                {left: '$$', right: '$$', display: true},
                {left: '$', right: '$', display: false},
                {left: '\\(', right: '\\)', display: false},
                {left: '\\[', right: '\\]', display: true}
            ],
            throwOnError: false
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    renderMathInPage();
    setTimeout(renderMathInPage, 300);

    // Configure MathLive virtual keyboard if available
    if (window.mathVirtualKeyboard) {
        try {
            window.mathVirtualKeyboard.style = "auto";
        } catch(e) {}
    }

    const addModal = document.getElementById('addQuestionModal');
    if (addModal) {
        // Prevent Bootstrap modal from locking focus away from the virtual keyboard
        addModal.removeAttribute('tabindex');

        addModal.addEventListener('shown.bs.modal', function() {
            const firstMf = document.getElementById('modal_qText');
            if (firstMf) {
                setTimeout(() => {
                    firstMf.focus();
                    activeMathField = firstMf;
                }, 150);
            }
        });

        addModal.addEventListener('hidden.bs.modal', function() {
            if (window.mathVirtualKeyboard && typeof window.mathVirtualKeyboard.hide === 'function') {
                window.mathVirtualKeyboard.hide();
            }
        });

        const form = addModal.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                syncMathFields();
                const qText = (document.getElementById('qTextInput').value || '').trim();
                if (!qText) {
                    e.preventDefault();
                    alert('Please enter a question statement before saving.');
                    const qmf = document.getElementById('modal_qText');
                    if (qmf) qmf.focus();
                    return false;
                }
            });
        }
    }

    document.querySelectorAll('.toggle-math-kb-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            toggleMathKeyboard();
        });
    });
});
</script>

<?php include '../Common/footer.php'; ?>