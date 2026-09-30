
<?php

require_once __DIR__ . '/../includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Redirect Already Logged-In Members
|--------------------------------------------------------------------------
*/

if (!empty($_SESSION['member_id'])) {

    redirect('/fgck_joyland/member/dashboard.php');

}


/*
|--------------------------------------------------------------------------
| Variables
|--------------------------------------------------------------------------
*/

$error = '';

$registration_success = false;

$membership_no = '';


/*
|--------------------------------------------------------------------------
| Registration
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();


    $n   = trim($_POST['full_name'] ?? '');
    $p   = trim($_POST['phone'] ?? '');
    $em  = trim($_POST['email'] ?? '');
    $g   = trim($_POST['gender'] ?? '');
    $pw  = $_POST['password'] ?? '';
    $cpw = $_POST['confirm_password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        strlen($n) < 3 ||
        strlen($p) < 7 ||
        !filter_var($em, FILTER_VALIDATE_EMAIL) ||
        strlen($pw) < 8 ||
        !preg_match('/[A-Za-z]/', $pw) ||
        !preg_match('/[0-9]/', $pw)
    ) {

        $error =
            'Please provide valid registration details. ' .
            'Your password must contain at least 8 characters, ' .
            'including at least one letter and one number.';

    }


    elseif ($pw !== $cpw) {

        $error =
            'The passwords do not match. Please check and try again.';

    }


    else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Generate Membership Number
            |--------------------------------------------------------------------------
            */

            $mem =
                'FGCK-' .
                date('Y') .
                '-' .
                strtoupper(
                    bin2hex(random_bytes(3))
                );


            /*
            |--------------------------------------------------------------------------
            | Create Member
            |--------------------------------------------------------------------------
            */

            $q = $pdo->prepare(
                'INSERT INTO members
                (
                    membership_no,
                    full_name,
                    phone,
                    email,
                    gender,
                    password_hash
                )
                VALUES (?, ?, ?, ?, ?, ?)'
            );


            $q->execute([

                $mem,

                $n,

                $p,

                $em,

                $g,

                password_hash(
                    $pw,
                    PASSWORD_DEFAULT
                )

            ]);


            /*
            |--------------------------------------------------------------------------
            | Secure Session
            |--------------------------------------------------------------------------
            */

            session_regenerate_id(true);


            $_SESSION['member_id'] =
                (int) $pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | Membership Number
            |--------------------------------------------------------------------------
            */

            $membership_no =
                $mem;


            /*
            |--------------------------------------------------------------------------
            | Activity Log
            |--------------------------------------------------------------------------
            */

            log_activity(

                $pdo,

                $_SESSION['member_id'],

                null,

                'registration',

                'Member account created'

            );


            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            $registration_success =
                true;

        }


        catch (PDOException $e) {

            if ($e->getCode() === '23000') {

                $error =
                    'That email address or phone number is already registered. ' .
                    'Please use different details or sign in to your existing account.';

            }

            else {

                $error =
                    'Registration could not be completed at this time. ' .
                    'Please try again.';

            }

        }

    }

}


$page_title =
    'Create Member Account';


require __DIR__ . '/../includes/header.php';

?>


<!-- =========================================================
     SWEETALERT
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/sweetalert2@11"
></script>


<style>

/* =========================================================
   FGCK JOYLAND
   BLUE PREMIUM MEMBER REGISTRATION
========================================================= */


:root {

    --joy-blue: #1261a0;

    --joy-blue-dark: #071a33;

    --joy-blue-deep: #06152b;

    --joy-blue-light: #1683d8;

    --joy-sky: #5db7f5;

    --joy-sky-soft: #eaf6ff;

    --joy-gold: #f4c95d;

    --joy-gold-dark: #d9a92e;

    --joy-gold-light: #ffe7a3;

    --joy-text: #142235;

    --joy-muted: #718197;

    --joy-border: #dfe8f1;

}


/* =========================================================
   PAGE
========================================================= */

.member-register-page {

    min-height:
        calc(100vh - 70px);

    display: flex;

    justify-content: center;

    align-items: center;

    padding:
        40px 20px;

    position: relative;

    overflow: hidden;

    background:

        radial-gradient(
            circle at 5% 10%,
            rgba(22,131,216,.14),
            transparent 30%
        ),

        radial-gradient(
            circle at 95% 90%,
            rgba(244,201,93,.13),
            transparent 30%
        ),

        linear-gradient(
            135deg,
            #f7fbff,
            #edf5fb
        );
}


