<?php
    require_once "../Connection/connection.php";

    $Stud_ID = $_POST["Stud_ID"];
    $Exam_ID = $_POST["Exam_ID"];

    // =============== MODE B: SAVE MARKS ====================
    if (isset($_POST["save"]) && $_POST["save"] == "1") {

        $marks = $_POST["marks"];

        // Fetch result JSON
        $query = "SELECT * FROM result WHERE Stud_ID='$Stud_ID' AND Exam_ID='$Exam_ID'";
        $run = mysqli_query($con, $query);
        $row = mysqli_fetch_assoc($run);

        $Details = json_decode($row["Details"], true);

        $Total = 0;

        // Update JSON with entered marks
        foreach ($Details as &$d) {

            $qid = $d["Q_ID"];
            $key = "marks_" . $qid;

            if (isset($marks[$key])) {
                $d["Obtained_Marks"] = intval($marks[$key]);
                $Total += intval($marks[$key]);
            }
        }

        // Store back to DB
        $newJSON = mysqli_real_escape_string($con, json_encode($Details));

        $update = "UPDATE result 
                SET Details='$newJSON', Obtained_Marks='$Total' 
                WHERE Stud_ID='$Stud_ID' AND Exam_ID='$Exam_ID'";
        mysqli_query($con, $update);

        echo "success";
        exit;
    }

    // =============== MODE A: LOAD TABLE ====================

    $query = "SELECT * FROM result WHERE Stud_ID='$Stud_ID' AND Exam_ID='$Exam_ID'";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($run);

    if ($row) {

        $Details = json_decode($row["Details"], true);
        $sl = 1;

        foreach ($Details as $d) {

            $Q_ID = $d["Q_ID"];
            $Selected = nl2br($d["Selected"]);
            $Correct = $d["Correct"];
            $Marks = $d["Obtained_Marks"];

            // Fetch question text
            $qQuery = "SELECT Question_Text FROM question_bank WHERE Q_ID='$Q_ID'";
            $qRun = mysqli_query($con, $qQuery);
            $qRow = mysqli_fetch_assoc($qRun);
            $Question = $qRow ? nl2br($qRow["Question_Text"]) : "Question not found";

            echo "
            <tr>
                <td>$sl</td>
                <td>$Question</td>
                <td>$Correct</td>
                <td>$Selected</td>
                <td>
                    <input type='number'
                        class='form-control marks-input'
                        name='marks_$Q_ID'
                        value='$Marks'
                        min='0'>
                </td>
            </tr>";

            $sl++;
        }

        echo "
        <tr>
            <td colspan='5' class='text-center'>
                <button class='btn btn-primary save-marks-btn'
                        data-studid='$Stud_ID'
                        data-examid='$Exam_ID'>
                    Save Marks
                </button>
            </td>
        </tr>
        ";

    } else {

        echo "<tr><td colspan='5'>No result found.</td></tr>";
    }

?>
