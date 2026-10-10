# Changelog

Die maßgebliche Fassung steht im [README](https://github.com/mrclksr2409/Relaymint#changelog).

## 0.4.1 — 2026-10-10

**Geändert**
- Author URI zeigt jetzt auf das GitHub-Profil.
- Neuer Link **Wiki** in der Plugin-Zeile der Plugin-Liste (führt zu diesem Wiki).

## 0.4.0 — 2026-10-10

**Neu**
- Versandart **Microsoft 365 / Outlook** über die Microsoft Graph API (OAuth 2.0): Anmeldung mit dem
  Postfach (delegiert, Autorisierungscode mit PKCE, Microsoft 365 und Outlook.com) oder App-only-Zugriff
  (Client Credentials, Microsoft 365). Verfügbar für primäre und zusätzliche Verbindungen, Smart
  Routing und die Test-E-Mail.
- Verschlüsselte Speicherung von geheimen Clientschlüsseln und OAuth-Tokens, automatische
  Token-Erneuerung, Konstanten `RELAYMINT_MAILER` / `RELAYMINT_MS_*`.

**Geändert**
- Die Verbindungseinstellungen beginnen mit der Auswahl der Versandart; die Verbindungsliste zeigt,
  wie jede Verbindung versendet.

## 0.3.0 — 2026-10-09

**Neu**
- Einstellung **Update-Kanal** (Tab *Sonstiges*): Stabil (GitHub-Releases von `main`) oder Beta
  (`beta`-Branch).

## 0.2.0 — 2026-10-09

**Geändert**
- Einheitliches Admin-Design über die mitgelieferte WP-Backend UI 1.0.2 (`libraries/wp-backend-ui/`):
  gemeinsamer Seitenkopf mit Versions-Badge, Tabs, Karten und Design-Tokens auf Einstellungen,
  E-Mail-Protokoll und Werkzeuge.
- E-Mail-Protokoll: Status als Badges; die Detailansicht zeigt den Eintrag als Karte mit
  Schlüssel/Wert-Liste, Header und Nachricht in Karten, Zurück/Erneut senden im Seitenkopf.
- Werkzeuge: Test-E-Mail und Debug-Log als Karten, Testergebnisse als Hinweisboxen.
- Smart-Routing-Routen sowie die Unteroptionen von Domain-Prüfung und Ratenbegrenzung als Karten.
- Rückfragen nutzen `data-wpb-confirm` der Bibliothek; `assets/css/admin.css` enthält nur noch
  Relaymint-spezifisches Layout.

## 0.1.0 — 2026-10-09

**Neu**
- SMTP-Versand mit verschlüsselten Zugangsdaten und Überschreiben per `wp-config.php`.
- E-Mail-Protokoll mit Suche, Filtern, Detailansicht, Erneut senden und Aufbewahrungsdauer.
- Smart Routing mit zusätzlichen Verbindungen.
- Domain-Prüfung, Nicht senden, Option für unsichere SSL-Zertifikate, Debug-Log.
- Hintergrundversand über den Action Scheduler und E-Mail-Ratenbegrenzung.
- Test-E-Mail, deutsche Übersetzung.
