<?php
// app/services/AuthService.php

class AuthService {
    public static function attempt(string $username, string $password): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT u.*, r.name as role_name, r.slug as role_slug 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE (u.username = :u OR u.email = :e) AND u.status = 'Active' AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['u' => $username, 'e' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['role_slug'] = $user['role_slug'];
            $_SESSION['last_activity'] = time();

            // Fetch Permissions
            $permStmt = $db->prepare("
                SELECT p.slug 
                FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                WHERE rp.role_id = :rid
            ");
            $permStmt->execute(['rid' => $user['role_id']]);
            $_SESSION['permissions'] = $permStmt->fetchAll(PDO::FETCH_COLUMN);

            // Update last login
            $upStmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id");
            $upStmt->execute(['id' => $user['id']]);

            AuditLogger::log('login', 'Auth', (string)$user['id'], ['username' => $user['username']]);
            return true;
        }

        AuditLogger::log('failed_login', 'Auth', null, ['username' => $username]);
        return false;
    }

    public static function check(): bool {
        if (empty($_SESSION['user_id'])) {
            return false;
        }
        // Inactivity timeout: 30 mins
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            self::logout();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function user(): ?array {
        if (!self::check()) return null;
        return [
            'id'        => $_SESSION['user_id'],
            'username'  => $_SESSION['username'],
            'email'     => $_SESSION['email'],
            'role_id'   => $_SESSION['role_id'],
            'role_name' => $_SESSION['role_name'],
            'role_slug' => $_SESSION['role_slug'],
        ];
    }

    public static function hasRole(string $roleSlug): bool {
        if (!self::check()) return false;
        return ($_SESSION['role_slug'] ?? '') === $roleSlug;
    }

    public static function hasPermission(string $permissionSlug): bool {
        if (!self::check()) return false;
        if ($_SESSION['role_slug'] === 'super-admin') return true;
        $perms = $_SESSION['permissions'] ?? [];
        return in_array($permissionSlug, $perms);
    }

    public static function logout(): void {
        if (isset($_SESSION['user_id'])) {
            AuditLogger::log('logout', 'Auth', (string)$_SESSION['user_id']);
        }
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
