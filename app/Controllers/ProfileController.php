<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Helpers\Security;
use PDO;
use Exception;

class ProfileController {

    public function __construct() {
        AuthMiddleware::handle();
    }

    public function view(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff', 'Recruiter']);
        
        $userId = (int)$request->getParam('id', 0);
        if ($userId === 0) {
            header('Location: /staff');
            exit;
        }

        $role = (string)($_SESSION['user_role'] ?? 'Staff');
        $sessionUserId = (int)($_SESSION['user_id'] ?? 0);
        if (!in_array($role, ['Super Admin', 'HR Manager'], true) && $userId !== $sessionUserId) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }

        $db = Database::getConnection();

        // 1. Fetch Primary Account Data + Extended Staff Demographic Metadata
        $stmt = $db->prepare("
            SELECT s.*, u.fullname, u.username, u.email, u.created_at, d.dept_name, dg.title as designation_title 
            FROM staff_records s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN departments d ON s.dept_id = d.dept_id
            LEFT JOIN designations dg ON s.designation_id = dg.id
            WHERE s.user_id = :uid AND u.deleted_at IS NULL 
            LIMIT 1
        ");
        $stmt->execute(['uid' => $userId]);
        $staffRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$staffRecord) {
            Security::setFlash('error', 'Profile not found');
            header('Location: /staff');
            exit;
        }

        // 2. Query Academic Credentials Collection Matrix (For Tab 3)
        $stmtEdu = $db->prepare("SELECT * FROM staff_education WHERE user_id = :uid ORDER BY year_from DESC");
        $stmtEdu->execute(['uid' => $userId]);
        $educationRecords = $stmtEdu->fetchAll(PDO::FETCH_ASSOC);

        // 3. Query Corporate Professional Experience Trace Log Rows (For Tab 4)
        $stmtExp = $db->prepare("SELECT * FROM staff_experience WHERE user_id = :uid ORDER BY year_from DESC");
        $stmtExp->execute(['uid' => $userId]);
        $experienceRecords = $stmtExp->fetchAll(PDO::FETCH_ASSOC);

        $stmtPromotions = $db->prepare("SELECT id, COALESCE(requested_rank, to_designation) AS requested_rank,
                COALESCE(current_rank, from_designation) AS current_rank,
                status, supporting_document, created_at
            FROM staff_promotions
            WHERE user_id = :uid
            ORDER BY created_at DESC");
        $stmtPromotions->execute(['uid' => $userId]);
        $promotionRecords = $stmtPromotions->fetchAll(PDO::FETCH_ASSOC);

        // 4. Fetch dropdown data for edit modal
        $departments = $db->query("SELECT dept_id, dept_name FROM departments ORDER BY dept_name")->fetchAll(PDO::FETCH_ASSOC);
        $supervisors = $db->query("SELECT u.id, u.fullname FROM users u JOIN staff_records s ON s.user_id = u.id WHERE u.status = 'Active' AND u.deleted_at IS NULL ORDER BY u.fullname")->fetchAll(PDO::FETCH_ASSOC);
        $roles = $db->query("SELECT id, role_name FROM roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

        // 5. Get current role_id for the staff user
        $roleStmt = $db->prepare("SELECT role_id FROM users WHERE id = :uid");
        $roleStmt->execute(['uid' => $userId]);
        $currentRoleId = (int)$roleStmt->fetchColumn();

        // 6. Group data parameters inside the structural namespace array expected by the view template
        $profileData = [
            'staff'         => $staffRecord,
            'education'     => $educationRecords ?? [],
            'experience'    => $experienceRecords ?? [],
            'promotions'    => $promotionRecords ?? [],
            'departments'   => $departments,
            'supervisors'   => $supervisors,
            'roles'         => $roles,
            'current_role_id' => $currentRoleId,
        ];

        $pageTitle = "HRGoTo HCM - " . $staffRecord['fullname'] . " Profile Context";

        // View Router Rendering Core Execution (Fixed File Path Target Typo Here)
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/staff/view_profile.php'; 
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function dossierPdf(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);

        $userId = (int)($_GET['id'] ?? 0);
        if ($userId === 0) {
            http_response_code(404);
            echo 'Staff ID not provided.';
            exit;
        }

        $role = (string)($_SESSION['user_role'] ?? 'Staff');
        $sessionUserId = (int)($_SESSION['user_id'] ?? 0);
        if (!in_array($role, ['Super Admin', 'HR Manager'], true) && $userId !== $sessionUserId) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }

        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT s.*, u.fullname, u.username, u.email, u.created_at, d.dept_name, dg.title as designation_title
            FROM staff_records s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN departments d ON s.dept_id = d.dept_id
            LEFT JOIN designations dg ON s.designation_id = dg.id
            WHERE s.user_id = :uid AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['uid' => $userId]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$staff) {
            http_response_code(404);
            echo 'Staff record not found.';
            exit;
        }

