<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\User[] $teamMembers */ // For staff filter dropdown
/** @var \App\UserManagement\Model\Timesheet[] $timesheets */
/** @var array $filters */ // Current filter values (staff_id, date_from, date_to, status)
/** @var string[] $timesheetStatuses */ // Available statuses for filter dropdown
/** @var string|null $errorMessage */
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><?= htmlspecialchars($pageTitle) ?></h2>
        <?php // Link back to Supervisor Dashboard or other relevant page ?>
    </div>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h4>Filter Timesheets</h4>
        </div>
        <div class="card-body">
            <form action="/supervisor/team-timesheets" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="filter_staff_id" class="form-label">Staff Member</label>
                    <select name="staff_id" id="filter_staff_id" class="form-select">
                        <option value="">All My Team Members</option>
                        <?php foreach ($teamMembers as $staff): ?>
                            <option value="<?= htmlspecialchars($staff->id) ?>" <?= (isset($filters['staff_id']) && $filters['staff_id'] == $staff->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($staff->getFullName()) ?> (ID: <?= htmlspecialchars($staff->id) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_date_from" class="form-label">Date From</label>
                    <input type="date" name="date_from" id="filter_date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="filter_date_to" class="form-label">Date To</label>
                    <input type="date" name="date_to" id="filter_date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="filter_status" class="form-label">Status</label>
                    <select name="status" id="filter_status" class="form-select">
                        <option value="">All Statuses</option>
                        <?php foreach ($timesheetStatuses as $status): ?>
                            <option value="<?= htmlspecialchars($status) ?>" <?= (isset($filters['status']) && $filters['status'] === $status) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>


    <?php if (empty($timesheets) && !$errorMessage): ?>
        <div class="alert alert-info">No timesheets found matching your criteria.</div>
    <?php elseif (!empty($timesheets)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead class="table-light">
                <tr>
                    <th>Staff Name</th>
                    <th>Site</th>
                    <th>Shift Date</th>
                    <th class="text-end">Hours</th>
                    <th>Status</th>
                    <th>Submitted</th>
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
                        <td><?= substr(nl2br(htmlspecialchars($timesheet->notes ?? '')), 0, 50) . (strlen($timesheet->notes ?? '') > 50 ? '...' : '') ?></td>
                        <td>
                            <a href="/supervisor/timesheets/edit/<?= htmlspecialchars($timesheet->id) ?>" class="btn btn-outline-primary btn-sm">Edit</a>
                            <?php // View details link could also be useful here, or edit covers it.
                            // Approve/Reject buttons could be here if status allows, but pending list is primary for that.
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>