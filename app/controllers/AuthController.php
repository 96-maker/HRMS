<?php
// app/controllers/AuthController.php

class AuthController {
    public function showLogin(): void {
        if (AuthService::check()) {
            redirect('/dashboard');
        }

        view('auth.login', ['title' => 'Sign In — DonTech PeopleSuite']);
    }

    public function login(): void {
        $username = sanitize_string($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            redirect('/login', 'Username and password are required.', 'danger');
        }

        if (AuthService::attempt($username, $password)) {
            redirect('/dashboard', 'Welcome back to DonTech PeopleSuite!', 'success');
        } else {
            redirect('/login', 'Invalid credentials or inactive account.', 'danger');
        }
    }

    public function logout(): void {
        AuthService::logout();
        redirect('/login', 'You have been safely logged out.', 'info');
    }

    public function profile(): void {
        AuthMiddleware::handle();
        $db = Database::getInstance();
        $user = AuthService::user();

        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $user['id']]);
        $userData = $stmt->fetch();

        view('auth.profile', [
            'title' => 'My Profile — DonTech PeopleSuite',
            'user'  => $userData
        ]);
    }

    public function changePassword(): void {
        AuthMiddleware::handle();
        $db = Database::getInstance();
        $user = AuthService::user();

        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new !== $confirm) {
            redirect('/profile', 'New password confirmation does not match.', 'danger');
        }

        if (strlen($new) < 12) {
            redirect('/profile', 'New password must contain at least 12 characters.', 'danger');
        }

        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->execute(['id' => $user['id']]);
        $pwdHash = $stmt->fetchColumn();

        if (!password_verify($current, $pwdHash)) {
            redirect('/profile', 'Current password is incorrect.', 'danger');
        }

        $newHash = password_hash($new, PASSWORD_BCRYPT);
        $upStmt = $db->prepare("UPDATE users SET password_hash = :h WHERE id = :id");
        $upStmt->execute(['h' => $newHash, 'id' => $user['id']]);

        AuditLogger::log('change_password', 'Auth', (string)$user['id']);
        redirect('/profile', 'Password updated successfully.', 'success');
    }
}
