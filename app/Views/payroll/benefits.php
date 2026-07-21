<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
$categories = ['housing' => 'Housing', 'transport' => 'Transport', 'medical' => 'Medical', 'other' => 'Other'];
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Benefits & Incentives</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold m-0">Benefit Types</h5>
                <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#benefitTypeModal"><i class="fas fa-plus me-1"></i>New Benefit Type</button>
            </div>
            <div class="table-responsive">
                <table id="benefitTypesTable" class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th><th>Name</th><th>Category</th><th>Default Amount (GHS)</th><th>Calc Type</th><th>Taxable</th><th>SSNIT Liable</th><th>Active</th><th class="no-export">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($benefitTypes as $bt): ?>
                        <tr>
                            <td><?php echo (int)$bt['id']; ?></td>
                            <td><?php echo \App\Helpers\Security::escape($bt['name']); ?></td>
                            <td><?php echo \App\Helpers\Security::escape($categories[$bt['category']] ?? $bt['category']); ?></td>
                            <td><?php echo $bt['calculation_type'] === 'percentage' ? \App\Helpers\Security::escape($bt['default_amount']) . '%' : number_format((float)$bt['default_amount'], 2); ?></td>
                            <td><?php echo \App\Helpers\Security::escape($bt['calculation_type']); ?></td>
                            <td><span class="badge bg-<?php echo (int)$bt['is_taxable'] ? 'danger' : 'secondary'; ?>"><?php echo (int)$bt['is_taxable'] ? 'Yes' : 'No'; ?></span></td>
                            <td><span class="badge bg-<?php echo (int)$bt['is_ssnit_liable'] ? 'danger' : 'secondary'; ?>"><?php echo (int)$bt['is_ssnit_liable'] ? 'Yes' : 'No'; ?></span></td>
                            <td><span class="badge bg-<?php echo (int)$bt['is_active'] ? 'success' : 'secondary'; ?>"><?php echo (int)$bt['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                            <td class="no-export">
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editBenefitTypeModal<?php echo (int)$bt['id']; ?>"><i class="fas fa-edit"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-3">Assign Benefits to Employee</h5>
            <form method="GET" action="<?php echo $appUrl; ?>/payroll/benefits" class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Select Employee --</option>
                        <?php foreach ($staff as $s): ?>
                            <option value="<?php echo (int)$s['id']; ?>" <?php echo ($userId === (int)$s['id']) ? 'selected' : ''; ?>>
                                <?php echo \App\Helpers\Security::escape($s['fullname'] . ' (' . ($s['staff_id_card'] ?? 'N/A') . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <a href="<?php echo $appUrl; ?>/payroll/benefits" class="btn btn-sm btn-outline-secondary">Clear</a>
                </div>
            </form>

            <?php if ($userId > 0): ?>
                <form method="POST" action="<?php echo $appUrl; ?>/payroll/benefits/assign" class="row g-3 mb-4 p-3 bg-light rounded">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                    <div class="col-12 col-md-5">
                        <label class="form-label small">Benefit Type</label>
                        <select name="benefit_type_id" class="form-select form-select-sm" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($benefitTypes as $bt): ?>
                                <option value="<?php echo (int)$bt['id']; ?>"><?php echo \App\Helpers\Security::escape($bt['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small">Amount (GHS)</label>
                        <input type="number" step="0.01" name="amount" class="form-control form-control-sm" value="0.00" min="0" required>
                    </div>
                    <div class="col-12 col-md-3 d-flex align-items-end">
                        <button class="btn btn-sm btn-success w-100" type="submit"><i class="fas fa-plus me-1"></i>Assign</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table id="assignmentsTable" class="table table-sm table-striped table-bordered align-middle">
                        <thead class="table-dark">
                            <tr><th>#</th><th>Benefit</th><th>Category</th><th>Amount (GHS)</th><th>Effective From</th><th>Effective To</th><th class="no-export">Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($assignments as $a): ?>
                            <tr>
                                <td><?php echo (int)$a['id']; ?></td>
                                <td><?php echo \App\Helpers\Security::escape($a['benefit_name']); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($categories[$a['category']] ?? $a['category']); ?></td>
                                <td><?php echo number_format((float)$a['amount'], 2); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($a['effective_from'] ?? '-'); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($a['effective_to'] ?? '-'); ?></td>
                                <td class="no-export">
                                    <form method="POST" action="<?php echo $appUrl; ?>/payroll/benefits/remove" class="d-inline" onsubmit="return confirm('Remove this benefit?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="benefitTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo $appUrl; ?>/payroll/benefits/save-type">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <div class="modal-header"><h5 class="modal-title">New Benefit Type</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <?php foreach ($categories as $k => $v): ?>
                                <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Calculation Type</label>
                        <select name="calculation_type" class="form-select">
                            <option value="fixed">Fixed Amount</option>
                            <option value="percentage">Percentage of Base Salary</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Default Amount / Percentage</label><input type="number" step="0.01" name="default_amount" class="form-control" value="0.00" min="0" required></div>
                    <div class="mb-3">
                        <div class="form-check"><input type="checkbox" name="is_taxable" class="form-check-input" value="1" id="btTax"><label class="form-check-label" for="btTax">Taxable</label></div>
                        <div class="form-check"><input type="checkbox" name="is_ssnit_liable" class="form-check-input" value="1" id="btSsnit"><label class="form-check-label" for="btSsnit">SSNIT Liable</label></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>
</div>

<?php foreach ($benefitTypes as $bt): ?>
<div class="modal fade" id="editBenefitTypeModal<?php echo (int)$bt['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo $appUrl; ?>/payroll/benefits/save-type">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="id" value="<?php echo (int)$bt['id']; ?>">
                <div class="modal-header"><h5 class="modal-title">Edit Benefit Type</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?php echo \App\Helpers\Security::escape($bt['name']); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <?php foreach ($categories as $k => $v): ?>
                                <option value="<?php echo $k; ?>" <?php echo $bt['category'] === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Calculation Type</label>
                        <select name="calculation_type" class="form-select">
                            <option value="fixed" <?php echo $bt['calculation_type'] === 'fixed' ? 'selected' : ''; ?>>Fixed Amount</option>
                            <option value="percentage" <?php echo $bt['calculation_type'] === 'percentage' ? 'selected' : ''; ?>>Percentage of Base Salary</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Default Amount / Percentage</label><input type="number" step="0.01" name="default_amount" class="form-control" value="<?php echo (float)$bt['default_amount']; ?>" min="0" required></div>
                    <div class="mb-3">
                        <div class="form-check"><input type="checkbox" name="is_taxable" class="form-check-input" value="1" id="btTax<?php echo (int)$bt['id']; ?>" <?php echo (int)$bt['is_taxable'] ? 'checked' : ''; ?>><label class="form-check-label" for="btTax<?php echo (int)$bt['id']; ?>">Taxable</label></div>
                        <div class="form-check"><input type="checkbox" name="is_ssnit_liable" class="form-check-input" value="1" id="btSsnit<?php echo (int)$bt['id']; ?>" <?php echo (int)$bt['is_ssnit_liable'] ? 'checked' : ''; ?>><label class="form-check-label" for="btSsnit<?php echo (int)$bt['id']; ?>">SSNIT Liable</label></div>
                        <div class="form-check"><input type="checkbox" name="is_active" class="form-check-input" value="1" id="btActive<?php echo (int)$bt['id']; ?>" <?php echo (int)$bt['is_active'] ? 'checked' : ''; ?>><label class="form-check-label" for="btActive<?php echo (int)$bt['id']; ?>">Active</label></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Update</button></div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
$(document).ready(function() {
    if ($('#benefitTypesTable').length) {
        $('#benefitTypesTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No benefit types defined." },
            order: [[0, 'asc']]
        });
    }
    <?php if ($userId > 0): ?>
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
            language: { search: "Search:", emptyTable: "No benefits assigned to this employee." },
            order: [[0, 'asc']]
        });
    }
    <?php endif; ?>
});
</script>
