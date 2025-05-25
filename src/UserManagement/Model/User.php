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

    // Future methods: findById, save (create/update), delete (soft delete), etc.
}