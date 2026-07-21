<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Middleware\AuthMiddleware;

class ManualController {
    public function __construct() {
        AuthMiddleware::checkRole(['Super Admin', 'HR Manager', 'Supervisor', 'Staff']);
    }

    public function index(Request $request): void {
        $pageTitle = 'HRGoTo HCM - User Guide';
        require_once __DIR__ . '/../Views/dashboard/layouts/header.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/sidebar.php';
        require_once __DIR__ . '/../Views/manual/index.php';
        require_once __DIR__ . '/../Views/dashboard/layouts/footer.php';
    }
}
