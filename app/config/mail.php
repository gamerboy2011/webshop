<?php
/**
 * Mail Configuration
 * Email küldési beállítások (PHPMailer)
 */

return [
    // SMTP szerver
    'host' => 'smtp.gmail.com',
    
    // SMTP port
    'port' => 587,
    
    // SMTP biztonság (tls/ssl)
    'encryption' => 'tls',
    
    // SMTP authentikáció
    'auth' => true,
    
    // SMTP felhasználónév (email cím)
    'username' => 'yoursywear@gmail.com',
    
    // SMTP jelszó (app password)
    'password' => '',
    
    // Küldő email cím
    'from_address' => 'yoursywear@gmail.com',
    
    // Küldő név
    'from_name' => 'YoursyWear',
    
    // Debug mód (0 = off, 1 = client, 2 = server)
    'debug' => 0,
    
    // Karakterkódolás
    'charset' => 'UTF-8'
];
