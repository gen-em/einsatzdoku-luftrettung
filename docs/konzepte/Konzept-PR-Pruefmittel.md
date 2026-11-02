# Konzept PR — Prüfmittel (Fahrplanschritt „PR")

**Kürzel:** PR. Pakete `PR-NN`, Entscheidungen `E-PR-NN`, Befunde
`F-PR-NN`, Fragen `Q-PR-NN`, Prüfpunkte `P-PR-NN` (`docs/Pruefablauf.md` 7).
Das Kürzel ist die Kennung aus dem Fahrplan; mit „PR #NNN" (Pull Request)
ist es nicht verwandt.

**Zweig:** `claude/kind-cray-lgaz7d` · **Herkunft:**
`docs/konzepte/Vorbereitung-Backlog-Einordnung.md` 4 und E-BE-04
(Betreiberin, 02.11.2026) · **Prüfdokument:** `Pruefdokument-PR-Pruefmittel.md`
(entsteht mit PR-04).

---

## Statusblock

| Paket | Inhalt | Stand |
|---|---|---|
| PR-01 | Nr. 385 — Zweitfaktorprobe räumt ihre Sperre ab | **erledigt** 02.11.2026 |
| PR-02 | Nr. 352 — `nummern.py` kennt den offenen Merge | **erledigt** 02.11.2026 |
| PR-03 | Nr. 403 — Freigabeprobe im Gesamtlauf; dazu Nr. 460 (Lücke im Prüfstand) | **erledigt** 02.11.2026 |
| PR-04 | Abschluss — Prüfstand, Prüfdokument, Pull Request | in Arbeit |

**In Arbeit:** PR-04. **Es hakt:** nirgends. Nach dem Pull Request: Freigabe der Betreiberin, dann Abschluss nach CLAUDE.md 7.

---

## 1. Auftrag

Drei Werkzeugfehler, die heute Läufe rot färben oder Messungen verfälschen,
vor den Schritten 21 und 22 beheben (E-BE-04). Nur `tools/` und die
Dokumente, die diese Werkzeuge beschreiben — **keine Fassung** wird
hochgestuft, also auch kein Eintrag im Changelog (CLAUDE.md 2 (1)).

Nicht Teil dieses Schritts: die übrigen Werkzeug-Punkte (232, 234, 237, 240,
290, 300, 343, 347, 353, 384, 388, 391, 410, 411). Sie tragen `gehört zu:
14`; die Inventur nach R68 entscheidet über sie (E-BE-04).

## 2. Fragen an die Betreiberin

**Q-PR-01 — Die Lücke F-PR-04 in diesem Schritt schließen?** (a) Zwei
Muster in `pruefablauf.json` (`konfig_stellen.php` → die acht Proben,
die es benutzen; `tools/proben/zweitfaktor/**` → `zweitfaktorprobe`),
mit neuer Backlog-Nummer als Anlass; (b) als Backlog-Punkt nach 14 (die
Inventur entscheidet über die Muster). → **E-PR-03.**

## 2a. Entscheidungen

**E-PR-01 — Ein schlankes Konzept mit Statusblock und Prüfdokument
(02.11.2026, Betreiberin).** Die Fahrplanzeile sagte „kein Konzept — drei
Backlog-Punkte mit Weg und Abnahme"; K5 und K9 verlangen aber Statusblock
und Prüfdokument. Gewählt ist das Muster von RP: dieses Dokument, ein
Prüfdokument daneben, beide gehen beim Abschluss nach CLAUDE.md 7.

**E-PR-02 — Reihenfolge 385, 352, 403 (02.11.2026, Betreiberin).** Die
zwei kleinen, sicheren zuerst; 403 braucht lange Gesamtläufe (je rund
95 Minuten) und hat eine unbekannte Ursache.

**E-PR-03 — Die Lücke F-PR-04 wird in diesem Schritt geschlossen
(02.11.2026, Betreiberin, Q-PR-01 (a)).** Neue Nummer 460 aus der Spanne
460 bis 469 (die Spanne 450 bis 459 trägt `claude/new-session-p1duc7`);
Muster `konfig-stellen` mit den neun Proben, die `konfig_stellen.php`
einbinden (acht Proben und die Bedienprobe über
`einstellungen_profil_passkey.mjs`), und `tools/proben/zweitfaktor/**` im
Muster `zweitfaktor`. Nicht darunter: die Freigabeprobe — sie benutzt den
Rückweg nicht, sie hat nur unter ihm gelitten.

**Modellwahl:** Opus für alle Pakete. **Fächerung:** keine — PR-01 und
PR-03 sind Prüfarbeit an der einen örtlichen Anlage, PR-02 ist eine Datei
(CLAUDE.md 7).

## 3. Arbeitspakete

### PR-01 Zweitfaktorprobe räumt ihre Sperre ab (Nr. 385) — erledigt

