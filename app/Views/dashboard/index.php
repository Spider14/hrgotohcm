<?php 
require_once __DIR__ . '/layouts/header.php'; 
require_once __DIR__ . '/layouts/sidebar.php'; 
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
?>

<div id="content" class="w-100">
    
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3">
                <i class="fas fa-bars"></i>
            </button>
            
            <nav aria-label="breadcrumb" class="d-none d-sm-inline-block">
                <ol class="breadcrumb m-0 bg-transparent p-0 small">
                    <li class="breadcrumb-item text-muted"><i class="fas fa-home me-1"></i> HRGoTo HCM </li>
                    <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Operational Dashboard</li>
                </ol>
            </nav>

            <?php require __DIR__ . '/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4">
        
        <!-- Row 1: Stat Cards -->
        <div class="row g-4 mb-5">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 rounded-lg stat-card-hover" style="border-left: 5px solid #2b6cb0 !important;">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase font-monospace tracking-wider text-xs mb-2">Staff Strength</h6>
                            <h3 class="fw-bold m-0 text-dark"><?php echo $dashboardStats['total_staff']; ?></h3>
                        </div>
                        <div class="stat-icon-wrapper rounded bg-blue-light p-3 text-primary"><i class="fas fa-users-viewfinder fa-2x"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 rounded-lg stat-card-hover" style="border-left: 5px solid #2f855a !important;">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase font-monospace tracking-wider text-xs mb-2">Active Workforce</h6>
                            <h3 class="fw-bold m-0 text-success"><?php echo $dashboardStats['active_staff']; ?></h3>
                        </div>
                        <div class="stat-icon-wrapper rounded bg-success-light p-3 text-success"><i class="fas fa-user-check fa-2x"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 rounded-lg stat-card-hover" style="border-left: 5px solid #c53030 !important;">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase font-monospace tracking-wider text-xs mb-2">On Leave</h6>
                            <h3 class="fw-bold m-0 text-danger"><?php echo $dashboardStats['on_leave']; ?></h3>
                        </div>
                        <div class="stat-icon-wrapper rounded bg-danger-light p-3 text-danger"><i class="fas fa-plane-departure fa-2x"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 rounded-lg stat-card-hover" style="border-left: 5px solid #d69e2e !important;">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase font-monospace tracking-wider text-xs mb-2">Pending Appraisals</h6>
                            <h3 class="fw-bold m-0 text-warning"><?php echo $dashboardStats['pending_appraisals']; ?></h3>
                        </div>
                        <div class="stat-icon-wrapper rounded bg-warning-light p-3 text-warning"><i class="fas fa-file-signature fa-2x"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 rounded-lg stat-card-hover" style="border-left: 5px solid #4c51bf !important;">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase font-monospace tracking-wider text-xs mb-2">Active Jobs</h6>
                            <h3 class="fw-bold m-0 text-indigo"><?php echo $dashboardStats['active_jobs']; ?></h3>
                        </div>
                        <div class="stat-icon-wrapper rounded bg-indigo-light p-3 text-indigo"><i class="fas fa-laptop-code fa-2x"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 rounded-lg stat-card-hover" style="border-left: 5px solid #319795 !important;">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase font-monospace tracking-wider text-xs mb-2">Total Applicants</h6>
                            <h3 class="fw-bold m-0 text-teal"><?php echo $dashboardStats['total_applicants']; ?></h3>
                        </div>
                        <div class="stat-icon-wrapper rounded bg-teal-light p-3 text-teal"><i class="fas fa-briefcase fa-2x"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 rounded-lg stat-card-hover" style="border-left: 5px solid #b83280 !important;">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase font-monospace tracking-wider text-xs mb-2">Retiring in 5 Years</h6>
                            <h3 class="fw-bold m-0 text-pink"><?php echo $dashboardStats['retiring_soon']; ?></h3>
                        </div>
                        <div class="stat-icon-wrapper rounded bg-pink-light p-3 text-pink"><i class="fas fa-clock fa-2x"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 rounded-lg stat-card-hover" style="border-left: 5px solid #d69e2e !important;">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase font-monospace tracking-wider text-xs mb-2">Pending Leave Requests</h6>
                            <h3 class="fw-bold m-0 text-warning"><?php echo $dashboardStats['pending_leaves']; ?></h3>
                        </div>
                        <div class="stat-icon-wrapper rounded bg-warning-light p-3 text-warning"><i class="fas fa-clock fa-2x"></i></div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Row 2: Charts side by side -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold mb-0"><i class="fas fa-people-group me-2 text-primary"></i>Age Distribution</h6>
                        <small class="text-muted">UN classification</small>
                    </div>
                    <div class="card-body">
                        <canvas id="ageChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold mb-0"><i class="fas fa-venus-mars me-2 text-success"></i>Gender Distribution</h6>
                        <small class="text-muted">Male / Female</small>
                    </div>
                    <div class="card-body">
                        <canvas id="genderChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 3: Staff Performance full width -->
        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0"><i class="fas fa-trophy me-2 text-warning"></i>Staff Performance</h6>
                            <small class="text-muted">Current year appraisal scores (descending)</small>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="staffPerformanceTable" class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Designation</th>
                                        <th>Score</th>
                                        <th>Rating</th>
                                        <th>Period</th>
                                        <th class="no-export">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($staffPerformance)): ?>
                                        <tr><td colspan="8" class="text-center text-muted py-4 small">No appraisal data for the current year.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($staffPerformance as $i => $emp): ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo $i + 1; ?></td>
                                                <td><?php echo \App\Helpers\Security::escape($emp['fullname']); ?></td>
                                                <td><?php echo \App\Helpers\Security::escape($emp['dept_name']); ?></td>
                                                <td><?php echo \App\Helpers\Security::escape($emp['designation']); ?></td>
                                                <td>
                                                    <?php if ($emp['score'] !== null): ?>
                                                        <span class="badge bg-<?php echo (float)$emp['score'] >= 85 ? 'success' : ((float)$emp['score'] >= 70 ? 'primary' : 'warning'); ?>"><?php echo (int)$emp['score']; ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo !empty($emp['rating']) ? \App\Helpers\Security::escape($emp['rating']) : '<span class="text-muted small">—</span>'; ?></td>
                                                <td class="small text-muted"><?php echo \App\Helpers\Security::escape($emp['period_label'] ?? '—'); ?></td>
                                                <td>
                                                    <?php if ($emp['score'] !== null && $i === 0): ?>
                                                        <button onclick="printCertificate(<?php echo (int)$emp['id']; ?>, '<?php echo \App\Helpers\Security::escape($emp['fullname']); ?>', '<?php echo \App\Helpers\Security::escape($emp['dept_name']); ?>', '<?php echo \App\Helpers\Security::escape($emp['designation']); ?>', <?php echo (int)$emp['score']; ?>, '<?php echo \App\Helpers\Security::escape($emp['period_label'] ?? ''); ?>')" class="btn btn-outline-warning btn-sm" title="Print Certificate"><i class="fas fa-award"></i></button>
                                                    <?php endif; ?>
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
        </div>

    </div>
