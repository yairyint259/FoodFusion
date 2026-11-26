<?php
require_once __DIR__ . '/db.php';

$pageTitle = 'Contact Us';
$error = '';

$values = [
    'reason' => 'General question',
    'subject' => '',
    'message' => ''
];

$isLoggedIn = false;
$username = '';
$email = '';

// Get the logged-in user's details from the `user` table.
if (!empty($_SESSION['user_id'])) {
    $userId = (int) $_SESSION['user_id'];

    $stmt = $conn->prepare(
        'SELECT username, email
         FROM `user`
         WHERE user_id = ?'
    );

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {
        $isLoggedIn = true;
        $username = $user['username'];
        $email = $user['email'];
    }
}

$reasons = [
    'General question',
    'Recipe suggestion',
    'Technical issue',
    'Feedback',
    'Partnership'
];

// Process the contact form.
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ($values as $key => $unused) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    if (
        !in_array($values['reason'], $reasons, true) ||
        $values['subject'] === '' ||
        mb_strlen($values['subject']) > 180 ||
        $values['message'] === '' ||
        mb_strlen($values['message']) > 10000
    ) {
        $error = 'Please choose a reason and enter a subject and message.';
    } else {
        try {
            // Use account details from the database, not form input.
            $stmt = $conn->prepare(
                'INSERT INTO contact_message
                    (username, email, reason, subject, message)
                 VALUES (?, ?, ?, ?, ?)'
            );

            $stmt->bind_param(
                'sssss',
                $username,
                $email,
                $values['reason'],
                $values['subject'],
                $values['message']
            );

            $stmt->execute();
            $stmt->close();

            $_SESSION['flash'] =
                'Thanks for contacting FoodFusion. Your message has been received.';

            header('Location: contact.php');
            exit;
        } catch (mysqli_sql_exception $ex) {
            error_log(
                'FoodFusion contact form error: ' . $ex->getMessage()
            );

            $error = 'Your message could not be saved right now. Please try again.';
        }
    }
}

require __DIR__ . '/header.php';
?>

<div class="page-heading">
    <span class="eyebrow">We would love to hear from you</span>

    <h1>Contact Us</h1>

    <p class="muted">
        Questions, suggestions, or feedback? Send us a message.
    </p>
</div>

<?php if (!$isLoggedIn): ?>

    <section class="panel" style="max-width: 700px; margin: 0 auto; text-align: center;">

        <h2>Login Required</h2>

        <p class="muted">
            Please log in to your FoodFusion account or register for a new
            account before contacting us.
        </p>

        <div style="display: flex; justify-content: center; gap: 12px;
                    flex-wrap: wrap; margin-top: 24px;">

            <a class="button" href="index.php?auth=login&amp;return_to=contact.php">
                Log In
            </a>

            <a class="button button-secondary" href="register.php?return_to=contact.php">
                Register
            </a>

        </div>
    </section>

<?php else: ?>

    <form class="panel" method="post" style="max-width: 850px">

        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <?php if ($error !== ''): ?>
            <p class="error" role="alert">
                <?= e($error) ?>
            </p>
        <?php endif; ?>

        <div class="form-grid">

            <!-- Username: automatically retrieved from the account -->
            <div class="field field-full">
                <label for="username">Username</label>

                <input id="username" type="text" value="<?= e($username) ?>" readonly aria-describedby="username-hint">

                <small id="username-hint" class="muted">
                    Your username is automatically taken from your account.
                </small>
            </div>

            <!-- Email: automatically retrieved from the account -->
            <div class="field field-full">
                <label for="email">Email</label>

                <input id="email" type="email" value="<?= e($email) ?>" readonly aria-describedby="email-hint">

                <small id="email-hint" class="muted">
                    We will use your registered email address for this enquiry.
                </small>
            </div>

            <!-- Reason -->
            <div class="field field-full">
                <label for="reason">Reason</label>

                <select id="reason" name="reason" required>
                    <?php foreach ($reasons as $reason): ?>
                        <option value="<?= e($reason) ?>" <?= $values['reason'] === $reason ? 'selected' : '' ?>>
                            <?= e($reason) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <small class="muted">
                    Select the option that best matches your enquiry.
                </small>
            </div>

            <!-- Subject -->
            <div class="field field-full">
                <label for="subject">Subject</label>

                <input id="subject" name="subject" type="text" maxlength="180" required value="<?= e($values['subject']) ?>"
                    placeholder="e.g. Recipe suggestion">

                <small class="muted">
                    Give your message a short, clear title.
                </small>
            </div>

            <!-- Message -->
            <div class="field field-full">
                <label for="message">Message</label>

                <textarea id="message" name="message" maxlength="10000" required
                    placeholder="Write your question, suggestion, or feedback here..."><?= e($values['message']) ?></textarea>

                <small class="muted">
                    Explain your enquiry clearly so our team can understand it.
                    Maximum 10,000 characters.
                </small>
            </div>

            <!-- Submit -->
            <div class="field field-full">
                <button class="button" type="submit">
                    Send Message
                </button>
            </div>

        </div>
    </form>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>