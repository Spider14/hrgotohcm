<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
$suggestions = $_SESSION['talent_suggestions'] ?? [];
$suggestionJob = $_SESSION['talent_suggestions_job'] ?? '';
unset($_SESSION['talent_suggestions'], $_SESSION['talent_suggestions_job']);
?>
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0"><i class="fas fa-database me-2"></i>Talent Pool</h4>
        <div>
            <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#suggestModal"><i class="fas fa-robot me-1"></i>AI Suggest from Pool</button>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_ok'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_ok']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php unset($_SESSION['flash_ok'], $_SESSION['flash_error']); ?>

    <?php if (!empty($suggestions)): ?>
        <div class="card border-success shadow-sm mb-4">
            <div class="card-header bg-success text-white"><i class="fas fa-robot me-2"></i>AI Suggestions for "<?= htmlspecialchars($suggestionJob) ?>"</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Name</th><th>Qualification</th><th>Exp</th><th>Score</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suggestions as $idx => $s): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($s['highest_qualification']) ?></span></td>
                                    <td><?= (int)$s['years_experience'] ?> yrs</td>
                                    <td class="fw-bold text-primary"><?= round($s['rank_score'], 1) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white"><i class="fas fa-list me-2"></i>Pool Members (<?= count($pool) ?>)</div>
        <div class="card-body p-0">
            <?php if (!empty($pool)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Reference</th>
                                <th>Qualification</th>
                                <th>Experience</th>
                                <th>Job Applied</th>
                                <th>Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pool as $p): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($p['email']) ?></small>
                                    </td>
                                    <td><small><?= htmlspecialchars($p['reference_number']) ?></small></td>
                                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($p['highest_qualification']) ?></span></td>
                                    <td><?= (int)$p['years_experience'] ?> yrs</td>
                                    <td class="small"><?= htmlspecialchars($p['job_title']) ?></td>
                                    <td class="small text-muted"><?= date('M d, Y', strtotime($p['added_at'])) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/recruitment/view?id=<?= $p['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-eye"></i></a>
                                            <form method="post" action="/recruitment/talent-pool/remove" class="d-inline" onsubmit="return confirm('Remove from talent pool?')">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                <input type="hidden" name="pool_id" value="<?= $p['pool_id'] ?>">
                                                <button class="btn btn-outline-danger btn-sm"><i class="fas fa-times"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3 d-block"></i><p>Talent pool is empty. Add applicants from the pipeline or applicant views.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- AI Suggest Modal -->
<div class="modal fade" id="suggestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="/recruitment/talent-pool/suggest">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="modal-header bg-success text-white">
                    <h6 class="modal-title"><i class="fas fa-robot me-1"></i>AI Talent Pool Suggestion</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Job Role</label>
                        <select name="job_id" class="form-select" required>
                            <option value="">-- Choose a job --</option>
                            <?php foreach ($jobs as $j): ?>
                                <option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Number of Candidates</label>
                        <select name="limit" class="form-select">
                            <option value="5">Top 5</option>
                            <option value="10" selected>Top 10</option>
                        </select>
                    </div>
                    <p class="small text-muted">AI will analyze talent pool members against the job requirements and suggest the most qualified candidates.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-robot me-1"></i>Run AI Suggestion</button>
                </div>
            </form>
        </div>
    </div>
</div>
