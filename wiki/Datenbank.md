# Datenbank

Relaymint legt zwei eigene Tabellen an (Präfix je nach Installation, meist `wp_`), speichert seine
Einstellungen in wenigen Optionen und nutzt den Action Scheduler für geplante Aufgaben.

## Tabellen

### `wp_relaymint_email_log` – E-Mail-Protokoll

| Spalte | Inhalt |
|---|---|
| `id` | Primärschlüssel |
| `status` | `queued`, `sending`, `sent`, `failed` oder `blocked` (Index) |
| `subject` | Betreff |
| `from_email` | `From`-Header des Aufrufs, nach dem Versand über eine Relaymint-Verbindung der tatsächliche Absender (`Name <adresse>`) |
| `to_email`, `cc`, `bcc`, `reply_to` | Adressen, kommagetrennt |
| `headers` | Alle Header, einer pro Zeile – nur mit „E-Mail-Inhalt protokollieren“ |
| `message` | Nachrichtentext – nur mit „E-Mail-Inhalt protokollieren“ |
| `content_type` | `text/plain` oder `text/html` |
| `attachments` | JSON-Array der **Dateinamen** der Anhänge |
| `connection` | Verbindungs-ID (`primary`, `c…`) oder leer (PHP `mail()`, wartend, blockiert) |
| `initiator_type` | `plugin`, `mu-plugin`, `theme`, `core` oder `unknown` |
| `initiator_name` | Name des Plugins/Themes bzw. `WordPress` |
| `initiator_file` | Datei, die `wp_mail()` aufgerufen hat |
| `error` | Fehlermeldung bzw. Grund der Blockierung |
| `created_at`, `updated_at` | Zeitstempel in UTC (`created_at` mit Index) |

### `wp_relaymint_queue` – Warteschlange des Hintergrundversands

| Spalte | Inhalt |
|---|---|
| `id` | Primärschlüssel; abgearbeitet wird in aufsteigender Reihenfolge |
| `log_id` | Zugehöriger Protokolleintrag (0, wenn das Protokoll aus ist) |
| `data` | JSON mit `atts` (wp_mail-Argumente, Anhänge als Pfade der Kopien), `initiator` und `attachment_dir`; wird nach dem Versand geleert |
| `status` | `queued`, `sending`, `sent` oder `failed` |
| `attempts` | Anzahl der Versandversuche |
| `created_at` | Zeitpunkt des Einreihens (UTC) |
| `sent_at` | Zeitpunkt des Versandversuchs (UTC) – Grundlage der [Ratenbegrenzung](Rate-Limiting) |

Index: `status_sent (status, sent_at)`.

Beide Tabellen werden mit `dbDelta()` angelegt bzw. aktualisiert – beim Aktivieren und immer dann,
wenn die gespeicherte Schema-Version (`relaymint_db_version`) nicht zur Version im Code passt
(derzeit `1`).

## Optionen

| Option | Autoload | Inhalt |
|---|---|---|
| `relaymint_settings` | ja | Alle Einstellungen: `connections` (je Verbindung, Passwörter/Secrets verschlüsselt), `routing`, `logs`, `misc` |
| `relaymint_db_version` | ja | Schema-Version der Tabellen |
| `relaymint_oauth` | nein | Microsoft-Tokens je Verbindungs-ID: `fingerprint`, `access_token`, `refresh_token` (beide verschlüsselt), `expires`, `account` |
| `relaymint_debug_log` | nein | Debug-Log, max. 200 Einträge (`time`, `level`, `message`) |

### Aufbau von `relaymint_settings`

