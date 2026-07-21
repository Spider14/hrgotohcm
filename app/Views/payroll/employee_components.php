<?php
declare(strict_types=1);

$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Employee Components</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="get" action="<?php echo $appUrl; ?>/payroll/employee-components" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Select Staff</label>
                        <select name="user_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Choose Staff --</option>
<?php foreach ($staff as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $userId === (int)$s['id'] ? 'selected' : ''; ?>>
                                <?php echo \App\Helpers\Security::escape($s['fullname']); ?> (<?php echo \App\Helpers\Security::escape($s['staff_id_card'] ?? 'N/A'); ?>)
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

<?php if ($userId > 0): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0"><i class="fas fa-plus-circle me-2"></i>Assign Component</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo $appUrl; ?>/payroll/employee-components/save" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">

                    <div class="col-md-4">
                        <label class="form-label">Component</label>
                        <select name="component_id" class="form-select" required>
                            <option value="">-- Select --</option>
<?php foreach ($components as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo \App\Helpers\Security::escape($c['name']); ?> (<?php echo $c['type']; ?>)</option>
<?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="0" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0"><i class="fas fa-list me-2"></i>Assigned Components</h5>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered align-middle m-0" id="assignmentsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Component</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Effective From</th>
                                <th>Effective To</th>
                            </tr>
                        </thead>
                        <tbody>
<?php foreach ($assignments as $a): ?>
                            <tr>
                                <td><?php echo \App\Helpers\Security::escape($a['comp_name']); ?></td>
                                <td><span class="badge bg-<?php echo $a['comp_type'] === 'allowance' ? 'success' : 'danger'; ?>"><?php echo ucfirst($a['comp_type']); ?></span></td>
                                <td><?php echo number_format((float)$a['amount'], 2); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($a['effective_from'] ?? '-'); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($a['effective_to'] ?? '-'); ?></td>
                            </tr>
<?php endforeach; ?>
<?php if (empty($assignments)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No components assigned yet.</td></tr>
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
    if ($('#assignmentsTable').length) {
        $('#assignmentsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No components assigned." },
            order: [[0, 'asc']]
        });
    }
});
</script>
