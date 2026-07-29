<?php
session_start();
if (!$_SESSION["LoginFaculty"]) {
    echo '<script>alert("You Are Not An Authorized Person For This Link");</script>';
    echo '<script>window.location="../Login/Login.php"</script>';
}

require_once "../Connection/connection.php";

// $con = mysqli_connect("localhost", "root", "", "exam_db");

$Fac_ID    = $_SESSION['LoginFaculty'];
$Course_ID = $_GET['Course_ID'] ?? "123";

$q   = mysqli_query($con, "SELECT * FROM `faculty` WHERE `Fac_ID` = '$Fac_ID'");
$row = mysqli_fetch_array($q);

$cq     = mysqli_query($con, "SELECT * FROM `courses` WHERE `Course_ID` = '$Course_ID'");
$course = mysqli_fetch_array($cq);
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
    <link rel="stylesheet" href="../Common/style.css">

    <style>
        /* Only what Bootstrap cannot do */
        .type-pill.active {
            background: #e7f1ff;
            border-color: #0d6efd;
            color: #0a58ca;
            font-weight: 500;
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

        .image-upload {
            border: 2px dashed #dee2e6;
            cursor: pointer;
            transition: .15s;
        }

        .image-upload:hover {
            border-color: #0d6efd;
            background: #f0f4ff;
        }

        math-field {
            width: 100%;
            min-height: 42px;
            border: 1px solid #dee2e6;
            border-radius: .375rem;
            padding: 6px 10px;
            font-size: 14px;
        }

        .qn-card {
            transition: opacity .2s, transform .2s;
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
                <i class="ti ti-clipboard-plus me-2"></i>Question Bank
                <?php if ($Course_Name): ?>
                    <small class="fw-normal opacity-75 ms-2">
                        <?= htmlspecialchars($Course_Name) ?> (<?= htmlspecialchars($Course_Code) ?>)
                    </small>
                <?php endif; ?>
            </h4>
        </div>
        <nav>
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true">Add Questions</button>
                <button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">View Question Bank</button>
            </div>
        </nav>
        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active mt-3" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab" tabindex="0">
                <!-- Upload Methods Card -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom py-2">
                        <span class="text-uppercase small fw-semibold text-muted">Choose Upload Method</span>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-pills mb-3" id="methodTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="manual-tab" data-bs-toggle="pill" data-bs-target="#manual-pane" type="button" role="tab" aria-controls="manual-pane" aria-selected="true">
                                    Manually
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="csv-tab" data-bs-toggle="pill" data-bs-target="#csv-pane" type="button" role="tab" aria-controls="csv-pane" aria-selected="false">
                                    Bulk Upload
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content" id="methodTabContent">
                            <!-- Manual Pane -->
                            <div class="tab-pane fade show active" id="manual-pane" role="tabpanel" aria-labelledby="manual-tab">
                                <label class="form-label small text-muted">Select Question Type</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary type-pill active" data-type="mcq" onclick="setType(this,'mcq')">
                                    MCQ
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
                            
                            <!-- CSV Pane -->
                            <div class="tab-pane fade" id="csv-pane" role="tabpanel" aria-labelledby="csv-tab">
                                <form id="csvUploadForm" onsubmit="handleCsvSubmit(event)">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted">Question Type for CSV</label>
                                            <select class="form-select form-select" id="csvQuestionType" onchange="updateCsvInstructions()" required>
                                                <option value="mcq">MCQ</option>
                                                <option value="match">Match the following</option>
                                                <option value="open">Open ended</option>
                                                <option value="fill">Fill in the blanks</option>
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted">Select CSV File</label>
                                            <input type="file" class="form-control form-control" id="csvFile" accept=".csv" required>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-success w-100" id="btnUploadCsv">
                                                Import CSV
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-3 p-3 bg-light rounded border border-dashed">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 fw-semibold text-muted small">CSV Format Instructions</h6>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 11px;" onclick="downloadCsvTemplate()">
                                                <i class="ti ti-download me-1"></i>Download Template
                                            </button>
                                        </div>
                                        <div id="csvInstructions" class="small text-muted mb-0">
                                            <!-- Will be populated dynamically by JS -->
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Questions card -->
                <div class="card border-0 shadow-sm" id="questionsCard">
                    <div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-uppercase small fw-semibold text-muted">Questions</span>
                            <span class="badge bg-light text-secondary border" id="qnCountBadge">0 questions</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="questionList"></div>

                        <div class="d-flex align-items-center gap-2 mt-2">
                            <hr class="flex-grow-1 m-0">
                            <button type="button" class="btn btn-success btn-sm" onclick="addQuestion()">
                                Add question
                            </button>
                            <hr class="flex-grow-1 m-0">
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button class="btn btn-primary" id="btnPublish" onclick="saveToBank()">
                        Upload Questions
                    </button>
                </div>
                    
            </div>
            <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab" tabindex="0">
                <!-- Question Search Card -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom py-2">
                        <span class="text-uppercase small fw-semibold text-muted">Exam Search</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Exam Name <span class="text-danger">*</span></label>
                                    <select class="form-control" name="Exam_ID" id="Exam_ID" required>
                                        <option value="" selected disabled>Select exam name</option>
                                        <?php
                                            $q=mysqli_query($con,"select * from new_question_bank where Fac_ID='$Fac_ID' and Course_ID='$Course_ID'");
                                            while($row=mysqli_fetch_array($q)){
                                                echo "<option value='".$row['Exam_ID']."'>".$row['Question_Text']."</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>    
            </div>
        </div>

        <div class="sub-main pb-5">

            <!-- Top action bar -->
            <!-- <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small">
                            <li class="breadcrumb-item text-muted">Exams</li>
                            <li class="breadcrumb-item active">Create New Exam</li>
                        </ol>
                    </nav>
                    <h5 class="mb-0 fw-semibold">Add Questions</h5>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-info" onclick="openExamList()">
                        View Question Bank
                    </button>
                    <button class="btn btn-outline-secondary" id="btnDraft" onclick="saveExam('draft')">
                        Save draft
                    </button>
                    <button class="btn btn-primary" id="btnPublish" onclick="saveToBank()">
                        Upload Questions
                    </button>
                </div>
            </div> -->

            
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
        const FAC_ID = <?= json_encode($Fac_ID) ?>;
        const API = 'api.php';

        const CHIP_CLASS = {
            mcq: 'chip-mcq',
            match: 'chip-match',
            open: 'chip-open',
            fill: 'chip-fill'
        };
        const CHIP_LABEL = {
            mcq: 'MCQ',
            match: 'Match',
            open: 'Open ended',
            fill: 'Fill up'
        };

        let currentType = 'mcq';
        let questionCounter = 0;
        let questions = [];
        let examId = null;
        let bankData = [];
        let bankTypeFilter = '';
        let bankDebounce = null;

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
                <math-field id="qt_${qId}" placeholder="Enter your question here…"></math-field>
                <div class="mt-2 d-flex flex-column gap-2">
                    ${['A','B','C','D'].map(l => `
                    <div class="d-flex align-items-center gap-2">
                        <input type="radio" name="correct_${qId}" class="opt-radio form-check-input mt-0 flex-shrink-0">
                        <span class="badge bg-light text-dark border">${l}</span>
                        <math-field class="opt-input flex-grow-1" id="qo_${qId}_${l}" placeholder="Option ${l}"></math-field>
                    </div>`).join('')}
                </div>
                <p class="text-muted small mt-2 mb-0"><i class="ti ti-info-circle me-1"></i>Select the radio button to mark the correct answer</p>
                ${getImageAttachmentBlock(qId)}`;

            if (type === 'match') return `
                <math-field id="qt_${qId}" placeholder="Match the following items…"></math-field>
                <div class="row g-2 mt-1">
                    <div class="col-6">
                        <div class="text-center small fw-semibold rounded py-1 mb-2 bg-primary bg-opacity-10 text-primary">Column A</div>
                        ${[1,2,3,4].map(i => `
                        <div class="input-group input-group-sm mb-1">
                            <span class="input-group-text">${i}</span>
                            <math-field class="opt-input form-control" id="qa_${qId}_${i}" placeholder="Item ${i}"></math-field>
                        </div>`).join('')}
                    </div>
                    <div class="col-6">
                        <div class="text-center small fw-semibold rounded py-1 mb-2 bg-success bg-opacity-10 text-success">Column B</div>
                        ${['a','b','c','d'].map(l => `
                        <div class="input-group input-group-sm mb-1">
                            <span class="input-group-text">${l}</span>
                            <math-field class="opt-input form-control" id="qb_${qId}_${l}" placeholder="Match ${l}"></math-field>
                        </div>`).join('')}
                    </div>
                </div>`;

            if (type === 'open') return `
                <math-field id="qt_${qId}" placeholder="Enter your open-ended question…" style="min-height:80px"></math-field>
                <div class="mt-2">
                    <label class="form-label small text-muted">Rubric / marking scheme</label>
                    <math-field class="open-rubric" placeholder="e.g. 2 marks for definition, 3 marks for explanation…"></math-field>
                </div>
                <div class="d-flex align-items-center gap-2 mt-2">
                    <label class="form-label small text-muted mb-0">Word limit</label>
                    <input type="number" class="form-control form-control-sm word-limit-input" placeholder="No limit" min="0" style="width:110px">
                </div>
                ${getImageAttachmentBlock(qId)}`;

            if (type === 'fill') return `
                <p class="small text-muted mb-1">
                    Write the sentence and use
                    <code class="bg-primary bg-opacity-10 text-primary rounded px-1">___</code>
                    for each blank
                </p>
                <math-field id="qt_${qId}"
                    placeholder="e.g. The chemical formula for water is ___ and it consists of ___ atoms."
                    oninput="updateFillPreview(this,'${qId}')"></math-field>
                <div id="fp_${qId}" class="bg-light border rounded p-2 mt-2 small text-muted" style="display:none;line-height:2"></div>
                <div class="mt-2">
                    <label class="form-label small text-muted">Answers (in order)</label>
                    <div id="fill_ans_${qId}" class="d-flex flex-column gap-2"></div>
                </div>
                ${getImageAttachmentBlock(qId)}`;

            return '';
        }

        // ── Fill preview ─────────────────────────────────────────────
        function updateFillPreview(ta, qId) {
            const val = ta.value;
            const fp = document.getElementById('fp_' + qId);
            const ansDiv = document.getElementById('fill_ans_' + qId);
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
        function addQuestion(type, text, extraData) {
            questionCounter++;
            const qId = 'q' + questionCounter;
            const qType = type || currentType;
            questions.push({
                id: qId,
                type: qType
            });

            const card = document.createElement('div');
            card.className = 'card border shadow-sm qn-card mb-2';
            card.id = 'card_' + qId;
            card.dataset.type = qType;
            card.dataset.qid = qId;

            card.innerHTML = `
                <div class="card-body">
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <span class="badge bg-secondary rounded-circle d-flex align-items-center justify-content-center qn-num" style="width:26px;height:26px;font-size:12px">${questions.length}</span>
                        <span class="badge rounded-pill ${CHIP_CLASS[qType]}">${CHIP_LABEL[qType]}</span>
                        <div class="ms-auto d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0" onclick="moveQuestion('${qId}',-1)" title="Move up">
                                <i class="ti ti-arrow-up"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0" onclick="moveQuestion('${qId}',1)" title="Move down">
                                <i class="ti ti-arrow-down"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="deleteQuestion('${qId}')" title="Delete">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="qn-body">${getQuestionTemplate(qType, qId)}</div>
                    <div class="d-flex align-items-center gap-2 mt-3 pt-2 border-top">
                        <label class="form-label small text-muted mb-0">Marks</label>
                        <input type="number" id="marks_${qId}" class="form-control form-control-sm" value="${extraData?.marks ?? 1}" min="0" step="0.5" style="width:70px">
                        <label class="form-label small text-muted mb-0">CO</label>
                        <input type="number" id="co_${qId}" class="form-control form-control-sm" value="${extraData?.co ?? 1}" min="1" step="1" style="width:70px">
                    </div>
                </div>`;

            document.getElementById('questionList').appendChild(card);

            if (qType === 'mcq' || qType === 'open' || qType === 'fill') {
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
            if (qt && text) qt.value = text;
            if (extraData) prefillQuestion(card, qType, qId, extraData);

            updateCount();
            card.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }

        // ── Prefill from bank ────────────────────────────────────────
        function prefillQuestion(card, type, qId, data) {
            if (type === 'mcq' && Array.isArray(data.options)) {
                data.options.forEach(opt => {
                    const el = document.getElementById(`qo_${qId}_${opt.letter}`);
                    if (el) el.value = opt.text || '';
                });
                if (data.correct_opt) {
                    const radios = card.querySelectorAll('.opt-radio');
                    const letters = ['A', 'B', 'C', 'D'];
                    const idx = letters.indexOf(data.correct_opt);
                    if (idx >= 0 && radios[idx]) radios[idx].checked = true;
                }
            }
            if (type === 'match' && Array.isArray(data.pairs)) {
                const cols = ['a', 'b', 'c', 'd'];
                data.pairs.forEach((pair, i) => {
                    const aEl = document.getElementById(`qa_${qId}_${i + 1}`);
                    const bEl = document.getElementById(`qb_${qId}_${cols[i]}`);
                    if (aEl) aEl.value = pair.a || '';
                    if (bEl) bEl.value = pair.b || '';
                });
            }
            if (type === 'open') {
                const r = card.querySelector('.open-rubric');
                const wl = card.querySelector('.word-limit-input');
                if (r && data.rubric) r.value = data.rubric;
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
            // CO applies to every question type
            if (data.co !== undefined && data.co !== null) {
                const coEl = document.getElementById('co_' + qId);
                if (coEl) coEl.value = data.co;
            }
            // Image attachment applies to mcq / open / fill (not match) —
            // already handled at upload-area creation time in addQuestion(),
            // this covers the case where prefillQuestion runs after that.
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
        function deleteQuestion(qId) {
            const idx = questions.findIndex(q => q.id === qId);
            if (idx > -1) questions.splice(idx, 1);
            const card = document.getElementById('card_' + qId);
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'scale(.97)';
                setTimeout(() => card.remove(), 200);
            }
            setTimeout(updateCount, 220);
        }

        function moveQuestion(qId, dir) {
            const list = document.getElementById('questionList');
            const card = document.getElementById('card_' + qId);
            if (dir === -1 && card.previousElementSibling) list.insertBefore(card, card.previousElementSibling);
            if (dir === 1 && card.nextElementSibling) list.insertBefore(card.nextElementSibling, card);
            updateCount();
        }

        function updateCount() {
            const cards = document.querySelectorAll('.qn-card');
            cards.forEach((c, i) => {
                const n = c.querySelector('.qn-num');
                if (n) n.textContent = i + 1;
            });
            const cnt = cards.length;
            document.getElementById('qnCountBadge').textContent = cnt + ' question' + (cnt !== 1 ? 's' : '');
        }

        // ── Serialize ────────────────────────────────────────────────
        function serializeQuestion(card) {
            const type = card.dataset.type;
            const qId = card.dataset.qid;
            const qtEl = document.getElementById('qt_' + qId);
            const questionText = qtEl ? (qtEl.value || '') : '';
            const marks = parseFloat(document.getElementById('marks_' + qId)?.value || 1);
            const co = parseInt(document.getElementById('co_' + qId)?.value || 1, 10);
            const q = {
                type,
                question_text: questionText,
                marks,
                co
            };

            if (type === 'mcq') {
                const opts = [];
                let correctOpt = null;
                const letters = ['A', 'B', 'C', 'D'];
                card.querySelectorAll('.d-flex.align-items-center.gap-2').forEach((row, i) => {
                    const radio = row.querySelector('.opt-radio');
                    const input = row.querySelector('.opt-input');
                    if (!input) return;
                    opts.push({
                        letter: letters[i],
                        text: input.value || ''
                    });
                    if (radio?.checked) correctOpt = letters[i];
                });
                q.options = opts;
                q.correct_opt = correctOpt;
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
                const aEls = card.querySelectorAll('[id^="qa_"]');
                const bEls = card.querySelectorAll('[id^="qb_"]');
                q.pairs = Array.from({
                    length: Math.max(aEls.length, bEls.length)
                }, (_, i) => ({
                    a: aEls[i]?.value || '',
                    b: bEls[i]?.value || ''
                }));
            }
            // Optional image attachment — available on mcq / open / fill (not match)
            if (type !== 'match') {
                const du = document.getElementById('du_' + qId);
                q.image_path = du?.dataset.imagePath || null;
            }
            return q;
        }

        // ── Save manual questions to bank ────────────────────────────
        async function saveToBank() {
            const cards = document.querySelectorAll('.qn-card');
            if (!cards.length) {
                showToast('Please add at least one question', 'danger');
                return;
            }
            
            // Validate each question
            for (const card of cards) {
                const qt = document.getElementById('qt_' + card.dataset.qid);
                if (!qt?.value?.trim()) {
                    card.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    showToast('All questions must have question text', 'danger');
                    return;
                }
                if (card.dataset.type === 'mcq') {
                    const filled = Array.from(card.querySelectorAll('.opt-input')).filter(i => i.value.trim()).length;
                    if (filled < 2) {
                        card.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        showToast('MCQ needs at least 2 options', 'danger');
                        return;
                    }
                    const checked = card.querySelector('.opt-radio:checked');
                    if (!checked) {
                        card.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        showToast('Please select a correct answer for MCQ', 'danger');
                        return;
                    }
                }
            }
            
            const payload = {
                course_id: COURSE_ID,
                questions: Array.from(cards).map((c, i) => serializeQuestion(c))
            };
            
            setSavingState(true);
            try {
                const res = await fetch(`${API}?action=save_bank_questions`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message || 'Questions uploaded to bank successfully!', 'success');
                    // Clear the list of questions on success
                    document.getElementById('questionList').innerHTML = '';
                    questions = [];
                    questionCounter = 0;
                    updateCount();
                } else {
                    showToast('Error: ' + (data.message || 'Save failed'), 'danger');
                }
            } catch {
                showToast('Network error — please try again', 'danger');
            } finally {
                setSavingState(false);
            }
        }

        function setSavingState(active) {
            document.getElementById('savingOverlay').classList.toggle('show', active);
            const btnPublish = document.getElementById('btnPublish');
            if (btnPublish) btnPublish.disabled = active;
            const btnUploadCsv = document.getElementById('btnUploadCsv');
            if (btnUploadCsv) btnUploadCsv.disabled = active;
        }

        // ── CSV Import functions ──────────────────────────────────────
        function updateCsvInstructions() {
            const type = document.getElementById('csvQuestionType').value;
            const instEl = document.getElementById('csvInstructions');
            let content = '';
            
            if (type === 'mcq') {
                content = `
                    <p class="mb-1"><strong>Required Columns (in order):</strong></p>
                    <ol class="mb-2 ps-3">
                        <li><code>Question Text</code> (Required)</li>
                        <li><code>Option A</code> (Required)</li>
                        <li><code>Option B</code> (Required)</li>
                        <li><code>Option C</code> (Required)</li>
                        <li><code>Option D</code> (Required)</li>
                        <li><code>Correct Option</code> (Required: A, B, C, or D)</li>
                        <li><code>Marks</code> (Optional, defaults to 1)</li>
                        <li><code>CO</code> (Optional Course Outcome, defaults to 1)</li>
                    </ol>
                    <p class="mb-0 text-secondary" style="font-size:11px;">Example Row: <code>"What is 2+2?","3","4","5","6","B",1,1</code></p>
                `;
            } else if (type === 'match') {
                content = `
                    <p class="mb-1"><strong>Required Columns (in order):</strong></p>
                    <ol class="mb-2 ps-3">
                        <li><code>Question Text</code> (Required)</li>
                        <li><code>Pairs</code> (Required, format: <code>Key1:Val1|Key2:Val2|Key3:Val3|Key4:Val4</code>)</li>
                        <li><code>Marks</code> (Optional, defaults to 1)</li>
                        <li><code>CO</code> (Optional Course Outcome, defaults to 1)</li>
                    </ol>
                    <p class="mb-0 text-secondary" style="font-size:11px;">Example Row: <code>"Match capitals","Paris:France|London:UK|Rome:Italy|Berlin:Germany",1,1</code></p>
                `;
            } else if (type === 'open') {
                content = `
                    <p class="mb-1"><strong>Required Columns (in order):</strong></p>
                    <ol class="mb-2 ps-3">
                        <li><code>Question Text</code> (Required)</li>
                        <li><code>Rubric / Marking Scheme</code> (Optional description)</li>
                        <li><code>Word Limit</code> (Optional number, blank for no limit)</li>
                        <li><code>Marks</code> (Optional, defaults to 1)</li>
                        <li><code>CO</code> (Optional Course Outcome, defaults to 1)</li>
                    </ol>
                    <p class="mb-0 text-secondary" style="font-size:11px;">Example Row: <code>"Explain photosynthesis","2 marks for light reaction, 3 marks for dark reaction",200,5,2</code></p>
                `;
            } else if (type === 'fill') {
                content = `
                    <p class="mb-1"><strong>Required Columns (in order):</strong></p>
                    <ol class="mb-2 ps-3">
                        <li><code>Question Text</code> (Required, use <code>___</code> for blanks)</li>
                        <li><code>Answers</code> (Required, comma or pipe-separated answers in order)</li>
                        <li><code>Marks</code> (Optional, defaults to 1)</li>
                        <li><code>CO</code> (Optional Course Outcome, defaults to 1)</li>
                    </ol>
                    <p class="mb-0 text-secondary" style="font-size:11px;">Example Row: <code>"The capital of France is ___ and Germany is ___.","Paris,Berlin",2,1</code></p>
                `;
            }
            instEl.innerHTML = content;
        }

        function downloadCsvTemplate() {
            const type = document.getElementById('csvQuestionType').value;
            let headers = [];
            let row = [];
            
            if (type === 'mcq') {
                headers = ['Question Text', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Option', 'Marks', 'CO'];
                row = ['What is the capital of France?', 'Paris', 'London', 'Berlin', 'Rome', 'A', '1', '1'];
            } else if (type === 'match') {
                headers = ['Question Text', 'Pairs', 'Marks', 'CO'];
                row = ['Match the following countries and capitals', 'Paris:France|London:UK|Berlin:Germany|Rome:Italy', '1', '1'];
            } else if (type === 'open') {
                headers = ['Question Text', 'Rubric', 'Word Limit', 'Marks', 'CO'];
                row = ['Describe the greenhouse effect and its primary causes.', 'Explain greenhouse gases (3 marks), human impact (2 marks)', '250', '5', '3'];
            } else if (type === 'fill') {
                headers = ['Question Text', 'Answers', 'Marks', 'CO'];
                row = ['Water consists of ___ and ___ atoms.', 'hydrogen,oxygen', '2', '1'];
            }
            
            const csvContent = "data:text/csv;charset=utf-8," 
                + [headers.map(h => `"${h.replace(/"/g, '""')}"`).join(","), 
                   row.map(r => `"${r.replace(/"/g, '""')}"`).join(",")].join("\n");
            
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `mits_${type}_question_template.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        async function handleCsvSubmit(e) {
            e.preventDefault();
            const fileInput = document.getElementById('csvFile');
            const file = fileInput.files[0];
            if (!file) {
                showToast('Please select a CSV file to upload', 'danger');
                return;
            }
            
            const type = document.getElementById('csvQuestionType').value;
            const fd = new FormData();
            fd.append('csv_file', file);
            fd.append('course_id', COURSE_ID);
            fd.append('question_type', type);
            
            setSavingState(true);
            try {
                const res = await fetch(`${API}?action=upload_csv_questions`, {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message || 'CSV imported successfully!', 'success');
                    fileInput.value = ''; // clear file input
                } else {
                    showToast('Error: ' + (data.message || 'Import failed'), 'danger');
                }
            } catch {
                showToast('Network error during CSV upload', 'danger');
            } finally {
                setSavingState(false);
            }
        }

        // Tab toggle listeners to hide/show manual questions list
        document.getElementById('manual-tab').addEventListener('shown.bs.tab', function () {
            document.getElementById('questionsCard').style.display = 'block';
            document.getElementById('btnPublish').style.display = 'block';
        });
        document.getElementById('csv-tab').addEventListener('shown.bs.tab', function () {
            document.getElementById('questionsCard').style.display = 'none';
            document.getElementById('btnPublish').style.display = 'none';
        });

        // Initialize CSV instructions on page load
        updateCsvInstructions();

        // ── Question bank ────────────────────────────────────────────
        async function openQBank() {
            bankData = [];
            renderBankList([]);
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
            const list = document.getElementById('qbankList');
            list.innerHTML = '<p class="text-center text-muted py-4 mb-0">Loading…</p>';
            const params = new URLSearchParams({
                action: 'bank_questions'
            });
            if (search) params.set('search', search);
            if (type) params.set('type', type);
            if (examId) params.set('exclude_exam', examId);
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
            checked.forEach(cb => {
                const q = bankData[parseInt(cb.value, 10)];
                if (q) addQuestion(q.type, q.question_text, q);
            });
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
            return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>
</body>


</html>