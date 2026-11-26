<?php
require_once __DIR__ . '/db.php';
$returnTo = (string) ($_POST['return_to'] ?? $_GET['return_to'] ?? 'index.php');
if (!preg_match('~^(?!//)[A-Za-z0-9_./?=&%-]+$~', $returnTo) || str_contains($returnTo, '..'))
    $returnTo = 'index.php';
$pageTitle = 'Log in';
$error = '';
$identity = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identity = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if ($identity === '' || $password === '')
        $error = 'Enter your username or email and password.';
    else {
        try {
            $stmt = $conn->prepare('SELECT user_id, username, email, password_hash, is_admin, failed_attempts, locked_until FROM `user` WHERE username = ? OR email = ? LIMIT 1');
            $stmt->bind_param('ss', $identity, $identity);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            if (!$user)
                $error = 'The login details are incorrect.';
            elseif (!empty($user['locked_until']) && strtotime($user['locked_until']) > time())
                $error = 'This account is temporarily locked. Please try again later.';
            elseif (password_verify($password, $user['password_hash'])) {
                $reset = $conn->prepare('UPDATE `user` SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?');
                $reset->bind_param('i', $user['user_id']);
                $reset->execute();
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = (int) $user['is_admin'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['flash'] = 'Welcome back, ' . $user['username'] . '!';

                // Administrators go straight to their private dashboard after login.
                $destination = !empty($user['is_admin']) ? 'admin-dashboard.php' : $returnTo;
                header('Location: ' . $destination);
                exit;
            } else {
                $attempts = min(255, (int) $user['failed_attempts'] + 1);
                if ($attempts >= 3) {
                    $lock = date('Y-m-d H:i:s', time() + 180);
                    $update = $conn->prepare('UPDATE `user` SET failed_attempts = 0, locked_until = ? WHERE user_id = ?');
                    $update->bind_param('si', $lock, $user['user_id']);
                    $update->execute();
                    $error = 'Too many failed attempts. This account is locked for 3 minutes.';
                } else {
                    $update = $conn->prepare('UPDATE `user` SET failed_attempts = ? WHERE user_id = ?');
                    $update->bind_param('ii', $attempts, $user['user_id']);
                    $update->execute();
                    $error = 'The login details are incorrect.';
                }
            }
        } catch (mysqli_sql_exception $exception) {
            error_log($exception->getMessage());
            $error = 'Login is temporarily unavailable. Please try again.';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['login_error'] = $error ?: 'Please check your login details and try again.';
    header('Location: ' . $returnTo . (str_contains($returnTo, '?') ? '&' : '?') . 'auth=login');
    exit;
}
header('Location: index.php?auth=login');
exit;
