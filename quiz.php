<?php 
require_once("auth.php");
require_once 'db_connect.php';

// Set PHP timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_quiz'])) {
        $id = intval($_POST['delete_quiz']);

        // Check if any student answers are linked to this quiz
        $stmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM student_answers sa JOIN questions q ON sa.question_id = q.id WHERE q.quiz_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if (intval($res['cnt']) > 0) {
            echo json_encode(["success" => false, "message" => "Cannot delete quiz. It has already been answered by students."]);
            exit;
        }

        // Delete choices, questions, then the quiz itself
        $mysqli->query("DELETE FROM choices WHERE question_id IN (SELECT id FROM questions WHERE quiz_id = $id)");
        $mysqli->query("DELETE FROM questions WHERE quiz_id = $id");
        $mysqli->query("DELETE FROM quizzes WHERE id = $id");

        echo json_encode(["success" => true, "message" => "Quiz deleted successfully."]);
        exit;
    }

    if (isset($_POST['quiz_data'])) {
        $quiz = json_decode($_POST['quiz_data'], true);
        // Server-side validation for title and questions
        if (empty(trim($quiz['title']))) {
            echo json_encode(["error" => "Quiz title cannot be empty."]);
            exit;
        }
        if (empty($quiz['questions']) || !is_array($quiz['questions'])) {
            echo json_encode(["error" => "Quiz must have at least one question."]);
            exit;
        }
        $token = $quiz['id'] ? $quiz['token'] : generateToken();

        // In PHP, handle null deadline
        $deadline = !empty($quiz['deadline']) ? $quiz['deadline'] : null;
        if ($quiz['id']) {
            // Fetch current status from DB
            $stmt = $mysqli->prepare("SELECT status FROM quizzes WHERE id = ?");
            $stmt->bind_param("i", $quiz['id']);
            $stmt->execute();
            $currentQuiz = $stmt->get_result()->fetch_assoc();
            $currentStatus = $currentQuiz ? $currentQuiz['status'] : null;

    // Check if quiz is already published and has student answers
        $stmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM student_answers sa JOIN questions q ON sa.question_id = q.id WHERE q.quiz_id = ?");
        $stmt->bind_param("i", $quiz['id']);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
            $hasAnswers = intval($res['cnt']) > 0;

            // Prevent changing published quiz back to draft if already answered
            if ($currentStatus === 'published' && $quiz['status'] === 'draft' && $hasAnswers) {
                echo json_encode(["error" => "Cannot change a published quiz with student answers back to draft."]);
                exit;
            }

            // Prevent editing published quiz if already answered
            if ($quiz['status'] === 'published' && $hasAnswers) {
            echo json_encode(["error" => "Cannot update a published quiz that already has student answers."]);
            exit;
    }

    // Safe to proceed
    $stmt = $mysqli->prepare("UPDATE quizzes SET title=?, subject=?, instructions=?, time_limit=?, attempts_allowed=?, deadline=?, status=? WHERE id=?");
            $stmt->bind_param("sssiissi", $quiz['title'], $quiz['subject'], $quiz['instructions'], $quiz['time_limit'], $quiz['attempts_allowed'], $deadline, $quiz['status'], $quiz['id']);
    $stmt->execute();
    $quiz_id = $quiz['id'];
    $mysqli->query("DELETE FROM questions WHERE quiz_id = $quiz_id");


        } else {
            $stmt = $mysqli->prepare("INSERT INTO quizzes (title, subject, instructions, time_limit, attempts_allowed, deadline, status, quiz_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiisss", $quiz['title'], $quiz['subject'], $quiz['instructions'], $quiz['time_limit'], $quiz['attempts_allowed'], $deadline, $quiz['status'], $token);
            $stmt->execute();
            $quiz_id = $stmt->insert_id;
        }

        foreach ($quiz['questions'] as $q) {
            $stmtQ = $mysqli->prepare("INSERT INTO questions (quiz_id, question_text, question_type, correct_answer) VALUES (?, ?, ?, ?)");
            $stmtQ->bind_param("isss", $quiz_id, $q['text'], $q['type'], $q['correct_answer']);
            $stmtQ->execute();
            $question_id = $stmtQ->insert_id;

            if (in_array($q['type'], ['mcq', 'true_false'])) {
                foreach ($q['choices'] as $c) {
                    $stmtC = $mysqli->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)");
                    $stmtC->bind_param("isi", $question_id, $c['text'], $c['is_correct']);
                    $stmtC->execute();
                }
            }
        }

        echo json_encode(["message" => "Quiz saved.", "token" => $token]);
        exit;
    }

    if (isset($_POST['delete_question'])) {
        $qid = intval($_POST['delete_question']);
        // Check if this question has any student answers
        $stmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM student_answers WHERE question_id = ?");
        $stmt->bind_param("i", $qid);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if (intval($res['cnt']) > 0) {
            echo json_encode(["success" => false, "message" => "Cannot delete this question. It has already been answered by students."]);
            exit;
        }
        $mysqli->query("DELETE FROM questions WHERE id = $qid");
        $mysqli->query("DELETE FROM choices WHERE question_id = $qid");
        echo json_encode(["message" => "Question deleted."]);
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $mysqli->query("DELETE FROM quizzes WHERE id = $id");
    echo "Deleted";
    exit;
}

