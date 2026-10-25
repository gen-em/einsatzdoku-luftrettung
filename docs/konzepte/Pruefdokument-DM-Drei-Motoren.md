# Prüfdokument DM — Jede Browserprobe in drei Engines

Zu Konzept DM (Schritt 19, R87), **abgeschlossen am 20.10.2026**. Das
Konzept ist gelöscht; sein letzter Stand liegt in `3bedc25`
(`git show 3bedc25:docs/konzepte/Konzept-DM-Drei-Motoren.md`). Dieses
Dokument beantwortet „was muss **ich** noch tun?" und bleibt, bis die
Prüfliste abgehakt ist (offen: P-DM-02, -03, -06; Rahmenplan 6.1). Die
Prüfberichte stehen in den Nachrichten der Commits von DM-05, F-DM-12 und
F-DM-13, der erste volle Lauf `neben` in der von `a5e4d556` (Nr. 393).

## 1. Was nicht geprüft werden konnte

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Der Prüfbericht dieses PR fährt die Browserproben nur in Chromium.** | Der PR ist Stufe `klein` (Werkzeuge und Web 23.0.1, eine Korrektur), und dort laufen sie seit E-DM-08 in Chromium allein. Firefox und WebKit sind einzeln gefahren (Abschnitt 2), nicht im Bericht. | **Nachgeholt am 25.10.2026** (P-DM-04, Nr. 393): voller Lauf `neben`, 57 von 57 in drei Engines. |
| **Ein echter Safari.** | Playwrights WebKit ist die Engine, nicht der Browser (E-DM-04). | P-DM-03. |
| **Das Nachholen der fünf neuen WebKit-Bibliotheken aus einem Abbild, dem sie fehlen.** | Sie lagen nach dem ersten Lauf vor; sie zu entfernen hätte abhängige Pakete mitgerissen (F-DM-01). Auch der frische Container vom 20.10.2026 (P-DM-05) brachte sie schon mit: `aufbauen.sh` installierte nichts. | Erst ein Abbild, dem sie fehlen. Dann zeigt der Sitzungshook „FEHLT webkit" oder lädt nach. |
| **Ein Seitenwechsel nach einem Klick, an dem WebKit stolpert** (`waitForNavigation`). | Der Klick wird weiter nicht wiederholt (E-DM-10). **Seit Nr. 393 (25.10.2026)** fährt die Bedienprobe aber einen Weg, der in WebKit an einer Zeitüberschreitung scheitert, einmal ganz von vorn, und sie meldet eine Rolle neu an, deren Seite stumm geworden ist — beides gezählt. Gemessen: Die stumme Seite schickt keine Anfrage und rechnet nicht; der Grund liegt in WebKit, nicht in der Anwendung. | Im Bericht die Zeile „Wiederholungen in WebKit"; im Protokoll der Probe `WebKit-Wiederholung:` und `WebKit-Neuanmeldung:`. |
| **`ap4a-kurzname-im-band` in Firefox im vollen Lauf nach der Änderung** (F-DM-10). | **Erledigt 25.10.2026:** im vollen Lauf `neben` grün (Bedienprobe in Firefox 77 von 77). Der Fehlklick kam von `rollenVorschau()`, die die Rollenfelder über dem Knopf neu baut (`ap4a.mjs`). | — |
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
| `pruefen.sh` (Stufe klein) | der ganze Stand | Bericht in der Nachricht des Commits DM-05; nach F-DM-13 erneut 23 grün, 0 rot (`3bedc25`) |
| `pruefen.sh --stufe neben` (25.10.2026, Nr. 393, `a5e4d556`) | der ganze Stand in drei Engines (P-DM-04) | 57 grün, 0 rot, 0 nicht gemessen, 8836 s; `motoren=chromium+firefox+webkit`; Bedienprobe je 77 von 77; 2 Zeilen „Wiederholungen in WebKit", beide an einem Weg (`p5c-ap8-adhoc-tag-in-der-luft`: Seite stumm, Rolle neu, Weg im zweiten Versuch grün). Davor vier volle Läufe mit roten Proben; ihre Funde lagen im Werkzeug (CHANGELOG „Werkzeug: Playwright 1.64.1") |
| Stufe 2, Lauf 133 (20.10.2026, `ubuntu-24.04`) | Kreislauf `edbak` und Rückwegprobe gegen Staging je Engine (P-DM-01) | Kreislauf 0 unerklärte Abweichungen in Chromium, Firefox und WebKit (3:10 min); Rückwegprobe je 21 von 21 ok (8:24 min); Job 11:41 min; Fassungen 141.0.7390.37, 142.0.1, 26.0; keine WebKit-Wiederholung in 1724 Protokollzeilen. Davor Lauf 131 und 132 auf 26.04 rot, bevor sie maßen (F-DM-12, -13) |

