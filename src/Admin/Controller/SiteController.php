<?php

namespace App\Admin\Controller;

use App\Core\View;
use App\UserManagement\Model\Site;    // Assuming Site model is in UserManagement
use App\UserManagement\Model\Company; // To fetch companies for dropdown
use Exception;

class SiteController
{
    private function isAdmin(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] === 1;
    }

    private function enforceAdmin(): void
    {
        if (!$this->isAdmin()) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Access Denied. Administrator access required.'];
            header('Location: /login');
            exit;
        }
    }

    public function index(): void
    {
        $this->enforceAdmin();
        try {
            $sites = Site::findAll();
            View::render('admin/sites/index', [
                'pageTitle' => 'Manage Sites',
                'sites' => $sites
            ], 'app');
        } catch (Exception $e) {
            error_log("Error in SiteController::index: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load site data.'];
            View::render('error/generic', ['pageTitle' => 'Error', 'errorMessage' => 'Could not load site data.'], 'app');
        }
    }

    public function create(): void
    {
        $this->enforceAdmin();
        try {
            $companies = Company::findAll(); // For company dropdown
            View::render('admin/sites/create', [
                'pageTitle' => 'Create New Site',
                'companies' => $companies,
                'site_data' => $_SESSION['form_data'] ?? [],
                'errors' => $_SESSION['form_errors'] ?? []
            ], 'app');
            unset($_SESSION['form_errors']);
            unset($_SESSION['form_data']);
        } catch (Exception $e) {
            error_log("Error in SiteController::create: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load site creation form.'];
            header('Location: /admin/sites');
            exit;
        }
    }

    public function store(): void
    {
        $this->enforceAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/sites/create');
            exit;
        }

        $errors = [];
        $formData = $_POST;

        if (empty($formData['site_name'])) {
            $errors['site_name'] = 'Site Name is required.';
        }
        if (empty($formData['site_address'])) {
            $errors['site_address'] = 'Site Address is required.';
        }
        // Add more validation (e.g., budget format)
        if (!empty($formData['budget_per_pay_period']) && !is_numeric($formData['budget_per_pay_period'])) {
            $errors['budget_per_pay_period'] = 'Budget must be a valid number.';
        }


        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $formData;
            header('Location: /admin/sites/create');
            exit;
        }

        try {
            $site = new Site();
            $site->site_name = trim($formData['site_name']);
            $site->site_address = trim($formData['site_address']);
            $site->company_id = !empty($formData['company_id']) ? (int)$formData['company_id'] : null;
            $site->budget_per_pay_period = !empty($formData['budget_per_pay_period']) ? (float)$formData['budget_per_pay_period'] : null;
            $site->is_active = isset($formData['is_active']) ? 1 : 0;

            if ($site->save()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Site created successfully!'];
                header('Location: /admin/sites');
            } else {
                $_SESSION['form_data'] = $formData;
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to create site. Please try again.'];
                header('Location: /admin/sites/create');
            }
        } catch (Exception $e) {
            error_log("Error in SiteController::store: " . $e->getMessage());
            $_SESSION['form_data'] = $formData;
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
            header('Location: /admin/sites/create');
        }
        exit;
    }

    public function edit(string $id): void
    {
        $this->enforceAdmin();
        $siteId = (int)$id;
        try {
            $site = Site::findById($siteId);
            if (!$site) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Site not found.'];
                header('Location: /admin/sites');
                exit;
            }
            $companies = Company::findAll(); // For company dropdown

            View::render('admin/sites/edit', [
                'pageTitle' => 'Edit Site: ' . htmlspecialchars($site->site_name),
                'site' => $site,
                'companies' => $companies,
                'form_data' => $_SESSION['form_data'] ?? (array)$site,
                'errors' => $_SESSION['form_errors'] ?? []
            ], 'app');
            unset($_SESSION['form_errors']);
            unset($_SESSION['form_data']);
        } catch (Exception $e) {
            error_log("Error in SiteController::edit for ID {$siteId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load site edit form.'];
            header('Location: /admin/sites');
            exit;
        }
    }

    public function update(string $id): void
    {
        $this->enforceAdmin();
        $siteId = (int)$id;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/sites/edit/' . $siteId);
            exit;
        }

        $site = Site::findById($siteId);
        if (!$site) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Site not found for update.'];
            header('Location: /admin/sites');
            exit;
        }

        $errors = [];
        $formData = $_POST;

        if (empty($formData['site_name'])) {
            $errors['site_name'] = 'Site Name is required.';
        }
        if (empty($formData['site_address'])) {
            $errors['site_address'] = 'Site Address is required.';
        }
        if (!empty($formData['budget_per_pay_period']) && !is_numeric($formData['budget_per_pay_period'])) {
            $errors['budget_per_pay_period'] = 'Budget must be a valid number.';
        }


        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $formData;
            header('Location: /admin/sites/edit/' . $siteId);
            exit;
        }

        try {
            $site->site_name = trim($formData['site_name']);
            $site->site_address = trim($formData['site_address']);
            $site->company_id = !empty($formData['company_id']) ? (int)$formData['company_id'] : null;
            $site->budget_per_pay_period = !empty($formData['budget_per_pay_period']) ? (float)$formData['budget_per_pay_period'] : null;
            $site->is_active = isset($formData['is_active']) ? 1 : 0;

            if ($site->save()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Site updated successfully!'];
                header('Location: /admin/sites');
            } else {
                $_SESSION['form_data'] = $formData;
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to update site.'];
                header('Location: /admin/sites/edit/' . $siteId);
            }
        } catch (Exception $e) {
            error_log("Error in SiteController::update for ID {$siteId}: " . $e->getMessage());
            $_SESSION['form_data'] = $formData;
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
            header('Location: /admin/sites/edit/' . $siteId);
        }
        exit;
    }

    public function delete(): void
    {
        $this->enforceAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['site_id'])) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Invalid request.'];
            header('Location: /admin/sites');
            exit;
        }
        $siteId = (int)$_POST['site_id'];
        try {
            // Add a check here: Can this site be deleted? (e.g., are there active timesheets for it?)
            // For now, simple delete.
            if (Site::softDelete($siteId)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Site soft deleted successfully.'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to delete site.'];
            }
        } catch (Exception $e) {
            error_log("Error in SiteController::delete for ID {$siteId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
        }
        header('Location: /admin/sites');
        exit;
    }
}