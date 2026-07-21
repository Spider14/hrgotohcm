<?php
// =========================================================================
// DATA UNPACKING & INITIALIZATION LAYER
// =========================================================================
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');

// Extract records cleanly from the controller array matrix
$staff      = $profileData['staff'] ?? [];
$education  = $profileData['education'] ?? [];
$experience = $profileData['experience'] ?? [];
$promotions = $profileData['promotions'] ?? [];

$departments   = $profileData['departments'] ?? [];
$supervisors   = $profileData['supervisors'] ?? [];
$roles         = $profileData['roles'] ?? [];
$currentRoleId = $profileData['current_role_id'] ?? 0;

// Determine user avatar image pathway cleanly inside /public/uploads/avatars/
$avatarUrl = !empty($staff['avatar_url']) ? $appUrl . $staff['avatar_url'] : null;
$avatarExists = !empty($staff['avatar_url']) && file_exists(__DIR__ . '/../../../public/uploads/avatars/' . basename($staff['avatar_url']));
?>
<style>
    /* Strict Layout Overrides to enforce high contrast on giant white backgrounds */
    #sidebar {
        background-color: #1a202c !important; /* Rich solid charcoal obsidian background */
        border-right: 2px solid #2d3748 !important;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.15) !important;
    }
    
    .profile-workstation-bg {
        background-color: #f7fafc !important; /* Soft distinct canvas backdrop */
    }

    .contrast-card {
        background-color: #ffffff !important;
        border: 1px solid #cbd5e0 !important; /* Sharp gray outline boundary mapping */
        border-radius: 8px !important;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03) !important;
    }

    .meta-data-box {
        background-color: #edf2f7 !important; /* High contrast inner gray well grouping */
        border: 1px solid #e2e8f0 !important;
        border-radius: 6px;
        padding: 12px 16px;
    }

    /* Tab Headers Structural Enhancements */
    .profile-work-tabs {
        background-color: #ebf8ff !important; /* Light blue high-contrast tab banner well */
        border: 1px solid #bee3f8 !important;
        border-radius: 6px 6px 0 0;
    }
    
    .profile-work-tabs .nav-link {
        color: #4a5568 !important; /* Deep dark slate charcoal text for extreme readability */
        font-weight: 600 !important;
        border: none !important;
        border-right: 1px solid #cbd5e0 !important;
        border-bottom: 3px solid transparent !important;
        transition: all 0.15s ease-in-out;
        padding: 12px 20px !important;
    }
    
    .profile-work-tabs .nav-link:hover {
        color: #2b6cb0 !important;
        background-color: #e2e8f0 !important;
    }
    
    .profile-work-tabs .nav-link.active {
        color: #ffffff !important;
        background-color: #2b6cb0 !important; /* Solid premium blue for active indicators */
        border-bottom-color: #2b6cb0 !important;
        font-weight: 700 !important;
    }
	.portrait-preview-container img#portrait-preview-display {
        object-fit: cover !important;
        object-position: center top !important; 
        width: 70% !important;
        height: 70% !important;
    }
</style>

