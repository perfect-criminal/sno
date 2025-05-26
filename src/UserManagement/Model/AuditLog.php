<?php

namespace App\UserManagement\Model;

use App\Core\Database\Connection;
use PDO;
use Exception;

class AuditLog
{
    public ?int $id = null;
    public ?int $user_id = null;
    public string $action;
    public ?string $target_type = null;
    public ?int $target_id = null;
    public ?string $details = null;
    public ?string $ip_address = null; // Added based on your original schema
    public ?string $created_at = null;  // Changed from 'timestamp' to 'created_at'

    public ?string $user_name = null;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $this->action = $data['action'] ?? '';
        $this->target_type = $data['target_type'] ?? null;
        $this->target_id = isset($data['target_id']) ? (int)$data['target_id'] : null;
        $this->details = $data['details'] ?? null;
        $this->ip_address = $data['ip_address'] ?? null; // Added
        $this->created_at = $data['created_at'] ?? null; // Changed from 'timestamp'
        $this->user_name = $data['user_name'] ?? null;
    }

    public static function logAction(?int $userId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null, ?string $ipAddress = null): bool
    {
        $db = Connection::getInstance();
        // Using `created_at` as the column name. DEFAULT CURRENT_TIMESTAMP will handle it if not provided.
        // Or explicitly set with NOW(). For consistency with other tables, let's explicitly set it.
        $sql = "INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, created_at) 
                VALUES (:user_id, :action, :target_type, :target_id, :details, :ip_address, NOW())";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindParam(':action', $action, PDO::PARAM_STR);
            $stmt->bindParam(':target_type', $targetType, $targetType === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':target_id', $targetId, $targetId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindParam(':details', $details, $details === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':ip_address', $ipAddress, $ipAddress === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("AuditLog::logAction failed: " . $e->getMessage());
            return false;
        }
    }

    public static function findByTarget(string $targetType, int $targetId): array
    {
        if (empty($targetType) || $targetId <= 0) {
            return [];
        }
        $db = Connection::getInstance();
        // Using `al.created_at` in ORDER BY
        $sql = "SELECT al.*, CONCAT(u.first_name, ' ', u.last_name) AS user_name 
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.target_type = :target_type AND al.target_id = :target_id
                ORDER BY al.created_at ASC"; // Changed 'al.timestamp' to 'al.created_at'

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':target_type', $targetType, PDO::PARAM_STR);
            $stmt->bindParam(':target_id', $targetId, PDO::PARAM_INT);
            $stmt->execute();

            $logsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $logs = [];
            foreach ($logsData as $logData) {
                $logs[] = new self($logData);
            }
            return $logs;
        } catch (Exception $e) {
            error_log("AuditLog::findByTarget failed for {$targetType} ID {$targetId}: " . $e->getMessage());
            throw $e;
        }
    }
}