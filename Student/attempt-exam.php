<?php
/**
 * MITS Assessment Portal - Student Proctored CBT Engine
 * Bootstrap 5.3 layout — only essential custom CSS.
 */
session_start();
require_once __DIR__ . '/../Common/auth.php';
check_role('Student');
require_once __DIR__ . "/../Connection/connection.php";

$Stud_ID = $_SESSION['LoginStudent'];
$exam_id = isset($_GET['Exam_ID']) ? (int)$_GET['Exam_ID'] : 0;

if ($exam_id <= 0) {
    header("Location: view-exams.php");
    exit;
}

// 1. Verify if already submitted
$dupCheck = $pdo->prepare("SELECT Result_ID, Status, Marks_Obtained, Total_Marks FROM results WHERE Exam_ID = ? AND Stud_ID = ? LIMIT 1");
$dupCheck->execute([$exam_id, $Stud_ID]);
$existingResult = $dupCheck->fetch();
if ($existingResult) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background:#111827;'>
    <script>
    Swal.fire({
        icon: 'info',
        title: 'Exam Already Attempted',
        text: 'You have already submitted this assessment. You cannot attempt it again.',
        confirmButtonColor: '#d1202d'
    }).then(() => { window.location.href = 'view-exams.php'; });
    </script></body></html>";
    exit;
}

// 2. Fetch Exam Details
$stmt = $pdo->prepare("
    SELECT e.*, c.Course_Name, c.Course_Code 
    FROM exams e
    LEFT JOIN courses c ON c.Course_ID = e.Course_ID
    WHERE e.Exam_ID = ? AND e.Status = 'published'
    LIMIT 1
");
$stmt->execute([$exam_id]);
$examRow = $stmt->fetch();

if (!$examRow) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background:#111827;'>
    <script>
    Swal.fire({
        icon: 'error',
        title: 'Exam Unavailable',
        text: 'This examination is either not published or does not exist.',
        confirmButtonColor: '#d1202d'
    }).then(() => { window.location.href = 'view-exams.php'; });
    </script></body></html>";
    exit;
}

// Check window time if start/end times exist
$istTz = new DateTimeZone('Asia/Kolkata');
$now = new DateTime('now', $istTz);
if (!empty($examRow['Start_Time'])) {
    $startTime = new DateTime($examRow['Start_Time'], $istTz);
    if ($now < $startTime) {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background:#111827;'>
        <script>
        Swal.fire({
            icon: 'warning',
            title: 'Exam Not Started Yet',
            text: 'This exam is scheduled to begin at " . $startTime->format('d M Y, h:i A') . " IST.',
            confirmButtonColor: '#d1202d'
        }).then(() => { window.location.href = 'view-exams.php'; });
        </script></body></html>";
        exit;
    }
}
if (!empty($examRow['End_Time'])) {
    $endTime = new DateTime($examRow['End_Time'], $istTz);
    if ($now > $endTime) {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background:#111827;'>
        <script>
        Swal.fire({
            icon: 'error',
            title: 'Exam Window Closed',
            text: 'The scheduled window for this examination closed at " . $endTime->format('d M Y, h:i A') . " IST.',
            confirmButtonColor: '#d1202d'
        }).then(() => { window.location.href = 'view-exams.php'; });
        </script></body></html>";
        exit;
    }
}

$exam = [
    'Exam_ID'    => $examRow['Exam_ID'],
    'Exam_Name'  => $examRow['Exam_Name'],
    'Course'     => ($examRow['Course_Code'] ? $examRow['Course_Code'] . ' - ' : '') . ($examRow['Course_Name'] ?? 'General Examination'),
    'Duration'   => (int)$examRow['Duration'],
    'Start_Time' => $examRow['Start_Time'],
    'End_Time'   => $examRow['End_Time'],
];

// 3. Fetch Questions
$qstmt = $pdo->prepare("
    SELECT *
    FROM exam_questions
    WHERE Exam_ID = ?
    ORDER BY Sort_Order ASC, Question_ID ASC
");
$qstmt->execute([$exam_id]);
$rawQuestions = $qstmt->fetchAll();

if (empty($rawQuestions)) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background:#111827;'>
    <script>
    Swal.fire({
        icon: 'warning',
        title: 'Empty Exam',
        text: 'No questions have been configured for this examination.',
        confirmButtonColor: '#d1202d'
    }).then(() => { window.location.href = 'view-exams.php'; });
    </script></body></html>";
    exit;
}

$questions = [];
foreach ($rawQuestions as $row) {
    $questions[] = [
        'Question_ID'   => (int)$row['Question_ID'],
        'Question_Type' => $row['Question_Type'],
        'Question_Text' => $row['Question_Text'],
        'Marks'         => (float)$row['Marks'],
        'Options_JSON'  => $row['Options_JSON'],
        'Correct_Opt'   => $row['Correct_Opt'],
        'Rubric'        => $row['Rubric'],
        'Word_Limit'    => $row['Word_Limit'],
        'Answers_JSON'  => $row['Answers_JSON'],
        'Pairs_JSON'    => $row['Pairs_JSON'],
        'Image_Path'    => $row['Image_Path']
    ];
}

// 4. Shuffle if configured
if (!empty($examRow['Shuffle_Questions'])) {
    mt_srand(crc32($Stud_ID . '_' . $exam_id));
    shuffle($questions);
}

// 5. Fetch student details
$sstmt = $pdo->prepare("SELECT Stud_Name, Stud_Prog, Stud_Branch FROM student WHERE Stud_ID = ?");
$sstmt->execute([$Stud_ID]);
$srow = $sstmt->fetch();
$stud_name = $srow['Stud_Name'] ?? ($_SESSION['student_name'] ?? 'Student');

$student = [
    'name'   => $stud_name,
    'roll'   => $Stud_ID,
    'avatar' => strtoupper(substr($stud_name, 0, 2))
];

// 6. Fetch existing autosaved drafts
$savedAnswers = [];
$savedStatus = [];
try {
    $draftsStmt = $pdo->prepare("SELECT Question_ID, Student_Answer, Answer_Status FROM exam_drafts WHERE Exam_ID = ? AND Stud_ID = ?");
    $draftsStmt->execute([$exam_id, $Stud_ID]);
    $rawDrafts = $draftsStmt->fetchAll();

    foreach ($rawDrafts as $d) {
        $qId = (int)$d['Question_ID'];
        $decoded = json_decode($d['Student_Answer'], true);
        $savedAnswers[$qId] = ($decoded !== null) ? $decoded : $d['Student_Answer'];
        $savedStatus[$qId] = $d['Answer_Status'] ?: 'answered';
    }
} catch (Exception $eDrafts) {}

