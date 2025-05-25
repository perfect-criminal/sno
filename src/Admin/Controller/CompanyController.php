<?php

namespace App\Admin\Controller;

use App\Core\View;
use App\UserManagement\Model\Company; // Updated namespace for Company model
use Exception;

class CompanyController
{
    // Re-using the admin check logic from UserController.
    // Consider moving isAdmin/enforceAdmin to a base AdminController or a Trait later if you have many admin controllers.
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
            $companies = Company::findAll();
            View::render('admin/companies/index', [
                'pageTitle' => 'Manage Companies',
                'companies' => $companies
            ], 'app');
        } catch (Exception $e) {
            error_log("Error in CompanyController::index: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load company data.'];
            View::render('error/generic', ['pageTitle' => 'Error', 'errorMessage' => 'Could not load company data.'], 'app');
        }
    }

    public function create(): void
    {
        $this->enforceAdmin();
        View::render('admin/companies/create', [
            'pageTitle' => 'Create New Company',
            'company_data' => $_SESSION['form_data'] ?? [], // For pre-filling form on error
            'errors' => $_SESSION['form_errors'] ?? []
        ], 'app');
        unset($_SESSION['form_errors']);
        unset($_SESSION['form_data']);
    }

    public function store(): void
    {
        $this->enforceAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/companies/create');
            exit;
        }

        $errors = [];
        $formData = $_POST;

        if (empty($formData['company_name'])) {
            $errors['company_name'] = 'Company Name is required.';
        } elseif (Company::nameExists(trim($formData['company_name']))) {
            $errors['company_name'] = 'This Company Name already exists.';
        }
        // Add more validation for other fields if necessary (e.g., email format)
        if (!empty($formData['contact_email']) && !filter_var($formData['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = 'Invalid contact email format.';
        }


        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $formData;
            header('Location: /admin/companies/create');
            exit;
        }

        try {
            $company = new Company();
            $company->company_name = trim($formData['company_name']);
            $company->contact_person = !empty($formData['contact_person']) ? trim($formData['contact_person']) : null;
            $company->contact_email = !empty($formData['contact_email']) ? trim($formData['contact_email']) : null;
            $company->contact_phone = !empty($formData['contact_phone']) ? trim($formData['contact_phone']) : null;
            $company->address = !empty($formData['address']) ? trim($formData['address']) : null;

            if ($company->save()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Company created successfully!'];
                header('Location: /admin/companies');
            } else {
                $_SESSION['form_data'] = $formData;
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to create company. Please try again.'];
                header('Location: /admin/companies/create');
            }
        } catch (Exception $e) {
            error_log("Error in CompanyController::store: " . $e->getMessage());
            $_SESSION['form_data'] = $formData;
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
            header('Location: /admin/companies/create');
        }
        exit;
    }

    public function edit(string $id): void
    {
        $this->enforceAdmin();
        $companyId = (int)$id;
        try {
            $company = Company::findById($companyId);
            if (!$company) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Company not found.'];
                header('Location: /admin/companies');
                exit;
            }
            View::render('admin/companies/edit', [
                'pageTitle' => 'Edit Company: ' . htmlspecialchars($company->company_name),
                'company' => $company,
                'form_data' => $_SESSION['form_data'] ?? (array)$company, // Pre-fill from session on error, else from company
                'errors' => $_SESSION['form_errors'] ?? []
            ], 'app');
            unset($_SESSION['form_errors']);
            unset($_SESSION['form_data']);
        } catch (Exception $e) {
            error_log("Error in CompanyController::edit for ID {$companyId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load company edit form.'];
            header('Location: /admin/companies');
            exit;
        }
    }

    public function update(string $id): void
    {
        $this->enforceAdmin();
        $companyId = (int)$id;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/companies/edit/' . $companyId);
            exit;
        }

        $company = Company::findById($companyId);
        if (!$company) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Company not found for update.'];
            header('Location: /admin/companies');
            exit;
        }

        $errors = [];
        $formData = $_POST;

        if (empty($formData['company_name'])) {
            $errors['company_name'] = 'Company Name is required.';
        } elseif ($formData['company_name'] !== $company->company_name && Company::nameExists(trim($formData['company_name']), $companyId)) {
            $errors['company_name'] = 'This Company Name already exists.';
        }
        if (!empty($formData['contact_email']) && !filter_var($formData['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = 'Invalid contact email format.';
        }


        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $formData;
            header('Location: /admin/companies/edit/' . $companyId);
            exit;
        }

        try {
            $company->company_name = trim($formData['company_name']);
            $company->contact_person = !empty($formData['contact_person']) ? trim($formData['contact_person']) : null;
            $company->contact_email = !empty($formData['contact_email']) ? trim($formData['contact_email']) : null;
            $company->contact_phone = !empty($formData['contact_phone']) ? trim($formData['contact_phone']) : null;
            $company->address = !empty($formData['address']) ? trim($formData['address']) : null;

            if ($company->save()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Company updated successfully!'];
                header('Location: /admin/companies');
            } else {
                $_SESSION['form_data'] = $formData;
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to update company.'];
                header('Location: /admin/companies/edit/' . $companyId);
            }
        } catch (Exception $e) {
            error_log("Error in CompanyController::update for ID {$companyId}: " . $e->getMessage());
            $_SESSION['form_data'] = $formData;
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
            header('Location: /admin/companies/edit/' . $companyId);
        }
        exit;
    }

    public function delete(): void
    {
        $this->enforceAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['company_id'])) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Invalid request.'];
            header('Location: /admin/companies');
            exit;
        }
        $companyId = (int)$_POST['company_id'];
        try {
            if (Company::softDelete($companyId)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Company soft deleted successfully.'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to delete company.'];
            }
        } catch (Exception $e) {
            error_log("Error in CompanyController::delete for ID {$companyId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
        }
        header('Location: /admin/companies');
        exit;
    }
}