<?php
/**
 * MITS Exam Portal - Interactive MathLive Question Studio
 * Fullscreen authoring workbench with LaTeX virtual math keyboard, live equation preview, & CO tagging
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

check_role('Faculty');

$fac_id = $_SESSION['LoginFaculty'] ?? $_SESSION['user_id'] ?? '';

$flashMessage = null;
$flashType = 'info';

// Fetch assigned courses
$stmtCourses = $pdo->prepare("
    SELECT c.Course_ID, c.Course_Code, c.Course_Name, c.Semester
    FROM course_mapping cm
    JOIN courses c ON cm.Course_ID = c.Course_ID
    WHERE cm.Fac_ID = ?
    ORDER BY c.Course_Name ASC
");
$stmtCourses->execute([$fac_id]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $course_id = (int)($_POST['Course_ID'] ?? 0);
    $q_type = trim($_POST['Question_Type'] ?? 'mcq');
    $q_text = trim($_POST['Question_Text'] ?? '');
    $marks = (float)($_POST['Marks'] ?? 1.0);
    $co = (int)($_POST['CO'] ?? 1);
    $bloom = trim($_POST['Bloom_Level'] ?? 'Understand');
    $rubric = trim($_POST['Rubric'] ?? '');
    $word_limit = !empty($_POST['Word_Limit']) ? (int)$_POST['Word_Limit'] : null;

    $options_json = null;
    $correct_opt = null;
    $answers_json = null;
    $pairs_json = null;

    if ($q_type === 'mcq' || $q_type === 'multiselect') {
        $opts = [];
        $letters = ['A', 'B', 'C', 'D'];
        foreach ($letters as $l) {
            $val = trim($_POST["opt_{$l}"] ?? '');
            if ($val !== '') {
                $opts[] = ['letter' => $l, 'text' => $val];
            }
        }
        $options_json = json_encode($opts, JSON_UNESCAPED_UNICODE);
        if ($q_type === 'mcq') {
            $correct_opt = trim($_POST['correct_mcq'] ?? 'A');
        } else {
            $sel = $_POST['correct_multi'] ?? [];
            $correct_opt = json_encode($sel);
        }
    } elseif ($q_type === 'fill') {
        $accepted = array_filter(array_map('trim', explode(',', $_POST['fill_answers'] ?? '')));
        $answers_json = json_encode(array_values($accepted), JSON_UNESCAPED_UNICODE);
        $correct_opt = implode(', ', $accepted);
    } elseif ($q_type === 'match') {
        $pairs = [];
        $lefts = $_POST['match_left'] ?? [];
        $rights = $_POST['match_right'] ?? [];
        for ($i = 0; $i < count($lefts); $i++) {
            $l = trim($lefts[$i] ?? '');
            $r = trim($rights[$i] ?? '');
            if ($l !== '' && $r !== '') {
                $pairs[] = ['left' => $l, 'right' => $r];
            }
        }
        $pairs_json = json_encode($pairs, JSON_UNESCAPED_UNICODE);
    }

    // Diagram Upload
    $image_path = null;
    if (isset($_FILES['diagram_file']) && $_FILES['diagram_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['diagram_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'])) {
            $uploadDir = __DIR__ . '/../Uploads/questions/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $filename = 'qb_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $image_path = 'Uploads/questions/' . $filename;
            }
        }
    }

    if ($q_text === '' || $course_id <= 0) {
        $flashMessage = 'Please select a course and provide the question statement.';
        $flashType = 'warning';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO new_question_bank 
                (Course_ID, Fac_ID, Question_Type, Question_Text, Marks, CO, Bloom_Level, Options_JSON, Correct_Opt, Rubric, Word_Limit, Answers_JSON, Pairs_JSON, Image_Path)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $course_id, $fac_id, $q_type, $q_text, $marks, $co, $bloom,
                $options_json, $correct_opt, $rubric, $word_limit, $answers_json, $pairs_json, $image_path
            ]);
            $flashMessage = 'Question successfully saved to Question Bank!';
            $flashType = 'success';
        } catch (Exception $e) {
            $flashMessage = 'Save failed: ' . $e->getMessage();
            $flashType = 'danger';
        }
    }
}

include '../Common/header.php';
include '../Common/faculty-sidebar.php';
?>

<!-- MathLive & KaTeX for live mathematical authoring -->
<script defer src="https://unpkg.com/mathlive"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>

<main>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--mits-primary);">MathLive Question Authoring Studio</h4>
            <p class="text-muted mb-0 small">Create complex STEM equations with interactive LaTeX virtual keyboards and instant rendering</p>
        </div>
        <a href="question-bank.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Question Bank
        </a>
    </div>

    <?php if ($flashMessage): ?>
        <div class="alert alert-<?= $flashType ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi <?= $flashType === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
            <?= e($flashMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Authoring Workbench Form -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Question Specification
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="studioForm">
                        <?= csrf_field() ?>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Target Course <span class="text-danger">*</span></label>
                                <select name="Course_ID" id="courseSelect" class="form-select" required>
                                    <option value="">-- Choose Course --</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?= e($c['Course_ID']) ?>">
                                            <?= e($c['Course_Code'] ? $c['Course_Code'] . ' - ' : '') ?><?= e($c['Course_Name']) ?> (S<?= e($c['Semester']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Question Type <span class="text-danger">*</span></label>
                                <select name="Question_Type" id="qTypeSelect" class="form-select" onchange="handleTypeChange()">
                                    <option value="mcq">Single Choice (MCQ)</option>
                                    <option value="multiselect">Multiple Select (Checkboxes)</option>
                                    <option value="fill">Fill in the Blanks</option>
                                    <option value="match">Match the Following</option>
                                    <option value="open">Open-Ended (Subjective / Derivation)</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Course Outcome</label>
                                <select name="CO" id="coSelect" class="form-select">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?= $i ?>">CO<?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Allocated Marks</label>
                                <input type="number" step="0.5" name="Marks" id="marksInput" class="form-control" value="2.0" min="0.5" max="50" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Bloom's Level</label>
                                <select name="Bloom_Level" class="form-select">
                                    <option value="Remember">Remember</option>
                                    <option value="Understand" selected>Understand</option>
                                    <option value="Apply">Apply</option>
                                    <option value="Analyze">Analyze</option>
                                    <option value="Evaluate">Evaluate</option>
                                    <option value="Create">Create</option>
                                </select>
                            </div>
                        </div>

                        <!-- STEM Formula Quick Insertion Chips -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">STEM Equation Quick Inserts</label>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="insertLatex('\\frac{a}{b}')">$$\frac{a}{b}$$</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="insertLatex('\\sqrt{x}')">$$\sqrt{x}$$</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="insertLatex('x^2')">$$x^2$$</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="insertLatex('\\int_{a}^{b} f(x) dx')">$$\int$$</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="insertLatex('\\sum_{i=1}^{n}')">$$\sum$$</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="insertLatex('\\lim_{x \\to 0}')">$$\lim$$</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="insertLatex('\\Omega')">$$\Omega$$</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="insertLatex('\\alpha, \\beta, \\theta')">$$\theta$$</button>
                            </div>
                        </div>

                        <!-- Question Statement Textarea -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Question Statement <span class="text-danger">*</span></label>
                            <textarea name="Question_Text" id="qTextInput" class="form-control" rows="4" placeholder="Enter problem statement. For inline equations write $x = \frac{-b \pm \sqrt{b^2-4ac}}{2a}$ or use the MathLive field below..." required></textarea>
                        </div>

                        <!-- MathLive Virtual Interactive Field -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-primary">
                                <i class="bi bi-keyboard me-1"></i>MathLive Virtual Math Keyboard
                            </label>
                            <math-field id="mathLiveField" style="width: 100%; min-height: 48px; border: 1px solid #ced4da; border-radius: 0.375rem; padding: 8px 12px; font-size: 16px;"></math-field>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted">Type equations visually with full touchscreen/stylus support</small>
                                <button type="button" class="btn btn-sm btn-link text-decoration-none" onclick="copyMathLiveToText()">
                                    <i class="bi bi-arrow-up-circle me-1"></i> Insert into Statement
                                </button>
                            </div>
                        </div>

                        <!-- Diagram / Image Attachment -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Circuit Diagram / Graph (Optional)</label>
                            <input type="file" name="diagram_file" id="diagramInput" class="form-control" accept=".png,.jpg,.jpeg,.webp,.svg">
                        </div>

                        <!-- Dynamic Type Fields -->
                        <!-- MCQ Options -->
                        <div id="sectionMcq" class="border rounded p-3 bg-light mb-3">
                            <h6 class="fw-bold text-dark mb-2">Options & Correct Answer</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text fw-bold">A</span>
                                        <input type="text" name="opt_A" id="optA" class="form-control" placeholder="Option A text">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text fw-bold">B</span>
                                        <input type="text" name="opt_B" id="optB" class="form-control" placeholder="Option B text">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text fw-bold">C</span>
                                        <input type="text" name="opt_C" id="optC" class="form-control" placeholder="Option C text">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text fw-bold">D</span>
                                        <input type="text" name="opt_D" id="optD" class="form-control" placeholder="Option D text">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3" id="singleChoiceBox">
                                <label class="form-label fw-semibold small">Designated Correct Option</label>
                                <select name="correct_mcq" class="form-select">
                                    <option value="A">Option A</option>
                                    <option value="B">Option B</option>
                                    <option value="C">Option C</option>
                                    <option value="D">Option D</option>
                                </select>
                            </div>

                            <div class="mt-3 d-none" id="multiChoiceBox">
                                <label class="form-label fw-semibold small">Designated Correct Options</label>
                                <div class="d-flex gap-3">
                                    <label><input type="checkbox" name="correct_multi[]" value="A"> Option A</label>
                                    <label><input type="checkbox" name="correct_multi[]" value="B"> Option B</label>
                                    <label><input type="checkbox" name="correct_multi[]" value="C"> Option C</label>
                                    <label><input type="checkbox" name="correct_multi[]" value="D"> Option D</label>
                                </div>
                            </div>
                        </div>

                        <!-- Fill in Blanks -->
                        <div id="sectionFill" class="border rounded p-3 bg-light mb-3 d-none">
                            <label class="form-label fw-semibold">Accepted Keyword / Numerical Answers</label>
                            <input type="text" name="fill_answers" class="form-control" placeholder="e.g. 299792458, 3x10^8">
                            <div class="form-text">Comma-separated accepted alternatives.</div>
                        </div>

                        <!-- Match Following -->
                        <div id="sectionMatch" class="border rounded p-3 bg-light mb-3 d-none">
                            <label class="form-label fw-semibold">Matching Column Pairs</label>
                            <div class="row g-2 mb-2">
                                <div class="col-6"><input type="text" name="match_left[]" class="form-control form-control-sm" placeholder="Left item 1"></div>
                                <div class="col-6"><input type="text" name="match_right[]" class="form-control form-control-sm" placeholder="Right pair 1"></div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6"><input type="text" name="match_left[]" class="form-control form-control-sm" placeholder="Left item 2"></div>
                                <div class="col-6"><input type="text" name="match_right[]" class="form-control form-control-sm" placeholder="Right pair 2"></div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6"><input type="text" name="match_left[]" class="form-control form-control-sm" placeholder="Left item 3"></div>
                                <div class="col-6"><input type="text" name="match_right[]" class="form-control form-control-sm" placeholder="Right pair 3"></div>
                            </div>
                        </div>

                        <!-- Subjective Rubric -->
                        <div id="sectionOpen" class="border rounded p-3 bg-light mb-3 d-none">
                            <label class="form-label fw-semibold">Rubric Breakdown</label>
                            <textarea name="Rubric" class="form-control" rows="3" placeholder="Mark distribution breakdown..."></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1"></i> Save to Question Bank
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Real-Time Student View Preview -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-eye me-2"></i>Live Examinee Preview
                    </h6>
                    <span class="badge bg-light text-dark border">CBT View</span>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary-subtle text-primary fw-semibold" id="previewTypeBadge">MCQ</span>
                        <span class="badge bg-secondary-subtle text-secondary" id="previewCOBadge">CO1</span>
                        <span class="badge bg-info-subtle text-info-emphasis" id="previewMarksBadge">2.0 Marks</span>
                    </div>

                    <!-- Statement Render -->
                    <div id="previewStatement" class="fw-semibold text-dark fs-6 mb-3">
                        <em>Your rendered question statement will appear here in real-time...</em>
                    </div>

                    <!-- Options Preview Container -->
                    <div id="previewOptionsBox" class="d-flex flex-column gap-2">
                        <div class="p-2 border rounded bg-light text-muted small">Option preview will render here</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function handleTypeChange() {
    const val = document.getElementById('qTypeSelect').value;
    const sMcq = document.getElementById('sectionMcq');
    const sFill = document.getElementById('sectionFill');
    const sMatch = document.getElementById('sectionMatch');
    const sOpen = document.getElementById('sectionOpen');
    const single = document.getElementById('singleChoiceBox');
    const multi = document.getElementById('multiChoiceBox');

    sMcq.classList.add('d-none');
    sFill.classList.add('d-none');
    sMatch.classList.add('d-none');
    sOpen.classList.add('d-none');
    single.classList.add('d-none');
    multi.classList.add('d-none');

    if (val === 'mcq') {
        sMcq.classList.remove('d-none');
        single.classList.remove('d-none');
    } else if (val === 'multiselect') {
        sMcq.classList.remove('d-none');
        multi.classList.remove('d-none');
    } else if (val === 'fill') {
        sFill.classList.remove('d-none');
    } else if (val === 'match') {
        sMatch.classList.remove('d-none');
    } else if (val === 'open') {
        sOpen.classList.remove('d-none');
    }
    updatePreview();
}

function insertLatex(code) {
    const textarea = document.getElementById('qTextInput');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const insert = `$${code}$`;
    textarea.value = text.substring(0, start) + insert + text.substring(end);
    textarea.focus();
    updatePreview();
}

function copyMathLiveToText() {
    const mf = document.getElementById('mathLiveField');
    const val = mf.getValue();
    if (val) {
        insertLatex(val);
    }
}

function updatePreview() {
    const text = document.getElementById('qTextInput').value.trim();
    const prev = document.getElementById('previewStatement');
    const type = document.getElementById('qTypeSelect').value;
    const co = document.getElementById('coSelect').value;
    const marks = document.getElementById('marksInput').value;

    document.getElementById('previewTypeBadge').innerText = type.toUpperCase();
    document.getElementById('previewCOBadge').innerText = 'CO' + co;
    document.getElementById('previewMarksBadge').innerText = marks + ' Marks';

    if (text) {
        prev.innerHTML = text;
        if (typeof renderMathInElement !== 'undefined') {
            renderMathInElement(prev, {
                delimiters: [
                    {left: '$$', right: '$$', display: true},
                    {left: '$', right: '$', display: false}
                ],
                throwOnError: false
            });
        }
    } else {
        prev.innerHTML = '<em>Your rendered question statement will appear here in real-time...</em>';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('qTextInput').addEventListener('input', updatePreview);
    document.getElementById('marksInput').addEventListener('input', updatePreview);
    document.getElementById('coSelect').addEventListener('change', updatePreview);
});
</script>

<?php include '../Common/footer.php'; ?>