# Einspiellauf des Referenzdatensatzes

Spielt den erzeugten Datensatz über die **regulären Wege** ein — kein SQL (R4).

## Aufruf

```bash
sh lokal_einrichten.sh     # von Null: Datenbank, install.php, Admin, Demo-Konto
sh lokal_starten.sh        # nur hochfahren: MariaDB, PHP-Server, TLS davor
python3 einspielen.py --stufen konto
php  demo_kennzeichnen.php                     # zwingend vor der ersten Anmeldung
node passwort_setzen.mjs '<Einrichtungslink>' 'nadokudemo0815' rc.json
python3 einspielen.py --stufen stammdaten,geraet,ingest,zuordnen,nachtragen,manuell,papierkorb,sperrliste,schneiden
python3 messprotokoll.py && node sichtpruefung.mjs
```

## Was es misst

Sechs Wege: `pair.php`, `ingest.php`, `api/day.php`, `einsatz_form.php`,
`api/schneiden.php` und die Oberfläche. Stand der Stufen in `lauf.json`;
warum die Reihenfolge so ist, steht an der Stufe in `einspielen.py`.
`messprotokoll.py` misst das Sendeverhalten einer Uhr (E-P1-14).

## Was es braucht

Eine Installation hinter TLS auf `127.0.0.1:8443` — das Sitzungs-Cookie
trägt `secure`. Vorgaben: `admin@gen-em.org` / `pruefstandzugang2026`,
`demo@gen-em.org` / `nadokudemo0815`. `lauf.json` und `rc.json` gehören
**einer** Installation und stehen in `.gitignore`; `rc.json` öffnet ohne Passwort.

## Erwartete Zahl

Alle Stufen ohne Abbruch, so viele Schnitte wie in den Quelldaten (E-DA-11),
`sitzungsprobe.py` **2 von 2** (beide Hüllenfassungen) — in rund vier Minuten.

## Was es nicht kann

Den Browserschritt nachbauen (E-P1-10) oder auf `edka1:` umstellen
(E-S10-15); Kacheln und Mail sieht es nicht, und der Sperrlistenschritt geht
je Installation nur einmal.
