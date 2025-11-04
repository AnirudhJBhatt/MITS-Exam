<?php  
	session_start();
	if (!$_SESSION["LoginFaculty"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location = "../Login/Login.php"</script>';
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
	<title>Admin - Manage Students</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
	<?php include '../Common/header.php'; ?>
	<?php include '../Common/faculty-sidebar.php'; ?>

	<main>
		<div class="dashboard-header">
			<h4 class="mb-0 fw-bold">Question Bank</h4>
		</div>

		<div class="sub-main">
			<div class="row">
				<div class="col-md-12 container-fluid">
					<form method="POST" enctype="multipart/form-data">
						<div class="row mt-3">
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
							<div class="col-md-4">
								<label>Subject</label>
								<input type="text" name="Subject" class="form-control" required>
							</div>
							<div class="col-md-4">
								<label>File</label>
								<input type="file" class="form-control" name="csv_file" accept=".csv" required>
							</div>
						</div>
						<div class="row mt-3">
							<div class="col-md-6">
								<input type="submit" name="Submit" value="Add Questions" class="btn btn-success">
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</main>

	<?php include '../Common/footer.php'; ?>
</body>

</html>