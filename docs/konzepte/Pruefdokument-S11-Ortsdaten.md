# Prüfdokument S11 — Ortsdaten verschlüsselt (Weg B)

*Gehört zu `Konzept-S11-Ortsdaten-Weg-B.md`. Beantwortet „was muss **ich**
noch tun?" — das Protokoll „ist es belegt?" steht im Statusblock und in
Abschnitt 6 des Konzepts. Angelegt am 11.10.2026 mit S11-00 (Opus); jedes
Paket füllt es mit Mittel **und** Zahl. Stand: **Konzept freigegeben am
11.10.2026** (P-S11-01); **S11-01 erledigt** (Grundlinie); **S11-03 erledigt** (13.10.2026, Web 22.0.0); **S11-04 erledigt** (14.10.2026, Web 22.0.4) — ohne Bilderlauf-Vergleich und Messung, die mit S11-10/-11 kommen (E-S11-41); **S11-05a bis -05c erledigt** (15.10.2026, Web 22.2.0); **S11-05d erledigt** (16.10.2026, Web 22.3.0); als Nächstes S11-05e.*

---

## 1. Was nicht geprüft werden konnte — und warum

Steht vor allem anderen. Was dazukommt, gehört hierher — an den Anfang.

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Der Kartendialog im Formular mit Spur, die Luftlinie zur „letzten Zielklinik" (Einsatz- und Tagesansicht), der Filter „Transportziel" der Suche und die Kachel „Höchster Einsatzort" der Zeitraumübersicht** (S11-05b). | Die Ortsdatenprobe fährt Formular, Einsatzansicht und Blob, aber nicht diese vier Wege; sie sind gelesen, nicht gemessen. Bilderlauf und Bedienprobe, die sie berührten, sind bis S11-10 rot (E-S11-41). | P-S11-18 bis -20; S11-10 (Bilderlauf grün). |
| **Schneiden und Löschen an einem Segment mit Klartextpunkten oder mit Stücken der Stufe 2/3; eine teilweise überlappende Nachlieferung nach dem Löschen** (S11-05c). | Altbestand-Segmente zeigt die Tagesansicht nicht zum Schneiden (`n` = 0); Stufe 2/3 gibt es erst ab S11-07. Die Probe liefert nach dem Löschen nur das ganze Stück erneut (Dublette), nicht ein teilweise überlappendes. | S11-07 (Stufen), S11-10 (Altbestand); die Abweisungen `409 altbestand` und `409 stufe` sind gelesen, nicht gemessen. |
| **Das Einspielwerkzeug des Referenzbestands** über das Formular (F-S11-43). | Es bedient das Formular noch im alten Format; seit 22.1.0 lehnt das Formular das ab. Der volle Einspiellauf gehört nicht zum Prüfstand. | Beim nächsten Neuaufbau einer Anlage: Abbruch in der Stufe „nachtragen". Behoben in S11-06. |
| **Ob Einsatz und Diensttag dieselbe Karte zeigen wie vor S11-04, und wie schnell** (Abnahme von S11-04: Bilderlauf, Messung gegen S11-01). **Bedienprobe, Stilvergleich, Bilderlauf und Messstand sind im Prüfstand rot** seit S11-04c bzw. S11-05b (die GPX-Probe ist seit S11-05d wieder grün, F-S11-54). | Der Prüfbestand (Demo, Referenz) liegt bis zum Einmalwerkzeug im Klartext; ohne Übergang (E-S11-36) zeigen die Ansichten dort keine Karte, eine Messung hätte nichts geöffnet. Die BetreiberIn hat rote Berichte bis S11-10 angenommen und ein Vorab-Werkzeug abgelehnt (E-S11-41). | S11-10 (Bilderlauf, die übrigen roten Schritte wieder grün) und S11-11 (Messung, E-S11-26). Bleibt eine davon dort rot, ist es ein Befund und keine Folge von E-S11-41 mehr. |
| **Wie der Ladeplatz bei Drossel 6× aussieht und ob V1 die Zeit verkürzt.** | Aus demselben Grund; die Ortsdatenprobe öffnet zehn Punkte, nicht einen Bestand. | S11-11. |
| **Die Browserliste gegen `CompressionStream`** (E-S11-21). | Es gibt keine Liste in `docs/Technik.md`, und weder S11-04 noch S11-05d packen (F-S11-38, -56). | S11-07, das erste Paket, in dem der Browser packt. |
| **GPX-Abruf und -Import in Firefox und WebKit; Dateien über 1 MB; die Auswahl eines vollen Diensttags.** (S11-05d) | Nur Chromium im Abbild (F-S11-20). Die Probe baut aus 211 Referenzdateien (größte 1063 Punkte), importiert 4 Punkte; eine Datei an der Grenze (12 MB, 50 000 Punkte) ist nicht gefahren. | P-S11-23; ein langer Import zeigt sich als Wartezeit im Dialog. |
| **Der Hinweis „Wartet auf dein Passwort" vor dem GPX-Abruf einer gesperrten Sitzung.** (S11-05d) | Die Probe ist entsperrt angemeldet; der gesperrte Weg (Einsatzansicht: Rückfrage, dann Passwort) ist gelesen, nicht gefahren. | P-S11-23. |
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

