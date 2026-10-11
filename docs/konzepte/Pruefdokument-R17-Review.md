# Prüfdokument R17 — Bedrohungsmodell (Schritt 12, Review Stück 1)

*Gehört zu `Review-R17.md`. Beantwortet „was muss **ich** noch tun?" — das
Protokoll „ist es belegt?" steht im Statusblock und in Abschnitt 5 des
Konzepts. Angelegt am 11.10.2026 mit R17-00 (Fable); jedes Paket füllt es
mit Mittel **und** Zahl. Stand: **R17-03 gestellt** (acht Fragen, Konzept 2.2), R17-04
offen. Dieses Dokument bleibt, bis seine Prüfliste abgehakt ist (K9); ob
12b es fortschreibt oder ein eigenes anlegt, entscheidet 12b.*

---

## 1. Was nicht geprüft werden konnte — und warum

Steht vor allem anderen. Was dazukommt, gehört hierher — an den Anfang.

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Ob das Modell vollständig ist, misst kein Werkzeug.** | Ein Bedrohungsmodell ist eine Lesung: Es zählt auf, wogegen die Anlage hält und wogegen nicht. Dass keine Angreiferlage fehlt, lässt sich nicht nachzählen, nur gegenlesen — gegen die Liste im Fahrplan (Schritt 12), gegen `Review-Krypto-Sicherheit.md` 2 und gegen den Code. Die Inventartabelle (Konzept 3.2) macht sichtbar, **welche** Quellen gelesen wurden; sie belegt nicht, dass es alle sind. | P-R17-01: die Betreiberin liest das Modell gegen ihr eigenes Bild der Anlage. |
| **Was die drei Leser von R17-01 nicht sicher lesen konnten** — je Punkt, wo es sich zeigt: (1) `Toybox.Cryptography` auf der Garmin (Kurven, AES-CBC, HMAC, Verfügbarkeit auf allen 99 Geräten) — kein Connect-IQ-SDK im Container; SP-9 behauptet es. (2) Ob Garmin den App-Speicher verschlüsselt und ob er per USB lesbar ist. (3) Der Weg einer Connect-IQ-Anfrage (direkt oder über Garmin-Server, Zertifikatsprüfung, `http://`). (4) Ob die App-Einstellungen der Garmin (Alt-Weg mit `apiKey`) in Garmins Cloud liegen. (5) Ob der Wear-Data-Layer ohne Bluetooth über Google-Server vermittelt. (6) Hardware-Anker des Keystore (TEE/StrongBox) — gerätabhängig, nicht gefordert. (7) Schutzregeln der GitHub-Umgebungen und Zweitfaktor-Zwang der Organisation — API 403; wer freigeben darf, ist nirgends belegt. (8) Ob `kdf_anteil` auf Produktiv gesetzt ist (Rahmenplan 6.1: offen). (9) `display_errors`, PHP-Fassung, bcrypt-Kosten, `csp_scharf`, `hsts_tage` auf Produktiv. (10) Zugriffsprotokolle beider Hoster (Dauer, Inhalt). (11) Ein vollständiger IDOR- und XSS-Durchgang (Stichproben nur; 43 `innerHTML`-Zuweisungen ungeprüft) — **Gegenstand von 12b, nicht von 12**. (12) Welche `ftp-deploy`-Fassung in der gepinnten SHA steckt. | Für (1): Probe-App im Uhr-Prüfstand, Stufe I über alle Geräte (vor S11). (2)–(5): Herstellerdoku, vor S11. (7), (8), (9): die Betreiberin liest es in GitHub bzw. auf Betrieb → Status ab — P-R17-04. (11): 12b. |
| **Die Behauptungen des Modells über den Code sind gelesen, nicht gemessen.** | R17-01 liest den Code bei Web 21.14.0, Uhr 3.1.0, Android 0.17.0 und nennt Funktionsnamen. Dass eine genannte Funktion tut, was das Modell sagt, belegt die Lesung; eine Probe, die es misst, gibt es nur dort, wo ein Prüfwerkzeug schon steht (`tools/proben/`). Das Modell nennt je Baustein, ob eine Probe ihn misst. | Abschnitt 2 je Paket; Konzept 3.2. |

## 2. Maschinell geprüft

Je Paket die Mittel mit Zahl. Die Zahl eines Pakets steht hier mit dem
nächsten Paket — eine Zeile hier ändert den Baum, den der Prüfstand gemessen
hat (`Pruefablauf.md` 5.1); der Bericht eines Pakets steht in **seiner**
Commit-Nachricht.

