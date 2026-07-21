<?php
declare(strict_types=1);

/** @var \App\Core\Router $router */

// =========================================================================
// 0. PUBLIC JOB APPLICATION PORTAL (no auth required)
// =========================================================================
$router->add('GET',  'apply',              'ApplyController@index');
$router->add('GET',  'apply/submit',       'ApplyController@form');
$router->add('POST', 'apply/submit',       'ApplyController@submit');
$router->add('GET',  'apply/success',      'ApplyController@success');

// =========================================================================
// 1. CORE AUTHENTICATION ROUTES
// =========================================================================
$router->add('GET',  '',                            'AuthController@showLogin');
$router->add('GET',  'login',                       'AuthController@showLogin');
$router->add('POST', 'auth/login',                  'AuthController@login');
$router->add('GET',  'forgot-password',              'AuthController@showForgotPassword');
$router->add('POST', 'auth/forgot-password',         'AuthController@sendResetLink');
$router->add('GET',  'reset-password',               'AuthController@showResetPassword');
$router->add('POST', 'auth/reset-password',          'AuthController@processResetPassword');
$router->add('GET',  'logout',                      'AuthController@logout');
$router->add('GET',  'dashboard',                   'DashboardController@index');

// =========================================================================
// 2. STAFF REGISTRY & INTERACTIVE PROFILE MANAGEMENT
// =========================================================================
$router->add('GET',  'staff',                       'StaffController@index');
$router->add('GET',  'staff/portal',                'StaffServicesController@portal');
$router->add('GET',  'staff/services',              'StaffServicesController@index');
$router->add('GET',  'staff/approvals',             'StaffServicesController@approvals');
$router->add('GET',  'staff/update-info',           'StaffServicesController@updateInfoView');
$router->add('POST', 'staff/update-info',           'StaffServicesController@updateOwnProfile');
$router->add('GET',  'staff/my-leave',              'StaffServicesController@leaveView');
$router->add('POST', 'staff/my-leave',              'StaffServicesController@submitLeave');
$router->add('GET',  'staff/promotion',             'StaffServicesController@promotionView');
$router->add('POST', 'staff/promotion',             'StaffServicesController@submitPromotion');
$router->add('GET',  'staff/appraisal',             'StaffServicesController@appraisalView');
$router->add('GET',  'staff/appraisal/pdf',        'StaffServicesController@appraisalPdf');
$router->add('POST', 'staff/appraisal',             'StaffServicesController@submitAppraisal');
$router->add('POST', 'staff/appraisal/respond',     'StaffServicesController@reviewAppraisalAsStaff');
$router->add('GET',  'staff/bank-details',          'StaffServicesController@bankDetailsView');
$router->add('POST', 'staff/bank-details/save',     'StaffServicesController@saveOwnBankDetails');
$router->add('GET',  'staff/dossier',               'StaffServicesController@dossierView');
$router->add('GET',  'staff/payslips',              'PayrollController@payslips');
$router->add('POST', 'staff/services/leave',        'StaffServicesController@submitLeave');
$router->add('POST', 'staff/services/promotion',    'StaffServicesController@submitPromotion');
$router->add('POST', 'staff/services/appraisal',    'StaffServicesController@submitAppraisal');
$router->add('POST', 'staff/approvals/leave',       'StaffServicesController@reviewLeaveAsSupervisor');
$router->add('POST', 'staff/approvals/promotion',   'StaffServicesController@reviewPromotionAsSupervisor');
$router->add('POST', 'staff/approvals/appraisal',   'StaffServicesController@reviewAppraisalAsSupervisor');
$router->add('GET',  'staff/profile',               'ProfileController@view');
$router->add('POST', 'staff/profile/update',        'ProfileController@update');
$router->add('GET',  'staff/promotions',            'WorkforceController@promotions');
$router->add('POST', 'staff/promotions/create',     'WorkforceController@createPromotion');
$router->add('POST', 'staff/promotions/review',     'WorkforceController@reviewPromotion');
$router->add('GET',  'staff/appraisals',            'WorkforceController@appraisals');
$router->add('GET',  'staff/appraisals/pdf',       'WorkforceController@appraisalPdf');
$router->add('POST', 'staff/appraisals/create',     'WorkforceController@createAppraisal');
$router->add('POST', 'staff/appraisals/finalize',   'WorkforceController@finalizeAppraisal');
$router->add('GET',  'staff/leave',                 'WorkforceController@leaveIndex');
$router->add('POST', 'staff/leave/create',          'WorkforceController@createLeaveRequest');
$router->add('POST', 'staff/leave/review',          'WorkforceController@reviewLeaveRequest');
$router->add('GET',  'staff/csv-template',          'StaffController@downloadCsvTemplate');
$router->add('POST', 'staff/csv-upload',            'StaffController@uploadCsv');
$router->add('POST', 'staff/csv-process',           'StaffController@processCsvBatch');
$router->add('GET',  'staff/csv-status',            'StaffController@getCsvBatchStatus');

