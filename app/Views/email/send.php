<?php
/**
 * @var array $config
 * @var array $templates
 * @var array $departments
 * @var array $faculties
 * @var array $directorates
 * @var string $pageTitle
 * @var string $appUrl
 */
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3">
                <i class="fas fa-bars"></i>
            </button>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb m-0 bg-transparent p-0">
                    <li class="breadcrumb-item text-dark"><a href="<?php echo $appUrl; ?>/dashboard" class="text-decoration-none fw-semibold">Dashboard</a></li>
                    <li class="breadcrumb-item text-dark fw-semibold">Email</li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Send Email</li>
                </ol>
            </nav>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">

        <div class="card border-0 shadow-sm rounded-lg p-4 bg-white">
            <div class="border-bottom pb-3 mb-4">
                <h4 class="fw-bold text-dark m-0"><i class="fas fa-envelope-open-text me-2" style="color: #1d2a52;"></i>HRGoTo Email Dispatch</h4>
                <p class="text-muted small m-0 mt-1">Send emails to recruitment applicants, internal staff, or custom recipient lists using saved templates.</p>
            </div>

            <form action="<?php echo $appUrl; ?>/email/send" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\Security::generateCsrfToken(); ?>">

                <div class="row mb-4">
                    <div class="col-12 col-md-4">
                        <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block">Sender Email</label>
                        <input type="email" name="sender_email" class="form-control font-monospace fw-bold" style="border: 1.5px solid #d1d5db; border-radius: 6px; color: #1d2a52;" value="<?php echo \App\Helpers\Security::escape($config['smtp_from_email'] ?? ''); ?>" placeholder="noreply@example.com" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block">Sender Name</label>
                        <input type="text" name="sender_name" class="form-control" style="border: 1.5px solid #d1d5db; border-radius: 6px;" value="<?php echo \App\Helpers\Security::escape($config['smtp_from_name'] ?? 'HRGoTo HCM'); ?>" placeholder="HRGoTo HCM">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block">Email Subject</label>
                        <input type="text" id="emailSubject" name="subject" class="form-control" style="border: 1.5px solid #d1d5db; border-radius: 6px;" placeholder="Enter email subject" required>
                    </div>
                </div>

                <ul class="nav nav-pills border-bottom pb-2 mb-4 font-monospace" id="emailWorkflowTabs" role="tablist">
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

                <div class="tab-content" id="emailWorkflowPanes">

                    <div class="tab-pane fade show active" id="recruitment-pane" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-12 col-md-6">
                                <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block">Select Stage</label>
                                <select name="recruitment_status" id="recruitmentStatusDropdown" class="form-select" style="border: 1.5px solid #d1d5db; border-radius: 6px;">
                                    <option value="">-- Select Stage --</option>
                                    <option value="pending">Pending Applicants</option>
                                    <option value="shortlisted">Shortlisted Applicants</option>
                                    <option value="hired">Successful Applicants</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block">Load Template</label>
                                <select id="recruitmentTemplateSelector" class="form-select font-sans" style="border: 1.5px solid #d1d5db; border-radius: 6px;">
                                    <option value="">-- Select Email Template --</option>
                                    <?php foreach ($templates as $tpl): ?>
                                        <option value="<?php echo \App\Helpers\Security::escape($tpl['id']); ?>" data-subject="<?php echo \App\Helpers\Security::escape($tpl['template_subject']); ?>" data-body="<?php echo \App\Helpers\Security::escape($tpl['template_body']); ?>"><?php echo \App\Helpers\Security::escape($tpl['template_name']); ?></option>
                                    <?php endforeach; ?>
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
                                    <label class="font-monospace small text-muted fw-bold mb-1">Target Department</label>
                                    <select name="target_dept_id" class="form-select" style="border: 1.5px solid #94a3b8;">
                                        <option value="">-- Choose Target Department --</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['dept_id']; ?>"><?php echo \App\Helpers\Security::escape($dept['dept_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div id="facultySubGroup" class="mb-3 d-none sub-selector">
                                    <label class="font-monospace small text-muted fw-bold mb-1">Target Faculty</label>
                                    <select name="target_faculty_id" class="form-select" style="border: 1.5px solid #94a3b8;">
                                        <option value="">-- Choose Target Faculty --</option>
                                        <?php foreach ($faculties as $fac): ?>
                                            <option value="<?php echo $fac['sch_id']; ?>"><?php echo \App\Helpers\Security::escape($fac['sch_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div id="directorateSubGroup" class="mb-3 d-none sub-selector">
                                    <label class="font-monospace small text-muted fw-bold mb-1">Target Directorate</label>
                                    <select name="target_dir_id" class="form-select" style="border: 1.5px solid #94a3b8;">
                                        <option value="">-- Choose Target Directorate --</option>
                                        <?php foreach ($directorates as $dir): ?>
                                            <option value="<?php echo $dir['dir_id']; ?>"><?php echo \App\Helpers\Security::escape($dir['dir_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block">Load Email Template</label>
                                <select id="staffTemplateSelector" class="form-select font-sans" style="border: 1.5px solid #d1d5db; border-radius: 6px;">
                                    <option value="">-- Select Email Template --</option>
                                    <?php foreach ($templates as $tpl): ?>
                                        <option value="<?php echo \App\Helpers\Security::escape($tpl['id']); ?>" data-subject="<?php echo \App\Helpers\Security::escape($tpl['template_subject']); ?>" data-body="<?php echo \App\Helpers\Security::escape($tpl['template_body']); ?>"><?php echo \App\Helpers\Security::escape($tpl['template_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="other-pane" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-12 col-md-6">
                                <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block">Manual Recipient Emails</label>
                                <textarea name="raw_recipients" class="form-control mb-3" rows="3" style="border: 1.5px solid #d1d5db; border-radius: 6px;" placeholder="user1@example.com, user2@example.com"></textarea>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block">Load Email Template</label>
                                <select id="otherTemplateSelector" class="form-select font-sans" style="border: 1.5px solid #d1d5db; border-radius: 6px;">
                                    <option value="">-- Select Email Template --</option>
                                    <?php foreach ($templates as $tpl): ?>
                                        <option value="<?php echo \App\Helpers\Security::escape($tpl['id']); ?>" data-subject="<?php echo \App\Helpers\Security::escape($tpl['template_subject']); ?>" data-body="<?php echo \App\Helpers\Security::escape($tpl['template_body']); ?>"><?php echo \App\Helpers\Security::escape($tpl['template_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4 pt-4 border-top">
                    <div class="col-12">
                        <label class="text-uppercase font-monospace text-muted small fw-bold mb-2 d-block"><i class="fas fa-edit me-1 text-secondary"></i>Email Body (HTML supported)</label>
                        <textarea id="emailBodyTextarea" name="email_body" class="form-control p-3 text-dark font-sans" rows="8" style="border: 1.5px solid #a4b0be; border-radius: 6px; resize: vertical; background-color: #f8f9fa;" placeholder="&lt;p&gt;Dear [fullname],&lt;/p&gt;&lt;p&gt;We are pleased to inform you that you have been appointed to the position of [position] with effect from [effective_date]. Your salary will be GHS [salary] per month, with additional benefits including: [other_benefits].&lt;/p&gt;&lt;p&gt;Please contact HR for further details.&lt;/p&gt;&lt;p&gt;Best regards,&lt;br&gt;HRGoTo HCM&lt;/p&gt;" required></textarea>
                    </div>
                </div>

                <div class="pt-3 text-end">
                    <button type="submit" class="btn btn-sm px-5 py-2 fw-bold shadow-sm text-white" style="background-color: #1d2a52; border-radius: 6px;">
                        <i class="fas fa-paper-plane me-2"></i> Send Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const bodyField = document.getElementById("emailBodyTextarea");
    const subjectField = document.getElementById("emailSubject");
    const tracker = document.getElementById("targetFlowTracker");

    function loadTemplate(selectEl) {
        const selected = selectEl.options[selectEl.selectedIndex];
        if (selected && selected.value) {
            subjectField.value = selected.getAttribute('data-subject') || '';
            bodyField.value = selected.getAttribute('data-body') || '';
        }
    }

    document.querySelectorAll('#emailWorkflowTabs button').forEach(button => {
        button.addEventListener('shown.bs.tab', function (e) {
            tracker.value = e.target.id.replace('-tab', '');
        });
    });

    document.getElementById("recruitmentTemplateSelector").addEventListener("change", function() {
        loadTemplate(this);
    });
    document.getElementById("staffTemplateSelector").addEventListener("change", function() {
        loadTemplate(this);
    });
    document.getElementById("otherTemplateSelector").addEventListener("change", function() {
        loadTemplate(this);
    });

    document.getElementById("staffScopeSelector").addEventListener("change", function() {
        document.querySelectorAll(".sub-selector").forEach(el => el.classList.add("d-none"));
        const targetId = this.value + "SubGroup";
        const targetEl = document.getElementById(targetId);
        if (targetEl) targetEl.classList.remove("d-none");
    });
});
</script>
