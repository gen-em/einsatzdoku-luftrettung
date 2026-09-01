# Jobprobe — tut der Job-Rahmen, was er zusagt?

`php tools/jobprobe/probe.php`

Prüft `server/jobs_lib.php` und `server/jobs.php` (S2/AP2). Rückgabewert 0 =
alle Erwartungen erfüllt, 1 = mindestens eine nicht.

## Wozu

Der Rahmen hat drei Zusagen, und alle drei sind unsichtbar, solange nichts
schiefgeht — genau deshalb braucht es eine Probe:

1. **Drei Auslöser, eine Arbeit.** Ob `cli`, `token` oder `anfrage` — derselbe
   Rückstand muss verschwinden. Ein Weg, der still nichts tut, fällt sonst
   erst auf, wenn die Platte voll ist.
2. **Die gemeldete Zahl stimmt.** „erledigt 7" muss heißen, dass sieben Dinge
   weg sind. Eine Zahl, die niemand nachrechnet, ist Dekoration — in AP0 und
   AP2 stand je eine falsche in einem Bericht.
3. **Der Huckepack-Weg ist ein Rückfall.** Er darf nicht bei jeder Anfrage
   laufen und nicht länger als sein Budget dauern. Genau das war der erste
   Anlauf falsch: bis zu 18 s je Anfrage.

Dazu die Sperre (zwei gleichzeitige Läufe schließen sich aus, eine verwaiste
Sperre verfällt), die Tagesgrenze des Aufräumjobs und das Token.

## Sie ändert etwas — anders als die Spurprobe

Der Waisenjob löscht; das ist sein Zweck. Ein Job, der in einer
zurückgerollten Transaktion läuft, beweist nichts über sein Zusammenspiel mit
der Sperre — die ist auf `COMMIT` angewiesen. Die Probe legt deshalb **eigene
Waisen** an: Punkte auf Eigentümerkennungen oberhalb aller vergebenen
(`MAX(id) + 100 000`), und räumt am Ende hinter sich auf, auch bei einem
Abbruch (`finally`).

Sie fasst **keine bestehenden Daten** an. Was sie nicht verhindern kann und
auch nicht soll: dass der Waisenjob dabei *echte* Waisen abräumt, die schon
vorher dastanden. Das ist sein Zweck.

Der erste Anlauf dieser Probe verglich trotzdem `COUNT(*)` der ganzen Tabelle
und schlug deshalb an — 3 313 253 → 3 313 246, sieben echte Waisen. Teil 9
vergleicht seither, was einen **Eigentümer hat**: Daran darf der Job nicht
rühren. Die nebenbei abgeräumten echten Waisen stehen als Auskunft daneben,
nicht als Erwartung.

## Was sie nicht prüft

- **Den HTTP-Weg von `jobs.php`.** Die Probe ruft `jobs_lauf()` direkt. Ob
  Token-Prüfung, Ratenschutz und Antwortzeit-Angleichung in `jobs.php` greifen,
  ist im Browser bzw. mit `curl` zu prüfen (Prüfdokument S2).
- **Das Verhalten unter echter Last.** Die Zeiten hier sind an einem
  Referenzbestand von rund 3,3 Mio. Zeilen gemessen; für die Zielmenge Z2 gibt
  es keine Messung, sondern eine Rechnung.
- **Die Wartungsseite.** Was `update.php` anzeigt, ist Oberfläche und wird im
  Browser geprüft.

## Voraussetzungen

Eine eingerichtete Installation mit gelaufener Migration `2026_08_31_jobs`
(sonst schlägt Teil 1 an). Ein Referenzbestand ist nicht nötig — die Probe
bringt ihre eigenen Waisen mit —, macht die Zeiten in Teil 6 aber
aussagekräftiger.
