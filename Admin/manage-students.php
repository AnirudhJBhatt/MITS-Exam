<?php  
	session_start();
	if (!isset($_SESSION['LoginAdmin'])) {
		echo "<script>alert('You are not authorized to access this page'); window.location.href='../index.php';</script>";
		exit;
	}
	require_once "../Connection/connection.php";

	function selected($field, $value) {
        return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
    }
?>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin - Manage Students</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
	<?php include '../Common/header.php'; ?>
	<?php include '../Common/admin-sidebar.php'; ?>

	<main>
		<div class="dashboard-header">
			<h4 class="mb-0 fw-bold">Manage Students</h4>
		</div>
		<div class="sub-main">
			<form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
				<div class="col-md-4">
					<label for="Stud_Batch" class="form-label">Batch</label>
					<select class="form-select" name="Stud_Batch" id="Stud_Batch">
						<option>Select Batch</option>
						<?php
							$query = "SELECT DISTINCT Stud_Year FROM student";
							$run = mysqli_query($con, $query);
							while ($row = mysqli_fetch_array($run)) {
								echo "<option value='" . $row['Stud_Year'] . "' " . selected('Stud_Batch', $row['Stud_Year']) . ">" . $row['Stud_Year'] . "</option>";
							}
						?>
					</select>
				</div>
				<div class="col-md-4">
					<label for="Stud_Dept" class="form-label">Department</label>
					<select class="form-select" name="Stud_Dept">
						<option>Select Department</option>
						<?php
							$query = "SELECT * From department";
							$run = mysqli_query($con, $query);
							while ($row = mysqli_fetch_array($run)) {
								echo "<option value='" . $row['Dept_ID'] . "' " . selected('Stud_Dept', $row['Dept_ID']) . ">" . $row['Dept_Name'] . "</option>";
							}
						?>
					</select>
				</div>
				<div class="col-md-4">
					<input type="submit" name="Submit" value="View" class="btn btn-success">
				</div>
			</form>
			<?php	
				if (isset($_POST['Submit'])) {
				$Stud_Batch = $_POST['Stud_Batch'];
				$Stud_Dept = $_POST['Stud_Dept'];
				$query = "SELECT * FROM student WHERE Stud_Dept='$Stud_Dept' AND Stud_Year='$Stud_Batch' ORDER BY Stud_Name ASC;";
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
	</main>

	<?php include '../Common/footer.php'; ?>
</body>

</html>