<?php

namespace App\UserManagement\Model;

use App\Core\Database\Connection;
use PDO;
use Exception;

class Site
{
    public ?int $id = null;
    public ?int $company_id = null; // Nullable, for direct contracts
    public string $site_name;
    public string $site_address;
    public ?float $budget_per_pay_period = null; // DECIMAL in DB, float or string in PHP
    public bool $is_active = true;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $deleted_at = null;

    // For display purposes when joining
    public ?string $company_name = null;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->company_id = isset($data['company_id']) ? (int)$data['company_id'] : null;
        $this->site_name = $data['site_name'] ?? '';
        $this->site_address = $data['site_address'] ?? '';
        $this->budget_per_pay_period = isset($data['budget_per_pay_period']) ? (float)$data['budget_per_pay_period'] : null;
        $this->is_active = isset($data['is_active']) ? (bool)$data['is_active'] : true;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
        $this->deleted_at = $data['deleted_at'] ?? null;
        $this->company_name = $data['company_name'] ?? null; // From JOINs
    }

    /**
     * @return Site[]
     * @throws Exception
     */
    public static function findAll(): array
    {
        $db = Connection::getInstance();
        // Optionally join with companies to get company_name for display
        $sql = "SELECT s.*, c.company_name 
                FROM sites s
                LEFT JOIN companies c ON s.company_id = c.id AND c.deleted_at IS NULL
                WHERE s.deleted_at IS NULL 
                ORDER BY s.site_name ASC";
        $stmt = $db->query($sql);
        $sitesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sites = [];
        foreach ($sitesData as $siteData) {
            $sites[] = new self($siteData);
        }
        return $sites;
    }

    public static function findById(int $id): ?Site
    {
        $db = Connection::getInstance();
        $sql = "SELECT s.*, c.company_name 
                FROM sites s
                LEFT JOIN companies c ON s.company_id = c.id AND c.deleted_at IS NULL
                WHERE s.id = :id AND s.deleted_at IS NULL 
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $siteData = $stmt->fetch(PDO::FETCH_ASSOC);
        return $siteData ? new self($siteData) : null;
    }

    public function save(): bool
    {
        $db = Connection::getInstance();
        $now = date('Y-m-d H:i:s');
        $isActiveInt = (int)$this->is_active;

        if ($this->id === null) { // Create
            $this->created_at = $now;
            $this->updated_at = $now;
            $sql = "INSERT INTO sites (company_id, site_name, site_address, budget_per_pay_period, is_active, created_at, updated_at) 
                    VALUES (:company_id, :site_name, :site_address, :budget_per_pay_period, :is_active, :created_at, :updated_at)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':created_at', $this->created_at, PDO::PARAM_STR);
        } else { // Update
            $this->updated_at = $now;
            $sql = "UPDATE sites SET 
                        company_id = :company_id, 
                        site_name = :site_name, 
                        site_address = :site_address, 
                        budget_per_pay_period = :budget_per_pay_period, 
                        is_active = :is_active, 
                        updated_at = :updated_at 
                    WHERE id = :id AND deleted_at IS NULL";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        }

        $stmt->bindParam(':site_name', $this->site_name, PDO::PARAM_STR);
        $stmt->bindParam(':site_address', $this->site_address, PDO::PARAM_STR);
        $stmt->bindParam(':is_active', $isActiveInt, PDO::PARAM_INT);
        $stmt->bindParam(':updated_at', $this->updated_at, PDO::PARAM_STR);

        // Nullable fields
        $stmt->bindValue(':company_id', $this->company_id, $this->company_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':budget_per_pay_period', $this->budget_per_pay_period, $this->budget_per_pay_period === null ? PDO::PARAM_NULL : PDO::PARAM_STR); // DECIMAL stored as string

        $success = $stmt->execute();
        if ($success && $this->id === null) {
            $this->id = (int)$db->lastInsertId();
        }
        return $success;
    }

    public static function softDelete(int $id): bool
    {
        if ($id <= 0) return false;
        $db = Connection::getInstance();
        $now = date('Y-m-d H:i:s');
        try {
            // Also set is_active to 0 when soft deleting
            $stmt = $db->prepare("UPDATE sites SET deleted_at = :deleted_at, is_active = 0 WHERE id = :id AND deleted_at IS NULL");
            $stmt->bindParam(':deleted_at', $now, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in Site::softDelete for ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Find all sites assigned to a specific staff member.
     *
     * @param int $staffId The ID of the staff member.
     * @return Site[] An array of Site objects.
     * @throws Exception
     */
    public static function findAssignedToStaff(int $staffId): array
    {
        if ($staffId <= 0) {
            return []; // Or throw an InvalidArgumentException
        }

        $db = Connection::getInstance();
        try {
            // Join staff_assigned_sites with sites table
            // Also join with companies to get company_name if needed for display
            $sql = "SELECT s.*, c.company_name
                    FROM sites s
                    INNER JOIN staff_assigned_sites sas ON s.id = sas.site_id
                    LEFT JOIN companies c ON s.company_id = c.id AND c.deleted_at IS NULL
                    WHERE sas.staff_user_id = :staff_id 
                      AND s.deleted_at IS NULL
                      AND s.is_active = 1  -- Optionally, only show active sites
                    ORDER BY s.site_name ASC";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':staff_id', $staffId, PDO::PARAM_INT);
            $stmt->execute();

            $sitesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $assignedSites = [];
            foreach ($sitesData as $siteData) {
                $assignedSites[] = new self($siteData);
            }
            return $assignedSites;
        } catch (Exception $e) {
            error_log("Error in Site::findAssignedToStaff for staff ID {$staffId}: " . $e->getMessage());
            throw new Exception("Database query failed while fetching assigned sites. " . $e->getMessage(), 0, $e);
        }
    }
    // Add nameExists or similar unique checks if needed for site_name, perhaps scoped to a company_id or globally
    // For now, we'll assume site_name doesn't need to be globally unique for simplicity.
    public static function findAllActive(): array
    {
        $db = Connection::getInstance();
        try {
            // Optionally join with companies to get company_name if needed for display consistency
            $sql = "SELECT s.*, c.company_name
                    FROM sites s
                    LEFT JOIN companies c ON s.company_id = c.id AND c.deleted_at IS NULL
                    WHERE s.is_active = 1 AND s.deleted_at IS NULL 
                    ORDER BY s.site_name ASC";

            $stmt = $db->query($sql);
            $sitesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $activeSites = [];
            foreach ($sitesData as $siteData) {
                $activeSites[] = new self($siteData);
            }
            return $activeSites;
        } catch (Exception $e) {
            error_log("Error in Site::findAllActive: " . $e->getMessage());
            throw new Exception("Database query failed while fetching all active sites. " . $e->getMessage(), 0, $e);
        }
    }

}
