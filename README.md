# Relaymint

> Reliable SMTP delivery for WordPress — with email log, smart routing, background sending and rate limiting.

[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2+-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
![WordPress](https://img.shields.io/badge/WordPress-6.9%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)

## Description

Relaymint replaces the default PHP `mail()` transport of WordPress with an authenticated SMTP connection, so emails from WordPress core, plugins and themes actually reach the inbox. Every email is logged, can be routed through different SMTP accounts based on rules, and can optionally be sent in the background with rate limits — a self-hosted alternative to plugins like Easy WP SMTP.

## Features

- **SMTP mailer** — host, port, encryption (none / SSL / TLS), Auto TLS, authentication, From email/name (optionally forced), return path.
- **Encrypted credentials** — SMTP passwords are stored with AES-256-GCM (key derived from the WordPress salts) or defined as constants in `wp-config.php`.
- **Email log** — status (sent, failed, queued, blocked), recipients, connection, initiating plugin/theme, error messages; optional storage of content and headers; search, status filter, detail view with sandboxed HTML preview, resend, bulk delete and automatic retention.
- **Smart Routing** — additional SMTP connections plus rules (subject, message, from, to, cc, bcc, reply-to, header, initiator × contains / is / starts with / ends with / regex and their negations). Conditions within a group are AND-combined, groups are OR-combined, first matching route wins.
- **Domain check** — only use SMTP on allowed site domains (e.g. to keep staging copies off the production mail account), optionally block all emails on a mismatch.
- **Do Not Send** — block all outgoing emails (they are still logged).
- **Allow insecure SSL certificates** — for SMTP servers with self-signed certificates.
- **Debug log** — SMTP transcripts with masked credentials; errors are always recorded.
- **Optimize email sending** — emails are queued and sent in the background via the bundled [Action Scheduler](https://actionscheduler.org/). Attachments are copied to a protected directory until the email is sent.
- **Email rate limiting** — max. emails per minute / hour / day / week; emails above the limit wait in the queue.
- **Test email** — send a test via any connection and see the full SMTP debug output.
- German translation included.

## Requirements

- WordPress 6.9 or higher (required by the bundled Action Scheduler 4.2)
- PHP 7.4 or higher with the OpenSSL extension
- An SMTP account (any provider)

## Installation

### From GitHub

1. Download `relaymint.zip` from the latest release on the [Releases page](https://github.com/mrclksr2409/Relaymint/releases).
2. Upload it via **Plugins → Add New → Upload Plugin**.
3. Activate the plugin.

### Manual

```bash
cd wp-content/plugins
git clone https://github.com/mrclksr2409/Relaymint.git relaymint
```

## Usage

1. Go to **Relaymint → Settings → General** and enter the SMTP data of your provider.
2. Send a test email under **Relaymint → Tools**.
3. Optional: add more SMTP accounts under **Additional Connections** and create rules under **Smart Routing**.
4. Configure logging under **Email Log** and the remaining options under **Misc**.
5. Sent emails are listed under **Relaymint → Email Log**.

### Constants for `wp-config.php`

Defined constants override the stored settings and lock the corresponding field in the admin.

```php
// Primary connection.
define( 'RELAYMINT_SMTP_HOST', 'smtp.example.com' );
define( 'RELAYMINT_SMTP_PORT', 587 );
define( 'RELAYMINT_SMTP_ENCRYPTION', 'tls' ); // none | ssl | tls
define( 'RELAYMINT_SMTP_USER', 'user@example.com' );
define( 'RELAYMINT_SMTP_PASS', 'secret' );
define( 'RELAYMINT_FROM_EMAIL', 'noreply@example.com' );
define( 'RELAYMINT_FROM_NAME', 'Example' );

// Password of an additional connection (the ID is shown in the connection list).
define( 'RELAYMINT_SMTP_PASS_CABC12345', 'secret' );
```

> **Note:** Stored passwords are encrypted with a key derived from `AUTH_KEY`, `SECURE_AUTH_KEY` and `LOGGED_IN_KEY`. If you change these salts, re-enter the SMTP passwords.

### How the options interact

1. **Do Not Send** blocks everything (logged as *blocked*).
2. **Domain check** mismatch → block all (if enabled) or send without SMTP via PHP `mail()`.
3. **Optimize Email Sending** → the email is queued and `wp_mail()` returns `true` immediately.
4. **Smart Routing** picks the connection (falls back to the primary connection).
5. **Rate limiting** is applied when the queue is processed, so it requires *Optimize Email Sending*.

## Updates

This plugin uses the [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) library to deliver updates directly from GitHub Releases. Once installed, WordPress will check for updates automatically (every 12 hours by default) and show them on the standard **Dashboard → Updates** screen. You can trigger a manual check via **Plugins → Check for updates**.

Releases are built by `.github/workflows/release.yml`: pushing a tag `vX.Y.Z` creates `relaymint.zip` and attaches it to the GitHub release of that tag.

## Hooks & Filters

### Actions

| Hook | Parameters | Description |
|---|---|---|
| `relaymint_before_send` | `$mail, $connection` | Fires before an email is handed to PHPMailer. `$connection` is empty when PHP `mail()` is used. |

### Filters

| Hook | Parameters | Description |
|---|---|---|
| `relaymint_route_connection` | `$connection, $mail, $initiator` | Connection ID used for an email (after Smart Routing). |
| `relaymint_log_entry_data` | `$data, $mail` | Row data of a new log entry. |
| `relaymint_rate_limits` | `$limits` | Max. emails keyed by period length in seconds. |
| `relaymint_allowed_domains` | `$domains` | Allowed site domains of the domain check. |
| `relaymint_do_not_send_result` | `$result` | Return value of `wp_mail()` for emails blocked by *Do Not Send* (default `true`). |
| `relaymint_queue_time_limit` | `$seconds` | Max. runtime of one queue worker run (default 20). |

`$mail` is a `Relaymint_Mail_Data` object (`$mail->atts` holds the original `wp_mail()` arguments).

## Development

```bash
git clone https://github.com/mrclksr2409/Relaymint.git
cd Relaymint
# Coding standards (WPCS 3):
phpcs            # uses phpcs.xml.dist
# Regenerate translations:
wp i18n make-pot . languages/relaymint.pot --exclude=libraries,plugin-update-checker
wp i18n make-mo languages/
```

Bundled libraries (do not edit): `libraries/action-scheduler/` (Action Scheduler 4.2.0) and `plugin-update-checker/` (PUC 5.6).

## Changelog

### [1.0.0] — 2026-10-09

#### Added
- SMTP mailer with encrypted credentials and `wp-config.php` overrides.
- Email log with search, filters, detail view, resend and retention.
- Smart Routing with additional connections.
- Domain check, Do Not Send, insecure SSL option, debug log.
- Background sending via Action Scheduler and email rate limiting.
- Test email tool, German translation.

## License

GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
