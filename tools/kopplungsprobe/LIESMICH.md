# Kopplungsprobe — zwei Proben für denselben Vorgang

Hier liegen **zwei** Prüfmittel, und sie stellen verschiedene Fragen:

| Datei | Frage | Aufruf |
|---|---|---|
| `probe.php` | Antwortet `pair.php` so, wie der Vertrag es zusagt? | `php tools/kopplungsprobe/probe.php` |
| `rundlauf.mjs` | Kann ein Mensch mit einem Gerät in der Hand ein Konto damit verbinden? | `node tools/kopplungsprobe/rundlauf.mjs` |

Die erste misst den **Endpunkt**, die zweite den **Weg**. Beide brauchen eine
laufende lokale Installation (`sh tools/referenzdatensatz/einspielen/lokal_starten.sh`),
und in beiden ist die Probe selbst das Gerät: Sie holt sich ihre
Kopplungssitzung über `pair.php` mit `aktion=start`, wie eine Uhr es tut.

**Fahre sie nicht dicht hintereinander mit dem Bilderlauf.** Der Topf
`pair_start` lässt zwanzig `start`-Aufrufe je zehn Minuten und Adresse zu;
`tools/screenshots/` braucht zwei davon, der Rundlauf einen, und `probe.php`
füllt ihn in Fall 21 absichtlich ganz. Sie räumt ihn danach wieder — der
Bilderlauf tut das nicht.

---

## `rundlauf.mjs` — der Weg im Browser (S5 Paket B)

25 Erwartungen in einem Zug: anmelden · die drei Zustände der Karte „Gerät
koppeln" · beide Fehlerwege (Code mit „0", unbekannter Code) · die Eingabe
**mit Leerzeichen und klein geschrieben**, so wie ein Mensch abliest · die
Umleitung nach dem Beanspruchen · ein Neuladen im Wartezustand · das Ja am
Gerät und **das Nachladen von selbst** · Vollzugsmeldung, Geräteliste,
Rückkehr in Zustand 1 · Überlauf und Knopfhöhen · das Abmelden des Prüfgeräts.

```bash
node tools/kopplungsprobe/rundlauf.mjs
node tools/kopplungsprobe/rundlauf.mjs --bilder /tmp/kopplung   # mit Bildern
```

Er läuft im **Demo-Konto** — dort ist Ausprobieren erwünscht, und der Reset
alle 30 Minuten fängt auf, was ein Abbruch liegenlässt. Das Prüfgerät meldet
er am Ende über `aktion=trennen` wieder ab; die Zahl der Geräte vorher und
nachher steht im Bericht. **Bricht er mittendrin ab, bleibt ein Gerät im
Demo-Konto stehen** — bei fünf davon ist das Limit erreicht und der nächste
Lauf scheitert an einer Karte, die kein Feld mehr zeigt. Dann von Hand löschen
oder den Demo-Reset abwarten.

Konsolenfehler zählt er wie der Bilderlauf, mit derselben Rauschregel: Die
Kartenkacheln kommen von einem fremden Server, den ein abgeschotteter
Prüfstand nicht erreicht.

---

## `probe.php` — der Endpunkt


Seit Web 13.0.0 (S5, R49) läuft die Kopplung umgekehrt: Das Gerät holt sich
mit `start` eine Sitzung und **zeigt** den Code, ein Mensch tippt ihn im Web
in sein Konto, und das Gerät bestätigt mit Ja. Diese Probe fährt den Endpunkt
über **echtes HTTP** gegen eine laufende lokale Installation durch alle
Zustände, Fristen, Fehlerzweige und Bremsen (Konzept S5, Abschnitt 10.2).

## Aufruf

```bash
sh tools/referenzdatensatz/einspielen/lokal_starten.sh    # PHP-Server auf 127.0.0.1:8080
php tools/kopplungsprobe/probe.php [basisadresse] [protokoll]
```

Vorgabe: `http://127.0.0.1:8080` und `/tmp/php-server.log` (dorthin schreibt
`lokal_starten.sh` die Ausgabe des PHP-Servers; die Probe liest daraus, ob der
Mailversand nach der Antwort betreten wurde). Rückgabewert `0` = alle
Erwartungen erfüllt, `1` = mindestens eine nicht. Dauer rund 25 s — davon
stammen etwa acht Sekunden aus den Antwortzeit-Fällen, die je 0,35 s warten
müssen, und rund zehn aus 1000 eingefügten Sitzungen.

## Was sie prüft — 75 Erwartungen in sechs Teilen

