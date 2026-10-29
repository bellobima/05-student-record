<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';

$pageTitle = 'Student details';
$studentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($studentId === false || $studentId === null) {
    http_response_code(404);
    exit('Student not found.');
}

$student = null;
$databaseError = false;

try {
    $statement = db()->prepare(
        'SELECT id, first_name, last_name, email, phone, course, enrolled_on, created_at, updated_at
         FROM students
         WHERE id = :id'
    );
    $statement->execute(['id' => $studentId]);
    $student = $statement->fetch();
    if ($student === false) {
        $student = null;
        http_response_code(404);
    }
} catch (PDOException $exception) {
    error_log('Student Records detail lookup failed: ' . $exception->getMessage());
    http_response_code(503);
    $databaseError = true;
}

require __DIR__ . '/partials/header.php';

if ($databaseError):
?>
    <section class="card error-card">
        <h1>Student records are temporarily unavailable</h1>
        <p>We could not access the student records database. Please make sure MySQL is running in XAMPP, then refresh this page.</p>
    </section>
<?php elseif ($student === null): ?>
    <section class="card error-card">
        <h1>Student not found</h1>
        <p>This student record may have been removed.</p>
        <a class="button button-secondary" href="index.php">Back to students</a>
    </section>
<?php else: ?>
    <section class="page-heading">
        <div>
            <h1><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h1>
            <p class="page-description">Student record details.</p>
        </div>
        <a class="button button-primary" href="edit.php?id=<?= (int) $student['id'] ?>">Edit student</a>
    </section>

    <section class="card detail-card" aria-label="Student details">
        <dl class="detail-list">
            <div class="detail-row">
                <dt>Email</dt>
                <dd><a href="mailto:<?= e($student['email']) ?>"><?= e($student['email']) ?></a></dd>
            </div>
            <div class="detail-row">
                <dt>Phone</dt>
                <dd><?= $student['phone'] !== null && $student['phone'] !== '' ? e($student['phone']) : 'Not provided' ?></dd>
            </div>
            <div class="detail-row">
                <dt>Course</dt>
                <dd><span class="course-badge"><?= e($student['course']) ?></span></dd>
            </div>
            <div class="detail-row">
                <dt>Enrolled on</dt>
                <dd>
                    <time datetime="<?= e($student['enrolled_on']) ?>">
                        <?= e((new DateTimeImmutable($student['enrolled_on']))->format('d M Y')) ?>
                    </time>
                </dd>
            </div>
            <div class="detail-row">
                <dt>Record created</dt>
                <dd><?= e($student['created_at']) ?></dd>
            </div>
            <div class="detail-row">
                <dt>Last updated</dt>
                <dd><?= e($student['updated_at']) ?></dd>
            </div>
        </dl>
    </section>

    <p class="back-link"><a href="index.php">← Back to students</a></p>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
