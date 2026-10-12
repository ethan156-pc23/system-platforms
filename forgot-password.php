<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . (is_admin() ? '/dashboard.php' : '/index.php'));
    exit;
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile      = trim($_POST['mobile_number'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';

    if ($mobile === '' || $newPassword === '') {
        $errors[] = 'Please fill in both fields.';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'Your new password should be at least 8 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE mobile_number = ? LIMIT 1');
        $stmt->execute([$mobile]);
        $userRow = $stmt->fetch();

        if (!$userRow) {
            $errors[] = 'No account found with that mobile number.';
        } else {
            $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $update->execute([password_hash($newPassword, PASSWORD_BCRYPT), $userRow['id']]);
            $success = true;
        }
    }
}

$pageTitle = 'Forgot password — Cebu Emergency Hotlines';
$hideNav = true;
include __DIR__ . '/header.php';
?>
<main class="auth-page">
    <div class="auth-shell">
        <?php if ($success): ?>
            <div class="auth-card auth-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="56" height="56"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                <h1 class="auth-title" style="font-size:22px;">Password updated</h1>
                <p style="text-align:center; color:var(--color-body); margin-bottom:20px;">You can now log in with your new password.</p>
                <a href="<?= BASE_URL ?>/login.php" class="btn-primary" style="text-decoration:none;">Back to log in</a>
            </div>
        <?php else: ?>
            <div class="auth-avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="44" height="44"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <h1 class="auth-title" style="font-size:28px;">Reset password</h1>

            <?php if ($errors): ?>
                <div class="alert alert--error"><?= e($errors[0]) ?></div>
            <?php endif; ?>

            <div class="auth-card">
                <form method="post" action="<?= BASE_URL ?>/forgot-password.php" novalidate>
                    <div class="field">
                        <label for="mobile_number">Mobile Number</label>
                        <input type="text" id="mobile_number" name="mobile_number" placeholder="Enter mobile number"
                               value="<?= e($_POST['mobile_number'] ?? '') ?>" inputmode="tel" autofocus required>
                    </div>
                    <div class="field">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                    </div>
                    <button type="submit" class="btn-primary">Reset Password</button>
                </form>
                <a href="<?= BASE_URL ?>/login.php" class="text-link">Back to log in</a>
            </div>
            <p class="field-hint" style="text-align:center; margin-top:16px;">
                Note: for a production deployment you&rsquo;d want to verify the mobile number
                (e.g. an SMS code) before allowing a reset here &mdash; this build matches the
                prototype's two-field form as designed.
            </p>
        <?php endif; ?>
    </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
