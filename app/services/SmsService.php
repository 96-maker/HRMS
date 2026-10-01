<?php
// app/services/SmsService.php

class SmsService {
    public static function sendSms(string $recipientPhone, string $message): bool {
        $db = Database::getInstance();
        $settings = $db->query("SELECT setting_key, setting_value FROM company_settings WHERE setting_key IN ('beem_api_key', 'beem_secret_key', 'beem_sender_id')")->fetchAll(PDO::FETCH_KEY_PAIR);

        $apiKey    = $settings['beem_api_key'] ?? '';
        $secretKey = $settings['beem_secret_key'] ?? '';
        $senderId  = $settings['beem_sender_id'] ?? 'DONTECH_HR';

        if (empty($apiKey) || empty($secretKey)) {
            // Log warning: SMS credentials not set
            error_log("Beem SMS skipped: API Key or Secret Key missing.");
            return false;
        }

        // Format phone number to international format (255XXXXXXXXX)
        $cleanDigits = preg_replace('/\D/', '', $recipientPhone);
        if (str_starts_with($cleanDigits, '0')) {
            $formattedPhone = '255' . substr($cleanDigits, 1);
        } elseif (str_starts_with($cleanDigits, '255')) {
            $formattedPhone = $cleanDigits;
        } else {
            $formattedPhone = '255' . $cleanDigits;
        }

        $postData = [
            'source_addr' => $senderId,
            'encoding' => '0',
            'schedule_time' => '',
            'message' => $message,
            'recipients' => [
                ['recipient_id' => '1', 'dest_addr' => $formattedPhone]
            ]
        ];

        $url = 'https://apisms.beem.africa/v1/send';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode($apiKey . ':' . $secretKey),
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200);
    }

    public static function sendBulkSms(array $recipients, string $message): int {
        $successCount = 0;
        foreach ($recipients as $phone) {
            if (!empty($phone) && self::sendSms($phone, $message)) {
                $successCount++;
            }
        }
        return $successCount;
    }
}
