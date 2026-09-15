# Prüfdokument P5a — Kette und Fundament

**Konzept:** `Konzept-P5a-Kette-und-Fundament.md` (15.09.2026, E-P5a-01 bis
-33, AP1 bis AP12). **Gemessen auf:** Zweig `claude/butte-umsetzen-5opi9u`.
**Stand dieses Dokuments:** 15.09.2026, nach AP4.

Das Prüfprotokoll im Konzept beantwortet „ist es belegt?". Dieses Dokument
beantwortet „was muss **ich** noch tun?" (`CLAUDE.md` 7, K9).

---

## 0. Was nicht geprüft werden konnte und warum

**Diese Liste steht am Anfang, nicht in einer Fußnote.**

| # | Was | Warum nicht | Wie es doch geprüft wird |
|---|---|---|---|
| N1 | **Die Arbeitsläufe selbst** (`pruefung.yml`, `auslieferung.yml`, `integritaet.yml`) | Ein GitHub-Arbeitslauf läuft nur bei GitHub. Der Container hat keine Actions-Umgebung, und ein `act`-Nachbau misst etwas anderes als das Original — vor allem bei Umgebungen mit Pflichtfreigabe. | Geprüft ist bisher nur, dass die drei Dateien **gültiges YAML** sind und die erwarteten Auslöser und Jobs tragen (Zahlen unten). Der erste echte Lauf ist Prüfpunkt **P1** unten. |
| N2 | **Die Pflichtfreigabe der Umgebung `produktion`** | Sie ist eine Einstellung des GitHub-Kontos und braucht das echte Konto der Betreiberin. | Prüfpunkt **P3** — Probe-Tag, Freigabe von Hand. |
| N3 | **Das Backup-Tor gegen eine echte Installation** | Es verlangt einen Produktivserver mit Job-Token und ein 10-GB-Backup. | Die **Selbstprobe** misst die Logik an fünf Lagen ohne Netz (5/5 erfüllt); der echte Lauf ist Prüfpunkt **P2**. |
| N4 | **Stufe 2** (Kreisläufe, Bilderlauf, Messstand gegen Staging) | Staging existiert noch nicht (Zuarbeit, Rahmenplan Abschnitt 6). | Prüfpunkt **P4**. |
| N5 | **Uhr Stufe I im Arbeitslauf** | `CIQ_GERAETE_URL` ist in dieser Arbeitsumgebung gesetzt, als GitHub-Secret aber noch nicht hinterlegt (Zuarbeit). | Der Schritt ist so gebaut, dass er **ausdrücklich überspringt und es sagt** — nie ein stilles Grün. Prüfpunkt **P5**. |
| N6 | **`./gradlew build` im Arbeitslauf** | Im Container liegt das SDK unter `/opt/android-sdk`; der GitHub-Läufer bringt ein eigenes mit. Ob die Pfade dort zusammenpassen, zeigt erst der Lauf. | Prüfpunkt **P1**. |
| N7 | **Die Weiche auf einem echten PHP 8.0/8.1** | Der Container hat PHP 8.4, und eine zweite Fassung daneben zu bauen misst etwas anderes als ein Hoster-PHP. | Nachgestellt: Die Untergrenze wurde in einer **Kopie** von `server/` auf 99.0 gesetzt und die Seite abgerufen — **HTTP 500, Titel „PHP zu alt", Text lesbar, 923 Byte**. Dazu zählt `tools/installweiche/` nach, dass die Datei PHP-7-lesbar geblieben ist. Prüfpunkt **P9**. |
| N9 | **Ob eine Kartenkachel wirklich ankommt** | Der Wegwerf-Container hat keine Egress-Freigabe zu den vier Kachelservern; die Anfragen enden in `net::ERR_ABORTED`. Ein fehlendes Bild sagt dann nichts über die Richtlinie. | Gemessen wird stattdessen, was die Richtlinie **tut**: Unter der scharfen CSP erzeugt jeder der vier Hosts **keine** Konsolenmeldung „Refused to load the image", ein nicht gelisteter Host (`kachel.invalid`) **eine**. Ohne diese Gegenprobe belegten die vier nichts. Prüfpunkt **P13**. |
| N10 | **HSTS und der HTTPS-Zwang im Echtbetrieb** | Die lokale Installation liegt hinter einem TLS-Weiterleiter (`socat`), der unverschlüsselt an PHP weiterreicht. `$_SERVER['HTTPS']` ist damit leer — richtigerweise setzt die Anwendung dort **kein** HSTS und leitet nicht um. | Die Logik ist einzeln geprüft (`kopf_hsts_tage()`, Stufen 0/1/7/365, Ablehnung anderer Werte). Der Echtlauf ist Prüfpunkt **P14**. |
| N11 | **Der Weg über die Reporting-API (`report-to`)** | Aus demselben Grund: `kopf_melde_url()` liefert ohne HTTPS bewusst nichts, also steht `report-to` lokal gar nicht in der Richtlinie. Gemessen ist deshalb nur der Weg über `report-uri` — der, den heute ohnehin jeder Browser nimmt. | Prüfpunkt **P15** auf einer echten HTTPS-Installation. Dass der Endpunkt **beide** Formate versteht, ist einzeln geprüft: `report-uri`-Rumpf (`{"csp-report":{…}}`) und Reporting-API-Liste (`[{"type":"csp-violation",…}]`) — beide **204**, beide als Zeile gespeichert, und in beiden Fällen ist der **Abfrageteil der Adresse weg** (`suche.php?q=geheim` → `suche.php`). |
| N12 | **Die zwei Wochen Report-Only** | Sie sind Betrieb, keine Abnahme — und sie messen genau das, was ein Prüflauf nicht sieht: was **Nutzerinnen** auf Wegen tun, die der Bilderlauf nicht geht. | Prüfpunkt **P16**; dort steht auch, woran man erkennt, dass man zu früh scharf geschaltet hat. |
| N13 | **Die Anwendung hinter einem echten Reverse Proxy** | Der Container hat keinen. | `netz_in_bereich()` ist gegen **18 Fälle** geprüft, alle richtig: IPv4 und IPv6, Einzeladresse und CIDR, die Ränder eines /24, `0.0.0.0/0`, ein IPv4 gegen einen IPv6-Bereich, eine unlesbare Adresse und zwei unmögliche Präfixlängen (`/33`, `/-1`) — die letzten vier müssen **false** ergeben und tun es. Der Echtlauf ist Prüfpunkt **P17**. |
| N8 | **Die Kontingent-Warnmail auf einem echten Mailserver** | Der Container hat keinen. | Die Logik ist mit abgesenkten Schwellen (50/53 %) durchgespielt: Beide Schwellen schlagen an, der Versand scheitert erwartungsgemäß und wird **nicht** als gemeldet vermerkt — also am nächsten Tag erneut versucht. Prüfpunkt **P10**. |

