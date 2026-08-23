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

---

## 3. Keyguard-Einträge beim Abmelden (S22-3)

**Frage 1: Was steht in `pckb`/`pckt`, wo liegen sie, tragen sie
Schlüsselmaterial oder davon Ableitbares?**

Beide liegen im `sessionStorage` des Tabs — also je Tab getrennt und nur bis zu
dessen Ende.

- **`pckb`** ist das Ergebnis von `EdCrypto.wrapFingerprint(wrap)`: die ersten
  16 Hexzeichen (64 Bit) eines SHA-256 über `'edk-wrap:' + wrap`. `wrap` ist
  die **Hülle** des Inhaltsschlüssels — nicht der Schlüssel. Und die Hülle ist
  kein Geheimnis: Der Server schreibt sie jeder Seite als `PAT_WRAP` bzw.
  `pat_wrap` mit. `pckb` ist damit ein gekürzter Einwegwert über eine ohnehin
  offen ausgelieferte Angabe. Selbst wer ihn umkehren könnte, hätte die Hülle —
  und die ist ohne den Datenschlüssel wertlos.
- **`pckt`** ist ein Zeitstempel in Millisekunden (`Date.now()`), gesetzt beim
  Entpacken. Er steuert die Frist von 30 Minuten.

**Antwort: nein.** Kein Schlüsselmaterial, nichts davon Ableitbares. Was sie
verraten, ist Nutzungsspur — dass in diesem Tab gearbeitet wurde und wann.

Auch die Ausnutzbarkeit ist geprüft: `contentKey()` sieht `pckb` erst an,
**nachdem** `sessionStorage.getItem('pck')` etwas geliefert hat. Nach dem
Abmelden ist `pck` fort, der Zweig wird also gar nicht erreicht; beim nächsten
Entpacken werden beide Fächer überschrieben. Ein stehengebliebener `pckb` kann
keinen fremden Schlüssel durchreichen.

**Nach Ziffer 3 des Auftrags wird deshalb nichts geändert.** Die toten Exporte
`EdKeyGuard.beenden()`/`raeumen()` bleiben unberührt liegen (Nr. 21).

**Frage 2: Ist die Erwartung aus V-10 heute überhaupt erfüllt?**

Sie war es **nicht** — aber aus einem anderen Grund als vermutet. Die
vollständige Erhebung der Fächer (`assets/crypto.js`, `assets/keyguard.js`,
`einstellungen.php` — sechs Fächer, mehr führt das Projekt nicht) förderte
`edk_neu` zutage:

`einstellungen.php` legt beim Passwortwechsel den **neuen Datenschlüssel** unter
`edk_neu` ab und löst das Fach beim nächsten Aufruf desselben Reiters wieder auf
(M2-07). Kommt dieser Aufruf nie — die Übertragung bricht ab, die Nutzerin geht
zurück oder meldet sich ab —, blieb ein vollwertiger Datenschlüssel liegen, und
zwar über das Abmelden hinaus: `EdCrypto.clearSession()` kannte nur `edk`, `pck`
und `edkvor`. Genau das verbietet V-10.

**Behoben** mit einer Zeile in `clearSession()`. Auf dem auflösenden Weg ändert
sie nichts: Dort wird `edk_neu` ausgelesen und entfernt, **bevor**
`clearSession()` läuft. Der tote Code in `keyguard.js` wurde dafür nicht
angefasst.

**Beleg im Browser** (`tools/abmelde-probe/pruefe.mjs`, Chromium, echte
`crypto.js`): Alle 6 Fächer belegt, dann der Abmeldeweg darüber.

| Stand | übrig | davon Schlüsselmaterial |
|---|---|---|
| Web 7.2.0 | `edk_neu`, `pckb`, `pckt` | **`edk_neu`** — V-10 verletzt |
| Web 7.2.1 | `pckb`, `pckt` | **keines** — V-10 erfüllt |

Seitenfehler: keine.

**Grenze des Prüfmittels:** Die Probe füllt die Fächer selbst und ruft
`clearSession()` direkt auf. Sie beweist, dass der Baustein räumt — nicht, dass
`logout.php` ihn erreicht. Dass er erreicht wird, ist gelesen
(`session_lib.php` liefert eine kurze Seite aus, die `crypto.js` lädt und
`EdCrypto.clearSession()` aufruft), aber nicht am laufenden System gemessen;
das bleibt der Betreiberin (Prüfliste, Punkt P-2).

---

## 4. Backlog und Version (S22-4)

- **Nr. 17 berichtigt.** Die Zuordnung „an P1/P2 übergeben" ist ersetzt durch
  **P5** (Rahmenplan R19), mit dem Zusatz, dass P1 nur das Aufrufverhalten
  misst und keine Grenze festlegt.
- **Nr. 22 unter *Erledigt*** eingetragen, mit Fundstellen
  (`server/assets/missiontable.js`, `server/assets/import.js`,
  `server/index.php`, `server/assets/crypto.js`) und Version (Web 7.2.1).
- **Version** `server/version.php` auf `7.2.1`, mit fortgeschriebener
  Erzählung im Kopfkommentar. **Changelog** ergänzt, mit dem Hinweis, dass die
  Lücke seit Web 5.2.0 bestand und P0 sie weder verursacht noch verschärft,
  sondern gefunden hat.
- **`docs/Technik.md`**: die beiden neuen Proben in der Verzeichnisübersicht
  nachgetragen.