if (isset($_GET['get_quiz'])) {
    $id = intval($_GET['get_quiz']);
    $result = $mysqli->query("SELECT * FROM quizzes WHERE id = $id");
    $quiz = $result->fetch_assoc();
    $quiz['questions'] = [];
    $qResult = $mysqli->query("SELECT * FROM questions WHERE quiz_id = $id");
    while ($q = $qResult->fetch_assoc()) {
        $q['choices'] = [];
        if (in_array($q['question_type'], ['mcq', 'true_false'])) {
            $cResult = $mysqli->query("SELECT * FROM choices WHERE question_id = " . $q['id']);
            while ($c = $cResult->fetch_assoc()) {
                $c['is_correct'] = (int)$c['is_correct']; // Force to int (0 or 1)
                $q['choices'][] = $c;
            }
        }
        $quiz['questions'][] = $q;
    }
    header('Content-Type: application/json');
    echo json_encode($quiz);
    exit;
}

// Pagination AJAX endpoint
if (isset($_GET['fetch_quizzes'])) {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 7; // quizzes per page (changed from 10 to 7)
    $offset = ($page - 1) * $perPage;

    $totalRes = $mysqli->query("SELECT COUNT(*) as cnt FROM quizzes");
    $total = $totalRes->fetch_assoc()['cnt'];

    $result = $mysqli->query("SELECT q.*, COUNT(questions.id) AS total_questions FROM quizzes q LEFT JOIN questions ON q.id = questions.quiz_id GROUP BY q.id ORDER BY q.created_at DESC LIMIT $perPage OFFSET $offset");
    $quizzes = [];
    while ($q = $result->fetch_assoc()) {
        $quizzes[] = $q;
    }
    echo json_encode([
        'quizzes' => $quizzes,
        'total' => $total,
        'perPage' => $perPage,
        'page' => $page
    ]);
    exit;
}

