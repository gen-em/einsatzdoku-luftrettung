# Prüfdokument — Backlog-Runde 3

*Angelegt am 13.09.2026 (Fable) mit dem Konzept `Konzept-Backlog-Runde-3.md`,
**von der Umsetzung am 13.09.2026 mit Zahlen gefüllt und abgeschlossen** — es
steht keine Klammer mehr offen. Das Prüfprotokoll in Abschnitt 2 und 3
beantwortet „ist es belegt?"; die Prüfliste in Abschnitt 4 beantwortet „was
muss **ich** noch tun?". Muster: `Pruefdokument-Backlog-Runde-2.md`.
**Dieses Dokument bleibt**, bis seine Prüfliste abgehakt ist (K9); das Konzept
wird nach der Freigabe gelöscht.*

> | | |
> |---|---|
> | Stand | **Vollständig — AP1 bis AP10 erledigt** (13.09.2026), alle neun Backlog-Punkte. Offen ist der **Merge auf `main`** nach Freigabe und danach die Prüfliste in Abschnitt 4 |
> | Stufe | **Web 19.3.1 — Korrektur** (E-BR3-14; die Begründung steht im Konzept). **Keine Migration**, `update.php` muss nach dem Deploy **nicht** laufen. Uhr und Android unberührt. `version.php` steigt erst mit AP4 — dem ersten Paket, das `server/` anfasst |
> | Punkte | Backlog **Nr. 91, 94, 117, 47, 58, 173, 174** erledigt; **Nr. 67** Unterpunkt erledigt, Punkt bleibt (P5); **Nr. 41** drei Streichungen erledigt, Punkt bleibt (9c) |
> | Neu entstanden | Prüfgruppe „5 Zusagen" in `tools/vollstaendigkeit/`, Liste `zusagen.md`; neue Referenzdateien unter `tools/referenzdatensatz/referenz/` |
> | Prüfumgebung | Wegwerf-Container (Linux 6.18, x86_64): **PHP 8.4.19** (eingebauter Server, `opcache.enable_cli` Off), **MariaDB 10.11.14** (mit AP4 nachinstalliert — der Container bringt keinen Datenbankserver mit), TLS über `socat` auf 8443, **Chromium** über Playwright. Volle Installation mit Demo-Konto: 88 Einsätze, 16 Diensttage, 2 Geräte. **Kein ImageMagick, kein `rsvg-convert`** (AP2), **kein Connect-IQ-Simulator** (AP1) |
> | Ergebnis | **Maschinell grün, mit zwei Funden und einer nicht reproduzierbaren Zahl.** Wortliste **0/0**, Vollständigkeit **330** Befunde (`[offen]` 2, Gruppe 5: 0/2/0 und 0/7/0), Linkprobe **117/0**, Wartungsprobe **55/0**, Spurprobe **45/0**, Kontraste **22/0**, Klickprobe **40/40**, Kreisläufe **287 687/0** und **9 120/0** gegen die neue Referenz, Bilderlauf **360 Bilder, 0/0/0**, `php -l` **100/0**, Selbstprüfzahl **54 = 54**. **Zwei echte Funde:** `apk.php` lieferte seine 404-Seite ohne Seitenhülle aus (behoben, H-BR3-1) und der Rauschfilter des Bilderlaufs verschluckt lokale Fehler (**nicht** behoben — Nr. 176, K4). **Eine Zahl bleibt offen:** Ein früherer Bilderlauf meldete 15 Konsolenfehler, der Abschlusslauf 0; der alte Bericht existiert nicht mehr (Abschnitt 2) |

---

## 0. Was NICHT geprüft werden konnte — und warum

Steht bewusst vor allem anderen. Erwartet sind mindestens diese drei; was
dazukommt, gehört hierher.

- **Nr. 91 und 94 am Simulator.** Beide Punkte sind Dokumentation eines
  gemessenen Verhaltens (S5/C, V-S5-05). Die Runde misst es **nicht** noch
  einmal — kein Connect-IQ-Simulator im Prüfstand. Die Aussagen stammen aus
  dem Backlog-Eintrag und dem Messprotokoll von damals; wer sie anzweifelt,
  braucht den Simulator. **Bestätigt für Nr. 91 (AP1, 13.09.2026):** Es gibt
  keinen Connect-IQ-Simulator in diesem Container, und es wurde keiner
  beschafft. Die vier Aussagen des neuen LIESMICH-Abschnitts sind
  **abgeschrieben, nicht gemessen** — Quelle ist der Backlog-Eintrag Nr. 91
  und der Simulator-Rundlauf aus S5 Paket C
  (`Pruefdokument-S5-Kopplung-umgekehrt.md`). **Eine fünfte Aussage ist
  dagegen am Code belegt, nicht abgeschrieben:** dass BACK kein Ersatz für
  „Nein" ist, folgt daraus, dass `KoppelnDelegate` in
  `watch/source/PairView.mc` nur `onResponse` trägt — das ist gelesen und
  nachprüfbar, ohne Simulator. Prüfliste Punkt 4 holt die Gegenprobe beim
  Auftraggeber ein.
