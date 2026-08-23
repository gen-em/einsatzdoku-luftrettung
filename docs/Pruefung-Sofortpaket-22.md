# Prüfdokument — Sofortpaket Backlog Nr. 22 (Web 7.2.1)

Maskierung des Altersfelds. Grundlage: Auftrag Sofortpaket Nr. 22, Befund F-20
aus P0. Muster und Regeln nach K9 bzw. Konzept P0, Abschnitt 11.

---

## 0. Was NICHT geprüft werden konnte

- **Ein echter Import am laufenden System.** Die Probe baut das Markup der
  Einsatztabelle nach und lädt dafür die echten Dateien; sie ersetzt nicht den
  Weg Datei → Import → Speichern → Anzeige. Dieser Weg braucht eine Anmeldung,
  einen entsperrten Inhaltsschlüssel und eine Datenbank — er bleibt der
  Betreiberin (Prüfliste, Punkt P-1).
- **Die Abgleichsansicht mit echten Bestandsdaten.** Sie zeigt die rohen
  Zellen der Datei in `<input value="…">` und dazu Angaben aus dem Bestand.
  Die Durchsicht unten ist eine Lesung des Codes, kein Lauf mit Daten.
- **Der Abmeldeweg** (S22-3) — siehe Abschnitt 3.

---

## 1. Durchsicht des Importpfads (S22-2)

**Frage:** Welche Werte aus einer Importdatei erreichen per `innerHTML` (oder
`insertAdjacentHTML` o. ä.) die Seite, ohne maskiert zu sein?

**Umfang der Lesung:** 23 eigene JavaScript-Dateien unter `server/assets/`
(ohne `vendor/`) sowie alle Seiten unter `server/` und `server/api/`.
Gefunden wurden **32 Ausgabestellen** mit `innerHTML`, `insertAdjacentHTML`,
`outerHTML` oder `document.write`. Weitere Senken (`srcdoc`, `.html()`,
`eval`, `new Function`, `createContextualFragment`, `setHTML`,
`javascript:`-Verweise) kommen im Projekt **nicht** vor — gesucht, null Treffer.

**Ergebnis: ein Fund — das Alter (F-20, behoben mit S22-1). Sonst keiner.**

### 1.1 Die 32 Ausgabestellen

| Datei | Zeilen | Was dort steht | Urteil |
|---|---|---|---|
| `assets/import_ui.js` | 59, 66 | Profilliste, Profilparameter | `esc()` in Text **und** Attribut |
| `assets/import_ui.js` | 93 | Passwortdialog | festes Markup; Text über `textContent` |
| `assets/import_ui.js` | 426 | **Abgleichs-/Vorschautabelle** | s. 1.2 |
| `assets/import_ui.js` | 692 | Ergebnis der Übernahme | s. 1.3 |
| `assets/missiontable.js` | 272 | Spaltenköpfe (`sp.kopf`) | feste Literale der Spaltenliste, absichtlich Markup (`Sekundär<br>Transport`) |
| `assets/missiontable.js` | 309 | **Einsatzzeile** | Fundstelle F-20 — behoben (S22-1) |
| `assets/missiontable.js` | 286, 304 | Leeren (`= ''`) | keine Daten |
| `index.php` | 333 | Einsatzzeile der Tagesübersicht | Katalogspalten `esc(t)`; `_col` aus fester Palette `COLORS`; die drei geschützten Spalten über `zelleGeschuetzt()` |
| `index.php` | 317, 652 | Leeren | keine Daten |
| `einsatz.php` | 314 | `dlZeile()` — Rumpf für alle Zeilen | **alle 12 Aufrufer** maskieren (s. 1.4) |
| `einsatz.php` | 373 | Kopfzeile | `esc()`; Kennzeichen aus Nachschlagetabellen `ORIGIN_LABEL`/`ORIGIN_KLASSE` |
| `einsatz.php` | 417 | Besatzung | `esc(c.label)`, `esc(c.name)` |
| `einsatz.php` | 461, 483 | Phasen, Reanimation | `esc(label)`; `time` serverseitig aus DATETIME formatiert |
| `einsatz.php` | 479 | Tabellenkopf | festes Markup |
| `suche.php` | 449, 459 | Auswahlfelder | feste Optionen |
| `suche.php` | 433 | Leeren; gefüllt wird über `option.textContent` | keine Daten im Markup |
| `zeitraum.php` | 394 | Hinweis „neutrale Diensttage" | Zahl + festes Markup |
| `zeitraum.php` | 262 | Leeren | keine Daten |
| `einsatz_form.php` | 1749, 1780 | Leeren; gefüllt über `createTextNode`/`textContent` | keine Daten im Markup |
| `assets/ortsfeld.js` | 183, 216, 281, 313 | Leeren; gefüllt über `createTextNode`/`textContent` | keine Daten im Markup |
| `assets/confirm.js` | 55 | Bestätigungsdialog | festes Markup; Text über `textContent` |
| `assets/unlock.js` | 44 | Entsperrdialog | festes Markup |
| `assets/map_fullscreen.js` | 77 | Vollbild-Symbol | festes Inline-SVG |

12 der 32 Stellen sind reines Leeren (`innerHTML = ''`) und tragen überhaupt
keine Daten.

### 1.2 Abgleichs-/Vorschauansicht — ausdrücklich nachgesehen

Sie ist der vom Auftrag genannte zweite Angriffsweg: Sie zeigt Angaben, bevor
irgendetwas gespeichert ist. Alle vier Bausteine maskieren:

- `zelle()` — der **rohe Zellenwert der Datei** steht in einer
  Attributposition (`<input value="…">`) und geht durch `esc()`; die
  Fehlermeldung im `title` ebenso.
