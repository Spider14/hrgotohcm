<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();

$companyName = !empty($config['company_name']) ? $config['company_name'] : '';
$companyShortName = !empty($config['company_short_name']) ? $config['company_short_name'] : '';
$companyAddress = !empty($config['company_address']) ? $config['company_address'] : '';
$companyPhone = !empty($config['company_phone']) ? $config['company_phone'] : '';
$companyEmail = !empty($config['company_email']) ? $config['company_email'] : '';
$companyWebsite = !empty($config['company_website']) ? $config['company_website'] : '';
$companyLogoUrl = !empty($config['company_logo_url']) ? $config['company_logo_url'] : '';
$companyRegion = !empty($config['company_region']) ? $config['company_region'] : '';
$companyCity = !empty($config['company_city']) ? $config['company_city'] : '';
$companyTell = !empty($config['company_tell']) ? $config['company_tell'] : '';
$companyTagline = !empty($config['company_tagline']) ? $config['company_tagline'] : '';
$smtpHost = !empty($config['smtp_host']) ? $config['smtp_host'] : '';
$smtpPort = !empty($config['smtp_port']) ? $config['smtp_port'] : '';
$smtpUsername = !empty($config['smtp_username']) ? $config['smtp_username'] : '';
$smtpPassword = !empty($config['smtp_password']) ? '********' : '';
$smtpEncryption = !empty($config['smtp_encryption']) ? $config['smtp_encryption'] : 'tls';
$smtpFromEmail = !empty($config['smtp_from_email']) ? $config['smtp_from_email'] : '';
$smtpFromName = !empty($config['smtp_from_name']) ? $config['smtp_from_name'] : '';
?>

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Company Profile</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <?php require __DIR__ . '/../dashboard/layouts/flash.php'; ?>

        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-3">Company Information</h5>
            <p class="text-muted small mb-4">Configure your organization details. These are used across the system including on ID cards and reports.</p>

            <form method="POST" action="<?php echo $appUrl; ?>/admin/company-profile" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

                <div class="row g-4">
                    <div class="col-12 col-md-8">
                        <div class="border rounded p-4 bg-light">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Company Name</label>
                                    <input type="text" name="company_name" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($companyName); ?>" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">Short Name</label>
                                    <input type="text" name="company_short_name" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($companyShortName); ?>" placeholder="e.g. BCB PLC">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Address</label>
                                    <textarea name="company_address" class="form-control form-control-sm" rows="2"><?php echo \App\Helpers\Security::escape($companyAddress); ?></textarea>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">Phone</label>
                                    <input type="text" name="company_phone" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($companyPhone); ?>">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">Email</label>
                                    <input type="email" name="company_email" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($companyEmail); ?>">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">Website</label>
                                    <input type="url" name="company_website" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($companyWebsite); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Tagline</label>
                                    <input type="text" name="company_tagline" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($companyTagline); ?>" placeholder="e.g. Empowering Excellence, Driving Growth">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-semibold">Region</label>
                                    <input type="text" name="company_region" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($companyRegion); ?>" placeholder="e.g. Greater Accra">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-semibold">City</label>
                                    <input type="text" name="company_city" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($companyCity); ?>" placeholder="e.g. Accra">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-semibold">Tel (Landline)</label>
                                    <input type="text" name="company_tell" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($companyTell); ?>" placeholder="e.g. +233 (0) 302 000 000">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="border rounded p-4 bg-light">
                            <h6 class="fw-semibold mb-3">Company Logo</h6>
                            <div class="mb-3 text-center" id="logoContainer">
                                <?php if (!empty($companyLogoUrl)): ?>
                                    <img src="<?php echo \App\Helpers\Security::escape($companyLogoUrl); ?>?v=<?php echo time(); ?>" alt="Current Logo" style="max-height:80px;max-width:100%;margin-bottom:10px;" id="logoPreview">
                                <?php else: ?>
                                    <div class="border rounded bg-white d-flex align-items-center justify-content-center mx-auto mb-2" style="width:120px;height:80px;" id="logoPlaceholder">
                                        <i class="fas fa-image text-muted fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <label class="form-label small fw-semibold">Upload Logo</label>
                            <input type="file" name="company_logo" class="form-control form-control-sm" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml" onchange="var c=document.getElementById('logoContainer');var f=this.files[0];if(f){var r=new FileReader();r.onload=function(e){var i=new Image();i.src=e.target.result;i.style.maxHeight='80px';i.style.maxWidth='100%';i.style.marginBottom='10px';c.replaceChildren(i)};r.readAsDataURL(f)}">
                            <small class="text-muted d-block mt-1">Accepted: PNG, JPG, GIF, WebP, SVG. Used on ID cards, reports, and system branding.</small>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-3">
                    <div class="col-12">
                        <div class="border rounded p-4 bg-light">
                            <h6 class="fw-semibold mb-3"><i class="fas fa-envelope me-2"></i>SMTP Settings</h6>
                            <p class="text-muted small mb-3">Configure email server settings for system emails (password resets, notifications).</p>
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">SMTP Host</label>
                                    <input type="text" name="smtp_host" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($smtpHost); ?>" placeholder="smtp.gmail.com">
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small fw-semibold">Port</label>
                                    <input type="number" name="smtp_port" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($smtpPort); ?>" placeholder="587">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small fw-semibold">Encryption</label>
                                    <select name="smtp_encryption" class="form-select form-select-sm">
                                        <option value="tls"<?php echo $smtpEncryption === 'tls' ? ' selected' : ''; ?>>TLS</option>
                                        <option value="ssl"<?php echo $smtpEncryption === 'ssl' ? ' selected' : ''; ?>>SSL</option>
                                        <option value="none"<?php echo $smtpEncryption === 'none' ? ' selected' : ''; ?>>None</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-semibold">Username</label>
                                    <input type="text" name="smtp_username" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($smtpUsername); ?>" placeholder="your@email.com" autocomplete="off">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-semibold">Password</label>
                                    <input type="password" name="smtp_password" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($smtpPassword); ?>" placeholder="SMTP password" autocomplete="new-password">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">From Email</label>
                                    <input type="email" name="smtp_from_email" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($smtpFromEmail); ?>" placeholder="noreply@example.com">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">From Name</label>
                                    <input type="text" name="smtp_from_name" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($smtpFromName); ?>" placeholder="HRGoTo HCM">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-3">
                    <div class="col-12">
                        <div class="border rounded p-4 bg-light">
                            <h6 class="fw-semibold mb-3"><i class="fas fa-map-pin me-2"></i>Office Location & Attendance Security</h6>
                            <div class="mb-3">
                                <p class="text-muted small mb-1"><i class="fas fa-circle me-2" style="font-size:0.4rem;vertical-align:middle;"></i>The information gathered below is used to ensure that staff clock-in only at work</p>
                                <p class="text-muted small mb-1"><i class="fas fa-circle me-2" style="font-size:0.4rem;vertical-align:middle;"></i>Kindly do this from your company location</p>
                                <p class="text-muted small mb-0"><i class="fas fa-circle me-2" style="font-size:0.4rem;vertical-align:middle;"></i>Make sure that you are on the company WiFi and not on your mobile data or hotspot</p>
                            </div>
                            <div class="row g-3">
                                <div class="col-12 col-md-3">
                                    <label class="form-label small fw-semibold">Latitude</label>
                                    <div class="input-group">
                                        <input type="text" name="office_latitude" id="officeLat" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($config['office_latitude'] ?? ''); ?>" readonly>
                                        <button type="button" id="getLocationBtn" class="btn btn-outline-primary btn-sm"><i class="fas fa-crosshairs"></i></button>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small fw-semibold">Longitude</label>
                                    <div class="input-group">
                                        <input type="text" name="office_longitude" id="officeLng" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($config['office_longitude'] ?? ''); ?>" readonly>
                                        <button type="button" id="getLocationBtn2" class="btn btn-outline-primary btn-sm"><i class="fas fa-crosshairs"></i></button>
                                    </div>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small fw-semibold">Radius (m)</label>
                                    <input type="number" name="office_radius_meters" class="form-control form-control-sm" value="<?php echo (int)($config['office_radius_meters'] ?? 200); ?>" min="50" max="1000">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">Allowed IPs</label>
                                    <input type="text" name="office_ip_whitelist" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($config['office_ip_whitelist'] ?? ''); ?>" placeholder="Comma-separated, e.g. 197.251.10.20,197.251.10.21">
                                </div>
                                <div class="col-12">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Make sure you are doing this from the office and that you are not on mobile data or hotspot.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-dark btn-sm px-4">Save Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    function fetchLocation(btnId, latId, lngId) {
        $(btnId).on('click', function() {
            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        $(latId).val(pos.coords.latitude.toFixed(7));
                        $(lngId).val(pos.coords.longitude.toFixed(7));
                        $btn.prop('disabled', false).html('<i class="fas fa-crosshairs"></i>');
                    },
                    function(err) {
                        alert('Could not get location: ' + err.message + '. Make sure you are at the office and not on mobile data.');
                        $btn.prop('disabled', false).html('<i class="fas fa-crosshairs"></i>');
                    },
                    { enableHighAccuracy: true, timeout: 15000 }
                );
            } else {
                alert('Geolocation is not supported by your browser.');
                $btn.prop('disabled', false).html('<i class="fas fa-crosshairs"></i>');
            }
        });
    }
    fetchLocation('#getLocationBtn', '#officeLat', '#officeLng');
    fetchLocation('#getLocationBtn2', '#officeLat', '#officeLng');
});
</script>
