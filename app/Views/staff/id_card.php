<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$staffId = $staff['staff_id_card'] ?? '';
$staffName = $staff['fullname'] ?? '';
$staffDept = $staff['dept_name'] ?? '';
$staffDesig = $staff['designation_title'] ?? '';
$staffStatus = $staff['employment_status'] ?? '';
$staffPhone = $staff['phone_one'] ?? '';
$staffEmail = $staff['email'] ?? '';
$staffGender = $staff['gender'] ?? '';
$staffJoined = $staff['date_joined'] ?? '';
$staffDob = $staff['date_of_birth'] ?? '';
$staffAvatar = $staff['avatar_url'] ?? '';

$initials = '';
if (!empty($staffName)) {
    $parts = explode(' ', $staffName);
    $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[count($parts) - 1] ?? '', 0, 1));
}

$cName = \App\Helpers\Security::escape($companyName);
$cWords = explode(' ', $cName);
$cTotal = count($cWords);
// Front: 1st word on line 1
$cLine1 = $cWords[0] ?? $cName;
// Front >1 word: rest on line 2
$cLine2 = $cTotal > 1 ? implode(' ', array_slice($cWords, 1)) : '';
// Back: >2 words → split second+third to line 2; 4+ → two even lines
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
    $backC1 = $cName;
    $backC2 = '';
}

