<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Bank Transfer Report</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="get" action="<?php echo $appUrl; ?>/payroll/bank-transfer-report" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Payroll Period</label>
                        <select name="period_id" class="form-select" required>
                            <option value="">-- Select Processed Period --</option>
                            <?php foreach ($periods as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo ($periodId === (int)$p['id']) ? 'selected' : ''; ?>><?php echo \App\Helpers\Security::escape($p['period_label']); ?> (<?php echo $p['start_date']; ?> to <?php echo $p['end_date']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>View</button>
                    </div>
                    <?php if (!empty($rows)): ?>
                    <div class="col-md-2">
                        <a href="<?php echo $appUrl; ?>/payroll/bank-transfer-report?period_id=<?php echo $periodId; ?>&export=csv" class="btn btn-success w-100"><i class="fas fa-file-csv me-1"></i>Export CSV</a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if (!empty($rows)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0"><i class="fas fa-list me-2"></i>Payment Instructions</h5>
                <span class="badge bg-primary"><?php echo count($rows); ?> employees</span>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered align-middle m-0" id="transferTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Employee</th>
                                <th>Staff ID</th>
                                <th>Department</th>
                                <th>Payment Method</th>
                                <th>Bank / Provider</th>
                                <th>Branch</th>
                                <th>Account Name</th>
                                <th>Account / Number</th>
                                <th>Net Pay (GHS)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $totalNet = 0; ?>
                            <?php foreach ($rows as $r): ?>
                                <?php $hasBank = !empty($r['bank_name']) || !empty($r['mobile_money_number']); $totalNet += (float)$r['net_pay']; ?>
                            <tr class="<?php echo $hasBank ? '' : 'table-warning'; ?>">
                                <td><?php echo \App\Helpers\Security::escape($r['fullname']); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($r['staff_id_card'] ?? 'N/A'); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($r['dept_name'] ?? 'N/A'); ?></td>
                                <td><span class="badge bg-<?php echo $r['payment_method'] === 'bank_transfer' ? 'primary' : ($r['payment_method'] === 'mobile_money' ? 'success' : ($r['payment_method'] === 'direct_deposit' ? 'info' : 'secondary')); ?>"><?php echo str_replace('_', ' ', ucwords($r['payment_method'] ?? 'bank_transfer')); ?></span></td>
                                <td><?php echo \App\Helpers\Security::escape($r['bank_name'] ?: $r['mobile_money_provider'] ?: '-'); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($r['branch'] ?? '-'); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($r['account_name'] ?: '-'); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($r['account_number'] ?: $r['mobile_money_number'] ?: '-'); ?></td>
                                <td class="fw-bold"><?php echo number_format((float)$r['net_pay'], 2); ?></td>
                                <td>
                                    <?php if ($hasBank): ?>
                                        <span class="text-success small"><i class="fas fa-check-circle"></i> Ready</span>
                                    <?php else: ?>
                                        <span class="text-danger small"><i class="fas fa-exclamation-triangle"></i> Missing</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark fw-bold">
                                <td colspan="8" class="text-end">Total Net Pay:</td>
                                <td><?php echo number_format($totalNet, 2); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <?php elseif ($periodId > 0): ?>
        <div class="alert alert-info">No processed payroll data found for this period.</div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($('#transferTable').length) {
        $('#transferTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 50,
            language: { search: "Search:", emptyTable: "No payment data found." },
            order: [[0, 'asc']]
        });
    }
});
</script>
