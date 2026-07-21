<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();

$currentFields = [];
if (!empty($config['id_card_fields'])) {
    $currentFields = json_decode((string)$config['id_card_fields'], true) ?: [];
}
$primaryColor = !empty($config['id_card_primary_color']) ? $config['id_card_primary_color'] : '#162C5B';
$secondaryColor = !empty($config['id_card_secondary_color']) ? $config['id_card_secondary_color'] : '#2b6cb0';
$logoUrl = !empty($config['id_card_logo']) ? $config['id_card_logo'] : '';
$companyName = !empty($config['company_name']) ? $config['company_name'] : '';
$companyPhone = !empty($config['company_phone']) ? $config['company_phone'] : '';
$companyEmail = !empty($config['company_email']) ? $config['company_email'] : '';
$companyLogoUrl = !empty($config['company_logo_url']) ? $config['company_logo_url'] : '';
$companyRegion = !empty($config['company_region']) ? $config['company_region'] : '';
$companyCity = !empty($config['company_city']) ? $config['company_city'] : '';
$companyTell = !empty($config['company_tell']) ? $config['company_tell'] : '';
$companyTagline = !empty($config['company_tagline']) ? $config['company_tagline'] : '';
$watermarkUrl = \App\Helpers\Security::escape(!empty($logoUrl) ? $logoUrl : (!empty($companyLogoUrl) ? $companyLogoUrl : ($appUrl . '/assets/img/logo_nbg.png')));

// Split company name for display
$cNameEscaped = \App\Helpers\Security::escape($companyName);
$cWords = explode(' ', $cNameEscaped);
$cTotal = count($cWords);
$cLine1 = $cWords[0] ?? $cNameEscaped;
$cLine2 = $cTotal > 1 ? implode(' ', array_slice($cWords, 1)) : '';
// Back name split: >2 words → split second+third to line 2; 4+ → two even lines
if ($cTotal > 4) {
    $half = (int)ceil($cTotal / 2);
    $backC1 = implode(' ', array_slice($cWords, 0, $half));
    $backC2 = implode(' ', array_slice($cWords, $half));
} elseif ($cTotal === 4) {
    $backC1 = implode(' ', array_slice($cWords, 0, 2));
    $backC2 = implode(' ', array_slice($cWords, 2));
} elseif ($cTotal === 3) {
    $backC1 = $cWords[0];
    $backC2 = implode(' ', array_slice($cWords, 1));
} else {
    $backC1 = $cNameEscaped;
    $backC2 = '';
}