$qrUrl = urlencode(($_ENV['APP_URL'] ?? '') . '/index.php?url=staff/id-card&id=' . ($staff['id'] ?? ''));
$logoDisplay = \App\Helpers\Security::escape($logoUrl ?: ($_ENV['APP_URL'] ?? '') . '/assets/img/logo_nbg.png');
$companyRegionVal = \App\Helpers\Security::escape($companyRegion ?: 'ACCRA');
$companyCityVal = \App\Helpers\Security::escape($companyCity ?: $companyRegionVal);
$companyTellVal = \App\Helpers\Security::escape($companyTell ?: '+233 (0) 302 000 000');
$companyPhoneVal = \App\Helpers\Security::escape($companyPhone ?: '+233 (0) 50 000 0000');
$emergency1 = str_starts_with($companyTellVal, '+') ? $companyTellVal : '+233 (0) ' . ltrim($companyTellVal, '0');
$emergency2 = str_starts_with($companyPhoneVal, '+') ? $companyPhoneVal : '+233 (0) ' . ltrim($companyPhoneVal, '0');
$taglineVal = !empty($companyTagline) ? \App\Helpers\Security::escape($companyTagline) : '';
?>
<div class="container py-3">
    <div class="text-center mb-4 no-print">
        <button onclick="toggleFlip()" class="btn btn-outline-secondary btn-sm me-2"><i class="fas fa-sync-alt me-1"></i>Flip</button>
        <button onclick="window.print()" class="btn btn-primary btn-sm me-2"><i class="fas fa-print me-1"></i>Print</button>
        <button onclick="window.close()" class="btn btn-secondary btn-sm"><i class="fas fa-times me-1"></i>Close</button>
    </div>

    <div class="d-flex justify-content-center">
        <div class="id-card-container" id="idCardContainer">
            <div class="id-card" id="idCard">

                <!-- FRONT -->
                <div class="card-side card-front">
                    <div class="watermark wm-left"></div>
                    <div class="watermark wm-right"></div>
                    <div class="lanyard-hole-zone"></div>

                    <div class="card-content">
                        <header class="card-header">
                            <h1 class="company-name"><?php echo $cLine1; ?></h1>
                            <?php if (!empty($cLine2)): ?>
                                <h1 class="company-name"><?php echo $cLine2; ?></h1>
                            <?php endif; ?>
                            <?php if (!empty($taglineVal)): ?>
                                <p class="company-tagline"><?php echo $taglineVal; ?></p>
                            <?php endif; ?>
                        </header>

                        <div class="photo-container">
                            <?php if (!empty($staffAvatar)): ?>
                                <img src="<?php echo $appUrl . \App\Helpers\Security::escape($staffAvatar); ?>" alt="" class="staff-photo">
                            <?php else: ?>
                                <div class="photo-placeholder"><?php echo $initials; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="employee-details">
                            <h2 class="employee-name"><?php echo \App\Helpers\Security::escape(strtoupper($staffName)); ?></h2>
                            <div class="meta-grid">
                                <?php if (in_array('staff_id_card', $displayFields)): ?>
                                    <div class="meta-row"><span class="label">ID NO:</span><span class="value"><?php echo \App\Helpers\Security::escape($staffId ?: 'N/A'); ?></span></div>
                                <?php endif; ?>
                                <?php if (in_array('designation_title', $displayFields)): ?>
                                    <div class="meta-row"><span class="label">JOB TITLE:</span><span class="value"><?php echo \App\Helpers\Security::escape($staffDesig ?: 'N/A'); ?></span></div>
                                <?php endif; ?>
                                <?php if (in_array('dept_name', $displayFields)): ?>
                                    <div class="meta-row"><span class="label">DEPARTMENT:</span><span class="value"><?php echo \App\Helpers\Security::escape($staffDept ?: 'N/A'); ?></span></div>
                                <?php endif; ?>
                                <?php if (in_array('date_joined', $displayFields)): ?>
                                    <div class="meta-row"><span class="label">ISSUE DATE:</span><span class="value"><?php echo \App\Helpers\Security::escape($staffJoined ?: 'N/A'); ?></span></div>
                                <?php endif; ?>
                                <?php if (in_array('employment_status', $displayFields)): ?>
                                    <div class="meta-row"><span class="label">STATUS:</span><span class="value"><?php echo \App\Helpers\Security::escape($staffStatus ?: 'N/A'); ?></span></div>
                                <?php endif; ?>
                                <?php if (in_array('phone_one', $displayFields)): ?>
                                    <div class="meta-row"><span class="label">PHONE:</span><span class="value"><?php echo \App\Helpers\Security::escape($staffPhone ?: 'N/A'); ?></span></div>
                                <?php endif; ?>
                                <?php if (in_array('email', $displayFields)): ?>
                                    <div class="meta-row"><span class="label">EMAIL:</span><span class="value"><?php echo \App\Helpers\Security::escape($staffEmail ?: 'N/A'); ?></span></div>
                                <?php endif; ?>
                                <?php if (in_array('gender', $displayFields)): ?>
                                    <div class="meta-row"><span class="label">GENDER:</span><span class="value"><?php echo \App\Helpers\Security::escape($staffGender ?: 'N/A'); ?></span></div>
                                <?php endif; ?>
                                <?php if (in_array('date_of_birth', $displayFields)): ?>
                                    <div class="meta-row"><span class="label">DOB:</span><span class="value"><?php echo \App\Helpers\Security::escape($staffDob ?: 'N/A'); ?></span></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <footer class="card-footer-front">
                            <span class="auth-text">AUTHORIZED STAFF</span>
                            <div class="hologram-seal"></div>
                        </footer>
                    </div>
                </div>

                <!-- BACK -->
                <div class="card-side card-back">
                    <div class="lanyard-hole-zone"></div>

                    <div class="card-content">
                        <div class="back-logo-area">
                            <img src="<?php echo $logoDisplay; ?>" alt="" class="back-logo-img">
                        </div>

                        <div class="ownership-info">
                            <h3>PROPERTY OF <?php echo strtoupper($backC1); ?><?php if (!empty($backC2)): ?> <?php echo strtoupper($backC2); ?><?php endif; ?></h3>
                            <p>IF FOUND, PLEASE RETURN TO:</p>
                            <p>HEAD OFFICE, <?php echo $companyCityVal; ?>, GHANA</p>
                            <p class="phone-text">TEL: <?php echo $companyTellVal; ?></p>
                        </div>

                        <div class="verification-zone">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?php echo $qrUrl; ?>&margin=4" alt="" class="qr-code">
                            <p class="qr-caption">EMPLOYEE VERIFICATION QR<br>ISSUED BY <?php echo strtoupper($backC1); ?><?php if (!empty($backC2)): ?> <?php echo strtoupper($backC2); ?><?php endif; ?>, <?php echo $companyCityVal; ?></p>
                        </div>

                        <div class="security-assets">
                            <svg id="barcodeSvg"></svg>
                            <div class="magnetic-strip"></div>
                        </div>

                        <footer class="card-footer-back">
                            <span class="emergency-heading">EMERGENCY CONTACTS:</span>
                            <span class="emergency-number"><?php echo $emergency1; ?> / <?php echo $emergency2; ?></span>
                        </footer>
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
    --card-width: 320px;
    --card-height: 520px;
    --slot-clearance: 38px;
}

