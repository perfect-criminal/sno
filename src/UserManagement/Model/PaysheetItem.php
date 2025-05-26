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
    public float $hours_worked_snapshot;
    public float $pay_rate_snapshot;
    public float $calculated_pay;

    public ?string $staff_name = null;
    public ?string $site_name = null;
    public ?string $shift_date = null;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->paysheet_id = (int)($data['paysheet_id'] ?? 0);
        $this->timesheet_id = (int)($data['timesheet_id'] ?? 0);
        $this->hours_worked_snapshot = isset($data['hours_worked_snapshot']) ? (float)$data['hours_worked_snapshot'] : 0.0;
        $this->pay_rate_snapshot = isset($data['pay_rate_snapshot']) ? (float)$data['pay_rate_snapshot'] : 0.0;
        $this->calculated_pay = isset($data['calculated_pay']) ? (float)$data['calculated_pay'] : 0.0;
        $this->staff_name = $data['staff_name'] ?? null;
        $this->site_name = $data['site_name'] ?? null;
        $this->shift_date = $data['shift_date'] ?? null;
    }

    public function save(): bool
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
        $stmt->bindParam(':hours_worked_snapshot', $this->hours_worked_snapshot);
        $stmt->bindParam(':pay_rate_snapshot', $this->pay_rate_snapshot);
        $stmt->bindParam(':calculated_pay', $this->calculated_pay);

        $success = $stmt->execute();
        if ($success) {
            $this->id = (int)$db->lastInsertId();
        }
        return $success;
    }

    /**
     * Create multiple paysheet items.
     * This method EXPECTS to be run within an existing transaction from the calling code
     * if atomicity with other operations is required.
     *
     * @param int $paysheetId
     * @param array $itemsData Array of arrays, each sub-array with item data
     * @return bool True if all items saved, throws Exception on failure.
     * @throws Exception
     */
    public static function saveBatch(int $paysheetId, array $itemsData): bool
    {
        if (empty($itemsData)) {
            return true; // Nothing to save
        }
        $db = Connection::getInstance();

        $sql = "INSERT INTO paysheet_items (paysheet_id, timesheet_id, hours_worked_snapshot, pay_rate_snapshot, calculated_pay) 
                VALUES (:paysheet_id, :timesheet_id, :hours_worked_snapshot, :pay_rate_snapshot, :calculated_pay)";
        $stmt = $db->prepare($sql);

        // The try-catch here is for general SQL execution errors within the loop,
        // not for transaction management.
        try {
            foreach ($itemsData as $item) {
                // Basic check for required keys in $item to avoid notices
                if (!isset($item['timesheet_id'], $item['hours_worked_snapshot'], $item['pay_rate_snapshot'], $item['calculated_pay'])) {
                    // If an item is malformed, we should probably stop and let the outer transaction roll back.
                    throw new Exception("Malformed data for a paysheet item. Paysheet ID: {$paysheetId}");
                }

                $stmt->bindParam(':paysheet_id', $paysheetId, PDO::PARAM_INT);
                $stmt->bindParam(':timesheet_id', $item['timesheet_id'], PDO::PARAM_INT);
                $stmt->bindParam(':hours_worked_snapshot', $item['hours_worked_snapshot']);
                $stmt->bindParam(':pay_rate_snapshot', $item['pay_rate_snapshot']);
                $stmt->bindParam(':calculated_pay', $item['calculated_pay']);

                if (!$stmt->execute()) {
                    // If one execute fails, throw an exception.
                    // The calling method's (controller's) transaction will handle the rollback.
                    throw new Exception("Failed to save a paysheet item during batch. Paysheet ID: {$paysheetId}, Item Timesheet ID: {$item['timesheet_id']}");
                }
            }
            return true; // All items executed successfully
        } catch (Exception $e) {
            // Log error and re-throw to allow the controller's transaction to roll back.
            error_log("Error in PaysheetItem::saveBatch for Paysheet ID {$paysheetId}: " . $e->getMessage());
            throw $e;
        }
    } // This '}' correctly closes the saveBatch method.

    // Method to fetch items for a specific paysheet with details for display
    /**
     * @param int $paysheetId
     * @return PaysheetItem[]
     * @throws Exception
     */
    public static function findAllByPaysheetId(int $paysheetId): array // Line 110 is likely here now
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
            $items[] = new self($row);
        }
        return $items;
    }

    public static function deleteByPaysheetId(int $paysheetId): bool
    {
        if ($paysheetId <= 0) {
            return false;
        }
        $db = Connection::getInstance();
        try {
            $sql = "DELETE FROM paysheet_items WHERE paysheet_id = :paysheet_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':paysheet_id', $paysheetId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in PaysheetItem::deleteByPaysheetId for Paysheet ID {$paysheetId}: " . $e->getMessage());
            throw $e;
        }
    }
}