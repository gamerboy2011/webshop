<?php
/**
 * Database Configuration
 * Adatbázis beállítások
 */

return [
    // Adatbázis szerver
    'host' => 'localhost',
    
    // Adatbázis neve
    'database' => 'webshop',
    
    // Felhasználónév
    'username' => 'root',
    
    // Jelszó
    'password' => '',
    
    // Karakterkódolás
    'charset' => 'utf8mb4',
    
    // Collation
    'collation' => 'utf8mb4_hungarian_ci',
    
    // PDO beállítások
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
];