$totalMarks  = array_sum(array_column($questions, 'Marks'));
$totalDurationSec = (int)$exam['Duration'] * 60;

// 7. Hybrid Timer Synchronization (Server Authority)
$sessionStartKey = "exam_start_{$exam_id}_{$Stud_ID}";
$alreadyStarted = isset($_SESSION[$sessionStartKey]);

// If session was cleared/lost but drafts exist in database, recover start time from earliest draft
if (!$alreadyStarted && !empty($rawDrafts)) {
    try {
        $earliestStmt = $pdo->prepare("SELECT MIN(Updated_At) AS first_activity FROM exam_drafts WHERE Exam_ID = ? AND Stud_ID = ?");
        $earliestStmt->execute([$exam_id, $Stud_ID]);
        $earliestRow = $earliestStmt->fetch();
        if (!empty($earliestRow['first_activity'])) {
            $_SESSION[$sessionStartKey] = strtotime($earliestRow['first_activity']);
            $alreadyStarted = true;
        }
    } catch (Exception $eRecov) {}
}

$remainingSec = $totalDurationSec;
$serverDeadlineMs = null;

if ($alreadyStarted) {
    $startTime = (int)$_SESSION[$sessionStartKey];
    $elapsed = time() - $startTime;
    $remainingSec = max(0, $totalDurationSec - $elapsed);
    $serverDeadlineMs = ($startTime + $totalDurationSec) * 1000;

    if ($remainingSec <= 0) {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background:#111827;'>
        <script>
        Swal.fire({
            icon: 'error',
            title: 'Exam Time Expired',
            text: 'The scheduled duration for this assessment has elapsed.',
            confirmButtonColor: '#d1202d'
        }).then(() => { window.location.href = 'view-exams.php'; });
        </script></body></html>";
        exit;
    }
}

$durationSec = $remainingSec;

$questionsJson        = json_encode($questions);
$examJson             = json_encode($exam);
$studentJson          = json_encode($student);
$draftsJson           = json_encode($savedAnswers);
$draftStatusJson      = json_encode($savedStatus);
$alreadyStartedJson   = json_encode($alreadyStarted);
$serverDeadlineMsJson = json_encode($serverDeadlineMs);
$remainingSecJson     = (int)$remainingSec;
$totalDurationSecJson = (int)$totalDurationSec;
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1.0">
	<title><?= htmlspecialchars($exam['Exam_Name']) ?> — MITS CBT</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
	<script defer src="https://unpkg.com/mathlive"></script>
	<style>
		/* Only what Bootstrap cannot do */
		math-field[read-only] { border: 0; background: transparent; padding: 0; outline: none; font-size: 1.1em; }
		@keyframes blink { 50% { opacity: .4; } }
		.blink { animation: blink 1s step-end infinite; }
	</style>
</head>

<body class="bg-light vh-100 overflow-hidden user-select-none">

	<!-- FULLSCREEN GATE OVERLAY -->
	<div id="fs-gate" class="position-fixed top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-4 text-white" style="z-index:1050; background: #d1202d;">
		<div class="card border-secondary bg-white text-center w-100 mx-3" style="max-width:540px;">
			<div class="card-body p-4 p-md-5">
				<div class="rounded-circle border border-2 border-danger d-flex align-items-center justify-content-center mx-auto mb-3 fs-2" style="width:76px;height:76px;">
					<i class="bi bi-shield-lock-fill text-dark"></i>
				</div>
				<h2 class="h4 fw-bold">Proctored Exam Environment</h2>
				<p class="text-dark small">
					This assessment runs in a secure, full-screen examination environment.<br>
					Tab switching, copy/paste shortcuts, and right-click menus are locked.<br>
					<span class="text-warning fw-bold">3 tab-switch warnings</span> will automatically submit your assessment.
				</p>
				<div class="text-start rounded-3 p-3 bg-danger-subtle small my-4">
					<div class="mb-1"><i class="bi bi-file-earmark-text-fill text-danger me-2"></i><strong><?= htmlspecialchars($exam['Exam_Name']) ?></strong></div>
					<div class="mb-1 text-dark"><i class="bi bi-clock me-2"></i><?= $exam['Duration'] ?> minutes &nbsp;|&nbsp; <?= $totalMarks ?> marks</div>
					<div class="text-dark"><i class="bi bi-person me-2"></i><?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['roll']) ?>)</div>
				</div>
				<button class="btn btn-danger btn-lg px-5" id="btn-enter-fs">
					<i class="bi bi-fullscreen me-2"></i>ENTER FULLSCREEN &amp; START
				</button>
			</div>
		</div>
	</div>

	<!-- PAUSED FULLSCREEN WARNING OVERLAY -->
<div id="fs-warning"
	class="position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center p-3"
	style="display:none; z-index:8000; background:rgba(127,0,0,.95); backdrop-filter:blur(8px);">

	<div class="card border-0 shadow-lg rounded-4 text-center" style="max-width:480px; width:100%;">
		<div class="card-body p-4 p-md-5">

			<div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center mb-4"
				style="width:84px; height:84px; font-size:2.5rem;">
				<i class="bi bi-exclamation-triangle-fill"></i>
			</div>

			<h2 class="h3 fw-bold text-dark mb-2">Exam Paused</h2>
			<p class="text-secondary mb-4">
				You have exited full-screen mode.<br>
				Return to full screen to continue your exam.
			</p>

			<div class="alert alert-warning small py-2 mb-4">
				<i class="bi bi-clock-history me-1"></i>
				The exam timer is still running.
			</div>

			<button class="btn btn-danger btn-lg w-100 fw-bold" onclick="reEnterFullscreen()">
				<i class="bi bi-fullscreen me-2"></i>Return to Fullscreen
			</button>
		</div>
	</div>
