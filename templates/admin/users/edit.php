<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\User $userToEdit */ // The user object to edit
/** @var \App\UserManagement\Model\Role[] $roles */
/** @var \App\UserManagement\Model\User[] $supervisors */
/** @var array $form_data */ // Sticky form data, from $_SESSION['form_data'] or (array)$userToEdit
/** @var array $errors */    // Validation errors
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h2><?= htmlspecialchars($pageTitle) ?></h2>
            </div>
            <div class="card-body">
                
                <form action="/admin/users/update/<?= htmlspecialchars($userToEdit->id) ?>" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" id="first_name" name="first_name" value="<?= htmlspecialchars($form_data['first_name'] ?? '') ?>" required>
                            <?php if (isset($errors['first_name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['first_name']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" id="last_name" name="last_name" value="<?= htmlspecialchars($form_data['last_name'] ?? '') ?>" required>
                            <?php if (isset($errors['last_name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['last_name']) ?></div><?php endif; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" required>
                        <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div><?php endif; ?>
                    </div>

                    <h5 class="mt-4">Change Password (optional)</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" placeholder="Leave blank to keep current">
                            <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password">
                            <?php if (isset($errors['confirm_password'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['confirm_password']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['role_id']) ? 'is-invalid' : '' ?>" id="role_id" name="role_id" required>
                            <option value="">Select Role...</option>
                            <?php foreach ($roles as $role_item): ?>
                                <option value="<?= htmlspecialchars($role_item->id) ?>"
                                    <?= (($form_data['role_id'] ?? $userToEdit->role_id) == $role_item->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role_item->role_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['role_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['role_id']) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3" id="supervisor_assign_group_edit">
                        <label for="supervisor_id" class="form-label">Assign Supervisor (for Staff role)</label>
                        <select class="form-select <?= isset($errors['supervisor_id']) ? 'is-invalid' : '' ?>" id="supervisor_id_edit" name="supervisor_id">
                            <option value="">-- None --</option>
                            <?php if (!empty($supervisors)): ?>
                                <?php foreach ($supervisors as $supervisor): ?>
                                    <option value="<?= htmlspecialchars($supervisor->id) ?>"
                                        <?= (($form_data['supervisor_id'] ?? $userToEdit->supervisor_id) == $supervisor->id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($supervisor->getFullName()) ?> (ID: <?= $supervisor->id ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No other active supervisors available.</option>
                            <?php endif; ?>
                        </select>
                        <?php if (isset($errors['supervisor_id'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['supervisor_id']) ?></div>
                        <?php endif; ?>
                        <small class="form-text text-muted">Select a supervisor if the user's role is 'Staff' or similar. User cannot be their own supervisor.</small>
                    </div>

                    <div class="mb-3">
                        <label for="pay_rate" class="form-label">Pay Rate (per hour)</label>
                        <input type="number" step="0.01" class="form-control <?= isset($errors['pay_rate']) ? 'is-invalid' : '' ?>" id="pay_rate" name="pay_rate" value="<?= htmlspecialchars($form_data['pay_rate'] ?? '') ?>">
                        <?php if (isset($errors['pay_rate'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['pay_rate']) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                            <?= ((isset($form_data['is_active']) && $form_data['is_active']) || (!isset($form_data['is_active']) && $userToEdit->is_active)) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">User is Active</label>
                    </div>

                    <hr>

                    <input type="hidden" name="user_id_form_field" value="<?= htmlspecialchars($userToEdit->id) ?>"> <?php // Changed name to avoid conflict with user_id in $_POST, though controller uses ID from URL ?>
                    <button type="submit" class="btn btn-primary">Update User</button>
                    <a href="/admin/users" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Optional: JavaScript to show/hide supervisor dropdown based on role selection
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelectEdit = document.getElementById('role_id');
        const supervisorGroupEdit = document.getElementById('supervisor_assign_group_edit');
        const staffRoleId = '2';

        function toggleSupervisorFieldEdit() {
            if (supervisorGroupEdit) {
                // Use $userToEdit->role_id which is passed to the view and available via PHP
                // or the currently selected role in the dropdown if a change event triggers this
                let currentRoleId = roleSelectEdit ? roleSelectEdit.value : '<?= $userToEdit->role_id ?? '' ?>';

                if (currentRoleId === staffRoleId) {
                    supervisorGroupEdit.style.display = 'block';
                } else {
                    supervisorGroupEdit.style.display = 'none';
                }
            }
        }

        if (roleSelectEdit) { // Check if roleSelectEdit exists before adding listener
            roleSelectEdit.addEventListener('change', toggleSupervisorFieldEdit);
        }
        toggleSupervisorFieldEdit(); // Call on page load
    });
</script>