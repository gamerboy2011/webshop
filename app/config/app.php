<?php





return [
    
    'name' => 'YoursyWear',
    
    
    'url' => 'http://localhost/webshop',
    
    
    'debug' => true,
    
    
    'timezone' => 'Europe/Budapest',
    
    
    'locale' => 'hu_HU',
    
    
    'session' => [
        'name' => 'yoursywear_session',
        'lifetime' => 7200, 
        'secure' => false,  
        'httponly' => true
    ],
    
    
    'upload' => [
        'max_size' => 5 * 1024 * 1024, 
        'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'path' => '/storage/uploads/'
    ],
    
    
    'free_shipping_limit' => 15000,
    
    
    'shipping_costs' => [
        'delivery' => 1490,
        'foxpost' => 990
    ]
];
