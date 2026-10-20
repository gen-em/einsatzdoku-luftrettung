# Prüfdokument DM — Jede Browserprobe in drei Engines

Zum Konzept `Konzept-DM-Drei-Motoren.md` (Schritt 19, R87). Es beantwortet
„was muss **ich** noch tun?"; das Prüfprotokoll im Konzept beantwortet „ist
es belegt?". Der Prüfbericht des Prüfstands steht in der Nachricht des
letzten Commits (DM-05).

## 1. Was nicht geprüft werden konnte

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Stufe 2 dreifach auf dem Läufer** (`ubuntu-26.04`, die Engines und die Bibliotheken aus `install-deps --dry-run` mit Namensersetzung, F-DM-12; Kreislauf und Rückwegprobe je Engine, Zeitgrenze 40 min). | Stufe 2 läuft nur nach dem Merge auf `main`; gegen Staging wird von hier aus nur gelesen (E-PK-29). Ob WebKit auf 26.04 mit Playwright 1.56 startet, ist offen — Playwright kennt 26.04 nicht (Nr. 236). | P-DM-01. Rot mit „Host system is missing dependencies" oder „Kreislauf edbak gegen Staging in webkit gescheitert". |
| **Der Prüfbericht dieses PR fährt die Browserproben nur in Chromium.** | Der PR ist Stufe `klein` (Werkzeuge und Web 23.0.1, eine Korrektur), und dort laufen sie seit E-DM-08 in Chromium allein. Firefox und WebKit sind einzeln gefahren (Abschnitt 2), nicht im Bericht. | P-DM-04: der erste Prüfstand einer Nebenstufe. |
| **Ein echter Safari.** | Playwrights WebKit ist die Engine, nicht der Browser (E-DM-04). | P-DM-03. |
| **Das Nachholen der fünf neuen WebKit-Bibliotheken aus einem Abbild, dem sie fehlen.** | Sie lagen nach dem ersten Lauf vor; sie zu entfernen hätte abhängige Pakete mitgerissen (F-DM-01). | P-DM-05: eine frische Sitzung. |
| **Ein Seitenwechsel nach einem Klick, an dem WebKit stolpert** (`waitForNavigation`). | Wird nicht wiederholt (E-DM-10); er bleibt rot. Im vollen WebKit-Lauf der Bedienprobe waren es 2 von 3 Ausfällen. | P-DM-04, falls WebKit dort rot ist: Protokoll lesen, ob es „internal error" war. |
| **`ap4a-kurzname-im-band` in Firefox im vollen Lauf nach der Änderung** (F-DM-10). | Einzeln grün; der volle Firefox-Lauf danach fehlt. | P-DM-04. |
| **Web 23.0.1 auf Staging und Produktiv.** | Kommt mit dem Merge bzw. dem Tag. | P-DM-02, P-DM-06. |
| **Die elf Browserwerkzeuge, die kein Prüfstand ruft** (Nr. 391). | Außerhalb dieses Schritts; sie können drei Engines, gefahren hat sie niemand. | Backlog Nr. 391. |

## 2. Maschinell geprüft

