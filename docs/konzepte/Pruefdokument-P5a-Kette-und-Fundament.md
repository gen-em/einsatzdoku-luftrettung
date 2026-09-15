# Prüfdokument P5a — Kette und Fundament

**Konzept:** `Konzept-P5a-Kette-und-Fundament.md` (15.09.2026, E-P5a-01 bis
-21, AP1 bis AP12). **Gemessen auf:** Zweig `claude/butte-umsetzen-5opi9u`.
**Stand dieses Dokuments:** 15.09.2026, nach AP1.

Das Prüfprotokoll im Konzept beantwortet „ist es belegt?". Dieses Dokument
beantwortet „was muss **ich** noch tun?" (`CLAUDE.md` 7, K9).

---

## 0. Was nicht geprüft werden konnte und warum

**Diese Liste steht am Anfang, nicht in einer Fußnote.**

| # | Was | Warum nicht | Wie es doch geprüft wird |
|---|---|---|---|
| N1 | **Die Arbeitsläufe selbst** (`pruefung.yml`, `auslieferung.yml`, `integritaet.yml`) | Ein GitHub-Arbeitslauf läuft nur bei GitHub. Der Container hat keine Actions-Umgebung, und ein `act`-Nachbau misst etwas anderes als das Original — vor allem bei Umgebungen mit Pflichtfreigabe. | Geprüft ist bisher nur, dass die drei Dateien **gültiges YAML** sind und die erwarteten Auslöser und Jobs tragen (Zahlen unten). Der erste echte Lauf ist Prüfpunkt **P1** unten. |
| N2 | **Die Pflichtfreigabe der Umgebung `produktion`** | Sie ist eine Einstellung des GitHub-Kontos und braucht das echte Konto der Betreiberin. | Prüfpunkt **P3** — Probe-Tag, Freigabe von Hand. |
| N3 | **Das Backup-Tor gegen eine echte Installation** | Es verlangt einen Produktivserver mit Job-Token und ein 10-GB-Backup. | Die **Selbstprobe** misst die Logik an fünf Lagen ohne Netz (5/5 erfüllt); der echte Lauf ist Prüfpunkt **P2**. |
| N4 | **Stufe 2** (Kreisläufe, Bilderlauf, Messstand gegen Staging) | Staging existiert noch nicht (Zuarbeit, Rahmenplan Abschnitt 6). | Prüfpunkt **P4**. |
| N5 | **Uhr Stufe I im Arbeitslauf** | `CIQ_GERAETE_URL` ist in dieser Arbeitsumgebung gesetzt, als GitHub-Secret aber noch nicht hinterlegt (Zuarbeit). | Der Schritt ist so gebaut, dass er **ausdrücklich überspringt und es sagt** — nie ein stilles Grün. Prüfpunkt **P5**. |
| N6 | **`./gradlew build` im Arbeitslauf** | Im Container liegt das SDK unter `/opt/android-sdk`; der GitHub-Läufer bringt ein eigenes mit. Ob die Pfade dort zusammenpassen, zeigt erst der Lauf. | Prüfpunkt **P1**. |

---

## 1. Was maschinell geprüft wurde — mit Mittel und Zahl

Alles unten auf dem Stand nach AP1 (Web 20.4.0), im Wegwerf-Container
(PHP 8.4.19, Python 3.11.15, Node 22.22.2, MariaDB 10.11.14).

| Mittel | Aufruf | Ergebnis |
|---|---|---|
| PHP-Syntax | `php -l` über `server/` und `tools/` | **0 Fehler** |
| YAML der drei Arbeitsläufe | `yaml.safe_load` | **3 von 3 gültig**; `pruefung.yml` → Auslöser `push`, `pull_request`, `workflow_dispatch`, Job `pruefung`; `auslieferung.yml` → `push`, `workflow_dispatch`, Jobs `staging`, `stufe2`, `produktion`; `integritaet.yml` → `schedule`, `workflow_run`, `workflow_dispatch`, Job `wache` |
| Migrationsregister | `php tools/migrationsregister/pruefen.php` | **46 Kennungen im Katalog, 46 in der Vorabliste, 30 Tabellen, 180 Spalten, 29 Löschungen, 0 Befunde, 4 erklärte Ausnahmen, 0 ungenutzte** |
| Migrationsregister, Selbstprobe | `… --selbstprobe` | **4 von 4** eingebauten Schäden gefunden (Prüfungen 1, 2, 4, 5) |
| Backup-Tor | `python3 tools/kette/tor.py --selbstprobe` | **5 von 5** Lagen erfüllt |
| Wortliste | `python3 tools/wortliste/wortliste.py` | **0 Treffer außerhalb der Ausnahmen (in 0 Zeilen), 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen**; 96 Regeln, 96 gegriffen |
| Kontraste | `python3 tools/screenshots/kontrast.py` | **22 Paare gerechnet, 0 verfehlt** |
| Vollständigkeit | `python3 tools/vollstaendigkeit/pruefen.py` | **340 Befunde** — und zwar **unverändert**: derselbe Lauf auf `origin/main` (`7f334cb`) in einem zweiten Arbeitsbaum meldet ebenfalls **340**. AP1 fasst `style.css` nicht an. |
| Backlog-Nummern | `grep -oE '^[0-9]+\.' docs/Backlog.md \| tr -d '.' \| sort -n \| uniq -d` | **leer** |

