<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
?>
<style>
    /* Forces the preview image to maintain aspect ratio and center on the face */
    .portrait-preview-container img#portrait-preview-display {
        object-fit: cover !important;
        object-position: center top !important; 
        width: 100% !important;
        height: 100% !important;
    }
</style>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
        <div class="container-fluid p-0">
            <button type="button" id="sidebarCollapseBtn" class="btn btn-outline-secondary btn-sm rounded-circle me-3">
                <i class="fas fa-bars"></i>
            </button>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb m-0 bg-transparent p-0">
                    <li class="breadcrumb-item text-dark"><a href="<?php echo $appUrl; ?>/dashboard" class="text-decoration-none fw-semibold">Dashboard</a></li>
                    <li class="breadcrumb-item text-dark"><a href="<?php echo $appUrl; ?>/staff" class="text-decoration-none fw-semibold">Staff Records</a></li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Employee Onboarding Wizard</li>
                </ol>
            </nav>
            <?php require __DIR__ . '/../dashboard/layouts/navbar_user.php'; ?>
        </div>
    </nav>

    <div class="container-fluid p-4" style="max-width: 1100px;">

        <!-- Tab Navigation: Single vs Bulk CSV -->
        <ul class="nav nav-pills nav-justified mb-4 gap-2" id="onboardingTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold px-4 py-2" id="single-tab" data-bs-toggle="pill" data-bs-target="#single-panel" type="button" role="tab" aria-controls="single-panel" aria-selected="true">
                    <i class="fas fa-user-plus me-2"></i> Single Employee Onboarding
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-4 py-2" id="csv-tab" data-bs-toggle="pill" data-bs-target="#csv-panel" type="button" role="tab" aria-controls="csv-panel" aria-selected="false">
                    <i class="fas fa-file-csv me-2"></i> Bulk CSV Import
                </button>
            </li>
        </ul>

        <!-- Single Onboarding Tab -->
        <div class="tab-pane fade show active" id="single-panel" role="tabpanel" aria-labelledby="single-tab">
        <div class="card border-0 shadow-sm rounded-lg p-4 mb-4 bg-white">
            <div class="d-flex justify-content-between position-relative wizard-progress-track">
                <div class="wizard-step-node active text-center" id="node-step-1">
                    <div class="node-circle mx-auto mb-2"><i class="fas fa-user-shield"></i></div>
                    <span class="fw-bold text-dark text-uppercase tracking-wider font-monospace">1. User Account</span>
                </div>
                <div class="wizard-step-node text-center" id="node-step-2">
                    <div class="node-circle mx-auto mb-2"><i class="fas fa-id-card"></i></div>
                    <span class="fw-bold text-secondary text-uppercase tracking-wider font-monospace">2. Personal Bio</span>
                </div>
                <div class="wizard-step-node text-center" id="node-step-3">
                    <div class="node-circle mx-auto mb-2"><i class="fas fa-user-graduate"></i></div>
                    <span class="fw-bold text-secondary text-uppercase tracking-wider font-monospace">3. Education Background</span>
                </div>
                <div class="wizard-step-node text-center" id="node-step-4">
                    <div class="node-circle mx-auto mb-2"><i class="fas fa-briefcase"></i></div>
                    <span class="fw-bold text-secondary text-uppercase tracking-wider font-monospace">4. History & Dossier</span>
                </div>
            </div>
        </div>

        <div id="wizard-feedback-alert" class="alert d-none shadow-sm fw-bold fs-6" role="alert"></div>

        <form id="onboarding-multi-step-form" enctype="multipart/form-data" novalidate>
            <input type="hidden" id="wizard-csrf-token" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            
            <div class="wizard-content-card shadow-sm border-0 card p-4 mb-4 bg-white active" id="panel-step-1">
                <h4 class="fw-bold text-dark mb-1 border-bottom pb-2"><i class="fas fa-key text-primary me-2"></i>System Credentials</h4>
                <p class="text-dark fw-medium mb-4">Provide details for staff credential creation. If the phone number provided is correct, they will get an SMS alert.</p>
                
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Surname / Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="surname" class="form-control accessibility-input input-unmuted" placeholder="e.g., Azaabi" required pattern="^[A-Za-z'\s-]{2,50}$">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Other Names / First Names <span class="text-danger">*</span></label>
                        <input type="text" name="other_names" class="form-control accessibility-input input-unmuted" placeholder="e.g., Cletus" required pattern="^[A-Za-z'\s-]{2,50}$">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control accessibility-input input-unmuted" placeholder="e.g., cazaabi" required pattern="^[A-Za-z'.\s-]{3,30}$">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Institution Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control accessibility-input input-unmuted" placeholder="employee@company.com" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Access Level (Role) <span class="text-danger">*</span></label>
                        <select name="role_id" class="form-select accessibility-input input-unmuted" required>
                            <?php foreach ($roles ?? [] as $role): ?>
                                <option value="<?php echo $role['id']; ?>" <?php echo ($role['role_name'] === 'Staff') ? 'selected' : ''; ?>>
                                    <?php echo \App\Helpers\Security::escape($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="wizard-content-card shadow-sm border-0 card p-4 mb-4 bg-white d-none" id="panel-step-2">
                <h4 class="fw-bold text-dark mb-1 border-bottom pb-2"><i class="fas fa-id-card-clip text-primary me-2"></i>Employee Personal Details</h4>
                <p class="text-dark fw-medium mb-4">Employee Bio data for tracking institutional progression.</p>
                
                <div class="row align-items-center mb-4 bg-light p-3 rounded mx-1 border">
                    <div class="col-12 col-sm-3 col-md-2 text-center mb-3 mb-sm-0">
                        <div class="portrait-preview-container mx-auto shadow-sm border bg-white d-flex align-items-center justify-content-center overflow-hidden">
                            <img id="portrait-preview-display" src="" class="d-none w-100 h-100 object-fit-cover" alt="Staff Portrait">
                            <i id="portrait-preview-placeholder" class="fas fa-user fa-3x text-secondary"></i>
                        </div>
                    </div>
                    <div class="col-12 col-sm-9 col-md-10">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider m-0 mb-1">Staff Passport Photo <span class="text-danger">*</span></label>
                        <span class="text-muted d-block small mb-2 fw-semibold">Accepted Extensions: JPEG, PNG formats (Max. size 2MB)</span>
                        <input type="file" id="avatar_photo_input" name="avatar_photo" class="form-control accessibility-input" accept="image/jpeg, image/png" required>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Staff ID Card Number <span class="text-danger">*</span></label>
                        <input type="text" name="staff_id_card" class="form-control font-monospace fw-bold accessibility-input input-unmuted" placeholder="e.g., HRG-EST-2026-089" required>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Staff Department <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select accessibility-input input-unmuted" required>
                            <option value="" selected disabled>-- Choose Department --</option>
                            <?php foreach ($departments ?? [] as $dept): ?>
                                <option value="<?php echo \App\Helpers\Security::escape($dept['dept_id']); ?>"><?php echo mb_strtoupper(\App\Helpers\Security::escape($dept['dept_name'])); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Designation<span class="text-danger">*</span></label>
                        <select name="designation_id" class="form-select accessibility-input input-unmuted" required>
                            <option value="" selected disabled>-- Choose Designation --</option>
                            <?php foreach ($designations ?? [] as $desig): ?>
                                <option value="<?php echo $desig['id']; ?>"><?php echo \App\Helpers\Security::escape($desig['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select accessibility-input input-unmuted" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control accessibility-input input-unmuted" id="wizard_dob_input"required>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="date_joined" class="form-control accessibility-input input-unmuted" value="2026-01-01" required>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Engagement Mode <span class="text-danger">*</span></label>
                        <select name="employment_status" class="form-select accessibility-input input-unmuted" required>
                            <option value="Permanent">Permanent</option>
                            <option value="Contract">Contract</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Phone Number 1 (Primary) <span class="text-danger">*</span></label>
                        <input type="tel" name="phone_one" class="form-control accessibility-input input-unmuted" placeholder="e.g., 0244112233" required pattern="^\+?[0-9\s\-]{10,16}$">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Phone Number 2 (Secondary)</label>
                        <input type="tel" name="phone_two" class="form-control accessibility-input input-unmuted" placeholder="e.g., 0504433221" pattern="^\+?[0-9\s\-]{10,16}$">
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Home Town <span class="text-danger">*</span></label>
                        <input type="text" name="hometown" class="form-control accessibility-input input-unmuted" placeholder="e.g., Soe (Bolgatanga)" required>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Region of Origin <span class="text-danger">*</span></label>
                        <select name="region" class="form-select accessibility-input input-unmuted" required>
                            <option value="Upper East" selected>Upper East Region</option>
                            <option value="Upper West">Upper West Region</option>
                            <option value="Northern">Northern Region</option>
                            <option value="Savannah">Savannah Region</option>
                            <option value="North East">North East Region</option>
                            <option value="Ashanti">Ashanti Region</option>
                            <option value="Greater Accra">Greater Accra Region</option>
                            <option value="Western">Western Region</option>
                            <option value="Eastern">Eastern Region</option>
                            <option value="Central">Central Region</option>
                            <option value="Volta">Volta Region</option>
                            <option value="Bono">Bono Region</option>
                            <option value="Bono East">Bono East Region</option>
                            <option value="Ahafo">Ahafo Region</option>
                            <option value="Oti">Oti Region</option>
                            <option value="Western North">Western North Region</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-4 col-md-4">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Nationality <span class="text-danger">*</span></label>
                        <input type="text" name="nationality" class="form-control accessibility-input input-unmuted" value="Ghanaian" required>
                    </div>
                    <div class="col-12 col-sm-4 col-md-4">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Religion <span class="text-danger">*</span></label>
                        <input type="text" name="religion" class="form-control accessibility-input input-unmuted" placeholder="e.g., Christian / Muslim / Traditional" required>
                    </div>
                    <div class="col-12 col-sm-4 col-md-4">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Marital Status <span class="text-danger">*</span></label>
                        <select name="marital_status" class="form-select accessibility-input input-unmuted" required>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Widowed">Widowed</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-4 col-md-4">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Number of Children <span class="text-danger">*</span></label>
                        <input type="number" name="number_of_children" class="form-control accessibility-input input-unmuted" min="0" max="20" value="0" required>
                    </div>
					<div class="col-12 mt-3">
					<label class="form-label fw-bold text-dark text-uppercase tracking-wider">
						Staff Brief Biography / Background Notes 
						<span class="text-muted small">(Optional)</span>
					</label>
					<textarea 
						name="biography" 
						id="wizard_biography_input" 
						class="form-control accessibility-input input-unmuted" 
						rows="4" 
						placeholder="Provide a brief summary of the staff's corporate background, core competencies, or introductory professional notes..."
						style="resize: vertical; min-height: 100px;"></textarea>
					<div class="invalid-feedback">Please ensure biography content remains clean and correctly structured.</div>
				</div>
                </div>
            </div>

            <div class="wizard-content-card shadow-sm border-0 card p-4 mb-4 bg-white d-none" id="panel-step-3">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h4 class="fw-bold text-dark m-0"><i class="fas fa-user-graduate text-primary me-2"></i>Academic Background</h4>
                    <button type="button" id="add-education-row" class="btn btn-primary btn-sm fw-bold px-3">
                        <i class="fas fa-plus me-1"></i> Add Record (Max 2)
                    </button>
                </div>
                <p class="text-dark fw-medium mb-4">Provide academic details of the employee. You can add up to 3, latest first.</p>
                
                <div id="education-records-container">
                    <div class="education-row border rounded p-3 mb-3 bg-light position-relative">
                        <span class="badge bg-dark font-monospace text-uppercase mb-2">Compulsory</span>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Institution Attended <span class="text-danger">*</span></label>
                                <input type="text" name="edu_institution[]" class="form-control accessibility-input input-unmuted" required placeholder="e.g., Bolgatanga Technical University">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Certificate Obtained <span class="text-danger">*</span></label>
                                <input type="text" name="edu_certificate[]" class="form-control accessibility-input input-unmuted" required placeholder="e.g., HND in Accountancy">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Year From <span class="text-danger">*</span></label>
                                <input type="number" name="edu_from[]" class="form-control accessibility-input input-unmuted font-monospace" min="1950" max="2030" placeholder="YYYY" required>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Year To <span class="text-danger">*</span></label>
                                <input type="number" name="edu_to[]" class="form-control accessibility-input input-unmuted font-monospace" min="1950" max="2030" placeholder="YYYY" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Upload Certificate Copy (PDF/Image) <span class="text-danger">*</span></label>
                                <input type="file" name="edu_certificate_file[]" class="form-control accessibility-input" accept="application/pdf, image/jpeg, image/png" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wizard-content-card shadow-sm border-0 card p-4 mb-4 bg-white d-none" id="panel-step-4">
                <h4 class="fw-bold text-dark mb-1 border-bottom pb-2"><i class="fas fa-briefcase text-primary me-2"></i>Work Experience</h4>
                <p class="text-dark fw-medium mb-4">Prior to applying to join us, where did you work and what were your duties.</p>
                
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Institution / Employer Name</label>
                        <input type="text" name="prev_institution" class="form-control accessibility-input input-unmuted" placeholder="e.g., Zuarungu Senior High School">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Address</label>
                        <input type="text" name="prev_address" class="form-control accessibility-input input-unmuted" placeholder="e.g., P.O. Box 45, Zuarungu">
                    </div>
                    <div class="col-12 col-sm-4 col-md-4">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Last Position / Job Title</label>
                        <input type="text" name="prev_title" class="form-control accessibility-input input-unmuted" placeholder="e.g., Senior Accounting Assistant">
                    </div>
                    <div class="col-12 col-sm-4 col-md-4">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Worked From (Date)</label>
                        <input type="date" name="prev_from" class="form-control accessibility-input input-unmuted">
                    </div>
                    <div class="col-12 col-sm-4 col-md-4">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">To (Date)</label>
                        <input type="date" name="prev_to" class="form-control accessibility-input input-unmuted">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Duties Performed</label>
                        <textarea name="prev_duties" class="form-control accessibility-input input-unmuted" rows="3" placeholder="Elaborate clearly on tasks managed, reports compiled, or student metrics tracked..."></textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between bg-transparent p-0">
                <button type="button" class="btn btn-secondary btn-lg px-5 py-2 fw-bold shadow-sm d-none" id="btn-wizard-prev">
                    <i class="fas fa-arrow-left me-2"></i> Back Step
                </button>
                <button type="button" class="btn btn-primary btn-lg px-5 py-2 fw-bold shadow-sm ms-auto" id="btn-wizard-next">
                    Next Step <i class="fas fa-arrow-right ms-2"></i>
                </button>
                <button type="submit" class="btn btn-success btn-lg px-5 py-2 fw-bold shadow-sm ms-auto d-none" id="btn-wizard-submit">
                    <span id="submit-btn-label"><i class="fas fa-cloud-arrow-up me-2"></i> Commit & Finalize Onboarding</span>
                    <span id="submit-btn-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </form>
        </div><!-- /single-panel -->

        <!-- Bulk CSV Import Tab Panel -->
        <div class="tab-pane fade" id="csv-panel" role="tabpanel" aria-labelledby="csv-tab">
            <div class="card border-0 shadow-sm rounded-lg p-4 bg-white">
                <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
                    <div>
                        <h4 class="fw-bold text-dark m-0"><i class="fas fa-file-csv text-primary me-2"></i>Bulk CSV Onboarding</h4>
                        <p class="text-muted small m-0 mt-1">Import multiple employee records at once using a structured CSV file.</p>
                    </div>
                    <a href="<?php echo $appUrl; ?>/staff/csv-template" class="btn btn-outline-dark btn-sm fw-semibold">
                        <i class="fas fa-download me-1"></i> Download Template
                    </a>
                </div>

                <div class="row g-3 align-items-end mb-4">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold text-dark text-uppercase tracking-wider small">Upload CSV File</label>
                        <input type="file" id="csvOnboardFile" class="form-control" accept=".csv">
                    </div>
                    <div class="col-12 col-md-6 d-flex gap-2">
                        <button type="button" id="csvUploadBtn" class="btn btn-primary fw-semibold">
                            <i class="fas fa-file-upload me-1"></i> Upload CSV
                        </button>
                        <button type="button" id="csvProcessBtn" class="btn btn-dark fw-semibold" disabled>
                            <i class="fas fa-play me-1"></i> Start Processing
                        </button>
                    </div>
                </div>

                <div class="p-3 bg-light rounded border">
                    <div class="progress" style="height: 20px;">
                        <div id="csvProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width:0%">0%</div>
                    </div>
                    <small id="csvProgressText" class="text-muted d-block mt-2">No batch selected. Upload a CSV file to begin.</small>
                    <small id="csvProgressError" class="text-danger d-block mt-1"></small>
                </div>
            </div>
        </div><!-- /csv-panel -->
    </div><!-- /container -->
</div><!-- /content -->

<style>
/* Accessibility Engine: Clean 1.5px baseline styling borders */
.input-unmuted { color: #000000 !important; font-weight: 600 !important; font-size: 1.05rem !important; }
.form-control::placeholder { color: #5a6268 !important; font-weight: 400 !important; }
.accessibility-input { border: 1.5px solid #495057 !important; padding: 0.6rem !important; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out; }
.accessibility-input:focus { border-color: #0275d8 !important; box-shadow: 0 0 0 4px rgba(2, 117, 216, 0.35) !important; outline: none !important; }

/* Structural Step Tracker Component Styles */
.node-circle { width: 50px; height: 50px; border-radius: 50%; background: #ced4da; color: #343a40; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; transition: all 0.3s; border: 3px solid #fff; box-shadow: 0 0 0 2px #ced4da; }
.wizard-step-node.active .node-circle { background: #0275d8; color: #fff; box-shadow: 0 0 0 3px #0275d8; }
.wizard-progress-track::before { content: ''; position: absolute; top: 25px; left: 0; width: 100%; height: 4px; background: #ced4da; z-index: 0; }
.wizard-step-node { z-index: 1; width: 25%; position: relative; }

/* Staff Portrait Circular Layout Container styling */
.portrait-preview-container { width: 100px; height: 100px; border-radius: 50%; background-color: #e9ecef; }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let currentStep = 1;
    const totalSteps = 4;
    let educationRowCount = 0;

    const btnPrev = document.getElementById("btn-wizard-prev");
    const btnNext = document.getElementById("btn-wizard-next");
    const btnSubmit = document.getElementById("btn-wizard-submit");
    const formElement = document.getElementById("onboarding-multi-step-form");
    const feedbackAlert = document.getElementById("wizard-feedback-alert");
    const addEduRowBtn = document.getElementById("add-education-row");
    const eduContainer = document.getElementById("education-records-container");
    const appBaseUrl = "<?php echo $appUrl; ?>";

    // Portrait File Upload Event Preview Engine Handler
    const avatarInput = document.getElementById("avatar_photo_input");
    const portraitDisplay = document.getElementById("portrait-preview-display");
    const portraitPlaceholder = document.getElementById("portrait-preview-placeholder");

    // Enforce Date of Birth restrictions to exactly 18 years ago today
    const dobField = document.getElementById('wizard_dob_input');
    if (dobField) {
        const today = new Date();
        const maxYear = today.getFullYear() - 18;
        const maxMonth = String(today.getMonth() + 1).padStart(2, '0');
        const maxDay = String(today.getDate()).padStart(2, '0');
        
        dobField.max = `${maxYear}-${maxMonth}-${maxDay}`;
    }

    avatarInput.addEventListener("change", function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                portraitDisplay.src = e.target.result;
                portraitDisplay.classList.remove("d-none");
                portraitPlaceholder.classList.add("d-none");
            }
            reader.readAsDataURL(file);
        } else {
            portraitDisplay.src = "";
            portraitDisplay.classList.add("d-none");
            portraitPlaceholder.classList.remove("d-none");
        }
    });

    // Dynamic Academic Qualifications Array Append Handler Block
    addEduRowBtn.addEventListener("click", function() {
        if (educationRowCount >= 2) {
            alert("Maximum allocation reached. You can attach up to 2 additional qualification blocks.");
            return;
        }
        educationRowCount++;
        
        const rowDiv = document.createElement("div");
        rowDiv.className = "education-row border rounded p-3 mb-3 bg-white position-relative shadow-sm border-primary";
        rowDiv.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-primary font-monospace text-uppercase">Additional Credential Block #${educationRowCount}</span>
                <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2 remove-edu-row" title="Drop row parameters"><i class="fas fa-trash-can"></i></button>
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Institution Attended <span class="text-danger">*</span></label>
                    <input type="text" name="edu_institution[]" class="form-control accessibility-input input-unmuted" required placeholder="e.g., UDS, Tamale Campus">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Certificate Obtained <span class="text-danger">*</span></label>
                    <input type="text" name="edu_certificate[]" class="form-control accessibility-input input-unmuted" required placeholder="e.g., M.Tech in Accounting Informatics">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Year From <span class="text-danger">*</span></label>
                    <input type="number" name="edu_from[]" class="form-control accessibility-input input-unmuted font-monospace" min="1950" max="2030" placeholder="YYYY" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Year To <span class="text-danger">*</span></label>
                    <input type="number" name="edu_to[]" class="form-control accessibility-input input-unmuted font-monospace" min="1950" max="2030" placeholder="YYYY" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold text-dark text-uppercase tracking-wider">Upload Certificate Copy (PDF/Image) <span class="text-danger">*</span></label>
                    <input type="file" name="edu_certificate_file[]" class="form-control accessibility-input" accept="application/pdf, image/jpeg, image/png" required>
                </div>
            </div>
        `;
        eduContainer.appendChild(rowDiv);
    });

    eduContainer.addEventListener("click", function(e) {
        if (e.target.closest(".remove-edu-row")) {
            e.target.closest(".education-row").remove();
            educationRowCount--;
        }
    });

    function renderActiveStep() {
        for (let i = 1; i <= totalSteps; i++) {
            const panel = document.getElementById(`panel-step-${i}`);
            const node = document.getElementById(`node-step-${i}`);
            
            if (i === currentStep) {
                panel.classList.remove("d-none");
                node.classList.add("active");
                node.querySelector("span").classList.replace("text-secondary", "text-dark");
            } else {
                panel.classList.add("d-none");
                node.classList.remove("active");
                node.querySelector("span").classList.replace("text-dark", "text-secondary");
            }
        }

        if (currentStep === 1) {
            btnPrev.classList.add("d-none");
        } else {
            btnPrev.classList.remove("d-none");
        }

        if (currentStep === totalSteps) {
            btnNext.classList.add("d-none");
            btnSubmit.classList.remove("d-none");
        } else {
            btnNext.classList.remove("d-none");
            btnSubmit.classList.add("d-none");
        }
    }

    btnNext.addEventListener("click", function() {
        const activePanel = document.getElementById(`panel-step-${currentStep}`);
        const activeInputs = activePanel.querySelectorAll("input, select, textarea");
        let stepValid = true;

        activeInputs.forEach(input => {
            if (!input.checkValidity()) {
                input.classList.add("is-invalid");
                stepValid = false;
            } else {
                input.classList.remove("is-invalid");
            }
        });

        if (!stepValid) {
            feedbackAlert.className = "alert alert-danger shadow-sm mb-3 fw-bold";
            feedbackAlert.innerHTML = "<i class='fas fa-triangle-exclamation me-2'></i>Please complete all mandatory step parameters correctly before moving forward.";
            feedbackAlert.classList.remove("d-none");
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        feedbackAlert.classList.add("d-none");
        if (currentStep < totalSteps) {
            currentStep++;
            renderActiveStep();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    btnPrev.addEventListener("click", function() {
        if (currentStep > 1) {
            currentStep--;
            renderActiveStep();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    formElement.addEventListener("submit", function(e) {
        e.preventDefault();
        
        feedbackAlert.classList.add("d-none");
        document.getElementById("submit-btn-label").classList.add("d-none");
        document.getElementById("submit-btn-spinner").classList.remove("d-none");
        btnSubmit.setAttribute("disabled", "true");

        let formData = new FormData(this);

        // Safely extract token and pass it inside the multipart context directly
        const tokenField = document.getElementById("wizard-csrf-token");
        if (tokenField) {
            formData.append('csrf_token', tokenField.value);
        }

        // Fetch execution without manual header boundaries modification
        fetch(`${appBaseUrl}/onboard/submit`, {
			method: "POST",
			body: formData // Ensure your multi-step fields are bound here
		})
		.then(async response => {
			const contentType = response.headers.get("content-type");
			
			// Catch non-JSON outputs (like HTML syntax/database error stack traces)
			if (!contentType || !contentType.includes("application/json")) {
				const rawText = await response.text();
				throw new Error(`Server returned non-JSON payload: ${rawText}`);
			}
			
			return response.json();
		})
		.then(result => {
			if (result.success) {
				alert("Success: " + result.message);
				window.location.href = `${appBaseUrl}/dashboard`;
			} else {
				// Force the hidden failure message straight onto the screen
				alert("Validation/Server Failure: " + result.message);
			}
		})
		.catch(error => {
			// 1. Alert the error so you can read it clearly
			console.error("Critical Failure Trace:", error);
			alert("Application Error encountered: " + error.message);
			
			// 2. --- THE RESET FIX ---
			// Manually force the button to restore its state right here inside the error catch zone
			const submitBtn = document.getElementById('wizard_submit_btn');
			const spinner = document.getElementById('wizard_submit_spinner'); // Match your HTML ID
			
			if (submitBtn) submitBtn.disabled = false;
			if (spinner) spinner.classList.add('d-none');
        });
    });

    // === CSV Bulk Import Logic ===
    const csvUploadBtn = document.getElementById('csvUploadBtn');
    const csvProcessBtn = document.getElementById('csvProcessBtn');
    const csvFileInput = document.getElementById('csvOnboardFile');
    const csvProgressBar = document.getElementById('csvProgressBar');
    const csvProgressText = document.getElementById('csvProgressText');
    const csvProgressError = document.getElementById('csvProgressError');
    let currentCsvBatch = localStorage.getItem('hrgoto_csv_batch') || '';
    let csvPollTimer = null;

    function endpoint(path) {
        const base = String(appBaseUrl || '').replace(/\/+$/, '');
        return `${base}/index.php?url=${path.replace(/^\//, '')}`;
    }

    function updateCsvProgress(completed, total, percentage, status, message, errorMessage) {
        const pct = Math.max(0, Math.min(100, Number(percentage || 0)));
        csvProgressBar.style.width = `${pct}%`;
        csvProgressBar.textContent = `${pct}%`;
        csvProgressText.textContent = `${status || 'Queued'}: ${completed || 0}/${total || 0}. ${message || ''}`.trim();
        csvProgressError.textContent = errorMessage || '';
    }

    async function pollCsvStatus() {
        if (!currentCsvBatch) return;
        try {
            const response = await fetch(`${endpoint('staff/csv-status')}&batch_id=${encodeURIComponent(currentCsvBatch)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (!data.success) return;
            updateCsvProgress(data.completed, data.total, data.percentage, data.status, data.progress_message, data.error_message);
            if (data.status === 'Completed') {
                clearInterval(csvPollTimer);
                csvPollTimer = null;
                csvProcessBtn.disabled = true;
                setTimeout(() => { window.location.reload(); }, 900);
            }
        } catch (e) {
            csvProgressError.textContent = 'Status polling failed. Please refresh.';
        }
    }

    if (csvUploadBtn) {
        csvUploadBtn.addEventListener('click', async function () {
            csvProgressError.textContent = '';
            if (!csvFileInput.files.length) {
                csvProgressError.textContent = 'Select a CSV file first.';
                return;
            }
            const fd = new FormData();
            const tokenField = document.getElementById("wizard-csrf-token");
            fd.append('csrf_token', tokenField ? tokenField.value : '');
            fd.append('staff_csv', csvFileInput.files[0]);
            csvUploadBtn.disabled = true;
            try {
                const response = await fetch(endpoint('staff/csv-upload'), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': tokenField ? tokenField.value : '', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd
                });
                const data = await response.json();
                if (!data.success) {
                    csvProgressError.textContent = data.message || 'Upload failed.';
                    return;
                }
                currentCsvBatch = data.batch_id;
                localStorage.setItem('hrgoto_csv_batch', currentCsvBatch);
                csvProcessBtn.disabled = false;
                updateCsvProgress(0, data.total_rows || 0, 0, 'Queued', data.message || 'CSV uploaded', '');
            } catch (e) {
                csvProgressError.textContent = 'Upload failed due to network/server error.';
            } finally {
                csvUploadBtn.disabled = false;
            }
        });

        csvProcessBtn.addEventListener('click', async function () {
            if (!currentCsvBatch) {
                csvProgressError.textContent = 'No batch found. Upload CSV first.';
                return;
            }
            csvProcessBtn.disabled = true;
            csvProgressError.textContent = '';
            try {
                const payload = new FormData();
                const tokenField = document.getElementById("wizard-csrf-token");
                payload.append('csrf_token', tokenField ? tokenField.value : '');
                payload.append('batch_id', currentCsvBatch);
                const response = await fetch(endpoint('staff/csv-process'), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': tokenField ? tokenField.value : '', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: payload
                });
                const data = await response.json();
                if (!data.success) {
                    csvProgressError.textContent = data.message || 'Processing failed.';
                    csvProcessBtn.disabled = false;
                    return;
                }
                updateCsvProgress(data.completed_rows, data.total_rows, Math.floor(((data.completed_rows || 0) / Math.max(1, data.total_rows || 1)) * 100), data.status, 'Processing in progress', (data.errors || []).slice(-1)[0] || '');
                if (!csvPollTimer) {
                    csvPollTimer = setInterval(pollCsvStatus, 1500);
                }
            } catch (e) {
                csvProgressError.textContent = 'Processing request failed.';
                csvProcessBtn.disabled = false;
            }
        });

        if (currentCsvBatch) {
            csvProcessBtn.disabled = false;
            pollCsvStatus();
            csvPollTimer = setInterval(pollCsvStatus, 2000);
        }
    }
});
</script>