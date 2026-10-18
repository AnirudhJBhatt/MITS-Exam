<?php
require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/../Common/auth.php";

check_role('Faculty');

$Fac_ID    = $_SESSION['LoginFaculty'] ?? $_SESSION['user_id'] ?? '';
$Course_ID = $_GET['Course_ID'] ?? null;

// Fetch all courses assigned to this faculty
$stmtMyCourses = $pdo->prepare("
    SELECT c.Course_ID, c.Course_Code, c.Course_Name, c.Semester, cm.Acad_Year
    FROM course_mapping cm
    JOIN courses c ON cm.Course_ID = c.Course_ID
    WHERE cm.Fac_ID = ?
    ORDER BY c.Course_Name ASC
");
$stmtMyCourses->execute([$Fac_ID]);
$myCourses = $stmtMyCourses->fetchAll(PDO::FETCH_ASSOC);

if (!$Course_ID && !empty($myCourses)) {
    $Course_ID = $myCourses[0]['Course_ID'];
}

$cStmt = $pdo->prepare("SELECT * FROM courses WHERE Course_ID = ? LIMIT 1");
$cStmt->execute([$Course_ID]);
$course = $cStmt->fetch(PDO::FETCH_ASSOC);

$Course_Name = $course['Course_Name'] ?? '';
$Course_Code = $course['Course_Code'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty – Create Exam</title>

    <script defer src="https://unpkg.com/mathlive"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../Common/style.css">

    <style>
        /* Custom UI / Question Card enhancements */
        .type-pill.active          { background:#fdf2f2; border-color:#d1202d; color:#d1202d; font-weight:600; }
        .chip-mcq                  { background:#d1e7dd; color:#0a3622; }
        .chip-multiselect          { background:#fff3cd; color:#664d03; }
        .chip-diagram              { background:#fff3cd; color:#664d03; }
        .chip-match                { background:#e2d9f3; color:#432874; }
        .chip-open                 { background:#f8d7da; color:#58151c; }
        .chip-fill                 { background:#fce8ef; color:#6e1c35; }
        .diagram-upload            { border:2px dashed #ced4da; cursor:pointer; transition:.15s; background:#ffffff; }
        .diagram-upload:hover      { border-color:#d1202d; background:#fff5f5; }
        math-field                 { width:100%; min-height:42px; border:1px solid #ced4da; border-radius:.375rem; padding:6px 10px; font-size:15px; background:#fff; display:block; transition:border-color .15s ease-in-out, box-shadow .15s ease-in-out; }
        math-field:focus-within    { outline:none; border-color:#d1202d; box-shadow:0 0 0 0.25rem rgba(209, 32, 45, 0.2); }
        math-field::part(virtual-keyboard-toggle) { color:#d1202d; }
        :root                      { --keyboard-zindex: 2200; }
        .ML__keyboard, div.MLK__backdrop, .MLK__container, div[role="dialog"].ML__keyboard { z-index: 2200 !important; }
        .math-quick-bar button:hover { background-color:#e2e8f0; }
        .qn-card                   { border-radius:0.75rem; border:1px solid #e2e8f0; transition:box-shadow .2s ease, transform .2s ease, opacity .2s ease; }
        .qn-card:hover             { box-shadow:0 0.35rem 0.85rem rgba(0,0,0,.06) !important; }
        .saving-overlay            { display:none; position:fixed; inset:0; background:rgba(255,255,255,.65); z-index:2000; align-items:center; justify-content:center; }
        .saving-overlay.show       { display:flex; }
    </style>
</head>
<body>
    <?php include '../Common/header.php'; ?>
    <?php include '../Common/faculty-sidebar.php'; ?>

    <div class="saving-overlay" id="savingOverlay">
        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Saving…</span></div>
    </div>

    <main>
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h3 class="fw-bold mb-1 text-dark">
                    <i class="bi bi-pencil-square text-danger me-2"></i>Create Assessment
                </h3>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-danger" onclick="window.history.back()">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </button>
                <a href="question-bank.php" class="btn btn-outline-secondary">
                    <i class="bi bi-folder-check me-1"></i> Question Bank
                </a>
            </div>
        </div>

        <div class="sub-main">
            <!-- Exam details card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="text-uppercase small fw-semibold mb-0">Create exam</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Exam Name <span class="text-danger">*</span></label>
                            <input type="text" id="examName" class="form-control" placeholder="e.g. Mid-Semester Biology Test">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Duration (min) <span class="text-danger">*</span></label>
                            <input type="number" id="examDuration" class="form-control" placeholder="e.g. 60" min="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Start Time</label>
                            <input type="datetime-local" id="startTime" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">End Time</label>
                            <input type="datetime-local" id="endTime" class="form-control">
                        </div>
                    </div>

                    <label class="form-label small text-muted">Question type</label>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary type-pill active" data-type="mcq" onclick="setType(this,'mcq')">
                            MCQ
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary type-pill" data-type="multiselect" onclick="setType(this, 'multiselect')">
                            Multi-Select
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary type-pill" data-type="diagram" onclick="setType(this,'diagram')">
                            Diagram
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary type-pill" data-type="match" onclick="setType(this,'match')">
                            Match the following
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary type-pill" data-type="open" onclick="setType(this,'open')">
                            Open ended
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary type-pill" data-type="fill" onclick="setType(this,'fill')">
                            Fill in the blanks
                        </button>
                    </div>
                </div>
            </div>

            <!-- Questions card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-uppercase small fw-semibold text-white">Questions</span>
                        <span class="badge bg-light text-secondary border" id="qnCountBadge">0 questions</span>
                    </div>
                        <!-- <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openQBank()">
                            Add from question bank
                        </button> -->
                </div>
                <div class="card-body">
                    <div id="questionList"></div>

                    <div class="d-flex align-items-center gap-2 mt-2">
                        <hr class="flex-grow-1 m-0">
                        <button type="button" class="btn btn-success btn-sm" onclick="addQuestion()">
                            Add Question
                        </button>
                        
                    <button type="button" class="btn btn-sm btn-success" onclick="openQBank()">
                        Add from Question Bank
                    </button>
                        <hr class="flex-grow-1 m-0">
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 justify-content-center mt-3">
                    <!-- <button class="btn btn-outline-secondary" id="btnDraft" onclick="saveExam('draft')">
                        <i class="ti ti-device-floppy me-1"></i>Save draft
                    </button> -->
                    <button class="btn btn-primary" id="btnPublish" onclick="saveExam('published')">
                        <i class="bi bi-cursor-fill me-1"></i>Publish exam
                    </button>
                </div>

        </div>
    </main>

    <?php include '../Common/footer.php'; ?>

    <!-- Toast -->
    <div id="ebToast" class="toast align-items-center border-0 position-fixed bottom-0 end-0 m-3" role="alert" style="z-index:2001">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="ebToastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>

    <!-- Question bank modal -->
    <div class="modal fade" id="qbankModal" tabindex="-1" aria-label="Question bank">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-database me-2"></i>Question bank</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-3 border-bottom">
                        <input type="text" id="bankSearchInput" class="form-control form-control-sm mb-2" placeholder="Search questions…">
                        <div class="d-flex flex-wrap gap-1" id="bankTypeFilters">
                            <button type="button" class="btn btn-sm btn-primary type-filter-chip" onclick="setBankTypeFilter(this,'')">All</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary type-filter-chip" onclick="setBankTypeFilter(this,'mcq')">MCQ</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary type-filter-chip" onclick="setBankTypeFilter(this,'open')">Open ended</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary type-filter-chip" onclick="setBankTypeFilter(this,'fill')">Fill</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary type-filter-chip" onclick="setBankTypeFilter(this,'match')">Match</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary type-filter-chip" onclick="setBankTypeFilter(this,'diagram')">Diagram</button>
                        </div>
                    </div>
                    <div id="qbankList" class="p-3" style="min-height:200px">
                        <p class="text-center text-muted py-4 mb-0">Loading…</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="importFromBank()">
                        <i class="ti ti-download me-1"></i>Import selected
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        'use strict';

        const COURSE_ID = <?= json_encode($Course_ID) ?>;
        const FAC_ID    = <?= json_encode($Fac_ID) ?>;
        const API       = 'api.php';

        const CHIP_CLASS = { mcq:'chip-mcq', multiselect:'chip-multiselect', diagram:'chip-diagram', match:'chip-match', open:'chip-open', fill:'chip-fill' };
        const CHIP_LABEL = { mcq:'MCQ', multiselect:'Multi-Select', diagram:'Diagram', match:'Match', open:'Open ended', fill:'Fill up' };

        let currentType    = 'mcq';
        let questionCounter = 0;
        let questions      = [];
        let examId         = null;
        let bankData       = [];
        let bankTypeFilter = '';
        let bankDebounce   = null;

        const qbankModal = new bootstrap.Modal(document.getElementById('qbankModal'));
        const toastEl    = document.getElementById('ebToast');
        const bsToast    = new bootstrap.Toast(toastEl, { delay: 2800 });

        // ── Type pills ──────────────────────────────────────────────
        function setType(el, type) {
            document.querySelectorAll('.type-pill').forEach(p => p.classList.remove('active'));
            el.classList.add('active');
            currentType = type;
        }

        // ── Question templates ───────────────────────────────────────
        function getQuestionTemplate(type, qId) {
            if (type === 'mcq') return `
                <div class="col-12 row g-2">
                    <div class="col-12 d-flex justify-content-between align-items-center mt-1 mb-1">
                        <label class="form-label fw-bold text-dark mb-0 small"><i class="bi bi-ui-radios-grid me-1 text-danger"></i>Answer Options & Correct Choice</label>
                        <small class="text-muted" style="font-size: 11px;">Select the radio to mark correct answer</small>
                    </div>
                    ${['A','B','C','D'].map((l, idx) => `
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 p-1 border rounded bg-white">
                            <input type="radio" name="correct_${qId}" value="${l}" class="form-check-input opt-radio mt-0 flex-shrink-0" ${idx === 0 ? 'checked' : ''} title="Mark Option ${l} as correct">
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle fw-bold">${l}</span>
                            <math-field class="opt-input flex-grow-1" id="qo_${qId}_${l}" placeholder="Option ${l} formula / text…"></math-field>
                        </div>
                    </div>`).join('')}
                </div>`;

            if (type === 'multiselect') return `
                <div class="col-12 row g-2">
                    <div class="col-12 d-flex justify-content-between align-items-center mt-1 mb-1">
                        <label class="form-label fw-bold text-dark mb-0 small"><i class="bi bi-ui-checks me-1 text-danger"></i>Answer Options & Correct Choices</label>
                        <small class="text-muted" style="font-size: 11px;">Select checkboxes to mark correct answers</small>
                    </div>
                    ${['A','B','C','D'].map(l => `
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 p-1 border rounded bg-white">
                            <input type="checkbox" id="qms_${qId}_${l}" name="correct_multi_${qId}[]" value="${l}" class="opt-checkbox form-check-input mt-0 flex-shrink-0" title="Mark Option ${l} as correct">
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle fw-bold">${l}</span>
                            <math-field class="opt-input flex-grow-1" id="qo_${qId}_${l}" placeholder="Option ${l} formula / text…"></math-field>
                        </div>
                    </div>`).join('')}
                </div>`;

            if (type === 'diagram') return `
                <div class="mt-2">
                    <label class="form-label fw-semibold small mb-1">Schematic / Diagram Image (Optional)</label>
                    <div class="diagram-upload rounded p-4 text-center text-muted" id="du_${qId}" role="button" tabindex="0">
                        <i class="ti ti-cloud-upload fs-3 d-block mb-1"></i>
                        <span class="small">Click to upload diagram / reference image</span><br>
                        <span class="text-muted" style="font-size:11px">PNG, JPG, SVG, WebP — up to 5 MB</span>
                    </div>
                    <div class="mt-2">
                        <label class="form-label fw-semibold small mb-1">Grading Rubric / Annotation Instructions</label>
                        <math-field class="diagram-rubric" id="mf_rubric_${qId}" placeholder="e.g. Label the parts A–D, 2 marks each…"></math-field>
                    </div>
                </div>`;

            if (type === 'match') return `
                <div class="mt-2">
                    <label class="form-label fw-semibold small mb-1"><i class="bi bi-arrow-left-right me-1 text-danger"></i>Matching Pairs (MathLive Enabled)</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="text-center small fw-semibold rounded py-1 mb-2 bg-primary bg-opacity-10 text-primary">Column A (Premises)</div>
                            ${[1,2,3,4].map(i => `
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text fw-bold">${i}</span>
                                <math-field class="opt-input form-control" id="qa_${qId}_${i}" placeholder="Premise ${i}"></math-field>
                            </div>`).join('')}
                        </div>
                        <div class="col-6">
                            <div class="text-center small fw-semibold rounded py-1 mb-2 bg-success bg-opacity-10 text-success">Column B (Matches)</div>
                            ${['a','b','c','d'].map((l) => `
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text fw-bold text-uppercase">${l}</span>
                                <math-field class="opt-input form-control" id="qb_${qId}_${l}" placeholder="Match ${l.toUpperCase()}"></math-field>
                            </div>`).join('')}
                        </div>
                    </div>
                </div>`;

            if (type === 'open') return `
                <div class="mt-2">
                    <label class="form-label fw-semibold small mb-1"><i class="bi bi-card-text me-1 text-danger"></i>Grading Rubric / Criteria (MathLive Enabled)</label>
                    <math-field class="open-rubric" id="mf_rubric_${qId}" placeholder="Describe key points or marking scheme (e.g. 2 marks for derivation of \\nabla \\times E = 0)..." style="min-height: 60px;"></math-field>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <label class="form-label small text-muted mb-0">Word Limit (Optional):</label>
                        <input type="number" class="form-control form-control-sm word-limit-input" placeholder="e.g. 250" min="10" style="max-width: 140px;">
                    </div>
                </div>`;

            if (type === 'fill') return `
                <div class="mt-2">
                    <div class="alert alert-light border py-2 px-3 small text-muted mb-2">
                        <i class="bi bi-info-circle me-1 text-primary"></i>Use <code class="bg-primary bg-opacity-10 text-primary rounded px-1">___</code> (three underscores) in the Question Statement above for each blank.
                    </div>
                    <div id="fp_${qId}" class="bg-light border rounded p-2 mb-2 small text-muted" style="display:none;line-height:2"></div>
                    <label class="form-label fw-semibold small mb-1">Accepted Answers (in order)</label>
                    <div id="fill_ans_${qId}" class="d-flex flex-column gap-2"></div>
                </div>`;

            return '';
        }

        // ── Fill preview ─────────────────────────────────────────────
        function updateFillPreview(ta, qId) {
            const val    = ta.value;
            const fp     = document.getElementById('fp_'       + qId);
            const ansDiv = document.getElementById('fill_ans_' + qId);
            if (!val) { if (fp) fp.style.display = 'none'; return; }
            const blanks = (val.match(/___/g) || []).length;
            if (!blanks) { if (fp) fp.style.display = 'none'; if (ansDiv) ansDiv.innerHTML = ''; return; }
            if (fp) {
                fp.style.display = 'block';
                fp.innerHTML = val.replace(/___/g, `<span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2">blank</span>`);
            }
            if (!ansDiv) return;
            const existing = ansDiv.querySelectorAll('input').length;
            if (existing < blanks) {
                for (let i = existing; i < blanks; i++) {
                    const inp = document.createElement('input');
                    inp.className   = 'form-control form-control-sm';
                    inp.type        = 'text';
                    inp.placeholder = `Answer for blank ${i + 1}`;
                    ansDiv.appendChild(inp);
                }
            } else {
                while (ansDiv.querySelectorAll('input').length > blanks)
                    ansDiv.removeChild(ansDiv.lastChild);
            }
        }

        // ── Add question ─────────────────────────────────────────────
        function addQuestion(type, text, extraData) {
            questionCounter++;
            const qId   = 'q' + questionCounter;
            const qType = type || currentType;
            questions.push({ id: qId, type: qType });

            const card = document.createElement('div');
            card.className    = 'card border shadow-sm qn-card mb-3';
            card.id           = 'card_' + qId;
            card.dataset.type = qType;
            card.dataset.qid  = qId;

            card.innerHTML = `
                <div class="card-header bg-light d-flex align-items-center justify-content-between py-2 px-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger rounded-circle d-flex align-items-center justify-content-center qn-num" style="width:28px;height:28px;font-size:13px;font-weight:600">${questions.length}</span>
                        <span class="badge rounded-pill ${CHIP_CLASS[qType]} px-3 py-1 fw-semibold">${CHIP_LABEL[qType]}</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="moveQuestion('${qId}',-1)" title="Move up">
                            <i class="bi bi-chevron-up"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="moveQuestion('${qId}',1)" title="Move down">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteQuestion('${qId}')" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Metadata Controls matching Question Bank Modal -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Course Outcome (CO)</label>
                            <select id="co_${qId}" class="form-select form-select-sm">
                                <option value="1">CO1</option>
                                <option value="2">CO2</option>
                                <option value="3">CO3</option>
                                <option value="4">CO4</option>
                                <option value="5">CO5</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Marks Allocated</label>
                            <input type="number" step="0.5" id="marks_${qId}" class="form-control form-control-sm" value="${extraData?.marks ?? (extraData?.Marks ?? 1.0)}" min="0.5" max="50">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Bloom's Level</label>
                            <select id="bloom_${qId}" class="form-select form-select-sm">
                                <option value="Remember">Remember</option>
                                <option value="Understand" selected>Understand</option>
                                <option value="Apply">Apply</option>
                                <option value="Analyze">Analyze</option>
                                <option value="Evaluate">Evaluate</option>
                                <option value="Create">Create</option>
                            </select>
                        </div>
                    </div>

                    <!-- Question Statement with MathLive & Virtual Math Keyboard -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold small mb-0">Question Statement <span class="text-danger">*</span></label>
                        </div>
                        <div class="math-field-wrapper position-relative">
                            <math-field id="qt_${qId}" style="min-height: 60px;" placeholder="Enter question statement or type equations using the MathLive keyboard…"${qType === 'fill' ? ` oninput="updateFillPreview(this,'${qId}')"` : ''}></math-field>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-1 math-quick-bar">
                            <small class="text-muted me-1 align-self-center" style="font-size: 11px;">Quick Math:</small>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('qt_${qId}', '\\\\frac{#@}{#?}')">½ frac</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('qt_${qId}', '\\\\sqrt{#?}')">√ sqrt</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('qt_${qId}', '#@^{#?}')">x² sup</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('qt_${qId}', '#@_{#?}')">x₁ sub</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('qt_${qId}', '\\\\pm')">±</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('qt_${qId}', '\\\\pi')">π</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('qt_${qId}', '\\\\theta')">θ</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('qt_${qId}', '\\\\sum_{#?}^{#?}')">∑</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('qt_${qId}', '\\\\int_{#?}^{#?}')">∫</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-1 font-monospace" style="font-size:11px;" onclick="insertSymbol('qt_${qId}', '\\\\infty')">∞</button>
                        </div>
                    </div>

                    <!-- Type Specific Answers Body -->
                    <div class="qn-body">${getQuestionTemplate(qType, qId)}</div>
                </div>`;

            document.getElementById('questionList').appendChild(card);

            if (qType === 'diagram') {
                const uploadArea = card.querySelector('#du_' + qId);
                const fi = document.createElement('input');
                fi.type = 'file'; fi.accept = 'image/*,.svg'; fi.style.display = 'none';
                fi.id   = 'img_' + qId;
                card.appendChild(fi);
                uploadArea.onclick   = (e) => { e.stopPropagation(); fi.click(); };
                uploadArea.onkeydown = (e) => { if (e.key === 'Enter' || e.key === ' ') fi.click(); };
                fi.onchange = (e) => handleImageUpload(e, qId, uploadArea);
                if (extraData?.image_path) {
                    uploadArea.dataset.imagePath = extraData.image_path;
                    uploadArea.innerHTML = `<img src="${extraData.image_path}" class="img-fluid rounded" style="max-height:180px" alt="Uploaded diagram">`;
                }
            }

            const qt = document.getElementById('qt_' + qId);
            if (qt && text) {
                qt.value = text;
                if (qType === 'fill') updateFillPreview(qt, qId);
            }
            if (extraData) prefillQuestion(card, qType, qId, extraData);

            updateCount();
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // ── Prefill from bank ────────────────────────────────────────
        function prefillQuestion(card, type, qId, data) {
            if (data.co) {
                const coEl = document.getElementById('co_' + qId);
                if (coEl) coEl.value = data.co;
            }
            if (data.bloom_level || data.Bloom_Level) {
                const bEl = document.getElementById('bloom_' + qId);
                if (bEl) bEl.value = data.bloom_level || data.Bloom_Level;
            }
            if (data.marks !== undefined || data.Marks !== undefined) {
                const mEl = document.getElementById('marks_' + qId);
                if (mEl) mEl.value = data.marks ?? data.Marks;
            }

            if (type === 'mcq' && Array.isArray(data.options)) {
                data.options.forEach(opt => {
                    const el = document.getElementById(`qo_${qId}_${opt.letter}`);
                    if (el) el.value = opt.text || '';
                });
                if (data.correct_opt) {
                    const radio = card.querySelector(`input[name="correct_${qId}"][value="${data.correct_opt}"]`);
                    if (radio) radio.checked = true;
                }
            }
            if (type === 'multiselect' && Array.isArray(data.options)) {
                data.options.forEach(opt => {
                    const el = document.getElementById(`qo_${qId}_${opt.letter}`);
                    if (el) el.value = opt.text || '';
                });
                const ans = data.answers || data.correct_opts;
                if (ans) {
                    let correctOpts = [];
                    if (Array.isArray(ans)) {
                        correctOpts = ans;
                    } else if (typeof ans === 'string') {
                        try {
                            const parsed = JSON.parse(ans);
                            if (Array.isArray(parsed)) correctOpts = parsed;
                            else correctOpts = ans.split(',');
                        } catch (e) {
                            correctOpts = ans.split(',');
                        }
                    }
                    card.querySelectorAll('.opt-checkbox').forEach(cb => cb.checked = false);
                    correctOpts.forEach(c => {
                        const val = String(c).trim();
                        const cb = document.getElementById(`qms_${qId}_${val}`) || card.querySelector(`input[name="correct_multi_${qId}[]"][value="${val}"]`);
                        if (cb) cb.checked = true;
                    });
                }
            }
            if (type === 'match' && Array.isArray(data.pairs)) {
                const cols = ['a','b','c','d'];
                data.pairs.forEach((pair, i) => {
                    const aEl = document.getElementById(`qa_${qId}_${i + 1}`);
                    const bEl = document.getElementById(`qb_${qId}_${cols[i]}`);
                    if (aEl) aEl.value = pair.a || '';
                    if (bEl) bEl.value = pair.b || '';
                });
            }
            if (type === 'open') {
                const r  = card.querySelector('.open-rubric');
                const wl = card.querySelector('.word-limit-input');
                if (r  && data.rubric)     r.value  = data.rubric;
                if (wl && data.word_limit) wl.value = data.word_limit;
            }
            if (type === 'diagram' && data.rubric) {
                const r = card.querySelector('.diagram-rubric');
                if (r) r.value = data.rubric;
            }
            if (type === 'fill' && Array.isArray(data.answers)) {
                setTimeout(() => {
                    const inputs = document.getElementById('fill_ans_' + qId)?.querySelectorAll('input') || [];
                    data.answers.forEach((ans, i) => { if (inputs[i]) inputs[i].value = ans; });
                }, 150);
            }
        }

        // ── Diagram upload ───────────────────────────────────────────
        async function handleImageUpload(e, qId, uploadArea) {
            const file = e.target.files[0];
            if (!file) return;
            uploadArea.innerHTML = `<div class="spinner-border spinner-border-sm text-primary me-2"></div>Uploading…`;
            const fd = new FormData();
            fd.append('image', file);
            try {
                const res  = await fetch(`${API}?action=upload_image`, { method:'POST', body:fd });
                const data = await res.json();
                if (data.success) {
                    uploadArea.dataset.imagePath = data.path;
                    uploadArea.innerHTML = `<img src="${data.path}" class="img-fluid rounded" style="max-height:180px" alt="Uploaded"><br><small class="text-muted">${file.name}</small>`;
                } else {
                    uploadArea.innerHTML = `<i class="ti ti-cloud-upload fs-3 d-block"></i><span class="small">Upload failed — click to retry</span>`;
                    showToast('Upload failed: ' + data.message, 'danger');
                }
            } catch {
                uploadArea.innerHTML = `<i class="ti ti-cloud-upload fs-3 d-block"></i><span class="small">Upload failed — click to retry</span>`;
                showToast('Network error during upload', 'danger');
            }
        }

        // ── Delete / move ────────────────────────────────────────────
        function deleteQuestion(qId) {
            const idx = questions.findIndex(q => q.id === qId);
            if (idx > -1) questions.splice(idx, 1);
            const card = document.getElementById('card_' + qId);
            if (card) {
                card.style.opacity   = '0';
                card.style.transform = 'scale(.97)';
                setTimeout(() => card.remove(), 200);
            }
            setTimeout(updateCount, 220);
        }

        function moveQuestion(qId, dir) {
            const list = document.getElementById('questionList');
            const card = document.getElementById('card_' + qId);
            if (dir === -1 && card.previousElementSibling) list.insertBefore(card, card.previousElementSibling);
            if (dir ===  1 && card.nextElementSibling)     list.insertBefore(card.nextElementSibling, card);
            updateCount();
        }

        function updateCount() {
            const cards = document.querySelectorAll('.qn-card');
            cards.forEach((c, i) => { const n = c.querySelector('.qn-num'); if (n) n.textContent = i + 1; });
            const cnt = cards.length;
            document.getElementById('qnCountBadge').textContent = cnt + ' question' + (cnt !== 1 ? 's' : '');
        }

        // ── Serialize ────────────────────────────────────────────────
        function serializeQuestion(card) {
            const type         = card.dataset.type;
            const qId          = card.dataset.qid;
            const qtEl         = document.getElementById('qt_' + qId);
            const questionText = qtEl ? (qtEl.value || '') : '';
            const marks        = parseFloat(document.getElementById('marks_' + qId)?.value || 1);
            const co           = parseInt(document.getElementById('co_' + qId)?.value || 1, 10);
            const bloomLevel   = document.getElementById('bloom_' + qId)?.value || 'Understand';
            const q            = { type, question_text: questionText, marks, co, bloom_level: bloomLevel };

            if (type === 'mcq') {
                const opts = []; let correctOpt = null;
                const letters = ['A','B','C','D'];
                letters.forEach(l => {
                    const el = document.getElementById(`qo_${qId}_${l}`);
                    if (el) opts.push({ letter: l, text: el.value || '' });
                });
                const checkedRadio = card.querySelector(`input[name="correct_${qId}"]:checked`);
                if (checkedRadio) correctOpt = checkedRadio.value;
                q.options = opts; q.correct_opt = correctOpt;
            }
            if (type === 'multiselect') {
                const opts = [];
                const correctSet = new Set();
                ['A', 'B', 'C', 'D'].forEach(letter => {
                    const el = document.getElementById(`qo_${qId}_${letter}`);
                    const checkbox = document.getElementById(`qms_${qId}_${letter}`);
                    if (el) opts.push({ letter, text: el.value || '' });
                    if (checkbox?.checked) correctSet.add(letter);
                });
                q.options = opts;
                const correctArr = Array.from(correctSet);
                q.correct_opts = correctArr;
                q.answers = correctArr;
            }
            if (type === 'open') {
                q.rubric     = card.querySelector('.open-rubric')?.value || null;
                const wl     = card.querySelector('.word-limit-input')?.value;
                q.word_limit = wl ? parseInt(wl, 10) : null;
            }
            if (type === 'fill') {
                const ansDiv = document.getElementById('fill_ans_' + qId);
                q.answers = ansDiv ? Array.from(ansDiv.querySelectorAll('input')).map(i => i.value) : [];
            }
            if (type === 'match') {
                const aEls = card.querySelectorAll('[id^="qa_"]');
                const bEls = card.querySelectorAll('[id^="qb_"]');
                q.pairs = Array.from({ length: Math.max(aEls.length, bEls.length) }, (_, i) => ({
                    a: aEls[i]?.value || '', b: bEls[i]?.value || ''
                }));
            }
            if (type === 'diagram') {
                const du     = document.getElementById('du_' + qId);
                q.image_path = du?.dataset.imagePath || null;
                q.rubric     = card.querySelector('.diagram-rubric')?.value || null;
            }
            return q;
        }

        // ── Validate ─────────────────────────────────────────────────
        function validateExam() {
            if (!document.getElementById('examName').value.trim())     { showToast('Please enter an exam name', 'danger'); return false; }
            if (!document.getElementById('examDuration').value.trim()) { showToast('Please enter the duration', 'danger'); return false; }
            const cards = document.querySelectorAll('.qn-card');
            if (!cards.length) { showToast('Add at least one question', 'danger'); return false; }
            for (const card of cards) {
                const qt = document.getElementById('qt_' + card.dataset.qid);
                if (!qt?.value?.trim()) {
                    card.scrollIntoView({ behavior:'smooth', block:'center' });
                    showToast('All questions must have question text', 'danger');
                    return false;
                }
                if (card.dataset.type === 'mcq' || card.dataset.type === 'multiselect') {
                    const filled = Array.from(card.querySelectorAll('.opt-input')).filter(i => i.value.trim()).length;
                    if (filled < 2) {
                        card.scrollIntoView({ behavior:'smooth', block:'center' });
                        showToast((card.dataset.type === 'mcq' ? 'MCQ' : 'Multi-Select') + ' needs at least 2 options', 'danger');
                        return false;
                    }
                }
            }
            return true;
        }

        // ── Save exam ────────────────────────────────────────────────
        async function saveExam(status) {
            if (!validateExam()) return;
            const cards   = document.querySelectorAll('.qn-card');
            const payload = {
                course_id:  COURSE_ID,
                fac_id:     FAC_ID,
                name:       document.getElementById('examName').value.trim(),
                duration:   parseInt(document.getElementById('examDuration').value, 10),
                start_time: document.getElementById('startTime').value  || null,
                end_time:   document.getElementById('endTime').value    || null,
                status,
                questions:  Array.from(cards).map((c, i) => ({ ...serializeQuestion(c), sort_order: i })),
            };
            if (examId) payload.exam_id = examId;
            setSavingState(true);
            try {
                const action = examId ? 'update_exam' : 'save_exam';
                const res    = await fetch(`${API}?action=${action}`, {
                    method:'POST', headers:{ 'Content-Type':'application/json' }, body:JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    examId = data.exam_id;
                    showToast(status === 'published' ? `Exam published! (ID ${examId})` : `Draft saved (ID ${examId})`, 'success');
                } else {
                    showToast('Error: ' + (data.message || 'Unknown error'), 'danger');
                }
            } catch {
                showToast('Network error — please try again', 'danger');
            } finally {
                setSavingState(false);
            }
        }

        function setSavingState(active) {
            const overlay = document.getElementById('savingOverlay');
            if (overlay) overlay.classList.toggle('show', active);
            const btnDraft = document.getElementById('btnDraft');
            if (btnDraft) btnDraft.disabled = active;
            const btnPublish = document.getElementById('btnPublish');
            if (btnPublish) btnPublish.disabled = active;
        }

        // ── Question bank ────────────────────────────────────────────
        async function openQBank() {
            bankData = []; renderBankList([]);
            qbankModal.show();
            await loadBankQuestions('', bankTypeFilter);
        }

        function setBankTypeFilter(el, type) {
            document.querySelectorAll('.type-filter-chip').forEach(c => {
                c.classList.remove('btn-primary');
                c.classList.add('btn-outline-secondary');
            });
            el.classList.remove('btn-outline-secondary');
            el.classList.add('btn-primary');
            bankTypeFilter = type;
            loadBankQuestions(document.getElementById('bankSearchInput').value.trim(), type);
        }

        async function loadBankQuestions(search, type) {
            const list   = document.getElementById('qbankList');
            list.innerHTML = '<p class="text-center text-muted py-4 mb-0">Loading…</p>';
            const params = new URLSearchParams({ action:'bank_questions' });
            if (search)    params.set('search', search);
            if (type)      params.set('type', type);
            if (examId)    params.set('exclude_exam', examId);
            if (COURSE_ID) params.set('course_id', COURSE_ID);
            try {
                const res  = await fetch(`${API}?${params}`);
                const data = await res.json();
                if (!data.success) { list.innerHTML = `<p class="text-center text-danger py-4 mb-0">${data.message}</p>`; return; }
                bankData = data.questions;
                renderBankList(bankData);
            } catch {
                list.innerHTML = '<p class="text-center text-danger py-4 mb-0">Network error</p>';
            }
        }

        function renderBankList(qs) {
            const list = document.getElementById('qbankList');
            if (!qs.length) { list.innerHTML = '<p class="text-center text-muted py-4 mb-0">No questions found</p>'; return; }
            list.innerHTML = qs.map((q, i) => `
                <div class="d-flex align-items-center gap-2 border rounded p-2 mb-2 qbank-item" onclick="toggleBankItem(this)" style="cursor:pointer">
                    <input type="checkbox" id="bq_${i}" value="${i}" class="form-check-input mt-0 flex-shrink-0">
                    <label for="bq_${i}" class="flex-grow-1 small mb-0" onclick="event.stopPropagation()">${escHtml(q.question_text)}</label>
                    <span class="badge rounded-pill ${CHIP_CLASS[q.type]} flex-shrink-0">${CHIP_LABEL[q.type]}</span>
                </div>`).join('');
        }

        function toggleBankItem(el) {
            const cb = el.querySelector('input[type=checkbox]');
            if (cb) cb.checked = !cb.checked;
        }

        async function importFromBank() {
            const checked = document.querySelectorAll('#qbankList input[type=checkbox]:checked');
            if (!checked.length) { showToast('Select at least one question', 'danger'); return; }
            checked.forEach(cb => { const q = bankData[parseInt(cb.value, 10)]; if (q) addQuestion(q.type, q.question_text, q); });
            qbankModal.hide();
            showToast(`${checked.length} question${checked.length > 1 ? 's' : ''} imported`, 'success');
        }

        document.getElementById('bankSearchInput').addEventListener('input', (e) => {
            clearTimeout(bankDebounce);
            bankDebounce = setTimeout(() => loadBankQuestions(e.target.value.trim(), bankTypeFilter), 350);
        });

        // ── Toast ────────────────────────────────────────────────────
        function showToast(msg, type = 'success') {
            toastEl.className = `toast align-items-center border-0 position-fixed bottom-0 end-0 m-3 text-bg-${type}`;
            document.getElementById('ebToastMsg').textContent = msg;
            bsToast.show();
        }

        function escHtml(str) {
            return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        // ── MathLive Symbol Insert Helper ────────────────────────────
        let activeMathField = null;
        document.addEventListener('focusin', function(e) {
            if (e.target && e.target.tagName && e.target.tagName.toLowerCase() === 'math-field') {
                activeMathField = e.target;
            }
        });

        function insertSymbol(targetId, latex) {
            let mf = activeMathField;
            if (!mf || (targetId && !mf.closest('#card_' + targetId.replace('qt_', '')))) {
                mf = document.getElementById(targetId) || activeMathField;
            }
            if (mf) {
                if (typeof mf.executeCommand === 'function') {
                    mf.focus();
                    mf.executeCommand(['insert', latex]);
                } else if ('value' in mf) {
                    mf.value = (mf.value || '') + latex;
                }
            }
        }
    </script>
</body>
</html>