```php
array(
	'connections' => array(
		'primary'   => array( /* Verbindung */ ),
		'cabc12345' => array( /* zusätzliche Verbindung */ ),
	),
	'routing' => array(
		'enabled' => false,
		'routes'  => array(
			array(
				'enabled'    => true,
				'connection' => 'cabc12345',
				'groups'     => array(   // ODER
					array(               // UND
						array( 'field' => 'subject', 'operator' => 'contains', 'value' => 'Bestellung' ),
					),
				),
			),
		),
	),
	'logs' => array( 'enabled' => true, 'log_content' => false, 'retention_days' => 0 ),
	'misc' => array(
		'domain_check' => false, 'domain_check_allowed' => '', 'domain_check_block_all' => false,
		'do_not_send' => false, 'allow_insecure_ssl' => false, 'debug_log' => false,
		'optimize_sending' => false, 'rate_limit' => false,
		'rate_limit_minute' => 0, 'rate_limit_hour' => 0, 'rate_limit_day' => 0, 'rate_limit_week' => 0,
		'update_channel' => 'stable',
	),
)
```

Felder einer Verbindung mit Standardwerten:

| Schlüssel | Standard | Schlüssel | Standard |
|---|---|---|---|
| `name` | `''` (primär: `Primary`) | `port` | `587` |
| `mailer` | `smtp` | `encryption` | `tls` |
| `from_email` | `''` | `autotls` | `true` |
| `from_name` | `''` | `auth` | `true` |
| `force_from_email` | `true` | `user` | `''` |
| `force_from_name` | `false` | `pass` | `''` (verschlüsselt) |
| `return_path` | `false` | `ms_auth` | `delegated` |
| `host` | `''` | `ms_tenant` | `common` |
| `ms_client_id` | `''` | `ms_client_secret` | `''` (verschlüsselt) |

## Transients

| Transient | Lebensdauer | Zweck |
|---|---|---|
| `relaymint_cleanup_scheduled` | 1 Tag | Merker, dass die tägliche Aufräum-Action geprüft wurde |
| `relaymint_notices_<Benutzer-ID>` | 60 Sekunden | Meldungen über eine Weiterleitung hinweg |
| `relaymint_test_result_<Benutzer-ID>` | 5 Minuten | Ergebnis und Debug-Ausgabe der letzten Test-E-Mail |
| `relaymint_ms_state_<state>` | 15 Minuten | Laufende Microsoft-Anmeldung (Benutzer, Verbindung, PKCE-Verifier) |

## Geplante Aktionen (Action Scheduler)

| Hook | Gruppe | Art | Aufgabe |
|---|---|---|---|
| `relaymint_process_queue` | `relaymint` | asynchron oder einmalig zu einem Zeitpunkt | Warteschlange abarbeiten |
| `relaymint_daily_cleanup` | `relaymint` | wiederkehrend alle 24 Stunden | Protokolleinträge älter als die Aufbewahrungsdauer löschen; erledigte Warteschlangen-Einträge älter als 8 Tage löschen; seit über 1 Stunde hängende Einträge zurücksetzen; ggf. Worker planen |

`relaymint_daily_cleanup` wird beim Aufruf einer Admin-Seite angelegt, falls sie fehlt (höchstens
einmal pro Tag geprüft); der erste Lauf ist eine Stunde danach.

## Dateien

| Pfad | Inhalt |
|---|---|
| `wp-content/uploads/relaymint-queue/` | Kopien von Anhängen wartender E-Mails, je E-Mail ein zufälliger Unterordner; mit `index.php` und `.htaccess` |

## Deaktivieren und Deinstallieren

**Deaktivieren** entfernt nur die geplanten Aktionen der Gruppe `relaymint`.

**Löschen** über die Plugin-Liste (`uninstall.php`) entfernt:

- die Optionen `relaymint_settings`, `relaymint_db_version`, `relaymint_debug_log`, `relaymint_oauth`
- den Transient `relaymint_cleanup_scheduled`
- die Tabellen `wp_relaymint_email_log` und `wp_relaymint_queue`
- ausstehende (`pending`) Aktionen `relaymint_process_queue` und `relaymint_daily_cleanup` direkt aus
  der Tabelle `wp_actionscheduler_actions`
- das Verzeichnis `wp-content/uploads/relaymint-queue`

Die kurzlebigen Transients laufen von selbst ab. Konstanten in der `wp-config.php` musst du selbst
entfernen.
