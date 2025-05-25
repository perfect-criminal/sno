<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Site[] $assignedSites */
/** @var \App\UserManagement\Model\Site[] $allActiveSites */ // For unscheduled shifts
/** @var array $form_data */ // Previously submitted form data for sticky form
/** @var array $errors */    // Validation errors

$isUnscheduledShiftChecked = isset($form_data['is_unscheduled_shift']) && $form_data['is_unscheduled_shift'] == '1';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h2><?= htmlspecialchars($pageTitle) ?></h2>
            </div>
            <div class="card-body">
                <form action="/staff/timesheets/store" method="POST" id="timesheetForm">

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_unscheduled_shift" name="is_unscheduled_shift" value="1" <?= $isUnscheduledShiftChecked ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_unscheduled_shift">This is an Unscheduled Shift</label>
                    </div>

                    <div class="mb-3" id="assigned_site_group">
                        <label for="assigned_site_id" class="form-label">Select Assigned Site <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['assigned_site_id']) ? 'is-invalid' : '' ?>" id="assigned_site_id" name="assigned_site_id">
                            <option value="">-- Select from your assigned sites --</option>
                            <?php if (!empty($assignedSites)): ?>
                                <?php foreach ($assignedSites as $site): ?>
                                    <option value="<?= htmlspecialchars($site->id) ?>"
                                        <?= (isset($form_data['assigned_site_id']) && $form_data['assigned_site_id'] == $site->id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($site->site_name) ?> (<?= htmlspecialchars($site->company_name ?? 'Direct') ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No sites currently assigned to you.</option>
                            <?php endif; ?>
                        </select>
                        <?php if (isset($errors['assigned_site_id'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['assigned_site_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3" id="unscheduled_site_group" style="display: <?= $isUnscheduledShiftChecked ? 'block' : 'none' ?>;">
                        <label for="unscheduled_site_id" class="form-label">Select Site for Unscheduled Shift <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['unscheduled_site_id']) ? 'is-invalid' : '' ?>" id="unscheduled_site_id" name="unscheduled_site_id">
                            <option value="">-- Select from all active sites --</option>
                            <?php if (!empty($allActiveSites)): ?>
                                <?php foreach ($allActiveSites as $site): ?>
                                    <option value="<?= htmlspecialchars($site->id) ?>"
                                        <?= (isset($form_data['unscheduled_site_id']) && $form_data['unscheduled_site_id'] == $site->id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($site->site_name) ?> (<?= htmlspecialchars($site->company_name ?? 'Direct') ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (isset($errors['unscheduled_site_id'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['unscheduled_site_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="shift_date" class="form-label">Shift Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control <?= isset($errors['shift_date']) ? 'is-invalid' : '' ?>" id="shift_date" name="shift_date" value="<?= htmlspecialchars($form_data['shift_date'] ?? date('Y-m-d')) ?>" required>
                        <?php if (isset($errors['shift_date'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['shift_date']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="hours_worked" class="form-label">Hours Worked <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.25" max="24" class="form-control <?= isset($errors['hours_worked']) ? 'is-invalid' : '' ?>" id="hours_worked" name="hours_worked" value="<?= htmlspecialchars($form_data['hours_worked'] ?? '') ?>" required placeholder="e.g., 8.5">
                        <?php if (isset($errors['hours_worked'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['hours_worked']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes <span id="notes_required_indicator" class="text-danger" style="display: <?= $isUnscheduledShiftChecked ? 'inline' : 'none' ?>;">*</span></label>
                        <textarea class="form-control <?= isset($errors['notes']) ? 'is-invalid' : '' ?>" id="notes" name="notes" rows="3"><?= htmlspecialchars($form_data['notes'] ?? '') ?></textarea>
                        <?php if (isset($errors['notes'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['notes']) ?></div>
                        <?php endif; ?>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-primary">Submit Timesheet</button>
                    <a href="/staff/dashboard" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const unscheduledCheckbox = document.getElementById('is_unscheduled_shift');
        const assignedSiteGroup = document.getElementById('assigned_site_group');
        const assignedSiteSelect = document.getElementById('assigned_site_id');
        const unscheduledSiteGroup = document.getElementById('unscheduled_site_group');
        const unscheduledSiteSelect = document.getElementById('unscheduled_site_id');
        const notesLabelIndicator = document.getElementById('notes_required_indicator');
        const notesInput = document.getElementById('notes');

        function toggleSiteFields() {
            if (unscheduledCheckbox.checked) {
                assignedSiteGroup.style.display = 'none';
                assignedSiteSelect.removeAttribute('required');
                assignedSiteSelect.value = ''; // Clear assigned site selection

                unscheduledSiteGroup.style.display = 'block';
                unscheduledSiteSelect.setAttribute('required', 'required');
                notesLabelIndicator.style.display = 'inline'; // Show asterisk
                notesInput.setAttribute('required', 'required'); // Make notes required
            } else {
                assignedSiteGroup.style.display = 'block';
                assignedSiteSelect.setAttribute('required', 'required');

                unscheduledSiteGroup.style.display = 'none';
                unscheduledSiteSelect.removeAttribute('required');
                unscheduledSiteSelect.value = ''; // Clear unscheduled site selection
                notesLabelIndicator.style.display = 'none'; // Hide asterisk
                notesInput.removeAttribute('required'); // Make notes optional
            }
        }

        unscheduledCheckbox.addEventListener('change', toggleSiteFields);

        // Initial call to set correct state on page load (e.g., if form is re-rendered with errors)
        toggleSiteFields();
    });
</script>