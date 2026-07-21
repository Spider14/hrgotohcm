<?php
$requestPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$basePath = rtrim(dirname($scriptPath), '/');
if ($basePath === '.' || $basePath === '\\') {
    $basePath = '';
}

// Build a stable base for assets and AJAX regardless of virtual host/subfolder layout.
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
    <title>HRGoTo HCM - Login</title>
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
    
    <div id="connectivity-badge" class="online">
        <i class="fas fa-signal"></i> <span id="connectivity-text">Online</span>
    </div>

    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card login-card shadow-lg p-4 border-0">
            <div class="text-center mb-4">
                <div class="mx-auto mb-1 d-flex align-items-center justify-content-center">
                    <?php if (!empty($companyLogo)): ?>
                        <img src="<?php echo \App\Helpers\Security::escape($companyLogo); ?>" alt="<?php echo \App\Helpers\Security::escape($companyName); ?> Logo" class="img-fluid" style="max-height: 95px;">
                    <?php else: ?>
                        <img src="<?php echo \App\Helpers\Security::escape(($assetBase !== '' ? $assetBase : '') . '/assets/img/logo_nbg_h.png'); ?>" alt="HRGoto HCM Logo" class="img-fluid" style="max-height: 95px;">
                    <?php endif; ?>
                </div>
                <h4 class="fw-bold text-dark mb-1">HRGoTo HCM</h4>
                <p class="text-muted" style="font-size:1.1rem;"><?php echo \App\Helpers\Security::escape($companyName); ?></p>
            </div>

            <div id="toast-wrapper-feedback" class="alert d-none" role="alert"></div>
            <?php if (\App\Helpers\Security::hasFlash('error')): ?>
                <div class="alert alert-danger" role="alert"><?php echo \App\Helpers\Security::escape((string)\App\Helpers\Security::getFlash('error')); ?></div>
            <?php endif; ?>
            <?php if (\App\Helpers\Security::hasFlash('ok')): ?>
                <div class="alert alert-success" role="alert"><?php echo \App\Helpers\Security::escape((string)\App\Helpers\Security::getFlash('ok')); ?></div>
            <?php endif; ?>

            <form id="hrgoto-login-form" method="POST" action="index.php?url=auth/login" autocomplete="off" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\Security::generateCsrfToken(); ?>">
                <div class="mb-3 input-group-container">
                    <label class="form-label small fw-semibold">Username (Email)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                        <input type="text" name="identifier" id="email" class="form-control bg-light border-start-0" placeholder="Email" value="" required>
                    </div>
                </div>

                <div class="mb-3 input-group-container">
                    <label class="form-label small fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" name="password" id="password" class="form-control bg-light border-start-0 border-end-0" placeholder="••••••••" required>
                        <span class="input-group-text bg-light border-start-0 custom-password-toggle" id="togglePassword" style="cursor: pointer;">
                            <i class="fas fa-eye text-muted"></i>
                        </span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label small text-muted" for="rememberMe">Remember Session</label>
                    </div>
                    <a href="index.php?url=forgot-password" class="small text-decoration-none text-primary fw-medium">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2" id="submit-btn-element">
                    <span id="btn-text-default" class="fs-4">Login</span>
                    <svg id="btn-spinner-element" class="d-none animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="width:18px;height:18px;animation:spin 0.8s linear infinite;">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:0.25;"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:0.75;"></path>
                    </svg>
                    <span id="btn-text-verifying" class="d-none">Verifying Credentials</span>
                </button>
                <style>
                    @keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
                </style>
            </form>
            <p class="text-muted text-center" style="font-size:0.8rem;margin-top:1rem;margin-bottom:0;">Powered by <a href="https://norgence.com" target="_blank" rel="noopener noreferrer" class="fw-bold text-decoration-none" style="color:#162C5B;">Norgence</a> Digital Solutions</p>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = "<?php echo \App\Helpers\Security::generateCsrfToken(); ?>";
        const APP_URL = <?php echo json_encode($assetBase); ?>;
        const AUTH_LOGIN_ENDPOINT = `${APP_URL || ""}/index.php?url=auth/login`;
        const DASHBOARD_ENDPOINT = `${APP_URL || ""}/index.php?url=dashboard`;
    </script>
    <script src="<?php echo \App\Helpers\Security::escape(($assetBase !== '' ? $assetBase : '') . '/assets/js/auth.js?v=20260704-4'); ?>"></script>
</body>
</html>