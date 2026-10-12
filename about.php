<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$pageTitle = 'About — Cebu Emergency Hotlines';
include __DIR__ . '/header.php';
?>
<main class="page-content">
    <section class="about-hero">
        <h1 class="about-hero__eyebrow">ABOUT</h1>
        <p class="about-hero__quote">&ldquo;Your lifeline in Cebu Province.&rdquo;</p>
    </section>

    <section class="about-body">
        <p>
            The Cebu Province Emergency Hotline is a centralized service established to provide
            immediate assistance during critical situations. Operating 24 hours a day, 7 days a
            week, this ensures that residents and visitors across the province can access rapid
            response and reliable guidance in times of need.
        </p>
        <p>
            Our service is coordinated with local hospitals, fire protection units, police
            departments, and disaster risk reduction agencies to guarantee that every call is
            directed to the appropriate authority without delay.
        </p>
    </section>
</main>
<?php include __DIR__ . '/footer.php'; ?>
