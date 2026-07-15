<<<<<<< HEAD
<<<<<<< HEAD
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
    $Fac_Acad_Year=$row['Fac_Acad_Year'];
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
                            <div class="row mt-3  d-flex align-items-end">
                                <div class="col-md-2">
                                    <label>Academic Year</label>
                                    <select class="form-select" name="Acad_Year" required onchange="this.form.submit();">
										<option>Academic Year</option>
										<?php
                                            $ayquery = mysqli_query($con, "SELECT * FROM academic_year ORDER BY AY_Name");
                                            while($ay = mysqli_fetch_assoc($ayquery)){
                                                $selected = (isset($_POST['Acad_Year']) && $_POST['Acad_Year'] == $ay['AY_Name']) ? 'selected' : '';
                                                echo "<option value='".$ay['AY_Name']."' ".$selected.">".$ay['AY_Name']."</option>";
                                            }
										?>
									</select>
                                </div>
                                <div class="col-md-2">
									<label for="">Programme</label>
									<select name="Prog_ID" class="form-select" required onchange="this.form.submit();">
                                        <option value="">Select Programme</option>
                                        <?php
                                            $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';
                                            $query = "SELECT DISTINCT p.Prog_ID, p.Prog_Name
														FROM course_mapping m
														JOIN courses c ON m.Course_ID = c.Course_ID
														JOIN programmes p ON c.Prog_ID = p.Prog_ID
														WHERE m.Fac_ID = '$Fac_ID'
														AND m.Acad_Year = '$Acad_Year'";
                                            $run = mysqli_query($con, $query);
                                            
                                            while($row = mysqli_fetch_array($run)) {
                                                $selected = (isset($_POST['Prog_ID']) && $_POST['Prog_ID'] == $row['Prog_ID']) ? 'selected' : '';
												echo "<option value='{$row['Prog_ID']}' $selected>{$row['Prog_Name']}</option>";
                                            }
                                        ?>
                                    </select>
								</div>
                                <div class="col-md-2">
                                    <label>Semester</label>
                                    <select name="Semester" class="form-select" required onchange="this.form.submit();">
                                        <option value="">Select Semester</option>
                                        <?php
											if (isset($_POST['Prog_ID']) && $_POST['Prog_ID'] != '') {

												$Prog_ID = mysqli_real_escape_string($con, $_POST['Prog_ID']);
                                                $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';

												$semQuery = "SELECT DISTINCT c.Semester
													FROM course_mapping m
													JOIN courses c ON m.Course_ID = c.Course_ID
													WHERE m.Fac_ID = '$Fac_ID'
													AND m.Acad_Year = '$Acad_Year'
													AND c.Prog_ID = '$Prog_ID'
													ORDER BY c.Semester";

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
                                <div class="col-md-2">
                                    <label>Subject</label>
                                    <select name="Course_ID" class="form-select" required onchange="this.form.submit();">
                                        <option value="">Select Course</option>
                                        <?php
                                            $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';
                                            $Semester  = isset($_POST['Semester']) ? mysqli_real_escape_string($con, $_POST['Semester']) : '';

                                            if ($Acad_Year != "" && $Semester != "") {
                                                $squery = "SELECT c.Course_ID, c.Course_Name FROM course_mapping m INNER JOIN courses c ON m.Course_ID = c.Course_ID WHERE m.Fac_ID = '$Fac_ID' AND m.Acad_Year = '$Acad_Year' AND c.Semester = '$Semester' ORDER BY c.Course_Name";
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
                                <div class="col-md-2">
                                    <label>Exam</label>
                                    <select name="Exam_ID" class="form-select" required>
                                        <option value="">Select Course</option>
                                        <?php
                                            $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';
                                            $Semester  = isset($_POST['Semester']) ? mysqli_real_escape_string($con, $_POST['Semester']) : '';
                                            $Course_ID  = isset($_POST['Course_ID']) ? mysqli_real_escape_string($con, $_POST['Course_ID']) : '';

                                            if ($Acad_Year != "" && $Semester != "" && $Course_ID != "") {
                                                $equery = "SELECT * FROM exam WHERE Course_ID = '$Course_ID' AND Acad_Year = '$Acad_Year' AND Semester = '$Semester' AND Fac_ID = '$Fac_ID' ORDER BY Exam_Title";
                                                $run = mysqli_query($con, $equery);
                                                if (!$run || mysqli_num_rows($run) == 0) {
                                                    echo '<option value="">No Exams Available</option>';
                                                } else {
                                                    while ($row = mysqli_fetch_assoc($run)) {
                                                        $selected = (isset($_POST['Exam_ID']) && $_POST['Exam_ID'] == $row['Exam_ID']) ? 'selected' : '';
                                                        echo "<option value='{$row['Exam_ID']}' $selected>{$row['Exam_Title']}</option>";
                                                    }
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="submit" name="Submit" value="Search" class="btn btn-primary w-100">
                                </div>
                            </div>		
                        </form>
					</div>
				</div>
				<div class="row">
                    <div class="col-md-12 container-fluid">
                        <?php
                        if (isset($_POST['Submit'])) {
                            $Exam_ID = $_POST['Exam_ID'];
                            // $query = "SELECT r.Result_ID, s.Stud_ID, s.Stud_Name, r.Obtained_Marks, r.Total_Marks 
                            //             FROM result r, student s, exam e 
                            //             WHERE r.Stud_ID = s.Stud_ID 
                            //             AND  r.Exam_ID=e.Exam_ID 
                            //             AND r.Exam_ID = $Exam_ID 
                            //             ORDER BY s.Stud_Name";
$query = "SELECT 
            s.Stud_ID,
            s.Stud_Name,
            r.Result_ID,
            r.Obtained_Marks,
            r.Total_Marks
          FROM student s
          INNER JOIN courses c 
                ON s.Stud_Branch = c.Prog_ID 
                AND s.Stud_Sem = c.Semester
          INNER JOIN course_mapping m 
                ON c.Course_ID = m.Course_ID
          LEFT JOIN result r 
                ON s.Stud_ID = r.Stud_ID 
                AND r.Exam_ID = '$Exam_ID'
          WHERE c.Course_ID = '$Course_ID'
          AND m.Fac_ID = '$Fac_ID'
          AND m.Acad_Year = '$Acad_Year'
          ORDER BY s.Stud_Name";

        //   echo $query; // Debugging line to check the generated SQL query
                            $run = mysqli_query($con, $query);

                            $examQueryRun = mysqli_query($con, "SELECT Result_Status FROM exam WHERE Exam_ID = '$Exam_ID'");
                            $examRow = mysqli_fetch_assoc($examQueryRun);
                            $Result_Status = $examRow['Result_Status'];

                            if (mysqli_num_rows($run) > 0) {
                                $Sl = 1;
                        ?>
                        <section class="mt-3">
                            <table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
                                <tr class="table-dark text-white">
                                    <th>Roll No</th>
                                    <th>Name</th>
                                    <th>Total Marks</th>
                                    <th>Obtained Marks</th>
                                    <th>Action</th>
                                </tr>
                                <?php
                                $presentCount = 0;
                                $absentCount = 0;
                                while ($row = mysqli_fetch_array($run)) {
                                    if (isset($row['Result_ID'])) {
                                        $presentCount++;
                                    } else {
                                        $absentCount++;
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $Sl++; ?></td>
                                    <td><?php echo htmlspecialchars($row['Stud_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Total_Marks'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['Obtained_Marks'] ?? '-'); ?></td>
                                    <td class="w-auto">
                                        <?php if (isset($row['Result_ID'])) { ?>
                                            <button 
                                                class="btn btn-info btn-sm view-details" 
                                                data-studid="<?php echo $row['Stud_ID']; ?>" 
                                                data-examid="<?php echo $Exam_ID; ?>">
                                                View Details
                                            </button>
<<<<<<< HEAD
=======
                                            <a href="download-result.php?Stud_ID=<?php echo $row['Stud_ID']; ?>&Exam_ID=<?php echo $Exam_ID; ?>" class="btn btn-success btn-sm" target="_blank">
                                                Download
                                            </a>
>>>>>>> af63e72 (MITS-Exam)
                                            <button 
                                                class="btn btn-warning btn-sm enter-marks" 
                                                data-studid="<?php echo $row['Stud_ID']; ?>" 
                                                data-examid="<?php echo $Exam_ID; ?>">
                                                Enter/Edit Marks
                                            </button>
                                        <?php } else { ?>
                                            <span class="badge bg-danger p-2">Not Attempted</span>
                                        <?php } ?> 
                                    </td>
                                </tr>
                                <?php
                                }
                                ?>
                                <div class="mb-3">
                                    <span class="badge bg-success p-2">Present: <?php echo $presentCount; ?></span>
                                    <span class="badge bg-danger p-2">Absent: <?php echo $absentCount; ?></span>
                                    <span class="badge bg-dark p-2">
                                        Total: <?php echo ($presentCount + $absentCount); ?>
                                    </span>
                                </div>
                            </table>
                            <div class="mt-3 text-center">
                                <a href="export-results.php?Exam_ID=<?php echo $Exam_ID; ?>" class="btn btn-primary">Export Results</a>
                                <button 
                                    class="btn <?php echo ($Result_Status == 1) ? 'btn-danger' : 'btn-success'; ?> publish-btn"
                                    data-examid="<?php echo $Exam_ID; ?>" 
                                    data-status="<?php echo ($Result_Status == 1) ? '1' : '0'; ?>">
                                    <?php echo ($Result_Status == 1) ? 'UnPublish Results' : 'Publish Results'; ?>
                                </button>
                            </div>
                        </section>
                        <?php
                            } else {
                                echo "<p class='text-center mt-3 text-danger'>No results found for this exam.</p>";
                            }
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

        // Load modal
        $(document).on("click", ".enter-marks", function () {
            var studID = $(this).data("studid");
            var examID = $(this).data("examid");

            $.post("enter-marks.php", { Stud_ID: studID, Exam_ID: examID }, function(response) {
                $("#resultDetails").html(response);
                var modal = new bootstrap.Modal(document.getElementById("resultModal"));
                modal.show();
            });
        });

        // Save marks
        $(document).on("click", ".save-marks-btn", function () {
            var studID = $(this).data("studid");
            var examID = $(this).data("examid");

            var marksData = {};
            $("#resultDetails input").each(function () {
                marksData[$(this).attr("name")] = $(this).val();
            });

            $.post("enter-marks.php", {
                Stud_ID: studID,
                Exam_ID: examID,
                save: "1",
                marks: marksData
            }, function(response) {
                if (response.trim() == "success") {
                    alert("Marks Updated Successfully!");
                    location.reload();
                }
            });
        });

        // Publish results
        $(document).on('click', '.publish-btn', function () {
            const btn = $(this);
            const examId = btn.data('examid');
            const currentStatus = btn.data('status'); // 1 or 0

            btn.prop('disabled', true);

            $.ajax({
                url: 'update-exam-status.php',
                type: 'POST',
                data: {exam_id: examId, status: currentStatus},
                success: function (response) {
                    if (response.success) {
                        if (response.new_status === 1) {
                            btn
                                .removeClass('btn-success')
                                .addClass('btn-danger')
                                .text('UnPublish Results')
                                .data('status', 1);
                        } else {
                            btn
                                .removeClass('btn-danger')
                                .addClass('btn-success')
                                .text('Publish Results')
                                .data('status', 0);
                        }
                    } else {
                        alert(response.message || 'Operation failed');
                    }
                },
                error: function () {
                    alert('Server error. Try again.');
                },
                complete: function () {
                    btn.prop('disabled', false);
                }
            });
        });
    </script>

</body>
</html>



=======
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
    $Fac_Acad_Year=$row['Fac_Acad_Year'];
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
                            <div class="row mt-3  d-flex align-items-end">
                                <div class="col-md-2">
                                    <label>Academic Year</label>
                                    <select class="form-select" name="Acad_Year" required onchange="this.form.submit();">
										<option>Academic Year</option>
										<?php
                                            $ayquery = mysqli_query($con, "SELECT * FROM academic_year ORDER BY AY_Name");
                                            while($ay = mysqli_fetch_assoc($ayquery)){
                                                $selected = (isset($_POST['Acad_Year']) && $_POST['Acad_Year'] == $ay['AY_Name']) ? 'selected' : '';
                                                echo "<option value='".$ay['AY_Name']."' ".$selected.">".$ay['AY_Name']."</option>";
                                            }
										?>
									</select>
                                </div>
                                <div class="col-md-2">
									<label for="">Programme</label>
									<select name="Prog_ID" class="form-select" required onchange="this.form.submit();">
                                        <option value="">Select Programme</option>
                                        <?php
                                            $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';
                                            $query = "SELECT DISTINCT p.Prog_ID, p.Prog_Name
														FROM course_mapping m
														JOIN courses c ON m.Course_ID = c.Course_ID
														JOIN programmes p ON c.Prog_ID = p.Prog_ID
														WHERE m.Fac_ID = '$Fac_ID'
														AND m.Acad_Year = '$Acad_Year'";
                                            $run = mysqli_query($con, $query);
                                            
                                            while($row = mysqli_fetch_array($run)) {
                                                $selected = (isset($_POST['Prog_ID']) && $_POST['Prog_ID'] == $row['Prog_ID']) ? 'selected' : '';
												echo "<option value='{$row['Prog_ID']}' $selected>{$row['Prog_Name']}</option>";
                                            }
                                        ?>
                                    </select>
								</div>
                                <div class="col-md-2">
                                    <label>Semester</label>
                                    <select name="Semester" class="form-select" required onchange="this.form.submit();">
                                        <option value="">Select Semester</option>
                                        <?php
											if (isset($_POST['Prog_ID']) && $_POST['Prog_ID'] != '') {

												$Prog_ID = mysqli_real_escape_string($con, $_POST['Prog_ID']);
                                                $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';

												$semQuery = "SELECT DISTINCT c.Semester
													FROM course_mapping m
													JOIN courses c ON m.Course_ID = c.Course_ID
													WHERE m.Fac_ID = '$Fac_ID'
													AND m.Acad_Year = '$Acad_Year'
													AND c.Prog_ID = '$Prog_ID'
													ORDER BY c.Semester";

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
                                <div class="col-md-2">
                                    <label>Subject</label>
                                    <select name="Course_ID" class="form-select" required onchange="this.form.submit();">
                                        <option value="">Select Course</option>
                                        <?php
                                            $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';
                                            $Semester  = isset($_POST['Semester']) ? mysqli_real_escape_string($con, $_POST['Semester']) : '';

                                            if ($Acad_Year != "" && $Semester != "") {
                                                $squery = "SELECT c.Course_ID, c.Course_Name FROM course_mapping m INNER JOIN courses c ON m.Course_ID = c.Course_ID WHERE m.Fac_ID = '$Fac_ID' AND m.Acad_Year = '$Acad_Year' AND c.Semester = '$Semester' ORDER BY c.Course_Name";
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
                                <div class="col-md-2">
                                    <label>Exam</label>
                                    <select name="Exam_ID" class="form-select" required>
                                        <option value="">Select Course</option>
                                        <?php
                                            $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';
                                            $Semester  = isset($_POST['Semester']) ? mysqli_real_escape_string($con, $_POST['Semester']) : '';
                                            $Course_ID  = isset($_POST['Course_ID']) ? mysqli_real_escape_string($con, $_POST['Course_ID']) : '';

                                            if ($Acad_Year != "" && $Semester != "" && $Course_ID != "") {
                                                $equery = "SELECT * FROM exam WHERE Course_ID = '$Course_ID' AND Acad_Year = '$Acad_Year' AND Semester = '$Semester' AND Fac_ID = '$Fac_ID' ORDER BY Exam_Title";
                                                $run = mysqli_query($con, $equery);
                                                if (!$run || mysqli_num_rows($run) == 0) {
                                                    echo '<option value="">No Exams Available</option>';
                                                } else {
                                                    while ($row = mysqli_fetch_assoc($run)) {
                                                        $selected = (isset($_POST['Exam_ID']) && $_POST['Exam_ID'] == $row['Exam_ID']) ? 'selected' : '';
                                                        echo "<option value='{$row['Exam_ID']}' $selected>{$row['Exam_Title']}</option>";
                                                    }
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="submit" name="Submit" value="Search" class="btn btn-primary w-100">
                                </div>
                            </div>		
                        </form>
					</div>
				</div>
				<div class="row">
                    <div class="col-md-12 container-fluid">
                        <?php
                        if (isset($_POST['Submit'])) {
                            $Exam_ID = $_POST['Exam_ID'];
                            // $query = "SELECT r.Result_ID, s.Stud_ID, s.Stud_Name, r.Obtained_Marks, r.Total_Marks 
                            //             FROM result r, student s, exam e 
                            //             WHERE r.Stud_ID = s.Stud_ID 
                            //             AND  r.Exam_ID=e.Exam_ID 
                            //             AND r.Exam_ID = $Exam_ID 
                            //             ORDER BY s.Stud_Name";
$query = "SELECT 
            s.Stud_ID,
            s.Stud_Name,
            r.Result_ID,
            r.Obtained_Marks,
            r.Total_Marks
          FROM student s
          INNER JOIN courses c 
                ON s.Stud_Branch = c.Prog_ID 
                AND s.Stud_Sem = c.Semester
          INNER JOIN course_mapping m 
                ON c.Course_ID = m.Course_ID
          LEFT JOIN result r 
                ON s.Stud_ID = r.Stud_ID 
                AND r.Exam_ID = '$Exam_ID'
          WHERE c.Course_ID = '$Course_ID'
          AND m.Fac_ID = '$Fac_ID'
          AND m.Acad_Year = '$Acad_Year'
          ORDER BY s.Stud_Name";

        //   echo $query; // Debugging line to check the generated SQL query
                            $run = mysqli_query($con, $query);

                            $examQueryRun = mysqli_query($con, "SELECT Result_Status FROM exam WHERE Exam_ID = '$Exam_ID'");
                            $examRow = mysqli_fetch_assoc($examQueryRun);
                            $Result_Status = $examRow['Result_Status'];

                            if (mysqli_num_rows($run) > 0) {
                                $Sl = 1;
                        ?>
                        <section class="mt-3">
                            <table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
                                <tr class="table-dark text-white">
                                    <th>Roll No</th>
                                    <th>Name</th>
                                    <th>Total Marks</th>
                                    <th>Obtained Marks</th>
                                    <th>Action</th>
                                </tr>
                                <?php
                                $presentCount = 0;
                                $absentCount = 0;
                                while ($row = mysqli_fetch_array($run)) {
                                    if (isset($row['Result_ID'])) {
                                        $presentCount++;
                                    } else {
                                        $absentCount++;
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $Sl++; ?></td>
                                    <td><?php echo htmlspecialchars($row['Stud_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Total_Marks'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['Obtained_Marks'] ?? '-'); ?></td>
                                    <td class="w-auto">
                                        <?php if (isset($row['Result_ID'])) { ?>
                                            <button 
                                                class="btn btn-info btn-sm view-details" 
                                                data-studid="<?php echo $row['Stud_ID']; ?>" 
                                                data-examid="<?php echo $Exam_ID; ?>">
                                                View Details
                                            </button>
<<<<<<< HEAD
=======
                                            <a href="download-result.php?Stud_ID=<?php echo $row['Stud_ID']; ?>&Exam_ID=<?php echo $Exam_ID; ?>" class="btn btn-success btn-sm" target="_blank">
                                                Download
                                            </a>
>>>>>>> af63e72 (MITS-Exam)
                                            <button 
                                                class="btn btn-warning btn-sm enter-marks" 
                                                data-studid="<?php echo $row['Stud_ID']; ?>" 
                                                data-examid="<?php echo $Exam_ID; ?>">
                                                Enter/Edit Marks
                                            </button>
                                        <?php } else { ?>
                                            <span class="badge bg-danger p-2">Not Attempted</span>
                                        <?php } ?> 
                                    </td>
                                </tr>
                                <?php
                                }
                                ?>
                                <div class="mb-3">
                                    <span class="badge bg-success p-2">Present: <?php echo $presentCount; ?></span>
                                    <span class="badge bg-danger p-2">Absent: <?php echo $absentCount; ?></span>
                                    <span class="badge bg-dark p-2">
                                        Total: <?php echo ($presentCount + $absentCount); ?>
                                    </span>
                                </div>
                            </table>
                            <div class="mt-3 text-center">
                                <a href="export-results.php?Exam_ID=<?php echo $Exam_ID; ?>" class="btn btn-primary">Export Results</a>
                                <button 
                                    class="btn <?php echo ($Result_Status == 1) ? 'btn-danger' : 'btn-success'; ?> publish-btn"
                                    data-examid="<?php echo $Exam_ID; ?>" 
                                    data-status="<?php echo ($Result_Status == 1) ? '1' : '0'; ?>">
                                    <?php echo ($Result_Status == 1) ? 'UnPublish Results' : 'Publish Results'; ?>
                                </button>
                            </div>
                        </section>
                        <?php
                            } else {
                                echo "<p class='text-center mt-3 text-danger'>No results found for this exam.</p>";
                            }
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

        // Load modal
        $(document).on("click", ".enter-marks", function () {
            var studID = $(this).data("studid");
            var examID = $(this).data("examid");

            $.post("enter-marks.php", { Stud_ID: studID, Exam_ID: examID }, function(response) {
                $("#resultDetails").html(response);
                var modal = new bootstrap.Modal(document.getElementById("resultModal"));
                modal.show();
            });
        });

        // Save marks
        $(document).on("click", ".save-marks-btn", function () {
            var studID = $(this).data("studid");
            var examID = $(this).data("examid");

            var marksData = {};
            $("#resultDetails input").each(function () {
                marksData[$(this).attr("name")] = $(this).val();
            });

            $.post("enter-marks.php", {
                Stud_ID: studID,
                Exam_ID: examID,
                save: "1",
                marks: marksData
            }, function(response) {
                if (response.trim() == "success") {
                    alert("Marks Updated Successfully!");
                    location.reload();
                }
            });
        });

        // Publish results
        $(document).on('click', '.publish-btn', function () {
            const btn = $(this);
            const examId = btn.data('examid');
            const currentStatus = btn.data('status'); // 1 or 0

            btn.prop('disabled', true);

            $.ajax({
                url: 'update-exam-status.php',
                type: 'POST',
                data: {exam_id: examId, status: currentStatus},
                success: function (response) {
                    if (response.success) {
                        if (response.new_status === 1) {
                            btn
                                .removeClass('btn-success')
                                .addClass('btn-danger')
                                .text('UnPublish Results')
                                .data('status', 1);
                        } else {
                            btn
                                .removeClass('btn-danger')
                                .addClass('btn-success')
                                .text('Publish Results')
                                .data('status', 0);
                        }
                    } else {
                        alert(response.message || 'Operation failed');
                    }
                },
                error: function () {
                    alert('Server error. Try again.');
                },
                complete: function () {
                    btn.prop('disabled', false);
                }
            });
        });
    </script>

</body>
</html>



>>>>>>> f1e265abf03ca415a8e766b8518d8c076d9bf836
=======
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
    $Fac_Acad_Year=$row['Fac_Acad_Year'];
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
                            <div class="row mt-3  d-flex align-items-end">
                                <div class="col-md-2">
                                    <label>Academic Year</label>
                                    <select class="form-select" name="Acad_Year" required onchange="this.form.submit();">
										<option>Academic Year</option>
										<?php
                                            $ayquery = mysqli_query($con, "SELECT * FROM academic_year ORDER BY AY_Name");
                                            while($ay = mysqli_fetch_assoc($ayquery)){
                                                $selected = (isset($_POST['Acad_Year']) && $_POST['Acad_Year'] == $ay['AY_Name']) ? 'selected' : '';
                                                echo "<option value='".$ay['AY_Name']."' ".$selected.">".$ay['AY_Name']."</option>";
                                            }
										?>
									</select>
                                </div>
                                <div class="col-md-2">
									<label for="">Programme</label>
									<select name="Prog_ID" class="form-select" required onchange="this.form.submit();">
                                        <option value="">Select Programme</option>
                                        <?php
                                            $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';
                                            $query = "SELECT DISTINCT p.Prog_ID, p.Prog_Name
														FROM course_mapping m
														JOIN courses c ON m.Course_ID = c.Course_ID
														JOIN programmes p ON c.Prog_ID = p.Prog_ID
														WHERE m.Fac_ID = '$Fac_ID'
														AND m.Acad_Year = '$Acad_Year'";
                                            $run = mysqli_query($con, $query);
                                            
                                            while($row = mysqli_fetch_array($run)) {
                                                $selected = (isset($_POST['Prog_ID']) && $_POST['Prog_ID'] == $row['Prog_ID']) ? 'selected' : '';
												echo "<option value='{$row['Prog_ID']}' $selected>{$row['Prog_Name']}</option>";
                                            }
                                        ?>
                                    </select>
								</div>
                                <div class="col-md-2">
                                    <label>Semester</label>
                                    <select name="Semester" class="form-select" required onchange="this.form.submit();">
                                        <option value="">Select Semester</option>
                                        <?php
											if (isset($_POST['Prog_ID']) && $_POST['Prog_ID'] != '') {

												$Prog_ID = mysqli_real_escape_string($con, $_POST['Prog_ID']);
                                                $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';

												$semQuery = "SELECT DISTINCT c.Semester
													FROM course_mapping m
													JOIN courses c ON m.Course_ID = c.Course_ID
													WHERE m.Fac_ID = '$Fac_ID'
													AND m.Acad_Year = '$Acad_Year'
													AND c.Prog_ID = '$Prog_ID'
													ORDER BY c.Semester";

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
                                <div class="col-md-2">
                                    <label>Subject</label>
                                    <select name="Course_ID" class="form-select" required onchange="this.form.submit();">
                                        <option value="">Select Course</option>
                                        <?php
                                            $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';
                                            $Semester  = isset($_POST['Semester']) ? mysqli_real_escape_string($con, $_POST['Semester']) : '';

                                            if ($Acad_Year != "" && $Semester != "") {
                                                $squery = "SELECT c.Course_ID, c.Course_Name FROM course_mapping m INNER JOIN courses c ON m.Course_ID = c.Course_ID WHERE m.Fac_ID = '$Fac_ID' AND m.Acad_Year = '$Acad_Year' AND c.Semester = '$Semester' ORDER BY c.Course_Name";
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
                                <div class="col-md-2">
                                    <label>Exam</label>
                                    <select name="Exam_ID" class="form-select" required>
                                        <option value="">Select Course</option>
                                        <?php
                                            $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';
                                            $Semester  = isset($_POST['Semester']) ? mysqli_real_escape_string($con, $_POST['Semester']) : '';
                                            $Course_ID  = isset($_POST['Course_ID']) ? mysqli_real_escape_string($con, $_POST['Course_ID']) : '';

                                            if ($Acad_Year != "" && $Semester != "" && $Course_ID != "") {
                                                $equery = "SELECT * FROM exam WHERE Course_ID = '$Course_ID' AND Acad_Year = '$Acad_Year' AND Semester = '$Semester' AND Fac_ID = '$Fac_ID' ORDER BY Exam_Title";
                                                $run = mysqli_query($con, $equery);
                                                if (!$run || mysqli_num_rows($run) == 0) {
                                                    echo '<option value="">No Exams Available</option>';
                                                } else {
                                                    while ($row = mysqli_fetch_assoc($run)) {
                                                        $selected = (isset($_POST['Exam_ID']) && $_POST['Exam_ID'] == $row['Exam_ID']) ? 'selected' : '';
                                                        echo "<option value='{$row['Exam_ID']}' $selected>{$row['Exam_Title']}</option>";
                                                    }
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="submit" name="Submit" value="Search" class="btn btn-primary w-100">
                                </div>
                            </div>		
                        </form>
					</div>
				</div>
				<div class="row">
                    <div class="col-md-12 container-fluid">
                        <?php
                        if (isset($_POST['Submit'])) {
                            $Exam_ID = $_POST['Exam_ID'];
                            // $query = "SELECT r.Result_ID, s.Stud_ID, s.Stud_Name, r.Obtained_Marks, r.Total_Marks 
                            //             FROM result r, student s, exam e 
                            //             WHERE r.Stud_ID = s.Stud_ID 
                            //             AND  r.Exam_ID=e.Exam_ID 
                            //             AND r.Exam_ID = $Exam_ID 
                            //             ORDER BY s.Stud_Name";
$query = "SELECT 
            s.Stud_ID,
            s.Stud_Name,
            r.Result_ID,
            r.Obtained_Marks,
            r.Total_Marks
          FROM student s
          INNER JOIN courses c 
                ON s.Stud_Branch = c.Prog_ID 
                AND s.Stud_Sem = c.Semester
          INNER JOIN course_mapping m 
                ON c.Course_ID = m.Course_ID
          LEFT JOIN result r 
                ON s.Stud_ID = r.Stud_ID 
                AND r.Exam_ID = '$Exam_ID'
          WHERE c.Course_ID = '$Course_ID'
          AND m.Fac_ID = '$Fac_ID'
          AND m.Acad_Year = '$Acad_Year'
          ORDER BY s.Stud_Name";

        //   echo $query; // Debugging line to check the generated SQL query
                            $run = mysqli_query($con, $query);

                            $examQueryRun = mysqli_query($con, "SELECT Result_Status FROM exam WHERE Exam_ID = '$Exam_ID'");
                            $examRow = mysqli_fetch_assoc($examQueryRun);
                            $Result_Status = $examRow['Result_Status'];

                            if (mysqli_num_rows($run) > 0) {
                                $Sl = 1;
                        ?>
                        <section class="mt-3">
                            <table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
                                <tr class="table-dark text-white">
                                    <th>Roll No</th>
                                    <th>Name</th>
                                    <th>Total Marks</th>
                                    <th>Obtained Marks</th>
                                    <th>Action</th>
                                </tr>
                                <?php
                                $presentCount = 0;
                                $absentCount = 0;
                                while ($row = mysqli_fetch_array($run)) {
                                    if (isset($row['Result_ID'])) {
                                        $presentCount++;
                                    } else {
                                        $absentCount++;
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $Sl++; ?></td>
                                    <td><?php echo htmlspecialchars($row['Stud_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Total_Marks'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['Obtained_Marks'] ?? '-'); ?></td>
                                    <td class="w-auto">
                                        <?php if (isset($row['Result_ID'])) { ?>
                                            <button 
                                                class="btn btn-info btn-sm view-details" 
                                                data-studid="<?php echo $row['Stud_ID']; ?>" 
                                                data-examid="<?php echo $Exam_ID; ?>">
                                                View Details
                                            </button>
<<<<<<< HEAD
=======
                                            <a href="download-result.php?Stud_ID=<?php echo $row['Stud_ID']; ?>&Exam_ID=<?php echo $Exam_ID; ?>" class="btn btn-success btn-sm" target="_blank">
                                                Download
                                            </a>
>>>>>>> af63e72 (MITS-Exam)
                                            <button 
                                                class="btn btn-warning btn-sm enter-marks" 
                                                data-studid="<?php echo $row['Stud_ID']; ?>" 
                                                data-examid="<?php echo $Exam_ID; ?>">
                                                Enter/Edit Marks
                                            </button>
                                        <?php } else { ?>
                                            <span class="badge bg-danger p-2">Not Attempted</span>
                                        <?php } ?> 
                                    </td>
                                </tr>
                                <?php
                                }
                                ?>
                                <div class="mb-3">
                                    <span class="badge bg-success p-2">Present: <?php echo $presentCount; ?></span>
                                    <span class="badge bg-danger p-2">Absent: <?php echo $absentCount; ?></span>
                                    <span class="badge bg-dark p-2">
                                        Total: <?php echo ($presentCount + $absentCount); ?>
                                    </span>
                                </div>
                            </table>
                            <div class="mt-3 text-center">
                                <a href="export-results.php?Exam_ID=<?php echo $Exam_ID; ?>" class="btn btn-primary">Export Results</a>
                                <button 
                                    class="btn <?php echo ($Result_Status == 1) ? 'btn-danger' : 'btn-success'; ?> publish-btn"
                                    data-examid="<?php echo $Exam_ID; ?>" 
                                    data-status="<?php echo ($Result_Status == 1) ? '1' : '0'; ?>">
                                    <?php echo ($Result_Status == 1) ? 'UnPublish Results' : 'Publish Results'; ?>
                                </button>
                            </div>
                        </section>
                        <?php
                            } else {
                                echo "<p class='text-center mt-3 text-danger'>No results found for this exam.</p>";
                            }
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

        // Load modal
        $(document).on("click", ".enter-marks", function () {
            var studID = $(this).data("studid");
            var examID = $(this).data("examid");

            $.post("enter-marks.php", { Stud_ID: studID, Exam_ID: examID }, function(response) {
                $("#resultDetails").html(response);
                var modal = new bootstrap.Modal(document.getElementById("resultModal"));
                modal.show();
            });
        });

        // Save marks
        $(document).on("click", ".save-marks-btn", function () {
            var studID = $(this).data("studid");
            var examID = $(this).data("examid");

            var marksData = {};
            $("#resultDetails input").each(function () {
                marksData[$(this).attr("name")] = $(this).val();
            });

            $.post("enter-marks.php", {
                Stud_ID: studID,
                Exam_ID: examID,
                save: "1",
                marks: marksData
            }, function(response) {
                if (response.trim() == "success") {
                    alert("Marks Updated Successfully!");
                    location.reload();
                }
            });
        });

        // Publish results
        $(document).on('click', '.publish-btn', function () {
            const btn = $(this);
            const examId = btn.data('examid');
            const currentStatus = btn.data('status'); // 1 or 0

            btn.prop('disabled', true);

            $.ajax({
                url: 'update-exam-status.php',
                type: 'POST',
                data: {exam_id: examId, status: currentStatus},
                success: function (response) {
                    if (response.success) {
                        if (response.new_status === 1) {
                            btn
                                .removeClass('btn-success')
                                .addClass('btn-danger')
                                .text('UnPublish Results')
                                .data('status', 1);
                        } else {
                            btn
                                .removeClass('btn-danger')
                                .addClass('btn-success')
                                .text('Publish Results')
                                .data('status', 0);
                        }
                    } else {
                        alert(response.message || 'Operation failed');
                    }
                },
                error: function () {
                    alert('Server error. Try again.');
                },
                complete: function () {
                    btn.prop('disabled', false);
                }
            });
        });
    </script>

</body>
</html>



>>>>>>> f1e265abf03ca415a8e766b8518d8c076d9bf836
            