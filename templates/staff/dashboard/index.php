<?php
/** @var string $pageTitle */
/** @var string $userName */
/** @var \App\UserManagement\Model\Site[] $assignedSites */
/** @var \App\UserManagement\Model\Timesheet[] $timesheetsPendingConfirmation */ // <-- New variable
/** @var string|null $dashboardErrorMessage */ // Renamed from sitesErrorMessage for clarity
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <?php if ($dashboardErrorMessage): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($dashboardErrorMessage) ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h2><?= htmlspecialchars($pageTitle) ?></h2>
                </div>
                <div class="card-body">
                    <p>Hello, <strong><?= htmlspecialchars($userName) ?></strong>! Welcome to your dashboard.</p>
                </div>
            </div>

            <?php if (!empty($timesheetsPendingConfirmation)): ?>
                <div class="card mb-4 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h4>Timesheets Awaiting Your Confirmation</h4>
                    </div>
                    <div class="card-body">
                        <p>Your supervisor has edited the following timesheets. Please review the changes.</p>
                        <ul class="list-group">
                            <?php foreach ($timesheetsPendingConfirmation as $timesheet): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Site:</strong> <?= htmlspecialchars($timesheet->site_name ?? 'N/A') ?> <br>
                                        <strong>Shift Date:</strong> <?= htmlspecialchars(date('D, M j, Y', strtotime($timesheet->shift_date))) ?> <br>
                                        <strong>Hours:</strong> <?= htmlspecialchars(number_format($timesheet->hours_worked, 2)) ?>
                                        (Edited by supervisor on <?= htmlspecialchars(date('M j, Y H:i', strtotime($timesheet->edited_at))) ?>)
                                    </div>
                                    <a href="/staff/timesheets/review/<?= $timesheet->id ?>" class="btn btn-outline-primary btn-sm">Review Changes</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Quick Actions</h4>
                </div>
                <div class="card-body">
                    <a href="/staff/timesheets/create" class="btn btn-primary me-2">Submit New Timesheet</a>
                    <a href="/staff/timesheets" class="btn btn-info">My Timesheet History</a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h4>Your Assigned Sites</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($assignedSites)): ?>
                        <ul class="list-group">
                            <?php foreach ($assignedSites as $site): ?>
                                <li class="list-group-item">
                                    <strong><?= htmlspecialchars($site->site_name) ?></strong>
                                    <?php if ($site->company_name): ?>
                                        (<?= htmlspecialchars($site->company_name) ?>)
                                    <?php endif; ?>
                                    <br>
                                    <small><?= nl2br(htmlspecialchars($site->site_address)) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>You are not currently assigned to any active sites. If you believe this is an error, please contact your supervisor.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Notifications</h4>
                </div>
                <div class="card-body">
                    <p><em>(Notifications will appear here.)</em></p>
                </div>
            </div>

        </div>
    </div>
</div>