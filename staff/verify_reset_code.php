<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

if (!empty($_SESSION['staff_id'])) {
    redirect('/fgck_joyland/staff/dashboard.php');
}

$userId = (int)($_SESSION['password_reset_user_id'] ?? 0);
$method = $_SESSION['password_reset_method'] ?? '';
$identifier = $_SESSION['password_reset_identifier'] ?? '';

if (!$userId || !in_array($method, ['email', 'phone'], true) || $identifier === '') {
    redirect('/fgck_joyland/staff/forgot_password.php');
}

$error = '';
$sentAt = (int)($_SESSION['password_reset_sent_at'] ?? 0);
$resendAfter = max(0, 60 - (time() - $sentAt));

$stmt = $pdo->prepare("
    SELECT id, email, phone, reset_code_hash, reset_code_expires_at, reset_attempts
    FROM users
    WHERE id = ? AND status = 'active'
    LIMIT 1
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    unset(
        $_SESSION['password_reset_user_id'],
        $_SESSION['password_reset_method'],
        $_SESSION['password_reset_identifier'],
        $_SESSION['password_reset_sent_at']
    );
    redirect('/fgck_joyland/staff/forgot_password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $code = trim((string)($_POST['code'] ?? ''));

    if (!preg_match('/^\d{6}$/', $code)) {
        $error = 'Please enter the 6-digit verification code.';
    } elseif ((int)$user['reset_attempts'] >= 5) {
        $error = 'Too many incorrect attempts. Please request a new verification code.';
    } elseif (
        empty($user['reset_code_hash']) ||
        empty($user['reset_code_expires_at']) ||
        strtotime($user['reset_code_expires_at']) < time()
    ) {
        $error = 'This verification code has expired. Please request a new code.';
    } elseif (!password_verify($code, $user['reset_code_hash'])) {
        $attempts = (int)$user['reset_attempts'] + 1;

        $pdo->prepare("
            UPDATE users
            SET reset_attempts = ?
            WHERE id = ?
        ")->execute([$attempts, $userId]);

        $remaining = max(0, 5 - $attempts);

        $error = $remaining > 0
            ? 'The verification code is incorrect. You have ' . $remaining . ' attempt(s) remaining.'
            : 'Too many incorrect attempts. Please request a new verification code.';
    } else {
        $_SESSION['password_reset_verified'] = true;
        $_SESSION['password_reset_user_id'] = $userId;

        redirect('/fgck_joyland/staff/reset_password.php');
    }
}

$maskedDestination = $identifier;

if ($method === 'email') {
    $parts = explode('@', $identifier, 2);
    if (count($parts) === 2) {
        $local = $parts[0];
        $maskedDestination =
            (strlen($local) > 2 ? substr($local, 0, 2) : substr($local, 0, 1))
            . str_repeat('*', max(2, strlen($local) - 2))
            . '@' . $parts[1];
    }
} else {
    $normalized = normalize_kenyan_phone($identifier);
    if ($normalized) {
        $maskedDestination =
            substr($normalized, 0, 5) .
            str_repeat('*', 5) .
            substr($normalized, -2);
    }
}

$page_title = 'Verify Reset Code';
require __DIR__ . '/../includes/header.php';
?>

<style>
.verify-page{
    min-height:calc(100vh - 70px);display:flex;align-items:center;
    justify-content:center;padding:30px 15px;
    background:radial-gradient(circle at 10% 10%,rgba(8,116,67,.12),transparent 30%),
    radial-gradient(circle at 90% 90%,rgba(217,164,65,.12),transparent 30%),
    linear-gradient(135deg,#f8fbf9,#eef5f1);
}
.verify-card{width:100%;max-width:500px;background:#fff;padding:45px;border-radius:26px;
    box-shadow:0 25px 70px rgba(16,57,38,.14)}
.verify-icon{width:80px;height:80px;display:flex;align-items:center;justify-content:center;
    margin:0 auto 25px;border-radius:50%;background:#edf8f2;color:#087443;font-size:32px}
.verify-heading{text-align:center;margin-bottom:28px}
.verify-heading h1{font-size:28px;font-weight:800;color:#17221c}
.verify-heading p{color:#718078;font-size:14px;line-height:1.7}
.destination{background:#f5f8f6;border-radius:12px;padding:12px;text-align:center;color:#087443;font-weight:700;font-size:13px;margin-bottom:25px}
.code-input{height:65px;text-align:center;font-size:28px;font-weight:800;letter-spacing:10px;border-radius:14px;background:#f9fbfa;border:1px solid #e5ebe7}
.code-input:focus{border-color:#087443;box-shadow:0 0 0 4px rgba(8,116,67,.09)}
.verify-button{height:54px;border:0;border-radius:13px;background:linear-gradient(135deg,#087443,#0b8f55);color:#fff;font-weight:800;margin-top:20px}
@media(max-width:500px){.verify-card{padding:30px 22px}.code-input{font-size:22px;letter-spacing:7px}}
</style>

<div class="verify-page">
    <div class="verify-card">
        <div class="verify-icon">
            <i class="bi <?= $method === 'phone' ? 'bi-phone-fill' : 'bi-envelope-check-fill' ?>"></i>
        </div>

        <div class="verify-heading">
            <h1>Enter Verification Code</h1>
            <p>
                We sent a 6-digit code to your
                <?= $method === 'phone' ? 'phone number' : 'email address' ?>.
            </p>
        </div>

        <div class="destination">
            <i class="bi <?= $method === 'phone' ? 'bi-phone me-1' : 'bi-envelope me-1' ?>"></i>
            <?= e($maskedDestination) ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

            <input
                type="text"
                name="code"
                class="form-control code-input"
                placeholder="000000"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                pattern="[0-9]{6}"
                required
                autofocus
            >

            <button type="submit" class="btn verify-button w-100">
                <i class="bi bi-shield-check me-2"></i>
                Verify Code
            </button>
        </form>

        <div class="text-center mt-3 small text-muted">
            <?php if ($resendAfter > 0): ?>
                Resend available in <strong><?= $resendAfter ?></strong> seconds.
            <?php else: ?>
                <a href="/fgck_joyland/staff/forgot_password.php" class="fw-bold" style="color:#087443">
                    Request a new code
                </a>
            <?php endif; ?>
        </div>

        <div class="text-center mt-3">
            <a href="/fgck_joyland/staff/login.php" class="small text-decoration-none" style="color:#087443">
                <i class="bi bi-arrow-left me-1"></i> Back to Login
            </a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
