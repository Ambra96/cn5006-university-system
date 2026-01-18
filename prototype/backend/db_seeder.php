<?php
//Run php db_seeder.php

require_once __DIR__ . '/db.php';

$db = (new DatabaseConnection())->getConnection();

echo "Starting FULL database seeding...\n";

try {
    // --------------------------------------------------
    // Disable FK checks
    // --------------------------------------------------
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    // --------------------------------------------------
    // Truncate ALL tables
    // --------------------------------------------------
    $tables = [
        'student_submissions',
        'student_grades',
        'assignments',
        'student_registrations',
        'teacher_subjects',
        'univ_subjects',
        'users',
        'roles',
    ];

    foreach ($tables as $table) {
        $db->exec("TRUNCATE TABLE {$table}");
    }

    echo "✓ Tables truncated\n";

    // --------------------------------------------------
    // ROLES (1=student, 2=teacher)
    // --------------------------------------------------
    $roles = [
        1 => 'student',
        2 => 'teacher',
    ];

    $stmt = $db->prepare("INSERT INTO roles (id, title) VALUES (?, ?)");
    foreach ($roles as $id => $title) {
        $stmt->execute([$id, $title]);
    }

    echo "✓ Roles seeded\n";

    // --------------------------------------------------
    // USERS
    // --------------------------------------------------
    $passwordPlain = '123456789';
    $passwordHash  = password_hash($passwordPlain, PASSWORD_BCRYPT);

    $users = [
        ['Dr. Alice Brown', 'alice.brown@hellenictech.edu.gr', 2],
        ['Dr. John Smith',  'john.smith@hellenictech.edu.gr',  2],
        ['Dr. Maria Lopez', 'maria.lopez@hellenictech.edu.gr', 2],
        ['Student One',    's01@hellenictech.edu.gr', 1],
        ['Student Two',    's02@hellenictech.edu.gr', 1],
        ['Student Three',  's03@hellenictech.edu.gr', 1],
        ['Student Four',   's04@hellenictech.edu.gr', 1],
        ['Student Five',   's05@hellenictech.edu.gr', 1],
        ['Student Six',    's06@hellenictech.edu.gr', 1],
        ['Student Seven',  's07@hellenictech.edu.gr', 1],
        ['Student Eight',  's08@hellenictech.edu.gr', 1],
        ['Student Nine',   's09@hellenictech.edu.gr', 1],
        ['Student Ten',    's10@hellenictech.edu.gr', 1],
        ['Student Eleven', 's11@hellenictech.edu.gr', 1],
        ['Student Twelve', 's12@hellenictech.edu.gr', 1],
    ];


    $stmt = $db->prepare("
        INSERT INTO users (user_name, email, password, role_id)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($users as $u) {
        $stmt->execute([$u[0], $u[1], $passwordHash, $u[2]]);
    }

    echo "✓ Users seeded (password: 123456789)\n";

    // --------------------------------------------------
    // SUBJECTS
    // --------------------------------------------------
    $subjects = [
        ['CS101', 'Introduction to Programming', 'Basics of programming', '1'],
        ['CS102', 'Data Structures', 'Arrays, lists, trees', '2'],
        ['CS103', 'Databases I', 'Relational databases', '2'],
        ['CS104', 'Web Development', 'HTML, CSS, JS', '3'],
        ['CS105', 'Operating Systems', 'Processes & memory', '3'],
        ['CS106', 'Algorithms', 'Algorithm design', '4'],
        ['CS107', 'Software Engineering', 'SDLC & UML', '4'],
        ['CS108', 'Computer Networks', 'TCP/IP basics', '4'],
        ['CS109', 'Databases II', 'Advanced SQL', '5'],
        ['CS110', 'Artificial Intelligence', 'AI fundamentals', '5'],
        ['CS111', 'Machine Learning', 'Supervised learning', '6'],
        ['CS112', 'Cyber Security', 'Security basics', '6'],
        ['CS113', 'Cloud Computing', 'Cloud models', '6'],
        ['CS114', 'Mobile Development', 'Android & iOS', '5'],
        ['CS115', 'Game Development', 'Game engines', '6'],
        ['CS116', 'Human Computer Interaction', 'UX principles', '3'],
        ['CS117', 'Distributed Systems', 'Scalability', '5'],
        ['CS118', 'Big Data', 'Data analytics', '6'],
        ['CS119', 'Compiler Design', 'Parsing & lexing', '7'],
        ['CS120', 'Computer Graphics', 'Rendering', '7'],
        ['CS121', 'Numerical Analysis', 'Math computing', '7'],
        ['CS122', 'IoT Systems', 'Sensors & data', '7'],
        ['CS123', 'Blockchain', 'Distributed ledgers', '7'],
        ['CS124', 'Digital Logic', 'Logic gates', '1'],
        ['CS125', 'Discrete Mathematics', 'Sets & graphs', '1'],
        ['CS126', 'Linear Algebra', 'Matrices', '1'],
        ['CS127', 'Statistics', 'Probability', '2'],
        ['CS128', 'Ethics in Computing', 'Ethics', '8'],
        ['CS129', 'Project Management', 'Agile & Scrum', '8'],
        ['CS130', 'Final Year Project', 'Capstone', '8'],
    ];

    $stmt = $db->prepare("
        INSERT INTO univ_subjects (sub_code, sub_title, sub_description, sub_semester)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($subjects as $s) {
        $stmt->execute($s);
    }

    echo "✓ Subjects seeded\n";

    // --------------------------------------------------
    // TEACHER ↔ SUBJECTS
    // --------------------------------------------------
    $teacherSubjects = [
        [1, 1, '1'],
        [1, 2, '2'],
        [1, 3, '2'],
        [1, 4, '3'],
        [2, 5, '3'],
        [2, 6, '4'],
        [2, 7, '4'],
        [2, 8, '4'],
        [3, 9, '5'],
        [3, 10, '5'],
        [3, 11, '6'],
        [3, 12, '6'],
    ];

    $stmt = $db->prepare("
        INSERT INTO teacher_subjects (teacher_id, subject_id, semester)
        VALUES (?, ?, ?)
    ");

    foreach ($teacherSubjects as $ts) {
        $stmt->execute($ts);
    }

    echo "✓ Teacher subjects seeded\n";

    // --------------------------------------------------
    // STUDENT REGISTRATIONS
    // --------------------------------------------------
    $registrations = [
        [4, 1],
        [4, 2],
        [4, 3],
        [5, 1],
        [5, 2],
        [6, 3],
        [6, 4],
        [7, 4],
        [7, 5],
        [8, 6],
        [8, 7],
        [9, 8],
        [9, 9],
        [10, 10],
        [11, 11],
        [12, 12],
    ];

    $stmt = $db->prepare("
        INSERT INTO student_registrations (student_id, subject_id)
        VALUES (?, ?)
    ");

    foreach ($registrations as $r) {
        $stmt->execute($r);
    }

    echo "✓ Student registrations seeded\n";

    // --------------------------------------------------
    // ASSIGNMENTS
    // --------------------------------------------------
    $assignments = [
        [1, 'Assignment 1', 'Intro exercises', '2026-03-01'],
        [2, 'Assignment 2', 'Trees & Lists', '2026-03-10'],
        [3, 'Assignment 3', 'SQL Queries', '2026-03-15'],
        [4, 'Assignment 4', 'Web Page', '2026-03-20'],
        [5, 'Assignment 5', 'Process Scheduling', '2026-03-25'],
    ];

    $stmt = $db->prepare("
        INSERT INTO assignments (subject_id, tsk_title, tsk_description, tsk_due_date)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($assignments as $a) {
        $stmt->execute($a);
    }

    echo "✓ Assignments seeded\n";

    // --------------------------------------------------
    // STUDENT SUBMISSIONS
    // --------------------------------------------------
    $submissions = [
        [1, 4, 'My first assignment', 85, 'Good job'],
        [2, 4, 'Data structures work', 78, 'Needs improvement'],
        [3, 5, 'SQL assignment', 90, 'Excellent'],
        [4, 6, 'Website project', 88, 'Well structured'],
    ];

    $stmt = $db->prepare("
        INSERT INTO student_submissions
        (task_id, student_id, sub_comments, grade, tch_feedback)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($submissions as $s) {
        $stmt->execute($s);
    }

    echo "✓ Student submissions seeded\n";

    // --------------------------------------------------
    // FINAL GRADES
    // --------------------------------------------------
    $grades = [
        [4, 1, 8.5],
        [4, 2, 7.8],
        [5, 3, 9.0],
        [6, 4, 8.8],
    ];

    $stmt = $db->prepare("
        INSERT INTO student_grades (student_id, subject_id, final_grade)
        VALUES (?, ?, ?)
    ");

    foreach ($grades as $g) {
        $stmt->execute($g);
    }

    echo "✓ Student grades seeded\n";

    // --------------------------------------------------
    // Restore FK checks
    // --------------------------------------------------
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "FULL DATABASE SEEDED SUCCESSFULLY\n";
} catch (Throwable $e) {
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
