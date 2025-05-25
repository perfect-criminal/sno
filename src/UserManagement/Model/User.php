<?php

namespace App\UserManagement\Model;

use PDO;
use App\Core\Database\Connection; // Make sure Connection.php is in App\Core\Database
use Exception; // Use global Exception

class User
{
    public ?int $id = null;
    public int $role_id;
    public ?int $supervisor_id = null;
    public string $first_name;
    public string $last_name;
    public string $email;
    public string $password_hash;
    public ?string $phone_number = null;
    public ?string $address = null;
    public ?float $pay_rate = null;
    public bool $is_active = true;
    public ?string $profile_image_path = null;
    public ?string $created_at; // Changed to allow null if not set in constructor
    public ?string $updated_at; // Changed to allow null if not set in constructor
    public ?string $last_login_at = null;
    public ?string $deleted_at = null;
    public string $role_name = '';
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = isset($data['id']) ? (int)$data['id'] : null;
            $this->role_id = (int)$data['role_id']; // Assuming role_id is always present
            $this->supervisor_id = isset($data['supervisor_id']) ? (int)$data['supervisor_id'] : null;
            $this->first_name = $data['first_name']; // Assuming first_name is always present
            $this->last_name = $data['last_name'];   // Assuming last_name is always present
            $this->email = $data['email'];         // Assuming email is always present
            $this->password_hash = $data['password_hash']; // Assuming password_hash is always present
            $this->phone_number = $data['phone_number'] ?? null;
            $this->address = $data['address'] ?? null;
            $this->pay_rate = isset($data['pay_rate']) ? (float)$data['pay_rate'] : null;
            $this->is_active = isset($data['is_active']) ? (bool)$data['is_active'] : true;
            $this->profile_image_path = $data['profile_image_path'] ?? null;
            $this->created_at = $data['created_at'] ?? null;
            $this->updated_at = $data['updated_at'] ?? null;
            $this->last_login_at = $data['last_login_at'] ?? null;
            $this->deleted_at = $data['deleted_at'] ?? null;
        }
    }

    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Find a user by email (excluding soft-deleted users).
     *
     * @param string $email
     * @return User|null
     * @throws Exception if database connection fails
     */
    public static function findByEmail(string $email): ?User
    {
        try {
            $db = Connection::getInstance(); // This will use your config which now works!
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                return new self($userData);
            }
            return null;
        } catch (Exception $e) { // Catching PDOException or our custom Exception from Connection
            // Log error (more robust logging should be implemented later)
            error_log("Error in User::findByEmail for {$email}: " . $e->getMessage());
            // Depending on your error strategy, you might re-throw, or return null
            // For now, let's re-throw to see it in the browser during development
            throw new Exception("Database query failed while finding user by email. " . $e->getMessage(), 0, $e);
        }
    }
    public static function findAll(): array
    {
        try {
            $db = Connection::getInstance();
            // Join with roles table to get role_name
            $sql = "SELECT u.*, r.role_name 
                    FROM users u
                    LEFT JOIN roles r ON u.role_id = r.id
                    WHERE u.deleted_at IS NULL 
                    ORDER BY u.created_at DESC";
            $stmt = $db->query($sql);
            $usersData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $users = [];
            foreach ($usersData as $userData) {
                $user = new self($userData);
                $user->role_name = $userData['role_name'] ?? 'N/A'; // Assign role_name
                $users[] = $user;
            }
            return $users;
        } catch (Exception $e) {
            error_log("Error in User::findAll: " . $e->getMessage());
            throw new Exception("Database query failed while fetching all users. " . $e->getMessage(), 0, $e);
        }
    }

    // Add findById if not present, useful for edit/delete later
    /**
     * Find a user by ID (excluding soft-deleted users).
     *
     * @param int $id
     * @return User|null
     * @throws Exception if database connection fails
     */
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

            if ($userData) {
                $user = new self($userData);
                $user->role_name = $userData['role_name'] ?? 'N/A';
                return $user;
            }
            return null;
        } catch (Exception $e) {
            error_log("Error in User::findById for ID {$id}: " . $e->getMessage());
            throw new Exception("Database query failed while finding user by ID. " . $e->getMessage(), 0, $e);
        }
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

    /**
     * Save the current user object to the database (handles create/update).
     * @return bool True on success, false on failure.
     * @throws Exception
     */
    public function save(): bool
    {
        $db = Connection::getInstance();

        // Default created_at and updated_at if not set (especially for new records)
        if ($this->id === null && $this->created_at === null) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        if ($this->updated_at === null) {
            $this->updated_at = date('Y-m-d H:i:s');
        }


        if ($this->id === null) { // Creating a new user
            $sql = "INSERT INTO users (role_id, supervisor_id, first_name, last_name, email, password_hash, phone_number, address, pay_rate, is_active, created_at, updated_at) 
                    VALUES (:role_id, :supervisor_id, :first_name, :last_name, :email, :password_hash, :phone_number, :address, :pay_rate, :is_active, :created_at, :updated_at)";
            $stmt = $db->prepare($sql);
            // Set created_at for new records if not already set by constructor from form
            $this->created_at = $this->created_at ?? date('Y-m-d H:i:s');
            $this->updated_at = $this->updated_at ?? date('Y-m-d H:i:s');

        } else { // Updating an existing user
            $this->updated_at = date('Y-m-d H:i:s'); // Always update timestamp on save
            $sql = "UPDATE users SET 
                        role_id = :role_id,
                        supervisor_id = :supervisor_id,
                        first_name = :first_name,
                        last_name = :last_name,
                        email = :email,
                        password_hash = :password_hash, -- Be careful: only update if a new password is provided
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
        $stmt->bindParam(':role_id', $this->role_id, PDO::PARAM_INT);
        $stmt->bindParam(':supervisor_id', $this->supervisor_id, PDO::PARAM_INT_OR_NULL); // Use PDO::PARAM_INT_OR_NULL if PHP >= 8.1 or handle null explicitly
        $stmt->bindParam(':first_name', $this->first_name, PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $this->last_name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $this->email, PDO::PARAM_STR);
        $stmt->bindParam(':password_hash', $this->password_hash, PDO::PARAM_STR); // Always bind, even if not changing for update (logic to change hash should be in controller)
        $stmt->bindParam(':phone_number', $this->phone_number, PDO::PARAM_STR_OR_NULL);
        $stmt->bindParam(':address', $this->address, PDO::PARAM_STR_OR_NULL);
        $stmt->bindParam(':pay_rate', $this->pay_rate); // PDO infers type, or use PDO::PARAM_STR
        $stmt->bindParam(':is_active', $this->is_active, PDO::PARAM_INT); // Store boolean as 0 or 1
        $stmt->bindParam(':updated_at', $this->updated_at, PDO::PARAM_STR);
        if ($this->id === null) { // Only bind created_at for new records
            $stmt->bindParam(':created_at', $this->created_at, PDO::PARAM_STR);
        }


        $success = $stmt->execute();
        if ($success && $this->id === null) {
            $this->id = (int)$db->lastInsertId();
        }
        return $success;
    }
    // Future methods: findById, save (create/update), delete (soft delete), etc.
}