 <!---------------- Session starts form here ----------------------->
 <?php  
	session_start();

	if (!$_SESSION["LoginFaculty"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../login/login.php"</script>';
	}

	require_once "../connection/connection.php";

    $Fac_ID=$_SESSION['LoginFaculty'];
	$query = "SELECT * FROM `faculty` WHERE `Fac_ID` = '$Fac_ID' ";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_array($run);
	$Fac_Dept=$row['Fac_Dept'];

?>
<!---------------- Session Ends form here ------------------------>
<?php
	if (isset($_POST['submit_csv'])) {
		$records = $_POST['records'];
		foreach ($records as $record) {
			$Stud_ID = mysqli_real_escape_string($con, $record['Stud_ID']);
			$Stud_Name = mysqli_real_escape_string($con, $record['Stud_Name']);
			$Stud_Course = mysqli_real_escape_string($con, $record['Stud_Course']);
			$Stud_Batch = mysqli_real_escape_string($con, $record['Stud_Batch']);
			$Stud_Year = mysqli_real_escape_string($con, $record['Stud_Year']);

			$query1= "INSERT INTO student (`Stud_ID`, `Stud_Name`,`Stud_Course`, `Stud_Batch`,`Stud_Year`) VALUES ('$Stud_ID', '$Stud_Name','$Stud_Course','$Stud_Batch', '$Stud_Year')";
			$query2="INSERT INTO `login`(`user_id`, `Password`, `Role`, `account`) VALUES ('$Stud_ID','Student123*','Student','Activate')";
			
			$run1=mysqli_query($con, $query1);
			$run2=mysqli_query($con, $query2);
		}

		if ($run1 && $run2) {
			echo "<script>alert('Success'); window.location='add-student.php';</script>";
		} else {
			echo "<script>alert('Failed'); window.location = 'add-student.php';</script>";
		}
	}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty - Add Student</title> 
</head>
    <body>
        <?php include('../Common/header.php'); ?>
        <?php include('../Common/faculty-sidebar.php'); ?>
        
        <main role="main" class="mt-1 mb-3 px-md-4 page-content-index">
			<div class="sub-main">
                <div class="dashboard-header flex-wrap flex-md-no-wrap px-3 pt-2 pb-1 mb-3 text-white">
                    <div class="d-flex flex-row">
                        <div class="p-2"><h4>Add Batch</h4></div>
                    </div>
                </div>
				<div class="container">
					<section>			
						<form class="row row-cols-lg-auto g-3 align-items-center" action="" method="post" enctype="multipart/form-data">
							<div class="col-12">
								<select class="form-select" name="Stud_Year" required>
									<option>Select Year</option>
									<?php
									for ($i = 2020; $i <= 2030; $i++) {
										$selected = (isset($_POST['Stud_Year']) && $_POST['Stud_Year'] == $i) ? 'selected' : '';
										echo "<option value='$i' $selected>$i</option>";
									}
									?>
								</select>
							</div>
							<div class="col-12">
								<select class="form-select" name="Stud_Course" required>
									<option>Select Course</option>
									<?php
									$courses = ["B.Tech", "M.Tech", "MCA"];
									foreach ($courses as $course) {
										$selected = (isset($_POST['Stud_Course']) && $_POST['Stud_Course'] == $course) ? 'selected' : '';
										echo "<option value='$course' $selected>$course</option>";
									}
									?>
								</select>
							</div>
							<div class="col-12">
								<input type="file" class="form-control" name="csv_file" accept=".csv" required>
							</div>
							<div class="col-12">
								<input type="submit" class="btn btn-primary px-5" name="Add" value="Add">
							</div>
						</form>
						<p class="text-muted mt-2">* CSV should have columns: Stud_ID, Stud_Name, Stud_Course, Stud_Batch, Stud_Year</p>
					</section>
					<?php 
						if(isset($_POST['Add'])) {
							$Stud_Year = $_POST['Stud_Year'];
							$Stud_Course = $_POST['Stud_Course'];
							$Fac_Dept = $row['Fac_Dept']; // already retrieved in your session block
							$students = [];

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
											'Stud_ID' => $data[0],
											'Stud_Name' => $data[1],
											'Stud_Course' => $Stud_Course,
											'Stud_Batch' => $Fac_Dept,
											'Stud_Year' => $Stud_Year
										];
									}
									fclose($handle);
								}
							}
					?>
							<section class="mt-3">
								<form method="POST">
									<table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
										<tr class="table-dark text-white">
											<th>Roll No</th>
											<th>Student ID</th>
											<th>Name</th>
											<th>Course</th>
											<th>Department</th>
											<th>Year</th>
										</tr>
										<?php foreach ($students as $i => $student): ?>
											<tr>
												<td><?= $i + 1 ?></td>
												<td><input type="text" class="form-control" name="records[<?= $i ?>][Stud_ID]" value="<?= $student['Stud_ID'] ?>" readonly></td>
												<td><input type="text" class="form-control" name="records[<?= $i ?>][Stud_Name]" value="<?= $student['Stud_Name'] ?>" readonly></td>
												<td><input type="text" class="form-control" name="records[<?= $i ?>][Stud_Course]" value="<?= $student['Stud_Course'] ?>" readonly></td>
												<td><input type="text" class="form-control" name="records[<?= $i ?>][Stud_Batch]" value="<?= $student['Stud_Batch'] ?>" readonly></td>
												<td><input type="text" class="form-control" name="records[<?= $i ?>][Stud_Year]" value="<?= $student['Stud_Year'] ?>" readonly></td>
											</tr>
										<?php endforeach; ?>
									</table>
									<div class="text-center mb-5">
										<input type="submit" name="submit_csv" value="Add Students" class="btn btn-success">				
									</div>
								</form>
							</section>
						<?php				
							}
						?>
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
