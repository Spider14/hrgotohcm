<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Core\Database;

class DashboardController {
    public function __construct() {
        AuthMiddleware::handle();
    }

    public function index(Request $request): void {
        $db = Database::getConnection();
        $userId = (int)$_SESSION['user_id'];

        $notifStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0");
        $notifStmt->execute(['user_id' => $userId]);
        $unreadNotificationsCount = (int)$notifStmt->fetchColumn();

        $totalStaff = (int)($db->query("SELECT COUNT(*) FROM staff_records")->fetchColumn() ?: 0);
        $staffOnLeave = (int)($db->query("SELECT COUNT(DISTINCT user_id) FROM leave_requests WHERE status = 'Approved' AND start_date <= CURDATE() AND end_date >= CURDATE()")->fetchColumn() ?: 0);
        $activeStaff = $totalStaff - $staffOnLeave;
        $onLeave = (int)($db->query("SELECT COUNT(*) FROM leave_requests WHERE status IN ('Pending Supervisor Sign-off', 'Pending HR Approval', 'Approved')")->fetchColumn() ?: 0);
        $pendingAppraisals = (int)($db->query("SELECT COUNT(*) FROM staff_appraisals WHERE status IN ('Pending Comments', 'Pending Supervisor Sign-off', 'Pending HR Approval')")->fetchColumn() ?: 0);
        $activeJobs = (int)($db->query("SELECT COUNT(*) FROM jobs WHERE status = 'open'")->fetchColumn() ?: 0);
        $totalApplicants = (int)($db->query("SELECT COUNT(*) FROM applications")->fetchColumn() ?: 0);

        // Retirement in next 5 years (assuming retirement at 60)
        $retiringSoon = (int)($db->query(
            "SELECT COUNT(*) FROM staff_records WHERE date_of_birth IS NOT NULL AND TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 55 AND 59"
        )->fetchColumn() ?: 0);

        // Pending leave requests (awaiting approval)
        $pendingLeaves = (int)($db->query(
            "SELECT COUNT(*) FROM leave_requests WHERE status IN ('Pending Supervisor Sign-off', 'Pending HR Approval')"
        )->fetchColumn() ?: 0);

        // Age distribution (UN definitions)
        $ageGroups = ['Youth (15-24)' => 0, 'Young Adult (25-44)' => 0, 'Middle-Age (45-64)' => 0, 'Elderly (65+)' => 0];
        $ageStmt = $db->query(
            "SELECT CASE
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 15 AND 24 THEN 'Youth (15-24)'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 25 AND 44 THEN 'Young Adult (25-44)'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 45 AND 64 THEN 'Middle-Age (45-64)'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 65 THEN 'Elderly (65+)'
                ELSE 'Unknown'
            END AS age_group, COUNT(*) AS cnt
            FROM staff_records WHERE date_of_birth IS NOT NULL GROUP BY age_group"
        );
        while ($row = $ageStmt->fetch()) {
            if (isset($ageGroups[$row['age_group']])) $ageGroups[$row['age_group']] = (int)$row['cnt'];
        }

        // Gender distribution
        $genderDist = ['Male' => 0, 'Female' => 0, 'Other' => 0];
        $genderStmt = $db->query(
            "SELECT gender, COUNT(*) AS cnt FROM staff_records WHERE gender IS NOT NULL GROUP BY gender"
        );
        while ($row = $genderStmt->fetch()) {
            $g = ucfirst(strtolower(trim((string)$row['gender'])));
            if ($g === 'Male') $genderDist['Male'] = (int)$row['cnt'];
            elseif ($g === 'Female') $genderDist['Female'] = (int)$row['cnt'];
            else $genderDist['Other'] += (int)$row['cnt'];
        }

        // Staff performance — all staff with current year appraisal score (descending)
        $staffPerformance = [];
        $perfStmt = $db->query(
            "SELECT u.id, u.fullname,
                    sa.score, sa.rating, sa.period_label,
                    COALESCE(d.dept_name, 'N/A') AS dept_name,
                    COALESCE(dg.title, 'N/A') AS designation
             FROM users u
             INNER JOIN staff_records s ON s.user_id = u.id
             LEFT JOIN departments d ON d.dept_id = s.dept_id
             LEFT JOIN designations dg ON dg.id = s.designation_id
             LEFT JOIN staff_appraisals sa ON sa.user_id = u.id
                 AND sa.status IN ('Commented', 'Submitted')
                 AND sa.score IS NOT NULL
                 AND YEAR(sa.created_at) = YEAR(CURDATE())
             ORDER BY sa.score DESC"
        );
        while ($row = $perfStmt->fetch()) {
            $staffPerformance[] = $row;
        }

        $dashboardStats = [
            'total_staff'        => $totalStaff,
            'active_staff'       => $activeStaff,
            'on_leave'           => $onLeave,
            'pending_appraisals' => $pendingAppraisals,
            'active_jobs'        => $activeJobs,
            'total_applicants'   => $totalApplicants,
            'retiring_soon'      => $retiringSoon,
            'pending_leaves'     => $pendingLeaves,
        ];

        $pageTitle = "HRGoTo HCM - Command Dashboard Hub";
        require_once __DIR__ . '/../Views/dashboard/index.php';
    }

    public function markAsRead(Request $request): void {
        CSRFMiddleware::validate($request);
        $db = Database::getConnection();
        $userId = (int)$_SESSION['user_id'];
        $body = $request->getBody();
        $id = isset($body['id']) ? (int)$body['id'] : 0;

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id");
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
        }

        if ($request->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            return;
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/dashboard'));
    }

    public function markAllRead(Request $request): void {
        CSRFMiddleware::validate($request);
        $db = Database::getConnection();
        $userId = (int)$_SESSION['user_id'];

        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0");
        $stmt->execute(['user_id' => $userId]);

        if ($request->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            return;
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/dashboard'));
    }
}
