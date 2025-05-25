<?php

namespace App\Admin\Controller;

use App\Core\View;
use App\UserManagement\Model\User;
use App\UserManagement\Model\Role; // <-- ADD THIS to use the Role model
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
            $users = User::findAll();
            View::render('admin/users/index', [
                'pageTitle' => 'Manage Users',
                'users' => $users
            ], 'app');
        } catch (Exception $e) {
            error_log("Error in UserController::index: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load user data.'];
            View::render('error/generic', ['pageTitle' => 'Error', 'errorMessage' => $_SESSION['flash_message']['message']], 'app');
        }
    }

    /**
     * Display the form to create a new user.
     */
    public function create(): void
    {
        $this->enforceAdmin();
        try {
            $roles = Role::findAll(); // Fetch all roles for the dropdown
            View::render('admin/users/create', [
                'pageTitle' => 'Create New User',
                'roles' => $roles,
                'user_data' => [], // For pre-filling form on error, empty for create
                'errors' => $_SESSION['form_errors'] ?? []
            ], 'app');
            unset($_SESSION['form_errors']); // Clear errors after displaying
        } catch (Exception $e) {
            error_log("Error in UserController::create: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load user creation form.'];
            header('Location: /admin/users'); // Redirect to user list on error
            exit;
        }
    }

    /**
     * Store a newly created user in the database.
     */
    public function store(): void
    {
        // --- BEGIN DEBUGGING ---
        echo "<pre>UserController::store() method reached.\n";
        echo "POST data received:\n";
        var_dump($_POST);
        echo "Session form_errors before processing:\n";
        var_dump($_SESSION['form_errors'] ?? null);
        echo "Session form_data before processing:\n";
        var_dump($_SESSION['form_data'] ?? null);
        echo "</pre><hr>";
        // --- END DEBUGGING ---

        $this->enforceAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // This should ideally not be reached if routing is correct for POST only
            echo "DEBUG: store() called with non-POST method. Redirecting to create form.<br>";
            header('Location: /admin/users/create');
            exit;
        }

        // --- Basic Validation (expand this later) ---
        $errors = [];
        $formData = $_POST; // Keep a copy of POST data for potential pre-filling

        $requiredFields = ['first_name', 'last_name', 'email', 'password', 'confirm_password', 'role_id'];
        foreach ($requiredFields as $field) {
            if (empty($formData[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        // ... (rest of your validation logic: email format, password match, emailExists) ...
        // As provided in the previous step

        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format.';
        }

        if (!empty($_POST['password']) && $_POST['password'] !== $_POST['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (!empty($_POST['email']) && !isset($errors['email']) && User::emailExists($_POST['email'])) {
            $errors['email'] = 'This email address is already registered.';
        }


        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $formData; // Use the copy we made
            echo "DEBUG: Validation errors found. Redirecting back to create form.<br>";
            echo "<pre>Errors:\n"; var_dump($errors); echo "</pre>";
            // header('Location: /admin/users/create'); // Temporarily comment out redirect to see debug
            // exit;
            // For now, let's allow the script to continue to see if it reaches the view part if create was called next
            // But actually, it should redirect. To see errors on the form, the create() method must handle $_SESSION['form_errors']
            // The redirect is correct. The create() method should display errors from session.
            header('Location: /admin/users/create');
            exit;
        }
        // --- End Basic Validation ---

        // If validation passes:
        echo "DEBUG: Validation passed. Attempting to save user.<br>";

        try {
            $user = new User();
            $user->first_name = trim($formData['first_name']);
            $user->last_name = trim($formData['last_name']);
            $user->email = trim($formData['email']);
            $user->password_hash = password_hash($formData['password'], PASSWORD_DEFAULT);
            $user->role_id = (int)$formData['role_id'];
            $user->is_active = isset($formData['is_active']) ? 1 : 0;
            // supervisor_id and pay_rate are not in $requiredFields, handle them carefully
            $user->supervisor_id = !empty($formData['supervisor_id']) ? (int)$formData['supervisor_id'] : null;
            $user->pay_rate = !empty($formData['pay_rate']) ? (float)$formData['pay_rate'] : null;


            if ($user->save()) {
                echo "DEBUG: User save() method returned true.<br>";
                unset($_SESSION['form_data']); // Clear form data on success
                unset($_SESSION['form_errors']); // Clear any potential stale errors
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'User created successfully!'];
                header('Location: /admin/users');
                exit;
            } else {
                echo "DEBUG: User save() method returned false.<br>";
                $_SESSION['form_data'] = $formData;
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to create user (save method returned false). Please try again.'];
                header('Location: /admin/users/create');
                exit;
            }
        } catch (Exception $e) {
            error_log("Error in UserController::store: " . $e->getMessage());
            echo "DEBUG: Exception caught during save: " . $e->getMessage() . "<br>";
            $_SESSION['form_data'] = $formData;
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred while creating the user. ' . $e->getMessage()];
            header('Location: /admin/users/create');
            exit;
        }
    }

    /**
     * Update an existing user's details in the database.
     * @param string $id The ID of the user to update (from URL parameter)
     */
    public function update(string $id): void  // <--- MAKE SURE THIS METHOD EXISTS EXACTLY LIKE THIS
    {
        $this->enforceAdmin();
        $userId = (int)$id;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/users/edit/' . $userId);
            exit;
        }

        // Fetch the existing user to update
        $user = User::findById($userId);
        if (!$user) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'User not found for update.'];
            header('Location: /admin/users');
            exit;
        }

        // --- Validation ---
        $errors = [];
        $formData = $_POST;

        $requiredFields = ['first_name', 'last_name', 'email', 'role_id'];
        foreach ($requiredFields as $field) {
            if (empty($formData[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        if (!empty($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format.';
        }

        if (!empty($formData['email']) && $formData['email'] !== $user->email && User::emailExists($formData['email'], $userId)) {
            $errors['email'] = 'This email address is already registered by another user.';
        }

        if (!empty($formData['password'])) {
            if ($formData['password'] !== $formData['confirm_password']) {
                $errors['confirm_password'] = 'New passwords do not match.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $formData;
            header('Location: /admin/users/edit/' . $userId);
            exit;
        }
        // --- End Validation ---

        $user->first_name = trim($formData['first_name']);
        $user->last_name = trim($formData['last_name']);
        $user->email = trim($formData['email']);
        $user->role_id = (int)$formData['role_id'];
        $user->is_active = isset($formData['is_active']) ? 1 : 0;

        $user->supervisor_id = !empty($formData['supervisor_id']) ? (int)$formData['supervisor_id'] : null;
        $user->phone_number = !empty($formData['phone_number']) ? trim($formData['phone_number']) : null;
        $user->address = !empty($formData['address']) ? trim($formData['address']) : null;
        $user->pay_rate = !empty($formData['pay_rate']) ? (float)$formData['pay_rate'] : null;

        if (!empty($formData['password'])) {
            $user->password_hash = password_hash($formData['password'], PASSWORD_DEFAULT);
        }

        try {
            if ($user->save()) {
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
    /**
     * Handle the soft deletion of a user.
     */
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
            if (User::softDelete($userId)) {
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
    public function edit(string $id): void
    {
        $this->enforceAdmin();
        $userId = (int)$id;

        try {
            $user = User::findById($userId);
            if (!$user) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'User not found.'];
                header('Location: /admin/users');
                exit;
            }

            $roles = Role::findAll();

            // This part is likely not reached if the error message is "Could not load user edit form"
            View::render('admin/users/edit', [
                'pageTitle' => 'Edit User: ' . htmlspecialchars($user->getFullName()),
                'user' => $user,
                'roles' => $roles,
                'form_data' => (array)$user,
                'errors' => $_SESSION['form_errors'] ?? []
            ], 'app');
            unset($_SESSION['form_errors']);
            unset($_SESSION['form_data']);

        } catch (Exception $e) {
            // --- TEMPORARY DEBUGGING ---
            echo "<h1>An Exception Occurred!</h1>";
            echo "<p><strong>Message from Controller:</strong> Could not load user edit form.</p>";
            echo "<p><strong>Specific Exception Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " on line " . htmlspecialchars($e->getLine()) . "</p>";
            echo "<strong>Stack Trace:</strong><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            error_log("Error in UserController::edit for user ID {$userId}: " . $e->getMessage() . "\n" . $e->getTraceAsString()); // Enhanced logging
            // $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load user edit form. Specific: ' . $e->getMessage()]; // Include specific message in flash
            // header('Location: /admin/users'); // Comment out redirect to see the error
            exit; // Stop execution to see the error
            // --- END TEMPORARY DEBUGGING ---
        }
    }
    public function save(): bool
    {
        $db = Connection::getInstance();
        $now = date('Y-m-d H:i:s');

        if ($this->id === null) { // Creating a new user
            $this->created_at = $this->created_at ?? $now; // Ensure created_at is set
            $this->updated_at = $now; // Set updated_at for new user

            $sql = "INSERT INTO users (role_id, supervisor_id, first_name, last_name, email, password_hash, phone_number, address, pay_rate, is_active, created_at, updated_at) 
                    VALUES (:role_id, :supervisor_id, :first_name, :last_name, :email, :password_hash, :phone_number, :address, :pay_rate, :is_active, :created_at, :updated_at)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':created_at', $this->created_at, PDO::PARAM_STR);

        } else { // Updating an existing user
            $this->updated_at = $now; // Always update timestamp on save for existing record

            // Note: The controller is responsible for ensuring $this->password_hash contains
            // either the new hashed password or the original one if not changed.
            $sql = "UPDATE users SET 
                        role_id = :role_id,
                        supervisor_id = :supervisor_id,
                        first_name = :first_name,
                        last_name = :last_name,
                        email = :email,
                        password_hash = :password_hash, 
                        phone_number = :phone_number,
                        address = :address,
                        pay_rate = :pay_rate,
                        is_active = :is_active,
                        updated_at = :updated_at
                    WHERE id = :id AND deleted_at IS NULL";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        }

        // Bind common parameters
        $isActiveInt = (int)$this->is_active;

        $stmt->bindParam(':role_id', $this->role_id, PDO::PARAM_INT);
        $stmt->bindParam(':first_name', $this->first_name, PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $this->last_name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $this->email, PDO::PARAM_STR);
        $stmt->bindParam(':password_hash', $this->password_hash, PDO::PARAM_STR); // Bind whatever is in the property
        $stmt->bindParam(':is_active', $isActiveInt, PDO::PARAM_INT);
        $stmt->bindParam(':updated_at', $this->updated_at, PDO::PARAM_STR);

        // Nullable fields - using bindValue for explicit NULL handling
        $stmt->bindValue(':supervisor_id', $this->supervisor_id, $this->supervisor_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':phone_number', $this->phone_number, $this->phone_number === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':address', $this->address, $this->address === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':pay_rate', $this->pay_rate, $this->pay_rate === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

        $success = $stmt->execute();
        if ($success && $this->id === null) { // If it was an INSERT
            $this->id = (int)$db->lastInsertId();
        }
        return $success;
    }
}