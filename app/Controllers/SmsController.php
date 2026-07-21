<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Models\SmsCampaign;
use App\Helpers\Security;
use PDO;

class SmsController {

    public function __construct() {
        // Enforces role permissions globally for all actions inside this file
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
    }

    /**
     * 1. SMS DASHBOARD STATISTICS
     * Mapped route target: GET /sms/dashboard
     */
    public function dashboard(Request $request): void {
        $db = Database::getConnection();

        // Gather your standard config sets for notification routes
        $config = $db->query("SELECT sms_endpoint, gen_sms_sender_id, sms_apikey FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $apiKey = !empty($config['sms_apikey']) ? trim($config['sms_apikey']) : '';
        $senderId = !empty($config['gen_sms_sender_id']) ? trim($config['gen_sms_sender_id']) : 'HRGoTo';

        // Query dynamic system aggregates from your local operational tracking logs
        $totalSent = (int)$db->query("SELECT COUNT(*) FROM sms_logs")->fetchColumn();
        $delivered = (int)$db->query("SELECT COUNT(*) FROM sms_logs WHERE status = 'Delivered'")->fetchColumn();
        $failed    = (int)$db->query("SELECT COUNT(*) FROM sms_logs WHERE status = 'Failed'")->fetchColumn();

        // Count category volumes for the side panel density trackers
        $hiredCount       = (int)$db->query("SELECT COUNT(*) FROM sms_logs WHERE LOWER(milestone) = 'hired'")->fetchColumn();
        $shortlistCount   = (int)$db->query("SELECT COUNT(*) FROM sms_logs WHERE LOWER(milestone) = 'shortlisted'")->fetchColumn();
        $bulkCount        = (int)$db->query("SELECT COUNT(*) FROM sms_logs WHERE LOWER(milestone) = 'bulk'")->fetchColumn();

        // Fetch live API credit balance from mNotify endpoint securely using curl
        $apiBalanceGhs = 0.00;
        $balanceUrl = 'https://api.mnotify.com/api/balance/v2?key=' . $apiKey;
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $balanceUrl); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $balResult = curl_exec($ch);

            if ($balResult !== false) {
                $balArr = json_decode($balResult, true);
                if (isset($balArr['status']) && $balArr['status'] === 'success' && isset($balArr['balance'])) {
                    $apiBalanceGhs = (float)$balArr['balance'];
                }
            }
        } catch (\Exception $e) {
            error_log("Failed pulling mNotify live wallet tracking: " . $e->getMessage());
        }

        // Package stats data array cleanly for the UI template view
        $stats = [
            'total_sent'      => $totalSent,
            'delivered'       => $delivered,
            'failed'          => $failed,
            'hired_count'     => $hiredCount,
            'shortlist_count' => $shortlistCount,
            'bulk_count'      => $bulkCount,
            'api_balance_ghs' => $apiBalanceGhs,
            'api_provider'    => 'mNotify Engine (' . $senderId . ')'
        ];

        // Fetch the absolute last 10 real rows from the transmission event table
        $stmt = $db->query("SELECT phone, milestone, message, status, created_at FROM sms_logs ORDER BY id DESC LIMIT 10");
        $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Dynamic Variables matching layout expectations explicitly
        $pageTitle = "HRGoTo HCM - SMS Analytics Dashboard";
        $appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');

        // Render exact layout chains using relative directory mapping from Controller
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/sms/dashboard.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    /**
     * 3. AUTOMATED SMS CAMPAIGNS LIST
     * Mapped route target: GET /sms/campaigns
     */
    public function campaignIndex(Request $request): void {
        $db = Database::getConnection();
        
        // 1. Fetch historical transmission logging arrays for data tracking tables
        $stmt = $db->query("SELECT * FROM sms_logs ORDER BY id DESC");
        $allLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 2. Fetch active campaign templates schema setup via Model layer
        $settings = \App\Models\SmsCampaign::getCampaignSettings();
        
        // Dynamic Meta Page Titles matching layout core rules
        $pageTitle = "HRGoTo HCM - SMS Campaign Layouts";
        
        // Render view relative to campaigns component folder context
        require_once __DIR__ . '/../Views/sms/campaigns/campaigns_list.php';
    }

