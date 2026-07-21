<?php
$notifications = [];
if (isset($_SESSION['user_id'])) {
    $db = \App\Core\Database::getConnection();
    $stmt = $db->prepare("SELECT id, title, message, type, created_at FROM notifications WHERE user_id = :uid AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
    $stmt->execute(['uid' => $_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);
}
$unreadCount = count($notifications);
$userFullname = $_SESSION['user_fullname'] ?? 'User';
$userInitials = strtoupper(substr($userFullname, 0, 2));
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
?>
<div class="ms-auto d-flex align-items-center gap-2">
    <button id="sidebarCollapseBtn" class="btn btn-sm btn-outline-secondary border-0 p-2" title="Toggle sidebar">
        <i class="fas fa-bars"></i>
    </button>
    <div class="dropdown position-relative">
        <a href="#" class="text-dark position-relative p-2 rounded-circle bg-light d-inline-block text-decoration-none" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-bell"></i>
            <?php if ($unreadCount > 0): ?>
                <span class="position-absolute badge rounded-pill bg-danger font-monospace text-xs" style="top: -4px; right: -8px;"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-2" aria-labelledby="notifDropdown" style="width: 290px;">
            <li class="dropdown-header fw-bold border-bottom pb-2 mb-2">Notifications</li>
            <?php if (empty($notifications)): ?>
                <li><span class="dropdown-item text-muted small text-center py-3">No unread notifications</span></li>
            <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                    <li><a class="dropdown-item rounded small text-wrap p-2 mb-1 bg-light notif-item" href="#" data-notif-id="<?php echo $n['id']; ?>">
                        <i class="fas fa-circle-dot text-<?php echo $n['type']; ?> me-2"></i>
                        <strong><?php echo \App\Helpers\Security::escape($n['title']); ?></strong><br>
                        <span class="text-muted"><?php echo \App\Helpers\Security::escape($n['message']); ?></span>
                    </a></li>
                <?php endforeach; ?>
            <?php endif; ?>
            <li><hr class="dropdown-divider my-1"></li>
            <li class="d-flex justify-content-between px-3 py-1">
                <a class="small text-primary text-decoration-none mark-all-read" href="#" style="cursor:pointer;">Mark all as read</a>
                <span class="small text-muted"><?php echo $unreadCount; ?> unread</span>
            </li>
        </ul>
    </div>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle text-dark" id="profileMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold text-xs font-monospace" style="width:36px;height:36px;"><?php echo $userInitials; ?></div>
            <span class="small fw-semibold d-none d-md-inline-block"><?php echo \App\Helpers\Security::escape($userFullname); ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-2" aria-labelledby="profileMenuDropdown">
            <li><span class="dropdown-item-text text-muted small py-1">Role: <strong class="badge bg-secondary"><?php echo \App\Helpers\Security::escape($_SESSION['user_role'] ?? ''); ?></strong></span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item small" href="#"><i class="fas fa-user-gear me-2 text-muted"></i> Account Settings</a></li>
            <li><a class="dropdown-item small text-danger" href="<?php echo $appUrl; ?>/logout"><i class="fas fa-power-off me-2"></i> Logout</a></li>
        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const appUrl = '<?php echo $appUrl; ?>';
    const csrfToken = '<?php echo \App\Helpers\Security::generateCsrfToken(); ?>';

    document.querySelectorAll('.notif-item').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.notifId;
            if (!id) return;
            fetch(appUrl + '/notifications/mark-read', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
                body: 'id=' + id + '&csrf_token=' + encodeURIComponent(csrfToken)
            }).then(function(r) { return r.json(); }).then(function() {
                const parent = el.closest('li');
                if (parent) parent.remove();
                const badge = document.querySelector('.badge.rounded-pill.bg-danger');
                const countEl = document.querySelector('.mark-all-read + span');
                if (badge) {
                    const c = parseInt(badge.textContent) - 1;
                    badge.textContent = c > 0 ? c : '';
                    if (c <= 0) badge.style.display = 'none';
                }
                if (countEl) {
                    const c = parseInt(countEl.textContent) - 1;
                    countEl.textContent = (c > 0 ? c : '0') + ' unread';
                }
                const remaining = document.querySelectorAll('.notif-item').length;
                if (remaining === 0) {
                    document.querySelector('.notif-item')?.closest('ul')?.querySelector('.dropdown-header')?.insertAdjacentHTML('afterend', '<li><span class="dropdown-item text-muted small text-center py-3">No unread notifications</span></li>');
                }
            });
        });
    });

    document.querySelector('.mark-all-read')?.addEventListener('click', function(e) {
        e.preventDefault();
        fetch(appUrl + '/notifications/mark-all-read', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: 'csrf_token=' + encodeURIComponent(csrfToken)
        }).then(function(r) { return r.json(); }).then(function() {
            document.querySelectorAll('.notif-item').forEach(function(el) {
                var li = el.closest('li');
                if (li) li.remove();
            });
            var badge = document.querySelector('.badge.rounded-pill.bg-danger');
            if (badge) { badge.textContent = ''; badge.style.display = 'none'; }
            var countEl = document.querySelector('.mark-all-read + span');
            if (countEl) countEl.textContent = '0 unread';
            var header = document.querySelector('.dropdown-header');
            if (header && !header.nextElementSibling?.querySelector('.notif-item')) {
                header.insertAdjacentHTML('afterend', '<li><span class="dropdown-item text-muted small text-center py-3">No unread notifications</span></li>');
            }
        });
    });
});
</script>