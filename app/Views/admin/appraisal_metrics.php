<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Appraisal Metrics</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h5 class="fw-bold mb-3">Create Metric</h5>
            <form method="POST" action="<?php echo $appUrl; ?>/admin/appraisal-metrics" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <div class="col-12 col-md-4"><label class="form-label">Metric Name</label><input type="text" name="metric_name" class="form-control form-control-sm" required></div>
                <div class="col-12 col-md-6"><label class="form-label">Metric Prompt</label><input type="text" name="metric_prompt" class="form-control form-control-sm" required></div>
                <div class="col-12 col-md-2"><label class="form-label">Active</label><select name="is_active" class="form-select form-select-sm"><option value="1">Yes</option><option value="0">No</option></select></div>
                <div class="col-12 d-grid d-md-flex justify-content-md-end"><button class="btn btn-dark btn-sm" type="submit">Save Metric</button></div>
            </form>
        </div>

        <?php require __DIR__ . '/../dashboard/layouts/flash.php'; ?>

        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-3">Metric Registry</h5>
            <div class="table-responsive">
                <table id="appraisalMetricsTable" class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark"><tr><th>ID</th><th>Name</th><th>Prompt</th><th>Active</th><th>Created</th></tr></thead>
                    <tbody>
                    <?php foreach ($metrics as $metric): ?>
                        <tr>
                            <td><?php echo (int)$metric['id']; ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$metric['metric_name']); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)$metric['metric_prompt']); ?></td>
                            <td><span class="badge bg-<?php echo ((int)($metric['is_active'] ?? 0) === 1) ? 'success' : 'secondary'; ?>"><?php echo ((int)($metric['is_active'] ?? 0) === 1) ? 'Yes' : 'No'; ?></span></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($metric['created_at'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($('#appraisalMetricsTable').length) {
        $('#appraisalMetricsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No metrics found." },
            order: [[0, 'asc']]
        });
    }
});
</script>
