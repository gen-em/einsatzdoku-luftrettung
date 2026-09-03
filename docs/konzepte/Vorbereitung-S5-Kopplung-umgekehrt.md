# Vorbereitung der S5-Umsetzung — Prüfung des Konzepts und Prüfstand

**Rahmenplan Schritt 5 · Zweig `claude/s7-umsetzung-vorbereiten-s8kax0` ·
Stand 03.09.2026, erhoben an `main`, Commit `696449d` (Web 12.9.4, Uhr 2.0.0,
Android 0.7.7 — S7 gemergt).**

> | | |
> |---|---|
> | Zweck | Beide S5-Konzepte gegen den Code prüfen und alles aufbauen, was die Umsetzung an Prüfmitteln braucht — **bevor** sie beginnt |
> | Gebaut | Datenbank und Installation mit Demo-Bestand · Android-SDK, Baulauf und Rundlauf · **Uhr-Prüfstand vollständig** (SDK, 173 Gerätedateien, 1332 Schriften, Simulator) |
> | Fehlt | nichts am Prüfstand. Offen sind Entscheidungen: **F-S5-01 bis -12** (Pakete A–D) und **F-S5Z-01 bis -05** (Paket E) |
> | S7 | gemergt, **ohne Wirkung auf S5** — `pair.php`, `ratelimit_lib.php`, `schema.sql`, `geraete_lib.php`, `watch/` und `android/` byteweise unverändert; alle 115 Anker gefunden (Abschnitt 8a) |
> | Diese Datei | wird mit dem Abschluss von Paket A in das Prüfdokument nach K9 überführt und dann gelöscht |

---

## 0. Was hier nicht steht

**Kein Anwendungscode.** Weder `pair.php`, noch `PairView.mc`, noch die
Kopplungsprobe sind angefangen. Die Umsetzung beginnt erst nach dem
S7-Merge und nach der Freigabe der elf offenen Fragen (Konzept Abschnitt 3).
Was hier steht, ist Befund und Prüfstand.

---

## 1. Wie geprüft wurde

Gelesen wurde jede im Konzept genannte Fundstelle im Repositorium, nicht
stichprobenweise: **77 Verweise** mit Datei und Zeile (bzw. Abschnitt) auf
`server/`, `watch/`, `docs/` und `tools/`. Dazu drei Zählungen
(Migrationsregister, Backlog-Nummern, Fundstellen der abzulösenden Begriffe)
und ein Lauf sämtlicher Prüfmittel, die ohne Gerätedateien laufen.

**Ergebnis der Fundstellenprüfung: die Zahlen des Konzepts stimmen.** Von 77
Verweisen trafen 75 auf die Zeile oder den Block, den sie benennen. Die zwei
Fehlgriffe stehen als V-S5-07 (folgenlos) und **V-S5-12** (ein
Sachfehler in B-S5-01) unten.

Namentlich nachgelesen und bestätigt: `pair.php` 56–64/68–74/104–162/171/
175–198/206–227/229–249/269–274/319–359 · `db.php` 448–462/476–483/570–580 ·
`ratelimit_lib.php` 43–55/96–107/115–134/147–194/196–200/209/234/245 ·
`einstellungen.php` 252–268/274–325/2942–2977/3072–3075 · `jobs_lib.php`
200–207/502–507 · `schema.sql` 420–427 · `geraete_lib.php` 91 ff. ·
`jobs.php` 95–128 · `style.css` 2783–2795 · `Pair.mc` 3–4/32–41/45–70/64/
93–108/176–179/237–264/240/289–336/317–323/330–333 · `SyncView.mc`
23–36/90–99/225–234 · `Ui.mc` 120–135 · `ClockView.mc` 115/125/216 ·
`Uploader.mc` 180–192/216 · `Input.mc` 16/18–22 · `properties.xml` ·
`settings.xml` · `admin_users.php` 169 · `Technik.md`
1754–1763/1935–1947/2290–2298 · `JSON-Vertrag.md` 42–50/203–212 ·
`Backlog.md` 66/84 · `Uhr-Layout_Regeln.md` 3.1/4.2/5.1/7 · `Design.md`
2.2/9.0–9.11.

Nachgezählt:

| Zahl | Konzept | gemessen |
|---|---|---|
| Migrationskennungen `schema.sql` = `update.php` (Z-19) | 40, „plus eine" → 41 = 41 | **40 = 40** ✓ |
| Backlog-Nummern doppelt | „zweimal kollidiert" | heute **0 Dubletten**, höchste Nummer **88** |
| `ClockView.mc` 216, längster Confirmation-Text | 38 Zeichen | **38** ✓ |
| „Uhr-App aktualisieren" gegen `ZEILE_MAX` (Z-17) | 21 < 26 | **21 < 26** ✓ |

---

## 2. Befunde am Konzept (V-S5)

Nummeriert wie die Fehlerfunde des Konzepts, aber mit eigenem Präfix, damit
beide Listen nebeneinander bestehen können. Zwölf Befunde; **vier davon ändern die
Dateilisten der Pakete** (V-S5-01 bis V-S5-04).

### V-S5-01 — `demo_lib.php` löscht aus `pair_codes`; Paket A nennt die Datei nicht

`server/demo_lib.php` 387 führt `pair_codes` in der Liste der Tabellen, die
beim Zurücksetzen des Demo-Kontos geleert werden:

```php
foreach (['missions', 'rest_segments', 'days', 'devices', 'pair_codes',
          'password_resets', …] as $t) {
    $pdo->prepare("DELETE FROM `$t` WHERE user_id = ?")->execute([$id]);
}
```

E-S5-28 lässt die Tabelle fallen. Danach wirft jeder Demo-Reset — und der
läuft **alle 30 Minuten von selbst** (`DEMO_RESET_SEKUNDEN`) — an dieser
Zeile. Die Dateiliste von Paket A nennt `demo_lib.php` nicht.