## 3. Prüfliste

| Nr. | Wann | Weg | Erwartet | Gescheitert, wenn |
|---|---|---|---|---|
| P-DM-01 | **erledigt 20.10.2026** (Lauf 133, Abschnitt 2) | Actions → Auslieferung, Job „Prüfung Stufe 2": Schritte „Kreislauf edbak gegen Staging" und „Rückwegprobe gegen Staging" lesen | je Engine ein Abschnitt `--- … · chromium/firefox/webkit ---`, alle grün; die Zusammenfassung sagt „alle grün"; Dauer unter 40 min | „rot in webkit" o. ä.; „Host system is missing dependencies"; Zeitgrenze. Dann: Protokoll an mich, der Job ist der einzige Ort, der es zeigt |
| P-DM-02 | nach dem Merge, Staging | Registrierung kurz öffnen (Verwaltung → Registrierung, Betriebsart „offen"), in Firefox `registrieren.php` öffnen, ein paar Sekunden warten, Knopf „Konto anlegen" | Der Knopf ist sofort frei, keine Zeile „Sicherheitsprüfung läuft …" | Die Zeile steht mehr als eine Sekunde da; danach Betriebsart zurückstellen |
| P-DM-03 | wenn ein Apple-Gerät zur Hand ist | wie P-DM-02, in Safari (iPhone oder Mac) | wie P-DM-02 | wie P-DM-02, oder „Dein Browser kann die Sicherheitsprüfung nicht rechnen" |
| P-DM-04 | **erledigt 25.10.2026** (`a5e4d556`, Abschnitt 2) — der erste Prüfstand einer Nebenstufe nach dem Merge | im Bericht | `motoren=chromium+firefox+webkit`; keine Probe `rot:…`; Zeilen „Seitenwechsel wiederholt" höchstens einzeln | `rot:firefox` oder `rot:webkit` bei einer Probe — dann Protokoll lesen |
| P-DM-05 | **erledigt 20.10.2026** — frischer Container, `aufbauen.sh web`: ok chromium 141.0.7390.37, firefox 142.0.1, webkit 26.0 (das Nachladen selbst nicht, Abschnitt 1) | Startausgabe des Sitzungshooks | „ok chromium / firefox / webkit" je mit Fassung | „FEHLT webkit" mit „missing dependencies" — dann fehlt eine Bibliothek in `aufbauen.sh` |
| P-DM-06 | nach dem Tag `web-v23.0.1` | wie P-DM-02 auf Produktiv, nur wenn die Registrierung dort offen ist | wie P-DM-02 | wie P-DM-02 |

## 4. Grenzen der Prüfmittel

- Die Regel `motoren` erkennt einen Browserstart an `.launch(`, `launchPersistentContext(` und `starten(`; einen Start über eine Hilfsfunktion in einer anderen Datei sieht sie nicht.
- Die WebKit-Wiederholung greift nur bei `goto` und `reload` und nur bei genau dem Wortlaut „WebKit encountered an internal error".
- Die Wiederholung eines ganzen Wegs (Nr. 393) gibt es nur in der Bedienprobe, nur in WebKit und nur nach einer geworfenen Zeitüberschreitung; ein falsches Ist wird nicht wiederholt. Ein Fehler der Anwendung, der nur jedes zweite Mal auftritt, kann sie verdecken — die Zeile im Bericht ist der Hinweis darauf.
- Die Zeitmessung der Rechenaufgabe ist ungedrosselt, auf diesem Rechner; das alte Diensthandy ist nicht gemessen (P-SR-14 bleibt).
- `paarAbwarten()` wartet höchstens 20 s und nur, wenn die Seite `KP_STAND` kennt; der Befund an der Anwendung bleibt Nr. 392.
