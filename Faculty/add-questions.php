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
?>
<?php
	if (isset($_POST['Submit'])) {
		$Exam_Title = $_POST['Exam_Title'];
		$Batch = $_POST['Batch'];
		$Subject = $_POST['Subject'];
		$Exam_Type = $_POST['Exam_Type'];
		$Duration = $_POST['Duration'];

		$query = "INSERT INTO `exam`(`Exam_Title`, `Exam_Type`, `Subject`, `Batch`, `Total_Marks`, `Duration`)
		VALUES ('$Exam_Title', '$Exam_Type', '$Subject', '$Batch', '0', '$Duration')";

		$run = mysqli_query($con, $query);
		if ($run) {
			echo "<script> confirm('Exam Added'); window.location.href = window.location.href; </script>";
 		}
 		else {
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
									<input type="text" name="Exam_Title" class="form-control" required>
								</div>
								<div class="col-md-4">
									<label>Batch</label>
									<input type="text" name="Batch" class="form-control" required>
								</div>
								<div class="col-md-4">
									<label>Subject</label>
									<input type="text" name="Subject" class="form-control" required>
								</div>
							</div>	
							<div class="row mt-3">	
								<div class="col-md-4">
									<label>Type</label>
									<select class="form-select" name="Exam_Type" required>
										<option>Select Type</option>
										<option value="MCQ">MCQ</option>
										<option value="Match">Match the following</option>
										<option value="Fill">Fill in the blanks</option>
									</select>
								</div>							
								<div class="col-md-4">
									<label>Duration</label>
									<input type="number" name="Duration" class="form-control" required>
								</div>
								<div class="col-md-4">
									<label>No of Questions</label>
									<input type="number" name="" class="form-control" required>
								</div>
							</div>		
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
						<section class="mt-3">
							<table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
								<tr class="table-dark text-white">
									<th>SL No</th>
									<th>Exam Name</th>
									<th>Batch</th>
									<th>Subject</th>
									<th>Action</th>
								</tr>
								<?php
									$query="SELECT * FROM exam;";
									$run=mysqli_query($con,$query);
									while($row=mysqli_fetch_array($run)) {
										$Exam_ID=$row['Exam_ID'];
										$Sl=1;
								?>
								<tr>
									<td><?php echo $Sl++; ?></td>
									<td><?php echo $row['Exam_Title']; ?></td>
									<td><?php echo $row['Batch']; ?></td>
									<td><?php echo $row['Subject']; ?></td>
									<td width='200'>
										<a class="btn btn-info" href="add-questions.php?Exam_ID=<?php echo $row['Exam_ID']; ?>">Add Questions</a>
										<a class="btn btn-success" href="add-questions.php?Exam_ID=<?php echo $row['Exam_ID']; ?>">Publish Results</a>
									</td>
								</tr>
								<?php
									}
								?>
							</table>				
						</section>
					</div>
				</div>
			</div>
	</main>

	<?php include '../Common/footer.php'; ?>
</body>

</html>



            