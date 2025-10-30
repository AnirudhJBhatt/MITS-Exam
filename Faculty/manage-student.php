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
			<h4 class="mb-0 fw-bold">Manage Students</h4>
		</div>
		    <div class="sub-main">
				<!-- <div class="row">
					<div class="col-md-12 container-fluid">
						<section class="mt-3">
							<table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
								<tr class="table-dark text-white">
									<th>SL No</th>
									<th>Exam</th>
									<th>Subject</th>
									<th>Action</th>
									<th>Marks</th>
								</tr>
								<?php
									$query="SELECT d.D_ID, d.D_Name, d.Marks_10th, d.Marks_12th, d.Marks_UG, d.CGPA, d.D_Date 
        							FROM drive d, student s WHERE FIND_IN_SET(s.Stud_Course, d.Course) > 0 
									AND FIND_IN_SET(s.Stud_Batch, d.Branch) > 0 
									AND d.Year = s.Stud_Year 
									AND d.Marks_10th <= s.Marks_10th 
									AND d.Marks_12th <= s.Marks_12th 
									AND (s.Marks_UG <= 0 OR d.Marks_UG <= s.Marks_UG) 
									AND d.CGPA <= s.CGPA AND d.Backlogs <= s.Stud_Backlogs 
									AND d.D_Package <= s.Stud_Package 
									AND s.Stud_ID='$Stud_ID'";
									$run=mysqli_query($con,$query);
									while($row=mysqli_fetch_array($run)) {
										$D_ID=$row['D_ID'];
								?>
								<tr>
									<td><?php echo $row['D_ID']; ?></td>
									<td><?php echo $row['D_Name']; ?></td>
									<td><?php echo $row['Marks_10th']; ?></td>
									<td><?php echo $row['Marks_12th']; ?></td>
									<td><?php echo $row['Marks_UG']; ?></td>
									<td><?php echo $row['CGPA']; ?></td>
									<td><?php echo $row['D_Date']; ?></td>
									<td width='200'>
										<button class="btn btn-info view-drive" data-id="<?php echo $row['D_ID']; ?>">View</button>
										<?php
											$isExpired = strtotime($row['D_Date']) < strtotime(date('Y-m-d'));
											if (in_array($D_ID, $applied_jobs)) {
												echo '<button class="btn btn-secondary" disabled>Applied</button>';
											} elseif ($isExpired) {
												echo '<button class="btn btn-danger" disabled>Expired</button>';
											} else {
												echo '<a class="btn btn-success" href="apply.php?D_ID=' . $row['D_ID'] . '">Apply</a>';
											}
										?>
									</td>
								</tr>
								<?php
									}
								?>
							</table>				
						</section>
					</div>
				</div> -->
			</div>
	</main>

	<?php include '../Common/footer.php'; ?>
</body>

</html>



            