<div id="content" class="w-100 profile-workstation-bg">
    
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom border-secondary-subtle px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-dark btn-sm rounded-circle me-3">
                <i class="fas fa-bars"></i>
            </button>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb m-0 bg-transparent p-0 small">
                    <li class="breadcrumb-item"><a href="/staff" class="text-decoration-none text-dark fw-bold"><i class="fas fa-users me-1"></i> Staff Profile</a></li>
                </ol>
            </nav>
            <div class="ms-auto d-flex align-items-center gap-3">
                <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <div class="row g-4">
            
            <div class="col-12 col-lg-4 col-xl-3">
                <div class="card contrast-card text-center p-4 sticky-md-top" style="top: 80px; z-index: 5;">
                    
                    <div class="position-relative d-inline-block mx-auto mb-3 portrait-preview-container">
                        <?php if ($avatarUrl && $avatarExists): ?>
                            <img id="portrait-preview-display" src="<?php echo $avatarUrl; ?>" alt="Identity Portrait" class="rounded-circle img-thumbnail shadow-sm object-fit-cover border border-dark border-2" style="width: 130px; height: 130px; object-position: center;">
                        <?php else: ?>
                            <div class="rounded-circle d-flex align-items-center justify-content-center border border-secondary shadow-sm mx-auto font-monospace text-dark fw-bold" style="width: 130px; height: 130px; font-size: 2.2rem; background: linear-gradient(135deg, #cbd5e1 0%, #94a3b8 100%);">
                                <?php echo strtoupper(substr($staff['fullname'] ?? 'ST', 0, 2)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php 
                            $statusDot = 'bg-secondary';
                            if (($staff['employment_status'] ?? '') === 'Permanent') $statusDot = 'bg-success';
                            elseif (($staff['employment_status'] ?? '') === 'Probation') $statusDot = 'bg-warning';
                            elseif (($staff['employment_status'] ?? '') === 'Suspended') $statusDot = 'bg-danger';
                        ?>
                        <span class="position-absolute bottom-0 end-0 p-2 border border-3 border-white rounded-circle <?php echo $statusDot; ?>" style="transform: translate(-5px, -5px);"></span>
                    </div>

                    <h4 class="fw-bold text-dark mb-1"><?php echo \App\Helpers\Security::escape($staff['fullname'] ?? 'N/A'); ?></h4>
                    <p class="text-secondary text-sm font-monospace mb-3 fw-bold bg-light py-1 border rounded">@<?php echo \App\Helpers\Security::escape($staff['username'] ?? 'unallocated'); ?></p>
                    
                    <span class="badge bg-dark text-white rounded px-3 py-2 text-xs font-monospace fw-bold mb-4 w-100 border">
                        <i class="fas fa-id-card-clip me-1"></i> <?php echo \App\Helpers\Security::escape($staff['staff_id_card'] ?? 'No ID Card Assigned'); ?>
                    </span>

                    <hr class="border-dark opacity-25 my-3">

                    <div class="text-start space-y-3 mt-2">
                        <div class="d-flex align-items-center mb-3 p-2 bg-light border rounded">
                            <div class="bg-dark text-white rounded p-2 me-3" style="width: 35px; text-align: center;"><i class="fas fa-sitemap"></i></div>
                            <div>
                                <small class="text-xxs text-uppercase font-monospace text-muted d-block fw-bold">Department</small>
                                <strong class="text-sm text-dark"><?php echo \App\Helpers\Security::escape($staff['dept_name'] ?? 'Unassigned Scope'); ?></strong>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3 p-2 bg-light border rounded">
                            <div class="bg-dark text-white rounded p-2 me-3" style="width: 35px; text-align: center;"><i class="fas fa-graduation-cap"></i></div>
                            <div>
                                <small class="text-xxs text-uppercase font-monospace text-muted d-block fw-bold">Designation Title</small>
                                <strong class="text-sm text-dark"><?php echo \App\Helpers\Security::escape($staff['designation_title'] ?? 'General Officer Access'); ?></strong>
                            </div>
                        </div>
                        <div class="d-flex align-items-center p-2 bg-light border rounded">
                            <div class="bg-dark text-white rounded p-2 me-3" style="width: 35px; text-align: center;"><i class="fas fa-calendar-alt"></i></div>
                            <div>
                                <small class="text-xxs text-uppercase font-monospace text-muted d-block fw-bold">Staff Since</small>
                                <strong class="text-xs text-dark font-monospace"><?php echo !empty($staff['date_joined']) ? date('d M Y', strtotime($staff['date_joined'])) : 'N/A'; ?></strong>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12 col-lg-8 col-xl-9">
                <div class="card contrast-card overflow-hidden">
                    
                    <div class="p-0 border-bottom border-secondary-subtle">
                        <ul class="nav nav-tabs border-0 profile-work-tabs text-xs font-monospace text-uppercase fw-bold" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="bio-tab" data-bs-toggle="tab" data-bs-target="#bio-panel" type="button" role="tab"><i class="fas fa-user-tie me-1"></i> Personal Bio Data</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="academic-tab" data-bs-toggle="tab" data-bs-target="#academic-panel" type="button" role="tab"><i class="fas fa-certificate me-1"></i> Academic Qualifications</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="experience-tab" data-bs-toggle="tab" data-bs-target="#experience-panel" type="button" role="tab"><i class="fas fa-history me-1"></i> Work History</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="files-tab" data-bs-toggle="tab" data-bs-target="#files-panel" type="button" role="tab"><i class="fas fa-folder-closed me-1"></i> Staff Attachments</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="promotion-tab" data-bs-toggle="tab" data-bs-target="#promotion-panel" type="button" role="tab"><i class="fas fa-arrow-trend-up me-1"></i> Promotions</button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-4 tab-content bg-white" id="profileTabsContent">
                        
                        <div class="tab-pane fade show active" id="bio-panel" role="tabpanel">
                            <h5 class="fw-bold border-bottom border-dark pb-2 mb-3 text-dark text-sm text-uppercase font-monospace"><i class="fas fa-id-badge me-2"></i>Demographics & Identity Profiling</h5>
                            <div class="row g-3 text-sm mb-4">
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="meta-data-box">
                                        <span class="text-muted d-block text-xxs text-uppercase font-monospace fw-bold mb-1">Email Address</span>
                                        <a href="mailto:<?php echo \App\Helpers\Security::escape($staff['email'] ?? ''); ?>" class="fw-bold text-primary text-decoration-none text-break"><?php echo \App\Helpers\Security::escape($staff['email'] ?? 'N/A'); ?></a>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="meta-data-box">
                                        <span class="text-muted d-block text-xxs text-uppercase font-monospace fw-bold mb-1">Primary Phone Connection</span>
                                        <span class="fw-bold text-dark font-monospace"><?php echo \App\Helpers\Security::escape($staff['phone_one'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="meta-data-box">
                                        <span class="text-muted d-block text-xxs text-uppercase font-monospace fw-bold mb-1">Secondary Fallback Line</span>
                                        <span class="fw-bold text-secondary font-monospace"><?php echo !empty($staff['phone_two']) ? \App\Helpers\Security::escape($staff['phone_two']) : 'Not Provided'; ?></span>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="meta-data-box">
                                        <span class="text-muted d-block text-xxs text-uppercase font-monospace fw-bold mb-1">Gender Identification</span>
                                        <span class="fw-bold text-dark"><?php echo \App\Helpers\Security::escape($staff['gender'] ?? 'Not Specified'); ?></span>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="meta-data-box">
                                        <span class="text-muted d-block text-xxs text-uppercase font-monospace fw-bold mb-1">Date of Birth Calendar</span>
                                        <span class="fw-bold text-dark font-monospace"><?php echo !empty($staff['date_of_birth']) ? date('d M Y', strtotime($staff['date_of_birth'])) : 'N/A'; ?></span>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="meta-data-box">
                                        <span class="text-muted d-block text-xxs text-uppercase font-monospace fw-bold mb-1">Marital Status Status</span>
                                        <span class="fw-bold text-dark"><?php echo \App\Helpers\Security::escape($staff['marital_status'] ?? 'Single'); ?></span>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="meta-data-box">
                                        <span class="text-muted d-block text-xxs text-uppercase font-monospace fw-bold mb-1">Nationality Origin</span>
                                        <span class="fw-bold text-dark"><?php echo \App\Helpers\Security::escape($staff['nationality'] ?? 'Ghanaian'); ?></span>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="meta-data-box">
                                        <span class="text-muted d-block text-xxs text-uppercase font-monospace fw-bold mb-1">Hometown Baseline</span>
                                        <span class="fw-bold text-dark"><?php echo \App\Helpers\Security::escape($staff['hometown'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="meta-data-box">
                                        <span class="text-muted d-block text-xxs text-uppercase font-monospace fw-bold mb-1">Administrative Region Bounds</span>
                                        <span class="fw-bold text-dark"><?php echo \App\Helpers\Security::escape($staff['region'] ?? 'N/A'); ?> Region</span>
                                    </div>
                                </div>
                            </div>

                            <h5 class="fw-bold border-bottom border-dark pb-2 mb-3 text-dark text-sm text-uppercase font-monospace"><i class="fas fa-quote-left me-2"></i>Personal Biography Statement Narrative</h5>
                            <div class="p-3 border border-secondary rounded text-sm text-dark style-blockquote" style="background-color: #fafafa !important; line-height: 1.6;">
                                <?php echo !empty($staff['biography']) ? nl2br(\App\Helpers\Security::escape($staff['biography'])) : '<em>No descriptive personal profile biographical overview outline statement has been configured under this profile record yet.</em>'; ?>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-4">
                                <a href="/staff/dossier/pdf?id=<?php echo (int)($staff['user_id'] ?? 0); ?>" class="btn btn-danger btn-sm rounded px-3 fw-bold">
                                    <i class="fas fa-file-pdf me-1"></i> Download Dossier
                                </a>
                                <a href="/staff" class="btn btn-outline-dark btn-sm rounded px-3 fw-bold">
                                    <i class="fas fa-arrow-left me-1"></i> Return to Directory
                                </a>
                                <button type="button" class="btn btn-primary btn-sm rounded px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                    <i class="fas fa-user-gear me-2"></i> Modify System Meta Data
                                </button>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="academic-panel" role="tabpanel">
                            <h5 class="fw-bold border-bottom border-dark pb-2 mb-3 text-dark text-sm text-uppercase font-monospace"><i class="fas fa-graduation-cap me-2"></i>Academic Histry</h5>
                            
                            <?php if (empty($education)): ?>
                                <div class="text-center py-4 text-dark bg-light border border-secondary border-dashed rounded font-monospace text-xs fw-bold">
                                    <i class="fas fa-folder-open fa-2x mb-2 text-secondary"></i>
                                    <p class="m-0">No academic qualifications or certificates are pinned to this employee .</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="profileEduTable" class="table table-striped table-bordered align-middle text-sm border-dark mb-0">
                                        <thead class="table-dark font-monospace text-xxs text-uppercase">
                                            <tr>
                                                <th class="p-3">Institution Name</th>
                                                <th class="p-3">Degree Acquired</th>
                                                <th class="w-25 text-center p-3">Timeline</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($education as $edu): ?>
                                                <tr>
                                                    <td class="fw-bold text-dark p-3 border-secondary"><?php echo \App\Helpers\Security::escape($edu['institution']); ?></td>
                                                    <td class="p-3 border-secondary"><span class="badge bg-light text-dark border border-dark font-monospace px-2 py-1 fw-bold"><?php echo \App\Helpers\Security::escape($edu['certificate']); ?></span></td>
                                                    <td class="font-monospace text-center text-dark fw-bold p-3 border-secondary"><?php echo (int)$edu['year_from']; ?> – <?php echo (int)$edu['year_to']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="tab-pane fade" id="experience-panel" role="tabpanel">
                            <h5 class="fw-bold border-bottom border-dark pb-2 mb-3 text-dark text-sm text-uppercase font-monospace"><i class="fas fa-history me-2"></i>Work Experience</h5>
                            
                            <?php if (empty($experience)): ?>
                                <div class="text-center py-4 text-dark bg-light border border-secondary border-dashed rounded font-monospace text-xs fw-bold">
                                    <i class="fas fa-briefcase fa-2x mb-2 text-secondary"></i>
                                    <p class="m-0">No past industrial experience found for this employee.</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($experience as $exp): ?>
                                        <div class="p-3 border border-secondary rounded mb-3 bg-light shadow-xs">
                                            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2 border-bottom border-secondary-subtle pb-2">
                                                <div>
                                                    <h6 class="fw-bold text-dark m-0 text-base"><?php echo \App\Helpers\Security::escape($exp['job_title']); ?></h6>
                                                    <span class="text-sm text-primary font-monospace fw-bold"><?php echo \App\Helpers\Security::escape($exp['company_name']); ?></span>
                                                </div>
                                                <span class="badge bg-dark text-white font-monospace text-xs px-2 py-1 border">
                                                    Span: <?php echo (int)$exp['year_from']; ?> – <?php echo ((int)$exp['year_to'] === 0) ? 'Current Employee' : (int)$exp['year_to']; ?>
                                                </span>
                                            </div>
                                            <div class="text-xxs text-uppercase font-monospace text-muted fw-bold mb-1">Core Duties & Accountabilities Logged:</div>
                                            <p class="text-xs text-dark m-0 bg-white p-3 rounded border border-secondary font-sans" style="line-height: 1.5;">
                                                <?php echo !empty($exp['responsibilities']) ? nl2br(\App\Helpers\Security::escape($exp['responsibilities'])) : '<em>No descriptive accountability metrics trace string arrays provided.</em>'; ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="tab-pane fade" id="files-panel" role="tabpanel">
                            <h5 class="fw-bold border-bottom border-dark pb-2 mb-3 text-dark text-sm text-uppercase font-monospace"><i class="fas fa-shield-halved me-2"></i>Staff Documents</h5>
                            
                            <?php 
                            $hasFiles = false;
                            if (!empty($education)): ?>
                                <div class="row g-3">
                                    <?php foreach ($education as $edu): 
                                        if (!empty($edu['dossier_url'])): 
                                            $hasFiles = true;
                                            $diskPath = __DIR__ . '/../../public' . $edu['dossier_url'];
                                            $fileExt = strtolower(pathinfo($diskPath, PATHINFO_EXTENSION));
                                            $fileIcon = (in_array($fileExt, ['jpg', 'jpeg', 'png'])) ? 'fa-file-image text-primary' : 'fa-file-pdf text-danger';
                                            $filePresent = file_exists($diskPath);
                                    ?>
                                        <div class="col-12 col-md-6">
                                            <div class="p-3 border border-secondary rounded bg-light d-flex align-items-center justify-content-between shadow-xs">
                                                <div class="d-flex align-items-center text-truncate me-2">
                                                    <div class="p-2 bg-white rounded border border-secondary me-3">
                                                        <i class="fas <?php echo $fileIcon; ?> fa-2x"></i>
                                                    </div>
                                                    <div class="text-truncate">
                                                        <strong class="text-sm text-dark d-block text-truncate fw-bold" title="<?php echo \App\Helpers\Security::escape($edu['certificate']); ?>">
                                                            <?php echo \App\Helpers\Security::escape($edu['certificate']); ?>
                                                        </strong>
                                                        <small class="text-xs text-secondary font-monospace d-block text-truncate fw-bold">
                                                            Scope: <?php echo \App\Helpers\Security::escape($edu['institution']); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div>
                                                    <?php if ($filePresent): ?>
                                                        <a href="<?php echo $appUrl . $edu['dossier_url']; ?>" target="_blank" class="btn btn-sm btn-dark text-white rounded px-3 font-monospace text-xs text-nowrap fw-bold border">
                                                            Open File <i class="fas fa-arrow-up-right-from-square ms-1 small"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger text-white font-monospace text-xxs p-2 border">
                                                            <i class="fas fa-triangle-exclamation me-1"></i> File Missing
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!$hasFiles): ?>
                                <div class="text-center py-5 text-dark bg-light border border-secondary border-dashed rounded font-monospace text-xs fw-bold">
                                    <i class="fas fa-cloud-arrow-up fa-2x mb-2 text-secondary"></i>
                                    <p class="m-0">No physical backup attachment files or legal academic certificates were found uploaded to this node track.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="tab-pane fade" id="promotion-panel" role="tabpanel">
                            <h5 class="fw-bold border-bottom border-dark pb-2 mb-3 text-dark text-sm text-uppercase font-monospace"><i class="fas fa-arrow-trend-up me-2"></i>Promotion Requests</h5>
                            <?php if (empty($promotions)): ?>
                                <div class="text-center py-4 text-dark bg-light border border-secondary border-dashed rounded font-monospace text-xs fw-bold">
                                    <i class="fas fa-folder-open fa-2x mb-2 text-secondary"></i>
                                    <p class="m-0">No promotion requests found for this profile.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="profilePromotionTable" class="table table-striped table-bordered align-middle text-sm">
                                        <thead class="table-dark text-uppercase text-xxs">
                                            <tr>
                                                <th>Current Rank</th>
                                                <th>Requested Rank</th>
                                                <th>Status</th>
                                                <th class="no-export">Document</th>
                                                <th>Created</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($promotions as $promotion): ?>
                                            <tr>
                                                <td><?php echo \App\Helpers\Security::escape($promotion['current_rank'] ?? ''); ?></td>
                                                <td><?php echo \App\Helpers\Security::escape($promotion['requested_rank'] ?? ''); ?></td>
                                                <td><span class="badge bg-info text-dark"><?php echo \App\Helpers\Security::escape($promotion['status'] ?? 'Pending'); ?></span></td>
                                                <td class="no-export">
                                                    <?php if (!empty($promotion['supporting_document'])): ?>
                                                        <a href="<?php echo $appUrl . \App\Helpers\Security::escape($promotion['supporting_document']); ?>" target="_blank" class="btn btn-sm btn-dark">Open</a>
                                                    <?php else: ?>
                                                        <span class="text-muted">None</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo \App\Helpers\Security::escape((string)$promotion['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div> </div>
            </div> </div>
    </div> </div> <div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border border-dark shadow-lg">
            <div class="modal-header bg-dark text-white p-3">
                <h6 class="modal-title fw-bold font-monospace"><i class="fas fa-user-pen me-2"></i>Edit Profile Meta fields</h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="modifyProfileForm" novalidate>
                <input type="hidden" name="user_id" value="<?php echo (int)($staff['user_id'] ?? 0); ?>">
                <div class="modal-body p-4 text-sm bg-white">
                    <div id="modal-feedback-alert" class="alert d-none text-xs py-2 shadow-sm" role="alert"></div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark text-xs text-uppercase font-monospace">Full Legal Name</label>
                        <input type="text" name="fullname" class="form-control form-control-sm border-dark-subtle" value="<?php echo \App\Helpers\Security::escape($staff['fullname'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark text-xs text-uppercase font-monospace">Primary Phone Connection</label>
                        <input type="tel" name="phone_one" class="form-control form-control-sm border-dark-subtle" value="<?php echo \App\Helpers\Security::escape($staff['phone_one'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark text-xs text-uppercase font-monospace">Department</label>
                        <select name="department_id" class="form-select form-select-sm border-dark-subtle" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo (int)$dept['dept_id']; ?>" <?php echo ((int)($staff['dept_id'] ?? 0) === (int)$dept['dept_id']) ? 'selected' : ''; ?>><?php echo \App\Helpers\Security::escape($dept['dept_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark text-xs text-uppercase font-monospace">Supervisor</label>
                        <select name="supervisor_user_id" class="form-select form-select-sm border-dark-subtle">
                            <option value="">No Supervisor</option>
                            <?php foreach ($supervisors as $sup): ?>
                            <option value="<?php echo (int)$sup['id']; ?>" <?php echo ((int)($staff['supervisor_user_id'] ?? 0) === (int)$sup['id']) ? 'selected' : ''; ?>><?php echo \App\Helpers\Security::escape($sup['fullname']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark text-xs text-uppercase font-monospace">Access Level</label>
                        <select name="role_id" class="form-select form-select-sm border-dark-subtle" required>
                            <option value="">Select Role</option>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo (int)$role['id']; ?>" <?php echo ((int)$currentRoleId === (int)$role['id']) ? 'selected' : ''; ?>><?php echo \App\Helpers\Security::escape($role['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark text-xs text-uppercase font-monospace">Engagement Mode</label>
                        <select name="employment_status" class="form-select form-select-sm border-dark-subtle" required>
                            <option value="Permanent" <?php echo (($staff['employment_status'] ?? '') === 'Permanent') ? 'selected' : ''; ?>>Permanent</option>
                            <option value="Probation" <?php echo (($staff['employment_status'] ?? '') === 'Probation') ? 'selected' : ''; ?>>Probation</option>
                            <option value="Contract" <?php echo (($staff['employment_status'] ?? '') === 'Contract') ? 'selected' : ''; ?>>Contract</option>
                            <option value="Suspended" <?php echo (($staff['employment_status'] ?? '') === 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light p-2 border-top border-secondary-subtle">
                    <button type="button" class="btn btn-secondary btn-sm fw-bold px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold px-3" id="saveProfileBtn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($('#profilePromotionTable').length) {
        $('#profilePromotionTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No promotion records found." },
            order: [[4, 'desc']]
        });
    }
});
</script>

<div id="dossierProgressModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:99999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:2rem;max-width:400px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i>
        <h5 class="fw-bold">Generating Dossier</h5>
        <p class="text-muted small mb-3">Please wait while your dossier is being prepared...</p>
        <div class="progress mb-2" style="height:10px;background:#e9ecef;border-radius:6px;">
            <div id="dossierProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width:0%;border-radius:6px;"></div>
        </div>
        <p id="dossierProgressText" class="small fw-bold text-muted mb-0">0%</p>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const editForm = document.getElementById("modifyProfileForm");
    const feedback = document.getElementById("modal-feedback-alert");
    const saveBtn = document.getElementById("saveProfileBtn");
    const profileUpdateEndpoint = "<?php echo $appUrl; ?>/staff/profile/update";
    const csrfToken = "<?php echo \App\Helpers\Security::generateCsrfToken(); ?>";

    // Download Dossier progress modal with simulated progress
    const dossierBtn = document.querySelector('a[href*="dossier/pdf"]');
    if (dossierBtn) {
        dossierBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            const modalEl = document.getElementById('dossierProgressModal');
            const barEl = document.getElementById('dossierProgressBar');
            const textEl = document.getElementById('dossierProgressText');
            if (!modalEl) { window.location.href = url; return; }

            modalEl.style.display = 'flex';
            barEl.style.width = '0%';
            textEl.textContent = '0%';

            let progress = 0;
            const interval = setInterval(function() {
                progress += Math.random() * 8 + 2;
                if (progress >= 95) {
                    progress = 95;
                    clearInterval(interval);
                    setTimeout(function() {
                        window.location.href = url;
                    }, 200);
                    return;
                }
                barEl.style.width = Math.round(progress) + '%';
                textEl.textContent = Math.round(progress) + '%';
            }, 300);

            setTimeout(function() {
                modalEl.style.display = 'none';
            }, 15000);
        });
    }

    if(editForm) {
        editForm.addEventListener("submit", function(e) {
            e.preventDefault();
            feedback.classList.add("d-none");
            saveBtn.setAttribute("disabled", "true");

            let formData = new FormData(this);
            formData.append("csrf_token", csrfToken);

            fetch(profileUpdateEndpoint, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json"
                },
                body: formData
            })
            .then(async (res) => {
                let payload = null;
                try {
                    payload = await res.json();
                } catch (e) {
                    throw new Error("Invalid server response");
                }
                if (!res.ok) {
                    throw new Error(payload && payload.message ? payload.message : "Request failed");
                }
                return payload;
            })
            .then(data => {
                saveBtn.removeAttribute("disabled");
                if (data.success) {
                    feedback.className = "alert alert-success text-xs py-2 shadow-sm mb-3";
                    feedback.innerHTML = `<i class='fas fa-check-circle me-1'></i>${data.message}`;
                    setTimeout(() => { window.location.reload(); }, 1000);
                } else {
                    feedback.className = "alert alert-danger text-xs py-2 shadow-sm mb-3";
                    feedback.innerHTML = `<i class='fas fa-exclamation-triangle me-1'></i>${data.message}`;
                }
            })
            .catch(() => {
                saveBtn.removeAttribute("disabled");
                feedback.className = "alert alert-danger text-xs py-2 shadow-sm mb-3";
                feedback.innerHTML = "<i class='fas fa-exclamation-triangle me-1'></i>HTTP Network error returned processing payload request.";
            });
        });
    }
});
</script>