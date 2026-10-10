# Entwicklung

## Architektur

```
wp_mail()
  │
  ├─ pre_wp_mail ─────────► Relaymint_Interceptor ──► Nicht senden / Domain-Prüfung
  │                               │                   (Relaymint_Domain_Check)
  │                               ├─► Relaymint_Queue::enqueue() ──► Action Scheduler
  │                               │        └─ relaymint_process_queue ──► Relaymint_Rate_Limiter
  │                               │                                     ──► wp_mail() erneut
  │                               ├─► Relaymint_Router::resolve() ──► Verbindungs-ID
  │                               └─► Relaymint_Mailer::prepare() + Relaymint_Logger::create()
  │
  ├─ phpmailer_init ──────► Relaymint_Mailer::configure()
  │                               ├─ SMTP: isSMTP(), Host, Port, Auth …
  │                               └─ Microsoft: Relaymint_PHPMailer (Mailer „relaymintgraph“)
  │                                     └─ Relaymint_Microsoft::send() ──► Graph sendMail
  │
  └─ wp_mail_succeeded / wp_mail_failed ──► Relaymint_Logger, Relaymint_Debug_Log
```

Die Entscheidung fällt komplett in `pre_wp_mail`, weil PHPMailer für Microsoft-Verbindungen
**ersetzt** werden muss, bevor `wp_mail()` ihn erzeugt. `Relaymint_PHPMailer` erweitert
`WP_PHPMailer` um den Transport `relaymintgraphSend()`: PHPMailer baut die MIME-Nachricht, die
Unterklasse ergänzt den `Bcc`-Header und schickt alles Base64-kodiert an Graph.

## Verzeichnisstruktur

```
relaymint/
├── relaymint.php                     Bootstrap: Header, Konstanten, Bibliotheken, Update Checker
├── uninstall.php                     Aufräumen beim Löschen
├── includes/
│   ├── class-relaymint.php           Singleton, verdrahtet alle Komponenten, tägliche Wartung
│   ├── class-relaymint-options.php   Option relaymint_settings, Standardwerte
│   ├── class-relaymint-connections.php  Verbindungen, Konstanten, Sanitizing
│   ├── class-relaymint-secrets.php   AES-256-GCM
│   ├── class-relaymint-microsoft.php OAuth 2.0 (PKCE, Client Credentials), Tokens, Graph-Versand
│   ├── class-relaymint-phpmailer.php PHPMailer-Unterklasse mit Graph-Transport (bei Bedarf geladen)
│   ├── class-relaymint-mail-data.php wp_mail-Argumente normalisieren, Auslöser erkennen
│   ├── class-relaymint-interceptor.php  pre_wp_mail
│   ├── class-relaymint-mailer.php    phpmailer_init, Absender-Filter, Mitschnitt
│   ├── class-relaymint-router.php    Smart Routing
│   ├── class-relaymint-domain-check.php
│   ├── class-relaymint-queue.php     Warteschlange + Worker
│   ├── class-relaymint-rate-limiter.php
│   ├── class-relaymint-logger.php    E-Mail-Protokoll
│   ├── class-relaymint-debug-log.php Debug-Log mit Maskierung
│   ├── class-relaymint-installer.php Tabellen, Aktivierung/Deaktivierung
│   └── admin/
│       ├── class-relaymint-admin.php         Menü, Formular-Handler (admin_post_*)
│       ├── class-relaymint-log-list-table.php
│       └── views/                            Tabs, Verbindungsformular, Protokoll, Werkzeuge
├── assets/css/admin.css, assets/js/admin.js
├── languages/                        relaymint.pot, relaymint-de_DE.po/.mo
├── libraries/action-scheduler/       Action Scheduler 4.2.0 (nicht bearbeiten)
├── libraries/wp-backend-ui/          WP-Backend UI 1.0.2 (nicht bearbeiten)
├── plugin-update-checker/            PUC 5.6 (nicht bearbeiten)
└── wiki/                             Quelle dieses Wikis
```

Die Klassen werden in `relaymint.php` per `require_once` geladen – neue Dateien dort eintragen.

## Admin-Aktionen

Alle Formulare und Links laufen über `admin-post.php`, prüfen `manage_options` und eine Nonce:

