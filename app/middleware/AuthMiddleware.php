<?php
// app/middleware/AuthMiddleware.php

class AuthMiddleware {
    public static function handle(): void {
        if (!AuthService::check()) {
            redirect('/login', 'Please log in to access the system.', 'warning');
        }
    }
}
