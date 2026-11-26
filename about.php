<?php require_once __DIR__ . '/db.php';
$pageTitle = 'About Us';
require __DIR__ . '/header.php'; ?>
<div class="page-heading"><span class="eyebrow">Our story</span>
    <h1>Food connects us all.</h1>
    <p class="lead">FoodFusion is a place to discover dishes, share knowledge, and celebrate the cultures and memories
        that make food special.</p>
</div>
<section class="two-col section">
    <div class="panel">
        <h2>Our mission</h2>
        <p class="muted">Make cooking more welcoming by helping people explore recipes, learn practical skills, and
            share what they know.</p>
    </div>
    <div class="panel">
        <h2>Our values</h2>
        <ul class="list-clean">
            <li>Curiosity about food and culture.</li>
            <li>Respect for different traditions and dietary needs.</li>
            <li>Sharing knowledge with kindness.</li>
            <li>Clear, useful and accessible information.</li>
        </ul>
    </div>
</section><img class="hero-image" style="max-height:380px"
    src="https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&fit=crop&w=1400&q=85"
    alt="People preparing food together" loading="lazy">
<?php require __DIR__ . '/footer.php'; ?>