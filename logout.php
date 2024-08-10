<?php
// Start or resume session
session_start();

// Check if the user is logged in (i.e., session variables are set)
if (isset($_SESSION['userID'])) {
    // Unset all session variables
    $_SESSION = [];

    // Destroy the session
    session_destroy();
}

// Redirect to login page
header("Location: /login");
exit();
?>
