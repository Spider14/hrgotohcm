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

class WorkforceController {
    public function __construct() {
        AuthMiddleware::handle();
    }

    private function ensureModuleTables(): void {
        $db = Database::getConnection();

        $db->exec("CREATE TABLE IF NOT EXISTS staff_promotions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            from_designation VARCHAR(150) NULL,
            to_designation VARCHAR(150) NULL,
            effective_date DATE NULL,
            current_rank VARCHAR(150) NULL,
            requested_rank VARCHAR(150) NULL,
            remarks TEXT NULL,
            supervisor_comment TEXT NULL,
            hr_comment TEXT NULL,
            supervisor_id INT NULL,
            hr_id INT NULL,
            supporting_document VARCHAR(255) NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'Pending Comments',
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_promotions_user (user_id),
            INDEX idx_promotions_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS staff_appraisals (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            period_label VARCHAR(100) NOT NULL,
            score DECIMAL(5,2) NOT NULL,
            self_score DECIMAL(5,2) NULL,
            rating VARCHAR(40) NOT NULL,
            summary TEXT NULL,
            supervisor_comment TEXT NULL,
            hr_comment TEXT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'Pending Comments',
            appraiser_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_appraisals_user (user_id),
            INDEX idx_appraisals_period (period_label)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS leave_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            leave_type VARCHAR(60) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            total_days INT NOT NULL,
            reason TEXT NOT NULL,
            supervisor_comment TEXT NULL,
            hr_comment TEXT NULL,
            supervisor_id INT NULL,
            hr_id INT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'Pending Supervisor Sign-off',
            review_note TEXT NULL,
            reviewed_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_leave_user (user_id),
            INDEX idx_leave_status (status),
            INDEX idx_leave_dates (start_date, end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->addColumnIfMissing($db, 'staff_promotions', 'current_rank', 'VARCHAR(150) NULL');
        $this->addColumnIfMissing($db, 'staff_promotions', 'requested_rank', 'VARCHAR(150) NULL');
        $this->addColumnIfMissing($db, 'staff_promotions', 'supervisor_comment', 'TEXT NULL');
        $this->addColumnIfMissing($db, 'staff_promotions', 'hr_comment', 'TEXT NULL');
        $this->addColumnIfMissing($db, 'staff_promotions', 'supervisor_id', 'INT NULL');
        $this->addColumnIfMissing($db, 'staff_promotions', 'hr_id', 'INT NULL');
        $this->addColumnIfMissing($db, 'staff_promotions', 'supporting_document', 'VARCHAR(255) NULL');

        $this->addColumnIfMissing($db, 'staff_appraisals', 'self_score', 'DECIMAL(5,2) NULL');
        $this->addColumnIfMissing($db, 'staff_appraisals', 'supervisor_comment', 'TEXT NULL');
        $this->addColumnIfMissing($db, 'staff_appraisals', 'hr_comment', 'TEXT NULL');
        $this->addColumnIfMissing($db, 'staff_appraisals', 'staff_approval_note', 'TEXT NULL');
        $this->addColumnIfMissing($db, 'staff_appraisals', 'finalized_by', 'INT NULL');

        $this->addColumnIfMissing($db, 'leave_requests', 'supervisor_comment', 'TEXT NULL');
        $this->addColumnIfMissing($db, 'leave_requests', 'hr_comment', 'TEXT NULL');
        $this->addColumnIfMissing($db, 'leave_requests', 'supervisor_id', 'INT NULL');
        $this->addColumnIfMissing($db, 'leave_requests', 'hr_id', 'INT NULL');
    }

    private function addColumnIfMissing(PDO $db, string $table, string $column, string $definition): void {
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name");
        $stmt->execute([
            'table_name' => $table,
            'column_name' => $column,
        ]);
        if ((int)$stmt->fetchColumn() === 0) {
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }

    public function promotions(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor']);
        $this->ensureModuleTables();

        $db = Database::getConnection();

        $summary = $db->query("SELECT
            COUNT(*) AS total_records,
            SUM(CASE WHEN status IN ('Pending Comments', 'Pending Supervisor Sign-off', 'Pending HR Approval') THEN 1 ELSE 0 END) AS proposed_records,
            SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved_records,
            SUM(CASE WHEN status IN ('Rejected', 'Declined') THEN 1 ELSE 0 END) AS rejected_records
            FROM staff_promotions
        ")->fetch(PDO::FETCH_ASSOC) ?: [];

        $rows = $db->query("SELECT
            p.id,
            COALESCE(p.current_rank, p.from_designation) AS current_rank,
            COALESCE(p.requested_rank, p.to_designation) AS requested_rank,
            p.status,
            p.remarks,
            p.supporting_document,
            p.supervisor_comment,
            p.hr_comment,
            p.created_at,
            u.fullname,
            s.staff_id_card,
            d.dept_name
            FROM staff_promotions p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN staff_records s ON s.user_id = u.id
            LEFT JOIN departments d ON d.dept_id = s.dept_id
            WHERE p.status <> 'Completed'
            ORDER BY p.created_at DESC
            LIMIT 200
        ")->fetchAll(PDO::FETCH_ASSOC);

        $staff = $db->query("SELECT u.id, u.fullname, s.staff_id_card
            FROM users u
            JOIN staff_records s ON s.user_id = u.id
            WHERE u.deleted_at IS NULL
            ORDER BY u.fullname ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'HRGoTo HCM - Promotions';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/staff/promotions.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function createPromotion(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        CSRFMiddleware::validate($request);
        $this->ensureModuleTables();

        $body = $request->getBody();
        $role = (string)($_SESSION['user_role'] ?? 'Staff');
        $sessionUserId = (int)($_SESSION['user_id'] ?? 0);
        $userId = in_array($role, ['Super Admin', 'HR Manager'], true)
            ? (int)($body['user_id'] ?? 0)
            : $sessionUserId;
        $fromDesignation = trim((string)($body['from_designation'] ?? ''));
        $toDesignation = trim((string)($body['to_designation'] ?? ''));
        $effectiveDate = trim((string)($body['effective_date'] ?? ''));
        $remarks = trim((string)($body['remarks'] ?? ''));

        if ($userId <= 0 || $fromDesignation === '' || $toDesignation === '' || $effectiveDate === '') {
            Security::setFlash('error', 'Invalid promotion payload');
            header('Location: /staff/promotions');
            exit;
        }

        try {
            $db = Database::getConnection();
            $docUrl = null;
            if (!empty($_FILES['supporting_document']) && (int)$_FILES['supporting_document']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo((string)$_FILES['supporting_document']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                    $name = bin2hex(random_bytes(12)) . '.' . $ext;
                    $dest = __DIR__ . '/../../public/uploads/documents/' . $name;
                    if (move_uploaded_file((string)$_FILES['supporting_document']['tmp_name'], $dest)) {
                        $docUrl = '/uploads/documents/' . $name;
                    }
                }
            }

            $stmt = $db->prepare("INSERT INTO staff_promotions
                (user_id, from_designation, to_designation, effective_date, current_rank, requested_rank,
                 remarks, status, supporting_document, created_by)
                VALUES (:user_id, :from_designation, :to_designation, :effective_date, :current_rank, :requested_rank,
                        :remarks, 'Pending HR Approval', :supporting_document, :created_by)");

            $stmt->execute([
                'user_id' => $userId,
                'from_designation' => $fromDesignation,
                'to_designation' => $toDesignation,
                'effective_date' => $effectiveDate,
                'current_rank' => $fromDesignation,
                'requested_rank' => $toDesignation,
                'remarks' => $remarks,
                'supporting_document' => $docUrl,
                'created_by' => (int)($_SESSION['user_id'] ?? 0),
            ]);

            \App\Helpers\Notification::create($userId, 'Promotion Approved', 'You have been promoted. Please check your promotion details.', 'success');
            Security::setFlash('ok', 'Promotion record saved');
            header('Location: /staff/promotions');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /staff/promotions');
        }
        exit;
    }

    public function appraisals(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensureModuleTables();

        $db = Database::getConnection();

    $summary = $db->query("SELECT
        COUNT(*) AS total_records,
        AVG(score) AS average_score,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed_records,
        SUM(CASE WHEN status = 'Staff Approved' THEN 1 ELSE 0 END) AS pending_final_records
        FROM staff_appraisals
    ")->fetch(PDO::FETCH_ASSOC) ?: [];

    $rows = $db->query("SELECT
        a.id,
        a.period_label,
        a.score,
        a.rating,
        a.status,
        a.summary,
        a.supervisor_comment,
        a.staff_approval_note,
        a.hr_comment,
        a.created_at,
        u.fullname,
        s.staff_id_card,
        d.dept_name
        FROM staff_appraisals a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN staff_records s ON s.user_id = u.id
        LEFT JOIN departments d ON d.dept_id = s.dept_id
        ORDER BY a.created_at DESC
        LIMIT 200
    ")->fetchAll(PDO::FETCH_ASSOC);

        $staff = $db->query("SELECT u.id, u.fullname, s.staff_id_card
            FROM users u
            JOIN staff_records s ON s.user_id = u.id
            WHERE u.deleted_at IS NULL
            ORDER BY u.fullname ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'HRGoTo HCM - Staff Appraisals';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/staff/appraisals.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function appraisalPdf(Request $request): void
    {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $appraisalId = (int)$request->getParam('id', 0);
        if ($appraisalId <= 0) {
            http_response_code(400);
            echo "Invalid appraisal ID.";
            exit;
        }
        \App\Helpers\PdfHelper::generateAppraisalPdf($appraisalId);
    }

    public function createAppraisal(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $this->ensureModuleTables();

        $body = $request->getBody();
        $userId = (int)($body['user_id'] ?? 0);
        $periodLabel = trim((string)($body['period_label'] ?? ''));
        $score = (float)($body['score'] ?? 0);
        $summary = trim((string)($body['summary'] ?? ''));

        if ($userId <= 0 || $periodLabel === '' || $score < 0 || $score > 100) {
            Security::setFlash('error', 'Invalid appraisal payload');
            header('Location: /staff/appraisals');
            exit;
        }

        $rating = 'Needs Improvement';
        if ($score >= 85) {
            $rating = 'Excellent';
        } elseif ($score >= 70) {
            $rating = 'Good';
        } elseif ($score >= 50) {
            $rating = 'Satisfactory';
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO staff_appraisals
                (user_id, period_label, score, rating, summary, status, appraiser_id)
                VALUES (:user_id, :period_label, :score, :rating, :summary, 'Submitted', :appraiser_id)");

            $stmt->execute([
                'user_id' => $userId,
                'period_label' => $periodLabel,
                'score' => $score,
                'rating' => $rating,
                'summary' => $summary,
                'appraiser_id' => (int)($_SESSION['user_id'] ?? 0),
            ]);

            Security::setFlash('ok', 'Appraisal record saved');
            header('Location: /staff/appraisals');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /staff/appraisals');
        }
        exit;
    }

    public function finalizeAppraisal(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $this->ensureModuleTables();
        $body = $request->getBody();
        $id = (int)($body['appraisal_id'] ?? 0);
        $hrComment = trim((string)($body['hr_comment'] ?? ''));
        if ($id <= 0) {
            Security::setFlash('error', 'Invalid appraisal ID');
            header('Location: /staff/appraisals');
            exit;
        }
        try {
            $db = Database::getConnection();
            $check = $db->prepare("SELECT status FROM staff_appraisals WHERE id = :id");
            $check->execute(['id' => $id]);
            $row = $check->fetch(PDO::FETCH_ASSOC);
            if (!$row || $row['status'] !== 'Staff Approved') {
                Security::setFlash('error', 'Only staff-approved appraisals can be finalized.');
                header('Location: /staff/appraisals');
                exit;
            }
            $stmt = $db->prepare("UPDATE staff_appraisals SET status = 'Completed', finalized_by = :uid, hr_comment = :comment WHERE id = :id");
            $stmt->execute([
                'uid' => (int)($_SESSION['user_id'] ?? 0),
                'comment' => $hrComment,
                'id' => $id,
            ]);
            $stmtU = $db->prepare("SELECT user_id FROM staff_appraisals WHERE id = :id LIMIT 1");
            $stmtU->execute(['id' => $id]);
            $staffUserId = (int)$stmtU->fetchColumn();
            if ($staffUserId > 0) {
                \App\Helpers\Notification::create($staffUserId, 'Appraisal Finalized', 'Your appraisal has been finalized by management.', 'info');
            }
            Security::setFlash('ok', 'Appraisal finalized');
            header('Location: /staff/appraisals');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /staff/appraisals');
        }
        exit;
    }

    public function leaveIndex(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff', 'Recruiter']);
        $this->ensureModuleTables();

        $db = Database::getConnection();
        $role = (string)($_SESSION['user_role'] ?? 'Staff');
        $currentUser = (int)($_SESSION['user_id'] ?? 0);

        if (in_array($role, ['Super Admin', 'HR Manager'], true)) {
            $query = "SELECT
                l.id,
                l.leave_type,
                l.start_date,
                l.end_date,
                l.total_days,
                l.reason,
                l.status,
                l.supervisor_comment,
                l.hr_comment,
                l.created_at,
                u.fullname,
                s.staff_id_card,
                d.dept_name
                FROM leave_requests l
                JOIN users u ON l.user_id = u.id
                LEFT JOIN staff_records s ON s.user_id = u.id
                LEFT JOIN departments d ON d.dept_id = s.dept_id
                ORDER BY l.created_at DESC
                LIMIT 200";
            $rows = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($role === 'Supervisor') {
            $stmt = $db->prepare("SELECT
                l.id,
                l.leave_type,
                l.start_date,
                l.end_date,
                l.total_days,
                l.reason,
                l.status,
                l.supervisor_comment,
                l.hr_comment,
                l.created_at,
                u.fullname,
                s.staff_id_card,
                d.dept_name
                FROM leave_requests l
                JOIN users u ON l.user_id = u.id
                LEFT JOIN staff_records s ON s.user_id = u.id
                LEFT JOIN departments d ON d.dept_id = s.dept_id
                LEFT JOIN staff_records owner ON owner.user_id = u.id
                WHERE owner.supervisor_user_id = :supervisor_id
                ORDER BY l.created_at DESC
                LIMIT 200");
            $stmt->execute(['supervisor_id' => $currentUser]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $db->prepare("SELECT
                l.id,
                l.leave_type,
                l.start_date,
                l.end_date,
                l.total_days,
                l.reason,
                l.status,
                l.supervisor_comment,
                l.hr_comment,
                l.created_at,
                u.fullname,
                s.staff_id_card,
                d.dept_name
                FROM leave_requests l
                JOIN users u ON l.user_id = u.id
                LEFT JOIN staff_records s ON s.user_id = u.id
                LEFT JOIN departments d ON d.dept_id = s.dept_id
                WHERE l.user_id = :user_id
                ORDER BY l.created_at DESC
                LIMIT 200");
            $stmt->execute(['user_id' => $currentUser]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $summary = $db->query("SELECT
            COUNT(*) AS total_records,
            SUM(CASE WHEN status IN ('Pending Supervisor Sign-off', 'Pending HR Approval', 'Pending Comments') THEN 1 ELSE 0 END) AS pending_records,
            SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved_records,
            SUM(CASE WHEN status IN ('Rejected', 'Declined') THEN 1 ELSE 0 END) AS rejected_records
            FROM leave_requests
        ")->fetch(PDO::FETCH_ASSOC) ?: [];

        $staff = $db->query("SELECT u.id, u.fullname, s.staff_id_card
            FROM users u
            JOIN staff_records s ON s.user_id = u.id
            WHERE u.deleted_at IS NULL
            ORDER BY u.fullname ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'HRGoTo HCM - Leave Management';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/staff/manage_leave.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function createLeaveRequest(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff', 'Recruiter']);
        CSRFMiddleware::validate($request);
        $this->ensureModuleTables();

        $body = $request->getBody();
        $role = (string)($_SESSION['user_role'] ?? 'Staff');

        $userId = in_array($role, ['Super Admin', 'HR Manager'], true)
            ? (int)($body['user_id'] ?? 0)
            : (int)($_SESSION['user_id'] ?? 0);

        $leaveType = trim((string)($body['leave_type'] ?? ''));
        $startDate = trim((string)($body['start_date'] ?? ''));
        $endDate = trim((string)($body['end_date'] ?? ''));
        $reason = trim((string)($body['reason'] ?? ''));

        if ($userId <= 0 || $leaveType === '' || $startDate === '' || $endDate === '' || $reason === '') {
            Security::setFlash('error', 'Invalid leave request payload');
            header('Location: /staff/leave');
            exit;
        }

        $days = (int)((strtotime($endDate) - strtotime($startDate)) / 86400) + 1;
        if ($days <= 0) {
            Security::setFlash('error', 'Invalid leave date range');
            header('Location: /staff/leave');
            exit;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO leave_requests
                (user_id, leave_type, start_date, end_date, total_days, reason, status)
                VALUES (:user_id, :leave_type, :start_date, :end_date, :total_days, :reason, 'Pending Supervisor Sign-off')");

            $stmt->execute([
                'user_id' => $userId,
                'leave_type' => $leaveType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $days,
                'reason' => $reason,
            ]);

            Security::setFlash('ok', 'Leave request submitted');
            header('Location: /staff/leave');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /staff/leave');
        }
        exit;
    }

    public function reviewLeaveRequest(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor']);
        CSRFMiddleware::validate($request);
        $this->ensureModuleTables();

        $body = $request->getBody();
        $leaveId = (int)($body['leave_id'] ?? 0);
        $status = trim((string)($body['status'] ?? ''));
        $note = trim((string)($body['review_note'] ?? $body['comment'] ?? ''));
        $role = (string)($_SESSION['user_role'] ?? 'Staff');

        $allowed = $role === 'Supervisor'
            ? ['Pending HR Approval', 'Declined']
            : ['Approved', 'Rejected'];

        if ($leaveId <= 0 || !in_array($status, $allowed, true)) {
            Security::setFlash('error', 'Invalid review payload');
            header('Location: /staff/leave');
            exit;
        }

        try {
            $db = Database::getConnection();
            if ($role === 'Supervisor') {
                $check = $db->prepare("SELECT COUNT(*) FROM leave_requests l
                    JOIN staff_records s ON s.user_id = l.user_id
                    WHERE l.id = :leave_id AND s.supervisor_user_id = :supervisor_id");
                $check->execute(['leave_id' => $leaveId, 'supervisor_id' => (int)($_SESSION['user_id'] ?? 0)]);
                if ((int)$check->fetchColumn() === 0) {
                    Security::setFlash('error', 'You can only review leave requests from your supervisees.');
                    header('Location: /staff/leave');
                    exit;
                }
                $stmt = $db->prepare("UPDATE leave_requests
                    SET status = :status, supervisor_comment = :comment, supervisor_id = :reviewed_by
                    WHERE id = :id");
                $stmt->execute([
                    'status' => $status,
                    'comment' => $note,
                    'reviewed_by' => (int)($_SESSION['user_id'] ?? 0),
                    'id' => $leaveId,
                ]);
            } else {
                $stmt = $db->prepare("UPDATE leave_requests
                    SET status = :status, hr_comment = :comment, hr_id = :reviewed_by
                    WHERE id = :id");
                $stmt->execute([
                    'status' => $status,
                    'comment' => $note,
                    'reviewed_by' => (int)($_SESSION['user_id'] ?? 0),
                    'id' => $leaveId,
                ]);
                $this->notifyLeaveDecision((int)$leaveId, $status, $note, $db);
            }

            $stmtU = $db->prepare("SELECT user_id FROM leave_requests WHERE id = :id LIMIT 1");
            $stmtU->execute(['id' => $leaveId]);
            $staffUserId = (int)$stmtU->fetchColumn();
            if ($staffUserId > 0) {
                \App\Helpers\Notification::create($staffUserId, 'Leave Request Reviewed', "Your leave request has been {$status}.", 'info');
            }
            Security::setFlash('ok', 'Leave request reviewed');
            header('Location: /staff/leave');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /staff/leave');
        }
        exit;
    }

    public function reviewPromotion(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $this->ensureModuleTables();

        $body = $request->getBody();
        $id = (int)($body['promotion_id'] ?? 0);
        $action = trim((string)($body['action'] ?? ''));
        $comment = trim((string)($body['comment'] ?? ''));

        if ($id <= 0 || !in_array($action, ['Approved', 'Declined'], true)) {
            Security::setFlash('error', 'Invalid review payload');
            header('Location: /staff/promotions');
            exit;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE staff_promotions SET status = :status, hr_comment = :comment, hr_id = :reviewer WHERE id = :id");
            $stmt->execute(['status' => $action, 'comment' => $comment, 'reviewer' => (int)($_SESSION['user_id'] ?? 0), 'id' => $id]);

            $stmtU = $db->prepare("SELECT user_id FROM staff_promotions WHERE id = :id LIMIT 1");
            $stmtU->execute(['id' => $id]);
            $staffUserId = (int)$stmtU->fetchColumn();
            if ($staffUserId > 0) {
                \App\Helpers\Notification::create($staffUserId, 'Promotion Review Complete', "Your promotion request has been {$action}.", $action === 'Approved' ? 'success' : 'danger');
            }
            Security::setFlash('ok', "Promotion {$action}");
            header('Location: /staff/promotions');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /staff/promotions');
        }
        exit;
    }

    private function notifyLeaveDecision(int $leaveId, string $status, string $note, PDO $db): void {
        $stmt = $db->prepare("SELECT l.id, u.fullname, s.phone_one
            FROM leave_requests l
            JOIN users u ON u.id = l.user_id
            LEFT JOIN staff_records s ON s.user_id = u.id
            WHERE l.id = :id LIMIT 1");
        $stmt->execute(['id' => $leaveId]);
        $leave = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$leave || empty($leave['phone_one'])) {
            return;
        }

        $config = $db->query("SELECT sms_endpoint, gen_sms_sender_id, sms_apikey FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
        $message = "Dear {$leave['fullname']}, your leave request is {$status}.";
        if ($note !== '') {
            $message .= " Reason: {$note}";
        }

        $endPoint = !empty($config['sms_endpoint']) ? trim((string)$config['sms_endpoint']) : 'https://api.mnotify.com/api/sms/quick';
        $apiKey = !empty($config['sms_apikey']) ? trim((string)$config['sms_apikey']) : '';
        $senderId = !empty($config['gen_sms_sender_id']) ? trim((string)$config['gen_sms_sender_id']) : 'HRGoTo';
        if ($apiKey === '') {
            return;
        }

        $url = $endPoint . '?key=' . $apiKey;
        $data = [
            'recipient' => [trim((string)$leave['phone_one'])],
            'sender' => $senderId,
            'message' => $message,
            'is_schedule' => 'false',
            'schedule_date' => ''
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_exec($ch);
        curl_close($ch);
    }
}
