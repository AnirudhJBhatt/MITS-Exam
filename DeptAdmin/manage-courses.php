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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dept Admin - Manage Courses</title>
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
        <div class="dashboard-header">
            <h4 class="mb-0 fw-bold">Manage Courses</h4>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4 hover-elevate">
            <div class="card-header bg-light border-0 py-3 px-4">
                <h6 class="mb-0 fw-bold text-uppercase"><i class="ti ti-file-upload me-2"></i>Add Courses (CSV)</h6>
            </div>
            <div class="card-body px-4 py-3">
                <div class="row">
                    <div class="col-md-12">
                        <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-secondary mb-1">Select Programme</label>
                                <select name="Stud_Branch" class="form-select shadow-none border-secondary-subtle" required>
                                    <option value="" selected disabled>-- Select --</option>
                                    <?php
										$pgquery ="SELECT * FROM programmes WHERE Dept_ID='$Dept_ID' ORDER BY Prog_Name DESC";
										$pgrun = mysqli_query($con, $pgquery);
										while ($pgrow = mysqli_fetch_array($pgrun)) {
											echo "<option value='" . $pgrow['Prog_ID'] . "'>" . $pgrow['Prog_Name'] . "</option>";
										}
									?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-secondary mb-1">Upload CSV</label>
                                <input type="file" class="form-control shadow-none border-secondary-subtle" name="csv_file" accept=".csv" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary px-4 fw-bold w-100" name="Add"><i class="ti ti-upload me-1"></i>Upload CSV</button>
                            </div>
                            <div class="col-md-12">
                                <a href="../Templates/Courses_Template.csv" class="btn btn-sm btn-outline-secondary rounded-pill px-4" download><i class="ti ti-download me-1"></i>Download CSV Template</a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
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
                            <div class="table-responsive rounded-3 border mt-4">
                                <table class="table table-hover align-middle mb-0 text-center">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="py-3 text-secondary fw-semibold">SL No</th>
                                            <th class="py-3 text-secondary fw-semibold">Course Code</th>
                                            <th class="py-3 text-secondary fw-semibold">Course Name</th>
                                            <th class="py-3 text-secondary fw-semibold">Year</th>
                                            <th class="py-3 text-secondary fw-semibold">Semester</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($courses as $i => $course): ?>
                                        <tr>
                                            <td class="text-muted fw-bold">
                                                <?= $i + 1 ?>
                                            </td>
                                            <td class="fw-semibold text-dark">
                                                <?= htmlspecialchars($course['Course_Code']) ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($course['Course_Name']) ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($course['Course_Year']) ?>
                                            </td>
                                            <td><span class="badge bg-secondary rounded-pill">S
                                                    <?= htmlspecialchars($course['Semester']) ?>
                                                </span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-4 mb-2">
                                <button type="submit" name="submit_csv"
                                    class="btn btn-success px-4 fw-bold shadow"><i
                                        class="ti ti-check me-2"></i>Confirm & Add Courses</button>
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

        <div class="card shadow-sm border-0 rounded-4 mb-4 hover-elevate">
            <div class="card-header bg-light border-0 py-3 px-4">
                <h6 class="mb-0 fw-bold text-uppercase"><i class="ti ti-search me-2"></i>Search Courses</h6>
            </div>
            <div class="card-body px-4 py-3">
                <!-- Search -->
                <form method="POST" class="row g-3 align-items-end">
                    <!-- Search Box -->
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary mb-1">Programme</label>
                        <select name="Prog_ID" class="form-select shadow-none border-secondary-subtle">
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
                    <div class="col-md-2">
                        <button type="submit" name="Search"
                            class="btn btn-success fw-bold w-100 shadow-sm"><i
                                class="ti ti-search me-1"></i>Search</button>
                    </div>
                </form>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <?php  
                            if (isset($_POST['Search'])) {  
                                $prog_id = mysqli_real_escape_string($con, $_POST['Prog_ID']);
                                $query = "SELECT * FROM courses WHERE Dept_ID = '$Dept_ID' AND Prog_ID = '$prog_id' ORDER BY Course_Year, Semester, Course_Name";
                                $run = mysqli_query($con, $query);

                                if (mysqli_num_rows($run) == 0) {
                                    echo "<p class='mt-3 text-danger text-center'>No matching courses found.</p>";
                                } 
                                else {
                                    $courses = [];
                                    $availableYears = [];
                                    $availableSemesters = [];
                                    while ($row = mysqli_fetch_assoc($run)) {
                                        $courses[] = $row;
                                        $y = (string)$row['Course_Year'];
                                        $s = (string)$row['Semester'];
                                        if ($y !== '' && !in_array($y, $availableYears, true)) {
                                            $availableYears[] = $y;
                                        }
                                        if ($s !== '' && !in_array($s, $availableSemesters, true)) {
                                            $availableSemesters[] = $s;
                                        }
                                    }
                                    sort($availableYears, SORT_NUMERIC);
                                    sort($availableSemesters, SORT_NUMERIC);
                        ?>
                        <!-- 3 JS Filters: Course Name, Year & Semester -->
                        <div class="card bg-light border-0 rounded-4 p-3 mb-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-secondary mb-1">
                                        <i class="ti ti-search me-1"></i>Filter by Course Name / Code
                                    </label>
                                    <input type="text" id="filterCourseName" class="form-control shadow-none border-secondary-subtle bg-white" placeholder="Type course name or code...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary mb-1">
                                        <i class="ti ti-calendar me-1"></i>Filter by Year
                                    </label>
                                    <select id="filterYear" class="form-select shadow-none border-secondary-subtle bg-white">
                                        <option value="">-- All Years --</option>
                                        <?php 
                                            $yearLabels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
                                            foreach ($availableYears as $y): 
                                                $label = isset($yearLabels[$y]) ? $yearLabels[$y] : "Year $y";
                                        ?>
                                            <option value="<?php echo htmlspecialchars($y); ?>"><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary mb-1">
                                        <i class="ti ti-books me-1"></i>Filter by Semester
                                    </label>
                                    <select id="filterSemester" class="form-select shadow-none border-secondary-subtle bg-white">
                                        <option value="">-- All Semesters --</option>
                                        <?php foreach ($availableSemesters as $s): ?>
                                            <option value="<?php echo htmlspecialchars($s); ?>">S<?php echo htmlspecialchars($s); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" id="resetFilters" class="btn btn-outline-secondary fw-semibold w-100 shadow-sm" title="Reset Filters">
                                        <i class="ti ti-refresh"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive border rounded-4 mt-2">
                            <table class="table table-hover align-middle mb-0 text-center" id="coursesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="py-3 text-secondary fw-semibold">SL No</th>
                                        <th class="py-3 text-secondary fw-semibold">Course Code</th>
                                        <th class="py-3 text-secondary fw-semibold">Course Name</th>
                                        <th class="py-3 text-secondary fw-semibold">Year</th>
                                        <th class="py-3 text-secondary fw-semibold">Semester</th>
                                        <th class="py-3 text-secondary fw-semibold">Credits</th>
                                        <th class="py-3 text-secondary fw-semibold">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $sl = 1;
                                    foreach ($courses as $row) {
                                ?>
                                    <tr class="course-row" 
                                        data-name="<?php echo htmlspecialchars(strtolower($row['Course_Name'])); ?>" 
                                        data-code="<?php echo htmlspecialchars(strtolower($row['Course_Code'])); ?>" 
                                        data-year="<?php echo htmlspecialchars($row['Course_Year']); ?>" 
                                        data-semester="<?php echo htmlspecialchars($row['Semester']); ?>">
                                        <td class="text-muted fw-bold sl-no"><?php echo $sl++; ?></td>
                                        <td class="fw-semibold text-dark"><?php echo $row['Course_Code']; ?></td>
                                        <td><?php echo $row['Course_Name']; ?></td>
                                        <td><?php echo $row['Course_Year']; ?></td>
                                        <td><span class="badge bg-secondary rounded-pill">S<?php echo $row['Semester']; ?></span></td>
                                        <td><span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3"><?php echo $row['Credits']; ?></span></td>
                                        <td>
                                            <a href="edit-course.php?Course_ID=<?php echo $row['Course_ID']; ?>" class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-semibold"><i class="ti ti-edit me-1"></i></a>
                                            <a href="delete-course.php?Course_ID=<?php echo $row['Course_ID']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-semibold ms-1" onclick="return confirm('Delete this course?');"><i class="ti ti-trash me-1"></i></a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                    <tr id="noResultsRow" style="display: none;">
                                        <td colspan="7" class="text-muted py-4 text-center">
                                            <i class="ti ti-mood-empty fs-3 d-block mb-1"></i>No courses match the selected filters.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <?php 
                                } // end else
                            } // end if searched
                        ?>
                    </div>
                </div>
            </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const courseInput = document.getElementById('filterCourseName');
        const yearSelect = document.getElementById('filterYear');
        const semesterSelect = document.getElementById('filterSemester');
        const resetBtn = document.getElementById('resetFilters');
        const rows = document.querySelectorAll('.course-row');
        const noResultsRow = document.getElementById('noResultsRow');

        if (!rows.length) return;

        // Cache all available semester options rendered from server
        const allSemesterOptions = semesterSelect ? Array.from(semesterSelect.options).map(opt => ({
            value: opt.value,
            text: opt.text
        })) : [];

        function updateAvailableSemesters() {
            if (!semesterSelect || !yearSelect) return;
            const selectedYear = yearSelect.value.trim();
            const currentSem = semesterSelect.value.trim();

            if (!selectedYear) {
                semesterSelect.innerHTML = '';
                allSemesterOptions.forEach(opt => {
                    const el = document.createElement('option');
                    el.value = opt.value;
                    el.textContent = opt.text;
                    if (opt.value === currentSem) el.selected = true;
                    semesterSelect.appendChild(el);
                });
                return;
            }

            const availableSemsForYear = new Set();
            rows.forEach(r => {
                if (r.getAttribute('data-year') === selectedYear) {
                    availableSemsForYear.add(r.getAttribute('data-semester'));
                }
            });

            semesterSelect.innerHTML = '';
            let keepCurrentSem = false;
            allSemesterOptions.forEach(opt => {
                if (opt.value === '' || availableSemsForYear.has(opt.value)) {
                    const el = document.createElement('option');
                    el.value = opt.value;
                    el.textContent = opt.text;
                    if (opt.value === currentSem) {
                        el.selected = true;
                        keepCurrentSem = true;
                    }
                    semesterSelect.appendChild(el);
                }
            });

            if (!keepCurrentSem) {
                semesterSelect.value = '';
            }
        }

        function applyFilters() {
            const courseTerm = (courseInput ? courseInput.value : '').toLowerCase().trim();
            const selectedYear = (yearSelect ? yearSelect.value : '').trim();
            const selectedSem = (semesterSelect ? semesterSelect.value : '').trim();

            let visibleCount = 0;

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const code = row.getAttribute('data-code') || '';
                const year = row.getAttribute('data-year') || '';
                const semester = row.getAttribute('data-semester') || '';

                const matchesName = !courseTerm || name.includes(courseTerm) || code.includes(courseTerm);
                const matchesYear = !selectedYear || year === selectedYear;
                const matchesSem = !selectedSem || semester === selectedSem;

                if (matchesName && matchesYear && matchesSem) {
                    row.style.display = '';
                    visibleCount++;
                    const slCell = row.querySelector('.sl-no');
                    if (slCell) {
                        slCell.textContent = visibleCount;
                    }
                } else {
                    row.style.display = 'none';
                }
            });

            if (noResultsRow) {
                noResultsRow.style.display = (visibleCount === 0) ? '' : 'none';
            }
        }

        if (courseInput) {
            courseInput.addEventListener('input', applyFilters);
            courseInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') e.preventDefault();
            });
        }
        if (yearSelect) {
            yearSelect.addEventListener('change', function () {
                updateAvailableSemesters();
                applyFilters();
            });
        }
        if (semesterSelect) {
            semesterSelect.addEventListener('change', applyFilters);
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (courseInput) courseInput.value = '';
                if (yearSelect) yearSelect.value = '';
                if (semesterSelect) {
                    semesterSelect.innerHTML = '';
                    allSemesterOptions.forEach(opt => {
                        const el = document.createElement('option');
                        el.value = opt.value;
                        el.textContent = opt.text;
                        semesterSelect.appendChild(el);
                    });
                    semesterSelect.value = '';
                }
                applyFilters();
            });
        }
    });
    </script>
    <?php include '../Common/footer.php'; ?>
</body>

</html>