$allFields = [
    'staff_id_card' => 'Staff ID',
    'fullname' => 'Full Name',
    'dept_name' => 'Department',
    'designation_title' => 'Designation',
    'employment_status' => 'Employment Status',
    'phone_one' => 'Phone',
    'email' => 'Email',
    'gender' => 'Gender',
    'date_joined' => 'Date Joined',
    'date_of_birth' => 'Date of Birth',
];
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">ID Card Settings</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <?php require __DIR__ . '/../dashboard/layouts/flash.php'; ?>

        <div class="row g-4">
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm p-4">
                    <h5 class="fw-bold mb-3">ID Card Configuration</h5>
                    <p class="text-muted small mb-4">Select which fields appear on employee ID cards.</p>

                    <form method="POST" action="<?php echo $appUrl; ?>/admin/id-card-config" id="idCardConfigForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

                        <h6 class="fw-semibold mb-2">Display Fields</h6>
                        <div class="border rounded p-3 bg-light mb-4">
                            <div class="row">
                                <?php foreach ($allFields as $key => $label): ?>
                                    <div class="col-6">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="fields[]" value="<?php echo $key; ?>" id="field_<?php echo $key; ?>" <?php echo in_array($key, $currentFields) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="field_<?php echo $key; ?>"><?php echo $label; ?></label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <h6 class="fw-semibold mb-2">Appearance</h6>
                        <div class="border rounded p-3 bg-light">
                            <div class="row g-3">
                                <div class="col-6 col-md-4">
                                    <label class="form-label small">Primary Color</label>
                                    <input type="color" name="primary_color" class="form-control form-control-color" value="<?php echo $primaryColor; ?>">
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small">Secondary Color</label>
                                    <input type="color" name="secondary_color" class="form-control form-control-color" value="<?php echo $secondaryColor; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end">
                            <button type="submit" class="btn btn-dark btn-sm px-4">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold mb-0">Live Preview</h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active" id="previewFrontBtn" onclick="showPreviewFace('front')">Front</button>
                            <button type="button" class="btn btn-outline-primary" id="previewBackBtn" onclick="showPreviewFace('back')">Back</button>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">Adjust settings to see changes in real time.</p>

                    <div class="d-flex justify-content-center preview-wrapper">
                        <div class="id-card-preview" id="previewCard">

                            <!-- FRONT -->
                            <div id="previewFront" class="preview-face">
                                <div class="watermark wm-left"></div>
                                <div class="watermark wm-right"></div>
                                <div class="lanyard-hole-zone"></div>

                                <div class="card-content">
                                    <header class="card-header">
                                        <h1 class="company-name" id="previewCompanyLine1"><?php echo $cLine1; ?></h1>
                                        <?php if (!empty($cLine2)): ?>
                                            <h1 class="company-name" id="previewCompanyLine2"><?php echo $cLine2; ?></h1>
                                        <?php endif; ?>
                                        <?php if (!empty($companyTagline)): ?>
                                            <p class="company-tagline" id="previewTagline"><?php echo \App\Helpers\Security::escape($companyTagline); ?></p>
                                        <?php endif; ?>
                                    </header>

                                    <div class="photo-container">
                                        <div class="photo-placeholder" style="background:<?php echo $secondaryColor; ?>;">JD</div>
                                    </div>

                                    <div class="employee-details">
                                        <h2 class="employee-name">JOHN DOE</h2>
                                        <div class="meta-grid">
                                            <div class="meta-row" id="previewStaffId"><span class="label">ID NO:</span><span class="value">EMP-001</span></div>
                                            <div class="meta-row preview-row" data-field="dept_name"><span class="label">DEPARTMENT:</span><span class="value">Information Technology</span></div>
                                            <div class="meta-row preview-row" data-field="designation_title"><span class="label">JOB TITLE:</span><span class="value">Senior Developer</span></div>
                                            <div class="meta-row preview-row" data-field="employment_status"><span class="label">STATUS:</span><span class="value">Active</span></div>
                                            <div class="meta-row preview-row" data-field="phone_one"><span class="label">PHONE:</span><span class="value">+233 50 000 0000</span></div>
                                            <div class="meta-row preview-row" data-field="email"><span class="label">EMAIL:</span><span class="value">john.doe@example.com</span></div>
                                            <div class="meta-row preview-row" data-field="gender"><span class="label">GENDER:</span><span class="value">Male</span></div>
                                            <div class="meta-row preview-row" data-field="date_joined"><span class="label">JOINED:</span><span class="value">2024-01-15</span></div>
                                            <div class="meta-row preview-row" data-field="date_of_birth"><span class="label">DOB:</span><span class="value">1990-05-20</span></div>
                                        </div>
                                    </div>

                                    <footer class="card-footer-front">
                                        <span class="auth-text">AUTHORIZED STAFF</span>
                                        <div class="hologram-seal"></div>
                                    </footer>
                                </div>
                            </div>

                            <!-- BACK -->
                            <div id="previewBack" class="preview-face" style="display:none;">
                                <div class="lanyard-hole-zone"></div>

                                <div class="card-content">
                                    <div class="back-logo-area">
                                        <img src="<?php echo $watermarkUrl; ?>" alt="" class="back-logo-img" id="previewLogo">
                                    </div>

                                    <div class="ownership-info">
                                        <h3>PROPERTY OF <?php echo strtoupper($backC1); ?><?php if (!empty($backC2)): ?> <?php echo strtoupper($backC2); ?><?php endif; ?></h3>
                                        <p>IF FOUND, PLEASE RETURN TO:</p>
                                        <p>HEAD OFFICE, <?php echo \App\Helpers\Security::escape(strtoupper($companyCity ?: ($companyRegion ?: 'ACCRA'))); ?>, GHANA</p>
                                        <p class="phone-text">TEL: <?php echo \App\Helpers\Security::escape($companyTell ?: '+233 (0) 302 000 000'); ?></p>
                                    </div>

                                    <div class="verification-zone">
                                        <div class="qr-placeholder">[QR]</div>
                                        <p class="qr-caption">EMPLOYEE VERIFICATION QR<br>ISSUED BY <?php echo strtoupper($backC1); ?><?php if (!empty($backC2)): ?> <?php echo strtoupper($backC2); ?><?php endif; ?>, <?php echo \App\Helpers\Security::escape($companyCity ?: ($companyRegion ?: 'ACCRA')); ?></p>
                                    </div>

                                    <div class="security-assets">
                                        <svg id="barcodeSvg"></svg>
                                        <div class="magnetic-strip"></div>
                                    </div>

                                    <footer class="card-footer-back">
                                        <span class="emergency-heading">EMERGENCY CONTACTS:</span>
                                        <span class="emergency-number"><?php echo \App\Helpers\Security::escape($companyTell ?: '+233 (0) 302 000 000'); ?> / <?php echo \App\Helpers\Security::escape($companyPhone ?: '+233 (0) 50 000 0000'); ?></span>
                                    </footer>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary-navy: #0F1E36;
    --text-dark: #334155;
    --card-bg-start: #E0F2FE;
    --card-bg-end: #DCFCE7;
    --footer-purple: #E0E7FF;
    --silver-gradient: linear-gradient(135deg, #CBD5E1 0%, #E2E8F0 50%, #94A3B8 100%);
    --card-width: 300px;
    --card-height: 490px;
    --slot-clearance: 36px;
}

