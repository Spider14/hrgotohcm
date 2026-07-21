<?php
/**
 * @var array $settings
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
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Configure Campaigns</li>
                </ol>
            </nav>
            <?php require __DIR__ . '/../../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        
        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] === 'saved'): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-lg d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i> Campaign template configurations updated successfully.
                </div>
            <?php elseif ($_GET['status'] === 'field_added'): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-lg d-flex align-items-center" role="alert">
                    <i class="fas fa-plus-circle me-2"></i> New campaign milestone category column successfully structuralized.
                </div>
            <?php elseif ($_GET['status'] === 'save_failed' || $_GET['status'] === 'field_failed'): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-lg d-flex align-items-center" role="alert">
                    <i class="fas fa-excounter-circle me-2"></i> Database operation was rejected. Verify parameters or structural collisions.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm rounded-lg p-4 bg-white">
                    <div class="border-bottom pb-3 mb-4">
                        <h4 class="fw-bold text-dark m-0"><i class="fas fa-sliders-h me-2" style="color: #1d2a52;"></i>Automated SMS Templates</h4>
                        <p class="text-muted small m-0 mt-1">Modify runtime variables dispatched during automated state context transitions.</p>
                    </div>

                    <form action="/sms/campaigns/update" method="POST">
                        <input type="hidden" name="sms_cam_id" value="<?php echo (int)($settings['sms_cam_id'] ?? 1); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\Security::generateCsrfToken(); ?>">

                        <?php 
                        // Loop through columns dynamically bypassing internal primary key ID indicator
                        foreach ($settings as $columnName => $textValue): 
                            if ($columnName === 'sms_cam_id') continue;
                            
                            $formattedLabel = ucwords(str_replace('_', ' ', $columnName));
                        ?>
                            <div class="mb-4">
                                <label class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block mb-2">
                                    <?php echo \App\Helpers\Security::escape($formattedLabel); ?> Template Notification Message
                                </label>
                                <textarea 
                                    name="<?php echo \App\Helpers\Security::escape($columnName); ?>" 
                                    class="form-control font-sans text-dark p-3" 
                                    rows="4" 
                                    style="border: 1.5px solid #d1d5db; border-radius: 6px; resize: vertical;"
                                    placeholder="Type notification script details here..."
                                ><?php echo \App\Helpers\Security::escape($textValue); ?></textarea>
                                <div class="form-text text-muted small">System applies raw character strings matching this layout container automatically.</div>
                            </div>
                        <?php endforeach; ?>

                        <div class="border-top pt-3 text-end">
                            <button type="submit" class="btn btn-sm px-4 fw-bold shadow-sm" style="color: #ffffff; background-color: #1d2a52; border-radius: 6px;">
                                <i class="fas fa-save me-1"></i> Save Templates
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm rounded-lg p-4 bg-white mb-4">
                    <div class="border-bottom pb-3 mb-3">
                        <h5 class="fw-bold text-dark m-0"><i class="fas fa-folder-plus me-2" style="color: #1d2a52;"></i>Extend Template Structure</h5>
                    </div>
                    <p class="text-muted small">Append a new milestone status categorization context dynamically onto database structural tracking records.</p>
                    
                    <form action="/sms/campaigns/add-field" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\Security::generateCsrfToken(); ?>">
                        <div class="mb-3">
                            <input 
                                type="text" 
                                name="field_name" 
                                class="form-control small font-monospace fw-bold" 
                                style="border: 1.5px solid #d1d5db; border-radius: 6px; font-size: 0.85rem;" 
                                placeholder="e.g. interviewed, rejected" 
                                required
                            >
                        </div>
                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100 fw-bold rounded-lg py-2">
                            <i class="fas fa-plus me-1"></i> Append Column Attribute
                        </button>
                    </form>
                </div>

                <div class="card border-0 shadow-sm rounded-lg p-4 bg-white" style="border-start: 4px solid #1d2a52 !important;">
                    <div class="mb-2">
                        <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">System Configuration Integrity Notice</span>
                    </div>
                    <p class="text-muted small m-0">Ensure modification updates closely follow operational communication patterns. Avoid deleting placeholders without checking integration references throughout candidate change events.</p>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .tracking-wider { tracking-spacing: 0.05em; }
    textarea.form-control:focus {
        border-color: #1d2a52 !important;
        box-shadow: 0 0 0 0.2rem rgba(29, 42, 82, 0.2) !important;
        outline: none;
    }
</style>

<?php require_once dirname(__DIR__, 3) . '/Views/dashboard/layouts/footer.php'; ?>