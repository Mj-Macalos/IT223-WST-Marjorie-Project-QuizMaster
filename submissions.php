<?php
require_once("auth.php");
require_once 'db_connect.php';

// Handle AJAX request for modal content
if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && isset($_GET['view'])) {
    $attempt_id = intval($_GET['view']);
    $attempt = $mysqli->query("
        SELECT sa.*, q.title AS quiz_title, q.id AS quiz_id 
        FROM student_attempts sa 
        JOIN quizzes q ON sa.quiz_id = q.id
        WHERE sa.id = $attempt_id
    ")->fetch_assoc();

    $answers = $mysqli->query("
        SELECT a.*, qs.question_text, qs.question_type, qs.correct_answer, qs.id as question_id 
        FROM student_answers a 
        JOIN questions qs ON a.question_id = qs.id 
        WHERE a.attempt_id = $attempt_id
    ");
    
    echo '<div class="grade-form">';
    echo '<div class="student-details">';
    echo '<div class="student-info-card">';
    echo '<h3>Student Information</h3>';
   echo '<div class="info-row"><strong>Student:</strong> ' . htmlspecialchars(trim("{$attempt['first_name']} {$attempt['middle_initial']} {$attempt['last_name']} {$attempt['suffix']}")) . '</div>';
    echo '<div class="info-row"><strong>Program/Year:</strong> ' . $attempt['program'] . ' - ' . $attempt['year_level'] . '</div>';
    echo '<div class="info-row"><strong>Email:</strong> ' . $attempt['email'] . '</div>';
    echo '<div class="info-row"><strong>Quiz:</strong> ' . $attempt['quiz_title'] . '</div>';
    echo '</div>';
    echo '</div>';
    
    $isFinalized = intval($attempt['is_graded']) === 1;
$formAttr = $isFinalized ? 'onsubmit="return false;"' : 'method="POST" action="submissions.php"';

echo "<form $formAttr id='gradingForm'>";
echo '<input type="hidden" name="attempt_id" value="' . $attempt_id . '">';

    
    $question_number = 1;
    while ($a = $answers->fetch_assoc()) {
        echo '<div class="question-preview">';
        echo '<div class="question-preview-header">';
        echo '<div class="question-preview-number">Question ' . $question_number . '</div>';
        echo '<div class="question-preview-type">' . ucfirst(str_replace('_', ' ', $a['question_type'])) . '</div>';
        echo '</div>';
        
        echo '<div class="question-preview-text">' . htmlspecialchars($a['question_text']) . '</div>';
        
        echo '<div class="student-answer-section">';
        echo '<div class="answer-label">Student Answer:</div>';
        echo '<div class="answer-text">' . nl2br(htmlspecialchars($a['answer'])) . '</div>';
        echo '</div>';

        $isCorrect = false;
        if (in_array($a['question_type'], ['mcq', 'true_false'])) {
            $qid = intval($a['question_id']);
            $correctIndex = intval($a['correct_answer']);
            $res = $mysqli->query("SELECT choice_text FROM choices WHERE question_id = $qid ORDER BY id ASC LIMIT 1 OFFSET $correctIndex");
            $correctChoice = $res->fetch_assoc()['choice_text'] ?? '';
            $isCorrect = trim(strtolower($a['answer'])) === trim(strtolower($correctChoice));
        } elseif ($a['question_type'] === 'short_answer') {
            $isCorrect = trim(strtolower($a['answer'])) === trim(strtolower($a['correct_answer']));
        }

        if ($a['question_type'] === 'essay') {
            echo '<div class="manual-score-section">';
            echo '<label class="score-label">Manual Score:</label>';
            $manualScoreVal = isset($a['manual_score']) ? htmlspecialchars($a['manual_score']) : '';
            if ($isFinalized) {
                echo '<span class="final-score-display">' . ($manualScoreVal !== '' ? $manualScoreVal : '—') . '</span>';
            } else {
                echo '<input type="number" step="0.1" name="manual_score[' . $a['question_id'] . ']" min="0" required class="score-input" value="' . $manualScoreVal . '">';
            }
            echo '</div>';
        } else {
            echo '<div class="auto-grade-section">';
            echo '<div class="grade-result ' . ($isCorrect ? 'correct' : 'incorrect') . '">';
            echo '<div class="grade-icon">' . ($isCorrect ? '✅' : '❌') . '</div>';
            echo '<div class="grade-text">' . ($isCorrect ? 'Correct' : 'Incorrect') . '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        $question_number++;
    }
    
    echo '<div class="modal-actions">';
    // Check if the quiz has essay questions
    $essayQ = $mysqli->query("SELECT COUNT(*) as cnt FROM questions WHERE quiz_id = {$attempt['quiz_id']} AND question_type = 'essay'");
    $hasEssay = $essayQ ? intval($essayQ->fetch_assoc()['cnt']) : 0;
    if ($hasEssay > 0 && !$isFinalized) {
        echo '<button type="submit" name="grade" class="btn-primary" onclick="return confirm(\'Are you sure you want to finalize the grading?\')">Finalize Grade</button>';
    }
    
    echo '<button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>';
    echo '</div>';
    echo '</form>';
    echo '</div>';
    
    exit;
}

// Handle grading submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade'])) {
    $attempt_id = intval($_POST['attempt_id']);
    $manual_scores = $_POST['manual_score'] ?? [];

    // Fetch current score
    $attempt = $mysqli->query("SELECT score FROM student_attempts WHERE id = $attempt_id")->fetch_assoc();
    $total_score = floatval($attempt['score']);

    // Add manual scores
    foreach ($manual_scores as $qid => $score) {
        $total_score += floatval($score);
    }

    // Update score and mark as graded
    $mysqli->query("UPDATE student_attempts SET score = $total_score, is_graded = 1 WHERE id = $attempt_id");

    echo "<script>alert('Submission graded successfully.'); window.location.href='submissions.php';</script>";
    exit;
}

// Pagination AJAX endpoint for submissions
if (isset($_GET['fetch_submissions'])) {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 7;
    $offset = ($page - 1) * $perPage;

    $totalRes = $mysqli->query("SELECT COUNT(*) as cnt FROM student_attempts");
    $total = $totalRes->fetch_assoc()['cnt'];

    $result = $mysqli->query("
        SELECT sa.*, q.title AS quiz_title, q.id AS quiz_id 
        FROM student_attempts sa 
        JOIN quizzes q ON sa.quiz_id = q.id
        ORDER BY sa.submitted_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $submissions = [];
    while ($row = $result->fetch_assoc()) {
        // Get total auto-graded questions for this attempt
        $attempt_id = intval($row['id']);
        $quiz_id = intval($row['quiz_id']);
        $autoQ = $mysqli->query("SELECT COUNT(*) as cnt FROM student_answers a JOIN questions q ON a.question_id = q.id WHERE a.attempt_id = $attempt_id AND q.question_type IN ('mcq','true_false','short_answer')");
        $autoTotal = $autoQ ? intval($autoQ->fetch_assoc()['cnt']) : 0;
        $row['auto_total'] = $autoTotal;
        // Check if the quiz has essay questions
        $essayQ = $mysqli->query("SELECT COUNT(*) as cnt FROM questions WHERE quiz_id = $quiz_id AND question_type = 'essay'");
        $hasEssay = $essayQ ? intval($essayQ->fetch_assoc()['cnt']) : 0;
        $row['has_essay'] = $hasEssay;
        // If no essay, mark as graded (is_graded = 1)
        if ($hasEssay == 0 && $row['is_graded'] == 0) {
            $mysqli->query("UPDATE student_attempts SET is_graded = 1 WHERE id = $attempt_id");
            $row['is_graded'] = 1;
        }
        $submissions[] = $row;
    }
    echo json_encode([
        'submissions' => $submissions,
        'total' => $total,
        'perPage' => $perPage,
        'page' => $page
    ]);
    exit;
}

// Fetch submissions
// $result = $mysqli->query("
//     SELECT sa.*, q.title AS quiz_title 
//     FROM student_attempts sa 
//     JOIN quizzes q ON sa.quiz_id = q.id
//     ORDER BY sa.submitted_at DESC
// ");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Submissions</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="submission.css">
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
                <a href="quiz.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-list-ul"></i></span>
                    <span class="nav-label">Quiz Manager</span>
                </a>
                <a href="submissions.php" class="nav-item active">
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
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Quiz Submissions</h1>
                <p class="page-subtitle">Review and grade student submissions</p>
            </div>

            <!-- Top Controls -->
            <div class="top-controls">
                <input type="text" id="searchInput" placeholder="Search submissions...">
            </div>

            <!-- Table Container -->
            <div class="table-container">
                <table id="submissionsTable">
                    <thead>
                        <tr>
                            <th>Student Information</th>
                            <th>Quiz Title</th>
                            <th>Score</th>
                            <th>Submitted At</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="submissionsTableBody"></tbody>
                </table>
                <div id="pagination"></div>
            </div>
        </div>
    </div>

    <!-- Grading Modal -->
    <div id="gradingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Grade Submission</h2>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <script src="submissions.js"></script>
</body>
</html>