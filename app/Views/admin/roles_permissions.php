<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Roles & Permissions</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h5 class="fw-bold mb-3">Create Role</h5>
            <form method="POST" action="<?php echo $appUrl; ?>/admin/roles-permissions" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <div class="col-12 col-md-4"><label class="form-label">Role Name</label><input type="text" name="role_name" class="form-control form-control-sm" required></div>
                <div class="col-12 col-md-6"><label class="form-label">Permissions (comma-separated)</label><input type="text" name="permissions" class="form-control form-control-sm" placeholder="staff.view,staff.edit,leave.approve"></div>
                <div class="col-12 col-md-2 d-grid"><label class="form-label">&nbsp;</label><button class="btn btn-dark btn-sm" type="submit">Save Role</button></div>
            </form>
        </div>

        <?php require __DIR__ . '/../dashboard/layouts/flash.php'; ?>

        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-3">Role Matrix</h5>
            <div class="table-responsive">
                <table id="rolesTable" class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark"><tr><th>ID</th><th>Role</th><th>Permissions</th><th>Created</th><th class="no-export">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><?php echo (int)$role['id']; ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$role['role_name']); ?></td>
                            <td><small><?php echo \App\Helpers\Security::escape((string)($role['permissions'] ?? '')); ?></small></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($role['created_at'] ?? '')); ?></td>
                            <td class="no-export">
                                <button class="btn btn-outline-primary btn-sm" title="Edit" data-bs-toggle="modal" data-bs-target="#editRoleModal<?php echo (int)$role['id']; ?>">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php foreach ($roles as $role): ?>
<div class="modal fade" id="editRoleModal<?php echo (int)$role['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?php echo $appUrl; ?>/admin/roles/edit">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="id" value="<?php echo (int)$role['id']; ?>">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Edit Role</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Role Name</label>
                        <input type="text" name="role_name" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape((string)$role['role_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Permissions (comma-separated)</label>
                        <input type="text" name="permissions" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape((string)($role['permissions'] ?? '')); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
$(document).ready(function() {
    if ($('#rolesTable').length) {
        $('#rolesTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No roles found." },
            order: [[0, 'asc']],
            columnDefs: [{ targets: 'no-export', orderable: false }]
        });
    }
});
</script>
