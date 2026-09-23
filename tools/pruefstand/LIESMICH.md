# pruefstand — Station B: ein Befehl vor dem Pull Request

## Aufruf

```
bash tools/pruefstand/pruefen.sh [--stufe klein|neben|haupt] [--basis origin/main]
                                 [--datei PFAD] [--gegen staging]
                                 [--ohne-hochfahren] [--trocken]
python3 tools/pruefstand/auswahl.py --abdeckung | --selbstprobe | --stufe-ermitteln
python3 tools/pruefstand/bericht.py lesen [--commit C] [--basis B] [--riegel n=w …]
                                    [--alle-riegel] | --selbstprobe | erzeugen-doku
```

## Was es misst

`pruefen.sh` ermittelt die Stufe aus dem Versionssprung, fährt die örtliche
Anlage hoch, läuft die Riegel und die Proben, die zur Berührung gehören, und
**erzeugt** den Prüfbericht für die Commit-Nachricht. `auswahl.py` beantwortet
„welche Probe zu welcher Datei" aus `pruefablauf.json`. `bericht.py lesen` ist
die Gegenlesung in Stufe 1 (PK-05): fünf rote Lagen, Riegelzahlen als `--riegel`.

## Was es braucht

Die Ausbaustufe `web` (`tools/sandbox/`); für `android-bau` das Android-SDK,
für `uhr-stufe1` `CIQ_GERAETE_URL`, für die Schemaprobe das Modul
`plattform`. **Was fehlt, wird gezählt und benannt, nicht übersprungen.**

## Erwartete Zahl

`bericht.py lesen --selbstprobe` → **13 Lagen / 0**, `auswahl.py
--selbstprobe` → **23 / 0**, `--abdeckung` → **0 Dateien ohne Muster**.
`pruefen.sh` → 0 rot, 0 nicht gemessen, rc 0 (sonst rc 1); klein ohne `server/` **14**.

## Was es nicht kann

Der Bericht ist ein **Nachweis, kein Riegel** (`docs/Pruefablauf.md` 5.2).
Die Stufe kommt aus `server/version.php`; unlesbar ist rot, nicht „klein".
`--gegen staging` misst **nur lesend** (E-PK-29).

*Anlass: O9c, Nr. 217, Nr. 267, F-PK-30.*
