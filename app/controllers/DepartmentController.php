<?php
// app/controllers/DepartmentController.php

class DepartmentController {
    public function index(): void {
        RoleMiddleware::hasPermission('employees.view');
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT d.*, e.first_name as manager_fn, e.last_name as manager_ln,
                   (SELECT COUNT(*) FROM employees WHERE department_id = d.id AND deleted_at IS NULL) as total_employees
            FROM departments d
            LEFT JOIN employees e ON d.manager_id = e.id
            ORDER BY d.id ASC
        ");
        $stmt->execute();
        $departments = $stmt->fetchAll();

        $employees = $db->query("SELECT id, first_name, last_name FROM employees WHERE status != 'Terminated'")->fetchAll();

        view('departments.index', [
            'title'       => 'Departments — DonTech PeopleSuite',
            'departments' => $departments,
            'employees'   => $employees
        ]);
    }

    public function create(): void {
        RoleMiddleware::hasPermission('employees.create');
        $db = Database::getInstance();
        $employees = $db->query("SELECT id, first_name, last_name FROM employees WHERE status != 'Terminated'")->fetchAll();

        view('departments.create', [
            'title'     => 'Create Department — DonTech PeopleSuite',
            'employees' => $employees
        ]);
    }

    public function store(): void {
        RoleMiddleware::hasPermission('employees.create');
        $db = Database::getInstance();

        $name   = sanitize_string($_POST['name'] ?? '');
        $code   = sanitize_string($_POST['code'] ?? 'DEPT-' . rand(10,99));
        $mngrId = (int)($_POST['manager_id'] ?? 0) ?: null;
        $budget = (float)($_POST['budget'] ?? 100000000);

        $stmt = $db->prepare("INSERT INTO departments (name, code, manager_id, budget) VALUES (:n, :c, :m, :b)");
        $stmt->execute(['n' => $name, 'c' => $code, 'm' => $mngrId, 'b' => $budget]);

        AuditLogger::log('create_department', 'Departments', (string)$db->lastInsertId());
        redirect('/departments', 'Department created successfully!', 'success');
    }

    public function destroy(): void {
        RoleMiddleware::hasPermission('employees.delete');
        $db = Database::getInstance();
        $id = (int)($_POST['id'] ?? 0);

        $stmt = $db->prepare("DELETE FROM departments WHERE id = :id");
        $stmt->execute(['id' => $id]);

        AuditLogger::log('delete_department', 'Departments', (string)$id);
        redirect('/departments', 'Department removed.', 'info');
    }
}
