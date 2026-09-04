# Prüfdokument R64 — Herkunft und Gerät je Einsatz, Sperrvermerke in der Sicherung

**Zum Konzept `Konzept-R64-Herkunft-Geraet.md` (E-R64-14).** Das Prüfprotokoll
im Konzept beantwortet „ist es belegt?"; **dieses Dokument beantwortet „was
muss *ich* noch tun?"** — es ist für die Person vor der Anwendung geschrieben,
nicht für die Instanz am Code.

| | |
|---|---|
| Stand | 04.09.2026 — **AP1, AP3 und AP2 fertig** (Web 14.0.0, 14.1.0, 14.2.0). AP4 und AP5 stehen aus; ihre Abschnitte hier sind als solche gekennzeichnet |
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
| Referenzbestand mit gekoppelten Geräten und einem Schnitt | **AP4** |
| Alle drei Kreisläufe auf 0 unerklärt | **AP4** |
| Demo-Fixture im neuen Format | **AP4** |

### 1.4 Der Messstand am 5000er-Bestand (R35)

Er braucht seinen eigenen Prüfbestand, den diese Umgebung nicht trägt.
Gemessen ist stattdessen dasselbe an der Referenz — 87 Einsätze, ein Fenster,
ohne Spuren: **21,9 → 23,3 ms** Median (+1,4 ms), Datei **179 314 → 187 825
Byte** (+4,7 %), Speicherspitze 4,0 MB unverändert. Das ist die **Obergrenze**
des Zusatzaufwands bei durchweg leeren Vermerklisten, nicht der Regelfall.

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

### 2.3 AP2 — Sicherung: Nutzlast 9 (Web 14.2.0)

| Prüfmittel | Ergebnis |
|---|---|
| `php tools/wiederherstellungs-probe/probe.php` | **94 Erwartungen, 0 nicht erfüllt** — davon **18 neu** in Teil 11 |
| `node tools/containerprobe/probe.mjs` | **32 / 0** (vorher dauerhaft 31/1, siehe 1.5) |
| `php tools/spurprobe/probe.php` | **45 / 0** — hier hängt die Zusage „zwei ganze Sicherungen desselben Kontos sind gleich" |
| `php tools/ingestprobe/probe.php` | 39 / 0, unverändert |
| `kreislauf.py --art edbak-alt --frisch` | **287 743 Einzelvergleiche, 647 erwartete, 0 unerklärte**, 0 ungenutzte Regeln |
| `kreislauf.py --art edbak --frisch` | 253 343 Einzelvergleiche, 16 erwartete, **88 unerklärte** — 87 × `missions.schnitte` `None → []`, 1 × `kopf.version` `8 → 9` |
| Wortliste | 0 / 0 / 0 bei 79 Regeln |
| Vollständigkeit | **278 → 280** — beide neu in „Unicode-Zeichen als Symbol im Markup", beides Auslassungszeichen in **Kommentaren** |

**Die 88 des edbak-Kreislaufs verschwinden mit AP4** und bekommen deshalb
**keine** Ausnahmeregel: Sobald die Referenz selbst Nutzlast 9 ist, gleichen
sich beide Seiten. Eine Regel dafür wäre ein Filter.

**Die achtzehn neuen Fälle von Teil 11:** eine Nutzlast-9-Datei wird
angenommen · Art und Modell kommen an Einsatz und Segment zurück · ein Einsatz
ohne Angabe bleibt **NULL** · der Vermerk kommt an, mit unveränderten Zeiten
und Punktzahl · seine Quelle zeigt auf das **eingespielte** Segment · der
ursprüngliche `erstellt_am` reist mit · ein zweites Einspielen überspringt
statt zu verdoppeln · verwaiste Quelle → verworfen, **mit genannter Kennung**
· `bis_ts` vor `von_ts` → verworfen · `n_punkte = 0` → verworfen · unbekannte
`quelle_art` → verworfen und **nichts geschrieben** (die ENUM-Falle) · eine
Nutzlast-8-Datei ergibt keine Zähler und keine Meldung.

### 1.5 Drei Prüfmittel hatten aufgehört zu prüfen

Das gehört hierher und nicht in eine Fußnote, weil es die Aussagekraft
früherer Prüfungen berührt:

