<?php
// Variables expected by MVC runtime engine scope: 
// $app (array), $educationEntries (array), $employmentEntries (array), $uploadedFiles (array), $id (int), $csrf (string)

$statusColors = [
    'pending'      => 'warning',
    'reviewing'    => 'info',
    'shortlisted'  => 'primary',
    'interviewed'  => 'purple',
    'rejected'     => 'danger',
    'hired'        => 'success'
];

if (!function_exists('displayVal')) {
    function displayVal($val) {
        return (!empty($val) || $val === '0' || $val === 0) ? htmlspecialchars((string)$val) : '<span style="color:#aaa">N/A</span>';
    }
}
?>
<style>
	body {
        background-color: #d7dff7 !important;
    }
    .container-fluid .card {
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08), 0 2px 6px rgba(0, 0, 0, 0.06) !important;
        border: 1px solid rgba(0, 0, 0, 0.03) !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="container-fluid py-4" style="padding-left: 20px; margin-left: 10px; width: calc(100% - 270px); max-width: none;">
    
    <div style="display: grid; grid-template-columns: 3fr 1.2fr; gap: 24px; align-items: flex-start; width: 100%; margin: 0; padding: 10px 0;">
        
        <div>
            <div class="card shadow-sm" style="margin-bottom:20px;">
                <div class="card-body">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:15px">
                        <div>
							<p style="font-size:13px; color:var(--gray); margin:5px 0 0 0;">
                                <i class="bi bi-hash"></i> Ref: <strong><?= htmlspecialchars($app['reference_number'] ?? 'N/A') ?></strong> · <i class="bi bi-calendar-check" style="margin-left: 5px;"></i> Submitted <?= !empty($app['submitted_at']) ? date('M d, Y \a\t g:i A', strtotime($app['submitted_at'])) : 'Unknown Date' ?>
                            </p>
							
                            <h2 style="font-size:24px; color:var(--dark); margin:0 0 5px 0; font-weight:700;">
                                <i class="bi bi-person-badge text-primary" style="margin-right: 8px;"></i>
                                <?= ($app['title'] ? htmlspecialchars($app['title']).' ' : '') . htmlspecialchars(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? '')) ?>
                            </h2>
							
                            <p style="color:var(--primary); font-weight:600; margin:0 0 5px 0; font-size:15px;">
                                <i class="bi bi-briefcase" style="margin-right: 5px;"></i><?= htmlspecialchars($app['job_title'] ?? 'N/A') ?> · <span class="text-muted"><?= htmlspecialchars($app['department'] ?? 'N/A') ?></span>
                            </p>
                            
                        </div>
                        <span class="badge badge-<?= $statusColors[$app['status'] ?? 'pending'] ?>" style="font-size:14px; padding:8px 18px; border-radius: 20px; font-weight: 600;">
                            <?= ucfirst(htmlspecialchars($app['status'] ?? 'pending')) ?>
                        </span>
						<div class="pull-right">
							<a href="/recruitment/compile-report?id=<?= (int)$app['id'] ?>" class="btn btn-dark btn-md d-inline-flex align-items-center shadow-sm" style="font-weight:600; border-radius:6px; padding:10px 20px; gap:8px;" target="_blank">
								<i class="fas fa-file-pdf" style="font-size:16px; color:#e74c3c;"></i>
								<span>Download Full Application</span>
							</a>
						</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm" style="margin-bottom:20px; border:0;">
                <div class="card-header bg-dark text-white py-3 d-flex align-items-center">
                    <h3 style="margin:0; font-size:15px; font-weight:600; text-uppercase; tracking-wider;"><i class="bi bi-card-text" style="margin-right: 8px;"></i> Personal Data</h3>
                </div>
                <div class="card-body">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px 24px;">
                        <?php
                        $personalFields = [
                            'Title'               => $app['title'] ?? null,
                            'Surname'             => $app['first_name'] ?? null,
                            'Other Names'         => $app['last_name'] ?? null,
                            'Email'               => $app['email'] ?? null,
                            'Cell Phone(s)'       => $app['phone'] ?? null,
                            'Postal Address'      => $app['address'] ?? null,
                            'Date of Birth'       => !empty($app['date_of_birth']) ? date('M d, Y', strtotime($app['date_of_birth'])) : null,
                            'Gender'              => !empty($app['gender']) ? ucfirst(str_replace('_',' ',$app['gender'])) : null,
                            'Place of Birth'      => $app['place_of_birth'] ?? null,
                            'Home Town'           => $app['home_town'] ?? null,
                            'Region'              => $app['region'] ?? null,
                            'Nationality'         => $app['nationality'] ?? null,
                            'Religion'            => $app['religion'] ?? null,
                            'Marital Status'      => !empty($app['marital_status']) ? ucfirst($app['marital_status']) : null,
                            'Spouse Name'         => $app['spouse_name'] ?? null,
                            'Spouse Address'      => $app['spouse_address'] ?? null,
                            'GRA PIN'             => $app['gra_pin'] ?? null,
                            'Ghana Card Number'   => $app['ghana_card_number'] ?? null,
                        ];
                        foreach ($personalFields as $label => $value):
                            if ($value === null || $value === '') continue;
                        ?>
                        <div style="border-bottom: 1px dashed #f1f1f1; padding-bottom: 6px;">
                            <div style="font-size:12px; color:var(--gray); margin-bottom:3px; font-weight: 600; text-uppercase; letter-spacing: 0.5px;"><?= $label ?></div>
                            <div style="font-weight:500; color:#333;"><?= nl2br(htmlspecialchars((string)$value)) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($app['children_details'])): ?>
                    <div style="margin-top:20px; background: #fdfdfd; padding: 12px; border-left: 3px solid #6c757d; border-radius: 4px;">
                        <div style="font-size:12px; color:var(--gray); margin-bottom:4px; font-weight: 600;"><i class="bi bi-people"></i> Children (Names & Dates of Birth)</div>
                        <div style="font-size:13px; white-space:pre-line; color:#444; font-weight: 500;"><?= htmlspecialchars($app['children_details']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm" style="margin-bottom:20px; border:0;">
                <div class="card-header bg-dark text-white py-3 d-flex align-items-center">
                    <h3 style="margin:0; font-size:15px; font-weight:600; text-uppercase; tracking-wider;"><i class="bi bi-mortarboard" style="margin-right: 8px;"></i> Educational Qualification</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($educationEntries)): ?>
                    <div class="table-responsive" style="margin-bottom:20px;">
                        <table id="applicantEduTable" class="table table-hover border-0 align-middle" style="font-size:13px; width:100%">
                            <thead class="thead-light">
                                <tr>
                                    <th style="font-weight: 600;"><i class="bi bi-building"></i> Institution Attended</th>
                                    <th style="font-weight: 600;"><i class="bi bi-patch-check"></i> Certificate</th>
                                    <th style="font-weight: 600;" class="text-center">From</th>
                                    <th style="font-weight: 600;" class="text-center">To</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($educationEntries as $edu): ?>
                                <tr>
                                    <td style="font-weight:600; color:#222;"><?= htmlspecialchars($edu['institution'] ?? 'N/A') ?></td>
                                    <td><span class="badge badge-light border text-dark" style="font-size: 12px;"><?= htmlspecialchars($edu['certificate'] ?? 'N/A') ?></span></td>
                                    <td class="text-center text-muted"><?= displayVal($edu['from_year'] ?? '') ?></td>
                                    <td class="text-center text-muted"><?= displayVal($edu['to_year'] ?? '') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-journal-x d-block h3 text-gray-300 mb-2"></i> No education history logged.
                    </div>
                    <?php endif; ?>

                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; border-top:1px solid #eee; padding-top:20px">
                        <div>
                            <div style="font-size:12px; color:var(--gray); margin-bottom:3px; font-weight:600;">Highest Qualification</div>
                            <div style="font-weight:600; color: var(--dark);"><?= htmlspecialchars_decode($app['highest_qualification'] ?? 'N/A') ?></div>
                        </div> 
                        <div>
                            <div style="font-size:12px; color:var(--gray); margin-bottom:3px; font-weight:600;">Institution</div>
                            <div style="font-weight:500; color: #444;"><?= htmlspecialchars($app['institution'] ?? 'N/A') ?></div>
                        </div> 
                        <div>
                            <div style="font-size:12px; color:var(--gray); margin-bottom:3px; font-weight:600;">Years of Experience</div>
                            <div style="font-weight:600; color: var(--success);"><i class="bi bi-clock-history"></i> <?= htmlspecialchars($app['years_experience'] ?? '0') ?> year<?= ($app['years_experience'] ?? 0) != 1 ? 's' : '' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm" style="margin-bottom:20px; border:0;">
                <div class="card-header bg-dark text-white py-3 d-flex align-items-center">
                    <h3 style="margin:0; font-size:15px; font-weight:600; text-uppercase; tracking-wider;"><i class="bi bi-buildings" style="margin-right: 8px;"></i> Employment Record</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($employmentEntries)): ?>
                        <?php foreach ($employmentEntries as $idx => $emp):
                            $isPresent = ($idx === 0);
                            $isFirstPrev = ($idx === 1);
                        ?>
                            <?php if ($isPresent): ?>
                            <p style="font-size:13px; color:var(--primary); font-weight:700; margin-bottom:10px; text-uppercase; letter-spacing:0.5px;"><i class="bi bi-arrow-right-circle-fill text-success"></i> Present Employment</p>
                            <?php elseif ($isFirstPrev): ?>
                            <p style="font-size:13px; color:var(--primary); font-weight:700; margin:20px 0 10px; text-uppercase; letter-spacing:0.5px;"><i class="bi bi-clock-history text-muted"></i> Previous Employment History</p>
                            <?php endif; ?>

                            <div style="background:var(--light-gray, #f8f9fa); border-left:4px solid #4e73df; border-radius:8px; padding:15px; margin-bottom:12px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.02)">
                                <div style="display:grid; grid-template-columns:1fr 1fr 2fr 1fr; gap:12px; font-size:13px">
                                    <div>
                                        <div style="font-size:11px; color:var(--gray); font-weight:600;">From</div>
                                        <div style="font-weight:500;"><i class="bi bi-calendar3"></i> <?= displayVal($emp['from_date'] ?? '') ?></div>
                                    </div>
                                    <div>
                                        <div style="font-size:11px; color:var(--gray); font-weight:600;">To</div>
                                        <div style="font-weight:500;">
                                            <?= !$isPresent ? '<i class="bi bi-calendar3"></i> '.displayVal($emp['to_date'] ?? '') : '<span class="badge badge-success">Current Role</span>' ?>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="font-size:11px; color:var(--gray); font-weight:600;">Institution Profile</div>
                                        <div style="font-weight:600; color:#222;"><?= htmlspecialchars($emp['institution_name'] ?? 'N/A') ?></div>
                                        <?php if (!empty($emp['institution_address'])): ?>
                                        <div style="font-size:12px; color:var(--gray);"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($emp['institution_address']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-size:11px; color:var(--gray); font-weight:600;">Position</div>
                                        <div style="font-weight:600; color:var(--primary);"><?= displayVal($emp['position'] ?? '') ?></div>
                                    </div>
                                </div>
                                <?php if (!empty($emp['subject_work'])): ?>
                                <div style="margin-top:10px; border-top: 1px solid #eaecf4; padding-top: 8px;">
                                    <div style="font-size:11px; color:var(--gray); font-weight:600;">Key Responsibility / Scope of Work</div>
                                    <div style="font-size:13px; white-space:pre-line; color:#555; line-height:1.5;"><?= htmlspecialchars_decode($emp['subject_work']) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if (!$isPresent && (!empty($emp['nature']) || !empty($emp['reason_for_leaving']))): ?>
                                <div style="margin-top:8px; display:grid; grid-template-columns:1fr 1fr; gap:10px; border-top: 1px dotted #eaecf4; padding-top: 8px;">
                                    <div>
                                        <div style="font-size:11px; color:var(--gray); font-weight:600;">Classification</div>
                                        <div style="font-weight:500;"><i class="bi bi-hourglass-split"></i> <?= ($emp['nature'] ?? '') === 'part-time' ? 'Part Time' : 'Full Time' ?></div>
                                    </div>
                                    <div>
                                        <div style="font-size:11px; color:var(--gray); font-weight:600;">Reason for Leaving</div>
                                        <div style="font-weight:500; color:#ca3b27;"><i class="bi bi-door-open"></i> <?= displayVal($emp['reason_for_leaving'] ?? '') ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-briefcase-x d-block h3 text-gray-300 mb-2"></i> No active or historic employment timeline mapped.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($app['publications']) || !empty($app['relevance_statement'])): ?>
            <div class="card shadow-sm" style="margin-bottom:20px; border:0;">
                <div class="card-header bg-dark text-white py-3 d-flex align-items-center">
                    <h3 style="margin:0; font-size:15px; font-weight:600; text-uppercase; tracking-wider;"><i class="bi bi-book" style="margin-right: 8px;"></i> Publications & Relevance</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($app['publications'])): ?>
                    <div style="margin-bottom:20px;">
                        <div style="font-size:12px; color:var(--gray); margin-bottom:6px; font-weight:600; text-uppercase;"><i class="bi bi-journal-text text-primary"></i> Academic Publications / Papers</div>
                        <div style="font-size:13px; white-space:pre-line; line-height:1.8; color:#444; background:#fafafa; padding:15px; border-radius:6px; border:1px solid #eee;"><?= htmlspecialchars($app['publications']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($app['relevance_statement'])): ?>
                    <div>
                        <div style="font-size:12px; color:var(--gray); margin-bottom:6px; font-weight:600; text-uppercase;"><i class="bi bi-bullseye text-danger"></i> Statement of Relevance to Position</div>
                        <div style="font-size:13px; white-space:pre-line; line-height:1.8; color:#444; background:#fafafa; padding:15px; border-radius:6px; border:1px solid #eee;"><?= htmlspecialchars($app['relevance_statement']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm" style="margin-bottom:20px; border:0;">
                <div class="card-header bg-dark text-white py-3 d-flex align-items-center">
                    <h3 style="margin:0; font-size:15px; font-weight:600; text-uppercase; tracking-wider;"><i class="bi bi-clipboard-check" style="margin-right: 8px;"></i> General Requirements & Disclosures</h3>
                </div>
                <div class="card-body">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px 24px;">
                        <div>
                            <div style="font-size:12px; color:var(--gray); margin-bottom:3px; font-weight:600;">Objection to Reference Contact</div>
                            <div style="font-weight:500; color:#333;"><i class="bi bi-shield-exclamation text-muted"></i> <?= !empty($app['objection_to_reference']) ? ucfirst(htmlspecialchars($app['objection_to_reference'])) : '<span style="color:#aaa">Not specified</span>' ?></div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--gray); margin-bottom:3px; font-weight:600;">Physical Disability Status</div>
                            <div style="font-weight:500; color:#333;"><i class="bi bi-activity text-muted"></i> <?= !empty($app['physical_disability']) ? ucfirst(htmlspecialchars($app['physical_disability'])) : '<span style="color:#aaa">Not specified</span>' ?></div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--gray); margin-bottom:3px; font-weight:600;">Criminal Court Convictions</div>
                            <div style="font-weight:500; color:#333;"><i class="bi bi-gavel text-muted"></i> <?= !empty($app['conviction']) ? ucfirst(htmlspecialchars($app['conviction'])) : '<span style="color:#aaa">Not specified</span>' ?></div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--gray); margin-bottom:3px; font-weight:600;">Notice Period Requirement</div>
                            <div style="font-weight:600; color:var(--dark);"><i class="bi bi-hourglass text-muted"></i> <?= displayVal($app['appointment_notice_period'] ?? '') ?></div>
                        </div>
                    </div>
                    <?php if (!empty($app['disability_details'])): ?>
                    <div style="margin-top:15px; background-color:#fff3cd; border-left:4px solid #ffc107; border-radius:4px; padding:12px; color:#856404;">
                        <div style="font-size:12px; font-weight:700; margin-bottom:4px;"><i class="bi bi-exclamation-triangle-fill"></i> Disability Structural Details</div>
                        <div style="font-size:13px; line-height:1.5; font-weight:500;"><?= nl2br(htmlspecialchars($app['disability_details'])) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($app['conviction_details'])): ?>
                    <div style="margin-top:15px; background-color:#f8d7da; border-left:4px solid #dc3545; border-radius:4px; padding:12px; color:#721c24;">
                        <div style="font-size:12px; font-weight:700; margin-bottom:4px;"><i class="bi bi-exclamation-octagon-fill"></i> Court Conviction Details File Record</div>
                        <div style="font-size:13px; line-height:1.5; font-weight:500;"><?= nl2br(htmlspecialchars($app['conviction_details'])) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($app['hobbies'])): ?>
                    <div style="margin-top:15px; border-top:1px dotted #eee; padding-top:12px;">
                        <div style="font-size:12px; color:var(--gray); margin-bottom:4px; font-weight:600;"><i class="bi bi-heart text-danger"></i> Personal Hobbies & Leisure</div>
                        <div style="font-size:13px; color:#555;"><?= nl2br(htmlspecialchars($app['hobbies'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($app['additional_info'])): ?>
            <div class="card shadow-sm" style="margin-bottom:20px; border:0;">
                <div class="card-header bg-dark text-white py-3 d-flex align-items-center">
                    <h3 style="margin:0; font-size:15px; font-weight:600; text-uppercase; tracking-wider;"><i class="bi bi-pencil-square" style="margin-right: 8px;"></i> Additional Information</h3>
                </div>
                <div class="card-body">
                    <div style="white-space:pre-line; color:#444; line-height:1.8; font-size:13px; background:#fcfcfc; padding:15px; border-radius:6px; border:1px solid #f1f1f1;"><?= htmlspecialchars($app['additional_info']) ?></div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm" style="margin-bottom:20px; border:0;">
                <div class="card-header bg-dark text-white py-3 d-flex align-items-center">
                    <h3 style="margin:0; font-size:15px; font-weight:600; text-uppercase; tracking-wider;"><i class="bi bi-people" style="margin-right: 8px;"></i> Referees Panel</h3>
                </div>
                <div class="card-body">
                    <?php for ($r = 1; $r <= 3; $r++):
                        $rName = $app["referee{$r}_name"] ?? '';
                        if (!$rName) continue;
                    ?>
                    <div style="background:var(--light-gray, #f8f9fa); border-top:3px solid var(--secondary); border-radius:6px; padding:15px; margin-bottom:12px; box-shadow: 0 1px 2px rgba(0,0,0,0.02)">
                        <h4 style="margin:0 0 12px 0; font-size:14px; color:var(--primary); font-weight:700;"><i class="bi bi-person-check"></i> Referee Slot #<?= $r ?></h4>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px 20px; font-size:13px">
                            <div>
                                <div style="font-size:11px; color:var(--gray); font-weight:600;">FullName</div>
                                <div style="font-weight:600; color:#222;"><?= htmlspecialchars($rName) ?></div>
                            </div>
                            <div>
                                <div style="font-size:11px; color:var(--gray); font-weight:600;">Occupation Sector</div>
                                <div style="font-weight:500; color:#444;"><?= displayVal($app["referee{$r}_occupation"] ?? '') ?></div>
                            </div>
                            <div>
                                <div style="font-size:11px; color:var(--gray); font-weight:600;">Designated Position</div>
                                <div style="font-weight:500; color:#444;"><?= displayVal($app["referee{$r}_position"] ?? '') ?></div>
                            </div>
                            <div>
                                <div style="font-size:11px; color:var(--gray); font-weight:600;">Contact Tel</div>
                                <div style="font-weight:600; color:var(--dark);"><i class="bi bi-telephone-outbound text-muted" style="font-size:11px;"></i> <?= displayVal($app["referee{$r}_tel"] ?? '') ?></div>
                            </div>
                            <div style="grid-column: span 2;">
                                <div style="font-size:11px; color:var(--gray); font-weight:600;">Verified Email Address</div>
                                <div style="font-weight:500;"><i class="bi bi-envelope text-muted" style="font-size:11px;"></i> <?= displayVal($app["referee{$r}_email"] ?? '') ?></div>
                            </div>
                        </div>
                        <?php if (!empty($app["referee{$r}_address"])): ?>
                        <div style="margin-top:10px; padding-top:8px; border-top:1px dotted #e3e6f0;">
                            <div style="font-size:11px; color:var(--gray); font-weight:600;">Postal / Corporate Address</div>
                            <div style="font-size:12px; color:#555; white-space:pre-line;"><?= nl2br(htmlspecialchars($app["referee{$r}_address"])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                    <?php if (empty(array_filter([$app['referee1_name'] ?? '', $app['referee2_name'] ?? '', $app['referee3_name'] ?? '']))): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-person-x d-block h3 text-gray-300 mb-2"></i> No references provided.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm" style="border:0;">
                <div class="card-header bg-dark text-white py-3 d-flex align-items-center">
                    <h3 style="margin:0; font-size:15px; font-weight:600; text-uppercase; tracking-wider;"><i class="bi bi-paperclip" style="margin-right: 8px;"></i> Attachments</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($uploadedFiles)): ?>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <?php foreach ($uploadedFiles as $file): ?>
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:15px; padding:12px 15px; border:1px solid #eaecf4; border-radius:8px; background:#fdfdfd; box-shadow:0 1px 2px rgba(0,0,0,0.01)">
                            <div style="min-width:0; display:flex; align-items:center; gap:12px;">
                                <?php 
                                    $ext = strtolower(pathinfo($file['stored_name'] ?? '', PATHINFO_EXTENSION));
                                    $icon = match($ext) {
                                        'pdf' => 'bi-file-earmark-pdf-fill text-danger',
                                        'doc', 'docx' => 'bi-file-earmark-word-fill text-primary',
                                        'jpg', 'jpeg', 'png' => 'bi-file-earmark-image-fill text-success',
                                        default => 'bi-file-earmark-arrow-up-fill text-secondary'
                                    };
                                ?>
                                <i class="bi <?= $icon ?>" style="font-size: 24px;"></i>
                                <div style="min-width:0;">
                                    <div style="font-weight:600; font-size:14px; color:#222; word-break:break-all;"><?= htmlspecialchars($file['original_name'] ?? 'File') ?></div>
                                    <div style="font-size:12px; color:var(--gray); margin-top:2px;">
                                        <span class="badge badge-light border text-uppercase" style="font-size:10px; padding:2px 5px; font-weight:700;"><?= $ext ?></span> · 
                                        <?= !empty($file['size_bytes']) ? round($file['size_bytes'] / 1024, 1) . ' KB' : 'N/A' ?> · 
                                        Uploaded <?= !empty($file['uploaded_at']) ? date('M d, Y', strtotime($file['uploaded_at'])) : 'Unknown' ?>
                                    </div>
                                </div>
                            </div>
                            <a href="/uploads/cvs/<?= htmlspecialchars($file['stored_name'] ?? '') ?>" class="btn btn-primary btn-sm d-inline-flex align-items-center" style="flex-shrink:0; font-weight:600; padding:6px 14px; gap:5px;" download>
                                <i class="bi bi-cloud-download" style="font-size:14px;"></i> Download
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-folder-x d-block h3 text-gray-300 mb-2"></i> No verified certificates loaded.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div>
            <div>
            <div>
            <div class="card" style="margin-bottom:20px; border:0; border-radius: 8px;">
                <div class="card-header bg-dark text-white py-3 d-flex align-items-center" style="border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    <h3 style="margin:0; font-size:15px; font-weight:600; text-uppercase; tracking-wider;"><i class="bi bi-person-circle" style="margin-right: 8px;"></i> Passport Picture </h3>
                </div>
                <div class="card-body" style="text-align:center; padding:24px 16px;">
                    <?php 
                    // Extract the raw image name from the database variable
                    $photoFilename = !empty($app['passport_photo_filename']) ? basename($app['passport_photo_filename']) : '';
                    
                    // Resolve photo path against this local project root so local Apache works without hosted paths.
                    $absoluteServerPath = dirname(__DIR__, 3) . '/public/uploads/photos/' . $photoFilename;
                    
                    if (!empty($photoFilename) && file_exists($absoluteServerPath)): 
                        // Fetch the file binary content directly from disk and encode it inline
                        $imageData = base64_encode(file_get_contents($absoluteServerPath));
                        $imageSrc = 'data:image/jpeg;base64,' . $imageData;
                    ?>
                        <div style="position:relative; display:inline-block; margin-bottom: 12px;">
                            <img src="<?= $imageSrc ?>" alt="Passport Photo" style="width:160px; height:195px; object-fit:cover; border-radius:6px; border:4px solid #fff; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                        </div>
                    <?php else: ?>
                        <div style="width:160px; height:195px; background:#eaecf4; border-radius:6px; display:inline-flex; flex-direction:column; align-items:center; justify-content:center; border:2px dashed #d1d3e2; margin-bottom:12px; color:#b7b9cc;">
                            <i class="bi bi-person-bounding-box" style="font-size:42px; margin-bottom:5px;"></i>
                            <span style="font-size:11px; font-weight:600; padding:0 10px;">No Photo Found</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm" style="border:0; border-radius: 8px;">
                <div class="card-header bg-dark text-white py-3">
                    <h3 style="margin:0; font-size:15px; font-weight:600; text-uppercase; tracking-wider;"><i class="bi bi-sliders" style="margin-right: 8px;"></i> Action Workflow</h3>
                </div>
                <div class="card-body" style="padding:20px;">
                    <form action="/recruitment/update-status" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="applicant_id" value="<?= (int)$id ?>">
                        
                        <div style="margin-bottom:16px;">
                            <label style="font-size:12px; font-weight:700; color:var(--dark);  display:block; margin-bottom:6px;">Recruitment Stages</label>
                            <select name="status" class="form-control basic-select-custom-style" style="font-size:13px; font-weight:500; height:40px; border-radius:6px;">
                                <option value="pending" <?= ($app['status'] ?? '') === 'pending' ? 'selected' : '' ?>>⏳ Pending Review</option>
                                <option value="reviewing" <?= ($app['status'] ?? '') === 'reviewing' ? 'selected' : '' ?>>🔍 Under Reviewing</option>
                                <option value="shortlisted" <?= ($app['status'] ?? '') === 'shortlisted' ? 'selected' : '' ?>>📋 Shortlisted</option>
                                <option value="interviewed" <?= ($app['status'] ?? '') === 'interviewed' ? 'selected' : '' ?>>🎙 Interviewed</option>
                                <option value="unsuccessful" <?= ($app['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>❌ Rejected</option>
                                <option value="hired" <?= ($app['status'] ?? '') === 'hired' ? 'selected' : '' ?>>✅ Hired </option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom:20px;">
                            <label style="font-size:12px; font-weight:700; color:var(--dark); display:block; margin-bottom:6px;">HR Process Notes</label>
                            <textarea name="hr_notes" rows="5" class="form-control text-area-custom-style" placeholder="Applicant's review comments" style="font-size:13px; border-radius:6px; padding:10px; line-height:1.5; resize:none;"><?= htmlspecialchars($app['hr_notes'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="alert alert-info d-flex align-items-start shadow-none border-0" style="font-size:12px; padding:12px; background-color:#eaecf4; color:#2e59d9; border-radius:6px; margin-bottom:16px; gap:8px;">
                            <i class="bi bi-info-circle-fill" style="font-size:14px; flex-shrink:0; margin-top:2px;"></i>
                            <span style="font-weight:500;">Applicant will be notified by email automatically when lifecycle workflow state changes.</span>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block py-2" style="width:100%; font-weight:700; text-uppercase; tracking-wide; border-radius:6px; box-shadow:0 2px 4px rgba(78,115,223,0.25);">
                            <i class="bi bi-check-circle-fill"></i> Save Updates
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
$(document).ready(function() {
    if ($('#applicantEduTable').length) {
        $('#applicantEduTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { text: '<i class="fas fa-copy"></i> Copy', className: 'btn btn-info btn-sm', extend: 'copy', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', extend: 'excel', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', extend: 'pdf', exportOptions: { columns: ':not(.no-export)' } },
                { text: '<i class="fas fa-print"></i> Print', className: 'btn btn-secondary btn-sm', extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            language: { search: "Search:", emptyTable: "No education records found." },
            order: [[3, 'desc']]
        });
    }
});
</script>
