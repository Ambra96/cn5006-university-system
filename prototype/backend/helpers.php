<?php
//role based access control helper functions

function isAuthenticated()
{
    return isset($_SESSION['user']) && isset($_SESSION['user']['id']);
}

function isStudent()
{
    return isAuthenticated() && $_SESSION['user']['role_id'] === 1;
}

function isTeacher()
{
    return isAuthenticated() && $_SESSION['user']['role_id'] === 2;
}

function requireAuth()
{
    if (!isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
}

function requireStudent()
{
    requireAuth();
    if (!isStudent()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden Action - Student access only']);
        exit;
    }
}

function requireTeacher()
{
    requireAuth();
    if (!isTeacher()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden Action - Teacher access only']);
        exit;
    }
}

function getUserId()
{
    requireAuth();
    return $_SESSION['user']['id'];
}

function getUserRole()
{
    requireAuth();
    return $_SESSION['user']['role_id'];
}

function getRoleName()
{
    requireAuth();
    return $_SESSION['user']['role_id'] === 1 ? 'student' : 'teacher';
}
