<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Timesheet[] $timesheets */
/** @var string|null $errorMessage */
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><?= htmlspecialchars($pageTitle) ?></h2>
        <a href="/supervisor/timesheets/pending" class="btn btn-outline-secondary">View Pending Approvals</a>
    </div>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if (empty($timesheets) && !$errorMessage): ?>
        <div class="alert alert-info">There are no disputed timesheets at this time.</div>
    <?php elseif (!empty($timesheets)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                <tr>
                    <th>Staff Name</th>
                    <th>Site Name</th>
                    <th>Shift Date</th>
                    <th class="text-end">Hours (Edited)</th>
                    <?php // <th>Orig. Hours</th> // Optional: if you want to show this directly in list ?>
                    <th>Edited By Sup. At</th>
                    <th>Staff Dispute Reason</th>
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
                        <?php /* <td class="text-end">
                                <?= $timesheet->original_hours_worked !== null ? htmlspecialchars(number_format($timesheet->original_hours_worked, 2)) : 'N/A' ?>
                            </td>
                            */ ?>
                        <td><?= htmlspecialchars($timesheet->edited_at ? date('Y-m-d H:i', strtotime($timesheet->edited_at)) : 'N/A') ?></td>
                        <td style="white-space: pre-wrap; word-break: break-word; color: red; font-weight:bold;"><?= nl2br(htmlspecialchars($timesheet->staff_dispute_reason ?? 'No reason provided.')) ?></td>
                        <td>
                            <a href="/supervisor/timesheets/edit/<?= htmlspecialchars($timesheet->id) ?>" class="btn btn-info btn-sm">Review & Re-Edit</a>
                            <?php // Add other actions later, e.g., "Mark as Resolved", "Force Approve", "Force Reject" ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>