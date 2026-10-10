# Microsoft 365 / Outlook einrichten

Mit der Versandart **Microsoft 365 / Outlook** versendet Relaymint über die
[Microsoft Graph `sendMail` API](https://learn.microsoft.com/graph/api/user-sendmail) mit OAuth 2.0
statt über SMTP. PHPMailer baut die Nachricht wie gewohnt als MIME, Relaymint übergibt sie
Base64-kodiert an Graph – HTML, Anhänge, CC, BCC und Antwort-an funktionieren wie bei SMTP.
**SMTP AUTH muss für das Postfach nicht aktiviert sein**, ein Postfach-Passwort wird nicht benötigt.

Verfügbar für die primäre Verbindung, zusätzliche Verbindungen, Smart Routing und die Test-E-Mail.

---

## Delegiert oder Nur App?

| | **Mit dem Postfach anmelden** (delegiert) | **Nur App** (Anwendungsberechtigung) |
|---|---|---|
| Konten | Microsoft 365 (Geschäfts-/Schulkonto) **und** private Outlook.com-Konten | Nur Microsoft 365 |
| Anmeldung | Einmalig mit dem Postfach über **Mit Microsoft verbinden** | Keine |
| Graph-Berechtigungen | `Mail.Send`, `User.Read`, `offline_access` (delegiert) | `Mail.Send` (Anwendung) mit Administratorzustimmung |
| OAuth-Ablauf | Autorisierungscode mit PKCE (S256), danach Refresh-Token | Client Credentials, Scope `https://graph.microsoft.com/.default` |
| Mandant | Mandanten-ID/Domain oder `common`, `organizations`, `consumers` | **Nur** konkrete Mandanten-ID oder -Domain |
| Gesendet über | `POST /v1.0/me/sendMail` | `POST /v1.0/users/{Absender-E-Mail}/sendMail` |
| Absender | Angemeldetes Postfach (Absender-E-Mail leer lassen); andere Adressen brauchen „Senden als“ in Exchange | Die **Absender-E-Mail** muss ein Postfach im Mandanten sein (Pflicht) |
| Umleitungs-URI nötig | Ja | Nein |

---

## Schritt 1: App in Microsoft Entra ID registrieren

1. Im [Microsoft Entra Admin Center](https://entra.microsoft.com/) **App registrations → New
   registration** (App-Registrierungen → Neue Registrierung) öffnen.
2. **Name:** frei wählbar, z. B. „Relaymint – meine-seite.de“.
3. **Supported account types** (Unterstützte Kontotypen):
   - nur deine Organisation, **oder**
   - *Accounts in any organizational directory and personal Microsoft accounts*, wenn du ein
     privates **Outlook.com**-Konto verwenden willst.
4. **Redirect URI** (Umleitungs-URI) – nur für „Mit dem Postfach anmelden“:
   - Plattform: **Web**
   - Wert: genau die URI, die Relaymint im Feld **Umleitungs-URI** anzeigt, also
     `https://deine-seite.de/wp-admin/admin-post.php`
   - Microsoft akzeptiert nur **HTTPS** (Ausnahme: `localhost`). Läuft der Adminbereich ohne HTTPS,
     zeigt Relaymint einen Warnhinweis unter dem Feld.
5. **Register** klicken.
6. Auf der Übersichtsseite der App notieren:
   - **Application (client) ID** → Feld *Anwendungs-ID (Client)*
   - **Directory (tenant) ID** → Feld *Verzeichnis-ID (Mandant)*

> Relaymint nutzt bewusst die URL `admin-post.php` **ohne** Parameter, weil Microsoft nicht für alle
> Kontotypen Query-Strings in Umleitungs-URIs erlaubt. Die Antwort wird am `state`-Parameter erkannt.

## Schritt 2: Geheimen Clientschlüssel erstellen

1. **Certificates & secrets → Client secrets → New client secret** (Zertifikate & Geheimnisse).
2. Beschreibung und Ablaufdatum wählen, **Add**.
3. Sofort den **Value** (Wert) kopieren – **nicht** die *Secret ID*. Der Wert ist später nicht mehr
   sichtbar.

> Geheime Clientschlüssel laufen ab. Lege rechtzeitig einen neuen an und trage ihn in Relaymint ein
> (siehe [Wartung](#wartung)).

## Schritt 3: API-Berechtigungen erteilen

**API permissions → Add a permission → Microsoft Graph** (API-Berechtigungen):

**Für „Mit dem Postfach anmelden“** → *Delegated permissions*:

| Berechtigung | Wozu |
|---|---|
| `Mail.Send` | E-Mails als angemeldeter Benutzer senden |
| `User.Read` | Adresse des angemeldeten Postfachs lesen (`/me`) |
| `offline_access` | Refresh-Token erhalten, damit der Zugriff dauerhaft erneuert werden kann |

**Für „Nur App“** → *Application permissions*:

| Berechtigung | Wozu |
|---|---|
| `Mail.Send` | E-Mails als beliebiges Postfach des Mandanten senden |

Danach **Grant admin consent** (Administratorzustimmung erteilen) klicken.

> **Wichtig bei „Nur App“:** `Mail.Send` als Anwendungsberechtigung erlaubt ohne Einschränkung den
> Versand aus **jedem** Postfach des Mandanten. Beschränke die App mit einer
> [Application Access Policy bzw. RBAC for Applications](https://learn.microsoft.com/exchange/permissions-exo/application-rbac)
> in Exchange Online auf das Absender-Postfach.

## Schritt 4: Verbindung in Relaymint anlegen

**Relaymint → Einstellungen → Allgemein** (primäre Verbindung) oder
**Zusätzliche Verbindungen → Verbindung hinzufügen**:

1. **Senden über:** *Microsoft 365 / Outlook*
2. **Authentifizierung:** *Mit dem Postfach anmelden* oder *Nur App*
3. **Verzeichnis-ID (Mandant):**
   - Mandanten-ID (GUID) oder Domain, z. B. `contoso.onmicrosoft.com`
   - bei „Mit dem Postfach anmelden“ alternativ `common` (beliebiges Konto), `organizations`
     (nur Geschäfts-/Schulkonten) oder `consumers` (nur Outlook.com)
   - bei „Nur App“ **zwingend** die eigene Mandanten-ID/Domain
   - leer gelassen wird `common` gespeichert
4. **Anwendungs-ID (Client)** und **Geheimer Clientschlüssel** eintragen.
5. **Absender-E-Mail:**
   - delegiert: **leer lassen**, dann wird als angemeldetes Postfach gesendet; andere Adressen
     brauchen die Berechtigung „Senden als“ in Exchange
   - Nur App: Adresse des sendenden Postfachs im Mandanten (Pflicht)
6. **Änderungen speichern** bzw. **Verbindung speichern**.

## Schritt 5 (nur delegiert): Mit Microsoft verbinden

Nach dem Speichern erscheint der Hinweis „Klicke auf „Mit Microsoft verbinden“, um dich mit dem
Postfach anzumelden. Bis die Verbindung autorisiert ist, werden keine E-Mails über Microsoft versendet.“

1. In der Zeile **Microsoft-Konto** auf **Mit Microsoft verbinden** klicken.
2. Bei Microsoft das **sendende Postfach** auswählen bzw. anmelden (Relaymint fordert immer die
   Kontoauswahl an) und den Berechtigungen zustimmen.
3. Microsoft leitet zurück zu Relaymint: „Microsoft-Konto verbunden. Sende eine Test-E-Mail, um die
   Verbindung zu prüfen.“ Als Status erscheint die Adresse des Postfachs.

Die Anmeldung muss **innerhalb von 15 Minuten** und vom **selben WordPress-Benutzer** abgeschlossen
werden, der sie gestartet hat. Ungespeicherte Änderungen vorher speichern.

## Schritt 6: Testen

**Relaymint → Werkzeuge → Test-E-Mail senden**, Verbindung auswählen. Die Debug-Ausgabe zeigt z. B.:

```
Sending via Microsoft Graph (delegated, connection 'primary').
POST https://graph.microsoft.com/v1.0/me/sendMail (12.345 bytes MIME)
Microsoft Graph responded with HTTP 202.
```

---

## Wie Relaymint mit Tokens umgeht

- **Speicherung:** Access- und Refresh-Token liegen verschlüsselt in der Option `relaymint_oauth`
  (nicht automatisch geladen), zusammen mit Ablaufzeit und Postfach-Adresse. Siehe [Sicherheit](Sicherheit).
- **Erneuerung:** Ein Access-Token wird bis 60 Sekunden vor Ablauf wiederverwendet, danach automatisch
  erneuert – delegiert per Refresh-Token, bei „Nur App“ per Client Credentials. Gibt Microsoft einen
  neuen Refresh-Token aus, wird er übernommen.
- **401 beim Senden:** Relaymint holt einmalig ein frisches Token und versucht es erneut.
- **Bindung an die Einstellungen:** Tokens gehören zu der Kombination aus *Authentifizierung*,
  *Mandant* und *Client-ID*. Änderst du eines davon, werden die gespeicherten Tokens verworfen –
  bei „Mit dem Postfach anmelden“ musst du dich **neu verbinden**. Ein neuer **geheimer
  Clientschlüssel** allein erfordert keine neue Anmeldung.
- **Trennen** löscht die gespeicherten Tokens der Verbindung. **Erneut verbinden** startet die
  Anmeldung neu, z. B. um ein anderes Postfach zu verwenden.
- Beim **Löschen** einer zusätzlichen Verbindung werden auch ihre Tokens gelöscht.

## Wartung

| Situation | Was tun |
|---|---|
| Geheimer Clientschlüssel läuft ab | In Entra neuen Schlüssel anlegen, Wert in Relaymint eintragen, speichern. Keine neue Anmeldung nötig. |
| Anderes Postfach verwenden (delegiert) | **Erneut verbinden** und mit dem neuen Postfach anmelden |
| Mandant, Client-ID oder Authentifizierung geändert | Delegiert: **Mit Microsoft verbinden** erneut ausführen |
| WordPress-Salts geändert | Tokens und Schlüssel sind nicht mehr entschlüsselbar: Schlüssel neu eintragen und neu verbinden |

## Grenzen und Hinweise

- Graph nimmt laut README Nachrichten bis etwa **4 MB** pro Anfrage an – einschließlich
  Base64-kodierter Anhänge.
- Der Server muss `login.microsoftonline.com` und `graph.microsoft.com` per HTTPS erreichen.
- Relaymint ersetzt für Microsoft-Verbindungen die globale PHPMailer-Instanz durch eine eigene
  Unterklasse. Ersetzt ein anderes Plugin PHPMailer danach erneut, schlägt die E-Mail fehl und das
  Debug-Log meldet „Microsoft connection cannot be used: the PHPMailer instance was replaced by
  another plugin.“
- Bei „Nur App“ dient die tatsächliche Absenderadresse als Postfach in der Graph-URL. Lass daher
  **Absender-E-Mail erzwingen** eingeschaltet (Standard), sonst würde eine von einem Plugin gesetzte
  Absenderadresse als Postfach verwendet.
- Eine Microsoft-Verbindung, die noch nicht vollständig ist (delegiert: nicht verbunden; Nur App:
  keine gültige Absender-E-Mail oder Mandant `common` usw.), wird nicht verwendet – WordPress
  versendet dann über PHP `mail()`. Siehe [Versandablauf](Versandablauf#4-verbindung-wählen).

Fehlermeldungen und Lösungen: [Fehlerbehebung](Fehlerbehebung#microsoft-365--outlook).
