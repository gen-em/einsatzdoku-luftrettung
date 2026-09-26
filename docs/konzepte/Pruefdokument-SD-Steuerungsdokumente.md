# Prüfdokument SD — Steuerungsdokumente schneiden

*Gehört zu `Konzept-SD-Steuerungsdokumente.md`. Beantwortet „was muss **ich**
noch tun?" — das Protokoll „ist es belegt?" steht im Statusblock und in
Abschnitt 9 des Konzepts. Stand: **SD-02 gebaut, 26.09.2026** (Zweig
`claude/serene-tesla-sqeno2`; `origin/main` `f5bddc2`, der Merge von PR #88,
ist aufgenommen). Wird mit jedem Paket fortgeschrieben.*

---

## 1. Was nicht geprüft werden konnte — und warum

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Stufe 1 auf GitHub für diesen Zweig** | Sie läuft auf Arbeitszweigen nur beim Pull Request, und der kommt mit SD-04. Örtlich ist der Prüfstand gefahren (Abschnitt 2, Stufe klein); ob das Tor dasselbe sagt, zeigt der PR-Lauf | P-SD-15 |
| **Ob eine Zeile in Abschnitt 6 wirklich erledigt ist** | Dreizehn Zeilen tragen den Vermerk „vermutlich erledigt" oder „löschen?" — das ist aus anderen Dokumenten geschlossen (Konzept PK: M1 am 21.09.2026 mit Migrationen von Hand; Fassung 110: Gerätetest erfolgt; Produktiv antwortet unter `nadoku.gen-em.org`), nicht an der Anlage gemessen. Gelöscht ist keine (E-SD-31) | Q-SD-01, Prüfliste 3.1 |
| **Ob die drei Zuarbeiten-Gruppen richtig sortiert sind** | Die Einteilung „jetzt / vor Schritt X / vor v1.0" ist aus der Spalte „Wann" der Fassung 124 gelesen; drei Zeilen ohne klares „Wann" (SPF/DKIM, BR-Fragen, „Freigabe je Konzept") stehen in 6.1 | Q-SD-01, Prüfliste 3.1 |
| **Ob die Kurzfassungen Sinn verloren haben** | Register-Statussätze (R51 bis R85 von Absätzen auf ≤ 160 Zeichen), Erledigt-Zeilen (31 Blöcke von bis zu 90 Zeilen auf je eine) und die Fahrplanzeilen sind Kürzungen; gezählt sind Zeichen, nicht Sinn. Der Volltext liegt in Archiv-2 | Prüfliste 3.2 |
| **Die Zuordnung der 40 Backlog-Einträge ohne genannten Schritt** | Zwei unabhängige Leser je Eintrag und ein Schiedsrichter bei Abweichung (Abschnitt 2) — gelesen, nicht entschieden. Die Entscheidung ist Q-SD-01 (b) | Prüfliste 3.3 |
| **Die Decken mit dem Werkzeug** | `tools/steuerung/decken.py` entsteht erst mit SD-03; gemessen hat ein Wegwerfskript im Scratchpad (nicht eingecheckt). P-SD-14 misst denselben Stand noch einmal mit dem Werkzeug | P-SD-13, P-SD-14 |
| **Der Prüfstand mit `origin/main` als Basis — bis zur Aufnahme von `main`** | SD-01 und SD-M1 liefen mit `--basis bdf1787` (der Zweig saß auf dem AR-Zweig, `android/` berührt, kein Android-SDK im Container). Seit `1596469` (PR #88 gemergt, `main` aufgenommen) fährt der Prüfstand gegen `origin/main`; berührt sind nur `docs/` und `tools/` | Commit-Nachrichten SD-01, SD-M1, SD-02 |
| **Ob die 52 Kurzfassungen des Backlogs Sinn verloren haben** | Gekürzt ist von Hand nach Konzept 4.7 (Befund, Wirkung, Weg, Entscheidung bleiben; Messreihen und Werdegang entfallen); gezählt sind Zeilen, nicht Sinn. Der Volltext liegt in `docs/Backlog.md@f5bddc2`, und jeder gekürzte Eintrag nennt ihn in seiner letzten Zeile | Prüfliste 3.2 |
| **Wie GitHub die Nummern der Einträge zeigt** | Hier rendert nur `cmark-gfm`: eine Liste `<ol start="21">` mit 105 `<li>`. Dass ein Browser daraus 21, 22, 23 … macht, folgt aus HTML und ist nicht auf GitHub angesehen (F-SD-08) | Prüfliste 3.4 |
| **Die Kopfzeilen mit dem Werkzeug** | `tools/steuerung/uebersicht.py --pruefen` (P-SD-09) entsteht mit SD-03; gemessen hat `grep -P` mit der Grammatik aus Konzept 4.6, und das Zusammensetz-Skript hat jedes Ziel gegen die Kennungen der Fahrplan-Tabelle gehalten | P-SD-09 mit SD-03 |

**Vorbedingungen** (Konzept 3.2): 10c ist gemergt (PR #89, 26.09.2026); PR #88
(AR) ist gemergt (`f5bddc2`) und nach `Pruefablauf.md` 5.3 aufgenommen
(`1596469`), bevor SD-02 den Backlog angefasst hat. Offene Pull Requests, die
Rahmenplan oder Backlog anfassen, am 26.09.2026 nach dem Merge von #88:
**keine** (nachgesehen über die GitHub-Schnittstelle; Sperre in Konzept 4,
E-SD-19).

---

## 2. Maschinell geprüft

| Paket | Prüfpunkt | Mittel | Gegenstand | Zahl |
|---|---|---|---|---|
| SD-01 | P-SD-02 | `diff <(tail -n +28 docs/Rahmenplan-Archiv-2.md) <(git show 26101e1:docs/Rahmenplan.md)` | Archiv-2 ab der Trennlinie gegen Fassung 124 | **0 Unterschiede**; 27 Kopfzeilen + 3 901 = 3 928 Zeilen |
| SD-01 | P-SD-02 | `sha256sum` | Arbeitskopie `docs/Rahmenplan.md`@`bdf1787` = `origin/main`@`d34908b` (AR fasst den Rahmenplan nicht an) | gleich (`2d6357c8…`) |
| SD-01 | P-SD-03 | `wc -l docs/Rahmenplan.md` | Zeilen gesamt (Decke 500, Ziel beim Schnitt < 400) | **487** — Decke gehalten, **Ziel verfehlt** (F-SD-01) |
| SD-01 | P-SD-03 | Zeilen bis zur ersten `## ` | Kopf (Decke 15) | **15** |
| SD-01 | Decken 5 | Wegwerfskript über die Tabelle in Konzept 5 | Blockquote-Zeilen · Berichtigungsmuster (`hier stand`, `stand bis Fassung`, `berichtigt mit Fassung`, `ÜBERHOLT`) | **0 · 0** |
| SD-01 | Decken 5 | dasselbe | Fahrplan-Zellen: Inhalt ≤ 300, Status ≤ 240, erstes Wort aus dem Vokabular | **14 Zeilen, 0 über der Decke, 0 außerhalb des Vokabulars** |
| SD-01 | Decken 5 | dasselbe | Blöcke in Abschnitt 3 (≤ 30 Zeilen) | **2 Blöcke** (12: 13, 12a: 17 Zeilen) |
| SD-01 | Decken 5 | dasselbe | Zuarbeiten-Zeilen 6.1–6.3 ≤ 300 Zeichen | **69 Zeilen, 0 über der Decke** (nach zwei Kürzungen) |
| SD-01 | Decken 5 | dasselbe | Register: Kern ≤ 160, Status ≤ 160 | **85 Zeilen, 0 über der Decke** |
| SD-01 | Decken 5 | dasselbe | Erledigt-Zeilen ≤ 400 | **34 Zeilen, 0 über der Decke** |
| SD-01 | Decken 5 | dasselbe | Verlaufszeilen ≤ 300 | **3 Zeilen, 0 über der Decke** |
| SD-01 | P-SD-04 | `awk`/`grep` gegen `git show 26101e1:docs/Rahmenplan.md` | Fahrplanzeilen alt → neu | **27 → 14**: 11 aus Fassung 124 (6, 11, 12, 12a, 13, 14, 17, 18, Betriebsübergang, Kette II, SD), **3 neu** (AR, BV, PK — E-SD-09); 16 erledigte Zeilen ausgetragen |
| SD-01 | P-SD-04 | dasselbe | Zuarbeiten-Zeilen alt (nicht durchgestrichen) → neu | **67 → 69**: 67 eins zu eins, **2 neu** (Tag für 10c aus der Fahrplanzeile; Kette-II-Prüfpunkte 26–28 aus der Fahrplanzeile); 42 durchgestrichene nur noch in Archiv-2 |
| SD-01 | P-SD-04 | dasselbe | Register-Zeilen | **85 = 85**; R42 und R64 mit Statussatz (Nr. 193) |
| SD-01 | P-SD-04 | dasselbe | Erledigt: Blöcke alt → Zeilen neu | **31 → 34**: 31 aus den Blöcken, **3** aus den Fahrplanzeilen 1 (S4 Merge), 2 (S6), 3 (S5 Konzept), die keinen Block hatten (E-SD-30) |
| SD-01 | P-SD-04 | dasselbe | 6a-Schritte | **10 = 10** (E-KH-21) |
| SD-01 | P-SD-06 | `grep` je Datei unter `docs/konzepte/` (Konzept-*, Review-*) in Abschnitt 3 | Konzepte mit Fahrplanzeile | **9 von 9** (AR, BV, Kette II, PK, Planung v1.0, S4, SD, V1-Ortsdaten, Review-Krypto) |
| SD-01 | Nr. 196 | `cmark-gfm -e table --to html` | `docs/Rahmenplan.md`: `<pre>` · `<table>` · `<blockquote>`; `Rahmenplan-Verlauf.md` | **0 · 9 · 0**; **0 · 1 · 0** |
| SD-01 | Vorarbeit SD-M1/SD-02 | Fächerung: 105 offene Backlog-Einträge, je Block von 9 zwei unabhängige Leser, Schiedsrichter bei Abweichung (32 Agenten, nur lesend; E-SD-33) | genannte Zuordnung, Stand, Aufnahmedatum, Zeilenzahl | **105 gelesen; 97 einig, 8 Schiedsrichter**; **40 ohne genannten Schritt** (Konzept: „mindestens 18"); Stand: 76 offen, 23 teilweise, 4 zurückgestellt, 2 nicht umsetzen; **55 über 20 Zeilen** (Konzept: 66 bei 113); 11 ohne Aufnahmedatum im Text; 2 sagen selbst „umgesetzt, Prüfung offen" (213, 227) |
| SD-01 | Prüfstand | `bash tools/pruefstand/pruefen.sh --basis bdf1787` | Stufe klein, die Riegel | Bericht in der Commit-Nachricht von SD-01 |
| SD-02 | P-SD-07 | `diff <(sed -n '/^## Erledigt/,$p' docs/Backlog-Erledigt.md) <(git show f5bddc2:docs/Backlog.md \| sed -n '/^## Erledigt/,$p')` | Erledigt-Teil gegen Fassung N; die Werdegang-Absätze gegen Zeilen 34–271 des alten Kopfes | **0 Unterschiede** (228 Einträge); **0** |
| SD-02 | P-SD-08 | `grep -cE '^[0-9]+\. '` und `diff` der sortierten Nummern | offene Einträge alt = neu | **105 = 105**, Mengen gleich |
| SD-02 | P-SD-09 | `grep -cP` mit der Grammatik aus Konzept 4.6; Zusammensetz-Skript gegen die Kennungen der Fahrplan-Tabelle | Kopfzeilen; Ziele; Stände | **105 von 105**; Ziele 17 × 54, PK × 9, nach v1.0 × 8, 18 × 7, 14 × 6, SD · 13 · 12 je 5, 12a · Zuarbeit · Pflegeaufgabe je 2, **0** außerhalb; Stände offen 73, teilweise 24, nur auf Anlass 5, nicht umsetzen 2, zurückgestellt 1 |
| SD-02 | P-SD-10 | Zusammensetz-Skript (Zeilen je Eintrag, Kopfzeile eingeschlossen); `grep -c 'Werdegang bis 26.09.2026'` | Einträge über 20 Zeilen; Werdegang-Zeilen | **0** (acht Einträge mit genau 20: 259, 263, 266, 275, 300, 318, 324, 332); **52 = 52** gekürzte |
| SD-02 | P-SD-11 | `cmark-gfm -e table --to html docs/Backlog.md` | `<pre>` · `<li>` · `<table>` | **0 · 105 · 1** (die Reservierungstabelle); eine Liste `<ol start="21">` (F-SD-08) |
| SD-02 | P-SD-12 | `comm -12` der Nummern beider Dateien; `uniq -d` je Datei; `python3 tools/quelltext/bestand.py` | Schnittmenge; doppelt; Regel `backlog` über beide Dateien | **0; 0 / 0; 0 Befunde** — Selbstprobe **141 Fälle, 0 Fehlschläge** (neuer Fall: eine Nummer in beiden Dateien) |
| SD-02 | Decken 5 | `grep -n '^## Offen'`; `grep -vE` auf die Einrückung; Berichtigungsmuster; `^ *>` | Kopf bis `## Offen` (≤ 60); Folgezeilen ohne fünf / mit mehr als fünf Leerzeichen; Muster; Blockquotes | **56; 0 / 0; 0; 0** |
| SD-02 | Umfang | `wc -l` | `docs/Backlog.md` Fassung N → neu; `docs/Backlog-Erledigt.md` | **11 329 → 1 788**; **8 041** |
| SD-02 | Prüfstand | `bash tools/pruefstand/pruefen.sh` | Stufe klein, die Riegel, gegen `origin/main` | Bericht in der Commit-Nachricht von SD-02 |

---

## 3. Prüfliste für die Betreiberin

### 3.1 Q-SD-01 (a) — die Zuarbeiten durchsehen (SD-M1)

**Erledigt am 26.09.2026** (anklickbar in sechs Runden, je vier Zeilen):
27 Zeilen gestrichen, 42 bleiben, 6a Schritte 7 bis 9 abgehakt — E-SD-34 im
Konzept, Verlaufszeile 128. Ein Widerspruch bleibt benannt (F-SD-05).

**Bedienweg (war):** `docs/Rahmenplan.md` Abschnitt 6 öffnen. Drei Gruppen: 6.1
(42 Zeilen), 6.2 (12), 6.3 (15), dazu 6a (10 Schritte, 3 offen). Jede Zeile
trägt `seit`. **Erwartung:** Zu jeder Zeile eine von drei Antworten —
*erledigt* (die Zeile wird gelöscht, mit Verlaufszeile), *bleibt* (ggf. mit
anderer Gruppe), *falsch* (was stattdessen gilt). **Scheitern:** eine Zeile,
die weder erledigt noch verständlich ist — dann fehlt der Bedienweg, und die
Zeile bekommt einen aus Archiv-2 6.

Die dreizehn Zeilen mit Vermerk „vermutlich erledigt" oder „löschen?", alle
in 6.1: `update.php` für S9/9a/P5b · Signaturschlüssel verwahren ·
GitHub-Umgebungen und Pflichtfreigabe · GitHub-App am Handy / `CIQ_GERAETE_URL`
(halb) · V1 bis V9 · Nachträge an P5a · P5a-Reste · Data Layer auf echter
Hardware · Dienst-Test am S24 · DNS und TLS für `nadoku.gen-em.org` ·
`nachaufloesen.php` (der Nachlöse-Job aus P5a AP11?) · 2FA-Zwang (der
Zweigschutz steht) · Komplett-Backups auf Produktiv öffnen (Stand?).

### 3.2 Stichprobe der Kurzfassungen

**Bedienweg:** sechs Registerzeilen (R9, R37, R38, R42, R64, R67) und vier
Erledigt-Zeilen (S5, S10, P5b, P5c) gegen `Rahmenplan-Archiv-2.md` 7 und 8
lesen. **Erwartung:** Jeder Statussatz sagt, was gilt, und nichts Falsches;
jede Erledigt-Zeile nennt Versionen, Datum, PR und den letzten Commit richtig.
**Scheitern:** ein Satz, der den Stand von Fassung 124 verkürzt statt
zusammenfasst — dann ist es ein Befund F-SD-NN, und die Zeile wird ersetzt
(Verlaufszeile).

**Backlog (SD-02):** drei der 52 Kurzfassungen gegen `git show
f5bddc2:docs/Backlog.md` lesen — Nr. 37 (211 → 20 Zeilen), Nr. 202 (211 → 20)
und Nr. 229 (78 → 20). **Erwartung:** Befund, Wirkung und Weg stehen noch,
keine Zahl ohne Messdatum, die Werdegang-Zeile nennt `f5bddc2`.
**Scheitern:** ein Satz, der etwas anderes sagt als der Volltext — dann
F-SD-NN, und der Eintrag wird ersetzt; der Volltext bleibt erreichbar.

### 3.3 Q-SD-01 (b) — die 40 Backlog-Einträge ohne genannten Schritt

**Erledigt am 26.09.2026:** alle 40 Vorschläge übernommen (E-SD-35); die
Tabelle bleibt als Vorgabe für SD-02 stehen. **Q-SD-02** ist am selben Tag
entschieden: Decke 500 reicht (E-SD-36).

**Bedienweg (war):** die Tabelle unten. Spalte „Abschnitt 5 (F124)" ist die alte
Zuordnung im Rahmenplan, soweit es eine gab; „Vorschlag" ist, was SD-02 in
die Kopfzeile (`gehört zu`) schriebe. **Erwartung:** je Zeile ein Haken oder
ein anderes Ziel aus dem Vokabular (Kennung aus der Fahrplan-Tabelle, oder
`nächste Backlog-Runde` · `Zuarbeit` · `Pflegeaufgabe` · `nach v1.0`).
`nächste Backlog-Runde` heißt hier Schritt 17. **Scheitern:** ein Ziel, das es
in der Fahrplan-Tabelle nicht gibt — `uebersicht.py --pruefen` (SD-03) meldet
es dann als „ohne gültiges Ziel".

| Nr. | Titel (gekürzt) | Abschnitt 5 (F124) | Vorschlag `gehört zu` | Stand (aus dem Text) |
|---|---|---|---|---|
| 23 | `JSON-Vertrag.md` 3.3 nennt eine Reanimationsart, die kein Schreibweg annimmt | P7 | 13 | offen |
| 50 | Der Versand liest je Konto ein Verzeichnis | nach v1.0 | nach v1.0 | offen |
| 51 | Die Suchseite verarbeitet 5 000 Einträge, um 200 zu zeigen | nach v1.0 | nach v1.0 | offen |
| 52 | WebDAV als viertes Backup-Ziel | nach v1.0 | nach v1.0 | offen |
| 55 | Das Komplett-Backup kennt keinen scharfen Schnappschuss | nach v1.0 | nach v1.0 | offen |
| 90 | Der Simulator kann keinen Verbindungsabriss herstellen | nach v1.0 | nach v1.0 | offen |
| 92 | `pruefstand.sh bildreihe` fotografiert nur den Startbildschirm | Backlog-Runde | 17 | offen |
| 154 | Handy-App liest `kept_points` und `kept_meta` nicht | P7 (Store-Fassung der Apps) | 13 | offen |
| 157 | Handy-App: Sackgasse zwischen „Schlüssel abgewiesen" und „Gerät trennen" | P7 (Store-Fassung der Apps) | 13 | offen |
| 170 | Kein Prüfmittel misst, ob die Kennzeichnung vollständig ist | Backlog-Runde | 17 | offen |
| 172 | Eine Erwartung der Wartungsprobe flackert | Backlog-Runde | 17 | offen |
| 187 | Alle „Anhebungs"-Wege werden mit NaDoku 1.0 abgeschafft | vor 1.0 | 14 | offen |
| 201 | `Retry-After` in Uhr und Handy auswerten | P7 (Store-Fassung der Apps) | 13 | offen |
| 207 | `gen-em.org` steht 96× in `tools/` und `.github/` | Schritt 17 | 17 | offen |
| 209 | `Design.md` führt die erzeugte Bausteintabelle mit falschen Zeilen | Schritt 17 | 17 | offen |
| 210 | `ingest.php` läuft bei gleichzeitigen Uploads auf denselben Diensttag in Deadlocks | Schritt 18 | 18 | teilweise |
| 213 | Die Zustandsdatei der Auslieferungskette lag im Webroot | Kette II (AP2–AP8) | PK | teilweise (Text: umgesetzt, Prüfung offen) |
| 216 | Zwei Trennlinien hintereinander an vier Stellen des P5a-Prüfdokuments | Schritt 17 | 17 (oder erledigt — das Prüfdokument P5a ist gelöscht) | offen |
| 228 | Proof-of-Work im Browser als dritte Stufe gegen Registrierungs-Spam | niedrig; nur auf Anlass | 18 | nur auf Anlass |
| 229 | Die Uhr sagt „abgemeldet", wo „gesperrt" steht | P7 (Store-Fassung der Apps) | 13 | zurückgestellt |
| 232 | Die Fristen der Rückfragen sind nie im Betrieb abgelaufen | niedrig; wenn die nächste Frist dazukommt | 17 | nur auf Anlass |
| 234 | Kein Prüfmittel fährt den Weg einer frisch ausgelieferten Anlage | Kette II (AP2–AP8) | PK | teilweise |
| 236 | `ubuntu-latest` wandert am 19.10.2026 auf Ubuntu 26 | Kette II (AP2–AP8) | PK | offen |
| 237 | Der Täter-Finder des Bilderlaufs findet den Täter nicht | Kette II (AP2–AP8) | PK | offen |
| 239 | `backup_lib.php` baut sein `INSERT` ohne Backticks | Schritt 17 | 17 | offen |
| 240 | Der Rundlauf-Prüffall des Handy-Moduls läuft in der Kette nie | Kette II (AP2–AP8) | PK | offen |
| 261 | Staging bewahrt zwei Komplett-Stände auf — der Hotfix-Weg braucht mehr | Zuarbeit (Prüfpunkt 28), vor M2 | Zuarbeit | offen |
| 263 | Die Integritätswache sieht nur, was öffentlich abrufbar ist | offen — eigener Durchgang | 12 | offen |
| 266 | `plattform_pruefen()` sagt „aus", wo „nicht feststellbar" stehen müsste | — | 17 | offen |
| 271 | Die leere Meldungshülle im Schnittblock trägt kein Symbol | — | 17 | offen |
| 272 | `<p class="meldung">` im Entsperrdialog ist keine Meldung | — | 17 | offen |
| 273 | Eine dritte Schreibweise für Dauern, die kein Zählmittel sieht | — | 17 | offen |
| 275 | Der Referenzdatensatz kennt keinen Dienst über Mitternacht | — | 17 | offen |
| 277 | `einstellungen.php` — ein `await fetch` ohne eigenes `catch` | — | 17 | offen |
| 280 | Die Kartenquelle OpenHikingMap steht in keiner Lizenzliste | — | Pflegeaufgabe | offen |
| 283 | Die Textprobe liest die Kommentare in `server/` nicht | — | 17 | teilweise |
| 290 | Keine Stufe der Kette richtet eine Anlage ein | Backlog-Runde (oder das nächste Paket an der Kette) | PK | offen |
| 323 | Referenzbestand und Demo-Fixture tragen noch Nutzlast 11 | Backlog-Runde (Vorschlag) | 17 | offen |
| 333 | Ein Kommentar in `style.css` nennt für `.map` → `.geo` den falschen Anlass | — | 17 | offen |
| 336 | Der Baustein `Eingabefeld` des Handy-Moduls wird nirgends aufgerufen | — | 17 | offen |

Dazu, ohne Frage: **22 offene Nummern hatten in Abschnitt 5 der Fassung 124
keine Zeile** (250–252, 259, 265, 266, 271–273, 275–277, 280, 283, 294, 295,
331–336), und **8 Zeilen dort galten Nummern, die nicht mehr offen sind**
(40, 57, 65, 184, 194, 212, 214, 222) — der Befund F-P5c-134 in Zahlen.
Von den 22 nennen 13 im eigenen Text ein Ziel; die übrigen 9 stehen oben.

### 3.4 Lesbarkeit

**Bedienweg:** `docs/Rahmenplan.md` auf GitHub öffnen. **Erwartung:** neun
Tabellen, kein grauer Kasten, der Kopf passt ohne Rollen auf einen Bildschirm.
**Scheitern:** eine Tabelle, die als Text erscheint — dann steht ein `|`
ungeschützt in einer Zelle.

**Backlog (SD-02):** `docs/Backlog.md` auf GitHub öffnen. **Erwartung:** kein
grauer Kasten bei den 105 Einträgen, die Reservierungstabelle als Tabelle.
**Bekannt und nicht Gegenstand:** Die angezeigten Nummern laufen 21, 22, 23 …,
weil GitHub eine Liste fortzählt (F-SD-08); die Nummer im Markdown ist die
gültige.

### 3.5 Kopfzeilen, die SD-02 selbst entschieden hat

**Bedienweg:** die Kopfzeilen der folgenden Einträge in `docs/Backlog.md`
lesen (E-SD-38, E-SD-39). **Erwartung:** Ziel und Stand passen zum Text.
**Scheitern:** eine Zuordnung, die die Betreiberin anders will — dann wird die
Kopfzeile geändert; das ist eine Zeile, kein Paket.

| Nr. | Kopfzeile sagt | Warum |
|---|---|---|
| 21 | 12 · offen | „Zuordnung: P6" |
| 36, 37 | 17 · teilweise | P3 und S2 sind erledigt; der Rest sind ein Prüfmittel und eine Seitengrenze |
| 46 | 14 · teilweise | ein Paket mit 187 (Nr. 324), nicht „nach v1.0" |
| 62 | 17 · offen | die Logovorlagen liegen vor (E-SD-34) |
| 80 | Zuarbeit · teilweise | offen ist nur die Datenschutzerklärung (6.3) |
| 140 | 17 · teilweise | alles gebaut oder erledigt; nur die Verschiebung fehlt |
| 177 | SD · offen | statt zurückgestellt — SD erledigt ihn |
| 193 | SD · teilweise | zwei Sätze bleiben für SD-04 (F-SD-07) |
| 198, 200 | 17 · nicht umsetzen | geschlossen, nicht erledigt |
| 202 | 17 · nur auf Anlass | Schritt 15 ist durch, Paket 6 ist Beifang |
| 227, 295 | PK / 17 · teilweise | erledigt im Text, nicht verschoben |
| 233 | 18 · offen | „S10c" → Sicherheitsrunde II (wie Archiv-2 5) |
| 260 | 17 · offen | Kette II/AP5 liegt auf `main`; eine Web-Stufe fehlt |
| 264, 265 | PK / 17 · nur auf Anlass | die Auslöser stehen im Text |
| 335 | 17 · offen | „nach dem Merge von P5c" |

Die 40 Zuordnungen aus 3.3 sind E-SD-35; die übrigen 46 stehen so im Text des
Eintrags.

---

## 4. Grenzen der benutzten Prüfmittel

- **Das Deckenskript** ist ein Wegwerfskript; es misst Zeilen und Zeichen mit
  denselben Mustern, die Konzept SD 5 nennt, aber es ist nicht das Werkzeug,
  das ab SD-03 im Tor läuft. Eine Zahl hier ist ein Vorgriff, keine Abnahme.
- **Die Fächerung** hat gelesen, was im Eintrag steht — zwei Leser und ein
  Schiedsrichter senken das Risiko einer Fehllesung, sie ersetzen nicht die
  Entscheidung. Wo Leser A und B abwichen (8 Fälle: 46, 62, 210, 227, 228,
  295, 299, 335), steht das Urteil mit Begründung im Scratchpad der
  Sitzung, nicht im Repositorium.
- **Das Zusammensetz-Skript** (Scratchpad, nicht eingecheckt) hat die
  Kopfzeilen gebaut und die Decken gemessen; es ist nicht `uebersicht.py`
  (SD-03). Die 52 Kürzungen sind Handarbeit — eine Kürzung kann einen Satz
  verlieren, den der Volltext hatte; deshalb nennt jede den Commit.
- **`cmark-gfm`** misst die Struktur (`<pre>`, `<li>`, `<table>`), nicht das,
  was GitHub daraus zeigt; die fortlaufende Nummerierung ist daraus
  geschlossen (F-SD-08).
- **Die Vermerke „vermutlich erledigt"** sind Schlüsse aus Dokumenten, keine
  Messungen an Staging oder Produktiv.
- **`cmark-gfm`** zählt Blöcke, nicht Lesbarkeit; eine Tabelle mit 500
  Zeichen je Zelle rendert und ist trotzdem schwer zu lesen.
