# pruefstand — Station B: ein Befehl vor dem Pull Request

## Aufruf

```
bash tools/pruefstand/pruefen.sh [--stufe klein|neben|haupt] [--basis origin/main]
                                 [--datei PFAD] [--gegen staging] [--voll] [--ohne-hochfahren] [--trocken]
python3 tools/pruefstand/auswahl.py --abdeckung | --selbstprobe | --stufe-ermitteln
                                    | --umfang-ermitteln --stufe S [--voll]
                                    | --motoren | --bilderlauf | --ererbt   (je --stufe S --umfang U)
python3 tools/pruefstand/bericht.py lesen [--commit C] [--basis B] [--riegel n=w …]
                                    [--alle-riegel] | --selbstprobe | erzeugen-doku
```

## Was es misst

`pruefen.sh` ermittelt Stufe (Versionssprung, neue Migration = haupt) und Umfang (voll in
haupt, an der Zehnergrenze, mit `--voll`; sonst schmal, E-PU-01), fährt die Anlage hoch, läuft
Riegel und Proben der Berührung (`demo: true` schiebt die Demo-Marke je Engine-Lauf, Nr. 322;
`motoren: true` je Engine, `motoren.md`) und **erzeugt** den Prüfbericht. Vier Ergebnisse: grün,
rot, nicht gemessen, `hinweis` (hält nicht auf, E-PU-05); in `neben` erbt er Grünes vom Vorgänger (E-PU-13).
`auswahl.py`: welche Probe zu welcher Datei (`pruefablauf.json`); `bericht.py lesen`: die
Gegenlesung in Stufe 1 (Lagen: `docs/Pruefablauf.md` 5.1).

## Was es braucht

Die Ausbaustufe `web` (`tools/sandbox/`); für `android-bau` das Android-SDK, für
`uhr-stufe1` `CIQ_GERAETE_URL`, für die Schemaprobe das Modul `plattform`. **Was fehlt, wird gezählt und benannt, nicht übersprungen.**

## Erwartete Zahl

`bericht.py lesen --selbstprobe` → **48 / 0**, `auswahl.py --selbstprobe` → **103 / 0**, `--abdeckung` → **0 ohne
Muster**. `pruefen.sh` → 0 rot, 0 nicht gemessen, rc 0 (ohne Bericht rc 1); klein ohne `server/` die **23** Riegel.

## Was es nicht kann

Der Bericht ist ein **Nachweis, kein Riegel** (`Pruefablauf.md` 5.2); ein Erbe hängt an Mustern, die von Hand gepflegt sind (5.4).
Stufe, Umfang aus `server/version.php`, unlesbar ist rot; `--gegen staging` nur lesend (E-PK-29).

*Anlass: O9c, Nr. 217, Nr. 267, Nr. 314, Nr. 322, Nr. 335, Nr. 390, F-PK-30.*
