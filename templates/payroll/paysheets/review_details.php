<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Paysheet $paysheet */
/** @var \App\UserManagement\Model\PaysheetItem[] $paysheetItems */
/** @var \App\UserManagement\Model\AuditLog[] $auditLogs */
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2><?= htmlspecialchars($pageTitle) ?></h2>
            <a href="/payroll/paysheets/pending-review" class="btn btn-outline-secondary">Back to Pending List</a>
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
                    elseif ($paysheet->status === 'Cancelled') $statusBadge = 'danger';
                    ?>
                    <span class="badge bg-<?= $statusBadge ?>"><?= htmlspecialchars($paysheet->status) ?></span>
                </dd>

                <dt class="col-sm-3">Submitted At (by Supervisor):</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($paysheet->submitted_at ? date('Y-m-d H:i', strtotime($paysheet->submitted_at)) : 'N/A') ?></dd>

                <?php if ($paysheet->status === 'Review' && $paysheet->review_remarks): ?>
                    <dt class="col-sm-3 text-danger">Payroll Remarks:</dt>
                    <dd class="col-sm-9 text-danger" style="white-space: pre-wrap;"><?= htmlspecialchars($paysheet->review_remarks) ?></dd>
                    <dt class="col-sm-3 text-danger">Marked for Review By:</dt>
                    <dd class="col-sm-9 text-danger"><?= htmlspecialchars($paysheet->payroll_reviewer_name ?? 'N/A') ?> (ID: <?= htmlspecialchars($paysheet->reviewed_by_payroll_id ?? 'N/A')?>)</dd>
                <?php endif; ?>

                <?php if ($paysheet->status === 'Approved' && $paysheet->approved_by_payroll_id): ?>
                    <dt class="col-sm-3 text-success">Approved By Payroll:</dt>
                    <dd class="col-sm-9 text-success">
                        <?php // You might need to fetch payroll approver name if not already on $paysheet object
                        // For now, assuming it might be available via payroll_reviewer_name or a similar property if populated
                        // Let's display the ID and date for now.
                        ?>
                        User ID: <?= htmlspecialchars($paysheet->approved_by_payroll_id) ?> on <?= htmlspecialchars($paysheet->approved_at ? date('Y-m-d H:i', strtotime($paysheet->approved_at)) : 'N/A') ?>
                    </dd>
                <?php endif; ?>
            </dl>

            <hr>
            <h4>Paysheet Items</h4>
            <?php if (empty($paysheetItems)): ?>
                <div class="alert alert-info">No items found for this paysheet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover">
                        <thead class="table-light">
                        <tr>
                            <th>Staff Name</th>
                            <th>Site Name</th>
                            <th>Shift Date</th>
                            <th class="text-end">Hours</th>
                            <th class="text-end">Pay Rate Used</th>
                            <th class="text-end">Calculated Pay</th>
                            <th>Timesheet ID</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($paysheetItems as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item->staff_name ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($item->site_name ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($item->shift_date ? date('D, M j, Y', strtotime($item->shift_date)) : 'N/A') ?></td>
                                <td class="text-end"><?= htmlspecialchars(number_format($item->hours_worked_snapshot, 2)) ?></td>
                                <td class="text-end">$<?= htmlspecialchars(number_format($item->pay_rate_snapshot, 2)) ?></td>
                                <td class="text-end"><strong>$<?= htmlspecialchars(number_format($item->calculated_pay, 2)) ?></strong></td>
                                <td><?= htmlspecialchars($item->timesheet_id) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>


            <hr>
            <h4>Paysheet History (Audit Log)</h4>
            <?php if (empty($auditLogs)): ?>
                <div class="alert alert-info">No history found for this paysheet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($auditLogs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log->created_at ? date('Y-m-d H:i:s', strtotime($log->created_at)) : 'N/A') ?></td> {/* <-- CORRECTED: $log->created_at */}
                                <td><?= htmlspecialchars($log->user_name ?? 'System/Unknown') ?> (ID: <?= htmlspecialchars($log->user_id ?? 'N/A') ?>)</td>
                                <td><?= htmlspecialchars(str_replace('_', ' ', $log->action)) ?></td>
                                <td>
                                    <?php
                                    if ($log->details) {
                                        $detailsArray = json_decode($log->details, true);
                                        if (json_last_error() === JSON_ERROR_NONE && is_array($detailsArray)) {
                                            echo "<ul class='list-unstyled mb-0 small'>";
                                            foreach ($detailsArray as $key => $value) {
                                                echo "<li><strong>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . ":</strong> " . htmlspecialchars($value) . "</li>";
                                            }
                                            echo "</ul>";
                                        } else {
                                            echo nl2br(htmlspecialchars($log->details));
                                        }
                                    } else {
                                        echo "N/A";
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <hr>
            <div class="mt-3">
                <?php if ($paysheet->status === 'Pending Payroll'): ?>
                    <form action="/payroll/paysheets/approve/<?= htmlspecialchars($paysheet->id) ?>" method="POST" style="display: inline-block;" class="me-2">
                        <button type="submit" class="btn btn-success">Approve for Payroll Run</button>
                    </form>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#markForReviewModal">
                        Mark for Supervisor Review
                    </button>
                <?php elseif ($paysheet->status === 'Review'): ?>
                    <p class="text-warning fw-bold">This paysheet is currently under 'Review' by the supervisor (<?= htmlspecialchars($paysheet->supervisor_name ?? '') ?>).</p>
                    <p class="text-muted">Payroll Remarks Sent: <?= nl2br(htmlspecialchars($paysheet->review_remarks ?? '')) ?></p>
                    <form action="/payroll/paysheets/approve/<?= htmlspecialchars($paysheet->id) ?>" method="POST" style="display: inline-block;" class="me-2" onsubmit="return confirm('This paysheet is marked for supervisor review. Are you sure you want to override and approve it directly?');">
                        <button type="submit" class="btn btn-success">Approve Anyway (Override)</button>
                    </form>
                <?php elseif ($paysheet->status === 'Approved'): ?>
                    <p class="text-success fw-bold">This paysheet has been approved for a payroll run.</p>
                    <p class="text-muted">Approved by Payroll User ID: <?= htmlspecialchars($paysheet->approved_by_payroll_id ?? 'N/A') ?> on <?= htmlspecialchars($paysheet->approved_at ? date('Y-m-d H:i', strtotime($paysheet->approved_at)) : 'N/A') ?></p>
                <?php elseif ($paysheet->status === 'Cancelled'): ?>
                    <p class="text-danger fw-bold">This paysheet has been cancelled by the supervisor.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="markForReviewModal" tabindex="-1" aria-labelledby="markForReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="/payroll/paysheets/mark-review/<?= htmlspecialchars($paysheet->id) ?>" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="markForReviewModalLabel">Mark Paysheet for Supervisor Review (ID: <?= htmlspecialchars($paysheet->id) ?>)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="review_remarks" class="form-label">Remarks for Supervisor (Reason for review) <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="review_remarks" name="review_remarks" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Submit for Review</button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>