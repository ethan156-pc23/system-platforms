<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_login();

$user = current_user();
$errors = [];
$username = trim((string) ($user['username'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errors[] = 'Your form has expired. Refresh the page and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
            $errors[] = 'Choose a username with 3–30 letters, numbers, dots, dashes, or underscores.';
        } else {
            $check = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
            $check->execute([$username, (int) $user['id']]);
            if ($check->fetch()) {
                $errors[] = 'That username is already taken. Please choose another.';
            } else {
                $update = $pdo->prepare('UPDATE users SET username = ? WHERE id = ?');
                $update->execute([$username, (int) $user['id']]);
                $_SESSION['user']['username'] = $username;
                flash_set('success', 'Your username has been updated.');
                header('Location: ' . BASE_URL . '/profile.php');
                exit;
            }
        }
    }
}

$flash = flash_get();
$pageTitle = 'My profile — Cebu Emergency Hotlines';
include __DIR__ . '/header.php';
?>
<main class="page-content">
    <section class="auth-shell">
        <h1 class="auth-title">My profile</h1>
        <?php if ($flash): ?>
            <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert alert--error"><?= e($errors[0]) ?></div>
        <?php endif; ?>
        <div class="auth-card">
            <form method="post" action="<?= BASE_URL ?>/profile.php">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" minlength="3" maxlength="30"
                           pattern="[A-Za-z0-9_.-]{3,30}" value="<?= e($username) ?>"
                           autocomplete="username" required>
                    <p class="field-hint">3–30 letters, numbers, dots, dashes, or underscores.</p>
                </div>
                <button type="submit" class="btn-primary">Save username</button>
            </form>
        </div>
    </section>
</main>
<?php include __DIR__ . '/footer.php'; ?>
