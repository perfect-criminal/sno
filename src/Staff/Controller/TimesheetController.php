<?php

namespace App\Staff\Controller;

use App\Core\View;
use App\UserManagement\Model\Site;        // To fetch assigned sites
use App\UserManagement\Model\Timesheet;  // The new Timesheet model
use Exception;

class TimesheetController
{
    // Re-using isStaff/enforceStaffAccess from DashboardController.
    // Consider moving these to a BaseStaffController or a Trait if you have many staff controllers.
    private function isStaff(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
            isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] === 2; // Role ID 2 for Staff
    }

    private function enforceStaffAccess(): void
    {
        if (!$this->isStaff()) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Access Denied. Staff access required.'];
            header('Location: /login');
            exit;
        }
    }
    /**
     * Display the list of timesheets for the logged-in staff member.
     */
    public function index(): void
    {
        $this->enforceStaffAccess();
        $staffId = $_SESSION['user_id'] ?? 0;
        $timesheets = [];
        $errorMessage = null;

        if ($staffId <= 0) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'User session error. Please login again.'];
            header('Location: /login');
            exit;
        }

        try {
            $timesheets = Timesheet::findByStaffId($staffId);
        } catch (Exception $e) {
            error_log("Error fetching timesheet history for staff ID {$staffId}: " . $e->getMessage());
            // Set a flash message or pass error to the view
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load your timesheet history.'];
            // Or pass $errorMessage to the view if you prefer not to use flash for this
            $errorMessage = 'Could not load your timesheet history at this time.';
        }

        View::render('staff/timesheets/index', [
            'pageTitle' => 'My Timesheet History',
            'timesheets' => $timesheets,
            'errorMessage' => $errorMessage
        ], 'app');
    }
    /**
     * Display the form for staff to submit a new timesheet.
     */
    public function create(): void
    {
        $this->enforceStaffAccess();
        $staffId = $_SESSION['user_id'] ?? 0;
        $assignedSites = [];
        $allActiveSites = []; // For unscheduled shifts

        try {
            if ($staffId > 0) {
                $assignedSites = Site::findAssignedToStaff($staffId);
            }
            // Fetch all active sites for the unscheduled option
            $allActiveSites = Site::findAllActive();

        } catch (Exception $e) {
            error_log("Error fetching sites for timesheet form: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load site data for timesheet form.'];
            // Potentially redirect or display error within a simpler view
        }

        View::render('staff/timesheets/create', [
            'pageTitle' => 'Submit New Timesheet',
            'assignedSites' => $assignedSites,
            'allActiveSites' => $allActiveSites, // <-- PASS ALL ACTIVE SITES TO VIEW
            'form_data' => $_SESSION['form_data'] ?? [],
            'errors' => $_SESSION['form_errors'] ?? []
        ], 'app');
        unset($_SESSION['form_errors']);
        unset($_SESSION['form_data']);
    }

    /**
     * Store a newly submitted timesheet.
     */
    public function store(): void
    {
        $this->enforceStaffAccess();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /staff/timesheets/create');
            exit;
        }

        $staffId = $_SESSION['user_id'] ?? 0;
        $formData = $_POST;
        $errors = [];

        $isUnscheduled = isset($formData['is_unscheduled_shift']) && $formData['is_unscheduled_shift'] == '1';

        // Validation
        if (empty($staffId)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'User session error. Please login again.'];
            header('Location: /login');
            exit;
        }

        // Determine which site_id to use
        $siteId = null;
        if ($isUnscheduled) {
            if (empty($formData['unscheduled_site_id'])) {
                $errors['unscheduled_site_id'] = 'Please select a site for the unscheduled shift.';
            } else {
                $siteId = (int)$formData['unscheduled_site_id'];
            }
            // Make notes mandatory for unscheduled shifts
            if (empty(trim($formData['notes']))) {
                $errors['notes'] = 'Notes are required for unscheduled shifts to provide justification.';
            }
        } else {
            if (empty($formData['assigned_site_id'])) { // Changed name from 'site_id' to be specific
                $errors['assigned_site_id'] = 'Please select an assigned site.';
            } else {
                $siteId = (int)$formData['assigned_site_id'];
            }
        }
        // Common validations
        if (empty($formData['shift_date'])) {
            $errors['shift_date'] = 'Shift date is required.';
        } else {
            $d = \DateTime::createFromFormat('Y-m-d', $formData['shift_date']);
            if (!$d || $d->format('Y-m-d') !== $formData['shift_date']) {
                $errors['shift_date'] = 'Invalid date format. Please use YYYY-MM-DD.';
            } elseif ($d > new \DateTime('now')) {
                $errors['shift_date'] = 'Shift date cannot be in the future.';
            }
        }
        if (empty($formData['hours_worked'])) {
            $errors['hours_worked'] = 'Hours worked are required.';
        } elseif (!is_numeric($formData['hours_worked']) || $formData['hours_worked'] <= 0 || $formData['hours_worked'] > 24) {
            $errors['hours_worked'] = 'Hours worked must be a positive number, not exceeding 24.';
        }


        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $formData;
            header('Location: /staff/timesheets/create');
            exit;
        }

        try {
            $timesheet = new Timesheet();
            $timesheet->staff_user_id = $staffId;
            $timesheet->site_id = $siteId; // Use the determined siteId
            $timesheet->shift_date = $formData['shift_date'];
            $timesheet->hours_worked = (float)$formData['hours_worked'];
            $timesheet->is_unscheduled_shift = $isUnscheduled; // Set the flag
            $timesheet->notes = !empty($formData['notes']) ? trim($formData['notes']) : null;
            $timesheet->status = 'Pending';

            if ($timesheet->save()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Timesheet submitted successfully!'];
                header('Location: /staff/dashboard');
            } else {
                $_SESSION['form_data'] = $formData;
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to submit timesheet. Please try again.'];
                header('Location: /staff/timesheets/create');
            }
        } catch (Exception $e) {
            error_log("Error in TimesheetController::store: " . $e->getMessage());
            $_SESSION['form_data'] = $formData;
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
            header('Location: /staff/timesheets/create');
        }
        exit;
    }
    /**
     * Display a specific timesheet for staff review after supervisor edit.
     * @param string $id The ID of the timesheet to review.
     */
    public function review(string $id): void
    {
        $this->enforceStaffAccess();
        $timesheetId = (int)$id;
        $staffId = $_SESSION['user_id'] ?? 0;

        try {
            $timesheet = Timesheet::findById($timesheetId); // findById already joins site and company name

            if (!$timesheet) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Timesheet not found.'];
                header('Location: /staff/dashboard'); // Or timesheet history
                exit;
            }

            // Security Check: Belongs to current staff and is in 'PendingStaffConfirmation' status
            if ($timesheet->staff_user_id !== $staffId || $timesheet->status !== 'PendingStaffConfirmation') {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'This timesheet is not available for your review or has already been actioned.'];
                header('Location: /staff/dashboard'); // Or timesheet history
                exit;
            }

            // Fetch original user details (if needed, Timesheet::findById already gets staff_name)
            // Fetch supervisor details (if you want to display who edited it)
            $supervisorName = null;
            if ($timesheet->edited_by_supervisor_id) {
                $supervisor = \App\UserManagement\Model\User::findById($timesheet->edited_by_supervisor_id);
                if ($supervisor) {
                    $supervisorName = $supervisor->getFullName();
                }
            }

            View::render('staff/timesheets/review_edit', [
                'pageTitle' => 'Review Edited Timesheet',
                'timesheet' => $timesheet,
                'supervisorName' => $supervisorName,
                // No form_data or errors initially, these would be for re-display after failed agree/disagree attempt
                'form_data' => $_SESSION['form_data'] ?? [], // For sticky disagreement reason
                'errors' => $_SESSION['form_errors'] ?? []
            ], 'app');
            unset($_SESSION['form_errors']);
            unset($_SESSION['form_data']);

        } catch (Exception $e) {
            error_log("Error loading timesheet review for staff (ID {$timesheetId}): " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load timesheet for review. ' . $e->getMessage()];
            header('Location: /staff/dashboard');
            exit;
        }
    }
    public function agreeToEdit(string $id): void
    {
        $this->enforceStaffAccess();
        $timesheetId = (int)$id;
        $staffId = $_SESSION['user_id'] ?? 0;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /staff/timesheets/review/' . $timesheetId);
            exit;
        }

        try {
            $timesheet = Timesheet::findById($timesheetId);

            if (!$timesheet || $timesheet->staff_user_id !== $staffId || $timesheet->status !== 'PendingStaffConfirmation') {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Invalid action or timesheet not found for agreement.'];
                header('Location: /staff/dashboard');
                exit;
            }

            $timesheet->status = 'Approved'; // Staff agrees, so it's considered approved
            // The edited_by_supervisor_id and edited_at fields remain for audit.
            // original_hours_worked also remains.
            // Clear any dispute reason if it was somehow set
            $timesheet->staff_dispute_reason = null;
            // Note: 'approver_user_id' and 'approved_at' are typically for final supervisor/payroll approval.
            // If staff agreeing means it's fully approved, you might set those here too.
            // For now, let's assume 'Approved' by staff is sufficient for this step.

            if ($timesheet->save()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'You have agreed to the changes. Timesheet is now marked as Approved.'];
                // TODO: Notify supervisor
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to update timesheet status. Please try again.'];
            }
        } catch (Exception $e) {
            error_log("Error in TimesheetController::agreeToEdit for ID {$timesheetId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
        }
        header('Location: /staff/timesheets'); // Redirect to timesheet history
        exit;
    }

    public function disagreeWithEdit(string $id): void
    {
        $this->enforceStaffAccess();
        $timesheetId = (int)$id;
        $staffId = $_SESSION['user_id'] ?? 0;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /staff/timesheets/review/' . $timesheetId);
            exit;
        }

        $disagreementReason = trim($_POST['disagreement_reason'] ?? '');
        $errors = [];

        if (empty($disagreementReason)) {
            $errors['disagreement_reason'] = 'A reason for disagreement is required.';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST; // For sticky reason field
            header('Location: /staff/timesheets/review/' . $timesheetId);
            exit;
        }

        try {
            $timesheet = Timesheet::findById($timesheetId);

            if (!$timesheet || $timesheet->staff_user_id !== $staffId || $timesheet->status !== 'PendingStaffConfirmation') {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Invalid action or timesheet not found for disagreement.'];
                header('Location: /staff/dashboard');
                exit;
            }

            $timesheet->status = 'DisputedByStaff';
            $timesheet->staff_dispute_reason = $disagreementReason;
            // Edited_by_supervisor_id and edited_at remain from supervisor's edit

            if ($timesheet->save()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Your disagreement has been recorded and sent for supervisor review.'];
                // TODO: Notify supervisor
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to record disagreement. Please try again.'];
            }
        } catch (Exception $e) {
            error_log("Error in TimesheetController::disagreeWithEdit for ID {$timesheetId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
        }
        header('Location: /staff/timesheets'); // Redirect to timesheet history
        exit;
    }
    /**
     * Display the form for staff to edit and resubmit a rejected timesheet.
     * @param string $id The ID of the timesheet to edit.
     */
    public function edit(string $id): void
    {
        $this->enforceStaffAccess();
        $timesheetId = (int)$id;
        $staffId = $_SESSION['user_id'] ?? 0;

        try {
            $timesheet = Timesheet::findById($timesheetId);

            if (!$timesheet) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Timesheet not found.'];
                header('Location: /staff/timesheets');
                exit;
            }

            // Security Check: Belongs to current staff and is in 'Rejected' status
            if ($timesheet->staff_user_id !== $staffId || $timesheet->status !== 'Rejected') {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'This timesheet cannot be edited or resubmitted at this time.'];
                header('Location: /staff/timesheets');
                exit;
            }

            $assignedSites = Site::findAssignedToStaff($staffId);
            $allActiveSites = Site::findAllActive();

            View::render('staff/timesheets/edit_resubmit', [ // New view file
                'pageTitle' => 'Edit and Resubmit Timesheet (ID: ' . $timesheetId . ')',
                'timesheet' => $timesheet, // Pass the timesheet data
                'assignedSites' => $assignedSites,
                'allActiveSites' => $allActiveSites,
                'form_data' => $_SESSION['form_data'] ?? (array)$timesheet, // Pre-fill with timesheet or old form data
                'errors' => $_SESSION['form_errors'] ?? []
            ], 'app');
            unset($_SESSION['form_errors']);
            unset($_SESSION['form_data']);

        } catch (Exception $e) {
            error_log("Error loading timesheet edit form for staff (ID {$timesheetId}): " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load timesheet for editing.'];
            header('Location: /staff/timesheets');
            exit;
        }
    }
}