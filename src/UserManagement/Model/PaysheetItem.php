<?php

namespace App\UserManagement\Model;

use App\Core\Database\Connection;
use PDO;
use Exception;

class PaysheetItem
{
    public ?int $id = null;
    public int $paysheet_id;
    public int $timesheet_id;
    public float $hours_worked_snapshot; // New
    public float $pay_rate_snapshot;   // New
    public float $calculated_pay;

    // For display when joining - add as needed if findAllByPaysheetId joins them
    public ?string $staff_name = null;
    public ?string $site_name = null;
    public ?string $shift_date = null;


    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->paysheet_id = (int)($data['paysheet_id'] ?? 0);
        $this->timesheet_id = (int)($data['timesheet_id'] ?? 0);
        $this->hours_worked_snapshot = isset($data['hours_worked_snapshot']) ? (float)$data['hours_worked_snapshot'] : 0.0; // New
        $this->pay_rate_snapshot = isset($data['pay_rate_snapshot']) ? (float)$data['pay_rate_snapshot'] : 0.0;     // New
        $this->calculated_pay = isset($data['calculated_pay']) ? (float)$data['calculated_pay'] : 0.0;

        // For joined data
        $this->staff_name = $data['staff_name'] ?? null;
        $this->site_name = $data['site_name'] ?? null;
        $this->shift_date = $data['shift_date'] ?? null;
    }

    public function save(): bool // Primarily for single item, not used by current paysheet generation
    {
        $db = Connection::getInstance();
        if ($this->id !== null) {
            throw new Exception("Updating existing paysheet items not directly supported. Recreate if necessary.");
        }

        $sql = "INSERT INTO paysheet_items (paysheet_id, timesheet_id, hours_worked_snapshot, pay_rate_snapshot, calculated_pay) 
                VALUES (:paysheet_id, :timesheet_id, :hours_worked_snapshot, :pay_rate_snapshot, :calculated_pay)";
        $stmt = $db->prepare($sql);

        $stmt->bindParam(':paysheet_id', $this->paysheet_id, PDO::PARAM_INT);
        $stmt->bindParam(':timesheet_id', $this->timesheet_id, PDO::PARAM_INT);
        $stmt->bindParam(':hours_worked_snapshot', $this->hours_worked_snapshot); // New
        $stmt->bindParam(':pay_rate_snapshot', $this->pay_rate_snapshot);     // New
        $stmt->bindParam(':calculated_pay', $this->calculated_pay);

        $success = $stmt->execute();
        if ($success) {
            $this->id = (int)$db->lastInsertId();
        }
        return $success;
    }

    public static function saveBatch(int $paysheetId, array $itemsData): bool
    {
        if (empty($itemsData)) {
            return true;
        }
        $db = Connection::getInstance();
        try {
            $db->beginTransaction();
            $sql = "INSERT INTO paysheet_items (paysheet_id, timesheet_id, hours_worked_snapshot, pay_rate_snapshot, calculated_pay) 
                    VALUES (:paysheet_id, :timesheet_id, :hours_worked_snapshot, :pay_rate_snapshot, :calculated_pay)";
            $stmt = $db->prepare($sql);

            foreach ($itemsData as $item) {
                $stmt->bindParam(':paysheet_id', $paysheetId, PDO::PARAM_INT);
                $stmt->bindParam(':timesheet_id', $item['timesheet_id'], PDO::PARAM_INT);
                $stmt->bindParam(':hours_worked_snapshot', $item['hours_worked_snapshot']); // New
                $stmt->bindParam(':pay_rate_snapshot', $item['pay_rate_snapshot']);         // New
                $stmt->bindParam(':calculated_pay', $item['calculated_pay']);
                if (!$stmt->execute()) {
                    $db->rollBack();
                    return false;
                }
            }
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error in PaysheetItem::saveBatch for Paysheet ID {$paysheetId}: " . $e->getMessage());
            throw $e;
        }
    }

    // Method to fetch items for a specific paysheet with details for display
    /**
     * @param int $paysheetId
     * @return PaysheetItem[]
     * @throws Exception
     */
    public static function findAllByPaysheetId(int $paysheetId): array
    {
        $db = Connection::getInstance();
        $sql = "SELECT 
                    pi.*, 
                    t.shift_date, 
                    s.site_name, 
                    CONCAT(u.first_name, ' ', u.last_name) as staff_name
                FROM paysheet_items pi
                JOIN timesheets t ON pi.timesheet_id = t.id
                JOIN users u ON t.staff_user_id = u.id
                JOIN sites s ON t.site_id = s.id
                WHERE pi.paysheet_id = :paysheet_id
                ORDER BY u.last_name, u.first_name, t.shift_date";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':paysheet_id', $paysheetId, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = new self($row); // Constructor will map all fields including joined ones
        }
        return $items;
    }
}