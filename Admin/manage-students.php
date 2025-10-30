<?php  
session_start();
if (!isset($_SESSION['LoginAdmin'])) {
	echo "<script>alert('You are not authorized to access this page'); window.location.href='../index.php';</script>";
	exit;
}
require_once "../Connection/connection.php";
?>

<?php
if (isset($_POST['submit_csv'])) {
	$records = json_decode($_POST['json_data'], true);
	if ($records && is_array($records)) {
		foreach ($records as $record) {
			$Stud_ID = mysqli_real_escape_string($con, $record['Stud_ID']);
			$Stud_Name = mysqli_real_escape_string($con, $record['Stud_Name']);
			$Stud_DOB = mysqli_real_escape_string($con, $record['Stud_DOB']);
			$Stud_Gender = mysqli_real_escape_string($con, $record['Stud_Gender']);
			$Stud_Mob = mysqli_real_escape_string($con, $record['Stud_Mob']);
			$Stud_Email = mysqli_real_escape_string($con, $record['Stud_Email']);
			$Stud_Course = mysqli_real_escape_string($con, $record['Stud_Course']);
			$Stud_Branch = mysqli_real_escape_string($con, $record['Stud_Branch']);
			$Stud_Year = mysqli_real_escape_string($con, $record['Stud_Year']);

			$query1 = "INSERT INTO student (Stud_ID, Stud_Name, Stud_DOB, Stud_Gender, Stud_Mob, Stud_Email, Stud_Course, Stud_Branch, Stud_Year) VALUES ('$Stud_ID', '$Stud_Name', '$Stud_DOB', '$Stud_Gender', '$Stud_Mob', '$Stud_Email', '$Stud_Course', '$Stud_Branch', '$Stud_Year')";

			$query2 = "INSERT INTO login (User_ID, Password, Role, Status) VALUES ('$Stud_ID', 'Student123*', 'Student', 'Activate')";

			$run1 = mysqli_query($con, $query1);
			$run2 = mysqli_query($con, $query2);

			if (!$run1 || !$run2) {
				echo "<script>alert('Some records failed to insert!'); window.location='manage-students.php';</script>";
				exit;
			}
		}
		echo "<script>alert('All students added successfully!'); window.location='manage-students.php';</script>";
	}
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
			<div class="container">
				<section>
					<form class="row row-cols-lg-auto g-3 align-items-center" action="" method="post" enctype="multipart/form-data">
						<div class="col">
							<input type="file" class="form-control" name="csv_file" accept=".csv" required>
						</div>

						<div class="col">
							<input type="submit" class="btn btn-primary px-5" name="Add" value="Upload CSV">
						</div>
					</form>
					<p class="text-muted mt-2">* CSV should have columns: ID, Name, DOB, Gender, Mobile, Email, Course, Branch and Year</p>
				</section>

				<?php
				if (isset($_POST['Add'])) {
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
									'Stud_ID'     => $data[0],
									'Stud_Name'   => $data[1],
									'Stud_DOB'    => $data[2],
									'Stud_Gender' => $data[3],
									'Stud_Mob'    => $data[4],
									'Stud_Email'  => $data[5],
									'Stud_Course' => $data[6],
									'Stud_Branch' => $data[7],
									'Stud_Year'   => $data[8],
								];
							}
							fclose($handle);
						}
					}

					if (!empty($students)) {
				?>
						<section class="mt-3">
							<form method="POST">
								<input type="hidden" name="json_data" value='<?= json_encode($students, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
								<table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
									<tr class="table-dark text-white">
										<th>SL No</th>
										<th>Student ID</th>
										<th>Name</th>
										<th>Course</th>
										<th>Branch</th>
										<th>Year</th>
									</tr>
									<?php foreach ($students as $i => $student): ?>
										<tr>
											<td><?= $i + 1 ?></td>
											<td><?= htmlspecialchars($student['Stud_ID']) ?></td>
											<td><?= htmlspecialchars($student['Stud_Name']) ?></td>
											<td><?= htmlspecialchars($student['Stud_Course']) ?></td>
											<td><?= htmlspecialchars($student['Stud_Branch']) ?></td>
											<td><?= htmlspecialchars($student['Stud_Year']) ?></td>
										</tr>
									<?php endforeach; ?>
								</table>
								<div class="text-center mb-5">
									<input type="submit" name="submit_csv" value="Add Students" class="btn btn-success">
								</div>
							</form>
						</section>
				<?php
					} else {
						echo "<div class='alert alert-warning mt-3'>No valid student data found in CSV.</div>";
					}
				}
				?>
			</div>
		</div>
	</main>

	<?php include '../Common/footer.php'; ?>
</body>

</html>

