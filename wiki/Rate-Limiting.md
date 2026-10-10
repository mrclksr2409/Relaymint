# Rate Limiting

**Relaymint → Einstellungen → Sonstiges → E-Mail-Ratenbegrenzung**

Viele Anbieter begrenzen, wie viele E-Mails pro Zeitraum über ein Konto versendet werden dürfen.
Überschreitest du das, werden E-Mails abgelehnt oder das Konto gesperrt. Mit der Ratenbegrenzung
hält Relaymint E-Mails über dem Limit in der Warteschlange zurück und sendet sie, sobald es wieder
erlaubt ist.

## Voraussetzung: Hintergrundversand

Die Ratenbegrenzung wirkt **nur** beim Abarbeiten der Warteschlange und setzt daher
[**E-Mail-Versand optimieren**](Hintergrundversand-und-Warteschlange) voraus. Hakst du sie ohne
Hintergrundversand an, wird sie nicht gespeichert und es erscheint der Hinweis „Die
E-Mail-Ratenbegrenzung erfordert „E-Mail-Versand optimieren“ und wurde nicht aktiviert.“ Die Zeile
ist in der Oberfläche nur sichtbar, solange der Hintergrundversand angehakt ist.

## Einstellungen

| Feld | Zeitfenster |
|---|---|
| **Pro Minute** | die letzten 60 Sekunden |
| **Pro Stunde** | die letzten 3 600 Sekunden |
| **Pro Tag** | die letzten 86 400 Sekunden |
| **Pro Woche** | die letzten 604 800 Sekunden |

Leer oder `0` bedeutet *kein Limit* für diesen Zeitraum. Die Limits gelten **gemeinsam** für alle
Verbindungen.

## Wie gezählt wird

- **Gleitende Zeitfenster**, keine Kalendereinheiten: „Pro Stunde: 100“ heißt *höchstens 100 E-Mails
  in den letzten 60 Minuten*, nicht *pro voller Stunde*.
- Gezählt werden nur E-Mails, die **über die Warteschlange erfolgreich versendet** wurden.
  Fehlgeschlagene E-Mails, Test-E-Mails und erneut gesendete E-Mails zählen nicht mit, ebenso
  wenig E-Mails, die wegen der Domain-Prüfung direkt über PHP `mail()` gingen.
- Vor **jeder** E-Mail prüft der Worker alle Zeitfenster. Ist eines voll, berechnet er, wann die
  älteste mitgezählte E-Mail aus dem Fenster fällt, und plant den nächsten Lauf genau dafür
  (`relaymint_process_queue` als einmalige Action). Bei mehreren vollen Fenstern gilt der späteste
  Zeitpunkt.
- Mit eingeschaltetem [Debug-Log](Testmail-und-Werkzeuge#debug-log) steht dort
  „Rate limit reached, queue continues at JJJJ-MM-TT HH:MM:SS UTC.“

## Beispiel

Limit *Pro Minute: 10*, ein Newsletter-Plugin ruft 25-mal `wp_mail()` auf:

1. Alle 25 E-Mails landen sofort in der Warteschlange (*In Warteschlange*).
2. Der erste Lauf versendet 10 E-Mails und plant den nächsten Lauf etwa eine Minute nach der
   ersten versendeten E-Mail.
3. Lauf zwei versendet die nächsten 10, Lauf drei die letzten 5.

## Eigene Zeitfenster

Mit dem Filter [`relaymint_rate_limits`](Hooks-und-Filter) kannst du Limits im Code setzen oder
weitere Zeitfenster ergänzen (Schlüssel = Fensterlänge in Sekunden):

```php
add_filter( 'relaymint_rate_limits', function ( array $limits ) {
	$limits[ 5 * MINUTE_IN_SECONDS ] = 30; // max. 30 E-Mails in 5 Minuten
	return $limits;
} );
```

> Erledigte Einträge der Warteschlange werden nach 8 Tagen gelöscht. Zeitfenster, die länger als
> 8 Tage sind, werden daher nicht vollständig gezählt.
