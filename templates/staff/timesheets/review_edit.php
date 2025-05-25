<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Timesheet $timesheet */
/** @var string|null $supervisorName */
/** @var array $form_data */
/** @var array $errors */
?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h2><?= htmlspecialchars($pageTitle) ?></h2>
                <p class="text-muted">Your supervisor, <?= htmlspecialchars($supervisorName ?? 'Supervisor') ?>, edited this timesheet on <?= htmlspecialchars(date('M j, Y H:i', strtotime($timesheet->edited_at))) ?>. Please review the changes.</p>
            </div>
            <div class="card-body">
                <h4>Original Details (if applicable)</h4>
                <dl class="row">
                    <?php if ($timesheet->original_hours_worked !== null): ?>
                        <dt class="col-sm-3">Original Hours Worked:</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars(number_format($timesheet->original_hours_worked, 2)) ?></dd>
                    <?php else: ?>
                        <dt class="col-sm-3">Original Hours Worked:</dt>
                        <dd class="col-sm-9"><em>No change to hours, or first submission.</em></dd>
                    <?php endif; ?>
                    <?php // You can add more "original" fields here if you store them, e.g., original site, original notes ?>
                </dl>
                <hr>
                <h4>Edited Details (Current Values)</h4>
                <dl class="row">
                    <dt class="col-sm-3">Site:</dt>
                    <dd class="col-sm-9">
                        <?= htmlspecialchars($timesheet->site_name ?? 'N/A') ?>
                        <?php if ($timesheet->is_unscheduled_shift): ?>
                            <span class="badge bg-warning text-dark">Unscheduled</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-3">Shift Date:</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars(date('D, M j, Y', strtotime($timesheet->shift_date))) ?></dd>

                    <dt class="col-sm-3">Hours Worked:</dt>
                    <dd class="col-sm-9"><strong><?= htmlspecialchars(number_format($timesheet->hours_worked, 2)) ?></strong></dd>

                    <dt class="col-sm-3">Notes:</dt>
                    <dd class="col-sm-9"><?= nl2br(htmlspecialchars($timesheet->notes ?? 'N/A')) ?></dd>
                </dl>
                <hr>
                <h4>Your Action</h4>
                <div class="d-flex flex-wrap">
                    <form action="/staff/timesheets/agree/<?= $timesheet->id ?>" method="POST" class="me-2 mb-2">
                        <button type="submit" class="btn btn-success">Agree with Changes & Approve</button>
                    </form>

                    <button type="button" class="btn btn-danger mb-2" data-bs-toggle="modal" data-bs-target="#disagreeModal">
                        Disagree with Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="disagreeModal" tabindex="-1" aria-labelledby="disagreeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="/staff/timesheets/disagree/<?= $timesheet->id ?>" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="disagreeModalLabel">Disagree with Changes</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Please provide a reason for disagreeing with the supervisor's changes:</p>
                        <div class="mb-3">
                            <label for="disagreement_reason" class="form-label">Reason for Disagreement <span class="text-danger">*</span></label>
                            <textarea class="form-control <?= isset($errors['disagreement_reason']) ? 'is-invalid' : '' ?>" id="disagreement_reason" name="disagreement_reason" rows="4" required><?= htmlspecialchars($form_data['disagreement_reason'] ?? '') ?></textarea>
                            <?php if (isset($errors['disagreement_reason'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['disagreement_reason']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Submit Disagreement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php if(!empty($errors)): // If there were errors submitting disagreement, re-open modal ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var disagreeModal = new bootstrap.Modal(document.getElementById('disagreeModal'));
            disagreeModal.show();
        });
    </script>
<?php endif; ?>