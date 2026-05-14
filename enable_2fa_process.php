<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/src/GoogleAuthenticator.php';
require_once __DIR__ . '/config.php'; // provides $mysqli

use TwoFA\GoogleAuthenticator;

// ── Helpers ─────────────────────────────────────────────────────────────────
function jsonResponse(bool $success, string $message, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message], JSON_THROW_ON_ERROR);
    exit;
}

function redirect(string $url, string $flash = '', bool $error = false): never
{
    if ($flash !== '') {
        $_SESSION['flash'] = ['text' => $flash, 'error' => $error];
    }
    header("Location: {$url}");
    exit;
}

// ── Guards ───────────────────────────────────────────────────────────────────
if (empty($_SESSION['member_id'])) {
    redirect('login.php', 'Please log in first.', true);
}

if (empty($_SESSION['temp_google_secret'])) {
    redirect('setup_2fa.php', 'Session expired. Please start setup again.', true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('setup_2fa.php');
}

// ── Input validation ──────────────────────────────────────────────────────────
$otp = trim((string) ($_POST['otp'] ?? ''));

if (!preg_match('/^\d{6}$/', $otp)) {
    redirect('setup_2fa.php', 'OTP must be exactly 6 digits.', true);
}

$memberId = (int) $_SESSION['member_id'];
$secret   = $_SESSION['temp_google_secret'];

// ── Verify OTP ───────────────────────────────────────────────────────────────
$ga          = new GoogleAuthenticator();
$isValid     = $ga->verifyCode($secret, $otp, discrepancy: 1);

if (!$isValid) {
    redirect('setup_2fa.php', 'Invalid OTP. Please try again.', true);
}

// ── Persist to DB ─────────────────────────────────────────────────────────────
$stmt = $mysqli->prepare('
    UPDATE member
       SET google_secret      = ?,
           google_2fa_enabled = 1,
           updated_at         = NOW()
     WHERE member_id = ?
');

if (!$stmt) {
    error_log('2FA DB prepare error: ' . $mysqli->error);
    redirect('setup_2fa.php', 'A database error occurred. Please try again.', true);
}

$stmt->bind_param('si', $secret, $memberId);

if (!$stmt->execute()) {
    error_log('2FA DB execute error: ' . $stmt->error);
    redirect('setup_2fa.php', 'A database error occurred. Please try again.', true);
}

// ── Cleanup & success ─────────────────────────────────────────────────────────
unset($_SESSION['temp_google_secret']);
$_SESSION['2fa_enabled'] = true;

redirect('account.php', '2FA has been enabled successfully! 🎉');
