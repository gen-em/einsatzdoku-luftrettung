# Zählung

Hält jede Sache ihre eine Stelle? (Schritt 15, R83, E-ZE-24)
**Anlass: Nr. 202, 257** — eine Bibliothek in zehn fremden Dateien; `$CFG` blieb unter `tools/`.

## Aufruf

```bash
php tools/zaehlung/zaehlen.php                         # --selbstprobe · --stellen
php tools/zaehlung/zaehlen.php --zeile=Z19 --stellen   # eine Zeile, mit Datei und Zeile
```

## Was es misst

Je Sache eine Zeile in `register.php` mit einer **Decke**; liegt der
Ist-Wert darüber, schlägt die Zählung an. Gelesen wird mit dem Tokenizer in
drei zeilentreuen Sichten — Aufrufe, SQL und Literale, JavaScript —, damit
`--stellen` auf das Original zeigt. Felder und Regelarten stehen im Kopf von
`register.php`, die Sichten im Kopf von `zaehlen.php`.

## Was es braucht

Nichts außer `php`. Rückgabe 0 = jede Zeile auf oder unter ihrer Decke,
1 = eine darüber, 2 = nicht gelaufen.

## Erwartete Zahl

**0 über der Decke** (38 Zeilen, 24.09.2026); `--selbstprobe` → **34 von
34**. Wird eine Zeile rot: Eine neue zweite Stelle gehört an die eine, die
`grund` nennt; eine begründete Ausnahme kommt mit Grund in `ausser`; eine
falsche Decke wird im Konzept begründet, bevor sie steigt (E-ZE-24).

## Was es nicht kann

Sagen, ob ein Treffer erreichbar ist oder ob zwei Stellen dieselbe Sache
tun — das behauptet das Register. Es findet **keinen neuen Namen**: Z34 hält
eine Namensliste, und ein Formatierer unter neuem Namen fällt ihr nicht auf.
Der JS-Zerleger ist eine Heuristik; `vendor/` und Code, der zur Laufzeit
entsteht, sieht es nicht.
