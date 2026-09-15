# Konzept — Demo-Ausbau (Schritt 9d)

*Erstellt am 15.09.2026 (Fable) gegen `gen-em/einsatzdoku-luftrettung`, Zweig
`main`, Stand **Web 19.6.0** (`version.php`); Uhr und Android sind von diesem
Paket nicht berührt. Grundlage sind der Auftrag vom 14./15.09.2026 (drei
Rückfragen beantwortet, siehe Abschnitt 2) und die Messung am ZIP von `main`
(Abschnitt 1). Format nach K1; Prüfdokument nach K9 liegt als Vorlage daneben
(`Pruefdokument-Demo-Ausbau.md`).*

> | | |
> |---|---|
> | Paket | **Demo-Ausbau** — neuer Schritt **9d** des Fahrplans (Einschub in Anhang A) |
> | Ziel | Der Referenzbestand — und damit das Demo-Konto `demo@gen-em.org` — zeigt die drei Rettungsmittel-Typen aus S9 im Betrieb, ein Verlegungsfahrzeug, Schnitte vom Handy an mehreren Tagen und nachgetragene Einsätze mit Koordinaten, aber ohne Spur (gestrichelte Luftlinien). Standorte mit Rettungsmitteln tragen Koordinaten |
> | Umfang | **5 neue Diensttage** (D17–D21), **16 handgeschriebene Einsätze + 2 Schnitte** = 18 im Bestand (88 → 106), **4 neue Rettungsmittel**, **1 neuer Standort**, **1 Regeländerung in `server/`** (Fähigkeiten bei Typ Bergwacht auch bodengebunden) |
> | Nicht drin | Katalog-Erweiterung für erzeugten Betriebsalltag (`katalog.py`) · ein drittes Gerät · Umbau bestehender Diensttage · neue Einsatzfelder · Messstand-Lauf · das Demo-Passwort (P7, R25) · S11-Format (Backup Fassung 4) |
> | Modell | Opus (K2). **Keine Fable-Schritte.** Drei Haltepunkte (Abschnitt 3), einer davon eine **Zuarbeit des Auftraggebers** (OSRM-Routen, H-DA-1) |
> | Versionsnummer | legt die Umsetzung fest (K3). **Nur AP0 fasst `server/` an** (Rang nach Einschätzung der Umsetzung: Neben — sichtbare Regeländerung, **keine Migration**). AP1–AP4 liegen in `tools/`, `docs/` und `server/demo/fixture.json.gz` |
> | Voraussetzung | **S10 auf `main`, `update.php` gelaufen, S10-Prüfliste abgehakt** (E-DA-01). Der Zweig darf früher entstehen; **der Merge wartet auf die S10-Messung** |
> | Reihenfolge | AP0 → AP1 → AP2 → **H-DA-1** → AP3 → AP4. AP3 zuletzt vor dem Abschluss, weil es die Vergleichsgrundlage der Regressionspflicht (R24) ersetzt |

## Stand (von der Umsetzung geführt)

| | |
|---|---|
| **In Arbeit** | — |
| **Erledigt** | **AP0** (Web 20.3.0) · **AP1** · **AP2** · **H-DA-1** (selbst geholt) · **AP3** · **AP4** |
| **Offen** | Merge und Freigabe des Abschlusses (Prüfdokument, zehn Punkte) |
| **Haltepunkte** | **H-DA-1 entfällt** (OSRM ist erreichbar, E-DA-22) · **H-DA-2 gefahren** (E-DA-23) · **H-DA-3 nicht ausgelöst** — beide Kreisläufe melden 0 unerklärt |
| **Prüfstand** | lokal aufgebaut: MariaDB, PHP 8.4.19, TLS über socat, Playwright (Chromium/Firefox/WebKit), Node 22 |
| **Zweig** | `claude/umsetzung-ohne-pausen-2vfppr` |
| **Hakt es?** | nein |

**Auftragslage dieser Umsetzung.** Der Auftraggeber hat am 14.09.2026
angewiesen: *„Umsetzung bitte. Keine Pausen wenn nicht nötig — du darfst dich
über das Konzept hinwegsetzen."* Die beiden offenen Fragen F-DA-1 und F-DA-2
sind deshalb nach der Empfehlung des Konzepts entschieden (E-DA-24), und die
Haltepunkte werden nur dann gehalten, wenn ohne Antwort nicht weiterzuarbeiten
wäre. Jede Abweichung vom Konzept steht als eigene Entscheidung in Abschnitt 2
oder als Fund in Abschnitt 7 — keine wird stillschweigend gemacht.

---

## 0. Warum dieser Schnitt

Die Demo-Fixture ist auf dem aktuellen Spalten- und Verschlüsselungsstand
(Abschnitt 1.1). Was ihr fehlt, ist **Inhalt**: S9 hat mit Web 16.0.0 drei
Rettungsmittel-Typen eingeführt, und der Referenzbestand kennt sie nur als
Stammdaten — kein Diensttag, kein Einsatz. Wer das Demo-Konto öffnet, sieht
davon nichts; und das Regressionsnetz (R24) prüft davon nichts. Beides ist
derselbe Mangel, und er lässt sich nur an einer Stelle beheben: in den
Quelldaten unter `tools/referenzdatensatz/quelldaten/`.

Der Zeitpunkt ist nicht beliebig. Nach S10 kommen P5 (Demo-Konto „in jeder
Betriebsart mitdenken", Staging mit Referenzdatensatz), P6 (Review prüft die
Demo-Konstruktion) und S11 (verschlüsselt genau die Felder, die dieser Bestand
trägt: Spur, Phasenkoordinaten, Reanimationen, Zielklinik). Diese Schritte
sollen gegen einen Bestand messen, der die S9-Typen enthält — nicht gegen
einen, dem sie fehlen. Und **während** S10 darf der Bestand nicht wandern: S10
nimmt den „Umstellungslauf mit dem Referenzbestand" als Abnahme.

Der Bestand wird ohnehin noch mehrfach neu gebaut (S11: Fassung 4; P7: neues
Demo-Passwort und `app`-Name; P8: Neuaufsetzen, R11). Der Neubau ist billig
(vier Minuten plus Kreisläufe). Was kostet und jeden Formatwechsel überlebt,
ist der Inhalt — deshalb entsteht er jetzt, einmal, als handgeschriebene
Prüffälle.

Ein Punkt sprengt den Rahmen „nur `tools/` und `docs/`": Ein Bergwachtnotarzt
am Boden braucht die Winde, und E29 erlaubt Fähigkeiten ausschließlich an
luftgebundenen Rettungsmitteln (`validate_lib.php`, `pruef_rettungsmittel()`).
Das ist eine Regeländerung in `server/` — klein, ohne Migration, aber sie
gehört als **AP0** vor alles andere, weil der Einspielweg sonst die
Fähigkeiten still verwirft und kein Bergwacht-Einsatz seine Windenfelder
bekommt.

---

## 1. Befund — gemessen am 15.09.2026

Jede Zahl ist am ZIP von `main` gemessen. Die Umsetzung misst vor AP1 erneut
(H-DA-2).

### 1.1 Die Fixture ist aktuell, der Inhalt nicht

`server/demo/fixture.json.gz`: Format `einsatzdoku-demo-fixture` Fassung 2,
erzeugt 12.09.2026 auf Web 19.3.0, `kdf_iter` **600 000** (= `KDF_ITER_ZIEL`),
Nutzlast **7**, 88 Einsätze mit `geraet_art`/`geraet_modell`, ohne
`site_desc`- und `notes`-Spalte (beide im `pat_blob`). Seit 19.3.0 kamen nur
19.4.0–19.6.0 (Mockup-Runde, keine Migration). Die Referenzdateien unter
`referenz/` stammen vom 13.09.2026 (Runde 3, Nr. 173/174). **Es gibt also
nichts „auf Stand zu bringen"** — die Aufgabe ist Inhalt.

### 1.2 Was der Bestand zeigt und was nicht

