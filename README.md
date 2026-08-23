# HEMS Einsatzdoku

Dokumentation von Hubschraubereinsätzen: Garmin-Uhr-App (Fenix 6 Pro,
Connect IQ) erfasst Phasen, GPS-Tracks und Reanimations-Ereignisse und lädt
sie auf einen eigenen Server; die Web-App (PHP/MySQL) zeigt Flugtage,
Einsätze und Rea-Protokolle und erlaubt Nachtragen/Bearbeiten. Diagnose,
Alter und Einsatzort sind **Ende-zu-Ende-verschlüsselt** (Schlüssel aus dem
Login-Passwort, Wiederherstellungsschlüssel als Rettungsanker); ein
verschlüsseltes **Backup** (.edbak) sichert alle Daten in eine Datei.

Zum Ausprobieren gibt es ein **Demo-Konto** mit erfundenen Daten
(`demo@gen-em.org` / `nadokudemo0815`), das sich alle 30 Minuten selbst
zurücksetzt. Es ist die **einzige** Stelle, an der die
Ende-zu-Ende-Verschlüsselung bewusst ausgesetzt ist — sein Schlüsselmaterial
liegt auf dem Server, damit die Rücksetzung funktioniert. Näheres im Handbuch
(Abschnitt 3.2) und in `docs/Technik.md` 4.99a.

## Dokumentation

| Dokument | Inhalt |
|---|---|
| [`docs/Handbuch.md`](docs/Handbuch.md) | Vorstellung und Bedienung aller Funktionen (Uhr + Web) |
| [`docs/Technik.md`](docs/Technik.md) | Architektur, Datenmodell, Abläufe, Build, Deployment, **Betrieb/Runbook** |
| [`docs/CHANGELOG.md`](docs/CHANGELOG.md) | Änderungshistorie |
| [`docs/JSON-Vertrag.md`](docs/JSON-Vertrag.md) | Schnittstelle Uhr/Fremdquellen → Server |
| [`docs/Backup-Format.md`](docs/Backup-Format.md) | Aufbau der `.edbak` und was **nicht** darin steht |
| [`docs/Export-Format.md`](docs/Export-Format.md) | CSV- und Excel-Export, Rückimport |
| [`docs/Backlog.md`](docs/Backlog.md) | bewusst offene Punkte, Nummern sind dauerhaft |
| [`tools/referenzdatensatz/LIESMICH.md`](tools/referenzdatensatz/LIESMICH.md) | erfundener Beispielbestand: Demo-Konto **und** Regressionsreferenz |
| `docs/archiv/` | eingefrorene Bau-Spezifikation (nicht mehr gepflegt) |

## Schnellstart

**Server:** `server/` auf den Webspace, leere MySQL-DB bereitstellen,
`index.php` aufrufen → der Installer führt durch die Einrichtung
(Details: Technik-Doku, Abschnitt Betrieb).

**Uhr:** `watch/` mit VS Code + Monkey-C-Erweiterung + Connect-IQ-SDK bauen
(Ziel `fenix6pro`; vorher die Server-Domain in `properties.xml` eintragen),
`.prg` per USB nach `GARMIN/Apps/`, dann **per Code koppeln**: Web →
Einstellungen → Geräte → Code erzeugen; Uhr → Sync-Seite (DOWN) → START
halten → Code eintippen (Details: Handbuch, Abschnitt 4).

**Deployment:** Push auf `main` deployt `server/` automatisch per FTPS
(GitHub Actions). Nach DB-Änderungen als Admin `update.php` aufrufen.