**Vorschlag:** `pair_codes` in Paket A durch `pair_sessions` ersetzen (die
neue Tabelle hat dieselbe Spalte `user_id` und denselben Fremdschlüssel, das
Löschen bleibt also sinnvoll und die Zeile bleibt eine Zeile).
`server/demo_lib.php` gehört in die Dateiliste von Paket A.

### V-S5-02 — `docs/Backup-Format.md` führt `pair_codes`; Paket D nennt die Datei nicht

`docs/Backup-Format.md` 1006 zählt `pair_codes` unter den Tabellen auf, die
**bewusst nicht** in der Sicherung stehen („kurzlebig, außerhalb ihres
Zeitfensters ohne Bedeutung"). Dieselbe Begründung trägt `pair_sessions`
wortgleich. Die Dateiliste von Paket D nennt `Backup-Format.md` nicht,
obwohl CLAUDE.md 9 sie ausdrücklich der Sicherung zuordnet.

**Vorschlag:** Zeile in Paket D umschreiben statt streichen — sonst fehlt
die Aussage „diese Tabelle fehlt mit Absicht" für die neue Tabelle.

**Keine Arbeit ist dagegen an der Komplettsicherung nötig:**
`komp_tabellen()` (`komplett_lib.php` 286) liest die Tabellen über
`SHOW FULL TABLES` aus dem Schema, `komp_reihenfolge()` sortiert nach
Fremdschlüsseln. `pair_sessions` (AI-PK, FK auf `users`) läuft von selbst
mit; die Komplettprobe prüft relative Zahlen, keine feste Tabellenzahl.

### V-S5-03 — `pair_sessions` mischt `TIMESTAMP` und `DATETIME`

Die Tabellendefinition in Paket A setzt `erstellt_am TIMESTAMP` und
`beansprucht_am DATETIME`. `docs/Technik.md` 3701 hält ausdrücklich fest,
dass sich beide **verschieden verhalten** — `TIMESTAMP` rechnet MySQL beim
Schreiben nach UTC um und beim Lesen zurück, `DATETIME` speichert, was
dasteht — und dass das „bei jeder Zeitspalte mitzudenken" ist.

Folgenlos ist das nur, solange `beansprucht_am` reine Auskunft bleibt: Der
Fristvergleich läuft über `erstellt_am`, und dort sind Schreiben und
Vergleichen (`NOW()`) in derselben Sitzungszone konsistent. Sobald jemand
später eine Nachfrist rechnet (F-S5-04, heute verneint), stünden zwei
Zeitwelten in einer Tabelle.

**Vorschlag:** beide Spalten gleich typisieren. `pair_codes` führt heute
`created_at TIMESTAMP` **und** `used_at TIMESTAMP` — die neue Tabelle sollte
demselben Muster folgen. Die Zeile 3701 in `Technik.md` nennt `pair_codes`
namentlich und ist in Paket D ohnehin anzufassen (dort steht heute nur
„Abschnitt 3 Datenmodell" in der Liste, die Zeitrechnungszeile ist eine
zweite Stelle).

### V-S5-04 — Backlog 66 (`watch/` in die Wortliste) ist einer anderen Instanz zugewiesen

E-S5-29 und E-S5-30 nehmen Backlog 66 in Paket C. `docs/Backlog.md` 706–709
sagt dagegen: **„Auf Ansage einer anderen Instanz zugewiesen (01.09.2026)"**,
und `tools/wortliste/wortliste.py` 69–74 wie `CLAUDE.md` 6 sagen dasselbe
(„`watch/` fehlt noch und ist einer anderen Instanz zugewiesen").

Das ist keine Frage der Reihenfolge, sondern eine Kollision: Beide Instanzen
würden dieselbe Bereichszeile in `wortliste.py` und dieselben Ausnahmen in
`ausnahmen.json` anlegen.

**Vor Beginn von Paket C zu klären.** Fachlich spricht für S5, dass Paket C
die Uhr-Texte ohnehin neu schreibt (Konzept 6.2) — ein Bereich `e`, der
danach angelegt wird, prüft zweimal.

### V-S5-05 — Abschnitt 11 erwartet von `uhr-bilder` einen „Diff = 0", den es nicht geben kann

Konzept Abschnitt 11 will offenlassen, ob die S3-Kacheln schon im
Repositorium liegen, und schlägt als Beleg vor: `erzeugen.sh` laufen lassen,
**„Diff = 0 erwartet"**. Diesen Diff wird es nicht geben — und zwar
planmäßig nicht.

Gemessen (rsvg-convert 2.58.0, ImageMagick 6.9.12-98): Ein Lauf ändert
**17 Dateien**. Alle sind pixelgleich (`compare -metric AE` = 0), die
Dateigrößen sind identisch, und der Unterschied sind **genau 7 Byte je
Datei** — der `tIME`-Block des PNG.

**Das ist bereits bekannt und dokumentiert**, samt derselben Zahl:
`docs/CHANGELOG.md`, Eintrag „[Werkzeug: Uhr-Prüfstand] — 2026-09-02",
Abschnitt „`git status` taugt nicht als Beleg für `uhr-bilder`", und
`tools/uhr-bilder/LIESMICH.md` 31–36. Diese Messung ist also eine
**Bestätigung**, kein neuer Fund. Der Fund ist die Erwartung im Konzept, die
dem widerspricht.

**Antwort auf die offene Frage aus Abschnitt 11:** Die neu gerasterten
Kacheln **liegen im Repositorium** —
`watch/resources-marke60|101|118/drawables/logo_boden.png` tragen Commit
`5574348` („S3/AP11: das Bodenlogo war nie so klein, wie es aussah — es war
gepolstert"), die `logo_luft.png` stammen unverändert aus `fd5907d`
(Uhr 1.10.3, Bildmarke in vier Stufen). Der Lauf ist für Paket C **nicht
nötig**; wer ihn dennoch fährt, räumt mit `git checkout -- watch/` auf.

**Offen bleibt nur eine Kleinigkeit:** Der Kopfkommentar von `erzeugen.sh`
(Zeilen 12–14) behauptet weiterhin „reproduziert sie BITGLEICH (geprüft mit
`compare -metric AE`)" — die LIESMICH daneben widerspricht ihm. Ein Wort
(„pixelgleich") oder `-define png:exclude-chunk=time` im `convert`-Aufruf
räumt das auf. **Backlog-Kandidat, kein S5-Gegenstand.**

### V-S5-06 — `android/LIESMICH.md` beschreibt den alten Kopplungsweg

`android/LIESMICH.md` 66–79 und der Kopfkommentar von
`KopplungRundlaufTest.kt` 33–35 weisen an, Kopplungscodes per SQL in
`pair_codes` einzufügen. Nach Paket A gibt es die Tabelle nicht mehr.

Der Baulauf bleibt davon grün — die Rundlauffälle überspringen sich ohne
`-Pnadoku.rundlauf` (`assumeTrue`, `KopplungRundlaufTest.kt` 83). Die
**Anleitung** wird aber falsch, und sie ist die einzige Stelle, an der der
Weg *App → `pair.php` → `devices`* beschrieben ist.

**Vorschlag:** `android/LIESMICH.md` in die Dateiliste von Paket D (CLAUDE.md
9 ordnet sie ohnehin der Android-App zu). Der Testkopf gehört dem S4-Rest.

### V-S5-07 — B-S5-08 nennt die falsche Zeile

B-S5-08 sagt „Vertrag 0, **Zeile 413**"; die Fundstellenspalte derselben
Zeile sagt `docs/JSON-Vertrag.md` **46**. Richtig ist 46 (Zeile 413 ist
Abschnitt 3.3, Reanimationsarten). Folgenlos — B-S5-08 sieht ohnehin keine
Handlung vor.

### V-S5-08 — „Formatfehler zählt nicht" (E-S5-17) ist eine Änderung, keine Fortschreibung

Heute zählt der Musterfehler mit: `pair.php` 171 ruft `abweisen(400, 'code')`
ohne dritten Parameter, und `abweisen()` hat `$zaehlen = true` als Vorgabe
(`pair.php` 56). Im neuen Entwurf liegt die Musterprüfung im Web (Aktion
`koppeln_pruefen`, Topf `pair_code`), und dort ist „zählt nicht" richtig
begründet. Die Fundstellenspalte von E-S5-17 legt nahe, es sei schon so —
beim Umsetzen nicht darauf verlassen.

### V-S5-09 — „verworfen" ergibt 401, nicht 410

Vertrag 1a.2 nennt für `410 abgelaufen` als Bedeutung „die Sitzung ist
verfallen **oder verworfen**". Nach E-S5-11 wird eine verworfene Sitzung
**gelöscht**; die Kopfzeilen sind danach unbekannt, und der Server antwortet
nach E-S5-31/1a.2 mit **401**. Für das Gerät ist das gleichbedeutend (1a.2
sagt es ausdrücklich: „Für das Gerät dasselbe wie 410"), die Rümpfe sind es
nicht.

**Vorschlag:** In 1a.2 „oder verworfen" streichen; die Gleichbehandlung steht
schon in der 401-Zeile.

### V-S5-10 — Kopplungsprobe Fall 22 braucht einen billigen Hash

Fall 22 verlangt `PAIR_SITZUNGEN_MAX` (1000) unverfallene und danach 1000
verfallene Zeilen. Würde die Probe je Zeile `password_hash()` rufen, dauerte
der Fall bei Kostenfaktor 10 mehrere Minuten. `api_key_hash` wird in diesen
Zeilen nie geprüft — ein fester Platzhalter genügt, und die 1000 Codes
kommen aus einer Schleife über `PAIR_CHARS` statt aus Zufall (die Spalte ist
`UNIQUE`).

### V-S5-11 — nach dem S7-Merge driften alle Zeilennummern

S7 ersetzt „Sicherung" durch „Backup" in einem Zug (Rahmenplan Schritt 4,
Stand 02.09.2026: 451 Treffer in `server/`). Gemessen, wie stark die
S5-Dateien betroffen sind:

| Datei | Treffer „Sicherung" | Bedeutung für S5 |
|---|---|---|
| `server/pair.php` | **0** | unberührt |
| `server/ratelimit_lib.php` | **0** | unberührt |
| `server/db.php` | 3 | Zeilendrift |
| `server/schema.sql` | 4 | Zeilendrift |
| `server/update.php` | 16 | Zeilendrift |
| `server/jobs_lib.php` | 22 | Zeilendrift |
| `server/einstellungen.php` | 46 | Zeilendrift — **im Geräteabschnitt (2900–3100) aber 0 Treffer** |
| `docs/JSON-Vertrag.md` | 2 | Zeilendrift |
| `docs/Handbuch.md` | 71 | Paket D schreibt auf dem S7-Ergebnis |
| `docs/Technik.md` | 114 | Paket D schreibt auf dem S7-Ergebnis |

**Inhaltliche Kollision ist nicht zu erwarten** — die Kopplung heißt an
keiner Stelle „Sicherung". Zu erwarten ist reine Zeilendrift. Deshalb liegt
neben dieser Datei ein Werkzeug, das die Anker des Konzepts **nach Inhalt**
statt nach Zeilennummer wiederfindet: `tools/s5-anker/anker.py`
(Abschnitt 5).

### V-S5-12 — B-S5-01: `admin_users.php` 169 legt kein Gerät an

B-S5-01 sagt: „Auch `admin_users.php` 169 zieht 8 Bytes **für ein Gerät**."
Zeile 169 zieht acht Bytes für den **`account_key` eines Kontos**
(`INSERT INTO users (email, name, role, account_key)`) — mit Gerätekennungen
hat sie nichts zu tun.

Nachgezählt: `'dev-'` entsteht im ganzen `server/` an **genau zwei** Stellen —
`einstellungen.php` 261 (4 Zufallsbytes) und `pair.php` 249 (16, seit M4-08).
Alle übrigen `INSERT INTO devices` legen das virtuelle Gerät „Manuelle
Einträge" mit dem Präfix `manual-` an.

**Die erste Hälfte von B-S5-01 stimmt damit weiterhin** und ist der
eigentliche Fund: Zwei Wege zur selben Spalte, mit 32 und 128 Bit. Die
zweite Hälfte gehört gestrichen — sonst sucht die Umsetzung eine dritte
Stelle, die es nicht gibt.

---

## 3. Der Prüfstand im Container — was steht

Alles Folgende ist **im laufenden Container aufgebaut und gemessen**. Ein
Container ist flüchtig; wie er sich in einem Zug wiederherstellt, steht in
Abschnitt 3.5.

### 3.1 Datenbank und lokale Installation

| | |
|---|---|
| MariaDB | 10.11.14, Datenbank `nadoku`, Benutzer `nadoku` |
| Anwendung | über `install.php` eingerichtet, Schema aus `schema.sql`, **40 Migrationen als `skipped` verbucht** |
| Admin | `admin@gen-em.org` / `adminlokal2026` (die Vorgabe von `kreislauf.py` und `aufnehmen.mjs`) |
| Demo | `demo@gen-em.org` / `nadokudemo0815`, aus `server/demo/fixture.json.gz` |
| Bestand (Demo-Konto) | **16 Diensttage, 87 Einsätze, 2 Geräte, 181 Spuren mit 48 981 Punkten** |
| Adressen | `http://127.0.0.1:8080` (PHP-Server) und `https://127.0.0.1:8443` (socat davor — das Sitzungs-Cookie ist `secure`) |

**Die Punktzahl ist über `spur_lib` gezählt, nicht über `track_points`** — und
das ist kein Detail. Ein `COUNT(*) FROM track_points` liefert je nach Stand des
Verdichtungsjobs etwas anderes: Im ersten Lauf standen dort 55 861 Zeilen, nach
ein paar Dutzend Seitenaufrufen 30 610 — der Job war huckepack gelaufen und
hatte 125 Spuren in Blobs verschoben. Keine davon ist verloren, aber als
Ausgangszahl taugt die Zeilenzahl nicht. `spur_zahlen()` zählt beide Ablagen
und liefert dieselbe Zahl wie die Spurprobe (181 / 48 981).

### 3.2 Ausgangszahlen der Prüfmittel

Damit die Umsetzung „unverändert" belegen kann, statt es zu behaupten:

| Prüfmittel | Ausgangszahl (02.09.2026, `main` c2ac707) |
|---|---|
| `php tools/geraeteprobe/probe.php` | **39 Erwartungen, 0 Abweichungen** |
| `php tools/ingestprobe/probe.php` | **24 Erwartungen, 0 nicht erfüllt** |
| `php tools/spurprobe/probe.php` | **45 Erwartungen, 0 nicht erfüllt** (dreimal gelaufen) |
| `python3 tools/wortliste/wortliste.py` | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen**; 77 Regeln, 77 gegriffen; Bereiche a/b/c/d |
| `python3 tools/vollstaendigkeit/pruefen.py` | **272 Befunde** — 54 Klassen ohne Gegenstück, 6 `[offen]`, 49 Regeln ohne Markup, 11 `style="…"`, 201 Unicode-Symbole, 20 Symboldateien ohne Verweis; alle vier Wertprüfungen und die Knopfhöhe **0** |
| `python3 tools/screenshots/kontrast.py` | **21 Paare gerechnet, 0 verfehlt** |
| `node tools/screenshots/aufnehmen.mjs --nur 33- --klein` | **8 Einzelbilder, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px**; Gegenprobe: 8 Bilder, 8 verschiedene Prüfsummen |
| `kreislauf.py --art edbak --frisch` | **252 882 Einzelvergleiche, 0 unerklärte, 16 erwartete Abweichungen** |
| `kreislauf.py --art csv --frisch` | **8 797 Einzelvergleiche, 0 unerklärte, 859 erwartete Abweichungen** |

### 3.3 Android

Android-SDK unter `/opt/android-sdk` (Plattform 36, Build-Tools 36.0.0,
Platform-Tools) — das Verzeichnis, das `android/LIESMICH.md` nennt, war im
Container nicht vorhanden und ist nachinstalliert.

```
ANDROID_HOME=/opt/android-sdk JAVA_HOME=/usr/lib/jvm/java-21-openjdk-amd64 \
  ./gradlew build --no-daemon
```

| | gemessen | `android/LIESMICH.md` sagt |
|---|---|---|
| Ergebnis | **BUILD SUCCESSFUL** (6 min 35 s) | — |
| Lint-Fehler | **0** | 0 |
| Lint-Warnungen | **14** | 14 |
| Prüffälle `handy` | **167**, davon 12 übersprungen | 167, davon 12 |
| Prüffälle `uhr` | **53**, davon 0 | 53, davon 0 |
| Fehlschläge | **0** | 0 |
| APK `handy` (Release, unsigniert) | 9 631 811 B | 9 598 911 B (Stand 0.7.0) |
| APK `uhr` | 19 573 618 B | 19 491 794 B (Stand 0.7.0) |

Die Zählung der Testberichte ergibt 334 und 106 — `build` fährt die Fälle für
`debug` **und** `release`, jeder Fall zählt zweimal. Die Zahlen der
Dokumentation sind die je Variante.

### 3.4 Garmin-Uhr — aufgebaut bis auf die Gerätedateien

```
tools/uhr-pruefstand/pruefstand.sh pruefen
```

| | |
|---|---|
| SDK | **Connect IQ Compiler 9.2.0** ✓ |
| Entwicklerschlüssel | vorhanden ✓ |
| Simulatorbibliotheken | **0 fehlend** ✓ (webkit2gtk 4.0 aus 22.04 neben dem Simulator) |
| Schriften | **0 Dateien** ✗ |
| fenix6pro / fr945 / venu3s | **FEHLT** ✗ |

`developer.garmin.com` ist aus dem Container erreichbar (`sdks.json` → 200),
das SDK also beschaffbar. Was fehlt, sind die Gerätedateien und die rund
1,2 GB Schriften — siehe Abschnitt 4.

### 3.5 Wiederherstellung in einem Zug

Ein neuer Container braucht drei Befehle. Der zweite ist neu und liegt
neben dem vorhandenen `lokal_starten.sh` (das nur hochfährt, nicht
einrichtet — so steht es auch in seiner Anleitung):

```bash
# 1  Systemvoraussetzungen (MariaDB, Android-SDK, rsvg/imagemagick, cffi)
sh tools/containeraufbau/aufbau.sh

# 2  Anwendung einrichten: Datenbank, install.php, Admin-Passwort, Demo-Konto
sh tools/referenzdatensatz/einspielen/lokal_einrichten.sh

# 3  Uhr-Prüfstand (braucht CIQ_GERAETE_URL)
CIQ_ZIELE=alle tools/uhr-pruefstand/pruefstand.sh aufbau
```

---

## 4. Was fehlt und woher es kommt

| Fehlt | Wofür | Woher |
|---|---|---|
| **`CIQ_GERAETE_URL`** | Gerätedateien und Schriften des Connect-IQ-SDK. Ohne sie: **kein** Übersetzen (Stufe I, 99 Geräte), **kein** Simulatorbild (Stufe II), **kein** Simulator-Rundlauf, **keine** Freigabe der `PairView` am Bild (F-S5-06) — also die halbe Abnahme von Paket C | Projektleitung; die Adresse steht bewusst nicht im Repositorium (`tools/uhr-pruefstand/LIESMICH.md`, Abschnitt „Quelle") |
| Entscheidung zu **F-S5-01 bis F-S5-11** | K6 verlangt sie vor Beginn des betroffenen Pakets; F-S5-02, -03, -04, -07, -08, -09 betreffen **Paket A**, also den ersten Schritt | Auftraggeber |
| Klärung zu **V-S5-04** (Backlog 66) | sonst legen zwei Instanzen denselben Wortliste-Bereich an | Auftraggeber |
| **S7-Merge auf `main`** | Rahmenplan 4 sperrt die S5-Umsetzung gegen S7 | läuft |

---

## 5. Nach dem S7-Merge: die Anker nachziehen

`tools/s5-anker/anker.py` sucht jede Fundstelle des Konzepts **am Inhalt**
und meldet ihre heutige Zeile. Ein Lauf nach dem Merge sagt in einem Blick,
was sich verschoben hat und was verschwunden ist:

```bash
python3 tools/s5-anker/anker.py            # Tabelle: Anker, Soll-Zeile, Ist-Zeile
python3 tools/s5-anker/anker.py --knapp    # nur Abweichungen
```

Rückgabewert 0, solange jeder Anker so oft gefunden wird wie erwartet; ≠ 0,
sobald einer fehlt oder mehrdeutig wird. Ein fehlender Anker ist die
Auskunft, dass S7 (oder ein anderes Paket) die Stelle umgeschrieben hat —
dann ist der Konzeptabsatz dazu neu zu lesen, nicht die Zeilennummer neu zu
raten.

**Stand auf diesem Zweig:** 83 Anker, **0 nicht gefunden, 0 mehrdeutig,
5 verschoben** (`docs/Technik.md` um +13 bis +15 — die zwei neuen
Werkzeugeinträge dieses Zweigs selbst; siehe Abschnitt 7). Alle Sollzeilen
sind die von `main` `c2ac707`, also der Stand, den das Konzept nennt. Nach
dem S7-Merge ist jede weitere Abweichung dessen Werk.

---

## 6. Fundstellen-Inventar für die Konsistenzlesung in Paket D

Paket D verlangt „Zahl der Fundstellen vorher/nachher". Hier die
Vorher-Zahlen, erhoben mit `git grep` (ohne `.git/`, ohne Baustände):

### 6.1 `nadoku.beispieldomain.de` — 22 Fundstellen in 15 Dateien

| Ort | Zahl | Los |
|---|---|---|
| `watch/resources/settings/properties.xml` | 1 | **S5 Paket C** |
| `watch/resources/settings/settings.xml` | 1 | **S5 Paket C** |
| `watch/source/Uploader.mc` | 1 | **S5 Paket C** |
| `docs/Handbuch.md` | 1 | **S5 Paket D** |
| `tools/wortliste/sperrliste.json` (Feld `ersatz`) | 1 | prüfen, bleibt vermutlich |
| `android/…` (6 Dateien: `Serveradresse.kt`, `QrInhalt.kt`, `DienstAnsicht.kt`, `strings.xml`, drei Testdateien) | 32 | **S4-Rest** (Backlog 84) |
| `docs/CHANGELOG.md`, `docs/Rahmenplan-Archiv.md`, `docs/konzepte/erledigt/*` | 18 | **bleiben** (Historie) |
| `docs/mockups/S4-app.html` | 1 | S4-Rest |

*(Die Gesamtzahl 68 aus einem naiven `grep -rn` enthält 12 Treffer in
`android/*/build/` — Baustände, nicht im Repositorium.)*

### 6.2 `pair_codes` — 13 Dateien

**Code:** `server/pair.php`, `server/einstellungen.php`, `server/jobs_lib.php`,
`server/schema.sql`, `server/update.php`, **`server/demo_lib.php`** (V-S5-01).
**Doku:** `docs/Technik.md` (2 Stellen: 422 und 3701, V-S5-03),
**`docs/Backup-Format.md`** (V-S5-02), `docs/Rahmenplan.md`.
**Bleibt:** `docs/Rahmenplan-Archiv.md`, `docs/konzepte/Konzept-S4-…`.
**Android:** `android/LIESMICH.md`, `KopplungRundlaufTest.kt` (V-S5-06).

### 6.3 „Kopplungscode" — 40 Dateien, davon in `server/`

`einstellungen.php` (5), `db.php` (4), `version.php` (2), `jobs_lib.php` (2),
`assets/style.css` (2), `schema.sql` (1), `pair.php` (1), `demo_lib.php` (1),
`admin_demo.php` (1), `reset_request.php` (1), `pw_handling.php` (1).

Die drei letzten meinen **nicht** die Gerätekopplung, sondern den
Passwort-Token — beim Lesen zu trennen.

### 6.4 „Code eintippen" — die Stellen, die der Umkehr widersprechen

`server/einstellungen.php` 2945 · `docs/Handbuch.md` 2682, 2718 ·
`docs/JSON-Vertrag.md` 209 · `watch/source/Pair.mc` 3.
Alles Übrige steht in `CHANGELOG.md`, `version.php` und erledigten
Konzepten — Historie, bleibt.

---

## 7. Kleine Funde am Prüfstand selbst (kein S5-Gegenstand)

| | Fund | Bemerkung |
|---|---|---|
| P-01 | `/opt/android-sdk` fehlte im Container, obwohl `CLAUDE.md` 6 und `android/LIESMICH.md` es voraussetzen | mit `tools/containeraufbau/aufbau.sh` behoben |
| P-02 | Das Python-Paket `cryptography` war unbrauchbar (`ModuleNotFoundError: _cffi_backend`) — `kreislauf.py` und `einspielen.py` brachen beim Import ab | `pip install cffi`; im Aufbauskript |
| P-03 | `tools/referenzdatensatz/einspielen/` beschreibt das Einrichten als Browserschritt und lässt es aus | `lokal_einrichten.sh` schließt die Lücke; der Browserschritt (Passwort, Schlüsselmaterial) bleibt der Browserschritt — er ruft `passwort_setzen.mjs` |
| P-05 | Dieser Zweig hat selbst `docs/Technik.md` verlängert (zwei Werkzeugeinträge in der Verzeichnisstruktur) und damit fünf Anker um +13 bis +15 verschoben | im Ankerlauf sichtbar, RC bleibt 0 — genau der Fall, für den das Werkzeug gebaut ist |
| P-04 | Der Kopfkommentar von `tools/uhr-bilder/erzeugen.sh` sagt „bitgleich", die LIESMICH daneben sagt „pixelgleich, `git status` taugt nicht als Beleg" | V-S5-05; Backlog-Kandidat |

---

## 8. Paket E — Zusatzkonzept „Android: Ortung und Dienstende"

Der Zusatz `Konzept-S5-Zusatz-Android-Ortung-Dienstende.md` liegt seit dem
03.09.2026 daneben und ist auf demselben Weg geprüft: **70 Fundstellen**,
davon rund 50 in `android/`.

**Die Zahlen des Zusatzes stimmen ebenso.** Stichprobe an den zwölf
Kernstellen, gemessen statt gelesen — `AufzeichnungsDienst.kt` 63 (Handler),
65 (Zuhörer als SAM-Lambda), 123/141 (Sendefaden und Nachposten), 154/159/171
(Ortung anfordern, das nicht eingelöste Versprechen, `requestLocationUpdates`),
194 (`beenden()`), 207/222/262/266 (Kanal, Meldung, ID, Takt) ·
`Ausduenner.kt` 77/131/137 · `Sendetakt.kt` 44/53/65 · `HandyHorcher.kt` 25/41 ·
`Uhrnachricht.kt` 35/80/85/94/174. Alle **32 neuen Anker** stehen im
Ankerwerkzeug (`--paket E`) und lösen eindeutig auf.

### 8.1 Antwort auf F-S5Z-06 — der Wortlaut steht in der App, nicht im Web

Das ist die Frage, an der die Diagnose aus 1.3 hängt, und sie lässt sich aus
dem Repositorium beantworten:

| Wo | Zeichenkette | Wortlaut |
|---|---|---|
| App, Dauermeldung | `strings.xml:103` `dienst_meldung_laeuft` | **„Aufzeichnung läuft seit %1$s"** |
| App, Zustandszeile | `strings.xml:112` `dienst_laeuft_seit` | **„Aufzeichnung läuft seit %1$s · GPS an"** |
| App, Zustandszeile ohne Freigabe | `strings.xml:113` `dienst_laeuft_seit_ohne_gps` | „Aufzeichnung läuft seit %1$s · GPS fehlt" |
| Web | — | **kein solcher Wortlaut** |

Gegengeprüft: `git grep -i` über `server/` nach „laufend", „läuft",
„Aufzeichnung" findet **keine** Stelle, an der die Weboberfläche behauptete,
eine Aufzeichnung laufe. Was das Web bei fehlendem Abschluss zeigt, ist
genau das, was der Zusatz beschreibt: `–offen` auf der Spurenseite
(`tag_spuren.php:183`) und ein `days.ended_at`, das NULL bleibt.

**Damit stützt der Befund H1 (Kette B3):** Der Dienst wurde an der Uhr
beendet, der Vordergrunddienst lief weiter, und die App sagte weiter
„Aufzeichnung läuft seit …". Die Diagnose am Gerät (1.3) bleibt trotzdem zu
fahren — sie entscheidet, was das Prüfdokument als **belegten** Fehler führt.
Eine Rückfrage bleibt: Der Auftraggeber sagte „im Diensttag selbst" — das
klingt nach der Weboberfläche. Zu klären, ob er die App-Ansicht oder eine
Webseite meinte; nur die App kennt den Wortlaut.

Nebenbei eine Genauigkeit zum Befund 1.1 des Zusatzes: Es gibt **zwei**
Zustandszeilen, nicht eine — `dienst_laeuft_seit` und
`dienst_laeuft_seit_ohne_gps`. Sie schalten an der **Freigabe** um, nicht am
Ortungszustand; die Aussage des Zusatzes stimmt also, die Zahl der Texte ist
zwei. E1 ersetzt beide.

### 8.2 Der Prüfstand für Paket E — Ausgangszahlen

Alles gemessen, nicht übernommen:

| Prüfmittel | Ausgangszahl (03.09.2026, `main` c2ac707) |
|---|---|
| `./gradlew build` | **BUILD SUCCESSFUL**, **0 Lint-Fehler, 14 Warnungen** |
| Prüffälle `handy` / `uhr` | **167** (12 übersprungen) / **53** (0) = **220**, **0 Fehlschläge** |
| **`handy` mit laufender Installation** (`-Pnadoku.rundlauf=http://127.0.0.1:8080/`) | **167 von 167, 0 übersprungen, 0 Fehlschläge** — der Rundlauf gegen das echte `ingest.php` läuft |
| `werkzeuge/kontraste.py` | **16 Paare, 0 unter dem Zielwert** |
| `werkzeuge/farbabgleich.py` | **0 Abweichungen, 0 eigene Farbwerte**; 18 Web-Token, 17 App-Token, 1 nicht übernommen (`--spur-8`) |
| `werkzeuge/stroeme.py` | **5 Ströme, 0 Abweichungen** analytisch gegen Referenzregel |
| `werkzeuge/bildmarken.sh pruefen` | **0 Abweichungen** (4 Bildmarken) |
| `tools/wortliste/` Bereich d | **2 Dateien, 3 Treffer, alle durch Ausnahmen erklärt, 0 außerhalb** |

Der Rundlauf ist der wichtigste dieser Punkte: **`SendeRundlaufTest` ist die
Abnahme von Paket E2** („Segment am Server `final = 1`, `days.ended_at`
gesetzt"), und dass er hier gegen eine echte Installation grün läuft, ist die
Voraussetzung dafür.

**Was der Rundlauf hinterlässt, ist mehr, als die Anleitung sagt.**
`android/LIESMICH.md` verspricht: „Die Fälle räumen hinter sich auf: Was sie
koppeln, trennen sie wieder." Das stimmt — die Geräte sind hinterher weg, es
stehen wieder nur die zwei des Demo-Kontos. Der **hochgeladene Bestand**
bleibt aber stehen: Nach einem Lauf trug das Admin-Konto **9 Diensttage,
5 Einsätze und 14 439 Spurpunkte**, die vorher nicht da waren. Das ist kein
Fehler der Prüffälle (der Sinn des Rundlaufs ist gerade, dass die Daten
wirklich ankommen), aber es heißt: **Jeder weitere Lauf legt noch einmal so
viel dazu.** Wer die Installation als Ausgangsstand braucht, räumt vorher das
Admin-Konto ab oder baut sie mit `lokal_einrichten.sh` neu. Für das
Prüfdokument von E2 gehört die Zahl vor und nach dem Lauf notiert — sonst
misst der zweite Lauf den Bestand des ersten. **Backlog-Kandidat:** die
Rundlauffälle räumen auch ihren Bestand ab (`day`-Kennungen sind bekannt).

### 8.3 Kein Emulator — gemessen, nicht vermutet

Abschnitt 9.3 des Zusatzes zählt auf, was der Emulator kann und die Umsetzung
nutzen soll: `adb emu geo fix`, das Abschalten des Standorts,
`adb shell cmd jobscheduler run -f`, `adb shell dumpsys notification`.
**In diesem Container geht davon nichts.** Beide Wege sind zu, und beide sind
gemessen:

| Weg | Ergebnis |
|---|---|
| **arm64-Abbild** (`system-images;android-34;google_apis;arm64-v8a`, heruntergeladen und AVD angelegt) | `FATAL | Avd's CPU Architecture 'arm64' is not supported by the QEMU2 emulator on x86_64 host.` — der heutige Emulator (37.1.11) übersetzt keine fremde Architektur mehr |
| **x86_64-Abbild** | braucht KVM. `/dev/kvm` fehlt, und `/proc/cpuinfo` nennt weder `vmx` noch `svm` — die CPU ist selbst virtualisiert (`hypervisor`), Verschachtelung ist nicht freigegeben |

**Folge für das Prüfdokument:** Die vier Emulator-Griffe aus 9.3 gehören an
den Anfang der Liste „nicht prüfbar" — **zusammen** mit dem, wofür sie als
Ersatz gedacht waren. Was bleibt, ist der Gerätetest nach 9.2 und die
JVM-Prüffälle. Das trifft besonders:

- **`STANDORT_AUS` und der Rückruf** (`onProviderDisabled`) — nur am Gerät;
  der JVM-Prüffall der Zustandsmaschine belegt die *Entscheidung*, nicht das
  Ankommen des Rückrufs.
- **Der Nachsende-Job** — `jobscheduler run -f` fällt aus; Punkt 9 und 10 der
  Prüfliste sind die einzigen Belege.
- **Die drei Meldungs-IDs** — `dumpsys notification` fällt aus; am Gerät ist
  es das Auge.

Das ist kein Rückschlag für den Entwurf, sondern eine Größe für die Planung:
**Paket E ist zu einem größeren Teil gerätegebunden, als der Zusatz annimmt.**
Die JVM-Prüffälle (E1 ≥ 16, E2 ≥ 13, E3 ≥ 6) werden dadurch wichtiger, nicht
unwichtiger — sie sind das Einzige, was im Container überhaupt läuft.

---

## 8a. Paket C — der Uhr-Prüfstand steht, und F-S5-11 ist beantwortet

Die Adresse der Gerätedateien liegt seit dem 03.09.2026 in den
Umgebungsvariablen; damit ließ sich der Prüfstand hier vollständig aufbauen.

| | |
|---|---|
| SDK | Connect IQ 9.2.0 |
| Gerätedateien | **173 mit `compiler.json`** (343 MB) |
| Schriften | **1332 Dateien** (1,2 GB) |
| Simulatorbibliotheken | 0 fehlend |
| `geraeteklassen.py` | **99** Geräte für Stufe I, **20** Vertreter für Stufe II |
| **Stufe I auf dem heutigen Stand** | **99 übersetzt, 0 fehlgeschlagen, 0 ohne Gerätedatei, 0 Warnungen, 0 Fehler** |

Die **20 Vertreter** beantworten den offenen Punkt aus Abschnitt 11 des
Konzepts („Wie viele Bilder `bildreihe` liefert"). Und die **99** sind
geklärt: Sie sind nicht die Zahl der Gerätedateien — das sind 173 —, sondern
die Auswahl, die `geraeteklassen.py` daraus zieht. `tools/uhr-pruefstand/LIESMICH.md`
hatte beides verwechselt und ist berichtigt.

### F-S5-11 — die Antwort hat drei Zeilen, nicht zwei

Das Konzept fragt, ob der Simulator `makeWebRequest` gegen
`http://127.0.0.1:8080` zulässt, und sagt „erst messen". Gemessen mit
`tools/netzprobe/` (neu), SDK 9.2.0, fenix6pro:

| Weg | Was die App sieht | Was beim Server ankommt |
|---|---|---|
| `http://127.0.0.1:8080` | **−1001** `SECURE_CONNECTION_REQUIRED` | **die Anfrage** — `[405]: GET /pair.php` im Zugriffsprotokoll |
| `https://…`, **selbstsigniert** (der bisherige Weg von `lokal_starten.sh`) | 404 | nichts — `tlsv1 alert unknown ca` |
| `https://…`, Zertifikat aus **eigener CA im Systemspeicher** | **405 von `pair.php`** | die Anfrage |

**Beide vom Konzept erwogenen Wege sind zu**, der dritte trägt. Damit ist der
Simulator-Rundlauf für Paket C möglich — die 18 Mitschnitte (6 Fälle × 3
Geräte) brauchen keinen Ersatz durch eine Attrappe.

Die erste Zeile ist die, die man kennen muss: Über blankes HTTP geht die
Anfrage **hinaus und wird ausgeführt**, nur die Antwort erreicht die App
nicht. Wer beim Aufsetzen des Rundlaufs nur auf den Rücklaufcode sieht, hält
den Weg für tot und übersieht, dass die Gegenseite bereits gehandelt hat — bei
einem `POST` auf `pair.php` wäre das eine angelegte Sitzung, von der die Uhr
nichts weiß.

`lokal_starten.sh` erzeugt deshalb jetzt eine eigene CA, unterschreibt damit
das Serverzertifikat (`subjectAltName=IP:127.0.0.1`) und legt die CA nach
`/usr/local/share/ca-certificates/`. Für alles andere ändert sich nichts —
die Skripte prüfen weiter mit `-k`, und `curl` **ohne** `-k` liefert jetzt
zusätzlich 200 statt eines Zertifikatsfehlers.

### Zwei Fehler im Prüfstand, beide beim Benutzen gefunden

| | Fund | Behandlung |
|---|---|---|
| **P-06** | `pruefstand.sh bauen fenix6pro -l 3` — die in der LIESMICH dokumentierte Form — übergab `-l` als **Jungle-Pfad**. `monkeyc` brach mit „Missing argument for option: f" ab und druckte seine Hilfe. Ausgerechnet `-l 3` ist der Schalter, den die Abnahme von Paket C verlangt | behoben: ein zweites Argument, das mit `-` beginnt, ist ein Schalter und kein Pfad |
| **P-07** | `bauen` setzte `java.awt.headless` nicht — `reihe` tut es, und `umgebung()` leert `JAVA_TOOL_OPTIONS` sogar ausdrücklich. Geräte, deren Launcher-Symbol skaliert werden muss, brachen in einem `AWTError` ab, **ohne** ERROR-Zeile. `fenix6pro` und `fr945` bauten durch, `venu3s` mit der Eingabe-Probe nicht — der Ausfall sah nach einem Geräteproblem aus | behoben, mit Verweis auf die Begründung, die in `reihe` schon stand |

Beide hätten die Uhr-Instanz in ihrer ersten halben Stunde getroffen.

---

## 9. Änderungsverlauf dieser Datei

| Fassung | Datum | Inhalt |
|---|---|---|
| 1 | 02.09.2026 | Erstfassung: Prüfung des Konzepts (77 Fundstellen), 12 Befunde V-S5-01 bis V-S5-12, Prüfstand aufgebaut und mit Ausgangszahlen belegt, Fundstellen-Inventar, offene Zuarbeiten |
| 2 | 03.09.2026 | Abschnitt 8: das Zusatzkonzept (Paket E) geprüft — 32 neue Anker, Antwort auf F-S5Z-06, Ausgangszahlen der Android-Prüfmittel, Rundlauf 167/167 belegt, Emulator als unmöglich gemessen |
| 3 | 03.09.2026 | Abschnitt 8a: Uhr-Prüfstand vollständig (173 Gerätedateien, 1332 Schriften), Stufe I 99/0/0/0 als Ausgangsstand, **F-S5-11 beantwortet** (nur TLS mit bekannter CA), zwei Fehler im Prüfstand behoben; S7-Merge ohne Wirkung auf S5 |