### R17-02 Das Modell (11.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| `python3 tools/quelltext/textprobe.py --bereich c` | das neue Dokument im Bereich `c` — Land und Luft, Hausform, Adressen, Netz, Namen | erster Lauf 52 Treffer in 29 Zeilen (43 `luft`, 9 `hausform`); nach Korrektur der Hausform (9 × BetreiberIn) und zwei Ausnahmen mit Begründung (`spur-fachdokument-bedrohungsmodell`, `bedrohungsmodell-plattformen`): **0 außerhalb der Ausnahmen, 0 durchgerutschte Fallen**; ein Rest (`fenix5`) umformuliert |
| `textprobe.py --selbstprobe` | die Probe prüft sich selbst | 4/4 |
| `decken.py` · `bestand.py` · `linkprobe.py` · `pysyntax.py` | Form der Steuerung, des Werkzeugbestands, der Verweise, der Python-Werkzeuge | 21/0 · 0 Befunde · 150/0 · 63/0 |
| Lesung gegen die Gliederung (Konzept 3.1) | jeder der sechs Abschnitte vorhanden, jede I-Nummer des Inventars einem Abschnitt zugeordnet, jeder F-Befund an mindestens einer Stelle genannt | 6 von 6 Abschnitte; **28 von 31** Befunde genannt (gezählt mit `grep -o`) — die drei Sammelbefunde F-R17-29 bis -31 (Doku-Altstände, veraltete Krypto-Review, Hinweise) nennt das Modell nicht einzeln. *Bis R17-03 stand hier „31 von 31" — die Zahl war geschätzt, nicht gezählt (berichtigt).* |
| `bash tools/pruefstand/pruefen.sh --stufe klein` | die billigen Riegel | 23 Riegel grün (die Ausnahmenliste bringt den Riegel `wortliste` dazu), 0 rot, 0 nicht gemessen, 63 s; Baum `60bfe49…` = Baum von `8a4c163` |

