<?php

function studentCourseValues(string $columnType): array
{
    if (!preg_match('/^enum\((.*)\)$/i', $columnType, $enumMatch)) {
        throw new RuntimeException('The students.course column is not an ENUM.');
    }

    preg_match_all("/'((?:[^'\\\\]|\\\\.|'')*)'/", $enumMatch[1], $valueMatches);
    $values = array_map(
        static fn (string $value): string => stripslashes(str_replace("''", "'", $value)),
        $valueMatches[1]
    );

    if ($values === []) {
        throw new RuntimeException('The students.course ENUM has no values.');
    }

    return $values;
}

function validateStudent(PDO $pdo, array $student, array $courses, ?int $excludeId = null): array
{
    $errors = [];

    foreach (['first_name', 'last_name'] as $field) {
        $value = $student[$field];
        $characterCount = preg_match_all('/./us', $value);

        if ($value === '') {
            $errors[$field] = 'This field is required.';
        } elseif ($characterCount === false || $characterCount < 2 || $characterCount > 60) {
            $errors[$field] = 'Enter between 2 and 60 characters.';
        } elseif (!preg_match('/^[\p{L} -]+$/u', $value) || !preg_match('/\p{L}/u', $value)) {
            $errors[$field] = 'Use letters, spaces, and hyphens only.';
        }
    }

    if ($student['email'] === '') {
        $errors['email'] = 'Email is required.';
    } elseif (strlen($student['email']) > 254 || filter_var($student['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    } else {
        $emailSql = 'SELECT id FROM students WHERE email = :email';
        if ($excludeId !== null) {
            $emailSql .= ' AND id <> :exclude_id';
        }
        $emailCheck = $pdo->prepare($emailSql . ' LIMIT 1');
        $parameters = ['email' => $student['email']];
        if ($excludeId !== null) {
            $parameters['exclude_id'] = $excludeId;
        }
        $emailCheck->execute($parameters);
        if ($emailCheck->fetch() !== false) {
            $errors['email'] = 'A student with this email address already exists.';
        }
    }

    if ($student['phone'] !== '' && !preg_match('/^0[789][01]\d{8}$/', $student['phone'])) {
        $errors['phone'] = 'Enter a valid phone number or leave this field blank.';
    }

    if (!in_array($student['course'], $courses, true)) {
        $errors['course'] = 'Select a valid course.';
    }

    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $enrolledDate = DateTimeImmutable::createFromFormat('!Y-m-d', $student['enrolled_on']);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (
        $enrolledDate === false
        || $enrolledDate->format('Y-m-d') !== $student['enrolled_on']
        || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        || $student['enrolled_on'] > $today
    ) {
        $errors['enrolled_on'] = 'Enter a valid enrollment date that is not in the future.';
    }

    return $errors;
}
