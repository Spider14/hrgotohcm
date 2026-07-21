<?php
$requestPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$basePath = rtrim(dirname($scriptPath), '/');
if ($basePath === '.' || $basePath === '\\') {
    $basePath = '';
}
if ($requestPath !== '' && strpos($requestPath, '/index.php') !== false) {
    $activeBase = rtrim(substr($requestPath, 0, strpos($requestPath, '/index.php')), '/');
} else {
    $activeBase = $basePath;
}
$assetBase = $activeBase !== '' ? $activeBase : $basePath;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRGoTo HCM - Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="<?php echo \App\Helpers\Security::escape(($assetBase !== '' ? $assetBase : '') . '/assets/css/style.css'); ?>" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="<?php echo \App\Helpers\Security::escape(($assetBase !== '' ? $assetBase : '') . '/assets/favicons/favicon.svg'); ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo \App\Helpers\Security::escape(($assetBase !== '' ? $assetBase : '') . '/assets/favicons/favicon-96x96.png'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo \App\Helpers\Security::escape(($assetBase !== '' ? $assetBase : '') . '/assets/favicons/apple-touch-icon.png'); ?>">
    <link rel="manifest" href="<?php echo \App\Helpers\Security::escape(($assetBase !== '' ? $assetBase : '') . '/assets/favicons/site.webmanifest'); ?>">
    <meta name="theme-color" content="#162C5B">
    <style>
        #background-overlay {
            background: linear-gradient(135deg, rgba(26, 54, 93, 0.88) 0%, rgba(43, 108, 176, 0.88) 100%),
                        url('<?php echo \App\Helpers\Security::escape($backgroundImageUrl ?? (($assetBase !== '' ? $assetBase : '') . "/assets/login-backgrounds/hrgoto_bg.jpg")); ?>') no-repeat center center/cover !important;
        }
    </style>
</head>
<body>
    <div id="background-overlay"></div>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card login-card shadow-lg p-4 border-0">
            <div class="text-center mb-4">
                <div class="mx-auto mb-1 d-flex align-items-center justify-content-center">
                    <img src="<?php echo \App\Helpers\Security::escape(($assetBase !== '' ? $assetBase : '') . '/assets/img/logo.jpg'); ?>" alt="HRGoto HCM Logo" class="img-fluid" style="max-height: 95px;">
                </div>
                <h4 class="fw-bold text-dark mb-1">Reset Password</h4>
                <p class="text-muted" style="font-size:0.95rem;">Enter your email to receive a reset link</p>
            </div>

            <?php if (\App\Helpers\Security::hasFlash('error')): ?>
                <div class="alert alert-danger" role="alert"><?php echo \App\Helpers\Security::escape((string)\App\Helpers\Security::getFlash('error')); ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php?url=auth/forgot-password" autocomplete="off" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\Security::generateCsrfToken(); ?>">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                        <input type="email" name="email" class="form-control bg-light border-start-0" placeholder="you@example.com" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Send Reset Link</button>
                <p class="text-center mt-3 mb-0"><a href="index.php?url=login" class="small text-decoration-none">Back to Login</a></p>
            </form>
            <p class="text-muted text-center" style="font-size:0.8rem;margin-top:1rem;margin-bottom:0;">Powered by <a href="https://norgence.com" target="_blank" rel="noopener noreferrer" class="fw-bold text-decoration-none" style="color:#162C5B;">Norgence</a> Digital Solutions</p>
        </div>
    </div>
</body>
</html>
