<?php
declare(strict_types=1);

$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Process Payroll</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="get" action="<?php echo $appUrl; ?>/payroll/process" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Select Payroll Period</label>
                        <select name="period_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Choose Period --</option>
<?php foreach ($periods as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo $periodId === (int)$p['id'] ? 'selected' : ''; ?>>
                                <?php echo \App\Helpers\Security::escape($p['period_label']); ?> (<?php echo ucfirst($p['status']); ?>)
                            </option>
<?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <noscript><button type="submit" class="btn btn-primary">Load</button></noscript>
                    </div>
                </form>
            </div>
        </div>

<?php if ($periodId > 0): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0"><i class="fas fa-calculator me-2"></i>Payroll Results</h5>
                <form method="post" action="<?php echo $appUrl; ?>/payroll/process/run" onsubmit="return confirm('Process payroll for this period? Existing runs will be updated.');">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="period_id" value="<?php echo $periodId; ?>">
                    <button type="submit" class="btn btn-success"><i class="fas fa-play me-1"></i>Run Payroll</button>
                </form>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered align-middle m-0" id="processTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Staff ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Gross Pay</th>
                                <th>Allowances</th>
                                <th>Deductions</th>
                                <th>SSNIT (Emp)</th>
                                <th>PAYE Tax</th>
                                <th>Net Pay</th>
                            </tr>
                        </thead>
                        <tbody>
<?php foreach ($runs as $r): ?>
                            <tr>
                                <td><?php echo \App\Helpers\Security::escape($r['staff_id_card'] ?? 'N/A'); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($r['fullname']); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($r['dept_name'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format((float)$r['gross_pay'], 2); ?></td>
                                <td><?php echo number_format((float)$r['total_allowances'], 2); ?></td>
                                <td><?php echo number_format((float)$r['total_deductions'], 2); ?></td>
                                <td><?php echo number_format((float)$r['ssnit_employee'], 2); ?></td>
                                <td><?php echo number_format((float)$r['paye_tax'], 2); ?></td>
                                <td><strong><?php echo number_format((float)$r['net_pay'], 2); ?></strong></td>
                            </tr>
<?php endforeach; ?>
<?php if (empty($runs)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-3">No results. Click <strong>Run Payroll</strong> to process.</td></tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
<?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function () {
    if ($('#processTable').length) {
        $('#processTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 25,
            language: { search: "Search:", emptyTable: "No payroll data found." },
            order: [[1, 'asc']]
        });
    }
});
</script>
