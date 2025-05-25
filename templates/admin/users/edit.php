<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\User $user */     // The user object to edit
/** @var \App\UserManagement\Model\Role[] $roles */ // Array of Role objects
/** @var array $form_data */ // Old form data for pre-filling (might be identical to $user initially)
/** @var array $errors */    // Validation errors
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h2><?= htmlspecialchars($pageTitle) ?></h2>
            </div>
            <div class="card-body">
                <form action="/admin/users/update/<?= htmlspecialchars($user->id) ?>" method="POST">
                    <?php // Display general errors ?>
                    <?php if (!empty($errors) && is_string($errors)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($errors) ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" id="first_name" name="first_name" value="<?= htmlspecialchars($form_data['first_name'] ?? $user->first_name ?? '') ?>" required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['first_name']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" id="last_name" name="last_name" value="<?= htmlspecialchars($form_data['last_name'] ?? $user->last_name ?? '') ?>" required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['last_name']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($form_data['email'] ?? $user->email ?? '') ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <h5 class="mt-4">Change Password (optional)</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" placeholder="Leave blank to keep current password">
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password">
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['role_id']) ? 'is-invalid' : '' ?>" id="role_id" name="role_id" required>
                            <option value="">Select Role...</option>
                            <?php foreach ($roles as $role_item): ?>
                                <option value="<?= htmlspecialchars($role_item->id) ?>" <?= ((isset($form_data['role_id']) && $form_data['role_id'] == $role_item->id) || (!isset($form_data['role_id']) && $user->role_id == $role_item->id)) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role_item->role_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['role_id'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['role_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="pay_rate" class="form-label">Pay Rate (per hour)</label>
                        <input type="number" step="0.01" class="form-control <?= isset($errors['pay_rate']) ? 'is-invalid' : '' ?>" id="pay_rate" name="pay_rate" value="<?= htmlspecialchars($form_data['pay_rate'] ?? $user->pay_rate ?? '') ?>">
                        <?php if (isset($errors['pay_rate'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['pay_rate']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= ((isset($form_data['is_active']) && $form_data['is_active']) || (!isset($form_data['is_active']) && $user->is_active)) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">User is Active</label>
                    </div>

                    <hr>
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user->id) ?>"> <?php // Keep user_id for update method, though it's also in URL ?>
                    <button type="submit" class="btn btn-primary">Update User</button>
                    <a href="/admin/users" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>