### S11-05a Packstufe des GPX-Archivs (14.10.2026)

| Mittel | Was es misst | Zahl |
|---|---|---|
| Messskript im Notizordner (Playwright, Chromium, zip.js 2.8.34) | Größe und Packzeit des echten Jahresexports (8174 GPX-Spuren) je Packstufe, mit und ohne Passwort | Drossel 6×, Passwort an: Stufe 6 30,2 MB / 209,5 s, Stufe 1 35,5 MB / 209,0 s · Passwort aus: Stufe 6 29,8 MB / 191,9 s, Stufe 1 35,1 MB / 203,2 s, ungepackt 215,6 MB / 191,6 s |

Ergebnis: Die Packstufe spart keine Zeit (F-S11-40); V5 entfällt (E-S11-44).
**Grenze:** eine Messung je Variante, keine Streuung; Unterschiede unter 10 %
zwischen zwei Läufen sind Rauschen (F-S11-22). Für die Entscheidung genügt
das: Bei 6× liegen Stufe 6 und 1 mit Passwort 0,5 s auseinander.

### S11-05b Formular und Feldkatalog (15.10.2026)

| Mittel | Was es misst | Zahl |
|---|---|---|
| `proben.sh ortsdaten` Schritt 9 | `EdFormat.ortszeitZuEpoch()` gegen `local_to_utc()` — Sommertag, beide Zeitumstellungen direkt und über „+1 Tag", Jahreswechsel, zwei ungültige Eingaben | 11 Fälle, 0 abweichend |
| … Schritt 10 | Formular öffnet die Hülle (1 Reanimation, 2 Ereignisse, kein Feld mit `name`); unverändert gespeichert → Phasenorte und Reanimation gleich, 0 Klartextzeilen; **Gegenprobe**: Phasenzeiten auf dem Weg um 60 s verfälscht → Meldung, Hülle unverändert; Altbestand (2 Phasenorte, 1 Reanimation im Klartext) → in der Hülle, 0 Klartextzeilen | 5 ok |
| … Schritt 11 | Nachtrag mit Reanimation: `client_ref` `man-`+16 Hex, Hülle da, 0 Zeilen in `resus_sessions`, Reanimation 10:05 in der Ansicht | 1 ok |
| … Schritt 12 | Transportziel gesetzt, Phase 5 ergänzt: Spalten `transport_dest`, `dest_lat`, `dest_lon`, `site_ele_m` NULL; Blob `transport_dest` {addr, lat, lon}; `ortshoehe` = Höhe des nächsten gesendeten Punkts (679 m); „Klinikum Probe" weder in `api/mission.php` noch im Markup von `einsatz.php`; in der Ansicht nach dem Entsperren; „ambulant" → kein Ziel im Blob, Höhe bleibt | 6 ok |
| Ortsdatenprobe gesamt | Schritte 1 bis 13 | 39 ok, 0 fehlen |
| `kennzeichnung.php` | Sollliste um Transportziel, Reanimation, Ortshöhe ergänzt; drei neue Prüfformen | 26 von 26 Kennzeichen; Gegenprobe: Schloss am Transportziel bzw. am Titel der Reanimation entfernt → je 1 Befund |
| `spaltenregister/pruefen.php` | `dest_lat`, `dest_lon` aus `tag`, `site_ele_m` aus `range`, `transport_dest` aus `suchindex`, Positionen neu | 0 Befunde |
| Prüfstand, erster Lauf | 8 rot: dazu `vollstaendigkeit` (drei Ankerklassen ohne Eintrag), `bestand` (Design-Tabelle der Symbole nicht neu erzeugt), `kreislauf-edbak` (Datenverlust beim Einspielen, F-S11-48), `messstand` (F-S11-49) | behoben bzw. erklärt; `kreislauf.py --art edbak` danach 346 763 Einzelvergleiche, 0 unerklärt |
| Prüfstand, zweiter Lauf | Bedienprobe, Stilvergleich, Bilderlauf, GPX-Probe und — neu — Messstand rot (E-S11-41, F-S11-49) | Bericht in der Commit-Nachricht |

