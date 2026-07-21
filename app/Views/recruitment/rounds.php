<?php
/**
 * @var array $jobs
 * @var array $roundsByJob
 * @var array $applicationsByJob
 * @var array $assignments
 * @var string $appUrl
 */
$csrf = \App\Helpers\Security::generateCsrfToken(); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3">
                <i class="fas fa-bars"></i>
            </button>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb m-0 bg-transparent p-0">
                    <li class="breadcrumb-item text-dark"><a href="<?php echo $appUrl; ?>/dashboard" class="text-decoration-none fw-semibold">Dashboard</a></li>
                    <li class="breadcrumb-item text-dark fw-semibold">Recruitment</li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Recruitment Rounds</li>
                </ol>
            </nav>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">

        <?php if ($msg = \App\Helpers\Security::getFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo \App\Helpers\Security::escape($msg); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Round Stages by Job -->
        <div class="card border-0 shadow-sm rounded-lg p-4 bg-white mb-4">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                <div>
                    <h4 class="fw-bold text-dark m-0"><i class="fas fa-layer-group me-2" style="color: #1d2a52;"></i>Round Stages by Job</h4>
                    <p class="text-muted small m-0 mt-1">Define evaluation stages (e.g. Screening, Interview) for each job posting.</p>
                </div>
                <button class="btn btn-sm px-3 fw-bold shadow-sm" style="color:#fff;background-color:#1d2a52;border-color:#1d2a52;" data-bs-toggle="modal" data-bs-target="#roundModal" onclick="resetRoundForm()"><i class="fas fa-plus me-1"></i>New Round</button>
            </div>

            <div class="table-responsive">
                <table id="roundsTable" class="table table-striped table-bordered align-middle w-100">
                    <thead class="table-dark text-uppercase tracking-wider small text-white">
                        <tr>
                            <th class="border-0">Job</th>
                            <th class="border-0">Stage</th>
                            <th class="border-0 text-center">Order</th>
                            <th class="border-0 text-center">Passing Score</th>
                            <th class="border-0 text-center no-export">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="small font-sans">
                        <?php if (empty($roundsByJob)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No round stages defined yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($roundsByJob as $jobId => $data): ?>
                                <?php if (empty($data['rounds'])): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?php echo \App\Helpers\Security::escape($data['job_title']); ?></td>
                                    <td colspan="4" class="text-muted small">No rounds defined</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($data['rounds'] as $r): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?php echo \App\Helpers\Security::escape($r['job_title']); ?></td>
                                        <td><?php echo \App\Helpers\Security::escape($r['stage_name']); ?></td>
                                        <td class="text-center font-monospace fw-bold"><?php echo (int)$r['stage_order']; ?></td>
                                        <td class="text-center font-monospace"><?php echo $r['passing_score'] !== null ? (float)$r['passing_score'] : '-'; ?></td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-sm px-2 fw-bold shadow-sm" style="color:#1d2a52;border:1.5px solid #1d2a52;background:transparent;" onclick="editRound(<?php echo htmlspecialchars(json_encode($r)); ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this round?')">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                                    <input type="hidden" name="action" value="delete-round">
                                                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                                    <button class="btn btn-sm px-2 fw-bold shadow-sm" style="color:#dc3545;border:1.5px solid #dc3545;background:transparent;" title="Delete"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Assign Applicants to Round -->
        <div class="card border-0 shadow-sm rounded-lg p-4 bg-white mb-4">
            <div class="border-bottom pb-3 mb-3">
                <h4 class="fw-bold text-dark m-0"><i class="fas fa-user-plus me-2" style="color: #1d2a52;"></i>Assign Applicants to Round</h4>
                <p class="text-muted small m-0 mt-1">Select a round and choose pending applicants to assign.</p>
            </div>

            <form method="post" class="row g-3 align-items-end">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="action" value="assign">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Round</label>
                    <select name="round_id" class="form-select" required>
                        <option value="">Select round...</option>
                        <?php foreach ($roundsByJob as $jobId => $data): ?>
                            <?php foreach ($data['rounds'] as $r): ?>
                            <option value="<?php echo (int)$r['id']; ?>"><?php echo \App\Helpers\Security::escape($data['job_title'] . ' — ' . $r['stage_name']); ?></option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label small fw-bold">Applicants</label>
                    <div class="p-3 border rounded bg-light" style="max-height: 130px; overflow-y: auto;">
                        <?php 
                        $hasApps = false;
                        foreach ($applicationsByJob as $jobId => $apps): 
                            foreach ($apps as $a): 
                                $hasApps = true;
                        ?>
                        <label class="form-check-label me-3 d-inline-block mb-1" style="font-size:0.85rem;">
                            <input type="checkbox" name="application_ids[]" value="<?php echo (int)$a['id']; ?>">
                            <?php echo \App\Helpers\Security::escape($a['first_name'] . ' ' . $a['last_name'] . ' (' . $a['reference_number'] . ')'); ?>
                        </label>
                        <?php 
                            endforeach;
                        endforeach; 
                        if (!$hasApps): 
                        ?>
                        <span class="text-muted small">No pending applicants available</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn px-3 fw-bold shadow-sm w-100" style="color:#fff;background-color:#1d2a52;border-color:#1d2a52;" <?php echo !$hasApps ? 'disabled' : ''; ?>>Assign</button>
                </div>
            </form>
        </div>

        <!-- Round Scoreboard -->
        <div class="card border-0 shadow-sm rounded-lg p-4 bg-white">
            <div class="border-bottom pb-3 mb-3">
                <h4 class="fw-bold text-dark m-0"><i class="fas fa-table me-2" style="color: #1d2a52;"></i>Round Scoreboard</h4>
                <p class="text-muted small m-0 mt-1">Track applicant scores, statuses (Cleared/Failed/Pending), and add evaluator comments.</p>
            </div>

            <div class="table-responsive">
                <table id="scoreTable" class="table table-striped table-bordered align-middle w-100">
                    <thead class="table-dark text-uppercase tracking-wider small text-white">
                        <tr>
                            <th class="border-0">Job</th>
                            <th class="border-0">Round</th>
                            <th class="border-0">Applicant</th>
                            <th class="border-0 text-center">Score</th>
                            <th class="border-0 text-center">Status</th>
                            <th class="border-0">Comment</th>
                            <th class="border-0 text-center no-export">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="small font-sans">
                        <?php if (empty($assignments)): ?>
                        <?php /* DataTable needs at least one normal row to detect column count; hide it after init */ ?>
                        <tr style="display:none;"><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                        <?php else: ?>
                            <?php foreach ($assignments as $ar): ?>
                            <tr>
                                <td><?php echo \App\Helpers\Security::escape($ar['job_title'] ?? ''); ?></td>
                                <td class="fw-semibold"><?php echo \App\Helpers\Security::escape($ar['stage_name']); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($ar['first_name'] . ' ' . $ar['last_name']); ?>
                                    <br><small class="text-muted font-monospace"><?php echo \App\Helpers\Security::escape($ar['reference_number']); ?></small></td>
                                <td class="text-center"><span class="badge <?php echo $ar['score'] !== null ? ($ar['score'] >= 70 ? 'bg-success' : ($ar['score'] >= 50 ? 'bg-warning text-dark' : 'bg-danger')) : 'bg-secondary'; ?> px-3 py-2 font-monospace tracking-wide rounded-pill" style="font-size:0.75rem;"><?php echo $ar['score'] !== null ? (float)$ar['score'] : '-'; ?></span></td>
                                <td class="text-center">
                                    <span class="badge <?php echo $ar['status'] === 'cleared' ? 'bg-success' : ($ar['status'] === 'failed' ? 'bg-danger' : 'bg-secondary'); ?> px-3 py-2 font-monospace tracking-wide rounded-pill text-uppercase" style="font-size:0.7rem;"><?php echo \App\Helpers\Security::escape($ar['status']); ?></span>
                                </td>
                                <td class="small text-muted" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo \App\Helpers\Security::escape($ar['comment'] ?? '-'); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm px-2 fw-bold shadow-sm" style="color:#1d2a52;border:1.5px solid #1d2a52;background:transparent;" onclick="scoreApplicant(<?php echo htmlspecialchars(json_encode($ar)); ?>)" title="Score"><i class="fas fa-pencil"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Create/Edit Round Modal -->
<div class="modal fade" id="roundModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="action" id="roundFormAction" value="create-round">
                <input type="hidden" name="id" id="roundId" value="0">
                <div class="modal-header" style="background:#1d2a52;color:#fff;">
                    <h5 class="modal-title fw-bold" id="roundModalTitle">New Round</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Job <span class="text-danger">*</span></label>
                        <select name="job_id" id="roundJobId" class="form-select" required>
                            <option value="">Select job...</option>
                            <?php foreach ($jobs as $j): ?>
                            <option value="<?php echo (int)$j['id']; ?>"><?php echo \App\Helpers\Security::escape($j['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Stage Name <span class="text-danger">*</span></label>
                        <input type="text" name="stage_name" id="roundStageName" class="form-control" required placeholder="e.g. Screening, Interview, Practical">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Order</label>
                            <input type="number" name="stage_order" id="roundOrder" class="form-control" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Passing Score</label>
                            <input type="number" name="passing_score" id="roundPassingScore" class="form-control" min="0" max="100" step="0.01">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm px-3 fw-bold shadow-sm" style="color:#6c757d;border:1.5px solid #6c757d;background:transparent;" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm px-3 fw-bold shadow-sm" style="color:#fff;background-color:#1d2a52;border-color:#1d2a52;">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Score Applicant Modal -->
<div class="modal fade" id="scoreModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="action" value="score">
                <input type="hidden" name="ar_id" id="scoreArId" value="0">
                <div class="modal-header" style="background:#1d2a52;color:#fff;">
                    <h5 class="modal-title fw-bold">Score Applicant</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3"><strong id="scoreApplicantName" class="text-dark"></strong> <br><span id="scoreRoundName" class="text-muted small"></span></p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Score</label>
                        <input type="number" name="score" id="scoreValue" class="form-control" min="0" max="100" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" id="scoreStatus" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="cleared">Cleared</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Comment</label>
                        <textarea name="comment" id="scoreComment" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm px-3 fw-bold shadow-sm" style="color:#6c757d;border:1.5px solid #6c757d;background:transparent;" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm px-3 fw-bold shadow-sm" style="color:#fff;background-color:#1d2a52;border-color:#1d2a52;">Save Score</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .tracking-wider { letter-spacing: 0.05em; }
    div.dataTables_wrapper div.dataTables_filter input {
        border: 1.5px solid #d1d5db !important;
        border-radius: 6px !important;
        padding: 0.35rem 0.75rem !important;
        font-weight: 600 !important;
    }
    div.dataTables_wrapper div.dataTables_filter input:focus {
        border-color: #1d2a52 !important;
        box-shadow: 0 0 0 0.2rem rgba(29,42,82,0.2) !important;
        outline: none;
    }
    div.dataTables_wrapper div.dataTables_length select {
        border: 1.5px solid #d1d5db !important;
        border-radius: 6px !important;
        padding: 0.25rem 0.5rem !important;
    }
</style>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    if ($('#roundsTable').length) {
        $('#roundsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 15,
            order: [],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search rounds..."
            },
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
        });
    }

    if ($('#scoreTable').length) {
        $('#scoreTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 15,
            order: [],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search scoreboard...",
                emptyTable: "No assignments yet."
            },
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
        });
    }
});

