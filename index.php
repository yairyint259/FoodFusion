<?php
require_once __DIR__ . '/db.php';
$pageTitle = 'Home';
$recipes = [];
try {
    $sql = "SELECT r.recipe_id, r.title, r.description, r.duration_minutes, r.servings, r.image_path, c.category_name, cu.cuisine_name, d.difficulty_name FROM recipe r JOIN category c ON c.category_id=r.category_id JOIN cuisine cu ON cu.cuisine_id=r.cuisine_id JOIN cooking_difficulty d ON d.difficulty_id=r.difficulty_id ORDER BY r.created_at DESC LIMIT 3";
    $result = $conn->query($sql);
    $recipes = $result->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
}
require __DIR__ . '/header.php';
?>
<section class="hero">
    <div class="hero-copy">
        <span class="eyebrow">A community for curious cooks</span>
        <h1>Good food.<br><span>Great stories.</span></h1>
        <p class="lead">Discover recipes from around the world, learn new kitchen skills, and share the dishes that
            bring people together.</p>
        <div class="hero-actions">
            <a class="button" href="recipes.php">Explore recipes</a>
            <a class="button button-outline" href="community.php">Join the community</a>
        </div>
    </div>
    <div class="hero-slideshow" aria-label="FoodFusion food photography slideshow">
        <img class="hero-slide slide-one"
            src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&amp;fit=crop&amp;w=1400&amp;q=85"
            alt="Colourful fresh food served in bowls">
        <img class="hero-slide slide-two"
            src="https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&amp;fit=crop&amp;w=1400&amp;q=85"
            alt="Fresh salad with colourful vegetables">
        <img class="hero-slide slide-three"
            src="https://images.unsplash.com/photo-1473093295043-cdd812d0e601?auto=format&amp;fit=crop&amp;w=1400&amp;q=85"
            alt="Pasta dish with fresh ingredients">
        <img class="hero-slide slide-four"
            src="https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&amp;fit=crop&amp;w=1400&amp;q=85"
            alt="Healthy bowl of fresh vegetables">
        <div class="slideshow-caption"><span>Fresh ideas, every day</span><span class="slideshow-dots"
                aria-hidden="true">● ● ● ●</span></div>
    </div>
</section>
<section class="section">
    <div class="section-heading">
        <div><span class="eyebrow">From our kitchen</span>
            <h2>Latest recipes</h2>
        </div><a href="recipes.php">Browse all recipes →</a>
    </div>
    <?php if ($recipes): ?>
        <div class="grid"><?php foreach ($recipes as $recipe): ?>
                <article class="card"><img class="recipe-image"
                        src="<?= e($recipe['image_path'] ?: 'images/recipe-placeholder.svg') ?>"
                        alt="<?= e($recipe['title']) ?>" loading="lazy" data-image-fallback="images/recipe-placeholder.svg">
                    <div class="card-body"><span class="tag"><?= e($recipe['category_name']) ?></span>
                        <h3><a href="recipes.php?id=<?= (int) $recipe['recipe_id'] ?>"><?= e($recipe['title']) ?></a></h3>
                        <p><?= e(mb_strimwidth((string) ($recipe['description'] ?? ''), 0, 125, '…')) ?></p>
                        <div class="meta"><span><?= e($recipe['cuisine_name']) ?></span><span>·
                                <?= e($recipe['difficulty_name']) ?></span><?php if ($recipe['duration_minutes']): ?><span>·
                                    <?= (int) $recipe['duration_minutes'] ?> min</span><?php endif; ?></div>
                    </div>
                </article><?php endforeach; ?>
        </div><?php else: ?>
        <div class="empty-state">
            <h3>No recipes have been added yet</h3>
            <p>Import the sample data SQL or log in and submit your first recipe.</p><a class="button"
                href="recipes.php">Open recipe collection</a>
        </div><?php endif; ?>
</section>
<section class="section two-col">
    <div class="panel"><span class="eyebrow">Share what you know</span>
        <h2>Every cook has a story.</h2>
        <p class="muted">Share a family favourite, a kitchen tip, or a lesson you learned while cooking.</p><a
            class="button" href="community.php">Visit Community Cookbook</a>
    </div>
    <div class="panel"><span class="eyebrow">Learn something new</span>
        <h2>Build your kitchen confidence.</h2>
        <p class="muted">Explore guides, ingredient ideas, and practical resources for your next meal.</p><a
            class="button button-outline" href="culinaryRes.php">Explore resources</a>
    </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>