<?php

namespace App\Supervisor\Controller;

use App\Core\View;
use App\UserManagement\Model\Timesheet;
use App\UserManagement\Model\Paysheet;
use App\UserManagement\Model\PaysheetItem;
use App\UserManagement\Model\User;
use App\UserManagement\Model\Site;      // Included if other methods might use it
use App\UserManagement\Model\AuditLog;  // For logging actions
use App\Core\Database\Connection;
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
                header('Location: /'); // Or a generic dashboard
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
        $formData = $_POST; // Use $formData for consistency

        // Validate dates
        $errors = [];
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
                if (!isset($tsData['timesheet_id']) || !isset($tsData['hours_worked']) || !isset($tsData['pay_rate'])) {
                    error_log("Paysheet Gen: Missing critical data for timesheet processing. Timesheet data: " . print_r($tsData, true));
                    continue;
                }

                $hoursWorked = (float)$tsData['hours_worked'];
                $payRate = (float)$tsData['pay_rate'];
                $calculatedPay = $hoursWorked * $payRate;

                $paysheetItemsData[] = [
                    'timesheet_id' => (int)$tsData['timesheet_id'],
                    'hours_worked_snapshot' => $hoursWorked,
                    'pay_rate_snapshot' => $payRate,
                    'calculated_pay' => $calculatedPay
                ];
                $totalPaysheetAmount += $calculatedPay;
            }

            if (empty($paysheetItemsData)) {
                $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'No processable timesheet data after calculations.'];
                header('Location: /supervisor/paysheets/create');
                exit;
            }

            $paysheet = new Paysheet([
                'supervisor_user_id' => $supervisorId,
                'pay_period_start_date' => $startDateStr,
                'pay_period_end_date' => $endDateStr,
                'total_hours_amount' => $totalPaysheetAmount,
                'status' => 'Pending Payroll',
                'submitted_at' => date('Y-m-d H:i:s')
            ]);

            if ($paysheet->save()) {
                if (PaysheetItem::saveBatch($paysheet->id, $paysheetItemsData)) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Paysheet (ID: '.$paysheet->id.') generated successfully and submitted to Payroll!'];

                    // Log this action
                    AuditLog::logAction(
                        $supervisorId,
                        'PAYSHEET_GENERATED_BY_SUPERVISOR',
                        'Paysheet',
                        $paysheet->id,
                        json_encode([
                            'period_start' => $paysheet->pay_period_start_date,
                            'period_end' => $paysheet->pay_period_end_date,
                            'total_amount' => $paysheet->total_hours_amount
                        ])
                    );
                    // TODO: Notify Payroll Team
                    header('Location: /supervisor/paysheets'); // Redirect to paysheet list page
                    exit;
                } else {
                    error_log("Paysheet created (ID: {$paysheet->id}) but failed to save items.");
                    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Paysheet created, but failed to add timesheet items. Please contact support.'];
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to create paysheet. Please try again.'];
            }
        } catch (Exception $e) {
            error_log("Error in PaysheetController::generate for supervisor ID {$supervisorId}: " . $e->getMessage());
            $_SESSION['form_data'] = $formData;
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred while generating the paysheet: ' . $e->getMessage()];
        }
        // Fallthrough redirect if errors occurred before explicit exits
        header('Location: /supervisor/paysheets/create');
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

            if ($paysheet->supervisor_user_id !== $supervisorId) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'You are not authorized to view this paysheet.'];
                header('Location: /supervisor/paysheets');
                exit;
            }

            $paysheetItems = PaysheetItem::findAllByPaysheetId($paysheetId);
            // Optionally, fetch audit logs for this paysheet if supervisor should see its history
            // $auditLogs = AuditLog::findByTarget('Paysheet', $paysheetId);

            View::render('supervisor/paysheets/view', [
                'pageTitle' => 'Paysheet Details (ID: ' . htmlspecialchars($paysheet->id) . ')',
                'paysheet' => $paysheet,
                'paysheetItems' => $paysheetItems
                // 'auditLogs' => $auditLogs // Pass to view if fetched
            ], 'app');

        } catch (Exception $e) {
            error_log("Error loading paysheet details for ID {$paysheetId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load paysheet details. ' . $e->getMessage()];
            header('Location: /supervisor/paysheets');
            exit;
        }
    }

    /**
     * Cancel/Recall a paysheet that was marked for review by Payroll.
     * This will soft-delete the paysheet and its items.
     * @param string $id The ID of the paysheet to cancel.
     */
    public function cancelReviewedPaysheet(string $id): void
    {
        $this->enforceSupervisorAccess();
        $paysheetId = (int)$id;
        $supervisorId = $_SESSION['user_id'] ?? 0;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Invalid request method.'];
            header('Location: /supervisor/paysheets/under-review');
            exit;
        }

        try {
            $paysheet = Paysheet::findById($paysheetId);

            if (!$paysheet) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Paysheet not found.'];
                header('Location: /supervisor/paysheets/under-review');
                exit;
            }

            if ($paysheet->supervisor_user_id !== $supervisorId || $paysheet->status !== 'Review') {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'This paysheet cannot be cancelled or is not under review by you.'];
                header('Location: /supervisor/paysheets/under-review');
                exit;
            }

            $previousStatus = $paysheet->status;

            if (Paysheet::softDeleteWithItems($paysheetId)) { // This method also sets status to 'Cancelled'
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Paysheet (ID: '.$paysheetId.') has been cancelled/recalled. Its timesheets are now available for a new paysheet.'];

                // Log this action
                AuditLog::logAction(
                    $supervisorId,
                    'PAYSHEET_CANCELLED_BY_SUPERVISOR',
                    'Paysheet',
                    $paysheetId,
                    json_encode(['previous_status' => $previousStatus, 'new_status' => 'Cancelled', 'reason' => 'Recalled after payroll review'])
                );
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to cancel the paysheet. Please try again.'];
            }

        } catch (Exception $e) {
            error_log("Error cancelling reviewed paysheet ID {$paysheetId} by supervisor ID {$supervisorId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
        }

        header('Location: /supervisor/paysheets/under-review'); // Refresh the list
        exit;
    }

    /**
     * Display a list of paysheets marked for review by Payroll, for the current supervisor.
     */
    public function listUnderReview(): void
    {
        $this->enforceSupervisorAccess();
        $supervisorId = $_SESSION['user_id'] ?? 0;
        $paysheetsInReview = [];
        $errorMessage = null;

        if ($supervisorId <= 0) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'User session error. Please login again.'];
            header('Location: /login');
            exit;
        }

        try {
            $paysheetsInReview = Paysheet::findInReviewBySupervisorId($supervisorId);
        } catch (Exception $e) {
            error_log("Error fetching paysheets under review for supervisor ID {$supervisorId}: " . $e->getMessage());
            $errorMessage = "Could not load paysheets under review at this time.";
        }

        View::render('supervisor/paysheets/under_review', [
            'pageTitle' => 'Paysheets Under Review',
            'paysheets' => $paysheetsInReview,
            'errorMessage' => $errorMessage
        ], 'app');
    }
    public function acknowledgeReview(string $id): void
    {
        $this->enforceSupervisorAccess();
        $paysheetId = (int)$id;
        $supervisorId = $_SESSION['user_id'] ?? 0;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Invalid request method.'];
            header('Location: /supervisor/paysheets/under-review');
            exit;
        }

        $db = Connection::getInstance(); // For transaction

        try {
            $paysheet = Paysheet::findById($paysheetId);

            if (!$paysheet || $paysheet->supervisor_user_id !== $supervisorId || $paysheet->status !== 'Review') {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Paysheet not found or cannot be actioned.'];
                header('Location: /supervisor/paysheets/under-review');
                exit;
            }

            $db->beginTransaction();

            // 1. Get all timesheet IDs associated with this paysheet
            $paysheetItems = PaysheetItem::findAllByPaysheetId($paysheetId); // Assuming this returns objects with timesheet_id
            $timesheetIdsToRevert = [];
            foreach ($paysheetItems as $item) {
                $timesheetIdsToRevert[] = $item->timesheet_id;
            }

            // 2. Revert status of these timesheets to 'Pending' (or 'CorrectionNeeded')
            if (!empty($timesheetIdsToRevert)) {
                if (!Timesheet::revertStatusForCorrection($timesheetIdsToRevert, 'Pending')) {
                    throw new Exception("Failed to revert status of associated timesheets."); // This would trigger the catch block
                }
                // Log audit for each timesheet reverted (optional, can be verbose)
                foreach ($timesheetIdsToRevert as $tsId) {
                    AuditLog::logAction($supervisorId, 'TIMESHEET_STATUS_REVERTED_FOR_PAYSHEET_CORRECTION', 'Timesheet', $tsId, json_encode(['paysheet_id' => $paysheetId, 'new_status' => 'Pending']));
                }
            }

            // 3. Delete existing paysheet items for this paysheet, as they will be regenerated
            if (!PaysheetItem::deleteByPaysheetId($paysheetId)) {
                throw new Exception("Failed to delete old paysheet items.");
            }

            // 4. Update the paysheet status
            $paysheet->status = 'AddressingReview';
            // Keep review_remarks from Payroll, clear payroll approval fields
            $paysheet->approved_by_payroll_id = null;
            $paysheet->approved_at = null;

            if (!$paysheet->save()) {
                throw new Exception("Failed to update paysheet status.");
            }

            AuditLog::logAction(
                $supervisorId,
                'PAYSHEET_REVIEW_ACKNOWLEDGED',
                'Paysheet',
                $paysheetId,
                json_encode(['new_status' => 'AddressingReview', 'payroll_remarks' => $paysheet->review_remarks])
            );

            $db->commit();
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Paysheet review acknowledged. Associated timesheets are now unlocked for correction. Please correct them and then resubmit this paysheet.'];

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error in PaysheetController::acknowledgeReview for Paysheet ID {$paysheetId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
        }

        header('Location: /supervisor/paysheets/under-review'); // Refresh the list, or redirect to where they can fix timesheets
        exit;
    }
    public function resubmitReviewedPaysheet(string $id): void
    {
        $this->enforceSupervisorAccess();
        $paysheetId = (int)$id;
        $supervisorId = $_SESSION['user_id'] ?? 0;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Invalid request method.'];
            header('Location: /supervisor/paysheets/under-review');
            exit;
        }

        $db = Connection::getInstance(); // For transaction

        try {
            $paysheet = Paysheet::findById($paysheetId);

            if (!$paysheet || $paysheet->supervisor_user_id !== $supervisorId || $paysheet->status !== 'AddressingReview') {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Paysheet not found or cannot be resubmitted at this time.'];
                header('Location: /supervisor/paysheets/under-review');
                exit;
            }

            $db->beginTransaction();

            // 1. Delete old paysheet items (should have been done in acknowledgeReview, but defensive delete is okay)
            // This ensures only new, corrected items are linked.
            PaysheetItem::deleteByPaysheetId($paysheetId);

            // 2. Re-fetch approved timesheets for the original period
            $approvedTimesheetsData = Timesheet::findApprovedForPaysheet(
                $supervisorId,
                $paysheet->pay_period_start_date,
                $paysheet->pay_period_end_date
            );

            if (empty($approvedTimesheetsData)) {
                $db->rollBack(); // Rollback before redirecting
                $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'No approved timesheets found to regenerate items for this paysheet. Please ensure timesheets are corrected and approved.'];
                header('Location: /supervisor/paysheets/under-review');
                exit;
            }

            $newPaysheetItemsData = [];
            $newTotalPaysheetAmount = 0.00;

            foreach ($approvedTimesheetsData as $tsData) {
                if (!isset($tsData['timesheet_id']) || !isset($tsData['hours_worked']) || !isset($tsData['pay_rate'])) {
                    continue;
                }
                $hoursWorked = (float)$tsData['hours_worked'];
                $payRate = (float)$tsData['pay_rate'];
                $calculatedPay = $hoursWorked * $payRate;

                $newPaysheetItemsData[] = [
                    'timesheet_id' => (int)$tsData['timesheet_id'],
                    'hours_worked_snapshot' => $hoursWorked,
                    'pay_rate_snapshot' => $payRate,
                    'calculated_pay' => $calculatedPay
                ];
                $newTotalPaysheetAmount += $calculatedPay;
            }

            if (empty($newPaysheetItemsData)) {
                $db->rollBack();
                $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'No processable timesheet data after re-evaluation.'];
                header('Location: /supervisor/paysheets/under-review');
                exit;
            }

            // 3. Save new paysheet items
            if (!PaysheetItem::saveBatch($paysheet->id, $newPaysheetItemsData)) {
                throw new Exception("Failed to save new paysheet items during resubmission.");
            }

            // 4. Update the original paysheet record
            $paysheet->total_hours_amount = $newTotalPaysheetAmount;
            $paysheet->status = 'Pending Payroll'; // Back to payroll for review
            $paysheet->submitted_at = date('Y-m-d H:i:s'); // Update submission time
            $paysheet->review_remarks = null; // Clear payroll's previous remarks
            $paysheet->reviewed_by_payroll_id = null;
            $paysheet->approved_by_payroll_id = null; // Clear previous payroll approval if any
            $paysheet->approved_at = null;

            if (!$paysheet->save()) {
                throw new Exception("Failed to update and resubmit paysheet.");
            }

            AuditLog::logAction(
                $supervisorId,
                'PAYSHEET_RESUBMITTED_BY_SUPERVISOR',
                'Paysheet',
                $paysheetId,
                json_encode([
                    'new_status' => 'Pending Payroll',
                    'new_total_amount' => $newTotalPaysheetAmount,
                    'items_recalculated' => count($newPaysheetItemsData)
                ])
            );

            $db->commit();
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Paysheet (ID: '.$paysheetId.') has been re-evaluated and resubmitted to Payroll.'];

        } catch (Exception $e) {
            if ($db->inTransaction()) { // Check if transaction is active before rollback
                $db->rollBack();
            }
            error_log("Error resubmitting paysheet ID {$paysheetId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred while resubmitting the paysheet: ' . $e->getMessage()];
        }

        header('Location: /supervisor/paysheets/under-review'); // Or /supervisor/paysheets
        exit;
    }
}