</div>

<script>
// Age distribution chart
new Chart(document.getElementById('ageChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_keys($ageGroups)); ?>,
        datasets: [{
            label: 'Staff',
            data: <?php echo json_encode(array_values($ageGroups)); ?>,
            backgroundColor: ['#2b6cb0', '#2f855a', '#d69e2e', '#c53030'],
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// Gender distribution chart
new Chart(document.getElementById('genderChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_keys($genderDist)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_values($genderDist)); ?>,
            backgroundColor: ['#2b6cb0', '#d53f8c', '#718096']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

$(document).ready(function() {
    if ($('#staffPerformanceTable').length && typeof $.fn.DataTable !== 'undefined') {
        $('#staffPerformanceTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 25,
            ordering: true,
            order: [[4, 'desc']],
            columnDefs: [
                { orderable: false, targets: [7] },
                { type: 'num', targets: [4] }
            ],
            language: { search: "Search:", emptyTable: "No appraisal data for the current year." }
        });
    }
});

function printCertificate(id, name, dept, designation, score, period) {
    const w = window.open('', '_blank', 'width=800,height=600');
    w.document.write(`
<!DOCTYPE html>
<html><head><title>Certificate of Excellence</title>
<style>
    @page { margin: 0; }
    body { margin: 0; padding: 40px; font-family: 'Georgia', serif; background: #faf6f0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
    .certificate { max-width: 800px; width: 100%; background: #fff; padding: 60px 50px; border: 12px double #c9a84c; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.15); position: relative; }
    .certificate:before { content: ''; position: absolute; top: 10px; left: 10px; right: 10px; bottom: 10px; border: 1px solid #c9a84c; pointer-events: none; }
    h1 { font-size: 2.8rem; color: #1a365d; margin: 0 0 5px; letter-spacing: 4px; text-transform: uppercase; }
    .subtitle { font-size: 1.1rem; color: #c9a84c; text-transform: uppercase; letter-spacing: 6px; margin: 0 0 30px; border-bottom: 2px solid #c9a84c; padding-bottom: 10px; display: inline-block; }
    .award { font-size: 1.3rem; color: #4a5568; margin: 20px 0; font-style: italic; }
    .emp-name { font-size: 2.2rem; color: #1a365d; font-weight: bold; margin: 15px 0; text-transform: uppercase; }
    .details { font-size: 1rem; color: #718096; margin: 10px 0; }
    .details span { color: #2d3748; font-weight: 600; }
    .score-badge { display: inline-block; background: #c9a84c; color: #fff; font-size: 1.5rem; font-weight: bold; padding: 10px 30px; border-radius: 30px; margin: 20px 0; }
    .date-line { font-size: 0.9rem; color: #a0aec0; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
    .footer { font-size: 0.85rem; color: #a0aec0; margin-top: 10px; }
    .seal { font-size: 3rem; margin-top: 10px; color: #c9a84c; }
    @media print { body { -webkit-print-color-adjust: exact; color-adjust: exact; } .certificate { box-shadow: none; } }
</style></head><body>
<div class="certificate">
    <div class="seal">&#9672;</div>
    <h1>Certificate of Excellence</h1>
    <div class="subtitle">Employee of the Month</div>
    <div class="award">Proudly Presented To</div>
    <div class="emp-name">${name}</div>
    <div class="details"><span>${dept}</span> &mdash; <span>${designation}</span></div>
    <div class="score-badge">Score: ${score}</div>
    <div class="details">Period: ${period}</div>
    <div class="date-line">Issued on ${new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })}</div>
    <div class="footer">HRGoTo HCM &mdash; Powered by Norgence Digital Solutions</div>
</div>
<script>
window.onload = function() { window.print(); };
<\/script>
</body></html>`);
    w.document.close();
}
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
