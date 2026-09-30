<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

if (
    empty($_SESSION['password_reset_verified']) ||
    empty($_SESSION['password_reset_user_id'])
) {
    redirect('/fgck_joyland/staff/forgot_password.php');
}

$userId = (int)$_SESSION['password_reset_user_id'];
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (strlen($password) < 8) {
        $error = 'Your password must contain at least 8 characters.';
    } elseif (!preg_match('/\d/', $password)) {
        $error = 'Your password must contain at least one number.';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = 'Your password must contain at least one special character.';
    } elseif ($password !== $confirm) {
        $error = 'The passwords do not match.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE id = ? AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Your password reset session is invalid.';
        } else {
            $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);

            $update = $pdo->prepare("
                UPDATE users
                SET password_hash = ?,
                    reset_code_hash = NULL,
                    reset_code_expires_at = NULL,
                    reset_attempts = 0
                WHERE id = ?
            ");
            $update->execute([$newPasswordHash, $userId]);

            log_activity(
                $pdo,
                null,
                $userId,
                'password_reset',
                'Pastor password reset successfully'
            );

            unset(
                $_SESSION['password_reset_verified'],
                $_SESSION['password_reset_user_id'],
                $_SESSION['password_reset_method'],
                $_SESSION['password_reset_identifier'],
                $_SESSION['password_reset_sent_at']
            );

            $success = true;
        }
    }
}

$page_title = 'Create New Password';

require __DIR__ . '/../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>

.reset-page {
    min-height:calc(100vh - 70px);

    display:flex;
    align-items:center;
    justify-content:center;

    padding:30px 15px;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(8,116,67,.12),
            transparent 30%
        ),
        radial-gradient(
            circle at 90% 90%,
            rgba(217,164,65,.12),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #f8fbf9,
            #eef5f1
        );
}

.reset-card {
    width:100%;
    max-width:520px;

    background:#fff;

    padding:45px;

    border-radius:26px;

    box-shadow:
        0 25px 70px rgba(16,57,38,.14);
}

.reset-icon {
    width:80px;
    height:80px;

    display:flex;
    align-items:center;
    justify-content:center;

    margin:0 auto 25px;

    border-radius:50%;

    background:#edf8f2;

    color:#087443;

    font-size:30px;
}

.reset-heading {
    text-align:center;
    margin-bottom:30px;
}

.reset-heading h1 {
    font-size:28px;
    font-weight:800;
    color:#17221c;
}

.reset-heading p {
    color:#718078;
    font-size:14px;
}

.form-label {
    font-size:13px;
    font-weight:700;
    color:#34443b;
}

.password-wrapper {
    position:relative;
}

.password-wrapper input {
    height:54px;
    border-radius:13px;
    padding-right:50px;
    background:#f9fbfa;
    border:1px solid #e5ebe7;
}

.password-wrapper input:focus {
    border-color:#087443;
    box-shadow:0 0 0 4px rgba(8,116,67,.09);
}

.toggle-password {
    position:absolute;

    right:10px;
    top:50%;

    transform:translateY(-50%);

    border:0;
    background:transparent;

    width:40px;
    height:40px;

    color:#84928a;
}

.save-password {
    height:54px;

    border:0;

    border-radius:13px;

    background:
        linear-gradient(
            135deg,
            #087443,
            #0b8f55
        );

    color:#fff;

    font-weight:800;

    margin-top:10px;

    box-shadow:
        0 10px 22px rgba(8,116,67,.22);
}

.save-password:hover {
    color:#fff;
    transform:translateY(-2px);
}

.password-rules {
    margin-top:8px;
    font-size:11px;
    color:#8a968f;
}

</style>

<div class="reset-page">

    <div class="reset-card">

        <div class="reset-icon">

            <i class="bi bi-key-fill"></i>

        </div>

        <div class="reset-heading">

            <h1>
                Create New Password
            </h1>

            <p>
                Your verification was successful.
                Create a secure password for your pastor account.
            </p>

        </div>

        <?php if ($error): ?>

            <div class="alert alert-danger">

                <i class="bi bi-exclamation-circle-fill me-2"></i>

                <?= e($error) ?>

            </div>

        <?php endif; ?>

        <form method="post" id="resetPasswordForm">

            <input
                type="hidden"
                name="csrf"
                value="<?= e(csrf_token()) ?>"
            >

            <div class="mb-4">

                <label
                    class="form-label"
                    for="password"
                >
                    New Password
                </label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter new password"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        data-target="password"
                    >
                        <i class="bi bi-eye"></i>
                    </button>

                </div>

                <div class="password-rules">
                    Use at least 8 characters.
                </div>

            </div>

            <div class="mb-4">

                <label
                    class="form-label"
                    for="confirm_password"
                >
                    Confirm New Password
                </label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        placeholder="Confirm new password"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        data-target="confirm_password"
                    >
                        <i class="bi bi-eye"></i>
                    </button>

                </div>

            </div>

            <button
                type="submit"
                class="btn save-password w-100"
            >

                <i class="bi bi-check-circle-fill me-2"></i>

                Update Password

            </button>

        </form>

    </div>

</div>

<?php if ($success): ?>

<script>

Swal.fire({

    icon: 'success',

    title: 'Password Updated!',

    html: `
        <div style="line-height:1.7">

            <div style="
                width:64px;
                height:64px;
                margin:0 auto 15px;
                border-radius:50%;
                display:flex;
                align-items:center;
                justify-content:center;
                background:#edf8f2;
                color:#087443;
                font-size:28px;
            ">
                <i class="bi bi-shield-check"></i>
            </div>

            <strong style="
                display:block;
                color:#087443;
                font-size:18px;
                margin-bottom:7px;
            ">
                Password changed successfully
            </strong>

            <p style="
                margin:0;
                color:#718078;
                font-size:13px;
            ">
                You can now sign in using your new password.
            </p>

        </div>
    `,

    confirmButtonText: 'Continue to Login',

    confirmButtonColor: '#087443',

    allowOutsideClick: false,

    customClass: {
        popup: 'rounded-4'
    }

}).then(() => {

    window.location.href =
        '/fgck_joyland/staff/login.php';

});

</script>

<?php endif; ?>

<script>

document.querySelectorAll('.toggle-password')
.forEach(function(button) {

    button.addEventListener('click', function() {

        const targetId =
            this.getAttribute('data-target');

        const input =
            document.getElementById(targetId);

        const icon =
            this.querySelector('i');

        if (input.type === 'password') {

            input.type = 'text';

            icon.className =
                'bi bi-eye-slash';

        } else {

            input.type = 'password';

            icon.className =
                'bi bi-eye';

        }

    });

});

</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>