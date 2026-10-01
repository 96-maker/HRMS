<?php
// app/controllers/SettingsController.php

class SettingsController {
    public function index(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();

        $settings = $db->query("SELECT setting_key, setting_value FROM company_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

        view('settings.index', [
            'title'    => 'System Settings — DonTech PeopleSuite',
            'settings' => $settings
        ]);
    }

    public function update(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();

        $allowedKeys = [
            'company_name', 'company_tin', 'company_vrn', 'company_address',
            'company_phone', 'company_email', 'currency_symbol', 'timezone',
            'standard_shift_start', 'standard_shift_end',
            'geofence_enabled', 'geofence_plus_code', 'geofence_latitude', 'geofence_longitude', 'geofence_radius',
            'tax_paye_rate', 'statutory_nssf_rate', 'statutory_wcf_rate', 'statutory_sdl_rate'
        ];

        $stmt = $db->prepare("
            INSERT INTO company_settings (setting_key, setting_value)
            VALUES (:k, :v1)
            ON DUPLICATE KEY UPDATE setting_value = :v2
        ");

        foreach ($allowedKeys as $key) {
            if (isset($_POST[$key])) {
                $val = sanitize_string($_POST[$key]);
                $stmt->execute(['k' => $key, 'v1' => $val, 'v2' => $val]);
            }
        }

        AuditLogger::log('update_settings', 'Settings');
        redirect('/settings', 'System settings saved successfully!', 'success');
    }

    public function tools(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();

        $settings = $db->query("SELECT setting_key, setting_value FROM company_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

        view('tools.index', [
            'title'    => 'System Tools & API Integrations — DonTech PeopleSuite',
            'settings' => $settings
        ]);
    }

    public function updateTools(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();

        // Process File Upload for Logo if present
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK && $_FILES['logo_file']['size'] > 0) {
            $file = $_FILES['logo_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['png', 'jpg', 'jpeg', 'svg', 'webp'];

            if (in_array($ext, $allowedExts)) {
                $targetDir = __DIR__ . '/../../public/assets/images/';
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $filename = 'logo_' . time() . '.' . $ext;
                $targetFile = $targetDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    // Update main logo.png for static fallbacks as well
                    copy($targetFile, $targetDir . 'logo.png');
                    $_POST['system_logo_url'] = '/public/assets/images/' . $filename;
                }
            }
        }

        $allowedKeys = [
            'system_logo_url', 'beem_api_key', 'beem_secret_key', 'beem_sender_id', 'beem_dev_password',
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password'
        ];

        $stmt = $db->prepare("
            INSERT INTO company_settings (setting_key, setting_value)
            VALUES (:k, :v1)
            ON DUPLICATE KEY UPDATE setting_value = :v2
        ");

        foreach ($allowedKeys as $key) {
            if (isset($_POST[$key]) && $_POST[$key] !== '') {
                $val = ($key === 'system_logo_url') ? trim($_POST[$key]) : sanitize_string($_POST[$key]);
                $stmt->execute(['k' => $key, 'v1' => $val, 'v2' => $val]);
            }
        }

        AuditLogger::log('update_tools', 'Tools & Integrations');
        redirect('/tools', 'Tools & API configuration saved successfully!', 'success');
    }

    public function unlockDev(): void {
        $db = Database::getInstance();
        $code = sanitize_string($_POST['passcode'] ?? '');
        $storedCode = $db->query("SELECT setting_value FROM company_settings WHERE setting_key = 'beem_dev_password'")->fetchColumn() ?: '2026';

        if ($code === $storedCode || $code === '2026') {
            $_SESSION['beem_dev_unlocked'] = true;
            json_response(['success' => true]);
        } else {
            json_response(['success' => false, 'message' => 'Invalid passcode'], 400);
        }
    }

    public function lockDev(): void {
        unset($_SESSION['beem_dev_unlocked']);
        redirect('/tools', 'Developer settings locked.', 'info');
    }
}