---

## 1. Was maschinell geprüft wurde — mit Mittel und Zahl

Alles unten auf dem Stand nach AP1 (Web 20.4.0), im Wegwerf-Container
(PHP 8.4.19, Python 3.11.15, Node 22.22.2, MariaDB 10.11.14).

| Mittel | Aufruf | Ergebnis |
|---|---|---|
| PHP-Syntax | `php -l` über `server/` und `tools/` | **0 Fehler** |
| YAML der drei Arbeitsläufe | `yaml.safe_load` | **3 von 3 gültig**; `pruefung.yml` → Auslöser `push`, `pull_request`, `workflow_dispatch`, Job `pruefung`; `auslieferung.yml` → `push`, `workflow_dispatch`, Jobs `staging`, `stufe2`, `produktion`; `integritaet.yml` → `schedule`, `workflow_run`, `workflow_dispatch`, Job `wache` |
| Migrationsregister | `php tools/migrationsregister/pruefen.php` | **46 Kennungen im Katalog, 46 in der Vorabliste, 30 Tabellen, 180 Spalten, 29 Löschungen, 0 Befunde, 4 erklärte Ausnahmen, 0 ungenutzte** |
| Migrationsregister, Selbstprobe | `… --selbstprobe` | **4 von 4** eingebauten Schäden gefunden (Prüfungen 1, 2, 4, 5) |
| Backup-Tor | `python3 tools/kette/tor.py --selbstprobe` | **5 von 5** Lagen erfüllt |
| Wortliste | `python3 tools/wortliste/wortliste.py` | **0 Treffer außerhalb der Ausnahmen (in 0 Zeilen), 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen**; 96 Regeln, 96 gegriffen |
| Kontraste | `python3 tools/screenshots/kontrast.py` | **22 Paare gerechnet, 0 verfehlt** |
| Vollständigkeit | `python3 tools/vollstaendigkeit/pruefen.py` | **340 Befunde** — und zwar **unverändert**: derselbe Lauf auf `origin/main` (`7f334cb`) in einem zweiten Arbeitsbaum meldet ebenfalls **340**. AP1 fasst `style.css` nicht an. |
| Vollständigkeit **nach AP4** | dasselbe | **365 Befunde** gegen **351** auf dem Stand vor AP4 (gemessen im selben Container per `git stash`). Die Bewegung ist vollständig erklärt: `style=`-Attribute **13 → 10** (die drei statischen sind aufgelöst), Unicode-Symbole **280 → 297** — **+17, alle in Kommentaren** neuer Dateien, je Datei nachgezählt. Sichtbares Markup hat kein Symbol dazubekommen |
| Backlog-Nummern | `grep -oE '^[0-9]+\.' docs/Backlog.md \| tr -d '.' \| sort -n \| uniq -d` | **leer** |
| Plattformprofil, Installation | `plattform_pruefen(db())` gegen die lokale Installation | **21 Befunde**; Muss offen **0**, Empfohlen offen **0**, nicht feststellbar **4** (freier Platz, HTTPS auf der Kommandozeile, und zwei davon abhängige) |
| Plattformprofil, Neuinstallation | dieselbe Funktion in einer Kopie von `server/` **ohne `config.php`** | **15 Befunde**, Muss offen **0** — die Einrichtung liefe durch |
| Die Weiche | `php tools/installweiche/pruefen.php` | `install.php` **623 Zeilen**, `php_mindest.php` **35 Zeilen**, 7 Token-Arten in der Sperrliste, **0 Befunde** |
| Die Weiche, Selbstprobe | `… --selbstprobe` | **8 von 8**; davon **4 Fälle, die NICHT anschlagen dürfen** (dieselben Wörter im Kommentar, `preg_match`, `match` als Zeichenkette, gewöhnlicher Rückgabetyp) |
| Kontingent-Warnung | `speicher_kontingente_melden()` mit Schwellen 50/53 % und `db_gb = 0,01` | beide Schwellen schlagen an; ohne Mailserver **2 Fehler**, Marke bleibt leer → wird wiederholt |
| Aufräumjob | `php jobs.php aufraeumen` | läuft durch, 8 Schritte, kein Fehler |
| Wartungsprobe **mit Teil 7** | `php tools/wartungsprobe/probe.php https://127.0.0.1:8443` | **67 Erwartungen, 0 nicht erfüllt** (vorher 57; Teil 7 bringt 10) |
| Torwächter im Browser | eigene Playwright-Probe, angemeldet als BetreiberIn | **12 von 12**: 503 auf der Startseite, Grund auf der Wartungsseite, JSON-503 für `ingest.php`, Betrieb → Updates offen mit Grund, „Wartung beenden" nach dem Lauf, danach wieder 200 · **0 Seitenfehler** |
| Bilderlauf `betrieb_updates.php` | acht Breiten | **8 Bilder · 0 Überlauf · 0 Konsolenfehler · 0 falsche Knopfhöhen** |
| **CSP-Probe, Quelltext** (AP4) | `php tools/cspprobe/pruefen.php` | **106 PHP-Dateien** (ohne `vendor/`), **108 `<script>`-Stellen**, **0 Befunde** über fünf Regeln; `style="…"` **0 in PHP**, **10 in `assets/*.js`** (erlaubt, E-P5a-32) |
| **CSP-Probe, Selbstprobe** | `… --selbstprobe` | **8 von 8**; davon **4 Fälle, die NICHT anschlagen dürfen** (ein Kommentar, der `<script>` nennt; `<script src>` auf eigene Dateien; `<script>` mit Nonce; `data-onload="1" name="onlineform"`) |
| **CSP-Probe, Browser** (AP4) | `node tools/cspprobe/browserprobe.mjs` | **33 von 33 Erwartungen**, **0 Seitenfehler**. Darunter: Nonce je Anfrage verschieden, 9 Seiten mit **0** Inline-Skripten ohne Nonce, JSON-Antwort mit `nosniff` und bewusst **ohne** CSP, Umschalten scharf ↔ Report-Only, vier Kachelanbieter erlaubt + ein nicht gelisteter gesperrt, eingeschleustes `<script src>` blockiert, Inline-Skript ohne Nonce blockiert (scharf) bzw. ausgeführt (Report-Only), `style="…"` wirkt weiter |
| **Gegenprobe des Meldewegs** | Teil derselben Probe | **4 absichtlich ausgelöste Verstöße → 4 Zeilen** in `csp_berichte`, richtig zusammengefasst (`inline` mit Zähler **2**). **Diese Zeile ist der Grund, warum die Zahl darunter etwas wert ist** — siehe den Kasten unten |
| **Bilderlauf, voll** (AP4) | `node tools/screenshots/aufnehmen.mjs` | **392 Einzelbilder, 49 Kontaktbögen · 0 Überlauf · 0 Konsolenfehler · 0 falsche Knopfhöhen** |
| **`netz_in_bereich()`** | 18 Fälle von Hand | **18 von 18** — IPv4/IPv6, Einzeladresse und CIDR, `/24`-Ränder, `0.0.0.0/0`, IPv4 gegen IPv6-Bereich, unlesbare Adresse, `/33` und `/-1` (die letzten vier müssen **false** ergeben) |
| **`kopf_hsts_tage_setzen()`** | 8 Werte | **8 von 8** — 0/1/7/365 angenommen, 2/−5/99999/30 **abgewiesen**; Vorgabe ohne Eintrag **1** |
| **`api/csp_bericht.php`, beide Formate** | Aufruf von Hand | `report-uri`-Rumpf und Reporting-API-Liste: je **204**, je eine Zeile; **Abfrageteil entfernt** (`suche.php?q=geheim` → `suche.php`). Unfug im Rumpf und ein `GET`: **204 ohne Zeile** |
| **CSP-Berichte nach dem Bilderlauf** | `SELECT COUNT(*) FROM csp_berichte` | **0** — beim **dritten** Lauf. Die beiden davor: siehe Kasten |
| **Wartungsprobe** (AP4-Stand) | `php tools/wartungsprobe/probe.php https://127.0.0.1:8443` | **67 Erwartungen, 0 nicht erfüllt** — darunter Erwartung 12a, die den Nonce-Fund gemacht hat |
| **Integritätswache, Selbstprobe** | `python3 tools/integritaetswache/wache.py --selbstprobe` | **30 Erwartungen, 0 nicht erfüllt** |
| **Integritätswache, Lauf** | `… https://127.0.0.1:8443 --unsicher` | **122 Dateien unter `assets/` gleich**, 1 Inline-Block gleich, 1 externes Skript gleich, 1 Formular gleich, 0 Ereignisattribute, 0 `javascript:`-Adressen — **kein Unterschied**. *Vor der Behebung: eine Abweichung, und zwar bei jedem Lauf* |