### S11-05c Schneiden, Rücknahme, ein Stück löschen (15.10.2026)

| Mittel | Was es misst | Zahl |
|---|---|---|
| `proben.sh ortsdaten` Schritt 13 | Zweites Ruhesegment, 10 Punkte je Minute; Zeitraum 4. bis 6. Minute über die Bedienung der Tagesansicht. **Schnitt:** Einsatz `origin = schnitt`, `cut-`+16 Hex, 2 verschlossene Punkte, 1 Vermerk; Stücke des Segments Byte für Byte (MD5) unverändert; Einsatzansicht zeigt 2 Punkte; Tagesansicht zeigt 8 von 10. **Rücknahme** über den Knopf: Einsatz, Stücke, Vermerk 0/0/0, Segment unverändert. **Gegenprobe:** gültige Stücke mit falschem Stand → „neu laden", nichts getauscht. **Löschen** mit Rückfrage: Segment 8 Punkte, Vermerk mit `mission_id` NULL, 1 Protokollzeile. **Gegenprobe:** dasselbe Paket noch einmal über `ingest.php` → weiter 8 Punkte | 8 ok |
| Ortsdatenprobe gesamt | Schritte 1 bis 14 | 47 ok, 0 fehlen |
| `tools/proben/spur/probe.php` | `spur_teilen()` mit dem neuen Schalter, Altbestand | 45 Erwartungen, 0 nicht erfüllt |
| `proben.sh ingest` | Eingang mit `pruef_spurstueck()` | 97 Erwartungen, 0 nicht erfüllt |
| `spaltenregister/wegprobe.py` | Schnitt mit eigenem verschlossenem Stück (3 Punkte), INSERT aus dem Register | 34 Erwartungen, 0 nicht erfüllt |
| `migrationsregister.php` | `2026_10_15_loeschvermerk` eingetragen, auf der Anlage angewendet | 0 Befunde |

### S11-05d GPX-Abruf und -Import im Browser (16.10.2026)

