<?php

// sno/public/index.php
session_start(); // START SESSIONS

// 1. Set up error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1); // Corrected from duplicate display_errors
error_reporting(E_ALL);

// 2. Require Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// 3. Load environment variables from .env file
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    die("Dotenv: Could not find .env file path: " . $e->getMessage() . " Please ensure it exists in the project root (" . realpath(__DIR__ . '/../') . ").");
} catch (\Dotenv\Exception\InvalidFileException $e) {
    die("Dotenv: Could not read the .env file: " . $e->getMessage());
} catch (\Exception $e) {
    die("Dotenv: An unexpected error occurred during loading: " . $e->getMessage());
}

// 4. Use statements for classes
use App\Core\Http\Router;
use App\UserManagement\Controller\AuthController; // This line is correct
use App\Core\View;
use App\Admin\Controller\UserController as AdminUserController;
// 5. Instantiate the Router
$router = new Router();
$router->get('/admin/users', [AdminUserController::class, 'index']);
// 6. Define Routes
$router->get('/', function() {
    // Check if user is logged in to show different links
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        echo "Welcome to the ShineO Application, " . htmlspecialchars($_SESSION['user_name']) . "!<br>";
        echo "<a href='/dashboard'>Go to Dashboard</a> | <a href='/logout'>Logout</a>";
    } else {
        echo "Welcome to the ShineO Application! Environment variables are loaded.<br>";
        echo "<a href='/login'>Login</a>";
        // You can add the test-db-user link back here if you still need it for debugging:
        // echo " or <a href='/test-db-user'>Test DB User Fetch</a>";
    }
});

// Dashboard route - THIS IS THE CRUCIAL ADDITION
$router->get('/dashboard', function() {
    // Protect this page
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $_SESSION['login_error'] = 'You need to login to access the dashboard.';
        header('Location: /login'); // Make sure /login route is defined
        exit;
    }

    // If logged in, show the dashboard content
    echo "<h1>Welcome to your Dashboard, " . htmlspecialchars($_SESSION['user_name']) . "!</h1>";
    echo "<p>Your User ID is: " . htmlspecialchars($_SESSION['user_id']) . "</p>";
    echo "<p>Your Role ID is: " . htmlspecialchars($_SESSION['user_role_id']) . "</p>";
    echo "<p><a href='/logout'>Logout</a></p>"; // Make sure /logout route is defined
});

// Routes for AuthController
$router->get('/login', [AuthController::class, 'showLoginForm']);
$router->post('/login-process', [AuthController::class, 'handleLoginAttempt']);
$router->get('/test-db-user', [AuthController::class, 'testDatabaseUserFetch']);
$router->get('/logout', [AuthController::class, 'logout']);

// 7. Dispatch the request
$requestUri = $_SERVER['REQUEST_URI'];
// Basic sanitization: remove query string from URI for routing
if (false !== $pos = strpos($requestUri, '?')) {
    $requestUri = substr($requestUri, 0, $pos);
}
$requestUri = rawurldecode($requestUri);

// Trim trailing slash if not the root path
if (strlen($requestUri) > 1 && substr($requestUri, -1) === '/') {
    $requestUri = rtrim($requestUri, '/');
}
// If after trimming, the URI became empty (e.g., it was originally just '/'), set it back to '/'
if (empty($requestUri)) {
    $requestUri = '/';
}
// Ensure it starts with a slash if it's not empty
if (!empty($requestUri) && $requestUri[0] !== '/') {
    $requestUri = '/' . $requestUri;
}

$requestMethod = $_SERVER['REQUEST_METHOD'];

try {
    $router->dispatch($requestMethod, $requestUri);
} catch (\Exception $e) {
    // Basic error handling
    $errorMessage = $e->getMessage();
    if (strpos($errorMessage, "No route found for") === 0) { // Check if it's a "No route found" error
        http_response_code(404);
    } else {
        http_response_code(500);
    }
    echo "Error: " . htmlspecialchars($errorMessage) . "<br>";
    // For detailed debugging during development (remove or make conditional for production)
    echo "Trace: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}