<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
$bankDetailsByUser = [];
foreach ($bankDetails as $bd) {
    $bankDetailsByUser[(int)$bd['user_id']] = $bd;
}
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold">Employee Bank Details</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0"><i class="fas fa-plus-circle me-2"></i>Add / Edit Bank Details</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo $appUrl; ?>/payroll/bank-details/save" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

                    <div class="col-md-4">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Select Employee --</option>
                            <?php foreach ($staff as $s): ?>
                                <option value="<?php echo $s['id']; ?>" data-bank="<?php echo isset($bankDetailsByUser[(int)$s['id']]) ? '1' : '0'; ?>"><?php echo \App\Helpers\Security::escape($s['fullname']); ?> (<?php echo \App\Helpers\Security::escape($s['staff_id_card'] ?? 'N/A'); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" id="paymentMethod">
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="direct_deposit">Direct Deposit</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>

                    <div class="col-md-12"><hr><h6 class="text-muted">Bank Account Details</h6></div>

                    <div class="col-md-4">
                        <label class="form-label">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control" id="bankName" placeholder="e.g. GC Bank, Zenith, etc.">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Branch</label>
                        <input type="text" name="branch" class="form-control" id="branch" placeholder="Branch name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Account Name</label>
                        <input type="text" name="account_name" class="form-control" id="accountName" placeholder="Account holder's name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="account_number" class="form-control" id="accountNumber" placeholder="Account number">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Account Type</label>
                        <select name="account_type" class="form-select" id="accountType">
                            <option value="savings">Savings</option>
                            <option value="current">Current</option>
                        </select>
                    </div>

                    <div class="col-12" id="mobileMoneySection" style="display:none;">
                        <hr><h6 class="text-muted">Mobile Money Details</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Provider</label>
                                <select name="mobile_money_provider" class="form-select" id="mmProvider">
                                    <option value="">-- Select --</option>
                                    <option value="MTN">MTN Mobile Money</option>
                                    <option value="Vodafone">Vodafone Cash</option>
                                    <option value="AirtelTigo">AirtelTigo Money</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mobile Money Number</label>
                                <input type="text" name="mobile_money_number" class="form-control" id="mmNumber" placeholder="e.g. 054XXXXXXX">
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Details</button>
                        <button type="button" class="btn btn-secondary" onclick="this.form.reset()"><i class="fas fa-undo me-1"></i>Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0"><i class="fas fa-list me-2"></i>Bank Details Registry</h5>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered align-middle m-0" id="bankTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Employee</th>
                                <th>Staff ID</th>
                                <th>Payment Method</th>
                                <th>Bank / Provider</th>
                                <th>Account / Number</th>
                                <th class="no-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bankDetails as $bd): ?>
                            <tr>
                                <td><?php echo \App\Helpers\Security::escape($bd['fullname']); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($staffMap[$bd['user_id']]['staff_id_card'] ?? ''); ?></td>
                                <td><span class="badge bg-<?php echo $bd['payment_method'] === 'bank_transfer' ? 'primary' : ($bd['payment_method'] === 'mobile_money' ? 'success' : ($bd['payment_method'] === 'direct_deposit' ? 'info' : 'secondary')); ?>"><?php echo str_replace('_', ' ', ucwords($bd['payment_method'])); ?></span></td>
                                <td><?php echo \App\Helpers\Security::escape($bd['bank_name'] ?: $bd['mobile_money_provider'] ?: '-'); ?></td>
                                <td><?php echo \App\Helpers\Security::escape($bd['account_number'] ?: $bd['mobile_money_number'] ?: '-'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary editBank" data-userid="<?php echo $bd['user_id']; ?>" data-bank="<?php echo \App\Helpers\Security::escape($bd['bank_name'] ?? ''); ?>" data-branch="<?php echo \App\Helpers\Security::escape($bd['branch'] ?? ''); ?>" data-accname="<?php echo \App\Helpers\Security::escape($bd['account_name'] ?? ''); ?>" data-accnum="<?php echo \App\Helpers\Security::escape($bd['account_number'] ?? ''); ?>" data-acctype="<?php echo $bd['account_type']; ?>" data-pm="<?php echo $bd['payment_method']; ?>" data-mmp="<?php echo \App\Helpers\Security::escape($bd['mobile_money_provider'] ?? ''); ?>" data-mmn="<?php echo \App\Helpers\Security::escape($bd['mobile_money_number'] ?? ''); ?>"><i class="fas fa-edit"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($('#bankTable').length) {
        $('#bankTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No bank details recorded." },
            order: [[0, 'asc']]
        });
    }

    function toggleMobileMoney() {
        if ($('#paymentMethod').val() === 'mobile_money') {
            $('#mobileMoneySection').show();
        } else {
            $('#mobileMoneySection').hide();
        }
    }
    $('#paymentMethod').on('change', toggleMobileMoney);
    toggleMobileMoney();

    $('.editBank').click(function() {
        var btn = $(this);
        $('select[name="user_id"]').val(btn.data('userid'));
        $('#paymentMethod').val(btn.data('pm'));
        toggleMobileMoney();
        $('#bankName').val(btn.data('bank'));
        $('#branch').val(btn.data('branch'));
        $('#accountName').val(btn.data('accname'));
        $('#accountNumber').val(btn.data('accnum'));
        $('#accountType').val(btn.data('acctype'));
        $('#mmProvider').val(btn.data('mmp'));
        $('#mmNumber').val(btn.data('mmn'));
        $('html, body').animate({scrollTop: 0}, 300);
    });
});
</script>
