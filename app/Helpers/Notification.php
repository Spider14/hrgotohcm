<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;

class Notification
{
    public static function create(int $userId, string $title, string $message, string $type = 'info'): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (:user_id, :title, :message, :type)");
        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
        ]);
    }
}
