<?php
require_once __DIR__ . '/db.php';

$pageTitle = 'Community Cookbook';
$error = '';
$openForm = false;

// Handle new community posts.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    verify_csrf();

    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $type = (string) ($_POST['post_type'] ?? 'Discussion');
    $country = trim((string) ($_POST['country'] ?? ''));

    $allowed = ['Tip', 'Cooking Experience', 'Discussion', 'Recipe'];

    if (
        $title === '' ||
        mb_strlen($title) > 180 ||
        $content === '' ||
        mb_strlen($content) > 20000 ||
        mb_strlen($country) > 100 ||
        !in_array($type, $allowed, true)
    ) {
        $error = 'Please complete the form correctly. Check the title, type, country and content.';
        $openForm = true;
    } else {
        try {
            $uid = (int) $_SESSION['user_id'];
            $countryValue = $country !== '' ? $country : null;

            $stmt = $conn->prepare(
                'INSERT INTO community_post
                (user_id, post_type, title, content, country)
                VALUES (?, ?, ?, ?, ?)'
            );

            $stmt->bind_param(
                'issss',
                $uid,
                $type,
                $title,
                $content,
                $countryValue
            );

            $stmt->execute();
            $stmt->close();

            $_SESSION['flash'] = 'Your community post has been published.';
            header('Location: community.php');
            exit;
        } catch (mysqli_sql_exception $ex) {
            error_log($ex->getMessage());
            $error = 'Your post could not be saved. Please try again.';
            $openForm = true;
        }
    }
}

// Get community posts.
$posts = $conn->query(
    'SELECT
        p.post_id,
        p.post_type,
        p.title,
        p.content,
        p.country,
        p.created_at,
        u.username
     FROM community_post p
     JOIN `user` u ON u.user_id = p.user_id
     ORDER BY p.created_at DESC
     LIMIT 50'
)->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/header.php';
?>

