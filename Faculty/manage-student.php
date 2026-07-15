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
	$Fac_Acad_Year=$row['Acad_Year'];
?>

<?php

?>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Faculty - Manage Students</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
	<?php include '../Common/header.php'; ?>
	<?php include '../Common/faculty-sidebar.php'; ?>

	<main>
		<div class="dashboard-header">
			<h4 class="mb-0 fw-bold">View Students</h4>
		</div>
			<div class="sub-main">
				<div class="row">
					<div class="col-md-12 container-fluid">
						<form method="POST" enctype="multipart/form-data">
							<div class="row mt-3 d-flex align-items-end">
								<div class="col-md-3">
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
								<div class="col-md-3">
									<label>Programme</label>
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
								<div class="col-md-3">
									<label>Semester</label>
									<select name="Semester" class="form-select" required>
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
								<div class="col-md-3">
									<input type="submit" name="Submit" value="View" class="btn btn-success w-50">
								</div>
							</div>			
						</form>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12 container-fluid">
					<?php	
						if (isset($_POST['Submit'])) {
							$Acad_Year = mysqli_real_escape_string($con, $_POST['Acad_Year']);
							$Prog_ID = mysqli_real_escape_string($con, $_POST['Prog_ID']);
							$Semester = mysqli_real_escape_string($con, $_POST['Semester']);
							$query = "SELECT * FROM student WHERE Stud_Branch='$Prog_ID' AND Stud_Sem='$Semester' AND Curr_AY='$Acad_Year' ORDER BY Stud_Name ASC;";
							$run = mysqli_query($con, $query);
							if (mysqli_num_rows($run) == 0) {
								echo "<p class='mt-3 text-danger text-center'>No students found for the selected batch.</p>";
							}
							else {
					?>	
						<section class="mt-3">
							<table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
								<tr class="table-dark text-white">
									<th>SL No</th>
									<th>Student ID</th>
									<th>Name</th>
									<th>Branch</th>
									<th>Year</th>
								</tr>
								<?php
									$Sl=1;
									while($row=mysqli_fetch_array($run)) {
								?>
								<tr>
									<td><?php echo $Sl++; ?></td>
									<td><?php echo $row['Stud_ID']; ?></td>
									<td><?php echo $row['Stud_Name']; ?></td>
									<td><?php echo $row['Stud_Dept']; ?></td>
									<td><?php echo $row['Stud_Year']; ?></td>
									<!-- <td width='200'>
										<a class="btn btn-warning" href="view-results.php?Exam_ID=<?php echo $row['Exam_ID']; ?>">Edit</a>
									</td> -->
								</tr>
								<?php
									}
								?>
							</table>				
						</section>
					<?php
							}
						}
					?>
					</div>
				</div>
			</div>
	</main>
	<?php include '../Common/footer.php'; ?>
</body>

</html>



            