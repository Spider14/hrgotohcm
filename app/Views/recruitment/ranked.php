<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0"><i class="fas fa-ranking-star me-2"></i>Ranked Applicants</h4>
        <a href="/recruitment/shortlist" class="btn btn-outline-primary btn-sm"><i class="fas fa-wand-magic-sparkles me-1"></i>Back to Shortlisting</a>
    </div>

    <?php if (!empty($_SESSION['flash_ok'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_ok']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php unset($_SESSION['flash_ok'], $_SESSION['flash_error']); ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="d-flex align-items-center gap-2">
                <label class="small text-muted fw-semibold">Filter by Job:</label>
                <select name="job_id" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                    <option value="">Select a job...</option>
                    <?php foreach ($jobs as $j): ?>
                        <option value="<?= $j['id'] ?>" <?= ($j['id'] == ($filterJob ?? 0)) ? 'selected' : '' ?>><?= htmlspecialchars($j['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <?php if ($filterJob > 0 && !empty($ranked)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white"><i class="fas fa-list-ol me-2"></i>Ranked Shortlisted Candidates</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Name</th>
                                <th>Qualification</th>
                                <th>Institution</th>
                                <th>Exp (yrs)</th>
                                <th>Rank Score</th>
                                <th>Interview</th>
                                <th>Final Score</th>
                                <th style="width:200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ranked as $idx => $r): ?>
                                <tr>
                                    <td class="fw-bold text-center">
                                        <?php if ($idx === 0): ?><span class="badge bg-warning text-dark"><i class="fas fa-crown"></i></span>
                                        <?php elseif ($idx === 1): ?><span class="badge bg-secondary"><i class="fas fa-medal"></i></span>
                                        <?php elseif ($idx === 2): ?><span class="badge bg-danger"><i class="fas fa-award"></i></span>
                                        <?php else: ?><?= $idx + 1 ?><?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($r['reference_number']) ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($r['highest_qualification']) ?></span></td>
                                    <td class="small"><?= htmlspecialchars($r['institution']) ?></td>
                                    <td class="text-center"><?= (int)$r['years_experience'] ?></td>
                                    <td class="text-center">
                                        <span class="fw-bold text-primary"><?= round($r['rank_score'] ?? 0, 1) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($r['interview_score'])): ?>
                                            <span class="fw-bold text-success"><?= $r['interview_score'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold fs-5 <?= ($r['final_score'] >= 60) ? 'text-success' : (($r['final_score'] >= 40) ? 'text-warning' : 'text-danger') ?>"><?= $r['final_score'] ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/recruitment/view?id=<?= $r['id'] ?>" class="btn btn-outline-primary" title="View Profile"><i class="fas fa-eye"></i></a>
                                            <button type="button" class="btn btn-outline-info" title="Add Score" onclick="openScoreModal(<?= $r['id'] ?>)"><i class="fas fa-star"></i></button>
                                            <button type="button" class="btn btn-success" title="Hire" onclick="confirmHire(<?= $r['id'] ?>, '<?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name'], ENT_QUOTES) ?>')"><i class="fas fa-user-check"></i> Hire</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif ($filterJob > 0): ?>
        <div class="text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3 d-block"></i><p>No shortlisted applicants found for this job.</p></div>
    <?php else: ?>
        <div class="text-center text-muted py-5"><i class="fas fa-info-circle fa-3x mb-3 d-block"></i><p>Select a job to view ranked applicants.</p></div>
    <?php endif; ?>
</div>

<!-- Score Modal -->
<div class="modal fade" id="scoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="/recruitment/shortlist/score">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="interview_id" id="score_interview_id">
                <div class="modal-header bg-info text-white">
                    <h6 class="modal-title"><i class="fas fa-star me-1"></i>Add Interview Score</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Select the interview to score:</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Interview ID</label>
                        <input type="number" name="interview_id_display" id="score_interview_id_display" class="form-control" required>
                        <small class="text-muted">Find the interview ID from the pipeline or interview list.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Score (0-100)</label>
                        <input type="number" name="score" class="form-control" min="0" max="100" step="0.5" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Comment</label>
                        <textarea name="score_comment" class="form-control" rows="3" placeholder="Optional comment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white"><i class="fas fa-save me-1"></i>Save Score</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hire Confirmation Modal -->
<div class="modal fade" id="hireModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="/recruitment/hire">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="applicant_id" id="hire_applicant_id">
                <div class="modal-header bg-success text-white">
                    <h6 class="modal-title"><i class="fas fa-user-check me-1"></i>Confirm Hire</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-user-plus fa-3x text-success mb-3"></i>
                    <h5>Are you sure?</h5>
                    <p class="text-muted">You are about to hire <strong id="hire_applicant_name"></strong>.</p>
                    <p class="small text-danger"><i class="fas fa-exclamation-triangle me-1"></i>This will send an appointment letter via email & SMS and convert them to a staff member.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Yes, Hire Now</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmHire(id, name) {
    document.getElementById('hire_applicant_id').value = id;
    document.getElementById('hire_applicant_name').textContent = name;
    new bootstrap.Modal(document.getElementById('hireModal')).show();
}
function openScoreModal(appId) {
    document.getElementById('score_interview_id').value = appId;
    document.getElementById('score_interview_id_display').value = appId;
    new bootstrap.Modal(document.getElementById('scoreModal')).show();
}
</script>
