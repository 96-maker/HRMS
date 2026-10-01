<?php
// app/controllers/AdminController.php

class AdminController {
    public function users(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();

        $statusFilter = trim($_GET['status'] ?? 'all');
        $searchQuery  = trim($_GET['search'] ?? '');

        // Query all Employees with their linked Users & Roles (including employees without active users)
        $users = $db->query("
            SELECT 
                e.id as employee_id,
                e.first_name,
                e.last_name,
                e.email as emp_email,
                e.employee_code,
                e.status as emp_status,
                u.id as user_id,
                u.username,
                u.email as user_email,
                u.role_id,
                u.status as user_status,
                u.last_login_at,
                r.name as role_name
            FROM employees e
            LEFT JOIN users u ON e.user_id = u.id AND u.deleted_at IS NULL
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE e.deleted_at IS NULL
            ORDER BY e.id ASC
        ")->fetchAll();

        // Also fetch system users not linked to employees (e.g. system admin)
        $unlinkedUsers = $db->query("
            SELECT 
                NULL as employee_id,
                '' as first_name,
                '' as last_name,
                u.email as emp_email,
                '' as employee_code,
                u.status as emp_status,
                u.id as user_id,
                u.username,
                u.email as user_email,
                u.role_id,
                u.status as user_status,
                u.last_login_at,
                r.name as role_name
            FROM users u
            LEFT JOIN employees e ON e.user_id = u.id
            JOIN roles r ON u.role_id = r.id
            WHERE e.id IS NULL AND u.deleted_at IS NULL
            ORDER BY u.id ASC
        ")->fetchAll();

        $allUsers = array_merge($unlinkedUsers, $users);

        // Apply Status Filter & Live Search
        $filteredUsers = array_values(array_filter($allUsers, function($u) use ($statusFilter, $searchQuery) {
            $hasAccount = !empty($u['user_id']);
            
            // Status Filtering
            if ($statusFilter === 'active' && !$hasAccount) {
                return false;
            }
            if ($statusFilter === 'pending' && $hasAccount) {
                return false;
            }

            // Search Query Filtering
            if (!empty($searchQuery)) {
                $sq = strtolower($searchQuery);
                $name     = strtolower(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                $username = strtolower($u['username'] ?? '');
                $email    = strtolower($u['user_email'] ?? ($u['emp_email'] ?? ''));
                $code     = strtolower($u['employee_code'] ?? '');
                $role     = strtolower($u['role_name'] ?? '');

                if (strpos($name, $sq) === false &&
                    strpos($username, $sq) === false &&
                    strpos($email, $sq) === false &&
                    strpos($code, $sq) === false &&
                    strpos($role, $sq) === false) {
                    return false;
                }
            }

            return true;
        }));

        $roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

        view('admin.users', [
            'title'        => 'System Administrators & User Accounts — DonTech PeopleSuite',
            'users'        => $filteredUsers,
            'allUsers'     => $allUsers,
            'roles'        => $roles,
            'statusFilter' => $statusFilter,
            'searchQuery'  => $searchQuery,
            'currentUser'  => AuthService::user()
        ]);
    }

    public function createUser(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();
        $roles = $db->query("SELECT * FROM roles")->fetchAll();

        view('admin.create_user', [
            'title' => 'Add System User — DonTech PeopleSuite',
            'roles' => $roles
        ]);
    }

    public function inviteUser(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();

        $username = sanitize_string($_POST['username'] ?? '');
        $email    = sanitize_email($_POST['email'] ?? '');
        $roleId   = (int)($_POST['role_id'] ?? 2);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['password_confirmation'] ?? '';

        if (strlen($password) < 12 || $password !== $confirmPassword) {
            redirect('/users/create', 'A password of at least 12 characters is required and both password fields must match.', 'danger');
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role_id, status) VALUES (:u, :e, :p, :r, 'Active')");
        $stmt->execute(['u' => $username, 'e' => $email, 'p' => $passwordHash, 'r' => $roleId]);

        AuditLogger::log('invite_user', 'Admin', (string)$db->lastInsertId());
        redirect('/users', "System user {$username} created successfully.", 'success');
    }

    public function updateUserRole(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();

        $userId = (int)($_POST['user_id'] ?? 0);
        $roleId = (int)($_POST['role_id'] ?? 2);

        if ($userId > 0) {
            $stmt = $db->prepare("UPDATE users SET role_id = :r WHERE id = :id");
            $stmt->execute(['r' => $roleId, 'id' => $userId]);

            AuditLogger::log('update_user_role', 'Admin', (string)$userId);
            redirect('/users', 'User system access role updated successfully!', 'success');
            return;
        }
        redirect('/users', 'Invalid user selected.', 'danger');
    }

    public function activateUser(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();

        $empId    = (int)($_POST['employee_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $roleId   = (int)($_POST['role_id'] ?? 2);

        if (strlen($password) < 12) {
            redirect('/users', 'A password of at least 12 characters is required.', 'danger');
            return;
        }

        $empStmt = $db->prepare("SELECT * FROM employees WHERE id = :eid LIMIT 1");
        $empStmt->execute(['eid' => $empId]);
        $emp = $empStmt->fetch();

        if (!$emp) {
            redirect('/users', 'Employee not found.', 'danger');
            return;
        }

        // Generate username
        $baseUsername = strtolower(explode('@', $emp['email'])[0] ?? ($emp['first_name'] . '.' . $emp['last_name']));
        $username = preg_replace('/[^a-z0-9._-]/', '', $baseUsername);

        $chkUser = $db->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
        $chkUser->execute(['u' => $username]);
        if ($chkUser->fetchColumn()) {
            $username .= rand(10, 99);
        }

        $passHash = password_hash($password, PASSWORD_BCRYPT);
        $userStmt = $db->prepare("
            INSERT INTO users (username, email, password_hash, role_id, status)
            VALUES (:u, :e, :p, :r, 'Active')
        ");
        $userStmt->execute([
            'u' => $username,
            'e' => $emp['email'],
            'p' => $passHash,
            'r' => $roleId
        ]);
        $userId = (int)$db->lastInsertId();

        // Link Employee to User
        $linkStmt = $db->prepare("UPDATE employees SET user_id = :uid WHERE id = :eid");
        $linkStmt->execute(['uid' => $userId, 'eid' => $empId]);

        AuditLogger::log('activate_user', 'Admin', (string)$userId, ['username' => $username]);
        redirect('/users', "User account activated for {$emp['first_name']} {$emp['last_name']}.", 'success');
    }

    public function resetUserPassword(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();

        $id = (int)($_POST['id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['new_password_confirmation'] ?? '';

        if (strlen($newPassword) < 12 || $newPassword !== $confirmPassword) {
            redirect('/users', 'A password of at least 12 characters is required and both password fields must match.', 'danger');
            return;
        }

        if ($id > 0) {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password_hash = :h, status = 'Active', deleted_at = NULL WHERE id = :id");
            $stmt->execute(['h' => $hash, 'id' => $id]);

            AuditLogger::log('reset_user_password', 'Admin', (string)$id);
            redirect('/users', 'User password has been reset successfully.', 'success');
            return;
        }
        redirect('/users', 'Invalid user selected.', 'danger');
    }

    public function deleteUser(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();
        $id = (int)($_POST['id'] ?? 0);
        $currentUserId = AuthService::user()['id'] ?? 0;

        if ($id <= 0 || $id === 1 || $id === $currentUserId) {
            redirect('/users', 'Hauwezi kuifuta akaunti ya Main Super Admin au akaunti yako unayotumia sasa hivi.', 'danger');
            return;
        }

        $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND id != 1");
        $stmt->execute(['id' => $id]);

        AuditLogger::log('delete_user', 'Admin', (string)$id);
        redirect('/users', 'Akaunti ya mtumiaji imefutwa kikamilifu!', 'success');
    }

    public function roles(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();

        $roles = $db->query("SELECT * FROM roles")->fetchAll();
        $permissions = $db->query("SELECT * FROM permissions")->fetchAll();

        // Fetch pivot map
        $matrix = [];
        $rp = $db->query("SELECT role_id, permission_id FROM role_permissions")->fetchAll();
        foreach ($rp as $r) {
            $matrix[$r['role_id']][$r['permission_id']] = true;
        }

        view('admin.roles', [
            'title'       => 'Role Permission Matrix — DonTech PeopleSuite',
            'roles'       => $roles,
            'permissions' => $permissions,
            'matrix'      => $matrix
        ]);
    }

    public function updatePermissions(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();

        $roleId = (int)($_POST['role_id'] ?? 0);
        $selectedPerms = $_POST['permissions'] ?? [];

        if ($roleId > 0) {
            $db->beginTransaction();
            try {
                $del = $db->prepare("DELETE FROM role_permissions WHERE role_id = :rid");
                $del->execute(['rid' => $roleId]);

                $ins = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:rid, :pid)");
                foreach ($selectedPerms as $pid) {
                    $ins->execute(['rid' => $roleId, 'pid' => (int)$pid]);
                }

                $db->commit();
                AuditLogger::log('update_permissions', 'Admin', (string)$roleId);
                redirect('/roles', 'Role permissions updated successfully!', 'success');
            } catch (Exception $e) {
                $db->rollBack();
                redirect('/roles', 'Failed to update matrix: ' . $e->getMessage(), 'danger');
            }
        }
        redirect('/roles', 'Invalid role selection.', 'warning');
    }

    public function markAllNotificationsRead(): void {
        $userId = AuthService::user()['id'] ?? 0;
        if ($userId > 0) {
            NotificationService::markAllAsRead($userId);
        }
        $referer = $_SERVER['HTTP_REFERER'] ?? '/attendance';
        header("Location: " . $referer);
        exit;
    }
}
