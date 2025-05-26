<?php
/** @var string $pageTitle */
/** @var array $form_data */ // For sticky form on error
/** @var array $errors */    // Validation errors
?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h2><?= htmlspecialchars($pageTitle) ?></h2>
            </div>
            <div class="card-body">
                <p>Select a date range to generate a paysheet for approved timesheets submitted by your staff.</p>
                <form action="/supervisor/paysheets/generate" method="POST">
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Pay Period Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control <?= isset($errors['start_date']) ? 'is-invalid' : '' ?>" id="start_date" name="start_date" value="<?= htmlspecialchars($form_data['start_date'] ?? '') ?>" required>
                        <?php if (isset($errors['start_date'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['start_date']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="end_date" class="form-label">Pay Period End Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control <?= isset($errors['end_date']) ? 'is-invalid' : '' ?>" id="end_date" name="end_date" value="<?= htmlspecialchars($form_data['end_date'] ?? '') ?>" required>
                        <?php if (isset($errors['end_date'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['end_date']) ?></div>
                        <?php endif; ?>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-primary">Generate Paysheet</button>
                    <a href="/supervisor/dashboard" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>