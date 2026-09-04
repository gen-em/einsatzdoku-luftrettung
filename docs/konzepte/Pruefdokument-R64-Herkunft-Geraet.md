# Prüfdokument R64 — Herkunft und Gerät je Einsatz, Sperrvermerke in der Sicherung

**Zum Konzept `Konzept-R64-Herkunft-Geraet.md` (E-R64-14).** Das Prüfprotokoll
im Konzept beantwortet „ist es belegt?"; **dieses Dokument beantwortet „was
muss *ich* noch tun?"** — es ist für die Person vor der Anwendung geschrieben,
nicht für die Instanz am Code.

| | |
|---|---|
| Stand | 04.09.2026 — **AP1 und AP3 fertig** (Web 14.0.0 und 14.1.0). AP2, AP4 und AP5 stehen aus; ihre Abschnitte hier sind als solche gekennzeichnet |
| Zweig | `claude/rahmenplan-schritt-6-ewm0kx` (S4-Rest) |
| Erhoben an | Zweigstand `14028a9`; Ausgangspunkt `main` vom 04.09.2026 (Web 13.2.0, Uhr 3.0.0, Android 0.10.2) |
| Gehört zu | Prüfdokument S4 (`Pruefdokument-S4-Handy-Uhr-Client.md`) — dort steht eine Verweiszeile; die Server-Prüfungen von R64 stehen **hier** |

> **Dieses Dokument ist unvollständig, solange es diesen Kasten trägt.** Es
> wird nach jedem Arbeitspaket fortgeschrieben und ist erst mit AP5 fertig.
> Wer es vorher benutzt, prüft einen Zwischenstand — und der ist **nicht
> ausgeliefert**: Der S4-Rest kommt als Ganzes auf `main` (K7).

---

## 1. Was **nicht** geprüft werden konnte — und warum

**Das steht an erster Stelle, nicht in einer Fußnote.**

### 1.1 Der Bestand des Produktivservers

Alle Migrationszahlen unten sind an einer **lokalen** Installation gemessen
(272 Einsätze, 242 Ruhesegmente, drei Geräte). Der Produktivbestand ist
größer und anders zusammengesetzt; wie viele Einsätze dort eine Momentaufnahme
bekommen, hängt daran, wie viele Geräteverweise noch stehen — und das weiß
nur die Datenbank dort.

**Die einzige Zahl, die sich vorher nicht schätzen lässt**, ist die
Nachfüllung: `UPDATE missions m JOIN devices d ON d.id = m.device_id`. Am
Demo-Konto waren am 02.09.2026 **82 von 82** Einsätzen ohne Geräteverweis —
dort füllt die Migration nichts. Ob das für die echten Konten auch gilt, ist
Punkt **P-1** der Prüfliste.

### 1.2 Die Anzeige an einem echten Gerätebestand

Die drei neuen Plaketten sind an **selbst erzeugten** Einsätzen belegt
(`am-`, `wm-`, `cut-` an einer lokalen Installation). Ein Konto, das die
Android-App wirklich benutzt, hat sie noch nicht — das kommt mit dem
Gerätetest des S4-Rests.

### 1.3 Was zum Stand dieses Dokuments schlicht noch nicht existiert

| Fehlt | Kommt mit |
|---|---|
| Die Sicherung trägt Momentaufnahme und Sperrvermerke | **AP2** |
| Nutzlast 9, alle vier verdrahteten Nummern | **AP2** |
| Referenzbestand mit gekoppelten Geräten und einem Schnitt | **AP4** |
| Alle drei Kreisläufe auf 0 unerklärt | **AP4** |
| Demo-Fixture im neuen Format | **AP4** |

---

## 2. Was **maschinell** geprüft wurde — mit Mittel und Zahl

### 2.1 AP1 — Datenmodell, Ableitung, anlegende Stellen (Web 14.0.0)

