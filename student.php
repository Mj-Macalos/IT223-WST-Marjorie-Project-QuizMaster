<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db_connect.php';

$token = $_GET['token'] ?? '';
$quiz = null;
$attempt_id = null;

if ($token) {
    $stmt = $mysqli->prepare("SELECT * FROM quizzes WHERE quiz_token = ? AND status = 'published'");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $quiz = $result->fetch_assoc();
        $questions = [];

        $qRes = $mysqli->query("SELECT * FROM questions WHERE quiz_id = " . $quiz['id']);
        while ($q = $qRes->fetch_assoc()) {
            $question_id = $q['id'];
            $q['choices'] = [];

            if (in_array($q['question_type'], ['mcq', 'true_false'])) {
                $stmt = $mysqli->prepare("SELECT id, choice_text, is_correct FROM choices WHERE question_id = ?");
                $stmt->bind_param("i", $question_id);
                $stmt->execute();
                $choices_result = $stmt->get_result();

                while ($choice = $choices_result->fetch_assoc()) {
                    $q['choices'][] = $choice;
                }
            }

            $questions[] = $q;
        }
        $quiz['questions'] = $questions;
    }
}

$quiz_expired = false;
if ($quiz && !empty($quiz['deadline']) && $quiz['deadline'] !== '0000-00-00 00:00:00') {
    date_default_timezone_set('Asia/Manila');
    $now = date('Y-m-d H:i:s');
    if ($quiz['deadline'] < $now) {
        $quiz_expired = true;
    }
}

