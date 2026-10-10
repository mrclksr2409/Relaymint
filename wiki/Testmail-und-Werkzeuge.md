# Testmail & Werkzeuge

**Relaymint → Werkzeuge** enthält zwei Bereiche: die **Test-E-Mail** und das **Debug-Log**.

---

## Test-E-Mail senden

| Feld | Beschreibung | Standard |
|---|---|---|
| **Empfänger** | Zieladresse | Deine eigene E-Mail-Adresse |
| **Verbindung** | Primäre Verbindung oder eine zusätzliche Verbindung | Primäre Verbindung |
| **Als HTML senden** | HTML-Variante statt reinem Text | an |

**Test-E-Mail senden** verschickt eine E-Mail mit dem Betreff „Relaymint: Test-E-Mail von
&lt;Website-Name&gt;“ und nennt im Text die verwendete Verbindung.

### Besonderheiten der Test-E-Mail

- Sie geht **direkt** über die gewählte Verbindung – **ohne Smart Routing** und **ohne
  Warteschlange**, auch wenn der Hintergrundversand aktiv ist.
- Die Debug-Ausgabe wird **immer** aufgezeichnet, auch wenn das Debug-Log ausgeschaltet ist.
- *Nicht senden* und die [Domain-Prüfung](Domain-Pruefung-und-Nicht-senden) gelten trotzdem.
- Ist die gewählte Verbindung [unvollständig](Versandablauf#4-verbindung-wählen), wird die
  Test-E-Mail über PHP `mail()` versendet. Erkennbar an einer **leeren Debug-Ausgabe** und an
  „PHP mail()“ in der Spalte *Verbindung* des Protokolls.
- Die Test-E-Mail erscheint wie jede andere E-Mail im [E-Mail-Protokoll](E-Mail-Protokoll).

### Ergebnis

Nach dem Absenden steht oben im Kasten:

- **Erfolg:** „Die Test-E-Mail wurde an … gesendet.“
- **Fehler:** „Die Test-E-Mail konnte nicht gesendet werden.“ mit der Fehlermeldung von PHPMailer
  bzw. Microsoft.

Darunter die aufklappbare **Debug-Ausgabe** – bei einem Fehler ist sie bereits aufgeklappt:

- **SMTP:** die komplette SMTP-Kommunikation (PHPMailer-Debug-Stufe 3, `CLIENT -> SERVER` /
  `SERVER -> CLIENT`). Benutzername und Passwort nach `AUTH LOGIN`, `AUTH PLAIN`, `AUTH XOAUTH2` und
  `AUTH CRAM-MD5` sind durch `********` ersetzt.
- **Microsoft Graph:** Authentifizierungsart, die aufgerufene Graph-URL mit Nachrichtengröße und der
  HTTP-Status der Antwort, bei Fehlern Fehlercode und -text von Microsoft.

Das Ergebnis wird 5 Minuten für deinen Benutzer zwischengespeichert und nach dem Anzeigen gelöscht.

---

## Debug-Log

Das Debug-Log steht unten auf **Werkzeuge** (Link „Debug-Log ansehen“ unter **Einstellungen →
Sonstiges**). Neueste Einträge zuerst, jeweils mit Zeitpunkt und Stufe `INFO` oder `ERROR`.

### Was aufgezeichnet wird

| Stufe | Wann | Beispiele |
|---|---|---|
| **ERROR** | **Immer**, auch bei ausgeschaltetem Debug-Log | „Email failed: …“, Versand-Mitschnitt fehlgeschlagener E-Mails, Fehler bei der Token-Erneuerung, Verschlüsselungsfehler, Fehler beim Einreihen in die Warteschlange, ersetzte PHPMailer-Instanz |
| **INFO** | Nur mit **Einstellungen → Sonstiges → Debug-Log aktivieren** | Mitschnitt jeder erfolgreich gesendeten E-Mail, blockierte E-Mails, „Rate limit reached …“, erfolgreiche Microsoft-Anmeldung |

Mit eingeschaltetem Debug-Log zeichnet Relaymint bei **jeder** E-Mail über eine Relaymint-Verbindung
den Mitschnitt auf – bei vielen E-Mails nur zur Fehlersuche einschalten.

### Eigenschaften

- Gespeichert in der Option `relaymint_debug_log`, **maximal 200 Einträge** – ältere fallen heraus.
- Zugangsdaten in SMTP-Mitschnitten werden vor dem Speichern maskiert.
- **Debug-Log leeren** löscht alle Einträge (nach Rückfrage).
- Die Meldungen im Debug-Log sind englisch.
