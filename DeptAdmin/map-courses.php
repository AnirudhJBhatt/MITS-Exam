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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dept Admin - Course Mapping</title>
    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
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
                    <h4 class="mb-1">Course Mapping</h4>
                    <p class="text-muted mb-0">Assign faculty members to specific courses and synchronize data.</p>
                </div>
                <a href="sync-student-courses.php" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm hover-elevate">
                    <i class="ti ti-refresh me-2"></i>Sync Student Mappings
                </a>
            </div>

            <div class="card shadow-sm border-0 rounded-4 mb-4 hover-elevate">
                <div class="card-header bg-white border-bottom-0 py-3 px-4">
                    <h6 class="text-uppercase fw-bold text-success mb-0"><i class="ti ti-filter me-2"></i>Filter Courses</h6>
                </div>
                <div class="card-body px-4 pb-4">
                    <!-- FILTER FORM -->
                    <form method="POST" class="row g-3 align-items-end bg-light p-3 rounded-4 mb-4">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-secondary mb-1">Academic Year</label>
                            <select name="Acad_Year" class="form-select shadow-none border-secondary-subtle">
                                <option value="">-- Select Year --</option>
                                <?php 
                                    $ayquery = mysqli_query($con, "SELECT * FROM academic_year ORDER BY AY_Name");
                                    while($ay = mysqli_fetch_assoc($ayquery)){
                                        echo "<option value='".$ay['AY_Name']."' ".selected('Acad_Year',$ay['AY_Name']).">".$ay['AY_Name']."</option>";
                                    }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-secondary mb-1">Semester</label>
                            <select name="Filter_Semester" class="form-select shadow-none border-secondary-subtle">
                                <option value="">-- Select Semester --</option>
                                <?php 
                                    for($i=1;$i<=8;$i++){
                                        echo "<option value='$i' ".selected('Filter_Semester',$i).">S$i</option>";
                                    }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-secondary mb-1">Programme</label>
                            <select name="Prog_ID" class="form-select shadow-none border-secondary-subtle">
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
                            <button type="submit" name="Filter_Courses" class="btn btn-success rounded-pill fw-bold w-100 shadow-sm"><i class="ti ti-search me-1"></i>Show Courses</button>
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
                
                <div class="table-responsive border rounded-4">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 text-secondary fw-semibold">SL No</th>
                                <th class="py-3 text-secondary fw-semibold">Course Code</th>
                                <th class="py-3 text-secondary fw-semibold text-start ps-5">Course Name</th>
                                <th class="py-3 text-secondary fw-semibold text-start">Faculty Mapping</th>
                            </tr>
                        </thead>
                        <tbody>

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
                        <td class="text-muted fw-bold" style="width: 8%;">
                            <?= $sl++ ?>
                        </td>
                        <td class="fw-semibold text-dark" style="width: 15%;">
                            <?= $c['Course_Code'] ?>
                        </td>
                        <td class="text-start ps-5" style="width: 35%;">
                            <?= $c['Course_Name'] ?>
                        </td>
                        <td class="text-start pe-4">

                            <div id="faculty-container-<?= $c['Course_ID'] ?>">

                                <?php
                                    if(!empty($mappedFaculty)){
                                        foreach($mappedFaculty as $mf){
                                        ?>
                                        <div class="faculty-row d-flex mb-2">
                                            <div class="flex-grow-1">
                                                <select name="Fac_ID[<?= $c['Course_ID'] ?>][]" class="form-select shadow-none border-secondary-subtle faculty-select"
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
                                            </div>
                                        <button type="button" 
                                            class="btn btn-outline-danger btn-sm ms-2 rounded-circle shadow-sm" style="width: 38px; height: 38px; flex-shrink: 0;"
                                            onclick="deleteFaculty(this, '<?= $c['Course_ID'] ?>', '<?= $mf ?? '' ?>', '<?= $Acad_Year ?>')" title="Remove">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </div>
                                    <?php } } else { ?>
                                    <div class="faculty-row d-flex mb-2">
                                        <div class="flex-grow-1">
                                            <select name="Fac_ID[<?= $c['Course_ID'] ?>][]" class="form-select shadow-none border-secondary-subtle faculty-select"
                                                data-course="<?= $c['Course_ID'] ?>">
                                                <option value="">-- Select Faculty --</option>
                                                <?php
                                                    $fRun = mysqli_query($con,"SELECT * FROM faculty ORDER BY Fac_Name");
                                                    while($f = mysqli_fetch_assoc($fRun)){
                                                        echo "<option value='".$f['Fac_ID']."'>".$f['Fac_ID']." - ".$f['Fac_Name']."</option>";
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger btn-sm ms-2 rounded-circle shadow-sm" style="width: 38px; height: 38px; flex-shrink: 0;"
                                            onclick="removeFaculty(this)" title="Remove"><i class="ti ti-x"></i></button>
                                    </div>
                                    <?php } ?>

                                </div>

                                <button type="button" class="btn btn-outline-success btn-sm mt-1 rounded-pill px-3 fw-bold"
                                    onclick="addFaculty(<?= $c['Course_ID'] ?>)">
                                    <i class="ti ti-plus me-1"></i> Add Faculty
                                </button>

                            </td>
                        </tr>

                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-end mt-4 mb-2">
                    <button type="submit" name="Save_Mappings" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                        <i class="ti ti-device-floppy me-2"></i>Save All Mappings
                    </button>
                </div>

            </form>

            <?php endif; ?>

                </div>
            </div>
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
            $('.faculty-select').select2({ 
                width: '100%',
                theme: 'bootstrap-5'
            });
        });

        function addFaculty(courseId) {
            let container = $('#faculty-container-' + courseId);

            let newRow = `
    <div class="faculty-row d-flex mb-2">
        <div class="flex-grow-1">
            <select name="Fac_ID[${courseId}][]" 
                    class="form-select shadow-none border-secondary-subtle faculty-select"
                    data-course="${courseId}">
                <option value="">-- Select Faculty --</option>
                <?php
                $fRunJS = mysqli_query($con,"SELECT * FROM faculty ORDER BY Fac_Name");
                while($f=mysqli_fetch_assoc($fRunJS)){
                    echo "<option value='".$f['Fac_ID']."'>".$f['Fac_ID']." - ".$f['Fac_Name']."</option>";
                }
                ?>
            </select>
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm ms-2 rounded-circle shadow-sm" style="width: 38px; height: 38px; flex-shrink: 0;" onclick="removeFaculty(this)"><i class="ti ti-x"></i></button>
    </div>`;

            container.append(newRow);
            container.find('.faculty-select').select2({ 
                width: '100%',
                theme: 'bootstrap-5'
            });
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