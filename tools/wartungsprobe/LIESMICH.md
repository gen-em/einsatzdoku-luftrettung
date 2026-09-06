# Wartungsprobe — sperrt der Wartungsmodus das Richtige?

```bash
sh tools/referenzdatensatz/einspielen/lokal_starten.sh   # laufende Installation
php tools/wartungsprobe/probe.php                        # Vorgabe http://127.0.0.1:8080
php tools/wartungsprobe/probe.php https://127.0.0.1:8443
```

Rückgabewert 0 = alle Erwartungen erfüllt, 1 = mindestens eine nicht.

> **Diese Probe legt den Schalter um.** Sie schreibt `server/wartung.lock`
> und räumt sie im `finally` wieder weg — auch bei einem Abbruch. Findet sie
> beim Start schon eine vor, stellt sie deren Inhalt am Ende wieder her und
> sagt es in der ersten Zeile. **Auf einer Installation mit Betrieb nicht
> fahren:** Für die Dauer des Laufs ist die Installation geschlossen.

> **Sie führt Migrationen aus, und zwar an zwei Stellen.** Das ist keine
> Neuerung von Teil 6: **Erwartung 16** ruft seit jeher `php update.php` auf,
> und das ist der einstufige Notausgang — er führt aus und schreibt ins
> Register. Neu ist nur, dass **Teil 6** vorher eine Zeile herausnimmt.
>
> Teil 6 nimmt **eine** Zeile aus `schema_migrations`, misst daran, drückt
> „Ausstehende ausführen" und legt sie im `finally` mit ihrem
> **ursprünglichen Zeitpunkt** zurück. Drei Vorkehrungen:
>
> - Die Kennung wird **gesucht**, nicht hingeschrieben — sie muss im Register
>   stehen **und** eine `skip`-Prüfung haben, die wahr liefert. Sonst führte
>   der Knopfdruck echtes SQL aus.
> - Steht auf der Installation ohnehin etwas offen **oder blockiert**, drückt
>   die Probe nicht. Zwei Gründe: Der Knopf führt *alle* ausstehenden
>   Migrationen aus, darunter solche, die Spalten löschen — und eine blockierte
>   Migration zählt nicht als `offen`, stünde aber als zweite Zeile in der
>   Karte und machte die Messung falsch. Erwartung 21 wird dann rot, 22 bis 27
>   melden „nicht gemessen".
> - Zurückgelegt wird **mit `applied_at`**. Ein bloßes Neuanlegen setzte
>   `CURRENT_TIMESTAMP` und änderte still, was `migrationen_stand()` auf zwei
>   Karten als „zuletzt … am …" anzeigt und was der Bilderlauf fotografiert.
>
> **Was ein harter Abbruch hinterlässt.** Der `finally` läuft bei einer
> Ausnahme, aber **nicht** bei einem PHP-Fatal, einem `exit` oder einem
> Abbruch von außen (Strg-C, Zeitgrenze). Dann steht `server/wartung.lock`
> noch und die Registerzeile fehlt. Beides ist von Hand zu richten: Datei
> löschen, `php update.php` einmal fahren — es trägt die Zeile als `skipped`
> nach. Und **zwei Läufe zugleich** sind nicht vorgesehen; die Probe kennt
> keine Sperre.

## Wozu — eine Sperre kann auf zwei Arten falsch sein

Der Wartungsmodus (S5 Paket W) beantwortet während eines Updates jede Anfrage
außer denen der Verwaltung mit **503**. Das ist der Unterschied zwischen
„kaputt" und „gleich wieder da": Uhr und Handy behandeln 5xx nach dem
JSON-Vertrag (Abschnitt 5) als „später unverändert erneut" und liefern nach.
Ein **500** aus einer halb umgebauten Datenbank ist für sie etwas anderes.

Die Probe misst beide Richtungen:

- **Zu wenig gesperrt** — eine Uhr kommt während der Migration durch und
  schreibt in ein Schema, das gerade geändert wird (Fälle 1–5).
- **Zu viel gesperrt** — die BetreiberIn kommt nicht mehr an
  `betrieb_updates.php`, und die Installation bleibt zu, bis jemand per SSH
  eine Datei löscht (Fälle 6–12, 16).

Seit S8/AP2 liegt der Schalter auf **Betrieb → Updates**, nicht mehr auf
`update.php`. Die **fünf** Betriebsseiten stehen deshalb in der Ausnahmeliste
— ohne den Eintrag antwortete ausgerechnet die Seite mit dem Ausschalter mit
503 (F-S8-P-04). Fall 6 misst genau das, für alle fünf. `update.php` selbst
ist seit S8/AP3 eine **302** auf Betrieb → Updates; Fall 7 prüft, dass die
alte Adresse im Wartungsmodus weiterleitet statt 503 zu antworten.

