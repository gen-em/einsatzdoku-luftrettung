# Prüfdokument — Demo-Ausbau (Schritt 9d)

*Angelegt am 15.09.2026 (Fable) mit dem Konzept `Konzept-Demo-Ausbau.md` als
**Vorlage**; die Umsetzung füllt die Zahlen je Paket und schließt das Dokument
ab. Abschnitt 2 und 3 beantworten „ist es belegt?", Abschnitt 4 beantwortet
„was muss **ich** noch tun?". Muster: `Pruefdokument-Backlog-Runde-3.md`.
**Dieses Dokument bleibt**, bis seine Prüfliste abgehakt ist (K9); das Konzept
wird nach der Freigabe gelöscht (R62).*

> | | |
> |---|---|
> | Stand | *(von der Umsetzung)* |
> | Stufe | *(von der Umsetzung — nur AP0 stuft; keine Migration, `update.php` muss nach dem Deploy **nicht** laufen; Uhr und Android unberührt)* |
> | Bestand danach | *(Soll:* 21 Diensttage · 106 Einsätze im Bestand (103 in den Quelldaten + 3 Schnitte) · 3 Standorte · 10 Rettungsmittel, davon **4 ohne Standort** · 2 Geräte · Spurpunkte: gemessen*)* |
> | Neu entstanden | *(Referenzdateien unter `referenz/`, Fixture, ggf. neue Strecken unter `generator/routen/`)* |
> | Prüfumgebung | *(Container, PHP, MariaDB, Browser — wie Runde 3)* |
> | Ergebnis | *(von der Umsetzung)* |

---

## 0. Was NICHT geprüft werden konnte — und warum

Steht bewusst vor allem anderen. Erwartet sind mindestens diese vier; was
dazukommt, gehört hierher.

1. **Die Karte im Auge.** Ob die gestrichelten Luftlinien der Konzert-Einsätze
   (D21) und der Standort-Pin von Talwang so aussehen, wie der Auftraggeber sie
   meint, misst kein Werkzeug — der Bilderlauf belegt nur „ohne Überlauf" und
   der Stilvergleich nur Maße. Prüfliste, Punkt 4.
2. **Plausibilität der Inhalte.** Diagnosen, Zeiten, Notizen und Namen der 16
   neuen Einsätze sind erfunden; ob sie für eine Vorführung vor Fachpublikum
   taugen, entscheidet die Auftraggeberin, nicht `pruefen.py`. Prüfliste,
   Punkt 6.
3. **Die Regeländerung am Produktivstand** (Fähigkeiten bei Typ Bergwacht am
   Boden, AP0): geprüft auf dem Prüfstand; ob ein Bestandskonto mit einem
   bodengebundenen Bergwacht-Rettungsmittel danach die Windenkacheln sieht,
   ist am Produktivserver erst nach dem Merge zu sehen. Prüfliste, Punkt 2.
4. **OSRM-Strecken.** Die neuen Bodenstrecken sind ein Abruf beim Demoserver
   (H-DA-1); ob die Straßenführung der Realität entspricht, prüft niemand —
   das war auch bisher so (E-P1-03).

## 1. Zusagen, die sich geändert haben

| Zusage | Vorher | Nachher | Freigegeben |
|---|---|---|---|
| E29 „Fähigkeiten ausschließlich an luftgebundenen Rettungsmitteln" | galt für alle Typen | gilt für Standard; **Typ Bergwacht** in beiden Betriebsarten (E-DA-06) | Auftraggeber, 15.09.2026 |
| Matrixzeile „≥ 2 Standorte, einer ohne Koordinaten" | strukturell geprüft | nach F-DA-1 *(von der Umsetzung eintragen)* | *(offen)* |
| E-R64-16 „genau ein Schnitt im Referenzbestand" | `schnittzahl == 1` | `schnittzahl == len(schnitte) ≥ 1` (E-DA-11) | Konzept |

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

