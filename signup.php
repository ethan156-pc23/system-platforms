<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/sms.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . (is_admin() ? '/dashboard.php' : '/index.php'));
    exit;
}

$method = ($_POST['method'] ?? $_GET['method'] ?? 'mobile') === 'email' ? 'email' : 'mobile';
$errors = [];
$identifier = trim($_POST['identifier'] ?? '');
$pendingOtp = $_SESSION['signup_otp'] ?? null;
$username = trim($_POST['username'] ?? ($pendingOtp['username'] ?? ''));
if ($method === 'mobile') {
    $identifier = normalize_ph_mobile($identifier) ?? $identifier;
}
$otpNotice = $_SESSION['signup_otp_notice'] ?? null;
unset($_SESSION['signup_otp_notice']);

// After redirect (GET), restore the pending identifier from the session
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['verify'])
    && $pendingOtp && $pendingOtp['expires_at'] >= time()) {
    $method     = $pendingOtp['method'];
    $identifier = $pendingOtp['identifier'];
    $username   = $pendingOtp['username'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'send_otp';
    $column = $method === 'mobile' ? 'mobile_number' : 'email';

    if ($action === 'verify_otp') {
        $otp = trim($_POST['otp'] ?? '');
        $pending = $_SESSION['signup_otp'] ?? null;

        if (!$pending || $pending['method'] !== $method || $pending['identifier'] !== $identifier) {
            $errors[] = 'Your verification code expired. Request a new code.';
        } elseif (empty($pending['username'])) {
            unset($_SESSION['signup_otp']);
            $errors[] = 'Your signup details expired. Please start signup again.';
        } elseif ($pending['expires_at'] < time()) {
            unset($_SESSION['signup_otp']);
            $errors[] = 'Your verification code expired. Request a new code.';
        } elseif (!preg_match('/^\d{6}$/', $otp) || !password_verify($otp, $pending['hash'])) {
            $_SESSION['signup_otp']['attempts'] = ($pending['attempts'] ?? 0) + 1;
            if ($_SESSION['signup_otp']['attempts'] >= 5) {
                unset($_SESSION['signup_otp']);
                $errors[] = 'Too many wrong attempts. Request a new code.';
            } else {
                $errors[] = 'The verification code is incorrect.';
            }
        } else {
            $check = $pdo->prepare("SELECT id FROM users WHERE $column = ? LIMIT 1");
            $check->execute([$identifier]);
            $usernameCheck = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $usernameCheck->execute([$pending['username']]);

            if ($usernameCheck->fetch()) {
                unset($_SESSION['signup_otp']);
                $errors[] = 'That username was just taken. Choose another username to continue.';
            } elseif ($check->fetch()) {
                $errors[] = $method === 'mobile'
                    ? 'That mobile number already has an account. Try logging in instead.'
                    : 'That email already has an account. Try logging in instead.';
            } else {
                $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
                $username = $pending['username'];
                $insert = $pdo->prepare("INSERT INTO users (username, $column, password, role) VALUES (?, ?, ?, 'user')");
                $insert->execute([$username, $identifier, $randomPassword]);
                unset($_SESSION['signup_otp']);

                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'    => (int) $pdo->lastInsertId(),
                    'role'  => 'user',
                    'username' => $username,
                    'label' => $identifier,
                ];
                flash_set('success', 'Your account has been verified and created.');
                header('Location: ' . BASE_URL . '/index.php');
                exit;
            }
        }
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
        $errors[] = 'Choose a username with 3–30 letters, numbers, dots, dashes, or underscores.';
    } elseif ($identifier === '') {
        $errors[] = 'Please enter your ' . ($method === 'mobile' ? 'mobile number' : 'email address') . '.';
    } elseif ($method === 'mobile' && !is_valid_ph_mobile($identifier)) {
        $errors[] = 'Enter a valid mobile number, e.g. 09171234567.';
    } elseif ($method === 'email' && !filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    } else {
        $usernameCheck = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $usernameCheck->execute([$username]);
        if ($usernameCheck->fetch()) {
            $errors[] = 'That username is already taken. Please choose another.';
        } else {
            $check = $pdo->prepare("SELECT id FROM users WHERE $column = ? LIMIT 1");
            $check->execute([$identifier]);

            if ($check->fetch()) {
                $errors[] = $method === 'mobile'
                    ? 'That mobile number already has an account. Try logging in instead.'
                    : 'That email already has an account. Try logging in instead.';
            } else {
                $prev = $_SESSION['signup_otp'] ?? null;
                if ($prev && ($prev['identifier'] ?? '') === $identifier && time() - ($prev['sent_at'] ?? 0) < 60) {
                    $errors[] = 'Please wait a minute before requesting another code.';
                } else {
                    $otp = (string) random_int(100000, 999999);
                    $_SESSION['signup_otp'] = [
                        'method'     => $method,
                        'identifier' => $identifier,
                        'username'   => $username,
                        'hash'       => password_hash($otp, PASSWORD_DEFAULT),
                        'expires_at' => time() + 600,
                        'attempts'   => 0,
                        'sent_at'    => time(),
                    ];

                    $sent = true;
                    if ($method === 'email') {
                        @mail($identifier, 'Cebu Emergency verification code', "Your verification code is $otp. It expires in 10 minutes.");
                    } elseif (SMS_API_KEY !== '') {
                        $sent = send_sms_otp($identifier, $otp);
                    }

                    if (!$sent) {
                        unset($_SESSION['signup_otp']);
                        $errors[] = 'We could not send the SMS right now. Please try again later.';
                    } else {
                        $_SESSION['signup_otp_notice'] = "Verification code sent to your $method." . (SHOW_TEST_CODE ? " Local testing code: $otp" : '');
                        header('Location: ' . BASE_URL . '/signup.php?method=' . $method . '&verify=1');
                        exit;
                    }
                }
            }
        }
    }
}