</div>

	<!-- VIOLATION BANNER -->
	<div id="violation-banner" class="alert alert-danger rounded-0 border-0 mb-0 position-fixed top-0 start-0 end-0 d-none align-items-center gap-2 fw-semibold py-2" style="z-index:1055;">
		<i class="bi bi-exclamation-triangle-fill"></i><span id="violation-text"></span>
	</div>

	<!-- CBT EXAMINATION SHELL -->
	<div id="exam-shell" class="d-none flex-column vh-100">

		<!-- HEADER -->
		<header class="d-flex align-items-center gap-3 px-3 py-2 text-white shadow-sm flex-shrink-0" style="background-color: #d1202d">
			<div class="d-flex align-items-center gap-2 border border-secondary rounded px-3 py-1">
				<div>
					<div class="text-uppercase text-white-50 fw-bold" style="font-size:.65rem;">Time Left</div>
					<div id="countdown" class="fs-4 fw-semibold font-monospace text-success">--:--</div>
				</div>
				<i class="bi bi-hourglass-split text-white-50 fs-5"></i>
			</div>

			<div class="flex-grow-1 text-center text-truncate">
				<div class="fw-bold fs-5 text-truncate"><?= htmlspecialchars($exam['Exam_Name']) ?></div>
				<div class="small text-white-50 text-truncate"><?= htmlspecialchars($exam['Course']) ?></div>
			</div>

			<div id="autosave-indicator" class="d-flex align-items-center gap-1 small text-white-50">
				<i class="bi bi-cloud-check text-success"></i>
				<span class="d-none d-md-inline text-light">Synced</span>
			</div>

			<div class="d-flex align-items-center gap-2">
				<div class="text-end d-none d-md-block lh-sm">
					<div class="small fw-semibold"><?= htmlspecialchars($student['name']) ?></div>
					<div class="small text-white-50 font-monospace"><?= htmlspecialchars($student['roll']) ?></div>
				</div>
				<div class="rounded-circle border border-secondary d-flex align-items-center justify-content-center fw-bold small" style="width:38px;height:38px;">
					<?= htmlspecialchars($student['avatar']) ?>
				</div>
			</div>
		</header>

		<!-- PROGRESS BAR -->
		<div class="progress rounded-0 flex-shrink-0" style="height:5px;">
			<div id="progress-fill" class="progress-bar bg-danger" style="width:0%"></div>
		</div>

		<!-- STAGE & PALETTE -->
		<div class="d-flex flex-grow-1 overflow-hidden">
			<div class="d-flex flex-column flex-grow-1 overflow-hidden">
				<div class="px-3 pt-3 pb-1 fw-bold text-danger text-uppercase small flex-shrink-0" id="bc-pos">Question 1 of <?= count($questions) ?></div>

				<div id="q-viewport" class="flex-grow-1 overflow-auto px-3 pb-3"></div>

				<div class="d-flex align-items-center gap-2 p-3 bg-white border-top flex-shrink-0">
					<button class="btn btn-light" id="btn-prev" onclick="navigate(-1)" disabled>
						<i class="bi bi-chevron-left"></i> Previous
					</button>
					<button class="btn btn-outline-primary" id="btn-mark" onclick="toggleMark()">
						<i class="bi bi-flag me-1"></i>Mark for Review
					</button>
					<button class="btn btn-outline-secondary" onclick="clearCurrentAnswer()">
						<i class="bi bi-x-circle me-1"></i>Clear
					</button>
					<div class="flex-grow-1"></div>
					<button class="btn btn-danger" id="btn-next" onclick="navigate(1)">
						Next <i class="bi bi-chevron-right"></i>
					</button>
				</div>
			</div>

			<aside class="d-none d-md-flex flex-column bg-white border-start flex-shrink-0" style="width:280px;">
				<div class="px-3 py-2 border-bottom fw-bold text-secondary">
					<i class="bi bi-grid-3x3-gap me-2"></i>Question Palette
				</div>

				<ul class="list-group list-group-flush small">
					<li class="list-group-item d-flex justify-content-between align-items-center">Answered
						<span class="badge text-bg-success" id="cnt-answered">0</span></li>
					<li class="list-group-item d-flex justify-content-between align-items-center">Visited, Unanswered
						<span class="badge text-bg-danger" id="cnt-visited">0</span></li>
					<li class="list-group-item d-flex justify-content-between align-items-center">Marked for Review
						<span class="badge text-bg-warning" id="cnt-marked">0</span></li>
					<li class="list-group-item d-flex justify-content-between align-items-center">Not Visited
						<span class="badge text-bg-secondary" id="cnt-not">0</span></li>
				</ul>

				<div class="flex-grow-1 overflow-auto p-3 border-top">
					<div class="text-uppercase text-muted fw-bold mb-2" style="font-size:.7rem;">Jump to Question</div>
					<div id="palette-grid" class="row row-cols-5 g-2"></div>
				</div>

				<div class="row row-cols-2 g-2 px-3 py-2 border-top m-0">
					<div class="col"><div class="border rounded text-center p-2">
						<div class="fs-5 fw-bold text-danger"><?= count($questions) ?></div>
						<div class="text-uppercase text-muted" style="font-size:.65rem;">Total Qs</div></div></div>
					<div class="col"><div class="border rounded text-center p-2">
						<div class="fs-5 fw-bold text-danger"><?= $totalMarks ?></div>
						<div class="text-uppercase text-muted" style="font-size:.65rem;">Total Marks</div></div></div>
					<div class="col"><div class="border rounded text-center p-2">
						<div class="fs-5 fw-bold text-danger" id="stat-attempted">0</div>
						<div class="text-uppercase text-muted" style="font-size:.65rem;">Attempted</div></div></div>
					<div class="col"><div class="border rounded text-center p-2">
						<div class="fs-5 fw-bold text-danger" id="stat-remain"><?= count($questions) ?></div>
						<div class="text-uppercase text-muted" style="font-size:.65rem;">Remaining</div></div></div>
				</div>

				<div class="p-3">
					<button class="btn btn-danger w-100 fw-bold" id="btn-submit" onclick="confirmSubmit()">
						<i class="bi bi-send-check-fill me-1"></i> SUBMIT EXAM
					</button>
				</div>
			</aside>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script>
		const QUESTIONS = <?= $questionsJson ?>;
		const EXAM = <?= $examJson ?>;
		const STUDENT = <?= $studentJson ?>;
		const SAVED_ANSWERS = <?= $draftsJson ?>;
		const SAVED_STATUS = <?= $draftStatusJson ?>;
		const TOTAL_DURATION_S = <?= $totalDurationSecJson ?>;
		const DURATION_S = <?= $durationSec ?>;
		const ALREADY_STARTED = <?= $alreadyStartedJson ?>;
		const SERVER_DEADLINE_MS = <?= $serverDeadlineMsJson ?>;
		const REMAINING_SEC = <?= $remainingSecJson ?>;
		const TIMER_KEY = `mits_exam_end_${EXAM.Exam_ID}_${STUDENT.roll}`;
		const TOTAL = QUESTIONS.length;
		const TYPE_LABELS = {
			mcq: 'Multiple Choice',
			multiselect: 'Multiselect',
			fill: 'Fill in the Blanks',
			open: 'Open Ended',
			match: 'Match the Following'
		};
		const PAL_CLASS = {
			answered: 'btn-success',
			visited: 'btn-danger',
			marked: 'btn-warning',
			not: 'btn-outline-secondary'
		};

		// Check localStorage fallback if db drafts are empty
		let localDrafts = {};
		try {
			const raw = localStorage.getItem(`mits_exam_${EXAM.Exam_ID}_${STUDENT.roll}`);
			if (raw) localDrafts = JSON.parse(raw);
		} catch (e) {}

		// Determine initial deadline from Server authority or localStorage fallback
		let initialDeadline = SERVER_DEADLINE_MS;
		if (!initialDeadline) {
			const stored = localStorage.getItem(TIMER_KEY);
			if (stored) {
				const parsed = parseInt(stored, 10);
				if (!isNaN(parsed) && parsed > Date.now()) {
					initialDeadline = parsed;
				}
			}
		}

		const state = {
			currentIdx: 0,
			answers: { ...localDrafts, ...SAVED_ANSWERS },
			status: { ...SAVED_STATUS },
			tabViolations: 0,
			examActive: false,
			deadlineMs: initialDeadline,
			timerLeft: initialDeadline ? Math.max(0, Math.floor((initialDeadline - Date.now()) / 1000)) : REMAINING_SEC,
			timerInterval: null
		};

		// Initialize default status
		QUESTIONS.forEach(q => {
			if (!state.status[q.Question_ID]) {
				state.status[q.Question_ID] = (state.answers[q.Question_ID] !== undefined && state.answers[q.Question_ID] !== null) ? 'answered' : 'not';
			}
		});

		/* ── SHOW / HIDE (Bootstrap d-none / d-flex) ───── */
		function showEl(id) {
			const el = document.getElementById(id);
			if (el) { el.classList.remove('d-none'); el.classList.add('d-flex'); }
		}
		function hideEl(id) {
			const el = document.getElementById(id);
			if (el) { el.classList.remove('d-flex'); el.classList.add('d-none'); }
		}

		/* ── REAL-TIME AUTOSAVE ────────────────────────── */
		function saveDraftAsync(qId, answer, status = 'answered') {
			state.answers[qId] = answer;
			state.status[qId] = status;
			try {
				localStorage.setItem(`mits_exam_${EXAM.Exam_ID}_${STUDENT.roll}`, JSON.stringify(state.answers));
			} catch (e) {}

			const syncBadge = document.getElementById('autosave-indicator');
			if (syncBadge) {
				syncBadge.innerHTML = '<i class="bi bi-cloud-arrow-up text-warning"></i> <span class="d-none d-md-inline text-white-50">Saving...</span>';
			}

			fetch('save-answer.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					exam_id: EXAM.Exam_ID,
					question_id: qId,
					student_answer: answer,
					answer_status: status
				})
			}).then(r => r.json()).then(data => {
				if (data.success && syncBadge) {
					syncBadge.innerHTML = '<i class="bi bi-cloud-check text-success"></i> <span class="d-none d-md-inline text-light">Saved ' + data.saved_at + '</span>';
				}
			}).catch(() => {
				if (syncBadge) {
					syncBadge.innerHTML = '<i class="bi bi-cloud-slash text-danger"></i> <span class="d-none d-md-inline text-danger">Offline draft</span>';
				}
			});
		}

		/* ── FULLSCREEN ─────────────────────────────────── */
		document.getElementById('btn-enter-fs').addEventListener('click', () => {
			const el = document.documentElement;
			const requestFs = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;

			if (requestFs) {
				try {
					let promise = requestFs.call(el);
					if (promise !== undefined) {
						promise.then(() => {
							if (!state.examActive) startExam();
						}).catch(err => {
							console.warn("Fullscreen request failed:", err);
							if (!state.examActive) startExam();
						});
					} else {
						setTimeout(() => { if (!state.examActive) startExam(); }, 500);
					}
				} catch (e) {
					console.warn("Fullscreen request error:", e);
					if (!state.examActive) startExam();
				}
			} else {
				if (!state.examActive) startExam();
			}
		});

		document.addEventListener('fullscreenchange', onFsChange);
		document.addEventListener('webkitfullscreenchange', onFsChange);

		function onFsChange() {
			const inFs = !!document.fullscreenElement || !!document.webkitFullscreenElement;
			if (!state.examActive && inFs) startExam();
			else if (state.examActive && !inFs) {
				const d = document.documentElement;
				const requestFs = d.requestFullscreen || d.webkitRequestFullscreen || d.mozRequestFullScreen || d.msRequestFullscreen;
				if (requestFs) showEl('fs-warning');
			}
		}

		function reEnterFullscreen() {
			const el = document.documentElement;
			const requestFs = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
			if (requestFs) {
				try {
					let promise = requestFs.call(el);
					if (promise !== undefined) {
						promise.then(() => hideEl('fs-warning')).catch(() => hideEl('fs-warning'));
					} else {
						hideEl('fs-warning');
					}
				} catch (e) {
					hideEl('fs-warning');
				}
			} else {
				hideEl('fs-warning');
			}
		}

		async function startExam() {
			if (state.examActive) return;
			state.examActive = true;
			hideEl('fs-gate');
			showEl('exam-shell');
			buildPalette();
			goTo(0);

			// If not yet started on the server, inform server to record start timestamp
			if (!ALREADY_STARTED && !state.deadlineMs) {
				try {
					const res = await fetch('start-timer.php', {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({ exam_id: EXAM.Exam_ID })
					});
					const data = await res.json();
					if (data.success && data.deadline_ms) {
						state.deadlineMs = data.deadline_ms;
						state.timerLeft = data.remaining_sec;
						localStorage.setItem(TIMER_KEY, data.deadline_ms);
					}
				} catch (e) {
					console.warn('Could not sync start time with server:', e);
				}
			}

			startTimer();
			attachProctoring();

			// Periodic autosave sweep every 30 seconds
			setInterval(() => {
				if (state.examActive && state.currentIdx >= 0) {
					const currQ = QUESTIONS[state.currentIdx];
					if (currQ && state.answers[currQ.Question_ID] !== undefined) {
						saveDraftAsync(currQ.Question_ID, state.answers[currQ.Question_ID], state.status[currQ.Question_ID]);
					}
				}
			}, 30000);
		}

		/* ── TIMER (Hybrid Deadline-Based) ───────────────── */
		function startTimer() {
			if (!state.deadlineMs) {
				state.deadlineMs = Date.now() + (state.timerLeft * 1000);
			}
			localStorage.setItem(TIMER_KEY, state.deadlineMs);

			function tick() {
				const now = Date.now();
				const remaining = Math.max(0, Math.floor((state.deadlineMs - now) / 1000));
				state.timerLeft = remaining;
				renderClock();
				if (remaining <= 0) {
					clearInterval(state.timerInterval);
					autoSubmit('Time has expired for this examination. Auto-submitting now.');
				}
			}

			tick();
			if (state.timerInterval) clearInterval(state.timerInterval);
			state.timerInterval = setInterval(tick, 1000);
		}

		function renderClock() {
			const el = document.getElementById('countdown');
			const m = Math.floor(state.timerLeft / 60),
				s = state.timerLeft % 60;
			el.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
			const tone = state.timerLeft <= 60 ? 'text-danger blink' : state.timerLeft <= 300 ? 'text-warning' : 'text-success';
			el.className = `fs-4 fw-semibold font-monospace ${tone}`;
		}

		/* ── PROCTORING TELEMETRY ──────────────────────── */
		function attachProctoring() {
			document.addEventListener('visibilitychange', () => {
				if (!state.examActive || !document.hidden) return;
				state.tabViolations++;
				const rem = 3 - state.tabViolations;
				if (state.tabViolations >= 3) {
					autoSubmit('Maximum tab violations reached. Your exam has been auto-submitted.');
					return;
				}
				showBanner(`Tab switch detected! Violation ${state.tabViolations}/3 — ${rem} warning(s) remaining.`);
				Swal.fire({
					icon: 'warning',
					title: `⚠️ Tab Switch Detected (${state.tabViolations}/3)`,
					html: `<p>Navigating away from the exam tab is <strong>strictly prohibited</strong>.</p><p class="mt-2 text-danger"><strong>${rem} more violation(s)</strong> will result in immediate automatic submission.</p>`,
					confirmButtonText: 'Return to Exam',
					confirmButtonColor: '#d1202d',
					allowOutsideClick: false,
					allowEscapeKey: false
				});
			});

			document.addEventListener('contextmenu', e => e.preventDefault());
			document.addEventListener('keydown', e => {
				if ((e.ctrlKey || e.metaKey) && ['s', 'c', 'v', 'a', 'p', 'u', 'i', 'f'].includes(e.key.toLowerCase())) e.preventDefault();
				if (['F12', 'PrintScreen'].includes(e.key)) e.preventDefault();
				if (e.key === 'ArrowRight' && !e.target.matches('textarea,input,select')) navigate(1);
				if (e.key === 'ArrowLeft' && !e.target.matches('textarea,input,select')) navigate(-1);
			});
			['cut', 'copy'].forEach(ev => document.addEventListener(ev, e => {
				if (e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'INPUT') e.preventDefault();
			}));
		}

		function showBanner(msg) {
			document.getElementById('violation-text').textContent = msg;
			showEl('violation-banner');
			setTimeout(() => hideEl('violation-banner'), 6000);
		}

		/* ── CORE: goTo ─────────────────────────────────── */
		function goTo(newIdx) {
			if (newIdx < 0 || newIdx >= TOTAL) return;
			const oldIdx = state.currentIdx;
			state.currentIdx = newIdx;

			// Mark old as visited if untouched
			if (oldIdx !== newIdx) {
				const prevQ = QUESTIONS[oldIdx];
				if (state.status[prevQ.Question_ID] === 'not') state.status[prevQ.Question_ID] = 'visited';
			}
			// Mark current as visited
			const q = QUESTIONS[newIdx];
			if (state.status[q.Question_ID] === 'not') state.status[q.Question_ID] = 'visited';

			const viewport = document.getElementById('q-viewport');
			viewport.innerHTML = buildHTML(q, newIdx);
			viewport.scrollTop = 0;

			restoreAnswer(q);
			attachListeners(q);

			updateBreadcrumb();
			updateNavBtns();
			updateMarkBtn();
			updatePalette();
			updateProgress();
		}

		function buildAttachedImage(q) {
			if (!q.Image_Path) return '';
			return `<img src="../${esc(q.Image_Path)}" class="img-fluid rounded border mb-3" alt="Question Diagram" onerror="this.style.display='none'">`;
		}

		function getNormalizedOptions(raw) {
			let parsed = [];
			try {
				parsed = typeof raw === 'string' ? JSON.parse(raw || '[]') : (raw || []);
			} catch (e) { parsed = []; }

			const defLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
			const res = [];
			if (Array.isArray(parsed)) {
				parsed.forEach((item, idx) => {
					if (typeof item === 'object' && item !== null) {
						res.push({
							letter: item.letter || item.option || item.key || defLetters[idx] || String(idx),
							text: item.text || item.value || item.opt || ''
						});
					} else {
						res.push({
							letter: defLetters[idx] || String(idx),
							text: String(item)
						});
					}
				});
			} else if (typeof parsed === 'object' && parsed !== null) {
				Object.keys(parsed).forEach(k => {
					res.push({ letter: k, text: String(parsed[k]) });
				});
			}
			return res;
		}

		/* Option markup shared by MCQ + Multiselect */
		const OPT_CLASS = 'mcq-opt list-group-item list-group-item-action d-flex align-items-start gap-3';
		const LETTER_CLASS = 'opt-letter badge rounded-circle text-bg-light border d-inline-flex align-items-center justify-content-center flex-shrink-0';
		const LETTER_STYLE = 'width:2rem;height:2rem;';

		function setSel(label, on) {
			label.classList.toggle('selected', on);
			label.classList.toggle('border-danger', on);
			label.classList.toggle('bg-danger-subtle', on);
			const b = label.querySelector('.opt-letter');
			if (b) {
				b.classList.toggle('text-bg-danger', on);
				b.classList.toggle('text-bg-light', !on);
				b.classList.toggle('border', !on);
			}
		}

		function setMatchSel(opt, on) {
			opt.classList.toggle('selected', on);
			opt.classList.toggle('bg-danger-subtle', on);
			opt.classList.toggle('fw-semibold', on);
		}

		function buildHTML(q, idx) {
			const num = idx + 1;
			const img = buildAttachedImage(q);
			let body = '';

			if (q.Question_Type === 'mcq') {
				const opts = getNormalizedOptions(q.Options_JSON);
				body = `<div class="list-group">${opts.map(o => `
					<label class="${OPT_CLASS}" role="button" data-opt="${esc(o.letter)}">
						<input type="radio" class="d-none" name="r_${q.Question_ID}" value="${esc(o.letter)}">
						<span class="${LETTER_CLASS}" style="${LETTER_STYLE}">${esc(o.letter)}</span>
						<div class="pt-1">${formatMath(o.text)}</div>
					</label>`).join('')}</div>`;
			} else if (q.Question_Type === 'multiselect') {
				const opts = getNormalizedOptions(q.Options_JSON);
				body = `<div class="list-group">${opts.map(o => `
					<label class="${OPT_CLASS} multiselect-opt" role="button" data-opt="${esc(o.letter)}">
						<input type="checkbox" class="d-none" name="c_${q.Question_ID}[]" value="${esc(o.letter)}">
						<span class="${LETTER_CLASS}" style="${LETTER_STYLE}"><i class="bi bi-check2"></i></span>
						<div class="pt-1">${formatMath(o.text)}</div>
					</label>`).join('')}</div>`;
			} else if (q.Question_Type === 'fill') {
				body = `<div class="input-group">
					<span class="input-group-text fw-bold">Your Answer:</span>
					<input type="text" class="form-control math-fill-input" id="fill-inp" placeholder="Type answer here..." autocomplete="off">
				</div>`;
			} else if (q.Question_Type === 'open') {
				const wl = q.Word_Limit || 500;
				body = `<div>
					<textarea class="form-control open-textarea" id="open-ta" rows="8" data-wl="${wl}" placeholder="Write your comprehensive response here..."></textarea>
					<div class="form-text text-end font-monospace" id="open-wc">0 / ${wl} words</div>
				</div>`;
			} else if (q.Question_Type === 'match') {
				const pairs = JSON.parse(q.Pairs_JSON || '[]');
				const rightOpts = pairs.map((p, i) => ({ val: String(i), text: p.right }));
				body = `<table class="table align-middle mb-0">
					<thead class="table-dark"><tr><th class="w-50">Column A</th><th class="w-50">Column B (Match)</th></tr></thead>
					<tbody>${pairs.map((p, i) => `
						<tr>
							<td><strong>${i + 1}.</strong> ${formatMath(p.left)}</td>
							<td>
								<div class="dropdown match-dropdown" data-idx="${i}">
									<button class="btn btn-outline-secondary w-100 d-flex justify-content-between align-items-center gap-2 text-start match-dd-trigger" type="button" data-bs-toggle="dropdown">
										<span class="match-dd-selected text-truncate flex-grow-1">— Select Match —</span>
										<i class="bi bi-chevron-down small"></i>
									</button>
									<ul class="dropdown-menu w-100 overflow-auto" style="max-height:220px;">
										${rightOpts.map(o => `<li><a class="dropdown-item match-opt" href="#" data-val="${esc(o.val)}"><div class="math-content">${formatMath(o.text)}</div></a></li>`).join('')}
									</ul>
								</div>
							</td>
						</tr>`).join('')}
					</tbody>
				</table>`;
			}

			return `
				<div class="card shadow-sm">
					<div class="card-body p-3 p-md-4">
						<div class="d-flex align-items-center gap-2 mb-3">
							<span class="fw-bold text-danger text-uppercase">Question ${num}</span>
							<span class="badge rounded-pill bg-danger-subtle text-danger-emphasis text-uppercase">${TYPE_LABELS[q.Question_Type] || q.Question_Type}</span>
							<span class="badge rounded-pill text-bg-light border ms-auto"><i class="bi bi-award me-1"></i>${q.Marks} mark${q.Marks > 1 ? 's' : ''}</span>
						</div>
						${img}
						<div class="fs-6 fw-medium mb-3">${formatMath(q.Question_Text)}</div>
						${body}
					</div>
				</div>
			`;
		}

		function restoreAnswer(q) {
			const saved = state.answers[q.Question_ID];
			if (saved === undefined || saved === null) return;

			if (q.Question_Type === 'mcq') {
				document.querySelectorAll('.mcq-opt:not(.multiselect-opt)').forEach(l => {
					if (l.dataset.opt === saved) {
						setSel(l, true);
						l.querySelector('input').checked = true;
					}
				});
			} else if (q.Question_Type === 'multiselect') {
				document.querySelectorAll('.multiselect-opt').forEach(l => {
					if (Array.isArray(saved) && saved.includes(l.dataset.opt)) {
						setSel(l, true);
						l.querySelector('input').checked = true;
					}
				});
			} else if (q.Question_Type === 'fill') {
				const inputs = document.querySelectorAll('.math-fill-input');
				if (Array.isArray(saved)) {
					inputs.forEach((inp, i) => { if (saved[i] !== undefined) inp.value = saved[i]; });
				} else if (saved !== undefined && inputs[0]) {
					inputs[0].value = saved;
				}
			} else if (q.Question_Type === 'open') {
				const ta = document.getElementById('open-ta');
				if (ta) {
					ta.value = saved;
					updateWC(ta);
				}
			} else if (q.Question_Type === 'match' && typeof saved === 'object') {
				document.querySelectorAll('.match-dropdown').forEach(dd => {
					const idx = dd.dataset.idx;
					const val = saved[idx];
					if (!val) return;
					const opt = [...dd.querySelectorAll('.match-opt')].find(o => o.dataset.val === val);
					if (opt) {
						setMatchSel(opt, true);
						dd.querySelector('.match-dd-selected').innerHTML = opt.querySelector('.math-content').innerHTML;
					}
				});
			}
		}

		function attachListeners(q) {
			const qid = q.Question_ID;
			if (q.Question_Type === 'mcq') {
				document.querySelectorAll('.mcq-opt:not(.multiselect-opt)').forEach(lbl => lbl.addEventListener('click', (e) => {
					e.preventDefault();
					document.querySelectorAll('.mcq-opt:not(.multiselect-opt)').forEach(l => setSel(l, false));
					setSel(lbl, true);
					lbl.querySelector('input').checked = true;
					const ans = lbl.dataset.opt;
					state.answers[qid] = ans;
					setStatus(qid, 'answered');
					saveDraftAsync(qid, ans, 'answered');
				}));
			} else if (q.Question_Type === 'multiselect') {
				document.querySelectorAll('.multiselect-opt').forEach(lbl => lbl.addEventListener('click', (e) => {
					e.preventDefault();
					const inp = lbl.querySelector('input');
					inp.checked = !inp.checked;
					setSel(lbl, inp.checked);

					const checkedBoxes = Array.from(document.querySelectorAll(`input[name="c_${qid}[]"]:checked`)).map(i => i.value);
					const ans = checkedBoxes.length > 0 ? checkedBoxes : null;
					state.answers[qid] = ans;
					const st = checkedBoxes.length > 0 ? 'answered' : 'visited';
					setStatus(qid, st);
					saveDraftAsync(qid, ans, st);
				}));
			} else if (q.Question_Type === 'fill') {
				const inputs = document.querySelectorAll('.math-fill-input');
				inputs.forEach(inp => {
					inp.addEventListener('input', () => {
						const vals = Array.from(inputs).map(el => (el.value || '').trim());
						state.answers[qid] = vals;
						const st = vals.some(v => v !== '') ? 'answered' : 'visited';
						setStatus(qid, st);
						saveDraftAsync(qid, vals, st);
					});
				});
			} else if (q.Question_Type === 'open') {
				const ta = document.getElementById('open-ta');
				if (ta) ta.addEventListener('input', () => {
					updateWC(ta);
					state.answers[qid] = ta.value;
					const st = ta.value.trim() ? 'answered' : 'visited';
					setStatus(qid, st);
					saveDraftAsync(qid, ta.value, st);
				});
			} else if (q.Question_Type === 'match') {
				document.querySelectorAll('.match-dropdown').forEach(dd => {
					const idx = dd.dataset.idx;
					const selectedLabel = dd.querySelector('.match-dd-selected');

					dd.querySelectorAll('.match-opt').forEach(opt => {
						opt.addEventListener('click', (e) => {
							e.preventDefault();
							const val = opt.dataset.val;

							dd.querySelectorAll('.match-opt').forEach(o => setMatchSel(o, false));
							setMatchSel(opt, true);
							selectedLabel.innerHTML = opt.querySelector('.math-content').innerHTML;

							if (!state.answers[qid] || typeof state.answers[qid] !== 'object') state.answers[qid] = {};
							state.answers[qid][idx] = val;

							const allDD = document.querySelectorAll('.match-dropdown');
							const allFilled = [...allDD].every(d => d.querySelector('.match-opt.selected'));
							const st = allFilled ? 'answered' : 'visited';
							setStatus(qid, st);
							saveDraftAsync(qid, state.answers[qid], st);
						});
					});
				});
			}
		}

		function updateWC(ta) {
			const wl = +ta.dataset.wl,
				wc = document.getElementById('open-wc');
			if (!wc) return;
			const words = ta.value.trim() ? ta.value.trim().split(/\s+/).length : 0;
			wc.textContent = `${words} / ${wl} words`;
			const tone = words > wl ? 'text-danger fw-bold' : words > wl * .85 ? 'text-warning' : '';
			wc.className = `form-text text-end font-monospace ${tone}`;
		}

		function setStatus(qid, s) {
			if (state.status[qid] === 'marked' && (s === 'visited' || s === 'not')) return;
			state.status[qid] = s;
			updatePalette();
			updateProgress();
		}

		function navigate(dir) {
			const n = state.currentIdx + dir;
			if (n < 0 || n >= TOTAL) return;
			goTo(n);
		}

		function toggleMark() {
			const q = QUESTIONS[state.currentIdx],
				qid = q.Question_ID;
			const wasMarked = state.status[qid] === 'marked';
			state.status[qid] = wasMarked ? (state.answers[qid] ? 'answered' : 'visited') : 'marked';
			updateMarkBtn();
			updatePalette();
			saveDraftAsync(qid, state.answers[qid], state.status[qid]);
		}

		function clearCurrentAnswer() {
			const q = QUESTIONS[state.currentIdx],
				qid = q.Question_ID;
			state.answers[qid] = null;
			if (q.Question_Type === 'mcq') {
				document.querySelectorAll('.mcq-opt:not(.multiselect-opt)').forEach(l => setSel(l, false));
				document.querySelectorAll(`input[name="r_${qid}"]`).forEach(i => i.checked = false);
			} else if (q.Question_Type === 'multiselect') {
				document.querySelectorAll('.multiselect-opt').forEach(l => setSel(l, false));
				document.querySelectorAll(`input[name="c_${qid}[]"]`).forEach(i => i.checked = false);
			} else if (q.Question_Type === 'fill') {
				const i = document.getElementById('fill-inp');
				if (i) i.value = '';
			} else if (q.Question_Type === 'match') {
				document.querySelectorAll('.match-dropdown').forEach(dd => {
					dd.querySelectorAll('.match-opt').forEach(o => setMatchSel(o, false));
					dd.querySelector('.match-dd-selected').textContent = '— Select Match —';
				});
			} else {
				const ta = document.getElementById('open-ta');
				if (ta) {
					ta.value = '';
					updateWC(ta);
				}
			}
			if (state.status[qid] !== 'marked') setStatus(qid, 'visited');
			saveDraftAsync(qid, null, 'visited');
		}

		function updateNavBtns() {
			const p = document.getElementById('btn-prev');
			const n = document.getElementById('btn-next');
			if (p) p.disabled = state.currentIdx === 0;
			if (n) {
				n.innerHTML = state.currentIdx === TOTAL - 1
					? 'Review <i class="bi bi-check2-circle"></i>'
					: 'Next <i class="bi bi-chevron-right"></i>';
			}
		}

		function updateMarkBtn() {
			const btn = document.getElementById('btn-mark');
			if (!btn) return;
			const q = QUESTIONS[state.currentIdx];
			const isM = state.status[q.Question_ID] === 'marked';
			btn.classList.toggle('btn-primary', isM);
			btn.classList.toggle('btn-outline-primary', !isM);
			btn.innerHTML = isM ? '<i class="bi bi-flag-fill me-1"></i>Marked' : '<i class="bi bi-flag me-1"></i>Mark for Review';
		}

		function updateBreadcrumb() {
			const el = document.getElementById('bc-pos');
			if (el) el.textContent = `Question ${state.currentIdx + 1} of ${TOTAL}`;
		}

		function updateProgress() {
			const answered = Object.values(state.status).filter(s => s === 'answered').length;
			const pct = TOTAL > 0 ? (answered / TOTAL) * 100 : 0;
			const bar = document.getElementById('progress-fill');
			if (bar) bar.style.width = `${pct}%`;
		}

		function buildPalette() {
			const grid = document.getElementById('palette-grid');
			if (!grid) return;
			grid.innerHTML = '';
			QUESTIONS.forEach((q, i) => {
				const col = document.createElement('div');
				col.className = 'col';
				const btn = document.createElement('button');
				btn.className = 'btn btn-sm w-100 fw-bold';
				btn.style.aspectRatio = '1';
				btn.id = `pal-${q.Question_ID}`;
				btn.textContent = i + 1;
				btn.type = 'button';
				btn.onclick = () => goTo(i);
				col.appendChild(btn);
				grid.appendChild(col);
			});
		}

		function updatePalette() {
			let ans = 0, vis = 0, mrk = 0, not = 0;
			QUESTIONS.forEach((q, i) => {
				const btn = document.getElementById(`pal-${q.Question_ID}`);
				if (!btn) return;
				const st = state.status[q.Question_ID] || 'not';
				btn.className = `btn btn-sm w-100 fw-bold ${PAL_CLASS[st] || PAL_CLASS.not}`;
				if (i === state.currentIdx) btn.classList.add('border-dark', 'border-3');
				if (st === 'answered') ans++;
				else if (st === 'visited') vis++;
				else if (st === 'marked') mrk++;
				else not++;
			});

			const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
			set('cnt-answered', ans);
			set('cnt-visited', vis);
			set('cnt-marked', mrk);
			set('cnt-not', not);
			set('stat-attempted', ans);
			set('stat-remain', TOTAL - ans);
		}

		function confirmSubmit() {
			const ans = Object.values(state.status).filter(s => s === 'answered').length,
				un = TOTAL - ans;
			Swal.fire({
				title: 'Submit Examination?',
				html: `
					<div class="text-start">
						<div class="mb-2"><span class="badge bg-success me-2 fs-6">${ans}</span>Answered</div>
						<div class="mb-2"><span class="badge bg-danger me-2 fs-6">${un}</span>Unanswered / Skipped</div>
						<hr>
						<p class="text-muted small mt-2">Once submitted, you cannot modify your answers.</p>
					</div>
				`,
				icon: 'question',
				showCancelButton: true,
				confirmButtonText: '<i class="bi bi-send-check-fill me-1"></i> Yes, Submit Now',
				cancelButtonText: 'Review Answers',
				confirmButtonColor: '#d1202d',
				cancelButtonColor: '#6b7280',
				allowOutsideClick: false,
			}).then(r => {
				if (r.isConfirmed) submitExam(false);
			});
		}

		function autoSubmit(reason) {
			clearInterval(state.timerInterval);
			state.examActive = false;
			Swal.fire({
				icon: 'error',
				title: 'Exam Auto-Submitted',
				text: reason,
				confirmButtonColor: '#d1202d',
				allowOutsideClick: false,
				allowEscapeKey: false
			}).then(() => submitExam(true));
		}

		async function submitExam(auto = false) {
			clearInterval(state.timerInterval);
			state.examActive = false;
			if (document.exitFullscreen) document.exitFullscreen().catch(() => { });

			const payload = {
				exam_id: EXAM.Exam_ID,
				student_id: STUDENT.roll,
				auto_submit: auto,
				time_taken: TOTAL_DURATION_S - state.timerLeft,
				violations: state.tabViolations,
				answers: buildPayload()
			};

			Swal.fire({
				title: 'Submitting Examination...',
				html: '<div class="spinner-border text-danger" role="status"></div><p class="mt-3">Evaluating & saving responses...</p>',
				allowOutsideClick: false,
				allowEscapeKey: false,
				showConfirmButton: false
			});

			try {
				const res = await fetch('submit-exam.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(payload)
				});
				const data = await res.json();
				if (data.success) {
					try {
						localStorage.removeItem(`mits_exam_${EXAM.Exam_ID}_${STUDENT.roll}`);
						localStorage.removeItem(TIMER_KEY);
					} catch (e) {}

					Swal.fire({
						icon: 'success',
						title: 'Exam Submitted Successfully!',
						html: `
							<p>Your responses have been recorded.</p>
							<p class="mt-2"><strong>Time Taken:</strong> ${fmt(payload.time_taken)}</p>
							<p><strong>Attempted:</strong> ${Object.keys(payload.answers).length} / ${TOTAL}</p>
						`,
						confirmButtonColor: '#d1202d',
						confirmButtonText: 'Go to Dashboard',
						allowOutsideClick: false,
					}).then(() => {
						window.location.href = 'dashboard.php';
					});
				} else {
					throw new Error(data.message || 'Server error occurred during submission.');
				}
			} catch (err) {
				Swal.fire({
					icon: 'error',
					title: 'Submission Failed',
					text: 'Network issue or server error (' + err.message + '). Please inform the invigilator immediately.',
					confirmButtonColor: '#d1202d'
				});
			}
		}

		function buildPayload() {
			const out = {};
			QUESTIONS.forEach(q => {
				const a = state.answers[q.Question_ID];
				if (a !== undefined && a !== null) {
					out[q.Question_ID] = {
						type: q.Question_Type,
						answer: a,
						status: state.status[q.Question_ID]
					};
				}
			});
			return out;
		}

		/* ── HELPERS & MATH ─────────────────────────────── */
		function esc(s) {
			if (!s) return '';
			return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
		}

		function fmt(sec) {
			return `${Math.floor(sec / 60)}m ${sec % 60}s`;
		}

		function unwrapSingleText(s) {
			const m = s.match(/^\s*\\text\s*\{/);
			if (!m) return null;
			let depth = 1, i = m[0].length;
			for (; i < s.length && depth > 0; i++) {
				if (s[i] === '{') depth++;
				else if (s[i] === '}') depth--;
			}
			if (depth !== 0 || s.slice(i).trim() !== '') return null;
			return s.slice(m[0].length, i - 1);
		}

		const MF_INLINE = '<math-field read-only class="d-inline-block">';
		const MF_BLOCK = '<math-field read-only class="d-block text-center">';

		function formatMath(text) {
			if (!text) return '';
			text = esc(text);

			const inner = unwrapSingleText(text);
			if (inner !== null) {
				const hasDelimiter = /\\\(|\\\[|\$\$/.test(inner);
				const hasLatex = /\\[a-zA-Z]+|[\^_]/.test(inner);

				if (hasDelimiter) return formatMath(inner);
				if (!hasLatex) return `<span class="plain-text">${inner}</span>`;
				return `${MF_INLINE}${text}</math-field>`;
			}

			const hasDelimiter = text.includes('\\(') || text.includes('\\[') || text.includes('$$');
			if (!hasDelimiter) {
				const latexPattern = /\\text|\\_|\\^|\^|_|\\begin|\\frac|\\sqrt|\\sum|\\int|\\oint|\\alpha|\\beta|\\gamma|\\pi|\\theta|\\sin|\\cos|\\tan|\\log|\\lim|\\matrix|\\pmatrix|\\bmatrix|\\infty|\\pm|\\equiv|\\approx|\\leq|\\geq|\\times|\\div|\\partial|\\nabla|\\Rightarrow|\\rightarrow|\\to/;
				if (latexPattern.test(text)) {
					return `${MF_INLINE}${text}</math-field>`;
				}
			}

			text = text.replace(/\$\$([\s\S]*?)\$\$/g, `${MF_BLOCK}$1</math-field>`);
			text = text.replace(/\\\(([\s\S]*?)\\\)/g, `${MF_INLINE}$1</math-field>`);
			text = text.replace(/\\\[([\s\S]*?)\\\]/g, `${MF_BLOCK}$1</math-field>`);

			return `<div class="math-content">${text}</div>`;
		}

		/* ── AUTO-RESUME ON SCREEN REFRESH ──────────────── */
		if (ALREADY_STARTED || (state.deadlineMs && state.deadlineMs > Date.now())) {
			startExam();
			// Since refresh drops fullscreen mode, prompt student to resume fullscreen
			setTimeout(() => {
				const inFs = !!document.fullscreenElement || !!document.webkitFullscreenElement;
				if (!inFs) {
					showEl('fs-warning');
				}
			}, 400);
		}
	</script>
</body>

</html>