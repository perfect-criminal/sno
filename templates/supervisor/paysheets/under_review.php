<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Paysheet[] $paysheets */
/** @var string|null $errorMessage */
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><?= htmlspecialchars($pageTitle) ?></h2>
        <a href="/supervisor/paysheets" class="btn btn-outline-secondary">Back to My Generated Paysheets</a>
    </div>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if (empty($paysheets) && !$errorMessage): ?>
        <div class="alert alert-info">You have no paysheets currently marked for your review by the Payroll team.</div>
    <?php elseif (!empty($paysheets)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Pay Period</th>
                    <th class="text-end">Total Amount</th>
                    <th>Payroll Reviewer</th>
                    <th>Payroll Remarks</th>
                    <th>Originally Submitted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($paysheets as $paysheet): ?>
                    <tr>
                        <td><?= htmlspecialchars($paysheet->id) ?></td>
                        <td><?= htmlspecialchars(date('M j, Y', strtotime($paysheet->pay_period_start_date))) ?> - <?= htmlspecialchars(date('M j, Y', strtotime($paysheet->pay_period_end_date))) ?></td>
                        <td class="text-end">$<?= htmlspecialchars(number_format($paysheet->total_hours_amount ?? 0, 2)) ?></td>
                        <td><?= htmlspecialchars($paysheet->payroll_reviewer_name ?? 'N/A') ?> (ID: <?= htmlspecialchars($paysheet->reviewed_by_payroll_id ?? 'N/A') ?>)</td>
                        <td style="white-space: pre-wrap; word-break: break-word; color: #dc3545;"><?= nl2br(htmlspecialchars($paysheet->review_remarks ?? 'No remarks.')) ?></td>
                        <td><?= htmlspecialchars($paysheet->submitted_at ? date('Y-m-d H:i', strtotime($paysheet->submitted_at)) : 'N/A') ?></td>
                        <td>
                            <a href="/supervisor/paysheets/view/<?= htmlspecialchars($paysheet->id) ?>" class="btn btn-info btn-sm mb-1 me-1">View Details</a>

                            <?php if ($paysheet->status === 'Review'): ?>
                                <form action="/supervisor/paysheets/acknowledge-review/<?= htmlspecialchars($paysheet->id) ?>" method="POST" style="display: inline;" onsubmit="return confirm('This will unlock associated timesheets for correction and remove current items from this paysheet. Are you sure?');">
                                    <button type="submit" class="btn btn-warning btn-sm mb-1 me-1">Acknowledge & Unlock</button>
                                </form>
                            <?php elseif ($paysheet->status === 'AddressingReview'): ?>
                                <form action="/supervisor/paysheets/resubmit-reviewed/<?= htmlspecialchars($paysheet->id) ?>" method="POST" style="display: inline;" onsubmit="return confirm('Ensure all underlying timesheets are corrected and approved. This will regenerate items and resubmit to payroll. Proceed?');">
                                    <button type="submit" class="btn btn-success btn-sm mb-1 me-1">Re-evaluate & Resubmit</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($paysheet->status === 'Review' || $paysheet->status === 'AddressingReview'): ?>
                                <form action="/supervisor/paysheets/cancel-review/<?= htmlspecialchars($paysheet->id) ?>" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel/recall this paysheet (ID: <?= htmlspecialchars($paysheet->id) ?>)? This will remove it and its items, making the original timesheets available for a new paysheet generation.');">
                                    <input type="hidden" name="paysheet_id" value="<?= htmlspecialchars($paysheet->id) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm mb-1">Cancel Paysheet</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/supervisor/paysheets/view/<?= htmlspecialchars($paysheet->id) ?>" class="btn btn-info btn-sm mb-1 me-1">View Details</a>

                            <?php if ($paysheet->status === 'Review'): ?>
                                <form action="/supervisor/paysheets/acknowledge-review/<?= htmlspecialchars($paysheet->id) ?>" method="POST" style="display: inline-block;" class="mb-1 me-1" onsubmit="return confirm('This will unlock the associated timesheets for correction and remove current items from this paysheet. Are you sure?');">
                                    <button type="submit" class="btn btn-warning btn-sm">Acknowledge & Unlock</button>
                                </form>
                            <?php elseif ($paysheet->status === 'AddressingReview'): ?>
                                <form action="/supervisor/paysheets/resubmit-reviewed/<?= htmlspecialchars($paysheet->id) ?>" method="POST" style="display: inline-block;" class="mb-1 me-1" onsubmit="return confirm('Ensure all underlying timesheets are corrected and approved. This will regenerate items and resubmit to payroll. Proceed?');">
                                    <button type="submit" class="btn btn-success btn-sm">Re-evaluate & Resubmit</button>
                                </form>
                            <?php endif; ?>

                            <?php // Cancel button is available for both 'Review' and 'AddressingReview' statuses ?>
                            <form action="/supervisor/paysheets/cancel-review/<?= htmlspecialchars($paysheet->id) ?>" method="POST" style="display: inline-block;" class="mb-1" onsubmit="return confirm('Are you sure you want to cancel/recall this paysheet (ID: <?= htmlspecialchars($paysheet->id) ?>)? This will remove it and its items, making the original timesheets available for a new paysheet generation.');">
                                <input type="hidden" name="paysheet_id" value="<?= htmlspecialchars($paysheet->id) ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Cancel Paysheet</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>