// Handle POST requests for quiz answers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    if (!$data || !isset($data['attempt_id'], $data['answers']) || !is_array($data['answers'])) {
        echo json_encode(["success" => false, "message" => "Invalid submission data."]);
        exit;
    }

    $attempt_id = intval($data['attempt_id']);
    $answers = $data['answers'];
    $score = 0;
    $total_questions = count($answers);
    $has_essay = false;

    foreach ($answers as $ans) {
    if (!isset($ans['question_id'], $ans['answer'])) continue;
    $qid = intval($ans['question_id']);
        $selected = trim($ans['answer']);

        $stmt = $mysqli->prepare("INSERT INTO student_answers (attempt_id, question_id, answer) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $attempt_id, $qid, $selected);
    $stmt->execute();

        $qRes = $mysqli->query("SELECT question_type, correct_answer FROM questions WHERE id = $qid");
        $qRow = $qRes->fetch_assoc();

        if ($qRow) {
            $correct = $qRow['correct_answer'];
            $type = $qRow['question_type'];

            // Check if there's an essay question
            if ($type === 'essay') {
                $has_essay = true;
                // Don't add to score for essays - they need manual grading
            } elseif (in_array($type, ['mcq', 'true_false'])) {
                $correct = intval($correct);
                $choiceRes = $mysqli->query("SELECT choice_text FROM choices WHERE question_id = $qid ORDER BY id ASC LIMIT 1 OFFSET $correct");
                $choiceText = $choiceRes->fetch_assoc()['choice_text'] ?? '';
                if (strtolower(trim($choiceText)) === strtolower($selected)) {
                    $score++;
                }
            } elseif ($type === 'short_answer') {
                if (strtolower(trim($selected)) === strtolower(trim($correct))) {
                    $score++;
                }
            }
        }
    }

    $mysqli->query("UPDATE student_attempts SET submitted_at = NOW(), score = $score WHERE id = $attempt_id");
    
    echo json_encode([
        "success" => true, 
        "message" => "Quiz submitted successfully.",
        "score" => $score,
        "total_questions" => $total_questions,
        "has_essay" => $has_essay
    ]);
exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Quiz</title>
    <link rel="stylesheet" href="student.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<?php if (!$quiz): ?>
    <div class="container">
        <div class="card"><h2>Invalid or unpublished quiz link.</h2></div>
        </div>
<?php elseif ($quiz_expired): ?>
    <div class="container">
        <div class="card"><h2>This quiz is expired or the deadline has already been reached.</h2></div>
    </div>
<?php else: ?>
    <div class="container">
        <!-- Student Info Card -->
        <div class="card" id="studentInfoCard">
            <h2>QuizMaster+</h2>

            <div class="form-group">
                <label for="firstName">First Name <span class="required">*</span></label>
                <input type="text" id="firstName" placeholder="Enter first name" onkeypress="allowOnlyLetters(event)">
            </div>

            <div class="form-group">
                <label for="middleInitial">Middle Initial</label>
                <input type="text" id="middleInitial" maxlength="1" onkeypress="allowOnlyLetters(event)">
            </div>

            <div class="form-group">
                <label for="lastName">Last Name <span class="required">*</span></label>
                <input type="text" id="lastName" placeholder="Enter last name" onkeypress="allowOnlyLetters(event)">
            </div>

            <div class="form-group">
                <label for="suffix">Suffix</label>
                <select id="suffix" class="styled-select">
                    <option value="">None</option>
                    <option value="Jr.">Jr.</option>
                    <option value="Sr.">Sr.</option>
                    <option value="II">II</option>
                    <option value="III">III</option>
                    <option value="IV">IV</option>
                    <option value="V">V</option>
                    <option value="VI">VI</option>
                </select>
            </div>

            <div class="form-group">
                <label for="program">Program <span class="required">*</span></label>
                <select id="program" class="styled-select">
                    <option value="">Select program</option>
                    <option value="BEED">BEED</option>
                    <option value="BSED">BSED</option>
                </select>
            </div>

            <div class="form-group">
                <label for="yearLevel">Year Level <span class="required">*</span></label>
                <select id="yearLevel" class="styled-select">
                    <option value="">Select year</option>
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year">3rd Year</option>
                </select>
            </div>

            <div class="form-group">
                <label for="studentId">Student ID <span class="required">*</span></label>
               <input type="text" id="studentId" oninput="filterStudentIdInput(this)" maxlength="20" />
            </div>

            <button class="btn" onclick="submitStudentInfo()">PROCEED TO QUIZ</button>
        </div>

        <!-- Quiz Details Card -->
        <div class="card hidden" id="quizDetailsCard">
            <h2>QuizMaster+</h2>
            <div class="welcome-text">Welcome, <span id="studentName"></span>!</div>
            <div class="quiz-title">Quiz Title: <span><?= htmlspecialchars($quiz['title']) ?></span></div>
            <div class="quiz-info-box">
                <div class="quiz-info-title">Quiz Instructions</div>
                <div class="quiz-info-content"><?= htmlspecialchars($quiz['instructions']) ?></div>
                <div class="quiz-stats"><strong>Questions:</strong> <?= count($quiz['questions']) ?></div>
                <div class="quiz-stats"><strong>Time Limit:</strong> <?= $quiz['time_limit'] ?> mins</div>
               <div class="quiz-stats"><strong>Deadline:</strong> <?= date("F j, Y - g:i A", strtotime($quiz['deadline'])) ?></div>
            </div>
            <button class="btn start-btn" onclick="startQuiz()">START QUIZ</button>
        </div>


        <!-- Timer UI -->
        <div id="timerBox">
             Time Left: <span id="quizTimer">--:--</span>
        </div>

        <!-- Quiz UI -->
        <div class="card hidden" id="quizCard">
            <div id="questionContainer"></div>
            <div id="flashcardNav" style="display: flex; justify-content: space-between; margin-top: 20px;"></div>
        </div>
    </div>

    <script>
        const quiz = <?= json_encode($quiz) ?>;
        const questions = quiz.questions;
        let student = {};
        let attempt_id = null;
        let timerInterval;
    let currentQuestionIndex = 0;
    let answers = Array(questions.length).fill(null); // Store answers by index

    function allowOnlyLetters(e) {
        const char = String.fromCharCode(e.keyCode || e.which);
        const pattern = /^[A-Za-z]$/;
        if (!pattern.test(char)) {
            e.preventDefault();
        }
    }

    function filterStudentIdInput(input) {
    input.value = input.value.replace(/[^\d]/g, ''); // Remove anything that's not a digit
}


        function submitStudentInfo() {
    const firstName = document.getElementById("firstName").value.trim();
    const middleInitial = document.getElementById("middleInitial").value.trim();
    const lastName = document.getElementById("lastName").value.trim();
    const suffix = document.getElementById("suffix").value.trim();
    const program = document.getElementById("program").value.trim();
    const yearLevel = document.getElementById("yearLevel").value.trim();
    const studentId = document.getElementById("studentId").value.trim(); // ✅ match with HTML ID

    if (!firstName || !lastName || !program || !yearLevel || !studentId) {
        alert("Please fill in all required fields.");
        return;
    }

    const nameRegex = /^[A-Za-z]+$/;
    if (!nameRegex.test(firstName) || !nameRegex.test(lastName)) {
        alert("First and Last Name must contain only letters.");
        return;
    }

    if (middleInitial && !/^[A-Za-z]$/.test(middleInitial)) {
        alert("Middle Initial must be a single letter.");
                return;
            }

    // ✅ Validate student ID before proceeding
    if (!validateStudentID()) return;

    const studentData = {
        first_name: firstName,
        middle_initial: middleInitial,
        last_name: lastName,
        suffix: suffix,
        program: program,
        year_level: yearLevel,
                student_id: studentId,
                quiz_id: quiz.id
            };

            const form = new FormData();
    form.append("studentData", JSON.stringify(studentData));

    fetch("submit.php", {
        method: "POST",
        body: form
    })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                    } else {
                        attempt_id = data.attempt_id;
            student = studentData;
            document.getElementById("studentInfoCard").classList.add("hidden");
            document.getElementById("quizDetailsCard").classList.remove("hidden");
            document.getElementById("studentName").innerText = `${student.first_name} ${student.last_name}`;
                    }
                })
                .catch(error => {
        alert("Submission failed.");
        console.error(error);
    });
}


    function validateStudentID() {
    const studentIdInput = document.getElementById("studentId"); // match ID
    const studentId = studentIdInput.value.trim();

    const idIsValid = /^\d+$/.test(studentId); // only digits

    if (!idIsValid) {
        alert("Student ID must contain numbers only.");
        studentIdInput.focus();
        return false;
    }

    return true;
}



        function startQuiz() {
        document.getElementById("quizDetailsCard").classList.add("hidden");
        document.getElementById("quizCard").classList.remove("hidden");
        document.getElementById("timerBox").style.display = "block";
        currentQuestionIndex = 0;
        renderFlashcardQuestion();
            startTimer(quiz.time_limit);
        }

        function startTimer(minutes) {
            let time = minutes * 60;
    const display = document.getElementById("quizTimer");
            
            function updateTimer() {
        const mins = String(Math.floor(time / 60)).padStart(2, '0');
        const secs = String(time % 60).padStart(2, '0');
        display.textContent = `${mins}:${secs}`;
                
                if (time <= 0) {
                    clearInterval(timerInterval);
            alert("Time is up! Submitting your quiz...");
            submitQuiz();
                    return;
                }

                time--;
            }
            
    updateTimer(); // call once immediately
    timerInterval = setInterval(updateTimer, 1000); // repeat every second
}


    function addChoiceEventListeners() {
        const choiceLabels = document.querySelectorAll('.choice-option');
        choiceLabels.forEach(label => {
            label.addEventListener('click', function() {
                const questionId = this.getAttribute('data-question');
                const radioInput = this.querySelector('input[type="radio"]');
                const questionChoices = document.querySelectorAll(`label[data-question="${questionId}"]`);
                questionChoices.forEach(choice => {
                    choice.classList.remove('selected');
                });
                this.classList.add('selected');
                radioInput.checked = true;
            });
        });
    }

    function renderFlashcardQuestion() {
        const container = document.getElementById("questionContainer");
        container.innerHTML = "";
        const q = questions[currentQuestionIndex];
        const index = currentQuestionIndex;
        const qDiv = document.createElement("div");
        qDiv.className = "question-block";
        const questionHeader = `<h3>Q${index + 1}: ${q.question_text}</h3>`;
        let answerValue = answers[index] !== null ? answers[index] : "";
        if (q.question_type === "mcq" || q.question_type === "true_false") {
            let choicesHTML = '<div class="choices-container">';
            q.choices.forEach((choice, choiceIndex) => {
                const choiceId = `choice_${q.id}_${choiceIndex}`;
                const checked = answerValue === choice.choice_text ? 'checked' : '';
                const selected = answerValue === choice.choice_text ? 'selected' : '';
                choicesHTML += `
                    <label for="${choiceId}" class="choice-option ${selected}" data-question="${q.id}">
                        <input type="radio" id="${choiceId}" name="question_${q.id}" value="${choice.choice_text}" ${checked}>
                        <span class="choice-text">${choice.choice_text}</span>
                    </label>
                `;
            });
            choicesHTML += '</div>';
            qDiv.innerHTML = questionHeader + choicesHTML;
        } else if (q.question_type === "essay") {
            qDiv.innerHTML = questionHeader + `
                <div class="choices-container">
                    <textarea name="question_${q.id}" placeholder="Write your essay answer here..." class="essay-input" rows="6">${answerValue}</textarea>
                </div>
            `;
        } else {
            qDiv.innerHTML = questionHeader + `
                <div class="choices-container">
                    <input type="text" name="question_${q.id}" placeholder="Enter your answer here..." class="short-answer-input" value="${answerValue}">
                </div>
            `;
        }
        container.appendChild(qDiv);
        addChoiceEventListeners();
        renderFlashcardNav();
    }

    function renderFlashcardNav() {
        const nav = document.getElementById("flashcardNav");
        nav.innerHTML = "";
        // Previous button
        const prevBtn = document.createElement("button");
        prevBtn.className = "btn";
        prevBtn.textContent = "Previous";
        prevBtn.disabled = currentQuestionIndex === 0;
        prevBtn.onclick = () => {
            saveCurrentAnswer();
            if (currentQuestionIndex > 0) {
                currentQuestionIndex--;
                renderFlashcardQuestion();
            }
        };
        nav.appendChild(prevBtn);
        // Next or Submit button
        if (currentQuestionIndex < questions.length - 1) {
            const nextBtn = document.createElement("button");
            nextBtn.className = "btn";
            nextBtn.textContent = "Next";
            nextBtn.onclick = () => {
                if (!saveCurrentAnswer()) return;
                currentQuestionIndex++;
                renderFlashcardQuestion();
            };
            nav.appendChild(nextBtn);
        } else {
            const submitBtn = document.createElement("button");
            submitBtn.className = "btn";
            submitBtn.textContent = "Submit Quiz";
            submitBtn.onclick = () => {
                if (!saveCurrentAnswer()) return;
                submitQuiz();
            };
            nav.appendChild(submitBtn);
        }
    }

    function saveCurrentAnswer() {
        const q = questions[currentQuestionIndex];
        let input;
        if (q.question_type === 'essay') {
            input = document.querySelector(`textarea[name='question_${q.id}']`);
        } else if (q.question_type === 'mcq' || q.question_type === 'true_false') {
            input = document.querySelector(`input[name='question_${q.id}']:checked`);
        } else {
            input = document.querySelector(`input[name='question_${q.id}']`);
        }
        const answer = input ? input.value.trim() : "";
        answers[currentQuestionIndex] = answer;
        // Validation: require answer before moving next/submit
        if (!answer) {
            alert("Please answer this question before proceeding.");
            return false;
        }
        return true;
    }

    function submitQuiz() {
        if (!attempt_id) {
            alert("Missing attempt ID.");
            return;
        }
        // Build answers array for submission
        const submission = questions.map((q, i) => ({ question_id: q.id, answer: answers[i] || "" }));
        if (submission.some(a => !a.answer)) {
            alert("Please answer all the questions before submitting.");
            return;
        }
        // Disable submit button
        const nav = document.getElementById("flashcardNav");
        nav.querySelector(".btn:last-child").textContent = 'Submitting...';
        nav.querySelector(".btn:last-child").disabled = true;
        fetch("student.php?token=" + quiz.quiz_token, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                submitAnswers: true,
        attempt_id: attempt_id, 
                answers: submission
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                clearInterval(timerInterval); // stop timer
                document.getElementById("timerBox").style.display = "none"; // hide timer
                // Build score display
                let scoreHTML = "";
                if (data.total_questions !== undefined && data.score !== undefined) {
                    if (data.has_essay) {
                        const nonEssayQuestions = questions.filter(q => q.question_type !== 'essay').length;
                        scoreHTML = `
                            <div class="score-box">
                                <div class="score-title">Your Score</div>
                                <div class="score-value">${data.score}/${nonEssayQuestions}</div>
                                <div class="score-label">Partial Score (Auto-graded only)</div>
                                <div class="score-note">Essay answers require manual grading</div>
                            </div>
                        `;
                    } else {
                        const percentage = Math.round((data.score / data.total_questions) * 100);
                        scoreHTML = `
                            <div class="score-box">
                                <div class="score-title">Your Final Score</div>
                                <div class="score-value">${data.score}/${data.total_questions}</div>
                                <div class="score-label">${percentage}% Correct</div>
                            </div>
                        `;
                    }
                }
                document.getElementById("quizCard").innerHTML = `
                    ${scoreHTML}
                    <div class="success-message">
                        <h3>🎉 Quiz Submitted Successfully!</h3>
                        <p>Thank you for completing the quiz.</p>
                    </div>
                `;
            } else {
                nav.querySelector(".btn:last-child").textContent = "Submit Quiz";
                nav.querySelector(".btn:last-child").disabled = false;
                alert(data.message || "Submission failed.");
            }
        })
        .catch(err => {
            nav.querySelector(".btn:last-child").textContent = "Submit Quiz";
            nav.querySelector(".btn:last-child").disabled = false;
            alert("Network error. Please try again.");
            console.error(err);
        });
    }

    </script>
<?php endif; ?>
</body>
</html>