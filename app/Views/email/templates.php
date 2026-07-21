<?php
/**
 * @var array $emailTemplates
 * @var string $appUrl
 * @var string $csrf
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
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Email Templates</li>
                </ol>
            </nav>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1200px;">
        <div class="card border-0 shadow-sm rounded-lg p-4 bg-white mb-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-plus-circle me-2 text-success"></i>Add Email Template</h5>
            <form method="POST" action="<?php echo $appUrl; ?>/email/templates/save">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Template Name</label>
                        <input type="text" name="template_name" class="form-control form-control-sm" placeholder="e.g. Appointment Letter" required>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label fw-semibold">Subject</label>
                        <input type="text" name="template_subject" class="form-control form-control-sm" placeholder="Email subject line" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Body (HTML supported)</label>
                        <textarea name="template_body" rows="6" class="form-control form-control-sm font-monospace" placeholder="&lt;p&gt;Dear [fullname],&lt;/p&gt;&lt;p&gt;We are pleased to inform you that you have been appointed to the position of [position] with effect from [effective_date]. Your salary will be GHS [salary] per month, with additional benefits including: [other_benefits].&lt;/p&gt;&lt;p&gt;Please contact HR for further details.&lt;/p&gt;&lt;p&gt;Best regards,&lt;br&gt;HRGoTo HCM&lt;/p&gt;" required></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Available Placeholders</label>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-secondary">[fullname]</span>
                            <span class="badge bg-secondary">[email]</span>
                            <span class="badge bg-secondary">[staff_id]</span>
                            <span class="badge bg-secondary">[department]</span>
                            <span class="badge bg-secondary">[appraisal_score]</span>
                            <span class="badge bg-secondary">[leave_type]</span>
                            <span class="badge bg-secondary">[leave_dates]</span>
                            <span class="badge bg-secondary">[status]</span>
                            <span class="badge bg-secondary">[comment]</span>
                            <span class="badge bg-secondary">[position]</span>
                            <span class="badge bg-secondary">[effective_date]</span>
                            <span class="badge bg-secondary">[salary]</span>
                            <span class="badge bg-secondary">[other_benefits]</span>
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-success px-4 btn-sm">Add Template</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm rounded-lg p-4 bg-white">
            <h5 class="fw-bold mb-3"><i class="fas fa-list me-2 text-primary"></i>Saved Email Templates</h5>
            <div class="table-responsive">
                <table id="emailTemplatesTable" class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark text-uppercase small">
                        <tr>
                            <th>Name</th>
                            <th>Subject</th>
                            <th>Body Preview</th>
                            <th class="text-center">Active</th>
                            <th class="no-export text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emailTemplates as $tpl): ?>
                            <tr>
                                <td class="fw-bold"><?php echo \App\Helpers\Security::escape($tpl['template_name']); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($tpl['template_subject']); ?></td>
                                <td class="text-muted small" style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?php echo strip_tags($tpl['template_body']); ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo !empty($tpl['is_active']) ? 'success' : 'secondary'; ?>">
                                        <?php echo !empty($tpl['is_active']) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="text-center no-export">
                                    <form method="POST" action="<?php echo $appUrl; ?>/email/templates/delete" style="display:inline;" onsubmit="return confirm('Delete this template?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <input type="hidden" name="template_id" value="<?php echo (int)$tpl['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($('#emailTemplatesTable').length) {
        $('#emailTemplatesTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No email templates found." },
            order: [[0, 'asc']]
        });
    }
});
</script>
