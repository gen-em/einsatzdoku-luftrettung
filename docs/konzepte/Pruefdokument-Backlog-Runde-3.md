# Prüfdokument — Backlog-Runde 3

*Angelegt am 13.09.2026 (Fable) mit dem Konzept `Konzept-Backlog-Runde-3.md`;
**die Zahlen trägt die Umsetzung ein.** Alles in `[eckigen Klammern]` ist
auszufüllen, alles andere ist Sollwert oder Anweisung. Das Prüfprotokoll in
Abschnitt 2 und 3 beantwortet „ist es belegt?"; die Prüfliste in Abschnitt 4
beantwortet „was muss **ich** noch tun?". Muster:
`Pruefdokument-Backlog-Runde-2.md`.*

> | | |
> |---|---|
> | Stand | **Block A vollständig** (AP1–AP5) und **AP6** erledigt (13.09.2026); AP7 in Arbeit. Die Zeilen zu noch nicht gelaufenen Paketen tragen weiter `[ ]` |
> | Stufe | **Web 19.3.1 — Korrektur** (E-BR3-14; die Begründung steht im Konzept). **Keine Migration**, `update.php` muss nach dem Deploy **nicht** laufen. Uhr und Android unberührt. `version.php` steigt erst mit AP4 — dem ersten Paket, das `server/` anfasst |
> | Punkte | Backlog **Nr. 91, 94, 117, 47, 58, 173, 174** erledigt; **Nr. 67** Unterpunkt erledigt, Punkt bleibt (P5); **Nr. 41** drei Streichungen erledigt, Punkt bleibt (9c) |
> | Neu entstanden | Prüfgruppe „5 Zusagen" in `tools/vollstaendigkeit/`, Liste `zusagen.md`; neue Referenzdateien unter `tools/referenzdatensatz/referenz/` |
> | Prüfumgebung | Wegwerf-Container (Linux 6.18, x86_64): **PHP 8.4.19** (eingebauter Server, `opcache.enable_cli` Off), **MariaDB 10.11.14** (mit AP4 nachinstalliert — der Container bringt keinen Datenbankserver mit), TLS über `socat` auf 8443, **Chromium** über Playwright. Volle Installation mit Demo-Konto: 88 Einsätze, 16 Diensttage, 2 Geräte. **Kein ImageMagick, kein `rsvg-convert`** (AP2), **kein Connect-IQ-Simulator** (AP1) |
> | Ergebnis | **Nach AP1:** maschinell grün, soweit AP1 reicht — Wortliste 0/0, Selbstprüfzahl 58 = 58, `grep -c Confirmation` 3 (war 0). Noch kein Urteil über die Runde |

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
  wurde (LIESMICH, „Regressionslauf"). [Gelaufen? Wenn ja: nach Reset,
  Ergebnis.]
- **~~`tools/uhr-bilder/erzeugen.sh` ist nicht gelaufen~~ — erledigt am
  13.09.2026.** Stand so hier, weil der Container kein ImageMagick mitbrachte.
  Mit dem Startvorgang aus AP4 (`.claude/hooks/session-start.sh`) ist es
  nachinstalliert, das Skript **gelaufen** und die Zusage **gemessen** statt
  nur umformuliert — die Zahlen stehen in Abschnitt 2. Der Eintrag bleibt
  sichtbar, damit nachvollziehbar ist, dass die Lücke bestand und wodurch sie
  geschlossen wurde.