| Mittel | Was es misst | Zahl |
|---|---|---|
| `aufbauen.sh web` nach Beiseitelegen von Firefox und WebKit | Nachladen der Engines | „nachladen: firefox webkit"; Chromium 141.0.7390.37, Firefox 142.0.1, WebKit 26.0; rc 0; 12 s |
| `bestand.py --selbstprobe` | Regel `motoren` | 177 Fälle, 0 Fehlschläge, 110 von 110 Befundstellen |
| `bestand.py` am Bestand, mit Gegenprobe | Register gegen Werkzeuge | 0 Befunde; Zeile gestrichen → `motoren-ohne-zeile` + `motoren-verwaist`; `chromium.launch()` eingesetzt → `motoren-direkt` |
| `bericht.py lesen --selbstprobe` | Lage 6 je Stufe | 18 Lagen, 0 Fehlschläge (14 rote, 4 grüne) |
| `aufnehmen.mjs --selbstprobe` | Rauschklassen des Bilderlaufs | 17 von 17 (neu 16: abgebrochener Schriftabruf ist Rauschen; 17: anderer Status bleibt Fehler) |
| `harmloserResizeHinweis()` | WebKit-Hinweis | 3 von 3 (genau der Wortlaut; „loop limit exceeded" und ein TypeError bleiben Fehler) |
| WebKit-Wiederholung, nachgebaute Seite | E-DM-10 | „internal error" → zweiter Versuch (2 Aufrufe); `ERR_CONNECTION_REFUSED` → geworfen (1 Aufruf); echte Seite lädt |
| SHA-256 des Workers gegen Node | Web 23.0.1 | 1505 Eingaben (0 bis 300 Bytes, je 5), 0 Abweichungen |
| Hashrate, 65 536 Hashes | Ursache von Q-DM-01 | WebCrypto 64er-Bündel: Chromium 1008, Firefox 1317, WebKit 522 ms · eigene Rechnung 428, 243, 166 ms |
| Bedienprobe, Weg `registrieren`, je 9 Läufe | Soll Median < 1 s | Chromium 516, Firefox 416 (vorher 1365/544/1333), WebKit 319 ms; je Konto `unbestaetigt`, 0 Konsolenfehler |
| Einzelläufe Firefox und WebKit | die Proben | frist, abmelden, container, huelle, gpx, kontopaar, ortsdaten, wartungslauf je 2 von 2 · freigabe 3 von 3 (23/23) nach F-DM-02 · csp-browser 3 von 3 (37/37) nach F-DM-03 · rückweg 3 von 3 · Kreislauf `csv` 0 unerklärt von 11 234 (Chromium, Firefox, WebKit) · Kreislauf `edbak` 0 unerklärt von 347 566 (Firefox, WebKit) · Stilvergleich rc 0 (Firefox, WebKit) |
| Bedienprobe voll | 77 Wege | Firefox 66 → 73 → 75 von 77 (dann Q-DM-01 behoben, F-DM-10 nachgebessert), WebKit 66 → 74 von 77 (die drei: „internal error", E-DM-10) |
| Bilderlauf klein | 258 Bilder | Firefox 1 Konsolenfehler (F-DM-08, behoben); WebKit 3 Seitenfehler (F-DM-09, behoben) + 1 Bild ohne Abzug, einzeln nachgefahren ohne Befund |
| Seitenhöhe Nachtdienst bei 1920 px | das fehlende Bild | Chromium 1834, Firefox 1835, WebKit 1802 px |
| Riegel einzeln | vor dem Prüfstand | bestand, textprobe, vollstaendigkeit, linkprobe, kennzeichnung, handbuch ok; 21 Decken, 0 gerissen; kettenaufrufe ohne Widerspruch; zaehlung ok |
| `pruefen.sh` (Stufe klein) | der ganze Stand | Bericht in der Nachricht des Commits DM-05 |

## 3. Prüfliste

| Nr. | Wann | Weg | Erwartet | Gescheitert, wenn |
|---|---|---|---|---|
| P-DM-01 | nach dem Merge | Actions → Auslieferung, Job „Prüfung Stufe 2": Schritte „Kreislauf edbak gegen Staging" und „Rückwegprobe gegen Staging" lesen | je Engine ein Abschnitt `--- … · chromium/firefox/webkit ---`, alle grün; die Zusammenfassung sagt „alle grün"; Dauer unter 40 min | „rot in webkit" o. ä.; „Host system is missing dependencies"; Zeitgrenze. Dann: Protokoll an mich, der Job ist der einzige Ort, der es zeigt |
| P-DM-02 | nach dem Merge, Staging | Registrierung kurz öffnen (Verwaltung → Registrierung, Betriebsart „offen"), in Firefox `registrieren.php` öffnen, ein paar Sekunden warten, Knopf „Konto anlegen" | Der Knopf ist sofort frei, keine Zeile „Sicherheitsprüfung läuft …" | Die Zeile steht mehr als eine Sekunde da; danach Betriebsart zurückstellen |
| P-DM-03 | wenn ein Apple-Gerät zur Hand ist | wie P-DM-02, in Safari (iPhone oder Mac) | wie P-DM-02 | wie P-DM-02, oder „Dein Browser kann die Sicherheitsprüfung nicht rechnen" |
| P-DM-04 | der erste Prüfstand einer Nebenstufe nach dem Merge | im Bericht | `motoren=chromium+firefox+webkit`; keine Probe `rot:…`; Zeilen „Seitenwechsel wiederholt" höchstens einzeln | `rot:firefox` oder `rot:webkit` bei einer Probe — dann Protokoll lesen |
| P-DM-05 | die nächste frische Sitzung | Startausgabe des Sitzungshooks | „ok chromium / firefox / webkit" je mit Fassung | „FEHLT webkit" mit „missing dependencies" — dann fehlt eine Bibliothek in `aufbauen.sh` |
| P-DM-06 | nach dem Tag `web-v23.0.1` | wie P-DM-02 auf Produktiv, nur wenn die Registrierung dort offen ist | wie P-DM-02 | wie P-DM-02 |

## 4. Grenzen der Prüfmittel

- Die Regel `motoren` erkennt einen Browserstart an `.launch(`, `launchPersistentContext(` und `starten(`; einen Start über eine Hilfsfunktion in einer anderen Datei sieht sie nicht.
- Die WebKit-Wiederholung greift nur bei `goto` und `reload` und nur bei genau dem Wortlaut „WebKit encountered an internal error".
- Die Zeitmessung der Rechenaufgabe ist ungedrosselt, auf diesem Rechner; das alte Diensthandy ist nicht gemessen (P-SR-14 bleibt).
- `paarAbwarten()` wartet höchstens 20 s und nur, wenn die Seite `KP_STAND` kennt; der Befund an der Anwendung bleibt Nr. 392.