*Weg:* Der Aufräumteil der Probe (`register_shutdown_function` nach dem
Anlegen des Probenkontos) löscht Konto, Protokollzeilen und Zähler, aber
nicht das Ereignis in `sicherheit_ereignisse`, das `rate_ereignis()` beim
Sperren schreibt. Neu: `DELETE FROM sicherheit_ereignisse WHERE merkmal = ?`
mit dem Merkmal des Probenkontos (`id:zweitfaktor-probe@probe.invalid`),
alle Töpfe.

*Abnahme:* nach einem Lauf keine Zeile mehr für das Probenkonto.

*Ergebnis:* siehe Prüfprotokoll 4, PR-01.

### PR-02 `nummern.py` kennt den offenen Merge (Nr. 352) — erledigt

*Weg:* Liegt `MERGE_HEAD` vor, werden die Nummern des Arbeitsbaums
zusätzlich um die von `MERGE_HEAD` bereinigt — was der aufgenommene Stand
schon trägt, ist nicht neu. Die Selbstprobe bekommt den Fall und eine
Gegenprobe (eine eigene neue Nummer während des Merges bleibt sichtbar).
`docs/Pruefablauf.md` 5.3 verliert den Satz zur Umgehung über einen
Folge-Commit.

*Abnahme:* ein offener Merge mit einer Nummer aus `main` → 0
Überschneidungen.

*Ergebnis:* siehe Prüfprotokoll 4, PR-02, und F-PR-01.

### PR-03 Freigabeprobe im Gesamtlauf (Nr. 403)

*Weg:* Ursache unbekannt. Gesamtlauf `neben` fahren; trifft der Abbruch,
an der Seitenkennung (seit Web 23.5.1 in der Meldung) die Ursache ablesen
und beheben — nicht die Probe wiederholen lassen. Bleibt der Abbruch aus,
wird das als Befund mit Zahl gemeldet, nicht als Behebung.

*Abnahme:* zwei Gesamtläufe `neben` ohne diesen Abbruch.

*Ergebnis:* Ursache F-PR-02. Behoben in `tools/sandbox/konfig_stellen.php`:
Nach dem Zurücklegen der Datei der Anlage wartet der Rückweg
`max(2, opcache.revalidate_freq) + 1` Sekunden, wenn sich die Bytes
geändert haben (`konfig_stellen_webserver_abwarten()`). An einer Stelle,
weil acht Proben diesen Rückweg gehen und jede die nächste so treffen
kann; die Anteilprobe ist die, die es sichtbar machte. Dazu
`docs/Sandbox-Setup.md` 3 und 6.1 (F-PR-03). Abnahmeläufe:
Prüfprotokoll 4, PR-03.

### PR-04 Abschluss

Prüfstand über den Endstand, Prüfdokument, Backlog-Punkte nach
`Backlog-Erledigt.md`, Pull Request. Erledigt-Zeile, Löschung von Konzept
und Prüfdokument erst nach der Freigabe (CLAUDE.md 7).

## 4. Prüfprotokoll

**PR-01** (02.11.2026, örtliche Anlage Fassung v23.6.0 nach
`hochfahren.sh`, MariaDB des Containers):

| Messung | vorher | nachher |
|---|---|---|
| `php tools/proben/zweitfaktor/probe.php` | 139 ok, 0 fehlen | 139 ok, 0 fehlen |
| Zeilen in `sicherheit_ereignisse` mit Merkmal `%zweitfaktor%` nach dem Lauf | 1 (`totp`, `sperre`, `id:zweitfaktor-probe@probe.invalid`) | 0 |

Der Fehler ist damit vor der Änderung nachgestellt und danach nicht mehr
messbar; Laufzeit der Probe je rund 4 min.

**PR-02** (02.11.2026):

| Messung | alte Fassung | neue Fassung |
|---|---|---|
| `nummern.py --selbstprobe` | — (10 Fälle; die zwei neuen schlagen fehl: 7 und 8 als Überschneidung mit `origin/main`) | 12 Fälle, 0 Fehlschläge |
| Echtes Repositorium: Arbeitsbaum auf `118bc6c~1` (vor Nr. 440), `git merge --no-commit origin/main`, `nummern.py --ohne-holen` | 2 Überschneidungen (Nr. 433, 440) | 0 Überschneidungen |
| `nummern.py --ohne-holen` auf dem Arbeitszweig | 0 | 0 |

**PR-03** (02.11.2026, dieselbe Anlage; Freigabeprobe mit `MOTOR=chromium`
über `tools/proben/proben.sh`):

| Folge | vor der Behebung | nach der Behebung |
|---|---|---|
| Raten → Wartung → Verbindung → Anteil → Freigabe | 1 von 1 rot (3 s, `login.php`) | — |
| Anteil → Freigabe, unmittelbar | 3 von 3 rot (je 3 s, `login.php`) | 3 von 3 grün (25 Erwartungen, 0 nicht erfüllt) |
| Verbindung → Freigabe | 3 von 3 grün | — |
| Anteil → 10 s Pause → Freigabe | 3 von 3 grün | — |
| Anteilprobe selbst | 74 von 74, 13 s | 74 von 74, 16–17 s |

