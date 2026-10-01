<?php
// app/helpers/response.php

function view(string $path, array $data = []): void {
    extract($data);
    $viewFile = __DIR__ . '/../views/' . str_replace('.', '/', $path) . '.php';
    if (!file_exists($viewFile)) {
        http_response_code(500);
        echo "View error: [{$path}] template not found at {$viewFile}";
        exit;
    }
    require $viewFile;
}

function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function get_base_url(): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = str_replace('\\', '/', dirname($scriptName));
    if ($dir === '/' || $dir === '\\') {
        return '';
    }
    return rtrim($dir, '/');
}

function redirect(string $url, string $flashMessage = null, string $flashType = 'success'): void {
    if ($flashMessage) {
        $_SESSION['flash'] = [
            'message' => $flashMessage,
            'type'    => $flashType
        ];
    }
    $target = get_base_url() . '/' . ltrim($url, '/');
    header("Location: {$target}");
    exit;
}

function set_flash(string $message, string $type = 'info'): void {
    $_SESSION['flash'] = [
        'message' => $message,
        'type'    => $type
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
