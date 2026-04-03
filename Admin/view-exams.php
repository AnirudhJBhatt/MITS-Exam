<?php  
	session_start();
	if (!isset($_SESSION['LoginAdmin'])) {
		echo "<script>alert('You are not authorized to access this page'); window.location.href='../index.php';</script>";
		exit;
	}
	require_once "../Connection/connection.php";

	function selected($field, $value) {
        return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
    }
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - View Exams</title>
</head>

<body>
    <!-- NAVBAR -->
    <?php include '../Common/header.php'; ?>
    <!-- SIDEBAR -->
    <?php include '../Common/admin-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main>
        <div class="dashboard-header">
            <h4 class="mb-0 fw-bold">View Exams</h4>
        </div>
        <div class="sub-main">
            <!-- Search -->
            <form method="POST" class="row g-3 mt-3 align-items-end">
                <!-- Year Filter -->
                <div class="col-md-3">
                    <label for="Acad_Year" class="form-label">Academic Year</label>
                    <select name="Acad_Year" class="form-control">
                        <option value="">-- Select Year --</option>
                        <?php
                            $ayquery ="SELECT DISTINCT Acad_Year FROM exam ORDER BY Acad_Year DESC";
                            $ayrun = mysqli_query($con, $ayquery);
                            while ($ayrow = mysqli_fetch_assoc($ayrun)) {
                                $ay = $ayrow['Acad_Year'];
                                echo "<option value='$ay' ".selected('Acad_Year',"$ay").">$ay</option>";
                            }
                        ?>                        
                    </select>
                </div>
                <div class="col-md-3">
					<label for="Dept_ID" class="form-label">Department</label>
					<select class="form-select" name="Dept_ID">
						<option>Select Department</option>
						<?php
							$query = "SELECT * From department";
							$run = mysqli_query($con, $query);
							while ($row = mysqli_fetch_array($run)) {
								echo "<option value='" . $row['Dept_ID'] . "' " . selected('Dept_ID', $row['Dept_ID']) . ">" . $row['Dept_Name'] . "</option>";
							}
						?>
					</select>
				</div>
                <!-- Semester Filter -->
                <div class="col-md-3">
                    <label for="Semester" class="form-label">Semester</label>
                    <select name="Semester" class="form-control">
                        <option value="">Semester</option>
                        <?php 
                            for($i=1;$i<=8;$i++){
                                echo "<option value='$i' ".selected('Semester',"$i").">S$i</option>";
                            }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="submit" name="Search" class="btn btn-success" value="Search">
                </div>
            </form>


            <div class="row mt-4">
                <div class="col-md-12">
                    <?php  
                        if (isset($_POST['Search'])) {

                            $conditions = [];
                            $year = $_POST['Acad_Year'];
                            $Dept_ID = $_POST['Dept_ID'];
                            $semester = $_POST['Semester'];
                            // Year Filter
                            if (!empty($year)) {
                                $conditions[] = "e.Acad_Year = '$year'";
                            }
                            // Semester Filter
                            if (!empty($semester)) {
                                $conditions[] = "e.Semester = '$semester'";
                            }
                            // Always include department
                            $conditions[] = "e.Dept = '$Dept_ID'";

                            // Build final query
                            $whereSQL = "WHERE " . implode(" AND ", $conditions);

                            $query = "SELECT * FROM exam e, courses c, faculty f $whereSQL AND e.Course_ID=c.Course_ID AND e.Fac_ID=f.Fac_ID ORDER BY e.Exam_Title ASC";
                            // echo $query;
                            $run = mysqli_query($con, $query);

                            if (mysqli_num_rows($run) == 0) {
                                echo "<p class='mt-3 text-danger text-center'>No exams found.</p>";
                            } 
                            else {
                    ?>


                    <table class="table table-bordered table-hover text-center border-dark mt-3">
                        <tr class="table-dark text-white">
                            <th>SL No</th>
                            <th>Exam</th>
                            <th>Course</th>
                            <th>Faculty</th>
                        </tr>

                        <?php 
                            $sl = 1;
                            while ($row = mysqli_fetch_assoc($run)) {
                        ?>
                        <tr>
                            <td><?php echo $sl++; ?></td>
                            <td><?php echo $row['Exam_Title']; ?></td>
                            <td><?php echo $row['Course_ID'] . " - " . $row['Course_Name']; ?></td>
                            <td><?php echo $row['Fac_ID'] . " - " . $row['Fac_Name']; ?></td>
                        </tr>
                        <?php } ?>
                    </table>

                    <?php 
                            } // end else
                        } // end if searched
                    ?>
                </div>
            </div>
        </div>
    </main>
    <?php include '../Common/footer.php'; ?>
</body>
</html>