// =========================================================================
// 3. RECRUITMENT & CANDIDATE MANIFEST WORKFLOWS
// =========================================================================
// Multi-Step Onboarding Form Wizard
$router->add('GET',  'onboard',                     'OnboardingController@index');
$router->add('POST', 'onboard/submit',              'OnboardingController@submit');

// New Recruitment Registry Dashboard Metrics
$router->add('GET',  'recruitment',                 'RecruitmentController@index');

// View Applicant Profile and Update Workflow
$router->add('GET',  'recruitment/view',            'RecruitmentController@viewApplicant');
$router->add('POST', 'recruitment/update-status',   'RecruitmentController@updateStatus');

// Jobs CRUD and Rounds management
$router->add('GET',  'recruitment/jobs',            'RecruitmentController@jobsIndex');
$router->add('POST', 'recruitment/jobs',            'RecruitmentController@jobsIndex');
$router->add('GET',  'recruitment/rounds',          'RecruitmentController@roundsIndex');
$router->add('POST', 'recruitment/rounds',          'RecruitmentController@roundsIndex');

// Hiring Pipeline
$router->add('GET',  'recruitment/pipeline',               'RecruitmentController@pipelineIndex');
$router->add('POST', 'recruitment/pipeline/move',          'RecruitmentController@pipelineMove');
$router->add('POST', 'recruitment/pipeline/schedule-interview', 'RecruitmentController@pipelineScheduleInterview');
$router->add('POST', 'recruitment/pipeline/send-offer',    'RecruitmentController@pipelineSendOffer');
$router->add('POST', 'recruitment/pipeline/add-note',      'RecruitmentController@pipelineAddNote');
$router->add('GET',  'recruitment/pipeline/status-history', 'RecruitmentController@pipelineStatusHistory');

// Reporting & File Utilities
$router->add('GET',  'recruitment/compile-report',          'RecruitmentController@compileReport');
$router->add('GET',  'recruitment/generate-report-endpoint', 'RecruitmentController@generateReportEndpoint');
$router->add('GET',  'recruitment/download-file',           'RecruitmentController@downloadFile');

// =========================================================================
// 4. COMMUNICATIONS & ENGAGEMENT FRAMEWORK (SMS)
// =========================================================================
$router->add('GET',  'sms/dashboard',                    'SmsController@dashboard');
$router->add('GET',  'sms/campaigns/configure_campaign', 'SmsController@configureCampaign');
$router->add('GET',  'sms/campaigns/bulk',               'SmsController@bulkIndex');
$router->add('POST', 'sms/campaigns/bulk',               'SmsController@sendBulkProcess');
$router->add('POST', 'sms/campaigns/bulk/process',       'SmsController@sendBulkProcess');
$router->add('POST', 'sms/campaigns/update',             'SmsController@updateCampaignConfig');
$router->add('POST', 'sms/campaigns/add-field',          'SmsController@addCampaignField');

// =========================================================================
// 5. EMAIL COMMUNICATIONS
// =========================================================================
$router->add('GET',  'email/send',                       'EmailController@sendView');
$router->add('POST', 'email/send',                       'EmailController@processSend');
$router->add('GET',  'email/templates',                  'EmailController@templates');
$router->add('POST', 'email/templates/save',             'EmailController@saveTemplate');
$router->add('POST', 'email/templates/delete',           'EmailController@deleteTemplate');

// =========================================================================
// 6. ADMINISTRATIVE CONFIGURATION & UTILITIES (ADMIN TOOLS)
// =========================================================================
$router->add('GET',  'admin/users',                 'AdminController@usersIndex');
$router->add('POST', 'admin/users',                 'AdminController@usersStore');
$router->add('GET',  'admin/roles-permissions',     'AdminController@rolesIndex');
$router->add('POST', 'admin/roles-permissions',     'AdminController@rolesStore');
$router->add('POST', 'admin/roles/edit',            'AdminController@rolesUpdate');
$router->add('GET',  'admin/appraisal-metrics',     'AdminController@appraisalIndex');
$router->add('POST', 'admin/appraisal-metrics',     'AdminController@appraisalStore');
$router->add('GET',  'admin/departments',           'AdminController@departmentsIndex');
$router->add('POST', 'admin/departments',           'AdminController@departmentsStore');
$router->add('POST', 'admin/departments/edit',      'AdminController@departmentsUpdate');
$router->add('POST', 'admin/departments/delete',    'AdminController@departmentsDelete');
$router->add('GET',  'admin/designations',          'AdminController@designationsIndex');
$router->add('POST', 'admin/designations',          'AdminController@designationsStore');
$router->add('POST', 'admin/designations/edit',     'AdminController@designationsUpdate');
$router->add('POST', 'admin/designations/delete',   'AdminController@designationsDelete');
$router->add('POST', 'admin/users/edit',            'AdminController@usersUpdate');
$router->add('POST', 'admin/users/toggle-status',   'AdminController@usersToggleStatus');
$router->add('POST', 'admin/users/resend-password', 'AdminController@usersResendPassword');
$router->add('GET',  'admin/ranks',                 'AdminController@ranksIndex');
$router->add('POST', 'admin/ranks',                 'AdminController@ranksStore');
$router->add('POST', 'admin/ranks/edit',            'AdminController@ranksUpdate');
$router->add('POST', 'admin/ranks/delete',          'AdminController@ranksDelete');
$router->add('GET',  'admin/audit-logs',           'AdminController@auditLogs');
$router->add('GET',  'admin/backup',               'AdminController@backupIndex');
$router->add('POST', 'admin/backup/create-db',     'AdminController@backupCreateDb');
$router->add('POST', 'admin/backup/create-files',  'AdminController@backupCreateFiles');
$router->add('POST', 'admin/backup/delete',        'AdminController@backupDelete');
$router->add('GET',  'admin/backup/download',      'AdminController@backupDownload');
$router->add('GET',  'admin/backup/progress',      'AdminController@backupProgress');

