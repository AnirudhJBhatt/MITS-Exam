<?php  
    session_start();
    if (!isset($_SESSION['LoginDeptAdmin'])) {
        echo "<script>alert('You Are Not Authorize Person For This link'); window.location.href='../index.php';</script>";
        exit;
    }
    require_once "../Connection/connection.php";

    $Dept_ID = $_SESSION['LoginDeptAdmin'];

    // Helper
    function selected($field, $value) {
        return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
    }

    /* === SAVE MULTIPLE FACULTY MAPPINGS === */

    if(isset($_POST['Save_Mappings'])) {

        $Acad_Year = $_POST['Acad_Year'];
        $facultyData = $_POST['Fac_ID'];

        $success = 0;
        $already_mapped = 0;

        foreach($facultyData as $course => $faculties){

            foreach($faculties as $fac){

                if(empty($fac) || empty($Acad_Year)) continue;

                $check = mysqli_query($con,
                    "SELECT 1 FROM course_mapping 
                    WHERE Course_ID='$course'
                    AND Fac_ID='$fac'
                    AND Acad_Year='$Acad_Year'");

                if(mysqli_num_rows($check) > 0){
                    $already_mapped++;
                    continue;
                }

                $insert = mysqli_query($con,
                    "INSERT INTO course_mapping 
                    (Course_ID, Fac_ID, Dept_ID, Acad_Year)
                    VALUES ('$course','$fac','$Dept_ID','$Acad_Year')");

                if($insert){

                    // Get Semester and Prog_ID
                    $qCourse = mysqli_query($con, "SELECT Semester, Prog_ID FROM courses WHERE Course_ID='$course'");
                    $c = mysqli_fetch_assoc($qCourse);
                    $semester = $c['Semester'];
                    $prog = $c['Prog_ID'];

                    // Insert mappings in bulk for all eligible students who are not already mapped
                    $map_query ="INSERT INTO student_course_mapping (Stud_ID, Course_ID, Fac_ID, Dept_ID, Semester, Acad_Year)
                        SELECT s.Stud_ID, '$course', '$fac', '$Dept_ID', '$semester', '$Acad_Year'
                        FROM student s
                        LEFT JOIN student_course_mapping scm 
                        ON scm.Stud_ID = s.Stud_ID 
                        AND scm.Course_ID = '$course' 
                        AND scm.Fac_ID = '$fac' 
                        AND scm.Acad_Year = '$Acad_Year'
                        WHERE s.Stud_Dept = '$Dept_ID'
                        AND s.Stud_Sem = '$semester'
                        AND s.Stud_Branch = '$prog'
                        AND s.Curr_AY = '$Acad_Year'
                        AND scm.Stud_ID IS NULL";

                    mysqli_query($con, $map_query);

                    $success++;
                }
            }
        }

        if ($success > 0) {
            $_SESSION['status_msg'] = "$success Mappings saved! " . ($already_mapped > 0 ? "$already_mapped already existed." : "");
            $_SESSION['status_type'] = "success";
        } else if ($already_mapped > 0) {
            $_SESSION['status_msg'] = "No new mappings saved. $already_mapped already existed.";
            $_SESSION['status_type'] = "info";
        } else {
            $_SESSION['status_msg'] = "No mappings were saved. Please select valid faculty and academic year.";
            $_SESSION['status_type'] = "warning";
        }

        header("Location: map-courses.php");
        exit();
    }
?>

<!DOCTYPE html>
<html>

<head>
    <title>Admin - Map Courses</title>
</head>

<body>

    <?php include '../Common/header.php'; ?>
    <?php include '../Common/deptadmin-sidebar.php'; ?>

    <main>
        <div class="dashboard-header d-flex justify-content-between align-items-center">
            <h4 class="fw-bold">Course Mapping</h4>
            <a href="sync-student-courses.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-repeat"></i> Sync Student Mappings
            </a>
        </div>

        <div class="sub-main">

            <!-- FILTER FORM -->
            <form method="POST" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label>Academic Year</label>
                    <select name="Acad_Year" class="form-control">
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
                    <label>Semester</label>
                    <select name="Filter_Semester" class="form-control">
                        <option value="">Select Semester</option>
                        <?php 
                            for($i=1;$i<=8;$i++){
                                echo "<option value='$i' ".selected('Filter_Semester',$i).">S$i</option>";
                            }
                        ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Programme</label>
                    <select name="Prog_ID" class="form-control">
                        <option value="">-- Select Programme --</option>
                        <?php
                            $pquery = mysqli_query($con, "SELECT * FROM programmes WHERE Dept_ID='$Dept_ID'");
                            while($p = mysqli_fetch_assoc($pquery)){
                                echo "<option value='".$p['Prog_ID']."' ".selected('Prog_ID',$p['Prog_ID']).">".$p['Prog_Name']."</option>";
                            }
                        ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" name="Filter_Courses" class="btn btn-success">Show Courses</button>
                </div>
            </form>

            <?php
                if(isset($_POST['Filter_Courses'])):

                $semester = $_POST['Filter_Semester'];
                $Acad_Year = $_POST['Acad_Year'];
                $Prog_ID = $_POST['Prog_ID'];

                $course_query = mysqli_query($con,
                    "SELECT * FROM courses 
                    WHERE Dept_ID='$Dept_ID' 
                    AND Semester='$semester' 
                    AND Prog_ID='$Prog_ID'");

                ?>

            <form method="POST">
                <input type="hidden" name="Acad_Year" value="<?= $Acad_Year ?>">

                <table class="w-100 table table-bordered border-dark table-hover text-center mt-3">
                    <tr class="table-dark text-white">
                        <th>SL No</th>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Faculty</th>
                    </tr>

                    <?php 
                    $sl=1;
                    while($c = mysqli_fetch_assoc($course_query)):

                    $mapQuery = mysqli_query($con,
                        "SELECT Fac_ID FROM course_mapping 
                        WHERE Course_ID='".$c['Course_ID']."' 
                        AND Acad_Year='$Acad_Year'");

                    $mappedFaculty = [];
                    while($m = mysqli_fetch_assoc($mapQuery)){
                        $mappedFaculty[] = $m['Fac_ID'];
                    }
                    ?>

                    <tr>
                        <td>
                            <?= $sl++ ?>
                        </td>
                        <td>
                            <?= $c['Course_Code'] ?>
                        </td>
                        <td>
                            <?= $c['Course_Name'] ?>
                        </td>
                        <td>

                            <div id="faculty-container-<?= $c['Course_ID'] ?>">

                                <?php
                                    if(!empty($mappedFaculty)){
                                        foreach($mappedFaculty as $mf){
                                        ?>
                                        <div class="faculty-row d-flex mb-2">
                                            <select name="Fac_ID[<?= $c['Course_ID'] ?>][]" class="form-control faculty-select"
                                            data-course="<?= $c['Course_ID'] ?>">
                                        <option value="">-- Select Faculty --</option>
                                        <?php
                                            $fRun = mysqli_query($con,"SELECT * FROM faculty ORDER BY Fac_Name");
                                            while($f = mysqli_fetch_assoc($fRun)){
                                            $sel = ($f['Fac_ID']==$mf) ? "selected" : "";
                                            echo "<option value='".$f['Fac_ID']."' $sel>".$f['Fac_ID']." - ".$f['Fac_Name']."</option>";
                                            }
                                        ?>
                                    </select>
                                    <button type="button" 
                                        class="btn btn-danger btn-sm ms-2"
                                        onclick="deleteFaculty(this, '<?= $c['Course_ID'] ?>', '<?= $mf ?? '' ?>', '<?= $Acad_Year ?>')">
                                        ✖
                                    </button>
                                </div>
                                <?php } } else { ?>
                                <div class="faculty-row d-flex mb-2">
                                    <select name="Fac_ID[<?= $c['Course_ID'] ?>][]" class="form-control faculty-select"
                                        data-course="<?= $c['Course_ID'] ?>">
                                        <option value="">-- Select Faculty --</option>
                                        <?php
                                            $fRun = mysqli_query($con,"SELECT * FROM faculty ORDER BY Fac_Name");
                                            while($f = mysqli_fetch_assoc($fRun)){
                                                echo "<option value='".$f['Fac_ID']."'>".$f['Fac_ID']." - ".$f['Fac_Name']."</option>";
                                            }
                                        ?>
                                    </select>
                                    <button type="button" class="btn btn-danger btn-sm ms-2"
                                        onclick="removeFaculty(this)">✖</button>
                                </div>
                                <?php } ?>

                            </div>

                            <button type="button" class="btn btn-success btn-sm mt-2"
                                onclick="addFaculty(<?= $c['Course_ID'] ?>)">
                                + Add Faculty
                            </button>

                        </td>
                    </tr>

                    <?php endwhile; ?>
                </table>

                <div class="text-center">
                    <button type="submit" name="Save_Mappings" class="btn btn-primary">
                        Save Mappings
                    </button>
                </div>

            </form>

            <?php endif; ?>

        </div>
    </main>

    <?php include '../Common/footer.php'; ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function () {
            $('.faculty-select').select2({ width: '100%' });
        });

        function addFaculty(courseId) {
            let container = $('#faculty-container-' + courseId);

            let newRow = `
    <div class="faculty-row d-flex mb-2">
        <select name="Fac_ID[${courseId}][]" 
                class="form-control faculty-select"
                data-course="${courseId}">
            <option value="">-- Select Faculty --</option>
            <?php
            $fRunJS = mysqli_query($con,"SELECT * FROM faculty ORDER BY Fac_Name");
            while($f=mysqli_fetch_assoc($fRunJS)){
                echo "<option value='".$f['Fac_ID']."'>".$f['Fac_ID']." - ".$f['Fac_Name']."</option>";
            }
            ?>
        </select>
        <button type="button" class="btn btn-danger btn-sm ms-2" onclick="removeFaculty(this)">✖</button>
    </div>`;

            container.append(newRow);
            container.find('.faculty-select').select2({ width: '100%' });
        }

        function removeFaculty(btn) {
            $(btn).closest('.faculty-row').remove();
        }

        // Prevent duplicate selection
        $(document).on('change', '.faculty-select', function () {
            let courseId = $(this).data('course');
            let values = [];

            $(`select[data-course='${courseId}']`).each(function () {
                if ($(this).val()) {
                    values.push($(this).val());
                }
            });

            let duplicates = values.filter((item, index) => values.indexOf(item) !== index);

            if (duplicates.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Duplicate Selection',
                    text: 'Faculty already selected for this course!',
                    confirmButtonColor: '#3085d6'
                });
                $(this).val(null).trigger('change');
            }
        });
        
        function deleteFaculty(button, courseId, facultyId, acadYear){

            if(!facultyId){
                // Just remove row if not saved yet
                $(button).closest('.faculty-row').remove();
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: "You want to remove this faculty mapping?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "delete-course-faculty.php",
                        type: "POST",
                        data: {
                            course: courseId,
                            faculty: facultyId,
                            acad_year: acadYear
                        },
                        success: function(response){
                            if(response.trim() === "success"){
                                $(button).closest('.faculty-row').fadeOut(300, function(){
                                    $(this).remove();
                                });
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: 'Faculty mapping has been removed.',
                                    confirmButtonColor: '#3085d6'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Error deleting faculty mapping.',
                                    confirmButtonColor: '#3085d6'
                                });
                            }
                        }
                    });
                }
            });
        }
    </script>

    <?php if (isset($_SESSION['status_msg'])): ?>
    <script>
        Swal.fire({
            icon: '<?= $_SESSION['status_type'] ?>',
            title: '<?= $_SESSION['status_type'] === 'success' ? 'Success' : 'Notice' ?>',
            text: '<?= $_SESSION['status_msg'] ?>',
            confirmButtonColor: '#3085d6'
        });
    </script>
    <?php 
        unset($_SESSION['status_msg']);
        unset($_SESSION['status_type']);
    endif; 
    ?>

</body>

</html>