body { background: #f0f2f5; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif; }

.id-card-container {
    width: var(--card-width);
    height: var(--card-height);
    perspective: 1000px;
    margin: 0 auto;
}

.id-card {
    width: 100%;
    height: 100%;
    position: relative;
    transform-style: preserve-3d;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    border-radius: 16px;
}
.id-card-container:hover .id-card,
.id-card.flipped {
    transform: rotateY(180deg);
}

.card-side {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    backface-visibility: hidden;
    border-radius: 16px;
    overflow: hidden;
    background: linear-gradient(180deg, var(--card-bg-start) 0%, var(--card-bg-end) 100%);
    border: 3px solid rgba(15, 30, 54, 0.15);
    box-sizing: border-box;
}
.card-back {
    transform: rotateY(180deg);
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
    padding: var(--slot-clearance) 20px 0 20px;
    box-sizing: border-box;
    text-transform: uppercase;
}

/* Header */
.card-header {
    text-align: center;
    width: 100%;
    margin-top: 8px;
    margin-bottom: 12px;
}
.company-name {
    color: var(--primary-navy);
    font-size: 1.2rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.5px;
    line-height: 1.2;
}
.company-tagline {
    color: var(--text-dark);
    font-size: 0.65rem;
    font-weight: 600;
    margin: 4px 0 0 0;
    letter-spacing: 2px;
    opacity: 0.8;
}

/* Photo */
.photo-container {
    width: 120px;
    height: 145px;
    border: 1px solid rgba(15, 30, 54, 0.2);
    border-radius: 6px;
    overflow: hidden;
    background: #f8fafc;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    margin-bottom: 10px;
}
.staff-photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.photo-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: 700;
    color: #fff;
    background: <?php echo $secondaryColor; ?>;
}

/* Details */
.employee-details {
    width: 100%;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    padding: 0 10px;
    box-sizing: border-box;
}
.employee-name {
    color: var(--primary-navy);
    font-size: 1.1rem;
    font-weight: 800;
    text-align: center;
    margin: 0 0 10px 0;
    letter-spacing: -0.2px;
}
.meta-grid {
    display: flex;
    flex-direction: column;
    gap: 5px;
    font-size: 0.72rem;
}
.meta-row {
    display: flex;
    justify-content: flex-start;
}
.meta-row .label {
    color: var(--primary-navy);
    font-weight: 700;
    width: 95px;
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
    height: 48px;
    background: var(--silver-gradient);
    display: flex;
    justify-content: center;
    align-items: center;
    box-sizing: border-box;
}
.auth-text {
    color: var(--primary-navy);
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 1.5px;
}
.hologram-seal {
    position: absolute;
    right: 16px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: radial-gradient(circle, #67e8f9 0%, #a855f7 50%, #e2e8f0 100%);
    opacity: 0.7;
    box-shadow: inset 0 0 4px rgba(255,255,255,0.8);
}

/* ---- Back ---- */
.back-logo-area {
    margin-top: 18px;
    margin-bottom: 8px;
    text-align: center;
}
.back-logo-img {
    max-width: 64px;
    max-height: 64px;
}

.ownership-info {
    text-align: center;
    font-size: 0.75rem;
    color: var(--text-dark);
    line-height: 1.35;
    padding: 0 6px;
}
.ownership-info h3 {
    color: var(--primary-navy);
    font-size: 0.85rem;
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
    margin: 6px 0;
}
.qr-code {
    width: 80px;
    height: 80px;
    padding: 4px;
    background: #fff;
    border-radius: 6px;
    border: 1px solid rgba(0,0,0,0.05);
}
.qr-caption {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--text-dark);
    text-align: center;
    margin: 6px 0 0 0;
    line-height: 1.3;
    opacity: 0.8;
}

.security-assets {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    margin-bottom: 0;
}
#barcodeSvg {
    width: 100%;
    height: 36px;
}
.magnetic-strip {
    align-self: stretch;
    margin: 0 -20px;
    height: 28px;
    background: #1E293B;
}

/* Back Footer */
.card-footer-back {
    align-self: stretch;
    margin: 0 -20px;
    background: var(--footer-purple);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    box-sizing: border-box;
    flex-shrink: 0;
    padding: 10px 20px;
    min-height: 52px;
}
.emergency-heading {
    color: var(--primary-navy);
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.5px;
}
.emergency-number {
    color: var(--text-dark);
    font-size: 0.8rem;
    font-weight: 600;
}

/* Print */
@media print {
    .no-print { display: none !important; }
    body { background: #fff; padding: 0; margin: 0; }
    .id-card-container { box-shadow: none; }
    .id-card { box-shadow: none; }
    .card-side { border: 3px solid #000 !important; box-shadow: none; }
    .id-card-container:hover .id-card { transform: none; }
    .id-card.flipped { transform: rotateY(180deg); }
    @page { margin: 0.5in; size: portrait; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
function toggleFlip() {
    document.getElementById('idCard').classList.toggle('flipped');
}
document.addEventListener('DOMContentLoaded', function() {
    var data = '<?php echo \App\Helpers\Security::escape($staffId ?: 'N/A'); ?>';
    JsBarcode('#barcodeSvg', data, {
        format: 'CODE128',
        width: 1.5,
        height: 32,
        displayValue: true,
        fontSize: 10,
        margin: 2
    });
});
</script>