.preview-wrapper {
    background: #e8ecf1;
    border-radius: 8px;
    padding: 24px 0;
    min-height: 540px;
    display: flex;
    align-items: center;
}

.id-card-preview {
    position: relative;
    width: var(--card-width);
    height: var(--card-height);
    perspective: 1000px;
    border-radius: 16px;
    overflow: hidden;
    background: linear-gradient(180deg, var(--card-bg-start) 0%, var(--card-bg-end) 100%);
    border: 3px solid rgba(15, 30, 54, 0.15);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    box-sizing: border-box;
}

.preview-face {
    width: 100%;
    height: 100%;
    position: relative;
    box-sizing: border-box;
}

/* Lanyard hole */
.lanyard-hole-zone {
    position: absolute;
    top: 12px;
    left: 50%;
    transform: translateX(-50%);
    width: 32px;
    height: 8px;
    border-radius: 4px;
    background: rgba(15, 30, 54, 0.08);
    border: 1px dashed rgba(15, 30, 54, 0.15);
    z-index: 10;
}

/* Watermark circles */
.watermark {
    position: absolute;
    width: 200px;
    height: 200px;
    border: 2px dashed rgba(15, 30, 54, 0.04);
    border-radius: 50%;
    pointer-events: none;
}
.wm-left { top: 25%; left: -50px; }
.wm-right { bottom: 15%; right: -50px; }

/* Content wrapper */
.card-content {
    position: relative;
    z-index: 2;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: var(--slot-clearance) 18px 0 18px;
    box-sizing: border-box;
    text-transform: uppercase;
}

/* Header */
.card-header {
    text-align: center;
    width: 100%;
    margin-top: 8px;
    margin-bottom: 10px;
}
.company-name {
    color: var(--primary-navy);
    font-size: 1.1rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.5px;
    line-height: 1.2;
}
.company-tagline {
    color: var(--text-dark);
    font-size: 0.58rem;
    font-weight: 600;
    margin: 4px 0 0 0;
    letter-spacing: 2px;
    opacity: 0.8;
}

/* Photo */
.photo-container {
    width: 100px;
    height: 120px;
    border: 1px solid rgba(15, 30, 54, 0.2);
    border-radius: 6px;
    overflow: hidden;
    background: #f8fafc;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    margin-bottom: 8px;
}
.photo-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
}

