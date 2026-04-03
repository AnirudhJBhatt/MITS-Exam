<?php  
	session_start();
	if (!$_SESSION["LoginStudent"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../Login/Login.php"</script>';
	}

	require_once "../Connection/connection.php";

    $Stud_ID=$_SESSION['LoginStudent'];
	$query = "SELECT * FROM `student` WHERE `Stud_ID` = '$Stud_ID' ";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_array($run);
	$Stud_Dept=$row['Stud_Dept'];
    
    function selected($field, $value) {
        return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
    }

?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student - View Courses</title>
</head>

<body>
    <!-- NAVBAR -->
    <?php include '../Common/header.php'; ?>
    <!-- SIDEBAR -->
    <?php include '../Common/student-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main>
        <div class="dashboard-header">
            <h4 class="mb-0 fw-bold">Dashboard</h4>
        </div>
        <div class="sub-main">
            <div class="row">
                <div class="col-md-12 container-fluid">
                    <!-- Search Form -->
                    <form method="POST" class="row g-3 align-items-end">
                        <!-- <div class="col-md-3">
                            <label class="form-label mb-0">Academic Year</label>
                            <select name="Acad_Year" class="form-control">
                                <option value="">Select Year</option>
                                <?php 
                                    $ayquery = mysqli_query($con, "SELECT * FROM academic_year ORDER BY AY_Name");
                                    while($ay = mysqli_fetch_assoc($ayquery)){
                                        echo "<option value='".$ay['AY_Name']."' ".selected('Acad_Year',$ay['AY_Name']).">".$ay['AY_Name']."</option>";
                                    }
                                ?>
                            </select>
                        </div> -->
                        <div class="col-md-3">
                            <label class="form-label mb-0">Semester</label>
                            <select name="Filter_Semester" class="form-control">
                                <option value="">Select Semester</option>
                                <?php for($i=1;$i<=8;$i++){
                                    echo "<option value='$i' ".selected('Filter_Semester',$i).">S$i</option>";
                                } ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" name="Filter_Courses" class="btn btn-success">Show Courses</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 container-fluid">
                    <?php
                    if (isset($_POST['Filter_Courses'])) {  
                        $semester = $_POST['Filter_Semester'];
                        // $Acad_Year = $_POST['Acad_Year'];
                        if (empty($semester)) {
                            echo "<p class='text-danger mt-3'>Please select Semester.</p>";
                        } else {
                            $query = "SELECT 
                                        scm.Course_ID,
                                        c.Course_Code,
                                        c.Course_Name,
                                        c.Credits,
                                        GROUP_CONCAT(f.Fac_Name SEPARATOR '||') AS Faculty_List
                                    FROM student_course_mapping scm
                                    INNER JOIN courses c ON scm.Course_ID = c.Course_ID
                                    INNER JOIN faculty f ON scm.Fac_ID = f.Fac_ID
                                    WHERE scm.Stud_ID = '$Stud_ID'
                                    AND scm.Semester = '$semester'
                                    GROUP BY scm.Course_ID
                                    ORDER BY c.Course_Name";
                            $run = mysqli_query($con, $query);
                            if (mysqli_num_rows($run) == 0) {
                                echo "<p class='text-danger mt-3'>No courses found for selected semester.</p>";
                            } else {
                    ?>
                    <section>
                        <table class="w-100 table table-bordered border-dark table-hover text-center">
                            <tr class="table-dark text-white">
                                <th>SL No</th>
                                <th>Subject</th>
                                <th>Faculty Name</th>
                                <th>Credits</th>
                            </tr>

                            <?php
                                $Sl = 1;
                                while ($row = mysqli_fetch_assoc($run)) {
                                    $facultyArray = explode("||", $row['Faculty_List']);
                            ?>
                            <tr>
                                <td><?php echo $Sl++; ?></td>
                                <td><?php echo $row['Course_Code'] . " - " . $row['Course_Name']; ?></td>
                                <td>
                                    <?php 
                                        foreach($facultyArray as $facultyName){
                                            echo $facultyName . "<br>";
                                        }
                                    ?>
                                </td>
                                <td><?php echo $row['Credits']; ?></td>  
                            </tr>
                            <?php } ?>
                        </table>
                    </section>
                    <?php } 
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