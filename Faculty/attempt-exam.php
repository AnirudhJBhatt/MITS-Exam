

<?php
session_start();
require_once __DIR__ . "/../Connection/connection.php";
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 4;

if ($exam_id <= 0) {
	die("Invalid Exam ID");
}

/* ----------------------------------------------------------
	FETCH EXAM DETAILS
	---------------------------------------------------------- */

$sql = "SELECT e.*, c.Course_Name FROM exams e
		LEFT JOIN courses c ON c.Course_ID = e.Course_ID
		WHERE e.Exam_ID = ?
		LIMIT 1
	";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();

$examResult = $stmt->get_result();

if ($examResult->num_rows == 0) {
	die("Exam not found");
}

$examRow = $examResult->fetch_assoc();

$exam = [
	'Exam_ID'    => $examRow['Exam_ID'],
	'Exam_Name'  => $examRow['Exam_Name'],
	'Course'     => $examRow['Course_Name'] ?? 'Unknown Course',
	'Duration'   => $examRow['Duration'],
	'Start_Time' => $examRow['Start_Time'],
	'End_Time'   => $examRow['End_Time'],
];

/* ---------------------------------------------------------- 
	FETCH QUESTIONS
	---------------------------------------------------------- */

$qsql = "
		SELECT *
		FROM exam_questions
		WHERE Exam_ID = ?
		ORDER BY Sort_Order ASC, Question_ID ASC
	";

$qstmt = $con->prepare($qsql);
$qstmt->bind_param("i", $exam_id);
$qstmt->execute();

$qResult = $qstmt->get_result();

$questions = [];

while ($row = $qResult->fetch_assoc()) {
	$questions[] = [
		'Question_ID'   => $row['Question_ID'],
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

if (empty($questions)) {
	die("No questions found for this exam");
}

/* ----------------------------------------------------------
	STUDENT SESSION
	---------------------------------------------------------- */

$student = [
	'name'   => $_SESSION['student_name'] ?? 'Student',
	'roll'   => $_SESSION['Stud_ID'] ?? 'N/A',
	'avatar' => strtoupper(substr($_SESSION['student_name'] ?? 'ST', 0, 2))
];

/* ----------------------------------------------------------
	TOTALS
	---------------------------------------------------------- */

$totalMarks  = array_sum(array_column($questions, 'Marks'));

$durationSec = $exam['Duration'] * 60;

$questionsJson = json_encode($questions);
$examJson      = json_encode($exam);
$studentJson   = json_encode($student);
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1.0">
	<title>
		<?= htmlspecialchars($exam['Exam_Name']) ?> — ExamBuilder
	</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
	<link
		href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap"
		rel="stylesheet">
	<link rel="stylesheet"
		href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">

	<script defer
		src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js">
	</script>

	<script defer
		src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js">
	</script>
	<style>
		:root {
			--red-900: #7f0000;
			--red-800: #9b0000;
			--red-700: #b91c1c;
			--red-600: #dc2626;
			--red-500: #ef4444;
			--red-400: #f87171;
			--red-100: #fee2e2;
			--red-50: #fff5f5;
			--white: #ffffff;
			--gray-50: #f9fafb;
			--gray-100: #f3f4f6;
			--gray-200: #e5e7eb;
			--gray-300: #d1d5db;
			--gray-400: #9ca3af;
			--gray-500: #6b7280;
			--gray-600: #4b5563;
			--gray-700: #374151;
			--gray-900: #111827;
			--clr-answered: #16a34a;
			--clr-visited: #dc2626;
			--clr-marked: #d97706;
			--clr-not: #374151;
			--header-h: 68px;
			--progress-h: 5px;
			--sidebar-w: 280px;
			font-family: 'DM Sans', sans-serif;
		}

		*,
		*::before,
		*::after {
			box-sizing: border-box;
		}

		html,
		body {
			height: 100%;
			margin: 0;
			background: var(--gray-50);
		}

		body {
			overflow: hidden;
		}

		/* GATE */
		#fs-gate {
			position: fixed;
			inset: 0;
			z-index: 9999;
			background: linear-gradient(135deg, var(--red-900) 0%, #1a0000 100%);
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 32px;
			color: var(--white);
		}

		.gate-logo {
			font-family: 'Bebas Neue', sans-serif;
			font-size: clamp(2.4rem, 5vw, 4rem);
			letter-spacing: 4px;
		}

		.gate-card {
			background: rgba(255, 255, 255, .07);
			border: 1px solid rgba(255, 255, 255, .15);
			backdrop-filter: blur(12px);
			border-radius: 18px;
			padding: 48px 56px;
			text-align: center;
			max-width: 520px;
			width: 90%;
		}

		.gate-icon {
			width: 80px;
			height: 80px;
			background: rgba(239, 68, 68, .2);
			border: 2px solid var(--red-500);
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 2rem;
			margin: 0 auto 24px;
			animation: pulse-ring 2s infinite;
		}

		@keyframes pulse-ring {
			0% {
				box-shadow: 0 0 0 0 rgba(239, 68, 68, .5);
			}

			70% {
				box-shadow: 0 0 0 16px rgba(239, 68, 68, 0);
			}

			100% {
				box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
			}
		}

		.gate-card h2 {
			font-size: 1.5rem;
			font-weight: 700;
			margin-bottom: 8px;
		}

		.gate-card p {
			color: rgba(255, 255, 255, .7);
			font-size: .95rem;
			line-height: 1.6;
		}

		.btn-enter {
			background: linear-gradient(135deg, var(--red-600), var(--red-800));
			color: var(--white);
			border: none;
			border-radius: 10px;
			padding: 14px 40px;
			font-size: 1rem;
			font-weight: 600;
			letter-spacing: 1px;
			cursor: pointer;
			transition: transform .18s, box-shadow .18s;
		}

		.btn-enter:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 24px rgba(239, 68, 68, .45);
		}

		/* SHELL */
		#exam-shell {
			display: none;
			height: 100vh;
			flex-direction: column;
		}

		#exam-shell.visible {
			display: flex;
		}

		/* FS WARNING */
		#fs-warning {
			display: none;
			position: fixed;
			inset: 0;
			z-index: 8000;
			background: rgba(127, 0, 0, .92);
			backdrop-filter: blur(6px);
			flex-direction: column;
			align-items: center;
			justify-content: center;
			color: var(--white);
			text-align: center;
			gap: 20px;
		}

		#fs-warning.show {
			display: flex;
		}

		#fs-warning h2 {
			font-size: 2rem;
			font-family: 'Bebas Neue', sans-serif;
			letter-spacing: 3px;
		}

		#fs-warning p {
			max-width: 400px;
			opacity: .8;
		}

		/* VIOLATION BANNER */
		#violation-banner {
			display: none;
			position: fixed;
			top: calc(var(--header-h) + var(--progress-h));
			left: 0;
			right: 0;
			z-index: 500;
			background: #7f1d1d;
			color: var(--white);
			padding: 9px 20px;
			font-weight: 600;
			font-size: .88rem;
			align-items: center;
			gap: 10px;
			animation: slide-in .3s ease;
		}

		#violation-banner.show {
			display: flex;
		}

		@keyframes slide-in {
			from {
				transform: translateY(-100%);
			}

			to {
				transform: translateY(0);
			}
		}

		/* HEADER */
		#exam-header {
			height: var(--header-h);
			flex-shrink: 0;
			background: linear-gradient(90deg, var(--red-900) 0%, var(--red-700) 100%);
			display: flex;
			align-items: center;
			padding: 0 20px;
			gap: 16px;
			box-shadow: 0 2px 12px rgba(0, 0, 0, .3);
			z-index: 100;
		}

		.hdr-timer {
			display: flex;
			align-items: center;
			gap: 10px;
			background: rgba(0, 0, 0, .25);
			border-radius: 8px;
			padding: 8px 16px;
			min-width: 170px;
			border: 1px solid rgba(255, 255, 255, .15);
		}

		.timer-label {
			font-size: .68rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 1px;
			color: rgba(255, 255, 255, .6);
		}

		#countdown {
			font-family: 'JetBrains Mono', monospace;
			font-size: 1.5rem;
			font-weight: 600;
			color: #86efac;
			transition: color .4s;
		}

		#countdown.amber {
			color: #fcd34d;
		}

		#countdown.red {
			color: #fca5a5;
			animation: blink 1s step-end infinite;
		}

		@keyframes blink {

			0%,
			100% {
				opacity: 1
			}

			50% {
				opacity: .4
			}
		}

		.hdr-center {
			flex: 1;
			text-align: center;
			overflow: hidden;
		}

		.hdr-exam-name {
			font-family: 'Bebas Neue', sans-serif;
			font-size: 1.35rem;
			letter-spacing: 2px;
			color: var(--white);
			line-height: 1.1;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.hdr-course {
			font-size: .72rem;
			color: rgba(255, 255, 255, .65);
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.hdr-student {
			display: flex;
			align-items: center;
			gap: 10px;
			min-width: 190px;
			justify-content: flex-end;
		}

		.avatar-initials {
			width: 38px;
			height: 38px;
			border-radius: 50%;
			background: rgba(255, 255, 255, .18);
			border: 2px solid rgba(255, 255, 255, .35);
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 700;
			font-size: .85rem;
			color: var(--white);
			flex-shrink: 0;
		}

		.stu-name {
			font-size: .85rem;
			font-weight: 600;
			color: var(--white);
			white-space: nowrap;
		}

		.stu-roll {
			font-size: .7rem;
			color: rgba(255, 255, 255, .6);
			font-family: 'JetBrains Mono', monospace;
		}

		/* PROGRESS BAR */
		#progress-track {
			height: var(--progress-h);
			background: rgba(185, 28, 28, .2);
			flex-shrink: 0;
			overflow: hidden;
		}

		#progress-fill {
			height: 100%;
			background: linear-gradient(90deg, #fca5a5, var(--red-500));
			width: 0%;
			transition: width .45s cubic-bezier(.4, 0, .2, 1);
		}

		/* BODY */
		#exam-body {
			flex: 1;
			display: flex;
			overflow: hidden;
		}

		/* QUESTION STAGE */
		#question-stage {
			flex: 1;
			display: flex;
			flex-direction: column;
			overflow: hidden;
			background: var(--gray-50);
		}

		/* BREADCRUMB */
		#q-breadcrumb {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 14px 28px 8px;
			flex-shrink: 0;
		}

		.bc-position {
			font-family: 'Bebas Neue', sans-serif;
			font-size: 1.05rem;
			letter-spacing: 2px;
			color: var(--red-700);
		}

		.bc-dots {
			display: flex;
			gap: 5px;
			align-items: center;
		}

		.bc-dot {
			height: 6px;
			width: 6px;
			border-radius: 3px;
			background: var(--gray-200);
			transition: all .35s;
		}

		.bc-dot.done {
			background: var(--clr-answered);
		}

		.bc-dot.current {
			background: var(--red-600);
			width: 18px;
		}

		.bc-dot.marked {
			background: var(--clr-marked);
		}

		.bc-dot.visited {
			background: var(--clr-visited);
		}

		/* VIEWPORT */
		#q-viewport {
			flex: 1;
			overflow: hidden;
			position: relative;
			padding: 0 28px 4px;
		}

		/* SLIDE */
		.q-slide {
			position: absolute;
			inset: 0 28px;
			overflow-y: auto;
			transition: transform .32s cubic-bezier(.4, 0, .2, 1), opacity .32s ease;
		}

		.q-slide::-webkit-scrollbar {
			width: 5px;
		}

		.q-slide::-webkit-scrollbar-thumb {
			background: var(--gray-300);
			border-radius: 4px;
		}

		.q-slide.exit-left {
			transform: translateX(-64px);
			opacity: 0;
			pointer-events: none;
		}

		.q-slide.exit-right {
			transform: translateX(64px);
			opacity: 0;
			pointer-events: none;
		}

		.q-slide.enter-left {
			transform: translateX(64px);
			opacity: 0;
		}

		.q-slide.enter-right {
			transform: translateX(-64px);
			opacity: 0;
		}

		/* QUESTION CARD */
		.q-card {
			background: var(--white);
			border: 1.5px solid var(--gray-200);
			border-radius: 14px;
			padding: 28px 32px;
			box-shadow: 0 2px 14px rgba(0, 0, 0, .07);
		}

		.q-meta {
			display: flex;
			align-items: center;
			gap: 10px;
			margin-bottom: 16px;
		}

		.q-num {
			font-family: 'Bebas Neue', sans-serif;
			font-size: 1.15rem;
			letter-spacing: 1px;
			color: var(--red-700);
		}

		.q-type-badge {
			font-size: .68rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 1px;
			padding: 3px 10px;
			border-radius: 20px;
			background: var(--red-100);
			color: var(--red-800);
		}

		.q-marks {
			margin-left: auto;
			font-size: .78rem;
			font-weight: 600;
			color: var(--gray-500);
			background: var(--gray-100);
			padding: 3px 10px;
			border-radius: 20px;
		}

		.q-text {
			font-size: 1rem;
			line-height: 1.75;
			color: var(--gray-900);
			font-weight: 500;
			margin-bottom: 20px;
		}

		/* MCQ */
		.mcq-options {
			display: flex;
			flex-direction: column;
			gap: 10px;
		}

		.mcq-opt {
			display: flex;
			align-items: flex-start;
			gap: 12px;
			border: 1.5px solid var(--gray-200);
			border-radius: 9px;
			padding: 13px 16px;
			cursor: pointer;
			transition: all .2s;
			user-select: none;
		}

		.mcq-opt:hover {
			border-color: var(--red-400);
			background: var(--red-50);
		}

		.mcq-opt.selected {
			border-color: var(--red-600);
			background: var(--red-50);
			box-shadow: 0 0 0 3px rgba(220, 38, 38, .1);
		}

		.mcq-opt input[type="radio"] {
			display: none;
		}

		.opt-letter {
			width: 30px;
			height: 30px;
			border-radius: 50%;
			border: 2px solid var(--gray-300);
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 700;
			font-size: .8rem;
			color: var(--gray-600);
			flex-shrink: 0;
			transition: all .2s;
		}

		.mcq-opt.selected .opt-letter {
			background: var(--red-600);
			border-color: var(--red-600);
			color: var(--white);
		}

		.opt-text {
			font-size: .93rem;
			color: var(--gray-800);
			line-height: 1.5;
			padding-top: 4px;
		}

		/* FILL */
		.fill-wrap {
			display: flex;
			align-items: center;
			gap: 12px;
			flex-wrap: wrap;
			background: var(--gray-50);
			border-radius: 8px;
			padding: 16px 20px;
			border: 1.5px solid var(--gray-200);
			font-size: .95rem;
			color: var(--gray-700);
		}

		.fill-input {
			border: none;
			border-bottom: 2px solid var(--red-400);
			background: transparent;
			font-size: 1rem;
			color: var(--gray-900);
			padding: 4px 8px;
			outline: none;
			min-width: 180px;
			font-family: 'JetBrains Mono', monospace;
			transition: border-color .2s;
			flex: 1;
		}

		.fill-input:focus {
			border-color: var(--red-700);
		}

		/* OPEN */
		.open-textarea {
			width: 100%;
			resize: vertical;
			min-height: 160px;
			border: 1.5px solid var(--gray-200);
			border-radius: 8px;
			padding: 14px 16px;
			font-size: .93rem;
			color: var(--gray-900);
			font-family: 'DM Sans', sans-serif;
			line-height: 1.6;
			transition: border-color .2s;
			outline: none;
		}

		.open-textarea:focus {
			border-color: var(--red-500);
		}

		.word-counter {
			font-size: .75rem;
			color: var(--gray-400);
			text-align: right;
			margin-top: 6px;
			font-family: 'JetBrains Mono', monospace;
		}

		.word-counter.near-limit {
			color: var(--clr-marked);
		}

		.word-counter.over-limit {
			color: var(--red-600);
			font-weight: 700;
		}

		/* MATCH */
		.match-table {
			width: 100%;
			border-collapse: separate;
			border-spacing: 0;
		}

		.match-table th {
			background: var(--red-700);
			color: var(--white);
			font-size: .78rem;
			text-transform: uppercase;
			letter-spacing: 1px;
			padding: 10px 16px;
			font-weight: 600;
		}

		.match-table th:first-child {
			border-radius: 8px 0 0 0;
		}

		.match-table th:last-child {
			border-radius: 0 8px 0 0;
		}

		.match-table td {
			border-bottom: 1px solid var(--gray-100);
			padding: 10px 16px;
			font-size: .9rem;
			vertical-align: middle;
		}

		.match-table tr:last-child td {
			border-bottom: none;
		}

		/* MATCH — Bootstrap dropdown skin */
		.match-dropdown {
			width: 100%;
		}

		.match-dd-trigger {
			width: 100%;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 8px;
			border: 1.5px solid var(--gray-200);
			border-radius: 6px;
			padding: 8px 12px;
			font-size: .88rem;
			color: var(--gray-800);
			background: var(--white);
			text-align: left;
		}

		.match-dd-trigger:hover,
		.match-dd-trigger:focus {
			border-color: var(--red-500);
			box-shadow: none;
			color: var(--gray-800);
		}

		.match-dd-trigger.show,
		.match-dropdown.show .match-dd-trigger {
			border-color: var(--red-500);
		}

		.match-dd-selected {
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
			flex: 1;
			text-align: left;
		}

		.match-dd-menu {
			width: 100%;
			max-height: 220px;
			overflow-y: auto;
			padding: 4px;
			border: 1.5px solid var(--gray-200);
			border-radius: 8px;
			box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
		}

		.match-opt {
			padding: 9px 12px !important;
			border-radius: 6px;
			font-size: .88rem;
			color: var(--gray-800);
			display: block;
		}

		.match-opt:hover,
		.match-opt:focus {
			background: var(--red-50);
			color: var(--gray-900);
		}

		.match-opt.selected {
			background: var(--red-100) !important;
			font-weight: 600;
			color: var(--red-800);
		}

		/* ATTACHED IMAGE (optional, on mcq / open / fill) */
		.attached-img {
			max-width: 100%;
			border-radius: 10px;
			border: 1.5px solid var(--gray-200);
			margin-bottom: 16px;
			display: block;
		}

		.attached-note {
			font-size: .8rem;
			color: var(--gray-500);
			background: var(--gray-50);
			border-left: 3px solid var(--red-400);
			padding: 8px 14px;
			border-radius: 0 6px 6px 0;
			margin-bottom: 14px;
		}

		/* BOTTOM NAV */
		#q-nav-bar {
			display: flex;
			align-items: center;
			gap: 10px;
			padding: 13px 28px;
			background: var(--white);
			border-top: 1px solid var(--gray-200);
			flex-shrink: 0;
			box-shadow: 0 -2px 10px rgba(0, 0, 0, .06);
		}

		.nav-btn-prev,
		.nav-btn-next {
			padding: 10px 22px;
			border-radius: 9px;
			font-weight: 600;
			font-size: .88rem;
			border: none;
			cursor: pointer;
			transition: all .18s;
			display: flex;
			align-items: center;
			gap: 7px;
		}

		.nav-btn-prev {
			background: var(--gray-100);
			color: var(--gray-700);
		}

		.nav-btn-prev:hover {
			background: var(--gray-200);
		}

		.nav-btn-prev:disabled {
			opacity: .4;
			cursor: not-allowed;
		}

		.nav-btn-next {
			background: var(--red-600);
			color: var(--white);
		}

		.nav-btn-next:hover {
			background: var(--red-700);
		}

		.btn-mark-review {
			padding: 10px 16px;
			border-radius: 9px;
			font-weight: 600;
			font-size: .85rem;
			border: 1.5px solid var(--clr-marked);
			color: var(--clr-marked);
			background: transparent;
			cursor: pointer;
			transition: all .18s;
		}

		.btn-mark-review:hover,
		.btn-mark-review.active {
			background: var(--clr-marked);
			color: var(--white);
		}

		.btn-clear-ans {
			padding: 10px 14px;
			border-radius: 9px;
			font-weight: 600;
			font-size: .82rem;
			border: 1.5px solid var(--gray-200);
			color: var(--gray-500);
			background: transparent;
			cursor: pointer;
			transition: all .18s;
		}

		.btn-clear-ans:hover {
			border-color: var(--red-400);
			color: var(--red-500);
		}

		.nav-spacer {
			flex: 1;
		}

		/* SIDEBAR */
		#exam-sidebar {
			width: var(--sidebar-w);
			flex-shrink: 0;
			background: var(--white);
			border-left: 1px solid var(--gray-200);
			display: flex;
			flex-direction: column;
			overflow: hidden;
		}

		.sidebar-head {
			background: linear-gradient(135deg, var(--red-900), var(--red-700));
			color: var(--white);
			padding: 16px 18px;
			font-family: 'Bebas Neue', sans-serif;
			font-size: 1rem;
			letter-spacing: 2px;
		}

		.legend-section {
			padding: 14px 18px;
			border-bottom: 1px solid var(--gray-100);
		}

		.legend-item {
			display: flex;
			align-items: center;
			gap: 8px;
			font-size: .78rem;
			color: var(--gray-600);
			margin-bottom: 6px;
		}

		.legend-dot {
			width: 14px;
			height: 14px;
			border-radius: 4px;
			flex-shrink: 0;
		}

		.legend-count {
			margin-left: auto;
			font-weight: 700;
			font-family: 'JetBrains Mono', monospace;
			font-size: .82rem;
		}

		.palette-section {
			flex: 1;
			overflow-y: auto;
			padding: 14px 18px;
		}

		.palette-section::-webkit-scrollbar {
			width: 4px;
		}

		.palette-section::-webkit-scrollbar-thumb {
			background: var(--gray-200);
		}

		.palette-label {
			font-size: .72rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 1px;
			color: var(--gray-400);
			margin-bottom: 10px;
		}

		#palette-grid {
			display: grid;
			grid-template-columns: repeat(5, 1fr);
			gap: 6px;
		}

		.palette-btn {
			width: 100%;
			aspect-ratio: 1;
			border-radius: 6px;
			border: none;
			cursor: pointer;
			font-weight: 700;
			font-size: .78rem;
			background: var(--clr-not);
			color: var(--white);
			transition: transform .15s, opacity .15s;
			font-family: 'JetBrains Mono', monospace;
		}

		.palette-btn:hover {
			transform: scale(1.12);
			opacity: .85;
		}

		.palette-btn.answered {
			background: var(--clr-answered);
		}

		.palette-btn.visited {
			background: var(--clr-visited);
		}

		.palette-btn.marked {
			background: var(--clr-marked);
		}

		.palette-btn.current {
			outline: 3px solid var(--gray-900);
			outline-offset: 2px;
		}

		.stats-row {
			padding: 12px 18px;
			border-top: 1px solid var(--gray-100);
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 8px;
		}

		.stat-chip {
			background: var(--gray-50);
			border: 1px solid var(--gray-100);
			border-radius: 8px;
			padding: 8px 10px;
			text-align: center;
		}

		.stat-chip .sc-val {
			font-family: 'Bebas Neue', sans-serif;
			font-size: 1.4rem;
			color: var(--red-700);
			line-height: 1;
		}

		.stat-chip .sc-lbl {
			font-size: .65rem;
			text-transform: uppercase;
			letter-spacing: .5px;
			color: var(--gray-400);
			font-weight: 600;
		}

		.sidebar-submit {
			padding: 14px 18px;
		}

		.btn-submit-exam {
			width: 100%;
			padding: 14px;
			background: linear-gradient(135deg, var(--red-700), var(--red-900));
			color: var(--white);
			border: none;
			border-radius: 10px;
			font-size: 1rem;
			font-weight: 700;
			cursor: pointer;
			letter-spacing: 1px;
			font-family: 'Bebas Neue', sans-serif;
			transition: all .2s;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			box-shadow: 0 4px 16px rgba(185, 28, 28, .35);
		}

		.btn-submit-exam:hover {
			background: linear-gradient(135deg, var(--red-600), var(--red-800));
			box-shadow: 0 6px 24px rgba(185, 28, 28, .5);
			transform: translateY(-1px);
		}

		@media(max-width:860px) {
			:root {
				--sidebar-w: 220px;
			}

			.hdr-student .stu-name {
				display: none;
			}

			#palette-grid {
				grid-template-columns: repeat(4, 1fr);
			}

			#q-viewport {
				padding: 0 16px 4px;
			}

			#q-breadcrumb {
				padding: 12px 16px 6px;
			}

			#q-nav-bar {
				padding: 10px 16px;
			}
		}

		@media(max-width:640px) {
			:root {
				--sidebar-w: 0px;
			}

			#exam-sidebar {
				display: none;
			}

			.q-card {
				padding: 20px 16px;
			}
		}
	</style>
