<?php
/**
 * Shared helpers used across the public site, the JSON API, and the admin panel.
 * Require this file (after db.php) at the top of every entry-point page.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Derive the project URL from the current request so the same files work
 * directly in htdocs or inside an XAMPP subfolder.
 */
if (!defined('BASE_URL')) {
    $requestPath = $_SERVER['SCRIPT_NAME'] ?? '';
    $projectPath = str_replace('\\', '/', dirname(rawurldecode($requestPath)));
    $baseUrl = $projectPath === '/' || $projectPath === '.'
        ? ''
        : '/' . implode('/', array_filter(array_map('rawurlencode', explode('/', trim($projectPath, '/')))));
    define('BASE_URL', $baseUrl);
}

/** Escape a string for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** The logged-in user's session data, or null if nobody is logged in. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function current_user_display_name(): string
{
    $user = current_user();
    return trim((string) ($user['username'] ?? '')) ?: (string) ($user['label'] ?? 'there');
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === 'admin';
}

/** Record recent activity so administrators can see active sessions. */
function record_user_activity(): void
{
    $user = current_user();
    if ($user === null || empty($user['id'])) {
        return;
    }

    static $updated = false;
    if (!$updated) {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE users SET last_seen_at = NOW() WHERE id = ?');
        $stmt->execute([(int) $user['id']]);
        $updated = true;
    }
}

/** Send visitors who aren't logged in back to the login page. */
function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? null;
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/** Gate an admin-only page. Sends non-admins somewhere sensible instead of a blank 403. */
function require_admin(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? null;
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    if (!is_admin()) {
        flash_set('error', 'That page is for administrators only.');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/* -------------------------------------------------------------- *
 * CSRF protection — every admin form includes csrf_field(), every
 * handler calls verify_csrf($_POST['csrf_token'] ?? null).
 * -------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/* -------------------------------------------------------------- *
 * One-time flash messages (survive exactly one redirect).
 * -------------------------------------------------------------- */

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * The category table stores name="Disaster" / slug="others" for the 4th
 * bucket — the Figma prototype labels that chip "Others", so this is the
 * single place that mapping lives.
 */
function category_display_name(array $category): string
{
    return $category['slug'] === 'others' ? 'Others' : $category['name'];
}

/** Basic Philippine mobile number check: 09XXXXXXXXX or +639XXXXXXXXX. */
function is_valid_ph_mobile(string $mobile): bool
{
    return (bool) preg_match('/^(09\d{9}|\+639\d{9})$/', trim($mobile));
}

record_user_activity();
