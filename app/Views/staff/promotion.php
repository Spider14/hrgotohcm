<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Apply for Promotion</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1200px;">
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h5 class="fw-bold mb-3">Promotion Request Form</h5>
            <form method="POST" action="<?php echo $appUrl; ?>/staff/promotion" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <div class="col-12 col-md-6">
                    <label class="form-label">Current Rank</label>
                    <input type="text" name="current_rank" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($currentRank); ?>" readonly>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Requested Rank</label>
                    <select name="requested_rank" class="form-select form-select-sm" required>
                        <option value="">-- Select Rank --</option>
                        <?php foreach ($ranks as $r): ?>
                            <option value="<?php echo \App\Helpers\Security::escape((string)$r['rank_name']); ?>"><?php echo \App\Helpers\Security::escape((string)$r['rank_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Justification / Remarks</label>
                    <textarea name="remarks" class="form-control form-control-sm" rows="4" required></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Supporting Document (PDF, JPG, PNG)</label>
                    <input type="file" name="supporting_document" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="col-12 d-grid d-md-flex justify-content-md-end">
                    <button class="btn btn-primary px-4" type="submit">Submit Promotion Request</button>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-3">My Promotion Requests</h5>
            <div class="table-responsive">
                <table id="promotionTable" class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark"><tr><th>#</th><th>Current Rank</th><th>Requested Rank</th><th>Status</th><th>Supervisor Remarks</th><th>HR Remarks</th><th>Applied</th><th class="no-export">Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($promotionRows as $p): ?>
                        <tr>
                            <td><?php echo (int)$p['id']; ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$p['current_rank']); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$p['requested_rank']); ?></td>
                            <td><span class="badge bg-<?php echo $p['status'] === 'Approved' ? 'success' : ($p['status'] === 'Declined' ? 'danger' : 'warning'); ?>"><?php echo \App\Helpers\Security::escape((string)$p['status']); ?></span></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($p['supervisor_comment'] ?? '-')); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($p['hr_comment'] ?? '-')); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($p['created_at'] ?? '')); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info text-white" title="View" data-bs-toggle="modal" data-bs-target="#viewPromoModal<?php echo (int)$p['id']; ?>"><i class="fas fa-eye"></i></button>
                                <?php if (!empty($p['supporting_document'])): ?>
                                    <a href="<?php echo $appUrl . \App\Helpers\Security::escape((string)$p['supporting_document']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Download"><i class="fas fa-download"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php foreach ($promotionRows as $p): ?>
<div class="modal fade" id="viewPromoModal<?php echo (int)$p['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Promotion Request #<?php echo (int)$p['id']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6"><strong>Current Rank:</strong><br><?php echo \App\Helpers\Security::escape((string)$p['current_rank']); ?></div>
                    <div class="col-6"><strong>Requested Rank:</strong><br><?php echo \App\Helpers\Security::escape((string)$p['requested_rank']); ?></div>
                    <div class="col-12"><strong>Status:</strong><br><span class="badge bg-<?php echo $p['status'] === 'Approved' ? 'success' : ($p['status'] === 'Declined' ? 'danger' : 'warning'); ?> fs-6"><?php echo \App\Helpers\Security::escape((string)$p['status']); ?></span></div>
                    <div class="col-12"><strong>Justification / Remarks:</strong><br><?php echo nl2br(\App\Helpers\Security::escape((string)($p['remarks'] ?? '-'))); ?></div>
                    <div class="col-6"><strong>Supervisor Comment:</strong><br><?php echo nl2br(\App\Helpers\Security::escape((string)($p['supervisor_comment'] ?? '-'))); ?></div>
                    <div class="col-6"><strong>HR Comment:</strong><br><?php echo nl2br(\App\Helpers\Security::escape((string)($p['hr_comment'] ?? '-'))); ?></div>
                    <div class="col-6"><strong>Applied:</strong><br><?php echo \App\Helpers\Security::escape((string)($p['created_at'] ?? '-')); ?></div>
                    <div class="col-6">
                        <strong>Supporting Document:</strong><br>
                        <?php if (!empty($p['supporting_document'])): ?>
                            <a href="<?php echo $appUrl . \App\Helpers\Security::escape((string)$p['supporting_document']); ?>" target="_blank" class="btn btn-sm btn-primary"><i class="fas fa-download"></i> Download</a>
                        <?php else: ?>
                            <span class="text-muted">None</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
$(document).ready(function() {
    if ($('#promotionTable').length) {
        $('#promotionTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 25,
            language: { search: "Search:", emptyTable: "No promotion requests found." },
            order: [[0, 'desc']]
        });
    }
});
</script>
