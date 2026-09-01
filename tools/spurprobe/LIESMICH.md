# Spurprobe

Beleg zu **S2/AP1**: Kommt aus einem SPUR1-Blob genau das zurück, was
hineinging?

## Warum es sie gibt

**Die Verdichtung löscht Zeilen.** Was danach fehlt, ist weg — es gibt keine
zweite Quelle. Die Rundlaufprüfung in `server/spur_lib.php` ist deshalb die
letzte Instanz vor einem unwiderruflichen `DELETE`, und dieses Werkzeug fährt
sie über den **ganzen** Referenzbestand statt über ein Beispiel.

Dass das nötig ist, hat der erste Lauf gezeigt: Er meldete Abweichungen bei
175 von 181 Spuren — „erwartet 780, gelesen 780". Dieselbe Zahl, ein anderer
Typ: `7800 / 10` ist in PHP `int(780)`, `round(780.0*10)/10` ist
`float(780.0)`, und `!==` prüft den Typ mit. Ohne diese Probe wäre das erst im
Betrieb aufgefallen, als Verdichtungsjob, der nie eine Zeile löscht.

## Aufruf

```
php tools/spurprobe/probe.php [konto]
```

Ohne Angabe läuft sie gegen `demo@gen-em.org`. Rückgabewert: `0` = alle
Erwartungen erfüllt, `1` = mindestens eine nicht, `2` = Konto nicht gefunden.

## Was sie prüft

| Teil | Frage |
|---|---|
| 1 | Kommt jede Spur unverändert zurück? Was kostet der Blob je Punkt? |
| 2 | Stehen Punktzahl, Stufe und Auflösung im Kopf — und wird ein Blob mit unbekannter Fassung oder Auflösung **abgelehnt**? |
| 3 | Liefern `spur_lesen_viele()` und `edbak_build()` vor und nach der Verdichtung dasselbe? Überlebt `next_seq`? Räumt der Löschweg Zeile **und** Blob ab? |
| 4 | Hält die **Ausdünnung** ihre Zusage (2 m waagerecht / 3 m senkrecht, gegen den *endgültigen* Streckenzug nachgemessen)? Bleibt der Höhenermittlung des Einsatzorts je Phase ein Punkt im ±300-s-Fenster? Was spart sie wirklich — in Byte, nicht in Punkten? |
| 5 | Die Prüffälle, die der Referenzbestand **nicht liefert**: Gleichstand beim zeitnächsten Punkt, eine Spur ganz ohne Höhe, eine Höhenspitze hinter einem höhenlosen Eckpunkt, der Abschnittsdeckel, `n_original` im Stufe-3-Kopf |

## Sie ändert nichts

Teil 3 verdichtet wirklich — in einer Transaktion, die am Ende zurückgerollt
wird, und die letzte Erwartung prüft nach, dass der Bestand danach unverändert
ist. Teil 4 rechnet nur und schreibt nichts, Teil 5 arbeitet auf erfundenen
Spuren. Das Werkzeug lässt sich deshalb auch gegen einen Bestand fahren, den
man behalten will.

## Grenzen

- **Sie dünnt nichts wirklich aus.** Teil 4 *rechnet* die Behalteliste über den
  ganzen Bestand und prüft sie; geschrieben wird nichts. Dass der Job das
  Ergebnis auch richtig einträgt, prüft `tools/jobprobe/`; dass die
  Uhr-Schnittstelle danach richtig antwortet, `tools/ingestprobe/`.
- **Schon ausgedünnte Spuren überspringt sie** und sagt, wie viele. Eine
  Stufe-3-Spur noch einmal auszudünnen ist nicht die Handlung, um die es geht:
  Ihre Punkte sind bereits die nötigen, ein zweiter Lauf behält fast alle, und
  der gemessene Anteil steigt, ohne dass sich etwas verbessert hätte. Genau das
  ist beim ersten Lauf passiert — 25 Spuren des Referenzkontos waren vom Job
  schon ausgedünnt, und der Anteil sprang von 37,7 auf 43,0 %. Die Zahl war
  richtig gerechnet und beschrieb etwas anderes, als ihre Beschriftung sagte.
  **Wer die volle Zahl braucht, setzt das Referenzkonto vorher neu auf und hält
  dabei die Jobs an** (`php jobs.php --pause 1800`).
- **Der Nachzügler-Fall** — Uhr liefert Punkte nach, während der Blob schon
  steht — wird hier nicht hergestellt. `spur_lesen_viele()` setzt beides
  zusammen; `tools/ingestprobe/` fährt ihn über echtes HTTP.
- **Der Vergleichsmaßstab ist der quantisierte Bestand**, nicht der rohe. Das
  Format sagt 10⁻⁶ Grad und 0,1 m zu (F-S2-01); wer gegen die rohe
  `DOUBLE`-Spalte prüfte, prüfte eine Genauigkeit, die nie versprochen war.
