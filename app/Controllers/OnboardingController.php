<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use Exception;
use PDO;

class OnboardingController {

    /**
     * Controller Constructor Gatekeeper
     */
    public function __construct() {
        // Run standard RBAC access checks cleanly
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
    }

    /**
     * Display the multi-step onboarding wizard interface
     */
    public function index(Request $request): void {
        // Retain connection verification handle
        $db = Database::getConnection();
        // Fetch initialization variables for configuration dropdown mappings
        $roles = $db->query("SELECT id, role_name FROM roles ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $departments = $db->query("SELECT dept_id, dept_name FROM departments ORDER BY dept_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $designations = $db->query("SELECT id, title FROM designations ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = "HRGoTo HCM - Add Employee Profile Wizard";

        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/staff/onboard.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    /**
     * Process multi-step onboarding form data with child records relational mapping
     */
    public function submit(Request $request): void {
        header('Content-Type: application/json; charset=UTF-8');
        $db = Database::getConnection();

        $data = $request->getBody();

        // 1. Enforce Core Parametric Input Validation Checks (Aligned with onboard.php keys)
        $mandatoryFields = [
            'surname', 'other_names', 'email', 'username', 'staff_id_card', 
            'department_id', 'designation_id', 'gender', 'date_of_birth', 
            'date_joined', 'employment_status', 'phone_one', 
            'hometown', 'region', 'nationality', 'religion', 'marital_status'
        ];

        foreach ($mandatoryFields as $field) {
            if (empty($data[$field])) {
                echo json_encode(['success' => false, 'message' => "Mandatory parameter missing: [{$field}] validation check failed."]);
                exit;
            }
        }

        // =========================================================================
        // 2. Enforce Strict Multi-File Attachment Stream Validations
        // =========================================================================
        
        // FIX 1: Map directly to name="avatar_photo" from the HTML form
        $avatarFile = $_FILES['avatar_photo'] ?? null;
        
        // FIX 2: Dynamic tracking of the file array array structure for name="edu_certificate_file[]"
        $eduFilesArray = $_FILES['edu_certificate_file'] ?? null;

        // Verify passport image presence
        if (!$avatarFile || $avatarFile['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Validation Failure: Staff Identity passport photo is required.']);
            exit;
        }

        // Verify academic background dossier layout array block presence
        if (!$eduFilesArray || !isset($eduFilesArray['error'][0]) || $eduFilesArray['error'][0] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Validation Failure: The compulsory step-3 background verification dossier is missing.']);
            exit;
        }

        // Validate image format and constraints
        $allowedImageTypes = ['image/jpeg', 'image/png'];
        $detectedImageType = mime_content_type($avatarFile['tmp_name']);
        if (!in_array($detectedImageType, $allowedImageTypes) || $avatarFile['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Invalid image format or portrait file size exceeds 2MB threshold constraint. Only JPEG/PNG allowed.']);
            exit;
        }

        // Pre-validate dynamic uploaded background verification documents arrays
        foreach ($eduFilesArray['error'] as $index => $errorState) {
            // Check only active rows where an institution was typed
            if (!empty($data['edu_institution'][$index])) {
                if ($errorState !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'message' => "Validation Failure: Background verification document file missing or broken for Row #" . ($index + 1)]);
                    exit;
                }

                $detectedDossierType = mime_content_type($eduFilesArray['tmp_name'][$index]);
                $allowedDossierTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                
                if (!in_array($detectedDossierType, $allowedDossierTypes) || $eduFilesArray['size'][$index] > 5 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'message' => "Background validation dossier at Row #" . ($index + 1) . " must be a valid PDF, JPEG, or PNG document under 5MB."]);
                    exit;
                }
            }
        }

        // 3. Begin Transaction Block for Cross-Table Data Integrity
        $db->beginTransaction();
        
        // Arrays to trace processed files for automated cleanups during unexpected rollbacks
        $uploadedTrackers = [];

        try {
            // Check for existing users to prevent identifier duplication anomalies
            $chk = $db->prepare("SELECT id FROM users WHERE email = :email OR username = :uname LIMIT 1");
            $chk->execute(['email' => trim($data['email']), 'uname' => trim($data['username'])]);
            if ($chk->fetch()) {
                throw new Exception("Identity conflict anomaly: Email or username profile is already registered in system databases.");
            }

            // Generate an automated corporate default temporary fallback password string
            $defaultPasswordText = "Welcome@HRGoTo" . date('Y');
            $securedHash = password_hash($defaultPasswordText, PASSWORD_BCRYPT, ['cost' => 11]);

            // Construct corporate complete full name from structured dynamic inputs
            $computedFullName = trim(trim($data['surname']) . ' ' . trim($data['other_names']));

            // Insert Entry Layer 1: Core System Account Frame Context Mapping
            $stmtUser = $db->prepare("
                INSERT INTO users (fullname, username, email, password, role_id, status) 
                VALUES (:fname, :uname, :email, :pass, :role_id, 'Active')
            ");
            $stmtUser->execute([
                'fname'   => $computedFullName,
                'uname'   => trim($data['username']),
                'email'   => trim($data['email']),
                'pass'    => $securedHash,
                'role_id' => (int)$data['role_id']
            ]);

            $newUserId = (int)$db->lastInsertId();

            // Process and Move Identity Portrait Image Asset File
            $avatarExt = ($detectedImageType === 'image/jpeg') ? 'jpg' : 'png';
            $avatarName = bin2hex(random_bytes(16)) . '.' . $avatarExt;
            $avatarPath = __DIR__ . '/../../public/uploads/avatars/' . $avatarName;

            if (!move_uploaded_file($avatarFile['tmp_name'], $avatarPath)) {
                throw new Exception("File stream system copy operation encountered an avatar upload storage directory access fault.");
            }
            $uploadedTrackers[] = $avatarPath; // Register for tracking protection
            $avatarUrl = '/uploads/avatars/' . $avatarName;

            // Insert Entry Layer 2: Demographic staff profile expansion records
            $stmtStaff = $db->prepare("
                INSERT INTO staff_records (
                    user_id, staff_id_card, dept_id, designation_id, gender, 
                    date_of_birth, date_joined, employment_status, phone_one, phone_two, 
                    hometown, region, nationality, religion, marital_status, 
                    number_of_children, biography, avatar_url
                ) VALUES (
                    :uid, :card, :dept_id, :desig_id, :gender, 
                    :dob, :joined, :estate, :phone_one, :phone_two, 
                    :hometown, :region, :nationality, :religion, :marital_status, 
                    :children, :bio, :avatar_url
                )
            ");
            
            $stmtStaff->execute([
                'uid'            => $newUserId,
                'card'           => trim($data['staff_id_card']),
                'dept_id'        => (int)$data['department_id'],
                'desig_id'       => (int)$data['designation_id'],
                'gender'         => $data['gender'],
                'dob'            => $data['date_of_birth'],
                'joined'         => $data['date_joined'],
                'estate'         => $data['employment_status'],
                'phone_one'      => trim($data['phone_one']), // FIX 3: Adjusted field mapping context to track phone_one key
                'phone_two'      => !empty($data['phone_two']) ? trim($data['phone_two']) : null,
                'hometown'       => trim($data['hometown']),
                'region'         => $data['region'],
                'nationality'    => trim($data['nationality']),
                'religion'       => trim($data['religion']),
                'marital_status' => $data['marital_status'],
                'children'       => (int)($data['number_of_children'] ?? 0),
                'bio'            => !empty($data['biography']) ? trim($data['biography']) : null,
                'avatar_url'     => $avatarUrl
            ]);

            // =========================================================================
			// SAFE LOOP 1: Dynamic Academic Qualification Loops (Step-3 arrays)
			// =========================================================================
			// Bypass custom request mapping wrapper for raw array validation checks
			$rawInstitutions = $_POST['edu_institution'] ?? [];
			$rawCertificates = $_POST['edu_certificate'] ?? [];
			$rawFromYears    = $_POST['edu_from'] ?? [];
			$rawToYears      = $_POST['edu_to'] ?? [];

			if (!empty($rawInstitutions) && is_array($rawInstitutions)) {
				$stmtEdu = $db->prepare("
					INSERT INTO staff_education (user_id, institution, certificate, year_from, year_to, dossier_url) 
					VALUES (:uid, :institution, :certificate, :year_from, :year_to, :dossier_url)
				");
				
				foreach ($rawInstitutions as $key => $institution) {
					$trimmedInstitution = trim((string)$institution);
					
					// Ensure we only insert rows where an institution name was actually provided
					if ($trimmedInstitution !== '') {
						
						$dossierUrl = null;
						
						// Check if a file was actually uploaded for this specific row entry
						if (isset($eduFilesArray['tmp_name'][$key]) && $eduFilesArray['error'][$key] === UPLOAD_ERR_OK) {
							$originalFileName = $eduFilesArray['name'][$key];
							$fileExt = pathinfo($originalFileName, PATHINFO_EXTENSION);
							$dossierName = bin2hex(random_bytes(16)) . '.' . $fileExt;
							$dossierPath = __DIR__ . '/../../public/uploads/documents/' . $dossierName;

							if (!move_uploaded_file($eduFilesArray['tmp_name'][$key], $dossierPath)) {
								throw new Exception("Dynamic academic verification document failed copy operation stream processing at Row #" . ($key + 1));
							}
							$uploadedTrackers[] = $dossierPath; // For rollback cleanup protection
							$dossierUrl = '/uploads/documents/' . $dossierName;
						}

						$stmtEdu->execute([
							'uid'         => $newUserId,
							'institution' => $trimmedInstitution,
							'certificate' => trim((string)($rawCertificates[$key] ?? '')),
							'year_from'   => (int)($rawFromYears[$key] ?? 0),
							'year_to'     => (int)($rawToYears[$key] ?? 0),
							'dossier_url' => $dossierUrl
						]);
					}
				}
			}

            // =========================================================================
            // SAFE LOOP 2: Dynamic Corporate Employment History Loops (Step-4 arrays)
            // =========================================================================
            // Adjust to track dynamic form variables named: prev_institution, prev_address, etc.
            if (!empty($data['prev_institution'])) {
                $stmtExp = $db->prepare("
                    INSERT INTO staff_experience (user_id, company_name, job_title, year_from, year_to, responsibilities) 
                    VALUES (:uid, :company, :title, :year_from, :year_to, :resp)
                ");

                // Check single string or sequential values from layout definitions
                $prevInstitutions = is_array($data['prev_institution']) ? $data['prev_institution'] : [$data['prev_institution']];
                
                foreach ($prevInstitutions as $key => $company) {
                    $trimmedCompany = trim((string)$company);
                    if ($trimmedCompany !== '') {
                        
                        // Extract optional timeline string configurations safely
                        $rawFrom = is_array($data['prev_from'] ?? null) ? ($data['prev_from'][$key] ?? '') : ($data['prev_from'] ?? '');
                        $rawTo   = is_array($data['prev_to'] ?? null) ? ($data['prev_to'][$key] ?? '') : ($data['prev_to'] ?? '');
                        $rawTitle = is_array($data['prev_title'] ?? null) ? ($data['prev_title'][$key] ?? '') : ($data['prev_title'] ?? '');
                        $rawDuties = is_array($data['prev_duties'] ?? null) ? ($data['prev_duties'][$key] ?? '') : ($data['prev_duties'] ?? '');

                        $stmtExp->execute([
                            'uid'       => $newUserId,
                            'company'   => $trimmedCompany,
                            'title'     => trim((string)$rawTitle),
                            'year_from' => !empty($rawFrom) ? (int)date('Y', strtotime($rawFrom)) : 0,
                            'year_to'   => !empty($rawTo) ? (int)date('Y', strtotime($rawTo)) : 0,
                            'resp'      => !empty($rawDuties) ? trim((string)$rawDuties) : null
                        ]);
                    }
                } 
            }

            // 5. Log Transaction Audit Trail Configuration
            $stmtAudit = $db->prepare("
                INSERT INTO audit_logs (user_id, action, description, ip_address) 
                VALUES (:admin_id, 'HR_STAFF_ONBOARD', :descr, :ip)
            ");
            $stmtAudit->execute([
                'admin_id' => $_SESSION['user_id'] ?? $newUserId, 
                'descr'    => "Successfully onboarded user index: " . trim($data['email']) . " assigned unique ID Card: " . trim($data['staff_id_card']),
                'ip'       => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);

            // Everything succeeded. Safely commit transaction atomic changes to disk.
            $db->commit();
            foreach ($db->query("SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.role_name IN ('Super Admin', 'HR Manager') AND u.deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN) as $sId) {
                \App\Helpers\Notification::create((int)$sId, 'New Staff Onboarded', "Employee {$computedFullName} has been onboarded successfully.", 'success');
            }
            echo json_encode([
                'success' => true, 
                'message' => 'Employee profile completely onboarded into system registries. Default system access credentials provisioned: ' . $defaultPasswordText
            ]);

        } catch (Exception $e) {
            // Atomic safety protection rollback
            $db->rollBack();
            
            // Loop and clear any files written during this crashed session request
            foreach ($uploadedTrackers as $filePath) {
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            echo json_encode(['success' => false, 'message' => 'Database operation transaction aborted: ' . $e->getMessage()]);
        }
        exit;
    }
}