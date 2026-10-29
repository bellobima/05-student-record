<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';

$pageTitle = 'Students';
$students = [];
$databaseError = false;

try {
    $statement = db()->query(
        'SELECT id, first_name, last_name, email, course, enrolled_on
         FROM students
         ORDER BY last_name, first_name'
    );
    $students = $statement->fetchAll();
} catch (PDOException $exception) {
    error_log('Student Records database connection/query failed: ' . $exception->getMessage());
    http_response_code(503);
    $databaseError = true;
}

require __DIR__ . '/partials/header.php';

if ($databaseError):
?>
    <section class="card error-card">
        <h1>Student records are temporarily unavailable</h1>
        <p>
            We could not connect to the student records database. Please make sure
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
            <h1>Students</h1>
            <p class="page-description">View and manage student enrollment records.</p>
        </div>
        <a class="button button-primary" href="create.php">Add student</a>
    </section>

    <section class="card" aria-label="Student records">
        <?php if ($students === []): ?>
            <div class="empty-state">
                <p>No student records yet.</p>
                <a class="button button-primary" href="create.php">Add your first student</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Course</th>
                            <th scope="col">Enrolled</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td class="student-name">
                                    <?= e($student['first_name'] . ' ' . $student['last_name']) ?>
                                </td>
                                <td><?= e($student['email']) ?></td>
                                <td><span class="course-badge"><?= e($student['course']) ?></span></td>
                                <td>
                                    <time datetime="<?= e($student['enrolled_on']) ?>">
                                        <?= e((new DateTimeImmutable($student['enrolled_on']))->format('d M Y')) ?>
                                    </time>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a class="action-link" href="edit.php?id=<?= (int) $student['id'] ?>">Edit</a>
                                        <form action="delete.php" method="post">
                                            <input type="hidden" name="id" value="<?= (int) $student['id'] ?>">
                                            <button class="action-button" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php require __DIR__ . '/partials/footer.php'; ?>
