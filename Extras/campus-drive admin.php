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
		$D_Name=$_POST['D_Name'];
		$Role=$_POST['Role'];
		$Year=$_POST['Year'];
		$Course = implode(",", $_POST['Course']);
		$Branch = implode(",", $_POST['Branch']);
		$Marks_10th=$_POST['Marks_10th'];
		$Marks_12th=$_POST['Marks_12th'];
		$Marks_UG=$_POST['Marks_UG'];
		$CGPA=$_POST['CGPA'];
		$Backlogs=$_POST['Backlogs'];
		$D_Package=$_POST['D_Package'];
		$D_Date=$_POST['D_Date'];
		$C_ID=$_POST['C_ID'];

		$query="INSERT INTO `drive`(`D_Name`, `Role`, `Course`, `Branch`, `Year`, `Marks_10th`, `Marks_12th`, `Marks_UG`, `CGPA`, `Backlogs`,`D_Package`, `D_Date`, `C_ID`) VALUES ('$D_Name', '$Role', '$Course', '$Branch', '$Year', '$Marks_10th', '$Marks_12th', '$Marks_UG', '$CGPA', '$Backlogs', '$D_Package' , '$D_Date' , '$C_ID')";
		$run=mysqli_query($con, $query);
		if ($run) {
			echo "<script>alert('Success'); history.back();</script>";
 		}
 		else {
			echo $query;
 		}
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Campus Drive</title> 
</head>
    <body>
        <?php include('../Common/header.php'); ?>
        <?php include('../Common/admin-sidebar.php'); ?>
        
        <main role="main" class="mt-1 mb-3 px-md-4 page-content-index">
            <div class="sub-main">
                <div class="dashboard-header flex-wrap flex-md-no-wrap px-3 pt-2 pb-1 mb-3 text-white">
                    <div class="d-flex flex-row">
                        <div class="p-2"><h4>Campus Drive</h4></div>
                    </div>
                </div>
				<div class="row">
					<div class="col-md-12 container-fluid">
						<form method="POST" enctype="multipart/form-data">
							<div class="row mt-3">
								<div class="col-md-4">
									<label>Drive Name</label>
									<input type="text" name="D_Name" class="form-control" required>
								</div>
								<div class="col-md-4">
									<label>Company Name</label>
									<select class="form-select" name="C_ID" required>
										<option>Select Company Name</option>
										<?php
											$query="SELECT * from company";
											$run=mysqli_query($con,$query);
											while($row=mysqli_fetch_array($run)) {
												echo"<option value=".$row['C_ID'].">".$row['C_Name']."</option>";
											}
										?>
									</select>
								</div>
								<div class="col-md-4">
									<label>Role</label>
									<input type="text" name="Role" class="form-control" required>
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
								<div class="col-md-4">
									<label>10th Marks</label>
									<input type="number" name="Marks_10th" class="form-control">
								</div>
								<div class="col-md-4">
									<label>12th Marks</label>
									<input type="number" name="Marks_12th" class="form-control" id="">
								</div>
								<div class="col-md-4">
									<label>UG Marks</label>
									<input type="number" name="Marks_UG" class="form-control">
								</div>
							</div>
							<div class="row mt-3">
								<div class="col-md-6">
									<label>Current CGPA</label>
									<input type="number" name="CGPA" class="form-control">
								</div>
								<div class="col-md-6">
									<label>No of backlogs</label>
									<input type="number" name="Backlogs" class="form-control">
								</div>
							</div>
							<div class="row mt-3">
								<div class="col-md-6">
									<label>Select Package</label>
									<select class="form-select" name="D_Package">
										<option>Select Package</option>
										<option value="0">Low</option>
										<option value="1">Medium</option>
										<option value="2">High</option>
									</select>
								</div>
								<div class="col-md-6">
									<label>Last Date</label>
									<input type="date" name="D_Date" class="form-control">
								</div>
							</div>						
							<div class="row mt-3">
								<div class="col-md-6">
									<input type="submit" name="Submit" value="Add Drive" class="btn btn-primary">
									<input type="submit" name="View" value="View Eligible Students" class="btn btn-success">
								</div>
							</div>
						</form>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12 container-fluid">
						<?php
							if (isset($_POST['View'])) {
						?>	
								<section class="mt-3">
									<div class="d-flex justify-content-center">
										<table class="w-50 table table-bordered table-hover border-dark text-center" cellpadding="10">
											<thead>
												<tr class="table-dark text-white">
													<th class="w-25">Branch</th>
													<th class="w-25">Eligible Students</th>
												</tr>
											</thead>
											<tbody>
												<?php
													$Year=$_POST['Year'];
													$Course = implode(",", $_POST['Course']);
													$Branch = implode(",", $_POST['Branch']);
													$Marks_10th=$_POST['Marks_10th'];
													$Marks_12th=$_POST['Marks_12th'];
													$Marks_UG=$_POST['Marks_UG'];
													$CGPA=$_POST['CGPA'];
													$Backlogs=$_POST['Backlogs'];
													$D_Package=$_POST['D_Package'];
													$D_Date=$_POST['D_Date'];
												
													$query="SELECT s.Stud_Batch, COUNT(DISTINCT s.Stud_ID) AS Stud_Count 
															FROM drive d, student s 
															WHERE FIND_IN_SET(s.Stud_Course, '$Course') > 0 
															AND FIND_IN_SET(s.Stud_Batch, '$Branch') > 0 
															AND '$Year' = s.Stud_Year 
															AND '$Marks_10th' <= s.Marks_10th 
															AND '$Marks_12th' <= s.Marks_12th 
															AND (s.Marks_UG <= 0 OR '$Marks_UG' <= s.Marks_UG) 
															AND '$CGPA' <= s.CGPA 
															AND '$Backlogs' <= s.Stud_Backlogs 
															AND '$D_Package' <= s.Stud_Package 
															GROUP BY s.Stud_Batch";
												
													$run=mysqli_query($con,$query);
													while($row=mysqli_fetch_array($run)) {
												?>
														<tr>
															<td><?php echo $row['Stud_Batch']; ?></td>
															<td><?php echo $row['Stud_Count']; ?></td>
														</tr>
												<?php
													}
												?>
											</tbody>
										</table>
									</div>
								</section>

						<?php
							}
						?>
						<section class="my-3">
							<table class="w-100 table table-bordered table-hover border-dark text-center" cellpadding="10">
								<tr class="table-dark">
									<th>Drive ID</th>
									<th>Drive Name</th>
									<th>Comapany</th>
									<th>Branch</th>
									<th>Year</th>
									<th>Last Date</th>
									<th>10th</th>
									<th>12th</th>
									<th>UG</th>
									<th>CGPA</th>
									<th>Backlogs</th>
									<th>Pack</th>
									<th>Eligible Students</th>
									<th>Applied Students</th>
								</tr>
								<?php
									$query="SELECT 
												d.*, c.*,
												COUNT(DISTINCT s.Stud_ID) AS Stud_Count,
												COUNT(DISTINCT a.App_ID) AS App_Count
											FROM 
												drive d
											LEFT JOIN student s 
												ON FIND_IN_SET(s.Stud_Course, d.Course) > 0 
												AND FIND_IN_SET(s.Stud_Batch, d.Branch) > 0 
												AND d.Year = s.Stud_Year 
												AND d.Marks_10th <= s.Marks_10th 
												AND d.Marks_12th <= s.Marks_12th 
												AND (s.Marks_UG <= 0 OR d.Marks_UG <= s.Marks_UG) 
												AND d.CGPA <= s.CGPA 
												AND d.Backlogs <= s.Stud_Backlogs 
												AND d.D_Package <= s.Stud_Package
											LEFT JOIN application a 
												ON a.D_ID = d.D_ID
											LEFT JOIN company c ON
												c.C_ID = d.C_ID
											GROUP BY d.D_ID";
									$pack=array("0"=>"Low","1"=>"Medium","2"=>"High");
									$run=mysqli_query($con,$query);
									while($row=mysqli_fetch_array($run)) {
										$D_ID=$row['D_ID'];
										echo "<tr>";
										echo "<td>".$row['D_ID']."</td>";
										echo "<td>".$row['D_Name']."</td>";
										echo "<td>".$row['C_Name']."</td>";
										echo "<td>".$row['Branch']."</td>";
										echo "<td>".$row['Year']."</td>";
										echo "<td>".$row['D_Date']."</td>";
										echo "<td>".$row['Marks_10th']."</td>";
										echo "<td>".$row['Marks_12th']."</td>";
										echo "<td>".$row['Marks_UG']."</td>";
										echo "<td>".$row['CGPA']."</td>";
										echo "<td>".$row['Backlogs']."</td>";
										echo "<td>".$pack[$row['D_Package']]."</td>";
										echo "<td>".$row['Stud_Count']."</td>";
										echo "<td>".$row['App_Count']."</td>";
										echo "</tr>";
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