### R17-01 Bestandsaufnahme (11.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| drei lesende Leser (Opus), ohne Nebenwirkung | Code gegen Doku: Server und Browser (14 Punkte), Uhr und Android (6 Punkte), Kette und Betrieb (12 Punkte) | 129 + 71 + 105 Werkzeugaufrufe; 36 + 25 + 32 Bausteinzeilen; 16 + 16 + 23 Befunde; verdichtet auf 55 Bausteine und 31 Befunde |
| Nachprüfung Fable am Quelltext | die Hoch- und die bestätigbaren Mittel-Befunde | 11 nachgeprüft, 11 bestätigt (F-R17-01 bis -06, -11, -14, -15, -19, -21); 20 als Lesung gekennzeichnet |
| `gh api` (lesend) | `rules/branches/produktion`; Rulesets | `[]` — keine Regel auf dem Zeiger; Umgebungen und Organisation 403 |
| Zählung `uses:` | der Handbefehl aus `CLAUDE.md` 3 (beide Zählungen) | 14 von 14 |
| `decken.py` · `uebersicht.py --ziel 12` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` | wie R17-00 | 21/0 · 62/0/0, 2 zu Ziel 12 · 0 Überschneidungen · 0 neue Treffer · 0 Befunde · 150/0 |
| `bash tools/pruefstand/pruefen.sh --stufe klein` | die billigen Riegel | 22 Riegel grün, 0 rot, 0 nicht gemessen, 53 s; Baum `63fef5e…` = Baum von `3b6f330` |

### R17-00 Buchführung (11.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| `python3 tools/steuerung/decken.py` | Decken der Steuerungsdokumente (Kopf des Rahmenplans, Backlog-Einträge, Verlaufszeile) | 21 Decken, 0 gerissen — die Verlaufszeile 154 riss beim ersten Lauf mit 333 Zeichen (Decke 300), gekürzt auf 283 |
| `python3 tools/steuerung/uebersicht.py --ziel 12` | Kopfzeilen; Zuordnung zu 12 | 62 offene Einträge, 0 ohne Grammatik, 0 ohne gültiges Ziel; 2 zu Ziel 12 (Nr. 146, 263) |
| `python3 tools/steuerung/nummern.py` | neue Nummern gegen `origin/main` und die Remote-Zweige | 0 neue Nummern, 8 Zweige verglichen, 0 Überschneidungen; kein offener PR (GitHub, 11.10.2026) |
| `python3 tools/quelltext/textprobe.py --bereich c` | neue Treffer in der normativen Doku | 0 Treffer außerhalb der Ausnahmen, 0 durchgerutschte Fallen (3 ungenutzte Ausnahmen gehören zu Bereich a, nicht gefahren) |
| `python3 tools/quelltext/bestand.py` · `linkprobe.py` | Form des Werkzeugbestands; Verweise | 0 Befunde · 150 Verweise, 0 Abweichungen |
| `bash tools/pruefstand/pruefen.sh --stufe klein` | die billigen Riegel, Bericht für die Commit-Nachricht | 22 Riegel grün, 0 rot, 0 nicht gemessen, 86 s; Baum `5fa9b53…` = Baum von `fca8549` |

## 3. Im Browser geprüft

Entfällt für diesen Schritt: kein Paket ändert die Oberfläche. Steht hier,
damit die Leerstelle sichtbar ist und nicht als Vergessen gelesen wird.

## 4. Prüfliste für die Betreiberin

Je Punkt: Bedienweg, erwartetes Ergebnis, woran ein Scheitern zu erkennen
ist. Abhaken, wenn geprüft.

| Nr. | Bedienweg | Erwartung | Scheitern erkennbar an | ☐ |
|---|---|---|---|---|
| P-R17-01 | `docs/Bedrohungsmodell.md` von vorn bis hinten lesen, mit dem eigenen Bild der Anlage daneben: Welche Lage fehlt? Welche Zusage steht dort anders, als Sie sie geben würden? | Keine Lage fehlt, keine Zusage ist zu weit; jede Grenze, die Sie kennen, steht benannt. | Eine Angreiferlage oder ein Klartextfeld, das Ihnen einfällt und im Modell nicht steht. | ☐ |
| P-R17-02 | Die acht Fragen Q-R17-01 bis -08 (Konzept 2.2) beantworten — je Zeile die Empfehlung annehmen oder anders entscheiden. | Jede Frage hat eine Antwort, die als E-R17-NN eingetragen ist und im Modell 6 steht. | Eine Frage ohne E-Nummer nach dem Abschluss; eine Antwort, die im Modell fehlt. | ☐ |
| P-R17-04 | In GitHub: Settings → Environments → `produktion` und `staging`: Stehen Pflichtprüfer und eine Zweigbeschränkung (Deployment branches and tags)? Organisation `gen-em` → Authentication security: Ist der Zweitfaktor erzwungen? Auf Produktiv: Betrieb → Status, Zeile *Server-Anteil*; Betrieb → Servereinstellungen, CSP und HSTS. | Pflichtprüfer gesetzt, Zweige auf `main` und die drei Tag-Muster beschränkt; 2FA erzwungen; Anteil angelegt; CSP scharf, HSTS mindestens 180 Tage. | Eine fehlende Beschränkung bestätigt F-R17-04; ein fehlender Anteil F-R17-05. | ☐ |
| P-R17-03 | Nach dem Merge: `docs/Bedrohungsmodell.md` ist aus `README.md` und `docs/Technik.md` erreichbar; die Links führen hin. | Beide Verweise öffnen das Modell. | 404 oder ein Verweis auf eine andere Datei. | ☐ |

*Weitere Punkte kommen mit R17-02 bis R17-04.*

## 5. Grenzen der benutzten Prüfmittel

- **`decken.py`, `uebersicht.py`, `nummern.py`** messen Form, nicht Inhalt:
  Kopfzeilen, Zeilenzahlen, Nummernkollisionen. Eine falsche Zuordnung mit
  richtiger Grammatik ist für sie grün.
- **Die Textprobe** (`textprobe.py`, Bereich `c`) meldet nur **neue**
  Treffer gegen den Altbestand; sie liest `docs/Bedrohungsmodell.md` erst,
  wenn R17-02 die Datei im Bereich eingetragen hat — und das ist der Zweck
  des Eintrags im selben Paket (`Pruefablauf.md` 6.6).
- **Der Prüfstand** (`pruefen.sh`, Stufe `klein`) läuft ohne `server/`-
  Berührung über die 20 billigen Riegel; er misst nicht, ob ein Satz im
  Modell wahr ist.

## 6. Was aus dem Schritt offen bleibt

*Kommt mit R17-04.*
