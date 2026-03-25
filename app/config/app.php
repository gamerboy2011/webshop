<?php
/**
 * Application Configuration
 * Alkalmazás beállítások
 */

return [
    // Alkalmazás neve
    'name' => 'YoursyWear',
    
    // Alap URL
    'url' => 'http://localhost/webshop',
    
    // Debug mód (production-ben false!)
    'debug' => true,
    
    // Időzóna
    'timezone' => 'Europe/Budapest',
    
    // Alapértelmezett nyelv
    'locale' => 'hu_HU',
    
    // Session beállítások
    'session' => [
        'name' => 'yoursywear_session',
        'lifetime' => 7200, // 2 óra
        'secure' => false,  // HTTPS esetén true
        'httponly' => true
    ],
    
    // Fájlfeltöltés beállítások
    'upload' => [
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'path' => '/storage/uploads/'
    ],
    
    // Ingyenes szállítás limit
    'free_shipping_limit' => 15000,
    
    // Szállítási költségek
    'shipping_costs' => [
        'delivery' => 1490,
        'foxpost' => 990
    ]
];
