<?php
// public/index.php

// 1. Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_name('DONTECH_PEOPLESUITE_SESS');
    session_start();
}

// Ensure exact East Africa Time (EAT) timezone
date_default_timezone_set('Africa/Dar_es_Salaam');

// 2. Class Autoloader
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../app/controllers/' . $class . '.php',
        __DIR__ . '/../app/models/' . $class . '.php',
        __DIR__ . '/../app/services/' . $class . '.php',
        __DIR__ . '/../app/middleware/' . $class . '.php',
    ];
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 3. Load Helpers & Config
require_once __DIR__ . '/../app/helpers/sanitize.php';
require_once __DIR__ . '/../app/helpers/response.php';
require_once __DIR__ . '/../app/helpers/formatting.php';

// 4. CSRF Middleware Automatic Verification for Mutations
CsrfMiddleware::check();

// 5. Route Resolution
$routes = require __DIR__ . '/../routes/web.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normalize base path for subfolder deployments (e.g. /HR or /HR/public)
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$scriptDir  = dirname($scriptName); // e.g. /HR/public or /HR

if ($scriptDir !== '/' && strpos($requestUri, $scriptDir) === 0) {
    $requestUri = substr($requestUri, strlen($scriptDir));
}

// Fallback strip of parent project directory if accessing directly via /HR/route
$parentDir = dirname($scriptDir);
if ($parentDir !== '/' && $parentDir !== '.' && strpos($requestUri, $parentDir) === 0) {
    $requestUri = substr($requestUri, strlen($parentDir));
}

$requestUri = '/' . ltrim($requestUri, '/');
// Strip /public prefix if present at start of URI
if (strpos($requestUri, '/public/') === 0) {
    $requestUri = substr($requestUri, 7);
} elseif ($requestUri === '/public') {
    $requestUri = '/';
}
$httpMethod = $_SERVER['REQUEST_METHOD'];


$routeKey = "{$httpMethod} {$requestUri}";

if (array_key_exists($routeKey, $routes)) {
    $handler = $routes[$routeKey];
    list($controllerName, $methodName) = explode('@', $handler);

    if (class_exists($controllerName)) {
        $controller = new $controllerName();
        if (method_exists($controller, $methodName)) {
            $controller->$methodName();
            exit;
        }
    }
}

// 6. 404 Handler
http_response_code(404);
view('errors.404', ['uri' => $requestUri]);
