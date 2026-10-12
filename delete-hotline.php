<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Invalid request.');
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare('DELETE FROM hotlines WHERE id = ?');
    $stmt->execute([$id]);
    flash_set('success', 'Hotline deleted.');
}

header('Location: ' . BASE_URL . '/dashboard.php');
exit;
