<?php
declare(strict_types=1);

$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Salary Components</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0"><i class="fas fa-plus-circle me-2"></i>Add / Edit Component</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo $appUrl; ?>/payroll/components/save" class="row g-3" id="componentForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="id" id="compId" value="0">

                    <div class="col-md-4">
                        <label class="form-label">Component Name</label>
                        <input type="text" name="name" id="compName" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select name="type" id="compType" class="form-select">
                            <option value="allowance">Allowance</option>
                            <option value="deduction">Deduction</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Calculation</label>
                        <select name="calculation_type" id="compCalcType" class="form-select">
                            <option value="fixed">Fixed</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Default Value</label>
                        <input type="number" step="0.01" name="default_value" id="compDefault" class="form-control" value="0">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2 pb-1">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()"><i class="fas fa-undo me-1"></i>Reset</button>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-check-inline">
                            <input type="checkbox" class="form-check-input" name="is_taxable" id="compTaxable" value="1">
                            <label class="form-check-label" for="compTaxable">Taxable</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="checkbox" class="form-check-input" name="is_ssnit_liable" id="compSsnit" value="1">
                            <label class="form-check-label" for="compSsnit">SSNIT Liable</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="checkbox" class="form-check-input" name="is_mandatory" id="compMandatory" value="1">
                            <label class="form-check-label" for="compMandatory">Mandatory</label>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0"><i class="fas fa-list me-2"></i>Components List</h5>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered align-middle m-0" id="componentsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Calculation</th>
                                <th>Default Value</th>
                                <th>Taxable</th>
                                <th>SSNIT</th>
                                <th>Mandatory</th>
                                <th class="no-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
<?php foreach ($components as $c): ?>
                            <tr>
                                <td><?php echo $c['id']; ?></td>
                                <td><?php echo \App\Helpers\Security::escape($c['name']); ?></td>
                                <td><span class="badge bg-<?php echo $c['type'] === 'allowance' ? 'success' : 'danger'; ?>"><?php echo ucfirst($c['type']); ?></span></td>
                                <td><?php echo ucfirst($c['calculation_type']); ?></td>
                                <td><?php echo number_format((float)$c['default_value'], 2); ?></td>
                                <td><i class="fas fa-<?php echo $c['is_taxable'] ? 'check text-success' : 'times text-muted'; ?>"></i></td>
                                <td><i class="fas fa-<?php echo $c['is_ssnit_liable'] ? 'check text-success' : 'times text-muted'; ?>"></i></td>
                                <td><i class="fas fa-<?php echo $c['is_mandatory'] ? 'check text-success' : 'times text-muted'; ?>"></i></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary editComp" data-id="<?php echo $c['id']; ?>" data-name="<?php echo \App\Helpers\Security::escape($c['name']); ?>" data-type="<?php echo $c['type']; ?>" data-calc="<?php echo $c['calculation_type']; ?>" data-default="<?php echo $c['default_value']; ?>" data-taxable="<?php echo $c['is_taxable']; ?>" data-ssnit="<?php echo $c['is_ssnit_liable']; ?>" data-mandatory="<?php echo $c['is_mandatory']; ?>"><i class="fas fa-edit"></i></button>
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
    if ($('#componentsTable').length) {
        $('#componentsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No components found." },
            order: [[0, 'desc']],
            columnDefs: [{ targets: 'no-export', orderable: false, searchable: false }]
        });
    }

    $('.editComp').click(function () {
        var btn = $(this);
        $('#compId').val(btn.data('id'));
        $('#compName').val(btn.data('name'));
        $('#compType').val(btn.data('type'));
        $('#compCalcType').val(btn.data('calc'));
        $('#compDefault').val(btn.data('default'));
        $('#compTaxable').prop('checked', btn.data('taxable') == 1);
        $('#compSsnit').prop('checked', btn.data('ssnit') == 1);
        $('#compMandatory').prop('checked', btn.data('mandatory') == 1);
    });
});

function resetForm() {
    $('#compId').val(0);
    $('#componentForm')[0].reset();
    $('#compTaxable, #compSsnit, #compMandatory').prop('checked', false);
}
</script>