Historie der Berichte auf `main` seit dem 20.10.2026 (`git log`, Feld
`freigabeprobe=`): rot nur in `16c3faf` (R17-23), `d0ee64e` (R17-25),
`e5c3e57` (R17-26), alle mit `anteilprobe=0` im selben Bericht.

Abnahmeläufe: `pruefen.sh --stufe neben --datei server/index.php --datei
server/serverkrypto_lib.php --datei server/db.php` (die Berührung benannt,
weil der Zweig selbst sie nicht auslöste, F-PR-04) — 32 Proben, darunter
Verbindung, Anteil, Raten, Wartung, Freigabe [chromium]:

| Lauf | Ergebnis | Dauer | Freigabeprobe |
|---|---|---|---|
| 1 | 53 grün, 0 rot, 0 nicht gemessen, 1 Hinweis (`bestand`: `tools/steuerung/LIESMICH.md` 41 von 40 Zeilen — eigene Zeile aus PR-02, gekürzt) | 2 266 s | ok, 31 s |
| 2 | 54 grün, 0 rot, 0 nicht gemessen, 0 Hinweise | 2 162 s | ok, 31 s |

Die Reihenfolge im Prüfstand folgt den Mustern: Die Anteilprobe lief früh,
die Ratenprobe (auch über `konfig_stellen`) zwei Proben vor der
Freigabeprobe. Die Folge „Anteil unmittelbar vor Freigabe" belegt die
Nachstellung oben, nicht diese Läufe. Nach beiden Läufen in
`sicherheit_ereignisse` 0 Zeilen mit Merkmal `%zweitfaktor%` (PR-01).

**Nr. 460** (02.11.2026): Trockenlauf `pruefen.sh --trocken` über den
Zweig — vorher 4 Proben (`erzeugen`, `kettenaufrufe`, `steuerung`,
`nummern`), nachher 13, darunter `anteilprobe`, `ratenprobe` und
`zweitfaktorprobe`. `auswahl.py --selbstprobe`: 103 Lagen, 0
Fehlschläge.

## 5. Befunde

**F-PR-02 — Ursache von Nr. 403: der Webserver rechnete nach der
Anteilprobe noch mit ihrem Anteil.** Die Anteilprobe stellt `config.php`
um und legt sie im `finally` bytegleich zurück. `opcache_invalidate()`
leert dabei nur den Speicher der Kommandozeile; der Webserver (`php -S`,
`opcache.enable=1`, `revalidate_freq=2`) sieht die zurückgelegte Datei
erst bis zu zwei Sekunden später. Die Probe wusste das — nach jedem
eigenen Umstellen wartete sie drei Sekunden (Kommentar über
`$fUmstellen`) —, nach dem Zurücklegen aber nicht. Die Freigabeprobe
steht in `pruefablauf.json` unmittelbar dahinter und meldete sich in
Chromium binnen einer Sekunde an; ihr `user_add` landete auf `login.php`
(Seitenkennung: „HTTP 200 · Adresse …/login.php · über 302 · Titel
Anmelden"). Firefox und WebKit liefen später und trafen es nie. Die
Anteilprobe läuft nur, wenn `krypto`, `schluesselwechsel` oder die
Hauptstufe berührt ist — deshalb traf es R17-23, -25, -26 und keinen
Lauf seit PU-03, in dem sie nicht mitlief.

**F-PR-03 — `docs/Sandbox-Setup.md` 6.1 nannte die falsche Ursache.**
„Es ist nicht OPcache (`opcache.enable_cli` steht auf Off)" — der
eingebaute Webserver richtet sich nach `opcache.enable`, und das steht auf
1. Berichtigt mit Verlaufssatz an Ort und Stelle.

**F-PR-04 — Die Dateien dieses Schritts lösen im Prüfstand ihre eigenen
Proben nicht aus.** Kein Muster in `pruefablauf.json` deckt
`tools/sandbox/konfig_stellen.php` (acht Proben gehen darüber) oder
`tools/proben/zweitfaktor/**`; der Trockenlauf über diesen Zweig nennt 4
Proben (`erzeugen`, `kettenaufrufe`, `steuerung`, `nummern`), keine davon
misst die Änderung. Die Abnahme von PR-01 und PR-03 läuft deshalb mit
`--datei` über eine benannte Berührung. Nach K4 gesammelt, nicht nebenbei
behoben — Q-PR-01.

**F-PR-01 — Der erste Fall der Selbstprobe war grün, ohne zu messen.** Der
Klon der Selbstprobe holt `origin` erst im gemessenen Lauf; ein Merge davor
nahm deshalb ein `origin/main` ohne die Nummern 7 und 8 auf, und der Fall
war grün, bevor das Werkzeug geändert war. Behoben in der Anlage des Falls
(`fetch` vor dem Merge); erst danach schlug er fehl, wie er sollte, und
die Behebung färbte ihn grün.
