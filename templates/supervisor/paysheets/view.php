<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Paysheet $paysheet */
/** @var \App\UserManagement\Model\PaysheetItem[] $paysheetItems */
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2><?= htmlspecialchars($pageTitle) ?></h2>
            <a href="/supervisor/paysheets" class="btn btn-outline-secondary">Back to Paysheet List</a>
        </div>
        <div class="card-body">
            <h4>Paysheet Summary</h4>
            <dl class="row">
                <dt class="col-sm-3">Paysheet ID:</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($paysheet->id) ?></dd>

                <dt class="col-sm-3">Pay Period:</dt>
                <dd class="col-sm-9"><?= htmlspecialchars(date('M j, Y', strtotime($paysheet->pay_period_start_date))) ?> - <?= htmlspecialchars(date('M j, Y', strtotime($paysheet->pay_period_end_date))) ?></dd>

                <dt class="col-sm-3">Total Amount:</dt>
                <dd class="col-sm-9"><strong>$<?= htmlspecialchars(number_format($paysheet->total_hours_amount ?? 0, 2)) ?></strong></dd>

                <dt class="col-sm-3">Status:</dt>
                <dd class="col-sm-9">
                    <?php
                    $statusBadge = 'secondary'; // Default
                    if ($paysheet->status === 'Approved') $statusBadge = 'success';
                    elseif ($paysheet->status === 'Review') $statusBadge = 'warning';
                    elseif ($paysheet->status === 'Pending Payroll') $statusBadge = 'info';
                    elseif ($paysheet->status === 'Processed') $statusBadge = 'dark';
                    ?>
                    <span class="badge bg-<?= $statusBadge ?>"><?= htmlspecialchars($paysheet->status) ?></span>
                </dd>

                <dt class="col-sm-3">Submitted At:</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($paysheet->submitted_at ? date('Y-m-d H:i', strtotime($paysheet->submitted_at)) : 'N/A') ?></dd>
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
        </div>
    </div>
</div>