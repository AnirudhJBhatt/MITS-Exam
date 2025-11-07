<?php
    session_start();
    require_once "../Connection/connection.php";

    if (!isset($_SESSION["LoginStudent"])) {
        echo "<script>alert('Unauthorized access!'); window.location='../Login/Login.php';</script>";
        exit;
    }

    $Stud_ID = $_SESSION["LoginStudent"];
    $query = "SELECT * FROM `student` WHERE `Stud_ID` = '$Stud_ID' ";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_array($run);
    $Stud_Name = $row['Stud_Name'];

    $Exam_ID = $_GET['Exam_ID']; // exam ID passed via URL

    // Fetch exam data
    $exam_query = "SELECT * FROM exam WHERE Exam_ID = '$Exam_ID'";
    $exam_run = mysqli_query($con, $exam_query);
    $exam_data = mysqli_fetch_assoc($exam_run);
    $durationMinutes = (int)$exam_data['Duration'];
    $qids = json_decode($exam_data['Q_IDs'], true);

    // Fetch all questions in one query
    $qid_list = implode(",", $qids);
    $questions_query = "SELECT * FROM question_bank WHERE Q_ID IN ($qid_list)";
    $questions_run = mysqli_query($con, $questions_query);

    $questions = [];
    while ($row = mysqli_fetch_assoc($questions_run)) {
        $questions[] = [
            "qid" => $row['Q_ID'],
            "type" => $row['Type_ID'],
            "question" => $row['Question_Text'],
            "options" => json_decode($row['Options'], true),
            "correct" => json_decode($row['Correct_Answer'], true),
            "marks" => $row['Marks']
        ];
        
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MITS Exam - Attempt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Css/style.css">
    <style>
        .timer {
            font-weight: 700;
            color: #d1202d;
            font-size: 18px;
            background: #fce8e8;
            padding: 8px 16px;
            border-radius: 10px;
        }

        .exam-sidebar {
            position: fixed;
            top: 70px;
            left: 0;
            width: 280px;
            height: calc(100vh - 70px);
            background-color: #181a1e;
            color: #fff;
            overflow-y: auto;
            padding: 20px;
        }

        .exam-sidebar h6 {
            font-weight: 600;
            margin-bottom: 15px;
            color: #f1f1f1;
        }

        .question-status {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(45px, 1fr));
            gap: 10px;
        }

        .question-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: none;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .question-btn:hover {
            transform: scale(1.1);
        }

        .not-visited {
            background-color: #6c757d !important; /* Grey */
        }

        .not-answered {
            background-color: #dc3545 !important; /* Red */
        }
        
        .answered {
            background-color: #28a745 !important; /* Green */
        }

        .active-question {
            background-color: #ffc107 !important; color: #fff;;
        }

        .question-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 30px;
            transition: all 0.3s;
        }

        .options label {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 10px;
            display: block;
            cursor: pointer;
            border: 1px solid #e1e1e1;
            transition: all 0.2s;
        }

        .options input[type="radio"] {
            display: none;
        }

        .options input[type="radio"]:checked+label {
            background: #d1202d;
            color: #fff;
            border: none;
        }

        .navigation-buttons {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        .navigation-buttons button {
            min-width: 130px;
            border-radius: 8px;
        }

        #tabWarning {
            position: fixed;
            top: 80px;
            right: 20px;
            background: #fff3cd;
            color: #856404;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #ffeeba;
            display: none;
            z-index: 2000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg fixed-top px-4">
        <div class="d-flex align-items-center">
            <span class="fw-bold text-dark"><?php echo $Stud_Name; ?></span>
        </div>
        <a class="navbar-brand position-absolute top-50 start-50 translate-middle" href="#">
            <img src="https://mgmits.ac.in/frontend/images/logo1.png" alt="MITS Logo" height="75" class="me-2">
        </a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="timer" id="timer">30:00</div>
        </div>
    </nav>

    <div id="tabWarning">You switched tabs <span id="tabCount">0</span> times</div>

    <div class="exam-sidebar">
        <h6>Question Status</h6>
        <div class="question-status" id="questionStatus"></div>
    </div>

    <main>
        <form id="examForm" method="POST" action="submit-exam.php">
            <input type="hidden" name="Exam_ID" value="<?php echo $Exam_ID; ?>">
            <div class="question-card" id="questionCard"></div>
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-success px-4 py-2"><i class="bi bi-check-circle"></i> Submit
                    Exam</button>
            </div>
        </form>
    </main>
    
    <script>
        const questions = <?php echo json_encode($questions); ?>;
        let currentQ = 0;
        let time = <?php echo $durationMinutes; ?> * 60;
        const answers = {};
        const visited = new Set();

        // Cache DOM elements
        const timerDisplay = document.getElementById("timer");
        const questionCard = document.getElementById("questionCard");
        const questionStatus = document.getElementById("questionStatus");
        const examForm = document.getElementById("examForm");

        // Create sidebar buttons
        questions.forEach((_, i) => {
            const btn = document.createElement("button");
            btn.className = "question-btn not-visited";
            btn.textContent = i + 1;
            btn.onclick = () => loadQuestion(i);
            questionStatus.appendChild(btn);
        });

        function updateStatusColors() {
            document.querySelectorAll(".question-btn").forEach((btn, i) => {
                btn.className = "question-btn " + (
                    i === currentQ ? "active-question" :
                    answers[i] ? "answered" :
                    visited.has(i) ? "not-answered" : "not-visited"
                );
            });
        }

        // ⬇️ Universal question loader (MCQ + Fill-up)
        function loadQuestion(i) {
            currentQ = i;
            visited.add(i);
            const q = questions[i];
            let html = `
                <h5 class="fw-bold mb-3">Question ${i + 1} of ${questions.length}</h5>
                <p class="mb-4">${q.question}</p>
            `;

            if (q.type == 1) {
                // 🟢 Fill-up type
                html += `
                    <div class="mb-3">
                        <input type="text" class="form-control" placeholder="Type your answer here"
                            value="${answers[i] || ''}"
                            oninput="selectOption(${i}, '${q.qid}', this.value)">
                    </div>
                `;
            } 
            else if (q.type == 3 && q.options) {
                // 🔵 MCQ type
                html += q.options.map((opt, j) => `
                    <div class="options">
                        <input type="radio" name="q${q.qid}" id="q${i}_${j}" value="${opt}"
                            ${answers[i] === opt ? "checked" : ""}
                            onchange="selectOption(${i}, '${q.qid}', '${opt}')">
                        <label for="q${i}_${j}">${opt}</label>
                    </div>
                `).join('');
            } 
            else {
                // ⚪ For any unsupported type (future types)
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

        // 🧩 Save selected answer (works for both MCQ and fill-up)
        function selectOption(i, qid, val) {
            answers[i] = val;
            updateStatusColors();

            let hidden = examForm.querySelector(`[name="answers[${qid}]"]`);
            if (!hidden) {
                hidden = document.createElement("input");
                hidden.type = "hidden";
                hidden.name = `answers[${qid}]`;
                examForm.appendChild(hidden);
            }
            hidden.value = val;
        }

        function nextQ() {
            if (currentQ < questions.length - 1) loadQuestion(currentQ + 1);
            else if (confirm("Submit exam now?")) examForm.submit();
        }

        function prevQ() {
            if (currentQ > 0) loadQuestion(currentQ - 1);
        }

        function timerTick() {
            const m = Math.floor(time / 60);
            const s = time % 60;
            timerDisplay.textContent = `${m}:${s.toString().padStart(2, "0")}`;

            if (time <= 0) {
                clearInterval(timerInterval);
                alert("Time’s up! Submitting...");
                examForm.submit();
            }
            time--;
        }
        

        // 🚫 Anti-cheat (tab switch lock)
        let tabSwitch = 0;
        document.addEventListener("visibilitychange", () => {
            if (document.hidden && ++tabSwitch >= 3) {
                alert("Too many tab switches. Exam locked.");
                document.querySelectorAll("button, input").forEach(e => e.disabled = true);
            }
        });

        // 🚀 Initialize        
        loadQuestion(0);
        timerTick();
    </script>

    <div id="startOverlay" style="
        position: fixed; 
        top: 0; left: 0; 
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.9); 
        color: white; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        flex-direction: column; 
        z-index: 9999;">
        <h2>Click below to start your exam in full-screen mode</h2>
        <button id="startExamBtn" class="btn btn-primary btn-lg mt-3">Start Exam</button>
    </div>

    <!-- Fullscreen warning overlay -->
    <div id="fullscreenWarning" style="
        display:none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.9);
        color: white;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        z-index: 10000;
        text-align: center;">
        <h3>You exited full-screen mode.</h3>
        <p>Please click below to return to full-screen and continue your exam.</p>
        <button id="returnFullscreenBtn" class="btn btn-warning mt-3">Return to Fullscreen</button>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const startOverlay = document.getElementById("startOverlay");
            const startExamBtn = document.getElementById("startExamBtn");
            const fullscreenWarning = document.getElementById("fullscreenWarning");
            const returnFullscreenBtn = document.getElementById("returnFullscreenBtn");

            let exitCount = 0;
            let fullscreenEnteredOnce = false; // ✅ prevent warning on first entry

            // Function to request fullscreen safely
            const startFullscreen = () => {
                const elem = document.documentElement;
                if (elem.requestFullscreen) elem.requestFullscreen();
                else if (elem.mozRequestFullScreen) elem.mozRequestFullScreen(); // Firefox
                else if (elem.webkitRequestFullscreen) elem.webkitRequestFullscreen(); // Safari
                else if (elem.msRequestFullscreen) elem.msRequestFullscreen(); // IE/Edge
            };

            // When user clicks Start Exam
            startExamBtn.addEventListener("click", () => {
                startFullscreen(); // ✅ Allowed by user gesture
                startOverlay.style.display = "none"; // Hide overlay
                loadQuestion(0); 
                timerTick();
                timerInterval = setInterval(timerTick, 1000);
            });

            // Detect fullscreen changes
            document.addEventListener("fullscreenchange", () => {
                if (!document.fullscreenElement) {
                    // If user exits fullscreen after the first entry
                    if (fullscreenEnteredOnce) {
                        exitCount++;
                        fullscreenWarning.style.display = "flex";
                        if (exitCount >= 3) {
                            alert("You exited full-screen too many times. Exam locked.");
                            document.querySelectorAll("button, input").forEach(e => e.disabled = true);
                            fullscreenWarning.style.display = "none";
                        }
                    }
                } else {
                    // First successful fullscreen entry
                    fullscreenEnteredOnce = true;
                    fullscreenWarning.style.display = "none";
                }
            });

            // Return to fullscreen (✅ valid user gesture)
            returnFullscreenBtn.addEventListener("click", () => {
                startFullscreen();
                fullscreenWarning.style.display = "none";
            });
        });
    </script>


    <!-- <script>
        const questions = <?php echo json_encode($questions); ?>;
        let currentQ = 0;
        let time = 30 * 60;
        const answers = {};
        const visited = new Set();

        // Cache DOM elements
        const timerDisplay = document.getElementById("timer");
        const questionCard = document.getElementById("questionCard");
        const questionStatus = document.getElementById("questionStatus");
        const examForm = document.getElementById("examForm");

        // Create question buttons
        questions.forEach((_, i) => {
            const btn = document.createElement("button");
            btn.className = "question-btn not-visited";
            btn.textContent = i + 1;
            btn.onclick = () => loadQuestion(i);
            questionStatus.appendChild(btn);
        });

        function updateStatusColors() {
        document.querySelectorAll(".question-btn").forEach((btn, i) => {
            btn.className = "question-btn " + (
            i === currentQ ? "active-question" :
            answers[i] ? "answered" :
            visited.has(i) ? "not-answered" : "not-visited"
            );
        });
        }

        function loadQuestion(i) {
        currentQ = i;
        visited.add(i);
        const q = questions[i];
        questionCard.innerHTML = `
            <h5 class="fw-bold mb-3">Question ${i + 1} of ${questions.length}</h5>
            <p class="mb-4">${q.question}</p>
            ${q.options.map((opt, j) => `
            <div class="options">
                <input type="radio" name="q${q.qid}" id="q${i}_${j}" value="${opt}"
                ${answers[i] === opt ? "checked" : ""}
                onchange="selectOption(${i}, '${q.qid}', '${opt}')">
                <label for="q${i}_${j}">${opt}</label>
            </div>
            `).join('')}
            <div class="navigation-buttons">
                <button type="button" class="btn btn-secondary" onclick="prevQ()">
                    <i class='bi bi-arrow-left'></i> Previous
                </button>
                <button type="button" class="btn btn-danger" onclick="nextQ()">
                    Next <i class='bi bi-arrow-right'></i>
                </button>
            </div>
        `;
        updateStatusColors();
        }

        function selectOption(i, qid, val) {
            answers[i] = val;
            updateStatusColors();
            let hidden = examForm.querySelector(`[name="answers[${qid}]"]`);
            if (!hidden) {
                hidden = document.createElement("input");
                hidden.type = "hidden";
                hidden.name = `answers[${qid}]`;
                examForm.appendChild(hidden);
            }
            hidden.value = val;
        }

        function nextQ() {
            if (currentQ < questions.length - 1) loadQuestion(currentQ + 1);
            else if (confirm("Submit exam now?")) examForm.submit();
        }

        function prevQ() {
        if (currentQ > 0) loadQuestion(currentQ - 1);
        }

        function timerTick() {
        const m = Math.floor(time / 60), s = time % 60;
        timerDisplay.textContent = `${m}:${s.toString().padStart(2,"0")}`;
        if (time-- > 0) setTimeout(timerTick, 1000);
        else { alert("Time’s up! Submitting..."); examForm.submit(); }
        }

        // Anti-cheat
        let tabSwitch = 0;
        document.addEventListener("visibilitychange", () => {
        if (document.hidden && ++tabSwitch >= 3) {
            alert("Too many tab switches. Exam locked.");
            document.querySelectorAll("button, input").forEach(e => e.disabled = true);
        }
        });

        // Initialize
        loadQuestion(0);
        timerTick();

        
    </script> -->
</body>

</html>