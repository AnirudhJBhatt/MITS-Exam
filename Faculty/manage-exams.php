<?php  
	session_start();
	if (!$_SESSION["LoginFaculty"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../index.php"</script>';
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

	$Course_ID = isset($_GET['Course_ID']) ? $_GET['Course_ID'] : null;

	$course_query = "SELECT * FROM `courses` WHERE `Course_ID` = '$Course_ID'";
	$course_run = mysqli_query($con, $course_query);
	$course = mysqli_fetch_array($course_run);
	$Course_Name = $course['Course_Name'];
	$Course_Code = $course['Course_Code'];

?>
<!DOCTYPE html>
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
		<div class="container-fluid">
			<!-- Header -->
			<div class="dashboard-header mb-4 d-flex justify-content-between align-items-center border-bottom pb-3">
				<div>
					<h4 class="mb-0 fw-bold">Manage Exams</h4>
					<small class="text-muted"><?php echo htmlspecialchars($Course_Code . " - " . $Course_Name); ?></small>
				</div>
				<a href="view-courses.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold">
					<i class="bi bi-arrow-left me-1"></i> Back to Courses
				</a>
			</div>
            
			<div class="row g-4 mt-2">
				<!-- Create Exam Card -->
				<div class="col-md-4">
					<div class="card h-100 border-0 shadow-sm rounded-4 hover-elevate">
						<div class="card-body text-center p-5 d-flex flex-column align-items-center justify-content-center">
							<div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
								<i class="bi bi-journal-plus fs-1"></i>
							</div>
							<h5 class="fw-bold mb-3 text-dark">Exams</h5>
							<p class="text-muted small mb-4">Create, view, and manage examinations for this course.</p>
							<a href="create-newexam.php?Course_ID=<?php echo urlencode($Course_ID); ?>" class="btn btn-primary rounded-pill px-4 fw-semibold mt-auto w-100">
								Create Exam
							</a>
						</div>
					</div>
				</div>

				<!-- Question Bank Card -->
				<div class="col-md-4">
					<div class="card h-100 border-0 shadow-sm rounded-4 hover-elevate">
						<div class="card-body text-center p-5 d-flex flex-column align-items-center justify-content-center">
							<div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
								<i class="bi bi-file-earmark-text fs-1"></i>
							</div>
							<h5 class="fw-bold mb-3 text-dark">Question Bank</h5>
							<p class="text-muted small mb-4">Create and manage the repository of questions.</p>
							<a href="new_question-bank.php?Course_ID=<?php echo urlencode($Course_ID); ?>" class="btn btn-success rounded-pill px-4 fw-semibold mt-auto w-100">
								View Question Bank
							</a>
						</div>
					</div>
				</div>

				<!-- Results Card -->
				<div class="col-md-4">
					<div class="card h-100 border-0 shadow-sm rounded-4 hover-elevate">
						<div class="card-body text-center p-5 d-flex flex-column align-items-center justify-content-center">
							<div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
								<i class="bi bi-bar-chart-line fs-1"></i>
							</div>
							<h5 class="fw-bold mb-3 text-dark">View Results</h5>
							<p class="text-muted small mb-4">Analyze and export student performance and grades.</p>
							<a href="view-results-new.php?Course_ID=<?php echo urlencode($Course_ID); ?>" class="btn btn-warning text-white rounded-pill px-4 fw-semibold mt-auto w-100">
								View Results
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>
	<style>
		.hover-elevate {
			transition: transform 0.3s ease, box-shadow 0.3s ease;
		}
		.hover-elevate:hover {
			transform: translateY(-5px);
			box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
		}
	</style>

	<?php include '../Common/footer.php'; ?>
</body>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>



</html>