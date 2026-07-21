<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
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
                    <li class="breadcrumb-item text-dark">Staff Management</li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Appraisals</li>
                </ol>
            </nav>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-primary">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-primary-light text-primary me-3"><i class="fas fa-star-half-stroke fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Total Appraisals</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo (int)($summary['total_records'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-info">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-info-light text-info me-3"><i class="fas fa-chart-line fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Average Score</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo number_format((float)($summary['average_score'] ?? 0), 1); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-success">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-success-light text-success me-3"><i class="fas fa-check-circle fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Completed</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo (int)($summary['completed_records'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-warning">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-warning-light text-warning me-3"><i class="fas fa-clock fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Pending Final</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo (int)($summary['pending_final_records'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-lg p-4 bg-white">
            <h5 class="fw-bold mb-3"><i class="fas fa-list me-2 text-primary"></i>Appraisal History</h5>
            <div class="table-responsive">
                <table id="manageAppraisalTable" class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark text-uppercase small">
                        <tr>
                            <th>Staff</th>
                            <th>Department</th>
                            <th>Period</th>
                            <th>Score</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Supervisor</th>
                            <th>Staff Response</th>
                            <th class="no-export">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo \App\Helpers\Security::escape($row['fullname']); ?></div>
                                    <small class="text-muted font-monospace"><?php echo \App\Helpers\Security::escape($row['staff_id_card'] ?? ''); ?></small>
                                </td>
                                <td><?php echo \App\Helpers\Security::escape($row['dept_name'] ?? 'N/A'); ?></td>
                                <td class="font-monospace"><?php echo \App\Helpers\Security::escape($row['period_label']); ?></td>
                                <td class="fw-bold"><?php echo \App\Helpers\Security::escape((string)$row['score']); ?></td>
                                <td>
                                    <?php $ratingBadge = 'bg-secondary'; if (($row['rating'] ?? '') === 'Excellent') { $ratingBadge = 'bg-success'; } elseif (($row['rating'] ?? '') === 'Good') { $ratingBadge = 'bg-primary'; } elseif (($row['rating'] ?? '') === 'Satisfactory') { $ratingBadge = 'bg-warning text-dark'; } elseif (($row['rating'] ?? '') === 'Needs Improvement') { $ratingBadge = 'bg-danger'; } ?>
                                    <span class="badge <?php echo $ratingBadge; ?>"><?php echo \App\Helpers\Security::escape($row['rating']); ?></span>
                                </td>
                                <td><?php
                                    $statusBadge = match ($row['status']) {
                                        'Completed' => 'bg-success',
                                        'Declined', 'Staff Disapproved' => 'bg-danger',
                                        'Staff Approved' => 'bg-info text-dark',
                                        'Commented' => 'bg-primary',
                                        default => 'bg-warning text-dark',
                                    };
                                    ?><span class="badge <?php echo $statusBadge; ?>"><?php echo \App\Helpers\Security::escape($row['status']); ?></span></td>
                                <td><small><?php echo \App\Helpers\Security::escape($row['supervisor_comment'] ?? '-'); ?></small></td>
                                <td><small><?php echo \App\Helpers\Security::escape($row['staff_approval_note'] ?? '-'); ?></small></td>
                                <td class="no-export">
                                    <div class="d-flex gap-1">
                                        <a href="<?php echo $appUrl; ?>/staff/appraisals/pdf?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-danger" title="PDF"><i class="fas fa-file-pdf"></i></a>
                                        <?php if ($row['status'] === 'Staff Approved'): ?>
                                        <button class="btn btn-sm btn-success" title="Finalize" data-bs-toggle="modal" data-bs-target="#finalizeModal<?php echo (int)$row['id']; ?>"><i class="fas fa-check-double"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $csrf = \App\Helpers\Security::generateCsrfToken(); ?>
<?php foreach ($rows as $row): if ($row['status'] === 'Staff Approved'): ?>
<div class="modal fade" id="finalizeModal<?php echo (int)$row['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?php echo $appUrl; ?>/staff/appraisals/finalize">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="appraisal_id" value="<?php echo (int)$row['id']; ?>">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Finalize Appraisal</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small mb-2"><strong>Staff:</strong> <?php echo \App\Helpers\Security::escape($row['fullname']); ?> — <?php echo \App\Helpers\Security::escape($row['period_label']); ?></p>
                    <p class="small mb-2"><strong>Score:</strong> <?php echo \App\Helpers\Security::escape((string)$row['score']); ?> — <strong>Rating:</strong> <?php echo \App\Helpers\Security::escape($row['rating']); ?></p>
                    <?php if (!empty($row['supervisor_comment'])): ?><p class="small mb-2"><strong>Supervisor:</strong> <?php echo \App\Helpers\Security::escape($row['supervisor_comment']); ?></p><?php endif; ?>
                    <?php if (!empty($row['staff_approval_note'])): ?><p class="small mb-2"><strong>Staff Response:</strong> <?php echo \App\Helpers\Security::escape($row['staff_approval_note']); ?></p><?php endif; ?>
                    <div class="mb-2">
                        <label class="form-label small">HR / Director Comment (optional)</label>
                        <textarea name="hr_comment" class="form-control form-control-sm" rows="2" placeholder="Final remarks"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check-double me-1"></i>Finalize</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; endforeach; ?>

<script>
$(document).ready(function() {
    if ($('#manageAppraisalTable').length) {
        $('#manageAppraisalTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No appraisal records found." },
            order: [[0, 'asc']]
        });
    }
});
</script>
