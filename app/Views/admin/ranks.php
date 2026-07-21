<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Job Ranks</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h5 class="fw-bold mb-3">Create Rank</h5>
            <form method="POST" action="<?php echo $appUrl; ?>/admin/ranks" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <div class="col-12 col-md-4"><label class="form-label">Rank Name</label><input type="text" name="rank_name" class="form-control form-control-sm" required></div>
                <div class="col-12 col-md-2"><label class="form-label">Order</label><input type="number" name="rank_order" class="form-control form-control-sm" value="0" min="0"></div>
                <div class="col-12 col-md-2"><label class="form-label">Salary (GHS)</label><input type="number" step="0.01" name="salary" class="form-control form-control-sm" value="0.00" min="0"></div>
                <div class="col-12 col-md-2"><label class="form-label">Leave Days</label><input type="number" name="leave_days" class="form-control form-control-sm" value="20" min="0"></div>
                <div class="col-12 col-md-3"><label class="form-label">Other Benefits</label><input type="text" name="other_benefits" class="form-control form-control-sm" placeholder="e.g. Housing, Transport"></div>
                <div class="col-12 d-flex justify-content-end"><button class="btn btn-dark btn-sm px-4" type="submit">Save Rank</button></div>
            </form>
        </div>

        <?php require __DIR__ . '/../dashboard/layouts/flash.php'; ?>

        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-3">All Ranks</h5>
            <div class="table-responsive">
                <table id="ranksTable" class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark"><tr><th>#</th><th>Rank Name</th><th>Order</th><th>Salary (GHS)</th><th>Leave Days</th><th>Other Benefits</th><th>Status</th><th>Created</th><th class="no-export">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($ranks as $r): ?>
                        <tr>
                            <td><?php echo (int)$r['id']; ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$r['rank_name']); ?></td>
                            <td><?php echo (int)$r['rank_order']; ?></td>
                            <td><?php echo number_format((float)($r['salary'] ?? 0), 2); ?></td>
                            <td><?php echo (int)($r['leave_days'] ?? 0); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($r['other_benefits'] ?? '')); ?></td>
                            <td><span class="badge bg-<?php echo ((int)($r['is_active'] ?? 0) === 1) ? 'success' : 'secondary'; ?>"><?php echo ((int)($r['is_active'] ?? 0) === 1) ? 'Active' : 'Inactive'; ?></span></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($r['created_at'] ?? '')); ?></td>
                            <td class="no-export">
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editRankModal<?php echo (int)$r['id']; ?>"><i class="fas fa-edit"></i></button>
                                <form method="POST" action="<?php echo $appUrl; ?>/admin/ranks/delete" class="d-inline" onsubmit="return confirm('Delete this rank?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                    <button class="btn btn-outline-danger btn-sm" type="submit"><i class="fas fa-trash"></i></button>
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
    if ($('#ranksTable').length) {
        $('#ranksTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No ranks found." },
            order: [[0, 'asc']]
        });
    }
});
</script>

<?php foreach ($ranks as $r): ?>
<div class="modal fade" id="editRankModal<?php echo (int)$r['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo $appUrl; ?>/admin/ranks/edit">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                <div class="modal-header"><h5 class="modal-title">Edit Rank</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Rank Name</label><input type="text" name="rank_name" class="form-control" value="<?php echo \App\Helpers\Security::escape((string)$r['rank_name']); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Order</label><input type="number" name="rank_order" class="form-control" value="<?php echo (int)$r['rank_order']; ?>" min="0"></div>
                    <div class="mb-3"><label class="form-label">Salary (GHS)</label><input type="number" step="0.01" name="salary" class="form-control" value="<?php echo (float)($r['salary'] ?? 0); ?>" min="0"></div>
                    <div class="mb-3"><label class="form-label">Leave Days</label><input type="number" name="leave_days" class="form-control" value="<?php echo (int)($r['leave_days'] ?? 0); ?>" min="0"></div>
                    <div class="mb-3"><label class="form-label">Other Benefits</label><input type="text" name="other_benefits" class="form-control" value="<?php echo \App\Helpers\Security::escape((string)($r['other_benefits'] ?? '')); ?>"></div>
                    <div class="mb-3"><label class="form-label">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="1" <?php echo ((int)($r['is_active'] ?? 0) === 1) ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo ((int)($r['is_active'] ?? 0) === 0) ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Update</button></div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
