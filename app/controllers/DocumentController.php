<?php
// app/controllers/DocumentController.php

class DocumentController {
    private function privateUploadDirectory(): string {
        $config = require __DIR__ . '/../config/app.php';
        $directory = rtrim($config['private_upload_dir'], "\\/") . DIRECTORY_SEPARATOR;

        if (!is_dir($directory)) {
            mkdir($directory, 0750, true);
        }

        return $directory;
    }

    private function locateDocumentFile(string $storedName): ?string {
        $safeName = basename($storedName);
        $candidates = [
            $this->privateUploadDirectory() . $safeName,
            __DIR__ . '/../../public/uploads/' . $safeName,
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function index(): void {
        RoleMiddleware::hasPermission('documents.view');
        $db = Database::getInstance();
        $user = AuthService::user();

        // Fetch current user's employee record
        $myEmpStmt = $db->prepare("SELECT id FROM employees WHERE user_id = :uid LIMIT 1");
        $myEmpStmt->execute(['uid' => $user['id']]);
        $myEmpId = (int)($myEmpStmt->fetchColumn() ?: 0);

        $search = sanitize_string($_GET['search'] ?? '');
        $typeFilter = sanitize_string($_GET['type'] ?? '');

        $where = [];
        $params = [];

        // Self-service data scoping for regular Employee
        if (AuthService::hasRole('employee')) {
            $where[] = "d.employee_id = :my_eid";
            $params['my_eid'] = $myEmpId;
        }

        if (!empty($search)) {
            $where[] = "(d.title LIKE :s OR e.first_name LIKE :s OR e.last_name LIKE :s)";
            $params['s'] = "%{$search}%";
        }

        if (!empty($typeFilter) && $typeFilter !== 'all') {
            $where[] = "d.document_type = :dt";
            $params['dt'] = $typeFilter;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

        $stmt = $db->prepare("
            SELECT d.*, e.first_name, e.last_name, e.employee_code 
            FROM employee_documents d
            JOIN employees e ON d.employee_id = e.id
            {$whereClause}
            ORDER BY d.created_at DESC
        ");
        $stmt->execute($params);
        $documents = $stmt->fetchAll();

        // For Admin, fetch all active employees. For regular Employee, restrict to self.
        if (AuthService::hasRole('employee')) {
            $employees = $db->query("SELECT id, first_name, last_name FROM employees WHERE id = {$myEmpId}")->fetchAll();
        } else {
            $employees = $db->query("SELECT id, first_name, last_name FROM employees WHERE status != 'Terminated' AND deleted_at IS NULL ORDER BY first_name ASC")->fetchAll();
        }

        view('documents.index', [
            'title'      => 'Document Repository — DonTech PeopleSuite',
            'documents'  => $documents,
            'employees'  => $employees,
            'myEmpId'    => $myEmpId,
            'search'     => $search,
            'typeFilter' => $typeFilter
        ]);
    }

    public function create(): void {
        RoleMiddleware::hasPermission('documents.upload');
        $db = Database::getInstance();
        $user = AuthService::user();

        $myEmpStmt = $db->prepare("SELECT id FROM employees WHERE user_id = :uid LIMIT 1");
        $myEmpStmt->execute(['uid' => $user['id']]);
        $myEmpId = (int)($myEmpStmt->fetchColumn() ?: 0);

        if (AuthService::hasRole('employee')) {
            $employees = $db->query("SELECT id, first_name, last_name FROM employees WHERE id = {$myEmpId}")->fetchAll();
        } else {
            $employees = $db->query("SELECT id, first_name, last_name FROM employees WHERE status != 'Terminated' AND deleted_at IS NULL ORDER BY first_name ASC")->fetchAll();
        }

        view('documents.upload', [
            'title'     => 'Upload Employee Document — DonTech PeopleSuite',
            'employees' => $employees
        ]);
    }

    public function upload(): void {
        RoleMiddleware::hasPermission('documents.upload');
        $db = Database::getInstance();
        $user = AuthService::user();

        $myEmpStmt = $db->prepare("SELECT id FROM employees WHERE user_id = :uid LIMIT 1");
        $myEmpStmt->execute(['uid' => $user['id']]);
        $myEmpId = (int)($myEmpStmt->fetchColumn() ?: 0);

        // If regular Employee, force employee_id to self
        if (AuthService::hasRole('employee')) {
            $empId = $myEmpId;
        } else {
            $empId = (int)($_POST['employee_id'] ?? $myEmpId);
        }

        $type   = sanitize_string($_POST['document_type'] ?? 'Contracts');
        $title  = sanitize_string($_POST['title'] ?? 'Document');
        $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            redirect('/documents', 'File upload error or no file chosen.', 'danger');
        }

        $file = $_FILES['file'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            redirect('/documents', 'File size exceeds 5MB limit.', 'danger');
        }

        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes)) {
            redirect('/documents', 'Invalid file type. Only PDF, PNG, JPG, and DOCX allowed.', 'danger');
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $storedName = 'doc_' . uniqid() . '_' . time() . '.' . strtolower($ext);
        $uploadDir = $this->privateUploadDirectory();

        if (move_uploaded_file($file['tmp_name'], $uploadDir . $storedName)) {
            $stmt = $db->prepare("
                INSERT INTO employee_documents (employee_id, document_type, title, original_name, stored_name, mime_type, file_size, expiry_date, uploaded_by)
                VALUES (:eid, :dt, :title, :oname, :sname, :mime, :fsize, :exp, :uid)
            ");
            $stmt->execute([
                'eid' => $empId, 'dt' => $type, 'title' => $title, 'oname' => $file['name'],
                'sname' => $storedName, 'mime' => $mime, 'fsize' => $file['size'],
                'exp' => $expiry, 'uid' => $user['id']
            ]);

            AuditLogger::log('upload_document', 'Documents', (string)$db->lastInsertId());
            redirect('/documents', 'Document uploaded successfully!', 'success');
        } else {
            redirect('/documents', 'Failed to save uploaded file.', 'danger');
        }
    }

    public function destroy(): void {
        RoleMiddleware::hasPermission('documents.delete');
        $db = Database::getInstance();
        $user = AuthService::user();
        $id = (int)($_POST['id'] ?? 0);

        $myEmpStmt = $db->prepare("SELECT id FROM employees WHERE user_id = :uid LIMIT 1");
        $myEmpStmt->execute(['uid' => $user['id']]);
        $myEmpId = (int)($myEmpStmt->fetchColumn() ?: 0);

        $docStmt = $db->prepare("SELECT * FROM employee_documents WHERE id = :id LIMIT 1");
        $docStmt->execute(['id' => $id]);
        $doc = $docStmt->fetch();

        if (!$doc) {
            redirect('/documents', 'Document not found.', 'danger');
            return;
        }

        if (AuthService::hasRole('employee') && (int)$doc['employee_id'] !== $myEmpId) {
            redirect('/documents', 'Hauwezi kufuta hati ya mfanyakazi mwingine.', 'danger');
            return;
        }

        // Delete physical file if exists
        $filePath = $this->locateDocumentFile($doc['stored_name']);
        if ($filePath !== null) {
            @unlink($filePath);
        }

        $stmt = $db->prepare("DELETE FROM employee_documents WHERE id = :id");
        $stmt->execute(['id' => $id]);

        AuditLogger::log('delete_document', 'Documents', (string)$id);
        redirect('/documents', 'Document removed successfully.', 'info');
    }

    public function download(): void {
        RoleMiddleware::hasPermission('documents.view');
        $db = Database::getInstance();
        $user = AuthService::user();
        $id = (int)($_GET['id'] ?? 0);

        $myEmpStmt = $db->prepare("SELECT id FROM employees WHERE user_id = :uid LIMIT 1");
        $myEmpStmt->execute(['uid' => $user['id']]);
        $myEmpId = (int)($myEmpStmt->fetchColumn() ?: 0);

        $docStmt = $db->prepare("SELECT * FROM employee_documents WHERE id = :id LIMIT 1");
        $docStmt->execute(['id' => $id]);
        $doc = $docStmt->fetch();

        if (!$doc) {
            redirect('/documents', 'Document not found.', 'danger');
            return;
        }

        // Self-service scoping: Regular employee can only download their own document
        if (AuthService::hasRole('employee') && (int)$doc['employee_id'] !== $myEmpId) {
            redirect('/documents', 'Hauwezi kupakua hati ya mfanyakazi mwingine.', 'danger');
            return;
        }

        $filePath = $this->locateDocumentFile($doc['stored_name']);

        if ($filePath === null) {
            redirect('/documents', 'File missing on server.', 'danger');
            return;
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($doc['mime_type'] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($doc['original_name']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}
