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

class EmailController
{
    public function __construct()
    {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
    }

    private function ensureTables(): void
    {
        $db = Database::getConnection();

        $db->exec("CREATE TABLE IF NOT EXISTS email_templates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            template_name VARCHAR(120) NOT NULL,
            template_subject VARCHAR(255) NOT NULL,
            template_body TEXT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $columnMap = [
            ['old' => 'subject', 'new' => 'template_subject', 'def' => 'template_subject VARCHAR(255) NOT NULL'],
            ['old' => 'body', 'new' => 'template_body', 'def' => 'template_body TEXT NOT NULL'],
        ];
        $hasCol = function (string $col) use ($db): bool {
            $st = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'email_templates' AND COLUMN_NAME = :c");
            $st->execute(['c' => $col]);
            return (int)$st->fetchColumn() > 0;
        };
        foreach ($columnMap as $m) {
            $hasOld = $hasCol($m['old']);
            $hasNew = $hasCol($m['new']);
            if ($hasOld && !$hasNew) {
                $db->exec("ALTER TABLE email_templates CHANGE COLUMN `{$m['old']}` {$m['def']}");
            } elseif (!$hasOld && !$hasNew) {
                $db->exec("ALTER TABLE email_templates ADD COLUMN {$m['def']}");
            }
        }

        $cols = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_from_email', 'smtp_from_name'];
        foreach ($cols as $col) {
            $check = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'app_config' AND COLUMN_NAME = :col");
            $check->execute(['col' => $col]);
            if ((int)$check->fetchColumn() === 0) {
                $type = ($col === 'smtp_port') ? 'INT NULL' : 'VARCHAR(255) NULL';
                $db->exec("ALTER TABLE app_config ADD COLUMN `{$col}` {$type} NULL");
            }
        }
    }

    public function sendView(Request $request): void
    {
        $this->ensureTables();
        $db = Database::getConnection();

        $config = $db->query("SELECT smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, smtp_from_email, smtp_from_name FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

        $templates = $db->query("SELECT id, template_name, template_subject, template_body FROM email_templates WHERE is_active = 1 ORDER BY template_name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $departments = $db->query("SELECT dept_id, dept_name FROM departments ORDER BY dept_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $faculties   = $db->query("SELECT sch_id, sch_name FROM schools ORDER BY sch_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $directorates = $db->query("SELECT dir_id, dir_name FROM directorates ORDER BY dir_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'HRGoTo HCM - Send Email';
        $appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');

        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/email/send.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function templates(Request $request): void
    {
        $this->ensureTables();
        $db = Database::getConnection();

        $emailTemplates = $db->query("SELECT id, template_name, template_subject, template_body, is_active, created_at FROM email_templates ORDER BY template_name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $pageTitle = 'HRGoTo HCM - Email Templates';
        $appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
        $csrf = \App\Helpers\Security::generateCsrfToken();

        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/email/templates.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function saveTemplate(Request $request): void
    {
        CSRFMiddleware::validate($request);
        $this->ensureTables();
        $body = $request->getBody();

        $name = trim((string)($body['template_name'] ?? ''));
        $subject = trim((string)($body['template_subject'] ?? ''));
        $templateBody = trim((string)($body['template_body'] ?? ''));

        if ($name === '' || $subject === '' || $templateBody === '') {
            Security::setFlash('error', 'All template fields are required');
            header('Location: /email/templates');
            exit;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO email_templates (template_name, template_subject, template_body) VALUES (:name, :subject, :body)");
            $stmt->execute([
                'name' => $name,
                'subject' => $subject,
                'body' => $templateBody,
            ]);
            Security::setFlash('ok', 'Email template saved');
            header('Location: /email/templates');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /email/templates');
        }
        exit;
    }

    public function deleteTemplate(Request $request): void
    {
        CSRFMiddleware::validate($request);
        $body = $request->getBody();
        $templateId = (int)($body['template_id'] ?? 0);

        if ($templateId <= 0) {
            Security::setFlash('error', 'Invalid template');
            header('Location: /email/templates');
            exit;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM email_templates WHERE id = :id");
            $stmt->execute(['id' => $templateId]);
            Security::setFlash('ok', 'Email template deleted');
            header('Location: /email/templates');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /email/templates');
        }
        exit;
    }

    public function processSend(Request $request): void
    {
        CSRFMiddleware::validate($request);
        $this->ensureTables();

        $body = $request->getBody();
        $senderEmail = trim((string)($body['sender_email'] ?? ''));
        $senderName = trim((string)($body['sender_name'] ?? ''));
        $subject = trim((string)($body['subject'] ?? ''));
        $emailBody = trim((string)($body['email_body'] ?? ''));
        $flow = trim((string)($body['target_flow'] ?? 'recruitment'));

        if ($senderEmail === '' || $subject === '' || $emailBody === '') {
            Security::setFlash('error', 'Sender email, subject, and body are required');
            header('Location: /email/send');
            exit;
        }

        $recipients = [];

        try {
            $db = Database::getConnection();

            switch ($flow) {
                case 'recruitment':
                    $status = trim((string)($body['recruitment_status'] ?? ''));
                    if (!empty($status)) {
                        $stmt = $db->prepare("SELECT email FROM applications WHERE status = ? AND email IS NOT NULL AND email != ''");
                        $stmt->execute([$status]);
                        $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    }
                    break;

                case 'staff':
                    $scope = trim((string)($body['staff_scope'] ?? ''));
                    if ($scope === 'all') {
                        $recipients = $db->query("SELECT email FROM users WHERE deleted_at IS NULL AND email IS NOT NULL AND email != ''")->fetchAll(PDO::FETCH_COLUMN);
                    } elseif ($scope === 'department' && !empty($_POST['target_dept_id'])) {
                        $stmt = $db->prepare("SELECT u.email FROM users u JOIN staff_records s ON s.user_id = u.id WHERE s.dept_id = ? AND u.email IS NOT NULL AND u.email != ''");
                        $stmt->execute([(int)$body['target_dept_id']]);
                        $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    } elseif ($scope === 'faculty' && !empty($_POST['target_faculty_id'])) {
                        $stmt = $db->prepare("SELECT u.email FROM users u JOIN staff_records s ON s.user_id = u.id WHERE s.dept_id IN (SELECT dept_id FROM departments WHERE parent_unit = ?) AND u.email IS NOT NULL AND u.email != ''");
                        $stmt->execute([(int)$body['target_faculty_id']]);
                        $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    } elseif ($scope === 'directorate' && !empty($_POST['target_dir_id'])) {
                        $stmt = $db->prepare("SELECT u.email FROM users u JOIN staff_records s ON s.user_id = u.id WHERE s.dept_id IN (SELECT dept_id FROM departments WHERE parent_unit = ?) AND u.email IS NOT NULL AND u.email != ''");
                        $stmt->execute([(int)$body['target_dir_id']]);
                        $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    }
                    break;

                case 'other':
                    if (!empty($_POST['raw_recipients'])) {
                        $rawEmails = explode(',', $_POST['raw_recipients']);
                        foreach ($rawEmails as $email) {
                            $clean = trim($email);
                            if (!empty($clean)) $recipients[] = $clean;
                        }
                    }
                    break;
            }
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
            header('Location: /email/send');
            exit;
        }

        $recipients = array_filter(array_unique($recipients));

        if (empty($recipients)) {
            Security::setFlash('error', 'No valid recipients found');
            header('Location: /email/send');
            exit;
        }

        $smtpConfig = $db->query("SELECT smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, smtp_from_email, smtp_from_name FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

        $successCount = 0;
        $errorCount = 0;

        foreach ($recipients as $recipientEmail) {
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
                else $mail->SMTPAuth = false;

                $mail->setFrom($senderEmail, $senderName);
                $mail->addAddress($recipientEmail);
                $mail->Subject = $subject;
                $mail->isHTML(true);
                $mail->Body = $emailBody;

                $mail->send();
                $successCount++;
            } catch (\Throwable $e) {
                $errorCount++;
                error_log("Email send failed to {$recipientEmail}: " . $e->getMessage());
            }
        }

        Security::setFlash('ok', "Email dispatch complete. Sent: {$successCount}, Failed: {$errorCount}");
        header('Location: /email/send');
        exit;
    }
}
