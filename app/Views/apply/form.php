<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$brand = \App\Helpers\Security::escape($company['short_name'] . ' Careers');
$error = $_SESSION['apply_error'] ?? '';
unset($_SESSION['apply_error']);
$maxFileMb = 10;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo \App\Helpers\Security::escape($pageTitle); ?></title>
  <link rel="icon" type="image/svg+xml" href="<?php echo $appUrl; ?>/assets/favicons/favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    :root {
      --primary-navy: #0F1E36;
      --accent-blue: #3B82F6;
      --accent-green: #22C55E;
      --text-main: #1E293B;
      --text-muted: #64748B;
      --bg-light: #F8FAFC;
      --card-border: rgba(15, 30, 54, 0.12);
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-light);
      color: var(--text-main);
      -webkit-font-smoothing: antialiased;
    }

    .navbar {
      background: var(--primary-navy);
      border-bottom: 4px solid var(--primary-navy);
      padding: 16px 24px;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .nav-container { max-width: 900px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
    .brand-name { color: #fff; font-size: 1.5rem; font-weight: 800; letter-spacing: -0.5px; }
    .brand-tagline { color: rgba(255,255,255,0.7); font-size: 0.55rem; font-weight: 600; letter-spacing: 1px; }
    .nav-back-btn { color: rgba(255,255,255,0.85); text-decoration: none; font-size: 0.875rem; font-weight: 600; display: flex; align-items: center; gap: 6px; transition: color 0.2s; }
    .nav-back-btn:hover { color: #fff; }

    .page-container { max-width: 900px; margin: 0 auto; padding: 32px 24px 60px; }

    .job-banner {
      background: var(--primary-navy);
      border-radius: 16px;
      padding: 32px;
      margin-bottom: 32px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 8px 24px rgba(15,30,54,0.15);
    }
    .job-banner h3 { font-weight: 800; font-size: 1.4rem; color: #fff; position: relative; z-index: 1; }
    .job-banner .meta { color: rgba(255,255,255,0.6); font-size: 0.85rem; position: relative; z-index: 1; }
    .job-banner .meta i { color: var(--accent-blue); width: 18px; }
    .job-banner .badge-type { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; padding: 5px 16px; border-radius: 100px; background: rgba(255,255,255,0.15); color: #fff; }

    .alert-box {
      background: #FEF2F2;
      border: 1px solid #FECACA;
      border-radius: 12px;
      padding: 16px 20px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 12px;
      color: #991B1B;
      font-weight: 500;
      font-size: 0.9rem;
    }
    .alert-box i { font-size: 1.2rem; color: #EF4444; }

    .form-card {
      background: #fff;
      border: 1px solid #d1d5db;
      border-radius: 16px;
      padding: 36px;
      margin-bottom: 24px;
      box-shadow: 0 4px 16px rgba(15,30,54,0.06);
    }
    .form-card:hover { box-shadow: 0 6px 24px rgba(15,30,54,0.1); }

    .section-header {
      font-weight: 800;
      font-size: 1.05rem;
      color: var(--primary-navy);
      padding-bottom: 14px;
      border-bottom: 3px solid var(--accent-blue);
      margin-bottom: 28px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .section-header i { color: var(--accent-blue); font-size: 1.15rem; }

    .form-label {
      font-weight: 600;
      font-size: 0.8rem;
      color: var(--text-main);
      margin-bottom: 4px;
      display: block;
    }
    .required::after { content: " *"; color: #EF4444; }

    .form-control, .form-select {
      width: 100%;
      border: 1.5px solid #d1d5db;
      border-radius: 10px;
      font-size: 0.9rem;
      padding: 11px 14px;
      font-family: inherit;
      transition: all 0.2s;
      background: #fff;
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--accent-blue);
      box-shadow: 0 0 0 4px rgba(59,130,246,0.12);
      outline: none;
    }

    .file-row {
      background: #FAFBFC;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 14px;
      position: relative;
    }
    .file-row .remove-btn {
      position: absolute;
      top: 10px;
      right: 12px;
      background: none;
      border: none;
      color: #EF4444;
      font-size: 1.1rem;
      cursor: pointer;
      padding: 4px 8px;
      border-radius: 6px;
      transition: background 0.2s;
    }
    .file-row .remove-btn:hover { background: #FEF2F2; }
    .file-input-wrap { position: relative; }
    .file-input-wrap input[type="file"] {
      width: 100%;
      padding: 10px;
      border: 2px dashed #d1d5db;
      border-radius: 8px;
      font-size: 0.8rem;
      cursor: pointer;
      transition: border-color 0.2s;
      background: #fff;
    }
    .file-input-wrap input[type="file"]:hover { border-color: var(--accent-blue); }
    .file-limit-hint { font-size: 0.7rem; color: var(--text-muted); margin-top: 4px; }

    .btn-add-file {
      background: none;
      border: 2px dashed #d1d5db;
      border-radius: 12px;
      padding: 18px;
      width: 100%;
      color: var(--text-muted);
      font-weight: 600;
      font-size: 0.85rem;
      cursor: pointer;
      transition: all 0.2s;
      font-family: inherit;
    }
    .btn-add-file:hover { border-color: var(--accent-blue); color: var(--accent-blue); background: #F0F7FF; }

    .btn-submit {
      background: var(--primary-navy);
      color: #fff;
      font-weight: 700;
      font-size: 1rem;
      padding: 16px 56px;
      border-radius: 12px;
      border: none;
      transition: all 0.2s;
      cursor: pointer;
      font-family: inherit;
    }
    .btn-submit:hover {
      background: #1a3256;
      transform: translateY(-2px);
      box-shadow: 0 10px 24px rgba(15,30,54,0.2);
    }

    .footer {
      background: var(--primary-navy);
      text-align: center;
      padding: 28px 24px;
      margin-top: 20px;
    }
    .footer-copy { color: rgba(255,255,255,0.85); font-size: 0.85rem; font-weight: 500; }
    .footer-powered { color: rgba(255,255,255,0.6); font-size: 0.65rem; font-weight: 600; letter-spacing: 1px; margin-top: 4px; }

    hr { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }

    .row { display: flex; flex-wrap: wrap; margin: 0 -8px; }
    .row > [class*="col-"] { padding: 0 8px; }
    .col-12 { width: 100%; }
    .col-md-2 { width: 16.666%; }
    .col-md-4 { width: 33.333%; }
    .col-md-5 { width: 41.666%; }
    .col-md-6 { width: 50%; }
    .col-md-7 { width: 58.333%; }

    @media (max-width: 768px) {
      .col-md-2, .col-md-4, .col-md-5, .col-md-6, .col-md-7 { width: 100%; }
      .form-card { padding: 24px; }
      .job-banner { padding: 24px; }
      .job-banner h3 { font-size: 1.15rem; }
    }
  </style>
</head>
<body>

  <nav class="navbar">
    <div class="nav-container">
      <div>
        <span class="brand-name"><?php echo $brand; ?></span>
        <div class="brand-tagline">POWERED BY NORGENCE</div>
      </div>
      <a href="/apply" class="nav-back-btn"><i class="fas fa-arrow-left me-1"></i>All Jobs</a>
    </div>
  </nav>

  <div class="page-container">

    <?php if ($error): ?>
    <div class="alert-box"><i class="fas fa-exclamation-circle"></i><?php echo \App\Helpers\Security::escape($error); ?></div>
    <?php endif; ?>

    <div class="job-banner">
      <div class="d-flex justify-content-between align-items-start flex-wrap" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
        <div>
          <h3><?php echo \App\Helpers\Security::escape($job['title']); ?></h3>
          <div class="meta"><i class="fas fa-building"></i><?php echo \App\Helpers\Security::escape($job['department']); ?> &middot; <i class="fas fa-location-dot ms-2"></i><?php echo \App\Helpers\Security::escape($job['location'] ?: 'N/A'); ?></div>
        </div>
        <span class="badge-type"><?php echo \App\Helpers\Security::escape($job['type']); ?></span>
      </div>
    </div>

    <form method="post" action="/apply/submit" enctype="multipart/form-data" onsubmit="return validateForm()">
      <input type="hidden" name="job_id" value="<?php echo (int)$job['id']; ?>">

      <!-- Personal Information -->
      <div class="form-card">
        <div class="section-header"><i class="fas fa-user"></i>Personal Information</div>
        <div class="row" style="row-gap:16px;">
          <div class="col-md-2">
            <label class="form-label">Title</label>
            <select name="title" class="form-select">
              <option value="">Select</option>
              <option value="Mr">Mr</option>
              <option value="Mrs">Mrs</option>
              <option value="Miss">Miss</option>
              <option value="Ms">Ms</option>
              <option value="Dr">Dr</option>
              <option value="Prof">Prof</option>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label required">First Name</label>
            <input type="text" name="first_name" class="form-control" required>
          </div>
          <div class="col-md-5">
            <label class="form-label required">Last Name</label>
            <input type="text" name="last_name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label required">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label required">Phone</label>
            <input type="tel" name="phone" class="form-control" required placeholder="e.g. 024XXXXXXX">
          </div>
          <div class="col-12">
            <label class="form-label">Postal Address</label>
            <textarea name="address" class="form-control" rows="2"></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">Place of Birth</label>
            <input type="text" name="place_of_birth" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">Home Town</label>
            <input type="text" name="home_town" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">Region</label>
            <input type="text" name="region" class="form-control" placeholder="e.g. Upper East">
          </div>
          <div class="col-md-4">
            <label class="form-label">Nationality</label>
            <input type="text" name="nationality" class="form-control" value="Ghanaian">
          </div>
          <div class="col-md-4">
            <label class="form-label">Religion</label>
            <input type="text" name="religion" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">Marital Status</label>
            <select name="marital_status" class="form-select">
              <option value="">Select</option>
              <option value="single">Single</option>
              <option value="married">Married</option>
              <option value="divorced">Divorced</option>
              <option value="widowed">Widowed</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="date_of_birth" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-select">
              <option value="">Select</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Ghana Card</label>
            <input type="text" name="ghana_card_number" class="form-control" placeholder="GHA-XXXXXXXXX-X">
          </div>
          <div class="col-md-4">
            <label class="form-label">GRA PIN</label>
            <input type="text" name="gra_pin" class="form-control">
          </div>
          <div class="col-12" style="margin-top:8px;">
            <label class="form-label">Hobbies / Interests</label>
            <textarea name="hobbies" class="form-control" rows="2"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Additional Information</label>
            <textarea name="additional_info" class="form-control" rows="3"></textarea>
          </div>
        </div>
      </div>

      <!-- Education -->
      <div class="form-card">
        <div class="section-header"><i class="fas fa-graduation-cap"></i>Education</div>
        <div id="eduContainer">
          <div class="edu-row" style="background:#FAFBFC;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:14px;">
            <div class="row" style="row-gap:12px;">
              <div class="col-md-6">
                <label class="form-label required">Qualification</label>
                <select name="edu_qualification[]" class="form-select" required>
                  <option value="">Select...</option>
                  <option value="PhD / Doctorate">PhD / Doctorate</option>
                  <option value="Masters Degree">Masters Degree</option>
                  <option value="Bachelors Degree">Bachelors Degree</option>
                  <option value="HND">HND</option>
                  <option value="Diploma">Diploma</option>
                  <option value="Certificate">Certificate</option>
                  <option value="WASSCE / SSSCE">WASSCE / SSSCE</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label required">Institution</label>
                <input type="text" name="edu_institution[]" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Field of Study</label>
                <input type="text" name="edu_field[]" class="form-control" placeholder="e.g. Computer Science">
              </div>
              <div class="col-md-2">
                <label class="form-label">Start Year</label>
                <input type="number" name="edu_start[]" class="form-control" min="1950" max="2099" placeholder="YYYY">
              </div>
              <div class="col-md-2">
                <label class="form-label">End Year</label>
                <input type="number" name="edu_end[]" class="form-control" min="1950" max="2099" placeholder="YYYY">
              </div>
              <div class="col-md-2">
                <label class="form-label">Grade</label>
                <input type="text" name="edu_grade[]" class="form-control" placeholder="e.g. First Class">
              </div>
            </div>
          </div>
        </div>
        <button type="button" class="btn-add-file" onclick="addEduRow()"><i class="fas fa-plus me-2"></i>Add Another Qualification</button>
      </div>

      <!-- Experience -->
      <div class="form-card">
        <div class="section-header"><i class="fas fa-briefcase"></i>Experience</div>
        <div id="expContainer">
          <div class="exp-row" style="background:#FAFBFC;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:14px;">
            <div class="row" style="row-gap:12px;">
              <div class="col-md-6">
                <label class="form-label">Role / Position</label>
                <input type="text" name="job_role[]" class="form-control" placeholder="e.g. Senior Accountant">
              </div>
              <div class="col-md-3">
                <label class="form-label">Start Year</label>
                <input type="number" name="job_start[]" class="form-control" min="1950" max="2099" placeholder="YYYY">
              </div>
              <div class="col-md-3">
                <label class="form-label">End Year</label>
                <input type="number" name="job_end[]" class="form-control" min="1950" max="2099" placeholder="YYYY">
              </div>
            </div>
          </div>
        </div>
        <button type="button" class="btn-add-file" onclick="addExpRow()"><i class="fas fa-plus me-2"></i>Add Another Job</button>
      </div>

      <!-- Referees -->
      <div class="form-card">
        <div class="section-header"><i class="fas fa-address-book"></i>Referees</div>
        <div class="row" style="row-gap:16px;">
          <div class="col-md-6">
            <label class="form-label">Referee 1 Name</label>
            <input type="text" name="referee1_name" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Position</label>
            <input type="text" name="referee1_position" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="tel" name="referee1_tel" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="referee1_email" class="form-control">
          </div>
        </div>
        <hr>
        <div class="row" style="row-gap:16px;">
          <div class="col-md-6">
            <label class="form-label">Referee 2 Name</label>
            <input type="text" name="referee2_name" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Position</label>
            <input type="text" name="referee2_position" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="tel" name="referee2_tel" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="referee2_email" class="form-control">
          </div>
        </div>
      </div>

      <!-- CV + Photo -->
      <div class="form-card">
        <div class="section-header"><i class="fas fa-file-alt"></i>CV & Photograph</div>
        <div class="row" style="row-gap:16px;">
          <div class="col-md-6">
            <label class="form-label required">CV / Resume</label>
            <div class="file-input-wrap">
              <input type="file" name="cv" accept=".pdf,.doc,.docx" required onchange="this.style.borderColor='#22C55E'">
            </div>
            <div class="file-limit-hint">Accepted: PDF, DOC, DOCX (max <?php echo $maxFileMb; ?>MB)</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Passport Photo</label>
            <div class="file-input-wrap">
              <input type="file" name="passport_photo" accept=".jpg,.jpeg,.png,.gif" onchange="this.style.borderColor='#22C55E'">
            </div>
            <div class="file-limit-hint">Accepted: JPG, PNG, GIF (max <?php echo $maxFileMb; ?>MB)</div>
          </div>
        </div>
      </div>

      <!-- Supporting Documents -->
      <div class="form-card">
        <div class="section-header"><i class="fas fa-folder-open"></i>Supporting Documents <span style="font-weight:400;font-size:0.75rem;color:var(--text-muted);">(up to 5 files, <?php echo $maxFileMb; ?>MB each)</span></div>
        <div id="fileUploadContainer">
          <div class="file-row" data-index="0">
            <button type="button" class="remove-btn" onclick="removeFileRow(this)" title="Remove" style="display:none;"><i class="fas fa-times"></i></button>
            <div class="row" style="row-gap:12px;">
              <div class="col-md-7">
                <label class="form-label">File</label>
                <div class="file-input-wrap">
                  <input type="file" name="file_upload[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onchange="this.style.borderColor='#22C55E'">
                </div>
                <div class="file-limit-hint">Accepted: PDF, DOC, DOCX, JPG, PNG (max <?php echo $maxFileMb; ?>MB)</div>
              </div>
              <div class="col-md-5">
                <label class="form-label">Description</label>
                <input type="text" name="file_desc[]" class="form-control" placeholder="e.g. Transcript, Certificate, etc.">
              </div>
            </div>
          </div>
        </div>
        <button type="button" class="btn-add-file" id="addFileBtn" onclick="addFileRow()"><i class="fas fa-plus me-2"></i>Add Another File</button>
      </div>

      <!-- Submit -->
      <div class="text-center">
        <button type="submit" class="btn-submit"><i class="fas fa-paper-plane me-2"></i>Submit Application</button>
        <p style="color:var(--text-muted);font-size:0.8rem;margin-top:14px;">By submitting, you confirm that all information provided is accurate.</p>
      </div>

    </form>
  </div>

  <footer class="footer">
    <div class="footer-copy">&copy; <?php echo date('Y'); ?> HRGoTo HCM</div>
    <div class="footer-powered">Powered By Norgence</div>
  </footer>

<script>
let fileCount = 1;
function addFileRow() {
  if (fileCount >= 5) { alert('Maximum of 5 supporting files allowed.'); return; }
  const container = document.getElementById('fileUploadContainer');
  const div = document.createElement('div');
  div.className = 'file-row';
  div.dataset.index = fileCount;
  div.innerHTML = `
    <button type="button" class="remove-btn" onclick="removeFileRow(this)" title="Remove"><i class="fas fa-times"></i></button>
    <div class="row" style="row-gap:12px;">
      <div class="col-md-7">
        <label class="form-label">File</label>
        <div class="file-input-wrap">
          <input type="file" name="file_upload[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onchange="this.style.borderColor='#22C55E'">
        </div>
        <div class="file-limit-hint">Accepted: PDF, DOC, DOCX, JPG, PNG (max ${<?php echo $maxFileMb; ?>}MB)</div>
      </div>
      <div class="col-md-5">
        <label class="form-label">Description</label>
        <input type="text" name="file_desc[]" class="form-control" placeholder="e.g. Transcript, Certificate, etc.">
      </div>
    </div>`;
  container.appendChild(div);
  fileCount++;
  document.getElementById('addFileBtn').style.display = fileCount >= 5 ? 'none' : '';
}
function removeFileRow(btn) {
  const row = btn.closest('.file-row');
  row.remove();
  fileCount--;
  document.getElementById('addFileBtn').style.display = '';
}
function addEduRow() {
  const container = document.getElementById('eduContainer');
  const div = document.createElement('div');
  div.className = 'edu-row';
  div.style.cssText = 'background:#FAFBFC;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:14px;position:relative;';
  div.innerHTML = `
    <button type="button" style="position:absolute;top:8px;right:10px;background:none;border:none;color:#EF4444;font-size:1.1rem;cursor:pointer;padding:4px 8px;border-radius:6px;" onclick="this.closest('.edu-row').remove()" title="Remove"><i class="fas fa-times"></i></button>
    <div class="row" style="row-gap:12px;">
      <div class="col-md-6">
        <label class="form-label">Qualification</label>
        <select name="edu_qualification[]" class="form-select">
          <option value="">Select...</option>
          <option value="PhD / Doctorate">PhD / Doctorate</option>
          <option value="Masters Degree">Masters Degree</option>
          <option value="Bachelors Degree">Bachelors Degree</option>
          <option value="HND">HND</option>
          <option value="Diploma">Diploma</option>
          <option value="Certificate">Certificate</option>
          <option value="WASSCE / SSSCE">WASSCE / SSSCE</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Institution</label>
        <input type="text" name="edu_institution[]" class="form-control">
      </div>
      <div class="col-md-6">
        <label class="form-label">Field of Study</label>
        <input type="text" name="edu_field[]" class="form-control" placeholder="e.g. Computer Science">
      </div>
      <div class="col-md-2">
        <label class="form-label">Start Year</label>
        <input type="number" name="edu_start[]" class="form-control" min="1950" max="2099" placeholder="YYYY">
      </div>
      <div class="col-md-2">
        <label class="form-label">End Year</label>
        <input type="number" name="edu_end[]" class="form-control" min="1950" max="2099" placeholder="YYYY">
      </div>
      <div class="col-md-2">
        <label class="form-label">Grade</label>
        <input type="text" name="edu_grade[]" class="form-control" placeholder="e.g. First Class">
      </div>
    </div>`;
  container.appendChild(div);
}

function addExpRow() {
  const container = document.getElementById('expContainer');
  const div = document.createElement('div');
  div.className = 'exp-row';
  div.style.cssText = 'background:#FAFBFC;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:14px;position:relative;';
  div.innerHTML = `
    <button type="button" style="position:absolute;top:8px;right:10px;background:none;border:none;color:#EF4444;font-size:1.1rem;cursor:pointer;padding:4px 8px;border-radius:6px;" onclick="this.closest('.exp-row').remove()" title="Remove"><i class="fas fa-times"></i></button>
    <div class="row" style="row-gap:12px;">
      <div class="col-md-6">
        <label class="form-label">Role / Position</label>
        <input type="text" name="job_role[]" class="form-control" placeholder="e.g. Senior Accountant">
      </div>
      <div class="col-md-3">
        <label class="form-label">Start Year</label>
        <input type="number" name="job_start[]" class="form-control" min="1950" max="2099" placeholder="YYYY">
      </div>
      <div class="col-md-3">
        <label class="form-label">End Year</label>
        <input type="number" name="job_end[]" class="form-control" min="1950" max="2099" placeholder="YYYY">
      </div>
    </div>`;
  container.appendChild(div);
}

function validateForm() {
  const cv = document.querySelector('input[name="cv"]');
  if (!cv.files || cv.files.length === 0) { alert('Please upload your CV/Resume.'); return false; }
  if (cv.files[0].size > <?php echo $maxFileMb * 1024 * 1024; ?>) { alert('CV exceeds <?php echo $maxFileMb; ?>MB limit.'); return false; }
  const photo = document.querySelector('input[name="passport_photo"]');
  if (photo.files && photo.files[0] && photo.files[0].size > <?php echo $maxFileMb * 1024 * 1024; ?>) { alert('Photo exceeds <?php echo $maxFileMb; ?>MB limit.'); return false; }
  const fileInputs = document.querySelectorAll('input[name="file_upload[]"]');
  for (const fi of fileInputs) {
    if (fi.files && fi.files[0] && fi.files[0].size > <?php echo $maxFileMb * 1024 * 1024; ?>) { alert('One or more supporting files exceed <?php echo $maxFileMb; ?>MB limit.'); return false; }
  }
  return true;
}
</script>
</body>
</html>
