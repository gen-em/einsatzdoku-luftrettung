# Prüfdokument R17 — Bedrohungsmodell (Schritt 12, Review Stück 1)

*Gehört zu `Review-R17.md`. Beantwortet „was muss **ich** noch tun?" — das
Protokoll „ist es belegt?" steht im Statusblock und in Abschnitt 5 des
Konzepts. Angelegt am 11.10.2026 mit R17-00 (Fable); jedes Paket füllt es
mit Mittel **und** Zahl. Stand: **R17-00 erledigt**, R17-01 bis R17-04
offen. Dieses Dokument bleibt, bis seine Prüfliste abgehakt ist (K9); ob
12b es fortschreibt oder ein eigenes anlegt, entscheidet 12b.*

---

## 1. Was nicht geprüft werden konnte — und warum

Steht vor allem anderen. Was dazukommt, gehört hierher — an den Anfang.

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Ob das Modell vollständig ist, misst kein Werkzeug.** | Ein Bedrohungsmodell ist eine Lesung: Es zählt auf, wogegen die Anlage hält und wogegen nicht. Dass keine Angreiferlage fehlt, lässt sich nicht nachzählen, nur gegenlesen — gegen die Liste im Fahrplan (Schritt 12), gegen `Review-Krypto-Sicherheit.md` 2 und gegen den Code. Die Inventartabelle (Konzept 3.2) macht sichtbar, **welche** Quellen gelesen wurden; sie belegt nicht, dass es alle sind. | P-R17-01: die Betreiberin liest das Modell gegen ihr eigenes Bild der Anlage. |
| **Die Behauptungen des Modells über den Code sind gelesen, nicht gemessen.** | R17-01 liest den Code bei Web 21.14.0, Uhr 3.1.0, Android 0.17.0 und nennt Funktionsnamen. Dass eine genannte Funktion tut, was das Modell sagt, belegt die Lesung; eine Probe, die es misst, gibt es nur dort, wo ein Prüfwerkzeug schon steht (`tools/proben/`). Das Modell nennt je Baustein, ob eine Probe ihn misst. | Abschnitt 2 je Paket; Konzept 3.2. |

## 2. Maschinell geprüft

Je Paket die Mittel mit Zahl. Die Zahl eines Pakets steht hier mit dem
nächsten Paket — eine Zeile hier ändert den Baum, den der Prüfstand gemessen
hat (`Pruefablauf.md` 5.1); der Bericht eines Pakets steht in **seiner**
Commit-Nachricht.

### R17-00 Buchführung (11.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| `python3 tools/steuerung/decken.py` | Decken der Steuerungsdokumente (Kopf des Rahmenplans, Backlog-Einträge, Verlaufszeile) | 21 Decken, 0 gerissen — die Verlaufszeile 154 riss beim ersten Lauf mit 333 Zeichen (Decke 300), gekürzt auf 283 |
| `python3 tools/steuerung/uebersicht.py --ziel 12` | Kopfzeilen; Zuordnung zu 12 | 62 offene Einträge, 0 ohne Grammatik, 0 ohne gültiges Ziel; 2 zu Ziel 12 (Nr. 146, 263) |
| `python3 tools/steuerung/nummern.py` | neue Nummern gegen `origin/main` und die Remote-Zweige | 0 neue Nummern, 8 Zweige verglichen, 0 Überschneidungen; kein offener PR (GitHub, 11.10.2026) |
| `python3 tools/quelltext/textprobe.py --bereich c` | neue Treffer in der normativen Doku | 0 Treffer außerhalb der Ausnahmen, 0 durchgerutschte Fallen (3 ungenutzte Ausnahmen gehören zu Bereich a, nicht gefahren) |
| `python3 tools/quelltext/bestand.py` · `linkprobe.py` | Form des Werkzeugbestands; Verweise | 0 Befunde · 150 Verweise, 0 Abweichungen |
| `bash tools/pruefstand/pruefen.sh --stufe klein` | die billigen Riegel, Bericht für die Commit-Nachricht | *Bericht in der Commit-Nachricht von R17-00; Zahl hier mit R17-01* |

## 3. Im Browser geprüft

Entfällt für diesen Schritt: kein Paket ändert die Oberfläche. Steht hier,
damit die Leerstelle sichtbar ist und nicht als Vergessen gelesen wird.

## 4. Prüfliste für die Betreiberin

Je Punkt: Bedienweg, erwartetes Ergebnis, woran ein Scheitern zu erkennen
ist. Abhaken, wenn geprüft.

| Nr. | Bedienweg | Erwartung | Scheitern erkennbar an | ☐ |
|---|---|---|---|---|
| P-R17-01 | `docs/Bedrohungsmodell.md` von vorn bis hinten lesen, mit dem eigenen Bild der Anlage daneben: Welche Lage fehlt? Welche Zusage steht dort anders, als Sie sie geben würden? | Keine Lage fehlt, keine Zusage ist zu weit; jede Grenze, die Sie kennen, steht benannt. | Eine Angreiferlage oder ein Klartextfeld, das Ihnen einfällt und im Modell nicht steht. | ☐ |
| P-R17-02 | Die Fragen aus R17-03 (Q-R17-NN im Konzept 2.2) beantworten. | Jede Frage hat eine Antwort, die als E-R17-NN eingetragen ist. | Eine Frage ohne E-Nummer nach dem Abschluss. | ☐ |
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