</head>

<body>

	<div id="fs-gate">
		<div class="gate-logo"><i class="bi bi-mortarboard-fill me-2"></i>ExamBuilder</div>
		<div class="gate-card">
			<div class="gate-icon"><i class="bi bi-shield-lock-fill"></i></div>
			<h2>Secure Exam Environment</h2>
			<p>This exam runs in a proctored full-screen environment.<br>
				Tab switching, right-clicking, and keyboard shortcuts are disabled.<br>
				<strong>3 tab-switch violations</strong> will auto-submit your exam.
			</p>
			<div class="mt-4 text-start" style="font-size:.82rem;opacity:.65;margin-bottom:24px;">
				<div class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>
						<?= htmlspecialchars($exam['Exam_Name']) ?>
					</strong></div>
				<div class="mb-1"><i class="bi bi-clock me-2"></i>
					<?= $exam['Duration'] ?> minutes &nbsp;|&nbsp;
					<?= $totalMarks ?> marks
				</div>
				<div><i class="bi bi-person me-2"></i>
					<?= htmlspecialchars($student['name']) ?> &nbsp;(
					<?= htmlspecialchars($student['roll']) ?>)
				</div>
			</div>
			<button class="btn-enter" id="btn-enter-fs"><i class="bi bi-fullscreen me-2"></i>ENTER FULLSCREEN &
				BEGIN</button>
		</div>
	</div>

	<div id="fs-warning">
		<div style="font-size:3.5rem;"><i class="bi bi-exclamation-triangle-fill"></i></div>
		<h2>Return to Fullscreen</h2>
		<p>You have exited fullscreen mode. The exam is paused until you return.</p>
		<button class="btn-enter" onclick="reEnterFullscreen()"><i class="bi bi-fullscreen me-2"></i>Return to
			Fullscreen</button>
	</div>

	<div id="exam-shell">
		<div id="violation-banner"><i class="bi bi-exclamation-triangle-fill"></i><span id="violation-text"></span>
		</div>

		<header id="exam-header">
			<div class="hdr-timer">
				<div>
					<div class="timer-label">Time Left</div>
					<div id="countdown">90:00</div>
				</div>
				<div style="margin-left:8px;"><i class="bi bi-hourglass-split"
						style="color:rgba(255,255,255,.5);font-size:1.2rem;"></i></div>
			</div>
			<div class="hdr-center">
				<div class="hdr-exam-name">
					<?= htmlspecialchars($exam['Exam_Name']) ?>
				</div>
				<div class="hdr-course">
					<?= htmlspecialchars($exam['Course']) ?>
				</div>
			</div>
			<div class="hdr-student">
				<div>
					<div class="stu-name">
						<?= htmlspecialchars($student['name']) ?>
					</div>
					<div class="stu-roll">
						<?= htmlspecialchars($student['roll']) ?>
					</div>
				</div>
				<div class="avatar-initials">
					<?= htmlspecialchars($student['avatar']) ?>
				</div>
			</div>
		</header>

		<div id="progress-track">
			<div id="progress-fill"></div>
		</div>

		<div id="exam-body">
			<div id="question-stage">
				<div id="q-breadcrumb">
					<div class="bc-position" id="bc-pos">Question 1 of
						<?= count($questions) ?>
					</div>
					<div class="bc-dots" id="bc-dots"></div>
				</div>
				<div id="q-viewport"></div>
				<div id="q-nav-bar">
					<button class="nav-btn-prev" id="btn-prev" onclick="navigate(-1)" disabled><i
							class="bi bi-chevron-left"></i>
						Previous</button>
					<button class="btn-mark-review" id="btn-mark" onclick="toggleMark()"><i
							class="bi bi-flag me-1"></i>Mark for
						Review</button>
					<button class="btn-clear-ans" onclick="clearCurrentAnswer()"><i
							class="bi bi-x-circle me-1"></i>Clear</button>
					<div class="nav-spacer"></div>
					<button class="nav-btn-next" id="btn-next" onclick="navigate(1)">Next <i
							class="bi bi-chevron-right"></i></button>
				</div>
			</div>

			<aside id="exam-sidebar">
				<div class="sidebar-head"><i class="bi bi-grid-3x3-gap me-2"></i>Question Palette</div>
				<div class="legend-section">
					<div class="legend-item">
						<div class="legend-dot" style="background:var(--clr-answered)"></div>Answered<span
							class="legend-count" id="cnt-answered" style="color:var(--clr-answered)">0</span>
					</div>
					<div class="legend-item">
						<div class="legend-dot" style="background:var(--clr-visited)"></div>Visited, Unanswered<span
							class="legend-count" id="cnt-visited" style="color:var(--clr-visited)">0</span>
					</div>
					<div class="legend-item">
						<div class="legend-dot" style="background:var(--clr-marked)"></div>Marked for Review<span
							class="legend-count" id="cnt-marked" style="color:var(--clr-marked)">0</span>
					</div>
					<div class="legend-item">
						<div class="legend-dot" style="background:var(--clr-not)"></div>Not Visited<span
							class="legend-count" id="cnt-not">0</span>
					</div>
				</div>
				<div class="palette-section">
					<div class="palette-label">Jump to Question</div>
					<div id="palette-grid"></div>
				</div>
				<div class="stats-row">
					<div class="stat-chip">
						<div class="sc-val">
							<?= count($questions) ?>
						</div>
						<div class="sc-lbl">Total Qs</div>
					</div>
					<div class="stat-chip">
						<div class="sc-val">
							<?= $totalMarks ?>
						</div>
						<div class="sc-lbl">Total Marks</div>
					</div>
					<div class="stat-chip">
						<div class="sc-val" id="stat-attempted">0</div>
						<div class="sc-lbl">Attempted</div>
					</div>
					<div class="stat-chip">
						<div class="sc-val" id="stat-remain">
							<?= count($questions) ?>
						</div>
						<div class="sc-lbl">Remaining</div>
					</div>
				</div>
				<div class="sidebar-submit"><button class="btn-submit-exam" id="btn-submit"><i
							class="bi bi-send-check-fill"></i> SUBMIT EXAM</button></div>
			</aside>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script>
		const QUESTIONS = <?= $questionsJson ?>;
		const EXAM = <?= $examJson ?>;
		const STUDENT = <?= $studentJson ?>;
		const DURATION_S = <?= $durationSec ?>;
		const TOTAL = QUESTIONS.length;
		const TYPE_LABELS = {
			mcq: 'Multiple Choice',
			fill: 'Fill in the Blanks',
			open: 'Open Ended',
			match: 'Match the Following'
		};

		const state = {
			currentIdx: 0,
			answers: {},
			status: {},
			tabViolations: 0,
			examActive: false,
			timerLeft: DURATION_S,
			timerInterval: null
		};
		QUESTIONS.forEach(q => state.status[q.Question_ID] = 'not');

		/* ── FULLSCREEN ─────────────────────────────────── */
		document.getElementById('btn-enter-fs').addEventListener('click', () => {
			const el = document.documentElement;
			(el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen).call(el);
		});
		document.addEventListener('fullscreenchange', onFsChange);
		document.addEventListener('webkitfullscreenchange', onFsChange);

		function onFsChange() {
			const inFs = !!document.fullscreenElement || !!document.webkitFullscreenElement;
			if (!state.examActive && inFs) startExam();
			else if (state.examActive && !inFs) document.getElementById('fs-warning').classList.add('show');
		}

		function reEnterFullscreen() {
			document.getElementById('fs-warning').classList.remove('show');
			const el = document.documentElement;
			(el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen).call(el);
		}

		function startExam() {
			state.examActive = true;
			document.getElementById('fs-gate').style.display = 'none';
			document.getElementById('exam-shell').classList.add('visible');
			buildPalette();
			buildDots();
			goTo(0, 'none');
			startTimer();
			attachProctoring();
		}

		/* ── TIMER ──────────────────────────────────────── */
		function startTimer() {
			renderClock();
			state.timerInterval = setInterval(() => {
				state.timerLeft--;
				renderClock();
				if (state.timerLeft <= 0) {
					clearInterval(state.timerInterval);
					autoSubmit('Time is up!');
				}
			}, 1000);
		}

		function renderClock() {
			const el = document.getElementById('countdown');
			const m = Math.floor(state.timerLeft / 60),
				s = state.timerLeft % 60;
			el.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
			el.className = state.timerLeft <= 60 ? 'red' : state.timerLeft <= 300 ? 'amber' : '';
		}

		/* ── PROCTORING ─────────────────────────────────── */
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
					title: `⚠️ Tab Switch (${state.tabViolations}/3)`,
					html: `<p>Switching tabs is <strong>not allowed</strong>.</p><p class="mt-2 text-danger"><strong>${rem} more</strong> will auto-submit your exam.</p>`,
					confirmButtonText: 'Return to Exam',
					confirmButtonColor: '#dc2626',
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
			const b = document.getElementById('violation-banner');
			document.getElementById('violation-text').textContent = msg;
			b.classList.add('show');
			setTimeout(() => b.classList.remove('show'), 6000);
		}

		/* ── CORE: goTo ─────────────────────────────────── */
		function goTo(newIdx, direction) {
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
			const oldSlide = viewport.querySelector('.q-slide');

			// Animate out
			if (oldSlide && direction !== 'none') {
				oldSlide.classList.add(direction === 'right' ? 'exit-left' : 'exit-right');
				setTimeout(() => oldSlide.remove(), 340);
			} else if (oldSlide) {
				oldSlide.remove();
			}

			// Build new slide
			const slide = document.createElement('div');
			slide.className = 'q-slide';
			if (direction !== 'none') slide.classList.add(direction === 'right' ? 'enter-left' : 'enter-right');
			slide.innerHTML = buildHTML(q, newIdx);
			viewport.appendChild(slide);

			requestAnimationFrame(() => {
				requestAnimationFrame(() => {
					slide.classList.remove('enter-left', 'enter-right');
				});
				restoreAnswer(q);
				attachListeners(q);
				renderMath(slide);
			});

			updateNavBtns();
			updateBreadcrumb();
			updatePalette();
			updateProgress();
		}

		/* ── BUILD HTML ─────────────────────────────────── */
		function buildAttachedImage(q) {
			if (!q.Image_Path) return '';
			return `<div class="attached-note"><i class="bi bi-info-circle me-1"></i>Refer to the image below before answering.</div>
      <img src="${esc(q.Image_Path)}" class="attached-img" alt="Question attachment" loading="lazy">`;
		}

		function buildHTML(q, idx) {
			let body = '';
			if (q.Question_Type === 'mcq') {
				const opts = JSON.parse(q.Options_JSON || '[]');
				body = `${buildAttachedImage(q)}<div class="mcq-options">${opts.map(o => `
      <label class="mcq-opt" data-opt="${o.letter}">
        <input type="radio" name="r_${q.Question_ID}" value="${o.letter}">
        <div class="opt-letter">${o.letter}</div>
		<div class="opt-text math-content">${formatMath(o.text)}</div>

      </label>`).join('')}</div>`;
			} else if (q.Question_Type === 'fill') {
				body = ` ${buildAttachedImage(q)} <div class="fill-wrap"> <span class="math-content">${formatMath(q.Question_Text)}</span> </div> <div class="fill-wrap mt-3"> Answer:&nbsp; <input type="text" id="fill-inp" class="fill-input" placeholder="Type your answer…" autocomplete="off" spellcheck="false"> </div>`;
			} else if (q.Question_Type === 'open') {
				const wl = q.Word_Limit || 300; body = ` ${buildAttachedImage(q)} <div class="math-content mb-3"> ${formatMath(q.Question_Text)} </div> <textarea class="open-textarea" id="open-ta" data-wl="${wl}" placeholder="Write your answer here…" rows="7"></textarea> <div class="word-counter" id="open-wc"> 0 / ${wl} words </div>`;
			} else if (q.Question_Type === 'match') {
				const pairs = JSON.parse(q.Pairs_JSON || '[]');
				const shuffled = [...pairs.map(p => p.b)].sort(() => Math.random() - .5);

				const optionsHTML = shuffled.map(r =>
					`<li><a class="dropdown-item match-opt" href="#" data-val="${esc(r)}">
            <span class="math-content">${formatMath(r)}</span>
          </a></li>`
				).join('');

				body = `<table class="match-table"><thead><tr><th>Column A</th><th>Column B (Match)</th></tr></thead><tbody>${pairs.map((p, i) => `<tr><td> <strong class="math-content"> ${formatMath(p.a)} </strong> </td><td>
            <div class="dropdown match-dropdown" data-idx="${i}">
              <button class="btn match-dd-trigger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="match-dd-selected math-content">— Select —</span>
              </button>
              <ul class="dropdown-menu match-dd-menu">${optionsHTML}</ul>
            </div>
          </td></tr>`).join('')
					}</tbody></table>`;
			}
			return `<div class="q-card">
    <div class="q-meta">
      <div class="q-num">Q${idx + 1}</div>
      <div class="q-type-badge">${TYPE_LABELS[q.Question_Type] || q.Question_Type}</div>
      <div class="q-marks"><i class="bi bi-star-fill me-1" style="color:var(--clr-marked)"></i>${q.Marks} mark${q.Marks != 1 ? 's' : ''}</div>
    </div>
	<div class="q-text math-content">${formatMath(q.Question_Text)}</div>
    ${body}
  </div>`;
		}

		/* ── RESTORE ANSWER ─────────────────────────────── */
		function restoreAnswer(q) {
			const saved = state.answers[q.Question_ID];
			if (saved === undefined || saved === null) return;
			if (q.Question_Type === 'mcq') {
				document.querySelectorAll('.mcq-opt').forEach(l => {
					if (l.dataset.opt === saved) {
						l.classList.add('selected');
						l.querySelector('input').checked = true;
					}
				});
			} else if (q.Question_Type === 'fill') {
				const inp = document.getElementById('fill-inp');
				if (inp) inp.value = saved;
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
						opt.classList.add('selected');
						dd.querySelector('.match-dd-selected').innerHTML = opt.querySelector('.math-content').innerHTML;
					}
				});
			}
		}

		/* ── ATTACH LISTENERS ───────────────────────────── */
		function attachListeners(q) {
			const qid = q.Question_ID;
			if (q.Question_Type === 'mcq') {
				document.querySelectorAll('.mcq-opt').forEach(lbl => lbl.addEventListener('click', () => {
					document.querySelectorAll('.mcq-opt').forEach(l => l.classList.remove('selected'));
					lbl.classList.add('selected');
					lbl.querySelector('input').checked = true;
					state.answers[qid] = lbl.dataset.opt;
					setStatus(qid, 'answered');
				}));
			} else if (q.Question_Type === 'fill') {
				const inp = document.getElementById('fill-inp');
				if (inp) inp.addEventListener('input', () => {
					state.answers[qid] = inp.value.trim();
					setStatus(qid, inp.value.trim() ? 'answered' : 'visited');
				});
			} else if (q.Question_Type === 'open') {
				const ta = document.getElementById('open-ta');
				if (ta) ta.addEventListener('input', () => {
					updateWC(ta);
					state.answers[qid] = ta.value;
					setStatus(qid, ta.value.trim() ? 'answered' : 'visited');
				});
			} else if (q.Question_Type === 'match') {
				document.querySelectorAll('.match-dropdown').forEach(dd => {
					const idx = dd.dataset.idx;
					const trigger = dd.querySelector('.match-dd-trigger');
					const selectedLabel = dd.querySelector('.match-dd-selected');

					dd.querySelectorAll('.match-opt').forEach(opt => {
						opt.addEventListener('click', (e) => {
							e.preventDefault();
							const val = opt.dataset.val;

							dd.querySelectorAll('.match-opt').forEach(o => o.classList.remove('selected'));
							opt.classList.add('selected');
							selectedLabel.innerHTML = opt.querySelector('.math-content').innerHTML;

							if (!state.answers[qid] || typeof state.answers[qid] !== 'object') state.answers[qid] = {};
							state.answers[qid][idx] = val;

							const allDD = document.querySelectorAll('.match-dropdown');
							const allFilled = [...allDD].every(d => d.querySelector('.match-opt.selected'));
							setStatus(qid, allFilled ? 'answered' : 'visited');

							renderMath(trigger);
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
			wc.className = 'word-counter' + (words > wl ? ' over-limit' : words > wl * .85 ? ' near-limit' : '');
		}

		/* ── STATUS ─────────────────────────────────────── */
		function setStatus(qid, s) {
			if (state.status[qid] === 'marked' && (s === 'visited' || s === 'not')) return;
			state.status[qid] = s;
			updatePalette();
		}

		/* ── NAVIGATE ───────────────────────────────────── */
		function navigate(dir) {
			const n = state.currentIdx + dir;
			if (n < 0 || n >= TOTAL) return;
			goTo(n, dir > 0 ? 'right' : 'left');
		}

		function toggleMark() {
			const q = QUESTIONS[state.currentIdx],
				qid = q.Question_ID;
			const wasMarked = state.status[qid] === 'marked';
			state.status[qid] = wasMarked ? (state.answers[qid] ? 'answered' : 'visited') : 'marked';
			updateMarkBtn();
			updatePalette();
		}

		function clearCurrentAnswer() {
			const q = QUESTIONS[state.currentIdx],
				qid = q.Question_ID;
			state.answers[qid] = null;
			if (q.Question_Type === 'mcq') {
				document.querySelectorAll('.mcq-opt').forEach(l => l.classList.remove('selected'));
				document.querySelectorAll(`input[name="r_${qid}"]`).forEach(i => i.checked = false);
			} else if (q.Question_Type === 'fill') {
				const i = document.getElementById('fill-inp');
				if (i) i.value = '';
			} else if (q.Question_Type === 'match') {
				document.querySelectorAll('.match-dropdown').forEach(dd => {
					dd.querySelectorAll('.match-opt').forEach(o => o.classList.remove('selected'));
					dd.querySelector('.match-dd-selected').textContent = '— Select —';
				});
			} else {
				const ta = document.getElementById('open-ta');
				if (ta) {
					ta.value = '';
					updateWC(ta);
				}
			}
			if (state.status[qid] !== 'marked') setStatus(qid, 'visited');
		}

		/* ── UI CHROME ──────────────────────────────────── */
		function updateNavBtns() {
			document.getElementById('btn-prev').disabled = state.currentIdx === 0;
			const nb = document.getElementById('btn-next');
			if (state.currentIdx === TOTAL - 1) {
				nb.innerHTML = '<i class="bi bi-send-check-fill me-1"></i>Submit';
				nb.onclick = confirmSubmit;
				nb.style.background = 'linear-gradient(135deg,var(--red-700),var(--red-900))';
			} else {
				nb.innerHTML = 'Next <i class="bi bi-chevron-right"></i>';
				nb.onclick = () => navigate(1);
				nb.style.background = '';
			}
			updateMarkBtn();
		}

		function updateMarkBtn() {
			const q = QUESTIONS[state.currentIdx],
				isM = state.status[q.Question_ID] === 'marked';
			const b = document.getElementById('btn-mark');
			b.className = 'btn-mark-review' + (isM ? ' active' : '');
			b.innerHTML = isM ? '<i class="bi bi-flag-fill me-1"></i>Marked' : '<i class="bi bi-flag me-1"></i>Mark for Review';
		}

		function updateBreadcrumb() {
			document.getElementById('bc-pos').textContent = `Question ${state.currentIdx + 1} of ${TOTAL}`;
			updateDots();
		}

		function buildDots() {
			const wrap = document.getElementById('bc-dots');
			wrap.innerHTML = '';
			QUESTIONS.forEach((_, i) => {
				const d = document.createElement('div');
				d.id = `dot-${i}`;
				d.className = 'bc-dot';
				wrap.appendChild(d);
			});
			updateDots();
		}

		function updateDots() {
			QUESTIONS.forEach((q, i) => {
				const d = document.getElementById(`dot-${i}`);
				if (!d) return;
				const s = state.status[q.Question_ID];
				d.className = 'bc-dot' + (i === state.currentIdx ? ' current' : s === 'answered' ? ' done' : s === 'marked' ? ' marked' : s === 'visited' ? ' visited' : '');
				d.style.width = i === state.currentIdx ? '18px' : '6px';
			});
		}

		function updateProgress() {
			const n = Object.values(state.status).filter(s => s === 'answered').length;
			document.getElementById('progress-fill').style.width = (n / TOTAL * 100) + '%';
		}

		function buildPalette() {
			const g = document.getElementById('palette-grid');
			g.innerHTML = '';
			QUESTIONS.forEach((q, i) => {
				const b = document.createElement('button');
				b.className = 'palette-btn not';
				b.id = `pb-${q.Question_ID}`;
				b.textContent = i + 1;
				b.addEventListener('click', () => goTo(i, i > state.currentIdx ? 'right' : i < state.currentIdx ? 'left' : 'none'));
				g.appendChild(b);
			});
			updatePalette();
		}

		function updatePalette() {
			let cnt = {
				answered: 0,
				visited: 0,
				marked: 0,
				not: 0
			};
			QUESTIONS.forEach((q, i) => {
				const s = state.status[q.Question_ID],
					b = document.getElementById(`pb-${q.Question_ID}`);
				if (!b) return;
				b.className = 'palette-btn ' + s + (i === state.currentIdx ? ' current' : '');
				cnt[s]++;
			});
			['answered', 'visited', 'marked', 'not'].forEach(k => {
				const el = document.getElementById(`cnt-${k}`);
				if (el) el.textContent = cnt[k];
			});
			const att = cnt.answered;
			document.getElementById('stat-attempted').textContent = att;
			document.getElementById('stat-remain').textContent = TOTAL - att;
			updateProgress();
			updateDots();
		}

		/* ── SUBMIT ─────────────────────────────────────── */
		document.getElementById('btn-submit').addEventListener('click', confirmSubmit);

		function confirmSubmit() {
			const ans = Object.values(state.status).filter(s => s === 'answered').length,
				un = TOTAL - ans;
			Swal.fire({
				title: 'Submit Exam?',
				html: `<div class="text-start"><div class="mb-2"><span class="badge bg-success me-2">${ans}</span>Answered</div><div class="mb-2"><span class="badge bg-danger me-2">${un}</span>Unanswered / Skipped</div><hr><p class="text-muted small mt-2">Once submitted, you cannot make any changes.</p></div>`,
				icon: 'question',
				showCancelButton: true,
				confirmButtonText: '<i class="bi bi-send-check-fill me-1"></i> Yes, Submit Now',
				cancelButtonText: 'Review Answers',
				confirmButtonColor: '#dc2626',
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
					confirmButtonColor: '#dc2626',
					allowOutsideClick: false,
					allowEscapeKey: false
				})
				.then(() => submitExam(true));
		}
		async function submitExam(auto = false) {
			clearInterval(state.timerInterval);
			state.examActive = false;
			if (document.exitFullscreen) document.exitFullscreen().catch(() => {});
			const payload = {
				exam_id: EXAM.Exam_ID,
				student_id: STUDENT.roll,
				auto_submit: auto,
				time_taken: DURATION_S - state.timerLeft,
				violations: state.tabViolations,
				answers: buildPayload()
			};
			Swal.fire({
				title: 'Submitting…',
				html: '<div class="spinner-border text-danger" role="status"></div><p class="mt-3">Please wait…</p>',
				allowOutsideClick: false,
				allowEscapeKey: false,
				showConfirmButton: false
			});
			try {
				const res = await fetch('submit-exam.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(payload)
				});
				const data = await res.json();
				if (data.success) {
					Swal.fire({
						icon: 'success',
						title: 'Exam Submitted!',
						html: `<p>Responses saved.</p><p class="mt-2"><strong>Time:</strong> ${fmt(payload.time_taken)}</p><p><strong>Submitted:</strong> ${Object.keys(payload.answers).length}/${TOTAL}</p>`,
						confirmButtonColor: '#dc2626',

						confirmButtonText: 'Done',
						allowOutsideClick: false,
					}).then(() => {
						window.location.href = 'dashboard.php';
					});
				} else throw new Error(data.message || 'Server error');
			} catch (err) {
				Swal.fire({
					icon: 'error',
					title: 'Submission Failed',
					text: 'Could not reach server. Inform invigilator. (' + err.message + ')',
					confirmButtonColor: '#dc2626'
				});
			}
		}

		function buildPayload() {
			const out = {};
			QUESTIONS.forEach(q => {
				const a = state.answers[q.Question_ID];
				if (a !== undefined && a !== null) out[q.Question_ID] = {
					type: q.Question_Type,
					answer: a,
					status: state.status[q.Question_ID]
				};
			});
			return out;
		}

		/* ── HELPERS ────────────────────────────────────── */
		function esc(s) {
			if (!s) return '';
			return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
		}

		function fmt(sec) {
			return `${Math.floor(sec / 60)}m ${sec % 60}s`;
		}

		function formatMath(text) {
			if (!text) return '';

			// Escape HTML first
			text = esc(text);

			// If already contains latex delimiters, keep as-is
			const hasDelimiter =
				text.includes('\\(') ||
				text.includes('\\[') ||
				text.includes('$$');

			// Auto-wrap raw LaTeX environments
			if (!hasDelimiter) {

				// Detect common LaTeX commands/environments
				const latexPattern =
					/\\begin|\\frac|\\sqrt|\\sum|\\int|\\alpha|\\beta|\\gamma|\\pi|\\theta|\\sin|\\cos|\\tan|\\log|\\lim|\\matrix|\\pmatrix|\\bmatrix/;

				if (latexPattern.test(text)) {
					text = `\\[${text}\\]`;
				}
			}

			return `<div class="math-content">${text}</div>`;
		}

		function renderMath(container = document.body) {

			if (typeof renderMathInElement !== 'undefined') {

				renderMathInElement(container, {
					delimiters: [{
							left: "$$",
							right: "$$",
							display: true
						},
						{
							left: "\\(",
							right: "\\)",
							display: false
						},
						{
							left: "\\[",
							right: "\\]",
							display: true
						}
					],

					throwOnError: false,
					strict: false,

					trust: true
				});
			}
		}
	</script>
</body>


<?php
session_start();
require_once __DIR__ . "/../Connection/connection.php";
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 4;

if ($exam_id <= 0) {
	die("Invalid Exam ID");
}

/* ----------------------------------------------------------
	FETCH EXAM DETAILS
	---------------------------------------------------------- */

$sql = "SELECT e.*, c.Course_Name FROM exams e
		LEFT JOIN courses c ON c.Course_ID = e.Course_ID
		WHERE e.Exam_ID = ?
		LIMIT 1
	";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();

$examResult = $stmt->get_result();

if ($examResult->num_rows == 0) {
	die("Exam not found");
}

$examRow = $examResult->fetch_assoc();

$exam = [
	'Exam_ID'    => $examRow['Exam_ID'],
	'Exam_Name'  => $examRow['Exam_Name'],
	'Course'     => $examRow['Course_Name'] ?? 'Unknown Course',
	'Duration'   => $examRow['Duration'],
	'Start_Time' => $examRow['Start_Time'],
	'End_Time'   => $examRow['End_Time'],
];

/* ---------------------------------------------------------- 
	FETCH QUESTIONS
	---------------------------------------------------------- */

$qsql = "
		SELECT *
		FROM exam_questions
		WHERE Exam_ID = ?
		ORDER BY Sort_Order ASC, Question_ID ASC
	";

$qstmt = $con->prepare($qsql);
$qstmt->bind_param("i", $exam_id);
$qstmt->execute();

$qResult = $qstmt->get_result();

$questions = [];

while ($row = $qResult->fetch_assoc()) {
	$questions[] = [
		'Question_ID'   => $row['Question_ID'],
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

if (empty($questions)) {
	die("No questions found for this exam");
}

/* ----------------------------------------------------------
	STUDENT SESSION
	---------------------------------------------------------- */

$student = [
	'name'   => $_SESSION['student_name'] ?? 'Student',
	'roll'   => $_SESSION['Stud_ID'] ?? 'N/A',
	'avatar' => strtoupper(substr($_SESSION['student_name'] ?? 'ST', 0, 2))
];

/* ----------------------------------------------------------
	TOTALS
	---------------------------------------------------------- */

$totalMarks  = array_sum(array_column($questions, 'Marks'));

$durationSec = $exam['Duration'] * 60;

$questionsJson = json_encode($questions);
$examJson      = json_encode($exam);
$studentJson   = json_encode($student);
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1.0">
	<title>
		<?= htmlspecialchars($exam['Exam_Name']) ?> — ExamBuilder
	</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
	<link
		href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap"
		rel="stylesheet">
	<link rel="stylesheet"
		href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">

	<script defer
		src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js">
	</script>

	<script defer
		src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js">
	</script>
	<style>
		:root {
			--red-900: #7f0000;
			--red-800: #9b0000;
			--red-700: #b91c1c;
			--red-600: #dc2626;
			--red-500: #ef4444;
			--red-400: #f87171;
			--red-100: #fee2e2;
			--red-50: #fff5f5;
			--white: #ffffff;
			--gray-50: #f9fafb;
			--gray-100: #f3f4f6;
			--gray-200: #e5e7eb;
			--gray-300: #d1d5db;
			--gray-400: #9ca3af;
			--gray-500: #6b7280;
			--gray-600: #4b5563;
			--gray-700: #374151;
			--gray-900: #111827;
			--clr-answered: #16a34a;
			--clr-visited: #dc2626;
			--clr-marked: #d97706;
			--clr-not: #374151;
			--header-h: 68px;
			--progress-h: 5px;
			--sidebar-w: 280px;
			font-family: 'DM Sans', sans-serif;
		}

		*,
		*::before,
		*::after {
			box-sizing: border-box;
		}

		html,
		body {
			height: 100%;
			margin: 0;
			background: var(--gray-50);
		}

		body {
			overflow: hidden;
		}

		/* GATE */
		#fs-gate {
			position: fixed;
			inset: 0;
			z-index: 9999;
			background: linear-gradient(135deg, var(--red-900) 0%, #1a0000 100%);
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 32px;
			color: var(--white);
		}

		.gate-logo {
			font-family: 'Bebas Neue', sans-serif;
			font-size: clamp(2.4rem, 5vw, 4rem);
			letter-spacing: 4px;
		}

		.gate-card {
			background: rgba(255, 255, 255, .07);
			border: 1px solid rgba(255, 255, 255, .15);
			backdrop-filter: blur(12px);
			border-radius: 18px;
			padding: 48px 56px;
			text-align: center;
			max-width: 520px;
			width: 90%;
		}

		.gate-icon {
			width: 80px;
			height: 80px;
			background: rgba(239, 68, 68, .2);
			border: 2px solid var(--red-500);
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 2rem;
			margin: 0 auto 24px;
			animation: pulse-ring 2s infinite;
		}

		@keyframes pulse-ring {
			0% {
				box-shadow: 0 0 0 0 rgba(239, 68, 68, .5);
			}

			70% {
				box-shadow: 0 0 0 16px rgba(239, 68, 68, 0);
			}

			100% {
				box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
			}
		}

		.gate-card h2 {
			font-size: 1.5rem;
			font-weight: 700;
			margin-bottom: 8px;
		}

		.gate-card p {
			color: rgba(255, 255, 255, .7);
			font-size: .95rem;
			line-height: 1.6;
		}

		.btn-enter {
			background: linear-gradient(135deg, var(--red-600), var(--red-800));
			color: var(--white);
			border: none;
			border-radius: 10px;
			padding: 14px 40px;
			font-size: 1rem;
			font-weight: 600;
			letter-spacing: 1px;
			cursor: pointer;
			transition: transform .18s, box-shadow .18s;
		}

		.btn-enter:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 24px rgba(239, 68, 68, .45);
		}

		/* SHELL */
		#exam-shell {
			display: none;
			height: 100vh;
			flex-direction: column;
		}

		#exam-shell.visible {
			display: flex;
		}

		/* FS WARNING */
		#fs-warning {
			display: none;
			position: fixed;
			inset: 0;
			z-index: 8000;
			background: rgba(127, 0, 0, .92);
			backdrop-filter: blur(6px);
			flex-direction: column;
			align-items: center;
			justify-content: center;
			color: var(--white);
			text-align: center;
			gap: 20px;
		}

		#fs-warning.show {
			display: flex;
		}

		#fs-warning h2 {
			font-size: 2rem;
			font-family: 'Bebas Neue', sans-serif;
			letter-spacing: 3px;
		}

		#fs-warning p {
			max-width: 400px;
			opacity: .8;
		}

		/* VIOLATION BANNER */
		#violation-banner {
			display: none;
			position: fixed;
			top: calc(var(--header-h) + var(--progress-h));
			left: 0;
			right: 0;
			z-index: 500;
			background: #7f1d1d;
			color: var(--white);
			padding: 9px 20px;
			font-weight: 600;
			font-size: .88rem;
			align-items: center;
			gap: 10px;
			animation: slide-in .3s ease;
		}

		#violation-banner.show {
			display: flex;
		}

		@keyframes slide-in {
			from {
				transform: translateY(-100%);
			}

			to {
				transform: translateY(0);
			}
		}

		/* HEADER */
		#exam-header {
			height: var(--header-h);
			flex-shrink: 0;
			background: linear-gradient(90deg, var(--red-900) 0%, var(--red-700) 100%);
			display: flex;
			align-items: center;
			padding: 0 20px;
			gap: 16px;
			box-shadow: 0 2px 12px rgba(0, 0, 0, .3);
			z-index: 100;
		}

		.hdr-timer {
			display: flex;
			align-items: center;
			gap: 10px;
			background: rgba(0, 0, 0, .25);
			border-radius: 8px;
			padding: 8px 16px;
			min-width: 170px;
			border: 1px solid rgba(255, 255, 255, .15);
		}

		.timer-label {
			font-size: .68rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 1px;
			color: rgba(255, 255, 255, .6);
		}

		#countdown {
			font-family: 'JetBrains Mono', monospace;
			font-size: 1.5rem;
			font-weight: 600;
			color: #86efac;
			transition: color .4s;
		}

		#countdown.amber {
			color: #fcd34d;
		}

		#countdown.red {
			color: #fca5a5;
			animation: blink 1s step-end infinite;
		}

		@keyframes blink {

			0%,
			100% {
				opacity: 1
			}

			50% {
				opacity: .4
			}
		}

		.hdr-center {
			flex: 1;
			text-align: center;
			overflow: hidden;
		}

		.hdr-exam-name {
			font-family: 'Bebas Neue', sans-serif;
			font-size: 1.35rem;
			letter-spacing: 2px;
			color: var(--white);
			line-height: 1.1;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.hdr-course {
			font-size: .72rem;
			color: rgba(255, 255, 255, .65);
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.hdr-student {
			display: flex;
			align-items: center;
			gap: 10px;
			min-width: 190px;
			justify-content: flex-end;
		}

		.avatar-initials {
			width: 38px;
			height: 38px;
			border-radius: 50%;
			background: rgba(255, 255, 255, .18);
			border: 2px solid rgba(255, 255, 255, .35);
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 700;
			font-size: .85rem;
			color: var(--white);
			flex-shrink: 0;
		}

		.stu-name {
			font-size: .85rem;
			font-weight: 600;
			color: var(--white);
			white-space: nowrap;
		}

		.stu-roll {
			font-size: .7rem;
			color: rgba(255, 255, 255, .6);
			font-family: 'JetBrains Mono', monospace;
		}

		/* PROGRESS BAR */
		#progress-track {
			height: var(--progress-h);
			background: rgba(185, 28, 28, .2);
			flex-shrink: 0;
			overflow: hidden;
		}

		#progress-fill {
			height: 100%;
			background: linear-gradient(90deg, #fca5a5, var(--red-500));
			width: 0%;
			transition: width .45s cubic-bezier(.4, 0, .2, 1);
		}

		/* BODY */
		#exam-body {
			flex: 1;
			display: flex;
			overflow: hidden;
		}

		/* QUESTION STAGE */
		#question-stage {
			flex: 1;
			display: flex;
			flex-direction: column;
			overflow: hidden;
			background: var(--gray-50);
		}

		/* BREADCRUMB */
		#q-breadcrumb {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 14px 28px 8px;
			flex-shrink: 0;
		}

		.bc-position {
			font-family: 'Bebas Neue', sans-serif;
			font-size: 1.05rem;
			letter-spacing: 2px;
			color: var(--red-700);
		}

		.bc-dots {
			display: flex;
			gap: 5px;
			align-items: center;
		}

		.bc-dot {
			height: 6px;
			width: 6px;
			border-radius: 3px;
			background: var(--gray-200);
			transition: all .35s;
		}

		.bc-dot.done {
			background: var(--clr-answered);
		}

		.bc-dot.current {
			background: var(--red-600);
			width: 18px;
		}

		.bc-dot.marked {
			background: var(--clr-marked);
		}

		.bc-dot.visited {
			background: var(--clr-visited);
		}

		/* VIEWPORT */
		#q-viewport {
			flex: 1;
			overflow: hidden;
			position: relative;
			padding: 0 28px 4px;
		}

		/* SLIDE */
		.q-slide {
			position: absolute;
			inset: 0 28px;
			overflow-y: auto;
			transition: transform .32s cubic-bezier(.4, 0, .2, 1), opacity .32s ease;
		}

		.q-slide::-webkit-scrollbar {
			width: 5px;
		}

		.q-slide::-webkit-scrollbar-thumb {
			background: var(--gray-300);
			border-radius: 4px;
		}

		.q-slide.exit-left {
			transform: translateX(-64px);
			opacity: 0;
			pointer-events: none;
		}

		.q-slide.exit-right {
			transform: translateX(64px);
			opacity: 0;
			pointer-events: none;
		}

		.q-slide.enter-left {
			transform: translateX(64px);
			opacity: 0;
		}

		.q-slide.enter-right {
			transform: translateX(-64px);
			opacity: 0;
		}

		/* QUESTION CARD */
		.q-card {
			background: var(--white);
			border: 1.5px solid var(--gray-200);
			border-radius: 14px;
			padding: 28px 32px;
			box-shadow: 0 2px 14px rgba(0, 0, 0, .07);
		}

		.q-meta {
			display: flex;
			align-items: center;
			gap: 10px;
			margin-bottom: 16px;
		}

		.q-num {
			font-family: 'Bebas Neue', sans-serif;
			font-size: 1.15rem;
			letter-spacing: 1px;
			color: var(--red-700);
		}

		.q-type-badge {
			font-size: .68rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 1px;
			padding: 3px 10px;
			border-radius: 20px;
			background: var(--red-100);
			color: var(--red-800);
		}

		.q-marks {
			margin-left: auto;
			font-size: .78rem;
			font-weight: 600;
			color: var(--gray-500);
			background: var(--gray-100);
			padding: 3px 10px;
			border-radius: 20px;
		}

		.q-text {
			font-size: 1rem;
			line-height: 1.75;
			color: var(--gray-900);
			font-weight: 500;
			margin-bottom: 20px;
		}

		/* MCQ */
		.mcq-options {
			display: flex;
			flex-direction: column;
			gap: 10px;
		}

		.mcq-opt {
			display: flex;
			align-items: flex-start;
			gap: 12px;
			border: 1.5px solid var(--gray-200);
			border-radius: 9px;
			padding: 13px 16px;
			cursor: pointer;
			transition: all .2s;
			user-select: none;
		}

		.mcq-opt:hover {
			border-color: var(--red-400);
			background: var(--red-50);
		}

		.mcq-opt.selected {
			border-color: var(--red-600);
			background: var(--red-50);
			box-shadow: 0 0 0 3px rgba(220, 38, 38, .1);
		}

		.mcq-opt input[type="radio"] {
			display: none;
		}

		.opt-letter {
			width: 30px;
			height: 30px;
			border-radius: 50%;
			border: 2px solid var(--gray-300);
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 700;
			font-size: .8rem;
			color: var(--gray-600);
			flex-shrink: 0;
			transition: all .2s;
		}

		.mcq-opt.selected .opt-letter {
			background: var(--red-600);
			border-color: var(--red-600);
			color: var(--white);
		}

		.opt-text {
			font-size: .93rem;
			color: var(--gray-800);
			line-height: 1.5;
			padding-top: 4px;
		}

		/* FILL */
		.fill-wrap {
			display: flex;
			align-items: center;
			gap: 12px;
			flex-wrap: wrap;
			background: var(--gray-50);
			border-radius: 8px;
			padding: 16px 20px;
			border: 1.5px solid var(--gray-200);
			font-size: .95rem;
			color: var(--gray-700);
		}

		.fill-input {
			border: none;
			border-bottom: 2px solid var(--red-400);
			background: transparent;
			font-size: 1rem;
			color: var(--gray-900);
			padding: 4px 8px;
			outline: none;
			min-width: 180px;
			font-family: 'JetBrains Mono', monospace;
			transition: border-color .2s;
			flex: 1;
		}

		.fill-input:focus {
			border-color: var(--red-700);
		}

		/* OPEN */
		.open-textarea {
			width: 100%;
			resize: vertical;
			min-height: 160px;
			border: 1.5px solid var(--gray-200);
			border-radius: 8px;
			padding: 14px 16px;
			font-size: .93rem;
			color: var(--gray-900);
			font-family: 'DM Sans', sans-serif;
			line-height: 1.6;
			transition: border-color .2s;
			outline: none;
		}

		.open-textarea:focus {
			border-color: var(--red-500);
		}

		.word-counter {
			font-size: .75rem;
			color: var(--gray-400);
			text-align: right;
			margin-top: 6px;
			font-family: 'JetBrains Mono', monospace;
		}

		.word-counter.near-limit {
			color: var(--clr-marked);
		}

		.word-counter.over-limit {
			color: var(--red-600);
			font-weight: 700;
		}

		/* MATCH */
		.match-table {
			width: 100%;
			border-collapse: separate;
			border-spacing: 0;
		}

		.match-table th {
			background: var(--red-700);
			color: var(--white);
			font-size: .78rem;
			text-transform: uppercase;
			letter-spacing: 1px;
			padding: 10px 16px;
			font-weight: 600;
		}

		.match-table th:first-child {
			border-radius: 8px 0 0 0;
		}

		.match-table th:last-child {
			border-radius: 0 8px 0 0;
		}

		.match-table td {
			border-bottom: 1px solid var(--gray-100);
			padding: 10px 16px;
			font-size: .9rem;
			vertical-align: middle;
		}

		.match-table tr:last-child td {
			border-bottom: none;
		}

		/* MATCH — Bootstrap dropdown skin */
		.match-dropdown {
			width: 100%;
		}

		.match-dd-trigger {
			width: 100%;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 8px;
			border: 1.5px solid var(--gray-200);
			border-radius: 6px;
			padding: 8px 12px;
			font-size: .88rem;
			color: var(--gray-800);
			background: var(--white);
			text-align: left;
		}

		.match-dd-trigger:hover,
		.match-dd-trigger:focus {
			border-color: var(--red-500);
			box-shadow: none;
			color: var(--gray-800);
		}

		.match-dd-trigger.show,
		.match-dropdown.show .match-dd-trigger {
			border-color: var(--red-500);
		}

		.match-dd-selected {
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
			flex: 1;
			text-align: left;
		}

		.match-dd-menu {
			width: 100%;
			max-height: 220px;
			overflow-y: auto;
			padding: 4px;
			border: 1.5px solid var(--gray-200);
			border-radius: 8px;
			box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
		}

		.match-opt {
			padding: 9px 12px !important;
			border-radius: 6px;
			font-size: .88rem;
			color: var(--gray-800);
			display: block;
		}

		.match-opt:hover,
		.match-opt:focus {
			background: var(--red-50);
			color: var(--gray-900);
		}

		.match-opt.selected {
			background: var(--red-100) !important;
			font-weight: 600;
			color: var(--red-800);
		}

		/* ATTACHED IMAGE (optional, on mcq / open / fill) */
		.attached-img {
			max-width: 100%;
			border-radius: 10px;
			border: 1.5px solid var(--gray-200);
			margin-bottom: 16px;
			display: block;
		}

		.attached-note {
			font-size: .8rem;
			color: var(--gray-500);
			background: var(--gray-50);
			border-left: 3px solid var(--red-400);
			padding: 8px 14px;
			border-radius: 0 6px 6px 0;
			margin-bottom: 14px;
		}

		/* BOTTOM NAV */
		#q-nav-bar {
			display: flex;
			align-items: center;
			gap: 10px;
			padding: 13px 28px;
			background: var(--white);
			border-top: 1px solid var(--gray-200);
			flex-shrink: 0;
			box-shadow: 0 -2px 10px rgba(0, 0, 0, .06);
		}

		.nav-btn-prev,
		.nav-btn-next {
			padding: 10px 22px;
			border-radius: 9px;
			font-weight: 600;
			font-size: .88rem;
			border: none;
			cursor: pointer;
			transition: all .18s;
			display: flex;
			align-items: center;
			gap: 7px;
		}

		.nav-btn-prev {
			background: var(--gray-100);
			color: var(--gray-700);
		}

		.nav-btn-prev:hover {
			background: var(--gray-200);
		}

		.nav-btn-prev:disabled {
			opacity: .4;
			cursor: not-allowed;
		}

		.nav-btn-next {
			background: var(--red-600);
			color: var(--white);
		}

		.nav-btn-next:hover {
			background: var(--red-700);
		}

		.btn-mark-review {
			padding: 10px 16px;
			border-radius: 9px;
			font-weight: 600;
			font-size: .85rem;
			border: 1.5px solid var(--clr-marked);
			color: var(--clr-marked);
			background: transparent;
			cursor: pointer;
			transition: all .18s;
		}

		.btn-mark-review:hover,
		.btn-mark-review.active {
			background: var(--clr-marked);
			color: var(--white);
		}

		.btn-clear-ans {
			padding: 10px 14px;
			border-radius: 9px;
			font-weight: 600;
			font-size: .82rem;
			border: 1.5px solid var(--gray-200);
			color: var(--gray-500);
			background: transparent;
			cursor: pointer;
			transition: all .18s;
		}

		.btn-clear-ans:hover {
			border-color: var(--red-400);
			color: var(--red-500);
		}

		.nav-spacer {
			flex: 1;
		}

		/* SIDEBAR */
		#exam-sidebar {
			width: var(--sidebar-w);
			flex-shrink: 0;
			background: var(--white);
			border-left: 1px solid var(--gray-200);
			display: flex;
			flex-direction: column;
			overflow: hidden;
		}

		.sidebar-head {
			background: linear-gradient(135deg, var(--red-900), var(--red-700));
			color: var(--white);
			padding: 16px 18px;
			font-family: 'Bebas Neue', sans-serif;
			font-size: 1rem;
			letter-spacing: 2px;
		}

		.legend-section {
			padding: 14px 18px;
			border-bottom: 1px solid var(--gray-100);
		}

		.legend-item {
			display: flex;
			align-items: center;
			gap: 8px;
			font-size: .78rem;
			color: var(--gray-600);
			margin-bottom: 6px;
		}

		.legend-dot {
			width: 14px;
			height: 14px;
			border-radius: 4px;
			flex-shrink: 0;
		}

		.legend-count {
			margin-left: auto;
			font-weight: 700;
			font-family: 'JetBrains Mono', monospace;
			font-size: .82rem;
		}

		.palette-section {
			flex: 1;
			overflow-y: auto;
			padding: 14px 18px;
		}

		.palette-section::-webkit-scrollbar {
			width: 4px;
		}

		.palette-section::-webkit-scrollbar-thumb {
			background: var(--gray-200);
		}

		.palette-label {
			font-size: .72rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 1px;
			color: var(--gray-400);
			margin-bottom: 10px;
		}

		#palette-grid {
			display: grid;
			grid-template-columns: repeat(5, 1fr);
			gap: 6px;
		}

		.palette-btn {
			width: 100%;
			aspect-ratio: 1;
			border-radius: 6px;
			border: none;
			cursor: pointer;
			font-weight: 700;
			font-size: .78rem;
			background: var(--clr-not);
			color: var(--white);
			transition: transform .15s, opacity .15s;
			font-family: 'JetBrains Mono', monospace;
		}

		.palette-btn:hover {
			transform: scale(1.12);
			opacity: .85;
		}

		.palette-btn.answered {
			background: var(--clr-answered);
		}

		.palette-btn.visited {
			background: var(--clr-visited);
		}

		.palette-btn.marked {
			background: var(--clr-marked);
		}

		.palette-btn.current {
			outline: 3px solid var(--gray-900);
			outline-offset: 2px;
		}

		.stats-row {
			padding: 12px 18px;
			border-top: 1px solid var(--gray-100);
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 8px;
		}

		.stat-chip {
			background: var(--gray-50);
			border: 1px solid var(--gray-100);
			border-radius: 8px;
			padding: 8px 10px;
			text-align: center;
		}

		.stat-chip .sc-val {
			font-family: 'Bebas Neue', sans-serif;
			font-size: 1.4rem;
			color: var(--red-700);
			line-height: 1;
		}

		.stat-chip .sc-lbl {
			font-size: .65rem;
			text-transform: uppercase;
			letter-spacing: .5px;
			color: var(--gray-400);
			font-weight: 600;
		}

		.sidebar-submit {
			padding: 14px 18px;
		}

		.btn-submit-exam {
			width: 100%;
			padding: 14px;
			background: linear-gradient(135deg, var(--red-700), var(--red-900));
			color: var(--white);
			border: none;
			border-radius: 10px;
			font-size: 1rem;
			font-weight: 700;
			cursor: pointer;
			letter-spacing: 1px;
			font-family: 'Bebas Neue', sans-serif;
			transition: all .2s;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			box-shadow: 0 4px 16px rgba(185, 28, 28, .35);
		}

		.btn-submit-exam:hover {
			background: linear-gradient(135deg, var(--red-600), var(--red-800));
			box-shadow: 0 6px 24px rgba(185, 28, 28, .5);
			transform: translateY(-1px);
		}

		@media(max-width:860px) {
			:root {
				--sidebar-w: 220px;
			}

			.hdr-student .stu-name {
				display: none;
			}

			#palette-grid {
				grid-template-columns: repeat(4, 1fr);
			}

			#q-viewport {
				padding: 0 16px 4px;
			}

			#q-breadcrumb {
				padding: 12px 16px 6px;
			}

			#q-nav-bar {
				padding: 10px 16px;
			}
		}

		@media(max-width:640px) {
			:root {
				--sidebar-w: 0px;
			}

			#exam-sidebar {
				display: none;
			}

			.q-card {
				padding: 20px 16px;
			}
		}
	</style>