> ### Warum die „0 CSP-Berichte" dreimal gemessen wurde
>
> **Erster Lauf: 0 Berichte — und wertlos.** Die Richtlinie trug
> `report-to csp`, die Gruppe war nie auflösbar definiert, und Chromium
> **bevorzugt `report-to` und verwirft den Bericht dann ersatzlos**. Die
> Tabelle blieb leer, weil nichts ankam, nicht weil nichts zu melden war.
> Gefunden hat es die Gegenprobe: zwei absichtlich ausgelöste Verstöße
> standen in der Konsole und fehlten in der Tabelle.
>
> **Zweiter Lauf, nach der Behebung: 140 Verstöße** auf genau vier Seiten —
> `index.php` 65, `zeitraum.php` 31, `einsatz.php` 28, `tag_spuren.php` 16,
> alle mit der Quelle `data`. Das sind die vier Kartenseiten. Ursache: eine
> eingebaute Konstante in `leaflet.js` (ein 1×1 durchsichtiges GIF als
> `data:`), die in einer **minifizierten Bibliothek** steht und die eine
> Zählung im eigenen Quelltext deshalb nicht sehen kann. `img-src data:`
> war zuvor gestrichen worden, weil jene Zählung 0 ergab.
>
> **Dritter Lauf, mit `data:` in `img-src`: 0 Berichte** — und diesmal heißt
> das etwas.
>
> **Die Lehre für jedes künftige Paket:** Eine Prüfung, die nur nachsieht, ob
> *nichts* gemeldet wurde, kann „alles in Ordnung" nicht von „der Meldeweg
> ist kaputt" unterscheiden. Sie muss bei jedem Lauf einen Verstoß auslösen
> und ihn wiederfinden. In `browserprobe.mjs` steht das als fester Punkt.

