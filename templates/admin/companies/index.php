<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Company[] $companies */
?>

    <h2><?= htmlspecialchars($pageTitle) ?></h2>

    <p><a href="/admin/companies/create" class="btn btn-primary mb-3">Create New Company</a></p>

<?php if (empty($companies)): ?>
    <p>No companies found.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
            <tr>
                <th>ID</th>
                <th>Company Name</th>
                <th>Contact Person</th>
                <th>Contact Email</th>
                <th>Contact Phone</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($companies as $company): ?>
                <tr>
                    <td><?= htmlspecialchars($company->id) ?></td>
                    <td><?= htmlspecialchars($company->company_name) ?></td>
                    <td><?= htmlspecialchars($company->contact_person ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($company->contact_email ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($company->contact_phone ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($company->created_at ? date('Y-m-d H:i', strtotime($company->created_at)) : 'N/A') ?></td>
                    <td>
                        <a href="/admin/companies/edit/<?= htmlspecialchars($company->id) ?>" class="btn btn-warning btn-sm">Edit</a>
                        <form action="/admin/companies/delete" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this company? This might affect associated sites.');">
                            <input type="hidden" name="company_id" value="<?= htmlspecialchars($company->id) ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>