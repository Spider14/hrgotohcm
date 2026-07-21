<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Middleware\AuthMiddleware;
use App\Models\Staff;
use App\Core\Database;
use App\Middleware\CSRFMiddleware;
use PDO;
use Throwable;

class StaffController {
    public function __construct() {
        // Enforce basic access control session tracking rules immediately
        AuthMiddleware::handle();
    }

    public function index(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor']);

        $db = Database::getConnection();
        $role = (string)($_SESSION['user_role'] ?? 'Staff');
        $userId = (int)($_SESSION['user_id'] ?? 0);

        // Pull reference parameters for form configurations
        $departments = $db->query("SELECT dept_id, dept_name FROM departments ORDER BY dept_name ASC")->fetchAll();

        // Refactored to use standard array checks, bypassing the missing Request::getParam method
        $filters = [
            'search'        => isset($_GET['search']) ? trim((string)$_GET['search']) : '',
            'department_id' => isset($_GET['department_id']) ? trim((string)$_GET['department_id']) : '',
            'status'        => isset($_GET['status']) ? trim((string)$_GET['status']) : '',
            'supervisor_user_id' => $role === 'Supervisor' ? $userId : null
        ];
        
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) { $currentPage = 1; }

        // Execute queries via models
        $recordsResult = Staff::getFilteredList($filters, $currentPage, 10);
        $statusSummary = Staff::getStatusSummary($role === 'Supervisor' ? $userId : null);
        
        $pageTitle = "HRGoTo HCM - Staff Registry Directory";
        
