-- HRGoTo HCM final complete schema
-- Generated for fresh setup with one admin seed user

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS hr_notes;
DROP TABLE IF EXISTS application_files;
DROP TABLE IF EXISTS employment_entries;
DROP TABLE IF EXISTS education_entries;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS onboarding_csv_batches;
DROP TABLE IF EXISTS sms_logs;
DROP TABLE IF EXISTS sms_campaign_templates;
DROP TABLE IF EXISTS sender_ids;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS rate_limits;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS app_config;
DROP TABLE IF EXISTS leave_requests;
DROP TABLE IF EXISTS staff_appraisals;
DROP TABLE IF EXISTS appraisal_metrics;
DROP TABLE IF EXISTS staff_promotions;
DROP TABLE IF EXISTS staff_experience;
DROP TABLE IF EXISTS staff_education;
DROP TABLE IF EXISTS staff_records;
DROP TABLE IF EXISTS designations;
DROP TABLE IF EXISTS directorates;
DROP TABLE IF EXISTS schools;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(80) NOT NULL UNIQUE,
    permissions TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(150) NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Active',
    last_login DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at DATETIME NULL,
    INDEX idx_users_role (role_id),
    INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE departments (
    dept_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dept_code VARCHAR(30) NOT NULL UNIQUE,
    dept_name VARCHAR(150) NOT NULL,
    parent_unit VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE schools (
    sch_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sch_name VARCHAR(255) NOT NULL,
    sch_code VARCHAR(255) NULL,
    parent_unit VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE directorates (
    dir_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dir_name VARCHAR(255) NOT NULL,
    dir_code VARCHAR(255) NULL,
    parent_unit VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE designations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    designation_category VARCHAR(80) NULL,
    category VARCHAR(80) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE staff_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    staff_id_card VARCHAR(60) NOT NULL UNIQUE,
    dept_id INT UNSIGNED NULL,
    designation_id INT UNSIGNED NULL,
    supervisor_user_id INT UNSIGNED NULL,
    gender VARCHAR(20) NULL,
    date_of_birth DATE NULL,
    date_joined DATE NULL,
    employment_status VARCHAR(40) NOT NULL DEFAULT 'Permanent',
    phone_one VARCHAR(30) NULL,
    phone_two VARCHAR(30) NULL,
    hometown VARCHAR(100) NULL,
    region VARCHAR(100) NULL,
    nationality VARCHAR(80) NULL,
    religion VARCHAR(80) NULL,
    marital_status VARCHAR(30) NULL,
    number_of_children INT NOT NULL DEFAULT 0,
    biography TEXT NULL,
    avatar_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_supervisor (supervisor_user_id),
    INDEX idx_staff_dept (dept_id),
    INDEX idx_staff_designation (designation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE staff_education (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    institution VARCHAR(200) NOT NULL,
    certificate VARCHAR(150) NULL,
    year_from INT NULL,
    year_to INT NULL,
    dossier_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_edu_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE staff_experience (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    company_name VARCHAR(200) NULL,
    job_title VARCHAR(150) NULL,
    year_from INT NULL,
    year_to INT NULL,
    responsibilities TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_exp_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE staff_promotions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    from_designation VARCHAR(150) NULL,
    to_designation VARCHAR(150) NULL,
    effective_date DATE NULL,
    current_rank VARCHAR(150) NULL,
    requested_rank VARCHAR(150) NULL,
    remarks TEXT NULL,
    supervisor_comment TEXT NULL,
    hr_comment TEXT NULL,
    supervisor_id INT UNSIGNED NULL,
    hr_id INT UNSIGNED NULL,
    supporting_document VARCHAR(255) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Pending Comments',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_promotions_user (user_id),
    INDEX idx_promotions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE appraisal_metrics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(120) NOT NULL,
    metric_prompt TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE staff_appraisals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    period_label VARCHAR(100) NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    self_score DECIMAL(5,2) NULL,
    rating VARCHAR(40) NOT NULL,
    summary TEXT NULL,
    supervisor_comment TEXT NULL,
    hr_comment TEXT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Pending Comments',
    appraiser_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_appraisals_user (user_id),
    INDEX idx_appraisals_period (period_label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE leave_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    leave_type VARCHAR(60) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INT NOT NULL,
    reason TEXT NOT NULL,
    supervisor_comment TEXT NULL,
    hr_comment TEXT NULL,
    supervisor_id INT UNSIGNED NULL,
    hr_id INT UNSIGNED NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Pending Supervisor Sign-off',
    review_note TEXT NULL,
    reviewed_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_leave_user (user_id),
    INDEX idx_leave_status (status),
    INDEX idx_leave_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE app_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sms_endpoint VARCHAR(255) NULL,
    gen_sms_sender_id VARCHAR(20) NULL,
    sms_apikey VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sender_ids (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sms_campaign_templates (
    sms_cam_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pending TEXT NULL,
    shortlisted TEXT NULL,
    interviewed TEXT NULL,
    hired TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE email_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(120) NOT NULL,
    template_subject VARCHAR(255) NOT NULL,
    template_body TEXT NOT NULL,
    variables VARCHAR(500) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sms_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(30) NOT NULL,
    milestone VARCHAR(60) NULL,
    message TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Delivered',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE onboarding_csv_batches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id VARCHAR(32) NOT NULL UNIQUE,
    file_path VARCHAR(255) NOT NULL,
    total_rows INT NOT NULL DEFAULT 0,
    completed_rows INT NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'Queued',
    progress_message VARCHAR(255) NULL,
    error_message TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_onboarding_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    department VARCHAR(150) NOT NULL,
    type ENUM('full-time','part-time','contract','internship') NOT NULL DEFAULT 'full-time',
    location VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT NOT NULL,
    salary_range VARCHAR(100) NULL,
    deadline DATE NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_jobs_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(20) NOT NULL UNIQUE,
    job_id INT UNSIGNED NOT NULL,
    title VARCHAR(20) NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NULL,
    place_of_birth VARCHAR(100) NULL,
    home_town VARCHAR(100) NULL,
    region VARCHAR(100) NULL,
    nationality VARCHAR(100) NULL,
    religion VARCHAR(100) NULL,
    marital_status ENUM('married','single','widowed','divorced') NULL,
    spouse_name VARCHAR(200) NULL,
    spouse_address TEXT NULL,
    children_details TEXT NULL,
    gra_pin VARCHAR(50) NULL,
    ghana_card_number VARCHAR(50) NULL,
    date_of_birth DATE NULL,
    gender ENUM('male','female','other','prefer_not_to_say') NULL,
    highest_qualification VARCHAR(150) NOT NULL,
    institution VARCHAR(200) NOT NULL,
    years_experience INT NOT NULL DEFAULT 0,
    publications TEXT NULL,
    relevance_statement TEXT NULL,
    objection_to_reference ENUM('yes','no') NULL,
    physical_disability ENUM('yes','no') NULL,
    disability_details TEXT NULL,
    conviction ENUM('yes','no') NULL,
    conviction_details TEXT NULL,
    hobbies TEXT NULL,
    appointment_notice_period VARCHAR(100) NULL,
    additional_info TEXT NULL,
    referee1_name VARCHAR(150) NULL,
    referee1_occupation VARCHAR(100) NULL,
    referee1_position VARCHAR(100) NULL,
    referee1_address TEXT NULL,
    referee1_tel VARCHAR(20) NULL,
    referee1_email VARCHAR(150) NULL,
    referee2_name VARCHAR(150) NULL,
    referee2_occupation VARCHAR(100) NULL,
    referee2_position VARCHAR(100) NULL,
    referee2_address TEXT NULL,
    referee2_tel VARCHAR(20) NULL,
    referee2_email VARCHAR(150) NULL,
    referee3_name VARCHAR(150) NULL,
    referee3_occupation VARCHAR(100) NULL,
    referee3_position VARCHAR(100) NULL,
    referee3_address TEXT NULL,
    referee3_tel VARCHAR(20) NULL,
    referee3_email VARCHAR(150) NULL,
    cv_filename VARCHAR(255) NOT NULL,
    cv_original_name VARCHAR(255) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'pending',
    hr_notes TEXT NULL,
    ip_address VARCHAR(45) NULL,
    submitted_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    passport_photo_filename VARCHAR(255) NULL,
    passport_photo_original_name VARCHAR(255) NULL,
    INDEX idx_applications_status (status),
    INDEX idx_applications_job (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE education_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    institution VARCHAR(200) NOT NULL,
    certificate VARCHAR(150) NOT NULL,
    from_year VARCHAR(10) NULL,
    to_year VARCHAR(10) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    INDEX idx_edu_app (application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE employment_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    from_date VARCHAR(20) NULL,
    to_date VARCHAR(20) NULL,
    institution_name VARCHAR(200) NULL,
    institution_address TEXT NULL,
    position VARCHAR(150) NULL,
    subject_work TEXT NULL,
    nature ENUM('full-time','part-time') DEFAULT 'full-time',
    reason_for_leaving TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    INDEX idx_emp_app (application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE application_files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NULL,
    size_bytes INT NULL DEFAULT 0,
    uploaded_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_application_id (application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE hr_notes (
    note_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT UNSIGNED NOT NULL,
    reference_number VARCHAR(32) NOT NULL,
    round_id INT NOT NULL,
    message TEXT NOT NULL,
    INDEX idx_hr_notes_applicant (applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(100) NOT NULL,
    attempted_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    is_successful TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_login_attempts_lookup (ip_address, email, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rate_limits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    action VARCHAR(50) NOT NULL,
    attempts INT NOT NULL DEFAULT 1,
    last_attempt TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_rate_limit (ip_address, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user_unread (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles (id, role_name, permissions) VALUES
(1, 'Super Admin', 'all'),
(2, 'HR Manager', 'users.manage,roles.manage,staff.manage,leave.approve,promotions.approve,appraisals.manage'),
(3, 'Supervisor', 'staff.services,approvals.review,leave.comment,promotion.comment,appraisal.comment'),
(4, 'Staff', 'staff.portal,leave.request,promotion.request,appraisal.submit')
ON DUPLICATE KEY UPDATE role_name = VALUES(role_name), permissions = VALUES(permissions);

INSERT INTO departments (dept_code, dept_name, parent_unit) VALUES
('HR', 'Human Resource', 'Corporate Services'),
('FIN', 'Finance', 'Corporate Services'),
('OPS', 'Operations', 'Business Operations')
ON DUPLICATE KEY UPDATE dept_name = VALUES(dept_name), parent_unit = VALUES(parent_unit);

INSERT INTO schools (sch_name, sch_code, parent_unit) VALUES
('School of Applied Science', 'SAS', 'Academic'),
('School of Engineering', 'SOE', 'Academic')
ON DUPLICATE KEY UPDATE sch_name = VALUES(sch_name);

INSERT INTO directorates (dir_name, dir_code, parent_unit) VALUES
('Human Resource Directorate', 'HRD', 'Corporate Services'),
('Finance Directorate', 'FIND', 'Corporate Services')
ON DUPLICATE KEY UPDATE dir_name = VALUES(dir_name);

INSERT INTO designations (title, designation_category, category) VALUES
('HR Manager', 'Management', 'Management'),
('Supervisor', 'Management', 'Management'),
('Staff Officer', 'Staff', 'Staff')
ON DUPLICATE KEY UPDATE
designation_category = VALUES(designation_category),
category = VALUES(category);

INSERT INTO app_config (id, sms_endpoint, gen_sms_sender_id, sms_apikey)
VALUES (1, 'https://api.mnotify.com/api/sms/quick', 'HRGoTo', '')
ON DUPLICATE KEY UPDATE
sms_endpoint = VALUES(sms_endpoint),
gen_sms_sender_id = VALUES(gen_sms_sender_id),
sms_apikey = VALUES(sms_apikey);

INSERT INTO sender_ids (name) VALUES ('HRGoTo')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO sms_campaign_templates (sms_cam_id, pending, shortlisted, interviewed, hired)
VALUES
(1,
 'Dear [fullname], your application is pending review. HRGoTo HCM.',
 'Dear [fullname], congratulations. You have been shortlisted. HRGoTo HCM.',
 'Dear [fullname], you have been invited for an interview. Please attend on [interview_date] at [interview_time]. HRGoTo HCM.',
 'Dear [fullname], congratulations. You have been selected. HRGoTo HCM.')
ON DUPLICATE KEY UPDATE
pending = VALUES(pending),
shortlisted = VALUES(shortlisted),
interviewed = VALUES(interviewed),
hired = VALUES(hired);

INSERT INTO email_templates (template_name, template_subject, template_body, variables, is_active) VALUES
('Appointment Letter', 'Appointment Letter - [job_title] - HRGoTo HCM',
 '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;"><h2 style="color:#0d6efd;">Appointment Letter</h2><p>Dear [fullname],</p><p>Congratulations! We are pleased to offer you the position of <strong>[job_title]</strong> at our organization.</p><p>Please review the attached appointment letter for full details regarding your compensation, benefits, and start date.</p><p>If you have any questions, please do not hesitate to contact the HR department.</p><p>Best regards,<br><strong>HR Department</strong><br>HRGoTo HCM</p></div>',
 '[fullname],[job_title]', 1)
ON DUPLICATE KEY UPDATE template_subject = VALUES(template_subject), template_body = VALUES(template_body), variables = VALUES(variables);

INSERT INTO appraisal_metrics (metric_name, metric_prompt, is_active) VALUES
('Service Delivery', 'Rate your service delivery quality and timeliness.', 1),
('Team Collaboration', 'Rate your collaboration with colleagues and supervisors.', 1),
('Initiative', 'Rate how proactively you solve problems and improve workflows.', 1)
ON DUPLICATE KEY UPDATE metric_prompt = VALUES(metric_prompt), is_active = VALUES(is_active);

INSERT INTO users (id, fullname, username, email, password, role_id, status)
VALUES
(1, 'System Administrator', 'admin', 'admin@norgence.com', '$2y$05$K6kkn6yZA57FB7.GTLk7d.TWuGeeZufv7wzq3zDtfecXrm7qL1qPK', 1, 'Active')
ON DUPLICATE KEY UPDATE
fullname = VALUES(fullname),
username = VALUES(username),
email = VALUES(email),
password = VALUES(password),
role_id = VALUES(role_id),
status = VALUES(status);

INSERT INTO staff_records (
    user_id, staff_id_card, dept_id, designation_id, supervisor_user_id, gender,
    date_of_birth, date_joined, employment_status, phone_one, nationality, marital_status
)
SELECT
    1,
    'ADM-0001',
    (SELECT dept_id FROM departments WHERE dept_code = 'HR' LIMIT 1),
    (SELECT id FROM designations WHERE title = 'HR Manager' LIMIT 1),
    NULL,
    'Male',
    '1990-01-01',
    CURDATE(),
    'Permanent',
    '0200000000',
    'Ghanaian',
    'single'
WHERE NOT EXISTS (SELECT 1 FROM staff_records WHERE user_id = 1);

INSERT INTO jobs (id, title, department, type, location, description, requirements, salary_range, deadline, status, created_by)
VALUES
(1, 'Lecturer', 'General', 'full-time', 'Bolgatanga', 'General lecturer role', 'Relevant qualification', '', DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'open', 1)
ON DUPLICATE KEY UPDATE
title = VALUES(title),
department = VALUES(department),
status = VALUES(status);
