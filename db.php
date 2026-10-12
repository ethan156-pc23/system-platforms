<?php
/**
 * Database connection
 * ---------------------------------------------------------------
 * Edit the four constants below to match your MySQL setup
 * (XAMPP/WAMP default is usually host=localhost, user=root, pass="").
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'cebu_emergency');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    // Keep existing XAMPP databases compatible with the activity view.
    $columnCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_seen_at'");
    if (!$columnCheck->fetch()) {
        $pdo->exec('ALTER TABLE users ADD COLUMN last_seen_at DATETIME NULL AFTER role');
    }

    $usernameCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'username'");
    if (!$usernameCheck->fetch()) {
        $pdo->exec('ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL UNIQUE AFTER id');
    }

    $tableCheck = $pdo->query("SHOW TABLES LIKE 'location_requests'");
    if (!$tableCheck->fetch()) {
        $pdo->exec("CREATE TABLE location_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            hotline_id INT NOT NULL,
            latitude DECIMAL(10, 7) NOT NULL,
            longitude DECIMAL(10, 7) NOT NULL,
            accuracy DECIMAL(10, 2) NULL,
            help_description VARCHAR(500) NULL,
            status ENUM('pending', 'responding', 'resolved') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (hotline_id) REFERENCES hotlines(id) ON DELETE CASCADE,
            INDEX idx_location_requests_created_at (created_at)
        )");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS favorite_municipalities (
        user_id INT NOT NULL,
        municipality VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, municipality),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
    http_response_code(500);
    // Don't leak connection details to visitors — log them instead in a real deployment.
    die('Database connection failed. Please check the settings in db.php and make sure the "cebu_emergency" database has been imported.');
}
