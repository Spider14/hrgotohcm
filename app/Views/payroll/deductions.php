<?php
declare(strict_types=1);

$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Loan / Deductions Management</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0"><i class="fas fa-plus-circle me-2"></i>Add New Deduction</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo $appUrl; ?>/payroll/deductions/save" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

                    <div class="col-md-4">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Select Staff --</option>
<?php foreach ($staff as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo \App\Helpers\Security::escape($s['fullname']); ?> (<?php echo \App\Helpers\Security::escape($s['staff_id_card'] ?? 'N/A'); ?>)</option>
<?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Deduction Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Salary Advance" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Total Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="total_amount" class="form-control" value="0" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Installments</label>
                        <input type="number" name="installments" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Start Period</label>
                        <select name="start_period_id" class="form-select">
                            <option value="">-- Optional --</option>
<?php foreach ($periods as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo \App\Helpers\Security::escape($p['period_label']); ?></option>
<?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Deduction</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0"><i class="fas fa-list me-2"></i>Deductions List</h5>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered align-middle m-0" id="deductionsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Employee</th>
                                <th>Name</th>
                                <th>Total Amount</th>
                                <th>Installment</th>
                                <th>Remaining</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
<?php foreach ($deductions as $d): ?>
                            <tr>
                                <td><?php echo $d['id']; ?></td>
                                <td><?php echo \App\Helpers\Security::escape($d['fullname']); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($d['name']); ?></td>
                                <td><?php echo number_format((float)$d['total_amount'], 2); ?></td>
                                <td><?php echo number_format((float)$d['installment_amount'], 2); ?></td>
                                <td><?php echo number_format((float)$d['remaining_amount'], 2); ?></td>
                                <td><span class="badge bg-<?php echo $d['status'] === 'active' ? 'warning' : 'success'; ?>"><?php echo ucfirst($d['status']); ?></span></td>
                                <td><?php echo \App\Helpers\Security::escape($d['created_at']); ?></td>
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
    if ($('#deductionsTable').length) {
        $('#deductionsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No deductions recorded." },
            order: [[0, 'desc']]
        });
    }
});
</script>
