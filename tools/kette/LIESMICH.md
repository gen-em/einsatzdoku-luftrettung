# Die Tore der Auslieferungskette

```
python3 tools/kette/tor.py --selbstprobe                              # ohne Netz
python3 tools/kette/tor.py backup      --basis https://… --token …
python3 tools/kette/tor.py wartung-an  --basis https://… --token …
python3 tools/kette/tor.py wartung-aus --basis https://… --token …
python3 tools/kette/tor.py zustand     --basis https://… --token … [--frage migration]
```

Rückgabewert: `0` = Tor offen · `1` = Tor zu (der Grund steht darüber) ·
`2` = die Prüfung kam nicht zustande (Angabe fehlt).

Nur Python 3, keine Abhängigkeiten. Gegenstelle ist `server/jobs.php` mit dem
Parameter `aktion` (E-P5a-12); das Token ist dasselbe wie für den
Zeitplandienst und steht im Wartungsbereich.

## Warum das nicht drei Zeilen im Arbeitslauf sind

Das Backup-Tor ist die eine Stelle der Kette, an der etwas Unwiderrufliches
verhindert wird: ein Deploy auf Produktiv ohne frisches Komplett-Backup. Eine
Bedingung, die das leisten soll, gehört nicht in ein YAML-Feld, in dem sie
niemand liest und niemand ausprobieren kann — sie gehört dorthin, wo eine
Selbstprobe sie nachweisen kann. Genau das verlangt die Abnahme von AP1:

> das Backup-Tor bricht **nachweislich** ab, wenn `komplett` nicht fertig
> meldet

## Zwei Bedingungen, nicht eine

`fertig` allein genügt nicht. Ein Backup, das schon gestern fertig wurde,
meldet ebenfalls `fertig` — und schützt diesen Deploy nicht. Der jüngste
Stand muss deshalb **jünger sein als der Laufbeginn**. Das ist der Unterschied
zwischen „es gibt ein Backup" und „es gibt ein Backup von diesem Stand".

## Warum eine Schleife

Der Token-Einstieg hat 20 s Budget je Aufruf (`JOB_BUDGET_TOKEN`); ein
Komplett-Backup von 10 GB braucht mehr. Ein Lauf arbeitet in Häppchen und
meldet je Aufruf, ob er fertig ist. Wer einmal ruft und das `fertig` glaubt,
hat bei kleinen Beständen recht und bei großen unrecht — und merkt den
Unterschied erst, wenn er das Backup braucht.

Vorgabe: **40 Aufrufe, 20 s Pause** (`--versuche`, `--pause`).

## Ein Nein ist kein Warten

Antwortet die Installation mit `error` — falsches Token, unbekannte Aktion,
kein Serverschlüssel, Speichergrenze erreicht —, bricht das Tor **sofort** ab.
Vierzigmal zu fragen hieße dreizehn Minuten zu warten auf eine Antwort, die
nach dem ersten Aufruf feststand. Ein **Netzfehler** oder eine unlesbare
Antwort ist etwas anderes: Die werden wiederholt.

## Selbstprobe

`--selbstprobe` ersetzt den Aufruf an `jobs.php` durch eine Attrappe mit
vorgeschriebenen Antworten und prüft fünf Lagen:

| # | Lage | Erwartung |
|---|---|---|
| 1 | fertig nach zwei Häppchen, frischer Stand | Tor **offen** |
| 2 | meldet nie `fertig` | Tor **zu** |
| 3 | falsches Token | Tor **zu**, sofort |
| 4 | fertig, aber Stand älter als der Laufbeginn | Tor **zu** |
| 5 | fertig, aber gar kein Stand vorhanden | Tor **zu** |

Das ist keine Bequemlichkeit: Die interessanten Lagen — „meldet nie fertig",
„Stand ist von gestern" — lassen sich gegen eine echte Installation nicht
herstellen, ohne sie zu beschädigen.

Der Produktionslauf ruft die Selbstprobe **vor** dem Tor. Ein Tor, das immer
aufgeht, sieht von außen aus wie eines, das geprüft hat.

Stand 15.09.2026: **5 von 5 erfüllt.**

## Was es nicht prüft

Ob die Gegenstelle wirklich der Produktivserver ist, ob das Backup lesbar ist
und ob der Serverschlüssel der richtige ist. Das erste ist Sache der Umgebung
(`PRODUKTION_URL`), das zweite und dritte Sache der
Wiederherstellungsprobe (`tools/wiederherstellungs-probe/`).
