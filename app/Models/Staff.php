<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Staff {
    /**
     * Build an optimized, dynamically filtered dataset using server-side pagination logic.
     */
    public static function getFilteredList(array $filters, int $page, int $perPage = 10): array {
        $db = Database::getConnection();
        $offset = ($page - 1) * $perPage;
        
        // Base Query joining core identity frames with demographic mapping frames
        $baseSql = "FROM staff_records s
            JOIN users u ON s.user_id = u.id
            JOIN roles r ON u.role_id = r.id
            JOIN departments d ON s.dept_id = d.dept_id
            JOIN designations dg ON s.designation_id = dg.id 
            WHERE u.deleted_at IS NULL";
			
        $params = [];
        
        // Match Search Query against Name or Staff ID Card Number
        if (!empty($filters['search'])) {
            $baseSql .= " AND (u.fullname LIKE :search OR s.staff_id_card LIKE :search_id)";
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search_id'] = '%' . $filters['search'] . '%';
        }
        
        // Filter strictly by department assignment
        if (!empty($filters['department_id'])) {
            $baseSql .= " AND s.dept_id = :dept_id";
            $params['dept_id'] = (int)$filters['department_id'];
        }
        
        // Filter by employment status category
        if (!empty($filters['status'])) {
            $baseSql .= " AND s.employment_status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['supervisor_user_id'])) {
            $baseSql .= " AND s.supervisor_user_id = :supervisor_user_id";
            $params['supervisor_user_id'] = (int)$filters['supervisor_user_id'];
        }

        // 1. Compute exact match count metrics for pagination offsets
        $countQuery = "SELECT COUNT(*) " . $baseSql;
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();
        $totalPages = (int)ceil($totalRecords / $perPage);

        // 2. Extract paginated data subset
        $dataQuery = "SELECT s.*, u.fullname, u.email, u.status as user_account_status, r.role_name, d.dept_name, d.dept_code, dg.title as designation_title, dg.category as designation_category " 
             . $baseSql . " ORDER BY u.fullname ASC LIMIT :limit OFFSET :offset";            
        
		$stmt = $db->prepare($dataQuery);
        
        // Explicit binding safeguards against strict string parameter degradation
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data'         => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total_pages'  => $totalPages,
            'current_page' => $page,
            'total_rows'   => $totalRecords
        ];
    }

    /**
     * Compute top-level staff status summary metrics for dashboard cards.
     */
    public static function getStatusSummary(?int $supervisorUserId = null): array {
        $db = Database::getConnection();

        $sql = "
            SELECT
                COUNT(*) AS total_staff,
                SUM(CASE WHEN s.employment_status = 'Permanent' THEN 1 ELSE 0 END) AS permanent_staff,
                SUM(CASE WHEN s.employment_status = 'Contract' THEN 1 ELSE 0 END) AS contract_staff,
                SUM(CASE WHEN s.employment_status = 'Probation' THEN 1 ELSE 0 END) AS probation_staff,
                SUM(CASE WHEN s.employment_status = 'Suspended' THEN 1 ELSE 0 END) AS suspended_staff
            FROM staff_records s
            JOIN users u ON s.user_id = u.id
            WHERE u.deleted_at IS NULL
        ";

        $params = [];
        if ($supervisorUserId !== null && $supervisorUserId > 0) {
            $sql .= " AND s.supervisor_user_id = :supervisor_user_id";
            $params['supervisor_user_id'] = $supervisorUserId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int)($row['total_staff'] ?? 0),
            'permanent' => (int)($row['permanent_staff'] ?? 0),
            'contract' => (int)($row['contract_staff'] ?? 0),
            'probation' => (int)($row['probation_staff'] ?? 0),
            'suspended' => (int)($row['suspended_staff'] ?? 0),
        ];
    }
}