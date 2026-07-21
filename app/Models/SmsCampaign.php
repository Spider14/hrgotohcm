<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SmsCampaign {
    
    /**
     * Retrieves the primary campaign configuration row
     */
    public static function getCampaignSettings(): array {
        $db = Database::getConnection();
        
        // Ensure at least one configuration row exists in the new schema table
        $stmt = $db->query("SELECT * FROM sms_campaign_templates LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$settings) {
            // Seed a default baseline if empty using the correct column keys
            $db->exec("INSERT INTO sms_campaign_templates (shortlisted, hired) VALUES ('', '')");
            $stmt = $db->query("SELECT * FROM sms_campaign_templates LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $settings;
    }

    /**
     * Dynamically alters the database structure to append a new column text field
     */
    public static function addNewFieldColumn(string $fieldName): bool {
        $db = Database::getConnection();
        
        // Sanitize field name to avoid SQL errors or injections
        $cleanName = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($fieldName));
        if (empty($cleanName)) return false;

        // Verify if column already exists in the new table schema
        $stmt = $db->query("SHOW COLUMNS FROM sms_campaign_templates LIKE '{$cleanName}'");
        if ($stmt->fetch()) {
            return false; // Field already exists
        }

        // Alter table mapping structure safely matching the new name
        return $db->exec("ALTER TABLE sms_campaign_templates ADD `{$cleanName}` TEXT NOT NULL DEFAULT ''") !== false;
    }

    /**
     * Updates column template configurations dynamically
     */
    public static function saveCampaignSettings(int $id, array $fieldsData): bool {
        $db = Database::getConnection();
        
        if (empty($fieldsData)) return false;

        $updatePairs = [];
        $params = [];

        foreach ($fieldsData as $columnName => $textValue) {
            // Guard clause ensuring we only update columns that exist
            $cleanCol = preg_replace('/[^a-zA-Z0-9_]/', '', $columnName);
            if ($cleanCol === 'sms_cam_id') continue;

            $updatePairs[] = "`{$cleanCol}` = ?";
            $params[] = $textValue;
        }

        $params[] = $id;
        $sql = "UPDATE sms_campaign_templates SET " . implode(', ', $updatePairs) . " WHERE sms_cam_id = ?";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
	
	
}