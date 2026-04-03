 <?php  
	session_start();
	if (!isset($_SESSION['LoginAdmin'])) {
        echo "<script>alert('You Are Not Authorize Person For This link'); window.location.href='../index.php';</script>";
        exit;
    }
	require_once "../Connection/connection.php";
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Academic Year</title>
</head>

<body>
    <!-- NAVBAR -->
    <?php include '../Common/header.php'; ?>
    <!-- SIDEBAR -->
    <?php include '../Common/admin-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main>
        <div class="dashboard-header">
            <h4 class="mb-0 fw-bold">Academic Year</h4>
        </div>
        <div class="sub-main">
			<form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
				<div class="col-md-4">
					<label for="Acad_Year" class="form-label">Academic Year</label>
					<input type="text" name="Acad_Year" required class="form-control" placeholder="Enter Academic Year eg. 2023-2024">
				</div>
				<div class="col-md-4">
					<input type="submit" name="Submit" value="Add" class="btn btn-success w-25">
				</div>
			</form>
			<?php	
				if (isset($_POST['Submit'])) {
                    $Acad_Year = $_POST['Acad_Year'];
                    $query = "INSERT INTO academic_year (AY_Name) VALUES ('$Acad_Year')";
                    $run = mysqli_query($con, $query);
                    if ($run) {
                        echo "<p class='mt-3 text-success text-center'>Academic Year added successfully.</p>";
                        $query1 = "UPDATE faculty SET Acad_year='$Acad_Year'";
                        $query2 = "UPDATE student SET Curr_AY='$Acad_Year'";
                        mysqli_query($con, $query1);
                        mysqli_query($con, $query2);
                        if (mysqli_affected_rows($con) > 0) {
                            echo "<p class='mt-3 text-success text-center'>Academic Year updated for all faculty and students.</p>";
                        } else {
                            echo "<p class='mt-3 text-warning text-center'>No faculty or students were updated.</p>";
                        }
                    } else {
                        echo "<p class='mt-3 text-danger text-center'>Error adding Academic Year: " . mysqli_error($con) . "</p>";
                    }
                }
			?>
		</div>
    </main>
    <?php include '../Common/footer.php'; ?>
</body>
</html>