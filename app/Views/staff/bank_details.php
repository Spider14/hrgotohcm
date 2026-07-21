<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">My Bank Details</h4>
                    </div>
                </div>
            </div>

            <?php if ($flash = \App\Core\Security::getFlash('ok')): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif ($flash = \App\Core\Security::getFlash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <p class="text-muted">Update your bank or mobile money details for payroll processing.</p>
                    <form method="post" action="/staff/bank-details/save">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select" onchange="togglePaymentFields(this.value)" required>
                                    <option value="bank_transfer" <?= ($bankDetail['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                                    <option value="mobile_money" <?= ($bankDetail['payment_method'] ?? '') === 'mobile_money' ? 'selected' : '' ?>>Mobile Money</option>
                                </select>
                            </div>
                        </div>

                        <div id="bank-fields" class="row g-3 mt-2" style="<?= ($bankDetail['payment_method'] ?? 'bank_transfer') === 'mobile_money' ? 'display:none' : '' ?>">
                            <h5 class="mt-3">Bank Account Details</h5>
                            <div class="col-md-6">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" value="<?= htmlspecialchars($bankDetail['bank_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Branch</label>
                                <input type="text" name="branch" class="form-control" value="<?= htmlspecialchars($bankDetail['branch'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Number</label>
                                <input type="text" name="account_number" class="form-control" value="<?= htmlspecialchars($bankDetail['account_number'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Name</label>
                                <input type="text" name="account_name" class="form-control" value="<?= htmlspecialchars($bankDetail['account_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Type</label>
                                <select name="account_type" class="form-select">
                                    <option value="savings" <?= ($bankDetail['account_type'] ?? 'savings') === 'savings' ? 'selected' : '' ?>>Savings</option>
                                    <option value="current" <?= ($bankDetail['account_type'] ?? '') === 'current' ? 'selected' : '' ?>>Current</option>
                                </select>
                            </div>
                        </div>

                        <div id="mobile-fields" class="row g-3 mt-2" style="<?= ($bankDetail['payment_method'] ?? 'bank_transfer') !== 'mobile_money' ? 'display:none' : '' ?>">
                            <h5 class="mt-3">Mobile Money Details</h5>
                            <div class="col-md-6">
                                <label class="form-label">Mobile Money Provider</label>
                                <input type="text" name="mobile_money_provider" class="form-control" placeholder="e.g. MTN, Vodafone, AirtelTigo" value="<?= htmlspecialchars($bankDetail['mobile_money_provider'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile Money Number</label>
                                <input type="text" name="mobile_money_number" class="form-control" placeholder="e.g. 024XXXXXXX" value="<?= htmlspecialchars($bankDetail['mobile_money_number'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Bank Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePaymentFields(val) {
    document.getElementById('bank-fields').style.display = val === 'mobile_money' ? 'none' : '';
    document.getElementById('mobile-fields').style.display = val === 'mobile_money' ? '' : 'none';
}
</script>