</head>

<body>

	<div id="fs-gate">
		<div class="gate-logo"><i class="bi bi-mortarboard-fill me-2"></i>ExamBuilder</div>
		<div class="gate-card">
			<div class="gate-icon"><i class="bi bi-shield-lock-fill"></i></div>
			<h2>Secure Exam Environment</h2>
			<p>This exam runs in a proctored full-screen environment.<br>
				Tab switching, right-clicking, and keyboard shortcuts are disabled.<br>
				<strong>3 tab-switch violations</strong> will auto-submit your exam.
			</p>
			<div class="mt-4 text-start" style="font-size:.82rem;opacity:.65;margin-bottom:24px;">
				<div class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>
						<?= htmlspecialchars($exam['Exam_Name']) ?>
					</strong></div>
				<div class="mb-1"><i class="bi bi-clock me-2"></i>
					<?= $exam['Duration'] ?> minutes &nbsp;|&nbsp;
					<?= $totalMarks ?> marks
				</div>
				<div><i class="bi bi-person me-2"></i>
					<?= htmlspecialchars($student['name']) ?> &nbsp;(
					<?= htmlspecialchars($student['roll']) ?>)
				</div>
			</div>
			<button class="btn-enter" id="btn-enter-fs"><i class="bi bi-fullscreen me-2"></i>ENTER FULLSCREEN &
				BEGIN</button>
		</div>
	</div>

	<div id="fs-warning">
		<div style="font-size:3.5rem;"><i class="bi bi-exclamation-triangle-fill"></i></div>
		<h2>Return to Fullscreen</h2>
		<p>You have exited fullscreen mode. The exam is paused until you return.</p>
		<button class="btn-enter" onclick="reEnterFullscreen()"><i class="bi bi-fullscreen me-2"></i>Return to
			Fullscreen</button>
	</div>

	<div id="exam-shell">
		<div id="violation-banner"><i class="bi bi-exclamation-triangle-fill"></i><span id="violation-text"></span>
		</div>

		<header id="exam-header">
			<div class="hdr-timer">
				<div>
					<div class="timer-label">Time Left</div>
					<div id="countdown">90:00</div>
				</div>
				<div style="margin-left:8px;"><i class="bi bi-hourglass-split"
						style="color:rgba(255,255,255,.5);font-size:1.2rem;"></i></div>
			</div>
			<div class="hdr-center">
				<div class="hdr-exam-name">
					<?= htmlspecialchars($exam['Exam_Name']) ?>
				</div>
				<div class="hdr-course">
					<?= htmlspecialchars($exam['Course']) ?>
				</div>
			</div>
			<div class="hdr-student">
				<div>
					<div class="stu-name">
						<?= htmlspecialchars($student['name']) ?>
					</div>
					<div class="stu-roll">
						<?= htmlspecialchars($student['roll']) ?>
					</div>
				</div>
				<div class="avatar-initials">
					<?= htmlspecialchars($student['avatar']) ?>
				</div>
			</div>
		</header>

		<div id="progress-track">
			<div id="progress-fill"></div>
		</div>

		<div id="exam-body">
			<div id="question-stage">
				<div id="q-breadcrumb">
					<div class="bc-position" id="bc-pos">Question 1 of
						<?= count($questions) ?>
					</div>
					<div class="bc-dots" id="bc-dots"></div>
				</div>
				<div id="q-viewport"></div>
				<div id="q-nav-bar">
					<button class="nav-btn-prev" id="btn-prev" onclick="navigate(-1)" disabled><i
							class="bi bi-chevron-left"></i>
						Previous</button>
					<button class="btn-mark-review" id="btn-mark" onclick="toggleMark()"><i
							class="bi bi-flag me-1"></i>Mark for
						Review</button>
					<button class="btn-clear-ans" onclick="clearCurrentAnswer()"><i
							class="bi bi-x-circle me-1"></i>Clear</button>
					<div class="nav-spacer"></div>
					<button class="nav-btn-next" id="btn-next" onclick="navigate(1)">Next <i
							class="bi bi-chevron-right"></i></button>
				</div>
			</div>

			<aside id="exam-sidebar">
				<div class="sidebar-head"><i class="bi bi-grid-3x3-gap me-2"></i>Question Palette</div>
				<div class="legend-section">
					<div class="legend-item">
						<div class="legend-dot" style="background:var(--clr-answered)"></div>Answered<span
							class="legend-count" id="cnt-answered" style="color:var(--clr-answered)">0</span>
					</div>
					<div class="legend-item">
						<div class="legend-dot" style="background:var(--clr-visited)"></div>Visited, Unanswered<span
							class="legend-count" id="cnt-visited" style="color:var(--clr-visited)">0</span>
					</div>
					<div class="legend-item">
						<div class="legend-dot" style="background:var(--clr-marked)"></div>Marked for Review<span
							class="legend-count" id="cnt-marked" style="color:var(--clr-marked)">0</span>
					</div>
					<div class="legend-item">
						<div class="legend-dot" style="background:var(--clr-not)"></div>Not Visited<span
							class="legend-count" id="cnt-not">0</span>
					</div>
				</div>
				<div class="palette-section">
					<div class="palette-label">Jump to Question</div>
					<div id="palette-grid"></div>
				</div>
				<div class="stats-row">
					<div class="stat-chip">
						<div class="sc-val">
							<?= count($questions) ?>
						</div>
						<div class="sc-lbl">Total Qs</div>
					</div>
					<div class="stat-chip">
						<div class="sc-val">
							<?= $totalMarks ?>
						</div>
						<div class="sc-lbl">Total Marks</div>
					</div>
					<div class="stat-chip">
						<div class="sc-val" id="stat-attempted">0</div>
						<div class="sc-lbl">Attempted</div>
					</div>
					<div class="stat-chip">
						<div class="sc-val" id="stat-remain">
							<?= count($questions) ?>
						</div>
						<div class="sc-lbl">Remaining</div>
					</div>
				</div>
				<div class="sidebar-submit"><button class="btn-submit-exam" id="btn-submit"><i
							class="bi bi-send-check-fill"></i> SUBMIT EXAM</button></div>
			</aside>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script>
		const QUESTIONS = <?= $questionsJson ?>;
		const EXAM = <?= $examJson ?>;
		const STUDENT = <?= $studentJson ?>;
		const DURATION_S = <?= $durationSec ?>;
		const TOTAL = QUESTIONS.length;
		const TYPE_LABELS = {
			mcq: 'Multiple Choice',
			fill: 'Fill in the Blanks',
			open: 'Open Ended',
			match: 'Match the Following'
		};

		const state = {
			currentIdx: 0,
			answers: {},
			status: {},
			tabViolations: 0,
			examActive: false,
			timerLeft: DURATION_S,
			timerInterval: null
		};
		QUESTIONS.forEach(q => state.status[q.Question_ID] = 'not');

		/* ── FULLSCREEN ─────────────────────────────────── */
		document.getElementById('btn-enter-fs').addEventListener('click', () => {
			const el = document.documentElement;
			(el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen).call(el);
		});
		document.addEventListener('fullscreenchange', onFsChange);
		document.addEventListener('webkitfullscreenchange', onFsChange);

		function onFsChange() {
			const inFs = !!document.fullscreenElement || !!document.webkitFullscreenElement;
			if (!state.examActive && inFs) startExam();
			else if (state.examActive && !inFs) document.getElementById('fs-warning').classList.add('show');
		}

		function reEnterFullscreen() {
			document.getElementById('fs-warning').classList.remove('show');
			const el = document.documentElement;
			(el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen).call(el);
		}

		function startExam() {
			state.examActive = true;
			document.getElementById('fs-gate').style.display = 'none';
			document.getElementById('exam-shell').classList.add('visible');
			buildPalette();
			buildDots();
			goTo(0, 'none');
			startTimer();
			attachProctoring();
		}

		/* ── TIMER ──────────────────────────────────────── */
		function startTimer() {
			renderClock();
			state.timerInterval = setInterval(() => {
				state.timerLeft--;
				renderClock();
				if (state.timerLeft <= 0) {
					clearInterval(state.timerInterval);
					autoSubmit('Time is up!');
				}
			}, 1000);
		}

		function renderClock() {
			const el = document.getElementById('countdown');
			const m = Math.floor(state.timerLeft / 60),
				s = state.timerLeft % 60;
			el.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
			el.className = state.timerLeft <= 60 ? 'red' : state.timerLeft <= 300 ? 'amber' : '';
		}

		/* ── PROCTORING ─────────────────────────────────── */
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
					title: `⚠️ Tab Switch (${state.tabViolations}/3)`,
					html: `<p>Switching tabs is <strong>not allowed</strong>.</p><p class="mt-2 text-danger"><strong>${rem} more</strong> will auto-submit your exam.</p>`,
					confirmButtonText: 'Return to Exam',
					confirmButtonColor: '#dc2626',
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
			const b = document.getElementById('violation-banner');
			document.getElementById('violation-text').textContent = msg;
			b.classList.add('show');
			setTimeout(() => b.classList.remove('show'), 6000);
		}

		/* ── CORE: goTo ─────────────────────────────────── */
		function goTo(newIdx, direction) {
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
			const oldSlide = viewport.querySelector('.q-slide');

			// Animate out
			if (oldSlide && direction !== 'none') {
				oldSlide.classList.add(direction === 'right' ? 'exit-left' : 'exit-right');
				setTimeout(() => oldSlide.remove(), 340);
			} else if (oldSlide) {
				oldSlide.remove();
			}

			// Build new slide
			const slide = document.createElement('div');
			slide.className = 'q-slide';
			if (direction !== 'none') slide.classList.add(direction === 'right' ? 'enter-left' : 'enter-right');
			slide.innerHTML = buildHTML(q, newIdx);
			viewport.appendChild(slide);

			requestAnimationFrame(() => {
				requestAnimationFrame(() => {
					slide.classList.remove('enter-left', 'enter-right');
				});
				restoreAnswer(q);
				attachListeners(q);
				renderMath(slide);
			});

			updateNavBtns();
			updateBreadcrumb();
			updatePalette();
			updateProgress();
		}

		/* ── BUILD HTML ─────────────────────────────────── */
		function buildAttachedImage(q) {
			if (!q.Image_Path) return '';
			return `<div class="attached-note"><i class="bi bi-info-circle me-1"></i>Refer to the image below before answering.</div>
      <img src="${esc(q.Image_Path)}" class="attached-img" alt="Question attachment" loading="lazy">`;
		}

		function buildHTML(q, idx) {
			let body = '';
			if (q.Question_Type === 'mcq') {
				const opts = JSON.parse(q.Options_JSON || '[]');
				body = `${buildAttachedImage(q)}<div class="mcq-options">${opts.map(o => `
      <label class="mcq-opt" data-opt="${o.letter}">
        <input type="radio" name="r_${q.Question_ID}" value="${o.letter}">
        <div class="opt-letter">${o.letter}</div>
		<div class="opt-text math-content">${formatMath(o.text)}</div>

      </label>`).join('')}</div>`;
			} else if (q.Question_Type === 'fill') {
				body = ` ${buildAttachedImage(q)} <div class="fill-wrap"> <span class="math-content">${formatMath(q.Question_Text)}</span> </div> <div class="fill-wrap mt-3"> Answer:&nbsp; <input type="text" id="fill-inp" class="fill-input" placeholder="Type your answer…" autocomplete="off" spellcheck="false"> </div>`;
			} else if (q.Question_Type === 'open') {
				const wl = q.Word_Limit || 300; body = ` ${buildAttachedImage(q)} <div class="math-content mb-3"> ${formatMath(q.Question_Text)} </div> <textarea class="open-textarea" id="open-ta" data-wl="${wl}" placeholder="Write your answer here…" rows="7"></textarea> <div class="word-counter" id="open-wc"> 0 / ${wl} words </div>`;
			} else if (q.Question_Type === 'match') {
				const pairs = JSON.parse(q.Pairs_JSON || '[]');
				const shuffled = [...pairs.map(p => p.b)].sort(() => Math.random() - .5);

				const optionsHTML = shuffled.map(r =>
					`<li><a class="dropdown-item match-opt" href="#" data-val="${esc(r)}">
            <span class="math-content">${formatMath(r)}</span>
          </a></li>`
				).join('');

				body = `<table class="match-table"><thead><tr><th>Column A</th><th>Column B (Match)</th></tr></thead><tbody>${pairs.map((p, i) => `<tr><td> <strong class="math-content"> ${formatMath(p.a)} </strong> </td><td>
            <div class="dropdown match-dropdown" data-idx="${i}">
              <button class="btn match-dd-trigger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="match-dd-selected math-content">— Select —</span>
              </button>
              <ul class="dropdown-menu match-dd-menu">${optionsHTML}</ul>
            </div>
          </td></tr>`).join('')
					}</tbody></table>`;
			}
			return `<div class="q-card">
    <div class="q-meta">
      <div class="q-num">Q${idx + 1}</div>
      <div class="q-type-badge">${TYPE_LABELS[q.Question_Type] || q.Question_Type}</div>
      <div class="q-marks"><i class="bi bi-star-fill me-1" style="color:var(--clr-marked)"></i>${q.Marks} mark${q.Marks != 1 ? 's' : ''}</div>
    </div>
	<div class="q-text math-content">${formatMath(q.Question_Text)}</div>
    ${body}
  </div>`;
		}

		/* ── RESTORE ANSWER ─────────────────────────────── */
		function restoreAnswer(q) {
			const saved = state.answers[q.Question_ID];
			if (saved === undefined || saved === null) return;
			if (q.Question_Type === 'mcq') {
				document.querySelectorAll('.mcq-opt').forEach(l => {
					if (l.dataset.opt === saved) {
						l.classList.add('selected');
						l.querySelector('input').checked = true;
					}
				});
			} else if (q.Question_Type === 'fill') {
				const inp = document.getElementById('fill-inp');
				if (inp) inp.value = saved;
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
						opt.classList.add('selected');
						dd.querySelector('.match-dd-selected').innerHTML = opt.querySelector('.math-content').innerHTML;
					}
				});
			}
		}

		/* ── ATTACH LISTENERS ───────────────────────────── */
		function attachListeners(q) {
			const qid = q.Question_ID;
			if (q.Question_Type === 'mcq') {
				document.querySelectorAll('.mcq-opt').forEach(lbl => lbl.addEventListener('click', () => {
					document.querySelectorAll('.mcq-opt').forEach(l => l.classList.remove('selected'));
					lbl.classList.add('selected');
					lbl.querySelector('input').checked = true;
					state.answers[qid] = lbl.dataset.opt;
					setStatus(qid, 'answered');
				}));
			} else if (q.Question_Type === 'fill') {
				const inp = document.getElementById('fill-inp');
				if (inp) inp.addEventListener('input', () => {
					state.answers[qid] = inp.value.trim();
					setStatus(qid, inp.value.trim() ? 'answered' : 'visited');
				});
			} else if (q.Question_Type === 'open') {
				const ta = document.getElementById('open-ta');
				if (ta) ta.addEventListener('input', () => {
					updateWC(ta);
					state.answers[qid] = ta.value;
					setStatus(qid, ta.value.trim() ? 'answered' : 'visited');
				});
			} else if (q.Question_Type === 'match') {
				document.querySelectorAll('.match-dropdown').forEach(dd => {
					const idx = dd.dataset.idx;
					const trigger = dd.querySelector('.match-dd-trigger');
					const selectedLabel = dd.querySelector('.match-dd-selected');

					dd.querySelectorAll('.match-opt').forEach(opt => {
						opt.addEventListener('click', (e) => {
							e.preventDefault();
							const val = opt.dataset.val;

							dd.querySelectorAll('.match-opt').forEach(o => o.classList.remove('selected'));
							opt.classList.add('selected');
							selectedLabel.innerHTML = opt.querySelector('.math-content').innerHTML;

							if (!state.answers[qid] || typeof state.answers[qid] !== 'object') state.answers[qid] = {};
							state.answers[qid][idx] = val;

							const allDD = document.querySelectorAll('.match-dropdown');
							const allFilled = [...allDD].every(d => d.querySelector('.match-opt.selected'));
							setStatus(qid, allFilled ? 'answered' : 'visited');

							renderMath(trigger);
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
			wc.className = 'word-counter' + (words > wl ? ' over-limit' : words > wl * .85 ? ' near-limit' : '');
		}

		/* ── STATUS ─────────────────────────────────────── */
		function setStatus(qid, s) {
			if (state.status[qid] === 'marked' && (s === 'visited' || s === 'not')) return;
			state.status[qid] = s;
			updatePalette();
		}

		/* ── NAVIGATE ───────────────────────────────────── */
		function navigate(dir) {
			const n = state.currentIdx + dir;
			if (n < 0 || n >= TOTAL) return;
			goTo(n, dir > 0 ? 'right' : 'left');
		}

		function toggleMark() {
			const q = QUESTIONS[state.currentIdx],
				qid = q.Question_ID;
			const wasMarked = state.status[qid] === 'marked';
			state.status[qid] = wasMarked ? (state.answers[qid] ? 'answered' : 'visited') : 'marked';
			updateMarkBtn();
			updatePalette();
		}

		function clearCurrentAnswer() {
			const q = QUESTIONS[state.currentIdx],
				qid = q.Question_ID;
			state.answers[qid] = null;
			if (q.Question_Type === 'mcq') {
				document.querySelectorAll('.mcq-opt').forEach(l => l.classList.remove('selected'));
				document.querySelectorAll(`input[name="r_${qid}"]`).forEach(i => i.checked = false);
			} else if (q.Question_Type === 'fill') {
				const i = document.getElementById('fill-inp');
				if (i) i.value = '';
			} else if (q.Question_Type === 'match') {
				document.querySelectorAll('.match-dropdown').forEach(dd => {
					dd.querySelectorAll('.match-opt').forEach(o => o.classList.remove('selected'));
					dd.querySelector('.match-dd-selected').textContent = '— Select —';
				});
			} else {
				const ta = document.getElementById('open-ta');
				if (ta) {
					ta.value = '';
					updateWC(ta);
				}
			}
			if (state.status[qid] !== 'marked') setStatus(qid, 'visited');
		}

		/* ── UI CHROME ──────────────────────────────────── */
		function updateNavBtns() {
			document.getElementById('btn-prev').disabled = state.currentIdx === 0;
			const nb = document.getElementById('btn-next');
			if (state.currentIdx === TOTAL - 1) {
				nb.innerHTML = '<i class="bi bi-send-check-fill me-1"></i>Submit';
				nb.onclick = confirmSubmit;
				nb.style.background = 'linear-gradient(135deg,var(--red-700),var(--red-900))';
			} else {
				nb.innerHTML = 'Next <i class="bi bi-chevron-right"></i>';
				nb.onclick = () => navigate(1);
				nb.style.background = '';
			}
			updateMarkBtn();
		}

		function updateMarkBtn() {
			const q = QUESTIONS[state.currentIdx],
				isM = state.status[q.Question_ID] === 'marked';
			const b = document.getElementById('btn-mark');
			b.className = 'btn-mark-review' + (isM ? ' active' : '');
			b.innerHTML = isM ? '<i class="bi bi-flag-fill me-1"></i>Marked' : '<i class="bi bi-flag me-1"></i>Mark for Review';
		}

		function updateBreadcrumb() {
			document.getElementById('bc-pos').textContent = `Question ${state.currentIdx + 1} of ${TOTAL}`;
			updateDots();
		}

		function buildDots() {
			const wrap = document.getElementById('bc-dots');
			wrap.innerHTML = '';
			QUESTIONS.forEach((_, i) => {
				const d = document.createElement('div');
				d.id = `dot-${i}`;
				d.className = 'bc-dot';
				wrap.appendChild(d);
			});
			updateDots();
		}

		function updateDots() {
			QUESTIONS.forEach((q, i) => {
				const d = document.getElementById(`dot-${i}`);
				if (!d) return;
				const s = state.status[q.Question_ID];
				d.className = 'bc-dot' + (i === state.currentIdx ? ' current' : s === 'answered' ? ' done' : s === 'marked' ? ' marked' : s === 'visited' ? ' visited' : '');
				d.style.width = i === state.currentIdx ? '18px' : '6px';
			});
		}

		function updateProgress() {
			const n = Object.values(state.status).filter(s => s === 'answered').length;
			document.getElementById('progress-fill').style.width = (n / TOTAL * 100) + '%';
		}

		function buildPalette() {
			const g = document.getElementById('palette-grid');
			g.innerHTML = '';
			QUESTIONS.forEach((q, i) => {
				const b = document.createElement('button');
				b.className = 'palette-btn not';
				b.id = `pb-${q.Question_ID}`;
				b.textContent = i + 1;
				b.addEventListener('click', () => goTo(i, i > state.currentIdx ? 'right' : i < state.currentIdx ? 'left' : 'none'));
				g.appendChild(b);
			});
			updatePalette();
		}

		function updatePalette() {
			let cnt = {
				answered: 0,
				visited: 0,
				marked: 0,
				not: 0
			};
			QUESTIONS.forEach((q, i) => {
				const s = state.status[q.Question_ID],
					b = document.getElementById(`pb-${q.Question_ID}`);
				if (!b) return;
				b.className = 'palette-btn ' + s + (i === state.currentIdx ? ' current' : '');
				cnt[s]++;
			});
			['answered', 'visited', 'marked', 'not'].forEach(k => {
				const el = document.getElementById(`cnt-${k}`);
				if (el) el.textContent = cnt[k];
			});
			const att = cnt.answered;
			document.getElementById('stat-attempted').textContent = att;
			document.getElementById('stat-remain').textContent = TOTAL - att;
			updateProgress();
			updateDots();
		}

		/* ── SUBMIT ─────────────────────────────────────── */
		document.getElementById('btn-submit').addEventListener('click', confirmSubmit);

		function confirmSubmit() {
			const ans = Object.values(state.status).filter(s => s === 'answered').length,
				un = TOTAL - ans;
			Swal.fire({
				title: 'Submit Exam?',
				html: `<div class="text-start"><div class="mb-2"><span class="badge bg-success me-2">${ans}</span>Answered</div><div class="mb-2"><span class="badge bg-danger me-2">${un}</span>Unanswered / Skipped</div><hr><p class="text-muted small mt-2">Once submitted, you cannot make any changes.</p></div>`,
				icon: 'question',
				showCancelButton: true,
				confirmButtonText: '<i class="bi bi-send-check-fill me-1"></i> Yes, Submit Now',
				cancelButtonText: 'Review Answers',
				confirmButtonColor: '#dc2626',
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
					confirmButtonColor: '#dc2626',
					allowOutsideClick: false,
					allowEscapeKey: false
				})
				.then(() => submitExam(true));
		}
		async function submitExam(auto = false) {
			clearInterval(state.timerInterval);
			state.examActive = false;
			if (document.exitFullscreen) document.exitFullscreen().catch(() => {});
			const payload = {
				exam_id: EXAM.Exam_ID,
				student_id: STUDENT.roll,
				auto_submit: auto,
				time_taken: DURATION_S - state.timerLeft,
				violations: state.tabViolations,
				answers: buildPayload()
			};
			Swal.fire({
				title: 'Submitting…',
				html: '<div class="spinner-border text-danger" role="status"></div><p class="mt-3">Please wait…</p>',
				allowOutsideClick: false,
				allowEscapeKey: false,
				showConfirmButton: false
			});
			try {
				const res = await fetch('submit-exam.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(payload)
				});
				const data = await res.json();
				if (data.success) {
					Swal.fire({
						icon: 'success',
						title: 'Exam Submitted!',
						html: `<p>Responses saved.</p><p class="mt-2"><strong>Time:</strong> ${fmt(payload.time_taken)}</p><p><strong>Submitted:</strong> ${Object.keys(payload.answers).length}/${TOTAL}</p>`,
						confirmButtonColor: '#dc2626',

						confirmButtonText: 'Done',
						allowOutsideClick: false,
					}).then(() => {
						window.location.href = 'dashboard.php';
					});
				} else throw new Error(data.message || 'Server error');
			} catch (err) {
				Swal.fire({
					icon: 'error',
					title: 'Submission Failed',
					text: 'Could not reach server. Inform invigilator. (' + err.message + ')',
					confirmButtonColor: '#dc2626'
				});
			}
		}

		function buildPayload() {
			const out = {};
			QUESTIONS.forEach(q => {
				const a = state.answers[q.Question_ID];
				if (a !== undefined && a !== null) out[q.Question_ID] = {
					type: q.Question_Type,
					answer: a,
					status: state.status[q.Question_ID]
				};
			});
			return out;
		}

		/* ── HELPERS ────────────────────────────────────── */
		function esc(s) {
			if (!s) return '';
			return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
		}

		function fmt(sec) {
			return `${Math.floor(sec / 60)}m ${sec % 60}s`;
		}

		function formatMath(text) {
			if (!text) return '';

			// Escape HTML first
			text = esc(text);

			// If already contains latex delimiters, keep as-is
			const hasDelimiter =
				text.includes('\\(') ||
				text.includes('\\[') ||
				text.includes('$$');

			// Auto-wrap raw LaTeX environments
			if (!hasDelimiter) {

				// Detect common LaTeX commands/environments
				const latexPattern =
					/\\begin|\\frac|\\sqrt|\\sum|\\int|\\alpha|\\beta|\\gamma|\\pi|\\theta|\\sin|\\cos|\\tan|\\log|\\lim|\\matrix|\\pmatrix|\\bmatrix/;

				if (latexPattern.test(text)) {
					text = `\\[${text}\\]`;
				}
			}

			return `<div class="math-content">${text}</div>`;
		}

		function renderMath(container = document.body) {

			if (typeof renderMathInElement !== 'undefined') {

				renderMathInElement(container, {
					delimiters: [{
							left: "$$",
							right: "$$",
							display: true
						},
						{
							left: "\\(",
							right: "\\)",
							display: false
						},
						{
							left: "\\[",
							right: "\\]",
							display: true
						}
					],

					throwOnError: false,
					strict: false,

					trust: true
				});
			}
		}
	</script>
</body>



<?php
session_start();
require_once __DIR__ . "/../Connection/connection.php";
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 4;

if ($exam_id <= 0) {
	die("Invalid Exam ID");
}

/* ----------------------------------------------------------
	FETCH EXAM DETAILS
	---------------------------------------------------------- */

$sql = "SELECT e.*, c.Course_Name FROM exams e
		LEFT JOIN courses c ON c.Course_ID = e.Course_ID
		WHERE e.Exam_ID = ?
		LIMIT 1
	";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();

$examResult = $stmt->get_result();

if ($examResult->num_rows == 0) {
	die("Exam not found");
}

$examRow = $examResult->fetch_assoc();

$exam = [
	'Exam_ID'    => $examRow['Exam_ID'],
	'Exam_Name'  => $examRow['Exam_Name'],
	'Course'     => $examRow['Course_Name'] ?? 'Unknown Course',
	'Duration'   => $examRow['Duration'],
	'Start_Time' => $examRow['Start_Time'],
	'End_Time'   => $examRow['End_Time'],
];

/* ---------------------------------------------------------- 
	FETCH QUESTIONS
	---------------------------------------------------------- */

$qsql = "
		SELECT *
		FROM exam_questions
		WHERE Exam_ID = ?
		ORDER BY Sort_Order ASC, Question_ID ASC
	";

$qstmt = $con->prepare($qsql);
$qstmt->bind_param("i", $exam_id);
$qstmt->execute();

$qResult = $qstmt->get_result();

$questions = [];

while ($row = $qResult->fetch_assoc()) {
	$questions[] = [
		'Question_ID'   => $row['Question_ID'],
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

if (empty($questions)) {
	die("No questions found for this exam");
}

/* ----------------------------------------------------------
	STUDENT SESSION
	---------------------------------------------------------- */

$student = [
	'name'   => $_SESSION['student_name'] ?? 'Student',
	'roll'   => $_SESSION['Stud_ID'] ?? 'N/A',
	'avatar' => strtoupper(substr($_SESSION['student_name'] ?? 'ST', 0, 2))
];

/* ----------------------------------------------------------
	TOTALS
	---------------------------------------------------------- */

$totalMarks  = array_sum(array_column($questions, 'Marks'));

$durationSec = $exam['Duration'] * 60;

$questionsJson = json_encode($questions);
$examJson      = json_encode($exam);
$studentJson   = json_encode($student);
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1.0">
	<title>
		<?= htmlspecialchars($exam['Exam_Name']) ?> — ExamBuilder
	</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
	<link
		href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap"
		rel="stylesheet">
	<link rel="stylesheet"
		href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">

	<script defer
		src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js">
	</script>

	<script defer
		src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js">
	</script>
	<style>
		:root {
			--red-900: #7f0000;
			--red-800: #9b0000;
			--red-700: #b91c1c;
			--red-600: #dc2626;
			--red-500: #ef4444;
			--red-400: #f87171;
			--red-100: #fee2e2;
			--red-50: #fff5f5;
			--white: #ffffff;
			--gray-50: #f9fafb;
			--gray-100: #f3f4f6;
			--gray-200: #e5e7eb;
			--gray-300: #d1d5db;
			--gray-400: #9ca3af;
			--gray-500: #6b7280;
			--gray-600: #4b5563;
			--gray-700: #374151;
			--gray-900: #111827;
			--clr-answered: #16a34a;
			--clr-visited: #dc2626;
			--clr-marked: #d97706;
			--clr-not: #374151;
			--header-h: 68px;
			--progress-h: 5px;
			--sidebar-w: 280px;
			font-family: 'DM Sans', sans-serif;
		}

		*,
		*::before,
		*::after {
			box-sizing: border-box;
		}

		html,
		body {
			height: 100%;
			margin: 0;
			background: var(--gray-50);
		}

		body {
			overflow: hidden;
		}

		/* GATE */
		#fs-gate {
			position: fixed;
			inset: 0;
			z-index: 9999;
			background: linear-gradient(135deg, var(--red-900) 0%, #1a0000 100%);
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 32px;
			color: var(--white);
		}

		.gate-logo {
			font-family: 'Bebas Neue', sans-serif;
			font-size: clamp(2.4rem, 5vw, 4rem);
			letter-spacing: 4px;
		}

		.gate-card {
			background: rgba(255, 255, 255, .07);
			border: 1px solid rgba(255, 255, 255, .15);
			backdrop-filter: blur(12px);
			border-radius: 18px;
			padding: 48px 56px;
			text-align: center;
			max-width: 520px;
			width: 90%;
		}

		.gate-icon {
			width: 80px;
			height: 80px;
			background: rgba(239, 68, 68, .2);
			border: 2px solid var(--red-500);
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 2rem;
			margin: 0 auto 24px;
			animation: pulse-ring 2s infinite;
		}

		@keyframes pulse-ring {
			0% {
				box-shadow: 0 0 0 0 rgba(239, 68, 68, .5);
			}

			70% {
				box-shadow: 0 0 0 16px rgba(239, 68, 68, 0);
			}

			100% {
				box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
			}
		}

		.gate-card h2 {
			font-size: 1.5rem;
			font-weight: 700;
			margin-bottom: 8px;
		}

		.gate-card p {
			color: rgba(255, 255, 255, .7);
			font-size: .95rem;
			line-height: 1.6;
		}

		.btn-enter {
			background: linear-gradient(135deg, var(--red-600), var(--red-800));
			color: var(--white);
			border: none;
			border-radius: 10px;
			padding: 14px 40px;
			font-size: 1rem;
			font-weight: 600;
			letter-spacing: 1px;
			cursor: pointer;
			transition: transform .18s, box-shadow .18s;
		}

		.btn-enter:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 24px rgba(239, 68, 68, .45);
		}

		/* SHELL */
		#exam-shell {
			display: none;
			height: 100vh;
			flex-direction: column;
		}

		#exam-shell.visible {
			display: flex;
		}

		/* FS WARNING */
		#fs-warning {
			display: none;
			position: fixed;
			inset: 0;
			z-index: 8000;
			background: rgba(127, 0, 0, .92);
			backdrop-filter: blur(6px);
			flex-direction: column;
			align-items: center;
			justify-content: center;
			color: var(--white);
			text-align: center;
			gap: 20px;
		}

		#fs-warning.show {
			display: flex;
		}

		#fs-warning h2 {
			font-size: 2rem;
			font-family: 'Bebas Neue', sans-serif;
			letter-spacing: 3px;
		}

		#fs-warning p {
			max-width: 400px;
			opacity: .8;
		}

		/* VIOLATION BANNER */
		#violation-banner {
			display: none;
			position: fixed;
			top: calc(var(--header-h) + var(--progress-h));
			left: 0;
			right: 0;
			z-index: 500;
			background: #7f1d1d;
			color: var(--white);
			padding: 9px 20px;
			font-weight: 600;
			font-size: .88rem;
			align-items: center;
			gap: 10px;
			animation: slide-in .3s ease;
		}

		#violation-banner.show {
			display: flex;
		}

		@keyframes slide-in {
			from {
				transform: translateY(-100%);
			}

			to {
				transform: translateY(0);
			}
		}

		/* HEADER */
		#exam-header {
			height: var(--header-h);
			flex-shrink: 0;
			background: linear-gradient(90deg, var(--red-900) 0%, var(--red-700) 100%);
			display: flex;
			align-items: center;
			padding: 0 20px;
			gap: 16px;
			box-shadow: 0 2px 12px rgba(0, 0, 0, .3);
			z-index: 100;
		}

		.hdr-timer {
			display: flex;
			align-items: center;
			gap: 10px;
			background: rgba(0, 0, 0, .25);
			border-radius: 8px;
			padding: 8px 16px;
			min-width: 170px;
			border: 1px solid rgba(255, 255, 255, .15);
		}

		.timer-label {
			font-size: .68rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 1px;
			color: rgba(255, 255, 255, .6);
		}

		#countdown {
			font-family: 'JetBrains Mono', monospace;
			font-size: 1.5rem;
			font-weight: 600;
			color: #86efac;
			transition: color .4s;
		}

		#countdown.amber {
			color: #fcd34d;
		}

		#countdown.red {
			color: #fca5a5;
			animation: blink 1s step-end infinite;
		}

		@keyframes blink {

			0%,
			100% {
				opacity: 1
			}

			50% {
				opacity: .4
			}
		}

		.hdr-center {
			flex: 1;
			text-align: center;
			overflow: hidden;
		}

		.hdr-exam-name {
			font-family: 'Bebas Neue', sans-serif;
			font-size: 1.35rem;
			letter-spacing: 2px;
			color: var(--white);
			line-height: 1.1;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.hdr-course {
			font-size: .72rem;
			color: rgba(255, 255, 255, .65);
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.hdr-student {
			display: flex;
			align-items: center;
			gap: 10px;
			min-width: 190px;
			justify-content: flex-end;
		}

		.avatar-initials {
			width: 38px;
			height: 38px;
			border-radius: 50%;
			background: rgba(255, 255, 255, .18);
			border: 2px solid rgba(255, 255, 255, .35);
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 700;
			font-size: .85rem;
			color: var(--white);
			flex-shrink: 0;
		}

		.stu-name {
			font-size: .85rem;
			font-weight: 600;
			color: var(--white);
			white-space: nowrap;
		}

		.stu-roll {
			font-size: .7rem;
			color: rgba(255, 255, 255, .6);
			font-family: 'JetBrains Mono', monospace;
		}

		/* PROGRESS BAR */
		#progress-track {
			height: var(--progress-h);
			background: rgba(185, 28, 28, .2);
			flex-shrink: 0;
			overflow: hidden;
		}

		#progress-fill {
			height: 100%;
			background: linear-gradient(90deg, #fca5a5, var(--red-500));
			width: 0%;
			transition: width .45s cubic-bezier(.4, 0, .2, 1);
		}

		/* BODY */
		#exam-body {
			flex: 1;
			display: flex;
			overflow: hidden;
		}

		/* QUESTION STAGE */
		#question-stage {
			flex: 1;
			display: flex;
			flex-direction: column;
			overflow: hidden;
			background: var(--gray-50);
		}

		/* BREADCRUMB */
		#q-breadcrumb {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 14px 28px 8px;
			flex-shrink: 0;
		}

		.bc-position {
			font-family: 'Bebas Neue', sans-serif;
			font-size: 1.05rem;
			letter-spacing: 2px;
			color: var(--red-700);
		}

		.bc-dots {
			display: flex;
			gap: 5px;
			align-items: center;
		}

		.bc-dot {
			height: 6px;
			width: 6px;
			border-radius: 3px;
			background: var(--gray-200);
			transition: all .35s;
		}

		.bc-dot.done {
			background: var(--clr-answered);
		}

		.bc-dot.current {
			background: var(--red-600);
			width: 18px;
		}

		.bc-dot.marked {
			background: var(--clr-marked);
		}

		.bc-dot.visited {
			background: var(--clr-visited);
		}

		/* VIEWPORT */
		#q-viewport {
			flex: 1;
			overflow: hidden;
			position: relative;
			padding: 0 28px 4px;
		}

		/* SLIDE */
		.q-slide {
			position: absolute;
			inset: 0 28px;
			overflow-y: auto;
			transition: transform .32s cubic-bezier(.4, 0, .2, 1), opacity .32s ease;
		}

		.q-slide::-webkit-scrollbar {
			width: 5px;
		}

		.q-slide::-webkit-scrollbar-thumb {
			background: var(--gray-300);
			border-radius: 4px;
		}

		.q-slide.exit-left {
			transform: translateX(-64px);
			opacity: 0;
			pointer-events: none;
		}

		.q-slide.exit-right {
			transform: translateX(64px);
			opacity: 0;
			pointer-events: none;
		}

		.q-slide.enter-left {
			transform: translateX(64px);
			opacity: 0;
		}

		.q-slide.enter-right {
			transform: translateX(-64px);
			opacity: 0;
		}

		/* QUESTION CARD */
		.q-card {
			background: var(--white);
			border: 1.5px solid var(--gray-200);
			border-radius: 14px;
			padding: 28px 32px;
			box-shadow: 0 2px 14px rgba(0, 0, 0, .07);
		}

		.q-meta {
			display: flex;
			align-items: center;
			gap: 10px;
			margin-bottom: 16px;
		}

		.q-num {
			font-family: 'Bebas Neue', sans-serif;
			font-size: 1.15rem;
			letter-spacing: 1px;
			color: var(--red-700);
		}

		.q-type-badge {
			font-size: .68rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 1px;
			padding: 3px 10px;
			border-radius: 20px;
			background: var(--red-100);
			color: var(--red-800);
		}

		.q-marks {
			margin-left: auto;
			font-size: .78rem;
			font-weight: 600;
			color: var(--gray-500);
			background: var(--gray-100);
			padding: 3px 10px;
			border-radius: 20px;
		}

		.q-text {
			font-size: 1rem;
			line-height: 1.75;
			color: var(--gray-900);
			font-weight: 500;
			margin-bottom: 20px;
		}

		/* MCQ */
		.mcq-options {
			display: flex;
			flex-direction: column;
			gap: 10px;
		}

		.mcq-opt {
			display: flex;
			align-items: flex-start;
			gap: 12px;
			border: 1.5px solid var(--gray-200);
			border-radius: 9px;
			padding: 13px 16px;
			cursor: pointer;
			transition: all .2s;
			user-select: none;
		}

		.mcq-opt:hover {
			border-color: var(--red-400);
			background: var(--red-50);
		}

		.mcq-opt.selected {
			border-color: var(--red-600);
			background: var(--red-50);
			box-shadow: 0 0 0 3px rgba(220, 38, 38, .1);
		}

		.mcq-opt input[type="radio"] {
			display: none;
		}

		.opt-letter {
			width: 30px;
			height: 30px;
			border-radius: 50%;
			border: 2px solid var(--gray-300);
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 700;
			font-size: .8rem;
			color: var(--gray-600);
			flex-shrink: 0;
			transition: all .2s;
		}

		.mcq-opt.selected .opt-letter {
			background: var(--red-600);
			border-color: var(--red-600);
			color: var(--white);
		}

		.opt-text {
			font-size: .93rem;
			color: var(--gray-800);
			line-height: 1.5;
			padding-top: 4px;
		}

		/* FILL */
		.fill-wrap {
			display: flex;
			align-items: center;
			gap: 12px;
			flex-wrap: wrap;
			background: var(--gray-50);
			border-radius: 8px;
			padding: 16px 20px;
			border: 1.5px solid var(--gray-200);
			font-size: .95rem;
			color: var(--gray-700);
		}

		.fill-input {
			border: none;
			border-bottom: 2px solid var(--red-400);
			background: transparent;
			font-size: 1rem;
			color: var(--gray-900);
			padding: 4px 8px;
			outline: none;
			min-width: 180px;
			font-family: 'JetBrains Mono', monospace;
			transition: border-color .2s;
			flex: 1;
		}

		.fill-input:focus {
			border-color: var(--red-700);
		}

		/* OPEN */
		.open-textarea {
			width: 100%;
			resize: vertical;
			min-height: 160px;
			border: 1.5px solid var(--gray-200);
			border-radius: 8px;
			padding: 14px 16px;
			font-size: .93rem;
			color: var(--gray-900);
			font-family: 'DM Sans', sans-serif;
			line-height: 1.6;
			transition: border-color .2s;
			outline: none;
		}

		.open-textarea:focus {
			border-color: var(--red-500);
		}

		.word-counter {
			font-size: .75rem;
			color: var(--gray-400);
			text-align: right;
			margin-top: 6px;
			font-family: 'JetBrains Mono', monospace;
		}

		.word-counter.near-limit {
			color: var(--clr-marked);
		}

		.word-counter.over-limit {
			color: var(--red-600);
			font-weight: 700;
		}

		/* MATCH */
		.match-table {
			width: 100%;
			border-collapse: separate;
			border-spacing: 0;
		}

		.match-table th {
			background: var(--red-700);
			color: var(--white);
			font-size: .78rem;
			text-transform: uppercase;
			letter-spacing: 1px;
			padding: 10px 16px;
			font-weight: 600;
		}

		.match-table th:first-child {
			border-radius: 8px 0 0 0;
		}

		.match-table th:last-child {
			border-radius: 0 8px 0 0;
		}

		.match-table td {
			border-bottom: 1px solid var(--gray-100);
			padding: 10px 16px;
			font-size: .9rem;
			vertical-align: middle;
		}

		.match-table tr:last-child td {
			border-bottom: none;
		}

		/* MATCH — Bootstrap dropdown skin */
		.match-dropdown {
			width: 100%;
		}

		.match-dd-trigger {
			width: 100%;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 8px;
			border: 1.5px solid var(--gray-200);
			border-radius: 6px;
			padding: 8px 12px;
			font-size: .88rem;
			color: var(--gray-800);
			background: var(--white);
			text-align: left;
		}

		.match-dd-trigger:hover,
		.match-dd-trigger:focus {
			border-color: var(--red-500);
			box-shadow: none;
			color: var(--gray-800);
		}

		.match-dd-trigger.show,
		.match-dropdown.show .match-dd-trigger {
			border-color: var(--red-500);
		}

		.match-dd-selected {
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
			flex: 1;
			text-align: left;
		}

		.match-dd-menu {
			width: 100%;
			max-height: 220px;
			overflow-y: auto;
			padding: 4px;
			border: 1.5px solid var(--gray-200);
			border-radius: 8px;
			box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
		}

		.match-opt {
			padding: 9px 12px !important;
			border-radius: 6px;
			font-size: .88rem;
			color: var(--gray-800);
			display: block;
		}

		.match-opt:hover,
		.match-opt:focus {
			background: var(--red-50);
			color: var(--gray-900);
		}

		.match-opt.selected {
			background: var(--red-100) !important;
			font-weight: 600;
			color: var(--red-800);
		}

		/* ATTACHED IMAGE (optional, on mcq / open / fill) */
		.attached-img {
			max-width: 100%;
			border-radius: 10px;
			border: 1.5px solid var(--gray-200);
			margin-bottom: 16px;
			display: block;
		}

		.attached-note {
			font-size: .8rem;
			color: var(--gray-500);
			background: var(--gray-50);
			border-left: 3px solid var(--red-400);
			padding: 8px 14px;
			border-radius: 0 6px 6px 0;
			margin-bottom: 14px;
		}

		/* BOTTOM NAV */
		#q-nav-bar {
			display: flex;
			align-items: center;
			gap: 10px;
			padding: 13px 28px;
			background: var(--white);
			border-top: 1px solid var(--gray-200);
			flex-shrink: 0;
			box-shadow: 0 -2px 10px rgba(0, 0, 0, .06);
		}

		.nav-btn-prev,
		.nav-btn-next {
			padding: 10px 22px;
			border-radius: 9px;
			font-weight: 600;
			font-size: .88rem;
			border: none;
			cursor: pointer;
			transition: all .18s;
			display: flex;
			align-items: center;
			gap: 7px;
		}

		.nav-btn-prev {
			background: var(--gray-100);
			color: var(--gray-700);
		}

		.nav-btn-prev:hover {
			background: var(--gray-200);
		}

		.nav-btn-prev:disabled {
			opacity: .4;
			cursor: not-allowed;
		}

		.nav-btn-next {
			background: var(--red-600);
			color: var(--white);
		}

		.nav-btn-next:hover {
			background: var(--red-700);
		}

		.btn-mark-review {
			padding: 10px 16px;
			border-radius: 9px;
			font-weight: 600;
			font-size: .85rem;
			border: 1.5px solid var(--clr-marked);
			color: var(--clr-marked);
			background: transparent;
			cursor: pointer;
			transition: all .18s;
		}

		.btn-mark-review:hover,
		.btn-mark-review.active {
			background: var(--clr-marked);
			color: var(--white);
		}

		.btn-clear-ans {
			padding: 10px 14px;
			border-radius: 9px;
			font-weight: 600;
			font-size: .82rem;
			border: 1.5px solid var(--gray-200);
			color: var(--gray-500);
			background: transparent;
			cursor: pointer;
			transition: all .18s;
		}

		.btn-clear-ans:hover {
			border-color: var(--red-400);
			color: var(--red-500);
		}

		.nav-spacer {
			flex: 1;
		}

		/* SIDEBAR */
		#exam-sidebar {
			width: var(--sidebar-w);
			flex-shrink: 0;
			background: var(--white);
			border-left: 1px solid var(--gray-200);
			display: flex;
			flex-direction: column;
			overflow: hidden;
		}

		.sidebar-head {
			background: linear-gradient(135deg, var(--red-900), var(--red-700));
			color: var(--white);
			padding: 16px 18px;
			font-family: 'Bebas Neue', sans-serif;
			font-size: 1rem;
			letter-spacing: 2px;
		}

		.legend-section {
			padding: 14px 18px;
			border-bottom: 1px solid var(--gray-100);
		}

		.legend-item {
			display: flex;
			align-items: center;
			gap: 8px;
			font-size: .78rem;
			color: var(--gray-600);
			margin-bottom: 6px;
		}

		.legend-dot {
			width: 14px;
			height: 14px;
			border-radius: 4px;
			flex-shrink: 0;
		}

		.legend-count {
			margin-left: auto;
			font-weight: 700;
			font-family: 'JetBrains Mono', monospace;
			font-size: .82rem;
		}

		.palette-section {
			flex: 1;
			overflow-y: auto;
			padding: 14px 18px;
		}

		.palette-section::-webkit-scrollbar {
			width: 4px;
		}

		.palette-section::-webkit-scrollbar-thumb {
			background: var(--gray-200);
		}

		.palette-label {
			font-size: .72rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 1px;
			color: var(--gray-400);
			margin-bottom: 10px;
		}

		#palette-grid {
			display: grid;
			grid-template-columns: repeat(5, 1fr);
			gap: 6px;
		}

		.palette-btn {
			width: 100%;
			aspect-ratio: 1;
			border-radius: 6px;
			border: none;
			cursor: pointer;
			font-weight: 700;
			font-size: .78rem;
			background: var(--clr-not);
			color: var(--white);
			transition: transform .15s, opacity .15s;
			font-family: 'JetBrains Mono', monospace;
		}

		.palette-btn:hover {
			transform: scale(1.12);
			opacity: .85;
		}

		.palette-btn.answered {
			background: var(--clr-answered);
		}

		.palette-btn.visited {
			background: var(--clr-visited);
		}

		.palette-btn.marked {
			background: var(--clr-marked);
		}

		.palette-btn.current {
			outline: 3px solid var(--gray-900);
			outline-offset: 2px;
		}

		.stats-row {
			padding: 12px 18px;
			border-top: 1px solid var(--gray-100);
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 8px;
		}

		.stat-chip {
			background: var(--gray-50);
			border: 1px solid var(--gray-100);
			border-radius: 8px;
			padding: 8px 10px;
			text-align: center;
		}

		.stat-chip .sc-val {
			font-family: 'Bebas Neue', sans-serif;
			font-size: 1.4rem;
			color: var(--red-700);
			line-height: 1;
		}

		.stat-chip .sc-lbl {
			font-size: .65rem;
			text-transform: uppercase;
			letter-spacing: .5px;
			color: var(--gray-400);
			font-weight: 600;
		}

		.sidebar-submit {
			padding: 14px 18px;
		}

		.btn-submit-exam {
			width: 100%;
			padding: 14px;
			background: linear-gradient(135deg, var(--red-700), var(--red-900));
			color: var(--white);
			border: none;
			border-radius: 10px;
			font-size: 1rem;
			font-weight: 700;
			cursor: pointer;
			letter-spacing: 1px;
			font-family: 'Bebas Neue', sans-serif;
			transition: all .2s;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			box-shadow: 0 4px 16px rgba(185, 28, 28, .35);
		}

		.btn-submit-exam:hover {
			background: linear-gradient(135deg, var(--red-600), var(--red-800));
			box-shadow: 0 6px 24px rgba(185, 28, 28, .5);
			transform: translateY(-1px);
		}

		@media(max-width:860px) {
			:root {
				--sidebar-w: 220px;
			}

			.hdr-student .stu-name {
				display: none;
			}

			#palette-grid {
				grid-template-columns: repeat(4, 1fr);
			}

			#q-viewport {
				padding: 0 16px 4px;
			}

			#q-breadcrumb {
				padding: 12px 16px 6px;
			}

			#q-nav-bar {
				padding: 10px 16px;
			}
		}

		@media(max-width:640px) {
			:root {
				--sidebar-w: 0px;
			}

			#exam-sidebar {
				display: none;
			}

			.q-card {
				padding: 20px 16px;
			}
		}
	</style>