- `zeileHtml()` — `title` der Zeile über `esc()`; `z.srcRow` ist ein
  Zeilenzähler (Zahl).
- `aktionZelle()` — `esc(dup.grund)` im `title`.
- Die Kopfzeile der Tagesgruppe — `esc(t.day)`, `esc(crewText)`, `esc(konflikt)`.

Dass die Attributpositionen halten, liegt an `EdHtml.escape` (Baustein B7): Es
maskiert **fünf** Zeichen, also auch beide Anführungszeichen. Die vor Web 4.6.0
verstreuten Fassungen mit drei Zeichen hätten hier nicht getragen — die
Zusammenführung in `assets/html.js` ist die Voraussetzung dieses Urteils.

Die Ansicht entschlüsselt zwar Bestandsdaten (`bestandEinsatznummernIndex()`),
zeigt davon aber nichts: Aus den entschlüsselten Blobs wird ausschließlich die
Einsatznummer gelesen und als Schlüssel eines Index für die Dublettenprüfung
benutzt. Sie erreicht keine Ausgabestelle.

### 1.3 Ergebnis der Übernahme — die einzige Stelle mit unmaskierten Werten

`import_ui.js:692` setzt Zähler ohne `esc()` ein: `missions_inserted`,
`missions_overwritten`, `missions_skipped`, `days_inserted`, `days_updated`
sowie die Anzahl je verworfener Ursache. Sie stammen **nicht** aus der Datei,
sondern sind in `api/import_commit.php` hochgezählte PHP-Ganzzahlen. Die
Textanteile daneben — Ursachenschlüssel und `first_day` — gehen durch `esc()`.
Kein Fund, aber die Stelle ist die einzige, an der überhaupt etwas Unmaskiertes
steht; sie ist hier genannt, damit die Aussage nachprüfbar bleibt.

### 1.4 Nachgezogen: wo importierte Werte sonst sichtbar werden

Der Auftrag fragt nach dem Importpfad; ein importierter Wert wird aber vor
allem **nach** dem Import sichtbar. Deshalb mitgelesen:

- **Einzelansicht** (`einsatz.php`, `zeigePat()`): Einsatznummer, Name,
  Geburtsdatum, **Alter**, Einsatzort, Beschreibung und Diagnose — alle sieben
  über `esc()`. Auch `alterText()` läuft durch `esc()`; der Weg über die
  Einzelansicht war nie offen.
- **Klartextfelder des Imports** (`transport_dest`, `bw_unit`, `bw_info`,
  `other_ema`, `notes`, Besatzungsnamen, `resources`): sichtbar in der
  Zusatzfeldliste (`esc(f.label)`, `esc(f.value)`), in den Auswahlfeldern der
  Suche (`option.textContent`) und in den Chips des Formulars
  (`createTextNode`). Keine Fundstelle.
- **Papierkorb, Nachbearbeitung, Export**: keine HTML-Ausgabestelle
  (`export.js` schreibt Dateien und benutzt `xmlEscape()`).

### 1.5 Nebenbefunde — nicht angefasst (Gebiet Backlog Nr. 21)

- `import_ui.js:692` mischt Markup und Zahlen in einer langen Verkettung; das
  ist lesbar, aber die Stelle, an der die nächste Zeichenkette unbemerkt
  unmaskiert dazukäme. Kandidat für Nr. 21, kein Fehler heute.
- `zeileHtml()` setzt `z.srcRow` zweimal unmaskiert ein. Es ist ein Zähler und
  kann nichts anderes sein — genannt der Vollständigkeit halber.

---

## 2. Was maschinell geprüft wurde

| Mittel | Umfang | Ergebnis |
|---|---|---|
| `node --check` | `server/assets/missiontable.js` | fehlerfrei |
| `php -l` | `server/index.php` | „No syntax errors detected" |
| Chromium (Playwright 1.56.1), `tools/maskierungs-probe/pruefe.mjs` | 6 Fälle × 2 Stände = 12 gezeichnete Zeilen | s. u. |

**Vorher/Nachher im Browser** (Nachweis 2 und 3 des Auftrags). Die Probe hält
den Stand 7.2.0 als wörtliche Kopie neben die echten Dateien des Stands 7.2.1
und zeichnet dieselben Fälle zweimal:

- **Angriffswert im Altersfeld** (`47<img src=x onerror="…">`):
  vorher **1** ausgelöste Nutzlast und **1** eingehängtes `<img>`;
  nachher **0** und **0** — der Wert steht als Text in der Zelle.
- **Gegenprobe Normalfall:** Das Zellen-HTML ist für `47`, für den leeren Wert
  (Gedankenstrich), für `0` und für den unlesbaren Fall (Warnzeichen)
  **zeichengleich** zwischen 7.2.0 und 7.2.1 — 4 von 4 Fällen.
- **Einsatzort und Diagnose** mit demselben Angriffswert: vorher wie nachher
  zeichengleich als Text. Die Umstellung hat an ihnen nichts verändert.
- Konsolenfehler: keine (der `ERR_FILE_NOT_FOUND` des Bildes `src=x` ist der
  Auslöser der Nutzlast selbst und gehört zum Aufbau).

**Grenze des Prüfmittels:** Die Probe misst gezeichnetes Markup, keinen
Bedienzustand und keinen echten Datenweg. Sie beweist, dass `zelleGeschuetzt()`
maskiert — nicht, dass ein Import ankommt.

`<script>` ist bewusst nicht die Nutzlast: `innerHTML` führt eingefügte
`<script>`-Elemente nicht aus. Das verharmlost die Lücke nicht; `onerror` und
verwandte Attribute laufen sehr wohl, und genau das zeigt die Probe.
