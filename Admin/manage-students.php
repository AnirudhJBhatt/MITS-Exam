<?php
/**
 * MITS Exam Portal - Admin Student Registry
 * Parameterized queries, filter by Batch/Department, real-time search, and CSV export
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

check_role('Admin');

// Fetch dropdown data
$batches = $pdo->query("SELECT DISTINCT Stud_Year FROM student WHERE Stud_Year IS NOT NULL AND Stud_Year != '' ORDER BY Stud_Year DESC")->fetchAll(PDO::FETCH_COLUMN);
$departments = $pdo->query("SELECT Dept_ID, Dept_Name FROM department ORDER BY Dept_Name ASC")->fetchAll(PDO::FETCH_ASSOC);

$selectedBatch = $_GET['batch'] ?? '';
$selectedDept = $_GET['dept'] ?? '';

// Build parameterized query
$params = [];
$conditions = [];

if ($selectedBatch !== '') {
    $conditions[] = "s.Stud_Year = ?";
    $params[] = $selectedBatch;
}

if ($selectedDept !== '') {
    $conditions[] = "s.Stud_Dept = ?";
    $params[] = $selectedDept;
}

$whereSql = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

$query = "
    SELECT s.Stud_ID, s.Stud_Name, s.Stud_Email, s.Stud_Dept, s.Stud_Year, s.Curr_AY, d.Dept_Name
    FROM student s
    LEFT JOIN department d ON s.Stud_Dept = d.Dept_ID
    {$whereSql}
    ORDER BY s.Stud_Year DESC, s.Stud_Name ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../Common/header.php';
include '../Common/admin-sidebar.php';
?>

<main>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--mits-primary);">Institutional Student Registry</h4>
            <p class="text-muted mb-0 small">Search and review registered examinees across all batches and engineering departments</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" onclick="exportTableToCSV('Student_Registry.csv')">
                <i class="bi bi-download me-1"></i> Export Filtered CSV
            </button>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-muted">Filter by Batch / Year</label>
                    <select class="form-select" name="batch" onchange="this.form.submit()">
                        <option value="">All Batches</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= e($b) ?>" <?= $selectedBatch === $b ? 'selected' : '' ?>>
                                Batch <?= e($b) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label fw-semibold small text-muted">Filter by Department</label>
                    <select class="form-select" name="dept" onchange="this.form.submit()">
                        <option value="">All Academic Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= e($d['Dept_ID']) ?>" <?= $selectedDept === $d['Dept_ID'] ? 'selected' : '' ?>>
                                <?= e($d['Dept_Name']) ?> (<?= e($d['Dept_ID']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <?php if ($selectedBatch !== '' || $selectedDept !== ''): ?>
                        <a href="manage-students.php" class="btn btn-outline-danger w-100">
                            <i class="bi bi-x-circle me-1"></i> Reset Filters
                        </a>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i> Apply Filter
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Students Directory Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-people me-2"></i>Enrolled Students (<?= count($students) ?>)
            </h6>
            <div class="input-group input-group-sm" style="max-width: 260px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="studentSearch" class="form-control border-start-0" placeholder="Live filter students...">
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="studentTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 50px;">#</th>
                            <th style="width: 140px;">ID</th>
                            <th>Name</th>
                            <th>Email Address</th>
                            <th>Department</th>
                            <th class="text-center" style="width: 120px;">Batch</th>
                            <th class="text-center" style="width: 140px;">Academic Year</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-x fs-2 d-block mb-2 text-secondary"></i>
                                    No student records match the selected filter criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $idx => $s): ?>
                                <tr>
                                    <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?= e($s['Stud_ID']) ?></span></td>
                                    <td class="fw-semibold text-dark"><?= e($s['Stud_Name']) ?></td>
                                    <td>
                                        <a href="mailto:<?= e($s['Stud_Email']) ?>" class="text-decoration-none">
                                            <?= e($s['Stud_Email'] ?: '-') ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <?= e($s['Dept_Name'] ?: $s['Stud_Dept']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border font-monospace">
                                            <?= e($s['Stud_Year']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info-emphasis px-2 py-1 rounded-pill">
                                            <?= e($s['Curr_AY'] ?: 'Current') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Client-side quick filter
    const searchInput = document.getElementById('studentSearch');
    const table = document.getElementById('studentTable');
    if (searchInput && table) {
        searchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
});

// Export table to CSV
function exportTableToCSV(filename) {
    const table = document.getElementById('studentTable');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        if (rows[i].style.display === 'none') continue;
        let row = [], cols = rows[i].querySelectorAll('td, th');
        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s+)/g, ' ');
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        csv.push(row.join(','));
    }

    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>

<?php include '../Common/footer.php'; ?>