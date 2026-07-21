<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Departments</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h5 class="fw-bold mb-3">Create Department</h5>
            <form method="POST" action="<?php echo $appUrl; ?>/admin/departments" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <div class="col-12 col-md-3"><label class="form-label">Department Code</label><input type="text" name="dept_code" class="form-control form-control-sm" required></div>
                <div class="col-12 col-md-5"><label class="form-label">Department Name</label><input type="text" name="dept_name" class="form-control form-control-sm" required></div>
                <div class="col-12 col-md-4"><label class="form-label">Parent Unit</label><input type="text" name="parent_unit" class="form-control form-control-sm" placeholder="Directorate/Division"></div>
                <div class="col-12 d-grid d-md-flex justify-content-md-end"><button class="btn btn-dark btn-sm" type="submit">Save Department</button></div>
            </form>
        </div>

        <?php require __DIR__ . '/../dashboard/layouts/flash.php'; ?>

        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-3">Department Directory</h5>
            <div class="table-responsive">
                <table id="departmentsTable" class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark"><tr><th>ID</th><th>Code</th><th>Name</th><th>Parent Unit</th><th>Created</th><th class="no-export">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($departments as $dept): ?>
                        <tr>
                            <td><?php echo (int)$dept['dept_id']; ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$dept['dept_code']); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$dept['dept_name']); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($dept['parent_unit'] ?? '')); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($dept['created_at'] ?? '')); ?></td>
                            <td class="no-export">
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editDeptModal<?php echo (int)$dept['dept_id']; ?>"><i class="fas fa-edit"></i></button>
                                <form method="POST" action="<?php echo $appUrl; ?>/admin/departments/delete" class="d-inline" onsubmit="return confirm('Delete this department?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                    <input type="hidden" name="dept_id" value="<?php echo (int)$dept['dept_id']; ?>">
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
    if ($('#departmentsTable').length) {
        $('#departmentsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No departments found." },
            order: [[0, 'asc']]
        });
    }
});
</script>

<?php foreach ($departments as $dept): ?>
<div class="modal fade" id="editDeptModal<?php echo (int)$dept['dept_id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo $appUrl; ?>/admin/departments/edit">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="dept_id" value="<?php echo (int)$dept['dept_id']; ?>">
                <div class="modal-header"><h5 class="modal-title">Edit Department</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Department Code</label><input type="text" name="dept_code" class="form-control" value="<?php echo \App\Helpers\Security::escape((string)$dept['dept_code']); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Department Name</label><input type="text" name="dept_name" class="form-control" value="<?php echo \App\Helpers\Security::escape((string)$dept['dept_name']); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Parent Unit</label><input type="text" name="parent_unit" class="form-control" value="<?php echo \App\Helpers\Security::escape((string)($dept['parent_unit'] ?? '')); ?>"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Update</button></div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
