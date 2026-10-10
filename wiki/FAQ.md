# FAQ

### Was macht Relaymint anders als der normale WordPress-Versand?
WordPress versendet standardmäßig über PHP `mail()` – ohne Anmeldung beim Mailserver, was oft im
Spam endet oder gar nicht ankommt. Relaymint versendet über ein authentifiziertes SMTP-Konto oder
über Microsoft Graph und protokolliert dabei jede E-Mail.

### Muss ich bestehende Plugins anpassen?
Nein. Relaymint greift in `wp_mail()` ein; jedes Plugin und Theme, das `wp_mail()` benutzt, profitiert
automatisch.

### Funktioniert es mit jedem SMTP-Anbieter?
Ja, sofern er Standard-SMTP mit keiner Verschlüsselung, SSL oder STARTTLS (TLS) und optional
Benutzername/Passwort anbietet. Spezielle Anmeldeverfahren außer der Microsoft-Graph-Anbindung gibt
es nicht.

### SMTP oder Microsoft 365 / Outlook – was soll ich bei Microsoft nehmen?
Die Versandart **Microsoft 365 / Outlook**. Sie braucht weder SMTP AUTH noch ein Postfach-Passwort,
funktioniert auch mit privaten Outlook.com-Konten (delegiert) und erneuert ihre Tokens selbst.
Siehe [Microsoft 365 / Outlook einrichten](Microsoft-365-Outlook-einrichten).

### Delegiert oder „Nur App“?
**Delegiert** („Mit dem Postfach anmelden“), wenn du ein einzelnes Postfach nutzt oder Outlook.com
hast – einmal anmelden, fertig. **Nur App**, wenn es ohne interaktive Anmeldung gehen soll und du
Administrator im Microsoft-365-Mandanten bist; dann die App per Application Access Policy auf das
Postfach beschränken.

### Kann ich mehrere Konten gleichzeitig nutzen?
Ja: eine primäre Verbindung plus beliebig viele [zusätzliche Verbindungen](Weitere-Verbindungen-und-Smart-Routing).
Welche E-Mail über welche Verbindung geht, steuern Smart-Routing-Regeln oder der Filter
`relaymint_route_connection`. SMTP- und Microsoft-Verbindungen lassen sich mischen.

### Gibt es ein automatisches Ausweichen auf eine zweite Verbindung, wenn die erste scheitert?
Nein. Schlägt der Versand fehl, wird die E-Mail als *Fehlgeschlagen* protokolliert. Ist eine
Verbindung allerdings **unvollständig** eingerichtet, versendet WordPress über PHP `mail()`.

### Werden fehlgeschlagene E-Mails automatisch wiederholt?
Nein. Mit protokolliertem Inhalt kannst du sie im [E-Mail-Protokoll](E-Mail-Protokoll) über
**Erneut senden** nachholen (ohne Anhänge).

### Speichert Relaymint den Inhalt meiner E-Mails?
Standardmäßig nur Metadaten (Empfänger, Betreff, Status, Verbindung, Auslöser, Fehler). Inhalt und
Header nur, wenn du **E-Mail-Inhalt protokollieren** einschaltest. Anhänge werden nie gespeichert,
nur ihre Dateinamen.

### Wie verhindere ich, dass eine Staging-Kopie echte E-Mails verschickt?
Mit der [Domain-Prüfung](Domain-Pruefung-und-Nicht-senden) auf der Live-Seite (nur die Live-Domain
erlauben, ggf. **Alle E-Mails blockieren**) – sie greift automatisch, sobald die Kopie unter einer
anderen Domain läuft. Alternativ auf der Kopie **Nicht senden** einschalten.

### Macht der Hintergrundversand meine Seite schneller?
Seiten, die beim Aufruf E-Mails versenden (Formulare, Checkout, Registrierung), warten nicht mehr auf
den Mailserver. Die E-Mail geht kurz danach über den Action Scheduler raus. Siehe
[Hintergrundversand & Warteschlange](Hintergrundversand-und-Warteschlange).

### Kann ich Relaymint neben einem anderen SMTP-Plugin betreiben?
Nicht sinnvoll. Beide greifen in denselben Versand ein; beantwortet das andere Plugin `pre_wp_mail`
selbst, tut Relaymint nichts, und ein Plugin, das PHPMailer ersetzt, verhindert den Versand über
Microsoft. Nur ein Mail-Plugin aktiv lassen.

### Welche Sprache hat die Oberfläche?
Englisch als Grundlage, eine deutsche Übersetzung liegt bei (`languages/relaymint-de_DE`). Die
Meldungen im Debug-Log sind englisch.

### Was kostet Relaymint?
Das Plugin ist kostenlos und steht unter GPL-2.0-or-later. Kosten entstehen höchstens bei deinem
Mail-Anbieter.

### Wo melde ich Fehler oder Wünsche?
Unter [Issues](https://github.com/mrclksr2409/Relaymint/issues) im Repository.