/* =========================================================
   DECORATIVE BACKGROUND
========================================================= */

.member-register-page::before {

    content: "";

    position: absolute;

    width: 520px;

    height: 520px;

    border-radius: 50%;

    top: -300px;

    right: -190px;

    border:
        1px solid
        rgba(22,131,216,.10);

    box-shadow:

        0 0 0 80px
        rgba(22,131,216,.018),

        0 0 0 160px
        rgba(22,131,216,.012);

}


.member-register-page::after {

    content: "";

    position: absolute;

    width: 420px;

    height: 420px;

    border-radius: 50%;

    bottom: -260px;

    left: -190px;

    border:
        1px solid
        rgba(244,201,93,.16);

}


/* =========================================================
   MAIN CARD
========================================================= */

.member-register-card {

    width: 100%;

    max-width: 1080px;

    min-height: 660px;

    display: grid;

    grid-template-columns:
        .82fr 1.18fr;

    background:
        rgba(255,255,255,.98);

    border-radius: 30px;

    overflow: hidden;

    position: relative;

    z-index: 2;

    box-shadow:

        0 35px 90px
        rgba(7,26,51,.16),

        0 10px 30px
        rgba(7,26,51,.06);

}


/* =========================================================
   BRAND PANEL
========================================================= */

.register-brand-panel {

    display: flex;

    flex-direction: column;

    justify-content: center;

    align-items: center;

    text-align: center;

    padding:
        50px 40px;

    position: relative;

    color: white;

    overflow: hidden;

    background:

        radial-gradient(
            circle at 15% 15%,
            rgba(93,183,245,.25),
            transparent 30%
        ),

        radial-gradient(
            circle at 90% 90%,
            rgba(244,201,93,.12),
            transparent 30%
        ),

        linear-gradient(
            145deg,
            #06152b,
            #071a33 40%,
            #0b477b 100%
        );
}


/* =========================================================
   BRAND PANEL DECORATIONS
========================================================= */

.register-brand-panel::before {

    content: "";

    position: absolute;

    width: 370px;

    height: 370px;

    border-radius: 50%;

    top: -200px;

    left: -180px;

    border:
        1px solid
        rgba(93,183,245,.18);

}


.register-brand-panel::after {

    content: "";

    position: absolute;

    width: 430px;

    height: 430px;

    border-radius: 50%;

    right: -230px;

    bottom: -240px;

    border:
        1px solid
        rgba(244,201,93,.20);

}


/* =========================================================
   LOGO
========================================================= */

.register-logo {

    width: 122px;

    height: 122px;

    padding: 13px;

    border-radius: 50%;

    background:
        linear-gradient(
            145deg,
            #ffffff,
            #edf6ff
        );

    display: flex;

    align-items: center;

    justify-content: center;

    box-shadow:

        0 18px 45px
        rgba(0,0,0,.25),

        0 0 0 6px
        rgba(93,183,245,.07);

    margin-bottom: 25px;

    position: relative;

    z-index: 2;

}


.register-logo img {

    width: 100%;

    height: 100%;

    object-fit: contain;

}


/* =========================================================
   BRAND HEADING
========================================================= */

.register-brand-panel h1 {

    font-size: 29px;

    font-weight: 800;

    margin:
        0 0 10px;

    position: relative;

    z-index: 2;
}


.register-brand-panel h1::after {

    content: "";

    display: block;

    width: 48px;

    height: 3px;

    margin:
        13px auto 0;

    border-radius: 10px;

    background:
        linear-gradient(
            90deg,
            var(--joy-sky),
            var(--joy-gold)
        );
}


/* =========================================================
   BRAND DESCRIPTION
========================================================= */

.register-brand-description {

    max-width: 310px;

    color:
        rgba(255,255,255,.78);

    font-size: 14px;

    line-height: 1.8;

    margin:
        18px 0 30px;

    position: relative;

    z-index: 2;
}


/* =========================================================
   BENEFITS
========================================================= */

.registration-benefits {

    width: 100%;

    max-width: 310px;

    text-align: left;

    position: relative;

    z-index: 2;
}


.registration-benefit {

    display: flex;

    align-items: center;

    gap: 11px;

    margin-bottom: 13px;

    color:
        rgba(255,255,255,.88);

    font-size: 12px;
}


.registration-benefit-icon {

    width: 32px;

    height: 32px;

    flex:
        0 0 32px;

    border-radius: 9px;

    display: flex;

    align-items: center;

    justify-content: center;

    color:
        var(--joy-gold-light);

    background:
        rgba(244,201,93,.10);

    border:
        1px solid
        rgba(244,201,93,.22);
}