| Prüfmittel | Ergebnis |
|---|---|
| `php tools/ingestprobe/probe.php` (echtes HTTP gegen `ingest.php`) | **39 Erwartungen, 0 nicht erfüllt** — davon **9 neu** in Teil 8 |
| Migration an einer Installation mit 272 Einsätzen / 242 Segmenten | `origin` **vorher** watch 177 · manual 5 · import 90 → **nachher** watch 162 · android 12 · wear 3 · manual 4 · import 90 · schnitt 1 |
| dieselbe Migration, Momentaufnahme | **85 von 272** Einsätzen gefüllt (81 uhr, 4 handy), **108 von 242** Segmenten (100 uhr, 8 handy). Der Rest hat keinen Geräteverweis mehr |
| zweiter Lauf von `update.php` | `skip` greift: „Nicht nötig (Schema bereits aktuell) — als erledigt vermerkt" |
| frische Installation aus `server/schema.sql` | vier Spalten vorhanden, `origin` = `varchar(16) NOT NULL DEFAULT 'watch'` |
| Migrationsregister gegengezählt | **42 = 42** (`update.php` gegen `schema.sql`) |
| Schnitt über `api/schneiden.php` (echtes HTTP, angemeldete Sitzung) | neuer Einsatz `cut-…`: `origin='schnitt'`, `manual=1`, `geraet_art='handy'`, `geraet_modell='Google Pixel 8'` **von der Quelle geerbt**, Gerät weiterhin `manual-1`; `track_cuts` eine Zeile, 181 Punkte gewandert |
| `php -l` über alle geänderten Dateien | 0 Fehler |

**Die neun neuen Fälle der Ingestprobe (Teil 8) im Einzelnen** — sie sind der
Kern des Belegs, dass die Herkunft am Präfix hängt und nicht an der Geräteart:

| Fall | Erwartet | Ergebnis |
|---|---|---|
| `m-` über das Uhr-Gerät | `watch` + Art/Modell der Uhr | ✓ |
| `am-` über das Handy-Gerät | `android` + Art/Modell des Handys | ✓ |
| `wm-` über **dasselbe** Handy-Gerät | `wear`, Gerät bleibt das Handy | ✓ |
| unbekanntes Präfix am Handy | `android` (Rückfall auf die Geräteart) | ✓ |
| **Gegenprobe:** dasselbe an der Uhr | `watch` | ✓ |
| Ruhesegment der Uhr | Art und Modell, **keine** Herkunftsspalte | ✓ |
| Ruhesegment des Handys | Art und Modell | ✓ |
| Gerät zwischen zwei Paketen umgeschrieben | Einsatz behält den **alten** Wert | ✓ |
| **Gegenprobe:** neuer Einsatz danach | bekommt den **neuen** Wert | ✓ |

### 2.2 AP3 — Export und Anzeige (Web 14.1.0)

| Prüfmittel | Ergebnis |
|---|---|
| `kreislauf.py --art csv --frisch` | **8965 Einzelvergleiche, 1023 erwartete, 5 unerklärte**, 0 ungenutzte Regeln |
| echter CSV-Export über die Oberfläche | `einsaetze.csv` **94 Spalten** (letzte zwei `geraet_art`, `geraet_modell`); `herkunft` = handy 12 / wear 3 / schnitt 1; `geraet_art` = handy 5, leer 11 |
| dasselbe, `ruhezeiten.csv` | **11 Spalten**, letzte zwei dieselben, `geraet_art` = handy 8, leer 34, **keine** Spalte `herkunft` |
| `felder.csv` | beschreibt alle vier neuen Zeilen |
| **Gegenprobe Excel (Standard)** | **31 Spalten** — dieselbe Liste wie in `Export-Format.md` 2, **unverändert** |
| `tools/wortliste/` (Bereiche a–e) | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** bei **79** Regeln |
| `tools/vollstaendigkeit/` | **278 Befunde**, unverändert gegenüber dem Stand vor R64 |
| `node --check` über die geänderten JS-Dateien | 0 Fehler |

