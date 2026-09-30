<?php
require __DIR__ . '/../includes/bootstrap.php';

if (!empty($_SESSION['staff_id'])) {
    redirect('/fgck_joyland/staff/dashboard.php');
}

$error = '';
$login_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {

        $q = $pdo->prepare("
            SELECT *
            FROM users
            WHERE username = ?
            AND status = 'active'
            LIMIT 1
        ");

        $q->execute([$username]);
        $u = $q->fetch();

        if ($u && password_verify($password, $u['password_hash'])) {

            // Regenerate session ID for security
            session_regenerate_id(true);

            // Store logged-in staff
            $_SESSION['staff_id'] = $u['id'];

            // Update last login
            $pdo->prepare("
                UPDATE users
                SET last_login_at = NOW()
                WHERE id = ?
            ")->execute([$u['id']]);

            // Record activity
            log_activity(
                $pdo,
                null,
                $u['id'],
                'staff_login',
                'Staff login'
            );

            $login_success = true;

        } else {

            $error = 'The username or password you entered is incorrect.';
        }
    }
}

$page_title = 'Pastor Portal Login';

require __DIR__ . '/../includes/header.php';
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>

/* =========================================================
   FGCK JOYLAND - PROFESSIONAL PASTOR LOGIN
   BLUE THEME
   ========================================================= */

:root {
    --joy-blue: #2563eb;
    --joy-blue-dark: #1d4ed8;
    --joy-blue-deep: #0f2a55;
    --joy-blue-navy: #0b1f3a;
    --joy-blue-light: #3b82f6;
    --joy-sky: #38bdf8;
    --joy-sky-soft: #e0f2fe;

    --joy-gold: #f4c95d;
    --joy-gold-light: #ffe7a3;

    --joy-white: #ffffff;
    --joy-text: #172033;
    --joy-muted: #68758a;
    --joy-border: #dce5f0;
    --joy-bg: #f3f7fc;
}

/* Page */

.auth-page {
    min-height: calc(100vh - 70px);

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 35px 20px;

    position: relative;

    overflow: hidden;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(37, 99, 235, 0.12),
            transparent 30%
        ),
        radial-gradient(
            circle at 90% 90%,
            rgba(56, 189, 248, 0.12),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #f8fbff 0%,
            #eef5fc 100%
        );
}

/* Decorative circles */

.auth-page::before,
.auth-page::after {
    content: "";

    position: absolute;

    border-radius: 50%;

    pointer-events: none;
}

.auth-page::before {
    width: 360px;
    height: 360px;

    top: -180px;
    right: -120px;

    background: rgba(37, 99, 235, 0.07);
}

.auth-page::after {
    width: 300px;
    height: 300px;

    bottom: -150px;
    left: -120px;

    background: rgba(56, 189, 248, 0.08);
}

/* Main Card */

.auth-card {
    width: 100%;

    max-width: 980px;

    min-height: 590px;

    display: grid;

    grid-template-columns: 0.95fr 1.05fr;

    background: rgba(255, 255, 255, 0.97);

    border-radius: 30px;

    overflow: hidden;

    position: relative;

    z-index: 2;

    box-shadow:
        0 30px 80px rgba(15, 42, 85, 0.14),
        0 8px 25px rgba(0, 0, 0, 0.05);

    border: 1px solid rgba(255,255,255,.8);
}


/* =========================================================
   BRAND PANEL
   ========================================================= */

.auth-brand-panel {
    position: relative;

    display: flex;

    flex-direction: column;

    justify-content: center;

    align-items: center;

    text-align: center;

    padding: 50px 40px;

    color: white;

    background:
        linear-gradient(
            145deg,
            rgba(11, 31, 58, .99),
            rgba(37, 99, 235, .97)
        );
}

/* Decorative overlay */

.auth-brand-panel::before {
    content: "";

    position: absolute;

    width: 280px;
    height: 280px;

    border-radius: 50%;

    top: -120px;
    left: -100px;

    border: 1px solid rgba(255,255,255,.10);
}

.auth-brand-panel::after {
    content: "";

    position: absolute;

    width: 340px;
    height: 340px;

    border-radius: 50%;

    bottom: -180px;
    right: -150px;

    border: 1px solid rgba(244,201,93,.25);
}


/* Logo */

.brand-logo-wrapper {
    width: 125px;
    height: 125px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: rgba(255,255,255,.97);

    padding: 13px;

    box-shadow:
        0 15px 35px rgba(0,0,0,.20);

    margin-bottom: 25px;

    position: relative;

    z-index: 2;
}

