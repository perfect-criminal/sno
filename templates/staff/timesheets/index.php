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
                            if ($timesheet->status === 'Approved') $statusBadge = 'success';
                            elseif ($timesheet->status === 'Rejected') $statusBadge = 'danger';
                            elseif ($timesheet->status === 'Pending') $statusBadge = 'info';
                            elseif ($timesheet->status === 'Edited') $statusBadge = 'warning';
                            ?>
                            <span class="badge bg-<?= $statusBadge ?>"><?= htmlspecialchars($timesheet->status) ?></span>
                        </td>
                        <td><?= htmlspecialchars($timesheet->submitted_at ? date('Y-m-d H:i', strtotime($timesheet->submitted_at)) : 'N/A') ?></td>
                        <td><?= nl2br(htmlspecialchars($timesheet->notes ?? '')) ?></td>
                        <td><?= nl2br(htmlspecialchars($timesheet->rejection_reason ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>