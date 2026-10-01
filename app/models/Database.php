<?php
// app/models/Database.php

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                error_log("Database Connection Failure: " . $e->getMessage());
                http_response_code(500);
                $env = process_env('APP_ENV', 'production');
                if (strtolower($env) === 'development' || strtolower($env) === 'dev') {
                    die("Database Connection Error: " . $e->getMessage());
                }
                die("<h1>500 Internal Server Error</h1><p>Mfumo umepata tatizo la kiufundi la kuunganishwa na Database. Maelezo yamehifadhiwa kwenye Server Error Log.</p>");
            }
        }
        return self::$instance;
    }
}