**Die fünf unerklärten Abweichungen sind benannt und bleiben stehen.** Sie
liegen sämtlich in `felder.csv`: vier neue Zeilen (`geraet_art`,
`geraet_modell` je in `einsaetze.csv` und `ruhezeiten.csv`) und eine geänderte
Beschreibung (`herkunft`). Das ist **keine Eigenschaft des Umlaufs**, sondern
die Folge davon, dass die Referenzdatei vom 24.08.2026 älter ist als der Code.
Mit der Erneuerung der Referenz in **AP4** verschwinden sie. Eine
Ausnahmeregel dafür wäre ein Filter und kein Ausnahmegrund
(`tools/referenzdatensatz/vergleich/LIESMICH.md`).

### 2.3 AP2, AP4, AP5

*Steht aus.* Die Sollwerte stehen im Konzept, Abschnitt 7.1.

---

## 3. Was **im Browser** geprüft wurde

| Weg | Ergebnis |
|---|---|
| Einsatzansicht eines `am-`-Einsatzes (Chromium, angemeldet als Admin) | Plakette **„Handy"** |
| dasselbe an einem `wm-`-Einsatz | Plakette **„Wear"** |
| dasselbe an einem `cut-`-Einsatz | Plakette **„Schnitt"**, dazu „Spur · 181 Punkte" |
| Konsolenfehler auf diesen drei Seiten | nur die bekannten Kartenkachel-Abrufe (`ERR_CONNECTION_RESET`) — die Umgebung erreicht `tile.openstreetmap.org` aus Chromium nicht (dokumentiert in `tools/screenshots/aufnehmen.mjs`). **0** andere |
| Schneiden über die Tagesansicht | nicht über die Oberfläche gefahren, sondern über `api/schneiden.php` mit angemeldeter Sitzung — siehe Prüfpunkt **P-6** |

---

## 4. Die Prüfliste

Je Punkt: der **Bedienweg**, das **erwartete Ergebnis** und **woran ein
Scheitern zu erkennen ist**. Abhaken, was erledigt ist.

### P-1 — `update.php` aufrufen (**zuerst, sonst funktioniert nichts**)

- [ ] **Weg:** Nach dem Deploy als Administratorin `update.php` öffnen, die
      Liste ansehen, **„Ausstehende ausführen"** drücken.
- **Erwartet:** Die Zeile `2026_09_04_herkunft_geraet` (Web 14.0.0) steht als
      ausstehend da und läuft durch: „Erfolgreich angewendet."
- **Scheitern erkennbar an:** Jeder Upload der Uhr und des Handys antwortet
      danach mit einem Fehler (die vier Spalten fehlen im INSERT), und
      `ingest.php` schreibt einen 500er. **Sichtbar wird das an den Geräten,
      nicht im Web** — deshalb steht dieser Punkt zuerst.
- **Wichtig:** Die Migration ist **nicht** destruktiv (kein `zerstoert`, kein
  `inhalt`) und fragt daher nicht zurück. Ein Backup davor ist trotzdem
  richtig, wie bei jeder Schemaänderung.

### P-2 — Die Nachfüllung nachzählen

- [ ] **Weg:** Nach P-1 in der Datenbank zählen:

      SELECT origin, COUNT(*) FROM missions GROUP BY origin;
      SELECT COUNT(*) FROM missions      WHERE geraet_art IS NOT NULL;
      SELECT COUNT(*) FROM rest_segments WHERE geraet_art IS NOT NULL;

- **Erwartet:** `origin` kennt jetzt bis zu sechs Werte. Wie viele Zeilen eine
      Momentaufnahme bekommen haben, hängt am Bestand — **jede Zahl ist
      richtig, auch 0.** Der Sinn des Punkts ist, die Zahl zu **kennen**:
      Sie sagt, wie viel Aussagekraft die spätere Kachel (Nr. 88) und das
      Dashboard (Nr. 80) überhaupt haben können.
- **Scheitern erkennbar an:** `origin` enthält einen leeren String — dann ist
      der ENUM-Wechsel nicht vor den drei UPDATEs gelaufen. In dem Fall die
      betroffenen Zeilen aus dem `client_ref`-Präfix neu setzen.

### P-3 — Ein Einsatz vom Handy trägt die richtige Plakette

- [ ] **Weg:** Mit der Android-App einen Einsatz aufzeichnen und senden, dann
      im Web öffnen.
