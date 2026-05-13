<?php

declare(strict_types=1);

// ── Path constants ────────────────────────────────────────────────────────────
define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH',  ROOT_PATH . '/src');
define('PUB_PATH',  ROOT_PATH . '/public');

// Base URL: the subdirectory prefix this app lives under.
// e.g. "" at domain root, "/KZ-Intergration/public" in a subdirectory.
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

// ── Autoloader ────────────────────────────────────────────────────────────────
require SRC_PATH . '/Core/Autoloader.php';

$autoloader = new KazSign\Core\Autoloader(SRC_PATH);
$autoloader->register();

// ── Environment variables (.env) ──────────────────────────────────────────────
// A minimal .env loader — production deployments should prefer real env vars.
$envFile = ROOT_PATH . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

// ── Session ───────────────────────────────────────────────────────────────────
session_start();

// ── Router ────────────────────────────────────────────────────────────────────
$router = new KazSign\Core\Router();

// Auth
$router->get( '/login',    [\KazSign\Controllers\AuthController::class,     'loginForm']);
$router->post('/login',    [\KazSign\Controllers\AuthController::class,     'login']);
$router->get( '/register', [\KazSign\Controllers\AuthController::class,     'registerForm']);
$router->post('/register', [\KazSign\Controllers\AuthController::class,     'register']);
$router->get( '/logout',   [\KazSign\Controllers\AuthController::class,     'logout']);
$router->get( '/key',         [\KazSign\Controllers\AuthController::class,  'saveKeyPage']);
$router->post('/key/confirm',  [\KazSign\Controllers\AuthController::class,  'saveKeyConfirm']);

// Dashboard
$router->get( '/',         [\KazSign\Controllers\DashboardController::class,'index']);

// Documents
$router->get( '/documents',          [\KazSign\Controllers\DocumentController::class, 'index']);
$router->get( '/documents/upload',   [\KazSign\Controllers\DocumentController::class, 'uploadForm']);
$router->post('/documents/upload',   [\KazSign\Controllers\DocumentController::class, 'upload']);
$router->get( '/documents/:id/sign', [\KazSign\Controllers\DocumentController::class, 'sign']);
$router->post('/documents/:id/sign', [\KazSign\Controllers\DocumentController::class, 'doSign']);
$router->get( '/documents/:id/verify',[\KazSign\Controllers\DocumentController::class,'verify']);

$router->dispatch();
