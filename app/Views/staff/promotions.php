<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
$userRole = $_SESSION['user_role'] ?? 'Staff';
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
                    <li class="breadcrumb-item text-dark">Staff Services</li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Promotions</li>
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
                        <div class="metric-icon-box bg-primary-light text-primary me-3"><i class="fas fa-arrow-trend-up fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Total Records</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo (int)($summary['total_records'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-warning">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-warning-light text-warning me-3"><i class="fas fa-clock fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Proposed</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo (int)($summary['proposed_records'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-success">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-success-light text-success me-3"><i class="fas fa-check-circle fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Approved</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo (int)($summary['approved_records'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-danger">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-danger-light text-danger me-3"><i class="fas fa-ban fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Rejected</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo (int)($summary['rejected_records'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-lg p-4 bg-white">
            <h5 class="fw-bold mb-3"><i class="fas fa-list me-2 text-primary"></i>Promotion History</h5>
            <div class="table-responsive">
                <table id="managePromotionTable" class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark text-uppercase small">
                        <tr>
                            <th>Staff</th>
                            <th>Department</th>
                            <th>Current Rank</th>
                            <th>Requested Rank</th>
                            <th>Status</th>
                            <th class="no-export">Document</th>
                            <th>Notes</th>
                            <?php if (in_array($userRole, ['Super Admin', 'HR Manager'], true)): ?>
                            <th class="no-export text-center">Actions</th>
                            <?php endif; ?>
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
                                <td><?php echo \App\Helpers\Security::escape($row['current_rank'] ?? ''); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($row['requested_rank'] ?? ''); ?></td>
                                <td>
                                    <?php $badge = 'bg-secondary'; if (($row['status'] ?? '') === 'Approved') { $badge = 'bg-success'; } elseif (in_array(($row['status'] ?? ''), ['Rejected', 'Declined'], true)) { $badge = 'bg-danger'; } elseif (in_array(($row['status'] ?? ''), ['Pending Comments', 'Pending Supervisor Sign-off', 'Pending HR Approval'], true)) { $badge = 'bg-warning text-dark'; } ?>
                                    <span class="badge <?php echo $badge; ?>"><?php echo \App\Helpers\Security::escape($row['status']); ?></span>
                                </td>
                                <td class="no-export">
                                    <?php if (!empty($row['supporting_document'])): ?>
                                        <a href="<?php echo $appUrl . \App\Helpers\Security::escape($row['supporting_document']); ?>" target="_blank" class="btn btn-sm btn-outline-dark">View</a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo \App\Helpers\Security::escape($row['remarks'] ?? ''); ?></td>
                                <?php if (in_array($userRole, ['Super Admin', 'HR Manager'], true)): ?>
                                <td class="text-center">
                                    <?php $pendingStatuses = ['Pending Comments', 'Pending Supervisor Sign-off', 'Pending HR Approval']; ?>
                                    <?php if (in_array($row['status'] ?? '', $pendingStatuses, true)): ?>
                                        <button class="btn btn-success btn-sm me-1" data-bs-toggle="modal" data-bs-target="#approvePromoModal<?php echo (int)$row['id']; ?>"><i class="fas fa-check"></i></button>
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#declinePromoModal<?php echo (int)$row['id']; ?>"><i class="fas fa-times"></i></button>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
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
    if ($('#managePromotionTable').length) {
        $('#managePromotionTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No promotion records found." },
            order: [[0, 'asc']]
        });
    }
});
</script>

<?php if (in_array($userRole, ['Super Admin', 'HR Manager'], true)): ?>
<?php foreach ($rows as $row): ?>
<?php $pendingStatuses = ['Pending Comments', 'Pending Supervisor Sign-off', 'Pending HR Approval']; ?>
<?php if (in_array($row['status'] ?? '', $pendingStatuses, true)): ?>
<div class="modal fade" id="approvePromoModal<?php echo (int)$row['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo $appUrl; ?>/staff/promotions/review">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="promotion_id" value="<?php echo (int)$row['id']; ?>">
                <input type="hidden" name="action" value="Approved">
                <div class="modal-header"><h5 class="modal-title">Approve Promotion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p>Approve promotion for <strong><?php echo \App\Helpers\Security::escape($row['fullname']); ?></strong>?</p>
                    <div class="mb-3"><label class="form-label">Comment (optional)</label><textarea name="comment" class="form-control" rows="3"></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">Approve</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button></div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="declinePromoModal<?php echo (int)$row['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo $appUrl; ?>/staff/promotions/review">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="promotion_id" value="<?php echo (int)$row['id']; ?>">
                <input type="hidden" name="action" value="Declined">
                <div class="modal-header"><h5 class="modal-title">Decline Promotion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p>Decline promotion for <strong><?php echo \App\Helpers\Security::escape($row['fullname']); ?></strong>?</p>
                    <div class="mb-3"><label class="form-label">Reason <span class="text-danger">*</span></label><textarea name="comment" class="form-control" rows="3" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-danger">Decline</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button></div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>
