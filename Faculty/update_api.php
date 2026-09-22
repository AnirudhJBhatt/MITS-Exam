<?php
$content = file_get_contents('api.php');

$switchInsert = <<<'EOT'
        case 'get_bank_question':
            handleGetBankQuestion();
            break;
        case 'update_bank_question':
            handleUpdateBankQuestion();
            break;
EOT;
$content = str_replace("case 'save_bank_questions':", $switchInsert . "\n        case 'save_bank_questions':", $content);

$funcInsert = <<<'EOT'
    function handleGetBankQuestion(): void
    {
        global $con, $FAC_ID;
        $bankId = (int)($_GET['bank_id'] ?? 0);
        if (!$bankId) jsonError('bank_id is required');

        $stmt = $con->prepare("SELECT * FROM new_question_bank WHERE Bank_ID = ? AND Fac_ID = ?");
        $stmt->bind_param("is", $bankId, $FAC_ID);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) jsonError('Question not found or unauthorized');

        jsonSuccess(['question' => $res->fetch_assoc()]);
    }

    function handleUpdateBankQuestion(): void
    {
        global $con, $FAC_ID;
        $body = getJsonBody();
        $bankId = (int)($body['bank_id'] ?? 0);
        if (!$bankId) jsonError('bank_id is required');

        $type       = sanitizeType($body['type'] ?? '');
        $text       = trim($body['question_text'] ?? '');
        $marks      = round((float)($body['marks'] ?? 1), 1);
        $co         = (int)($body['co'] ?? 1);

        if (!$type || !$text) jsonError('Invalid question data');

        $optionsJson  = null;
        $correctOpt   = null;
        $rubric       = null;
        $wordLimit    = null;
        $answersJson  = null;
        $pairsJson    = null;
        $imagePath    = null;

        switch ($type) {
            case 'mcq':
                $opts = $body['options'] ?? [];
                if (is_array($opts) && count($opts)) {
                    $cleanOpts = array_map(fn($o) => [
                        'letter' => strtoupper(substr($o['letter'] ?? '', 0, 1)),
                        'text'   => trim($o['text'] ?? ''),
                    ], $opts);
                    $optionsJson = json_encode($cleanOpts, JSON_UNESCAPED_UNICODE);
                }
                $raw = strtoupper(trim($body['correct_opt'] ?? ''));
                if (strlen($raw) && ctype_alpha($raw[0])) $correctOpt = $raw[0];
                break;
            case 'match':
                $pairs = $body['pairs'] ?? [];
                if (is_array($pairs) && count($pairs)) {
                    $cleanPairs = array_map(fn($p) => [
                        'a' => trim($p['a'] ?? ''),
                        'b' => trim($p['b'] ?? ''),
                    ], $pairs);
                    $pairsJson = json_encode($cleanPairs, JSON_UNESCAPED_UNICODE);
                }
                break;
            case 'open':
                $rubric    = trim($body['rubric'] ?? '');
                $wordLimit = (int)($body['word_limit'] ?? 0) ?: null;
                $imagePath = trim($body['image_path'] ?? '');
                break;
            case 'fill':
                $ans = $body['answers'] ?? [];
                if (is_array($ans) && count($ans)) {
                    $cleanAns = array_map(fn($a) => trim($a), $ans);
                    $answersJson = json_encode($cleanAns, JSON_UNESCAPED_UNICODE);
                }
                $imagePath = trim($body['image_path'] ?? '');
                break;
        }

        $stmt = $con->prepare(
            "UPDATE new_question_bank 
             SET Question_Type=?, Question_Text=?, Marks=?, Options_JSON=?, Correct_Opt=?, 
                 Rubric=?, Word_Limit=?, Answers_JSON=?, Pairs_JSON=?, Image_Path=?, CO=? 
             WHERE Bank_ID=? AND Fac_ID=?"
        );
        $stmt->bind_param("ssdsssisssiis", 
            $type, $text, $marks, $optionsJson, $correctOpt, 
            $rubric, $wordLimit, $answersJson, $pairsJson, $imagePath, $co, 
            $bankId, $FAC_ID
        );
        
        if ($stmt->execute()) {
            jsonSuccess(['message' => 'Question updated successfully']);
        } else {
            jsonError('Failed to update question');
        }
    }

EOT;
$content = str_replace("function handleSaveBankQuestions(): void", $funcInsert . "\n    function handleSaveBankQuestions(): void", $content);

file_put_contents('api.php', $content);
echo "api.php updated successfully";
?>
