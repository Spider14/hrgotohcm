<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
$leaveTypes = ['Annual', 'Sick', 'Casual', 'Maternity', 'Paternity', 'Study', 'Compassionate'];
?>

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Apply for Leave</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1200px;">
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 text-center">
                    <h6 class="text-muted mb-1">Annual Leave Entitlement</h6>
                    <span class="fs-3 fw-bold text-primary"><?php echo (int)$totalEntitled; ?></span>
                    <small class="text-muted">days per year</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 text-center">
                    <h6 class="text-muted mb-1">Leave Used (This Year)</h6>
                    <span class="fs-3 fw-bold text-warning"><?php echo (int)$usedDaysCount; ?></span>
                    <small class="text-muted">approved days</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 text-center">
                    <h6 class="text-muted mb-1">Leave Balance</h6>
                    <span class="fs-3 fw-bold <?php echo $leaveBalance > 0 ? 'text-success' : 'text-danger'; ?>"><?php echo (int)$leaveBalance; ?></span>
                    <small class="text-muted">days remaining</small>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h5 class="fw-bold mb-3">Leave Application Form</h5>
            <form method="POST" action="<?php echo $appUrl; ?>/staff/my-leave" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <div class="col-12 col-md-4">
                    <label class="form-label">Leave Type</label>
                    <select name="leave_type" class="form-select form-select-sm" required>
                        <?php foreach ($leaveTypes as $lt): ?>
                            <option value="<?php echo $lt; ?>"><?php echo $lt; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" required>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">Total Days <small class="text-muted">(excl. weekends)</small></label>
                    <input type="number" name="total_days" id="total_days" class="form-control form-control-sm" readonly>
                </div>
                <div class="col-12">
                    <label class="form-label">Reason</label>
                    <textarea name="reason" class="form-control form-control-sm" rows="3" required></textarea>
                </div>
                <div class="col-12 d-grid d-md-flex justify-content-md-end">
                    <button class="btn btn-primary px-4" type="submit">Submit Leave Request</button>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-3">My Leave Applications</h5>
            <div class="table-responsive">
                <table id="leaveTable" class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark"><tr><th>#</th><th>Type</th><th>Start</th><th>End</th><th>Days</th><th>Status</th><th>Applied</th></tr></thead>
                    <tbody>
                    <?php foreach ($leaveRows as $l): ?>
                        <tr>
                            <td><?php echo (int)$l['id']; ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$l['leave_type']); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$l['start_date']); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$l['end_date']); ?></td>
                            <td><?php echo (int)$l['total_days']; ?></td>
                            <td><span class="badge bg-<?php echo $l['status'] === 'Approved' ? 'success' : ($l['status'] === 'Declined' ? 'danger' : 'warning'); ?>"><?php echo \App\Helpers\Security::escape((string)$l['status']); ?></span></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($l['created_at'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function countWeekdays(start, end) {
    var count = 0, current = new Date(start), endDate = new Date(end);
    while (current <= endDate) {
        var day = current.getDay();
        if (day !== 0 && day !== 6) count++;
        current.setDate(current.getDate() + 1);
    }
    return count;
}
document.querySelectorAll('input[name="start_date"], input[name="end_date"]').forEach(function(el) {
    el.addEventListener('change', function() {
        var start = document.querySelector('input[name="start_date"]').value;
        var end = document.querySelector('input[name="end_date"]').value;
        if (start && end) {
            document.getElementById('total_days').value = countWeekdays(start, end);
        }
    });
});
$(document).ready(function() {
    if ($('#leaveTable').length) {
        $('#leaveTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No leave applications found." }
        });
    }
});
</script>
