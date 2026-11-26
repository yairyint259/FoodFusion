<?php
require_once __DIR__ . '/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
verify_csrf();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $params['path'], 'domain' => $params['domain'], 'secure' => $params['secure'], 'httponly' => $params['httponly'], 'samesite' => 'Lax']);
}
session_destroy();
session_start();
$_SESSION['flash'] = 'You have been logged out.';
$returnTo = (string) ($_POST['return_to'] ?? 'index.php');
if (!preg_match('~^(?!//)[A-Za-z0-9_./?=&%-]+$~', $returnTo) || str_contains($returnTo, '..'))
    $returnTo = 'index.php';
header('Location: ' . $returnTo);
exit;
