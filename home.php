<?php
session_start();

require_once 'db_connect.php';

// Redirect to login page if not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}


$total_quizzes = $mysqli->query("SELECT COUNT(*) AS total FROM quizzes")->fetch_assoc()['total'];
$total_submissions = $mysqli->query("SELECT COUNT(*) AS total FROM student_attempts")->fetch_assoc()['total'];
$pending_grades = $mysqli->query("SELECT COUNT(*) AS total FROM student_attempts WHERE is_graded = 0")->fetch_assoc()['total'];
$completed_quizzes = $mysqli->query("SELECT COUNT(*) AS total FROM quizzes WHERE deadline < NOW()")->fetch_assoc()['total'];
$ongoing_quizzes = $mysqli->query("SELECT COUNT(*) AS total FROM quizzes WHERE deadline >= NOW()")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Master - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
        <div class="shape shape-5"></div>
        <div class="shape shape-6"></div>
    </div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="sidebar-logo-text">QuizMaster+</div>
            </div>
        </div>

        <div class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main Menu</div>
                <a href="home.php" class="nav-item active">
                    <i class="nav-icon fas fa-home"></i>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="quiz.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-list-ul"></i></span>
                    <span class="nav-label">Quiz Manager</span>
                </a>
                <a href="submissions.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
                    <span class="nav-label">Submissions</span>
                </a>
                <a href="report.php" class="nav-item">
                    <i class="nav-icon fas fa-chart-bar"></i>
                    <span class="nav-label">Reports</span>
                </a>
                <a href="logout.php" class="nav-item">
                    <i class="nav-icon fas fa-user"></i>
                    <span class="nav-label">Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Hero Section -->
            <header class="hero-section">
                <div class="hero-content">
                    <h1 class="hero-title">Welcome Back!</h1>
                    <p class="hero-subtitle">Ready to create amazing quizzes and engage your students?</p>
                </div>
                <div class="hero-illustration">
                    <div class="quiz-bubble bubble-1">
                        <i class="fas fa-question"></i>
                    </div>
                    <div class="quiz-bubble bubble-2">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="quiz-bubble bubble-3">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="quiz-bubble bubble-4">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
            </header>

            <section class="dashboard-section">
                <h2 class="section-title">Dashboard Overview</h2>
                
                <div class="stats-grid">
                    <div class="stat-card total-quizzes">
                        <div class="stat-content">
                            <div class="stat-number"><?= $total_quizzes ?></div>
                            <div class="stat-label">Total Quizzes</div>
                        </div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i>
                            <span>+12%</span>
                        </div>
                    </div>

                    <div class="stat-card total-submissions">
                        <div class="stat-content">
                            <div class="stat-number"><?= $total_submissions ?></div>
                            <div class="stat-label">Total Submissions</div>
                        </div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i>
                            <span>+25%</span>
                        </div>
                    </div>

                    <div class="stat-card pending-grades">
                        <div class="stat-content">
                            <div class="stat-number"><?= $pending_grades ?></div>
                            <div class="stat-label">Pending Grades</div>
                        </div>
                        <div class="stat-alert">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>

                    <div class="stat-card completed-quizzes">
                        <div class="stat-content">
                            <div class="stat-number"><?= $completed_quizzes ?></div>
                            <div class="stat-label">Completed Quizzes</div>
                        </div>
                        <div class="stat-trend">
                            <i class="fas fa-check"></i>
                            <span>Done</span>
                        </div>
                    </div>

                    <div class="stat-card ongoing-quizzes">
                        <div class="stat-content">
                            <div class="stat-number"><?= $ongoing_quizzes ?></div>
                            <div class="stat-label">Live Quizzes</div>
                        </div>
                        <div class="stat-pulse">
                            <div class="pulse-dot"></div>
                            <span>Active</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
        // Toggle sidebar for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        // Add interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats on load
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach((stat, index) => {
                setTimeout(() => {
                    stat.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        stat.style.transform = 'scale(1)';
                    }, 200);
                }, index * 100);
            });

            // Add hover effects to action cards
            const actionCards = document.querySelectorAll('.action-card');
            actionCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Floating shapes animation
            const shapes = document.querySelectorAll('.shape');
            shapes.forEach(shape => {
                const randomDelay = Math.random() * 5;
                shape.style.animationDelay = `${randomDelay}s`;
            });
        });
    </script>
</body>
</html>