$quizzes = $mysqli->query("SELECT q.*, COUNT(questions.id) AS total_questions FROM quizzes q LEFT JOIN questions ON q.id = questions.quiz_id GROUP BY q.id ORDER BY q.created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz Manager</title>
    <link rel="stylesheet" href="quiz.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
/* Remove table-container scrolling if present */
.table-container {
    overflow: unset !important;
    max-height: unset !important;
}
/* Center and style pagination */
#pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 4px;
    margin: 16px 0;
}
#pagination button {
    background: #f0f0f0;
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 4px 10px;
    margin: 0 2px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.2s, color 0.2s;
}
#pagination button.active {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
}
#pagination button:disabled {
    background: #eee;
    color: #aaa;
    cursor: not-allowed;
}
</style>
</head>
<body>
    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
        <div class="shape shape-5"></div>
        <div class="shape shape-6"></div>
    </div>

    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="sidebar-logo-text">QuizMaster+</div>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main Menu</div>
                <a href="home.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-home"></i></span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="quiz.php" class="nav-item active">
                    <span class="nav-icon"><i class="fas fa-list-ul"></i></span>
                    <span class="nav-label">Quiz Manager</span>
                </a>
                <a href="submissions.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
                    <span class="nav-label">Submissions</span>
                </a>
                <a href="report.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
                    <span class="nav-label">Reports</span>
                </a>      
        <a href="logout.php" class="nav-item">
                    <i class="nav-icon fas fa-user"></i>
                    <span class="nav-label">Logout</span>
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Quiz Manager</h1>
                <p class="page-subtitle">Create, manage, and monitor your quizzes</p>
            </div>

            <div class="top-controls">
                <input type="text" id="searchInput" onkeyup="searchQuizzes()" placeholder="Search quizzes...">
                <button class="btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    Create New Quiz
                </button>
            </div>

            <div class="table-container">
                <table border="1" id="quizTable">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Subject</th>
                        <th>Questions</th>
                        <th>Posted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody id="quizTableBody"></tbody>
                </table>
                <div id="pagination"></div>
            </div>
        </div>
    </div>

    <!-- Quiz Modal -->
    <div id="quizModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create/Edit Quiz</h2>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <input type="hidden" id="quizId">
                    <input type="hidden" id="quizToken">
                    <input type="hidden" id="quizStatus">
                    
                    <label for="quizTitle">Quiz Title</label>
                    <input type="text" id="quizTitle" placeholder="Enter quiz title">
                    
                    <label for="quizSubject">Subject</label>
                    <input type="text" id="quizSubject" placeholder="Enter subject">
                    
                    <label for="quizInstructions">Instructions</label>
                    <textarea id="quizInstructions" placeholder="Enter quiz instructions"></textarea>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="quizTime">Time Limit (minutes)</label>
                            <input type="number" id="quizTime" placeholder="30">
                        </div>
                        <div class="form-col">
                            <label for="quizAttempts">Attempts Allowed</label>
                            <input type="number" id="quizAttempts" placeholder="1">
                        </div>
                    </div>
                    
                    <label for="quizDeadline">Deadline</label>
                    <input type="datetime-local" id="quizDeadline">
                </div>
                
                <div class="questions-section">
                    <h3>Questions</h3>
                    <div id="questionsContainer"></div>
                    <button class="btn-secondary" onclick="addQuestion()">
                        <i class="fas fa-plus"></i>
                        Add Question
                    </button>
                </div>
                
                <div class="modal-actions">
                    <button class="btn-secondary" onclick="submitQuiz('draft')">
                        <i class="fas fa-save"></i>
                        Save as Draft
                    </button>
                    <button class="btn-primary" onclick="submitQuiz('published')">
                        <i class="fas fa-paper-plane"></i>
                        Publish Quiz
                    </button>
                    <button class="btn-cancel" onclick="closeModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="viewTitle"></h2>
                <button class="modal-close" onclick="document.getElementById('viewModal').style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="quiz-info">
                    <p><strong>Subject:</strong> <span id="viewSubject"></span></p>
                    <p><strong>Instructions:</strong> <span id="viewInstructions"></span></p>
                    <p><strong>Time Limit:</strong> <span id="viewTime"></span> minutes</p>
                    <p><strong>Attempts Allowed:</strong> <span id="viewAttempts"></span></p>
                    <p><strong>Deadline:</strong> <span id="viewDeadline"></span></p>
                </div>
                <div id="viewQuestions"></div>
            </div>
        </div>
    </div>

    <!-- Publish Overview Modal -->
    <div id="publishOverviewModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Publish Quiz Overview</h2>
          <button class="modal-close" onclick="closePublishOverview()">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body" id="publishOverviewBody">
          <!-- Overview content will be injected here -->
        </div>
        <div class="modal-actions">
          <button class="btn-primary" onclick="confirmPublishQuiz()">Publish Quiz</button>
          <button class="btn-cancel" onclick="closePublishOverview()">Cancel</button>
            </div>
        </div>
    </div>

    <script src="quiz.js"></script>
    
</body>
</html>