// =========================================================================
// 7. STAFF DOSSIER & ID CARD GENERATOR
// =========================================================================
$router->add('GET',  'staff/dossier/pdf',            'ProfileController@dossierPdf');
$router->add('GET',  'staff/id-card',                'StaffController@idCard');
$router->add('GET',  'admin/id-card-config',         'AdminController@idCardConfig');
$router->add('POST', 'admin/id-card-config',         'AdminController@idCardConfigSave');

// =========================================================================
// 8. ATTENDANCE MODULE
// =========================================================================
$router->add('GET',  'staff/attendance',             'AttendanceController@myAttendance');
$router->add('POST', 'staff/attendance/clock',       'AttendanceController@clock');
$router->add('GET',  'admin/attendance',             'AttendanceController@index');
$router->add('GET',  'admin/attendance/register',    'AttendanceController@register');
$router->add('GET',  'admin/attendance/reports',     'AttendanceController@reports');
$router->add('GET',  'admin/attendance/settings',    'AttendanceController@settingsView');
$router->add('POST', 'admin/attendance/settings',    'AttendanceController@saveSettings');

// =========================================================================
// 9. COMPANY PROFILE
// =========================================================================
$router->add('GET',  'admin/company-profile',        'AdminController@companyProfile');
$router->add('POST', 'admin/company-profile',        'AdminController@companyProfileSave');

// =========================================================================
// 10. PAYROLL MODULE
// =========================================================================
$router->add('GET',  'payroll',                              'PayrollController@index');
$router->add('GET',  'payroll/index',                        'PayrollController@index');
$router->add('GET',  'payroll/components',                   'PayrollController@components');
$router->add('POST', 'payroll/components/save',              'PayrollController@saveComponent');
$router->add('GET',  'payroll/employee-components',          'PayrollController@employeeComponents');
$router->add('POST', 'payroll/employee-components/save',     'PayrollController@saveEmployeeComponent');
$router->add('GET',  'payroll/periods',                      'PayrollController@periods');
$router->add('POST', 'payroll/periods/create',               'PayrollController@createPeriod');
$router->add('POST', 'payroll/periods/close',                'PayrollController@closePeriod');
$router->add('GET',  'payroll/process',                      'PayrollController@processView');
$router->add('POST', 'payroll/process/run',                  'PayrollController@processRun');
$router->add('GET',  'payroll/payslips',                     'PayrollController@payslips');
$router->add('GET',  'payroll/payslips/pdf',                'PayrollController@payslipPdf');
$router->add('GET',  'payroll/reports',                      'PayrollController@reports');
$router->add('GET',  'payroll/deductions',                   'PayrollController@deductions');
$router->add('POST', 'payroll/deductions/save',              'PayrollController@saveDeduction');
$router->add('GET',  'payroll/bank-details',                 'PayrollController@bankDetails');
$router->add('POST', 'payroll/bank-details/save',            'PayrollController@saveBankDetails');
$router->add('GET',  'payroll/bank-transfer-report',         'PayrollController@bankTransferReport');
$router->add('GET',  'payroll/benefits',                     'PayrollController@benefits');
$router->add('POST', 'payroll/benefits/save-type',           'PayrollController@saveBenefitType');
$router->add('POST', 'payroll/benefits/assign',              'PayrollController@assignEmployeeBenefit');
$router->add('POST', 'payroll/benefits/remove',              'PayrollController@removeEmployeeBenefit');
$router->add('GET',  'payroll/benefits/report',              'PayrollController@benefitsReport');
$router->add('GET',  'payroll/analysis',                    'PayrollController@analysis');

// =========================================================================
// 11. SYSTEM USER GUIDE
// =========================================================================
$router->add('GET',  'manual',                       'ManualController@index');

// =========================================================================
// 12. NOTIFICATIONS
// =========================================================================
$router->add('POST', 'notifications/mark-read',      'DashboardController@markAsRead');
$router->add('POST', 'notifications/mark-all-read',  'DashboardController@markAllRead');