        $eduStmt = $db->prepare("SELECT * FROM staff_education WHERE user_id = :uid ORDER BY year_from DESC");
        $eduStmt->execute(['uid' => $userId]);
        $education = $eduStmt->fetchAll(PDO::FETCH_ASSOC);

        $expStmt = $db->prepare("SELECT * FROM staff_experience WHERE user_id = :uid ORDER BY year_from DESC");
        $expStmt->execute(['uid' => $userId]);
        $experience = $expStmt->fetchAll(PDO::FETCH_ASSOC);

        $promStmt = $db->prepare("SELECT id, COALESCE(requested_rank, to_designation) AS requested_rank,
                COALESCE(current_rank, from_designation) AS current_rank,
                status, supporting_document, created_at
            FROM staff_promotions WHERE user_id = :uid ORDER BY created_at DESC");
        $promStmt->execute(['uid' => $userId]);
        $promotions = $promStmt->fetchAll(PDO::FETCH_ASSOC);

        ob_start();

        $rootPath = dirname(__DIR__, 2);
        $docsDir = $rootPath . '/public/uploads/documents/';
        $outputDir = $rootPath . '/public/uploads/compiled_reports/';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $staff['fullname']);
        $summaryPdfPath = $outputDir . "summary_{$userId}.pdf";
        $finalName = strtoupper($safeName) . '_DOSSIER.pdf';
        $finalPath = $outputDir . $finalName;

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('HRGoTo HCM');
        $pdf->SetTitle("Staff Dossier - {$staff['fullname']}");
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'STAFF DOSSIER', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $pdf->Ln(3);

        $html = '<h2 style="color:#162C5B;">Personal Information</h2>
        <table border="1" cellpadding="4" cellspacing="0" style="font-size:9pt;">
            <tr><td width="30%"><b>Full Name</b></td><td>' . \App\Helpers\Security::escape($staff['fullname']) . '</td></tr>
            <tr><td><b>Staff ID</b></td><td>' . \App\Helpers\Security::escape($staff['staff_id_card'] ?? 'N/A') . '</td></tr>
            <tr><td><b>Username</b></td><td>' . \App\Helpers\Security::escape($staff['username'] ?? 'N/A') . '</td></tr>
            <tr><td><b>Email</b></td><td>' . \App\Helpers\Security::escape($staff['email'] ?? 'N/A') . '</td></tr>
            <tr><td><b>Phone</b></td><td>' . \App\Helpers\Security::escape($staff['phone_one'] ?? 'N/A') . '</td></tr>
            <tr><td><b>Department</b></td><td>' . \App\Helpers\Security::escape($staff['dept_name'] ?? 'N/A') . '</td></tr>
            <tr><td><b>Designation</b></td><td>' . \App\Helpers\Security::escape($staff['designation_title'] ?? 'N/A') . '</td></tr>
            <tr><td><b>Employment Status</b></td><td>' . \App\Helpers\Security::escape($staff['employment_status'] ?? 'N/A') . '</td></tr>
            <tr><td><b>Date Joined</b></td><td>' . \App\Helpers\Security::escape($staff['date_joined'] ?? 'N/A') . '</td></tr>
            <tr><td><b>Gender</b></td><td>' . \App\Helpers\Security::escape($staff['gender'] ?? 'N/A') . '</td></tr>
            <tr><td><b>Date of Birth</b></td><td>' . \App\Helpers\Security::escape($staff['date_of_birth'] ?? 'N/A') . '</td></tr>
            <tr><td><b>Hometown</b></td><td>' . \App\Helpers\Security::escape($staff['hometown'] ?? 'N/A') . '</td></tr>
            <tr><td><b>Region</b></td><td>' . \App\Helpers\Security::escape($staff['region'] ?? 'N/A') . '</td></tr>
            <tr><td><b>Nationality</b></td><td>' . \App\Helpers\Security::escape($staff['nationality'] ?? 'N/A') . '</td></tr>
            <tr><td><b>Marital Status</b></td><td>' . \App\Helpers\Security::escape($staff['marital_status'] ?? 'N/A') . '</td></tr>
        </table>';

        if (!empty($education)) {
            $html .= '<h2 style="color:#162C5B;margin-top:15px;">Academic Qualifications</h2>
            <table border="1" cellpadding="4" cellspacing="0" style="font-size:9pt;">
                <tr style="background:#e2e8f0;"><td><b>#</b></td><td><b>Institution</b></td><td><b>Certificate</b></td><td><b>Year From</b></td><td><b>Year To</b></td></tr>';
            $i = 1;
            foreach ($education as $edu) {
                $html .= '<tr><td>' . $i++ . '</td><td>' . \App\Helpers\Security::escape($edu['institution'] ?? '') . '</td><td>' . \App\Helpers\Security::escape($edu['certificate'] ?? '') . '</td><td>' . \App\Helpers\Security::escape((string)($edu['year_from'] ?? '')) . '</td><td>' . \App\Helpers\Security::escape((string)($edu['year_to'] ?? '')) . '</td></tr>';
            }
            $html .= '</table>';
        }

