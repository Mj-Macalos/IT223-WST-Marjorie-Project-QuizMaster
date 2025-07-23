<?php
require_once("auth.php");

require_once 'db_connect.php';

$view_id = isset($_GET['view']) ? intval($_GET['view']) : null;

// Pagination AJAX endpoint for reports
if (isset($_GET['fetch_reports'])) {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 7;
    $offset = ($page - 1) * $perPage;

    $totalRes = $mysqli->query("SELECT COUNT(*) as cnt FROM quizzes");
    $total = $totalRes->fetch_assoc()['cnt'];

    $result = $mysqli->query("SELECT q.*, COUNT(sa.id) AS submissions_count FROM quizzes q LEFT JOIN student_attempts sa ON sa.quiz_id = q.id GROUP BY q.id ORDER BY q.created_at DESC LIMIT $perPage OFFSET $offset");
    $quizzes = [];
    while ($q = $result->fetch_assoc()) {
        // Ensure deadline is included in the output
        $q['deadline'] = $q['deadline'];
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

$quiz_details = null;
$student_scores = [];
if ($view_id) {
    $quiz_details = $mysqli->query("SELECT * FROM quizzes WHERE id = $view_id")->fetch_assoc();
    $student_scores = $mysqli->query("
        SELECT first_name, middle_initial, last_name, suffix, program, year_level, score 
        FROM student_attempts 
        WHERE quiz_id = $view_id
    ")->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz Reports</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="report.css">
</head>
<body>
<div class="floating-shapes">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    <div class="shape shape-4"></div>
    <div class="shape shape-5"></div>
    <div class="shape shape-6"></div>
</div>

<!-- Sidebar -->
<button class="mobile-menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo"><div class="sidebar-logo-text">QuizMaster+</div></div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Main Menu</div>
            <a href="home.php" class="nav-item"><span class="nav-icon"><i class="fas fa-home"></i></span><span class="nav-label">Dashboard</span></a>
            <a href="quiz.php" class="nav-item"><span class="nav-icon"><i class="fas fa-list-ul"></i></span><span class="nav-label">Quiz Manager</span></a>
            <a href="submissions.php" class="nav-item"><span class="nav-icon"><i class="fas fa-file-alt"></i></span><span class="nav-label">Submissions</span></a>
            <a href="report.php" class="nav-item active"><span class="nav-icon"><i class="fas fa-chart-bar"></i></span><span class="nav-label">Reports</span></a>
            <a href="logout.php" class="nav-item"><i class="nav-icon fas fa-user"></i><span class="nav-label">Logout</span></a>
        </div>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Quiz Reports</h1>
            <p class="page-subtitle">Monitor quiz performance and student submissions</p>
        </div>

        <?php if (!$quiz_details): ?>
        <div class="report-search no-print">
            <input type="text" id="searchBox" placeholder="Search reports..." onkeyup="searchQuizzes()">
        </div>

        <div class="table-container">
            <table id="quizTable" class="no-print">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Subject</th>
                        <th>Date Published</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="quizTableBody"></tbody>
            </table>
            <div id="pagination"></div>
        </div>
        <?php endif; ?>

        <?php if ($quiz_details): ?>
        <div id="printSection">
            <div class="report-actions no-print">
                <button onclick="window.location.href='report.php'" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Reports
                </button>
                <button onclick="printCurrentView()" class="btn-print">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>

            <div class="quiz-detail-info">
                <h3><?= htmlspecialchars($quiz_details['title']) ?></h3>
                <p><strong>Subject:</strong> <?= htmlspecialchars($quiz_details['subject']) ?></p>
                <p><strong>Date Published:</strong> <?= $quiz_details['deadline'] ?></p>
                <p><strong>Total Submissions:</strong> <?= count($student_scores) ?></p>
            </div>

            <div class="scores-section">
                <h4>Student Scores</h4>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Program</th>
                                <th>Year Level</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($student_scores as $s): ?>
                            <?php
                                $full_name = trim("{$s['first_name']} {$s['middle_initial']} {$s['last_name']} {$s['suffix']}");
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($full_name) ?></td>
                                <td><?= htmlspecialchars($s['program']) ?></td>
                                <td><?= htmlspecialchars($s['year_level']) ?></td>
                                <td><?= $s['score'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="report.js"></script>

</body>
</html>
