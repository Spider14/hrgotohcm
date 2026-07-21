<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$colorPalette = ['#2b6cb0','#2f855a','#d69e2e','#c53030','#805ad5','#dd6b20','#319795','#d53f8c','#718096','#38a169'];
$deptColors = [];
$ci = 0;
foreach ($deptNames as $dn) {
    $deptColors[$dn] = $colorPalette[$ci % count($colorPalette)];
    $ci++;
}
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Payroll Analysis & Forecasting</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
<?php if (count($processedPeriods) < 2): ?>
        <div class="alert alert-info">At least 2 processed payroll periods are required for analysis.</div>
<?php else: ?>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small text-uppercase fw-semibold">Periods Analyzed</div>
                                <div class="h3 mb-0"><?php echo count($processedPeriods); ?></div>
                            </div>
                            <i class="fas fa-calendar-alt fa-2x text-primary opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small text-uppercase fw-semibold">Departments</div>
                                <div class="h3 mb-0"><?php echo count($deptNames); ?></div>
                            </div>
                            <i class="fas fa-building fa-2x text-success opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small text-uppercase fw-semibold">Avg Cost / Period</div>
                                <div class="h5 mb-0">GHS <?php echo number_format(count($periodTotals) > 0 ? array_sum(array_column($periodTotals, 'net')) / count($periodTotals) : 0, 2); ?></div>
                            </div>
                            <i class="fas fa-chart-line fa-2x text-info opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small text-uppercase fw-semibold">Total Attrition</div>
                                <div class="h3 mb-0"><?php echo array_sum(array_column($attritionData, 'count')); ?></div>
                            </div>
                            <i class="fas fa-user-minus fa-2x text-danger opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0"><i class="fas fa-chart-bar me-2"></i>Department Cost Trends — Gross Pay by Period</h5>
            </div>
            <div class="card-body">
                <canvas id="deptTrendChart" height="320"></canvas>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0"><i class="fas fa-chart-line me-2"></i>Budget Forecast — Net Pay Trend & Projection</h5>
            </div>
            <div class="card-body">
                <canvas id="forecastChart" height="280"></canvas>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="fw-bold m-0"><i class="fas fa-table me-2"></i>Period-over-Period Totals</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered align-middle m-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Period</th>
                                        <th>Gross Pay</th>
                                        <th>Deductions</th>
                                        <th>Tax (PAYE+SSNIT)</th>
                                        <th>Net Pay</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php foreach ($periodTotals as $pt): ?>
                                    <tr>
                                        <td><?php echo \App\Helpers\Security::escape($pt['period_label']); ?></td>
                                        <td>GHS <?php echo number_format($pt['gross'], 2); ?></td>
                                        <td>GHS <?php echo number_format($pt['deductions'], 2); ?></td>
                                        <td>GHS <?php echo number_format($pt['tax'], 2); ?></td>
                                        <td><strong>GHS <?php echo number_format($pt['net'], 2); ?></strong></td>
                                    </tr>
<?php endforeach; ?>
<?php foreach ($forecast as $fc): ?>
                                    <tr class="table-info">
                                        <td><em><?php echo \App\Helpers\Security::escape($fc['label']); ?></em></td>
                                        <td>—</td>
                                        <td>—</td>
                                        <td>—</td>
                                        <td><strong>GHS <?php echo number_format($fc['projected_net'], 2); ?></strong></td>
                                    </tr>
<?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="fw-bold m-0"><i class="fas fa-user-minus me-2"></i>Attrition Cost Analysis</h5>
                    </div>
                    <div class="card-body p-0">
<?php if (empty($attritionData)): ?>
                        <div class="p-4 text-muted text-center">No attrition detected between consecutive periods.</div>
<?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered align-middle m-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>From → To</th>
                                        <th>Left</th>
                                        <th>Gross Cost</th>
                                        <th>Net Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php foreach ($attritionData as $ad): ?>
                                    <tr>
                                        <td><?php echo \App\Helpers\Security::escape($ad['from_period'] . ' → ' . $ad['to_period']); ?></td>
                                        <td><?php echo $ad['count']; ?></td>
                                        <td>GHS <?php echo number_format($ad['total_gross'], 2); ?></td>
                                        <td>GHS <?php echo number_format($ad['total_net'], 2); ?></td>
                                    </tr>
