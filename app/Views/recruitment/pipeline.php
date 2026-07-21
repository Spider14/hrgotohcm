<?php
/**
 * @var array $grouped
 * @var array $jobs
 * @var int $filterJob
 * @var array $stageLabels
 * @var array $stages
 */
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
$stageColors = [
    'pending'      => 'secondary',
    'reviewing'    => 'info',
    'shortlisted'  => 'primary',
    'interviewed'  => 'warning',
    'offered'      => 'success',
    'hired'        => 'success',
    'rejected'     => 'danger',
];
$stageIcons = [
    'pending'      => 'fa-paper-plane',
    'reviewing'    => 'fa-search',
    'shortlisted'  => 'fa-star',
    'interviewed'  => 'fa-handshake',
    'offered'      => 'fa-file-signature',
    'hired'        => 'fa-check-double',
    'rejected'     => 'fa-ban',
];
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
.pipeline-column { min-height: 400px; }
.pipeline-column .card { border-radius: 12px; transition: all 0.2s; }
.pipeline-column .card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.08); }
.pipeline-card { cursor: pointer; border-left: 4px solid transparent; }
.pipeline-card .card-text { font-size: 0.75rem; }
.column-header { position: sticky; top: 0; z-index: 10; border-radius: 12px 12px 0 0; }
.pipeline-scroll { overflow-x: auto; padding-bottom: 16px; }
.pipeline-scroll::-webkit-scrollbar { height: 6px; }
.pipeline-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.modal-tab-content { max-height: 60vh; overflow-y: auto; }
</style>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0"><i class="fas fa-chart-simple me-2"></i>Hiring Pipeline</h4>
        <form method="get" class="d-flex align-items-center gap-2">
            <label class="small text-muted fw-semibold">Filter by Job:</label>
            <select name="job_id" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="">All Openings</option>
                <?php foreach ($jobs as $j): ?>
                <option value="<?php echo (int)$j['id']; ?>" <?php echo $filterJob === (int)$j['id'] ? 'selected' : ''; ?>><?php echo \App\Helpers\Security::escape($j['title']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($filterJob): ?>
            <a href="/recruitment/pipeline" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <div class="pipeline-scroll">
        <div class="d-flex gap-3" style="min-width:1100px;">
            <?php foreach ($stages as $stage): ?>
            <?php $count = count($grouped[$stage]); ?>
            <div class="pipeline-column" style="flex:1;min-width:150px;">
                <div class="card border-0 shadow-sm">
                    <div class="card-header column-header py-2 px-3 d-flex justify-content-between align-items-center bg-<?php echo $stageColors[$stage]; ?> text-white">
                        <span class="fw-bold small"><i class="fas <?php echo $stageIcons[$stage]; ?> me-1"></i><?php echo $stageLabels[$stage]; ?></span>
                        <span class="badge bg-white text-dark rounded-pill"><?php echo $count; ?></span>
                    </div>
                    <div class="card-body p-2" style="background:#f8fafc;min-height:300px;">
                        <?php if ($count === 0): ?>
                        <p class="text-muted small text-center py-4 mb-0">No applicants</p>
                        <?php else: ?>
                            <?php foreach ($grouped[$stage] as $app): ?>
                            <div class="card pipeline-card mb-2 border" style="border-color:#e2e8f0!important;" onclick="openDetail(<?php echo (int)$app['id']; ?>)">
                                <div class="card-body p-3">
                                    <h6 class="fw-bold mb-1 small" style="font-size:0.85rem;"><?php echo \App\Helpers\Security::escape($app['first_name'] . ' ' . $app['last_name']); ?></h6>
                                    <p class="card-text text-muted mb-1"><i class="fas fa-briefcase me-1"></i><?php echo \App\Helpers\Security::escape($app['job_title']); ?></p>
                                    <p class="card-text text-muted mb-0"><i class="fas fa-hashtag me-1"></i><?php echo \App\Helpers\Security::escape($app['reference_number']); ?></p>
                                    <?php if ($app['submitted_at']): ?>
                                    <p class="card-text text-muted mt-1 mb-0"><i class="far fa-clock me-1"></i><?php echo date('d M', strtotime($app['submitted_at'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Applicant Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" data-bs-backdrop="static">
<div class="modal-dialog modal-xl modal-dialog-scrollable">
<div class="modal-content border-0 shadow">
    <div class="modal-header bg-dark text-white py-3">
        <h5 class="modal-title fw-bold"><i class="fas fa-user me-2"></i><span id="modalName"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body p-0">
        <ul class="nav nav-tabs px-3 pt-3 bg-light" id="detailTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active small fw-semibold" data-bs-toggle="tab" data-bs-target="#tabInfo" type="button"><i class="fas fa-info-circle me-1"></i>Profile</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link small fw-semibold" data-bs-toggle="tab" data-bs-target="#tabHistory" type="button"><i class="fas fa-history me-1"></i>Status History</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link small fw-semibold" data-bs-toggle="tab" data-bs-target="#tabInterviews" type="button"><i class="fas fa-handshake me-1"></i>Interviews</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link small fw-semibold" data-bs-toggle="tab" data-bs-target="#tabOffers" type="button"><i class="fas fa-file-signature me-1"></i>Offers</button>
            </li>
        </ul>
        <div class="tab-content p-3" id="detailTabContent">
            <div class="tab-pane fade show active" id="tabInfo">
                <div id="tabInfoContent" class="modal-tab-content"></div>
            </div>
            <div class="tab-pane fade" id="tabHistory">
                <div id="tabHistoryContent" class="modal-tab-content"></div>
            </div>
            <div class="tab-pane fade" id="tabInterviews">
                <div id="tabInterviewsContent" class="modal-tab-content"></div>
            </div>
            <div class="tab-pane fade" id="tabOffers">
                <div id="tabOffersContent" class="modal-tab-content"></div>
            </div>
        </div>
    </div>
    <div class="modal-footer bg-light">
        <div class="d-flex gap-2 flex-wrap w-100 justify-content-between align-items-center">
            <div class="d-flex gap-2 flex-wrap">
                <form method="post" action="/recruitment/pipeline/move" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="applicant_id" id="moveApplicantId" value="">
                    <input type="hidden" name="job_filter" value="<?php echo $filterJob; ?>">
                    <select name="status" class="form-select form-select-sm d-inline" style="width:auto;" onchange="if(this.value)this.form.submit()">
                        <option value="">Move to...</option>
                        <option value="pending">Applied</option>
                        <option value="reviewing">Under Review</option>
                        <option value="shortlisted">Shortlisted</option>
                        <option value="interviewed">Interviewed</option>
                        <option value="offered">Offered</option>
                        <option value="hired">Hired</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </form>
                <button class="btn btn-sm btn-outline-primary" onclick="showScheduleInterview()"><i class="fas fa-calendar-plus me-1"></i>Interview</button>
                <button class="btn btn-sm btn-outline-success" onclick="showSendOffer()"><i class="fas fa-file-signature me-1"></i>Offer</button>
                <button class="btn btn-sm btn-outline-secondary" onclick="showAddNote()"><i class="fas fa-sticky-note me-1"></i>Note</button>
                <a id="viewFullProfile" href="#" class="btn btn-sm btn-outline-info" target="_blank"><i class="fas fa-external-link-alt me-1"></i>Full Profile</a>
            </div>
            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div></div></div>

<!-- Schedule Interview Modal -->
<div class="modal fade" id="interviewModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content border-0 shadow">
    <div class="modal-header bg-primary text-white">
        <h6 class="modal-title fw-bold"><i class="fas fa-calendar-plus me-2"></i>Schedule Interview</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form method="post" action="/recruitment/pipeline/schedule-interview">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <input type="hidden" name="applicant_id" id="interviewApplicantId" value="">
        <input type="hidden" name="job_filter" value="<?php echo $filterJob; ?>">
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Date *</label>
                    <input type="date" name="interview_date" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Time</label>
                    <input type="time" name="interview_time" class="form-control form-control-sm">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Type</label>
                    <select name="interview_type" class="form-select form-select-sm">
                        <option value="in-person">In-Person</option>
                        <option value="phone">Phone</option>
                        <option value="video">Video Call</option>
                        <option value="panel">Panel</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Interviewer</label>
                    <input type="text" name="interviewer" class="form-control form-control-sm" placeholder="Name">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Location / Link</label>
                    <input type="text" name="location" class="form-control form-control-sm" placeholder="Room or meeting URL">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold small">Notes</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-check me-1"></i>Schedule</button>
        </div>
    </form>
</div></div></div>

<!-- Send Offer Modal -->
<div class="modal fade" id="offerModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content border-0 shadow">
    <div class="modal-header bg-success text-white">
        <h6 class="modal-title fw-bold"><i class="fas fa-file-signature me-2"></i>Send Offer</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form method="post" action="/recruitment/pipeline/send-offer" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <input type="hidden" name="applicant_id" id="offerApplicantId" value="">
        <input type="hidden" name="job_filter" value="<?php echo $filterJob; ?>">
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Offered Salary (GHS)</label>
                    <input type="number" step="0.01" name="offered_salary" class="form-control form-control-sm">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Offer Date</label>
                    <input type="date" name="offer_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold small">Offer Letter (PDF/DOC)</label>
                    <input type="file" name="offer_letter" class="form-control form-control-sm" accept=".pdf,.doc,.docx">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold small">Notes</label>
                    <textarea name="offer_notes" class="form-control form-control-sm" rows="2"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-paper-plane me-1"></i>Send Offer</button>
        </div>
    </form>
</div></div></div>

<!-- Add Note Modal -->
<div class="modal fade" id="noteModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content border-0 shadow">
    <div class="modal-header bg-secondary text-white">
        <h6 class="modal-title fw-bold"><i class="fas fa-sticky-note me-2"></i>Add Note</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form method="post" action="/recruitment/pipeline/add-note">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <input type="hidden" name="applicant_id" id="noteApplicantId" value="">
        <input type="hidden" name="job_filter" value="<?php echo $filterJob; ?>">
        <div class="modal-body">
            <textarea name="note" class="form-control" rows="4" placeholder="Enter internal note..." required></textarea>
        </div>
        <div class="modal-footer d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-dark"><i class="fas fa-save me-1"></i>Save Note</button>
        </div>
    </form>
</div></div></div>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
let currentApplicantId = 0;
function openDetail(id) {
    currentApplicantId = id;
    document.getElementById('moveApplicantId').value = id;
    document.getElementById('interviewApplicantId').value = id;
    document.getElementById('offerApplicantId').value = id;
    document.getElementById('noteApplicantId').value = id;
    document.getElementById('viewFullProfile').href = '/recruitment/view?id=' + id;
    loadDetailTabs(id);
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}
function loadDetailTabs(id) {
    fetch('/recruitment/pipeline/status-history?applicant_id=' + id)
        .then(r => r.json())
        .then(d => {
            document.getElementById('modalName').textContent = (d.app.first_name || '') + ' ' + (d.app.last_name || '');
            document.getElementById('tabInfoContent').innerHTML = buildInfoTab(d.app);
            document.getElementById('tabHistoryContent').innerHTML = buildHistoryTab(d.log);
            document.getElementById('tabInterviewsContent').innerHTML = buildInterviewsTab(d.interviews);
            document.getElementById('tabOffersContent').innerHTML = buildOffersTab(d.offers);
        });
}
function buildInfoTab(app) {
    if (!app) return '<p class="text-muted">No data</p>';
    let notes = app.hr_notes ? app.hr_notes.replace(/\n/g, '<br>') : 'None';
    return `
        <div class="row g-3">
            <div class="col-md-6"><strong>Reference:</strong> ${app.reference_number || 'N/A'}</div>
            <div class="col-md-6"><strong>Job:</strong> ${app.job_title || 'N/A'}</div>
            <div class="col-12"><strong>HR Notes:</strong><br><p class="small text-muted">${notes}</p></div>
        </div>`;
}
function buildHistoryTab(log) {
    if (!log || log.length === 0) return '<p class="text-muted">No status changes recorded yet.</p>';
    let h = '<div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Date</th><th>From</th><th>To</th><th>Note</th><th>By</th></tr></thead><tbody>';
    log.forEach(r => {
        h += `<tr><td class="small">${r.changed_at || ''}</td><td class="small">${r.old_status || '-'}</td><td class="small fw-bold">${r.new_status}</td><td class="small">${r.note || '-'}</td><td class="small">${(r.changed_first || '') + ' ' + (r.changed_last || '')}</td></tr>`;
    });
    h += '</tbody></table></div>';
    return h;
}
function buildInterviewsTab(interviews) {
    if (!interviews || interviews.length === 0) return '<p class="text-muted">No interviews scheduled.</p>';
    let h = '<div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Date</th><th>Time</th><th>Type</th><th>Interviewer</th><th>Location</th><th>Notes</th><th>Status</th></tr></thead><tbody>';
    interviews.forEach(r => {
        h += `<tr><td class="small">${r.interview_date || ''}</td><td class="small">${r.interview_time || '-'}</td><td class="small">${r.interview_type || '-'}</td><td class="small">${r.interviewer || '-'}</td><td class="small">${r.location || '-'}</td><td class="small">${r.notes || '-'}</td><td class="small">${r.status || 'scheduled'}</td></tr>`;
    });
    h += '</tbody></table></div>';
    return h;
}
function buildOffersTab(offers) {
    if (!offers || offers.length === 0) return '<p class="text-muted">No offers sent.</p>';
    let h = '<div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Date</th><th>Salary</th><th>Status</th><th>Acceptance</th><th>Notes</th></tr></thead><tbody>';
    offers.forEach(r => {
        h += `<tr><td class="small">${r.offer_date || ''}</td><td class="small">${r.offered_salary ? 'GHS ' + parseFloat(r.offered_salary).toLocaleString() : '-'}</td><td class="small">${r.status || 'draft'}</td><td class="small">${r.acceptance_date || '-'}</td><td class="small">${r.notes || '-'}</td></tr>`;
    });
    h += '</tbody></table></div>';
    return h;
}
function showScheduleInterview() { if (currentApplicantId) new bootstrap.Modal(document.getElementById('interviewModal')).show(); }
function showSendOffer() { if (currentApplicantId) new bootstrap.Modal(document.getElementById('offerModal')).show(); }
function showAddNote() { if (currentApplicantId) new bootstrap.Modal(document.getElementById('noteModal')).show(); }
</script>
