# Integritätswache

Liegt auf Produktiv noch das, was ausgeliefert wurde — und hält die Sperre?
**Anlass: Nr. 140** — eine Datei, die niemand hochgeladen hat, fällt sonst nie auf.

## Aufruf

```bash
python3 tools/integritaetswache/wache.py <basis> --stand zeiger    # so läuft integritaet.yml
python3 tools/integritaetswache/wache.py <basis> --teil punktpfade # nur Teil 3 (Stufe 2, Staging)
python3 tools/integritaetswache/wache.py --selbstprobe
```

## Was es misst

Über HTTPS, ohne Zugangsdaten, gegen den Zeiger `produktion`: (1) jede Datei
unter `server/assets/` per SHA-256, (2) auf `login.php` alles, was den Weg
des Passworts bestimmt, (3) seit R17-19 die Punktpfade — sechs mit **403**,
`.well-known/` nicht 403, keine Kopie der `config.php` mit 200 (F-R17-18).
Täglich und nach jeder Auslieferung (`integritaet.yml`), Selbstprobe zuerst.

## Was es braucht

Den Zweig `produktion` (fehlt er, ist sie rot) und `WACHE_BASIS`. **Und eine
Benachrichtigung, die ankommt:** Rot meldet nur GitHub — täglich an das Konto,
das die `cron`-Zeile zuletzt geändert hat, nach einer Auslieferung an das, das
sie auslöste; ist dort die Meldung fehlgeschlagener Läufe aus, bleibt Rot
unbemerkt (F-R17-15).

## Erwartete Zahl

**0 Abweichungen, 0 nicht erreichbar**; Teil 3 „6 gesperrt", „3 von 3 Kopien
nicht ausgeliefert"; Selbstprobe **51 / 0** (25.10.2026). **Ist sie rot:**
`docs/Technik.md` 7, „Die Integritätswache ist rot" — zuerst nichts
überschreiben.

## Was es nicht kann

Keinen PHP-Quelltext, keine `config.php`, nicht die `.htaccess` — nur ihre Wirkung
(Nr. 263); keinen Angreifer mit Push-Recht, keine Datenbank. *(Bis R17-19: „mit FTPS".)*
