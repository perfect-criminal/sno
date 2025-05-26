<?php

namespace App\Payroll\Controller;

use App\Core\View;
use App\UserManagement\Model\Paysheet;
use App\UserManagement\Model\PaysheetItem;
use App\UserManagement\Model\User;
use App\UserManagement\Model\AuditLog; // <-- ADDED for logging
use Exception;

class PaysheetController
{
    private function isPayrollTeam(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
            isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] === 4; // Role ID 4 for Payroll
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

    public function listPendingReview(): void
    {
        $this->enforcePayrollAccess();
        $paysheets = [];
        $errorMessage = null;

        try {
            $paysheets = Paysheet::findPendingPayrollReview();
        } catch (Exception $e) {
            error_log("Error fetching paysheets for payroll review: " . $e->getMessage());
            $errorMessage = "Could not load paysheets for review at this time.";
        }

        View::render('payroll/paysheets/pending_review', [
            'pageTitle' => 'Paysheets Pending Review',
            'paysheets' => $paysheets,
            'errorMessage' => $errorMessage
        ], 'app');
    }

    public function reviewDetails(string $id): void
    {
        $this->enforcePayrollAccess();
        $paysheetId = (int)$id;

        try {
            $paysheet = Paysheet::findById($paysheetId);

            if (!$paysheet) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Paysheet not found.'];
                header('Location: /payroll/paysheets/pending-review');
                exit;
            }

            if (empty($paysheet->supervisor_name) && $paysheet->supervisor_user_id) {
                $supervisor = User::findById($paysheet->supervisor_user_id);
                if ($supervisor) {
                    $paysheet->supervisor_name = $supervisor->getFullName();
                }
            }

            $paysheetItems = PaysheetItem::findAllByPaysheetId($paysheetId);

            // Fetch audit logs for this paysheet
            $auditLogs = AuditLog::findByTarget('Paysheet', $paysheetId);


            View::render('payroll/paysheets/review_details', [
                'pageTitle' => 'Review Paysheet Details (ID: ' . htmlspecialchars($paysheet->id) . ')',
                'paysheet' => $paysheet,
                'paysheetItems' => $paysheetItems,
                'auditLogs' => $auditLogs // <-- PASS AUDIT LOGS TO VIEW
            ], 'app');

        } catch (Exception $e) {
            error_log("Error loading paysheet details for payroll review (ID {$paysheetId}): " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load paysheet details. ' . $e->getMessage()];
            header('Location: /payroll/paysheets/pending-review');
            exit;
        }
    }

    public function approveForRun(string $id): void
    {
        $this->enforcePayrollAccess();
        $paysheetId = (int)$id;
        $payrollUserId = $_SESSION['user_id'] ?? 0;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { /* ... */ }

        try {
            $paysheet = Paysheet::findById($paysheetId);
            if (!$paysheet) { /* ... */ }
            $allowedStatuses = ['Pending Payroll', 'Review'];
            if (!in_array($paysheet->status, $allowedStatuses)) { /* ... */ }

            $paysheet->status = 'Approved';
            $paysheet->approved_by_payroll_id = $payrollUserId;
            $paysheet->approved_at = date('Y-m-d H:i:s');
            $paysheet->reviewed_by_payroll_id = $payrollUserId;
            $paysheet->review_remarks = null;

            if ($paysheet->save()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Paysheet (ID: '.$paysheetId.') approved for payroll run successfully!'];
                AuditLog::logAction(
                    $payrollUserId,
                    'PAYSHEET_APPROVED_BY_PAYROLL',
                    'Paysheet',
                    $paysheetId,
                    json_encode(['new_status' => 'Approved'])
                );
            } else { /* ... */ }
        } catch (Exception $e) { /* ... */ }
        header('Location: /payroll/paysheets/pending-review');
        exit;
    }

    public function markForSupervisorReview(string $id): void
    {
        $this->enforcePayrollAccess();
        $paysheetId = (int)$id;
        $payrollUserId = $_SESSION['user_id'] ?? 0;
        $formData = $_POST; // Use formData for remarks

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { /* ... */ }
        $reviewRemarks = trim($formData['review_remarks'] ?? '');
        if (empty($reviewRemarks)) { /* ... */ }

        try {
            $paysheet = Paysheet::findById($paysheetId);
            if (!$paysheet) { /* ... */ }
            if ($paysheet->status !== 'Pending Payroll') { /* ... */ }

            $paysheet->status = 'Review';
            $paysheet->review_remarks = $reviewRemarks;
            $paysheet->reviewed_by_payroll_id = $payrollUserId;
            $paysheet->approved_by_payroll_id = null;
            $paysheet->approved_at = null;

            if ($paysheet->save()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Paysheet (ID: '.$paysheetId.') marked for supervisor review. Remarks sent.'];
                AuditLog::logAction(
                    $payrollUserId,
                    'PAYSHEET_MARKED_FOR_REVIEW',
                    'Paysheet',
                    $paysheetId,
                    json_encode(['new_status' => 'Review', 'remarks' => $reviewRemarks])
                );
            } else { /* ... */ }
        } catch (Exception $e) { /* ... */ }
        header('Location: /payroll/paysheets/pending-review');
        exit;
    }
}