<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

$stmt = $pdo->query('SELECT DISTINCT municipality FROM hotlines ORDER BY municipality ASC');
$municipalities = array_column($stmt->fetchAll(), 'municipality');

echo json_encode(['success' => true, 'data' => $municipalities]);
