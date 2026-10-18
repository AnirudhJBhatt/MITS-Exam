<?php  
/**
 * Department Administrator - Faculty Directory Management
 * Transactional CSV Batch Onboarding with Bcrypt Credentials & Parametric Search
 */

require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/../Common/auth.php";
require_once __DIR__ . "/../Common/csrf.php";

check_role('DeptAdmin');

$Dept_ID = $_SESSION['LoginDeptAdmin'];
$batchMessage = "";
$batchMsgType = "info";

// -----------------------------------------------------------------------------
// 1. TRANSACTIONAL BATCH CSV IMPORT
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_csv'])) {
    if (!verify_csrf(false)) {
        $batchMessage = "Security session expired. Please re-upload the CSV file.";
        $batchMsgType = "error";
    } else {
        $records = json_decode($_POST['json_data'] ?? '[]', true);

        if (!empty($records) && is_array($records)) {
            $inserted = 0;
            $defaultPasswordHash = password_hash('Faculty123*', PASSWORD_BCRYPT);

            if ($pdo) {
                try {
                    $pdo->beginTransaction();

                    $stmtFaculty = $pdo->prepare("
                        INSERT INTO faculty (Fac_ID, Fac_Name, Fac_Email, Fac_Dept)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            Fac_Name = VALUES(Fac_Name),
                            Fac_Email = VALUES(Fac_Email),
                            Fac_Dept = VALUES(Fac_Dept)
                    ");

                    $stmtLogin = $pdo->prepare("
                        INSERT INTO login (ID, User_ID, Password, Role, Status)
                        VALUES (?, ?, ?, 'Faculty', 'Activate')
                        ON DUPLICATE KEY UPDATE Status = VALUES(Status)
                    ");

                    foreach ($records as $r) {
                        $fId    = trim($r['Fac_ID'] ?? '');
                        $fName  = trim($r['Fac_Name'] ?? '');
                        $fEmail = trim($r['Fac_Email'] ?? '');

                        if ($fId === '' || $fEmail === '') continue;

                        $stmtFaculty->execute([$fId, $fName, $fEmail, $Dept_ID]);
                        $stmtLogin->execute([$fId, $fEmail, $defaultPasswordHash]);
                        $inserted++;
                    }

                    $pdo->commit();
                    $_SESSION['flash_success'] = "Successfully onboarded {$inserted} faculty members!";
                    header("Location: manage-faculty.php");
                    exit;
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $batchMessage = "Database error: " . $e->getMessage();
                    $batchMsgType = "error";
                }
            } elseif ($con) {
                mysqli_begin_transaction($con);
                try {
                    $stmt1 = mysqli_prepare($con, "
                        INSERT INTO faculty (Fac_ID, Fac_Name, Fac_Email, Fac_Dept)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE Fac_Name=VALUES(Fac_Name), Fac_Email=VALUES(Fac_Email), Fac_Dept=VALUES(Fac_Dept)
                    ");
                    $stmt2 = mysqli_prepare($con, "
                        INSERT INTO login (ID, User_ID, Password, Role, Status)
                        VALUES (?, ?, ?, 'Faculty', 'Activate')
                        ON DUPLICATE KEY UPDATE Status=VALUES(Status)
                    ");

                    foreach ($records as $r) {
                        $fId = $r['Fac_ID']; $fName = $r['Fac_Name']; $fEmail = $r['Fac_Email'];
                        if (empty($fId) || empty($fEmail)) continue;

                        mysqli_stmt_bind_param($stmt1, "ssss", $fId, $fName, $fEmail, $Dept_ID);
                        mysqli_stmt_execute($stmt1);

                        mysqli_stmt_bind_param($stmt2, "sss", $fId, $fEmail, $defaultPasswordHash);
                        mysqli_stmt_execute($stmt2);
                        $inserted++;
                    }

                    mysqli_commit($con);
                    $_SESSION['flash_success'] = "Successfully onboarded {$inserted} faculty members!";
                    header("Location: manage-faculty.php");
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
// 2. CSV PARSE PREVIEW
// -----------------------------------------------------------------------------
$previewFaculty = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Upload_CSV'])) {
    if (!verify_csrf(false)) {
        $batchMessage = "Security session expired. Please re-upload.";
        $batchMsgType = "error";
    } else {
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

                    $previewFaculty[] = [
                        'Fac_ID'    => trim($data[0] ?? ''),
                        'Fac_Name'  => trim($data[1] ?? ''),
                        'Fac_Email' => trim($data[2] ?? ''),
                        'Fac_Dept'  => $Dept_ID
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
// 3. SEARCH EXISTING FACULTY
// -----------------------------------------------------------------------------
$searchTerm = trim($_POST['Fac_Name'] ?? '');
$facultyFound = [];

if ($pdo) {
    if ($searchTerm !== '') {
        $st = $pdo->prepare("SELECT * FROM faculty WHERE Fac_Dept = ? AND Fac_Name LIKE ? ORDER BY Fac_Name ASC");
        $st->execute([$Dept_ID, "%{$searchTerm}%"]);
    } else {
        $st = $pdo->prepare("SELECT * FROM faculty WHERE Fac_Dept = ? ORDER BY Fac_Name ASC");
        $st->execute([$Dept_ID]);
    }
    $facultyFound = $st->fetchAll();
} elseif ($con) {
    $searchEsc = mysqli_real_escape_string($con, $searchTerm);
    $sql = "SELECT * FROM faculty WHERE Fac_Dept = '$Dept_ID'";
    if ($searchTerm !== '') $sql .= " AND Fac_Name LIKE '%$searchEsc%'";
    $sql .= " ORDER BY Fac_Name ASC";
    $res = mysqli_query($con, $sql);
    while ($r = mysqli_fetch_assoc($res)) $facultyFound[] = $r;
}

include '../Common/header.php';
include '../Common/deptadmin-sidebar.php';
?>

<!-- MAIN CONTENT -->
<main>
    <div class="dashboard-header">
        <div>
            <h4 class="mb-1">Department Faculty Directory</h4>
            <p class="mb-0 text-white-50 small">Onboard and manage teaching staff in <?= htmlspecialchars($Dept_ID) ?></p>
        </div>
        <span class="badge bg-white text-danger px-3 py-2 rounded-pill fw-semibold shadow-sm">
            <i class="bi bi-person-video3 me-1"></i> Dept: <?= htmlspecialchars($Dept_ID) ?>
        </span>
    </div>

    <!-- 1. CSV BATCH UPLOAD -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-danger text-white py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-cloud-arrow-up me-2"></i>Batch Faculty Onboarding (CSV)</h6>
            <a href="../Templates/Faculty_Template.csv" class="btn btn-sm btn-light rounded-pill px-3 fw-semibold" download>
                <i class="bi bi-file-earmark-arrow-down me-1"></i> Download Template
            </a>
        </div>
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                <?= csrf_field() ?>

                <div class="col-md-9">
                    <label class="form-label small fw-bold text-secondary mb-1">Upload Faculty CSV File</label>
                    <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                </div>

                <div class="col-md-3">
                    <button type="submit" name="Upload_CSV" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-eye me-1"></i> Preview
                    </button>
                </div>
            </form>

            <!-- PREVIEW TABLE -->
            <?php if (!empty($previewFaculty)): ?>
                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0">Previewing <?= count($previewFaculty) ?> Faculty Records</h6>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1">Ready to Insert</span>
                    </div>

                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="json_data" value='<?= htmlspecialchars(json_encode($previewFaculty), ENT_QUOTES, 'UTF-8') ?>'>

                        <div class="table-responsive rounded-3 border mb-3">
                            <table class="table table-hover align-middle mb-0 text-center">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Faculty ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($previewFaculty as $i => $f): ?>
                                        <tr>
                                            <td class="text-muted fw-bold"><?= $i + 1 ?></td>
                                            <td class="fw-semibold text-danger"><?= htmlspecialchars($f['Fac_ID']) ?></td>
                                            <td class="fw-medium text-dark"><?= htmlspecialchars($f['Fac_Name']) ?></td>
                                            <td><?= htmlspecialchars($f['Fac_Email']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end">
                            <a href="manage-faculty.php" class="btn btn-light rounded-pill px-3 me-2">Cancel</a>
                            <button type="submit" name="submit_csv" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Confirm & Import <?= count($previewFaculty) ?> Faculty
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. SEARCH & EXISTING DIRECTORY -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-dark text-white py-3 px-4">
            <h6 class="mb-0 fw-bold"><i class="bi bi-people me-2"></i>Active Faculty Directory</h6>
        </div>
        <div class="card-body p-4">
            <form method="POST" class="row g-3 align-items-end mb-4">
                <?= csrf_field() ?>

                <div class="col-md-9">
                    <label class="form-label small fw-bold text-secondary mb-1">Search Faculty Name</label>
                    <input type="text" name="Fac_Name" class="form-control" placeholder="Search by name..." value="<?= htmlspecialchars($searchTerm) ?>">
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>
            </form>

            <?php if (empty($facultyFound)): ?>
                <div class="alert alert-warning text-center py-4 rounded-3 border-0">
                    <i class="bi bi-person-x fs-2 d-block mb-2"></i>
                    No faculty members found in this department.
                </div>
            <?php else: ?>
                <div class="table-responsive rounded-3 border">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 20%;">Faculty ID</th>
                                <th class="text-start ps-4" style="width: 35%;">Name</th>
                                <th style="width: 25%;">Email</th>
                                <th style="width: 15%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facultyFound as $idx => $row): ?>
                                <tr>
                                    <td class="text-muted fw-bold"><?= $idx + 1 ?></td>
                                    <td class="fw-semibold text-danger"><?= htmlspecialchars($row['Fac_ID']) ?></td>
                                    <td class="text-start ps-4 fw-medium text-dark"><?= htmlspecialchars($row['Fac_Name']) ?></td>
                                    <td><a href="mailto:<?= htmlspecialchars($row['Fac_Email']) ?>" class="text-decoration-none small text-secondary"><?= htmlspecialchars($row['Fac_Email']) ?></a></td>
                                    <td>
                                        <a href="edit-faculty.php?Fac_ID=<?= htmlspecialchars($row['Fac_ID']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete-faculty.php?Fac_ID=<?= htmlspecialchars($row['Fac_ID']) ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Are you sure you want to delete this faculty member?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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