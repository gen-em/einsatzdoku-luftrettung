# Spaltenregister

Führt das Register jede Spalte von `missions` — und keine, die es nicht gibt?
**Anlass: Nr. 276, 282** — eine tote Spalte, und ein Register ohne `start_sort`.

## Aufruf

```bash
php tools/spaltenregister/pruefen.php          # --selbstprobe
python3 tools/spaltenregister/wegprobe.py      # --basis · --konto · --passwort
```

## Was es misst

`pruefen.php` hält `schema.sql` gegen `mf_missions_register()`, in beiden
Richtungen, und die drei Abbildungen, die von Hand bleiben, auf
Vollständigkeit. `wegprobe.py` fährt die zwei Anweisungen, die kein
Kreislauf abdeckt — Schnitt und den UPDATE-Zweig des Imports — und liest die
entstandene Zeile.

## Was es braucht

`pruefen.php` nur `php`. `wegprobe.py` eine örtliche Anlage und das
Umlaufkonto des edbak-Kreislaufs; ohne Angabe nimmt sie es aus
`kreislauf.py`, und der Prüfstand fährt den Kreislauf vorher (`nach`).

## Erwartete Zahl

`pruefen.php --selbstprobe` → **16 von 16**, Lauf **0 Befunde**. Die
Wegprobe: alle Erwartungen erfüllt, rc 0.

## Was es nicht kann

`pruefen.php` misst die Form, nicht das Verhalten — ob eine erzeugte
Anweisung das Richtige tut, sagen Kreislauf und Wegprobe. Die Wegprobe
**schreibt**: Sie gehört an ein Wegwerfkonto, nie an das Demo-Konto.
