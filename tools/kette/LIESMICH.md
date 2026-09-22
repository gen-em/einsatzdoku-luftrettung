# Kette — die Werkzeuge der Auslieferung

Vier Befehle, die der Auslieferungslauf braucht und die niemand von Hand
fährt. **Anlass: F3, Nr. 219** — ein Lauf, der grün meldete, ohne
ausgeliefert zu haben.

## Aufruf

```bash
python3 tools/kette/tor.py pause --basis <url> --jobs-token <t>
python3 tools/kette/freigabe.py            # --selbstprobe
python3 tools/kette/zielprobe.py <url>
```

## Was es misst

**`tor.py`** hält die Hintergrundjobs an und wartet, bis ein Komplett-Backup
**fertig** ist — zwei Bedingungen, nicht eine: `fertig` allein genügt nicht,
ein Backup von gestern meldet das auch. **`freigabe.py`** zählt die grünen
Läufe auf einem Commit; ein Push auf einen Arbeitszweig zählt **nicht**.
**`zielprobe.py`** unterscheidet **liegt / fehlt / nicht feststellbar** —
ein „fehlt", das ein „ich konnte nicht nachsehen" war, hat die Kette schon
zu falschen Schlüssen gebracht.

## Was es braucht

Eine erreichbare Anlage und ihr `JOBS_TOKEN`; `freigabe.py` die GitHub-API.

## Erwartete Zahl

`freigabe.py --selbstprobe`: **32 erfüllt, 0 offen** (21.09.2026).

## Was es nicht kann

Nichts über den **Inhalt** der Auslieferung sagen — dafür die
Integritätswache. Und `tor.py` kann ein Backup nicht schneller machen: Es
wartet oder bricht ab.
