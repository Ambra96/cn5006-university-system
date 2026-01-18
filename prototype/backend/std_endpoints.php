<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// RBAC check - only students allowed
if (!isStudent()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden Action']);
    exit;
}

$db = (new DatabaseConnection())->getConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user']['id'];

try {
    switch ($action) {
        case 'overview':
            handleOverview($db, $userId);
            break;

        case 'courses':
            handleCourses($db, $userId);
            break;

        case 'assignments':
            handleAssignments($db, $userId);
            break;

        case 'grades':
            handleGrades($db, $userId);
            break;

        case 'submit_assignment':
            handleSubmitAssignment($db, $userId);
            break;

        case 'available_courses':
            handleAvailableCourses($db, $userId);
            break;

        case 'enroll':
            handleEnroll($db, $userId);
            break;

        case 'unenroll':
            handleUnenroll($db, $userId);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/* =========================================================
   AVAILABLE COURSES
========================================================= */
function handleAvailableCourses($db, $userId)
{
    $stmt = $db->prepare("
        SELECT
            u.id,
            u.sub_code,
            u.sub_title,
            u.sub_description,
            u.sub_semester,
            t.user_name AS teacher_name
        FROM univ_subjects u
        INNER JOIN teacher_subjects ts
            ON ts.subject_id = u.id
           AND ts.is_active = 1
        INNER JOIN users t
            ON ts.teacher_id = t.id
        WHERE u.id NOT IN (
            SELECT subject_id
            FROM student_registrations
            WHERE student_id = :student_id
        )
        ORDER BY u.sub_code
    ");
    $stmt->execute(['student_id' => $userId]);

    echo json_encode(['success' => true, 'courses' => $stmt->fetchAll()]);
}

/* =========================================================
   ENROLL / UNENROLL
========================================================= */
function handleEnroll($db, $userId)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request');
    }

    $subjectId = $_POST['subject_id'] ?? null;
    if (!$subjectId) {
        throw new Exception('Subject required');
    }

    $stmt = $db->prepare("
        INSERT INTO student_registrations (student_id, subject_id)
        SELECT :student_id_1, :subject_id_1
        WHERE NOT EXISTS (
            SELECT 1
            FROM student_registrations
            WHERE student_id = :student_id_2
              AND subject_id = :subject_id_2
        )
    ");

    $stmt->execute([
        'student_id_1' => $userId,
        'subject_id_1' => $subjectId,
        'student_id_2' => $userId,
        'subject_id_2' => $subjectId,
    ]);

    echo json_encode(['success' => true]);
}


function handleUnenroll($db, $userId)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request');
    }

    $subjectId = $_POST['subject_id'] ?? null;
    if (!$subjectId) throw new Exception('Subject required');

    $stmt = $db->prepare("
        DELETE FROM student_registrations
        WHERE student_id = :student_id AND subject_id = :subject_id
    ");
    $stmt->execute([
        'student_id' => $userId,
        'subject_id' => $subjectId
    ]);

    echo json_encode(['success' => true]);
}



//SUBMIT ASSIGNMENT (PDF UPLOAD )

function handleSubmitAssignment($db, $userId)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $assignmentId   = (int) ($_POST['assignment_id'] ?? 0);
    $submissionText = trim($_POST['submission_text'] ?? '');

    if (!$assignmentId) {
        throw new Exception('Assignment ID is required');
    }

    // Check enrollment
    $stmt = $db->prepare("
        SELECT 1
        FROM student_registrations r
        INNER JOIN assignments a ON r.subject_id = a.subject_id
        WHERE r.student_id = :student_id
          AND a.id = :assignment_id
    ");
    $stmt->execute([
        'student_id'    => $userId,
        'assignment_id' => $assignmentId
    ]);

    if (!$stmt->fetch()) {
        throw new Exception('You are not enrolled in this course');
    }

    // Prevent duplicate submission
    $stmt = $db->prepare("
        SELECT id FROM student_submissions
        WHERE task_id = :task_id AND student_id = :student_id
    ");
    $stmt->execute([
        'task_id'    => $assignmentId,
        'student_id' => $userId
    ]);

    if ($stmt->fetch()) {
        throw new Exception('You have already submitted this assignment');
    }

    /* ---------- PDF UPLOAD ---------- */
    if (!isset($_FILES['assignment_pdf'])) {
        throw new Exception('PDF file is required');
    }

    $file = $_FILES['assignment_pdf'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed');
    }

    if (mime_content_type($file['tmp_name']) !== 'application/pdf') {
        throw new Exception('Only PDF files are allowed');
    }

    // ✅ CORRECT PATH
    $uploadDir = __DIR__ . '/uploads/assignments/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!is_writable($uploadDir)) {
        throw new Exception('Upload directory is not writable');
    }

    $filename = uniqid('assignment_', true) . '.pdf';
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save uploaded file');
    }

    /* ---------- DB INSERT ---------- */
    $stmt = $db->prepare("
        INSERT INTO student_submissions
        (task_id, student_id, sub_comments, file_path)
        VALUES (:task_id, :student_id, :comments, :file_path)
    ");
    $stmt->execute([
        'task_id'    => $assignmentId,
        'student_id' => $userId,
        'comments'   => $submissionText,
        'file_path'  => 'uploads/assignments/' . $filename
    ]);

    echo json_encode(['success' => true]);
}



function handleOverview($db, $userId)
{
    /* ===============================
       TOTAL ENROLLED COURSES
    =============================== */
    $stmt = $db->prepare("
        SELECT COUNT(*) AS total
        FROM student_registrations
        WHERE student_id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);
    $totalCourses = (int) $stmt->fetch()['total'];


    /* ===============================
       PENDING ASSIGNMENTS
    =============================== */
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT a.id) AS total
        FROM assignments a
        INNER JOIN student_registrations r 
            ON a.subject_id = r.subject_id
        LEFT JOIN student_submissions s
            ON a.id = s.task_id AND s.student_id = :student_id
        WHERE r.student_id = :student_id_2
          AND s.id IS NULL
          AND (a.tsk_due_date IS NULL OR a.tsk_due_date > NOW())
    ");
    $stmt->execute([
        'student_id'   => $userId,
        'student_id_2' => $userId
    ]);
    $pendingAssignments = (int) $stmt->fetch()['total'];


    /* ===============================
       AVERAGE GRADE
    =============================== */
    $stmt = $db->prepare("
        SELECT AVG(grade) AS avg_score
        FROM student_submissions
        WHERE student_id = :user_id
          AND grade IS NOT NULL
    ");
    $stmt->execute(['user_id' => $userId]);
    $avg = $stmt->fetch()['avg_score'];
    $averageGrade = $avg ? round($avg, 1) : null;


    /* ===============================
       RECENT ACTIVITY (LAST 5)
       - enrollments
       - submissions
    =============================== */
    $stmt = $db->prepare("
        (
            SELECT
                'enroll' AS type,
                u.sub_title AS title,
                r.enrolled_at AS activity_time
            FROM student_registrations r
            INNER JOIN univ_subjects u ON r.subject_id = u.id
            WHERE r.student_id = :user_id_1
        )
        UNION ALL
        (
            SELECT
                'submission' AS type,
                a.tsk_title AS title,
                s.submitted_at AS activity_time
            FROM student_submissions s
            INNER JOIN assignments a ON s.task_id = a.id
            WHERE s.student_id = :user_id_2
        )
        ORDER BY activity_time DESC
        LIMIT 5
    ");

    $stmt->execute([
        'user_id_1' => $userId,
        'user_id_2' => $userId
    ]);

    $activityRows = $stmt->fetchAll();

    $recentActivity = array_map(function ($row) {
        return [
            'icon' => $row['type'] === 'submission' ? 'upload' : 'book',
            'message' => $row['type'] === 'submission'
                ? 'You submitted "' . $row['title'] . '"'
                : 'You enrolled in "' . $row['title'] . '"',
            'time' => date('d/m/Y H:i', strtotime($row['activity_time']))
        ];
    }, $activityRows);


    /* ===============================
       FINAL RESPONSE
    =============================== */
    echo json_encode([
        'success' => true,
        'total_courses' => $totalCourses,
        'pending_assignments' => $pendingAssignments,
        'average_grade' => $averageGrade,
        'recent_activity' => $recentActivity
    ]);
}

function handleCourses($db, $userId)
{

    $stmt = $db->prepare("
    SELECT
        u.id,
        u.sub_code AS course_code,
        u.sub_title AS course_name,
        u.sub_description AS description,
        u.sub_semester AS semester,
        t.user_name AS teacher_name
    FROM student_registrations r
    INNER JOIN univ_subjects u ON r.subject_id = u.id
    INNER JOIN teacher_subjects ts
        ON ts.subject_id = u.id
       AND ts.is_active = 1
    INNER JOIN users t
        ON ts.teacher_id = t.id
    WHERE r.student_id = :user_id
    ORDER BY u.created_at DESC
");

    $stmt->execute(['user_id' => $userId]);

    echo json_encode([
        'success' => true,
        'courses' => $stmt->fetchAll()
    ]);
}

function handleAssignments($db, $userId)
{

    $stmt = $db->prepare("
        SELECT
            a.id,
            a.tsk_title AS title,
            a.tsk_description AS description,
            a.tsk_due_date AS due_date,
            u.sub_code,
            u.sub_title AS course_name,
            s.id AS submission_id,
            s.submitted_at,
            s.grade,
            CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END AS submitted
        FROM assignments a
        INNER JOIN univ_subjects u ON a.subject_id = u.id
        INNER JOIN student_registrations r ON u.id = r.subject_id
        LEFT JOIN student_submissions s
            ON a.id = s.task_id AND s.student_id = :student_id
        WHERE r.student_id = :student_id_2
        ORDER BY 
            CASE WHEN s.id IS NULL THEN 0 ELSE 1 END,
            a.tsk_due_date ASC
    ");
    $stmt->execute(['student_id' => $userId, 'student_id_2' => $userId]);

    echo json_encode([
        'success' => true,
        'assignments' => $stmt->fetchAll()
    ]);
}


function handleGrades($db, $userId)
{

    $stmt = $db->prepare("
        SELECT
            s.grade,
            s.tch_feedback AS feedback,
            s.submitted_at AS submitted_at,
            a.tsk_title AS assignment_title,
            u.sub_code,
            u.sub_title AS course_name
        FROM student_submissions s
        INNER JOIN assignments a ON s.task_id = a.id
        INNER JOIN univ_subjects u ON a.subject_id = u.id
        WHERE s.student_id = :user_id
        AND s.grade IS NOT NULL
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute(['user_id' => $userId]);

    echo json_encode([
        'success' => true,
        'grades' => $stmt->fetchAll()
    ]);
}
