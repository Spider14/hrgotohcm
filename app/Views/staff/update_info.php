<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Update Personal Info</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 800px;">
        <div class="card border-0 shadow-sm p-4">
            <div class="text-center mb-4">
                <div id="avatarWrapper" class="rounded-circle border overflow-hidden" style="width:100px;height:100px;margin:0 auto;display:flex;align-items:center;justify-content:center;background:#6c757d;font-size:2rem;font-weight:700;color:#fff;">
                    <img id="avatarPreview" style="width:100%;height:100%;object-fit:cover;display:<?php echo !empty($profile['avatar_url']) ? 'block' : 'none'; ?>;" src="<?php echo !empty($profile['avatar_url']) ? $appUrl . \App\Helpers\Security::escape((string)$profile['avatar_url']) : ''; ?>">
                    <span id="avatarInitials" style="display:<?php echo !empty($profile['avatar_url']) ? 'none' : 'inline'; ?>;"><?php echo strtoupper(substr($profile['fullname'] ?? 'U', 0, 2)); ?></span>
                </div>
                <h5 class="mt-2 mb-0 fw-bold"><?php echo \App\Helpers\Security::escape($profile['fullname'] ?? ''); ?></h5>
                <small class="text-muted"><?php echo \App\Helpers\Security::escape($profile['staff_id_card'] ?? ''); ?></small>
            </div>

            <form method="POST" action="<?php echo $appUrl; ?>/staff/update-info" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

                <div class="col-12 col-md-6">
                    <label class="form-label">Phone (Primary)</label>
                    <input type="text" name="phone_one" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($profile['phone_one'] ?? ''); ?>" placeholder="+233 XX XXX XXXX">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Phone (Alternate)</label>
                    <input type="text" name="phone_two" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($profile['phone_two'] ?? ''); ?>" placeholder="+233 XX XXX XXXX">
                </div>
                <div class="col-12">
                    <label class="form-label">Residential Address</label>
                    <textarea name="residential_address" class="form-control form-control-sm" rows="2" placeholder="Street, City, Region"><?php echo \App\Helpers\Security::escape($profile['residential_address'] ?? ''); ?></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Marital Status</label>
                    <select name="marital_status" class="form-select form-select-sm">
                        <option value="single" <?php echo (($profile['marital_status'] ?? '') === 'single') ? 'selected' : ''; ?>>Single</option>
                        <option value="married" <?php echo (($profile['marital_status'] ?? '') === 'married') ? 'selected' : ''; ?>>Married</option>
                        <option value="divorced" <?php echo (($profile['marital_status'] ?? '') === 'divorced') ? 'selected' : ''; ?>>Divorced</option>
                        <option value="widowed" <?php echo (($profile['marital_status'] ?? '') === 'widowed') ? 'selected' : ''; ?>>Widowed</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Passport Photo</label>
                    <input type="file" name="avatar_photo" id="avatarPhotoInput" class="form-control form-control-sm" accept="image/jpeg,image/png" capture="user">
                    <div class="d-flex gap-2 mt-1">
                        <small class="text-muted">JPG or PNG. Max 10MB.</small>
                        <button type="button" id="cameraBtn" class="btn btn-outline-secondary btn-sm ms-auto"><i class="fas fa-camera me-1"></i>Take Photo</button>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Ghana Card Number</label>
                    <input type="text" name="ghana_card_number" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($profile['ghana_card_number'] ?? ''); ?>" placeholder="GHA-XXXXXXXXX-XXXXX">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Ghana Card Photo</label>
                    <input type="file" name="ghana_card_photo" class="form-control form-control-sm" accept="image/jpeg,image/png">
                    <small class="text-muted">JPG or PNG. Max 2MB.</small>
                    <?php if (!empty($profile['ghana_card_photo'])): ?>
                        <div class="mt-1"><a href="<?php echo $appUrl . \App\Helpers\Security::escape((string)$profile['ghana_card_photo']); ?>" target="_blank" class="small">View uploaded card photo</a></div>
                    <?php endif; ?>
                </div>
                <div class="col-12 d-grid d-md-flex justify-content-md-end gap-2">
                    <a href="<?php echo $appUrl; ?>/staff/dossier" class="btn btn-outline-info px-4"><i class="fas fa-file-alt me-1"></i>View Full Dossier</a>
                    <button class="btn btn-primary px-4" type="submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="cameraModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:9999;flex-direction:column;align-items:center;justify-content:center;">
    <video id="cameraView" autoplay playsinline style="max-width:100%;max-height:70vh;border-radius:8px;"></video>
    <div style="display:flex;gap:12px;margin-top:16px;">
        <button type="button" id="captureBtn" class="btn btn-light btn-lg rounded-circle" style="width:60px;height:60px;"><i class="fas fa-camera"></i></button>
        <button type="button" id="closeCameraBtn" class="btn btn-danger btn-lg rounded-circle" style="width:60px;height:60px;"><i class="fas fa-times"></i></button>
    </div>
    <canvas id="cameraCanvas" style="display:none;"></canvas>
</div>

<script>
document.getElementById('cameraBtn')?.addEventListener('click', function() {
    const modal = document.getElementById('cameraModal');
    const video = document.getElementById('cameraView');
    modal.style.display = 'flex';
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } } })
        .then(function(stream) {
            video.srcObject = stream;
            video._stream = stream;
        })
        .catch(function(err) {
            alert('Camera access denied or unavailable: ' + err.message);
            modal.style.display = 'none';
        });
});

document.getElementById('captureBtn')?.addEventListener('click', function() {
    const video = document.getElementById('cameraView');
    const canvas = document.getElementById('cameraCanvas');
    const modal = document.getElementById('cameraModal');
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    canvas.toBlob(function(blob) {
        const file = new File([blob], 'selfie_' + Date.now() + '.jpg', { type: 'image/jpeg' });
        const dt = new DataTransfer();
        dt.items.add(file);
        const input = document.getElementById('avatarPhotoInput');
        input.files = dt.files;
        input.dispatchEvent(new Event('change'));
        // Stop camera
        if (video._stream) { video._stream.getTracks().forEach(t => t.stop()); video.srcObject = null; }
        modal.style.display = 'none';
    }, 'image/jpeg', 0.85);
});

document.getElementById('closeCameraBtn')?.addEventListener('click', function() {
    const modal = document.getElementById('cameraModal');
    const video = document.getElementById('cameraView');
    if (video._stream) { video._stream.getTracks().forEach(t => t.stop()); video.srcObject = null; }
    modal.style.display = 'none';
});

document.querySelector('input[name="avatar_photo"]')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(ev) {
        document.getElementById('avatarPreview').src = ev.target.result;
        document.getElementById('avatarPreview').style.display = 'block';
        document.getElementById('avatarInitials').style.display = 'none';
    };
    reader.readAsDataURL(file);
});
</script>
