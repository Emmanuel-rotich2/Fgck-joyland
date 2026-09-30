<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (!empty($_SESSION['member_id'])) {
    redirect('/fgck_joyland/member/dashboard.php');
}

$error = '';
$login_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $error = 'Please enter your email address and password.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } else {

        $q = $pdo->prepare("
            SELECT *
            FROM members
            WHERE email = ?
            AND status = 'active'
            LIMIT 1
        ");

        $q->execute([$email]);
        $m = $q->fetch();

        if ($m && password_verify($password, $m['password_hash'])) {

            /*
             * Regenerate session ID for security.
             */
            session_regenerate_id(true);

            /*
             * Store member session.
             */
            $_SESSION['member_id'] = (int) $m['id'];

            /*
             * Update last login.
             */
            $pdo->prepare("
                UPDATE members
                SET last_login_at = NOW()
                WHERE id = ?
            ")->execute([$m['id']]);

            /*
             * Record member activity.
             */
            log_activity(
                $pdo,
                $m['id'],
                null,
                'member_login',
                'Member login'
            );

            $login_success = true;

        } else {

            $error = 'The email or password you entered is incorrect.';
        }
    }
}

$page_title = 'Member Login';

require __DIR__ . '/../includes/header.php';
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>

/* =========================================================
   FGCK JOYLAND MEMBER LOGIN
   MODERN BLUE PORTAL DESIGN
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

    --joy-text: #172033;
    --joy-muted: #68758a;

    --joy-border: #dce5f0;

    --joy-bg: #f3f7fc;
}


/* =========================================================
   PAGE
   ========================================================= */

.member-auth-page {

    min-height: calc(100vh - 70px);

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 40px 20px;

    position: relative;

    overflow: hidden;

    background:

        radial-gradient(
            circle at 5% 15%,
            rgba(37, 99, 235, .12),
            transparent 30%
        ),

        radial-gradient(
            circle at 95% 85%,
            rgba(56, 189, 248, .13),
            transparent 30%
        ),

        radial-gradient(
            circle at 50% 100%,
            rgba(244, 201, 93, .08),
            transparent 25%
        ),

        linear-gradient(
            135deg,
            #f9fbff,
            #eef4fb
        );
}


/* =========================================================
   BACKGROUND DECORATIONS
   ========================================================= */

.member-auth-page::before {

    content: "";

    position: absolute;

    width: 430px;

    height: 430px;

    border-radius: 50%;

    top: -230px;

    right: -150px;

    border: 1px solid rgba(37, 99, 235, .10);

    box-shadow:

        0 0 0 35px rgba(37, 99, 235, .025),

        0 0 0 70px rgba(37, 99, 235, .018);
}


.member-auth-page::after {

    content: "";

    position: absolute;

    width: 350px;

    height: 350px;

    border-radius: 50%;

    bottom: -190px;

    left: -140px;

    border: 1px solid rgba(56, 189, 248, .13);

    box-shadow:

        0 0 0 35px rgba(56, 189, 248, .025),

        0 0 0 70px rgba(56, 189, 248, .018);
}


/* =========================================================
   MAIN CARD
   ========================================================= */

.member-auth-card {

    width: 100%;

    max-width: 1040px;

    min-height: 610px;

    display: grid;

    grid-template-columns: .95fr 1.05fr;

    background: #ffffff;

    border-radius: 30px;

    overflow: hidden;

    position: relative;

    z-index: 2;

    box-shadow:

        0 35px 90px rgba(15, 42, 85, .15),

        0 10px 30px rgba(15, 42, 85, .06);

    border: 1px solid rgba(255, 255, 255, .95);
}


/* =========================================================
   LEFT BRAND PANEL
   ========================================================= */

.member-brand-panel {

    position: relative;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    text-align: center;

    padding: 55px 45px;

    color: #ffffff;

    overflow: hidden;

    background:

        radial-gradient(
            circle at 15% 20%,
            rgba(56, 189, 248, .25),
            transparent 28%
        ),

        radial-gradient(
            circle at 90% 85%,
            rgba(244, 201, 93, .13),
            transparent 30%
        ),

        linear-gradient(
            145deg,
            #081c36 0%,
            #0d3270 48%,
            #1557c7 100%
        );
}


/* Top shine */

.member-brand-panel > * {

    position: relative;

    z-index: 3;
}


/* Decorative rings */