    /**
     * 4. SMS CAMPAIGN CONFIGURATIONS 
     * Mapped route target: GET /sms/campaigns/configure
     */
    public function configureCampaign(Request $request): void {
        $settings = SmsCampaign::getCampaignSettings();
        require_once __DIR__ . '/../Views/sms/campaigns/configure_campaign.php';
    }

    /**
     * WORKFLOW ACTION LOGIC HOOKS
     */
    public function updateCampaignConfig(Request $request): void {
        CSRFMiddleware::validate($request);

        $id = isset($_POST['sms_cam_id']) ? (int)$_POST['sms_cam_id'] : 0;
        if ($id <= 0) {
            Security::setFlash('error', 'Invalid campaign ID');
            header('Location: /sms/campaigns/configure_campaign');
            exit;
        }
        $fieldsToUpdate = $_POST;
        unset($fieldsToUpdate['sms_cam_id'], $fieldsToUpdate['csrf_token']);

        $success = SmsCampaign::saveCampaignSettings($id, $fieldsToUpdate);
        header('Location: /sms/campaigns/configure_campaign?status=' . ($success ? 'saved' : 'save_failed'));
        exit;
    }

    public function addCampaignField(Request $request): void {
        CSRFMiddleware::validate($request);

        $newFieldName = isset($_POST['field_name']) ? trim($_POST['field_name']) : '';
        if (empty($newFieldName)) {
            Security::setFlash('error', 'Field name is required');
            header('Location: /sms/campaigns/configure_campaign');
            exit;
        }
        
        $success = SmsCampaign::addNewFieldColumn($newFieldName);
        header('Location: /sms/campaigns/configure_campaign?status=' . ($success ? 'field_added' : 'field_failed'));
        exit;
    }

