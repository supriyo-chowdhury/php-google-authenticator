<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/src/GoogleAuthenticator.php';
require_once __DIR__ . '/config.php'; // provides $mysqli

use TwoFA\GoogleAuthenticator;

// ── Auth guard ──────────────────────────────────────────────────────────────
if (empty($_SESSION['member_id'])) {
    header('Location: login.php');
    exit;
}

$memberId = (int) $_SESSION['member_id'];

// Generate a fresh secret and store temporarily in the session
$ga     = new GoogleAuthenticator();
$secret = $ga->createSecret();
$_SESSION['temp_google_secret'] = $secret;

$appName   = 'MyApp';                    // ← change to your app name
$qrCodeUrl = $ga->getQRCodeUrl("{$appName}:{$memberId}", $secret, $appName);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup 2FA — <?= htmlspecialchars($appName) ?></title>
    <link rel="stylesheet" href="assets/2fa.css">
</head>
<body>
<main class="card">
    <h1>Set up Two-Factor Authentication</h1>

    <p class="hint">
        Open <strong>Google Authenticator</strong> (or any TOTP app) and scan
        the QR code below.
    </p>

    <img class="qr" src="<?= htmlspecialchars($qrCodeUrl) ?>"
         alt="QR code for Google Authenticator" width="200" height="200">

    <details class="manual">
        <summary>Can't scan? Enter the key manually</summary>
        <code class="secret"><?= htmlspecialchars($secret) ?></code>
        <p class="muted">Type this key exactly into your authenticator app.</p>
    </details>

    <form method="POST" action="enable_2fa_process.php" novalidate>
        <label for="otp">Enter the 6-digit code shown in your app</label>
        <input id="otp" type="text" name="otp"
               inputmode="numeric" pattern="\d{6}" maxlength="6"
               placeholder="000000" autocomplete="one-time-code" required>
        <button type="submit">Verify &amp; Enable 2FA</button>
    </form>
</main>
<link rel="stylesheet" href="assets/2fa.css">
</body>
</html>
