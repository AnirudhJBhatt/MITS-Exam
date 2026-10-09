<?php  
	session_start();
    if (!$_SESSION["LoginDeptAdmin"]) {        
        echo "<script>alert('You Are Not Authorize Person For This link'); window.location.href='../index.php';</script>";
		exit;
    }

	require_once "../Connection/connection.php";

    $Dept_ID=$_SESSION['LoginDeptAdmin'];
	$query = "SELECT * FROM `department` WHERE `Dept_ID` = '$Dept_ID' ";

	function selected($field, $value) {
		return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
	}
?>

<?php

if (isset($_POST['submit_csv'])) {

	$records = json_decode($_POST['json_data'], true);
	echo $_POST['json_data'];


if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_last_error_msg();
}
	if ($records && is_array($records)) {
		foreach ($records as $record) {
			$Stud_ID = mysqli_real_escape_string($con, $record['Stud_ID']);
			$Stud_Name = mysqli_real_escape_string($con, $record['Stud_Name']);
			$Stud_Email = mysqli_real_escape_string($con, $record['Stud_Email']);
			$Stud_Prog = mysqli_real_escape_string($con, $record['Stud_Prog']);
			$Stud_Branch = mysqli_real_escape_string($con, $record['Stud_Branch']);
			$Stud_Dept = mysqli_real_escape_string($con, $Dept_ID);
			$Stud_Year = mysqli_real_escape_string($con, $record['Stud_Year']);

			$query1 = "INSERT INTO student (Stud_ID, Stud_Name, Stud_Email, Stud_Prog, Stud_Branch, Stud_Dept, Stud_Year) 
			VALUES ('$Stud_ID', '$Stud_Name', '$Stud_Email', '$Stud_Prog', '$Stud_Branch', '$Stud_Dept', '$Stud_Year')";

			$query2 = "INSERT INTO login (ID, User_ID, Password, Role, Status) VALUES ('$Stud_ID', '$Stud_Email', 'Student123*', 'Student', 'Activate')";

			$run1 = mysqli_query($con, $query1);
			$run2 = mysqli_query($con, $query2);

			if (!$run1 || !$run2) {
				echo "<script>alert('Some records failed to insert!'); window.location='manage-student.php';</script>";
				exit;
				// echo mysqli_error($con);
				// echo $query1;
				// echo $query2;
				
			}
		}
		echo "<script>alert('All students added successfully!'); window.location = 'manage-student.php';</script>";
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Dept Admin - Manage Students</title>
    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../Common/style.css">
</head>

<body>
	<?php include '../Common/header.php'; ?>
	<?php include '../Common/deptadmin-sidebar.php'; ?>

	<main>
        <div class="container-fluid py-4">
            <div class="dashboard-header d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <h4 class="mb-1">Manage Students</h4>
                    <p class="text-muted mb-0">Upload and manage student enrollments for your department.</p>
                </div>
            </div>

			<div class="card shadow-sm border-0 rounded-4 mb-4 hover-elevate">
				<div class="card-header bg-white border-bottom-0 py-3 px-4">
					<h6 class="text-uppercase fw-bold text-primary mb-0"><i class="ti ti-file-upload me-2"></i>Upload Students (CSV)</h6>
				</div>
				<div class="card-body px-4 pb-4">	
					<div class="row">
						<div class="col-md-12">
							<form method="POST" enctype="multipart/form-data">
								<div class="row g-3 align-items-center mt-1">
									<div class="col-md-4">
                                        <label class="form-label small fw-bold text-secondary mb-1">Select Programme</label>
										<select name="Stud_Branch" class="form-select shadow-none border-secondary-subtle" required>
											<option value="">-- Select Programme --</option>
											<?php
												$pgquery ="SELECT * FROM programmes WHERE Dept_ID='$Dept_ID' ORDER BY Prog_Name DESC";
												$pgrun = mysqli_query($con, $pgquery);
												while ($pgrow = mysqli_fetch_array($pgrun)) {
													echo "<option value='" . $pgrow['Prog_ID'] . "'>" . $pgrow['Prog_Name'] . "</option>";
												}
											?>                        
										</select>
									</div>
									<div class="col-md-5">
                                        <label class="form-label small fw-bold text-secondary mb-1">Upload CSV</label>
										<input type="file" class="form-control shadow-none border-secondary-subtle" name="csv_file" accept=".csv" required>
									</div>
									<div class="col-md-3 mt-4 pt-2">
										<button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold w-100" name="Add"><i class="ti ti-upload me-1"></i>Upload CSV</button>
									</div>
								</div>
                                <div class="mt-3">
                                    <a href="../Templates/Student Template.csv" class="btn btn-sm btn-outline-secondary rounded-pill px-3" download><i class="ti ti-download me-1"></i>Download CSV Template</a>
                                </div>
							</form>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12 container-fluid">
						<?php
							if (isset($_POST['Add'])) {
								$students = [];
								$selectedProg = $_POST['Stud_Branch'];

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
												'Stud_Email'  => $data[2],
												'Stud_Prog' => $data[3],
												'Stud_Branch' => $selectedProg,
												'Stud_Year'   => $data[4],
											];
										}
										fclose($handle);
									}
								}

								if (!empty($students)) {
							?>
										<form method="POST">
											<input type="hidden" name="json_data" value='<?= json_encode($students, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
											<div class="table-responsive rounded-3 border mt-4">
                                                <table class="table table-hover align-middle mb-0 text-center">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th class="py-3 text-secondary fw-semibold">SL No</th>
                                                            <th class="py-3 text-secondary fw-semibold">Student ID</th>
                                                            <th class="py-3 text-secondary fw-semibold">Name</th>
                                                            <th class="py-3 text-secondary fw-semibold">Course</th>
                                                            <th class="py-3 text-secondary fw-semibold">Year</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($students as $i => $student): ?>
                                                            <tr>
                                                                <td class="text-muted fw-bold"><?= $i + 1 ?></td>
                                                                <td class="fw-semibold text-dark"><?= htmlspecialchars($student['Stud_ID']) ?></td>
                                                                <td><?= htmlspecialchars($student['Stud_Name']) ?></td>
                                                                <td><span class="badge bg-primary bg-opacity-10 text-primary rounded-pill"><?= htmlspecialchars($student['Stud_Prog']) ?></span></td>
                                                                <td><?= htmlspecialchars($student['Stud_Year']) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
											<div class="text-end mt-4 mb-2">
												<button type="submit" name="submit_csv" class="btn btn-success rounded-pill px-4 fw-bold shadow"><i class="ti ti-check me-2"></i>Confirm & Add Students</button>
											</div>
										</form>
							<?php
								} else {
									echo "<div class='alert alert-warning mt-3'>No valid student data found in CSV.</div>";
								}
							}
							?>
						</div>
					</div>
				</div>
			</div>
			<div class="card shadow-sm border-0 rounded-4 mb-4 hover-elevate">
				<div class="card-header bg-white border-bottom-0 py-3 px-4">
					<h6 class="text-uppercase fw-bold text-success mb-0"><i class="ti ti-users me-2"></i>Existing Students</h6>
				</div>
				<div class="card-body px-4 pb-4">	
					<div class="row">
						<div class="col-md-12">
							<form method="POST" class="bg-light p-3 rounded-4 mb-4">
								<div class="row g-3 align-items-end">
									<div class="col-md-4">
										<label class="form-label small fw-bold text-secondary mb-1">Select Programme</label>
										<select name="Prog_ID" class="form-select shadow-none border-secondary-subtle" required>
										<option value="">-- All Programmes --</option>
											<?php
												$pgquery ="SELECT * FROM programmes WHERE Dept_ID='$Dept_ID' ORDER BY Prog_Name DESC";
												$pgrun = mysqli_query($con, $pgquery);
												while ($pgrow = mysqli_fetch_array($pgrun)) {
													echo "<option value='" . $pgrow['Prog_ID'] . "' " . selected('Prog_ID', $pgrow['Prog_ID']) . ">" . $pgrow['Prog_Name'] . "</option>";
												}
											?>                        
										</select>
									</div>
									<div class="col-md-4">
										<label class="form-label small fw-bold text-secondary mb-1">Select Batch</label>
										<select class="form-select shadow-none border-secondary-subtle" name="Stud_Year" required>
											<option value="">-- All Batches --</option>
											<?php
												$bquery = "SELECT DISTINCT Stud_Year FROM student WHERE Stud_Dept='$Dept_ID' ORDER BY Stud_Year DESC";
												$brun = mysqli_query($con, $bquery);
												while ($brow = mysqli_fetch_array($brun)) {
													echo "<option value='" . $brow['Stud_Year'] . "' " . selected('Stud_Year', $brow['Stud_Year']) . ">" . $brow['Stud_Year'] . "</option>";
												}
											?>
										</select>
									</div>
									<div class="col-md-2">
										<button type="submit" name="Submit" class="btn btn-success rounded-pill fw-bold w-100 shadow-sm"><i class="ti ti-search me-1"></i>Search</button>
									</div>
								</div>			
							</form>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
						<?php	
							if (isset($_POST['Submit'])) {
								$Prog_ID = $_POST['Prog_ID'];
								$Stud_Year = $_POST['Stud_Year'];
								$query = "SELECT * FROM student s, programmes p WHERE Stud_Dept='$Dept_ID' AND Stud_Year='$Stud_Year' AND s.Stud_Branch = p.Prog_ID AND s.Stud_Branch='$Prog_ID' ORDER BY Stud_Name ASC";
								// echo $query;
								$run = mysqli_query($con, $query);
								if (mysqli_num_rows($run) == 0) {
									echo "<p class='mt-3 text-danger text-center'><i class='ti ti-mood-empty fs-3 d-block mb-2'></i>No students found for the selected batch.</p>";
								}
								else {
						?>	
							<section class="mt-3">
                                <div class="table-responsive border rounded-4 mt-2">
                                    <table class="table table-hover align-middle mb-0 text-center">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="py-3 text-secondary fw-semibold">SL No</th>
                                                <th class="py-3 text-secondary fw-semibold">Student ID</th>
                                                <th class="py-3 text-secondary fw-semibold">Name</th>
                                                <th class="py-3 text-secondary fw-semibold">Branch</th>
                                                <th class="py-3 text-secondary fw-semibold">Batch</th>
                                                <th class="py-3 text-secondary fw-semibold">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                            $Sl=1;
                                            while($row=mysqli_fetch_array($run)) {
                                        ?>
                                        <tr>
                                            <td class="text-muted fw-bold"><?php echo $Sl++; ?></td>
                                            <td class="fw-semibold text-dark"><?php echo $row['Stud_ID']; ?></td>
                                            <td><?php echo $row['Stud_Name']; ?></td>
                                            <td><span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2"><?php echo $row['Prog_Name']; ?></span></td>
                                            <td><?php echo $row['Stud_Year']; ?></td>
                                            <td>
                                                <a href="edit-student.php?Stud_ID=<?php echo $row['Stud_ID']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold"><i class="ti ti-edit"></i></a>
                                                <a href="view-student.php?Stud_ID=<?php echo $row['Stud_ID']; ?>" class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-semibold" title="Reset Password"><i class="ti ti-rotate-clockwise"></i></a>
                                                <a href="../Admin/delete.php?Stud_ID=<?php echo $row['Stud_ID']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-semibold" onclick="return confirm('Are you sure you want to delete this student?');"><i class="ti ti-trash"></i></a>
                                            </td>
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
							}
						?>
						</div>
					</div>
				</div>
			</div>
        </div>
	</main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<?php include '../Common/footer.php'; ?>
</body>

</html>