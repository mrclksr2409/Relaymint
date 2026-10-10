# Hooks und Filter

Eigener Code gehört in ein kleines eigenes Plugin (z. B. ein MU-Plugin) oder die `functions.php` des
(Child-)Themes – nicht in die Relaymint-Dateien, sonst ist er beim nächsten Update weg.

## Das Objekt `$mail`

Die meisten Hooks erhalten ein `Relaymint_Mail_Data`-Objekt:

| Eigenschaft / Methode | Rückgabe |
|---|---|
| `$mail->atts` | Die ursprünglichen `wp_mail()`-Argumente: `to`, `subject`, `message`, `headers`, `attachments` |
| `$mail->headers` | Geparste Header als Liste von `[ Name, Wert ]` |
| `$mail->to()` | Empfängeradressen als Array |
| `$mail->header_addresses( 'Cc' )` | Adressen aus einem Header (`Cc`, `Bcc`, `Reply-To` …) |
| `$mail->header_values( 'X-Foo' )` | Alle Werte eines Headers (Groß-/Kleinschreibung egal) |
| `$mail->from()` | Der `From`-Header des Aufrufs oder `''` |
| `$mail->is_html()` | `true`, wenn per Content-Type-Header oder Filter `wp_mail_content_type` HTML |
| `$mail->attachments()` | Pfade der Anhänge |
| `$mail->headers_string()` | Alle Header, einer pro Zeile |

`$initiator` ist ein Array mit `type` (`plugin`, `mu-plugin`, `theme`, `core` oder `unknown`),
`name` (z. B. der Plugin-Name aus dem Header oder `WordPress`) und `file` (Pfad der Datei, die
`wp_mail()` aufgerufen hat).

---

## Actions

| Hook | Parameter | Wann |
|---|---|---|
| `relaymint_before_send` | `Relaymint_Mail_Data $mail`, `string $connection` | Unmittelbar bevor Relaymint die E-Mail an PHPMailer übergibt. `$connection` ist die Verbindungs-ID oder `''`, wenn der Standard-PHP-Mailer verwendet wird. Feuert nicht für blockierte und gerade eingereihte E-Mails – bei E-Mails aus der Warteschlange erst beim tatsächlichen Versand. |

### Beispiel: Versand ins PHP-Fehlerprotokoll schreiben

```php
add_action( 'relaymint_before_send', function ( $mail, $connection ) {
	error_log( sprintf(
		'Relaymint: "%s" an %s über %s',
		$mail->atts['subject'],
		implode( ', ', $mail->to() ),
		'' !== $connection ? $connection : 'PHP mail()'
	) );
}, 10, 2 );
```

---

## Filter

| Filter | Parameter | Rückgabe | Zweck |
|---|---|---|---|
| `relaymint_route_connection` | `string $connection`, `Relaymint_Mail_Data $mail`, `array $initiator` | `string` | Verbindungs-ID für eine E-Mail, nach Smart Routing |
| `relaymint_log_entry_data` | `array $data`, `Relaymint_Mail_Data $mail` | `array` | Zeile eines neuen Protokolleintrags vor dem Speichern |
| `relaymint_rate_limits` | `array $limits` | `array` | Ratenlimits, Schlüssel = Zeitfenster in Sekunden |
| `relaymint_allowed_domains` | `string[] $domains` | `string[]` | Erlaubte Domains der Domain-Prüfung |
| `relaymint_do_not_send_result` | `bool $result` | `bool` | Rückgabewert von `wp_mail()` bei *Nicht senden* (Standard `true`) |
| `relaymint_queue_time_limit` | `int $seconds` | `int` | Max. Laufzeit eines Warteschlangen-Laufs (Standard `20`) |

### `relaymint_route_connection`

Läuft für jede E-Mail, die über eine Verbindung gehen soll – auch wenn Smart Routing ausgeschaltet
ist (dann ist `$connection` immer `primary`). Gib eine Verbindungs-ID zurück (`primary` oder z. B.
`cabc12345`). Ist die zurückgegebene Verbindung unbekannt oder unvollständig, versendet WordPress
über PHP `mail()`. Die Test-E-Mail durchläuft diesen Filter nicht.

