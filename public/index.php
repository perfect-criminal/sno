<?php

// sno/public/index.php
session_start(); // START SESSIONS

// 1. Set up error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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
use App\UserManagement\Controller\AuthController;
use App\Core\View; // Make sure this is here
use App\Admin\Controller\UserController as AdminUserController;
use App\Admin\Controller\CompanyController as AdminCompanyController;
use App\Admin\Controller\SiteController as AdminSiteController;
use App\Staff\Controller\DashboardController as StaffDashboardController;
use App\Staff\Controller\TimesheetController as StaffTimesheetController;
use App\Supervisor\Controller\DashboardController as SupervisorDashboardController;
use App\Supervisor\Controller\TimesheetController as SupervisorTimesheetController;
use App\Supervisor\Controller\PaysheetController as SupervisorPaysheetController;
use App\Payroll\Controller\DashboardController as PayrollDashboardController;
use App\Payroll\Controller\PaysheetController as PayrollPaysheetController;


// 5. Instantiate the Router
$router = new Router();

// 6. Define Routes
$router->get('/', function() {
    // Data for the home view
    $data = ['pageTitle' => 'Welcome to ShineO'];
    // If you need to pass session info to the view specifically:
    // if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    //     $data['userName'] = $_SESSION['user_name'];
    // }
    // The layout already handles conditional display based on session for nav
    View::render('home/index', $data, 'app'); // Use View::render for the home page
});

// Dashboard route - MODIFIED TO USE View::render()
$router->get('/dashboard', function() {
    // Protect this page
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        // Use flash message for consistency
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'You need to login to access the dashboard.'];
        header('Location: /login');
        exit;
    }

    // If logged in, render the dashboard view
    View::render('dashboard/index', [
        'pageTitle' => 'Dashboard',
        'userName' => $_SESSION['user_name'],
        'userId' => $_SESSION['user_id'],
        'userRoleId' => $_SESSION['user_role_id']
    ], 'app'); // 'app' is the layout file (templates/layouts/app.php)
});
//ROUTES DEFINED FOR ALL FUNCTIONALITIES OF THE PROGRAM
// Admin Routes
$router->get('/admin/users', [AdminUserController::class, 'index']);

// Routes for AuthController
$router->get('/login', [AuthController::class, 'showLoginForm']);
$router->post('/login-process', [AuthController::class, 'handleLoginAttempt']);
$router->get('/test-db-user', [AuthController::class, 'testDatabaseUserFetch']);
$router->get('/logout', [AuthController::class, 'logout']);
// Admin Users Routes
$router->get('/admin/users', [AdminUserController::class, 'index']);
$router->get('/admin/users/create', [AdminUserController::class, 'create']);
$router->post('/admin/users/store', [AdminUserController::class, 'store']);
$router->get('/admin/users/edit/{id}', [AdminUserController::class, 'edit']);
$router->post('/admin/users/update/{id}', [AdminUserController::class, 'update']);
$router->post('/admin/users/delete', [AdminUserController::class, 'delete']);
// Admin Company Routes
$router->get('/admin/companies', [AdminCompanyController::class, 'index']);
$router->get('/admin/companies/create', [AdminCompanyController::class, 'create']);
$router->post('/admin/companies/store', [AdminCompanyController::class, 'store']);
$router->get('/admin/companies/edit/{id}', [AdminCompanyController::class, 'edit']);
$router->post('/admin/companies/update/{id}', [AdminCompanyController::class, 'update']);
$router->post('/admin/companies/delete', [AdminCompanyController::class, 'delete']);
// Admin Site Routes
$router->get('/admin/sites', [AdminSiteController::class, 'index']);
$router->get('/admin/sites/create', [AdminSiteController::class, 'create']);
$router->post('/admin/sites/store', [AdminSiteController::class, 'store']);
$router->get('/admin/sites/edit/{id}', [AdminSiteController::class, 'edit']);
$router->post('/admin/sites/update/{id}', [AdminSiteController::class, 'update']);
$router->post('/admin/sites/delete', [AdminSiteController::class, 'delete']);
// Staff Routes
$router->get('/staff/dashboard', [StaffDashboardController::class, 'index']);
$router->get('/staff/timesheets/create', [StaffTimesheetController::class, 'create']);
$router->post('/staff/timesheets/store', [StaffTimesheetController::class, 'store']);
$router->get('/staff/timesheets', [StaffTimesheetController::class, 'index']);
$router->get('/staff/timesheets/review/{id}', [StaffTimesheetController::class, 'review']);
$router->post('/staff/timesheets/agree/{id}', [StaffTimesheetController::class, 'agreeToEdit']);
$router->post('/staff/timesheets/disagree/{id}', [StaffTimesheetController::class, 'disagreeWithEdit']);
$router->get('/staff/timesheets/edit/{id}', [StaffTimesheetController::class, 'edit']);
$router->post('/staff/timesheets/resubmit/{id}', [StaffTimesheetController::class, 'resubmit']);
// Supervisor Routes
$router->get('/supervisor/dashboard', [SupervisorDashboardController::class, 'index']);
$router->get('/supervisor/timesheets/pending', [SupervisorTimesheetController::class, 'pending']);
$router->get('/supervisor/timesheets/edit/{id}', [SupervisorTimesheetController::class, 'edit']);
$router->post('/supervisor/timesheets/update/{id}', [SupervisorTimesheetController::class, 'update']);
$router->get('/supervisor/timesheets/disputed', [SupervisorTimesheetController::class, 'disputedList']);
$router->post('/supervisor/timesheets/approve', [SupervisorTimesheetController::class, 'approve']);
$router->get('/supervisor/timesheets/reject-form/{id}', [SupervisorTimesheetController::class, 'showRejectForm']);
$router->post('/supervisor/timesheets/reject-action/{id}', [SupervisorTimesheetController::class, 'processReject']);

