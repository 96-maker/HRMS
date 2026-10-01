<?php
// app/middleware/RoleMiddleware.php

class RoleMiddleware {
    public static function hasPermission(string $permissionSlug): void {
        AuthMiddleware::handle();
        if (!AuthService::hasPermission($permissionSlug)) {
            http_response_code(403);
            view('errors.403', ['permission' => $permissionSlug]);
            exit;
        }
    }
}
