<?php
// Private FoodFusion admin area for site overview and member support.
require_once __DIR__ . '/db.php';
require_admin();

$pageTitle = 'Admin Dashboard';
$error = '';
$selectedUserId = 0;

// Display one-time success messages after redirects.
$flashMessage = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Count the main records for the dashboard overview.
$stats = [];

foreach ([
    'Members' => 'user',
    'Recipes' => 'recipe',
    'Community posts' => 'community_post',
    'Contact messages' => 'contact_message',
] as $label => $table) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM `$table`");
    $stats[$label] = (int) $result->fetch_assoc()['total'];
}

// Process the administrator's member password-reset form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $selectedUserId = filter_input(
        INPUT_POST,
        'user_id',
        FILTER_VALIDATE_INT
    ) ?: 0;

    $adminPassword = (string) ($_POST['admin_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (
        $selectedUserId < 1 ||
        $adminPassword === '' ||
        $newPassword === '' ||
        $confirmPassword === ''
    ) {
        $error = 'Complete every field before submitting the reset form.';
    } elseif (
        strlen($newPassword) < 8 ||
        !preg_match('/[A-Za-z]/', $newPassword) ||
        !preg_match('/[0-9]/', $newPassword)
    ) {
        $error = 'The new password must be at least 8 characters and include a letter and a number.';
    } elseif (!hash_equals($newPassword, $confirmPassword)) {
        $error = 'The new password and confirmation do not match.';
    } else {
        try {
            // Verify the current administrator's password.
            $adminId = (int) $_SESSION['user_id'];

            $adminQuery = $conn->prepare(
                'SELECT password_hash
                 FROM `user`
                 WHERE user_id = ? AND is_admin = 1
                 LIMIT 1'
            );

            $adminQuery->bind_param('i', $adminId);
            $adminQuery->execute();
            $adminAccount = $adminQuery->get_result()->fetch_assoc();
            $adminQuery->close();

            if (
                !$adminAccount ||
                !password_verify(
                    $adminPassword,
                    $adminAccount['password_hash']
                )
            ) {
                $error = 'Administrator password is incorrect.';
            } else {
                // Only allow password resets for regular member accounts.
                $memberQuery = $conn->prepare(
                    'SELECT user_id, username
                     FROM `user`
                     WHERE user_id = ? AND is_admin = 0
                     LIMIT 1'
                );

                $memberQuery->bind_param('i', $selectedUserId);
                $memberQuery->execute();
                $member = $memberQuery->get_result()->fetch_assoc();
                $memberQuery->close();

                if (!$member) {
                    $error = 'Select a valid member account to reset.';
                } else {
                    $passwordHash = password_hash(
                        $newPassword,
                        PASSWORD_DEFAULT
                    );

                    $update = $conn->prepare(
                        'UPDATE `user`
                         SET password_hash = ?,
                             failed_attempts = 0,
                             locked_until = NULL
                         WHERE user_id = ? AND is_admin = 0'
                    );

                    $update->bind_param(
                        'si',
                        $passwordHash,
                        $selectedUserId
                    );

                    $update->execute();
                    $update->close();

                    $_SESSION['flash'] =
                        'Password reset completed for ' .
                        $member['username'] .
                        '. The member can log in with the new password.';

                    header('Location: admin-dashboard.php');
                    exit;
                }
            }
        } catch (mysqli_sql_exception $exception) {
            error_log(
                'FoodFusion dashboard password reset failed: ' .
                $exception->getMessage()
            );

            $error = 'The request could not be completed. Please try again.';
        }
    }
}

// Member options for the password-reset form.
$members = $conn->query(
    'SELECT user_id, username, email
     FROM `user`
     WHERE is_admin = 0
     ORDER BY username'
);

// Recent recipes.
$recentRecipes = $conn->query(
    'SELECT r.title, r.created_at, u.username
     FROM recipe r
     JOIN `user` u ON u.user_id = r.user_id
     ORDER BY r.created_at DESC
     LIMIT 5'
);

// Recent contact enquiries submitted through contact.php.
$contactMessages = $conn->query(
    'SELECT username, email, reason, subject, message, created_at
     FROM contact_message
     ORDER BY created_at DESC
     LIMIT 20'
);

// Recent member accounts.
$memberRows = $conn->query(
    'SELECT username, email, created_at, failed_attempts, locked_until
     FROM `user`
     WHERE is_admin = 0
     ORDER BY created_at DESC
     LIMIT 100'
);

require __DIR__ . '/header.php';
?>

<section class="page-heading admin-heading">
    <p class="eyebrow">Private area</p>
    <h1>Admin Dashboard</h1>

    <p class="muted">
        Welcome, <?= e($_SESSION['username'] ?? 'Administrator') ?>.
        Review site activity and help members regain account access.
    </p>
</section>

<?php if (is_string($flashMessage) && $flashMessage !== ''): ?>
    <p class="notice" role="status">
        <?= e($flashMessage) ?>
    </p>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <p class="notice notice-error" role="alert">
        <?= e($error) ?>
    </p>
<?php endif; ?>

