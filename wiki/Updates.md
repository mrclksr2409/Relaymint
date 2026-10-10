# Updates

Relaymint aktualisiert sich über den
[Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (5.6, mitgeliefert)
direkt aus dem GitHub-Repository – wie ein Plugin aus dem offiziellen Verzeichnis. Updates
erscheinen unter **Dashboard → Aktualisierungen** und in der Plugin-Liste und lassen sich mit einem
Klick installieren (oder automatisch, wenn Auto-Updates für das Plugin aktiviert sind).

## Update-Kanal

**Relaymint → Einstellungen → Sonstiges → Update-Kanal**

| Kanal | Quelle |
|---|---|
| **Stabil (GitHub-Releases)** (Standard) | GitHub-Releases des `main`-Branches; installiert wird die angehängte `relaymint.zip` |
| **Beta (beta-Branch)** | Der aktuelle Stand des `beta`-Branches. Ein Update wird angeboten, sobald der `Version`-Header in `relaymint.php` auf `beta` höher ist als die installierte Version. |

> Der Beta-Kanal kann instabile Versionen liefern – **nicht** auf Produktivseiten verwenden.

Beim Wechsel des Kanals verwirft Relaymint die zwischengespeicherten Update-Daten, damit die nächste
Prüfung die neue Quelle verwendet.

**Zurück zu Stabil** führt kein Downgrade durch: Die Seite bleibt auf ihrer Beta-Version, bis ein
stabiles Release mit höherer Versionsnummer erscheint.

## Vor einem Update

- Datenbank-Änderungen werden nach dem Update beim nächsten Seitenaufruf automatisch durchgeführt
  (Vergleich der Schema-Version, siehe [Datenbank](Datenbank)).
- Den Browser-Cache musst du nicht leeren: Skripte und Styles tragen die Versionsnummer.
- Einstellungen, Protokoll und Warteschlange bleiben bei Updates erhalten.

## Änderungen nachlesen

Siehe [Changelog](Changelog) bzw. den Abschnitt *Changelog* im
[README](https://github.com/mrclksr2409/Relaymint#changelog).

## Update wird nicht angezeigt

1. In der Plugin-Liste bei Relaymint auf **Check for updates** klicken oder
   **Dashboard → Aktualisierungen → Erneut prüfen**.
2. Der Plugin-Ordner sollte `relaymint` heißen.
3. Der Server muss `github.com` und `api.github.com` erreichen können.
4. GitHub begrenzt anonyme API-Anfragen; bei vielen Seiten hinter einer IP kann die Prüfung
   vorübergehend scheitern – später erneut versuchen.

## Releases erstellen (für Maintainer)

Der Workflow `.github/workflows/release.yml` läuft bei jedem Tag `v*`: Er baut `relaymint.zip`
(ohne `.git`, `.github`, `.gitignore`, `phpcs.xml.dist`, `node_modules`, `tests` u. a.) und hängt
sie an das GitHub-Release des Tags.

```bash
git tag v0.4.1
git push origin v0.4.1
```

Die Versionsnummer steht an zwei Stellen in `relaymint.php`: im Header `Version:` und in der
Konstante `RELAYMINT_VERSION`.
