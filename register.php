<?php
require_once __DIR__ . '/db.php';
$returnTo = (string) ($_POST['return_to'] ?? $_GET['return_to'] ?? 'index.php');
if (!preg_match('~^(?!//)[A-Za-z0-9_./?=&%-]+$~', $returnTo) || str_contains($returnTo, '..'))
    $returnTo = 'index.php';
$pageTitle = 'Join FoodFusion';
$error = '';
$username = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        $error = 'Username must be 3–50 characters and use letters, numbers, dots, underscores or hyphens.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Password must be at least 8 characters and include a letter and a number.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO `user` (username, email, password_hash) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $username, $email, $hash);
            $stmt->execute();
            $_SESSION['flash'] = 'Your account was created. You can now log in.';
            header('Location: ' . $returnTo . (str_contains($returnTo, '?') ? '&' : '?') . 'auth=login');
            exit;
        } catch (mysqli_sql_exception $exception) {
            if ((int) $exception->getCode() === 1062)
                $error = 'That username or email is already registered.';
            else {
                error_log($exception->getMessage());
                $error = 'Registration could not be completed. Please try again.';
            }
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['flash'] = $error ?: 'Registration could not be completed. Please try again.';
    header('Location: ' . $returnTo . (str_contains($returnTo, '?') ? '&' : '?') . 'auth=register');
    exit;
}
header('Location: index.php?auth=register');
exit;
