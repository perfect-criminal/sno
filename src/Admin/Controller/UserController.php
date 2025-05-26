<?php

namespace App\Admin\Controller;

use App\Core\View;
use App\UserManagement\Model\User;
use App\UserManagement\Model\Role;
use Exception;

class UserController
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
            $users = User::findAll(); // User::findAll() should fetch role_name as well
            View::render('admin/users/index', [
                'pageTitle' => 'Manage Users',
                'users' => $users
            ], 'app');
        } catch (Exception $e) {
            error_log("Error in UserController::index: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load user data.'];
            View::render('error/generic', ['pageTitle' => 'Error', 'errorMessage' => $_SESSION['flash_message']['message'] ?? $e->getMessage()], 'app');
        }
    }

    /**
     * Display the form to create a new user.
     */
    public function create(): void
    {
        $this->enforceAdmin();
        try {
            $roles = Role::findAll();

            // Fetch potential supervisors (active users with 'Supervisor' role)
            // For more efficiency, consider adding User::findAllByRoleId(3) to your User model
            $allUsers = User::findAll();
            $supervisors = [];
            foreach ($allUsers as $potentialSupervisor) {
                if ($potentialSupervisor->role_id === 3 && $potentialSupervisor->is_active) {
                    $supervisors[] = $potentialSupervisor;
                }
            }

            View::render('admin/users/create', [
                'pageTitle' => 'Create New User',
                'roles' => $roles,
                'supervisors' => $supervisors, // Pass supervisors to the view
                'user_data' => $_SESSION['form_data'] ?? [],
                'errors' => $_SESSION['form_errors'] ?? []
            ], 'app');
            unset($_SESSION['form_errors']);
            unset($_SESSION['form_data']);
        } catch (Exception $e) {
            error_log("Error in UserController::create: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load user creation form.'];
            header('Location: /admin/users');
            exit;
        }
    }

    /**
     * Store a newly created user in the database.
     */
    public function store(): void
    {
        $this->enforceAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/users/create');
            exit;
        }

        $errors = [];
        $formData = $_POST;

        // Required fields validation
        $requiredFields = ['first_name', 'last_name', 'email', 'password', 'confirm_password', 'role_id'];
        foreach ($requiredFields as $field) {
            if (empty($formData[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        if (!empty($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format.';
        }

        if (!empty($formData['password']) && $formData['password'] !== $formData['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (!empty($formData['email']) && !isset($errors['email']) && User::emailExists(trim($formData['email']))) {
            $errors['email'] = 'This email address is already registered.';
        }

        // Validate supervisor_id if provided
        $supervisorId = !empty($formData['supervisor_id']) ? (int)$formData['supervisor_id'] : null;
        if ($supervisorId !== null) {
            $supervisorUser = User::findById($supervisorId);
            if (!$supervisorUser || $supervisorUser->role_id !== 3) { // Assuming role 3 is Supervisor
                $errors['supervisor_id'] = 'Invalid supervisor selected.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $formData;
            header('Location: /admin/users/create');
            exit;
        }

        try {
            $user = new User();
            $user->first_name = trim($formData['first_name']);
            $user->last_name = trim($formData['last_name']);
            $user->email = trim($formData['email']);
            $user->password_hash = password_hash($formData['password'], PASSWORD_DEFAULT);
            $user->role_id = (int)$formData['role_id'];
            $user->is_active = isset($formData['is_active']) ? 1 : 0;
            $user->pay_rate = !empty($formData['pay_rate']) ? (float)$formData['pay_rate'] : null;
            $user->supervisor_id = $supervisorId; // Assign validated supervisor_id

            if ($user->save()) { // Assumes User model has a working save() method
                unset($_SESSION['form_data']);
                unset($_SESSION['form_errors']);
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'User created successfully!'];
                header('Location: /admin/users');
            } else {
                $_SESSION['form_data'] = $formData;
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to create user. Please try again.'];
                header('Location: /admin/users/create');
            }
        } catch (Exception $e) {
            error_log("Error in UserController::store: " . $e->getMessage());
            $_SESSION['form_data'] = $formData;
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred while creating the user: ' . $e->getMessage()];
            header('Location: /admin/users/create');
        }
        exit;
    }

    /**
     * Display the form to edit an existing user.
     * @param string $id The ID of the user to edit
     */
    public function edit(string $id): void
    {
        $this->enforceAdmin();
        $userId = (int)$id;

        try {
            $userToEdit = User::findById($userId); // Changed variable name
            if (!$userToEdit) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'User not found.'];
                header('Location: /admin/users');
                exit;
            }

            $roles = Role::findAll();

            // Fetch potential supervisors
            $allUsers = User::findAll();
            $supervisors = [];
            foreach ($allUsers as $potentialSupervisor) {
                if ($potentialSupervisor->id !== $userId && // Cannot be their own supervisor
                    $potentialSupervisor->role_id === 3 &&   // Must have Supervisor role (ID 3)
                    $potentialSupervisor->is_active) {       // Must be an active user
                    $supervisors[] = $potentialSupervisor;
                }
            }

            // Use $userToEdit for pre-filling, not $form_data unless there was a previous error on THIS edit attempt
            $formDataForView = $_SESSION['form_data'] ?? (array)$userToEdit;
            // Ensure properties are mapped correctly if (array)$userToEdit is used
            // For example, if $userToEdit->getFullName() is a method, it won't be in (array)$userToEdit
            // It's safer to pass the object and access properties/methods in the view
            // However, for sticky forms, $_SESSION['form_data'] is usually just $_POST
            // Let's refine $form_data to be more consistent for the view.
            // If $_SESSION['form_data'] exists, it means we are returning from a validation error on THIS form.
            // Otherwise, populate from $userToEdit.
            $currentFormData = $_SESSION['form_data'] ?? [
                'first_name' => $userToEdit->first_name,
                'last_name' => $userToEdit->last_name,
                'email' => $userToEdit->email,
                'role_id' => $userToEdit->role_id,
                'supervisor_id' => $userToEdit->supervisor_id,
                'pay_rate' => $userToEdit->pay_rate,
                'is_active' => $userToEdit->is_active,
                'phone_number' => $userToEdit->phone_number,
                'address' => $userToEdit->address
                // Do not include password_hash here
            ];


            View::render('admin/users/edit', [
                'pageTitle' => 'Edit User: ' . htmlspecialchars($userToEdit->getFullName()),
                'userToEdit' => $userToEdit,       // Pass the original user object
                'roles' => $roles,
                'supervisors' => $supervisors, // Pass supervisors to the view
                'form_data' => $currentFormData, // For sticky form fields
                'errors' => $_SESSION['form_errors'] ?? []
            ], 'app');
            unset($_SESSION['form_errors']);
            unset($_SESSION['form_data']);

        } catch (Exception $e) {
            error_log("Error in UserController::edit for user ID {$userId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load user edit form. Please try again.'];
            header('Location: /admin/users');
            exit;
        }
    }

    /**
     * Update an existing user's details in the database.
     * @param string $id The ID of the user to update
     */
    public function update(string $id): void
    {
        $this->enforceAdmin();
        $userId = (int)$id;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/users/edit/' . $userId);
            exit;
        }

        $userToUpdate = User::findById($userId); // Changed variable name
        if (!$userToUpdate) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'User not found for update.'];
            header('Location: /admin/users');
            exit;
        }

        $errors = [];
        $formData = $_POST;

        // Required fields validation
        $requiredFields = ['first_name', 'last_name', 'email', 'role_id'];
        foreach ($requiredFields as $field) {
            if (empty($formData[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        if (!empty($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format.';
        }

        // Check if email already exists for another user
        if (!empty($formData['email']) && strtolower(trim($formData['email'])) !== strtolower($userToUpdate->email) && User::emailExists(trim($formData['email']), $userId)) {
            $errors['email'] = 'This email address is already registered by another user.';
        }

        // Password validation (only if new password is provided)
        if (!empty($formData['password'])) {
            if ($formData['password'] !== $formData['confirm_password']) {
                $errors['confirm_password'] = 'New passwords do not match.';
            }
            // Add password strength validation here if desired
        }

        // Validate supervisor_id if provided
        $supervisorId = !empty($formData['supervisor_id']) ? (int)$formData['supervisor_id'] : null;
        if ($supervisorId !== null) {
            if ($supervisorId === $userId) { // User cannot be their own supervisor
                $errors['supervisor_id'] = 'User cannot be their own supervisor.';
            } else {
                $supervisorUser = User::findById($supervisorId);
                if (!$supervisorUser || $supervisorUser->role_id !== 3) { // Assuming role 3 is Supervisor
                    $errors['supervisor_id'] = 'Invalid supervisor selected.';
                }
            }
        }


        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $formData;
            header('Location: /admin/users/edit/' . $userId);
            exit;
        }

        // Update user object properties
        $userToUpdate->first_name = trim($formData['first_name']);
        $userToUpdate->last_name = trim($formData['last_name']);
        $userToUpdate->email = trim($formData['email']);
        $userToUpdate->role_id = (int)$formData['role_id'];
        $userToUpdate->is_active = isset($formData['is_active']) ? 1 : 0;
        $userToUpdate->phone_number = !empty($formData['phone_number']) ? trim($formData['phone_number']) : null;
        $userToUpdate->address = !empty($formData['address']) ? trim($formData['address']) : null;
        $userToUpdate->pay_rate = !empty($formData['pay_rate']) ? (float)$formData['pay_rate'] : null;
        $userToUpdate->supervisor_id = $supervisorId; // Assign validated supervisor_id

        // Handle password update
        if (!empty($formData['password'])) {
            $userToUpdate->password_hash = password_hash($formData['password'], PASSWORD_DEFAULT);
        }
        // If password field is empty, $userToUpdate->password_hash retains its original value

        try {
            if ($userToUpdate->save()) { // Assumes User model has a working save() method
                unset($_SESSION['form_data']);
                unset($_SESSION['form_errors']);
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'User updated successfully!'];
                header('Location: /admin/users');
            } else {
                $_SESSION['form_data'] = $formData;
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to update user. Please try again.'];
                header('Location: /admin/users/edit/' . $userId);
            }
        } catch (Exception $e) {
            error_log("Error in UserController::update for user ID {$userId}: " . $e->getMessage());
            $_SESSION['form_data'] = $formData;
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred while updating the user: ' . $e->getMessage()];
            header('Location: /admin/users/edit/' . $userId);
        }
        exit;
    }

    public function delete(): void
    {
        $this->enforceAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id'])) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Invalid request for deletion.'];
            header('Location: /admin/users');
            exit;
        }

        $userId = (int)$_POST['user_id'];

        try {
            // Add a check: Cannot delete own account (logged in admin)
            if ($userId === ($_SESSION['user_id'] ?? null)) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'You cannot delete your own account.'];
            } elseif (User::softDelete($userId)) { // Assumes User model has softDelete
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'User soft deleted successfully.'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to delete user or user already deleted.'];
            }
        } catch (Exception $e) {
            error_log("Error in UserController::delete for user ID {$userId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred while deleting the user.'];
        }

        header('Location: /admin/users');
        exit;
    }

    // The save() method should be in your User.php MODEL, not here in the CONTROLLER.
    // public function save(): bool { ... } // <--- REMOVE THIS FROM CONTROLLER
}