.member-brand-panel::before {

    content: "";

    position: absolute;

    width: 320px;

    height: 320px;

    border-radius: 50%;

    top: -165px;

    left: -145px;

    border: 1px solid rgba(255, 255, 255, .10);

    box-shadow:

        0 0 0 35px rgba(255, 255, 255, .018),

        0 0 0 70px rgba(255, 255, 255, .012);
}


.member-brand-panel::after {

    content: "";

    position: absolute;

    width: 390px;

    height: 390px;

    border-radius: 50%;

    right: -205px;

    bottom: -210px;

    border: 1px solid rgba(244, 201, 93, .20);

    box-shadow:

        0 0 0 35px rgba(244, 201, 93, .025);
}


/* =========================================================
   LOGO
   ========================================================= */

.member-logo {

    width: 130px;

    height: 130px;

    padding: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: rgba(255, 255, 255, .98);

    box-shadow:

        0 20px 45px rgba(0, 0, 0, .25),

        0 0 0 7px rgba(255, 255, 255, .08);

    margin-bottom: 27px;
}


.member-logo img {

    width: 100%;

    height: 100%;

    object-fit: contain;
}


/* =========================================================
   BRAND TEXT
   ========================================================= */

.member-brand-panel h1 {

    font-size: 31px;

    font-weight: 850;

    margin: 0 0 10px;

    letter-spacing: -.6px;
}


.member-brand-panel .brand-description {

    max-width: 315px;

    color: rgba(255, 255, 255, .82);

    font-size: 14px;

    line-height: 1.8;

    margin: 0 0 28px;
}


/* =========================================================
   MEMBER BADGE
   ========================================================= */

.member-badge {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    padding: 11px 18px;

    border-radius: 50px;

    background: rgba(255, 255, 255, .08);

    border: 1px solid rgba(255, 255, 255, .16);

    color: #ffffff;

    font-size: 12px;

    font-weight: 800;

    backdrop-filter: blur(10px);

    box-shadow:

        0 10px 25px rgba(0, 0, 0, .10);
}


.member-badge i {

    color: var(--joy-gold);

    font-size: 15px;
}


/* =========================================================
   SMALL BRAND LINE
   ========================================================= */

.member-brand-line {

    width: 55px;

    height: 3px;

    border-radius: 50px;

    background:

        linear-gradient(
            90deg,
            var(--joy-sky),
            var(--joy-gold)
        );

    margin: 0 auto 20px;
}


/* =========================================================
   RIGHT LOGIN PANEL
   ========================================================= */

.member-login-panel {

    padding: 58px 68px;

    display: flex;

    flex-direction: column;

    justify-content: center;

    background:

        linear-gradient(
            180deg,
            #ffffff 0%,
            #fbfdff 100%
        );
}


/* =========================================================
   LOGIN HEADING
   ========================================================= */

.member-login-heading {

    margin-bottom: 30px;
}


.member-login-heading .eyebrow {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    color: var(--joy-blue);

    font-size: 11px;

    font-weight: 850;

    letter-spacing: 1.7px;

    text-transform: uppercase;

    margin-bottom: 10px;
}


.member-login-heading .eyebrow::before {

    content: "";

    width: 20px;

    height: 2px;

    border-radius: 20px;

    background: var(--joy-blue);
}


.member-login-heading h2 {

    margin: 0 0 9px;

    color: var(--joy-text);

    font-size: 32px;

    font-weight: 850;

    letter-spacing: -.7px;
}


.member-login-heading p {

    margin: 0;

    color: var(--joy-muted);

    font-size: 14px;

    line-height: 1.7;

}


/* =========================================================
   ERROR
   ========================================================= */

.member-login-error {

    display: flex;

    align-items: flex-start;

    gap: 11px;

    padding: 14px 15px;

    margin-bottom: 21px;

    border-radius: 13px;

    background: #fff5f5;

    border: 1px solid #ffd9d9;

    color: #b42318;

    font-size: 13px;

    line-height: 1.55;

}


.member-login-error i {

    font-size: 17px;

    margin-top: 1px;
}


/* =========================================================
   FORM
   ========================================================= */

.member-form-group {

    margin-bottom: 21px;
}


.member-form-label {

    display: block;

    margin-bottom: 8px;

    color: #344158;

    font-size: 13px;

    font-weight: 750;
}


.member-input-wrapper {

    position: relative;
}


.member-input-icon {

    position: absolute;

    left: 17px;

    top: 50%;

    transform: translateY(-50%);

    color: #8a98ad;

    font-size: 17px;

    pointer-events: none;

    transition: color .2s ease;
}


