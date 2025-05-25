<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Site[] $sites */
?>

    <h2><?= htmlspecialchars($pageTitle) ?></h2>

    <p><a href="/admin/sites/create" class="btn btn-primary mb-3">Create New Site</a></p>

<?php if (empty($sites)): ?>
    <p>No sites found.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
            <tr>
                <th>ID</th>
                <th>Site Name</th>
                <th>Associated Company</th>
                <th>Address</th>
                <th>Budget/Period</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($sites as $site): ?>
                <tr>
                    <td><?= htmlspecialchars($site->id) ?></td>
                    <td><?= htmlspecialchars($site->site_name) ?></td>
                    <td><?= htmlspecialchars($site->company_name ?? 'Direct Contract / N/A') ?></td>
                    <td><?= nl2br(htmlspecialchars($site->site_address)) ?></td>
                    <td><?= $site->budget_per_pay_period !== null ? '$' . number_format($site->budget_per_pay_period, 2) : 'N/A' ?></td>
                    <td><?= $site->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                    <td><?= htmlspecialchars($site->created_at ? date('Y-m-d H:i', strtotime($site->created_at)) : 'N/A') ?></td>
                    <td>
                        <a href="/admin/sites/edit/<?= htmlspecialchars($site->id) ?>" class="btn btn-warning btn-sm mb-1">Edit</a>
                        <form action="/admin/sites/delete" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this site?');">
                            <input type="hidden" name="site_id" value="<?= htmlspecialchars($site->id) ?>">
                            <button type="submit" class="btn btn-danger btn-sm mb-1">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>