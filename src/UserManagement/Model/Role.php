<?php

namespace App\UserManagement\Model;

use App\Core\Database\Connection;
use PDO;
use Exception;

class Role
{
    public ?int $id = null;
    public string $role_name;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = isset($data['id']) ? (int)$data['id'] : null;
            $this->role_name = $data['role_name'] ?? '';
            $this->created_at = $data['created_at'] ?? null;
            $this->updated_at = $data['updated_at'] ?? null;
        }
    }

    /**
     * @return Role[]
     * @throws Exception
     */
    public static function findAll(): array
    {
        try {
            $db = Connection::getInstance();
            $stmt = $db->query("SELECT * FROM roles ORDER BY role_name ASC");
            $rolesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $roles = [];
            foreach ($rolesData as $roleData) {
                $roles[] = new self($roleData);
            }
            return $roles;
        } catch (Exception $e) {
            error_log("Error in Role::findAll: " . $e->getMessage());
            throw new Exception("Database query failed while fetching roles. " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param int $id
     * @return Role|null
     * @throws Exception
     */
    public static function findById(int $id): ?Role
    {
        try {
            $db = Connection::getInstance();
            $stmt = $db->prepare("SELECT * FROM roles WHERE id = :id LIMIT 1");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $roleData = $stmt->fetch(PDO::FETCH_ASSOC);
            return $roleData ? new self($roleData) : null;
        } catch (Exception $e) {
            error_log("Error in Role::findById for ID {$id}: " . $e->getMessage());
            throw new Exception("Database query failed while fetching role by ID. " . $e->getMessage(), 0, $e);
        }
    }
}