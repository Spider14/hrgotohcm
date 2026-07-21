<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use PDO;

class PdfHelper
{
    public static function generateAppraisalPdf(int $appraisalId, string $outputMode = 'D'): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare(
            "SELECT a.*, u.fullname, u.email, s.staff_id_card, s.phone_one, d.dept_name
             FROM staff_appraisals a
             JOIN users u ON u.id = a.user_id
             LEFT JOIN staff_records s ON s.user_id = u.id
             LEFT JOIN departments d ON d.dept_id = s.dept_id
             WHERE a.id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $appraisalId]);
        $appraisal = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appraisal) {
            http_response_code(404);
            echo "Appraisal not found.";
            exit;
        }

        $periodLabel = $appraisal['period_label'] ?? 'N/A';
        $staffName = $appraisal['fullname'] ?? 'N/A';
        $staffId = $appraisal['staff_id_card'] ?? 'N/A';
        $department = $appraisal['dept_name'] ?? 'N/A';
        $email = $appraisal['email'] ?? '';
        $phone = $appraisal['phone_one'] ?? '';
        $score = $appraisal['score'] ?? 0;
        $selfScore = $appraisal['self_score'] ?? null;
        $rating = $appraisal['rating'] ?? 'N/A';
        $status = $appraisal['status'] ?? 'N/A';
        $supervisorComment = $appraisal['supervisor_comment'] ?? '';
        $hrComment = $appraisal['hr_comment'] ?? '';
        $summary = $appraisal['summary'] ?? '';
        $createdAt = $appraisal['created_at'] ?? '';

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetCreator('HRGoTo HCM');
        $pdf->SetAuthor('HRGoTo HCM');
        $pdf->SetTitle("Performance Appraisal - $staffName - $periodLabel");
        $pdf->SetSubject('Performance Appraisal Report');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->AddPage();
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 10, 'Performance Appraisal Report', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, "Generated: " . date('Y-m-d H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Employee Information', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);

        $html = '
        <table border="1" cellpadding="5" cellspacing="0">
            <tr><td width="35%"><b>Staff Name</b></td><td width="65%">' . Security::escape($staffName) . '</td></tr>
            <tr><td><b>Staff ID</b></td><td>' . Security::escape($staffId) . '</td></tr>
            <tr><td><b>Department</b></td><td>' . Security::escape($department) . '</td></tr>
            <tr><td><b>Email</b></td><td>' . Security::escape($email) . '</td></tr>
            <tr><td><b>Phone</b></td><td>' . Security::escape($phone) . '</td></tr>
        </table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Appraisal Summary', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);

        $html2 = '
        <table border="1" cellpadding="5" cellspacing="0">
            <tr><td width="35%"><b>Period</b></td><td width="65%">' . Security::escape($periodLabel) . '</td></tr>
            <tr><td><b>Overall Score</b></td><td>' . Security::escape((string)$score) . '</td></tr>
            <tr><td><b>Self Score</b></td><td>' . ($selfScore !== null ? Security::escape((string)$selfScore) : 'N/A') . '</td></tr>
            <tr><td><b>Rating</b></td><td>' . Security::escape($rating) . '</td></tr>
            <tr><td><b>Status</b></td><td>' . Security::escape($status) . '</td></tr>
            <tr><td><b>Submitted</b></td><td>' . Security::escape($createdAt) . '</td></tr>
        </table>';

        $pdf->writeHTML($html2, true, false, true, false, '');
        $pdf->Ln(5);

        $parsedSummary = json_decode($summary, true);
        if (is_array($parsedSummary)) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Metric Scores', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);

            $metricStmt = $db->query("SELECT id, metric_name, metric_prompt FROM appraisal_metrics ORDER BY id ASC");
            $metrics = $metricStmt->fetchAll(PDO::FETCH_ASSOC);
            $metricMap = [];
            foreach ($metrics as $m) {
                $metricMap[(int)$m['id']] = $m['metric_name'];
            }

            $scoreLabels = ['', '1 - Needs Improvement', '2 - Fair', '3 - Good', '4 - Very Good', '5 - Excellent'];

            $html3 = '<table border="1" cellpadding="5" cellspacing="0">
                        <thead><tr style="background-color:#f0f0f0;"><td width="60%"><b>Metric</b></td><td width="20%"><b>Score</b></td><td width="20%"><b>Rating</b></td></tr></thead><tbody>';
            foreach ($parsedSummary as $metricId => $scoreVal) {
                $metricName = $metricMap[(int)$metricId] ?? "Metric #$metricId";
                $scoreLabel = $scoreLabels[(int)$scoreVal] ?? Security::escape((string)$scoreVal);
                $html3 .= "<tr><td>" . Security::escape($metricName) . "</td><td>" . Security::escape((string)$scoreVal) . "</td><td>$scoreLabel</td></tr>";
            }
            $html3 .= '</tbody></table>';
            $pdf->writeHTML($html3, true, false, true, false, '');
        } elseif (!empty($summary)) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Summary Notes', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0, 6, Security::escape($summary), 0, 'L');
        }

        if (!empty($supervisorComment)) {
            $pdf->Ln(3);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, "Supervisor's Comment", 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0, 6, Security::escape($supervisorComment), 0, 'L');
        }

        if (!empty($hrComment)) {
            $pdf->Ln(3);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'HR Comment', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0, 6, Security::escape($hrComment), 0, 'L');
        }

        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 5, 'This is a system-generated document from HRGoTo HCM.', 0, 1, 'C');

        $filename = "Appraisal_{$staffId}_{$periodLabel}.pdf";
        $pdf->Output($filename, $outputMode);
        exit;
    }
}
