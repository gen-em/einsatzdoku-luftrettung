# Prüfdokument PU — Der Prüfumfang folgt der Berührung

*Gehört zu `Konzept-PU-Pruefumfang.md`. In der Form, die PU selbst
einführt (E-PU-08): was nicht geprüft werden konnte, die Grenzen der
Mittel, die Handprüfliste. Die maschinellen Zahlen stehen im Prüfprotokoll
des Konzepts (Abschnitt 5) und im Prüfbericht jedes Commits. Nach dem Merge
geht dieses Dokument in `Pruefliste.md` auf (E-PU-09).*

## 1. Was nicht geprüft werden konnte — und warum

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Das Tor auf GitHub mit den Lagen 7 und 8** | Stufe 1 läuft erst auf dem Pull Request. Örtlich ist die Gegenlesung jedes Commits gefahren (`bericht.py lesen --basis origin/main`, in Ordnung), dazu ein Durchstich mit echtem Git in einer Wegwerfkopie (Lage 8 grün und mit geerbtem `edbak` rot) — der Lauf auf GitHub selbst nicht. | P-PU-01 |
| **Eine echte Nebenstufe mit einer Änderung unter `server/`** | Dieser Schritt fasst nichts unter `server/` an und stuft keine Fassung hoch; gefahren ist `--stufe neben` über diesen Baum. Damit greift das Muster `nebenstufe` nicht, und von den rund 30 Proben gegen die Anlage laufen nur die, die eine Werkzeugdatei auswählt. Wie viel ein schmaler Lauf bei einer typischen Nebenstufe spart, zeigt erst das nächste Paket mit Nebennummer. | P-PU-04 |
| **Der Bericht eines Laufs an der Zehnergrenze** | Die Zehnerregel ist an zwölf Grenzfällen der Selbstprobe gemessen; einen Versionssprung über eine Zehnergrenze gab es in diesem Schritt nicht. Der volle Lauf selbst ist mit `--voll` gefahren — derselbe Umfang, ein anderer Grund im Bericht. | P-PU-05 |
| **Stufe 2 und Produktiv** | Nicht angefasst (Konzept 6): Staging misst weiter dreifach. | — |

## 2. Grenzen der benutzten Prüfmittel

- **Ein Erbe ist so gut wie die Muster** (E-PU-13, Preis). Eine Datei, die
  in keinem Muster einer Probe steht, erbt deren Grün. E-PU-18 fängt den
  Prüfstand selbst und die Dateien, die ein Aufruf nennt; Werkzeuge, die
  eine Probe nur indirekt lädt, fängt es nicht.
- **Die Seitenwahl kennt Seiten nur über ihre eigene Datei** (E-PU-17). Ein
  Skript, das nur eine Seite lädt, gilt als geteilt und bringt alle Seiten
  — sicher, aber teurer als nötig. Die Bedienprobe fährt immer alle Wege.
- **Ein Hinweis hält nicht auf** (E-PU-05). Er steht im Lauf und im
  Bericht; wer ihn überliest, committet eine 41. Zeile oder eine gerissene
  Decke. Gekürzt wird trotzdem.
- **Die Changelog-Regel hat keinen Riegel** (E-PU-12); sie kann unbemerkt
  reißen.
- **Die Prüfliste ist von einem Einmalwerkzeug ausgezogen**, gegen
  Rahmenplan 6.1 als Maßstab. Was dort fehlte, fehlt auch in der Liste.

## 3. Handprüfliste

*P-PU-08 (E-PU-16 und -18 entscheiden) ist erledigt: bestätigt von der Betreiberin am 02.11.2026.*

| Nr. | Bedienweg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| P-PU-01 | Pull Request öffnen; Lauf „Prüfung" zum Kopf-Commit, Job `Stufe 1`, Schritt „Prüfbericht gegenlesen". | grün; „Prüfbericht in Ordnung: Stufe neben …" und darunter „Geerbt vom Vorgänger (Lage 8 nachgerechnet): …" mit den geerbten Proben. | rot mit „durfte nicht erben" oder „Der Vorgänger hat den Baum …" — dann liest das Tor den Vorgänger anders als der Prüfstand; Zeile an mich, nicht neu fahren. |
| P-PU-02 | Im selben Lauf die Schritte von `Stufe 1` zählen. | 16 Schritte, keiner heißt „Steuerungsdokumente". | Der Schritt steht noch, oder die Gegenlesung meldet „Riegel steuerung fehlt im Bericht". |
| P-PU-03 | Nach dem Merge: Lauf „Prüfung" auf `main`, Job „Schon gemessen?". | Er findet den grünen PR-Lauf mit demselben Baum und lässt `Stufe 1` aus. | „kein grüner PR-Lauf mit Baum …" bei grünem PR. |
| P-PU-04 | Beim nächsten Paket mit Nebennummer (z. B. Web 23.7.0): den Kopf des Prüfstandlaufs und die Dauer lesen. | „Umfang schmal", „Engines der Browserproben: chromium", Bilderlauf mit „fuenf" Breiten und nur den berührten Seiten, wenn nur Seitendateien berührt sind; Dauer deutlich unter den 12 205 s des letzten Nebenlaufs. | „voll" ohne Zehnergrenze; drei Engines ohne Stylesheet; zehn Breiten — dann liest `auswahl.py` die Berührung anders. |
| P-PU-05 | Beim ersten Paket, dessen Nebennummer 23.10 erreicht (oder einer Hauptstufe): Kopf des Laufs. | „voll:zehner" (bzw. „voll:haupt"), drei Engines, zehn Breiten, alle Seiten; Stufe 1 grün. | „schmal" — dann rechnet die Zehnerregel falsch; das Tor ist dann rot mit „Umfang zu schmal". |
| P-PU-06 | Im selben Zweig nach einer kleinen Berichtigung den Prüfstand ein zweites Mal fahren. | Zeilen „erbt NAME — grün im Bericht von HEAD"; der Kreislauf `edbak` und die Riegel messen trotzdem; der Lauf ist um die geerbten Proben kürzer. | Kein Erbe, obwohl nur Doku berührt ist (dann den Grund aus „erbt 0 von N Proben — sonst etwa: …" lesen). |
| P-PU-07 | Ein Paket mit Änderung an `style.css` oder `ui.php` in `neben`. | drei Engines, zehn Breiten, alle Seiten. | Chromium allein oder fünf Breiten. |
| P-PU-09 | `docs/konzepte/Pruefliste.md` durchsehen: Stimmen die Bereiche? Fehlt ein Punkt, den Sie offen wissen? Stichprobe: drei Zeilen aus Rahmenplan 6.1 gegen die Liste halten. | jede genannte Nummer steht in der Liste, im passenden Bereich | eine Nummer aus 6.1 fehlt — dann hat der Auszug sie nicht gefunden. |
| P-PU-10 | `CLAUDE.md` 2 (Changelog: fünf Sätze) und 7 (Prüfdokument: drei Teile, höchstens 20 Punkte; danach Prüfliste) lesen. | Die Regeln sagen, was gemeint war. | ein Satz, der anders gemeint war. |
