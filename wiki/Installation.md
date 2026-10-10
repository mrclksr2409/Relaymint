# Installation

## Voraussetzungen

| Komponente | Mindestens | Hinweis |
|---|---|---|
| WordPress | 6.9 | Vom mitgelieferten Action Scheduler 4.2 vorausgesetzt |
| PHP | 7.4 | Mit der **OpenSSL**-Erweiterung – ohne sie können Passwörter, Secrets und Tokens nicht verschlüsselt gespeichert werden (siehe [Sicherheit](Sicherheit)) |
| WordPress-Salts | – | `AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY` in der `wp-config.php` – daraus wird der Verschlüsselungsschlüssel abgeleitet |
| Postfach | – | Ein SMTP-Konto (beliebiger Anbieter) **oder** ein Microsoft-365-/Outlook.com-Postfach mit App-Registrierung in Microsoft Entra ID |
| Verbindungen nach außen | – | Zum SMTP-Server bzw. zu `login.microsoftonline.com` und `graph.microsoft.com`; für Updates zu GitHub |
| HTTPS im Adminbereich | – | Nur für Microsoft mit „Mit dem Postfach anmelden“ – Microsoft akzeptiert nur HTTPS-Umleitungs-URIs (außer `localhost`) |

Mitgeliefert, nichts zu installieren: [Action Scheduler](https://actionscheduler.org/) 4.2.0,
[Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) 5.6 und
WP-Backend UI 1.0.2 (gemeinsames Admin-Design).

## Plugin installieren

### Variante A: ZIP über den WordPress-Admin (empfohlen)

1. Unter [Releases](https://github.com/mrclksr2409/Relaymint/releases) beim neuesten Release die
   Datei **`relaymint.zip`** herunterladen (nicht *Source code*).
2. In WordPress **Plugins → Neues Plugin hinzufügen → Plugin hochladen** wählen, die ZIP-Datei
   auswählen, **Jetzt installieren**, danach **Aktivieren**.

### Variante B: Per Git

```bash
cd wp-content/plugins
git clone https://github.com/mrclksr2409/Relaymint.git relaymint
```

Danach unter **Plugins** auf **Aktivieren** klicken.

> Der Ordner sollte `relaymint` heißen – so heißt er auch in der Release-ZIP.

## Was beim Aktivieren passiert

- Zwei Datenbanktabellen werden angelegt: `wp_relaymint_email_log` und `wp_relaymint_queue`
  (siehe [Datenbank](Datenbank)).
- Die Standard-Einstellungen werden gespeichert, sofern noch keine existieren.
- Beim ersten Aufruf einer Admin-Seite wird die tägliche Aufräum-Aktion `relaymint_daily_cleanup`
  im Action Scheduler eingeplant.

**Wichtig:** Direkt nach der Aktivierung ist noch keine Verbindung eingerichtet. Solange die primäre
Verbindung unvollständig ist, lässt Relaymint den WordPress-Versand unverändert (PHP `mail()`),
protokolliert die E-Mails aber bereits im [E-Mail-Protokoll](E-Mail-Protokoll) (dort steht als
Verbindung „PHP mail()“).

Im WordPress-Menü erscheint der Eintrag **Relaymint** mit den Unterseiten *Einstellungen*,
*E-Mail-Protokoll* und *Werkzeuge*. Alle Seiten erfordern Administratorrechte (`manage_options`).
In der Plugin-Liste stehen außerdem die Links **Einstellungen** und **Wiki**.

## Zugangsdaten sicher hinterlegen (empfohlen)

Statt im Formular können Host, Benutzer, Passwort oder Microsoft-Zugangsdaten in der
`wp-config.php` stehen. Sie haben dann Vorrang vor dem Formularfeld, das Feld wird gesperrt und das
Passwort landet nie in der Datenbank:

```php
define( 'RELAYMINT_SMTP_HOST', 'smtp.example.com' );
define( 'RELAYMINT_SMTP_USER', 'user@example.com' );
define( 'RELAYMINT_SMTP_PASS', 'secret' );
```

Alle Konstanten: [Konstanten in wp-config.php](Konstanten-in-wp-config).

## Deaktivieren und Deinstallieren

| Aktion | Folge |
|---|---|
| **Deaktivieren** | Alle geplanten Aktionen der Gruppe `relaymint` im Action Scheduler werden entfernt. Einstellungen, Protokoll und noch wartende E-Mails der Warteschlange bleiben erhalten. |
| **Löschen** | Entfernt die Optionen `relaymint_settings`, `relaymint_db_version`, `relaymint_debug_log`, `relaymint_oauth`, den Transient `relaymint_cleanup_scheduled`, beide Tabellen, noch ausstehende Aktionen `relaymint_process_queue` / `relaymint_daily_cleanup` und den Ordner `wp-content/uploads/relaymint-queue`. |

> Beim Löschen gehen Protokoll und Warteschlange unwiderruflich verloren. Noch nicht versendete
> E-Mails vorher unter **Einstellungen → Sonstiges → Warteschlange jetzt abarbeiten** versenden.

Weiter mit dem **[Schnellstart](Schnellstart)**.
