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
	if ($records && is_array($records)) {
		foreach ($records as $record) {
			$Fac_ID = mysqli_real_escape_string($con, $record['Fac_ID']);
			$Fac_Name = mysqli_real_escape_string($con, $record['Fac_Name']);
			$Fac_Email = mysqli_real_escape_string($con, $record['Fac_Email']);
			$Fac_Dept = mysqli_real_escape_string($con, $Dept_ID);

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
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Dept Admin - Manage Faculty</title>
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
                    <h4 class="mb-1">Manage Faculty</h4>
                    <p class="text-muted mb-0">Upload and manage faculty members for your department.</p>
                </div>
            </div>

			<div class="card shadow-sm border-0 rounded-4 mb-4 hover-elevate">
				<div class="card-header bg-white border-bottom-0 py-3 px-4">
					<h6 class="text-uppercase fw-bold text-primary mb-0"><i class="ti ti-file-upload me-2"></i>Upload Faculty (CSV)</h6>
				</div>
				<div class="card-body px-4 pb-4">	
					<div class="row">
						<div class="col-md-12">
							<form method="POST" enctype="multipart/form-data">
								<div class="row g-3 align-items-center mt-1">
									<div class="col-md-5">
                                        <label class="form-label small fw-bold text-secondary mb-1">Upload CSV</label>
										<input type="file" class="form-control shadow-none border-secondary-subtle" name="csv_file" accept=".csv" required>
									</div>
									<div class="col-md-3 mt-4 pt-2">
										<button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold w-100" name="Add"><i class="ti ti-upload me-1"></i>Upload CSV</button>
									</div>
								</div>
                                <div class="mt-3">
                                    <a href="../Templates/Faculty_Template.csv" class="btn btn-sm btn-outline-secondary rounded-pill px-3" download><i class="ti ti-download me-1"></i>Download CSV Template</a>
                                </div>
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
                                                'Fac_Dept' => $Dept_ID
											];
										}
										fclose($handle);
									}
								}

								if (!empty($faculties)) {
							?>
										<form method="POST">
											<input type="hidden" name="json_data" value='<?= json_encode($faculties, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
											<div class="table-responsive rounded-3 border mt-4">
                                                <table class="table table-hover align-middle mb-0 text-center">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th class="py-3 text-secondary fw-semibold">SL No</th>
                                                            <th class="py-3 text-secondary fw-semibold">Faculty ID</th>
                                                            <th class="py-3 text-secondary fw-semibold">Name</th>
                                                            <th class="py-3 text-secondary fw-semibold">Email</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($faculties as $i => $faculty): ?>
                                                            <tr>
                                                                <td class="text-muted fw-bold"><?= $i + 1 ?></td>
                                                                <td class="fw-semibold text-dark"><?= htmlspecialchars($faculty['Fac_ID']) ?></td>
                                                                <td><?= htmlspecialchars($faculty['Fac_Name']) ?></td>
                                                                <td><a href="mailto:<?= htmlspecialchars($faculty['Fac_Email']) ?>" class="text-decoration-none"><?= htmlspecialchars($faculty['Fac_Email']) ?></a></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
											<div class="text-end mt-4 mb-2">
												<button type="submit" name="submit_csv" class="btn btn-success rounded-pill px-4 fw-bold shadow"><i class="ti ti-check me-2"></i>Confirm & Add Faculties</button>
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
			<div class="card shadow-sm border-0 rounded-4 mb-4 hover-elevate">
				<div class="card-header bg-white border-bottom-0 py-3 px-4">
					<h6 class="text-uppercase fw-bold text-success mb-0"><i class="ti ti-users me-2"></i>Existing Faculty</h6>
				</div>
				<div class="card-body px-4 pb-4">	
					<div class="row">
                        <div class="col-md-12">
                            <!-- Search Form -->
                            <form method="POST" class="bg-light p-3 rounded-4 mb-4">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-secondary mb-1">Faculty Name</label>
                                        <input type="text" name="Fac_Name" class="form-control shadow-none border-secondary-subtle" placeholder="Search by name..."
                                            value="<?php echo isset($_POST['Fac_Name']) ? $_POST['Fac_Name'] : ''; ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" name="Submit" class="btn btn-success rounded-pill fw-bold w-100 shadow-sm"><i class="ti ti-search me-1"></i>Search</button>
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

                            $query = "SELECT * FROM faculty WHERE Fac_Dept='$Dept_ID' $search ORDER BY Fac_Name ASC";
                            $run = mysqli_query($con, $query);

                            if (mysqli_num_rows($run) == 0) {
                                echo "<p class='mt-3 text-danger text-center'>No faculty found.</p>";
                            } else {
						?>	
							<section class="mt-3">
                                <div class="table-responsive border rounded-4 mt-2">
                                    <table class="table table-hover align-middle mb-0 text-center">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="py-3 text-secondary fw-semibold">SL No</th>
                                                <th class="py-3 text-secondary fw-semibold">Faculty ID</th>
                                                <th class="py-3 text-secondary fw-semibold">Name</th>
                                                <th class="py-3 text-secondary fw-semibold">Email</th>
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
                                            <td class="fw-semibold text-dark"><?php echo $row['Fac_ID']; ?></td>
                                            <td><?php echo $row['Fac_Name']; ?></td>
                                            <td><a href="mailto:<?php echo $row['Fac_Email']; ?>" class="text-decoration-none"><?php echo $row['Fac_Email']; ?></a></td>
                                            <td>
                                                <a href="edit-faculty.php?Fac_ID=<?php echo $row['Fac_ID']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold"><i class="ti ti-edit me-1"></i>Edit</a>
                                                <a href="delete-faculty.php?Fac_ID=<?php echo $row['Fac_ID']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-semibold ms-1" onclick="return confirm('Are you sure you want to delete this faculty?');"><i class="ti ti-trash me-1"></i>Delete</a>
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



            