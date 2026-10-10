# SMTP einrichten

**Relaymint → Einstellungen → Allgemein → Senden über: SMTP**

SMTP funktioniert mit jedem Anbieter. Die Werte für Host, Port und Verschlüsselung findest du in der
Dokumentation deines Mail-Anbieters bzw. Hosters.

## Felder

### Absender

| Feld | Hinweis |
|---|---|
| **Absender-E-Mail** | Die meisten Anbieter akzeptieren nur eine Adresse des angemeldeten Kontos bzw. der eigenen Domain. |
| **Absender-E-Mail erzwingen** | Standardmäßig **an**: Jede E-Mail geht mit dieser Adresse raus, auch wenn ein Plugin (z. B. ein Formular-Plugin) eine andere setzt. Ausgeschaltet ersetzt Relaymint nur die WordPress-Standardadresse `wordpress@…`. |
| **Absendername** / **erzwingen** | Ohne Erzwingen wird nur der Standardname „WordPress“ ersetzt. |
| **Return-Path** | Setzt den Envelope-Sender (PHPMailer `Sender`) auf die Absender-E-Mail. Unzustellbarkeitsmeldungen gehen dann an diese Adresse. |

### SMTP-Server

| Feld | Hinweis |
|---|---|
| **SMTP-Host** | Hostname des Servers. Ohne Host gilt die Verbindung als unvollständig und WordPress versendet über PHP `mail()`. |
| **Verschlüsselung** | **TLS** = STARTTLS, empfohlen für Port **587**. **SSL** = implizites TLS, empfohlen für Port **465**. **Keine** = unverschlüsselt, typisch Port **25**. |
| **SMTP-Port** | Wird beim Umschalten der Verschlüsselung automatisch auf 25 / 465 / 587 gesetzt, solange er noch einen dieser Werte enthält. Ein eigener Port bleibt unangetastet. |
| **Auto-TLS** | Nur relevant bei Verschlüsselung *Keine*: PHPMailer wechselt automatisch auf TLS, wenn der Server es anbietet. Deaktivieren, wenn der Server TLS anbietet, es aber nicht korrekt nutzt. Bei SSL/TLS ist Auto-TLS immer an. |
| **SMTP-Authentifizierung verwenden** | Standardmäßig an. Aus nur bei Servern, die ohne Anmeldung annehmen (z. B. ein interner Relay). |
| **SMTP-Benutzername** | Meist die vollständige E-Mail-Adresse |
| **SMTP-Passwort** | Siehe unten |

## Das Passwort

- Das Passwort wird **verschlüsselt** gespeichert (AES-256-GCM, siehe [Sicherheit](Sicherheit))
  und nach dem Speichern **nie wieder angezeigt** – im Feld steht nur `••••••••`.
- **Leer lassen** behält das gespeicherte Passwort.
- **Gespeichertes Passwort entfernen** anhaken und speichern löscht es.
- Sonderzeichen bleiben erhalten; nur Zeilenumbrüche werden entfernt.
- Noch besser: das Passwort gar nicht speichern, sondern in der `wp-config.php` definieren:

```php
define( 'RELAYMINT_SMTP_PASS', 'secret' );
```

Für zusätzliche Verbindungen lautet die Konstante `RELAYMINT_SMTP_PASS_<ID>` – der genaue Name steht
als Tipp unter dem Passwortfeld. Alle Konstanten: [Konstanten in wp-config.php](Konstanten-in-wp-config).

> Wird das Passwort trotz Speichern nicht übernommen, fehlen OpenSSL oder die WordPress-Salts – das
> Debug-Log enthält dann den Fehler „Could not encrypt the SMTP password: OpenSSL or WordPress salts
> are missing.“ Siehe [Fehlerbehebung](Fehlerbehebung).

## Selbstsignierte Zertifikate

Verwendet dein SMTP-Server ein selbstsigniertes oder ungültiges Zertifikat, schlägt der
TLS-Handshake fehl. Unter **Sonstiges → Selbstsignierte oder ungültige SSL-Zertifikate erlauben**
schaltet Relaymint für SMTP-Verbindungen die Prüfung ab (`verify_peer`, `verify_peer_name` aus,
`allow_self_signed` an).

> Das macht die Verbindung anfällig für Abhören – nur aktivieren, wenn es nicht anders geht.
> Die Einstellung gilt für **alle** SMTP-Verbindungen, nicht für Microsoft Graph.

## Testen

**Relaymint → Werkzeuge → Test-E-Mail senden** – die Debug-Ausgabe zeigt die komplette
SMTP-Kommunikation (PHPMailer-Debug-Stufe 3), Zugangsdaten sind maskiert. Siehe
[Testmail & Werkzeuge](Testmail-und-Werkzeuge).
