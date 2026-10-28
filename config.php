<?php
declare(strict_types=1);

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/Argentina/Buenos_Aires');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
header('Cache-Control: no-store');

function db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $host = getenv('DB_HOST') ?: 'db';
        $name = getenv('DB_NAME') ?: 'dmproducciones';
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", getenv('DB_USER') ?: 'dm', getenv('DB_PASSWORD') ?: '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

set_exception_handler(function (Throwable $error): void {
    error_log('Error de aplicación: ' . get_class($error));
    http_response_code(500);
    echo 'No se pudo completar la operación. Revisa que la base de datos esté disponible e inténtalo nuevamente.';
});
