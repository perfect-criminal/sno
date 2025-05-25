<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Timesheet[] $timesheets */
/** @var string|null $errorMessage */
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><?= htmlspecialchars($pageTitle) ?></h2>
        <a href="/staff/timesheets/create" class="btn btn-primary">Submit New Timesheet</a>
    </div>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if (empty($timesheets) && !$errorMessage): ?>
        <div class="alert alert-info">You have not submitted any timesheets yet.</div>
    <?php elseif (!empty($timesheets)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th>Shift Date</th>
                    <th>Site</th>
                    <th>Hours Worked</th>
                    <th>Status</th>
                    <th>Submitted At</th>
                    <th>Notes</th>
                    <th>Rejection Reason</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($timesheets as $timesheet): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('D, M j, Y', strtotime($timesheet->shift_date))) ?></td>
                        <td>
                            <?= htmlspecialchars($timesheet->site_name ?? 'N/A') ?>
                            <?php if ($timesheet->is_unscheduled_shift): ?>
                                <span class="badge bg-warning text-dark">Unscheduled</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= htmlspecialchars(number_format($timesheet->hours_worked, 2)) ?></td>
                        <td>
                            <?php
                            $statusBadge = 'secondary'; // Default
                            $statusText = htmlspecialchars($timesheet->status);
                            if ($timesheet->status === 'Approved') $statusBadge = 'success';
                            elseif ($timesheet->status === 'Rejected') $statusBadge = 'danger';
                            elseif ($timesheet->status === 'Pending') $statusBadge = 'info';
                            elseif ($timesheet->status === 'EditedBySupervisor') $statusBadge = 'warning';
                            elseif ($timesheet->status === 'PendingStaffConfirmation') $statusBadge = 'primary';
                            elseif ($timesheet->status === 'DisputedByStaff') $statusBadge = 'dark';
                            ?>
                            <span class="badge bg-<?= $statusBadge ?>"><?= $statusText ?></span>
                        </td>
                        <td><?= htmlspecialchars($timesheet->submitted_at ? date('Y-m-d H:i', strtotime($timesheet->submitted_at)) : 'N/A') ?></td>
                        <td><?= nl2br(htmlspecialchars($timesheet->notes ?? '')) ?></td>
                        <td>
                            <?php if ($timesheet->status === 'Rejected'): ?>
                                <strong class="text-danger">Reason:</strong> <?= nl2br(htmlspecialchars($timesheet->rejection_reason ?? 'N/A')) ?><br>
                                <a href="/staff/timesheets/edit/<?= $timesheet->id ?>" class="btn btn-warning btn-sm mt-1">Edit & Resubmit</a>
                            <?php elseif ($timesheet->status === 'PendingStaffConfirmation'): ?>
                                <a href="/staff/timesheets/review/<?= $timesheet->id ?>" class="btn btn-primary btn-sm mt-1">Review Supervisor Edit</a>
                            <?php else: ?>
                                <?= nl2br(htmlspecialchars($timesheet->rejection_reason ?? '')) ?>
                            <?php endif; ?>
                        </td>
                        <?php /* Action column for staff usually not needed unless they can cancel a pending one
            <td>
                <?php if ($timesheet->status === 'Pending'): ?>
                    <a href="/staff/timesheets/cancel/<?= $timesheet->id ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
                <?php endif; ?>
            </td>
            */ ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>