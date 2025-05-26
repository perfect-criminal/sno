<?php
/** @var string $pageTitle */
/** @var string $userName */
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h2><?= htmlspecialchars($pageTitle) ?></h2>
                </div>
                <div class="card-body">
                    <p>Hello, <strong><?= htmlspecialchars($userName) ?></strong>! Welcome to the Payroll dashboard.</p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h4>Quick Actions</h4>
                </div>
                <div class="card-body">
                    <a href="/payroll/paysheets/pending-review" class="btn btn-primary">View Paysheets for Review</a>
                    <?php // Add more payroll-specific quick action links here ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Notifications</h4>
                </div>
                <div class="card-body">
                    <p><em>(Payroll-specific notifications will appear here.)</em></p>
                </div>
            </div>
        </div>
    </div>
</div>