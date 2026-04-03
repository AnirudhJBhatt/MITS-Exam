<?php  
	session_start();
	if (!($_SESSION["LoginAdmin"] || $_SESSION["LoginFaculty"])){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../Login/Login.php"</script>';
	}
	require_once "../Connection/connection.php";

    /* -------------------- UPDATE REQUEST -------------------- */
    if (isset($_POST['action']) && $_POST['action'] === 'update') {

        $Exam_ID    = mysqli_real_escape_string($con, $_POST['Exam_ID']);
        $Title      = mysqli_real_escape_string($con, $_POST['Exam_Title']);
        $Duration   = mysqli_real_escape_string($con, $_POST['Duration']);
        $Start_Time = mysqli_real_escape_string($con, $_POST['Start_Time']);
        $End_Time   = mysqli_real_escape_string($con, $_POST['End_Time']);

        $update = "UPDATE exam SET Exam_Title = '$Title', Duration = '$Duration', Start_Time = '$Start_Time', End_Time = '$End_Time' WHERE Exam_ID = '$Exam_ID'";

        if (mysqli_query($con, $update)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
        exit;
    }

    /* -------------------- LOAD FORM -------------------- */
    if (!isset($_POST['Exam_ID'])) {
        exit('Invalid request');
    }

    $Exam_ID = mysqli_real_escape_string($con, $_POST['Exam_ID']);
    $res = mysqli_query($con, "SELECT * FROM exam WHERE Exam_ID = '$Exam_ID'");
    $exam = mysqli_fetch_assoc($res);
?>

<form id="updateExamForm">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="Exam_ID" value="<?php echo $exam['Exam_ID']; ?>">

    <div class="row mb-3">
        <div class="col-md-6">
            <label>Exam Title</label>
            <input type="text" name="Exam_Title" class="form-control" value="<?php echo htmlspecialchars($exam['Exam_Title']); ?>" required>
        </div>

        <div class="col-md-6">
            <label>Duration (minutes)</label>
            <input type="number" name="Duration" class="form-control" value="<?php echo $exam['Duration']; ?>" required>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label>Start Time</label>
            <input type="datetime-local" name="Start_Time" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($exam['Start_Time'])); ?>" required>
        </div>

        <div class="col-md-6">
            <label>End Time</label>
            <input type="datetime-local" name="End_Time" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($exam['End_Time'])); ?>" required>
        </div>
    </div>

    <div class="modal-footer">
        <button type="submit" class="btn btn-success">Update Exam</button>
    </div>
</form>

<script>
    $('#updateExamForm').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: '../Admin/edit-exam.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    alert('Exam updated successfully');
                    $('#editExamModal').modal('hide');
                    location.reload();
                } else {
                    alert('Update failed');
                }
            }
        });
    });
</script>
