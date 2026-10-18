<?php
/**
 * MITS Exam Portal - Faculty Enrolled Students
 * Parameterized queries, course-based enrollment filtering, live search, and roster export
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

check_role('Faculty');

$fac_id = $_SESSION['LoginFaculty'] ?? $_SESSION['user_id'] ?? '';

// Fetch assigned courses
$stmtCourses = $pdo->prepare("
    SELECT c.Course_ID, c.Course_Code, c.Course_Name, c.Semester, cm.Acad_Year 
    FROM course_mapping cm
    JOIN courses c ON cm.Course_ID = c.Course_ID
    WHERE cm.Fac_ID = ?
    ORDER BY c.Course_Name ASC
");
$stmtCourses->execute([$fac_id]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

$selectedCourse = $_GET['course'] ?? ($courses[0]['Course_ID'] ?? '');

$students = [];
$courseInfo = null;

if ($selectedCourse !== '') {
    // Fetch course details
    $cStmt = $pdo->prepare("SELECT * FROM courses WHERE Course_ID = ? LIMIT 1");
    $cStmt->execute([$selectedCourse]);
    $courseInfo = $cStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch enrolled students
    $stmtStud = $pdo->prepare("
        SELECT s.Stud_ID, s.Stud_Name, s.Stud_Email, s.Stud_Dept, s.Stud_Year, s.Curr_AY, d.Dept_Name
        FROM student_course_mapping scm
        JOIN student s ON scm.Stud_ID = s.Stud_ID
        LEFT JOIN department d ON s.Stud_Dept = d.Dept_ID
        WHERE scm.Course_ID = ?
        ORDER BY s.Stud_Name ASC
    ");
    $stmtStud->execute([$selectedCourse]);
    $students = $stmtStud->fetchAll(PDO::FETCH_ASSOC);

    // Fallback if student_course_mapping is empty, check students by branch & semester
    if (empty($students) && $courseInfo) {
        $stmtFb = $pdo->prepare("
            SELECT s.Stud_ID, s.Stud_Name, s.Stud_Email, s.Stud_Dept, s.Stud_Year, s.Curr_AY, d.Dept_Name
            FROM student s
            LEFT JOIN department d ON s.Stud_Dept = d.Dept_ID
            WHERE s.Stud_Dept = ? AND s.Stud_Sem = ?
            ORDER BY s.Stud_Name ASC
        ");
        $stmtFb->execute([$courseInfo['Dept_ID'], $courseInfo['Semester']]);
        $students = $stmtFb->fetchAll(PDO::FETCH_ASSOC);
    }
}

include '../Common/header.php';
include '../Common/faculty-sidebar.php';
?>

<main>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--mits-primary);">Enrolled Students</h4>
            <p class="text-muted mb-0 small">Review examinee rosters and candidate details enrolled in your teaching subjects</p>
        </div>
        <?php if (!empty($students)): ?>
            <div>
                <button class="btn btn-outline-secondary" onclick="exportTableToCSV('Student_Cohort_<?= e($selectedCourse) ?>.csv')">
                    <i class="bi bi-download me-1"></i> Export Roster CSV
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label fw-semibold small text-muted">Select Subject / Course</label>
                    <select name="course" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Choose Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= e($c['Course_ID']) ?>" <?= $selectedCourse == $c['Course_ID'] ? 'selected' : '' ?>>
                                <?= e($c['Course_Code'] ? $c['Course_Code'] . ' - ' : '') ?><?= e($c['Course_Name']) ?> (Semester S<?= e($c['Semester']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-people me-1"></i> View Students
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Student List Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-person-lines-fill me-2"></i>Enrolled Students (<?= count($students) ?>)
                    <?php if ($courseInfo): ?>
                        <small class="text-white"><?= e($courseInfo['Course_Code']) ?> &bull; <?= e($courseInfo['Course_Name']) ?></small>
                    <?php endif; ?>
                </h6>
            </div>
            <div class="input-group input-group-sm" style="max-width: 250px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="studSearchInput" class="form-control border-start-0" placeholder="Live filter students...">
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
                            <th>Email</th>
                            <th>Department</th>
                            <th class="text-center" style="width: 120px;">Batch</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-x fs-2 d-block mb-2 text-secondary"></i>
                                    No students are currently enrolled in this subject.
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
                                            <?= e($s['Curr_AY'] ?: 'Active') ?>
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
    const searchInput = document.getElementById('studSearchInput');
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

function exportTableToCSV(filename) {
    const table = document.getElementById('studentTable');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        if (rows[i].style.display === 'none') continue;
        let row = [], cols = rows[i].querySelectorAll('td, th');
        for (let j = 0; j < cols.length; j++) {
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