/* Details */
.employee-details {
    width: 100%;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    padding: 0 6px;
    box-sizing: border-box;
}
.employee-name {
    color: var(--primary-navy);
    font-size: 1rem;
    font-weight: 800;
    text-align: center;
    margin: 0 0 8px 0;
    letter-spacing: -0.2px;
}
.meta-grid {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 0.62rem;
}
.meta-row {
    display: flex;
    justify-content: flex-start;
}
.meta-row .label {
    color: var(--primary-navy);
    font-weight: 700;
    width: 85px;
    flex-shrink: 0;
}
.meta-row .value {
    color: var(--text-dark);
    font-weight: 500;
}

/* Front Footer */
.card-footer-front {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 42px;
    background: var(--silver-gradient);
    display: flex;
    justify-content: center;
    align-items: center;
    box-sizing: border-box;
}
.auth-text {
    color: var(--primary-navy);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 1.5px;
}
.hologram-seal {
    position: absolute;
    right: 16px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: radial-gradient(circle, #67e8f9 0%, #a855f7 50%, #e2e8f0 100%);
    opacity: 0.7;
    box-shadow: inset 0 0 4px rgba(255,255,255,0.8);
}

/* ---- Back ---- */
.back-logo-area {
    margin-top: 18px;
    margin-bottom: 6px;
    text-align: center;
}
.back-logo-img {
    max-width: 56px;
    max-height: 56px;
}

.ownership-info {
    text-align: center;
    font-size: 0.7rem;
    color: var(--text-dark);
    line-height: 1.35;
    padding: 0 6px;
}
.ownership-info h3 {
    color: var(--primary-navy);
    font-size: 0.8rem;
    font-weight: 700;
    margin: 0 0 4px 0;
    line-height: 1.2;
}
.ownership-info p { margin: 0; }
.ownership-info .phone-text { font-weight: 600; margin-top: 3px; }

.verification-zone {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin: 4px 0;
}
.qr-placeholder {
    width: 74px;
    height: 74px;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.4rem;
    color: #999;
}
.qr-caption {
    font-size: 0.65rem;
    font-weight: 600;
    color: var(--text-dark);
    text-align: center;
    margin: 4px 0 0 0;
    line-height: 1.3;
    opacity: 0.8;
}

.security-assets {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    margin-bottom: 0;
}
#barcodeSvg {
    width: 100%;
    height: 32px;
}
.magnetic-strip {
    align-self: stretch;
    margin: 0 -18px;
    height: 24px;
    background: #1E293B;
}

/* Back Footer */
.card-footer-back {
    align-self: stretch;
    margin: 0 -18px;
    background: var(--footer-purple);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    box-sizing: border-box;
    flex-shrink: 0;
    padding: 8px 18px;
    min-height: 46px;
}
.emergency-heading {
    color: var(--primary-navy);
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.5px;
}
.emergency-number {
    color: var(--text-dark);
    font-size: 0.75rem;
    font-weight: 600;
}
</style>

<script>
function showPreviewFace(face) {
    document.getElementById('previewFront').style.display = face === 'front' ? '' : 'none';
    document.getElementById('previewBack').style.display = face === 'back' ? '' : 'none';
    document.getElementById('previewFrontBtn').classList.toggle('active', face === 'front');
    document.getElementById('previewBackBtn').classList.toggle('active', face === 'back');
}
(function() {
    var primaryInput = document.querySelector('input[name="primary_color"]');
    var secondaryInput = document.querySelector('input[name="secondary_color"]');
    var fieldChecks = document.querySelectorAll('input[name="fields[]"]');

    function updatePreview() {
        var checked = [];
        fieldChecks.forEach(function(cb) { if (cb.checked) checked.push(cb.value); });

        document.getElementById('previewStaffId').style.display = checked.indexOf('staff_id_card') !== -1 ? '' : 'none';

        document.querySelectorAll('.preview-row').forEach(function(row) {
            row.style.display = checked.indexOf(row.getAttribute('data-field')) !== -1 ? '' : 'none';
        });
    }

    primaryInput.addEventListener('input', updatePreview);
    secondaryInput.addEventListener('input', updatePreview);
    fieldChecks.forEach(function(cb) { cb.addEventListener('change', updatePreview); });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    JsBarcode('#barcodeSvg', 'EMP-001', {
        format: 'CODE128',
        width: 1.5,
        height: 28,
        displayValue: true,
        fontSize: 9,
        margin: 2
    });
});
</script>
