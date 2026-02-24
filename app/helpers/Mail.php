<?php
/**
 * Mail Helper - Resend API
 */

class Mail
{
    private static $apiKey = 're_hhHTJpeV_NMxHi7QSdFBNDryb54WAEnjy';
    private static $fromEmail = 'onboarding@resend.dev'; // Resend test email
    private static $fromName = 'YoursyWear';

    /**
     * Email küldése Resend API-val
     * 
     * @param string $to Címzett email
     * @param string $subject Tárgy
     * @param string $htmlBody HTML tartalom
     * @param string|null $toName Címzett neve (opcionális)
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function send($to, $subject, $htmlBody, $toName = null)
    {
        $data = [
            'from' => self::$fromName . ' <' . self::$fromEmail . '>',
            'to' => [$to],
            'subject' => $subject,
            'html' => $htmlBody
        ];

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . self::$apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        $result = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'error' => null];
        } else {
            return ['success' => false, 'error' => $result['message'] ?? 'Unknown error'];
        }
    }
}
