# Messprotokoll des Einspiellaufs

**Vorarbeit zu R19** (E-P1-14). Bemessungsgrundlage für den P5-Entwurf einer
Mengenbremse an `ingest.php`. **Erhebung, keine Schutzmaßnahme.**

## Was hier gemessen ist

Das Sendeverhalten **einer Uhr**, nicht die Geschwindigkeit des
Einspielskripts. Der Lauf schaufelt in Minuten, was im Betrieb über Tage
anfällt; die Wanduhr des Replays sagt über die Last nichts. Grundlage sind
die **Soll-Zeitpunkte** aus dem Sendeplan. Sie folgen den drei Auslösern der
Uhr (`grep syncAll watch/source`):

- stündlich während der Aufzeichnung (`Track.mc`, `REST_SYNC_INTERVAL_S`)
- am Ende jedes Einsatzes (`Model.mc`, `_endMission`)
- am Ende des Dienstes (`Model.mc`, `endDay`)

Ein Auslöser arbeitet die **Warteschlange** ab, nicht ein Paket:
`onResponse` ruft `_next()`. An einem Auslöser entstehen deshalb so viele
Anfragen, wie Teilstücke offen sind — und das ist die Zahl, auf die es
ankommt.

## Ergebnis

| Größe | Wert |
|---|---|
| Anfragen gesamt | 526 |
| Dienste | 16 |
| Pakete (Einsätze und Ruhe-Segmente) | 182 |
| Anfragen je Dienst | 16 … 45 (Median 34) |
| Teilstücke je Paket | 1 … 14 (Median 3) |
| Pakete in mehreren Teilstücken | 166 |
| Trackpunkte gesamt | 56587 |
| Body-Größe | 255 … 22785 Bytes (Median 2343) |
| Übertragen gesamt | 2.5 MB |
| **Fehlversuche** | **0** |
| Anfragen mit verworfenen Einzelwerten (`rejected`) | 0 |
| Anfragen mit übergangener Liste (`kept_*`) | 0 |

## Die Zahl, auf die es für P5 ankommt

**Der Soll-Abstand zwischen zwei Anfragen desselben Dienstes:**

| | Sekunden |
|---|---|
| kleinster Abstand | 0 |
| Median | 1020 |
| größter Abstand | 6600 |
| Abstände unter 60 s | 174 |
| Abstände von 0 s (gleicher Auslöser) | 174 |

**Ein Abstand von 0 s ist der Regelfall, nicht die Ausnahme.** Er entsteht,
wann immer ein Auslöser mehrere offene Teilstücke vorfindet — die Uhr sendet
sie unmittelbar hintereinander, weil die Antwort das nächste Paket anstößt.
Eine Mengenbremse, die auf Abstände zwischen einzelnen Anfragen schaut,
würde genau das treffen, was die Uhr korrekt tut.

**Die Spitze je Dienst** (die meisten Anfragen an einem Auslöser):

| Dienst | Anfragen | Zeitpunkt |
|---|---|---|
| D02 | 3 | 2026-02-08T13:26:00+01:00 |
| D07 | 3 | 2026-05-10T14:54:00+02:00 |
| D09 | 3 | 2026-07-05T17:40:00+02:00 |
| D11 | 3 | 2026-08-16T14:20:00+02:00 |
| D12 | 3 | 2026-09-12T11:52:00+02:00 |

Daraus folgt für P5: Eine Bremse muss den **Ausbruch** zulassen und die
**Menge über die Zeit** begrenzen — nicht die Rate zwischen zwei Anfragen.
Wie hoch die Grenze liegt, entscheidet P5; diese Erhebung sagt nur, was
eine Uhr im Normalbetrieb tatsächlich tut.
