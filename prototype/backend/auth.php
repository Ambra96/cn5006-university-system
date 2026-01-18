<?php
session_start();
header('Content-Type: application/json');

//some tests for debugging
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sanitize.php';

$db = (new DatabaseConnection())->getConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Invalid request method.']]);
    exit;
}

// read input
$mode        = s_string($_POST['mode'] ?? '');
$email       = s_email($_POST['email'] ?? '');
$password    = s_string($_POST['password'] ?? '');
$role        = s_string($_POST['role'] ?? '');
$fullname    = s_string($_POST['fullname'] ?? '');
$specialCode = s_string($_POST['special_code'] ?? '');

// base validation
if (!in_array($mode, ['login', 'signup'], true)) {
    $errors[] = 'Invalid form mode.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Enter a valid email address.';
}

if (strlen($password) < 4) {
    $errors[] = 'Password must be at least 4 characters.';
}

if (!in_array($role, ['student', 'teacher'], true)) {
    $errors[] = 'Invalid role selected.';
}

/* ----------------signup setcion---------------- */
if ($mode === 'signup') {

    if (strlen($fullname) < 3) {
        $errors[] = 'Full name must be at least 3 characters.';
    }

    $validStudent = ($role === 'student' && $specialCode === 'STUD2025');
    $validTeacher = ($role === 'teacher' && $specialCode === 'PROF2025');

    if (!$validStudent && !$validTeacher) {
        $errors[] = 'Invalid registration code.';
    }

    if ($errors) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    // email exists?
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);

    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'errors' => ['Email already registered.']]);
        exit;
    }

    // create user
    $hash   = password_hash($password, PASSWORD_DEFAULT);
    $roleId = ($role === 'student') ? 1 : 2;

    try {
        $stmt = $db->prepare("
        INSERT INTO users (role_id, user_name, email, password)
        VALUES (:role_id, :user_name, :email, :password)
    ");
        $stmt->execute([
            'role_id'   => $roleId,
            'user_name' => $fullname,
            'email'     => $email,
            'password'  => $hash,
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully. You can now sign in.'
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'errors' => ['Database error: ' . $e->getMessage()]
        ]);
        exit;
    }
}

/* ----------------login section---------------- */
if ($mode === 'login') {

    $stmt = $db->prepare("
        SELECT id, user_name, email, password, role_id
        FROM users
        WHERE email = :email
        LIMIT 1
    ");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'errors' => ['Incorrect email or password.']]);
        exit;
    }

    if (
        ($role === 'student' && $user['role_id'] != 1) ||
        ($role === 'teacher' && $user['role_id'] != 2)
    ) {
        echo json_encode(['success' => false, 'errors' => ['Role mismatch for this account.']]);
        exit;
    }

    $_SESSION['user'] = [
        'id'        => $user['id'],
        'user_name' => $user['user_name'],
        'email'     => $user['email'],
        'role_id'   => $user['role_id'],
    ];

    echo json_encode(['success' => true]);
    exit;
}
