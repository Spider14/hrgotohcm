<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Database;
use App\Helpers\Security;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use PDO;

class RecruitmentController {

    public function __construct() {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Recruiter']);
        $this->ensureTablesExist();
    }

    private function ensureTablesExist(): void {
        $db = Database::getConnection();
        $db->exec("CREATE TABLE IF NOT EXISTS recruitment_rounds (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            job_id INT UNSIGNED NOT NULL,
            stage_name VARCHAR(255) NOT NULL,
            stage_order INT NOT NULL DEFAULT 0,
            passing_score DECIMAL(5,2) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
        )");
        $db->exec("CREATE TABLE IF NOT EXISTS application_rounds (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            round_id INT UNSIGNED NOT NULL,
            application_id INT UNSIGNED NOT NULL,
            score DECIMAL(5,2) DEFAULT NULL,
            comment TEXT DEFAULT NULL,
            status ENUM('pending', 'cleared', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (round_id) REFERENCES recruitment_rounds(id) ON DELETE CASCADE,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            UNIQUE KEY unique_app_round (round_id, application_id)
        )");
        $db->exec("CREATE TABLE IF NOT EXISTS application_status_log (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            application_id INT UNSIGNED NOT NULL,
            old_status VARCHAR(100) DEFAULT NULL,
            new_status VARCHAR(100) NOT NULL,
            changed_by INT UNSIGNED DEFAULT NULL,
            note TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
        )");
        $db->exec("CREATE TABLE IF NOT EXISTS application_interviews (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            application_id INT UNSIGNED NOT NULL,
            interview_date DATE NOT NULL,
            interview_time TIME DEFAULT NULL,
            interview_type VARCHAR(100) DEFAULT NULL,
            interviewer VARCHAR(255) DEFAULT NULL,
            location VARCHAR(255) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            score DECIMAL(5,2) DEFAULT NULL,
            score_comment TEXT DEFAULT NULL,
            created_by INT UNSIGNED DEFAULT NULL,
            status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
        )");
        $db->exec("CREATE TABLE IF NOT EXISTS application_offers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            application_id INT UNSIGNED NOT NULL,
            offered_salary DECIMAL(12,2) DEFAULT NULL,
            offer_date DATE DEFAULT NULL,
            offer_letter_filename VARCHAR(255) DEFAULT NULL,
            offer_letter_original VARCHAR(255) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_by INT UNSIGNED DEFAULT NULL,
            status ENUM('draft', 'sent', 'accepted', 'declined') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
        )");
        $db->exec("CREATE TABLE IF NOT EXISTS talent_pool (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            application_id INT UNSIGNED NOT NULL,
            pool_status ENUM('active','archived','hired') DEFAULT 'active',
            added_by INT UNSIGNED DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            UNIQUE KEY unique_talent_app (application_id)
        )");

        $this->ensureColumn($db, 'jobs', 'shortlist_limit', "INT UNSIGNED NOT NULL DEFAULT 10");
        $this->ensureColumn($db, 'applications', 'rank_score', "DECIMAL(8,2) DEFAULT NULL");
        $this->ensureColumn($db, 'application_interviews', 'score', "DECIMAL(5,2) DEFAULT NULL");
        $this->ensureColumn($db, 'application_interviews', 'score_comment', "TEXT DEFAULT NULL");
        $this->ensureColumn($db, 'sms_campaign_templates', 'interviewed', "TEXT NULL");

        $this->migratePipelineSchema($db);
    }

    private function ensureColumn(PDO $db, string $table, string $column, string $definition): void {
        try {
            $check = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c");
            $check->execute(['t' => $table, 'c' => $column]);
            if ((int)$check->fetchColumn() === 0) {
                $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            }
        } catch (\Throwable $e) { /* table may not exist yet */ }
    }

    private function migratePipelineSchema($db): void {
        // application_status_log: rename from_status/to_status → old_status/new_status
        try {
            $cols = $db->query("SHOW COLUMNS FROM application_status_log")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('from_status', $cols, true)) {
                $db->exec("ALTER TABLE application_status_log CHANGE from_status old_status VARCHAR(100) DEFAULT NULL");
            }
            if (in_array('to_status', $cols, true)) {
                $db->exec("ALTER TABLE application_status_log CHANGE to_status new_status VARCHAR(100) NOT NULL");
            }
        } catch (\Throwable $e) { /* table may not exist yet */ }

        // application_interviews: replace scheduled_at with interview_date + interview_time + interviewer + created_by
        try {
            $cols = $db->query("SHOW COLUMNS FROM application_interviews")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('scheduled_at', $cols, true)) {
                $db->exec("ALTER TABLE application_interviews DROP COLUMN scheduled_at");
                $db->exec("ALTER TABLE application_interviews ADD COLUMN interview_date DATE NOT NULL AFTER application_id");
                $db->exec("ALTER TABLE application_interviews ADD COLUMN interview_time TIME DEFAULT NULL AFTER interview_date");
            }
            if (!in_array('interviewer', $cols, true)) {
                $db->exec("ALTER TABLE application_interviews ADD COLUMN interviewer VARCHAR(255) DEFAULT NULL AFTER interview_type");
            }
            if (!in_array('created_by', $cols, true)) {
                $db->exec("ALTER TABLE application_interviews ADD COLUMN created_by INT UNSIGNED DEFAULT NULL AFTER notes");
            }
        } catch (\Throwable $e) { /* table may not exist yet */ }

