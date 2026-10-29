<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/student-form.php';

$pageTitle = 'Students';
$students = [];
$courses = [];
$databaseError = false;
$csrfToken = csrfToken();
$perPage = 10;
$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$selectedCourse = is_string($_GET['course'] ?? null) ? trim($_GET['course']) : '';
$invalidCourse = false;
$pageValue = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentPage = $pageValue === false ? 1 : $pageValue;
$totalStudents = 0;
$totalPages = 1;
$offset = 0;
$paginationParams = [];

try {
    $pdo = db();
    $courseColumn = $pdo->query("SHOW COLUMNS FROM students WHERE Field = 'course'")->fetch();
    if ($courseColumn === false) {
        throw new RuntimeException('Could not read the students.course column definition.');
    }
    $courses = studentCourseValues($courseColumn['Type']);

    if ($selectedCourse !== '' && !in_array($selectedCourse, $courses, true)) {
        $invalidCourse = true;
        $selectedCourse = '';
    }

    $conditions = [];
    $parameters = [];
    if ($search !== '') {
        $conditions[] = '(first_name LIKE :first_name OR last_name LIKE :last_name OR email LIKE :email)';
        $pattern = '%' . $search . '%';
        $parameters['first_name'] = $pattern;
        $parameters['last_name'] = $pattern;
        $parameters['email'] = $pattern;
    }
    if ($selectedCourse !== '') {
        $conditions[] = 'course = :course';
        $parameters['course'] = $selectedCourse;
    }
    $whereSql = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

    $count = $pdo->prepare('SELECT COUNT(*) FROM students' . $whereSql);
    $count->execute($parameters);
    $totalStudents = (int) $count->fetchColumn();
    $totalPages = max(1, (int) ceil($totalStudents / $perPage));
    $currentPage = min($currentPage, $totalPages);
    $offset = ($currentPage - 1) * $perPage;

    $list = $pdo->prepare(
        'SELECT id, first_name, last_name, email, course, enrolled_on
         FROM students'
        . $whereSql
        . ' ORDER BY last_name, first_name
           LIMIT :limit OFFSET :offset'
    );
    foreach ($parameters as $name => $value) {
        $list->bindValue(':' . $name, $value, PDO::PARAM_STR);
    }
    $list->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $list->bindValue(':offset', $offset, PDO::PARAM_INT);
    $list->execute();
    $students = $list->fetchAll();

    $paginationParams = [];
    if ($search !== '') {
        $paginationParams['q'] = $search;
    }
    if ($selectedCourse !== '') {
        $paginationParams['course'] = $selectedCourse;
    }
} catch (PDOException $exception) {
    error_log('Student Records database query failed: ' . $exception->getMessage());
    http_response_code(503);
    $databaseError = true;
} catch (RuntimeException $exception) {
    error_log('Student Records schema error: ' . $exception->getMessage());
    http_response_code(503);
    $databaseError = true;
}

$rangeStart = $totalStudents === 0 ? 0 : $offset + 1;
$rangeEnd = min($offset + count($students), $totalStudents);

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

    <form class="filter-form" action="index.php" method="get">
        <div class="filter-search">
            <label class="visually-hidden" for="q">Search students</label>
            <input id="q" name="q" type="search" maxlength="100"
                placeholder="Search name or email" value="<?= e($search) ?>">
        </div>
        <div class="filter-course">
            <label class="visually-hidden" for="course">Filter by course</label>
            <select id="course" name="course">
                <option value="">All courses</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course) ?>" <?= $selectedCourse === $course ? 'selected' : '' ?>>
                        <?= e($course) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="button button-primary" type="submit">Search</button>
        <?php if ($search !== '' || $selectedCourse !== '' || $invalidCourse): ?>
            <a class="button button-secondary" href="index.php">Clear filters</a>
        <?php endif; ?>
    </form>

    <?php if ($invalidCourse): ?>
        <p class="filter-notice" role="status">That course filter was not recognized; showing all courses.</p>
    <?php endif; ?>

    <p class="results-summary">
        Showing <?= number_format($rangeStart) ?>–<?= number_format($rangeEnd) ?>
        of <?= number_format($totalStudents) ?> students
    </p>

    <section class="card" aria-label="Student records">
        <?php if ($students === []): ?>
            <div class="empty-state">
                <?php if ($search !== '' || $selectedCourse !== ''): ?>
                    <p>No students match the selected filters.</p>
                <?php else: ?>
                    <p>No student records yet.</p>
                    <a class="button button-primary" href="create.php">Add your first student</a>
                <?php endif; ?>
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
                                        <a class="action-link" href="show.php?id=<?= (int) $student['id'] ?>">View</a>
                                        <a class="action-link" href="edit.php?id=<?= (int) $student['id'] ?>">Edit</a>
                                        <form class="inline-form" action="delete.php" method="post"
                                            data-confirm="<?= e('Delete ' . $student['first_name'] . ' ' . $student['last_name'] . '?') ?>"
                                            onsubmit="return confirm(this.dataset.confirm)">
                                            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
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

    <?php if ($totalPages > 1): ?>
        <?php
        $firstPage = max(1, $currentPage - 2);
        $lastPage = min($totalPages, $currentPage + 2);
        ?>
        <nav class="pagination" aria-label="Student list pages">
            <?php if ($currentPage > 1): ?>
                <?php $previousParams = $paginationParams + ['page' => $currentPage - 1]; ?>
                <a class="pagination-link" href="index.php?<?= e(http_build_query($previousParams)) ?>">Previous</a>
            <?php endif; ?>
            <?php for ($page = $firstPage; $page <= $lastPage; $page++): ?>
                <?php $pageParams = $paginationParams + ['page' => $page]; ?>
                <?php if ($page === $currentPage): ?>
                    <span class="pagination-current" aria-current="page"><?= $page ?></span>
                <?php else: ?>
                    <a class="pagination-link" href="index.php?<?= e(http_build_query($pageParams)) ?>"><?= $page ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($currentPage < $totalPages): ?>
                <?php $nextParams = $paginationParams + ['page' => $currentPage + 1]; ?>
                <a class="pagination-link" href="index.php?<?= e(http_build_query($nextParams)) ?>">Next</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