| | Ist | Fehlt |
|---|---|---|
| Standorte | 2: Hochkreuth (47.7255 / 10.314), **Talwang ohne Koordinaten** (`lat: null`; die Bodendienste tragen `spur_ausgangspunkt` 47.559 / 10.217) | Talwang mit Koordinaten; ein Standort für den Bergwachtnotarzt |
| Rettungsmittel | 6 — Alpenfalke 1, Alpenfalke 2 (Luft, Standard), NEF Talwang 76/1 (Boden, Standard); **Bergwacht Hochkreuth** (Bergwacht, Luft, mit Standort), **Sanitätsdienst Seefest** (Veranstaltung, ohne Standort), **Reserve Talwang** (Sonstiges, ohne Standort) | Diensttage an den drei Typ-Rettungsmitteln: **null**. Alle 16 Diensttage hängen an Alpenfalke 1/2 (8) und NEF Talwang (8) |
| Herkunft (`origin`) der 87 Quelldaten-Einsätze (aus den JSON-Dateien gezählt, nicht aus dem Matrix-Abgleich — der zählt Marken) | watch 39 · android 39 · wear 3 · manual 2 · import 4; dazu 1 Schnitt `cut-` | Schnitte an mehr als einem Handy-Tag; nachgetragene Einsätze mit Ort- **und** Zielkoordinate an einem Standort, der selbst Koordinaten hat |
| Schnitte | genau **einer** (D08, `ar-12-1288367401`); `pruefen.py` erzwingt `schnittzahl == 1` (E-R64-16) | mehrere Schnitte an mehreren Handy-Tagen |
| Sekundärtransport | 3, alle Luft (D02, D03, D07) | bodengebunden (Verlegungsfahrzeug) |
| Diensttag ohne Standort | seit Web 16.0.0 möglich (`index.php` `vehicleBaseSync()`, `api/day.php` „ohne Standort keine Vorschläge") — **im Referenzbestand nie eingespielt** | ein Tag mit `days.base_id = NULL`, Transportziel ad hoc |
| Fähigkeiten | `pruef_rettungsmittel()`: `caps` nur bei `kind === 'air'` (E29); `pruef_tagesrettungsmittel()` teilt die Regel; die Einsatzfelder Winde/Bergwacht hängen am **`cap_gate`** des Diensttags, nicht an der Art (`mission_fields.php`) | Winde an einem bodengebundenen Bergwacht-Rettungsmittel |
| Matrix | 83 Zeilen in `pruefen.py` `MATRIX`, 0 offen (`matrix_abgleich.md`, erzeugt) | ~10 neue Zeilen (Abschnitt 4, AP1) |

### 1.3 Werkzeugkette — was sie kann und was nicht

- **Quelldaten** (`quelldaten/dienste/D01…D16.json`, `stammdaten.json`,
  `geraete.json`): JSON ist die Quelle (E-P1-04); `aufbauen.py` füllt Dienste
  bis `ziel_einsaetze` aus `katalog.py` auf und **baut die Ruhesegmente
  immer neu**. Der Katalog kennt Luft- und Bodenalltag, keine Bergwacht- und
  Veranstaltungsinhalte.
- **`pruefen.py`** bindet jeden Dienst an einen Standort
  (`dn["standort"] in standort`, `rm["standort"] == dn["standort"]`), verlangt
  `spur_ausgangspunkt` nur bei einem Standort ohne Koordinaten, prüft
  Transportziel, Bereitschaft und weitere Rettungsmittel gegen die Listen
  **des Standorts** und zählt `schnittzahl == 1`. Strukturzeile
  „≥ 2 Standorte, **einer ohne Koordinaten**" (Zeile 791–793).
- **Generator** (`generator/`): Lufttracks geometrisch (`spur.flug`),
  Bodentracks aus **eingecheckter OSRM-Geometrie** (`generator/routen/`, 89
  Dateien, Hash des Koordinatenpaars). Neue Bodenstrecken holt
  `routen_holen.py` bei `router.project-osrm.org` — **dieser Host ist im
  Sandkasten der Umsetzung nicht freigegeben** (Netzliste). Einen Fußweg
  kennt der Generator nicht (kein `spur.fussweg`). Höhen kommen aus
  `gelaende.py` (Stützpunkte, kein Netz).
- **Einspielen** (`einspielen/einspielen.py`): Stufen `konto, stammdaten,
  geraet, ingest, zuordnen, nachtragen, manuell, papierkorb, sperrliste,
  schneiden`; Stufe `zuordnen` postet `base_id = ids["standorte"][dn["standort"]]`
  — ein Tag ohne Standort ist nicht vorgesehen; Rettungsmittel mit
  `ohne_standort` gehen mit `base_id: 0` hinaus (seit 13.09.2026, Nr. 174).
  `nachtragen` postet `f_transport_dest_lat/lon` bei einem Ziel mit
  Koordinaten — der Weg für ein Transportziel ad hoc (S9 PS-7) besteht.
- **Fixture** (`fixture/erzeugen.php`): bricht ab, wenn das Demo-Konto nicht
  auf `KDF_ITER_ZIEL` steht (Nr. 155). Dateikopf warnt: **Hintergrundjobs
  vorher anhalten** (12.09.2026: 8285 Spurpunkte ausgedünnt).
- **Vergleich** (`vergleich/kreislauf.py`, `ausnahmen/*.json`): zuletzt
  edbak 287 687 / 0 unerklärt / 16 erwartet / 0 ungenutzt; csv 9 120 / 0 /
  1 021 / 0 (Runde 3, AP8).

### 1.4 Feste Zahlen des Bestands im Repositorium

Stellen, die den **heutigen** Bestand beschreiben und nachziehen (AP4):
`tools/referenzdatensatz/LIESMICH.md` („Der Bestand in Zahlen", Ordnertabelle),
`quelldaten/FORMAT.md` (Zeilen 32 und 127: 16/87/88, „höchstens an einem
Dienst"), `einspielen/einspielen.py` Kommentar „2 von 6 ohne Standort",
`fixture/erzeugen.php` Kommentar „55 861 Spurpunkte", `browser/papierkorb_misch.mjs`
Kommentare „Uebersprungen: 87 Einsaetze, 100 Ruhesegmente", `docs/Technik.md`
Zeile 429 (Verzeichnisstruktur: „88 Einsätze"), `server/betrieb_statistik.php`
Kopfkommentar („88 erfundene Einsätze"), `server/assets/missiontable.js`
Kommentar („Referenzbestand 88 Einsaetze").

Stellen, die **Messprotokoll** sind und bleiben (nicht anfassen):
`server/version.php` (Changelog-Prosa), `docs/Backup-Format.md` („Gemessen am
Referenzbestand"), `tools/messstand/*` (Ausgangsmessung 28 KB je Einsatz),
`docs/konzepte/Konzept-R64-Herkunft-Geraet.md`, `Vorbereitung-S5-*.md`,
`server/spur_lib.php` (Kommentar zur Ausdünnstufe), `server/admin_sicherungen.php`
(Beispieltext). Die Abdeckungsmatrix in `docs/konzepte/erledigt/Konzept-P1.md`
Abschnitt 5 ist **eingefroren** (R62: der Bestand bis S3 wird nicht mehr
fortgeschrieben) — die lebende Matrix ist `pruefen.py` `MATRIX`, ihr Abgleich
`matrix_abgleich.md` (erzeugt).

---

## 2. Entscheidungen

Antworten des Auftraggebers vom 15.09.2026 sind als solche gekennzeichnet.

| Nr. | Entscheidung |
|---|---|
| **E-DA-01** | **Ort im Fahrplan: Schritt 9d, nach dem S10-Merge, parallel zum P5-Konzept.** Voraussetzung: S10 auf `main`, `update.php`, S10-Prüfliste. Grund: S10 nimmt den Referenzbestand als Messlatte; ein wandernder Bestand macht die Messung unscharf. Der Zweig darf vorher entstehen (AP0 fasst `einstellungen.php` an, das S10 ebenfalls schreibt — deshalb nicht vor dem Merge), gemergt wird nach der S10-Messung. *(Auftraggeber: „Passt.")* |
| **E-DA-02** | **Umfang.** 5 neue Diensttage D17–D21; 16 handgeschriebene Einsätze plus 2 Schnitte, also 18 im Bestand (88 → 106); Quelldaten zählen 87 → 103. Zielwerte — die Umsetzung darf um ±2 Einsätze abweichen, wenn ein Prüffall es verlangt, und schreibt die Abweichung nach Abschnitt 8. |
| **E-DA-03** | **Neue Tage, keine umgebauten.** Die 16 bestehenden Diensttage bleiben inhaltlich unverändert (Ausnahme: `spur_ausgangspunkt`, E-DA-09). **Alle neuen Tage liegen vor dem 27.12.2026** — D16 bleibt der neueste Diensttag, weil `index.php` ihn öffnet, `sichtpruefung.mjs` und der Bilderlauf auf seine erste Einsatzzeile greifen und kein Schnitt am neuesten Tag liegen darf. *(Auftraggeber: „Neue Tage.")* |
| **E-DA-04** | **Veranstaltungen laufen abends nach einem anderen Dienst am selben Kalendertag**, ohne zeitliche Überschneidung (R57 meldet Überschneidungen). *(Auftraggeber.)* |
| **E-DA-05** | **Bergwachtnotarzt = neues Rettungsmittel** `Bergwachtnotarzt Sonnenau`, Typ **Bergwacht**, Betriebsart **Boden**, Kurzname `BW-NA`, eigener Standort mit Koordinaten (E-DA-09), Fähigkeiten **Winde und Bergwacht**. Der Einsatz mit Polizeihubschrauber und Winde führt den Hubschrauber **nur in der Notiz**, nicht als weiteres Rettungsmittel; die Windenfelder (`winch`, `winch_cycles`, `winch_cycles_pat`, `winch_airload`) sind gesetzt. *(Auftraggeber: „ist quasi auch ein neues Rettungsmittel … braucht also auch Windenoption".)* Das bestehende „Bergwacht Hochkreuth" (Luft, Prüffall E-S9-09) bleibt unangetastet und ohne Diensttag. |
| **E-DA-06** | **Regeländerung: Bei Typ Bergwacht sind Fähigkeiten in beiden Betriebsarten zulässig** (Winde, Bergwacht). Das ist eine benannte Ausnahme zu E29 („Fähigkeiten ausschließlich an luftgebundenen Rettungsmitteln") und ergänzt die Tabelle in E-S9-09, Zeile Bergwacht, Spalte Fähigkeiten: „nach Betriebsart (Luft: …)" → „**Winde und Bergwacht anhakbar, in beiden Betriebsarten**". Gilt für `pruef_rettungsmittel()` **und** `pruef_tagesrettungsmittel()` (eine Regel, zwei Aufrufer — so, wie `pruef_typ_betriebsart()` es vorsieht), für das Stammdatenformular (`stammdaten_ui.php`, Häkchen sichtbar) und die Formularlogik in `einstellungen.php`. Standard bleibt bei E29; Veranstaltung bleibt ohne; Sonstiges: F-DA-2. Begründung: Die Einsatzfelder hängen am `cap_gate` des Tages und nicht an der Art (`mission_fields.php`, Kopfkommentar) — die Regel war die einzige Stelle, die Boden und Winde koppelte, und sie beschreibt den Bergwachtnotarzt falsch: Er fährt hin und wird geflogen. |
| **E-DA-07** | **VEF Talwang 76/2** — Typ Standard, Betriebsart Boden, Standort Notarztstandort Talwang, Rollen `driver`, `other`, keine Fähigkeiten, Kurzname `VEF`. Ein Diensttag mit **2 Sekundärtransporten** (`secondary = 1`, `na_escort = 1`, Ziel mit Koordinate) und **1 Primäreinsatz**, dazu ein Schnitt. *(Auftraggeber.)* |
| **E-DA-08** | **Zwei Veranstaltungs-Rettungsmittel** `Boxkampf Rainer Maria Rilke` (Kurzname `Boxkampf`) und `Konzert von Karl Marx` (Kurzname `Konzert`), Typ Veranstaltung, **ohne Standort** (`base_id NULL`; die Karte, aus der das Formular kommt, bleibt Hochkreuth — Muster Seefest). Ihre Diensttage sind **Tage ohne Standort**: keine Vorschlagslisten, keine Rollen, **Transportziel ad hoc als Freitext mit Koordinaten** (`f_transport_dest_lat/lon`). Die Namen sind historische Personen als Veranstaltungstitel — E-P1-02 verbietet reale **Rufnamen und Orte**; `VERBOTENE_NAMEN` bleibt unverändert, und `pruefen.py` läuft ohne Ausnahme durch. *(Auftraggeber.)* |
| **E-DA-09** | **Standorte mit Koordinaten.** `Notarztstandort Talwang` bekommt **exakt** `lat 47.559, lon 10.217` — den bisherigen `spur_ausgangspunkt` seiner acht Bodendienste; damit bleiben deren Spuren byteweise gleich (`basis_von()` liest denselben Wert). Der Eintrag `spur_ausgangspunkt` **entfällt** an D04, D06, D08, D10, D11, D13, D14 und D16, und `pruefen.py` bekommt die Gegenregel: `spur_ausgangspunkt` ist nur zulässig, wenn der Standort keine Koordinaten hat **oder** der Tag keinen Standort hat — ein Ausgangspunkt neben einem Standort mit Koordinaten wäre eine zweite Wahrheit. Neuer Standort **`Bergwachtstation Sonnenau`** mit `lat 47.532, lon 10.287` (ein Stützpunkt aus `gelaende.py`, 780 m), `standard: false`. **Rettungsmittel ohne Standort bleiben ohne** — die Abdeckung aus Nr. 174 wächst von „2 von 6" auf **„4 von 10"**. *(Auftraggeber: „Standorte aller Rettungsmittel haben GPS-Daten.")* Die Strukturzeile „einer ohne Koordinaten" entscheidet F-DA-1. |
| **E-DA-10** | **Vorbelegungen am neuen Standort** (Stammdaten sind je Standort, E15): Zielkliniken `Bergklinik Sonnenau` (47.559 / 10.217), `Unfallklinik Felsberg` (47.681 / 11.202), `Kreisklinik Steinach` (47.515 / 10.281); Bereitschaften `Bergwacht Sonnenau`, `Bergwacht Felsgrat`, `Bergwacht Moosachtal` (dieselben Namen wie in Hochkreuth — Namen sind je Standort, Dubletten über Standorte sind zulässig, siehe Kreisklinikum Auwiesen); weitere Rettungsmittel `RTW Talwang 76/83`, `First Responder Sonnenau`. **Keine Besatzungs-Vorbelegungen** — Typ Bergwacht hat keine Rollen-Vorlagen (E-S9-09). Talwang bekommt zusätzlich `Universitätsklinikum Nordstadt` (48.4011 / 9.9876) als Zielklinik — das Verlegungsziel. |
| **E-DA-11** | **Mehrere Schnitte.** `pruefen.py`: `schnittzahl == 1` → `schnittzahl == Anzahl der schnitte-Einträge` **und** `≥ 1`; die vier Randbedingungen je Schnitt bleiben. Jeder Schnitt liegt an einem **Handy-Tag** (Ruhesegment `ar-12-…`), keiner am neuesten Tag. `FORMAT.md` „höchstens an einem Dienst" → „beliebig viele, je Schnitt ein Objekt in der Liste `schnitte`". Die Sperrvermerke der Schnitte gehen wie bisher in die Sicherung (Nutzlast 9, Nr. 63). |
| **E-DA-12** | **Herkunftsmix der 16 neuen Einsätze:** `android` 10, `wear` 1, `manual` 5, `import` 0 — dazu 2 Schnitte (`cut-`). Nach dem Ausbau in den Quelldaten: watch 39, android 49, wear 4, manual 7, import 4 (= 103); im Bestand dazu 3 Schnitte (= 106). Ein `wm-`-Einsatz steht nur an einem handgeschriebenen Prüffall (FORMAT.md). |
| **E-DA-13** | **Fußwege im Generator.** Neuer Wegpunkt **`zustieg`** (Koordinate in `spur.zustieg`: Parkplatz, Talstation, Hüttenzufahrt). Die Teilstücke `zustieg → ort` und `ort → zustieg` werden **zu Fuß** gezeichnet: neue Funktion `spur.fussweg(von, nach, t0, t1, saat)` — geometrisch wie `spur.flug`, mit Gehgeschwindigkeit (Zielwert 2–4 km/h, aus Zeitfenster und Distanz), stärkerem Zittern, Höhen aus `gelaende.py`. Alles andere fährt (OSRM) oder fliegt. **Kein fremder Dienst** (E-P1-03). `wegpunkte.py` löst `zustieg` auf und `pruefen.py` prüft ihn wie jeden Wegpunkt. |
| **E-DA-14** | **Nur handgeschriebene Prüffälle.** Die neuen Tage tragen `ziel_einsaetze` = Zahl ihrer Einsätze; `aufbauen.py` fügt nichts hinzu und baut nur die Ruhesegmente. Jeder Einsatz trägt `$warum`. Eine Katalog-Erweiterung für Bergwacht- und Veranstaltungsalltag ist nicht Teil dieses Pakets. |
| **E-DA-15** | **Referenz und Fixture aus einem Lauf** (Gerätekennungen und `created_at` entstehen je Lauf neu — zwei Läufe ergäben eine Fixture, deren Kreislauf gegen die Referenz Geräte-Abweichungen meldet). Reihenfolge: Jobs anhalten → einspielen → `referenz_export.mjs` → `fixture/erzeugen.php`. Beide Dateien unter `referenz/` werden **ersetzt** (Datum im Namen, Verweise in LIESMICH und `kreislauf.py` ziehen nach); `referenz/altformat/` bleibt (Nr. 46). |
| **E-DA-16** | **Zahlen.** Die Stellen aus 1.4, Absatz 1, werden nachgezogen; die aus Absatz 2 bleiben Messprotokoll. `matrix_abgleich.md` wird erzeugt, nicht editiert. Konzept-P1 Abschnitt 5 bleibt eingefroren. |
| **E-DA-17** | **Geräte bleiben zwei.** Bergwachtnotarzt, VEF und beide Veranstaltungen dokumentieren mit **Handy 12** (`ad-12-`, `am-12-`, `ar-12-`, `wm-12-`); die Konzert-Einsätze werden **von Hand nachgetragen** (`kanal: formular`), das Handy lief mit und liefert nur Ruhesegmente. Ein drittes Gerät änderte „2 Geräte" an zu vielen Stellen ohne Gewinn für die Abdeckung. |
| **E-DA-18** | **Messstand läuft nicht** (R35): kein Mengenpfad ändert sich; die Zahlen in `tools/messstand/` sind Messprotokoll gegen den Bestand vom 04.09.2026 und werden mit einem Satz in der Messstand-LIESMICH als solches gekennzeichnet („die Referenz ist seit dem Demo-Ausbau größer; die Kennzahlen je Einsatz gelten weiter"). |
| **E-DA-19** | **Rang und Buchführung.** Versionsstufe nur mit AP0 (`version.php`), Changelog-Prosa je Paket mit Begründung, **eine neue Backlog-Nummer** für die Regeländerung E-DA-06 (die Umsetzung vergibt sie nach dem Kopf von `Backlog.md`; höchste vergebene Nummer am 15.09.2026: **186**) und ein Rahmenplan-Einschub nach Anhang A. Kommentarregel: Grund ja, Nummer nein (R69) — das gilt für den neuen Code in `validate_lib.php`. |
| **E-DA-20** | **Klickprobe und Bilderlauf** laufen gegen den neuen Bestand. Beide greifen auf den neuesten Tag (unverändert D16); die Klickprobe zählt Rettungsmittel ohne Standort (Soll **4**) — die Zahl steht in `tools/klickprobe/` nach, falls sie dort fest ist (Umsetzung misst). |
| **E-DA-21** | **Die Fähigkeitsregel wird eine SPALTE, keine zweite Bedingung** (AP0, Umsetzung). Das Konzept sagt „`$kind === 'air'` **oder** `$typ === 'bergwacht'`". Ausgeführt ist statt dessen `VEHICLE_TYPEN[...]['faehigkeiten']` (`'luft'` \| `'immer'`) plus `veh_caps_erlaubt()`. Grund: Die Regel wird an **vier** Stellen gebraucht — Prüfschicht, Rückspielweg der Sicherung, Markup des Dialogs und dessen Skript. Eine Bedingung an einer Stelle hätte an den drei anderen abgetippt werden müssen; genau daran ist die alte Fassung gescheitert (siehe F-DA-2 und F-DA-3 in Abschnitt 7). Der Kopfkommentar in `db.php`, der die fehlende Spalte begründete, ist mit der Begründung ersetzt, warum es sie jetzt gibt. |
| **E-DA-22** | **H-DA-1 entfällt: Die Umsetzung holt die Strecken selbst.** Gemessen am 14.09.2026: `https://router.project-osrm.org/route/v1/driving/…` antwortet in dieser Sitzung mit **HTTP 200** und einer brauchbaren Geometrie. Die Annahme des Konzepts („dieser Host ist im Sandkasten der Umsetzung nicht freigegeben") gilt hier nicht. Der Haltepunkt wird deshalb nicht ausgelöst; die neuen `strecke_*.geojson` werden wie bisher **eingecheckt** (E-P1-03), und `routen_holen.py` bleibt unverändert der Weg dorthin. |
| **E-DA-23** | **H-DA-2 gefahren, eine Abweichung — und zwar die erwartete.** Gemessen am 14.09.2026 auf `main`: `pruefen.py` meldet 16 Dienste, **87 Einsätze**, 100 Ruhesegmente, 1129 Zeitstempel, 5961 Einzelprüfungen, **83 Matrixzeilen / 0 offen**, 1 Schnitt — alles wie im Befund. Die Fixture steht auf Fassung 2, `kdf_iter` 600 000, Nutzlast 7, 88 Einsätze, erzeugt auf Web **19.3.0**. Abweichend ist allein der **Stand der Anwendung**: nicht Web 19.6.0, sondern **20.2.1** — S10 ist inzwischen gemergt (`98d677d`). Das ist keine Störung, sondern die Voraussetzung E-DA-01, die damit **erfüllt** ist. Die Umsetzung fährt weiter; die Versionsstufe rechnet ab 20.2.1. |
| **E-DA-25** | **Die Fensterableitung zieht nach `wegpunkte.py`** (AP1, Umsetzung). Bis dahin stand sie zweimal: `erzeugen._fenster()` und `pruefen.bewegungsfenster()`. Der Kommentar dort begründete das mit „die Regel selbst ist kurz". Sie war es — mit dem Wegpunkt `zustieg` sind es vier Teilstücke bei drei Phasenfenstern, und die Zuteilung passt in keine sechs Zeilen. Zwei Fassungen hießen, dass das Prüfskript die Erreichbarkeit eines **anderen** Ablaufs misst als den, den der Generator zeichnet — und dass beide dabei Erfolg melden. `wegpunkte.py` ist das Modul, das es für genau diesen Zweck schon gibt („eine Stelle für zwei Leser"); die Abhängigkeit, die der alte Kommentar vermeiden wollte, entsteht dabei nicht, weil `quelldaten/` nichts aus `generator/` kennt. Dazu ziehen `epoche()` und `ist_fussweg()` mit. |
| **E-DA-26** | **Der neue Standort Talwang ändert 36 erzeugte Einsätze — und das bleibt so.** Gemessen in AP1: `aufbauen.py` setzt an den acht Talwang-Bodendiensten `start_src` von `null` auf `"base"`, weil der Abfahrtort „Standort" dort seit E-DA-09 zur Auswahl steht. Das widerspricht dem Wortlaut von E-DA-03 („die 16 bestehenden Diensttage bleiben inhaltlich unverändert"), und die Umsetzung nimmt es trotzdem an: Es ist die **ehrliche Folge** der Koordinaten. Sie zu unterdrücken hieße, an einer Stelle so zu tun, als hätte Talwang keine — also genau die zweite Wahrheit, die E-DA-09 abschafft. **Was dabei nicht wandert:** Spuren, Phasen, Payloads, GPX, CSV und Sendeplan sind byteweise dieselben (`diff -r`, gemessen). Nebeneffekt, der dem Bestand nützt: Die Matrixzeile „Abfahrtort Regel base" ist jetzt auch bodengebunden belegt, nicht nur in der Luft. |
| **E-DA-27** | **`einspielen/demo_kennzeichnen.php` ist neu, und der Aufbau hat ohne es eine Lücke** (AP3, Umsetzung). Seit S10 überspringt `api/kdf_upgrade.php` die stille Umstellung auf `edka1:` nur für das Konto, auf das `app_state.demo_user_id` zeigt. Die LIESMICH sagte deshalb „das Demo-Konto steht vor den Browserläufen" — nur ließ sich das nicht ausführen: Der Adminbereich kann ein Demo-Konto erst anlegen, wenn eine **Fixture** vorliegt, und die entsteht am Ende. Das Skript vermerkt die Kontonummer unmittelbar nach `--stufen konto` und setzt die Reset-Marke einen Tag in die Zukunft (sonst räumt der erste Seitenaufruf den halb aufgebauten Bestand weg). Es benutzt `demo_lib.php` und `db()`, kein eigenes SQL, und es kann **kein** Demo-Konto anlegen — das braucht die Fixture. |
| **E-DA-28** | **Zwei Abweichungen im Kreislauf werden normalisiert, nicht in die Ausnahmeliste geschrieben** (AP3, Umsetzung). Der edbak-Umlauf meldete 12 unerklärte Abweichungen: paarweise vertauschte `base_ref` in `bw_units` und `resources` (zwei Bereitschaften gleichen Namens an verschiedenen Standorten) und zwei Reanimations-Ereignisse derselben Minute in der CSV-Spalte `rea_json`. Beides sind **Ordnungen, die die Datenbank nie zugesagt hat**. Eine Ausnahme dafür hätte jede künftige echte Abweichung an derselben Stelle mitverschluckt; `normalisieren.py` sortiert sie deshalb weg, und drei neue Selbstproben belegen, dass ein geänderter **Wert** weiterhin gemeldet wird (Hinprobe) und eine Umordnung nicht (Gegenprobe). |
| **E-DA-29** | **Der Bilderlauf bekommt drei Seiten, und sie suchen ihren Tag über den Inhalt** (AP3, Umsetzung). Die Abnahme verlangt Bilder von „Diensttag D20, Einsatzansicht D19/1, Karte D21". Die Platzhalter des Werkzeugs nehmen aber den **ersten** Diensttag der Übersicht — der hat einen Standort, eine Spur und keine Winde. Ohne neue Seiten hätte der Lauf drei Bilder des Regelfalls unter dem Namen des Sonderfalls geliefert, und genau davor warnt die eigene LIESMICH dreimal (F-P3-AH, F-P3-AQ, O11). Die drei neuen Platzhalter fragen deshalb `api/day.php`: kein `base_name`, keine Spurpunkte, `winch` an einem Bergwachttag am Boden. Kennungen oder Namen wären ein zweiter Ort, an dem die Quelldaten stehen — sie wandern bei jedem Neubau. Findet sich der Fall nicht, bleibt der Platzhalter `null` und die Seite wird **nicht** fotografiert. |
| **E-DA-30** | **Die Referenz wird nach der Messung des Demo-Resets wiederhergestellt, nicht neu eingespielt** (AP3, Umsetzung). Die Messung zu Backlog Nr. 76 braucht `demo_zuruecksetzen()` mit **beiden** Fixtures, alt und neu — sie überschreibt den Bestand also mehrfach. Wiederhergestellt wird er durch einen letzten Reset mit der neuen Fixture, und das ist zugleich eine Probe: Danach stehen wieder **106 Einsätze, 21 Diensttage, 119 Ruhesegmente und 63 752 Spurpunkte** in der Datenbank — dieselben Zahlen wie vor der Messung. Ein Fixture-Umlauf, der nichts verliert, ist mehr wert als ein zweiter Einspiellauf von vier Minuten. |
| **E-DA-24** | **F-DA-1 und F-DA-2 sind nach der Empfehlung des Konzepts entschieden** (Auftrag „keine Pausen"): **F-DA-1 → (a)** — die Strukturzeile „≥ 2 Standorte, einer ohne Koordinaten" wird zu **„≥ 1 Diensttag ohne Standort mit `spur_ausgangspunkt`"**; sie prüft damit den Fall, den die Anwendung seit Web 16.0.0 wirklich hat, statt einen, den der Auftrag abschafft. **F-DA-2 → nein** — der Typ Sonstiges bleibt bei E29. Beide Antworten stehen im Prüfdokument, Abschnitt 1, als „von der Umsetzung entschieden" und sind beim Abschluss zu bestätigen. |

---

## 3. Offene Fragen und Haltepunkte

### 3.1 Fragen — vor AP1 zu entscheiden (K6)

| Nr. | Frage | Empfehlung |
|---|---|---|
| **F-DA-1** | E-DA-09 gibt Talwang Koordinaten; damit fällt die Strukturzeile **„≥ 2 Standorte, einer ohne Koordinaten"** (`pruefen.py` 791–793) und mit ihr der einzige lebende Fall für `spur_ausgangspunkt` **an einem Standort**. Drei Wege: **(a)** Zeile streichen, der Fall gilt als abgedeckt durch die Tage ohne Standort (die `spur_ausgangspunkt` weiter brauchen); **(b)** ein vierter Standort **ohne Koordinaten und ohne Rettungsmittel** (etwa `Bereitschaftsraum Moosachtal`) hält die Zeile — geprüft wird dann nur noch die Standortseite und die Karte ohne Pin, nicht mehr die Spurerzeugung; **(c)** Talwang bleibt ohne Koordinaten — widerspricht dem Auftrag. | **(a)**, und die Zeile wird umformuliert zu „≥ 1 Diensttag ohne Standort mit `spur_ausgangspunkt`" — das ist der Fall, den die Anwendung seit 16.0.0 tatsächlich hat. Die alte Zeile prüfte einen Zustand, den der Auftrag abschafft. |
| **F-DA-2** | Soll E-DA-06 auch für **Typ Sonstiges** gelten (Fähigkeiten in beiden Betriebsarten)? | **Nein.** Sonstiges ist der Sammeltyp ohne Bedeutung; wer eine Winde braucht, ist Bergwacht oder Standard-Luft. Weniger Ausnahme, weniger zu erklären. Bei „ja" ändert sich in AP0 nur die Bedingung um eine Typ-Prüfung. |

### 3.2 Haltepunkte

| Nr. | Wann | Was |
|---|---|---|
| **H-DA-1 — Routen (Zuarbeit)** | nach AP2, vor AP3 | `generator/erzeugen.py` schreibt `routen/routen_soll.json` und meldet die **fehlenden Bodenstrecken** (neue Koordinatenpaare aus D17–D21). Der Sandkasten der Umsetzung erreicht `router.project-osrm.org` **nicht**. Zwei Wege, der Auftraggeber wählt: **(a)** er führt `python3 tools/referenzdatensatz/generator/routen/routen_holen.py` lokal aus und gibt die neuen `strecke_<hash>.geojson` an die Umsetzung zurück (eingecheckt nach E-P1-03); **(b)** er gibt den Host in den Netzeinstellungen der Umsetzungssitzung frei, und die Umsetzung holt selbst. **Der Generator weicht nicht auf erfundene Geometrie aus** — bis die Dateien da sind, steht AP3. Erwartete Größenordnung: rund 15–25 neue Strecken (jedes Koordinatenpaar einmal; Wiederholungen sparen). |
| **H-DA-2 — Vormessung** | vor AP1 | Weicht eine Zahl aus Abschnitt 1 ab (Fixture-Stand, 2 von 6 ohne Standort, 83 Matrixzeilen / 0 offen, ein Schnitt, Kreisläufe 0/0), anhalten und berichten — nicht selbst entscheiden. |
| **H-DA-3 — Kreisläufe** | in AP3 | Melden die Kreisläufe gegen die **neue** Referenz unerklärte Abweichungen, zuerst verstehen (neue Felder? neue Fälle wie `base_ref: null` am Tag?), dann eine Regel mit datiertem Grund in `ausnahmen/*.json` — und nur dann, wenn die Abweichung **erwartet** ist. Eine Regel, die eine Regression erklärt, ist keine. Ungenutzte Regeln: Soll weiter 0. |

---

## 4. Arbeitspakete

### AP0 · Regeländerung Fähigkeiten bei Typ Bergwacht (E-DA-06) — `server/`

*Was zu tun ist:* In `validate_lib.php` die Fähigkeitsregel von „`$kind === 'air'`"
auf „`$kind === 'air'` **oder** `$typ === 'bergwacht'`" erweitern — an der
einen Stelle, die beide Schreibwege teilen; der Meldetext „nur an
luftgebundenen Rettungsmitteln — verworfen" wird zu „bei diesem Typ nur
luftgebunden — verworfen". `stammdaten_ui.php`: die Fähigkeits-Häkchen bei
Typ Bergwacht auch für Boden anzeigen; `einstellungen.php`: die
Formularlogik, die Fähigkeiten an `kind === 'air'` hängt, entsprechend
(Umsetzung misst mit `grep -n "caps" server/*.php`, welche Stellen es sind —
1.2 nennt zwei). Rückspielweg der Sicherung (`backup_lib.php`, Fähigkeiten
werden gegen `VEHICLE_CAPABILITIES` gefiltert) prüfen: Er soll die Regel
**erben**, nicht kopieren. Handbuch (Abschnitt Rettungsmittel-Typen, die
Tabelle aus E-S9-09), `Technik.md` (falls die Typregeln dort stehen),
Changelog, `version.php`, Backlog-Nummer neu (E-DA-19).

*Vorher messen:* Ein Bergwacht-Rettungsmittel mit Betriebsart Boden und
`caps[] = winch` über das Stammdatenformular: **Fähigkeit verworfen** (Ist).
Dasselbe über ein Tagesrettungsmittel (E-S9-10): verworfen (Ist).

*Abnahme:* (1) Beide Wege speichern Winde und Bergwacht bei Typ Bergwacht /
Boden; `day_capabilities` friert sie beim Zuordnen ein; das Einsatzformular
eines solchen Tages zeigt die Windenkacheln und die Bergwachtfelder
(`cap_gate`). (2) Standard / Boden verwirft weiter (E29 bleibt für Standard).
(3) Veranstaltung verwirft weiter (fest Boden, keine Fähigkeiten). (4) Eine
Sicherung mit einem solchen Rettungsmittel kommt im Umlauf mit Fähigkeiten
zurück (Kreislauf edbak — Zahl in AP3). (5) Wortliste 0/0/0, Vollständigkeit
ohne neue `[offen]`, Bilderlauf auf Einstellungen → Rettungsmittel in beiden
Bedienhöhen ohne Überlauf.

*Pflichten:* Versionsstufe, Changelog-Absatz mit dem Grund (Bergwachtnotarzt
fährt hin, wird geflogen — die Kopplung von Winde und Luft war eine Regel über
Hubschrauber, nicht über Bergwacht), Handbuch, Backlog (neue Nummer, gleich
nach *Erledigt* mit Verweis auf dieses Konzept), Rahmenplan Abschnitt 7
(Register: Zusatz zu E-S9-09 als Zeile bei R73 oder als eigener Eintrag —
Umsetzung schlägt vor, Auftraggeber entscheidet beim Abschluss).

**AP0 liegt vor AP1**, weil AP1 die neuen Stammdaten mit Fähigkeiten
schreibt und `einspielen.py` sie sonst still verliert.

### AP1 · Stammdaten und Werkzeuge

*Was zu tun ist:*

1. **`stammdaten.json`:** Talwang mit Koordinaten (E-DA-09); Standort
   `Bergwachtstation Sonnenau`; vier Rettungsmittel (E-DA-05, -07, -08) mit
   `$warum`; Vorbelegungen nach E-DA-10. Die zwei bestehenden Rettungsmittel
   ohne Standort bleiben so, wie sie sind (Nr. 174).
2. **Schema** (`quelldaten/schema/`): `dienst.standort` darf `null` sein;
   neuer Wegpunkt `zustieg` in `route` und `spur.zustieg`; `schnitte` als
   Liste ohne Obergrenze; `dienst.besatzung` darf leer sein.
3. **`pruefen.py`:**
   - Tag ohne Standort: erlaubt genau dann, wenn das Rettungsmittel
     `ohne_standort` trägt; dann Pflicht `spur_ausgangspunkt`, `besatzung`
     leer, keine Prüfung von Transportziel, Bereitschaft und weiteren
     Rettungsmitteln gegen Standortlisten (sie sind Freitext), Marke
     `ziel-adhoc` aus dem Inhalt, wenn `transport_dest` gesetzt ist.
   - Gegenregel zu `spur_ausgangspunkt` (E-DA-09).
   - Fähigkeiten: ein Bergwacht-Rettungsmittel darf `winch`/`bergwacht`
     in beiden Betriebsarten tragen; ein Einsatz mit `winch = 1` braucht
     ein Rettungsmittel mit `winch` (wie heute), unabhängig von der Art.
   - Schnitte nach E-DA-11.
   - `zustieg` wie jeden Wegpunkt auflösen.
   - **Neue Matrixzeilen** (Dimension → Anforderung): *Diensttage* → „Typ
     Bergwacht", „Typ Veranstaltung", „ohne Standort (`base_id NULL`)",
     „ohne Rollen (Typ ohne Vorlagen)", „Abenddienst nach einem anderen
     Dienst am Kalendertag"; *Transport* → „Sekundärtransport
     bodengebunden", „Transportziel ad hoc (Freitext mit Koordinate, ohne
     Vorschlagsliste)"; *Bergrettung* (die bisherige Dimension
     „Luftspezifik" wird so umbenannt — sie ist es nicht mehr) → „Winde am
     bodengebundenen Bergwacht-Dienst"; *Herkunft* → „Schnitte an mehr als
     einem Diensttag"; *Erfassungsart* → „ohne Track, mit Ort- und
     Zielkoordinate (Luftlinie)"; *Spur* → „Fußweg (`zustieg`)". Strukturzeile
     Standorte nach F-DA-1. Zielwert **≈ 94 Zeilen, 0 offen** — die genaue
     Zahl ins Prüfdokument.
4. **`wegpunkte.py`**, **`generator/spur.py`** (`fussweg`),
   **`generator/erzeugen.py`** (Teilstücke `zustieg`), **`generator/pruefen.py`**
   (Gehgeschwindigkeit als Vertragsgrenze wie Flug- und Fahrgeschwindigkeit).
5. **`einspielen.py`**, Stufe `zuordnen`: `base_id: ""` für Tage ohne
   Standort; Stufe `nachtragen`: Freitext-Ziel mit Koordinaten (besteht,
   prüfen); Stufe `schneiden`: über alle `schnitte` aller Dienste; Kommentar
   „2 von 6" → „4 von 10".
6. **`FORMAT.md`:** die neuen Felder und Regeln, die Zahlen (E-DA-16).

*Vorher messen:* H-DA-2. Dazu `python3 quelldaten/pruefen.py` auf dem
unveränderten Stand: 83 Zeilen, 0 offen, 87 Einsätze.

*Abnahme:* `pruefen.py` läuft auf dem **alten** Bestand (nur Stammdaten und
Werkzeuge geändert, noch keine neuen Tage) mit **0 Sachfehlern** und meldet die
neuen Zeilen als **offen** — genau die Zahl der in Schritt 3 hinzugefügten
Zeilen (Soll ≈ 11, abzüglich der nach F-DA-1 gestrichenen). `generator/erzeugen.py`
läuft durch und erzeugt für die 16 alten Tage **byteweise dieselben**
Payloads wie vorher (Talwang-Koordinate = alter Ausgangspunkt, E-DA-09) —
gemessen mit `diff -r` gegen einen vorher gesicherten Ausgabeordner.

*Pflichten:* keine Versionsstufe (nur `tools/`), Changelog-Absatz (die
Werkzeuge können jetzt Tage ohne Standort, Fußwege und mehrere Schnitte),
`FORMAT.md`, `generator/LIESMICH.md`, `einspielen/LIESMICH.md`.

### AP2 · Die fünf Diensttage

*Was zu tun ist:* `dienste/D17.json` … `D21.json` von Hand, jeder Einsatz mit
`$warum` und Marken; `python3 quelldaten/aufbauen.py` baut die Ruhesegmente;
`pruefen.py --matrix` erzeugt `matrix_abgleich.md`. Die Tabellen unten sind
die Vorgabe; Uhrzeiten, Adressen, Diagnosen und Namen wählt die Umsetzung
plausibel, erfunden (E-P1-02) und mit Sonderzeichen, wo die Marke es verlangt.
Koordinaten liegen im Stützpunktnetz von `gelaende.py`; die genannten sind
Vorschläge.

**Die Tage**

| Kennung | Datum | Rettungsmittel | Zeit (lokal) | Standort des Tages | Zeit­zone | Besonderheit |
|---|---|---|---|---|---|---|
| **D17** | Sa 14.02.2026 | Bergwachtnotarzt Sonnenau (Boden, Bergwacht) | 08:00–17:00 | Bergwachtstation Sonnenau | MEZ | Winterdienst; ein Einsatz von Hand nachgetragen |
| **D18** | Sa 18.04.2026 | VEF Talwang 76/2 (Boden, Standard) | 07:00–19:00 | Notarztstandort Talwang | MESZ | zwei Verlegungen, ein Primäreinsatz, ein Schnitt |
| **D19** | Sa 01.08.2026 | Bergwachtnotarzt Sonnenau | 07:30–18:30 | Bergwachtstation Sonnenau | MESZ | Winde mit Polizeihubschrauber (Notiz); ein `wm-`-Einsatz; ein Schnitt |
| **D20** | So 14.06.2026, **abends nach D08** (NEF 07:00–19:00) | Boxkampf Rainer Maria Rilke (Boden, Veranstaltung) | 19:30–23:45 | **keiner** — `spur_ausgangspunkt` Halle ≈ 47.583 / 10.331 | MESZ | Kalendertag mit zwei Diensten; Transportziel ad hoc |
| **D21** | Mo 06.07.2026, **abends nach D10** (NEF 07:00–19:00) | Konzert von Karl Marx (Boden, Veranstaltung) | 19:30–23:30 | **keiner** — `spur_ausgangspunkt` Festwiese ≈ 47.596 / 10.411 | MESZ | alle Einsätze von Hand nachgetragen → Luftlinien |

`day_ref` je Tag `ad-12-…` (Handy 12); `besatzung` an D17, D19, D20, D21
**leer** (kein Rollensatz), an D18 `driver` und `other` aus den
Talwang-Vorbelegungen; `notizen` an jedem Tag.

**Die Einsätze** — Herkunft, Kurzbild, Pflichtmarken

| Tag | Nr. | Kanal / Herkunft | Kurzbild | Felder und geschützte Angaben | Marken (mindestens) |
|---|---|---|---|---|---|
| D17 | 1 | `ingest` / `android`, Nachtrag | Skiunfall, Knieverletzung, Abtransport im Akja zur Talstation (`zustieg`), Fahrt zur Klinik | `bergwacht 1`, `bw_unit Bergwacht Sonnenau`, `bw_info`, `transport_mode ground`, `transport_dest Bergklinik Sonnenau` mit Koordinate, `other_resources RTW Talwang 76/83` | dienst-bergwacht, fussweg-zustieg, bergwacht-einheit, transport-ground |
| D17 | 2 | `ingest` / `android`, Nachtrag | Winterwanderer, Sturz, ambulant versorgt | `transport_mode ambulant`, `bergwacht 1` | transport-ambulant |
| D17 | 3 | `formular` / `manual`, **`route: null`** | Hypothermie an einer Hütte, nachgetragen mit Ortskoordinate und Ziel `Unfallklinik Felsberg` mit Koordinate | `loc.lat/lon` gesetzt, `dest_lat/lon` gesetzt, `bergwacht 1`, Zeilenumbruch in `notes` | erfassung-ohne-track, luftlinie-ort-ziel, herkunft-manual |
| D18 | 1 | `ingest` / `android`, Nachtrag | **Sekundärtransport** Klinik Talwang → Universitätsklinikum Nordstadt | `secondary 1`, `na_escort 1`, `transport_mode ground`, Ziel mit Koordinate, `loc` = Klinik Talwang (Adresse + Koordinate), Diagnose intensivpflichtig | sekundaer-boden, transport-ground |
| D18 | 2 | `ingest` / `android`, Nachtrag | **Primäreinsatz** häuslicher Sturz, Transport Kreisklinik Steinach | `secondary 0`, `schockraum 0` | transport-ground |
| D18 | 3 | `ingest` / `android`, Nachtrag | **Sekundärtransport** Kreisklinik Steinach → Kreisklinikum Auwiesen, Abfahrt direkt vom vorigen Ziel | `secondary 1`, `start_src prev_dest` | sekundaer-boden, start-prev-dest |
| D18 | S1 | **Schnitt** aus einem `ar-12-`-Ruhesegment | „vergessener" kurzer Einsatz, Phasen 3, 4, 7 | — (keine geschützten Angaben, Sperrvermerk) | herkunft-schnitt |
| D19 | 1 | `ingest` / `android`, Nachtrag | Kletterunfall am Felsgrat, **Rettung mit Winde durch Polizeihubschrauber** — Hubschrauber nur in `notes`, Patient geflogen | `winch 1`, `winch_cycles 1`, `winch_cycles_pat 1`, `winch_airload 1`, `bergwacht 1`, `bw_unit Bergwacht Felsgrat`, `transport_mode air`, Ziel `Unfallklinik Felsberg` mit Koordinate; `zustieg` = Parkplatz, Fußweg zum Ort, Rückweg zu Fuß | winde-boden-bergwacht, winde-cycles, luftverladung, transport-air, fussweg-zustieg |
| D19 | 2 | `ingest` / **`wear`** (`wm-12-`), Nachtrag | Wanderer, Kreislaufkollaps, ambulant; an der Uhr begonnen | `transport_mode ambulant`, `bergwacht 0` | herkunft-wear, transport-ambulant |
| D19 | 3 | `ingest` / `android`, Nachtrag | Mountainbike-Sturz, Schulterluxation, Transport Kreisklinik Steinach | `bergwacht 1`, `bw_unit Bergwacht Moosachtal`, `transport_mode ground` | transport-ground, bergwacht-einheit |
| D19 | S2 | **Schnitt** aus einem `ar-12-`-Ruhesegment | Mitfahrt ohne eigene Dokumentation, Phasen 3, 4, 7 | — | herkunft-schnitt |
| D20 | 1 | `ingest` / `android`, Nachtrag | Boxer nach K.o., Commotio, Transport | `transport_dest Kreisklinik Steinach` **als Freitext** mit `dest_lat/lon` (kein Standort, keine Liste), `transport_mode ground`, `schockraum 0` | dienst-veranstaltung, tag-ohne-standort, ziel-adhoc |
| D20 | 2 | `ingest` / `android`, Nachtrag | Zuschauerin, Synkope, ambulant — kurzer Weg in der Halle (Route `basis → ort`, wenige hundert Meter) | `transport_mode ambulant` | transport-ambulant |
| D20 | 3 | `ingest` / `android`, Nachtrag | Schnittverletzung Hand, ambulant; Sonderzeichen `;` und `"` in `site_desc` | `transport_mode ambulant` | sonderzeichen |
| D21 | 1 | `formular` / `manual`, `route: null` | Alkoholintoxikation, Transport `Kreisklinikum Auwiesen` als Freitext mit Koordinate | `loc` mit Koordinate, `dest_lat/lon` | luftlinie-ort-ziel, ziel-adhoc, herkunft-manual |
| D21 | 2 | `formular` / `manual`, `route: null` | Hyperventilation, ambulant — nur Ortskoordinate | `transport_mode ambulant`, kein Ziel | erfassung-ohne-track |
| D21 | 3 | `formular` / `manual`, `route: null` | **Reanimation** vor der Bühne, ROSC, Transport mit Schockraum | `rea` mit Sitzung und ≥ 5 Ereignissen, `schockraum 1`, `other_ema`, Ziel mit Koordinate | rea-eine-sitzung, schockraum, luftlinie-ort-ziel |
| D21 | 4 | `formular` / `manual`, `route: null` | Hitzekollaps, ambulant; Formel-Anfangszeichen `=` in `notes` | `transport_mode ambulant` | formel-gleich |

Die Marken der bestehenden Matrix (Phasen, Zeit, Sonderzeichen,
Geburtsdatum/Handalter, Einsatznummer, Ortsbeschreibung, Notizen) vergibt die
Umsetzung so, dass **keine bestehende Zeile ihre Belegung verliert** und die
neuen Zeilen belegt sind.

*Vorher messen:* AP1-Abnahme steht (offene Zeilen = neue Zeilen).

*Abnahme:* `pruefen.py`: **0 offene Zeilen, 0 Sachfehler**, 21 Dienste, 103
Einsätze (±2 nach E-DA-02), 3 Schnitte, Zeitstempel-Prüfungen > 1129.
`generator/erzeugen.py` und `generator/pruefen.py` grün für alle Einsätze
**mit vorhandener Strecke**; die Liste fehlender Strecken ist die Übergabe an
**H-DA-1**. Alle Fußwege innerhalb der Gehgeschwindigkeits-Grenze.

*Pflichten:* keine Versionsstufe; `FORMAT.md` (Zahlen), `matrix_abgleich.md`
(erzeugt).

### AP3 · Einspielen, Referenz und Fixture neu — nach H-DA-1

*Was zu tun ist:* Die drei Läufe aus `tools/referenzdatensatz/LIESMICH.md`
auf dem Prüfstand (Aufbau wie Runde 3: `lokal_starten.sh`,
`lokal_einrichten.sh`, MariaDB, PHP, TLS, Chromium): (1) Bestand erzeugen und
prüfen; (2) Einspielen in **allen** Stufen samt `browser/csv_import.mjs`;
(3) **Hintergrundjobs anhalten**, dann `browser/referenz_export.mjs` (beide
Referenzdateien neu) und `php fixture/erzeugen.php` — **aus demselben
Einspiellauf** (E-DA-15). Alte Referenzdateien ersetzen, Verweise nachziehen.

*Vorher messen (in der frisch eingespielten Installation):*
`vehicles.base_id IS NULL` = **4** (von 10); `days.base_id IS NULL` = **2**
(D20, D21); Rettungsmittel mit `winch` und `kind = 'ground'` = **1**;
`missions` mit `client_ref LIKE 'cut-%'` = **3**; `missions.secondary = 1`
= **5**; `track_points` **unverändert** zwischen Einspielen und Export (die
Zahl aus dem Einspiellauf, gegen die Zahl im Fixture-Kopf).

*Abnahme:*
1. Beide Kreisläufe gegen die **neue** Referenz: **0 unerklärt, 0
   ungenutzt**; im edbak-Umlauf kommen zurück: ein Rettungsmittel mit
   `base_ref: null` (4/4), ein Diensttag ohne Standort (2/2), ein
   bodengebundenes Bergwacht-Rettungsmittel **mit** Fähigkeiten (AP0-Abnahme 4),
   drei Schnitte mit Sperrvermerk (3/3). Einzelvergleiche in der
   Größenordnung edbak ≈ 350 000, csv ≈ 11 000 — die genaue Zahl ins
   Prüfdokument. Abweichungen: H-DA-3.
2. Klickprobe **n von n** (heute 40 von 40 — die Zahl kann sich mit dem
   Bestand ändern, die Umsetzung nennt beide); Bilderlauf über die
   berührten Seiten (Einstellungen → Rettungsmittel, Einstellungen →
   Standorte, Diensttag D20, Einsatzansicht D19/1, Karte D21) in beiden
   Bedienhöhen ohne Überlauf; Papierkorb-Mischfall (R27) grün — er läuft mit,
   weil die Referenz Papierkorb-Einträge trägt und der Bestand sich ändert.
3. Fixture: `erzeugen.php` läuft ohne Abbruch (Demo-Konto auf
   `KDF_ITER_ZIEL`), Kopf nennt Web-Version und Spurpunkte; Demo-Konto auf
   dem Prüfstand **anlegen** und **zurücksetzen** (Adminbereich), Anmeldung
   mit `demo@gen-em.org` / `nadokudemo0815`, Diensttag D19 zeigt die
   Windenkacheln, D20 zeigt kein Standortfeld und keine Rollen, Karte D21
   zeigt gestrichelte Luftlinien.
4. Dauer des Demo-Resets vorher/nachher gemessen (Nr. 76 will erst messen —
   hier fällt die Zahl nebenbei an; ins Prüfdokument).

*Pflichten:* keine Versionsstufe; Changelog-Absatz (die Referenz ist Teil des
Repositoriums — wer später vergleicht, muss wissen, seit wann sie 106 Einsätze,
Tage ohne Standort und drei Schnitte trägt); `tools/referenzdatensatz/LIESMICH.md`
(„Der Bestand in Zahlen", Ordnertabelle, Demo-Fixture-Absatz), Verweise auf
die Referenzdateien.

### AP4 · Zahlen, Dokumente, Übergabe

1. **Regressionspflicht R24:** die Zahlen aus AP3 ins Prüfdokument; dazu
   Wortliste **0/0/0**, Vollständigkeit (Gesamtzahl vorher/nachher, keine neuen
   `[offen]`), Linkprobe, Klickprobe, Bilderlauf, Wartungsprobe (unberührt —
   trotzdem laufen lassen, weil `demo_lib.php` die Fixture liest; Soll
   unverändert grün).
2. **Zahlen nachziehen** nach 1.4 Absatz 1; Messstand-Satz nach E-DA-18;
   Absatz 2 bleibt.
3. **Dokumente auf Konsistenz:** Handbuch (Rettungsmittel-Typen mit
   Fähigkeiten, Demo-Konto-Abschnitt mit dem Bestand), `Technik.md`
   (Verzeichnisstruktur, 4.99a Demo-Mechanik falls Zahlen), `Design.md` nur,
   falls das Stammdatenformular ein neues Element bekam (Soll: nein),
   Changelog (eine Stufe aus AP0, Prosa je Paket).
4. **Backlog:** neue Nummer aus AP0 nach *Erledigt*; Nr. 76 mit der gemessenen
   Reset-Dauer ergänzen (bleibt offen); Kopf: reservierte Nummer.
5. **Rahmenplan:** Fahrplan Schritt 9d (Anhang A) — Status; Abschnitt 4
   Sperrtabelle; Abschnitt 6 (Zuarbeit OSRM erledigt); Abschnitt 8 — Zeile
   „Demo-Ausbau"; Abschnitt 10 — Fassung; Abschnitt 7 — Registerzeile zu
   E-DA-06.
6. **Prüfdokument** nach K9 fertigstellen: Vorlage `Pruefdokument-Demo-Ausbau.md`
   mit Zahlen füllen, Nicht-Prüfbares zuerst, Prüfliste für den Auftraggeber.
7. **Ausgabe:** ZIP mit der Ordnerstruktur des Repositoriums, nur geänderte und
   neue Dateien (`server/…`, `tools/…`, `docs/…`); dieses Konzept mit
   fortgeschriebenem Abschnitt 8; das Prüfdokument.

---

## 5. Was bei jeder Codeänderung mitläuft (CLAUDE.md 2)

| Pflicht | Hier |
|---|---|
| Version | nur AP0 (`version.php`), Stufe nach Einschätzung der Umsetzung |
| Changelog | je Paket ein Absatz mit Begründung; AP1–AP4 ohne Stufe unter der AP0-Stufe oder als „nur `tools/`"-Absatz, wie Runde 3 es gemacht hat |
| Handbuch / Technik | AP0 (Typregeln), AP4 (Bestand, Verzeichnisstruktur) |
| Backlog | eine neue Nummer (AP0), Nr. 76 ergänzt |
| Design | voraussichtlich nichts — kein neuer Baustein, kein neues Element |
| Lizenzen | nichts — keine neue Abhängigkeit (Routen bleiben OSRM-Geometrie nach E-P1-03, wie bisher) |
| Wortliste | nach jeder sichtbaren Textänderung (AP0, Handbuch), Soll 0/0/0 |
| Vollständigkeit, Bilderlauf, Linkprobe, Klickprobe, Wartungsprobe, Papierkorb-Mischfall | AP3/AP4, Zahlen ins Prüfdokument |
| Messstand | läuft nicht (E-DA-18) |
| Kommentare | Grund ja, Nummer nein (R69) für neuen Code in `server/`; in `tools/` wie bisher üblich |

---

## 6. Prüfprotokoll

Getrennt nach K9: `Pruefdokument-Demo-Ausbau.md` (Vorlage daneben). Die
Umsetzung füllt je Paket die Zahlen und führt den Stand am Kopf dieses
Konzepts (K5, R62).

---

## 7. Fehlerfunde (K4)

*Gesammelt während der Umsetzung, nicht sofort behoben — außer der Fund
blockiert.* Vorab bekannt und **kein** Fund, sondern Auftrag: die
Fähigkeitsregel (AP0).

| Nr. | Wo | Was | Blockiert? | Stand |
|---|---|---|---|---|
| **F-DA-1** | `server/einstellungen.php`, Skript des Rettungsmittel-Dialogs | Die Zeile `var capsAn = regel.rollen && kind === 'air'` war eine **dritte** Fassung der Fähigkeitsregel — und **enger als der Server**. Ein Rettungsmittel des Typs **Bergwacht oder Sonstiges mit Betriebsart Luft** durfte Fähigkeiten führen (`pruef_rettungsmittel()` nahm sie an), bekam die Häkchen im Dialog aber **nie** zu sehen: Die Zeile hing an `regel.rollen`, und das hat außer `standard` kein Typ. Der Bestand führt den Fall seit S9 („Bergwacht Hochkreuth", Luft, mit Winde und Bergwacht) — er ließ sich nur nicht über die Oberfläche herstellen. | nein | **behoben in AP0** (Web 20.3.0). Der Dialog liest jetzt dieselbe Tabelle wie die Prüfschicht; die Klickprobe `da-faehigkeiten-nach-typ` misst alle sieben herstellbaren Kombinationen. |
| **F-DA-2** | `server/einstellungen.php`, Standortseite | Die Karte **Bergwacht-Bereitschaften** erschien nur, wenn am Standort ein **luftgebundenes** Rettungsmittel stand (`$hatLuft`). Mit E-DA-06 wäre die Bergwachtstation Sonnenau — ein bodengebundener Notarzt vom Typ Bergwacht — ein Standort mit dem Feld „Bereitschaft" im Einsatz **und ohne jeden Ort, an dem sich Bereitschaften anlegen lassen**. Der Einspiellauf hätte die drei Bereitschaften aus E-DA-10 trotzdem angelegt (der POST-Zweig prüft `$hatLuft` nicht) — sie wären danach unsichtbar und unbearbeitbar im Bestand gelegen. | **ja** — hätte AP1 stumm beschädigt | **behoben in AP0**: Gefragt wird `veh_caps_erlaubt()`, also „steht hier ein Rettungsmittel, das die Fähigkeit führen **darf**". |
| **F-DA-3** | Konzept, E-DA-06 und AP0-Abnahme (1) | Das Konzept verlangt die Regeländerung auch für `pruef_tagesrettungsmittel()` und erwartet, dass „**beide Wege** Winde und Bergwacht speichern". Gemessen: `pruef_tagesrettungsmittel()` kennt **überhaupt keine Fähigkeiten** — es liefert `name, typ, kind, base_id, base_name`, und `dt_zuordnen()` löscht für ein Tagesrettungsmittel `day_capabilities` ausdrücklich leer (F19, E-S9-10). Geteilt wird zwischen den beiden Wegen `pruef_typ_betriebsart()`, also Typ und Betriebsart — nicht die Fähigkeitsregel. | nein | **Konzeptkorrektur.** AP0 ändert dort nichts. Ein Tagesrettungsmittel vom Typ Bergwacht bekommt weiterhin keine Windenfelder; das ist bestehendes Verhalten und war nie Gegenstand des Auftrags. Der Referenzbestand braucht es nicht: D17 und D19 laufen auf einem **Stammdaten**-Rettungsmittel. |
| **F-DA-4** | `server/api/range.php`, `server/zeitraum.php` | Folge der Regeländerung, die im Konzept fehlt: Die Zeitraumübersicht beantwortet `faehigkeiten` nur über `d.kind = 'air'`, und die beiden Windenkacheln stehen nur im **Luft**-Kachelsatz. Ein bodengebundener Bergwacht-Diensttag mit Windeneinsatz (D19) zeigt seine Windenfelder im Einsatzformular, wird in der Auswertung aber nicht als Windendienst gezählt. | nein | **bewusst offen gelassen**, als **Backlog Nr. 191**. Zwei Kacheln mehr im Bodensatz wären zehn Kacheln in vier Spalten — eine Gestaltungsentscheidung mit Freigabe und Mockup (`CLAUDE.md` 5). Der Kommentar an der Abfrage sagt jetzt, dass die Zeile eine **Lücke** ist und keine Herleitung; `Technik.md` und Handbuch ebenso. |
| **F-DA-5** | `tools/referenzdatensatz/generator/pruefen.py` | Der Kommentarblock über `GRENZE_HOEHE` ist um acht Leerzeichen eingerückt und steht damit optisch **innerhalb** der Zuweisung darüber. Rein kosmetisch. | nein | **behoben in AP1** (mitgenommen, weil die Datei ohnehin angefasst wird). |
| **F-DA-6** | `server/einsatz.php` | Die Höhe des Einsatzorts (`site_ele_m`) erscheint nur an einem **luftgebundenen** Diensttag (`m.day_kind === 'air'`). Ein Bergwacht-Einsatz am Boden auf 1200 m führt die Höhe in der Datenbank, zeigt sie aber nicht. | nein | **nicht behoben.** Das ist eine Anzeigeentscheidung aus A13 und nicht Gegenstand dieses Pakets; anders als bei den Fähigkeiten hat der Auftraggeber dazu nichts gesagt. Als Beobachtung hier festgehalten — falls sie ihn stört, gehört sie zu Nr. 191 in dieselbe Runde. |
| **F-DA-7** | `tools/referenzdatensatz/fixture/erzeugen.php` | Der Riegel auf der Schlüsselhülle (S10) hat beim ersten Neubau des Bestands zugeschlagen: Das frisch angelegte Konto trug nach dem ersten Anmelden `edka1:<kennung>:`, und der Erzeuger hielt richtigerweise an — nach vier Minuten Einspielen. Die Anleitung sagte zwar „Demo-Konto vor den Browserläufen", aber der Adminbereich kann eines erst **mit** Fixture anlegen. Die Reihenfolge war also gar nicht ausführbar. | **ja** — AP3 kam ohne Behebung nicht zum Ende | **behoben in AP3** (E-DA-27): `einspielen/demo_kennzeichnen.php` vermerkt das Konto ohne Fixture und ohne Adminbereich. LIESMICH und Einspiel-Anleitung tragen die Zeile jetzt in der Befehlsfolge, nicht nur als Satz. |
| **F-DA-8** | `tools/klickprobe/wege/ap3.mjs`, Hilfsfunktion `zuordnungLesen()` | Die Klickprobe hat den **Bestand beschädigt**: Sie las den Standort eines Diensttags aus dem `<select>` `#basesel` und schrieb ihn im `finally` zurück. Bei einem Tag **ohne** Standort liefert das Auswahlfeld seine erste Option — D21 bekam dadurch „Luftrettungsstation Hochkreuth". Vor dem Demo-Ausbau gab es keinen Tag ohne Standort; der Fehler war seit S9 da und konnte nicht auffallen. | nein — die Fixture war bereits gebaut und sauber (`base_ref: null`) | **behoben in AP3.** Die Hilfsfunktion liest jetzt `api/day.php` statt des Auswahlfelds; D20 und D21 sind über `api/day.php` zurückgesetzt und nachgemessen. |
| **F-DA-9** | `tools/referenzdatensatz/vergleich/ausnahmen/csv_umlauf.json` | Die Selbstprobe „Zeile in `diensttage.csv` entfernt" meldete **0 statt 1**: Eine Ausnahme mit dem Platzhalter `diensttage/*` und der Art `fehlt` deckte **jedes** fehlende Feld dieser Datei ab — auch eines, das die Probe absichtlich herbeiführt. Der Fund ist **älter als dieses Paket**; nachgewiesen gegen die alte Referenz in einem eigenen Arbeitsbaum. | nein | **behoben in AP3.** Die Wildcard-Regel ist in zwei Regeln mit **Schlüssel** zerlegt (`2026-09-12` und `20\d\d-\d\d-\d\d#\d+`); `vergleichen.py` kann dafür jetzt `schluessel_regex`. Die Probe meldet **10 von 10**. |

---

## 8. Stand der Abarbeitung

| Paket | Stand | Version | Zahlen | Probleme und Lösungen |
|---|---|---|---|---|
| AP0 | **erledigt** 14.09.2026 | **Web 20.3.0** (Neben) | `veh_caps_erlaubt()` über **8** Typ/Betriebsart-Paare durch `pruef_rettungsmittel()` gefahren, Ergebnis wie die Regeltabelle · Klickprobe `da-faehigkeiten-nach-typ` **7 von 7** Kombinationen im Dialog · Wortliste **0/0/0** (96 Ausnahmen, 96 gegriffen) · Vollständigkeit **340 Befunde**, gegen `main` im Zweitbaum gemessen: **unverändert 340**, **0** neue `[offen]` · `php -l` auf 8 berührten Dateien fehlerfrei | **Die Regel wurde eine Spalte statt einer Bedingung** (E-DA-21) — sie wird an vier Stellen gebraucht. **Zwei Lücken lagen darunter** (F-DA-1, F-DA-2): Das Dialogskript führte eine dritte, engere Fassung der Regel, und die Bergwacht-Karte hing am gleichen zu engen Merkmal — F-DA-2 hätte AP1 stumm beschädigt. **Eine Konzeptannahme war falsch** (F-DA-3): `pruef_tagesrettungsmittel()` kennt keine Fähigkeiten. **Eine Folge bleibt offen** (F-DA-4 / Backlog Nr. 191). Mitgenommen, weil dieses Paket `server/` ohnehin anfasst: **Backlog Nr. 189** (toter Konzeptpfad in zwei Kommentaren). |
| AP1 | **erledigt** 15.09.2026 | — (nur `tools/`) | `quelldaten/pruefen.py` am **alten** Bestand: 16 Dienste, 87 Einsätze, 100 Ruhesegmente, 1129 Zeitstempel, **6020 Einzelprüfungen**, **0 Sachfehler**, **97 Matrixzeilen / 10 offen** (die 14 neuen Zeilen, davon 4 schon belegt) · `generator/erzeugen.py`: 526 Anfragen, 56 587 Spurpunkte, 82 GPX, 117 OSRM-Strecken — `diff -r` gegen den Stand vor AP1: **kein Unterschied außer** `start_src` in 36 Formulardateien (E-DA-26) und der neuen `fusswege.json` · `generator/pruefen.py`: **283 997 Einzelprüfungen, 0 Befunde** | **Die Fensterableitung stand zweimal** und musste zusammengeführt werden (E-DA-25) — mit `zustieg` hat ein Einsatz vier Teilstücke und drei Phasenfenster. **Talwangs neue Koordinaten ändern 36 erzeugte Einsätze** (E-DA-26): `start_src` null → base. Die Spuren bleiben byteweise gleich. **F-DA-5** (Einrückung in `generator/pruefen.py`) mitgenommen. |
| AP2 | **erledigt** 15.09.2026 | — (nur `tools/`) | `pruefen.py`: **21 Dienste, 103 Einsätze, 119 Ruhesegmente, 1335 Zeitstempel, 7042 Einzelprüfungen, 97 Matrixzeilen / 0 offen, 0 Sachfehler**, 109 Marken · `generator/erzeugen.py`: 612 Anfragen, 64 478 Spurpunkte, 93 GPX, **4 Fußwege (2,1–3,5 km/h)**, 144 OSRM-Strecken · `generator/pruefen.py`: **321 799 Einzelprüfungen, 0 Befunde** · Payloads der 16 alten Diensttage byteweise unverändert (`diff -rq` je Ordner D01…D16) | **Zwei Funde, beide vom Prüfmittel und nicht vom Auge.** (1) `phasenfenster()` füllte fehlende Fenster selbst auf, und `fenster()` schloss daraus, es gebe genug — der Fußweg bekam ein Viertel der Einsatzdauer und lief mit **1,2 km/h**. Getrennt in `phasenkandidaten()` (roh) und `phasenfenster()` (aufgefüllt). (2) Ein Fußweg, der exakt am Wegpunkt endet, während die Fahrt danach auf der Straße beginnt, lässt die Spur springen: **557 km/h** an D19/1, gemeldet von `generator/pruefen.py`. Fußwege werden jetzt in einem **zweiten Durchgang** gezeichnet, zwischen den tatsächlichen Enden ihrer Nachbarn. Dazu: Die Umfangszeile (`80–100 Einsätze`) musste auf **95–125** — sie prüft keine Regel der Anwendung, sondern fängt einen halben `aufbauen.py`-Lauf. |
| H-DA-1 | **entfallen** 15.09.2026 | — | `routen_holen.py`: **144 Teilstücke, 108 verschiedene Strecken, 24 neu geholt, 84 vorhanden**; Fußwege übersprungen | Der Haltepunkt nahm an, `router.project-osrm.org` sei im Sandkasten der Umsetzung gesperrt. Gemessen: **HTTP 200**. Die Umsetzung hat selbst geholt und die Dateien eingecheckt (E-DA-22). |
| AP3 | **erledigt** 15.09.2026 | — (nur `tools/`, `docs/` und `server/demo/fixture.json.gz`; die Zahlen in `betrieb_statistik.php` und `missiontable.js` sind Kommentare) | Einspiellauf **612 Anfragen, 0 Fehler**, 21 Tage, 90 nachgetragen, 7 manuell, 3 Schnitte · Datenbank danach: **106 Einsätze** (101 aktiv, 5 Papierkorb), **21 Diensttage** (20 aktiv), **119 Ruhesegmente** (114 aktiv), **63 752 Spurpunkte**, 3 Standorte (alle mit Koordinate), 10 Rettungsmittel (**4 ohne Standort**), **2 Tage ohne Standort**, **1 Bodenfahrzeug mit Winde**, 3 `cut-`, 5 `secondary = 1`; Herkunft Uhr 39 / Handy 49 / Wear 4 / manuell 7 / Import 4 / Schnitt 3 · Referenz-Export **101 Einsätze, 204 GPX, 225 Einträge, 63 752 Punkte** · Fixture-Kopf Web **20.3.0**, 106/21/119/63 752, gepackt **rund 860 KB** · **edbak-Kreislauf 328 771 Einzelvergleiche — 0 unerklärt, 21 erwartet, 0 ungenutzt**, Selbstproben **15 von 15** · **CSV-Kreislauf 10 922 Einzelvergleiche — 0 unerklärt, 1271 erwartet, 0 ungenutzt**, Selbstproben **10 von 10** · Klickprobe **47 von 47** · Linkprobe 117 Verweise / 0 Abweichungen · Wartungsprobe 57 Erwartungen / 0 nicht erfüllt · Papierkorb-Mischfall 15 Einzelprüfungen / 0 Befunde · Bilderlauf **Zeiger 49 Seiten / 392 Bilder, 0 Überlauf / 0 Konsolenfehler / 0 Knöpfe falscher Höhe (44 und 36 px)**, **Finger 16 berührte Seiten / 128 Bilder, 0/0/0 (44 px)** · Demo-Reset gemessen: **5859 ms** alt gegen **6610 ms** neu (je 3 Läufe) | **Drei Funde.** (1) Der Fixture-Riegel hielt den Lauf an, weil die dokumentierte Reihenfolge nicht ausführbar war — `demo_kennzeichnen.php` löst das (F-DA-7, E-DA-27). (2) Die Klickprobe hat D21 einen Standort verpasst, weil sie ihn aus einem Auswahlfeld las, das für einen Tag ohne Standort seine erste Option liefert (F-DA-8) — seit S9 vorhanden, erst jetzt auslösbar. (3) Eine Wildcard-Ausnahme verschluckte eine Selbstprobe des CSV-Vergleichs (F-DA-9) — älter als dieses Paket. **Zwei Ordnungen normalisiert statt ausgenommen** (E-DA-28): Stammdatenlisten und Reanimations-Ereignisse. **Fünf Klickprobe-Wege mussten an den neuen Bestand** — sie hingen an Zahlen und an „der erste Standort", und der heißt jetzt Bergwachtstation Sonnenau und hat keine Besatzungskarte. **Drei Seiten im Bilderlauf neu** (E-DA-29), weil seine Platzhalter sonst den Regelfall unter dem Namen des Sonderfalls fotografiert hätten. |
| AP4 | **erledigt** 15.09.2026 | — (nur `docs/` und `tools/`) | Wortliste **0/0/0** über fünf Bereiche (667 Treffer, alle durch die 96 Ausnahmen erklärt, 0 ungenutzt, 0 durchgerutschte Fallen) · Vollständigkeit **340 Befunde, 0 `[offen]`** — dieselbe Zahl wie auf `main` · Linkprobe **117 Verweise / 0 Abweichungen** · Wartungsprobe **57 Erwartungen / 0 nicht erfüllt** · Klickprobe **47 von 47** (nach den Demo-Resets erneut gefahren, weil sich alle Kennungen verschoben hatten) · Bilderlauf **Zeiger 49 Seiten / 392 Bilder 0/0/0**, alle drei neuen Seiten aufgelöst und fotografiert · Selbstprüfzahl des Rahmenplans **53 = 53**, mit `diff` gegengeprüft | **Ein Treffer der Wortliste, und zwar in einem Satz dieses Pakets:** „auf derselben **Maschine**" in der neuen Reset-Messung (`Technik.md` 4.99a) — „Maschine" ist ein Luftbegriff und steht auf der Sperrliste. Ersetzt durch „Rechner", an drei Stellen. Genau dafür läuft das Werkzeug **nach** der letzten Änderung und nicht davor. **Eine Zahl steht jetzt an einer Stelle weniger von Hand:** `einspielen/messprotokoll.md` wird **erzeugt** und ist neu gefahren (612 Anfragen, 21 Dienste, 64 478 Punkte); die Zitate in `papierkorb_misch.mjs` bleiben als **datierte** Rückmeldung stehen und sagen das jetzt auch. |

---

## 9. Quellen

`docs/Rahmenplan.md` (Fahrplan Schritt 9b/10/12/12a/13/14, Abschnitt 2.2, 4,
8 — P1, Runde 2 und 3), `docs/Rahmenplan-Archiv.md` (R25, R42 Muster),
`docs/Backlog.md` (Nr. 63, 76, 155, 173, 174), `docs/konzepte/Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md`
(E-S9-09, E-S9-10, E-S9-18, PS-7), `docs/konzepte/Konzept-R64-Herkunft-Geraet.md`
(E-R64-16), `docs/konzepte/Konzept-Backlog-Runde-3.md` (Block C, Prüfstand),
`docs/konzepte/Vorbereitung-Sicherheitspaket.md` (S10 und Demo-Konto),
`tools/referenzdatensatz/LIESMICH.md`, `quelldaten/FORMAT.md`,
`quelldaten/pruefen.py`, `quelldaten/matrix_abgleich.md`, `quelldaten/stammdaten.json`,
`quelldaten/dienste/D01–D16.json`, `generator/routen/LIESMICH.md`,
`generator/gelaende.py`, `generator/spur.py`, `einspielen/einspielen.py`,
`fixture/erzeugen.php`, `server/demo/fixture.json.gz` (Kopf),
`server/validate_lib.php`, `server/db.php` (`VEHICLE_TYPEN`,
`VEHICLE_CAPABILITIES`), `server/mission_fields.php`, `server/index.php`
(`vehicleBaseSync`), `server/api/day.php`, `docs/JSON-Vertrag.md` Abschnitt 8.

---

## Anhang A — Einschübe für den Rahmenplan (von der Umsetzung einzutragen)

**Fahrplan, neue Zeile nach 9c:**

| Schritt | Kennung | Inhalt | Voraussetzung | Konzept | Modell | Status |
|---|---|---|---|---|---|---|
| 9d | **Demo-Ausbau** | Referenzbestand und Demo-Konto um die S9-Typen im Betrieb erweitern: fünf Diensttage (Bergwachtnotarzt ×2, VEF Talwang, zwei Veranstaltungen), 16 Einsätze + 2 Schnitte, Standorte mit Koordinaten, Tage ohne Standort, Fußwege; dazu die Regeländerung **Fähigkeiten bei Typ Bergwacht in beiden Betriebsarten** (AP0, `server/`, keine Migration) | **Schritt 9b gemergt** (S10 misst mit dem Referenzbestand); parallel zum P5-Konzept; **Zuarbeit:** OSRM-Routen (Abschnitt 6) | `docs/konzepte/Konzept-Demo-Ausbau.md` | Opus, kein Fable-Schritt | offen |

**Abschnitt 4, Sperrtabelle:** „Demo-Ausbau zu S10 — **nicht parallel** (AP0
schreibt `einstellungen.php`; der Referenzbestand ist S10-Messlatte); Demo-Ausbau
zu P5-Konzept und zum R42-Rest (`betrieb_statistik.php`) — parallel, keine
gemeinsamen Dateien außer Buchführung."

**Abschnitt 6, Zuarbeiten:** „OSRM-Routen für die neuen Bodeneinsätze:
`routen_holen.py` lokal ausführen und die `strecke_*.geojson` zurückgeben,
oder `router.project-osrm.org` für die Umsetzungssitzung freigeben — fällig
nach AP2 (H-DA-1)."

**Abschnitt 5:** Nr. 76 bleibt (Messung fällt in AP3 an); die neue Nummer aus
AP0 erscheint dort nicht (gleich erledigt).

## Anhang B — Was dieses Paket bewusst nicht tut

- Es ändert **kein** Format (Fixture Fassung 2, Nutzlast 7, Backup-Format
  unverändert) und **keine** Migration — S11, P7 und P8 bauen den Bestand
  ohnehin neu; der Inhalt aus den Quelldaten überlebt das.
- Es fasst das **Demo-Passwort** nicht an (P7, R25) und den Demo-Reset-Takt
  nicht (Nr. 76: erst messen).
- Es erweitert den **Katalog** des Betriebsalltags nicht — die neuen Tage
  sind Prüffälle, und ein erzeugter Bergwacht-Alltag wäre ein eigenes Paket
  mit eigener Abdeckung.
- Es legt kein **drittes Gerät** an.
