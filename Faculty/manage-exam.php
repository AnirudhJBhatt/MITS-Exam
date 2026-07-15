<?php  
	session_start();
	if (!$_SESSION["LoginFaculty"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../Login/Login.php"</script>';
	}

	require_once "../Connection/connection.php";
	
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

    $Fac_ID=$_SESSION['LoginFaculty'];
	$query = "SELECT * FROM `faculty` WHERE `Fac_ID` = '$Fac_ID' ";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_array($run);
	$Fac_Dept=$row['Fac_Dept'];
	$Fac_Acad_Year = $row['Acad_Year'];
?>
<?php
	if (isset($_POST['Submit'])) {

		$Exam_Title = $_POST['Exam_Title'];
		$Course_ID    = $_POST['Course_ID'];
		$Exam_Type  = $_POST['Exam_Type'];
		$Duration   = $_POST['Duration'];
		$Semester   = $_POST['Semester'];
		$Start_Time = $_POST['Start_Time'];
		$End_Time   = $_POST['End_Time'];
		$Acad_Year  = $Fac_Acad_Year; // already fetched earlier
		$Dept       = $Fac_Dept;  // already fetched earlier
		$Prog_ID	= $_POST['Prog_ID'];

		$Q_IDs = []; 

		if (isset($_POST['is_diagram_mode']) && $_POST['is_diagram_mode'] == "1") {

			$inserted_qids = [];

			foreach ($_POST['question_text'] as $i => $qtext) {

				$blob = mysqli_real_escape_string($con, file_get_contents($_FILES['diagram']['tmp_name'][$i]));
				$answer = mysqli_real_escape_string($con, $_POST['correct_answer'][$i]);
				$marks  = mysqli_real_escape_string($con, $_POST['marks'][$i]);
				$co  = mysqli_real_escape_string($con, $_POST['co'][$i]);
				$answer = json_encode($answer);

				$query = "INSERT INTO question_bank (Type_ID, Question_Text, Subject_Code, Diagram_Image, Correct_Answer, Marks, CO) VALUES (9, '$qtext', '$Course_ID', '$blob', '$answer', '$marks', '$co')";

				$run = mysqli_query($con, $query);

				if (!$run) {
					echo "INSERT ERROR: " . mysqli_error($con);
					exit;
				}

				$Q_IDs[] = mysqli_insert_id($con);
			}

			print_r($Q_IDs); // debug
		}
		else {
			$Q_IDs = isset($_POST['selected_questions'])? $_POST['selected_questions']: [];
		}

		$Q_IDs_json = json_encode($Q_IDs);

		$query = "INSERT INTO exam (Fac_ID, Exam_Title, Exam_Type, Course_ID, Dept, Prog_ID, Acad_Year, Semester, Q_IDs, Duration, Start_Time, End_Time) VALUES ('$Fac_ID', '$Exam_Title', '$Exam_Type', '$Course_ID', '$Dept', '$Prog_ID', '$Acad_Year', '$Semester', '$Q_IDs_json', '$Duration', '$Start_Time', '$End_Time')";

		$run = mysqli_query($con, $query);

		if ($run) {
			echo "<script>alert('Exam Added Successfully');window.location.href = window.location.href;</script>";
			// echo $query;
		} else {
			echo "Error: " . mysqli_error($con);
		}
	}
?>

<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Faculty - Manage Exam</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
	<?php include '../Common/header.php'; ?>
	<?php include '../Common/faculty-sidebar.php'; ?>
	<main>
		<div class="dashboard-header">
			<h4 class="mb-0 fw-bold">Exam</h4>
		</div>
		    <div class="sub-main">
				<div class="row">
					<div class="col-md-12 container-fluid">
						<form method="POST" enctype="multipart/form-data">
							<div class="row mt-3">
								<div class="col-md-4">
									<label>Title</label>
									<input type="text" name="Exam_Title" class="form-control" required value="<?php echo isset($_POST['Exam_Title']) ? htmlspecialchars($_POST['Exam_Title']) : ''; ?>">
								</div>
								<div class="col-md-4">
									<label for="">Programme</label>
									<select name="Prog_ID" class="form-select" required onchange="this.form.submit();">
                                        <option value="">Select Programme</option>
                                        <?php
                                            $query = "SELECT DISTINCT p.Prog_ID, p.Prog_Name
														FROM course_mapping m
														JOIN courses c ON m.Course_ID = c.Course_ID
														JOIN programmes p ON c.Prog_ID = p.Prog_ID
														WHERE m.Fac_ID = '$Fac_ID'
														AND m.Acad_Year = '$Fac_Acad_Year'";
                                            $run = mysqli_query($con, $query);
                                            while($row = mysqli_fetch_array($run)) {
                                                $selected = (isset($_POST['Prog_ID']) && $_POST['Prog_ID'] == $row['Prog_ID']) ? 'selected' : '';
												echo "<option value='{$row['Prog_ID']}' $selected>{$row['Prog_Name']}</option>";
                                            }
                                        ?>
                                    </select>
								</div>
								<div class="col-md-4">
									<label>Semester</label>
									<select name="Semester" class="form-select" required onchange="this.form.submit();">
                                        <option value="">Select Semester</option>
										<?php
											if (isset($_POST['Prog_ID']) && $_POST['Prog_ID'] != '') {

												$Prog_ID = mysqli_real_escape_string($con, $_POST['Prog_ID']);

												$semQuery = "SELECT DISTINCT c.Semester
													FROM course_mapping m
													JOIN courses c ON m.Course_ID = c.Course_ID
													WHERE m.Fac_ID = '$Fac_ID'
													AND m.Acad_Year = '$Fac_Acad_Year'
													AND c.Prog_ID = '$Prog_ID'
													ORDER BY c.Semester
												";

												$semRun = mysqli_query($con, $semQuery);

												while ($row = mysqli_fetch_assoc($semRun)) {
													$sem = $row['Semester'];
													$selected = (isset($_POST['Semester']) && $_POST['Semester'] == $sem) ? 'selected' : '';
													echo "<option value='$sem' $selected>S$sem</option>";
												}
											}
										?>
                                    </select>
								</div>
							</div>	
							<div class="row mt-3">	
								<div class="col-md-4">
									<label>Subject</label>
									<select name="Course_ID" class="form-select" id="Course_ID" required onchange="this.form.submit();">
                                        <option value="">Select Course</option>
                                        <?php
                                            $Semester  = isset($_POST['Semester']) ? mysqli_real_escape_string($con, $_POST['Semester']) : '';
											$Prog_ID   = isset($_POST['Prog_ID']) ? mysqli_real_escape_string($con, $_POST['Prog_ID']) : '';
                                            if ($Semester != "" && $Prog_ID != "") {
                                                $squery = "SELECT c.Course_ID, c.Course_Name FROM course_mapping m INNER JOIN courses c ON m.Course_ID = c.Course_ID WHERE m.Fac_ID = '$Fac_ID' AND m.Acad_Year = '$Fac_Acad_Year' AND c.Semester = '$Semester' AND c.Prog_ID = '$Prog_ID' ORDER BY c.Course_Name";
                                                echo "<script>console.log('Query: ' + " . json_encode($squery) . ");</script>";
												$run = mysqli_query($con, $squery);
                                                if (!$run || mysqli_num_rows($run) == 0) {
                                                    echo '<option value="">No Courses Available</option>';
                                                } else {
                                                    while ($row = mysqli_fetch_assoc($run)) {
                                                        $selected = (isset($_POST['Course_ID']) && $_POST['Course_ID'] == $row['Course_ID']) ? 'selected' : '';
                                                        echo "<option value='{$row['Course_ID']}' $selected>{$row['Course_Name']}</option>";
                                                    }
                                                }
                                            }
                                        ?>
                                    </select>
								</div>
								<div class="col-md-4">
									<label>No of Questions</label>
									<input type="number" id="NoOfQuestions" name="" class="form-control" required>
								</div>
								<div class="col-md-4">
									<label>Type</label>
									<select class="form-select" name="Exam_Type" id="Exam_Type" required>
										<option value="">---None---</option>
										<?php
											$query = "SELECT * FROM question_type;";
											$run = mysqli_query($con, $query);
											while($row = mysqli_fetch_array($run)) {
												echo '<option value="'.$row['Type_ID'].'">'.$row['Type_Name'].'</option>';
											}
										?>
									</select>
								</div>
							</div>
							<div class="row mt-3">								
								<div class="col-md-4">
									<label>Duration(minutes)</label>
									<input type="number" name="Duration" class="form-control" required>
								</div>						
								<div class="col-md-4">
									<label>Start Time</label>
									<input type="datetime-local" name="Start_Time" class="form-control" required>
								</div>							
								<div class="col-md-4">
									<label>End Time</label>
									<input type="datetime-local" name="End_Time" class="form-control" required>
								</div>
							</div>		
							<!-- Container for the question table -->
							<div class="row mt-3" id="questionTableContainer"></div>
							<div class="row mt-3">
								<div class="col-md-6">
									<input type="submit" name="Submit" value="Add Exam" class="btn btn-success">
								</div>
							</div>			
						</form>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12 container-fluid">
						<div class="col-md-4">
							<label>Academic Year</label>
							<form action="">
								<select class="form-select" name="Acad_Year" required>
								<option>Academic Year</option>
								<?php
									$ayquery = mysqli_query($con, "SELECT * FROM academic_year ORDER BY AY_Name");
									while($ay = mysqli_fetch_assoc($ayquery)){
										$selected = (isset($_POST['Acad_Year']) && $_POST['Acad_Year'] == $ay['AY_Name']) ? 'selected' : '';
										echo "<option value='".$ay['AY_Name']."' ".$selected.">".$ay['AY_Name']."</option>";
									}
								?>
								</select>
							</form>
						</div>
						<section class="mt-3">
							<?php
								$query = "SELECT e.Exam_ID, e.Exam_Title, c.Course_Name, c.Semester, p.Prog_Name 
											FROM exam e, courses c, programmes p
											WHERE c.Course_ID=e.Course_ID 
											AND p.Prog_ID=c.Prog_ID 
											AND e.Fac_ID = '$Fac_ID' 
											ORDER BY Exam_ID DESC";
								$run = mysqli_query($con, $query);
								if (mysqli_num_rows($run) == 0) {
									echo '<div class="alert alert-warning mt-3">No exams found.</div>';
								}
								else {
							?>
										<table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
											<tr class="table-dark text-white">
												<th>SL No</th>
												<th>Exam Name</th>
												<th>Batch</th>
												<th>Subject</th>
												<th>Action</th>
											</tr>
											<?php
												$Sl=1;
												while($row=mysqli_fetch_array($run)) {
													$Exam_ID=$row['Exam_ID'];
													$checkResult = mysqli_query($con, "SELECT 1 FROM result WHERE Exam_ID = '$Exam_ID' LIMIT 1");
													$hasResult = mysqli_num_rows($checkResult) > 0;
											?>
											<tr>
												<td><?php echo $Sl++; ?></td>
												<td><?php echo $row['Exam_Title']; ?></td>
												<td><?php echo "S".$row['Semester']." ".$row['Prog_Name']; ?></td>
												<td><?php echo $row['Course_Name']; ?></td>
												<td width='300'>
													<button class="btn btn-info btn-sm downloadQPBtn" data-exam-id="<?php echo $Exam_ID; ?>">
														Download QP
													</button>
													<a href="#" class="btn btn-warning btn-sm editExamBtn" data-exam-id="<?php echo $Exam_ID; ?>" data-bs-toggle="modal" data-bs-target="#editExamModal"> Edit </a>
													<?php if ($hasResult): ?>
														<button class="btn btn-danger btn-sm" disabled>Delete</button>
													<?php else: ?>
														<a href="../Admin/delete.php?Exam_ID=<?php echo $Exam_ID; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this exam?');">Delete</a>
													<?php endif; ?>
												</td>
											</tr>
											<?php
												}
											?>
										</table>
							<?php
								}
							?>				
						</section>
					</div>
				</div>
			</div>
	</main>
    <div class="modal fade" id="editExamModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="editExamModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-xl modal-dialog-centered">			
			<div class="modal-content">
				<div class="modal-header bg-dark text-white">
					<h5 class="modal-title">Edit Exam</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body" id="editExamContent">
				</div>
			</div>
		</div>
	</div>

	<?php include '../Common/footer.php'; ?>
</body>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
	$(document).ready(function(){
		$('#Exam_Type').change(function () {
			var type_id = $(this).val();
			var noq = $('#NoOfQuestions').val();
			var Course_ID = $('#Course_ID').val();

			if (noq === "" || noq <= 0) {
				alert("Enter number of questions first.");
				$('#Exam_Type').val("");
				return;
			}

			if (type_id !== "") {
				$.ajax({
					url: 'fetch_questions.php',
					method: 'POST',
					data: { type_id: type_id, noq: noq, Course_ID: Course_ID },
					success: function (response) {
						$('#questionTableContainer').html(response);
					}
				});
			} else {
				$('#questionTableContainer').html('');
			}
		});
	});
	
	$('form').on('submit', function(e){

		let isDiagramMode = $('input[name="is_diagram_mode"]').val() === "1";
		if (!isDiagramMode) {
			let count = $('input[name="selected_questions[]"]:checked').length;
			if (count === 0) {
				alert("Please select at least one question before adding the exam.");
				e.preventDefault();
				return false;
			}
		}
	});

	$(document).on('click', '.editExamBtn', function () {
		var examId = $(this).data('exam-id');

		$.ajax({
			url: '../Admin/edit-exam.php',
			type: 'POST',
			data: { Exam_ID: examId },
			success: function (response) {
				$('#editExamContent').html(response);
			}
		});
	});
	
	$(document).on('click', '.downloadQPBtn', function () {
    let examId = $(this).data('exam-id');

	// download as word document
    $.ajax({
        url: 'generate_qp copy.php',
        type: 'POST',
        data: { Exam_ID: examId },
        xhrFields: {
            responseType: 'blob' // IMPORTANT for PDF
        },
        success: function (data) {
            
            let blob = new Blob([data], {
                type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            });
			
            let link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = "Question_Paper.docx";
            link.click();
        },
        error: function () {
            alert("Failed to generate Word document");
        }
    });
});

</script>

</html>