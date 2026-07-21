<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Database;
use PDO;

class ApplyController {

    private function getConfig(): array {
        $db = Database::getConnection();
        $config = $db->query("SELECT company_name, company_short_name, company_logo_url, company_tagline FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        return [
            'name'       => $config['company_name'] ?? 'HRGoTo',
            'short_name' => $config['company_short_name'] ?? 'HRGoTo',
            'logo'       => $config['company_logo_url'] ?? '',
            'tagline'    => $config['company_tagline'] ?? '',
        ];
    }

    public function index(Request $request): void {
        $db = Database::getConnection();
        $jobs = $db->query("SELECT id, title, department, type, location, salary_range, description, requirements, deadline, created_at FROM jobs WHERE status = 'open' AND (deadline IS NULL OR deadline >= CURDATE()) ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        $company = $this->getConfig();
        $pageTitle = "Careers - Current Job Openings";
        require_once __DIR__ . '/../Views/apply/jobs.php';
    }

    public function form(Request $request): void {
        $db = Database::getConnection();
        $jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
        $job = $db->prepare("SELECT id, title, department, type, location, salary_range, description, requirements, deadline FROM jobs WHERE id = ? AND status = 'open' AND (deadline IS NULL OR deadline >= CURDATE())");
        $job->execute([$jobId]);
        $job = $job->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            http_response_code(404);
            echo '<h2 class="text-center mt-5 text-muted">This job posting is no longer available.</h2><p class="text-center"><a href="/apply" class="btn btn-primary">Browse Openings</a></p>';
            exit;
        }

        $company = $this->getConfig();
        $pageTitle = "Apply for " . $job['title'];
        require_once __DIR__ . '/../Views/apply/form.php';
    }

