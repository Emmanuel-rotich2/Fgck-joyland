<?php
require __DIR__.'/../includes/bootstrap.php';
staff_required();

$staff = current_staff($pdo);
if (!$staff) {
    unset($_SESSION['staff_id']);
    redirect('/fgck_joyland/staff/login.php');
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($current === '' || $new === '' || $confirm === '') {
        $error = 'Please complete all password fields.';
    } elseif (!password_verify($current, $staff['password_hash'])) {
        $error = 'Your current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $error = 'The new password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Za-z]/', $new)) {
        $error = 'The new password must contain at least one letter.';
    } elseif (!preg_match('/\d/', $new)) {
        $error = 'The new password must contain at least one number.';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $new)) {
        $error = 'The new password must contain at least one special character.';
    } elseif (hash_equals($current, $new)) {
        $error = 'The new password must be different from your current password.';
    } elseif (!hash_equals($new, $confirm)) {
        $error = 'The new password and confirmation do not match.';
    } else {
        try {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $q = $pdo->prepare('UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=? AND status=\'active\'');
            $q->execute([$hash, (int)$staff['id']]);

            if ($q->rowCount() !== 1) {
                throw new RuntimeException('Password update did not affect the staff account.');
            }

            log_activity($pdo, null, (int)$staff['id'], 'staff_password_changed', 'Staff password changed successfully');
            $msg = 'Your password has been changed successfully. Your current login session remains active.';
        } catch (Throwable $e) {
            $error = 'The password could not be changed. Please try again.';
        }
    }
}

$page_title = 'Change Password';
require __DIR__.'/../includes/header.php';
?>
<div>
    <?php require __DIR__.'/../includes/staff_sidebar.php'; ?>

    <main class="main">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button>
                <div>
                    <h1>Change Password</h1>
                    <p>Update your Pastor Portal password securely.</p>
                </div>
            </div>
        </header>

        <div class="content">
            <div class="cardx p-4" style="max-width:720px">
                <?php if ($msg): ?>
                    <div class="alert alert-success small">
                        <i class="bi bi-check-circle me-1"></i><?=e($msg)?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger small">
                        <i class="bi bi-exclamation-triangle me-1"></i><?=e($error)?>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info small">
                    <i class="bi bi-shield-lock me-1"></i>
                    Your password is securely hashed in the database. Never share your password with anyone.
                </div>

                <form method="post" autocomplete="off">
                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current password</label>
                        <div class="input-group">
                            <input class="form-control password-field" type="password" name="current_password" required autocomplete="current-password">
                            <button class="btn btn-outline-secondary toggle-password" type="button" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">New password</label>
                        <div class="input-group">
                            <input class="form-control password-field" id="newPassword" type="password" name="new_password" required minlength="8" autocomplete="new-password">
                            <button class="btn btn-outline-secondary toggle-password" type="button" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">
                            Minimum 8 characters, including a letter, number and special character.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirm new password</label>
                        <div class="input-group">
                            <input class="form-control password-field" id="confirmPassword" type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
                            <button class="btn btn-outline-secondary toggle-password" type="button" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div id="passwordMatch" class="form-text"></div>
                    </div>

                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-key me-1"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
document.querySelectorAll('.toggle-password').forEach(function(button) {
    button.addEventListener('click', function() {
        const input = this.parentElement.querySelector('.password-field');
        const icon = this.querySelector('i');
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        icon.className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
        this.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
});

const newPassword = document.getElementById('newPassword');
const confirmPassword = document.getElementById('confirmPassword');
const passwordMatch = document.getElementById('passwordMatch');

function checkPasswordMatch() {
    if (!confirmPassword.value) {
        passwordMatch.textContent = '';
        passwordMatch.className = 'form-text';
        return;
    }
    if (newPassword.value === confirmPassword.value) {
        passwordMatch.textContent = 'Passwords match.';
        passwordMatch.className = 'form-text text-success';
    } else {
        passwordMatch.textContent = 'Passwords do not match.';
        passwordMatch.className = 'form-text text-danger';
    }
}

newPassword.addEventListener('input', checkPasswordMatch);
confirmPassword.addEventListener('input', checkPasswordMatch);
</script>

<?php require __DIR__.'/../includes/footer.php'; ?>
