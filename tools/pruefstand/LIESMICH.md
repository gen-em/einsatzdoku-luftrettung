# pruefstand — Station B: ein Befehl vor dem Pull Request

## Aufruf

```
bash tools/pruefstand/pruefen.sh [--stufe klein|neben|haupt] [--basis origin/main]
                                 [--datei PFAD] [--gegen staging] [--voll] [--ohne-hochfahren] [--trocken]
python3 tools/pruefstand/auswahl.py --abdeckung | --selbstprobe | --stufe-ermitteln
                                    | --umfang-ermitteln --stufe S [--voll]
python3 tools/pruefstand/bericht.py lesen [--commit C] [--basis B] [--riegel n=w …]
                                    [--alle-riegel] | --selbstprobe | erzeugen-doku
```

## Was es misst

`pruefen.sh` ermittelt Stufe (Versionssprung, neue Migration = haupt) und Umfang (voll in
haupt, an der Zehnergrenze, mit `--voll`; sonst schmal, E-PU-01), fährt die Anlage hoch, läuft
Riegel und Proben der Berührung (`demo: true` schiebt die Demo-Marke je Engine-Lauf, Nr. 322;
`motoren: true` je Engine, `motoren.md`) und **erzeugt** den Prüfbericht. Vier Ergebnisse: grün,
rot, nicht gemessen, `hinweis` (hält nicht auf; `hinweis: true` oder `HINWEIS: …`, E-PU-05).
`auswahl.py`: welche Probe zu welcher Datei (`pruefablauf.json`); `bericht.py lesen`: die
Gegenlesung in Stufe 1 (Lagen: `docs/Pruefablauf.md` 5.1).

## Was es braucht

Die Ausbaustufe `web` (`tools/sandbox/`); für `android-bau` das Android-SDK, für
`uhr-stufe1` `CIQ_GERAETE_URL`, für die Schemaprobe das Modul `plattform`. **Was fehlt, wird gezählt und benannt, nicht übersprungen.**

## Erwartete Zahl

`bericht.py lesen --selbstprobe` → **28 / 0**, `auswahl.py --selbstprobe` → **53 / 0**,
`--abdeckung` → **0 ohne Muster**. `pruefen.sh` → 0 rot, 0 nicht gemessen, rc 0 (ohne
Bericht rc 1); klein ohne `server/` die **23** Riegel, neben **44** (zuletzt 42 in 23 min, P5c/AP3).

## Was es nicht kann

Der Bericht ist ein **Nachweis, kein Riegel** (`docs/Pruefablauf.md` 5.2). Stufe und Umfang kommen aus
`server/version.php`; unlesbar ist rot. `--gegen staging` misst **nur lesend** (E-PK-29), ohne Demo-Marke.

*Anlass: O9c, Nr. 217, Nr. 267, Nr. 314, Nr. 322, Nr. 335, Nr. 390, F-PK-30.*
