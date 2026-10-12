<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

$stmt = $pdo->query(
    'SELECT c.id, c.name, c.slug, COUNT(h.id) AS hotline_count
     FROM categories c
     LEFT JOIN hotlines h ON h.category_id = c.id
     GROUP BY c.id, c.name, c.slug
     ORDER BY c.id ASC'
);
$categories = $stmt->fetchAll();

foreach ($categories as &$category) {
    $category['hotline_count'] = (int) $category['hotline_count'];
    $category['display_name']  = category_display_name($category);
}
unset($category);

echo json_encode(['success' => true, 'data' => $categories]);