| Mittel | Was es misst | Zahl |
|---|---|---|
| `proben.sh gpx` (`probe.mjs`, neu) Teil 0 | SHA-256 des vendorierten XSD; Schemalauf (`schema.php`, libxml) über jede Datei der Probe | unverändert; 4 + 422 + 3 Dateien, 0 ungültig |
| … Teil 1 | `EdGpx.bauenViele()` gegen Solldateien aus `gpx_lib.php` (Web 22.2.0) — Einzelspur Original, Einzelspur ausgedünnt mit Sonderzeichen im Namen, zwei Spuren gemischt, zwei ausgedünnt; Dateinamen | 4 von 4 Byte für Byte (creator-Fassung maskiert), 1 benannte Abweichung `1.0E-7` (F-S11-55); 6 von 6 Namen |
| … Teil 2 | Jede GPX-Datei des Referenzexports (`assets/export.js`) mit `EdGpx.lesen()` gelesen und mit `EdGpx.bauen()` neu gebaut | 211 von 211, 63 915 Punkte, 0 Abweichungen |
| … Teil 3 | Abruf über die Oberfläche: Einsatzansicht (Menü, Rückfrage), Spurenseite (Knopf je Zeile, Auswahl beider Spuren) | 6 Punkte = gesendet; Dateiname, Name, Beschreibung wie bisher; Auswahl 2 Spuren in zeitlicher Folge, `diensttag_…_2-spuren_original.gpx` |
| … Teil 4 | Import über den Dialog als Ruhesegment und als Einsatz (6 Punkte in der Datei, 1 ohne Zeit, 1 mit Breite 95, unsortiert); Datenbank; wieder geöffnet; Endpunkt unmittelbar | je 4 Punkte, Meldung nennt 1 + 1; `imp-`+24 Hex, 1 Stück, 0 Zeilen in `track_points`/`track_blobs`; geöffnet = Datei, nach Zeit sortiert; falsche Kennung 400, doppelt 409, fremder Tag 404, leer 400, kaputte Hülle 400 |
| … Teil 5 | `EdGpx.lesen()` gegen 14 böse Eingänge (UTF-16 mit/ohne BOM, DOCTYPE in drei Formen, Nullbyte, UTF-7, IBM037, UTF-16-Deklaration, ohne Zeit, ohne Trackpunkt, Wurzel `kml`, nicht wohlgeformt) und 6 gute (UTF-8, utf-8, ohne Deklaration, windows-1252, Latin-1 mit Umlaut, GPX 1.0) | 14 abgewiesen, 0 durch; 6 angenommen, Latin-1-Name mit U+FFFD |
| GPX-Probe gesamt | Teile 0 bis 5 | 46 Erwartungen, 0 nicht erfüllt |
| `zaehlen.php` | Z22 nach Wegfall von `gpx_lib.php` 375: Decke 5 → 4 | 44 Zeilen, 0 über der Decke |
| `quelltext/pruefen.sh alle` | Ausnahme „fremde Quelle" von `gpx_lib.php` nach `assets/gpx.js` umgezogen; Prüfablauf-Tabelle neu erzeugt | 14 von 14 grün |

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
| P-S11-18 | nach dem Merge auf Staging | Einen Einsatz mit Reanimation im Formular öffnen — einmal gesperrt (abmelden, anmelden, nicht entsperren), einmal entsperrt; eine Phasenzeit ändern, speichern | gesperrt: Reanimationskarte mit Schloss, Felder gesperrt, „Speichern" bietet zuerst das Entsperren an; entsperrt: Reanimation steht da, nach dem Speichern in der Ansicht unverändert, Phasenorte auf der Karte wie vorher | gespeichert ohne Entsperren, Reanimation fehlt nach dem Speichern, oder rote Meldung „ließen sich im Browser nicht gleich rechnen" |
| P-S11-19 | nach dem Merge auf Staging | Einen Einsatz mit Zielklinik samt Koordinate öffnen, dann den folgenden mit Abfahrt „Letzte Zielklinik" ohne GPS-Aufzeichnung; in der Suche nach der Zielklinik filtern | Klinik-Pin und Zeile „Transportziel" (Schloss) erst nach dem Entsperren; Luftlinie beginnt an der Klinik des Vorgängers; Filter findet den Einsatz nur entsperrt | Pin ohne Entsperren sichtbar, Luftlinie fehlt, Filter leer trotz Entsperren |
| P-S11-20 | nach dem Merge auf Staging | Formular eines Einsatzes mit GPS-Aufzeichnung, Einsatzort leer, Pin-Knopf → „Auf der Karte wählen" | Die Spur steht im Dialog, die Karte passt sich ihr an | Dialog ohne Spur (F-S11-42 nicht behoben) |
| P-S11-21 | nach dem Merge auf Staging (nach `update.php`) | Einen Diensttag mit Ruhezeit nach einer echten Uhrfahrt öffnen, entsperren; „Schneiden", einen Zeitraum wählen, „Einsatz erzeugen"; danach „Schnitt zurücknehmen" | Einsatz mit Spur entsteht, im Segment fehlt der Zeitraum; nach der Rücknahme ist der Zeitraum wieder da, der Einsatz weg | Meldung „kein einziger Punkt" trotz Aufzeichnung, Spur am Einsatz leer, oder nach der Rücknahme fehlen Punkte |
| P-S11-22 | nach dem Merge auf Staging | Im Schneide-Bereich einen Zeitraum wählen, „Zeitraum löschen", die Rückfrage bestätigen; Betrieb → Protokoll, Reiter Verwaltung | Die Punkte des Zeitraums sind fort, die Punktzahl sinkt, Protokollzeile „GPS-Daten eines Ruhesegments gelöscht" | Punkte noch da, keine Rückfrage, oder „neu laden", obwohl nichts ankam |
| P-S11-23 | nach dem Merge auf Staging | (a) Einen neu gesendeten Einsatz öffnen, Sitzung gesperrt; „Aktionen → GPS-Daten als GPX", Rückfrage bestätigen. (b) Tagesansicht → „GPS-Daten als GPX": je Zeile „GPX", dann zwei ankreuzen, „Auswahl als GPX". (c) Tagesansicht → „GPX importieren", eine echte Datei der Uhr (z. B. aus Garmin Connect) als Ruhesegment. Jeweils die Datei in einem Kartenprogramm öffnen. | (a) zuerst Passwort, dann eine Datei `einsatz_…_original.gpx` mit der Spur. (b) je eine Datei; die Auswahl eine Datei mit zwei Tracks. (c) Rückmeldung „als Ruhesegment übernommen — N Punkte"; die Spur steht in der Tagesansicht und lässt sich schneiden | keine Datei, eine leere Datei, ein Track mit Verbindungslinie zwischen zwei Spuren, „neu laden" beim Import, oder die Spur fehlt danach in der Tagesansicht |
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
