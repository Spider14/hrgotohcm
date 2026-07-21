<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Helpers\Security;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use PDO;
use Throwable;

class PayrollController {
    public function __construct() {
        AuthMiddleware::handle();
    }

    private function ensurePayrollTables(): void {
        $db = Database::getConnection();

        $db->exec("CREATE TABLE IF NOT EXISTS payroll_components (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            type ENUM('allowance','deduction') NOT NULL DEFAULT 'allowance',
            calculation_type ENUM('fixed','percentage') NOT NULL DEFAULT 'fixed',
            default_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            is_taxable TINYINT(1) NOT NULL DEFAULT 0,
            is_ssnit_liable TINYINT(1) NOT NULL DEFAULT 0,
            is_mandatory TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS payroll_employee_components (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            component_id INT UNSIGNED NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            effective_from DATE NULL,
            effective_to DATE NULL,
            UNIQUE KEY uk_emp_comp (user_id, component_id, effective_from)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS payroll_periods (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            period_label VARCHAR(60) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            status ENUM('open','closed','processed') NOT NULL DEFAULT 'open',
            processed_at DATETIME NULL,
            processed_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS payroll_runs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            period_id INT UNSIGNED NOT NULL,
            user_id INT NOT NULL,
            gross_pay DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            total_allowances DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            total_deductions DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            ssnit_employee DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            ssnit_employer DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            paye_tax DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            net_pay DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_period_user (period_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS payroll_run_details (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            run_id INT UNSIGNED NOT NULL,
            component_id INT UNSIGNED NULL,
            label VARCHAR(100) NOT NULL,
            type ENUM('earnings','deduction') NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS payroll_tax_brackets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            from_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            to_amount DECIMAL(12,2) NULL,
            rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS payroll_deductions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(150) NOT NULL,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            installment_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            remaining_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            start_period_id INT UNSIGNED NULL,
            status ENUM('active','completed') NOT NULL DEFAULT 'active',
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS staff_bank_details (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            bank_name VARCHAR(100) NULL,
            branch VARCHAR(100) NULL,
            account_number VARCHAR(30) NULL,
            account_name VARCHAR(150) NULL,
            account_type ENUM('savings','current') NULL DEFAULT 'savings',
            mobile_money_provider VARCHAR(20) NULL,
            mobile_money_number VARCHAR(20) NULL,
            payment_method ENUM('bank_transfer','mobile_money','direct_deposit','cash') NOT NULL DEFAULT 'bank_transfer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_bank_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS benefit_types (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            category ENUM('housing','transport','medical','other') NOT NULL DEFAULT 'other',
            default_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            calculation_type ENUM('fixed','percentage') NOT NULL DEFAULT 'fixed',
            is_taxable TINYINT(1) NOT NULL DEFAULT 0,
            is_ssnit_liable TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS employee_benefits (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            benefit_type_id INT UNSIGNED NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            effective_from DATE NULL,
            effective_to DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_emp_benefit (user_id, benefit_type_id, effective_from)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $rowCount = (int)$db->query("SELECT COUNT(*) FROM payroll_tax_brackets")->fetchColumn();
        if ($rowCount === 0) {
            $db->exec("INSERT INTO payroll_tax_brackets (from_amount, to_amount, rate) VALUES
                (0, 490.00, 0),
                (490.01, 605.00, 5),
                (605.01, 770.00, 10),
                (770.01, 3030.00, 17.5),
                (3030.01, 6720.00, 25),
                (6720.01, 11720.00, 30),
                (11720.01, NULL, 35)");
        }
    }

    public function index(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensurePayrollTables();
        $db = Database::getConnection();

        $latestPeriod = $db->query("SELECT * FROM payroll_periods ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
        $totalProcessed = 0;
        if ($latestPeriod) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM payroll_runs WHERE period_id = :pid");
            $stmt->execute(['pid' => $latestPeriod['id']]);
            $totalProcessed = (int)$stmt->fetchColumn();
        }
        $totalStaff = (int)$db->query("SELECT COUNT(*) FROM staff_records")->fetchColumn();
        $openPeriods = (int)$db->query("SELECT COUNT(*) FROM payroll_periods WHERE status = 'open'")->fetchColumn();

        $pageTitle = 'HRGoTo HCM - Payroll';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/payroll/index.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function components(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $components = $db->query("SELECT * FROM payroll_components ORDER BY type, name")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'HRGoTo HCM - Payroll Components';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/payroll/components.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function saveComponent(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $body = $request->getBody();
        $id = (int)($body['id'] ?? 0);

        try {
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE payroll_components SET name=:n, type=:t, calculation_type=:ct, default_value=:dv, is_taxable=:itx, is_ssnit_liable=:isl, is_mandatory=:im WHERE id=:id");
                $stmt->execute(['n' => $body['name'], 't' => $body['type'], 'ct' => $body['calculation_type'], 'dv' => (float)($body['default_value'] ?? 0), 'itx' => (int)($body['is_taxable'] ?? 0), 'isl' => (int)($body['is_ssnit_liable'] ?? 0), 'im' => (int)($body['is_mandatory'] ?? 0), 'id' => $id]);
            } else {
                $stmt = $db->prepare("INSERT INTO payroll_components (name, type, calculation_type, default_value, is_taxable, is_ssnit_liable, is_mandatory) VALUES (:n, :t, :ct, :dv, :itx, :isl, :im)");
                $stmt->execute(['n' => $body['name'], 't' => $body['type'], 'ct' => $body['calculation_type'], 'dv' => (float)($body['default_value'] ?? 0), 'itx' => (int)($body['is_taxable'] ?? 0), 'isl' => (int)($body['is_ssnit_liable'] ?? 0), 'im' => (int)($body['is_mandatory'] ?? 0)]);
            }
            Security::setFlash('ok', 'Component saved');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
        }
        header('Location: ' . $this->appUrl() . '/payroll/components');
        exit;
    }

    public function employeeComponents(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensurePayrollTables();
        $db = Database::getConnection();

        $userId = (int)($request->getParam('user_id') ?? 0);
        $staff = $db->query("SELECT u.id, u.fullname, s.staff_id_card, d.dept_name FROM users u LEFT JOIN staff_records s ON s.user_id = u.id LEFT JOIN departments d ON d.dept_id = s.dept_id WHERE u.deleted_at IS NULL ORDER BY u.fullname")->fetchAll(PDO::FETCH_ASSOC);
        $components = $db->query("SELECT * FROM payroll_components ORDER BY type, name")->fetchAll(PDO::FETCH_ASSOC);
        $assignments = [];
        if ($userId > 0) {
            $stmt = $db->prepare("SELECT pec.*, pc.name AS comp_name, pc.type AS comp_type FROM payroll_employee_components pec JOIN payroll_components pc ON pc.id = pec.component_id WHERE pec.user_id = :uid");
            $stmt->execute(['uid' => $userId]);
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $pageTitle = 'HRGoTo HCM - Employee Components';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/payroll/employee_components.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function saveEmployeeComponent(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $body = $request->getBody();
        $userId = (int)($body['user_id'] ?? 0);
        $compId = (int)($body['component_id'] ?? 0);
        $amount = (float)($body['amount'] ?? 0);

        try {
            $existing = $db->prepare("SELECT id FROM payroll_employee_components WHERE user_id = :uid AND component_id = :cid");
            $existing->execute(['uid' => $userId, 'cid' => $compId]);
            if ($existing->fetch()) {
                $db->prepare("UPDATE payroll_employee_components SET amount = :amt WHERE user_id = :uid AND component_id = :cid")
                    ->execute(['amt' => $amount, 'uid' => $userId, 'cid' => $compId]);
            } else {
                $db->prepare("INSERT INTO payroll_employee_components (user_id, component_id, amount) VALUES (:uid, :cid, :amt)")
                    ->execute(['uid' => $userId, 'cid' => $compId, 'amt' => $amount]);
            }
            Security::setFlash('ok', 'Component saved');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
        }
        header('Location: ' . $this->appUrl() . '/payroll/employee-components?user_id=' . $userId);
        exit;
    }

    public function periods(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $periods = $db->query("SELECT * FROM payroll_periods ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'HRGoTo HCM - Payroll Periods';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/payroll/periods.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function createPeriod(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $body = $request->getBody();

        try {
            $db->prepare("INSERT INTO payroll_periods (period_label, start_date, end_date) VALUES (:l, :s, :e)")
                ->execute(['l' => $body['period_label'], 's' => $body['start_date'], 'e' => $body['end_date']]);
            Security::setFlash('ok', 'Period created');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
        }
        header('Location: ' . $this->appUrl() . '/payroll/periods');
        exit;
    }

    public function closePeriod(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $db = Database::getConnection();
        $id = (int)($request->getBody()['id'] ?? 0);

        try {
            $db->prepare("UPDATE payroll_periods SET status = 'closed' WHERE id = :id")->execute(['id' => $id]);
            Security::setFlash('ok', 'Period closed');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
        }
        header('Location: ' . $this->appUrl() . '/payroll/periods');
        exit;
    }

    public function processView(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $periods = $db->query("SELECT * FROM payroll_periods WHERE status IN ('open','processed') ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
        $periodId = (int)($request->getParam('period_id') ?? 0);
        $runs = [];
        if ($periodId > 0) {
            $stmt = $db->prepare("SELECT pr.*, u.fullname, s.staff_id_card, d.dept_name FROM payroll_runs pr JOIN users u ON pr.user_id = u.id LEFT JOIN staff_records s ON s.user_id = u.id LEFT JOIN departments d ON d.dept_id = s.dept_id WHERE pr.period_id = :pid ORDER BY u.fullname");
            $stmt->execute(['pid' => $periodId]);
            $runs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $pageTitle = 'HRGoTo HCM - Process Payroll';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/payroll/process.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function processRun(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $periodId = (int)($request->getBody()['period_id'] ?? 0);

        try {
            $period = $db->prepare("SELECT * FROM payroll_periods WHERE id = :id");
            $period->execute(['id' => $periodId]);
            $periodData = $period->fetch(PDO::FETCH_ASSOC);
            if (!$periodData) throw new \Exception('Period not found');

            $components = $db->query("SELECT * FROM payroll_components ORDER BY type")->fetchAll(PDO::FETCH_ASSOC);
            $taxBrackets = $db->query("SELECT * FROM payroll_tax_brackets ORDER BY from_amount")->fetchAll(PDO::FETCH_ASSOC);
            $staffList = $db->query("SELECT u.id, u.fullname, s.staff_id_card FROM users u JOIN staff_records s ON s.user_id = u.id WHERE u.deleted_at IS NULL AND u.status = 'Active'")->fetchAll(PDO::FETCH_ASSOC);

            $db->beginTransaction();
            foreach ($staffList as $staff) {
                $uid = (int)$staff['id'];
                $grossPay = 0.0;
                $totalAllow = 0.0;
                $totalDed = 0.0;
                $details = [];

                foreach ($components as $comp) {
                    $stmt = $db->prepare("SELECT amount FROM payroll_employee_components WHERE user_id = :uid AND component_id = :cid LIMIT 1");
                    $stmt->execute(['uid' => $uid, 'cid' => $comp['id']]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($comp['calculation_type'] === 'fixed') {
                        $amount = $row ? (float)$row['amount'] : (float)$comp['default_value'];
                    } else {
                        $amount = 0.0;
                    }

                    if ($amount <= 0) continue;

                    if ($comp['type'] === 'allowance') {
                        $totalAllow += $amount;
                        $grossPay += $amount;
                        $details[] = ['label' => $comp['name'], 'type' => 'earnings', 'amount' => $amount, 'component_id' => $comp['id']];
                    } else {
                        $totalDed += $amount;
                        $details[] = ['label' => $comp['name'], 'type' => 'deduction', 'amount' => $amount, 'component_id' => $comp['id']];
                    }
                }

                // Include employee benefits as allowances
                $benefitsStmt = $db->prepare("SELECT eb.amount, bt.name, bt.is_taxable, bt.is_ssnit_liable, bt.calculation_type, bt.default_amount, r.salary FROM employee_benefits eb JOIN benefit_types bt ON bt.id = eb.benefit_type_id LEFT JOIN staff_records s ON s.user_id = eb.user_id LEFT JOIN ranks r ON r.id = s.rank_id WHERE eb.user_id = :uid AND bt.is_active = 1 AND (eb.effective_from IS NULL OR eb.effective_from <= CURDATE()) AND (eb.effective_to IS NULL OR eb.effective_to >= CURDATE())");
                $benefitsStmt->execute(['uid' => $uid]);
                foreach ($benefitsStmt->fetchAll(PDO::FETCH_ASSOC) as $ben) {
                    if ($ben['calculation_type'] === 'percentage') {
                        $base = (float)($ben['salary'] ?? 0);
                        $amount = $base > 0 ? round($base * (float)$ben['default_amount'] / 100, 2) : 0.0;
                    } else {
                        $amount = (float)$ben['amount'];
                    }
                    if ($amount <= 0) continue;
                    $grossPay += $amount;
                    $totalAllow += $amount;
                    $details[] = ['label' => $ben['name'], 'type' => 'earnings', 'amount' => $amount, 'component_id' => null];
                }

                $ssnitEmp = round($grossPay * 0.055, 2);
                $ssnitEmpr = round($grossPay * 0.13, 2);
                $taxableIncome = $grossPay - $ssnitEmp;
                $payeTax = $this->calculatePAYE($taxableIncome, $taxBrackets);

                $activeDeductions = $db->prepare("SELECT * FROM payroll_deductions WHERE user_id = :uid AND status = 'active'");
                $activeDeductions->execute(['uid' => $uid]);
                $loanDeductions = 0.0;
                foreach ($activeDeductions->fetchAll(PDO::FETCH_ASSOC) as $loan) {
                    $inst = (float)$loan['installment_amount'];
                    $loanDeductions += $inst;
                    $details[] = ['label' => $loan['name'], 'type' => 'deduction', 'amount' => $inst, 'component_id' => null];
                    $remaining = (float)$loan['remaining_amount'] - $inst;
                    if ($remaining <= 0) {
                        $db->prepare("UPDATE payroll_deductions SET status = 'completed', remaining_amount = 0 WHERE id = :id")->execute(['id' => $loan['id']]);
                    } else {
                        $db->prepare("UPDATE payroll_deductions SET remaining_amount = :rem WHERE id = :id")->execute(['rem' => $remaining, 'id' => $loan['id']]);
                    }
                }
                $totalDed += $loanDeductions;

                $netPay = $grossPay - $ssnitEmp - $payeTax - ($totalDed - $loanDeductions);

                $stmt = $db->prepare("INSERT INTO payroll_runs (period_id, user_id, gross_pay, total_allowances, total_deductions, ssnit_employee, ssnit_employer, paye_tax, net_pay) VALUES (:pid, :uid, :gp, :ta, :td, :se, :sem, :pt, :np) ON DUPLICATE KEY UPDATE gross_pay=:gp2, total_allowances=:ta2, total_deductions=:td2, ssnit_employee=:se2, ssnit_employer=:sem2, paye_tax=:pt2, net_pay=:np2");
                $stmt->execute([
                    'pid' => $periodId, 'uid' => $uid, 'gp' => $grossPay, 'ta' => $totalAllow, 'td' => $totalDed, 'se' => $ssnitEmp, 'sem' => $ssnitEmpr, 'pt' => $payeTax, 'np' => $netPay,
                    'gp2' => $grossPay, 'ta2' => $totalAllow, 'td2' => $totalDed, 'se2' => $ssnitEmp, 'sem2' => $ssnitEmpr, 'pt2' => $payeTax, 'np2' => $netPay,
                ]);
                $runId = (int)$db->lastInsertId();
                if ($runId === 0) {
                    $stmt = $db->prepare("SELECT id FROM payroll_runs WHERE period_id = :pid AND user_id = :uid");
                    $stmt->execute(['pid' => $periodId, 'uid' => $uid]);
                    $runId = (int)$stmt->fetchColumn();
                }

                $db->prepare("DELETE FROM payroll_run_details WHERE run_id = :rid")->execute(['rid' => $runId]);
                $insDet = $db->prepare("INSERT INTO payroll_run_details (run_id, component_id, label, type, amount) VALUES (:rid, :cid, :l, :t, :a)");
                foreach ($details as $d) {
                    $insDet->execute(['rid' => $runId, 'cid' => $d['component_id'], 'l' => $d['label'], 't' => $d['type'], 'a' => $d['amount']]);
                }
            }

            $db->prepare("UPDATE payroll_periods SET status = 'processed', processed_at = NOW(), processed_by = :pb WHERE id = :id")
                ->execute(['pb' => (int)($_SESSION['user_id'] ?? 0), 'id' => $periodId]);

            $db->commit();
            Security::setFlash('ok', 'Payroll processed for ' . count($staffList) . ' employees');
        } catch (Throwable $e) {
            $db->rollBack();
            Security::setFlash('error', $e->getMessage());
        }
        header('Location: ' . $this->appUrl() . '/payroll/process?period_id=' . $periodId);
        exit;
    }

    private function calculatePAYE(float $taxableIncome, array $brackets): float {
        $tax = 0.0;
        foreach ($brackets as $b) {
            $from = (float)$b['from_amount'];
            $to = $b['to_amount'] !== null ? (float)$b['to_amount'] : PHP_FLOAT_MAX;
            $rate = (float)$b['rate'] / 100;
            if ($taxableIncome > $from) {
                $bandIncome = min($taxableIncome, $to) - $from;
                if ($bandIncome > 0) {
                    $tax += $bandIncome * $rate;
                }
            }
            if ($taxableIncome <= $to) break;
        }
        return round($tax, 2);
    }

    public function payslips(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $role = (string)($_SESSION['user_role'] ?? 'Staff');

        $periods = $db->query("SELECT * FROM payroll_periods WHERE status = 'processed' ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
        $periodId = (int)($request->getParam('period_id') ?? 0);
        $runs = [];

        if ($periodId > 0) {
            if (in_array($role, ['Super Admin', 'HR Manager'], true)) {
                $stmt = $db->prepare("SELECT pr.*, u.fullname, s.staff_id_card, d.dept_name FROM payroll_runs pr JOIN users u ON pr.user_id = u.id LEFT JOIN staff_records s ON s.user_id = u.id LEFT JOIN departments d ON d.dept_id = s.dept_id WHERE pr.period_id = :pid ORDER BY u.fullname");
            } else {
                $stmt = $db->prepare("SELECT pr.*, u.fullname, s.staff_id_card, d.dept_name FROM payroll_runs pr JOIN users u ON pr.user_id = u.id LEFT JOIN staff_records s ON s.user_id = u.id LEFT JOIN departments d ON d.dept_id = s.dept_id WHERE pr.period_id = :pid AND pr.user_id = :uid ORDER BY u.fullname");
                $stmt->bindValue('uid', $userId, PDO::PARAM_INT);
            }
            $stmt->bindValue('pid', $periodId, PDO::PARAM_INT);
            $stmt->execute();
            $runs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $pageTitle = 'HRGoTo HCM - Payslips';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/payroll/payslips.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function payslipPdf(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $role = (string)($_SESSION['user_role'] ?? 'Staff');
        $runId = (int)($request->getParam('id') ?? 0);

        $stmt = $db->prepare("SELECT pr.*, u.fullname, u.email, s.staff_id_card, s.date_joined, s.gender, d.dept_name, dg.title AS designation, pp.period_label, pp.start_date, pp.end_date FROM payroll_runs pr JOIN users u ON pr.user_id = u.id LEFT JOIN staff_records s ON s.user_id = u.id LEFT JOIN departments d ON d.dept_id = s.dept_id LEFT JOIN designations dg ON dg.id = s.designation_id JOIN payroll_periods pp ON pp.id = pr.period_id WHERE pr.id = :rid");
        $stmt->execute(['rid' => $runId]);
        $run = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$run) { http_response_code(404); exit; }
        if (!in_array($role, ['Super Admin', 'HR Manager'], true) && (int)$run['user_id'] !== $userId) { http_response_code(403); exit; }

        $details = $db->prepare("SELECT * FROM payroll_run_details WHERE run_id = :rid");
        $details->execute(['rid' => $runId]);
        $lines = $details->fetchAll(PDO::FETCH_ASSOC);
        $company = $db->query("SELECT company_name, company_address, company_logo_url FROM app_config LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('HRGoTo HCM');
        $pdf->SetTitle('Payslip - ' . $run['fullname'] . ' - ' . $run['period_label']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        $html = '<h2 style="color:#162C5B;text-align:center;">' . \App\Helpers\Security::escape($company['company_name'] ?? 'HRGoTo HCM') . '</h2>';
        $html .= '<p style="text-align:center;font-size:9pt;">' . \App\Helpers\Security::escape($company['company_address'] ?? '') . '</p>';
        $html .= '<hr><h3 style="text-align:center;">PAYSLIP</h3>';
        $html .= '<p style="text-align:center;font-size:10pt;"><strong>Period:</strong> ' . \App\Helpers\Security::escape($run['period_label']) . ' (' . $run['start_date'] . ' to ' . $run['end_date'] . ')</p>';
        $html .= '<hr>';
        $html .= '<table border="1" cellpadding="4" cellspacing="0" style="font-size:9pt;"><tr><td width="50%"><b>Employee:</b> ' . \App\Helpers\Security::escape($run['fullname']) . '</td><td><b>Staff ID:</b> ' . \App\Helpers\Security::escape($run['staff_id_card'] ?? 'N/A') . '</td></tr>';
        $html .= '<tr><td><b>Department:</b> ' . \App\Helpers\Security::escape($run['dept_name'] ?? 'N/A') . '</td><td><b>Designation:</b> ' . \App\Helpers\Security::escape($run['designation'] ?? 'N/A') . '</td></tr></table>';
        $html .= '<br><h4>Earnings</h4><table border="1" cellpadding="4" cellspacing="0" style="font-size:9pt;"><tr style="background:#e2e8f0;"><td><b>Description</b></td><td align="right"><b>Amount (GHS)</b></td></tr>';
        $earningsTotal = 0;
        foreach ($lines as $l) {
            if ($l['type'] === 'earnings') {
                $html .= '<tr><td>' . \App\Helpers\Security::escape($l['label']) . '</td><td align="right">' . number_format((float)$l['amount'], 2) . '</td></tr>';
                $earningsTotal += (float)$l['amount'];
            }
        }
        $html .= '<tr style="font-weight:bold;"><td>Gross Pay</td><td align="right">' . number_format($earningsTotal, 2) . '</td></tr></table>';
        $html .= '<br><h4>Deductions</h4><table border="1" cellpadding="4" cellspacing="0" style="font-size:9pt;"><tr style="background:#e2e8f0;"><td><b>Description</b></td><td align="right"><b>Amount (GHS)</b></td></tr>';
        foreach ($lines as $l) {
            if ($l['type'] === 'deduction') {
                $html .= '<tr><td>' . \App\Helpers\Security::escape($l['label']) . '</td><td align="right">' . number_format((float)$l['amount'], 2) . '</td></tr>';
            }
        }
        $html .= '<tr><td>SSNIT Employee (5.5%)</td><td align="right">' . number_format($run['ssnit_employee'], 2) . '</td></tr>';
        $html .= '<tr><td>PAYE Tax</td><td align="right">' . number_format($run['paye_tax'], 2) . '</td></tr>';
        $html .= '<tr style="font-weight:bold;"><td>Total Deductions</td><td align="right">' . number_format($run['total_deductions'] + $run['ssnit_employee'] + $run['paye_tax'], 2) . '</td></tr></table>';
        $html .= '<br><table border="1" cellpadding="6" cellspacing="0" style="font-size:11pt;"><tr style="background:#162C5B;color:#fff;"><td align="center"><b>NET PAY</b></td><td align="center"><b>GHS ' . number_format($run['net_pay'], 2) . '</b></td></tr></table>';
        $html .= '<br><hr><p style="text-align:center;font-size:8pt;color:#666;">Generated by HRGoTo HCM on ' . date('Y-m-d H:i:s') . '</p>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Payslip_' . $run['staff_id_card'] . '_' . $run['period_label'] . '.pdf', 'D');
        exit;
    }

    public function reports(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensurePayrollTables();
        $db = Database::getConnection();

        $periods = $db->query("SELECT * FROM payroll_periods WHERE status = 'processed' ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
        $periodId = (int)($request->getParam('period_id') ?? 0);
        $summary = [];
        $grandTotal = ['staff' => 0, 'gross' => 0, 'allowances' => 0, 'deductions' => 0, 'ssnit_emp' => 0, 'ssnit_empr' => 0, 'paye' => 0, 'net' => 0];

        if ($periodId > 0) {
            $stmt = $db->prepare("SELECT pr.*, u.fullname, s.staff_id_card, d.dept_name FROM payroll_runs pr JOIN users u ON pr.user_id = u.id LEFT JOIN staff_records s ON s.user_id = u.id LEFT JOIN departments d ON d.dept_id = s.dept_id WHERE pr.period_id = :pid ORDER BY d.dept_name, u.fullname");
            $stmt->execute(['pid' => $periodId]);
            $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($summary as $r) {
                $grandTotal['staff']++;
                $grandTotal['gross'] += (float)$r['gross_pay'];
                $grandTotal['allowances'] += (float)$r['total_allowances'];
                $grandTotal['deductions'] += (float)$r['total_deductions'];
                $grandTotal['ssnit_emp'] += (float)$r['ssnit_employee'];
                $grandTotal['ssnit_empr'] += (float)$r['ssnit_employer'];
                $grandTotal['paye'] += (float)$r['paye_tax'];
                $grandTotal['net'] += (float)$r['net_pay'];
            }
        }

        $pageTitle = 'HRGoTo HCM - Payroll Reports';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/payroll/reports.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function deductions(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $periods = $db->query("SELECT id, period_label FROM payroll_periods ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
        $deductions = $db->query("SELECT pd.*, u.fullname FROM payroll_deductions pd JOIN users u ON pd.user_id = u.id ORDER BY pd.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        $staff = $db->query("SELECT u.id, u.fullname, s.staff_id_card FROM users u JOIN staff_records s ON s.user_id = u.id WHERE u.deleted_at IS NULL ORDER BY u.fullname")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'HRGoTo HCM - Deductions';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/payroll/deductions.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function bankDetails(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $staff = $db->query("SELECT u.id, u.fullname, s.staff_id_card, d.dept_name FROM users u JOIN staff_records s ON s.user_id = u.id LEFT JOIN departments d ON d.dept_id = s.dept_id WHERE u.deleted_at IS NULL ORDER BY u.fullname")->fetchAll(PDO::FETCH_ASSOC);
        $staffMap = [];
        foreach ($staff as $s) { $staffMap[(int)$s['id']] = $s; }
        $bankDetails = $db->query("SELECT sbd.*, u.fullname FROM staff_bank_details sbd JOIN users u ON u.id = sbd.user_id ORDER BY u.fullname")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'HRGoTo HCM - Bank Details';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/payroll/bank_details.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function saveBankDetails(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $body = $request->getBody();
        $userId = (int)($body['user_id'] ?? 0);

        try {
            $stmt = $db->prepare("INSERT INTO staff_bank_details (user_id, bank_name, branch, account_number, account_name, account_type, mobile_money_provider, mobile_money_number, payment_method) VALUES (:uid, :bn, :br, :an, :acn, :at, :mp, :mn, :pm) ON DUPLICATE KEY UPDATE bank_name=:bn2, branch=:br2, account_number=:an2, account_name=:acn2, account_type=:at2, mobile_money_provider=:mp2, mobile_money_number=:mn2, payment_method=:pm2");
            $stmt->execute([
                'uid' => $userId, 'bn' => $body['bank_name'] ?? '', 'br' => $body['branch'] ?? '', 'an' => $body['account_number'] ?? '', 'acn' => $body['account_name'] ?? '', 'at' => $body['account_type'] ?? 'savings', 'mp' => $body['mobile_money_provider'] ?? '', 'mn' => $body['mobile_money_number'] ?? '', 'pm' => $body['payment_method'] ?? 'bank_transfer',
                'bn2' => $body['bank_name'] ?? '', 'br2' => $body['branch'] ?? '', 'an2' => $body['account_number'] ?? '', 'acn2' => $body['account_name'] ?? '', 'at2' => $body['account_type'] ?? 'savings', 'mp2' => $body['mobile_money_provider'] ?? '', 'mn2' => $body['mobile_money_number'] ?? '', 'pm2' => $body['payment_method'] ?? 'bank_transfer',
            ]);
            Security::setFlash('ok', 'Bank details saved');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
        }
        header('Location: ' . $this->appUrl() . '/payroll/bank-details');
        exit;
    }

    public function bankTransferReport(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $periodId = (int)($request->getParam('period_id') ?? 0);
        $export = $request->getParam('export', '');

        $periods = $db->query("SELECT * FROM payroll_periods WHERE status = 'processed' ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
        $rows = [];
        $companyName = '';
        $companyStmt = $db->query("SELECT company_name FROM app_config LIMIT 1");
        if ($c = $companyStmt->fetch(PDO::FETCH_ASSOC)) $companyName = $c['company_name'] ?? '';

        if ($periodId > 0) {
            $stmt = $db->prepare("SELECT pr.*, u.fullname, s.staff_id_card, d.dept_name, sbd.bank_name, sbd.branch, sbd.account_number, sbd.account_name, sbd.mobile_money_provider, sbd.mobile_money_number, sbd.payment_method FROM payroll_runs pr JOIN users u ON pr.user_id = u.id LEFT JOIN staff_records s ON s.user_id = u.id LEFT JOIN departments d ON d.dept_id = s.dept_id LEFT JOIN staff_bank_details sbd ON sbd.user_id = u.id WHERE pr.period_id = :pid ORDER BY u.fullname");
            $stmt->execute(['pid' => $periodId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get period info for filename
            $pStmt = $db->prepare("SELECT period_label FROM payroll_periods WHERE id = :id");
            $pStmt->execute(['id' => $periodId]);
            $periodLabel = $pStmt->fetchColumn() ?: 'export';
        }

        // CSV export
        if ($export === 'csv' && $periodId > 0 && !empty($rows)) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="Bank_Transfer_' . str_replace(['/', ' '], '_', $periodLabel) . '.csv"');
            header('Pragma: no-cache');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM for Excel
            fputcsv($out, ['Employee', 'Staff ID', 'Department', 'Bank', 'Branch', 'Account Name', 'Account Number', 'Payment Method', 'Mobile Money', 'Net Pay (GHS)']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['fullname'],
                    $r['staff_id_card'] ?? '',
                    $r['dept_name'] ?? '',
                    $r['bank_name'] ?? '',
                    $r['branch'] ?? '',
                    $r['account_name'] ?? '',
                    $r['account_number'] ?? '',
                    $r['payment_method'] ?? 'bank_transfer',
                    $r['payment_method'] === 'mobile_money' ? ($r['mobile_money_provider'] . ' ' . $r['mobile_money_number']) : '',
                    number_format((float)$r['net_pay'], 2),
                ]);
            }
            fclose($out);
            exit;
        }

        $pageTitle = 'HRGoTo HCM - Bank Transfer Report';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/payroll/bank_transfer_report.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function saveDeduction(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $body = $request->getBody();

        try {
            $total = (float)($body['total_amount'] ?? 0);
            $installments = max((int)($body['installments'] ?? 1), 1);
            $instAmount = round($total / $installments, 2);

            $db->prepare("INSERT INTO payroll_deductions (user_id, name, total_amount, installment_amount, remaining_amount, start_period_id, created_by) VALUES (:uid, :n, :ta, :ia, :ra, :sp, :cb)")
                ->execute([
                    'uid' => (int)$body['user_id'], 'n' => $body['name'], 'ta' => $total, 'ia' => $instAmount,
                    'ra' => $total, 'sp' => !empty($body['start_period_id']) ? (int)$body['start_period_id'] : null,
                    'cb' => (int)($_SESSION['user_id'] ?? 0),
                ]);
            Security::setFlash('ok', 'Deduction saved');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
        }
        header('Location: ' . $this->appUrl() . '/payroll/deductions');
        exit;
    }

    // -----------------------------------------------------------------------
    // BATCH 9: BENEFITS & INCENTIVES
    // -----------------------------------------------------------------------

    public function benefits(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensurePayrollTables();
        $db = Database::getConnection();

        $benefitTypes = $db->query("SELECT * FROM benefit_types ORDER BY category, name")->fetchAll(PDO::FETCH_ASSOC);
        $staff = $db->query("SELECT u.id, u.fullname, s.staff_id_card, d.dept_name FROM users u JOIN staff_records s ON s.user_id = u.id LEFT JOIN departments d ON d.dept_id = s.dept_id WHERE u.deleted_at IS NULL ORDER BY u.fullname")->fetchAll(PDO::FETCH_ASSOC);

        $userId = (int)($request->getParam('user_id') ?? 0);
        $assignments = [];
        if ($userId > 0) {
            $stmt = $db->prepare("SELECT eb.*, bt.name AS benefit_name, bt.category FROM employee_benefits eb JOIN benefit_types bt ON bt.id = eb.benefit_type_id WHERE eb.user_id = :uid ORDER BY bt.category");
            $stmt->execute(['uid' => $userId]);
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $pageTitle = 'HRGoTo HCM - Benefits & Incentives';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/payroll/benefits.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function saveBenefitType(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $body = $request->getBody();
        $id = (int)($body['id'] ?? 0);

        try {
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE benefit_types SET name=:n, category=:c, default_amount=:da, calculation_type=:ct, is_taxable=:itx, is_ssnit_liable=:isl, is_active=:ia WHERE id=:id");
                $stmt->execute(['n' => $body['name'], 'c' => $body['category'], 'da' => (float)($body['default_amount'] ?? 0), 'ct' => $body['calculation_type'], 'itx' => (int)($body['is_taxable'] ?? 0), 'isl' => (int)($body['is_ssnit_liable'] ?? 0), 'ia' => (int)($body['is_active'] ?? 1), 'id' => $id]);
            } else {
                $stmt = $db->prepare("INSERT INTO benefit_types (name, category, default_amount, calculation_type, is_taxable, is_ssnit_liable, is_active) VALUES (:n, :c, :da, :ct, :itx, :isl, :ia)");
                $stmt->execute(['n' => $body['name'], 'c' => $body['category'], 'da' => (float)($body['default_amount'] ?? 0), 'ct' => $body['calculation_type'], 'itx' => (int)($body['is_taxable'] ?? 0), 'isl' => (int)($body['is_ssnit_liable'] ?? 0), 'ia' => (int)($body['is_active'] ?? 1)]);
            }
            Security::setFlash('ok', 'Benefit type saved');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
        }
        header('Location: ' . $this->appUrl() . '/payroll/benefits');
        exit;
    }

    public function assignEmployeeBenefit(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $body = $request->getBody();
        $userId = (int)($body['user_id'] ?? 0);
        $benefitTypeId = (int)($body['benefit_type_id'] ?? 0);
        $amount = (float)($body['amount'] ?? 0);

        try {
            $existing = $db->prepare("SELECT id FROM employee_benefits WHERE user_id = :uid AND benefit_type_id = :btid");
            $existing->execute(['uid' => $userId, 'btid' => $benefitTypeId]);
            if ($existing->fetch()) {
                $db->prepare("UPDATE employee_benefits SET amount = :amt WHERE user_id = :uid AND benefit_type_id = :btid")
                    ->execute(['amt' => $amount, 'uid' => $userId, 'btid' => $benefitTypeId]);
            } else {
                $db->prepare("INSERT INTO employee_benefits (user_id, benefit_type_id, amount) VALUES (:uid, :btid, :amt)")
                    ->execute(['uid' => $userId, 'btid' => $benefitTypeId, 'amt' => $amount]);
            }
            Security::setFlash('ok', 'Benefit assigned');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
        }
        header('Location: ' . $this->appUrl() . '/payroll/benefits?user_id=' . $userId);
        exit;
    }

    public function removeEmployeeBenefit(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        CSRFMiddleware::validate($request);
        $this->ensurePayrollTables();
        $db = Database::getConnection();
        $body = $request->getBody();
        $id = (int)($body['id'] ?? 0);
        $userId = (int)($body['user_id'] ?? 0);

        try {
            $db->prepare("DELETE FROM employee_benefits WHERE id = :id")->execute(['id' => $id]);
            Security::setFlash('ok', 'Benefit removed');
        } catch (Throwable $e) {
            Security::setFlash('error', $e->getMessage());
        }
        header('Location: ' . $this->appUrl() . '/payroll/benefits?user_id=' . $userId);
        exit;
    }

    public function benefitsReport(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensurePayrollTables();
        $db = Database::getConnection();

        $benefitTypes = $db->query("SELECT * FROM benefit_types ORDER BY category, name")->fetchAll(PDO::FETCH_ASSOC);

        $export = $request->getParam('export', '');
        $rows = [];
        $grandTotal = [];

        if (!empty($benefitTypes)) {
            $benefits = $db->query("SELECT eb.*, bt.name AS benefit_name, bt.category, u.fullname, s.staff_id_card, d.dept_name FROM employee_benefits eb JOIN benefit_types bt ON bt.id = eb.benefit_type_id JOIN users u ON eb.user_id = u.id LEFT JOIN staff_records s ON s.user_id = u.id LEFT JOIN departments d ON d.dept_id = s.dept_id WHERE (eb.effective_from IS NULL OR eb.effective_from <= CURDATE()) AND (eb.effective_to IS NULL OR eb.effective_to >= CURDATE()) ORDER BY d.dept_name, u.fullname, bt.category")
                ->fetchAll(PDO::FETCH_ASSOC);

            foreach ($benefits as $b) {
                $dept = $b['dept_name'] ?? 'N/A';
                if (!isset($rows[$dept])) {
                    $rows[$dept] = ['department' => $dept, 'employees' => [], 'totals' => []];
                }
                $uid = (int)$b['user_id'];
                if (!isset($rows[$dept]['employees'][$uid])) {
                    $rows[$dept]['employees'][$uid] = [
                        'fullname' => $b['fullname'],
                        'staff_id_card' => $b['staff_id_card'] ?? '',
                        'benefits' => [],
                        'total' => 0.0,
                    ];
                }
                $cat = $b['category'];
                $amt = (float)$b['amount'];
                $rows[$dept]['employees'][$uid]['benefits'][] = $b;
                $rows[$dept]['employees'][$uid]['total'] += $amt;
                if (!isset($rows[$dept]['totals'][$cat])) {
                    $rows[$dept]['totals'][$cat] = 0.0;
                }
                $rows[$dept]['totals'][$cat] += $amt;
                if (!isset($grandTotal[$cat])) {
                    $grandTotal[$cat] = 0.0;
                }
                $grandTotal[$cat] += $amt;
                $grandTotal['overall'] = ($grandTotal['overall'] ?? 0) + $amt;
            }
        }

        if ($export === 'csv' && !empty($rows)) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="Benefits_Report_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Department', 'Employee', 'Staff ID', 'Benefit Type', 'Category', 'Amount (GHS)']);
            foreach ($rows as $dept => $data) {
                foreach ($data['employees'] as $emp) {
                    foreach ($emp['benefits'] as $b) {
                        fputcsv($out, [$dept, $emp['fullname'], $emp['staff_id_card'], $b['benefit_name'], $b['category'], number_format((float)$b['amount'], 2)]);
                    }
                }
            }
            fclose($out);
            exit;
        }

        $pageTitle = 'HRGoTo HCM - Benefits Report';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/payroll/benefits_report.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    public function analysis(Request $request): void {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager']);
        $this->ensurePayrollTables();
        $db = Database::getConnection();

        $processedPeriods = $db->query("SELECT id, period_label, start_date, end_date FROM payroll_periods WHERE status IN ('processed','closed') ORDER BY start_date ASC")->fetchAll(PDO::FETCH_ASSOC);
        $periodIds = array_column($processedPeriods, 'id');

        // 1. Department cost trends (by period, by department)
        $deptData = [];
        $deptNameMap = [];
        if (!empty($periodIds)) {
            $placeholders = implode(',', array_fill(0, count($periodIds), '?'));
            $stmt = $db->prepare("SELECT d.dept_id, d.dept_name, pr.period_id, pp.period_label, COUNT(DISTINCT pr.user_id) AS headcount, COALESCE(SUM(pr.gross_pay),0) AS total_gross, COALESCE(SUM(pr.net_pay),0) AS total_net, COALESCE(SUM(pr.ssnit_employee + pr.ssnit_employer + pr.paye_tax),0) AS total_tax, COALESCE(SUM(pr.total_deductions),0) AS total_deductions FROM payroll_runs pr JOIN payroll_periods pp ON pp.id = pr.period_id LEFT JOIN users u ON u.id = pr.user_id LEFT JOIN staff_records s ON s.user_id = u.id LEFT JOIN departments d ON d.dept_id = s.dept_id WHERE pr.period_id IN ($placeholders) GROUP BY d.dept_id, pr.period_id ORDER BY d.dept_name, pp.start_date");
            $stmt->execute($periodIds);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $dept = $r['dept_name'] ?? 'Unassigned';
                $deptNameMap[$dept] = true;
                $deptData[$r['period_id']]['period_label'] = $r['period_label'];
                $deptData[$r['period_id']]['departments'][$dept] = [
                    'headcount' => (int)$r['headcount'],
                    'gross' => (float)$r['total_gross'],
                    'net' => (float)$r['total_net'],
                    'tax' => (float)$r['total_tax'],
                    'deductions' => (float)$r['total_deductions'],
                ];
            }
        }
        $deptNames = array_keys($deptNameMap);
        sort($deptNames);

        // 2. Period-over-period totals for forecasting
        $periodTotals = [];
        foreach ($processedPeriods as $p) {
            $pid = $p['id'];
            $gross = 0; $net = 0; $tax = 0; $ded = 0;
            if (isset($deptData[$pid]['departments'])) {
                foreach ($deptData[$pid]['departments'] as $d) {
                    $gross += $d['gross'];
                    $net += $d['net'];
                    $tax += $d['tax'];
                    $ded += $d['deductions'];
                }
            }
            $periodTotals[] = [
                'period_label' => $p['period_label'],
                'gross' => $gross,
                'net' => $net,
                'tax' => $tax,
                'deductions' => $ded,
            ];
        }

        // 3. Simple budget forecast (linear projection of net pay)
        $forecast = [];
        $fcLabels = [];
        $fcValues = [];
        if (count($periodTotals) >= 2) {
            $changes = [];
            for ($i = 1; $i < count($periodTotals); $i++) {
                $changes[] = $periodTotals[$i]['net'] - $periodTotals[$i - 1]['net'];
            }
            $avgChange = count($changes) > 0 ? array_sum($changes) / count($changes) : 0;
            $lastNet = $periodTotals[count($periodTotals) - 1]['net'];
            for ($i = 1; $i <= 3; $i++) {
                $projected = $lastNet + ($avgChange * $i);
                $label = 'Forecast +' . $i;
                $forecast[] = ['label' => $label, 'projected_net' => round($projected, 2)];
                $fcLabels[] = $label;
                $fcValues[] = round($projected, 2);
            }
        }

        // 4. Attrition cost analysis (users paid in period N but not in N+1)
        $attritionData = [];
        for ($i = 0; $i < count($processedPeriods) - 1; $i++) {
            $prev = $processedPeriods[$i];
            $curr = $processedPeriods[$i + 1];
            $stmt = $db->prepare("SELECT pr.user_id, pr.gross_pay, pr.net_pay, u.fullname, s.staff_id_card, COALESCE(d.dept_name,'Unassigned') AS dept_name FROM payroll_runs pr JOIN users u ON u.id = pr.user_id LEFT JOIN staff_records s ON s.user_id = u.id LEFT JOIN departments d ON d.dept_id = s.dept_id WHERE pr.period_id = :prev AND pr.user_id NOT IN (SELECT user_id FROM payroll_runs WHERE period_id = :curr)");
            $stmt->execute(['prev' => $prev['id'], 'curr' => $curr['id']]);
            $left = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($left)) {
                $attritionData[] = [
                    'from_period' => $prev['period_label'],
                    'to_period' => $curr['period_label'],
                    'count' => count($left),
                    'total_gross' => round(array_sum(array_column($left, 'gross_pay')), 2),
                    'total_net' => round(array_sum(array_column($left, 'net_pay')), 2),
                    'employees' => $left,
                ];
            }
        }

        $pageTitle = 'HRGoTo HCM - Payroll Analysis & Forecasting';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/payroll/analysis.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }

    private function appUrl(): string {
        return rtrim(\App\Helpers\Security::escape($_ENV['APP_URL'] ?? ''), '/');
    }
}
