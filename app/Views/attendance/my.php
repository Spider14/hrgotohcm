<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
$current = $current ?? null;
$settings = $settings ?? [];
$startTime = $settings['work_start_time'] ?? '08:00';
$endTime = $settings['work_end_time'] ?? '17:00';
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">My Attendance</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1200px;">
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center h-100">
                    <h6 class="text-muted text-uppercase small fw-bold">Work Hours</h6>
                    <div class="display-6 fw-bold text-primary mt-2"><?php echo substr($startTime, 0, 5); ?></div>
                    <div class="text-muted small">to</div>
                    <div class="display-6 fw-bold text-danger mt-1"><?php echo substr($endTime, 0, 5); ?></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center h-100">
                    <h6 class="text-muted text-uppercase small fw-bold">Today's Status</h6>
                    <?php if ($current && $current['clock_in']): ?>
                        <div class="mt-2">
                            <span class="badge bg-<?php echo $current['status'] === 'present' ? 'success' : ($current['status'] === 'late' ? 'warning text-dark' : ($current['status'] === 'half-day' ? 'info' : 'danger')); ?> fs-6 px-3 py-2">
                                <?php echo ucfirst($current['status']); ?>
                            </span>
                            <div class="mt-2 small text-muted">
                                Clocked in: <strong><?php echo date('H:i', strtotime($current['clock_in'])); ?></strong>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-2 text-muted">Not clocked in yet</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center h-100 d-flex align-items-center justify-content-center">
                    <?php if ($current && $current['clock_in'] && $current['clock_out'] === null): ?>
                        <form method="POST" action="<?php echo $appUrl; ?>/staff/attendance/clock" class="clock-form">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                            <input type="hidden" name="latitude" value="">
                            <input type="hidden" name="longitude" value="">
                            <button class="btn btn-danger btn-lg px-5 py-3 rounded-pill shadow" type="submit">
                                <i class="fas fa-sign-out-alt me-2"></i>Clock Out
                            </button>
                        </form>
                    <?php elseif ($current && $current['clock_out'] !== null): ?>
                        <div class="text-center">
                            <i class="fas fa-check-circle text-success fa-3x mb-2"></i>
                            <h6 class="fw-bold text-success">Done for today</h6>
                            <small class="text-muted">Clocked out at <?php echo date('H:i', strtotime($current['clock_out'])); ?></small>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="<?php echo $appUrl; ?>/staff/attendance/clock" class="clock-form">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                            <input type="hidden" name="latitude" value="">
                            <input type="hidden" name="longitude" value="">
                            <button class="btn btn-primary btn-lg px-5 py-3 rounded-pill shadow" type="submit">
                                <i class="fas fa-sign-in-alt me-2"></i>Clock In
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold m-0">Attendance Log</h5>
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <select name="month" class="form-select form-select-sm" style="width:auto;">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo str_pad((string)$m, 2, '0', STR_PAD_LEFT); ?>" <?php echo ($_GET['month'] ?? date('m')) === str_pad((string)$m, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <select name="year" class="form-select form-select-sm" style="width:auto;">
                        <?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y'); $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo ($_GET['year'] ?? date('Y')) == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="fas fa-filter"></i></button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr><th>#</th><th>Date</th><th>Clock In</th><th>Clock Out</th><th>Duration</th><th>Status</th><th>IP</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($attendanceRows)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No attendance records found</td></tr>
                    <?php else: $i = 0; ?>
                        <?php foreach ($attendanceRows as $a): $i++; ?>
                            <?php
                            $duration = '';
                            if ($a['clock_in'] && $a['clock_out']) {
                                $diff = strtotime($a['clock_out']) - strtotime($a['clock_in']);
                                $h = intdiv($diff, 3600);
                                $m = intdiv($diff % 3600, 60);
                                $duration = $h . 'h ' . $m . 'm';
                            }
                            $statusClass = $a['status'] === 'present' ? 'success' : ($a['status'] === 'late' ? 'warning text-dark' : ($a['status'] === 'half-day' ? 'info' : 'danger'));
                            $clockInIp = $a['clock_in_ip'] ?? '';
                            $clockOutIp = $a['clock_out_ip'] ?? '';
                            $ipDisplay = $clockInIp;
                            if ($clockOutIp && $clockOutIp !== $clockInIp) {
                                $ipDisplay .= ' / ' . $clockOutIp;
                            }
                            ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td><?php echo date('D, M j', strtotime($a['work_date'])); ?></td>
                                <td><?php echo $a['clock_in'] ? date('H:i', strtotime($a['clock_in'])) : '-'; ?></td>
                                <td><?php echo $a['clock_out'] ? date('H:i', strtotime($a['clock_out'])) : '-'; ?></td>
                                <td><?php echo $duration ?: '-'; ?></td>
                                <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($a['status']); ?></span></td>
                                <td><small class="text-muted"><?php echo \App\Helpers\Security::escape($ipDisplay ?: '-'); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.clock-form').on('submit', function(e) {
        var $form = $(this);
        if (navigator.geolocation) {
            e.preventDefault();
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    $form.find('input[name="latitude"]').val(pos.coords.latitude);
                    $form.find('input[name="longitude"]').val(pos.coords.longitude);
                    $form.off('submit').submit();
                },
                function() {
                    $form.find('input[name="latitude"]').val('');
                    $form.find('input[name="longitude"]').val('');
                    $form.off('submit').submit();
                },
                { timeout: 10000, enableHighAccuracy: true }
            );
        }
    });
});
</script>
