<?php
    // submit-exam.php
    session_start();
    require_once "../Connection/connection.php";

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

    header('Content-Type: text/html; charset=utf-8');

    // --- Session & basic checks -------------------------------------------------
    if (!isset($_SESSION["LoginStudent"]) || empty($_SESSION["LoginStudent"])) {
        http_response_code(403);
        exit("<script>alert('Unauthorized access!'); window.location='../Login/Login.php';</script>");
    }

    $Stud_ID = $_SESSION["LoginStudent"]; // force integer

    // Ensure POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit("Method Not Allowed");
    }

    // Minimal required fields
    if (!isset($_POST['Exam_ID']) || !isset($_POST['answers'])) {
        exit("<script>alert('Missing required data.'); window.location='dashboard.php';</script>");
    }

    $Exam_ID = (int) $_POST['Exam_ID'];

    // Basic helper normalizer (used for answer comparison)
    function normalize($str) {
        if (!is_string($str)) return '';
        $str = strtolower($str); // to lowercase
        $str = preg_replace('/\s+/', '', $str);            // remove spaces
        $str = preg_replace('/[^a-z0-9]/u', '', $str);    // remove non-alphanumeric (basic)
        return $str;
    }

    // Safe decode helper
    function safe_json_decode($json) {
        if ($json === null || $json === '') return null;
        $r = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) return null;
        return $r;
    }

    // Answers posted by client (should be associative: Q_ID => answer or JSON)
    $raw_answers = $_POST['answers'];
    // If client sent JSON string, decode:
    if (is_string($raw_answers)) {
        $posted_answers = safe_json_decode($raw_answers);
        if ($posted_answers === null) {
            // Fallback: try to parse as form array (rare)
            $posted_answers = $_POST['answers'];
        }
    } else {
        $posted_answers = $raw_answers;
    }
    if (!is_array($posted_answers)) $posted_answers = []; // ensure array

    // --- Verify the exam exists and fetch authoritative Q_ID list ----------------
    // Important: do NOT trust any client-sent list of questions/marks/etc.
    // Also: you should ensure the exam is active / the student is allowed to take it.
    $stmt = $con->prepare("SELECT * FROM exam WHERE Exam_ID = ?");
    $stmt->bind_param('i', $Exam_ID);
    $stmt->execute();
    $exam_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$exam_row) {
        exit("<script>alert('Invalid exam.'); window.location='dashboard.php';</script>");
    }

    // decode qids safely
    $qids = safe_json_decode($exam_row['Q_IDs']);
    if (!is_array($qids) || count($qids) === 0) {
        exit("<script>alert('Exam has no questions.'); window.location='dashboard.php';</script>");
    }

    // Ensure qids are integers and unique
    $qids = array_values(array_unique(array_map('intval', $qids)));
    if (count($qids) === 0) {
        exit("<script>alert('No valid questions for this exam.'); window.location='dashboard.php';</script>");
    }

    // Limit the number of qids to a reasonable amount to avoid abuse:
    if (count($qids) > 2000) {
        exit("<script>alert('Too many questions.'); window.location='dashboard.php';</script>");
    }

    // --- Fetch questions from DB (authoritative) --------------------------------
    // We'll fetch only questions that belong to this exam (use IN with placeholders)
    $placeholders = implode(',', array_fill(0, count($qids), '?'));
    $types = str_repeat('i', count($qids));
    $sql = "SELECT Q_ID, Type_ID, Marks, Correct_Answer, CO FROM question_bank WHERE Q_ID IN ($placeholders)";
    // prepare dynamic bind
    $stmt = $con->prepare($sql);
    $bind_names = [];
    $bind_names[] = $types;
    for ($i=0;$i<count($qids);$i++){
        $bind_names[] = &$qids[$i];
    }
    // Call_user_func_array for bind_param
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
    $stmt->execute();
    $result = $stmt->get_result();

    $questions = [];
    while ($r = $result->fetch_assoc()) {
        $questions[(int)$r['Q_ID']] = $r;
    }
    $stmt->close();

    // Ensure we fetched every Q_ID (fail if mismatch - prevents forged Q_IDs)
    $missing = array_diff($qids, array_keys($questions));
    if (count($missing) > 0) {
        // A defensive choice: reject the submission if DB mismatch occurs.
        exit("<script>alert('Question set mismatch. Submission rejected.'); window.location='dashboard.php';</script>");
    }

    // --- Validate posted answers only contains those Q_IDs (drop others) ---------
    $filtered_answers = [];
    foreach ($posted_answers as $k => $v) {
        $qidKey = (int) $k;
        if (isset($questions[$qidKey])) {
            // accept
            $filtered_answers[$qidKey] = $v;
        }
    }
    // Now $filtered_answers contains only valid Q_ID => answer

    // --- Start transaction to calculate & store result atomically ----------------
    $con->begin_transaction();

    try {
        $total_marks = 0;
        $obtained_marks = 0;
        $result_details = [];
        $co_marks = []; // CO => obtained marks

        foreach ($qids as $qid) {
            $row = $questions[$qid];
            $type = (int)$row['Type_ID'];
            $marks = (float)$row['Marks'];
            $total_marks += $marks;

            $correct_raw = $row['Correct_Answer'];
            $correct_answers = safe_json_decode($correct_raw);

            $selected = isset($filtered_answers[$qid]) ? $filtered_answers[$qid] : null;

            $is_correct = 0;
            $obtained = 0;

            // --- TYPE 2: MATCH THE FOLLOWING (partial marking allowed) ------------
            if ($type === 2) {
                // correct_answers expected to be associative: left => right
                if (!is_array($correct_answers)) $correct_answers = [];

                // student answer can be sent as associative array left => right (or list)
                if (is_string($selected)) {
                    $candidate = safe_json_decode($selected);
                    if ($candidate === null) {
                        $candidate = [];
                    }
                } else {
                    $candidate = is_array($selected) ? $selected : [];
                }

                // Normalize keys: ensure we compare using the 'left' key presence
                $total_pairs = count($correct_answers);
                $correct_pairs = 0;

                if ($total_pairs > 0) {
                    foreach ($correct_answers as $left => $right) {
                        // Student might use numeric indexes; try to match by left key
                        $stu_val = '';
                        if (isset($candidate[$left])) {
                            $stu_val = $candidate[$left];
                        } else {
                            $vals = array_values($candidate);
                            $idx = array_search($left, array_keys($correct_answers), true);
                            if ($idx !== false && isset($vals[$idx])) $stu_val = $vals[$idx];
                        }

                        if (normalize((string)$stu_val) === normalize((string)$right)) {
                            $correct_pairs++;
                        }
                    }

                    // Partial marking: proportion of marks for correct pairs
                    $obtained = ($marks * $correct_pairs) / $total_pairs;
                    // Consider rounding to 2 decimals
                    $obtained = round($obtained, 2);
                    if ($obtained >= $marks - 1e-9) $is_correct = 1; // fully correct
                    else if ($obtained > 0) $is_correct = 2; // partially correct (2)
                    else $is_correct = 0;
                    $obtained_marks += $obtained;
                    
                    if ($obtained > 0) {
                        $co = (string) $row['CO'];

                        if (!isset($co_marks[$co])) {
                            $co_marks[$co] = 0;
                        }
                        $co_marks[$co] += $obtained;
                    }

                } else {
                    // no correct pairs configured
                    $obtained = 0;
                    $is_correct = 0;
                }

                // encode student selected safely
                $selected_enc = is_array($candidate) ? json_encode($candidate, JSON_UNESCAPED_UNICODE) : (string)$candidate;
            }

            // --- TYPE 7: OPEN-ENDED (manual grading) --------------------------------
            else if ($type === 7) {
                // Mark as pending manual grading (null / -1)
                $is_correct = null;
                $obtained = 0;
                // keep selected as sent
                $selected_enc = is_array($selected) ? json_encode($selected, JSON_UNESCAPED_UNICODE) : (string)$selected;
            }

            // --- Other types: MCQ, statement-filling etc (exact match) --------------
            else {
                // If correct_answers is array (multiple acceptable answers), compare normalized strings
                $selected_str = '';
                if (is_array($selected)) {
                    // If client sends array (e.g., for multi-select), join safely
                    $selected_str = implode('|', array_map('strval', $selected));
                } else {
                    $selected_str = is_null($selected) ? '' : (string)$selected;
                }
                $sel_norm = normalize($selected_str);

                $matched = false;
                if (is_array($correct_answers) && count($correct_answers) > 0) {
                    foreach ($correct_answers as $ans) {
                        if (normalize((string)$ans) === $sel_norm) {
                            $matched = true;
                            break;
                        }
                    }
                } else {
                    // if correct_answers is scalar string
                    if (!is_array($correct_answers) && $correct_answers !== null) {
                        if (normalize((string)$correct_answers) === $sel_norm) $matched = true;
                    }
                }

                if ($matched) {
                    $is_correct = 1;
                    $obtained = $marks;
                    $obtained_marks += $obtained;
                    
                    if ($obtained > 0) {
                    $co = (string) $row['CO'];

                    if (!isset($co_marks[$co])) {
                        $co_marks[$co] = 0;
                    }
                    $co_marks[$co] += $obtained;
                    
                }
      
                }

                $selected_enc = is_array($selected) ? json_encode($selected, JSON_UNESCAPED_UNICODE) : (string)$selected;
            }

            // store detail entry
            $result_details[] = [
                'Q_ID' => $qid,
                'Type' => $type,
                'Selected' => $selected_enc,
                'Correct' => is_array($correct_answers) ? json_encode($correct_answers, JSON_UNESCAPED_UNICODE) : $correct_answers,
                'Is_Correct' => $is_correct,
                'Obtained_Marks' => $obtained
            ];
            $co_details = [];

            foreach ($co_marks as $co => $marks) {
                $co_details[] = [
                    'CO' => $co,
                    'CO_Total_Marks' => round($marks, 2)
                ];
            }
        }

        // Round totals to sensible precision
        $total_marks = round($total_marks, 2);
        $obtained_marks = round($obtained_marks, 2);

        // Store result: insert or update. Use prepared statements.
        $details_json = json_encode($result_details, JSON_UNESCAPED_UNICODE);
        $co_details_json = json_encode($co_details, JSON_UNESCAPED_UNICODE);

        $details_esc = $details_json; // we'll bind it, no manual escaping needed

        // Check existing result row (for this student+exam)
        $chk = $con->prepare("SELECT Result_ID FROM result WHERE Stud_ID = ? AND Exam_ID = ?");
        $chk->bind_param('si', $Stud_ID, $Exam_ID);
        $chk->execute();
        $chk_res = $chk->get_result();
        $exists = $chk_res->fetch_assoc();
        $chk->close();

        if ($exists) {
            // UPDATE
            $upd = $con->prepare("UPDATE result SET Obtained_Marks = ?, Total_Marks = ?, Details = ?, Submitted_At = NOW(), CO_Details = ? WHERE Stud_ID = ? AND Exam_ID = ?");
            $upd->bind_param('ddsssi', $obtained_marks, $total_marks, $details_esc, $co_details_json, $Stud_ID, $Exam_ID);
            $upd->execute();
            $upd->close();
            echo "Hi";
        } else {
            // INSERT
            $ins = $con->prepare("INSERT INTO result (Stud_ID, Exam_ID, Obtained_Marks, Total_Marks, Details, Submitted_At, CO_Details) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
            $ins->bind_param('siddss', $Stud_ID, $Exam_ID, $obtained_marks, $total_marks, $details_esc, $co_details_json);// note: 'iidds' — adjust types if your DB columns are different
            $ins->execute();
            $ins->close();
            echo "Hello";
        }

        // Commit transaction
        $con->commit();

        echo "<script>alert('Exam submitted successfully!'); window.location='exam.php';</script>";
        exit();

    } catch (Exception $e) {
        // Rollback and return error
        $con->rollback();
        // Log server side: you must log $e->getMessage() to server logs (not shown to user)
        error_log("Exam submission error for Stud_ID {$Stud_ID}, Exam_ID {$Exam_ID}: " . $e->getMessage());
        exit("<script>alert('An error occurred while submitting. Please contact administrator.'); window.location='dashboard.php';</script>");
    }
?>
