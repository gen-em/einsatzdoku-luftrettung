# Prüfdokument — Demo-Ausbau (Schritt 9d)

*Angelegt am 15.09.2026 (Fable) mit dem Konzept `Konzept-Demo-Ausbau.md` als
Vorlage, **gefüllt von der Umsetzung** am 14./15.09.2026. Abschnitt 2 und 3
beantworten „ist es belegt?", Abschnitt 4 beantwortet „was muss **ich** noch
tun?". Muster: `Pruefdokument-Backlog-Runde-3.md`. **Dieses Dokument bleibt**,
bis seine Prüfliste abgehakt ist (K9); das Konzept wird nach der Freigabe
gelöscht (R62).*

> | | |
> |---|---|
> | Stand | **gebaut, geprüft und mit `main` zusammengeführt**, 15.09.2026 · Zweig `claude/umsetzung-ohne-pausen-2vfppr` · sechs Commits (AP0–AP4 und der Merge-Nachlauf) |
> | Stufe | **Web 20.3.0** (Neben) — nur AP0 stuft; **keine Migration**, `update.php` muss nach dem Deploy **nicht** laufen; Uhr und Android unberührt |
> | Bestand danach | **21 Diensttage** (20 aktiv, 1 im Papierkorb) · **106 Einsätze** (101 aktiv, 5 im Papierkorb; 103 aus den Quelldaten + 3 Schnitte) · **119 Ruhesegmente** (114 aktiv) · **63 752 Spurpunkte** · 3 Standorte (alle mit Koordinate) · 10 Rettungsmittel, davon **4 ohne Standort** · 12 Zielkliniken · 6 Bergwacht-Bereitschaften · 10 weitere Rettungsmittel · 2 Geräte (+ „Manuelle Einträge") |
> | Neu entstanden | `quelldaten/dienste/D17.json` bis `D21.json` · `referenz/einsatzdoku-backup-2026-09-15.edbak` und `referenz/…_15-09-2026_csv_mit-pers_unverschl_demo-gen-em-org.zip` (die beiden alten Dateien vom 13.09.2026 sind ersetzt) · `server/demo/fixture.json.gz` neu · 24 neue `generator/routen/strecke_*.geojson` · `generator/ausgabe/fusswege.json` · `einspielen/demo_kennzeichnen.php` · `tools/klickprobe/wege/da.mjs` · drei neue Seiten im Bilderlauf |
> | Prüfumgebung | Container ohne KVM · PHP **8.4.19** (cli, `php -S`) · MariaDB **10.11.14** · TLS über `socat` vor `127.0.0.1:8443` · Playwright mit **Chromium 141**, Firefox 142, WebKit 26 (`/opt/pw-browsers`) · Node **22.22.2** |
> | Ergebnis | **Alle Prüfmittel grün.** Vier Fehlerfunde während des Laufs, alle behoben (F-DA-7 bis F-DA-10); eine Folge bewusst offen gelassen (Backlog Nr. 198) |
> | Nach dem Merge | `main` ist am 15.09.2026 um PR #47 (R42-Nachlauf) gewachsen. Dabei **drei Nummernkollisionen** aufgelöst (Backlog 190/191 → **197/198**, Rahmenplan-Fassung 66 → **67**) und **eine stille Fehlverschmelzung** in `docs/Backlog.md` von Hand repariert (F-DA-10). Mitgenommen: die zweite Hälfte von **Nr. 189** |
> | Gegenprüfung | fünf unabhängige Blickwinkel auf den zusammengeführten Stand: **21 Befunde gemeldet, 15 bestätigt, 6 widerlegt**. Elf gehören diesem Paket und sind behoben (F-DA-11); drei sind älter als der Zweig und stehen jetzt als **Nr. 196** (erweitert) und **Nr. 199** (neu) im Backlog |

---

## 0. Was NICHT geprüft werden konnte — und warum

Steht bewusst vor allem anderen.

1. **Der Produktivstand.** Alles unten ist am Prüfstand gemessen. Ob ein
   Bestandskonto mit einem bodengebundenen Bergwacht-Rettungsmittel nach dem
   Deploy die Windenkacheln sieht, und ob das Demo-Konto dort den neuen
   Bestand zeigt, ist erst nach dem Merge zu sehen. **Und der Bestand kommt
   nicht von selbst:** Der Deploy legt nur die neue `fixture.json.gz` ab; das
   bestehende Demo-Konto zeigt den **alten** Bestand, bis jemand im
   Adminbereich „Auf Standard zurücksetzen" drückt oder der 30-Minuten-Reset
   von selbst fällig wird. Prüfliste, Punkte 1 und 2.
2. **Die Karte im Auge.** Ob die gestrichelten Luftlinien der
   Konzert-Einsätze (D21) und der Standort-Pin von Talwang so aussehen, wie
   der Auftraggeber sie meint, misst kein Werkzeug. Der Bilderlauf belegt
   „ohne Überlauf", und die Bilder sind angesehen worden (Abschnitt 3) —
   aber „sieht richtig aus" ist eine Beurteilung, keine Messung.
   Prüfliste, Punkt 4.
3. **Plausibilität der Inhalte.** Diagnosen, Zeiten, Notizen und Namen der 16
   neuen Einsätze sind erfunden; ob sie für eine Vorführung vor Fachpublikum
   taugen, entscheidet der Auftraggeber, nicht `pruefen.py`. Prüfliste,
   Punkt 6.
4. **Die OSRM-Strecken.** Die 24 neuen Bodenstrecken sind ein Abruf beim
   Demoserver. Ob die Straßenführung der Wirklichkeit entspricht, prüft
   niemand — das war auch bisher so (E-P1-03). Geprüft ist nur, dass
   Geometrie, Distanz und Fahrzeit zusammenpassen und die Spur daraus keine
   unmögliche Geschwindigkeit ergibt.
5. **Der Emulator und der Simulator laufen nicht** — dieses Paket fasst weder
   `android/` noch `watch/` an. Das ist keine übersprungene Prüfung, sondern
   ein leerer Prüfbereich.
6. **Der Stilvergleich läuft nicht** — `server/assets/style.css` und
   `server/ui.php` sind **unberührt** (`git diff --stat` über beide: keine
   Zeile). Während P3 wacht ohnehin die Vollständigkeitsprobe an seiner
   Stelle, und die ist gefahren.
7. **Nach dem Merge mit `main` sind nicht alle Prüfmittel erneut gefahren.**
   Gemessen ist, was die Merge-Auflösung berühren konnte: Wortliste,
   Vollständigkeit, Linkprobe, `php -l`, die beiden Zählungen über Backlog
   und Rahmenplan — und die **Klickprobe** als Gegenprobe am laufenden
   Programm. **Nicht** erneut gefahren sind Bilderlauf, Kreisläufe,
   Papierkorb-Mischfall und Wartungsprobe. Der Grund ist eine Messung, keine
   Annahme: `git diff HEAD -- server/` über die Merge-Auflösung zeigt
   **ausschließlich Kommentarzeilen** (`api/range.php` Nr. 191 → 198,
   `version.php` dasselbe plus ein neuer Absatz, `schema.sql` 156 → 153).
   Kein Verhalten, keine Abfrage, keine Regel hat sich geändert; die
   Prüfmittel würden denselben Stand messen. Wer das nicht glaubt, fährt
   `node tools/screenshots/aufnehmen.mjs` nach — elf Minuten.
8. **Zwei Motoren.** Der Bilderlauf ist in **Chromium** gefahren, nicht
   zusätzlich in Firefox und WebKit. Begründung: Dieses Paket ändert **keine
   Regel des Stylesheets** — es füllt den Bestand. Die Engine-Unterschiede,
   für die die Risikoliste da ist, entstehen an CSS-Merkmalen, nicht an einer
   Zeile mehr in einer Tabelle. Wer das anders sieht, fährt
   `--motor firefox --risiko --nur 10a-,10b-,12a-` nach.

## 1. Zusagen, die sich geändert haben

| Zusage | Vorher | Nachher | Freigegeben |
|---|---|---|---|
| E29 „Fähigkeiten ausschließlich an luftgebundenen Rettungsmitteln" | galt für alle Typen | gilt für Standard, Veranstaltung und Sonstiges; **Typ Bergwacht** in beiden Betriebsarten (E-DA-06, jetzt **R80**) | Auftraggeber, 15.09.2026 |
| Matrixzeile „≥ 2 Standorte, einer ohne Koordinaten" | strukturell geprüft | ersetzt durch **„≥ 1 Diensttag ohne Standort mit `spur_ausgangspunkt`"** — F-DA-1, Weg (a) | **von der Umsetzung entschieden** (E-DA-24), zu bestätigen |
| „Der Typ Sonstiges bekommt die Fähigkeiten nicht mit" (F-DA-2) | — | **bleibt bei E29** — nur der Typ Bergwacht fällt aus der Reihe | **von der Umsetzung entschieden** (E-DA-24), zu bestätigen |
| E-R64-16 „genau ein Schnitt im Referenzbestand" | `schnittzahl == 1` | `schnittzahl == len(schnitte)` und `>= 1` (E-DA-11); heute **3** | Konzept |
| Backlog-Nummern des Pakets | 190 (erledigt) und 191 (offen) | **197** und **198** — die Nummern 190–196 hat PR #47 zuerst auf `main` vergeben (E-DA-31) | Umsetzung, 15.09.2026 |
| Rahmenplan-Fassung des Pakets | 66 | **67** — 66 gehört PR #47 | Umsetzung, 15.09.2026 |
| CLAUDE.md 4 „Die Klartextliste" | 2 Rettungsmittel ohne Standort | zusätzlich **2 Diensttage ohne Standort** — an der Verschlüsselung ändert sich nichts, der Bestand deckt nur einen weiteren Weg ab | — (keine Zusage berührt) |

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

*Jede Zahl benennt, was sie gemessen hat (CLAUDE.md 6). „vorher" ist der Stand
von `main` (`98d677d`, Web 20.2.1).*

| Mittel | Was es misst | vorher | nachher |
|---|---|---|---|
| `quelldaten/pruefen.py` | Schema, Sachlogik, Abdeckungsmatrix | 16 Dienste · 87 Einsätze · 100 Ruhesegmente · 1129 Zeitstempel · 5961 Einzelprüfungen · **83 Matrixzeilen / 0 offen** · 1 Schnitt · 0 Sachfehler | **21 Dienste · 103 Einsätze · 119 Ruhesegmente · 1335 Zeitstempel · 7042 Einzelprüfungen · 97 Matrixzeilen / 0 offen · 109 Marken · 3 Schnitte · 0 Sachfehler** |
| `generator/erzeugen.py` | Payloads, Formulardaten, CSV, GPX | 526 Anfragen · 56 587 Spurpunkte · 82 GPX · 117 OSRM-Strecken | **612 Anfragen · 64 478 Spurpunkte** (Luft 25 515, Boden 22 561, Ruhe 16 402) **· 93 GPX · 4 Fußwege (2,1–3,5 km/h) · 144 OSRM-Strecken** |
| `generator/pruefen.py` | Vertragsgrenzen, Folge, Krypto, Spur, CSV | 283 989 Einzelprüfungen / 0 Befunde | **321 799 Einzelprüfungen / 0 Befunde** |
| `diff -rq` über `generator/ausgabe/` | bleiben die 16 alten Diensttage gleich? | — | **byteweise gleich** — außer `start_src` in 36 Formulardateien (E-DA-26) und der neuen `fusswege.json`. Spuren, Phasen, Nutzlasten, GPX, CSV und Sendeplan unverändert |
| `routen/routen_holen.py` | fehlende Strecken | — | **144 Teilstücke, 108 verschiedene, 24 neu geholt, 84 vorhanden**; Fußwege übersprungen (E-DA-13). `routen_soll.json`: 0 fehlend |
| Einspiellauf (`einspielen.py` + `csv_import.mjs`) | Anfragen, Fehler, Dauer | 526 Anfragen | **612 Anfragen, 0 Fehlversuche**, 21 Diensttage, 90 nachgetragen, 7 manuell, 4 CSV-Zeilen, 3 Schnitte · rund **2 Minuten** |
| `einspielen/messprotokoll.py` | Sendeverhalten der Uhr | 526 Anfragen · 182 Pakete · 56 587 Punkte · 2,5 MB | **612 Anfragen · 212 Pakete · 64 478 Punkte · 2,9 MB · 0 Fehlversuche · 0 `rejected` · 0 `kept_*`** |
| Prüfstand-DB | `vehicles.base_id IS NULL` | 2 von 6 | **4 von 10** |
| Prüfstand-DB | `days.base_id IS NULL` | 0 | **2** (D20, D21) |
| Prüfstand-DB | `kind='ground'` **mit** `winch` | 0 | **1** (Bergwachtnotarzt Sonnenau) |
| Prüfstand-DB | `client_ref LIKE 'cut-%'` | 1 | **3** |
| Prüfstand-DB | `missions.secondary = 1` | 3 | **5** |
| Prüfstand-DB | `bases` mit Koordinate | 1 von 2 | **3 von 3** |
| Prüfstand-DB | Herkunft je Einsatz | — | Uhr **39** · Handy **49** · Wear **4** · manuell **7** · Import **4** · Schnitt **3** = 106 |
| gesendet → gespeichert | Spurpunkte auf dem Weg | 56 587 → 55 861 (**−726**) | 64 478 → 63 752 (**−726**) — *dieselbe Differenz wie vorher, also keine neue Verlustquelle; sie stammt aus dem Altbestand und ist mit diesem Paket nicht gewachsen* |
| Einspiellauf vs. Fixture-Kopf | `track_points` unverändert? | — | **63 752 = 63 752** (Hintergrundjobs angehalten, `jobs.php --pause`) |
| `browser/referenz_export.mjs` | beide Referenzdateien | — | **101 Einsätze, 204 GPX, 225 Einträge, 63 752 Punkte** |
| `fixture/erzeugen.php` | Riegel und Kopf | — | läuft ohne Abbruch · Fixture **Fassung 2**, `web_version` **20.3.0**, `kdf_iter` **600 000**, Hülle **`edk1:`** · 106 / 21 / 119 / 63 752 · gepackt **858 KiB** (878 569 Bytes; roh 2,79 MB) |
| `kreislauf.py --art edbak --frisch` | Einzelvergleiche / unerklärt / erwartet / ungenutzt | 287 687 / 0 / 16 / 0 | **328 771 / 0 / 21 / 0** · Selbstproben **15 von 15** |
| `kreislauf.py --art csv --frisch` | dito | 9 120 / 0 / 1021 / 0 | **10 922 / 0 / 1271 / 0** · Selbstproben **10 von 10** |
| edbak-Umlauf, Einzelzeilen | kommt der Sonderfall zurück? | — | Rettungsmittel mit `base_ref: null` **4/4** · Diensttag ohne Standort **2/2** · Bergwacht/Boden **mit** Fähigkeiten **1/1** · Schnitte mit Sperrvermerk **3/3** |
| `tools/klickprobe/probe.mjs` | Bedienwege | 40 von 40 | **47 von 47** (4 neue Wege in `wege/da.mjs`, 5 bestehende an den neuen Bestand angepasst) |
| Bilderlauf, **Zeiger** (Chromium) | alle Seiten, 8 Breiten, 44/36 px | 46 Seiten / 368 Bilder / 0 / 0 / 0 | **49 Seiten / 392 Bilder / 0 Überlauf / 0 Konsolenfehler / 0 Knöpfe falscher Höhe** |
| Bilderlauf, **Finger** (Chromium) | die 16 berührten Seiten, 8 Breiten, 44 px | — | **16 Seiten / 128 Bilder / 0 / 0 / 0** |
| `browser/papierkorb_misch.mjs` (R27) | Papierkorb-Umlauf im gemischten Fall | — | **15 Einzelprüfungen / 0 Befunde** |
| `tools/wortliste/` | sichtbare Texte, alle fünf Bereiche | 0/0/0 | **0/0/0** — fünf Bereiche (100 PHP-Dateien, 36 Skripte, 8 normative Dokumente, 2 Android-Ressourcen, 35 Uhr-Dateien), 667 Treffer, **alle durch die 96 Ausnahmen erklärt**, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen |
| `tools/vollstaendigkeit/` | Befunde, neue `[offen]` | 340 | **340** — dieselbe Zahl wie auf `main`, im Zweitbaum gegengemessen; **0** Klassen „im Markup ohne Regel, als `[offen]` vermerkt" (Sollmenge 220, im Markup belegt 381) |
| `tools/linkprobe/` | Verweise in `server/` | 117 / 0 | **117 Verweise / 0 Abweichungen**, 0 Ausnahmen, 0 tote Zeilen |
| `tools/wartungsprobe/` | Wartungsseite (liest `demo_lib.php`) | 57 / 0 | **57 Erwartungen / 0 nicht erfüllt** (sie liest `demo_lib.php` und war deshalb mitzufahren) |
| Demo-Reset (Backlog Nr. 76) | Dauer von `demo_zuruecksetzen()`, je 3 Läufe | **5859 ms** (5845–5950) mit 88 Einsätzen | **6610 ms** (6440–6631) mit 106 Einsätzen — **+0,75 s für +20 % Einsätze** |
| Fixture-Umlauf | überlebt der Bestand einen Reset? | — | nach dem letzten Reset wieder **106 / 21 / 119 / 63 752** — dieselben Zahlen wie vorher |
| `php -l` | Syntax aller berührten PHP-Dateien | — | **0 Fehler** |
| `node --check` | Syntax aller berührten JS/MJS-Dateien | — | **0 Fehler** |
| `grep -rn "88 Eins\|87 Eins\|55 861\|16 Diensttage"` | feste Zahlen im Repositorium | — | nur noch Messprotokoll-Stellen (Konzept 1.4, Absatz 2) und wörtliche, datierte Zitate — jedes davon als solches gekennzeichnet. **Eine Fundstelle führt Konzept 1.4 nicht auf:** `server/adminbackup_lib.php` nennt „87 Einsätze" als **Beispieltext** dafür, wie eine Meldung aussieht — dieselbe Gattung wie `admin_sicherungen.php`, das dort ausdrücklich steht. Inhaltlich ist nichts falsch; die Liste ist unvollständig, nicht der Kommentar |
| Backlog gegen Rahmenplan Abschnitt 5 | dieselben offenen Nummern? | 53 = 53 (vor dem Merge) | **60 = 60** nach dem Merge, mit `diff` gegengeprüft (0 Zeilen Unterschied); **keine doppelt vergebene Nummer** in beiden Abschnitten zusammen |
| `mb_strlen()` über `GERAETE_MODELLE` | längster Sammelname (Nr. 189, zweite Hälfte) | Kommentar sagte 156 | **153 Zeichen** (154 Bytes) · 173 Modellnamen · **5** über 64 Zeichen |
| `cmarkgfm` über `docs/Backlog.md`, Eintrag für Eintrag | wie viele Einträge rendern auf GitHub als Codeblock (Nr. 196)? | 192 Einträge / 65 (Stand `origin/main`) | **195 Einträge / 68**, vier davon mit verschluckter Tabelle (123, 187, 192, 193) — die drei neuen dreistelligen Einträge 197, 198 und 199 sind selbst betroffen; Titel und Abnahme von Nr. 196 sind nachgezogen |
| `cmarkgfm` über `docs/Rahmenplan.md` | rendert Abschnitt 10 als Tabelle? hat die Fahrplanzeile 9c sieben Zellen? | — | **8 von 58** Fassungszeilen im HTML (eine Leerzeile im Eintrag zu Fassung 61 beendet die Tabelle) · Zeile 9c hat **9 Zellen** bei einem Kopf mit 7, zwei werden verworfen. **Beides älter als dieser Zweig** (auf `98d677d` nachgemessen) und deshalb **nicht** hier behoben, sondern in Nr. 196 nachgetragen |
| Gegenprüfung des zusammengeführten Stands | fünf unabhängige Blickwinkel, jeder Befund adversarisch widerlegt oder bestätigt | — | **21 gemeldet, 15 bestätigt, 6 widerlegt** · elf gingen auf dieses Paket zurück und sind behoben (F-DA-11), drei sind älter und stehen als Backlog Nr. 196 (erweitert) und Nr. 199 (neu), einer war die Dublette eines anderen |

## 3. Was im Browser geprüft wurde

*Prüfstand `https://127.0.0.1:8443`, angemeldet als `demo@gen-em.org`. Die
Bilder stammen aus dem Bilderlauf (`tools/screenshots/ausgabe/einzeln/`) und
einer gezielten Sichtprüfung; angesehen, nicht nur gezählt.*

| Seite | Handlung | Erwartet | Beobachtet |
|---|---|---|---|
| Einstellungen → Standorte | Liste öffnen | 3 Standorte, alle mit Koordinate; Karte „Ohne Standort" mit 4 Einträgen | **wie erwartet** — „Eigene Standorte **3**": Bergwachtstation Sonnenau (1 Rettungsmittel · 0 Besatzung · 3 Zielkliniken), Luftrettungsstation Hochkreuth (3 · 11 · 5), Notarztstandort Talwang (2 · 4 · 4); darunter die Karte **„Ohne Standort — 4 Rettungsmittel"**. Koordinaten stehen auf der jeweiligen Standortseite (Sonnenau: 47.532000, 10.287000); die Datenbank zählt 3 von 3 mit Koordinate |
| Standortseite Bergwachtstation Sonnenau | öffnen | Bergwachtnotarzt Sonnenau (Typ Bergwacht, Boden), Karte **Bergwacht-Bereitschaften** vorhanden, **keine** Besatzungskarte | **wie erwartet** — Kopfzeile „1 Rettungsmittel · **0 Besatzung** · 3 Zielkliniken"; das Rettungsmittel steht mit dem Bodenzeichen und „keine Rollen · Bergwacht, Winde"; die Karte **Bergwacht** führt drei Bereitschaften und den Satz „Der Abschnitt erscheint, weil an diesem Standort ein Rettungsmittel steht, das die Fähigkeit führen darf" — genau die Lücke aus F-DA-2. Die Besatzungskarte steht da, aber ohne Anlegen-Weg |
| Rettungsmittel-Dialog | Bergwachtnotarzt Sonnenau öffnen | Typ Bergwacht, Betriebsart Boden, Fähigkeiten **sichtbar** mit Winde und Bergwacht angehakt, Kleinzeile „bei diesem Typ auch bodengebunden" | **wie erwartet** — Typ „Bergwacht", Betriebsart **bodengebunden**, Kleinzeile **„bei diesem Typ auch bodengebunden"**, Winde und Bergwacht angehakt, darunter „Keine Besatzungsrollen — nur der Typ Standard hat Rollen-Vorlagen". Zum Vergleich der Boxkampf: Typ Veranstaltung, „Betriebsart * **bei Veranstaltung fest**", luftgebunden gesperrt, **kein** Fähigkeitsblock, Standort „Ohne Standort" |
| Diensttag 01.08.2026 (D19), Einsatz 1 | Einsatzansicht | Windenkacheln „Winde · 1 Cycles, 1 mit PatientIn · Luftverladung" und „Bergwacht Felsgrat" an einem **bodengebundenen** Dienst | **wie erwartet** — Bild `12a-einsatzansicht-winde-1280.png`; Kopfzeile „Bergwachtnotarzt Sonnenau · Bergwachtstation Sonnenau", Schlösser an allen geschützten Feldern |
| Diensttag 14.06.2026 abends (D20) | Tagesübersicht | **kein** Standortfeld, keine Rollen, Spur vorhanden | **wie erwartet** — Bild `10a-tagesuebersicht-ohne-standort-1280.png`; Karte „Diensttag-Daten" führt nur Rettungsmittel und Notizen; in der Leiste stehen **zwei** Dienste am 14.06. |
| Diensttag 06.07.2026 abends (D21) | Tageskarte | keine Spur an den Einsätzen, **gestrichelte Luftlinien** | **wie erwartet** — Bild `10b-tagesuebersicht-luftlinie-1280.png`; vier Einsätze ohne km-Wert („–"), zwei gestrichelte Linien auf der Karte |
| Diensttag 06.07.2026, GPS-Daten | Spurenseite | Einsätze „keine Aufzeichnung", Ruhezeiten mit Punkten | **wie erwartet** — „3 von 7 Einträgen tragen GPS-Daten · 68 Punkte insgesamt" |
| Diensttag 18.04.2026 (D18) | Tagesübersicht mit Karte | Pin am Standort Talwang, Spuren der Verlegungen, geschnittener Einsatz sichtbar | **wie erwartet** — Standort „Notarztstandort Talwang" in den Diensttag-Daten und als **Schild auf der Karte** (das war vor diesem Paket nicht möglich — Talwang hatte keine Koordinate), Besatzung „Fahrer Gerd Wallner · Sonstige Dr. Hanna Kestner", vier Einsätze mit **zwei** Sekundär-Haken, und der geschnittene Einsatz Nr. 4 (16:05) mit der Marke „geschnitten 16:05 – 17:10" und „Schnitt zurücknehmen" am Ruhesegment |
| Adminbereich → Demo-Konto | zurücksetzen | läuft durch; Bestand danach 21 / 106 | **wie erwartet** — **siebenmal** gefahren: sechs Läufe der Messung zu Nr. 76 (dreimal alte, dreimal neue Fixture, Zeile oben) und ein siebter, der den Bestand wiederherstellt (E-DA-30). Dieser siebte lief **6802 ms** und gehört **nicht** zur Messung — er liegt deshalb außerhalb ihrer Spanne 6440–6631. Danach 106 Einsätze, 21 Diensttage, 119 Ruhesegmente, 63 752 Spurpunkte |

## 4. Prüfliste — was der Auftraggeber noch tun muss

*Jeder Punkt nennt Bedienweg, erwartetes Ergebnis und woran ein Scheitern zu
erkennen ist. Die Punkte 1 und 2 des Vorlagenstands („F-DA-1/F-DA-2
entscheiden", „Zuarbeit H-DA-1") sind entfallen — beide sind in der Umsetzung
erledigt (E-DA-24, E-DA-22); ihre Entscheidungen stehen in Abschnitt 1 und
sind zu **bestätigen**.*

1. **Nach dem Merge: Demo-Konto zurücksetzen.** Verwaltung → Demo-Konto →
   „Auf Standard zurücksetzen". Erwartet: Der Vorgang läuft durch (rund 7 s),
   danach zeigt die Diensttage-Leiste **21** Tage und die Jahresübersicht
   **106** Einsätze. *Scheitern:* Der Reset bricht mit einer Meldung ab — dann
   ist die Fixture nicht mit deployt worden oder ihre Hülle passt nicht zur
   Installation; **nicht selbst beheben, melden.** Ohne diesen Schritt zeigt
   das Demo-Konto bis zum nächsten automatischen Reset (spätestens 30 Minuten
   nach der nächsten Anfrage) den alten Bestand — das ist kein Fehler.
2. **Windenkacheln am Bodendienst ansehen.** Demo-Konto → Diensttag
   **01.08.2026** (Bergwachtnotarzt Sonnenau) → Einsatz 1 → bearbeiten.
   Erwartet: Der Block „Winde" ist da und gefüllt (1 Cycle, 1 mit PatientIn,
   Luftverladung), dazu „Bergwacht Felsgrat". *Scheitern:* Die Felder fehlen —
   dann greift die Regeländerung aus AP0 am Produktivstand nicht; melden.
3. **Einstellungen → Standorte.** Erwartet: **drei** Standorte, jeder mit
   Koordinate; darunter die zugeklappte Karte **„Ohne Standort"** mit
   **vier** Rettungsmitteln (Sanitätsdienst Seefest, Reserve Talwang,
   Boxkampf Rainer Maria Rilke, Konzert von Karl Marx). *Scheitern:* Die Karte
   ist leer oder zeigt weniger — dann hat sich Backlog Nr. 174 wiederholt (der
   Einspielweg verliert den leeren Standort).
4. **Zwei Karten im Auge prüfen.** (a) Diensttag **06.07.2026** (Konzert):
   Die Tageskarte zeigt **gestrichelte** Luftlinien von den Einsatzorten zu
   den Zielen und **keine** durchgezogene Spur an den Einsätzen. (b) Diensttag
   **18.04.2026** (VEF Talwang): ein Pin am Standort Talwang und die Spuren
   der Verlegungsfahrten. *Scheitern:* keine Linien — dann fehlen Koordinaten
   im `pat_blob` oder am Standort; das ist eine Sache der **Quelldaten**,
   nicht der Karte.
5. **Diensttag ohne Standort ansehen.** Diensttag **14.06.2026, 19:30**
   (Boxkampf). Erwartet: In „Diensttag-Daten" steht **kein** Standort und
   **keine** Besatzung; Zielklinik und weitere Rettungsmittel sind Freitext.
   Am selben Kalendertag steht ein zweiter Dienst (NEF Talwang, 07:00) — beide
   nebeneinander in der Leiste. *Scheitern:* Ein Standort steht da — dann hat
   etwas ihn nachgetragen (so geschehen in F-DA-8 durch ein Prüfmittel).
6. **Inhalte lesen.** Die 16 neuen Einsätze der Tage 14.02., 18.04., 14.06.,
   06.07. und 01.08.2026 einmal durchblättern: Sind Diagnosen, Notizen und
   Namen für eine Vorführung vor Fachpublikum tauglich? *Scheitern:*
   Textänderung in den Quelldaten und Neubau des Bestands (drei Läufe, rund
   zehn Minuten) — **kein** Eingriff in den Code.
7. **Die beiden Entscheidungen aus Abschnitt 1 bestätigen** (F-DA-1 Weg (a),
   F-DA-2 „nein"). Sie sind nach der Empfehlung des Konzepts getroffen worden,
   weil der Auftrag „keine Pausen" lautete.
8. **Die Reset-Dauer zur Kenntnis nehmen** (Abschnitt 2, letzte Zeilen) — sie
   ist die Messung, auf die Backlog Nr. 76 seit dem 02.09.2026 wartet. Die
   Entscheidung („durchlaufen lassen" oder „Änderungsmarke") steht weiter aus.
9. **Backlog Nr. 198 einordnen:** Ein bodengebundener Bergwacht-Diensttag
   zeigt seine Windenfelder im Formular, wird in der **Zeitraumübersicht**
   aber nicht als Windendienst gezählt. Das zu ändern hieße zehn Kacheln in
   vier Spalten — eine Gestaltungsentscheidung mit Mockup. Gehört sie in die
   nächste Mockup-Runde?
10. **Freigabe des Abschlusses** — danach löscht die Umsetzung das Konzept
    (R62) und trägt die Erledigt-Zeile in Rahmenplan Abschnitt 8 fort. Dieses
    Prüfdokument bleibt, bis die Punkte 1 bis 9 abgehakt sind.

## 5. Grenzen der benutzten Prüfmittel

- **Die Kreisläufe belegen Reproduzierbarkeit, nicht Richtigkeit.** Sie
  vergleichen einen Export gegen die Referenz, die im **selben** Lauf
  entstanden ist. Wäre der Bestand inhaltlich falsch eingespielt worden, käme
  er genauso falsch wieder heraus, und beide Kreisläufe wären grün. Was die
  Inhalte prüft, ist `quelldaten/pruefen.py` — und das prüft die **Quelle**,
  nicht die Datenbank.
- **`pruefen.py` prüft Marken, und Marken kann die Quelle behaupten.** Von den
  97 Matrixzeilen leiten sich die neuen aus dem **Inhalt** ab (kein
  `standort` am Dienst, `spur_ausgangspunkt` vorhanden, Wegpunkt `zustieg`,
  `winch` an einem Bergwachtdienst mit `art: ground`, `route: null` bei
  vorhandenen Koordinaten, zwei Dienste an einem Kalendertag) — eine Marke aus
  dem Inhalt ist stärker als eine behauptete. Die **Marke `abenddienst`** ist
  die Ausnahme: Sie folgt aus der Uhrzeit, und die schreibt die Quelle selbst.
- **Der Bilderlauf klickt keinen Knopf.** Er fotografiert Seiten im
  Ruhezustand. Was ein Dialog tut, nachdem jemand Typ und Betriebsart gewählt
  hat, sieht er nie — dafür gibt es die Klickprobe (`da-faehigkeiten-nach-typ`,
  7 von 7 Kombinationen).
- **Die Klickprobe misst Zustände, keine Gestaltung.** „Der Knopf ist da und
  tut etwas" ist nicht „er sieht richtig aus".
- **Die Vollständigkeitsprobe zählt Klassen, nicht Wirkung.** Eine Regel, die
  es gibt, aber nichts bewirkt, zählt sie als vorhanden.
- **Der Einspiellauf ist kein Lasttest.** Er schaufelt in zwei Minuten, was im
  Betrieb über Wochen anfiele; die Wanduhr sagt über die Last nichts. Deshalb
  misst das Messprotokoll die **Soll**-Abstände der Uhr, nicht die
  tatsächlichen.
- **Die Reset-Messung ist eine Prüfstandzahl.** Datenbank und PHP liegen dort
  auf demselben Rechner. Auf dem Produktivserver ist die Datenbank ein
  eigener Rechner; die Zahl dürfte höher liegen, und gleichzeitige Zugriffe
  sind gar nicht gemessen.

## 6. Was aus dem Paket offen bleibt

- **Backlog Nr. 198** (neu): Die Zeitraumübersicht zählt Winde und Bergwacht
  weiter nur luftgebunden. Bewusst nicht mitgemacht — es wäre eine
  Gestaltungsentscheidung mit Freigabe und Mockup (F-DA-4).
- **Backlog Nr. 76** bleibt offen: gemessen ist sie jetzt, entschieden nicht.
- **Backlog Nr. 199** (neu, aus der Gegenprüfung): Die Nummer **5** fehlt im
  Backlog, obwohl der Changelog zu Web 7.2.0 zweimal sagt, sie stehe unter
  *Erledigt*. Älter als dieser Zweig, nicht hier behoben.
- **Backlog Nr. 196** ist um zwei gemessene Befunde am **Rahmenplan**
  gewachsen (Abschnitt 10 rendert 8 von 58 Zeilen als Tabelle; die
  Fahrplanzeile 9c verliert ihren Statustext an ungeschützte Pipes). Beides
  älter als dieser Zweig — und beides beantwortet die Frage, die Nr. 196
  selbst gestellt hatte.
- **F-DA-6** (Beobachtung, kein Auftrag): Die Höhe des Einsatzorts
  (`site_ele_m`) erscheint nur an einem luftgebundenen Diensttag. Ein
  Bergwacht-Einsatz am Boden auf 1200 m führt sie in der Datenbank und zeigt
  sie nicht. Gehört zusammen mit Nr. 198 in dieselbe Runde, falls es stört.
- **Sonst nichts.** Alle Arbeitspakete des Konzepts sind abgearbeitet, die
  vier Fehlerfunde des Laufs behoben.

**Was der Merge mit `main` hinterlässt und was nicht.** Aufgelöst sind die
drei Nummernkollisionen und die stille Fehlverschmelzung (F-DA-10);
mitgenommen ist die zweite Hälfte von Nr. 189. **Nicht** angefasst sind die
übrigen sechs Punkte aus PR #47 (Nr. 190 bis 196) — sie gehören dorthin und
haben mit diesem Paket nichts zu tun. Zwei davon berühren allerdings Dateien,
die dieses Paket ebenfalls anfasst, und sollten deshalb beim nächsten Griff
zusammen betrachtet werden: **Nr. 190** (die Statistikseite lässt das
virtuelle Gerät stehen — dieselbe Datei, in der hier die Zahl „106 erfundene
Einsätze" steht) und **Nr. 196** (die Einrückung des Backlogs, die das
Rendern bricht — sie ist auch der Grund, warum der Auto-Merge die Blöcke
nicht auseinanderhalten konnte).