.brand-logo-wrapper img {
    width: 100%;
    height: 100%;

    object-fit: contain;
}

.brand-title {
    font-size: 29px;

    font-weight: 800;

    margin: 0 0 8px;

    letter-spacing: -.5px;

    position: relative;

    z-index: 2;
}

.brand-subtitle {
    font-size: 14px;

    color: rgba(255,255,255,.82);

    max-width: 280px;

    line-height: 1.7;

    margin-bottom: 28px;

    position: relative;

    z-index: 2;
}


/* Motto */

.brand-motto {
    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 10px 18px;

    border-radius: 50px;

    background: rgba(244,201,93,.14);

    border: 1px solid rgba(244,201,93,.35);

    color: var(--joy-gold-light);

    font-size: 13px;

    font-weight: 700;

    position: relative;

    z-index: 2;
}

.brand-motto i {
    font-size: 15px;
}


/* =========================================================
   LOGIN PANEL
   ========================================================= */

.auth-login-panel {
    padding: 55px 60px;

    display: flex;

    flex-direction: column;

    justify-content: center;

    background: #fff;
}

.login-heading {
    margin-bottom: 32px;
}

.login-heading .eyebrow {
    display: inline-block;

    color: var(--joy-blue);

    font-size: 12px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: 1.5px;

    margin-bottom: 8px;
}

.login-heading h1 {
    font-size: 31px;

    font-weight: 800;

    color: var(--joy-text);

    margin: 0 0 8px;
}

.login-heading p {
    color: var(--joy-muted);

    font-size: 14px;

    margin: 0;

    line-height: 1.6;
}


/* Form groups */

.form-group {
    margin-bottom: 21px;
}

.form-label {
    font-size: 13px;

    font-weight: 700;

    color: #34445b;

    margin-bottom: 8px;
}


/* Input wrapper */

.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;

    left: 16px;

    top: 50%;

    transform: translateY(-50%);

    color: #8493a8;

    font-size: 17px;

    pointer-events: none;
}

.form-control.auth-input {
    height: 53px;

    border-radius: 13px;

    border: 1px solid var(--joy-border);

    background: #f8fbff;

    padding: 0 48px;

    color: var(--joy-text);

    font-size: 14px;

    transition: all .25s ease;
}

.form-control.auth-input::placeholder {
    color: #9ba8ba;
}

.form-control.auth-input:hover {
    border-color: #c6d5e8;
}

.form-control.auth-input:focus {
    border-color: var(--joy-blue);

    background: white;

    box-shadow:
        0 0 0 4px rgba(37,99,235,.09);

    outline: none;
}


/* Password button */

.password-toggle {
    position: absolute;

    right: 14px;

    top: 50%;

    transform: translateY(-50%);

    border: 0;

    background: transparent;

    color: #8493a8;

    width: 35px;
    height: 35px;

    border-radius: 8px;

    display: flex;

    align-items: center;

    justify-content: center;

    cursor: pointer;

    transition: .2s;
}

.password-toggle:hover {
    background: #edf5ff;

    color: var(--joy-blue);
}


/* Error */

.login-error {
    display: flex;

    align-items: flex-start;

    gap: 10px;

    background: #fff4f3;

    border: 1px solid #ffd8d4;

    color: #b42318;

    border-radius: 12px;

    padding: 12px 14px;

    margin-bottom: 22px;

    font-size: 13px;

    line-height: 1.5;
}

.login-error i {
    font-size: 17px;

    margin-top: 1px;
}


/* Login button */

.login-button {
    height: 54px;

    border: 0;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            var(--joy-blue-dark),
            var(--joy-blue-light)
        );

    color: white;

    font-size: 14px;

    font-weight: 800;

    letter-spacing: .2px;

    box-shadow:
        0 10px 22px rgba(37,99,235,.22);

    transition:
        transform .2s ease,
        box-shadow .2s ease;
}

.login-button:hover {
    transform: translateY(-2px);

    box-shadow:
        0 14px 28px rgba(37,99,235,.28);

    color: white;
}

.login-button:active {
    transform: translateY(0);
}

.login-button:disabled {
    opacity: .75;

    cursor: not-allowed;

    transform: none;
}


/* Security note */

.security-note {
    display: flex;

    justify-content: center;

    align-items: center;

    gap: 7px;

    color: #8a97a8;

    font-size: 11px;

    margin-top: 20px;
}

.security-note i {
    color: var(--joy-blue);
}


/* Back link */

