# Fehlerbehebung

Erster Schritt fast immer: eine **Test-E-Mail** unter **Relaymint → Werkzeuge** senden und die
**Debug-Ausgabe** lesen. Danach das **Debug-Log** (gleiche Seite, unten) und das
**E-Mail-Protokoll** prüfen. Siehe [Testmail & Werkzeuge](Testmail-und-Werkzeuge).

---

## Allgemein

### Im Protokoll steht als Verbindung „PHP mail()“
Relaymint hat keine Verbindung verwendet. Mögliche Ursachen:

- Die Verbindung ist **unvollständig**: bei SMTP fehlt der Host; bei Microsoft fehlen Client-ID oder
  Secret, das Postfach ist nicht verbunden (delegiert) bzw. Absender-E-Mail oder Mandant passen nicht
  (Nur App). Siehe [Versandablauf](Versandablauf#4-verbindung-wählen).
- Die **Domain-Prüfung** schlägt fehl – dann zeigen alle Relaymint-Seiten einen gelben Hinweis.
  Siehe [Domain-Prüfung & Nicht senden](Domain-Pruefung-und-Nicht-senden).
- Ein Filter `relaymint_route_connection` gibt eine unbekannte Verbindungs-ID zurück.

### Test-E-Mail „erfolgreich“, aber die Debug-Ausgabe ist leer
Die Test-E-Mail ist über PHP `mail()` gegangen, weil die gewählte Verbindung unvollständig ist –
siehe oben.

### E-Mails kommen nicht an, obwohl das Protokoll „Gesendet“ zeigt
„Gesendet“ heißt: angenommen. Prüfe den Spam-Ordner und ob die **Absender-E-Mail** zur Domain bzw.
zum Konto passt (viele Anbieter verwerfen oder markieren fremde Absender). Lass dazu
**Absender-E-Mail erzwingen** eingeschaltet.

### Es erscheinen gar keine Einträge im Protokoll
- **Einstellungen → E-Mail-Protokoll → E-Mail-Protokoll aktivieren** ist aus.
- Ein anderes Plugin beantwortet den Filter `pre_wp_mail` selbst (z. B. ein zweites Mail-Plugin) –
  dann greift Relaymint nicht ein. Nur ein Mail-Plugin gleichzeitig aktiv lassen.

### Keine E-Mail verlässt die Website, Status „Blockiert“
*Nicht senden* ist aktiv oder die Domain-Prüfung blockiert alles. Der Grund steht in der Spalte
*Status* bzw. in der Detailansicht.

### Passwort/Secret wird nicht gespeichert
Nach dem Speichern bleibt der Platzhalter `••••••••` aus, im Debug-Log steht „Could not encrypt the
SMTP password: OpenSSL or WordPress salts are missing.“ Die PHP-Erweiterung **OpenSSL** fehlt oder
die Salts `AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY` sind nicht definiert. Entweder beheben oder
das Passwort als [Konstante](Konstanten-in-wp-config) setzen.

### Nach einem Umzug bzw. neuen Salts funktioniert nichts mehr
Die gespeicherten Passwörter, Secrets und Tokens sind mit den alten Salts verschlüsselt und nicht
mehr lesbar. SMTP-Passwörter und Client-Secrets neu eintragen, Microsoft-Konten neu verbinden.
Siehe [Sicherheit](Sicherheit).

---

## SMTP

### Verbindung schlägt fehl / Timeout
- Host und Port prüfen; **TLS** gehört zu Port **587**, **SSL** zu Port **465**.
- Kommt in der Debug-Ausgabe keine einzige Serverantwort an, erreicht der Webserver den
  SMTP-Server nicht (z. B. ausgehender Port gesperrt) – beim Hoster nachfragen.

### Anmeldung abgelehnt (AUTH-Fehler)
- Benutzername ist meist die vollständige E-Mail-Adresse.
- Passwort neu eintragen (Leerzeichen am Ende?).
- Bei Konten mit Zwei-Faktor-Anmeldung verlangen viele Anbieter ein App-Passwort.
- Für Microsoft 365 ist SMTP AUTH häufig deaktiviert – nutze stattdessen die Versandart
  [Microsoft 365 / Outlook](Microsoft-365-Outlook-einrichten).

### Zertifikatsfehler
Der Server nutzt ein selbstsigniertes oder ungültiges Zertifikat. Entweder den Hostnamen verwenden,
auf den das Zertifikat ausgestellt ist, oder **Sonstiges → Selbstsignierte oder ungültige
SSL-Zertifikate erlauben** (mit den bekannten Risiken).

### Server bietet TLS an, der Versand scheitert trotzdem
Bei Verschlüsselung *Keine*: **Auto-TLS** ausschalten.

### Absender wird vom Server abgelehnt
Die **Absender-E-Mail** muss zum angemeldeten Konto bzw. zur Domain passen. Mit **Return-Path**
wird zusätzlich der Envelope-Sender angeglichen.

---

## Microsoft 365 / Outlook

### „Microsoft akzeptiert nur HTTPS-Umleitungs-URIs …“
Der Adminbereich läuft ohne HTTPS. Für „Mit dem Postfach anmelden“ HTTPS aktivieren (außer bei
`localhost`) – oder „Nur App“ verwenden.

### Kein Knopf „Mit Microsoft verbinden“
Die Verbindung ist noch nicht gespeichert, oder Client-ID bzw. Secret fehlen. Versandart
*Microsoft 365 / Outlook* wählen, Daten eintragen, **speichern**. Bei einer neuen zusätzlichen
Verbindung erscheint der Knopf erst nach dem ersten Speichern.

### „Speichere zuerst die Microsoft-Client-ID und den geheimen Clientschlüssel.“
Wie oben – erst speichern, dann verbinden.

### Fehlermeldung von Microsoft auf der Anmeldeseite / „Die Microsoft-Anmeldung wurde nicht abgeschlossen: …“
Microsoft liefert den Grund mit. Häufig:
- **Umleitungs-URI** in Entra stimmt nicht exakt mit der in Relaymint angezeigten überein
  (Plattform muss **Web** sein; `http` vs. `https`, `www` oder Pfad abweichend).
- Der **Kontotyp** der App passt nicht zum Konto (z. B. Outlook.com-Konto, App aber nur für die
  eigene Organisation) oder der **Mandant** in Relaymint passt nicht (für Outlook.com `common` oder
  `consumers`).
- Die Anmeldung wurde abgebrochen oder die Zustimmung verweigert.

### „Microsoft hat kein Aktualisierungstoken zurückgegeben …“
Die delegierte Berechtigung `offline_access` fehlt. In Entra unter **API permissions** ergänzen und
neu verbinden.

### „Microsoft-Anmeldung fehlgeschlagen (…): …“
Der Token-Endpunkt hat abgelehnt; Code und Text in Klammern stammen von Microsoft. Typische Ursachen:
falsche Client-ID, falscher oder **abgelaufener geheimer Clientschlüssel** (auch: *Secret ID*
statt *Value* eingetragen), falscher Mandant. Secret in Entra neu erstellen und den **Wert**
eintragen.

### Nach der Rückkehr von Microsoft „Du hast keine Berechtigung für diese Aktion.“
Die Anmeldung wurde von einem anderen WordPress-Benutzer gestartet, oder der angemeldete Benutzer ist
kein Administrator. Mit demselben Admin-Konto erneut **Mit Microsoft verbinden**.

### Anmeldung wird nicht übernommen
Die Anmeldung muss innerhalb von **15 Minuten** abgeschlossen werden; jeder Anmeldevorgang ist nur
einmal gültig. Erneut **Mit Microsoft verbinden** klicken.

### „Microsoft-Konto verbunden“, der Status bleibt aber „Nicht verbunden“
Die Tokens konnten nicht verschlüsselt werden (OpenSSL/Salts fehlen) – siehe
[Passwort/Secret wird nicht gespeichert](#passwortsecret-wird-nicht-gespeichert).

### Status plötzlich wieder „Nicht verbunden“
Mandant, Client-ID oder Authentifizierungsart wurden geändert – die Tokens gehören zur alten
Kombination und wurden verworfen. Neu verbinden.

### „Microsoft Graph hat die E-Mail abgelehnt (…): …“
Graph hat die Nachricht abgewiesen; Code und Text stammen von Microsoft. Häufig:
- Die **Absender-E-Mail** ist nicht das angemeldete Postfach und es fehlt die Berechtigung
  „Senden als“ (delegiert). Absender-E-Mail leer lassen oder Berechtigung erteilen.
- Bei „Nur App“: Berechtigung `Mail.Send` (Anwendung) fehlt, **Administratorzustimmung** wurde nicht
  erteilt, oder eine Application Access Policy schließt das Postfach aus.
- Nachricht zu groß (laut README ca. 4 MB pro Anfrage inkl. Base64-kodierter Anhänge).

### „Für den Versand mit der Microsoft-Graph-Anwendungsberechtigung ist eine gültige Absender-E-Mail erforderlich.“
Bei „Nur App“ eine gültige **Absender-E-Mail** (Postfach im Mandanten) eintragen und
**Absender-E-Mail erzwingen** eingeschaltet lassen.

### „Die Microsoft-Verbindung ist nicht autorisiert …“
Das Postfach ist nicht (mehr) verbunden. **Mit Microsoft verbinden** klicken.

### Debug-Log: „Microsoft connection cannot be used: the PHPMailer instance was replaced by another plugin.“
Ein anderes Plugin ersetzt PHPMailer. Die E-Mail schlägt fehl, statt unbemerkt über `mail()` zu
gehen. Das andere Plugin deaktivieren oder SMTP verwenden.

### Debug-Log: „Could not refresh the token of Microsoft connection …“
Die Token-Erneuerung ist fehlgeschlagen; die Meldung enthält den Grund von Microsoft (z. B. ein
abgelaufenes Secret oder ein widerrufener Zugriff). Secret prüfen und neu verbinden.

---

## Hintergrundversand & Ratenbegrenzung

### E-Mails bleiben „In Warteschlange“
Der Action Scheduler arbeitet nicht. Prüfe unter **Werkzeuge → Scheduled Actions**, ob
`relaymint_process_queue` ausstehend oder fehlgeschlagen ist. Sofort-Hilfe: **Einstellungen →
Sonstiges → Warteschlange jetzt abarbeiten**. Bei aktiver Ratenbegrenzung ist das Warten gewollt –
mit eingeschaltetem Debug-Log steht dort, wann es weitergeht.

### E-Mails aus der Warteschlange hängen auf „Wird gesendet“
Meist ein PHP-Abbruch während des Versands. Die tägliche Wartung setzt Warteschlangen-Einträge, die
vor mehr als einer Stunde eingereiht wurden und noch *in Arbeit* sind, auf *wartend* zurück; danach
werden sie erneut versendet. (Für E-Mails, die ohne Warteschlange versendet wurden, gibt es keine
solche Wiederholung.)

### Ratenbegrenzung lässt sich nicht einschalten
Sie erfordert **E-Mail-Versand optimieren**. Siehe [Rate Limiting](Rate-Limiting).

### Anhang fehlt bei E-Mails aus der Warteschlange
Relaymint kopiert Anhänge nach `wp-content/uploads/relaymint-queue/`. Ist das Upload-Verzeichnis
nicht beschreibbar, bleibt der Originalpfad stehen – existiert die Datei beim Versand nicht mehr,
fehlt der Anhang. Schreibrechte des Upload-Verzeichnisses prüfen.

---

## E-Mail-Protokoll

### Kein „Erneut senden“ / keine Nachricht in der Detailansicht
Der Inhalt wurde nicht protokolliert. **Einstellungen → E-Mail-Protokoll → E-Mail-Inhalt
protokollieren** einschalten – das gilt nur für künftige E-Mails.

### Erneut gesendete E-Mail ohne Anhang
Gewollt: Anhänge werden nicht gespeichert, nur ihre Dateinamen.

### Alte Einträge werden nicht gelöscht
Aufbewahrungsdauer prüfen (Standard: *Unbegrenzt*) und unter **Werkzeuge → Scheduled Actions**, ob
`relaymint_daily_cleanup` läuft.
