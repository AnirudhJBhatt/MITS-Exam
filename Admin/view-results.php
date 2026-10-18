<?php
/**
 * MITS Exam Portal - Admin Institutional Results & Analytics
 * Parameterized cascade filtering, live summary statistics, and interactive student review
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

check_role('Admin');

// Dropdown data
$academicYears = $pdo->query("SELECT DISTINCT Acad_Year FROM exams WHERE Acad_Year IS NOT NULL AND Acad_Year != '' ORDER BY Acad_Year DESC")->fetchAll(PDO::FETCH_COLUMN);
$departments = $pdo->query("SELECT Dept_ID, Dept_Name FROM department ORDER BY Dept_Name ASC")->fetchAll(PDO::FETCH_ASSOC);

$selectedYear = $_GET['year'] ?? '';
$selectedSem  = $_GET['sem'] ?? '';
$selectedDept = $_GET['dept'] ?? '';
$selectedCourse = $_GET['course'] ?? '';
$selectedExam = $_GET['exam'] ?? '';

// Cascade courses based on selected department and semester
$courses = [];
if ($selectedDept !== '' || $selectedSem !== '') {
    $cSql = "SELECT Course_ID, Course_Code, Course_Name FROM courses WHERE 1=1";
    $cParams = [];
    if ($selectedDept !== '') {
        $cSql .= " AND Dept_ID = ?";
        $cParams[] = $selectedDept;
    }
    if ($selectedSem !== '') {
        $cSql .= " AND Semester = ?";
        $cParams[] = $selectedSem;
    }
    $cSql .= " ORDER BY Course_Name ASC";
    $cStmt = $pdo->prepare($cSql);
    $cStmt->execute($cParams);
    $courses = $cStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Cascade exams based on filters
$examsList = [];
if ($selectedCourse !== '' || $selectedYear !== '') {
    $eSql = "SELECT Exam_ID, Exam_Name, Exam_Name FROM exams WHERE 1=1";
    $eParams = [];
    if ($selectedCourse !== '') {
        $eSql .= " AND Course_ID = ?";
        $eParams[] = $selectedCourse;
    }
    if ($selectedYear !== '') {
        $eSql .= " AND Acad_Year = ?";
        $eParams[] = $selectedYear;
    }
    $eSql .= " ORDER BY Exam_Name ASC";
    $eStmt = $pdo->prepare($eSql);
    $eStmt->execute($eParams);
    $examsList = $eStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Results and summary stats if an exam is selected
$results = [];
$examMeta = null;
$stats = [
    'total_candidates' => 0,
    'avg_score' => 0,
    'highest_score' => 0,
    'lowest_score' => 0,
    'pass_count' => 0,
    'pass_rate' => 0
];

if ($selectedExam !== '') {
    // Fetch Exam Meta
    $stmtEx = $pdo->prepare("
        SELECT e.*, c.Course_Name, c.Course_Code, f.Fac_Name, d.Dept_Name 
        FROM exams e
        LEFT JOIN courses c ON e.Course_ID = c.Course_ID
        LEFT JOIN faculty f ON e.Fac_ID = f.Fac_ID
        LEFT JOIN department d ON e.Dept = d.Dept_ID
        WHERE e.Exam_ID = ? LIMIT 1
    ");
    $stmtEx->execute([$selectedExam]);
    $examMeta = $stmtEx->fetch(PDO::FETCH_ASSOC);

    // Fetch Results
    $stmtRes = $pdo->prepare("
        SELECT r.*, s.Stud_Name, s.Stud_Email, s.Stud_Dept, s.Stud_Year
        FROM results r
        LEFT JOIN student s ON r.Stud_ID = s.Stud_ID
        WHERE r.Exam_ID = ?
        ORDER BY r.Marks_Obtained DESC, r.Stud_ID ASC
    ");
    $stmtRes->execute([$selectedExam]);
    $results = $stmtRes->fetchAll(PDO::FETCH_ASSOC);

    // Calculate live analytics
    if (!empty($results)) {
        $count = count($results);
        $totalObtained = 0;
        $highest = -1;
        $lowest = 999999;
        $passed = 0;
        $passingMarks = $examMeta['Passing_Marks'] ?? ($examMeta['Total_Marks'] * 0.4);

        foreach ($results as $res) {
            $marks = (float)$res['Marks_Obtained'];
            $totalObtained += $marks;
            if ($marks > $highest) $highest = $marks;
            if ($marks < $lowest) $lowest = $marks;
            if ($marks >= $passingMarks) $passed++;
        }

        $stats['total_candidates'] = $count;
        $stats['avg_score'] = round($totalObtained / $count, 2);
        $stats['highest_score'] = $highest >= 0 ? $highest : 0;
        $stats['lowest_score'] = $lowest !== 999999 ? $lowest : 0;
        $stats['pass_count'] = $passed;
        $stats['pass_rate'] = round(($passed / $count) * 100, 1);
    }
}

include '../Common/header.php';
include '../Common/admin-sidebar.php';
?>

<main>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--mits-primary);">Institutional Assessment Results</h4>
            <p class="text-muted mb-0 small">Comprehensive performance analytics, marks distribution, and individual answer sheets</p>
        </div>
        <?php if (!empty($results)): ?>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" onclick="exportTableToCSV('Exam_Results_<?= e($selectedExam) ?>.csv')">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Scorecard CSV
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cascade Filter Bar Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-funnel me-2"></i>Filter Examination Results</h6>
        </div>
        <div class="card-body p-3">
            <form method="GET" class="row g-3 align-items-end" id="filterForm">
                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-muted">Academic Year</label>
                    <select name="year" class="form-select" onchange="this.form.submit()">
                        <option value="">All Years</option>
                        <?php foreach ($academicYears as $ay): ?>
                            <option value="<?= e($ay) ?>" <?= $selectedYear === $ay ? 'selected' : '' ?>><?= e($ay) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
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

                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-muted">Semester</label>
                    <select name="sem" class="form-select" onchange="this.form.submit()">
                        <option value="">All Semesters</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>" <?= $selectedSem == $i ? 'selected' : '' ?>>Semester S<?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted">Course / Subject</label>
                    <select name="course" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= e($c['Course_ID']) ?>" <?= $selectedCourse == $c['Course_ID'] ? 'selected' : '' ?>>
                                <?= e($c['Course_Code'] ? $c['Course_Code'] . ' - ' : '') ?><?= e($c['Course_Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted">Assessment / Exam</label>
                    <select name="exam" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Select Exam --</option>
                        <?php foreach ($examsList as $ex): ?>
                            <option value="<?= e($ex['Exam_ID']) ?>" <?= $selectedExam == $ex['Exam_ID'] ? 'selected' : '' ?>>
                                <?= e($ex['Exam_Name'] ?: $ex['Exam_Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($examMeta): ?>
        <!-- Performance KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary-subtle text-primary p-3 me-3">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small">Total Candidates</span>
                            <h4 class="fw-bold mb-0"><?= $stats['total_candidates'] ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info-subtle text-info-emphasis p-3 me-3">
                            <i class="bi bi-graph-up fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small">Class Average</span>
                            <h4 class="fw-bold mb-0"><?= $stats['avg_score'] ?> / <?= (float)$examMeta['Total_Marks'] ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success-subtle text-success p-3 me-3">
                            <i class="bi bi-award-fill fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small">Highest Mark</span>
                            <h4 class="fw-bold mb-0"><?= $stats['highest_score'] ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-warning-subtle text-warning-emphasis p-3 me-3">
                            <i class="bi bi-check2-circle fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small">Pass Rate</span>
                            <h4 class="fw-bold mb-0"><?= $stats['pass_rate'] ?>%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Results Roster Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-table me-2"></i>Scorecard Roster: <?= e($examMeta['Exam_Name'] ?: $examMeta['Exam_Name']) ?>
                    </h6>
                    <small class="text-muted">Course: <?= e($examMeta['Course_Name']) ?> | Examiner: <?= e($examMeta['Fac_Name'] ?: 'Faculty') ?></small>
                </div>
                <div class="input-group input-group-sm" style="max-width: 250px;">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="resultSearch" class="form-control border-start-0" placeholder="Filter student scores...">
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="resultsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 50px;">#</th>
                                <th style="width: 140px;">Student ID</th>
                                <th>Name</th>
                                <th class="text-center" style="width: 130px;">Marks Obtained</th>
                                <th class="text-center" style="width: 110px;">Percentage</th>
                                <th class="text-center" style="width: 110px;">Time Taken</th>
                                <th class="text-center" style="width: 100px;">Violations</th>
                                <th class="text-center" style="width: 110px;">Status</th>
                                <th class="text-end pe-3" style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($results)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-clipboard-x fs-2 d-block mb-2 text-secondary"></i>
                                        No student submissions found for this assessment yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($results as $idx => $r): ?>
                                    <?php 
                                        $marks = (float)$r['Marks_Obtained'];
                                        $total = (float)$r['Total_Marks'];
                                        $pct = $total > 0 ? round(($marks / $total) * 100, 1) : 0;
                                        $timeSec = (int)$r['Time_Taken_Sec'];
                                        $duration = floor($timeSec / 60) . 'm ' . ($timeSec % 60) . 's';
                                    ?>
                                    <tr>
                                        <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                        <td><span class="badge bg-light text-dark border font-monospace"><?= e($r['Stud_ID']) ?></span></td>
                                        <td class="fw-semibold text-dark"><?= e($r['Stud_Name'] ?: 'Student') ?></td>
                                        <td class="text-center fw-bold text-dark fs-6">
                                            <?= $marks ?> <span class="text-muted fw-normal small">/ <?= $total ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $pct >= 50 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> px-2 py-1 rounded-pill">
                                                <?= $pct ?>%
                                            </span>
                                        </td>
                                        <td class="text-center small text-muted"><?= $duration ?></td>
                                        <td class="text-center">
                                            <?php if ((int)$r['Violations'] > 0): ?>
                                                <span class="badge bg-danger text-white rounded-pill px-2 py-1" title="Tab switches or fullscreen exit">
                                                    <?= (int)$r['Violations'] ?> alerts
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border text-capitalize small">
                                                <?= str_replace('_', ' ', $r['Status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <button class="btn btn-sm btn-outline-primary view-details-btn" 
                                                    data-studid="<?= e($r['Stud_ID']) ?>" 
                                                    data-examid="<?= e($selectedExam) ?>">
                                                <i class="bi bi-eye"></i> Details
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
    <?php elseif ($selectedExam === ''): ?>
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-file-earmark-bar-graph fs-1 text-muted mb-3 d-block"></i>
                <h5 class="fw-bold text-dark">Select an Examination to View Analytics</h5>
                <p class="text-muted small mb-0" style="max-width: 450px; margin: 0 auto;">
                    Choose an Academic Year, Department, Course, and Exam from the filter above to inspect individual scorecards and performance statistics.
                </p>
            </div>
        </div>
    <?php endif; ?>
</main>

<!-- Result Details Modal -->
<div class="modal fade" id="resultDetailsModal" tabindex="-1" aria-labelledby="resultDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="resultDetailsModalLabel">
                    <i class="bi bi-clipboard-data me-2"></i>Candidate Submission Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalResultContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quick search filter
    const searchInput = document.getElementById('resultSearch');
    const table = document.getElementById('resultsTable');
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

    // Modal loader for student answer details
    const resultModal = new bootstrap.Modal(document.getElementById('resultDetailsModal'));
    document.querySelectorAll('.view-details-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const studId = this.dataset.studid;
            const examId = this.dataset.examid;
            const container = document.getElementById('modalResultContent');
            
            container.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="small text-muted mt-2">Loading itemized answer evaluation...</div>
                </div>
            `;
            resultModal.show();

            const formData = new FormData();
            formData.append('Stud_ID', studId);
            formData.append('Exam_ID', examId);

            fetch('../Faculty/fetch-result-details.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
            })
            .catch(err => {
                container.innerHTML = `<div class="alert alert-danger">Failed to fetch result details.</div>`;
            });
        });
    });
});

function exportTableToCSV(filename) {
    const table = document.getElementById('resultsTable');
    if (!table) return;
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