<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold"><i class="fas fa-hard-drive me-2"></i>Backup Center</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <?php require __DIR__ . '/../dashboard/layouts/flash.php'; ?>

        <!-- Action cards side by side -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 text-center d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-database fa-3x text-primary mb-3"></i>
                        <h6 class="fw-bold mb-1">Database Backup</h6>
                        <p class="text-muted small mb-3">Export all tables, routines, and triggers as SQL</p>
                        <form method="POST" action="<?php echo $appUrl; ?>/admin/backup/create-db" onsubmit="submitBackup('db', this); return false;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="fas fa-play me-1"></i>Create DB Backup
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 text-center d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-folder-open fa-3x text-success mb-3"></i>
                        <h6 class="fw-bold mb-1">File Backup</h6>
                        <p class="text-muted small mb-3">Archive all uploaded files (documents, photos, etc.)</p>
                        <form method="POST" action="<?php echo $appUrl; ?>/admin/backup/create-files" onsubmit="submitBackup('files', this); return false;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                            <button type="submit" class="btn btn-success btn-sm px-4">
                                <i class="fas fa-play me-1"></i>Create File Backup
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup listing table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white small fw-bold py-2 px-3 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-archive me-1"></i>Saved Backups</span>
                <span class="badge bg-light text-dark"><?php echo count($backups); ?> file(s)</span>
            </div>
            <?php if (empty($backups)): ?>
            <div class="card-body p-4 text-center text-muted small">
                <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                No backups yet. Create one above.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-bordered align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th style="width:70px;">Type</th>
                            <th>File</th>
                            <th style="width:100px;">Size</th>
                            <th style="width:160px;">Created</th>
                            <th class="no-export" style="width:120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $b): ?>
                        <tr>
                            <td>
                                <?php if ($b['type'] === 'sql'): ?>
                                    <span class="badge bg-primary"><i class="fas fa-database me-1"></i>SQL</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="fas fa-file-archive me-1"></i>ZIP</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo \App\Helpers\Security::escape($b['name']); ?></code></td>
                            <td><?php echo $b['size_hr']; ?></td>
                            <td><?php echo $b['date']; ?></td>
                            <td class="no-export">
                                <div class="d-flex gap-1">
                                    <a href="<?php echo $appUrl; ?>/admin/backup/download?name=<?php echo rawurlencode($b['name']); ?>" class="btn btn-outline-success btn-sm" title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <form method="POST" action="<?php echo $appUrl; ?>/admin/backup/delete" class="d-inline" onsubmit="return confirm('Delete this backup?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <input type="hidden" name="name" value="<?php echo \App\Helpers\Security::escape($b['name']); ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
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

<!-- Progress Modal -->
<div class="modal fade" id="backupProgressModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="fas fa-spinner fa-pulse me-2" id="progressIcon"></i><span id="progressTitle">Backup in Progress</span></h6>
            </div>
            <div class="modal-body text-center py-4">
                <div class="progress mb-3" style="height:28px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%">0%</div>
                </div>
                <p id="progressMessage" class="small text-muted mb-3">Initializing...</p>
                <div class="d-flex justify-content-between small text-muted border-top pt-2">
                    <span id="progressElapsed">Elapsed: 0s</span>
                    <span id="progressEta">ETA: --</span>
                </div>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0" id="progressFooter" style="display:none;">
                <button type="button" class="btn btn-primary btn-sm px-4" data-bs-dismiss="modal" onclick="if (window.backupDone) location.reload();">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let backupPollInterval = null, backupModalInstance = null, backupRetries = 0;

function submitBackup(type, form) {
    var formData = new FormData(form);
    document.getElementById('progressTitle').textContent = type === 'db' ? 'Database Backup' : 'File Backup';
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressBar').textContent = '0%';
    document.getElementById('progressBar').className = 'progress-bar progress-bar-striped progress-bar-animated';
    document.getElementById('progressMessage').textContent = 'Starting...';
    document.getElementById('progressElapsed').textContent = 'Elapsed: 0s';
    document.getElementById('progressEta').textContent = 'ETA: --';
    document.getElementById('progressFooter').style.display = 'none';
    window.backupDone = false;
    backupRetries = 0;
    var modalEl = document.getElementById('backupProgressModal');
    backupModalInstance = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
    backupModalInstance.show();
    fetch(form.action, { method: 'POST', body: formData });
    if (backupPollInterval) clearInterval(backupPollInterval);
    backupPollInterval = setInterval(pollProgress, 600);
}

function pollProgress() {
    fetch('<?php echo $appUrl; ?>/admin/backup/progress')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'idle') {
                if (++backupRetries > 30) { clearInterval(backupPollInterval); backupPollInterval = null; }
                return;
            }
            backupRetries = 0;
            var pct = data.percent || 0;
            var bar = document.getElementById('progressBar');
            bar.style.width = pct + '%';
            bar.textContent = pct + '%';
            document.getElementById('progressMessage').textContent = data.message || '';
            if (data.elapsed !== undefined) {
                document.getElementById('progressElapsed').textContent = 'Elapsed: ' + data.elapsed + 's';
            }
            if (data.eta !== undefined && data.eta > 0) {
                document.getElementById('progressEta').textContent = 'ETA: ~' + data.eta + 's';
            }
            if (data.status === 'completed') {
                clearInterval(backupPollInterval);
                backupPollInterval = null;
                bar.classList.remove('progress-bar-animated');
                bar.classList.add('bg-success');
                document.getElementById('progressIcon').className = 'fas fa-check-circle text-success me-2';
                document.getElementById('progressEta').textContent = 'Done!';
                document.getElementById('progressFooter').style.display = 'flex';
                window.backupDone = true;
                if (data.filename) {
                    var a = document.createElement('a');
                    a.href = '<?php echo $appUrl; ?>/admin/backup/download?name=' + encodeURIComponent(data.filename);
                    a.download = data.filename;
                    a.click();
                }
                setTimeout(function() { location.reload(); }, 2000);
            } else if (data.status === 'error') {
                clearInterval(backupPollInterval);
                backupPollInterval = null;
                bar.classList.remove('progress-bar-animated');
                bar.classList.add('bg-danger');
                document.getElementById('progressIcon').className = 'fas fa-times-circle text-danger me-2';
                document.getElementById('progressFooter').style.display = 'flex';
            } else {
                document.getElementById('progressIcon').className = 'fas fa-spinner fa-pulse me-2';
            }
        })
        .catch(function() { if (++backupRetries > 30) { clearInterval(backupPollInterval); backupPollInterval = null; } });
}
</script>
