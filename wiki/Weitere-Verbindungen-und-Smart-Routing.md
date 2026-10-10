# Weitere Verbindungen & Smart Routing

Mit **zusätzlichen Verbindungen** und **Smart Routing** schickst du bestimmte E-Mails über ein
anderes SMTP-Konto oder Microsoft-365-/Outlook-Postfach – z. B. Shop-Bestellungen über ein
Transaktions-Konto, Newsletter-Bestätigungen über ein anderes.

---

## Zusätzliche Verbindungen

**Relaymint → Einstellungen → Zusätzliche Verbindungen**

### Anlegen und bearbeiten

1. **Verbindung hinzufügen** klicken.
2. **Name der Verbindung** eintragen (Pflichtfeld).
3. Die übrigen Felder sind dieselben wie bei der primären Verbindung:
   [SMTP einrichten](SMTP-einrichten) bzw.
   [Microsoft 365 / Outlook einrichten](Microsoft-365-Outlook-einrichten).
4. **Verbindung speichern.** Danach bleibt das Formular offen – bei Microsoft (delegiert) kannst du
   jetzt **Mit Microsoft verbinden** klicken.

### Die Verbindungs-ID

Jede zusätzliche Verbindung erhält beim ersten Speichern eine zufällige, unveränderliche ID aus
`c` und acht Kleinbuchstaben/Ziffern, z. B. `cabc12345`. Sie steht in der Spalte
**Verbindungs-ID** und wird gebraucht für

- die Passwort-/Secret-Konstanten: `RELAYMINT_SMTP_PASS_CABC12345`,
  `RELAYMINT_MS_CLIENT_SECRET_CABC12345` (siehe [Konstanten](Konstanten-in-wp-config)),
- den Filter [`relaymint_route_connection`](Hooks-und-Filter).

Die primäre Verbindung hat die ID `primary`.

### Löschen

**Löschen** in der Liste entfernt die Verbindung, ihre gespeicherten Microsoft-Tokens **und alle
Smart-Routing-Routen, die auf sie zeigen**. Die primäre Verbindung lässt sich nicht löschen.

---

## Smart Routing

**Relaymint → Einstellungen → Smart Routing**

> Der Tab zeigt nur einen Hinweis, solange keine zusätzliche Verbindung existiert.

### Aufbau

```
Route 1  [Aktiv]  Senden über: „Shop-Konto“
   wenn die folgenden Bedingungen zutreffen:
     Gruppe A:  Auslöser enthält „WooCommerce“   UND   Betreff enthält „Bestellung“
     oder
     Gruppe B:  An endet mit „@example.com“
Route 2  …
```

- **Bedingungen** innerhalb einer Gruppe sind **UND**-verknüpft (**+ Und**).
- **Gruppen** innerhalb einer Route sind **ODER**-verknüpft (**+ Bedingungsgruppe hinzufügen (oder)**).
- **Routen** werden von **oben nach unten** geprüft – **die erste passende gewinnt**. Mit den
  Pfeilen änderst du die Reihenfolge.
- E-Mails ohne passende Route gehen über die **primäre Verbindung**.
- **Smart Routing aktivieren** muss angehakt sein, sonst werden alle Routen ignoriert.

Übersprungen werden Routen, die

- nicht **Aktiv** sind,
- auf eine Verbindung zeigen, die [unvollständig](Versandablauf#4-verbindung-wählen) ist
  (z. B. eine nicht verbundene Microsoft-Verbindung),
- keine einzige Bedingung enthalten.

Als Ziel einer Route sind nur **zusätzliche** Verbindungen wählbar.

### Felder

| Feld | Geprüfter Wert |
|---|---|
| **Betreff** | Betreff der E-Mail |
| **Nachricht** | Nachrichtentext, wie er an `wp_mail()` übergeben wurde |
| **Von** | Der `From`-Header, wie er an `wp_mail()` übergeben wurde – **leer**, wenn das aufrufende Plugin keinen `From`-Header setzt (die Absender-E-Mail der Verbindung spielt hier keine Rolle) |
| **An** | Jede Empfängeradresse einzeln |
| **CC** / **BCC** / **Antwort an** | Jede Adresse aus dem jeweiligen Header einzeln |
| **Header** | Jeder Header einzeln im Format `Name: Wert` |
| **Auslöser (Plugin/Theme)** | Name des Plugins/Themes, das `wp_mail()` aufgerufen hat, **und** der Dateipfad des Aufrufs |

Adressen werden so verglichen, wie sie übergeben wurden – also z. B. `Max <max@example.com>`, wenn
das Plugin den Namen mitliefert.

Der **Auslöser** wird aus dem Aufruf-Stack ermittelt: Plugins (Name aus dem Plugin-Header),
MU-Plugins, Themes (Theme-Name), WordPress-Kern (`WordPress`) oder unbekannt. Welcher Name genau
erkannt wird, siehst du in der Spalte **Auslöser** im [E-Mail-Protokoll](E-Mail-Protokoll).

### Operatoren

| Operator | Bedeutung |
|---|---|
| **enthält** / **enthält nicht** | Teilzeichenkette |
| **ist** / **ist nicht** | Exakte Gleichheit |
| **beginnt mit** / **endet mit** | Anfang bzw. Ende |
| **passt auf Regex** / **passt nicht auf Regex** | PHP-PCRE-Ausdruck **mit Begrenzern**, z. B. `/^bestellung #\d+/i` |

Regeln für den Vergleich:

- Alle Operatoren außer Regex vergleichen **ohne Beachtung der Groß-/Kleinschreibung**. Bei Regex
  steuerst du das selbst (Modifikator `i`).
- Hat ein Feld **mehrere Werte** (z. B. mehrere Empfänger), trifft eine positive Bedingung zu, wenn
  **irgendein** Wert passt. Eine verneinte Bedingung trifft zu, wenn **kein** Wert passt.
- Ein **leeres Feld** (z. B. kein CC) wird als ein leerer Wert behandelt: `CC ist` mit leerem Wert
  trifft also zu, wenn es kein CC gibt.
- **enthält**, **beginnt mit** und **endet mit** mit leerem Vergleichswert treffen nie zu.
- Ungültige reguläre Ausdrücke werden beim Speichern verworfen; es erscheint der Hinweis
  „Der reguläre Ausdruck … ist ungültig und wurde übersprungen.“

### Beispiele

**Alle E-Mails eines Formular-Plugins über ein eigenes Konto**

| Feld | Operator | Wert |
|---|---|---|
| Auslöser (Plugin/Theme) | enthält | `Contact Form 7` |

**Passwort-Mails von WordPress selbst**

| Feld | Operator | Wert |
|---|---|---|
| Auslöser (Plugin/Theme) | ist | `WordPress` |
| Betreff | enthält | `Passwort` |

**Interne Empfänger (Gruppe A) oder Kopie an die Buchhaltung (Gruppe B)**

- Gruppe A: `An` · *endet mit* · `@firma.de`
- Gruppe B: `BCC` · *ist* · `buchhaltung@firma.de`

### Testen

Die [Test-E-Mail](Testmail-und-Werkzeuge) umgeht Smart Routing – dort wählst du die Verbindung
direkt. Ob eine Regel greift, siehst du an echten E-Mails in der Spalte **Verbindung** im
[E-Mail-Protokoll](E-Mail-Protokoll). Für Logik, die sich nicht als Regel ausdrücken lässt, gibt es
den Filter [`relaymint_route_connection`](Hooks-und-Filter).
