# Konzept S1 — Sicherung und Import (Zwischenpaket)

Programm: Gen-EM NAdoku (Rahmenplan, Zwischenpaket S1 nach R23, zwischen P1 und P2)
Dieses Dokument: Paketkonzept nach K1 — Befund, Entscheidungen (E), offene
Fragen (F), Arbeitspakete mit Abnahmekriterien, Prüfprotokoll, Fehlerfunde.
Es ist die Übergabeeinheit an die umsetzende Claude-Code-Instanz und wird von
ihr fortgeschrieben.

Ablage im Repositorium: `docs/Konzept-S1-Sicherung-Import.md` (E-S1-18);
das Prüfdokument nach K9 daneben als
`docs/Pruefdokument-S1-Sicherung-Import.md`.

Keine Versionsnummern in diesem Dokument (K3); die Umsetzung stuft die
Version selbst — der Umfang begründet eine **Hauptversion** (die Nutzlast
der Sicherung steigt, E-S1-07). Standardmodell der Umsetzung ist Opus;
**S1 enthält keinen Fable-Schritt** (K2/K8, E-S1-17).
Fehlerfunde werden gesammelt, nicht sofort behoben (K4).
Je Arbeitspaket ein Commit (deutsche Nachricht); gepusht wird einmal am
Paketende nach ausdrücklicher Bestätigung — ein Push auf `main` deployt
sofort (K7).
**Ab diesem Paket gilt die Regressionspflicht R24** (Abschnitt 7).

---

## 1. Ziel

Das Regressionsnetz aus P1 auf **null** bringen und die Sicherung
**vollständig** machen, bevor P2 und P3 sich darauf verlassen:

1. **Der Papierkorb kommt in beide Sicherungen** — NutzerInnen- und
   Admin-Sicherung — und **kommt als Papierkorb zurück** (R22,
   Backlog Nr. 30). Die Nutzlastversion der Sicherung steigt auf 7.
2. **Die beiden Importfehler des CSV-Kreislaufs** werden behoben:
   Nr. 27 (mehrzeilige Notizen verlieren ihre Umbrüche) und Nr. 28
   (`final = 0` und ein leeres `ende` werden überschrieben).
3. **Die Ausnahmelisten der Dokumentation** werden vollständig:
   Nr. 24 (Formelschutz-Apostroph) und Nr. 29 (`Export-Format.md` 5.1).
4. **Nr. 25 ist entschieden:** `missions.created_at` wird beim Einspielen
   **mitgeschrieben** (E-S1-06).
5. **Der Papierkorb-Teil des Demo-Nachlaufs wird zurückgebaut**
   (E-P1-21 wird gegenstandslos) — die Fixture bringt den Papierkorb
   künftig selbst mit.
6. **Referenz-edbak und Fixture werden neu gezogen**, beide Kreisläufe neu
   gemessen. Sollstand danach: **edbak 0, CSV 0** unerklärte Abweichungen
   (R24). Die Abnahmedatei zu R11 ist danach die neue Referenz-edbak.

**Terminbindung:** Die Referenzinstallation räumt ihren eigenen Papierkorb
über den Tagesjob (`TRASH_DAYS = 90`) etwa am **20.11.2026** endgültig ab
(Löschzeitpunkte des P1-Einspiellaufs: ~20.–23.08.2026). Das Neuziehen der
Referenz (C7) muss **davor** liegen — oder über einen frischen Einspiellauf
(`tools/referenzdatensatz/einspielen/einspielen.py`, deterministisch)
erfolgen.

## 2. Befund (statische Analyse, Stand `main` = Web 7.3.1)

- **B-S1-01 — Die Frist sind 90 Tage, nicht 30.** `trash_lib.php`:
  `TRASH_DAYS = 90`; Handbuch 8 und Technik sagen 90. Rahmenplan (R22,
  S1-Text) und Konzept P1 §10 nennen fälschlich 30 — Berichtigung des
  Rahmenplans in Abschnitt 9.
- **B-S1-02 — Sichern ist fast fertig.** `edbak_build(int $userId,
  bool $mitPapierkorb = false)`: Der Filter `deleted_at IS NULL` ist
  abschaltbar; die Spalten `deleted_at`/`deleted_with_day` (Einsätze,
  Ruhesegmente) und `days.deleted_at` stehen bereits in jeder Datei (bisher
  stets `null`). `api/backup_data.php`, `adminbackup_lib.php`
  (`edbak_sicherung_erzeugen()`) und die Fixture
  (`tools/referenzdatensatz/fixture/erzeugen.php`) rufen alle
  `edbak_build()` — der Papierkorb erbt sich dorthin ohne eigene Logik.
- **B-S1-03 — Der Rückweg fehlt vollständig.** `edbak_restore()` schreibt
  keine der drei Spalten: Einsätze über Feldkatalog + benannte Ausnahmen
  (`start_src`, `pat_blob`), Ruhesegmente mit fester Spaltenliste, Diensttage
  mit festem INSERT. Ein bloßes Abschalten des Filters brächte den
  Papierkorb als **aktiven** Bestand zurück.
- **B-S1-04 — D1 heute:** `edbak_restore()` stellt vorab die **Daten** der
  Papierkorb-Tage des **Zielkontos** fest (`$tageImPapierkorb`, Schlüssel =
  Datum) und überspringt jeden Datei-Tag dieses Datums (Zählgrund
  `tag_im_papierkorb`). Die Wiedererkennung (Schritt 1 `client_ref`,
  Schritt 2 Fingerabdruck `$findeTag`) filtert **nicht** nach `deleted_at`.
  „Tag im Zielkonto im Papierkorb" und „Tag in der Datei im Papierkorb"
  sind im Code heute ununterscheidbar.
- **B-S1-05 — Zombie-Gefahr ohne Invariante.** `trash_list_missions()`
  zeigt nur `deleted_with_day = 0`; `trash_restore_day()` holt nur
  `deleted_with_day = 1` zurück. Ein Einsatz mit `deleted_with_day = 1` an
  einem **aktiven** Tag wäre unsichtbar und unwiederbringlich. Der Rückweg
  braucht die Invariante E-S1-04.
- **B-S1-06 — Nutzlast 7 sperrt nichts Ausgeliefertes.**
  `api/backup_restore.php` weist nur `version < 6` ab. Ein bereits
  ausgelieferter Stand (7.3.1) nimmt eine Version-7-Datei an und brächte
  deren Papierkorb **aktiv** zurück. Der Sprung auf 7 ist Kennzeichnung,
  keine Sperre — so wird er dokumentiert (E-S1-07).
- **B-S1-07 — Rückmeldung unvollständig.** Die Beschriftungen der
  Überspringgründe in `einstellungen.php` kennen `tag_im_papierkorb` und
  `tag_unbrauchbar` nicht (roher Schlüssel erscheint); Einsätze und
  Ruhesegmente eines übersprungenen Tages laufen unter
  `datum_oder_zeit` — irreführend.
- **B-S1-08 — Nr. 25:** `missions.created_at` wird in der Anwendung
  nirgends gelesen (einzige Fundstelle: `$missionSpalten` in
  `backup_lib.php`). Das Vergleichswerkzeug normalisiert
  `missions[].created_at` weg (`normalisieren.py`) — der Kreislauf sieht
  die Asymmetrie heute nicht.
- **B-S1-09 — Nr. 27:** Ursache ist der Parser `trim`
  (`assets/import.js`): jede Leerraumfolge → ein Leerzeichen, Umbrüche
  eingeschlossen. Das Notizfeld (`target: notes`) lesen drei Profile:
  `export_csv_v1` (Spalte `notizen` — der in P1 gemessene Fall, 4 Notizen,
  je genau 1 Umbruch, Zeichenzahl 164/253/119/150), `export_excel_v1` und
  das GuteSeele-Excel (je Spalte `Notizen`). Alle drei laufen über `trim`.
  Diensttag-Notizen kommen über keinen Import zurück — nur die
  Einsatz-Notiz ist betroffen.
