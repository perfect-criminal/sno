<?php

namespace App\Supervisor\Controller;

use App\Core\View;
use App\UserManagement\Model\Timesheet;
use App\UserManagement\Model\Paysheet;
use App\UserManagement\Model\PaysheetItem;
use App\UserManagement\Model\User; // For supervisor's own details if needed
use Exception;
use DateTime; // For date validation

class PaysheetController
{
    private function isSupervisor(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
            isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] === 3; // Role ID 3 for Supervisor
    }

    private function enforceSupervisorAccess(): void
    {
        if (!$this->isSupervisor()) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Access Denied. Supervisor access required.'];
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                header('Location: /');
            } else {
                header('Location: /login');
            }
            exit;
        }
    }

    /**
     * Display the form to select a date range for paysheet generation.
     */
    public function create(): void
    {
        $this->enforceSupervisorAccess();
        View::render('supervisor/paysheets/create', [
            'pageTitle' => 'Generate New Paysheet',
            'form_data' => $_SESSION['form_data'] ?? [],
            'errors' => $_SESSION['form_errors'] ?? []
        ], 'app');
        unset($_SESSION['form_errors']);
        unset($_SESSION['form_data']);
    }

    /**
     * Generate and store a new paysheet based on a date range.
     */
    public function generate(): void
    {
        $this->enforceSupervisorAccess();
        $supervisorId = $_SESSION['user_id'] ?? 0;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /supervisor/paysheets/create');
            exit;
        }

        $formData = $_POST;
        $errors = [];

        // Validate dates
        $startDateStr = $formData['start_date'] ?? '';
        $endDateStr = $formData['end_date'] ?? '';

        if (empty($startDateStr)) {
            $errors['start_date'] = 'Start date is required.';
        } else {
            $startDate = DateTime::createFromFormat('Y-m-d', $startDateStr);
            if (!$startDate || $startDate->format('Y-m-d') !== $startDateStr) {
                $errors['start_date'] = 'Invalid start date format. Please use YYYY-MM-DD.';
            }
        }

        if (empty($endDateStr)) {
            $errors['end_date'] = 'End date is required.';
        } else {
            $endDate = DateTime::createFromFormat('Y-m-d', $endDateStr);
            if (!$endDate || $endDate->format('Y-m-d') !== $endDateStr) {
                $errors['end_date'] = 'Invalid end date format. Please use YYYY-MM-DD.';
            }
        }

        if (isset($startDate) && isset($endDate) && $startDate > $endDate) {
            $errors['end_date'] = 'End date cannot be before the start date.';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $formData;
            header('Location: /supervisor/paysheets/create');
            exit;
        }

        try {
            $approvedTimesheetsData = Timesheet::findApprovedForPaysheet($supervisorId, $startDateStr, $endDateStr);

            if (empty($approvedTimesheetsData)) {
                $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'No approved timesheets found for your staff in the selected period, or they are already on other paysheets.'];
                header('Location: /supervisor/paysheets/create');
                exit;
            }

            $paysheetItemsData = [];
            $totalPaysheetAmount = 0.00;

            foreach ($approvedTimesheetsData as $tsData) {
                // Ensure required keys exist from Timesheet::findApprovedForPaysheet query
                if (!isset($tsData['timesheet_id']) || !isset($tsData['hours_worked']) || !isset($tsData['pay_rate'])) {
                    error_log("Paysheet Gen: Missing critical data for timesheet processing. Timesheet data: " . print_r($tsData, true));
                    // Potentially throw an error or skip this item with a warning
                    continue;
                }

                $hoursWorked = (float)$tsData['hours_worked'];
                $payRate = (float)$tsData['pay_rate'];
                $calculatedPay = $hoursWorked * $payRate;

                $paysheetItemsData[] = [
                    'timesheet_id' => (int)$tsData['timesheet_id'],
                    'hours_worked_snapshot' => $hoursWorked, // <-- ADD THIS
                    'pay_rate_snapshot' => $payRate,         // <-- ADD THIS
                    'calculated_pay' => $calculatedPay
                ];
                $totalPaysheetAmount += $calculatedPay;
            }
            if (empty($paysheetItemsData)) { // Double check after processing
                $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'No processable timesheet data after calculations.'];
                header('Location: /supervisor/paysheets/create');
                exit;
            }

            // Create the main paysheet record
            $paysheet = new Paysheet([
                'supervisor_user_id' => $supervisorId,
                'pay_period_start_date' => $startDateStr,
                'pay_period_end_date' => $endDateStr,
                'total_hours_amount' => $totalPaysheetAmount,
                'status' => 'Pending Payroll', // Default status
                'submitted_at' => date('Y-m-d H:i:s')
            ]);

            if ($paysheet->save()) { // This saves the paysheet and gets its ID
                // Now save the paysheet items
                if (PaysheetItem::saveBatch($paysheet->id, $paysheetItemsData)) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Paysheet (ID: '.$paysheet->id.') generated successfully and submitted to Payroll!'];
                    // TODO: Notify Payroll Team
                    header('Location: /supervisor/dashboard'); // Or a paysheet list page
                } else {
                    // This is tricky: paysheet created but items failed. Might need to delete paysheet or mark as error.
                    // For now, simple error.
                    error_log("Paysheet created (ID: {$paysheet->id}) but failed to save items.");
                    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Paysheet created, but failed to add timesheet items. Please contact support.'];
                    header('Location: /supervisor/paysheets/create');
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to create paysheet. Please try again.'];
                header('Location: /supervisor/paysheets/create');
            }

        } catch (Exception $e) {
            error_log("Error in PaysheetController::generate for supervisor ID {$supervisorId}: " . $e->getMessage());
            $_SESSION['form_data'] = $formData;
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred while generating the paysheet: ' . $e->getMessage()];
            header('Location: /supervisor/paysheets/create');
        }
        exit;
    }

    /**
     * Display a list of paysheets created by the current supervisor.
     */
    public function index(): void
    {
        $this->enforceSupervisorAccess();
        $supervisorId = $_SESSION['user_id'] ?? 0;
        $paysheets = [];
        $errorMessage = null;

        if ($supervisorId <= 0) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'User session error. Please login again.'];
            header('Location: /login');
            exit;
        }

        try {
            $paysheets = Paysheet::findAllBySupervisorId($supervisorId);
        } catch (Exception $e) {
            error_log("Error fetching paysheets for supervisor ID {$supervisorId}: " . $e->getMessage());
            $errorMessage = "Could not load your generated paysheets at this time.";
        }

        View::render('supervisor/paysheets/index', [
            'pageTitle' => 'My Generated Paysheets',
            'paysheets' => $paysheets,
            'errorMessage' => $errorMessage
        ], 'app');
    }
    /**
     * Display the details of a specific paysheet.
     * @param string $id The ID of the paysheet to view.
     */
    public function view(string $id): void
    {
        $this->enforceSupervisorAccess();
        $paysheetId = (int)$id;
        $supervisorId = $_SESSION['user_id'] ?? 0;

        try {
            $paysheet = Paysheet::findById($paysheetId);

            if (!$paysheet) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Paysheet not found.'];
                header('Location: /supervisor/paysheets');
                exit;
            }

            // Security Check: Ensure this paysheet belongs to the current supervisor
            if ($paysheet->supervisor_user_id !== $supervisorId) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'You are not authorized to view this paysheet.'];
                header('Location: /supervisor/paysheets');
                exit;
            }

            $paysheetItems = PaysheetItem::findAllByPaysheetId($paysheetId);

            View::render('supervisor/paysheets/view', [
                'pageTitle' => 'Paysheet Details (ID: ' . htmlspecialchars($paysheet->id) . ')',
                'paysheet' => $paysheet,
                'paysheetItems' => $paysheetItems
            ], 'app');

        } catch (Exception $e) {
            error_log("Error loading paysheet details for ID {$paysheetId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load paysheet details. ' . $e->getMessage()];
            header('Location: /supervisor/paysheets');
            exit;
        }
    }
}