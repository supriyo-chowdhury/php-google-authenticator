# PHP 2FA — Google Authenticator (TOTP)

A clean, modern PHP implementation of **Time-based One-Time Password (TOTP)** two-factor authentication, compatible with Google Authenticator, Authy, and any RFC 6238 app.

---

## ✨ Features

- ✅ Pure PHP, zero Composer dependencies
- ✅ Namespaced, PSR-4-ready (`TwoFA\GoogleAuthenticator`)
- ✅ Strict types, PHP 8.0+
- ✅ Cryptographically secure secret generation (`random_bytes`)
- ✅ Timing-safe OTP verification (`hash_equals`)
- ✅ Session rotation after full authentication
- ✅ Flash-message system for user feedback
- ✅ Polished, responsive UI for all 2FA pages
- ✅ Static HTML demo — try it without a server

---

## 📁 Project Structure

```
php-google-authenticator/
├── src/
│   └── GoogleAuthenticator.php          # Core TOTP library
├── assets/
│   └── 2fa.css                          # Shared styles for all pages
├── migrations/
│   └── 001_add_2fa_columns.sql          # DB migration for member table
├── demo/
│   └── index.html                       # Static demo (no server needed)
├── setup_2fa.php                        # Generate secret + QR code page
├── enable_2fa_process.php               # Verify OTP and save to DB
├── verify_login_2fa.php                 # OTP prompt during login
├── verify_login_2fa_process.php         # Verify login OTP + rotate session
├── config.example.php                   # DB config template
└── README.md
```

---

## 🚀 Quick Start

### 1. Clone the repository

```bash
git clone https://github.com/supriyo-chowdhury/php-google-authenticator.git
cd php-google-authenticator
```

### 2. Configure your database

```bash
cp config.example.php config.php
# Edit config.php with your MySQL credentials
```

### 3. Run the database migration

```bash
mysql -u your_user -p your_database < migrations/001_add_2fa_columns.sql
```

### 4. Copy the files into your project

Place the files in the appropriate location in your PHP project. Update the `require_once` paths in each file to match your project structure.

### 5. Integrate into your login flow

**After your password check passes, redirect to:**
```
verify_login_2fa.php
```

**In your account settings, link to:**
```
setup_2fa.php
```

---

## 🔌 Usage — `GoogleAuthenticator` class

```php
use TwoFA\GoogleAuthenticator;

$ga = new GoogleAuthenticator();

// 1. Generate a secret key for a new user
$secret = $ga->createSecret(); // e.g. "JBSWY3DPEHPK3PXP..."

// 2. Display a QR code the user can scan
$qrUrl = $ga->getQRCodeUrl('user@example.com', $secret, 'MyApp');
// Use $qrUrl as <img src="...">

// 3. Verify a code from the authenticator app
$isValid = $ga->verifyCode($secret, $_POST['otp']); // true or false
```

---

## 🔐 Security Notes

| Concern | How it's handled |
|---|---|
| Secret storage | Stored in the DB only after the user verifies the first OTP |
| OTP validation | `hash_equals()` — constant-time, prevents timing attacks |
| Time drift | ±1 window (±30 s) — configurable via `$discrepancy` parameter |
| Session fixation | `session_regenerate_id(true)` called after successful 2FA login |
| Input validation | OTP regex-validated as exactly 6 digits before DB query |
| Error messages | Generic messages — no leaking of internal state |
| Config file | `config.php` should **never** be committed (add to `.gitignore`) |

---

## ⚙️ Requirements

| Requirement | Version |
|---|---|
| PHP | 8.0 or higher |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| PHP extensions | `mysqli`, `hash`, `openssl` (all standard) |

---

## 🗄️ Database Schema

The migration adds these columns to your existing `member` table:

```sql
google_secret       VARCHAR(128)  -- Base32 TOTP secret
google_2fa_enabled  TINYINT(1)    -- 0 = off, 1 = on
updated_at          TIMESTAMP     -- auto-updated on row change
```

---

## 🖥️ Static Demo

Open `demo/index.html` in any browser (no server needed):

- **Setup tab** — generates a real TOTP secret and QR code you can scan
- **Verify tab** — live 30-second countdown ring, verifies real TOTP codes
- **Flow tab** — visual map of how the five PHP files work together

---

## 🤝 Contributing

Pull requests are welcome! Please open an issue first for larger changes.

1. Fork the repo
2. Create a feature branch: `git checkout -b feature/my-change`
3. Commit your changes: `git commit -m "Add my change"`
4. Push to the branch: `git push origin feature/my-change`
5. Open a pull request

---

## 📄 License

MIT — free to use in personal and commercial projects. See [LICENSE](LICENSE).

---

## 🙏 Credits

**Author:** [Supriyo Chowdhury](https://github.com/supriyo-chowdhury) — [github.com/supriyo-chowdhury/php-google-authenticator](https://github.com/supriyo-chowdhury/php-google-authenticator)

Core TOTP algorithm originally by Michael Kliewe (PHPGangsta).
Refactored to modern PHP 8, namespaced, and extended by Supriyo Chowdhury.
