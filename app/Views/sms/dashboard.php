<?php
/**
 * @var array $stats
 * @var array $recentLogs
 * @var string $appUrl
 */
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3">
                <i class="fas fa-bars"></i>
            </button>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb m-0 bg-transparent p-0">
                    <li class="breadcrumb-item text-dark"><a href="<?php echo $appUrl; ?>/dashboard" class="text-decoration-none fw-semibold">Dashboard</a></li>
                    <li class="breadcrumb-item text-dark fw-semibold">Communications</li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">SMS Analytics</li>
                </ol>
            </nav>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-primary">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-primary-light text-primary me-3">
                            <i class="fas fa-wallet fa-2x"></i>
                        </div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Gateway Balance</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1">GHS <?php echo number_format($stats['api_balance_ghs'], 2); ?></h3>
                            <small class="text-muted text-xs font-monospace"><?php echo \App\Helpers\Security::escape($stats['api_provider']); ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-dark">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-dark-light text-dark me-3">
                            <i class="fas fa-paper-plane fa-2x"></i>
                        </div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Total Outbound</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo number_format($stats['total_sent']); ?></h3>
                            <small class="text-muted text-xs font-monospace">All Dispatches</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-success">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-success-light text-success me-3">
                            <i class="fas fa-circle-check fa-2x"></i>
                        </div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Delivered Packets</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo number_format($stats['delivered']); ?></h3>
                            <small class="text-success text-xs font-monospace fw-bold">
                                <?php echo $stats['total_sent'] > 0 ? round(($stats['delivered'] / $stats['total_sent']) * 100, 1) : 0; ?>% Success Rate
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-danger">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-danger-light text-danger me-3">
                            <i class="fas fa-circle-exclamation fa-2x"></i>
                        </div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Failed Attempts</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo number_format($stats['failed']); ?></h3>
                            <small class="text-danger text-xs font-monospace">Check logs & endpoint keys</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-lg p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h5 class="fw-bold text-dark m-0">
                            <i class="fas fa-clock-rotate-left me-2" style="color: #1d2a52;"></i>Recent Transmission Logs
                        </h5>
                        <a href="<?php echo $appUrl; ?>/sms/campaigns" class="btn btn-link btn-sm text-decoration-none fw-bold p-0 text-primary">
                            View All History <i class="fas fa-arrow-right small ms-1"></i>
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table id="recentSmsTable" class="table table-striped table-bordered align-middle w-100 m-0">
                            <thead class="table-dark text-uppercase tracking-wider small text-white">
                                <tr>
                                    <th class="border-0">Recipient</th>
                                    <th class="border-0">Trigger Context</th>
                                    <th class="border-0">Message Payload</th>
                                    <th class="border-0 text-center">Gateway Status</th>
                                    <th class="border-0">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="small font-sans">
                                <?php foreach ($recentLogs as $log): 
                                    $statusClass = ($log['status'] === 'Delivered') ? 'bg-success' : 'bg-danger';
                                    
                                    $milestoneBadge = 'bg-primary';
                                    if (strtolower($log['milestone']) === 'shortlisted') $milestoneBadge = 'bg-info text-white';
                                    if (strtolower($log['milestone']) === 'hired') $milestoneBadge = 'bg-success';
                                ?>
                                    <tr>
                                        <td class="font-monospace fw-bold text-dark"><?php echo \App\Helpers\Security::escape($log['phone']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $milestoneBadge; ?> rounded-pill text-uppercase font-monospace px-2 py-1" style="font-size: 0.65rem;">
                                                <?php echo \App\Helpers\Security::escape($log['milestone']); ?>
                                            </span>
                                        </td>
                                        <td class="text-muted text-wrap" style="max-width: 450px; font-size: 0.8rem;">
                                            <?php echo \App\Helpers\Security::escape($log['message']); ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?php echo $statusClass; ?> px-2 py-1 font-monospace rounded-pill text-uppercase" style="font-size:0.7rem;">
                                                <?php echo \App\Helpers\Security::escape($log['status']); ?>
                                            </span>
                                        </td>
                                        <td class="font-monospace text-nowrap"><?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?></td>
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
    if ($('#recentSmsTable').length) {
        $('#recentSmsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No recent SMS logs recorded." },
            order: [[4, 'desc']]
        });
    }
});
</script>