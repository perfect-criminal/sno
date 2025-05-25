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
        $this->enforceAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/users/create');
            exit;
        }

        // --- Basic Validation (expand this later) ---
        $errors = [];
        $requiredFields = ['first_name', 'last_name', 'email', 'password', 'confirm_password', 'role_id'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format.';
        }

        if (!empty($_POST['password']) && $_POST['password'] !== $_POST['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        // Check if email already exists
        if (!empty($_POST['email']) && User::emailExists($_POST['email'])) {
            $errors['email'] = 'This email address is already registered.';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST; // Preserve submitted data
            header('Location: /admin/users/create');
            exit;
        }
        // --- End Basic Validation ---

        try {
            $user = new User();
            $user->first_name = trim($_POST['first_name']);
            $user->last_name = trim($_POST['last_name']);
            $user->email = trim($_POST['email']);
            $user->password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
            $user->role_id = (int)$_POST['role_id'];
            $user->is_active = isset($_POST['is_active']) ? 1 : 0;
            // created_at and updated_at are usually handled by DB default or model's save method

            if ($user->save()) { // We need to implement/update save() in User model
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'User created successfully!'];
                header('Location: /admin/users');
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to create user. Please try again.'];
                header('Location: /admin/users/create');
            }
        } catch (Exception $e) {
            error_log("Error in UserController::store: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred while creating the user. ' . $e->getMessage()];
            // Preserve form data on general error too
            $_SESSION['form_data'] = $_POST;
            header('Location: /admin/users/create');
        }
        exit;
    }
}