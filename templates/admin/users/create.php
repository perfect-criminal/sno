<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Role[] $roles */ // Array of Role objects
/** @var array $user_data */ // Previously submitted form data (for pre-filling on error)
/** @var array $errors */    // Validation errors
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h2><?= htmlspecialchars($pageTitle) ?></h2>
            </div>
            <div class="card-body">
                <form action="/admin/users/store" method="POST">
                    <?php // Display general errors or success messages if not using layout's flash messages for form-specific feedback ?>
                    <?php if (!empty($errors) && is_string($errors)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($errors) ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" id="first_name" name="first_name" value="<?= htmlspecialchars($user_data['first_name'] ?? '') ?>" required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['first_name']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" id="last_name" name="last_name" value="<?= htmlspecialchars($user_data['last_name'] ?? '') ?>" required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['last_name']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password" required>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['role_id']) ? 'is-invalid' : '' ?>" id="role_id" name="role_id" required>
                            <option value="">Select Role...</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars($role->id) ?>" <?= (isset($user_data['role_id']) && $user_data['role_id'] == $role->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role->role_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['role_id'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['role_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3" id="supervisor_assign_group">
                        <label for="supervisor_id" class="form-label">Assign Supervisor (for Staff role)</label>
                        <select class="form-select <?= isset($errors['supervisor_id']) ? 'is-invalid' : '' ?>" id="supervisor_id" name="supervisor_id">
                            <option value="">-- None --</option>
                            <?php if (!empty($supervisors)): ?>
                                <?php foreach ($supervisors as $supervisor): ?>
                                    <option value="<?= htmlspecialchars($supervisor->id) ?>"
                                        <?= (isset($user_data['supervisor_id']) && $user_data['supervisor_id'] == $supervisor->id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($supervisor->getFullName()) ?> (ID: <?= $supervisor->id ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No active supervisors available.</option>
                            <?php endif; ?>
                        </select>
                        <?php if (isset($errors['supervisor_id'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['supervisor_id']) ?></div>
                        <?php endif; ?>
                        <small class="form-text text-muted">Select a supervisor if the user's role is 'Staff' or similar.</small>
                    </div>

                    <div class="mb-3">
                        <label for="pay_rate" class="form-label">Pay Rate (per hour)</label>
                        <input type="number" step="0.01" class="form-control <?= isset($errors['pay_rate']) ? 'is-invalid' : '' ?>" id="pay_rate" name="pay_rate" value="<?= htmlspecialchars($user_data['pay_rate'] ?? '') ?>">
                        <?php if (isset($errors['pay_rate'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['pay_rate']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= (isset($user_data['is_active']) && $user_data['is_active'] == 1 || !isset($user_data['is_active']) && empty($user_data) ) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">User is Active</label>
                    </div>

                    <hr>

                    <button type="submit" class="btn btn-primary">Create User</button>
                    <a href="/admin/users" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>