**Was die Zahlen benennen** (`CLAUDE.md` 6): Die Wortliste hat sechs Bereiche
gemessen (Server-PHP, Skripte, Dokumentation, Android, `watch/`), nicht einen;
die Vollständigkeitszahl ist ein **Vergleich gegen den Ausgangsstand**, keine
absolute Güte — 340 ist der Altbestand aus P3, den AP4 und spätere Pakete
teilweise abbauen (13 `style=`-Attribute, davon **3 in PHP**: die drei, die
AP4 auflöst).

---

## 2. Was im Browser geprüft wurde

**Nach AP1: nichts, und das ist richtig.** AP1 ändert an der Oberfläche keine
Zeile. Die einzige Codeänderung ist der Parameter `aktion` in `jobs.php` —
ein JSON-Endpunkt ohne Oberfläche, der sich mit dem Token aufrufen lässt
(Prüfpunkt P6).

---

## 3. Die Prüfliste

Je Punkt: der Bedienweg, das erwartete Ergebnis, und **woran ein Scheitern zu
erkennen ist**.

### P1 — Stufe 1 läuft grün (nach dem ersten Push)

**Weg:** Den Arbeitszweig pushen, in GitHub → Actions den Lauf **Prüfung**
öffnen.
**Erwartet:** Alle Schritte grün. In der Zusammenfassung stehen Zahlen: „PHP-
Syntax: N Dateien, 0 Fehler", der Wortlisten-Auszug, das Migrationsregister
mit seinen sechs Zahlen.
**Scheitern erkennbar an:** einem roten Schritt — **oder** an einer
Zusammenfassung, in der „ÜBERSPRUNGEN" steht, wo es nicht stehen soll. Ein
grüner Lauf mit zwei übersprungenen Schritten ist kein grüner Lauf.

### P2 — Das Backup-Tor bricht gegen die echte Installation ab

**Weg:** Den Produktionslauf mit einem Tag starten, dessen `JOBS_TOKEN`
absichtlich falsch ist (Umgebungsgeheimnis kurz ändern).
**Erwartet:** Der Schritt „Backup-Tor" endet **rot**, mit der Zeile „Die
Installation antwortet mit 'token' … Es wird nicht ausgeliefert." **Der
FTPS-Schritt läuft gar nicht erst.**
**Scheitern erkennbar an:** einem Lauf, der trotzdem hochlädt — dann steht das
Tor offen. Danach das Geheimnis zurücksetzen und den Lauf wiederholen.

### P3 — Die Pflichtfreigabe hält an

**Weg:** Tag `web-v20.4.0` setzen (oder die dann gültige Fassung). In Actions
den Lauf **Auslieferung** öffnen.
**Erwartet:** Der Job `produktion` steht auf **„Waiting"** mit dem Hinweis auf
die nötige Freigabe. Erst nach dem Klick auf *Review deployments → Approve*
läuft er los.
**Scheitern erkennbar an:** einem Job, der ohne Rückfrage durchläuft — dann ist
in der Umgebung `produktion` kein Reviewer eingetragen.

### P4 — Stufe 2 misst gegen Staging

**Weg:** Nach der Einrichtung von Staging (Zuarbeit) einen Push auf `main`.
**Erwartet:** Job `stufe2` grün; Kreisläufe **0 unerklärt**, Bilderlauf
**0 Überlauf, 0 Konsolenfehler, 0 falsche Knopfhöhen**.
**Scheitern erkennbar an:** „ÜBERSPRUNGEN (kein Prüfkonto)" in der
Zusammenfassung — dann fehlen `STAGING_KONTO`/`STAGING_PASS`, und **gemessen
wurde nichts**.

### P5 — Uhr Stufe I im Lauf

