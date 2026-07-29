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
	$Stud_Branch=$row['Stud_Branch'];

	$attempted_exams = array();
	$examquery = "SELECT * FROM `result` WHERE `Stud_ID` = '$Stud_ID' ";
	$run = mysqli_query($con, $examquery);
	while ($row = mysqli_fetch_assoc($run)) {
		$attempted_exams[] = $row['ExamFi_ID'];
	}

?>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Student - Exam</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>
	<?php include '../Common/header.php'; ?>
	<?php include '../Common/student-sidebar.php'; ?>

	<main>
		<div class="dashboard-header">
			<h4 class="mb-0 fw-bold">Exam</h4>
		</div>
		<?php
			$query ="SELECT e.Exam_ID AS exam_id_exam, e.Total_Marks AS E_Marks, r.Total_Marks AS R_Marks, 
					e.Result_Status, e.Exam_Name, e.Course_ID, c.Course_Name, e.Duration, e.* ,
					CASE 
						WHEN CONVERT_TZ(NOW(), '+00:00', '+05:30') < e.Start_Time THEN 'Upcoming'
						WHEN CONVERT_TZ(NOW(), '+00:00', '+05:30') BETWEEN e.Start_Time AND e.End_Time THEN 'Ongoing'
						ELSE 'Completed'
					END AS Current_Status
					FROM exams e 
					INNER JOIN student_course_mapping scm ON scm.Course_ID = e.Course_ID 
					INNER JOIN courses c ON c.Course_ID = e.Course_ID
					LEFT JOIN result r ON r.Exam_ID = e.Exam_ID AND r.Stud_ID = '$Stud_ID'
					WHERE scm.Stud_ID = '$Stud_ID' AND e.Prog_ID = '$Stud_Branch' ORDER BY e.Exam_ID DESC";
					
			$run = mysqli_query($con, $query);
			if(mysqli_num_rows($run) == 0) {
				echo '<div class="alert alert-warning mt-4 text-center" role="alert">No exams available at the moment.</div>';
			}
			while($row = mysqli_fetch_array($run)) {
		?>
		<div class="card mb-3">
			<div class="card-body">
				<div class="row align-items-center">
					<div class="col-md-3 col-sm-12">
						<h5 class="text-primary mb-1 fw-bold"><?php echo $row['Exam_Name']; ?></h5>
						<span class="text-secondary small"><strong>Subject:</strong> <?php echo $row['Course_Name']; ?></span>
					</div>
					<div class="col-md-2 col-sm-6 mt-2 mt-md-0">
						<span class="text-muted d-block small">Max Marks</span>
						<span class="fw-semibold text-dark"><?php echo $row['E_Marks']; ?> Marks</span>
					</div>
					<div class="col-md-2 col-sm-6 mt-2 mt-md-0">
						<span class="text-muted d-block small">Start Time</span>
						<span class="small text-dark"><?php echo $row['Start_Time']; ?></span>
					</div>
					<div class="col-md-2 col-sm-6 mt-2 mt-md-0">
						<span class="text-muted d-block small">End Time</span>
						<span class="small text-dark"><?php echo $row['End_Time']; ?></span>
					</div>
					<?php if($row['Result_Status'] == 1) { ?>
					<div class="col-md-1 col-sm-6 mt-2 mt-md-0">
						<span class="text-muted d-block small">Marks</span>
						<span class="fw-bold text-success"><?php echo $row['R_Marks']; ?></span>
					</div>
					<?php } ?>
					<div class="<?php echo ($row['Result_Status'] == 1) ? 'col-md-2' : 'col-md-3'; ?> col-sm-12 mt-3 mt-md-0 text-md-end">
						<?php 
							$exam_id = $row['exam_id_exam'];
							$current_status = $row['Current_Status'];
							if (in_array($exam_id, $attempted_exams)) {
								if ($row['Result_Status'] == 1) {
									echo '<button class="btn btn-primary btn-sm view-details" data-studid="' . $Stud_ID . '" data-examid="' . $exam_id . '">View Details</button>';
								} else {
									echo '<span class="badge bg-info text-dark px-3 py-2">Attempted</span>';
								}
							} else {
								if ($current_status == 'Ongoing') {
									echo '<a href="attempt-exam.php?Exam_ID=' . $exam_id . '" class="btn btn-success btn-sm px-3 py-1">Attempt</a>';
								} elseif ($current_status == 'Upcoming') {
									echo '<span class="badge bg-warning text-dark px-3 py-2">Upcoming</span>';
								} else {
									echo '<span class="badge bg-secondary px-3 py-2">Exam Over</span>';
								}
							}
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
			}
		?>

		<!-- Modal -->
		<div class="modal fade" id="resultModal" tabindex="-1" aria-labelledby="resultModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-xl modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header bg-dark text-white">
						<h5 class="modal-title" id="resultModalLabel">Student Result Details</h5>
						<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
							aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<table class="table table-bordered text-center">
							<thead class="table-dark">
								<tr>
									<th>Q.No</th>
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
		$(document).ready(function () {
			$('.view-details').click(function () {
				var studID = $(this).data('studid');
				var examID = $(this).data('examid');

				$.ajax({
					url: '../Faculty/fetch-result-details.php',
					type: 'POST',
					data: { Stud_ID: studID, Exam_ID: examID },
					success: function (response) {
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