*Jede grüne Zahl benennt, was sie gemessen hat (CLAUDE.md 6). Soll-Werte aus
dem Konzept; Ist von der Umsetzung.*

| Mittel | Was es misst | Soll | Ist (vorher → nachher) |
|---|---|---|---|
| `quelldaten/pruefen.py` | Schema, Sache, Matrix | 21 Dienste, 103 Einsätze (±2), ≈ 94 Zeilen, **0 offen**, 3 Schnitte | 16 / 87 / 83 / 0 / 1 → |
| `generator/erzeugen.py` + `pruefen.py` | Payloads, Vertragsgrenzen, Fußweg-Geschwindigkeit | grün; alte 16 Tage **byteweise gleich** (`diff -r`) | |
| `routen/routen_soll.json` | fehlende Strecken | 0 nach H-DA-1 | |
| Einspiellauf | Anfragen, Dauer | grün; Dauer | 526 Anfragen, ~4 min → |
| Prüfstand-DB | `vehicles.base_id IS NULL` | **4** von 10 | 2 von 6 → |
| Prüfstand-DB | `days.base_id IS NULL` | **2** | 0 → |
| Prüfstand-DB | Rettungsmittel `kind='ground'` mit `winch` | **1** | 0 → |
| Prüfstand-DB | `client_ref LIKE 'cut-%'` | **3** | 1 → |
| Prüfstand-DB | `missions.secondary = 1` | **5** | 3 → |
| Prüfstand-DB | `track_points` beim Einspielen vs. Fixture-Kopf | **gleich** (Jobs angehalten) | |
| `kreislauf.py --art edbak --frisch` | Einzelvergleiche / unerklärt / erwartet / ungenutzt | ≈ 350 000 / **0** / n / **0** | 287 687 / 0 / 16 / 0 → |
| `kreislauf.py --art csv --frisch` | dito | ≈ 11 000 / **0** / n / **0** | 9 120 / 0 / 1 021 / 0 → |
| edbak-Umlauf, Einzelzeilen | `base_ref: null` am Rettungsmittel; Tag ohne Standort; Bergwacht/Boden mit Fähigkeiten; Sperrvermerke | 4/4 · 2/2 · 1/1 · 3/3 | |
| `fixture/erzeugen.php` | Abbruchriegel (KDF), Kopf | läuft; 600 000 | |
| Demo-Reset | Dauer eines Resets (Nr. 76) | gemessen | |
| `tools/klickprobe/probe.mjs` | Wege | n von n | 40 von 40 → |
| Bilderlauf (`--motor chromium|firefox|webkit`) | berührte Seiten, beide Bedienhöhen | ohne Überlauf | |
| Papierkorb-Mischfall (R27) | Papierkorb-Umlauf | grün | |
| `tools/wortliste/` | sichtbare Texte | **0/0/0** | |
| `tools/vollstaendigkeit/` | Befunde, `[offen]` | keine neuen `[offen]` | 330-… → |
| `tools/linkprobe/` | Links | 0 rot | |
| `tools/wartungsprobe/` | Wartungsseite | unverändert grün | |
| `grep -rn "88 Eins" tools docs server` | feste Zahlen | nur noch Messprotokoll-Stellen (Konzept 1.4, Absatz 2) | |

## 3. Was im Browser geprüft wurde

*(von der Umsetzung: Prüfstand, angemeldet als Demo-Konto — je Zeile Seite,
Handlung, Beobachtung)*

