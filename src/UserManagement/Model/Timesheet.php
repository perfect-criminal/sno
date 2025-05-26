<?php

namespace App\UserManagement\Model;

use App\Core\Database\Connection;
use PDO;
use Exception;

class Timesheet
{
    public ?int $id = null;
    public int $staff_user_id;
    public int $site_id;
    public string $shift_date; // YYYY-MM-DD
    public float $hours_worked;
    public bool $is_unscheduled_shift = false;
    public ?string $notes = null;
    public string $status = 'Pending';
    public ?string $submitted_at = null; // Set on new record
    public ?int $approver_user_id = null;
    public ?string $approved_at = null;
    public ?string $rejection_reason = null;
    public ?int $edited_by_supervisor_id = null;
    public ?string $edited_at = null;        // Set when supervisor edits
    public ?float $original_hours_worked = null;
    public ?string $deleted_at = null;

    public ?string $site_name = null;
    public ?string $staff_name = null;
    public ?string $staff_dispute_reason = null;


    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->staff_user_id = (int)($data['staff_user_id'] ?? 0);
        $this->site_id = (int)($data['site_id'] ?? 0);
        $this->shift_date = $data['shift_date'] ?? '';
        $this->hours_worked = isset($data['hours_worked']) ? (float)$data['hours_worked'] : 0.0;
        $this->is_unscheduled_shift = isset($data['is_unscheduled_shift']) ? (bool)$data['is_unscheduled_shift'] : false;
        $this->notes = $data['notes'] ?? null;
        $this->status = $data['status'] ?? 'Pending';
        $this->submitted_at = $data['submitted_at'] ?? null;
        $this->approver_user_id = isset($data['approver_user_id']) ? (int)$data['approver_user_id'] : null;
        $this->approved_at = $data['approved_at'] ?? null;
        $this->rejection_reason = $data['rejection_reason'] ?? null;
        $this->staff_dispute_reason = $data['staff_dispute_reason'] ?? null;
        $this->edited_by_supervisor_id = isset($data['edited_by_supervisor_id']) ? (int)$data['edited_by_supervisor_id'] : null;
        $this->edited_at = $data['edited_at'] ?? null;
        $this->original_hours_worked = isset($data['original_hours_worked']) ? (float)$data['original_hours_worked'] : null;
        $this->deleted_at = $data['deleted_at'] ?? null;
        $this->site_name = $data['site_name'] ?? null;
        $this->staff_name = $data['staff_name'] ?? null;
    }

    public function save(): bool
    {
        $db = Connection::getInstance();
        $nowForActions = date('Y-m-d H:i:s');
        $isUnscheduledShiftInt = (int)$this->is_unscheduled_shift;

        if ($this->id === null) { // Creating a new timesheet (INSERT)
            $this->submitted_at = $this->submitted_at ?? $nowForActions;
            $this->status = $this->status ?: 'Pending';

            $sql = "INSERT INTO timesheets 
                        (staff_user_id, site_id, shift_date, hours_worked, is_unscheduled_shift, notes, status, submitted_at) 
                    VALUES 
                        (:staff_user_id, :site_id, :shift_date, :hours_worked, :is_unscheduled_shift, :notes, :status, :submitted_at)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':staff_user_id', $this->staff_user_id, PDO::PARAM_INT);
            $stmt->bindParam(':submitted_at', $this->submitted_at, PDO::PARAM_STR); // This is set when object is prepared for INSERT

        } else { // Updating an existing timesheet (UPDATE)
            // For a re-submission by staff, the controller will have updated submitted_at.
            // For supervisor edits or approvals/rejections, other specific timestamps are set by the controller.

            $sql = "UPDATE timesheets SET 
                        site_id = :site_id, 
                        shift_date = :shift_date, 
                        hours_worked = :hours_worked, 
                        is_unscheduled_shift = :is_unscheduled_shift, 
                        notes = :notes, 
                        status = :status, 
                        
                        -- Fields that might be set or cleared by actions:
                        submitted_at = :submitted_at, -- This will be updated on staff re-submission
                        approver_user_id = :approver_user_id,
                        approved_at = :approved_at,
                        rejection_reason = :rejection_reason,
                        staff_dispute_reason = :staff_dispute_reason,
                        edited_by_supervisor_id = :edited_by_supervisor_id,
                        edited_at = :edited_at,
                        original_hours_worked = :original_hours_worked
                        -- No generic 'updated_at' column in this table as per schema
                    WHERE id = :id AND deleted_at IS NULL";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

            // Bind fields specific to update that might be null or set by actions
            // `submitted_at` is now part of update for re-submissions
            $stmt->bindParam(':submitted_at', $this->submitted_at, PDO::PARAM_STR);


            if ($this->edited_by_supervisor_id === null) { $stmt->bindValue(':edited_by_supervisor_id', null, PDO::PARAM_NULL); }
            else { $stmt->bindParam(':edited_by_supervisor_id', $this->edited_by_supervisor_id, PDO::PARAM_INT); }

            if ($this->edited_at === null) { $stmt->bindValue(':edited_at', null, PDO::PARAM_NULL); }
            else { $stmt->bindParam(':edited_at', $this->edited_at, PDO::PARAM_STR); }

            if ($this->original_hours_worked === null) { $stmt->bindValue(':original_hours_worked', null, PDO::PARAM_NULL); }
            else { $stmt->bindParam(':original_hours_worked', $this->original_hours_worked); }

            if ($this->approver_user_id === null) { $stmt->bindValue(':approver_user_id', null, PDO::PARAM_NULL); }
            else { $stmt->bindParam(':approver_user_id', $this->approver_user_id, PDO::PARAM_INT); }

            if ($this->approved_at === null) { $stmt->bindValue(':approved_at', null, PDO::PARAM_NULL); }
            else { $stmt->bindParam(':approved_at', $this->approved_at, PDO::PARAM_STR); }

            if ($this->rejection_reason === null) { $stmt->bindValue(':rejection_reason', null, PDO::PARAM_NULL); }
            else { $stmt->bindParam(':rejection_reason', $this->rejection_reason, PDO::PARAM_STR); }

            if ($this->staff_dispute_reason === null) { $stmt->bindValue(':staff_dispute_reason', null, PDO::PARAM_NULL); }
            else { $stmt->bindParam(':staff_dispute_reason', $this->staff_dispute_reason, PDO::PARAM_STR); }
        }

        // Common parameters for both INSERT and UPDATE
        $stmt->bindParam(':site_id', $this->site_id, PDO::PARAM_INT);
        $stmt->bindParam(':shift_date', $this->shift_date, PDO::PARAM_STR);
        $stmt->bindParam(':hours_worked', $this->hours_worked);
        $stmt->bindParam(':is_unscheduled_shift', $isUnscheduledShiftInt, PDO::PARAM_INT);

        if ($this->notes === null) { $stmt->bindValue(':notes', null, PDO::PARAM_NULL); }
        else { $stmt->bindParam(':notes', $this->notes, PDO::PARAM_STR); }

        $stmt->bindParam(':status', $this->status, PDO::PARAM_STR);

        $success = $stmt->execute();
        if ($success && $this->id === null) { // If it was an INSERT
            $this->id = (int)$db->lastInsertId();
        }
        return $success;
    }

    // --- findByStaffId(), findById(), findPendingBySupervisor() methods from before ---
    // (Ensure these are present and correct as previously provided)
    public static function findByStaffId(int $staffId, array $options = []): array
    {
        if ($staffId <= 0) { return []; }
        $db = Connection::getInstance();
        try {
            $sql = "SELECT t.*, s.site_name, c.company_name 
                    FROM timesheets t
                    INNER JOIN sites s ON t.site_id = s.id
                    LEFT JOIN companies c ON s.company_id = c.id AND c.deleted_at IS NULL
                    WHERE t.staff_user_id = :staff_id AND t.deleted_at IS NULL
                    ORDER BY t.shift_date DESC, t.submitted_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':staff_id', $staffId, PDO::PARAM_INT);
            $stmt->execute();
            $timesheetsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $timesheets = [];
            foreach ($timesheetsData as $timesheetData) {
                $timesheets[] = new self($timesheetData);
            }
            return $timesheets;
        } catch (Exception $e) {
            error_log("Error in Timesheet::findByStaffId for staff ID {$staffId}: " . $e->getMessage());
            throw new Exception("Database query failed while fetching staff timesheets. " . $e->getMessage(), 0, $e);
        }
    }

    public static function findById(int $id): ?Timesheet
    {
        $db = Connection::getInstance();
        try {
            $sql = "SELECT t.*, s.site_name, c.company_name, 
                           CONCAT(u.first_name, ' ', u.last_name) as staff_name
                    FROM timesheets t
                    INNER JOIN sites s ON t.site_id = s.id
                    LEFT JOIN companies c ON s.company_id = c.id AND c.deleted_at IS NULL
                    INNER JOIN users u ON t.staff_user_id = u.id 
                    WHERE t.id = :id AND t.deleted_at IS NULL
                    LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $timesheetData = $stmt->fetch(PDO::FETCH_ASSOC);
            return $timesheetData ? new self($timesheetData) : null;
        } catch (Exception $e) {
            error_log("Error in Timesheet::findById for ID {$id}: " . $e->getMessage());
            throw new Exception("Database query failed while fetching timesheet by ID. " . $e->getMessage(), 0, $e);
        }
    }

    public static function findPendingBySupervisor(int $supervisorId): array
    {
        if ($supervisorId <= 0) { return []; }
        $db = Connection::getInstance();
        try {
            $sql = "SELECT t.*, s.site_name, CONCAT(u.first_name, ' ', u.last_name) AS staff_name
                    FROM timesheets t
                    INNER JOIN users u ON t.staff_user_id = u.id
                    INNER JOIN sites s ON t.site_id = s.id
                    WHERE u.supervisor_id = :supervisor_id
                      AND t.status = 'Pending'
                      AND t.deleted_at IS NULL AND u.deleted_at IS NULL AND s.deleted_at IS NULL
                    ORDER BY t.submitted_at ASC, t.shift_date ASC";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':supervisor_id', $supervisorId, PDO::PARAM_INT);
            $stmt->execute();
            $timesheetsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $pendingTimesheets = [];
            foreach ($timesheetsData as $timesheetData) {
                $pendingTimesheets[] = new self($timesheetData);
            }
            return $pendingTimesheets;
        } catch (Exception $e) {
            error_log("Error in Timesheet::findPendingBySupervisor for supervisor ID {$supervisorId}: " . $e->getMessage());
            throw new Exception("Database query failed: " . $e->getMessage(), 0, $e);
        }
    }
    /**
     * Find all timesheets awaiting staff confirmation for a specific staff member.
     *
     * @param int $staffId The ID of the staff member.
     * @return Timesheet[] An array of Timesheet objects.
     * @throws Exception
     */
    public static function findPendingConfirmationByStaff(int $staffId): array
    {
        if ($staffId <= 0) {
            return [];
        }

        $db = Connection::getInstance();
        try {
            $sql = "SELECT t.*, s.site_name, c.company_name 
                    FROM timesheets t
                    INNER JOIN sites s ON t.site_id = s.id
                    LEFT JOIN companies c ON s.company_id = c.id AND c.deleted_at IS NULL
                    WHERE t.staff_user_id = :staff_id 
                      AND t.status = 'PendingStaffConfirmation'
                      AND t.deleted_at IS NULL 
                    ORDER BY t.shift_date ASC, t.edited_at DESC"; // Order by edit time

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':staff_id', $staffId, PDO::PARAM_INT);
            $stmt->execute();

            $timesheetsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $timesheets = [];
            foreach ($timesheetsData as $timesheetData) {
                $timesheets[] = new self($timesheetData);
            }
            return $timesheets;
        } catch (Exception $e) {
            error_log("Error in Timesheet::findPendingConfirmationByStaff for staff ID {$staffId}: " . $e->getMessage());
            throw new Exception("Database query failed while fetching timesheets pending confirmation. " . $e->getMessage(), 0, $e);
        }
    }
    /**
     * Find all 'DisputedByStaff' timesheets for staff assigned to a specific supervisor.
     *
     * @param int $supervisorId The ID of the supervisor.
     * @return Timesheet[] An array of Timesheet objects.
     * @throws Exception
     */
    public static function findDisputedBySupervisor(int $supervisorId): array
    {
        if ($supervisorId <= 0) {
            return [];
        }

        $db = Connection::getInstance();
        try {
            $sql = "SELECT t.*, s.site_name, CONCAT(u.first_name, ' ', u.last_name) AS staff_name
                    FROM timesheets t
                    INNER JOIN users u ON t.staff_user_id = u.id
                    INNER JOIN sites s ON t.site_id = s.id
                    WHERE u.supervisor_id = :supervisor_id
                      AND t.status = 'DisputedByStaff'
                      AND t.deleted_at IS NULL
                      AND u.deleted_at IS NULL 
                      AND s.deleted_at IS NULL
                    ORDER BY t.edited_at DESC, t.shift_date ASC"; // Order by when it was last edited (disputed)

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':supervisor_id', $supervisorId, PDO::PARAM_INT);
            $stmt->execute();

            $timesheetsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $disputedTimesheets = [];
            foreach ($timesheetsData as $timesheetData) {
                $disputedTimesheets[] = new self($timesheetData);
            }
            return $disputedTimesheets;
        } catch (Exception $e) {
            error_log("Error in Timesheet::findDisputedBySupervisor for supervisor ID {$supervisorId}: " . $e->getMessage());
            throw new Exception("Database query failed while fetching disputed timesheets: " . $e->getMessage(), 0, $e);
        }
    }
