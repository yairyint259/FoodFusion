<?php
// to reset password as admin
require_once __DIR__ . '/db.php';
if (!empty($_SESSION['user_id']) && !empty($_SESSION['is_admin'])) {
    header('Location: admin-dashboard.php');
} else {
    header('Location: index.php');
}
exit;
