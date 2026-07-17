<?php  
	session_start();
    if (!$_SESSION["LoginAdmin"]) {        
        echo "<script>alert('You Are Not Authorize Person For This link'); window.location.href='../index.php';</script>";
		exit;
    }

	require_once "../Connection/connection.php";

	function selected($field, $value) {
		return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
	}
?>

<?php

if (isset($_POST['submit_csv'])) {
	$records = json_decode($_POST['json_data'], true);
	if ($records && is_array($records)) {
		foreach ($records as $record) {
			$Fac_ID = mysqli_real_escape_string($con, $record['Fac_ID']);
			$Fac_Name = mysqli_real_escape_string($con, $record['Fac_Name']);
			$Fac_Email = mysqli_real_escape_string($con, $record['Fac_Email']);
			$Fac_Dept = mysqli_real_escape_string($con, $record['Fac_Dept']);

			$query1 = "INSERT INTO faculty (Fac_ID, Fac_Name, Fac_Email, Fac_Dept) VALUES ('$Fac_ID', '$Fac_Name', '$Fac_Email', '$Fac_Dept')";
			$query2 = "INSERT INTO login (ID, User_ID, Password, Role, Status) VALUES ('$Fac_ID', '$Fac_Email', 'Faculty123*', 'Faculty', 'Activate')";

			$run1 = mysqli_query($con, $query1);
			$run2 = mysqli_query($con, $query2);

			if (!$run1 || !$run2) {
				echo "<script>alert('Some records failed to insert!'); window.location='manage-faculty.php';</script>";
				exit;
				// echo mysqli_error($con);
			}
		}
		echo "<script>alert('All faculties added successfully!'); window.location = 'manage-faculty.php';</script>";
	}
}
?>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin - Manage Students</title>
</head>

<body>
	<?php include '../Common/header.php'; ?>
	<?php include '../Common/admin-sidebar.php'; ?>

	<main>
		<div class="dashboard-header">
			<h4 class="mb-0 fw-bold">Manage Faculties</h4>
		</div>
			<div class="card shadow-sm mb-4">
				<div class="card-header text-white fw-semibold" style="background-color: #D1202D;">
					Upload Faculties (CSV)
				</div>
				<div class="card-body">	
					<div class="row">
						<div class="col-md-12 container-fluid">
							<form method="POST" enctype="multipart/form-data">
								<div class="row mt-3">
									<div class="col-md-4">
										<input type="file" class="form-control" name="csv_file" accept=".csv" required>
									</div>
									<div class="col-md-4">
										<input type="submit" class="btn btn-primary" name="Add" value="Upload CSV">
									</div>
								</div>
								<p class="text-muted mt-2">Click here to download template <a href="../Templates/Faculty_Template.csv" download>Download Template</a></p>
							</form>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12 container-fluid">
						<?php
							if (isset($_POST['Add'])) {
								$faculties = [];

								if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
									$file = $_FILES['csv_file']['tmp_name'];
									if (($handle = fopen($file, "r")) !== FALSE) {
										$isHeader = true;
										while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
											if ($isHeader) {
												$isHeader = false;
												continue;
											}
											$faculties[] = [
                                                'Fac_ID' => $data[0],
                                                'Fac_Name' => $data[1],
                                                'Fac_Email' => $data[2],
                                                'Fac_Dept' => $data[3],
											];
										}
										fclose($handle);
									}
								}

								if (!empty($faculties)) {
							?>
										<form method="POST">
											<input type="hidden" name="json_data" value='<?= json_encode($faculties, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
											<table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
												<tr class="table-dark text-white">
													<th>SL No</th>
													<th>Faculty ID</th>
													<th>Name</th>
													<th>Email</th>
													<th>Department</th>
												</tr>
												<?php foreach ($faculties as $i => $faculty): ?>
													<tr>
														<td><?= $i + 1 ?></td>
														<td><?= htmlspecialchars($faculty['Fac_ID']) ?></td>
														<td><?= htmlspecialchars($faculty['Fac_Name']) ?></td>
														<td><?= htmlspecialchars($faculty['Fac_Email']) ?></td>
														<td><?= htmlspecialchars($faculty['Fac_Dept']) ?></td>
													</tr>
												<?php endforeach; ?>
											</table>
											<div class="text-center mb-5">
												<input type="submit" name="submit_csv" value="Add Faculties" class="btn btn-success">
											</div>
										</form>
							<?php
								} else {
									echo "<div class='alert alert-warning mt-3'>No valid faculty data found in CSV.</div>";
								}
							}
							?>
						</div>
					</div>
				</div>
			</div>
			<div class="card shadow-sm mb-4">
				<div class="card-header text-white fw-semibold" style="background-color: #D1202D;">
					Existing Faculty
				</div>
				<div class="card-body">	
					<div class="row">
                <div class="col-md-12 container-fluid">
                    <!-- Search Form -->
                    <form method="POST">
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <input type="text" name="Fac_Name" class="form-control" placeholder="Enter Faculty Name"
                                    value="<?php echo isset($_POST['Fac_Name']) ? $_POST['Fac_Name'] : ''; ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" name="Submit" class="btn btn-success">Search</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
					<div class="row">
						<div class="col-md-12 container-fluid">
						<?php	
							// Build dynamic query
                            $search = "";
                            if (isset($_POST['Submit']) && !empty($_POST['Fac_Name'])) {
                                $searchTerm = mysqli_real_escape_string($con, $_POST['Fac_Name']);
                                $search = " AND Fac_Name LIKE '%$searchTerm%' ";
                            }

                            $query = "SELECT * FROM faculty ORDER BY Fac_Name ASC";
                            $run = mysqli_query($con, $query);

                            if (mysqli_num_rows($run) == 0) {
                                echo "<p class='mt-3 text-danger text-center'>No faculty found.</p>";
                            } else {
						?>	
							<section class="mt-3">
								<table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
									<tr class="table-dark text-white">
										<th>SL No</th>
										<th>Faculty ID</th>
										<th>Name</th>
										<th>Email</th>
                                        <th>Action</th>
									</tr>
									<?php
										$Sl=1;
										while($row=mysqli_fetch_array($run)) {
									?>
									<tr>
										<td><?php echo $Sl++; ?></td>
										<td><?php echo $row['Fac_ID']; ?></td>
										<td><?php echo $row['Fac_Name']; ?></td>
										<td><?php echo $row['Fac_Email']; ?></td>
                                        <td>
                                            <a href="edit-faculty.php?Fac_ID=<?php echo $row['Fac_ID']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                            <a href="delete-faculty.php?Fac_ID=<?php echo $row['Fac_ID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this faculty?');">Delete</a>
                                        </td>
									</tr>
									<?php
										}
									?>
								</table>				
							</section>
						<?php
							}
						?>
						</div>
					</div>
				</div>
			</div>
	</main>
	<?php include '../Common/footer.php'; ?>
</body>

</html>



            