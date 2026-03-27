<?php





return [
    
    'host' => 'localhost',
    
    
    'database' => 'webshop',
    
    
    'username' => 'root',
    
    
    'password' => '',
    
    
    'charset' => 'utf8mb4',
    
    
    'collation' => 'utf8mb4_hungarian_ci',
    
    
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
];
