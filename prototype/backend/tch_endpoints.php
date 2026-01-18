<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// RBAC check - only teachers allowed
if (!isTeacher()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden Action']);
    exit;
}

$db = (new DatabaseConnection())->getConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user']['id'];

try {
    switch ($action) {

        // Dashboard cards
        case 'overview':
            handleOverview($db, $userId);
            break;

        // Teacher sees ALL subjects (catalog) + indicates which ones they teach
        case 'subjects_catalog':
            handleSubjectsCatalog($db, $userId);
            break;

        // Teacher “takes” a subject for a semester
        case 'take_subject':
            handleTakeSubject($db, $userId);
            break;

        // Teacher leaves a subject (deactivate)
        case 'leave_subject':
            handleLeaveSubject($db, $userId);
            break;

        // Assignments for a subject the teacher teaches
        case 'course_assignments':
            handleCourseAssignments($db, $userId);
            break;

        // Create assignment (only if teacher teaches subject)
        case 'create_assignment':
            handleCreateAssignment($db, $userId);
            break;

        // Teacher submissions list (only for subjects teacher teaches)
        case 'submissions':
            handleSubmissions($db, $userId);
            break;

        // Grade a submission (only for teacher’s subjects)
        case 'grade_submission':
            handleGradeSubmission($db, $userId);
            break;
        case 'create_course':
            handleCreateCourse($db, $userId);
            break;


        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


/**
 * 1) OVERVIEW
 * total_subjects_taught, pending_grading, total_students_in_my_subjects
 */
function handleOverview($db, $userId)
{
    // total subjects taught (active)
    $stmt = $db->prepare("
        SELECT COUNT(*) AS total
        FROM teacher_subjects
        WHERE teacher_id = :uid AND is_active = 1
    ");
    $stmt->execute(['uid' => $userId]);
    $totalTaught = (int)($stmt->fetch()['total'] ?? 0);

    // pending grading for my taught subjects
    $stmt = $db->prepare("
        SELECT COUNT(*) AS total
        FROM student_submissions s
        INNER JOIN assignments a ON s.task_id = a.id
        INNER JOIN teacher_subjects ts ON ts.subject_id = a.subject_id
        WHERE ts.teacher_id = :uid
          AND ts.is_active = 1
          AND s.grade IS NULL
    ");
    $stmt->execute(['uid' => $userId]);
    $pending = (int)($stmt->fetch()['total'] ?? 0);

    // total distinct students across my taught subjects
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT r.student_id) AS total
        FROM student_registrations r
        INNER JOIN teacher_subjects ts ON ts.subject_id = r.subject_id
        WHERE ts.teacher_id = :uid
          AND ts.is_active = 1
    ");
    $stmt->execute(['uid' => $userId]);
    $totalStudents = (int)($stmt->fetch()['total'] ?? 0);

    echo json_encode([
        'success' => true,
        'total_courses' => $totalTaught,
        'pending_grading' => $pending,
        'total_students' => $totalStudents
    ]);
}

function handleCreateCourse($db, $userId)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $code        = trim($_POST['sub_code'] ?? '');
    $title       = trim($_POST['sub_title'] ?? '');
    $description = trim($_POST['sub_description'] ?? '');
    $semester    = trim($_POST['sub_semester'] ?? '');

    if (!$code || !$title) {
        throw new Exception('Course code and title are required');
    }

    // prevent duplicate codes
    $stmt = $db->prepare("SELECT id FROM univ_subjects WHERE sub_code = :c");
    $stmt->execute(['c' => $code]);
    if ($stmt->fetch()) {
        throw new Exception('Course code already exists');
    }

    $stmt = $db->prepare("
        INSERT INTO univ_subjects
            (sub_code, sub_title, sub_description, sub_semester)
        VALUES
            (:code, :title, :descr, :sem)
    ");

    $stmt->execute([
        'code'  => $code,
        'title' => $title,
        'descr' => $description ?: null,
        'sem'   => $semester ?: null,
    ]);

    echo json_encode(['success' => true]);
}

/**
 * 2) SUBJECTS CATALOG (ALL subjects)
 * Adds:
 * - is_teaching (0/1)
 * - student_count (for THIS teacher if teaching, else 0)
 */
function handleSubjectsCatalog($db, $userId)
{
    $stmt = $db->prepare("
        SELECT
            s.id,
            s.sub_code,
            s.sub_title,
            s.sub_description,
            s.sub_semester,
            s.created_at,

            CASE WHEN ts.id IS NULL THEN 0 ELSE 1 END AS is_teaching,
            ts.semester AS teaching_semester,

            CASE
                WHEN ts.id IS NULL THEN 0
                ELSE (
                    SELECT COUNT(*)
                    FROM student_registrations r
                    WHERE r.subject_id = s.id
                )
            END AS student_count

        FROM univ_subjects s
        LEFT JOIN teacher_subjects ts
          ON ts.subject_id = s.id
         AND ts.teacher_id = :uid
         AND ts.is_active = 1

        ORDER BY s.created_at DESC
    ");
    $stmt->execute(['uid' => $userId]);

    echo json_encode([
        'success' => true,
        'subjects' => $stmt->fetchAll()
    ]);
}


/**
 * 3) TAKE SUBJECT (teacher enrolls to teach it for a semester)
 */
function handleTakeSubject($db, $userId)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $subjectId = $_POST['subject_id'] ?? null;
    $semester  = trim($_POST['semester'] ?? '');

    if (!$subjectId || !$semester) {
        throw new Exception('Subject ID and semester are required');
    }

    // prevent duplicates for same semester
    // check if relation already exists (active or inactive)
    $stmt = $db->prepare("
    SELECT id, is_active
    FROM teacher_subjects
    WHERE teacher_id = :uid
      AND subject_id = :sid
      AND semester = :sem
    LIMIT 1
");
    $stmt->execute([
        'uid' => $userId,
        'sid' => $subjectId,
        'sem' => $semester
    ]);

    $existing = $stmt->fetch();

    if ($existing) {

        // case 1: already active → block
        if ((int)$existing['is_active'] === 1) {
            throw new Exception('You already teach this subject for this semester');
        }

        // case 2: exists but inactive → reactivate
        $stmt = $db->prepare("
        UPDATE teacher_subjects
        SET is_active = 1
        WHERE id = :id
    ");
        $stmt->execute(['id' => $existing['id']]);
    } else {

        // case 3: no row → insert new
        $stmt = $db->prepare("
        INSERT INTO teacher_subjects (teacher_id, subject_id, semester, is_active)
        VALUES (:uid, :sid, :sem, 1)
    ");
        $stmt->execute([
            'uid' => $userId,
            'sid' => $subjectId,
            'sem' => $semester
        ]);
    }


    echo json_encode(['success' => true]);
}


/**
 * 4) LEAVE SUBJECT (deactivate current active link)
 */
function handleLeaveSubject($db, $userId)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $subjectId = $_POST['subject_id'] ?? null;
    if (!$subjectId) {
        throw new Exception('Subject ID required');
    }

    $stmt = $db->prepare("
        UPDATE teacher_subjects
        SET is_active = 0
        WHERE teacher_id = :uid
          AND subject_id = :sid
          AND is_active = 1
    ");
    $stmt->execute([
        'uid' => $userId,
        'sid' => $subjectId
    ]);

    echo json_encode(['success' => true]);
}


/**
 * helper: check teacher teaches subject
 */
function teacherTeachesSubject($db, $userId, $subjectId): bool
{
    $stmt = $db->prepare("
        SELECT 1
        FROM teacher_subjects
        WHERE teacher_id = :uid
          AND subject_id = :sid
          AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute(['uid' => $userId, 'sid' => $subjectId]);
    return (bool)$stmt->fetch();
}


/**
 * 5) COURSE ASSIGNMENTS (only if teacher teaches it)
 */
function handleCourseAssignments($db, $userId)
{
    $subjectId = $_GET['course_id'] ?? null; // keep same param name for frontend
    if (!$subjectId) throw new Exception('Subject ID required');

    if (!teacherTeachesSubject($db, $userId, $subjectId)) {
        throw new Exception('Access denied: you do not teach this subject');
    }

    // subject title
    $stmt = $db->prepare("SELECT sub_title FROM univ_subjects WHERE id = :id");
    $stmt->execute(['id' => $subjectId]);
    $subject = $stmt->fetch();
    if (!$subject) throw new Exception('Subject not found');

    // assignments list
    $stmt = $db->prepare("
        SELECT
            a.id,
            a.tsk_title AS title,
            a.tsk_description AS description,
            a.tsk_due_date AS due_date,
            COUNT(s.id) AS submission_count
        FROM assignments a
        LEFT JOIN student_submissions s ON a.id = s.task_id
        WHERE a.subject_id = :sid
        GROUP BY a.id
        ORDER BY a.created_at DESC
    ");
    $stmt->execute(['sid' => $subjectId]);

    echo json_encode([
        'success' => true,
        'course_name' => $subject['sub_title'],
        'assignments' => $stmt->fetchAll()
    ]);
}


/**
 * 6) CREATE ASSIGNMENT (only for subjects teacher teaches)
 */
function handleCreateAssignment($db, $userId)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $subjectId   = $_POST['course_id'] ?? null; // comes from assignment modal
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $dueDate     = $_POST['due_date'] ?? null;

    if (!$subjectId || !$title) {
        throw new Exception('Subject ID and title are required');
    }

    if (!teacherTeachesSubject($db, $userId, $subjectId)) {
        throw new Exception('Access denied: you do not teach this subject');
    }

    $stmt = $db->prepare("
        INSERT INTO assignments (subject_id, tsk_title, tsk_description, tsk_due_date)
        VALUES (:sid, :title, :descr, :due)
    ");
    $stmt->execute([
        'sid' => $subjectId,
        'title' => $title,
        'descr' => $description,
        'due' => $dueDate ?: null
    ]);

    echo json_encode(['success' => true]);
}


/**
 * 7) SUBMISSIONS (only for teacher’s subjects)
 */
function handleSubmissions($db, $userId)
{
    $stmt = $db->prepare("
        SELECT
    s.id,
    s.sub_comments AS submission_text,
    s.file_path,
    s.submitted_at,
    s.grade,
    s.tch_feedback AS feedback,
    a.tsk_title AS assignment_title,
    u.sub_code,
    u.sub_title,
    usr.user_name AS student_name
        FROM student_submissions s
        INNER JOIN assignments a ON s.task_id = a.id
        INNER JOIN univ_subjects u ON a.subject_id = u.id
        INNER JOIN teacher_subjects ts ON ts.subject_id = u.id
        INNER JOIN users usr ON s.student_id = usr.id
        WHERE ts.teacher_id = :uid
          AND ts.is_active = 1
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute(['uid' => $userId]);

    echo json_encode([
        'success' => true,
        'submissions' => $stmt->fetchAll()
    ]);
}


/**
 * 8) GRADE SUBMISSION (only for teacher’s subjects)
 */
function handleGradeSubmission($db, $userId)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $submissionId = $_POST['submission_id'] ?? null;
    $grade        = $_POST['score'] ?? null;     // keep name="score" from modal
    $feedback     = trim($_POST['feedback'] ?? '');

    if (!$submissionId || $grade === null) {
        throw new Exception('Submission ID and grade are required');
    }

    // verify teacher owns this submission through teacher_subjects
    $stmt = $db->prepare("
        SELECT s.id
        FROM student_submissions s
        INNER JOIN assignments a ON s.task_id = a.id
        INNER JOIN teacher_subjects ts ON ts.subject_id = a.subject_id
        WHERE s.id = :sid
          AND ts.teacher_id = :uid
          AND ts.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([
        'sid' => $submissionId,
        'uid' => $userId
    ]);

    if (!$stmt->fetch()) {
        throw new Exception('Submission not found or access denied');
    }

    $stmt = $db->prepare("
        UPDATE student_submissions
        SET grade = :g,
            tch_feedback = :fb
        WHERE id = :sid
    ");
    $stmt->execute([
        'g' => $grade,
        'fb' => $feedback,
        'sid' => $submissionId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Grade saved successfully'
    ]);
}