<style>
    /* =========================================================
   COMMUNITY COOKBOOK — FOODFUSION THEME
========================================================= */

    .community-page {
        width: min(1150px, calc(100% - 2rem));
        margin: 0 auto;
        color: #f8f9f5;
    }

    /* Hero */

    .community-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        padding: 38px 0 30px;
    }

    .community-hero>div {
        min-width: 0;
    }

    .community-hero h1 {
        margin: 8px 0 12px;
        font-size: clamp(2rem, 4vw, 3rem);
        line-height: 1.15;
        letter-spacing: -.04em;
    }

    .community-hero p {
        max-width: 600px;
        margin: 0;
        color: #c2c8bd;
        line-height: 1.7;
    }

    .community-eyebrow {
        color: #9cff00;
        font-size: .78rem;
        font-weight: 800;
        letter-spacing: 2px;
        text-transform: uppercase;
    }

    /* Shared community buttons */

    .community-share-btn {
        display: inline-flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 46px;
        padding: 12px 20px;
        border: 1px solid #9cff00;
        border-radius: 10px;
        background: #9cff00;
        color: #10140d;
        font: inherit;
        font-weight: 800;
        cursor: pointer;
        transition: background .2s, transform .2s, box-shadow .2s;
    }

    .community-share-btn:hover {
        transform: translateY(-2px);
        background: #b3ff40;
        box-shadow: 0 6px 22px #9cff0030;
    }

    .community-share-btn:focus-visible,
    .community-cancel-btn:focus-visible,
    .community-modal-close:focus-visible {
        outline: 2px solid #ffd400;
        outline-offset: 3px;
    }

    /* Posts heading */

    .community-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin: 10px 0 22px;
    }

    .community-toolbar h2 {
        margin: 0;
        color: #f8f9f5;
        font-size: 1.55rem;
        letter-spacing: -.02em;
    }

    .community-count {
        color: #c2c8bd;
        font-size: .9rem;
    }

    /* Post grid */

    .community-post-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: stretch;
        gap: 20px;
        margin-bottom: 40px;
    }

    /* Post cards */

    .community-post-card {
        position: relative;
        display: flex;
        min-width: 0;
        flex-direction: column;
        padding: 22px;
        overflow: hidden;
        border: 1px solid #343a30;
        border-radius: 16px;
        background: linear-gradient(145deg, #20251e, #141713);
        box-shadow: 0 5px 18px #0002;
        transition: transform .2s, border-color .2s, box-shadow .2s;
    }

    .community-post-card::before {
        position: absolute;
        top: 0;
        right: 22px;
        left: 22px;
        height: 2px;
        border-radius: 0 0 4px 4px;
        background: linear-gradient(90deg, #9cff00, #ffd400);
        content: "";
        opacity: .8;
    }

    .community-post-card:hover {
        transform: translateY(-4px);
        border-color: #65794c;
        box-shadow: 0 12px 28px #0004;
    }

    .community-post-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin: 4px 0 18px;
    }

    .community-post-tag {
        display: inline-block;
        max-width: 100%;
        padding: 5px 11px;
        border: 1px solid #526d34;
        border-radius: 999px;
        background: #28351e;
        color: #c5ff83;
        font-size: .75rem;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .community-post-number {
        flex-shrink: 0;
        color: #899184;
        font-size: .78rem;
    }

    .community-post-card h3 {
        margin: 0 0 12px;
        color: #f8f9f5;
        font-size: 1.18rem;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }

    .community-post-content {
        margin: 0 0 22px;
        color: #cbd0c6;
        font-size: .94rem;
        line-height: 1.8;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    .community-post-footer {
        display: flex;
        flex-direction: column;
        gap: 7px;
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px solid #343a30;
        color: #aeb5a8;
        font-size: .82rem;
        overflow-wrap: anywhere;
    }

    .community-post-author {
        color: #e5ebde;
        font-weight: 700;
    }

    /* Empty state */

    .community-empty {
        padding: 48px 20px;
        border: 1px dashed #526047;
        border-radius: 16px;
        background: #171a16;
        text-align: center;
    }

    .community-empty h3 {
        margin: 0 0 8px;
        color: #f8f9f5;
    }

    .community-empty p {
        max-width: 480px;
        margin: 0 auto;
        color: #c2c8bd;
        line-height: 1.7;
    }

    /* Error message */

    .community-error {
        margin: 0 0 20px;
        padding: 13px 16px;
        border: 1px solid #a34a3d;
        border-radius: 10px;
        background: #351b18;
        color: #ffe4df;
        line-height: 1.6;
    }

    /* =========================================================
   SHARE EXPERIENCE MODAL
========================================================= */

    .community-modal {
        position: fixed;
        inset: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        overflow-y: auto;
        background: rgb(0 0 0 / 78%);
        backdrop-filter: blur(7px);
    }

    .community-modal[hidden] {
        display: none !important;
    }

    .community-modal-dialog {
        position: relative;
        width: 100%;
        max-width: 660px;
        max-height: calc(100vh - 40px);
        max-height: calc(100dvh - 40px);
        overflow-y: auto;
        padding: 30px;
        border: 1px solid #4c5c3c;
        border-radius: 18px;
        background: #171a16;
        color: #f8f9f5;
        box-shadow: 0 25px 80px #0009;
    }

    .community-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 1px solid #343a30;
    }

    .community-modal-header>div {
        min-width: 0;
    }

    .community-modal-header h2 {
        margin: 7px 0 8px;
        color: #f8f9f5;
        font-size: clamp(1.4rem, 4vw, 1.8rem);
        line-height: 1.3;
    }

    .community-modal-header p {
        margin: 0;
        color: #c2c8bd;
        line-height: 1.65;
    }

    .community-modal-close {
        display: grid;
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        place-items: center;
        border: 1px solid #42483d;
        border-radius: 50%;
        background: #242920;
        color: #f8f9f5;
        font-size: 1.6rem;
        line-height: 1;
        cursor: pointer;
        transition: background .2s, color .2s;
    }

    .community-modal-close:hover {
        background: #9cff00;
        color: #10140d;
    }

    /* Form layout */

    .community-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .community-form-field {
        display: flex;
        min-width: 0;
        flex-direction: column;
        gap: 8px;
    }

    .community-form-field.full {
        grid-column: 1 / -1;
    }

    .community-form-field label {
        color: #e5ebde;
        font-size: .9rem;
        font-weight: 700;
    }

    /* Scope form control styles to this popup only */

    .community-modal .community-form-field input,
    .community-modal .community-form-field select,
    .community-modal .community-form-field textarea {
        width: 100%;
        min-width: 0;
        max-width: 100%;
        padding: 12px 14px;
        border: 1px solid #42483d;
        border-radius: 9px;
        background: #0c0e0c;
        color: #f8f9f5;
        font: inherit;
        box-sizing: border-box;
        transition: border-color .2s, box-shadow .2s;
    }

    .community-modal .community-form-field input::placeholder,
    .community-modal .community-form-field textarea::placeholder {
        color: #858d7e;
        opacity: 1;
    }

    .community-modal .community-form-field input:focus,
    .community-modal .community-form-field select:focus,
    .community-modal .community-form-field textarea:focus {
        border-color: #9cff00;
        outline: none;
        box-shadow: 0 0 0 3px #9cff0020;
    }

    .community-modal .community-form-field select option {
        background: #171a16;
        color: #f8f9f5;
    }

    .community-modal .community-form-field textarea {
        min-height: 160px;
        resize: vertical;
        line-height: 1.7;
    }

    .community-form-hint {
        color: #aeb5a8;
        font-size: .8rem;
        line-height: 1.6;
    }

    .community-form-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #343a30;
    }

    .community-cancel-btn {
        min-height: 46px;
        padding: 11px 18px;
        border: 1px solid #42483d;
        border-radius: 10px;
        background: #242920;
        color: #e5ebde;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
        transition: background .2s;
    }

    .community-cancel-btn:hover {
        background: #343a30;
    }

    body.community-modal-open {
        overflow: hidden;
    }

    /* =========================================================
   RESPONSIVE LAYOUT
========================================================= */

    @media (max-width: 900px) {
        .community-post-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 600px) {
        .community-page {
            width: calc(100% - 30px);
        }

        .community-hero {
            align-items: stretch;
            flex-direction: column;
            gap: 18px;
            padding: 25px 0;
        }

        .community-share-btn {
            width: 100%;
        }

        .community-post-grid {
            grid-template-columns: minmax(0, 1fr);
            gap: 15px;
        }

        .community-post-card {
            padding: 20px;
        }

        .community-toolbar {
            align-items: flex-start;
            flex-direction: column;
            gap: 5px;
        }

        .community-modal {
            align-items: flex-start;
            padding: 10px;
        }

        .community-modal-dialog {
            max-height: calc(100vh - 20px);
            max-height: calc(100dvh - 20px);
            padding: 21px;
            border-radius: 14px;
        }

        .community-modal-header {
            gap: 10px;
        }

        .community-form-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .community-form-field.full {
            grid-column: auto;
        }

        .community-form-actions {
            flex-direction: column-reverse;
        }

        .community-form-actions button {
            width: 100%;
        }
    }
</style>

<div class="community-page">

    <section class="community-hero">
        <div>
            <span class="community-eyebrow">Cook · Share · Connect</span>
            <h1>Community Cookbook</h1>
            <p>
                Every cook has a story. Share your kitchen tips, favourite
                dishes and cooking experiences with the FoodFusion community.
            </p>
        </div>

        <?php if (!empty($_SESSION['user_id'])): ?>
            <button class="community-share-btn" type="button" id="open-community-form">
                <span aria-hidden="true">＋</span> Share Your Experience
            </button>
        <?php else: ?>
            <button class="community-share-btn" type="button" data-open-auth="register">
                <span aria-hidden="true">＋</span> Share Your Experience
            </button>
        <?php endif; ?>
    </section>

    <?php if ($error !== ''): ?>
        <p class="community-error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <?php if (!empty($_SESSION['user_id'])): ?>
        <div class="community-modal" id="community-modal" role="dialog" aria-modal="true"
            aria-labelledby="community-modal-title" aria-describedby="community-modal-description" <?= $openForm ? '' : 'hidden' ?>>

            <div class="community-modal-dialog">
                <div class="community-modal-header">
                    <div>
                        <span class="community-eyebrow">Your story matters</span>
                        <h2 id="community-modal-title">Share with the community</h2>
                        <p id="community-modal-description">
                            Share something useful, delicious or inspiring
                            with other food lovers.
                        </p>
                    </div>

                    <button class="community-modal-close" type="button" id="close-community-form"
                        aria-label="Close form">&times;</button>
                </div>

                <form method="post" action="community.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="community-form-grid">
                        <div class="community-form-field">
                            <label for="post_type">Post type</label>
                            <select id="post_type" name="post_type" required>
                                <?php foreach (
                                    ['Discussion', 'Tip', 'Cooking Experience', 'Recipe']
                                    as $option
                                ): ?>
                                    <option value="<?= e($option) ?>" <?= (($_POST['post_type'] ?? 'Discussion') === $option) ? 'selected' : '' ?>>
                                        <?= e($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="community-form-field">
                            <label for="country">Country (optional)</label>
                            <input id="country" name="country" maxlength="100" value="<?= e($_POST['country'] ?? '') ?>"
                                placeholder="e.g. Myanmar">
                        </div>

                        <div class="community-form-field full">
                            <label for="title">Title</label>
                            <input id="title" name="title" maxlength="180" value="<?= e($_POST['title'] ?? '') ?>"
                                placeholder="Give your post a clear title" required>
                        </div>

                        <div class="community-form-field full">
                            <label for="content">Your story or idea</label>
                            <textarea id="content" name="content" maxlength="20000"
                                placeholder="Tell us about your cooking experience, recipe, tip or question..."
                                required><?= e($_POST['content'] ?? '') ?></textarea>
                            <small class="community-form-hint">
                                Be respectful and include enough detail for other members to follow along.
                            </small>
                        </div>
                    </div>

                    <div class="community-form-actions">
                        <button class="community-cancel-btn" type="button" id="cancel-community-form">
                            Cancel
                        </button>

                        <button class="community-share-btn" type="submit">
                            Publish Post
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <section class="section">
        <div class="community-toolbar">
            <h2>From Our Community</h2>
            <span class="community-count">
                <?= count($posts) ?> recent
                <?= count($posts) === 1 ? 'post' : 'posts' ?>
            </span>
        </div>

        <?php if ($posts): ?>
            <div class="community-post-grid">
                <?php foreach ($posts as $post): ?>
                    <article class="community-post-card">
                        <div class="community-post-top">
                            <span class="community-post-tag">
                                <?= e($post['post_type']) ?>
                            </span>
                            <span class="community-post-number">
                                #<?= (int) $post['post_id'] ?>
                            </span>
                        </div>

                        <h3><?= e($post['title']) ?></h3>

                        <p class="community-post-content"><?= e($post['content']) ?></p>

                        <div class="community-post-footer">
                            <span class="community-post-author">
                                By <?= e($post['username']) ?>
                                <?php if (!empty($post['country'])): ?>
                                    · <?= e($post['country']) ?>
                                <?php endif; ?>
                            </span>

                            <time datetime="<?= e(date('c', strtotime($post['created_at']))) ?>">
                                <?= e(date('d M Y', strtotime($post['created_at']))) ?>
                            </time>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="community-empty">
                <h3>Be the first to share your story!</h3>
                <p>
                    Start the conversation with a cooking tip, a favourite
                    recipe or an experience from your kitchen.
                </p>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
    (() => {
        const modal = document.getElementById('community-modal');
        const openButton = document.getElementById('open-community-form');

        if (!modal || !openButton) {
            return;
        }

        const closeButton = document.getElementById('close-community-form');
        const cancelButton = document.getElementById('cancel-community-form');

        let previousFocus = null;

        function openModal() {
            previousFocus = document.activeElement;
            modal.hidden = false;
            document.body.classList.add('community-modal-open');

            const firstField = modal.querySelector('select, input, textarea');
            if (firstField) {
                firstField.focus();
            }
        }

        function closeModal() {
            modal.hidden = true;
            document.body.classList.remove('community-modal-open');

            if (previousFocus) {
                previousFocus.focus();
            }
        }

        openButton.addEventListener('click', openModal);
        closeButton.addEventListener('click', closeModal);
        cancelButton.addEventListener('click', closeModal);

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    })();
</script>

<?php require __DIR__ . '/footer.php'; ?>