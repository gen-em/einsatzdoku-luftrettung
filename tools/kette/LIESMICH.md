# Kette — die Werkzeuge der Auslieferung

Fünf Befehle, die die Kette braucht und die niemand von Hand fährt.
**Anlass: Nr. 219** — ein Kettenschritt, der nur gegen die eigene Anlage ging.

## Aufruf

```bash
python3 tools/kette/tor.py backup|pause|zustand|wartung-an|wartung-aus --basis <url> --token <t>
python3 tools/kette/freigabe.py            # --selbstprobe | urteil --laeufe … --baum … --stufe1-laeufe …
python3 tools/kette/zielprobe.py <url>     # --selbstprobe
python3 tools/kette/zustand.py             # --selbstprobe
python3 tools/kette/baumsuche.py --baum <sha> --fenster <n> [--ereignis pull_request] [--erster | --ausgabe f.json]
```

## Was es misst

**`tor.py`** hält die Hintergrundjobs an und wartet, bis ein Komplett-Backup
**fertig** und **neu** ist — ein Backup von gestern meldet auch „fertig".
**`freigabe.py`** entscheidet, ob ein Stand auf Produktiv darf: Stufe 1
**nach Baum** (ein grüner PR-Lauf mit grünem Job `Stufe 1` zählt, Konzept
TB), Staging **nach Commit und Herkunft**. **`zielprobe.py`** unterscheidet
liegt / fehlt / nicht feststellbar; **`zustand.py`** legt die Zustandsdatei
der Auslieferungsaktion hin, wenn sie fehlt. **`baumsuche.py`** findet die
grünen Stufe-1-Läufe mit demselben Baum — für „Schon gemessen?" (Fenster 30)
und für `freigabe.py` (Fenster 50, E-BR-09).

## Was es braucht

Eine erreichbare Anlage und ihr `JOBS_TOKEN`; `baumsuche.py` `gh` und `GH_TOKEN`.

## Erwartete Zahl

`freigabe.py --selbstprobe` **52 / 0**, `baumsuche.py --selbstprobe` **11 / 0**.
Jedes der fünf hat eine Selbstprobe; Stufe 1 oder die Kette fährt sie.

## Was es nicht kann

Nichts über den **Inhalt** der Auslieferung sagen — dafür die
Integritätswache. `tor.py` wartet oder bricht ab, schneller macht es nichts.
