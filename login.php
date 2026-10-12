<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/sms.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . (is_admin() ? '/dashboard.php' : '/index.php'));
    exit;
}

$errors = [];
$identifier = trim($_POST['identifier'] ?? $_POST['mobile_number'] ?? '');
$method = ($_POST['method'] ?? $_GET['method'] ?? 'mobile') === 'email' ? 'email' : 'mobile';
if ($method === 'mobile') {
    $identifier = normalize_ph_mobile($identifier) ?? $identifier;
}
$otpNotice = $_SESSION['login_otp_notice'] ?? null;
unset($_SESSION['login_otp_notice']);

// After redirect (GET), restore the pending identifier from the session
$pendingOtp = $_SESSION['login_otp'] ?? null;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['verify'])
    && $pendingOtp && $pendingOtp['expires_at'] >= time()) {
    $method     = $pendingOtp['method'];
    $identifier = $pendingOtp['identifier'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? 'password_login';
    $mobile   = $identifier;
    $password = $_POST['password'] ?? '';
    $column   = $method === 'mobile' ? 'mobile_number' : 'email';

    if ($action === 'verify_otp') {
        $otp = trim($_POST['otp'] ?? '');
        $pending = $_SESSION['login_otp'] ?? null;
        if (!$pending || $pending['method'] !== $method || $pending['identifier'] !== $identifier || $pending['expires_at'] < time()) {
            unset($_SESSION['login_otp']);
            $errors[] = 'Your verification code expired. Request a new code.';
        } elseif (!preg_match('/^\d{6}$/', $otp) || !password_verify($otp, $pending['hash'])) {
            $_SESSION['login_otp']['attempts'] = ($pending['attempts'] ?? 0) + 1;
            if ($_SESSION['login_otp']['attempts'] >= 5) {
                unset($_SESSION['login_otp']);
                $errors[] = 'Too many wrong attempts. Request a new code.';
            } else {
                $errors[] = 'The verification code is incorrect.';
            }
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE $column = ? LIMIT 1");
            $stmt->execute([$identifier]);
            $userRow = $stmt->fetch();
            unset($_SESSION['login_otp']);
            if ($userRow) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'    => $userRow['id'],
                    'role'  => $userRow['role'],
                    'username' => $userRow['username'] ?? null,
                    'label' => $userRow['mobile_number'] ?: $userRow['email'],
                ];
                header('Location: ' . BASE_URL . ($userRow['role'] === 'admin' ? '/dashboard.php' : '/index.php'));
                exit;
            }
            $errors[] = 'That account no longer exists.';
        }
    } elseif ($action === 'send_otp') {
        if ($identifier === '') {
            $errors[] = 'Please enter your mobile number or email address.';
        } elseif ($method === 'mobile' && !is_valid_ph_mobile($identifier)) {
            $errors[] = 'Enter a valid mobile number, e.g. 09171234567.';
        } elseif ($method === 'email' && !filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE $column = ? LIMIT 1");
            $stmt->execute([$identifier]);
            if (!$stmt->fetch()) {
                $errors[] = 'No account was found for that ' . ($method === 'mobile' ? 'mobile number.' : 'email address.');
            } else {
                $prev = $_SESSION['login_otp'] ?? null;
                if ($prev && ($prev['identifier'] ?? '') === $identifier && time() - ($prev['sent_at'] ?? 0) < 60) {
                    $errors[] = 'Please wait a minute before requesting another code.';
                } else {
                    $otp = (string) random_int(100000, 999999);
                    $_SESSION['login_otp'] = [
                        'method'     => $method,
                        'identifier' => $identifier,
                        'hash'       => password_hash($otp, PASSWORD_DEFAULT),
                        'expires_at' => time() + 600,
                        'attempts'   => 0,
                        'sent_at'    => time(),
                    ];

                    $sent = true;
                    if ($method === 'email') {
                        @mail($identifier, 'Cebu Emergency login code', "Your login code is $otp. It expires in 10 minutes.");
                    } elseif (SMS_API_KEY !== '') {
                        $sent = send_sms_otp($identifier, $otp);
                    }

                    if (!$sent) {
                        unset($_SESSION['login_otp']);
                        $errors[] = 'We could not send the SMS right now. Please try again later.';
                    } else {
                        $_SESSION['login_otp_notice'] = 'Verification code sent.' . (SHOW_TEST_CODE ? " Local testing code: $otp" : '');
                        header('Location: ' . BASE_URL . '/login.php?method=' . $method . '&verify=1');
                        exit;
                    }
                }
            }
        }
    } elseif ($mobile === '' || $password === '') {
        $errors[] = 'Please enter your mobile number and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE mobile_number = ? LIMIT 1');
        $stmt->execute([$mobile]);
        $userRow = $stmt->fetch();

        if ($userRow && password_verify($password, $userRow['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'    => $userRow['id'],
                'role'  => $userRow['role'],
                'username' => $userRow['username'] ?? null,
                'label' => $userRow['mobile_number'] ?: $userRow['email'],
            ];

            $redirect = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);

            if ($redirect) {
                header('Location: ' . $redirect);
            } elseif ($userRow['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . '/index.php');
            }
            exit;
        }

        $errors[] = 'Incorrect mobile number or password.';
    }
}

