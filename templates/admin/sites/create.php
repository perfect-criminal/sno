<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Company[] $companies */
/** @var array $site_data */ // Previously submitted form data
/** @var array $errors */    // Validation errors
?>
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h2><?= htmlspecialchars($pageTitle) ?></h2>
            </div>
            <div class="card-body">
                <form action="/admin/sites/store" method="POST">
                    <div class="mb-3">
                        <label for="site_name" class="form-label">Site Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['site_name']) ? 'is-invalid' : '' ?>" id="site_name" name="site_name" value="<?= htmlspecialchars($site_data['site_name'] ?? '') ?>" required>
                        <?php if (isset($errors['site_name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['site_name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="company_id" class="form-label">Associated Company (Optional)</label>
                        <select class="form-select <?= isset($errors['company_id']) ? 'is-invalid' : '' ?>" id="company_id" name="company_id">
                            <option value="">-- Direct Contract / No Company --</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?= htmlspecialchars($company->id) ?>" <?= (isset($site_data['company_id']) && $site_data['company_id'] == $company->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($company->company_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['company_id'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['company_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="site_address" class="form-label">Site Address <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors['site_address']) ? 'is-invalid' : '' ?>" id="site_address" name="site_address" rows="3" required><?= htmlspecialchars($site_data['site_address'] ?? '') ?></textarea>
                        <?php if (isset($errors['site_address'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['site_address']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="budget_per_pay_period" class="form-label">Budget per Pay Period (e.g., 1500.00)</label>
                        <input type="number" step="0.01" class="form-control <?= isset($errors['budget_per_pay_period']) ? 'is-invalid' : '' ?>" id="budget_per_pay_period" name="budget_per_pay_period" value="<?= htmlspecialchars($site_data['budget_per_pay_period'] ?? '') ?>">
                        <?php if (isset($errors['budget_per_pay_period'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['budget_per_pay_period']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= (isset($site_data['is_active']) && $site_data['is_active'] == 1) || (!isset($site_data['is_active']) && empty($site_data)) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Site is Active</label>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-primary">Create Site</button>
                    <a href="/admin/sites" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>