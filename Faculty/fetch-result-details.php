<?php
    require_once "../Connection/connection.php";

    $Stud_ID = $_POST['Stud_ID'];
    $Exam_ID = $_POST['Exam_ID'];

    $query = "SELECT Details FROM result WHERE Stud_ID='$Stud_ID' AND Exam_ID='$Exam_ID'";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($run);
    if ($row) {
        $Details = json_decode($row['Details'], true);
        $sl = 1;

        foreach ($Details as $d) {
            $Q_ID = $d['Q_ID'];
            $Selected = $d['Selected'];
            $Correct = $d['Correct'];
            $Marks = $d['Marks'];

            // Fetch question text
            $qQuery = "SELECT Question_Text FROM Question_Bank WHERE Q_ID='$Q_ID'";
            $qRun = mysqli_query($con, $qQuery);
            $qRow = mysqli_fetch_assoc($qRun);
            $Question = $qRow ? $qRow['Question_Text'] : "Question not found";

            // Highlight incorrect answers
            $rowClass = ($Selected != $Correct) ? 'table-danger' : 'table-success';

            echo "<tr class='$rowClass'>
                    <td>$sl</td>
                    <td>$Question</td>
                    <td>$Correct</td>
                    <td>$Selected</td>
                    <td>$Marks</td>
                </tr>";
            $sl++;
        }
    } else {
        echo "<tr><td colspan='5'>No result found.</td></tr>";
    }
?>
