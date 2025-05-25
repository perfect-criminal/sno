<?php

session_start(); // ENSURE THIS IS THE VERY FIRST LINE

// ... (error reporting, autoloader, Dotenv loading) ...

use App\Core\Http\Router;
use App\UserManagement\Controller\AuthController; // Ensure this is uncommented

$router = new Router();

$router->get('/', function() {
    // Check if user is logged in to show different links
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        echo "Welcome to the ShineO Application, " . htmlspecialchars($_SESSION['user_name']) . "!<br>";
        echo "<a href='/dashboard'>Go to Dashboard</a> | <a href='/logout'>Logout</a>";
    } else {
        echo "Welcome to the ShineO Application! Environment variables are loaded.<br>";
        echo "<a href='/login'>Login</a>";
    }
});

// Dashboard route
$router->get('/dashboard', function() {
    // Protect this page
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $_SESSION['login_error'] = 'You need to login to access the dashboard.';
        header('Location: /login');
        exit;
    }

    // If logged in, show the dashboard content
    echo "<h1>Welcome to your Dashboard, " . htmlspecialchars($_SESSION['user_name']) . "!</h1>";
    echo "<p>Your User ID is: " . htmlspecialchars($_SESSION['user_id']) . "</p>";
    echo "<p>Your Role ID is: " . htmlspecialchars($_SESSION['user_role_id']) . "</p>";
    echo "<p><a href='/logout'>Logout</a></p>";
});

// Routes for AuthController
$router->get('/login', [AuthController::class, 'showLoginForm']);
$router->post('/login-process', [AuthController::class, 'handleLoginAttempt']);
$router->get('/test-db-user', [AuthController::class, 'testDatabaseUserFetch']); // Keep for debugging if needed

// ... (dispatch logic) ...