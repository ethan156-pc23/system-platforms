<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$_SESSION = [];
session_destroy();

session_start();
flash_set('success', 'You have been logged out.');
header('Location: ' . BASE_URL . '/index.php');
exit;
