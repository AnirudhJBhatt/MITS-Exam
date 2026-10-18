<?php
require_once "../Connection/connection.php";
require_once "../Common/auth.php";

check_role('Faculty');

$Fac_ID = $_SESSION['LoginFaculty'] ?? $_SESSION['user_id'] ?? '';
$Edit_Exam_ID = $_GET['Exam_ID'] ?? null;
$Course_ID = $_GET['Course_ID'] ?? null;

if (!$Edit_Exam_ID) {
    header("Location: manage-exams.php");
    exit;
}

// Fetch faculty academic year
$qStmt = $pdo->prepare("SELECT Acad_Year FROM faculty WHERE Fac_ID = ? LIMIT 1");
$qStmt->execute([$Fac_ID]);
$Acad_Year = $qStmt->fetchColumn() ?: '2024-2025';

// If Course_ID is missing, resolve it from exam header
if (!$Course_ID) {
    $eStmt = $pdo->prepare("SELECT Course_ID FROM exams WHERE Exam_ID = ? LIMIT 1");
    $eStmt->execute([$Edit_Exam_ID]);
    $Course_ID = $eStmt->fetchColumn();
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
    <title>Faculty – Edit Exam</title>

    <script defer src="https://unpkg.com/mathlive"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../Common/style.css">

    <style>
        /* Only what Bootstrap cannot do */
        .type-pill.active,
        .edit-type-pill.active {
            background: #fdf2f2;
            border-color: #d1202d;
            color: #d1202d;
            font-weight: 600;
        }

        .chip-mcq {
            background: #d1e7dd;
            color: #0a3622;
        }

        .chip-match {
            background: #e2d9f3;
            color: #432874;
        }

        .chip-open {
            background: #f8d7da;
            color: #58151c;
        }

        .chip-fill {
            background: #fce8ef;
            color: #6e1c35;
        }

        .chip-multiselect {
            background: #fff3cd;
            color: #664d03;
        }

        .image-upload {
            border: 2px dashed #ced4da;
            cursor: pointer;
            transition: .15s;
            background: #ffffff;
        }

        .image-upload:hover {
            border-color: #d1202d;
            background: #fff5f5;
        }

        math-field {
            width: 100%;
            min-height: 42px;
            border: 1px solid #ced4da;
            border-radius: .375rem;
            padding: 6px 10px;
            font-size: 15px;
            background: #fff;
            display: block;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        math-field:focus-within {
            outline: none;
            border-color: #d1202d;
            box-shadow: 0 0 0 0.25rem rgba(209, 32, 45, 0.2);
        }

        math-field::part(virtual-keyboard-toggle) {
            color: #d1202d;
        }

        :root {
            --keyboard-zindex: 2200;
        }

        .ML__keyboard, div.MLK__backdrop, .MLK__container, div[role="dialog"].ML__keyboard {
            z-index: 2200 !important;
        }

        .math-quick-bar button:hover {
            background-color: #e2e8f0;
        }

        .qn-card {
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            transition: box-shadow .2s ease, transform .2s ease, opacity .2s ease;
        }

        .qn-card:hover {
            box-shadow: 0 0.35rem 0.85rem rgba(0, 0, 0, .06) !important;
        }

        .saving-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, .65);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .saving-overlay.show {
            display: flex;
        }
    </style>
</head>

<body>
    <?php include '../Common/header.php'; ?>
    <?php include '../Common/faculty-sidebar.php'; ?>

    <div class="saving-overlay" id="savingOverlay">
        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Saving…</span></div>
    </div>

    <main>
        <div class="dashboard-header">
            <h4 class="mb-0 fw-bold">
                <i class="ti ti-pencil me-2"></i>Edit Exam
                <?php if ($Course_Name): ?>
                    <small class="fw-normal opacity-75 ms-2">
                        <?= htmlspecialchars($Course_Name) ?> (<?= htmlspecialchars($Course_Code) ?>)
                    </small>
                <?php endif; ?>
            </h4>
        </div>


        <div class="sub-main pb-5">
            
            <div>
                <div class="mt-3">
                    <!-- Exam details card -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                            <span class="text-uppercase small fw-semibold text-muted">Exam Details</span>
                            <button class="btn btn-outline-secondary" id="btndwn" onclick="downloadQP()">
                                Download Question Paper
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Exam Name <span class="text-danger">*</span></label>
                                    <input type="text" id="editExamName" class="form-control" placeholder="e.g. Mid-Semester Biology Test">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Duration (min) <span class="text-danger">*</span></label>
                                    <input type="number" id="editExamDuration" class="form-control" placeholder="e.g. 60" min="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Start Time</label> <span class="text-danger">*</span></label>
                                    <input type="datetime-local" id="editStartTime" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">End Time</label> <span class="text-danger">*</span></label>
                                    <input type="datetime-local" id="editEndTime" class="form-control">
                                </div>
                            </div>

                            <label class="form-label small text-muted">Select Question Type</label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary edit-type-pill active" data-type="mcq" onclick="setEditType(this,'mcq')">
                                MCQ
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-type-pill" data-type="match" onclick="setEditType(this,'match')">
                                    Match the following
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-type-pill" data-type="open" onclick="setEditType(this,'open')">
                                    Open ended
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-type-pill" data-type="fill" onclick="setEditType(this,'fill')">
                                    Fill in the blanks
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-type-pill" data-type="multiselect" onclick="setEditType(this,'multiselect')">
                                    Multi Select
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-type-pill" data-type="case" onclick="setEditType(this,'case')">
                                    Case Study
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Questions card -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-uppercase small fw-semibold text-muted">Questions</span>
                                <span class="badge bg-light text-secondary border" id="editQnCountBadge">0 questions</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openQBank(true)">
                                Add from question bank
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="editQuestionList"></div>

                            <div class="d-flex align-items-center gap-2 mt-2">
                                <hr class="flex-grow-1 m-0">
                                <button type="button" class="btn btn-success btn-sm" onclick="addQuestion(null, null, null, true)">
                                    Add question manually
                                </button>
                                <button type="button" class="btn btn-success btn-sm" onclick="openQBank(true)">
                                    Add question from question bank
                                </button>
                                <hr class="flex-grow-1 m-0">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button class="btn btn-primary" id="btnUpdateExam" onclick="saveExam(editExamStatus || 'published', true)">
                            Update exam
                        </button>
                    </div>
                </div>
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
                    <h5 class="modal-title">Question bank</h5>
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
                            <button type="button" class="btn btn-sm btn-outline-secondary type-filter-chip" onclick="setBankTypeFilter(this,'multiselect')">Multi Select</button>
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        'use strict';

        const COURSE_ID = <?= json_encode($Course_ID) ?>;
        const FAC_ID = <?= json_encode($Fac_ID) ?>;
        const Acad_Year = <?= json_encode($Acad_Year) ?>;
        const API = 'api.php';
        const EDIT_EXAM_ID = <?= json_encode($Edit_Exam_ID) ?>;
        document.addEventListener('DOMContentLoaded', () => { if(EDIT_EXAM_ID) loadExam(EDIT_EXAM_ID); });

        const CHIP_CLASS = {
            mcq: 'chip-mcq',
            match: 'chip-match',
            open: 'chip-open',
            fill: 'chip-fill',
            multiselect: 'chip-multiselect'
        };
        const CHIP_LABEL = {
            mcq: 'MCQ',
            match: 'Match',
            open: 'Open ended',
            fill: 'Fill up',
            multiselect: 'Multi Select'
        };

        // Helper to safely set math-field value preserving plain text spaces
        function setMathValue(el, val) {
            if (!el || val == null) return;
            const str = String(val);
            if (!str) {
                el.value = '';
                return;
            }
            // If it already contains LaTeX commands or braces, assume it's LaTeX
            if (str.includes('\\') || str.includes('_') || str.includes('^')) {
                el.value = str;
            } else {
                // Otherwise wrap in \text{} to preserve spaces in MathLive
                el.value = '\\text{' + str.replace(/[{}]/g, '') + '}';
            }
        }

        let currentType = 'mcq';
        let questionCounter = 0;
        let questions = [];
        let examId = null;
        let bankData = [];
        let bankTypeFilter = '';
        let bankDebounce = null;

        // Edit Mode variables
        let editExamId = null;
        let editQuestions = [];
        let editCurrentType = 'mcq';
        let editExamStatus = 'published';

        const qbankModal = new bootstrap.Modal(document.getElementById('qbankModal'));
        const toastEl = document.getElementById('ebToast');
        const bsToast = new bootstrap.Toast(toastEl, {
            delay: 2800
        });

        // ── Type pills ──────────────────────────────────────────────
        function setType(el, type) {
            document.querySelectorAll('.type-pill').forEach(p => p.classList.remove('active'));
            el.classList.add('active');
            currentType = type;
        }

        function setEditType(el, type) {
            document.querySelectorAll('.edit-type-pill').forEach(p => p.classList.remove('active'));
            if (el) el.classList.add('active');
            editCurrentType = type;
        }

        // ── Image attachment block (mcq / open / fill — NOT match) ────
        function getImageAttachmentBlock(qId) {
            return `
                <div class="mt-3 pt-2 border-top">
                    <label class="form-label small text-muted mb-1">Attach image (optional)</label>
                    <div class="image-upload rounded p-3 text-center text-muted" id="du_${qId}" role="button" tabindex="0">
                        <i class="ti ti-cloud-upload fs-3 d-block mb-1"></i>
                        <span class="small">Click to upload a reference image / diagram</span><br>
                        <span class="text-muted" style="font-size:11px">PNG, JPG, SVG, WebP — up to 5 MB</span>
                    </div>
                </div>`;
        }

        // ── Question templates ───────────────────────────────────────
        function getQuestionTemplate(type, qId) {
            if (type === 'mcq') return `
                <div class="col-12 row g-2">
                    <div class="col-12 d-flex justify-content-between align-items-center mt-1 mb-1">
                        <label class="form-label fw-bold text-dark mb-0 small"><i class="ti ti-list-check me-1 text-danger"></i>Answer Options & Correct Choice</label>
                        <small class="text-muted" style="font-size: 11px;">Select the radio to mark correct answer</small>
                    </div>
                    ${['A','B','C','D'].map((l, idx) => `
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 p-1 border rounded bg-white">
                            <input type="radio" name="correct_${qId}" value="${l}" class="form-check-input opt-radio mt-0 flex-shrink-0" ${idx === 0 ? 'checked' : ''} title="Mark Option ${l} as correct">
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle fw-bold">${l}</span>
                            <math-field class="opt-input flex-grow-1" id="qo_${qId}_${l}" default-mode="text" placeholder="Option ${l} formula / text…"></math-field>
                        </div>
                    </div>`).join('')}
                </div>
                ${getImageAttachmentBlock(qId)}`;

            if (type === 'multiselect') return `
                <div class="col-12 row g-2">
                    <div class="col-12 d-flex justify-content-between align-items-center mt-1 mb-1">
                        <label class="form-label fw-bold text-dark mb-0 small"><i class="ti ti-checkbox me-1 text-danger"></i>Answer Options & Correct Choices</label>
                        <small class="text-muted" style="font-size: 11px;">Select checkboxes to mark correct answers</small>
                    </div>
                    ${['A','B','C','D'].map(l => `
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 p-1 border rounded bg-white">
                            <input type="checkbox" name="correct_${qId}" value="${l}" class="opt-checkbox form-check-input mt-0 flex-shrink-0" title="Mark Option ${l} as correct">
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle fw-bold">${l}</span>
                            <math-field class="opt-input flex-grow-1" id="qo_${qId}_${l}" default-mode="text" placeholder="Option ${l} formula / text…"></math-field>
                        </div>
                    </div>`).join('')}
                </div>
                ${getImageAttachmentBlock(qId)}`;

            if (type === 'match') return `
                <div class="mt-2">
                    <label class="form-label fw-semibold small mb-1"><i class="ti ti-arrows-exchange me-1 text-danger"></i>Matching Pairs (MathLive Enabled)</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="text-center small fw-semibold rounded py-1 mb-2 bg-primary bg-opacity-10 text-primary">Column A (Premises)</div>
                            ${[1,2,3,4].map(i => `
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text fw-bold">${i}</span>
                                <math-field class="opt-input form-control" id="qa_${qId}_${i}" default-mode="text" placeholder="Premise ${i}"></math-field>
                            </div>`).join('')}
                        </div>
                        <div class="col-6">
                            <div class="text-center small fw-semibold rounded py-1 mb-2 bg-success bg-opacity-10 text-success">Column B (Matches)</div>
                            ${['a','b','c','d'].map((l) => `
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text fw-bold text-uppercase">${l}</span>
                                <math-field class="opt-input form-control" id="qb_${qId}_${l}" default-mode="text" placeholder="Match ${l.toUpperCase()}"></math-field>
                            </div>`).join('')}
                        </div>
                    </div>
                </div>`;

            if (type === 'open') return `
                <div class="mt-2">
                    <label class="form-label fw-semibold small mb-1"><i class="ti ti-notes me-1 text-danger"></i>Grading Rubric / Criteria (MathLive Enabled)</label>
                    <math-field class="open-rubric" id="mf_rubric_${qId}" default-mode="text" placeholder="Describe key points or marking scheme (e.g. 2 marks for definition, 3 marks for explanation)..." style="min-height: 60px;"></math-field>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <label class="form-label small text-muted mb-0">Word Limit (Optional):</label>
                        <input type="number" class="form-control form-control-sm word-limit-input" placeholder="e.g. 250" min="10" style="max-width: 140px;">
                    </div>
                    ${getImageAttachmentBlock(qId)}
                </div>`;

            if (type === 'fill') return `
                <div class="mt-2">
                    <div class="alert alert-light border py-2 px-3 small text-muted mb-2">
                        <i class="ti ti-info-circle me-1 text-primary"></i>Use <code class="bg-primary bg-opacity-10 text-primary rounded px-1">___</code> (three underscores) in the Question Statement above for each blank.
                    </div>
                    <div id="fp_${qId}" class="bg-light border rounded p-2 mb-2 small text-muted" style="display:none;line-height:2"></div>
                    <label class="form-label fw-semibold small mb-1">Accepted Answers (in order)</label>
                    <div id="fill_ans_${qId}" class="d-flex flex-column gap-2"></div>
                    ${getImageAttachmentBlock(qId)}
                </div>`;

            return '';
        }

        // ── Fill preview ─────────────────────────────────────────────
        function updateFillPreview(ta, qId) {
            if (!ta) return;
            const fp = document.getElementById('fp_' + qId);
            const ansDiv = document.getElementById('fill_ans_' + qId);
            if (!fp || !ansDiv) return;

            const val = ta.value;
            if (!val) {
                fp.style.display = 'none';
                return;
            }
            const blanks = (val.match(/___/g) || []).length;
            if (!blanks) {
                fp.style.display = 'none';
                ansDiv.innerHTML = '';
                return;
            }
            fp.style.display = 'block';
            fp.innerHTML = val.replace(/___/g, `<span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2">blank</span>`);
            const existing = ansDiv.querySelectorAll('input').length;
            if (existing < blanks) {
                for (let i = existing; i < blanks; i++) {
                    const inp = document.createElement('input');
                    inp.className = 'form-control form-control-sm';
                    inp.type = 'text';
                    inp.placeholder = `Answer for blank ${i + 1}`;
                    ansDiv.appendChild(inp);
                }
            } else {
                while (ansDiv.querySelectorAll('input').length > blanks)
                    ansDiv.removeChild(ansDiv.lastChild);
            }
        }

        // ── Add question ─────────────────────────────────────────────
        function addQuestion(type, text, extraData, isEdit = false) {
            questionCounter++;
            const qId = (isEdit ? 'eq' : 'q') + questionCounter;
            const qType = type || (isEdit ? editCurrentType : currentType);
            
            const qObj = {
                id: qId,
                type: qType
            };
            if (isEdit) {
                editQuestions.push(qObj);
            } else {
                questions.push(qObj);
            }

            const card = document.createElement('div');
            card.className = 'card border shadow-sm qn-card mb-3';
            card.id = 'card_' + qId;
            card.dataset.type = qType;
            card.dataset.qid = qId;

            card.innerHTML = `
                <div class="card-header bg-light d-flex align-items-center justify-content-between py-2 px-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger rounded-circle d-flex align-items-center justify-content-center qn-num" style="width:28px;height:28px;font-size:13px;font-weight:600">${isEdit ? editQuestions.length : questions.length}</span>
                        <span class="badge rounded-pill ${CHIP_CLASS[qType]} px-3 py-1 fw-semibold">${CHIP_LABEL[qType]}</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="moveQuestion('${qId}',-1, ${isEdit})" title="Move up">
                            <i class="ti ti-arrow-up"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="moveQuestion('${qId}',1, ${isEdit})" title="Move down">
                            <i class="ti ti-arrow-down"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteQuestion('${qId}', ${isEdit})" title="Delete">
                            <i class="ti ti-trash"></i>
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

                    <!-- Question Statement with MathLive & Quick Math -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold small mb-0">Question Statement <span class="text-danger">*</span></label>
                        </div>
                        <div class="math-field-wrapper position-relative">
                            <math-field id="qt_${qId}" default-mode="text" style="min-height: 60px;" placeholder="Enter question statement or type equations using the MathLive keyboard…"${qType === 'fill' ? ` oninput="updateFillPreview(this,'${qId}')"` : ''}></math-field>
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

            const targetListId = isEdit ? 'editQuestionList' : 'questionList';
            document.getElementById(targetListId).appendChild(card);

            if (qType === 'mcq' || qType === 'multiselect' || qType === 'open' || qType === 'fill') {
                const uploadArea = card.querySelector('#du_' + qId);
                if (uploadArea) {
                    const fi = document.createElement('input');
                    fi.type = 'file';
                    fi.accept = 'image/*,.svg';
                    fi.style.display = 'none';
                    fi.id = 'img_' + qId;
                    card.appendChild(fi);
                    uploadArea.onclick = (e) => {
                        e.stopPropagation();
                        fi.click();
                    };
                    uploadArea.onkeydown = (e) => {
                        if (e.key === 'Enter' || e.key === ' ') fi.click();
                    };
                    fi.onchange = (e) => handleImageUpload(e, qId, uploadArea);
                    if (extraData?.image_path) {
                        uploadArea.dataset.imagePath = extraData.image_path;
                        uploadArea.innerHTML = `<img src="${extraData.image_path}" class="img-fluid rounded" style="max-height:180px" alt="Attached image">`;
                    }
                }
            }

            const qt = document.getElementById('qt_' + qId);
            if (qt && text) {
                setMathValue(qt, text);
                if (qType === 'fill') updateFillPreview(qt, qId);
            }
            if (extraData) prefillQuestion(card, qType, qId, extraData);

            updateCount(isEdit);
            card.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }

        // ── Prefill from bank / server ───────────────────────────────
        function prefillQuestion(card, type, qId, data) {
            // Course Outcome
            const coVal = data.co !== undefined && data.co !== null ? data.co : (data.CO !== undefined ? data.CO : null);
            if (coVal !== null) {
                const coEl = document.getElementById('co_' + qId);
                if (coEl) coEl.value = coVal;
            }
            // Bloom's Level
            const bloomVal = data.bloom_level || data.Bloom_Level;
            if (bloomVal) {
                const bEl = document.getElementById('bloom_' + qId);
                if (bEl) bEl.value = bloomVal;
            }
            // Marks
            const marksVal = data.marks !== undefined && data.marks !== null ? data.marks : (data.Marks !== undefined ? data.Marks : null);
            if (marksVal !== null) {
                const mEl = document.getElementById('marks_' + qId);
                if (mEl) mEl.value = marksVal;
            }

            if (type === 'mcq' && Array.isArray(data.options)) {
                data.options.forEach(opt => {
                    const el = document.getElementById(`qo_${qId}_${opt.letter}`);
                    if (el) setMathValue(el, opt.text || '');
                });
                if (data.correct_opt) {
                    const radio = card.querySelector(`input[name="correct_${qId}"][value="${data.correct_opt}"]`);
                    if (radio) radio.checked = true;
                }
            }
            if (type === 'multiselect' && Array.isArray(data.options)) {
                data.options.forEach(opt => {
                    const el = document.getElementById(`qo_${qId}_${opt.letter}`);
                    if (el) setMathValue(el, opt.text || '');
                });
                if (data.answers) {
                    let correctOpts = [];
                    if (Array.isArray(data.answers)) {
                        correctOpts = data.answers;
                    } else if (typeof data.answers === 'string') {
                        try {
                            const parsed = JSON.parse(data.answers);
                            if (Array.isArray(parsed)) correctOpts = parsed;
                            else correctOpts = data.answers.split(',');
                        } catch (e) {
                            correctOpts = data.answers.split(',');
                        }
                    }

                    card.querySelectorAll('.opt-checkbox').forEach(cb => cb.checked = false);
                    correctOpts.forEach(c => {
                        const val = String(c).trim();
                        const cb = card.querySelector(`input[name="correct_${qId}"][value="${val}"]`);
                        if (cb) cb.checked = true;
                    });
                }
            }
            if (type === 'match' && Array.isArray(data.pairs)) {
                const cols = ['a', 'b', 'c', 'd'];
                data.pairs.forEach((pair, i) => {
                    const aEl = document.getElementById(`qa_${qId}_${i + 1}`);
                    const bEl = document.getElementById(`qb_${qId}_${cols[i]}`);
                    if (aEl) setMathValue(aEl, pair.a || pair.left || '');
                    if (bEl) setMathValue(bEl, pair.b || pair.right || '');
                });
            }
            if (type === 'open') {
                const r = card.querySelector('.open-rubric');
                const wl = card.querySelector('.word-limit-input');
                if (r && data.rubric) setMathValue(r, data.rubric);
                if (wl && data.word_limit) wl.value = data.word_limit;
            }
            if (type === 'fill' && Array.isArray(data.answers)) {
                setTimeout(() => {
                    const inputs = document.getElementById('fill_ans_' + qId)?.querySelectorAll('input') || [];
                    data.answers.forEach((ans, i) => {
                        if (inputs[i]) inputs[i].value = ans;
                    });
                }, 150);
            }
            // Image attachment applies to mcq / open / fill (not match)
            if (type !== 'match' && data.image_path) {
                const uploadArea = card.querySelector('#du_' + qId);
                if (uploadArea) {
                    uploadArea.dataset.imagePath = data.image_path;
                    uploadArea.innerHTML = `<img src="${data.image_path}" class="img-fluid rounded" style="max-height:180px" alt="Attached image">`;
                }
            }
        }

        // ── Image attachment upload ────────────────────────────────────
        async function handleImageUpload(e, qId, uploadArea) {
            const file = e.target.files[0];
            if (!file) return;
            uploadArea.innerHTML = `<div class="spinner-border spinner-border-sm text-primary me-2"></div>Uploading…`;
            const fd = new FormData();
            fd.append('image', file);
            try {
                const res = await fetch(`${API}?action=upload_image`, {
                    method: 'POST',
                    body: fd
                });
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
        function deleteQuestion(qId, isEdit = false) {
            const arr = isEdit ? editQuestions : questions;
            const idx = arr.findIndex(q => q.id === qId);
            if (idx > -1) arr.splice(idx, 1);
            const card = document.getElementById('card_' + qId);
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'scale(.97)';
                setTimeout(() => card.remove(), 200);
            }
            setTimeout(() => updateCount(isEdit), 220);
        }

        function moveQuestion(qId, dir, isEdit = false) {
            const listId = isEdit ? 'editQuestionList' : 'questionList';
            const list = document.getElementById(listId);
            const card = document.getElementById('card_' + qId);
            if (dir === -1 && card.previousElementSibling) list.insertBefore(card, card.previousElementSibling);
            if (dir === 1 && card.nextElementSibling) list.insertBefore(card.nextElementSibling, card);
            updateCount(isEdit);
        }

        function updateCount(isEdit = false) {
            const containerId = isEdit ? 'editQuestionList' : 'questionList';
            const badgeId = isEdit ? 'editQnCountBadge' : 'qnCountBadge';
            const cards = document.querySelectorAll(`#${containerId} .qn-card`);
            cards.forEach((c, i) => {
                const n = c.querySelector('.qn-num');
                if (n) n.textContent = i + 1;
            });
            const cnt = cards.length;
            const badge = document.getElementById(badgeId);
            if (badge) badge.textContent = cnt + ' question' + (cnt !== 1 ? 's' : '');
        }

        // ── Serialize ────────────────────────────────────────────────
        function serializeQuestion(card) {
            const type = card.dataset.type;
            const qId = card.dataset.qid;
            const qtEl = document.getElementById('qt_' + qId);
            const questionText = qtEl ? (qtEl.value || '') : '';
            const marks = parseFloat(document.getElementById('marks_' + qId)?.value || 1);
            const co = parseInt(document.getElementById('co_' + qId)?.value || 1, 10);
            const bloomLevel = document.getElementById('bloom_' + qId)?.value || 'Understand';
            const q = {
                type,
                question_text: questionText,
                marks,
                co,
                bloom_level: bloomLevel
            };

            if (type === 'mcq') {
                const opts = [];
                let correctOpt = null;
                const letters = ['A', 'B', 'C', 'D'];
                letters.forEach(l => {
                    const el = document.getElementById(`qo_${qId}_${l}`);
                    if (el) {
                        opts.push({
                            letter: l,
                            text: el.value || ''
                        });
                    }
                });
                const checkedRadio = card.querySelector(`input[name="correct_${qId}"]:checked`);
                if (checkedRadio) correctOpt = checkedRadio.value;
                q.options = opts;
                q.correct_opt = correctOpt;
            }
            if (type === 'multiselect') {
                const opts = [];
                let correctOpts = [];
                const letters = ['A', 'B', 'C', 'D'];
                letters.forEach(l => {
                    const el = document.getElementById(`qo_${qId}_${l}`);
                    if (el) {
                        opts.push({
                            letter: l,
                            text: el.value || ''
                        });
                    }
                });
                card.querySelectorAll(`input[name="correct_${qId}"]:checked`).forEach(cb => {
                    correctOpts.push(cb.value);
                });
                q.options = opts;
                q.answers = correctOpts;
            }
            if (type === 'open') {
                q.rubric = card.querySelector('.open-rubric')?.value || null;
                const wl = card.querySelector('.word-limit-input')?.value;
                q.word_limit = wl ? parseInt(wl, 10) : null;
            }
            if (type === 'fill') {
                const ansDiv = document.getElementById('fill_ans_' + qId);
                q.answers = ansDiv ? Array.from(ansDiv.querySelectorAll('input')).map(i => i.value) : [];
            }
            if (type === 'match') {
                const pairs = [];
                const cols = ['a', 'b', 'c', 'd'];
                [1, 2, 3, 4].forEach((num, idx) => {
                    const aEl = document.getElementById(`qa_${qId}_${num}`);
                    const bEl = document.getElementById(`qb_${qId}_${cols[idx]}`);
                    if (aEl || bEl) {
                        pairs.push({
                            a: aEl ? aEl.value || '' : '',
                            b: bEl ? bEl.value || '' : ''
                        });
                    }
                });
                q.pairs = pairs;
            }
            // Optional image attachment — available on mcq / open / fill (not match)
            if (type !== 'match') {
                const du = document.getElementById('du_' + qId);
                q.image_path = du?.dataset.imagePath || null;
            }
            return q;
        }

        // ── Validate ─────────────────────────────────────────────────
        function validateExam(isEdit = false) {
            const nameEl = isEdit ? document.getElementById('editExamName') : document.getElementById('examName');
            const durationEl = isEdit ? document.getElementById('editExamDuration') : document.getElementById('examDuration');
            if (!nameEl || !nameEl.value.trim()) {
                showToast('Please enter an exam name', 'danger');
                return false;
            }
            if (!durationEl || !durationEl.value.trim()) {
                showToast('Please enter the duration', 'danger');
                return false;
            }
            const containerId = isEdit ? 'editQuestionList' : 'questionList';
            const cards = document.querySelectorAll(`#${containerId} .qn-card`);
            if (!cards.length) {
                showToast('Add at least one question', 'danger');
                return false;
            }
            for (const card of cards) {
                const qId = card.dataset.qid;
                const qt = document.getElementById('qt_' + qId);
                if (!qt?.value?.trim()) {
                    card.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    showToast('All questions must have question text', 'danger');
                    return false;
                }
                if (card.dataset.type === 'mcq' || card.dataset.type === 'multiselect') {
                    const filled = ['A', 'B', 'C', 'D'].map(l => document.getElementById(`qo_${qId}_${l}`)?.value?.trim() || '').filter(Boolean).length;
                    if (filled < 2) {
                        card.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        showToast((card.dataset.type === 'mcq' ? 'MCQ' : 'Multi Select') + ' needs at least 2 options', 'danger');
                        return false;
                    }
                }
            }
            return true;
        }

        // ── Save exam ────────────────────────────────────────────────
        async function saveExam(status, isEdit = false) {
            if (!validateExam(isEdit)) return;
            const containerId = isEdit ? 'editQuestionList' : 'questionList';
            const cards = document.querySelectorAll(`#${containerId} .qn-card`);
            
            const nameEl = isEdit ? document.getElementById('editExamName') : document.getElementById('examName');
            const durationEl = isEdit ? document.getElementById('editExamDuration') : document.getElementById('examDuration');
            const startTimeEl = isEdit ? document.getElementById('editStartTime') : document.getElementById('startTime');
            const endTimeEl = isEdit ? document.getElementById('editEndTime') : document.getElementById('endTime');

            const payload = {
                course_id: COURSE_ID,
                fac_id: FAC_ID,
                acad_year: Acad_Year,
                name: nameEl.value.trim(),
                duration: parseInt(durationEl.value, 10),
                start_time: startTimeEl.value || null,
                end_time: endTimeEl.value || null,
                status,
                questions: Array.from(cards).map((c, i) => ({
                    ...serializeQuestion(c),
                    sort_order: i
                })),
            };
            const currentExamId = isEdit ? editExamId : examId;
            if (currentExamId) payload.exam_id = currentExamId;
            setSavingState(true, isEdit);
            try {
                const action = currentExamId ? 'update_exam' : 'save_exam';
                const res = await fetch(`${API}?action=${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    if (isEdit) {
                        showToast('Exam updated successfully!', 'success');
                        // Update dropdown name option text if it changed
                        const selectEl = document.getElementById('Exam_ID');
                        if (selectEl && selectEl.selectedIndex >= 0) {
                            const opt = selectEl.options[selectEl.selectedIndex];
                            if (opt) opt.textContent = payload.name;
                        }
                    } else {
                        examId = data.exam_id;
                        showToast(status === 'published' ? `Exam published! (ID ${examId})` : `Draft saved (ID ${examId})`, 'success');
                    }
                } else {
                    showToast('Error: ' + (data.message || 'Unknown error'), 'danger');
                }
            } catch {
                showToast('Network error — please try again', 'danger');
            } finally {
                setSavingState(false, isEdit);
            }
        }

        function setSavingState(active, isEdit = false) {
            document.getElementById('savingOverlay').classList.toggle('show', active);
            if (isEdit) {
                const btn = document.getElementById('btnUpdateExam');
                if (btn) btn.disabled = active;
            } else {
                const btnDraft = document.getElementById('btnDraft');
                const btnPublish = document.getElementById('btnPublish');
                if (btnDraft) btnDraft.disabled = active;
                if (btnPublish) btnPublish.disabled = active;
            }
        }

        // ── Question bank ────────────────────────────────────────────
        async function openQBank(isEdit = false) {
            bankData = [];
            renderBankList([]);
            qbankModal.show();
            document.getElementById('qbankModal').dataset.isEdit = isEdit ? 'true' : 'false';
            const currentExamId = isEdit ? editExamId : examId;
            await loadBankQuestions('', bankTypeFilter, currentExamId);
        }

        function setBankTypeFilter(el, type) {
            document.querySelectorAll('.type-filter-chip').forEach(c => {
                c.classList.remove('btn-primary');
                c.classList.add('btn-outline-secondary');
            });
            el.classList.remove('btn-outline-secondary');
            el.classList.add('btn-primary');
            bankTypeFilter = type;
            const isEdit = document.getElementById('qbankModal').dataset.isEdit === 'true';
            const currentExamId = isEdit ? editExamId : examId;
            loadBankQuestions(document.getElementById('bankSearchInput').value.trim(), type, currentExamId);
        }

        async function loadBankQuestions(search, type, currentExamId) {
            const list = document.getElementById('qbankList');
            list.innerHTML = '<p class="text-center text-muted py-4 mb-0">Loading…</p>';
            const params = new URLSearchParams({
                action: 'bank_questions'
            });
            if (search) params.set('search', search);
            if (type) params.set('type', type);
            if (currentExamId) params.set('exclude_exam', currentExamId);
            if (COURSE_ID) params.set('course_id', COURSE_ID);
            try {
                const res = await fetch(`${API}?${params}`);
                const data = await res.json();
                if (!data.success) {
                    list.innerHTML = `<p class="text-center text-danger py-4 mb-0">${data.message}</p>`;
                    return;
                }
                bankData = data.questions;
                renderBankList(bankData);
            } catch {
                list.innerHTML = '<p class="text-center text-danger py-4 mb-0">Network error</p>';
            }
        }

        function renderBankList(qs) {
            const list = document.getElementById('qbankList');
            if (!qs.length) {
                list.innerHTML = '<p class="text-center text-muted py-4 mb-0">No questions found</p>';
                return;
            }
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
            if (!checked.length) {
                showToast('Select at least one question', 'danger');
                return;
            }
            const isEdit = document.getElementById('qbankModal').dataset.isEdit === 'true';
            checked.forEach(cb => {
                const q = bankData[parseInt(cb.value, 10)];
                if (q) addQuestion(q.type, q.question_text, q, isEdit);
            });
            qbankModal.hide();
            showToast(`${checked.length} question${checked.length > 1 ? 's' : ''} imported`, 'success');
        }

        document.getElementById('bankSearchInput').addEventListener('input', (e) => {
            const isEdit = document.getElementById('qbankModal').dataset.isEdit === 'true';
            const currentExamId = isEdit ? editExamId : examId;
            clearTimeout(bankDebounce);
            bankDebounce = setTimeout(() => loadBankQuestions(e.target.value.trim(), bankTypeFilter, currentExamId), 350);
        });

        // ── Load Exam listener and functions ─────────────────────────
        document.getElementById('Exam_ID').addEventListener('change', function() {
            const val = this.value;
            if (val) {
                loadExam(val);
            }
        });

        async function loadExam(id) {
            setSavingState(true, true);
            try {
                const res = await fetch(`${API}?action=get_exam&exam_id=${id}`);
                const data = await res.json();
                if (data.success) {
                    editExamId = data.exam.exam_id;
                    editExamStatus = data.exam.status;
                    document.getElementById('editExamName').value = data.exam.name;
                    document.getElementById('editExamDuration').value = data.exam.duration;
                    document.getElementById('editStartTime').value = data.exam.start_time;
                    document.getElementById('editEndTime').value = data.exam.end_time;
                    
                    // Clear current questions on edit list
                    document.getElementById('editQuestionList').innerHTML = '';
                    editQuestions = [];
                    
                    // Reset edit current type pill
                    setEditType(document.querySelector('.edit-type-pill[data-type="mcq"]'), 'mcq');
                    
                    // Add questions
                    if (data.questions && data.questions.length > 0) {
                        data.questions.forEach(q => {
                            addQuestion(q.type, q.question_text, q, true);
                        });
                    }
                } else {
                    showToast('Failed to load exam details: ' + data.message, 'danger');
                }
            } catch (err) {
                showToast('Network error while loading exam', 'danger');
            } finally {
                setSavingState(false, true);
            }
        }

        // ── Toast ────────────────────────────────────────────────────
        function showToast(msg, type = 'success') {
            toastEl.className = `toast align-items-center border-0 position-fixed bottom-0 end-0 m-3 text-bg-${type}`;
            document.getElementById('ebToastMsg').textContent = msg;
            bsToast.show();
        }

        function downloadQP() {
            const examSelect = document.getElementById('Exam_ID');
            const examId = examSelect ? examSelect.value : (editExamId || EDIT_EXAM_ID || '');
            if (!examId) {
                showToast('Please select an exam first.', 'danger');
                return;
            }
            window.location.href = 'download-qp.php?Exam_ID=' + encodeURIComponent(examId);
        }

        function escHtml(str) {
            return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
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