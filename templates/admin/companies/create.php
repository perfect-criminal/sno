<?php
/** @var string $pageTitle */
/** @var array $company_data */ // Previously submitted form data
/** @var array $errors */       // Validation errors
?>
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h2><?= htmlspecialchars($pageTitle) ?></h2>
            </div>
            <div class="card-body">
                <form action="/admin/companies/store" method="POST">
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['company_name']) ? 'is-invalid' : '' ?>" id="company_name" name="company_name" value="<?= htmlspecialchars($company_data['company_name'] ?? '') ?>" required>
                        <?php if (isset($errors['company_name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['company_name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="contact_person" class="form-label">Contact Person</label>
                        <input type="text" class="form-control <?= isset($errors['contact_person']) ? 'is-invalid' : '' ?>" id="contact_person" name="contact_person" value="<?= htmlspecialchars($company_data['contact_person'] ?? '') ?>">
                        <?php if (isset($errors['contact_person'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['contact_person']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="contact_email" class="form-label">Contact Email</label>
                        <input type="email" class="form-control <?= isset($errors['contact_email']) ? 'is-invalid' : '' ?>" id="contact_email" name="contact_email" value="<?= htmlspecialchars($company_data['contact_email'] ?? '') ?>">
                        <?php if (isset($errors['contact_email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['contact_email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="contact_phone" class="form-label">Contact Phone</label>
                        <input type="tel" class="form-control <?= isset($errors['contact_phone']) ? 'is-invalid' : '' ?>" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars($company_data['contact_phone'] ?? '') ?>">
                        <?php if (isset($errors['contact_phone'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['contact_phone']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" id="address" name="address" rows="3"><?= htmlspecialchars($company_data['address'] ?? '') ?></textarea>
                        <?php if (isset($errors['address'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['address']) ?></div>
                        <?php endif; ?>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-primary">Create Company</button>
                    <a href="/admin/companies" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>