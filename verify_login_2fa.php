<?php

declare(strict_types=1);

session_start();

// Show only if mid-login (member_id set but 2fa not yet verified)
if (empty($_SESSION['member_id']) || !empty($_SESSION['2fa_verified'])) {
    header('Location: ' . (empty($_SESSION['member_id']) ? 'login.php' : 'dashboard.php'));
    exit;
}

// Show flash message if set
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-Factor Verification</title>
    <link rel="stylesheet" href="assets/2fa.css">
</head>
<body>
<main class="card">
    <h1>Two-Factor Verification</h1>

    <?php if ($flash): ?>
        <div class="alert <?= $flash['error'] ? 'alert--error' : 'alert--success' ?>">
            <?= htmlspecialchars($flash['text']) ?>
        </div>
    <?php endif; ?>

    <p class="hint">
        Open your authenticator app and enter the 6-digit code for this account.
    </p>

    <form method="POST" action="verify_login_2fa_process.php" novalidate>
        <label for="otp">Authentication code</label>
        <input id="otp" type="text" name="otp"
               inputmode="numeric" pattern="\d{6}" maxlength="6"
               placeholder="000000" autocomplete="one-time-code"
               autofocus required>
        <button type="submit">Verify &amp; Log In</button>
    </form>
</main>
</body>
</html>
