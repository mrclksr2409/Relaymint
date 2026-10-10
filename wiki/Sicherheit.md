# Sicherheit

## Verschlüsselung von Passwörtern, Secrets und Tokens

Relaymint speichert keine Zugangsdaten im Klartext in der Datenbank. Verschlüsselt werden:

| Wert | Gespeichert in |
|---|---|
| SMTP-Passwörter | Option `relaymint_settings` |
| Geheime Clientschlüssel (Microsoft) | Option `relaymint_settings` |
| OAuth Access- und Refresh-Tokens | Option `relaymint_oauth` |

### Verfahren

- **Algorithmus:** AES-256-GCM (authentifizierte Verschlüsselung) über die PHP-Erweiterung OpenSSL
- **Schlüssel:** SHA-256 über `relaymint|` + `AUTH_KEY` + `SECURE_AUTH_KEY` + `LOGGED_IN_KEY`
  aus der `wp-config.php`. Der Schlüssel selbst wird nirgends gespeichert.
- **IV:** 12 zufällige Bytes pro Verschlüsselung, dazu ein 16-Byte-Authentifizierungs-Tag
- **Format:** `rmenc:` + Base64(IV + Tag + Chiffretext)

Manipulierte oder mit einem anderen Schlüssel erzeugte Werte werden beim Entschlüsseln verworfen
(leerer Wert statt falscher Daten).

### Folgen für den Betrieb

| Situation | Folge |
|---|---|
| **Salts geändert** (z. B. neue Schlüssel nach einem Sicherheitsvorfall) | Gespeicherte Passwörter, Secrets und Tokens sind nicht mehr entschlüsselbar. SMTP-Passwörter und Client-Secrets neu eintragen, Microsoft-Konten neu verbinden. |
| **Datenbank auf eine andere Installation kopiert** | Nur entschlüsselbar, wenn dort dieselben drei Salts gelten |
| **OpenSSL fehlt oder keine Salts definiert** | Es kann nichts verschlüsselt werden: Das Passwort bzw. Secret wird **leer** gespeichert, das Debug-Log meldet „Could not encrypt the SMTP password: OpenSSL or WordPress salts are missing.“ (die Meldung erscheint auch bei Secrets und Tokens) |

### Noch sicherer: Konstanten

Definierst du Passwort bzw. Secret als [Konstante in der wp-config.php](Konstanten-in-wp-config),
liegt es gar nicht in der Datenbank.

### In der Oberfläche

- Gespeicherte Passwörter und Secrets werden **nie** an den Browser zurückgegeben – das Feld zeigt
  nur `••••••••`. Leer lassen behält den Wert, eine eigene Checkbox entfernt ihn.
- Test-Ausgabe und Debug-Log maskieren SMTP-Zugangsdaten (`AUTH LOGIN`, `AUTH PLAIN`,
  `AUTH XOAUTH2`, `AUTH CRAM-MD5`) mit `********`.

## Microsoft OAuth

- **PKCE (S256)** beim Autorisierungscode-Ablauf; der Code-Verifier wird nur serverseitig gespeichert.
- Der `state`-Parameter ist zufällig (32 Zeichen), **15 Minuten** gültig, nur **einmal** verwendbar
  und an den WordPress-Benutzer gebunden, der die Anmeldung gestartet hat. Ein anderer Benutzer
  erhält „Du hast keine Berechtigung für diese Aktion.“
- Tokens sind an Authentifizierungsart, Mandant und Client-ID gebunden; ändert sich davon etwas,
  werden sie verworfen.
- Die Option `relaymint_oauth` wird nicht automatisch bei jedem Seitenaufruf geladen.
- Bei **Nur App** darf die App ohne weitere Einschränkung als jedes Postfach des Mandanten senden –
  beschränke sie in Exchange Online mit einer Application Access Policy bzw. RBAC for Applications.
  Siehe [Microsoft 365 / Outlook einrichten](Microsoft-365-Outlook-einrichten).

## Rechte und Formulare

- Alle Relaymint-Seiten und -Aktionen erfordern die Berechtigung `manage_options` (Administratoren).
- Jede Aktion (Speichern, Löschen, Test-E-Mail, Erneut senden, Warteschlange abarbeiten,
  Microsoft verbinden/trennen, Debug-Log leeren) ist mit einer Nonce geschützt.
- Löschaktionen fragen vorher nach („Bist du sicher?“).

## Protokoll und Datenschutz

- **E-Mail-Inhalte** werden standardmäßig **nicht** gespeichert. Inhalte können personenbezogene
  Daten oder Links zum Zurücksetzen von Passwörtern enthalten – aktiviere *E-Mail-Inhalt
  protokollieren* nur bei Bedarf und setze eine [Aufbewahrungsdauer](E-Mail-Protokoll).
- Mit dem Filter [`relaymint_log_entry_data`](Hooks-und-Filter) lassen sich einzelne Inhalte vor
  dem Speichern entfernen.
- **HTML-Vorschau:** Protokollierte HTML-E-Mails werden in einem `iframe` mit leerem
  `sandbox`-Attribut angezeigt; Skripte daraus laufen nicht im Adminbereich.

## Anhänge in der Warteschlange

Beim [Hintergrundversand](Hintergrundversand-und-Warteschlange) werden Anhänge nach
`wp-content/uploads/relaymint-queue/<zufälliger Ordner>/` kopiert und nach dem Versand gelöscht.
Das Verzeichnis enthält eine `index.php` und eine `.htaccess` mit `Require all denied` /
`Deny from all`. Die `.htaccess` wirkt nur auf Servern, die sie auswerten (Apache); auf anderen
Servern schützt nur der zufällige, 20 Zeichen lange Ordnername – sperre den Pfad dort bei Bedarf in
der Serverkonfiguration.

## SSL-Prüfung

**Unsichere SSL-Zertifikate erlauben** schaltet die Zertifikatsprüfung für SMTP-Verbindungen ab.
Damit ist die Verbindung anfällig für Man-in-the-Middle-Angriffe – nur bei selbstsignierten
Zertifikaten im eigenen Netz verwenden.
