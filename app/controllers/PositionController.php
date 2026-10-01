<?php
// app/controllers/PositionController.php

class PositionController {
    public function index(): void {
        RoleMiddleware::hasPermission('employees.view');
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT p.*, d.name as department_name 
            FROM positions p
            JOIN departments d ON p.department_id = d.id
            ORDER BY p.id ASC
        ");
        $stmt->execute();
        $positions = $stmt->fetchAll();

        $departments = $db->query("SELECT * FROM departments WHERE status='Active'")->fetchAll();

        view('positions.index', [
            'title'       => 'Positions & Roles — DonTech PeopleSuite',
            'positions'   => $positions,
            'departments' => $departments
        ]);
    }

    public function create(): void {
        RoleMiddleware::hasPermission('employees.create');
        $db = Database::getInstance();
        $departments = $db->query("SELECT * FROM departments WHERE status='Active'")->fetchAll();

        view('positions.create', [
            'title'       => 'Add Position — DonTech PeopleSuite',
            'departments' => $departments
        ]);
    }

    public function store(): void {
        RoleMiddleware::hasPermission('employees.create');
        $db = Database::getInstance();

        $title   = sanitize_string($_POST['title'] ?? '');
        $deptId  = (int)($_POST['department_id'] ?? 1);
        $level   = sanitize_string($_POST['level'] ?? 'Mid');
        $head    = (int)($_POST['headcount'] ?? 1);
        $open    = (int)($_POST['open_roles'] ?? 0);

        $stmt = $db->prepare("INSERT INTO positions (department_id, title, level, headcount, open_roles) VALUES (:d, :t, :l, :h, :o)");
        $stmt->execute(['d' => $deptId, 't' => $title, 'l' => $level, 'h' => $head, 'o' => $open]);

        AuditLogger::log('create_position', 'Positions', (string)$db->lastInsertId());
        redirect('/positions', 'Job position created successfully!', 'success');
    }
}
