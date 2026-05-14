<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/src/GoogleAuthenticator.php';
require_once __DIR__ . '/config.php'; // provides $mysqli

use TwoFA\GoogleAuthenticator;

// ── Helper ───────────────────────────────────────────────────────────────────
function redirect(string $url, string $flash = '', bool $error = false): never
{
    if ($flash !== '') {
        $_SESSION['flash'] = ['text' => $flash, 'error' => $error];
    }
    header("Location: {$url}");
    exit;
}

// ── Guards ────────────────────────────────────────────────────────────────────
if (empty($_SESSION['member_id'])) {
    redirect('login.php', 'Session expired. Please log in again.', true);
}

if (!empty($_SESSION['2fa_verified'])) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('verify_login_2fa.php');
}

// ── Input ─────────────────────────────────────────────────────────────────────
$otp = trim((string) ($_POST['otp'] ?? ''));

if (!preg_match('/^\d{6}$/', $otp)) {
    redirect('verify_login_2fa.php', 'OTP must be exactly 6 digits.', true);
}

$memberId = (int) $_SESSION['member_id'];

// ── Fetch secret ──────────────────────────────────────────────────────────────
$stmt = $mysqli->prepare('
    SELECT google_secret
      FROM member
     WHERE member_id = ?
       AND google_2fa_enabled = 1
');

if (!$stmt) {
    error_log('2FA verify DB prepare error: ' . $mysqli->error);
    redirect('verify_login_2fa.php', 'A server error occurred. Please try again.', true);
}

$stmt->bind_param('i', $memberId);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();

if (!$row || empty($row['google_secret'])) {
    // 2FA not configured — let the user in without it (or redirect to setup)
    $_SESSION['2fa_verified'] = true;
    redirect('dashboard.php');
}

// ── Verify ────────────────────────────────────────────────────────────────────
$ga      = new GoogleAuthenticator();
$isValid = $ga->verifyCode($row['google_secret'], $otp, discrepancy: 1);

if (!$isValid) {
    redirect('verify_login_2fa.php', 'Invalid code. Please try again.', true);
}

// ── Mark verified & continue ──────────────────────────────────────────────────
$_SESSION['2fa_verified'] = true;
session_regenerate_id(true); // rotate session ID after full auth

redirect('dashboard.php', 'You are now logged in. Welcome!');
