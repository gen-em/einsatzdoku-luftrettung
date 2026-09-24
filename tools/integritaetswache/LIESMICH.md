# Integritätswache

Liegt auf dem Produktivserver genau das, was ausgeliefert wurde?
**Anlass: Nr. 140** — eine Datei, die niemand hochgeladen hat, fällt sonst nie auf.

## Aufruf

```bash
python3 tools/integritaetswache/wache.py    # läuft als integritaet.yml
```

## Was es misst

Sie holt jede Datei unter `server/` vom Produktivserver und vergleicht sie
mit dem Stand im Zweig **`produktion`** — dem Zeiger auf den zuletzt
ausgelieferten Commit (E-KH-13). Gemeldet wird dreierlei: geändert, fehlt,
**steht dort und gehört nicht dorthin**.

**Ohne eingecheckte Prüfsummen:** Eine Liste im Repositorium wäre eine
zweite Wahrheit, die altert. Verglichen wird gegen git.

## Was es braucht

FTPS-Zugang zum Produktivserver und den Zweig `produktion`. **Fehlt der
Zweig, ist die Wache rot** und sagt, wie man ihn anlegt — sie läuft nie
still grün weiter.

## Erwartete Zahl

**0 Abweichungen** außerhalb der Ausnahmeliste (`config.php`,
`install.lock`, `wartung.lock`, `ueberlast.json`, `sicherungen/`, `apk/`,
`.sitzungen/`, `doku/`). Die Liste steht in der Wache, nicht hier.

## Was es nicht kann

Sie prüft **Dateien, nicht Verhalten** — ein Server, der die richtigen
Dateien hat und trotzdem falsch antwortet, fällt ihr nicht auf. Und sie
sieht nur `server/`; die Datenbank ist außerhalb.
