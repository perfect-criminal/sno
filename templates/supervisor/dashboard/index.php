<?php
/** @var string $pageTitle */
/** @var string $userName */
/** @var \App\UserManagement\Model\User[] $assignedStaff */
/** @var string|null $staffErrorMessage */
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h2><?= htmlspecialchars($pageTitle) ?></h2>
                </div>
                <div class="card-body">
                    <p>Hello, <strong><?= htmlspecialchars($userName) ?></strong>! Welcome to your supervisor dashboard.</p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h4>Quick Actions</h4>
                </div>
                <div class="card-body">
                    <a href="/supervisor/timesheets/pending" class="btn btn-primary">View Pending Timesheets</a>
                    <a href="/supervisor/timesheets/disputed" class="btn btn-warning me-2">View Disputed Timesheets</a>
                    <?php // Add more supervisor-specific quick action links here ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h4>Your Assigned Staff</h4>
                </div>
                <div class="card-body">
                    <?php if ($staffErrorMessage): ?>
                        <div class="alert alert-warning"><?= htmlspecialchars($staffErrorMessage) ?></div>
                    <?php elseif (!empty($assignedStaff)): ?>
                        <div class="list-group">
                            <?php foreach ($assignedStaff as $staff): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><?= htmlspecialchars($staff->getFullName()) ?></h5>
                                        <small>ID: <?= htmlspecialchars($staff->id) ?></small>
                                    </div>
                                    <p class="mb-1"><?= htmlspecialchars($staff->email) ?></p>
                                    <small>Status: <?= $staff->is_active ? 'Active' : 'Inactive' ?></small>
                                    <?php // You could add a link here to view this staff member's timesheets ?>
                                    <a href="/supervisor/staff/<?= $staff->id ?>/timesheets">View Timesheets</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>You do not have any active staff members currently assigned to you.</p>
                        <p class="small">If you believe this is an error, please contact an administrator to ensure staff are assigned to you and have the correct 'Staff' role.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Notifications</h4>
                </div>
                <div class="card-body">
                    <p><em>(Supervisor-specific notifications will appear here.)</em></p>
                </div>
            </div>

        </div>
    </div>
</div>