.member-input {

    width: 100%;

    height: 55px;

    border-radius: 13px;

    border: 1px solid var(--joy-border);

    background: #f8faff;

    padding: 0 50px;

    color: var(--joy-text);

    font-size: 14px;

    transition:

        border-color .2s ease,

        box-shadow .2s ease,

        background .2s ease;
}


.member-input::placeholder {

    color: #9ba7b7;
}


.member-input:hover {

    border-color: #bdcde1;

    background: #ffffff;
}


.member-input:focus {

    outline: none;

    background: #ffffff;

    border-color: var(--joy-blue);

    box-shadow:

        0 0 0 4px rgba(37, 99, 235, .09);
}


.member-input:focus ~ .member-input-icon {

    color: var(--joy-blue);
}


/* =========================================================
   PASSWORD TOGGLE
   ========================================================= */

.member-password-toggle {

    position: absolute;

    right: 10px;

    top: 50%;

    transform: translateY(-50%);

    width: 38px;

    height: 38px;

    border: 0;

    border-radius: 9px;

    background: transparent;

    color: #8491a4;

    display: flex;

    align-items: center;

    justify-content: center;

    cursor: pointer;

    transition:

        color .2s ease,

        background .2s ease;
}


.member-password-toggle:hover {

    color: var(--joy-blue);

    background: #edf4ff;
}


/* =========================================================
   SIGN IN BUTTON
   ========================================================= */

.member-login-button {

    width: 100%;

    height: 56px;

    border: 0;

    border-radius: 13px;

    color: #ffffff;

    font-size: 14px;

    font-weight: 850;

    letter-spacing: .1px;

    background:

        linear-gradient(
            135deg,
            #1d4ed8 0%,
            #2563eb 55%,
            #3b82f6 100%
        );

    box-shadow:

        0 12px 27px rgba(37, 99, 235, .25);

    transition:

        transform .2s ease,

        box-shadow .2s ease,

        opacity .2s ease;
}


.member-login-button:hover {

    color: #ffffff;

    transform: translateY(-2px);

    box-shadow:

        0 17px 34px rgba(37, 99, 235, .31);
}


.member-login-button:active {

    transform: translateY(0);
}


.member-login-button:disabled {

    opacity: .75;

    cursor: not-allowed;

    transform: none;
}


/* =========================================================
   SECURITY MESSAGE
   ========================================================= */

.member-security {

    display: flex;

    justify-content: center;

    align-items: center;

    gap: 7px;

    color: #8995a7;

    font-size: 11px;

    margin-top: 18px;

    text-align: center;
}


.member-security i {

    color: var(--joy-blue);

    font-size: 13px;
}


/* =========================================================
   REGISTER AREA
   ========================================================= */

.member-register {

    margin-top: 27px;

    padding-top: 22px;

    border-top: 1px solid #e9eef5;

    text-align: center;

    color: #7b8798;

    font-size: 13px;
}


.member-register a {

    color: var(--joy-blue);

    font-weight: 800;

    text-decoration: none;

    margin-left: 3px;

    transition: color .2s ease;
}


.member-register a:hover {

    color: var(--joy-blue-dark);

    text-decoration: underline;
}


/* =========================================================
   BACK TO PORTAL
   ========================================================= */

.member-back {

    text-align: center;

    margin-top: 18px;
}


.member-back a {

    display: inline-flex;

    align-items: center;

    color: #8793a4;

    font-size: 12px;

    font-weight: 650;

    text-decoration: none;

    transition:

        color .2s ease,

        transform .2s ease;
}


.member-back a:hover {

    color: var(--joy-blue);

    transform: translateX(-2px);
}


/* =========================================================
   SWEETALERT
   ========================================================= */

.joyland-member-login-popup {

    border-radius: 26px !important;

    padding: 35px !important;

    box-shadow:

        0 30px 80px rgba(15, 42, 85, .22) !important;

    max-width: 430px !important;

    border: 1px solid rgba(37, 99, 235, .08) !important;
}


.joyland-member-login-popup .swal2-title {

    color: #172033 !important;

    font-size: 25px !important;

    font-weight: 850 !important;
}


.joyland-member-login-popup
.swal2-icon.swal2-success {

    border-color:
        var(--joy-blue) !important;

    color:
        var(--joy-blue) !important;
}


.joyland-member-login-popup
.swal2-success-ring {

    border-color:
        rgba(37, 99, 235, .18) !important;
}