// ... (supervisor timesheet routes) ...
$router->get('/supervisor/paysheets/create', [SupervisorPaysheetController::class, 'create']);     // <-- Show form
$router->post('/supervisor/paysheets/generate', [SupervisorPaysheetController::class, 'generate']); // <-- Process form
$router->get('/supervisor/paysheets', [SupervisorPaysheetController::class, 'index']); // <- view paysheets
$router->get('/supervisor/paysheets/view/{id}', [SupervisorPaysheetController::class, 'view']);
$router->get('/supervisor/team-timesheets', [SupervisorTimesheetController::class, 'listTeamTimesheets']);
// Payroll Routes
$router->get('/payroll/dashboard', [PayrollDashboardController::class, 'index']);
$router->get('/payroll/paysheets/pending-review', [PayrollPaysheetController::class, 'listPendingReview']);
$router->get('/payroll/paysheets/review/{id}', [PayrollPaysheetController::class, 'reviewDetails']);
$router->post('/payroll/paysheets/approve/{id}', [PayrollPaysheetController::class, 'approveForRun']);
$router->post('/payroll/paysheets/mark-review/{id}', [PayrollPaysheetController::class, 'markForSupervisorReview']);
$router->get('/supervisor/paysheets/under-review', [SupervisorPaysheetController::class, 'listUnderReview']);
$router->post('/supervisor/paysheets/cancel-review/{id}', [SupervisorPaysheetController::class, 'cancelReviewedPaysheet']);
$router->post('/supervisor/paysheets/acknowledge-review/{id}', [SupervisorPaysheetController::class, 'acknowledgeReview']);
$router->post('/supervisor/paysheets/resubmit-reviewed/{id}', [SupervisorPaysheetController::class, 'resubmitReviewedPaysheet']);
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
    // Check if it's a "No route found" error (adjust for PHP version if needed)
    if (function_exists('str_starts_with') && str_starts_with($errorMessage, "No route found for")) {
        http_response_code(404);
    } elseif (strpos($errorMessage, "No route found for") === 0) { // Fallback for PHP < 8
        http_response_code(404);
    } else {
        http_response_code(500);
    }
    echo "Error: " . htmlspecialchars($errorMessage) . "<br>";
    // For detailed debugging during development (remove or make conditional for production)
    echo "Trace: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}