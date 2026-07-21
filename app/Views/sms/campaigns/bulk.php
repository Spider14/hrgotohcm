<?php
/**
 * @var array $templates
 * @var array $config
 * @var array $departments
 * @var array $faculties
 * @var array $directorates
 * @var array $senderIds
 * @var string $pageTitle
 * @var string $appUrl
 */
?>
<?php require_once __DIR__ . '/../../dashboard/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../../dashboard/layouts/sidebar.php'; ?>

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
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Segmented Bulk Outbound</li>
                </ol>
            </nav>
            <?php require __DIR__ . '/../../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        
        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] === 'dispatched'): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-lg" role="alert">
                    <i class="fas fa-check-circle me-2"></i> Broadcast Complete! Successfully processed and dispatched messages to <strong><?php echo (int)($_GET['count'] ?? 0); ?></strong> recipients.
                </div>
            <?php elseif ($_GET['status'] === 'no_recipients'): ?>
                <div class="alert alert-warning border-0 shadow-sm rounded-lg" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> Operational Abort: Target database segment query returned 0 valid numbers.
                </div>
            <?php elseif ($_GET['status'] === 'empty_fields'): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-lg" role="alert">
                    <i class="fas fa-times-circle me-2"></i> Validation Error: Message body context or target parameters cannot be empty.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-lg p-4 bg-white">
            <div class="border-bottom pb-3 mb-4">
                <h4 class="fw-bold text-dark m-0"><i class="fas fa-mail-bulk me-2" style="color: #1d2a52;"></i>HRGoTo Bulk SMS Hub</h4>
                <p class="text-muted small m-0 mt-1">Target specific employee categories, active recruitment applicant listings, or custom ad-hoc batches.</p>
            </div>

            <form action="/sms/campaigns/bulk/process" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\Security::generateCsrfToken(); ?>">
                
                <div class="row mb-4">
                    <div class="col-12 col-md-4">
                        <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block">Select Authorized Sender ID</label>
                        <select name="sender_id" class="form-select font-monospace fw-bold" style="border: 1.5px solid #d1d5db; border-radius: 6px; color: #1d2a52;" required>
                            <option value="<?php echo \App\Helpers\Security::escape($config['gen_sms_sender_id'] ?? 'HRGOTO'); ?>">
                                Default (<?php echo \App\Helpers\Security::escape($config['gen_sms_sender_id'] ?? 'HRGOTO'); ?>)
                            </option>
                            <?php foreach ($senderIds as $sid): ?>
                                <option value="<?php echo \App\Helpers\Security::escape($sid['name'] ?? $sid['sender_id'] ?? ''); ?>">
                                    <?php echo \App\Helpers\Security::escape($sid['name'] ?? $sid['sender_id'] ?? ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <ul class="nav nav-pills border-bottom pb-2 mb-4 font-monospace" id="bulkWorkflowTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-uppercase" id="recruitment-tab" data-bs-toggle="pill" data-bs-target="#recruitment-pane" type="button" role="tab"><i class="fas fa-user-graduate me-2"></i>1. Recruitment</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-uppercase" id="staff-tab" data-bs-toggle="pill" data-bs-target="#staff-pane" type="button" role="tab"><i class="fas fa-users-cog me-2"></i>2. Internal Staff</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-uppercase" id="other-tab" data-bs-toggle="pill" data-bs-target="#other-pane" type="button" role="tab"><i class="fas fa-globe me-2"></i>3. Other</button>
                    </li>
                </ul>

                <input type="hidden" name="target_flow" id="targetFlowTracker" value="recruitment">

                <div class="tab-content" id="bulkWorkflowPanes">
                    
                    <div class="tab-pane fade show active" id="recruitment-pane" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-12 col-md-6">
                                <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block">Select Stage</label>
                                <select name="recruitment_status" id="recruitmentStatusDropdown" class="form-select" style="border: 1.5px solid #d1d5db; border-radius: 6px;">
                                    <option value="">-- Select Stage --</option>
                                    <option value="pending" data-template="<?php echo \App\Helpers\Security::escape($templates['pending'] ?? ''); ?>">Pending Applicants</option>
                                    <option value="shortlisted" data-template="<?php echo \App\Helpers\Security::escape($templates['shortlisted'] ?? ''); ?>">Shortlisted Applicants</option>
                                    <option value="hired" data-template="<?php echo \App\Helpers\Security::escape($templates['hired'] ?? ''); ?>">Successful Applicants</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="staff-pane" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-12 col-md-6">
                                <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block">Staff Target Scope</label>
                                <select name="staff_scope" id="staffScopeSelector" class="form-select mb-3" style="border: 1.5px solid #d1d5db; border-radius: 6px;">
                                    <option value="">-- Choose Your Target Category --</option>
                                    <option value="all">All Staff Personnel</option>
                                    <option value="department">Department</option>
                                    <option value="faculty">Faculty</option>
                                    <option value="directorate">Directorates</option>
                                </select>

                                <div id="departmentSubGroup" class="mb-3 d-none sub-selector">
                                    <label class="font-monospace small text-muted fw-bold mb-1">Target Department Profile</label>
                                    <select name="target_dept_id" class="form-select" style="border: 1.5px solid #94a3b8;">
                                        <option value="">-- Choose Target Department --</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['dept_id']; ?>"><?php echo \App\Helpers\Security::escape($dept['dept_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div id="facultySubGroup" class="mb-3 d-none sub-selector">
                                    <label class="font-monospace small text-muted fw-bold mb-1">Target Faculty Profile</label>
                                    <select name="target_faculty_id" class="form-select" style="border: 1.5px solid #94a3b8;">
                                        <option value="">-- Choose Target Faculty --</option>
                                        <?php foreach ($faculties as $fac): ?>
                                            <option value="<?php echo $fac['sch_id']; ?>"><?php echo \App\Helpers\Security::escape($fac['sch_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div id="directorateSubGroup" class="mb-3 d-none sub-selector">
                                    <label class="font-monospace small text-muted fw-bold mb-1">Target Directorate Profile</label>
                                    <select name="target_dir_id" class="form-select" style="border: 1.5px solid #94a3b8;">
                                        <option value="">-- Choose Target Directorate --</option>
                                        <?php foreach ($directorates as $dir): ?>
                                            <option value="<?php echo $dir['dir_id']; ?>"><?php echo \App\Helpers\Security::escape($dir['dir_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block">Staff Communication Script Base</label>
                                <select id="staffTemplateSelector" class="form-select font-sans" style="border: 1.5px solid #d1d5db; border-radius: 6px;">
                                    <option value="">-- Optional: Load Script from Template Settings --</option>
                                    <?php foreach ($templates as $key => $txt): ?>
                                        <?php if ($key === 'sms_cam_id' || empty($txt)) continue; ?>
                                        <option value="<?php echo \App\Helpers\Security::escape($txt); ?>"><?php echo ucwords(str_replace('_', ' ', $key)); ?> Template</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="other-pane" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-12 col-md-6">
                                <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block">Manual Custom Phone Inputs</label>
                                <textarea name="raw_recipients" class="form-control mb-3" rows="3" style="border: 1.5px solid #d1d5db; border-radius: 6px;" placeholder="0244000000, 0501234567, +233201112222"></textarea>
                                
                                <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block">Or Parse Data via CSV Binary Sheet Document</label>
                                <input type="file" name="csv_file" class="form-control" accept=".csv" style="border: 1.5px solid #d1d5db; border-radius: 6px;">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block">Ad-Hoc Message Template Presets</label>
                                <select id="otherTemplateSelector" class="form-select font-sans" style="border: 1.5px solid #d1d5db; border-radius: 6px;">
                                    <option value="">-- Optional: Load Script Preset --</option>
                                    <?php foreach ($templates as $key => $txt): ?>
                                        <?php if ($key === 'sms_cam_id' || empty($txt)) continue; ?>
                                        <option value="<?php echo \App\Helpers\Security::escape($txt); ?>"><?php echo ucwords(str_replace('_', ' ', $key)); ?> Template</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4 pt-4 border-top">
                    <div class="col-12">
                        <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block"><i class="fas fa-edit me-1 text-secondary"></i>Type/Edit your message below</label>
                        <textarea id="unifiedWorkspaceTextarea" name="message" class="form-control p-3 text-dark font-sans" rows="5" style="border: 1.5px solid #a4b0be; border-radius: 6px; resize: vertical; background-color: #f8f9fa;" placeholder="Select parameters above or type custom strings manually..." required></textarea>
                        <div class="d-flex justify-content-between mt-2 px-1">
                            <span class="small text-muted font-monospace" id="charMetricsLabel">0 characters</span>
                            <span class="small text-muted font-monospace" id="segmentMetricsLabel">1 page structural segment box</span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 text-end">
                    <button type="submit" class="btn btn-sm px-5 py-2 fw-bold shadow-sm text-white" style="background-color: #1d2a52; border-radius: 6px;">
                        <i class="fas fa-rocket me-2"></i> Dispatch Pipeline Channel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const workspace = document.getElementById("unifiedWorkspaceTextarea");
    const tracker = document.getElementById("targetFlowTracker");
    
    function calculateMetrics() {
        let len = workspace.value.length;
        document.getElementById("charMetricsLabel").textContent = `${len} characters`;
        document.getElementById("segmentMetricsLabel").textContent = `${Math.ceil(len / 160) || 1} SMS Page Segment(s)`;
    }
    workspace.addEventListener("input", calculateMetrics);

    // Tab Flow State Observers
    document.querySelectorAll('#bulkWorkflowTabs button').forEach(button => {
        button.addEventListener('shown.bs.tab', function (e) {
            let flowId = e.target.id.replace('-tab', '');
            tracker.value = flowId;
            workspace.value = ""; 
            calculateMetrics();
        });
    });

    // Template Selector Watchers
    document.getElementById("recruitmentStatusDropdown").addEventListener("change", function() {
        let selectedOption = this.options[this.selectedIndex];
        workspace.value = selectedOption.getAttribute('data-template') || "";
        calculateMetrics();
    });

    document.getElementById("staffTemplateSelector").addEventListener("change", function() {
        workspace.value = this.value;
        calculateMetrics();
    });

    document.getElementById("otherTemplateSelector").addEventListener("change", function() {
        workspace.value = this.value;
        calculateMetrics();
    });

    // Reactive Sub-dropdown Conditional Toggler
    document.getElementById("staffScopeSelector").addEventListener("change", function() {
        // Hide all secondary selector fields down the tree safely
        document.querySelectorAll(".sub-selector").forEach(el => el.classList.add("d-none"));
        
        // Target and reveal the matching container ID explicitly
        let targetSubContainerId = `${this.value}SubGroup`;
        let targetElement = document.getElementById(targetSubContainerId);
        if (targetElement) {
            targetElement.classList.remove("d-none");
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../dashboard/layouts/footer.php'; ?>