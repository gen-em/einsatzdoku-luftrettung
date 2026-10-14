# Prüfdokument S11 — Ortsdaten verschlüsselt (Weg B)

*Gehört zu `Konzept-S11-Ortsdaten-Weg-B.md`. Beantwortet „was muss **ich**
noch tun?" — das Protokoll „ist es belegt?" steht im Statusblock und in
Abschnitt 6 des Konzepts. Angelegt am 11.10.2026 mit S11-00 (Opus); jedes
Paket füllt es mit Mittel **und** Zahl. Stand: **Konzept freigegeben am
11.10.2026** (P-S11-01); **S11-01 erledigt** (Grundlinie); **S11-03 erledigt** (13.10.2026, Web 22.0.0); **S11-04 erledigt** (14.10.2026, Web 22.0.4) — ohne Bilderlauf-Vergleich und Messung, die mit S11-10/-11 kommen (E-S11-41); als Nächstes S11-05.*

---

## 1. Was nicht geprüft werden konnte — und warum

Steht vor allem anderen. Was dazukommt, gehört hierher — an den Anfang.

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Ob Einsatz und Diensttag dieselbe Karte zeigen wie vor S11-04, und wie schnell** (Abnahme von S11-04: Bilderlauf, Messung gegen S11-01). **Bedienprobe, Stilvergleich, Bilderlauf und GPX-Probe sind im Prüfstand rot** seit S11-04c. | Der Prüfbestand (Demo, Referenz) liegt bis zum Einmalwerkzeug im Klartext; ohne Übergang (E-S11-36) zeigen die Ansichten dort keine Karte, eine Messung hätte nichts geöffnet. Die BetreiberIn hat rote Berichte bis S11-10 angenommen und ein Vorab-Werkzeug abgelehnt (E-S11-41). | S11-10 (Bilderlauf, die vier Proben wieder grün) und S11-11 (Messung, E-S11-26). Bleibt eine davon dort rot, ist es ein Befund und keine Folge von E-S11-41 mehr. |
| **Wie der Ladeplatz bei Drossel 6× aussieht und ob V1 die Zeit verkürzt.** | Aus demselben Grund; die Ortsdatenprobe öffnet zehn Punkte, nicht einen Bestand. | S11-11. |
| **Die Browserliste gegen `CompressionStream`** (E-S11-21). | Es gibt keine Liste in `docs/Technik.md`, und S11-04 packt nicht (F-S11-38). | Im ersten Paket, in dem der Browser packt. |
| **Forerunner 945 und Venu 3s auf der echten Uhr.** Nur im Simulator (je 7 gut, 0 Fehler) und im Übersetzen geprüft; die Betreiberin nimmt an, dass sie wie die Fenix 6 Pro rechnen (E-S11-30). | Die Uhren standen am 13.10.2026 nicht zur Verfügung. | Rechnet eine anders, gehen ihre Hüllen im Browser nicht auf — sichtbar bei P-S11-05; S11-08 prüft die Byteordnung beim Start der App gegen einen eingebauten Vektor. |
| **Ob die echte Garmin-Uhr die Krypto kann.** Im **Simulator** gemessen (S11-02): Fenix 6 Pro, Forerunner 945, Venu 3s je 7 gut, 0 Fehler. Ob der Simulator rechnet wie die Uhr — gerade bei der Byteordnung des ECDH-Geheimnisses (F-S11-18) —, ist unbelegt. Zeiten und Speicher des Simulators sind die des Rechners. | Keine Uhr in der Arbeitsumgebung. | P-S11-02; Halt, bis das Ergebnis vorliegt. |
| **Komplett- und Schlüsselwechselprobe unter dem PHP 8.3.6 aus Ubuntu.** Dort scheitern drei Fälle, die ein Komplett-Siegel über Häppchen schreiben — mit und ohne S11-03 gleich (F-S11-26, -28, -33). | Kein Fehler von S11; unter PHP 8.3.33 und 8.4.19 grün. Die Ursache unter 8.3.6 ist nicht gefunden: Backlog Nr. 383. | Eine Anlage auf PHP 8.3.6 könnte eine Komplettsicherung schreiben, die nicht aufgeht. |
| **Der volle Einspiellauf des Referenzbestands mit Hüllen** (`einspielen.py`, S11-04a). | Er braucht eine leer eingerichtete Datenbank; `lokal_einrichten.sh` löscht den Bestand dieser Anlage, auf dem Messstand und Bilderlauf rechnen. Geprüft sind die beiden neuen Teile einzeln: der Umbau aller 638 Pakete und das Anlegen des Kontopaars. | Beim nächsten Neuaufbau einer Anlage (`hochfahren.sh --neu`) oder auf Staging: rot, wenn ein Paket mit `400` zurückkommt. |
| **Ob ein Gerät überhaupt senden kann.** Seit S11-04a weist der Server Klartext ab; Uhr und Handy verschließen erst ab S11-08/-09. | So geplant (E-S11-09); geprüft ist nur die Abweisung. | P-S11-05 nach der Hauptauslieferung. |
| **Firefox und WebKit.** Die Hüllenprobe lief nur in Chromium (39/0). | Beide fehlen im Abbild vom 11.10.2026 (F-S11-20): `/opt/pw-browsers` hält nur `chromium-1194`; die Probe bricht mit rc 2 ab. | S11-04 (Browserliste); bis dahin offen. |
| **Ob die Krypto auf den übrigen 96 Zielgeräten läuft.** | E-S11-16: Laufzeit nur auf Fenix 6 Pro, Forerunner 945, Venu 3s. Stufe I übersetzt für alle 99; Übersetzen ist nicht Laufen, und der kleinste App-Speicher (128 kB) liegt unter dem der drei. | Erst wenn ein anderes Gerät in Betrieb geht. |
| **Ob die Grundlinie für ein Telefon gilt.** S11-01 hat auf einem Container mit vier Kernen gemessen, einmal mit Drossel 6× (Nachbildung des Referenzgeräts Z3), einmal ohne. | Kein echtes Gerät in der Arbeitsumgebung; die Drossel bildet Rechenzeit nach, nicht Speicher, Grafik und Leitung. | Der Vergleich vorher/nachher in S11-11 gilt nur auf derselben Maschine (E-S11-15). |
| **Wartungslauf und Einmalwerkzeug haben kein Vorher.** | Es gibt sie erst mit S11-07 und S11-10. | Sie werden dort gemessen und benannt, nicht gegen eine Grundlinie gehalten. |
| **Die Befunde des Konzepts sind gelesen, nicht gemessen.** F-S11-01 bis -13 stammen aus der Lesung dreier Leser (Opus) und der Nachprüfung am Quelltext für F-S11-01 und -02 und in `docs/Backup-Format.md` für F-S11-05. | Kein Paket hat bisher Code ausgeführt. | F-S11-02 belegt S11-05 durch einen Durchgang im Browser; die übrigen zeigen sich in ihrem Paket. |

