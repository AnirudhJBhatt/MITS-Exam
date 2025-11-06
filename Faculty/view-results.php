<?php  
	session_start();
	if (!$_SESSION["LoginFaculty"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../Login/Login.php"</script>';
	}

	require_once "../Connection/connection.php";

    $Fac_ID=$_SESSION['LoginFaculty'];
	$query = "SELECT * FROM `faculty` WHERE `Fac_ID` = '$Fac_ID' ";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_array($run);
	$Fac_Dept=$row['Fac_Dept'];
?>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Faculty - View Results</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
	<?php include '../Common/header.php'; ?>
	<?php include '../Common/faculty-sidebar.php'; ?>

	<main>
		<div class="dashboard-header">
			<h4 class="mb-0 fw-bold">Results</h4>
		</div>
		    <div class="sub-main">
                <div class="row">
					<div class="col-md-12 container-fluid">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <label>Title</label>
                                    <select class="form-select" name="Exam_Title" required>
                                        <option value="">---None---</option>
                                        <?php
                                            $query = "SELECT * FROM exam;";
                                            $run = mysqli_query($con, $query);
                                            while($row = mysqli_fetch_array($run)) {
                                                $selected = (isset($_POST['Exam_Title']) && $_POST['Exam_Title'] == $row['Exam_ID']) ? 'selected' : '';
                                                echo '<option value="'.$row['Exam_ID'].'" '.$selected.'>'.$row['Exam_Title'].'</option>';
                                            }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label>Batch</label>
                                    <input type="text" name="Batch" class="form-control" value="<?php echo isset($_POST['Batch']) ? htmlspecialchars($_POST['Batch']) : ''; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label>Subject</label>
                                    <input type="text" name="Subject" class="form-control" value="<?php echo isset($_POST['Subject']) ? htmlspecialchars($_POST['Subject']) : ''; ?>" required>
                                </div>
                            </div>		
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <input type="submit" name="Submit" value="Search" class="btn btn-primary">
                                </div>
                            </div>			
                        </form>
					</div>
				</div>
				<div class="row">
                    <div class="col-md-12 container-fluid">
                        <?php
                        if (isset($_POST['Submit'])) {
                            $Exam_ID = intval($_POST['Exam_Title']); // Safety cast
                            $query = "SELECT * FROM result r, student s WHERE r.Stud_ID = s.Stud_ID AND r.Exam_ID = $Exam_ID;";
                            $run = mysqli_query($con, $query);

                            if (mysqli_num_rows($run) > 0) {
                                $Sl = 1;
                        ?>
                        <section class="mt-3">
                            <table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
                                <tr class="table-dark text-white">
                                    <th>SL No</th>
                                    <th>Name</th>
                                    <th>Marks</th>
                                    <th>Action</th>
                                </tr>
                                <?php
                                while ($row = mysqli_fetch_array($run)) {
                                ?>
                                <tr>
                                    <td><?php echo $Sl++; ?></td>
                                    <td><?php echo htmlspecialchars($row['Stud_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Total_Marks']); ?></td>
                                    <td width='200'>
                                        <button class="btn btn-info view-details" 
                                                data-studid="<?php echo $row['Stud_ID']; ?>" 
                                                data-examid="<?php echo $Exam_ID; ?>">
                                            View Results
                                        </button>
                                    </td>
                                </tr>
                                <?php
                                }
                                ?>
                            </table>
                            <div class="mt-3 text-center">
                                <a href="export-results.php?Exam_ID=<?php echo $Exam_ID; ?>" class="btn btn-primary">Export Results</a>
                                <button class="btn btn-success publish-btn" data-examid="<?php echo $Exam_ID; ?>">Publish Results</button>
                            </div>
                        </section>
                        <?php
                            } else {
                                echo "<p class='text-center mt-3 text-danger'>No results found for this exam.</p>";
                            }
                        }
                        ?>

                        <!-- Modal -->
                        <div class="modal fade" id="resultModal" tabindex="-1" aria-labelledby="resultModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-dark text-white">
                                        <h5 class="modal-title" id="resultModalLabel">Student Result Details</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <table class="table table-bordered text-center">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Q. No</th>
                                                    <th>Question</th>
                                                    <th>Correct Answer</th>
                                                    <th>Selected Answer</th>
                                                    <th>Marks</th>
                                                </tr>
                                            </thead>
                                            <tbody id="resultDetails">
                                                <!-- AJAX Data will appear here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

			</div>
	</main>
	<?php include '../Common/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.view-details').click(function() {
                var studID = $(this).data('studid');
                var examID = $(this).data('examid');

                $.ajax({
                    url: 'fetch-result-details.php',
                    type: 'POST',
                    data: { Stud_ID: studID, Exam_ID: examID },
                    success: function(response) {
                        $('#resultDetails').html(response);
                        var modal = new bootstrap.Modal(document.getElementById('resultModal'));
                        modal.show();
                    }
                });
            });
        });

        $(document).ready(function() {
            $(".publish-btn").click(function() {
                const button = $(this);
                const examId = button.data("examid");

                if (confirm("Are you sure you want to publish results for this exam?")) {
                    button.prop("disabled", true).text("Publishing...");

                    $.ajax({
                        url: "update-exam-status.php",
                        type: "POST",
                        data: { Exam_ID: examId },
                        success: function(response) {
                            if (response.trim() === "success") {
                                button
                                    .removeClass("btn-success")
                                    .addClass("btn-secondary")
                                    .html("Published")
                                    .prop("disabled", true);
                            } else {
                                alert("Failed to update exam status.");
                                button.prop("disabled", false).text("Publish Results");
                            }
                        },
                        error: function() {
                            alert("Server error while updating status.");
                            button.prop("disabled", false).text("Publish Results");
                        }
                    });
                }
            });
        });
    </script>

</body>
</html>



            