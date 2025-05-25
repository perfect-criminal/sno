<?php

namespace App\UserManagement\Controller;

use App\UserManagement\Model\User; // Uses the User model
use App\Core\View;
use Exception; // Use global Exception

class AuthController
{
    public function showLoginForm(): void
    {
        $actionUrl = '/login-process'; // Ensure this route exists for POST

        $errorMessage = '';
        if (isset($_SESSION['login_error'])) {
            $errorMessage = "<p class='error-message'>" . htmlspecialchars($_SESSION['login_error']) . "</p>";
            unset($_SESSION['login_error']); // Clear the error message after displaying it
        }

        // Basic login form. Later, this would be a separate template file.
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Login - ShineO</title>
            <style>
                body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 80vh; background-color: #f4f4f4; }
                .login-container { background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); min-width: 300px; }
                .form-group { margin-bottom: 15px; }
                label { display: block; margin-bottom: 5px; }
                input[type="email"], input[type="password"] { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 3px; }
                input[type="submit"] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 3px; cursor: pointer; width:100%; }
                input[type="submit"]:hover { background-color: #0056b3; }
                .error-message { color: red; background-color: #ffebee; border: 1px solid #ef9a9a; padding: 10px; border-radius: 3px; margin-bottom: 15px; text-align: center;}
                hr { margin-top: 20px; margin-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h2>ShineO Login</h2>
                {$errorMessage}
                <form method="POST" action="{$actionUrl}">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="admin@example.com" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <input type="submit" value="Login">
                    </div>
                </form>
                </div>
        </body>
        </html>
HTML;
    }

    public function handleLoginAttempt(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "Invalid request method.";
            exit;
        }

        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;

        if (empty($email) || empty($password)) {
            // In a real app, set a flash message to display on the login page
            $_SESSION['login_error'] = 'Email and Password are required.';
            header('Location: /login'); // Or your actual login route
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['login_error'] = 'Invalid email format.';
            header('Location: /login');
            exit;
        }

        try {
            $user = User::findByEmail($email);

            if ($user && $user->is_active) {
                if (password_verify($password, $user->password_hash)) {
                    // Password is correct! Start the session.
                    // Regenerate session ID for security (prevents session fixation)
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user->id;
                    $_SESSION['user_role_id'] = $user->role_id;
                    $_SESSION['user_name'] = $user->getFullName();
                    $_SESSION['logged_in'] = true;
                    unset($_SESSION['attempted_email']);
                    unset($_SESSION['flash_message']);

                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Login successful! Welcome back.'];

// Role-based redirect
                    if ($user->role_id === 1) { // Admin
                        header('Location: /admin/users'); // Or /admin/dashboard if you create one
                    } elseif ($user->role_id === 2) { // Staff
                        header('Location: /staff/dashboard');
                    } elseif ($user->role_id === 3) { // Supervisor
                        header('Location: /supervisor/dashboard'); // Placeholder for now
                    } else {
                        header('Location: /dashboard'); // Generic fallback or for other roles
                    }
                    exit;
                }
            } else {
                $_SESSION['login_error'] = 'Login failed: User not found or not active.';
                header('Location: /login');
                exit;
            }
        } catch (Exception $e) {
            error_log("Login attempt error: " . $e->getMessage());
            $_SESSION['login_error'] = 'An error occurred. Please try again later.';
            header('Location: /login');
            exit;
        }
    }

    // A simple test method to fetch a user directly
    public function testDatabaseUserFetch(): void
    {
        $testEmail = "admin@example.com"; // Make sure this user exists in your DB
        echo "Attempting to fetch user: " . htmlspecialchars($testEmail) . "<br>";
        try {
            $user = User::findByEmail($testEmail);
            if ($user) {
                echo "SUCCESS! User found: " . htmlspecialchars($user->getFullName()) . "<br>";
                echo "Email: " . htmlspecialchars($user->email) . "<br>";
                echo "Role ID: " . htmlspecialchars($user->role_id) . "<br>";
            } else {
                echo "FAILURE! User '" . htmlspecialchars($testEmail) . "' not found in the database (or is soft-deleted).<br>";
            }
        } catch (Exception $e) {
            echo "DATABASE ERROR during test fetch: " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
    }
    public function logout(): void
    {
        // Unset all of the session variables.
        $_SESSION = array();

        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Finally, destroy the session.
        session_destroy();

        // Redirect to login page
        header('Location: /login');
        exit;
    }
}