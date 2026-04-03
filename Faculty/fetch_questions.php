<?php
require_once "../Connection/connection.php";

if (isset($_POST['type_id'])) {
    
    $type_id = $_POST['type_id'];
    $Course_ID = $_POST['Course_ID'];

    // For DIAGRAM-BASED QUESTION TYPE (Type_ID = 9)
    if ($type_id == 9) {

        // Number of rows requested from main form
        $noq = isset($_POST['noq']) ? intval($_POST['noq']) : 0;

        if ($noq <= 0) {
            echo '<div class="alert alert-danger mt-3">Enter the number of questions first.</div>';
            exit;
        }

        echo '<div class="col-md-12 mt-3">';
            echo '<h5 class="text-center">Add Diagram-Based Questions</h5>';
            echo '<table class="table table-bordered border-dark mt-3 text-center">';
                echo '<tr class="table-dark text-white">
                        <th>Q.No</th>
                        <th>Diagram</th>
                        <th>Question Text</th>
                        <th>Correct Answer</th>
                        <th>Marks</th>
                        <th>CO</th>
                    </tr>';

                for ($i = 1; $i <= $noq; $i++) {

                    echo '<tr>';
                        echo '<td>'.$i.'</td>';
                        echo '<td><input type="file" name="diagram['.$i.']" class="form-control" accept="image/*" required></td>';
                        echo '<td><input type="text" name="question_text['.$i.']" class="form-control" required></td>';
                        echo '<td><input type="text" name="correct_answer['.$i.']" class="form-control" required></td>';
                        echo '<td><input type="number" name="marks['.$i.']" class="form-control" required></td>';
                        echo '<td><input type="number" name="co['.$i.']" class="form-control" required></td>';
                    echo '</tr>';
                }
            echo '</table>';
            // Hidden flag to tell main form to use DIAGRAM MODE
            echo '<input type="hidden" name="is_diagram_mode" value="1">';
        echo '</div>';

        exit;
    }

    // ---------------------------
    // NORMAL QUESTION TYPES (not diagram)
    // ---------------------------

    $query = "SELECT * FROM question_bank WHERE Type_ID = '$type_id' and Subject_Code = '$Course_ID' ORDER BY Q_ID DESC";
    $run = mysqli_query($con, $query);

    if (mysqli_num_rows($run) > 0) {

        echo '<div class="col-md-12 container-fluid">';
        echo '<h5 class="mt-3 text-center">Questions Bank</h5>';
        echo '<table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">';

        echo '<tr class="table-dark text-white">
                <th>SL No</th>
                <th>Question</th>
                <th>Options</th>
                <th>Correct Answer</th>
                <th>Marks</th>
                <th>Select</th>
              </tr>';

        $sl = 1;
        while ($row = mysqli_fetch_array($run)) {

            echo '<tr>';
                echo '<td>'.$sl++.'</td>';
                echo '<td>'.htmlspecialchars($row['Question_Text']).'</td>';
                echo '<td>'.htmlspecialchars($row['Options']).'</td>';
                echo '<td>'.htmlspecialchars($row['Correct_Answer']).'</td>';
                echo '<td>'.$row['Marks'].'</td>';

                echo '<td>
                        <input type="checkbox" name="selected_questions[]" value="'.$row['Q_ID'].'">
                      </td>';
            echo '</tr>';
        }

        echo '</table>';
        echo '</div>';

    } else {
        echo '<div class="col-md-12 container-fluid">
                <div class="alert alert-warning mt-3">No questions found for this type.</div>
              </div>';
    }
}
?>
