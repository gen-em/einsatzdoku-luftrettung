# Kette — die Werkzeuge der Auslieferung

Vier Befehle, die der Auslieferungslauf braucht und die niemand von Hand
fährt. **Anlass: F3, Nr. 219** — ein Lauf, der grün meldete, ohne
ausgeliefert zu haben.

## Aufruf

```bash
python3 tools/kette/tor.py pause --basis <url> --jobs-token <t>
python3 tools/kette/freigabe.py            # --selbstprobe | urteil --laeufe … --baum … --stufe1-laeufe …
python3 tools/kette/zielprobe.py <url>
```

## Was es misst

**`tor.py`** hält die Hintergrundjobs an und wartet, bis ein Komplett-Backup
**fertig** ist — zwei Bedingungen, nicht eine: `fertig` allein genügt nicht,
ein Backup von gestern meldet das auch. **`freigabe.py`** entscheidet, ob ein Stand
auf Produktiv darf: **Stufe 1 nach Baum** — ein grüner `pruefung.yml`-Lauf
zählt, wenn sein Commit denselben Baum hat wie der Tag-Commit **und** der
Job `Stufe 1` darin selbst grün war (Konzept TB; ein PR-Lauf zählt, der
Verweis-Lauf auf `main` nicht) — und **Staging nach Commit und Herkunft**:
ein Push auf einen Arbeitszweig zählt **nicht**.
**`zielprobe.py`** unterscheidet **liegt / fehlt / nicht feststellbar** —
ein „fehlt", das ein „ich konnte nicht nachsehen" war, hat die Kette schon
zu falschen Schlüssen gebracht.

## Was es braucht

Eine erreichbare Anlage und ihr `JOBS_TOKEN`; `freigabe.py` die GitHub-API.

## Erwartete Zahl

`freigabe.py --selbstprobe`: **52 erfüllt, 0 offen** (23.09.2026, TB-01;
vorher 32). Die zwanzig neuen Lagen prüfen den Baum — gleicher Baum mit
grünem, übersprungenem oder rotem Job, anderer, leerer, abgeschnittener
oder groß geschriebener Baum, fehlender Zielbaum.

## Was es nicht kann

Nichts über den **Inhalt** der Auslieferung sagen — dafür die
Integritätswache. Und `tor.py` kann ein Backup nicht schneller machen: Es
wartet oder bricht ab.
