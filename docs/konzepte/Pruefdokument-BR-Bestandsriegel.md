# Prüfdokument BR — Der Bestandsriegel

*Zum Konzept `Konzept-BR-Bestandsriegel.md`. Beantwortet „was muss **ich**
noch tun?" — das Protokoll „ist es belegt?" steht im Konzept, Abschnitt 9.
Stand: BR-01 erledigt, 24.09.2026.*

---

## 1. Was nicht geprüft werden konnte — und warum

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Der Riegel in Stufe 1 auf GitHub** | Stufe 1 läuft erst beim Pull Request. Örtlich gefahren sind derselbe Läufer, dieselbe Selbstprobe und dieselbe Gegenlesung (`bericht.py lesen`); ob der Schritt im Tor mit dem Bericht übereinstimmt, zeigt erst der PR. | P-BR-01 |
| **Ein roter Riegel hält wirklich einen Merge auf** | Das hieße, einen PR mit einem eingebauten Fehler zu öffnen. Belegt ist es an zwei Stellen, die zusammen tragen: Die Selbstprobe baut je Regel einen Fehler ein (22 / 0), und das Tor liest jeden Riegel aus `pruefablauf.json` gegen den Bericht (`--alle-riegel`, P-PK-29 bis -32). | P-BR-03 (freiwillig) |

---

## 2. Maschinell geprüft

| Paket | Mittel | Gegenstand | Zahl |
|---|---|---|---|
| BR-01 | `python3 tools/quelltext/bestand.py --selbstprobe` | je Regel ein eingebauter Fehler, dazu Gegenproben | **22 Fälle, 0 Fehlschläge** (19 Fehler, 3 Gegenproben, 6 von 6 Regeln abgedeckt) |
| BR-01 | `bash tools/quelltext/pruefen.sh --selbstprobe` | die sechs Selbstproben des Läufers | **6 von 6** |
| BR-01 | `python3 tools/quelltext/bestand.py` | 17 Ordner, 24 Anleitungen, 20 Proben, 2 lose Dateien | **57 Befunde** — rot, gewollt bis BR-02 |
| BR-01 | `python3 tools/kettenaufrufe/pruefen.py --probe`, dann ohne Schalter | Aufrufe in 4 Workflows und 44 Proben | **16 von 16**; 76 Aufrufe, 0 Befunde, 2 ungeprüft |

Im Browser ist nichts zu prüfen: BR berührt weder `server/` noch eine
Android- oder Uhr-Oberfläche.

---

## 3. Prüfliste

- [ ] **P-BR-01 — Stufe 1 fährt den neunten Riegel und liest ihn gegen.**
  *Weg:* im PR von BR den Lauf „Prüfung" → Job `Stufe 1` öffnen.
  *Erwartet:* Schritt „Quelltext — neun Prüfungen, ein Läufer" zeigt
  „6 von 6 Selbstproben grün" und „9 von 9 Prüfungen grün"; der Schritt
  „Prüfbericht gegenlesen" endet mit „Prüfbericht in Ordnung" und rc 0.
  *Scheitern erkennbar an:* „8 von 9" (der Altbestand ist nicht auf null),
  oder „Riegel „bestand" steht in pruefablauf.json, läuft aber nicht im Tor",
  oder „Riegel „bestand": Bericht …, im Tor gemessen …".
- [ ] **P-BR-02 — Der Bericht im Kopf-Commit nennt `bestand=0`.**
  *Weg:* `git log -1` auf dem PR-Kopf.
  *Erwartet:* ein Block `Prüfstand: …` mit `bestand=0` unter den Riegeln.
  *Scheitern erkennbar an:* `bestand=1` oder kein `bestand` im Block.
- [ ] **P-BR-03 (freiwillig) — Ein eingebauter Fehler macht Stufe 1 rot.**
  *Weg:* auf einem Wegwerfzweig eine Zeile an `tools/kette/LIESMICH.md`
  hängen, bis sie 41 Zeilen hat, Prüfstand fahren, PR öffnen.
  *Erwartet:* Stufe 1 rot, Befund `form · tools/kette/LIESMICH.md: 41
  Zeilen, erlaubt sind 40`. *Scheitern erkennbar an:* Stufe 1 grün.
- [ ] **P-BR-04 — `kettenaufrufe` meldet einen vertippten Namen.**
  *Weg:* in `tools/pruefstand/pruefablauf.json` örtlich
  `pruefen.sh bestand` in `pruefen.sh bestnd` ändern,
  `python3 tools/kettenaufrufe/pruefen.py` fahren, zurücksetzen.
  *Erwartet:* 1 Befund „kennt den Befehl "bestnd" nicht".
  *Scheitern erkennbar an:* 0 Befunde.

---

## 4. Grenzen der Prüfmittel

- **`bestand` misst Form, nicht Inhalt.** Ob eine Anleitung stimmt, ob der
  Anlass zur Probe passt und ob ein Werkzeug überflüssig ist, sieht er
  nicht (E-BR-03). Eine Anlass-Zeile mit einer existierenden, aber falschen
  Nummer ist für ihn grün.
- **„Gerufen" heißt „genannt".** Die Inventur sucht `tools/<ordner>/` als
  Zeichenkette in vier Quellen; ein Kommentar dort zählt so viel wie ein
  Aufruf.
- **Die Einstiegsdatei einer Probe kommt aus `proben.sh`.** Wer eine Probe
  an `RUF` vorbei startet, entzieht sie dem Riegel — der Ordner ohne Eintrag
  ist dann aber ein Befund.
