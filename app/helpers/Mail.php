<?php
/**
 * Mail Helper - PHPMailer wrapper Mailtrap SMTP-vel
 */

require_once __DIR__ . '/../../vendor/PHPMailer-6.9.1/src/PHPMailer.php';
require_once __DIR__ . '/../../vendor/PHPMailer-6.9.1/src/SMTP.php';
require_once __DIR__ . '/../../vendor/PHPMailer-6.9.1/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mail
{
    private static $config = null;

    /**
     * Konfiguráció betöltése
     */
    private static function getConfig()
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../config/mail.php';
        }
        return self::$config;
    }

    /**
     * Email küldése
     * 
     * @param string $to Címzett email
     * @param string $subject Tárgy
     * @param string $htmlBody HTML tartalom
     * @param string|null $toName Címzett neve (opcionális)
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function send($to, $subject, $htmlBody, $toName = null)
    {
        $config = self::getConfig();
        $mail = new PHPMailer(true);

        try {
            // SMTP beállítások
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_username'];
            $mail->Password = $config['smtp_password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $config['smtp_port'];
            $mail->CharSet = 'UTF-8';

            // Feladó
            $mail->setFrom($config['from_email'], $config['from_name']);

            // Címzett
            $mail->addAddress($to, $toName ?? '');

            // Tartalom
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

            $mail->send();
            return ['success' => true, 'error' => null];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $mail->ErrorInfo];
        }
    }
}
