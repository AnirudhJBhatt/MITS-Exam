<?php  
/**
 * Department Administrator - Manage Degree Programmes
 * Add and maintain programmes (B.Tech, M.Tech, MCA, etc.) for the department
 */

require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/../Common/auth.php";
require_once __DIR__ . "/../Common/csrf.php";

check_role('DeptAdmin');

$Dept_ID = $_SESSION['LoginDeptAdmin'];
$message = "";
$msgType = "info";

// 1. Insert New Programme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Add_Programme'])) {
    if (!verify_csrf(false)) {
        $message = "Security session expired. Please refresh the page.";
        $msgType = "error";
    } else {
        $Prog_Name = trim($_POST['Prog_Name'] ?? '');

        if ($Prog_Name !== '') {
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO programmes (Dept_ID, Prog_Name) VALUES (?, ?)");
                    $stmt->execute([$Dept_ID, $Prog_Name]);
                    $_SESSION['flash_success'] = "Programme '{$Prog_Name}' added successfully!";
                    header("Location: manage-programmes.php");
                    exit;
                } catch (Exception $e) {
                    $message = "Database error: " . $e->getMessage();
                    $msgType = "error";
                }
            } elseif ($con) {
                $stmt = $con->prepare("INSERT INTO programmes (Dept_ID, Prog_Name) VALUES (?, ?)");
                $stmt->bind_param("ss", $Dept_ID, $Prog_Name);
                if ($stmt->execute()) {
                    $_SESSION['flash_success'] = "Programme '{$Prog_Name}' added successfully!";
                    header("Location: manage-programmes.php");
                    exit;
                } else {
                    $message = "Database error: " . $stmt->error;
                    $msgType = "error";
                }
            }
        } else {
            $message = "Please enter a valid programme name.";
            $msgType = "warning";
        }
    }
}

// 2. Fetch Existing Programmes
$programmes = [];
if ($pdo) {
    $st = $pdo->prepare("SELECT * FROM programmes WHERE Dept_ID = ? ORDER BY Prog_Name ASC");
    $st->execute([$Dept_ID]);
    $programmes = $st->fetchAll();
} elseif ($con) {
    $st = $con->prepare("SELECT * FROM programmes WHERE Dept_ID = ? ORDER BY Prog_Name ASC");
    $st->bind_param("s", $Dept_ID);
    $st->execute();
    $res = $st->get_result();
    while ($r = $res->fetch_assoc()) $programmes[] = $r;
}

include '../Common/header.php';
include '../Common/deptadmin-sidebar.php';
?>

<!-- Add Programme Modal -->
<div class="modal fade" id="AddProgrammeModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="modalLabel"><i class="bi bi-mortarboard-fill me-2"></i>Add Academic Programme</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body py-4">
                    <label class="form-label small fw-bold text-secondary mb-1">Programme Name</label>
                    <input type="text" name="Prog_Name" class="form-control" placeholder="e.g. Computer Applications (MCA)" required autofocus>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="Add_Programme" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Save Programme
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MAIN CONTENT -->
<main>
    <div class="dashboard-header">
        <div>
            <h4 class="mb-1">Department Programmes</h4>
            <p class="mb-0 text-white-50 small">Manage degree specializations and courses offered by <?= htmlspecialchars($Dept_ID) ?></p>
        </div>
        <button class="btn btn-light rounded-pill px-4 fw-semibold text-danger shadow-sm" data-bs-toggle="modal" data-bs-target="#AddProgrammeModal">
            <i class="bi bi-plus-lg me-1"></i> Add Programme
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-dark text-white py-3 px-4">
            <h6 class="mb-0 fw-bold"><i class="bi bi-list-columns-reverse me-2"></i>Active Programmes</h6>
        </div>
        <div class="card-body p-4">
            <?php if (empty($programmes)): ?>
                <div class="alert alert-warning text-center py-4 rounded-3 border-0">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    No programmes found for this department. Click "Add Programme" above to register one.
                </div>
            <?php else: ?>
                <div class="table-responsive rounded-3 border">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead>
                            <tr>
                                <th style="width: 8%;">#</th>
                                <th class="text-start ps-4">Programme Title</th>
                                <th style="width: 20%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programmes as $i => $p): ?>
                                <tr>
                                    <td class="text-muted fw-bold"><?= $i + 1 ?></td>
                                    <td class="text-start ps-4 fw-semibold text-dark"><?= htmlspecialchars($p['Prog_Name']) ?></td>
                                    <td>
                                        <a href="edit-programme.php?Prog_ID=<?= (int)$p['Prog_ID'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1">
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </a>
                                        <a href="delete-programme.php?Prog_ID=<?= (int)$p['Prog_ID'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Are you sure you want to delete this programme? All associated courses may be affected.');">
                                            <i class="bi bi-trash me-1"></i> Delete
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

<?php if (!empty($message)): ?>
<script>
    Swal.fire({
        icon: '<?= $msgType ?>',
        title: 'Notice',
        text: <?= json_encode($message) ?>,
        confirmButtonColor: '#d1202d'
    });
</script>
<?php endif; ?>
