<?php
// app/config/database.php

return [
    'driver'    => 'mysql',
    'host'      => process_env('DB_HOST', '127.0.0.1'),
    'port'      => process_env('DB_PORT', '3307'),
    'database'  => process_env('DB_DATABASE', 'hr_system'),
    'username'  => process_env('DB_USERNAME', 'root'),
    'password'  => process_env('DB_PASSWORD', ''),
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options'   => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ],
];
