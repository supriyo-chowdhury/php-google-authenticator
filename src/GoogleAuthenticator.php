<?php

declare(strict_types=1);

namespace TwoFA;

/**
 * PHP Class for handling Google Authenticator 2-factor authentication (TOTP/RFC 6238).
 *
 * Author: Supriyo Chowdhury
 * Repository: https://github.com/supriyo-chowdhury/php-google-authenticator
 * Modernised: namespaced, strict types, PSR-4, PHP 8+ compatible.
 * Original TOTP algorithm credit: Michael Kliewe (PHPGangsta)
 *
 * @license BSD License
 */
class GoogleAuthenticator
{
    private int $codeLength;

    public function __construct(int $codeLength = 6)
    {
        if ($codeLength < 6) {
            throw new \InvalidArgumentException('Code length must be at least 6.');
        }
        $this->codeLength = $codeLength;
    }

    /**
     * Generate a new cryptographically secure Base32 secret key.
     *
     * @param int $secretLength Length in characters (16–128). 32 recommended.
     * @throws \Exception
     */
    public function createSecret(int $secretLength = 32): string
    {
        if ($secretLength < 16 || $secretLength > 128) {
            throw new \InvalidArgumentException('Secret length must be between 16 and 128.');
        }

        $chars  = $this->getBase32LookupTable();
        $bytes  = random_bytes($secretLength);
        $secret = '';

        for ($i = 0; $i < $secretLength; ++$i) {
            $secret .= $chars[ord($bytes[$i]) & 31];
        }

        return $secret;
    }

    /**
     * Calculate the current TOTP code for a given secret.
     *
     * @param string   $secret    Base32-encoded secret
     * @param int|null $timeSlice Unix time / 30 (null = now)
     */
    public function getCode(string $secret, ?int $timeSlice = null): string
    {
        $timeSlice ??= (int) floor(time() / 30);

        $secretKey = $this->base32Decode($secret);
        $time      = "\x00\x00\x00\x00" . pack('N*', $timeSlice);
        $hash      = hash_hmac('sha1', $time, $secretKey, true);
        $offset    = ord($hash[-1]) & 0x0F;
        $value     = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % (10 ** $this->codeLength)), $this->codeLength, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a user-supplied OTP code against the secret.
     *
     * @param string   $secret      Base32 secret
     * @param string   $code        6-digit code from the authenticator app
     * @param int      $discrepancy Allowed time-drift windows (each = 30 s). Default 1.
     * @param int|null $timeSlice   Override current time slice (for testing).
     */
    public function verifyCode(string $secret, string $code, int $discrepancy = 1, ?int $timeSlice = null): bool
    {
        if (strlen($code) !== $this->codeLength) {
            return false;
        }

        $currentSlice = $timeSlice ?? (int) floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; ++$i) {
            if (hash_equals($this->getCode($secret, $currentSlice + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a QR-code image URL (via api.qrserver.com) that authenticator apps can scan.
     *
     * @param string $accountName User identifier shown in the app (e.g. email)
     * @param string $secret      Base32 secret
     * @param string $issuer      App / company name shown in the app
     * @param int    $size        QR image size in pixels (default 200)
     */
    public function getQRCodeUrl(
        string $accountName,
        string $secret,
        string $issuer = '',
        int $size = 200
    ): string {
        $otpauth = 'otpauth://totp/' . rawurlencode($accountName) . '?secret=' . $secret;

        if ($issuer !== '') {
            $otpauth .= '&issuer=' . rawurlencode($issuer);
        }

        return sprintf(
            'https://api.qrserver.com/v1/create-qr-code/?data=%s&size=%dx%d&ecc=M',
            urlencode($otpauth),
            $size,
            $size
        );
    }

    // ──────────────────────────────────────────────
    // Internal helpers
    // ──────────────────────────────────────────────

    private function base32Decode(string $secret): string
    {
        if ($secret === '') {
            return '';
        }

        $table   = $this->getBase32LookupTable();
        $flipped = array_flip($table);

        $secret = strtoupper(str_replace('=', '', $secret));
        $chars  = str_split($secret);
        $binary = '';

        foreach (array_chunk($chars, 8) as $chunk) {
            $bin = '';
            foreach ($chunk as $char) {
                if (!isset($flipped[$char])) {
                    return '';
                }
                $bin .= str_pad(decbin($flipped[$char]), 5, '0', STR_PAD_LEFT);
            }
            foreach (str_split($bin, 8) as $byte) {
                if (strlen($byte) === 8) {
                    $binary .= chr(bindec($byte));
                }
            }
        }

        return $binary;
    }

    /** @return string[] */
    private function getBase32LookupTable(): array
    {
        return str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567=');
    }
}
