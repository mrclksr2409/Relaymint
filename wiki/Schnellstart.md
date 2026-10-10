# Schnellstart

In fünf Schritten von der Installation zur ersten zugestellten Test-E-Mail.

## 1. Plugin installieren und aktivieren

Siehe [Installation](Installation).

## 2. Versandart wählen

**Relaymint → Einstellungen → Allgemein**

Ganz oben unter **Versandart → Senden über** wählst du:

| Versandart | Wann |
|---|---|
| **SMTP** | Funktioniert mit jedem Anbieter, der SMTP anbietet. Du brauchst Host, Port, Verschlüsselung und meist Benutzername und Passwort. |
| **Microsoft 365 / Outlook** | Versand über die Microsoft Graph API mit OAuth 2.0. Benötigt weder SMTP AUTH noch ein Postfach-Passwort, dafür eine App-Registrierung in Microsoft Entra ID. |

Je nach Auswahl werden nur die passenden Felder angezeigt.

## 3. Zugangsdaten eintragen und speichern

**SMTP** (Details: [SMTP einrichten](SMTP-einrichten)):

1. **Absender-E-Mail** eintragen – meist muss sie zum angemeldeten Konto bzw. zur Domain passen.
2. **SMTP-Host**, **Verschlüsselung** (TLS für Port 587, SSL für Port 465) und **SMTP-Port**.
3. **SMTP-Authentifizierung verwenden** (standardmäßig an), **SMTP-Benutzername** und **SMTP-Passwort**.
4. **Änderungen speichern**.

**Microsoft 365 / Outlook** (Details: [Microsoft 365 / Outlook einrichten](Microsoft-365-Outlook-einrichten)):

1. App in Entra ID registrieren, die angezeigte **Umleitungs-URI** als Plattform „Web“ eintragen,
   geheimen Clientschlüssel erstellen, Graph-Berechtigungen erteilen.
2. **Authentifizierung**, **Verzeichnis-ID (Mandant)**, **Anwendungs-ID (Client)** und
   **Geheimer Clientschlüssel** eintragen und speichern.
3. Bei „Mit dem Postfach anmelden“: **Mit Microsoft verbinden** klicken und mit dem Postfach anmelden.

## 4. Test-E-Mail senden

**Relaymint → Werkzeuge → Test-E-Mail senden**

Empfänger ist vorausgefüllt mit deiner eigenen Adresse. Verbindung wählen, **Test-E-Mail senden**
klicken. Das Ergebnis erscheint oben im Kasten, darunter die **Debug-Ausgabe** – bei einem Fehler
ist sie automatisch aufgeklappt. Mehr: [Testmail & Werkzeuge](Testmail-und-Werkzeuge).

## 5. Protokoll prüfen

**Relaymint → E-Mail-Protokoll** zeigt die Test-E-Mail mit Status *Gesendet* und der verwendeten
Verbindung. Ab jetzt landet hier jede E-Mail der Website.

## Optional als Nächstes

- **Inhalte protokollieren**, um E-Mails ansehen und erneut senden zu können:
  [E-Mail-Protokoll](E-Mail-Protokoll)
- **Aufbewahrungsdauer** festlegen, damit das Protokoll nicht unbegrenzt wächst
- **Weitere Konten** und Regeln: [Weitere Verbindungen & Smart Routing](Weitere-Verbindungen-und-Smart-Routing)
- **Staging absichern**: [Domain-Prüfung & Nicht senden](Domain-Pruefung-und-Nicht-senden)
- **Schnellere Seiten** durch Versand im Hintergrund: [Hintergrundversand & Warteschlange](Hintergrundversand-und-Warteschlange)
- **Passwort aus der Datenbank heraushalten**: [Konstanten in wp-config.php](Konstanten-in-wp-config)
