<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3">
                <i class="fas fa-bars"></i>
            </button>
            <span class="fw-bold">Manage Users</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <?php require __DIR__ . '/../dashboard/layouts/flash.php'; ?>

        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-3">Registered Users</h5>
            <div class="table-responsive">
                <table id="usersTable" class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark"><tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th class="no-export">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo (int)$user['id']; ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$user['fullname']); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$user['username']); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$user['email']); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($user['phone'] ?? '')); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$user['role_name']); ?></td>
                            <td><span class="badge bg-<?php echo (($user['status'] ?? '') === 'Active') ? 'success' : 'secondary'; ?>"><?php echo \App\Helpers\Security::escape((string)$user['status']); ?></span></td>
                            <td class="no-export">
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo (int)$user['id']; ?>"><i class="fas fa-edit"></i></button>
                                <form method="POST" action="<?php echo $appUrl; ?>/admin/users/toggle-status" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                                    <button class="btn btn-outline-<?php echo (($user['status'] ?? '') === 'Active') ? 'warning' : 'success'; ?> btn-sm" type="submit" title="<?php echo (($user['status'] ?? '') === 'Active') ? 'Deactivate' : 'Activate'; ?>"><i class="fas fa-<?php echo (($user['status'] ?? '') === 'Active') ? 'ban' : 'check'; ?>"></i></button>
                                </form>
                                <form method="POST" action="<?php echo $appUrl; ?>/admin/users/resend-password" class="d-inline" onsubmit="return confirm('Reset password for <?php echo \App\Helpers\Security::escape((string)$user['fullname']); ?> and send via SMS?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                                    <button class="btn btn-outline-info btn-sm" type="submit" title="Resend Password via SMS"><i class="fas fa-key"></i></button>
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
    if ($('#usersTable').length) {
        $('#usersTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No users found." },
            order: [[0, 'desc']]
        });
    }
});
</script>

<?php foreach ($users as $user): ?>
<div class="modal fade" id="editUserModal<?php echo (int)$user['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo $appUrl; ?>/admin/users/edit">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                <div class="modal-header"><h5 class="modal-title">Edit User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="fullname" class="form-control" value="<?php echo \App\Helpers\Security::escape((string)$user['fullname']); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo \App\Helpers\Security::escape((string)$user['email']); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?php echo \App\Helpers\Security::escape((string)($user['phone'] ?? '')); ?>"></div>
                    <div class="mb-3"><label class="form-label">Role</label>
                        <select name="role_id" class="form-select">
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo (int)$role['id']; ?>" <?php echo ((int)$user['role_id'] === (int)$role['id']) ? 'selected' : ''; ?>><?php echo \App\Helpers\Security::escape((string)$role['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Update</button></div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
