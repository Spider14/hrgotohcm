<?php
declare(strict_types=1);

namespace App\Controllers;
use App\Core\Request;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Helpers\Security;
use PDO;
use Throwable;

class AdminController {
    public function __construct() {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
    }

    public function usersIndex(Request $request): void {
        $db = Database::getConnection();
        $this->ensureUsersPhoneColumn($db);
        $users = $db->query("SELECT u.id, u.fullname, u.username, u.email, u.phone, u.status, u.role_id, r.role_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE u.deleted_at IS NULL
            ORDER BY u.id DESC
            LIMIT 300")->fetchAll(PDO::FETCH_ASSOC);
        $roles = $db->query("SELECT id, role_name FROM roles ORDER BY role_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $pageTitle = 'HRGoTo HCM - Manage Users';
        $suppressGlobalFlash = true;
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/admin/users.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function usersStore(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $fullname = trim((string)($body['fullname'] ?? ''));
        $username = trim((string)($body['username'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $phone = trim((string)($body['phone'] ?? ''));
        $roleId = (int)($body['role_id'] ?? 0);
        $status = trim((string)($body['status'] ?? 'Active'));
        $password = trim((string)($body['password'] ?? ''));

        if ($fullname === '' || $username === '' || $email === '' || $roleId <= 0 || $password === '') {
            Security::setFlash('error', 'Missing required user fields');
            header('Location: /admin/users');
            exit;
        }

        try {
            $db = Database::getConnection();
            $exists = $db->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
            $exists->execute(['username' => $username, 'email' => $email]);
            if ($exists->fetch()) {
                Security::setFlash('error', 'Username or email already exists');
                header('Location: /admin/users');
                exit;
            }

            $stmt = $db->prepare("INSERT INTO users (fullname, username, email, phone, password, role_id, status)
                VALUES (:fullname, :username, :email, :phone, :password, :role_id, :status)");
            $stmt->execute([
                'fullname' => $fullname,
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 11]),
                'role_id' => $roleId,
                'status' => in_array($status, ['Active', 'Inactive'], true) ? $status : 'Active',
            ]);

            Security::setFlash('ok', 'User created');
            header('Location: /admin/users');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/users');
        }
        exit;
    }

    public function rolesIndex(Request $request): void {
        $db = Database::getConnection();
        $roles = $db->query("SELECT id, role_name, permissions, created_at FROM roles ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $pageTitle = 'HRGoTo HCM - Roles & Permissions';
        $suppressGlobalFlash = true;
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/admin/roles_permissions.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function rolesStore(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $roleName = trim((string)($body['role_name'] ?? ''));
        $permissions = trim((string)($body['permissions'] ?? ''));
        if ($roleName === '') {
            Security::setFlash('error', 'Role name is required');
            header('Location: /admin/roles-permissions');
            exit;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO roles (role_name, permissions) VALUES (:role_name, :permissions)");
            $stmt->execute([
                'role_name' => $roleName,
                'permissions' => $permissions,
            ]);
            Security::setFlash('ok', 'Role saved');
            header('Location: /admin/roles-permissions');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/roles-permissions');
        }
        exit;
    }

    public function rolesUpdate(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $id = (int)($body['id'] ?? 0);
        $roleName = trim((string)($body['role_name'] ?? ''));
        $permissions = trim((string)($body['permissions'] ?? ''));
        if ($id <= 0 || $roleName === '') {
            Security::setFlash('error', 'Invalid request');
            header('Location: /admin/roles-permissions');
            exit;
        }
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE roles SET role_name = :role_name, permissions = :permissions WHERE id = :id");
            $stmt->execute(['role_name' => $roleName, 'permissions' => $permissions, 'id' => $id]);
            Security::setFlash('ok', 'Role updated');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
        }
        header('Location: /admin/roles-permissions');
        exit;
    }

    public function appraisalIndex(Request $request): void {
        $db = Database::getConnection();
        $db->exec("CREATE TABLE IF NOT EXISTS appraisal_metrics (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            metric_name VARCHAR(120) NOT NULL,
            metric_prompt TEXT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $metrics = $db->query("SELECT id, metric_name, metric_prompt, is_active, created_at
            FROM appraisal_metrics
            ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        $pageTitle = 'HRGoTo HCM - Appraisal Metrics';
        $suppressGlobalFlash = true;
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/admin/appraisal_metrics.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function appraisalStore(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $name = trim((string)($body['metric_name'] ?? ''));
        $prompt = trim((string)($body['metric_prompt'] ?? ''));
        $isActive = (int)($body['is_active'] ?? 1) === 1 ? 1 : 0;

        if ($name === '' || $prompt === '') {
            Security::setFlash('error', 'Metric name and prompt are required');
            header('Location: /admin/appraisal-metrics');
            exit;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO appraisal_metrics (metric_name, metric_prompt, is_active)
                VALUES (:metric_name, :metric_prompt, :is_active)");
            $stmt->execute([
                'metric_name' => $name,
                'metric_prompt' => $prompt,
                'is_active' => $isActive,
            ]);
            Security::setFlash('ok', 'Metric saved');
            header('Location: /admin/appraisal-metrics');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/appraisal-metrics');
        }
        exit;
    }

    public function ranksIndex(Request $request): void {
        $db = Database::getConnection();
        $this->ensureRanksSalaryColumns($db);
        $ranks = $db->query("SELECT id, rank_name, rank_order, salary, leave_days, other_benefits, is_active, created_at FROM ranks ORDER BY rank_order ASC")
            ->fetchAll(PDO::FETCH_ASSOC);
        $pageTitle = 'HRGoTo HCM - Ranks';
        $suppressGlobalFlash = true;
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/admin/ranks.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function ranksStore(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $rankName = trim((string)($body['rank_name'] ?? ''));
        $rankOrder = (int)($body['rank_order'] ?? 0);
        $salary = (float)($body['salary'] ?? 0);
        $leaveDays = (int)($body['leave_days'] ?? 0);
        $otherBenefits = trim((string)($body['other_benefits'] ?? ''));
        if ($rankName === '') {
            Security::setFlash('error', 'Rank name is required');
            header('Location: /admin/ranks');
            exit;
        }
        try {
            $db = Database::getConnection();
            $this->ensureRanksSalaryColumns($db);
            $stmt = $db->prepare("INSERT INTO ranks (rank_name, rank_order, salary, leave_days, other_benefits) VALUES (:rank_name, :rank_order, :salary, :leave_days, :other_benefits)");
            $stmt->execute(['rank_name' => $rankName, 'rank_order' => $rankOrder, 'salary' => $salary, 'leave_days' => $leaveDays, 'other_benefits' => $otherBenefits]);
            Security::setFlash('ok', 'Rank created');
            header('Location: /admin/ranks');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/ranks');
        }
        exit;
    }

    public function ranksUpdate(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $id = (int)($body['id'] ?? 0);
        $rankName = trim((string)($body['rank_name'] ?? ''));
        $rankOrder = (int)($body['rank_order'] ?? 0);
        $isActive = (int)($body['is_active'] ?? 1);
        $salary = (float)($body['salary'] ?? 0);
        $leaveDays = (int)($body['leave_days'] ?? 0);
        $otherBenefits = trim((string)($body['other_benefits'] ?? ''));
        if ($id <= 0 || $rankName === '') {
            Security::setFlash('error', 'Invalid request');
            header('Location: /admin/ranks');
            exit;
        }
        try {
            $db = Database::getConnection();
            $this->ensureRanksSalaryColumns($db);
            $stmt = $db->prepare("UPDATE ranks SET rank_name = :rank_name, rank_order = :rank_order, is_active = :is_active, salary = :salary, leave_days = :leave_days, other_benefits = :other_benefits WHERE id = :id");
            $stmt->execute(['rank_name' => $rankName, 'rank_order' => $rankOrder, 'is_active' => $isActive, 'salary' => $salary, 'leave_days' => $leaveDays, 'other_benefits' => $otherBenefits, 'id' => $id]);
            Security::setFlash('ok', 'Rank updated');
            header('Location: /admin/ranks');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/ranks');
        }
        exit;
    }

    public function ranksDelete(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            Security::setFlash('error', 'Invalid request');
            header('Location: /admin/ranks');
            exit;
        }
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM ranks WHERE id = :id");
            $stmt->execute(['id' => $id]);
            Security::setFlash('ok', 'Rank deleted');
            header('Location: /admin/ranks');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/ranks');
        }
        exit;
    }

    public function departmentsIndex(Request $request): void {
        $db = Database::getConnection();
        $departments = $db->query("SELECT dept_id, dept_code, dept_name, parent_unit, created_at FROM departments ORDER BY dept_name ASC")
            ->fetchAll(PDO::FETCH_ASSOC);
        $pageTitle = 'HRGoTo HCM - Departments';
        $suppressGlobalFlash = true;
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/admin/departments.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function departmentsStore(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $deptName = trim((string)($body['dept_name'] ?? ''));
        $deptCode = trim((string)($body['dept_code'] ?? ''));
        $parentUnit = trim((string)($body['parent_unit'] ?? ''));

        if ($deptName === '' || $deptCode === '') {
            Security::setFlash('error', 'Department name and code are required');
            header('Location: /admin/departments');
            exit;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO departments (dept_code, dept_name, parent_unit)
                VALUES (:dept_code, :dept_name, :parent_unit)");
            $stmt->execute([
                'dept_code' => $deptCode,
                'dept_name' => $deptName,
                'parent_unit' => $parentUnit !== '' ? $parentUnit : null,
            ]);
            Security::setFlash('ok', 'Department saved');
            header('Location: /admin/departments');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/departments');
        }
        exit;
    }

    private function ensureRanksSalaryColumns(PDO $db): void
    {
        $cols = ['salary', 'leave_days', 'other_benefits'];
        foreach ($cols as $col) {
            $check = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ranks' AND COLUMN_NAME = :c");
            $check->execute(['c' => $col]);
            if ((int)$check->fetchColumn() === 0) {
                $type = match ($col) {
                    'salary' => 'DECIMAL(10,2) NULL DEFAULT 0.00',
                    'leave_days' => 'INT UNSIGNED NULL DEFAULT 0',
                    default => 'TEXT NULL',
                };
                $db->exec("ALTER TABLE ranks ADD COLUMN `{$col}` {$type}");
            }
        }
    }

    public function idCardConfig(Request $request): void
    {
        $db = Database::getConnection();

        $cols = ['id_card_fields', 'id_card_primary_color', 'id_card_secondary_color', 'id_card_logo'];
        foreach ($cols as $col) {
            $check = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'app_config' AND COLUMN_NAME = :c");
            $check->execute(['c' => $col]);
            if ((int)$check->fetchColumn() === 0) {
                $type = ($col === 'id_card_fields') ? 'TEXT NULL' : 'VARCHAR(255) NULL';
                $db->exec("ALTER TABLE app_config ADD COLUMN `{$col}` {$type}");
            }
        }

        $config = $db->query("SELECT * FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

        $pageTitle = 'HRGoTo HCM - ID Card Settings';
        $suppressGlobalFlash = true;
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/admin/id_card_config.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function idCardConfigSave(Request $request): void
    {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();

        $fields = (array)($body['fields'] ?? []);
        $primaryColor = trim((string)($body['primary_color'] ?? '#162C5B'));
        $secondaryColor = trim((string)($body['secondary_color'] ?? '#2b6cb0'));
        $logoUrl = trim((string)($body['logo_url'] ?? ''));

        try {
            $db = Database::getConnection();

            $rowCount = (int)$db->query("SELECT COUNT(*) FROM app_config")->fetchColumn();
            if ($rowCount === 0) {
                $db->exec("INSERT INTO app_config (id_card_fields, id_card_primary_color, id_card_secondary_color, id_card_logo) VALUES ('[]', '#162C5B', '#2b6cb0', '')");
            }

            $stmt = $db->prepare("UPDATE app_config SET id_card_fields = :fields, id_card_primary_color = :primary, id_card_secondary_color = :secondary, id_card_logo = :logo LIMIT 1");
            $stmt->execute([
                'fields' => json_encode($fields),
                'primary' => $primaryColor,
                'secondary' => $secondaryColor,
                'logo' => $logoUrl,
            ]);

            Security::setFlash('ok', 'ID Card settings saved');
            header('Location: /admin/id-card-config');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/id-card-config');
        }
        exit;
    }

    public function companyProfile(Request $request): void
    {
        $db = Database::getConnection();
        $this->ensureCompanyProfileColumns($db);

        $config = $db->query("SELECT * FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

        $pageTitle = 'HRGoTo HCM - Company Profile';
        $suppressGlobalFlash = true;
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/admin/company_profile.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function companyProfileSave(Request $request): void
    {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();

        $db = Database::getConnection();

        $name = trim((string)($body['company_name'] ?? ''));
        $shortName = trim((string)($body['company_short_name'] ?? ''));
        $address = trim((string)($body['company_address'] ?? ''));
        $phone = trim((string)($body['company_phone'] ?? ''));
        $email = trim((string)($body['company_email'] ?? ''));
        $website = trim((string)($body['company_website'] ?? ''));
        $companyRegion = trim((string)($body['company_region'] ?? ''));
        $companyCity = trim((string)($body['company_city'] ?? ''));
        $companyTell = trim((string)($body['company_tell'] ?? ''));
        $companyTagline = trim((string)($body['company_tagline'] ?? ''));
        $smtpHost = trim((string)($body['smtp_host'] ?? ''));
        $smtpPort = trim((string)($body['smtp_port'] ?? ''));
        $smtpUsername = trim((string)($body['smtp_username'] ?? ''));
        $smtpPassword = trim((string)($body['smtp_password'] ?? ''));
        if ($smtpPassword === '********') {
            $existingPass = $db->query("SELECT smtp_password FROM app_config LIMIT 1")->fetchColumn();
            $smtpPassword = is_string($existingPass) ? $existingPass : '';
        }
        $smtpEncryption = trim((string)($body['smtp_encryption'] ?? 'tls'));
        $smtpFromEmail = trim((string)($body['smtp_from_email'] ?? ''));
        $smtpFromName = trim((string)($body['smtp_from_name'] ?? ''));

        if ($name === '') {
            Security::setFlash('error', 'Company name is required');
            header('Location: /admin/company-profile');
            exit;
        }

        try {
            $this->ensureCompanyProfileColumns($db);

            $rowCount = (int)$db->query("SELECT COUNT(*) FROM app_config")->fetchColumn();
            if ($rowCount === 0) {
                $db->exec("INSERT INTO app_config (company_name) VALUES ('" . $db->quote($name) . "')");
            }

            $logoUrl = '';
            $existing = $db->query("SELECT company_logo_url FROM app_config LIMIT 1")->fetchColumn();

            if (!empty($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['company_logo'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];
                if (!in_array($ext, $allowed)) {
                    Security::setFlash('error', 'Invalid file type. Accepted: PNG, JPG, GIF, WebP, SVG');
                    header('Location: /admin/company-profile');
                    exit;
                }
                $uploadDir = __DIR__ . '/../../public/assets/img/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filename = 'company_logo_' . time() . '.' . $ext;
                $dest = $uploadDir . $filename;
                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    Security::setFlash('error', 'Failed to upload logo');
                    header('Location: /admin/company-profile');
                    exit;
                }
                $logoUrl = ($_ENV['APP_URL'] ?? '') . '/assets/img/' . $filename;
            } else {
                $logoUrl = is_string($existing) ? $existing : '';
            }

            $officeLat = trim((string)($body['office_latitude'] ?? ''));
            $officeLng = trim((string)($body['office_longitude'] ?? ''));
            $officeRadius = (int)($body['office_radius_meters'] ?? 200);
            $officeIPs = trim((string)($body['office_ip_whitelist'] ?? ''));

            $stmt = $db->prepare("UPDATE app_config SET company_name = :name, company_short_name = :short, company_address = :addr, company_phone = :phone, company_email = :email, company_website = :web, company_logo_url = :logo, company_region = :region, company_city = :city, company_tell = :tell, company_tagline = :tagline, smtp_host = :smtp_host, smtp_port = :smtp_port, smtp_username = :smtp_user, smtp_password = :smtp_pass, smtp_encryption = :smtp_enc, smtp_from_email = :smtp_from, smtp_from_name = :smtp_name, office_latitude = :ol, office_longitude = :olg, office_radius_meters = :or, office_ip_whitelist = :oi LIMIT 1");
            $stmt->execute([
                'name' => $name,
                'short' => $shortName,
                'addr' => $address,
                'phone' => $phone,
                'email' => $email,
                'web' => $website,
                'logo' => $logoUrl,
                'region' => $companyRegion,
                'city' => $companyCity,
                'tell' => $companyTell,
                'tagline' => $companyTagline,
                'smtp_host' => $smtpHost,
                'smtp_port' => $smtpPort,
                'smtp_user' => $smtpUsername,
                'smtp_pass' => $smtpPassword,
                'smtp_enc' => $smtpEncryption,
                'smtp_from' => $smtpFromEmail,
                'smtp_name' => $smtpFromName,
                'ol' => $officeLat !== '' ? (float)$officeLat : null,
                'olg' => $officeLng !== '' ? (float)$officeLng : null,
                'or' => $officeRadius,
                'oi' => $officeIPs,
            ]);

            Security::setFlash('ok', 'Company profile saved');
            header('Location: /admin/company-profile');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/company-profile');
        }
        exit;
    }

    private function ensureCompanyProfileColumns(PDO $db): void
    {
        $cols = ['company_name', 'company_short_name', 'company_address', 'company_phone', 'company_email', 'company_website', 'company_logo_url', 'company_region', 'company_city', 'company_tell', 'company_tagline', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_from_email', 'smtp_from_name', 'office_latitude', 'office_longitude', 'office_radius_meters', 'office_ip_whitelist'];
        foreach ($cols as $col) {
            $check = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'app_config' AND COLUMN_NAME = :c");
            $check->execute(['c' => $col]);
            if ((int)$check->fetchColumn() === 0) {
                $type = 'VARCHAR(255) NULL';
                $db->exec("ALTER TABLE app_config ADD COLUMN `{$col}` {$type}");
            }
        }
    }

    public function departmentsUpdate(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $id = (int)($body['dept_id'] ?? 0);
        $deptCode = trim((string)($body['dept_code'] ?? ''));
        $deptName = trim((string)($body['dept_name'] ?? ''));
        $parentUnit = trim((string)($body['parent_unit'] ?? ''));
        if ($id <= 0 || $deptName === '' || $deptCode === '') {
            Security::setFlash('error', 'Invalid request');
            header('Location: /admin/departments');
            exit;
        }
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE departments SET dept_code = :code, dept_name = :name, parent_unit = :parent WHERE dept_id = :id");
            $stmt->execute(['code' => $deptCode, 'name' => $deptName, 'parent' => $parentUnit !== '' ? $parentUnit : null, 'id' => $id]);
            Security::setFlash('ok', 'Department updated');
            header('Location: /admin/departments');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/departments');
        }
        exit;
    }

    public function departmentsDelete(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $id = (int)($body['dept_id'] ?? 0);
        if ($id <= 0) {
            Security::setFlash('error', 'Invalid request');
            header('Location: /admin/departments');
            exit;
        }
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM departments WHERE dept_id = :id");
            $stmt->execute(['id' => $id]);
            Security::setFlash('ok', 'Department deleted');
            header('Location: /admin/departments');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/departments');
        }
        exit;
    }

    public function designationsIndex(Request $request): void {
        $db = Database::getConnection();
        $designations = $db->query("SELECT * FROM designations ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
        $pageTitle = 'HRGoTo HCM - Designations';
        $suppressGlobalFlash = true;
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/admin/designations.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function designationsStore(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $title = trim((string)($body['title'] ?? ''));
        $category = trim((string)($body['category'] ?? ''));
        if ($title === '') {
            Security::setFlash('error', 'Designation title is required');
            header('Location: /admin/designations');
            exit;
        }
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO designations (title, category) VALUES (:title, :category)");
            $stmt->execute(['title' => $title, 'category' => $category !== '' ? $category : null]);
            Security::setFlash('ok', 'Designation saved');
            header('Location: /admin/designations');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/designations');
        }
        exit;
    }

    public function designationsUpdate(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $id = (int)($body['id'] ?? 0);
        $title = trim((string)($body['title'] ?? ''));
        $category = trim((string)($body['category'] ?? ''));
        if ($id <= 0 || $title === '') {
            Security::setFlash('error', 'Invalid request');
            header('Location: /admin/designations');
            exit;
        }
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE designations SET title = :title, category = :category WHERE id = :id");
            $stmt->execute(['title' => $title, 'category' => $category !== '' ? $category : null, 'id' => $id]);
            Security::setFlash('ok', 'Designation updated');
            header('Location: /admin/designations');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/designations');
        }
        exit;
    }

    public function designationsDelete(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            Security::setFlash('error', 'Invalid request');
            header('Location: /admin/designations');
            exit;
        }
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM designations WHERE id = :id");
            $stmt->execute(['id' => $id]);
            Security::setFlash('ok', 'Designation deleted');
            header('Location: /admin/designations');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/designations');
        }
        exit;
    }

    public function usersUpdate(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $id = (int)($body['id'] ?? 0);
        $fullname = trim((string)($body['fullname'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $phone = trim((string)($body['phone'] ?? ''));
        $roleId = (int)($body['role_id'] ?? 0);
        if ($id <= 0 || $fullname === '' || $email === '' || $roleId <= 0) {
            Security::setFlash('error', 'Invalid request');
            header('Location: /admin/users');
            exit;
        }
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE users SET fullname = :fullname, email = :email, phone = :phone, role_id = :role_id WHERE id = :id");
            $stmt->execute(['fullname' => $fullname, 'email' => $email, 'phone' => $phone, 'role_id' => $roleId, 'id' => $id]);
            Security::setFlash('ok', 'User updated');
            header('Location: /admin/users');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/users');
        }
        exit;
    }

    public function usersToggleStatus(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            Security::setFlash('error', 'Invalid request');
            header('Location: /admin/users');
            exit;
        }
        try {
            $db = Database::getConnection();
            $current = $db->prepare("SELECT status FROM users WHERE id = :id");
            $current->execute(['id' => $id]);
            $status = $current->fetchColumn();
            $newStatus = ($status === 'Active') ? 'Inactive' : 'Active';
            $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $id]);
            Security::setFlash('ok', "User status changed to {$newStatus}");
            header('Location: /admin/users');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/users');
        }
        exit;
    }

    public function usersResendPassword(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            Security::setFlash('error', 'Invalid request');
            header('Location: /admin/users');
            exit;
        }
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT id, fullname, phone FROM users WHERE id = :id AND deleted_at IS NULL");
            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                Security::setFlash('error', 'User not found');
                header('Location: /admin/users');
                exit;
            }

            $newPassword = bin2hex(random_bytes(4));
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 11]);

            $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->execute(['password' => $hashed, 'id' => $id]);

            $sent = false;
            if (!empty($user['phone'])) {
                $config = $db->query("SELECT sms_endpoint, sms_apikey, gen_sms_sender_id FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
                $message = "Hello {$user['fullname']}, your HRGoTo password has been reset. New password: {$newPassword}. Please login and change your password.";
                $sent = $this->sendOutboundSms($config, $user['phone'], $message, 'Password Reset');
            }

            if ($sent) {
                Security::setFlash('ok', "Password reset for {$user['fullname']}. New password sent via SMS.");
            } else {
                Security::setFlash('ok', "Password reset for {$user['fullname']}. New password: {$newPassword}" . (empty($user['phone']) ? ' (no phone on file - add one to enable SMS)' : ' (SMS delivery failed)'));
            }
            header('Location: /admin/users');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /admin/users');
        }
        exit;
    }

    private function ensureUsersPhoneColumn(PDO $db): void {
        $check = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'phone'");
        $check->execute();
        if ((int)$check->fetchColumn() === 0) {
            $db->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(30) NULL AFTER email");
        }
    }

    private function sendOutboundSms(array $config, string $phone, string $message, string $milestone = 'Bulk'): bool {
        $endPoint = !empty($config['sms_endpoint']) ? trim($config['sms_endpoint']) : 'https://api.mnotify.com/api/sms/quick';
        $apiKey   = !empty($config['sms_apikey']) ? trim($config['sms_apikey']) : '';
        $senderId = !empty($config['gen_sms_sender_id']) ? trim($config['gen_sms_sender_id']) : 'HRGoTo';

        if (empty($phone) || empty($apiKey)) {
            return false;
        }

        $phone = trim(str_replace(' ', '', $phone));
        $url = $endPoint . '?key=' . $apiKey;

        $data = [
            'recipient'     => [$phone],
            'sender'        => $senderId,
            'message'       => $message,
            'is_schedule'   => 'false',
            'schedule_date' => ''
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $result = curl_exec($ch);
            curl_close($ch);

            if ($result !== false) {
                $responseArray = json_decode($result, true);
                if (isset($responseArray['status']) && $responseArray['status'] === 'success') {
                    return true;
                }
            }
        } catch (Throwable $e) {
            error_log("SMS error: " . $e->getMessage());
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO sms_logs (phone, milestone, message, status, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$phone, $milestone, $message, 'Failed']);
        } catch (Throwable $e) {
            error_log("SMS log error: " . $e->getMessage());
        }

        return false;
    }

    public function auditLogs(Request $request): void
    {
        $db = Database::getConnection();

        $page = max(1, (int)($request->input('page') ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $total = (int)$db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));

        $logs = $db->prepare("
            SELECT al.*, u.fullname
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $logs->execute(['limit' => $perPage, 'offset' => $offset]);
        $logs = $logs->fetchAll(\PDO::FETCH_ASSOC);

        $pageTitle = 'Audit Trail';
        $appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
        $suppressGlobalFlash = true;
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/admin/audit_logs.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    private function getProgressFilePath(): string {
        return sys_get_temp_dir() . '/backup_progress_' . session_id() . '.json';
    }

    private function writeBackupProgress(int $percent, string $status, string $message, string $filename = ''): void {
        $startTime = $_SESSION['_backup_start'] ?? $_SERVER['REQUEST_TIME_FLOAT'];
        $elapsed = microtime(true) - $startTime;
        $data = [
            'status' => $status,
            'percent' => min(100, max(0, $percent)),
            'message' => $message,
            'elapsed' => round($elapsed, 1),
            'filename' => $filename,
        ];
        if ($percent > 0) {
            $data['eta'] = round(($elapsed / $percent) * (100 - $percent), 1);
        } else {
            $data['eta'] = 0;
        }
        file_put_contents($this->getProgressFilePath(), json_encode($data), LOCK_EX);
    }

    public function backupIndex(Request $request): void {
        $progressFile = $this->getProgressFilePath();
        if (file_exists($progressFile)) {
            $data = json_decode(file_get_contents($progressFile), true);
            if ($data && ($data['status'] ?? '') === 'completed') {
                @unlink($progressFile);
            }
        }
        $backupDir = __DIR__ . '/../storage/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $files = array_diff(scandir($backupDir) ?: [], ['.', '..']);
        $backups = [];
        foreach ($files as $f) {
            $path = $backupDir . '/' . $f;
            if (is_file($path)) {
                $size = filesize($path);
                $backups[] = [
                    'name' => $f,
                    'size' => $size,
                    'size_hr' => $size >= 1048576
                        ? sprintf('%.1f MB', $size / 1048576)
                        : sprintf('%.1f KB', $size / 1024),
                    'date' => date('Y-m-d H:i:s', filemtime($path)),
                    'type' => strtolower(pathinfo($f, PATHINFO_EXTENSION)),
                ];
            }
        }
        rsort($backups);
        $pageTitle = 'Backup Center';
        $appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
        $csrf = \App\Helpers\Security::generateCsrfToken();
        $suppressGlobalFlash = true;
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/admin/backup_center.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function backupCreateDb(Request $request): void {
        CSRFMiddleware::validate($request);
        header('Content-Type: application/json');
        try {
            $dbName = $_ENV['DB_DATABASE'] ?? 'hrmis';
            $backupDir = __DIR__ . '/../storage/backups';
            if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
            $timestamp = date('Ymd_His');
            $sqlFile = $backupDir . '/db_' . $timestamp . '.sql';
            $fh = fopen($sqlFile, 'w');
            if (!$fh) throw new \RuntimeException('Cannot write backup file.');
            fwrite($fh, "-- HRGoTo HCM Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n-- Database: $dbName\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");
            $_SESSION['_backup_start'] = microtime(true);
            session_write_close();
            $this->writeBackupProgress(0, 'running', 'Preparing database backup...');
            $pdo = Database::getConnection();
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $total = count($tables);
            $this->writeBackupProgress(3, 'running', "Found $total tables to dump");
            foreach ($tables as $i => $table) {
                $pct = (int)round(5 + (($i + 1) / $total) * 80);
                $this->writeBackupProgress($pct, 'running', "Dumping table: $table");
                fwrite($fh, "DROP TABLE IF EXISTS `$table`;\n");
                $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
                $row = $stmt->fetch(\PDO::FETCH_NUM);
                fwrite($fh, $row[1] . ";\n\n");
                $stmt = $pdo->query("SELECT * FROM `$table`");
                while ($dataRow = $stmt->fetch(\PDO::FETCH_NUM)) {
                    $values = [];
                    foreach ($dataRow as $val) {
                        $values[] = $val === null ? 'NULL' : $pdo->quote((string)$val);
                    }
                    fwrite($fh, "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n");
                }
                fwrite($fh, "\n");
            }
            fwrite($fh, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fclose($fh);
            $this->writeBackupProgress(100, 'completed', 'Database backup completed', 'db_' . $timestamp . '.sql');
            echo json_encode(['success' => true, 'file' => 'db_' . $timestamp . '.sql']);
        } catch (\Throwable $e) {
            if (isset($fh) && is_resource($fh)) fclose($fh);
            if (isset($sqlFile) && file_exists($sqlFile)) unlink($sqlFile);
            $this->writeBackupProgress(0, 'error', $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function backupCreateFiles(Request $request): void {
        CSRFMiddleware::validate($request);
        header('Content-Type: application/json');
        try {
            $backupDir = __DIR__ . '/../storage/backups';
            if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
            $timestamp = date('Ymd_His');
            $zipPath = $backupDir . '/files_' . $timestamp . '.zip';
            $uploadsDir = __DIR__ . '/../../public/uploads';
            if (!is_dir($uploadsDir)) throw new \RuntimeException('Uploads directory not found.');
            $_SESSION['_backup_start'] = microtime(true);
            session_write_close();
            $this->writeBackupProgress(0, 'running', 'Preparing file backup...');
            $this->writeBackupProgress(2, 'running', 'Scanning files...');
            $fileList = [];
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($uploadsDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $f) {
                if ($f->isFile()) $fileList[] = $f->getPathname();
            }
            $total = count($fileList);
            if ($total === 0) throw new \RuntimeException('No files found to back up.');
            $this->writeBackupProgress(5, 'running', "Found $total files to compress");
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) throw new \RuntimeException('Failed to create zip archive.');
            $dirLen = strlen(rtrim($uploadsDir, '/\\')) + 1;
            foreach ($fileList as $i => $filePath) {
                $relativePath = substr($filePath, $dirLen);
                $this->writeBackupProgress((int)round(5 + (($i + 1) / $total) * 90), 'running', "Compressing: $relativePath");
                $zip->addFile($filePath, 'uploads/' . $relativePath);
            }
            $zip->close();
            $this->writeBackupProgress(100, 'completed', 'File backup completed', 'files_' . $timestamp . '.zip');
            echo json_encode(['success' => true, 'file' => 'files_' . $timestamp . '.zip']);
        } catch (\Throwable $e) {
            if (isset($zipPath) && file_exists($zipPath)) unlink($zipPath);
            $this->writeBackupProgress(0, 'error', $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function backupProgress(Request $request): void {
        header('Content-Type: application/json');
        $path = $this->getProgressFilePath();
        if (!file_exists($path)) {
            echo json_encode(['status' => 'idle', 'percent' => 0, 'message' => '', 'elapsed' => 0, 'eta' => 0, 'filename' => '']);
            return;
        }
        echo file_get_contents($path);
    }

    public function backupDelete(Request $request): void {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $name = basename((string)($body['name'] ?? ''));
        $backupDir = __DIR__ . '/../storage/backups';
        $path = $backupDir . '/' . $name;
        if ($name && file_exists($path) && is_file($path)) {
            unlink($path);
            Security::setFlash('ok', 'Backup deleted: ' . $name);
        } else {
            Security::setFlash('error', 'Backup file not found.');
        }
        header('Location: /admin/backup');
        exit;
    }

    public function backupDownload(Request $request): void {
        $name = basename((string)($_GET['name'] ?? ''));
        $backupDir = __DIR__ . '/../storage/backups';
        $path = $backupDir . '/' . $name;
        if (!$name || !file_exists($path) || !is_file($path)) {
            Security::setFlash('error', 'Backup file not found.');
            header('Location: /admin/backup');
            exit;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $contentType = $ext === 'sql' ? 'application/sql' : 'application/zip';
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

}