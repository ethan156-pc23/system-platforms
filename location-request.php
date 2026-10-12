<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST requests only.']);
    exit;
}

$hotlineId = (int) ($_POST['hotline_id'] ?? 0);
$latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
$longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
$accuracy = filter_var($_POST['accuracy'] ?? null, FILTER_VALIDATE_FLOAT);
$description = trim($_POST['help_description'] ?? '');

if ($hotlineId < 1 || $latitude === false || $longitude === false
    || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please provide a valid hotline and GPS location.']);
    exit;
}

$hotlineCheck = $pdo->prepare('SELECT id FROM hotlines WHERE id = ? LIMIT 1');
$hotlineCheck->execute([$hotlineId]);
if (!$hotlineCheck->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Hotline not found.']);
    exit;
}

$user = current_user();
$stmt = $pdo->prepare(
    'INSERT INTO location_requests (user_id, hotline_id, latitude, longitude, accuracy, help_description)
     VALUES (?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $user ? (int) $user['id'] : null,
    $hotlineId,
    $latitude,
    $longitude,
    $accuracy === false ? null : $accuracy,
    $description === '' ? null : substr($description, 0, 500),
]);

echo json_encode(['success' => true, 'message' => 'Location shared with emergency administrators.']);