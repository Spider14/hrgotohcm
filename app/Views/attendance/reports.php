<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
$settings = $settings ?? [];
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Attendance Reports</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small">Month</label>
                        <select name="month" class="form-select form-select-sm">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo str_pad((string)$m, 2, '0', STR_PAD_LEFT); ?>" <?php echo ($_GET['month'] ?? date('m')) === str_pad((string)$m, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small">Year</label>
                        <select name="year" class="form-select form-select-sm">
                            <?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y'); $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo ($_GET['year'] ?? date('Y')) == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
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
                        <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-filter me-1"></i>Generate</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold m-0">
                    <i class="fas fa-file-alt me-2 text-primary"></i>
                    Monthly Summary - <?php echo date('F Y', strtotime(sprintf('%s-%s-01', $_GET['year'] ?? date('Y'), $_GET['month'] ?? date('m')))); ?>
                </h6>
                <span class="badge bg-secondary"><?php echo count($reportRows); ?> staff</span>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table id="attendanceReportTable" class="table table-sm table-striped table-bordered align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th><th>Staff ID</th><th>Name</th><th>Department</th>
                                <th>Total Days</th><th>Present</th><th>Late</th><th>Half Day</th><th>Absent</th>
                                <th>Total Hours</th><th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($reportRows)): $i = 0; ?>
                            <?php foreach ($reportRows as $r): $i++; ?>
                                <?php
                                $total = (int)($r['total_days'] ?? 1);
                                $present = (int)($r['present_days'] ?? 0);
                                $pct = round(($present / max($total, 1)) * 100);
                                $pctClass = $pct >= 90 ? 'success' : ($pct >= 75 ? 'warning text-dark' : 'danger');
                                ?>
                                <tr>
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo \App\Helpers\Security::escape($r['staff_id_card'] ?? '-'); ?></td>
                                    <td><?php echo \App\Helpers\Security::escape($r['fullname'] ?? ''); ?></td>
                                    <td><?php echo \App\Helpers\Security::escape($r['dept_name'] ?? '-'); ?></td>
                                    <td><?php echo $total; ?></td>
                                    <td><?php echo $present; ?></td>
                                    <td><?php echo (int)($r['late_days'] ?? 0); ?></td>
                                    <td><?php echo (int)($r['half_days'] ?? 0); ?></td>
                                    <td><?php echo (int)($r['absent_days'] ?? 0); ?></td>
                                    <td><?php echo $r['total_hours'] ?? '-'; ?></td>
                                    <td><span class="badge bg-<?php echo $pctClass; ?>"><?php echo $pct; ?>%</span></td>
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
    if ($('#attendanceReportTable').length) {
        $('#attendanceReportTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 25,
            ordering: true,
            columnDefs: [{ orderable: false, targets: [0] }],
            language: { search: "Search:", emptyTable: "No attendance data found for the selected period." }
        });
    }
});
</script>