        if (!empty($experience)) {
            $html .= '<h2 style="color:#162C5B;margin-top:15px;">Work Experience</h2>
            <table border="1" cellpadding="4" cellspacing="0" style="font-size:9pt;">
                <tr style="background:#e2e8f0;"><td><b>#</b></td><td><b>Organisation</b></td><td><b>Job Title</b></td><td><b>Year From</b></td><td><b>Year To</b></td></tr>';
            $i = 1;
            foreach ($experience as $exp) {
                $html .= '<tr><td>' . $i++ . '</td><td>' . \App\Helpers\Security::escape($exp['organisation'] ?? $exp['organization'] ?? '') . '</td><td>' . \App\Helpers\Security::escape($exp['job_title'] ?? '') . '</td><td>' . \App\Helpers\Security::escape((string)($exp['year_from'] ?? '')) . '</td><td>' . \App\Helpers\Security::escape((string)($exp['year_to'] ?? '')) . '</td></tr>';
            }
            $html .= '</table>';
        }

        if (!empty($promotions)) {
            $html .= '<h2 style="color:#162C5B;margin-top:15px;">Promotion History</h2>
            <table border="1" cellpadding="4" cellspacing="0" style="font-size:9pt;">
                <tr style="background:#e2e8f0;"><td><b>#</b></td><td><b>Current Rank</b></td><td><b>Requested Rank</b></td><td><b>Status</b></td><td><b>Date</b></td></tr>';
            $i = 1;
            foreach ($promotions as $prom) {
                $html .= '<tr><td>' . $i++ . '</td><td>' . \App\Helpers\Security::escape($prom['current_rank'] ?? 'N/A') . '</td><td>' . \App\Helpers\Security::escape($prom['requested_rank'] ?? 'N/A') . '</td><td>' . \App\Helpers\Security::escape($prom['status'] ?? 'N/A') . '</td><td>' . \App\Helpers\Security::escape($prom['created_at'] ?? '') . '</td></tr>';
            }
            $html .= '</table>';
        }

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($summaryPdfPath, 'F');

        try {
            $prevLevel = error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
            $merger = new \PDFMerger();
            $merger->addPDF($summaryPdfPath, 'all');

            foreach ($education as $edu) {
                if (empty($edu['dossier_url'])) continue;
                $physicalPath = $rootPath . '/public' . $edu['dossier_url'];
                $ext = strtolower(pathinfo($physicalPath, PATHINFO_EXTENSION));
                if ($ext === 'pdf' && file_exists($physicalPath)) {
                    $merger->addPDF($physicalPath, 'all');
                }
            }

            $merger->merge('file', $finalPath);
            error_reporting($prevLevel);
            if (file_exists($summaryPdfPath)) unlink($summaryPdfPath);
        } catch (\Throwable $e) {
            error_reporting($prevLevel);
            rename($summaryPdfPath, $finalPath);
        }

        if (ob_get_length()) ob_end_clean();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($finalName) . '"');
        header('Content-Length: ' . filesize($finalPath));
        readfile($finalPath);
        exit;
    }

    public function update(Request $request): void {
        header('Content-Type: application/json');
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);

        $data = $request->getBody();
        $userId = (int)($data['user_id'] ?? 0);

        if ($userId === 0 || empty($data['fullname']) || empty($data['phone_one'])) {
            echo json_encode(['success' => false, 'message' => 'Mandatory parameters missing.']);
            exit;
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // Update Base identity record table
            $stmtUser = $db->prepare("UPDATE users SET fullname = :fname, role_id = :role_id WHERE id = :uid");
            $stmtUser->execute([
                'fname'   => trim($data['fullname']),
                'role_id' => (int)($data['role_id'] ?? 0),
                'uid'     => $userId
            ]);

            // Update Extended demographic staff data table
            $stmtStaff = $db->prepare("
                UPDATE staff_records 
                SET phone_one = :phone, employment_status = :status, dept_id = :dept_id, supervisor_user_id = :supervisor_id 
                WHERE user_id = :uid
            ");
            $stmtStaff->execute([
                'phone'         => trim($data['phone_one']),
                'status'        => $data['employment_status'],
                'dept_id'       => (int)($data['department_id'] ?? 0),
                'supervisor_id' => (int)($data['supervisor_user_id'] ?? 0),
                'uid'           => $userId
            ]);

            // Log operational audit trail event
            $stmtAudit = $db->prepare("
                INSERT INTO audit_logs (user_id, action, description) 
                VALUES (:admin, 'HR_PROFILE_UPDATE', :descr)
            ");
            $stmtAudit->execute([
                'admin' => $_SESSION['user_id'] ?? 0,
                'descr' => "Updated employee metadata record values for User ID reference: " . $userId
            ]);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Employee profile records modified successfully.']);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Modification routine failed: ' . $e->getMessage()]);
        }
        exit;
    }
}