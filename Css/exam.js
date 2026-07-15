let currentQ = 0;
let time = durationMinutes * 60;
let timerInterval;

const answers = {};
const visited = new Set();

const timerDisplay = document.getElementById("timer");
const questionCard = document.getElementById("questionCard");
const questionStatus = document.getElementById("questionStatus");
const examForm = document.getElementById("examForm");

// -------------------------------
// Create Sidebar Buttons
// -------------------------------
questions.forEach((_, i) => {
    const btn = document.createElement("button");
    btn.className = "question-btn not-visited";
    btn.textContent = i + 1;
    btn.onclick = () => loadQuestion(i);
    questionStatus.appendChild(btn);
});

// -------------------------------
// Status Color Updater
// -------------------------------
function updateStatusColors() {
    document.querySelectorAll(".question-btn").forEach((btn, i) => {
        const q = questions[i];

        let state =
            i === currentQ ? "active-question" :
            (q.Type_ID == 2? (answers[i] && Object.keys(answers[i]).length === Object.keys(q.matching).length): answers[i])
            ? "answered": visited.has(i)
            ? "not-answered": "not-visited";

        btn.className = "question-btn " + state;
    });
}

// -------------------------------
// Question Loader
// -------------------------------
function loadQuestion(i) {
    currentQ = i;
    visited.add(i);
    const q = questions[i];
    console.log("Loading question:", q);
    
    let html = `
        <h5 class="fw-bold mb-3">Question ${i + 1} of ${questions.length}</h5>
        <p class="mb-4">${q.Question_Text}</p>
    `;

    // TYPE 1: Fill-Up
    if (q.Type_ID == 1) {
        html += `
            <input type="text" class="form-control" placeholder="Type your answer here"
                value="${answers[i] || ''}"
                oninput="selectOption(${i}, '${q.Q_ID}', this.value)">
        `;
    }

    // TYPE 2: Matching
    else if (q.Type_ID == 2) {
        const left = Object.keys(q.matching);
        const right = Object.values(q.matching);
        const shuffled = [...right].sort(() => Math.random() - 0.5);

        html += `<div class="row"><strong>Match the Following</strong></div><hr>`;

        left.forEach((l) => {
            const saved = answers[i]?.[l] || "";
            html += `
            <div class="row mb-3">
                <div class="col-6 bg-light border p-2 rounded">${l}</div>
                <div class="col-6">
                    <select class="form-select"
                        onchange="selectMatch(${i}, '${q.Q_ID}', '${l}', this.value)">
                        <option value="">-- select --</option>
                        ${shuffled.map(r =>
                            `<option value="${r}" ${saved === r ? "selected" : ""}>${r}</option>`
                        ).join('')}
                    </select>
                </div>
            </div>
            `;
        });
    }
    // TYPE 3: MCQ
    else if (q.Type_ID == 3) {
        html += q.options.map((opt, j) => `
        <div class="options">
            <input type="radio" id="q${q.Q_ID}_${j}" name="q${q.Q_ID}" value="${opt}" ${answers[i] === opt ? "checked" : ""}
                onchange="selectOption(${i}, '${q.Q_ID}', '${opt}')">
            <label for="q${q.Q_ID}_${j}">${opt}</label>
        </div>
        `).join('');
        // console.log("Q_ID:", q.Q_ID, "Answers:", answers[i]);
    }

    // TYPE 7: Open Ended
    else if (q.Type_ID == 7) {
        html += `
            <textarea class="form-control" rows="4"
                oninput="selectOption(${i}, '${q.Q_ID}', this.value)">${answers[i] || ''}</textarea>
        `;
    }

    // TYPE 9: Diagram
    else if (q.Type_ID == 9) {
        html += `
            <img src="get-image.php?id=${q.Q_ID}" class="img-fluid mb-3 rounded border">
            <textarea class="form-control" rows="4"
                oninput="selectOption(${i}, '${q.Q_ID}', this.value)">${answers[i] || ''}</textarea>
        `;
    }
    else {
        console.error("Unknown question type:", q.Type_ID);
        html += `<p class="text-muted">This question type is not yet supported in the UI.</p>`;
    }

    html += `
        <div class="navigation-buttons">
            <button type="button" class="btn btn-secondary" onclick="prevQ()">
                <i class='bi bi-arrow-left'></i> Previous
            </button>
            <button type="button" class="btn btn-danger" onclick="nextQ()">
                Next <i class='bi bi-arrow-right'></i>
            </button>
        </div>
    `;
    
    questionCard.innerHTML = html;
    updateStatusColors();
}

// -------------------------------
// Save Answers
// -------------------------------
function selectOption(i, Q_ID, val) {
    answers[i] = val;
    updateStatusColors();

    let hidden = examForm.querySelector(`[name="answers[${Q_ID}]"]`);
    if (!hidden) {
        hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = `answers[${Q_ID}]`;
        examForm.appendChild(hidden);
    }
    hidden.value = val;
}

function selectMatch(i, Q_ID, left, val) {
    if (!answers[i]) answers[i] = {};
    answers[i][left] = val;
    updateStatusColors();

    let name = `answers[${Q_ID}][${left}]`;
    let hidden = examForm.querySelector(`[name="${name}"]`);

    if (!hidden) {
        hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = name;
        examForm.appendChild(hidden);
    }
    hidden.value = val;
}

// -------------------------------
// Navigation
// -------------------------------
function nextQ() {
    if (currentQ < questions.length - 1) loadQuestion(currentQ + 1);
    else if (confirm("Submit exam?")) examForm.submit();
}

function prevQ() {
    if (currentQ > 0) loadQuestion(currentQ - 1);
}

// -------------------------------
// Timer
// -------------------------------
function timerTick() {
    const m = Math.floor(time / 60);
    const s = time % 60;
    timerDisplay.textContent = `${m}:${s.toString().padStart(2, "0")}`;
    if (time <= 0) {
        alert("Time's up");
        examForm.submit();
    }
    time--;
}

// -------------------------------
// Anti-cheat: tab switch
// -------------------------------
let tabSwitch = 0;
document.addEventListener("visibilitychange", () => {
    if (document.hidden && ++tabSwitch >= 3) {
        alert("Too many tab switches. Exam locked.");
        document.querySelectorAll("button,input,select,textarea").forEach(e => e.disabled = true);
    }
});

// -------------------------------
// FULLSCREEN HANDLING
// -------------------------------
const startOverlay = document.getElementById("startOverlay");
const startExamBtn = document.getElementById("startExamBtn");
const fullscreenWarning = document.getElementById("fullscreenWarning");
const returnFullscreenBtn = document.getElementById("returnFullscreenBtn");

let exitCount = 0;
let fullscreenEntered = false;

function requestFullscreen() {
    const el = document.documentElement;
    (el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen).call(el);
}

startExamBtn.onclick = () => {
    requestFullscreen();
    startOverlay.style.display = "none";
    loadQuestion(0);
    timerTick();
    timerInterval = setInterval(timerTick, 1000);
};

document.addEventListener("fullscreenchange", () => {
    if (!document.fullscreenElement) {
        if (fullscreenEntered) {
            exitCount++;
            fullscreenWarning.style.display = "flex";
            if (exitCount >= 3) {
                alert("Exited full-screen too many times. Exam locked.");
                document.querySelectorAll("button,input,select,textarea").forEach(e => e.disabled = true);
                fullscreenWarning.style.display = "none";
            }
        }
    } else {
        fullscreenEntered = true;
        fullscreenWarning.style.display = "none";
    }
});

returnFullscreenBtn.onclick = requestFullscreen;