- **B-S1-10 — Nr. 28:** `api/import_commit.php` setzt `final` als Literal
  `1` im INSERT; das UPDATE (Überschreiben) fasst `final` nicht an. Ein
  leeres `ende` fällt auf `started_at` zurück
  (`$endedAt = $utc(...) ?? $startedAt`). Der Browser sendet
  `ended_utc: m.ended || null` (`assets/import_ui.js`) — „Spalte fehlt"
  (Jahresliste, Excel-Profile) und „Zelle leer" sind serverseitig
  ununterscheidbar. `dt_zeitraum_fortschreiben()` verträgt `null` als Ende.
- **B-S1-11 — Nr. 24/29:** reine Dokumentation (`Export-Format.md` 5.1).
  Nebenbefund: dass der Papierkorb auch im **CSV-Export** nicht enthalten
  ist (`api/export_data.php` filtert `deleted_at IS NULL`), steht ebenfalls
  nirgends.
- **B-S1-12 — Demo-Nachlauf (E-P1-21):** `demo_nachlauf()` in
  `demo_lib.php` (läuft nach dem Commit, weil `trash_lib.php` eigene
  Transaktionen öffnet), Fixture-Block `nachlauf` (Erzeuger
  `fixture/erzeugen.php`), Doku in `docs/Technik.md` 4.99a und der
  Aufzählungspunkt in `server/admin_demo.php`. Die **alte** Fixture bleibt
  unter dem neuen Rückweg lauffähig: ihre `daten` tragen `deleted_at`
  bereits (mit Flag erzeugt); der Nachlauf findet dann nichts mehr
  (`deleted_at IS NULL`-Filter) und zählt `nicht_gefunden` — harmlos, bis
  C3 ihn entfernt.
- **B-S1-13 — Vergleichswerkzeug:** `normalisieren.py` ersetzt flüchtige
  Anteile durch Marken; `deleted_at` ist dort heute nicht behandelt (war
  immer `null`). Ausnahmelisten: `edbak_umlauf.json` (1 Regel, 15×
  gemessen), `csv_umlauf.json` (Regeln mit Messzahlen; die 6 unerklärten
  Abweichungen sind genau Nr. 27/28). Referenzumfang: 82 Einsätze,
  95 Ruhesegmente, 15 Diensttage **aktiv** + 5/5/1 im Papierkorb =
  87/100/16 gesamt.
- **B-S1-14 — `deleted_refs`** ist kein Papierkorb: hängt an einer
  Gerätekennung; Geräte stehen in keiner Sicherung. Bleibt draußen
  (E-S1-09). Wiederhergestellte Einträge tragen `device_id = NULL` — ein
  späteres endgültiges Löschen füllt die Sperrliste dann nicht
  (`trash_block_ref()` verlangt eine Gerätekennung); das ist bestehendes,
  dokumentiertes Verhalten der Wiederherstellung (Geräte weg → Sperrliste
  leer) und keine neue Lücke.

## 3. Entscheidungen

Alle F-Fragen wurden **vor** Konzepterstellung entschieden
(23./24.08.2026) und sind hier als E überführt.

