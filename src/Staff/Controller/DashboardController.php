<?php

namespace App\Staff\Controller;

use App\Core\View;
use App\UserManagement\Model\Site;
use App\UserManagement\Model\Timesheet;
use Exception;

class DashboardController
{
    /**
     * Checks if the current logged-in user is a staff member.
     * Assumes Role ID 2 is 'Staff'.
     *
     * @return bool True if staff, false otherwise.
     */
    private function isStaff(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
            isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] === 2; // Role ID 2 for Staff
    }

    /**
     * Enforces staff access. If the user is not staff, it sets a flash message
     * and redirects them appropriately.
     */
    private function enforceStaffAccess(): void
    {
        if (!$this->isStaff()) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Access Denied. Staff access required.'];

            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                // If logged in but not staff, redirect to a generic dashboard or home
                header('Location: /'); // Or /dashboard if you have a generic one
            } else {
                // If not logged in at all, redirect to login
                header('Location: /login');
            }
            exit;
        }
    }

    /**
     * Displays the staff dashboard, including assigned sites.
     */
    public function index(): void
    {
        $this->enforceStaffAccess();

        $staffId = $_SESSION['user_id'] ?? 0;
        $assignedSites = [];
        $timesheetsPendingConfirmation = []; // For timesheets edited by supervisor
        $dashboardErrorMessage = null; // General error message for the dashboard

        if ($staffId > 0) {
            try {
                $assignedSites = Site::findAssignedToStaff($staffId);
                $timesheetsPendingConfirmation = Timesheet::findPendingConfirmationByStaff($staffId); // <-- FETCH THESE
            } catch (Exception $e) {
                error_log("Error fetching data for staff dashboard (ID {$staffId}): " . $e->getMessage());
                $dashboardErrorMessage = "Could not load all dashboard data at this time.";
            }
        } else {
            error_log("Staff dashboard: user_id not found in session.");
            $dashboardErrorMessage = "Could not retrieve your user information.";
        }

        $data = [
            'pageTitle' => 'Staff Dashboard',
            'userName' => $_SESSION['user_name'] ?? 'Staff Member',
            'assignedSites' => $assignedSites,
            'timesheetsPendingConfirmation' => $timesheetsPendingConfirmation, // <-- PASS TO VIEW
            'dashboardErrorMessage' => $dashboardErrorMessage
        ];
        View::render('staff/dashboard/index', $data, 'app');
    }
}