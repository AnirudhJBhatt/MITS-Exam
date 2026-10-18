<?php  
/**
 * Department Administrator - Course Catalog Management
 * Add, CSV batch import, and search courses offered by the department
 */

require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/../Common/auth.php";
require_once __DIR__ . "/../Common/csrf.php";

check_role('DeptAdmin');

$Dept_ID = $_SESSION['LoginDeptAdmin'];
$batchMessage = "";
$batchMsgType = "info";

function selected($field, $value) {
    return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
}

// -----------------------------------------------------------------------------
// 1. TRANSACTIONAL BATCH CSV IMPORT
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_csv'])) {
    if (!verify_csrf(false)) {
        $batchMessage = "Security session expired. Please re-upload the CSV.";
        $batchMsgType = "error";
    } else {
        $records = json_decode($_POST['json_data'] ?? '[]', true);

        if (!empty($records) && is_array($records)) {
            $inserted = 0;
            if ($pdo) {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("
                        INSERT INTO courses (Prog_ID, Course_Code, Course_Name, Credits, Course_Year, Semester, Dept_ID) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            Course_Name = VALUES(Course_Name),
                            Credits = VALUES(Credits),
                            Course_Year = VALUES(Course_Year),
                            Semester = VALUES(Semester)
                    ");

                    foreach ($records as $r) {
                        $pId   = (int)($r['Prog_ID'] ?? 0);
                        $cCode = trim($r['Course_Code'] ?? '');
                        $cName = trim($r['Course_Name'] ?? '');
                        $cCred = (int)($r['Credits'] ?? 4);
                        $cYear = trim($r['Course_Year'] ?? '');
                        $cSem  = (int)($r['Semester'] ?? 1);

                        if ($cCode === '' || $cName === '') continue;

                        $stmt->execute([$pId, $cCode, $cName, $cCred, $cYear, $cSem, $Dept_ID]);
                        $inserted++;
                    }

                    $pdo->commit();
                    $_SESSION['flash_success'] = "Successfully imported {$inserted} courses!";
                    header("Location: manage-courses.php");
                    exit;
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $batchMessage = "Database error: " . $e->getMessage();
                    $batchMsgType = "error";
                }
            } elseif ($con) {
                mysqli_begin_transaction($con);
                try {
                    $stmt = mysqli_prepare($con, "
                        INSERT INTO courses (Prog_ID, Course_Code, Course_Name, Credits, Course_Year, Semester, Dept_ID) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($records as $r) {
                        $pId   = (int)$r['Prog_ID'];
                        $cCode = $r['Course_Code'];
                        $cName = $r['Course_Name'];
                        $cCred = (int)$r['Credits'];
                        $cYear = $r['Course_Year'];
                        $cSem  = (int)$r['Semester'];

                        mysqli_stmt_bind_param($stmt, "issisis", $pId, $cCode, $cName, $cCred, $cYear, $cSem, $Dept_ID);
                        mysqli_stmt_execute($stmt);
                        $inserted++;
                    }
                    mysqli_commit($con);
                    $_SESSION['flash_success'] = "Successfully imported {$inserted} courses!";
                    header("Location: manage-courses.php");
                    exit;
                } catch (Exception $e) {
                    mysqli_rollback($con);
                    $batchMessage = "Database error: " . $e->getMessage();
                    $batchMsgType = "error";
                }
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 2. FETCH PROGRAMMES FOR DROPDOWN
// -----------------------------------------------------------------------------
$programmes = [];
if ($pdo) {
    $stP = $pdo->prepare("SELECT Prog_ID, Prog_Name FROM programmes WHERE Dept_ID = ? ORDER BY Prog_Name ASC");
    $stP->execute([$Dept_ID]);
    $programmes = $stP->fetchAll();
} elseif ($con) {
    $rP = mysqli_query($con, "SELECT Prog_ID, Prog_Name FROM programmes WHERE Dept_ID = '$Dept_ID' ORDER BY Prog_Name ASC");
    while ($row = mysqli_fetch_assoc($rP)) $programmes[] = $row;
}

// -----------------------------------------------------------------------------
// 3. PARSE CSV PREVIEW
// -----------------------------------------------------------------------------
$previewCourses = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Upload_CSV'])) {
    if (!verify_csrf(false)) {
        $batchMessage = "Security session expired. Please re-upload.";
        $batchMsgType = "error";
    } else {
        $selectedProg = (int)($_POST['Stud_Branch'] ?? 0);

        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['csv_file']['tmp_name'];
            if (($handle = fopen($tmpPath, "r")) !== false) {
                $isHeader = true;
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    if ($isHeader) {
                        $isHeader = false;
                        continue;
                    }
                    if (empty($data[0])) continue;

                    $previewCourses[] = [
                        'Prog_ID'     => $selectedProg,
                        'Course_Code' => trim($data[0] ?? ''),
                        'Course_Name' => trim($data[1] ?? ''),
                        'Credits'     => trim($data[2] ?? '4'),
                        'Course_Year' => trim($data[3] ?? '1'),
                        'Semester'    => trim($data[4] ?? '1')
                    ];
                }
                fclose($handle);
            }
        } else {
            $batchMessage = "Please select a valid CSV file.";
            $batchMsgType = "warning";
        }
    }
}

// -----------------------------------------------------------------------------
// 4. SEARCH EXISTING COURSES
// -----------------------------------------------------------------------------
$searchedCourses = null;
$availableYears = [];
$availableSemesters = [];

if (isset($_POST['Search_Courses'])) {
    $filterProg = (int)($_POST['Prog_ID'] ?? 0);

    if ($pdo) {
        $stC = $pdo->prepare("SELECT * FROM courses WHERE Dept_ID = ? AND (? = 0 OR Prog_ID = ?) ORDER BY Course_Year, Semester, Course_Name");
        $stC->execute([$Dept_ID, $filterProg, $filterProg]);
        $searchedCourses = $stC->fetchAll();
    } elseif ($con) {
        $sql = "SELECT * FROM courses WHERE Dept_ID = '$Dept_ID'";
        if ($filterProg > 0) $sql .= " AND Prog_ID = '$filterProg'";
        $sql .= " ORDER BY Course_Year, Semester, Course_Name";
        $rC = mysqli_query($con, $sql);
        $searchedCourses = [];
        while ($row = mysqli_fetch_assoc($rC)) $searchedCourses[] = $row;
    }

    if (!empty($searchedCourses)) {
        foreach ($searchedCourses as $row) {
            $y = (string)$row['Course_Year'];
            $s = (string)$row['Semester'];
            if ($y !== '' && !in_array($y, $availableYears, true)) $availableYears[] = $y;
            if ($s !== '' && !in_array($s, $availableSemesters, true)) $availableSemesters[] = $s;
        }
        sort($availableYears, SORT_NUMERIC);
        sort($availableSemesters, SORT_NUMERIC);
    }
}

include '../Common/header.php';
include '../Common/deptadmin-sidebar.php';
?>

<!-- MAIN CONTENT -->
<main>
    <div class="dashboard-header">
        <div>
            <h4 class="mb-1">Course Catalog Management</h4>
            <p class="mb-0 text-white-50 small">Register and maintain courses, syllabus credits, and semester mappings</p>
        </div>
        <span class="badge bg-white text-danger px-3 py-2 rounded-pill fw-semibold shadow-sm">
            <i class="bi bi-book-half me-1"></i> Dept: <?= htmlspecialchars($Dept_ID) ?>
        </span>
    </div>

    <!-- 1. CSV BATCH UPLOAD -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-danger text-white py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-cloud-arrow-up me-2"></i>Batch Course Upload (CSV)</h6>
            <a href="../Templates/Courses_Template.csv" class="btn btn-sm btn-light rounded-pill px-3 fw-semibold" download>
                <i class="bi bi-file-earmark-arrow-down me-1"></i> Download Template
            </a>
        </div>
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                <?= csrf_field() ?>

                <div class="col-md-5">
                    <label class="form-label small fw-bold text-secondary mb-1">Target Programme</label>
                    <select name="Stud_Branch" class="form-select" required>
                        <option value="">-- Choose Programme --</option>
                        <?php foreach ($programmes as $p): ?>
                            <option value="<?= htmlspecialchars($p['Prog_ID']) ?>"><?= htmlspecialchars($p['Prog_Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label small fw-bold text-secondary mb-1">Upload CSV File</label>
                    <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                </div>

                <div class="col-md-2">
                    <button type="submit" name="Upload_CSV" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-eye me-1"></i> Preview
                    </button>
                </div>
            </form>

            <!-- PREVIEW TABLE -->
            <?php if (!empty($previewCourses)): ?>
                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0">Previewing <?= count($previewCourses) ?> Courses</h6>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1">Ready to Insert</span>
                    </div>

                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="json_data" value='<?= htmlspecialchars(json_encode($previewCourses), ENT_QUOTES, 'UTF-8') ?>'>

                        <div class="table-responsive rounded-3 border mb-3">
                            <table class="table table-hover align-middle mb-0 text-center">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Course Code</th>
                                        <th class="text-start ps-4">Course Title</th>
                                        <th>Credits</th>
                                        <th>Year</th>
                                        <th>Semester</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($previewCourses as $i => $c): ?>
                                        <tr>
                                            <td class="text-muted fw-bold"><?= $i + 1 ?></td>
                                            <td class="fw-semibold text-danger"><?= htmlspecialchars($c['Course_Code']) ?></td>
                                            <td class="text-start ps-4 fw-medium text-dark"><?= htmlspecialchars($c['Course_Name']) ?></td>
                                            <td><span class="badge bg-info bg-opacity-10 text-info px-3 py-1 rounded-pill"><?= htmlspecialchars($c['Credits']) ?></span></td>
                                            <td><?= htmlspecialchars($c['Course_Year']) ?></td>
                                            <td><span class="badge bg-secondary rounded-pill">S<?= htmlspecialchars($c['Semester']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end">
                            <a href="manage-courses.php" class="btn btn-light rounded-pill px-3 me-2">Cancel</a>
                            <button type="submit" name="submit_csv" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Confirm & Import <?= count($previewCourses) ?> Courses
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. SEARCH & INTERACTIVE TABLE -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-dark text-white py-3 px-4">
            <h6 class="mb-0 fw-bold"><i class="bi bi-search me-2"></i>Search Department Courses</h6>
        </div>
        <div class="card-body p-4">
            <form method="POST" class="row g-3 align-items-end mb-4">
                <?= csrf_field() ?>

                <div class="col-md-9">
                    <label class="form-label small fw-bold text-secondary mb-1">Filter by Academic Programme</label>
                    <select name="Prog_ID" class="form-select">
                        <option value="0">-- All Programmes --</option>
                        <?php foreach ($programmes as $p): ?>
                            <option value="<?= htmlspecialchars($p['Prog_ID']) ?>" <?= selected('Prog_ID', $p['Prog_ID']) ?>>
                                <?= htmlspecialchars($p['Prog_Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" name="Search_Courses" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-search me-1"></i> Load Courses
                    </button>
                </div>
            </form>

            <?php if ($searchedCourses !== null): ?>
                <?php if (empty($searchedCourses)): ?>
                    <div class="alert alert-warning text-center py-4 rounded-3 border-0">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No courses found matching the selected programme.
                    </div>
                <?php else: ?>
                    <!-- Client-Side Filter Toolbar -->
                    <div class="card bg-light border-0 rounded-4 p-3 mb-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-secondary mb-1">
                                    <i class="bi bi-search me-1"></i> Instant Filter (Code or Name)
                                </label>
                                <input type="text" id="filterCourseName" class="form-control bg-white" placeholder="Type course code or title...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-secondary mb-1">Filter by Year</label>
                                <select id="filterYear" class="form-select bg-white">
                                    <option value="">-- All Years --</option>
                                    <?php foreach ($availableYears as $y): ?>
                                        <option value="<?= htmlspecialchars($y) ?>">Year <?= htmlspecialchars($y) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-secondary mb-1">Filter by Semester</label>
                                <select id="filterSemester" class="form-select bg-white">
                                    <option value="">-- All Semesters --</option>
                                    <?php foreach ($availableSemesters as $s): ?>
                                        <option value="<?= htmlspecialchars($s) ?>">Semester <?= htmlspecialchars($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="button" id="resetFilters" class="btn btn-outline-secondary w-100" title="Reset Filters">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover align-middle mb-0 text-center" id="coursesTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 15%;">Course Code</th>
                                    <th class="text-start ps-4" style="width: 40%;">Course Title</th>
                                    <th>Year</th>
                                    <th>Semester</th>
                                    <th>Credits</th>
                                    <th style="width: 15%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($searchedCourses as $idx => $row): ?>
                                    <tr class="course-row" 
                                        data-name="<?= htmlspecialchars(strtolower($row['Course_Name'])) ?>" 
                                        data-code="<?= htmlspecialchars(strtolower($row['Course_Code'])) ?>" 
                                        data-year="<?= htmlspecialchars($row['Course_Year']) ?>" 
                                        data-semester="<?= htmlspecialchars($row['Semester']) ?>">
                                        <td class="text-muted fw-bold sl-no"><?= $idx + 1 ?></td>
                                        <td class="fw-semibold text-danger"><?= htmlspecialchars($row['Course_Code']) ?></td>
                                        <td class="text-start ps-4 fw-medium text-dark"><?= htmlspecialchars($row['Course_Name']) ?></td>
                                        <td><?= htmlspecialchars($row['Course_Year']) ?></td>
                                        <td><span class="badge bg-secondary rounded-pill">S<?= htmlspecialchars($row['Semester']) ?></span></td>
                                        <td><span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3"><?= htmlspecialchars($row['Credits']) ?></span></td>
                                        <td>
                                            <a href="edit-course.php?Course_ID=<?= (int)$row['Course_ID'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1" title="Edit Course">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="delete-course.php?Course_ID=<?= (int)$row['Course_ID'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Are you sure you want to delete this course? Associated questions and results may be affected.');" title="Delete Course">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr id="noResultsRow" style="display: none;">
                                    <td colspan="7" class="text-muted py-4 text-center">
                                        <i class="bi bi-search fs-3 d-block mb-1"></i>No courses match the active filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../Common/footer.php'; ?>

<!-- Client-side Interactive Filter Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const courseInput = document.getElementById('filterCourseName');
    const yearSelect = document.getElementById('filterYear');
    const semesterSelect = document.getElementById('filterSemester');
    const resetBtn = document.getElementById('resetFilters');

    if (!courseInput) return;

    function applyFilters() {
        const textVal = courseInput.value.toLowerCase().trim();
        const yearVal = yearSelect.value;
        const semVal = semesterSelect.value;

        const rows = document.querySelectorAll('.course-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const code = row.getAttribute('data-code') || '';
            const year = row.getAttribute('data-year') || '';
            const sem = row.getAttribute('data-semester') || '';

            const matchesText = (textVal === '') || name.includes(textVal) || code.includes(textVal);
            const matchesYear = (yearVal === '') || (year === yearVal);
            const matchesSem = (semVal === '') || (sem === semVal);

            if (matchesText && matchesYear && matchesSem) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const noRow = document.getElementById('noResultsRow');
        if (noRow) {
            noRow.style.display = (visibleCount === 0) ? '' : 'none';
        }
    }

    courseInput.addEventListener('input', applyFilters);
    yearSelect.addEventListener('change', applyFilters);
    semesterSelect.addEventListener('change', applyFilters);

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            courseInput.value = '';
            yearSelect.value = '';
            semesterSelect.value = '';
            applyFilters();
        });
    }
});
</script>

<?php if (!empty($batchMessage)): ?>
<script>
    Swal.fire({
        icon: '<?= $batchMsgType ?>',
        title: 'Notice',
        text: <?= json_encode($batchMessage) ?>,
        confirmButtonColor: '#d1202d'
    });
</script>
<?php endif; ?>