<?php
session_start();
// Unset all session variables
$_SESSION = array();

//delte the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

//destroy the session
session_destroy();

// return json response for ajax requests
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
exit;
