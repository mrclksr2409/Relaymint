# Hintergrundversand & Warteschlange

**Relaymint → Einstellungen → Sonstiges → E-Mail-Versand optimieren → E-Mails im Hintergrund senden**

Normalerweise wartet eine Seite, die eine E-Mail verschickt (z. B. ein Kontaktformular oder ein
Checkout), bis der SMTP-Server bzw. Microsoft Graph geantwortet hat. Mit dem Hintergrundversand
stellt Relaymint die E-Mail nur in eine Warteschlange und versendet sie anschließend asynchron über
den mitgelieferten [Action Scheduler](https://actionscheduler.org/). Die Seite lädt schneller.

---

## Ablauf

```
wp_mail() ──► Eintrag in wp_relaymint_queue (Status „queued“)
          ──► Protokoll: „In Warteschlange“
          ──► wp_mail() liefert sofort true
                │
Action Scheduler: relaymint_process_queue
                │
          ──► Ratenbegrenzung prüfen ──► E-Mail regulär versenden (Routing, SMTP/Graph)
          ──► Protokoll: „Wird gesendet“ → „Gesendet“ / „Fehlgeschlagen“
```

1. **Einreihen:** Die `wp_mail()`-Argumente und der erkannte Auslöser werden als JSON in der Tabelle
   `wp_relaymint_queue` gespeichert. Im Protokoll entsteht sofort ein Eintrag *In Warteschlange*
   (noch ohne Verbindung).
2. **Planen:** Ist noch kein Lauf geplant, wird eine asynchrone Action `relaymint_process_queue`
   (Gruppe `relaymint`) angelegt.
3. **Abarbeiten:** Der Worker nimmt die älteste wartende E-Mail, markiert sie als *in Arbeit*
   (damit ein paralleler Worker sie nicht doppelt sendet) und ruft `wp_mail()` erneut auf. Dabei
   gelten [„Nicht senden“, Domain-Prüfung, Smart Routing](Versandablauf) wie bei jeder E-Mail.
   Der Protokolleintrag von Schritt 1 wird aktualisiert – es entsteht kein zweiter.
4. **Zeitlimit:** Ein Worker-Lauf dauert höchstens **20 Sekunden** (Filter
   [`relaymint_queue_time_limit`](Hooks-und-Filter)). Ist danach noch etwas übrig, plant er sofort
   einen Folgelauf.
5. **Ratenbegrenzung:** Vor jeder E-Mail wird das [Rate Limiting](Rate-Limiting) geprüft. Ist das
   Limit erreicht, endet der Lauf und ein neuer wird für den Zeitpunkt geplant, an dem wieder ein
   Platz frei wird.

## Was nicht über die Warteschlange läuft

- **Test-E-Mails** und **erneut gesendete E-Mails** aus dem Protokoll werden immer sofort versendet.
- E-Mails auf einer [nicht erlaubten Domain](Domain-Pruefung-und-Nicht-senden) gehen sofort über
  PHP `mail()` (oder werden blockiert).
- Durch [*Nicht senden*](Domain-Pruefung-und-Nicht-senden) blockierte E-Mails werden gar nicht
  erst eingereiht.

## Anhänge

Anhänge sind oft temporäre Dateien, die nach dem Request verschwinden. Relaymint kopiert sie deshalb
beim Einreihen in ein eigenes Verzeichnis:

```
wp-content/uploads/relaymint-queue/<zufälliger Ordner>/<Dateiname>
```

- Das Basisverzeichnis erhält eine `index.php` und eine `.htaccess` mit `Require all denied` /
  `Deny from all`; der Unterordner hat einen zufälligen, 20 Zeichen langen Namen.
- Nach dem Versandversuch (erfolgreich oder nicht) wird der Unterordner gelöscht.
- Lässt sich eine Datei nicht kopieren, bleibt der ursprüngliche Pfad in der Warteschlange stehen.

## Rückgabewert und Fehler

Weil `wp_mail()` beim Einreihen sofort `true` liefert, erfährt das aufrufende Plugin von einem
späteren Versandfehler nichts. Fehlgeschlagene E-Mails erkennst du im
[E-Mail-Protokoll](E-Mail-Protokoll) am Status *Fehlgeschlagen* samt Fehlermeldung.

- Eine fehlgeschlagene E-Mail wird **nicht automatisch erneut versucht**. Mit protokolliertem Inhalt
  kannst du sie über **Erneut senden** nachholen.
- Kann eine E-Mail nicht in die Tabelle geschrieben werden, liefert `wp_mail()` `false`, der
  Protokolleintrag wird *Fehlgeschlagen* („E-Mail konnte nicht zur Warteschlange hinzugefügt
  werden.“) und das Debug-Log enthält den Datenbankfehler.

## Warteschlange manuell abarbeiten

Warten E-Mails, zeigt **Sonstiges** unter der Option „… E-Mails warten in der Warteschlange.“ und
den Knopf **Warteschlange jetzt abarbeiten**. Er führt den Worker direkt im aktuellen Request aus
(mit denselben Grenzen: Ratenbegrenzung und Zeitlimit).

Gezählt werden dabei E-Mails mit Status *wartend* und *in Arbeit*.

## Hintergrundversand ausschalten

Ausschalten ist jederzeit möglich. Warten beim Speichern noch E-Mails, plant Relaymint einen Lauf,
damit sie trotzdem versendet werden. Die [Ratenbegrenzung](Rate-Limiting) ist ohne
Hintergrundversand allerdings nicht aktiv.

## Tägliche Wartung

Die tägliche Action `relaymint_daily_cleanup` räumt auch die Warteschlange auf:

| Was | Regel |
|---|---|
| Erledigte Einträge (gesendet/fehlgeschlagen) | Werden gelöscht, wenn der Versand länger als **8 Tage** (1 Woche + 1 Tag) zurückliegt – so lange braucht sie die Wochen-Ratenbegrenzung |
| Hängengebliebene Einträge (*in Arbeit*, z. B. nach einem PHP-Fatal-Error) | Werden auf *wartend* zurückgesetzt, wenn sie vor mehr als **1 Stunde** eingereiht wurden |
| Noch wartende E-Mails | Ist etwas offen, wird ein Worker-Lauf geplant |

Nach dem Versand wird der gespeicherte Inhalt (`data`) einer Zeile sofort geleert; nur Status und
Zeitstempel bleiben für die Ratenbegrenzung stehen.

## Action Scheduler

Relaymint bringt Action Scheduler 4.2.0 mit. Nutzt ein anderes Plugin (z. B. WooCommerce) ebenfalls
Action Scheduler, entscheidet die Bibliothek selbst, welche Version geladen wird. Die geplanten
Aktionen von Relaymint (Gruppe `relaymint`) findest du unter **Werkzeuge → Scheduled Actions**.

| Hook | Art | Zweck |
|---|---|---|
| `relaymint_process_queue` | asynchron bzw. einmalig zu einem Zeitpunkt | Warteschlange abarbeiten |
| `relaymint_daily_cleanup` | wiederkehrend, täglich | Protokoll-Aufbewahrung, Warteschlange aufräumen |

Beim **Deaktivieren** des Plugins werden alle Aktionen der Gruppe `relaymint` entfernt. Wartende
E-Mails bleiben in der Tabelle; nach der Reaktivierung kannst du sie mit **Warteschlange jetzt
abarbeiten** versenden.