/* =========================================================
   FORM PANEL
========================================================= */

.register-form-panel {

    padding:
        48px 58px;

    display: flex;

    flex-direction: column;

    justify-content: center;

    background:
        rgba(255,255,255,.98);
}


/* =========================================================
   FORM HEADING
========================================================= */

.register-heading {

    margin-bottom: 25px;
}


.register-heading .eyebrow {

    display: inline-block;

    color:
        var(--joy-blue);

    font-size: 11px;

    font-weight: 800;

    letter-spacing: 1.6px;

    text-transform: uppercase;

    margin-bottom: 8px;
}


.register-heading h2 {

    margin:
        0 0 7px;

    color:
        var(--joy-text);

    font-size: 30px;

    font-weight: 800;

    letter-spacing: -.5px;
}


.register-heading p {

    margin: 0;

    color:
        var(--joy-muted);

    font-size: 13px;

    line-height: 1.6;
}


/* =========================================================
   ERROR
========================================================= */

.registration-error {

    display: flex;

    gap: 10px;

    align-items: flex-start;

    padding:
        12px 14px;

    margin-bottom: 20px;

    border-radius: 12px;

    background:
        #fff5f5;

    border:
        1px solid
        #ffd8d8;

    color:
        #b42318;

    font-size: 12px;

    line-height: 1.5;
}


.registration-error i {

    font-size: 17px;
}


/* =========================================================
   FORM FIELD
========================================================= */

.register-field {

    margin-bottom: 17px;
}


.register-label {

    display: block;

    margin-bottom: 7px;

    color:
        #34445b;

    font-size: 12px;

    font-weight: 700;
}


/* =========================================================
   INPUT WRAPPER
========================================================= */

.register-input-wrapper {

    position: relative;
}


/* =========================================================
   INPUT ICON
========================================================= */

.register-input-icon {

    position: absolute;

    left: 15px;

    top: 50%;

    transform:
        translateY(-50%);

    color:
        #8a9bad;

    font-size: 16px;

    pointer-events: none;

    z-index: 2;

    transition:
        color .25s ease;
}


/* =========================================================
   INPUTS
========================================================= */

.register-input,
.register-select {

    width: 100%;

    height: 49px;

    border-radius: 12px;

    border:
        1px solid
        var(--joy-border);

    background:
        #f8fbfe;

    color:
        var(--joy-text);

    font-size: 13px;

    padding-left: 44px;

    transition:
        all .25s ease;
}


.register-select {

    padding-right: 38px;

}


.register-input::placeholder {

    color:
        #a1adba;
}


.register-input:hover,
.register-select:hover {

    border-color:
        #bfd0e0;

    background:
        #ffffff;
}


.register-input:focus,
.register-select:focus {

    outline: none;

    background:
        #ffffff;

    border-color:
        var(--joy-blue-light);

    box-shadow:

        0 0 0 4px
        rgba(22,131,216,.09);
}


.register-input:focus
+ .register-password-toggle {

    color:
        var(--joy-blue);
}


/* =========================================================
   PASSWORD
========================================================= */

.password-input {

    padding-right:
        50px;
}


/* =========================================================
   PASSWORD TOGGLE
========================================================= */

.register-password-toggle {

    position: absolute;

    right: 9px;

    top: 50%;

    transform:
        translateY(-50%);

    width: 34px;

    height: 34px;

    border: 0;

    border-radius: 8px;

    background:
        transparent;

    color:
        #8795a5;

    display: flex;

    align-items: center;

    justify-content: center;

    cursor: pointer;

    transition:
        all .2s ease;
}


.register-password-toggle:hover {

    background:
        var(--joy-sky-soft);

    color:
        var(--joy-blue);
}


/* =========================================================
   PASSWORD STRENGTH
========================================================= */

.password-strength {

    margin-top:
        8px;
}


.password-strength-bar {

    width: 100%;

    height: 4px;

    background:
        #e7edf3;

    border-radius:
        10px;

    overflow:
        hidden;
}


.password-strength-fill {

    width: 0%;

    height: 100%;

    border-radius:
        10px;

    background:
        linear-gradient(
            90deg,
            #1683d8,
            #5db7f5
        );

    transition:
        width .3s ease;
}


.password-strength-text {

    margin-top:
        5px;

    font-size:
        10px;

    color:
        #8a97a6;
}


