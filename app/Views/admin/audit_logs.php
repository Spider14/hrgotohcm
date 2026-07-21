<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$search = trim((string)($_GET['search'] ?? ''));
?>

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3">
                <i class="fas fa-bars"></i>
            </button>
            <span class="fw-bold">Audit Trail</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm p-4 mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="url" value="admin/audit-logs">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Action, description, IP, user agent..." value="<?php echo \App\Helpers\Security::escape($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-search me-1"></i>Filter</button>
                </div>
                <?php if ($search !== ''): ?>
                <div class="col-md-2">
                    <a href="<?php echo $appUrl; ?>/index.php?url=admin/audit-logs" class="btn btn-sm btn-outline-secondary w-100"><i class="fas fa-times me-1"></i>Clear</a>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <?php require __DIR__ . '/../dashboard/layouts/flash.php'; ?>

        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-3">Audit Log Entries</h5>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Date/Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No audit log entries found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-nowrap" style="font-size:0.85rem;"><?php echo \App\Helpers\Security::escape((string)$log['created_at']); ?></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($log['fullname'] ?? ($log['user_id'] ? 'User #' . $log['user_id'] : 'Guest'))); ?></td>
                            <td><span class="badge bg-info"><?php echo \App\Helpers\Security::escape((string)$log['action']); ?></span></td>
                            <td><?php echo \App\Helpers\Security::escape((string)($log['description'] ?? '')); ?></td>
                            <td class="text-nowrap" style="font-size:0.85rem;"><?php echo \App\Helpers\Security::escape((string)$log['ip_address']); ?></td>
                            <td style="font-size:0.8rem;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo \App\Helpers\Security::escape((string)($log['user_agent'] ?? '')); ?>"><?php echo \App\Helpers\Security::escape((string)($log['user_agent'] ?? '')); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination pagination-sm justify-content-center mt-3">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo $appUrl; ?>/index.php?url=admin/audit-logs&page=<?php echo $page - 1; ?><?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                    </li>
                    <li class="page-item disabled">
                        <span class="page-link">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    </li>
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo $appUrl; ?>/index.php?url=admin/audit-logs&page=<?php echo $page + 1; ?><?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