</head>

<body>

	<div id="fs-gate">
		<div class="gate-logo"><i class="bi bi-mortarboard-fill me-2"></i>ExamBuilder</div>
		<div class="gate-card">
			<div class="gate-icon"><i class="bi bi-shield-lock-fill"></i></div>
			<h2>Secure Exam Environment</h2>
			<p>This exam runs in a proctored full-screen environment.<br>
				Tab switching, right-clicking, and keyboard shortcuts are disabled.<br>
				<strong>3 tab-switch violations</strong> will auto-submit your exam.
			</p>
			<div class="mt-4 text-start" style="font-size:.82rem;opacity:.65;margin-bottom:24px;">
				<div class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>
						<?= htmlspecialchars($exam['Exam_Name']) ?>
					</strong></div>
				<div class="mb-1"><i class="bi bi-clock me-2"></i>
					<?= $exam['Duration'] ?> minutes &nbsp;|&nbsp;
					<?= $totalMarks ?> marks
				</div>
				<div><i class="bi bi-person me-2"></i>
					<?= htmlspecialchars($student['name']) ?> &nbsp;(
					<?= htmlspecialchars($student['roll']) ?>)
				</div>
			</div>
			<button class="btn-enter" id="btn-enter-fs"><i class="bi bi-fullscreen me-2"></i>ENTER FULLSCREEN &
				BEGIN</button>
		</div>
	</div>

	<div id="fs-warning">
		<div style="font-size:3.5rem;"><i class="bi bi-exclamation-triangle-fill"></i></div>
		<h2>Return to Fullscreen</h2>
		<p>You have exited fullscreen mode. The exam is paused until you return.</p>
		<button class="btn-enter" onclick="reEnterFullscreen()"><i class="bi bi-fullscreen me-2"></i>Return to
			Fullscreen</button>
	</div>

	<div id="exam-shell">
		<div id="violation-banner"><i class="bi bi-exclamation-triangle-fill"></i><span id="violation-text"></span>
		</div>

		<header id="exam-header">
			<div class="hdr-timer">
				<div>
					<div class="timer-label">Time Left</div>
					<div id="countdown">90:00</div>
				</div>
				<div style="margin-left:8px;"><i class="bi bi-hourglass-split"
						style="color:rgba(255,255,255,.5);font-size:1.2rem;"></i></div>
			</div>
			<div class="hdr-center">
				<div class="hdr-exam-name">
					<?= htmlspecialchars($exam['Exam_Name']) ?>
				</div>
				<div class="hdr-course">
					<?= htmlspecialchars($exam['Course']) ?>
				</div>
			</div>
			<div class="hdr-student">
				<div>
					<div class="stu-name">
						<?= htmlspecialchars($student['name']) ?>
					</div>
					<div class="stu-roll">
						<?= htmlspecialchars($student['roll']) ?>
					</div>
				</div>
				<div class="avatar-initials">
					<?= htmlspecialchars($student['avatar']) ?>
				</div>
			</div>
		</header>

		<div id="progress-track">
			<div id="progress-fill"></div>
		</div>

		<div id="exam-body">
			<div id="question-stage">
				<div id="q-breadcrumb">
					<div class="bc-position" id="bc-pos">Question 1 of
						<?= count($questions) ?>
					</div>
					<div class="bc-dots" id="bc-dots"></div>
				</div>
				<div id="q-viewport"></div>
				<div id="q-nav-bar">
					<button class="nav-btn-prev" id="btn-prev" onclick="navigate(-1)" disabled><i
							class="bi bi-chevron-left"></i>
						Previous</button>
					<button class="btn-mark-review" id="btn-mark" onclick="toggleMark()"><i
							class="bi bi-flag me-1"></i>Mark for
						Review</button>
					<button class="btn-clear-ans" onclick="clearCurrentAnswer()"><i
							class="bi bi-x-circle me-1"></i>Clear</button>
					<div class="nav-spacer"></div>
					<button class="nav-btn-next" id="btn-next" onclick="navigate(1)">Next <i
							class="bi bi-chevron-right"></i></button>
				</div>
			</div>

			<aside id="exam-sidebar">
				<div class="sidebar-head"><i class="bi bi-grid-3x3-gap me-2"></i>Question Palette</div>
				<div class="legend-section">
					<div class="legend-item">
						<div class="legend-dot" style="background:var(--clr-answered)"></div>Answered<span
							class="legend-count" id="cnt-answered" style="color:var(--clr-answered)">0</span>
					</div>
					<div class="legend-item">
						<div class="legend-dot" style="background:var(--clr-visited)"></div>Visited, Unanswered<span
							class="legend-count" id="cnt-visited" style="color:var(--clr-visited)">0</span>
					</div>
					<div class="legend-item">
						<div class="legend-dot" style="background:var(--clr-marked)"></div>Marked for Review<span
							class="legend-count" id="cnt-marked" style="color:var(--clr-marked)">0</span>
					</div>
					<div class="legend-item">
						<div class="legend-dot" style="background:var(--clr-not)"></div>Not Visited<span
							class="legend-count" id="cnt-not">0</span>
					</div>
				</div>
				<div class="palette-section">
					<div class="palette-label">Jump to Question</div>
					<div id="palette-grid"></div>
				</div>
				<div class="stats-row">
					<div class="stat-chip">
						<div class="sc-val">
							<?= count($questions) ?>
						</div>
						<div class="sc-lbl">Total Qs</div>
					</div>
					<div class="stat-chip">
						<div class="sc-val">
							<?= $totalMarks ?>
						</div>
						<div class="sc-lbl">Total Marks</div>
					</div>
					<div class="stat-chip">
						<div class="sc-val" id="stat-attempted">0</div>
						<div class="sc-lbl">Attempted</div>
					</div>
					<div class="stat-chip">
						<div class="sc-val" id="stat-remain">
							<?= count($questions) ?>
						</div>
						<div class="sc-lbl">Remaining</div>
					</div>
				</div>
				<div class="sidebar-submit"><button class="btn-submit-exam" id="btn-submit"><i
							class="bi bi-send-check-fill"></i> SUBMIT EXAM</button></div>
			</aside>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script>
		const QUESTIONS = <?= $questionsJson ?>;
		const EXAM = <?= $examJson ?>;
		const STUDENT = <?= $studentJson ?>;
		const DURATION_S = <?= $durationSec ?>;
		const TOTAL = QUESTIONS.length;
		const TYPE_LABELS = {
			mcq: 'Multiple Choice',
			fill: 'Fill in the Blanks',
			open: 'Open Ended',
			match: 'Match the Following'
		};

		const state = {
			currentIdx: 0,
			answers: {},
			status: {},
			tabViolations: 0,
			examActive: false,
			timerLeft: DURATION_S,
			timerInterval: null
		};
		QUESTIONS.forEach(q => state.status[q.Question_ID] = 'not');

		/* ── FULLSCREEN ─────────────────────────────────── */
		document.getElementById('btn-enter-fs').addEventListener('click', () => {
			const el = document.documentElement;
			(el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen).call(el);
		});
		document.addEventListener('fullscreenchange', onFsChange);
		document.addEventListener('webkitfullscreenchange', onFsChange);

		function onFsChange() {
			const inFs = !!document.fullscreenElement || !!document.webkitFullscreenElement;
			if (!state.examActive && inFs) startExam();
			else if (state.examActive && !inFs) document.getElementById('fs-warning').classList.add('show');
		}

		function reEnterFullscreen() {
			document.getElementById('fs-warning').classList.remove('show');
			const el = document.documentElement;
			(el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen).call(el);
		}

		function startExam() {
			state.examActive = true;
			document.getElementById('fs-gate').style.display = 'none';
			document.getElementById('exam-shell').classList.add('visible');
			buildPalette();
			buildDots();
			goTo(0, 'none');
			startTimer();
			attachProctoring();
		}

		/* ── TIMER ──────────────────────────────────────── */
		function startTimer() {
			renderClock();
			state.timerInterval = setInterval(() => {
				state.timerLeft--;
				renderClock();
				if (state.timerLeft <= 0) {
					clearInterval(state.timerInterval);
					autoSubmit('Time is up!');
				}
			}, 1000);
		}

		function renderClock() {
			const el = document.getElementById('countdown');
			const m = Math.floor(state.timerLeft / 60),
				s = state.timerLeft % 60;
			el.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
			el.className = state.timerLeft <= 60 ? 'red' : state.timerLeft <= 300 ? 'amber' : '';
		}

		/* ── PROCTORING ─────────────────────────────────── */
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
					title: `⚠️ Tab Switch (${state.tabViolations}/3)`,
					html: `<p>Switching tabs is <strong>not allowed</strong>.</p><p class="mt-2 text-danger"><strong>${rem} more</strong> will auto-submit your exam.</p>`,
					confirmButtonText: 'Return to Exam',
					confirmButtonColor: '#dc2626',
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
			const b = document.getElementById('violation-banner');
			document.getElementById('violation-text').textContent = msg;
			b.classList.add('show');
			setTimeout(() => b.classList.remove('show'), 6000);
		}

		/* ── CORE: goTo ─────────────────────────────────── */
		function goTo(newIdx, direction) {
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
			const oldSlide = viewport.querySelector('.q-slide');

			// Animate out
			if (oldSlide && direction !== 'none') {
				oldSlide.classList.add(direction === 'right' ? 'exit-left' : 'exit-right');
				setTimeout(() => oldSlide.remove(), 340);
			} else if (oldSlide) {
				oldSlide.remove();
			}

			// Build new slide
			const slide = document.createElement('div');
			slide.className = 'q-slide';
			if (direction !== 'none') slide.classList.add(direction === 'right' ? 'enter-left' : 'enter-right');
			slide.innerHTML = buildHTML(q, newIdx);
			viewport.appendChild(slide);

			requestAnimationFrame(() => {
				requestAnimationFrame(() => {
					slide.classList.remove('enter-left', 'enter-right');
				});
				restoreAnswer(q);
				attachListeners(q);
				renderMath(slide);
			});

			updateNavBtns();
			updateBreadcrumb();
			updatePalette();
			updateProgress();
		}

		/* ── BUILD HTML ─────────────────────────────────── */
		function buildAttachedImage(q) {
			if (!q.Image_Path) return '';
			return `<div class="attached-note"><i class="bi bi-info-circle me-1"></i>Refer to the image below before answering.</div>
      <img src="${esc(q.Image_Path)}" class="attached-img" alt="Question attachment" loading="lazy">`;
		}

		function buildHTML(q, idx) {
			let body = '';
			if (q.Question_Type === 'mcq') {
				const opts = JSON.parse(q.Options_JSON || '[]');
				body = `${buildAttachedImage(q)}<div class="mcq-options">${opts.map(o => `
      <label class="mcq-opt" data-opt="${o.letter}">
        <input type="radio" name="r_${q.Question_ID}" value="${o.letter}">
        <div class="opt-letter">${o.letter}</div>
		<div class="opt-text math-content">${formatMath(o.text)}</div>

      </label>`).join('')}</div>`;
			} else if (q.Question_Type === 'fill') {
				body = ` ${buildAttachedImage(q)} <div class="fill-wrap"> <span class="math-content">${formatMath(q.Question_Text)}</span> </div> <div class="fill-wrap mt-3"> Answer:&nbsp; <input type="text" id="fill-inp" class="fill-input" placeholder="Type your answer…" autocomplete="off" spellcheck="false"> </div>`;
			} else if (q.Question_Type === 'open') {
				const wl = q.Word_Limit || 300; body = ` ${buildAttachedImage(q)} <div class="math-content mb-3"> ${formatMath(q.Question_Text)} </div> <textarea class="open-textarea" id="open-ta" data-wl="${wl}" placeholder="Write your answer here…" rows="7"></textarea> <div class="word-counter" id="open-wc"> 0 / ${wl} words </div>`;
			} else if (q.Question_Type === 'match') {
				const pairs = JSON.parse(q.Pairs_JSON || '[]');
				const shuffled = [...pairs.map(p => p.b)].sort(() => Math.random() - .5);

				const optionsHTML = shuffled.map(r =>
					`<li><a class="dropdown-item match-opt" href="#" data-val="${esc(r)}">
            <span class="math-content">${formatMath(r)}</span>
          </a></li>`
				).join('');

				body = `<table class="match-table"><thead><tr><th>Column A</th><th>Column B (Match)</th></tr></thead><tbody>${pairs.map((p, i) => `<tr><td> <strong class="math-content"> ${formatMath(p.a)} </strong> </td><td>
            <div class="dropdown match-dropdown" data-idx="${i}">
              <button class="btn match-dd-trigger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="match-dd-selected math-content">— Select —</span>
              </button>
              <ul class="dropdown-menu match-dd-menu">${optionsHTML}</ul>
            </div>
          </td></tr>`).join('')
					}</tbody></table>`;
			}
			return `<div class="q-card">
    <div class="q-meta">
      <div class="q-num">Q${idx + 1}</div>
      <div class="q-type-badge">${TYPE_LABELS[q.Question_Type] || q.Question_Type}</div>
      <div class="q-marks"><i class="bi bi-star-fill me-1" style="color:var(--clr-marked)"></i>${q.Marks} mark${q.Marks != 1 ? 's' : ''}</div>
    </div>
	<div class="q-text math-content">${formatMath(q.Question_Text)}</div>
    ${body}
  </div>`;
		}

		/* ── RESTORE ANSWER ─────────────────────────────── */
		function restoreAnswer(q) {
			const saved = state.answers[q.Question_ID];
			if (saved === undefined || saved === null) return;
			if (q.Question_Type === 'mcq') {
				document.querySelectorAll('.mcq-opt').forEach(l => {
					if (l.dataset.opt === saved) {
						l.classList.add('selected');
						l.querySelector('input').checked = true;
					}
				});
			} else if (q.Question_Type === 'fill') {
				const inp = document.getElementById('fill-inp');
				if (inp) inp.value = saved;
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
						opt.classList.add('selected');
						dd.querySelector('.match-dd-selected').innerHTML = opt.querySelector('.math-content').innerHTML;
					}
				});
			}
		}

		/* ── ATTACH LISTENERS ───────────────────────────── */
		function attachListeners(q) {
			const qid = q.Question_ID;
			if (q.Question_Type === 'mcq') {
				document.querySelectorAll('.mcq-opt').forEach(lbl => lbl.addEventListener('click', () => {
					document.querySelectorAll('.mcq-opt').forEach(l => l.classList.remove('selected'));
					lbl.classList.add('selected');
					lbl.querySelector('input').checked = true;
					state.answers[qid] = lbl.dataset.opt;
					setStatus(qid, 'answered');
				}));
			} else if (q.Question_Type === 'fill') {
				const inp = document.getElementById('fill-inp');
				if (inp) inp.addEventListener('input', () => {
					state.answers[qid] = inp.value.trim();
					setStatus(qid, inp.value.trim() ? 'answered' : 'visited');
				});
			} else if (q.Question_Type === 'open') {
				const ta = document.getElementById('open-ta');
				if (ta) ta.addEventListener('input', () => {
					updateWC(ta);
					state.answers[qid] = ta.value;
					setStatus(qid, ta.value.trim() ? 'answered' : 'visited');
				});
			} else if (q.Question_Type === 'match') {
				document.querySelectorAll('.match-dropdown').forEach(dd => {
					const idx = dd.dataset.idx;
					const trigger = dd.querySelector('.match-dd-trigger');
					const selectedLabel = dd.querySelector('.match-dd-selected');

					dd.querySelectorAll('.match-opt').forEach(opt => {
						opt.addEventListener('click', (e) => {
							e.preventDefault();
							const val = opt.dataset.val;

							dd.querySelectorAll('.match-opt').forEach(o => o.classList.remove('selected'));
							opt.classList.add('selected');
							selectedLabel.innerHTML = opt.querySelector('.math-content').innerHTML;

							if (!state.answers[qid] || typeof state.answers[qid] !== 'object') state.answers[qid] = {};
							state.answers[qid][idx] = val;

							const allDD = document.querySelectorAll('.match-dropdown');
							const allFilled = [...allDD].every(d => d.querySelector('.match-opt.selected'));
							setStatus(qid, allFilled ? 'answered' : 'visited');

							renderMath(trigger);
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
			wc.className = 'word-counter' + (words > wl ? ' over-limit' : words > wl * .85 ? ' near-limit' : '');
		}

		/* ── STATUS ─────────────────────────────────────── */
		function setStatus(qid, s) {
			if (state.status[qid] === 'marked' && (s === 'visited' || s === 'not')) return;
			state.status[qid] = s;
			updatePalette();
		}

		/* ── NAVIGATE ───────────────────────────────────── */
		function navigate(dir) {
			const n = state.currentIdx + dir;
			if (n < 0 || n >= TOTAL) return;
			goTo(n, dir > 0 ? 'right' : 'left');
		}

		function toggleMark() {
			const q = QUESTIONS[state.currentIdx],
				qid = q.Question_ID;
			const wasMarked = state.status[qid] === 'marked';
			state.status[qid] = wasMarked ? (state.answers[qid] ? 'answered' : 'visited') : 'marked';
			updateMarkBtn();
			updatePalette();
		}

		function clearCurrentAnswer() {
			const q = QUESTIONS[state.currentIdx],
				qid = q.Question_ID;
			state.answers[qid] = null;
			if (q.Question_Type === 'mcq') {
				document.querySelectorAll('.mcq-opt').forEach(l => l.classList.remove('selected'));
				document.querySelectorAll(`input[name="r_${qid}"]`).forEach(i => i.checked = false);
			} else if (q.Question_Type === 'fill') {
				const i = document.getElementById('fill-inp');
				if (i) i.value = '';
			} else if (q.Question_Type === 'match') {
				document.querySelectorAll('.match-dropdown').forEach(dd => {
					dd.querySelectorAll('.match-opt').forEach(o => o.classList.remove('selected'));
					dd.querySelector('.match-dd-selected').textContent = '— Select —';
				});
			} else {
				const ta = document.getElementById('open-ta');
				if (ta) {
					ta.value = '';
					updateWC(ta);
				}
			}
			if (state.status[qid] !== 'marked') setStatus(qid, 'visited');
		}

		/* ── UI CHROME ──────────────────────────────────── */
		function updateNavBtns() {
			document.getElementById('btn-prev').disabled = state.currentIdx === 0;
			const nb = document.getElementById('btn-next');
			if (state.currentIdx === TOTAL - 1) {
				nb.innerHTML = '<i class="bi bi-send-check-fill me-1"></i>Submit';
				nb.onclick = confirmSubmit;
				nb.style.background = 'linear-gradient(135deg,var(--red-700),var(--red-900))';
			} else {
				nb.innerHTML = 'Next <i class="bi bi-chevron-right"></i>';
				nb.onclick = () => navigate(1);
				nb.style.background = '';
			}
			updateMarkBtn();
		}

		function updateMarkBtn() {
			const q = QUESTIONS[state.currentIdx],
				isM = state.status[q.Question_ID] === 'marked';
			const b = document.getElementById('btn-mark');
			b.className = 'btn-mark-review' + (isM ? ' active' : '');
			b.innerHTML = isM ? '<i class="bi bi-flag-fill me-1"></i>Marked' : '<i class="bi bi-flag me-1"></i>Mark for Review';
		}

		function updateBreadcrumb() {
			document.getElementById('bc-pos').textContent = `Question ${state.currentIdx + 1} of ${TOTAL}`;
			updateDots();
		}

		function buildDots() {
			const wrap = document.getElementById('bc-dots');
			wrap.innerHTML = '';
			QUESTIONS.forEach((_, i) => {
				const d = document.createElement('div');
				d.id = `dot-${i}`;
				d.className = 'bc-dot';
				wrap.appendChild(d);
			});
			updateDots();
		}

		function updateDots() {
			QUESTIONS.forEach((q, i) => {
				const d = document.getElementById(`dot-${i}`);
				if (!d) return;
				const s = state.status[q.Question_ID];
				d.className = 'bc-dot' + (i === state.currentIdx ? ' current' : s === 'answered' ? ' done' : s === 'marked' ? ' marked' : s === 'visited' ? ' visited' : '');
				d.style.width = i === state.currentIdx ? '18px' : '6px';
			});
		}

		function updateProgress() {
			const n = Object.values(state.status).filter(s => s === 'answered').length;
			document.getElementById('progress-fill').style.width = (n / TOTAL * 100) + '%';
		}

		function buildPalette() {
			const g = document.getElementById('palette-grid');
			g.innerHTML = '';
			QUESTIONS.forEach((q, i) => {
				const b = document.createElement('button');
				b.className = 'palette-btn not';
				b.id = `pb-${q.Question_ID}`;
				b.textContent = i + 1;
				b.addEventListener('click', () => goTo(i, i > state.currentIdx ? 'right' : i < state.currentIdx ? 'left' : 'none'));
				g.appendChild(b);
			});
			updatePalette();
		}

		function updatePalette() {
			let cnt = {
				answered: 0,
				visited: 0,
				marked: 0,
				not: 0
			};
			QUESTIONS.forEach((q, i) => {
				const s = state.status[q.Question_ID],
					b = document.getElementById(`pb-${q.Question_ID}`);
				if (!b) return;
				b.className = 'palette-btn ' + s + (i === state.currentIdx ? ' current' : '');
				cnt[s]++;
			});
			['answered', 'visited', 'marked', 'not'].forEach(k => {
				const el = document.getElementById(`cnt-${k}`);
				if (el) el.textContent = cnt[k];
			});
			const att = cnt.answered;
			document.getElementById('stat-attempted').textContent = att;
			document.getElementById('stat-remain').textContent = TOTAL - att;
			updateProgress();
			updateDots();
		}

		/* ── SUBMIT ─────────────────────────────────────── */
		document.getElementById('btn-submit').addEventListener('click', confirmSubmit);

		function confirmSubmit() {
			const ans = Object.values(state.status).filter(s => s === 'answered').length,
				un = TOTAL - ans;
			Swal.fire({
				title: 'Submit Exam?',
				html: `<div class="text-start"><div class="mb-2"><span class="badge bg-success me-2">${ans}</span>Answered</div><div class="mb-2"><span class="badge bg-danger me-2">${un}</span>Unanswered / Skipped</div><hr><p class="text-muted small mt-2">Once submitted, you cannot make any changes.</p></div>`,
				icon: 'question',
				showCancelButton: true,
				confirmButtonText: '<i class="bi bi-send-check-fill me-1"></i> Yes, Submit Now',
				cancelButtonText: 'Review Answers',
				confirmButtonColor: '#dc2626',
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
					confirmButtonColor: '#dc2626',
					allowOutsideClick: false,
					allowEscapeKey: false
				})
				.then(() => submitExam(true));
		}
		async function submitExam(auto = false) {
			clearInterval(state.timerInterval);
			state.examActive = false;
			if (document.exitFullscreen) document.exitFullscreen().catch(() => {});
			const payload = {
				exam_id: EXAM.Exam_ID,
				student_id: STUDENT.roll,
				auto_submit: auto,
				time_taken: DURATION_S - state.timerLeft,
				violations: state.tabViolations,
				answers: buildPayload()
			};
			Swal.fire({
				title: 'Submitting…',
				html: '<div class="spinner-border text-danger" role="status"></div><p class="mt-3">Please wait…</p>',
				allowOutsideClick: false,
				allowEscapeKey: false,
				showConfirmButton: false
			});
			try {
				const res = await fetch('submit-exam.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(payload)
				});
				const data = await res.json();
				if (data.success) {
					Swal.fire({
						icon: 'success',
						title: 'Exam Submitted!',
						html: `<p>Responses saved.</p><p class="mt-2"><strong>Time:</strong> ${fmt(payload.time_taken)}</p><p><strong>Submitted:</strong> ${Object.keys(payload.answers).length}/${TOTAL}</p>`,
						confirmButtonColor: '#dc2626',

						confirmButtonText: 'Done',
						allowOutsideClick: false,
					}).then(() => {
						window.location.href = 'dashboard.php';
					});
				} else throw new Error(data.message || 'Server error');
			} catch (err) {
				Swal.fire({
					icon: 'error',
					title: 'Submission Failed',
					text: 'Could not reach server. Inform invigilator. (' + err.message + ')',
					confirmButtonColor: '#dc2626'
				});
			}
		}

		function buildPayload() {
			const out = {};
			QUESTIONS.forEach(q => {
				const a = state.answers[q.Question_ID];
				if (a !== undefined && a !== null) out[q.Question_ID] = {
					type: q.Question_Type,
					answer: a,
					status: state.status[q.Question_ID]
				};
			});
			return out;
		}

		/* ── HELPERS ────────────────────────────────────── */
		function esc(s) {
			if (!s) return '';
			return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
		}

		function fmt(sec) {
			return `${Math.floor(sec / 60)}m ${sec % 60}s`;
		}

		function formatMath(text) {
			if (!text) return '';

			// Escape HTML first
			text = esc(text);

			// If already contains latex delimiters, keep as-is
			const hasDelimiter =
				text.includes('\\(') ||
				text.includes('\\[') ||
				text.includes('$$');

			// Auto-wrap raw LaTeX environments
			if (!hasDelimiter) {

				// Detect common LaTeX commands/environments
				const latexPattern =
					/\\begin|\\frac|\\sqrt|\\sum|\\int|\\alpha|\\beta|\\gamma|\\pi|\\theta|\\sin|\\cos|\\tan|\\log|\\lim|\\matrix|\\pmatrix|\\bmatrix/;

				if (latexPattern.test(text)) {
					text = `\\[${text}\\]`;
				}
			}

			return `<div class="math-content">${text}</div>`;
		}

		function renderMath(container = document.body) {

			if (typeof renderMathInElement !== 'undefined') {

				renderMathInElement(container, {
					delimiters: [{
							left: "$$",
							right: "$$",
							display: true
						},
						{
							left: "\\(",
							right: "\\)",
							display: false
						},
						{
							left: "\\[",
							right: "\\]",
							display: true
						}
					],

					throwOnError: false,
					strict: false,

					trust: true
				});
			}
		}
	</script>
</body>


</html>