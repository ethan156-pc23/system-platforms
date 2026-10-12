<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_login();

$pageTitle = 'Cebu Emergency Hotlines';
$flash = flash_get();
$pageScripts = ['/app.js'];
include __DIR__ . '/header.php';
?>
<main class="page-content">

    <?php if ($flash): ?>
        <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php if (is_logged_in()): ?>
        <p class="field-hint">Welcome, <a href="<?= BASE_URL ?>/profile.php"><?= e(current_user_display_name()) ?></a>.</p>
    <?php endif; ?>

    <section class="hero">
        <h1 class="hero__title">CEBU<br>EMERGENCY HOTLINES</h1>
        <p class="hero__subtitle">Available 24/7 &mdash; immediate support for medical, fire, police, and disaster emergencies across Cebu Province.</p>
    </section>

    <div class="search-row">
        <div class="search-bar">
            <input type="search" id="searchInput" placeholder="Search for hotlines..." aria-label="Search for hotlines">
            <button type="button" class="search-bar__button" id="searchButton" aria-label="Search hotlines">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </button>
        </div>
        <select id="municipalitySelect" class="municipality-select" aria-label="Filter by city or municipality">
            <option value="">All Cities/Municipalities</option>
        </select>
    </div>

    <div class="category-filters" id="categoryFilters">
        <!-- category chips are rendered here by app.js -->
    </div>

    <section class="favorite-municipalities" aria-labelledby="favoriteMunicipalitiesTitle">
        <div class="favorite-municipalities__intro">
            <h2 class="favorite-municipalities__title" id="favoriteMunicipalitiesTitle">My priority cities/municipalities</h2>
            <p>Save places you live or travel. Their emergency hotlines will appear first in your directory.</p>
        </div>
        <form class="favorite-municipalities__form" id="favoriteMunicipalityForm">
            <label class="visually-hidden" for="favoriteMunicipalitySelect">Type a city or municipality to prioritize</label>
            <input type="text" id="favoriteMunicipalitySelect" class="municipality-select favorite-municipalities__input"
                   list="favoriteMunicipalityOptions" placeholder="Type to search cities/municipalities" autocomplete="off" required>
            <datalist id="favoriteMunicipalityOptions"></datalist>
            <button type="submit" class="favorite-municipalities__add">Add priority</button>
        </form>
        <div class="favorite-municipalities__list" id="favoriteMunicipalityList" aria-label="Saved priority cities/municipalities"></div>
        <p class="favorite-municipalities__status" id="favoriteMunicipalityStatus" role="status" aria-live="polite"></p>
        <input type="hidden" id="favoriteMunicipalityCsrf" value="<?= e(csrf_token()) ?>">
    </section>

    <div class="section-header">
        <h2 class="section-header__title" id="resultsTitle"><span id="resultsTitleText">Recommended</span><span class="section-header__count" id="resultsCount"></span></h2>
        <button type="button" class="section-header__see-all" id="seeAllBtn" hidden>See all</button>
    </div>

    <div class="hotline-grid" id="hotlineGrid">
        <p class="loading-state">Loading hotlines&hellip;</p>
    </div>
</main>

<div class="call-modal-overlay" id="callModal" hidden>
    <div class="call-modal" role="dialog" aria-modal="true" aria-labelledby="callModalTitle">
        <button type="button" class="call-modal__close" id="callModalClose" aria-label="Close">&times;</button>
        <h2 class="call-modal__title" id="callModalTitle">Hotline</h2>
        <div class="call-modal__number" id="callModalNumber">&mdash;</div>
        <label class="call-modal__label" for="helpDescription">Optional details for responders</label>
        <textarea class="call-modal__details" id="helpDescription" maxlength="500" placeholder="Describe where help is needed"></textarea>
        <button type="button" class="call-modal__location" id="shareLocationBtn">Share my location</button>
        <p class="call-modal__status" id="locationStatus" role="status"></p>
        <a href="tel:" class="call-modal__cta" id="callModalCta">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            Call now
        </a>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
