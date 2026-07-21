<?php
class ApplicantModel {
    private $db;

    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    public function getApplicantById($id) {
        $stmt = $this->db->prepare("SELECT * FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getEducation($id) {
        $stmt = $this->db->prepare("SELECT * FROM education_entries WHERE application_id = ? ORDER BY from_year DESC");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmployment($id) {
        $stmt = $this->db->prepare("SELECT * FROM employment_entries WHERE application_id = ? ORDER BY from_date DESC");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAttachments($id) {
        $stmt = $this->db->prepare("SELECT * FROM application_files WHERE application_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status, $notes) {
        $stmt = $this->db->prepare("UPDATE applications SET status = ?, hr_notes = ? WHERE id = ?");
        return $stmt->execute([$status, $notes, $id]);
    }
}