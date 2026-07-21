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

class StaffServicesController {
    public function __construct() {
        AuthMiddleware::handle();
    }

    public function portal(Request $request): void {
        AuthMiddleware::checkRole(['Staff']);
        $this->index($request);
    }

    public function index(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        $this->ensureTables();

        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $role = (string)($_SESSION['user_role'] ?? 'Staff');

        $stmt = $db->prepare(
            "SELECT u.id, u.fullname, u.email, s.phone_one, s.phone_two, s.hometown, s.region, s.nationality,
                    s.marital_status, s.avatar_url, s.supervisor_user_id, s.staff_id_card, d.dept_name, dg.title AS designation
             FROM users u
             LEFT JOIN staff_records s ON s.user_id = u.id
             LEFT JOIN departments d ON d.dept_id = s.dept_id
             LEFT JOIN designations dg ON dg.id = s.designation_id
             WHERE u.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $supervisors = $db->query(
            "SELECT u.id, u.fullname, COALESCE(s.staff_id_card, '') AS staff_id_card
             FROM users u
             LEFT JOIN staff_records s ON s.user_id = u.id
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.deleted_at IS NULL AND (r.role_name = 'Supervisor' OR r.role_name = 'HR Manager' OR r.role_name = 'Super Admin')
             ORDER BY u.fullname"
        )->fetchAll(PDO::FETCH_ASSOC);

        $leaveRows = $db->prepare(
            "SELECT id, leave_type, start_date, end_date, total_days, status, reason, created_at
             FROM leave_requests
             WHERE user_id = :id
             ORDER BY created_at DESC
             LIMIT 50"
        );
        $leaveRows->execute(['id' => $userId]);

        $promotionRows = $db->prepare(
            "SELECT id, requested_rank, current_rank, status, supervisor_comment, hr_comment, created_at, supporting_document
             FROM staff_promotions
             WHERE user_id = :id
             ORDER BY created_at DESC
             LIMIT 50"
        );
        $promotionRows->execute(['id' => $userId]);

        $appraisalRows = $db->prepare(
            "SELECT id, period_label, status, self_score, supervisor_comment, created_at
             FROM staff_appraisals
             WHERE user_id = :id
             ORDER BY created_at DESC
             LIMIT 50"
        );
        $appraisalRows->execute(['id' => $userId]);

        $appraisalMetrics = $db->query(
            "SELECT id, metric_name, metric_prompt, is_active
             FROM appraisal_metrics
             WHERE is_active = 1
             ORDER BY id ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'HRGoTo HCM - Staff Services';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/staff/services.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function approvals(Request $request): void {
        AuthMiddleware::checkRole(['Supervisor']);
        $this->ensureTables();

        $db = Database::getConnection();
        $supervisorId = (int)($_SESSION['user_id'] ?? 0);

        $leaveRows = $db->prepare(
            "SELECT l.id, u.fullname, s.staff_id_card, l.leave_type, l.start_date, l.end_date, l.status, l.reason, l.created_at
             FROM leave_requests l
             JOIN users u ON u.id = l.user_id
             LEFT JOIN staff_records s ON s.user_id = u.id
             LEFT JOIN staff_records owner ON owner.user_id = u.id
             WHERE owner.supervisor_user_id = :sid
               AND l.status IN ('Pending Supervisor Sign-off', 'Pending Comments')
             ORDER BY l.created_at DESC"
        );
        $leaveRows->execute(['sid' => $supervisorId]);

        $promotionRows = $db->prepare(
            "SELECT p.id, u.fullname, s.staff_id_card, p.current_rank, p.requested_rank, p.status, p.created_at
             FROM staff_promotions p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN staff_records s ON s.user_id = u.id
             LEFT JOIN staff_records owner ON owner.user_id = u.id
             WHERE owner.supervisor_user_id = :sid
               AND p.status IN ('Pending Comments', 'Pending Supervisor Sign-off')
             ORDER BY p.created_at DESC"
        );
        $promotionRows->execute(['sid' => $supervisorId]);

        $appraisalRows = $db->prepare(
            "SELECT a.id, u.fullname, s.staff_id_card, a.period_label, a.status, a.created_at
             FROM staff_appraisals a
             JOIN users u ON u.id = a.user_id
             LEFT JOIN staff_records s ON s.user_id = u.id
             LEFT JOIN staff_records owner ON owner.user_id = u.id
             WHERE owner.supervisor_user_id = :sid
               AND a.status IN ('Pending Comments', 'Pending Supervisor Sign-off')
             ORDER BY a.created_at DESC"
        );
        $appraisalRows->execute(['sid' => $supervisorId]);

        $pageTitle = 'HRGoTo HCM - Supervisor Approvals';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/staff/approvals.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function updateInfoView(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        $this->ensureTables();

        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $stmt = $db->prepare(
            "SELECT u.id, u.fullname, u.email, s.phone_one, s.phone_two, s.marital_status,
                    s.avatar_url, s.residential_address, s.staff_id_card,
                    s.ghana_card_number, s.ghana_card_photo
             FROM users u
             LEFT JOIN staff_records s ON s.user_id = u.id
             WHERE u.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $pageTitle = 'HRGoTo HCM - Update Info';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/staff/update_info.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function updateOwnProfile(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        CSRFMiddleware::validate($request);
        $this->ensureTables();

        $body = $request->getBody();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $db = Database::getConnection();

            $avatarDir = __DIR__ . '/../../public/uploads/avatars';
            if (!is_dir($avatarDir)) { mkdir($avatarDir, 0755, true); }
            $ghanaDir = __DIR__ . '/../../public/uploads/ghana_cards';
            if (!is_dir($ghanaDir)) { mkdir($ghanaDir, 0755, true); }

            $avatarUrl = null;
            $avatarUploadError = null;
            if (!empty($_FILES['avatar_photo']) && is_array($_FILES['avatar_photo'])) {
                $fileErr = (int)($_FILES['avatar_photo']['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($fileErr === UPLOAD_ERR_OK) {
                    $tmp = (string)$_FILES['avatar_photo']['tmp_name'];
                    $mime = mime_content_type($tmp);
                    if (in_array($mime, ['image/jpeg', 'image/png'], true)) {
                        $ext = $mime === 'image/png' ? 'png' : 'jpg';
                        $name = bin2hex(random_bytes(12)) . '.' . $ext;
                        $dest = $avatarDir . '/' . $name;
                        if (move_uploaded_file($tmp, $dest)) {
                            $avatarUrl = '/uploads/avatars/' . $name;
                        } else {
                            $avatarUploadError = 'Failed to save uploaded photo (server write error).';
                        }
                    } else {
                        $avatarUploadError = 'Photo must be JPEG or PNG. Detected: ' . $mime;
                    }
                } elseif ($fileErr !== UPLOAD_ERR_NO_FILE) {
                    $avatarUploadError = 'Photo upload error code: ' . $fileErr;
                }
            }

            $ghanaCardPhotoUrl = null;
            if (!empty($_FILES['ghana_card_photo']) && (int)$_FILES['ghana_card_photo']['error'] === UPLOAD_ERR_OK) {
                $tmp = (string)$_FILES['ghana_card_photo']['tmp_name'];
                $mime = mime_content_type($tmp);
                if (in_array($mime, ['image/jpeg', 'image/png'], true)) {
                    $ext = $mime === 'image/png' ? 'png' : 'jpg';
                    $name = bin2hex(random_bytes(12)) . '.' . $ext;
                    $dest = $ghanaDir . '/' . $name;
                    if (move_uploaded_file($tmp, $dest)) {
                        $ghanaCardPhotoUrl = '/uploads/ghana_cards/' . $name;
                    }
                }
            }

            $sql = "UPDATE staff_records SET phone_one = :phone_one, phone_two = :phone_two,
                    marital_status = :marital_status, residential_address = :residential_address,
                    ghana_card_number = :ghana_card_number";
            $params = [
                'phone_one' => trim((string)($body['phone_one'] ?? '')),
                'phone_two' => trim((string)($body['phone_two'] ?? '')),
                'marital_status' => trim((string)($body['marital_status'] ?? 'single')),
                'residential_address' => trim((string)($body['residential_address'] ?? '')),
                'ghana_card_number' => trim((string)($body['ghana_card_number'] ?? '')),
                'user_id' => $userId,
            ];
            if ($avatarUrl !== null) {
                $sql .= ", avatar_url = :avatar_url";
                $params['avatar_url'] = $avatarUrl;
            }
            if ($ghanaCardPhotoUrl !== null) {
                $sql .= ", ghana_card_photo = :ghana_card_photo";
                $params['ghana_card_photo'] = $ghanaCardPhotoUrl;
            }
            $sql .= " WHERE user_id = :user_id";

            $stmtStaff = $db->prepare($sql);
            $stmtStaff->execute($params);

            Security::setFlash('ok', 'Profile updated');
            if ($avatarUploadError !== null) {
                Security::setFlash('warning', $avatarUploadError);
            }
            header('Location: /staff/update-info');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /staff/update-info');
        }
        exit;
    }

    public function dossierView(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        $this->ensureTables();
        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $stmt = $db->prepare(
            "SELECT u.id, u.fullname, u.email, u.username, u.created_at AS account_created,
                    s.phone_one, s.phone_two, s.marital_status, s.avatar_url,
                    s.residential_address, s.staff_id_card, s.gender, s.date_of_birth,
                    s.date_joined, s.employment_status, s.hometown, s.region,
                    s.nationality, s.religion, s.number_of_children, s.biography,
                    s.ghana_card_number, s.ghana_card_photo,
                    d.dept_name, dg.title AS designation,
                    COALESCE(r.leave_days, 0) AS leave_entitlement
             FROM users u
             LEFT JOIN staff_records s ON s.user_id = u.id
             LEFT JOIN departments d ON d.dept_id = s.dept_id
             LEFT JOIN designations dg ON dg.id = s.designation_id
             LEFT JOIN ranks r ON r.id = s.rank_id
             WHERE u.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $eduStmt = $db->prepare("SELECT * FROM staff_education WHERE user_id = :uid ORDER BY year_from DESC");
        $eduStmt->execute(['uid' => $userId]);
        $education = $eduStmt->fetchAll(PDO::FETCH_ASSOC);

        $expStmt = $db->prepare("SELECT * FROM staff_experience WHERE user_id = :uid ORDER BY year_from DESC");
        $expStmt->execute(['uid' => $userId]);
        $experience = $expStmt->fetchAll(PDO::FETCH_ASSOC);

        $files = [];
        foreach ($education as $edu) {
            if (!empty($edu['dossier_url'])) {
                $files[] = [
                    'name' => $edu['certificate'] . ' Certificate',
                    'url' => $edu['dossier_url'],
                    'category' => 'Education',
                ];
            }
        }
        if (!empty($profile['ghana_card_photo'])) {
            $files[] = ['name' => 'Ghana Card Photo', 'url' => $profile['ghana_card_photo'], 'category' => 'Identification'];
        }
        if (!empty($profile['avatar_url'])) {
            $files[] = ['name' => 'Profile Picture', 'url' => $profile['avatar_url'], 'category' => 'Photo'];
        }

        $pageTitle = 'HRGoTo HCM - My Dossier';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/staff/dossier_view.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function submitLeave(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        CSRFMiddleware::validate($request);
        $this->ensureTables();

        $body = $request->getBody();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $start = trim((string)($body['start_date'] ?? ''));
        $end = trim((string)($body['end_date'] ?? ''));
        $type = trim((string)($body['leave_type'] ?? 'Annual'));
        $reason = trim((string)($body['reason'] ?? ''));

        $days = $this->countWeekdays($start, $end);
        if ($days <= 0 || $reason === '') {
            Security::setFlash('error', 'Invalid leave request');
            header('Location: /staff/my-leave');
            exit;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare(
                "INSERT INTO leave_requests
                (user_id, leave_type, start_date, end_date, total_days, reason, status)
                VALUES (:user_id, :leave_type, :start_date, :end_date, :total_days, :reason, 'Pending Supervisor Sign-off')"
            );
            $stmt->execute([
                'user_id' => $userId,
                'leave_type' => $type,
                'start_date' => $start,
                'end_date' => $end,
                'total_days' => $days,
                'reason' => $reason,
            ]);
            $userName = trim((string)($_SESSION['user_fullname'] ?? 'Staff'));
            foreach ($db->query("SELECT id FROM users WHERE role IN ('Super Admin', 'HR Manager', 'Supervisor') AND deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN) as $sId) {
                if ((int)$sId !== $userId) {
                    \App\Helpers\Notification::create((int)$sId, 'New Leave Request', "{$userName} has submitted a leave request.", 'info');
                }
            }
            Security::setFlash('ok', 'Leave request submitted');
            header('Location: /staff/my-leave');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /staff/my-leave');
        }
        exit;
    }

    public function submitPromotion(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        CSRFMiddleware::validate($request);
        $this->ensureTables();

        $body = $request->getBody();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $requested = trim((string)($body['requested_rank'] ?? ''));
        $remarks = trim((string)($body['remarks'] ?? ''));

        if ($requested === '') {
            Security::setFlash('error', 'Requested rank is required');
            header('Location: /staff/promotion');
            exit;
        }

        try {
            $db = Database::getConnection();
            $currentRankStmt = $db->prepare(
                "SELECT dg.title FROM staff_records s
                 LEFT JOIN designations dg ON dg.id = s.designation_id
                 WHERE s.user_id = :id LIMIT 1"
            );
            $currentRankStmt->execute(['id' => $userId]);
            $currentRank = (string)($currentRankStmt->fetchColumn() ?: 'Unassigned');

            $docUrl = null;
            if (!empty($_FILES['supporting_document']) && (int)$_FILES['supporting_document']['error'] === UPLOAD_ERR_OK) {
                $orig = (string)$_FILES['supporting_document']['name'];
                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                    $name = bin2hex(random_bytes(12)) . '.' . $ext;
                    $dest = __DIR__ . '/../../public/uploads/documents/' . $name;
                    if (move_uploaded_file((string)$_FILES['supporting_document']['tmp_name'], $dest)) {
                        $docUrl = '/uploads/documents/' . $name;
                    }
                }
            }

            $stmt = $db->prepare(
                "INSERT INTO staff_promotions
                (user_id, current_rank, requested_rank, remarks, status, supporting_document, created_by)
                VALUES (:user_id, :current_rank, :requested_rank, :remarks, 'Pending Comments', :supporting_document, :created_by)"
            );
            $stmt->execute([
                'user_id' => $userId,
                'current_rank' => $currentRank,
                'requested_rank' => $requested,
                'remarks' => $remarks,
                'supporting_document' => $docUrl,
                'created_by' => $userId,
            ]);
            $userName = trim((string)($_SESSION['user_fullname'] ?? 'Staff'));
            foreach ($db->query("SELECT id FROM users WHERE role IN ('Super Admin', 'HR Manager', 'Supervisor') AND deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN) as $sId) {
                if ((int)$sId !== $userId) {
                    \App\Helpers\Notification::create((int)$sId, 'New Promotion Request', "{$userName} has submitted a promotion request.", 'info');
                }
            }
            Security::setFlash('ok', 'Promotion request submitted');
            header('Location: /staff/promotion');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /staff/promotion');
        }
        exit;
    }

    public function submitAppraisal(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        CSRFMiddleware::validate($request);
        $this->ensureTables();

        $body = $request->getBody();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $period = trim((string)($body['period_label'] ?? ''));
        $responses = $body['responses'] ?? [];

        if ($period === '' || !is_array($responses) || $responses === []) {
            Security::setFlash('error', 'Invalid appraisal payload');
            header('Location: /staff/appraisal');
            exit;
        }

        $score = 0.0;
        foreach ($responses as $v) {
            $score += (float)$v;
        }
        $score = $score / max(1, count($responses));

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare(
                "INSERT INTO staff_appraisals
                (user_id, period_label, score, rating, summary, status, appraiser_id, self_score)
                VALUES (:user_id, :period_label, :score, :rating, :summary, 'Pending Comments', :appraiser_id, :self_score)"
            );
            $stmt->execute([
                'user_id' => $userId,
                'period_label' => $period,
                'score' => $score,
                'rating' => $score >= 70 ? 'Good' : 'Needs Improvement',
                'summary' => json_encode($responses),
                'appraiser_id' => $userId,
                'self_score' => $score,
            ]);
            $userName = trim((string)($_SESSION['user_fullname'] ?? 'Staff'));
            foreach ($db->query("SELECT id FROM users WHERE role IN ('Super Admin', 'HR Manager', 'Supervisor') AND deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN) as $sId) {
                if ((int)$sId !== $userId) {
                    \App\Helpers\Notification::create((int)$sId, 'New Appraisal Submission', "{$userName} has submitted an appraisal.", 'info');
                }
            }
            Security::setFlash('ok', 'Appraisal submitted');
            header('Location: /staff/appraisal');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /staff/appraisal');
        }
        exit;
    }

    public function leaveView(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        $this->ensureTables();
        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $leaveRows = $db->prepare(
            "SELECT id, leave_type, start_date, end_date, total_days, status, reason, created_at
             FROM leave_requests WHERE user_id = :id ORDER BY created_at DESC LIMIT 50"
        );
        $leaveRows->execute(['id' => $userId]);

        $checkRankCol = $db->query("SHOW COLUMNS FROM staff_records LIKE 'rank_id'");
        if ($checkRankCol->rowCount() === 0) {
            $db->exec("ALTER TABLE staff_records ADD COLUMN rank_id INT UNSIGNED NULL AFTER designation_id");
        }

        $entitlement = $db->prepare(
            "SELECT COALESCE(r.leave_days, 0) AS total_entitled
             FROM users u
             LEFT JOIN staff_records s ON s.user_id = u.id
             LEFT JOIN ranks r ON r.id = s.rank_id
             WHERE u.id = :id LIMIT 1"
        );
        $entitlement->execute(['id' => $userId]);
        $totalEntitled = (int)($entitlement->fetchColumn() ?: 0);

        $usedDays = $db->prepare(
            "SELECT COALESCE(SUM(total_days), 0) FROM leave_requests
             WHERE user_id = :id AND status = 'Approved'
             AND YEAR(created_at) = YEAR(CURDATE())"
        );
        $usedDays->execute(['id' => $userId]);
        $usedDaysCount = (int)($usedDays->fetchColumn() ?: 0);

        $leaveBalance = max(0, $totalEntitled - $usedDaysCount);

        $pageTitle = 'HRGoTo HCM - Apply for Leave';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/staff/leave.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function promotionView(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        $this->ensureTables();
        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $promotionRows = $db->prepare(
            "SELECT id, requested_rank, current_rank, status, remarks, supervisor_comment, hr_comment, created_at, supporting_document
             FROM staff_promotions WHERE user_id = :id ORDER BY created_at DESC LIMIT 50"
        );
        $promotionRows->execute(['id' => $userId]);

        $currentRankStmt = $db->prepare("SELECT COALESCE(dg.title, 'Unassigned') FROM staff_records s LEFT JOIN designations dg ON dg.id = s.designation_id WHERE s.user_id = :id LIMIT 1");
        $currentRankStmt->execute(['id' => $userId]);
        $currentRank = (string)($currentRankStmt->fetchColumn() ?: 'Unassigned');

        $ranks = $db->query("SELECT id, rank_name FROM ranks WHERE is_active = 1 ORDER BY rank_order ASC")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'HRGoTo HCM - Apply for Promotion';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/staff/promotion.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function appraisalView(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        $this->ensureTables();
        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $appraisalRows = $db->prepare(
            "SELECT id, period_label, status, self_score, supervisor_comment, staff_approval_note, created_at
             FROM staff_appraisals WHERE user_id = :id ORDER BY created_at DESC LIMIT 50"
        );
        $appraisalRows->execute(['id' => $userId]);

        $pendingStaffApproval = $db->prepare(
            "SELECT a.id, a.period_label, a.self_score, a.supervisor_comment, a.created_at, u.fullname AS reviewer_name
             FROM staff_appraisals a
             LEFT JOIN users u ON u.id = a.appraiser_id
             WHERE a.user_id = :id AND a.status = 'Commented'
             ORDER BY a.created_at DESC LIMIT 10"
        );
        $pendingStaffApproval->execute(['id' => $userId]);

        $appraisalMetrics = $db->query(
            "SELECT id, metric_name, metric_prompt, is_active FROM appraisal_metrics WHERE is_active = 1 ORDER BY id ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'HRGoTo HCM - Appraisal';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/staff/appraisal.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function appraisalPdf(Request $request): void
    {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        $appraisalId = (int)$request->getParam('id', 0);
        if ($appraisalId <= 0) {
            http_response_code(400);
            echo "Invalid appraisal ID.";
            exit;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT user_id FROM staff_appraisals WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $appraisalId]);
        $appraisal = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$appraisal) {
            http_response_code(404);
            echo "Appraisal not found.";
            exit;
        }
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $role = (string)($_SESSION['user_role'] ?? 'Staff');
        if (!in_array($role, ['Super Admin', 'HR Manager'], true) && (int)$appraisal['user_id'] !== $userId) {
            http_response_code(403);
            echo "You are not authorized to download this appraisal.";
            exit;
        }
        \App\Helpers\PdfHelper::generateAppraisalPdf($appraisalId);
    }

    public function reviewLeaveAsSupervisor(Request $request): void {
        AuthMiddleware::checkRole(['Supervisor']);
        CSRFMiddleware::validate($request);
        $this->ensureTables();

        $body = $request->getBody();
        $status = trim((string)($body['status'] ?? ''));
        $leaveId = (int)($body['leave_id'] ?? 0);
        $comment = trim((string)($body['comment'] ?? ''));

        if (!in_array($status, ['Pending HR Approval', 'Declined'], true) || $leaveId <= 0) {
            Security::setFlash('error', 'Invalid leave review state');
            header('Location: /staff/approvals');
            exit;
        }

        try {
            $db = Database::getConnection();
            $check = $db->prepare("SELECT COUNT(*) FROM leave_requests l
                JOIN staff_records s ON s.user_id = l.user_id
                WHERE l.id = :leave_id AND s.supervisor_user_id = :supervisor_id");
            $check->execute(['leave_id' => $leaveId, 'supervisor_id' => (int)($_SESSION['user_id'] ?? 0)]);
            if ((int)$check->fetchColumn() === 0) {
                Security::setFlash('error', 'You can only review leave requests from your supervisees.');
                header('Location: /staff/approvals');
                exit;
            }
            $stmt = $db->prepare(
                "UPDATE leave_requests
                 SET status = :status, supervisor_comment = :comment, supervisor_id = :reviewer
                 WHERE id = :id"
            );
            $stmt->execute([
                'status' => $status,
                'comment' => $comment,
                'reviewer' => (int)($_SESSION['user_id'] ?? 0),
                'id' => $leaveId,
            ]);
            $stmtU = $db->prepare("SELECT user_id FROM leave_requests WHERE id = :id LIMIT 1");
            $stmtU->execute(['id' => $leaveId]);
            $staffUserId = (int)$stmtU->fetchColumn();
            if ($staffUserId > 0) {
                \App\Helpers\Notification::create($staffUserId, 'Leave Request Reviewed', "Your leave request has been {$status}.", 'info');
            }
            Security::setFlash('ok', 'Leave review saved');
            header('Location: /staff/approvals');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /staff/approvals');
        }
        exit;
    }

    public function reviewPromotionAsSupervisor(Request $request): void {
        AuthMiddleware::checkRole(['Supervisor']);
        CSRFMiddleware::validate($request);
        $this->ensureTables();

        $body = $request->getBody();
        $status = trim((string)($body['status'] ?? ''));
        $id = (int)($body['promotion_id'] ?? 0);
        $comment = trim((string)($body['comment'] ?? ''));

        if (!in_array($status, ['Pending HR Approval', 'Declined'], true) || $id <= 0) {
            Security::setFlash('error', 'Invalid promotion review state');
            header('Location: /staff/approvals');
            exit;
        }

        try {
            $db = Database::getConnection();
            $check = $db->prepare("SELECT COUNT(*) FROM staff_promotions p
                JOIN staff_records s ON s.user_id = p.user_id
                WHERE p.id = :pid AND s.supervisor_user_id = :supervisor_id");
            $check->execute(['pid' => $id, 'supervisor_id' => (int)($_SESSION['user_id'] ?? 0)]);
            if ((int)$check->fetchColumn() === 0) {
                Security::setFlash('error', 'You can only review promotions from your supervisees.');
                header('Location: /staff/approvals');
                exit;
            }
            $stmt = $db->prepare(
                "UPDATE staff_promotions
                 SET status = :status, supervisor_comment = :comment, supervisor_id = :reviewer
                 WHERE id = :id"
            );
            $stmt->execute([
                'status' => $status,
                'comment' => $comment,
                'reviewer' => (int)($_SESSION['user_id'] ?? 0),
                'id' => $id,
            ]);
            $stmtU = $db->prepare("SELECT user_id FROM staff_promotions WHERE id = :id LIMIT 1");
            $stmtU->execute(['id' => $id]);
            $staffUserId = (int)$stmtU->fetchColumn();
            if ($staffUserId > 0) {
                \App\Helpers\Notification::create($staffUserId, 'Promotion Request Reviewed', "Your promotion request has been {$status}.", 'info');
            }
            Security::setFlash('ok', 'Promotion review saved');
            header('Location: /staff/approvals');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /staff/approvals');
        }
        exit;
    }

    public function reviewAppraisalAsSupervisor(Request $request): void {
        AuthMiddleware::checkRole(['Supervisor']);
        CSRFMiddleware::validate($request);
        $this->ensureTables();

        $body = $request->getBody();
        $status = trim((string)($body['status'] ?? ''));
        $id = (int)($body['appraisal_id'] ?? 0);
        $comment = trim((string)($body['comment'] ?? ''));

        if (!in_array($status, ['Commented', 'Declined'], true) || $id <= 0) {
            Security::setFlash('error', 'Invalid appraisal review state');
            header('Location: /staff/approvals');
            exit;
        }

        try {
            $db = Database::getConnection();
            $check = $db->prepare("SELECT COUNT(*) FROM staff_appraisals a
                JOIN staff_records s ON s.user_id = a.user_id
                WHERE a.id = :aid AND s.supervisor_user_id = :supervisor_id");
            $check->execute(['aid' => $id, 'supervisor_id' => (int)($_SESSION['user_id'] ?? 0)]);
            if ((int)$check->fetchColumn() === 0) {
                Security::setFlash('error', 'You can only review appraisals from your supervisees.');
                header('Location: /staff/approvals');
                exit;
            }
            $stmt = $db->prepare(
                "UPDATE staff_appraisals
                 SET status = :status, supervisor_comment = :comment, appraiser_id = :reviewer
                 WHERE id = :id"
            );
            $stmt->execute([
                'status' => $status,
                'comment' => $comment,
                'reviewer' => (int)($_SESSION['user_id'] ?? 0),
                'id' => $id,
            ]);
            $stmtU = $db->prepare("SELECT user_id FROM staff_appraisals WHERE id = :id LIMIT 1");
            $stmtU->execute(['id' => $id]);
            $staffUserId = (int)$stmtU->fetchColumn();
            if ($staffUserId > 0) {
                \App\Helpers\Notification::create($staffUserId, 'Appraisal Reviewed', "Your appraisal has been {$status}.", 'info');
            }
            Security::setFlash('ok', 'Appraisal review saved');
            header('Location: /staff/approvals');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /staff/approvals');
        }
        exit;
    }

    public function reviewAppraisalAsStaff(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        CSRFMiddleware::validate($request);
        $this->ensureTables();
        $body = $request->getBody();
        $id = (int)($body['appraisal_id'] ?? 0);
        $decision = trim((string)($body['decision'] ?? ''));
        $reason = trim((string)($body['reason'] ?? ''));
        if (!in_array($decision, ['Staff Approved', 'Staff Disapproved'], true) || $id <= 0) {
            Security::setFlash('error', 'Invalid response');
            header('Location: /staff/appraisal');
            exit;
        }
        try {
            $db = Database::getConnection();
            $check = $db->prepare("SELECT user_id, status FROM staff_appraisals WHERE id = :id");
            $check->execute(['id' => $id]);
            $row = $check->fetch(PDO::FETCH_ASSOC);
            if (!$row || (int)$row['user_id'] !== (int)($_SESSION['user_id'] ?? 0)) {
                Security::setFlash('error', 'You can only respond to your own appraisals.');
                header('Location: /staff/appraisal');
                exit;
            }
            if ($row['status'] !== 'Commented') {
                Security::setFlash('error', 'This appraisal is not awaiting your response.');
                header('Location: /staff/appraisal');
                exit;
            }
            $stmt = $db->prepare("UPDATE staff_appraisals SET status = :decision, staff_approval_note = :reason WHERE id = :id");
            $stmt->execute(['decision' => $decision, 'reason' => $reason, 'id' => $id]);
            $subject = $decision === 'Staff Approved' ? 'Appraisal Approved by Staff' : 'Appraisal Disapproved by Staff';
            $msg = "Appraisal #{$id} has been {$decision} by the staff member." . ($reason ? " Reason: {$reason}" : '');
            foreach ($db->query("SELECT id FROM users WHERE role IN ('Super Admin', 'HR Manager') AND deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN) as $sId) {
                \App\Helpers\Notification::create((int)$sId, $subject, $msg, 'info');
            }
            Security::setFlash('ok', "Appraisal {$decision}");
            header('Location: /staff/appraisal');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /staff/appraisal');
        }
        exit;
    }

    public function bankDetailsView(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        $db = Database::getConnection();
        $db->exec("CREATE TABLE IF NOT EXISTS staff_bank_details (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            bank_name VARCHAR(120) NULL,
            branch VARCHAR(120) NULL,
            account_number VARCHAR(60) NULL,
            account_name VARCHAR(120) NULL,
            account_type VARCHAR(30) DEFAULT 'savings',
            mobile_money_provider VARCHAR(60) NULL,
            mobile_money_number VARCHAR(30) NULL,
            payment_method VARCHAR(40) DEFAULT 'bank_transfer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM staff_bank_details WHERE user_id = :uid LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        $bankDetail = $stmt->fetch(PDO::FETCH_ASSOC);
        $pageTitle = 'My Bank Details';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/staff/bank_details.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function saveOwnBankDetails(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        CSRFMiddleware::validate($request);
        $db = Database::getConnection();
        $db->exec("CREATE TABLE IF NOT EXISTS staff_bank_details (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            bank_name VARCHAR(120) NULL,
            branch VARCHAR(120) NULL,
            account_number VARCHAR(60) NULL,
            account_name VARCHAR(120) NULL,
            account_type VARCHAR(30) DEFAULT 'savings',
            mobile_money_provider VARCHAR(60) NULL,
            mobile_money_number VARCHAR(30) NULL,
            payment_method VARCHAR(40) DEFAULT 'bank_transfer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $body = $request->getBody();
        try {
            $stmt = $db->prepare("INSERT INTO staff_bank_details (user_id, bank_name, branch, account_number, account_name, account_type, mobile_money_provider, mobile_money_number, payment_method) VALUES (:uid, :bn, :br, :an, :acn, :at, :mp, :mn, :pm) ON DUPLICATE KEY UPDATE bank_name=:bn2, branch=:br2, account_number=:an2, account_name=:acn2, account_type=:at2, mobile_money_provider=:mp2, mobile_money_number=:mn2, payment_method=:pm2");
            $stmt->execute([
                'uid' => $userId, 'bn' => $body['bank_name'] ?? '', 'br' => $body['branch'] ?? '', 'an' => $body['account_number'] ?? '', 'acn' => $body['account_name'] ?? '', 'at' => $body['account_type'] ?? 'savings', 'mp' => $body['mobile_money_provider'] ?? '', 'mn' => $body['mobile_money_number'] ?? '', 'pm' => $body['payment_method'] ?? 'bank_transfer',
                'bn2' => $body['bank_name'] ?? '', 'br2' => $body['branch'] ?? '', 'an2' => $body['account_number'] ?? '', 'acn2' => $body['account_name'] ?? '', 'at2' => $body['account_type'] ?? 'savings', 'mp2' => $body['mobile_money_provider'] ?? '', 'mn2' => $body['mobile_money_number'] ?? '', 'pm2' => $body['payment_method'] ?? 'bank_transfer',
            ]);
            Security::setFlash('ok', 'Bank details saved');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
        }
        header('Location: /staff/bank-details');
        exit;
    }

    private function countWeekdays(string $start, string $end): int {
        $days = 0;
        $current = strtotime($start);
        $endTime = strtotime($end);
        if ($current === false || $endTime === false || $current > $endTime) {
            return 0;
        }
        while ($current <= $endTime) {
            if ((int)date('N', $current) <= 5) {
                $days++;
            }
            $current = strtotime('+1 day', $current);
        }
        return $days;
    }

    private function ensureTables(): void {
        $db = Database::getConnection();

        $db->exec("CREATE TABLE IF NOT EXISTS appraisal_metrics (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            metric_name VARCHAR(120) NOT NULL,
            metric_prompt TEXT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->addColumnIfMissing($db, 'staff_records', 'supervisor_user_id', 'INT NULL');
        $this->addIndexIfMissing($db, 'staff_records', 'idx_staff_supervisor', 'supervisor_user_id');
        $this->addColumnIfMissing($db, 'staff_records', 'rank_id', 'INT UNSIGNED NULL');
        $this->addColumnIfMissing($db, 'staff_records', 'ghana_card_number', 'VARCHAR(20) NULL');
        $this->addColumnIfMissing($db, 'staff_records', 'ghana_card_photo', 'VARCHAR(255) NULL');

        $this->addColumnIfMissing($db, 'leave_requests', 'supervisor_comment', 'TEXT NULL');
        $this->addColumnIfMissing($db, 'leave_requests', 'supervisor_id', 'INT NULL');
        $this->addColumnIfMissing($db, 'leave_requests', 'hr_comment', 'TEXT NULL');
        $this->addColumnIfMissing($db, 'leave_requests', 'hr_id', 'INT NULL');

        $this->addColumnIfMissing($db, 'staff_promotions', 'current_rank', 'VARCHAR(150) NULL');
        $this->addColumnIfMissing($db, 'staff_promotions', 'requested_rank', 'VARCHAR(150) NULL');
        $this->addColumnIfMissing($db, 'staff_promotions', 'supervisor_comment', 'TEXT NULL');
        $this->addColumnIfMissing($db, 'staff_promotions', 'supervisor_id', 'INT NULL');
        $this->addColumnIfMissing($db, 'staff_promotions', 'hr_comment', 'TEXT NULL');
        $this->addColumnIfMissing($db, 'staff_promotions', 'hr_id', 'INT NULL');
        $this->addColumnIfMissing($db, 'staff_promotions', 'supporting_document', 'VARCHAR(255) NULL');

        $this->addColumnIfMissing($db, 'staff_appraisals', 'self_score', 'DECIMAL(5,2) NULL');
        $this->addColumnIfMissing($db, 'staff_appraisals', 'supervisor_comment', 'TEXT NULL');
        $this->addColumnIfMissing($db, 'staff_appraisals', 'hr_comment', 'TEXT NULL');
        $this->addColumnIfMissing($db, 'staff_appraisals', 'staff_approval_note', 'TEXT NULL');
        $this->addColumnIfMissing($db, 'staff_appraisals', 'finalized_by', 'INT NULL');
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

    private function addIndexIfMissing(PDO $db, string $table, string $indexName, string $columnList): void {
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND INDEX_NAME = :index_name");
        $stmt->execute([
            'table_name' => $table,
            'index_name' => $indexName,
        ]);
        if ((int)$stmt->fetchColumn() === 0) {
            $db->exec("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` ({$columnList})");
        }
    }
}
