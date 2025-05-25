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
}