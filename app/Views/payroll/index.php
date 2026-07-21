<?php
declare(strict_types=1);

$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Payroll Dashboard</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small text-uppercase fw-semibold">Latest Period</div>
                                <div class="h5 mb-0"><?php echo \App\Helpers\Security::escape($latestPeriod['period_label'] ?? 'N/A'); ?></div>
                            </div>
                            <i class="fas fa-calendar-alt fa-2x text-primary opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small text-uppercase fw-semibold">Total Processed</div>
                                <div class="h5 mb-0"><?php echo $totalProcessed; ?></div>
                            </div>
                            <i class="fas fa-check-circle fa-2x text-success opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small text-uppercase fw-semibold">Total Staff</div>
                                <div class="h5 mb-0"><?php echo $totalStaff; ?></div>
                            </div>
                            <i class="fas fa-users fa-2x text-info opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small text-uppercase fw-semibold">Open Periods</div>
                                <div class="h5 mb-0"><?php echo $openPeriods; ?></div>
                            </div>
                            <i class="fas fa-folder-open fa-2x text-warning opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0"><i class="fas fa-tachometer-alt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="<?php echo $appUrl; ?>/payroll/periods" class="btn btn-outline-primary w-100 py-3">
                            <i class="fas fa-calendar-plus me-2"></i>Manage Periods
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?php echo $appUrl; ?>/payroll/components" class="btn btn-outline-success w-100 py-3">
                            <i class="fas fa-cogs me-2"></i>Salary Components
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?php echo $appUrl; ?>/payroll/process" class="btn btn-outline-warning w-100 py-3">
                            <i class="fas fa-play me-2"></i>Run Payroll
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?php echo $appUrl; ?>/payroll/reports" class="btn btn-outline-info w-100 py-3">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