| Seite | Handlung | Erwartet | Beobachtet |
|---|---|---|---|
| Einstellungen → Rettungsmittel | Bergwachtnotarzt Sonnenau öffnen | Typ Bergwacht, Boden, Winde **und** Bergwacht angehakt, Standort Bergwachtstation Sonnenau | |
| Einstellungen → Rettungsmittel | Boxkampf öffnen | Veranstaltung, Boden ausgegraut, keine Fähigkeiten, „Ohne Standort" | |
| Einstellungen → Standorte | Liste | 3 Standorte, alle mit Koordinaten (nach F-DA-1) | |
| Diensttag 01.08.2026 (D19) | Einsatz 1 öffnen | Windenkacheln mit 1 / 1 / Luftverladung, Bergwacht Felsgrat, Notiz nennt den Polizeihubschrauber | |
| Diensttag 14.06.2026 abends (D20) | Tagesübersicht | kein Standort, keine Rollen, Transportziel als Freitext | |
| Karte, 06.07.2026 abends (D21) | Karte öffnen | keine Spur an den Einsätzen, gestrichelte Luftlinien Ort → Ziel | |
| Karte, 18.04.2026 (D18) | Karte öffnen | Pin am Standort Talwang, Spur der Verlegungen, geschnittener Einsatz sichtbar | |
| Adminbereich → Demo-Konto | zurücksetzen | läuft durch, Dauer, Bestand danach 21 / 106 | |

## 4. Prüfliste — was der Auftraggeber noch tun muss

*Jeder Punkt nennt Bedienweg, erwartetes Ergebnis und was ein Fehlschlag
bedeutet.*

1. **F-DA-1 und F-DA-2 entscheiden** — vor AP1 (K6). Ohne Entscheidung steht
   die Umsetzung bei Schritt 3 von AP1.
2. **Zuarbeit H-DA-1:** nach AP2 die fehlenden Strecken holen
   (`routen_holen.py` lokal) oder den Host freigeben. Ohne Strecken kein AP3.
3. **Nach dem Merge:** Demo-Konto am Produktivstand anmelden → Diensttag
   01.08.2026 → Einsatz „Kletterunfall" → Windenkacheln sichtbar und Werte
   gesetzt. *Fehlschlag:* die Regeländerung (AP0) greift am Produktivstand
   nicht oder die Fixture wurde nicht mit eingespielt — nicht selbst beheben,
   melden.
4. **Karte ansehen:** Diensttag 06.07.2026 (Konzert) → Karte → gestrichelte
   Luftlinien von den Einsatzorten zu den Kliniken; Diensttag 18.04.2026
   (VEF) → Pin am Standort Talwang. *Fehlschlag:* Koordinaten fehlen im
   `pat_blob` oder am Standort — Quelldaten prüfen, nicht die Karte.
5. **Einstellungen → Rettungsmittel:** zehn Einträge, vier unter „Ohne
   Standort" (Seefest, Reserve, Boxkampf, Konzert). *Fehlschlag:* Nr. 174 hat
   sich wiederholt (Einspielweg verliert den leeren Standort).
6. **Inhalte lesen:** die 16 neuen Einsätze einmal durchblättern — Diagnosen,
   Notizen, Namen tauglich für eine Vorführung? *Fehlschlag:* Textänderung in
   den Quelldaten, Neubau des Bestands (drei Läufe), kein Codeeingriff.
7. **Demo-Reset-Dauer** (Abschnitt 2) zur Kenntnis nehmen — sie geht in die
   Entscheidung zu Nr. 76 ein.
8. **Freigabe des Abschlusses** — danach trägt die Umsetzung die
   Erledigt-Zeile in Rahmenplan Abschnitt 8 ein und löscht das Konzept (R62).

## 5. Grenzen der benutzten Prüfmittel

*(von der Umsetzung — mindestens: die Kreisläufe vergleichen gegen die
Referenz, die im selben Lauf entstand; sie belegen Reproduzierbarkeit, nicht
Richtigkeit. `pruefen.py` prüft Marken, die die Quelle selbst setzt — eine
Marke, die aus dem Inhalt kommt, ist stärker als eine behauptete; welche der
neuen Zeilen aus dem Inhalt kommen, steht hier.)*

## 6. Was aus dem Paket offen bleibt

*(von der Umsetzung — Soll: nichts außer Nr. 76.)*
