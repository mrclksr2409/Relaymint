# E-Mail-Protokoll

**Relaymint → E-Mail-Protokoll** listet jede E-Mail, die über `wp_mail()` verschickt wurde – egal
ob von WordPress selbst, einem Plugin oder einem Theme. Eingestellt wird das Protokoll unter
**Einstellungen → E-Mail-Protokoll**.

---

## Einstellungen

| Einstellung | Beschreibung | Standard |
|---|---|---|
| **E-Mail-Protokoll aktivieren** | Schaltet das Protokoll ein. Ausgeschaltet entstehen keine neuen Einträge; die Protokollseite zeigt dann „Das E-Mail-Protokoll ist deaktiviert.“ mit Link zum Aktivieren. | an |
| **E-Mail-Inhalt protokollieren** | Speichert zusätzlich **Nachrichtentext und Header**. Nötig, um E-Mails anzusehen und erneut zu senden. | aus |
| **Aufbewahrungsdauer** | Unbegrenzt, 1 Tag, 1 Woche, 1 Monat, 3 Monate, 6 Monate, 1 Jahr | Unbegrenzt |

> **Datenschutz:** E-Mails können personenbezogene Daten oder Links zum Zurücksetzen von Passwörtern
> enthalten. Aktiviere *E-Mail-Inhalt protokollieren* nur, wenn du es brauchst, und setze eine
> Aufbewahrungsdauer.

## Was gespeichert wird

| Immer (bei aktivem Protokoll) | Nur mit „E-Mail-Inhalt protokollieren“ |
|---|---|
| Status, Betreff, Absender, An, CC, BCC, Antwort an | Nachrichtentext |
| Inhaltstyp (`text/plain` oder `text/html`) | Alle Header |
| **Dateinamen** der Anhänge (nicht die Dateien selbst) | |
| Verbindung, Auslöser (Typ, Name, Datei), Fehlermeldung | |
| Erstellt / zuletzt geändert | |

Als **Absender** speichert Relaymint zunächst den `From`-Header des Aufrufs und ersetzt ihn beim
Versand über eine Relaymint-Verbindung durch den tatsächlich verwendeten Absender
(`Name <adresse>`).

Mit dem Filter [`relaymint_log_entry_data`](Hooks-und-Filter) kannst du die Daten vor dem Speichern
ändern, z. B. einzelne Inhalte schwärzen.

## Status

| Status | Bedeutung |
|---|---|
| **Gesendet** | Die E-Mail wurde erfolgreich übergeben – an den SMTP-Server, an Microsoft Graph bzw. (ohne Relaymint-Verbindung) an PHP `mail()` |
| **Fehlgeschlagen** | Versand gescheitert, die Fehlermeldung steht unter dem Status |
| **In Warteschlange** | Wartet auf den [Hintergrundversand](Hintergrundversand-und-Warteschlange) |
| **Wird gesendet** | Versand läuft gerade |
| **Blockiert** | Durch [*Nicht senden* oder die Domain-Prüfung](Domain-Pruefung-und-Nicht-senden) gestoppt |

„Gesendet“ heißt: angenommen. Ob die E-Mail im Posteingang oder im Spam landet, kann
Relaymint nicht sehen.

## Die Liste

| Spalte | Inhalt |
|---|---|
| **Betreff** | Mit Aktionen *Ansehen* und – wenn der Inhalt protokolliert wurde – *Erneut senden* |
| **An** | Empfänger |
| **Status** | Status-Badge, bei Fehlern die gekürzte Fehlermeldung |
| **Verbindung** | Name der Verbindung; „PHP mail()“, wenn ohne Relaymint-Verbindung versendet wurde; „—“ bei wartenden und blockierten E-Mails |
| **Auslöser** | Plugin, Theme oder `WordPress`, das `wp_mail()` aufgerufen hat |
| **Datum** | Zeitpunkt des Eintrags in der Zeitzone der Website |

- **Statusfilter** über der Liste (Alle, Gesendet, Fehlgeschlagen, …) mit Anzahl
- **Suche** in Betreff, An, Von, CC, BCC und Auslöser – nicht im Nachrichtentext
- **Sortieren** nach Betreff, Status und Datum (Standard: neueste zuerst)
- 20 Einträge pro Seite

## Detailansicht

**Ansehen** öffnet die *E-Mail-Details*: Status, Datum, Betreff, Von, An, CC, BCC, Antwort an,
Verbindung, Auslöser mit Typ, aufrufende Datei, Anhänge und ggf. Fehler. Darunter die Header und die
Nachricht:

- **Text-E-Mails** werden als Text angezeigt.
- **HTML-E-Mails** werden in einem abgeschotteten `iframe` (`sandbox` ohne Skript-Erlaubnis)
  dargestellt, damit Skripte aus protokollierten E-Mails nicht im Adminbereich laufen.
- Ohne protokollierten Inhalt steht dort „Der Nachrichteninhalt wurde nicht protokolliert.“

## Erneut senden

Verfügbar in der Liste und in der Detailansicht, **nur wenn der Inhalt protokolliert wurde**.

- Verwendet werden die gespeicherten Empfänger, Betreff, Nachricht und Header (damit auch CC/BCC).
- **Anhänge werden nicht erneut gesendet** – es sind nur ihre Dateinamen gespeichert.
- Die E-Mail wird **sofort** versendet (ohne Warteschlange) und läuft erneut durch
  [Smart Routing](Weitere-Verbindungen-und-Smart-Routing) – sie kann also über eine andere
  Verbindung gehen als beim ersten Mal.
- *Nicht senden* und die Domain-Prüfung gelten auch hier.
- Es entsteht ein **neuer** Protokolleintrag; Auslöser ist dann Relaymint selbst.

## Löschen und Aufbewahrung

| Aktion | Wirkung |
|---|---|
| Häkchen + Massenaktion **Löschen** | Löscht die markierten Einträge |
| **Alle löschen** (oben rechts) | Leert die Tabelle vollständig (`TRUNCATE`), nach Rückfrage |
| **Aufbewahrungsdauer** | Die tägliche Action `relaymint_daily_cleanup` löscht Einträge, die älter als die gewählte Dauer sind |

Die tägliche Aufräum-Action wird beim ersten Aufruf einer Admin-Seite angelegt und läuft erstmals
etwa eine Stunde danach. Siehe [Datenbank](Datenbank).