## Was sie prüft — 50 Erwartungen in sieben Teilen

| Teil | Fälle (Nummern aus Konzept 6.1) |
|---|---|
| 0 Vergleichsmaß | ohne `wartung.lock` antwortet `index.php` normal; `ingest.php` ohne Zugangsdaten gibt 401 wie immer — und die Dauer dieser Antwort ist der Vergleichswert für Fall 15 |
| 1 gesperrt | `index.php` ohne Sitzung → 503 mit `Retry-After: 300`, `Cache-Control: no-store`, **ohne `Set-Cookie`** (1) · `einsatz.php` mit Nutzer-Sitzung → 503 HTML (2) · `ingest.php` mit **gültigem** Geräteschlüssel → 503 `maintenance` **und keine Zeile in `missions`** (3) · `pair.php` `start` → 503 JSON (4) · `api/kopplung_stand.php` mit Sitzung → 503 mit `meldung` (5, E-S5W-10) |
| 2 offen | `betrieb_updates.php` mit BetreiberIn-Sitzung → 200 mit Balken und Ausschalt-Knopf, die vier übrigen Betriebsseiten ebenso → 200 (6) · `betrieb_updates.php` mit Nutzer-Sitzung → 403 wie sonst, **nicht** 503, und `update.php` → 302 statt 503 (7) · `jobs.php` mit gültigem Token → 200, die Jobs laufen (8, E-S5W-11) · mit falschem Token → 403 `token` (9) · `login.php` → 200 mit Balken **und** Formular (10) · `wiederherstellen.php` mit Admin-Sitzung → nicht 503 (11) · `assets/style.css` → 200 (12) |
| 3 Schalten | Ausschalten über `betrieb_updates.php` (POST, CSRF) → Datei weg, Startseite antwortet wieder; Einschalten → Datei da, mit Zeitpunkt und Konto (13) · `wartung.lock` mit kaputtem Inhalt → **trotzdem 503**, Balken „seit unbekannt" (14) · das 503 kommt schneller als die Antwort ohne Wartung — das Tor greift vor Datenbank und Ratenschutz (15) |
| 4 Kommandozeile und Regeln | `php update.php` läuft im Wartungsmodus (16, Notausgang) · die Ausnahmeliste ist **genau** die aus E-S5W-04 plus die fünf Betriebsseiten aus S8/AP2 und AP4 — elf Einträge (17) · `login.php` liest `role`, prüft es seit S8/AP1 über `rolle_darf_verwalten()` und verwirft im Wartungsmodus die Sitzung ohne Verwaltungsrecht, und zwar **erst nach** `rate_erfolg` (18, E-S5W-09) |
| 5 die Seite | Stylesheet verlinkt, **kein Skript**, beide Sätze da (19) · das Logo wirft in 20 Aufrufen beide Standardlogos (20) |
| 6 eine Zählweise | Register ohne die jüngste Kennung, Schema aktuell: Vorbedingung — nichts offen, nichts blockiert (21) · Status nennt „1 Migration steht aus" (22) · Menüzähler an „Updates" nennt dieselbe 1 (23) · die Karte nennt „1 Update" und nicht „Alles aktuell" (24) · die Zeile trägt die neutrale Plakette „nicht nötig" (25) · der Knopf „Ausstehende ausführen" ist da (26) · nach dem Klick 0 offen, Register `skipped`, und die Meldung sagt es (27) — **Backlog Nr. 149** |

**Die Zahl, die zählt, steht in der letzten Zeile:** `-> 50 Erwartungen, 0
nicht erfuellt`.

**Das verwaltende Konto der Probe trägt seit S8/AP2 die Rolle `betreiberin`**,
nicht `admin`: `betrieb_updates.php` beginnt mit `require_betreiberin()`. Ein
bloßer `admin` käme dort nicht hinein, und die Probe mäße dann den Wächter
statt des Tors.

## Wie sie an ihre Sitzungen kommt

**Sie meldet sich nicht an.** Die Anmeldung leitet das Token im Browser per
PBKDF2 ab (`assets/crypto.js`); mit `curl` wäre sie nur nachzubilden, indem
man die Ableitung ein zweites Mal schreibt — und zwei Kopien einer
Schlüsselableitung sind genau die Art Duplikat, die dieses Projekt vermeidet.

