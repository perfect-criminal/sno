<?php

namespace App\Admin\Controller;

use App\Core\View;
use App\UserManagement\Model\User; // Assuming User model is in UserManagement
use Exception;

class UserController
{
    private function isAdmin(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] === 1; // Assuming 1 is Admin role ID
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
        $this->enforceAdmin(); // Protect the method

        try {
            $users = User::findAll(); // Fetch all users
            View::render('admin/users/index', [
                'pageTitle' => 'Manage Users',
                'users' => $users
            ], 'app');
        } catch (Exception $e) {
            // Log error and show a generic error page or message
            error_log("Error in UserController::index: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load user data. Please try again later.'];
            // Potentially render an error view or redirect
            View::render('error/generic', ['pageTitle' => 'Error', 'errorMessage' => $_SESSION['flash_message']['message']], 'app');
        }
    }

    // We will add create(), store(), edit(), update(), delete() methods later
}