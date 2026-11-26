<?php
// Handle PDF downloads before producing any HTML.
if (isset($_GET['download'])) {
    $allowed = [
        'baking-basics.pdf',
        'vegetarian-recipes.pdf',
        'asian-recipes.pdf'
    ];

    $file = basename((string) $_GET['download']);

    if (!in_array($file, $allowed, true)) {
        http_response_code(404);
        exit('Resource not found.');
    }

    $path = __DIR__ . '/private_resources/' . $file;

    if (!is_file($path) || !is_readable($path)) {
        http_response_code(404);
        exit('Resource not found.');
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');

    readfile($path);
    exit;
}

require_once __DIR__ . '/db.php';

$pageTitle = 'Culinary Resources';

require __DIR__ . '/header.php';
?>

<main class="wrap main-content">

    <div class="page-heading">
        <span class="eyebrow">Useful kitchen knowledge</span>
        <h1>Culinary Resources</h1>
        <p class="muted">
            Explore practical guides to help you prepare ingredients,
            improve cooking techniques, and enjoy your food.
        </p>
    </div>

    <!-- Culinary resource cards -->
    <div class="grid">

        <article class="card">
            <img class="recipe-image"
                src="https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&amp;fit=crop&amp;w=900&amp;q=80"
                alt="Ingredients prepared on a kitchen counter" loading="lazy">

            <div class="card-body">
                <h3>Kitchen Preparation</h3>
                <p>
                    Read a recipe fully, prepare ingredients before cooking,
                    and keep your work surface clean.
                </p>
            </div>
        </article>

        <article class="card">
            <img class="recipe-image"
                src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&amp;fit=crop&amp;w=900&amp;q=80"
                alt="Fresh ingredients arranged in bowls" loading="lazy">

            <div class="card-body">
                <h3>Ingredient Basics</h3>
                <p>
                    Learn how herbs, spices, aromatics, and acids can build
                    flavour and balance a dish.
                </p>
            </div>
        </article>

        <article class="card">
            <img class="recipe-image"
                src="https://images.unsplash.com/photo-1556909212-d5b604d0c90d?auto=format&amp;fit=crop&amp;w=900&amp;q=80"
                alt="Cooking utensils and cookware" loading="lazy">

            <div class="card-body">
                <h3>Safer Cooking Habits</h3>
                <p>
                    Wash your hands, avoid cross-contamination, and cook
                    food using safe preparation practices.
                </p>
            </div>
        </article>

    </div>

    <!-- Cooking videos: two columns on desktop -->
    <section class="section panel video-section">

        <h2>Culinary Tutorials and Recipes</h2>

        <p class="muted">
            Watch practical cooking tutorials to learn new recipes,
            improve food preparation, and build confidence in the kitchen.
        </p>

        <article class="video-item">
            <h3>Basic Cooking Skills</h3>

            <p class="muted">
                Learn essential techniques for preparing ingredients
                and cooking everyday meals.
            </p>

            <div class="video-frame">
                <iframe src="https://www.youtube-nocookie.com/embed/ZJy1ajvMU1k" title="Basic Cooking Skills"
                    loading="lazy"
                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
                </iframe>
            </div>

            <p class="muted video-source">
                <a href="https://www.youtube.com/watch?v=ZJy1ajvMU1k" target="_blank" rel="noopener noreferrer">
                    Watch on YouTube
                </a>
            </p>
        </article>

        <article class="video-item">
            <h3>How to Make Vegetable Soup</h3>

            <p class="muted">
                Follow a simple soup-making method and learn how to
                combine vegetables, stock, and seasonings.
            </p>

            <div class="video-frame">
                <iframe src="https://www.youtube-nocookie.com/embed/9F2it2twYwU" title="Vegetable Soup Tutorial"
                    loading="lazy"
                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
                </iframe>
            </div>

            <p class="muted video-source">
                <a href="https://www.youtube.com/watch?v=9F2it2twYwU" target="_blank" rel="noopener noreferrer">
                    Watch on YouTube
                </a>
            </p>
        </article>

        <article class="video-item">
            <h3>Preparing Scrambled Eggs</h3>

            <p class="muted">
                Practise a simple breakfast recipe and learn how cooking
                temperature affects the texture of eggs.
            </p>

            <div class="video-frame">
                <iframe src="https://www.youtube-nocookie.com/embed/PUP7U5vTMM0" title="Scrambled Eggs Cooking Tutorial"
                    loading="lazy"
                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
                </iframe>
            </div>

            <p class="muted video-source">
                <a href="https://www.youtube.com/watch?v=PUP7U5vTMM0" target="_blank" rel="noopener noreferrer">
                    Watch on YouTube
                </a>
            </p>
        </article>

    </section>

    <!-- PDF resources: two columns on desktop -->
    <section class="section panel">

        <h2>Downloadable Culinary PDFs</h2>

        <p class="muted">
            Choose a guide to download it as a PDF.
        </p>

        <ul class="resource-list">

            <li>
                <strong>Baking Basics</strong>

                <p class="muted">
                    A starting point for baking techniques and kitchen
                    measurements.
                </p>

                <a class="pdf-link" href="culinaryRes.php?download=baking-basics.pdf">
                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                    Download PDF
                </a>
            </li>

            <li>
                <strong>Vegetarian Recipes</strong>

                <p class="muted">
                    Ideas for flavourful meat-free meals.
                </p>

                <a class="pdf-link" href="culinaryRes.php?download=vegetarian-recipes.pdf">
                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                    Download PDF
                </a>
            </li>

            <li>
                <strong>Asian Recipes</strong>

                <p class="muted">
                    Recipe inspiration from a range of Asian cuisines.
                </p>

                <a class="pdf-link" href="culinaryRes.php?download=asian-recipes.pdf">
                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                    Download PDF
                </a>
            </li>

        </ul>

    </section>

</main>

<?php require __DIR__ . '/footer.php'; ?>