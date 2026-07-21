<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$role = $_SESSION['user_role'] ?? 'Staff';
$csrf = \App\Helpers\Security::generateCsrfToken();
$isStaffPortal = ($role === 'Staff');

$leaveData = $leaveRows->fetchAll(PDO::FETCH_ASSOC);
$promotionData = $promotionRows->fetchAll(PDO::FETCH_ASSOC);
$appraisalData = $appraisalRows->fetchAll(PDO::FETCH_ASSOC);
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
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Staff Services</li>
                </ol>
            </nav>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="row g-4 mb-5">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 rounded-lg" style="border-left: 5px solid #2b6cb0 !important;">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase font-monospace tracking-wider text-xs mb-2">Profile</h6>
                            <h5 class="fw-bold m-0 text-dark"><?php echo \App\Helpers\Security::escape((string)($profile['fullname'] ?? '')); ?></h5>
                            <small class="text-muted"><?php echo \App\Helpers\Security::escape((string)($profile['dept_name'] ?? '')); ?></small>
                        </div>
                        <div class="stat-icon-wrapper rounded bg-blue-light p-3 text-primary"><i class="fas fa-user fa-2x"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 rounded-lg" style="border-left: 5px solid #d69e2e !important;">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase font-monospace tracking-wider text-xs mb-2">Leave Requests</h6>
                            <h3 class="fw-bold m-0 text-warning"><?php echo count($leaveData); ?></h3>
                        </div>
                        <div class="stat-icon-wrapper rounded bg-warning-light p-3 text-warning"><i class="fas fa-calendar-check fa-2x"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 rounded-lg" style="border-left: 5px solid #319795 !important;">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase font-monospace tracking-wider text-xs mb-2">Promotion Requests</h6>
                            <h3 class="fw-bold m-0 text-teal"><?php echo count($promotionData); ?></h3>
                        </div>
                        <div class="stat-icon-wrapper rounded bg-teal-light p-3 text-teal"><i class="fas fa-arrow-trend-up fa-2x"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 rounded-lg" style="border-left: 5px solid #2f855a !important;">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase font-monospace tracking-wider text-xs mb-2">Appraisals</h6>
                            <h3 class="fw-bold m-0 text-success"><?php echo count($appraisalData); ?></h3>
                        </div>
                        <div class="stat-icon-wrapper rounded bg-success-light p-3 text-success"><i class="fas fa-star-half-stroke fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-lg p-4 bg-white mb-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-user-gear me-2 text-primary"></i>Update Personal Information</h5>
            <form method="POST" action="<?php echo $appUrl; ?>/staff/services/profile" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <div class="col-12 col-md-4">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="fullname" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape((string)($profile['fullname'] ?? '')); ?>" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Primary Phone</label>
                    <input type="text" name="phone_one" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape((string)($profile['phone_one'] ?? '')); ?>" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Secondary Phone</label>
                    <input type="text" name="phone_two" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape((string)($profile['phone_two'] ?? '')); ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Hometown</label>
                    <input type="text" name="hometown" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape((string)($profile['hometown'] ?? '')); ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Region</label>
                    <input type="text" name="region" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape((string)($profile['region'] ?? '')); ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Nationality</label>
                    <input type="text" name="nationality" class="form-control form-control-sm" value="<?php echo \App\Helpers\Security::escape((string)($profile['nationality'] ?? 'Ghanaian')); ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Marital Status</label>
                    <select name="marital_status" class="form-select form-select-sm">
                        <?php foreach (['single', 'married', 'widowed', 'divorced'] as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo (($profile['marital_status'] ?? '') === $status) ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Immediate Supervisor</label>
                    <select name="supervisor_user_id" class="form-select form-select-sm">
                        <option value="0">Select supervisor</option>
                        <?php foreach ($supervisors as $sp): ?>
                            <option value="<?php echo (int)$sp['id']; ?>" <?php echo ((int)($profile['supervisor_user_id'] ?? 0) === (int)$sp['id']) ? 'selected' : ''; ?>>
                                <?php echo \App\Helpers\Security::escape((string)$sp['fullname']); ?>
                                <?php if (!empty($sp['staff_id_card'])): ?>
                                    (<?php echo \App\Helpers\Security::escape((string)$sp['staff_id_card']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Passport Photo</label>
                    <input type="file" name="avatar_photo" class="form-control form-control-sm" accept="image/jpeg,image/png">
                </div>
                <div class="col-12 d-grid d-md-flex justify-content-md-end">
                    <button class="btn btn-dark btn-sm fw-bold px-4" type="submit">Save Profile Update</button>
                </div>
            </form>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100">
                    <h6 class="fw-bold">Leave History</h6>
                    <div class="table-responsive">
                        <table id="servicesLeaveTable" class="table table-sm table-striped align-middle">
                            <thead><tr><th>Type</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                            <?php foreach ($leaveData as $row): ?>
                                <tr>
                                    <td><?php echo \App\Helpers\Security::escape((string)$row['leave_type']); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo \App\Helpers\Security::escape((string)$row['status']); ?></span></td>
                                    <td class="small"><?php echo \App\Helpers\Security::escape((string)$row['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4 mt-1">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100">
                    <h6 class="fw-bold">Promotion History</h6>
                    <div class="table-responsive">
                        <table id="servicesPromotionTable" class="table table-sm table-striped align-middle">
                            <thead><tr><th>Rank</th><th>Status</th><th class="no-export">Doc</th></tr></thead>
                            <tbody>
                            <?php foreach ($promotionData as $row): ?>
                                <tr>
                                    <td><?php echo \App\Helpers\Security::escape((string)($row['requested_rank'] ?? '')); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo \App\Helpers\Security::escape((string)$row['status']); ?></span></td>
                                    <td class="no-export">
                                        <?php if (!empty($row['supporting_document'])): ?>
                                            <a href="<?php echo $appUrl . \App\Helpers\Security::escape((string)$row['supporting_document']); ?>" target="_blank">Open</a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4 mt-1">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100">
                    <h6 class="fw-bold">Appraisal History</h6>
                    <div class="table-responsive">
                        <table id="servicesAppraisalTable" class="table table-sm table-striped align-middle">
                            <thead><tr><th>Period</th><th>Status</th><th>Score</th></tr></thead>
                            <tbody>
                            <?php foreach ($appraisalData as $row): ?>
                                <tr>
                                    <td><?php echo \App\Helpers\Security::escape((string)$row['period_label']); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo \App\Helpers\Security::escape((string)$row['status']); ?></span></td>
                                    <td><?php echo \App\Helpers\Security::escape((string)($row['self_score'] ?? '')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($('#servicesLeaveTable').length) {
        $('#servicesLeaveTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 5,
            lengthMenu: [[5, 10, 25], [5, 10, 25]],
            language: { search: "Search:", emptyTable: "No leave history." },
            order: [[2, 'desc']]
        });
    }
    if ($('#servicesPromotionTable').length) {
        $('#servicesPromotionTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 5,
            lengthMenu: [[5, 10, 25], [5, 10, 25]],
            language: { search: "Search:", emptyTable: "No promotion history." },
            order: [[0, 'asc']]
        });
    }
    if ($('#servicesAppraisalTable').length) {
        $('#servicesAppraisalTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 5,
            lengthMenu: [[5, 10, 25], [5, 10, 25]],
            language: { search: "Search:", emptyTable: "No appraisal history." },
            order: [[0, 'asc']]
        });
    }
});
</script>
