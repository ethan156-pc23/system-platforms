<?php
/**
 * Shared admin header. Call require_admin() BEFORE including this file.
 * Expects (optionally) $pageTitle to be set.
 */
$pageTitle = $pageTitle ?? 'Admin — Cebu Emergency Hotlines';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/admin.css?v=<?= filemtime(__DIR__ . '/admin.css') ?>">
<?php if (!empty($includeMap)): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>
<script>window.APP_BASE_URL = "<?= BASE_URL ?>";</script>
</head>
<body class="admin-body">
<header class="admin-topbar">
    <a href="<?= BASE_URL ?>/dashboard.php" class="admin-topbar__brand">
        <span class="admin-topbar__logos">
            <img src="<?= BASE_URL ?>/assets/cebu-province-seal.webp" alt="Province of Cebu Official Seal">
            <img src="<?= BASE_URL ?>/assets/cordova-public-college.png" alt="Cordova Public College seal">
        </span>
        <span>EMERGENCY <span>Admin</span></span>
    </a>
    <nav class="admin-topbar__nav">
        <a href="<?= BASE_URL ?>/index.php" target="_blank" rel="noopener">View public site</a>
        <a href="<?= BASE_URL ?>/users.php">Users</a>
        <a href="<?= BASE_URL ?>/locations.php">Locations</a>
        <a href="<?= BASE_URL ?>/profile.php" class="admin-topbar__user"><?= e(current_user_display_name()) ?></a>
        <a href="<?= BASE_URL ?>/logout.php" class="admin-topbar__logout">Log out</a>
    </nav>
</header>
<main class="admin-content">
