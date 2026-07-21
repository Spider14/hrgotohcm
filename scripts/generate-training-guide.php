<?php
declare(strict_types=1);

/**
 * Generates a PDF training guide for HRGoTo HCM.
 * Run: php scripts/generate-training-guide.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

$pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetCreator('HRGoTo HCM');
$pdf->SetAuthor('HRGoTo HCM');
$pdf->SetTitle('HRGoTo HCM — HR Staff Training Guide');
$pdf->SetSubject('Training Manual');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->setFooterFont(['helvetica', '', 8]);
$pdf->SetFooterMargin(15);

$pdf->SetMargins(18, 18, 18);
$pdf->SetAutoPageBreak(true, 20);

// --- Style ---
$styleBold = ['helvetica', 'B', 11];
$styleNormal = ['helvetica', '', 10];
$styleSmall = ['helvetica', '', 8];
$styleTitle = ['helvetica', 'B', 20];
$styleH1 = ['helvetica', 'B', 15];
$styleH2 = ['helvetica', 'B', 12];
$styleH3 = ['helvetica', 'B', 11];
$blue = [22, 44, 91];
$lightBlue = [240, 244, 252];
$dark = [51, 51, 51];
$gray = [140, 150, 160];

function addTitlePage($pdf, $styleTitle, $styleNormal, $styleSmall, $blue, $gray): void {
    global $dark;
    $pdf->AddPage();
    $pdf->SetMargins(18, 18, 18);
    // top accent bar
    $pdf->SetFillColor($blue[0], $blue[1], $blue[2]);
    $pdf->Rect(0, 0, 210, 6, 'F');
    $pdf->Ln(40);

    $pdf->SetFont($styleTitle[0], $styleTitle[1], $styleTitle[2]);
    $pdf->SetTextColor($blue[0], $blue[1], $blue[2]);
    $pdf->Cell(0, 14, 'HRGoTo HCM', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 28);
    $pdf->Cell(0, 12, 'HR Staff Training Guide', 0, 1, 'C');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetTextColor(100);
    $pdf->Cell(0, 8, 'Practical Setup & Operations Manual', 0, 1, 'C');
    $pdf->Ln(20);

    // info box
    $pdf->SetFillColor(248, 249, 250);
    $pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
    $info = '<table cellpadding="6" cellspacing="0"><tr><td width="30%"><b>Version</b></td><td>' . (defined('APP_VERSION') ? APP_VERSION : '1.5.0') . '</td></tr>'
        . '<tr><td><b>Generated</b></td><td>' . date('Y-m-d H:i') . '</td></tr>'
        . '<tr><td><b>Delivery</b></td><td>3 training sessions</td></tr>'
        . '<tr><td><b>Audience</b></td><td>HR Managers, Payroll Staff, Supervisors, All Staff</td></tr></table>';
    $pdf->writeHTMLCell(0, 0, 30, '', $info, 1, 1);

    $pdf->Ln(40);
    $pdf->SetFont($styleSmall[0], $styleSmall[1], $styleSmall[2]);
    $pdf->SetTextColor($gray[0], $gray[1], $gray[2]);
    $pdf->Cell(0, 6, 'Confidential — For internal training purposes only', 0, 1, 'C');
}

function addSectionPage($pdf, $styleH1, $styleNormal, $blue, $num, $title): void {
    global $dark;
    $pdf->AddPage();
    $pdf->SetMargins(18, 18, 18);
    $pdf->SetFillColor($blue[0], $blue[1], $blue[2]);
    $pdf->Rect(0, 0, 210, 4, 'F');
    $pdf->Ln(10);
    $pdf->SetFont($styleH1[0], $styleH1[1], $styleH1[2]);
    $pdf->SetTextColor($blue[0], $blue[1], $blue[2]);
    $pdf->Cell(0, 10, "Phase $num: $title", 0, 1, 'L');
    $pdf->SetDrawColor($blue[0], $blue[1], $blue[2]);
    $pdf->Line(18, $pdf->GetY(), 192, $pdf->GetY());
    $pdf->Ln(6);
    $pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
}

function addStep($pdf, $styleH2, $styleNormal, $styleSmall, $num, $title, $body, $highlight = ''): void {
    global $dark;
    $pdf->SetFont($styleH2[0], $styleH2[1], $styleH2[2]);
    $pdf->SetTextColor(22, 44, 91);
    $pdf->Cell(0, 8, "Step $num \u{2014} $title", 0, 1, 'L');
    if ($highlight) {
        $pdf->SetFillColor(255, 243, 205);
        $pdf->SetFont($styleSmall[0], $styleSmall[1], $styleSmall[2]);
        $pdf->SetTextColor(133, 100, 4);
        $pdf->MultiCell(0, 5, $highlight, 0, 'L', true);
        $pdf->Ln(2);
    }
    $pdf->SetFont($styleNormal[0], $styleNormal[1], $styleNormal[2]);
    $pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
    $pdf->writeHTML('<div style="margin-left:8px;">' . $body . '</div>', true, false, true, false, '');
    $pdf->Ln(3);
}

function addTable($pdf, $headers, $rows): void {
    $html = '<table border="1" cellpadding="4" cellspacing="0" style="font-size:9pt;">'
        . '<thead><tr style="background-color:#162C5B;color:#fff;">';
    foreach ($headers as $h) {
        $html .= '<td><b>' . $h . '</b></td>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . $cell . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(3);
}

// ========================================================================
// BUILD DOCUMENT
// ========================================================================
addTitlePage($pdf, $styleTitle, $styleNormal, $styleSmall, $blue, $gray);

// --- Table of Contents ---
$pdf->AddPage();
$pdf->SetFont($styleH1[0], $styleH1[1], $styleH1[2]);
$pdf->SetTextColor($blue[0], $blue[1], $blue[2]);
$pdf->Cell(0, 10, 'Table of Contents', 0, 1, 'L');
$pdf->SetDrawColor($blue[0], $blue[1], $blue[2]);
$pdf->Line(18, $pdf->GetY(), 192, $pdf->GetY());
$pdf->Ln(6);
$pdf->SetTextColor($dark[0], $dark[1], $dark[2]);

$tocItems = [
    ['Phase 1', 'System Setup', 'Steps 1\u20132'],
    ['Phase 2', 'Foundation Data', 'Steps 3\u20138'],
    ['Phase 3', 'Staff Self-Service', 'Steps 9\u201314'],
    ['Phase 4', 'Payroll Processing', 'Steps 15\u201318'],
    ['Phase 5', 'Recruitment', 'Steps 19\u201321'],
    ['Phase 6', 'Communications', 'Steps 22\u201323'],
    ['Phase 7', 'Reports & Maintenance', 'Steps 24\u201325'],
    ['', 'Training Flow Recommendation', '3-session plan'],
];
foreach ($tocItems as $i => $item) {
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, ($item[0] ? $item[0] . '  \u2014  ' : '') . $item[1] . '  (' . $item[2] . ')', 0, 1, 'L');
}
$pdf->Ln(10);

// ========================================================================
// PHASE 1: SYSTEM SETUP
// ========================================================================
addSectionPage($pdf, $styleH1, $styleNormal, $blue, '1', 'System Setup (Trainer / Admin)');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '1', 'Environment & Installation', '
<ul>
<li>Ensure server meets requirements: <b>PHP 8.3+</b>, MySQL/MariaDB, Apache with <b>mod_rewrite</b>, SSL certificate</li>
<li>Create a MySQL database (e.g. <i>hrmis_db</i>)</li>
<li>Copy project files to web root, edit <b>.env</b> with your DB credentials:
<br><span style="color:#555;">DB_HOST=localhost / DB_PORT=3306 / DB_DATABASE=hrmis_db / DB_USERNAME=root / DB_PASSWORD=your_password</span></li>
<li>Run <b>composer install --no-dev</b> to install TCPDF and PDFMerger dependencies</li>
<li>Set Apache document root to the <b>public/</b> folder; enable mod_rewrite</li>
<li>Verify <b>public/uploads/</b> and <b>public/uploads/offers/</b> exist and are writable</li>
<li>Upload and profile photo uploads are limited to <b>10 MB</b> (configured in public/.htaccess)</li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '2', 'Initial Login', '
<ul>
<li>Open browser to <b>https://your-domain.com/login</b></li>
<li>Default credentials: a Super Admin account must be seeded directly in the database (INSERT INTO users with role_id pointing to the "Super Admin" role)</li>
<li>After login, the <b>Dashboard</b> displays:
<ul>
<li>Notification bell (top-right) \u2014 click to view unread alerts</li>
<li>Profile menu (initials button) \u2014 Account Settings, Logout</li>
<li>Session timer \u2014 30-minute idle timeout with a warning at 25 minutes</li>
</ul>
</li>
<li>Sidebar adapts based on user role \u2014 Staff see only Staff Portal; HR sees admin tools</li>
</ul>',
'<b>TIP:</b> Use the "Forgot Password" feature (requires SMTP configured) for self-service password resets.'
);

// ========================================================================
// PHASE 2: FOUNDATION DATA
// ========================================================================
addSectionPage($pdf, $styleH1, $styleNormal, $blue, '2', 'Foundation Data (HR Admin)');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '3', 'Company Profile',
'Navigate to <b>Admin Tools \u2192 Company Profile</b>.
<ul>
<li><b>Company Info</b>: Name, address, email, phone, SMTP settings for email notifications</li>
<li><b>Office Location</b>: Set GPS latitude/longitude and radius (meters) for attendance geo-fencing. Optionally add an IP whitelist so staff can clock in from specific networks.</li>
<li>SMTP password field shows <b>********</b> \u2014 leave as-is to preserve the existing password; type a new value only to change it</li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '4', 'Roles &amp; Permissions',
'Navigate to <b>Admin Tools \u2192 Roles &amp; Permissions</b>.
<ul>
<li>Built-in roles: <b>Super Admin</b>, <b>HR Manager</b>, <b>Supervisor</b>, <b>Staff</b></li>
<li>Each user has exactly one role \u2014 this controls which menu items they see and which features they can access</li>
<li>Super Admin and HR Manager have full access; Supervisors can review their supervisees; Staff access only their own data</li>
<li>Add or rename roles as needed for your organization structure</li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '5', 'Departments &amp; Designations',
'<ul>
<li><b>Admin Tools \u2192 Departments</b> \u2014 Add departments (e.g. Finance, Academic Affairs, IT). Each department can have a parent unit for org hierarchy.</li>
<li><b>Admin Tools \u2192 Designations</b> \u2014 Add job titles (e.g. Senior Lecturer, Accountant, HR Officer). Designations describe the position, while ranks define salary grades.</li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '6', 'Ranks &amp; Salary Grades',
'Navigate to <b>Admin Tools \u2192 Ranks</b>.
<ul>
<li>Define the promotion ladder (e.g. Lecturer \u2192 Senior Lecturer \u2192 Associate Professor \u2192 Professor)</li>
<li>Each rank has a <b>base salary</b> amount \u2014 this is the foundation for payroll gross pay calculation</li>
<li>Ranks are used in promotion workflows and payroll processing</li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '7', 'Appraisal Metrics',
'Navigate to <b>Admin Tools \u2192 Appraisal Metrics</b>.
<ul>
<li>Define measurable performance criteria e.g. Teaching Quality, Research Output, Punctuality</li>
<li>Each metric can have a maximum score (e.g. 1\u20135)</li>
<li>Staff score themselves; supervisors add their scores; HR Manager finalizes</li>
<li>If you do not use appraisals, this step can be skipped</li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '8', 'Create Users',
'Navigate to <b>Admin Tools \u2192 Users</b>.
<ul>
<li><b>Single user</b>: Click "Add User" \u2014 enter full name, email, staff ID, role, department, designation, rank, and supervisor</li>
<li><b>Bulk import</b>:
  <ul>
  <li>Download the CSV template from <b>Staff \u2192 Staff List \u2192 Download CSV Template</b></li>
  <li>Fill in staff data using Excel or Google Sheets</li>
  <li>Upload the CSV \u2192 preview the data \u2192 confirm import</li>
  <li>System creates users and notifies via email if SMTP is configured</li>
  </ul>
</li>
<li>Users can be toggled <b>active/inactive</b> using the Activate/Deactivate button in the Actions column</li>
<li>Inactive users cannot log in</li>
</ul>',
'<b>TIP:</b> Newly created users get a default password (set in code). Share credentials securely and instruct them to change on first login.'
);

// ========================================================================
// PHASE 3: STAFF SELF-SERVICE
// ========================================================================
addSectionPage($pdf, $styleH1, $styleNormal, $blue, '3', 'Staff Self-Service (Training Staff)');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '9', 'Staff Portal Orientation',
'<ul>
<li>Staff log in at <b>https://your-domain.com/login</b></li>
<li>If they forget their password, click <b>Forgot Password</b> \u2014 a reset link is sent via email (requires SMTP)</li>
<li>After login, the <b>Dashboard</b> shows:
  <ul>
  <li><b>Notification bell</b> (top-right) \u2014 click to see unread alerts (leave approvals, promotion status, etc.)</li>
  <li><b>Profile menu</b> (initials circle) \u2014 Account Settings, Logout</li>
  </ul>
</li>
<li>The sidebar menu shows only what is relevant to the Staff role</li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '10', 'Profile &amp; Documents',
'<ul>
<li><b>Staff Portal \u2192 My Profile</b> \u2014 view personal details, staff ID, department, designation, rank</li>
<li><b>Staff Portal \u2192 Update Info</b> \u2014 update phone, address, emergency contact, and upload a profile photo</li>
<li><b>Staff Documents</b> section (on profile page) shows uploaded documents from the dossier system</li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '11', 'Leave Requests',
'<ul>
<li><b>Staff Portal \u2192 My Requests \u2192 Apply for Leave</b></li>
<li>Select leave type, start/end dates, provide a reason</li>
<li>The request is routed to the staff member\'s <b>supervisor</b> for approval</li>
<li>Status updates in real-time: Pending \u2192 Approved / Rejected</li>
<li>Supervisors see pending requests under <b>Staff Portal \u2192 Approvals</b></li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '12', 'Promotion Requests',
'<ul>
<li><b>Staff Portal \u2192 My Requests \u2192 Request Promotion</b></li>
<li>Select current rank, requested rank, and upload supporting document (PDF, JPG, PNG)</li>
<li>Workflow: Staff submits \u2192 Supervisor reviews/comments \u2192 HR Manager approves/rejects</li>
<li>Status is visible in real-time; notification sent on approval</li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '13', 'Self-Appraisal',
'<ul>
<li><b>Staff Portal \u2192 My Requests \u2192 Self Appraisal</b></li>
<li>Select the appraisal period, score yourself on each defined metric (1\u20135 scale)</li>
<li>Supervisor reviews the self-score and adds their own assessment</li>
<li>HR Manager finalizes the appraisal</li>
<li>Download the completed appraisal as a <b>PDF</b> from the appraisals page</li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '14', 'Attendance Clock-In/Out',
'<ul>
<li><b>Staff Portal \u2192 My Attendance</b></li>
<li>Click <b>Clock In</b> \u2014 records timestamp with GPS coordinates</li>
<li>Must be within the office radius (configured in Company Profile), otherwise clock-in is rejected</li>
<li>Click <b>Clock Out</b> at end of shift</li>
<li>Attendance register and reports available to HR under <b>Admin Tools \u2192 Attendance</b></li>
</ul>');

// ========================================================================
// PHASE 4: PAYROLL PROCESSING
// ========================================================================
addSectionPage($pdf, $styleH1, $styleNormal, $blue, '4', 'Payroll Processing (HR Manager / Payroll Staff)');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '15', 'Payroll Setup',
'Before running payroll, complete these setup steps:
<ul>
<li><b>Payroll \u2192 Components</b> \u2014 Define earnings (e.g. Housing Allowance, Transport Subsidy) and deductions (e.g. Loan Repayment, Union Dues). Each component has a type (earnings or deduction)</li>
<li><b>Payroll \u2192 Employee Components</b> \u2014 Assign components to individual staff with specific amounts</li>
<li><b>Payroll \u2192 Benefits</b> \u2014 Create benefit types (percentage e.g. 10% of base salary, or fixed amount) and assign to employees (e.g. Risk Allowance, Research Grant)</li>
<li><b>Payroll \u2192 Bank Details</b> \u2014 Staff enter their bank account info. HR can view and export for bank transfer</li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '16', 'Create Pay Period',
'<ul>
<li>Navigate to <b>Payroll \u2192 Periods</b></li>
<li>Click <b>Create Period</b> \u2014 enter start date and end date</li>
<li>The system auto-generates a period label (e.g. "July 2026")</li>
<li>Once a period is <b>closed</b>, it cannot be re-run \u2014 this prevents accidental duplicate payroll runs</li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '17', 'Run Payroll',
'<ul>
<li><b>Payroll \u2192 Process Payroll</b> \u2014 select a period from the dropdown</li>
<li>Click <b>Run Payroll</b> for all employees, or filter by department and run for individuals</li>
<li><b>Calculation order</b>:
  <ol>
  <li>Gross Pay = rank base salary + assigned components + assigned benefits</li>
  <li>SSNIT Employee = 5.5% of gross pay</li>
  <li>Taxable Income = gross pay \u2212 SSNIT employee</li>
  <li>PAYE Tax = computed from Ghana GRA monthly tax brackets (iterative calculation)</li>
  <li>Loan Deductions = employee-specific assigned deduction amounts</li>
  <li><b>Net Pay</b> = gross pay \u2212 SSNIT \u2212 PAYE \u2212 loan deductions</li>
  </ol>
</li>
<li>Results are stored in the <b>payroll_runs</b> table. Re-running the same period <b>updates</b> existing rows (does not duplicate)</li>
</ul>',
'<b>NOTE:</b> Payroll uses Ghana-specific tax rules. SSNIT employer contribution (13%) is tracked separately. Adjust brackets in the code if tax rates change.'
);

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '18', 'Payslips &amp; Reports',
'<ul>
<li><b>Payroll \u2192 Payslips</b> \u2014 Search by staff name/ID, view individual payslips, download as <b>PDF</b></li>
<li><b>Payroll \u2192 Reports</b> \u2014 Summary by period and department. Export to Excel or CSV with DataTable buttons</li>
<li><b>Bank Transfer Report</b> \u2014 CSV file ready for bank upload (contains staff name, bank, account number, net pay)</li>
<li><b>Benefits Report</b> \u2014 CSV of all employee benefit assignments</li>
<li><b>Analysis &amp; Forecast</b> \u2014 Chart.js dashboard: department cost trends, budget projection, attrition cost analysis</li>
</ul>');

// ========================================================================
// PHASE 5: RECRUITMENT
// ========================================================================
addSectionPage($pdf, $styleH1, $styleNormal, $blue, '5', 'Recruitment (HR Manager)');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '19', 'Job Postings',
'<ul>
<li><b>Recruitment \u2192 Jobs</b> \u2014 Create job postings: title, department, description, requirements, salary range</li>
<li>Jobs appear on the <b>public applicant portal</b> at <b>https://your-domain.com/apply</b></li>
<li>Applicants can view open positions and submit applications with education and experience details</li>
<li>Applications flow into the recruitment pipeline for review</li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '20', 'Application Rounds',
'<ul>
<li><b>Recruitment \u2192 Rounds</b> \u2014 Define screening stages for each job (e.g. Application Review \u2192 Written Test \u2192 Interview \u2192 Offer)</li>
<li>Each round can have a <b>passing score</b> threshold</li>
<li>Rounds are reusable across jobs; assign them per job posting</li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '21', 'Manage Pipeline',
'<ul>
<li><b>Recruitment \u2192 Pipeline</b> \u2014 Kanban board showing all applicants grouped by stage</li>
<li><b>Move applicant</b>: Click status buttons (Pending \u2192 Under Review \u2192 Shortlisted \u2192 Interviewed \u2192 Offered \u2192 Hired/Rejected)</li>
<li><b>Schedule Interview</b>: Set date, time, type (in-person/virtual), interviewer, location</li>
<li><b>Send Offer</b>: Enter offered salary, upload offer letter (PDF/DOC), system logs it and notifies admins</li>
<li><b>Status History</b>: Click to view full audit trail with timestamps and user who made each change</li>
<li><b>Add Note</b>: Internal notes visible to the HR team</li>
</ul>');

// ========================================================================
// PHASE 6: COMMUNICATIONS
// ========================================================================
addSectionPage($pdf, $styleH1, $styleNormal, $blue, '6', 'Communications');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '22', 'Email Campaigns',
'<ul>
<li><b>Email \u2192 Send Email</b> \u2014 Compose and send to individuals, departments, or all staff</li>
<li><b>Email \u2192 Templates</b> \u2014 Save reusable templates for common announcements (payroll notifications, policy updates, etc.)</li>
<li>Email delivery requires SMTP to be configured in Company Profile</li>
</ul>');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '23', 'SMS Campaigns',
'<ul>
<li><b>SMS Dashboard</b> \u2014 View API balance, configure campaign templates</li>
<li><b>Bulk SMS</b> \u2014 Send to targeted groups by department, designation, or custom field</li>
<li>Requires an SMS API provider to be configured (see developer for provider setup)</li>
<li>If no API key is configured, SMS features will be inactive</li>
</ul>');

// ========================================================================
// PHASE 7: REPORTS & MAINTENANCE
// ========================================================================
addSectionPage($pdf, $styleH1, $styleNormal, $blue, '7', 'Reports &amp; Maintenance');

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '24', 'Key Reports',
'');
addTable($pdf,
    ['Report', 'Location', 'Format'],
    [
        ['Staff List with ID Cards', 'Staff \u2192 Staff List', 'PDF, CSV'],
        ['Dossier (full profile)', 'Profile \u2192 Download Dossier', 'PDF (merged)'],
        ['Attendance Register', 'Admin \u2192 Attendance \u2192 Register', 'CSV, Excel'],
        ['Payroll Summary', 'Payroll \u2192 Reports', 'CSV, Excel'],
        ['Bank Transfer File', 'Payroll \u2192 Bank Transfer Report', 'CSV'],
        ['Benefits Report', 'Payroll \u2192 Benefits \u2192 Report', 'CSV'],
        ['Audit Logs', 'Admin Tools \u2192 Audit Logs', 'Table view'],
        ['Recruitment Report', 'Recruitment \u2192 Compile Report', 'PDF'],
        ['Payroll Analysis', 'Payroll \u2192 Analysis &amp; Forecast', 'Chart.js dashboard'],
    ]
);

addStep($pdf, $styleH2, $styleNormal, $styleSmall, '25', 'Housekeeping',
'<ul>
<li><b>Session timeout</b>: 30 minutes idle; warning at 25 minutes with a "Stay Logged In" button. Clicking anywhere on the page resets the timer.</li>
<li><b>Password reset</b>: Via the "Forgot Password" flow (requires SMTP configured)</li>
<li><b>Audit logs</b>: All significant actions (user creation, payroll run, status changes) are logged with timestamp and user identity</li>
<li><b>ID Card Config</b>: Customize the ID card layout (logo, colors, fields shown) under <b>Admin Tools \u2192 ID Card Config</b></li>
<li><b>Flash messages</b>: Success/error messages appear at the top of pages after actions. If they do not show, check that <b>$suppressGlobalFlash</b> is not enabled without a local flash include.</li>
</ul>',
'<b>REMINDER:</b> Regularly back up your database. The system does not include an automated backup feature.'
);

// ========================================================================
// TRAINING FLOW RECOMMENDATION
// ========================================================================
$pdf->AddPage();
$pdf->SetFont($styleH1[0], $styleH1[1], $styleH1[2]);
$pdf->SetTextColor($blue[0], $blue[1], $blue[2]);
$pdf->Cell(0, 10, 'Training Flow Recommendation', 0, 1, 'L');
$pdf->SetDrawColor($blue[0], $blue[1], $blue[2]);
$pdf->Line(18, $pdf->GetY(), 192, $pdf->GetY());
$pdf->Ln(6);

$pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
$pdf->SetFont($styleNormal[0], $styleNormal[1], $styleNormal[2]);
$pdf->writeHTML('Deliver the training in <b>three sessions</b>. Use a <b>sandbox database</b> so staff can experiment safely without affecting production data.', true, false, true, false, '');
$pdf->Ln(4);

addTable($pdf,
    ['Session', 'Duration', 'Audience', 'Topics'],
    [
        ['1 \u2014 Admin Foundation', '2 hours', 'HR Manager, IT Admin', 'Steps 1\u20138: Setup, users, departments, ranks, appraisal metrics'],
        ['2 \u2014 Staff Self-Service', '1.5 hours', 'All staff', 'Steps 9\u201314: Portal, profile, leave, promotion, appraisal, attendance'],
        ['3 \u2014 Payroll &amp; Recruitment', '2.5 hours', 'Payroll staff, HR team', 'Steps 15\u201324: Payroll, recruitment, reports, communications'],
    ]
);

$pdf->Ln(4);
$pdf->SetFont($styleH2[0], $styleH2[1], $styleH2[2]);
$pdf->Cell(0, 8, 'Session Format', 0, 1, 'L');
$pdf->SetFont($styleNormal[0], $styleNormal[1], $styleNormal[2]);
$pdf->writeHTML('For each session: <b>Live demo \u2192 Hands-on practice \u2192 Q&amp;A</b>. Allow staff to follow along in their own accounts on the sandbox.', true, false, true, false, '');
$pdf->Ln(4);

$pdf->SetFont($styleH2[0], $styleH2[1], $styleH2[2]);
$pdf->Cell(0, 8, 'Pre-Training Checklist', 0, 1, 'L');
$pdf->SetFont($styleNormal[0], $styleNormal[1], $styleNormal[2]);
$pdf->writeHTML('<ul>
<li>Verify the system is installed and accessible from all training computers</li>
<li>Prepare a sandbox database pre-loaded with sample data (fake staff, departments, etc.)</li>
<li>Create training user accounts for each attendee</li>
<li>Ensure the training room projector/display works with the browser</li>
<li>Have printed copies of this guide available (or share the PDF beforehand)</li>
</ul>', true, false, true, false, '');

// --- Footer note ---
$pdf->Ln(8);
$pdf->SetFont($styleSmall[0], $styleSmall[1], $styleSmall[2]);
$pdf->SetTextColor($gray[0], $gray[1], $gray[2]);
$pdf->Cell(0, 6, 'End of Training Guide', 0, 1, 'C');

// --- Output ---
$outputPath = __DIR__ . '/../HRGoTo_HCM_Training_Guide.pdf';
$pdf->Output($outputPath, 'F');
echo "PDF generated: $outputPath\n";
