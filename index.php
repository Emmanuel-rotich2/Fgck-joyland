
<?php
require_once __DIR__ . '/includes/bootstrap.php';
?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, maximum-scale=1"
    >

    <meta
        name="description"
        content="FGCK Makutano West Joyland Pastor Appointment System. Book a private 30-minute appointment with the pastor."
    >

    <meta
        name="theme-color"
        content="#071a33"
    >

    <title>FGCK Joyland | Pastor Appointment</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         MAIN APPLICATION CSS
    ====================================================== -->

    <link
        href="/fgck_joyland/assets/css/app.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         PAGE STYLES
    ====================================================== -->

    <style>

        /* =====================================================
           COLOR SYSTEM
        ====================================================== */

        :root {

            --primary: #1683d8;
            --primary-dark: #0d5fa8;
            --primary-light: #5db7f5;

            --blue-deep: #071a33;
            --blue-dark: #0a2747;
            --blue-mid: #0f4778;
            --blue-soft: #eaf6ff;

            --gold: #f4c95d;
            --gold-dark: #d9a92e;
            --gold-light: #ffe7a3;

            --white: #ffffff;
            --ivory: #f8fbff;

            --text-light: #e9f5ff;
            --text-muted: #b8cee0;

            --glass: rgba(255,255,255,.075);
            --glass-hover: rgba(255,255,255,.12);

            --border: rgba(255,255,255,.14);
        }


        /* =====================================================
           GLOBAL
        ====================================================== */

        * {
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body.landing {

            margin: 0;

            min-height: 100vh;

            color: var(--white);

            background:

                radial-gradient(
                    circle at 15% 20%,
                    rgba(22,131,216,.34),
                    transparent 32%
                ),

                radial-gradient(
                    circle at 85% 25%,
                    rgba(244,201,93,.14),
                    transparent 30%
                ),

                radial-gradient(
                    circle at 50% 100%,
                    rgba(15,71,120,.30),
                    transparent 40%
                ),

                linear-gradient(
                    135deg,
                    rgba(4,20,39,.97),
                    rgba(7,26,51,.94)
                ),

                url('/fgck_joyland/assets/images/church-background.jpg')
                center center / cover fixed no-repeat;

            overflow-x: hidden;
        }


        body.landing::before {

            content: "";

            position: fixed;

            inset: 0;

            pointer-events: none;

            background:
                linear-gradient(
                    180deg,
                    rgba(3,17,34,.15),
                    rgba(3,17,34,.38)
                );

            z-index: -1;
        }


        a {
            text-decoration: none;
        }


        /* =====================================================
           NAVIGATION
        ====================================================== */

        .landing-nav {

            position: sticky;

            top: 0;

            z-index: 1050;

            padding: 13px 0;

            background:

                linear-gradient(
                    90deg,
                    rgba(5,25,48,.98),
                    rgba(9,49,86,.96)
                );

            backdrop-filter: blur(20px);

            -webkit-backdrop-filter: blur(20px);

            border-bottom:
                1px solid rgba(93,183,245,.18);

            box-shadow:
                0 8px 35px rgba(0,0,0,.28);
        }


        .brand-wrapper {
            min-width: 0;
        }


        .brand-logo {

            width: 49px;

            height: 49px;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            background:
                linear-gradient(
                    145deg,
                    #ffffff,
                    #edf6ff
                );

            border:
                2px solid rgba(244,201,93,.65);

            border-radius: 14px;

            overflow: hidden;

            box-shadow:

                0 7px 25px rgba(0,0,0,.28),

                0 0 0 3px
                rgba(93,183,245,.07);
        }


        .brand-logo img {

            width: 100%;

            height: 100%;

            object-fit: contain;

            padding: 4px;
        }


        .brand-name {

            color: #ffffff !important;

            font-size: 15px;

            font-weight: 800;

            line-height: 1.2;
        }


        .brand-subtitle {

            margin-top: 3px;

            color: var(--gold-light);

            font-size: 11px;

            font-weight: 500;
        }


        /* =====================================================
           DESKTOP NAVIGATION BUTTONS
        ====================================================== */

        .nav-actions {

            display: flex;

            align-items: center;

            gap: 9px;
        }


        .nav-actions .btn {

            min-height: 40px;

            padding: 7px 15px;

            border-radius: 10px;

            font-weight: 700;

            white-space: nowrap;

            transition:
                all .25s ease;
        }


        /* MEMBER LOGIN */

        .nav-actions .btn-outline-light {

            color: #eaf5ff;

            border-color:
                rgba(255,255,255,.30);

            background:
                rgba(255,255,255,.035);
        }


        .nav-actions .btn-outline-light:hover {

            color: #ffffff;

            background:
                rgba(22,131,216,.18);

            border-color:
                rgba(93,183,245,.65);

            transform:
                translateY(-2px);

            box-shadow:
                0 8px 22px rgba(22,131,216,.12);
        }


        /* REGISTER */

        .nav-actions .btn-success {

            background:
                linear-gradient(
                    135deg,
                    #1683d8,
                    #0d5fa8
                );

            border: none;

            box-shadow:
                0 7px 20px
                rgba(22,131,216,.25);
        }


        .nav-actions .btn-success:hover {

            background:
                linear-gradient(
                    135deg,
                    #2795e8,
                    #0870c7
                );

            transform:
                translateY(-2px);

            box-shadow:
                0 10px 28px
                rgba(22,131,216,.38);
        }


        /* PASTOR LOGIN */

        .nav-actions .btn-warning {

            color: #2c2107;

            background:
                linear-gradient(
                    135deg,
                    #ffe39a,
                    #f4c95d
                );

            border: none;

            box-shadow:
                0 7px 22px
                rgba(244,201,93,.20);
        }


        .nav-actions .btn-warning:hover {

            color: #1c1605;

            background:
                linear-gradient(
                    135deg,
                    #ffeab0,
                    #eab83f
                );

            transform:
                translateY(-2px);

            box-shadow:
                0 10px 28px
                rgba(244,201,93,.30);
        }


        /* =====================================================
           HERO
        ====================================================== */

        .hero {

            min-height:
                calc(100vh - 75px);

            display: flex;

            flex-direction: column;

            justify-content: center;

            padding-top: 75px;

            padding-bottom: 75px;

            position: relative;
        }


        /* LEFT BLUE GLOW */

        .hero::before {

            content: "";

            position: absolute;

            width: 500px;

            height: 500px;

            left: -280px;

            top: 80px;

            background:

                radial-gradient(
                    circle,
                    rgba(22,131,216,.28),
                    transparent 70%
                );

            pointer-events: none;
        }


        /* RIGHT GOLD/BLUE GLOW */

        .hero::after {

            content: "";

            position: absolute;

            width: 450px;

            height: 450px;

            right: -250px;

            bottom: 50px;

            background:

                radial-gradient(
                    circle,
                    rgba(93,183,245,.15),
                    transparent 70%
                );

            pointer-events: none;
        }


        .hero-content {

            position: relative;

            z-index: 2;
        }


        /* =====================================================
           HERO BADGE
        ====================================================== */

        .hero-badge {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            margin:
                0 auto 24px;

            padding:
                10px 18px;

            color: #123252;

            background:

                linear-gradient(
                    135deg,
                    #eaf6ff,
                    #bfe5ff
                );

            border:
                1px solid
                rgba(93,183,245,.65);

            border-radius: 999px;

            font-size: 12px;

            font-weight: 800;

            box-shadow:

                0 10px 35px
                rgba(0,0,0,.20),

                0 0 30px
                rgba(22,131,216,.10);
        }


        .hero-badge i {

            color:
                #0d70bb;
        }


        /* =====================================================
           HERO HEADING
        ====================================================== */

        .hero h1 {

            max-width: 950px;

            margin:
                0 auto 22px;

            color: #ffffff;

            font-size:
                clamp(2.2rem, 6vw, 4.5rem);

            line-height: 1.05;

            letter-spacing: -2px;

            text-shadow:
                0 8px 35px
                rgba(0,0,0,.35);
        }


        .hero h1 .highlight {

            color:
                var(--primary-light);

            text-shadow:
                0 0 30px
                rgba(93,183,245,.20);
        }


        /* =====================================================
           HERO DESCRIPTION
        ====================================================== */

        .hero-description {

            max-width: 710px;

            margin:
                0 auto;

            color:
                var(--text-muted);

            font-size:
                clamp(15px, 2vw, 18px);

            line-height: 1.8;
        }


        /* =====================================================
           HERO BUTTONS
        ====================================================== */

        .hero-buttons {

            margin-top: 32px;

            display: flex;

            justify-content: center;

            align-items: center;

            gap: 13px;

            flex-wrap: wrap;
        }


        .hero-buttons .btn {

            min-height: 54px;

            padding:
                14px 25px;

            border-radius: 13px;

            font-weight: 800;

            transition:

                transform .25s ease,

                box-shadow .25s ease,

                background .25s ease;
        }


        .hero-buttons .btn:hover {

            transform:
                translateY(-3px);
        }


        /* MAIN CTA */

        .hero-buttons .btn-warning {

            color: #302207;

            background:

                linear-gradient(
                    135deg,
                    #ffe9a8 0%,
                    #f4c95d 50%,
                    #e9b83e 100%
                );

            border: none;

            box-shadow:
                0 12px 35px
                rgba(244,201,93,.22);
        }


        .hero-buttons .btn-warning:hover {

            color: #211804;

            background:

                linear-gradient(
                    135deg,
                    #fff0bd,
                    #f6cf67
                );

            box-shadow:
                0 16px 42px
                rgba(244,201,93,.32);
        }


        /* LOGIN BUTTON */

        .hero-buttons .btn-outline-light {

            color:
                #e9f5ff;

            background:
                rgba(255,255,255,.045);

            border:
                1px solid
                rgba(255,255,255,.28);

            backdrop-filter:
                blur(10px);
        }


        .hero-buttons .btn-outline-light:hover {

            color: #ffffff;

            background:
                rgba(22,131,216,.20);

            border-color:
                rgba(93,183,245,.70);

            box-shadow:
                0 10px 30px
                rgba(22,131,216,.18);
        }


        /* =====================================================
           FEATURE SECTION
        ====================================================== */

        .features {

            margin-top: 75px;
        }


        .feature {

            height: 100%;

            padding: 27px;

            background:

                linear-gradient(
                    145deg,
                    rgba(255,255,255,.09),
                    rgba(22,131,216,.035)
                );

            backdrop-filter:
                blur(18px);

            -webkit-backdrop-filter:
                blur(18px);

            border:
                1px solid
                rgba(255,255,255,.12);

            border-radius: 20px;

            box-shadow:
                0 18px 45px
                rgba(0,0,0,.20);

            transition:

                transform .3s ease,

                background .3s ease,

                border-color .3s ease,

                box-shadow .3s ease;
        }


        .feature:hover {

            transform:
                translateY(-8px);

            background:

                linear-gradient(
                    145deg,
                    rgba(255,255,255,.13),
                    rgba(22,131,216,.11)
                );

            border-color:
                rgba(93,183,245,.40);

            box-shadow:
                0 25px 55px
                rgba(0,0,0,.28);
        }


        /* FEATURE ICON */

        .feature-icon {

            width: 52px;

            height: 52px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 17px;

            color:
                #bfe5ff;

            background:

                linear-gradient(
                    145deg,
                    rgba(22,131,216,.28),
                    rgba(93,183,245,.08)
                );

            border:
                1px solid
                rgba(93,183,245,.30);

            border-radius: 15px;

            font-size: 23px;

            box-shadow:
                0 8px 22px
                rgba(22,131,216,.12);
        }


        .feature h3 {

            margin-bottom: 9px;

            color:
                #ffffff;

            font-size: 17px;
        }


        .feature p {

            margin: 0;

            color:
                var(--text-muted);

            font-size: 14px;

            line-height: 1.7;
        }


        /* =====================================================
           TRUST LINE
        ====================================================== */

        .trust-line {

            margin-top: 32px;

            color:
                #9eb8ca;

            font-size: 13px;
        }


        .trust-line i {

            color:
                var(--primary-light);
        }


        /* =====================================================
           FOOTER
        ====================================================== */

        .landing-footer {

            padding:
                28px 15px;

            color:
                #91a9ba;

            text-align:
                center;

            font-size:
                13px;

            background:

                linear-gradient(
                    180deg,
                    rgba(5,25,48,.78),
                    rgba(3,16,31,.96)
                );

            border-top:
                1px solid
                rgba(93,183,245,.15);
        }


        .landing-footer strong {

            color:
                var(--gold);

            font-weight:
                800;
        }


        /* =====================================================
           MOBILE MENU BUTTON
        ====================================================== */

        .mobile-menu-button {

            display: none;
        }


        .mobile-menu-button .btn {

            border-radius: 11px;

            border-color:
                rgba(93,183,245,.38);

            background:
                rgba(255,255,255,.04);

            color: #ffffff;
        }


        .mobile-menu-button .btn:hover {

            background:
                rgba(22,131,216,.14);

            border-color:
                rgba(93,183,245,.65);
        }


        /* =====================================================
           OFFCANVAS
        ====================================================== */

        .offcanvas {

            background:

                linear-gradient(
                    160deg,
                    #071a33,
                    #0a2747 55%,
                    #0f4778
                ) !important;
        }


        .offcanvas-header {

            border-color:
                rgba(93,183,245,.16) !important;
        }


        .offcanvas-title {

            color:
                #ffffff;
        }


        .offcanvas .btn {

            min-height:
                53px;

            border-radius:
                13px;

            font-weight:
                800;
        }


        /* =====================================================
           MOBILE INFO CARD
        ====================================================== */

        .mobile-info-card {

            padding:
                19px;

            background:
                rgba(255,255,255,.055);

            border:
                1px solid
                rgba(255,255,255,.10);

            border-radius:
                15px;
        }


        /* =====================================================
           TABLET
        ====================================================== */

        @media (max-width: 991.98px) {

            .desktop-nav {

                display:
                    none !important;
            }


            .mobile-menu-button {

                display:
                    block;
            }


            .hero {

                padding-top:
                    58px;

                padding-bottom:
                    60px;
            }


            .features {

                margin-top:
                    55px;
            }
        }


        /* =====================================================
           MOBILE
        ====================================================== */

        @media (max-width: 575.98px) {

            body.landing {

                background-attachment:
                    scroll;
            }


            .landing-nav {

                padding:
                    10px 0;
            }


            .brand-logo {

                width:
                    42px;

                height:
                    42px;

                border-radius:
                    11px;
            }


            .brand-name {

                max-width:
                    210px;

                overflow:
                    hidden;

                text-overflow:
                    ellipsis;

                font-size:
                    13px;
            }


            .brand-subtitle {

                font-size:
                    9px;
            }


            .hero {

                min-height:
                    auto;

                padding-top:
                    48px;

                padding-bottom:
                    48px;
            }


            .hero-badge {

                max-width:
                    100%;

                padding:
                    8px 12px;

                font-size:
                    10px;
            }


            .hero h1 {

                font-size:
                    2.15rem;

                line-height:
                    1.12;

                letter-spacing:
                    -.9px;
            }


            .hero-description {

                font-size:
                    14px;

                line-height:
                    1.7;
            }


            .hero-buttons {

                flex-direction:
                    column;

                width:
                    100%;
            }


            .hero-buttons .btn {

                width:
                    100%;

                max-width:
                    360px;
            }


            .features {

                margin-top:
                    38px;
            }


            .feature {

                padding:
                    21px;

                border-radius:
                    16px;
            }


            .trust-line {

                font-size:
                    11px;

                line-height:
                    1.6;
            }
        }


        /* =====================================================
           SMALL PHONES
        ====================================================== */

        @media (max-width: 380px) {

            .brand-name {

                max-width:
                    165px;

                font-size:
                    12px;
            }


            .brand-subtitle {

                display:
                    none;
            }


            .brand-logo {

                width:
                    38px;

                height:
                    38px;
            }


            .hero h1 {

                font-size:
                    1.9rem;
            }


            .hero-description {

                font-size:
                    13px;
            }


            .hero-badge {

                font-size:
                    9px;
            }
        }


        /* =====================================================
           REDUCED MOTION
        ====================================================== */

        @media (prefers-reduced-motion: reduce) {

            html {

                scroll-behavior:
                    auto;
            }


            *,
            *::before,
            *::after {

                transition:
                    none !important;

                animation:
                    none !important;
            }
        }

    </style>

</head>


<body class="landing">


<!-- =========================================================
     NAVIGATION
========================================================= -->

<nav class="landing-nav">

    <div class="container">

        <div
            class="d-flex justify-content-between align-items-center"
        >


            <!-- BRAND -->

            <div
                class="d-flex align-items-center gap-2 brand-wrapper"
            >

                <div class="brand-logo">

                    <img
                        src="/fgck_joyland/assets/images/full_gospel_churches_logo.png"
                        alt="FGCK Makutano West Joyland Church Logo"
                    >

                </div>


                <div>

                    <div class="brand-name">

                        FGCK Makutano West Joyland

                    </div>


                    <div class="brand-subtitle">

                        Pastor Appointment System

                    </div>

                </div>

            </div>


            <!-- DESKTOP ACTIONS -->

            <div
                class="nav-actions desktop-nav"
            >


                <a
                    href="/fgck_joyland/auth/login.php"
                    class="btn btn-outline-light btn-sm"
                >

                    <i
                        class="bi bi-box-arrow-in-right me-1"
                    ></i>

                    Sign In

                </a>


                <a
                    href="/fgck_joyland/auth/register.php"
                    class="btn btn-success btn-sm"
                >

                    <i
                        class="bi bi-person-plus me-1"
                    ></i>

                    Register

                </a>


                <a
                    href="/fgck_joyland/staff/login.php"
                    class="btn btn-warning btn-sm"
                >

                    <i
                        class="bi bi-shield-lock me-1"
                    ></i>

                    Pastor Login

                </a>

            </div>


            <!-- MOBILE MENU -->

            <div
                class="mobile-menu-button"
            >

                <button
                    class="btn btn-outline-light"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#mobileMenu"
                    aria-controls="mobileMenu"
                    aria-label="Open navigation menu"
                >

                    <i
                        class="bi bi-list fs-5"
                    ></i>

                </button>

            </div>

        </div>

    </div>

</nav>



<!-- =========================================================
     MOBILE OFFCANVAS MENU
========================================================= -->

<div
    class="offcanvas offcanvas-end text-bg-dark"
    tabindex="-1"
    id="mobileMenu"
    aria-labelledby="mobileMenuLabel"
>


    <div
        class="offcanvas-header border-bottom border-secondary"
    >

        <h5
            class="offcanvas-title fw-bold"
            id="mobileMenuLabel"
        >

            <i
                class="bi bi-calendar-check text-warning me-2"
            ></i>

            FGCK Joyland

        </h5>


        <button
            type="button"
            class="btn-close btn-close-white"
            data-bs-dismiss="offcanvas"
            aria-label="Close"
        ></button>

    </div>


    <div
        class="offcanvas-body"
    >


        <!-- MOBILE BUTTONS -->

        <div
            class="d-grid gap-3"
        >


            <a
                href="/fgck_joyland/auth/login.php"
                class="btn btn-outline-light btn-lg"
            >

                <i
                    class="bi bi-box-arrow-in-right me-2"
                ></i>

                Member Sign In

            </a>


            <a
                href="/fgck_joyland/auth/register.php"
                class="btn btn-success btn-lg"
            >

                <i
                    class="bi bi-person-plus me-2"
                ></i>

                Create Member Account

            </a>


            <a
                href="/fgck_joyland/staff/login.php"
                class="btn btn-warning btn-lg"
            >

                <i
                    class="bi bi-shield-lock me-2"
                ></i>

                Pastor Login

            </a>

        </div>


        <hr
            class="border-secondary my-4"
        >


        <!-- INFORMATION CARD -->

        <div
            class="mobile-info-card"
        >

            <div
                class="d-flex gap-3"
            >

                <div>

                    <i
                        class="bi bi-calendar2-check text-warning fs-4"
                    ></i>

                </div>


                <div>

                    <div
                        class="fw-bold text-white mb-1"
                    >

                        Pastor Appointments

                    </div>


                    <div
                        class="text-secondary small"
                    >

                        Book a private 30-minute appointment
                        through the FGCK Joyland member portal.

                    </div>

                </div>

            </div>

        </div>


        <!-- SECOND INFORMATION CARD -->

        <div
            class="mobile-info-card mt-3"
        >

            <div
                class="d-flex gap-3"
            >

                <div>

                    <i
                        class="bi bi-shield-check text-info fs-4"
                    ></i>

                </div>


                <div>

                    <div
                        class="fw-bold text-white mb-1"
                    >

                        Private & Secure

                    </div>


                    <div
                        class="text-secondary small"
                    >

                        Your appointment details are protected
                        within your member account.

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main>


    <!-- =====================================================
         HERO SECTION
    ====================================================== -->

    <section
        class="hero container text-center"
    >

        <div
            class="hero-content"
        >


            <!-- AVAILABILITY BADGE -->

            <div
                class="hero-badge"
            >

                <i
                    class="bi bi-calendar-check me-2"
                ></i>

                EVERY WEDNESDAY
                •
                9:00 AM – 3:00 PM

            </div>


            <!-- MAIN HEADING -->

            <h1
                class="fw-bold"
            >

                Meet the Pastor with

                <span
                    class="highlight"
                >

                    purpose,

                </span>

                peace and convenience.

            </h1>


            <!-- DESCRIPTION -->

            <p
                class="hero-description"
            >

                Book a private 30-minute appointment with the pastor
                through a simple, secure and organized church member portal.

            </p>


            <!-- ACTION BUTTONS -->

            <div
                class="hero-buttons"
            >


                <a
                    href="/fgck_joyland/auth/register.php"
                    class="btn btn-warning btn-lg"
                >

                    <i
                        class="bi bi-calendar-plus me-2"
                    ></i>

                    Book an Appointment

                </a>


                <a
                    href="/fgck_joyland/auth/login.php"
                    class="btn btn-outline-light btn-lg"
                >

                    <i
                        class="bi bi-box-arrow-in-right me-2"
                    ></i>

                    Member Login

                </a>

            </div>


            <!-- =================================================
                 FEATURES
            ================================================== -->

            <div
                class="row g-3 g-md-4 features text-start"
            >


                <!-- FEATURE 1 -->

                <div
                    class="col-12 col-md-4"
                >

                    <div
                        class="feature"
                    >

                        <div
                            class="feature-icon"
                        >

                            <i
                                class="bi bi-clock-history"
                            ></i>

                        </div>


                        <h3
                            class="fw-bold"
                        >

                            30-Minute Sessions

                        </h3>


                        <p>

                            Every booking receives one dedicated
                            30-minute appointment slot with the pastor.

                        </p>

                    </div>

                </div>


                <!-- FEATURE 2 -->

                <div
                    class="col-12 col-md-4"
                >

                    <div
                        class="feature"
                    >

                        <div
                            class="feature-icon"
                        >

                            <i
                                class="bi bi-calendar2-week"
                            ></i>

                        </div>


                        <h3
                            class="fw-bold"
                        >

                            Wednesday Appointments

                        </h3>


                        <p>

                            Members can select available appointment
                            slots opened by the pastor.

                        </p>

                    </div>

                </div>


                <!-- FEATURE 3 -->

                <div
                    class="col-12 col-md-4"
                >

                    <div
                        class="feature"
                    >

                        <div
                            class="feature-icon"
                        >

                            <i
                                class="bi bi-shield-check"
                            ></i>

                        </div>


                        <h3
                            class="fw-bold"
                        >

                            Private & Secure

                        </h3>


                        <p>

                            Your appointment information remains
                            securely within your member account.

                        </p>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 TRUST LINE
            ================================================== -->

            <div
                class="trust-line"
            >

                <i
                    class="bi bi-check-circle-fill me-1"
                ></i>

                Simple booking

                <span class="mx-1">•</span>

                Private conversations

                <span class="mx-1">•</span>

                Organized appointments

            </div>

        </div>

    </section>

</main>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer
    class="landing-footer"
>

    <div
        class="container"
    >

        © 2026 FGCK Joyland. All rights reserved.

        <br>

        Powered by

        <strong>
            @MylesHubTechnologies
        </strong>

    </div>

</footer>



<!-- =========================================================
     BOOTSTRAP JAVASCRIPT
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>