- **Erwartet:** Plakette **„Handy"**, nicht „Uhr".
- **Scheitern erkennbar an:** Steht dort „Uhr", ist entweder die Migration
      nicht gelaufen (P-1) oder der Browser hat eine alte
      `einsatz.php`/`style.css` im Zwischenspeicher — **einmal hart neu
      laden**, danach `WEB_VERSION` in der Fußzeile prüfen (muss ≥ 14.1.0
      sein).

### P-4 — Ein an der Wear-OS-Uhr begonnener Einsatz

- [ ] **Weg:** Einsatz an der Wear-OS-Uhr beginnen, das Handy sendet ihn.
      Im Web öffnen.
- **Erwartet:** Plakette **„Wear"** — und in der Geräteliste steht **kein
      zweites Gerät**: Gesendet hat das Handy.
- **Scheitern erkennbar an:** Plakette „Handy" statt „Wear" → die App vergibt
      das Präfix `am-` statt `wm-`; das ist dann ein Fehler der App, nicht des
      Servers. Plakette „Uhr" → Migration (P-1).
- **Hängt an:** einer Wear-OS-Uhr. Die gibt es noch nicht (Rahmenplan
      Schritt 6).

### P-5 — Die Geräteangaben im CSV-Export

- [ ] **Weg:** Einstellungen → Import / Export → Zeitraum „Alles", Format
      **CSV (Standard)**, exportieren; `einsaetze.csv` und `ruhezeiten.csv`
      öffnen und ganz nach rechts scrollen.
- **Erwartet:** Die zwei letzten Spalten heißen `geraet_art` und
      `geraet_modell`. Bei Einsätzen von einem gekoppelten Gerät stehen dort
      Werte, sonst ist die Zelle **leer** (nicht „unbekannt"). `felder.csv`
      beschreibt beide.
- **Scheitern erkennbar an:** Die Spalten fehlen → alte `export.js` im
      Zwischenspeicher. Sie stehen da, sind aber **überall** leer, obwohl
      Geräte gekoppelt sind → die Momentaufnahme ist beim Anlegen nicht
      gesetzt worden; dann `ingest.php` und die `devices`-Zeile prüfen.

### P-6 — Der Schnitt trägt seine eigene Herkunft

- [ ] **Weg:** In der Tagesansicht eine Ruhezeit mit Spur wählen, **„Einsatz
      schneiden"**, Zeitraum eintragen, schneiden. Den entstandenen Einsatz
      öffnen.
- **Erwartet:** Plakette **„Schnitt"** (nicht „manuell"). Im CSV-Export steht
      bei diesem Einsatz `herkunft = schnitt`, und `geraet_art`/`geraet_modell`
      tragen **dieselben Werte wie die Ruhezeit**, aus der geschnitten wurde.
- **Scheitern erkennbar an:** Plakette „manuell" → `api/schneiden.php` ist
      nicht auf dem neuen Stand. Leere Geräteangaben, obwohl die Quelle
      welche hatte → die Abfrage des Quellsegments holt die zwei Spalten
      nicht.

### P-7 — Excel bleibt, wie es war

- [ ] **Weg:** Denselben Zeitraum als **Excel (Standard)** exportieren und die
      Kopfzeile zählen.
- **Erwartet:** **31 Spalten**, keine davon neu. Dasselbe für **Excel
      (GuteSeele)**: unverändert.
- **Scheitern erkennbar an:** Jede zusätzliche Spalte ist ein Fehler — die
      Geräteangaben gehören ausdrücklich **nicht** nach Excel (E-R64-10).

### P-8 — Ein Export lässt sich weiterhin zurücklesen — **hier bereits gemessen**

- [x] **Weg:** Ein Archiv aus Web 14.1.0 (also **mit** den zwei neuen Spalten)
      in ein frisches Konto einlesen und wieder exportieren.
