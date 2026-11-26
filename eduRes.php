<?php
// Handle PDF downloads before producing any HTML.
if (isset($_GET['download'])) {
    $allowed = [
        'knife-skills-guide.pdf',
        'cooking-techniques-guide.pdf',
        'meal-planning-guide.pdf'
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

$pageTitle = 'Education & Learning';

require __DIR__ . '/header.php';
?>

<main class="wrap main-content">

    <div class="page-heading">
        <span class="eyebrow">Learn at your own pace</span>
        <h1>Education &amp; Learning</h1>
        <p class="muted">
            Develop your cooking knowledge with practical lessons,
            food preparation tips, and downloadable study guides.
        </p>
    </div>

    <!-- Educational topics -->
    <div class="grid">

        <article class="card">
            <div class="card-body">
                <span class="tag">Beginner</span>
                <h3>Knife Skills and Preparation</h3>
                <p>
                    Use a stable cutting board, keep your fingers safely
                    positioned, and prepare ingredients before cooking.
                </p>
            </div>
        </article>

        <article class="card">
            <div class="card-body">
                <span class="tag">Technique</span>
                <h3>Understanding Heat</h3>
                <p>
                    Learn how low, medium, and high heat affect cooking.
                    Adjust the temperature to help prevent food from
                    burning or cooking unevenly.
                </p>
            </div>
        </article>

        <article class="card">
            <div class="card-body">
                <span class="tag">Planning</span>
                <h3>Meal Planning Basics</h3>
                <p>
                    Choose flexible recipes, check your pantry, and prepare
                    a shopping list to save time and reduce food waste.
                </p>
            </div>
        </article>

    </div>

    <!-- Individual educational videos -->
    <section class="section panel video-section">

        <h2>Learn Through Cooking Lessons</h2>

        <p class="muted">
            Explore practical lessons covering food safety and safe
            preparation habits. Use these topics to build good kitchen
            practices alongside your cooking skills.
        </p>

        <article class="video-item">
            <h3>Food Safety Basics</h3>

            <p class="muted">
                Review basic food-handling practices and learn why
                cleanliness matters when preparing meals.
            </p>

            <div class="video-frame">
                <iframe src="https://www.youtube-nocookie.com/embed/iguM_pqetzo" title="Food Safety Basics"
                    loading="lazy"
                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
                </iframe>
            </div>

            <p class="muted video-source">
                <a href="https://www.youtube.com/watch?v=iguM_pqetzo" target="_blank" rel="noopener noreferrer">
                    Watch on YouTube
                </a>
            </p>
        </article>

        <article class="video-item">
            <h3>Safe Food Handling</h3>

            <p class="muted">
                Learn about safe handling, preventing cross-contamination,
                and keeping food preparation areas clean.
            </p>

            <div class="video-frame">
                <iframe src="https://www.youtube-nocookie.com/embed/vw6r5UJ9cjo" title="Safe Food Handling"
                    loading="lazy"
                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
                </iframe>
            </div>

            <p class="muted video-source">
                <a href="https://www.youtube.com/watch?v=vw6r5UJ9cjo" target="_blank" rel="noopener noreferrer">
                    Watch on YouTube
                </a>
            </p>
        </article>

    </section>

    <!-- Educational PDF resources -->
    <section class="section panel">

        <h2>Downloadable Learning Guides</h2>

        <p class="muted">
            Download a guide to review cooking techniques and kitchen
            skills at your own pace.
        </p>

        <ul class="resource-list">

            <li>
                <strong>Knife Skills Guide</strong>

                <p class="muted">
                    Review ingredient preparation and basic knife safety.
                </p>

                <a class="pdf-link" href="eduRes.php?download=knife-skills-guide.pdf">
                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                    Download PDF
                </a>
            </li>

            <li>
                <strong>Cooking Techniques Guide</strong>

                <p class="muted">
                    Learn about cooking methods, heat control, and
                    preparing everyday dishes.
                </p>

                <a class="pdf-link" href="eduRes.php?download=cooking-techniques-guide.pdf">
                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                    Download PDF
                </a>
            </li>

            <li>
                <strong>Meal Planning Guide</strong>

                <p class="muted">
                    Organise meals, prepare shopping lists, and reduce
                    unnecessary food waste.
                </p>

                <a class="pdf-link" href="eduRes.php?download=meal-planning-guide.pdf">
                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                    Download PDF
                </a>
            </li>

        </ul>

    </section>

</main>

<?php require __DIR__ . '/footer.php'; ?>