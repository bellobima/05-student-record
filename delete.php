<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if (!isValidCsrfToken($_POST['_csrf_token'] ?? null)) {
    http_response_code(403);
    exit('The request could not be verified. Return to the student list and try again.');
}

$studentId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($studentId === false || $studentId === null) {
    http_response_code(400);
    exit('Invalid student ID.');
}

try {
    $delete = db()->prepare('DELETE FROM students WHERE id = :id');
    $delete->execute(['id' => $studentId]);

    if ($delete->rowCount() > 0) {
        flash('success', 'Student deleted');
    } else {
        flash('error', 'Student not found');
    }

    redirect('index.php');
} catch (PDOException $exception) {
    error_log('Student Records delete failed: ' . $exception->getMessage());
    http_response_code(503);
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-card">
        <h1>Student records are temporarily unavailable</h1>
        <p>We could not update the student records. Please make sure MySQL is running in XAMPP, then try again.</p>
        <a class="button button-secondary" href="index.php">Back to students</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
}
