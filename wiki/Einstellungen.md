# Einstellungen

**Relaymint → Einstellungen** ist in fünf Tabs gegliedert. **Jeder Tab hat sein eigenes Formular** –
**Änderungen speichern** speichert nur den gerade geöffneten Tab.

| Tab | Inhalt |
|---|---|
| [Allgemein](#tab-allgemein) | Die primäre Verbindung (SMTP oder Microsoft 365 / Outlook) |
| [Zusätzliche Verbindungen](#tab-zusätzliche-verbindungen) | Weitere Konten für Smart Routing |
| [Smart Routing](#tab-smart-routing) | Regeln, welche E-Mail über welche Verbindung geht |
| [E-Mail-Protokoll](#tab-e-mail-protokoll) | Protokoll an/aus, Inhalt speichern, Aufbewahrungsdauer |
| [Sonstiges](#tab-sonstiges) | Domain-Prüfung, Nicht senden, SSL, Debug-Log, Hintergrundversand, Ratenbegrenzung, Update-Kanal |

---

## Tab: Allgemein

Die **primäre Verbindung** wird für alle E-Mails verwendet, sofern keine Smart-Routing-Regel eine
andere Verbindung wählt. Sie kann nicht gelöscht werden.

### Versandart

| Einstellung | Beschreibung | Standard |
|---|---|---|
| **Senden über** | *SMTP* oder *Microsoft 365 / Outlook*. Blendet die jeweils passenden Felder ein. | SMTP |

### Absender

| Einstellung | Beschreibung | Standard |
|---|---|---|
| **Absender-E-Mail** | Adresse, von der E-Mails gesendet werden. Die meisten SMTP-Anbieter verlangen eine Adresse des angemeldeten Kontos bzw. der Domain. | leer |
| **Absender-E-Mail erzwingen** | Diese Adresse für jede E-Mail verwenden, auch wenn ein Plugin eine andere setzt. Andernfalls ersetzt sie nur die WordPress-Standardadresse. | an |
| **Absendername** | Anzeigename des Absenders | leer |
| **Absendername erzwingen** | Namen immer verwenden; sonst ersetzt er nur „WordPress“ | aus |
| **Return-Path** | Return-Path auf die Absender-E-Mail setzen (nur SMTP) | aus |

### SMTP-Server (nur bei Versandart SMTP)

| Einstellung | Beschreibung | Standard |
|---|---|---|
| **SMTP-Host** | z. B. `smtp.example.com` | leer |
| **Verschlüsselung** | *Keine*, *SSL* oder *TLS* (STARTTLS). Beim Umschalten wird der Port auf 25 / 465 / 587 gesetzt, sofern er noch einen dieser Standardwerte enthält. | TLS |
| **SMTP-Port** | 1–65535; ungültige Werte werden durch 587 ersetzt | 587 |
| **Auto-TLS** | Nur bei Verschlüsselung *Keine*: TLS automatisch nutzen, wenn der Server es anbietet | an |
| **SMTP-Authentifizierung verwenden** | Blendet Benutzername und Passwort ein | an |
| **SMTP-Benutzername** | | leer |
| **SMTP-Passwort** | Wird verschlüsselt gespeichert und nie wieder angezeigt. Leer lassen = gespeichertes Passwort behalten. **Gespeichertes Passwort entfernen** löscht es. | leer |

Details: [SMTP einrichten](SMTP-einrichten).

### Microsoft 365 / Outlook (nur bei dieser Versandart)

| Einstellung | Beschreibung | Standard |
|---|---|---|
| **Umleitungs-URI** | Nur zum Kopieren: `https://deine-seite/wp-admin/admin-post.php`. Muss in der Entra-App als Plattform „Web“ eingetragen sein. | – |
| **Authentifizierung** | *Mit dem Postfach anmelden (delegierte Berechtigung)* oder *Nur App (Anwendungsberechtigung)* | Mit dem Postfach anmelden |
| **Verzeichnis-ID (Mandant)** | Mandanten-ID oder Domain, bei „Mit dem Postfach anmelden“ auch `common`, `organizations`, `consumers` | `common` |
| **Anwendungs-ID (Client)** | Aus der App-Registrierung | leer |
| **Geheimer Clientschlüssel** | Der *Wert* des Geheimnisses; verschlüsselt gespeichert, leer lassen = behalten | leer |
| **Microsoft-Konto** | Nur bei „Mit dem Postfach anmelden“: Status, **Mit Microsoft verbinden**, **Erneut verbinden**, **Trennen** | – |

Details: [Microsoft 365 / Outlook einrichten](Microsoft-365-Outlook-einrichten).

Felder, die per [Konstante in der wp-config.php](Konstanten-in-wp-config) festgelegt sind, sind
gesperrt und tragen den Hinweis „Durch die Konstante … in der wp-config.php festgelegt.“

---

## Tab: Zusätzliche Verbindungen

Liste aller weiteren Verbindungen mit **Name**, **Absender-E-Mail**, **Versand über**
(`host:port` bzw. „Microsoft 365 / Outlook“), **Verbindungs-ID** und den Aktionen *Bearbeiten* /
*Löschen*. **Verbindung hinzufügen** öffnet dasselbe Formular wie im Tab *Allgemein*, zusätzlich mit
dem Pflichtfeld **Name der Verbindung**.

Details: [Weitere Verbindungen & Smart Routing](Weitere-Verbindungen-und-Smart-Routing).

---

## Tab: Smart Routing

| Einstellung | Beschreibung | Standard |
|---|---|---|
| **Smart Routing aktivieren** | Schaltet die Auswertung der Routen ein | aus |
| **Routen** | Je Route: *Aktiv*, *Senden über* (eine zusätzliche Verbindung), Bedingungsgruppen | keine |

Der Tab ist erst nutzbar, wenn mindestens eine zusätzliche Verbindung existiert. Details:
[Weitere Verbindungen & Smart Routing](Weitere-Verbindungen-und-Smart-Routing).

---

## Tab: E-Mail-Protokoll

| Einstellung | Beschreibung | Standard |
|---|---|---|
| **E-Mail-Protokoll aktivieren** | Protokolliert jede E-Mail: Empfänger, Betreff, Status, Verbindung, Auslöser und Fehlermeldungen | an |
| **E-Mail-Inhalt protokollieren** | Speichert Nachrichtentext und Header. Nötig zum Ansehen und erneuten Senden. | aus |
| **Aufbewahrungsdauer** | *Unbegrenzt*, *1 Tag*, *1 Woche*, *1 Monat* (30 Tage), *3 Monate* (90), *6 Monate* (180), *1 Jahr* (365). Ältere Einträge werden einmal täglich gelöscht. | Unbegrenzt |

Details: [E-Mail-Protokoll](E-Mail-Protokoll).

---

## Tab: Sonstiges

| Einstellung | Beschreibung | Standard |
|---|---|---|
| **Domain-Prüfung aktivieren** | SMTP-Einstellungen nur auf erlaubten Domains verwenden | aus |
| ↳ **Erlaubte Domains (kommagetrennt)** | Leer beim Aktivieren → die aktuelle Domain wird eingetragen | leer |
| ↳ **Alle E-Mails blockieren, wenn die Domain nicht passt** | Sonst Versand über den Standard-PHP-Mailer | aus |
| **Versand aller E-Mails stoppen** („Nicht senden“) | Keine E-Mail verlässt die Website; sie erscheinen trotzdem im Protokoll | aus |
| **Selbstsignierte oder ungültige SSL-Zertifikate erlauben** | Deaktiviert die Zertifikatsprüfung der SMTP-Verbindung | aus |
| **Debug-Log aktivieren** | Zeichnet die SMTP-Kommunikation jeder E-Mail auf (Zugangsdaten maskiert). Fehler werden immer aufgezeichnet. | aus |
| **E-Mails im Hintergrund senden** („E-Mail-Versand optimieren“) | Warteschlange über den Action Scheduler. Bei wartenden E-Mails erscheint die Anzahl und **Warteschlange jetzt abarbeiten**. | aus |
| **Anzahl gesendeter E-Mails begrenzen** („E-Mail-Ratenbegrenzung“) | Nur zusammen mit dem Hintergrundversand speicherbar | aus |
| ↳ **Pro Minute / Pro Stunde / Pro Tag / Pro Woche** | Höchstzahl; leer oder 0 = kein Limit | 0 |
| **Update-Kanal** | *Stabil (GitHub-Releases)* oder *Beta (beta-Branch)* | Stabil |

Details: [Domain-Prüfung & Nicht senden](Domain-Pruefung-und-Nicht-senden) ·
[Testmail & Werkzeuge](Testmail-und-Werkzeuge#debug-log) ·
[Hintergrundversand & Warteschlange](Hintergrundversand-und-Warteschlange) ·
[Rate Limiting](Rate-Limiting) · [Updates](Updates)

## Hinweise oben auf den Relaymint-Seiten

| Hinweis | Bedeutung |
|---|---|
| „„Nicht senden“ ist aktiv: Diese Website versendet keine E-Mails.“ | *Nicht senden* ist eingeschaltet |
| „Domain-Prüfung: … ist keine erlaubte Domain, die SMTP-Einstellungen werden nicht verwendet.“ | Die Domain-Prüfung schlägt auf dieser Installation fehl |
| „Die E-Mail-Ratenbegrenzung erfordert „E-Mail-Versand optimieren“ und wurde nicht aktiviert.“ | Ratenbegrenzung ohne Hintergrundversand angehakt |
| „Der reguläre Ausdruck … ist ungültig und wurde übersprungen.“ | Eine Regex-Bedingung im Smart Routing war ungültig |
| „Klicke auf „Mit Microsoft verbinden“ …“ | Delegierte Microsoft-Verbindung gespeichert, aber noch nicht angemeldet |