    /**
     * 2. BULK SMS PORTAL
     * Mapped route target: GET /sms/campaigns/bulk
     */
    public function bulkIndex(Request $request): void {
        $db = Database::getConnection();
        
        $config = $db->query("SELECT gen_sms_sender_id FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $templates = \App\Models\SmsCampaign::getCampaignSettings();
        
        // Fetch lookups to build conditional select widgets safely
        $departments = $db->query("SELECT dept_id, dept_name FROM departments ORDER BY dept_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $faculties   = $db->query("SELECT sch_id, sch_name FROM schools ORDER BY sch_name ASC")->fetchAll(PDO::FETCH_ASSOC); 
        $directorates = $db->query("SELECT dir_id, dir_name FROM directorates ORDER BY dir_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        
        // NEW LOOKUP: Fetch all available verification masks from your sender_ids database table
        $senderIds = $db->query("SELECT * FROM sender_ids ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = "HRGoTo HCM - Bulk SMS Dispatch Portal";
        $appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');

        require_once __DIR__ . '/../Views/sms/campaigns/bulk.php';
    }

    /**
     * ADVANCED SEGMENTED BULK SMS PROCESSING PIPELINE
     * Mapped route target: POST /sms/campaigns/bulk/process
     */
    public function sendBulkProcess(Request $request): void {
        CSRFMiddleware::validate($request);

        $db = Database::getConnection(); 
        
        $config = $db->query("SELECT sms_endpoint, sms_apikey, gen_sms_sender_id FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$config) {
            header('Location: /sms/campaigns/bulk?status=missing_config');
            exit;
        }

        // Intercept and load custom dropdown sender identity assignment choice
        if (!empty($_POST['sender_id'])) {
            $config['gen_sms_sender_id'] = substr(trim($_POST['sender_id']), 0, 11);
        }

        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        $flow = isset($_POST['target_flow']) ? trim($_POST['target_flow']) : 'recruitment';

        if (empty($message)) {
            header('Location: /sms/campaigns/bulk?status=empty_fields');
            exit;
        }

        $recipients = [];

        switch ($flow) {
            case 'recruitment':
                $status = isset($_POST['recruitment_status']) ? trim($_POST['recruitment_status']) : '';
                if (!empty($status)) {
                    $stmt = $db->prepare("SELECT phone FROM applications WHERE status = ?");
                    $stmt->execute([$status]);
                    $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
                break;

            case 'staff':
                $scope = isset($_POST['staff_scope']) ? trim($_POST['staff_scope']) : '';
                
                if ($scope === 'all') {
                    $recipients = $db->query("SELECT phone_one FROM staff_records WHERE phone_one IS NOT NULL AND phone_one != ''")->fetchAll(PDO::FETCH_COLUMN);
                } elseif ($scope === 'department' && !empty($_POST['target_dept_id'])) {
                    // Query filters staff matching chosen structural department_id column key precisely
                    $stmt = $db->prepare("SELECT phone_one FROM staff_records WHERE dept_id = ? AND phone_one IS NOT NULL AND phone_one != ''");
                    $stmt->execute([(int)$_POST['target_dept_id']]);
                    $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                } elseif ($scope === 'faculty' && !empty($_POST['target_faculty_id'])) {
                    $stmt = $db->prepare("SELECT phone_one FROM staff_records WHERE dept_id IN (SELECT dept_id FROM departments WHERE parent_unit = ?) AND phone_one IS NOT NULL AND phone_one != ''");
                    $stmt->execute([(int)$_POST['target_faculty_id']]);
                    $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                } elseif ($scope === 'directorate' && !empty($_POST['target_dir_id'])) {
                    $stmt = $db->prepare("SELECT phone_one FROM staff_records WHERE dept_id IN (SELECT dept_id FROM departments WHERE parent_unit = ?) AND phone_one IS NOT NULL AND phone_one != ''");
                    $stmt->execute([(int)$_POST['target_dir_id']]);
                    $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
                break;

            case 'other':
                if (!empty($_POST['raw_recipients'])) {
                    $rawPhones = explode(',', $_POST['raw_recipients']);
                    foreach ($rawPhones as $phone) {
                        $clean = preg_replace('/[^0-9+]/', '', trim($phone));
                        if (!empty($clean)) $recipients[] = $clean;
                    }
                }
                if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                    if (($handle = fopen($_FILES['csv_file']['tmp_name'], "r")) !== false) {
                        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                            if (isset($data[0])) {
                                $clean = preg_replace('/[^0-9+]/', '', trim($data[0]));
                                if (!empty($clean)) $recipients[] = $clean;
                            }
                        }
                        fclose($handle);
                    }
                }
                break;
        }

        // Sanitize arrays and drop duplicates
        $recipients = array_filter(array_unique($recipients));

        if (empty($recipients)) {
            header('Location: /sms/campaigns/bulk?status=no_recipients');
            exit;
        }

        // Fire sequential loop execution engine
        $successCount = 0;
        foreach ($recipients as $number) {
            $milestoneContext = 'Bulk - ' . ucfirst($flow);
            $dispatched = $this->sendOutboundSms($config, $number, $message, $milestoneContext);
            if ($dispatched) {
                $successCount++;
            }
        }

        header('Location: /sms/campaigns/bulk?status=dispatched&count=' . $successCount);
        exit;
    }

    /**
     * OUTBOUND ENGINE UTILITY METHOD (Your complete MNotify Integration)
     */
    private function sendOutboundSms(array $config, string $phone, string $message, string $milestone = 'Bulk'): bool {
        $endPoint = !empty($config['sms_endpoint']) ? trim($config['sms_endpoint']) : 'https://api.mnotify.com/api/sms/quick';
        $apiKey   = !empty($config['sms_apikey']) ? trim($config['sms_apikey']) : '';
        $senderId = !empty($config['gen_sms_sender_id']) ? trim($config['gen_sms_sender_id']) : 'BTU Notice';

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

        $statusValue = 'Failed';
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
            
            if ($result !== false) {
                $responseArray = json_decode($result, true);
                if (isset($responseArray['status']) && $responseArray['status'] === 'success') {
                    $statusValue = 'Delivered';
                }
            }
        } catch (\Exception $e) {
            error_log("SMS dispatch execution exception error context: " . $e->getMessage());
        }

        // Commit tracking record transaction directly into local database logs
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO sms_logs (phone, milestone, message, status, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$phone, $milestone, $message, $statusValue]);
        } catch (\Exception $dbEx) {
            error_log("Failed archiving log trace data into database matrix: " . $dbEx->getMessage());
        }

        return $statusValue === 'Delivered';
    }
}