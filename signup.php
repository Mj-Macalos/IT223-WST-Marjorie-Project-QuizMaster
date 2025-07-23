<?php
require_once 'db_connect.php';

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = password_hash(trim($_POST["password"]), PASSWORD_DEFAULT);

    // Check if email already exists (optional, but recommended)
    $check = $mysqli->prepare("SELECT id FROM user WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "An account with this email already exists.";
    } else {
        $stmt = $mysqli->prepare("INSERT INTO user (full_name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $full_name, $email, $password);

        if ($stmt->execute()) {
            $success = "Registration successful! Redirecting to login...";
            header("refresh:2;url=login.php");
        } else {
            $error = "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    $check->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up - QuizMaster+</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .signup-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1), 0 10px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .brand-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .brand-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .brand-logo::before {
            content: "📚";
            font-size: 28px;
            filter: brightness(0) invert(1);
        }
        
        .brand-title {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 5px;
        }
        
        .brand-subtitle {
            font-size: 14px;
            color: #666;
            font-weight: 400;
        }
        
        .form-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 18px;
            z-index: 1;
        }
        
        .form-input {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-input::placeholder {
            color: #999;
            font-weight: 400;
        }
        
        .signup-button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .signup-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .signup-button:active {
            transform: translateY(0);
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid #fcc;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .success {
            background: #efe;
            color: #5c7;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid #cfc;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .login-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 15%;
            left: 85%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 60%;
            left: 10%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 40px;
            height: 40px;
            top: 30%;
            left: 5%;
            animation-delay: 4s;
        }
        
        .shape:nth-child(4) {
            width: 100px;
            height: 100px;
            top: 80%;
            left: 90%;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .feature-highlight {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }
        
        .feature-highlight h3 {
            color: #667eea;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .feature-highlight p {
            color: #666;
            font-size: 14px;
            line-height: 1.4;
            text-justify    : center;
        }
        
        @media (max-width: 480px) {
            .signup-container {
                padding: 30px 20px;
            }
            
            .brand-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="floating-shapes">
    <div class="shape"></div>
    <div class="shape"></div>
    <div class="shape"></div>
    <div class="shape"></div>
</div>

<div class="signup-container">
    <div class="brand-header">
        <div class="brand-logo"></div>
        <h1 class="brand-title">QuizMaster+</h1>
        <p class="brand-subtitle">Join as a Teacher</p>
    </div>
    
    <div class="feature-highlight">
        <h3>🎯 Exclusive Teacher Access</h3>
        <p>Create and manage quizzes</p>
    </div>
    
    <form method="POST" action="">
        <?php if ($error): ?>
            <div class="error">
                <span>⚠️</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                <span>✅</span>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <i>👤</i>
            <input type="text" name="full_name" class="form-input" placeholder="Enter your full name" required>
        </div>
        
        <div class="form-group">
            <i>📧</i>
            <input type="email" name="email" class="form-input" placeholder="Enter your email address" required>
        </div>
        
        <div class="form-group">
            <i>🔒</i>
            <input type="password" name="password" class="form-input" placeholder="Create a secure password" required>
        </div>

        <button type="submit" class="signup-button">
            Create Teacher Account
        </button>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </form>
</div>

</body>
</html>