# Relaymint

> Reliable email delivery for WordPress via SMTP or Microsoft 365 / Outlook — with email log, smart routing, background sending and rate limiting.

[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2+-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
![WordPress](https://img.shields.io/badge/WordPress-6.9%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)

**Documentation (German):** detailed setup guides, reference and troubleshooting in the [Relaymint Wiki](https://github.com/mrclksr2409/Relaymint/wiki).

## Description

Relaymint replaces the default PHP `mail()` transport of WordPress with an authenticated SMTP connection or the Microsoft Graph API (Microsoft 365 / Outlook), so emails from WordPress core, plugins and themes actually reach the inbox. Every email is logged, can be routed through different SMTP accounts based on rules, and can optionally be sent in the background with rate limits — a self-hosted alternative to plugins like Easy WP SMTP.

## Features

- **SMTP mailer** — host, port, encryption (none / SSL / TLS), Auto TLS, authentication, From email/name (optionally forced), return path.
- **Microsoft 365 / Outlook mailer** — sends through the Microsoft Graph API with OAuth 2.0 instead of SMTP AUTH: sign in with the mailbox once (Microsoft 365 and Outlook.com) or use app-only access with the application permission `Mail.Send` (Microsoft 365). Tokens are renewed automatically.
- **Encrypted credentials** — SMTP passwords, Microsoft client secrets and OAuth tokens are stored with AES-256-GCM (key derived from the WordPress salts) or defined as constants in `wp-config.php`.
- **Email log** — status (sent, failed, queued, blocked), recipients, connection, initiating plugin/theme, error messages; optional storage of content and headers; search, status filter, detail view with sandboxed HTML preview, resend, bulk delete and automatic retention.
- **Smart Routing** — additional connections (SMTP or Microsoft 365 / Outlook) plus rules (subject, message, from, to, cc, bcc, reply-to, header, initiator × contains / is / starts with / ends with / regex and their negations). Conditions within a group are AND-combined, groups are OR-combined, first matching route wins.
- **Domain check** — only use SMTP on allowed site domains (e.g. to keep staging copies off the production mail account), optionally block all emails on a mismatch.
- **Do Not Send** — block all outgoing emails (they are still logged).
- **Allow insecure SSL certificates** — for SMTP servers with self-signed certificates.
- **Debug log** — SMTP transcripts with masked credentials; errors are always recorded.
- **Optimize email sending** — emails are queued and sent in the background via the bundled [Action Scheduler](https://actionscheduler.org/). Attachments are copied to a protected directory until the email is sent.
- **Email rate limiting** — max. emails per minute / hour / day / week; emails above the limit wait in the queue.
- **Test email** — send a test via any connection and see the full SMTP debug output.
- **Unified admin design** — all Relaymint screens use the bundled WP-Backend UI design system shared by our plugins (page header, cards, badges, tabs).
- German translation included.

## Requirements

- WordPress 6.9 or higher (required by the bundled Action Scheduler 4.2)
- PHP 7.4 or higher with the OpenSSL extension
- An SMTP account (any provider) or a Microsoft 365 / Outlook.com mailbox with an app registration in Microsoft Entra ID
- Bundled, nothing to install: [Action Scheduler](https://actionscheduler.org/) 4.2.0, [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) 5.6 and WP-Backend UI 1.0.2 (shared admin design system)

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

1. Go to **Relaymint → Settings → General**, choose the mailer (SMTP or Microsoft 365 / Outlook) and enter the data of your provider.
2. Send a test email under **Relaymint → Tools**.
3. Optional: add more SMTP accounts under **Additional Connections** and create rules under **Smart Routing**.
4. Configure logging under **Email Log** and the remaining options under **Misc**.
5. Sent emails are listed under **Relaymint → Email Log**.

### Microsoft 365 / Outlook

Relaymint sends via the [Microsoft Graph `sendMail` API](https://learn.microsoft.com/graph/api/user-sendmail). The message is built by PHPMailer as usual and handed to Graph as MIME, so HTML, attachments, Cc, Bcc and Reply-To work as with SMTP. SMTP AUTH does not have to be enabled for the mailbox.

1. In the [Microsoft Entra admin center](https://entra.microsoft.com/) open **App registrations → New registration**.
   - Supported account types: your organization only, or *any organization and personal Microsoft accounts* for Outlook.com.
   - Redirect URI: platform **Web**, value as shown in the Relaymint connection settings (`https://your-site/wp-admin/admin-post.php`). HTTPS is required.
2. **Certificates & secrets → New client secret**, copy the secret *value*.
3. **API permissions → Microsoft Graph**:
   - *Sign in with the mailbox* (delegated): `Mail.Send`, `User.Read`, `offline_access`.
   - *App-only* (application): `Mail.Send`, then **Grant admin consent**. Restrict the app to the sending mailbox with an [application access policy / RBAC for applications](https://learn.microsoft.com/exchange/permissions-exo/application-rbac).
4. In Relaymint select **Microsoft 365 / Outlook**, enter tenant ID, client ID and secret and save.
   - Delegated: click **Connect with Microsoft** and sign in with the sending mailbox. Leave *From Email* empty to send as that mailbox; other addresses need *Send As* rights.
   - App-only: set the tenant ID (not `common`) and the *From Email* of a mailbox in your tenant.
5. Send a test email under **Relaymint → Tools**.

Notes: Graph accepts messages up to about 4 MB per request (including base64 encoded attachments). Client secrets expire — renew them in Entra and enter the new value in time. Changing tenant, client ID or authentication mode requires signing in again.

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

// Primary connection via Microsoft 365 / Outlook.
define( 'RELAYMINT_MAILER', 'microsoft' ); // smtp | microsoft
define( 'RELAYMINT_MS_TENANT', 'contoso.onmicrosoft.com' );
define( 'RELAYMINT_MS_CLIENT_ID', '00000000-0000-0000-0000-000000000000' );
define( 'RELAYMINT_MS_CLIENT_SECRET', 'secret' );

// Password of an additional connection (the ID is shown in the connection list).
define( 'RELAYMINT_SMTP_PASS_CABC12345', 'secret' );
// Client secret of an additional Microsoft connection.
define( 'RELAYMINT_MS_CLIENT_SECRET_CABC12345', 'secret' );
```

> **Note:** Stored passwords, client secrets and OAuth tokens are encrypted with a key derived from `AUTH_KEY`, `SECURE_AUTH_KEY` and `LOGGED_IN_KEY`. If you change these salts, re-enter the SMTP passwords / client secrets and reconnect Microsoft accounts.

### How the options interact

1. **Do Not Send** blocks everything (logged as *blocked*).
2. **Domain check** mismatch → block all (if enabled) or send without SMTP via PHP `mail()`.
3. **Optimize Email Sending** → the email is queued and `wp_mail()` returns `true` immediately.
4. **Smart Routing** picks the connection (falls back to the primary connection).
5. **Rate limiting** is applied when the queue is processed, so it requires *Optimize Email Sending*.

## Updates

This plugin uses the [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) library to deliver updates directly from GitHub Releases. Once installed, WordPress will check for updates automatically (every 12 hours by default) and show them on the standard **Dashboard → Updates** screen. You can trigger a manual check via **Plugins → Check for updates**.

Under **Settings → Misc → Update Channel** you can choose the source:

- **Stable** (default) — GitHub Releases of the `main` branch.
- **Beta** — the current head of the `beta` branch. An update is offered as soon as the `Version` header in `relaymint.php` on `beta` is higher than the installed version. Not intended for production sites.

Switching the channel clears the cached update data. Switching back from beta to stable does not downgrade; the next stable release is installed once its version is higher than the installed beta.

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

Bundled libraries (do not edit): `libraries/action-scheduler/` (Action Scheduler 4.2.0), `libraries/wp-backend-ui/` (WP-Backend UI 1.0.2, shared admin design system) and `plugin-update-checker/` (PUC 5.6).

To update WP-Backend UI, replace `libraries/wp-backend-ui/` with the new release (only `wp-backend-ui.php`, `includes/`, `assets/` and `README.md`). If several plugins bundle the library, the newest copy is loaded.

## Changelog

### [0.4.1] — 2026-10-10

#### Changed
- Author URI now points to the GitHub profile.
- New "Wiki" link in the plugin row on the Plugins screen.

### [0.4.0] — 2026-10-10

#### Added
- Microsoft 365 / Outlook mailer via the Microsoft Graph API (OAuth 2.0): sign in with the mailbox (delegated, authorization code flow with PKCE, Microsoft 365 and Outlook.com) or app-only access (client credentials, Microsoft 365). Available for the primary and additional connections, Smart Routing and the test email.
- Encrypted storage of client secrets and OAuth tokens, automatic token renewal, `RELAYMINT_MAILER` / `RELAYMINT_MS_*` constants.

#### Changed
- Connection settings start with a mailer selection; the connection list shows how each connection sends.

### [0.3.0] — 2026-10-09

#### Added
- Update channel setting (Misc tab): stable (GitHub Releases from `main`) or beta (`beta` branch).

### [0.2.0] — 2026-10-09

#### Changed
- Unified admin design via the bundled WP-Backend UI 1.0.2 (`libraries/wp-backend-ui/`): shared page header with version badge, tabs, cards and design tokens on Settings, Email Log and Tools.
- Email log: status pills are now badges; the detail view shows the entry as a card with a key/value list, headers and message in cards, Back/Resend in the header.
- Tools: test email and debug log are shown as cards; test results as inline alerts.
- Smart Routing routes and the domain check / rate limit sub-options are shown as cards.
- Confirmation dialogs use the library's `data-wpb-confirm`; `assets/css/admin.css` only contains Relaymint-specific layout.

### [0.1.0] — 2026-10-09

#### Added
- SMTP mailer with encrypted credentials and `wp-config.php` overrides.
- Email log with search, filters, detail view, resend and retention.
- Smart Routing with additional connections.
- Domain check, Do Not Send, insecure SSL option, debug log.
- Background sending via Action Scheduler and email rate limiting.
- Test email tool, German translation.

## License

GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
