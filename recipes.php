
<?php
require_once __DIR__ . '/db.php';
$pageTitle = 'Recipe Collection';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'submit_recipe') {
    require_login();
    verify_csrf();

    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $instructions = trim((string) ($_POST['instructions'] ?? ''));
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $cuisineId = filter_input(INPUT_POST, 'cuisine_id', FILTER_VALIDATE_INT);
    $difficultyId = filter_input(INPUT_POST, 'difficulty_id', FILTER_VALIDATE_INT);
    $minutes = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);
    $servings = filter_input(INPUT_POST, 'servings', FILTER_VALIDATE_INT);
    $imagePath = trim((string) ($_POST['image_path'] ?? ''));

    if ($title === '' || mb_strlen($title) > 180 || $instructions === '' || !$categoryId || !$cuisineId || !$difficultyId) {
        $error = 'Please complete the required recipe fields.';
    } elseif ($minutes !== null && $minutes !== false && ($minutes < 1 || $minutes > 1440)) {
        $error = 'Cooking time must be between 1 and 1440 minutes.';
    } elseif ($servings !== null && $servings !== false && ($servings < 1 || $servings > 99)) {
        $error = 'Servings must be between 1 and 99.';
    } elseif ($imagePath !== '' && !filter_var($imagePath, FILTER_VALIDATE_URL) && !preg_match('#^images/[A-Za-z0-9_./-]+$#', $imagePath)) {
        $error = 'Use a valid image URL or a path inside the images folder.';
    } else {
        try {
            $userId = (int) $_SESSION['user_id'];
            $minutesValue = $minutes ?: null;
            $servingsValue = $servings ?: null;
            $imageValue = $imagePath ?: null;
            $descriptionValue = $description ?: null;

            $stmt = $conn->prepare(
                'INSERT INTO recipe
                (user_id, category_id, cuisine_id, difficulty_id, title, description,
                 instructions, duration_minutes, servings, image_path)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $stmt->bind_param(
                'iiiisssiis',
                $userId,
                $categoryId,
                $cuisineId,
                $difficultyId,
                $title,
                $descriptionValue,
                $instructions,
                $minutesValue,
                $servingsValue,
                $imageValue
            );

            $stmt->execute();

            $_SESSION['flash'] = 'Your recipe has been submitted.';
            header('Location: recipes.php');
            exit;

        } catch (mysqli_sql_exception $exception) {
            error_log($exception->getMessage());
            $error = 'The recipe could not be saved. Check the selected category, cuisine and difficulty.';
        }
    }
}

$categories = $conn->query(
    'SELECT category_id, category_name FROM category ORDER BY category_name'
)->fetch_all(MYSQLI_ASSOC);

$cuisines = $conn->query(
    'SELECT cuisine_id, cuisine_name FROM cuisine ORDER BY cuisine_name'
)->fetch_all(MYSQLI_ASSOC);

$difficulties = $conn->query(
    'SELECT difficulty_id, difficulty_name FROM cooking_difficulty ORDER BY difficulty_id'
)->fetch_all(MYSQLI_ASSOC);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$detail = null;
$ingredients = [];
$reviews = [];