.password-requirements {

    display:
        flex;

    flex-wrap:
        wrap;

    gap:
        5px 12px;

    margin-top:
        7px;
}


.password-requirement {

    color:
        #8a97a6;

    font-size:
        10px;

    transition:
        color .2s ease;
}


.password-requirement.valid {

    color:
        var(--joy-blue);
}


.password-requirement i {

    margin-right:
        3px;
}


/* =========================================================
   SUBMIT BUTTON
========================================================= */

.register-submit {

    width: 100%;

    height: 52px;

    border: 0;

    border-radius: 12px;

    color:
        white;

    background:

        linear-gradient(
            135deg,
            #1261a0,
            #1683d8
        );

    font-size:
        13px;

    font-weight:
        800;

    box-shadow:

        0 10px 25px
        rgba(18,97,160,.23);

    transition:
        all .25s ease;

    margin-top:
        5px;
}


.register-submit:hover {

    color:
        white;

    transform:
        translateY(-2px);

    background:

        linear-gradient(
            135deg,
            #0e558e,
            #0878c8
        );

    box-shadow:

        0 15px 30px
        rgba(18,97,160,.30);
}


.register-submit:active {

    transform:
        translateY(0);
}


.register-submit:disabled {

    opacity:
        .75;

    cursor:
        not-allowed;

    transform:
        none;
}


/* =========================================================
   LOGIN LINK
========================================================= */

.register-login {

    text-align:
        center;

    margin-top:
        20px;

    padding-top:
        17px;

    border-top:
        1px solid
        #edf1f5;

    color:
        #7b8795;

    font-size:
        12px;
}


.register-login a {

    color:
        var(--joy-blue);

    font-weight:
        800;

    text-decoration:
        none;
}


.register-login a:hover {

    color:
        var(--joy-blue-light);

    text-decoration:
        underline;
}


/* =========================================================
   BACK LINK
========================================================= */

.register-back {

    text-align:
        center;

    margin-top:
        13px;
}


.register-back a {

    color:
        #8a96a4;

    font-size:
        11px;

    font-weight:
        600;

    text-decoration:
        none;

    transition:
        color .2s ease;
}


.register-back a:hover {

    color:
        var(--joy-blue);
}


/* =========================================================
   SWEETALERT
========================================================= */

.joyland-registration-popup {

    border-radius:
        25px !important;

    padding:
        35px !important;

    max-width:
        460px !important;

    box-shadow:

        0 30px 80px
        rgba(7,26,51,.25) !important;
}


.joyland-registration-popup
.swal2-title {

    color:
        #142235 !important;

    font-size:
        25px !important;

    font-weight:
        800 !important;
}


.joyland-registration-popup
.swal2-icon.swal2-success {

    border-color:
        var(--joy-blue) !important;

    color:
        var(--joy-blue) !important;
}


.joyland-registration-popup
.swal2-success-ring {

    border-color:
        rgba(22,131,216,.20) !important;
}


.joyland-registration-popup
.swal2-timer-progress-bar {

    background:

        linear-gradient(
            90deg,
            var(--joy-blue),
            var(--joy-sky),
            var(--joy-gold)
        ) !important;
}


/* =========================================================
   RESPONSIVE TABLET
========================================================= */

@media (max-width: 900px) {

    .member-register-card {

        max-width:
            620px;

        grid-template-columns:
            1fr;

        min-height:
            auto;
    }


    .register-brand-panel {

        padding:
            38px 25px;
    }


    .register-logo {

        width:
            95px;

        height:
            95px;

        margin-bottom:
            18px;
    }


    .register-brand-panel h1 {

        font-size:
            25px;
    }


    .registration-benefits {

        display:
            none;
    }


    .register-form-panel {

        padding:
            40px 34px;
    }

}


/* =========================================================
   RESPONSIVE MOBILE
========================================================= */

@media (max-width: 575px) {

    .member-register-page {

        padding:
            15px 10px;
    }


    .member-register-card {

        border-radius:
            22px;
    }


    .register-brand-panel {

        padding:
            30px 20px;
    }


    .register-logo {

        width:
            85px;

        height:
            85px;

        padding:
            10px;
    }


    .register-brand-panel h1 {

        font-size:
            23px;
    }


    .register-brand-description {

        font-size:
            13px;

        margin-bottom:
            10px;
    }


    .register-form-panel {

        padding:
            30px 20px;
    }


    .register-heading h2 {

        font-size:
            25px;
    }


    .register-heading p {

        font-size:
            12px;
    }

}


