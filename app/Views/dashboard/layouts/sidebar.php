<?php
$userRole = $_SESSION['user_role'] ?? 'Staff';
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL']);
$routeBase = $appUrl . '/index.php?url=';
$companyConfig = \App\Core\Database::getConnection()->query("SELECT company_name FROM app_config LIMIT 1")->fetch(\PDO::FETCH_ASSOC) ?: [];
$sidebarCompanyName = $companyConfig['company_name'] ?? '';

// Extract the current request URI path to track navigation state (e.g., "/staff", "/onboard")
$routeFromQuery = trim((string)($_GET['url'] ?? ''), '/');
if ($routeFromQuery !== '') {
    $currentUri = '/' . $routeFromQuery;
} else {
    $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
}
$appPath = parse_url($_ENV['APP_URL'] ?? '', PHP_URL_PATH) ?: '';
if ($appPath !== '' && strpos($currentUri, $appPath) === 0) {
    $currentUri = substr($currentUri, strlen($appPath));
}
$currentUri = rtrim($currentUri, '/');
if ($currentUri === '') {
    $currentUri = '/';
}

// Active state tracking for each collapsible section
$isStaffManagementActive = in_array($currentUri, ['/staff', '/staff/profile', '/staff/edit', '/staff/promotions', '/staff/appraisals', '/staff/leave', '/onboard', '/admin/attendance', '/admin/attendance/register'], true);
$isSelfServiceActive = in_array($currentUri, ['/staff/services', '/staff/portal', '/staff/approvals', '/staff/update-info', '/staff/my-leave', '/staff/promotion', '/staff/appraisal', '/staff/promotion/apply', '/staff/leave/apply', '/staff/appraisal/view', '/staff/attendance', '/staff/bank-details', '/staff/payslips', '/staff/dossier'], true);
$isRecruitmentActive = in_array($currentUri, ['/recruitment', '/recruitment/rounds', '/recruitment/jobs', '/recruitment/pipeline'], true);
$isPayrollActive = in_array($currentUri, ['/payroll', '/payroll/index', '/payroll/components', '/payroll/employee-components', '/payroll/periods', '/payroll/process', '/payroll/payslips', '/payroll/deductions', '/payroll/bank-details', '/payroll/benefits'], true);
$isSmsActive = in_array($currentUri, ['/sms/dashboard', '/sms/campaigns/bulk', '/sms/campaigns', '/sms/campaigns/configure_campaign'], true);
$isEmailActive = in_array($currentUri, ['/email/send', '/email/templates'], true);
$isReportsActive = in_array($currentUri, ['/payroll/reports', '/payroll/bank-transfer-report', '/payroll/benefits/report', '/payroll/analysis', '/admin/attendance/reports'], true);
$isAdminToolsActive = in_array($currentUri, ['/admin/users', '/admin/roles-permissions', '/admin/appraisal-metrics', '/admin/departments', '/admin/designations', '/admin/ranks', '/admin/id-card-config', '/admin/backup'], true);
$isSystemSettingsActive = in_array($currentUri, ['/admin/company-profile', '/admin/attendance/settings', '/admin/audit-logs'], true);
?>
<nav id="sidebar" class="bg-dark text-white d-flex flex-column" style="overflow-y:auto;overflow-x:hidden;">
    <div class="sidebar-header p-3 text-center border-bottom border-secondary flex-shrink-0">
        <h5 class="fw-bold tracking-wide mb-1" style="font-size:1.05rem;">HRGoTo HCM</h5>
        <?php if (!empty($sidebarCompanyName)): ?>
            <small style="font-size:0.65rem;font-weight:600;color:#fff;letter-spacing:0.08em;text-transform:uppercase;line-height:1.1;"><?php echo \App\Helpers\Security::escape($sidebarCompanyName); ?></small>
        <?php endif; ?>
    </div>
    
    <ul class="list-unstyled components p-3 m-0 flex-grow-1">
        <li class="nav-item mb-1">
            <a href="<?php echo $routeBase . ($userRole === 'Staff' ? 'staff/portal' : 'dashboard'); ?>" class="nav-link py-2 px-3 rounded d-flex align-items-center gap-3 <?php echo in_array($currentUri, ['/dashboard', '/', '/staff/portal', '/staff/services']) ? 'bg-primary text-white' : ''; ?>">
                <i class="fas fa-chart-pie text-center" style="width:20px;"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- ====== 1. CORE OPERATIONS ====== -->
        <li class="text-uppercase px-3 pt-4 pb-2" style="font-size:0.75rem;font-weight:700;color:#fff;letter-spacing:0.08em;">Core Operations</li>

        <?php if (in_array($userRole, ['Super Admin', 'HR Manager', 'Supervisor'], true)): ?>
        <li class="nav-item mb-1">
            <a href="#" data-bs-target="#staffManagementSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo $isStaffManagementActive ? 'true' : 'false'; ?>" class="nav-link dropdown-toggle py-2 px-3 rounded d-flex align-items-center justify-content-between <?php echo $isStaffManagementActive ? 'text-white fw-bold' : ''; ?>">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-users text-center" style="width:20px;"></i>
                    <span>Staff Management</span>
                </div>
            </a>
            <ul class="collapse list-unstyled ps-4 bg-black bg-opacity-25 rounded <?php echo $isStaffManagementActive ? 'show' : ''; ?>" id="staffManagementSubmenu">
                <li>
                    <a href="<?php echo $routeBase; ?>staff" class="nav-link py-2 <?php echo ($currentUri === '/staff') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-list-ul me-2"></i>Manage Staff
                    </a>
                </li>
                <?php if (in_array($userRole, ['Super Admin', 'HR Manager'], true)): ?>
                    <li>
                        <a href="<?php echo $routeBase; ?>onboard" class="nav-link py-2 <?php echo ($currentUri === '/onboard') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                            <i class="fas fa-user-plus me-2"></i>Single Onboarding
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($userRole !== 'Staff'): ?>
                    <li>
                        <a href="<?php echo $routeBase; ?>staff/promotions" class="nav-link py-2 <?php echo ($currentUri === '/staff/promotions') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                            <i class="fas fa-arrow-trend-up me-2"></i>Manage Promotions
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $routeBase; ?>staff/appraisals" class="nav-link py-2 <?php echo ($currentUri === '/staff/appraisals') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                            <i class="fas fa-star-half-stroke me-2"></i>Manage Appraisal
                        </a>
                    </li>
                <?php endif; ?>
                <li>
                    <a href="<?php echo $routeBase; ?>staff/leave" class="nav-link py-2 <?php echo ($currentUri === '/staff/leave') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-calendar-minus me-2"></i>Manage Leave Records
                    </a>
                </li>
                <?php if ($userRole !== 'Staff'): ?>
                <li>
                    <a href="<?php echo $routeBase; ?>admin/attendance" class="nav-link py-2 <?php echo in_array($currentUri, ['/admin/attendance', '/admin/attendance/register']) ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-clock me-2"></i>Manage Attendance Records
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <?php if (in_array($userRole, ['Super Admin', 'HR Manager', 'Supervisor', 'Staff'], true)): ?>
        <li class="nav-item mb-1">
            <a href="#" data-bs-target="#selfServiceSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo $isSelfServiceActive ? 'true' : 'false'; ?>" class="nav-link dropdown-toggle py-2 px-3 rounded d-flex align-items-center justify-content-between <?php echo $isSelfServiceActive ? 'text-white fw-bold' : ''; ?>">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-id-card text-center" style="width:20px;"></i>
                    <span>Self Service</span>
                </div>
            </a>
            <ul class="collapse list-unstyled ps-4 bg-black bg-opacity-25 rounded <?php echo $isSelfServiceActive ? 'show' : ''; ?>" id="selfServiceSubmenu">
                <li>
                    <a href="<?php echo $routeBase; ?>staff/attendance" class="nav-link py-2 <?php echo ($currentUri === '/staff/attendance') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-clock me-2"></i>Clock In/Out
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>staff/update-info" class="nav-link py-2 <?php echo ($currentUri === '/staff/update-info') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-user-pen me-2"></i>Update Info
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>staff/my-leave" class="nav-link py-2 <?php echo in_array($currentUri, ['/staff/my-leave', '/staff/leave/apply']) ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-calendar-plus me-2"></i>Apply for Leave
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>staff/promotion" class="nav-link py-2 <?php echo in_array($currentUri, ['/staff/promotion', '/staff/promotion/apply']) ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-arrow-up-right-dots me-2"></i>Apply for Promotion
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>staff/appraisal" class="nav-link py-2 <?php echo in_array($currentUri, ['/staff/appraisal', '/staff/appraisal/view']) ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-clipboard-check me-2"></i>Appraisal
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>staff/bank-details" class="nav-link py-2 <?php echo ($currentUri === '/staff/bank-details') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-building-columns me-2"></i>Bank Details
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>staff/payslips" class="nav-link py-2 <?php echo ($currentUri === '/staff/payslips') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-wallet me-2"></i>Payslips
                    </a>
                </li>
                <?php if ($userRole === 'Supervisor'): ?>
                    <li>
                        <a href="<?php echo $routeBase; ?>staff/approvals" class="nav-link py-2 <?php echo ($currentUri === '/staff/approvals') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                            <i class="fas fa-list-check me-2"></i>Supervisor Actions
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <?php if (in_array($userRole, ['Super Admin', 'HR Manager', 'Recruiter'], true)): ?>
        <li class="nav-item mb-1">
            <a href="#" data-bs-target="#recruitmentSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo $isRecruitmentActive ? 'true' : 'false'; ?>" class="nav-link dropdown-toggle py-2 px-3 rounded d-flex align-items-center justify-content-between <?php echo $isRecruitmentActive ? 'text-white fw-bold' : ''; ?>">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-briefcase text-center" style="width:20px;"></i>
                    <span>Recruitment</span>
                </div>
            </a>
            <ul class="collapse list-unstyled ps-4 bg-black bg-opacity-25 rounded <?php echo $isRecruitmentActive ? 'show' : ''; ?>" id="recruitmentSubmenu">
                <li>
                    <a href="<?php echo $routeBase; ?>recruitment" class="nav-link py-2 <?php echo ($currentUri === '/recruitment') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-id-card-clip me-2"></i>Applicants
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>recruitment/jobs" class="nav-link py-2 <?php echo ($currentUri === '/recruitment/jobs') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-briefcase me-2"></i>Job Postings
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>recruitment/pipeline" class="nav-link py-2 <?php echo ($currentUri === '/recruitment/pipeline') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-chart-simple me-2"></i>Hiring Pipeline
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>recruitment/rounds" class="nav-link py-2 <?php echo ($currentUri === '/recruitment/rounds') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-rotate me-2"></i>Recruitment Rounds
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <!-- ====== 2. PAYROLL MANAGEMENT ====== -->
        <?php if (in_array($userRole, ['Super Admin', 'HR Manager'], true)): ?>
        <li class="text-uppercase px-3 pt-4 pb-2" style="font-size:0.75rem;font-weight:700;color:#fff;letter-spacing:0.08em;">Payroll Management</li>

        <li class="nav-item mb-1">
            <a href="#" data-bs-target="#payrollSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo $isPayrollActive ? 'true' : 'false'; ?>" class="nav-link dropdown-toggle py-2 px-3 rounded d-flex align-items-center justify-content-between <?php echo $isPayrollActive ? 'text-white fw-bold' : ''; ?>">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-coins text-center" style="width:20px;"></i>
                    <span>Payroll</span>
                </div>
            </a>
            <ul class="collapse list-unstyled ps-4 bg-black bg-opacity-25 rounded <?php echo $isPayrollActive ? 'show' : ''; ?>" id="payrollSubmenu">
                <li>
                    <a href="<?php echo $routeBase; ?>payroll" class="nav-link py-2 <?php echo in_array($currentUri, ['/payroll', '/payroll/index']) ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-chart-pie me-2"></i>Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>payroll/components" class="nav-link py-2 <?php echo ($currentUri === '/payroll/components') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-gear me-2"></i>Components
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>payroll/employee-components" class="nav-link py-2 <?php echo ($currentUri === '/payroll/employee-components') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-user-gear me-2"></i>Employee Components
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>payroll/periods" class="nav-link py-2 <?php echo ($currentUri === '/payroll/periods') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-calendar me-2"></i>Payroll Periods
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>payroll/process" class="nav-link py-2 <?php echo ($currentUri === '/payroll/process') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-play me-2"></i>Process
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>payroll/payslips" class="nav-link py-2 <?php echo ($currentUri === '/payroll/payslips') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-file-invoice me-2"></i>Payslips
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>payroll/deductions" class="nav-link py-2 <?php echo ($currentUri === '/payroll/deductions') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-hand-holding-dollar me-2"></i>Deductions
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>payroll/bank-details" class="nav-link py-2 <?php echo ($currentUri === '/payroll/bank-details') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-university me-2"></i>Bank Details
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>payroll/benefits" class="nav-link py-2 <?php echo ($currentUri === '/payroll/benefits') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-gift me-2"></i>Benefits & Incentives
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <!-- ====== 3. COMMUNICATIONS ====== -->
        <?php if (in_array($userRole, ['Super Admin', 'HR Manager'], true)): ?>
        <li class="text-uppercase px-3 pt-4 pb-2" style="font-size:0.75rem;font-weight:700;color:#fff;letter-spacing:0.08em;">Communications</li>

        <li class="nav-item mb-1">
            <a href="#" data-bs-target="#smsSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo $isSmsActive ? 'true' : 'false'; ?>" class="nav-link dropdown-toggle py-2 px-3 rounded d-flex align-items-center justify-content-between <?php echo $isSmsActive ? 'text-white fw-bold' : ''; ?>">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-paper-plane text-center" style="width:20px;"></i>
                    <span>SMS</span>
                </div>
            </a>
            <ul class="collapse list-unstyled ps-4 bg-black bg-opacity-25 rounded <?php echo $isSmsActive ? 'show' : ''; ?>" id="smsSubmenu">
                <li>
                    <a href="<?php echo $routeBase; ?>sms/dashboard" class="nav-link py-2 <?php echo ($currentUri === '/sms/dashboard') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-chart-bar me-2"></i>SMS Analytics
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>sms/campaigns/bulk" class="nav-link py-2 <?php echo ($currentUri === '/sms/campaigns/bulk') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-comment-sms me-2"></i>Bulk SMS Portal
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>sms/campaigns/configure_campaign" class="nav-link py-2 <?php echo ($currentUri === '/sms/campaigns/configure_campaign') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-sliders-h me-2"></i>Template Settings
                    </a>
                </li>
            </ul>
        </li>

        <li class="nav-item mb-1">
            <a href="#" data-bs-target="#emailSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo $isEmailActive ? 'true' : 'false'; ?>" class="nav-link dropdown-toggle py-2 px-3 rounded d-flex align-items-center justify-content-between <?php echo $isEmailActive ? 'text-white fw-bold' : ''; ?>">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-envelope text-center" style="width:20px;"></i>
                    <span>Email</span>
                </div>
            </a>
            <ul class="collapse list-unstyled ps-4 bg-black bg-opacity-25 rounded <?php echo $isEmailActive ? 'show' : ''; ?>" id="emailSubmenu">
                <li>
                    <a href="<?php echo $routeBase; ?>email/send" class="nav-link py-2 <?php echo ($currentUri === '/email/send') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-paper-plane me-2"></i>Send Email
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>email/templates" class="nav-link py-2 <?php echo ($currentUri === '/email/templates') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-file-lines me-2"></i>Email Templates
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <!-- ====== 4. REPORTS ====== -->
        <?php if (in_array($userRole, ['Super Admin', 'HR Manager'], true)): ?>
        <li class="text-uppercase px-3 pt-4 pb-2" style="font-size:0.75rem;font-weight:700;color:#fff;letter-spacing:0.08em;">Reports</li>

        <li class="nav-item mb-1">
            <a href="<?php echo $routeBase; ?>payroll/reports" class="nav-link py-2 px-3 rounded d-flex align-items-center gap-3 <?php echo ($currentUri === '/payroll/reports') ? 'bg-primary text-white' : ''; ?>">
                <i class="fas fa-file-invoice-dollar text-center" style="width:20px;"></i>
                <span>Payroll Reports</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="<?php echo $routeBase; ?>payroll/bank-transfer-report" class="nav-link py-2 px-3 rounded d-flex align-items-center gap-3 <?php echo ($currentUri === '/payroll/bank-transfer-report') ? 'bg-primary text-white' : ''; ?>">
                <i class="fas fa-file-export text-center" style="width:20px;"></i>
                <span>Bank Transfer Report</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="<?php echo $routeBase; ?>payroll/benefits/report" class="nav-link py-2 px-3 rounded d-flex align-items-center gap-3 <?php echo ($currentUri === '/payroll/benefits/report') ? 'bg-primary text-white' : ''; ?>">
                <i class="fas fa-gift text-center" style="width:20px;"></i>
                <span>Benefits Report</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="<?php echo $routeBase; ?>payroll/analysis" class="nav-link py-2 px-3 rounded d-flex align-items-center gap-3 <?php echo ($currentUri === '/payroll/analysis') ? 'bg-primary text-white' : ''; ?>">
                <i class="fas fa-chart-pie text-center" style="width:20px;"></i>
                <span>Analysis & Forecast</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="<?php echo $routeBase; ?>admin/attendance/reports" class="nav-link py-2 px-3 rounded d-flex align-items-center gap-3 <?php echo ($currentUri === '/admin/attendance/reports') ? 'bg-primary text-white' : ''; ?>">
                <i class="fas fa-clock text-center" style="width:20px;"></i>
                <span>Attendance Reports</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- ====== 5. ADMIN TOOLS ====== -->
        <?php if (in_array($userRole, ['Super Admin', 'HR Manager'], true)): ?>
        <li class="text-uppercase px-3 pt-4 pb-2" style="font-size:0.75rem;font-weight:700;color:#fff;letter-spacing:0.08em;">Admin Tools</li>

        <li class="nav-item mb-1">
            <a href="#" data-bs-target="#adminToolsSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo $isAdminToolsActive ? 'true' : 'false'; ?>" class="nav-link dropdown-toggle py-2 px-3 rounded d-flex align-items-center justify-content-between <?php echo $isAdminToolsActive ? 'text-white fw-bold' : ''; ?>">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-sliders text-center" style="width:20px;"></i>
                    <span>Admin Tools</span>
                </div>
            </a>
            <ul class="collapse list-unstyled ps-4 bg-black bg-opacity-25 rounded <?php echo $isAdminToolsActive ? 'show' : ''; ?>" id="adminToolsSubmenu">
                <li>
                    <a href="<?php echo $routeBase; ?>admin/users" class="nav-link py-2 <?php echo ($currentUri === '/admin/users') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-user-gear me-2"></i>Manage Users
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>admin/roles-permissions" class="nav-link py-2 <?php echo ($currentUri === '/admin/roles-permissions') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-shield-halved me-2"></i>Roles & Permissions
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>admin/appraisal-metrics" class="nav-link py-2 <?php echo ($currentUri === '/admin/appraisal-metrics') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-gauge-high me-2"></i>Appraisal Metrics
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>admin/departments" class="nav-link py-2 <?php echo ($currentUri === '/admin/departments') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-building-columns me-2"></i>Departments
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>admin/ranks" class="nav-link py-2 <?php echo ($currentUri === '/admin/ranks') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-layer-group me-2"></i>Ranks
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>admin/designations" class="nav-link py-2 <?php echo ($currentUri === '/admin/designations') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-briefcase me-2"></i>Designations
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>admin/id-card-config" class="nav-link py-2 <?php echo ($currentUri === '/admin/id-card-config') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-id-card me-2"></i>ID Card Settings
                    </a>
                </li>
                <li>
                    <a href="<?php echo $routeBase; ?>admin/backup" class="nav-link py-2 <?php echo ($currentUri === '/admin/backup') ? 'text-info fw-bold' : 'text-white-50'; ?> text-decoration-none d-block small">
                        <i class="fas fa-hard-drive me-2"></i>Backup Center
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <!-- ====== 6. SYSTEM SETTINGS ====== -->
        <?php if (in_array($userRole, ['Super Admin', 'HR Manager'], true)): ?>
        <li class="text-uppercase px-3 pt-4 pb-2" style="font-size:0.75rem;font-weight:700;color:#fff;letter-spacing:0.08em;">System Settings</li>

        <li class="nav-item mb-1">
            <a href="<?php echo $routeBase; ?>admin/company-profile" class="nav-link py-2 px-3 rounded d-flex align-items-center gap-3 <?php echo ($currentUri === '/admin/company-profile') ? 'bg-primary text-white' : ''; ?>">
                <i class="fas fa-building text-center" style="width:20px;"></i>
                <span>Company Profile</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="<?php echo $routeBase; ?>admin/attendance/settings" class="nav-link py-2 px-3 rounded d-flex align-items-center gap-3 <?php echo ($currentUri === '/admin/attendance/settings') ? 'bg-primary text-white' : ''; ?>">
                <i class="fas fa-sliders-h text-center" style="width:20px;"></i>
                <span>Attendance Settings</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="<?php echo $routeBase; ?>admin/audit-logs" class="nav-link py-2 px-3 rounded d-flex align-items-center gap-3 <?php echo ($currentUri === '/admin/audit-logs') ? 'bg-primary text-white' : ''; ?>">
                <i class="fas fa-history text-center" style="width:20px;"></i>
                <span>Audit Trail</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="<?php echo $routeBase; ?>manual" class="nav-link py-2 px-3 rounded d-flex align-items-center gap-3 <?php echo ($currentUri === '/manual') ? 'bg-primary text-white' : ''; ?>">
                <i class="fas fa-book-open text-center" style="width:20px;"></i>
                <span>User Guide</span>
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-item mt-4 border-top border-secondary pt-3">
            <a href="<?php echo $routeBase; ?>logout" class="nav-link py-2 px-3 rounded d-flex align-items-center gap-3 text-danger">
                <i class="fas fa-power-off text-center" style="width:20px;"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
    <div class="sidebar-footer p-2 text-center border-top border-secondary flex-shrink-0" style="font-size:0.7rem;">
        <span style="color:#94a3b8;">HRGoTo HCM <strong style="color:#cbd5e0;"><?php echo APP_VERSION; ?></strong></span>
    </div>
</nav>
