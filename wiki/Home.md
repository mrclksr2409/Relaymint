# Relaymint Wiki

**Relaymint** ist ein WordPress-Plugin für zuverlässigen E-Mail-Versand. Es ersetzt den
Standardversand über PHP `mail()` durch eine authentifizierte **SMTP-Verbindung** oder durch die
**Microsoft Graph API** (Microsoft 365 / Outlook, OAuth 2.0) – damit E-Mails von WordPress, Plugins
und Themes tatsächlich im Posteingang ankommen. Jede E-Mail wird protokolliert, kann per Regeln über
verschiedene Konten geroutet und optional im Hintergrund mit Ratenbegrenzung versendet werden.

> Aktuelle Version: **0.4.1** · Voraussetzungen: WordPress 6.9+, PHP 7.4+ mit OpenSSL, ein
> SMTP-Konto **oder** ein Microsoft-365-/Outlook.com-Postfach mit App-Registrierung in Microsoft Entra ID

---

## So funktioniert Relaymint in einem Satz

```
wp_mail() ──► Relaymint fängt ab ──► „Nicht senden“? Domain-Prüfung? Hintergrundversand?
          ──► Smart Routing wählt die Verbindung ──► SMTP oder Microsoft Graph ──► E-Mail-Protokoll
```

Mehr dazu unter **[Versandablauf](Versandablauf)**.

## Einstieg

| Seite | Wofür |
|---|---|
| [Installation](Installation) | Plugin hochladen, aktivieren, Voraussetzungen |
| [Schnellstart](Schnellstart) | In fünf Minuten zur ersten Test-E-Mail |
| [Versandablauf](Versandablauf) | In welcher Reihenfolge Relaymint über jede E-Mail entscheidet |

## Einrichtung

| Seite | Wofür |
|---|---|
| [Einstellungen](Einstellungen) | Alle fünf Tabs mit allen Feldern und Standardwerten |
| [SMTP einrichten](SMTP-einrichten) | Host, Port, Verschlüsselung, Authentifizierung, Absender |
| [Microsoft 365 / Outlook einrichten](Microsoft-365-Outlook-einrichten) | App-Registrierung in Entra ID, delegiert oder Nur App |
| [Weitere Verbindungen & Smart Routing](Weitere-Verbindungen-und-Smart-Routing) | Mehrere Konten, Regeln, Bedingungsgruppen |
| [Domain-Prüfung & Nicht senden](Domain-Pruefung-und-Nicht-senden) | Staging-Kopien absichern, Versand komplett stoppen |
| [Hintergrundversand & Warteschlange](Hintergrundversand-und-Warteschlange) | „E-Mail-Versand optimieren“ mit dem Action Scheduler |
| [Rate Limiting](Rate-Limiting) | Höchstzahl an E-Mails pro Minute, Stunde, Tag, Woche |
| [Konstanten in wp-config.php](Konstanten-in-wp-config) | Zugangsdaten außerhalb der Datenbank festlegen |

## Bedienung

| Seite | Wofür |
|---|---|
| [E-Mail-Protokoll](E-Mail-Protokoll) | Liste, Suche, Detailansicht, erneut senden, löschen, Aufbewahrung |
| [Testmail & Werkzeuge](Testmail-und-Werkzeuge) | Test-E-Mail mit Debug-Ausgabe, Debug-Log |

## Referenz

| Seite | Wofür |
|---|---|
| [Sicherheit](Sicherheit) | Verschlüsselung von Passwörtern, Secrets und Tokens; Rechte |
| [Hooks und Filter](Hooks-und-Filter) | Erweitern ohne Code-Änderung |
| [Datenbank](Datenbank) | Tabellen, Optionen, geplante Aktionen, Deinstallation |
| [Updates](Updates) | Updates aus GitHub, Stabil- und Beta-Kanal |
| [Fehlerbehebung](Fehlerbehebung) | Typische Probleme und Lösungen |
| [FAQ](FAQ) | Häufige Fragen |
| [Entwicklung](Entwicklung) | Architektur, Code-Struktur, Mitarbeit |
| [Changelog](Changelog) | Änderungen je Version |

## Funktionen im Überblick

- **SMTP-Versand** – Host, Port, Verschlüsselung (keine / SSL / TLS), Auto-TLS, Authentifizierung,
  Absender-E-Mail und -Name (optional erzwungen), Return-Path
- **Microsoft 365 / Outlook** – Versand über die Microsoft Graph API mit OAuth 2.0 statt SMTP AUTH:
  einmalige Anmeldung mit dem Postfach (Microsoft 365 und Outlook.com) oder App-only-Zugriff mit der
  Anwendungsberechtigung `Mail.Send` (Microsoft 365). Tokens werden automatisch erneuert.
- **Verschlüsselte Zugangsdaten** – SMTP-Passwörter, geheime Clientschlüssel und OAuth-Tokens werden
  mit AES-256-GCM gespeichert oder als Konstanten in der `wp-config.php` definiert
- **E-Mail-Protokoll** – Status, Empfänger, Verbindung, auslösendes Plugin/Theme, Fehlermeldungen;
  optional Inhalt und Header; Suche, Statusfilter, Detailansicht mit abgeschotteter HTML-Vorschau,
  erneut senden, Massenlöschen, automatische Aufbewahrungsdauer
- **Smart Routing** – zusätzliche Verbindungen plus Regeln auf Betreff, Nachricht, Von, An, CC,
  BCC, Antwort an, Header und Auslöser
- **Domain-Prüfung** – SMTP nur auf erlaubten Domains nutzen, optional alles blockieren
- **Nicht senden** – sämtlichen Versand stoppen, E-Mails werden trotzdem protokolliert
- **Unsichere SSL-Zertifikate erlauben** – für SMTP-Server mit selbstsigniertem Zertifikat
- **Debug-Log** – SMTP-Mitschnitte mit maskierten Zugangsdaten; Fehler werden immer aufgezeichnet
- **Hintergrundversand** – Warteschlange über den mitgelieferten Action Scheduler
- **Ratenbegrenzung** – max. E-Mails pro Minute / Stunde / Tag / Woche
- **Test-E-Mail** – über jede Verbindung, mit vollständiger Debug-Ausgabe
- **Deutsche Übersetzung** inklusive
