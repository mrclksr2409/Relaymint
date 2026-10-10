# Versandablauf

Relaymint hängt sich mit höchster Priorität (`PHP_INT_MAX`) in den WordPress-Filter `pre_wp_mail`
ein und entscheidet dort für **jede** E-Mail, was passiert. Die Reihenfolge ist fest:

```
wp_mail()
  │
  ├─ Hat ein anderes Plugin pre_wp_mail bereits beantwortet? ──► Relaymint greift nicht ein
  │
  ├─ 1. „Nicht senden“ aktiv? ──────────────► Status „Blockiert“, wp_mail() liefert true
  │
  ├─ 2. Domain-Prüfung: Domain nicht erlaubt?
  │        ├─ „Alle E-Mails blockieren“ an ─► Status „Blockiert“, wp_mail() liefert false
  │        └─ sonst ─────────────────────────► Versand über PHP mail(), ohne Relaymint-Verbindung
  │
  ├─ 3. „E-Mail-Versand optimieren“ an? ────► Warteschlange, Status „In Warteschlange“,
  │                                            wp_mail() liefert sofort true
  │
  ├─ 4. Verbindung wählen: Smart Routing (sonst primäre Verbindung)
  │        └─ Verbindung unvollständig? ────► Versand über PHP mail()
  │
  └─ 5. Versand über SMTP oder Microsoft Graph ──► Status „Gesendet“ oder „Fehlgeschlagen“
```

Die **Ratenbegrenzung** greift erst beim Abarbeiten der Warteschlange (Schritt 3) und setzt daher
„E-Mail-Versand optimieren“ voraus – siehe [Rate Limiting](Rate-Limiting).

## Die Schritte im Detail

### 1. Nicht senden
Blockiert jede E-Mail. Sie erscheint im Protokoll als *Blockiert* mit dem Grund
„Durch die Einstellung „Nicht senden“ blockiert.“. `wp_mail()` liefert `true`, damit aufrufende
Plugins keinen Fehler anzeigen – änderbar mit dem Filter
[`relaymint_do_not_send_result`](Hooks-und-Filter). Siehe
[Domain-Prüfung & Nicht senden](Domain-Pruefung-und-Nicht-senden).

### 2. Domain-Prüfung
Nur aktiv, wenn eingeschaltet. Läuft die Website nicht auf einer erlaubten Domain, wird entweder
alles blockiert oder die E-Mail **sofort** über den Standard-PHP-Mailer verschickt – ohne
Warteschlange und ohne Relaymint-Verbindung.

### 3. Hintergrundversand
Die E-Mail wird in die Tabelle `wp_relaymint_queue` geschrieben und später vom Action Scheduler
versendet. Beim Versand aus der Warteschlange durchläuft sie die Schritte 1, 2, 4 und 5 erneut –
„Nicht senden“ und die Domain-Prüfung gelten also auch noch zum Versandzeitpunkt. Test-E-Mails
und erneut gesendete E-Mails umgehen die Warteschlange immer. Siehe
[Hintergrundversand & Warteschlange](Hintergrundversand-und-Warteschlange).

### 4. Verbindung wählen
Ist Smart Routing aktiv, werden die Routen von oben nach unten geprüft; die erste passende gewinnt.
Sonst – oder wenn keine Route passt – wird die **primäre Verbindung** genutzt. Danach läuft der Filter
[`relaymint_route_connection`](Hooks-und-Filter).

Ist die gewählte Verbindung **unvollständig**, lässt Relaymint PHPMailer unverändert und WordPress
versendet über PHP `mail()`. Im Protokoll steht dann als Verbindung „PHP mail()“. Als vollständig gilt:

| Versandart | Bedingung |
|---|---|
| SMTP | SMTP-Host ist ausgefüllt |
| Microsoft, „Mit dem Postfach anmelden“ | Client-ID und geheimer Clientschlüssel vorhanden **und** das Postfach ist verbunden |
| Microsoft, „Nur App“ | Client-ID und geheimer Clientschlüssel vorhanden, gültige Absender-E-Mail, Mandant ist **nicht** `common`, `organizations` oder `consumers` |

Kurz vor der Übergabe an PHPMailer feuert die Action [`relaymint_before_send`](Hooks-und-Filter).

### 5. Versand
- **SMTP:** PHPMailer wird auf SMTP umgestellt (Host, Port, Verschlüsselung, Auth, ggf. Return-Path
  und unsichere Zertifikate) – siehe [SMTP einrichten](SMTP-einrichten).
- **Microsoft:** PHPMailer baut die MIME-Nachricht wie gewohnt, Relaymint übergibt sie an
  Microsoft Graph `sendMail` – siehe [Microsoft 365 / Outlook einrichten](Microsoft-365-Outlook-einrichten).

Ergebnis und ggf. Fehlermeldung landen im [E-Mail-Protokoll](E-Mail-Protokoll), der
Versand-Mitschnitt im [Debug-Log](Testmail-und-Werkzeuge#debug-log).

## Absender (From) und Return-Path

Für jede Verbindung gilt (nur wenn die Verbindung tatsächlich verwendet wird):

| Einstellung | Wirkung |
|---|---|
| **Absender-E-Mail** + **Absender-E-Mail erzwingen** (Standard: an) | Diese Adresse wird für jede E-Mail verwendet, auch wenn ein Plugin eine andere setzt |
| **Absender-E-Mail** ohne Erzwingen | Ersetzt nur die WordPress-Standardadresse (`wordpress@…`) |
| **Absendername** + **Absendername erzwingen** (Standard: aus) | Wird immer verwendet |
| **Absendername** ohne Erzwingen | Ersetzt nur den Standardnamen „WordPress“ |
| **Return-Path** (nur SMTP) | Setzt den Envelope-Sender auf die Absender-E-Mail; Unzustellbarkeitsmeldungen gehen dorthin |

Eine leere oder ungültige Absender-E-Mail wird ignoriert.
