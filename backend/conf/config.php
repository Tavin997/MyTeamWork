<?php

return [
    'General' => [
        'timezone' => 'America/Sao_Paulo',
        'default_handler' => 'tasks/index',
        'locale' => 'pt_BR',
    ],
    
    'Database' => [
        'driver' => 'mysql',
        'host' => getenv('DB_HOST'),
        'port' => 3306,
        'name' => getenv('DB_NAME'),
        'user' => getenv('DB_USER'),
        'pass' => getenv('DB_PASS'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => 'mtw_',
    ],
    
    'Cache' => [
        'driver' => 'file',
        'expire' => 3600,
    ],
    
    'Auth' => [
        'session_name' => 'mtw_session',
        'login_url' => '/auth/login',
        'logout_url' => '/auth/logout',
        'register_url' => '/auth/register',
        'password_reset_url' => '/auth/reset',
    ],
];