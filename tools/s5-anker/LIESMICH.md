# S5-Anker — Fundstellen am Inhalt wiederfinden

```
python3 tools/s5-anker/anker.py            # alle 52 Anker
python3 tools/s5-anker/anker.py --knapp    # nur Abweichungen
python3 tools/s5-anker/anker.py --paket A  # nur die eines Arbeitspakets
```

Rückgabewert 0 = jeder Anker genau so oft gefunden wie erwartet;
1 = mindestens einer fehlt oder ist mehrdeutig.

**Die Zahl schrumpft, und das gehört so.** Angefangen hat die Liste mit 83.
Wer ein Paket abschließt, trägt die Anker aus, deren Stellen er umgeschrieben
hat — sonst meldet das Werkzeug beim nächsten Lauf `NICHT GEFUNDEN` für etwas,
das erledigt ist, und die Meldung, die etwas heißen soll, geht darin unter.
Ausgetragen: **A und B** (11, Paket B), **neun von D** (D Hälfte 1) und
**vierzehn** nach dem Merge von Paket C und D Hälfte 2 — zehn davon in
`watch/`, wo C `Pair.mc` neu geschrieben hat, dazu die beiden Handbuch-Anker
und `claude.watch-fehlt`. Die Begründungen stehen als Kommentar an der Stelle,
an der sie standen.

**Was jetzt noch dasteht, gehört fast nur Paket E** (Android-Zusatz, eigene
Instanz) und ein paar Stellen, die S5 mitliest, aber nicht ändert. **Die
Liste hat ihre Arbeit getan:** Jeder ausgetragene Anker hat vorher einmal
`NICHT GEFUNDEN` gemeldet — genau die Auskunft, für die er da war. Mit dem
Abschluss von S5 geht das Verzeichnis (siehe „Danach").

## Wozu

`docs/konzepte/Konzept-S5-Kopplung-umgekehrt.md` belegt jede Aussage mit
einer Fundstelle samt Zeilennummer — 38 Verweise, am 02.09.2026 an `main`
(`c2ac707`) erhoben und alle richtig. Diese Nummern halten bis zum nächsten
Paket, das dieselben Dateien anfasst. **S7 ersetzt „Sicherung" durch
„Backup"** und berührt dabei `einstellungen.php` (46 Treffer),
`jobs_lib.php` (22), `update.php` (16), `Handbuch.md` (71), `Technik.md`
(114). Danach zeigt jede Nummer auf etwas anderes.

Das Konzept deswegen umzuschreiben wäre die falsche Antwort: Die Nummern
sind **Beleg**, kein Wegweiser — sie sagen, was zum Zeitpunkt der Erhebung
dastand. Dieses Werkzeug sucht statt ihrer den **Text** und sagt, wo er
heute steht.

## Drei Antworten, und die dritte ist die wichtige

| Befund | Bedeutung |
|---|---|
| `unveraendert` | Zeile steht noch dort, wo das Konzept sie nennt |
| `verschoben um ±n` | Stelle ist da, nur gewandert — nichts zu tun |
| `MEHRDEUTIG (n× statt m)` | Das Muster taugt nicht mehr, **oder** es gibt die Stelle jetzt mehrfach. Nachsehen |
| `NICHT GEFUNDEN` | Jemand hat die Stelle umgeschrieben oder entfernt. **Dann ist der Konzeptabsatz dazu neu zu lesen** — nicht die Zeilennummer neu zu raten |

Ein fehlender Anker ist damit eine Auskunft und keine Panne. Genau dafür ist
das Werkzeug da: Es macht sichtbar, welche Annahme des Konzepts ein anderes
Paket weggeräumt hat, **bevor** jemand danach baut.

## Wann es läuft

- **Nach dem S7-Merge**, vor dem ersten Arbeitspaket — der eigentliche Zweck.
- **Vor jedem Paket**, um zu sehen, ob das eigene vorige Paket eine Stelle
  des nächsten mitgenommen hat.
- **Am Ende**, wenn die Anker der abgelösten Sachen (`pair_codes`,
  „Kopplungscode erzeugen", `nadoku.beispieldomain.de` in `watch/`)
  planmäßig `NICHT GEFUNDEN` melden sollen — dann ist das die Gegenprobe zur
  Konsistenzlesung von Paket D.

## Was es nicht ist

Kein Prüfmittel im Sinn von `CLAUDE.md` 6. Es misst nichts an der Anwendung,
sondern hält ein Dokument und den Code aneinander. Es ersetzt weder die
Wortliste noch den Bilderlauf noch das Lesen.

## Danach

Mit dem Abschluss von S5 wird dieses Verzeichnis **gelöscht** — zusammen mit
dem Konzept (Rahmenplan R62). Die Anker beschreiben einen Zustand, den es
danach nicht mehr gibt.
