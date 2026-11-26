<?php
declare(strict_types=1);

// Shared database connection and security helpers for FoodFusion.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('session.use_strict_mode', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

$dbHost = getenv('FOODFUSION_DB_HOST') ?: 'localhost';
$dbUser = getenv('FOODFUSION_DB_USER') ?: 'root';
$dbPass = getenv('FOODFUSION_DB_PASS') ?: '';
$dbName = getenv('FOODFUSION_DB_NAME') ?: 'foodfusion';

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('FoodFusion database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('The website cannot connect to the database. Check the settings in db.php and import foodfusion.sql.');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Your form session expired. Go back, refresh the page, and try again.');
    }
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        $_SESSION['flash'] = 'Please log in to use that feature.';
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void
{
    if (empty($_SESSION['user_id'])) {
        $_SESSION['flash'] = 'Please log in with an administrator account to continue.';
        header('Location: index.php?auth=login');
        exit;
    }

    if (empty($_SESSION['is_admin'])) {
        http_response_code(403);
        exit('Access denied. This page is only available to FoodFusion administrators.');
    }
}

function flash_message(): string
{
    $message = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    return is_string($message) ? $message : '';
}