| Action | Zweck |
|---|---|
| `relaymint_save` | Einen Einstellungs-Tab speichern (`tab` = `general`, `connections`, `routing`, `logs`, `misc`) |
| `relaymint_delete_connection` | Zusätzliche Verbindung löschen |
| `relaymint_test_email` | Test-E-Mail |
| `relaymint_clear_debug_log` | Debug-Log leeren |
| `relaymint_resend` | Protokolleintrag erneut senden |
| `relaymint_delete_logs` | Protokoll leeren |
| `relaymint_run_queue` | Warteschlange jetzt abarbeiten |
| `relaymint_ms_connect` / `relaymint_ms_disconnect` | Microsoft-Anmeldung starten / Tokens löschen |
| *(ohne Action)* `admin_post` | Rückkehr von Microsoft, erkannt am `state`-Parameter |

## Konventionen

- PHP 7.4-kompatibel, WordPress Coding Standards (WPCS 3) über `phpcs.xml.dist`
- Präfix `relaymint` für alle globalen Namen, Text-Domain `relaymint`
- Klassen sind überwiegend statisch, Präfix `Relaymint_`
- Oberfläche auf Englisch mit deutscher Übersetzung; Debug-Log-Meldungen englisch
- Neue Einstellung: Standardwert in `Relaymint_Options::defaults()` bzw. `connection_defaults()`
  **und** Sanitizing in `Relaymint_Admin::handle_save()` bzw. `Relaymint_Connections::sanitize()`.
  Gelesen wird immer über `Relaymint_Options`, das gespeicherte Werte mit den Standardwerten
  zusammenführt.
- Schemaänderung: `RELAYMINT_DB_VERSION` erhöhen; `Relaymint_Installer::maybe_upgrade()` führt dann
  `dbDelta()` aus.
- Admin-Design: Seiten nutzen die mitgelieferte WP-Backend UI (`WPB_Admin_UI::header()`, `tabs()`,
  `card_start()`, `badge()`, `alert()`, `data-wpb-confirm`). `assets/css/admin.css` enthält nur
  Relaymint-spezifisches Layout.

## Lokal arbeiten

```bash
git clone https://github.com/mrclksr2409/Relaymint.git
cd Relaymint

# Coding Standards (WPCS 3)
phpcs                 # nutzt phpcs.xml.dist

# Übersetzungen neu erzeugen
wp i18n make-pot . languages/relaymint.pot --exclude=libraries,plugin-update-checker
wp i18n make-mo languages/
```

Hilfreich beim Testen:

- **Werkzeuge → Test-E-Mail** zeigt den kompletten SMTP- bzw. Graph-Mitschnitt.
- **Sonstiges → Nicht senden** + **E-Mail-Inhalt protokollieren** fängt alle E-Mails im Protokoll
  ab, ohne dass etwas versendet wird.
- Warteschlange anstoßen: `wp action-scheduler run --group=relaymint` (WP-CLI-Befehl des Action
  Schedulers) oder **Warteschlange jetzt abarbeiten**.

## Bibliotheken aktualisieren

- **WP-Backend UI:** `libraries/wp-backend-ui/` durch das neue Release ersetzen (nur
  `wp-backend-ui.php`, `includes/`, `assets/` und `README.md`). Bündeln mehrere Plugins die
  Bibliothek, wird die neueste Kopie geladen.
- **Action Scheduler** löst Versionskonflikte mit anderen Plugins ebenfalls selbst.

## Release

1. Version in `relaymint.php` an beiden Stellen erhöhen (Header `Version:` und `RELAYMINT_VERSION`).
2. Changelog im `README.md` und [Changelog](Changelog) im Wiki ergänzen.
3. Auf `main` mergen, Tag `vX.Y.Z` pushen – `.github/workflows/release.yml` baut `relaymint.zip`
   und hängt sie an das Release. Siehe [Updates](Updates).

## Wiki

Dieses Wiki liegt im Ordner `wiki/` des Repositorys. Der Workflow
`.github/workflows/wiki-sync.yml` spiegelt den Ordner bei jedem Push auf `main`, der `wiki/**`
ändert, in das GitHub-Wiki. Direkte Änderungen im GitHub-Wiki werden dabei überschrieben.

Einmalig nötig: Wiki in den Repository-Einstellungen aktivieren (**Settings → Features → Wikis**)
und im Web eine erste Seite anlegen – erst dann existiert das Wiki-Repository.
