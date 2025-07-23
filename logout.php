<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    // Clear all session variables
    $_SESSION = [];

    // Expire the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();

    // Redirect to login
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Logout</title>
    <script>
        window.onload = function () {
            const answer = confirm("Are you sure you want to logout?");
            if (answer) {
                // Submit the hidden form to logout
                document.getElementById('logoutForm').submit();
            } else {
                // Go back if user cancels
                window.location.href = "home.php"; // or wherever you want to go back
            }
        };
    </script>
</head>
<body>
    <form id="logoutForm" method="POST" style="display:none;">
        <input type="hidden" name="confirm" value="yes">
    </form>
</body>
</html>
