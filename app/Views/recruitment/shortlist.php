<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0"><i class="fas fa-wand-magic-sparkles me-2"></i>AI Shortlisting</h4>
        <a href="/recruitment/ranked" class="btn btn-outline-primary btn-sm"><i class="fas fa-ranking-star me-1"></i>View Ranked Applicants</a>
    </div>

    <?php if (!empty($_SESSION['flash_ok'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_ok']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php unset($_SESSION['flash_ok'], $_SESSION['flash_error']); ?>

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white"><i class="fas fa-robot me-2"></i>AI Auto-Shortlist</div>
                <div class="card-body">
                    <form method="post" action="/recruitment/shortlist/auto">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Job</label>
                            <select name="job_id" class="form-select" required>
                                <option value="">-- Choose a job --</option>
                                <?php foreach ($jobs as $j): ?>
                                    <option value="<?= $j['id'] ?>" <?= ($j['id'] == ($filterJob ?? 0)) ? 'selected' : '' ?>><?= htmlspecialchars($j['title']) ?> (Limit: <?= $j['shortlist_limit'] ?? 10 ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Shortlist Top N Candidates</label>
                            <select name="shortlist_limit" class="form-select">
                                <option value="2">Top 2</option>
                                <option value="5">Top 5</option>
                                <option value="10" selected>Top 10</option>
                            </select>
                            <small class="text-muted">AI scores based on qualification, experience & keyword match</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-wand-magic-sparkles me-1"></i>Run AI Shortlisting</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white"><i class="fas fa-hand-pointer me-2"></i>Manual Shortlisting</div>
                <div class="card-body">
                    <form method="get" class="mb-3">
                        <div class="input-group">
                            <select name="job_id" class="form-select">
                                <option value="">-- Filter by Job --</option>
                                <?php foreach ($jobs as $j): ?>
                                    <option value="<?= $j['id'] ?>" <?= ($j['id'] == ($filterJob ?? 0)) ? 'selected' : '' ?>><?= htmlspecialchars($j['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-filter"></i></button>
                        </div>
                    </form>

                    <?php if (!empty($applicants)): ?>
                        <form method="post" action="/recruitment/shortlist/manual">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr><th><input type="checkbox" id="selectAll"></th><th>Name</th><th>Qualification</th><th>Exp (yrs)</th><th>Job</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applicants as $a): ?>
                                            <tr>
                                                <td><input type="checkbox" name="application_ids[]" value="<?= $a['id'] ?>" class="app-check"></td>
                                                <td class="fw-semibold"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></td>
                                                <td><span class="badge bg-light text-dark"><?= htmlspecialchars($a['highest_qualification']) ?></span></td>
                                                <td><?= (int)$a['years_experience'] ?></td>
                                                <td class="small"><?= htmlspecialchars($a['job_title']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn btn-success mt-2" onclick="return confirm('Shortlist selected applicants?')"><i class="fas fa-check me-1"></i>Manually Shortlist Selected</button>
                        </form>
                    <?php elseif ($filterJob > 0): ?>
                        <div class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No pending applicants for this job.</div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4"><i class="fas fa-info-circle fa-2x mb-2 d-block"></i>Select a job to view applicants.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-info text-white"><i class="fas fa-paper-plane me-2"></i>Send Interview SMS to Shortlisted</div>
        <div class="card-body">
            <form method="post" action="/recruitment/shortlist/send-interview-sms">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Interview Date</label>
                        <input type="date" name="interview_placeholder[interview_date]" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Interview Time</label>
                        <input type="time" name="interview_placeholder[interview_time]" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Location</label>
                        <input type="text" name="interview_placeholder[interview_location]" class="form-control" placeholder="e.g. Conference Room A">
                    </div>
                </div>
                <div id="shortlistedApps" class="mb-3">
                    <small class="text-muted">Select shortlisted applicants to send interview SMS:</small>
                    <div class="mt-1" style="max-height:200px; overflow-y:auto;" id="shortlistCheckboxes">
                        <?php
                        $shortlisted = $db->query("SELECT a.id, a.first_name, a.last_name, a.phone, a.reference_number FROM applications a WHERE a.status = 'Shortlisted' ORDER BY a.last_name")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($shortlisted as $s): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="application_ids[]" value="<?= $s['id'] ?>" id="sms_<?= $s['id'] ?>">
                                <label class="form-check-label" for="sms_<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>
                                    <span class="text-muted small">(<?= htmlspecialchars($s['phone']) ?>)</span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($shortlisted)): ?>
                            <div class="text-muted small">No shortlisted applicants found.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-info text-white" onclick="return confirm('Send interview SMS to selected applicants?')"><i class="fas fa-sms me-1"></i>Send Interview SMS</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.app-check').forEach(c => c.checked = this.checked);
});
</script>
