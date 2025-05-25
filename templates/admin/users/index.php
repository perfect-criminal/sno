<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\User[] $users */ // Helps with autocompletion
?>

    <h2><?= htmlspecialchars($pageTitle) ?></h2>

    <p><a href="/admin/users/create">Create New User</a></p> <?php // Link will be functional later ?>

<?php if (empty($users)): ?>
    <p>No users found.</p>
<?php else: ?>
    <style>
        table.admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.admin-table th, table.admin-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table.admin-table th { background-color: #f2f2f2; }
        table.admin-table tr:nth-child(even) { background-color: #f9f9f9; }
        table.admin-table .actions a { margin-right: 5px; text-decoration: none; }
    </style>

    <table class="admin-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user->id) ?></td>
                <td><?= htmlspecialchars($user->getFullName()) ?></td>
                <td><?= htmlspecialchars($user->email) ?></td>
                <td><?= htmlspecialchars($user->role_name) ?> (ID: <?= htmlspecialchars($user->role_id) ?>)</td>
                <td><?= $user->is_active ? 'Active' : 'Inactive' ?></td>
                <td><?= htmlspecialchars($user->created_at ? date('Y-m-d H:i', strtotime($user->created_at)) : 'N/A') ?></td>
                <td class="actions">
                    <a href="/admin/users/edit/<?= $user->id ?>">Edit</a> <?php // Placeholder link ?>
                    <a href="/admin/users/delete/<?= $user->id ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a> <?php // Placeholder link ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>