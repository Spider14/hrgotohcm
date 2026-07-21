<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Database;
use App\Services\AuthService;
use App\Helpers\Security;
use App\Middleware\CSRFMiddleware;
use PDO;
use Throwable;

class AuthController {
    private AuthService $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

    public function showLogin(Request $request): void {
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/dashboard');
            exit;
        }
        $db = Database::getConnection();
        $config = $db->query("SELECT company_name, company_short_name, company_logo_url FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
        $companyName = $config['company_name'] ?? 'HRGoTo HCM';
        $companyShortName = $config['company_short_name'] ?? '';
        $companyLogo = $config['company_logo_url'] ?? '';
        require_once __DIR__ . '/../Views/auth/login.php';
    }

    public function login(Request $request): void {
        $isAjax = $request->isAjax();
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
        }

        // Convert PHP warnings/notices in this request into JSON responses instead of HTML output.
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Login processing error: ' . $message,
                'at' => basename($file) . ':' . $line,
            ]);
            exit;
        });

        try {
        
            $data = $request->getBody();
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? null);

            if (!Security::verifyCsrfToken($token)) {
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => 'Security Token CSRF Validation Mismatch.']);
                } else {
                    Security::setFlash('error', 'Session expired. Please login again.');
                    header('Location: index.php?url=login');
                }
                return;
            }

            $identifier = trim((string)($data['identifier'] ?? $data['email'] ?? ''));
            $password = $data['password'] ?? '';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            if ($identifier === '' || empty($password)) {
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => 'All mandatory parameters must be specified.']);
                } else {
                    Security::setFlash('error', 'Username/email and password are required.');
                    header('Location: index.php?url=login');
                }
                return;
            }

            $result = $this->authService->authenticate($identifier, $password, $ipAddress);
            if ($isAjax) {
                echo json_encode($result);
            } else {
                if (!empty($result['success'])) {
                    $redirect = trim((string)($result['redirect'] ?? '/dashboard'), '/');
                    header('Location: index.php?url=' . $redirect);
                } else {
                    $message = (string)($result['message'] ?? 'Invalid credentials provided.');
                    Security::setFlash('error', $message);
                    header('Location: index.php?url=login');
                }
            }
        } catch (Throwable $e) {
            if ($isAjax) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Unhandled login exception: ' . $e->getMessage(),
                ]);
            } else {
                Security::setFlash('error', 'Login failed. Please try again.');
            header('Location: index.php?url=login');
            }
        } finally {
            restore_error_handler();
            exit;
        }
    }

    public function showForgotPassword(Request $request): void {
        require_once __DIR__ . '/../Views/auth/forgot_password.php';
    }

    public function sendResetLink(Request $request): void {
        CSRFMiddleware::validate($request);
        $data = $request->getBody();
        $email = trim((string)($data['email'] ?? ''));

        if ($email === '') {
            Security::setFlash('error', 'Email is required');
            header('Location: index.php?url=forgot-password');
            exit;
        }

        try {
            $db = Database::getConnection();

            $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_token (token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $userStmt = $db->prepare("SELECT id, fullname FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1");
            $userStmt->execute(['email' => $email]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                Security::setFlash('ok', 'If that email is registered, a reset link has been sent.');
                header('Location: index.php?url=login');
                exit;
            }

            $db->prepare("DELETE FROM password_resets WHERE email = :email")->execute(['email' => $email]);

            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)");
            $stmt->execute(['email' => $email, 'token' => $token, 'expires_at' => $expires]);

            $resetLink = ($_ENV['APP_URL'] ?? '') . '/index.php?url=reset-password&token=' . urlencode($token) . '&email=' . urlencode($email);

            $config = $db->query("SELECT * FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

            if (empty($config['smtp_host'])) {
                Security::setFlash('error', 'System email is not configured. Please contact the administrator.');
                header('Location: index.php?url=forgot-password');
                exit;
            }

            $companyName = $config['company_name'] ?? 'HRGoTo HCM';
            $logoUrl = !empty($config['company_logo_url']) ? $config['company_logo_url'] : (($_ENV['APP_URL'] ?? '') . '/assets/img/logo.jpg');
            $year = date('Y');
            $expiryHours = 1;
            $hourLabel = $expiryHours === 1 ? '1 hour' : "{$expiryHours} hours";

            $mailBody = "
            <!DOCTYPE html>
            <html>
            <head><meta charset='UTF-8'></head>
            <body style='margin:0;padding:0;background-color:#f4f6f9;font-family:Arial,Helvetica,sans-serif;'>
                <table align='center' cellpadding='0' cellspacing='0' style='width:100%;max-width:600px;margin:40px auto;'>
                    <tr>
                        <td style='background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);'>
                            <table cellpadding='0' cellspacing='0' style='width:100%;'>
                                <tr>
                                    <td style='text-align:center;padding:32px 24px 16px;background:linear-gradient(135deg,#1a365d 0%,#2b6cb0 100%);'>
                                        <img src='{$logoUrl}' alt='{$companyName}' style='max-height:64px;margin-bottom:8px;'>
                                        <h1 style='color:#ffffff;font-size:20px;margin:8px 0 0;font-weight:600;'>{$companyName}</h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding:32px 24px;'>
                                        <h2 style='color:#1a365d;font-size:18px;margin:0 0 16px;'>Password Reset Request</h2>
                                        <p style='color:#555;font-size:14px;line-height:1.6;margin:0 0 16px;'>Hello <strong>{$user['fullname']}</strong>,</p>
                                        <p style='color:#555;font-size:14px;line-height:1.6;margin:0 0 16px;'>You recently requested to reset your password. Click the button below to set a new password. This link expires in <strong>{$hourLabel}</strong>.</p>
                                        <table cellpadding='0' cellspacing='0' style='margin:24px 0;'>
                                            <tr>
                                                <td style='background:#2b6cb0;border-radius:6px;text-align:center;padding:12px 32px;'>
                                                    <a href='{$resetLink}' style='color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;display:inline-block;'>Reset Password</a>
                                                </td>
                                            </tr>
                                        </table>
                                        <p style='color:#555;font-size:14px;line-height:1.6;margin:16px 0;'>If the button does not work, copy and paste this link into your browser:</p>
                                        <p style='color:#2b6cb0;font-size:12px;word-break:break-all;margin:0 0 16px;'>{$resetLink}</p>
                                        <hr style='border:none;border-top:1px solid #e2e8f0;margin:24px 0;'>
                                        <p style='color:#999;font-size:13px;line-height:1.5;margin:0;'>If you did not request this password reset, please ignore this email or contact your system administrator.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='background:#1a365d;padding:20px 24px;text-align:center;'>
                                        <p style='color:#a0c4e8;font-size:12px;margin:0 0 8px;'>&copy; {$year} {$companyName}. All rights reserved.</p>
                                        <p style='color:#a0c4e8;font-size:11px;margin:0;'>Powered by <a href='https://norgence.com' target='_blank' rel='noopener noreferrer' style='color:#63b3ed;text-decoration:none;font-weight:600;'>Norgence Digital Solutions</a></p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>";

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->Port = (int)($config['smtp_port'] ?? 587);
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_username'] ?? '';
            $mail->Password = $config['smtp_password'] ?? '';

            $enc = $config['smtp_encryption'] ?? 'tls';
            if ($enc === 'tls') $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            elseif ($enc === 'ssl') $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            else $mail->SMTPAuth = false;

            $mail->setFrom($config['smtp_from_email'] ?? 'noreply@hrgoto.com', $config['smtp_from_name'] ?? $companyName);
            $mail->addAddress($email, $user['fullname']);
            $mail->Subject = "{$companyName} - Password Reset Request";
            $mail->isHTML(true);
            $mail->Body = $mailBody;
            $mail->send();

            Security::setFlash('ok', 'If that email is registered, a reset link has been sent.');
            header('Location: index.php?url=login');
        } catch (Throwable $e) {
            error_log("Password reset email failed for {$email}: " . $e->getMessage());
            Security::setFlash('error', 'Unable to send reset email. Please try again or contact support.');
            header('Location: index.php?url=forgot-password');
        }
        exit;
    }

    public function showResetPassword(Request $request): void {
        $token = trim((string)($_GET['token'] ?? ''));
        $email = trim((string)($_GET['email'] ?? ''));
        if ($token === '' || $email === '') {
            Security::setFlash('error', 'Invalid reset link.');
            header('Location: index.php?url=login');
            exit;
        }
        require_once __DIR__ . '/../Views/auth/reset_password.php';
    }

    public function processResetPassword(Request $request): void {
        CSRFMiddleware::validate($request);
        $data = $request->getBody();
        $token = trim((string)($data['token'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $password = trim((string)($data['password'] ?? ''));
        $passwordConfirm = trim((string)($data['password_confirm'] ?? ''));

        if ($token === '' || $email === '' || $password === '') {
            Security::setFlash('error', 'All fields are required');
            header('Location: index.php?url=reset-password&token=' . urlencode($token) . '&email=' . urlencode($email));
            exit;
        }
        if ($password !== $passwordConfirm) {
            Security::setFlash('error', 'Passwords do not match');
            header('Location: index.php?url=reset-password&token=' . urlencode($token) . '&email=' . urlencode($email));
            exit;
        }
        if (strlen($password) < 6) {
            Security::setFlash('error', 'Password must be at least 6 characters');
            header('Location: index.php?url=reset-password&token=' . urlencode($token) . '&email=' . urlencode($email));
            exit;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM password_resets WHERE email = :email AND token = :token AND used = 0 AND expires_at > NOW() LIMIT 1");
            $stmt->execute(['email' => $email, 'token' => $token]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                Security::setFlash('error', 'Invalid or expired reset link. Please request a new one.');
                header('Location: index.php?url=login');
                exit;
            }

            $updateStmt = $db->prepare("UPDATE users SET password = :password WHERE email = :email AND deleted_at IS NULL");
            $updateStmt->execute([
                'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 11]),
                'email' => $email,
            ]);

            $db->prepare("UPDATE password_resets SET used = 1 WHERE id = :id")->execute(['id' => $reset['id']]);

            Security::setFlash('ok', 'Password reset successful. Please login with your new password.');
            header('Location: index.php?url=login');
        } catch (Throwable $e) {
            Security::setFlash('error', 'An error occurred. Please try again.');
            header('Location: index.php?url=login');
        }
        exit;
    }

    public function logout(Request $request): void {
        if (isset($_SESSION['user_id'])) {
            $userModel = new \App\Models\User();
            $userModel->logAuditTrail((int)$_SESSION['user_id'], 'AUTH_LOGOUT', 'User initialized dynamic session close.');
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/login');
        exit;
    }
}