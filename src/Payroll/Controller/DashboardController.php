<?php

namespace App\Payroll\Controller;

use App\Core\View;
use Exception;

class DashboardController
{
    private function isPayrollTeam(): bool
    {
        // Assuming Role ID 4 is 'Payroll Team'
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
            isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] === 4;
    }

    private function enforcePayrollAccess(): void
    {
        if (!$this->isPayrollTeam()) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Access Denied. Payroll Team access required.'];
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                header('Location: /');
            } else {
                header('Location: /login');
            }
            exit;
        }
    }

    public function index(): void
    {
        $this->enforcePayrollAccess();

        $data = [
            'pageTitle' => 'Payroll Dashboard',
            'userName' => $_SESSION['user_name'] ?? 'Payroll Team Member'
        ];
        View::render('payroll/dashboard/index', $data, 'app');
    }
}