- Die **Wiederherstellungsprobe** starb mitten in Teil 9 an einem fehlenden
  `require` für `smtp.php` — nach 43 grünen Zeilen. **Teil 10 lief nie.** Am
  Zweigstand vor AP2 nachgestellt: der Absturz war vorher da.
- Die **Containerprobe** suchte in einer Fehlermeldung einen Wortlaut, den es
  nicht mehr gibt, und stand dauerhaft auf 31/1. Ebenfalls gegengeprüft.

Beides ist behoben. Wer eine ältere Meldung „Wiederherstellungsprobe grün"
liest, sollte wissen, dass sie höchstens bis Teil 9 reichte.

**Das dritte kam in AP4 dazu (F-R64-04).** Die **GPX-Probe** vergleicht in
Teil 2 die eingecheckten Referenz-GPX punktweise gegen die Datenbank und
ordnet über die interne Kennung im Dateinamen zu. Was sie nicht wiederfand,
übersprang sie **still** — und meldete „0 Abweichungen". Sie zählt seither
mit und nennt beide Zahlen. Der Lauf nach AP4 sagt: 172 Dateien zugeordnet,
0 ohne Gegenstück, **1 verglichen, 171 verdichtet übersprungen**.

> **Und das ist eine Grenze, keine Beruhigung.** Der Punkt-für-Punkt-Vergleich
> greift nur bei **rohen** Spuren; sobald der Nachlauf sie zu Blobs verdichtet
> hat, gehören sie zu Recht übersprungen. Wer die volle Aussagekraft dieses
> Teils will, fährt ihn **unmittelbar nach dem Einspiellauf**, bevor der
> Nachlauf gearbeitet hat. Vorher stand dieselbe Einschränkung auch schon da —
> nur unsichtbar.

### 2.4 AP4 — Referenz, Kreisläufe, Fixture (Web 14.2.1)

| Mittel | Zahl |
|---|---|
| `quelldaten/pruefen.py` | 16 Dienste, **87 Einsätze**, 100 Ruhesegmente, 1129 Zeitstempel, **5959 Einzelprüfungen**, **83 Matrixzeilen, 0 offen**, 99 Marken, 0 Befunde |
| dieselbe Prüfung gegen **fünf absichtlich eingebaute Fehler** | **5 von 5 gefangen**: Uhr-Präfix an einem Handy-Einsatz, Phase außerhalb des Schnittfensters, erfundene Teilenummer, Schnitt am neuesten Diensttag, zwei Uhren statt Uhr und Handy. Dazu die alte Beschriftung „Uhr Luftrettung (Referenz)" — sie fällt jetzt als Sperrwort auf |
| `generator/erzeugen.py` | 87 Einsätze, 100 Segmente, **56 587 Spurpunkte**, 526 Ingest-Anfragen, 82 GPX, **117 Strecken aus OSRM / 112 Luftlinie** (beides erstmals gezählt) |
| `generator/pruefen.py` | **283 990 Einzelprüfungen**, 182 Spuren, 81 Krypto-Rundläufe, **0 Befunde** |
| Einspiellauf (alle Stufen, reguläre Wege) | Kopplung 2 Geräte, **526 Ingest-Anfragen, 0 Fehler**, 16 Diensttage zugeordnet, 79 nachgetragen, 2 manuell, Papierkorb, Sperrliste **bestanden**, Schnitt: **65 Punkte gewandert, 259 geblieben, 3 Phasen** |
| Bestand danach | **88 Einsätze** (5 im Papierkorb), **100 Ruhesegmente**, **16 Diensttage**, **1 Sperrvermerk** |
| Momentaufnahme im Bestand | **0 von 82** Einsätzen und **0 von 100** Segmenten ohne `geraet_art` — also überall gesetzt |
| Herkunft im Bestand | android **39**, watch **39**, wear **3**, manual **2**, import **4**, schnitt **1** — **alle sechs Werte belegt** (vorher: einer) |
| `kreislauf.py --art edbak --frisch` | **287 713 Einzelvergleiche, 16 erwartete, 0 unerklärte.** Die 88 unerklärten aus AP2 sind ohne eine einzige Ausnahmeregel verschwunden |
| `kreislauf.py --art csv --frisch` | **9080 Einzelvergleiche, 1021 erwartete, 0 unerklärte** (eine neue Regel, GEMESSEN 1×) |
| `kreislauf.py --art edbak-alt --frisch` | **287 743 Einzelvergleiche, 647 erwartete, 0 unerklärte** |
| Fixture (`fixture/erzeugen.php`) | 88 Einsätze (85 mit Chiffretext), 100 Segmente, 16 Diensttage, 55 861 Spurpunkte, **2 Geräte mit Art und Modell**, **1 Sperrvermerk** in `daten` |
| Demo-Konto aus der Fixture (`demo_anlegen()`) | **88 / 5 / 100 / 16 / 1 Vermerk / 2 Geräte** — die Fixture spielt sich vollständig ein |
| `tools/spurprobe/` | 45 Erwartungen, **0 nicht erfüllt** |
| `tools/ingestprobe/` | 39 Erwartungen, **0 nicht erfüllt** |
| `tools/containerprobe/` | 32 Erwartungen, **0 nicht erfüllt** |
| `tools/wiederherstellungs-probe/` | 94 Erwartungen, **0 nicht erfüllt** |
| `tools/gpxprobe/` | **77** Erwartungen (2 neue), 0 nicht erfüllt — davon **172 GPX zugeordnet, 0 ohne Gegenstück**; verglichen wurden **1 von 172**, 171 sind vom Nachlauf verdichtet (siehe 1.5) |
| `tools/wortliste/` | **0 Treffer / 0 ungenutzte Ausnahmen**, 79 Regeln, alle fünf Bereiche |
| `tools/vollstaendigkeit/` | **280** — unverändert gegen AP2 |
| `tools/screenshots/` | **336 Einzelbilder, 42 Kontaktbögen, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px** |

