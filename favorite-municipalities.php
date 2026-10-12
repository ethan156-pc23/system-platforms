<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

function favorite_municipalities_response(array $favorites): void
{
    echo json_encode(['success' => true, 'data' => $favorites]);
}

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to manage priority cities/municipalities.']);
    exit;
}

$userId = (int) current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare('SELECT municipality FROM favorite_municipalities WHERE user_id = ? ORDER BY created_at ASC, municipality ASC');
    $stmt->execute([$userId]);
    favorite_municipalities_response(array_column($stmt->fetchAll(), 'municipality'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    echo json_encode(['success' => false, 'message' => 'GET or POST requests only.']);
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? null;
if (!is_string($csrfToken) || !verify_csrf($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Your session expired. Refresh the page and try again.']);
    exit;
}

$action = $_POST['action'] ?? null;
$municipality = $_POST['municipality'] ?? null;
if (!is_string($action) || !in_array($action, ['add', 'remove'], true)
    || !is_string($municipality) || ($municipality = trim($municipality)) === '' || strlen($municipality) > 100) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Choose a valid city/municipality and action.']);
    exit;
}

if ($action === 'add') {
    $check = $pdo->prepare('SELECT 1 FROM hotlines WHERE municipality = ? LIMIT 1');
    $check->execute([$municipality]);
    if (!$check->fetchColumn()) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'That city/municipality is not available in the hotline directory.']);
        exit;
    }

    $stmt = $pdo->prepare('INSERT IGNORE INTO favorite_municipalities (user_id, municipality) VALUES (?, ?)');
    $stmt->execute([$userId, $municipality]);
} else {
    $stmt = $pdo->prepare('DELETE FROM favorite_municipalities WHERE user_id = ? AND municipality = ?');
    $stmt->execute([$userId, $municipality]);
}

$stmt = $pdo->prepare('SELECT municipality FROM favorite_municipalities WHERE user_id = ? ORDER BY created_at ASC, municipality ASC');
$stmt->execute([$userId]);
favorite_municipalities_response(array_column($stmt->fetchAll(), 'municipality'));
