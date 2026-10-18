<?php  
/**
 * Department Administrator - Student Cohort Management
 * Features Transactional Batch CSV Onboarding with Bcrypt Credentials & Parametric Search
 */

require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/../Common/auth.php";
require_once __DIR__ . "/../Common/csrf.php";

check_role('DeptAdmin');

$Dept_ID = $_SESSION['LoginDeptAdmin'];
$batchMessage = "";
$batchMsgType = "info";

// Helper for select dropdowns
function selected($field, $value) {
    return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
}

// -----------------------------------------------------------------------------
// 1. CONFIRM & BATCH INSERT STUDENTS INTO DATABASE (TRANSACTIONAL)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_csv'])) {
    if (!verify_csrf(false)) {
        $batchMessage = "Security session expired. Please re-upload the CSV file.";
        $batchMsgType = "error";
    } else {
        $records = json_decode($_POST['json_data'] ?? '[]', true);

        if (!empty($records) && is_array($records)) {
            $inserted = 0;
            $updated = 0;
            $failed = 0;
            $defaultPasswordHash = password_hash('Student123*', PASSWORD_BCRYPT);

            if ($pdo) {
                try {
                    $pdo->beginTransaction();

                    $stmtStudent = $pdo->prepare("
                        INSERT INTO student (Stud_ID, Stud_Name, Stud_Email, Stud_Prog, Stud_Branch, Stud_Dept, Stud_Year)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            Stud_Name = VALUES(Stud_Name),
                            Stud_Prog = VALUES(Stud_Prog),
                            Stud_Branch = VALUES(Stud_Branch),
                            Stud_Year = VALUES(Stud_Year)
                    ");

                    $stmtLogin = $pdo->prepare("
                        INSERT INTO login (ID, User_ID, Password, Role, Status)
                        VALUES (?, ?, ?, 'Student', 'Activate')
                        ON DUPLICATE KEY UPDATE Status = VALUES(Status)
                    ");

                    foreach ($records as $r) {
                        $sId    = trim($r['Stud_ID'] ?? '');
                        $sName  = trim($r['Stud_Name'] ?? '');
                        $sEmail = trim($r['Stud_Email'] ?? '');
                        $sProg  = trim($r['Stud_Prog'] ?? '');
                        $sBranch= trim($r['Stud_Branch'] ?? '');
                        $sYear  = trim($r['Stud_Year'] ?? '');

                        if ($sId === '' || $sEmail === '') {
                            $failed++;
                            continue;
                        }

                        $stmtStudent->execute([$sId, $sName, $sEmail, $sProg, $sBranch, $Dept_ID, $sYear]);
                        $stmtLogin->execute([$sId, $sEmail, $defaultPasswordHash]);
                        $inserted++;
                    }

                    $pdo->commit();
                    $_SESSION['flash_success'] = "Successfully processed {$inserted} student records!";
                    header("Location: manage-student.php");
                    exit;
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $batchMessage = "Database transaction failed: " . $e->getMessage();
                    $batchMsgType = "error";
                }
            } elseif ($con) {
                mysqli_begin_transaction($con);
                try {
                    $stmt1 = mysqli_prepare($con, "
                        INSERT INTO student (Stud_ID, Stud_Name, Stud_Email, Stud_Prog, Stud_Branch, Stud_Dept, Stud_Year)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE Stud_Name=VALUES(Stud_Name), Stud_Prog=VALUES(Stud_Prog), Stud_Branch=VALUES(Stud_Branch), Stud_Year=VALUES(Stud_Year)
                    ");
                    $stmt2 = mysqli_prepare($con, "
                        INSERT INTO login (ID, User_ID, Password, Role, Status)
                        VALUES (?, ?, ?, 'Student', 'Activate')
                        ON DUPLICATE KEY UPDATE Status=VALUES(Status)
                    ");

                    foreach ($records as $r) {
                        $sId = $r['Stud_ID']; $sName = $r['Stud_Name']; $sEmail = $r['Stud_Email'];
                        $sProg = $r['Stud_Prog']; $sBranch = $r['Stud_Branch']; $sYear = $r['Stud_Year'];

                        mysqli_stmt_bind_param($stmt1, "sssssss", $sId, $sName, $sEmail, $sProg, $sBranch, $Dept_ID, $sYear);
                        mysqli_stmt_execute($stmt1);

                        mysqli_stmt_bind_param($stmt2, "sss", $sId, $sEmail, $defaultPasswordHash);
                        mysqli_stmt_execute($stmt2);
                        $inserted++;
                    }

                    mysqli_commit($con);
                    $_SESSION['flash_success'] = "Successfully processed {$inserted} student records!";
                    header("Location: manage-student.php");
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
// 2. FETCH AVAILABLE PROGRAMMES FOR DEPARTMENT
// -----------------------------------------------------------------------------
$programmesList = [];
if ($pdo) {
    $stP = $pdo->prepare("SELECT Prog_ID, Prog_Name FROM programmes WHERE Dept_ID = ? ORDER BY Prog_Name ASC");
    $stP->execute([$Dept_ID]);
    $programmesList = $stP->fetchAll();
} elseif ($con) {
    $rP = mysqli_query($con, "SELECT Prog_ID, Prog_Name FROM programmes WHERE Dept_ID = '$Dept_ID' ORDER BY Prog_Name ASC");
    while ($row = mysqli_fetch_assoc($rP)) $programmesList[] = $row;
}

// Fetch Batches
$batchesList = [];
if ($pdo) {
    $stB = $pdo->prepare("SELECT DISTINCT Stud_Year FROM student WHERE Stud_Dept = ? AND Stud_Year != '' ORDER BY Stud_Year DESC");
    $stB->execute([$Dept_ID]);
    $batchesList = $stB->fetchAll(PDO::FETCH_COLUMN);
} elseif ($con) {
    $rB = mysqli_query($con, "SELECT DISTINCT Stud_Year FROM student WHERE Stud_Dept = '$Dept_ID' AND Stud_Year != '' ORDER BY Stud_Year DESC");
    while ($row = mysqli_fetch_assoc($rB)) $batchesList[] = $row['Stud_Year'];
}

// -----------------------------------------------------------------------------
// 3. HANDLE CSV PARSE PREVIEW
// -----------------------------------------------------------------------------
$previewStudents = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Upload_CSV'])) {
    if (!verify_csrf(false)) {
        $batchMessage = "Security session expired. Please re-upload.";
        $batchMsgType = "error";
    } else {
        $selectedProg = trim($_POST['Stud_Branch'] ?? '');

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

                    $previewStudents[] = [
                        'Stud_ID'     => trim($data[0] ?? ''),
                        'Stud_Name'   => trim($data[1] ?? ''),
                        'Stud_Email'  => trim($data[2] ?? ''),
                        'Stud_Prog'   => trim($data[3] ?? $selectedProg),
                        'Stud_Branch' => $selectedProg,
                        'Stud_Year'   => trim($data[4] ?? '')
                    ];
                }
                fclose($handle);
            }
        } else {
            $batchMessage = "Please select a valid CSV file to upload.";
            $batchMsgType = "warning";
        }
    }
}

// -----------------------------------------------------------------------------
// 4. FILTER / SEARCH EXISTING STUDENTS
// -----------------------------------------------------------------------------
$studentsFound = null;
if (isset($_POST['Search_Students'])) {
    $filterProg = trim($_POST['Prog_ID'] ?? '');
    $filterYear = trim($_POST['Stud_Year'] ?? '');

    if ($pdo) {
        $sql = "SELECT s.*, p.Prog_Name 
                FROM student s 
                LEFT JOIN programmes p ON s.Stud_Branch = p.Prog_ID 
                WHERE s.Stud_Dept = ? ";
        $params = [$Dept_ID];

        if ($filterProg !== '') {
            $sql .= " AND s.Stud_Branch = ? ";
            $params[] = $filterProg;
        }
        if ($filterYear !== '') {
            $sql .= " AND s.Stud_Year = ? ";
            $params[] = $filterYear;
        }
        $sql .= " ORDER BY s.Stud_Name ASC";

        $stSearch = $pdo->prepare($sql);
        $stSearch->execute($params);
        $studentsFound = $stSearch->fetchAll();
    } elseif ($con) {
        $filterProgEsc = mysqli_real_escape_string($con, $filterProg);
        $filterYearEsc = mysqli_real_escape_string($con, $filterYear);
        $sql = "SELECT s.*, p.Prog_Name FROM student s LEFT JOIN programmes p ON s.Stud_Branch = p.Prog_ID WHERE s.Stud_Dept = '$Dept_ID'";
        if ($filterProg !== '') $sql .= " AND s.Stud_Branch = '$filterProgEsc'";
        if ($filterYear !== '') $sql .= " AND s.Stud_Year = '$filterYearEsc'";
        $sql .= " ORDER BY s.Stud_Name ASC";
        $rSearch = mysqli_query($con, $sql);
        $studentsFound = [];
        while ($row = mysqli_fetch_assoc($rSearch)) $studentsFound[] = $row;
    }
}

include '../Common/header.php';
include '../Common/deptadmin-sidebar.php';
?>

<!-- MAIN CONTENT -->
<main>
    <div class="dashboard-header">
        <div>
            <h4 class="mb-1">Student Registry & Batch Onboarding</h4>
            <p class="mb-0 text-white-50 small">Upload Students via CSV or manage existing departmental rosters</p>
        </div>
        <span class="badge bg-white text-danger px-3 py-2 rounded-pill fw-semibold shadow-sm">
            <i class="bi bi-people-fill me-1"></i> Department: <?= htmlspecialchars($Dept_ID, ENT_QUOTES, 'UTF-8') ?>
        </span>
    </div>

    <!-- 1. CSV BATCH UPLOAD CARD -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-danger text-white py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-cloud-arrow-up me-2"></i>Batch Student Onboarding (CSV)</h6>
            <a href="../Templates/Student Template.csv" class="btn btn-sm btn-light rounded-pill px-3 fw-semibold" download>
                <i class="bi bi-file-earmark-arrow-down me-1"></i> Download Template
            </a>
        </div>
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                <?= csrf_field() ?>

                <div class="col-md-5">
                    <label class="form-label small fw-bold text-secondary mb-1">Target Academic Programme</label>
                    <select name="Stud_Branch" class="form-select" required>
                        <option value="">-- Choose Programme --</option>
                        <?php foreach ($programmesList as $p): ?>
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

            <!-- CSV PREVIEW TABLE -->
            <?php if (!empty($previewStudents)): ?>
                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0">Previewing <?= count($previewStudents) ?> Records</h6>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1">Ready to Insert</span>
                    </div>

                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="json_data" value='<?= htmlspecialchars(json_encode($previewStudents), ENT_QUOTES, 'UTF-8') ?>'>

                        <div class="table-responsive rounded-3 border mb-3">
                            <table class="table table-hover align-middle mb-0 text-center">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Email Address</th>
                                        <th>Batch</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($previewStudents as $i => $s): ?>
                                        <tr>
                                            <td class="text-muted fw-bold"><?= $i + 1 ?></td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($s['Stud_ID']) ?></td>
                                            <td><?= htmlspecialchars($s['Stud_Name']) ?></td>
                                            <td><?= htmlspecialchars($s['Stud_Email']) ?></td>
                                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= htmlspecialchars($s['Stud_Year']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end">
                            <a href="manage-student.php" class="btn btn-light rounded-pill px-3 me-2">Cancel</a>
                            <button type="submit" name="submit_csv" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Confirm & Import <?= count($previewStudents) ?> Students
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. SEARCH & FILTER EXISTING STUDENTS -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-dark text-white py-3 px-4">
            <h6 class="mb-0 fw-bold"><i class="bi bi-people me-2"></i>Department Student Directory</h6>
        </div>
        <div class="card-body p-4">
            <form method="POST" class="row g-3 align-items-end mb-4">
                <?= csrf_field() ?>

                <div class="col-md-5">
                    <label class="form-label small fw-bold text-secondary mb-1">Filter by Programme</label>
                    <select name="Prog_ID" class="form-select">
                        <option value="">-- All Programmes --</option>
                        <?php foreach ($programmesList as $p): ?>
                            <option value="<?= htmlspecialchars($p['Prog_ID']) ?>" <?= selected('Prog_ID', $p['Prog_ID']) ?>>
                                <?= htmlspecialchars($p['Prog_Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label small fw-bold text-secondary mb-1">Filter by Batch</label>
                    <select name="Stud_Year" class="form-select">
                        <option value="">-- All Batches --</option>
                        <?php foreach ($batchesList as $yr): ?>
                            <option value="<?= htmlspecialchars($yr) ?>" <?= selected('Stud_Year', $yr) ?>>
                                <?= htmlspecialchars($yr) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" name="Search_Students" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-search me-1"></i> Search
                    </button>
                </div>
            </form>

            <!-- SEARCH RESULTS TABLE -->
            <?php if ($studentsFound !== null): ?>
                <?php if (empty($studentsFound)): ?>
                    <div class="alert alert-warning text-center py-4 rounded-3 border-0">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No students found matching the selected criteria.
                    </div>
                <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0">Total Found: <?= count($studentsFound) ?> Students</h6>
                    </div>

                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Programme</th>
                                    <th>Batch</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($studentsFound as $idx => $st): ?>
                                    <tr>
                                        <td class="text-muted fw-bold"><?= $idx + 1 ?></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($st['Stud_ID']) ?></td>
                                        <td class="text-start ps-3 fw-medium"><?= htmlspecialchars($st['Stud_Name']) ?></td>
                                        <td class="text-secondary small"><?= htmlspecialchars($st['Stud_Email']) ?></td>
                                        <td>
                                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1">
                                                <?= htmlspecialchars($st['Prog_Name'] ?? $st['Stud_Prog']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                <?= htmlspecialchars($st['Stud_Year']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../Common/footer.php'; ?>

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