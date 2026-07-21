<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
$settings = $settings ?? [];
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Attendance Register</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm p-3 mb-4">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape($_GET['date'] ?? date('Y-m-d')); ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label small">Department</label>
                    <select name="dept_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?php echo $d['dept_id']; ?>" <?php echo ($_GET['dept_id'] ?? '') == $d['dept_id'] ? 'selected' : ''; ?>><?php echo \App\Helpers\Security::escape($d['dept_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="present" <?php echo ($_GET['status'] ?? '') === 'present' ? 'selected' : ''; ?>>Present</option>
                        <option value="late" <?php echo ($_GET['status'] ?? '') === 'late' ? 'selected' : ''; ?>>Late</option>
                        <option value="half-day" <?php echo ($_GET['status'] ?? '') === 'half-day' ? 'selected' : ''; ?>>Half Day</option>
                        <option value="absent" <?php echo ($_GET['status'] ?? '') === 'absent' ? 'selected' : ''; ?>>Absent</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-filter me-1"></i>Filter</button>
                    <a href="<?php echo $appUrl; ?>/admin/attendance/register" class="btn btn-sm btn-outline-secondary"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold m-0">
                    <i class="fas fa-calendar-day me-2 text-primary"></i>
                    Register for <?php echo date('F j, Y', strtotime($_GET['date'] ?? date('Y-m-d'))); ?>
                </h6>
                <span class="badge bg-secondary"><?php echo count($rows); ?> records</span>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="attendanceRegisterTable" class="table table-sm table-striped table-bordered align-middle">
                        <thead class="table-dark">
                            <tr><th>#</th><th>Staff ID</th><th>Name</th><th>Email</th><th>Department</th><th>Designation</th><th>Clock In</th><th>Clock Out</th><th>Duration</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="10" class="text-center text-muted py-3">No records found</td></tr>
                        <?php else: $i = 0; ?>
                            <?php foreach ($rows as $a): $i++; ?>
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
                                    <td><?php echo \App\Helpers\Security::escape($a['email'] ?? ''); ?></td>
                                    <td><?php echo \App\Helpers\Security::escape($a['dept_name'] ?? '-'); ?></td>
                                    <td><?php echo \App\Helpers\Security::escape($a['designation'] ?? '-'); ?></td>
                                    <td><?php echo $a['clock_in'] ? date('H:i', strtotime($a['clock_in'])) : '-'; ?></td>
                                    <td><?php echo $a['clock_out'] ? date('H:i', strtotime($a['clock_out'])) : '-'; ?></td>
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
    if ($('#attendanceRegisterTable').length) {
        $('#attendanceRegisterTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 25,
            ordering: true,
            columnDefs: [{ orderable: false, targets: [0] }]
        });
    }
});
</script>
