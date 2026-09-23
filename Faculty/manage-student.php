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
<!DOCTYPE html>
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

		<div class="container-fluid">
			<!-- Header -->
			<div class="dashboard-header mb-4 d-flex justify-content-between align-items-center border-bottom pb-3">
				<div>
					<h4 class="mb-0 fw-bold">View Students</h4>
				</div>
			</div>

			<div class="sub-main">
				<!-- Filter Card -->
				<div class="card shadow-sm border-0 mb-5">
					<div class="card-header bg-light border-0 pt-3 pb-2">
						<h6 class="mb-0 fw-bold text-secondary text-uppercase" style="letter-spacing: 0.5px;">Filter Students</h6>
					</div>
					<div class="card-body px-4 py-3">
						<form method="POST" class="row g-3 align-items-end">
							<div class="col-md-3">
								<label class="form-label text-muted small fw-bold mb-1">Academic Year</label>
								<select class="form-select shadow-none border-secondary-subtle" name="Acad_Year" required onchange="this.form.submit();">
									<option value="">Select Year</option>
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
								<label class="form-label text-muted small fw-bold mb-1">Programme</label>
								<select name="Prog_ID" class="form-select shadow-none border-secondary-subtle" required onchange="this.form.submit();">
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
								<label class="form-label text-muted small fw-bold mb-1">Semester</label>
								<select name="Semester" class="form-select shadow-none border-secondary-subtle" required>
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
							<div class="col-md-3 d-grid">
								<button type="submit" name="Submit" class="btn btn-primary shadow-sm fw-bold">View Students</button>
							</div>
						</form>
					</div>
				</div>
				
				<!-- Results Section -->
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
								echo "<div class='alert alert-info shadow-sm border-0 d-flex align-items-center gap-2'><i class='bi bi-info-circle-fill'></i> No students found for the selected batch.</div>";
							}
							else {
					?>	
						<div class="card shadow-sm border-0">
							<div class="card-header border-bottom pt-3 pb-2 d-flex justify-content-between align-items-center">
								<h6 class="mb-0 fw-bold"><i class="bi bi-people-fill me-2"></i>Student List</h6>
								<span class="badge bg-white text-dark rounded-pill"><?php echo mysqli_num_rows($run); ?> Students</span>
							</div>
							<div class="card-body">
								<div class="table-responsive">
									<table class="table table-hover align-middle mb-0 text-center">
										<thead class="table-light">
											<tr>
												<th class="py-3 text-secondary fw-semibold">SL No</th>
												<th class="py-3 text-secondary fw-semibold">Student ID</th>
												<th class="py-3 text-secondary fw-semibold">Name</th>
												<th class="py-3 text-secondary fw-semibold">Branch</th>
												<th class="py-3 text-secondary fw-semibold">Year</th>
											</tr>
										</thead>
										<tbody>
											<?php
												$Sl=1;
												while($row=mysqli_fetch_array($run)) {
											?>
											<tr>
												<td class="text-muted fw-bold"><?php echo $Sl++; ?></td>
												<td class="fw-semibold"><?php echo $row['Stud_ID']; ?></td>
												<td class="fw-bold text-dark text-start px-4"><?php echo $row['Stud_Name']; ?></td>
												<td><span class="badge bg-light text-dark border"><?php echo $row['Stud_Dept']; ?></span></td>
												<td class="text-muted fw-semibold"><?php echo $row['Stud_Year']; ?></td>
											</tr>
											<?php
												}
											?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					<?php
							}
						}
					?>
					</div>
				</div>
			</div>
		</div>
	</main>
	<?php include '../Common/footer.php'; ?>
</body>

</html>



            