Stattdessen schreibt die Probe die **PHP-Sitzungsdatei** direkt (dieselbe
`session.save_path`, dieselbe Maschine) und schickt deren Kennung als Cookie.
Was darin steht, ist das, was `auth_guard.php` nach einer gelungenen Anmeldung
vorfindet: `user_id`, `epoch`, `last_seen`, `csrf`.

> **Alle Sitzungen entstehen VOR der ersten gedruckten Zeile.** `session_id()`
> und `session_start()` scheitern, sobald PHP etwas ausgegeben hat. Der erste
> Entwurf las das CSRF-Token später nach, bekam einen Leerstring, und die drei
> Schaltfälle scheiterten mit 403 — an der Probe, nicht an der Anwendung. Wer
> hier eine Sitzung ergänzt, legt sie oben an.

## Was sie NICHT prüft — und wo es steht

- **Das Verhalten des Deploys gegenüber `wartung.lock`** (Konzept 6.3). Die
  Ausnahme in `.github/workflows/deploy.yml` ist eine **Zusage**; bewiesen
  wird sie beim ersten Deploy im Wartungsmodus. Steht im Prüfdokument S5.
- **Wie die Wartungsseite aussieht.** Die Probe misst, dass sie kommt und was
  drinsteht. Ob sie bei 360 px überläuft, misst
  `tools/screenshots/aufnehmen.mjs --nur 41`.
- **Die Anmeldung im Wartungsmodus über HTTP.** Fall 18 liest die drei
  Regeln aus E-S5W-09 im Quelltext nach, statt sie zu fahren — der Zweig ist
  ohne abgeleitetes Token nicht erreichbar. Eine Regel, die am Code gelesen
  ist, steht als solche im Bericht.
- **Nebenläufigkeit.** Ein Aufrufer, nacheinander. „Jemand schaltet aus,
  während eine Uhr sendet" ist nicht gemessen; die Datei ist der Schalter, und
  eine Anfrage sieht sie entweder oder nicht.
- **Teil 6 misst den Fall „nicht nötig, nicht verbucht" — nicht die
  Migrationen selbst.** Ob eine Migration richtig migriert, steht anderswo;
  hier geht es allein darum, dass Status, Menü und die Seite Updates dieselbe
  Zahl nennen.

## Wenn etwas rot ist

| Bild | Wahrscheinliche Ursache |
|---|---|
| Fälle 1–5 grün, 6–12 rot | Die Ausnahmeliste in `wartung_lib.php` stimmt nicht mehr mit E-S5W-04 überein — Fall 17 sagt, welche |
| Fall 3 rot mit HTTP 200 | Das Tor sitzt nicht mehr vor `db()`. Die Zeile in `db.php` steht hinter `json_out()` **und** vor jedem `db()`-Aufruf; wer sie verschiebt, lässt Geräte in die Baustelle |
| Fall 13 rot mit HTTP 403 | Kein gültiges CSRF-Token — meist, weil eine Sitzung nach der ersten Ausgabe angelegt wurde (siehe oben) |
| Fall 21 rot | Auf dieser Installation steht eine Migration **offen oder blockiert**, oder keine verbuchte hat eine `skip`-Prüfung, die wahr liefert. 22–27 melden dann „nicht gemessen" — **das ist kein Fehler der Anwendung**, sondern der Ausgangslage. Erst `php update.php` fahren, dann erneut |
| Fälle 24–27 rot, 22 und 23 grün | Genau der Zustand aus Backlog Nr. 149: `migrationen_lauf()` zählt die Migration als offen, die Seite Updates sortiert sie aber unter *Ausgeführt* und zeigt keinen Knopf. Zwei Stellen gehören zusammen — der Filter **und** der `match` für die Plakette in `betrieb_updates.php` |
| Fall 15 rot | Das 503 dauert länger als die normale Antwort. Dann läuft vor dem Tor etwas, das nicht dort hingehört |
| Fall 6 rot mit HTTP 503 | Eine der fünf Betriebsseiten fehlt in `WARTUNG_AUSNAHMEN`. Die Seite mit dem Ausschalter sperrt sich dann selbst aus (F-S8-P-04) |
| Fall 6 rot mit HTTP 403 | Das Konto der Probe ist kein `betreiberin` mehr, oder eine Betriebsseite hat ihren Wächter gewechselt |
| Fall 16 rot | Der CLI-Notausgang von `update.php` ist getort. `wartung_cli()` muss `PHP_SAPI` prüfen, nicht die Adresse |
| Alles rot, HTTP 0 | Es läuft keine Installation — `sh tools/referenzdatensatz/einspielen/lokal_starten.sh` |