**Was die Zahlen benennen** (`CLAUDE.md` 6): Die Wortliste hat sechs Bereiche
gemessen (Server-PHP, Skripte, Dokumentation, Android, `watch/`), nicht einen;
die Vollständigkeitszahl ist ein **Vergleich gegen den Ausgangsstand**, keine
absolute Güte — 340 ist der Altbestand aus P3, den AP4 und spätere Pakete
teilweise abbauen (13 `style=`-Attribute, davon **3 in PHP**: die drei, die
AP4 auflöst).

---

## 2. Was im Browser geprüft wurde

**Nach AP1: nichts, und das ist richtig.** AP1 ändert an der Oberfläche keine
Zeile. Die einzige Codeänderung ist der Parameter `aktion` in `jobs.php` —
ein JSON-Endpunkt ohne Oberfläche, der sich mit dem Token aufrufen lässt
(Prüfpunkt P6).

**Nach AP2**, gegen die lokale Installation (Chromium 141, angemeldet als
BetreiberIn):

| Was | Ergebnis |
|---|---|
| Bilderlauf `betrieb_status.php` und `betrieb_server.php`, acht Breiten von 360 bis 1920 px | **16 Bilder · 0 Überlauf · 0 Konsolenfehler · 0 Knöpfe falscher Höhe** (Zeiger, 44/36 px) |
| Karte „Plattform" **aufgeklappt** angesehen | 16 Zeilen, alle mit Messwert und Sollwert; 0 Konsolenfehler |
| Feld „Kontingent der Datenbank" neben „Webspace laut Hosting" | steht in derselben Reihe, Vorgabe **10** eingetragen |
| Ampel | Die Plattform-Karte trägt **0** zur Zählung oben bei — gemessen: 1 rot / 4 orange vor **und** nach ihrer Einführung, alle aus den anderen vier Karten |
| Weiche (nachgestellt, Kopie mit Untergrenze 99.0) | **HTTP 500**, Titel „PHP zu alt — Gen-EM NAdoku", Fließtext lesbar |

---

**Nach AP4**, gegen die lokale Installation (Chromium 141, angemeldet als
BetreiberIn):

| Was | Ergebnis |
|---|---|
| Kopfzeilen der **Anmeldeseite** ohne Sitzung gelesen | CSP-Report-Only vollständig, dazu `nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`; Abzug in `tools/cspprobe/kopfzeilen.png` |
| Nonce zweimal hintereinander | **verschieden** (`qE3YA2qE…` ≠ `iaDsZhxS…`) |
| 9 Seiten durchgegangen (Start, Zeitraum, Suche, Einstellungen, Betrieb ×2, Import, Nachbearbeitung, NutzerInnen) | jede mit CSP, **0** Inline-Skripte ohne wirksamen Nonce |
| Karte „Sicherheitskopfzeilen" auf Betrieb → Servereinstellungen | da; Schalter und Segment bedient, beide Richtungen wirken |
| Scharf geschaltet, dann Einschleuseversuche | fremdes `<script src>` **blockiert**, Inline-Skript ohne Nonce **blockiert**, `style="…"` wirkt weiter (E-P5a-32) |
| Import unter der scharfen Richtlinie | `import.php` lädt SheetJS und führt es aus (`window.XLSX` da) |
| Zurückgeschaltet auf Report-Only | wirkt; **Auslieferungszustand wiederhergestellt** |
| Voller Bilderlauf | 392 Bilder, **0 Konsolenfehler** |

