# Konstanten in wp-config.php

Zugangsdaten und Serverdaten können statt im Formular als Konstanten in der `wp-config.php`
stehen. Das hat drei Vorteile:

- **Passwörter und Secrets liegen nicht in der Datenbank** – auch nicht verschlüsselt, und landen
  damit auch nicht in Datenbank-Backups oder -Exporten.
- **Eine Staging-Kopie der Datenbank nimmt die Zugangsdaten nicht mit**, wenn die `wp-config.php`
  der Kopie sie nicht enthält.
- Die Werte lassen sich per Deployment verwalten.

Eine definierte Konstante **hat immer Vorrang** vor dem gespeicherten Wert. Das zugehörige Feld in
der Oberfläche ist gesperrt und trägt den Hinweis „Durch die Konstante … in der wp-config.php
festgelegt.“

Die Konstanten gehören **oberhalb** der Zeile `/* That's all, stop editing! */` in die `wp-config.php`.

---

## Primäre Verbindung

| Konstante | Feld | Werte |
|---|---|---|
| `RELAYMINT_MAILER` | Senden über | `smtp` oder `microsoft` (andere Werte → `smtp`) |
| `RELAYMINT_SMTP_HOST` | SMTP-Host | Hostname |
| `RELAYMINT_SMTP_PORT` | SMTP-Port | Zahl, wird als Integer gelesen |
| `RELAYMINT_SMTP_ENCRYPTION` | Verschlüsselung | `none`, `ssl` oder `tls` |
| `RELAYMINT_SMTP_USER` | SMTP-Benutzername | |
| `RELAYMINT_SMTP_PASS` | SMTP-Passwort | Klartext |
| `RELAYMINT_FROM_EMAIL` | Absender-E-Mail | E-Mail-Adresse |
| `RELAYMINT_FROM_NAME` | Absendername | |
| `RELAYMINT_MS_TENANT` | Verzeichnis-ID (Mandant) | Mandanten-ID, Domain oder `common` / `organizations` / `consumers`; wird auf Kleinbuchstaben, Ziffern, `.` und `-` reduziert, leer → `common` |
| `RELAYMINT_MS_CLIENT_ID` | Anwendungs-ID (Client) | |
| `RELAYMINT_MS_CLIENT_SECRET` | Geheimer Clientschlüssel | Klartext (der *Wert* des Secrets) |

### Beispiel: SMTP

```php
define( 'RELAYMINT_SMTP_HOST', 'smtp.example.com' );
define( 'RELAYMINT_SMTP_PORT', 587 );
define( 'RELAYMINT_SMTP_ENCRYPTION', 'tls' ); // none | ssl | tls
define( 'RELAYMINT_SMTP_USER', 'user@example.com' );
define( 'RELAYMINT_SMTP_PASS', 'secret' );
define( 'RELAYMINT_FROM_EMAIL', 'noreply@example.com' );
define( 'RELAYMINT_FROM_NAME', 'Example' );
```

### Beispiel: Microsoft 365 / Outlook

```php
define( 'RELAYMINT_MAILER', 'microsoft' ); // smtp | microsoft
define( 'RELAYMINT_MS_TENANT', 'contoso.onmicrosoft.com' );
define( 'RELAYMINT_MS_CLIENT_ID', '00000000-0000-0000-0000-000000000000' );
define( 'RELAYMINT_MS_CLIENT_SECRET', 'secret' );
```

Die **Authentifizierung** (delegiert / Nur App) wählst du weiterhin in der Oberfläche; bei
„Mit dem Postfach anmelden“ ist außerdem einmal **Mit Microsoft verbinden** nötig.

## Zusätzliche Verbindungen

Für zusätzliche Verbindungen gibt es nur die beiden Geheimnisse als Konstante. Der Name endet auf die
**Verbindungs-ID** in Großbuchstaben (andere Zeichen als Buchstaben und Ziffern werden zu `_`):

| Konstante | Feld |
|---|---|
| `RELAYMINT_SMTP_PASS_<ID>` | SMTP-Passwort |
| `RELAYMINT_MS_CLIENT_SECRET_<ID>` | Geheimer Clientschlüssel |

```php
// Verbindung mit der ID „cabc12345“ (Spalte „Verbindungs-ID“ in der Liste)
define( 'RELAYMINT_SMTP_PASS_CABC12345', 'secret' );
define( 'RELAYMINT_MS_CLIENT_SECRET_CABC12345', 'secret' );
```

Den exakten Namen zeigt Relaymint als Tipp unter dem Passwort- bzw. Secret-Feld der gespeicherten
Verbindung an.

## Was nicht per Konstante geht

Alle übrigen Einstellungen – *Absender-E-Mail erzwingen*, *Absendername erzwingen*, *Return-Path*,
*Auto-TLS*, *SMTP-Authentifizierung verwenden*, die Microsoft-*Authentifizierung*, Smart Routing,
Protokoll und *Sonstiges* – werden nur in der Oberfläche eingestellt.

## Hinweise

- Gesperrte Felder werden beim Speichern nicht übermittelt. Entfernst du eine Konstante später
  wieder, gilt der in der Datenbank gespeicherte Wert – der kann leer bzw. der Standardwert sein, wenn
  du das Formular in der Zwischenzeit gespeichert hast. Trage die Werte dann in der Oberfläche nach.
  Ein gespeichertes Passwort bzw. Secret bleibt dabei erhalten.
- Die Konstanten `RELAYMINT_VERSION`, `RELAYMINT_DB_VERSION`, `RELAYMINT_FILE`, `RELAYMINT_DIR`
  und `RELAYMINT_URL` definiert das Plugin selbst – nicht in der `wp-config.php` setzen.
