<?php
$pageTitle = $pageTitle ?? 'FoodFusion';
$loggedIn = !empty($_SESSION['user_id']);
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$loginError = '';
if (($_GET['auth'] ?? '') === 'login' && isset($_SESSION['login_error'])) {
    $loginError = is_string($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
    unset($_SESSION['login_error']);
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="Discover recipes, share cooking ideas, and explore food cultures with FoodFusion.">
    <title><?= e($pageTitle) ?> | FoodFusion</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>
    <header class="site-header">
        <div class="brand-row wrap"><a class="brand" href="index.php"><span class="brand-mark"><i
                        class="fa-solid fa-utensils" aria-hidden="true"></i></span> FoodFusion</a>
            <p>Discover, cook and share your passion for food.</p>
        </div>
        <nav class="nav wrap" aria-label="Main navigation">
            <div class="nav-links">
                <a href="index.php" <?= $currentPage === 'index.php' ? 'aria-current="page"' : '' ?>><i
                        class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
                <a href="recipes.php" <?= $currentPage === 'recipes.php' ? 'aria-current="page"' : '' ?>><i
                        class="fa-solid fa-book-open" aria-hidden="true"></i> Recipes</a>
                <a href="community.php" <?= $currentPage === 'community.php' ? 'aria-current="page"' : '' ?>><i
                        class="fa-solid fa-users" aria-hidden="true"></i> Community Cookbook</a>
                <div class="nav-dropdown"><span class="nav-dropdown-label"><i class="fa-solid fa-book-bookmark"
                            aria-hidden="true"></i> Resources <i class="fa-solid fa-chevron-down"
                            aria-hidden="true"></i></span>
                    <div class="nav-dropdown-menu"><a href="culinaryRes.php"><i class="fa-solid fa-utensils"
                                aria-hidden="true"></i> Culinary Resources</a><a href="eduRes.php"><i
                                class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Education &amp; Learning</a>
                    </div>
                </div>
                <a href="about.php" <?= $currentPage === 'about.php' ? 'aria-current="page"' : '' ?>><i
                        class="fa-solid fa-circle-info" aria-hidden="true"></i> About Us</a>
                <a href="contact.php"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Contact</a>
            </div>
            <div class="nav-account">
                <?php if ($loggedIn): ?><span class="welcome"><i class="fa-solid fa-user" aria-hidden="true"></i>
                        <?= e($_SESSION['username'] ?? 'Member') ?></span><?php if (!empty($_SESSION['is_admin'])): ?><a
                            class="button button-small button-outline" href="admin-dashboard.php"><i
                                class="fa-solid fa-shield-halved" aria-hidden="true"></i> Admin Dashboard</a><?php endif; ?>
                    <form action="logout.php" method="post" class="inline-form"><input type="hidden" name="csrf_token"
                            value="<?= e(csrf_token()) ?>"><input type="hidden" name="return_to"
                            value="<?= e($_SERVER['REQUEST_URI'] ?? 'index.php') ?>"><button
                            class="button button-small button-outline" type="submit"><i
                                class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Log out</button></form>
                <?php else: ?><button class="button button-small button-outline" type="button" data-open-auth="login"><i
                            class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Login</button><button
                        class="button button-small" type="button" data-open-auth="register"><i class="fa-solid fa-user-plus"
                            aria-hidden="true"></i> Join Us</button><?php endif; ?>
            </div>
        </nav>
    </header>
    <div class="auth-backdrop" id="auth-backdrop" hidden></div>
    <section class="auth-modal" id="login-modal" role="dialog" aria-modal="true" aria-labelledby="login-modal-title"
        hidden>
        <button class="modal-close" type="button" data-close-auth aria-label="Close login form">&times;</button>
        <span class="eyebrow">Welcome back</span>
        <h2 id="login-modal-title">Log in</h2>
        <p class="muted">Pick up where your kitchen story left off.</p>
        <?php if ($loginError !== ''): ?>
            <div class="error auth-error" role="alert" aria-live="assertive"><?= e($loginError) ?></div><?php endif; ?>
        <form method="post" action="login.php" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">
            <div class="field"><label for="modal-login-identity">Username or email</label><input
                    id="modal-login-identity" name="identity" autocomplete="username"
                    placeholder="Enter your username or email" required><small class="field-hint">Use the username or
                    email address you registered with.</small></div>
            <div class="field"><label for="modal-login-password">Password</label>
                <div class="password-input"><input id="modal-login-password" name="password" type="password"
                        autocomplete="current-password" placeholder="Enter your password" required><label
                        class="password-visibility"><input type="checkbox"
                            data-password-toggle="modal-login-password"><span>Show password</span></label></div><small
                    class="field-hint">Enter the password for your FoodFusion account.</small>
            </div>
            <button class="button" type="submit">Log in</button>
        </form>
        <p class="modal-switch">After 3 incorrect password attempts, the account is locked for 3 minutes.</p>
        <p class="modal-switch">New to FoodFusion? <button type="button" class="text-button"
                data-open-auth="register">Create an account</button></p>
    </section>
    <section class="auth-modal" id="register-modal" role="dialog" aria-modal="true"
        aria-labelledby="register-modal-title" hidden><button class="modal-close" type="button" data-close-auth
            aria-label="Close registration form">&times;</button><span class="eyebrow">Join our table</span>
        <h2 id="register-modal-title">Create an account</h2>
        <p class="muted">Save favourites, share recipes, and learn together.</p>
        <form method="post" action="register.php" class="auth-form"><input type="hidden" name="csrf_token"
                value="<?= e(csrf_token()) ?>"><input type="hidden" name="return_to"
                value="<?= e($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">
            <div class="field"><label for="modal-register-username">Username</label><input id="modal-register-username"
                    name="username" minlength="3" maxlength="50" autocomplete="username" placeholder="e.g. FoodLover23"
                    required><small class="field-hint">Choose a username between 3 and 50 characters.</small></div>
            <div class="field"><label for="modal-register-email">Email</label><input id="modal-register-email"
                    name="email" type="email" maxlength="254" autocomplete="email" placeholder="you@example.com"
                    required><small class="field-hint">Use an email address you can access.</small></div>
            <div class="field"><label for="modal-register-password">Password</label>
                <div class="password-input"><input id="modal-register-password" name="password" type="password"
                        minlength="8" autocomplete="new-password" placeholder="Create a strong password" required><label
                        class="password-visibility"><input type="checkbox"
                            data-password-toggle="modal-register-password"><span>Show password</span></label></div>
                <small class="field-hint">Use at least 8 characters, including at least one letter and one
                    number.</small>
            </div>
            <div class="field"><label for="modal-register-confirm-password">Confirm password</label>
                <div class="password-input"><input id="modal-register-confirm-password" name="confirm_password"
                        type="password" minlength="8" autocomplete="new-password"
                        placeholder="Enter the same password again" required><label class="password-visibility"><input
                            type="checkbox" data-password-toggle="modal-register-confirm-password"><span>Show
                            password</span></label></div><small class="field-hint">Type the same password again to catch
                    typing mistakes.</small>
            </div><button class="button" type="submit">Create account</button>
        </form>
        <p class="modal-switch">Already a member? <button type="button" class="text-button" data-open-auth="login">Log
                in</button></p>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const backdrop = document.getElementById('auth-backdrop');
            const modals = {
                login: document.getElementById('login-modal'),
                register: document.getElementById('register-modal')
            };
            let lastFocused = null;

            // One delegated handler is shared by login, password, and confirmation checkboxes.
            document.addEventListener('change', event => {
                const checkbox = event.target;
                if (!(checkbox instanceof HTMLInputElement) || !checkbox.matches('[data-password-toggle]')) return;
                const target = document.getElementById(checkbox.dataset.passwordToggle);
                if (target instanceof HTMLInputElement) target.type = checkbox.checked ? 'text' : 'password';
            });

            function closeModal() {
                Object.values(modals).forEach(modal => { modal.hidden = true; });
                backdrop.hidden = true;
                document.body.classList.remove('modal-open');
                if (lastFocused) lastFocused.focus();
            }

            function openModal(name, trigger) {
                if (!modals[name]) return;
                lastFocused = trigger || document.activeElement;
                Object.values(modals).forEach(modal => { modal.hidden = true; });
                backdrop.hidden = false;
                modals[name].hidden = false;
                document.body.classList.add('modal-open');
                const firstInput = modals[name].querySelector('input:not([type="hidden"])');
                if (firstInput) firstInput.focus();
            }

            document.addEventListener('click', event => {
                const opener = event.target.closest('[data-open-auth]');
                if (opener) {
                    event.preventDefault();
                    openModal(opener.dataset.openAuth, opener);
                    return;
                }
                if (event.target.closest('[data-close-auth]') || event.target === backdrop) closeModal();
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !backdrop.hidden) closeModal();
            });
            const requested = new URLSearchParams(window.location.search).get('auth');
            if (requested && modals[requested]) {
                openModal(requested);
                const cleanUrl = new URL(window.location.href);
                cleanUrl.searchParams.delete('auth');
                window.history.replaceState({}, '', cleanUrl);
                document.querySelectorAll('input[name="return_to"]').forEach(input => {
                    input.value = cleanUrl.pathname.split('/').pop() + cleanUrl.search;
                });
            }
        });
    </script>
    <main class="wrap main-content">
        <?php if ($notice = flash_message()): ?>
            <div class="notice" role="status"><?= e($notice) ?></div><?php endif; ?>