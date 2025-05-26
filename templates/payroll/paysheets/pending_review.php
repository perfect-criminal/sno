<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Paysheet[] $paysheets */
/** @var string|null $errorMessage */
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><?= htmlspecialchars($pageTitle) ?></h2>
        <?php // Link back to Payroll Dashboard ?>
        <a href="/payroll/dashboard" class="btn btn-outline-secondary">Back to Payroll Dashboard</a>
    </div>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if (empty($paysheets) && !$errorMessage): ?>
        <div class="alert alert-info">There are no paysheets currently pending your review.</div>
    <?php elseif (!empty($paysheets)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                <tr>
                    <th>Paysheet ID</th>
                    <th>Submitted By (Supervisor)</th>
                    <th>Pay Period Start</th>
                    <th>Pay Period End</th>
                    <th class="text-end">Total Amount</th>
                    <th>Submitted At</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($paysheets as $paysheet): ?>
                    <tr>
                        <td><?= htmlspecialchars($paysheet->id) ?></td>
                        <td><?= htmlspecialchars($paysheet->supervisor_name ?? 'N/A') ?> </td>
                        <td><?= htmlspecialchars(date('M j, Y', strtotime($paysheet->pay_period_start_date))) ?></td>
                        <td><?= htmlspecialchars(date('M j, Y', strtotime($paysheet->pay_period_end_date))) ?></td>
                        <td class="text-end">$<?= htmlspecialchars(number_format($paysheet->total_hours_amount ?? 0, 2)) ?></td>
                        <td><?= htmlspecialchars($paysheet->submitted_at ? date('Y-m-d H:i', strtotime($paysheet->submitted_at)) : 'N/A') ?></td>
                        <td>
                            <a href="/payroll/paysheets/review/<?= htmlspecialchars($paysheet->id) ?>" class="btn btn-info btn-sm">Review Details</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>