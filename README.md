# HRGoTo HCM

A full-featured Human Capital Management system built with PHP 8.3, MySQL, and a custom MVC framework. Covers the complete employee lifecycle — from recruitment and onboarding through payroll, attendance, appraisals, and offboarding.

## Tech Stack

- **Backend:** PHP 8.3+, Custom MVC (Router, Request, Database singleton)
- **Database:** MySQL 5.7+ via PDO with prepared statements
- **PDF Generation:** TCPDF
- **Email:** PHPMailer (SMTP)
- **SMS:** MNotify API integration
- **Frontend:** Server-rendered PHP views, Bootstrap, jQuery, Chart.js

## Features

### Authentication & Security
- Session-based authentication with 30-minute idle timeout
- Brute-force protection (3 failed attempts → 10-minute lockout)
- CSRF tokens on all POST routes
- Role-based access control: Super Admin, HR Manager, Supervisor, Staff
- Password reset via email with token expiry

### Staff Management
- Staff registry with search, filter, and pagination
- CSV bulk onboarding import with batch processing
- Employee self-service portal
- Staff ID card generator
- PDF dossier compilation
- Profile management with avatar upload

### Recruitment & Onboarding
- Job posting CRUD with open/closed status
- Public-facing job application portal
- Applicant tracking with status workflow (pending → reviewing → shortlisted → hired/unsuccessful)
- Multi-stage recruitment rounds with scoring
- Interview scheduling and offer management
- Automated SMS notifications on status changes
- Multi-page PDF report compilation for candidates

### Payroll
- Configurable payroll components (allowances/deductions, fixed/percentage)
- Employee-level component and benefit assignments
- Period-based payroll processing
- Ghanaian tax compliance: SSNIT (5.5% employee / 13% employer) and PAYE brackets
- PDF payslip generation
- Bank transfer report export (CSV)
- Loan and installment deduction tracking
- Benefits and incentives management

### Attendance
- Clock in/out with geolocation capture
- Configurable work hours, grace periods, and late thresholds
- Attendance reports and admin settings

### Workforce Management
- Promotion requests with supervisor → HR approval workflow
- Appraisal scoring, rating, and self-assessment
- Leave request management with multi-level approval
- Appraisal PDF generation

### Communications
- Bulk SMS campaign engine
- Email compose with HTML template builder
- In-app notification system

### Administration
- User, role, department, designation, and rank management
- Appraisal metrics configuration
- Company profile settings
- Audit trail logging
- Database and file backup tools
- Configurable staff ID card design

## Project Structure

```
hrgoto/
├── public/                  # Entry point, static assets, uploads
│   ├── index.php            # Front controller
│   ├── assets/              # CSS, JS, images
│   ├── auth/                # Auth-related assets
│   └── uploads/             # User uploads (photos, CVs, documents)
├── app/
│   ├── Controllers/         # 15 controllers
│   │   ├── AdminController.php
│   │   ├── ApplyController.php
│   │   ├── AttendanceController.php
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── EmailController.php
│   │   ├── ManualController.php
│   │   ├── OnboardingController.php
│   │   ├── PayrollController.php
│   │   ├── ProfileController.php
│   │   ├── RecruitmentController.php
│   │   ├── SmsController.php
│   │   ├── StaffController.php
│   │   ├── StaffServicesController.php
│   │   └── WorkforceController.php
│   ├── Core/
│   │   ├── Database.php     # PDO singleton
│   │   ├── Request.php      # Input sanitization
│   │   └── Router.php       # URL dispatcher
│   ├── Helpers/
│   │   ├── Notification.php # In-app notifications
│   │   ├── PdfHelper.php    # PDF utilities
│   │   └── Security.php     # CSRF, XSS, flash messages
│   ├── Middleware/
│   │   ├── AuthMiddleware.php   # Session + role checks
│   │   └── CSRFMiddleware.php   # Token validation
│   ├── Models/
│   │   ├── ApplicantModel.php
│   │   ├── SmsCampaign.php
│   │   ├── Staff.php
│   │   └── User.php
│   ├── Services/
│   │   └── AuthService.php  # Authentication logic
│   ├── Views/               # PHP templates per module
│   └── storage/             # App-level storage
├── routes/
│   └── web.php              # All route definitions (220 lines)
├── scripts/
│   └── generate-training-guide.php
├── vendor/                  # Composer dependencies
├── .env                     # Environment config
├── composer.json
└── *.sql                    # Database schemas
```

## Setup

### Prerequisites

- PHP 8.3+
- MySQL 5.7+
- Apache with `mod_rewrite` enabled
- Composer

### Installation

```bash
git clone https://github.com/your-username/hrgoto.git
cd hrgoto
composer install
```

1. Create a MySQL database
2. Import the schema:
   ```bash
   mysql -u root -p your_database < final_schema.sql
   ```
3. Configure environment:
   ```bash
   cp .env .env.backup
   ```
   Edit `.env` with your database credentials and SMTP/SMS settings:
   ```
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=hrmis
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```
4. Point your Apache document root to `hrgoto/public/`
5. Ensure `public/uploads/` is writable by the web server

### Default Credentials

| Field | Value |
|-------|-------|
| Username | `admin` |
| Email | `admin@norgence.com` |
| Password | See seeded bcrypt hash in `final_schema.sql` |
| Role | Super Admin |

> **Note:** If using the fresh `final_schema.sql`, the admin password hash is pre-seeded. You may need to use the password reset flow or update the hash directly if it doesn't match.

### Apache Configuration

Ensure `.htaccess` rewriting is enabled in your Apache vhost:

```apache
<Directory "C:/Apache24/htdocs/hrgoto/public">
    AllowOverride All
    Require all granted
</Directory>
```

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | MySQL host | `localhost` |
| `DB_PORT` | MySQL port | `3306` |
| `DB_DATABASE` | Database name | `hrmis` |
| `DB_USERNAME` | DB username | `root` |
| `DB_PASSWORD` | DB password | (empty) |
| `APP_URL` | Base URL of the application | Auto-detected |

## License

Proprietary — contact the author for usage rights.
