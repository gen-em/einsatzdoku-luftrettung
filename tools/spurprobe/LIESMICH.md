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

## Sie ändert nichts

Teil 3 verdichtet wirklich — in einer Transaktion, die am Ende zurückgerollt
wird, und die letzte Erwartung prüft nach, dass der Bestand danach unverändert
ist. Das Werkzeug lässt sich deshalb auch gegen einen Bestand fahren, den man
behalten will.

## Grenzen

- **Die Ausdünnung (Stufe 3) prüft sie nicht.** `spur_ausduennen()` gehört zu
  AP3 und existiert noch nicht.
- **Der Nachzügler-Fall** — Uhr liefert Punkte nach, während der Blob schon
  steht — wird hier nicht hergestellt. `spur_lesen_viele()` setzt beides
  zusammen; geprüft ist bislang nur der Weg ohne Nachzügler.
- **Der Vergleichsmaßstab ist der quantisierte Bestand**, nicht der rohe. Das
  Format sagt 10⁻⁶ Grad und 0,1 m zu (F-S2-01); wer gegen die rohe
  `DOUBLE`-Spalte prüfte, prüfte eine Genauigkeit, die nie versprochen war.
