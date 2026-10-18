<?php  
/**
 * Department Administrator - Department Exams Schedule & Monitoring
 */

require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/../Common/auth.php";
require_once __DIR__ . "/../Common/csrf.php";

check_role('DeptAdmin');

$Dept_ID = $_SESSION['LoginDeptAdmin'];

function selected($field, $value) {
    return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
}

// Fetch Academic Years
$academicYears = [];
if ($pdo) {
    $academicYears = $pdo->query("SELECT AY_Name FROM academic_year ORDER BY AY_Name DESC")->fetchAll(PDO::FETCH_COLUMN);
} elseif ($con) {
    $r = mysqli_query($con, "SELECT AY_Name FROM academic_year ORDER BY AY_Name DESC");
    while ($row = mysqli_fetch_assoc($r)) $academicYears[] = $row['AY_Name'];
}

// Search Exams
$searchedExams = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Search'])) {
    $filterYear = trim($_POST['Acad_Year'] ?? '');
    $filterSem  = trim($_POST['Semester'] ?? '');

    if ($pdo) {
        $sql = "
            SELECT e.Exam_ID, e.Exam_Name, e.Duration, e.Start_Time, e.End_Time, e.Status, 
                   c.Course_Code, c.Course_Name, c.Semester, f.Fac_Name 
            FROM exams e
            JOIN courses c ON e.Course_ID = c.Course_ID
            LEFT JOIN faculty f ON e.Fac_ID = f.Fac_ID
            WHERE c.Dept_ID = ?
        ";
        $params = [$Dept_ID];

        if ($filterSem !== '') {
            $sql .= " AND c.Semester = ? ";
            $params[] = (int)$filterSem;
        }

        $sql .= " ORDER BY e.Created_At DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $searchedExams = $stmt->fetchAll();
    } elseif ($con) {
        $sql = "
            SELECT e.Exam_ID, e.Exam_Name, e.Duration, e.Start_Time, e.End_Time, e.Status, 
                   c.Course_Code, c.Course_Name, c.Semester, f.Fac_Name 
            FROM exams e
            JOIN courses c ON e.Course_ID = c.Course_ID
            LEFT JOIN faculty f ON e.Fac_ID = f.Fac_ID
            WHERE c.Dept_ID = '$Dept_ID'
        ";
        if ($filterSem !== '') {
            $semEsc = (int)$filterSem;
            $sql .= " AND c.Semester = '$semEsc'";
        }
        $sql .= " ORDER BY e.Created_At DESC";
        $res = mysqli_query($con, $sql);
        $searchedExams = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) $searchedExams[] = $row;
        }
    }
}

include '../Common/header.php';
include '../Common/deptadmin-sidebar.php';
?>

<!-- MAIN CONTENT -->
<main>
    <div class="dashboard-header">
        <div>
            <h4 class="mb-1">Department Examinations Schedule</h4>
            <p class="mb-0 text-white-50 small">Monitor scheduled and active assessments across <?= htmlspecialchars($Dept_ID) ?></p>
        </div>
        <span class="badge bg-white text-danger px-3 py-2 rounded-pill fw-semibold shadow-sm">
            <i class="bi bi-calendar3 me-1"></i> Department Schedule
        </span>
    </div>

    <!-- Filter Form -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-dark text-white py-3 px-4">
            <h6 class="mb-0 fw-bold"><i class="bi bi-funnel me-2"></i>Filter Department Exams</h6>
        </div>
        <div class="card-body p-4">
            <form method="POST" class="row g-3 align-items-end">
                <?= csrf_field() ?>

                <div class="col-md-5">
                    <label class="form-label small fw-bold text-secondary mb-1">Academic Year</label>
                    <select name="Acad_Year" class="form-select">
                        <option value="">-- All Years --</option>
                        <?php foreach ($academicYears as $ay): ?>
                            <option value="<?= htmlspecialchars($ay) ?>" <?= selected('Acad_Year', $ay) ?>>
                                <?= htmlspecialchars($ay) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary mb-1">Semester</label>
                    <select name="Semester" class="form-select">
                        <option value="">-- All Semesters --</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>" <?= selected('Semester', $i) ?>>Semester <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" name="Search" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-search me-1"></i> Search Exams
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <?php if ($searchedExams !== null): ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-danger text-white py-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Department Assessments</h6>
                <span class="badge bg-white text-danger fw-semibold px-3 py-1 rounded-pill"><?= count($searchedExams) ?> Found</span>
            </div>
            <div class="card-body p-4">
                <?php if (empty($searchedExams)): ?>
                    <div class="alert alert-warning text-center py-4 rounded-3 border-0">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No exams found matching the selected filters.
                    </div>
                <?php else: ?>
                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th class="text-start ps-4" style="width: 30%;">Exam Title</th>
                                    <th class="text-start" style="width: 30%;">Course</th>
                                    <th>Faculty Examiner</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($searchedExams as $idx => $ex): ?>
                                    <tr>
                                        <td class="text-muted fw-bold"><?= $idx + 1 ?></td>
                                        <td class="text-start ps-4 fw-semibold text-dark">
                                            <?= htmlspecialchars($ex['Exam_Name']) ?>
                                        </td>
                                        <td class="text-start">
                                            <div class="fw-semibold text-danger"><?= htmlspecialchars($ex['Course_Code']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($ex['Course_Name']) ?></small>
                                        </td>
                                        <td class="fw-medium text-dark">
                                            <?= htmlspecialchars($ex['Fac_Name'] ?? 'Unassigned') ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                <?= (int)$ex['Duration'] ?> mins
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($ex['Status'] === 'published'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1">Published</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-1">Draft</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include '../Common/footer.php'; ?>