<?php
/** @var string $pageTitle */
/** @var \App\UserManagement\Model\Company $company */ // The company object to edit
/** @var array $form_data */ // Old form data for pre-filling (prioritizes this over $company for sticky form)
/** @var array $errors */    // Validation errors
?>
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h2><?= htmlspecialchars($pageTitle) ?></h2>
            </div>
            <div class="card-body">
                <form action="/admin/companies/update/<?= htmlspecialchars($company->id) ?>" method="POST">
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['company_name']) ? 'is-invalid' : '' ?>" id="company_name" name="company_name" value="<?= htmlspecialchars($form_data['company_name'] ?? $company->company_name ?? '') ?>" required>
                        <?php if (isset($errors['company_name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['company_name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="contact_person" class="form-label">Contact Person</label>
                        <input type="text" class="form-control <?= isset($errors['contact_person']) ? 'is-invalid' : '' ?>" id="contact_person" name="contact_person" value="<?= htmlspecialchars($form_data['contact_person'] ?? $company->contact_person ?? '') ?>">
                        <?php if (isset($errors['contact_person'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['contact_person']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="contact_email" class="form-label">Contact Email</label>
                        <input type="email" class="form-control <?= isset($errors['contact_email']) ? 'is-invalid' : '' ?>" id="contact_email" name="contact_email" value="<?= htmlspecialchars($form_data['contact_email'] ?? $company->contact_email ?? '') ?>">
                        <?php if (isset($errors['contact_email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['contact_email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="contact_phone" class="form-label">Contact Phone</label>
                        <input type="tel" class="form-control <?= isset($errors['contact_phone']) ? 'is-invalid' : '' ?>" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars($form_data['contact_phone'] ?? $company->contact_phone ?? '') ?>">
                        <?php if (isset($errors['contact_phone'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['contact_phone']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" id="address" name="address" rows="3"><?= htmlspecialchars($form_data['address'] ?? $company->address ?? '') ?></textarea>
                        <?php if (isset($errors['address'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['address']) ?></div>
                        <?php endif; ?>
                    </div>

                    <hr>
                    <input type="hidden" name="company_id" value="<?= htmlspecialchars($company->id) ?>">
                    <button type="submit" class="btn btn-primary">Update Company</button>
                    <a href="/admin/companies" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>