**Was dabei auffiel und nicht auffallen sollte:** Die Zählung der Nonces
scheiterte zuerst an der Probe, nicht an der Anwendung — Browser **verstecken**
den Wert des `nonce`-Attributs nach dem Parsen, `getAttribute('nonce')` liefert
eine leere Zeichenkette. Der Wert steht nur in der Eigenschaft `.nonce`. Die
Probe fragt seither danach.

---

## 3. Die Prüfliste

Je Punkt: der Bedienweg, das erwartete Ergebnis, und **woran ein Scheitern zu
erkennen ist**.

### P1 — Stufe 1 läuft grün (nach dem ersten Push)

**Weg:** Den Arbeitszweig pushen, in GitHub → Actions den Lauf **Prüfung**
öffnen.
**Erwartet:** Alle Schritte grün. In der Zusammenfassung stehen Zahlen: „PHP-
Syntax: N Dateien, 0 Fehler", der Wortlisten-Auszug, das Migrationsregister
mit seinen sechs Zahlen.
**Scheitern erkennbar an:** einem roten Schritt — **oder** an einer
Zusammenfassung, in der „ÜBERSPRUNGEN" steht, wo es nicht stehen soll. Ein
grüner Lauf mit zwei übersprungenen Schritten ist kein grüner Lauf.

### P2 — Das Backup-Tor bricht gegen die echte Installation ab

**Weg:** Den Produktionslauf mit einem Tag starten, dessen `JOBS_TOKEN`
absichtlich falsch ist (Umgebungsgeheimnis kurz ändern).
**Erwartet:** Der Schritt „Backup-Tor" endet **rot**, mit der Zeile „Die
Installation antwortet mit 'token' … Es wird nicht ausgeliefert." **Der
FTPS-Schritt läuft gar nicht erst.**
**Scheitern erkennbar an:** einem Lauf, der trotzdem hochlädt — dann steht das
Tor offen. Danach das Geheimnis zurücksetzen und den Lauf wiederholen.

### P3 — Die Pflichtfreigabe hält an

**Weg:** Tag `web-v20.4.0` setzen (oder die dann gültige Fassung). In Actions
den Lauf **Auslieferung** öffnen.
**Erwartet:** Der Job `produktion` steht auf **„Waiting"** mit dem Hinweis auf
die nötige Freigabe. Erst nach dem Klick auf *Review deployments → Approve*
läuft er los.
**Scheitern erkennbar an:** einem Job, der ohne Rückfrage durchläuft — dann ist
in der Umgebung `produktion` kein Reviewer eingetragen.

### P4 — Stufe 2 misst gegen Staging

**Weg:** Nach der Einrichtung von Staging (Zuarbeit) einen Push auf `main`.
**Erwartet:** Job `stufe2` grün; Kreisläufe **0 unerklärt**, Bilderlauf
**0 Überlauf, 0 Konsolenfehler, 0 falsche Knopfhöhen**.
**Scheitern erkennbar an:** „ÜBERSPRUNGEN (kein Prüfkonto)" in der
Zusammenfassung — dann fehlen `STAGING_KONTO`/`STAGING_PASS`, und **gemessen
wurde nichts**.

### P5 — Uhr Stufe I im Lauf

**Weg:** `CIQ_GERAETE_URL` als Repositoriums-Secret hinterlegen, Push.
**Erwartet:** Der Schritt übersetzt die App für alle Zielgeräte und endet
grün.
**Scheitern erkennbar an:** der Warnung „CIQ_GERAETE_URL nicht gesetzt — Uhr
Stufe I ÜBERSPRUNGEN, nicht bestanden".

### P6 — Die vier Aktionen von `jobs.php` gegen eine echte Installation

**Weg:** Mit dem Job-Token aus Betrieb → Jobs:

```
python3 tools/kette/tor.py zustand     --basis https://… --token …
python3 tools/kette/tor.py wartung-an  --basis https://… --token …
python3 tools/kette/tor.py wartung-aus --basis https://… --token …
python3 tools/kette/tor.py backup      --basis https://… --token … --versuche 3 --pause 5
```

**Erwartet:** `zustand` nennt `version`, `wartung`, `komplett` und
`migration_ausstehend`. `wartung-an` legt `server/wartung.lock` an, und die
Startseite antwortet danach mit **503** — der Wartungsbalken auf
Betrieb → Updates sagt „von kette". `wartung-aus` räumt sie weg.
**Scheitern erkennbar an:** `{"ok": false}` mit Meldung — bei `wartung-an`
heißt das fast immer: die Anwendungswurzel ist nicht beschreibbar.

### P7 — Die Integritätswache hält nach einem Staging-Deploy still

**Weg:** Push auf `main` (Staging-Deploy). In Actions den Lauf
**Integritaetswache** öffnen.
**Erwartet:** Der erste Schritt meldet „Job 'produktion' in Lauf …: nicht
gelaufen", die beiden Arbeitsschritte sind übersprungen, in der
Zusammenfassung steht „Dieser Lauf ging nicht auf Produktiv — die Wache hält
still."
**Scheitern erkennbar an:** einer roten Wache nach einem Staging-Deploy. Dann
vergleicht sie Produktiv gegen einen Stand, der dort nicht liegt.

