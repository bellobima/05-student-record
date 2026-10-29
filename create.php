<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/student-form.php';

$pageTitle = 'Add student';
$formAction = 'create.php';
$submitLabel = 'Add student';
$today = (new DateTimeImmutable('today'))->format('Y-m-d');
$student = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'course' => '',
    'enrolled_on' => $today,
];
$errors = [];
$databaseError = false;
$csrfToken = csrfToken();

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($student) as $field) {
        $student[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
    }

    if (!isValidCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        $errors['_form'] = 'Your form session expired or could not be verified. Please review the form and submit it again.';
    }
}

try {
    $pdo = db();
    $courseColumn = $pdo->query("SHOW COLUMNS FROM students WHERE Field = 'course'")->fetch();
    if ($courseColumn === false) {
        throw new RuntimeException('Could not read the students.course column definition.');
    }
    $courses = studentCourseValues($courseColumn['Type']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($errors['_form'])) {
        $errors = validateStudent($pdo, $student, $courses);

        if ($errors === []) {
            $insert = $pdo->prepare(
                'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
                 VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
            );
            $insert->execute([
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'email' => $student['email'],
                'phone' => $student['phone'] === '' ? null : $student['phone'],
                'course' => $student['course'],
                'enrolled_on' => $student['enrolled_on'],
            ]);

            flash('success', 'Student added');
            redirect('index.php');
        }
    }
} catch (PDOException $exception) {
    if (($exception->errorInfo[1] ?? null) === 1062 && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $errors['email'] = 'A student with this email address already exists.';
    } else {
        error_log('Student Records database operation failed: ' . $exception->getMessage());
        http_response_code(503);
        $databaseError = true;
    }
} catch (RuntimeException $exception) {
    error_log('Student Records configuration/schema error: ' . $exception->getMessage());
    http_response_code(503);
    $databaseError = true;
}

require __DIR__ . '/partials/header.php';

if ($databaseError):
?>
    <section class="card error-card">
        <h1>Student records are temporarily unavailable</h1>
        <p>
            We could not access the student records database. Please make sure
            MySQL is running in XAMPP, then refresh this page.
        </p>
    </section>
<?php
    require __DIR__ . '/partials/footer.php';
    exit;
endif;
?>
    <section class="page-heading">
        <div>
            <h1>Add student</h1>
            <p class="page-description">Enter the student's details below.</p>
        </div>
    </section>

    <?php require __DIR__ . '/partials/student-form.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
