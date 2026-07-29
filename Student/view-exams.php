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
	$examquery = "SELECT * FROM `results` WHERE `Stud_ID` = '$Stud_ID' ";
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
			box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
			transition: all 0.2s ease;
			background-color: #fff;
			margin-bottom: 1rem;
			padding: 1rem 1.5rem;
		}

		.exam-card:hover {
			transform: translateY(-3px);
			box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
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
			$query ="SELECT e.Exam_ID, e.Exam_Name, e.Start_Time, e.End_Time, e.Total_Marks, e.Duration, c.Course_Name, e.Result_Status,
				CASE 
					WHEN CONVERT_TZ(NOW(), '+00:00', '+05:30') < e.Start_Time THEN 'Upcoming'
					WHEN CONVERT_TZ(NOW(), '+00:00', '+05:30') BETWEEN e.Start_Time AND e.End_Time THEN 'Ongoing'
					ELSE 'Completed'
				END AS Current_Status
			FROM student_course_mapping scm 
				JOIN exams e ON scm.Course_ID = e.Course_ID
				JOIN courses c ON e.Course_ID = c.Course_ID
				WHERE scm.Stud_ID = '$Stud_ID'";
						
			$run = mysqli_query($con, $query);
			if(mysqli_num_rows($run) == 0) {
				echo '<div class="alert alert-warning mt-4 text-center" role="alert">No exams available at the moment.</div>';
			}
			while($row = mysqli_fetch_array($run)) {
		?>
		<div class="exam-card">
			<div class="exam-row">
				<div class="exam-title">
					<?php echo $row['Exam_Name']; ?>
				</div>
				<div class="exam-item"><strong>Subject:</strong>
					<?php echo $row['Course_Name']; ?>
				</div>
				<div class="exam-item"><strong>Max Marks:</strong>
					<?php echo $row['Total_Marks']; ?>
				</div>
				<div class="exam-item"><strong>Start Time:</strong>
					<?php echo $row['Start_Time']; ?>
				</div>
				<div class="exam-item"><strong>End Time:</strong>
					<?php echo $row['End_Time']; ?>
				</div>
				<?php
					if($row['Result_Status'] == 1) {
						echo '<div class="exam-item"><strong>Marks:</strong> ' . $row['Total_Marks'] . '</div>';
					} 
				?>
				<div class="exam-action">
					<?php 
						$exam_id = $row['Exam_ID'];
						$current_status = $row['Current_Status'];
						if (in_array($exam_id, $attempted_exams)) {
							if ($row['Result_Status'] == 1) {
								echo '<button class="btn btn-primary btn-sm view-details" data-studid="' . $Stud_ID . '" data-examid="' . $exam_id . '">View Details</button>';
							} else {
								echo '<span class="badge bg-info text-dark">Attempted</span>';
							}
						} else {
							if ($current_status == 'Ongoing') {
								echo '<a href="attempt-exam-new.php?Exam_ID=' . $exam_id . '" class="btn btn-success btn-sm">Attempt</a>';
							} elseif ($current_status == 'Upcoming') {
								echo '<span class="badge bg-warning text-dark">Upcoming</span>';
							} else {
								echo '<span class="badge bg-secondary">Exam Over</span>';
							}
						}
					?>
				</div>
			</div>
		</div>
		<?php
			}
		?>
		<!-- Modal -->
        <div class="modal fade" id="resultModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="resultModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title" id="resultModalLabel">Student Result Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="resultDetails">
                        <div class="text-center text-muted py-5">
                            <div class="spinner-border text-danger" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</main>
	<?php include '../Common/footer.php'; ?>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

	<script>
		$(document).ready(function () {
			$(document).on('click', '.view-details', function (e) {
                e.preventDefault();
                var studID = $(this).data('studid');
                var examID = $(this).data('examid');

                $('#resultDetails').html('<div class="text-center text-muted py-5"><div class="spinner-border text-danger" role="status"></div></div>');
                var modal = new bootstrap.Modal(document.getElementById('resultModal'));
                modal.show();

                $.ajax({
                    url: '../Faculty/fetch-result-details.php',
                    type: 'POST',
                    data: { Stud_ID: studID, Exam_ID: examID },
                    success: function (response) {
                        $('#resultDetails').html(response);
                    },
                    error: function () {
                        $('#resultDetails').html('<p class="text-danger text-center py-5">Could not load result details.</p>');
                    }
                });
            });
		}); 
	</script>

</body>

</html>