    public function submit(Request $request): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /apply');
            exit;
        }

        $db = Database::getConnection();
        $jobId = (int)($_POST['job_id'] ?? 0);

        $stmt = $db->prepare("SELECT id, title FROM jobs WHERE id = ? AND status = 'open' AND (deadline IS NULL OR deadline >= CURDATE())");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$job) {
            header('Location: /apply');
            exit;
        }

        $title          = trim($_POST['title'] ?? '');
        $firstName      = trim($_POST['first_name'] ?? '');
        $lastName       = trim($_POST['last_name'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $phone          = trim($_POST['phone'] ?? '');
        $address        = trim($_POST['address'] ?? '');
        $placeOfBirth   = trim($_POST['place_of_birth'] ?? '');
        $homeTown       = trim($_POST['home_town'] ?? '');
        $region         = trim($_POST['region'] ?? '');
        $nationality    = trim($_POST['nationality'] ?? '');
        $religion       = trim($_POST['religion'] ?? '');
        $maritalStatus  = trim($_POST['marital_status'] ?? '');
        $spouseName     = trim($_POST['spouse_name'] ?? '');
        $dob            = $_POST['date_of_birth'] ?? null;
        $gender         = $_POST['gender'] ?? null;
        // Collect education entries
        $eduQualifications = $_POST['edu_qualification'] ?? [];
        $eduInstitutions   = $_POST['edu_institution'] ?? [];
        $eduFields         = $_POST['edu_field'] ?? [];
        $eduStarts         = $_POST['edu_start'] ?? [];
        $eduEnds           = $_POST['edu_end'] ?? [];
        $eduGrades         = $_POST['edu_grade'] ?? [];
        $eduEntries = [];
        foreach ($eduQualifications as $i => $q) {
            $q = trim($q);
            $inst = trim($eduInstitutions[$i] ?? '');
            if ($q === '' || $inst === '') continue;
            $eduEntries[] = [
                'qualification' => $q,
                'institution'   => $inst,
                'field'         => trim($eduFields[$i] ?? ''),
                'start'         => !empty($eduStarts[$i]) ? (int)$eduStarts[$i] : null,
                'end'           => !empty($eduEnds[$i]) ? (int)$eduEnds[$i] : null,
                'grade'         => trim($eduGrades[$i] ?? ''),
            ];
        }
        $qualification = $eduEntries[0]['qualification'] ?? '';
        $institution   = $eduEntries[0]['institution'] ?? '';
        $yearsExp      = (int)($_POST['years_experience'] ?? 0);

        // Collect job experience entries
        $jobRoles = $_POST['job_role'] ?? [];
        $jobStarts = $_POST['job_start'] ?? [];
        $jobEnds   = $_POST['job_end'] ?? [];
        $jobExp = [];
        foreach ($jobRoles as $i => $role) {
            $role = trim($role);
            if ($role === '') continue;
            $jobExp[] = [
                'role'      => $role,
                'start_year'=> !empty($jobStarts[$i]) ? (int)$jobStarts[$i] : null,
                'end_year'  => !empty($jobEnds[$i]) ? (int)$jobEnds[$i] : null,
            ];
        }
        $ghanaCard      = trim($_POST['ghana_card_number'] ?? '');
        $graPin         = trim($_POST['gra_pin'] ?? '');
        $hobbies        = trim($_POST['hobbies'] ?? '');
        $additionalInfo = trim($_POST['additional_info'] ?? '');

        $referee1Name  = trim($_POST['referee1_name'] ?? '');
        $referee1Pos   = trim($_POST['referee1_position'] ?? '');
        $referee1Tel   = trim($_POST['referee1_tel'] ?? '');
        $referee1Email = trim($_POST['referee1_email'] ?? '');
        $referee2Name  = trim($_POST['referee2_name'] ?? '');
        $referee2Pos   = trim($_POST['referee2_position'] ?? '');
        $referee2Tel   = trim($_POST['referee2_tel'] ?? '');
        $referee2Email = trim($_POST['referee2_email'] ?? '');

        if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($qualification)) {
            $_SESSION['apply_error'] = 'Please fill in all required fields.';
            header('Location: /apply/submit?job_id=' . $jobId);
            exit;
        }

        $maxBytes = 10 * 1024 * 1024;
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/';
        $cvDir     = $uploadDir . 'cvs/';
        $photoDir  = $uploadDir . 'photos/';
        $supportDir = $uploadDir . 'supporting/';
        foreach ([$uploadDir, $cvDir, $photoDir, $supportDir] as $d) {
            if (!is_dir($d)) mkdir($d, 0755, true);
        }

        $cvFilename = '';
        $cvOriginal = '';
        if (!empty($_FILES['cv']['name'])) {
            if ($_FILES['cv']['size'] > $maxBytes) {
                $_SESSION['apply_error'] = 'CV exceeds 10MB limit.';
                header('Location: /apply/submit?job_id=' . $jobId);
                exit;
            }
            $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'doc', 'docx'])) {
                $_SESSION['apply_error'] = 'CV must be PDF or Word document.';
                header('Location: /apply/submit?job_id=' . $jobId);
                exit;
            }
            $cvOriginal = basename($_FILES['cv']['name']);
            $cvFilename = uniqid('cv_') . '.' . $ext;
            move_uploaded_file($_FILES['cv']['tmp_name'], $cvDir . $cvFilename);
        }

        $photoFilename = '';
        $photoOriginal = '';
        if (!empty($_FILES['passport_photo']['name'])) {
            if ($_FILES['passport_photo']['size'] > $maxBytes) {
                $_SESSION['apply_error'] = 'Photo exceeds 10MB limit.';
                header('Location: /apply/submit?job_id=' . $jobId);
                exit;
            }
            $ext = strtolower(pathinfo($_FILES['passport_photo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $_SESSION['apply_error'] = 'Photo must be an image (JPG, PNG, GIF).';
                header('Location: /apply/submit?job_id=' . $jobId);
                exit;
            }
            $photoOriginal = basename($_FILES['passport_photo']['name']);
            $photoFilename = uniqid('photo_') . '.' . $ext;
            move_uploaded_file($_FILES['passport_photo']['tmp_name'], $photoDir . $photoFilename);
        }

        // Collect supporting file uploads
        $supportFiles = [];
        if (!empty($_FILES['file_upload']['name'][0])) {
            $names = $_FILES['file_upload']['name'];
            $tmpNames = $_FILES['file_upload']['tmp_name'];
            $sizes = $_FILES['file_upload']['size'];
            $errors = $_FILES['file_upload']['error'];
            $descs = $_POST['file_desc'] ?? [];

            foreach ($names as $i => $name) {
                if (empty($name) || $errors[$i] !== UPLOAD_ERR_OK) continue;
                if ($sizes[$i] > $maxBytes) {
                    $_SESSION['apply_error'] = "Supporting file '" . basename($name) . "' exceeds 10MB limit.";
                    header('Location: /apply/submit?job_id=' . $jobId);
                    exit;
                }
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'])) {
                    $_SESSION['apply_error'] = "Supporting file '" . basename($name) . "' has an invalid format.";
                    header('Location: /apply/submit?job_id=' . $jobId);
                    exit;
                }
                $stored = uniqid('sup_') . '.' . $ext;
                move_uploaded_file($tmpNames[$i], $supportDir . $stored);
                $supportFiles[] = [
                    'original' => basename($name),
                    'stored'   => $stored,
                    'size'     => $sizes[$i],
                    'mime'     => mime_content_type($supportDir . $stored) ?: '',
                    'desc'     => trim($descs[$i] ?? ''),
                ];
            }
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO applications (job_id, title, first_name, last_name, email, phone, address, place_of_birth, home_town, region, nationality, religion, marital_status, spouse_name, date_of_birth, gender, highest_qualification, institution, years_experience, ghana_card_number, gra_pin, hobbies, additional_info, referee1_name, referee1_position, referee1_tel, referee1_email, referee2_name, referee2_position, referee2_tel, referee2_email, cv_filename, cv_original_name, passport_photo_filename, passport_photo_original_name, ip_address, reference_number, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'pending')");
            $stmt->execute([
                $jobId, $title, $firstName, $lastName, $email, $phone, $address, $placeOfBirth, $homeTown,
                $region, $nationality, $religion, $maritalStatus, $spouseName, $dob, $gender, $qualification,
                $institution, $yearsExp, $ghanaCard, $graPin, $hobbies, $additionalInfo,
                $referee1Name, $referee1Pos, $referee1Tel, $referee1Email,
                $referee2Name, $referee2Pos, $referee2Tel, $referee2Email,
                $cvFilename, $cvOriginal, $photoFilename, $photoOriginal, $ip, 'TEMP'
            ]);

            $appId = (int)$db->lastInsertId();

            // Save education records
            if (!empty($eduEntries)) {
                $stmt = $db->prepare("INSERT INTO application_education (application_id, school_name, qualification, field_of_study, start_year, end_year, grade) VALUES (?,?,?,?,?,?,?)");
                foreach ($eduEntries as $e) {
                    $stmt->execute([$appId, $e['institution'], $e['qualification'], $e['field'], $e['start'], $e['end'], $e['grade']]);
                }
            }

            // Save job experience
            if (!empty($jobExp)) {
                $stmt = $db->prepare("INSERT INTO application_experience (application_id, job_role, start_year, end_year) VALUES (?,?,?,?)");
                foreach ($jobExp as $je) {
                    $stmt->execute([$appId, $je['role'], $je['start_year'], $je['end_year']]);
                }
            }

            // Save supporting files
            if (!empty($supportFiles)) {
                $stmt = $db->prepare("INSERT INTO application_files (application_id, original_name, stored_name, mime_type, size_bytes, description) VALUES (?,?,?,?,?,?)");
                foreach ($supportFiles as $sf) {
                    $stmt->execute([$appId, $sf['original'], $sf['stored'], $sf['mime'], $sf['size'], $sf['desc']]);
                }
            }

            $refNum = 'APP-' . date('Y') . '/' . str_pad((string)$appId, 4, '0', STR_PAD_LEFT);
            $stmt = $db->prepare("UPDATE applications SET reference_number = ? WHERE id = ?");
            $stmt->execute([$refNum, $appId]);
            $db->commit();

            $fullName = $firstName . ' ' . $lastName;
            $this->sendConfirmationSms($phone, $fullName, $refNum, $job['title']);

            header('Location: /apply/success?ref=' . urlencode($refNum));
            exit;

        } catch (\Throwable $e) {
            $db->rollBack();
            $cleanupFiles = [$cvDir . $cvFilename, $photoDir . $photoFilename];
            foreach ($supportFiles as $sf) { $cleanupFiles[] = $supportDir . $sf['stored']; }
            foreach ($cleanupFiles as $f) { if (file_exists($f)) unlink($f); }
            $_SESSION['apply_error'] = 'An error occurred while submitting your application. Please try again.';
            header('Location: /apply/submit?job_id=' . $jobId);
            exit;
        }
    }

    public function success(Request $request): void {
        $company = $this->getConfig();
        $refNum = $_GET['ref'] ?? '';
        $pageTitle = "Application Submitted";
        require_once __DIR__ . '/../Views/apply/success.php';
    }

    private function sendConfirmationSms(string $phone, string $fullName, string $refNum, string $jobTitle): void {
        try {
            $db = Database::getConnection();
            $config = $db->query("SELECT sms_endpoint, gen_sms_sender_id, sms_apikey FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);

            $endPoint = !empty($config['sms_endpoint']) ? trim($config['sms_endpoint']) : 'https://api.mnotify.com/api/sms/quick';
            $apiKey   = !empty($config['sms_apikey']) ? trim($config['sms_apikey']) : '';
            $senderId = !empty($config['gen_sms_sender_id']) ? trim($config['gen_sms_sender_id']) : 'HRGoTo';

            if (empty($apiKey) || empty($phone)) return;

            $message = "Dear $fullName, thank you for applying for $jobTitle. Your tracking code is $refNum. HRGoTo HCM.";
            $phone = trim(str_replace(' ', '', $phone));
            $url = $endPoint . '?key=' . $apiKey;

            $data = [
                'recipient'     => [$phone],
                'sender'        => $senderId,
                'message'       => $message,
                'is_schedule'   => 'false',
                'schedule_date' => ''
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable $e) {
            error_log("Apply SMS error: " . $e->getMessage());
        }
    }
}
