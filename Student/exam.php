<?php  
	session_start();
	if (!isset($_SESSION['LoginStudent'])) {
		echo "<script>alert('You are not authorized to access this page'); window.location.href='../index.php';</script>";
		exit;
	}
	require_once "../Connection/connection.php";
	$Stud_ID = $_SESSION['LoginStudent'];
	$query = "SELECT * FROM `student` WHERE `Stud_ID` = '$Stud_ID' ";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_array($run);
	$Stud_Dept=$row['Stud_Dept'];
	$Stud_Year=$row['Stud_Year'];

	$attempted_exams = array();
	$examquery = "SELECT * FROM `result` WHERE `Stud_ID` = '$Stud_ID' ";
	$run = mysqli_query($con, $examquery);
	while ($row = mysqli_fetch_assoc($run)) {
		$attempted_exams[] = $row['Exam_ID'];
	}

?>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Student - Exam</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		.exam-card {
			border-radius: 15px;
			box-shadow: 0 4px 10px rgba(0,0,0,0.1);
			transition: all 0.2s ease;
			background-color: #fff;
			margin-bottom: 1rem;
			padding: 1rem 1.5rem;
		}
		.exam-card:hover {
			transform: translateY(-3px);
			box-shadow: 0 6px 15px rgba(0,0,0,0.15);
		}
		.exam-row {
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
		}
		.exam-title {
			font-weight: bold;
			font-size: 1.1rem;
			color: #0d6efd;
			min-width: 180px;
		}
		.exam-item {
			font-size: 0.95rem;
			color: #333;
			margin: 0 10px;
		}
		.exam-action {
			min-width: 120px;
			text-align: end;
		}
	</style>
</head>

<body>
	<?php include '../Common/header.php'; ?>
	<?php include '../Common/student-sidebar.php'; ?>

	<main>
		<div class="dashboard-header">
			<h4 class="mb-0 fw-bold">Exam</h4>
		</div>
		<?php
			$query = "SELECT * FROM `exam` WHERE `Dept`='$Stud_Dept' and `Batch`='$Stud_Year'";
			$run = mysqli_query($con, $query);
			while($row = mysqli_fetch_array($run)) {
		?>
			<div class="exam-card">
				<div class="exam-row">
					<div class="exam-title"><strong>Exam Name:</strong><?php echo $row['Exam_Title']; ?></div>
					<div class="exam-item"><strong>Type:</strong> <?php echo $row['Exam_Type']; ?></div>
					<div class="exam-item"><strong>Subject:</strong> <?php echo $row['Subject']; ?></div>
					<div class="exam-item"><strong>Duration:</strong> <?php echo $row['Duration']; ?> mins</div>
					<div class="exam-item"><strong>Marks:</strong> <?php echo $row['Total_Marks']; ?></div>
					<div class="exam-action">
						<?php 
							if (!in_array($row['Exam_ID'], $attempted_exams)) {
								echo '<a href="attempt-exam.php?Exam_ID='.$row['Exam_ID'].'" class="btn btn-primary">Attempt Exam</a>';
							} else if($row['Result_Status'] == 1) {
								echo '<button class="btn btn-success view-details" data-studid="'.$Stud_ID.'" data-examid="'.$row['Exam_ID'].'">View Result</button>';
							} else {
								echo '<button class="btn btn-success" disabled>Completed</button>';
							}
						?>
					</div>
				</div>
			</div>
		<?php
			}
		?>
				<!-- <div class="row">
					<div class="col-md-12 container-fluid">
						<section class="mt-3">
							<table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
								<tr class="table-dark text-white">
									<th>SL No</th>
									<th>Exam</th>
									<th>Type</th>
									<th>Subject</th>
									<th>Duration</th>
									<th>Action</th>
									<th>Marks</th>
								</tr>
								<?php
									$query="SELECT * FROM `exam` WHERE `Dept`='$Stud_Dept'";
									$run=mysqli_query($con,$query);
									while($row=mysqli_fetch_array($run)) {
										$D_ID=$row['Exam_ID'];
								?>
								<tr>
									<td><?php echo $row['Exam_ID']; ?></td>
									<td><?php echo $row['Exam_Title']; ?></td>
									<td><?php echo $row['Exam_Type']; ?></td>
									<td><?php echo $row['Subject']; ?></td>
									<td><?php echo $row['Duration']; ?></td>
									<td width='200'>
										<button class="btn btn-success" data-id="<?php echo $row['Exam_ID']; ?>">Attempt</button>
									</td>
								</tr>
								<?php
									}
								?>
							</table>				
						</section>
					</div>
				</div> -->

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
	</main>
	<?php include '../Common/footer.php'; ?>
	 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.view-details').click(function() {
                var studID = $(this).data('studid');
                var examID = $(this).data('examid');

                $.ajax({
                    url: '../Faculty/fetch-result-details.php',
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
    </script>

</body>

</html>



            