$verificationPending = !empty($_SESSION['signup_otp'])
    && ($_SESSION['signup_otp']['method'] ?? '') === $method
    && ($_SESSION['signup_otp']['identifier'] ?? '') === $identifier;

$pageTitle = 'Sign up — Cebu Emergency Hotlines';
$hideNav = true;
include __DIR__ . '/header.php';
?>
<main class="auth-page">
    <div class="auth-shell">
        <div class="auth-avatar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="44" height="44"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-6 8-6s8 2 8 6"/></svg>
        </div>
        <h1 class="auth-title">Sign up</h1>

        <?php if ($errors): ?>
            <div class="alert alert--error"><?= e($errors[0]) ?></div>
        <?php endif; ?>
        <?php if ($otpNotice): ?>
            <div class="alert alert--success"><?= e($otpNotice) ?></div>
        <?php endif; ?>

        <div class="auth-card">
            <?php if (!$verificationPending): ?>
            <div class="auth-tabs">
                <a class="auth-tab<?= $method === 'mobile' ? ' is-active' : '' ?>" href="<?= BASE_URL ?>/signup.php?method=mobile">Mobile Number</a>
                <a class="auth-tab<?= $method === 'email' ? ' is-active' : '' ?>" href="<?= BASE_URL ?>/signup.php?method=email">Email</a>
            </div>
            <?php endif; ?>

            <?php if ($verificationPending): ?>
                <!-- STEP 2: paste the code -->
                <p class="field-hint">Enter the 6-digit code sent to <strong><?= e($identifier) ?></strong>.</p>
                <form method="post" action="<?= BASE_URL ?>/signup.php" novalidate>
                    <input type="hidden" name="method" value="<?= e($method) ?>">
                    <input type="hidden" name="identifier" value="<?= e($identifier) ?>">
                    <input type="hidden" name="username" value="<?= e($username) ?>">
                    <input type="hidden" name="action" value="verify_otp">
                    <div class="field">
                        <label for="otp">Verification code</label>
                        <input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="Paste 6-digit code" required autofocus>
                        <p class="field-hint">The code expires in 10 minutes.</p>
                    </div>
                    <button type="submit" class="btn-primary">Verify and create account</button>
                </form>
                <form method="post" action="<?= BASE_URL ?>/signup.php" style="margin-top:12px;">
                    <input type="hidden" name="method" value="<?= e($method) ?>">
                    <input type="hidden" name="identifier" value="<?= e($identifier) ?>">
                    <input type="hidden" name="username" value="<?= e($username) ?>">
                    <input type="hidden" name="action" value="send_otp">
                    <button type="submit" class="text-link" style="background:none;border:0;cursor:pointer;">Resend code</button>
                    &middot; <a class="text-link" href="<?= BASE_URL ?>/signup.php?method=<?= e($method) ?>">Change <?= $method === 'mobile' ? 'number' : 'email' ?></a>
                </form>
            <?php else: ?>
                <!-- STEP 1: enter mobile/email -->
                <form method="post" action="<?= BASE_URL ?>/signup.php" novalidate>
                    <input type="hidden" name="method" value="<?= e($method) ?>">
                    <input type="hidden" name="action" value="send_otp">
                    <div class="field">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" minlength="3" maxlength="30" pattern="[A-Za-z0-9_.-]{3,30}"
                               placeholder="e.g. Ethanne" value="<?= e($username) ?>" autocomplete="username" required autofocus>
                        <p class="field-hint">3–30 letters, numbers, dots, dashes, or underscores.</p>
                    </div>
                    <div class="field">
                        <label for="identifier"><?= $method === 'mobile' ? 'Mobile Number' : 'Email' ?></label>
                        <input type="<?= $method === 'mobile' ? 'text' : 'email' ?>" id="identifier" name="identifier"
                               placeholder="<?= $method === 'mobile' ? 'Enter mobile number' : 'Enter email address' ?>"
                               value="<?= e($identifier) ?>" required>
                        <?php if ($method === 'mobile'): ?>
                            <p class="field-hint">Format: 09171234567</p>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn-primary">Send verification code</button>
                </form>
            <?php endif; ?>

            <p class="auth-signup-prompt">Already have an account? <a href="<?= BASE_URL ?>/login.php">Log in</a></p>
        </div>
        <p class="field-hint" style="text-align:center; margin-top:16px;">
            A one-time verification code is required. SMS delivery needs an SMS provider; local XAMPP testing displays the code on this page.
        </p>
    </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>