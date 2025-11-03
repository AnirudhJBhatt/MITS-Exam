<?php
require_once "../Connection/connection.php";

if(isset($_POST['type_id'])){
    $type_id = $_POST['type_id'];

    $query = "SELECT * FROM question_bank WHERE Type_ID = '$type_id'";
    $run = mysqli_query($con, $query);

    if(mysqli_num_rows($run) > 0){
        echo '<div class="col-md-12 container-fluid">';
            echo '<h5 class="mt-3 text-center">Questions Bank</h5>';
            echo '<table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">';
                echo '<tr class="table-dark text-white">';
                    echo '<th>SL No</th><th>Question</th><th>Options</th><th>Correct Answer</th><th>Marks</th><th>Select</th>';
                echo '</tr>';
                $sl = 1;
                while($row = mysqli_fetch_array($run)){
                    echo '<tr>';
                        echo '<td>'.$sl++.'</td>';
                        echo '<td>'.htmlspecialchars($row['Question_Text']).'</td>';
                        echo '<td>'.htmlspecialchars($row['Options']).'</td>';
                        echo '<td>'.htmlspecialchars($row['Correct_Answer']).'</td>';
                        echo '<td>'.$row['Marks'].'</td>';
                        echo '<td><input type="checkbox" name="selected_questions[]" value="'.$row['Q_ID'].'"></td>';
                    echo '</tr>';
                }
            echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="col-md-12 container-fluid">';
            echo '<div class="alert alert-warning mt-3">No questions found for this type.</div>';
        echo '</div>';
    }
}
?>