## 2. Maschinell geprüft

### S11-00 Buchführung (11.10.2026)

| Mittel | Was es misst | Zahl |
|---|---|---|
| `tools/steuerung/decken.py` | Decken von Rahmenplan, Verlauf und Backlog | 21 Decken, 0 gerissen; Backlog-Kopf 59 von 60 Zeilen |
| `tools/steuerung/uebersicht.py --ziel 12a` | Kopfzeilen, Zuordnung | 63 Einträge, 0 ohne Grammatik, 0 ohne Ziel, 4 zu Ziel 12a (Nr. 43, 53, 92, 161) |
| `tools/steuerung/nummern.py` | neue Backlog-Nummern gegen andere Zweige | 0 neue Nummern, 0 Überschneidungen gegen 8 Zweige |
| `tools/quelltext/textprobe.py` | Wortliste über alle Bereiche | 0 Treffer außerhalb der Ausnahmen, 111 von 111 Ausnahmen gegriffen, 0 durchgerutschte Fallen |
| `tools/quelltext/bestand.py` | Bestandsriegel | 0 Befunde |
| `tools/quelltext/linkprobe.py` | Verweise der Oberfläche | 150 Verweise, 0 Abweichungen |
| `tools/pruefstand/pruefen.sh --stufe klein` | Riegel und Proben der Berührung (Muster `steuerung`) | 22 grün, 0 rot, 0 nicht gemessen; der Bericht über den Baum dieses Commits steht in seiner Nachricht |

