<?php
require_once __DIR__ . '/../app/session.php';
start_session();
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - FitMap</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;">

<div style="width:100%;max-width:420px;padding:1.5rem;">
    <div style="text-align:center;margin-bottom:2rem;">
        <h1 style="font-size:2rem;font-weight:700;">Fit<span style="color:var(--accent);">Map</span></h1>
        <p style="color:var(--text-secondary);margin-top:0.4rem;">Sign in to your account</p>
    </div>

    <div class="card">
        <div id="auth-error" style="display:none;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:0.7rem 1rem;margin-bottom:1rem;color:var(--danger);font-size:0.88rem;"></div>

        <form id="login-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="you@email.com" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full" id="login-btn">Sign In</button>
        </form>

        <p style="text-align:center;margin-top:1.2rem;font-size:0.88rem;color:var(--text-secondary);">
            No account? <a href="register.php">Create one</a>
        </p>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
$(function () {
    $('#login-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#login-btn').text('Signing in...').prop('disabled', true);
        $('#auth-error').hide();

        ajax('../api/auth.php', {
            action:   'login',
            email:    $('#email').val(),
            password: $('#password').val(),
        }, 'POST')
        .done(function (r) {
            if (r.success) {
                window.location.href = r.redirect;
            } else {
                $('#auth-error').text(r.error).show();
                $btn.text('Sign In').prop('disabled', false);
            }
        })
        .fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Login failed.';
            $('#auth-error').text(msg).show();
            $btn.text('Sign In').prop('disabled', false);
        });
    });
});
</script>
</body>
</html>