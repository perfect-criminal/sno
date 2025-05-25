<?php

namespace App\UserManagement\Model; // Consistent with User and Role

use App\Core\Database\Connection;
use PDO;
use Exception;

class Company
{
    public ?int $id = null;
    public string $company_name;
    public ?string $contact_person = null;
    public ?string $contact_email = null;
    public ?string $contact_phone = null;
    public ?string $address = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $deleted_at = null;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->company_name = $data['company_name'] ?? '';
        $this->contact_person = $data['contact_person'] ?? null;
        $this->contact_email = $data['contact_email'] ?? null;
        $this->contact_phone = $data['contact_phone'] ?? null;
        $this->address = $data['address'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
        $this->deleted_at = $data['deleted_at'] ?? null;
    }

    /**
     * @return Company[]
     * @throws Exception
     */
    public static function findAll(): array
    {
        $db = Connection::getInstance();
        $stmt = $db->query("SELECT * FROM companies WHERE deleted_at IS NULL ORDER BY company_name ASC");
        $companiesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $companies = [];
        foreach ($companiesData as $companyData) {
            $companies[] = new self($companyData);
        }
        return $companies;
    }

    public static function findById(int $id): ?Company
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("SELECT * FROM companies WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $companyData = $stmt->fetch(PDO::FETCH_ASSOC);
        return $companyData ? new self($companyData) : null;
    }

    public function save(): bool
    {
        $db = Connection::getInstance();
        $now = date('Y-m-d H:i:s');

        if ($this->id === null) { // Create
            $this->created_at = $now;
            $this->updated_at = $now;
            $sql = "INSERT INTO companies (company_name, contact_person, contact_email, contact_phone, address, created_at, updated_at) 
                    VALUES (:company_name, :contact_person, :contact_email, :contact_phone, :address, :created_at, :updated_at)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':created_at', $this->created_at, PDO::PARAM_STR);
        } else { // Update
            $this->updated_at = $now;
            $sql = "UPDATE companies SET 
                        company_name = :company_name, 
                        contact_person = :contact_person, 
                        contact_email = :contact_email, 
                        contact_phone = :contact_phone, 
                        address = :address, 
                        updated_at = :updated_at 
                    WHERE id = :id AND deleted_at IS NULL";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        }

        $stmt->bindParam(':company_name', $this->company_name, PDO::PARAM_STR);
        $stmt->bindParam(':updated_at', $this->updated_at, PDO::PARAM_STR);

        // Bind nullable fields
        $stmt->bindValue(':contact_person', $this->contact_person, $this->contact_person === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':contact_email', $this->contact_email, $this->contact_email === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':contact_phone', $this->contact_phone, $this->contact_phone === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':address', $this->address, $this->address === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

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
            $stmt = $db->prepare("UPDATE companies SET deleted_at = :deleted_at WHERE id = :id AND deleted_at IS NULL");
            $stmt->bindParam(':deleted_at', $now, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in Company::softDelete for ID {$id}: " . $e->getMessage());
            throw $e; // Re-throw
        }
    }

    /**
     * Check if a company name already exists (for validation, excluding a specific ID for updates)
     */
    public static function nameExists(string $companyName, ?int $excludeId = null): bool
    {
        $db = Connection::getInstance();
        $sql = "SELECT id FROM companies WHERE company_name = :company_name AND deleted_at IS NULL";
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':company_name', $companyName, PDO::PARAM_STR);
        if ($excludeId !== null) {
            $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchColumn() !== false;
    }
}