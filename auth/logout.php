<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Clear all session data
$_SESSION = [];

// Destroy the session
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Logged Out | FGCK Joyland</title>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>

<script>

Swal.fire({

    icon: 'success',

    title: 'Logged Out Successfully',

    html: `
        <div style="margin-top:10px;">

            <strong
                style="
                    color:#2563eb;
                    font-size:20px;
                    display:block;
                    margin-bottom:8px;
                "
            >
                FGCK Joyland
            </strong>

            <p
                style="
                    margin:0 0 6px;
                    color:#66758a;
                    font-size:15px;
                "
            >
                You have been safely logged out of your account.
            </p>

            <small style="color:#94a0b1;">
                Redirecting to the home page...
            </small>

        </div>
    `,

    showConfirmButton: false,

    timer: 3000,

    timerProgressBar: true,

    allowOutsideClick: false,

    allowEscapeKey: false,

    allowEnterKey: false,

    customClass: {
        popup: 'joyland-logout-popup'
    }

}).then(() => {

    window.location.href =
        '/fgck_joyland/';

});


/*
 * Backup redirect after 3 seconds.
 */
setTimeout(() => {

    window.location.href =
        '/fgck_joyland/';

}, 3000);

</script>


<style>

/* =========================================
   FGCK JOYLAND LOGOUT ALERT
   BLUE THEME
   ========================================= */

.joyland-logout-popup {

    border-radius: 22px !important;

    padding: 2rem !important;

    box-shadow:
        0 20px 60px rgba(15, 42, 85, 0.18) !important;

    max-width: 430px !important;

}


/* SweetAlert title */

.joyland-logout-popup .swal2-title {

    font-weight: 700 !important;

    font-size: 25px !important;

    color: #172033 !important;

}


/* Blue success icon */

.joyland-logout-popup
.swal2-icon.swal2-success {

    border-color: #2563eb !important;

    color: #2563eb !important;

}


/* Success icon ring */

.joyland-logout-popup
.swal2-success-ring {

    border-color:
        rgba(37, 99, 235, 0.25) !important;

}


/* Blue timer */

.joyland-logout-popup
.swal2-timer-progress-bar {

    background: #2563eb !important;

}

</style>

</body>
</html>