<?php

namespace App\Supervisor\Controller;

use App\Core\View;
use App\UserManagement\Model\User; // To fetch assigned staff later
use Exception;

class DashboardController
{
    private function isSupervisor(): bool
    {
        // Assuming Role ID 3 is 'Supervisor'
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
            isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] === 3;
    }

    private function enforceSupervisorAccess(): void
    {
        if (!$this->isSupervisor()) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Access Denied. Supervisor access required.'];
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                header('Location: /'); // Or a generic dashboard
            } else {
                header('Location: /login');
            }
            exit;
        }
    }

    public function index(): void
    {
        $this->enforceSupervisorAccess();

        $supervisorId = $_SESSION['user_id'] ?? 0;
        $assignedStaff = [];
        $staffErrorMessage = null;

        if ($supervisorId > 0) {
            try {
                $assignedStaff = User::findStaffBySupervisorId($supervisorId);
            } catch (Exception $e) {
                error_log("Error fetching assigned staff for supervisor ID {$supervisorId}: " . $e->getMessage());
                $staffErrorMessage = "Could not load your assigned staff at this time.";
            }
        } else {
            // Should ideally not happen if enforceSupervisorAccess and session are working
            error_log("Supervisor dashboard: supervisor_id (user_id) not found in session.");
            $staffErrorMessage = "Your user information could not be retrieved.";
        }

        $data = [
            'pageTitle' => 'Supervisor Dashboard',
            'userName' => $_SESSION['user_name'] ?? 'Supervisor',
            'assignedStaff' => $assignedStaff,
            'staffErrorMessage' => $staffErrorMessage
        ];
        View::render('supervisor/dashboard/index', $data, 'app');
    }
}