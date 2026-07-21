<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Helpers\Security;
use PDO;
use Throwable;

class AttendanceController {
    public function __construct() {
        AuthMiddleware::handle();
    }

    private function ensureAttendanceTables(): void {
        $db = Database::getConnection();

        $db->exec("CREATE TABLE IF NOT EXISTS attendance_log (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            work_date DATE NOT NULL,
            clock_in DATETIME NULL,
            clock_out DATETIME NULL,
            clock_in_ip VARCHAR(45) NULL,
            clock_out_ip VARCHAR(45) NULL,
            latitude DECIMAL(10,7) NULL,
            longitude DECIMAL(10,7) NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'present',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_user_date (user_id, work_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS attendance_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            work_start_time TIME NOT NULL DEFAULT '08:00:00',
            work_end_time TIME NOT NULL DEFAULT '17:00:00',
            late_threshold_minutes INT NOT NULL DEFAULT 15,
            half_day_hours DECIMAL(4,2) NOT NULL DEFAULT 4.00,
            grace_period_minutes INT NOT NULL DEFAULT 15,
            weekend_days VARCHAR(40) NOT NULL DEFAULT 'Saturday,Sunday',
            updated_by INT NULL,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $db->query("SELECT COUNT(*) FROM attendance_settings");
        if ((int)$stmt->fetchColumn() === 0) {
            $db->exec("INSERT INTO attendance_settings (work_start_time, work_end_time) VALUES ('08:00:00', '17:00:00')");
        }
    }

    public function index(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensureAttendanceTables();

        $db = Database::getConnection();
        $today = date('Y-m-d');

        $todayAttendance = $db->prepare(
            "SELECT a.*, u.fullname, s.staff_id_card, d.dept_name
             FROM attendance_log a
             JOIN users u ON a.user_id = u.id
             LEFT JOIN staff_records s ON s.user_id = u.id
             LEFT JOIN departments d ON d.dept_id = s.dept_id
             WHERE a.work_date = :today
             ORDER BY a.clock_in DESC"
        );
        $todayAttendance->execute(['today' => $today]);
        $todayRows = $todayAttendance->fetchAll(PDO::FETCH_ASSOC);

        $summary = $db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent,
                SUM(CASE WHEN status = 'half-day' THEN 1 ELSE 0 END) AS half_day,
                SUM(CASE WHEN clock_out IS NULL AND clock_in IS NOT NULL THEN 1 ELSE 0 END) AS clocked_in
             FROM attendance_log
             WHERE work_date = :today"
        );
        $summary->execute(['today' => $today]);
        $stats = $summary->fetch(PDO::FETCH_ASSOC) ?: [];

        $settings = $db->query("SELECT * FROM attendance_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

        $pageTitle = 'HRGoTo HCM - Attendance Dashboard';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/attendance/index.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function myAttendance(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        $this->ensureAttendanceTables();

        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $today = date('Y-m-d');

        $todayRecord = $db->prepare("SELECT * FROM attendance_log WHERE user_id = :uid AND work_date = :today LIMIT 1");
        $todayRecord->execute(['uid' => $userId, 'today' => $today]);
        $current = $todayRecord->fetch(PDO::FETCH_ASSOC) ?: null;

        $month = $request->getParam('month') ?? date('m');
        $year = $request->getParam('year') ?? date('Y');
        $startDate = sprintf('%s-%s-01', $year, str_pad($month, 2, '0', STR_PAD_LEFT));
        $endDate = date('Y-m-t', strtotime($startDate));

        $rows = $db->prepare(
            "SELECT * FROM attendance_log
             WHERE user_id = :uid AND work_date BETWEEN :start AND :end
             ORDER BY work_date DESC"
        );
        $rows->execute(['uid' => $userId, 'start' => $startDate, 'end' => $endDate]);
        $attendanceRows = $rows->fetchAll(PDO::FETCH_ASSOC);

        $settings = $db->query("SELECT * FROM attendance_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

        $pageTitle = 'HRGoTo HCM - My Attendance';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/attendance/my.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function clock(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        CSRFMiddleware::validate($request);
        $this->ensureAttendanceTables();

        $db = Database::getConnection();

        // Read office location config
        $config = $db->query("SELECT office_latitude, office_longitude, office_radius_meters, office_ip_whitelist FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
        $officeLat      = (float)($config['office_latitude'] ?? 0);
        $officeLng      = (float)($config['office_longitude'] ?? 0);
        $maxRadiusMeters = (int)($config['office_radius_meters'] ?? 200);
        $ipList         = trim((string)($config['office_ip_whitelist'] ?? ''));
        $allowedIPs     = $ipList !== '' ? preg_split('/[\s,]+/', $ipList) : [];

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // --- LOCATION VALIDATION ---
        $body = $request->getBody();
        $userLat  = isset($body['latitude'])  ? (float)$body['latitude']  : 0.0;
        $userLng  = isset($body['longitude']) ? (float)$body['longitude'] : 0.0;
        $hasGps   = ($userLat !== 0.0 && $userLng !== 0.0);
        $ipAllowed = in_array($ip, $allowedIPs, true);
        $hasGeoConfig = ($officeLat !== 0.0 && $officeLng !== 0.0);

        if (!$ipAllowed && $hasGeoConfig) {
            if ($hasGps) {
                $dist = $this->haversineDistance($officeLat, $officeLng, $userLat, $userLng);
                if ($dist > $maxRadiusMeters) {
                    $this->redirectWithError('You+must+be+at+the+office+to+clock+in.+Your+location+is+' . round($dist) . 'm+away+(max+' . $maxRadiusMeters . 'm).');
                }
            } else {
                $this->redirectWithError('Location+data+required.+Please+enable+GPS+or+connect+to+the+office+network.');
            }
        }

        $existing = $db->prepare("SELECT * FROM attendance_log WHERE user_id = :uid AND work_date = :today LIMIT 1");
        $existing->execute(['uid' => $userId, 'today' => $today]);
        $record = $existing->fetch(PDO::FETCH_ASSOC);

        try {
            if ($record) {
                if ($record['clock_out'] !== null) {
                    $this->redirectWithError('Already+clocked+out+for+today');
                }
                $settings = $db->query("SELECT * FROM attendance_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
                $endTime = $settings['work_end_time'] ?? '17:00:00';

                $db->prepare("UPDATE attendance_log SET clock_out = :co, clock_out_ip = :ip, updated_at = NOW() WHERE id = :id")
                    ->execute(['co' => $now, 'ip' => $ip, 'id' => $record['id']]);

                $clockIn = new \DateTime($record['clock_in']);
                $clockOut = new \DateTime($now);
                $workedHours = ($clockOut->getTimestamp() - $clockIn->getTimestamp()) / 3600;

                $halfDayHours = (float)($settings['half_day_hours'] ?? 4);
                $newStatus = $workedHours < $halfDayHours ? 'half-day' : 'present';

                $db->prepare("UPDATE attendance_log SET status = :s WHERE id = :id")
                    ->execute(['s' => $newStatus, 'id' => $record['id']]);

                $totalMinutes = (int)(($clockOut->getTimestamp() - $clockIn->getTimestamp()) / 60);
                $h = intdiv($totalMinutes, 60);
                $m = $totalMinutes % 60;
                $this->redirectWithOk('Clocked+out+successfully.+Duration:+' . $h . 'h+' . $m . 'm');
            } else {
                $settings = $db->query("SELECT * FROM attendance_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
                $startTime = $settings['work_start_time'] ?? '08:00:00';
                $gracePeriod = (int)($settings['grace_period_minutes'] ?? 15);
                $lateThreshold = (int)($settings['late_threshold_minutes'] ?? 15);

                $scheduledStart = strtotime($today . ' ' . $startTime);
                $graceEnd = $scheduledStart + ($gracePeriod * 60);
                $lateEnd = $scheduledStart + ($lateThreshold * 60);
                $clockInTs = time();

                if ($clockInTs > $lateEnd) {
                    $status = 'late';
                } else {
                    $status = 'present';
                }

                $db->prepare(
                    "INSERT INTO attendance_log (user_id, work_date, clock_in, clock_in_ip, status, latitude, longitude)
                     VALUES (:uid, :date, :ci, :ip, :status, :lat, :lng)"
                )->execute([
                    'uid' => $userId,
                    'date' => $today,
                    'ci' => $now,
                    'ip' => $ip,
                    'status' => $status,
                    'lat' => $hasGps ? $userLat : null,
                    'lng' => $hasGps ? $userLng : null,
                ]);

                $this->redirectWithOk('Clocked+in+successfully+at+' . date('H:i'));
            }
        } catch (Throwable $e) {
            $this->redirectWithError(urlencode($e->getMessage()));
        }
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function redirectWithOk(string $message): void {
        Security::setFlash('ok', urldecode($message));
        header('Location: ' . $this->getAppUrl() . '/staff/attendance');
        exit;
    }

    private function redirectWithError(string $message): void {
        Security::setFlash('error', urldecode($message));
        header('Location: ' . $this->getAppUrl() . '/staff/attendance');
        exit;
    }

    public function register(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensureAttendanceTables();

        $db = Database::getConnection();
        $date = $request->getParam('date') ?? date('Y-m-d');
        $deptId = $request->getParam('dept_id');
        $statusFilter = $request->getParam('status');

        $sql = "SELECT a.*, u.fullname, u.email, s.staff_id_card, d.dept_name, dg.title AS designation
                FROM attendance_log a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN staff_records s ON s.user_id = u.id
                LEFT JOIN departments d ON d.dept_id = s.dept_id
                LEFT JOIN designations dg ON dg.id = s.designation_id
                WHERE a.work_date = :date";
        $params = ['date' => $date];

        if (!empty($deptId)) {
            $sql .= " AND s.dept_id = :dept_id";
            $params['dept_id'] = (int)$deptId;
        }
        if (!empty($statusFilter)) {
            $sql .= " AND a.status = :status";
            $params['status'] = $statusFilter;
        }
        $sql .= " ORDER BY a.clock_in DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $departments = $db->query("SELECT dept_id, dept_name FROM departments ORDER BY dept_name")->fetchAll(PDO::FETCH_ASSOC);
        $settings = $db->query("SELECT * FROM attendance_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

        $pageTitle = 'HRGoTo HCM - Attendance Register';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/attendance/register.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function reports(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensureAttendanceTables();

        $db = Database::getConnection();
        $month = $request->getParam('month') ?? date('m');
        $year = $request->getParam('year') ?? date('Y');
        $deptId = $request->getParam('dept_id');

        $startDate = sprintf('%s-%s-01', $year, str_pad($month, 2, '0', STR_PAD_LEFT));
        $endDate = date('Y-m-t', strtotime($startDate));

        $sql = "SELECT
                    a.user_id,
                    u.fullname,
                    s.staff_id_card,
                    d.dept_name,
                    COUNT(a.id) AS total_days,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_days,
                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) AS late_days,
                    SUM(CASE WHEN a.status = 'half-day' THEN 1 ELSE 0 END) AS half_days,
                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
                    SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, a.clock_in, a.clock_out))) AS total_hours
                FROM attendance_log a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN staff_records s ON s.user_id = u.id
                LEFT JOIN departments d ON d.dept_id = s.dept_id
                WHERE a.work_date BETWEEN :start AND :end";
        $params = ['start' => $startDate, 'end' => $endDate];

        if (!empty($deptId)) {
            $sql .= " AND s.dept_id = :dept_id";
            $params['dept_id'] = (int)$deptId;
        }
        $sql .= " GROUP BY a.user_id ORDER BY u.fullname";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $reportRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $departments = $db->query("SELECT dept_id, dept_name FROM departments ORDER BY dept_name")->fetchAll(PDO::FETCH_ASSOC);
        $settings = $db->query("SELECT * FROM attendance_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

        $pageTitle = 'HRGoTo HCM - Attendance Reports';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/attendance/reports.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function settingsView(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensureAttendanceTables();

        $db = Database::getConnection();
        $settings = $db->query("SELECT * FROM attendance_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

        $pageTitle = 'HRGoTo HCM - Attendance Settings';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/attendance/settings.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function saveSettings(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $this->ensureAttendanceTables();

        $body = $request->getBody();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $db = Database::getConnection();
        try {
            $stmt = $db->prepare(
                "UPDATE attendance_settings SET
                    work_start_time = :wst,
                    work_end_time = :wet,
                    late_threshold_minutes = :lt,
                    half_day_hours = :hdh,
                    grace_period_minutes = :gp,
                    weekend_days = :wd,
                    updated_by = :ub,
                    updated_at = NOW()
                 WHERE id = 1"
            );
            $stmt->execute([
                'wst' => $body['work_start_time'] ?? '08:00:00',
                'wet' => $body['work_end_time'] ?? '17:00:00',
                'lt' => (int)($body['late_threshold_minutes'] ?? 15),
                'hdh' => (float)($body['half_day_hours'] ?? 4),
                'gp' => (int)($body['grace_period_minutes'] ?? 15),
                'wd' => $body['weekend_days'] ?? 'Saturday,Sunday',
                'ub' => $userId,
            ]);
            Security::setFlash('ok', 'Settings saved');
            header('Location: ' . $this->getAppUrl() . '/admin/attendance/settings');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: ' . $this->getAppUrl() . '/admin/attendance/settings');
        }
        exit;
    }

    private function getAppUrl(): string {
        return rtrim(\App\Helpers\Security::escape($_ENV['APP_URL'] ?? ''), '/');
    }
}
