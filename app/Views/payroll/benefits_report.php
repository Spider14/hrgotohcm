<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$categories = ['housing' => 'Housing', 'transport' => 'Transport', 'medical' => 'Medical', 'other' => 'Other'];
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Benefits Report</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0">Benefit Cost Report</h5>
            <?php if (!empty($rows)): ?>
                <a href="<?php echo $appUrl; ?>/payroll/benefits/report?export=csv" class="btn btn-sm btn-success"><i class="fas fa-file-csv me-1"></i>Export CSV</a>
            <?php endif; ?>
        </div>

        <?php if (empty($rows)): ?>
            <div class="card border-0 shadow-sm p-4 text-center">
                <p class="text-muted m-0">No benefits assigned yet. <a href="<?php echo $appUrl; ?>/payroll/benefits">Assign benefits</a> to employees first.</p>
            </div>
        <?php else: ?>
            <div class="row g-3 mb-4">
                <?php foreach ($grandTotal as $cat => $total): ?>
                    <?php if ($cat === 'overall') continue; ?>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="card border-0 shadow-sm p-3 text-center h-100">
                            <small class="text-muted text-uppercase"><?php echo \App\Helpers\Security::escape($categories[$cat] ?? $cat); ?> Total</small>
                            <h4 class="fw-bold m-0">GHS <?php echo number_format($total, 2); ?></h4>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card border-0 shadow-sm p-3 text-center h-100 bg-light">
                        <small class="text-muted text-uppercase">Overall Total</small>
                        <h4 class="fw-bold m-0">GHS <?php echo number_format($grandTotal['overall'] ?? 0, 2); ?></h4>
                    </div>
                </div>
            </div>

            <?php foreach ($rows as $dept => $data): ?>
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-building-columns me-2 text-primary"></i><?php echo \App\Helpers\Security::escape($dept); ?></h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-bordered align-middle">
                            <thead class="table-dark">
                                <tr><th>Employee</th><th>Staff ID</th><th>Benefits</th><th>Total (GHS)</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($data['employees'] as $emp): ?>
                                <tr>
                                    <td><?php echo \App\Helpers\Security::escape($emp['fullname']); ?></td>
                                    <td><?php echo \App\Helpers\Security::escape($emp['staff_id_card']); ?></td>
                                    <td>
                                        <?php foreach ($emp['benefits'] as $b): ?>
                                            <span class="badge bg-info me-1"><?php echo \App\Helpers\Security::escape($b['benefit_name']); ?>: GHS <?php echo number_format((float)$b['amount'], 2); ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td class="fw-bold"><?php echo number_format($emp['total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light fw-bold">
                                    <td colspan="3" class="text-end">Department Totals:</td>
                                    <td>
                                        <?php
                                        $deptTotal = 0;
                                        foreach ($data['employees'] as $emp) { $deptTotal += $emp['total']; }
                                        echo number_format($deptTotal, 2);
                                        ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
