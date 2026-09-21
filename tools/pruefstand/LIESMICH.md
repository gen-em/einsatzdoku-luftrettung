# pruefstand — Station B: ein Befehl vor dem Pull Request

## Aufruf

```
bash tools/pruefstand/pruefen.sh [--stufe klein|neben|haupt] [--basis origin/main]
                                 [--datei PFAD] [--gegen staging]
                                 [--ohne-hochfahren] [--trocken]
python3 tools/pruefstand/auswahl.py --abdeckung | --selbstprobe | --stufe-ermitteln
python3 tools/pruefstand/bericht.py lesen --selbstprobe | erzeugen-doku
```

## Was es misst

`pruefen.sh` ermittelt die Stufe aus dem Versionssprung, fährt die örtliche
Anlage hoch, läuft die Riegel und die Proben, die zur Berührung gehören, und
**erzeugt** den Prüfbericht für die Commit-Nachricht. `auswahl.py` beantwortet
„welche Probe zu welcher Datei" aus `pruefablauf.json`. `bericht.py` schreibt
den Block und liest ihn im Tor gegen (vier rote Lagen).

## Was es braucht

Die Ausbaustufe `web` (`tools/sandbox/`); für `android-bau` das Android-SDK,
für `uhr-stufe1` `CIQ_GERAETE_URL`, für die Schemaprobe das Modul
`plattform`. **Was fehlt, wird gezählt und benannt, nicht übersprungen.**

## Erwartete Zahl

`bericht.py lesen --selbstprobe` → **6 Lagen, 0 Fehlschläge** (5 rote, 1
grüne). `auswahl.py --selbstprobe` → **11 Lagen, 0 Fehlschläge**.
`auswahl.py --abdeckung` → **0 Dateien ohne Muster**. `pruefen.sh` → 0 rot,
0 nicht gemessen; der Rückgabewert ist 0. Stufe klein auf einem Zweig ohne
`server/`-Änderung: **13 Proben in rund 25 s** (gemessen 21.09.2026).

## Was es nicht kann

Der Bericht ist ein **Nachweis, kein Riegel**: Station B ist die geprüfte
Partei, und ein Bericht, der einen Lauf behauptet, den es nicht gab, kommt
durch (`docs/Pruefablauf.md` 5.2). Die Stufe wird aus `server/version.php`
gelesen — wer sie nicht hochstuft, bekommt „klein", auch wenn er umbaut.
`--gegen staging` misst **nur lesend** (E-PK-29).

*Anlass: O9c (gemessen vor der letzten Änderung), Nr. 217 (ein Aufruf, der
zur Schnittstelle nicht passte), Nr. 267 (nur MySQL 8.4 scheiterte).*
