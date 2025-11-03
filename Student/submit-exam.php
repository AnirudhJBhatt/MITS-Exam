<?php
    session_start();
    require_once "../Connection/connection.php";

    if (!isset($_SESSION["LoginStudent"])) {
        echo "<script>alert('Unauthorized access!'); window.location='../Login/Login.php';</script>";
        exit;
    }

    $Stud_ID = $_SESSION["LoginStudent"];
    $Exam_ID = $_POST["Exam_ID"];

    // Ensure answers exist
    if (!isset($_POST['answers'])) {
        echo "<script>alert('No answers submitted!'); window.location='dashboard.php';</script>";
        exit;
    }

    $answers = $_POST['answers']; // associative array: [Q_ID => chosen option]
    
    print_r($answers); // For debugging, remove in production

    // Fetch all questions for this exam
    $exam_query = "SELECT Q_IDs FROM exam WHERE Exam_ID = '$Exam_ID'";
    $exam_run = mysqli_query($con, $exam_query);
    $exam_data = mysqli_fetch_assoc($exam_run);
    $qids = json_decode($exam_data['Q_IDs'], true);
    $qid_list = implode(",", $qids);

    // Fetch correct answers and marks
    $question_query = "SELECT Q_ID, Correct_Answer, Marks FROM question_bank WHERE Q_ID IN ($qid_list)";
    $question_run = mysqli_query($con, $question_query);

    $total_marks = 0;
    $obtained_marks = 0;

    // Create result details for storage (optional)
    $result_details = [];

    while ($row = mysqli_fetch_assoc($question_run)) {
        $qid = $row['Q_ID'];
        $correct_answers = json_decode($row['Correct_Answer'], true);
        $marks = $row['Marks'];

        $total_marks += $marks;

        $selected_answer = isset($answers[$qid]) ? trim($answers[$qid]) : "";

        $is_correct = false;

        foreach ($correct_answers as $ans) {
            if (strcasecmp(trim($selected_answer), trim($ans)) === 0) {
                $is_correct = true;
                break;
            }
        }

        if ($is_correct) {
            $obtained_marks += $marks;
        }

        $result_details[] = [
            'Q_ID' => $qid,
            'Selected' => $selected_answer,
            'Correct' => implode(", ", $correct_answers),
            'Is_Correct' => $is_correct ? 1 : 0,
            'Marks' => $marks
        ];
    }

    // Convert details to JSON for optional storage
    $result_json = mysqli_real_escape_string($con, json_encode($result_details));

    // Insert or update result
    $check_query = "SELECT * FROM result WHERE Stud_ID = '$Stud_ID' AND Exam_ID = '$Exam_ID'";
    $check_run = mysqli_query($con, $check_query);

    if (mysqli_num_rows($check_run) > 0) {
        // Update existing result
        $update_query = "UPDATE result
                        SET Obtained_Marks = '$obtained_marks', Total_Marks = '$total_marks', 
                            Details = '$result_json', Submitted_At = NOW()
                        WHERE Stud_ID = '$Stud_ID' AND Exam_ID = '$Exam_ID'";
        mysqli_query($con, $update_query);
    } else {
        // Insert new result
        $insert_query = "INSERT INTO result (Stud_ID, Exam_ID, Obtained_Marks, Total_Marks, Details, Submitted_At) 
                        VALUES ('$Stud_ID', '$Exam_ID', '$obtained_marks', '$total_marks', '$result_json', NOW())";
        mysqli_query($con, $insert_query);
    }

    echo "<script> alert('Exam submitted successfully!'); window.location='exam.php'; </script>";
?>