| Teil | Fälle (Nummern aus Konzept 10.2) |
|---|---|
| 1 `start` | 405 bei GET (33) · Sitzung ohne Block, Form der Antwort, drei NULL-Werte, SHA-256-Hash (1) · Uhr-Form und Handy-Form aufgelöst (2, 3) · unsinniger Block (4) · Rumpf ohne `aktion` und Rumpf der alten Uhr → 400 mit 21-Zeichen-Meldung, zählt nicht (5, 6) |
| 2 Weg zum Gerät | `status` offen mit Restzeit (7) · ohne Kopfzeilen 401 (31) · `antwort` fehlt oder falsch → 400 (32) · Ja im Zustand offen → 409 (10) · Beanspruchen über die Bibliothek, `status` beansprucht mit maskierter Adresse (8), zweite Beanspruchung scheitert (9) · `ingest.php` vor dem Ja 401, danach 200 (14) · Ja legt das Gerät an, Sitzung weg (11) · Versandweg der Mail nach der Antwort (27) · Ja wiederholt (12), `status` gekoppelt (13), Nein mit Gerätezugang lässt das Gerät stehen (E-S5-48) · `trennen` unverändert (26) · Nein offen und Nein beansprucht (15, 16) · `trennen` mit schwebenden Zugangsdaten wirkt wie Nein (E-S5-49) |
| 3 Frist | nach elf Minuten: `status` 410 **ohne Verzögerung**, Ja 410, Beanspruchen scheitert, Zeile bleibt liegen, zählt nicht, Nein räumt trotzdem (17) |
| 4 Gerätelimit | fünf Geräte am Konto → Ja 409 `device_limit`, Sitzung weg, zählt nicht (18) |
| 5 Antwortgleichheit und Töpfe | unbekannte Kennung und falscher Schlüssel: gleiche Rümpfe, beide ≥ 0,35 s, beide zählen; ein gültiges `status` leert den Topf **nicht** (19) · zehn 401 → 429 auch mit richtigen Daten, auch für `trennen` (20) · zwanzig `start` → der 21. 429 ohne Sitzung (21) · 1000 unverfallene Sitzungen → 429 `zu_viele_sitzungen`, dieselben verfallen → 200 (22) · Topf `pair_code` sperrt und leert (23) · das Muster, auf das Paket B sich stützt (24) |
| 6 Bibliothek | Dublettenschleife beim Code-Ziehen mit eingeschobener Codequelle (25) · `email_maskieren()` (30) · Aufräumjob (28) · Migrationsregister: Kennung verbucht, `pair_sessions` da, `pair_codes` weg, 41 Kennungen (29) · Kontolöschung nimmt die beanspruchte Sitzung mit (34) |

**Die Zahl, die zählt, steht in der letzten Zeile:** `-> 75 Erwartungen, 0
nicht erfuellt, 0 uebergangen`. Eine Zeile `[ -- ]` heißt „übergangen“ und
wird gezählt, nicht verschwiegen — heute nur Fall 27, wenn das Protokoll
fehlt.

## Was sie NICHT prüft — und wo es steht

- **Den Text der Kopplungsmail.** Es gibt keinen Mailserver im Prüfstand;
  belegt ist nur, dass der Versandweg **nach** der Antwort betreten wird
  (Protokollzeile `SMTP connect`). Der Text steht im Prüfdokument S5 zur
  Sichtprüfung.
- **Die Code-Eingabe im Web** (Fall 24 am Formular, Aktionen
  `koppeln_pruefen` und `koppeln_bestaetigen`): Paket B. Hier laufen nur der
  Topf `pair_code` und das Muster `PAIR_RE`.
- **Die Migration als Lauf.** Sie ist von Hand gefahren (Konzept 9,
  Umsetzungsstand); die Probe liest nur den Zustand danach.
- **Die Uhr und das Handy.** Die Probe ist das Gerät. Den Rundlauf mit dem
  Simulator fährt Paket C, den Gerätetest das Prüfdokument.

## Wie sie mit dem Bestand umgeht

Sie legt zwei eigene Konten an (`kopplungsprobe@gen-em.org`,
`kopplungsprobe-zwei@gen-em.org`), merkt sich jede Sitzung, die sie über
`start` anlegt, und räumt im `finally` alles ab: Konten (Kaskade nimmt Geräte
mit), Sitzungen, die 1000 Zeilen aus Fall 22, die Hilfsgeräte aus Fall 18 und
die Spuren des einen Uploads aus Fall 14. **Die Ratenschutz-Töpfe `pair`,
`pair_start` und `pair_code` werden für `127.0.0.1` geleert** — vorher,
zwischen den Teilen und am Ende —, denn die Probe füllt sie absichtlich.
Die Jobs sind während des Laufs angehalten (`jobs_pause`); Fall 28 ruft den
Aufräumjob einmal von Hand, und der entsorgt dabei auch, was sonst im
Papierkorb fällig ist.

## Wenn etwas rot ist

| Zeile | Wo suchen |
|---|---|
| 19 „beide >= 0,35 s“ | `rate_gleiche_dauer()` in `ratelimit_lib.php`; `abweisen()` in `pair.php` |
| 19 „Ruempfe byteweise gleich“ | die beiden 401-Zweige in `pair.php` (Sitzung mit falschem Schlüssel, unbekannte Kennung) |
| 20 / 21 / 22 | `RATE_GRENZEN` und `PAIR_SITZUNGEN_MAX`; die Probe druckt die Grenzen in der zweiten Zeile |
| 22 dauert Minuten statt Sekunden | jemand hasht die 1000 Platzhalter mit bcrypt (V-S5-10) — sie brauchen keinen Hash |
| 27 | `lokal_starten.sh` schreibt nicht nach `/tmp/php-server.log`, oder `smtp_send()` schweigt bei fehlendem Host |
| 29 „41 Kennungen“ | eine Migration ist dazugekommen — Zahl in der Probe und in Konzept Z-19 nachziehen |
