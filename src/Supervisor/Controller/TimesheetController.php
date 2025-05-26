<?php

namespace App\Supervisor\Controller;

use App\Core\View;
use App\UserManagement\Model\Timesheet;
use App\UserManagement\Model\Site;
use App\UserManagement\Model\User;
use Exception;

class TimesheetController
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
     * Display pending timesheets for the supervisor's staff.
     */
    public function pending(): void
    {
        $this->enforceSupervisorAccess();
        $supervisorId = $_SESSION['user_id'] ?? 0;
        $pendingTimesheets = [];
        $errorMessage = null;

        if ($supervisorId <= 0) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'User session error. Please login again.'];
            header('Location: /login');
            exit;
        }

        try {
            $pendingTimesheets = Timesheet::findPendingBySupervisor($supervisorId);
        } catch (Exception $e) {
            error_log("Error fetching pending timesheets for supervisor ID {$supervisorId}: " . $e->getMessage());
            $errorMessage = "Could not load pending timesheets at this time.";
        }

        View::render('supervisor/timesheets/pending', [
            'pageTitle' => 'Pending Timesheet Approvals',
            'timesheets' => $pendingTimesheets,
            'errorMessage' => $errorMessage
        ], 'app');
    }

    public function approve(): void
    {
        $this->enforceSupervisorAccess();
        $supervisorId = $_SESSION['user_id'] ?? 0;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['timesheet_id'])) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Invalid request.'];
            header('Location: /supervisor/timesheets/pending');
            exit;
        }

        $timesheetId = (int)$_POST['timesheet_id'];

        try {
            $timesheet = Timesheet::findById($timesheetId);

            if (!$timesheet) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Timesheet not found.'];
                header('Location: /supervisor/timesheets/pending');
                exit;
            }

            // Security Check: Ensure timesheet belongs to supervised staff
            $staffUser = User::findById($timesheet->staff_user_id);
            if (!$staffUser || $staffUser->supervisor_id !== $supervisorId) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'You are not authorized to approve this timesheet.'];
                header('Location: /supervisor/timesheets/pending');
                exit;
            }

            // Check if timesheet is in a state that can be approved
            // (e.g., Pending, EditedBySupervisor, PendingStaffConfirmation, or even DisputedByStaff if supervisor overrides)
            $allowedStatusesForApproval = ['Pending', 'EditedBySupervisor', 'PendingStaffConfirmation', 'DisputedByStaff'];
            if (!in_array($timesheet->status, $allowedStatusesForApproval)) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'This timesheet is not in a state that can be approved. Current status: ' . $timesheet->status];
                header('Location: /supervisor/timesheets/pending');
                exit;
            }

            $timesheet->status = 'Approved';
            $timesheet->approver_user_id = $supervisorId;
            $timesheet->approved_at = date('Y-m-d H:i:s');
            $timesheet->rejection_reason = null; // Clear any previous rejection reason
            $timesheet->staff_dispute_reason = null; // Clear any previous dispute reason

            if ($timesheet->save()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Timesheet (ID: '.$timesheetId.') approved successfully!'];
                // TODO: Create notification for staff member
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to approve timesheet. Please try again.'];
            }
        } catch (Exception $e) {
            error_log("Error approving timesheet ID {$timesheetId} by supervisor ID {$supervisorId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred while approving the timesheet: ' . $e->getMessage()];
        }

        // Redirect back to the list where it came from, or a general pending list
        // For now, let's always go back to pending list.
        header('Location: /supervisor/timesheets/pending');
        exit;
    }

    public function showRejectForm(string $id): void
    {
        $this->enforceSupervisorAccess();
        $timesheetId = (int)$id;
        $supervisorId = $_SESSION['user_id'] ?? 0;

        try {
            $timesheet = Timesheet::findById($timesheetId);

            if (!$timesheet) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Timesheet not found.'];
                header('Location: /supervisor/timesheets/pending');
                exit;
            }

            // Security Check: Ensure timesheet belongs to supervised staff
            $staffUser = User::findById($timesheet->staff_user_id);
            if (!$staffUser || $staffUser->supervisor_id !== $supervisorId) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'You are not authorized to act on this timesheet.'];
                header('Location: /supervisor/timesheets/pending');
                exit;
            }

            // Check if timesheet is in a state that can be rejected
            $allowedStatusesForRejection = ['Pending', 'EditedBySupervisor', 'PendingStaffConfirmation', 'DisputedByStaff'];
            if (!in_array($timesheet->status, $allowedStatusesForRejection)) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'This timesheet is not in a state that can be rejected. Current status: ' . $timesheet->status];
                header('Location: /supervisor/timesheets/pending');
                exit;
            }


            View::render('supervisor/timesheets/reject_form', [
                'pageTitle' => 'Reject Timesheet for ' . htmlspecialchars($timesheet->staff_name ?? 'Staff'),
                'timesheet' => $timesheet,
                'form_data' => $_SESSION['form_data'] ?? [], // For sticky form on error
                'errors' => $_SESSION['form_errors'] ?? []
            ], 'app');
            unset($_SESSION['form_errors']);
            unset($_SESSION['form_data']);

        } catch (Exception $e) {
            error_log("Error loading timesheet rejection form for ID {$timesheetId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load timesheet for rejection.'];
            header('Location: /supervisor/timesheets/pending');
            exit;
        }
    }
    public function processReject(string $id): void
    {
        $this->enforceSupervisorAccess();
        $timesheetId = (int)$id;
        $supervisorId = $_SESSION['user_id'] ?? 0;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Should not happen if form POSTs correctly
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Invalid request method.'];
            header('Location: /supervisor/timesheets/reject-form/' . $timesheetId);
            exit;
        }

        $rejectionReason = trim($_POST['rejection_reason'] ?? '');
        // The timesheet_id from the hidden form field can also be used for an extra check if needed,
        // but the ID from the URL is the primary identifier here.
        // $formTimesheetId = (int)($_POST['timesheet_id'] ?? 0);
        // if ($formTimesheetId !== $timesheetId) { ... handle mismatch ... }


        if (empty($rejectionReason)) {
            $_SESSION['form_errors'] = ['rejection_reason' => 'A reason for rejection is required.'];
            // $_SESSION['form_data'] = $_POST; // Not strictly needed as only one field, but good for consistency
            header('Location: /supervisor/timesheets/reject-form/' . $timesheetId);
            exit;
        }

        try {
            $timesheet = Timesheet::findById($timesheetId);

            if (!$timesheet) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Timesheet not found for rejection.'];
                header('Location: /supervisor/timesheets/pending');
                exit;
            }

            // Security Check: Ensure timesheet belongs to supervised staff
            $staffUser = User::findById($timesheet->staff_user_id);
            if (!$staffUser || $staffUser->supervisor_id !== $supervisorId) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'You are not authorized to reject this timesheet.'];
                header('Location: /supervisor/timesheets/pending');
                exit;
            }

            // Check if timesheet is in a state that can be rejected
            $allowedStatusesForRejection = ['Pending', 'EditedBySupervisor', 'PendingStaffConfirmation', 'DisputedByStaff'];
            if (!in_array($timesheet->status, $allowedStatusesForRejection)) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'This timesheet cannot be rejected at its current status: ' . $timesheet->status];
                header('Location: /supervisor/timesheets/pending');
                exit;
            }

            $timesheet->status = 'Rejected';
            $timesheet->rejection_reason = $rejectionReason;
            $timesheet->approver_user_id = $supervisorId; // The supervisor is the one actioning it
            $timesheet->approved_at = null; // Clear any previous approval timestamp
            // edited_by_supervisor_id and edited_at would remain if it was an edited timesheet being rejected.
            // staff_dispute_reason would also remain if it was a disputed timesheet being rejected.

            if ($timesheet->save()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Timesheet (ID: '.$timesheetId.') rejected successfully.'];
                // TODO: Create notification for staff member
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to reject timesheet. Please try again.'];
            }
        } catch (Exception $e) {
            error_log("Error rejecting timesheet ID {$timesheetId} by supervisor ID {$supervisorId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred while rejecting the timesheet: ' . $e->getMessage()];
        }

        header('Location: /supervisor/timesheets/pending');
        exit;
    }
    public function edit(string $id): void
    {
        $this->enforceSupervisorAccess();
        $timesheetId = (int)$id;
        $supervisorId = $_SESSION['user_id'] ?? 0;

        try {
            $timesheet = Timesheet::findById($timesheetId); // Assumes findById fetches necessary details like staff_id

            if (!$timesheet) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Timesheet not found.'];
                header('Location: /supervisor/timesheets/pending');
                exit;
            }

            // Security Check: Ensure this timesheet's staff member is supervised by the current supervisor
            $staffUser = User::findById($timesheet->staff_user_id);
            if (!$staffUser || $staffUser->supervisor_id !== $supervisorId) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'You are not authorized to edit this timesheet.'];
                header('Location: /supervisor/timesheets/pending');
                exit;
            }

            // Fetch sites for dropdowns (assigned to the specific staff and all active sites)
            $assignedSites = Site::findAssignedToStaff($timesheet->staff_user_id);
            $allActiveSites = Site::findAllActive();

            View::render('supervisor/timesheets/edit', [
                'pageTitle' => 'Edit Timesheet for ' . htmlspecialchars($timesheet->staff_name ?? 'Staff'),
                'timesheet' => $timesheet,
                'assignedSites' => $assignedSites,
                'allActiveSites' => $allActiveSites,
                'form_data' => $_SESSION['form_data'] ?? (array)$timesheet, // Pre-fill with timesheet data or old form data
                'errors' => $_SESSION['form_errors'] ?? []
            ], 'app');
            unset($_SESSION['form_errors']);
            unset($_SESSION['form_data']);

        } catch (Exception $e) {
            error_log("Error loading timesheet edit form for ID {$timesheetId}: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not load timesheet for editing. ' . $e->getMessage()];
            header('Location: /supervisor/timesheets/pending');
            exit;
        }
    }
    public function update(string $id): void
    {
        $this->enforceSupervisorAccess();
        $timesheetId = (int)$id;
        $supervisorId = $_SESSION['user_id'] ?? 0;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /supervisor/timesheets/edit/' . $timesheetId);
            exit;
        }

        // Initialize $formData early in case of exceptions before it's fully assigned from $_POST
        $formData = $_POST;

        try {
            $timesheet = Timesheet::findById($timesheetId);

            if (!$timesheet) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Timesheet not found for update.'];
                header('Location: /supervisor/timesheets/pending');
                exit;
            }

            // Security Check: Ensure this timesheet's staff member is supervised by the current supervisor
            $staffUser = User::findById($timesheet->staff_user_id);
            if (!$staffUser || $staffUser->supervisor_id !== $supervisorId) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'You are not authorized to update this timesheet.'];
                header('Location: /supervisor/timesheets/pending');
                exit;
            }

            // $formData is already $_POST from above
            $errors = [];

            $isUnscheduled = isset($formData['is_unscheduled_shift']) && $formData['is_unscheduled_shift'] == '1';
            $selectedSiteId = null;

            if ($isUnscheduled) {
                if (empty($formData['unscheduled_site_id'])) {
                    $errors['unscheduled_site_id'] = 'Please select a site for the unscheduled shift.';
                } else {
                    $selectedSiteId = (int)$formData['unscheduled_site_id'];
                }
                if (empty(trim($formData['notes']))) {
                    $errors['notes'] = 'Notes are required for unscheduled shifts.';
                }
            } else {
                if (empty($formData['assigned_site_id'])) {
                    $errors['assigned_site_id'] = 'Please select an assigned site.';
                } else {
                    $selectedSiteId = (int)$formData['assigned_site_id'];
                }
            }

            if (empty($formData['shift_date'])) {
                $errors['shift_date'] = 'Shift date is required.';
            } else {
                $d = \DateTime::createFromFormat('Y-m-d', $formData['shift_date']);
                if (!$d || $d->format('Y-m-d') !== $formData['shift_date']) {
                    $errors['shift_date'] = 'Invalid date format (YYYY-MM-DD).';
                } elseif ($d > new \DateTime('now')) { // Allow today, but not future
                    $today = new \DateTime('now');
                    $today->setTime(0,0,0); // Compare dates only
                    if ($d > $today) {
                        $errors['shift_date'] = 'Shift date cannot be in the future.';
                    }
                }
            }

            if (empty($formData['hours_worked'])) {
                $errors['hours_worked'] = 'Hours worked are required.';
            } elseif (!is_numeric($formData['hours_worked']) || $formData['hours_worked'] <= 0 || $formData['hours_worked'] > 24) {
                $errors['hours_worked'] = 'Hours worked must be a positive number (max 24).';
            }

            if (!empty($errors)) {
                $_SESSION['form_errors'] = $errors;
                // Pass back original timesheet data mixed with current form data for sticky form
                // Fetch original timesheet data again to ensure $timesheet object is pristine for merge if needed
                $originalTimesheetDataForForm = (array)Timesheet::findById($timesheetId);
                $stickyFormData = array_merge($originalTimesheetDataForForm, $formData);
                $_SESSION['form_data'] = $stickyFormData;
                header('Location: /supervisor/timesheets/edit/' . $timesheetId);
                exit;
            }

            // Validation passed, update the timesheet object
            $originalHours = $timesheet->hours_worked; // Get original hours before updating the object
            $newHours = (float)$formData['hours_worked'];

            $timesheet->site_id = $selectedSiteId;
            $timesheet->shift_date = $formData['shift_date'];
            $timesheet->hours_worked = $newHours;
            $timesheet->is_unscheduled_shift = $isUnscheduled;
            $timesheet->notes = !empty($formData['notes']) ? trim($formData['notes']) : null;

            $timesheet->edited_by_supervisor_id = $supervisorId;
            $timesheet->edited_at = date('Y-m-d H:i:s');

            // ***** MODIFICATION FOR NEW WORKFLOW *****
            $timesheet->status = 'PendingStaffConfirmation';

            // Handle original_hours_worked logic
            if ($newHours != $originalHours) { // Only if hours actually changed
                if ($timesheet->original_hours_worked === null) {
                    // If it's the first time hours are edited, store the original hours
                    $timesheet->original_hours_worked = $originalHours;
                }
                // If original_hours_worked is already set, we keep that first original value.
                // Or, business logic might dictate updating it to the value *before this specific edit*.
                // For now, only setting it if it was previously null and hours changed.
            }


            if ($timesheet->save()) {
                unset($_SESSION['form_data']);
                unset($_SESSION['form_errors']);
                // ***** MODIFIED FLASH MESSAGE *****
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Timesheet updated. It now requires staff confirmation.'];
                header('Location: /supervisor/timesheets/pending');
            } else {
                $_SESSION['form_data'] = $formData;
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to update timesheet. Please try again.'];
                header('Location: /supervisor/timesheets/edit/' . $timesheetId);
            }

        } catch (Exception $e) {
            error_log("Error updating timesheet ID {$timesheetId}: " . $e->getMessage());
            $_SESSION['form_data'] = $formData; // Ensure $formData is available
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'An error occurred while updating timesheet: ' . $e->getMessage()];
            header('Location: /supervisor/timesheets/edit/' . $timesheetId);
        }
        exit;
    }
    /**
     * Display disputed timesheets for the supervisor's staff.
     */
    public function disputedList(): void
    {
        $this->enforceSupervisorAccess();
        $supervisorId = $_SESSION['user_id'] ?? 0;
        $disputedTimesheets = [];
        $errorMessage = null;

        if ($supervisorId <= 0) {
            // This case should ideally not happen if session and access control work
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'User session error. Please login again.'];
            header('Location: /login');
            exit;
        }

        try {
            $disputedTimesheets = Timesheet::findDisputedBySupervisor($supervisorId);
        } catch (Exception $e) {
            error_log("Error fetching disputed timesheets for supervisor ID {$supervisorId}: " . $e->getMessage());
            $errorMessage = "Could not load disputed timesheets at this time.";
        }

        View::render('supervisor/timesheets/disputed', [
            'pageTitle' => 'Disputed Timesheets',
            'timesheets' => $disputedTimesheets,
            'errorMessage' => $errorMessage
        ], 'app');
    }
    /**
     * Display a list of all timesheets for the supervisor's team, with filtering options.
     */
    public function listTeamTimesheets(): void
    {
        $this->enforceSupervisorAccess();
        $supervisorId = $_SESSION['user_id'] ?? 0;

        $teamMembers = [];
        $timesheets = [];
        $errorMessage = null;

        // Get filter values from GET request
        $filters = [
            'staff_id' => isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'status' => $_GET['status'] ?? null,
        ];

        // Basic validation for dates if provided
        if ($filters['date_from'] && !DateTime::createFromFormat('Y-m-d', $filters['date_from'])) {
            $filters['date_from'] = null; // Invalid, so ignore
            // Optionally set a flash error message about invalid date format
        }
        if ($filters['date_to'] && !DateTime::createFromFormat('Y-m-d', $filters['date_to'])) {
            $filters['date_to'] = null; // Invalid, so ignore
        }
        // Ensure date_to is not before date_from
        if ($filters['date_from'] && $filters['date_to'] && $filters['date_from'] > $filters['date_to']) {
            // Swap them or set an error; for now, let's just note it or you could set an error message.
            // For simplicity, the query can handle it, or you can clear one.
            // $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Start date cannot be after end date for filtering.'];
            // $filters['date_to'] = $filters['date_from']; // or nullify
        }


        if ($supervisorId <= 0) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'User session error. Please login again.'];
            header('Location: /login');
            exit;
        }

        try {
            $teamMembers = User::findStaffBySupervisorId($supervisorId);
            $timesheets = Timesheet::findAllBySupervisorTeam($supervisorId, $filters);
        } catch (Exception $e) {
            error_log("Error fetching team timesheets or staff for supervisor ID {$supervisorId}: " . $e->getMessage());
            $errorMessage = "Could not load team timesheet data at this time.";
        }

        // For the status filter dropdown
        $timesheetStatuses = ['Pending', 'Approved', 'Rejected', 'EditedBySupervisor', 'PendingStaffConfirmation', 'DisputedByStaff'];


        View::render('supervisor/timesheets/team_overview', [
            'pageTitle' => 'Team Timesheets Overview',
            'teamMembers' => $teamMembers,      // For the staff filter dropdown
            'timesheets' => $timesheets,         // The list of timesheets
            'filters' => $filters,             // To pre-fill filter form
            'timesheetStatuses' => $timesheetStatuses, // For status filter dropdown
            'errorMessage' => $errorMessage
        ], 'app');
    }
}