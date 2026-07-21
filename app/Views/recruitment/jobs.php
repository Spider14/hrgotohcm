<?php
/**
 * @var array $jobs
 * @var array $departments
 * @var array $ranks
 * @var string $appUrl
 */
$csrf = \App\Helpers\Security::generateCsrfToken(); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

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
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Job Postings</li>
                </ol>
            </nav>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">

        <?php if ($msg = \App\Helpers\Security::getFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo \App\Helpers\Security::escape($msg); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($msg = \App\Helpers\Security::getFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?php echo \App\Helpers\Security::escape($msg); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-lg p-4 bg-white">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                <div>
                    <h4 class="fw-bold text-dark m-0"><i class="fas fa-briefcase me-2" style="color: #1d2a52;"></i>Job Postings</h4>
                    <p class="text-muted small m-0 mt-1">Create, edit, and manage open job positions.</p>
                </div>
                <button class="btn btn-sm px-3 fw-bold shadow-sm" style="color:#fff;background-color:#1d2a52;border-color:#1d2a52;" data-bs-toggle="modal" data-bs-target="#jobModal" onclick="resetJobForm()"><i class="fas fa-plus me-1"></i>New Job</button>
            </div>

            <div class="table-responsive">
                <table id="jobsTable" class="table table-striped table-bordered align-middle w-100">
                    <thead class="table-dark text-uppercase tracking-wider small text-white">
                        <tr>
                            <th class="border-0">Title</th>
                            <th class="border-0">Department</th>
                            <th class="border-0">Type</th>
                            <th class="border-0">Location</th>
                            <th class="border-0">Rank</th>
                            <th class="border-0">Deadline</th>
                            <th class="border-0 text-center">Status</th>
                            <th class="border-0 text-center no-export">Applicants</th>
                            <th class="border-0 text-center no-export">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="small font-sans">
                        <?php foreach ($jobs as $j): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?php echo \App\Helpers\Security::escape($j['title']); ?></td>
                            <td><?php echo \App\Helpers\Security::escape($j['department'] ?? ''); ?></td>
                            <td><span class="badge bg-secondary px-3 py-2 font-monospace tracking-wide rounded-pill text-uppercase" style="font-size:0.7rem;"><?php echo \App\Helpers\Security::escape($j['type'] ?? 'full-time'); ?></span></td>
                            <td><?php echo \App\Helpers\Security::escape($j['location'] ?? ''); ?></td>
                            <td><span class="badge bg-dark px-3 py-2 font-monospace tracking-wide rounded-pill" style="font-size:0.7rem;"><?php echo \App\Helpers\Security::escape($j['salary_range'] ?? '-'); ?></span></td>
                            <td class="font-monospace"><?php echo $j['deadline'] ? date('d M Y', strtotime($j['deadline'])) : '-'; ?></td>
                            <td class="text-center">
                                <form method="post" class="d-inline" onsubmit="return confirm('Toggle status?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$j['id']; ?>">
                                    <input type="hidden" name="action" value="toggle-status">
                                    <button type="submit" class="badge border-0 <?php echo $j['status'] === 'open' ? 'bg-success' : 'bg-danger'; ?> px-3 py-2 font-monospace tracking-wide rounded-pill text-uppercase" style="font-size:0.7rem;cursor:pointer;"><?php echo \App\Helpers\Security::escape($j['status']); ?></button>
                                </form>
                            </td>
                            <td class="text-center fw-bold font-monospace"><?php echo (int)$j['applicant_count']; ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-sm px-2 fw-bold shadow-sm" style="color:#1d2a52;border:1.5px solid #1d2a52;background:transparent;" onclick="editJob(<?php echo htmlspecialchars(json_encode($j)); ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this job?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <input type="hidden" name="id" value="<?php echo (int)$j['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button class="btn btn-sm px-2 fw-bold shadow-sm" style="color:#dc3545;border:1.5px solid #dc3545;background:transparent;" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Create/Edit Job Modal -->
<div class="modal fade" id="jobModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="action" id="jobFormAction" value="create">
                <input type="hidden" name="id" id="jobId" value="0">
                <div class="modal-header" style="background:#1d2a52;color:#fff;">
                    <h5 class="modal-title fw-bold" id="jobModalTitle">New Job</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Job Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="jobTitle" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Department <span class="text-danger">*</span></label>
                            <select name="department" id="jobDept" class="form-select" required>
                                <option value="">Select department...</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo \App\Helpers\Security::escape($dept); ?>"><?php echo \App\Helpers\Security::escape($dept); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Type</label>
                            <select name="type" id="jobType" class="form-select">
                                <option value="full-time">Full Time</option>
                                <option value="part-time">Part Time</option>
                                <option value="contract">Contract</option>
                                <option value="internship">Internship</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Location</label>
                            <input type="text" name="location" id="jobLocation" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Rank</label>
                            <select name="salary_range" id="jobRank" class="form-select">
                                <option value="">Select rank...</option>
                                <?php foreach ($ranks as $rank): ?>
                                <option value="<?php echo \App\Helpers\Security::escape($rank); ?>"><?php echo \App\Helpers\Security::escape($rank); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Deadline</label>
                            <input type="date" name="deadline" id="jobDeadline" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Job Description</label>
                            <textarea name="description" id="jobDesc" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Qualification</label>
                            <textarea name="requirements" id="jobReqs" class="form-control" rows="3"></textarea>
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

<style>
    .tracking-wider { letter-spacing: 0.05em; }
    .dt-buttons .btn {
        font-size: 0.85rem !important;
        font-weight: 700 !important;
        border-radius: 6px !important;
        padding: 0.4rem 1.2rem !important;
        background-color: #ffffff !important;
        border: 1.5px solid #d1d5db !important;
        color: #374151 !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        transition: all 0.2s ease-in-out !important;
    }
    .dt-buttons .btn:hover {
        background-color: #f9fafb !important;
        border-color: #9ca3af !important;
        color: #111827 !important;
    }
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
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script>
$(document).ready(function() {
    if ($('#jobsTable').length) {
        $('#jobsTable').DataTable({
            dom: "<'row mb-3'<'col-12 col-md-6'B><'col-12 col-md-6 text-end'f>>" +
                 "<'row'<'col-12'tr>>" +
                 "<'row mt-3'<'col-12 col-md-5 small text-muted'i><'col-12 col-md-7 d-flex justify-content-end'p>>",
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1 text-success"></i> Excel',
                    className: 'btn btn-sm mx-1',
                    title: 'HRGoTo HCM - Job Postings',
                    exportOptions: { columns: ':not(.no-export)' }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf me-1 text-danger"></i> PDF',
                    className: 'btn btn-sm mx-1',
                    title: 'HRGoTo HCM - Job Postings',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: { columns: ':not(.no-export)' }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print me-1 text-dark"></i> Print',
                    className: 'btn btn-sm mx-1',
                    title: 'HRGoTo HCM - Job Postings',
                    exportOptions: { columns: ':not(.no-export)' }
                }
            ],
        pageLength: 15,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search jobs..."
        },
        columnDefs: [
            { orderable: false, targets: -1 }
        ]
    });
    }
});

