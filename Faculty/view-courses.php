<?php  
	session_start();
	if (!$_SESSION["LoginFaculty"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../index.php"</script>';
	}

	require_once "../Connection/connection.php";

    $Fac_ID=$_SESSION['LoginFaculty'];
	$query = "SELECT * FROM `faculty` WHERE `Fac_ID` = '$Fac_ID' ";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_array($run);
	$Fac_Dept=$row['Fac_Dept'];
    $Fac_Acad_Year=$row['Acad_Year'];
    
    function selected($field, $value) {
        return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
    }

?>
<!DOCTYPE html>
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

        <div class="container-fluid">
            <!-- Header -->
            <div class="dashboard-header mb-4 d-flex justify-content-between align-items-center border-bottom pb-3">
                <div>
                    <h4 class="mb-0 fw-bold">My Courses</h4>
                </div>
            </div>

            <div class="sub-main">
                <!-- Filter Card -->
                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-header bg-light border-0 py-3 px-4">
                        <h6 class="mb-0 fw-bold text-uppercase">Filter Courses</h6>
                    </div>
                    <div class="card-body px-4 py-3">
                        <form method="POST" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label text-muted small fw-bold mb-1">Academic Year</label>
                                <select name="Acad_Year" class="form-select shadow-none border-secondary-subtle" required onchange="this.form.submit();">
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
                                <label class="form-label text-muted small fw-bold mb-1">Programme</label>
                                <select name="Prog_ID" class="form-select shadow-none border-secondary-subtle" required onchange="this.form.submit();">
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
                                <label class="form-label text-muted small fw-bold mb-1">Semester</label>
                                <select name="Semester" class="form-select shadow-none border-secondary-subtle" required onchange="this.form.submit();">
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
                            <div class="col-md-3 d-grid">
                                <button type="submit" name="Filter_Courses" class="btn btn-primary shadow-sm fw-bold">Apply Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Courses List -->
                <div class="row g-4">
                    <?php
                    // Default Query (Show All Courses)
                    if (!isset($_POST['Filter_Courses'])) {

                        $query = "SELECT c.*, p.Prog_Name
                                FROM course_mapping m
                                JOIN courses c ON m.Course_ID = c.Course_ID
                                JOIN programmes p ON c.Prog_ID = p.Prog_ID
                                WHERE m.Fac_ID = '$Fac_ID' AND m.Acad_Year='$Fac_Acad_Year'
                                ORDER BY c.Course_Name";

                    } else {

                        $semester = $_POST['Semester'];
                        $Prog_ID = $_POST['Prog_ID'];
                        $Acad_Year = $_POST['Acad_Year'];

                        if (empty($semester) || empty($Acad_Year) || empty($Prog_ID)) {
                            echo "<div class='col-12'><div class='alert alert-warning border-0 shadow-sm'>Please select all filters to view courses.</div></div>";
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

                    if ($run && mysqli_num_rows($run) == 0) {
                        echo "<div class='col-12'><div class='alert alert-info border-0 shadow-sm'>No courses found matching the selected criteria.</div></div>";
                    } elseif ($run) {
                        while ($row = mysqli_fetch_assoc($run)) {
                    ?>
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card h-100 shadow-sm border-0 bg-white rounded-3 overflow-hidden transition-hover">
                                <div class="card-header bg-light border-0 py-3 px-4">
                                    <h6 class="mb-0 fw-bold text-truncate" title="<?php echo $row['Course_Name']; ?>">
                                        <?php echo $row['Course_Code']; ?>
                                    </h6>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fw-bold mb-3" style="font-size: 1.1rem;">
                                        <?php echo $row['Course_Name']; ?>
                                    </h5>
                                    
                                    <div class="mb-3 mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2 fw-semibold">
                                                Semester <?php echo $row['Semester']; ?>
                                            </span>
                                            <span class="badge bg-info-subtle text-info rounded-pill px-3 py-2 fw-semibold">
                                                <?php echo $row['Credits']; ?> Credits
                                            </span>
                                        </div>
                                        <div class="text-muted small fw-semibold">
                                            <i class="bi bi-mortarboard-fill me-1"></i> <?php echo $row['Prog_Name']; ?>
                                        </div>
                                    </div>

                                    <a href="manage-exams.php?Course_ID=<?php echo urlencode($row['Course_ID']); ?>" class="btn btn-outline-primary w-100 fw-bold mt-2 rounded-pill">
                                        Manage Exams
                                    </a>
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
    <style>
        .transition-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .transition-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        }
        .bg-secondary-subtle {
            background-color: #e2e3e5 !important;
        }
        .bg-info-subtle {
            background-color: #cff4fc !important;
        }
    </style>
    <?php include '../Common/footer.php'; ?>
</body>

</html>