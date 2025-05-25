<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Timesheet[] $timesheets */
/** @var string|null $errorMessage */
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><?= htmlspecialchars($pageTitle) ?></h2>
        <?php // Add other relevant links here if needed, e.g., back to dashboard ?>
    </div>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if (empty($timesheets) && !$errorMessage): ?>
        <div class="alert alert-info">There are no pending timesheets requiring your approval at this time.</div>
    <?php elseif (!empty($timesheets)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                <tr>
                    <th>Staff Name</th>
                    <th>Site Name</th>
                    <th>Shift Date</th>
                    <th class="text-end">Hours Worked</th>
                    <th>Submitted At</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($timesheets as $timesheet): ?>
                    <tr>
                        <td><?= htmlspecialchars($timesheet->staff_name ?? 'N/A') ?></td>
                        <td>
                            <?= htmlspecialchars($timesheet->site_name ?? 'N/A') ?>
                            <?php if ($timesheet->is_unscheduled_shift): ?>
                                <span class="badge bg-warning text-dark">Unscheduled</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(date('D, M j, Y', strtotime($timesheet->shift_date))) ?></td>
                        <td class="text-end"><?= htmlspecialchars(number_format($timesheet->hours_worked, 2)) ?></td>
                        <td><?= htmlspecialchars($timesheet->submitted_at ? date('Y-m-d H:i', strtotime($timesheet->submitted_at)) : 'N/A') ?></td>
                        <td><?= nl2br(htmlspecialchars($timesheet->notes ?? '')) ?></td>
                        <td>
                            <a href="/supervisor/timesheets/edit/<?= htmlspecialchars($timesheet->id) ?>" class="btn btn-info btn-sm mb-1 me-1">Edit</a>

                            <form action="/supervisor/timesheets/approve" method="POST" style="display: inline-block;" class="mb-1 me-1">
                                <input type="hidden" name="timesheet_id" value="<?= htmlspecialchars($timesheet->id) ?>">
                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                            </form>
                            <form action="/supervisor/timesheets/reject" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to reject this timesheet? You will be asked for a reason.');">
                                <input type="hidden" name="timesheet_id" value="<?= htmlspecialchars($timesheet->id) ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>