### 2.4a F-R64-05 — „Dauer" heißt jetzt Dauer (Web 14.2.2)

| Mittel | Zahl |
|---|---|
| Gegenprobe vor der Änderung: fallen Phase 9 und `ended_at` je auseinander? | über **330 aktive Einsätze**: 323 gleich, **0 verschieden**, 3 mit Ende ohne Phase 9, 4 offen. Der Wechsel ändert also an keiner bestehenden Zeile etwas |
| Tagesansicht 14.06.2026 (Chromium) | Zeile des Schnitts: `7 · 16:10 · **1h 05min**`; „kein Ende" kommt auf der Seite **nicht mehr** vor |
| Einsatzansicht desselben Einsatzes | Kopfzeile „16:10 – 17:15 Uhr", Kopfzahl der Phasenkarte „1h 5min", **0** Konsolenfehler (ohne die bekannten Kartenkacheln) |
| Zeitraumübersicht 2026 | „kein Ende" **genau 1×** — der tatsächlich offene Einsatz vom 05.07.2026, 19:40 |
| `php -l` über die vier geänderten Dateien | 0 Fehler |

### 2.5 AP5

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
| **Geräteseite des Demo-Kontos** nach AP4 (Chromium, 1280 px) | zwei Geräte mit Modell: „Uhr Luftdienst (Referenz) · Uhr · fēnix 7 …" und „Handy Bodendienst (Referenz) · Handy · Samsung SM-S921B" — Beleg `docs/bilder/s4-rest/08-r64-geraete-mit-modell.png` |
| **Tagesansicht 14.06.2026** nach AP4 | der geschnittene Einsatz als Nr. 7 um 16:10 mit der Dauer **1h 05min**, am Ruhesegment „geschnitten 16:10 – 17:15" mit „Schnitt zurücknehmen" — Beleg `docs/bilder/s4-rest/09-r64-tag-mit-schnitt.png` (nach der Behebung von F-R64-05 neu aufgenommen) |
| **Einsatzansicht eines `am-`-Einsatzes im Bilderlauf** (1024 px) | Plakette **„Handy"** neben „editiert" und „Spur · 406 Punkte", Diagnose entschlüsselt — `tools/screenshots/ausgabe/einzeln/12-einsatzansicht-1024.png` |
| **Geschützte Angaben im Demo-Konto nach dem Einspielen der Fixture** | lesbar (Diagnose „Schädel-Hirn-Trauma bei Motorradunfall"), **0 Konsolenfehler** |

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

### P-9 — Ein Schnitt übersteht Sicherung und Wiedereinspielen

- [ ] **Weg:** Einen Einsatz aus einer Ruhezeit schneiden (P-6). Danach eine
      **Konto-Sicherung** erzeugen und in ein **frisches** Konto einspielen.
- **Erwartet:** Die Rückmeldung nennt eine Zeile „Sperrvermerke des
      Schneidens: 1 übernommen." Im Zielkonto trägt die Ruhezeit weiterhin
      ihre Sperre — ein Gerät, das den geschnittenen Zeitraum nachliefert,
      kommt dort **nicht** durch.
- **Scheitern erkennbar an:** Die Zeile fehlt → die Datei trug keine Vermerke
      (dann ist die Sicherung älter als Web 14.2.0). Sie sagt „verworfen" →
      die Quelle war nicht auflösbar; die Aufschlüsselung nennt den Grund, die
      Kennung steht in den abgelehnten Werten.
- **Wichtig:** In **dasselbe** Konto einzuspielen ergibt „übersprungen", nicht
      „übernommen" — das ist richtig so und keine Panne (Konzept 5.5).

### P-10 — Eine ältere Sicherung lässt sich weiterhin einspielen

- [ ] **Weg:** Eine Sicherung aus der Zeit **vor** Web 14.2.0 (Nutzlast 8)
      in ein Testkonto einspielen.
- **Erwartet:** Sie läuft durch wie bisher. Keine Zeile zu Sperrvermerken,
      Geräteangaben bleiben leer.
- **Scheitern erkennbar an:** „stammt aus einer neueren Fassung" — das wäre
      verkehrt herum und hieße, dass die Schranke falsch steht.

### P-11 — Eine neue Sicherung auf einem alten Stand

- [ ] **Weg:** Nur falls eine zweite, ältere Installation zur Hand ist: eine
      Sicherung aus Web 14.2.0 dort einspielen.
- **Erwartet:** Sie wird **abgewiesen** mit „stammt aus einer neueren
      Fassung". Genau dafür ist die Schranke gebaut.
- **Scheitern erkennbar an:** Sie läuft durch — dann legt der alte Stand einen
      halben Bestand an, und das fällt erst später auf.

### P-12 — Eine Umdatierung nimmt die Sperre mit

- [ ] **Weg:** Einen Diensttag mit einem geschnittenen Einsatz **umdatieren**
      (auf einen anderen Tag verschieben). Danach die Ruhezeit ansehen.
- **Erwartet:** Die Sperre liegt weiterhin über demselben **Stück Spur** wie
      vorher, nicht über dem alten Uhrzeitfenster.
- **Scheitern erkennbar an:** Ein Gerät liefert nach der Umdatierung den
      geschnittenen Bereich nach und er kommt durch — die Fahrt liegt dann in
      Einsatz und Segment. Das war bis Web 14.2.0 der Zustand.

### P-13 — Das Demo-Konto zeigt nach dem Deploy den neuen Bestand

**Weg:** Nach dem Merge auf `main` und dem Aufruf von `update.php` das
Demo-Konto öffnen (`demo@gen-em.org` / `nadokudemo0815`) und **einen
Reset abwarten** (höchstens 30 Minuten) oder ihn im Adminbereich auslösen.

**Erwartet:** Die Tagesliste zeigt **16 Diensttage**, der Bestand **83
aktive Einsätze**. Unter *Einstellungen → Geräte* stehen **zwei** Geräte mit
Modell: „Uhr Luftdienst (Referenz) · Uhr · fēnix 7 …" und „Handy Bodendienst
(Referenz) · Handy · Samsung SM-S921B".

**Scheitern erkennt man daran:** Die Geräteseite zeigt „Gerät unbekannt"
oder gar keine Modellzeile → die Fixture ist nicht mitgegangen (sie liegt
unter `server/demo/` und wird vom Deploy mit hochgeladen; ohne erhöhte
`WEB_VERSION` sieht der Browser außerdem alte Dateien).

### P-14 — Der geschnittene Einsatz und seine Sperre

**Weg:** Im Demo-Konto den **14.06.2026** öffnen.

**Erwartet:** In der Einsatzliste steht als letzter Eintrag ein Einsatz um
**16:10** ohne Einsatzort, Alter und Diagnose. Unter *Ruhesegmente* trägt das
Segment ab 15:21 den Vermerk **„geschnitten 16:10 – 17:15"** und den Knopf
**„Schnitt zurücknehmen"**. Die Einsatzansicht dieses Einsatzes zeigt die
Plakette **„Schnitt"** und „Spur · 65 Punkte".

**Scheitern erkennt man daran:** Der Vermerk fehlt → der Sperrvermerk hat
die Sicherung nicht überstanden, und Backlog Nr. 63 ist nicht behoben. Genau
das prüft dieser Weg — und weil der Reset alle 30 Minuten läuft, prüft er
sich danach von selbst.

**Und die Dauer gehört dazu** (F-R64-05, behoben in Web 14.2.2): In der
Spalte *Dauer* muss **„1h 05min"** stehen. Bis 14.2.1 stand dort „kein
Ende" — die Tabelle rechnete aus Beginn und Phase 9, und ein geschnittener
Einsatz hat keine. Steht dort wieder „kein Ende", ist die alte Rechnung
zurück (oder der Browser hat eine alte `missiontable.js`).

Dasselbe auf der **Einsatzansicht** dieses Einsatzes: Kopfzeile
**„16:10 – 17:15 Uhr"** (nicht „16:10 Uhr – kein Ende"), Kopfzahl der Karte
*Einsatzphasen* **„1h 5min"**. Und in der **Zeitraumübersicht** 2026 darf
„kein Ende" **genau einmal** vorkommen — am tatsächlich offenen Einsatz vom
05.07.2026, 19:40.