- **Der Tausch in `kdf_upgrade.php` am Produktivserver.** Geprüft auf dem
  Prüfstand mit dem Demo-Konto und einem Aufruf ohne Header (alter **und**
  neuer Stand gemessen). Ob das Produktiv-Demo-Konto nach dem Deploy
  unverändert hereinkommt, sagt erst Punkt 1 der Prüfliste.
- **Drei Befunde der Sichtprüfung sind NICHT von dieser Runde** und stehen
  hier, damit sie niemandem als Regression erscheinen: „Keine Phasenzeilen am
  Einsatz", „Keine Diagnose-Beschriftung auf der Einsatzseite", „Diagnose-
  Beschriftung ohne Wert — nicht entschlüsselt". Sie treten am **alten Stand
  genauso** auf (gemessen, siehe Abschnitt 2) und haben einen anderen Grund:
  `sichtpruefung.mjs` prüft den **Referenzzustand** (Abnahme B3), auf dem
  Prüfstand steht aber die **Demo-Fixture** — `einspielen.py` ist in dieser
  Runde bis AP8 nicht gelaufen. Das Werkzeug beendet sich deshalb hier immer
  mit 1; sein Rückgabewert ist für AP4 kein Signal, die Gleichheit der
  Messwerte ist es.
- **Die neue Referenz gegen die Demo-Installation.** Die Kreisläufe liefen
  gegen den Prüfstand. Der Vergleich gegen `nadoku.gen-em.org` misst
  Besucheränderungen mit, wenn das Demo-Konto nicht vorher zurückgesetzt
  wurde (LIESMICH, „Regressionslauf"). **Nicht gelaufen (13.09.2026):** Er
  braucht den Produktivstand und einen Demo-Reset unmittelbar davor; beides
  liegt außerhalb des Wegwerf-Containers. Die neue Referenz ist damit
  **gegen den Prüfstand belegt und gegen den Produktivstand nicht** — wer
  sie dort zum ersten Mal fährt, rechnet mit Abweichungen, die von Besuchern
  stammen, nicht von dieser Runde.
- **~~`tools/uhr-bilder/erzeugen.sh` ist nicht gelaufen~~ — erledigt am
  13.09.2026.** Stand so hier, weil der Container kein ImageMagick mitbrachte.
  Mit dem Startvorgang aus AP4 (`.claude/hooks/session-start.sh`) ist es
  nachinstalliert, das Skript **gelaufen** und die Zusage **gemessen** statt
  nur umformuliert — die Zahlen stehen in Abschnitt 2. Der Eintrag bleibt
  sichtbar, damit nachvollziehbar ist, dass die Lücke bestand und wodurch sie
  geschlossen wurde.
- **Gescheiterte Abrufe auf dem Prüfstand — aufgeklärt, aber nicht durch
  ein Prüfmittel.** Im Abschlusslauf scheiterten Abrufe auf dem **eigenen**
  Server, nicht nur Kartenkacheln; das sah nach einem Fehler aus. Nachgemessen
  mit einem eigens gebauten Rundlauf (drei Ladungen derselben Seite,
  Playwright, Kacheln abgewiesen): **14 gescheiterte Abrufe, alle
  `net::ERR_ABORTED`, alle in der ERSTEN Ladung nach der Anmeldung, 0 in den
  beiden folgenden, und 0 Konsolenfehler daraus.** Einzeln liefert jede der
  Dateien **HTTP 200** (vorher gemessen), und 20 gleichzeitige Anfragen je
  Port kamen **20 von 20 mit 200** zurück. `ERR_ABORTED` heißt hier: Die
  nächste Navigation räumt das noch ladende Dokument ab — Messartefakt des
  Rundlaufs, **kein Serverfehler**. Die Zahl der Konsolenfehler des
  Bilderlaufs ist davon unberührt, weil ein abgeräumter Teilabruf gar keine
  Konsolenmeldung erzeugt (0 von 14).
- **Dabei ist ein echter Fund abgefallen, der NICHT behoben ist:** Der
  Rauschfilter des Bilderlaufs (`istRauschen()` in
  `tools/screenshots/aufnehmen.mjs`) prüft drei Fehlercodes gegen den
  **Meldungstext**, ohne die Fundstelle anzusehen — ein lokaler Abruf, der mit
  `ERR_CONNECTION_RESET`, `ERR_CONNECTION_CLOSED` oder `ERR_ABORTED`
  scheitert, wird als Kartenrauschen weggeworfen. Am Muster nachgerechnet:
  **3 von 5** gebauten Fällen mit lokaler Fundstelle werden verschluckt, zwei
  gezählt (`ERR_CONNECTION_REFUSED`, HTTP 500). **Das ist Backlog Nr. 176**
  und nach K4 nicht in dieser Runde behoben. Es heißt auch: Die Zeile
  „0 Konsolenfehler" unten deckt diese drei Fehlerarten **nicht** ab.

---

## 1. Zusagen, die sich geändert haben

- **`tools/uhr-bilder/`:** „bitgleich" → „pixelgleich" an zwei Stellen. Die
  schwächere Zusage ist die, die `compare -metric AE` tatsächlich belegt.
- **Handbuch 11.2:** sagt jetzt, dass die vier Kennzahlen nur Konto-Backups
  der Verwaltung messen und die Anwendung nicht weiß, ob eine NutzerIn je ein
  Backup gezogen hat.
- **Zwei neue Zusagen mit Prüfmittel:** kein natives `confirm()`/`alert()`/
  `prompt()` außerhalb der zwei Rückfälle; jede Seite mit `ui_seite_start()`
  hat ihr Gerüst, sieben Ausnahmen mit Grund. Beide sind Regeln, die vorher
  nur im Kopf standen.
- **Keine weitere Zusage berührt.** Die Runde hat keine Zusage aus
  `CLAUDE.md` 4 angefasst: kein Feld ist in den verschlüsselten Block
  gewandert oder daraus heraus, keine fremde Laufzeitquelle ist dazugekommen,
  kein Schreibweg geht an `validate_lib.php` vorbei, kein Verbraucher an
  `spur_lib.php`, und weder die Uhr noch der Data Layer sind angefasst
  (`watch/` und `android/` sind in dieser Runde unverändert — gemessen:
  `git diff --stat origin/main -- watch android` ist leer).

---

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

| Mittel | Vorher (Runde 2, 12.09.2026) | Soll | Ergebnis |
|---|---|---|---|
| **Vollständigkeit** (`tools/vollstaendigkeit/pruefen.py`) | 334 Befunde; `[offen]` 5; „ohne Gegenstück" 53; „auf der Streichliste" 125 | `[offen]` **2**; „auf der Streichliste, aber noch im Markup" **0**; „ohne-regel.md: Eintrag ungenutzt" **0**; Gesamtzahl **330** — nicht 331 wie im Konzept: `rea-kopf` war zusätzlich ein Befund „ohne Gegenstück" | `[offen]` **2** (`imp-warn`, `imp-daygroup`) ✓ · noch im Markup **0** ✓ · ungenutzt **0** ✓ · „ohne Gegenstück" **52** ✓ · Streichliste **126** ✓ · **BEFUNDE 330** ✓ |
| **Gruppe 5 „Zusagen", native Dialoge** | — (neu) | **0** Befunde, **2** Ausnahmen (`confirm.js`, `forms.js`), **0** Ausnahmen ungenutzt | **0 / 2 / 0** ✓, Gesamtzahl unverändert **330** |
| **AP6 · was der Kommentar-Abtaster ausmacht** | grober `grep`: **5** Treffer | nach dem Abtaster **2** — die drei übrigen sind Kommentare | **5 → 2** ✓ (`unlock.js`, `confirm.js`-Kopf, `version.php` sind Kommentare) |
| **AP6 · Probe: `window.confirm("x")` in einer PHP-Datei** | 0 Befunde | **1** Befund mit `Datei:Zeile`; Gesamtzahl steigt | **1** (`server/einsatz.php:998`), Gesamt **330 → 331** ✓, danach entfernt |
| **AP6 · Probe: dieselben Aufrufe als Kommentar** (`/* */`, `//`, `#`) | — | **0** Befunde — der Abtaster trägt für alle drei Formen | **0** ✓, danach entfernt |
| **AP6 · Probe: Ausnahme zeigt ins Leere** | 0 ungenutzt | **1** Befund **und** **1** „Ausnahme ungenutzt" | **1 / 1** ✓, danach zurückgenommen |
| **Gruppe 5 „Zusagen", Seite ohne Gerüst** | — (neu) | **0** Befunde, **7** Ausnahmen, Hinweis „Gerüst ohne Seitenhülle" **1** (`apk.php`) | **0 / 7 / 0** ✓ · Hinweis **0**, nicht 1: `apk.php` war ein **Fehler** und ist behoben (H-BR3-1) |
| **AP7 · die sieben Ausnahmen einzeln geprüft** | — | jede ist wirklich außerhalb der Sitzung, sonst H-BR3-1 | **7/7 bestätigt** ✓ — `pw_handling.php` sagt im Dateikopf „keine Sitzung", `session_lib.php` rendert **nach** `session_destroy()` |
| **AP7 · `tag_spuren.php`** (der Anlass des Punktes) | ohne Gerüst (behoben in S9) | **kein** Befund und **keine** Ausnahme | 6 Aufrufe, 0 auf der Liste, 0 als Befund ✓ |
| **AP7 · Probe: neue Seite ohne Gerüst** | — | wird mit `Datei:Zeile` gemeldet | **1 Befund** (`server/probe_ap7.php:4`) ✓, danach entfernt |
| **AP7 · `apk.php` 404 vorher/nachher** (Browser) | Doctype **nein**, Stylesheet **nein**, `compatMode` **BackCompat**, Times New Roman | Doctype ja, Stylesheet ja, `CSS1Compat`, Open Sans | **alles ✓** — Titel „Datei nicht gefunden — Gen-EM NAdoku", Antwort 4187 → 4688 Zeichen |
| **Probe, dass beide rot werden** | — | ~~je ein eingeschleuster Verstoß → Rückgabewert ≠ 0~~ — **Sollmaß berichtigt (AP6):** `pruefen.py` endet bei 330 Befunden ohnehin mit einem Wert ≠ 0, der Rückgabewert beweist also nichts. Gemessen wird stattdessen die **Zahl der Befunde der jeweiligen Gruppe** | **beide rot, je 1 Befund mit `Datei:Zeile`** ✓ — native Dialoge: `server/einsatz.php:998` (Gesamt 330 → 331), Seite ohne Gerüst: `server/probe_ap7.php:4`; beide Einschleusungen danach entfernt, Gesamt wieder **330** (die beiden Einzelzeilen darüber tragen die Zahlen) |
| **Kreislauf edbak** (`vergleich/kreislauf.py --art edbak --frisch`) | 287 687 Einzelvergleiche, 0 unerklärt, 16 erwartet, **3 ungenutzte Regeln** | **0 unerklärt, 0 ungenutzt**, gegen die **neue** Referenz; ein Rettungsmittel mit `base_ref: null` kommt unverändert zurück | **287 687 / 0 unerklärt / 16 erwartet / 0 ungenutzt** gegen die **neue** Referenz ✓ · `base_ref: null` unverändert (2 vorher, 2 nachher) ✓ |
| **Kreislauf csv** (`--art csv --frisch`) | 9 120 Einzelvergleiche, 0 unerklärt, 1 021 erwartet, **2 ungenutzte Regeln** | **0 unerklärt, 0 ungenutzt**, neue Referenz | **9 120 / 0 unerklärt / 1 021 erwartet / 0 ungenutzt** gegen die **neue** Referenz ✓ |
| **Rettungsmittel ohne Standort** in der eingespielten Installation | 0 von 6 (Referenz vom 12.09.) | **2 von 6** vor dem Export und nach dem Umlauf | **2 von 6** ✓ (Sanitätsdienst Seefest, Reserve Talwang) · nach dem Umlauf: edbak-Konto **2**, csv-Konto **2** ✓ |
| **AP9 · die drei Läufe der Kette** | — | jede Stufe ohne Befund | Quelldaten **5 961** Einzelprüfungen / **0** Befunde · Generator **283 989** / **0** · Ingest **526** Anfragen / **0** Fehler · 16 Diensttage zugeordnet · 79 nachgetragen · 2 von Hand · Sperrliste **bestanden** · 4 CSV-Einsätze ✓ |
| **AP9 · Export** | — | beide Referenzdateien neu | edbak **188 Einträge** (85 geschützt), 16 Diensttage, 182 Aufzeichnungen, **55 861** Punkte · CSV **83 Einsätze**, **172** GPX ✓ |
| **Klickprobe** (`tools/klickprobe/probe.mjs`) | 39 von 40 bei der Aufnahme von Nr. 174 | **40 von 40** | **40 von 40, 0 verfehlt** ✓ |
| **Wortliste** (`tools/wortliste/`) | 0 Treffer außerhalb der Ausnahmen, 0 ungenutzt | **0 / 0** | **0 / 0** nach AP1 und AP2 ✓ |
| **AP2 · „bitgleich" in `tools/uhr-bilder/`** | Zusage an 2 Stellen behauptet | **0 Vorkommen, die die Bitgleichheit behaupten** — das Sollmaß „`grep -ci` = 0" ist berichtigt, weil der verlangte Zusatz das Wort verneinen muss (E-BR3-15) | **3 Vorkommen, 0 Behauptungen** ✓ (2 Verneinung, 1 datierte Rückschau) |
| **AP2 · `tools/s5-anker/anker.py`** | Anker `uhrbilder.bitgleich` „unveraendert"; 7 nicht gefunden | Anker findet die Stelle weiter; **nicht gefunden bleibt 7** | Anker `uhrbilder.wortlaut` **„unveraendert"**, nicht gefunden **7** ✓ |
| **AP2 · Syntax der berührten Dateien** | — | `bash -n` und Python-Parser ohne Fehler | **beide ok** ✓ |
| **AP2 · `erzeugen.sh` gelaufen, Zusage gemessen** (nachgetragen 13.09.2026) | die Zahl stammte vom 02.09.2026 | 0 Dateien mit abweichenden Bildpunkten; bytegleich darf **keine** sein | **17 PNG erzeugt · 0 bytegleich · 0 mit abweichenden Bildpunkten** ✓ — „bitgleich" wäre nachweisbar falsch, „pixelgleich" nachweisbar richtig |
| **AP2 · Gegenprobe, dass `compare` misst** | — | weiß gegen schwarz ≠ 0 | 64 × 64 px → **4096** Bildpunkte ✓ |
| **AP3 · „letztes Backup der NutzerIn" als Messwert im Handbuch** | — | **0** Fundstellen (grep auf „zuletzt … Backup", „letztes Backup") | **0** ✓ |
| **AP3 · Gegenprobe am Code** | — | `users` ohne Backup-Spalte; `edbak_konto_stand()` liest nur den Kontoordner; Erinnerungsmail auf denselben Ständen | **16 Spalten, keine davon ein Backup-Zeitpunkt**; `edbak_pakete()` + Begleitdatei; `edbak_faellige_konten()` filtert auf `ueberfaellig`/`nie` ✓ |
| **Backlog-Nummernmenge gegen `dabd7a3`** (neu ab AP3, wegen F-BR3-01) | 170 Einträge | **0 verloren, 0 doppelt**; nur beabsichtigte Verschiebungen | nach AP3 **171 Einträge, 0 verloren, 0 doppelt**; aus *Offen* heraus: 81, 87, 88 (Durchsicht) und 91, 94, 117 (diese Runde) ✓ |
| **Linkprobe** (`tools/linkprobe/probe.py`) | 0 Abweichungen, 0 tote Ausnahmen | **0 / 0** | **117 Verweise, 0 Abweichungen, 0 tote Zeilen** ✓ |
| **Wartungsprobe** (`tools/wartungsprobe/probe.php`) | 55 Erwartungen, 0 nicht erfüllt (15 flattert, Nr. 172) | **0** nicht erfüllt | **55 Erwartungen, 0 nicht erfüllt** ✓ — Erwartung 15 hat in diesem Lauf nicht geflattert |
| **Bilderlauf** Einsatzbearbeitung mit Reanimation und Einsatzansicht mit Phasen | — | **0** abweichende Bildpunkte gegen den Stand vor AP5 (`compare -metric AE`) — die drei Klassen hatten keine Regel | **0 auf allen vier Seiten** ✓ (1280 × bis 1943 px, ganze Seite). **Unmaskiert 3079 auf jeder** — das ist der Demo-Zähler, nicht die Änderung: Rahmen **aller** abweichenden Punkte y 117–126, x 808–1204, Text „in ca. 10 Minuten" gegen „in ca. 8 Minuten". Gegenprobe, dass der Vergleich misst: zwei verschiedene Seiten **525 585** Bildpunkte |
| **AP5 · Klassen im gerenderten DOM** (vier Seiten, vor/nach) | `rea-kopf` 2, `rea-beginn` 2, `phasen-name` 9 und 22 | alle **0**; `rea-sitzung` und `phasen-eingabe` **unverändert** | **0 / 0 / 0 / 0** ✓ · `rea-sitzung` 2 → 2, `phasen-eingabe` 22 → 22 und 9 → 9 ✓ |
| **AP5 · Konsolenfehler** auf den vier Seiten | — | 0 | **0** ✓ (Kartenkacheln ausgenommen — kein Netzzugang dorthin) |
| **`kdf_upgrade` ohne Header** (Demo-Sitzung über den regulären Anmeldeweg, ohne `X-CSRF`) | **200** `{"ok":true,"uebersprungen":"demo"}` — am alten Stand selbst gemessen, nicht angenommen | **403** `{"error":"csrf"}`; mit Header weiterhin 200 | **403** `{"error":"csrf"}` ✓ · mit Header **200** `uebersprungen: demo` ✓ |
| **AP4 · falscher und leerer Header** | — | beide **403** | **403 / 403** ✓ |
| **AP4 · Gegenprobe an einem zweiten Endpunkt** (`api/day.php` ohne Header) | — | **403** — zeigt, dass die Probe den Header wirklich wegließ | **403** ✓ |
| **AP4 · Vormessung vor dem Eingriff** | — | `unlock.js` schickt `X-CSRF`; sonst **anhalten** | `unlock.js:200` `'X-CSRF': CSRF`, einziger Aufrufer (3 weitere Fundstellen sind Kommentare) ✓ — **kein Haltepunkt** |
| **AP4 · Browser** (Chromium, `sichtpruefung.mjs`, Demo-Anmeldung über die Anmeldeseite) | 0 Konsolenfehler | Anmeldung läuft durch, **0 Konsolenfehler**, Messwerte **identisch** zum alten Stand | Anmeldung durch (Titel „SonntagSo, 27.12.2026"), **0 Konsolenfehler**, alle Messwerte identisch (Einsatzzeilen 6, Spuren 16/4, Kacheln 6, `geschuetzt_lesbar` false **vorher und nachher**) ✓ |
| **AP4 · `php -l`** auf `kdf_upgrade.php` und `version.php` | — | 0 Fehler | **0 Fehler** ✓ |
| **AP1 · `grep -c Confirmation tools/uhr-pruefstand/LIESMICH.md`** | **0** — die Lehre aus S5/C stand nirgends | ≥ 1 | **3** ✓ |
| **AP1 · die vier Aussagen einzeln** (Bild blind · `Return` bestätigt · BACK ohne `onResponse` · Wirkung statt Bild) | — | je **1** Fundstelle | **1 / 1 / 1 / 1** ✓ |
| **Selbstprüfzahl** (Rahmenplan Abschnitt 5 ↔ offene Backlog-Punkte, Einzeiler aus Fassung 46) | 59 = 59 | gleich nach **jedem** Paket, nicht erst in AP10 (E-BR3-13) | nach AP1 **58 = 58** ✓ |
| **AP10 · Bilderlauf über alles** (`aufnehmen.mjs --klein`, Zeigergerät) | 12.09.2026: 96 Bilder über sechs Seiten | **0** Überlauf, **0** Konsolenfehler, **0** Knöpfe falscher Höhe | **45 Seiten × 8 Breiten = 360 Einzelbilder, 45 Kontaktbögen · Überlauf 0 · Konsolenfehler 0 · Knopfhöhen 0** ✓ |
| **AP10 · Gegenprobe, dass die 360 Bilder nicht dasselbe zeigen** (Rezept aus der LIESMICH) | — | Dateizahl = Zahl verschiedener Bilder | **360 Dateien, 356 verschiedene** — die vier Doppel sind **erklärt und erwartet**: `11-tagesuebersicht-schublade` bei 1024, 1280, 1440 und 1920 px ist Bild für Bild `10-tagesuebersicht`, weil `.nur-schublade{display:none}` in `@media (min-width:1024px)` steht (`style.css:1887/1921`) — ab 1024 px gibt es keine Schublade. Unter 1024 px (360/390/420/768) unterscheiden sich beide ✓ |
| **AP10 · der frühere Lauf meldete 15 Konsolenfehler** | — | aufklären oder als Grenze benennen | **nicht reproduzierbar.** Der Abschlusslauf auf demselben Stand meldet **0** über 45 Seiten. Der Bericht des früheren Laufs existiert nicht mehr — `aufnehmen.mjs` löscht `ausgabe/` bei jedem Start (`rmSync`), die Wortlaute sind damit weg. Was aufgeklärt ist, steht in Abschnitt 0: die gescheiterten **Abrufe** waren `ERR_ABORTED` aus der Navigation und erzeugen **gar keine** Konsolenmeldung (0 von 14) |
| **AP10 · Spurprobe** (`php tools/spurprobe/probe.php`) | 45 Erwartungen, 0 nicht erfüllt | **0** nicht erfüllt | **45 Erwartungen, 0 nicht erfüllt** ✓ |
| **AP10 · Kontraste** (`python3 tools/screenshots/kontrast.py`) | 22 Paare, 0 verfehlt | **0** verfehlt | **22 Paare, 0 verfehlt** ✓ |
| **AP10 · `php -l` über `server/`** | — | 0 Fehler | **100 Dateien, 0 Fehler** ✓ |
| **AP10 · Selbstprüfzahl am Ende** | nach AP1 58 = 58 | gleich — und die zwei neuen Nummern 176/177 sind in **beiden** Listen | **54 = 54** ✓ (52 = 52 vor dem Anlegen von 176 und 177) |
| **AP10 · Backlog-Nummernmenge gegen `dabd7a3`** | 170 Einträge | 0 verloren, 0 doppelt | **171 → 173 Einträge, 0 verloren, 0 doppelt**; neu 175 (Runde 2/Durchsicht, hier eingetragen), **176** und **177** (Funde dieses Pakets) ✓ |
| **AP10 · `kdf_upgrade.php` aus dem Browser** (Demo-Anmeldung, alle `api/`-Antworten mitgeschrieben) | — | der Endpunkt wird aufgerufen und antwortet 200 | **er wird NICHT aufgerufen — und kann es auf diesem Stand nicht**: `unlock.js:184` ruft nur, wenn `KDF_ITER_ZIEL !== kdf_iter` **und** ein Token zur gespeicherten Rundenzahl vorliegt; `KDF_ITER_LISTE` hat seit dem 12.09.2026 (Nr. 155) **einen** Eintrag, und alle **4** Konten der Installation stehen auf **600 000** (SQL nachgezählt). Gemessen wurden stattdessen zwei Aufrufe: `api/day.php` **200**, `api/pat_anheben.php` **200**; Anmeldung landet auf `index.php`, Titel „Tagesübersicht — Gen-EM NAdoku" ✓ |
| **AP10 · `watch/` und `android/` unberührt** | — | leerer Diff | `git diff --stat origin/main -- watch android` **leer** ✓ |

---

## 3. Was im Browser geprüft wurde

- **Demo-Anmeldung** auf dem Prüfstand nach AP4: **läuft durch** — Ziel
  `index.php`, Titel „Tagesübersicht — Gen-EM NAdoku", 0 Konsolenfehler aus
  der Anwendung. **Der Netzwerkreiter zeigt `api/kdf_upgrade.php` NICHT**, und
  das ist richtig so: Der Aufruf hängt an einer Rundenzahl, die es auf diesem
  Stand nicht mehr gibt (Abschnitt 2, letzte Zeilen). Der Endpunkt selbst ist
  deshalb **unmittelbar** gemessen — mit einer echten Sitzung, ohne Header
  (**403**) und mit Header (**200**).
- **Einsatzbearbeitung mit Reanimationssitzung** und **Einsatzansicht mit
  Phasen** nach AP5: **sehen aus wie vorher** — vier Seiten Bild für Bild
  verglichen, **0 abweichende Bildpunkte** nach Maskieren des Demo-Zählers
  (Abschnitt 2). Im Abschlusslauf tragen dieselben Seiten 0 Überlauf und 0
  Konsolenfehler in allen acht Breiten.
- **Die 404-Seite von `apk.php`** (AP7, der Fund) vor und nach der Behebung im
  Browser geöffnet: vorher Times New Roman ohne Stylesheet und
  `compatMode: BackCompat`, nachher Titel „Datei nicht gefunden — Gen-EM
  NAdoku", Open Sans, `CSS1Compat`.
- **Handbuch-Absatz** (AP3): **nicht im gerenderten Handbuch gelesen, weil es
  keines gibt** — `docs/Handbuch.md` ist ein Markdown-Dokument des
  Repositoriums, die Anwendung rendert es nicht (gemessen: keine Seite unter
  `server/` liest es). Gelesen wurde der Absatz im Markdown, im Zusammenhang
  des Kapitels 11.2; Prüflistenpunkt 3 holt das Urteil über Ton und Ort beim
  Auftraggeber ein.

---

## 4. Prüfliste — was der Auftraggeber noch tun muss

| # | Bedienweg | Erwartet | Wenn nicht |
|---|---|---|---|
| 1 | Nach dem Deploy: **Demo-Konto anmelden** (`nadoku.gen-em.org`, Zugang laut Betriebsakte) | Anmeldung wie immer; Betrieb → Status, Zeile „Schlüsselableitung" unverändert. **Das Risiko ist kleiner, als es in AP4 aussah:** Die geänderte Stelle wird vom Browser nur erreicht, wenn ein Konto eine **andere** Rundenzahl trägt als `KDF_ITER_ZIEL` — seit dem 12.09.2026 (Nr. 155) hat `KDF_ITER_LISTE` nur noch einen Eintrag, und dann kann `unlock.js` den Aufruf gar nicht auslösen. Der Punkt bleibt trotzdem stehen: Er ist die einzige Messung am Produktivstand, und beim **nächsten** Anheben des Zielwerts wird der Weg wieder scharf | Der Tausch in `kdf_upgrade.php` hat den Demo-Weg getroffen — Anmeldung schlägt fehl oder Status zeigt das Demo-Konto „im Übergang". Sofort melden; Rückweg ist ein Commit (die zwei Zeilen zurück) plus Deploy |
| 2 | **Einen Einsatz mit Reanimation** öffnen (Bearbeitung und Ansicht) | Kopfzeile der Reanimationssitzung und Phasennamen sehen aus wie vor der Stufe | Eine der drei Klassen hatte doch eine Wirkung (Regel aus einem anderen Selektor oder ein Skript). Bildschirmfoto; die Klasse kommt zurück, der Streichlisten-Eintrag wird berichtigt |
| 3 | Handbuch 11.2 lesen | Der neue Absatz sagt verständlich, was die vier Zahlen nicht messen | Formulierung, nicht Sache: Änderungswunsch an die nächste Instanz |
| 4 | `tools/uhr-pruefstand/LIESMICH.md`, neuer Unterabschnitt zu `Confirmation` lesen | Deckt sich mit deiner Erinnerung an den S5/C-Rundlauf | Abweichung nennen — die Runde hat es nicht nachgemessen (Abschnitt 0) |
| 5 | **Freigabe des Abschlusses:** danach löscht K9-Lebenszyklus das Konzept; dieses Prüfdokument bleibt | — | — |

---

## 5. Grenzen der benutzten Prüfmittel

- **Die neue Prüfgruppe sieht Aufrufe, keine Wirkung.** `ui_seite_start()`
  in einer Datei heißt nicht, dass die Seite erreichbar ist; ein `confirm(`
  in einer zur Laufzeit zusammengesetzten Zeichenkette findet sie nicht.
  Beides ist bewusst: Ein Mittel, das mehr verspricht, wird ungenau.
- **Die Kreisläufe vergleichen gegen die Referenz, die diese Runde selbst
  erzeugt hat.** Ein Fehler, der auf beiden Seiten gleich ist, bleibt
  unsichtbar — deshalb die Zeile „Rettungsmittel ohne Standort 2 von 6" als
  Sollmaß von außen (Abschnitt 2) und die Klickprobe.
- **Die Wortliste misst die Buchführung dieser Runde nicht.** Bereich (c)
  umfasst `README.md` und sieben Dokumente; `docs/Backlog.md`,
  `docs/CHANGELOG.md` und die Konzept- und Prüfdokumente stehen ausdrücklich
  **nicht** darin, jedes mit Grund (LIESMICH, „Nicht geprüft"). `docs/Rahmenplan.md`
  fehlt in der Liste ebenfalls, wird dort aber nicht als Ausnahme genannt —
  eine Lücke in der Begründung, nicht im Lauf. **Die Folge für diese Runde:**
  „Wortliste 0/0" gilt für **179 Dateien in fünf Bereichen**
  (99 PHP, 35 JS, 8 Dokumente, 2 Android, 35 Uhr — 664 Treffer, alle durch
  Ausnahmen erklärt, 96 von 96 Regeln gegriffen), und AP10 hat ausschließlich
  Dateien angefasst, die **keiner** dieser Bereiche führt. Für sie gibt es
  keinen maschinellen Beleg, nur gelesenen Text.
- **Der Bilderlauf zählt drei Fehlerarten nicht mit.** `ERR_CONNECTION_RESET`,
  `ERR_CONNECTION_CLOSED` und `ERR_ABORTED` fallen auch dann unter das
  Kartenrauschen, wenn die Fundstelle der eigene Server ist (Nr. 176, mit Zahl
  in Abschnitt 0). „0 Konsolenfehler" heißt also: **0 außerhalb dieser drei
  Arten.** Solange Nr. 176 offen ist, gehört dieser Satz zu jeder Zahl des
  Bilderlaufs.
- **Der Bilderlauf löscht seine eigene Vorgeschichte.** `rmSync(AUSGABE)` beim
  Start heißt: Es gibt immer nur den letzten Lauf. Wer zwei Läufe vergleichen
  will, sichert den Bericht vorher — sonst ist die frühere Zahl unbelegbar,
  wie hier die 15.
- **Alle Messungen aus einem Wegwerf-Container**, nicht vom Produktivserver.

---

## 6. Was aus der Runde offen bleibt

- **Nr. 67** (Hauptpunkt, `csrf_check()` ohne API-Zweig) — P5.
- **Nr. 41** (Regeln für `imp-warn`, `imp-daygroup`) — Mockup-Runde 9c.
- **Nr. 172** (Wartungsprobe, Erwartung 15 flattert) — unverändert, nicht
  Teil dieser Runde.
- **Nr. 176** (Rauschfilter des Bilderlaufs verschluckt lokale Fehler) —
  neu in dieser Runde, Backlog-Runde. Nach K4 nicht mitbehoben.
- **Nr. 177** (sechs doppelte Fassungsnummern im Änderungsverlauf des
  Rahmenplans) — neu in dieser Runde, Backlog-Runde. Umnummerieren
  historischer Zeilen ist eine Festlegung, keine Korrektur.
- **Nr. 175** (`edbak_uebersicht()` ohne Aufrufer) — nicht Teil dieser Runde,
  am 13.09.2026 aus der Durchsicht übernommen und hier nur eingetragen.
- **Die 15 Konsolenfehler eines früheren Bilderlaufs** sind nicht aufgeklärt,
  sondern **nicht mehr messbar** (Abschnitt 2). Der Abschlusslauf meldet 0.
  Wer sie wiedersehen will, braucht Nr. 176 behoben und einen gesicherten
  Bericht je Lauf.
- **F-BR3-01** (der Beinahe-Verlust im Backlog, AP2/AP3) ist behoben und hat
  eine Dauerprüfung hinterlassen: die Nummernmenge gegen `dabd7a3`. Sie steht
  in Abschnitt 2 und gehört in jede weitere Runde, die Einträge verschiebt.
