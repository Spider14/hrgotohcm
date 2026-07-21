<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
$settings = $settings ?? [];
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Attendance Settings</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 800px;">
        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-4"><i class="fas fa-cog me-2 text-primary"></i>Attendance Configuration</h5>
            <form method="POST" action="<?php echo $appUrl; ?>/admin/attendance/settings">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Work Start Time</label>
                        <input type="time" name="work_start_time" class="form-control form-control-sm"
                               value="<?php echo \App\Helpers\Security::escape($settings['work_start_time'] ?? '08:00:00'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Work End Time</label>
                        <input type="time" name="work_end_time" class="form-control form-control-sm"
                               value="<?php echo \App\Helpers\Security::escape($settings['work_end_time'] ?? '17:00:00'); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Grace Period (minutes)</label>
                        <input type="number" name="grace_period_minutes" class="form-control form-control-sm"
                               value="<?php echo (int)($settings['grace_period_minutes'] ?? 15); ?>" min="0" required>
                        <small class="text-muted">No penalty if clocked in within this window</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Late Threshold (minutes)</label>
                        <input type="number" name="late_threshold_minutes" class="form-control form-control-sm"
                               value="<?php echo (int)($settings['late_threshold_minutes'] ?? 15); ?>" min="0" required>
                        <small class="text-muted">After this, status becomes "Late"</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Half-Day Hours</label>
                        <input type="number" name="half_day_hours" class="form-control form-control-sm"
                               value="<?php echo (float)($settings['half_day_hours'] ?? 4); ?>" min="0" step="0.5" required>
                        <small class="text-muted">Less than this = half day</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Weekend Days (comma-separated)</label>
                        <input type="text" name="weekend_days" class="form-control form-control-sm"
                               value="<?php echo \App\Helpers\Security::escape($settings['weekend_days'] ?? 'Saturday,Sunday'); ?>" required>
                    </div>
                    <div class="col-12 d-grid d-md-flex justify-content-md-end">
                        <button class="btn btn-primary px-4" type="submit"><i class="fas fa-save me-1"></i>Save Settings</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm p-4 mt-4">
            <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2 text-info"></i>How It Works</h6>
            <ul class="small text-muted mb-0">
                <li>Staff click <strong>Clock In</strong> when they arrive, <strong>Clock Out</strong> when they leave.</li>
                <li>If clocked in within the <strong>grace period</strong> after start time, status = <span class="badge bg-success">Present</span>.</li>
                <li>If clocked in after the grace period but within the <strong>late threshold</strong>, still <span class="badge bg-success">Present</span>.</li>
                <li>If clocked in after the late threshold, status = <span class="badge bg-warning text-dark">Late</span>.</li>
                <li>If total worked hours are less than <strong>half-day hours</strong>, status = <span class="badge bg-info">Half Day</span>.</li>
                <li>Weekend days are excluded from absence calculations.</li>
            </ul>
        </div>
    </div>
</div>
