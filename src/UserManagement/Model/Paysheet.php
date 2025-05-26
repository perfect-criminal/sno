<?php

namespace App\UserManagement\Model;

use App\Core\Database\Connection;
use PDO;
use Exception;

class Paysheet
{
    public ?int $id = null;
    public int $supervisor_user_id;
    public string $pay_period_start_date;
    public string $pay_period_end_date;
    public string $status = 'Pending Payroll';
    public ?string $submitted_at = null; // Set on creation
    public ?float $total_hours_amount = null;
    public ?int $reviewed_by_payroll_id = null;
    public ?string $review_remarks = null;
    public ?int $approved_by_payroll_id = null;
    public ?string $approved_at = null;    // Timestamp for payroll approval
    public ?string $deleted_at = null;    // For soft delete

    // Properties for joined data (not direct table columns)
    public ?string $supervisor_name = null;
    public ?string $payroll_reviewer_name = null;

    // Removed: public ?string $created_at = null;
    // Removed: public ?string $updated_at = null;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->supervisor_user_id = (int)($data['supervisor_user_id'] ?? 0);
        $this->pay_period_start_date = $data['pay_period_start_date'] ?? '';
        $this->pay_period_end_date = $data['pay_period_end_date'] ?? '';
        $this->status = $data['status'] ?? 'Pending Payroll';
        $this->submitted_at = $data['submitted_at'] ?? null;
        $this->total_hours_amount = isset($data['total_hours_amount']) ? (float)$data['total_hours_amount'] : null;
        $this->reviewed_by_payroll_id = isset($data['reviewed_by_payroll_id']) ? (int)$data['reviewed_by_payroll_id'] : null;
        $this->review_remarks = $data['review_remarks'] ?? null;
        $this->approved_by_payroll_id = isset($data['approved_by_payroll_id']) ? (int)$data['approved_by_payroll_id'] : null;
        $this->approved_at = $data['approved_at'] ?? null;
        $this->deleted_at = $data['deleted_at'] ?? null;