$pageTitle = 'Log in — Cebu Emergency Hotlines';
$hideNav = true;
$verificationPending = !empty($_SESSION['login_otp'])
    && ($_SESSION['login_otp']['method'] ?? '') === $method
    && ($_SESSION['login_otp']['identifier'] ?? '') === $identifier;
include __DIR__ . '/header.php';
?>
<main class="auth-page">
    <div class="auth-shell">
        <div class="auth-avatar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="44" height="44"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-6 8-6s8 2 8 6"/></svg>
        </div>
        <h1 class="auth-title">Log in</h1>

        <?php if ($errors): ?>
            <div class="alert alert--error"><?= e($errors[0]) ?></div>
        <?php endif; ?>
        <?php if ($otpNotice): ?>
            <div class="alert alert--success"><?= e($otpNotice) ?></div>
        <?php endif; ?>

        <div class="auth-card">
            <?php if (!$verificationPending): ?>
            <div class="auth-tabs">
                <a class="auth-tab<?= $method === 'mobile' ? ' is-active' : '' ?>" href="<?= BASE_URL ?>/login.php?method=mobile">Mobile Number</a>
                <a class="auth-tab<?= $method === 'email' ? ' is-active' : '' ?>" href="<?= BASE_URL ?>/login.php?method=email">Email</a>
            </div>
            <?php endif; ?>

            <?php if ($verificationPending): ?>
                <!-- STEP 2: paste the code -->
                <p class="field-hint">Enter the 6-digit code sent to <strong><?= e($identifier) ?></strong>.</p>
                <form method="post" action="<?= BASE_URL ?>/login.php" novalidate>
                    <input type="hidden" name="action" value="verify_otp">
                    <input type="hidden" name="method" value="<?= e($method) ?>">
                    <input type="hidden" name="identifier" value="<?= e($identifier) ?>">
                    <div class="field">
                        <label for="otp">Verification code</label>
                        <input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="Paste 6-digit code" required autofocus>
                    </div>
                    <button type="submit" class="btn-primary">Verify and log in</button>
                </form>
                <form method="post" action="<?= BASE_URL ?>/login.php" style="margin-top:12px;">
                    <input type="hidden" name="method" value="<?= e($method) ?>">
                    <input type="hidden" name="identifier" value="<?= e($identifier) ?>">
                    <input type="hidden" name="action" value="send_otp">
                    <button type="submit" class="text-link" style="background:none;border:0;cursor:pointer;">Resend code</button>
                    &middot; <a class="text-link" href="<?= BASE_URL ?>/login.php?method=<?= e($method) ?>">Change <?= $method === 'mobile' ? 'number' : 'email' ?></a>
                </form>
            <?php else: ?>
                <!-- STEP 1: enter mobile/email -->
                <form method="post" action="<?= BASE_URL ?>/login.php" novalidate>
                    <input type="hidden" name="method" value="<?= e($method) ?>">
                    <div class="field">
                        <label for="identifier"><?= $method === 'mobile' ? 'Mobile Number' : 'Email' ?></label>
                        <input type="<?= $method === 'mobile' ? 'text' : 'email' ?>" id="identifier" name="identifier" placeholder="<?= $method === 'mobile' ? 'Enter mobile number' : 'Enter email address' ?>" value="<?= e($identifier) ?>" required autofocus>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="For password-based accounts">
                    </div>
                    <button type="submit" name="action" value="password_login" class="btn-primary">Log in with password</button>
                    <button type="submit" name="action" value="send_otp" class="btn-primary">Send login code</button>
                    <a href="<?= BASE_URL ?>/forgot-password.php" class="text-link">Forgot password?</a>
                </form>
            <?php endif; ?>

            <p class="auth-signup-prompt">Don&rsquo;t have an account? <a href="<?= BASE_URL ?>/signup.php">Sign Up</a></p>

            <div class="auth-divider">OR</div>
            <p class="auth-alt-label">Sign Up with</p>
            <div class="auth-alt-methods">
                <a href="<?= BASE_URL ?>/signup.php?method=email" aria-label="Sign up with email">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
                </a>
                <a href="<?= BASE_URL ?>/signup.php?method=mobile" aria-label="Sign up with mobile number">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </a>
            </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>