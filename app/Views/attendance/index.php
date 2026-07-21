<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Attendance Dashboard</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0">Today - <?php echo date('l, F j, Y'); ?></h5>
            <div>
                <a href="<?php echo $appUrl; ?>/admin/attendance/register" class="btn btn-sm btn-outline-primary me-2"><i class="fas fa-list me-1"></i>Full Register</a>
                <a href="<?php echo $appUrl; ?>/admin/attendance/reports" class="btn btn-sm btn-outline-secondary me-2"><i class="fas fa-chart-bar me-1"></i>Reports</a>
                <a href="<?php echo $appUrl; ?>/admin/attendance/settings" class="btn btn-sm btn-outline-dark"><i class="fas fa-cog me-1"></i>Settings</a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 h-100 border-start border-4 border-primary">
                    <div class="d-flex align-items-center">
                        <div class="me-3 text-primary"><i class="fas fa-users fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase text-muted small fw-bold d-block">Total Staff</span>
                            <h3 class="fw-extrabold m-0 mt-1"><?php echo (int)($stats['total'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 h-100 border-start border-4 border-success">
                    <div class="d-flex align-items-center">
                        <div class="me-3 text-success"><i class="fas fa-check-circle fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase text-muted small fw-bold d-block">Present</span>
                            <h3 class="fw-extrabold m-0 mt-1"><?php echo (int)($stats['present'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 h-100 border-start border-4 border-warning">
                    <div class="d-flex align-items-center">
                        <div class="me-3 text-warning"><i class="fas fa-clock fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase text-muted small fw-bold d-block">Late</span>
                            <h3 class="fw-extrabold m-0 mt-1"><?php echo (int)($stats['late'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 h-100 border-start border-4 border-danger">
                    <div class="d-flex align-items-center">
                        <div class="me-3 text-danger"><i class="fas fa-times-circle fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase text-muted small fw-bold d-block">Absent</span>
                            <h3 class="fw-extrabold m-0 mt-1"><?php echo (int)($stats['absent'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold m-0"><i class="fas fa-list me-2 text-primary"></i>Today's Attendance</h6>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="attendanceDashboardTable" class="table table-sm table-striped table-bordered align-middle">
                        <thead class="table-dark">
                            <tr><th>#</th><th>Staff ID</th><th>Name</th><th>Department</th><th>Clock In</th><th>Clock Out</th><th>Duration</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php if (empty($todayRows)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-3">No attendance records for today</td></tr>
                        <?php else: $i = 0; ?>
                            <?php foreach ($todayRows as $a): $i++; ?>
                                <?php
                                $duration = '';
                                if ($a['clock_in'] && $a['clock_out']) {
                                    $diff = strtotime($a['clock_out']) - strtotime($a['clock_in']);
                                    $h = intdiv($diff, 3600);
                                    $m = intdiv($diff % 3600, 60);
                                    $duration = $h . 'h ' . $m . 'm';
                                }
                                $sClass = $a['status'] === 'present' ? 'success' : ($a['status'] === 'late' ? 'warning text-dark' : ($a['status'] === 'half-day' ? 'info' : 'danger'));
                                ?>
                                <tr>
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo \App\Helpers\Security::escape($a['staff_id_card'] ?? '-'); ?></td>
                                    <td><?php echo \App\Helpers\Security::escape($a['fullname'] ?? ''); ?></td>
                                    <td><?php echo \App\Helpers\Security::escape($a['dept_name'] ?? '-'); ?></td>
                                    <td><?php echo $a['clock_in'] ? date('H:i', strtotime($a['clock_in'])) : '-'; ?></td>
                                    <td><?php echo $a['clock_out'] ? date('H:i', strtotime($a['clock_out'])) : '<span class="badge bg-warning text-dark">Active</span>'; ?></td>
                                    <td><?php echo $duration ?: '-'; ?></td>
                                    <td><span class="badge bg-<?php echo $sClass; ?>"><?php echo ucfirst($a['status']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($('#attendanceDashboardTable').length) {
        $('#attendanceDashboardTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 25,
            ordering: true,
            columnDefs: [{ orderable: false, targets: [0] }],
            language: { search: "Search:", emptyTable: "No attendance records for today." }
        });
    }
});
</script>