.joyland-member-login-popup
.swal2-timer-progress-bar {

    background:

        linear-gradient(
            90deg,
            var(--joy-blue),
            var(--joy-sky)
        ) !important;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 900px) {

    .member-auth-card {

        max-width: 560px;

        min-height: auto;

        grid-template-columns: 1fr;
    }


    .member-brand-panel {

        padding: 43px 28px;
    }


    .member-logo {

        width: 105px;

        height: 105px;

        margin-bottom: 20px;
    }


    .member-brand-panel h1 {

        font-size: 27px;
    }


    .member-brand-panel .brand-description {

        margin-bottom: 22px;
    }


    .member-login-panel {

        padding: 45px 38px;
    }

}


@media (max-width: 520px) {

    .member-auth-page {

        padding: 18px 11px;
    }


    .member-auth-card {

        border-radius: 23px;
    }


    .member-brand-panel {

        padding: 34px 20px;
    }


    .member-logo {

        width: 92px;

        height: 92px;

        padding: 10px;
    }


    .member-brand-panel h1 {

        font-size: 24px;
    }


    .member-brand-panel .brand-description {

        font-size: 13px;

        line-height: 1.7;
    }


    .member-login-panel {

        padding: 34px 21px;
    }


    .member-login-heading {

        margin-bottom: 25px;
    }


    .member-login-heading h2 {

        font-size: 27px;
    }


    .member-input {

        height: 53px;
    }


    .member-login-button {

        height: 54px;
    }

}


/* =========================================================
   VERY SMALL SCREENS
   ========================================================= */

@media (max-width: 360px) {

    .member-auth-page {

        padding: 10px 7px;
    }


    .member-brand-panel {

        padding: 30px 16px;
    }


    .member-login-panel {

        padding: 30px 17px;
    }


    .member-login-heading h2 {

        font-size: 24px;
    }


    .member-register {

        line-height: 1.7;
    }


    .member-register a {

        display: block;

        margin: 4px 0 0;
    }

}


/* =========================================================
   REDUCED MOTION
   ========================================================= */

@media (prefers-reduced-motion: reduce) {

    * {

        transition: none !important;

        animation: none !important;
    }

}

</style>


<!-- =========================================================
     MEMBER LOGIN PAGE
     ========================================================= -->

<div class="member-auth-page">

    <div class="member-auth-card">


        <!-- =================================================
             BRAND PANEL
             ================================================= -->

        <section class="member-brand-panel">

            <div class="member-logo">

                <img
                    src="/fgck_joyland/assets/images/full_gospel_churches_logo.png"
                    alt="FGCK Joyland Church Logo"
                >

            </div>


            <div class="member-brand-line"></div>


            <h1>
                FGCK Joyland
            </h1>


            <p class="brand-description">

                Welcome to your member portal.

                Book pastoral appointments, manage your
                conversations and stay connected with the ministry.

            </p>


            <div class="member-badge">

                <i class="bi bi-people-fill"></i>

                <span>
                    Member Appointment Portal
                </span>

            </div>

        </section>


        <!-- =================================================
             LOGIN PANEL
             ================================================= -->

        <section class="member-login-panel">


            <div class="member-login-heading">

                <span class="eyebrow">
                    Member Portal
                </span>


                <h2>
                    Welcome Back
                </h2>


                <p>
                    Sign in to continue to your FGCK Joyland
                    member dashboard.
                </p>

            </div>


            <?php if ($error): ?>

                <div
                    class="member-login-error"
                    role="alert"
                >

                    <i
                        class="bi bi-exclamation-circle-fill"
                        aria-hidden="true"
                    ></i>

                    <div>

                        <?= e($error) ?>

                    </div>

                </div>

            <?php endif; ?>


            <form
                method="post"
                id="memberLoginForm"
                novalidate
            >

                <input
                    type="hidden"
                    name="csrf"
                    value="<?= e(csrf_token()) ?>"
                >


                <!-- =================================================
                     EMAIL
                     ================================================= -->

                <div class="member-form-group">

                    <label
                        class="member-form-label"
                        for="memberEmail"
                    >

                        Email address

                    </label>


                    <div class="member-input-wrapper">

                        <i
                            class="bi bi-envelope member-input-icon"
                            aria-hidden="true"
                        ></i>


                        <input
                            id="memberEmail"
                            class="member-input"
                            type="email"
                            name="email"
                            placeholder="Enter your email address"
                            autocomplete="email"
                            autocapitalize="none"
                            spellcheck="false"
                            value="<?= e($_POST['email'] ?? '') ?>"
                            required
                        >

                    </div>

                </div>


                <!-- =================================================
                     PASSWORD
                     ================================================= -->

                <div class="member-form-group">

                    <label
                        class="member-form-label"
                        for="memberPassword"
                    >

                        Password

                    </label>


                    <div class="member-input-wrapper">

                        <i
                            class="bi bi-lock member-input-icon"
                            aria-hidden="true"
                        ></i>


                        <input
                            id="memberPassword"
                            class="member-input"
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >


                        <button
                            type="button"
                            class="member-password-toggle"
                            id="memberPasswordToggle"
                            aria-label="Show password"
                            aria-pressed="false"
                        >

                            <i
                                class="bi bi-eye"
                                id="memberPasswordIcon"
                                aria-hidden="true"
                            ></i>

                        </button>

                    </div>

                </div>


                <!-- =================================================
                     LOGIN BUTTON
                     ================================================= -->

                <button
                    type="submit"
                    class="btn member-login-button"
                    id="memberLoginButton"
                >

                    <span id="memberLoginContent">

                        <i class="bi bi-box-arrow-in-right me-2"></i>

                        Sign In Securely

                    </span>

                </button>


                <!-- =================================================
                     SECURITY
                     ================================================= -->

                <div class="member-security">

                    <i class="bi bi-shield-lock-fill"></i>

                    <span>
                        Your account is protected with secure authentication
                    </span>

                </div>

            </form>


            <!-- =================================================
                 REGISTER
                 ================================================= -->

            <div class="member-register">

                <span>
                    New to FGCK Joyland?
                </span>

                <a href="/fgck_joyland/auth/register.php">

                    Create your member account

                </a>

            </div>


            <!-- =================================================
                 BACK TO PORTAL
                 ================================================= -->

            <div class="member-back">

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
                width:65px;
                height:65px;
                margin:0 auto 15px;
                border-radius:50%;
                display:flex;
                align-items:center;
                justify-content:center;
                background:#eaf2ff;
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

                FGCK Joyland

            </strong>


            <p style="
                margin:0 0 5px;
                color:#66758a;
                font-size:14px;
            ">

                You have signed in successfully.

            </p>


            <small style="
                color:#9aa6b5;
                font-size:12px;
            ">

                Opening your Member Dashboard...

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

        popup:
            'joyland-member-login-popup'

    }

}).then(() => {

    window.location.href =
        '/fgck_joyland/member/dashboard.php';

});