<!-- Website statistics -->
<section class="admin-stats" aria-label="Website overview">
    <?php foreach ($stats as $label => $total): ?>
        <article class="admin-stat-card">
            <span><?= e($label) ?></span>
            <strong><?= number_format($total) ?></strong>
        </article>
    <?php endforeach; ?>
</section>

<!-- Password reset and recent recipes -->
<section class="admin-dashboard-grid">

    <article class="panel admin-panel">
        <p class="eyebrow">Member support</p>
        <h2>Reset a member password</h2>

        <p class="muted">
            Confirm the member's identity before proceeding.
            Your current administrator password is required for every reset.
        </p>

        <form method="post" action="admin-dashboard.php" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="field">
                <label for="member-user">Member account</label>

                <select id="member-user" name="user_id" required>
                    <option value="">Choose a member</option>

                    <?php while ($memberOption = $members->fetch_assoc()): ?>
                        <option value="<?= (int) $memberOption['user_id'] ?>" <?= $selectedUserId === (int) $memberOption['user_id'] ? 'selected' : '' ?>>

                            <?= e($memberOption['username']) ?>
                            — <?= e($memberOption['email']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="field">
                <label for="admin-current-password">
                    Your administrator password
                </label>

                <input id="admin-current-password" name="admin_password" type="password" autocomplete="current-password"
                    required>

                <small class="field-hint">
                    Re-enter your own password to authorize this change.
                </small>
            </div>

            <div class="field">
                <label for="member-new-password">New member password</label>

                <input id="member-new-password" name="new_password" type="password" minlength="8"
                    autocomplete="new-password" required>

                <small class="field-hint">
                    Use at least 8 characters, including a letter and a number.
                </small>
            </div>

            <div class="field">
                <label for="member-confirm-password">Confirm new password</label>

                <input id="member-confirm-password" name="confirm_password" type="password" minlength="8"
                    autocomplete="new-password" required>
            </div>

            <button class="button" type="submit">
                Reset member password
            </button>
        </form>

        <p class="field-hint admin-security-note">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            Passwords are saved as hashes. This page never displays existing
            passwords or hashes.
        </p>
    </article>

    <article class="panel admin-panel">
        <p class="eyebrow">Latest contributions</p>
        <h2>Recent recipes</h2>

        <?php if ($recentRecipes->num_rows === 0): ?>
            <p class="muted">No recipes have been submitted yet.</p>
        <?php else: ?>
            <ul class="admin-recent-list">
                <?php while ($recipe = $recentRecipes->fetch_assoc()): ?>
                    <li>
                        <strong><?= e($recipe['title']) ?></strong>

                        <span>
                            by <?= e($recipe['username']) ?>
                        </span>

                        <time datetime="<?= e($recipe['created_at']) ?>">
                            <?= e(date('M j, Y', strtotime($recipe['created_at']))) ?>
                        </time>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>

        <a class="button button-outline admin-view-link" href="recipes.php">
            View recipe collection
        </a>
    </article>

</section>

<!-- NEW: Contact messages -->
<section class="panel admin-members-panel">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Member enquiries</p>
            <h2>Contact Messages</h2>
        </div>

        <span class="muted">
            Latest 20 messages
        </span>
    </div>

    <?php if ($contactMessages->num_rows === 0): ?>
        <p class="muted">
            No contact messages have been submitted yet.
        </p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Reason</th>
                        <th>Subject and message</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($contact = $contactMessages->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <time datetime="<?= e($contact['created_at']) ?>">
                                    <?= e(date(
                                        'M j, Y H:i',
                                        strtotime($contact['created_at'])
                                    )) ?>
                                </time>
                            </td>

                            <td><?= e($contact['username']) ?></td>
                            <td><?= e($contact['email']) ?></td>
                            <td>
                                <span class="tag">
                                    <?= e($contact['reason']) ?>
                                </span>
                            </td>

                            <td>
                                <strong><?= e($contact['subject']) ?></strong>

                                <details>
                                    <summary>Read message</summary>
                                    <p><?= nl2br(e($contact['message'])) ?></p>
                                </details>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- Member accounts -->
<section class="panel admin-members-panel">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Account overview</p>
            <h2>Member accounts</h2>
        </div>

        <span class="muted">
            Password values are never shown.
        </span>
    </div>

    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Joined</th>
                    <th>Login status</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($memberRows->num_rows === 0): ?>
                    <tr>
                        <td colspan="4">No member accounts yet.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($memberRow = $memberRows->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($memberRow['username']) ?></td>
                            <td><?= e($memberRow['email']) ?></td>

                            <td>
                                <?= e(date(
                                    'M j, Y',
                                    strtotime($memberRow['created_at'])
                                )) ?>
                            </td>

                            <td>
                                <?php
                                $isLocked =
                                    !empty($memberRow['locked_until']) &&
                                    strtotime($memberRow['locked_until']) > time();
                                ?>

                                <?php if ($isLocked): ?>
                                    <span class="status-pill status-locked">
                                        Temporarily locked
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill">
                                        Available
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>