### P-15 — Ein CSV-Export lässt sich wieder einlesen

**Weg:** Im Demo-Konto *Einstellungen → Import / Export* → Export als CSV
mit personenbezogenen Angaben. Das Archiv in ein **frisches** Konto
importieren.

**Erwartet:** Die Prüftabelle meldet **83 Einsätze, 0 Fehler**. Vor Web
14.2.1 war es **82 Einsätze und 1 Fehler** — der geschnittene fiel durch,
weil ihm die Alarmzeit fehlte.

**Scheitern erkennt man daran:** „1 Zeile(n) mit Fehler sind weder korrigiert
noch übersprungen" und der Import bleibt gesperrt → der Rückfall auf den
Einsatzbeginn in `export.js` greift nicht (alte Datei im Browser-Zwischenspeicher?).

---

## 5. Grenzen der benutzten Prüfmittel

| Mittel | Was es **nicht** sagt |
|---|---|
| **Ingestprobe** | Sie spricht mit `ingest.php` über echtes HTTP, aber mit **selbst angelegten** Geräten per SQL. Ob die Kopplung Art und Modell richtig einträgt, prüft `tools/kopplungsprobe/`, nicht sie. Und sie sagt nichts über die Uhr selbst — dass die Garmin-App `m-` und die Handy-App `am-` vergibt, steht in deren Quelltext und wird dort geprüft |
| **Der Einspiellauf** | Er koppelt seit AP4 **echt** über `pair.php` — damit prüft er den Kopplungsweg mit. Was er nicht prüft: den Weg der Uhr und der Handy-App bis dorthin. Dass die Garmin-App `m-` und die Handy-App `am-` vergibt, steht in deren Quelltext und wird dort geprüft |
| **Migration an einer lokalen Kopie** | Die Zahlen gelten für **diesen** Bestand. Die Migration selbst ist dieselbe; die Wirkung ist es nicht |
| **CSV-Kreislauf** | Er misst den **Umlauf**, nicht die Anzeige. Seit AP4 vergleicht er gegen die erneuerte Referenz vom 04.09.2026; die fünf Abweichungen aus AP3 sind damit verschwunden |
| **Browserprüfung der Plaketten** | Drei Seiten, ein Browser, eine Fensterbreite. Sie sagt nichts über andere Bildschirmgrößen — die Plakette ist allerdings ein vorhandener Baustein und keine neue Darstellung (`Design.md` 9) |
| **Wortliste** | Sie zählt Wörter, nicht Sinn. Dass „Wear" für die Wear-OS-App die richtige Beschriftung ist, hat sie nicht geprüft — das ist eine Entscheidung (E-R64-09) |
| **Vollständigkeit** | Sie zählt CSS-Klassen und Symbole. R64 hat an der Gestaltung nichts geändert; die unveränderte **280** ist deshalb ein **Ausschluss** („nichts kaputtgemacht"), kein Nachweis |