/* =========================================================
   SMALL PHONES
========================================================= */

@media (max-width: 380px) {

    .member-register-page {

        padding:
            10px 7px;
    }


    .register-form-panel {

        padding:
            25px 16px;
    }


    .register-brand-panel {

        padding:
            26px 16px;
    }


    .register-heading h2 {

        font-size:
            23px;
    }


    .register-input,
    .register-select {

        height:
            47px;
    }


    .register-submit {

        height:
            50px;
    }

}


/* =========================================================
   REDUCED MOTION
========================================================= */

@media (prefers-reduced-motion: reduce) {

    * {

        transition:
            none !important;
    }

}

</style>


<!-- =========================================================
     REGISTRATION PAGE
========================================================= -->

<div class="member-register-page">


    <div class="member-register-card">


        <!-- =================================================
             BRAND PANEL
        ================================================== -->

        <section
            class="register-brand-panel"
        >


            <!-- LOGO -->

            <div
                class="register-logo"
            >

                <img
                    src="/fgck_joyland/assets/images/full_gospel_churches_logo.png"
                    alt="FGCK Joyland Church Logo"
                >

            </div>


            <!-- TITLE -->

            <h1>

                Join FGCK Joyland

            </h1>


            <!-- DESCRIPTION -->

            <p
                class="register-brand-description"
            >

                Create your member account and stay connected
                with the ministry through our secure digital
                member portal.

            </p>


            <!-- BENEFITS -->

            <div
                class="registration-benefits"
            >


                <div
                    class="registration-benefit"
                >

                    <span
                        class="registration-benefit-icon"
                    >

                        <i
                            class="bi bi-calendar-check"
                        ></i>

                    </span>


                    <span>

                        Book pastoral appointments easily

                    </span>

                </div>


                <div
                    class="registration-benefit"
                >

                    <span
                        class="registration-benefit-icon"
                    >

                        <i
                            class="bi bi-person-heart"
                        ></i>

                    </span>


                    <span>

                        Stay connected with pastoral ministry

                    </span>

                </div>


                <div
                    class="registration-benefit"
                >

                    <span
                        class="registration-benefit-icon"
                    >

                        <i
                            class="bi bi-shield-check"
                        ></i>

                    </span>


                    <span>

                        Your account is securely protected

                    </span>

                </div>


                <div
                    class="registration-benefit"
                >

                    <span
                        class="registration-benefit-icon"
                    >

                        <i
                            class="bi bi-stars"
                        ></i>

                    </span>


                    <span>

                        Experience a connected church community

                    </span>

                </div>

            </div>

        </section>


        <!-- =================================================
             FORM PANEL
        ================================================== -->

        <section
            class="register-form-panel"
        >


            <!-- FORM HEADING -->

            <div
                class="register-heading"
            >

                <span
                    class="eyebrow"
                >

                    Member Registration

                </span>


                <h2>

                    Create Your Account

                </h2>


                <p>

                    Enter your details below to get started
                    with the FGCK Joyland member portal.

                </p>

            </div>


            <!-- ERROR -->

            <?php if ($error): ?>

                <div
                    class="registration-error"
                    role="alert"
                >

                    <i
                        class="bi bi-exclamation-circle-fill"
                    ></i>


                    <div>

                        <?= e($error) ?>

                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 FORM
            ================================================== -->

            <form
                method="post"
                id="registrationForm"
                novalidate
            >


                <input
                    type="hidden"
                    name="csrf"
                    value="<?= e(csrf_token()) ?>"
                >


                <!-- FULL NAME -->

                <div
                    class="register-field"
                >

                    <label
                        class="register-label"
                        for="fullName"
                    >

                        Full Name

                    </label>


                    <div
                        class="register-input-wrapper"
                    >

                        <i
                            class="bi bi-person register-input-icon"
                            aria-hidden="true"
                        ></i>


                        <input
                            id="fullName"
                            class="register-input"
                            name="full_name"
                            type="text"
                            placeholder="Enter your full name"
                            value="<?= e($_POST['full_name'] ?? '') ?>"
                            autocomplete="name"
                            minlength="3"
                            required
                        >

                    </div>

                </div>


                <!-- PHONE + GENDER -->

                <div
                    class="row g-3"
                >


                    <!-- PHONE -->

                    <div
                        class="col-md-6"
                    >

                        <div
                            class="register-field"
                        >

                            <label
                                class="register-label"
                                for="phone"
                            >

                                Phone Number

                            </label>


                            <div
                                class="register-input-wrapper"
                            >

                                <i
                                    class="bi bi-telephone register-input-icon"
                                    aria-hidden="true"
                                ></i>


                                <input
                                    id="phone"
                                    class="register-input"
                                    name="phone"
                                    type="tel"
                                    placeholder="e.g. 0712345678"
                                    value="<?= e($_POST['phone'] ?? '') ?>"
                                    autocomplete="tel"
                                    minlength="7"
                                    required
                                >

                            </div>

                        </div>

                    </div>


                    <!-- GENDER -->

                    <div
                        class="col-md-6"
                    >

                        <div
                            class="register-field"
                        >

                            <label
                                class="register-label"
                                for="gender"
                            >

                                Gender

                            </label>


                            <div
                                class="register-input-wrapper"
                            >

                                <i
                                    class="bi bi-people register-input-icon"
                                    aria-hidden="true"
                                ></i>


                                <select
                                    id="gender"
                                    class="register-select"
                                    name="gender"
                                >

                                    <option value="">
                                        Prefer not to say
                                    </option>


                                    <option
                                        value="Male"
                                        <?= (
                                            ($_POST['gender'] ?? '') === 'Male'
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        Male

                                    </option>


                                    <option
                                        value="Female"
                                        <?= (
                                            ($_POST['gender'] ?? '') === 'Female'
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        Female

                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- EMAIL -->

                <div
                    class="register-field"
                >

                    <label
                        class="register-label"
                        for="email"
                    >

                        Email Address

                    </label>


                    <div
                        class="register-input-wrapper"
                    >

                        <i
                            class="bi bi-envelope register-input-icon"
                            aria-hidden="true"
                        ></i>


                        <input
                            id="email"
                            class="register-input"
                            type="email"
                            name="email"
                            placeholder="Enter your email address"
                            value="<?= e($_POST['email'] ?? '') ?>"
                            autocomplete="email"
                            required
                        >

                    </div>

                </div>


                <!-- PASSWORD -->

                <div
                    class="register-field"
                >

                    <label
                        class="register-label"
                        for="password"
                    >

                        Create Password

                    </label>


                    <div
                        class="register-input-wrapper"
                    >

                        <i
                            class="bi bi-lock register-input-icon"
                            aria-hidden="true"
                        ></i>


                        <input
                            id="password"
                            class="register-input password-input"
                            type="password"
                            name="password"
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="Create a secure password"
                            required
                        >


                        <button
                            type="button"
                            class="register-password-toggle"
                            id="passwordToggle"
                            aria-label="Show password"
                        >

                            <i
                                class="bi bi-eye"
                                id="passwordIcon"
                            ></i>

                        </button>

                    </div>


                    <!-- PASSWORD STRENGTH -->

                    <div
                        class="password-strength"
                    >

                        <div
                            class="password-strength-bar"
                        >

                            <div
                                class="password-strength-fill"
                                id="passwordStrengthFill"
                            ></div>

                        </div>


                        <div
                            class="password-strength-text"
                            id="passwordStrengthText"
                        >

                            Use at least 8 characters,
                            including a letter and a number.

                        </div>


                        <div
                            class="password-requirements"
                        >

                            <span
                                class="password-requirement"
                                id="lengthRequirement"
                            >

                                <i
                                    class="bi bi-circle"
                                ></i>

                                8+ characters

                            </span>


                            <span
                                class="password-requirement"
                                id="letterRequirement"
                            >

                                <i
                                    class="bi bi-circle"
                                ></i>

                                Letter

                            </span>


                            <span
                                class="password-requirement"
                                id="numberRequirement"
                            >

                                <i
                                    class="bi bi-circle"
                                ></i>

                                Number

                            </span>

                        </div>

                    </div>

                </div>


                <!-- CONFIRM PASSWORD -->

                <div
                    class="register-field"
                >

                    <label
                        class="register-label"
                        for="confirmPassword"
                    >

                        Confirm Password

                    </label>


                    <div
                        class="register-input-wrapper"
                    >

                        <i
                            class="bi bi-shield-lock register-input-icon"
                            aria-hidden="true"
                        ></i>


                        <input
                            id="confirmPassword"
                            class="register-input password-input"
                            type="password"
                            name="confirm_password"
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="Confirm your password"
                            required
                        >


                        <button
                            type="button"
                            class="register-password-toggle"
                            id="confirmPasswordToggle"
                            aria-label="Show password"
                        >

                            <i
                                class="bi bi-eye"
                                id="confirmPasswordIcon"
                            ></i>

                        </button>

                    </div>

                </div>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="btn register-submit"
                    id="registerButton"
                >

                    <span
                        id="registerButtonContent"
                    >

                        <i
                            class="bi bi-person-plus-fill me-2"
                        ></i>

                        Create Member Account

                    </span>

                </button>

            </form>


            <!-- LOGIN -->

            <div
                class="register-login"
            >

                Already registered?

                <a
                    href="/fgck_joyland/auth/login.php"
                >

                    Sign in to your account

                </a>

            </div>


            <!-- BACK -->

            <div
                class="register-back"
            >

                <a
                    href="/fgck_joyland/"
                >

                    <i
                        class="bi bi-arrow-left me-1"
                    ></i>

                    Back to FGCK Joyland Portal

                </a>

            </div>

        </section>

    </div>

</div>


<!-- =========================================================
     REGISTRATION SUCCESS
========================================================= -->

<?php if ($registration_success): ?>

<script>

Swal.fire({

    icon: 'success',

    title: 'Welcome to FGCK Joyland!',

    html: `

        <div
            style="
                margin-top:10px;
                line-height:1.7;
            "
        >

            <div
                style="
                    width:70px;
                    height:70px;
                    margin:0 auto 15px;
                    border-radius:50%;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    background:#eaf6ff;
                    color:#1261a0;
                    font-size:29px;
                "
            >

                <i
                    class="bi bi-person-check-fill"
                ></i>

            </div>


            <strong
                style="
                    color:#1261a0;
                    font-size:19px;
                    display:block;
                    margin-bottom:7px;
                "
            >

                Account Created Successfully

            </strong>


            <p
                style="
                    margin:0 0 9px;
                    color:#66768a;
                    font-size:14px;
                "
            >

                Your FGCK Joyland member account
                is ready.

            </p>


            <div
                style="
                    display:inline-block;
                    margin:5px 0 12px;
                    padding:8px 15px;
                    border-radius:9px;
                    background:#f1f8fe;
                    border:1px solid #d9ecfa;
                    color:#1261a0;
                    font-size:13px;
                    font-weight:800;
                "
            >

                Membership No:
                <?= e($membership_no) ?>

            </div>


            <small
                style="
                    color:#9aa8b6;
                    font-size:12px;
                    display:block;
                "
            >

                Opening your Member Dashboard...

            </small>

        </div>

    `,

    showConfirmButton: false,

    timer: 3500,

    timerProgressBar: true,

    allowOutsideClick: false,

    allowEscapeKey: false,

    allowEnterKey: false,

    customClass: {

        popup:
            'joyland-registration-popup'

    }

}).then(() => {

    window.location.href =
        '/fgck_joyland/member/dashboard.php';

});


/*
|--------------------------------------------------------------------------
| Backup Redirect
|--------------------------------------------------------------------------
*/

setTimeout(() => {

    window.location.href =
        '/fgck_joyland/member/dashboard.php';

}, 3800);

</script>

<?php endif; ?>


<script>

/* =========================================================
   PASSWORD VISIBILITY
========================================================= */

function setupPasswordToggle(
    inputId,
    buttonId,
    iconId
) {

    const input =
        document.getElementById(inputId);

    const button =
        document.getElementById(buttonId);

    const icon =
        document.getElementById(iconId);


    if (
        !input ||
        !button ||
        !icon
    ) {

        return;

    }


    button.addEventListener(
        'click',
        function () {

            const hidden =
                input.type === 'password';


            input.type =
                hidden
                    ? 'text'
                    : 'password';


            icon.className =
                hidden
                    ? 'bi bi-eye-slash'
                    : 'bi bi-eye';


            button.setAttribute(
                'aria-label',
                hidden
                    ? 'Hide password'
                    : 'Show password'
            );

        }
    );

}


setupPasswordToggle(
    'password',
    'passwordToggle',
    'passwordIcon'
);


setupPasswordToggle(
    'confirmPassword',
    'confirmPasswordToggle',
    'confirmPasswordIcon'
);


/* =========================================================
   PASSWORD STRENGTH
========================================================= */

const password =
    document.getElementById(
        'password'
    );


const strengthFill =
    document.getElementById(
        'passwordStrengthFill'
    );


const strengthText =
    document.getElementById(
        'passwordStrengthText'
    );


const lengthRequirement =
    document.getElementById(
        'lengthRequirement'
    );


const letterRequirement =
    document.getElementById(
        'letterRequirement'
    );


const numberRequirement =
    document.getElementById(
        'numberRequirement'
    );


function updateRequirement(
    element,
    valid
) {

    if (!element) {

        return;

    }


    const icon =
        element.querySelector('i');


    if (valid) {

        element.classList.add(
            'valid'
        );


        if (icon) {

            icon.className =
                'bi bi-check-circle-fill';

        }

    }

    else {

        element.classList.remove(
            'valid'
        );


        if (icon) {

            icon.className =
                'bi bi-circle';

        }

    }

}


if (password) {

    password.addEventListener(
        'input',
        function () {

            const value =
                password.value;


            const hasLength =
                value.length >= 8;


            const hasLetter =
                /[A-Za-z]/.test(value);


            const hasNumber =
                /[0-9]/.test(value);


            updateRequirement(
                lengthRequirement,
                hasLength
            );


            updateRequirement(
                letterRequirement,
                hasLetter
            );


            updateRequirement(
                numberRequirement,
                hasNumber
            );


            let score = 0;


            if (hasLength) {

                score++;

            }


            if (hasLetter) {

                score++;

            }


            if (hasNumber) {

                score++;

            }


            if (value.length >= 12) {

                score++;

            }


            const percentages = [
                0,
                25,
                50,
                75,
                100
            ];


            strengthFill.style.width =
                percentages[score] + '%';


            if (!value) {

                strengthText.textContent =
                    'Use at least 8 characters, including a letter and a number.';

            }

            else if (score <= 1) {

                strengthText.textContent =
                    'Password is weak.';

            }

            else if (score === 2) {

                strengthText.textContent =
                    'Password is getting stronger.';

            }

            else if (score === 3) {

                strengthText.textContent =
                    'Good password.';

            }

            else {

                strengthText.textContent =
                    'Strong password.';

            }

        }
    );

}


/* =========================================================
   CONFIRM PASSWORD CHECK
========================================================= */

const confirmPassword =
    document.getElementById(
        'confirmPassword'
    );


if (
    confirmPassword &&
    password
) {

    confirmPassword.addEventListener(
        'input',
        function () {

            if (
                confirmPassword.value &&
                password.value ===
                confirmPassword.value
            ) {

                confirmPassword.style.borderColor =
                    '#1683d8';

            }

            else if (
                confirmPassword.value
            ) {

                confirmPassword.style.borderColor =
                    '#dc3545';

            }

            else {

                confirmPassword.style.borderColor =
                    '';

            }

        }
    );

}


/* =========================================================
   PREVENT DOUBLE SUBMISSION
========================================================= */

const registrationForm =
    document.getElementById(
        'registrationForm'
    );


const registerButton =
    document.getElementById(
        'registerButton'
    );


const registerButtonContent =
    document.getElementById(
        'registerButtonContent'
    );


if (registrationForm) {

    registrationForm.addEventListener(
        'submit',
        function (event) {

            /*
            |--------------------------------------------------------------------------
            | Let browser validation handle empty/invalid fields
            |--------------------------------------------------------------------------
            */

            if (
                !registrationForm.checkValidity()
            ) {

                return;

            }


            /*
            |--------------------------------------------------------------------------
            | Check Password Match
            |--------------------------------------------------------------------------
            */

            if (
                password &&
                confirmPassword &&
                password.value !==
                confirmPassword.value
            ) {

                event.preventDefault();


                Swal.fire({

                    icon: 'warning',

                    title: 'Passwords Do Not Match',

                    text:
                        'Please make sure both password fields contain the same password.',

                    confirmButtonText:
                        'Check Passwords',

                    confirmButtonColor:
                        '#1261a0'

                });


                return;

            }


            /*
            |--------------------------------------------------------------------------
            | Disable Button
            |--------------------------------------------------------------------------
            */

            if (registerButton) {

                registerButton.disabled =
                    true;

            }


            /*
            |--------------------------------------------------------------------------
            | Loading State
            |--------------------------------------------------------------------------
            */

            if (registerButtonContent) {

                registerButtonContent.innerHTML = `

                    <span
                        class="spinner-border
                               spinner-border-sm
                               me-2"
                        role="status"
                        aria-hidden="true">
                    </span>

                    Creating Account...

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

        const fullName =
            document.getElementById(
                'fullName'
            );


        if (
            fullName &&
            !fullName.value
        ) {

            fullName.focus();

        }

    }
);

</script>


<?php

require __DIR__ . '/../includes/footer.php';

?>
