<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
$leaveData = $leaveRows->fetchAll(PDO::FETCH_ASSOC);
$promotionData = $promotionRows->fetchAll(PDO::FETCH_ASSOC);
$appraisalData = $appraisalRows->fetchAll(PDO::FETCH_ASSOC);
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
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Approvals</li>
                </ol>
            </nav>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="row g-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm p-4 bg-white">
                    <h5 class="fw-bold mb-3"><i class="fas fa-calendar-check me-2 text-primary"></i>Pending Leave Approvals</h5>
                    <div class="table-responsive">
                        <table id="approvalsLeaveTable" class="table table-striped table-bordered align-middle">
                            <thead class="table-dark small text-uppercase">
                                <tr>
                                    <th>Staff</th>
                                    <th>Type</th>
                                    <th>Dates</th>
                                    <th>Status</th>
                                    <th class="no-export">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($leaveData as $row): ?>
                                <tr>
                                    <td><?php echo \App\Helpers\Security::escape((string)$row['fullname']); ?></td>
                                    <td><?php echo \App\Helpers\Security::escape((string)$row['leave_type']); ?></td>
                                    <td><?php echo \App\Helpers\Security::escape((string)$row['start_date']); ?> - <?php echo \App\Helpers\Security::escape((string)$row['end_date']); ?></td>
                                    <td><span class="badge bg-warning text-dark"><?php echo \App\Helpers\Security::escape((string)$row['status']); ?></span></td>
                                    <td class="no-export">
                                        <form method="POST" action="<?php echo $appUrl; ?>/staff/approvals/leave" class="d-flex gap-2">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="leave_id" value="<?php echo (int)$row['id']; ?>">
                                            <input type="text" name="comment" class="form-control form-control-sm" placeholder="Comment" required>
                                            <button class="btn btn-success btn-sm" name="status" value="Pending HR Approval">Approve</button>
                                            <button class="btn btn-danger btn-sm" name="status" value="Declined">Decline</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm p-4 bg-white h-100">
                    <h5 class="fw-bold mb-3"><i class="fas fa-arrow-trend-up me-2 text-primary"></i>Pending Promotion Comments</h5>
                    <div class="table-responsive">
                        <table id="approvalsPromotionTable" class="table table-striped table-bordered align-middle">
                            <thead class="table-dark small text-uppercase">
                                <tr><th>Staff</th><th>Current</th><th>Requested</th><th class="no-export">Action</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($promotionData as $row): ?>
                                <tr>
                                    <td><?php echo \App\Helpers\Security::escape((string)$row['fullname']); ?></td>
                                    <td><?php echo \App\Helpers\Security::escape((string)$row['current_rank']); ?></td>
                                    <td><?php echo \App\Helpers\Security::escape((string)$row['requested_rank']); ?></td>
                                    <td class="no-export">
                                        <form method="POST" action="<?php echo $appUrl; ?>/staff/approvals/promotion" class="d-flex gap-2">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="promotion_id" value="<?php echo (int)$row['id']; ?>">
                                            <input type="text" name="comment" class="form-control form-control-sm" placeholder="Comment" required>
                                            <button class="btn btn-success btn-sm" name="status" value="Pending HR Approval">Approve</button>
                                            <button class="btn btn-danger btn-sm" name="status" value="Declined">Decline</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm p-4 bg-white h-100">
                    <h5 class="fw-bold mb-3"><i class="fas fa-star-half-stroke me-2 text-primary"></i>Pending Appraisal Comments</h5>
                    <div class="table-responsive">
                        <table id="approvalsAppraisalTable" class="table table-striped table-bordered align-middle">
                            <thead class="table-dark small text-uppercase">
                                <tr><th>Staff</th><th>Period</th><th>Status</th><th class="no-export">Action</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($appraisalData as $row): ?>
                                <tr>
                                    <td><?php echo \App\Helpers\Security::escape((string)$row['fullname']); ?></td>
                                    <td><?php echo \App\Helpers\Security::escape((string)$row['period_label']); ?></td>
                                    <td><span class="badge bg-warning text-dark"><?php echo \App\Helpers\Security::escape((string)$row['status']); ?></span></td>
                                    <td class="no-export">
                                        <form method="POST" action="<?php echo $appUrl; ?>/staff/approvals/appraisal" class="d-flex gap-2">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="appraisal_id" value="<?php echo (int)$row['id']; ?>">
                                            <input type="text" name="comment" class="form-control form-control-sm" placeholder="Comment" required>
                                            <button class="btn btn-success btn-sm" name="status" value="Commented">Commented</button>
                                            <button class="btn btn-danger btn-sm" name="status" value="Declined">Decline</button>
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
    </div>
</div>

<script>
$(document).ready(function() {
    if ($('#approvalsLeaveTable').length) {
        $('#approvalsLeaveTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No pending leave approvals." },
            order: [[0, 'asc']]
        });
    }
    if ($('#approvalsPromotionTable').length) {
        $('#approvalsPromotionTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No pending promotion comments." },
            order: [[0, 'asc']]
        });
    }
    if ($('#approvalsAppraisalTable').length) {
        $('#approvalsAppraisalTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No pending appraisal comments." },
            order: [[0, 'asc']]
        });
    }
});
</script>
