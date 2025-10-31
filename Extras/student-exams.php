 <!---------------- Session starts form here ----------------------->
 <?php  
	session_start();
	if (!$_SESSION["LoginStudent"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../login/login.php"</script>';
	}
	require_once "../connection/connection.php";
	$Stud_ID=$_SESSION['LoginStudent'];
	$applied_jobs = array();
	$query = "SELECT D_ID FROM application WHERE S_ID = '$Stud_ID'";
	$run = mysqli_query($con, $query);
	while ($row = mysqli_fetch_assoc($run)) {
		$applied_jobs[] = $row['D_ID'];
	}
?>
<!---------------- Session Ends form here ------------------------>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student - Exams</title> 
</head>
    <body>
        <?php include('../Common/header.php'); ?>
        <?php include('../Common/student-sidebar.php'); ?>
        
        <main role="main" class="mt-1 mb-3 px-md-4 page-content-index">
            <div class="sub-main">
				<div class="dashboard-header flex-wrap flex-md-no-wrap px-3 pt-2 pb-1 mb-3 text-white">
					<div class="d-flex flex-row">
						<div class="p-2"><h4>Training Exams</h4></div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12 container-fluid">
						<section class="mt-3">
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
									$query="SELECT e.* from exam e, student s 
									WHERE FIND_IN_SET(s.Stud_Course, e.Course) > 0 
									AND FIND_IN_SET(s.Stud_Batch, e.Branch) > 0
									AND e.Year = s.Stud_Year 
									AND s.Stud_ID='$Stud_ID'";
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
										<a class="btn btn-success" href="attempt_exam.php?Exam_ID=<?php echo $row['Exam_ID']; ?>">Attempt</a>
										<!-- <?php if (in_array($D_ID, $applied_jobs)) { ?>
											<button class="btn btn-secondary" disabled>Applied</button>
										<?php 
										} else { ?>
											<a class="btn btn-success" href="attempt_exam.php?Exam_ID=<?php echo $row['Exam_ID']; ?>">Attempt</a>
										<?php	
										} ?> -->
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
