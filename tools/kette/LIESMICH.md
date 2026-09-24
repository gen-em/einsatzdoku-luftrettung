# Kette — die Werkzeuge der Auslieferung

Vier Befehle, die der Auslieferungslauf braucht und die niemand von Hand
fährt.
**Anlass: Nr. 219** — ein Kettenschritt, der nur gegen die eigene Anlage ging.

## Aufruf

```bash
python3 tools/kette/tor.py backup|pause|zustand|wartung-an|wartung-aus --basis <url> --token <t>
python3 tools/kette/freigabe.py            # --selbstprobe | urteil --laeufe … --baum … --stufe1-laeufe …
python3 tools/kette/zielprobe.py <url>     # --selbstprobe
python3 tools/kette/zustand.py             # --selbstprobe
```

## Was es misst

**`tor.py`** hält die Hintergrundjobs an und wartet, bis ein Komplett-Backup
**fertig** und **neu** ist — ein Backup von gestern meldet auch „fertig".
**`freigabe.py`** entscheidet, ob ein Stand auf Produktiv darf: Stufe 1
**nach Baum** (ein grüner PR-Lauf mit grünem Job `Stufe 1` zählt, Konzept
TB), Staging **nach Commit und Herkunft**. **`zielprobe.py`** unterscheidet
liegt / fehlt / nicht feststellbar; **`zustand.py`** legt die Zustandsdatei
der Auslieferungsaktion hin, wenn sie fehlt.

## Was es braucht

Eine erreichbare Anlage und ihr `JOBS_TOKEN`; `freigabe.py` die GitHub-API.

## Erwartete Zahl

`freigabe.py --selbstprobe`: **52 erfüllt, 0 offen** (23.09.2026, TB-01).
Jedes der vier hat eine Selbstprobe; die Kette fährt sie vor dem Aufruf.

## Was es nicht kann

Nichts über den **Inhalt** der Auslieferung sagen — dafür die
Integritätswache. Und `tor.py` kann ein Backup nicht schneller machen: Es
wartet oder bricht ab.
