```php
<?php

require __DIR__ . '/../includes/bootstrap.php';

staff_required();

$msg   = '';
$error = '';

$keys = [
    'church_name',
    'opening_time',
    'office_status',
    'booking_notice'
];

/*
|--------------------------------------------------------------------------
| Handle Settings Update
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $values = [];

    foreach ($keys as $k) {
        $values[$k] = trim((string)($_POST[$k] ?? ''));
    }

    /*
    |--------------------------------------------------------------------------
    | Fixed closing time
    |--------------------------------------------------------------------------
    */
    $values['closing_time'] = '15:00';

    /*
    |--------------------------------------------------------------------------
    | Validate opening time
    |--------------------------------------------------------------------------
    */
    if (!preg_match('/^\d{2}:\d{2}$/', $values['opening_time'])) {

        $error = 'Please enter a valid opening time.';

    } else {

        [$hour, $minute] = array_map('intval', explode(':', $values['opening_time']));

        $openingMinutes = ($hour * 60) + $minute;
        $latestOpening  = (14 * 60) + 30; // 2:30 PM
        $earliestOpening = (9 * 60);      // 9:00 AM

        if ($openingMinutes < $earliestOpening) {

            $error = 'Opening time cannot be earlier than 9:00 AM.';

        } elseif ($openingMinutes > $latestOpening) {

            $error = 'Opening time must be between 9:00 AM and 2:30 PM.';

        } else {

            try {

                $pdo->beginTransaction();

                $save = $pdo->prepare("
                    INSERT INTO settings(setting_key, setting_value)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value)
                ");

                foreach ($keys as $k) {
                    $save->execute([
                        $k,
                        $values[$k]
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Always keep closing time at 3:00 PM
                |--------------------------------------------------------------------------
                */
                $save->execute([
                    'closing_time',
                    '15:00'
                ]);

                /*
                |--------------------------------------------------------------------------
                | Synchronize future Wednesday appointment slots
                |--------------------------------------------------------------------------
                */
                sync_future_wednesday_slots($pdo, 52);

                /*
                |--------------------------------------------------------------------------
                | Remove legacy arrival-time setting
                |--------------------------------------------------------------------------
                */
                $pdo->exec("
                    DELETE FROM settings
                    WHERE setting_key = 'arrival_time'
                ");

                $pdo->commit();

                $msg = 'Settings saved successfully. Future appointment slots have been updated automatically.';

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = 'The settings could not be saved. Please try again.';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Load Current Settings
|--------------------------------------------------------------------------
*/
$v = [];

foreach ($keys as $k) {
    $v[$k] = setting($pdo, $k);
}

$page_title = 'Settings';

require __DIR__ . '/../includes/header.php';

?>

<div class="settings-page">

    <?php require __DIR__ . '/../includes/staff_sidebar.php'; ?>

    <main class="main">

        <!-- =========================================================
             TOP BAR
        ========================================================== -->
       

        <!-- =========================================================
             CONTENT
        ========================================================== -->
        <div class="content">

            <div class="settings-container">

                <!-- =================================================
                     PAGE INTRODUCTION
                ================================================== -->
                <div class="settings-intro">

                    <div class="intro-icon">
                        <i class="bi bi-sliders"></i>
                    </div>

                    <div>
                        <h2>Appointment Settings</h2>

                        <p>
                            Manage the church office availability and appointment
                            booking configuration from one place.
                        </p>
                    </div>

                </div>


                <!-- =================================================
                     ALERTS
                ================================================== -->

                <?php if ($msg): ?>

                    <div class="settings-alert success-alert">

                        <div class="alert-icon">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>

                        <div>
                            <strong>Settings Updated</strong>

                            <div>
                                <?= e($msg) ?>
                            </div>
                        </div>

                    </div>

                <?php endif; ?>


                <?php if ($error): ?>

                    <div class="settings-alert error-alert">

                        <div class="alert-icon">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>

                        <div>
                            <strong>Unable to Save</strong>

                            <div>
                                <?= e($error) ?>
                            </div>
                        </div>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     MAIN SETTINGS CARD
                ================================================== -->
                <div class="settings-card">

                    <div class="card-header-custom">

                        <div>

                            <span class="section-label">
                                GENERAL CONFIGURATION
                            </span>

                            <h3>
                                Office & Appointment Configuration
                            </h3>

                            <p>
                                Changes made here automatically affect future
                                appointment availability.
                            </p>

                        </div>

                        <div class="status-badge">
                            <span class="status-dot"></span>
                            System Active
                        </div>

                    </div>


                    <form method="post" class="settings-form">

                        <input
                            type="hidden"
                            name="csrf"
                            value="<?= e(csrf_token()) ?>"
                        >


                        <!-- =============================================
                             BASIC INFORMATION
                        ============================================== -->

                        <div class="form-section">

                            <div class="section-title">

                                <div class="section-title-icon">
                                    <i class="bi bi-building"></i>
                                </div>

                                <div>
                                    <h4>Church Information</h4>
                                    <p>Basic information displayed across the system.</p>
                                </div>

                            </div>


                            <div class="row g-3">

                                <!-- Church Name -->
                                <div class="col-12 col-lg-7">

                                    <label class="form-label" for="church_name">
                                        Church Name
                                    </label>

                                    <div class="input-wrapper">

                                        <i class="bi bi-building"></i>

                                        <input
                                            type="text"
                                            id="church_name"
                                            name="church_name"
                                            class="form-control"
                                            value="<?= e($v['church_name']) ?>"
                                            placeholder="Enter church name"
                                            autocomplete="organization"
                                            required
                                        >

                                    </div>

                                </div>


                                <!-- Office Status -->
                                <div class="col-12 col-lg-5">

                                    <label class="form-label" for="office_status">
                                        Office Status
                                    </label>

                                    <div class="input-wrapper select-wrapper">

                                        <i class="bi bi-door-open"></i>

                                        <select
                                            id="office_status"
                                            name="office_status"
                                            class="form-select"
                                        >

                                            <option
                                                value="available"
                                                <?= $v['office_status'] === 'available' ? 'selected' : '' ?>
                                            >
                                                Available
                                            </option>

                                            <option
                                                value="unavailable"
                                                <?= $v['office_status'] === 'unavailable' ? 'selected' : '' ?>
                                            >
                                                Unavailable / Closed
                                            </option>

                                        </select>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- =============================================
                             OFFICE HOURS
                        ============================================== -->

                        <div class="form-section">

                            <div class="section-title">

                                <div class="section-title-icon">
                                    <i class="bi bi-clock-history"></i>
                                </div>

                                <div>
                                    <h4>Office Hours</h4>

                                    <p>
                                        Set the time when appointment bookings begin.
                                    </p>

                                </div>

                            </div>


                            <div class="row g-3">

                                <!-- Opening Time -->
                                <div class="col-12 col-md-6">

                                    <label class="form-label" for="opening_time">
                                        Opening Time
                                    </label>

                                    <div class="input-wrapper">

                                        <i class="bi bi-clock"></i>

                                        <input
                                            type="time"
                                            id="opening_time"
                                            name="opening_time"
                                            class="form-control"
                                            value="<?= e($v['opening_time']) ?>"
                                            min="09:00"
                                            max="14:30"
                                            step="1800"
                                            required
                                        >

                                    </div>

                                    <div class="form-help">

                                        <i class="bi bi-info-circle"></i>

                                        Opening time must be between
                                        <strong>9:00 AM</strong> and
                                        <strong>2:30 PM</strong>.

                                    </div>

                                </div>


                                <!-- Closing Time -->
                                <div class="col-12 col-md-6">

                                    <label class="form-label">
                                        Closing Time
                                    </label>

                                    <div class="input-wrapper">

                                        <i class="bi bi-clock-fill"></i>

                                        <input
                                            type="time"
                                            class="form-control"
                                            value="15:00"
                                            disabled
                                        >

                                    </div>

                                    <div class="form-help fixed-time">

                                        <i class="bi bi-lock-fill"></i>

                                        Fixed closing time:
                                        <strong>3:00 PM</strong>

                                    </div>

                                </div>

                            </div>


                            <!-- Appointment Info -->
                            <div class="schedule-info">

                                <div class="schedule-icon">
                                    <i class="bi bi-calendar-check"></i>
                                </div>

                                <div>

                                    <strong>
                                        Automatic Appointment Synchronization
                                    </strong>

                                    <p>
                                        When the opening time changes, future
                                        Wednesday appointment slots will automatically
                                        be regenerated using the new schedule.
                                        Each appointment remains 30 minutes.
                                    </p>

                                </div>

                            </div>

                        </div>


                        <!-- =============================================
                             BOOKING NOTICE
                        ============================================== -->

                        <div class="form-section">

                            <div class="section-title">

                                <div class="section-title-icon">
                                    <i class="bi bi-megaphone"></i>
                                </div>

                                <div>

                                    <h4>Booking Notice</h4>

                                    <p>
                                        Message displayed to members when booking
                                        an appointment.
                                    </p>

                                </div>

                            </div>


                            <label
                                class="form-label"
                                for="booking_notice"
                            >
                                Member Notice
                            </label>

                            <textarea
                                id="booking_notice"
                                name="booking_notice"
                                class="form-control booking-textarea"
                                rows="5"
                                maxlength="1000"
                                placeholder="Enter an important notice for members..."
                            ><?= e($v['booking_notice']) ?></textarea>

                            <div class="form-help">

                                <i class="bi bi-lightbulb"></i>

                                Keep the message short, clear and helpful.

                            </div>

                        </div>


                        <!-- =============================================
                             ACTIONS
                        ============================================== -->

                        <div class="form-actions">

                            <div class="save-information">

                                <i class="bi bi-shield-check"></i>

                                <span>
                                    Your settings are securely saved.
                                </span>

                            </div>

                            <button
                                type="submit"
                                class="save-btn"
                            >

                                <i class="bi bi-check2-circle"></i>

                                <span>Save Settings</span>

                            </button>

                        </div>

                    </form>

                </div>


                <!-- =================================================
                     QUICK INFORMATION CARDS
                ================================================== -->

                <div class="info-grid">

                    <div class="info-card">

                        <div class="info-card-icon">
                            <i class="bi bi-calendar-week"></i>
                        </div>

                        <div>
                            <span>Appointment Day</span>
                            <strong>Wednesday</strong>
                        </div>

                    </div>


                    <div class="info-card">

                        <div class="info-card-icon">
                            <i class="bi bi-stopwatch"></i>
                        </div>

                        <div>
                            <span>Appointment Duration</span>
                            <strong>30 Minutes</strong>
                        </div>

                    </div>


                    <div class="info-card">

                        <div class="info-card-icon">
                            <i class="bi bi-clock"></i>
                        </div>

                        <div>
                            <span>Office Closing</span>
                            <strong>3:00 PM</strong>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>


<!-- ================================================================
     RESPONSIVE SETTINGS STYLES
================================================================ -->

<style>

    /* ---------------------------------------------------------------
       Base
    --------------------------------------------------------------- */

    .settings-page {
        min-height: 100vh;
    }

    .main {
        min-width: 0;
    }

    .topbar {
        min-height: 82px;
        display: flex;
        align-items: center;
        padding: 18px 28px;
        background: #fff;
        border-bottom: 1px solid #e9ecef;
    }

    .topbar-left {
        display: flex;
        align-items: center;
        width: 100%;
        gap: 16px;
    }

    .page-heading {
        display: flex;
        align-items: center;
        gap: 13px;
        min-width: 0;
    }

    .page-heading h1 {
        margin: 0;
        font-size: 24px;
        font-weight: 750;
        color: #172033;
        line-height: 1.2;
    }

    .page-heading p {
        margin: 5px 0 0;
        color: #7a8496;
        font-size: 13px;
    }

    .heading-icon {
        width: 46px;
        height: 46px;
        min-width: 46px;
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(13, 110, 253, .10);
        color: #0d6efd;
        font-size: 20px;
    }


    /* ---------------------------------------------------------------
       Content
    --------------------------------------------------------------- */

    .content {
        padding: 30px;
        background: #f6f8fb;
        min-height: calc(100vh - 82px);
    }

    .settings-container {
        width: 100%;
        max-width: 1100px;
        margin: 0 auto;
    }


    /* ---------------------------------------------------------------
       Introduction
    --------------------------------------------------------------- */

    .settings-intro {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }

    .intro-icon {
        width: 52px;
        height: 52px;
        min-width: 52px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        background: #fff;
        color: #0d6efd;
        box-shadow: 0 5px 20px rgba(20, 30, 50, .06);
        font-size: 22px;
    }

    .settings-intro h2 {
        margin: 0;
        font-size: 20px;
        font-weight: 750;
        color: #172033;
    }

    .settings-intro p {
        margin: 4px 0 0;
        font-size: 13px;
        color: #7a8496;
    }


    /* ---------------------------------------------------------------
       Alerts
    --------------------------------------------------------------- */

    .settings-alert {
        display: flex;
        align-items: flex-start;
        gap: 13px;
        padding: 15px 17px;
        border-radius: 12px;
        margin-bottom: 18px;
        font-size: 13px;
    }

    .settings-alert strong {
        display: block;
        margin-bottom: 3px;
    }

    .alert-icon {
        font-size: 20px;
        line-height: 1;
    }

    .success-alert {
        background: #ecfdf3;
        border: 1px solid #c8f0d8;
        color: #18794e;
    }

    .error-alert {
        background: #fff1f2;
        border: 1px solid #ffd0d4;
        color: #b4232d;
    }


    /* ---------------------------------------------------------------
       Main Card
    --------------------------------------------------------------- */

    .settings-card {
        background: #fff;
        border: 1px solid #e7ebf1;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 8px 35px rgba(30, 45, 70, .06);
    }

    .card-header-custom {
        padding: 25px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        border-bottom: 1px solid #edf0f4;
    }

    .section-label {
        display: block;
        margin-bottom: 5px;
        color: #0d6efd;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .9px;
    }

    .card-header-custom h3 {
        margin: 0;
        font-size: 18px;
        color: #172033;
        font-weight: 750;
    }

    .card-header-custom p {
        margin: 5px 0 0;
        color: #7a8496;
        font-size: 13px;
    }

    .status-badge {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 12px;
        border-radius: 50px;
        background: #ecfdf3;
        color: #18794e;
        font-size: 12px;
        font-weight: 700;
    }

    .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #20a464;
    }


    /* ---------------------------------------------------------------
       Form
    --------------------------------------------------------------- */

    .settings-form {
        padding: 0 28px;
    }

    .form-section {
        padding: 28px 0;
        border-bottom: 1px solid #edf0f4;
    }

    .section-title {
        display: flex;
        gap: 12px;
        margin-bottom: 22px;
    }

    .section-title-icon {
        width: 38px;
        height: 38px;
        min-width: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f6ff;
        color: #0d6efd;
        font-size: 16px;
    }

    .section-title h4 {
        margin: 0;
        font-size: 15px;
        font-weight: 750;
        color: #172033;
    }

    .section-title p {
        margin: 3px 0 0;
        color: #8992a3;
        font-size: 12px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        color: #303949;
        font-size: 13px;
        font-weight: 700;
    }

    .input-wrapper {
        position: relative;
    }

    .input-wrapper > i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 2;
        color: #8a94a6;
        pointer-events: none;
    }

    .input-wrapper .form-control,
    .input-wrapper .form-select {
        padding-left: 42px;
        min-height: 48px;
        border-radius: 10px;
        border: 1px solid #dfe4eb;
        font-size: 14px;
        box-shadow: none;
    }

    .input-wrapper .form-control:focus,
    .input-wrapper .form-select:focus,
    .booking-textarea:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, .08);
    }

    .form-help {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        margin-top: 7px;
        color: #8a94a6;
        font-size: 11px;
        line-height: 1.5;
    }

    .form-help i {
        margin-top: 1px;
    }

    .fixed-time {
        color: #18794e;
    }


    /* ---------------------------------------------------------------
       Schedule Information
    --------------------------------------------------------------- */

    .schedule-info {
        display: flex;
        gap: 13px;
        margin-top: 22px;
        padding: 15px;
        border-radius: 12px;
        background: #f5f9ff;
        border: 1px solid #dceaff;
    }

    .schedule-icon {
        width: 35px;
        height: 35px;
        min-width: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: #e5f0ff;
        color: #0d6efd;
    }

    .schedule-info strong {
        display: block;
        margin-bottom: 3px;
        font-size: 12px;
        color: #234;
    }

    .schedule-info p {
        margin: 0;
        color: #657085;
        font-size: 11px;
        line-height: 1.55;
    }


    /* ---------------------------------------------------------------
       Textarea
    --------------------------------------------------------------- */

    .booking-textarea {
        width: 100%;
        border: 1px solid #dfe4eb;
        border-radius: 10px;
        resize: vertical;
        min-height: 120px;
        padding: 13px 14px;
        font-size: 14px;
        box-shadow: none;
    }


    /* ---------------------------------------------------------------
       Actions
    --------------------------------------------------------------- */

    .form-actions {
        padding: 22px 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .save-information {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #7d8798;
        font-size: 11px;
    }

    .save-information i {
        color: #20a464;
        font-size: 15px;
    }

    .save-btn {
        border: 0;
        min-height: 46px;
        padding: 0 20px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: #0d6efd;
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: .2s ease;
    }

    .save-btn:hover {
        background: #0b5ed7;
        transform: translateY(-1px);
        box-shadow: 0 7px 18px rgba(13, 110, 253, .20);
    }

    .save-btn:active {
        transform: translateY(0);
    }


    /* ---------------------------------------------------------------
       Information Cards
    --------------------------------------------------------------- */

    .info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-top: 18px;
    }

    .info-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 17px;
        background: #fff;
        border: 1px solid #e7ebf1;
        border-radius: 14px;
        box-shadow: 0 5px 20px rgba(20, 30, 50, .04);
    }

    .info-card-icon {
        width: 40px;
        height: 40px;
        min-width: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #f1f6ff;
        color: #0d6efd;
        font-size: 17px;
    }

    .info-card span {
        display: block;
        color: #8a94a6;
        font-size: 10px;
        margin-bottom: 2px;
    }

    .info-card strong {
        color: #172033;
        font-size: 13px;
    }


    /* ---------------------------------------------------------------
       Tablet
    --------------------------------------------------------------- */

    @media (max-width: 991.98px) {

        .content {
            padding: 22px;
        }

        .settings-form {
            padding: 0 22px;
        }

        .card-header-custom {
            padding: 22px;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }

    }


    /* ---------------------------------------------------------------
       Mobile
    --------------------------------------------------------------- */

    @media (max-width: 767.98px) {

        .topbar {
            min-height: 70px;
            padding: 13px 15px;
        }

        .topbar-left {
            gap: 10px;
        }

        .page-heading {
            gap: 9px;
        }

        .heading-icon {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 10px;
            font-size: 17px;
        }

        .page-heading h1 {
            font-size: 18px;
        }

        .page-heading p {
            display: none;
        }

        .content {
            padding: 15px;
        }

        .settings-intro {
            align-items: flex-start;
        }

        .intro-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 12px;
        }

        .settings-intro h2 {
            font-size: 17px;
        }

        .settings-intro p {
            font-size: 11px;
        }

        .settings-card {
            border-radius: 14px;
        }

        .card-header-custom {
            padding: 19px 17px;
            align-items: flex-start;
            flex-direction: column;
        }

        .card-header-custom h3 {
            font-size: 16px;
        }

        .status-badge {
            font-size: 10px;
        }

        .settings-form {
            padding: 0 17px;
        }

        .form-section {
            padding: 22px 0;
        }

        .section-title {
            margin-bottom: 17px;
        }

        .section-title-icon {
            width: 34px;
            height: 34px;
            min-width: 34px;
        }

        .section-title h4 {
            font-size: 14px;
        }

        .section-title p {
            font-size: 11px;
        }

        .input-wrapper .form-control,
        .input-wrapper .form-select {
            min-height: 46px;
            font-size: 13px;
        }

        .schedule-info {
            padding: 12px;
        }

        .form-actions {
            flex-direction: column-reverse;
            align-items: stretch;
        }

        .save-information {
            justify-content: center;
        }

        .save-btn {
            width: 100%;
        }

        .info-grid {
            gap: 10px;
        }

        .info-card {
            padding: 14px;
        }

    }


    /* ---------------------------------------------------------------
       Small Mobile
    --------------------------------------------------------------- */

    @media (max-width: 420px) {

        .content {
            padding: 10px;
        }

        .page-heading h1 {
            font-size: 16px;
        }

        .settings-intro {
            margin-bottom: 14px;
        }

        .settings-intro h2 {
            font-size: 15px;
        }

        .card-header-custom {
            padding: 16px;
        }

        .settings-form {
            padding: 0 15px;
        }

        .form-section {
            padding: 19px 0;
        }

        .schedule-info {
            flex-direction: column;
        }

        .schedule-icon {
            width: 32px;
            height: 32px;
        }

        .info-card {
            padding: 12px;
        }

    }


    /* ---------------------------------------------------------------
       Prevent horizontal overflow
    --------------------------------------------------------------- */

    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .settings-page,
    .main,
    .content,
    .settings-container {
        max-width: 100%;
    }

</style>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.querySelector('.settings-form');
    const openingTime = document.getElementById('opening_time');

    if (!form || !openingTime) {
        return;
    }

    form.addEventListener('submit', function (event) {

        const value = openingTime.value;

        if (!value) {
            return;
        }

        const parts = value.split(':');

        const hour = parseInt(parts[0], 10);
        const minute = parseInt(parts[1], 10);

        const totalMinutes = (hour * 60) + minute;

        const minimum = 9 * 60;
        const maximum = (14 * 60) + 30;

        if (totalMinutes < minimum || totalMinutes > maximum) {

            event.preventDefault();

            alert(
                'Please select an opening time between 9:00 AM and 2:30 PM.'
            );

            openingTime.focus();
        }

    });

});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