        // For joined data
        $this->supervisor_name = $data['supervisor_name'] ?? null;
        $this->payroll_reviewer_name = $data['payroll_reviewer_name'] ?? null;
    }

    public function save(): bool
    {
        $db = Connection::getInstance();
        $nowForActions = date('Y-m-d H:i:s'); // For specific action timestamps

        if ($this->id === null) { // Create new Paysheet
            $this->submitted_at = $this->submitted_at ?? $nowForActions; // Supervisor submits it now
            $this->status = $this->status ?: 'Pending Payroll';

            // No generic created_at/updated_at in INSERT
            $sql = "INSERT INTO paysheets (supervisor_user_id, pay_period_start_date, pay_period_end_date, status, submitted_at, total_hours_amount) 
                    VALUES (:supervisor_user_id, :pay_period_start_date, :pay_period_end_date, :status, :submitted_at, :total_hours_amount)";
            $stmt = $db->prepare($sql);
            // No created_at to bind
        } else { // Update existing Paysheet
            // No generic updated_at to set on the object or bind for the timesheets table.
            // Specific action timestamps like approved_at are handled.
            $sql = "UPDATE paysheets SET 
                        supervisor_user_id = :supervisor_user_id, 
                        pay_period_start_date = :pay_period_start_date, 
                        pay_period_end_date = :pay_period_end_date, 
                        status = :status, 
                        submitted_at = :submitted_at, 
                        total_hours_amount = :total_hours_amount,
                        reviewed_by_payroll_id = :reviewed_by_payroll_id,
                        review_remarks = :review_remarks,
                        approved_by_payroll_id = :approved_by_payroll_id,
                        approved_at = :approved_at,
                        deleted_at = :deleted_at 
                    WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

            // Bind nullable fields for update
            $stmt->bindValue(':reviewed_by_payroll_id', $this->reviewed_by_payroll_id, $this->reviewed_by_payroll_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':review_remarks', $this->review_remarks, $this->review_remarks === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':approved_by_payroll_id', $this->approved_by_payroll_id, $this->approved_by_payroll_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':approved_at', $this->approved_at, $this->approved_at === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':deleted_at', $this->deleted_at, $this->deleted_at === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        }

        $stmt->bindParam(':supervisor_user_id', $this->supervisor_user_id, PDO::PARAM_INT);
        $stmt->bindParam(':pay_period_start_date', $this->pay_period_start_date, PDO::PARAM_STR);
        $stmt->bindParam(':pay_period_end_date', $this->pay_period_end_date, PDO::PARAM_STR);
        $stmt->bindParam(':status', $this->status, PDO::PARAM_STR);
        $stmt->bindParam(':submitted_at', $this->submitted_at, PDO::PARAM_STR); // submitted_at is relevant for both insert and potentially update if resubmitted
        $stmt->bindValue(':total_hours_amount', $this->total_hours_amount, $this->total_hours_amount === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // No generic updated_at to bind

        $success = $stmt->execute();
        if ($success && $this->id === null) {
            $this->id = (int)$db->lastInsertId();
        }
        return $success;
    }

    public static function findAllBySupervisorId(int $supervisorId): array
    {
        if ($supervisorId <= 0) {
            return [];
        }
        $db = Connection::getInstance();
        try {
            // This query does not need supervisor_name as it's for the supervisor's OWN list.
            $sql = "SELECT * FROM paysheets 
                    WHERE supervisor_user_id = :supervisor_id 
                      AND deleted_at IS NULL 
                    ORDER BY submitted_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':supervisor_id', $supervisorId, PDO::PARAM_INT);
            $stmt->execute();
            $paysheetsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $paysheets = [];
            foreach ($paysheetsData as $paysheetData) {
                $paysheets[] = new self($paysheetData);
            }
            return $paysheets;
        } catch (Exception $e) { /* ... error logging and throw ... */
        }
    }

    public static function findById(int $id): ?Paysheet
    {
        $db = Connection::getInstance();
        try {
            // Fetch supervisor name for display on detail views
            $sql = "SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) AS supervisor_name 
                    FROM paysheets p
                    LEFT JOIN users u ON p.supervisor_user_id = u.id 
                    WHERE p.id = :id AND p.deleted_at IS NULL 
                    LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $paysheetData = $stmt->fetch(PDO::FETCH_ASSOC);
            return $paysheetData ? new self($paysheetData) : null;
        } catch (Exception $e) { /* ... error logging and throw ... */
        }
    }

    public static function findPendingPayrollReview(): array
    {
        $db = Connection::getInstance();
        try {
            // Fetches supervisor name because payroll team sees paysheets from multiple supervisors
            $sql = "SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) AS supervisor_name 
                    FROM paysheets p
                    INNER JOIN users u ON p.supervisor_user_id = u.id
                    WHERE p.status = 'Pending Payroll' 
                      AND p.deleted_at IS NULL
                    ORDER BY p.submitted_at ASC";
            $stmt = $db->query($sql); // No parameters, direct query is fine
            $paysheetsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $paysheets = [];
            foreach ($paysheetsData as $paysheetData) {
                $paysheets[] = new self($paysheetData);
            }
            return $paysheets;
        } catch (Exception $e) { /* ... error logging and throw ... */
        }
    }

    public static function findInReviewBySupervisorId(int $supervisorId): array
    {
        if ($supervisorId <= 0) {
            return [];
        }
        $db = Connection::getInstance();
        try {
            // Fetches payroll reviewer name
            $sql = "SELECT p.*, CONCAT(u_payroll.first_name, ' ', u_payroll.last_name) AS payroll_reviewer_name
                    FROM paysheets p
                    LEFT JOIN users u_payroll ON p.reviewed_by_payroll_id = u_payroll.id 
                    WHERE p.supervisor_user_id = :supervisor_id
                      AND p.status = 'Review'
                      AND p.deleted_at IS NULL
                    ORDER BY p.updated_at DESC"; // Assuming updated_at was a typo and meant to be when payroll reviewed it (e.g. submitted_at or another timestamp)
            // For now, this is from your existing code. The paysheets table does not have a generic updated_at.
            // Let's order by p.submitted_at DESC or p.id DESC for now as there's no updated_at
            $stmt = $db->prepare(str_replace("ORDER BY p.updated_at DESC", "ORDER BY p.submitted_at DESC", $sql)); // Quick fix for ordering
            $stmt->bindParam(':supervisor_id', $supervisorId, PDO::PARAM_INT);
            $stmt->execute();
            $paysheetsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $reviewPaysheets = [];
            foreach ($paysheetsData as $paysheetData) {
                $reviewPaysheets[] = new self($paysheetData);
            }
            return $reviewPaysheets;
        } catch (Exception $e) { /* ... error logging and throw ... */
        }
    }
    /**
     * Soft delete a paysheet by setting the deleted_at timestamp.
     * Also deletes associated paysheet items.
     *
     * @param int $id The ID of the paysheet to soft delete.
     * @return bool True on success, false on failure.
     * @throws Exception
     */
    public static function softDeleteWithItems(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $db = Connection::getInstance();
        try {
            $db->beginTransaction();

            // Step 1: Delete associated paysheet items
            if (!PaysheetItem::deleteByPaysheetId($id)) {
                $db->rollBack();
                error_log("Failed to delete paysheet items for Paysheet ID {$id} during soft delete.");
                return false;
            }

            // Step 2: Soft delete the paysheet itself
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("UPDATE paysheets SET deleted_at = :deleted_at, status = 'Cancelled' WHERE id = :id AND deleted_at IS NULL");
            // Optionally change status to 'Cancelled' or similar
            $stmt->bindParam(':deleted_at', $now, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                $db->rollBack();
                error_log("Failed to soft delete paysheet for ID {$id}.");
                return false;
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error in Paysheet::softDeleteWithItems for ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }
}
