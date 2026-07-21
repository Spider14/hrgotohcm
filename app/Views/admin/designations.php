<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Designations</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>
    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h5 class="fw-bold mb-3">Create Designation</h5>
            <form method="POST" action="<?php echo $appUrl; ?>/admin/designations" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <div class="col-12 col-md-6"><label class="form-label">Designation Title</label><input type="text" name="title" class="form-control form-control-sm" required></div>
                <div class="col-12 col-md-4"><label class="form-label">Category</label><input type="text" name="category" class="form-control form-control-sm" placeholder="e.g. Academic, Administrative"></div>
                <div class="col-12 d-grid d-md-flex justify-content-md-end"><button class="btn btn-dark btn-sm" type="submit">Save Designation</button></div>
            </form>
        </div>

        <?php require __DIR__ . '/../dashboard/layouts/flash.php'; ?>

        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-3">All Designations</h5>
            <div class="table-responsive">
                <table id="designationsTable" class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark"><tr><th>#</th><th>Title</th><th>Category</th><th>Created</th><th class="no-export">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($designations as $d): ?>
                        <tr>
                            <td><?php echo (int)$d['id']; ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$d['title']); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($d['category'] ?? '')); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($d['created_at'] ?? '')); ?></td>
                            <td class="no-export">
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editDesigModal<?php echo (int)$d['id']; ?>"><i class="fas fa-edit"></i></button>
                                <form method="POST" action="<?php echo $appUrl; ?>/admin/designations/delete" class="d-inline" onsubmit="return confirm('Delete this designation?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
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
    if ($('#designationsTable').length) {
        $('#designationsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No designations found." },
            order: [[0, 'asc']]
        });
    }
});
</script>

<?php foreach ($designations as $d): ?>
<div class="modal fade" id="editDesigModal<?php echo (int)$d['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo $appUrl; ?>/admin/designations/edit">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
                <div class="modal-header"><h5 class="modal-title">Edit Designation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Designation Title</label><input type="text" name="title" class="form-control" value="<?php echo \App\Helpers\Security::escape((string)$d['title']); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Category</label><input type="text" name="category" class="form-control" value="<?php echo \App\Helpers\Security::escape((string)($d['category'] ?? '')); ?>"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Update</button></div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
