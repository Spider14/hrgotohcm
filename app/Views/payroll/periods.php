<?php
declare(strict_types=1);

$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Payroll Periods</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0"><i class="fas fa-plus-circle me-2"></i>Create New Period</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo $appUrl; ?>/payroll/periods/create" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

                    <div class="col-md-4">
                        <label class="form-label">Period Label</label>
                        <input type="text" name="period_label" class="form-control" placeholder="e.g. January 2026" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0"><i class="fas fa-list me-2"></i>Periods</h5>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered align-middle m-0" id="periodsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Label</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Processed At</th>
                                <th class="no-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
<?php foreach ($periods as $p): ?>
                            <tr>
                                <td><?php echo $p['id']; ?></td>
                                <td><?php echo \App\Helpers\Security::escape($p['period_label']); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($p['start_date']); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($p['end_date']); ?></td>
                                <td>
<?php
$badge = match ($p['status']) {
    'open' => 'warning',
    'processed' => 'success',
    'closed' => 'secondary',
    default => 'light',
};
?>
                                    <span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst($p['status']); ?></span>
                                </td>
                                <td><?php echo $p['processed_at'] ? \App\Helpers\Security::escape($p['processed_at']) : '-'; ?></td>
                                <td>
<?php if ($p['status'] === 'open'): ?>
                                    <form method="post" action="<?php echo $appUrl; ?>/payroll/periods/close" class="d-inline" onsubmit="return confirm('Close this period?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-lock me-1"></i>Close</button>
                                    </form>
<?php endif; ?>
                                </td>
                            </tr>
<?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    if ($('#periodsTable').length) {
        $('#periodsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No periods found." },
            order: [[0, 'desc']]
        });
    }
});
</script>