.back-link {
    text-align: center;

    margin-top: 25px;

    padding-top: 20px;

    border-top: 1px solid #edf1f6;
}

.back-link a {
    color: var(--joy-blue);

    font-size: 13px;

    font-weight: 700;

    text-decoration: none;

    transition: .2s;
}

.back-link a:hover {
    color: var(--joy-blue-dark);
}


/* =========================================================
   SWEETALERT
   ========================================================= */

.joyland-login-popup {
    border-radius: 24px !important;

    padding: 35px !important;

    box-shadow:
        0 30px 80px rgba(15,42,85,.20) !important;
}

.joyland-login-popup .swal2-title {
    font-weight: 800 !important;

    color: #172033 !important;
}

.joyland-login-popup .swal2-icon.swal2-success {
    border-color: var(--joy-blue) !important;

    color: var(--joy-blue) !important;
}

.joyland-login-popup .swal2-success-ring {
    border-color:
        rgba(37,99,235,.20) !important;
}

.joyland-login-popup .swal2-timer-progress-bar {
    background:
        linear-gradient(
            90deg,
            var(--joy-blue-dark),
            var(--joy-sky)
        ) !important;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 850px) {

    .auth-card {
        max-width: 520px;

        grid-template-columns: 1fr;

        min-height: auto;
    }

    .auth-brand-panel {
        padding: 38px 25px;
    }

    .brand-logo-wrapper {
        width: 95px;
        height: 95px;

        margin-bottom: 18px;
    }

    .brand-title {
        font-size: 24px;
    }

    .brand-subtitle {
        margin-bottom: 18px;
    }

    .auth-login-panel {
        padding: 40px 30px;
    }
}

@media (max-width: 480px) {

    .auth-page {
        padding: 18px 12px;
    }

    .auth-card {
        border-radius: 22px;
    }

    .auth-brand-panel {
        padding: 30px 20px;
    }

    .auth-login-panel {
        padding: 32px 22px;
    }

    .login-heading h1 {
        font-size: 26px;
    }

    .brand-title {
        font-size: 22px;
    }
}


/* Reduced motion */

@media (prefers-reduced-motion: reduce) {

    *,
    *::before,
    *::after {

        scroll-behavior: auto !important;

        transition: none !important;
    }
}

</style>


<div class="auth-page">

<div class="auth-card">


    <!-- ============================================
         BRAND SIDE
         ============================================ -->

    <section class="auth-brand-panel">

        <div class="brand-logo-wrapper">

            <img
                src="/fgck_joyland/assets/images/full_gospel_churches_logo.png"
                alt="FGCK Joyland Church Logo"
            >

        </div>


        <h2 class="brand-title">
            FGCK Joyland
        </h2>


        <p class="brand-subtitle">
            Welcome to the Pastor Appointment Management Portal.
        </p>

    </section>


    <!-- ============================================
         LOGIN SIDE
         ============================================ -->

    <section class="auth-login-panel">

        <div class="login-heading">

            <span class="eyebrow">
                Pastor Portal
            </span>

            <h1>
                Welcome Back
            </h1>

            <p>
                Sign in to access your pastoral dashboard
                and manage appointments.
            </p>

        </div>


        <?php if ($error): ?>

            <div
                class="login-error"
                role="alert"
            >

                <i class="bi bi-exclamation-circle-fill"></i>

                <div>
                    <?= e($error) ?>
                </div>

            </div>

        <?php endif; ?>


        <form
            method="post"
            id="loginForm"
            novalidate
        >

            <input
                type="hidden"
                name="csrf"
                value="<?= e(csrf_token()) ?>"
            >


            <!-- Username -->

            <div class="form-group">

                <label
                    class="form-label"
                    for="username"
                >
                    Username
                </label>

                <div class="input-wrapper">

                    <i
                        class="bi bi-person input-icon"
                        aria-hidden="true"
                    ></i>

                    <input
                        id="username"
                        class="form-control auth-input"
                        name="username"
                        type="text"
                        placeholder="Enter your username"
                        autocomplete="username"
                        autocapitalize="none"
                        spellcheck="false"
                        required
                        value="<?= e($_POST['username'] ?? '') ?>"
                    >

                </div>

            </div>


            <!-- Password -->

            <div class="form-group">

                <label
                    class="form-label"
                    for="password"
                >
                    Password
                </label>

                <div class="input-wrapper">

                    <i
                        class="bi bi-lock input-icon"
                        aria-hidden="true"
                    ></i>

                    <input
                        id="password"
                        class="form-control auth-input"
                        name="password"
                        type="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        id="togglePassword"
                        aria-label="Show password"
                    >

                        <i
                            class="bi bi-eye"
                            id="passwordIcon"
                        ></i>

                    </button>

                </div>

            </div>


            <!-- Login -->

            <button
                type="submit"
                class="btn login-button w-100"
                id="loginButton"
            >

                <span id="loginButtonContent">

                    <i class="bi bi-box-arrow-in-right me-2"></i>

                    Sign In Securely

                </span>

            </button>


            <div class="security-note">

                <i class="bi bi-shield-lock-fill"></i>

                <span>
                    Secure pastor authentication
                </span>

            </div>

        </form>


        <div class="text-end mb-3">

            <a
                href="/fgck_joyland/staff/forgot_password.php"
                style="
                    color:#2563eb;
                    font-size:13px;
                    font-weight:700;
                    text-decoration:none;
                "
            >

                <i class="bi bi-key me-1"></i>

                Forgot Password?

            </a>

        </div>


        <div class="back-link">

            <a href="/fgck_joyland/">

                <i class="bi bi-arrow-left me-1"></i>

                Back to FGCK Joyland Portal

            </a>

        </div>

    </section>

