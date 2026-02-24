<?php
/**
 * Email konfiguráció - Mailtrap SMTP
 * 
 * A credentials-t a Mailtrap dashboardról lehet másolni:
 * https://mailtrap.io → Email Testing → Inboxes → [inbox] → SMTP Settings
 */

return [
    'smtp_host' => 'sandbox.smtp.mailtrap.io',
    'smtp_port' => 2525,
    'smtp_username' => 'YOUR_MAILTRAP_USERNAME', // cseréld ki!
    'smtp_password' => 'YOUR_MAILTRAP_PASSWORD', // cseréld ki!
    'from_email' => 'noreply@yoursywear.hu',
    'from_name' => 'YoursyWear',
];
