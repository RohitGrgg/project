<?php
// Start the session
session_start();

// Destroy the session to log out the user
session_unset();   // Unset all session variables
session_destroy(); // Destroy the session

// Clear session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to the login or front page
header('Location: FRONT.php');
exit;
?>
