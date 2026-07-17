<?php  
    session_start();
	if (!isset($_SESSION['LoginDeptAdmin'])) {
        echo "<script>alert('You Are Not Authorize Person For This link'); window.location.href='../index.php';</script>";
        exit;
    }

    require_once "../Connection/connection.php";

    $Dept_ID = $_SESSION['LoginDeptAdmin'];
    function selected($field, $value) {
        return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
    }

?>

<?php
    if (isset($_POST['submit_csv'])) {
        $records = json_decode($_POST['json_data'], true);
        if ($records && is_array($records)) {
            foreach ($records as $record) {
                $Prog_ID = mysqli_real_escape_string($con, $record['Prog_ID']);
                $Course_Code = mysqli_real_escape_string($con, $record['Course_Code']);
                $Course_Name = mysqli_real_escape_string($con, $record['Course_Name']);
                $Credits = mysqli_real_escape_string($con, $record['Credits']);
                $Course_Year = mysqli_real_escape_string($con, $record['Course_Year']);
                $Semester = mysqli_real_escape_string($con, $record['Semester']);
                $Dept_ID = mysqli_real_escape_string($con, $Dept_ID);

                $query = "INSERT INTO courses (Prog_ID, Course_Code, Course_Name, Credits, Course_Year, Semester, Dept_ID) 
                VALUES ('$Prog_ID', '$Course_Code', '$Course_Name', '$Credits', '$Course_Year', '$Semester', '$Dept_ID')";

                $run = mysqli_query($con, $query);

                if (!$run) {
                    echo "<script>alert('Some records failed to insert!'); window.location='manage-courses.php';</script>";
                    exit;
                    // echo $query;
                    // echo mysqli_error($con);
                }
            }
            echo "<script>alert('All courses added successfully!'); window.location = 'manage-courses.php';</script>";
        }
    }
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Courses</title>
</head>

