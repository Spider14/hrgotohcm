<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
$periods = [date('Y') . ' Q1', date('Y') . ' Q2', date('Y') . ' Q3', date('Y') . ' Q4', date('Y') . ' Annual'];
?>

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Performance Appraisal</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1200px;">
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h5 class="fw-bold mb-3">Submit Self Appraisal</h5>
            <form method="POST" action="<?php echo $appUrl; ?>/staff/appraisal" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <div class="col-12 col-md-4">
                    <label class="form-label">Period</label>
                    <select name="period_label" class="form-select form-select-sm" required>
                        <?php foreach ($periods as $p): ?>
                            <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12"><hr><h6 class="fw-bold">Evaluation Metrics</h6></div>
                <?php foreach ($appraisalMetrics as $metric): ?>
                <div class="col-12 col-md-6">
                    <label class="form-label"><?php echo \App\Helpers\Security::escape((string)$metric['metric_name']); ?></label>
                    <select name="responses[<?php echo (int)$metric['id']; ?>]" class="form-select form-select-sm" required>
                        <option value="">-- Rate --</option>
                        <option value="1">1 - Needs Improvement</option>
                        <option value="2">2 - Fair</option>
                        <option value="3">3 - Good</option>
                        <option value="4">4 - Very Good</option>
                        <option value="5">5 - Excellent</option>
                    </select>
                </div>
                <?php endforeach; ?>
                <div class="col-12 d-grid d-md-flex justify-content-md-end mt-3">
                    <button class="btn btn-primary px-4" type="submit">Submit Appraisal</button>
                </div>
            </form>
        </div>

        <?php if (!empty($pendingStaffApproval)): ?>
        <div class="card border-0 shadow-sm p-4 mb-4 border-warning">
            <h5 class="fw-bold mb-3 text-warning"><i class="fas fa-clock me-2"></i>Awaiting Your Response</h5>
            <?php foreach ($pendingStaffApproval as $p): ?>
            <div class="border rounded p-3 mb-3 bg-light">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <strong><?php echo \App\Helpers\Security::escape((string)$p['period_label']); ?></strong>
                        <span class="badge bg-info ms-2">Score: <?php echo \App\Helpers\Security::escape((string)($p['self_score'] ?? '-')); ?></span>
                    </div>
                    <small class="text-muted"><?php echo \App\Helpers\Security::escape((string)($p['created_at'] ?? '')); ?></small>
                </div>
                <div class="mb-2 p-2 bg-white rounded border-start border-3 border-primary">
                    <small class="text-muted fw-bold">Supervisor's Assessment:</small>
                    <p class="mb-0 small"><?php echo \App\Helpers\Security::escape((string)($p['supervisor_comment'] ?? 'No comment provided.')); ?></p>
                    <small class="text-muted">— <?php echo \App\Helpers\Security::escape((string)($p['reviewer_name'] ?? 'Supervisor')); ?></small>
                </div>
                <form method="POST" action="<?php echo $appUrl; ?>/staff/appraisal/respond" class="row g-2 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="appraisal_id" value="<?php echo (int)$p['id']; ?>">
                    <div class="col-12 col-md-6">
                        <label class="small">Your Reason (required)</label>
                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. I agree with the assessment..." required>
                    </div>
                    <div class="col-6 col-md-3 d-grid">
                        <button type="submit" name="decision" value="Staff Approved" class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i>Approve</button>
                    </div>
                    <div class="col-6 col-md-3 d-grid">
                        <button type="submit" name="decision" value="Staff Disapproved" class="btn btn-outline-danger btn-sm"><i class="fas fa-times me-1"></i>Disapprove</button>
                    </div>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-3">My Appraisals</h5>
            <div class="table-responsive">
                <table id="appraisalTable" class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark"><tr><th>#</th><th>Period</th><th>Self Score</th><th>Status</th><th>Supervisor Comment</th><th>Staff Response</th><th>Submitted</th><th class="no-export">PDF</th></tr></thead>
                    <tbody>
                    <?php foreach ($appraisalRows as $a): ?>
                        <tr>
                            <td><?php echo (int)$a['id']; ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$a['period_label']); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($a['self_score'] ?? '-')); ?></td>
                            <td><?php
                                $badgeClass = match ($a['status']) {
                                    'Completed' => 'success',
                                    'Declined', 'Staff Disapproved' => 'danger',
                                    'Staff Approved' => 'info',
                                    'Commented' => 'primary',
                                    default => 'warning',
                                };
                                ?><span class="badge bg-<?php echo $badgeClass; ?>"><?php echo \App\Helpers\Security::escape((string)$a['status']); ?></span></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($a['supervisor_comment'] ?? '-')); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($a['staff_approval_note'] ?? '-')); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($a['created_at'] ?? '')); ?></td>
                            <td class="no-export"><a href="<?php echo $appUrl; ?>/staff/appraisal/pdf?id=<?php echo (int)$a['id']; ?>" class="btn btn-sm btn-danger"><i class="fas fa-file-pdf"></i></a></td>
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
    if ($('#appraisalTable').length) {
        $('#appraisalTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No appraisals found." },
            order: [[0, 'desc']]
        });
    }
});
</script>
