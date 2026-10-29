<form class="card student-form" action="<?= e($formAction ?? '') ?>" method="post">
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">

    <?php if (!empty($errors['_form'])): ?>
        <p class="form-error form-error-summary" role="alert"><?= e($errors['_form']) ?></p>
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-field">
            <label for="first_name">First name</label>
            <input id="first_name" name="first_name" type="text" minlength="2" maxlength="60"
                autocomplete="given-name" required value="<?= e($student['first_name'] ?? '') ?>"
                <?= isset($errors['first_name']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['first_name'])): ?>
                <p class="form-error"><?= e($errors['first_name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="last_name">Last name</label>
            <input id="last_name" name="last_name" type="text" minlength="2" maxlength="60"
                autocomplete="family-name" required value="<?= e($student['last_name'] ?? '') ?>"
                <?= isset($errors['last_name']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['last_name'])): ?>
                <p class="form-error"><?= e($errors['last_name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" maxlength="254"
                autocomplete="email" required value="<?= e($student['email'] ?? '') ?>"
                <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['email'])): ?>
                <p class="form-error"><?= e($errors['email']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="phone">Phone <span class="field-hint">(optional)</span></label>
            <input id="phone" name="phone" type="tel" autocomplete="tel"
                inputmode="numeric" pattern="0[789][01][0-9]{8}"
                value="<?= e($student['phone'] ?? '') ?>"
                <?= isset($errors['phone']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['phone'])): ?>
                <p class="form-error"><?= e($errors['phone']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="course">Course</label>
            <select id="course" name="course" required
                <?= isset($errors['course']) ? 'aria-invalid="true"' : '' ?>>
                <option value="">Select a course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course) ?>" <?= ($student['course'] ?? '') === $course ? 'selected' : '' ?>>
                        <?= e($course) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['course'])): ?>
                <p class="form-error"><?= e($errors['course']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="enrolled_on">Enrolled on</label>
            <input id="enrolled_on" name="enrolled_on" type="date" required
                max="<?= e((new DateTimeImmutable('today'))->format('Y-m-d')) ?>"
                value="<?= e($student['enrolled_on'] ?? '') ?>"
                <?= isset($errors['enrolled_on']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['enrolled_on'])): ?>
                <p class="form-error"><?= e($errors['enrolled_on']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-actions">
        <button class="button button-primary" type="submit"><?= e($submitLabel ?? 'Save student') ?></button>
        <a class="button button-secondary" href="index.php">Cancel</a>
    </div>
</form>
