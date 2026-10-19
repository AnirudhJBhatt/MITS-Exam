<?php
/**
 * MITS Exam Portal - Admin Exam Schedule & Master Timetable
 * Parameterized PDO queries, multi-filter by Year/Dept/Semester, and interactive modal editing
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

check_role('Admin');

// Fetch dropdown options
$academicYears = $pdo->query("SELECT DISTINCT Acad_Year FROM exams WHERE Acad_Year IS NOT NULL AND Acad_Year != '' ORDER BY Acad_Year DESC")->fetchAll(PDO::FETCH_COLUMN);
$departments = $pdo->query("SELECT Dept_ID, Dept_Name FROM department ORDER BY Dept_Name ASC")->fetchAll(PDO::FETCH_ASSOC);

$selectedYear = $_GET['year'] ?? '';
$selectedDept = $_GET['dept'] ?? '';
$selectedSem  = $_GET['sem'] ?? '';

// Build parameterized filter conditions
$conditions = [];
$params = [];

if ($selectedYear !== '') {
    $conditions[] = "e.Acad_Year = ?";
    $params[] = $selectedYear;
}
if ($selectedDept !== '') {
    $conditions[] = "e.Dept = ?";
    $params[] = $selectedDept;
}
if ($selectedSem !== '') {
    $conditions[] = "e.Semester = ?";
    $params[] = $selectedSem;
}

$whereSql = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

$query = "
    SELECT e.Exam_ID, e.Exam_Name, e.Duration, e.Total_Marks,c e.Start_Time, e.End_Time, 
           e.Status, e.Acad_Year, e.Semester, e.Dept,
           c.Course_ID, c.Course_Name,
           f.Fac_ID, f.Fac_Name,
           d.Dept_Name
    FROM exams e
    LEFT JOIN courses c ON e.Course_ID = c.Course_ID
    LEFT JOIN faculty f ON e.Fac_ID = f.Fac_ID
    LEFT JOIN department d ON e.Dept = d.Dept_ID
    {$whereSql}
    ORDER BY e.Start_Time DESC, e.Exam_Name ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../Common/header.php';
include '../Common/admin-sidebar.php';
?>

<main>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--mits-primary);">Institutional Exam Timetable</h4>
            <p class="text-muted mb-0 small">Monitor scheduled assessments, active testing windows, and department exam statuses</p>
        </div>
        <div>
            <button class="btn btn-outline-secondary" onclick="exportTableToCSV('Institutional_Exam_Schedule.csv')">
                <i class="bi bi-download me-1"></i> Export Schedule CSV
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted">Academic Year</label>
                    <select name="year" class="form-select" onchange="this.form.submit()">
                        <option value="">All Academic Years</option>
                        <?php foreach ($academicYears as $ay): ?>
                            <option value="<?= e($ay) ?>" <?= $selectedYear === $ay ? 'selected' : '' ?>>
                                <?= e($ay) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted">Department</label>
                    <select name="dept" class="form-select" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= e($d['Dept_ID']) ?>" <?= $selectedDept === $d['Dept_ID'] ? 'selected' : '' ?>>
                                <?= e($d['Dept_Name']) ?> (<?= e($d['Dept_ID']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted">Semester</label>
                    <select name="sem" class="form-select" onchange="this.form.submit()">
                        <option value="">All Semesters</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>" <?= $selectedSem == $i ? 'selected' : '' ?>>Semester <?= $i ?> (S<?= $i ?>)</option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <?php if ($selectedYear !== '' || $selectedDept !== '' || $selectedSem !== ''): ?>
                        <a href="view-exams.php" class="btn btn-outline-danger w-100">
                            <i class="bi bi-x-circle me-1"></i> Reset Filters
                        </a>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i> Filter Timetable
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Exams Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-journal-check me-2"></i>Scheduled Assessments (<?= count($exams) ?>)
            </h6>
            <div class="input-group input-group-sm" style="max-width: 260px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="examSearch" class="form-control border-start-0" placeholder="Live search exams...">
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="examTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 50px;">#</th>
                            <th>Exam Title</th>
                            <th>Course</th>
                            <th>Department & Sem</th>
                            <th>Examiner / Faculty</th>
                            <th>Time Window</th>
                            <th class="text-center" style="width: 90px;">Duration</th>
                            <th class="text-center" style="width: 100px;">Status</th>
                            <th class="text-end pe-3" style="width: 110px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exams)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x fs-2 d-block mb-2 text-secondary"></i>
                                    No examinations match the selected filter criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($exams as $idx => $e): ?>
                                <tr>
                                    <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($e['Exam_Name']) ?></div>
                                        <span class="badge bg-light text-secondary border font-monospace small">ID: <?= e($e['Exam_ID']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($e['Course_Name'] ?: 'N/A') ?></div>
                                        <span class="badge bg-secondary-subtle text-secondary small"><?= e($e['Course_ID']) ?></span>
                                    </td>
                                    <td>
                                        <div><span class="badge bg-light text-dark border"><?= e($e['Dept_Name'] ?: $e['Dept']) ?></span></div>
                                        <small class="text-muted">Semester S<?= e($e['Semester']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($e['Fac_Name'] ?: 'Unassigned') ?></div>
                                        <small class="text-muted"><?= e($e['Fac_ID']) ?></small>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark">
                                            <i class="bi bi-clock me-1 text-primary"></i>
                                            <?= $e['Start_Time'] ? date('M d, Y h:i A', strtotime($e['Start_Time'])) : 'TBD' ?>
                                        </div>
                                        <div class="small text-muted">
                                            to <?= $e['End_Time'] ? date('M d, Y h:i A', strtotime($e['End_Time'])) : 'TBD' ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">
                                            <?= (int)$e['Duration'] ?> mins
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (($e['Status'] ?? '') === 'published'): ?>
                                            <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill">Published</span>
                                        <?php elseif (($e['Status'] ?? '') === 'completed'): ?>
                                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill">Completed</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis px-2 py-1 rounded-pill">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-sm btn-outline-primary edit-exam-btn" data-id="<?= e($e['Exam_ID']) ?>">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
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

<!-- Edit Exam Modal -->
<div class="modal fade" id="editExamModal" tabindex="-1" aria-labelledby="editExamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="editExamModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Examination Window
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="editExamModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quick search filter
    const searchInput = document.getElementById('examSearch');
    const table = document.getElementById('examTable');
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

    // Modal loader for edit exam
    const editModal = new bootstrap.Modal(document.getElementById('editExamModal'));
    document.querySelectorAll('.edit-exam-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const examId = this.dataset.id;
            const body = document.getElementById('editExamModalBody');
            body.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="small text-muted mt-2">Loading exam parameters...</div>
                </div>
            `;
            editModal.show();

            fetch(`edit-exam.php?Exam_ID=${encodeURIComponent(examId)}`)
                .then(r => r.text())
                .then(html => {
                    body.innerHTML = html;
                })
                .catch(err => {
                    body.innerHTML = `<div class="alert alert-danger">Error loading exam details.</div>`;
                });
        });
    });
});

function exportTableToCSV(filename) {
    const table = document.getElementById('examTable');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        if (rows[i].style.display === 'none') continue;
        let row = [], cols = rows[i].querySelectorAll('td, th');
        for (let j = 0; j < cols.length - 1; j++) { // exclude action column
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/(\s\s+)/g, ' ').trim();
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