- [Weiteres.]

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
- [Weiteres, falls beim Bauen eine Zusage berührt wurde.]

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
| **Gruppe 5 „Zusagen", Seite ohne Gerüst** | — (neu) | **0** Befunde, **7** Ausnahmen, Hinweis „Gerüst ohne Seitenhülle" **1** (`apk.php`) | [ ] |
| **Probe, dass beide rot werden** | — | je ein eingeschleuster Verstoß → Rückgabewert ≠ 0; danach entfernt | [ ] |
| **Kreislauf edbak** (`vergleich/kreislauf.py --art edbak --frisch`) | 287 687 Einzelvergleiche, 0 unerklärt, 16 erwartet, **3 ungenutzte Regeln** | **0 unerklärt, 0 ungenutzt**, gegen die **neue** Referenz; ein Rettungsmittel mit `base_ref: null` kommt unverändert zurück | [ ] |
| **Kreislauf csv** (`--art csv --frisch`) | 9 120 Einzelvergleiche, 0 unerklärt, 1 021 erwartet, **2 ungenutzte Regeln** | **0 unerklärt, 0 ungenutzt**, neue Referenz | [ ] |
| **Rettungsmittel ohne Standort** in der eingespielten Installation | 0 von 6 (Referenz vom 12.09.) | **2 von 6** vor dem Export und nach dem Umlauf | [ ] |
| **Klickprobe** (`tools/klickprobe/probe.mjs`) | 40 von 40 | **40 von 40** | [ ] |
| **Wortliste** (`tools/wortliste/`) | 0 Treffer außerhalb der Ausnahmen, 0 ungenutzt | **0 / 0** | **0 / 0** nach AP1 und AP2 ✓ |
| **AP2 · „bitgleich" in `tools/uhr-bilder/`** | Zusage an 2 Stellen behauptet | **0 Vorkommen, die die Bitgleichheit behaupten** — das Sollmaß „`grep -ci` = 0" ist berichtigt, weil der verlangte Zusatz das Wort verneinen muss (E-BR3-15) | **3 Vorkommen, 0 Behauptungen** ✓ (2 Verneinung, 1 datierte Rückschau) |
| **AP2 · `tools/s5-anker/anker.py`** | Anker `uhrbilder.bitgleich` „unveraendert"; 7 nicht gefunden | Anker findet die Stelle weiter; **nicht gefunden bleibt 7** | Anker `uhrbilder.wortlaut` **„unveraendert"**, nicht gefunden **7** ✓ |
| **AP2 · Syntax der berührten Dateien** | — | `bash -n` und Python-Parser ohne Fehler | **beide ok** ✓ |
| **AP2 · `erzeugen.sh` gelaufen, Zusage gemessen** (nachgetragen 13.09.2026) | die Zahl stammte vom 02.09.2026 | 0 Dateien mit abweichenden Bildpunkten; bytegleich darf **keine** sein | **17 PNG erzeugt · 0 bytegleich · 0 mit abweichenden Bildpunkten** ✓ — „bitgleich" wäre nachweisbar falsch, „pixelgleich" nachweisbar richtig |
| **AP2 · Gegenprobe, dass `compare` misst** | — | weiß gegen schwarz ≠ 0 | 64 × 64 px → **4096** Bildpunkte ✓ |
| **AP3 · „letztes Backup der NutzerIn" als Messwert im Handbuch** | — | **0** Fundstellen (grep auf „zuletzt … Backup", „letztes Backup") | **0** ✓ |
| **AP3 · Gegenprobe am Code** | — | `users` ohne Backup-Spalte; `edbak_konto_stand()` liest nur den Kontoordner; Erinnerungsmail auf denselben Ständen | **16 Spalten, keine davon ein Backup-Zeitpunkt**; `edbak_pakete()` + Begleitdatei; `edbak_faellige_konten()` filtert auf `ueberfaellig`/`nie` ✓ |
| **Backlog-Nummernmenge gegen `dabd7a3`** (neu ab AP3, wegen F-BR3-01) | 170 Einträge | **0 verloren, 0 doppelt**; nur beabsichtigte Verschiebungen | nach AP3 **171 Einträge, 0 verloren, 0 doppelt**; aus *Offen* heraus: 81, 87, 88 (Durchsicht) und 91, 94, 117 (diese Runde) ✓ |
| **Linkprobe** (`tools/linkprobe/probe.py`) | 0 Abweichungen, 0 tote Ausnahmen | **0 / 0** | [ ] |
| **Wartungsprobe** (`tools/wartungsprobe/probe.php`) | 55 Erwartungen, 0 nicht erfüllt (15 flattert, Nr. 172) | **0** nicht erfüllt | [ ] |
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

---

## 3. Was im Browser geprüft wurde

- **Demo-Anmeldung** auf dem Prüfstand nach AP4: Anmeldung läuft durch,
  Netzwerkreiter zeigt `api/kdf_upgrade.php` → 200 mit `uebersprungen: demo`.
  [Ergebnis.]
- **Einsatzbearbeitung mit Reanimationssitzung** und **Einsatzansicht mit
  Phasen** nach AP5: sehen aus wie vorher (Bilderlauf, Abschnitt 2). [Ergebnis.]
- **Handbuch-Absatz** (AP3) im gerenderten Handbuch gelesen, Ton und Ort
  passen. [Ergebnis.]

---

## 4. Prüfliste — was der Auftraggeber noch tun muss

| # | Bedienweg | Erwartet | Wenn nicht |
|---|---|---|---|
| 1 | Nach dem Deploy: **Demo-Konto anmelden** (`nadoku.gen-em.org`, Zugang laut Betriebsakte) | Anmeldung wie immer; Betrieb → Status, Zeile „Schlüsselableitung" unverändert | Der Tausch in `kdf_upgrade.php` hat den Demo-Weg getroffen — Anmeldung schlägt fehl oder Status zeigt das Demo-Konto „im Übergang". Sofort melden; Rückweg ist ein Commit (die zwei Zeilen zurück) plus Deploy |
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
- **Alle Messungen aus einem Wegwerf-Container**, nicht vom Produktivserver.

---

## 6. Was aus der Runde offen bleibt

- **Nr. 67** (Hauptpunkt, `csrf_check()` ohne API-Zweig) — P5.
- **Nr. 41** (Regeln für `imp-warn`, `imp-daygroup`) — Mockup-Runde 9c.
- **Nr. 172** (Wartungsprobe, Erwartung 15 flattert) — unverändert, nicht
  Teil dieser Runde.
- [Fehlerfunde aus Konzept Abschnitt 7, falls welche.]
