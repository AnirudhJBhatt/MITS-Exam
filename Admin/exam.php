<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MITS Exam - MCQ Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        * { font-family: "Solway", serif; }

        body {
            background-color: #f7f8fa;
            margin: 0;
            overflow-x: hidden;
        }

        /* HEADER */
        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e5e5e5;
            height: 70px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.05);
            z-index: 1001;
        } 

        .navbar-brand {
            color: #d1202d !important;
            font-weight: 700;
            font-size: 22px;
        }

        .timer {
            font-weight: 700;
            color: #d1202d;
            font-size: 18px;
            background: #fce8e8;
            padding: 8px 16px;
            border-radius: 10px;
        }

        /* SIDEBAR */
        .sidebar {
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

        .sidebar h6 {
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
            transition: transform 0.2s ease;
        }

        .question-btn:hover {
            transform: scale(1.1);
        }

        .answered { background-color: #28a745; }    /* Green */
        .not-answered { background-color: #dc3545; } /* Red */
        .skipped { background-color: #6c757d; }      /* Grey */
        .active-question { outline: 3px solid #fff; }

        /* MAIN CONTENT */
        main {
            margin-left: 280px;
            padding: 100px 40px 60px 40px;
        }

        .question-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 30px;
            transition: all 0.3s ease;
        }

        .options label {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 10px;
            display: block;
            cursor: pointer;
            border: 1px solid #e1e1e1;
            transition: all 0.2s ease;
        }

        .options input[type="radio"] {
            display: none;
        }

        .options input[type="radio"]:checked + label {
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

        @media (max-width: 992px) {
            .sidebar { display: none; }
            main { margin-left: 0; padding: 80px 20px; }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <nav class="navbar navbar-expand-lg fixed-top px-4">
        <a class="navbar-brand" href="#">MITS Exam</a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="timer" id="timer">30:00</div>
        </div>
    </nav>

    <!-- Tab switch warning (hidden by default) -->
    <div id="tabWarning" style="position:fixed; top:80px; right:20px; background:#fff3cd; color:#856404; padding:10px 14px; border-radius:6px; border:1px solid #ffeeba; display:none; z-index:2000; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
        You switched tabs <span id="tabCount">0</span> times
    </div>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h6>Question Status</h6>
        <div class="question-status" id="questionStatus"></div>
    </div>

    <!-- MAIN CONTENT -->
    <main>
        <div class="question-card" id="questionCard">
            <!-- Dynamic Question Content -->
        </div>
    </main>

    <script>
        // Question data (example)
        const questions = [
            {
                question: "Which of the following is used for web development?",
                options: ["Python", "HTML", "C++", "Java"],
                correct: 1
            },
            {
                question: "What does CSS stand for?",
                options: ["Cascading Style Sheets", "Creative Style System", "Computer Style Sheet", "Colorful Style Syntax"],
                correct: 0
            },
            {
                question: "Which tag is used to create a hyperlink in HTML?",
                options: ["a", "link", "href", "url"],
                correct: 0
            },
            {
                question: "Which language runs in a web browser?",
                options: ["C", "Java", "Python", "JavaScript"],
                correct: 3
            }
        ];

        let currentQ = 0;
        let answers = Array(questions.length).fill(null);
        let time = 30 * 60;

        const questionCard = document.getElementById("questionCard");
        const questionStatus = document.getElementById("questionStatus");
        const timerDisplay = document.getElementById("timer");

        // Create sidebar buttons
        questions.forEach((_, i) => {
            const btn = document.createElement("button");
            btn.className = "question-btn skipped";
            btn.textContent = i + 1;
            btn.addEventListener("click", () => loadQuestion(i));
            questionStatus.appendChild(btn);
        });

        // Load Question
        function loadQuestion(index) {
            currentQ = index;
            const q = questions[index];
            document.querySelectorAll(".question-btn").forEach((b) => b.classList.remove("active-question"));
            document.querySelectorAll(".question-btn")[index].classList.add("active-question");

            questionCard.innerHTML = `
                <h5 class="fw-bold mb-3">Question ${index + 1} of ${questions.length}</h5>
                <p class="mb-4">${q.question}</p>
                <div class="options">
                    ${q.options.map((opt, i) => `
                        <input type="radio" name="q${index}" id="q${index}_opt${i}" ${answers[index] === i ? 'checked' : ''}>
                        <label for="q${index}_opt${i}" onclick="selectOption(${index}, ${i})">${opt}</label>
                    `).join('')}
                </div>
                <div class="navigation-buttons">
                    <button class="btn btn-secondary" onclick="prevQuestion()"><i class='bi bi-arrow-left'></i> Previous</button>
                    <button class="btn btn-outline-secondary" onclick="skipQuestion()">Skip</button>
                    <button class="btn btn-danger" onclick="nextQuestion()">Next <i class='bi bi-arrow-right'></i></button>
                </div>
            `;
        }

        function selectOption(qIndex, optIndex) {
            answers[qIndex] = optIndex;
            const btn = document.querySelectorAll(".question-btn")[qIndex];
            btn.className = "question-btn answered";
        }

        function nextQuestion() {
            if (currentQ < questions.length - 1) loadQuestion(currentQ + 1);
        }

        function prevQuestion() {
            if (currentQ > 0) loadQuestion(currentQ - 1);
        }

        function skipQuestion() {
            const btn = document.querySelectorAll(".question-btn")[currentQ];
            if (answers[currentQ] === null) btn.className = "question-btn skipped";
            nextQuestion();
        }

        // Timer Functionality
        function updateTimer() {
            const minutes = Math.floor(time / 60);
            const seconds = time % 60;
            timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, "0")}`;
            if (time > 0) {
                time--;
                setTimeout(updateTimer, 1000);
            } else {
                alert("Time’s up! Submitting your answers...");
            }
        }

        // --- Anti-cheat / UI protections ---
        // Utility: show a temporary message in the top-right warning area
        function showTempMessage(msg, timeout = 3000) {
            const banner = document.getElementById('tabWarning');
            banner.textContent = msg;
            banner.style.display = 'block';
            setTimeout(() => {
                banner.style.display = 'none';
                // restore default content
                document.getElementById('tabCount').textContent = tabSwitchCount;
            }, timeout);
        }

        // Tab switching detection
        let tabSwitchCount = 0;
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                tabSwitchCount++;
                const span = document.getElementById('tabCount');
                if (span) span.textContent = tabSwitchCount;
                showTempMessage(`You switched tabs ${tabSwitchCount} time${tabSwitchCount > 1 ? 's' : ''}`);
                console.warn('User switched tabs away from exam. Count:', tabSwitchCount);

                // After 3 switches lock the UI (example policy)
                if (tabSwitchCount >= 3) {
                    showTempMessage('You switched tabs too many times. The exam is now locked.');
                    // disable inputs and buttons to prevent further interaction
                    document.querySelectorAll('button, input').forEach(el => el.disabled = true);
                }
            }
        });

        // Disable right-click (context menu) and show a small message
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            showTempMessage('Right click is disabled during the exam');
            console.log('Context menu disabled');
            return false;
        });

        // Start timer and load first question
        updateTimer();
        loadQuestion(0);
    </script>
</body>
</html>
