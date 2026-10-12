<?php
/**
 * Shared public-site header.
 * Expects (optionally) $pageTitle to be set before including this file.
 */
$pageTitle = $pageTitle ?? 'Cebu Emergency Hotlines';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
<script>window.APP_BASE_URL = "<?= BASE_URL ?>";</script>
</head>
<body>
<?php if (empty($hideNav)): ?>
<header class="site-header">
    <a href="<?= BASE_URL ?>/index.php" class="site-header__brand" aria-label="Cebu Emergency Hotlines home">
        <span class="site-header__logos">
            <img src="<?= BASE_URL ?>/assets/cebu-province-seal.webp" alt="Province of Cebu Official Seal">
            <img src="<?= BASE_URL ?>/assets/cordova-public-college.png" alt="Cordova Public College seal">
        </span>
        <span class="site-header__brand-name">EMERGENCY</span>
    </a>
    <nav class="site-header__nav">
        <a href="<?= BASE_URL ?>/about.php" class="site-header__link">About</a>
        <?php if ($user): ?>
            <a href="<?= BASE_URL ?>/profile.php" class="site-header__link">My profile</a>
            <a href="<?= BASE_URL ?>/logout.php" class="site-header__profile-btn" title="Log out (<?= e($user['label']) ?>)" aria-label="Log out">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php" class="site-header__profile-btn" title="Log in" aria-label="Log in">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </a>
        <?php endif; ?>
    </nav>
</header>
<?php else: ?>
<header class="site-header site-header--auth">
    <a href="<?= BASE_URL ?>/index.php" class="site-header__brand" aria-label="Cebu Emergency Hotlines home">
        <span class="site-header__logos">
            <img src="<?= BASE_URL ?>/assets/cebu-province-seal.webp" alt="Province of Cebu Official Seal">
            <img src="<?= BASE_URL ?>/assets/cordova-public-college.png" alt="Cordova Public College seal">
        </span>
        <span class="site-header__brand-name">EMERGENCY</span>
    </a>
</header>
<?php endif; ?>