        // Load layout templates sequentially
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/staff/index.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function downloadCsvTemplate(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="hrgoto_staff_onboarding_template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'surname', 'other_names', 'username', 'email', 'staff_id_card', 'department_id', 'designation_id',
            'role_id', 'gender', 'date_of_birth', 'date_joined', 'employment_status', 'phone_one', 'phone_two',
            'hometown', 'region', 'nationality', 'religion', 'marital_status', 'number_of_children',
            'biography', 'supervisor_user_id'
        ]);
        fclose($out);
        exit;
    }

    public function uploadCsv(Request $request): void {
        header('Content-Type: application/json; charset=UTF-8');
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $this->ensureCsvBatchTable();

        $file = $_FILES['staff_csv'] ?? null;
        if (!$file || (int)$file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'CSV upload failed.']);
            exit;
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Only CSV files are supported.']);
            exit;
        }

        $batchId = bin2hex(random_bytes(8));
        $targetName = $batchId . '.csv';
        $targetPath = __DIR__ . '/../../public/uploads/documents/' . $targetName;
        if (!move_uploaded_file((string)$file['tmp_name'], $targetPath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Could not save uploaded CSV file.']);
            exit;
        }

        $total = $this->countCsvRows($targetPath);

        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO onboarding_csv_batches
            (batch_id, file_path, total_rows, completed_rows, status, created_by)
            VALUES (:batch_id, :file_path, :total_rows, 0, 'Queued', :created_by)");
        $stmt->execute([
            'batch_id' => $batchId,
            'file_path' => '/uploads/documents/' . $targetName,
            'total_rows' => $total,
            'created_by' => (int)($_SESSION['user_id'] ?? 0),
        ]);

        echo json_encode([
            'success' => true,
            'batch_id' => $batchId,
            'total_rows' => $total,
            'message' => 'CSV uploaded. Start processing to onboard records.'
        ]);
        exit;
    }

    public function processCsvBatch(Request $request): void {
        header('Content-Type: application/json; charset=UTF-8');
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $this->ensureCsvBatchTable();

        $body = $request->getBody();
        $batchId = trim((string)($body['batch_id'] ?? ''));
        if ($batchId === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Missing batch id.']);
            exit;
        }

        $db = Database::getConnection();
        $batchStmt = $db->prepare("SELECT * FROM onboarding_csv_batches WHERE batch_id = :batch_id LIMIT 1");
        $batchStmt->execute(['batch_id' => $batchId]);
        $batch = $batchStmt->fetch(PDO::FETCH_ASSOC);
        if (!$batch) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Batch not found.']);
            exit;
        }

        $absolutePath = __DIR__ . '/../../public' . (string)$batch['file_path'];
        if (!file_exists($absolutePath)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Batch file no longer exists.']);
            exit;
        }

        $db->prepare("UPDATE onboarding_csv_batches SET status = 'Running' WHERE batch_id = :batch_id")
            ->execute(['batch_id' => $batchId]);

        $completed = (int)$batch['completed_rows'];
        $total = (int)$batch['total_rows'];
        $errors = [];

        if (($handle = fopen($absolutePath, 'r')) !== false) {
            $header = fgetcsv($handle);
            if (!is_array($header)) {
                fclose($handle);
                $db->prepare("UPDATE onboarding_csv_batches SET status = 'Failed', error_message = 'CSV header missing' WHERE batch_id = :batch_id")
                    ->execute(['batch_id' => $batchId]);
                echo json_encode(['success' => false, 'message' => 'Invalid CSV header.']);
                exit;
            }

            $rowNumber = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if ($rowNumber <= $completed) {
                    continue;
                }

                $payload = array_combine($header, $row);
                if (!is_array($payload)) {
                    $errors[] = "Row {$rowNumber}: invalid row format";
                    continue;
                }

                try {
                    $this->insertCsvStaffRecord($payload, $db);
                    $completed++;
                } catch (Throwable $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                    $completed++;
                }

                $detail = "Processed {$completed}/{$total}";
                if (!empty($errors)) {
                    $detail .= '; Last error: ' . end($errors);
                }

                $db->prepare("UPDATE onboarding_csv_batches
                    SET completed_rows = :completed_rows,
                        progress_message = :progress_message,
                        error_message = :error_message
                    WHERE batch_id = :batch_id")
                    ->execute([
                        'completed_rows' => $completed,
                        'progress_message' => $detail,
                        'error_message' => !empty($errors) ? implode(' | ', array_slice($errors, -5)) : null,
                        'batch_id' => $batchId,
                    ]);

                if ($completed >= $total) {
                    break;
                }
            }
            fclose($handle);
        }

        $finalStatus = $completed >= $total ? 'Completed' : 'Running';
        $db->prepare("UPDATE onboarding_csv_batches
            SET status = :status,
                completed_rows = :completed_rows,
                completed_at = CASE WHEN :status = 'Completed' THEN NOW() ELSE completed_at END
            WHERE batch_id = :batch_id")
            ->execute([
                'status' => $finalStatus,
                'completed_rows' => $completed,
                'batch_id' => $batchId,
            ]);

        echo json_encode([
            'success' => true,
            'batch_id' => $batchId,
            'completed_rows' => $completed,
            'total_rows' => $total,
            'status' => $finalStatus,
            'errors' => $errors,
        ]);
        exit;
    }

    public function getCsvBatchStatus(Request $request): void {
        header('Content-Type: application/json; charset=UTF-8');
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensureCsvBatchTable();

        $batchId = trim((string)($_GET['batch_id'] ?? ''));
        if ($batchId === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Missing batch id.']);
            exit;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT batch_id, total_rows, completed_rows, status, progress_message, error_message
            FROM onboarding_csv_batches
            WHERE batch_id = :batch_id
            LIMIT 1");
        $stmt->execute(['batch_id' => $batchId]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$batch) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Batch not found.']);
            exit;
        }

        $total = (int)($batch['total_rows'] ?? 0);
        $completed = (int)($batch['completed_rows'] ?? 0);
        $percent = $total > 0 ? (int)floor(($completed / $total) * 100) : 0;

        echo json_encode([
            'success' => true,
            'batch_id' => $batch['batch_id'],
            'status' => $batch['status'],
            'completed' => $completed,
            'total' => $total,
            'percentage' => $percent,
            'progress_message' => (string)($batch['progress_message'] ?? ''),
            'error_message' => (string)($batch['error_message'] ?? ''),
        ]);
        exit;
    }

    private function countCsvRows(string $path): int {
        $count = 0;
        if (($handle = fopen($path, 'r')) !== false) {
            $headerRead = false;
            while (($row = fgetcsv($handle)) !== false) {
                if (!$headerRead) {
                    $headerRead = true;
                    continue;
                }
                if ($row !== [null] && $row !== false) {
                    $count++;
                }
            }
            fclose($handle);
        }
        return $count;
    }

    private function insertCsvStaffRecord(array $payload, PDO $db): void {
        $email = trim((string)($payload['email'] ?? ''));
        $username = trim((string)($payload['username'] ?? ''));
        $fullName = trim((string)($payload['surname'] ?? '') . ' ' . (string)($payload['other_names'] ?? ''));
        $roleId = (int)($payload['role_id'] ?? 0);
        if ($email === '' || $username === '' || $fullName === '' || $roleId <= 0) {
            throw new \RuntimeException('Required identity fields are missing.');
        }

        $existsStmt = $db->prepare("SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1");
        $existsStmt->execute(['email' => $email, 'username' => $username]);
        if ($existsStmt->fetch()) {
            throw new \RuntimeException('Email or username already exists.');
        }

        $password = password_hash('Welcome@HRGoTo2026', PASSWORD_BCRYPT, ['cost' => 11]);

        $db->beginTransaction();
        try {
            $userStmt = $db->prepare("INSERT INTO users (fullname, username, email, password, role_id, status)
                VALUES (:fullname, :username, :email, :password, :role_id, 'Active')");
            $userStmt->execute([
                'fullname' => $fullName,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role_id' => $roleId,
            ]);

            $userId = (int)$db->lastInsertId();

            $staffStmt = $db->prepare("INSERT INTO staff_records
                (user_id, staff_id_card, dept_id, designation_id, gender, date_of_birth, date_joined,
                 employment_status, phone_one, phone_two, hometown, region, nationality, religion,
                 marital_status, number_of_children, biography, supervisor_user_id)
                VALUES
                (:user_id, :staff_id_card, :dept_id, :designation_id, :gender, :date_of_birth, :date_joined,
                 :employment_status, :phone_one, :phone_two, :hometown, :region, :nationality, :religion,
                 :marital_status, :number_of_children, :biography, :supervisor_user_id)");
            $staffStmt->execute([
                'user_id' => $userId,
                'staff_id_card' => trim((string)($payload['staff_id_card'] ?? '')),
                'dept_id' => (int)($payload['department_id'] ?? 0),
                'designation_id' => (int)($payload['designation_id'] ?? 0),
                'gender' => trim((string)($payload['gender'] ?? 'Male')),
                'date_of_birth' => trim((string)($payload['date_of_birth'] ?? '1990-01-01')),
                'date_joined' => trim((string)($payload['date_joined'] ?? date('Y-m-d'))),
                'employment_status' => trim((string)($payload['employment_status'] ?? 'Permanent')),
                'phone_one' => trim((string)($payload['phone_one'] ?? '')),
                'phone_two' => trim((string)($payload['phone_two'] ?? '')),
                'hometown' => trim((string)($payload['hometown'] ?? '')),
                'region' => trim((string)($payload['region'] ?? '')),
                'nationality' => trim((string)($payload['nationality'] ?? 'Ghanaian')),
                'religion' => trim((string)($payload['religion'] ?? '')),
                'marital_status' => trim((string)($payload['marital_status'] ?? 'single')),
                'number_of_children' => (int)($payload['number_of_children'] ?? 0),
                'biography' => trim((string)($payload['biography'] ?? '')),
                'supervisor_user_id' => (int)($payload['supervisor_user_id'] ?? 0),
            ]);

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private function ensureCsvBatchTable(): void {
        $db = Database::getConnection();
        $db->exec("CREATE TABLE IF NOT EXISTS onboarding_csv_batches (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            batch_id VARCHAR(32) NOT NULL UNIQUE,
            file_path VARCHAR(255) NOT NULL,
            total_rows INT NOT NULL DEFAULT 0,
            completed_rows INT NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT 'Queued',
            progress_message VARCHAR(255) NULL,
            error_message TEXT NULL,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL DEFAULT NULL,
            INDEX idx_onboarding_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function idCard(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);

        $userId = (int)($_GET['id'] ?? 0);
        if ($userId <= 0) {
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

        $this->ensureIdCardConfig($db);
        $this->ensureCompanyProfileColumns($db);
        $config = $db->query("SELECT * FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
        $displayFields = !empty($config['id_card_fields']) ? json_decode((string)$config['id_card_fields'], true) : ['staff_id_card', 'fullname', 'dept_name', 'designation_title', 'phone_one', 'email'];
        $primaryColor = !empty($config['id_card_primary_color']) ? $config['id_card_primary_color'] : '#162C5B';
        $secondaryColor = !empty($config['id_card_secondary_color']) ? $config['id_card_secondary_color'] : '#2b6cb0';
        $companyName = !empty($config['company_name']) ? $config['company_name'] : 'Bessfa Community Bank PLC';
        $companyShortName = !empty($config['company_short_name']) ? $config['company_short_name'] : '';
        $logoUrl = !empty($config['id_card_logo']) ? $config['id_card_logo'] : (!empty($config['company_logo_url']) ? $config['company_logo_url'] : ($_ENV['APP_URL'] ?? '') . '/assets/img/logo_nbg.png');
        $companyPhone = !empty($config['company_phone']) ? $config['company_phone'] : '';
        $companyEmail = !empty($config['company_email']) ? $config['company_email'] : '';
        $companyRegion = !empty($config['company_region']) ? $config['company_region'] : '';
        $companyCity = !empty($config['company_city']) ? $config['company_city'] : '';
        $companyTell = !empty($config['company_tell']) ? $config['company_tell'] : '';
        $companyTagline = !empty($config['company_tagline']) ? $config['company_tagline'] : '';

        $stmt = $db->prepare(
            "SELECT u.id, u.fullname, u.email, s.staff_id_card, s.phone_one, s.avatar_url,
                    d.dept_name, dg.title AS designation_title, s.employment_status,
                    s.date_joined, s.gender, s.date_of_birth
             FROM users u
             LEFT JOIN staff_records s ON s.user_id = u.id
             LEFT JOIN departments d ON d.dept_id = s.dept_id
             LEFT JOIN designations dg ON dg.id = s.designation_id
             WHERE u.id = :id AND u.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute(['id' => $userId]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$staff) {
            http_response_code(404);
            echo 'Staff record not found.';
            exit;
        }

        $pageTitle = 'ID Card - ' . $staff['fullname'];
        require_once __DIR__ . '/../Views/dashboard/layouts/header_clean.php';
        require_once __DIR__ . '/../Views/staff/id_card.php';
    }

    private function ensureIdCardConfig(PDO $db): void {
        $cols = ['id_card_fields', 'id_card_primary_color', 'id_card_secondary_color', 'id_card_logo'];
        foreach ($cols as $col) {
            $check = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'app_config' AND COLUMN_NAME = :c");
            $check->execute(['c' => $col]);
            if ((int)$check->fetchColumn() === 0) {
                $type = ($col === 'id_card_fields') ? 'TEXT NULL' : 'VARCHAR(255) NULL';
                $db->exec("ALTER TABLE app_config ADD COLUMN `{$col}` {$type}");
            }
        }
    }

    private function ensureCompanyProfileColumns(PDO $db): void {
        $cols = ['company_name', 'company_short_name', 'company_address', 'company_phone', 'company_email', 'company_website', 'company_logo_url'];
        foreach ($cols as $col) {
            $check = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'app_config' AND COLUMN_NAME = :c");
            $check->execute(['c' => $col]);
            if ((int)$check->fetchColumn() === 0) {
                $db->exec("ALTER TABLE app_config ADD COLUMN `{$col}` VARCHAR(255) NULL");
            }
        }
    }
}