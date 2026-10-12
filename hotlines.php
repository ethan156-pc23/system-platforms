<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

$conditions = [];
$params     = [];

if (!empty($_GET['category'])) {
    $conditions[] = 'c.slug = ?';
    $params[]     = $_GET['category'];
}
if (!empty($_GET['municipality'])) {
    $conditions[] = 'h.municipality = ?';
    $params[]     = $_GET['municipality'];
}
if (!empty($_GET['q'])) {
    $conditions[] = '(h.title LIKE ? OR h.municipality LIKE ? OR h.description LIKE ?)';
    $like         = '%' . $_GET['q'] . '%';
    array_push($params, $like, $like, $like);
}

$sql = 'SELECT h.id, h.title, h.municipality, h.description, h.phone_number, h.availability, h.tag_label,
               c.id AS category_id, c.name AS category_name, c.slug AS category_slug
        FROM hotlines h
        JOIN categories c ON c.id = h.category_id';

if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY h.municipality ASC, h.title ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hotlines = $stmt->fetchAll();

foreach ($hotlines as &$hotline) {
    $hotline['category_display_name'] = category_display_name([
        'name' => $hotline['category_name'],
        'slug' => $hotline['category_slug'],
    ]);
}
unset($hotline);

echo json_encode(['success' => true, 'data' => $hotlines, 'count' => count($hotlines)]);
