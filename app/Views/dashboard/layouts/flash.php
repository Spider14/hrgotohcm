<?php
use App\Helpers\Security;

// Session flashes
$flashes = Security::getFlash();
foreach (['success' => 'success', 'error' => 'danger', 'ok' => 'success', 'warning' => 'warning', 'info' => 'info'] as $key => $bsClass):
    $msg = $flashes[$key] ?? '';
    if ($msg !== ''): ?>
<div class="alert alert-<?php echo $bsClass; ?> alert-dismissible fade show m-0 rounded-0 small" role="alert" style="position:relative;z-index:999;">
    <div class="container-fluid px-4"><?php echo Security::escape($msg); ?></div>
    <button type="button" class="btn-close position-absolute top-50 end-0 translate-middle-y me-3" data-bs-dismiss="alert" aria-label="Close" style="font-size:0.7rem;"></button>
</div>
<?php
    endif;
endforeach;

// Legacy GET fallbacks
if (isset($_GET['ok']) && $_GET['ok'] !== ''): ?>
<div class="alert alert-success alert-dismissible fade show m-0 rounded-0 small" role="alert" style="position:relative;z-index:999;">
    <div class="container-fluid px-4"><?php echo Security::escape((string)$_GET['ok']); ?></div>
    <button type="button" class="btn-close position-absolute top-50 end-0 translate-middle-y me-3" data-bs-dismiss="alert" aria-label="Close" style="font-size:0.7rem;"></button>
</div>
<?php endif; ?>
<?php if (isset($_GET['error']) && $_GET['error'] !== ''): ?>
<div class="alert alert-danger alert-dismissible fade show m-0 rounded-0 small" role="alert" style="position:relative;z-index:999;">
    <div class="container-fluid px-4"><?php echo Security::escape((string)$_GET['error']); ?></div>
    <button type="button" class="btn-close position-absolute top-50 end-0 translate-middle-y me-3" data-bs-dismiss="alert" aria-label="Close" style="font-size:0.7rem;"></button>
</div>
<?php endif; ?>