```php
// Alle E-Mails eines bestimmten Plugins über eine eigene Verbindung
add_filter( 'relaymint_route_connection', function ( $connection, $mail, $initiator ) {
	if ( 'plugin' === $initiator['type'] && 'WooCommerce' === $initiator['name'] ) {
		return 'cabc12345';
	}
	return $connection;
}, 10, 3 );
```

### `relaymint_log_entry_data`

`$data` enthält die Spalten der Tabelle `wp_relaymint_email_log` (siehe [Datenbank](Datenbank)),
z. B. `status`, `subject`, `to_email`, `message`, `headers`, `initiator_name`.

```php
// Passwort-Links nie protokollieren (macht „Erneut senden“ für diese E-Mails unmöglich)
add_filter( 'relaymint_log_entry_data', function ( array $data, $mail ) {
	if ( false !== stripos( $data['subject'], 'Passwort' ) ) {
		$data['message'] = '';
		$data['headers'] = '';
	}
	return $data;
}, 10, 2 );
```

### `relaymint_rate_limits`

Eingangswert: `[ 60 => Pro Minute, 3600 => Pro Stunde, 86400 => Pro Tag, 604800 => Pro Woche ]`
aus den Einstellungen. Einträge mit `0` oder weniger werden danach entfernt. Wirkt nur, wenn
Hintergrundversand **und** Ratenbegrenzung eingeschaltet sind. Siehe [Rate Limiting](Rate-Limiting).

```php
add_filter( 'relaymint_rate_limits', function ( array $limits ) {
	$limits[ 5 * MINUTE_IN_SECONDS ] = 30; // zusätzlich max. 30 E-Mails in 5 Minuten
	return $limits;
} );
```

### `relaymint_allowed_domains`

Erhält die bereits normalisierten Domains (Kleinschreibung, ohne Schema, Pfad, Port und `www.`).
Gib die Domains ebenfalls in dieser Form zurück.

```php
add_filter( 'relaymint_allowed_domains', function ( array $domains ) {
	$domains[] = 'shop.example.com';
	return $domains;
} );
```

### `relaymint_do_not_send_result`

```php
// Blockierte E-Mails als Fehler melden, damit Plugins sie anzeigen
add_filter( 'relaymint_do_not_send_result', '__return_false' );
```

### `relaymint_queue_time_limit`

```php
// Längere Läufe, wenn der Server es zulässt
add_filter( 'relaymint_queue_time_limit', function () {
	return 50;
} );
```

---

## WordPress-Hooks, die Relaymint nutzt

Hilfreich, wenn sich Relaymint mit einem anderen Mail-Plugin in die Quere kommt:

| Hook | Priorität | Zweck |
|---|---|---|
| `pre_wp_mail` | `PHP_INT_MAX` | Entscheidung über jede E-Mail (blockieren, einreihen, Verbindung wählen). Hat ein anderes Plugin hier schon einen Wert geliefert, greift Relaymint nicht ein. |
| `phpmailer_init` | `PHP_INT_MAX` | PHPMailer für SMTP bzw. Microsoft Graph konfigurieren |
| `wp_mail_from`, `wp_mail_from_name` | `PHP_INT_MAX` | Absender der Verbindung setzen |
| `wp_mail_succeeded`, `wp_mail_failed` | 5 und 10 | Protokoll und Debug-Log aktualisieren |

## Action-Scheduler-Hooks

| Hook | Gruppe | Zweck |
|---|---|---|
| `relaymint_process_queue` | `relaymint` | Warteschlange abarbeiten |
| `relaymint_daily_cleanup` | `relaymint` | Tägliche Aufräumarbeiten |

## Konstanten

Siehe [Konstanten in wp-config.php](Konstanten-in-wp-config).
