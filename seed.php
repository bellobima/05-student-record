<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/student-form.php';

$pageTitle = 'Seed sample students';
$databaseError = false;
$seeded = false;
$insertedCount = 0;

if (($_GET['confirm'] ?? null) === 'yes') {
    try {
        $pdo = db();
        $courseColumn = $pdo->query("SHOW COLUMNS FROM students WHERE Field = 'course'")->fetch();
        if ($courseColumn === false) {
            throw new RuntimeException('Could not read the students.course column definition.');
        }
        $courses = studentCourseValues($courseColumn['Type']);

        $firstNames = [
            'Chinedu', 'Nneka', 'Oluwaseun', 'Amina', 'Ibrahim',
            'Zainab', 'Tunde', 'Ifeoma', 'Musa', 'Adaeze',
        ];
        $lastNames = [
            'Adeyemi', 'Okafor', 'Bello', 'Balogun', 'Eze',
            'Abubakar', 'Nwankwo', 'Yusuf', 'Afolabi', 'Danjuma',
        ];
        $insert = $pdo->prepare(
            'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
             VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
        );

        $pdo->beginTransaction();
        for ($index = 0; $index < 50; $index++) {
            $firstName = $firstNames[$index % count($firstNames)];
            $lastName = $lastNames[intdiv($index, 5) % count($lastNames)];
            $email = sprintf(
                'seed.%02d.%s@example.test',
                $index + 1,
                bin2hex(random_bytes(4))
            );
            $enrolledOn = (new DateTimeImmutable('today'))
                ->sub(new DateInterval('P' . mt_rand(30, 1600) . 'D'))
                ->format('Y-m-d');

            $insert->execute([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => '080' . str_pad((string) (10000000 + $index), 8, '0', STR_PAD_LEFT),
                'course' => $courses[$index % count($courses)],
                'enrolled_on' => $enrolledOn,
            ]);
            $insertedCount++;
        }
        $pdo->commit();
        $seeded = true;
    } catch (PDOException $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Student Records seed failed: ' . $exception->getMessage());
        http_response_code(503);
        $databaseError = true;
    } catch (RuntimeException $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Student Records seed schema error: ' . $exception->getMessage());
        http_response_code(503);
        $databaseError = true;
    }
}

require __DIR__ . '/partials/header.php';
?>
    <section class="page-heading">
        <div>
            <h1>Seed sample students</h1>
            <p class="page-description">Add fictional sample records to test search and pagination.</p>
        </div>
    </section>

    <?php if ($databaseError): ?>
        <section class="card error-card">
            <h1>Student records are temporarily unavailable</h1>
            <p>We could not access the database. Please check that MySQL is running and the database is set up.</p>
        </section>
    <?php elseif ($seeded): ?>
        <section class="card seed-card">
            <h2>Sample students added</h2>
            <p><?= number_format($insertedCount) ?> fictional student records were added successfully.</p>
            <a class="button button-primary" href="index.php">View students</a>
        </section>
    <?php else: ?>
        <section class="card seed-card">
            <h2>Confirmation required</h2>
            <p>
                This will add 50 fictional student records to the database. Reloading the
                confirmation URL will add another 50.
            </p>
            <a class="button button-primary" href="seed.php?confirm=yes">Add 50 sample students</a>
            <a class="button button-secondary" href="index.php">Cancel</a>
        </section>
    <?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