### S11-01 Messstand: Grundlinie (11.10.2026)

Gemessen auf Web 21.14.0, frische Anlage, 5096 Einsätze, 2 874 650 Spurpunkte;
Einzelheiten und Maschine in `tools/messstand/ausgangsmessung.md`
(„Grundlinie 11.10.2026").

| Mittel | Was es misst | Zahl |
|---|---|---|
| `messen.py --frisch` (alle Schritte, Drossel 6×) | ganze Kette: Konto, Bestand, Einspielen, Browser, Server, Statistik | rc 0; die Kette lief zweimal, der erste Lauf scheiterte im neuen GPX-Schritt am Schalter (verborgenes Kästchen, behoben) |
| Schritt `browser`, Drossel 6× | Einsatz 141 (1356 Punkte) · Diensttag 30 (8 Ruhe-Segmente, 1471 Punkte) · Backup · GPX Jahr 2026 | Median 1,28 s (1,18–1,44) · Median 1,71 s (1,69–2,03) · 58,54 s, 12,01 MB · 329,6 s, 8174 Spuren, 31,95 MB, Halde 389 MB; 0 Konsolenfehler |
| Schritt `browser`, ungedrosselt | dieselben | Median 0,45 s (0,41–0,48) · Median 0,47 s (0,44–0,49) · 44,05 s · 74,62 s, Halde 392 MB; 0 Konsolenfehler |
| Schritt `server` | `edbak_build()` in Fenstern / am Stück | 3,8 s, Spitze 12 MB / 13,41 s, Spitze 2079 MB |
| Schritt `statistik` | drei Reiter, je mit und ohne Zeitraum | 0,07–0,08 s je Reiter, Index gewählt |
| Nachtrag: Schritt `browser`, 6× und ungedrosselt | Monat 03/2026 (588 Einsätze) · GPX Jahr mit/ohne Passwort, in Abschnitten · Wiederholung der Einzelansichten | sichtbar 2,38 s / 0,45 s · 329,6 s (Packen 208,5 s) / 69,8 s, ohne Passwort 270,2 s / 55,7 s · Diensttag 2,35 s statt 1,71 s (F-S11-22); 0 Konsolenfehler, rc 0 |

**Was daraus folgt:** Unter Drossel 6× liegen beide Einzelansichten schon
heute über der Sekunde aus E-S11-15 (F-S11-15, Frage Q-S11-16 im Konzept).

### S11-02 Probe-App und Prüfvektoren (11.10.2026, bis zum Halt)

| Mittel | Was es misst | Zahl |
|---|---|---|
| `referenz.py --selbstprobe` | die Referenz erkennt verfälschte Vektoren, Gegenproben bleiben grün | 5 Fälle, 0 Fehlschläge |
| `referenz.py pruefen` | jeder Vektor neu gerechnet, jede Hülle geöffnet, jede Störung abgewiesen | 6 Fälle geöffnet, 8 Störungen abgewiesen, 0 Abweichungen |
| `proben.sh huelle` (Chromium) | WebCrypto gegen die Vektoren: Z, HKDF, Bindung, Hülle byte-gleich, Öffnen, Abweisungsgrund, Rundlauf | 39 Prüfungen, 0 Abweichungen; ebenso bei Drossel 6× |
| dieselbe, Zeiten | Verschließen/Öffnen eines Spurstücks | ≤ 0,3 ms ungedrosselt, ≤ 2,9 ms bei 6× (500 Punkte JSON) |
| `pruefstand.sh kryptoprobe` | Probe-App im Simulator auf den drei Uhren | 3 von 3, je 7 gut, 0 Fehler; ECDH-Geheimnis little-endian (F-S11-18); `PACKEN nicht vorhanden` |
| Gegenprobe | ein Byte im Prüfwert von `Vektoren.mc` verfälscht | 5 gut, 2 Fehler (HMAC, HÜLLE kurz), rc 1 |
| `pruefstand.sh kryptoreihe` | Probe für alle Geräte der App übersetzt | 99 übersetzt, 0 fehlgeschlagen; 48 Warnungen, alle zur Skalierung des Launcher-Icons |
| `bestand` · `kettenaufrufe` | neue Prüfmittel in der Form, Aufrufe gegen ihre Werkzeuge | 0 Befunde · 0 Widersprüche |

### S11-13 Messpaket Seitenaufbau (13.10.2026)

| Mittel | Was es misst | Zahl |
|---|---|---|
| `messen.py --schritte browser`, 6× und ungedrosselt | Zeitmarken je Seite: Server, HTML, Skripte, `api/`, Krypto, Hauptfaden | 9 Seiten, 0 Konsolenfehler, rc 0; Tabelle in `tools/messstand/ausgangsmessung.md` |
| Eichlauf (feste Rechenaufgabe, je fünfmal) | wie schnell die Maschine an diesem Tag ist | 146–153 ms ungedrosselt, 898–987 ms bei 6× |
| Vergleich mit dem 11.10.2026 | derselbe Code nach einem Neustart des Containers | Einsatz 2,11 statt 1,33 s, GPX 414 statt 330 s (F-S11-25) |

**Nicht geklärt:** die lange Serverzeit von Startseite und Anmelden bei 6×
(0,93 und 2,15 s; ungedrosselt 0,07 und 0,13 s) — Einzelläufe, Vorschlag V4.

### S11-03 Schlüsselpaar und Hülle im Web (13.10.2026)

| Mittel | Was es misst | Zahl |
|---|---|---|
| `proben.sh huelle` | die Gerätehülle in `server/assets/crypto.js` gegen die Vektoren | 39 Prüfungen, 0 Abweichungen |
| `proben.sh kopplung` | `pair.php` samt Kontoschlüssel: ohne Paar `null` (K1), mit Paar SEC1 in `status` und `bestaetigen` (K5, K6), Siegel umgehängt (K7) und unversiegelt (K8) → `null`, beides gemeldet (K9) | 85 Erwartungen, 0 nicht erfüllt |
| `proben.sh kontopaar` (neu) | Browser: Paar entsteht still, Siegel und `edk1:` in der Datenbank, Abgleich passt, Rundlauf einer Hülle, privater Schlüssel nicht extrahierbar, vertauschter privater Teil gemeldet (Seite, Protokoll, Reiter System), kaputtes Siegel → `KP_STAND` siegel | 14 ok, 0 fehlen |
| `proben.sh schluesselwechsel` | Inventar mit Zweck `kontopaar`; Kontoschlüssel unter A angelegt, nach B umgehüllt | 66 ok, 0 fehlen unter PHP 8.4.19 (unter Ubuntu-8.3.6: 65 ok, 1 fehlt, auch ohne S11 — F-S11-33) |
| `proben.sh komplett` | Komplettsicherung mit den neuen Spalten | 75 von 75 unter PHP 8.4.19 (unter Ubuntu-8.3.6: 73 von 75, auch ohne S11 — F-S11-33) |
| `proben.sh rollen` (einzeln, nach F-S11-29) | Rollen an allen Seiten und Endpunkten | 534 Erwartungen, 0 offen |

### S11-04a Speicher, Prüfschicht, Eingang (13.10.2026)

| Mittel | Was es misst | Zahl |
|---|---|---|
| `proben.sh huelle` | Referenz, **Server** (`geraetehuelle_lib.php`, neu) und Browser gegen die Vektoren; Server-Hülle von der Referenz geöffnet; Innenform ohne Höhe (-32768) | Server 14/0, Browser 39/0 |
| `proben.sh ingest` | Stücke statt Punkte: Marke, Wiederholung, Stufe 2/3 über ein zusammengefügtes Stück, Ersetzfenster, Einsatzhülle mit den Phasen; Klartext (Punkte, Phase mit Koordinate, Reanimation) → `400 klartext`, nichts angelegt; kaputte Hülle → 400; abgelegte Hülle geht unter ihrer Bindung auf, an einem anderen Einsatz nicht | 97 Erwartungen, 0 offen |
| `proben.sh kopplung` · `verbindung` · `raten` · `spur` · `wartung` · `zweitfaktor` · `jobs` | senden über die Gerätehilfe | 85/0 · 26/26 · 82/0 · 45/0 · 70/0 · 139/0 · 36/0 |
| `proben.sh komplett` · `wiederherstellung` | Dump mit `track_stuecke` und `ort_huelle` | 75/75 · 115/0 |
| `plattform.sh alles` | Schema mit neuer Tabelle und Spalte auf vier Datenbankfassungen | 4 × 40 Prüfungen, 0 Fehlschläge |
| Umbau des Sendeplans (`geraet_cli.php`) | alle Pakete des Referenzbestands, verschlossen mit einem Wegwerfschlüssel und mit der Referenz geöffnet | 638 Pakete, 0 mit Klartext; 638 Stücke mit richtiger Punktzahl, 314 Einsatzhüllen geöffnet; `pruef_huelle()` 60/60 |
| `Sitzung.kontoschluessel()` | Python legt das Kontopaar an wie der Browser (Wegwerfkonto) | Stand „fehlt" → „da"; Siegel `edsk1:`, privat `edk1:`; privater Teil passt zum öffentlichen |

**Nicht gelaufen:** der volle Einspiellauf des Referenzbestands
(`einspielen.py`) — er braucht eine leer eingerichtete Datenbank, und
`lokal_einrichten.sh` löscht den Bestand dieser Anlage. Geprüft sind seine
beiden neuen Teile einzeln (Zeilen darüber); der ganze Lauf steht in 1.

### S11-04b Schnittstellen liefern die Hüllen (13.10.2026)

| Mittel | Was es misst | Zahl |
|---|---|---|
| `proben.sh ortsdaten` (neu) | Wegwerfkonto mit Kontopaar; ein Gerät sendet Einsatz (2 Stücke, 3 Phasen, 1 Reanimation) und Ruhesegment verschlossen; der Browser holt `api/mission.php` und `api/day.php` und öffnet mit seinem privaten Schlüssel; Punkt für Punkt gegen das Gesendete; fremde Bindung; Sperrzeit eines Schnitts; keine Klartextzeile in der Datenbank | 16 ok, 0 fehlen |

### S11-04c Die Ansichten öffnen die Hüllen (14.10.2026)

| Mittel | Was es misst | Zahl |
|---|---|---|
| `proben.sh ortsdaten` (Schritte 6 bis 8 neu) | Einsatzansicht: Spur aus den Stücken gezeichnet, Phasen und Reanimation aus der Hülle, Ladeplatz danach fort, keine Meldung · Tagesübersicht: Einsatz- und Ruhespur, gesperrte Punkte fehlen (E-S11-37) · `tag_spuren.php`: Zeilen und Linien · keine Skriptfehler | 23 ok, 0 fehlen |

**Nicht geprüft in 04c:** wie der Ladeplatz bei Drossel 6× tatsächlich
aussieht (erscheint er, bevor die Karte steht?) und ob V1 die Zeit verkürzt —
beides misst S11-11 (E-S11-41; ursprünglich S11-04d).

### S11-04d Meldung einer Hülle, die nicht aufgeht (14.10.2026)

| Mittel | Was es misst | Zahl |
|---|---|---|
| `proben.sh ortsdaten` (Schritt 9 neu) | eine Einsatzhülle in der Datenbank um ein Byte verändert (letztes Byte, Prüfwert): die Ansicht zeigt eine Meldung und keinen Ladeplatz; `api/kontopaar.php` antwortet 200; im Reiter System **genau eine** neue Zeile `ortsdaten: … (Einsatz N, einsatz, Grund pruefwert)` | 25 ok, 0 fehlen (davon 2 neu) |
| `pruefstand.sh kryptoprobe fenix6pro` mit verwaister Sperre (PID 999999) | `simulator_starten()` räumt sie ab und der Simulator kommt hoch (F-S11-39) | 7 gut, 0 Fehler; Meldung „verwaiste Simulator-Sperre entfernt" |
| Prüfstand | wie S11-04c: Bedienprobe, Stilvergleich, Bilderlauf, GPX-Probe rot (E-S11-41) | Bericht in der Commit-Nachricht |

**Nicht geprüft in 04d:** die Meldung aus der Tagesübersicht und aus
`tag_spuren.php` (gleicher Helfer, andere Aufrufstelle; nur gelesen) und der
Topf von fünf Meldungen je Stunde (derselbe wie bei der Abweichung, in der
Kontopaarprobe gemessen).

## 3. Im Browser geprüft

Noch nichts von Hand; S11-00 und S11-01 ändern nur Dokumente und Prüfmittel. Die Messungen von S11-01 laufen im Browser (Playwright, Chromium), stehen aber unter 2, weil sie Kosten messen, nicht Verhalten.

## 4. Prüfliste für die Betreiberin

Je Punkt: Bedienweg, erwartetes Ergebnis, woran ein Scheitern zu erkennen
ist. Die Punkte ab P-S11-03 entstehen mit ihrem Paket und werden dort
ausgeschrieben; hier stehen sie als Vorschau, damit nichts überrascht.

| Nr. | Wann | Bedienweg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|---|
| P-S11-01 | erledigt 11.10.2026 | Konzept lesen, Freigabe erteilen | Freigabe im Chat — **erteilt 11.10.2026** | — |
| P-S11-10 | erledigt 11.10.2026 | Frage Q-S11-16 im Konzept beantworten | **beantwortet:** Drossel 6×, Faktor 2 (E-S11-26) | — |
| P-S11-11 | erledigt 11.10.2026 | Frage Q-S11-17 im Konzept beantworten (absolute Grenze neben Faktor 2?) | **beantwortet:** unter 3 s bei 6×, unter 1 s ungedrosselt (E-S11-29) | — |
| P-S11-13 | erledigt 13.10.2026 | Frage Q-S11-18 beantworten (was misst „Seitenaufbau“, für welche Seiten?) | **beantwortet:** „sichtbar“, alle Seiten, so schnell wie möglich (E-S11-32) | — |
| P-S11-12 | in S11-04 | Mockup der Ladeanzeige (E-S11-27) ansehen und freigeben | Freigabe im Chat | — |
| P-S11-14 | nach dem Merge auf Staging | `update.php` aufrufen; mit einem eigenen Konto anmelden, eine Einsatzseite öffnen; in Betrieb → Protokoll, Reiter Verwaltung nachsehen | ein Eintrag „Kontoschlüssel angelegt“ für das Konto, keine Meldung oben auf der Seite | kein Eintrag, oder oben „…passt nicht zu deinem privaten Schlüssel“, oder ein Eintrag „kontopaar“ im Reiter System |
| **P-S11-02** | **jetzt** (Halt) · Fenix 6 Pro erster Lauf 13.10.2026: 6/6 Vektoren gut, ECDH little-endian, `T-PAAR 73/55/54 ms`, Zeitzeilen abgeschnitten (F-S11-23) → zweiter Lauf mit Fassung 2 der `.prg` · FR 945 und Venu 3s offen | 1. In VS Code mit Monkey-C-Erweiterung den Ordner `tools/uhr-pruefstand/krypto-probe/` öffnen. 2. Je Uhr „Monkey C: Build for Device", Gerät wählen. 3. Die `.prg` per USB nach `GARMIN/APPS/` kopieren, Uhr trennen. 4. „Krypto-Probe" starten, rund 10 s warten, bis `ERGEBNIS …` steht. 5. Mit Hoch/Runter (Venu 3s: wischen) blättern und **alle Zeilen** abschreiben oder fotografieren — besonders `ECDH …`, `T-PAAR …` und die vier `T-…`-Zeilen. 6. Für Simulator-Vergleich auf dem Arbeitsplatz dasselbe mit F5. Danach die App von der Uhr löschen. | je Uhr `ERGEBNIS 7 gut, 0 Fehler`; `ECDH gut (little-endian, umgedreht)` wie im Simulator; `PACKEN nicht vorhanden` | eine Zeile `FEHLER`, ein Absturz (IQ-Symbol), `ERGEBNIS` erscheint nicht; oder `ECDH gut (big-endian)` — dann rechnet die Uhr anders als der Simulator, und S11-08 muss beide Fälle kennen |
| P-S11-15 | nach dem Merge auf Staging | `update.php` aufrufen; eine gekoppelte Uhr der alten Fassung einen Einsatz senden lassen | Die Uhr parkt das Paket und meldet einen Fehler; im Web erscheint **kein** Einsatz; in Betrieb → System keine Meldung „ingest" mit Kennung | der Einsatz erscheint (dann nahm der Server Klartext an), oder die Uhr verwirft das Paket |
| P-S11-16 | nach dem Merge auf Staging | einen neu gesendeten Einsatz öffnen, einmal gesperrt (Abmelden, wieder anmelden) und einmal entsperrt | gesperrt: kurz „Wartet auf dein Passwort", nach dem Entsperren Karte, Phasen, Reanimation; entsperrt: Ring höchstens kurz, dann alles | die Karte bleibt leer ohne Meldung, oder rote Meldung „lassen sich nicht öffnen" |
| P-S11-17 | nach dem Merge auf Staging | Als Administratorin in der Datenbank von Staging bei einem eigenen, neu gesendeten Einsatz ein Byte von `missions.ort_huelle` ändern; den Einsatz öffnen und entsperren; danach Betrieb → System | in der Ansicht „Die Ortsdaten dieses Einsatzes lassen sich nicht öffnen"; im Reiter System eine Meldung `ortsdaten` mit Einsatznummer und Grund, ohne Ortsangabe | keine Meldung im Reiter System, oder eine Meldung mit Koordinaten oder Adresse |
| P-S11-03 | vor der Hauptauslieferung | Uhr mit der alten Fassung vollständig senden lassen | Rückstand 0 auf der Uhr | Rückstandszahl größer 0 |
| P-S11-04 | nach der Hauptauslieferung | `update.php` auf Staging und Produktiv | Migrationen gelaufen, Wartung aus | Wartungsseite bleibt |
| P-S11-05 | nach der Hauptauslieferung | neue Uhrfassung aufspielen, einmal neu koppeln, einen Probeeinsatz aufzeichnen und senden | Einsatz im Web mit Karte nach dem Entsperren | Uhr zeigt „Neu koppeln" weiter oder parkt |
| P-S11-06 | nach S11-10 | Einmalwerkzeug auf Produktiv laufen lassen | Ende mit „0 Klartext-Ortsdaten" je Tabelle | eine Zahl ungleich 0 |
| P-S11-07 | nach P-S11-06 | neue Konto- und Komplett-Backups anlegen; ein Komplett-Backup vom Stand vor S11 offline ablegen | Backups vorhanden | — |
| P-S11-08 | 30 Tage nach P-S11-07 | alte Sicherungen auf dem Server, in den Backup-Zielen und eigene `.edbak` löschen; das offline abgelegte Komplett-Backup löschen | keine Sicherung vor S11 mehr vorhanden | — |
| P-S11-09 | nach der Aufräumauslieferung | `update.php` | Migration läuft durch | Meldung „Klartext-Ortsdaten vorhanden" mit Fundort |

## 5. Grenzen der benutzten Prüfmittel

- **Messstand:** misst die Maschine, auf der er läuft. Vor und nach S11 auf
  derselben Maschine ist ein Vergleich; über ein Telefon sagt er nichts.
- **Uhr-Prüfstand Stufe I:** übersetzt, führt nicht aus.
- **Simulator der Uhr:** Ob seine Krypto der des Geräts gleicht, ist
  unbelegt; deshalb P-S11-02 auf echten Uhren.

## 6. Was aus dem Schritt offen bleibt

Wird mit dem Abschluss gefüllt.
