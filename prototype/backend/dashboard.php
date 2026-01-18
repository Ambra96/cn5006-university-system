<?php
//start session for logged in user and show dashboard info according to their role
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['authenticated' => false]);
    exit;
}

$user = $_SESSION['user'];

echo json_encode([
    'authenticated' => true,
    'user' => $user,
    'role_name' => $user['role_id'] === 1 ? 'student' : 'teacher'
]);
