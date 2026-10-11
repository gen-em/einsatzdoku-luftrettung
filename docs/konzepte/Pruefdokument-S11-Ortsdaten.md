# Prüfdokument S11 — Ortsdaten verschlüsselt (Weg B)

*Gehört zu `Konzept-S11-Ortsdaten-Weg-B.md`. Beantwortet „was muss **ich**
noch tun?" — das Protokoll „ist es belegt?" steht im Statusblock und in
Abschnitt 6 des Konzepts. Angelegt am 11.10.2026 mit S11-00 (Opus); jedes
Paket füllt es mit Mittel **und** Zahl. Stand: **Konzept freigegeben am
11.10.2026** (P-S11-01); **S11-01 erledigt** (Grundlinie); **S11-02 bis zum Halt** — es wartet auf P-S11-02 (Probe auf den echten Uhren).*

---

## 1. Was nicht geprüft werden konnte — und warum

Steht vor allem anderen. Was dazukommt, gehört hierher — an den Anfang.

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Ob die echte Garmin-Uhr die Krypto kann.** Im **Simulator** gemessen (S11-02): Fenix 6 Pro, Forerunner 945, Venu 3s je 7 gut, 0 Fehler. Ob der Simulator rechnet wie die Uhr — gerade bei der Byteordnung des ECDH-Geheimnisses (F-S11-18) —, ist unbelegt. Zeiten und Speicher des Simulators sind die des Rechners. | Keine Uhr in der Arbeitsumgebung. | P-S11-02; Halt, bis das Ergebnis vorliegt. |
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
| P-S11-11 | vor S11-04 | Frage Q-S11-17 im Konzept beantworten (absolute Grenze neben Faktor 2?) | Antwort im Chat | — |
| P-S11-12 | in S11-04 | Mockup der Ladeanzeige (E-S11-27) ansehen und freigeben | Freigabe im Chat | — |
| **P-S11-02** | **jetzt** (Halt) | 1. In VS Code mit Monkey-C-Erweiterung den Ordner `tools/uhr-pruefstand/krypto-probe/` öffnen. 2. Je Uhr „Monkey C: Build for Device", Gerät wählen. 3. Die `.prg` per USB nach `GARMIN/APPS/` kopieren, Uhr trennen. 4. „Krypto-Probe" starten, rund 10 s warten, bis `ERGEBNIS …` steht. 5. Mit Hoch/Runter (Venu 3s: wischen) blättern und **alle Zeilen** abschreiben oder fotografieren — besonders `ECDH …`, `T-PAAR …` und die vier `T-…`-Zeilen. 6. Für Simulator-Vergleich auf dem Arbeitsplatz dasselbe mit F5. Danach die App von der Uhr löschen. | je Uhr `ERGEBNIS 7 gut, 0 Fehler`; `ECDH gut (little-endian, umgedreht)` wie im Simulator; `PACKEN nicht vorhanden` | eine Zeile `FEHLER`, ein Absturz (IQ-Symbol), `ERGEBNIS` erscheint nicht; oder `ECDH gut (big-endian)` — dann rechnet die Uhr anders als der Simulator, und S11-08 muss beide Fälle kennen |
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
