<?php
/**
 * @var array $settings
 * @var array $allLogs
 */
?>
<?php require_once dirname(__DIR__, 3) . '/Views/dashboard/layouts/header.php'; ?>
<?php require_once dirname(__DIR__, 3) . '/Views/dashboard/layouts/sidebar.php'; ?>

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3">
                <i class="fas fa-bars"></i>
            </button>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb m-0 bg-transparent p-0">
                    <li class="breadcrumb-item text-dark"><a href="/dashboard" class="text-decoration-none fw-semibold">Dashboard</a></li>
                    <li class="breadcrumb-item text-dark fw-semibold">SMS Communications</li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Campaign Templates Manifest</li>
                </ol>
            </nav>
            <?php require __DIR__ . '/../../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark m-0">Campaign Templates Registry</h4>
                <p class="text-muted small m-0">Overview of active system milestone SMS triggers and text scripts.</p>
            </div>
            <a href="/sms/campaigns/configure" class="btn btn-sm px-3 fw-bold shadow-sm" style="color: #ffffff; background-color: #1d2a52; border-radius: 6px;">
                <i class="fas fa-edit me-1"></i> Manage & Edit Templates
            </a>
        </div>

        <div class="row g-4 mb-5">
            <?php 
            $templateCount = 0;
            foreach ($settings as $columnName => $textValue): 
                if ($columnName === 'sms_cam_id') continue;
                $templateCount++;
                
                $formattedTitle = ucwords(str_replace('_', ' ', $columnName));
                $charCount = strlen($textValue ?? '');
                $smsParts = ceil($charCount / 160) ?: 1;
            ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-lg bg-white h-100 border-top border-4" style="border-top-color: #1d2a52 !important;">
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="badge bg-light text-dark font-monospace text-uppercase tracking-wider px-2 py-1 small border">
                                        Trigger: <?php echo \App\Helpers\Security::escape($columnName); ?>
                                    </span>
                                    <span class="text-muted small font-monospace">
                                        <i class="fas fa-calculator me-1"></i> <?php echo $smsParts; ?> SMS Page
                                    </span>
                                </div>
                                
                                <h5 class="fw-bold text-dark mb-2"><?php echo \App\Helpers\Security::escape($formattedTitle); ?> Notification</h5>
                                
                                <div class="p-3 bg-light rounded font-sans text-dark small border mb-3" style="min-height: 90px; white-space: pre-wrap; font-style: <?php echo empty($textValue) ? 'italic' : 'normal'; ?>; color: <?php echo empty($textValue) ? '#6c757d' : '#212529'; ?> !important;">
                                    <?php echo !empty($textValue) ? \App\Helpers\Security::escape($textValue) : 'No template message initialized for this milestone event stage.'; ?>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-2">
                                <small class="text-muted font-monospace"><?php echo $charCount; ?> characters</small>
                                <a href="/sms/campaigns/configure" class="btn btn-link btn-sm text-decoration-none p-0 fw-bold" style="color: #1d2a52;">
                                    Modify Text <i class="fas fa-chevron-right ms-1 small"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($templateCount === 0): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-lg p-5 bg-white text-center">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <h5 class="fw-bold text-dark">No Templates Found</h5>
                        <p class="text-muted small">No structural template workflows have been configured inside `sms_campaign_templates` yet.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once dirname(__DIR__, 3) . '/Views/dashboard/layouts/footer.php'; ?>