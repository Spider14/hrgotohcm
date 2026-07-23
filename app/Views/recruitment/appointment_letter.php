<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
?>
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="fas fa-file-alt me-2"></i>Appointment Letter Template</h4>
        <a href="/recruitment/ranked" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>

    <?php if (!empty($_SESSION['flash_ok'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_ok']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php unset($_SESSION['flash_ok']); ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white"><i class="fas fa-envelope me-2"></i>Appointment Letter Email Template</div>
                <div class="card-body">
                    <form method="post" action="/email/templates/save">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="template_name" value="Appointment Letter">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Subject</label>
                            <input type="text" name="template_subject" class="form-control"
                                   value="<?= htmlspecialchars($template['template_subject'] ?? 'Appointment Letter - [job_title] at Bolgatanga Technical University') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Body (HTML)</label>
                            <textarea name="template_body" class="form-control" rows="18" required><?= htmlspecialchars($template['template_body'] ?? '
<h2>Congratulations, [fullname]!</h2>
<p>We are pleased to inform you that you have been selected for the position of <strong>[job_title]</strong> at Bolgatanga Technical University.</p>
<p>Please find your detailed appointment letter attached. Kindly report to the Human Resource Department within 14 days of receiving this letter.</p>
<p>We look forward to welcoming you to our team.</p>
<p>Best regards,<br><strong>Human Resource Department</strong><br>Bolgatanga Technical University</p>
                            ') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Save Template</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white"><i class="fas fa-info-circle me-2"></i>Available Placeholders</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead><tr><th>Placeholder</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td><code>[fullname]</code></td><td>Full name of the applicant</td></tr>
                            <tr><td><code>[job_title]</code></td><td>Position applied for</td></tr>
                        </tbody>
                    </table>
                    <p class="small text-muted mt-2">These placeholders will be replaced with actual values when the email is sent.</p>
                </div>
            </div>
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-secondary text-white"><i class="fas fa-sms me-2"></i>SMS Templates</div>
                <div class="card-body">
                    <p class="small text-muted">Interview SMS and Hired SMS templates are configured in:</p>
                    <a href="/sms/campaigns/configure_campaign" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-cog me-1"></i>SMS Campaign Settings</a>
                </div>
            </div>
        </div>
    </div>
</div>