if ($id) {
    $stmt = $conn->prepare(
        'SELECT r.*, u.username, c.category_name, cu.cuisine_name, d.difficulty_name
         FROM recipe r
         JOIN `user` u ON u.user_id = r.user_id
         JOIN category c ON c.category_id = r.category_id
         JOIN cuisine cu ON cu.cuisine_id = r.cuisine_id
         JOIN cooking_difficulty d ON d.difficulty_id = r.difficulty_id
         WHERE r.recipe_id = ?'
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $detail = $stmt->get_result()->fetch_assoc();

    if ($detail) {
        $stmt = $conn->prepare(
            'SELECT i.ingredient_name, ri.quantity, ri.notes
             FROM recipe_ingredient ri
             JOIN ingredient i ON i.ingredient_id = ri.ingredient_id
             WHERE ri.recipe_id = ?
             ORDER BY i.ingredient_name'
        );

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $ingredients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt = $conn->prepare(
            'SELECT rv.rating, rv.comment, rv.created_at, u.username
             FROM review rv
             JOIN `user` u ON u.user_id = rv.user_id
             WHERE rv.recipe_id = ?
             ORDER BY rv.created_at DESC'
        );

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'review') {
    require_login();
    verify_csrf();

    $recipeId = filter_input(INPUT_POST, 'recipe_id', FILTER_VALIDATE_INT);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $comment = trim((string) ($_POST['comment'] ?? ''));

    if (!$recipeId || !$rating || $rating < 1 || $rating > 5 || mb_strlen($comment) > 3000) {
        $_SESSION['flash'] = 'Please enter a rating from 1 to 5 and a shorter comment.';
        header('Location: recipes.php' . ($recipeId ? '?id=' . $recipeId : ''));
        exit;
    }

    try {
        $userId = (int) $_SESSION['user_id'];
        $commentValue = $comment ?: null;

        $stmt = $conn->prepare(
            'INSERT INTO review (user_id, recipe_id, rating, comment)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
             rating = VALUES(rating),
             comment = VALUES(comment),
             updated_at = CURRENT_TIMESTAMP'
        );

        $stmt->bind_param('iiis', $userId, $recipeId, $rating, $commentValue);
        $stmt->execute();

        $_SESSION['flash'] = 'Your review has been saved.';

    } catch (mysqli_sql_exception $exception) {
        error_log($exception->getMessage());
        $_SESSION['flash'] = 'The review could not be saved.';
    }

    header('Location: recipes.php?id=' . $recipeId);
    exit;
}

$search = trim((string) ($_GET['q'] ?? ''));
$categoryFilter = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$cuisineFilter = filter_input(INPUT_GET, 'cuisine', FILTER_VALIDATE_INT);
$difficultyFilter = filter_input(INPUT_GET, 'difficulty', FILTER_VALIDATE_INT);
$recipeRows = [];

if (!$id) {
    $sql = 'SELECT r.recipe_id, r.title, r.description, r.duration_minutes,
                   r.servings, r.image_path, c.category_name, cu.cuisine_name,
                   d.difficulty_name
            FROM recipe r
            JOIN category c ON c.category_id = r.category_id
            JOIN cuisine cu ON cu.cuisine_id = r.cuisine_id
            JOIN cooking_difficulty d ON d.difficulty_id = r.difficulty_id
            WHERE (? = "" OR r.title LIKE CONCAT("%", ?, "%")
                   OR r.description LIKE CONCAT("%", ?, "%"))
              AND (? IS NULL OR r.category_id = ?)
              AND (? IS NULL OR r.cuisine_id = ?)
              AND (? IS NULL OR r.difficulty_id = ?)
            ORDER BY r.created_at DESC';

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        'sssiiiiii',
        $search,
        $search,
        $search,
        $categoryFilter,
        $categoryFilter,
        $cuisineFilter,
        $cuisineFilter,
        $difficultyFilter,
        $difficultyFilter
    );

    $stmt->execute();
    $recipeRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

require __DIR__ . '/header.php';
?>

<div class="page-heading">
    <span class="eyebrow">Find your next favourite</span>

    <h1><?= $detail ? e($detail['title']) : 'Recipe Collection' ?></h1>

    <p class="muted">
        <?= $detail
            ? 'Shared by ' . e($detail['username'])
            : 'Search by recipe name, category, cuisine, or cooking difficulty.' ?>
    </p>
</div>

<?php if ($error): ?>
    <p class="error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<?php if ($detail): ?>

    <div class="recipe-detail">
        <div>
            <img
                src="<?= e($detail['image_path'] ?: 'images/recipe-placeholder.svg') ?>"
                alt="<?= e($detail['title']) ?>"
                data-image-fallback="images/recipe-placeholder.svg">

            <div class="meta" style="margin-top:1rem">
                <span class="tag"><?= e($detail['category_name']) ?></span>
                <span><?= e($detail['cuisine_name']) ?></span>
                <span><?= e($detail['difficulty_name']) ?></span>

                <?php if ($detail['duration_minutes']): ?>
                    <span><?= (int) $detail['duration_minutes'] ?> min</span>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <h2>About this recipe</h2>
            <p><?= nl2br(e($detail['description'] ?? '')) ?></p>

            <h2>Ingredients</h2>

            <?php if ($ingredients): ?>
                <ul class="list-clean">
                    <?php foreach ($ingredients as $ingredient): ?>
                        <li>
                            <?= e($ingredient['quantity'] ? $ingredient['quantity'] . ' ' : '') ?>
                            <strong><?= e($ingredient['ingredient_name']) ?></strong>
                            <?= $ingredient['notes'] ? ' — ' . e($ingredient['notes']) : '' ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="muted">No ingredient list has been added yet.</p>
            <?php endif; ?>

            <h2>Instructions</h2>
            <p><?= nl2br(e($detail['instructions'])) ?></p>
        </div>
    </div>

    <section class="section">
        <h2>Reviews</h2>

        <?php if ($reviews): ?>
            <div class="grid">
                <?php foreach ($reviews as $review): ?>
                    <article class="card">
                        <div class="card-body">
                            <strong><?= e($review['username']) ?></strong>

                            <p>
                                <?= str_repeat('★', (int) $review['rating']) .
                                    str_repeat('☆', 5 - (int) $review['rating']) ?>
                            </p>

                            <p><?= nl2br(e($review['comment'] ?? '')) ?></p>

                            <small class="muted"><?= e($review['created_at']) ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="muted">No reviews yet.</p>
        <?php endif; ?>
    </section>

    <?php if (!empty($_SESSION['user_id'])): ?>
        <section class="section panel">
            <h2>Leave a review</h2>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="form_action" value="review">
                <input type="hidden" name="recipe_id" value="<?= (int) $detail['recipe_id'] ?>">

                <div class="field">
                    <label for="rating">Rating</label>

                    <select id="rating" name="rating" required aria-describedby="rating-hint">
                        <option value="">Choose rating</option>
                        <?php for ($r = 5; $r >= 1; $r--): ?>
                            <option value="<?= $r ?>"><?= $r ?> / 5</option>
                        <?php endfor; ?>
                    </select>

                    <small id="rating-hint" class="field-hint">
                        Choose 1 for poor and 5 for excellent.
                    </small>
                </div>

                <div class="field" style="margin-top:1rem">
                    <label for="comment">Comment</label>

                    <textarea
                        id="comment"
                        name="comment"
                        maxlength="3000"
                        placeholder="What did you think of this recipe? (optional)"></textarea>

                    <small class="field-hint">
                        Share what worked well or any changes you made.
                    </small>
                </div>

                <p><button class="button" type="submit">Save review</button></p>
            </form>
        </section>
    <?php else: ?>
        <p class="muted">
            <a href="#login-modal" data-open-auth="login">Log in</a>
            to leave a review.
        </p>
    <?php endif; ?>

    <p><a href="recipes.php">← Back to all recipes</a></p>

<?php else: ?>

    <form class="filters" method="get" action="recipes.php">
        <div class="field">
            <label for="q">Search</label>
            <input
                id="q"
                name="q"
                value="<?= e($search) ?>"
                placeholder="e.g. tacos, tomato soup">
        </div>

        <div class="field">
            <label for="category">Category</label>

            <select id="category" name="category">
                <option value="">All categories</option>

                <?php foreach ($categories as $row): ?>
                    <option
                        value="<?= (int) $row['category_id'] ?>"
                        <?= $categoryFilter === (int) $row['category_id'] ? 'selected' : '' ?>>
                        <?= e($row['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="cuisine">Cuisine Type</label>

            <select id="cuisine" name="cuisine">
                <option value="">All cuisine types</option>

                <?php foreach ($cuisines as $row): ?>
                    <option
                        value="<?= (int) $row['cuisine_id'] ?>"
                        <?= $cuisineFilter === (int) $row['cuisine_id'] ? 'selected' : '' ?>>
                        <?= e($row['cuisine_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="difficulty">Difficulty</label>

            <select id="difficulty" name="difficulty">
                <option value="">Any level</option>

                <?php foreach ($difficulties as $row): ?>
                    <option
                        value="<?= (int) $row['difficulty_id'] ?>"
                        <?= $difficultyFilter === (int) $row['difficulty_id'] ? 'selected' : '' ?>>
                        <?= e($row['difficulty_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field filter-action">
            <label aria-hidden="true">&nbsp;</label>
            <button class="button" type="submit">Search</button>
        </div>
    </form>

    <?php if ($recipeRows): ?>
        <div class="grid">
            <?php foreach ($recipeRows as $recipe): ?>
                <article class="card">
                    <img
                        class="recipe-image"
                        src="<?= e($recipe['image_path'] ?: 'images/recipe-placeholder.svg') ?>"
                        alt="<?= e($recipe['title']) ?>"
                        loading="lazy"
                        data-image-fallback="images/recipe-placeholder.svg">

                    <div class="card-body">
                        <span class="tag"><?= e($recipe['category_name']) ?></span>

                        <h3>
                            <a href="recipes.php?id=<?= (int) $recipe['recipe_id'] ?>">
                                <?= e($recipe['title']) ?>
                            </a>
                        </h3>

                        <p>
                            <?= e(mb_strimwidth(
                                (string) ($recipe['description'] ?? ''),
                                0,
                                125,
                                '…'
                            )) ?>
                        </p>

                        <div class="meta">
                            <span><?= e($recipe['cuisine_name']) ?></span>
                            <span>· <?= e($recipe['difficulty_name']) ?></span>

                            <?php if ($recipe['duration_minutes']): ?>
                                <span>· <?= (int) $recipe['duration_minutes'] ?> min</span>
                            <?php endif; ?>
                        </div>

                        <p>
                            <a href="recipes.php?id=<?= (int) $recipe['recipe_id'] ?>">
                                View recipe →
                            </a>
                        </p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h2>No recipes found</h2>
            <p>Try another search or add a sample recipe using your SQL data file.</p>
        </div>
    <?php endif; ?>

    <!-- Share recipe form -->
    <?php if (!empty($_SESSION['user_id'])): ?>
        <?php
        $isRecipeError = !empty($error) &&
            ($_POST['form_action'] ?? '') === 'submit_recipe';
        ?>

        <section class="section">
            <div class="section-heading"
                 style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem">
                <div>
                    <span class="eyebrow">Pass it on</span>
                    <h2>Share a recipe</h2>
                </div>
            </div>

            <button
                type="button"
                class="button"
                onclick="const f = document.getElementById('share-recipe-form'); f.style.display = f.style.display === 'none' ? 'block' : 'none';">
                Share a Recipe
            </button>

            <form
                class="panel"
                id="share-recipe-form"
                method="post"
                action="recipes.php"
                style="display:<?= $isRecipeError ? 'block' : 'none' ?>;margin-top:1.5rem">

                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="form_action" value="submit_recipe">

                <div class="form-grid">
                    <div class="field">
                        <label for="title">Recipe title *</label>
                        <input
                            id="title"
                            name="title"
                            maxlength="180"
                            placeholder="e.g. Mexican Street Corn Tacos"
                            required>
                        <small class="field-hint">
                            Use the name people will recognise for this dish.
                        </small>
                    </div>

                    <div class="field">
                        <label for="image_path">Image URL (optional)</label>
                        <input
                            id="image_path"
                            name="image_path"
                            type="url"
                            maxlength="500"
                            placeholder="https://example.com/food-photo.jpg">
                        <small class="field-hint">
                            Optional: paste a direct link to an image you are allowed to use.
                        </small>
                    </div>

                    <div class="field">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Choose category</option>

                            <?php foreach ($categories as $row): ?>
                                <option value="<?= (int) $row['category_id'] ?>">
                                    <?= e($row['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-hint">Choose the meal or dish category.</small>
                    </div>

                    <div class="field">
                        <label for="cuisine_id">Cuisine Type *</label>
                        <select id="cuisine_id" name="cuisine_id" required>
                            <option value="">Choose cuisine type</option>

                            <?php foreach ($cuisines as $row): ?>
                                <option value="<?= (int) $row['cuisine_id'] ?>">
                                    <?= e($row['cuisine_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-hint">Choose the cuisine or food tradition.</small>
                    </div>

                    <div class="field">
                        <label for="difficulty_id">Difficulty *</label>
                        <select id="difficulty_id" name="difficulty_id" required>
                            <option value="">Choose difficulty</option>

                            <?php foreach ($difficulties as $row): ?>
                                <option value="<?= (int) $row['difficulty_id'] ?>">
                                    <?= e($row['difficulty_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-hint">Select how challenging the recipe is.</small>
                    </div>

                    <div class="field">
                        <label for="duration_minutes">Minutes</label>
                        <input
                            id="duration_minutes"
                            name="duration_minutes"
                            type="number"
                            min="1"
                            max="1440"
                            placeholder="e.g. 30">
                        <small class="field-hint">
                            Total preparation and cooking time in minutes.
                        </small>
                    </div>

                    <div class="field">
                        <label for="servings">Servings</label>
                        <input
                            id="servings"
                            name="servings"
                            type="number"
                            min="1"
                            max="99"
                            placeholder="e.g. 4">
                        <small class="field-hint">
                            How many people the recipe serves.
                        </small>
                    </div>

                    <div class="field field-full">
                        <label for="description">Short description</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            placeholder="Briefly describe the dish, flavour, or why you recommend it."></textarea>
                        <small class="field-hint">
                            A short introduction helps readers decide whether to try it.
                        </small>
                    </div>

                    <div class="field field-full">
                        <label for="instructions">Instructions *</label>
                        <textarea
                            id="instructions"
                            name="instructions"
                            placeholder="1. Prepare the ingredients...&#10;2. Cook...&#10;3. Serve..."
                            required></textarea>
                        <small class="field-hint">
                            Write the steps in order and include cooking times or temperatures where useful.
                        </small>
                    </div>

                    <div class="field field-full">
                        <button class="button" type="submit">Submit recipe</button>
                    </div>
                </div>
            </form>
        </section>
    <?php else: ?>
        <section class="section panel">
            <h2>Have a recipe to share?</h2>
            <p class="muted">
                Create an account or log in to submit recipes and reviews.
                Everyone can browse the public collection.
            </p>

            <a class="button" href="#register-modal" data-open-auth="register">Join Us</a>
            <a class="button button-outline" href="#login-modal" data-open-auth="login">Log in</a>
        </section>
    <?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
