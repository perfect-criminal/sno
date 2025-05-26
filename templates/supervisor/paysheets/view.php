<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Paysheet $paysheet */
/** @var \App\UserManagement\Model\PaysheetItem[] $paysheetItems */
// Assuming $auditLogs might also be passed here if you added that feature
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2><?= htmlspecialchars($pageTitle) ?></h2>
            <?php // Determine back link based on previous context or paysheet status ?>
            <?php if ($paysheet->status === 'AddressingReview' || $paysheet->status === 'Review'): ?>
                <a href="/supervisor/paysheets/under-review" class="btn btn-outline-secondary">Back to Under Review List</a>
            <?php else: ?>
                <a href="/supervisor/paysheets" class="btn btn-outline-secondary">Back to My Paysheets List</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <h4>Paysheet Summary</h4>

                <dl class="row">
                    <dt class="col-sm-3">Paysheet ID:</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($paysheet->id) ?></dd>
                    <dt class="col-sm-3">Submitted By (Supervisor):</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($paysheet->supervisor_name ?? 'N/A') ?> (ID: <?= htmlspecialchars($paysheet->supervisor_user_id) ?>)</dd>
                    <dt class="col-sm-3">Pay Period:</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars(date('M j, Y', strtotime($paysheet->pay_period_start_date))) ?> - <?= htmlspecialchars(date('M j, Y', strtotime($paysheet->pay_period_end_date))) ?></dd>
                    <dt class="col-sm-3">Total Calculated Amount:</dt>
                    <dd class="col-sm-9"><strong>$<?= htmlspecialchars(number_format($paysheet->total_hours_amount ?? 0, 2)) ?></strong></dd>
                    <dt class="col-sm-3">Status:</dt>
                    <dd class="col-sm-9">
                        <?php
                        $statusBadge = 'secondary'; // Default
                        if ($paysheet->status === 'Approved') $statusBadge = 'success';
                        elseif ($paysheet->status === 'Review') $statusBadge = 'warning';
                        elseif ($paysheet->status === 'Pending Payroll') $statusBadge = 'info';
                        elseif ($paysheet->status === 'Processed') $statusBadge = 'dark';
                        elseif ($paysheet->status === 'AddressingReview') $statusBadge = 'primary';
                        elseif ($paysheet->status === 'Cancelled') $statusBadge = 'danger';
                        ?>
                        <span class="badge bg-<?= $statusBadge ?>"><?= htmlspecialchars($paysheet->status) ?></span>
                    </dd>
                    <dt class="col-sm-3">Submitted At:</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($paysheet->submitted_at ? date('Y-m-d H:i', strtotime($paysheet->submitted_at)) : 'N/A') ?></dd>

                    <?php if ($paysheet->status === 'Review' || $paysheet->status === 'AddressingReview'): ?>
                        <?php if ($paysheet->review_remarks): ?>
                            <dt class="col-sm-3 text-danger">Payroll Remarks:</dt>
                            <dd class="col-sm-9 text-danger" style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($paysheet->review_remarks)) ?></dd>
                            <dt class="col-sm-3 text-danger">Marked for Review By:</dt>
                            <dd class="col-sm-9 text-danger"><?= htmlspecialchars($paysheet->payroll_reviewer_name ?? 'N/A') ?> (ID: <?= htmlspecialchars($paysheet->reviewed_by_payroll_id ?? 'N/A')?>)</dd>
                        <?php endif; ?>
                    <?php endif; ?>
                </dl>

                <hr>
                <h4>Paysheet Items</h4>
                <?php if (empty($paysheetItems)): ?>
                    <div class="alert alert-info">
                        <?php if ($paysheet->status === 'AddressingReview'): ?>
                            No items currently associated. This paysheet is awaiting re-evaluation and resubmission after corrections to underlying timesheets.
                        <?php else: ?>
                            No items found for this paysheet.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    {_-- ... (table display of paysheetItems as before) ... --_}
                <?php endif; ?>

                <?php // Display Audit Log if $auditLogs is passed and not empty ?>
                <?php if (isset($auditLogs) && !empty($auditLogs)): ?>
                    <hr>
                    <h4>Paysheet History (Audit Log)</h4>
                    {_-- ... (audit log table display as before) ... --_}
                <?php endif; ?>

                <hr>
                <div class="mt-3">
                    <?php // Supervisor Actions on this specific paysheet view page ?>
                    <?php if ($paysheet->status === 'AddressingReview'): ?>
                        <form action="/supervisor/paysheets/resubmit-reviewed/<?= htmlspecialchars($paysheet->id) ?>" method="POST" style="display: inline-block;" class="me-2" onsubmit="return confirm('Ensure all underlying timesheets are corrected and approved. This will regenerate items and resubmit this paysheet to payroll. Proceed?');">
                            <button type="submit" class="btn btn-success">Re-evaluate & Resubmit This Paysheet</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($paysheet->status === 'Review' || $paysheet->status === 'AddressingReview'): ?>
                        <form action="/supervisor/paysheets/cancel-review/<?= htmlspecialchars($paysheet->id) ?>" method="POST" style="display: inline-block;" class="mb-1" onsubmit="return confirm('Are you sure you want to cancel/recall this paysheet (ID: <?= htmlspecialchars($paysheet->id) ?>)? This will remove it and its items, making the original timesheets available for a new paysheet generation.');">
                            <button type="submit" class="btn btn-danger">Cancel This Paysheet</button>
                        </form>
                    <?php endif; ?>

                    <?php // Add other relevant supervisor actions here if applicable ?>
                </div>

        </div>
    </div>
</div>