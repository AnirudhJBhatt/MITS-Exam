

<?php  
	session_start();
	if (!$_SESSION["LoginFaculty"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../Login/Login.php"</script>';
	}

	require_once "../Connection/connection.php";

    $Fac_ID=$_SESSION['LoginFaculty'];
	$query = "SELECT * FROM `faculty` WHERE `Fac_ID` = '$Fac_ID' ";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_array($run);
	$Fac_Dept=$row['Fac_Dept'];
    
    function selected($field, $value) {
        return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
    }

?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty - View Courses</title>
</head>

<body>
    <!-- NAVBAR -->
    <?php include '../Common/header.php'; ?>
    <!-- SIDEBAR -->
    <?php include '../Common/faculty-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main>
        <div class="dashboard-header">
            <h4 class="mb-0 fw-bold">Courses</h4>
        </div>
        <div class="sub-main">
            <div class="row">
                <div class="col-md-12 container-fluid">
                    <!-- Search Form -->
                    <form method="POST" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label mb-0">Academic Year</label>
                            <select name="Acad_Year" class="form-control" required onchange="this.form.submit();">
                                <option value="">Select Year</option>
                                <?php 
                                    $ayquery = mysqli_query($con, "SELECT * FROM academic_year ORDER BY AY_Name");
                                    while($ay = mysqli_fetch_assoc($ayquery)){
                                        echo "<option value='".$ay['AY_Name']."' ".selected('Acad_Year',$ay['AY_Name']).">".$ay['AY_Name']."</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
						    <label for="">Programme</label>
							<select name="Prog_ID" class="form-select" required onchange="this.form.submit();">
                            <option value="">Select Programme</option>
                            <?php
                                $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';
                                $query = "SELECT DISTINCT p.Prog_ID, p.Prog_Name
								            FROM course_mapping m
											JOIN courses c ON m.Course_ID = c.Course_ID
											JOIN programmes p ON c.Prog_ID = p.Prog_ID
											WHERE m.Fac_ID = '$Fac_ID'
											AND m.Acad_Year = '$Acad_Year'";
                                $run = mysqli_query($con, $query);
                                            
                                while($row = mysqli_fetch_array($run)) {
                                    $selected = (isset($_POST['Prog_ID']) && $_POST['Prog_ID'] == $row['Prog_ID']) ? 'selected' : '';
									echo "<option value='{$row['Prog_ID']}' $selected>{$row['Prog_Name']}</option>";
                                }
                            ?>
                            </select>
                    	</div>
                        <div class="col-md-3">
                            <label>Semester</label>
                            <select name="Semester" class="form-select" required onchange="this.form.submit();">
                                <option value="">Select Semester</option>
                                <?php
                                    if (isset($_POST['Prog_ID']) && $_POST['Prog_ID'] != '') {
                                        $Prog_ID = mysqli_real_escape_string($con, $_POST['Prog_ID']);
                                        $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';

                                        $semQuery = "SELECT DISTINCT c.Semester
                                                        FROM course_mapping m
                                                        JOIN courses c ON m.Course_ID = c.Course_ID
                                                        WHERE m.Fac_ID = '$Fac_ID'
                                                        AND m.Acad_Year = '$Acad_Year'
                                                        AND c.Prog_ID = '$Prog_ID'
                                                        ORDER BY c.Semester";

                                        $semRun = mysqli_query($con, $semQuery);

                                        while ($row = mysqli_fetch_assoc($semRun)) {
                                            $sem = $row['Semester'];
                                            $selected = (isset($_POST['Semester']) && $_POST['Semester'] == $sem) ? 'selected' : '';
                                            echo "<option value='$sem' $selected>S$sem</option>";
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" name="Filter_Courses" class="btn btn-success">Show Courses</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row mt-3">
                <div class="row mt-4">
                    <?php
                    // Default Query (Show All Courses)
                    if (!isset($_POST['Filter_Courses'])) {

                        $query = "SELECT c.*, p.Prog_Name
                                FROM course_mapping m
                                JOIN courses c ON m.Course_ID = c.Course_ID
                                JOIN programmes p ON c.Prog_ID = p.Prog_ID
                                WHERE m.Fac_ID = '$Fac_ID'
                                ORDER BY c.Course_Name";

                    } else {

                        $semester = $_POST['Semester'];
                        $Prog_ID = $_POST['Prog_ID'];
                        $Acad_Year = $_POST['Acad_Year'];

                        if (empty($semester) || empty($Acad_Year) || empty($Prog_ID)) {
                            echo "<p class='text-danger mt-3'>Please select all filters.</p>";
                            return;
                        }

                        $query = "SELECT c.*, p.Prog_Name
                                FROM course_mapping m
                                JOIN courses c ON m.Course_ID = c.Course_ID
                                JOIN programmes p ON c.Prog_ID = p.Prog_ID
                                WHERE m.Fac_ID = '$Fac_ID'
                                AND c.Semester = '$semester'
                                AND p.Prog_ID = '$Prog_ID'
                                AND m.Acad_Year = '$Acad_Year'
                                ORDER BY c.Course_Name";
                    }

                    $run = mysqli_query($con, $query);

                    if (mysqli_num_rows($run) == 0) {
                        echo "<p class='text-danger mt-3'>No courses found.</p>";
                    } else {

                        while ($row = mysqli_fetch_assoc($run)) {
                    ?>

                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card shadow-sm border-0 course-card h-100">
                                <div class="card-body text-center">
                                    <h6 class="fw-bold text-uppercase">
                                        <?php echo $row['Course_Code']." - ".$row['Course_Name']; ?>
                                    </h6>
                                    <p class="mb-1 text-muted">
                                        <?php echo "S".$row['Semester']." ".$row['Prog_Name']; ?>
                                    </p><div class="mt-1">
                                        <small class="text-secondary">
                                            <?php echo "Credits: ".$row['Credits']; ?> 
                                        </small>
                                    </div>

                                    <a href="manage-exams.php?course=<?php echo urlencode($row['Course_ID']); ?>" class="btn btn-sm btn-success mt-3">Manage Exams</a>

                                    <a href="manage-exams.php?Course_ID=<?php echo urlencode($row['Course_ID']); ?>" class="btn btn-sm btn-success mt-3">Manage Exams</a>

                                </div>
                            </div>
                        </div>

                    <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>
    <?php include '../Common/footer.php'; ?>
</body>

<?php  
	session_start();
	if (!$_SESSION["LoginFaculty"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../Login/Login.php"</script>';
	}

	require_once "../Connection/connection.php";

    $Fac_ID=$_SESSION['LoginFaculty'];
	$query = "SELECT * FROM `faculty` WHERE `Fac_ID` = '$Fac_ID' ";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_array($run);
	$Fac_Dept=$row['Fac_Dept'];
    
    function selected($field, $value) {
        return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
    }

?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty - View Courses</title>
</head>

<body>
    <!-- NAVBAR -->
    <?php include '../Common/header.php'; ?>
    <!-- SIDEBAR -->
    <?php include '../Common/faculty-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main>
        <div class="dashboard-header">
            <h4 class="mb-0 fw-bold">Courses</h4>
        </div>
        <div class="sub-main">
            <div class="row">
                <div class="col-md-12 container-fluid">
                    <!-- Search Form -->
                    <form method="POST" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label mb-0">Academic Year</label>
                            <select name="Acad_Year" class="form-control" required onchange="this.form.submit();">
                                <option value="">Select Year</option>
                                <?php 
                                    $ayquery = mysqli_query($con, "SELECT * FROM academic_year ORDER BY AY_Name");
                                    while($ay = mysqli_fetch_assoc($ayquery)){
                                        echo "<option value='".$ay['AY_Name']."' ".selected('Acad_Year',$ay['AY_Name']).">".$ay['AY_Name']."</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
						    <label for="">Programme</label>
							<select name="Prog_ID" class="form-select" required onchange="this.form.submit();">
                            <option value="">Select Programme</option>
                            <?php
                                $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';
                                $query = "SELECT DISTINCT p.Prog_ID, p.Prog_Name
								            FROM course_mapping m
											JOIN courses c ON m.Course_ID = c.Course_ID
											JOIN programmes p ON c.Prog_ID = p.Prog_ID
											WHERE m.Fac_ID = '$Fac_ID'
											AND m.Acad_Year = '$Acad_Year'";
                                $run = mysqli_query($con, $query);
                                            
                                while($row = mysqli_fetch_array($run)) {
                                    $selected = (isset($_POST['Prog_ID']) && $_POST['Prog_ID'] == $row['Prog_ID']) ? 'selected' : '';
									echo "<option value='{$row['Prog_ID']}' $selected>{$row['Prog_Name']}</option>";
                                }
                            ?>
                            </select>
                    	</div>
                        <div class="col-md-3">
                            <label>Semester</label>
                            <select name="Semester" class="form-select" required onchange="this.form.submit();">
                                <option value="">Select Semester</option>
                                <?php
                                    if (isset($_POST['Prog_ID']) && $_POST['Prog_ID'] != '') {
                                        $Prog_ID = mysqli_real_escape_string($con, $_POST['Prog_ID']);
                                        $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';

                                        $semQuery = "SELECT DISTINCT c.Semester
                                                        FROM course_mapping m
                                                        JOIN courses c ON m.Course_ID = c.Course_ID
                                                        WHERE m.Fac_ID = '$Fac_ID'
                                                        AND m.Acad_Year = '$Acad_Year'
                                                        AND c.Prog_ID = '$Prog_ID'
                                                        ORDER BY c.Semester";

                                        $semRun = mysqli_query($con, $semQuery);

                                        while ($row = mysqli_fetch_assoc($semRun)) {
                                            $sem = $row['Semester'];
                                            $selected = (isset($_POST['Semester']) && $_POST['Semester'] == $sem) ? 'selected' : '';
                                            echo "<option value='$sem' $selected>S$sem</option>";
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" name="Filter_Courses" class="btn btn-success">Show Courses</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row mt-3">
                <div class="row mt-4">
                    <?php
                    // Default Query (Show All Courses)
                    if (!isset($_POST['Filter_Courses'])) {

                        $query = "SELECT c.*, p.Prog_Name
                                FROM course_mapping m
                                JOIN courses c ON m.Course_ID = c.Course_ID
                                JOIN programmes p ON c.Prog_ID = p.Prog_ID
                                WHERE m.Fac_ID = '$Fac_ID'
                                ORDER BY c.Course_Name";

                    } else {

                        $semester = $_POST['Semester'];
                        $Prog_ID = $_POST['Prog_ID'];
                        $Acad_Year = $_POST['Acad_Year'];

                        if (empty($semester) || empty($Acad_Year) || empty($Prog_ID)) {
                            echo "<p class='text-danger mt-3'>Please select all filters.</p>";
                            return;
                        }

                        $query = "SELECT c.*, p.Prog_Name
                                FROM course_mapping m
                                JOIN courses c ON m.Course_ID = c.Course_ID
                                JOIN programmes p ON c.Prog_ID = p.Prog_ID
                                WHERE m.Fac_ID = '$Fac_ID'
                                AND c.Semester = '$semester'
                                AND p.Prog_ID = '$Prog_ID'
                                AND m.Acad_Year = '$Acad_Year'
                                ORDER BY c.Course_Name";
                    }

                    $run = mysqli_query($con, $query);

                    if (mysqli_num_rows($run) == 0) {
                        echo "<p class='text-danger mt-3'>No courses found.</p>";
                    } else {

                        while ($row = mysqli_fetch_assoc($run)) {
                    ?>

                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card shadow-sm border-0 course-card h-100">
                                <div class="card-body text-center">
                                    <h6 class="fw-bold text-uppercase">
                                        <?php echo $row['Course_Code']." - ".$row['Course_Name']; ?>
                                    </h6>
                                    <p class="mb-1 text-muted">
                                        <?php echo "S".$row['Semester']." ".$row['Prog_Name']; ?>
                                    </p><div class="mt-1">
                                        <small class="text-secondary">
                                            <?php echo "Credits: ".$row['Credits']; ?> 
                                        </small>
                                    </div>

                                    <a href="manage-exams.php?course=<?php echo urlencode($row['Course_ID']); ?>" class="btn btn-sm btn-success mt-3">Manage Exams</a>

                                    <a href="manage-exams.php?Course_ID=<?php echo urlencode($row['Course_ID']); ?>" class="btn btn-sm btn-success mt-3">Manage Exams</a>

                                </div>
                            </div>
                        </div>

                    <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>
    <?php include '../Common/footer.php'; ?>
</body>


<?php  
	session_start();
	if (!$_SESSION["LoginFaculty"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../Login/Login.php"</script>';
	}

	require_once "../Connection/connection.php";

    $Fac_ID=$_SESSION['LoginFaculty'];
	$query = "SELECT * FROM `faculty` WHERE `Fac_ID` = '$Fac_ID' ";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_array($run);
	$Fac_Dept=$row['Fac_Dept'];
    
    function selected($field, $value) {
        return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
    }

?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty - View Courses</title>
</head>

<body>
    <!-- NAVBAR -->
    <?php include '../Common/header.php'; ?>
    <!-- SIDEBAR -->
    <?php include '../Common/faculty-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main>
        <div class="dashboard-header">
            <h4 class="mb-0 fw-bold">Courses</h4>
        </div>
        <div class="sub-main">
            <div class="row">
                <div class="col-md-12 container-fluid">
                    <!-- Search Form -->
                    <form method="POST" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label mb-0">Academic Year</label>
                            <select name="Acad_Year" class="form-control" required onchange="this.form.submit();">
                                <option value="">Select Year</option>
                                <?php 
                                    $ayquery = mysqli_query($con, "SELECT * FROM academic_year ORDER BY AY_Name");
                                    while($ay = mysqli_fetch_assoc($ayquery)){
                                        echo "<option value='".$ay['AY_Name']."' ".selected('Acad_Year',$ay['AY_Name']).">".$ay['AY_Name']."</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
						    <label for="">Programme</label>
							<select name="Prog_ID" class="form-select" required onchange="this.form.submit();">
                            <option value="">Select Programme</option>
                            <?php
                                $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';
                                $query = "SELECT DISTINCT p.Prog_ID, p.Prog_Name
								            FROM course_mapping m
											JOIN courses c ON m.Course_ID = c.Course_ID
											JOIN programmes p ON c.Prog_ID = p.Prog_ID
											WHERE m.Fac_ID = '$Fac_ID'
											AND m.Acad_Year = '$Acad_Year'";
                                $run = mysqli_query($con, $query);
                                            
                                while($row = mysqli_fetch_array($run)) {
                                    $selected = (isset($_POST['Prog_ID']) && $_POST['Prog_ID'] == $row['Prog_ID']) ? 'selected' : '';
									echo "<option value='{$row['Prog_ID']}' $selected>{$row['Prog_Name']}</option>";
                                }
                            ?>
                            </select>
                    	</div>
                        <div class="col-md-3">
                            <label>Semester</label>
                            <select name="Semester" class="form-select" required onchange="this.form.submit();">
                                <option value="">Select Semester</option>
                                <?php
                                    if (isset($_POST['Prog_ID']) && $_POST['Prog_ID'] != '') {
                                        $Prog_ID = mysqli_real_escape_string($con, $_POST['Prog_ID']);
                                        $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';

                                        $semQuery = "SELECT DISTINCT c.Semester
                                                        FROM course_mapping m
                                                        JOIN courses c ON m.Course_ID = c.Course_ID
                                                        WHERE m.Fac_ID = '$Fac_ID'
                                                        AND m.Acad_Year = '$Acad_Year'
                                                        AND c.Prog_ID = '$Prog_ID'
                                                        ORDER BY c.Semester";

                                        $semRun = mysqli_query($con, $semQuery);

                                        while ($row = mysqli_fetch_assoc($semRun)) {
                                            $sem = $row['Semester'];
                                            $selected = (isset($_POST['Semester']) && $_POST['Semester'] == $sem) ? 'selected' : '';
                                            echo "<option value='$sem' $selected>S$sem</option>";
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" name="Filter_Courses" class="btn btn-success">Show Courses</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row mt-3">
                <div class="row mt-4">
                    <?php
                    // Default Query (Show All Courses)
                    if (!isset($_POST['Filter_Courses'])) {

                        $query = "SELECT c.*, p.Prog_Name
                                FROM course_mapping m
                                JOIN courses c ON m.Course_ID = c.Course_ID
                                JOIN programmes p ON c.Prog_ID = p.Prog_ID
                                WHERE m.Fac_ID = '$Fac_ID'
                                ORDER BY c.Course_Name";

                    } else {

                        $semester = $_POST['Semester'];
                        $Prog_ID = $_POST['Prog_ID'];
                        $Acad_Year = $_POST['Acad_Year'];

                        if (empty($semester) || empty($Acad_Year) || empty($Prog_ID)) {
                            echo "<p class='text-danger mt-3'>Please select all filters.</p>";
                            return;
                        }

                        $query = "SELECT c.*, p.Prog_Name
                                FROM course_mapping m
                                JOIN courses c ON m.Course_ID = c.Course_ID
                                JOIN programmes p ON c.Prog_ID = p.Prog_ID
                                WHERE m.Fac_ID = '$Fac_ID'
                                AND c.Semester = '$semester'
                                AND p.Prog_ID = '$Prog_ID'
                                AND m.Acad_Year = '$Acad_Year'
                                ORDER BY c.Course_Name";
                    }

                    $run = mysqli_query($con, $query);

                    if (mysqli_num_rows($run) == 0) {
                        echo "<p class='text-danger mt-3'>No courses found.</p>";
                    } else {

                        while ($row = mysqli_fetch_assoc($run)) {
                    ?>

                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card shadow-sm border-0 course-card h-100">
                                <div class="card-body text-center">
                                    <h6 class="fw-bold text-uppercase">
                                        <?php echo $row['Course_Code']." - ".$row['Course_Name']; ?>
                                    </h6>
                                    <p class="mb-1 text-muted">
                                        <?php echo "S".$row['Semester']." ".$row['Prog_Name']; ?>
                                    </p><div class="mt-1">
                                        <small class="text-secondary">
                                            <?php echo "Credits: ".$row['Credits']; ?> 
                                        </small>
                                    </div>

                                    <a href="manage-exams.php?course=<?php echo urlencode($row['Course_ID']); ?>" class="btn btn-sm btn-success mt-3">Manage Exams</a>

                                    <a href="manage-exams.php?Course_ID=<?php echo urlencode($row['Course_ID']); ?>" class="btn btn-sm btn-success mt-3">Manage Exams</a>

                                </div>
                            </div>
                        </div>

                    <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>
    <?php include '../Common/footer.php'; ?>
</body>

</html>