 <!---------------- Session starts form here ----------------------->
 <?php  
	session_start();

	if (!$_SESSION["LoginAdmin"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../login/login.php"</script>';
	}

	require_once "../connection/connection.php";

?>
<!---------------- Session Ends form here ------------------------>
<?php  
	if (isset($_POST['Submit'])) {
		$Title = $_POST['Title'];
		$Topic = $_POST['Topic'];
		$Total_Questions = $_POST['Total_Questions'];
		$Year = $_POST['Year'];
		$Course = implode(",", $_POST['Course']);
		$Branch = implode(",", $_POST['Branch']);

		$query = "INSERT INTO `exam`(`Title`, `Topic`, `Total_Questions`, `Year`, `Course`, `Branch`) VALUES ('$Title', '$Topic', '$Total_Questions', '$Year', '$Course', '$Branch')";
		$run = mysqli_query($con, $query);
		if ($run) {
			echo "<script> confirm('Exam Added'); window.location.href = window.location.href; </script>";
 		}
 		else {
			echo "Error: " . mysqli_error($con);
 		}
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Exam</title> 
</head>
    <body>
        <?php include('../Common/header.php'); ?>
        <?php include('../Common/Admin-sidebar.php'); ?>
        
        <main role="main" class="mt-1 mb-3 px-md-4 page-content-index">
			<div class="sub-main">
                <div class="dashboard-header flex-wrap flex-md-no-wrap px-3 pt-2 pb-1 mb-3 text-white">
                    <div class="d-flex flex-row">
                        <div class="p-2"><h4>Add Exam</h4></div>
                    </div>
                </div>
				
				<div class="row">
					<div class="col-md-12 container-fluid">
						<form method="POST" enctype="multipart/form-data">
							<div class="row mt-3">
								<div class="col-md-4">
									<label>Exam Title</label>
									<input type="text" name="Title" class="form-control" required>
								</div>
								<div class="col-md-4">
									<label>Topic</label>
									<select class="form-select" name="Topic" required>
										<option>Select Topic</option>
										<option value="Aptitude">Aptitude</option>
										<option value="Logical">Logical</option>
										<option value="Technical">Technical</option>
									</select>
								</div>
								<div class="col-md-4">
									<label>No of Questions</label>
									<input type="number" name="Total_Questions" class="form-control" required>
								</div>
							</div>					
							<div class="row mt-3">
								<div class="col-md-4">
									<label>Batch</label>
									<input type="number" name="Year" class="form-control" required>
								</div>
								<div class="col">
									<?php
										$batches = ['B.Tech','M.Tech','MCA'];
										echo "<label class='my-4'>Select Course</label>\t";
										foreach ($batches as $batch) {
											echo "\t<div class='form-check form-check-inline'>
													<input class='form-check-input' type='checkbox' name='Course[]' value='$batch'>
													<label class='form-check-label' >$batch</label>
												  </div>\t";
										}
									?>
								</div>
								<div class="col">
									<?php
										$batches = ['CS', 'CS-AI',  'AI-DS', 'CS-CY', 'ME', 'CE', 'EEE', 'ECE', 'Computer Applications'];
										echo "<label class='my-4'>Select Branch</label>\t";
										foreach ($batches as $batch) {
											echo "\t<div class='form-check form-check-inline'>
													<input class='form-check-input' type='checkbox' name='Branch[]' value='$batch'>
													<label class='form-check-label' >$batch</label>
												  </div>\t";
										}
									?>
								</div>
							</div>						
							<div class="row mt-3">
								<div class="col-md-6">
									<input type="submit" name="Submit" value="Add Exam" class="btn btn-primary">
								</div>
							</div>
						</form>
					</div>
				</div>
				
				<div class="row">
					<div class="col-md-12 container-fluid">
						<section class="my-3">
							<table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
								<tr class="table-dark text-white">
									<th>Exam ID</th>
									<th>Title</th>
									<th>Domain</th>
									<th>No of Questions</th>
									<th>Total Time</th>
									<th>Total Marks</th>
									<th>Action</th>
								</tr>
								<?php
									$query="SELECT * from exam";
									$run=mysqli_query($con,$query);
									while($row=mysqli_fetch_array($run)) {
										$Exam_ID=$row['Exam_ID'];
								?>
								<tr>
									<td><?php echo $row['Exam_ID']; ?></td>
									<td><?php echo $row['Title']; ?></td>
									<td><?php echo $row['Topic']; ?></td>
									<td><?php echo $row['Total_Questions']; ?></td>
									<td><?php echo $row['Total_Questions']; ?></td>
									<td><?php echo $row['Total_Questions']; ?></td>
									<td width='200'>
										<a class="btn btn-success" href="add-questions.php?Exam_ID=<?php echo $row['Exam_ID']; ?>">Add Questions</a>
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

        <?php include('../Common/footer.php'); ?>

        <script>
            (() => {
                'use strict';
                const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(tooltipTriggerEl => {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                });
            })();
        </script>
    </body>
</html>
