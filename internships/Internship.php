<?php
require_once '../db_config.php';

class Internship {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }
    //Read
    public function read() {
        try {
            $sql = "SELECT i.id as internship_id, 
                           COALESCE(c.name, 'No Company Name') as company_name, 
                           COALESCE(c.address, 'No Address Provided') as company_address, 
                           CONCAT(COALESCE(cp.first_name, ''), ' ', COALESCE(cp.last_name, '')) as contact_name,
                           cp.email as contact_email, 
                           cp.phone as contact_phone
                    FROM internships i
                    JOIN companies c ON i.company_id = c.id
                    LEFT JOIN contact_person cp ON i.contact_person_id = cp.id
                    ORDER BY c.name, cp.last_name";
    
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $results;
        } catch (PDOException $e) {
            error_log("Read error: " . $e->getMessage());
            return false;
        }
    }
    
    
    //Delete
    public function delete($internship_id) {
        try {
            $this->db->beginTransaction();

            $sql = "SELECT company_id, contact_person_id FROM internships WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$internship_id]);
            $ids = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ids) {
                throw new Exception("Internship not found.");
            }

            $sql = "DELETE FROM internships WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$internship_id]);

            if ($ids['contact_person_id']) {
                $sql = "DELETE FROM contact_person WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$ids['contact_person_id']]);
            }

            if ($ids['company_id']) {
                $sql = "DELETE FROM companies WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$ids['company_id']]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Delete error: " . $e->getMessage());
            return false;
        }
    }
    //Create 
    public function create($data) {
        try {
            $this->db->beginTransaction();
    
            // Insert into companies
            $sql = "INSERT INTO companies (name, address) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$data['companyName'], $data['address']]);
            $companyId = $this->db->lastInsertId();
    
            // Insert into contact_person
            $sql = "INSERT INTO contact_person (first_name, last_name, email, phone, company_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['contactFirstName'], 
                $data['contactLastName'], 
                $data['contactEmail'], 
                $data['contactPhone'], 
                $companyId
            ]);
            $contactId = $this->db->lastInsertId();
    
            // Insert into internships
            $sql = "INSERT INTO internships (company_id, contact_person_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$companyId, $contactId]);
    
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Create error: " . $e->getMessage());
            return false;
        }
    }
    

    //Update

    public function updateCompany($companyId, $companyData) {
        try {
            $sql = "UPDATE companies SET name = ?, address = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$companyData['companyName'], $companyData['address'], $companyId]);
            return true;
        } catch (PDOException $e) {
            error_log("Update Company error: " . $e->getMessage());
            return false;
        }
    }

    public function updateContactPerson($contactId, $contactData) {
        try {
            $sql = "UPDATE contact_person SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$contactData['contactFirstName'], $contactData['contactLastName'], $contactData['contactEmail'], $contactData['contactPhone'], $contactId]);
            return true;
        } catch (PDOException $e) {
            error_log("Update Contact error: " . $e->getMessage());
            return false;
        }
    }

}

$internship = new Internship($pdo);
