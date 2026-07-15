<?php  
	session_start();
    if (!$_SESSION["LoginDeptAdmin"]) {        
        echo "<script>alert('You Are Not Authorize Person For This link'); window.location.href='../index.php';</script>";
		exit;
    }

	require_once "../Connection/connection.php";

    $Dept_ID=$_SESSION['LoginDeptAdmin'];
	$query = "SELECT * FROM `department` WHERE `Dept_ID` = '$Dept_ID' ";

	function selected($field, $value) {
		return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
	}
?>

<?php

if (isset($_POST['submit_csv'])) {

	$records = json_decode($_POST['json_data'], true);
	echo $_POST['json_data'];


if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_last_error_msg();
}
	if ($records && is_array($records)) {
		foreach ($records as $record) {
			$Stud_ID = mysqli_real_escape_string($con, $record['Stud_ID']);
			$Stud_Name = mysqli_real_escape_string($con, $record['Stud_Name']);
			$Stud_Email = mysqli_real_escape_string($con, $record['Stud_Email']);
			$Stud_Prog = mysqli_real_escape_string($con, $record['Stud_Prog']);
			$Stud_Branch = mysqli_real_escape_string($con, $record['Stud_Branch']);
			$Stud_Dept = mysqli_real_escape_string($con, $Dept_ID);
			$Stud_Year = mysqli_real_escape_string($con, $record['Stud_Year']);

			$query1 = "INSERT INTO student (Stud_ID, Stud_Name, Stud_Email, Stud_Prog, Stud_Branch, Stud_Dept, Stud_Year) 
			VALUES ('$Stud_ID', '$Stud_Name', '$Stud_Email', '$Stud_Prog', '$Stud_Branch', '$Stud_Dept', '$Stud_Year')";

			$query2 = "INSERT INTO login (ID, User_ID, Password, Role, Status) VALUES ('$Stud_ID', '$Stud_Email', 'Student123*', 'Student', 'Activate')";

			$run1 = mysqli_query($con, $query1);
			$run2 = mysqli_query($con, $query2);

			if (!$run1 || !$run2) {
				echo "<script>alert('Some records failed to insert!'); window.location='manage-student.php';</script>";
				exit;
				// echo mysqli_error($con);
				// echo $query1;
				// echo $query2;
				
			}
		}
		echo "<script>alert('All students added successfully!'); window.location = 'manage-student.php';</script>";
	}
}
?>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin - Manage Students</title>
</head>

