<?php
/**
 * @var array $stats
 * @var array $applicants
 * @var array $deptBreakdown
 * @var string $appUrl
 */
?>
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
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Applicants Overview</li>
                </ol>
            </nav>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-primary">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-primary-light text-primary me-3">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Total Applicants</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo $stats['total']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-scondary">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-secondary-light text-secondary me-3">
                            <i class="fas fa-clock-rotate-left fa-2x"></i>
                        </div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Applicants Pending Review</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo $stats['pending']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-success">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-success-light text-success me-3">
                            <i class="fas fa-user-check fa-2x"></i>
                        </div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Shortlisted Applicants</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo $stats['shortlisted']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-info">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-info-light text-info me-3">
                            <i class="fas fa-briefcase fa-2x"></i>
                        </div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">May 2026 Open Positions</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo $stats['open_jobs']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-warning">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-warning-light text-warning me-3">
                            <i class="fas fa-graduation-cap fa-2x"></i>
                        </div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Academic Applications</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo $stats['academic']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6">
                <div class="card border-0 shadow-sm rounded-lg p-3 bg-white h-100 border-start border-4 border-danger">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-danger-light text-danger me-3">
                            <i class="fas fa-building fa-2x"></i>
                        </div>
                        <div>
                            <span class="text-uppercase tracking-wider text-muted font-monospace small fw-bold d-block">Administrative Applications</span>
                            <h3 class="fw-extrabold text-dark m-0 mt-1"><?php echo $stats['administrative']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-lg p-4 bg-white mb-4">
            <div class="border-bottom pb-3 mb-3">
                <h4 class="fw-bold text-dark m-0"><i class="fas fa-chart-pie me-2" style="color: #1d2a52;"></i>Directorate/Departmental/Unit Distribution</h4>
                <p class="text-muted small m-0 mt-1">Total operational breakdown of received applications according to departments.</p>
            </div>
            <div class="row g-3">
                <?php foreach ($deptBreakdown as $dept): ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="p-3 border rounded bg-light d-flex justify-content-between align-items-center">
                            <span class="fw-semibold text-secondary text-truncate me-2" title="<?php echo \App\Helpers\Security::escape($dept['department_name']); ?>">
                                <?php echo \App\Helpers\Security::escape($dept['department_name']); ?>
                            </span>
                            <span class="badge bg-dark px-2 py-1 font-monospace rounded-pill"><?php echo $dept['total_count']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-lg p-4 bg-white">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                <div>
                    <h4 class="fw-bold text-dark m-0"><i class="fas fa-folder-open me-2" style="color: #1d2a52;"></i>Applications Received </h4>
                    <p class="text-muted small m-0 mt-1">Review, export, and track applicants moving through the institution's selection process.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table id="recruitmentDashboardTable" class="table table-striped table-bordered align-middle w-100">
                    <thead class="table-dark text-uppercase tracking-wider small text-white">
                        <tr>
                            <th class="border-0">Reference</th>
                            <th class="border-0">Applicant</th>
                            <th class="border-0">Phone</th>
                            <th class="border-0">Position</th>
                            <th class="border-0">Qualification</th>
                            <th class="border-0">Experience</th>
                            <th class="border-0 text-center">Status</th>
                            <th class="border-0 text-center no-export">Action</th>
                        </tr>
                    </thead>
                    <tbody class="small font-sans">
                        <?php foreach ($applicants as $row): 
                            $fullName = \App\Helpers\Security::escape($row['first_name'] . ' ' . $row['last_name']);
                            
                            $statusBadge = 'bg-secondary';
                            $statusLabel = \App\Helpers\Security::escape(ucfirst($row['status']));
                            switch($row['status']) {
                                case 'pending':     $statusBadge = 'bg-dark'; $statusLabel = 'Pending'; break;
                                case 'reviewing':   $statusBadge = 'bg-warning text-dark'; $statusLabel = 'Under Review'; break;
                                case 'shortlisted': $statusBadge = 'bg-info text-white'; $statusLabel = 'Shortlisted'; break;
                                case 'interviewed': $statusBadge = 'bg-primary'; $statusLabel = 'Interviewed'; break;
                                case 'rejected':    $statusBadge = 'bg-danger'; $statusLabel = 'Unsuccessful'; break;
                                case 'hired':       $statusBadge = 'bg-success'; $statusLabel = 'Employed'; break;
                            }
                        ?>
                            <tr>
                                <td class="font-monospace fw-bold" style="color: #1d2a52;"><?php echo \App\Helpers\Security::escape($row['reference_number']); ?></td>
                                <td class="fw-bold text-dark"><?php echo $fullName; ?></td>
                                <td class="font-monospace"><?php echo \App\Helpers\Security::escape($row['phone']); ?></td>
                                <td><span class="fw-semibold text-secondary"><?php echo \App\Helpers\Security::escape($row['job_title']); ?></span></td>
                                <td><?php echo \App\Helpers\Security::escape(htmlspecialchars_decode($row['highest_qualification'])); ?></td>
                                <td class="font-monospace fw-bold"><?php echo (int)$row['years_experience']; ?> Yrs</td>
                                <td class="text-center">
                                    <span class="badge <?php echo $statusBadge; ?> px-3 py-2 font-monospace tracking-wide rounded-pill text-uppercase" style="font-size:0.75rem;">
                                        <?php echo $statusLabel; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="<?php echo $appUrl; ?>/recruitment/view?id=<?php echo $row['id']; ?>" class="btn btn-custom-view btn-sm px-3 fw-bold shadow-sm">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<style>
    .metric-icon-box { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
    .bg-primary-light { background-color: rgba(2, 117, 216, 0.12); }
    .bg-warning-light { background-color: rgba(240, 173, 78, 0.12); }
    .bg-success-light { background-color: rgba(92, 184, 92, 0.12); }
    .bg-info-light { background-color: rgba(91, 192, 222, 0.12); }
    .bg-secondary-light { background-color: rgba(108, 117, 125, 0.12); }
    .bg-danger-light { background-color: rgba(220, 53, 69, 0.12); }
    .tracking-wider { tracking-spacing: 0.05em; }
    .fw-extrabold { font-weight: 800; }
    
    /* Re-styled Custom Readability Controls for DataTables Buttons */
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

    /* Custom Color Override for View Button */
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
    
    div.dataTables_wrapper div.dataTables_filter input { 
        border: 1.5px solid #d1d5db !important; 
        border-radius: 6px !important;
        padding: 0.35rem 0.75rem !important; 
        font-weight: 600 !important; 
    }
    div.dataTables_wrapper div.dataTables_filter input:focus {
        border-color: #1d2a52 !important;
        box-shadow: 0 0 0 0.2rem rgba(29, 42, 82, 0.2) !important;
        outline: none;
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
    if ($('#recruitmentDashboardTable').length) {
        $('#recruitmentDashboardTable').DataTable({
            dom: "<'row mb-3'<'col-12 col-md-6'B><'col-12 col-md-6 text-end'f>>" +
                 "<'row'<'col-12'tr>>" +
                 "<'row mt-3'<'col-12 col-md-5 small text-muted'i><'col-12 col-md-7 d-flex justify-content-end'p>>",
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1 text-success"></i> Excel',
                    className: 'btn btn-sm mx-1',
                    title: 'HRGoTo HCM - Applicants Export',
                    exportOptions: { columns: ':not(.no-export)' }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf me-1 text-danger"></i> PDF',
                    className: 'btn btn-sm mx-1',
                    title: 'HRGoTo HCM - Applicants Export',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: { columns: ':not(.no-export)' }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print me-1 text-dark"></i> Print',
                    className: 'btn btn-sm mx-1',
                    title: 'HRGoTo HCM - Applicants Export',
                    exportOptions: { columns: ':not(.no-export)' }
                }
            ],
        pageLength: 15,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search applicants..."
        },
        columnDefs: [
            { orderable: false, targets: -1 }
        ]
    });
    }
});
</script>