function resetRoundForm() {
    document.getElementById('roundFormAction').value = 'create-round';
    document.getElementById('roundId').value = '0';
    document.getElementById('roundModalTitle').textContent = 'New Round';
    document.getElementById('roundJobId').value = '';
    document.getElementById('roundStageName').value = '';
    document.getElementById('roundOrder').value = '';
    document.getElementById('roundPassingScore').value = '';
}

function editRound(r) {
    document.getElementById('roundFormAction').value = 'update-round';
    document.getElementById('roundId').value = r.id;
    document.getElementById('roundModalTitle').textContent = 'Edit Round';
    document.getElementById('roundJobId').value = r.job_id;
    document.getElementById('roundStageName').value = r.stage_name;
    document.getElementById('roundOrder').value = r.stage_order;
    document.getElementById('roundPassingScore').value = r.passing_score || '';
    new bootstrap.Modal(document.getElementById('roundModal')).show();
}

function scoreApplicant(ar) {
    document.getElementById('scoreArId').value = ar.id;
    document.getElementById('scoreApplicantName').textContent = ar.first_name + ' ' + ar.last_name;
    document.getElementById('scoreRoundName').textContent = ar.stage_name + ' — ' + ar.reference_number;
    document.getElementById('scoreValue').value = ar.score || '';
    document.getElementById('scoreStatus').value = ar.status || 'pending';
    document.getElementById('scoreComment').value = ar.comment || '';
    new bootstrap.Modal(document.getElementById('scoreModal')).show();
}
</script>
