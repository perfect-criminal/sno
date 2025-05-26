<?php

namespace App\UserManagement\Model; // Assuming you're keeping core models here

use App\Core\Database\Connection;
use PDO;
use Exception;

class Paysheet
{
    public ?int $id = null;
    public int $supervisor_user_id;
    public string $pay_period_start_date; // YYYY-MM-DD
    public string $pay_period_end_date;   // YYYY-MM-DD
    public string $status = 'Pending Payroll'; // enum('Pending Payroll','Review','Approved','Processed')
    public ?string $submitted_at = null;
    public ?float $total_hours_amount = null; // DECIMAL(12,2)
    public ?int $reviewed_by_payroll_id = null;
    public ?string $review_remarks = null;
    public ?int $approved_by_payroll_id = null;
    public ?string $approved_at = null; // Timestamp for payroll approval
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $deleted_at = null; // If you decide to soft delete paysheets

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
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
        $this->deleted_at = $data['deleted_at'] ?? null;
    }

    public function save(): bool
    {
        $db = Connection::getInstance();
        $now = date('Y-m-d H:i:s');

        if ($this->id === null) { // Create new Paysheet
            $this->submitted_at = $this->submitted_at ?? $now; // Supervisor submits it now
            $this->created_at = $now;
            $this->updated_at = $now;
            $this->status = $this->status ?: 'Pending Payroll';

            $sql = "INSERT INTO paysheets (supervisor_user_id, pay_period_start_date, pay_period_end_date, status, submitted_at, total_hours_amount, created_at, updated_at) 
                    VALUES (:supervisor_user_id, :pay_period_start_date, :pay_period_end_date, :status, :submitted_at, :total_hours_amount, :created_at, :updated_at)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':created_at', $this->created_at, PDO::PARAM_STR);
        } else { // Update existing Paysheet
            $this->updated_at = $now;
            $sql = "UPDATE paysheets SET 
                        supervisor_user_id = :supervisor_user_id, 
                        pay_period_start_date = :pay_period_start_date, 
                        pay_period_end_date = :pay_period_end_date, 
                        status = :status, 
                        submitted_at = :submitted_at, /* Might not update this often */
                        total_hours_amount = :total_hours_amount,
                        reviewed_by_payroll_id = :reviewed_by_payroll_id,
                        review_remarks = :review_remarks,
                        approved_by_payroll_id = :approved_by_payroll_id,
                        approved_at = :approved_at,
                        updated_at = :updated_at,
                        deleted_at = :deleted_at /* For soft delete */
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
        $stmt->bindParam(':submitted_at', $this->submitted_at, PDO::PARAM_STR);
        $stmt->bindValue(':total_hours_amount', $this->total_hours_amount, $this->total_hours_amount === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':updated_at', $this->updated_at, PDO::PARAM_STR);

        $success = $stmt->execute();
        if ($success && $this->id === null) {
            $this->id = (int)$db->lastInsertId();
        }
        return $success;
    }
    /**
     * Find all paysheets created by a specific supervisor.
     * Orders by submitted_at descending.
     *
     * @param int $supervisorId The ID of the supervisor.
     * @return Paysheet[] An array of Paysheet objects.
     * @throws Exception
     */
    public static function findAllBySupervisorId(int $supervisorId): array
    {
        if ($supervisorId <= 0) {
            return [];
        }

        $db = Connection::getInstance();
        try {
            $sql = "SELECT * FROM paysheets 
                    WHERE supervisor_user_id = :supervisor_id 
                      AND deleted_at IS NULL -- Assuming paysheets can be soft-deleted later
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
        } catch (Exception $e) {
            error_log("Error in Paysheet::findAllBySupervisorId for supervisor ID {$supervisorId}: " . $e->getMessage());
            throw new Exception("Database query failed while fetching supervisor paysheets. " . $e->getMessage(), 0, $e);
        }
    }

    // You might also want a findById method for viewing details later
    public static function findById(int $id): ?Paysheet
    {
        $db = Connection::getInstance();
        try {
            $sql = "SELECT * FROM paysheets WHERE id = :id AND deleted_at IS NULL LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $paysheetData = $stmt->fetch(PDO::FETCH_ASSOC);
            return $paysheetData ? new self($paysheetData) : null;
        } catch (Exception $e) {
            error_log("Error in Paysheet::findById for ID {$id}: " . $e->getMessage());
            throw new Exception("Database query failed while fetching paysheet by ID. " . $e->getMessage(), 0, $e);
        }
    }
}