/**
* Find 'Approved' timesheets for staff assigned to a supervisor within a date range.
* Includes staff pay_rate for calculations.
* Excludes timesheets already linked in paysheet_items.
*
* @param int $supervisorId
* @param string $startDate YYYY-MM-DD
* @param string $endDate YYYY-MM-DD
* @return array Array of timesheet data suitable for paysheet generation
* @throws Exception
*/
    public static function findApprovedForPaysheet(int $supervisorId, string $startDate, string $endDate): array
    {
        if ($supervisorId <= 0) {
            return [];
        }
        $db = Connection::getInstance();
        try {
            // Fetch timesheets that are 'Approved', belong to staff supervised by $supervisorId,
            // fall within the date range, AND are not already in paysheet_items.
            // Also fetch the staff user's pay_rate.
            $sql = "SELECT t.id as timesheet_id, t.staff_user_id, t.hours_worked, u.pay_rate
                    FROM timesheets t
                    INNER JOIN users u ON t.staff_user_id = u.id
                    WHERE u.supervisor_id = :supervisor_id
                      AND t.status = 'Approved'
                      AND t.shift_date BETWEEN :start_date AND :end_date
                      AND t.deleted_at IS NULL
                      AND u.deleted_at IS NULL
                      AND NOT EXISTS (SELECT 1 FROM paysheet_items pi WHERE pi.timesheet_id = t.id)
                    ORDER BY t.staff_user_id, t.shift_date ASC";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':supervisor_id', $supervisorId, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in Timesheet::findApprovedForPaysheet (Sup ID {$supervisorId}): " . $e->getMessage());
            throw new Exception("DB query failed while fetching approved timesheets for paysheet. " . $e->getMessage(), 0, $e);
        }
    }
}