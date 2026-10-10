# Domain-Prüfung & Nicht senden

Zwei Schutzschalter unter **Relaymint → Einstellungen → Sonstiges**, gedacht vor allem für
Staging-, Entwicklungs- und umgezogene Websites.

---

## Domain-Prüfung

Kopierst du eine Live-Website auf eine Staging-Domain, wandern die SMTP-Zugangsdaten mit. Ohne
Schutz verschickt die Kopie dann echte E-Mails über das Produktivkonto. Die Domain-Prüfung
verhindert das: Relaymint nutzt seine Verbindungen **nur**, wenn die Website auf einer der
erlaubten Domains läuft.

### Einstellungen

| Einstellung | Beschreibung |
|---|---|
| **Domain-Prüfung aktivieren** | Schaltet die Prüfung ein |
| **Erlaubte Domains (kommagetrennt)** | Liste der erlaubten Domains. Ist sie beim Speichern leer, trägt Relaymint die aktuelle Domain ein. Darunter steht „Aktuelle Domain der Website: …“. |
| **Alle E-Mails blockieren, wenn die Domain nicht passt** | Bestimmt, was auf fremden Domains passiert (siehe unten) |

### Wie verglichen wird

- Die aktuelle Domain ist der Host aus der **Website-Adresse** (`home_url()`).
- Domains werden normalisiert: Kleinschreibung, Schema (`https://`), Pfad, Port und ein
  führendes `www.` werden entfernt. `https://www.Example.com/shop` wird zu `example.com`.
- Einträge dürfen durch Kommas, Leerzeichen oder Zeilenumbrüche getrennt sein.
- Subdomains werden **nicht** automatisch erlaubt: `shop.example.com` muss eigens eingetragen werden.
- Per Filter [`relaymint_allowed_domains`](Hooks-und-Filter) lässt sich die Liste im Code erweitern.

### Was auf einer nicht erlaubten Domain passiert

| „Alle E-Mails blockieren“ | Ergebnis |
|---|---|
| **aus** | Die E-Mail wird **sofort** mit dem Standard-PHP-Mailer versendet – ohne Relaymint-Verbindung und ohne Warteschlange. Im Protokoll steht als Verbindung „PHP mail()“. |
| **an** | Die E-Mail wird nicht versendet, im Protokoll als *Blockiert* mit „Durch die Domain-Prüfung blockiert: … ist keine erlaubte Domain.“. `wp_mail()` liefert `false`. |

Solange die Domain nicht passt, zeigen alle Relaymint-Seiten den Hinweis
„Domain-Prüfung: … ist keine erlaubte Domain, die SMTP-Einstellungen werden nicht verwendet.“

> Die Prüfung gilt für **alle** Verbindungen – SMTP wie Microsoft – und auch für E-Mails, die
> bereits in der Warteschlange stehen, sobald sie versendet werden.

---

## Nicht senden

**Versand aller E-Mails stoppen** blockiert jede E-Mail der Website, unabhängig von Domain,
Verbindung oder Routing. Das ist die oberste Regel im [Versandablauf](Versandablauf).

- Jede E-Mail erscheint im [E-Mail-Protokoll](E-Mail-Protokoll) als *Blockiert* mit
  „Durch die Einstellung „Nicht senden“ blockiert.“ (sofern das Protokoll aktiv ist).
- `wp_mail()` liefert `true`, damit Plugins keine Fehlermeldungen anzeigen. Mit dem Filter
  [`relaymint_do_not_send_result`](Hooks-und-Filter) kannst du `false` zurückgeben lassen.
- Gilt auch für E-Mails, die noch in der Warteschlange stehen, und für Test-E-Mails.
- Alle Relaymint-Seiten zeigen den Hinweis „„Nicht senden“ ist aktiv: Diese Website versendet keine
  E-Mails.“
- Blockierte E-Mails lassen sich später – wenn der Inhalt protokolliert wurde – über
  **Erneut senden** im Protokoll nachträglich verschicken, sobald *Nicht senden* wieder aus ist.

## Typische Kombinationen

| Ziel | Einstellung |
|---|---|
| Produktivseite | Domain-Prüfung an, nur die Live-Domain erlaubt |
| Staging-Kopie soll gar nichts versenden | Domain-Prüfung an + **Alle E-Mails blockieren** (greift automatisch nach dem Kopieren) |
| Entwicklungsumgebung, E-Mails nur ansehen | **Nicht senden** + **E-Mail-Inhalt protokollieren** |
