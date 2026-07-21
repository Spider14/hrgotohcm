<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();

function d($v, $def = '—'): string {
    return !empty($v) && trim((string)$v) !== '' ? \App\Helpers\Security::escape((string)$v) : $def;
}

function imgUrl($path): string {
    if (empty($path)) return '';
    $base = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
    $p = \App\Helpers\Security::escape((string)$path);
    return str_starts_with($p, 'http') ? $p : $base . '/' . ltrim($p, '/');
}
?>
<style>
.dossier-section { border: none; border-radius: 14px; overflow: hidden; transition: box-shadow .2s; }
.dossier-section:hover { box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.08) !important; }
.dossier-section .card-header { background: linear-gradient(135deg, #f8f9fc, #eef1f8); border-bottom: 1px solid #e9ecef; padding: 1rem 1.5rem; }
.dossier-section .card-body { padding: 1.5rem; }
.field-label { font-size: .73rem; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; margin-bottom: .15rem; }
.field-value { font-size: .95rem; font-weight: 600; color: #1e293b; }
.avatar-frame { width: 110px; height: 110px; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 2px 12px rgba(0,0,0,.12); display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea, #764ba2); font-size: 2.4rem; font-weight: 700; color: #fff; overflow: hidden; margin: 0 auto; }
.avatar-frame img { width: 100%; height: 100%; object-fit: cover; }
.info-badge { display: inline-flex; align-items: center; gap: .35rem; background: #f1f5f9; color: #475569; padding: .2rem .7rem; border-radius: 50px; font-size: .8rem; font-weight: 500; }
.file-card { border: 1px solid #e9ecef; border-radius: 10px; transition: all .15s; }
.file-card:hover { border-color: #c7d2fe; background: #f8f9ff; }
.file-card .file-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #fff; }
.timeline-dot { width: 10px; height: 10px; border-radius: 50%; background: #6366f1; flex-shrink: 0; margin-top: 5px; }
.timeline-line { width: 2px; background: #e2e8f0; flex-shrink: 0; margin-left: 4px; }
</style>

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold"><i class="far fa-folder-open me-2 text-primary"></i>My Full Dossier</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1100px;">

        <!-- ===== HEADER CARD ===== -->
        <div class="card dossier-section shadow-sm mb-4">
            <div class="card-body text-center py-4" style="background: linear-gradient(180deg, #f0f4ff 0%, #fff 60%);">
                <div class="avatar-frame">
                    <img style="display:<?php echo empty($profile['avatar_url']) ? 'none' : 'block'; ?>;" src="<?php echo imgUrl($profile['avatar_url'] ?? ''); ?>">
                    <span style="display:<?php echo empty($profile['avatar_url']) ? 'inline' : 'none'; ?>;"><?php echo strtoupper(substr($profile['fullname'] ?? 'U', 0, 2)); ?></span>
                </div>
                <h4 class="mt-3 mb-1 fw-bold"><?php echo d($profile['fullname'] ?? ''); ?></h4>
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-2">
                    <span class="info-badge"><i class="fas fa-id-card text-primary"></i> <?php echo d($profile['staff_id_card'] ?? ''); ?></span>
                    <span class="info-badge"><i class="fas fa-briefcase text-success"></i> <?php echo d($profile['designation'] ?? ''); ?></span>
                    <span class="info-badge"><i class="fas fa-building text-info"></i> <?php echo d($profile['dept_name'] ?? ''); ?></span>
                    <span class="info-badge"><i class="fas fa-calendar-alt text-warning"></i> Joined <?php echo d($profile['date_joined'] ?? ''); ?></span>
                </div>
            </div>
        </div>

        <!-- ===== 1. USER ACCOUNT ===== -->
        <div class="card dossier-section shadow-sm mb-4">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-user-circle me-2 text-primary"></i>User Account</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="field-label"><i class="fas fa-tag fa-fw me-1"></i>Username</div>
                        <div class="field-value"><?php echo d($profile['username'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-label"><i class="fas fa-envelope fa-fw me-1"></i>Email Address</div>
                        <div class="field-value"><a href="mailto:<?php echo d($profile['email'] ?? ''); ?>" class="text-decoration-none"><?php echo d($profile['email'] ?? ''); ?></a></div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-label"><i class="fas fa-clock fa-fw me-1"></i>Account Created</div>
                        <div class="field-value"><?php echo d($profile['account_created'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-label"><i class="fas fa-id-card fa-fw me-1"></i>Staff ID Card</div>
                        <div class="field-value"><?php echo d($profile['staff_id_card'] ?? ''); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== 2. PERSONAL INFORMATION ===== -->
        <div class="card dossier-section shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="fas fa-address-card me-2 text-success"></i>Personal Information</h6>
            </div>
            <div class="card-body">

                <!-- Personal Details -->
                <h6 class="text-muted text-uppercase small fw-bold mb-3" style="letter-spacing:.06em;"><i class="fas fa-user me-1"></i>Personal Details</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="field-label">Gender</div>
                        <div class="field-value"><?php echo d(ucfirst($profile['gender'] ?? '')); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="field-label">Date of Birth</div>
                        <div class="field-value"><?php echo d($profile['date_of_birth'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="field-label">Marital Status</div>
                        <div class="field-value"><?php echo d(ucfirst($profile['marital_status'] ?? '')); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="field-label">Children</div>
                        <div class="field-value"><?php echo (int)($profile['number_of_children'] ?? 0); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-label">Nationality</div>
                        <div class="field-value"><?php echo d($profile['nationality'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-label">Religion</div>
                        <div class="field-value"><?php echo d($profile['religion'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-label">Ghana Card Number</div>
                        <div class="field-value"><?php echo d($profile['ghana_card_number'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="field-label">Hometown</div>
                        <div class="field-value"><?php echo d($profile['hometown'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="field-label">Region</div>
                        <div class="field-value"><?php echo d($profile['region'] ?? ''); ?></div>
                    </div>
                    <?php if (!empty($profile['biography'])): ?>
                    <div class="col-12">
                        <div class="field-label">Biography</div>
                        <div class="field-value"><?php echo nl2br(d($profile['biography'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <hr class="my-3">

                <!-- Contact Information -->
                <h6 class="text-muted text-uppercase small fw-bold mb-3" style="letter-spacing:.06em;"><i class="fas fa-phone-alt me-1"></i>Contact Information</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="field-label"><i class="fas fa-phone fa-fw me-1"></i>Phone (Primary)</div>
                        <div class="field-value"><a href="tel:<?php echo d($profile['phone_one'] ?? ''); ?>" class="text-decoration-none"><?php echo d($profile['phone_one'] ?? ''); ?></a></div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-label"><i class="fas fa-phone-alt fa-fw me-1"></i>Phone (Alternate)</div>
                        <div class="field-value"><?php echo d($profile['phone_two'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-label"><i class="fas fa-envelope fa-fw me-1"></i>Email</div>
                        <div class="field-value"><?php echo d($profile['email'] ?? ''); ?></div>
                    </div>
                    <div class="col-12">
                        <div class="field-label"><i class="fas fa-home fa-fw me-1"></i>Residential Address</div>
                        <div class="field-value"><?php echo d($profile['residential_address'] ?? ''); ?></div>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Employment Details -->
                <h6 class="text-muted text-uppercase small fw-bold mb-3" style="letter-spacing:.06em;"><i class="fas fa-building me-1"></i>Employment Details</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="field-label">Department</div>
                        <div class="field-value"><?php echo d($profile['dept_name'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-label">Designation</div>
                        <div class="field-value"><?php echo d($profile['designation'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-label">Employment Status</div>
                        <div class="field-value">
                            <?php
                            $status = $profile['employment_status'] ?? '';
                            $badge = match (strtolower($status)) {
                                'active' => 'bg-success',
                                'probation' => 'bg-warning text-dark',
                                'suspended' => 'bg-danger',
                                'terminated' => 'bg-secondary',
                                'resigned' => 'bg-dark',
                                default => 'bg-info'
                            };
                            ?>
                            <span class="badge <?php echo $badge; ?> rounded-pill"><?php echo d(ucfirst($status)); ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-label">Date Joined</div>
                        <div class="field-value"><?php echo d($profile['date_joined'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-label">Leave Entitlement</div>
                        <div class="field-value"><?php echo (int)($profile['leave_entitlement'] ?? 0); ?> days/year</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== 3. EDUCATION ===== -->
        <div class="card dossier-section shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="fas fa-graduation-cap me-2 text-warning"></i>Academic Qualifications</h6>
                <?php if (!empty($education)): ?><span class="badge bg-warning text-dark rounded-pill"><?php echo count($education); ?> record(s)</span><?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($education)): ?>
                    <p class="text-muted mb-0"><i class="far fa-frown me-1"></i>No academic qualifications recorded.</p>
                <?php else: ?>
                    <?php foreach ($education as $edu): ?>
                    <div class="d-flex align-items-start gap-3 mb-3 pb-3 border-bottom border-light">
                        <div class="file-icon rounded-circle" style="background: linear-gradient(135deg,#f59e0b,#d97706);min-width:44px;"><i class="fas fa-graduation-cap"></i></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold"><?php echo d($edu['institution'] ?? ''); ?></div>
                            <div class="text-muted small"><?php echo d($edu['certificate'] ?? ''); ?></div>
                            <div class="text-muted small mt-1">
                                <i class="far fa-calendar-alt me-1"></i><?php echo d((string)($edu['year_from'] ?? '')); ?> — <?php echo d((string)($edu['year_to'] ?? '')); ?>
                                <?php if (!empty($edu['dossier_url'])): ?>
                                    <a href="<?php echo imgUrl($edu['dossier_url']); ?>" target="_blank" class="ms-3 text-decoration-none"><i class="fas fa-paperclip me-1"></i>Attachment</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== 4. WORK EXPERIENCE ===== -->
        <div class="card dossier-section shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="fas fa-briefcase me-2 text-info"></i>Work Experience</h6>
                <?php if (!empty($experience)): ?><span class="badge bg-info rounded-pill"><?php echo count($experience); ?> record(s)</span><?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($experience)): ?>
                    <p class="text-muted mb-0"><i class="far fa-frown me-1"></i>No work experience recorded.</p>
                <?php else: ?>
                    <div class="position-relative">
                        <?php foreach ($experience as $i => $exp): ?>
                        <div class="d-flex gap-3 <?php echo $i < count($experience) - 1 ? 'mb-3' : ''; ?>">
                            <div class="d-flex flex-column align-items-center" style="width:14px;">
                                <div class="timeline-dot"></div>
                                <?php if ($i < count($experience) - 1): ?><div class="flex-grow-1 timeline-line" style="min-height:24px;"></div><?php endif; ?>
                            </div>
                            <div class="flex-grow-1 pb-3">
                                <div class="fw-bold"><?php echo d($exp['job_title'] ?? ''); ?></div>
                                <div class="text-muted">
                                    <i class="fas fa-building me-1"></i><?php echo d($exp['company_name'] ?? ''); ?>
                                    <span class="mx-2">&middot;</span>
                                    <i class="far fa-calendar-alt me-1"></i><?php echo d((string)($exp['year_from'] ?? '')); ?> — <?php echo d((string)($exp['year_to'] ?? __('Present'))); ?>
                                </div>
                                <?php if (!empty($exp['responsibilities'])): ?>
                                <div class="small text-muted mt-1"><?php echo nl2br(d($exp['responsibilities'])); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== 5. FILES & DOCUMENTS ===== -->
        <div class="card dossier-section shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="fas fa-paperclip me-2 text-danger"></i>Files &amp; Documents</h6>
                <?php if (!empty($files)): ?><span class="badge bg-danger rounded-pill"><?php echo count($files); ?> file(s)</span><?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($files)): ?>
                    <p class="text-muted mb-0"><i class="far fa-folder-open me-1"></i>No attached documents.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($files as $f): ?>
                        <div class="col-md-6">
                            <a href="<?php echo imgUrl($f['url']); ?>" target="_blank" class="text-decoration-none text-reset">
                                <div class="file-card p-3 d-flex align-items-center gap-3">
                                    <?php
                                    $icon = 'fas fa-file';
                                    $bg = 'bg-secondary';
                                    $cat = $f['category'] ?? '';
                                    if (str_contains($cat, 'Education')) { $icon = 'fas fa-file-pdf'; $bg = 'bg-danger'; }
                                    elseif (str_contains($cat, 'Identification')) { $icon = 'fas fa-id-card'; $bg = 'bg-primary'; }
                                    elseif (str_contains($cat, 'Photo')) { $icon = 'fas fa-user'; $bg = 'bg-success'; }
                                    ?>
                                    <div class="file-icon <?php echo $bg; ?>"><i class="<?php echo $icon; ?>"></i></div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-semibold small text-truncate"><?php echo d($f['name']); ?></div>
                                        <div class="text-muted small"><?php echo d($f['category']); ?> &middot; <i class="fas fa-external-link-alt"></i> View</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== PRINT BUTTON ===== -->
        <div class="d-flex justify-content-end gap-2 mt-4 mb-4">
            <button class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print me-1"></i> Print Dossier</button>
        </div>
    </div>
</div>