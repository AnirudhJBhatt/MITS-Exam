<?php  
/**
 * Department Administrator - Faculty Course Mapping
 * Secure Allocation of Teaching Faculty to Departmental Courses
 */

require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/../Common/auth.php";
require_once __DIR__ . "/../Common/csrf.php";

check_role('DeptAdmin');

$Dept_ID = $_SESSION['LoginDeptAdmin'];

function selected($field, $value) {
    return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
}

/* === SAVE MULTIPLE FACULTY MAPPINGS === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Save_Mappings'])) {
    if (!verify_csrf(false)) {
        $_SESSION['status_msg'] = "Security session expired. Please refresh and try again.";
        $_SESSION['status_type'] = "error";
        header("Location: map-courses.php");
        exit;
    }

    $Acad_Year = trim($_POST['Acad_Year'] ?? '');
    $facultyData = $_POST['Fac_ID'] ?? [];

    $success = 0;
    $already_mapped = 0;

    if (!empty($Acad_Year) && is_array($facultyData)) {
        if ($pdo) {
            $checkStmt = $pdo->prepare("SELECT 1 FROM course_mapping WHERE Course_ID = ? AND Fac_ID = ? AND Acad_Year = ?");
            $insertStmt = $pdo->prepare("INSERT INTO course_mapping (Course_ID, Fac_ID, Dept_ID, Acad_Year) VALUES (?, ?, ?, ?)");
            $courseInfoStmt = $pdo->prepare("SELECT Semester, Prog_ID FROM courses WHERE Course_ID = ?");
            $studentMapStmt = $pdo->prepare("
                INSERT INTO student_course_mapping (Stud_ID, Course_ID, Fac_ID, Dept_ID, Semester, Acad_Year)
                SELECT s.Stud_ID, ?, ?, ?, ?, ?
                FROM student s
                LEFT JOIN student_course_mapping scm 
                    ON scm.Stud_ID = s.Stud_ID 
                    AND scm.Course_ID = ? 
                    AND scm.Fac_ID = ? 
                    AND scm.Acad_Year = ?
                WHERE s.Stud_Dept = ?
                    AND s.Stud_Sem = ?
                    AND s.Stud_Branch = ?
                    AND s.Curr_AY = ?
                    AND scm.Stud_ID IS NULL
            ");

            foreach ($facultyData as $courseId => $faculties) {
                if (!is_array($faculties)) continue;
                $courseId = (int)$courseId;

                foreach ($faculties as $fac) {
                    $fac = trim($fac);
                    if ($fac === '') continue;

                    $checkStmt->execute([$courseId, $fac, $Acad_Year]);
                    if ($checkStmt->fetch()) {
                        $already_mapped++;
                        continue;
                    }

                    $insertStmt->execute([$courseId, $fac, $Dept_ID, $Acad_Year]);

                    // Fetch course sem & prog
                    $courseInfoStmt->execute([$courseId]);
                    $c = $courseInfoStmt->fetch();
                    if ($c) {
                        $sem = (int)$c['Semester'];
                        $prog = (int)$c['Prog_ID'];
                        $studentMapStmt->execute([$courseId, $fac, $Dept_ID, $sem, $Acad_Year, $courseId, $fac, $Acad_Year, $Dept_ID, $sem, $prog, $Acad_Year]);
                    }
                    $success++;
                }
            }
        } elseif ($con) {
            $checkStmt = $con->prepare("SELECT 1 FROM course_mapping WHERE Course_ID = ? AND Fac_ID = ? AND Acad_Year = ?");
            $insertStmt = $con->prepare("INSERT INTO course_mapping (Course_ID, Fac_ID, Dept_ID, Acad_Year) VALUES (?, ?, ?, ?)");
            $cStmt = $con->prepare("SELECT Semester, Prog_ID FROM courses WHERE Course_ID = ?");

            foreach ($facultyData as $courseId => $faculties) {
                if (!is_array($faculties)) continue;
                $courseId = (int)$courseId;

                foreach ($faculties as $fac) {
                    $fac = trim($fac);
                    if ($fac === '') continue;

                    $checkStmt->bind_param("iss", $courseId, $fac, $Acad_Year);
                    $checkStmt->execute();
                    if ($checkStmt->get_result()->num_rows > 0) {
                        $already_mapped++;
                        continue;
                    }

                    $insertStmt->bind_param("isss", $courseId, $fac, $Dept_ID, $Acad_Year);
                    if ($insertStmt->execute()) {
                        $cStmt->bind_param("i", $courseId);
                        $cStmt->execute();
                        $c = $cStmt->get_result()->fetch_assoc();
                        if ($c) {
                            $sem = (int)$c['Semester'];
                            $prog = (int)$c['Prog_ID'];
                            $scmQ = "INSERT INTO student_course_mapping (Stud_ID, Course_ID, Fac_ID, Dept_ID, Semester, Acad_Year)
                                     SELECT s.Stud_ID, '$courseId', '$fac', '$Dept_ID', '$sem', '$Acad_Year'
                                     FROM student s
                                     LEFT JOIN student_course_mapping scm 
                                         ON scm.Stud_ID = s.Stud_ID AND scm.Course_ID = '$courseId' AND scm.Fac_ID = '$fac' AND scm.Acad_Year = '$Acad_Year'
                                     WHERE s.Stud_Dept = '$Dept_ID' AND s.Stud_Sem = '$sem' AND s.Stud_Branch = '$prog' AND s.Curr_AY = '$Acad_Year' AND scm.Stud_ID IS NULL";
                            mysqli_query($con, $scmQ);
                        }
                        $success++;
                    }
                }
            }
        }
    }

    if ($success > 0) {
        $_SESSION['status_msg'] = "{$success} Course mappings saved successfully! " . ($already_mapped > 0 ? "({$already_mapped} already existed)" : "");
        $_SESSION['status_type'] = "success";
    } elseif ($already_mapped > 0) {
        $_SESSION['status_msg'] = "No new mappings added. {$already_mapped} mappings already existed.";
        $_SESSION['status_type'] = "info";
    } else {
        $_SESSION['status_msg'] = "No mappings were saved. Please select valid faculty.";
        $_SESSION['status_type'] = "warning";
    }

    header("Location: map-courses.php");
    exit;
}

// -----------------------------------------------------------------------------
// FETCH DROPDOWN DATA
// -----------------------------------------------------------------------------
$academicYears = [];
$programmes = [];
$allFaculty = [];

if ($pdo) {
    $academicYears = $pdo->query("SELECT AY_Name FROM academic_year ORDER BY AY_Name DESC")->fetchAll(PDO::FETCH_COLUMN);
    $stP = $pdo->prepare("SELECT Prog_ID, Prog_Name FROM programmes WHERE Dept_ID = ? ORDER BY Prog_Name ASC");
    $stP->execute([$Dept_ID]);
    $programmes = $stP->fetchAll();
    $allFaculty = $pdo->query("SELECT Fac_ID, Fac_Name FROM faculty ORDER BY Fac_Name ASC")->fetchAll();
} elseif ($con) {
    $rAY = mysqli_query($con, "SELECT AY_Name FROM academic_year ORDER BY AY_Name DESC");
    while ($r = mysqli_fetch_assoc($rAY)) $academicYears[] = $r['AY_Name'];
    $rP = mysqli_query($con, "SELECT Prog_ID, Prog_Name FROM programmes WHERE Dept_ID = '$Dept_ID' ORDER BY Prog_Name ASC");
    while ($r = mysqli_fetch_assoc($rP)) $programmes[] = $r;
    $rF = mysqli_query($con, "SELECT Fac_ID, Fac_Name FROM faculty ORDER BY Fac_Name ASC");
    while ($r = mysqli_fetch_assoc($rF)) $allFaculty[] = $r;
}

// -----------------------------------------------------------------------------
// FILTER COURSES FOR MAPPING
// -----------------------------------------------------------------------------
$filteredCourses = null;
$filteredAcadYear = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Filter_Courses'])) {
    $filterSem  = trim($_POST['Filter_Semester'] ?? '');
    $filterProg = trim($_POST['Prog_ID'] ?? '');
    $filteredAcadYear = trim($_POST['Acad_Year'] ?? '');

    if ($pdo) {
        $stC = $pdo->prepare("SELECT * FROM courses WHERE Dept_ID = ? AND Semester = ? AND Prog_ID = ? ORDER BY Course_Code ASC");
        $stC->execute([$Dept_ID, $filterSem, $filterProg]);
        $filteredCourses = $stC->fetchAll();
    } elseif ($con) {
        $stC = $con->prepare("SELECT * FROM courses WHERE Dept_ID = ? AND Semester = ? AND Prog_ID = ? ORDER BY Course_Code ASC");
        $stC->bind_param("sii", $Dept_ID, $filterSem, $filterProg);
        $stC->execute();
        $rC = $stC->get_result();
        $filteredCourses = [];
        while ($row = $rC->fetch_assoc()) $filteredCourses[] = $row;
    }
}

include '../Common/header.php';
include '../Common/deptadmin-sidebar.php';
?>

<!-- MAIN CONTENT -->
<main>
    <div class="dashboard-header">
        <div>
            <h4 class="mb-1">Course-Faculty Mapping</h4>
            <p class="mb-0 text-white-50 small">Allocate teaching faculty to courses and synchronize student enrollments</p>
        </div>
        <a href="sync-student-courses.php" class="btn btn-light rounded-pill px-4 fw-semibold shadow-sm text-danger">
            <i class="bi bi-arrow-repeat me-1"></i> Sync Student Enrollments
        </a>
    </div>

    <!-- FILTER CARD -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-dark text-white py-3 px-4">
            <h6 class="mb-0 fw-bold"><i class="bi bi-funnel me-2"></i>Filter Courses for Mapping</h6>
        </div>
        <div class="card-body p-4">
            <form method="POST" class="row g-3 align-items-end">
                <?= csrf_field() ?>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1">Academic Year</label>
                    <select name="Acad_Year" class="form-select" required>
                        <option value="">-- Choose Year --</option>
                        <?php foreach ($academicYears as $ay): ?>
                            <option value="<?= htmlspecialchars($ay) ?>" <?= selected('Acad_Year', $ay) ?>>
                                <?= htmlspecialchars($ay) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1">Semester</label>
                    <select name="Filter_Semester" class="form-select" required>
                        <option value="">-- Choose Semester --</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>" <?= selected('Filter_Semester', $i) ?>>Semester <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1">Programme</label>
                    <select name="Prog_ID" class="form-select" required>
                        <option value="">-- Choose Programme --</option>
                        <?php foreach ($programmes as $p): ?>
                            <option value="<?= htmlspecialchars($p['Prog_ID']) ?>" <?= selected('Prog_ID', $p['Prog_ID']) ?>>
                                <?= htmlspecialchars($p['Prog_Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" name="Filter_Courses" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-search me-1"></i> Load Courses
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MAPPING WORKBENCH -->
    <?php if ($filteredCourses !== null): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-danger text-white py-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-diagram-3 me-2"></i>Allocate Faculty to Courses (<?= htmlspecialchars($filteredAcadYear) ?>)</h6>
                <span class="badge bg-white text-danger fw-semibold px-3 py-1 rounded-pill"><?= count($filteredCourses) ?> Courses Found</span>
            </div>
            <div class="card-body p-4">
                <?php if (empty($filteredCourses)): ?>
                    <div class="alert alert-warning text-center py-4 rounded-3 border-0">
                        <i class="bi bi-exclamation-triangle fs-3 d-block mb-2"></i>
                        No courses found for the selected department, semester, and programme.
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="Acad_Year" value="<?= htmlspecialchars($filteredAcadYear) ?>">

                        <div class="table-responsive rounded-3 border">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 15%;">Course Code</th>
                                        <th style="width: 35%;">Course Name</th>
                                        <th style="width: 45%;">Assigned Faculty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    foreach ($filteredCourses as $idx => $c): 
                                        $cId = $c['Course_ID'];
                                        $mappedFac = [];
                                        if ($pdo) {
                                            $stM = $pdo->prepare("SELECT Fac_ID FROM course_mapping WHERE Course_ID = ? AND Acad_Year = ?");
                                            $stM->execute([$cId, $filteredAcadYear]);
                                            $mappedFac = $stM->fetchAll(PDO::FETCH_COLUMN);
                                        } elseif ($con) {
                                            $rM = mysqli_query($con, "SELECT Fac_ID FROM course_mapping WHERE Course_ID = '$cId' AND Acad_Year = '$filteredAcadYear'");
                                            while ($row = mysqli_fetch_assoc($rM)) $mappedFac[] = $row['Fac_ID'];
                                        }
                                    ?>
                                        <tr>
                                            <td class="text-center text-muted fw-bold"><?= $idx + 1 ?></td>
                                            <td class="fw-semibold text-danger"><?= htmlspecialchars($c['Course_Code']) ?></td>
                                            <td class="fw-medium text-dark"><?= htmlspecialchars($c['Course_Name']) ?></td>
                                            <td>
                                                <div id="faculty-container-<?= $cId ?>">
                                                    <?php if (!empty($mappedFac)): ?>
                                                        <?php foreach ($mappedFac as $mf): ?>
                                                            <div class="faculty-row d-flex gap-2 mb-2">
                                                                <select name="Fac_ID[<?= $cId ?>][]" class="form-select faculty-select">
                                                                    <option value="">-- Choose Faculty --</option>
                                                                    <?php foreach ($allFaculty as $f): ?>
                                                                        <option value="<?= htmlspecialchars($f['Fac_ID']) ?>" <?= ($f['Fac_ID'] == $mf) ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($f['Fac_ID'] . ' - ' . $f['Fac_Name']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <button type="button" class="btn btn-outline-danger btn-sm rounded-circle" style="width: 38px; height: 38px;" onclick="deleteFaculty(this, '<?= $cId ?>', '<?= htmlspecialchars($mf) ?>', '<?= htmlspecialchars($filteredAcadYear) ?>')">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="faculty-row d-flex gap-2 mb-2">
                                                            <select name="Fac_ID[<?= $cId ?>][]" class="form-select faculty-select">
                                                                <option value="">-- Choose Faculty --</option>
                                                                <?php foreach ($allFaculty as $f): ?>
                                                                    <option value="<?= htmlspecialchars($f['Fac_ID']) ?>">
                                                                        <?= htmlspecialchars($f['Fac_ID'] . ' - ' . $f['Fac_Name']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle" style="width: 38px; height: 38px;" onclick="$(this).closest('.faculty-row').remove();">
                                                                <i class="bi bi-x"></i>
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill mt-1" onclick="addFaculty(<?= $cId ?>)">
                                                    <i class="bi bi-plus-lg me-1"></i> Add Co-Faculty
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" name="Save_Mappings" class="btn btn-success rounded-pill px-5 fw-semibold shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Save All Course Mappings
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include '../Common/footer.php'; ?>

<script>
    function addFaculty(courseId) {
        const container = document.getElementById('faculty-container-' + courseId);
        const optionsHtml = `
            <option value="">-- Choose Faculty --</option>
            <?php foreach ($allFaculty as $f): ?>
                <option value="<?= htmlspecialchars($f['Fac_ID']) ?>"><?= htmlspecialchars($f['Fac_ID'] . ' - ' . $f['Fac_Name']) ?></option>
            <?php endforeach; ?>
        `;
        const div = document.createElement('div');
        div.className = 'faculty-row d-flex gap-2 mb-2';
        div.innerHTML = `
            <select name="Fac_ID[${courseId}][]" class="form-select faculty-select">
                ${optionsHtml}
            </select>
            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle" style="width: 38px; height: 38px;" onclick="$(this).closest('.faculty-row').remove();">
                <i class="bi bi-x"></i>
            </button>
        `;
        container.appendChild(div);
        $(div).find('.faculty-select').select2({ width: '100%', theme: 'bootstrap-5' });
    }

    function deleteFaculty(button, courseId, facultyId, acadYear) {
        if (!facultyId) {
            $(button).closest('.faculty-row').remove();
            return;
        }

        Swal.fire({
            title: 'Remove Faculty Mapping?',
            text: 'This will unassign this faculty member from this course for the selected academic term.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d1202d',
            cancelButtonColor: '#64748B',
            confirmButtonText: 'Yes, Remove'
        }).then((res) => {
            if (res.isConfirmed) {
                $.ajax({
                    url: 'delete-course-faculty.php',
                    type: 'POST',
                    data: { course: courseId, faculty: facultyId, acad_year: acadYear },
                    success: function (resp) {
                        if (resp.trim() === 'success') {
                            $(button).closest('.faculty-row').fadeOut(250, function () { $(this).remove(); });
                            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Mapping removed', showConfirmButton: false, timer: 2000 });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to remove mapping.' });
                        }
                    }
                });
            }
        });
    }

    $(document).ready(function () {
        $('.faculty-select').select2({ width: '100%', theme: 'bootstrap-5' });
    });
</script>

<?php if (isset($_SESSION['status_msg'])): ?>
<script>
    Swal.fire({
        icon: '<?= $_SESSION['status_type'] ?>',
        title: '<?= $_SESSION['status_type'] === 'success' ? 'Success' : 'Notice' ?>',
        text: <?= json_encode($_SESSION['status_msg']) ?>,
        confirmButtonColor: '#d1202d'
    });
</script>
<?php 
    unset($_SESSION['status_msg'], $_SESSION['status_type']);
endif; 
?>