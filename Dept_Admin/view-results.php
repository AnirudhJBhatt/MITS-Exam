<?php  
	session_start();
	if (!isset($_SESSION['LoginDeptAdmin'])) {
        echo "<script>alert('You Are Not Authorize Person For This link'); window.location.href='../index.php';</script>";
        exit;
    }
	require_once "../Connection/connection.php";
    
    $Dept_ID = $_SESSION['LoginDeptAdmin'];
    $qDept = mysqli_query($con, "SELECT * FROM department WHERE Dept_ID='$Dept_ID'");   
    $dept = mysqli_fetch_assoc($qDept);
    $Dept_Code = $dept['Dept_Code'];
    function selected($field, $value) {
        return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
    }
?>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin - View Results</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
	<?php include '../Common/header.php'; ?>
	<?php include '../Common/deptadmin-sidebar.php'; ?>

	<main>
		<div class="dashboard-header">
			<h4 class="mb-0 fw-bold">Results</h4>
		</div>
		    <div class="sub-main">
                <div class="row">
					<div class="col-md-12 container-fluid">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <label>Batch</label>
                                    <select class="form-select" name="Acad_Year" required onchange="this.form.submit();">
										<option>Academic Year</option>
										<?php
                                            $ayquery = mysqli_query($con, "SELECT * FROM academic_year ORDER BY AY_Name");
                                            while($ay = mysqli_fetch_assoc($ayquery)){
                                                $selected = (isset($_POST['Acad_Year']) && $_POST['Acad_Year'] == $ay['AY_Name']) ? 'selected' : '';
                                                echo "<option value='".$ay['AY_Name']."' ".$selected.">".$ay['AY_Name']."</option>";
                                            }
										?>
									</select>
                                </div>
                                <div class="col-md-3">
                                    <label>Semester</label>
                                    <select name="Semester" class="form-select" required onchange="this.form.submit();">
                                        <option value="">Select Semester</option>
                                        <?php for($i=1;$i<=8;$i++){
                                            $selected = (isset($_POST['Semester']) && $_POST['Semester'] == $i) ? 'selected' : '';
                                            echo "<option value='$i' ".$selected.">S$i</option>";
                                        } 
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Subject</label>
                                    <select name="Course_ID" class="form-select" required onchange="this.form.submit();">
                                        <option value="">Select Course</option>
                                        <?php
                                            $Semester  = isset($_POST['Semester']) ? mysqli_real_escape_string($con, $_POST['Semester']) : '';
                                            $squery = "SELECT * FROM courses WHERE Dept_ID = '$Dept_ID' AND Semester = '$Semester' ORDER BY Course_Name";
                                            $run = mysqli_query($con, $squery);
                                            while ($row = mysqli_fetch_assoc($run)) {
                                                $selected = (isset($_POST['Course_ID']) && $_POST['Course_ID'] == $row['Course_ID']) ? 'selected' : '';
                                                echo "<option value='{$row['Course_ID']}' $selected>{$row['Course_Name']}</option>";
                                            }
                                        ?>
                                    </select>
                                </div>

                                
                                <div class="col-md-3">
                                    <label>Exam</label>
                                    <select name="Exam_ID" class="form-select" required>
                                        <option value="">Select Course</option>
                                        <?php
                                            $Acad_Year = isset($_POST['Acad_Year']) ? mysqli_real_escape_string($con, $_POST['Acad_Year']) : '';
                                            $Semester  = isset($_POST['Semester']) ? mysqli_real_escape_string($con, $_POST['Semester']) : '';
                                            $Course_ID  = isset($_POST['Course_ID']) ? mysqli_real_escape_string($con, $_POST['Course_ID']) : '';

                                            if ($Acad_Year != "" && $Semester != "" && $Course_ID != "") {
                                                $equery = "SELECT * FROM exam e, courses c, faculty f 
                                                           WHERE e.Course_ID = c.Course_ID 
                                                           AND e.Fac_ID = f.Fac_ID 
                                                           AND e.Acad_Year = '$Acad_Year' 
                                                           AND e.Semester = '$Semester' 
                                                           AND e.Course_ID = '$Course_ID' 
                                                           AND e.Dept = '$Dept_ID'
                                                           ORDER BY e.Exam_Title";
                                                $run = mysqli_query($con, $equery);
                                                if (!$run || mysqli_num_rows($run) == 0) {
                                                    echo '<option value="">'.$equery.'</option>';
                                                } else {
                                                    while ($row = mysqli_fetch_assoc($run)) {
                                                        $selected = (isset($_POST['Exam_ID']) && $_POST['Exam_ID'] == $row['Exam_ID']) ? 'selected' : '';
                                                        echo "<option value='{$row['Exam_ID']}' $selected>{$row['Exam_Title']}</option>";
                                                    }
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>		
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <input type="submit" name="Submit" value="Search" class="btn btn-primary">
                                </div>
                            </div>			
                        </form>
					</div>
				</div>
				<div class="row">
                    <div class="col-md-12 container-fluid">
                        <?php
                        if (isset($_POST['Submit'])) {
                            $Exam_ID = $_POST['Exam_ID'];
                            $query = "SELECT * FROM result r, student s WHERE r.Stud_ID = s.Stud_ID AND r.Exam_ID = $Exam_ID;";
                            $run = mysqli_query($con, $query);

                            if (mysqli_num_rows($run) > 0) {
                                $Sl = 1;
                        ?>
                        <section class="mt-3">
                            <table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
                                <tr class="table-dark text-white">
                                    <th>SL No</th>
                                    <th>Name</th>
                                    <th>Total Marks</th>
                                    <th>Obtained Marks</th>
                                    <th>Action</th>
                                </tr>
                                <?php
                                while ($row = mysqli_fetch_array($run)) {
                                ?>
                                <tr>
                                    <td><?php echo $Sl++; ?></td>
                                    <td><?php echo htmlspecialchars($row['Stud_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Total_Marks']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Obtained_Marks']); ?></td>
                                    <td class="w-auto">
                                        <button class="btn btn-info view-details" 
                                                data-studid="<?php echo $row['Stud_ID']; ?>" 
                                                data-examid="<?php echo $Exam_ID; ?>">
                                            View Results
                                        </button>
                                    </td>
                                </tr>
                                <?php
                                }
                                ?>
                            </table>
                            <div class="mt-3 text-center">
                                <a href="../Faculty/export-results.php?Exam_ID=<?php echo $Exam_ID; ?>" class="btn btn-primary">Export Results</a>
                            </div>
                        </section>
                        <?php
                            } else {
                                echo "<p class='text-center mt-3 text-danger'>No results found for this exam.</p>";
                                // echo $query;
                            }
                        }
                        ?>

                        <!-- Modal -->
                        <div class="modal fade" id="resultModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="resultModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-dark text-white">
                                        <h5 class="modal-title" id="resultModalLabel">Student Result Details</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <table class="table table-bordered text-center">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Q. No</th>
                                                    <th>Question</th>
                                                    <th>Correct Answer</th>
                                                    <th>Selected Answer</th>
                                                    <th>Marks</th>
                                                </tr>
                                            </thead>
                                            <tbody id="resultDetails">
                                                <!-- AJAX Data will appear here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

			</div>
	</main>
	<?php include '../Common/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.view-details').click(function() {
                var studID = $(this).data('studid');
                var examID = $(this).data('examid');

                $.ajax({
                    url: '../Faculty/fetch-result-details.php',
                    type: 'POST',
                    data: { Stud_ID: studID, Exam_ID: examID },
                    success: function(response) {
                        $('#resultDetails').html(response);
                        var modal = new bootstrap.Modal(document.getElementById('resultModal'));
                        modal.show();
                    }
                });
            });
        });
    </script>

</body>
</html>



            