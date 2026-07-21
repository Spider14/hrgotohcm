<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL']);
$userRole = $_SESSION['user_role'] ?? 'Staff';
$csrfToken = \App\Helpers\Security::generateCsrfToken();
?>

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3">
                <i class="fas fa-bars"></i>
            </button>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb m-0 bg-transparent p-0">
                    <li class="breadcrumb-item text-dark"><a href="<?php echo $appUrl; ?>/dashboard" class="text-decoration-none fw-semibold">Dashboard</a></li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Staff Directory</li>
                </ol>
            </nav>
            <div class="ms-auto d-flex align-items-center gap-3">
                <?php if (in_array($userRole, ['Super Admin', 'HR Manager'], true)): ?>
                    <a href="<?php echo $appUrl; ?>/onboard" class="btn btn-outline-primary btn-sm rounded px-3 fw-semibold me-2">
                        <i class="fas fa-user-plus me-2"></i> Single Onboard
                    </a>
                <?php endif; ?>
                <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-primary">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-primary-light text-primary me-3"><i class="fas fa-users fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Total Staff</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo (int)($statusSummary['total'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-success">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-success-light text-success me-3"><i class="fas fa-user-check fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Permanent</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo (int)($statusSummary['permanent'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-warning">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-warning-light text-warning me-3"><i class="fas fa-hourglass-half fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Probation</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo (int)($statusSummary['probation'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-danger">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-danger-light text-danger me-3"><i class="fas fa-user-slash fa-2x"></i></div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Suspended</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo (int)($statusSummary['suspended'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-lg p-4 bg-white mb-4">
            <?php if (in_array($userRole, ['Super Admin', 'HR Manager'], true)): ?>
                <div class="mb-4 p-3 border rounded bg-light">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <h6 class="m-0 fw-bold text-uppercase font-monospace text-xs">CSV Onboarding Workspace</h6>
                        <a href="<?php echo $appUrl; ?>/staff/csv-template" class="btn btn-outline-dark btn-sm">
                            <i class="fas fa-download me-1"></i> Download Template
                        </a>
                    </div>

                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Upload CSV File</label>
                            <input type="file" id="csvOnboardFile" class="form-control form-control-sm" accept=".csv">
                        </div>
                        <div class="col-12 col-md-6 d-flex gap-2">
                            <button type="button" id="csvUploadBtn" class="btn btn-primary btn-sm">
                                <i class="fas fa-file-upload me-1"></i> Upload CSV
                            </button>
                            <button type="button" id="csvProcessBtn" class="btn btn-dark btn-sm" disabled>
                                <i class="fas fa-play me-1"></i> Start Processing
                            </button>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="progress" style="height: 18px;">
                            <div id="csvProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width:0%">0%</div>
                        </div>
                        <small id="csvProgressText" class="text-muted d-block mt-1">No batch selected.</small>
                        <small id="csvProgressError" class="text-danger d-block mt-1"></small>
                    </div>
                </div>
            <?php endif; ?>

            <form method="GET" action="<?php echo $appUrl; ?>/staff" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label font-monospace text-xs text-uppercase fw-bold text-muted">Search Records</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted"><i class="fas fa-magnifying-glass"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search name or ID Card..." value="<?php echo \App\Helpers\Security::escape($filters['search'] ?? ''); ?>">
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label font-monospace text-xs text-uppercase fw-bold text-muted">Department Filter</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">-- All Departments --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['dept_id']; ?>" <?php echo (string)($filters['department_id'] ?? '') === (string)$dept['dept_id'] ? 'selected' : ''; ?>>
                                <?php echo \App\Helpers\Security::escape($dept['dept_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label font-monospace text-xs text-uppercase fw-bold text-muted">Employment State</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- All Status Types --</option>
                        <option value="Permanent" <?php echo (($filters['status'] ?? '') === 'Permanent') ? 'selected' : ''; ?>>Permanent</option>
                        <option value="Contract" <?php echo (($filters['status'] ?? '') === 'Contract') ? 'selected' : ''; ?>>Contract</option>
                        <option value="Probation" <?php echo (($filters['status'] ?? '') === 'Probation') ? 'selected' : ''; ?>>Probation</option>
                        <option value="Suspended" <?php echo (($filters['status'] ?? '') === 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>

                <div class="col-12 col-md-2 d-grid">
                    <button type="submit" class="btn btn-dark btn-sm fw-bold"><i class="fas fa-filter me-2"></i>Apply Filters</button>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm rounded-lg p-4 bg-white">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                <div>
                    <h4 class="fw-bold text-dark m-0"><i class="fas fa-address-book me-2" style="color: #1d2a52;"></i>Staff Registry</h4>
                    <p class="text-muted small m-0 mt-1">Track employee profiles, designations, and employment state.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table id="staffDirectoryTable" class="table table-striped table-bordered align-middle w-100">
                    <thead class="table-dark text-uppercase tracking-wider small text-white">
                        <tr>
                            <th class="border-0">Staff ID</th>
                            <th class="border-0">Full Name</th>
                            <th class="border-0">Department</th>
                            <th class="border-0">Designation</th>
                            <th class="border-0 text-center">Status</th>
                            <th class="border-0 text-center no-export">Action</th>
                            <th class="border-0 text-center no-export">ID Card</th>
                        </tr>
                    </thead>
                    <tbody class="small font-sans">
                        <?php if (empty($recordsResult['data'])): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-folder-open fa-3x mb-3 text-opacity-25 text-secondary"></i>
                                    <p class="m-0 fw-semibold">No employee records found.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recordsResult['data'] as $row):
                                $statusBadgeClass = 'bg-secondary';
                                $statusLabel = $row['employment_status'];
                                if ($row['employment_status'] === 'Permanent') {
                                    $statusBadgeClass = 'bg-success';
                                } elseif ($row['employment_status'] === 'Probation') {
                                    $statusBadgeClass = 'bg-warning text-dark';
                                } elseif ($row['employment_status'] === 'Suspended') {
                                    $statusBadgeClass = 'bg-danger';
                                }
                            ?>
                                <tr>
                                    <td class="font-monospace fw-bold" style="color: #1d2a52;"><?php echo \App\Helpers\Security::escape($row['staff_id_card']); ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo \App\Helpers\Security::escape($row['fullname']); ?></div>
                                        <small class="text-muted"><?php echo \App\Helpers\Security::escape($row['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-secondary"><?php echo \App\Helpers\Security::escape($row['dept_name']); ?></span>
                                        <div><span class="badge bg-light text-dark border font-monospace"><?php echo \App\Helpers\Security::escape($row['dept_code'] ?? 'N/A'); ?></span></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-primary border rounded-pill small"><?php echo \App\Helpers\Security::escape($row['designation_category'] ?? 'Staff'); ?></span>
                                        <div class="small fw-semibold mt-1"><?php echo \App\Helpers\Security::escape($row['designation_title'] ?? 'General'); ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $statusBadgeClass; ?> px-3 py-2 font-monospace tracking-wide rounded-pill text-uppercase" style="font-size:0.75rem;">
                                            <?php echo \App\Helpers\Security::escape($statusLabel); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo $appUrl; ?>/staff/profile?id=<?php echo (int)$row['user_id']; ?>" class="btn btn-custom-view btn-sm px-3 fw-bold shadow-sm">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo $appUrl; ?>/staff/id-card?id=<?php echo (int)$row['user_id']; ?>" target="_blank" class="btn btn-outline-dark btn-sm px-3 fw-bold shadow-sm" title="Generate ID Card">
                                            <i class="fas fa-id-card me-1"></i> ID Card
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (($recordsResult['total_pages'] ?? 0) > 1): ?>
                <div class="card-footer bg-white border-top p-3 d-flex align-items-center justify-content-between mt-3">
                    <small class="text-muted fw-medium">
                        Showing page <strong><?php echo $recordsResult['current_page']; ?></strong> of <strong><?php echo $recordsResult['total_pages']; ?></strong>.
                    </small>
                    <nav aria-label="Staff pagination navigation panel">
                        <ul class="pagination pagination-sm m-0">
                            <li class="page-item <?php echo ($recordsResult['current_page'] <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $recordsResult['current_page'] - 1; ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>&department_id=<?php echo $filters['department_id'] ?? ''; ?>&status=<?php echo $filters['status'] ?? ''; ?>"><i class="fas fa-angle-left"></i></a>
                            </li>
                            <?php for ($p = 1; $p <= $recordsResult['total_pages']; $p++): ?>
                                <li class="page-item <?php echo ($recordsResult['current_page'] === $p) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $p; ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>&department_id=<?php echo $filters['department_id'] ?? ''; ?>&status=<?php echo $filters['status'] ?? ''; ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($recordsResult['current_page'] >= $recordsResult['total_pages']) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $recordsResult['current_page'] + 1; ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>&department_id=<?php echo $filters['department_id'] ?? ''; ?>&status=<?php echo $filters['status'] ?? ''; ?>"><i class="fas fa-angle-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .btn-custom-view {
        color: #1d2a52;
        border: 1.5px solid #1d2a52;
        background-color: transparent;
        transition: all 0.2s ease;
    }
    .btn-custom-view:hover {
        color: #ffffff;
        background-color: #1d2a52;
        border-color: #1d2a52;
    }
</style>

<script>
$(document).ready(function() {
    if ($('#staffDirectoryTable').length) {
        $('#staffDirectoryTable').DataTable({
            paging: false,
            info: false,
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            language: { search: "Search:" },
            order: [[1, 'asc']],
            columnDefs: [{ targets: 'no-export', orderable: false }]
        });
    }
});
</script>

<?php if (in_array($userRole, ['Super Admin', 'HR Manager'], true)): ?>
<script>
(function () {
    const appUrl = <?php echo json_encode((string)($_ENV['APP_URL'] ?? '')); ?>;
    const csrfToken = <?php echo json_encode($csrfToken); ?>;

    const uploadBtn = document.getElementById('csvUploadBtn');
    const processBtn = document.getElementById('csvProcessBtn');
    const fileInput = document.getElementById('csvOnboardFile');
    const progressBar = document.getElementById('csvProgressBar');
    const progressText = document.getElementById('csvProgressText');
    const progressError = document.getElementById('csvProgressError');

    let currentBatch = localStorage.getItem('hrgoto_csv_batch') || '';
    let pollTimer = null;

    function endpoint(path) {
        const base = String(appUrl || '').replace(/\/+$/, '');
        return `${base}/index.php?url=${path.replace(/^\//, '')}`;
    }

    function updateProgress(completed, total, percentage, status, message, errorMessage) {
        const pct = Math.max(0, Math.min(100, Number(percentage || 0)));
        progressBar.style.width = `${pct}%`;
        progressBar.textContent = `${pct}%`;
        progressText.textContent = `${status || 'Queued'}: ${completed || 0}/${total || 0}. ${message || ''}`.trim();
        progressError.textContent = errorMessage || '';
    }

    async function pollStatus() {
        if (!currentBatch) return;
        try {
            const response = await fetch(`${endpoint('staff/csv-status')}&batch_id=${encodeURIComponent(currentBatch)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (!data.success) return;

            updateProgress(data.completed, data.total, data.percentage, data.status, data.progress_message, data.error_message);

            if (data.status === 'Completed') {
                clearInterval(pollTimer);
                pollTimer = null;
                processBtn.disabled = true;
                setTimeout(() => { window.location.reload(); }, 900);
            }
        } catch (e) {
            progressError.textContent = 'Status polling failed. Please refresh.';
        }
    }

    uploadBtn.addEventListener('click', async function () {
        progressError.textContent = '';
        if (!fileInput.files.length) {
            progressError.textContent = 'Select a CSV file first.';
            return;
        }

        const fd = new FormData();
        fd.append('csrf_token', csrfToken);
        fd.append('staff_csv', fileInput.files[0]);

        uploadBtn.disabled = true;
        try {
            const response = await fetch(endpoint('staff/csv-upload'), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: fd
            });
            const data = await response.json();
            if (!data.success) {
                progressError.textContent = data.message || 'Upload failed.';
                return;
            }

            currentBatch = data.batch_id;
            localStorage.setItem('hrgoto_csv_batch', currentBatch);
            processBtn.disabled = false;
            updateProgress(0, data.total_rows || 0, 0, 'Queued', data.message || 'CSV uploaded', '');
        } catch (e) {
            progressError.textContent = 'Upload failed due to network/server error.';
        } finally {
            uploadBtn.disabled = false;
        }
    });

    processBtn.addEventListener('click', async function () {
        if (!currentBatch) {
            progressError.textContent = 'No batch found. Upload CSV first.';
            return;
        }

        processBtn.disabled = true;
        progressError.textContent = '';
        try {
            const payload = new FormData();
            payload.append('csrf_token', csrfToken);
            payload.append('batch_id', currentBatch);

            const response = await fetch(endpoint('staff/csv-process'), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: payload
            });
            const data = await response.json();
            if (!data.success) {
                progressError.textContent = data.message || 'Processing failed.';
                processBtn.disabled = false;
                return;
            }

            updateProgress(data.completed_rows, data.total_rows, Math.floor(((data.completed_rows || 0) / Math.max(1, data.total_rows || 1)) * 100), data.status, 'Processing in progress', (data.errors || []).slice(-1)[0] || '');

            if (!pollTimer) {
                pollTimer = setInterval(pollStatus, 1500);
            }
        } catch (e) {
            progressError.textContent = 'Processing request failed.';
            processBtn.disabled = false;
        }
    });

    if (currentBatch) {
        processBtn.disabled = false;
        pollStatus();
        pollTimer = setInterval(pollStatus, 2000);
    }
})();
</script>
<?php endif; ?>