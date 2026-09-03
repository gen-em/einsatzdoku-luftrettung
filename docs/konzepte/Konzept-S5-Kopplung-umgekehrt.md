# Konzept S5 — Kopplung umgekehrt

**Rahmenplan Schritt 3 (Konzept) und Schritt 5 (Umsetzung) · Beschlüsse R49
(E-R49-1 bis E-R49-8), R63 · Ablage `docs/konzepte/` nach K1, Lebenszyklus
R62.**

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 03.09.2026 — **Umsetzung läuft** (Schritt 5); Freigabe der Paket-A-Fragen am 03.09.2026 |
> | Paket in Arbeit | **B (Web)** als nächstes auf `claude/s7-umsetzung-vorbereiten-s8kax0`. C (Uhr) und E (Android-Zusatz) laufen in eigenen Instanzen auf eigenen Zweigen; D folgt hier |
> | Erledigt | **A — Server** (03.09.2026, Web **13.0.0**): `pair.php` mit vier Anliegen, `kopplung_lib.php`, Tabelle `pair_sessions`, Migration `2026_09_03_kopplungssitzungen`, drei Töpfe, Obergrenze, `email_maskieren()`, Geräte- und Sitzungsschlüssel als SHA-256, Kopplungsprobe **75/75**, Ingestprobe **24/24**, Geräteprobe **39/39**, Migration auf Bestand (41 = 41) und frisch (übersprungen) gefahren — Abschnitt 9 |
> | Wo es hakt | Entschieden sind F-S5-01 bis -05, -07 bis -09, -11 und die nachgetragene F-S5-12 (E-S5-32 bis -53). **Offen:** F-S5-06 und F-S5-10 (C — von der Uhr-Instanz vorgelegt und beantwortet, siehe Nummernvergabe unten) — Empfehlungen stehen, die Instanzen legen sie vor dem Paket vor. **Preisänderung durch E-S5-42:** die eine Bestandsuhr bleibt nach dem Deploy **nicht** gekoppelt (Abschnitt 8.1, nachgetragen). **Zwischen A und B ist der Knopf „Kopplungscode erzeugen“ im Web tot** (die Tabelle dahinter ist weg) — dieser Zweig geht nicht vor B auf `main` |
> | Fable-Schritt | **keiner** (R49: bekanntes Muster, keine neue Kryptographie; der R17-Review in P6 prüft mit) |
> | Erhoben an | `main`, Commit `c2ac707` (02.09.2026, Merge PR #26): Web 12.9.2, Uhr 2.0.0, Android 0.7.7 |
> | Erhoben aus | dem Repositorium allein — keine Uhr, kein Simulatorlauf, kein Server. Was sich so nicht ermitteln ließ, steht in Abschnitt 11 |

Dieses Dokument wird während der Umsetzung nach **jedem** Arbeitspaket
fortgeschrieben (Statusblock, Abschnitt 9 Umsetzungsstand, Abschnitt 10
Fehlerfunde); das Prüfdokument nach K9 entsteht daneben mit Paket A und wird
je Paket ergänzt. Nach der Freigabe des Abschlusses: Erledigt-Zeile in
`docs/Rahmenplan.md` Abschnitt 8, Reste nach Abschnitt 6, Backlog nach
Abschnitt 5, eine Zeile nach Abschnitt 10 — dann wird dieses Konzept gelöscht
(R62).

**Was dieses Konzept nicht festlegt (K2, K3, Anweisung vom 02.09.2026):**
keine Versionsnummern, keine Modellempfehlung je Paket, **keine
Backlog-Nummern** — Kandidaten heißen hier „Backlog-Kandidat“, die Nummer
vergibt die Umsetzungsinstanz beim Merge (heute laufen mehrere Zweige,
Nummern sind zweimal kollidiert).

**Nummernblöcke der E-S5-Einträge (03.09.2026, weil drei Instanzen gleichzeitig
eintragen).** Die Warnung oben hat sich sofort bewahrheitet: Die Uhr-Instanz
wollte E-S5-48 und -49 vergeben, die Paket A schon vergeben hatte. Deshalb
feste Blöcke statt Absprache im Einzelfall:

| Block | Wer | Stand |
|---|---|---|
| E-S5-32 bis -47 | Freigabe der offenen Fragen (Auftraggeber) | vergeben |
| E-S5-48 bis -59 | **Server-Instanz** — Pakete A, B, D | 48–53 vergeben |
| E-S5-60 bis -79 | **Uhr-Instanz** — Paket C | frei |
| E-S5-80 ff. | **Android-Instanz**, falls sie am Hauptkonzept etwas einträgt (ihre eigenen Einträge zählen als E-S5Z-, eigener Namensraum, keine Kollision) | frei |

Wer einen Block verlässt, sagt es hier, bevor er die Nummer benutzt.

---

## 0. Auftrag in einem Absatz

Das Gerät zeigt einen Code, das Web nimmt ihn entgegen, das Gerät bestätigt
das Konto. Ein Serverprotokoll für die Garmin-Uhr (hier, Paket C) und die
Android-App (S4-Rest, Schritt 6; hier nur beschrieben, Abschnitt 6). Die acht
Grundsatzbeschlüsse sind gefallen (R49); dieses Konzept legt fest, was dort
offen blieb — Vertragsabschnitt 1a im Wortlaut, Datenmodell und Migration,
die Zahlen mit Fundstelle, die Wortlaute, den Paketschnitt mit
Abnahmekriterien, das Prüfprotokoll-Soll, Preis und Bedrohungsmodell — und
sammelt Fehlerfunde am Bestand (K4).

---

## 1. Befund

### 1.1 Der heutige Weg, am Code gelesen

| Stelle | Was heute geschieht | Fundstelle |
|---|---|---|
| Web erzeugt den Code | `einstellungen.php`, Aktion `pair_code`: entwertet offene Codes des Kontos, zieht sechs Zeichen aus `PAIR_CHARS`, fünf Versuche gegen Dubletten, Grenze `MAX_GERAETE` **vor** dem Erzeugen | `server/einstellungen.php` 274–325 |
| Anzeige | Baustein `.codeblock` (Festbreite, `--schrift-fest`), Karte „Gerät koppeln“, Knopf „Kopplungscode erzeugen“ als **die** Haupthandlung des Reiters | `einstellungen.php` 2942–2977, `style.css` 2786, `Design.md` 2.2 |
| Uhr tippt ein | `Pair.start()` → bei bestehender Kopplung Rückfrage, Trennen (Nr. 14), dann `WatchUi.TextPicker` → `Pair.request(code)` sendet `{code, geraet}` ohne Kopfzeilen | `watch/source/Pair.mc` 93–108, 176–179, 237–264 |
| Server löst ein | „Entwerten zuerst, dann prüfen“ (`UPDATE … used_at` mit `rowCount() === 1`), dann `MAX_GERAETE`, dann `devices`-Anlage mit `dev-` + 16 Zufallsbytes und Schlüssel aus 24 Bytes; Antwort `{device_id, api_key}`; Mail nach `antwort_abschliessen()` mit 5 s Zeitlimit | `server/pair.php` 164–359 |
| Ratenschutz | ein Topf `pair` (10 Fehlversuche je 600 s, Sperre 600 s), **je IP**, gilt für Koppeln und Trennen — und, was der Rahmenplan nicht nennt, auch für das Token von `jobs.php` | `server/ratelimit_lib.php` 55; `docs/Technik.md` 2294 |
| Konstante Antwortdauer | jeder Fehlerzweig über `abweisen()` → `rate_gleiche_dauer($t0)`, Mindestdauer 0,35 s; unbekannte Gerätekennung läuft gegen `AUTH_VERGLEICHSWERT` | `pair.php` 56–64, 112–118; `ratelimit_lib.php` 234; `db.php` 483 |
| Aufräumen | Job `aufraeumen`, **täglich**, Schritt „Kopplungscodes“: verbrauchte oder ältere als `PAIR_TTL_MIN` | `server/jobs_lib.php` 200–207, 502–507 |
| Gerätekennung | `geraete_lib.php` liest beide Formen, wirft nie, gibt immer drei Werte; `pair.php` löst **nur im Moment der Kopplung** auf | `server/geraete_lib.php` 91 ff.; Rahmenplan E-S6-6 |
| Tabelle | `pair_codes (id, user_id, code VARCHAR(8) UNIQUE, created_at, used_at)`, FK auf `users` mit `ON DELETE CASCADE` | `server/schema.sql` 420–427 |
| Uhr-Einstellung | `serverUrl` **bewusst leer** („jede Installation hat ihren eigenen Server“); `deviceId`/`apiKey` als Alt-Weg in den Properties, die Kopplung schreibt nach `Storage "cred"` | `watch/resources/settings/properties.xml`; `Uploader.mc` 180–192 |
| Sync-Seite | 2-s-Timer (`refresh`), Mittelblock mit drei Zuständen (Einrichten / grün / Rückstand), unterer Block als Zeilenliste mit `Pair.status` und `Pair.statusHint`; START halten → `Pair.start()` | `watch/source/SyncView.mc` 23–36, 110–142, 231–234 |
| Fehlertexte der Uhr | zwei Zeilen — woran es liegt, was hilft —, Entscheidung am Feld `error`, nicht am Zahlencode; Hinweiszeile höchstens 26 Zeichen (`ZEILE_MAX`) | `Pair.mc` 45–70, 289–336 (Muster Uhr 1.7.0) |

### 1.2 Was R49 daran umdreht — und was bleibt

**Umgedreht:** Wer den Code erzeugt (Gerät statt Web), wer ihn eintippt (ein
Mensch im Browser statt auf der Uhr), womit sich das Gerät ausweist
(Kopfzeilen statt Code, E-R49-3), wo die Zugangsdaten bis zur Bestätigung
liegen (Sitzungstabelle statt `devices`, E-R49-2), wer das letzte Wort hat
(das Gerät, E-R49-4), wo der Ratenschutz zählt (Code-Eingabe im Web je Konto
und IP, `start` je IP, Obergrenze offener Sitzungen, E-R49-6).

**Bleibt, weil es am Code begründet ist:** Codeformat und Alphabet, die Frist
von zehn Minuten, die Gerätekennung `dev-` + 16 Bytes, der Schlüssel aus 24
Bytes, der Hash-nur-Speicher des Schlüssels, „die Datenbank ist der
Schiedsrichter“ (Änderung mit `rowCount()` statt Suchen-dann-Ändern), die
konstante Antwortdauer, die Mail nach Abschluss der Antwort, das Trennen
(Abschnitt 1b, R47), die manuelle Anlage als Rückfall (E-R49-7), das
zweizeilige Fehlermuster der Uhr.

### 1.3 Drei Befunde, die den Entwurf tragen

1. **Der Aufräumjob läuft täglich, die Frist ist zehn Minuten.** Eine
   Sitzungsobergrenze, die verfallene Zeilen mitzählt, ließe sich an einem
   Tag mit toten Sitzungen füllen. Die Obergrenze zählt deshalb **per SQL
   nur unverfallene** Sitzungen (E-S5-14); das Aufräumen bleibt Hygiene, kein
   Schutz.
2. **`RATE_GRENZEN` hängt am Topf, nicht am Merkmal** (`ratelimit_lib.php`
   51–74, Begründung am Muster `demo`/`demog`). Drei Zählungen mit drei
   verschiedenen Grenzen brauchen **drei Töpfe**; der heutige Topf `pair`
   bleibt für die kopfzeilen-ausgewiesenen Anliegen (`status`,
   `bestaetigen`, `trennen`) und für `jobs.php` (E-S5-16).
3. **Es gibt keinen Abschnitt „Bedrohungsmodell“ in `docs/Technik.md`.**
   E-R49-5 verlangt, „das Bedrohungsmodell (S2/AP10)“ fortzuschreiben. Der
   S2-Abschluss nennt eine „Fortschreibung des Bedrohungsmodells“, die aber
   in 4.99 („Sicherheit“, „Die Antwortzeit als Auskunft“) und 4.97c/d
   verteilt gelandet ist. Der Nachtrag bekommt deshalb einen **eigenen
   Unterabschnitt** (Abschnitt 8, B-S5-02).

### 1.4 Der Bestand, den der Umstieg trifft

- **Eine gekoppelte Uhr** (E-R49-7). Sie bleibt gekoppelt: `ingest.php`
  ändert sich nicht, die Zugangsdaten liegen in `Storage "cred"`. Erst eine
  **Neukopplung** braucht die neue Uhr-Fassung.
- **Uhr 2.0.0 auf einer frischen Uhr** schickt `{code, geraet}` ohne
  `aktion`. Der neue Server antwortet `400 {"error":"aktion","meldung":
  "Uhr-App aktualisieren"}`; Uhr 2.0.0 zeigt „Kopplung fehlgeschlagen (400)“
  und darunter die Servermeldung (`Pair.mc` 330–333) — der einzige Kanal, auf
  dem eine alte Uhr erfährt, was zu tun ist (E-S5-19).
- **Die Android-App 0.7.7** koppelt nach dem alten Modell (Konzept S4,
  Abschnitt 13). Sie ist nicht verteilt; der S4-Rest baut das Modul neu.

---

## 2. Entscheidungen

Die acht Beschlüsse aus R49 gehen hier als E-S5-01 bis E-S5-08 ein
(Kurzform; Volltext `docs/Rahmenplan-Archiv.md`, Eintrag R49), R63 als
E-S5-09. Ab E-S5-10 folgt, was dieses Konzept festlegt. Jede Zahl steht in
Abschnitt 5 mit Fundstelle.

| Nr. | Entscheidung | Herkunft |
|---|---|---|
| **E-S5-01** | **Ablauf:** `start` (Gerät, ohne Kopfzeilen, mit `geraet`-Block) → Server legt Sitzung an, antwortet mit Code **und** Zugangsdaten; das Gerät zeigt den Code; im Web ersetzt ein Feld „Code vom Gerät“ den Knopf „Kopplungscode erzeugen“; eine Bestätigungsseite zeigt Art und Modell und bindet die Sitzung ans Konto; `status` → „beansprucht“ trägt das Kontolabel; `bestaetigen` → Ja legt das Gerät an | E-R49-1 |
| **E-S5-02** | **Schwebende Zugangsdaten statt schwebender Geräte:** Sitzungstabelle hält Code, `device_id`, Schlüssel-**Hash**, `geraet`-Werte, Frist, Zustand, beanspruchendes Konto; `devices`-Zeile erst bei `bestaetigen` | E-R49-2 |
| **E-S5-03** | **Der Code ist nur für den Menschen.** `status`/`bestaetigen` weisen sich mit `X-Device-Id`/`X-Api-Key` aus | E-R49-3 |
| **E-S5-04** | **Rückbestätigung am Gerät; Label = maskierte E-Mail**, nur im Dialog, nie gespeichert; Nein oder Fristablauf verwirft Sitzung und Zugangsdaten | E-R49-4 |
| **E-S5-05** | **Zwei Angriffsflächen, zwei Tore** — (a) fremdes Gerät im eigenen Konto: Bestätigungsseite; (b) eigenes Gerät im fremden Konto: Rückbestätigung. Bedrohungsmodell fortschreiben | E-R49-5 |
| **E-S5-06** | **Ratenschutz gedreht:** Code-Eingabe im Web je Konto **und** je IP; `start` je IP; Obergrenze offener Sitzungen insgesamt; Frist 10 min; Aufräumen über den Job-Einstieg | E-R49-6 |
| **E-S5-07** | **Der alte Weg entfällt**; manuelle Anlage bleibt; Vertragsabschnitt 1a neu, 1b bleibt; Preis: keine ältere Uhr-Fassung koppelt mehr | E-R49-7 |
| **E-S5-08** | **Vorgabewert `serverUrl` = `nadoku.gen-em.org`** in der Uhr-App, Einstellung bleibt überschreibbar (Garmin Connect) | E-R49-8, R63 |
| **E-S5-09** | **Android: feste Adresse, keine Adresswahl, kein Adress-QR.** E-R49-8 und E-R45-2 gelten für Android insoweit nicht mehr; Selbsthoster bauen ein eigenes APK | R63, Backlog 84 |
| **E-S5-10** | **Vier Anliegen an einem Endpunkt, `aktion` ist Pflicht.** `pair.php` kennt `start`, `status`, `bestaetigen`, `trennen`; ein Rumpf ohne oder mit unbekannter `aktion` bekommt `400 {"error":"aktion"}`. Ein zweiter Endpunkt wäre eine weitere anmeldungsfreie Tür, die dieselbe Bremse noch einmal bräuchte (`pair.php` 88–91) | dieses Konzept |
| **E-S5-11** | **Zustände einer Sitzung:** `offen` (Code steht, kein Konto) → `beansprucht` (Konto gesetzt, `beansprucht_am`) → Ende durch `bestaetigt` (Gerät angelegt, Zeile gelöscht), `verworfen` (Nein am Gerät oder Abbruch, Zeile gelöscht) oder `abgelaufen` (Frist vorbei; die Zeile bleibt bis zum Aufräumen, zählt aber nirgends mehr). Es gibt **keine** Endzustände in der Tabelle — beendete Sitzungen werden gelöscht wie heute die Codes | dieses Konzept |
| **E-S5-12** | **Eine Frist, ab `start`, für alles.** Beanspruchung und Bestätigung müssen innerhalb derselben zehn Minuten liegen; die Beanspruchung verlängert nichts. Eine Nachfrist (F-S5-04) hätte eine zweite Uhr und einen zweiten Ablaufweg gebraucht; der Ablauf dauert im Regelfall unter zwei Minuten, und Uhr wie Bestätigungsseite zeigen die Restzeit | dieses Konzept |
| **E-S5-13** | **Die Datenbank ist der Schiedsrichter — an drei Stellen.** Beanspruchen: `UPDATE pair_sessions SET user_id=?, beansprucht_am=NOW() WHERE code=? AND user_id IS NULL AND erstellt_am > NOW()-frist`, gültig nur bei `rowCount() === 1`. Bestätigen: Transaktion mit `SELECT … FOR UPDATE`, `INSERT devices`, `DELETE` der Sitzung, `COMMIT`. Verwerfen: `DELETE … WHERE device_id=?`. Zwei Browser mit demselben Code, zwei Geräte mit derselben Kennung — genau einer gewinnt, wie bei `pair.php` 175–198 heute | `pair.php` 175–198 |
| **E-S5-14** | **Die Sitzungsobergrenze zählt unverfallene Sitzungen per SQL** (`COUNT(*) … WHERE erstellt_am > NOW()-frist`), nicht Zeilen. Der Aufräumjob läuft täglich (Befund 1.3.1). Dazu löscht `start` **nichts** vorab: Ein Angreifer soll den Server nicht mit jedem `start` aufräumen lassen; das tut der Job | `jobs_lib.php` 204 |
| **E-S5-15** | **`bestaetigen` ist idempotent, `status` kennt „gekoppelt“.** Kopfzeilen werden erst gegen `pair_sessions`, dann gegen `devices` gesucht. Trifft ein `bestaetigen ja` oder ein `status` ein bereits angelegtes Gerät (Antwort auf dem Rückweg verloren, Uhr wiederholt), antwortet der Server `200 {"ok":true}` bzw. `{"zustand":"gekoppelt"}` — sonst stünde ein Gerät im Konto, von dem die Uhr nichts weiß, und die Kopplung hinge an einem einzigen Funkpaket | dieses Konzept; F-S5-08 |
| **E-S5-16** | **Drei Töpfe, ein alter bleibt.** Neu `pair_start` (je IP, zählt jede Anfrage: `rate_zaehlen`), `pair_code` (Code-Eingabe im Web, Merkmale `ip:` **und** `id:<user_id>`, zählt Fehlversuche, `rate_erfolg` bei Treffer), `pair_sitzungen` ist **kein** Topf, sondern die SQL-Zählung aus E-S5-14 mit eigener Konstante. Der Topf `pair` bleibt unverändert für 401 an `status`/`bestaetigen`/`trennen` **und für das Token von `jobs.php`** (B-S5-04) | `ratelimit_lib.php` 51–74, 266–305 |
| **E-S5-17** | **Was zählt und was nicht.** Zählt: `start` (Menge), Code im Web nicht gefunden (Fehlversuch), 401 an kopfzeilen-ausgewiesenen Anliegen (Fehlversuch). Zählt **nicht**: ein Code, der das Muster `PAIR_RE` nicht erfüllt (die Datenbank wurde nicht gefragt, es ist nichts zu erraten), `status` mit Treffer, `410 abgelaufen`, `409 device_limit` („der Code war richtig, hier ist niemand am Raten“, `pair.php` 218) | `pair.php` 171, 206–227 |
| **E-S5-18** | **Gerätelimit an zwei Stellen, wie heute an zweien.** Beim Beanspruchen im Web (Feld gar nicht erst anbieten, Meldung wie heute bei `pair_code`) **und** bei `bestaetigen` (zwischen Klick und Ja kann ein Gerät von Hand dazukommen): `409 device_limit`, Sitzung wird gelöscht, die Uhr sagt „Zu viele Geräte / Erst eines im Web löschen“. Beanspruchte, unbestätigte Sitzungen zählen **nicht** gegen `MAX_GERAETE` — der Sonderfall zweier gleichzeitiger Kopplungen endet deterministisch mit einem 409 bei der zweiten Bestätigung | `einstellungen.php` 274–280; `pair.php` 220–227 |
| **E-S5-19** | **Die alte Uhr bekommt eine Meldung, die sie anzeigen kann.** `400 {"error":"aktion","meldung":"Uhr-App aktualisieren"}` — 21 Zeichen, unter `ZEILE_MAX` 26; Uhr 2.0.0 zeigt die Servermeldung im unbekannten Fall als zweite Zeile | `Pair.mc` 64, 330–333 |
| **E-S5-20** | **Die Kopplungsmail geht bei `bestaetigen ja`**, nicht beim Klick im Web. Der Wortlaut von E-R49-1 („hier greifen `MAX_GERAETE` und die Kopplungsmail“) nennt die Bestätigungsseite; gemeint ist der Schutzzweck, und der Zweck der Mail ist „ein Gerät ist **dazugekommen**“ (E-R49-5a: Entdeckungsnetz). Beim Klick gibt es das Gerät noch nicht, und ein Nein am Gerät machte die Mail falsch. Die Mail nennt Art, Modell, Kennung, Zeit — wie heute (`pair.php` 340–356). Abweichung vom Wortlaut, deshalb zusätzlich F-S5-09 | `pair.php` 319–359 |
| **E-S5-21** | **Maskierung der E-Mail:** die ersten zwei Zeichen des lokalen Teils, `***`, `@`, die volle Domain (`ph***@gen-em.de`); ein lokaler Teil aus einem Zeichen zeigt dieses eine. Kleingeschrieben. Die Domain bleibt voll, weil sie die Trägerin erkennen lässt und einem Ableser nichts gibt, was er nicht ohnehin weiß. Eine Funktion `email_maskieren()` in `db.php`, geprüft in der Kopplungsprobe | E-R49-4 |
| **E-S5-22** | **Die Uhr hält Code und Zugangsdaten bis zum Ja nur im Arbeitsspeicher** (Modulvariablen in `Pair`), das Label nur im Dialog. Erst `200 {"ok":true}` auf `bestaetigen ja` schreibt `Storage "cred"`. Wird die App vorher verlassen, ist die Sitzung für die Uhr weg und verfällt auf dem Server | E-R49-2/-4 |
| **E-S5-23** | **BACK in der Code-Ansicht bricht ab und meldet es**: die Uhr sendet `bestaetigen nein` (gilt in jedem Zustand, löscht die Sitzung) und geht auf die Sync-Seite zurück. Nicht warten auf die Antwort — lokal ist der Abbruch sofort, wie beim Trennen (R47) | R47-Muster |
| **E-S5-24** | **Die Code-Ansicht ist eine eigene View (`PairView`)** über der Sync-Seite, kein vierter Zustand des Mittelblocks: Der Code muss groß stehen (Buchstaben — **keine** Ziffernschrift, `Uhr-Layout_Regeln.md` 3.1), die Seite hat eine Restzeit, und BACK hat eine andere Bedeutung als auf der Sync-Seite. Die Rückbestätigung ist der vorhandene Baustein `WatchUi.Confirmation` (wie Trennen, Einsatzabschluss, Verlassen) | `Pair.mc` 32–41, `ClockView.mc` 116, 125 |
| **E-S5-25** | **Die Abfrage läuft am 2-s-Zeitgeber der Uhr, aber höchstens alle 5 s und nie überlappend.** `PairView` benutzt dasselbe Muster wie `SyncView` (`Timer` mit `requestUpdate`), stößt `status` aber nur an, wenn die vorige Antwort da ist und der Takt verstrichen ist. Ein Verbindungsfehler (Code < 0) beendet die Sitzung nicht — der Code bleibt stehen, die zweite Zeile sagt „Telefon in Reichweite?“, die Abfrage läuft weiter bis zur Frist | `SyncView.mc` 23–36; `Pair.mc` 317–323 |
| **E-S5-26** | **Die Bestätigungsseite ist ein zweiter Zustand der Karte „Gerät koppeln“**, keine eigene Seite und kein Dialog. Sie zeigt eine Sache (ein Gerät) und verlangt eine Handlung, hat aber Inhalt, den ein Dialog nicht tragen soll (Art, Modell, Kennung gekürzt, Restzeit; `Design.md` 9.11). Bausteine: `ui_karte`, `ui_zeile` mit Kleinzeile (wie die Geräteliste), `ui_knopf` primär/leise im `.listen-form-fuss`. **Keine neue Darstellung, kein Mockup nötig** | `Design.md` 9.0–9.11 |
| **E-S5-27** ~~gilt nicht mehr~~ | **Umgekehrt durch E-S5-53** (03.09.2026): Es wird nachgeladen. Der ursprüngliche Wortlaut: **Kein Nachladen im Web nach der Beanspruchung.** Die Karte zeigt die Meldung „Jetzt am Gerät bestätigen …“; das Gerät erscheint nach dem Neuladen in der Liste — wie heute nach der Kopplung („Das Gerät erscheint nach der Kopplung unten in der Liste“). Ein Abfragen im Browser wäre neues JavaScript für einen Vorgang, der selten und kurz ist (F-S5-05, Backlog-Kandidat) | `einstellungen.php` 2965 |
| **E-S5-28** | **`pair_codes` wird gelöscht.** Der alte Weg entfällt vollständig (E-R49-7); eine Tabelle ohne Leser ist ein Ort, an dem eine Migration später stolpert. Die Migration ist destruktiv im Sinn von M6-01: `zerstoert` wird angegeben, `inhalt` nicht — die Codes sind zehn Minuten gültige Zufallswerte, nichts davon ist von Hand eingegeben (Begründung nach dem Muster `2026_07_19_phase10_entfernen`, `update.php` 118–129) | `update.php` 105–142; F-S5-07 |
| **E-S5-29** | **Die Uhr-Auslieferung nimmt mit:** die neu gerasterten Kacheln aus S3 (E-S3-04), den Vorgabewert `serverUrl`, den Ersatz von `nadoku.beispieldomain.de` an den Stellen, die S5 ohnehin anfasst (`properties.xml`, `settings.xml`, `Uploader.mc` 216, Handbuch 12), und Backlog 66 (`watch/` in die Wortliste als Bereich `e`). **Eine Auslieferung statt zweier** (R29/R47) | E-S3-04, R47, Backlog 66 |
| **E-S5-30** | **Bereich `e` der Wortliste umfasst XML und Monkey C.** Backlog 66 nennt `watch/resources/**/*.xml`; die sichtbaren Texte der Kopplung stehen aber als Literale in `Pair.mc`, `SyncView.mc`, `StartView.mc` (`"Gekoppelt"`, `"Zu viele Geräte"`, …). Ein Bereich, der die XML prüft und die `.mc` übergeht, meldet wieder eine Null über etwas, das er nicht angesehen hat (`CLAUDE.md` 6). Monkey C kommentiert wie JavaScript (`//`, `/* */`); der JS-Zerleger passt (F-S5-10, B-S5-03) | Backlog 66; `tools/wortliste/LIESMICH.md` |
| **E-S5-31** | **Antwortgleichheit an den neuen Zweigen wie an den alten.** `status`/`bestaetigen` mit unbekannter Kennung laufen gegen `AUTH_VERGLEICHSWERT`; jeder Fehlerzweig endet über `abweisen()`; `410` und `409` sind **keine** Auskunft über Fremdes (sie setzen die richtige Kennung **und** den richtigen Schlüssel voraus) und dürfen deshalb ohne Verzögerung kommen. Die Rümpfe für „Kennung unbekannt“ und „Schlüssel falsch“ sind byteweise gleich | `pair.php` 99–118; `Technik.md` 1937–1945 |
| **E-S5-32** | **Abfragetakt 5 s, fest im Client, kein Serverfeld** (F-S5-01, Z-11). Mit E-S5-41 ist die Zahl eine Bedienzahl und keine Lastzahl: eine `status`-Abfrage kostet den Server einen SHA-256-Vergleich, keine bcrypt-Prüfung | Auftraggeber 03.09.2026 |
| **E-S5-33** | **`start` je IP: 20 je 600 s, Sperre 600 s** (F-S5-02, Z-12) | Auftraggeber 03.09.2026 |
| **E-S5-34** | **`PAIR_SITZUNGEN_MAX` = 1000**, per SQL über unverfallene Zeilen (F-S5-03, Z-13) | Auftraggeber 03.09.2026 |
| **E-S5-35** | **Keine Nachfrist** (F-S5-04; bestätigt E-S5-12) | Auftraggeber 03.09.2026 |
| **E-S5-36** | **`pair_codes` wird gelöscht** (F-S5-07; bestätigt E-S5-28) | Auftraggeber 03.09.2026 |
| **E-S5-37** | **Idempotenz über `devices` ohne Bedenken** (F-S5-08; bestätigt E-S5-15) | Auftraggeber 03.09.2026 |
| **E-S5-38** | **Kopplungsmail bei `bestaetigen ja`** (F-S5-09; bestätigt E-S5-20) | Auftraggeber 03.09.2026 |
| **E-S5-39** | **F-S5-11 ist gemessen, nicht entschieden:** Der Simulator verlangt TLS **und** einen bekannten Aussteller — `http://` erreicht den Server, die App bekommt `-1001`; selbstsigniert scheitert mit `unknown ca`; eine eigene CA im Systemspeicher liefert die echte Serverantwort. `lokal_starten.sh` legt die CA seit dem 03.09.2026 an; der Rundlauf in Paket C läuft ohne Attrappe | Vorbereitung 8a |
| **E-S5-40** | **Backlog 66 gehört zu Paket C** (V-S5-04): Bereich `e` der Wortliste entsteht in S5, nicht in einer anderen Instanz; `docs/Backlog.md` und `CLAUDE.md` 6 zieht C nach | Auftraggeber 03.09.2026 |
| **E-S5-41** | **Der Sitzungsschlüssel in `pair_sessions` wird als SHA-256 abgelegt, nicht als bcrypt** (F-S5-12). Die Regel, nach der das Projekt seine Verfahren wählt: **aus einem Passwort abgeleitet → bcrypt** (die Langsamkeit ist der Schutz, die Entropie bleibt die des Passworts); **Zufall mit ≥ 128 Bit → SHA-256 + `hash_equals`** (Langsamkeit kauft nichts; das Muster steht schon bei den Reset-Token, `pw_handling.php` 107); **muss der Server es zurücklesen → AES-GCM mit dem Serverschlüssel** (`serverkrypto_lib.php`). HMAC mit dem Serverschlüssel wurde erwogen und verworfen: kein Zugewinn gegen den Datenbankdieb bei 192 Bit Zufall, aber eine Abhängigkeit von `config.php` an einem Weg, der sie nicht braucht. Folge: 120 `status`-Abfragen kosten Mikrosekunden statt rund 27 s bcrypt (PHP 8.4, `PASSWORD_DEFAULT` = Kostenfaktor 12: 228 ms je Prüfung, gemessen) | Auftraggeber 03.09.2026 |
| **E-S5-42** | **Auch der Geräteschlüssel in `devices` wird SHA-256** — in Paket A, mit Hauptnummer. Prämisse des Auftraggebers: Ab 1.0 gibt es genau eine, frisch installierte Installation; Bestandsgeräte und Migration entfallen, und genau das macht den Wechsel jetzt billig (später bräuchte er einen Umhash-Pfad beim nächsten Upload). bcrypt war hier das falsche Werkzeug (192 Bit Zufall) am teuersten Pfad: jeder Upload zahlte 228 ms, und dort saß die Drift zu `AUTH_VERGLEICHSWERT` (Kostenfaktor 10 = 57 ms). Fundstellen: `ingest.php` 67–74, `pair.php` (3), `einstellungen.php` 263, `einsatz_form.php` 502, `api/gpx_import.php` 62, `api/import_commit.php` 246, `api/schneiden.php` 70, `schema.sql` 42, `tools/ingestprobe`, `tools/pruefkonten`, `tools/wiederherstellungs-probe`, `Technik.md`. **Der Drahtvertrag von `ingest.php` bleibt byteweise gleich.** `AUTH_VERGLEICHSWERT` zieht sich auf `login.php` zurück; die Gerätepfade vergleichen in konstanter Zeit (`hash_equals`) gegen einen festen SHA-256-Vergleichswert, damit der unbekannte Zweig dieselben Schritte geht wie der bekannte. **Preis, der Abschnitt 8.1 ändert:** Die eine Bestandsuhr trägt einen bcrypt-Hash und wird nach dem Deploy mit `401` abgewiesen — sie wird **einmal neu gekoppelt** (mit der neuen Uhr-Fassung, die S5 ohnehin bringt), nachdem ihr Sync vollständig ist. Kein Umhash-Pfad: Er wäre die Flickschusterei, die 1.0 gerade vermeiden soll. Die Demo-Geräte der Fixture behalten ihre bcrypt-Zeichenketten; sie passen dann nie — was für Geräte, deren Klartextschlüssel niemand hat, der richtige Zustand ist | Auftraggeber 03.09.2026 |
| **E-S5-43** | **Das Anmeldetoken bleibt bcrypt.** Es ist gestrecktes Passwort (PBKDF2 im Browser, `KDF_ITER_ZIEL` Runden), nicht mehr Entropie als das Passwort selbst; bcrypt ist die zweite Bremse gegen den Datenbankdieb und kostet eine Prüfung je Anmeldung. HMAC mit dem Serverschlüssel stellte Rechenarbeit auf Schlüsselgeheimhaltung um — wer Server samt `config.php` hat, zahlte dann nur noch PBKDF2 je Rateversuch. Sieben Fundstellen, die Demo-Fixture mit vorgerechnetem Hash und eine Hauptnummer für einen Umbau ohne Gewinn | Auftraggeber 03.09.2026 |
| **E-S5-44** | **`beansprucht_am` entfällt** (V-S5-03). Die Spalte wäre geschrieben und nie gelesen worden — der Fristvergleich läuft über `erstellt_am` (E-S5-12) —, und mit ihr entfällt die Mischung aus `TIMESTAMP` und `DATETIME`. Beanspruchen ist `UPDATE pair_sessions SET user_id = ? WHERE code = ? AND user_id IS NULL AND erstellt_am > …`, gültig bei `rowCount() === 1` (E-S5-13 entsprechend gelesen) | Auftraggeber 03.09.2026 |
| **E-S5-45** | **`server/demo_lib.php` gehört zu Paket A** (V-S5-01): das Zurücksetzen des Demo-Kontos löscht aus `pair_sessions` statt `pair_codes` | Auftraggeber 03.09.2026 |
| **E-S5-46** | **`docs/Backup-Format.md` gehört zu Paket D** (V-S5-02): die Zeile zu `pair_codes` wird umgeschrieben, nicht gestrichen — „diese Tabelle fehlt mit Absicht“ gilt für `pair_sessions` genauso | Auftraggeber 03.09.2026 |
| **E-S5-47** | **`android/LIESMICH.md` gehört zu Paket D** (V-S5-06): die Anleitung zum Server-Rundlauf beschreibt den alten Weg; der Testkopf von `KopplungRundlaufTest.kt` bleibt dem S4-Rest. Bis dahin läuft der Android-Rundlauf gegen einen Server vom Stand `main` (Paket E baut seine Installation aus dem eigenen Zweig) | Auftraggeber 03.09.2026 |
| **E-S5-48** | **`bestaetigen nein` mit Gerätezugang ist ein Nichtstun** — `200 {"ok":true}`, das Gerät bleibt. Der Fall: Das Ja hat das Gerät angelegt, die Antwort ging verloren, und statt zu wiederholen drückt jemand BACK. Ein Nein, das ein fertiges Gerät löschte, wäre ein Trennen ohne Trennen-Mail; das Gerät steht dann ohne Schlüsselhalter in der Liste, die Kopplungsmail hat es gemeldet, „neu“ zeigt es sieben Tage — löschen im Web. Kopplungsprobe Fall E48 | Umsetzung A |
| **E-S5-49** | **`trennen` mit schwebenden Zugangsdaten wirkt wie `nein`:** Sitzung gelöscht, `200`. Es gibt kein Gerät, das sich trennen ließe; die Sitzung braucht danach niemand mehr. Ein konformer Client sendet das nie, aber der Zweig braucht eine Antwort, die nichts kaputtmacht | Umsetzung A |
| **E-S5-50** | **`rate_erfolg('pair')` ruft nur `trennen`** (wie bisher). Ein gelungenes `status` oder `bestaetigen` leert den Topf nie: Sonst setzte ein Angreifer mit einer eigenen, gültigen Sitzung alle fünf Sekunden den IP-Zähler zurück, während er daneben fremde Kennungen durchprobiert. Kopplungsprobe Fall 19 misst es | Umsetzung A |
| **E-S5-51** | **Die Bibliothek `server/kopplung_lib.php`** trägt Frist-SQL, Anlegen mit Dublettenschleife, Suche nach Kennung und Code und das Beanspruchen — Paket B ruft `pair_sitzung_nach_code()` und `pair_sitzung_beanspruchen()` und legt die Frist nirgends zweimal aus. Die Kopplungsprobe prüft die Dublettenschleife über eine eingeschobene Codequelle (Fall 25), weil sich der Zufall über HTTP nicht patchen lässt | Umsetzung A |
| **E-S5-52** | **Doku-Schnitt zwischen A und D:** A berichtigt in `Technik.md` nur, was A falsch gemacht hätte (Datenmodellzeile `pair_sessions`, Jobkatalog, Antwortzeit- und Sicherheitstabelle, Verzeichnisstruktur); der Kopplungsweg selbst (JSON-Vertrag 1a, Handbuch 12, Technik 4.99b) bleibt bei D, wie die Dateiliste es vorsieht. Bis D beschreibt der Vertrag den alten Weg — der Statusblock sagt es | Umsetzung A |
| **E-S5-53** | **Die Geräteseite lädt nach — F-S5-05 ist mit Ja entschieden (Auftraggeber, 03.09.2026), und das kehrt E-S5-27 um.** Nach der Beanspruchung wartet die Karte nicht stumm: Ein kleines Skript fragt im Takt nach, ob das Gerät inzwischen Ja gesagt hat, und holt dann die Seite. Sechs Bedingungen, damit aus der Bequemlichkeit kein zweites Problem wird: **(a)** ein eigener, angemeldeter Endpunkt unter `server/api/`, der genau eine Frage beantwortet — „gibt es zu dieser Kennung schon ein Gerät in meinem Konto?“ — und sonst nichts; **(b)** gefragt wird nach der **Gerätekennung der beanspruchten Sitzung**, die die Bestätigungsseite ohnehin gekürzt anzeigt, nicht nach „hat sich irgendetwas geändert“; **(c)** die Abfrage endet von selbst — bei Erfolg, bei Ablehnung am Gerät, mit dem Ende der Frist und nach drei Fehlversuchen in Folge; sie läuft nie länger als die zehn Minuten, die die Sitzung lebt; **(d)** sie ruht, solange der Reiter im Hintergrund liegt; **(e)** ohne JavaScript bleibt der Weg vollständig — der Satz „lade die Seite neu“ steht weiter da, das Skript nimmt ihn nur vorweg; **(f)** der Sprung am Ende ist eine **GET-Navigation**, kein `reload()`: Die Seite entstand aus einem POST, und ein Neuladen fragte nach dem erneuten Absenden. **Warum die Umkehr:** E-S5-27 rechnete mit „selten und kurz“ und übersah, dass die Stelle die einzige im ganzen Ablauf ist, an der die Person nichts tun kann und trotzdem nicht weiß, ob es geklappt hat — sie schaut auf die Uhr, drückt Ja, schaut zurück und sieht eine Seite, die noch den alten Stand zeigt. Der Preis ist eine Datei Skript und ein Endpunkt, beide klein und beide abschaltbar | Auftraggeber 03.09.2026 |

---

## 3. Offene Fragen (F-S5) — mit Empfehlung

Nach K6 vor Beginn des betroffenen Pakets zu entscheiden und dann als
E-Eintrag hier einzutragen. Die Nummern der Zahlen verweisen auf Abschnitt 5.

| Nr. | Frage | Empfehlung | Paket |
|---|---|---|---|
| **F-S5-01** ✓ | **Abfragetakt der Uhr:** 5 s (Vorschlag, Abschnitt 5 Z-11)? Und soll der Server einen `takt_s` mitgeben, um ihn im Betrieb drosseln zu können? | **Entschieden 03.09.2026 → E-S5-32.** **5 s, fest im Client, kein Serverfeld.** Höchstens 120 Anfragen je Sitzung, Verzögerung nach dem Klick im Web höchstens fünf Sekunden; ein Serverfeld wäre Vertrag für einen Fall, den es noch nicht gibt. Bei Bedarf ist es ein Nachtrag ohne Vertragsbruch (Clients ignorieren unbekannte Felder) | C, A |
| **F-S5-02** ✓ | **`start` je IP** — 20 je 10 Minuten (Vorschlag, Z-12)? Der Kursfall: viele Uhren im selben WLAN oder hinter derselben Mobilfunk-NAT koppeln gleichzeitig | **Entschieden 03.09.2026 → E-S5-33.** **20 / 600 s / Sperre 600 s.** Zehn wie beim Topf `pair` reichten für einen Kurs mit zwölf Personen nicht; hundert machten den Topf wertlos gegen das Füllen der Obergrenze (5000 Sitzungen aus 50 Adressen). Wer die Zahl im Betrieb reißt, sieht „Zu viele Versuche / Später noch einmal“ und wartet zehn Minuten — kein Datenverlust | A |
| **F-S5-03** ✓ | **Obergrenze offener Sitzungen** — 1000 (Vorschlag, Z-13)? | **Entschieden 03.09.2026 → E-S5-34.** **1000.** Herleitung in Abschnitt 5; Trefferwahrscheinlichkeit je Rateversuch ≤ 1 · 10⁻⁶, und ein Treffer läuft noch in das Tor der Rückbestätigung. Bei 1000 Konten (R36) ist die Zahl das theoretische Maximum gleichzeitiger legitimer Kopplungen | A |
| **F-S5-04** ✓ | **Nachfrist nach der Beanspruchung** (etwa zwei Minuten), damit ein Klick in Minute 9:50 nicht ins Leere läuft? | **Entschieden 03.09.2026 → E-S5-35.** **Nein** (E-S5-12). Beide Seiten zeigen die Restzeit; wer knapp ist, startet neu — ein START-Halten | A |
| **F-S5-05** ✓ | **Nachladen der Geräteseite** nach der Beanspruchung, damit das Gerät ohne Neuladen erscheint? | **Entschieden 03.09.2026 → E-S5-53: Ja, mit Nachladen** (gegen die Empfehlung unten). Ursprüngliche Empfehlung: **Nein in S5** (E-S5-27); Backlog-Kandidat „Geräteseite lädt nach der Kopplung nach“ | B |
| **F-S5-06** | **Freigabe der Code-Ansicht der Uhr.** Es ist eine neue Ansicht. Skizze in Abschnitt 6.3; genügt die Freigabe am **Simulatorbild** (`pruefstand.sh abbild`) in Paket C, oder soll vorher ein Mockup her? | **Freigabe am Simulatorbild.** Die Skizze legt Zeilen, Schriften und Farben nach `Uhr-Layout_Regeln.md` fest; ein Mockup wäre eine Zeichnung derselben Zeilen. Das Bild auf allen drei Zielgeräten geht mit dem Paket C an den Auftraggeber, **bevor** C committet wird | C |
| **F-S5-07** ✓ | **`pair_codes` löschen** (E-S5-28) oder als tote Tabelle stehen lassen? | **Entschieden 03.09.2026 → E-S5-36.** **Löschen.** Migration mit `zerstoert`-Angabe; `update.php` nach dem Deploy ist ohnehin fällig | A |
| **F-S5-08** ✓ | **Idempotenz über `devices`** (E-S5-15): Ein `status` mit gültigen Gerätekopfzeilen antwortet „gekoppelt“ — das ist eine Auskunft „diese Zugangsdaten sind gültig“, die es heute nur über `ingest.php` gibt. Bedenken? | **Entschieden 03.09.2026 → E-S5-37.** **Keine.** Wer Kennung **und** Schlüssel hat, kann heute hochladen; „gekoppelt“ sagt ihm nichts Neues. Ohne die Antwort hinge die Kopplung an einem einzigen Funkpaket | A |
| **F-S5-09** ✓ | **Zeitpunkt der Kopplungsmail** — bei `bestaetigen ja` (E-S5-20) statt beim Web-Klick, abweichend vom Wortlaut E-R49-1 | **Entschieden 03.09.2026 → E-S5-38.** **Bei `bestaetigen ja`.** Begründung in E-S5-20 | A |
| **F-S5-10** | **Umfang von Wortliste-Bereich `e`** — nur `watch/resources/**/*.xml` (Backlog 66) oder auch `watch/source*/*.mc` (E-S5-30)? | **Beides.** Die Kopplungstexte stehen in `.mc` | C |
| **F-S5-11** ✓ | **Simulator gegen lokalen Server:** Erlaubt `makeWebRequest` im Simulator `http://127.0.0.1:8080`, oder verlangt er TLS wie das Gerät? Aus dem Container nicht ermittelbar (Abschnitt 11) | **Entschieden 03.09.2026 → E-S5-39.** **Erst messen** (ein Aufruf gegen `php -S`). Geht http: Rundlauf wie geplant. Geht nur https: lokales selbstsigniertes Zertifikat vor `php -S` (`stunnel` oder ein lokaler Apache) — und wenn auch das scheitert, ersetzt die **Kopplungsprobe** den Serverteil des Rundlaufs, und der Simulator läuft gegen eine Attrappe, die die vier Antworten liefert. Was dann fehlt, steht im Prüfdokument an erster Stelle und im Gerätetest | C |
| **F-S5-12** ✓ | **Hash des Sitzungsschlüssels** (nachgetragen 03.09.2026 aus V-S5-13 der Vorbereitung): bcrypt wie bei `devices`, SHA-256, oder HMAC-SHA256 mit dem Serverschlüssel? Und gehören Anmeldetoken und Geräteschlüssel gleich mit umgestellt? | **Entschieden 03.09.2026 → E-S5-41 bis -43.** SHA-256 für die Sitzung **und** für `devices`; das Anmeldetoken bleibt bcrypt; kein HMAC | A |

---

## 4. Vertragsabschnitt 1a — neuer Wortlaut

Zur Übernahme nach `docs/JSON-Vertrag.md` in Paket D. Er ersetzt den heutigen
Abschnitt 1a vollständig (Fassung 1.4); **1a.1** (die zwei Formen des
`geraet`-Blocks) und **1a.2** (was der Server davon speichert) bleiben
inhaltlich bestehen und rücken als **1a.4** und **1a.5** unter den neuen
Text. Abschnitt 1b (Trennen) bleibt; seine Tabelle bekommt die Zeile 429 mit
„gilt für alle vier Anliegen“ (B-S5-06). Abschnitt 0 (Stand der
Durchsetzung) bekommt die Zeile „Kopplung in drei Anliegen (1a) — durchgesetzt
seit Web ‹Fassung›“; die Fassung trägt die Umsetzung ein (K3).

> ## 1a. Kopplung (`pair.php`) — seit Uhr ‹Fassung› / Web ‹Fassung›
>
> `pair.php` kennt **vier** Anliegen, alle per `POST` mit
> `Content-Type: application/json` und einem Pflichtfeld `aktion`:
> `start`, `status` und `bestaetigen` (dieser Abschnitt) und `trennen`
> (Abschnitt 1b). Ein Rumpf ohne `aktion` oder mit einer unbekannten
> Aktion bekommt `400 {"error":"aktion","meldung":"Uhr-App aktualisieren"}`
> — die Meldung ist für Clients der alten Fassung gedacht, die den
> Kopplungscode noch **senden** statt ihn zu **zeigen**.
>
> **Das Gerät zeigt einen Code, das Web nimmt ihn entgegen, das Gerät
> bestätigt das Konto.** Der Ablauf hat drei Schritte, und jeder ist ein
> Anliegen:
>
> 1. **`start`** — das Gerät bittet um eine Kopplungssitzung. Ohne
>    Kopfzeilen: Es hat noch keine. Der Server antwortet mit einem
>    **Anzeigecode** für den Menschen und mit den **Zugangsdaten**, die das
>    Gerät ab jetzt trägt, aber erst nach Schritt 3 benutzen darf.
> 2. **`status`** — das Gerät fragt nach, ob jemand den Code in sein Konto
>    eingegeben hat. Mit den Kopfzeilen aus Abschnitt 1 (aus Schritt 1).
> 3. **`bestaetigen`** — das Gerät sagt Ja oder Nein zu dem Konto, das der
>    Server in Schritt 2 genannt hat. Mit Kopfzeilen. Nach Ja gibt es das
>    Gerät; nach Nein gibt es die Sitzung nicht mehr.
>
> Der Code ist **nur für den Menschen**. Er weist das Gerät nirgends aus;
> wer ihn abliest, kann am Gerät nichts auslösen. Was das Gerät ausweist,
> sind Kennung und Schlüssel aus Schritt 1 — und die sind bis Schritt 3
> **schwebend**: Der Server kennt sie, aber `ingest.php` weist sie ab
> (`401`), weil es das Gerät noch nicht gibt.
>
> ### 1a.1 `start` — Sitzung anlegen
>
> ```json
> { "aktion": "start",
>   "geraet": { "art": "uhr", "teil": "006-B4261-00", "br": 390, "ho": 390,
>               "touch": true, "fw": 1140, "ciq": "5.2.0", "app": "2.1.0" } }
> ```
>
> `geraet` ist **freiwillig** und kommt in zwei Formen (1a.4). Eine
> Kopplung scheitert nie an einer Statistikangabe — aber Art und Modell sind
> das, was die Kontoinhaberin auf der Bestätigungsseite **sieht**; ein
> Gerät, das nichts über sich sagt, erscheint dort als „Gerät unbekannt“.
>
> Antwort `200`:
>
> ```json
> { "code": "AB3K7Q",
>   "device_id": "dev-3f9a…",
>   "api_key": "8c1e…",
>   "frist_s": 600 }
> ```
>
> | Feld | Bedeutung |
> |---|---|
> | `code` | sechs Zeichen aus dem Alphabet `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (ohne 0/O und 1/I — sie sind auf einem Uhrendisplay nicht zu unterscheiden). **Ohne Trennzeichen.** Das Gerät zeigt ihn in zwei Dreiergruppen („AB3 K7Q“); das Web nimmt ihn mit und ohne Leerzeichen, in jeder Schreibung |
> | `device_id` | die künftige Gerätekennung, `dev-` + 32 Hexzeichen (16 Zufallsbytes) |
> | `api_key` | der Geräteschlüssel im Klartext, 48 Hexzeichen (24 Bytes). **Er wird genau einmal übertragen — hier.** Der Server speichert nur den Hash |
> | `frist_s` | Restgültigkeit in Sekunden ab dieser Antwort (600). Nach Ablauf sind Code **und** Zugangsdaten wertlos; das Gerät beginnt dann von vorn |
>
> Fehler:
>
> | Code | Rumpf | Bedeutung / Verhalten des Geräts |
> |---|---|---|
> | 429 | `{"error":"zu_viele_versuche"}` | zu viele Sitzungen von dieser Adresse — später noch einmal |
> | 429 | `{"error":"zu_viele_sitzungen"}` | der Server hält gerade so viele offene Sitzungen, wie er höchstens hält — später noch einmal; der Zustand ist Minuten, nicht Stunden |
> | 405 | `{"error":"method"}` | falsche HTTP-Methode |
>
> ### 1a.2 `status` — nachfragen
>
> Mit den Kopfzeilen `X-Device-Id` und `X-Api-Key` aus Schritt 1:
>
> ```json
> { "aktion": "status" }
> ```
>
> Antwort `200`, drei Zustände:
>
> | Rumpf | Bedeutung |
> |---|---|
> | `{"zustand":"offen","rest_s":540}` | noch hat niemand den Code eingegeben; `rest_s` ist die Restgültigkeit |
> | `{"zustand":"beansprucht","konto":"ph***@gen-em.de","rest_s":300}` | ein Konto hat den Code eingegeben. `konto` ist die **maskierte E-Mail-Adresse** dieses Kontos: die ersten zwei Zeichen des lokalen Teils, `***`, die volle Domain. Sie ist nur für den Dialog auf dem Gerät bestimmt und wird **nicht gespeichert** |
> | `{"zustand":"gekoppelt"}` | zu diesen Kopfzeilen gibt es bereits ein Gerät. Das ist der Fall, wenn die Antwort auf `bestaetigen` verlorenging: Das Gerät darf die Zugangsdaten als gültig speichern |
>
> Das Gerät fragt **höchstens alle fünf Sekunden** und nie, bevor die vorige
> Antwort da ist. Ein Verbindungsfehler ist kein Grund aufzuhören — die
> Sitzung lebt auf dem Server weiter, bis die Frist abläuft.
>
> Fehler:
>
> | Code | Rumpf | Bedeutung / Verhalten des Geräts |
> |---|---|---|
> | 401 | `{"error":"auth"}` | Kennung oder Schlüssel unbekannt — weder Sitzung noch Gerät. Für das Gerät dasselbe wie 410: von vorn beginnen |
> | 410 | `{"error":"abgelaufen"}` | die Sitzung ist verfallen oder verworfen. Code und Zugangsdaten wegwerfen, von vorn beginnen |
> | 429 | `{"error":"zu_viele_versuche"}` | Ratenschutz — gilt für alle vier Anliegen |
>
> ### 1a.3 `bestaetigen` — Ja oder Nein
>
> Mit Kopfzeilen. `antwort` ist Pflicht und lautet `ja` oder `nein`:
>
> ```json
> { "aktion": "bestaetigen", "antwort": "ja" }
> ```
>
> | Antwort | Bedeutung |
> |---|---|
> | `200 {"ok":true}` (nach `ja`) | **Das Gerät existiert jetzt** und ist aktiv; die Sitzung ist beendet. Das Gerät speichert Kennung und Schlüssel dauerhaft. Die Kontoinhaberin bekommt eine E-Mail — dieselbe wie bisher beim Koppeln. Eine Wiederholung derselben Anfrage antwortet ebenfalls `200` (das Gerät gibt es schon) |
> | `200 {"ok":true}` (nach `nein`) | die Sitzung samt Zugangsdaten ist gelöscht. `nein` ist in **jedem** Zustand erlaubt, auch `offen` — so bricht ein Gerät ab, das zurück auf die Sync-Seite geht |
> | `401 {"error":"auth"}` | Kennung oder Schlüssel unbekannt |
> | `409 {"error":"nicht_beansprucht"}` | `ja`, aber noch hat kein Konto den Code eingegeben. Die Sitzung bleibt bestehen; ein Gerät, das sich an dieses Dokument hält, sendet `ja` nur nach `beansprucht` |
> | `409 {"error":"device_limit","meldung":"…"}` | das Konto hat bereits `MAX_GERAETE` Geräte (zwischen Eingabe im Web und Ja kann eines dazugekommen sein). **Die Sitzung ist gelöscht**; erst ein Gerät im Web löschen, dann von vorn |
> | `410 {"error":"abgelaufen"}` | Frist vorbei. Von vorn |
> | `429 {"error":"zu_viele_versuche"}` | Ratenschutz |
>
> Was der Server nach `ja` in einem Zug tut: `devices`-Zeile anlegen (mit
> Konto, Kennung, Schlüssel-Hash, Vorgabename nach Art, Art, Modell,
> Rohangabe aus dem `geraet`-Block von Schritt 1), Sitzung löschen — beides
> in **einer** Transaktion. Scheitert das Anlegen, bleibt die Sitzung
> bestehen und das Gerät bekommt `500 {"error":"server"}`; es darf
> wiederholen.
>
> ### 1a.4 Zwei Formen des Blocks `geraet` — Uhr und Handy
>
> *(unverändert aus Fassung 1.4, heute 1a.1; der Block steht jetzt in
> `start` statt neben dem Code)*
>
> ### 1a.5 Was der Server davon speichert
>
> *(unverändert aus Fassung 1.4, heute 1a.2; Ergänzung: die drei Werte
> werden bei `start` gelesen und aufgelöst, liegen bis `bestaetigen` in der
> Sitzung und werden von dort in die `devices`-Zeile übernommen — das
> Nachauflösen nach E-S6-6 trifft nur `devices`, Sitzungen leben zehn
> Minuten)*
>
> ### 1a.6 Was ein Client der alten Fassung sieht
>
> Ein Gerät, das `{"code": "…"}` sendet, bekommt `400` mit `error: aktion`
> und `meldung: "Uhr-App aktualisieren"`. Es gibt **keine** Übergangszeit,
> in der beide Wege gehen (E-R49-7): Der alte Weg setzte einen im Web
> erzeugten Code voraus, und den gibt es nicht mehr. Bestehende Kopplungen
> sind davon nicht berührt — `ingest.php` und Abschnitt 1 ändern sich nicht.

---

## 5. Zahlen — jede mit Fundstelle und Begründung

Spalte „Herkunft“: **Code** = steht heute so im Repositorium und wird
übernommen; **abgeleitet** = aus dem Code gerechnet; **Vorschlag** = neu,
mit Begründung, in Abschnitt 3 zur Entscheidung gestellt.

| Nr. | Was | Wert | Herkunft | Fundstelle und Begründung |
|---|---|---|---|---|
| Z-01 | Codealphabet | 32 Zeichen, ohne 0/O/1/I | Code | `db.php` 459: auf einem Uhrendisplay nicht unterscheidbar — gilt beim **Ablesen** genauso wie beim Eintippen |
| Z-02 | Codelänge | 6 Zeichen = 30 Bit ≈ 1,07 · 10⁹ | Code | `db.php` 460, 448–452: die Arbeit macht der Ratenschutz, nicht die Länge |
| Z-03 | Anzeige | zwei Dreiergruppen „AB3 K7Q“ | abgeleitet | E-R49-1 nennt die Form; 7 Zeichen passen in `FONT_LARGE` auf 240 px, sonst greift `Ui.fitFont()` (`Ui.mc` 125–135) |
| Z-04 | Frist | 10 Minuten | Code | `db.php` 461, 456–458: „mit der Uhr in der Hand“ — der Ablauf hat sich nur umgedreht, die Nähe ist dieselbe |
| Z-05 | Gerätekennung | `dev-` + 16 Zufallsbytes | Code | `pair.php` 229–249 (M4-08): Geburtstagsproblem bei 4 Bytes |
| Z-06 | Geräteschlüssel | 24 Zufallsbytes, Hash `PASSWORD_DEFAULT` | Code | `pair.php` 250, 281 |
| Z-07 | Gerätelimit | 5 je Konto, aktive **und** deaktivierte | Code | `db.php` 575–580 |
| Z-08 | Code-Eingabe im Web (Topf `pair_code`) | 10 Fehlversuche je 600 s, Sperre 600 s; Merkmale `ip:` und `id:<user_id>` | Code, übertragen | heutiger Topf `pair`, `ratelimit_lib.php` 43–49, 55: „eine Person mit Tippfehlern bemerkt sie nicht, Durchprobieren wird aussichtslos“. Mit Z-13: je Merkmal 10 Versuche je 10 Minuten gegen ≤ 1000 gültige Codes in 1,07 · 10⁹ → Treffer je Versuch ≤ 9,3 · 10⁻⁷ |
| Z-09 | 401 an `status`/`bestaetigen`/`trennen` (Topf `pair`) | 10 / 600 s / 600 s, je IP | Code | `ratelimit_lib.php` 55; unverändert, weil `jobs.php` denselben Topf benutzt (`Technik.md` 2294) |
| Z-10 | konstante Antwortdauer bei Misserfolg | 0,35 s | Code | `ratelimit_lib.php` 234; `pair.php` 56–64 |
| Z-11 | **Abfragetakt der Uhr** | 5 s, am 2-s-Zeitgeber der Seite, nie überlappend | **Vorschlag** (F-S5-01) | Zeitgeber: `SyncView.mc` 25 (2000 ms). Bei 5 s: höchstens 120 Anfragen je Sitzung, je eine `password_verify` (bcrypt, Kostenfaktor 10 — `db.php` 476–481) auf dem Server; Verzögerung nach dem Klick ≤ 5 s. Bei 2 s wären es 300 Anfragen für eine Verzögerung, die niemand merkt; bei 10 s wartete die Person nach dem Klick spürbar |
| Z-12 | **`start` je IP** (Topf `pair_start`) | 20 je 600 s, Sperre 600 s, zählt jede Anfrage | **Vorschlag** (F-S5-02) | Muster `rate_zaehlen()` (`ratelimit_lib.php` 184–200: Menge begrenzen, wo es kein Scheitern gibt). Kursfall: viele Uhren hinter einer Adresse |
| Z-13 | **Obergrenze offener Sitzungen** (`PAIR_SITZUNGEN_MAX`) | 1000, gezählt per SQL über unverfallene Zeilen | **Vorschlag** (F-S5-03) | R36: 1000 Konten — mehr gleichzeitige legitime Kopplungen gibt es nicht. Angriff E-R49-6: Bei 1000 gefüllten Sitzungen trifft ein Rateversuch mit ≤ 9,3 · 10⁻⁷; mit Z-08 braucht ein Angreifer rund 10⁵ Adress-Konto-Paare für **einen** erwarteten Treffer — und der läuft noch in das Tor der Rückbestätigung (E-S5-05b). Gegenrichtung (Verstopfen): Z-12 verlangt 50 Adressen je 10 Minuten, und die Sperre dauert Minuten |
| Z-14 | Aufräumen | täglich, Job `aufraeumen`, Schritt „Kopplungssitzungen“ | Code | `jobs_lib.php` 200–207, 502–507: derselbe Schritt, andere Tabelle |
| Z-15 | Mail-Zeitlimit | 5 s | Code | `pair.php` 355; `Technik.md` 1756–1761 |
| Z-16 | Hinweiszeile der Uhr | 26 Zeichen | Code | `Pair.mc` 64 (`ZEILE_MAX`) |
| Z-17 | Meldung an die alte Uhr | „Uhr-App aktualisieren“, 21 Zeichen | abgeleitet | Z-16 |
| Z-18 | Restzeit-Anzeige | volle Minuten, ab 60 s in Sekunden | abgeleitet | `rest_s` aus `status`; die Uhr rechnet nicht selbst gegen die Frist (keine verlässliche Uhr gegenüber dem Server), sie zeigt den letzten Serverwert |
| Z-19 | Migrationsregister | nach S6 **40** Kennungen in `schema.sql` und `update.php`, plus eine | abgeleitet | Rahmenplan Schritt 2 (39 = 39 nach `geraetekennung`, danach `geraetemodell_breiter`); beim Merge gegenzählen (Rahmenplan 2.2) |

---

## 6. Wortlaute und Bedienwege

Alle sichtbaren Texte laufen durch `tools/wortliste/` (R28; Soll 0/0/0 in
den Bereichen a, c und e). Die Texte sind neutral für Land und Luft; der
Garmin-Tastenweg steht wie in E-P2-02 als Zusatz mit genannter Plattform.

### 6.1 Uhr — Sync-Seite (unverändert, zur Orientierung)

Einrichtungszustand: „Nicht eingerichtet“ / `Input.lSelectHold() + ": Gerät
koppeln"` (`SyncView.mc` 94–99). Mit dem Vorgabewert für `serverUrl` tritt
„Erst Server-Adresse setzen“ nur noch bei Selbsthostern auf, die den Wert
geleert haben; der Text bleibt.

### 6.2 Uhr — Weg

1. Sync-Seite → START halten (Venu: Action halten oder Zurück halten) →
   `Pair.start()`. Bei bestehender Kopplung wie heute: Rückstand prüfen,
   „Kopplung trennen und neu koppeln?“, trennen (R47). Danach — und im
   Erstfall sofort — **`start`** statt `openInput()`.
2. Sync-Seite zeigt „Hole Code…“ (hellgrau, `:busy`), bis die Antwort da
   ist. Fehler hier: zweizeilig wie unten.
3. **`PairView`** wird geschoben. Anzeige siehe 6.3. Die Seite fragt
   `status` im Takt Z-11.
4. `beansprucht` → `WatchUi.Confirmation("Mit ph***@gen-em.de koppeln?")`
   über der `PairView`. **Ja** → `bestaetigen ja` → bei `200`: `Storage
   "cred"`, beide Ansichten zurück, Sync-Seite „Gekoppelt“ (grün) — der
   Mittelblock wechselt von selbst auf „Sync vollständig“, weil
   `hasCredentials()` jetzt wahr ist. **Nein** → `bestaetigen nein`, beide
   Ansichten zurück, Sync-Seite „Nicht gekoppelt“ / `lSelectHold() + ":
   neuer Code"`.
5. BACK in der `PairView` → `bestaetigen nein` (ohne auf die Antwort zu
   warten), zurück zur Sync-Seite mit „Abgebrochen“ / `lSelectHold() + ":
   neuer Code"`.
6. Fristablauf (`410`) → zurück zur Sync-Seite mit „Code abgelaufen“ /
   `lSelectHold() + ": neuer Code"`.

**Fehlertexte, zwei Zeilen (Muster Uhr 1.7.0), Entscheidung am Feld
`error`:**

| Auslöser | Zeile 1 (rot) | Zeile 2 (hellgrau) | neu / bestehend |
|---|---|---|---|
| `start` 429 `zu_viele_versuche` | Zu viele Versuche | Später noch einmal | bestehend |
| `start` 429 `zu_viele_sitzungen` | Server ausgelastet | Später noch einmal | neu |
| `status`/`bestaetigen` 410, 401 | Code abgelaufen | `lSelectHold()` + „: neuer Code“ | neu |
| `bestaetigen` 409 `device_limit` | Zu viele Geräte | Erst eines im Web löschen | bestehend |
| Verbindung (Code < 0) in `PairView` | *(Code bleibt stehen)* | Telefon in Reichweite? (n) | bestehend, andere Stelle |
| Verbindung bei `start` oder `bestaetigen` | Keine Verbindung | Telefon in Reichweite? (n) | bestehend |
| Server-Adresse leer | Erst Server-Domain setzen | — | bestehend (`Pair.mc` 240) |
| alles Übrige | Kopplung fehlgeschlagen (n) | Servermeldung, gekürzt | bestehend |

„`lSelectHold()` + `: neuer Code`“ ergibt auf Fünf-Tasten-Uhren „START
halten: neuer Code“ (23 Zeichen), auf der Venu „Action halten: neuer Code“
(24) — beide unter Z-16.

### 6.3 Uhr — die Code-Ansicht (`PairView`), Skizze

Nach `Uhr-Layout_Regeln.md`: feste Blöcke zuerst (unten die Hinweiszeilen),
der Codeblock zentriert im Raum darüber (5.1); jede Zeile durch
`Ui.fitFont()` (4.2); **keine Ziffernschrift** für den Code (3.1 — er trägt
Buchstaben); Farben nach 7.

```
        ┌──────────────────────────┐
        │                          │
        │      Code für das Web    │   fontHint, hellgrau
        │                          │
        │        AB3 K7Q           │   FONT_LARGE → MEDIUM → SMALL (fitFont), weiß
        │                          │
        │   Einstellungen → Geräte │   fontHint, hellgrau
        │                          │
        │   noch 9 min             │   fontHint, hellgrau; ab 60 s „noch 45 s“, orange
        │   Telefon in Reichweite? │   nur bei Verbindungsfehler, rot (Zeile ersetzt „noch …“ nicht)
        │                          │
        └──────────────────────────┘
```

- Kein Bedienhinweis für BACK auf der Seite: Die Zurück-Taste ist auf allen
  Zielgeräten dieselbe Handlung (`Input.mc` 16), das Handbuch sagt es.
- Der Pfeil „→“ ist in den Geräteschriften vorhanden? **Nicht belegt**
  (Abschnitt 11); Rückfall „Einstellungen, Geräte“. Der Prüfstand zeigt es.
- Freigabe am Simulatorbild (F-S5-06), auf allen drei Zielgeräten.

### 6.4 Uhr — Einstellungen (`properties.xml`, `settings.xml`)

`serverUrl` bekommt den Vorgabewert `nadoku.gen-em.org`. Der Kommentar
ersetzt „Bewusst ohne Vorgabewert: Jede Installation hat ihren eigenen
Server“ durch: *Vorgabe ist die öffentliche Installation (R36, E-R49-8);
Selbsthoster tragen hier ihre eigene Domain ein. Die Domain genügt — Schema
und `/ingest.php` ergänzt die App.* Titel in `settings.xml`: „Server-Adresse
(Vorgabe: nadoku.gen-em.org; Selbsthoster tragen ihre Domain ein)“.
`deviceId`/`apiKey` bleiben als Alt-Weg für die manuelle Anlage (E-R49-7).

**Prüfstand-Falle:** Der Simulator behält alte `SETTINGS/*.SET`; vor jedem
Lauf mit geänderter Vorgabe `pruefstand.sh einstellungen-leeren`
(`tools/uhr-pruefstand/LIESMICH.md`, „Der Simulator merkt sich zwei Dinge“).

### 6.5 Web — Geräteseite (`einstellungen.php?t=geraete`)

Karte „Gerät koppeln“ in **drei Zuständen**, alles mit vorhandenen
Bausteinen (E-S5-26):

**Zustand 1 — Eingabe** (Regelfall):

> Starte die Kopplung auf dem Gerät: **Sync-Seite → Gerät koppeln**. Das
> Gerät zeigt einen Code aus sechs Zeichen. Gib ihn hier ein; danach fragt
> das Gerät, ob es sich mit deinem Konto verbinden soll — bestätige dort mit
> Ja. Der Code ist 10 Minuten gültig.
>
> *(Kleinzeile, wie heute)* Auf Garmin-Uhren: die Sync-Seite erreichst du
> vom Startbildschirm mit DOWN, das Koppeln startet mit gedrückt gehaltener
> START-Taste. Die Tastenwege der einzelnen Uhren stehen im Handbuch,
> Abschnitt 2.0.
>
> `ui_feld` **„Code vom Gerät“**, Platzhalter „AB3 K7Q“, `maxlength="8"`,
> `autocomplete="off"`, `autocapitalize="characters"`. Knopf **„Weiter“**
> (primär) im `.listen-form-fuss`. Aktion `koppeln_pruefen`.

Bei erreichtem Gerätelimit steht statt Feld und Knopf die heutige Meldung
(`einstellungen.php` 278–280), umformuliert: „Es sind bereits 5 Geräte mit
diesem Konto verbunden. Bitte zuerst ein nicht mehr genutztes Gerät löschen —
dann lässt sich wieder ein Gerät koppeln.“

**Zustand 2 — Bestätigung** (nach `koppeln_pruefen` mit Treffer): Titel der
Karte „Dieses Gerät koppeln?“; `ui_zeile` mit Text `geraet_bezeichnung(…)`
(z. B. „Uhr · Venu 3S“, bei fehlendem Block „Gerät unbekannt“) und Kleinzeile
„Code AB3 K7Q · gültig bis 14:32 Uhr · Kennung dev-3f9a…c1“
(`geraet_kennung_kurz`). Darunter:

> Wenn das dein Gerät ist und der Code stimmt, verbinde es. Das Gerät fragt
> dich danach noch einmal — erst dein Ja dort schließt die Kopplung ab.
> Kommt dir das Gerät unbekannt vor: abbrechen. Dann geschieht nichts.

Knöpfe: **„Mit meinem Konto verbinden“** (primär, Aktion
`koppeln_bestaetigen`, `hidden code`) und **„Abbrechen“** (leise, Link auf
`?t=geraete`). Bei fehlendem `geraet`-Block zusätzlich `ui_meldung` (warn):
„Das Gerät hat keine Angaben über sich gemacht. Das ist bei älteren Uhr-Apps
so; sei sicher, dass es deines ist.“

**Zustand 3 — Meldungen** über `ui_meldung` oben auf der Seite (`$notice` /
`$error`, wie heute):

| Fall | Ton | Text |
|---|---|---|
| Beanspruchung gelungen | info | Der Code ist deinem Konto zugeordnet. **Bestätige jetzt am Gerät mit Ja.** Danach erscheint das Gerät hier in der Liste — lade die Seite neu. |
| Code nicht gefunden (unbekannt, abgelaufen, schon beansprucht) | fehler | Diesen Code kennt der Server nicht — er ist falsch, abgelaufen oder schon verwendet. Auf dem Gerät einen neuen Code holen: Sync-Seite → Gerät koppeln. |
| Code passt nicht zum Muster | fehler | Ein Code hat sechs Zeichen; 0, O, 1 und I kommen darin nicht vor. Bitte vergleiche mit der Anzeige auf dem Gerät. |
| zu viele Versuche | fehler | Zu viele falsche Codes. Bis HH:MM Uhr nimmt der Server keine Eingabe von dir an. *(Zeit aus `rate_gesperrt_bis('pair_code', …)`)* |
| Gerätelimit beim Beanspruchen | fehler | *(wie Zustand 1)* |

Die Formularaktionen `pair_code` und der Codeblock „Kopplungscode“ entfallen.
Der Kommentar in `einstellungen.php` 3072–3075 („die eine Haupthandlung
dieses Reiters bleibt ‚Kopplungscode erzeugen‘“) wird zu „bleibt ‚Weiter‘ am
Feld ‚Code vom Gerät‘“ (B-S5-07). „Gerät von Hand anlegen“ bleibt
unverändert (E-R49-7).

### 6.6 Handbuch — Abschnitt 12, neuer Text

> ## 12. Ein neues Gerät einrichten (Kurzanleitung)
>
> Die Schritte gelten für jede Uhr und für die Handy-App. Wo die Plattform
> eigene Wege hat, steht der Zusatz kursiv darunter — bei Garmin die
> folgenden.
>
> 1. **App auf das Gerät laden** (siehe `Technik.md`). *Bei Garmin: aus dem
>    Connect-IQ-Projekt gebaut und per USB übertragen; Abschnitt 5 der
>    Technik-Doku. Die Uhr-App heißt „NAdoku“.*
> 2. **Server-Adresse:** Im Regelfall nichts zu tun — die App kennt
>    `nadoku.gen-em.org`. *Bei Garmin: Wer eine eigene Installation
>    betreibt, trägt deren Domain in Garmin Connect unter den
>    App-Einstellungen ein; die Domain genügt.*
> 3. **Auf dem Gerät die Kopplung starten:** Sync-Seite → **Gerät koppeln**.
>    Das Gerät holt sich einen Code und zeigt ihn groß an, zum Beispiel
>    „AB3 K7Q“. Er ist 10 Minuten gültig. *Bei Garmin: der Tastenweg je Uhr
>    steht in Abschnitt 2.0.*
> 4. **Im Web:** **Einstellungen → Geräte → „Code vom Gerät“** eingeben,
>    **Weiter**. Die Seite zeigt Art und Modell des Geräts. Ist es deines:
>    **Mit meinem Konto verbinden**.
> 5. **Zurück am Gerät:** Es fragt „Mit ph***@… koppeln?“ — deine
>    E-Mail-Adresse, teilweise verdeckt. **Ja** schließt die Kopplung ab; das
>    Gerät meldet „Gekoppelt“ mit einem Haken, im Web erscheint es in der
>    Geräteliste — mit Art und Modell —, und du bekommst eine E-Mail.
>    **Nein** oder Warten bis zum Ablauf lässt alles, wie es war.
> 6. **Alternative ohne Code:** Gerät im Web von Hand anlegen und Geräte-ID
>    sowie API-Schlüssel in die Einstellungen der Uhr-App eintragen (nur
>    nötig, wenn die Kopplung nicht möglich ist). *Bei Garmin: in Garmin
>    Connect.*
>
> **Zwei Dinge, die bewusst so sind.** Der Code weist niemanden aus — wer
> ihn abliest, kann damit nichts anfangen, solange du ihn nicht in dein
> Konto eingibst. Und das letzte Wort hat das Gerät: Gibt jemand anderes
> deinen Code in **sein** Konto ein, siehst du auf dem Gerät eine fremde
> Adresse und sagst Nein.
>
> **Wenn die Kopplung nicht klappt**, sagt das Gerät in zwei Zeilen, woran
> es liegt und was hilft:
>
> | Meldung | Was zu tun ist |
> |---|---|
> | „Code abgelaufen“ | Neu starten: Sync-Seite → Gerät koppeln. Der Code gilt 10 Minuten. |
> | „Zu viele Geräte“ | Im Web ein nicht mehr genutztes Gerät löschen, dann neu starten. |
> | „Zu viele Versuche“ / „Server ausgelastet“ | Kurz warten, dann neu starten. |
> | „Telefon in Reichweite?“ | Bluetooth an, Telefon in der Nähe. Der Code bleibt gültig; das Gerät fragt weiter nach. |
>
> **Ältere Uhr-App:** Eine Uhr mit NAdoku 2.0.0 oder älter meldet
> „Kopplung fehlgeschlagen (400) / Uhr-App aktualisieren“. Sie braucht die
> aktuelle Fassung; bestehende Kopplungen sind davon nicht betroffen.

**12.1** bleibt inhaltlich (Trennen vor Neukopplung); Schritt 3 dort wird zu
„Danach wie oben ab Schritt 3.“ **2.2** („mit START gedrückt halten startest
du hier die Geräte-Kopplung“) bleibt; der Absatz zu „Erst Server-Adresse
setzen“ bekommt den Satz „Seit Uhr ‹Fassung› ist `nadoku.gen-em.org`
voreingestellt; die Meldung erscheint nur, wenn jemand den Wert geleert
hat.“ **2.0** unverändert.

### 6.7 Kopplungsmail

Text wie heute (`pair.php` 340–356) mit einem Satz mehr nach „neues Gerät
gekoppelt“: „Das Gerät hat den Code gezeigt, du hast ihn im Web eingegeben
und am Gerät mit Ja bestätigt.“ Betreff unverändert.

---

## 7. Android-Seite — beschreibend (der S4-Rest baut sie)

Die Android-Handy-App setzt Abschnitt 1a **wörtlich** um; ihr Bedienweg ist
derselbe wie der der Uhr, ohne Adresswahl (R63). **Nichts davon läuft über
die Wear-OS-App** (`CLAUDE.md` 4: die Uhr kennt keine Zugangsdaten).

| Schritt | Handy-App |
|---|---|
| Adresse | Build-Konstante `nadoku.gen-em.org` an einer Stelle (Backlog 84); `Serveradresse.kt` wird zur Konstante, Adressfeld und `QrInhalt.kt` entfallen ganz — mit R63 trägt der QR nicht mehr „nur die Adresse“, es gibt ihn nicht mehr (Konzept S4, Abschnitt 13, Zeile „Was der QR trägt“ ist überholt) |
| Start | Kopplungsseite mit einem Knopf „Kopplung starten“ → `start` mit der Handy-Form des `geraet`-Blocks (E-S4-28, unverändert) |
| Anzeige | Code in zwei Dreiergruppen, groß; darunter „Im Web unter Einstellungen → Geräte eingeben“ und die Restzeit aus `frist_s`/`rest_s`; Bedienhöhe 48 dp (R58) |
| Abfrage | `status` alle 5 s (Z-11) aus einer Coroutine der Ansicht — **kein** Vordergrunddienst, kein WorkManager: Die Person hält das Handy in der Hand, die Ansicht ist offen; verlässt sie die Ansicht, sendet die App `bestaetigen nein` (E-S5-23) |
| Rückbestätigung | Dialog „Mit ph***@… koppeln?“ mit Ja/Nein; Ja → `bestaetigen ja` → Zugangsdaten in den Android Keystore (E-R45-10, wie heute nach `koppeln()`); Nein → `bestaetigen nein` |
| Fehler | dieselben Fälle wie 6.2, Texte in `strings.xml` (Wortliste-Bereich d) |
| Idempotenz | nach Verbindungsabriss beim Ja: `status` → `gekoppelt` → speichern (E-S5-15) |

Was das am Bestand trifft, steht in Konzept S4, Abschnitt 13 (sechs
Quelldateien, rund 600 Zeilen, 39 von 220 Prüffällen); dieses Konzept
ändert daran: `QrInhalt.kt` und `QrInhaltTest` **entfallen** statt
umgebaut zu werden.

---

## 8. Preis, Bedrohungsmodell, R17

### 8.1 Preis

Nach dem Serverumstieg koppelt **keine ältere Uhr-Fassung** mehr (E-R49-7).
Bestand: **eine** gekoppelte Uhr — nach dem ursprünglichen Plan bliebe sie
gekoppelt, weil `ingest.php` und Abschnitt 1 unverändert sind. **Nachtrag
03.09.2026 (E-S5-42):** Ihr Schlüssel liegt als bcrypt-Hash, und `ingest.php`
vergleicht ab Paket A gegen SHA-256 — sie wird nach dem Deploy mit `401`
abgewiesen und **einmal neu gekoppelt**, nachdem ihr Sync vollständig ist.
Der Drahtvertrag bleibt gleich; nur der gespeicherte Hash passt nicht mehr. Die Reihenfolge der Auslieferung folgt daraus: **Server, Web und
Uhr in einem Push auf `main`** (K7), nicht der Server vorweg; der Uhr-Build
braucht DNS und TLS für `nadoku.gen-em.org` (Rahmenplan 6). Nach dem Deploy
`update.php` (Schemaänderung).

### 8.2 Nachtrag zum Bedrohungsmodell (E-R49-5)

Als neuer Unterabschnitt **`Technik.md` 4.99b „Bedrohungsmodell der
Kopplung“** — es gibt bislang keinen Abschnitt dieses Namens (B-S5-02); die
verstreuten Aussagen (Sicherheit, Antwortzeit als Auskunft, `MAX_GERAETE`,
Kopplungsmail) werden von dort verwiesen, nicht verschoben.

| Nr. | Angriff | Tor / Gegenmittel | Rest |
|---|---|---|---|
| 1 | **Fremdes Gerät im eigenen Konto** („gib mal Code X ein“) | Bestätigungsseite mit Art und Modell (E-S5-05a); Kopplungsmail bei Anlage; Hinweis „neu“ in der Liste sieben Tage | Social Engineering bleibt möglich; das Gerät ist danach löschbar, hochgeladene Daten kenntlich |
| 2 | **Eigenes Gerät im fremden Konto** (Code vom Handgelenk abgelesen, schneller eingegeben) | Rückbestätigung mit maskierter E-Mail (E-S5-05b); die falsche Adresse fällt auf | Wer die maskierte Adresse der Trägerin nachahmen kann (`ph***@gen-em.de` mit gleicher Domain), gewinnt — dagegen hilft nur die volle Adresse, und die will R36/E-R49-4 nicht auf der Uhr |
| 3 | **Code-Raum füllen und auf Vertipper hoffen** (E-R49-6) | Obergrenze Z-13 per SQL, `start` je IP Z-12, Code-Eingabe je Konto und IP Z-08 | ≤ 9,3 · 10⁻⁷ je Versuch; ein Treffer läuft in Angriff 2 |
| 4 | **Verstopfen** (Obergrenze mit eigenen Sitzungen erreichen) | Z-12: 50 Adressen je 10 Minuten; Sperre je Adresse 10 Minuten; die Obergrenze zählt nur unverfallene Sitzungen (E-S5-14) | Ein großer Adressvorrat verhindert für die Dauer des Angriffs Neukopplungen — **nicht** den Betrieb gekoppelter Geräte |
| 5 | **Rechenlast** (`start` erzeugt einen bcrypt-Hash; `status` prüft einen) | Z-12 und Topf `pair` für Fehlversuche; unbekannte Kennung läuft gegen `AUTH_VERGLEICHSWERT` wie `ingest.php` | wie heute bei `ingest.php` |
| 6 | **Schwebende Zugangsdaten** (Klartextschlüssel geht bei `start` an ein Gerät, das nie bestätigt wird) | ohne `devices`-Zeile lehnt `ingest.php` ab (`401`); Sitzung verfällt nach zehn Minuten; Hash nur | keiner — die Daten sind ohne Bestätigung wertlos, und die Bestätigung braucht das Konto |
| 7 | **Ablesen der maskierten Adresse** durch einen Gerätehalter, der jemanden zur Eingabe bewegt hat | er kennt die Person ohnehin (Angriff 1) | zwei Zeichen und Domain — kein neuer Personenbezug über Angriff 1 hinaus |
| 8 | **Zeitseitenkanal** an `status`/`bestaetigen` | E-S5-31 | wie an `trennen` heute |
| 9 | **CSRF auf den Web-Aktionen** | `csrf_field()` wie jede Formularaktion | — |
| 10 | **Verlorene Antwort auf `bestaetigen`** | Idempotenz E-S5-15 | — |

### 8.3 Was der R17-Review in P6 mitprüft

Das Protokoll ist ein bekanntes Muster (Device-Code-Flow), kein Fable-Schritt
(R49). Der Review prüft: die Transaktion in `bestaetigen` (Anlage und
Löschung atomar, `FOR UPDATE`), die drei `rowCount()`-Stellen (E-S5-13), die
Antwortgleichheit der neuen Zweige (Länge, Zeichenvorrat, Aufbau, Dauer),
dass `api_key` nirgends protokolliert wird (Fehlerprotokoll, `error_log` in
den `catch`-Zweigen), die SQL-Zählung der Obergrenze unter Last, die
Maskierungsfunktion gegen Sonderfälle (lokaler Teil aus einem Zeichen, IDN),
den Adress-Ersatz `nadoku.gen-em.org` (nirgends mehr `beispieldomain` in
Uhr und Handbuch 12), und — aus E-R45-10 übernommen — die Ablage des
Schlüssels auf dem Handy.

---

## 9. Arbeitspakete A–D, Reihenfolge, Abnahme

**Reihenfolge:** A → (B ‖ C) → D. B und C brauchen A (C für den
Simulator-Rundlauf); B und C berühren keine gemeinsame Datei (`server/`
gegen `watch/`), die Buchführung (`version.php`, `CHANGELOG.md`) ist
mechanisch. **Ein Push auf `main` am Ende**, nach ausdrücklicher
Bestätigung (K7), Uhr-Build nach DNS/TLS. Nach jedem Paket: Statusblock,
Abschnitt 9 und 10 fortschreiben, Prüfdokument ergänzen, Zweig pushen.
Prüfmittel **zuletzt** (`CLAUDE.md` 6). Kein Fable-Schritt.

Sperren (Rahmenplan 4): S5-Umsetzung nicht parallel zu S6 (erledigt) und S7
(`einstellungen.php`, Handbuch, Technik — S7 läuft während Schritt 3, nicht
5); der S4-Rest wartet auf A.

### Paket A — Server

| | |
|---|---|
| **Dateien** | `server/schema.sql` (Tabelle `pair_sessions`, `pair_codes` raus, Register), `server/update.php` (Migration `‹Datum›_kopplungssitzungen`: Tabelle anlegen, `pair_codes` fallen lassen, `zerstoert`-Angabe; `web` trägt die Umsetzung ein), `server/db.php` (`PAIR_SITZUNGEN_MAX`, `email_maskieren()`; `PAIR_*` bleiben), `server/ratelimit_lib.php` (Töpfe `pair_start`, `pair_code`), `server/pair.php` (vier Anliegen; der Code-Zweig entfällt; Kopfkommentar neu), `server/jobs_lib.php` (Schritt „Kopplungssitzungen“, Beschreibung im Katalog), `tools/kopplungsprobe/` (neu, nach dem Muster `tools/ingestprobe/`: echtes HTTP gegen `php -S`, eigenes Konto per SQL, `finally`-Aufräumen, Jobs angehalten) |
| **Tabelle** | `pair_sessions (id AI PK · code VARCHAR(8) NOT NULL UNIQUE · device_id VARCHAR(64) NOT NULL UNIQUE · api_key_hash VARCHAR(255) NOT NULL · geraet_art VARCHAR(16) NULL · geraet_modell VARCHAR(191) NULL · geraet_teil VARCHAR(64) NULL · user_id INT UNSIGNED NULL FK users ON DELETE CASCADE · beansprucht_am DATETIME NULL · erstellt_am TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP · INDEX (erstellt_am) · INDEX (user_id))`. Keine IP, kein Label, kein Endzustand (R36; E-S5-11) |
| **`start`** | Ratenschutz `pair_start` **vor** allem (`rate_erlaubt`, dann `rate_zaehlen`); Obergrenze per SQL; Block lesen (`geraet_block_lesen`, try/catch wie heute); Kennung, Schlüssel, Hash; Code ziehen mit fünf Versuchen gegen `ist_dublettenfehler` (Muster `einstellungen.php` 292–315); `INSERT`; Antwort mit `frist_s` = `PAIR_TTL_MIN * 60` |
| **`status`/`bestaetigen`** | Topf `pair` vor allem; Kennung erst in `pair_sessions`, dann in `devices` suchen; unbekannt → `AUTH_VERGLEICHSWERT` + `abweisen(401)`; Frist prüfen (`410`, ohne Zählung); Zustände nach 1a; `ja` als Transaktion (E-S5-13); Mail nach `antwort_abschliessen()` |
| **Abnahme** | Kopplungsprobe: **alle Erwartungen erfüllt**, Zahl von der Probe gemeldet, mindestens die Fälle aus Abschnitt 10.2 (≥ 34) · `tools/geraeteprobe/` unverändert 39 · Migration auf einer frischen Installation (`install.php`) **und** auf einer mit `pair_codes` gelaufen, Register gegengezählt (41 = 41) · Antwortgleichheit: `status` mit unbekannter Kennung und mit falschem Schlüssel — gleiche Rümpfe, beide ≥ 0,35 s · `trennen` unverändert (Probe) |

### Paket B — Web

| | |
|---|---|
| **Dateien** | `server/einstellungen.php` (Geräteabschnitt: Aktionen `koppeln_pruefen`, `koppeln_bestaetigen`; `pair_code` und Codeblock raus; drei Zustände nach 6.5), `server/assets/style.css` **nur, wenn ein vorhandener Baustein nicht reicht** — Erwartung: keine Änderung; `tools/screenshots/seiten.json` (Seite 33 in den drei Zuständen, falls die Aufnahme einen Zustand nicht von selbst erreicht) |
| **Abnahme** | Bilderlauf Seite 33 in acht Breiten × drei Zuständen = **24 Bilder, 0 Überlauf, 0 Konsolenfehler, Knopfhöhe 44 px**; `tools/vollstaendigkeit/` unverändert; Stilvergleich nur, wenn `style.css` berührt wurde (Erwartung: nicht); Browserprüfung: Rundlauf mit der Kopplungsprobe als Gerät (sie kann `start` senden und `status` lesen) — Feld → Bestätigung → Meldung → Gerät in der Liste nach Neuladen; Fehlermeldungen alle fünf gesehen; Wortliste Bereich a und c 0/0/0 |

### Paket C — Uhr

| | |
|---|---|
| **Dateien** | `watch/source/Pair.mc` (Ablauf neu: `start`, Abfrage, `bestaetigen`; `TextPicker` und `PairTextDelegate` raus; Kopfkommentar neu, B-S5-05), `watch/source/PairView.mc` (neu, 6.3), `watch/source/SyncView.mc` (Texte 6.2), `watch/source/Uploader.mc` (Kommentar 216), `watch/resources/settings/properties.xml` und `settings.xml` (6.4), `watch/source/Const.mc` (Fassung — Umsetzung), `watch/resources-marke*/` (S3-Kacheln: **prüfen, ob die neu gerasterten Dateien bereits im Repositorium liegen** — E-S3-04 sagt ja; sonst `tools/uhr-bilder/erzeugen.sh`), `tools/wortliste/wortliste.py` und `ausnahmen.json` (Bereich `e`, E-S5-30), `tools/uhr-pruefstand/` nur bei Bedarf |
| **Abnahme** | Stufe I `reihe` über **alle 99** Zielgeräte mit `-l 3 -w`: 99 übersetzt, 0 fehlgeschlagen, **0 Warnungen, 0 Fehler** (Stand R47) · Stufe II `bildreihe` über die Vertreter aus `geraeteklassen.py`: 0 Abstürze, je Vertreter ein Bild der `PairView` · Simulator-Rundlauf gegen lokalen Server (F-S5-11) auf **fenix6pro, fr945, venu3s**: Rundlauf Ja, Nein, BACK, Fristablauf, Gerätelimit, Verbindungsabriss = **6 Fälle × 3 Geräte = 18 Mitschnitte/Bilder** · `einstellungen-leeren`, `speicher-leeren` vor jedem Lauf, dokumentiert · Sync-Seite frisch: „Nicht eingerichtet / START halten: Gerät koppeln“ (nicht „Erst Server-Adresse setzen“ — das belegt den Vorgabewert) · Wortliste Bereich e **0/0/0**, mit Nennung der geprüften Dateizahl · Freigabe der `PairView` am Simulatorbild (F-S5-06) **vor** dem Commit |

### Paket D — Doku und Abschluss

| | |
|---|---|
| **Dateien** | `docs/JSON-Vertrag.md` (1a aus Abschnitt 4, 0 und 1b nachziehen), `docs/Handbuch.md` (12, 12.1, 2.2), `docs/Technik.md` (2 Verzeichnisstruktur, 3 Datenmodell, 4.97a Katalogzeile, 4.99 Sicherheit + **4.99b** Bedrohungsmodell, 5 Uhr-Module, 7 Runbook „Kopplung klappt nicht“), `docs/Geraete-Eingabe.md` (kein neuer Tastenweg — prüfen, ob ein Verweis reicht), `docs/CHANGELOG.md` (Web und Uhr, erklärende Prosa), `docs/Backlog.md` (66 nach Erledigt; Kandidaten aus diesem Konzept aufnehmen; Nummern beim Merge), `CLAUDE.md` 6 (der Satz „`watch/` fehlt noch“ wird ausgetragen), `docs/Rahmenplan.md` (Abschnitt 3 Schritt 5 während der Arbeit; nach Freigabe R62-Abschluss), Prüfdokument S5 (K9) |
| **Abnahme** | Konsistenzlesung: jede Nennung von „Kopplungscode erzeugen“, `pair_codes`, „Code eintippen“, `nadoku.beispieldomain.de` in `docs/` und `server/` ist gefunden und entweder ersetzt oder mit Grund belassen (Changelog, Archiv, erledigte Konzepte) — Zahl der Fundstellen vorher/nachher · Wortliste a, c, e 0/0/0 · Kreisläufe R24 beide 0 unerklärt · P2-Prüfpunkt 4.1 im Prüfdokument S5 neu formuliert: „eine Kopplung mit der Uhr in der Hand, nach Handbuch 12 von oben nach unten, Erwartung: jeder Schritt ohne Zusatzwissen, Bezeichnungen stimmen mit der Uhr überein; Scheitern: ein Text auf der Uhr weicht vom Handbuch ab, besonders auf der Venu 3s“ |

### Umsetzungsstand (wird je Paket fortgeschrieben)

| Paket | Stand | Probleme / Lösungen | Entscheidungen |
|---|---|---|---|
| A | **erledigt** 03.09.2026 — Web **13.0.0** (Hauptnummer: neuer Weg **und** neues Verfahren, mit Migration). Dateien: `schema.sql`, `update.php`, `db.php`, `ratelimit_lib.php`, `pair.php` (neu geschrieben), **`kopplung_lib.php` (neu)**, `jobs_lib.php`, `demo_lib.php`, `ingest.php`, `einstellungen.php` 264, vier virtuelle Geräte, `tools/ingestprobe/`, **`tools/kopplungsprobe/` (neu)**, `version.php`, `CHANGELOG.md`, `Technik.md` (sechs Stellen), `Rahmenplan.md` Schritte 3 und 5 | **Nichts Unerwartetes im Code.** Zwei Stellen des Konzepts waren unterbestimmt und sind entschieden (E-S5-48, -49). `beansprucht_am` ist nicht da (E-S5-44). Die Probe fand beim ersten Lauf 75/75 — die eine Überraschung war organisatorisch: MariaDB war im Container gestorben (`aufbau.sh datenbank`). **Der Text der Kopplungsmail ist nicht maschinell belegt** (kein Mailserver), nur der Versandweg nach der Antwort — Prüfdokument | E-S5-48 bis -52 |
| B | offen | — | — |
| C | offen | — | — |
| D | offen | — | — |

---

## 10. Prüfprotokoll-Soll

### 10.1 Die Zahlen, die am Ende stehen müssen

| Prüfmittel | Misst | Soll |
|---|---|---|
| `tools/kopplungsprobe/probe.php` (neu) | Endpunkt über echtes HTTP, alle vier Anliegen, Ratenschutz, Obergrenze, Migration | **alle** Erwartungen erfüllt; ≥ 34 Fälle (10.2), Zahl gemeldet — **A: 75 Erwartungen, 0 nicht erfüllt, 0 übergangen** (03.09.2026, gegen Bestand mit gefahrener Migration und gegen frische Installation) |
| `tools/geraeteprobe/probe.php` | Blocklesen unverändert | 39 / 39 — **A: 39 / 39** |
| `tools/uhr-pruefstand/pruefstand.sh reihe` | Übersetzen, strenge Typprüfung | 99 übersetzt, 0 fehlgeschlagen, 0 Warnungen, 0 Fehler |
| `pruefstand.sh bildreihe` | Zeichnen auf Vertretern | 0 Abstürze; Bilder der `PairView` je Vertreter |
| Simulator-Rundlauf | sechs Fälle auf drei Geräten | 18 / 18 (oder: welche davon aus welchem Grund nicht, an erster Stelle des Prüfdokuments) |
| `tools/screenshots/` | Seite 33, acht Breiten, drei Zustände | 24 Bilder, 0 Überlauf, 0 Konsolenfehler, Knöpfe 44 px |
| `tools/vollstaendigkeit/` | Stylesheet | unverändert |
| `tools/wortliste/` | Bereiche a, c, **e** (neu, XML und `.mc`) | 0 / 0 / 0 je Bereich, Dateizahl genannt — **A: Bereiche a–d 0 / 0 / 0 (77 Ausnahmen, 77 gegriffen), zuletzt gefahren** |
| `tools/referenzdatensatz/vergleich/kreislauf.py` (csv, edbak) | Regressionspflicht R24 | 0 unerklärte Abweichungen, beide |
| Gerätetest | eine Kopplung mit der Uhr in der Hand (P2-Punkt 4.1, R55) | Rundlauf Ja; zusätzlich Nein und Fristablauf, wenn Zeit ist |

### 10.2 Fälle der Kopplungsprobe (Mindestliste)

1. `start` ohne Block → 200, Code nach `PAIR_RE`, `device_id` 36 Zeichen, `api_key` 48, `frist_s` 600; Zeile in `pair_sessions` mit drei NULL-Werten.
2. `start` mit Uhr-Form → Art/Modell/Teil aufgelöst (Teilenummer aus `geraetemodelle.php`).
3. `start` mit Handy-Form → `handy`, „Google Pixel 8“.
4. `start` mit unsinnigem Block (Zahl, Zeichenkette) → 200, NULL-Werte.
5. Rumpf ohne `aktion` → 400 `aktion` + `meldung` (21 Zeichen).
6. Rumpf `{"code":"AB3K7Q"}` (alte Uhr) → 400 `aktion`.
7. `status` offen → `offen`, `rest_s` ≤ 600 und > 590.
8. Beanspruchen per SQL-Simulation des Web-Klicks (`UPDATE … rowCount`) → `status` `beansprucht`, `konto` maskiert nach E-S5-21.
9. Zweite Beanspruchung desselben Codes → `rowCount() === 0`.
10. `bestaetigen ja` im Zustand offen → 409 `nicht_beansprucht`, Sitzung bleibt.
11. `bestaetigen ja` beansprucht → 200; `devices`-Zeile mit Konto, Hash, Vorgabename nach Art, drei Gerätewerten; Sitzung weg.
12. `bestaetigen ja` wiederholt → 200 (Idempotenz).
13. `status` nach 11 → `gekoppelt`.
14. `ingest.php` mit schwebenden Zugangsdaten (vor 11) → 401; nach 11 → 200.
15. `bestaetigen nein` offen → 200, Sitzung weg.
16. `bestaetigen nein` beansprucht → 200, Sitzung weg, **kein** Gerät.
17. Frist: `erstellt_am` per SQL um 11 Minuten zurück → `status` 410, `bestaetigen` 410, Beanspruchen `rowCount() === 0`.
18. Gerätelimit beim Bestätigen: Konto mit fünf Geräten → 409 `device_limit`, Sitzung weg.
19. Unbekannte Kennung an `status` → 401, Dauer ≥ 0,35 s; falscher Schlüssel → 401, gleicher Rumpf, Dauer ≥ 0,35 s; beide zählen im Topf `pair`.
20. Topf `pair`: zehn 401 → elfter Aufruf 429, auch mit richtigen Daten.
21. Topf `pair_start`: 20 `start` → 21. Aufruf 429 `zu_viele_versuche`.
22. Obergrenze: `PAIR_SITZUNGEN_MAX` unverfallene Zeilen per SQL → `start` 429 `zu_viele_sitzungen`; 1000 **verfallene** Zeilen → `start` 200 (E-S5-14).
23. Topf `pair_code` (Web-Simulation): zehn Fehlgriffe → gesperrt; ein Treffer → `rate_erfolg` leert.
24. Formatfehler zählt nicht (E-S5-17): zwanzig Codes mit „0“ → nicht gesperrt.
25. Dublette beim Code-Ziehen (Zeile mit demselben Code vorab eingefügt, Zufall gepatcht) → zweiter Versuch, 200.
26. `trennen` unverändert: 200, Gerät weg (Regression zu R47).
27. Mail: Versand nach 11 protokolliert (SMTP-Attrappe oder Fehlerprotokoll), Text enthält Art und Modell.
28. Job `aufraeumen`: verfallene Sitzungen weg, unverfallene bleiben.
29. Migration auf frischer Installation: Register `skipped`; auf Installation mit `pair_codes`: Tabelle weg, `pair_sessions` da, Vorschau zeigt `zerstoert`.
30. `email_maskieren()`: `philipp@gen-em.org` → `ph***@gen-em.org`; `a@b.de` → `a***@b.de`; Großschreibung → klein.
31. `status`/`bestaetigen` ohne Kopfzeilen → 401.
32. `bestaetigen` ohne `antwort` oder mit `vielleicht` → 400 `payload`.
33. `start` GET → 405.
34. Kontolöschung mit beanspruchter Sitzung → Sitzung weg (FK CASCADE).

### 10.3 Nicht prüfbar aus dem Container — steht im Prüfdokument an erster Stelle

Siehe Abschnitt 11. Der Gerätetest schließt die Lücken, die der Simulator
lässt.

---

## 11. Was sich aus dem Repositorium nicht ermitteln ließ — und wie es die Umsetzung belegt

| Offen | Warum nicht ermittelbar | Beleg in der Umsetzung |
|---|---|---|
| Ob der Simulator `makeWebRequest` gegen `http://127.0.0.1` zulässt | kein Simulator im Konzept-Container; die Prüfstand-LIESMICH sagt nichts dazu | Paket C, erster Schritt; F-S5-11 |
| Ob `WatchUi.Confirmation` eine Zeichenkette mit 30+ Zeichen auf 240 px lesbar umbricht | Systemdialog, im Code nicht ansprechbar; heute längster Text 38 Zeichen (`ClockView.mc` 216) — nicht fotografiert (R47: „der Simulator beantwortet sie mit demselben Tastendruck“) | Bild aus dem Simulator; Gerätetest |
| Ob „→“ in den Geräteschriften vorhanden ist | Schriften kommen mit den Gerätedateien, die nicht im Repositorium liegen | `bildreihe`; Rückfall „Einstellungen, Geräte“ |
| Der lange Action-Druck auf echter Venu-Hardware | seit `Input.mc` 18–22 ungeprüft | Gerätetest (P2 4.1 nennt die Venu ausdrücklich) |
| Wie viele Bilder `bildreihe` liefert (Zahl der Vertreterklassen) | `geraeteklassen.py` braucht die Gerätedateien | die Umsetzung nennt die Zahl |
| Ob die S3-Kacheln (E-S3-04) schon in `watch/resources-marke*/` liegen oder erst zu erzeugen sind | E-S3-04 sagt „die Dateien liegen im Repositorium“; ob es die **neu gerasterten** sind, ist ohne `erzeugen.sh`-Lauf nicht zu sehen | `tools/uhr-bilder/erzeugen.sh` laufen lassen, Diff = 0 erwartet |
| Verhalten der einen Bestandsuhr nach dem Serverumstieg (bleibt gekoppelt) | folgt aus `ingest.php`, nicht gemessen | Gerätetest: ein Upload nach dem Deploy |
| Rundlaufzeit `Uhr → Telefon → Server` je `status` | keine Messung im Repositorium | Mitschnitt im Simulator (Konsolenzeiten); am Gerät gefühlt |

---

## 12. Fehlerfunde am Bestand (B-S5, K4 — sammeln, nicht nebenbei beheben)

| Nr. | Fund | Fundstelle | Vorschlag |
|---|---|---|---|
| **B-S5-01** | Die manuelle Geräteanlage vergibt weiterhin `dev-` + **4** Zufallsbytes; die Kopplung seit M4-08 16 (Geburtstagsproblem, `pair.php` 229–248). Auch `admin_users.php` 169 zieht 8 Bytes für ein Gerät | `server/einstellungen.php` 260; `server/admin_users.php` 169 | Backlog-Kandidat; in Paket A **nicht** mitmachen (fremde Datei, K4) — oder in B, weil `einstellungen.php` ohnehin offen ist: Entscheidung der Umsetzung, hier nur vermerkt |
| **B-S5-02** | `Technik.md` hat keinen Abschnitt „Bedrohungsmodell“, auf den R49 und S2 verweisen | `docs/Technik.md` 4.99; Rahmenplan-Archiv R49 | Paket D legt 4.99b an (Abschnitt 8.2) |
| **B-S5-03** | Backlog 66 beschränkt Bereich `e` auf `watch/resources/**/*.xml`; die sichtbaren Uhr-Texte stehen in `.mc` | `docs/Backlog.md` 697–709 | E-S5-30, F-S5-10 |
| **B-S5-04** | Der Topf `pair` schützt auch das Token von `jobs.php` — im Rahmenplan und im Kopf von `ratelimit_lib.php` (Zeile 52–55: „pair“) nicht genannt; wer den Topf „dreht“, dreht den Wartungsschutz mit | `docs/Technik.md` 2294; `server/jobs.php` | E-S5-16 lässt `pair` unverändert; Kommentar in `ratelimit_lib.php` ergänzen (Paket A) |
| **B-S5-05** | Kopfkommentar von `Pair.mc` ist veraltet: „UP halten“, „5 Zeichen“ — es sind START halten und sechs Zeichen | `watch/source/Pair.mc` 3–4 | Paket C schreibt den Kopf ohnehin neu |
| **B-S5-06** | Vertrag 1b sagt „429 gilt für beide Anliegen“ | `docs/JSON-Vertrag.md` 207 | Paket D: „für alle vier“ |
| **B-S5-07** | Kommentar „die eine Haupthandlung dieses Reiters bleibt ‚Kopplungscode erzeugen‘“ wird mit B falsch | `server/einstellungen.php` 3072–3075 | Paket B |
| **B-S5-08** | Vertrag 0, Zeile 46 (V-S5-07): „beschrieben, nicht umgesetzt“ steht seit S2 — nicht S5, nur beim Lesen wieder gesehen | `docs/JSON-Vertrag.md` 46 | keine Handlung in S5 |

---

## 13. Übergabe an die Umsetzung (Schritt 5)

1. F-S5-01 bis F-S5-11 entscheiden (Freigabe des Konzepts), Ergebnisse als
   E-S5-32 ff. hier eintragen.
2. Zweig `claude/s5-umsetzung-…` von `main`; dieses Dokument liegt unter
   `docs/konzepte/Konzept-S5-Kopplung-umgekehrt.md`; Prüfdokument
   `docs/konzepte/Pruefdokument-S5-Kopplung-umgekehrt.md` mit Paket A
   anlegen (Muster S3).
3. Paket A. Dann B und C. Dann D. Je Paket: Version hochstufen (Web bei A, B,
   D; Uhr bei C), Changelog, Doku, Backlog, Statusblock, Push.
4. Vor dem Push auf `main`: DNS und TLS für `nadoku.gen-em.org` bestätigt;
   Migrationsregister gegengezählt; Backlog-Nummern `uniq -d` leer;
   Bestätigung des Auftraggebers. Nach dem Deploy `update.php`.
5. Nach Freigabe des Abschlusses: R62-Schritte, Konzept löschen; das
   Prüfdokument bleibt bis zur abgehakten Prüfliste (darin P2-Punkt 4.1 und
   der Gerätetest).

### 12.1 Backlog-Kandidaten aus der Umsetzung (Nummern beim Merge, K2)

| Kandidat | Herkunft | Vorschlag |
|---|---|---|
| `AUTH_VERGLEICHSWERT` trägt Kostenfaktor 10, PHP 8.4 legt 12 an — 57 gegen 228 ms, verdeckt nur von der Mindestdauer 0,35 s | V-S5-13, Paket A | Vergleichswert auf den tatsächlichen Kostenfaktor ziehen, sobald keine Installation mehr auf PHP 8.3 läuft; oder `rate_gleiche_dauer()` an `login.php` auf 0,5 s |
| ~~`ingest.php` schreibt beim Upsert bedingungslos~~ | Gegenlesung B5.3 | **erledigt in Web 13.0.1**, nicht als Backlog-Punkt: nachgestellt (es gingen Ende, Strecke **und** Anstieg verloren, nicht nur das Ende), mit `COALESCE` an vier Stellen behoben, Ingestprobe Teil 7 hält es. Für Paket E2 bleibt die Reihenfolge beim Nachsenden trotzdem die richtige Zusage — der Server verzeiht sie jetzt nur |
| Kopfkommentar von `tools/uhr-bilder/erzeugen.sh` sagt „bitgleich“, die LIESMICH daneben „pixelgleich“ | V-S5-05 | ein Wort, oder `-define png:exclude-chunk=time` |
| Die manuelle Geräteanlage vergibt `dev-` + 4 Zufallsbytes, die Kopplung 16 | B-S5-01 (erste Hälfte) | in Paket B mitnehmen, weil `einstellungen.php` dort ohnehin offen ist — Entscheidung der Umsetzung |
| Die Rundlauffälle der Android-App lassen 9 Diensttage, 5 Einsätze und 14 439 Punkte im Admin-Konto zurück | Vorbereitung 8.2 | Aufräumen im `@After` oder eigenes Prüfkonto |