### P8 — Die Wache löst nach einem Produktiv-Deploy aus

**Weg:** Nach P3 denselben Lauf ansehen.
**Erwartet:** Die Wache läuft, Selbstprobe grün, Vergleich grün.
**Scheitern erkennbar an:** gar keinem Lauf — dann stimmt der Name in
`integritaet.yml` nicht mehr mit dem Anzeigenamen in `auslieferung.yml`
überein (Fund F4).

### P12 — Der Torwächter auf der echten Installation

**Weg:** Nach dem nächsten Deploy **mit** Migration nichts tun außer sich
anmelden.
**Erwartet:** Die Wartungsseite mit „Die Anwendung hat selbst geschlossen".
Auf Betrieb → Updates die Meldung „Vom Torwächter geschlossen", danach
„Ausstehende ausführen", dann der Knopf „Wartung beenden".
**Scheitern erkennbar an:** einer Startseite, die trotz ausstehender Migration
antwortet. Dann prüfen, ob `app_state` die Zeilen `migration_tor_hash` und
`migration_tor_offen` trägt — fehlen sie, ließ sich die Tabelle nicht
beschreiben, und der Torwächter fällt (absichtlich) auf „offen lassen"
zurück.

### P9 — Die Weiche auf einem echten alten PHP

**Weg:** Beim Hoster im Kundenmenü PHP auf **8.0 oder 8.1** stellen,
`install.php` aufrufen. (Auf einer Installation mit Betrieb **nicht**
machen — die Anwendung antwortet dann auf keiner Seite mehr.)
**Erwartet:** Die Seite „PHP ist zu alt" mit der gemessenen Fassung und dem
Grund.
**Scheitern erkennbar an:** einem Parse Error („syntax error, unexpected …")
statt der Seite. Dann ist in `install.php` oder `php_mindest.php` PHP-8-Syntax
gelandet, die `tools/installweiche/` nicht sieht — die vier Lücken stehen in
seiner LIESMICH.

### P10 — Die Kontingent-Warnmail geht wirklich hinaus

**Weg:** Unter Betrieb → Servereinstellungen das **Kontingent der Datenbank**
kurz auf einen Wert knapp über der aktuellen Größe setzen (die Zeile
„Datenbank" auf Status nennt sie), dann den Aufräumjob laufen lassen —
`php jobs.php aufraeumen` oder einen Tag warten.
**Erwartet:** Eine Mail „Datenbank: 70 % des Kontingents erreicht" an alle mit
Verwaltungsrecht, **einmal**. Ein zweiter Lauf schickt nichts mehr.
Danach das Kontingent zurücksetzen (leer = Vorgabe).
**Scheitern erkennbar an:** gar keiner Mail (dann prüfen, ob „Letzter Versand"
auf Status rot steht) oder an einer Mail bei **jedem** Lauf — dann wird die
Marke `speicher_schwellen_gemeldet` nicht geschrieben.

### P11 — Die Warnung der Speichergrenze, die es nie gab

**Weg:** Speichergrenze kurz so weit senken, dass die vorhandenen Backups über
70 % liegen, dann den Aufräumjob laufen lassen.
**Erwartet:** Eine Mail „Backups: 70 % der Speichergrenze erreicht".
**Warum dieser Punkt hier steht:** Diese Mail ist seit S8 geschrieben und ist
**nie** hinausgegangen — der Aufruf fehlte. Dass sie jetzt im Aufräumjob steht,
ist nachgelesen; dass sie ankommt, ist es noch nicht.

---

### P13 — Die vier Kartenanbieter auf einer Installation mit Netz

**Weg:** Betrieb → Servereinstellungen → **CSP scharf schalten**. Dann auf der
Tagesübersicht die Karte öffnen und alle **vier** Grundkarten durchschalten
(Standard, Wanderkarte, Topografie, Luftbild).
**Erwartet:** Alle vier zeigen Kacheln. Die Konsole bleibt leer.
**Woran ein Scheitern zu erkennen ist:** Die Karte bleibt **grau** und in der
Konsole steht „Refused to load the image … because it violates … `img-src`".
Dann fehlt eine Domain in `KOPF_KACHELN` — genau der Fall, vor dem Backlog
Nr. 181 warnt. Sofort zurück auf Report-Only schalten; die Adresse steht in
der Meldung.
**Warum dieser Punkt hier steht:** Der Wegwerf-Container erreicht die
Kachelserver nicht (N9). Geprüft ist, dass die **Richtlinie** sie durchlässt —
nicht, dass sie antworten.

---

### P14 — HSTS auf einer echten HTTPS-Installation

**Weg:** Nach dem Deploy Betrieb → Servereinstellungen → Karte
„Sicherheitskopfzeilen". Die HSTS-Stufe steht auf **1 Tag**. Mit den
Entwicklerwerkzeugen (Netzwerk → eine beliebige Seite → Antwort-Header) nachsehen.
**Erwartet:** `Strict-Transport-Security: max-age=86400`. **Genau einmal.**
Dann auf **1 Jahr** stellen, neu laden: `max-age=31536000`, wieder genau einmal.
**Woran ein Scheitern zu erkennen ist:** Die Kopfzeile steht **zweimal** da,
oder sie ändert sich beim Umschalten nicht. Dann ist die HSTS-Zeile in
`server/.htaccess` wieder aufgetaucht — sie muss **weg** sein (E-P5a-31), weil
`Header always set` überschreibt, was PHP schickt.
**Warum dieser Punkt hier steht:** Lokal liegt die Installation hinter einem
TLS-Weiterleiter, der unverschlüsselt an PHP weitergibt; HSTS wird dort
richtigerweise gar nicht gesetzt (N10).

**Und ein Satz für die Betreiberin:** Nach dem Update steht die Bindung auf
**1 Tag**, auch wenn die `.htaccess` vorher ein Jahr band. Wer produktiv läuft
und bei seiner Domain bleibt, stellt hier wieder auf **1 Jahr**.

---

### P15 — Der Weg über die Reporting-API

**Weg:** Auf der echten HTTPS-Installation eine beliebige Seite öffnen und in
den Antwort-Headern nachsehen.
**Erwartet:** Neben der CSP steht `Reporting-Endpoints: csp="https://…"` mit
der **vollständigen** Adresse dieser Installation, und die CSP endet auf
`report-uri api/csp_bericht.php; report-to csp`.
**Gegenprobe (wichtiger als die Zeile selbst):** In der Konsole
`document.head.appendChild(Object.assign(document.createElement('img'), {src:'https://kachel.invalid/x.png'}))`
ausführen, dann Betrieb → Servereinstellungen neu laden. **Unter den Berichten
muss eine Zeile `img-src | https://kachel.invalid` stehen.**
**Woran ein Scheitern zu erkennen ist:** Die Konsole meldet den Verstoß, die
Liste bleibt leer. Genau das ist am 15.09.2026 passiert (F6), und es sah aus
wie ein grünes Ergebnis. Dann zeigt `report-to` auf eine Gruppe, die der
Browser nicht auflösen kann.
**Warum dieser Punkt hier steht:** Lokal liefert `kopf_melde_url()` bewusst
nichts (kein HTTPS), also steht `report-to` dort gar nicht erst in der
Richtlinie — geprüft ist nur der Weg über `report-uri` (N11).

---

### P16 — Scharf schalten, nach zwei Wochen und nicht früher

**Weg:** Zwei Wochen normalen Betrieb abwarten. Dann Betrieb →
Servereinstellungen → Karte „Sicherheitskopfzeilen" → die Liste der Berichte
ansehen.
**Erwartet:** **leer.** Erst dann den Schalter umlegen.
**Danach durchklicken, und zwar diese sechs Wege:** Karte in allen vier
Anbietern · Einsatzformular mit Adresssuche (tippen, Vorschlag wählen) ·
Import einer XLSX-Datei · Export · Druckansicht · Schneidewerkzeug einer Spur.
**Woran ein Scheitern zu erkennen ist:** Ein Knopf tut **nichts**. Keine
Fehlermeldung, keine rote Seite — die Meldung steht nur in der Konsole
(F12). Das ist die Betriebsart eines vergessenen Nonces. Sofort
zurückschalten; der Schalter wirkt ohne Neustart.
**Steht etwas in der Liste, ist es vor dem Umschalten zu klären, nicht danach.**
Jede Zeile nennt Richtlinie, Quelle und Seite — genug, um sie zu finden.
**Warum dieser Punkt hier steht:** Die zwei Wochen messen, was ein Prüflauf
nicht sieht: was **Nutzerinnen** auf Wegen tun, die der Bilderlauf nicht geht.

---

### P17 — Hinter einem Reverse Proxy

**Weg:** Nur nötig, wenn die Installation hinter einem Reverse Proxy,
Loadbalancer oder DDoS-Schutz steht. In `config.php` den Block `netz` füllen
(Adressen oder CIDR), dann von **zwei verschiedenen** Anschlüssen aus je
einmal falsch anmelden.
**Erwartet:** Beide Fehlversuche zählen **getrennt**. In `rate_limits` stehen
zwei Zeilen mit verschiedenen Adressen.
**Woran ein Scheitern zu erkennen ist:** Es steht **eine** Zeile da, mit der
Adresse des Proxys. Dann greift der Eintrag nicht — Tippfehler im CIDR, oder
der Proxy meldet sich mit einer anderen Adresse, als dort steht. Die Folge im
Betrieb: Der Ratenschutz sperrt **alle** Nutzerinnen gemeinsam aus.
**Die Gegenrichtung ist genauso wichtig:** Steht dort ein zu weiter Bereich
(etwa `0.0.0.0/0`), darf jeder seine eigene Adresse behaupten — und der
Ratenschutz ist wirkungslos. Im Zweifel **leer lassen**; dann rechnet alles
wie vor Web 20.7.0.

---

## 4. Zuarbeiten, ohne die Punkte offen bleiben

Aus dem Rahmenplan, Abschnitt 6 — hier nur, was P1 bis P8 blockiert:

1. GitHub-Umgebungen `staging` und `produktion` anlegen; die drei
   FTP-Geheimnisse je Umgebung, `JOBS_TOKEN` nur bei `produktion`.
2. **Pflichtfreigabe** („required reviewers") an der Umgebung `produktion`.
3. Zweigschutz auf `main` mit `pruefung` als **Pflichtprüfung** — ohne ihn ist
   Stufe 1 eine Auskunft und keine Schranke.
4. Variablen: `FTP_ZIELPFAD` je Umgebung, `STAGING_URL`, `PRODUKTION_URL`.
5. Staging-Installation samt Prüfkonto (`STAGING_KONTO`, `STAGING_PASS`).
6. `CIQ_GERAETE_URL` als Repositoriums-Secret.

---

## 5. Grenzen der benutzten Prüfmittel

- **`tools/migrationsregister/`** sieht keine DDL, die aus eingesetzten Namen
  gebaut wird (`ALTER TABLE \`$tab\` …`). Vier Spalten fallen heute genau so;
  sie stehen mit Begründung in `ausnahmen.json`. Eine Migration, die ihre
  ganze DDL so baut, ist für das Werkzeug unsichtbar.
- **`tools/kette/tor.py --selbstprobe`** misst die Entscheidungslogik, nicht
  das Netz: Ob die Gegenstelle wirklich der Produktivserver ist, ob das
  Backup lesbar ist und ob der Serverschlüssel der richtige ist, prüft sie
  nicht (das erste ist Sache der Umgebung, das zweite und dritte Sache von
  `tools/wiederherstellungs-probe/`).
- **Die YAML-Prüfung** sagt, dass die Dateien gültig sind, nicht dass sie das
  Richtige tun. Ein Ausdruck in `if:` kann syntaktisch einwandfrei und
  inhaltlich falsch sein; das zeigt erst P1.
- **`tools/vollstaendigkeit/`** misst das Stylesheet gegen den Stand vor P3.
  Seine Zahl ist ein Vergleich, keine Güte.
- **`tools/installweiche/`** sieht nur Konstrukte mit eigenem Token. Benannte
  Argumente, Eigenschaftenbeförderung, ein nachgestelltes Komma in einer
  Parameterliste und `new` in Initialisierern sieht sie **nicht** — sie druckt
  diese vier bei jedem Lauf mit aus, damit die Grenze nicht nur in der
  Dokumentation steht.
- **Der Torwächter misst nur bei angemeldeten Anfragen.** `ingest.php` und
  `pair.php` laden `auth_guard.php` nicht; bis zur ersten Anmeldung nach einem
  Update bekommen die Geräte 500 statt 503. Verloren geht nichts, und mit der
  Auslieferungskette ist das Fenster null — aber es ist da, und die
  Wartungsprobe misst es nicht (sie meldet sich an).
- **Ein Muster, das HTML liest, liest kein PHP.** `<script…[^>]*>` endet am
  `>` eines PHP-Schlusses `?>` — und `<script<?= kopf_nonce_attr() ?>>` ist
  die Schreibweise dieses Projekts. Zwei Werkzeuge sind darüber gestolpert
  (Integritätswache, Wartungsprobe 12a); beide lesen den Tag-Rumpf jetzt als
  `(?:<\?(?:php\b|=).*?\?>|[^>])*`. Wer ein drittes Werkzeug schreibt, das
  Markup aus **Quelldateien** liest, braucht dasselbe.

- **`tools/cspprobe/pruefen.php`** liest nur PHP. Markup, das zur Laufzeit in
  `assets/*.js` entsteht, sieht sie nicht — ein per `innerHTML` eingesetztes
  `<script>` führt der Browser allerdings ohnehin nicht aus. Und sie sieht
  **nicht, ob der Nonce wirkt**: Dass `kopf_nonce_attr()` dasteht, heißt
  nicht, dass `kopfzeilen_seite()` vorher lief. Dafür ist `browserprobe.mjs`
  da. Ihre Liste der Ereignis-Attribute (28 Stück) ist nicht abschließend.
- **`tools/cspprobe/browserprobe.mjs`** misst eine Installation **ohne
  HTTPS** (TLS-Weiterleiter davor). HSTS, der HTTPS-Zwang und der Weg über
  `report-to` stehen dort deshalb gar nicht erst in der Richtlinie und sind
  ungemessen (N10, N11). Und sie misst **Chromium**; dass Firefox und WebKit
  dieselbe Richtlinie gleich deuten, ist angenommen, nicht gemessen — bei
  `report-uri` gegen `report-to` gehen die Engines nachweislich auseinander.
- **Eine Zählung im eigenen Quelltext misst nicht, was der Browser tut.**
  `img-src data:` wurde gestrichen, weil `grep` über `server/` und
  `assets/style.css` **0 Treffer** ergab; der Browser meldete danach **140
  Verstöße**, weil die Quelle in einer **minifizierten Bibliothek** steht
  (`leaflet.js`). Wer künftig eine Richtlinienzeile mit einer Zählung
  begründet, zählt in `assets/vendor/` mit — oder verlässt sich auf die
  Report-Only-Phase und nicht auf die Zählung.
- **`plattform_pruefen()`** misst, was PHP messen kann. Der freie Plattenplatz
  ist auf geteiltem Webspace die Zahl des Hosts und nicht das Kontingent; die
  Zeile sagt es dazu und die Prüfung meldet `null` statt einer Entwarnung. Die
  Kontingente selbst sind **Angaben**, keine Messungen — wer sie falsch
  einträgt, bekommt eine falsche Warnung.
