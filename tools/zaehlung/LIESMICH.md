# Zählung

Hält jede Sache ihre eine Stelle? (Schritt 15, R83, E-ZE-24)
**Anlass: Nr. 202, 257, 258** — eine Bibliothek in zehn fremden Dateien; `$CFG` blieb unter `tools/`.

## Aufruf

```bash
php tools/zaehlung/zaehlen.php                         # --selbstprobe · --stellen
php tools/zaehlung/zaehlen.php --zeile=Z19 --stellen   # eine Zeile, mit Datei und Zeile
```

## Was es misst

Je Sache eine Zeile in `register.php` mit einer **Decke**; liegt der
Ist-Wert darüber — **seit R17-19 auch darunter** (Ratsche, F-R17-56) —,
schlägt die Zählung an. Gelesen wird mit dem Tokenizer in
drei zeilentreuen Sichten — Aufrufe, SQL und Literale, JavaScript —, damit
`--stellen` auf das Original zeigt. Felder und Regelarten stehen im Kopf von
`register.php`, die Sichten im Kopf von `zaehlen.php`.

## Was es braucht

Nichts außer `php`. Rückgabe 0 = jede Zeile genau auf ihrer Decke,
1 = eine darüber oder darunter, 2 = nicht gelaufen.

## Erwartete Zahl

**0 über, 0 unter der Decke** (44 Zeilen, 25.10.2026); `--selbstprobe` →
**37 von 37**. Darüber: Die zweite Stelle gehört an die eine aus `grund`, oder
mit Grund in `ausser`; eine Decke steigt nur mit Begründung im Konzept
(E-ZE-24). Darunter: die Decke im selben Commit herunterschreiben.

## Was es nicht kann

Sagen, ob ein Treffer erreichbar ist oder ob zwei Stellen dieselbe Sache
tun — das behauptet das Register. Es findet **keinen neuen Namen**: Z34 hält
eine Namensliste, und ein Formatierer unter neuem Namen fällt ihr nicht auf.
Der JS-Zerleger ist eine Heuristik; `vendor/` und Code, der zur Laufzeit
entsteht, sieht es nicht.