<body>
	<?php include '../Common/header.php'; ?>
	<?php include '../Common/deptadmin-sidebar.php'; ?>

	<main>
		<div class="dashboard-header">
			<h4 class="mb-0 fw-bold">Manage Students</h4>
		</div>
			<div class="card shadow-sm mb-4">
				<div class="card-header text-white fw-semibold" style="background-color: #D1202D;">
					Upload Students (CSV)
				</div>
				<div class="card-body">	
					<div class="row">
						<div class="col-md-12 container-fluid">
							<form method="POST" enctype="multipart/form-data">
								<div class="row mt-3">
									<div class="col-md-4">
										<select name="Stud_Branch" class="form-control">
											<option value="">Select Programme</option>
											<?php
												$pgquery ="SELECT * FROM programmes WHERE Dept_ID='$Dept_ID' ORDER BY Prog_Name DESC";
												$pgrun = mysqli_query($con, $pgquery);
												while ($pgrow = mysqli_fetch_array($pgrun)) {
													echo "<option value='" . $pgrow['Prog_ID'] . "'>" . $pgrow['Prog_Name'] . "</option>";
												}
											?>                        
										</select>
									</div>
									<div class="col-md-4">
										<input type="file" class="form-control" name="csv_file" accept=".csv" required>
									</div>
									<div class="col-md-4">
										<input type="submit" class="btn btn-primary" name="Add" value="Upload CSV">
									</div>
								</div>
								<p class="text-muted mt-2">Click here to download template <a href="../Templates/Student Template.csv" download>Download Template</a></p>
							</form>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12 container-fluid">
						<?php
							if (isset($_POST['Add'])) {
								$students = [];
								$selectedProg = $_POST['Stud_Branch'];

								if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
									$file = $_FILES['csv_file']['tmp_name'];
									if (($handle = fopen($file, "r")) !== FALSE) {
										$isHeader = true;
										while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
											if ($isHeader) {
												$isHeader = false;
												continue;
											}
											$students[] = [
												'Stud_ID'     => $data[0],
												'Stud_Name'   => $data[1],
												'Stud_Email'  => $data[2],
												'Stud_Prog' => $data[3],
												'Stud_Branch' => $selectedProg,
												'Stud_Year'   => $data[4],
											];
										}
										fclose($handle);
									}
								}

								if (!empty($students)) {
							?>
										<form method="POST">
											<input type="hidden" name="json_data" value='<?= json_encode($students, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
											<table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
												<tr class="table-dark text-white">
													<th>SL No</th>
													<th>Student ID</th>
													<th>Name</th>
													<th>Course</th>
													<th>Year</th>
												</tr>
												<?php foreach ($students as $i => $student): ?>
													<tr>
														<td><?= $i + 1 ?></td>
														<td><?= htmlspecialchars($student['Stud_ID']) ?></td>
														<td><?= htmlspecialchars($student['Stud_Name']) ?></td>
														<td><?= htmlspecialchars($student['Stud_Prog']) ?></td>
														<td><?= htmlspecialchars($student['Stud_Year']) ?></td>
													</tr>
												<?php endforeach; ?>
											</table>
											<div class="text-center mb-5">
												<input type="submit" name="submit_csv" value="Add Students" class="btn btn-success">
											</div>
										</form>
							<?php
								} else {
									echo "<div class='alert alert-warning mt-3'>No valid student data found in CSV.</div>";
								}
							}
							?>
						</div>
					</div>
				</div>
			</div>
			<div class="card shadow-sm mb-4">
				<div class="card-header text-white fw-semibold" style="background-color: #D1202D;">
					Existing Students
				</div>
				<div class="card-body">	
					<div class="row">
						<div class="col-md-12 container-fluid">
							<form method="POST" enctype="multipart/form-data">
								<div class="row mt-3 align-items-end">
									<div class="col-md-4">
										<label for="Stud_Branch">Select Programme</label>
										<select name="Stud_Branch" class="form-control">
										<option value="">Select Programme</option>
											<?php
												$pgquery ="SELECT * FROM programmes WHERE Dept_ID='$Dept_ID' ORDER BY Prog_Name DESC";
												$pgrun = mysqli_query($con, $pgquery);
												while ($pgrow = mysqli_fetch_array($pgrun)) {
													echo "<option value='" . $pgrow['Prog_ID'] . "' " . selected('Stud_Branch', $pgrow['Prog_ID']) . ">" . $pgrow['Prog_Name'] . "</option>";
												}
											?>                        
										</select>
									</div>
									<div class="col-md-4">
										<label for="Stud_Batch">Select Batch</label>
										<select class="form-select" name="Stud_Batch">
											<option>Select Batch</option>
											<?php
												$bquery = "SELECT DISTINCT Stud_Year FROM student WHERE Stud_Dept='$Dept_ID' ORDER BY Stud_Year DESC";
												$brun = mysqli_query($con, $bquery);
												while ($brow = mysqli_fetch_array($brun)) {
													echo "<option value='" . $brow['Stud_Year'] . "' " . selected('Stud_Batch', $brow['Stud_Year']) . ">" . $brow['Stud_Year'] . "</option>";
												}
											?>
										</select>
									</div>
									<div class="col-md-4">
										<input type="submit" name="Submit" value="View" class="btn btn-success">
									</div>
								</div>			
							</form>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12 container-fluid">
						<?php	
							if (isset($_POST['Submit'])) {
								$Stud_Branch = $_POST['Stud_Branch'];
								$Stud_Batch = $_POST['Stud_Batch'];
								$query = "SELECT * FROM student s, programmes p WHERE Stud_Dept='$Dept_ID' AND Stud_Year='$Stud_Batch' AND s.Stud_Branch = p.Prog_ID AND s.Stud_Branch='$Stud_Branch' ORDER BY Stud_Name ASC";
								// echo $query;
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
										<th>Batch</th>
										<th>Action</th>
									</tr>
									<?php
										$Sl=1;
										while($row=mysqli_fetch_array($run)) {
									?>
									<tr>
										<td><?php echo $Sl++; ?></td>
										<td><?php echo $row['Stud_ID']; ?></td>
										<td><?php echo $row['Stud_Name']; ?></td>
										<td><?php echo $row['Prog_Name']; ?></td>
										<td><?php echo $row['Stud_Year']; ?></td>
										<td>
											<a href="edit-student.php?Stud_ID=<?php echo $row['Stud_ID']; ?>" class="btn btn-sm btn-primary">Edit</a>
											<a href="view-student.php?Stud_ID=<?php echo $row['Stud_ID']; ?>" class="btn btn-sm btn-warning">Reset</a>
											<a href="../Admin/delete.php?Stud_ID=<?php echo $row['Stud_ID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
										</td>
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
			</div>
	</main>
	<?php include '../Common/footer.php'; ?>
</body>

</html>



            