        // application_offers: replace offered_position/offer_amount/offer_letter_url with new columns
        try {
            $cols = $db->query("SHOW COLUMNS FROM application_offers")->fetchAll(PDO::FETCH_COLUMN);
            $needsAlter = in_array('offered_position', $cols, true);
            if ($needsAlter) {
                if (in_array('offered_position', $cols, true)) $db->exec("ALTER TABLE application_offers DROP COLUMN offered_position");
                if (in_array('offer_amount', $cols, true)) $db->exec("ALTER TABLE application_offers DROP COLUMN offer_amount");
                if (in_array('offer_letter_url', $cols, true)) $db->exec("ALTER TABLE application_offers DROP COLUMN offer_letter_url");
                $db->exec("ALTER TABLE application_offers ADD COLUMN offered_salary DECIMAL(12,2) DEFAULT NULL AFTER application_id");
                $db->exec("ALTER TABLE application_offers ADD COLUMN offer_date DATE DEFAULT NULL AFTER offered_salary");
                $db->exec("ALTER TABLE application_offers ADD COLUMN offer_letter_filename VARCHAR(255) DEFAULT NULL AFTER offer_date");
                $db->exec("ALTER TABLE application_offers ADD COLUMN offer_letter_original VARCHAR(255) DEFAULT NULL AFTER offer_letter_filename");
            }
            if (!in_array('notes', $cols, true)) {
                $db->exec("ALTER TABLE application_offers ADD COLUMN notes TEXT DEFAULT NULL AFTER offer_letter_original");
            }
            if (!in_array('created_by', $cols, true)) {
                $db->exec("ALTER TABLE application_offers ADD COLUMN created_by INT UNSIGNED DEFAULT NULL AFTER notes");
            }
        } catch (\Throwable $e) { /* table may not exist yet */ }
    }

    /**
     * Display Recruitment Main Metric Dashboard View
     */
    public function index(Request $request): void {
        $db = Database::getConnection();

        // Fetch Aggregation Totals for Row 1
        $metrics = $db->query("
            SELECT 
                COUNT(*) as total_applicants,
                SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_review,
                SUM(CASE WHEN a.status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted,
                (SELECT COUNT(*) FROM jobs) as open_positions,
                SUM(CASE WHEN LOWER(j.title) = 'lecturer' THEN 1 ELSE 0 END) as academic_applicants
            FROM applications a
            LEFT JOIN jobs j ON a.job_id = j.id
        ")->fetch(PDO::FETCH_ASSOC);

        $totalApplicants = (int)($metrics['total_applicants'] ?? 0);
        $academicApplicants = (int)($metrics['academic_applicants'] ?? 0);
        $administrativeApplicants = $totalApplicants - $academicApplicants;
		
        $stats = [
            'total'          => $totalApplicants,
            'pending'        => (int)($metrics['pending_review'] ?? 0),
            'shortlisted'    => (int)($metrics['shortlisted'] ?? 0),
            'open_jobs'      => (int)($metrics['open_positions'] ?? 0),
            'academic'       => $academicApplicants,
            'administrative' => $administrativeApplicants
        ];

        // Fetch Department/Directorate Breakdown for Row 2
        $deptBreakdown = $db->query("
            SELECT 
                COALESCE(j.department, 'General Application') as department_name,
                COUNT(a.id) as total_count
            FROM applications a
            LEFT JOIN jobs j ON a.job_id = j.id
            GROUP BY j.department
            ORDER BY total_count DESC, department_name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Query Relational Applicant Records for DataTables Row 3
        $query = "
            SELECT 
                a.id,
                a.reference_number,
				a.phone,
                a.first_name,
                a.last_name,
                a.highest_qualification,
                a.years_experience,
                a.status,
                a.submitted_at,
                CONCAT(COALESCE(j.title, 'General Application'), ' - ', j.department) AS job_title
            FROM applications a
            LEFT JOIN jobs j ON a.job_id = j.id
            ORDER BY a.submitted_at DESC
        ";
        $applicants = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = "HRGoTo HCM - Recruitment Dashboard";
    $appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');

        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/recruitment/dashboard.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    /**
     * Jobs Management — List, Create, Edit, Delete, Toggle Status
     */
    public function jobsIndex(Request $request): void {
        $db = Database::getConnection();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFMiddleware::validate($request);
            $action = $_POST['action'] ?? '';

            if ($action === 'create' || $action === 'update') {
                $title        = trim($_POST['title'] ?? '');
                $department   = trim($_POST['department'] ?? '');
                $description  = trim($_POST['description'] ?? '');
                $requirements = trim($_POST['requirements'] ?? '');
                $location     = trim($_POST['location'] ?? '');
                $type         = trim($_POST['type'] ?? 'full-time');
                $salary_range = trim($_POST['salary_range'] ?? '');
                $deadline     = $_POST['deadline'] ?? null;

                if (empty($title) || empty($department)) {
                    Security::setFlash('error', 'Job title and department are required.');
                    header('Location: /recruitment/jobs');
                    exit;
                }

                if ($action === 'create') {
                    $stmt = $db->prepare("INSERT INTO jobs (title, department, description, requirements, location, type, salary_range, deadline, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'open')");
                    $stmt->execute([$title, $department, $description, $requirements, $location, $type, $salary_range, $deadline, $_SESSION['user_id'] ?? 1]);
                    foreach ($db->query("SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.role_name IN ('Super Admin', 'HR Manager') AND u.deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN) as $sId) {
                        \App\Helpers\Notification::create((int)$sId, 'New Job Created', "Job \"{$title}\" has been created.", 'info');
                    }
                } else {
                    $id = (int)($_POST['id'] ?? 0);
                    $stmt = $db->prepare("UPDATE jobs SET title=?, department=?, description=?, requirements=?, location=?, type=?, salary_range=?, deadline=? WHERE id=?");
                    $stmt->execute([$title, $department, $description, $requirements, $location, $type, $salary_range, $deadline, $id]);
                }
                Security::setFlash('ok', $action === 'create' ? 'Job created successfully.' : 'Job updated successfully.');
                header('Location: /recruitment/jobs');
                exit;
            }

            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $db->prepare("DELETE FROM jobs WHERE id=?");
                $stmt->execute([$id]);
                Security::setFlash('ok', 'Job deleted.');
                header('Location: /recruitment/jobs');
                exit;
            }

            if ($action === 'toggle-status') {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $db->prepare("SELECT status FROM jobs WHERE id=?");
                $stmt->execute([$id]);
                $job = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($job) {
                    $newStatus = $job['status'] === 'open' ? 'closed' : 'open';
                    $stmt = $db->prepare("UPDATE jobs SET status=? WHERE id=?");
                    $stmt->execute([$newStatus, $id]);
                }
                header('Location: /recruitment/jobs');
                exit;
            }
        }

        $jobs = $db->query("SELECT j.*, (SELECT COUNT(*) FROM applications WHERE job_id=j.id) AS applicant_count FROM jobs j ORDER BY j.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

        $departments = $db->query("SELECT dept_name FROM departments ORDER BY dept_name")->fetchAll(PDO::FETCH_COLUMN);
        $ranks = $db->query("SELECT rank_name FROM ranks WHERE is_active = 1 ORDER BY rank_order")->fetchAll(PDO::FETCH_COLUMN);

        $pageTitle = "HRGoTo HCM - Job Postings";
        $appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/recruitment/jobs.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    /**
     * Rounds Management — Define stages per job, assign/score applicants
     */
    public function roundsIndex(Request $request): void {
        $db = Database::getConnection();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFMiddleware::validate($request);
            $action = $_POST['action'] ?? '';

            if ($action === 'create-round') {
                $jobId    = (int)($_POST['job_id'] ?? 0);
                $name     = trim($_POST['stage_name'] ?? '');
                $order    = (int)($_POST['stage_order'] ?? 0);
                $passScore = !empty($_POST['passing_score']) ? (float)$_POST['passing_score'] : null;
                if ($jobId && $name) {
                    $stmt = $db->prepare("INSERT INTO recruitment_rounds (job_id, stage_name, stage_order, passing_score) VALUES (?,?,?,?)");
                    $stmt->execute([$jobId, $name, $order, $passScore]);
                }
                header('Location: /recruitment/rounds');
                exit;
            }

            if ($action === 'update-round') {
                $id       = (int)($_POST['id'] ?? 0);
                $name     = trim($_POST['stage_name'] ?? '');
                $order    = (int)($_POST['stage_order'] ?? 0);
                $passScore = !empty($_POST['passing_score']) ? (float)$_POST['passing_score'] : null;
                if ($id && $name) {
                    $stmt = $db->prepare("UPDATE recruitment_rounds SET stage_name=?, stage_order=?, passing_score=? WHERE id=?");
                    $stmt->execute([$name, $order, $passScore, $id]);
                }
                header('Location: /recruitment/rounds');
                exit;
            }

            if ($action === 'delete-round') {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $db->prepare("DELETE FROM recruitment_rounds WHERE id=?");
                $stmt->execute([$id]);
                header('Location: /recruitment/rounds');
                exit;
            }

            if ($action === 'assign') {
                $roundId = (int)($_POST['round_id'] ?? 0);
                $appIds  = $_POST['application_ids'] ?? [];
                if ($roundId && !empty($appIds)) {
                    $stmt = $db->prepare("INSERT IGNORE INTO application_rounds (round_id, application_id) VALUES (?,?)");
                    foreach ($appIds as $aid) {
                        $stmt->execute([$roundId, (int)$aid]);
                    }
                }
                header('Location: /recruitment/rounds');
                exit;
            }

            if ($action === 'score') {
                $arId   = (int)($_POST['ar_id'] ?? 0);
                $score  = !empty($_POST['score']) ? (float)$_POST['score'] : null;
                $comment = trim($_POST['comment'] ?? '');
                $status = $_POST['status'] ?? 'pending';
                if ($arId) {
                    $stmt = $db->prepare("UPDATE application_rounds SET score=?, comment=?, status=? WHERE id=?");
                    $stmt->execute([$score, $comment, $status, $arId]);
                }
                header('Location: /recruitment/rounds');
                exit;
            }
        }

        $jobs = $db->query("SELECT id, title FROM jobs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

        $rounds = [];
        $roundStmt = $db->query(
            "SELECT rr.*, j.title AS job_title 
             FROM recruitment_rounds rr 
             JOIN jobs j ON j.id = rr.job_id 
             ORDER BY j.title, rr.stage_order"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Build a structured array: job_id => [ job_title, rounds => [ ... ] ]
        $roundsByJob = [];
        foreach ($roundStmt as $r) {
            $jid = $r['job_id'];
            if (!isset($roundsByJob[$jid])) {
                $roundsByJob[$jid] = ['job_title' => $r['job_title'], 'rounds' => []];
            }
            $roundsByJob[$jid]['rounds'][] = $r;
        }

        // For assign modals: get pending applications per job
        $applicationsByJob = [];
        $appStmt = $db->query(
            "SELECT a.id, a.first_name, a.last_name, a.reference_number, a.job_id 
             FROM applications a 
             WHERE a.status IN ('pending','reviewing','shortlisted')
             ORDER BY a.last_name"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($appStmt as $a) {
            $applicationsByJob[$a['job_id']][] = $a;
        }

        // Application-round assignments for display
        $arStmt = $db->query(
            "SELECT ar.*, a.first_name, a.last_name, a.reference_number, rr.stage_name, rr.job_id, COALESCE(j.title, 'N/A') AS job_title
             FROM application_rounds ar
             JOIN recruitment_rounds rr ON rr.id = ar.round_id
             JOIN applications a ON a.id = ar.application_id
             LEFT JOIN jobs j ON j.id = rr.job_id
             ORDER BY rr.job_id, rr.stage_order, a.last_name"
        )->fetchAll(PDO::FETCH_ASSOC);

        $assignments = [];
        foreach ($arStmt as $ar) {
            $assignments[] = $ar;
        }

        $pageTitle = "HRGoTo HCM - Recruitment Rounds";
        $appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/recruitment/rounds.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    /**
     * View Detailed Applicant Profile
     */
    public function viewApplicant(Request $request): void {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            die("Invalid applicant identification payload matching this context.");
        }

        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT a.*, COALESCE(j.title, 'General Application') as job_title, j.department    
            FROM applications a 
            LEFT JOIN jobs j ON a.job_id = j.id 
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            die("The specified applicant records could not be found within this terminal.");
        }

        $stmt = $db->prepare("SELECT * FROM education_entries WHERE application_id = ? ORDER BY from_year DESC");
        $stmt->execute([$id]);
        $educationEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT * FROM employment_entries WHERE application_id = ? ORDER BY from_date DESC");
        $stmt->execute([$id]);
        $employmentEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT * FROM application_files WHERE application_id = ?");
        $stmt->execute([$id]);
        $uploadedFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrf = $_SESSION['csrf_token']; 

        $pageTitle = "Review Application Profile - " . \App\Helpers\Security::escape(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));

        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/recruitment/view.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    /**
     * Handle Workflow Status Modification Actions with Multi-channel Notifications
     */
    public function updateStatus(Request $request): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die("Invalid request method strategy invoked.");
        }

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            die("CSRF token verification failed configuration matching constraints.");
        }

        $id = isset($_POST['applicant_id']) ? (int)$_POST['applicant_id'] : 0;
        $status = $_POST['status'] ?? 'pending';
        $hrNotes = $_POST['hr_notes'] ?? '';

        if ($id <= 0) {
            die("Missing required profile entity identifiers.");
        }

        $db = Database::getConnection();
        
        // Extended Query context: Pull primary info along with bound relational job data parameters
        $stmt = $db->prepare("
            SELECT a.first_name, a.last_name, a.phone, a.email, 
                   COALESCE(j.title, 'General Application') as job_title, j.department 
            FROM applications a
            LEFT JOIN jobs j ON a.job_id = j.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            die("Applicant profile target records could not be resolved.");
        }

        $fullName     = trim(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
        $phoneNumber  = trim($app['phone'] ?? '');
        $emailAddress = trim($app['email'] ?? '');

        // Gather standard config sets for notification routes
        $config = $db->query("SELECT sms_endpoint, gen_sms_sender_id, sms_apikey FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        switch ($status) {
            case 'reviewing':
                $targetStatus = 'Under review';
                break;

            case 'shortlisted':
                $targetStatus = 'Shortlisted';
                $smsCampaign = $db->query("SELECT shortlisted FROM sms_campaign_templates LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                
                if ($config && $smsCampaign && !empty($phoneNumber)) {
                    $rawMessage = $smsCampaign['shortlisted'] ?? '';
                    $customMessage = str_replace('[fullname]', $fullName, $rawMessage);
                    $this->sendOutboundSms($config, $phoneNumber, $customMessage);
                }
                break;

            case 'interviewed':
                $targetStatus = 'Interviewed';
                break;

            case 'unsuccessful':
                $targetStatus = 'Unsuccessful';
                break;

            case 'hired':
                $targetStatus = 'Hired';
                $smsCampaign = $db->query("SELECT hired FROM sms_campaign_templates LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                
                if ($config && $smsCampaign && !empty($phoneNumber)) {
                    $rawMessage = $smsCampaign['hired'] ?? '';
                    $customMessage = str_replace('[fullname]', $fullName, $rawMessage);
                    $this->sendOutboundSms($config, $phoneNumber, $customMessage);
                }
                
                // Note: PHPMailer and Word Document Generation logic will be placed right below this line in Stage 2/3.
                break;

            default:
                $targetStatus = 'pending';
                break;
        }

        // Apply mutation changes down to system records
        $stmt = $db->prepare("UPDATE applications SET status = ?, hr_notes = ? WHERE id = ?");
        $stmt->execute([$targetStatus, $hrNotes, $id]);

        foreach ($db->query("SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.role_name IN ('Super Admin', 'HR Manager') AND u.deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN) as $sId) {
            \App\Helpers\Notification::create((int)$sId, 'Application Status Updated', "Applicant {$fullName} status changed to {$targetStatus}.", 'info');
        }
        header("Location: /recruitment/view?id=" . $id);
        exit;
    }

    /**
     * PRIVATE MNOTIFY OUTBOUND SMS GATEWAY: Natively aligned with JSON payload constraints
     */
    private function sendOutboundSms(array $config, string $phone, string $message): bool {
        $endPoint = !empty($config['sms_endpoint']) ? trim($config['sms_endpoint']) : 'https://api.mnotify.com/api/sms/quick';
        $apiKey   = !empty($config['sms_apikey']) ? trim($config['sms_apikey']) : '';
        $senderId = !empty($config['gen_sms_sender_id']) ? trim($config['gen_sms_sender_id']) : 'HRGoTo';

        if (empty($phone) || empty($apiKey)) {
            return false;
        }

        // Standardize the phone number into a clean string
        $phone = trim(str_replace(' ', '', $phone));
        $url = $endPoint . '?key=' . $apiKey;

        // Structure payload exactly how MNotify expects it (Recipient must be an array)
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
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            
            $result = curl_exec($ch);
            curl_close($ch);
            
            if ($result === false) {
                return false;
            }

            $responseArray = json_decode($result, true);
            return isset($responseArray['status']) && $responseArray['status'] === 'success';

        } catch (\Exception $e) {
            error_log("SMS dispatch execution exception error context: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Interface view showing progress loader and dynamic downland mechanics.
     */
    public function compileReport(Request $request): void {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            die("Invalid applicant payload identifier.");
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT first_name, last_name, reference_number FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            die("Applicant profile could not be resolved.");
        }

        $fullName = trim(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
        $refNum = str_replace('/', '_', $app['reference_number']);
        
        // Inline progress screen style mapping
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Assembling Comprehensive Application Dossier</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                .progress-card { background: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); text-align: center; width: 480px; }
                h2 { color: #1a202c; margin-bottom: 8px; font-size: 20px; }
                p { color: #718096; font-size: 14px; margin-bottom: 24px; }
                .progress-bar-container { background: #e2e8f0; border-radius: 100px; width: 100%; height: 12px; overflow: hidden; position: relative; margin-bottom: 20px; }
                .progress-bar-fill { background: linear-gradient(90deg, #3182ce, #2b6cb0); width: 0%; height: 100%; border-radius: 100px; transition: width 0.4s ease-in-out; }
                .status-percentage { font-weight: bold; color: #2b6cb0; font-size: 16px; margin-bottom: 24px; }
                .btn-download { display: inline-none; background-color: #2f855a; color: white; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.2s ease; border: none; cursor: pointer; }
                .btn-download:hover { background-color: #22543d; transform: translateY(-1px); }
                .error-box { display: none; background-color: #fff5f5; color: #c53030; padding: 15px; border-radius: 6px; border: 1px solid #fed7d7; font-size: 13px; text-align: left; overflow-x: auto; margin-top: 15px; }
                .spinner-icon { color: #3182ce; font-size: 40px; margin-bottom: 15px; animation: spin 1.5s linear infinite; }
                @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            </style>
        </head>
        <body>
            <div class="progress-card">
                <div id="loaderIcon"><i class="fa-solid fa-circle-notch spinner-icon"></i></div>
                <h2 id="titleText">Assembling Master Report Dossier</h2>
                <p id="subtext">Compiling candidate profiles and aggregating uploaded verification matrices...</p>
                
                <div class="progress-bar-container">
                    <div id="barFill" class="progress-bar-fill"></div>
                </div>
                <div id="percentageText" class="status-percentage">0%</div>
                
                <div id="actionContainer">
                    <button id="dlButton" class="btn-download" style="display: none;" onclick="triggerDownload()">
                        <i class="fa-solid fa-cloud-arrow-down" style="margin-right:8px;"></i> Download Full PDF Report
                    </button>
                </div>
                
                <div id="errorContainer" class="error-box"></div>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    let fill = document.getElementById("barFill");
                    let text = document.getElementById("percentageText");
                    let currentProgress = 0;

                    // Fake increment progress initialization for sleek user tracking
                    let progressInterval = setInterval(() => {
                        if (currentProgress < 85) {
                            currentProgress += Math.floor(Math.random() * 8) + 2;
                            if (currentProgress > 85) currentProgress = 85;
                            fill.style.width = currentProgress + "%";
                            text.innerText = currentProgress + "%";
                        }
                    }, 250);

                    // Execute actual non-blocking engine compilation payload background request
                    fetch('/recruitment/generate-report-endpoint?id=<?php echo $id; ?>')
                        .then(response => response.json())
                        .then(data => {
                            clearInterval(progressInterval);
                            if (data.status === 'success') {
                                fill.style.width = "100%";
                                text.innerText = "100%";
                                text.style.color = "#2f855a";
                                document.getElementById("loaderIcon").innerHTML = '<i class="fa-solid fa-circle-check" style="color:#2f855a; font-size:40px; margin-bottom:15px;"></i>';
                                document.getElementById("titleText").innerText = "Compilation Complete";
                                document.getElementById("subtext").innerText = "Dossier package assembled successfully.";
                                
                                let dlBtn = document.getElementById("dlButton");
                                dlBtn.style.display = "inline-block";
                                // Store secure filename payload targets dynamically
                                dlBtn.setAttribute("data-target", data.file_token);
                            } else {
                                handleCompilationFailure(data.message || "Unknown internal compiler generation fault.");
                            }
                        })
                        .catch(err => {
                            clearInterval(progressInterval);
                            handleCompilationFailure(err.toString());
                        });
                });

                function handleCompilationFailure(errorMsg) {
                    document.getElementById("barFill").style.backgroundColor = "#c53030";
                    document.getElementById("percentageText").innerText = "Fault Exception Raised";
                    document.getElementById("percentageText").style.color = "#c53030";
                    document.getElementById("loaderIcon").innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color:#c53030; font-size:40px; margin-bottom:15px;"></i>';
                    let errBox = document.getElementById("errorContainer");
                    errBox.style.display = "block";
                    errBox.innerText = errorMsg;
                }

                function triggerDownload() {
                    let token = document.getElementById("dlButton").getAttribute("data-target");
                    if(token) {
                        window.location.href = '/recruitment/download-file?token=' + encodeURIComponent(token);
                    }
                }
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * ASYNCHRONOUS COMPILATION BACKGROUND ENGINE (Shielded Architecture with Ghostscript Repair)
     * Target Router Method mapping: generateReportEndpoint
     */
    public function generateReportEndpoint(Request $request): void {
        // Activate full output buffering capture to trap any accidental warnings, notices, or early outputs
        ob_start();
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Invalid applicant payload identifier context.']);
            exit;
        }

        try {
            $db = Database::getConnection();

            // 1. Fetch targeted applicant record values alongside left-joined job characteristics
            $stmt = $db->prepare("
                SELECT a.*, COALESCE(j.title, 'General Application') as job_title, j.department    
                FROM applications a 
                LEFT JOIN jobs j ON a.job_id = j.id 
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $app = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$app) {
                ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'Applicant profile dataset components are unresolvable.']);
                exit;
            }

            // 2. Fetch associated educational track rows
            $stmt = $db->prepare("SELECT * FROM education_entries WHERE application_id = ? ORDER BY from_year DESC");
            $stmt->execute([$id]);
            $educationEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Fetch professional background milestones mapping exactly to your schema keys
            $stmt = $db->prepare("SELECT * FROM employment_entries WHERE application_id = ? ORDER BY sort_order ASC, from_date DESC");
            $stmt->execute([$id]);
            $employmentEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 4. Fetch all uploaded verification matrices/files
            $stmt = $db->prepare("SELECT stored_name FROM application_files WHERE application_id = ?");
            $stmt->execute([$id]);
            $uploadedFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Establish physical standard file system directory configurations matching your layout rooms
            $rootPath   = dirname(__DIR__, 2);
            $photosDir  = $rootPath . '/public/uploads/photos/';
            $docsDir    = $rootPath . '/public/uploads/cvs/';
            $outputDir  = $rootPath . '/public/uploads/compiled_reports/';

            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $refNum = str_replace('/', '_', $app['reference_number']);
            $summaryPdfPath = $outputDir . "summary_tmp_" . $id . ".pdf";
            $finalMasterReportName = strtoupper($app['first_name']) . '_' . strtoupper($app['last_name']) . '_' . "FULL_REPORT_" . $refNum . ".pdf";
            $finalMasterReportPath = $outputDir . $finalMasterReportName;

            // --- PHASE A: COMPILING COMPREHENSIVE DOSSIER SUMMARY VIA TCPDF ---
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator('HRGoTo HCM Engine');
            $pdf->SetTitle('Master Profile Dossier - ' . ($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 15);
            $pdf->AddPage();

            $photoFilename = !empty($app['passport_photo_filename']) ? basename($app['passport_photo_filename']) : '';
            $absolutePhotoPath = $photosDir . $photoFilename;
            $imgHtml = '<div style="width:120px; height:150px; background-color:#f8f9fc; border:1px solid #eaecf4; text-align:center; line-height:150px; color:#b7b9cc; font-size:10px;">No Photo Attached</div>';
            
            if (!empty($photoFilename) && file_exists($absolutePhotoPath)) {
                $imgHtml = '<img src="' . $absolutePhotoPath . '" style="width:120px; height:150px; border-radius:4px;" />';
            }

            // Section 1: Core Personal Data Profile Header Layout
            $html = '
            <span style="font-size:10px; color:#718096; font-weight: bold; letter-spacing:0.5px;">BOLGATANGA TECHNICAL UNIVERSITY &middot; HR REGISTRY</span><br/>
            <h1 style="font-size:22px; color:#1a202c; margin-top:2px; font-weight:bold;">' . strtoupper(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? '')) . '</h1>
            <p style="font-size:11px; color:#4a5568;"><b>Target Designation:</b> ' . htmlspecialchars($app['job_title'] ?? 'General Application') . ' &middot; <b>Dossier ID Reference:</b> ' . htmlspecialchars($app['reference_number'] ?? 'N/A') . '</p>
            <hr style="color:#e2e8f0;"/>
            <br/><br/>
            
            <table cellpadding="4" cellspacing="0" style="width:100%; border-bottom:2px solid #1a202c; background-color:#212529;">
                <tr>
                    <td><span style="font-size:12px; color:#ffffff; font-weight:bold;">&nbsp;&nbsp;I. MASTER PERSONAL DATA PROFILE</span></td>
                </tr>
            </table>
            <br/>
            <table cellpadding="4" cellspacing="0" style="width:100%;">
                <tr>
                    <td style="width:70%;">
                        <table cellpadding="3" cellspacing="0" style="width:100%; font-size:10px; color:#2d3748;">
                            <tr><td style="width:38%;"><b>Full Legal Name:</b></td><td>' . htmlspecialchars(($app['title'] ?? '') . ' ' . ($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? '')) . '</td></tr>
                            <tr><td><b>Email Channel:</b></td><td>' . htmlspecialchars($app['email'] ?? 'N/A') . '</td></tr>
                            <tr><td><b>Mobile Contact Phone:</b></td><td>' . htmlspecialchars($app['phone'] ?? 'N/A') . '</td></tr>
                            <tr><td><b>Gender / Identity:</b></td><td>' . ucfirst(htmlspecialchars($app['gender'] ?? 'N/A')) . '</td></tr>
                            <tr><td><b>Date of Birth:</b></td><td>' . htmlspecialchars($app['date_of_birth'] ?? 'N/A') . '</td></tr>
                            <tr><td><b>Nationality / Region:</b></td><td>' . htmlspecialchars($app['nationality'] ?? 'N/A') . ' (' . htmlspecialchars($app['region'] ?? 'N/A') . ' Region)</td></tr>
                            <tr><td><b>Home Town / Birthplace:</b></td><td>' . htmlspecialchars($app['home_town'] ?? 'N/A') . ' / ' . htmlspecialchars($app['place_of_birth'] ?? 'N/A') . '</td></tr>
                            <tr><td><b>Postal/Mailing Address:</b></td><td>' . htmlspecialchars($app['address'] ?? 'N/A') . '</td></tr>
                            <tr><td><b>Marital Status:</b></td><td>' . ucfirst(htmlspecialchars($app['marital_status'] ?? 'N/A')) . '</td></tr>
                            <tr><td><b>Ghana Card (NIA) Number:</b></td><td>' . strtoupper(htmlspecialchars($app['ghana_card_number'] ?? 'N/A')) . '</td></tr>
                            <tr><td><b>GRA Tax TIN/PIN:</b></td><td>' . strtoupper(htmlspecialchars($app['gra_pin'] ?? 'N/A')) . '</td></tr>
                        </table>
                    </td>
                    <td style="width:30%; text-align:right; vertical-align:top;">' . $imgHtml . '</td>
                </tr>
            </table>
            <br/><br/>';

            // Section 2: Educational Qualifications Context Layer
            $html .= '
            <table cellpadding="5" cellspacing="0" style="width:100%; background-color:#212529;">
                <tr>
                    <td><span style="font-size:12px; color:#ffffff; font-weight:bold;">&nbsp;&nbsp;II. EDUCATIONAL QUALIFICATION</span></td>
                </tr>
            </table>
            <br/>';
            
            if (!empty($educationEntries)) {
                $html .= '
                <table cellpadding="6" cellspacing="0" style="width:100%; font-size:11px;">
                    <tr style="background-color:#f8f9fa; font-weight:bold; color:#495057;">
                        <th style="width:50%; border-bottom:1px solid #dee2e6;">Institution Attended</th>
                        <th style="width:30%; border-bottom:1px solid #dee2e6;">Certificate</th>
                        <th style="width:10%; border-bottom:1px solid #dee2e6; text-align:center;">From</th>
                        <th style="width:10%; border-bottom:1px solid #dee2e6; text-align:center;">To</th>
                    </tr>';
                foreach ($educationEntries as $edu) {
                    $html .= '
                    <tr>
                        <td style="font-weight:bold; color:#222222; border-bottom:1px solid #f1f1f1;">' . htmlspecialchars($edu['institution'] ?? 'N/A') . '</td>
                        <td style="border-bottom:1px solid #f1f1f1;">' . htmlspecialchars($edu['certificate'] ?? 'N/A') . '</td>
                        <td style="text-align:center; color:#6c757d; border-bottom:1px solid #f1f1f1;">' . htmlspecialchars($edu['from_year'] ?? 'N/A') . '</td>
                        <td style="text-align:center; color:#6c757d; border-bottom:1px solid #f1f1f1;">' . htmlspecialchars($edu['to_year'] ?? 'N/A') . '</td>
                    </tr>';
                }
                $html .= '</table>';
            } else {
                $html .= '<div style="text-align:center; font-size:11px; color:#6c757d; padding:15px;">No education history logged.</div>';
            }

            // Summary Educational Sub-Grid Block Layout
            $html .= '
            <br/>
            <table cellpadding="6" cellspacing="0" style="width:100%; background-color:#ffffff; border-top:1px solid #eeeeee; font-size:11px;">
                <tr>
                    <td style="width:33.3%;">
                        <span style="font-size:10px; color:#6c757d; font-weight:bold;">Highest Qualification</span><br/>
                        <span style="font-weight:bold; color:#212529;">' . htmlspecialchars_decode($app['highest_qualification'] ?? 'N/A') . '</span>
                    </td>
                    <td style="width:33.3%;">
                        <span style="font-size:10px; color:#6c757d; font-weight:bold;">Institution</span><br/>
                        <span style="font-weight:500; color:#444444;">' . htmlspecialchars($app['institution'] ?? 'N/A') . '</span>
                    </td>
                    <td style="width:33.3%;">
                        <span style="font-size:10px; color:#6c757d; font-weight:bold;">Years of Experience</span><br/>
                        <span style="font-weight:bold; color:#198754;">' . htmlspecialchars($app['years_experience'] ?? '0') . ' Year(s)</span>
                    </td>
                </tr>
            </table>
            <br/><br/>';

            // Section 3: Professional Employment History Timeline
            $html .= '
            <table cellpadding="5" cellspacing="0" style="width:100%; background-color:#212529;">
                <tr>
                    <td><span style="font-size:12px; color:#ffffff; font-weight:bold;">&nbsp;&nbsp;III. EMPLOYMENT RECORD</span></td>
                </tr>
            </table>
            <br/>';
            
            if (!empty($employmentEntries)) {
                foreach ($employmentEntries as $idx => $emp) {
                    $isPresent = ($idx === 0);
                    $isFirstPrev = ($idx === 1);

                    if ($isPresent) {
                        $html .= '<p style="font-size:11px; color:#4e73df; font-weight:bold; text-transform:uppercase; margin-bottom:5px;">&raquo; Present Employment</p>';
                    } elseif ($isFirstPrev) {
                        $html .= '<p style="font-size:11px; color:#4e73df; font-weight:bold; text-transform:uppercase; margin-top:15px; margin-bottom:5px;">&raquo; Previous Employment History</p>';
                    }

                    // Native container box mimicking background layout via inline HTML table blocks
                    $html .= '
                    <table cellpadding="8" cellspacing="0" style="width:100%; background-color:#f8f9fa; border-left:4px solid #4e73df; margin-bottom:10px; font-size:11px;">
                        <tr>
                            <td style="width:15%;">
                                <span style="font-size:9px; color:#6c757d; font-weight:bold;">From</span><br/>
                                <b>' . htmlspecialchars($emp['from_date'] ?? 'N/A') . '</b>
                            </td>
                            <td style="width:20%;">
                                <span style="font-size:9px; color:#6c757d; font-weight:bold;">To</span><br/>
                                <b>' . ($isPresent ? '<span style="color:#198754; font-weight:bold;">Current Role</span>' : htmlspecialchars($emp['to_date'] ?? 'N/A')) . '</b>
                            </td>
                            <td style="width:40%;">
                                <span style="font-size:9px; color:#6c757d; font-weight:bold;">Institution Profile</span><br/>
                                <span style="font-weight:bold; color:#222222;">' . htmlspecialchars($emp['institution_name'] ?? 'N/A') . '</span>' .
                                (!empty($emp['institution_address']) ? '<br/><span style="font-size:10px; color:#6c757d;">' . htmlspecialchars($emp['institution_address']) . '</span>' : '') . '
                            </td>
                            <td style="width:25%;">
                                <span style="font-size:9px; color:#6c757d; font-weight:bold;">Position</span><br/>
                                <span style="font-weight:bold; color:#4e73df;">' . htmlspecialchars($emp['position'] ?? 'N/A') . '</span>
                            </td>
                        </tr>';

                        if (!empty($emp['subject_work'])) {
                            $html .= '
                            <tr>
                                <td colspan="4" style="border-top:1px solid #eaecf4; padding-top:6px;">
                                    <span style="font-size:9px; color:#6c757d; font-weight:bold;">Key Responsibility / Scope of Work</span><br/>
                                    <span style="color:#555555; line-height:1.4;">' . nl2br(htmlspecialchars_decode($emp['subject_work'])) . '</span>
                                </td>
                            </tr>';
                        }

                        if (!$isPresent && (!empty($emp['nature']) || !empty($emp['reason_for_leaving']))) {
                            $html .= '
                            <tr>
                                <td colspan="2" style="border-top:1px dotted #eaecf4; padding-top:6px;">
                                    <span style="font-size:9px; color:#6c757d; font-weight:bold;">Classification</span><br/>
                                    <span>' . (($emp['nature'] ?? '') === 'part-time' ? 'Part Time' : 'Full Time') . '</span>
                                </td>
                                <td colspan="2" style="border-top:1px dotted #eaecf4; padding-top:6px;">
                                    <span style="font-size:9px; color:#6c757d; font-weight:bold;">Reason for Leaving</span><br/>
                                    <span style="color:#ca3b27;">' . htmlspecialchars($emp['reason_for_leaving'] ?? 'N/A') . '</span>
                                </td>
                            </tr>';
                        }
                    $html .= '</table><br/>';
                }
            } else {
                $html .= '<div style="text-align:center; font-size:11px; color:#6c757d; padding:15px;">No active or historic employment timeline mapped.</div>';
            }
            $html .= '<br/>';

            // Section 4: Publications & Suitability Statements Card Configuration
            if (!empty($app['publications']) || !empty($app['relevance_statement'])) {
                $html .= '
                <table cellpadding="5" cellspacing="0" style="width:100%; background-color:#212529;">
                    <tr>
                        <td><span style="font-size:12px; color:#ffffff; font-weight:bold;">&nbsp;&nbsp;IV. PUBLICATIONS & RELEVANCE</span></td>
                    </tr>
                </table>
                <br/>';
                
                if (!empty($app['publications'])) {
                    $html .= '
                    <div style="margin-bottom:12px;">
                        <span style="font-size:10px; color:#6c757d; font-weight:bold; text-transform:uppercase;">Academic Publications / Papers</span><br/>
                        <table cellpadding="8" style="width:100%; background-color:#fafafa; border:1px solid #eeeeee;">
                            <tr><td><span style="font-size:11px; color:#444444; line-height:1.5;">' . nl2br(htmlspecialchars($app['publications'])) . '</span></td></tr>
                        </table>
                    </div><br/>';
                }
                
                if (!empty($app['relevance_statement'])) {
                    $html .= '
                    <div>
                        <span style="font-size:10px; color:#6c757d; font-weight:bold; text-transform:uppercase;">Statement of Relevance to Position</span><br/>
                        <table cellpadding="8" style="width:100%; background-color:#fafafa; border:1px solid #eeeeee;">
                            <tr><td><span style="font-size:11px; color:#444444; line-height:1.5;">' . nl2br(htmlspecialchars($app['relevance_statement'])) . '</span></td></tr>
                        </table>
                    </div><br/>';
                }
                $html .= '<br/>';
            }

            // Section 5: General Requirements & Clear Disclosures Layout Matrix
            $html .= '
            <table cellpadding="5" cellspacing="0" style="width:100%; background-color:#212529;">
                <tr>
                    <td><span style="font-size:12px; color:#ffffff; font-weight:bold;">&nbsp;&nbsp;V. GENERAL REQUIREMENTS & DISCLOSURES</span></td>
                </tr>
            </table>
            <br/>
            <table cellpadding="6" cellspacing="0" style="width:100%; font-size:11px;">
                <tr>
                    <td style="width:50%; border-bottom:1px solid #f1f1f1;">
                        <span style="font-size:9px; color:#6c757d; font-weight:bold;">Objection to Reference Contact</span><br/>
                        <span style="color:#333333; font-weight:500;">' . (!empty($app['objection_to_reference']) ? ucfirst(htmlspecialchars($app['objection_to_reference'])) : 'Not specified') . '</span>
                    </td>
                    <td style="width:50%; border-bottom:1px solid #f1f1f1;">
                        <span style="font-size:9px; color:#6c757d; font-weight:bold;">Physical Disability Status</span><br/>
                        <span style="color:#333333; font-weight:500;">' . (!empty($app['physical_disability']) ? ucfirst(htmlspecialchars($app['physical_disability'])) : 'Not specified') . '</span>
                    </td>
                </tr>
                <tr>
                    <td style="width:50%;">
                        <span style="font-size:9px; color:#6c757d; font-weight:bold;">Criminal Court Convictions</span><br/>
                        <span style="color:#333333; font-weight:500;">' . (!empty($app['conviction']) ? ucfirst(htmlspecialchars($app['conviction'])) : 'Not specified') . '</span>
                    </td>
                    <td style="width:50%;">
                        <span style="font-size:9px; color:#6c757d; font-weight:bold;">Notice Period Requirement</span><br/>
                        <span style="color:#212529; font-weight:bold;">' . htmlspecialchars($app['appointment_notice_period'] ?? 'None Logged') . '</span>
                    </td>
                </tr>
            </table>';

            if (!empty($app['disability_details'])) {
                $html .= '
                <br/>
                <table cellpadding="8" style="width:100%; background-color:#fff3cd; border-left:4px solid #ffc107; color:#856404; font-size:11px;">
                    <tr>
                        <td>
                            <b>Disability Structural Details:</b><br/>' . nl2br(htmlspecialchars($app['disability_details'])) . '
                        </td>
                    </tr>
                </table>';
            }

            if (!empty($app['conviction_details'])) {
                $html .= '
                <br/>
                <table cellpadding="8" style="width:100%; background-color:#f8d7da; border-left:4px solid #dc3545; color:#721c24; font-size:11px;">
                    <tr>
                        <td>
                            <b>Court Conviction Details File Record:</b><br/>' . nl2br(htmlspecialchars($app['conviction_details'])) . '
                        </td>
                    </tr>
                </table>';
            }

            if (!empty($app['hobbies'])) {
                $html .= '
                <br/>
                <table cellpadding="6" style="width:100%; border-top:1px dotted #eeeeee; font-size:11px;">
                    <tr>
                        <td>
                            <span style="font-size:9px; color:#6c757d; font-weight:bold;">Personal Hobbies & Leisure</span><br/>
                            <span style="color:#555555;">' . nl2br(htmlspecialchars($app['hobbies'])) . '</span>
                        </td>
                    </tr>
                </table>';
            }

            if (!empty($app['additional_info'])) {
                $html .= '
                <br/><br/>
                <table cellpadding="5" cellspacing="0" style="width:100%; background-color:#212529;">
                    <tr>
                        <td><span style="font-size:12px; color:#ffffff; font-weight:bold;">&nbsp;&nbsp;VI. ADDITIONAL INFORMATION</span></td>
                    </tr>
                </table>
                <br/>
                <table cellpadding="8" style="width:100%; background-color:#fcfcfc; border:1px solid #f1f1f1; font-size:11px;">
                    <tr><td><span style="color:#444444; line-height:1.5;">' . nl2br(htmlspecialchars($app['additional_info'])) . '</span></td></tr>
                </table>';
            }
            $html .= '<br/><br/>';

            // Section 6: Direct Column Inline Referees Panel Layout Configuration
            $html .= '
            <table cellpadding="5" cellspacing="0" style="width:100%; background-color:#212529;">
                <tr>
                    <td><span style="font-size:12px; color:#ffffff; font-weight:bold;">&nbsp;&nbsp;VII. REFEREES PANEL</span></td>
                </tr>
            </table>
            <br/>';
            
            $hasReferees = false;
            for ($r = 1; $r <= 3; $r++) {
                $rName = $app["referee{$r}_name"] ?? '';
                if (empty($rName)) continue;
                $hasReferees = true;

                $html .= '
                <table cellpadding="8" cellspacing="0" style="width:100%; background-color:#f8f9fa; border-top:3px solid #6c757d; margin-bottom:12px; font-size:11px;">
                    <tr>
                        <td colspan="2"><span style="font-weight:bold; color:#4e73df;">Referee Slot #' . $r . '</span></td>
                    </tr>
                    <tr>
                        <td style="width:50%;">
                            <span style="font-size:9px; color:#6c757d; font-weight:bold;">FullName</span><br/>
                            <span style="font-weight:bold; color:#222222;">' . htmlspecialchars($rName) . '</span>
                        </td>
                        <td style="width:50%;">
                            <span style="font-size:9px; color:#6c757d; font-weight:bold;">Occupation Sector</span><br/>
                            <span style="font-weight:500; color:#444444;">' . htmlspecialchars($app["referee{$r}_occupation"] ?? 'N/A') . '</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:50%;">
                            <span style="font-size:9px; color:#6c757d; font-weight:bold;">Designated Position</span><br/>
                            <span style="font-weight:500; color:#444444;">' . htmlspecialchars($app["referee{$r}_position"] ?? 'N/A') . '</span>
                        </td>
                        <td style="width:50%;">
                            <span style="font-size:9px; color:#6c757d; font-weight:bold;">Contact Tel</span><br/>
                            <span style="font-weight:bold; color:#212529;">' . htmlspecialchars($app["referee{$r}_tel"] ?? 'N/A') . '</span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span style="font-size:9px; color:#6c757d; font-weight:bold;">Verified Email Address</span><br/>
                            <span style="font-weight:500;">' . htmlspecialchars($app["referee{$r}_email"] ?? 'N/A') . '</span>
                        </td>
                    </tr>';

                    if (!empty($app["referee{$r}_address"])) {
                        $html .= '
                        <tr>
                            <td colspan="2" style="border-top:1px dotted #e3e6f0; padding-top:6px;">
                                <span style="font-size:9px; color:#6c757d; font-weight:bold;">Postal / Corporate Address</span><br/>
                                <span style="font-size:10px; color:#555555;">' . nl2br(htmlspecialchars($app["referee{$r}_address"])) . '</span>
                            </td>
                        </tr>';
                    }
                $html .= '</table><br/>';
            }

            if (!$hasReferees) {
                $html .= '<div style="text-align:center; font-size:11px; color:#6c757d; padding:15px;">No references provided.</div>';
            }

            // Section 7: Internal Review Remarks & Administrative Audit Logs
            $notesText = !empty($app['hr_notes']) ? $app['hr_notes'] : "No internal evaluation remarks or audit exceptions logged for this applicant profile context.";
            $html .= '
            <br/><br/>
            <table cellpadding="5" cellspacing="0" style="width:100%; background-color:#1d2a52;">
                <tr>
                    <td><span style="font-size:12px; color:#ffffff; font-weight:bold;">&nbsp;&nbsp;VIII. EXECUTIVE INTERNAL REVIEW PANEL REMARKS</span></td>
                </tr>
            </table>
            <br/>
            <table cellpadding="8" style="width:100%; background-color:#f8f9fc; border-left:3px solid #1d2a52; font-size:11px;">
                <tr><td><span style="color:#2d3748; font-style:italic; line-height:1.5;">' . nl2br(htmlspecialchars($notesText)) . '</span></td></tr>
            </table>';

            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output($summaryPdfPath, 'F');

            // --- PHASE B: INSTANTIATE SYSTEM PDF MERGER ENGINE ---
            $tempFixedFiles = [];
            $mergeSucceeded = false;

            try {
                if (class_exists('\rguedes\pdfmerger\PDFMerger')) {
                    $merger = new \rguedes\pdfmerger\PDFMerger();
                } elseif (class_exists('\ProjectEngine\PDFMerger\PDFMerger')) {
                    $merger = new \ProjectEngine\PDFMerger\PDFMerger();
                } else {
                    $merger = new \PDFMerger();
                }

                $merger->addPDF($summaryPdfPath, 'all');

                // --- PHASE C: RUN PRE-EMPTIVE NORMALIZATION ON ALL ATTACHMENTS ---
                $gsAvailable = (strncasecmp(PHP_OS, 'WIN', 3) === 0)
                    ? (shell_exec('where gs 2>NUL') !== null)
                    : (shell_exec('which gs 2>/dev/null') !== null);

                foreach ($uploadedFiles as $fileRecord) {
                    $storedFilename = $fileRecord['stored_name'] ?? '';
                    if (empty($storedFilename)) continue;

                    $physicalFilePath = $docsDir . $storedFilename;
                    $ext = strtolower(pathinfo($storedFilename, PATHINFO_EXTENSION));

                    if ($ext === 'pdf' && file_exists($physicalFilePath)) {

                        if ($gsAvailable) {
                            $fixedFilename = 'gs_fixed_' . uniqid() . '_' . $storedFilename;
                            $fixedFilePath = $outputDir . $fixedFilename;

                            $cmd = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=" . escapeshellarg($fixedFilePath) . " " . escapeshellarg($physicalFilePath);
                            shell_exec($cmd);

                            if (file_exists($fixedFilePath) && filesize($fixedFilePath) > 0) {
                                $merger->addPDF($fixedFilePath, 'all');
                                $tempFixedFiles[] = $fixedFilePath;
                                continue;
                            }
                        }

                        $warnFilePath = $this->appendWarningPage($summaryPdfPath, $storedFilename);
                        $merger->addPDF($warnFilePath, 'all');
                        $tempFixedFiles[] = $warnFilePath;
                    }
                }

                // --- PHASE D: EXECUTE COMPILATION ASSEMBLY BATCH ---
                $merger->merge('file', $finalMasterReportPath);
                $mergeSucceeded = true;
            } catch (\Throwable $e) {
                $mergeSucceeded = false;
            }
            
            // Clean up temporary tracking components
            foreach ($tempFixedFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }

            // Fallback: rename summary PDF as the final report if merge failed
            if (!$mergeSucceeded) {
                rename($summaryPdfPath, $finalMasterReportPath);
            } elseif (file_exists($summaryPdfPath)) {
                unlink($summaryPdfPath);
            }

            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'     => 'success',
                'file_token' => $finalMasterReportName
            ]);
            exit;

        } catch (\Throwable $ex) {
            if (isset($summaryPdfPath) && file_exists($summaryPdfPath)) {
                unlink($summaryPdfPath);
            }
            if (isset($tempFixedFiles) && is_array($tempFixedFiles)) {
                foreach ($tempFixedFiles as $tempFile) {
                    if (file_exists($tempFile)) unlink($tempFile);
                }
            }

            $bufferedSpill = ob_get_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => 'error',
                'message' => 'Pipeline Fault Exception: ' . $ex->getMessage(),
                'debug'   => !empty($bufferedSpill) ? strip_tags($bufferedSpill) : 'None'
            ]);
            exit;
        }
    }

    /**
     * Helper to render a temporary fallback notice page within the dossier 
     * instead of throwing a global runtime execution abort exception.
     */
    private function appendWarningPage(string $basePath, string $filename): string {
        $warnPath = str_replace('.pdf', '_warn_' . uniqid() . '.pdf', $basePath);
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 40, 15);
        $pdf->AddPage();
        
        $html = '
        <div style="text-align:center; padding: 30px;">
            <h1 style="color:#c53030; font-size:18px;">[ COMPILATION PIPELINE WARNING ]</h1>
            <br/><br/>
            <p style="font-size:12px; color:#2d3748; line-height:1.6;">
                The attachment upload item labeled <b>' . htmlspecialchars($filename) . '</b> 
                could not be compiled directly into this dossier package.
            </p>
            <p style="font-size:11px; color:#718096; font-style:italic;">
                Reason: The file has an incompatible internal cross-reference stream mapping matrix layout (PDF 1.5+ Compression / Active Form Layers).
            </p>
        </div>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($warnPath, 'F');
        return $warnPath;
    }

    /**
     * SECURE STREAM GATEWAY: Delivers file natively using custom requested filename
     */
    public function downloadFile(Request $request): void {
        $token = $_GET['token'] ?? '';
        if (empty($token) || strpos($token, '..') !== false) {
            die("Security Access Violation: Invalid or malformed token reference signature.");
        }

        $rootPath = dirname(__DIR__, 2);
        $targetFile = $rootPath . '/public/uploads/compiled_reports/' . basename($token);

        if (file_exists($targetFile)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($targetFile) . '"');
            header('Content-Length: ' . filesize($targetFile));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            readfile($targetFile);
            exit;
        } else {
            die("System Execution Error: The requested application report could not be read from disk storage.");
        }
    }

    // ---- HIRING PIPELINE ----

    public function pipelineIndex(Request $request): void {
        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $filterJob = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

        $jobs = $db->query("SELECT id, title FROM jobs WHERE status = 'open' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

        $where = '';
        $params = [];
        if ($filterJob > 0) {
            $where = 'WHERE a.job_id = ?';
            $params[] = $filterJob;
        }

        $stmt = $db->prepare("
            SELECT a.id, a.reference_number, a.first_name, a.last_name, a.status,
                   a.submitted_at, COALESCE(j.title, 'General Application') AS job_title, j.id AS job_id
            FROM applications a
            LEFT JOIN jobs j ON a.job_id = j.id
            $where
            ORDER BY a.submitted_at DESC
        ");
        $stmt->execute($params);
        $allApps = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stages = ['pending', 'reviewing', 'shortlisted', 'interviewed', 'offered', 'hired', 'rejected'];
        $stageLabels = [
            'pending'      => 'Applied',
            'reviewing'    => 'Under Review',
            'shortlisted'  => 'Shortlisted',
            'interviewed'  => 'Interviewed',
            'offered'      => 'Offered',
            'hired'        => 'Hired',
            'rejected'     => 'Rejected',
        ];

        $grouped = [];
        foreach ($stages as $s) $grouped[$s] = [];
        foreach ($allApps as $app) {
            $status = $app['status'];
            $key = 'pending';
            foreach ($stages as $s) {
                if (strtolower(trim($status)) === $s || strtolower(trim($status)) === strtolower($stageLabels[$s])) {
                    $key = $s;
                    break;
                }
            }
            $grouped[$key][] = $app;
        }

        $pageTitle = 'Hiring Pipeline';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/recruitment/pipeline.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function pipelineMove(Request $request): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); echo 'Invalid method'; exit;
        }
        CSRFMiddleware::validate($request);
        $id = (int)($_POST['applicant_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $userId = (int)($_SESSION['user_id'] ?? 0);

        if ($id <= 0 || $newStatus === '') {
            http_response_code(400); echo 'Missing parameters'; exit;
        }

        $stageMap = [
            'pending' => 'pending', 'reviewing' => 'Under review', 'shortlisted' => 'Shortlisted',
            'interviewed' => 'Interviewed', 'offered' => 'Offered', 'hired' => 'Hired', 'rejected' => 'Rejected',
        ];
        $targetStatus = $stageMap[$newStatus] ?? $newStatus;

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT status FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$current) { http_response_code(404); echo 'Applicant not found'; exit; }

        $oldStatus = $current['status'];

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE applications SET status = ? WHERE id = ?");
            $stmt->execute([$targetStatus, $id]);

            $stmt = $db->prepare("INSERT INTO application_status_log (application_id, old_status, new_status, changed_by, note) VALUES (?,?,?,?,?)");
            $stmt->execute([$id, $oldStatus, $targetStatus, $userId ?: null, $note]);

            if ($userId) {
                $appStmt = $db->prepare("SELECT first_name, last_name FROM applications WHERE id = ?");
                $appStmt->execute([$id]);
                $app = $appStmt->fetch(PDO::FETCH_ASSOC);
                $fullName = ($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? '');
                foreach ($db->query("SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.role_name IN ('Super Admin', 'HR Manager') AND u.deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN) as $sId) {
                    \App\Helpers\Notification::create((int)$sId, 'Pipeline Update', "{$fullName} moved to {$targetStatus}.", 'info');
                }
            }

            $db->commit();
            header('Location: /recruitment/pipeline' . ($_POST['job_filter'] ? '?job_id=' . (int)$_POST['job_filter'] : ''));
            exit;
        } catch (\Throwable $e) {
            $db->rollBack();
            http_response_code(500); echo 'Error moving applicant'; exit;
        }
    }

    public function pipelineScheduleInterview(Request $request): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        CSRFMiddleware::validate($request);
        $appId = (int)($_POST['applicant_id'] ?? 0);
        $date = $_POST['interview_date'] ?? '';
        $time = $_POST['interview_time'] ?? '';
        $type = $_POST['interview_type'] ?? 'in-person';
        $interviewer = trim($_POST['interviewer'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($appId <= 0 || !$date) { http_response_code(400); exit; }
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO application_interviews (application_id, interview_date, interview_time, interview_type, interviewer, location, notes, created_by) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$appId, $date, $time ?: null, $type, $interviewer ?: null, $location ?: null, $notes ?: null, $userId ?: null]);
        header('Location: /recruitment/pipeline' . ($_POST['job_filter'] ? '?job_id=' . (int)$_POST['job_filter'] : ''));
        exit;
    }

    public function pipelineSendOffer(Request $request): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        CSRFMiddleware::validate($request);
        $appId = (int)($_POST['applicant_id'] ?? 0);
        $salary = !empty($_POST['offered_salary']) ? (float)$_POST['offered_salary'] : null;
        $offerDate = $_POST['offer_date'] ?? date('Y-m-d');
        $notes = trim($_POST['offer_notes'] ?? '');
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($appId <= 0) { http_response_code(400); exit; }

        $offerLetterFilename = '';
        $offerLetterOriginal = '';
        if (!empty($_FILES['offer_letter']['name']) && $_FILES['offer_letter']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['offer_letter']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf', 'doc', 'docx'])) {
                $dir = dirname(__DIR__, 2) . '/public/uploads/offers/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $offerLetterOriginal = basename($_FILES['offer_letter']['name']);
                $offerLetterFilename = uniqid('offer_') . '.' . $ext;
                if (!move_uploaded_file($_FILES['offer_letter']['tmp_name'], $dir . $offerLetterFilename)) {
                    $offerLetterFilename = '';
                    $offerLetterOriginal = '';
                }
            }
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO application_offers (application_id, offered_salary, offer_date, offer_letter_filename, offer_letter_original, notes, created_by, status) VALUES (?,?,?,?,?,?,?,'draft')");
        $stmt->execute([$appId, $salary, $offerDate, $offerLetterFilename ?: null, $offerLetterOriginal ?: null, $notes ?: null, $userId ?: null]);

        $stmt = $db->prepare("UPDATE applications SET status = 'Offered' WHERE id = ? AND status NOT IN ('Hired','Rejected')");
        $stmt->execute([$appId]);

        $stmt = $db->prepare("SELECT first_name, last_name FROM applications WHERE id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($app) {
            $fullName = $app['first_name'] . ' ' . $app['last_name'];
            foreach ($db->query("SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.role_name IN ('Super Admin', 'HR Manager') AND u.deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN) as $sId) {
                \App\Helpers\Notification::create((int)$sId, 'Offer Sent', "Offer sent to {$fullName}.", 'success');
            }
        }

        header('Location: /recruitment/pipeline' . ($_POST['job_filter'] ? '?job_id=' . (int)$_POST['job_filter'] : ''));
        exit;
    }

    public function pipelineAddNote(Request $request): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        CSRFMiddleware::validate($request);
        $appId = (int)($_POST['applicant_id'] ?? 0);
        $note = trim($_POST['note'] ?? '');
        if ($appId <= 0 || $note === '') { http_response_code(400); exit; }
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE applications SET hr_notes = CONCAT(IFNULL(hr_notes,''), '\n[', NOW(), '] ', ?) WHERE id = ?");
        $stmt->execute([$note, $appId]);
        header('Location: /recruitment/pipeline' . ($_POST['job_filter'] ? '?job_id=' . (int)$_POST['job_filter'] : ''));
        exit;
    }

    public function pipelineStatusHistory(Request $request): void {
        $appId = (int)($_GET['applicant_id'] ?? 0);
        if ($appId <= 0) { http_response_code(400); exit; }
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT l.old_status, l.new_status, l.note, l.changed_at,
                   COALESCE(u.first_name, '') AS changed_first, COALESCE(u.last_name, '') AS changed_last
            FROM application_status_log l
            LEFT JOIN users u ON l.changed_by = u.id
            WHERE l.application_id = ?
            ORDER BY l.changed_at DESC
        ");
        $stmt->execute([$appId]);
        $log = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            SELECT i.*, COALESCE(u.first_name, '') AS created_first, COALESCE(u.last_name, '') AS created_last
            FROM application_interviews i
            LEFT JOIN users u ON i.created_by = u.id
            WHERE i.application_id = ?
            ORDER BY i.interview_date DESC
        ");
        $stmt->execute([$appId]);
        $interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            SELECT o.*, COALESCE(u.first_name, '') AS created_first, COALESCE(u.last_name, '') AS created_last
            FROM application_offers o
            LEFT JOIN users u ON o.created_by = u.id
            WHERE o.application_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$appId]);
        $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT a.first_name, a.last_name, a.reference_number, a.hr_notes,
               COALESCE(j.title, 'General Application') AS job_title
            FROM applications a LEFT JOIN jobs j ON a.job_id = j.id WHERE a.id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode(['app' => $app, 'log' => $log, 'interviews' => $interviews, 'offers' => $offers]);
        exit;
    }

    // =====================================================
    // AI SHORTLISTING
    // =====================================================

    public function shortlistView(Request $request): void {
        $db = Database::getConnection();
        $jobs = $db->query("SELECT id, title, requirements, shortlist_limit FROM jobs WHERE status = 'open' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

        $filterJob = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
        $applicants = [];
        if ($filterJob > 0) {
            $stmt = $db->prepare("
                SELECT a.id, a.first_name, a.last_name, a.reference_number, a.phone, a.email,
                       a.highest_qualification, a.institution, a.years_experience, a.status,
                       COALESCE(j.title, 'General Application') AS job_title
                FROM applications a
                LEFT JOIN jobs j ON a.job_id = j.id
                WHERE a.job_id = ? AND a.status IN ('pending','Under review','reviewing')
                ORDER BY a.submitted_at DESC
            ");
            $stmt->execute([$filterJob]);
            $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $pageTitle = "HRGoTo HCM - Shortlisting";
        $appUrl = Security::escape($_ENV['APP_URL'] ?? '');
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/recruitment/shortlist.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function autoShortlist(Request $request): void {
        CSRFMiddleware::validate($request);
        $db = Database::getConnection();
        $jobId = (int)($_POST['job_id'] ?? 0);
        $limit = (int)($_POST['shortlist_limit'] ?? 10);

        if ($jobId <= 0 || $limit <= 0) {
            Security::setFlash('error', 'Invalid job or limit.');
            header('Location: /recruitment/shortlist');
            exit;
        }

        $stmt = $db->prepare("SELECT requirements, title FROM jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$job) {
            Security::setFlash('error', 'Job not found.');
            header('Location: /recruitment/shortlist');
            exit;
        }

        $stmt = $db->prepare("
            SELECT a.id, a.first_name, a.last_name, a.highest_qualification, a.institution,
                   a.years_experience, a.publications, a.relevance_statement
            FROM applications a
            WHERE a.job_id = ? AND a.status IN ('pending','Under review','reviewing')
        ");
        $stmt->execute([$jobId]);
        $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $ranked = $this->rankApplicantsList($applicants, $job['requirements'] ?? '');
        $shortlisted = array_slice($ranked, 0, $limit);

        $db->beginTransaction();
        try {
            $statusLogStmt = $db->prepare("INSERT INTO application_status_log (application_id, old_status, new_status, changed_by, note) VALUES (?,?,?,?,?)");
            $userId = (int)($_SESSION['user_id'] ?? 0);
            foreach ($shortlisted as $app) {
                $stmt = $db->prepare("UPDATE applications SET status = 'Shortlisted', rank_score = ? WHERE id = ?");
                $stmt->execute([$app['rank_score'], $app['id']]);
                $statusLogStmt->execute([$app['id'], 'pending', 'Shortlisted', $userId ?: null, 'AI auto-shortlist (score: ' . round($app['rank_score'], 1) . ')']);
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Security::setFlash('error', 'Shortlisting failed: ' . $e->getMessage());
            header('Location: /recruitment/shortlist?job_id=' . $jobId);
            exit;
        }

        Security::setFlash('ok', count($shortlisted) . ' applicant(s) auto-shortlisted for "' . htmlspecialchars($job['title']) . '".');
        header('Location: /recruitment/shortlist?job_id=' . $jobId);
        exit;
    }

    private function rankApplicantsList(array $applicants, string $jobRequirements): array {
        $qualOrder = [
            'phd' => 5, 'doctorate' => 5, 'master' => 4, 'mba' => 4,
            'bachelor' => 3, 'degree' => 3, 'hnd' => 2, 'diploma' => 2,
            'certificate' => 1, 'ssce' => 0, 'wassce' => 0,
        ];

        $reqLower = strtolower($jobRequirements);
        $keywords = array_filter(preg_split('/[\s,;]+/', $reqLower));

        foreach ($applicants as &$app) {
            $score = 0;

            $qualLower = strtolower($app['highest_qualification'] ?? '');
            foreach ($qualOrder as $key => $pts) {
                if (strpos($qualLower, $key) !== false) {
                    $score += $pts * 10;
                    break;
                }
            }

            $exp = (int)($app['years_experience'] ?? 0);
            $score += min($exp * 5, 50);

            $text = strtolower(($app['publications'] ?? '') . ' ' . ($app['relevance_statement'] ?? ''));
            foreach ($keywords as $kw) {
                if (strlen($kw) > 2 && strpos($text, $kw) !== false) {
                    $score += 5;
                }
            }

            $app['rank_score'] = $score;
        }
        unset($app);

        usort($applicants, fn($a, $b) => $b['rank_score'] <=> $a['rank_score']);
        return $applicants;
    }

    public function manualShortlist(Request $request): void {
        CSRFMiddleware::validate($request);
        $db = Database::getConnection();
        $appIds = $_POST['application_ids'] ?? [];
        $userId = (int)($_SESSION['user_id'] ?? 0);

        if (empty($appIds)) {
            Security::setFlash('error', 'No applicants selected.');
            header('Location: /recruitment/shortlist');
            exit;
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE applications SET status = 'Shortlisted' WHERE id = ?");
            $logStmt = $db->prepare("INSERT INTO application_status_log (application_id, old_status, new_status, changed_by, note) VALUES (?,?,?,?,?)");
            foreach ($appIds as $id) {
                $stmt->execute([(int)$id]);
                $logStmt->execute([(int)$id, 'pending', 'Shortlisted', $userId ?: null, 'Manual shortlist']);
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
        }

        Security::setFlash('ok', count($appIds) . ' applicant(s) manually shortlisted.');
        header('Location: /recruitment/shortlist');
        exit;
    }

    public function sendInterviewSms(Request $request): void {
        CSRFMiddleware::validate($request);
        $db = Database::getConnection();
        $appIds = $_POST['application_ids'] ?? [];

        if (empty($appIds)) {
            Security::setFlash('error', 'No shortlisted applicants selected.');
            header('Location: /recruitment/shortlist');
            exit;
        }

        $config = $db->query("SELECT sms_endpoint, gen_sms_sender_id, sms_apikey FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $smsTemplate = $db->query("SELECT interviewed FROM sms_campaign_templates LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        $placeholders = $_POST['interview_placeholder'] ?? [];
        $interviewDate = $placeholders['interview_date'] ?? date('Y-m-d');
        $interviewTime = $placeholders['interview_time'] ?? '';
        $interviewLocation = $placeholders['interview_location'] ?? '';

        $successCount = 0;
        foreach ($appIds as $id) {
            $stmt = $db->prepare("SELECT first_name, last_name, phone FROM applications WHERE id = ?");
            $stmt->execute([(int)$id]);
            $app = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$app || empty($app['phone'])) continue;

            $fullName = trim($app['first_name'] . ' ' . $app['last_name']);
            $msg = $smsTemplate['interviewed'] ?? '';
            $msg = str_replace('[fullname]', $fullName, $msg);
            $msg = str_replace('[interview_date]', $interviewDate, $msg);
            $msg = str_replace('[interview_time]', $interviewTime, $msg);
            $msg = str_replace('[interview_location]', $interviewLocation, $msg);

            if ($this->sendOutboundSms($config, $app['phone'], $msg)) {
                $successCount++;
            }
        }

        Security::setFlash('ok', "Interview SMS sent to {$successCount} applicant(s).");
        header('Location: /recruitment/shortlist');
        exit;
    }

    public function addInterviewScore(Request $request): void {
        CSRFMiddleware::validate($request);
        $db = Database::getConnection();
        $interviewId = (int)($_POST['interview_id'] ?? 0);
        $score = !empty($_POST['score']) ? (float)$_POST['score'] : null;
        $comment = trim($_POST['score_comment'] ?? '');

        if ($interviewId <= 0) {
            Security::setFlash('error', 'Invalid interview.');
            header('Location: /recruitment/ranked');
            exit;
        }

        $stmt = $db->prepare("UPDATE application_interviews SET score = ?, score_comment = ?, status = 'completed' WHERE id = ?");
        $stmt->execute([$score, $comment ?: null, $interviewId]);

        Security::setFlash('ok', 'Interview score saved.');
        header('Location: /recruitment/ranked');
        exit;
    }

    // =====================================================
    // RANKED VIEW + HIRE
    // =====================================================

    public function rankedView(Request $request): void {
        $db = Database::getConnection();
        $jobs = $db->query("SELECT id, title FROM jobs WHERE status = 'open' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
        $filterJob = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

        $ranked = [];
        if ($filterJob > 0) {
            $stmt = $db->prepare("
                SELECT a.id, a.first_name, a.last_name, a.reference_number, a.phone, a.email,
                       a.highest_qualification, a.institution, a.years_experience, a.status,
                       a.rank_score, COALESCE(j.title, 'General Application') AS job_title,
                       (SELECT MAX(i.score) FROM application_interviews i WHERE i.application_id = a.id AND i.score IS NOT NULL) AS interview_score,
                       (SELECT i.id FROM application_interviews i WHERE i.application_id = a.id ORDER BY i.id DESC LIMIT 1) AS interview_id
                FROM applications a
                LEFT JOIN jobs j ON a.job_id = j.id
                WHERE a.job_id = ? AND a.status IN ('Shortlisted','Interviewed')
                ORDER BY COALESCE(a.rank_score, 0) DESC, interview_score DESC
            ");
            $stmt->execute([$filterJob]);
            $ranked = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($ranked as &$r) {
                $r['final_score'] = round(
                    ($r['rank_score'] ?? 0) * 0.5 +
                    ($r['interview_score'] ?? 0) * 0.4 +
                    min((int)$r['years_experience'] * 2, 20) * 0.1
                , 2);
            }
            unset($r);
            usort($ranked, fn($a, $b) => $b['final_score'] <=> $a['final_score']);
        }

        $pageTitle = "HRGoTo HCM - Ranked Applicants";
        $appUrl = Security::escape($_ENV['APP_URL'] ?? '');
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/recruitment/ranked.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function hireApplicant(Request $request): void {
        CSRFMiddleware::validate($request);
        $db = Database::getConnection();
        $appId = (int)($_POST['applicant_id'] ?? 0);
        $userId = (int)($_SESSION['user_id'] ?? 0);

        if ($appId <= 0) {
            Security::setFlash('error', 'Invalid applicant.');
            header('Location: /recruitment/ranked');
            exit;
        }

        $stmt = $db->prepare("SELECT a.*, COALESCE(j.title, 'General Application') AS job_title FROM applications a LEFT JOIN jobs j ON a.job_id = j.id WHERE a.id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$app) {
            Security::setFlash('error', 'Applicant not found.');
            header('Location: /recruitment/ranked');
            exit;
        }

        $fullName = trim($app['first_name'] . ' ' . $app['last_name']);
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE applications SET status = 'Hired' WHERE id = ?");
            $stmt->execute([$appId]);

            $logStmt = $db->prepare("INSERT INTO application_status_log (application_id, old_status, new_status, changed_by, note) VALUES (?,?,?,?,?)");
            $logStmt->execute([$appId, $app['status'], 'Hired', $userId ?: null, 'Hired via ranking view']);

            $this->createStaffFromApplicant($db, $appId, $app);

            $this->sendHireNotifications($db, $app);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Security::setFlash('error', 'Hire failed: ' . $e->getMessage());
            header('Location: /recruitment/ranked');
            exit;
        }

        Security::setFlash('ok', htmlspecialchars($fullName) . ' has been hired and converted to staff.');
        header('Location: /recruitment/ranked');
        exit;
    }

    private function createStaffFromApplicant(PDO $db, int $appId, array $app): void {
        $existingUser = $db->prepare("SELECT id FROM users WHERE email = ?");
        $existingUser->execute([$app['email'] ?? '']);
        if ($existingUser->fetch()) return;

        $fullName = trim(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $fullName)) . rand(100, 999);
        $passwordHash = password_hash('ChangeMe@' . rand(1000, 9999), PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (fullname, username, email, password, role_id, status, created_at) VALUES (?, ?, ?, ?, (SELECT id FROM roles WHERE role_name = 'Staff' LIMIT 1), 'Active', NOW())");
        $stmt->execute([$fullName, $username, $app['email'], $passwordHash]);
        $userId = (int)$db->lastInsertId();

        if ($userId > 0) {
            $stmt = $db->prepare("INSERT INTO staff_records (user_id, gender, date_of_birth, phone_one, ghana_card_number, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $userId,
                $app['gender'] ?? null,
                $app['date_of_birth'] ?? null,
                $app['phone'] ?? null,
                $app['ghana_card_number'] ?? null
            ]);
        }
    }

    private function sendHireNotifications(PDO $db, array $app): void {
        $fullName = trim($app['first_name'] . ' ' . $app['last_name']);
        $config = $db->query("SELECT sms_endpoint, gen_sms_sender_id, sms_apikey FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $smsTemplate = $db->query("SELECT hired FROM sms_campaign_templates LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        if ($config && $smsTemplate && !empty($app['phone'])) {
            $msg = str_replace('[fullname]', $fullName, $smsTemplate['hired'] ?? '');
            $this->sendOutboundSms($config, $app['phone'], $msg);
        }

        $smtpConfig = $db->query("SELECT smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, smtp_from_email, smtp_from_name FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $emailTemplate = $db->query("SELECT template_subject, template_body FROM email_templates WHERE template_name = 'Appointment Letter' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        if ($smtpConfig && $emailTemplate && !empty($app['email'])) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $smtpConfig['smtp_host'] ?? '';
                $mail->Port = (int)($smtpConfig['smtp_port'] ?? 587);
                $mail->SMTPAuth = true;
                $mail->Username = $smtpConfig['smtp_username'] ?? '';
                $mail->Password = $smtpConfig['smtp_password'] ?? '';
                $enc = $smtpConfig['smtp_encryption'] ?? 'tls';
                if ($enc === 'tls') $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                elseif ($enc === 'ssl') $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;

                $mail->setFrom($smtpConfig['smtp_from_email'] ?? '', $smtpConfig['smtp_from_name'] ?? 'HRGoTo HCM');
                $mail->addAddress($app['email']);
                $mail->isHTML(true);

                $subject = str_replace('[fullname]', $fullName, str_replace('[job_title]', $app['job_title'] ?? '', $emailTemplate['template_subject']));
                $body = str_replace('[fullname]', $fullName, str_replace('[job_title]', $app['job_title'] ?? '', $emailTemplate['template_body']));
                $mail->Subject = $subject;
                $mail->Body = $body;
                $mail->send();
            } catch (\Throwable $e) {
                error_log("Appointment letter email failed: " . $e->getMessage());
            }
        }
    }

    public function appointmentLetterView(Request $request): void {
        $db = Database::getConnection();
        $template = $db->query("SELECT template_subject, template_body FROM email_templates WHERE template_name = 'Appointment Letter' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        $pageTitle = "HRGoTo HCM - Appointment Letter Template";
        $appUrl = Security::escape($_ENV['APP_URL'] ?? '');
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/recruitment/appointment_letter.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    // =====================================================
    // TALENT POOL
    // =====================================================

    public function talentPoolView(Request $request): void {
        $db = Database::getConnection();

        $pool = $db->query("
            SELECT tp.id AS pool_id, tp.pool_status, tp.notes AS pool_notes, tp.created_at AS added_at,
                   a.id, a.first_name, a.last_name, a.reference_number, a.phone, a.email,
                   a.highest_qualification, a.institution, a.years_experience, a.status,
                   COALESCE(j.title, 'General Application') AS job_title
            FROM talent_pool tp
            JOIN applications a ON a.id = tp.application_id
            LEFT JOIN jobs j ON a.job_id = j.id
            WHERE tp.pool_status = 'active'
            ORDER BY tp.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = "HRGoTo HCM - Talent Pool";
        $appUrl = Security::escape($_ENV['APP_URL'] ?? '');
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/recruitment/talent_pool.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function addToTalentPool(Request $request): void {
        CSRFMiddleware::validate($request);
        $db = Database::getConnection();
        $appId = (int)($_POST['application_id'] ?? 0);
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $notes = trim($_POST['pool_notes'] ?? '');

        if ($appId <= 0) {
            Security::setFlash('error', 'Invalid applicant.');
            header('Location: /recruitment/talent-pool');
            exit;
        }

        try {
            $stmt = $db->prepare("INSERT INTO talent_pool (application_id, added_by, notes) VALUES (?,?,?) ON DUPLICATE KEY UPDATE pool_status='active', notes=?");
            $stmt->execute([$appId, $userId ?: null, $notes ?: null, $notes ?: null]);
            Security::setFlash('ok', 'Applicant added to talent pool.');
        } catch (\Throwable $e) {
            Security::setFlash('error', 'Failed: ' . $e->getMessage());
        }
        header('Location: /recruitment/talent-pool');
        exit;
    }

    public function removeFromTalentPool(Request $request): void {
        CSRFMiddleware::validate($request);
        $db = Database::getConnection();
        $poolId = (int)($_POST['pool_id'] ?? 0);

        $stmt = $db->prepare("UPDATE talent_pool SET pool_status = 'archived' WHERE id = ?");
        $stmt->execute([$poolId]);
        Security::setFlash('ok', 'Removed from talent pool.');
        header('Location: /recruitment/talent-pool');
        exit;
    }

    public function suggestFromTalentPool(Request $request): void {
        CSRFMiddleware::validate($request);
        $db = Database::getConnection();
        $jobId = (int)($_POST['job_id'] ?? 0);
        $limit = (int)($_POST['limit'] ?? 10);

        if ($jobId <= 0) {
            Security::setFlash('error', 'Select a job.');
            header('Location: /recruitment/talent-pool');
            exit;
        }

        $stmt = $db->prepare("SELECT requirements, title FROM jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        $poolApps = $db->query("
            SELECT a.id, a.first_name, a.last_name, a.highest_qualification, a.institution,
                   a.years_experience, a.publications, a.relevance_statement
            FROM talent_pool tp
            JOIN applications a ON a.id = tp.application_id
            WHERE tp.pool_status = 'active'
        ")->fetchAll(PDO::FETCH_ASSOC);

        $ranked = $this->rankApplicantsList($poolApps, $job['requirements'] ?? '');
        $suggested = array_slice($ranked, 0, $limit);

        $_SESSION['talent_suggestions'] = $suggested;
        $_SESSION['talent_suggestions_job'] = $job['title'] ?? '';
        Security::setFlash('ok', count($suggested) . ' candidate(s) suggested from talent pool for "' . htmlspecialchars($job['title']) . '".');
        header('Location: /recruitment/talent-pool');
        exit;
    }
}