<?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
<?php endif; ?>
                    </div>
<?php if (!empty($attritionData)): ?>
                    <div class="card-footer bg-white">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#attritionDetail">Show Details</button>
                        <div class="collapse mt-2" id="attritionDetail">
<?php foreach ($attritionData as $ad): ?>
                            <div class="mb-2">
                                <strong><?php echo \App\Helpers\Security::escape($ad['from_period'] . ' → ' . $ad['to_period']); ?></strong> (<?php echo $ad['count']; ?> employees)
                                <ul class="small mb-0">
<?php foreach ($ad['employees'] as $e): ?>
                                    <li><?php echo \App\Helpers\Security::escape($e['fullname'] . ' (' . ($e['staff_id_card'] ?? 'N/A') . ') — ' . $e['dept_name'] . ' — Last Gross: GHS ' . number_format((float)$e['gross_pay'], 2)); ?></li>
<?php endforeach; ?>
                                </ul>
                            </div>
<?php endforeach; ?>
                        </div>
                    </div>
<?php endif; ?>
                </div>
            </div>
        </div>
<?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function () {
<?php if (count($processedPeriods) >= 2): ?>

    // Chart 1: Department cost trends (grouped bar)
    (function () {
        var periodLabels = <?php echo json_encode(array_values(array_map(function ($p) { return $p['period_label']; }, $processedPeriods))); ?>;
        var datasets = [];
        <?php foreach ($deptNames as $dn): ?>
        datasets.push({
            label: '<?php echo \App\Helpers\Security::escape($dn, 'js'); ?>',
            data: [
                <?php foreach ($processedPeriods as $p):
                    $val = $deptData[$p['id']]['departments'][$dn]['gross'] ?? 0; ?>
                    <?php echo $val; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: '<?php echo $deptColors[$dn]; ?>',
            borderRadius: 3
        });
        <?php endforeach; ?>

        new Chart(document.getElementById('deptTrendChart'), {
            type: 'bar',
            data: { labels: periodLabels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } },
                    tooltip: { callbacks: { label: function (ctx) { return ctx.dataset.label + ': GHS ' + Number(ctx.raw).toLocaleString('en-US', {minimumFractionDigits:2}); } } }
                },
                scales: {
                    x: { stacked: false },
                    y: { beginAtZero: true, ticks: { callback: function (v) { return 'GHS ' + Number(v).toLocaleString('en-US'); } } }
                }
            }
        });
    })();

    // Chart 2: Budget forecast line (actual net pay + projection)
    (function () {
        var labels = <?php echo json_encode(array_merge(array_column($periodTotals, 'period_label'), $fcLabels)); ?>;
        var actualData = <?php echo json_encode(array_merge(array_column($periodTotals, 'net'), array_fill(0, count($fcLabels), null))); ?>;
        var forecastData = <?php echo json_encode(array_merge(array_fill(0, count($periodTotals), null), $fcValues)); ?>;

        new Chart(document.getElementById('forecastChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Actual Net Pay',
                        data: actualData,
                        borderColor: '#2b6cb0',
                        backgroundColor: 'rgba(43,108,176,0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: '#2b6cb0'
                    },
                    {
                        label: 'Forecast',
                        data: forecastData,
                        borderColor: '#c53030',
                        borderDash: [6, 3],
                        backgroundColor: 'rgba(197,48,48,0.05)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: '#c53030',
                        pointStyle: 'rectRot'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: function (ctx) { return ctx.dataset.label + ': GHS ' + Number(ctx.raw || 0).toLocaleString('en-US', {minimumFractionDigits:2}); } } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: function (v) { return 'GHS ' + Number(v).toLocaleString('en-US'); } } }
                }
            }
        });
    })();
<?php endif; ?>
});
</script>
