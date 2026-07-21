<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
$role = (string)($_SESSION['user_role'] ?? 'Staff');
?>

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Leave Management</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-primary">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-primary-light text-primary me-3"><i class="fas fa-calendar-check fa-2x"></i></div>
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
                        <div class="metric-icon-box bg-warning-light text-warning me-3"><i class="fas fa-hourglass-half fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Pending</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo (int)($summary['pending_records'] ?? 0); ?></h3>
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
                        <div class="metric-icon-box bg-danger-light text-danger me-3"><i class="fas fa-times-circle fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Rejected</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo (int)($summary['rejected_records'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-3">All Leave Applications</h5>
            <div class="table-responsive">
                <table id="manageLeaveTable" class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark text-uppercase small">
                        <tr>
                            <th>#</th>
                            <th>Staff</th>
                            <th>Department</th>
                            <th>Type</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Applied</th>
                            <th class="no-export">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $l): ?>
                            <tr>
                                <td><?php echo (int)$l['id']; ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo \App\Helpers\Security::escape((string)$l['fullname']); ?></div>
                                    <small class="text-muted font-monospace"><?php echo \App\Helpers\Security::escape((string)($l['staff_id_card'] ?? '')); ?></small>
                                </td>
                                <td><?php echo \App\Helpers\Security::escape((string)($l['dept_name'] ?? 'N/A')); ?></td>
                                <td><?php echo \App\Helpers\Security::escape((string)$l['leave_type']); ?></td>
                                <td><?php echo \App\Helpers\Security::escape((string)$l['start_date']); ?></td>
                                <td><?php echo \App\Helpers\Security::escape((string)$l['end_date']); ?></td>
                                <td><?php echo (int)$l['total_days']; ?></td>
                                <td><?php echo \App\Helpers\Security::escape((string)($l['reason'] ?? '')); ?></td>
                                <td>
                                    <?php
                                    $statusBadge = 'bg-secondary';
                                    if (($l['status'] ?? '') === 'Approved') { $statusBadge = 'bg-success'; }
                                    elseif (in_array(($l['status'] ?? ''), ['Rejected', 'Declined'], true)) { $statusBadge = 'bg-danger'; }
                                    elseif (in_array(($l['status'] ?? ''), ['Pending Comments', 'Pending Supervisor Sign-off', 'Pending HR Approval'], true)) { $statusBadge = 'bg-warning text-dark'; }
                                    ?>
                                    <span class="badge <?php echo $statusBadge; ?>"><?php echo \App\Helpers\Security::escape((string)$l['status']); ?></span>
                                </td>
                                <td><?php echo \App\Helpers\Security::escape((string)($l['created_at'] ?? '')); ?></td>
                                <td class="no-export">
                                    <?php if ($role === 'Supervisor' && in_array($l['status'], ['Pending Supervisor Sign-off', 'Pending Comments'], true)): ?>
                                        <form method="POST" action="<?php echo $appUrl; ?>/staff/leave/review" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="leave_id" value="<?php echo (int)$l['id']; ?>">
                                            <input type="hidden" name="status" value="Pending HR Approval">
                                            <button class="btn btn-sm btn-success" title="Approve as Supervisor"><i class="fas fa-check"></i></button>
                                        </form>
                                        <form method="POST" action="<?php echo $appUrl; ?>/staff/leave/review" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="leave_id" value="<?php echo (int)$l['id']; ?>">
                                            <input type="hidden" name="status" value="Declined">
                                            <button class="btn btn-sm btn-danger" title="Decline"><i class="fas fa-times"></i></button>
                                        </form>
                                    <?php elseif (in_array($role, ['Super Admin', 'HR Manager'], true) && in_array($l['status'], ['Pending HR Approval', 'Pending Supervisor Sign-off', 'Pending Comments'], true)): ?>
                                        <form method="POST" action="<?php echo $appUrl; ?>/staff/leave/review" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="leave_id" value="<?php echo (int)$l['id']; ?>">
                                            <input type="hidden" name="status" value="Approved">
                                            <button class="btn btn-sm btn-success" title="Approve"><i class="fas fa-check"></i></button>
                                        </form>
                                        <form method="POST" action="<?php echo $appUrl; ?>/staff/leave/review" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="leave_id" value="<?php echo (int)$l['id']; ?>">
                                            <input type="hidden" name="status" value="Rejected">
                                            <button class="btn btn-sm btn-danger" title="Reject"><i class="fas fa-times"></i></button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
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

<script>
$(document).ready(function() {
    if ($('#manageLeaveTable').length) {
        $('#manageLeaveTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No leave records found." },
            order: [[0, 'desc']]
        });
    }
});
</script>