| Nr. | Entscheidung |
|---|---|
| E-S1-01 | **Der Papierkorb ist grundsätzlich in jeder Sicherung** — NutzerInnen-Sicherung, Admin-Sicherung, Fixture. Der Parameter `$mitPapierkorb` von `edbak_build()` entfällt ersatzlos; der Kopfkommentar (der heute die Gegenentscheidung begründet) wird neu geschrieben. |
| E-S1-02 | **Keine Wahlmöglichkeit auf der Sicherungsseite.** Eine Sicherung ist ein Abbild. Stattdessen nennen die Umfangsangaben „davon im Papierkorb": im `umfang`-Block der Admin-Sicherung (neuer Unterblock `papierkorb` mit `einsaetze`/`diensttage`/`ruhezeiten`; Paketversion der Admin-Sicherung bleibt 1 — additiv), in der Admin-Tabelle (`admin_sicherungen.php`) und im Freigabe-Hinweis (`einstellungen.php`). Die NutzerInnen-Seite nennt die Papierkorbzahlen in der Import-Rückmeldung (E-S1-08). |
| E-S1-03 | **`deleted_at` wird beim Einspielen auf den Einspielzeitpunkt gesetzt.** Aus der Datei wird der **Zustand** übernommen (gelöscht ja/nein, `deleted_with_day`), nicht der Zeitpunkt — dieselbe Logik wie bei `herkunft`: Der Eintrag entsteht in dieser Installation neu und bekommt volle 90 Tage. Die Abweichung Datei ↔ Bestand wird in `Backup-Format.md` ausdrücklich benannt. Folgen: keine „Frist bereits überschritten"-Fälle beim Einspielen (der Prüffall aus Konzept P1 §10.3 b entfällt), und der Demo-Reset stempelt automatisch frisch — kein Sonderweg (E-S1-10). |
| E-S1-04 | **Invariante des Rückwegs:** `deleted_with_day = 1` wird nur geschrieben, wenn der **Ziel**-Diensttag (nach Zuordnung) selbst im Papierkorb liegt; andernfalls wird auf `0` gesetzt (einzeln gelöscht — sichtbar und wiederherstellbar). Ohne `deleted_at` kein `deleted_with_day`. Damit ist der Zombie-Fall aus B-S1-05 konstruktiv ausgeschlossen. |
| E-S1-05 | **D1 in zwei Fällen:** (1) Die Datumsprüfung gegen Papierkorb-Tage des **Zielkontos** gilt nur noch für **aktive** Tage der Datei — Verhalten unverändert: überspringen und zählen („Ablehnen statt zurückholen"). (2) Ein in der **Datei** gelöschter Tag durchläuft die normale Wiedererkennung; wird er nicht gefunden, entsteht er **als Papierkorbeintrag** samt seiner mitgelöschten Einsätze/Ruhesegmente. Wird er im Ziel gefunden (auch als aktiver Tag), bleibt der Zieltag unangetastet — „Angaben werden nicht überschrieben" gilt weiter; die Einsätze folgen dann E-S1-04. Zwei gelöschte Tage desselben Datums (Datei und Ziel, verschiedener Fingerabdruck) dürfen nebeneinander bestehen. |
| E-S1-06 | **`missions.created_at` wird mitgeschrieben** (Nr. 25) — als benannte Ausnahmespalte neben `start_src`/`pat_blob`, mit Begründung an Ort und Stelle. Prüfung über `pruef_utc_oder_sql`; ein unbrauchbarer Wert fällt auf die Datenbank-Vorgabe zurück (Zeile bleibt). Der Kreislauf belegt den Erhalt künftig (E-S1-15). `Backup-Format.md` 4: Der Eintrag wandert von „kommt nicht zurück" in die Formatbeschreibung. |
| E-S1-07 | **Nutzlastversion 7.** `edbak_build()` schreibt `version: 7`; die Annahme in `api/backup_restore.php` bleibt `>= 6` — Version-6-Dateien enthalten keinen Papierkorb und bleiben vollständig einspielbar. Der Sprung ist **Kennzeichnung, keine Sperre**: Bereits ausgelieferte Stände (Annahme `>= 6`) würden eine v7-Datei annehmen und den Papierkorb aktiv zurückbringen; das wird in `Backup-Format.md` als Warnung ausdrücklich gesagt. Container (`EDBAK2`, Version 3) unverändert. |
| E-S1-08 | **Rückmeldung der Wiederherstellung wird vollständig:** alle Überspringgründe benannt (`tag_im_papierkorb`, `tag_unbrauchbar` erhalten Beschriftungen; **neuer Grund** `tag_uebersprungen` für Einsätze/Ruhesegmente, deren Datei-Tag übersprungen wurde — bisher irreführend unter `datum_oder_zeit`); dazu die Papierkorbzahlen („N Einsätze, M Ruhesegmente, K Diensttage in den Papierkorb übernommen; die 90-Tage-Frist beginnt neu"). Ruhesegmente zählen ihre Gründe künftig mit. |
| E-S1-09 | **`deleted_refs` bleibt draußen** (Bestätigung R22): Die Sperrliste hängt an einer Gerätekennung, Geräte stehen aus gutem Grund in keiner Sicherung. Der Satz steht ausdrücklich im Konzept und in `Backup-Format.md` 4, damit die Bereiche nicht zusammengezogen werden. |
| E-S1-10 | **Demo ohne Sonderweg.** Der Reset läuft unverändert über `edbak_restore()`; über E-S1-03 trägt der Demo-Papierkorb nach jedem Reset frische 90 Tage. `demo_nachlauf()`, der Fixture-Block `nachlauf` und dessen Erzeugung entfallen ersatzlos (E-P1-21 wird gegenstandslos); der Berichtszähler `papierkorb` kommt aus den neuen Restore-Zählern. Fixture-Format: Pflichtfelder unverändert (`konto`, `daten`); die Versionsangabe der Fixture steigt auf 2 (Kennzeichnung des entfallenen Blocks; `demo_fixture_laden()` bleibt tolerant). |
| E-S1-11 | **Nr. 27:** Neuer Parser `trimMehrzeilig` (Leerraum nur **innerhalb** einer Zeile zusammenziehen, Umbrüche erhalten, Länge weiterhin über `max:2000`) für **alle** Spalten mit Ziel `notes` — `notizen` (export_csv_v1) und `Notizen` (export_excel_v1, GuteSeele). Bei einzeiligen Werten identisches Verhalten wie `trim`. |
| E-S1-12 | **Nr. 28:** `final` und `ende` sind **Zustand**, nicht Entstehung, und kommen aus der Datei — in INSERT **und** UPDATE (Überschreiben). „Spalte fehlt" ≠ „Zelle leer": Fehlt die Spalte im Profil (Jahresliste, Excel), gilt das bisherige Verhalten (`final = 1`, `ende = Beginn`); ist die Zelle leer, wird `final` aus der Datei bzw. `ended_at = NULL` geschrieben. Dazu Vertragserweiterung von `api/import_commit.php`: neues optionales Feld `final` (0/1); `ended_utc` **weggelassen** = Spalte fehlt, `null` = Zelle leer (der Browser sendet `ended_utc` nur noch, wenn das Profil die Spalte führt). Das Profil `export_csv_v1` übernimmt `final` (bisher `target: null`). |
| E-S1-13 | **Nr. 24:** Der Formelschutz-Apostroph wird als vierte bewusste Ausnahme in `Export-Format.md` 5.1 **dokumentiert**; der Import bleibt unverändert (ein Import, der Zeichen entfernt, schüfe den nächsten stillen Verlust). |
| E-S1-14 | **Nr. 29:** `Export-Format.md` 5.1 wird um die gemessenen Ausnahmen ergänzt (Ruhesegmente kommen nicht zurück, 95 → 0; der zweite Dienst eines Kalendertags geht verloren, 15 → 13). Zusätzlich in Abschnitt 6 (Grenzen): Der Papierkorb ist im CSV-Export nicht enthalten. |
| E-S1-15 | **Vergleichswerkzeug:** `normalisieren.py` ersetzt `deleted_at` durch eine Zeitmarke, **erhält aber die Unterscheidung leer/gesetzt** (der Kreislauf belegt „Papierkorbeintrag kommt als Papierkorbeintrag zurück", nicht den Zeitpunkt — Folge von E-S1-03). Die Normalisierung von `missions[].created_at` wird **aufgehoben** (Folge von E-S1-06; die Kopfangabe `created_at` der Datei bleibt normalisiert). Ausnahmelisten werden mit neu gemessenen Zahlen fortgeschrieben; die `--testabweichung`-Proben erhalten je eine Hin- und Gegenprobe für die neuen Regeln. |
| E-S1-16 | **Referenz neu:** Referenz-edbak (`tools/referenzdatensatz/referenz/`, Passwort `nadokudemo0815`) und Fixture (`server/demo/fixture.json.gz`) werden nach C1–C6 neu erzeugt — **vor dem 20.11.2026** aus der bestehenden Referenzinstallation oder über einen frischen Einspiellauf. Die neue Referenz-edbak ist zugleich die R11-Abnahmedatei. Die Referenz-CSV bleibt unverändert, sofern die Referenz nicht per frischem Einspiellauf neu entsteht (dann beide neu). |
| E-S1-17 | **Kein Fable-Schritt in S1.** D1 ist Prüfaufwand, keine Modellfrage; alle Pakete Standardmodell (K2). |
| E-S1-18 | **Ablage:** Konzept und Prüfdokument unter `docs/`, neben `docs/Pruefung-Sofortpaket-22.md`. |

## 4. Offene Fragen

Keine. Alle F-Fragen sind entschieden und als E-S1-01 bis E-S1-18 überführt
(K6). Neue Fragen während der Umsetzung hier eintragen und vor Umsetzung des
betroffenen Pakets entscheiden lassen.

## 5. Arbeitspakete

Reihenfolge ist verbindlich: C6 muss vor C7 liegen (das Werkzeug muss die
neuen Felder verstehen, bevor gemessen wird); C2 vor C3 (der Rückweg muss den
Papierkorb bringen, bevor der Nachlauf fällt). C4/C5 sind von C1–C3
unabhängig und können vorgezogen werden.

### C1 — Sicherung vollständig (Bauform und Kennzeichnung)

**Umfang:** `backup_lib.php` (`edbak_build()`), `api/backup_restore.php`
(nur Kommentar/Meldung), `adminbackup_lib.php`, `admin_sicherungen.php`,
`einstellungen.php` (Freigabe-Hinweis), `fixture/erzeugen.php` (Aufruf ohne
Flag), `docs/Backup-Format.md`, `docs/Handbuch.md` 6.

**Vorgehen:**
1. `$mitPapierkorb` entfernen; Filterfragmente `$nurAktive`/`$nurAktiveD`
   entfallen; Kopfkommentar neu (E-S1-01).
2. `version` auf 7; Kommentar zur Bedeutung (E-S1-07). Annahme in
   `api/backup_restore.php` bleibt `>= 6`; der dortige Kommentar erklärt,
   warum 6 lesbar bleibt.
3. `umfang` der Admin-Sicherung: Unterblock `papierkorb`
   (`einsaetze`/`diensttage`/`ruhezeiten`); Anzeige in Admin-Tabelle und
   Freigabe-Hinweis „davon X im Papierkorb" (E-S1-02).
4. Doku: `Backup-Format.md` 2 (Beispiel `version: 7`, Felder
   `deleted_at`/`deleted_with_day` beschrieben — samt Hinweis, dass der
   Zeitstempel beim Einspielen **nicht** übernommen wird, E-S1-03), 4
   (Papierkorb-Eintrag wandert aus „kommt gar nicht vor"; Warnung ältere
   Stände; `deleted_refs`-Abgrenzung bleibt); Handbuch 6 (Sicherung enthält
   den Papierkorb).

**Abnahme:** Eine frisch erzeugte NutzerInnen-Sicherung und eine
Admin-Sicherung enthalten die Papierkorbeinträge (Referenz: 5/5/1);
`umfang.papierkorb` stimmt; `version` = 7; eine Version-6-Datei wird
weiterhin angenommen.

### C2 — Rückweg: Papierkorb und `created_at`

Das Kernpaket — hier liegt die D1-Regel, „die Stelle, an der ein Fehler am
teuersten wäre".

**Umfang:** `backup_lib.php` (`edbak_restore()`), `einstellungen.php`
(Rückmeldung), `docs/Backup-Format.md` 3/4, `docs/Handbuch.md` 8.

**Vorgehen:**
1. **Diensttage:** Datumsprüfung `$tageImPapierkorb` nur noch für aktive
   Datei-Tage (E-S1-05.1). Datei-gelöschte Tage: normale Wiedererkennung;
   bei Neuanlage `deleted_at = UTC_TIMESTAMP()` (E-S1-03); bei Fund im Ziel
   keine Änderung am Zieltag.
2. **Einsätze:** benannte Ausnahmespalten um `created_at` (E-S1-06) sowie
   den Löschzustand erweitern. Löschzustand nach E-S1-03/E-S1-04:
   `deleted_at` gesetzt ⇒ `UTC_TIMESTAMP()`; `deleted_with_day = 1` nur,
   wenn der zugeordnete Zieltag im Papierkorb liegt, sonst 0.
3. **Ruhesegmente:** dieselben Regeln; Überspringgründe mitzählen
   (E-S1-08).
4. **Zählwerk und Rückmeldung:** neue Zähler (Papierkorb je Art), neuer
   Grund `tag_uebersprungen`, Beschriftungen vollständig; Satz zur neu
   beginnenden Frist (E-S1-08).
5. Doku: `Backup-Format.md` 3 (Import-Verhalten: Papierkorb, Frist neu,
   Invariante, D1 beide Fälle); Handbuch 8 (der Absatz „Import und
   Einspielen überspringen solche Tage" wird präzisiert: gilt für Tage, die
   **hier** im Papierkorb liegen; was **in der Datei** gelöscht ist, kommt
   als Papierkorbeintrag zurück).

**Abnahme (mindestens, zusätzlich Prüfprotokoll):**
- Umlauf der Referenz in ein frisches Konto: 87/100/16 gesamt, davon 5/5/1
  im Papierkorb; der mitgelöschte Bestand des gelöschten Diensttags trägt
  `deleted_with_day = 1`, einzeln Gelöschtes `0`.
- Gegenprobe D1 (Ziel-Papierkorb): Datei in ein Konto mit gleichnamigem
  Tag im Papierkorb → überspringen mit Grund, abhängige Einsätze unter
  `tag_uebersprungen`.
- Zombie-Gegenprobe: Datei-gelöschter Tag, im Ziel aktiv vorhanden →
  Einsätze landen (soweit neu) als **einzeln** gelöscht, Zieltag bleibt
  aktiv.
- `created_at` kommt wörtlich zurück (Stichprobe vor Kreislaufmessung).

### C3 — Demo-Nachlauf-Rückbau

**Umfang:** `demo_lib.php`, `fixture/erzeugen.php`, `admin_demo.php`,
`docs/Technik.md` 4.99a und Fixture-Tabelle,
`tools/referenzdatensatz/LIESMICH.md`.

**Vorgehen:** `demo_nachlauf()` samt Aufrufen entfernen; `$stats['papierkorb']`
aus den Restore-Zählern speisen; Erzeuger schreibt keinen `nachlauf`-Block
mehr, Fixture-Version 2 (E-S1-10); Admin-Text und Technik-Doku nachziehen.
Die **alte** Fixture bleibt bis C7 in Betrieb (B-S1-12) — der Rückbau darf
das Anlegen/Zurücksetzen mit ihr nicht brechen.

**Abnahme:** Reset mit alter Fixture: Papierkorb 5/5/1 direkt aus dem
Einspielen, Bericht ohne Nachlauf-Zähler, `demo_pruefen.mjs` grün
(„im Papierkorb" = 5).

### C4 — CSV-Import: Nr. 27 und Nr. 28

**Umfang:** `assets/import.js` (Parser), `assets/import_profiles.js`,
`assets/import_ui.js` (Nutzlastaufbau), `api/import_commit.php`
(Vertrag, INSERT, UPDATE), `docs/Export-Format.md` 5.1 (Verhaltensteil).

**Vorgehen:** `trimMehrzeilig` nach E-S1-11; `final`/`ended_utc` nach
E-S1-12 (Vertragskommentar in `import_commit.php` erweitern; Browser sendet
`final` und `ended_utc` nur bei vorhandener Spalte; leere Zelle → `final`
aus Datei bzw. `ended_utc: null` → `ended_at = NULL`).
`dt_zeitraum_fortschreiben()` bleibt unverändert (verträgt `null`).

**Abnahme:** Der Referenzfall (Einsatz 2026-07-05, 19:40 — der einzige
nicht abgeschlossene) übersteht den Umlauf mit `final = 0` und leerem
`ende`; die vier mehrzeiligen Notizen behalten ihren Umbruch bei
unveränderter Zeichenzahl (164/253/119/150); Jahreslisten- und
Excel-Import ohne `ende`-Spalte verhalten sich wie bisher.

### C5 — Doku-Ausnahmen: Nr. 24 und Nr. 29

**Umfang:** `docs/Export-Format.md` 5.1 und 6.

**Vorgehen:** Apostroph-Ausnahme (E-S1-13), Ruhesegmente und zweiter
Dienst je Kalendertag mit den Messzahlen (E-S1-14), Papierkorb-Hinweis in
Abschnitt 6.

**Abnahme:** 5.1 zählt alle gemessenen Ausnahmen; kein Widerspruch zu den
Ausnahmelisten des Vergleichswerkzeugs.

### C6 — Vergleichswerkzeug nachziehen

**Umfang:** `tools/referenzdatensatz/vergleich/normalisieren.py`,
`ausnahmen/*.json`, ggf. `vergleichen.py` (Proben).

**Vorgehen:** nach E-S1-15. Die Ausnahmelisten-Regeln zu Nr. 27/28
werden **nicht** aufgenommen — die Fehler werden in C4 behoben, nicht
festgeschrieben (R24). Neue Messzahlen in die Begründungen; je eine
Hin-/Gegenprobe für `deleted_at` (leer↔gesetzt muss gemeldet, Zeitwert
darf nicht gemeldet werden) und `created_at` (Wertänderung muss gemeldet
werden).

**Abnahme:** `--testabweichung` besteht mit den erweiterten Proben
vollständig.

### C7 — Referenz neu, Kreisläufe auf null, Abschluss

**Umfang:** `tools/referenzdatensatz/referenz/` (edbak neu; CSV nur bei
frischem Einspiellauf), `server/demo/fixture.json.gz` (neu, Format 2),
beide Kreislaufmessungen, `docs/Backlog.md`, `docs/CHANGELOG.md`,
Konzept- und Prüfdokument-Abschluss.

**Vorgehen:**
1. Referenzzustand herstellen (Terminbindung Abschnitt 1; bei frischem
   Einspiellauf entstehen die Papierkorbfälle über die Prüfschritte des
   Laufs neu).
2. Referenz-edbak neu ziehen (= neue R11-Abnahmedatei), Fixture neu
   erzeugen, Demo-Konto zurücksetzen.
3. Beide Kreisläufe (`kreislauf.py --art edbak` / `--art csv`), Zahlen ins
   Prüfdokument. **Soll: 0 / 0 unerklärt.**
4. Backlog: Nr. 24, 25, 27, 28, 29, 30 nach *Erledigt* (Nummern bleiben);
   neuer Eintrag für Fund F-S1-A (Abschnitt 8), freie Nummer.
5. Changelog und Versionsstufung (Hauptversion); Konsistenzdurchsicht der
   berührten Doku.

**Abnahme:** beide Kreisläufe 0 unerklärt; Angriffswerte-Regression
(`browser/angriffswerte.mjs`) unverändert grün; Demo-Prüfskripte grün;
Backlog/Changelog/Doku konsistent.

## 6. Prüfprotokoll (Soll)

Wird von der umsetzenden Instanz mit Ist-Zahlen fortgeschrieben; Bedienwege
und Fehlschlag-Erkennung gehören ins Prüfdokument (K9).

| Nr. | Prüfung | Soll |
|---|---|---|
| P-S1-01 | Kreislauf edbak (`kreislauf.py --art edbak`) | 0 unerklärte Abweichungen; Vergleichsumfang wächst um den Papierkorb (87/100/16 statt 82/95/15) |
| P-S1-02 | Kreislauf CSV (`--art csv`) | 0 unerklärte Abweichungen (heute 6: Nr. 27/28) |
| P-S1-03 | D1, Fall Zielkonto (Browser) | Datei-Tag mit Datum eines Ziel-Papierkorb-Tags wird übersprungen und benannt; abhängige Einsätze unter `tag_uebersprungen` |
| P-S1-04 | D1, Fall Datei (Browser) | gelöschter Datei-Tag entsteht als Papierkorbeintrag; Wiederherstellen des Tags bringt genau die mitgelöschten Einsätze (`deleted_with_day = 1`), nicht die vorher einzeln gelöschten |
| P-S1-05 | Invariante (Browser) | Datei-gelöschter Tag, im Ziel aktiv → neue Einsätze einzeln gelöscht; kein Eintrag mit `deleted_with_day = 1` an aktivem Tag (SQL-Nachzählung: 0) |
| P-S1-06 | Frist (E-S1-03) | Alle eingespielten Papierkorbeinträge tragen `deleted_at` ≈ Einspielzeitpunkt; Rückmeldung nennt die Zahlen und den Fristbeginn |
| P-S1-07 | `created_at` | Kreislauf edbak vergleicht `missions[].created_at` wörtlich (Normalisierung aufgehoben), 0 Abweichungen |
| P-S1-08 | Nr. 27 | 4 Notizen mit Umbruch, Zeichenzahl 164/253/119/150 unverändert |
| P-S1-09 | Nr. 28 | Referenzfall bleibt `final = 0`, `ende` leer — auch im Überschreiben-Modus |
| P-S1-10 | Version | v6-Referenzdatei (Bestand vor C7) wird angenommen und korrekt eingespielt; v7 in älteren Ständen ist **nicht prüfbar** — als Warnung dokumentiert, nicht behauptet |
| P-S1-11 | Demo | Reset ohne Nachlauf: Papierkorb 5/5/1, `demo_pruefen.mjs` 16/16, Reset-Dauer nicht schlechter als Ausgangswert (~6 s lokal) |
| P-S1-12 | Werkzeugproben | `--testabweichung` je Format vollständig bestanden, inkl. neuer Proben (E-S1-15) |
| P-S1-13 | Dauer-Regression R20 | `browser/angriffswerte.mjs`: 42 Einzelprüfungen, 0 Befunde |

## 7. Regressionspflicht (R24)

Ab S1 führt jede Phase vor Abschluss beide Kreisläufe und trägt die Zahlen
ins Prüfdokument. Sollstand nach S1: **edbak 0, CSV 0.** Vor jedem
Vergleichslauf wird das Demo-Konto zurückgesetzt. Eine neue unerklärte
Abweichung ist ein Befund der laufenden Phase; behebbare Abweichungen
kommen **nicht** in die Ausnahmelisten.

## 8. Fehlerfunde (gesammelt, K4)

### F-S1-A — Rückweg der Ruhesegmente ohne Prüfschicht

**Fundort:** `backup_lib.php`, `edbak_restore()`, Abschnitt Ruhesegmente.

**Sache:** Anders als bei den Einsätzen (die seit dem Review die
`pruef_*`-Funktionen durchlaufen) werden `started_at`/`ended_at` der
Ruhesegmente ungeprüft in das INSERT gegeben; Überspringgründe wurden
bisher nicht gezählt. Die Zählung behebt C2 (E-S1-08); die fehlende
Prüfschicht ist **nicht** Umfang von S1.

**Blockierend:** nein.

**Verbleib:** neuer Backlog-Eintrag in C7 (freie Nummer).

### F-S1-B — „30-Tage-Frist" in der Technik-Dokumentation

**Fundort:** `docs/Technik.md`, Tabelle „Zeitrechnung".

**Sache:** Der Eintrag zu `UTC_TIMESTAMP()` nannte für den Papierkorb eine
30-Tage-Frist. Es sind 90 (`TRASH_DAYS`); alle übrigen Stellen der
Dokumentation sagten das auch. Dieselbe falsche Zahl steht im Rahmenplan
(R22) und in Konzept P1 §10 — dort ist sie in Abschnitt 9 als Berichtigung
vorgemerkt.

**Blockierend:** nein. In C1 als Wortkorrektur behoben, kein Backlog-Eintrag.

### F-S1-C — Aktiver Datei-Eintrag auf einem gelöschten Zieltag

**Fundort:** `backup_lib.php`, `edbak_restore()`, Zuordnung Einsatz → Zieltag.

**Sache:** Die Invariante E-S1-04 schließt den Zombie in einer Richtung aus
(kein `deleted_with_day = 1` an einem aktiven Tag). Die Gegenrichtung ist
offen: Ein in der Datei **aktiver** Einsatz kann auf einem **gelöschten**
Zieltag landen und wird dort als aktiver Eintrag angelegt — er steht dann an
einem Tag, den die Tagesliste nicht zeigt.

Erreichbar ist das nur über Schritt 1 der Wiedererkennung (ein Einsatz
derselben `client_ref` liegt im Ziel bereits an einem gelöschten Tag
**anderen Datums**); die Datumsprüfung aus E-S1-05.1 greift dann nicht, weil
sie das Datum vergleicht. Der Fall entsteht etwa, wenn jemand nach einer
Wiederherstellung einen Einsatz auf einen anderen Tag verschiebt, diesen Tag
löscht und dieselbe Datei erneut einspielt.

**Warum hier nichts geändert wurde:** E-S1-04 sagt ausdrücklich „ohne
`deleted_at` kein `deleted_with_day`" — einen aktiven Datei-Eintrag beim
Einspielen zu löschen, weil sein Zieltag gelöscht ist, wäre genau die stille
Nebenwirkung, die D1 ausschließt. Der Fall ist außerdem **nicht neu**: Er ist
in derselben Form schon vor S1 erreichbar. Er gehört entschieden, nicht
nebenbei behoben.

**Blockierend:** nein.

**Verbleib:** neuer Backlog-Eintrag in C7 (freie Nummer), zusammen mit F-S1-A.

*Weitere Funde während der Umsetzung hier eintragen (Fundort, Wirkung,
blockierend ja/nein, Verbleib → Backlog/Phase).*

## 9. Statuspflege und Rahmenplan-Berichtigungen

Nach jedem Arbeitspaket dieses Dokument fortschreiben (erledigt, Probleme,
Lösungen, Entscheidungen); am Paketende Prüfdokument nach K9.

Nach Abschluss von S1 im Projektraum (nicht durch die umsetzende Instanz):

1. Rahmenplan Statuszeile S1 → umgesetzt; P1-Zeile: P-08 geschlossen.
2. **Berichtigung:** R22 und der S1-Phasentext nennen eine
   „30-Tage-Frist" — richtig sind **90 Tage** (`TRASH_DAYS = 90`,
   B-S1-01). Ebenso die Erwartung „ältere Stände weisen v7-Dateien ab" —
   richtig ist: der Sprung kennzeichnet, sperrt aber ausgelieferte Stände
   nicht (E-S1-07).
3. R24-Sollstand: „nach S1 beide 0" → erreicht (mit Messzahlen).
4. R11: Abnahmedatei ist die neue Referenz-edbak aus C7.

---

## 10. Umsetzungsstand

Fortgeschrieben von der umsetzenden Instanz, ein Abschnitt je Arbeitspaket
(K-Regel aus CLAUDE.md 7). Version der Umsetzung: **Web 8.0.0** — die
Hauptnummer steigt, weil die Nutzlast der Sicherung eine andere geworden ist
(6 → 7). Sie wurde mit C1 gesetzt und bleibt über alle Pakete stehen; die
Phase liefert **eine** Fassung aus.

### Prüfumgebung

Die Umsetzung läuft gegen eine **frisch aufgebaute lokale
Referenzinstallation**, nicht gegen die Referenzinstallation aus P1 (die steht
dieser Instanz nicht zur Verfügung). Aufbau über die regulären Wege nach
`tools/referenzdatensatz/LIESMICH.md`, „Die drei Läufe":

| Schritt | Ergebnis |
|---|---|
| `quelldaten/pruefen.py` | 5680 Einzelprüfungen, 78 Matrixzeilen, 0 offen, keine Befunde |
| `generator/erzeugen.py` | 16 Dienste, 87 Einsätze, 100 Ruhesegmente, 56 587 erzeugte Spurpunkte |
| `generator/pruefen.py` | 283 985 Einzelprüfungen, keine Befunde |
| `einspielen.py` (alle Stufen) | 526 Ingest-Anfragen, 0 Fehler; Sperrlisten-Prüfschritt bestanden |
| `browser/csv_import.mjs` | 4 Einsätze, 0 Hinweise, 0 Fehler, 0 Konsolenfehler |

**Bestand danach, nachgezählt:** 87 Einsätze (5 im Papierkorb, davon 4 mit
`deleted_with_day = 1`), 100 Ruhesegmente (5 im Papierkorb, alle
`deleted_with_day = 1`), 16 Diensttage (1 im Papierkorb), 55 861 Spurpunkte.
Das ist exakt der in `tools/referenzdatensatz/LIESMICH.md` beschriebene
Referenzzustand — der Datensatz ist reproduzierbar, und damit ist die
Terminbindung aus Abschnitt 1 (Referenz vor dem 20.11.2026 neu ziehen)
gegenstandslos: C7 zieht sie aus einem **frischen Einspiellauf**, wie dort als
zweiter Weg vorgesehen. Folge nach E-S1-16: **Referenz-CSV und Referenz-edbak
werden beide neu erzeugt.**

### C1 — Sicherung vollständig (erledigt)

**Geändert:** `server/backup_lib.php` (`edbak_build()` ohne `$mitPapierkorb`,
die drei `deleted_at IS NULL`-Filter entfallen, Kopfkommentar neu geschrieben,
`version` 6 → 7 mit Begründung), `server/api/backup_restore.php` (Kommentar:
warum die Schranke bei 6 bleibt und warum der Sprung nicht sperrt),
`server/adminbackup_lib.php` (`umfang.papierkorb`),
`server/admin_sicherungen.php` (`umfang_text()` nennt den Papierkorbanteil),
`server/einstellungen.php` (Freigabe-Hinweis nennt ihn ebenfalls, samt
Fristbeginn), `tools/referenzdatensatz/fixture/erzeugen.php` (Aufruf ohne
Flag), `server/version.php` (8.0.0), `docs/Backup-Format.md` 2/4/5,
`docs/Handbuch.md` 6 und 6.1, `docs/CHANGELOG.md`.

**Entscheidungen, die dabei fielen:**

1. **Die Papierkorbzahl wird ausgeschrieben, nicht als `5/1/5` abgekürzt.**
   Die erste Fassung schrieb „davon im Papierkorb: 5/1/5" hinter eine Zeile,
   die „87 Einsätze, 16 Diensttage, 100 Ruhezeiten" nennt. Die Reihenfolge
   stimmte, lesbar war es nicht — drei Zahlen ohne Bezeichnung zwingen zum
   Rückwärtszählen. Jetzt steht dort „davon im Papierkorb: 5 Einsätze,
   1 Diensttag, 5 Ruhezeiten".
2. **Fehlt der Unterblock, wird nichts angezeigt statt einer Null.** Bei
   Sicherungen von vor S1 gibt es keine Papierkorbzahl. Eine Null behauptete
   „nichts im Papierkorb"; richtig ist „nicht erhoben".

**Fund F-S1-B (nebenbei behoben):** `docs/Technik.md` nannte in der
Zeitrechnungs-Tabelle eine „30-Tage-Frist" für den Papierkorb. Es sind 90
(`TRASH_DAYS`); alle übrigen Stellen der Dokumentation sagten das auch. Eine
Wortkorrektur, kein Backlog-Eintrag.

**Prüfstand C1**

| Was | Wie | Ergebnis |
|---|---|---|
| Nutzlast der eigenen Sicherung | Browser: anmelden → Einstellungen → Backup → „Backup erstellen", Datei entsiegelt und ausgezählt | `version` 7; 87/100/16, davon 5/5/1 im Papierkorb; 4 Einsätze und 5 Ruhesegmente mit `deleted_with_day = 1`; 85 Einsätze mit Klartext-`pat`; 0 Konsolenfehler |
| Nutzlast über die API | `GET api/backup_data.php` in angemeldeter Sitzung | dieselben Zahlen, `version` 7 |
| Admin-Sicherung | Adminbereich → Sicherungen → „Alle sichern", Übersicht gelesen | „87 Einsätze, 16 Diensttage, 100 Ruhezeiten, davon im Papierkorb: 5 Einsätze, 1 Diensttag, 5 Ruhezeiten, 2.483 KB"; leere Konten zeigen „nichts im Papierkorb" |
| Syntax | `php -l` über alle sieben berührten PHP-Dateien | fehlerfrei |

**Noch offen aus C1:** Die Annahme einer **Version-6-Datei** (Abnahmekriterium
und P-S1-10) lässt sich erst prüfen, wenn der Rückweg aus C2 steht — sonst
misst der Lauf den alten Rückweg. Der Prüfschritt liegt deshalb **nach C2 und
vor C7**, solange die eingecheckte Referenz-`.edbak` noch Version 6 ist.

### C2 — Rückweg: Papierkorb und `created_at` (erledigt)

**Geändert:** `server/backup_lib.php` (`edbak_restore()`: ein `$loeschZeit` je
Lauf, `$zieltagGeloescht`-Zuordnung, D1 in zwei Hälften, Löschzustand an
Diensttagen, Einsätzen und Ruhesegmenten, `created_at` als Ausnahmespalte,
neuer Grund `tag_uebersprungen`, Gründe der Ruhesegmente, Zähler
`stats.papierkorb`), `server/einstellungen.php` (ein gemeinsamer
Rückmeldungs-Baustein für beide Einspielwege, `require_once trash_lib.php` für
`TRASH_DAYS`), `docs/Backup-Format.md` 3 und 4, `docs/Handbuch.md` 8,
`docs/CHANGELOG.md`.

**Entscheidungen, die dabei fielen:**

1. **Ein Löschzeitpunkt für den ganzen Lauf, in PHP gerechnet.** Die
   naheliegende Fassung wäre `UTC_TIMESTAMP()` im SQL gewesen — so macht es
   `trash_lib.php`. Sie hätte je Zeile einen eigenen Zeitpunkt ergeben. Ein
   Einspielvorgang ist aber **ein** Vorgang; ein gemeinsamer Stempel ist die
   ehrlichere Angabe und macht die Prüfung „alle Einträge tragen den
   Einspielzeitpunkt" überhaupt erst scharf. Die Verbindung steht auf UTC
   (`db.php`), `gmdate()` liefert denselben Wert.
2. **Zwei Gründe statt einem, wo bisher `datum_oder_zeit` stand.** Nennt die
   Datei einen Diensttag, den es nach der Zuordnung nicht gibt, heißt der
   Grund jetzt `tag_uebersprungen`; nennt sie gar keinen, bleibt es beim
   Datumsproblem. Die Unterscheidung kostet eine Zeile und beantwortet die
   Frage, die man beim Lesen der Meldung tatsächlich hat.
3. **Ein Rückmeldungs-Baustein statt zweier.** Die freigegebene Sicherung
   hatte einen eigenen, kürzeren Text („übersprungen, weil bereits vorhanden
   oder unbrauchbar") — er nannte weder Standortdaten noch Höhenfehler noch
   einzelne Gründe. Zwei Texte für dieselbe Auskunft laufen auseinander; jetzt
   gibt es `restoreBericht()`.
4. **Zahlwörter.** „1 Diensttage" stand nach dem ersten Durchlauf im Browser
   und ließ den ganzen Satz nach Maschine aussehen. Die Meldung nennt Zahlen,
   die oft auf 1 stehen — eine kleine Hilfsfunktion behebt das an allen sieben
   Stellen.

**Fund:** F-S1-C (Abschnitt 8) — die Gegenrichtung der Invariante ist offen
und wird nicht in diesem Paket entschieden.

**Prüfstand C2**

| Nr. | Was | Wie | Ergebnis |
|---|---|---|---|
| P-S1-10 | Version-6-Datei wird angenommen | `kreislauf.py --art edbak --frisch` mit der eingecheckten Referenz (v6) in ein frisches Konto | 82 Einsätze, 95 Ruhesegmente, 15 Diensttage eingespielt; **269 439 Einzelvergleiche**, 15 erwartete Abweichungen, **1 unerklärte**: `kopf/version 6 → 7` — die Nutzlaststufung selbst, nach C7 gegenstandslos. 0 Konsolenfehler |
| — | Umlauf einer Version-7-Datei | dieselbe Mechanik mit der in C1 im Browser erzeugten v7-Sicherung, Zielkonto `umlauf-v7@gen-em.org` | 87/100/16 eingespielt, davon **5/5/1 in den Papierkorb**; **286 739 Einzelvergleiche**, 16 erwartete, **11 unerklärte** — ausnahmslos `deleted_at`-Zeitwerte (5 Einsätze, 5 Ruhesegmente, 1 Diensttag). Genau das, was E-S1-03 will und C6 normalisiert; alle elf tragen **denselben** Zeitwert, der gemeinsame Stempel greift |
| — | `deleted_with_day` nach dem Umlauf | SQL-Nachzählung im Zielkonto | 4 Einsätze mit `1`, 1 mit `0`; 5 Ruhesegmente mit `1` — identisch zum Referenzkonto |
| P-S1-05 | Invariante, SQL-Nachzählung | über **alle sieben** Konten der Prüfinstallation: `deleted_with_day = 1` an aktivem Tag; `deleted_with_day = 1` ohne `deleted_at` | **0 / 0 / 0** |
| P-S1-07 | `created_at` wörtlich | SQL, 87 Einsätze paarweise über `client_ref` zwischen Referenz- und Zielkonto verglichen | **87 gleich, 0 abweichend** (83 verschiedene Werte auf beiden Seiten) |
| P-S1-03 | D1, Fall Zielkonto | Browser: einspielen → beide Dienste des 28.03.2026 (Referenz führt zwei) in den Papierkorb, den zweiten endgültig löschen → dieselbe Datei erneut einspielen | „Übersprungen: 87 Einsätze, 100 Ruhesegmente — bereits vorhanden 174, **Diensttag liegt hier im Papierkorb 2**, **Diensttag wurde übersprungen 13**"; kein roher Schlüssel; 7 Einzelprüfungen, 0 Befunde, 0 Konsolenfehler |
| P-S1-04 | D1, Fall Datei | Browser: einspielen → Papierkorb ansehen → Diensttag wiederherstellen | Papierkorb zeigt **1 Diensttag mit 4 mitgelöschten Einsätzen** und **1 einzeln gelöschten Einsatz**; nach dem Wiederherstellen ist der Tag weg und der einzeln gelöschte Einsatz liegt **weiterhin** dort; 9 Einzelprüfungen, 0 Befunde |
| P-S1-05 | Zombie-Gegenprobe | Browser: einspielen → Papierkorb-Tag wiederherstellen (Tag ist aktiv) → einen seiner Einsätze endgültig löschen → dieselbe Datei erneut einspielen | der fehlende Einsatz kommt zurück und liegt **einzeln** im Papierkorb (2 Zeilen dort, 0 Tage), der Zieltag bleibt aktiv; 8 Einzelprüfungen, 0 Befunde |
| P-S1-06 | Frist beginnt neu | Rückmeldung im Browser | „In den Papierkorb übernommen: 5 Einsätze, 5 Ruhesegmente, 1 Diensttag — die 90-Tage-Frist beginnt für sie neu." Die Zahl 90 kommt aus `TRASH_DAYS`, nicht aus dem Text |

**Prüfmittel:** Die Browserschritte laufen über Playwright/Chromium gegen die
lokale Installation; die drei Prüfkonten entstehen über den regulären
Einladungsweg (`kreislauf.konto_anlegen`), das Passwort wird im Browser
gesetzt. Die Skripte liegen **nicht** im Repositorium — sie prüfen einen
einmaligen Übergang, und ein Prüfmittel ohne Pflege ist schlechter als keins.
Der Bedienweg steht im Prüfdokument, damit er von Hand nachvollziehbar ist.

### C3 — Demo-Nachlauf-Rückbau (erledigt)

**Geändert:** `server/demo_lib.php` (`demo_nachlauf()` samt beiden Aufrufen
entfernt, `require_once trash_lib.php` mit — die Datei braucht ihn nicht mehr),
`server/backup_lib.php` (Kommentar zur Verschachtelung: der Nachlauf steht
nicht mehr in der Klammer), `server/admin_demo.php` (Aufzählungspunkt neu
geschrieben, `require_once trash_lib.php` für `TRASH_DAYS`),
`tools/referenzdatensatz/fixture/erzeugen.php` (Format 2, kein `nachlauf`,
Papierkorb wird nur noch für die Ausgabe gezählt), `docs/Technik.md` 4.99a und
Verzeichnisbaum, `tools/referenzdatensatz/LIESMICH.md`, `docs/CHANGELOG.md`.

**Problem, das dabei auftrat — und wie es gelöst wurde:** `demo_pruefen.mjs`
legt ein Demo-Konto mit der Adresse `demo@gen-em.org` an. Auf der
Referenzinstallation **ist** das die Adresse des Referenzkontos, und
`demo_anlegen()` weigert sich (zu Recht) gegen ein bereits bestehendes Konto
dieser Adresse. Die Abnahme lief deshalb auf einer **zweiten, getrennten
Installation** (eigene Datenbank, eigener PHP-Server, Kopie von `server/`) mit
der **alten** Fixture aus dem Repositorium. Das ist zugleich die schärfere
Probe: Sie belegt B-S1-12 — eine Fixture der Version 1 bleibt unter dem neuen
Rückweg lauffähig.

**Prüfstand C3**

| Was | Wie | Ergebnis |
|---|---|---|
| Demo-Abnahme, ganzer Weg | `browser/demo_pruefen.mjs` gegen die zweite Installation, alte Fixture (Format 1): anlegen → geschützte Angaben lesen → absichtlich verändern → zurücksetzen → Identitätssperren | **24 Einzelprüfungen, 0 Befunde, 0 Konsolenfehler.** Nach dem Anlegen 15 Diensttage, 82 Einsätze, 95 Ruhesegmente, **5 im Papierkorb**, 3 Geräte; nach der Veränderung 81/6; nach dem Reset wieder 82/5 |
| Papierkorb kommt aus dem Einspielen | SQL in der zweiten Datenbank | **5 Einsätze, 5 Ruhesegmente, 1 Diensttag**, alle mit **einem** `deleted_at` (dem Einspielzeitpunkt); `deleted_with_day = 1` an aktivem Tag: **0** |
| Bericht ohne Nachlauf-Zähler | Adminbereich → Demo-Konto → „Auf Standard zurücksetzen", Bericht des Laufs gelesen | `papierkorb: {einsaetze: 5, diensttage: 1, ruhezeiten: 5}`; kein `nicht_gefunden`, kein Drehbuch-Zähler |
| Reset-Dauer (P-S1-11) | dieselbe Handlung, Zeit genommen | **6,4 s** — der Ausgangswert lag bei rund 6 s, also nicht schlechter |

### Zwischenfall: die Referenzinstallation musste neu aufgebaut werden

Gehört hierher, weil er Zahlen erklärt und weil die Ursache behoben ist.

**Was passiert ist.** `browser/demo_pruefen.mjs` wurde versehentlich gegen die
**Referenzinstallation** gefahren statt gegen die zweite. Das Skript arbeitet
auf dem Konto `demo@gen-em.org` — und auf der Referenzinstallation ist das
nicht das Demo-Konto, sondern das **Referenzkonto**. Es hat dort einen Einsatz
gelöscht, einen Standort angelegt und die E-Mail-Adresse in
`gekapert@example.org` geändert. Der Befund „E-Mail-Änderung wurde NICHT
abgewiesen" stand danach im Bericht — richtig gemeldet, aber zu spät.

**Folge.** Der Referenzstand wurde vollständig neu aufgebaut (Datenbank neu,
`install.php`, alle Einspielstufen, CSV-Import im Browser). Danach wieder exakt
87 Einsätze / 5 im Papierkorb, 100 Ruhesegmente / 5, 16 Diensttage / 1,
55 861 Spurpunkte — der Datensatz ist reproduzierbar, das ist die gute
Nachricht daran. Betroffen waren keine Messungen: C1 und C2 lagen davor, der
CSV-Kreislauf arbeitet mit den eingecheckten Referenz**dateien** und einem
frischen Konto.

**Fund F-S1-D — und hier wurde von K4 abgewichen.** Ein Prüfmittel, das seinen
Prüfling zerstören kann, braucht eine Grenze, die nicht davon abhängt, dass die
Bedienerin aufpasst. `demo_pruefen.mjs` hat deshalb einen **Riegel** bekommen:
Vor allem anderen prüft es, ob unter der Demo-Adresse ein Konto liegt, das
**nicht** als Demo-Konto gekennzeichnet ist — und bricht dann ab, ohne etwas zu
berühren.

Das ist eine bewusste Abweichung von K4 („Funde sammeln, nicht sofort
beheben"). Begründung: K4 schützt den Umfang des **Produkts**; hier geht es um
ein Werkzeug unter `tools/`, das nicht ausgeliefert wird, und der Fund ist
kein Fehlverhalten der Anwendung, sondern eine Falle im Prüfmittel, die
unmittelbar Daten kostet. Der Riegel ist geprüft: gegen die
Referenzinstallation bricht der Lauf ab und fasst nichts an, gegen die zweite
Installation läuft er durch (**25 Einzelprüfungen, 0 Befunde,
0 Konsolenfehler** — eine Prüfung mehr als vorher, das ist der Riegel selbst).

### C4 — CSV-Import: Nr. 27 und Nr. 28 (erledigt)

**Geändert:** `server/assets/import.js` (neuer Parser `trimMehrzeilig`, `final`
in `EINFACHE_ZIELE` und in der Zeilenvorgabe, neues Ergebnisfeld
`zielspalten`), `server/assets/import_profiles.js` (drei Notizspalten auf
`trimMehrzeilig`, `final` mit Ziel statt `target: null`),
`server/assets/import_ui.js` (`ended_utc` und `final` nur bei vorhandener
Spalte), `server/api/import_commit.php` (Vertragskommentar, `final` als
Platzhalter im INSERT und neu im UPDATE, `ended_utc` unterscheidet fehlend von
leer), `docs/Export-Format.md` 5.1 und 5.2, `docs/CHANGELOG.md`.

**Entscheidungen, die dabei fielen:**

1. **Die Unterscheidung „Spalte fehlt / Zelle leer" gehört in `import.js`,
   nicht in `import_ui.js`.** Nur `verarbeiteMatrix()` kennt die Kopfzeile der
   Datei. Es liefert deshalb ein neues Feld `zielspalten` (Menge der Zielfelder,
   für die die Datei eine Spalte führt); die Oberfläche fragt es nur ab. Eine
   zweite, von Hand geführte Liste wäre genau die Bauart gewesen, die Web 7.2.2
   schon einmal einen stillen Datenverlust gekostet hat.
2. **Vorgabe `final: 1` in der Zeilenvorlage.** Eine leere Zelle in einer
   Datei, die die Spalte führt, ist damit dasselbe wie „kein Wert genannt" —
   und nicht „nicht abgeschlossen". Das entspricht der Datenbank-Vorgabe und
   dem bisherigen Verhalten; die Gegenrichtung hätte einen ganzen Jahrgang
   stillschweigend auf „offen" gesetzt.
3. **`$gehoert` liefert jetzt `final` statt `1`.** Beim Überschreiben ohne
   `final`-Spalte muss das UPDATE den **bestehenden** Wert zurückschreiben. Der
   Test auf „keine Zeile" bleibt `=== false` — ein `final` von 0 ist ein
   gültiger Wert, kein Fehlschlag.
4. **Leerzeilen mitten in einer Notiz bleiben stehen.** Sie sind Gliederung;
   nur am Anfang und Ende fallen sie weg.

**Prüfstand C4**

| Nr. | Was | Wie | Ergebnis |
|---|---|---|---|
| P-S1-02 | Kreislauf CSV | `kreislauf.py --art csv --frisch` | **8797 Einzelvergleiche, 0 unerklärte Abweichungen** (vorher 6), 859 erwartete, 0 ungenutzte Regeln, 0 Konsolenfehler |
| P-S1-08 | Nr. 27 | Referenz-CSV und Umlauf-CSV Zeile für Zeile verglichen | 4 mehrzeilige Notizen auf beiden Seiten, **164/253/119/150 Zeichen, je 1 Umbruch, wörtlich gleich** |
| P-S1-09 | Nr. 28, Anlegen | dieselbe Messung, Spalten `final` und `ende` | Referenz `('2026-07-05','19:40','0','')` → Umlauf **identisch** |
| P-S1-09 | Nr. 28, Überschreiben | Browser: dieselbe CSV erneut ins gefüllte Umlaufkonto, **alle 82 Zeilen auf „überschreiben"** | 82 überschrieben; danach `final = 0` und `ended_at IS NULL` — die Fassung davor hätte beides gesetzt |
| — | Excel ohne `ende`/`final`, Anlegen | Browser: Excel (Standard) aus dem Referenzkonto exportiert, in ein frisches Konto importiert | 82 Einsätze, **82× `final = 1`, 82× `ended_at = started_at`** — genau das bisherige Verhalten, auch für den offenen Einsatz |
| — | Excel ohne `ende`/`final`, Überschreiben | dieselbe Datei ins gefüllte CSV-Umlaufkonto, alle 82 Zeilen überschreiben | `final` bleibt **0** (die Datei sagt nichts dazu), `ende` wird auf den Beginn gesetzt — unverändertes Verhalten, in `Export-Format.md` 5.2 jetzt ausdrücklich benannt |

### C5 — Doku-Ausnahmen: Nr. 24 und Nr. 29 (erledigt)

**Geändert:** `docs/Export-Format.md` 5.1 (Ausnahmeliste von drei auf sechs,
jede mit Messzahl; Überschrift „verlustfrei" → „verlustfrei **für Einsätze**")
und 6 (Papierkorb, mit dem Unterschied zur Sicherung), `docs/CHANGELOG.md`.

**Entscheidung, die dabei fiel:** Die Überschrift wurde eingegrenzt. Sechs
Ausnahmen sind kein Grund, „verlustfrei" zu streichen — der CSV-Weg ist
weiterhin der einzige, der Phasen, Reanimation und alle Einsatzfelder
zurückbringt —, aber einer, das Wort auf **Einsätze** zu beziehen. Backlog
Nr. 24 formuliert den Anstoß genau so: „`Export-Format.md` 5.1 sagt, es sei
verlustfrei."

**Der Apostroph wurde gemessen, nicht abgeschrieben.** Backlog Nr. 24 nennt
keine Zahl. Gezählt im Umlauf: **3 Zellen** (zwei `notizen`, ein `other_ema`)
tragen im Referenzexport einen führenden Apostroph; im Zielkonto steht er nach
dem Umlauf **im Wert** (SQL: 3 Treffer, im Referenzkonto 0). Der erneute Export
fügt keinen zweiten hinzu — deshalb sieht der Kreislauf die Abweichung nicht,
und deshalb gehört sie in die Dokumentation statt in die Ausnahmeliste des
Werkzeugs.

**Prüfstand C5**

| Was | Wie | Ergebnis |
|---|---|---|
| Zahlen der Ausnahmen | Bericht des CSV-Kreislaufs, nach Feld gruppiert | GPX 171, Ruhezeiten 95, `track_datei`/`track_punkte` je 76, Rettungsmittel 51 (Einsätze) und 8 (Diensttage), fehlende Diensttage 2 — alle sechs Ausnahmen mit Zahl belegt |
| Formelschutz-Apostroph | Referenz- und Umlaufarchiv ausgezählt, dazu SQL im Zielkonto | 3 Zellen in beiden Archiven mit führendem Apostroph; im **Bestand** des Umlaufkontos 3 Werte mit Apostroph, im Referenzkonto 0 — der Wert ist ein Zeichen länger geworden, die Datei sieht gleich aus |
| Widerspruchsfreiheit | 5.1 gegen `vergleich/ausnahmen/csv_umlauf.json` gelesen | keine Aussage in 5.1 ohne Entsprechung in der Ausnahmeliste; die Zahlen der Liste werden in C6 auf denselben Stand gebracht |