<body>
    <?php include '../Common/header.php'; ?>
    <?php include '../Common/deptadmin-sidebar.php'; ?>
    
    <main>
        <div class="dashboard-header d-flex justify-content-start align-items-center">
            <h4 class="mb-0 fw-bold">Manage Courses</h4>
        </div>
        <div class="card shadow-sm mb-4 mt-4">
            <div class="card-header text-white fw-semibold" style="background-color: #D1202D;">
                Add Courses(CSV)
            </div> 
            <div class="card-body">
                <div class="row">
					<div class="col-md-12 container-fluid">
					    <form method="POST" enctype="multipart/form-data">
						    <div class="row mt-3">
							    <div class="col-md-4">
								    <select name="Stud_Branch" class="form-control" required>
									<option value="" selected disabled>Select Programme</option>
									    <?php
										    $pgquery ="SELECT * FROM programmes WHERE Dept_ID='$Dept_ID' ORDER BY Prog_Name DESC";
											$pgrun = mysqli_query($con, $pgquery);
											while ($pgrow = mysqli_fetch_array($pgrun)) {
											    echo "<option value='" . $pgrow['Prog_ID'] . "'>" . $pgrow['Prog_Name'] . "</option>";
											}
										?>                        
									</select>
								</div>
								<div class="col-md-4">
									<input type="file" class="form-control" name="csv_file" accept=".csv" required>
								</div>
								<div class="col-md-4">
									<input type="submit" class="btn btn-primary" name="Add" value="Upload CSV">
								</div>
							</div>
							<p class="text-muted mt-2">Click here to download template <a href="../Templates/Courses Template.csv" download>Download Template</a></p>
						</form>
					</div>
				</div>
                    <div class="row">
						<div class="col-md-12 container-fluid">
						<?php
							if (isset($_POST['Add'])) {
								$courses = [];
								$selectedProg = $_POST['Stud_Branch'];

								if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
									$file = $_FILES['csv_file']['tmp_name'];
									if (($handle = fopen($file, "r")) !== FALSE) {
										$isHeader = true;
										while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
											if ($isHeader) {
												$isHeader = false;
												continue;
											}
											$courses[] = [
                                                'Prog_ID'       => $selectedProg,
												'Course_Code'   => $data[0],
												'Course_Name'   => $data[1],
												'Credits'       => $data[2],
												'Course_Year'   => $data[3], 
												'Semester'      => $data[4],
											];
										}
										fclose($handle);
									}
								}

								if (!empty($courses)) {
							?>
										<form method="POST">
											<input type="hidden" name="json_data" value='<?= json_encode($courses, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
											<table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5">
												<tr class="table-dark text-white">
													<th>SL No</th>
													<th>Course Code</th>
													<th>Course Name</th>
													<th>Year</th>
													<th>Semester</th>
												</tr>
												<?php foreach ($courses as $i => $course): ?>
													<tr>
														<td><?= $i + 1 ?></td>
														<td><?= htmlspecialchars($course['Course_Code']) ?></td>
														<td><?= htmlspecialchars($course['Course_Name']) ?></td>
														<td><?= htmlspecialchars($course['Course_Year']) ?></td>
														<td><?= htmlspecialchars($course['Semester']) ?></td>
													</tr>
												<?php endforeach; ?>
											</table>
											<div class="text-center mb-5">
												<input type="submit" name="submit_csv" value="Add Courses" class="btn btn-success">
											</div>
										</form>
							<?php
								} else {
									echo "<div class='alert alert-warning mt-3'>No valid student data found in CSV.</div>";
								}
							}
							?>
						</div>
					</div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 mt-4">
            <div class="card-header text-white fw-semibold" style="background-color: #D1202D;">
                Search Courses
            </div> 
            <div class="card-body">
                <!-- Search -->
                <form method="POST" class="row g-3 align-items-end">
                    <!-- Search Box -->
                    <div class="col-md-3">
                        <label for="">Programme</label>
                        <select name="Prog_ID" class="form-control">
                            <option value="">-- Select Programme --</option>
                            <?php
                                $progQuery = "SELECT * FROM programmes WHERE Dept_ID='$Dept_ID' ORDER BY Prog_Name ASC";
                                $progResult = mysqli_query($con, $progQuery);
                                while ($progRow = mysqli_fetch_assoc($progResult)) {
                                    $selected = (isset($_POST['Prog_ID']) && $_POST['Prog_ID'] == $progRow['Prog_ID']) ? "selected" : "";
                                    echo "<option value='" . $progRow['Prog_ID'] . "' $selected>" . $progRow['Prog_Name'] . "</option>";
                                }
                            ?> 
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="">Course Name / Code</label>
                        <input type="text" name="Search_Course" 
                            class="form-control" placeholder="Search Course Name / Code" value="<?php 
                                echo isset($_POST['Search_Course']) ? $_POST['Search_Course'] : ''; ?>">
                    </div>

                    <!-- Year Filter -->
                    <div class="col-md-3">
                        <label for="">Year</label>
                        <select name="Filter_Year" class="form-control">
                            <option value="">-- Select Year --</option>
                            <option value="1"  <?= selected('Filter_Year', '1') ?>>1st Year</option>
                            <option value="2" <?= selected('Filter_Year', '2') ?>>2nd Year</option>
                            <option value="3" <?= selected('Filter_Year', '3') ?>>3rd Year</option>
                            <option value="4" <?= selected('Filter_Year', '4') ?>>4th Year</option>
                        </select>
                    </div>

                    <!-- Semester Filter -->
                    <div class="col-md-3">
                        <label for="">Semester</label>
                        <select name="Filter_Semester" class="form-control">
                            <option value="">Semester</option>
                            <?php 
                                for($i=1;$i<=8;$i++){
                                    echo "<option value='$i' ".selected('Filter_Semester',"$i").">S$i</option>";
                                }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" name="Search" class="btn btn-success w-100">Search</button>
                    </div>
                </form>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <?php  
                            if (isset($_POST['Search'])) {

                                $conditions = [];
                                $search = mysqli_real_escape_string($con, $_POST['Search_Course']);
                                $year = $_POST['Filter_Year'];
                                $semester = $_POST['Filter_Semester'];
                                // Text Search
                                if (!empty($search)) {
                                    $conditions[] = "(Course_Name LIKE '%$search%' OR Course_Code LIKE '%$search%')";
                                }
                                // Programme Filter
                                if (!empty($_POST['Prog_ID'])) {
                                    $prog_id = $_POST['Prog_ID'];
                                    $conditions[] = "Prog_ID = '$prog_id'";
                                }
                                // Year Filter
                                if (!empty($year)) {
                                    $conditions[] = "Course_Year = '$year'";
                                }
                                // Semester Filter
                                if (!empty($semester)) {
                                    $conditions[] = "Semester = '$semester'";
                                }
                                // Always include department
                                $conditions[] = "Dept_ID = '$Dept_ID'";

                                // Build final query
                                $whereSQL = "WHERE " . implode(" AND ", $conditions);

                                $query = "
                                    SELECT * FROM courses
                                    $whereSQL 
                                    ORDER BY Course_Year, Semester, Course_Name
                                ";

                                $run = mysqli_query($con, $query);

                                if (mysqli_num_rows($run) == 0) {
                                    echo "<p class='mt-3 text-danger text-center'>No matching courses found.</p>";
                                } 
                                else {
                        ?>


                        <table class="table table-bordered table-hover text-center border-dark mt-3">
                            <tr class="table-dark text-white">
                                <th>SL No</th>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Credits</th>
                                <th>Action</th>
                            </tr>

                            <?php 
                                $sl = 1;
                                while ($row = mysqli_fetch_assoc($run)) {
                            ?>
                            <tr>
                                <td><?php echo $sl++; ?></td>
                                <td><?php echo $row['Course_Code']; ?></td>
                                <td><?php echo $row['Course_Name']; ?></td>
                                <td><?php echo $row['Course_Year']; ?></td>
                                <td><?php echo $row['Semester']; ?></td>
                                <td><?php echo $row['Credits']; ?></td>

                                <td>
                                    <a href="edit-course.php?Course_ID=<?php echo $row['Course_ID']; ?>" 
                                    class="btn btn-sm btn-primary">Edit</a>

                                    <a href="delete-course.php?Course_ID=<?php echo $row['Course_ID']; ?>" 
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('Delete this course?');">
                                    Delete
                                    </a>
                                </td>
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
        </div>
    </main>

    <?php include '../Common/footer.php'; ?>
</body>
</html>