**Weg:** `CIQ_GERAETE_URL` als Repositoriums-Secret hinterlegen, Push.
**Erwartet:** Der Schritt übersetzt die App für alle Zielgeräte und endet
grün.
**Scheitern erkennbar an:** der Warnung „CIQ_GERAETE_URL nicht gesetzt — Uhr
Stufe I ÜBERSPRUNGEN, nicht bestanden".

### P6 — Die vier Aktionen von `jobs.php` gegen eine echte Installation

**Weg:** Mit dem Job-Token aus Betrieb → Jobs:

```
python3 tools/kette/tor.py zustand     --basis https://… --token …
python3 tools/kette/tor.py wartung-an  --basis https://… --token …
python3 tools/kette/tor.py wartung-aus --basis https://… --token …
python3 tools/kette/tor.py backup      --basis https://… --token … --versuche 3 --pause 5
```

**Erwartet:** `zustand` nennt `version`, `wartung`, `komplett` und
`migration_ausstehend`. `wartung-an` legt `server/wartung.lock` an, und die
Startseite antwortet danach mit **503** — der Wartungsbalken auf
Betrieb → Updates sagt „von kette". `wartung-aus` räumt sie weg.
**Scheitern erkennbar an:** `{"ok": false}` mit Meldung — bei `wartung-an`
heißt das fast immer: die Anwendungswurzel ist nicht beschreibbar.

### P7 — Die Integritätswache hält nach einem Staging-Deploy still

**Weg:** Push auf `main` (Staging-Deploy). In Actions den Lauf
**Integritaetswache** öffnen.
**Erwartet:** Der erste Schritt meldet „Job 'produktion' in Lauf …: nicht
gelaufen", die beiden Arbeitsschritte sind übersprungen, in der
Zusammenfassung steht „Dieser Lauf ging nicht auf Produktiv — die Wache hält
still."
**Scheitern erkennbar an:** einer roten Wache nach einem Staging-Deploy. Dann
vergleicht sie Produktiv gegen einen Stand, der dort nicht liegt.

### P8 — Die Wache löst nach einem Produktiv-Deploy aus

**Weg:** Nach P3 denselben Lauf ansehen.
**Erwartet:** Die Wache läuft, Selbstprobe grün, Vergleich grün.
**Scheitern erkennbar an:** gar keinem Lauf — dann stimmt der Name in
`integritaet.yml` nicht mehr mit dem Anzeigenamen in `auslieferung.yml`
überein (Fund F4).

---

## 4. Zuarbeiten, ohne die Punkte offen bleiben

Aus dem Rahmenplan, Abschnitt 6 — hier nur, was P1 bis P8 blockiert:

1. GitHub-Umgebungen `staging` und `produktion` anlegen; die drei
   FTP-Geheimnisse je Umgebung, `JOBS_TOKEN` nur bei `produktion`.
2. **Pflichtfreigabe** („required reviewers") an der Umgebung `produktion`.
3. Zweigschutz auf `main` mit `pruefung` als **Pflichtprüfung** — ohne ihn ist
   Stufe 1 eine Auskunft und keine Schranke.
4. Variablen: `FTP_ZIELPFAD` je Umgebung, `STAGING_URL`, `PRODUKTION_URL`.
5. Staging-Installation samt Prüfkonto (`STAGING_KONTO`, `STAGING_PASS`).
6. `CIQ_GERAETE_URL` als Repositoriums-Secret.

---

## 5. Grenzen der benutzten Prüfmittel

- **`tools/migrationsregister/`** sieht keine DDL, die aus eingesetzten Namen
  gebaut wird (`ALTER TABLE \`$tab\` …`). Vier Spalten fallen heute genau so;
  sie stehen mit Begründung in `ausnahmen.json`. Eine Migration, die ihre
  ganze DDL so baut, ist für das Werkzeug unsichtbar.
- **`tools/kette/tor.py --selbstprobe`** misst die Entscheidungslogik, nicht
  das Netz: Ob die Gegenstelle wirklich der Produktivserver ist, ob das
  Backup lesbar ist und ob der Serverschlüssel der richtige ist, prüft sie
  nicht (das erste ist Sache der Umgebung, das zweite und dritte Sache von
  `tools/wiederherstellungs-probe/`).
- **Die YAML-Prüfung** sagt, dass die Dateien gültig sind, nicht dass sie das
  Richtige tun. Ein Ausdruck in `if:` kann syntaktisch einwandfrei und
  inhaltlich falsch sein; das zeigt erst P1.
- **`tools/vollstaendigkeit/`** misst das Stylesheet gegen den Stand vor P3.
  Seine Zahl ist ein Vergleich, keine Güte.
