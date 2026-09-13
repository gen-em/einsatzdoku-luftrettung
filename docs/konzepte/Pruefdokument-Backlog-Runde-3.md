# Prüfdokument — Backlog-Runde 3

*Angelegt am 13.09.2026 (Fable) mit dem Konzept `Konzept-Backlog-Runde-3.md`;
**die Zahlen trägt die Umsetzung ein.** Alles in `[eckigen Klammern]` ist
auszufüllen, alles andere ist Sollwert oder Anweisung. Das Prüfprotokoll in
Abschnitt 2 und 3 beantwortet „ist es belegt?"; die Prüfliste in Abschnitt 4
beantwortet „was muss **ich** noch tun?". Muster:
`Pruefdokument-Backlog-Runde-2.md`.*

> | | |
> |---|---|
> | Stand | **AP1 und AP2 erledigt** (13.09.2026), AP3 in Arbeit. Die Zeilen zu noch nicht gelaufenen Paketen tragen weiter `[ ]` |
> | Stufe | **Web 19.3.1 — Korrektur** (E-BR3-14; die Begründung steht im Konzept). **Keine Migration**, `update.php` muss nach dem Deploy **nicht** laufen. Uhr und Android unberührt. `version.php` steigt erst mit AP4 — dem ersten Paket, das `server/` anfasst |
> | Punkte | Backlog **Nr. 91, 94, 117, 47, 58, 173, 174** erledigt; **Nr. 67** Unterpunkt erledigt, Punkt bleibt (P5); **Nr. 41** drei Streichungen erledigt, Punkt bleibt (9c) |
> | Neu entstanden | Prüfgruppe „5 Zusagen" in `tools/vollstaendigkeit/`, Liste `zusagen.md`; neue Referenzdateien unter `tools/referenzdatensatz/referenz/` |
> | Prüfumgebung | Wegwerf-Container (Linux 6.18, x86_64). **Für AP1 nicht gebraucht** — der Punkt ist Dokumentation. Fassungen von PHP, MariaDB und Browser werden mit dem ersten Paket eingetragen, das sie benutzt (AP4) |
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
  Prüfstand mit dem Demo-Konto und einem Aufruf ohne Header. Ob das
  Produktiv-Demo-Konto nach dem Deploy unverändert hereinkommt, sagt erst
  Punkt 1 der Prüfliste.
- **Die neue Referenz gegen die Demo-Installation.** Die Kreisläufe liefen
  gegen den Prüfstand. Der Vergleich gegen `nadoku.gen-em.org` misst
  Besucheränderungen mit, wenn das Demo-Konto nicht vorher zurückgesetzt
  wurde (LIESMICH, „Regressionslauf"). [Gelaufen? Wenn ja: nach Reset,
  Ergebnis.]
- **`tools/uhr-bilder/erzeugen.sh` ist nicht gelaufen** (AP2, 13.09.2026). Das
  Skript braucht `convert`, `compare` und `rsvg-convert`; im Container ist
  **kein ImageMagick** installiert (`command -v magick convert compare` leer).
  Die Änderung ist reiner Kommentar — keine Zeile Code berührt —, und
  `bash -n` meldet 0 Fehler. Was damit **nicht** belegt ist: dass das Rezept
  die vier Altdateien heute noch pixelgleich reproduziert. Diese Zahl stammt
  vom 02.09.2026 und ist von AP2 nicht nachgemessen worden; die Zusage im
  Dokument ist nur schwächer formuliert, nicht neu belegt.
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
| **Vollständigkeit** (`tools/vollstaendigkeit/pruefen.py`) | 334 Befunde; `[offen]` 5 | `[offen]` **2**; „auf der Streichliste, aber noch im Markup" **0**; „ohne-regel.md: Eintrag ungenutzt" **0**; Gesamtzahl 334 − 3 = **331** (plus/minus, was Unicode-Kommentare seither dazugetan haben — nennen) | [ ] |
| **Gruppe 5 „Zusagen", native Dialoge** | — (neu) | **0** Befunde, **2** Ausnahmen (`confirm.js`, `forms.js`), **0** Ausnahmen ungenutzt | [ ] |
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
| **Linkprobe** (`tools/linkprobe/probe.py`) | 0 Abweichungen, 0 tote Ausnahmen | **0 / 0** | [ ] |
| **Wartungsprobe** (`tools/wartungsprobe/probe.php`) | 55 Erwartungen, 0 nicht erfüllt (15 flattert, Nr. 172) | **0** nicht erfüllt | [ ] |
| **Bilderlauf** Einsatzbearbeitung mit Reanimation und Einsatzansicht mit Phasen | — | **0** abweichende Bildpunkte gegen den Stand vor AP5 (`compare -metric AE`) — die drei Klassen hatten keine Regel | [ ] |
| **`kdf_upgrade` ohne Header** (`curl` mit Demo-Sitzung, ohne `X-CSRF`) | 200 `uebersprungen: demo` | **403** `{"error":"csrf"}`; mit Header weiterhin 200 | [ ] |
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
