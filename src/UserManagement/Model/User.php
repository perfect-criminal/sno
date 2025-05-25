<?php

namespace App\UserManagement\Model;

use PDO;
use App\Core\Database\Connection;
use Exception;

class User
{
    public ?int $id = null;
    public int $role_id; // Assuming role_id is always required from form/controller
    public ?int $supervisor_id = null;
    public string $first_name;
    public string $last_name;
    public string $email;
    public string $password_hash; // This will be set by controller before calling save
    public ?string $phone_number = null;
    public ?string $address = null;
    public ?float $pay_rate = null;
    public bool $is_active = true; // Default to true
    public ?string $profile_image_path = null;

    // IMPORTANT: Initialize nullable typed properties
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $last_login_at = null;
    public ?string $deleted_at = null;

    public string $role_name = ''; // For joined data, not a direct DB column for this table

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = isset($data['id']) ? (int)$data['id'] : null;
            $this->role_id = (int)($data['role_id'] ?? 0); // Provide a default or ensure it's always set
            $this->supervisor_id = isset($data['supervisor_id']) ? (int)$data['supervisor_id'] : null;
            $this->first_name = $data['first_name'] ?? '';
            $this->last_name = $data['last_name'] ?? '';
            $this->email = $data['email'] ?? '';
            $this->password_hash = $data['password_hash'] ?? '';
            $this->phone_number = $data['phone_number'] ?? null;
            $this->address = $data['address'] ?? null;
            $this->pay_rate = isset($data['pay_rate']) ? (float)$data['pay_rate'] : null;
            $this->is_active = isset($data['is_active']) ? (bool)$data['is_active'] : true;
            $this->profile_image_path = $data['profile_image_path'] ?? null;