function resetJobForm() {
    document.getElementById('jobFormAction').value = 'create';
    document.getElementById('jobId').value = '0';
    document.getElementById('jobModalTitle').textContent = 'New Job';
    document.getElementById('jobTitle').value = '';
    document.getElementById('jobDept').value = '';
    document.getElementById('jobDesc').value = '';
    document.getElementById('jobReqs').value = '';
    document.getElementById('jobLocation').value = '';
    document.getElementById('jobType').value = 'full-time';
    document.getElementById('jobRank').value = '';
    document.getElementById('jobDeadline').value = '';
}

function editJob(j) {
    document.getElementById('jobFormAction').value = 'update';
    document.getElementById('jobId').value = j.id;
    document.getElementById('jobModalTitle').textContent = 'Edit Job';
    document.getElementById('jobTitle').value = j.title;
    document.getElementById('jobDept').value = j.department || '';
    document.getElementById('jobDesc').value = j.description || '';
    document.getElementById('jobReqs').value = j.requirements || '';
    document.getElementById('jobLocation').value = j.location || '';
    document.getElementById('jobType').value = j.type || 'full-time';
    document.getElementById('jobRank').value = j.salary_range || '';
    document.getElementById('jobDeadline').value = j.deadline || '';
    new bootstrap.Modal(document.getElementById('jobModal')).show();
}
</script>