</div>

</div>


<?php if ($login_success): ?>

<script>

Swal.fire({

    icon: 'success',

    title: 'Welcome Back!',

    html: `
        <div style="
            margin-top:10px;
            line-height:1.7;
        ">

            <div style="
                width:64px;
                height:64px;
                margin:0 auto 15px;
                border-radius:50%;
                display:flex;
                align-items:center;
                justify-content:center;
                background:#eff6ff;
                color:#2563eb;
                font-size:27px;
            ">

                <i class="bi bi-person-check-fill"></i>

            </div>

            <strong style="
                color:#2563eb;
                font-size:19px;
                display:block;
                margin-bottom:6px;
            ">
                FGCK Joyland Pastor Portal
            </strong>

            <p style="
                margin:0 0 5px;
                color:#66758a;
                font-size:14px;
            ">
                You have signed in successfully.
            </p>

            <small style="
                color:#9aa8b8;
                font-size:12px;
            ">
                Preparing your dashboard...
            </small>

        </div>
    `,

    showConfirmButton: false,

    timer: 2500,

    timerProgressBar: true,

    allowOutsideClick: false,

    allowEscapeKey: false,

    allowEnterKey: false,

    customClass: {
        popup: 'joyland-login-popup'
    }

}).then(() => {

    window.location.href =
        '/fgck_joyland/staff/dashboard.php';

});


/*
 * Backup redirect.
 * Prevents the user from remaining on the login
 * page if the SweetAlert callback is interrupted.
 */

setTimeout(() => {

    window.location.href =
        '/fgck_joyland/staff/dashboard.php';

}, 2800);

</script>

<?php endif; ?>


<script>

/* =========================================================
   PASSWORD VISIBILITY
   ========================================================= */

const passwordInput =
    document.getElementById('password');

const togglePassword =
    document.getElementById('togglePassword');

const passwordIcon =
    document.getElementById('passwordIcon');


if (togglePassword && passwordInput) {

    togglePassword.addEventListener(
        'click',
        function () {

            const isPassword =
                passwordInput.type === 'password';

            passwordInput.type =
                isPassword ? 'text' : 'password';

            passwordIcon.className =
                isPassword
                    ? 'bi bi-eye-slash'
                    : 'bi bi-eye';

            togglePassword.setAttribute(
                'aria-label',
                isPassword
                    ? 'Hide password'
                    : 'Show password'
            );

        }
    );

}


/* =========================================================
   PREVENT DOUBLE SUBMISSION
   ========================================================= */

const loginForm =
    document.getElementById('loginForm');

const loginButton =
    document.getElementById('loginButton');

const loginButtonContent =
    document.getElementById('loginButtonContent');


if (loginForm) {

    loginForm.addEventListener(
        'submit',
        function (event) {

            if (!loginForm.checkValidity()) {

                return;

            }

            if (loginButton) {

                loginButton.disabled = true;

            }

            if (loginButtonContent) {

                loginButtonContent.innerHTML = `

                    <span
                        class="spinner-border
                               spinner-border-sm
                               me-2"
                        role="status"
                        aria-hidden="true">
                    </span>

                    Authenticating...

                `;

            }

        }
    );

}


/* =========================================================
   AUTO FOCUS
   ========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const username =
            document.getElementById('username');

        if (username && !username.value) {

            username.focus();

        }

    }
);

</script>


<?php require __DIR__ . '/../includes/footer.php'; ?>