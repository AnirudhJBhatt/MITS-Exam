<?php
/**
 * MITS Assessment Portal - Student Enrolled Courses
 * Parameterized PDO queries with semester filtering and course instructor cards
 */
session_start();
require_once __DIR__ . '/../Common/auth.php';
check_role('Student');
require_once __DIR__ . "/../Connection/connection.php";

$Stud_ID = $_SESSION['LoginStudent'];

// 1. Fetch student's default current semester
$studStmt = $pdo->prepare("SELECT Stud_Name, Stud_Sem, Stud_Dept FROM student WHERE Stud_ID = ?");
$studStmt->execute([$Stud_ID]);
$student = $studStmt->fetch();
$defaultSem = (int)($student['Stud_Sem'] ?? 1);

// Selected semester from GET/POST or default
$selectedSem = isset($_GET['sem']) ? (int)$_GET['sem'] : (isset($_POST['Filter_Semester']) ? (int)$_POST['Filter_Semester'] : $defaultSem);

// 2. Fetch Enrolled Courses for this student
$query = "
    SELECT 
        scm.Course_ID,
        scm.Semester,
        scm.Acad_Year,
        c.Course_Code,
        c.Course_Name,
        c.Credits,
        GROUP_CONCAT(DISTINCT f.Fac_Name ORDER BY f.Fac_Name SEPARATOR '||') AS Faculty_List,
        (SELECT COUNT(*) FROM exams e WHERE e.Course_ID = scm.Course_ID AND e.Status = 'published') AS Total_Exams
    FROM student_course_mapping scm
    JOIN courses c ON scm.Course_ID = c.Course_ID
    LEFT JOIN faculty f ON scm.Fac_ID = f.Fac_ID
    WHERE scm.Stud_ID = ? " . ($selectedSem > 0 ? "AND scm.Semester = ?" : "") . "
    GROUP BY scm.Course_ID, scm.Semester, scm.Acad_Year
    ORDER BY c.Course_Code ASC
";

$stmt = $pdo->prepare($query);
if ($selectedSem > 0) {
    $stmt->execute([$Stud_ID, $selectedSem]);
} else {
    $stmt->execute([$Stud_ID]);
}
$courses = $stmt->fetchAll();

// 3. Count distinct semesters student is mapped to
$semStmt = $pdo->prepare("SELECT DISTINCT Semester FROM student_course_mapping WHERE Stud_ID = ? ORDER BY Semester ASC");
$semStmt->execute([$Stud_ID]);
$availableSems = $semStmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($availableSems)) {
    $availableSems = [$defaultSem];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses — MITS Assessment Portal</title>
</head>

<body>
    <!-- NAVBAR -->
    <?php include '../Common/header.php'; ?>
    <!-- SIDEBAR -->
    <?php include '../Common/student-sidebar.php'; ?>

    <main>
        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark">
                    <i class="bi bi-book-half text-danger me-2"></i>My Enrolled Courses
                </h3>
            </div>

            <!-- Semester Filter Pills -->
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small fw-semibold d-none d-sm-inline">Filter Semester:</span>
                <div class="btn-group shadow-sm rounded-pill" role="group">
                    <a href="view-courses.php?sem=0" class="btn btn-outline-danger btn-sm <?= ($selectedSem === 0) ? 'btn-danger text-white' : 'btn-outline-secondary' ?>">
                        All
                    </a>
                    <?php for ($s = 1; $s <= 8; $s++): ?>
                        <a href="view-courses.php?sem=<?= $s ?>" class="btn btn-outline-danger btn-sm <?= ($selectedSem === $s) ? 'btn-danger text-white' : 'btn-outline-secondary' ?>">
                            S<?= $s ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Course Cards Grid -->
        <?php if (empty($courses)): ?>
            <div class="card border-0 shadow-sm rounded-4 text-center py-5 bg-white">
                <div class="card-body">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 76px; height: 76px;">
                        <i class="bi bi-journal-x fs-1 text-muted"></i>
                    </div>
                    <h5 class="fw-bold text-dark">No Courses Found</h5>
                    <p class="text-muted small mb-0">No subject enrollments match semester S<?= $selectedSem ?> for your profile.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($courses as $c): 
                    $facultyMembers = !empty($c['Faculty_List']) ? explode('||', $c['Faculty_List']) : [];
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white transition-all hover-elevate overflow-hidden">
                            <div class="card-header bg-white border-0 py-3 px-3 d-flex justify-content-between align-items-center">
                                <span class="badge bg-danger bg-opacity-10  fw-bold px-2 py-1 rounded-3">
                                    <?= e($c['Course_Code']) ?>
                                </span>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-star-fill text-warning me-1"></i><?= $c['Credits'] ?> Credits
                                    </span>
                                    <span class="badge bg-dark text-white">
                                        S<?= $c['Semester'] ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body px-4 py-3 d-flex flex-column">
                                <h5 class="fw-bold text-dark mb-2 line-clamp-2" style="min-height: 48px;">
                                    <?= e($c['Course_Name']) ?>
                                </h5>

                                <div class="mt-auto pt-3 border-top">
                                    <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                                        Faculty
                                    </span>
                                    <?php if (!empty($facultyMembers)): ?>
                                        <div class="d-flex flex-column gap-1">
                                            <?php foreach ($facultyMembers as $fac): ?>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.7rem; font-weight: 700;">
                                                        <i class="bi bi-person-fill"></i>
                                                    </div>
                                                    <span class="small text-dark fw-medium"><?= e($fac) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="small text-muted font-italic">Faculty pending assignment</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-light border-top px-4 py-2 d-flex justify-content-between align-items-center">
                                <span class="small text-muted">
                                    <i class="bi bi-file-earmark-check me-1"></i><?= $c['Total_Exams'] ?> Assessments
                                </span>
                                <a href="view-exams.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-semibold">
                                    View Exams <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include '../Common/footer.php'; ?>

</body>

</html>