            $this->created_at = $data['created_at'] ?? null;
            $this->updated_at = $data['updated_at'] ?? null;
            $this->last_login_at = $data['last_login_at'] ?? null;
            $this->deleted_at = $data['deleted_at'] ?? null;
            $this->role_name = $data['role_name'] ?? '';
        }
    }

    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public static function emailExists(string $email, ?int $excludeId = null): bool
    {
        $db = Connection::getInstance();
        $sql = "SELECT id FROM users WHERE email = :email AND deleted_at IS NULL";
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        if ($excludeId !== null) {
            $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchColumn() !== false;
    }



    // --- findAll() and findById() methods from before ---
    /**
     * @return User[]
     * @throws Exception
     */
    public static function findAll(): array
    {
        try {
            $db = Connection::getInstance();
            $sql = "SELECT u.*, r.role_name 
                    FROM users u
                    LEFT JOIN roles r ON u.role_id = r.id
                    WHERE u.deleted_at IS NULL 
                    ORDER BY u.created_at DESC";
            $stmt = $db->query($sql);
            $usersData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $users = [];
            foreach ($usersData as $userData) {
                $user = new self($userData); // Constructor populates role_name if available
                $users[] = $user;
            }
            return $users;
        } catch (Exception $e) {
            error_log("Error in User::findAll: " . $e->getMessage());
            throw new Exception("Database query failed while fetching all users. " . $e->getMessage(), 0, $e);
        }
    }

    public static function findById(int $id): ?User
    {
        try {
            $db = Connection::getInstance();
            $stmt = $db->prepare("SELECT u.*, r.role_name 
                                  FROM users u 
                                  LEFT JOIN roles r ON u.role_id = r.id
                                  WHERE u.id = :id AND u.deleted_at IS NULL 
                                  LIMIT 1");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            return $userData ? new self($userData) : null; // Constructor populates role_name
        } catch (Exception $e) {
            error_log("Error in User::findById for ID {$id}: " . $e->getMessage());
            throw new Exception("Database query failed while finding user by ID. " . $e->getMessage(), 0, $e);
        }
    }

    public static function findByEmail(string $email): ?User
    {
        try {
            $db = Connection::getInstance();
            $stmt = $db->prepare("SELECT u.*, r.role_name 
                                  FROM users u
                                  LEFT JOIN roles r ON u.role_id = r.id
                                  WHERE u.email = :email AND u.deleted_at IS NULL LIMIT 1");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            return $userData ? new self($userData) : null;
        } catch (Exception $e) {
            error_log("Error in User::findByEmail for {$email}: " . $e->getMessage());
            throw new Exception("Database query failed while finding user by email. " . $e->getMessage(), 0, $e);
        }
    }
    /**
     * Soft delete a user by setting the deleted_at timestamp.
     * @param int $id The ID of the user to soft delete.
     * @return bool True on success, false on failure.
     * @throws Exception
     */
    public static function softDelete(int $id): bool
    {
        if ($id <= 0) {
            return false; // Or throw an InvalidArgumentException
        }

        $db = Connection::getInstance();
        $now = date('Y-m-d H:i:s');

        try {
            $stmt = $db->prepare("UPDATE users SET deleted_at = :deleted_at, is_active = 0 WHERE id = :id AND deleted_at IS NULL");
            $stmt->bindParam(':deleted_at', $now, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in User::softDelete for ID {$id}: " . $e->getMessage());
            throw new Exception("Database query failed while soft deleting user. " . $e->getMessage(), 0, $e);
        }
    }
    public function save(): bool
    {
        $db = Connection::getInstance();
        $now = date('Y-m-d H:i:s');

        if ($this->id === null) { // Creating a new user
            $this->created_at = $this->created_at ?? $now; // Ensure created_at is set
            $this->updated_at = $now; // Set updated_at for new user

            $sql = "INSERT INTO users (role_id, supervisor_id, first_name, last_name, email, password_hash, phone_number, address, pay_rate, is_active, created_at, updated_at) 
                    VALUES (:role_id, :supervisor_id, :first_name, :last_name, :email, :password_hash, :phone_number, :address, :pay_rate, :is_active, :created_at, :updated_at)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':created_at', $this->created_at, PDO::PARAM_STR);

        } else { // Updating an existing user
            $this->updated_at = $now; // Always update timestamp on save for existing record

            // Note: The controller is responsible for ensuring $this->password_hash contains
            // either the new hashed password or the original one if not changed.
            $sql = "UPDATE users SET 
                        role_id = :role_id,
                        supervisor_id = :supervisor_id,
                        first_name = :first_name,
                        last_name = :last_name,
                        email = :email,
                        password_hash = :password_hash, 
                        phone_number = :phone_number,
                        address = :address,
                        pay_rate = :pay_rate,
                        is_active = :is_active,
                        updated_at = :updated_at
                    WHERE id = :id AND deleted_at IS NULL";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        }

        // Bind common parameters
        $isActiveInt = (int)$this->is_active;

        $stmt->bindParam(':role_id', $this->role_id, PDO::PARAM_INT);
        $stmt->bindParam(':first_name', $this->first_name, PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $this->last_name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $this->email, PDO::PARAM_STR);
        $stmt->bindParam(':password_hash', $this->password_hash, PDO::PARAM_STR); // Bind whatever is in the property
        $stmt->bindParam(':is_active', $isActiveInt, PDO::PARAM_INT);
        $stmt->bindParam(':updated_at', $this->updated_at, PDO::PARAM_STR);

        // Nullable fields - using bindValue for explicit NULL handling
        $stmt->bindValue(':supervisor_id', $this->supervisor_id, $this->supervisor_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':phone_number', $this->phone_number, $this->phone_number === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':address', $this->address, $this->address === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':pay_rate', $this->pay_rate, $this->pay_rate === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

        $success = $stmt->execute();
        if ($success && $this->id === null) { // If it was an INSERT
            $this->id = (int)$db->lastInsertId();
        }
        return $success;
    }
    /**
     * Find all active staff members assigned to a specific supervisor.
     * Assumes Role ID 2 is 'Staff'.
     *
     * @param int $supervisorId The ID of the supervisor.
     * @return User[] An array of User objects (staff members).
     * @throws Exception
     */
    public static function findStaffBySupervisorId(int $supervisorId): array
    {
        if ($supervisorId <= 0) {
            return [];
        }

        $db = Connection::getInstance();
        try {
            // Assuming Role ID 2 is for 'Staff'
            // We also fetch the role_name for completeness, though it should always be 'Staff' here
            $sql = "SELECT u.*, r.role_name 
                    FROM users u
                    LEFT JOIN roles r ON u.role_id = r.id
                    WHERE u.supervisor_id = :supervisor_id 
                      AND u.role_id = 2 -- Ensure we are only fetching Staff
                      AND u.deleted_at IS NULL
                      AND u.is_active = 1 -- Optionally, only show active staff
                    ORDER BY u.last_name ASC, u.first_name ASC";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':supervisor_id', $supervisorId, PDO::PARAM_INT);
            $stmt->execute();

            $staffData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $staffMembers = [];
            foreach ($staffData as $staffMemberData) {
                $staffMembers[] = new self($staffMemberData);
            }
            return $staffMembers;
        } catch (Exception $e) {
            error_log("Error in User::findStaffBySupervisorId for supervisor ID {$supervisorId}: " . $e->getMessage());
            throw new Exception("Database query failed while fetching assigned staff. " . $e->getMessage(), 0, $e);
        }
    }
}