- **Gemessen am 04.09.2026** (`kreislauf.py --art csv --frisch` gegen ein
      Archiv aus dem Admin-Konto, 12 Einsätze):
      Profil **`export_csv_v1` erkannt**, „12 Einsätze, **0 Hinweise, 0
      Fehler**, 0 Dubletten", Import „12 Einsätze angelegt", Umlauf
      **2011 Einzelvergleiche, 171 erwartete, 0 unerklärte**.
      Die zwei unbekannten Spalten erzeugen also weder eine Warnung noch eine
      Abweichung — der Rückimport ordnet über Namen zu und geht über sie
      hinweg. Damit ist dieser Punkt **belegt** und nicht nur gelesen.
- **Trotzdem am Echtbestand nachziehen**, wenn dort andere Spalten stehen
      (Besatzung, Tracks, personenbezogene Angaben); das Probe-Archiv war
      klein — 21 Ausnahmeregeln haben mangels passender Daten nicht gegriffen.
- **Scheitern erkennbar an:** Der Import erkennt das Profil **nicht** mehr →
      dann stören die zwei neuen Spalten die Kopfzeilen-Erkennung.

> **Dabei aufgefallen (B-R64-02, kein Fehler dieses Pakets):** Ein
> **geschnittener** Einsatz kommt über den CSV-Rückimport **nicht** zurück.
> Der Schnitt vergibt nur die Phasen 3, 4 und 7 (`SCHNITT_PHASEN`), nie die
> Phase 2 (Alarmierung) — und `uhrzeit_ortszeit` ist im Importprofil eine
> **Pflichtangabe**. Die Zeile wird deshalb als Fehler angezeigt und muss von
> Hand korrigiert oder übersprungen werden. Dasselbe gilt für jeden Einsatz
> ohne Alarmierung, gleich welcher Herkunft. Gemessen: 4 von 16 Zeilen des
> Probe-Archivs. Das ist Bestandsverhalten seit Web 12.5.0 und war nirgends
> aufgeschrieben; R64 macht es nur sichtbar, weil es die Herkunft `schnitt`
> überhaupt erst benennt.

### P-9 bis P-… — Sperrvermerke und Referenz

*Entsteht mit AP2 und AP4.* Was dort geprüft werden muss, steht im Konzept,
Abschnitt 7.1: der edbak-Kreislauf mit dem Schnitt der Referenz, die
Wiederherstellungsprobe Teil 5 (fünf Grenzfälle), und der Demo-Reset als
**Dauerbeleg** auf dem Produktivserver.

---

## 5. Grenzen der benutzten Prüfmittel

| Mittel | Was es **nicht** sagt |
|---|---|
| **Ingestprobe** | Sie spricht mit `ingest.php` über echtes HTTP, aber mit **selbst angelegten** Geräten per SQL. Ob die Kopplung Art und Modell richtig einträgt, prüft `tools/kopplungsprobe/`, nicht sie. Und sie sagt nichts über die Uhr selbst — dass die Garmin-App `m-` und die Handy-App `am-` vergibt, steht in deren Quelltext und wird dort geprüft |
| **Migration an einer lokalen Kopie** | Die Zahlen gelten für **diesen** Bestand. Die Migration selbst ist dieselbe; die Wirkung ist es nicht |
| **CSV-Kreislauf** | Er misst den **Umlauf**, nicht die Anzeige. Und er vergleicht gegen eine Referenz vom 24.08.2026 — solange die nicht erneuert ist (AP4), sind Format­änderungen als Abweichung sichtbar und keine Regression |
| **Browserprüfung der Plaketten** | Drei Seiten, ein Browser, eine Fensterbreite. Sie sagt nichts über andere Bildschirmgrößen — die Plakette ist allerdings ein vorhandener Baustein und keine neue Darstellung (`Design.md` 9) |
| **Wortliste** | Sie zählt Wörter, nicht Sinn. Dass „Wear" für die Wear-OS-App die richtige Beschriftung ist, hat sie nicht geprüft — das ist eine Entscheidung (E-R64-09) |
| **Vollständigkeit** | Sie zählt CSS-Klassen und Symbole. R64 hat an der Gestaltung nichts geändert; die unveränderte 278 ist deshalb ein **Ausschluss** („nichts kaputtgemacht"), kein Nachweis |