**Nicht erledigt — und warum:** `Konzept-P0-Aufraeumen.md` (F-16- und
F-20-Zeile nachziehen) liegt **nicht in diesem Repositorium**. Die
Konzeptdokumente entstehen nach `CLAUDE.md`, Abschnitt 7, in einer getrennten
Sitzung und werden hier nicht geführt; eine Suche über den gesamten Baum
findet keine Datei dieses Namens. Der Nachzug bleibt offen und muss dort
erfolgen, wo das Dokument liegt. Zu übernehmen ist:

- **F-20-Zeile:** behoben mit Web 7.2.1 (Sofortpaket Nr. 22), Maskierung in
  `zelleGeschuetzt()`; Durchsicht des Importpfads ohne weiteren Fund.
- **F-16-Zeile:** Zuständigkeit **P5** statt P1/P2 (Rahmenplan R19).

---

## 5. Was der Betreiberin bleibt — abhakbare Prüfliste

Zwei Punkte. Beide brauchen ein laufendes System mit Anmeldung, Datenbank und
entsperrtem Inhaltsschlüssel; keiner davon lässt sich hier nachstellen.

### ☐ P-1 — Import mit Angriffswert im Altersfeld

**Bedienweg.** Eine Importdatei nach einem der vorhandenen Profile anlegen und
in die Alterspalte einer Zeile statt einer Zahl diesen Text setzen:

    47<img src=x onerror="alert('offen')">

Datei über *Import* einlesen, bis zur Abgleichsansicht gehen, übernehmen. Dann
den betroffenen Tag in der **Tagesübersicht** öffnen, danach dieselbe Zeile in
der **Suche** und in der **Zeitraum-Übersicht** ansehen, zuletzt den Einsatz
selbst.

**Erwartet.** In allen drei Tabellen steht in der Spalte *Alter* der Text
`47<img src=x onerror="alert('offen')">` — sichtbar, unverändert, als Text. In
der Abgleichsansicht steht er im Eingabefeld der Alterspalte. In der
Einzelansicht steht er in der Zeile *Alter 🔒*.

**Woran ein Scheitern zu erkennen ist.** Es erscheint ein Meldungsfenster
(„offen") — dann greift die Maskierung nicht. Ebenfalls Scheitern: In der
Spalte steht **nur** `47` und der Rest fehlt; dann ist das Markup in die Seite
gewandert und nur unsichtbar geblieben. Ein leeres Feld oder ein
Gedankenstrich ist auch ein Fehler — der Wert soll erhalten bleiben, nicht
verschwinden.

**Danach aufräumen:** Den Probeeinsatz löschen. Er ist ein echter Datensatz.

### ☐ P-2 — Abmelden räumt den sessionStorage

**Bedienweg.** Anmelden, geschützte Angaben entsperren (einen Einsatz mit
Diagnose öffnen). Dann in den Entwicklerwerkzeugen des Browsers unter
*Application → Session Storage* nachsehen, danach **Abmelden** und dieselbe
Stelle erneut ansehen. Der Tab muss dabei offen bleiben — beim Schließen wird
der Speicher ohnehin geleert, das beweist nichts.

**Erwartet.** Vor dem Abmelden liegen dort `edk`, `pck`, `pckb`, `pckt`. Nach
dem Abmelden sind `edk` und `pck` **fort**; `pckb` und `pckt` bleiben stehen —
das ist beabsichtigt (Abschnitt 3).

**Woran ein Scheitern zu erkennen ist.** `edk` oder `pck` stehen nach dem
Abmelden noch da. Häufigste Ursache wäre eine blockierte Skriptausführung auf
der Abmeldeseite: Sie räumt per JavaScript, weil eine Weiterleitung per
Kopfzeile nie Skript ausführt.

**Zusatz, nur wenn gerade ohnehin ein Passwortwechsel ansteht:** Nach
*Einstellungen → Passwort ändern* darf **kein** Fach `edk_neu` übrig bleiben —
weder nach dem Wechsel noch nach einem abgebrochenen Versuch, und erst recht
nicht nach dem Abmelden. Genau dieser Rest ist mit 7.2.1 behoben.

---

## 6. Grenzen der benutzten Prüfmittel

- **`node --check` und `php -l`** prüfen die Rechtschreibung des Codes, nicht
  seinen Sinn. Sie hätten die Lücke nie gefunden.
- **Die Browserproben** laufen gegen nachgebautes Markup und selbst gefüllten
  Speicher. Sie belegen das Verhalten der geänderten Bausteine, nicht den Weg
  dorthin: kein Import, keine Anmeldung, keine Datenbank.
- **Die Durchsicht in Abschnitt 1 ist eine Lesung.** Sie ist vollständig über
  die genannten Muster — aber vollständig heißt „alle Stellen gefunden, die
  diese Muster treffen". Ein Ausgabeweg, der keines davon benutzt, wäre nicht
  darunter. Gesucht wurde deshalb zusätzlich nach den bekannten Alternativen
  (`srcdoc`, `.html()`, `eval`, `new Function`, `createContextualFragment`,
  `setHTML`, `javascript:`); sie kommen im Projekt nicht vor.
- **Es gibt keine automatisierten Tests** (`CLAUDE.md`, Abschnitt 6). Was hier
  nicht gemessen oder gelesen wurde, ist nicht geprüft.
