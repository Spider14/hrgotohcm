<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$csrf = \App\Helpers\Security::generateCsrfToken();
$page = $_SESSION['user_role'] ?? '';
?>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3"><i class="fas fa-bars"></i></button>
            <span class="fw-bold"><i class="fas fa-book-open me-2"></i>User Guide</span>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1400px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <p class="text-muted small mb-0">Complete setup and operations manual for HR staff</p>
            </div>
            <a href="<?php echo $appUrl; ?>/HRGoTo_HCM_Training_Guide.pdf" class="btn btn-outline-primary btn-sm" target="_blank">
                <i class="fas fa-file-pdf me-1"></i>Download PDF
            </a>
        </div>

        <div class="d-flex gap-4 align-items-start">
            <!-- ====== TOC SIDEBAR ====== -->
            <nav id="manual-toc" class="flex-shrink-0 d-none d-lg-block" style="width:260px;position:sticky;top:56px;max-height:calc(100vh - 80px);overflow-y:auto;">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-dark text-white small fw-bold py-2 px-3">
                        <i class="fas fa-list me-1"></i>Contents
                    </div>
                    <div class="list-group list-group-flush">
                        <a class="list-group-item list-group-item-action small py-2" href="#phase1"><i class="fas fa-database me-2 text-success" style="width:1.1rem;"></i>Phase 1: Foundation Data</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step1">Step 1 — Company Profile</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step2">Step 2 — Roles &amp; Permissions</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step3">Step 3 — Departments &amp; Designations</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step4">Step 4 — Ranks &amp; Salary Grades</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step5">Step 5 — Appraisal Metrics</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step6">Step 6 — Staff Onboarding</a>

                        <a class="list-group-item list-group-item-action small py-2" href="#phase2"><i class="fas fa-user me-2 text-info" style="width:1.1rem;"></i>Phase 2: Staff Self-Service</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step7">Step 7 — Portal Orientation</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step8">Step 8 — Profile &amp; Documents</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step9">Step 9 — Leave Requests</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step10">Step 10 — Promotion Requests</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step11">Step 11 — Self-Appraisal</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step12">Step 12 — Attendance Clock-In/Out</a>

                        <a class="list-group-item list-group-item-action small py-2" href="#phase3"><i class="fas fa-calculator me-2 text-warning" style="width:1.1rem;"></i>Phase 3: Payroll Processing</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step13">Step 13 — Payroll Setup</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step14">Step 14 — Create Pay Period</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step15">Step 15 — Run Payroll</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step16">Step 16 — Payslips &amp; Reports</a>

                        <a class="list-group-item list-group-item-action small py-2" href="#phase4"><i class="fas fa-users me-2 text-danger" style="width:1.1rem;"></i>Phase 4: Recruitment</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step17">Step 17 — Job Postings</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step18">Step 18 — Application Rounds</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step19">Step 19 — Manage Pipeline</a>

                        <a class="list-group-item list-group-item-action small py-2" href="#phase5"><i class="fas fa-envelope me-2 text-secondary" style="width:1.1rem;"></i>Phase 5: Communications</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step20">Step 20 — Email Campaigns</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step21">Step 21 — SMS Campaigns</a>

                        <a class="list-group-item list-group-item-action small py-2" href="#phase6"><i class="fas fa-chart-bar me-2" style="color:#6f42c1;width:1.1rem;"></i>Phase 6: Reports &amp; Maintenance</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step22">Step 22 — Key Reports</a>
                        <a class="list-group-item list-group-item-action small py-2 ps-4" href="#step23">Step 23 — Housekeeping</a>

                    </div>
                </div>
            </nav>

            <!-- ====== CONTENT ====== -->
            <div class="flex-grow-1" style="min-width:0;">

                <!-- PHASE 1 -->
                <div class="card border-0 shadow-sm mb-4" id="phase1">
                    <div class="card-header bg-success text-white fw-bold py-3">
                        <i class="fas fa-database me-2"></i>Phase 1: Foundation Data <span class="badge bg-light text-success ms-2">HR Admin</span>
                    </div>
                    <div class="card-body p-4">

                        <div class="mb-4" id="step1">
                            <h5 class="fw-bold"><span class="badge bg-success me-2">Step 1</span>Company Profile</h5>
                            <p class="mb-1">Navigate to <strong>Admin Tools → Company Profile</strong>.</p>
                            <ul>
                                <li><strong>Company Info</strong>: Name, address, email, phone, SMTP settings for email notifications</li>
                                <li><strong>Office Location</strong>: Set GPS latitude/longitude and radius (meters) for attendance geo-fencing. Optionally add an IP whitelist so staff can clock in from specific networks.</li>
                                <li>SMTP password field shows <strong>********</strong> — leave as-is to preserve the existing password; type a new value only to change it</li>
                            </ul>
                        </div>

                        <div class="mb-4" id="step2">
                            <h5 class="fw-bold"><span class="badge bg-success me-2">Step 2</span>Roles &amp; Permissions</h5>
                            <p class="mb-1">Navigate to <strong>Admin Tools → Roles &amp; Permissions</strong>.</p>
                            <ul>
                                <li>Built-in roles: <strong>Super Admin</strong>, <strong>HR Manager</strong>, <strong>Supervisor</strong>, <strong>Staff</strong></li>
                                <li>Each user has exactly one role — this controls which menu items they see and which features they can access</li>
                                <li>Super Admin and HR Manager have full access; Supervisors can review their supervisees; Staff access only their own data</li>
                                <li>You can add, rename, or edit roles using the edit button in the Role Matrix table</li>
                            </ul>
                        </div>

                        <div class="mb-4" id="step3">
                            <h5 class="fw-bold"><span class="badge bg-success me-2">Step 3</span>Departments &amp; Designations</h5>
                            <ul>
                                <li><strong>Admin Tools → Departments</strong> — Add departments (e.g. Finance, Academic Affairs, IT). Each can have a parent unit for org hierarchy.</li>
                                <li><strong>Admin Tools → Designations</strong> — Add job titles (e.g. Senior Lecturer, Accountant). Designations describe the position while ranks define salary grades.</li>
                            </ul>
                        </div>

                        <div class="mb-4" id="step4">
                            <h5 class="fw-bold"><span class="badge bg-success me-2">Step 4</span>Ranks &amp; Salary Grades</h5>
                            <p class="mb-1">Navigate to <strong>Admin Tools → Ranks</strong>.</p>
                            <ul>
                                <li>Define the promotion ladder (e.g. Lecturer → Senior Lecturer → Associate Professor → Professor)</li>
                                <li>Each rank has a <strong>base salary</strong> — this is the foundation for payroll gross pay calculation</li>
                                <li>Ranks are used in promotion workflows and payroll processing</li>
                            </ul>
                        </div>

                        <div class="mb-4" id="step5">
                            <h5 class="fw-bold"><span class="badge bg-success me-2">Step 5</span>Appraisal Metrics</h5>
                            <p class="mb-1">Navigate to <strong>Admin Tools → Appraisal Metrics</strong>.</p>
                            <ul>
                                <li>Define measurable performance criteria (e.g. Teaching Quality, Research Output, Punctuality)</li>
                                <li>Each metric has a maximum score (e.g. 1–5)</li>
                                <li><strong>Flow:</strong> Staff scores themselves → Supervisor adds assessment + remarks → Staff approves or disapproves with reason → Director/Manager finalizes</li>
                                <li>Staff see the supervisor's assessment in their appraisal page and can respond with a reason</li>
                                <li>Director/Manager finalizes staff-approved appraisals with optional HR comment</li>
                                <li>If you do not use appraisals, this step can be skipped</li>
                            </ul>
                        </div>

                        <div id="step6">
                            <h5 class="fw-bold"><span class="badge bg-success me-2">Step 6</span>Staff Onboarding</h5>
                            <p class="mb-1">Navigate to <strong>Staff Management → Manage Staff</strong> and use bulk csv onboarding or single onboarding.</p>
                            <ul>
                                <li><strong>Single user</strong>: Click "Single Onboard" — enter full name, email, staff ID, role, department, designation, rank, supervisor</li>
                                <li><strong>Bulk CSV Onboarding</strong>:
                                    <ol>
                                        <li>Download CSV template from <strong>Staff → Staff List → Download CSV Template</strong></li>
                                        <li>Fill in staff data using Excel or Google Sheets (surname, other_names, username, email, staff_id_card, department, designation, role, etc.)</li>
                                        <li>Upload CSV via <strong>Staff → Staff List → Upload CSV</strong> — system creates a batch record</li>
                                        <li>Confirm import — each row creates a <strong>user account</strong> + <strong>staff record</strong> automatically</li>
                                        <li>System generates default credentials — new users can log in and change their password</li>
                                    </ol>
                                </li>
                                <li><strong>Onboarding Wizard</strong>: Navigate to <strong>Staff → Onboarding</strong> for a multi-step form (account, personal bio, education background, work experience) with document uploads</li>
                                <li>After onboarding, each staff member has a complete <strong>profile</strong> accessible from <strong>Staff → Staff List</strong> → click staff name</li>
                                <li><strong>Profile features</strong>:
                                    <ul>
                                        <li><strong>Staff Profile</strong> — Tabbed view: Personal Bio, Academic Qualifications, Work History, Attachments, Promotions</li>
                                        <li><strong>Dossier PDF</strong> — Click "Download Dossier" to generate a comprehensive PDF merging all profile data and uploaded certificates</li>
                                        <li><strong>ID Card</strong> — Click "View ID Card" to see a dual-sided card with photo, barcode, QR code for verification; can flip, print, or download</li>
                                    </ul>
                                </li>
                                <li>Users can be toggled <strong>active/inactive</strong> using the Activate/Deactivate button</li>
                                <li>Inactive users cannot log in</li>
                            </ul>
                        </div>

                    </div>
                </div>

                <!-- PHASE 2 -->
                <div class="card border-0 shadow-sm mb-4" id="phase2">
                    <div class="card-header bg-info text-white fw-bold py-3">
                        <i class="fas fa-user me-2"></i>Phase 2: Staff Self-Service <span class="badge bg-light text-info ms-2">All Staff</span>
                    </div>
                    <div class="card-body p-4">

                        <div class="mb-4" id="step7">
                            <h5 class="fw-bold"><span class="badge bg-info me-2">Step 7</span>Staff Portal Orientation</h5>
                            <ul>
                                <li>Staff log in through the same login page as administrators</li>
                                <li>If they forget their password, click <strong>Forgot Password</strong> — reset link sent via email (requires SMTP)</li>
                                <li>After login: <i class="fas fa-bell text-muted me-1"></i>Notification bell (unread alerts), <i class="fas fa-user-circle text-muted me-1"></i>Profile menu (settings, logout)</li>
                                <li>The sidebar shows only what is relevant to the Staff role</li>
                            </ul>
                        </div>

                        <div class="mb-4" id="step8">
                            <h5 class="fw-bold"><span class="badge bg-info me-2">Step 8</span>Profile &amp; Documents</h5>
                            <ul>
                                <li><strong>Staff Portal → My Profile</strong> — view personal details, staff ID, department, designation, rank</li>
                                <li><strong>Staff Portal → Update Info</strong> — update phone, address, emergency contact, upload profile photo</li>
                                <li><strong>Staff Documents</strong> section shows uploaded documents from the dossier system</li>
                            </ul>
                        </div>

                        <div class="mb-4" id="step9">
                            <h5 class="fw-bold"><span class="badge bg-info me-2">Step 9</span>Leave Requests</h5>
                            <ul>
                                <li><strong>Staff Portal → My Requests → Apply for Leave</strong></li>
                                <li>Select leave type, start/end dates, provide a reason</li>
                                <li>Routes to the staff member's <strong>supervisor</strong> for approval</li>
                                <li>Status updates in real-time: Pending → Approved / Rejected</li>
                                <li>Supervisors see pending requests under <strong>Staff Portal → Approvals</strong></li>
                            </ul>
                        </div>

                        <div class="mb-4" id="step10">
                            <h5 class="fw-bold"><span class="badge bg-info me-2">Step 10</span>Promotion Requests</h5>
                            <ul>
                                <li><strong>Staff Portal → My Requests → Request Promotion</strong></li>
                                <li>Select current rank, requested rank, upload supporting document (PDF, JPG, PNG)</li>
                                <li>Workflow: Staff submits → Supervisor reviews/comments → HR Manager approves/rejects</li>
                                <li>Status visible in real-time; notification sent on approval</li>
                            </ul>
                        </div>

                        <div class="mb-4" id="step11">
                            <h5 class="fw-bold"><span class="badge bg-info me-2">Step 11</span>Self-Appraisal</h5>
                            <ul>
                                <li><strong>Staff Portal → My Requests → Self Appraisal</strong></li>
                                <li>Select appraisal period, score yourself on each defined metric (1–5 scale)</li>
                                <li><strong>Step 1:</strong> Staff submits self-appraisal → status becomes <em>Pending Comments</em></li>
                                <li><strong>Step 2:</strong> Supervisor reviews, adds assessment and remarks → status becomes <em>Commented</em></li>
                                <li><strong>Step 3:</strong> Staff sees the supervisor's assessment on the appraisal page, approves or disapproves with a reason → status becomes <em>Staff Approved</em> or <em>Staff Disapproved</em></li>
                                <li><strong>Step 4:</strong> Director/Manager finalizes staff-approved appraisals → status becomes <em>Completed</em></li>
                                <li>Download completed appraisal as <strong>PDF</strong> from the appraisals page at any stage</li>
                            </ul>
                        </div>

                        <div id="step12">
                            <h5 class="fw-bold"><span class="badge bg-info me-2">Step 12</span>Attendance Clock-In/Out</h5>
                            <ul>
                                <li><strong>Staff Portal → My Attendance</strong></li>
                                <li>Click <strong>Clock In</strong> — records timestamp with GPS coordinates</li>
                                <li>Must be within the office radius (configured in Company Profile), otherwise clock-in is rejected</li>
                                <li>Click <strong>Clock Out</strong> at end of shift</li>
                                <li>Attendance register and reports available to HR under <strong>Admin Tools → Attendance</strong></li>
                            </ul>
                        </div>

                    </div>
                </div>

                <!-- PHASE 3 -->
                <div class="card border-0 shadow-sm mb-4" id="phase3">
                    <div class="card-header bg-warning text-dark fw-bold py-3">
                        <i class="fas fa-calculator me-2"></i>Phase 3: Payroll Processing <span class="badge bg-light text-warning ms-2">Payroll Staff</span>
                    </div>
                    <div class="card-body p-4">

                        <div class="mb-4" id="step13">
                            <h5 class="fw-bold"><span class="badge bg-warning text-dark me-2">Step 13</span>Payroll Setup</h5>
                            <p class="mb-1">Before running payroll, complete these setup steps:</p>
                            <ul>
                                <li><strong>Payroll → Components</strong> — Define earnings (Housing Allowance, Transport Subsidy) and deductions (Loan Repayment). Each has a type (earnings or deduction)</li>
                                <li><strong>Payroll → Employee Components</strong> — Assign components to individual staff with specific amounts</li>
                                <li><strong>Payroll → Benefits</strong> — Create benefit types (percentage or fixed) and assign to employees (Risk Allowance, Research Grant)</li>
                                <li><strong>Payroll → Bank Details</strong> — Staff enter their bank account info; HR can view and export for bank transfer</li>
                            </ul>
                        </div>

                        <div class="mb-4" id="step14">
                            <h5 class="fw-bold"><span class="badge bg-warning text-dark me-2">Step 14</span>Create Pay Period</h5>
                            <ul>
                                <li><strong>Payroll → Periods</strong> → Click <strong>Create Period</strong></li>
                                <li>Enter start/end dates; system auto-generates a label (e.g. "July 2026")</li>
                                <li>Once a period is <strong>closed</strong>, it cannot be re-run — prevents accidental duplicate payroll runs</li>
                            </ul>
                        </div>

                        <div class="mb-4" id="step15">
                            <h5 class="fw-bold"><span class="badge bg-warning text-dark me-2">Step 15</span>Run Payroll</h5>
                            <ul>
                                <li><strong>Payroll → Process Payroll</strong> → select a period</li>
                                <li>Click <strong>Run Payroll</strong> for all employees, or filter by department</li>
                                <li><strong>Calculation order</strong>:
                                    <ol>
                                        <li>Gross Pay = rank base salary + assigned components + assigned benefits</li>
                                        <li>SSNIT Employee = 5.5% of gross pay</li>
                                        <li>Taxable Income = gross pay − SSNIT employee</li>
                                        <li>PAYE Tax = computed from Ghana GRA monthly tax brackets</li>
                                        <li>Loan Deductions = employee-specific assigned deduction amounts</li>
                                        <li><strong>Net Pay</strong> = gross pay − SSNIT − PAYE − loan deductions</li>
                                    </ol>
                                </li>
                                <li>Re-running the same period <strong>updates</strong> existing rows (does not duplicate)</li>
                            </ul>
                            <div class="alert alert-info small py-2 mb-0"><i class="fas fa-info-circle me-1"></i><strong>NOTE:</strong> Payroll uses Ghana-specific tax rules. SSNIT employer contribution (13%) is tracked separately.</div>
                        </div>

                        <div id="step16">
                            <h5 class="fw-bold"><span class="badge bg-warning text-dark me-2">Step 16</span>Payslips &amp; Reports</h5>
                            <ul>
                                <li><strong>Payroll → Payslips</strong> — Search by name/ID, view payslips, download <strong>PDF</strong></li>
                                <li><strong>Payroll → Reports</strong> — Summary by period/department, export to Excel/CSV</li>
                                <li><strong>Payroll → Bank Transfer Report</strong> — CSV for bank upload (name, bank, account, net pay)</li>
                                <li><strong>Payroll → Benefits → Report</strong> — CSV of benefit assignments</li>
                                <li><strong>Payroll → Analysis &amp; Forecast</strong> — Chart.js dashboard with cost trends, budget projection, attrition analysis</li>
                            </ul>
                        </div>

                    </div>
                </div>

                <!-- PHASE 4 -->
                <div class="card border-0 shadow-sm mb-4" id="phase4">
                    <div class="card-header bg-danger text-white fw-bold py-3">
                        <i class="fas fa-users me-2"></i>Phase 4: Recruitment <span class="badge bg-light text-danger ms-2">HR Manager</span>
                    </div>
                    <div class="card-body p-4">

                        <div class="mb-4" id="step17">
                            <h5 class="fw-bold"><span class="badge bg-danger me-2">Step 17</span>Job Postings</h5>
                            <ul>
                                <li><strong>Recruitment → Jobs</strong> — Create job postings: title, department, description, requirements, salary range</li>
                                <li>Jobs appear on the <strong>public portal</strong> at <strong>https://your-domain.com/apply</strong></li>
                                <li>Applicants submit applications with education and experience details</li>
                                <li>Applications flow into the recruitment pipeline for review</li>
                            </ul>
                        </div>

                        <div class="mb-4" id="step18">
                            <h5 class="fw-bold"><span class="badge bg-danger me-2">Step 18</span>Application Rounds</h5>
                            <ul>
                                <li><strong>Recruitment → Rounds</strong> — Define screening stages (Application Review → Written Test → Interview → Offer)</li>
                                <li>Each round can have a <strong>passing score</strong> threshold</li>
                                <li>Rounds are reusable across jobs; assign per job posting</li>
                            </ul>
                        </div>

                        <div id="step19">
                            <h5 class="fw-bold"><span class="badge bg-danger me-2">Step 19</span>Manage Pipeline</h5>
                            <ul>
                                <li><strong>Recruitment → Pipeline</strong> — Kanban board grouped by stage</li>
                                <li><strong>Move applicant</strong>: Click status buttons (Pending → Under Review → Shortlisted → Interviewed → Offered → Hired/Rejected)</li>
                                <li><strong>Schedule Interview</strong>: Set date, time, type, interviewer, location</li>
                                <li><strong>Send Offer</strong>: Enter salary, upload offer letter (PDF/DOC), system logs it</li>
                                <li><strong>Status History</strong>: Full audit trail with timestamps</li>
                                <li><strong>Add Note</strong>: Internal notes visible to HR team</li>
                            </ul>
                        </div>

                    </div>
                </div>

                <!-- PHASE 5 -->
                <div class="card border-0 shadow-sm mb-4" id="phase5">
                    <div class="card-header bg-secondary text-white fw-bold py-3">
                        <i class="fas fa-envelope me-2"></i>Phase 5: Communications
                    </div>
                    <div class="card-body p-4">

                        <div class="mb-4" id="step20">
                            <h5 class="fw-bold"><span class="badge bg-secondary me-2">Step 20</span>Email Campaigns</h5>
                            <ul>
                                <li><strong>Email → Send Email</strong> — Compose and send to individuals, departments, or all staff</li>
                                <li><strong>Email → Templates</strong> — Save reusable templates for common announcements</li>
                                <li>Requires SMTP configured in Company Profile</li>
                            </ul>
                        </div>

                        <div id="step21">
                            <h5 class="fw-bold"><span class="badge bg-secondary me-2">Step 21</span>SMS Campaigns</h5>
                            <ul>
                                <li><strong>SMS Dashboard</strong> — View API balance, configure campaign templates</li>
                                <li><strong>Bulk SMS</strong> — Send to targeted groups by department, designation, or custom field</li>
                                <li>Requires an SMS API provider (see developer for setup)</li>
                            </ul>
                        </div>

                    </div>
                </div>

                <!-- PHASE 6 -->
                <div class="card border-0 shadow-sm mb-4" id="phase6">
                    <div class="card-header" style="background:#6f42c1;color:#fff;font-weight:bold;padding:0.75rem 1.25rem;">
                        <i class="fas fa-chart-bar me-2"></i>Phase 6: Reports &amp; Maintenance
                    </div>
                    <div class="card-body p-4">

                        <div class="mb-4" id="step22">
                            <h5 class="fw-bold"><span class="badge" style="background:#6f42c1;color:#fff;margin-right:0.5rem;">Step 22</span>Key Reports</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered small">
                                    <thead class="table-dark"><tr><th>Report</th><th>Location</th><th>Format</th></tr></thead>
                                    <tbody>
                                        <tr><td>Staff List with ID Cards</td><td>Staff → Staff List</td><td>PDF, CSV</td></tr>
                                        <tr><td>Dossier (full profile)</td><td>Profile → Download Dossier</td><td>PDF (merged)</td></tr>
                                        <tr><td>Attendance Register</td><td>Admin → Attendance → Register</td><td>CSV, Excel</td></tr>
                                        <tr><td>Payroll Summary</td><td>Payroll → Reports</td><td>CSV, Excel</td></tr>
                                        <tr><td>Bank Transfer File</td><td>Payroll → Bank Transfer Report</td><td>CSV</td></tr>
                                        <tr><td>Benefits Report</td><td>Payroll → Benefits → Report</td><td>CSV</td></tr>
                                        <tr><td>Audit Logs</td><td>Admin Tools → Audit Logs</td><td>Table view</td></tr>
                                        <tr><td>Recruitment Report</td><td>Recruitment → Compile Report</td><td>PDF</td></tr>
                                        <tr><td>Payroll Analysis</td><td>Payroll → Analysis &amp; Forecast</td><td>Chart.js</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="step23">
                            <h5 class="fw-bold"><span class="badge" style="background:#6f42c1;color:#fff;margin-right:0.5rem;">Step 23</span>Housekeeping &amp; Backup Center</h5>
                            <ul>
                                <li><strong>Session timeout</strong>: 30 minutes idle; warning at 25 minutes with "Stay Logged In" button</li>
                                <li><strong>Password reset</strong>: Via "Forgot Password" flow (requires SMTP)</li>
                                <li><strong>Audit logs</strong>: All significant actions logged with timestamp and user identity</li>
                                <li><strong>ID Card Config</strong>: Customize layout under <strong>Admin Tools → ID Card Config</strong></li>
                                <li><strong>Backup Center</strong> — <strong>Admin Tools → Backup Center</strong>
                                    <ul>
                                        <li>Creates a full backup: database dump (mysqldump) + uploaded files (uploads/) packaged in a single ZIP file</li>
                                        <li>Click <strong>Create Backup</strong> to generate a timestamped backup</li>
                                        <li>Download backups from the list, or delete old ones</li>
                                        <li>Backups are stored in <code>storage/backups/</code> outside the web root</li>
                                    </ul>
                                </li>
                            </ul>
                        </div>

                    </div>
                </div>

                <p class="text-center text-muted small py-3">End of User Guide — Generated <?php echo date('Y-m-d'); ?></p>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#manual-toc a[href^="#"]').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});
</script>
