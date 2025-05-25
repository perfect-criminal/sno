<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Timesheet $timesheet */
/** @var array $form_data */
/** @var array $errors */
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h2><?= htmlspecialchars($pageTitle) ?></h2>
                <h5 class="text-muted">Timesheet ID: <?= htmlspecialchars($timesheet->id) ?> for <?= htmlspecialchars($timesheet->staff_name ?? 'Staff') ?></h5>
            </div>
            <div class="card-body">
                <p><strong>Site:</strong> <?= htmlspecialchars($timesheet->site_name ?? 'N/A') ?></p>
                <p><strong>Shift Date:</strong> <?= htmlspecialchars(date('D, M j, Y', strtotime($timesheet->shift_date))) ?></p>
                <p><strong>Hours Submitted:</strong> <?= htmlspecialchars(number_format($timesheet->hours_worked, 2)) ?></p>
                <?php if ($timesheet->notes): ?>
                    <p><strong>Staff Notes:</strong> <?= nl2br(htmlspecialchars($timesheet->notes)) ?></p>
                <?php endif; ?>

                <hr>
                <form action="/supervisor/timesheets/reject-action/<?= htmlspecialchars($timesheet->id) ?>" method="POST">
                    <input type="hidden" name="timesheet_id" value="<?= htmlspecialchars($timesheet->id) ?>">

                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors['rejection_reason']) ? 'is-invalid' : '' ?>" id="rejection_reason" name="rejection_reason" rows="4" required><?= htmlspecialchars($form_data['rejection_reason'] ?? '') ?></textarea>
                        <?php if (isset($errors['rejection_reason'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['rejection_reason']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                    <a href="/supervisor/timesheets/pending" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>