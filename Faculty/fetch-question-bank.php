<?php
require_once "../Connection/connection.php";

if(isset($_POST['course_id']) && isset($_POST['type_id'])) {

    $course_id = $_POST['course_id'];
    $type_id = (int)$_POST['type_id'];

    $stmt = $con->prepare("SELECT * FROM question_bank 
                           WHERE Subject_Code=? AND Type_ID=? 
                           ORDER BY Question_ID DESC");
    $stmt->bind_param("si", $course_id, $type_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {

        echo '<h5 class="mb-3 fw-bold text-center">Existing Questions</h5>';
        echo '<table class="table table-bordered table-striped">';
        echo '<thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Question</th>
                    <th>Marks</th>
                    <th>CO</th>
                </tr>
              </thead><tbody>';

        $i=1;
        while($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>'.$i++.'</td>';
            echo '<td>'.htmlspecialchars($row['Question_Text']).'</td>';
            echo '<td>'.$row['Marks'].'</td>';
            echo '<td>'.$row['CO'].'</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

    } else {
        echo '<div class="alert alert-warning text-center">
                No questions found for selected Subject & Type.
              </div>';
    }
}
?>