/*
 * Backup redirect.
 */

setTimeout(() => {

    window.location.href =
        '/fgck_joyland/member/dashboard.php';

}, 2800);

</script>

<?php endif; ?>


<script>

/* =========================================================
   PASSWORD SHOW / HIDE
   ========================================================= */

const memberPassword =
    document.getElementById('memberPassword');

const memberPasswordToggle =
    document.getElementById('memberPasswordToggle');

const memberPasswordIcon =
    document.getElementById('memberPasswordIcon');


if (
    memberPassword &&
    memberPasswordToggle
) {

    memberPasswordToggle.addEventListener(
        'click',
        function () {

            const isHidden =
                memberPassword.type === 'password';


            memberPassword.type =
                isHidden
                    ? 'text'
                    : 'password';


            memberPasswordIcon.className =
                isHidden
                    ? 'bi bi-eye-slash'
                    : 'bi bi-eye';


            memberPasswordToggle.setAttribute(
                'aria-label',
                isHidden
                    ? 'Hide password'
                    : 'Show password'
            );


            memberPasswordToggle.setAttribute(
                'aria-pressed',
                isHidden
                    ? 'true'
                    : 'false'
            );

        }
    );

}


/* =========================================================
   PREVENT DOUBLE SUBMISSION
   ========================================================= */

const memberLoginForm =
    document.getElementById('memberLoginForm');

const memberLoginButton =
    document.getElementById('memberLoginButton');

const memberLoginContent =
    document.getElementById('memberLoginContent');


if (memberLoginForm) {

    memberLoginForm.addEventListener(
        'submit',
        function (event) {

            if (
                memberLoginButton &&
                memberLoginForm.checkValidity()
            ) {

                memberLoginButton.disabled = true;


                if (memberLoginContent) {

                    memberLoginContent.innerHTML = `

                        <span
                            class="spinner-border
                                   spinner-border-sm
                                   me-2"
                            role="status"
                            aria-hidden="true">
                        </span>

                        Signing In...

                    `;

                }

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

        const email =
            document.getElementById('memberEmail');

        if (
            email &&
            !email.value
        ) {

            email.focus();

        }

    }
);

</script>


<?php require __DIR__ . '/../includes/footer.php'; ?>