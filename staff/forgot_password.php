<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/email.php';
require_once __DIR__ . '/../includes/sms.php';

if (!empty($_SESSION['staff_id'])) {
    redirect('/fgck_joyland/staff/dashboard.php');
}

$error = '';
$method = $_POST['method'] ?? ($_SESSION['password_reset_method'] ?? 'email');
$identifier = trim((string)($_POST['identifier'] ?? ''));

if (!in_array($method, ['email', 'phone'], true)) {
    $method = 'email';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if ($identifier === '') {
        $error = $method === 'email'
            ? 'Please enter your email address.'
            : 'Please enter your phone number.';
    } else {
        $lastSent = (int)($_SESSION['password_reset_sent_at'] ?? 0);
        $elapsed = time() - $lastSent;

        if ($elapsed < 60) {
            $error = 'Please wait ' . (60 - $elapsed) .
                ' seconds before requesting another verification code.';
        } else {
            $user = false;

            if ($method === 'email') {
                $email = strtolower($identifier);

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address.';
                } else {
                    $stmt = $pdo->prepare("
                        SELECT id, username, full_name, email, phone, status
                        FROM users
                        WHERE LOWER(email) = LOWER(?)
                          AND status = 'active'
                        LIMIT 1
                    ");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            } else {
                $phone = normalize_kenyan_phone($identifier);

                if (!$phone) {
                    $error = 'Please enter a valid Kenyan phone number, for example 0706004939.';
                } else {
                    $stmt = $pdo->prepare("
                        SELECT id, username, full_name, email, phone, status
                        FROM users
                        WHERE phone = ?
                          AND status = 'active'
                        LIMIT 1
                    ");
                    $stmt->execute([$phone]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }

            if (!$error && !$user) {
                $error = 'No active pastor account was found for the details provided.';
            }

            if (!$error && $user) {
                if ($method === 'email' && !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
                    $error = 'This pastor account does not have a valid email address configured.';
                } elseif ($method === 'phone' && !normalize_kenyan_phone((string)$user['phone'])) {
                    $error = 'This pastor account does not have a valid Kenyan phone number configured.';
                }
            }

            if (!$error && $user) {
                $code = (string)random_int(100000, 999999);
                $codeHash = password_hash($code, PASSWORD_DEFAULT);
                $expiresAt = date('Y-m-d H:i:s', time() + 600);

                $update = $pdo->prepare("
                    UPDATE users
                    SET reset_code_hash = ?,
                        reset_code_expires_at = ?,
                        reset_attempts = 0
                    WHERE id = ?
                ");
                $update->execute([$codeHash, $expiresAt, $user['id']]);

                $recipientName = $user['full_name'] ?: $user['username'];
                $sent = false;

                if ($method === 'email') {
                    $subject = 'FGCK Makutano West Joyland - Password Reset Code';
                    $safeName = e($recipientName);

                    $message = '
                    <div style="font-family:Arial,sans-serif;line-height:1.7;color:#172033">
                        <h2 style="color:#2563eb">Password Reset Verification</h2>
                        <p>Dear ' . $safeName . ',</p>
                        <p>Use the verification code below to reset your pastor portal password.</p>

                        <div style="margin:25px 0;text-align:center">
                            <div style="display:inline-block;padding:18px 30px;background:#eff6ff;border:2px dashed #2563eb;border-radius:14px">
                                <div style="font-size:11px;color:#68758a;font-weight:700;letter-spacing:1px">
                                    VERIFICATION CODE
                                </div>

                                <div style="font-size:34px;font-weight:800;letter-spacing:8px;color:#2563eb">
                                    ' . $code . '
                                </div>
                            </div>
                        </div>

                        <p>This code expires in <strong>10 minutes</strong>.</p>

                        <p style="color:#68758a;font-size:12px">
                            If you did not request a password reset, ignore this message.
                        </p>
                    </div>';

                    $sent = send_system_email(
                        (string)$user['email'],
                        (string)$recipientName,
                        $subject,
                        $message
                    );
                } else {
                    $sent = send_password_reset_sms((string)$user['phone'], $code);
                }

                if (!$sent) {
                    $clear = $pdo->prepare("
                        UPDATE users
                        SET reset_code_hash = NULL,
                            reset_code_expires_at = NULL,
                            reset_attempts = 0
                        WHERE id = ?
                    ");
                    $clear->execute([$user['id']]);

                    $error = $method === 'email'
                        ? 'We could not send the verification email. Check your SMTP configuration and try again.'
                        : 'We could not send the SMS. Check your SMS gateway configuration and try again.';
                } else {
                    $_SESSION['password_reset_method'] = $method;
                    $_SESSION['password_reset_user_id'] = (int)$user['id'];
                    $_SESSION['password_reset_sent_at'] = time();
                    $_SESSION['password_reset_identifier'] =
                        $method === 'email'
                            ? (string)$user['email']
                            : (string)$user['phone'];

                    if (function_exists('log_activity')) {
                        log_activity(
                            $pdo,
                            null,
                            (int)$user['id'],
                            'password_reset_requested',
                            'Password reset verification code sent by ' . $method
                        );
                    }

                    redirect('/fgck_joyland/staff/verify_reset_code.php');
                }
            }
        }
    }
}

$page_title = 'Forgot Password';

require __DIR__ . '/../includes/header.php';
?>

<style>

/* =========================================================
   FGCK JOYLAND - PASSWORD RECOVERY
   BLUE THEME
   ========================================================= */

:root {
    --joy-blue: #2563eb;
    --joy-blue-dark: #1d4ed8;
    --joy-blue-deep: #0f2a55;
    --joy-blue-light: #3b82f6;
    --joy-sky: #38bdf8;
    --joy-sky-soft: #e0f2fe;

    --joy-gold: #f4c95d;

    --joy-text: #172033;
    --joy-muted: #68758a;
    --joy-border: #dce5f0;
}


/* =========================================================
   PAGE
   ========================================================= */

.recovery-page {

    min-height: calc(100vh - 70px);

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 30px 15px;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(37,99,235,.12),
            transparent 30%
        ),
        radial-gradient(
            circle at 90% 90%,
            rgba(56,189,248,.12),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #f8fbff,
            #eef5fc
        );

    position: relative;

    overflow: hidden;
}


/* Decorative background */

.recovery-page::before {

    content: "";

    position: absolute;

    width: 330px;
    height: 330px;

    border-radius: 50%;

    top: -170px;
    right: -120px;

    background: rgba(37,99,235,.06);

    pointer-events: none;
}

.recovery-page::after {

    content: "";

    position: absolute;

    width: 280px;
    height: 280px;

    border-radius: 50%;

    bottom: -140px;
    left: -110px;

    background: rgba(56,189,248,.07);

    pointer-events: none;
}


/* =========================================================
   CARD
   ========================================================= */

.recovery-card {

    width: 100%;

    max-width: 560px;

    background: rgba(255,255,255,.98);

    padding: 45px;

    border-radius: 26px;

    box-shadow:
        0 25px 70px rgba(15,42,85,.14),
        0 8px 25px rgba(0,0,0,.04);

    border: 1px solid rgba(255,255,255,.85);

    position: relative;

    z-index: 2;
}


/* =========================================================
   LOGO
   ========================================================= */

.recovery-logo {

    width: 88px;
    height: 88px;

    margin: 0 auto 22px;

    padding: 11px;

    border-radius: 50%;

    background: #fff;

    box-shadow:
        0 12px 30px rgba(15,42,85,.12);

    border: 1px solid #eef3f9;
}

.recovery-logo img {

    width: 100%;
    height: 100%;

    object-fit: contain;
}


/* =========================================================
   HEADING
   ========================================================= */

.recovery-heading {

    text-align: center;

    margin-bottom: 28px;
}

.recovery-heading h1 {

    font-size: 28px;

    font-weight: 800;

    color: var(--joy-text);
}

.recovery-heading p {

    color: var(--joy-muted);

    font-size: 14px;

    line-height: 1.7;
}


/* =========================================================
   METHOD TABS
   ========================================================= */

.method-tabs {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 10px;

    margin-bottom: 20px;
}

.method-option {

    border: 1px solid var(--joy-border);

    border-radius: 13px;

    padding: 14px;

    cursor: pointer;

    text-align: center;

    font-weight: 700;

    color: #526176;

    background: #f8fbff;

    transition: .2s;
}

.method-option:hover,
.method-option.active {

    border-color: var(--joy-blue);

    background: #eff6ff;

    color: var(--joy-blue);
}

.method-option input {

    display: none;
}


/* =========================================================
   INPUT
   ========================================================= */

.recovery-input {

    height: 55px;

    border-radius: 13px;

    background: #f8fbff;

    border: 1px solid var(--joy-border);

    font-size: 14px;

    color: var(--joy-text);

    transition: .25s;
}

.recovery-input::placeholder {

    color: #9ba8ba;
}

.recovery-input:hover {

    border-color: #c6d5e8;
}

.recovery-input:focus {

    border-color: var(--joy-blue);

    background: #fff;

    box-shadow:
        0 0 0 4px rgba(37,99,235,.09);
}


/* =========================================================
   RECOVERY BUTTON
   ========================================================= */

.recovery-button {

    height: 55px;

    border: 0;

    border-radius: 13px;

    color: #fff;

    font-weight: 800;

    background:
        linear-gradient(
            135deg,
            var(--joy-blue-dark),
            var(--joy-blue-light)
        );

    box-shadow:
        0 10px 22px rgba(37,99,235,.22);

    transition:
        transform .2s ease,
        box-shadow .2s ease;
}

.recovery-button:hover {

    color: #fff;

    transform: translateY(-2px);

    box-shadow:
        0 14px 28px rgba(37,99,235,.28);
}

.recovery-button:active {

    transform: translateY(0);
}


/* =========================================================
   NOTE
   ========================================================= */

.recovery-note {

    text-align: center;

    color: #8a97a8;

    font-size: 11px;

    margin-top: 18px;
}

.recovery-note i {

    color: var(--joy-blue);
}


/* =========================================================
   BACK TO LOGIN
   ========================================================= */

.back-login {

    text-align: center;

    margin-top: 22px;

    padding-top: 18px;

    border-top: 1px solid #edf1f6;
}

.back-login a {

    color: var(--joy-blue);

    font-size: 13px;

    font-weight: 700;

    text-decoration: none;

    transition: .2s;
}

.back-login a:hover {

    color: var(--joy-blue-dark);
}


/* =========================================================
   ERROR
   ========================================================= */

.recovery-card .alert-danger {

    border-radius: 12px;

    font-size: 13px;

    line-height: 1.5;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 550px) {

    .recovery-page {

        padding: 20px 12px;
    }

    .recovery-card {

        padding: 30px 22px;

        border-radius: 22px;
    }

    .recovery-heading h1 {

        font-size: 25px;
    }

    .method-option {

        padding: 12px 8px;

        font-size: 13px;
    }
}

@media (max-width: 380px) {

    .method-tabs {

        grid-template-columns: 1fr;
    }
}

</style>


<div class="recovery-page">

    <div class="recovery-card">


        <!-- =================================================
             LOGO
             ================================================= -->

        <div class="recovery-logo">

            <img
                src="/fgck_joyland/assets/images/full_gospel_churches_logo.png"
                alt="FGCK Makutano West Joyland"
            >

        </div>


        <!-- =================================================
             HEADING
             ================================================= -->

        <div class="recovery-heading">

            <div
                class="text-uppercase fw-bold small"
                style="
                    color:#2563eb;
                    letter-spacing:1.5px;
                "
            >
                Pastor Portal
            </div>

            <h1 class="mt-2 mb-2">
                Forgot Password?
            </h1>

            <p>
                Choose where you want to receive your
                6-digit verification code.
            </p>

        </div>


        <!-- =================================================
             ERROR
             ================================================= -->

        <?php if ($error): ?>

            <div class="alert alert-danger">

                <i class="bi bi-exclamation-circle-fill me-2"></i>

                <?= e($error) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             FORM
             ================================================= -->

        <form
            method="post"
            id="recoveryForm"
        >

            <input
                type="hidden"
                name="csrf"
                value="<?= e(csrf_token()) ?>"
            >


            <!-- Method -->

            <div class="method-tabs">

                <label
                    class="method-option
                    <?= $method === 'email' ? 'active' : '' ?>"
                >

                    <input
                        type="radio"
                        name="method"
                        value="email"
                        <?= $method === 'email' ? 'checked' : '' ?>
                    >

                    <i class="bi bi-envelope me-1"></i>

                    Email

                </label>


                <label
                    class="method-option
                    <?= $method === 'phone' ? 'active' : '' ?>"
                >

                    <input
                        type="radio"
                        name="method"
                        value="phone"
                        <?= $method === 'phone' ? 'checked' : '' ?>
                    >

                    <i class="bi bi-phone me-1"></i>

                    Phone / SMS

                </label>

            </div>


            <!-- Identifier -->

            <label
                class="form-label"
                id="identifierLabel"
            >
                Email address
            </label>


            <input
                type="text"
                name="identifier"
                id="identifier"
                class="form-control recovery-input"
                value="<?= e($identifier) ?>"
                placeholder="pastor@example.com"
                autocomplete="email"
                required
            >


            <div
                class="form-text mb-3"
                id="identifierHelp"
            >
                Enter the email address registered on your pastor account.
            </div>


            <!-- Submit -->

            <button
                type="submit"
                class="btn recovery-button w-100"
            >

                <i class="bi bi-send me-2"></i>

                Send Verification Code

            </button>

        </form>


        <!-- =================================================
             SECURITY NOTE
             ================================================= -->

        <div class="recovery-note">

            <i class="bi bi-shield-lock me-1"></i>

            Verification codes expire after 10 minutes.

        </div>


        <!-- =================================================
             BACK TO LOGIN
             ================================================= -->

        <div class="back-login">

            <a href="/fgck_joyland/staff/login.php">

                <i class="bi bi-arrow-left me-1"></i>

                Back to Pastor Login

            </a>

        </div>

    </div>

</div>


<script>

document
    .querySelectorAll('input[name="method"]')
    .forEach(function(radio){

        radio.addEventListener(
            'change',
            function(){

                const phone =
                    this.value === 'phone';


                document
                    .getElementById('identifierLabel')
                    .textContent =
                        phone
                            ? 'Phone number'
                            : 'Email address';


                document
                    .getElementById('identifier')
                    .placeholder =
                        phone
                            ? '0706004939'
                            : 'pastor@example.com';


                document
                    .getElementById('identifier')
                    .setAttribute(
                        'autocomplete',
                        phone ? 'tel' : 'email'
                    );


                document
                    .getElementById('identifierHelp')
                    .textContent =
                        phone
                            ? 'Enter the Kenyan phone number registered on your pastor account.'
                            : 'Enter the email address registered on your pastor account.';


                document
                    .querySelectorAll('.method-option')
                    .forEach(function(el){

                        el.classList.remove('active');

                    });


                this
                    .closest('.method-option')
                    .classList.add('active');

            }
        );

    });

</script>


<?php require __DIR__ . '/../includes/footer.php'; ?>