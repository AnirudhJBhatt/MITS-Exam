<?php  
	session_start();
	if (!$_SESSION["LoginFaculty"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../Login/Login.php"</script>';
	}

	require_once "../Connection/connection.php";
	
	ini_set('display_errors', 1);
	error_reporting(E_ALL);

    $Fac_ID=$_SESSION['LoginFaculty'];

	// Fetch Faculty Details
	$query1 = "SELECT * FROM `faculty` WHERE `Fac_ID` = '$Fac_ID' ";
    $run1 = mysqli_query($con, $query1);
    $row1 = mysqli_fetch_array($run1);

	$Fac_Dept=$row1['Fac_Dept'];
	$Fac_Acad_Year = $row1['Acad_Year'];

	// Fetch Mapped Courses
	$query = "SELECT p.Prog_Name, c.Course_Name, c.Semester, c.Course_ID
			  FROM programmes p, courses c, course_mapping cm 
			  WHERE cm.Fac_ID='$Fac_ID' 
			  AND c.Course_ID=cm.Course_ID 
			  AND p.Prog_ID=c.Prog_ID";

	$result = mysqli_query($con, $query);
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
		<div class="row g-4">

			<?php 
			if(mysqli_num_rows($result) > 0){
				while($row = mysqli_fetch_assoc($result)){
			?>
					<div class="col-md-4 col-lg-3">
						<div class="card p-3">
							<div class="card-body text-center">
								<h5 class="card-title">
									<?php echo strtoupper($row['Course_Name']); ?>
								</h5>
								<p class="semester-text mb-0">
									S<?php echo $row['Semester']; ?> 
									<?php echo $row['Prog_Name']; ?>
								</p>
								<a href="manage-exams.php?course=<?php echo urlencode($row['Course_ID']); ?>" class="btn btn-primary mt-3">Manage Exams</a>
							</div>
						</div>
					</div>
			<?php 
				}
			}else{
				echo "<p class='text-muted'>No Subjects Assigned</p>";
			}
			?>

		</div>
	</main>

	<?php include '../Common/footer.php'; ?>
</body>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


</html>