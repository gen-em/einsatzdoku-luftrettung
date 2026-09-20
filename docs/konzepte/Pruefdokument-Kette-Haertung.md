# Prüfdokument Kette II — Produktivpfad härten

Geführt nach `CLAUDE.md` 7, von AP1 an mitgeführt: Was ist geprüft, mit
welchem Mittel und mit welcher **Zahl**; **was konnte nicht geprüft werden und
warum**; welche Funde sind aufgetreten; und als Kernstück die **Prüfliste für
die Betreiberin** — alles, was nur an der laufenden Anlage geht.

Das Konzept liegt daneben (`Konzept-Kette-Haertung.md`) und trägt den
Statusblock der Umsetzung. Dieses Dokument bleibt, bis seine Prüfliste
abgehakt ist (R62).

> **Dieses Paket ist anders geprüft als die üblichen**, und der Grund gehört
> nach oben: **Die Kette prüft man, indem man sie fährt.** Ein Arbeitslauf
> ist kein Code, den man lesend abnehmen kann — er ist erst wahr, wenn er auf
> einem Läufer gegen eine echte Anlage lief. Genau das ist der Anlass dieses
> Konzepts (Abschnitt 1.2: *„Der Pfad `produktion` war nie gefahren worden"*).
> Was hier maschinell grün ist, sagt deshalb weniger als sonst — und was
> aussteht, steht in Abschnitt 0.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 20.09.2026 — **AP1 gebaut, Abnahme offen.** AP2 bis AP8 nicht begonnen |
> | Geprüft | Maschinell: Wortliste, Kettenaufrufe samt beider Selbstproben, YAML-Gültigkeit, Zählung der Fundstellen, Zählung der Ausnahmeliste. Zahlen in Abschnitt 1 |
> | Nicht geprüft | **Der Staging-Lauf gegen lima-city** — die Abnahme von AP1. Dazu alles, was **nur die Anwendung** weiß (Datenbank, Platz, Kontingent, Jobwege auf Staging): Die Einrichtung dort **scheitert gerade**, also hat die Statusseite nie geantwortet. Abschnitt 0 |
> | Funde | **fünf** (Abschnitt 2): drei aus der Umsetzung, einer aus einer unabhängigen Gegenlesung durch sieben getrennte Leser, einer aus den Z3-Angaben — **F-KH-U-05, ein Fehlbefund der Statusseite auf lima-city**, und damit der erste Ertrag des Hosterwechsels. Alle behandelt; zwei Befunde der Gegenlesung und F-KH-U-05 bleiben bewusst liegen (Servercode, nicht AP1) |
> | Prüfumgebung | Wegwerf-Container ohne Netzzugang zu den Anlagen (Abschnitt 0, Punkt 3); Python 3 für die Prüfmittel; **keine** lokale Installation nötig, weil AP1 keinen Web-Code anfasst |

---

## 0. Was **nicht** geprüft werden konnte, und warum

Das steht hier oben und nicht in einer Fußnote.

**1 — Die Abnahme von AP1 ist nicht gefahren, und sie ist aus der Umsetzung
heraus auch nicht fahrbar.** Das Konzept verlangt: *„Push auf `main` →
Staging-Lauf gegen lima-city grün; in `stufe2` alle fünf Messschritte
gemessen (kein ÜBERSPRUNGEN); Kreisläufe 0 unerklärt; Bilderlauf 0/0/0."*
Ein Push auf `main` ist der Betreiberin vorbehalten (`CLAUDE.md` 3: *„Niemals
ungefragt pushen"*; `CLAUDE.md` 8: *„Auf `main` kommt eine Phase einmal, am
Ende, nach ausdrücklicher Bestätigung"*), und er löst den Staging-Deploy
tatsächlich aus. **AP1 ist damit gebaut und nicht abgenommen.** Der
vollständige Bedienweg steht als **Prüfpunkt 1** unten — er ist der
wichtigste Punkt dieses Dokuments, weil er zugleich der **erste Kettenlauf
gegen lima-city überhaupt** ist.

**Und er kann derzeit gar nicht grün werden.** Die Einrichtung der neuen
Staging-Anlage **scheitert** (Rahmenplan 6a, Schritt 6; Stand 20.09.2026, eine
andere Instanz arbeitet daran). Solange `install.php` dort nicht durch ist,
leitet `login.php` auf `install.php` um — und **Stufe 2 ist rot, und zwar zu
Recht**: Ein Stand, der auf Staging nicht läuft, ist nicht freigabefähig.
**Die Abnahme von AP1 hängt damit an einer fremden Aufgabe**, nicht an diesem
Paket. Der Job `staging` (der FTPS-Abgleich) kann vorher grün werden; das
wäre schon eine Auskunft — siehe Prüfpunkt 1, Fehlerbild (c).

**2 — Z3 ist geliefert, aber die beiden Spalten sind nicht dasselbe wert.**
Die Betreiberin hat am 20.09.2026 beide Anlagen genannt, und beide Tabellen
(`docs/Technik.md` 6.3a, `docs/Rahmenplan.md` 6a) tragen sie. **Die Quellen
sind aber verschieden, und das begrenzt, was die Staging-Spalte belegt:**
Produktiv ist aus *Betrieb → Status* und *Betrieb → Hintergrundjobs*
abgelesen, also aus `plattform_pruefen()`. **Staging ist aus einer
`phpinfo()`-Ausgabe erhoben**, weil die Anwendung dort **noch nicht
installiert ist** — Rahmenplan 6a, Schritt 6 **scheitert gerade**.

**Fünf Zeilen der Staging-Spalte bleiben deshalb leer**, und zwar genau die,
die nur die Anwendung selbst wüsste: Datenbankfassung,
`max_user_connections`, Kontingent der Datenbank, freier Platz, Cron-Weg.
Dazu die Herkunft des Zertifikats, die keine der beiden Quellen nennt.
**Geschätzt wurde nichts.**

**Und `phpinfo()` ist nicht die Statusseite** — das ist keine Formalie,
sondern in diesem Paket nachgewiesen: Beim **OPcache** sagen die beiden
Quellen für dieselbe Anlage das Gegenteil (F-KH-U-05). Die Staging-Spalte ist
deshalb als **vorläufig** gekennzeichnet und wird ersetzt, sobald die
Statusseite dort antwortet. **Prüfpunkt 2b** holt das ein.

*Und ein Vergleich, dessen eine Hälfte anders gemessen ist als die andere,
trägt weniger als er aussieht.* Die Aussage aus 6.3a — *„die Zahlen des
Messstands sind nicht übertragbar"* — stützt sich weiterhin vor allem darauf,
dass zwei verschiedene Hoster zwei verschiedene Grenzen setzen. **Belegt ist
sie inzwischen auch:** `max_execution_time` 240 s gegen 300 s,
`post_max_size` 256 MB gegen 500 MB — drei Weblimits weichen ab, und damit
misst Stufe 2 auf Staging nachweislich andere Grenzen als Produktiv hat.

**3 — Ob `staging-nadoku.gen-em.org` antwortet, ist von hier aus nicht
messbar.** Versucht, zweimal, um 09:41:59 UTC: `curl` auf `/login.php` und
auf `/`. Beide Male **`curl: (56) CONNECT tunnel failed, response 403`**,
**0 Byte übertragen**. Die Ursache liegt **nicht** bei der Anlage, sondern an
der Netzpolitik dieser Arbeitsumgebung — der Statusbericht des Vermittlers
nennt beide Versuche wörtlich als `connect_rejected`, *„gateway answered 403
to CONNECT (policy denial or upstream failure)"* für
`staging-nadoku.gen-em.org:443`. **Daraus folgte nichts über den Server:**
weder dass er steht, noch dass er fehlt.

**Beantwortet hat es dann die Betreiberin, nicht die Messung:** Eine
`phpinfo()`-Ausgabe vom selben Tag belegt, dass die Anlage über **HTTPS**
antwortet (Apache 2.4, Port 443), dass `SERVER_NAME`
`staging-nadoku.gen-em.org` lautet und dass das Dokumentenwurzelverzeichnis
ein eigenes ist. **Schritt 1 in Rahmenplan 6a ist damit abgehakt** — mit
diesem Beleg und nicht mit einem Kettenlauf. Schritt 2 bleibt ungemeldet,
Schritt 6 **scheitert**.

**4 — Die Fehlermeldungen der Kette sind gelesen, nicht ausgelöst.** Dass
`auslieferung.yml` auf „Rahmenplan 6a, Schritte 1 bis 3" und „Schritt 4"
verweist (Grundlage von E-KH-21), ist durch Lesen der drei Stellen belegt.
Dass der Verweis nach der Neufassung von 6a noch trägt, ist durch Vergleich
der Schrittbedeutungen belegt — **nicht dadurch, dass jemand die Meldung
gesehen hat**. Sie erscheint nur, wenn die Subdomain nicht antwortet oder der
Zielpfad falsch ist; beides herzustellen hieße, Staging kaputtzumachen.

**5 — Der Satz „die Messstand-Zahlen sind nicht übertragbar" ist begründet,
nicht gemessen.** Er folgt daraus, dass zwei verschiedene Hoster zwei
verschiedene Grenzen setzen — nicht daraus, dass jemand die Grenzen beider
Anlagen nebeneinander gelegt hätte. Genau das täte der Plattformvergleich,
und der wartet auf Z3 (Punkt 2). **Bis dahin ist der Satz die vorsichtige
Annahme**, und die vorsichtige Annahme ist hier die richtige: Sie verbietet
eine Berufung, die vielleicht trüge, statt eine zu erlauben, die vielleicht
nicht trägt.

**6 — Nichts an der Wache, am Tor, an der Zielprobe und am Transport ist
berührt, also auch nichts davon geprüft.** AP1 ist ein Dokumentationspaket.
**E-KH-20 (Schutzliste) ist deshalb nicht ausgelöst worden** — die
Ausnahmeliste ist unverändert: gemessen **zwei** `exclude`-Blöcke in
`auslieferung.yml`, je **12 Zeilen**, **wortgleich**, darin die sieben
geschützten Pfade (`config.php`, `install.php`, `install.lock`,
`wartung.lock`, `ueberlast.json`, `sicherungen/`, `apk/`). Damit steht sie
weiterhin **zweimal** — genau das, was E-KH-20 (1) beheben will, und zwar in
**AP5**. Die Köderprobe (E-KH-20 (4)) gehört zu **AP4** und kann vorher
nichts belegen.

---

## 1. Prüfprotokoll — Soll und Ist

### 1.1 AP1 — maschinell

| Mittel | Aufruf | Soll | Ist | Was es gemessen hat |
|---|---|---|---|---|
| Wortliste | `python3 tools/wortliste/wortliste.py` | 0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen | **0 / 0 / 0**, Rückgabe 0 | fünf Bereiche; darin Bereich **c** (normative Doku) mit **11 Dateien** und **444 Treffern, alle erklärt**. **`docs/Technik.md` ist dabei** — das ist die von AP1 geänderte Datei, die die Wortliste überhaupt ansieht |
| Wortliste, Selbstprobe | `… --probe` | alle Fälle | **21 von 21** | dass der Zerleger Kommentare zeilentreu entfernt |
| Kettenaufrufe | `python3 tools/kettenaufrufe/pruefen.py` | 0 Befunde, 0 ungeprüft | **3 Arbeitsläufe · 28 Aufrufe · 0 Befunde · 0 ungeprüft** | dass jeder Werkzeugaufruf in `.github/workflows/` zur Schnittstelle seines Werkzeugs passt — unverändert gegenüber dem Stand vor AP1 |
| Kettenaufrufe, Selbstprobe | `… --probe` | alle Fälle | **10 von 10** | die vier Gegenproben inbegriffen |
| YAML | `yaml.safe_load` über alle drei Arbeitsläufe | gültig | **3 von 3 gültig** | dass der geänderte Kommentar den Lauf nicht zerbrochen hat |
| Fundstellen alte Adresse | `grep -rn 'staging\.nadoku\.gen-em\.org'` ohne `.git/` | nur noch Historie | **13 Fundstellen** (vorher 12) | jede einzeln eingeordnet — Tabelle unten |
| Fundstellen neue Adresse | `grep -rn 'staging-nadoku\.gen-em\.org'` ohne `.git/` | — | **16 Fundstellen** | eine Auskunft, kein Sollwert |
| Web-Code berührt? | `git status --short -- server/ watch/ android/` | 0 | **0 Zeilen** | dass keine Versionsstufe fällig ist (E-KH-23) |
| Ausnahmeliste | Blöcke in `auslieferung.yml` gezählt und verglichen | unverändert | **2 Blöcke · je 12 Zeilen · wortgleich** | dass AP1 den Transportschutz nicht angefasst hat (E-KH-20) |

**Zur Zahl 13 gegenüber 12.** Die alte Adresse steht nach AP1 an **mehr**
Stellen als vorher, und das ist kein Rückschritt. Vorher waren **5 der 12**
aktuelle Aussagen über die heutige Anlage; die sind umgestellt. **4** waren
Protokoll, das sich als Gegenwart lesen ließ; die sind datiert und mit
Vermerk versehen worden, statt umgeschrieben. **3** waren reine Historie und
sind unberührt. Dazugekommen sind Stellen, die den Umzug **erzählen** — die
Fassung 81 im Änderungsverlauf, der historische Kasten in 6a, `Technik.md`
6.3a, der Satz in `CLAUDE.md` — und das Konzept selbst, das die ursprünglichen
12 in seinem Abschnitt 1.5 aufzählt.

| # | Fundstelle | Einordnung |
|---|---|---|
| 1 | `.github/workflows/auslieferung.yml`:278 | Messung vom 16.09., als **„der DAMALIGEN"** gekennzeichnet |
| 2 | `CLAUDE.md`:79 | ausdrücklich **„der Stand bis zum 19.09.2026"** |
| 3 | `docs/Rahmenplan.md`:175 | Festlegung vom 15.09.; der nächste Satz sagt **„Seit dem 20.09.2026 gilt E-KH-04"** |
| 4 | `docs/Rahmenplan.md`:1218 | Vorbereitungsblock mit **Nachtrag** |
| 5 | `docs/Rahmenplan.md`:1870 | historischer Kasten in 6a (**„Bis zum 19.09.2026"**) |
| 6 | `docs/Rahmenplan.md`:2029 | Domain-Default-Messung, **„der damaligen Anlage"** |
| 7 | `docs/Rahmenplan.md`:3261 | Fassung 81 — der Änderungsverlauf selbst |
| 8 | `docs/Rahmenplan.md`:3272 | Fassung 70 — reine Historie, unberührt |
| 9 | `docs/konzepte/Pruefdokument-P5b-…`:586 | Protokoll eines Vorfalls, unberührt |
| 10 | `docs/konzepte/Pruefdokument-P5a-…`:1408 | Messprotokoll Stufe 2, Lauf #4, unberührt |
| 11 | `docs/konzepte/Vorbereitung-P5-Plattformprofil.md`:429 | Wortlaut E-PP-09 — bleibt als Herkunft, **Vermerk steht darüber** |
| 12 | `docs/konzepte/Konzept-Kette-Haertung.md`:213 | der Befund der Durchsicht, der die 12 Stellen aufzählt |
| 13 | `docs/Technik.md`:8897 | **„Bis zum 19.09.2026 lagen sie im selben Webspace"** |

### 1.2 AP1 — durch Lesen belegt

- **Die Schrittnummern in 6a tragen weiter.** Drei Verweise in
  `auslieferung.yml` geprüft: Zeile 317 („Schritte 1 bis 3"), Zeile 321
  („Schritt 4"), dazu der Kommentar zu den vier Lagen („Schritte 6–8").
  Jede Nummer hat in der Neufassung dieselbe Bedeutung wie vorher —
  Bedeutungen verglichen, nicht nur Nummern gezählt. Grundlage von E-KH-21.
- **`FTP_ZIELPFAD` und `FTP_STATE_PFAD` tragen Vorgabewerte.** Gelesen in
  `auslieferung.yml` Zeile 177 (`vars.FTP_ZIELPFAD || './staging/'`),
  197 (`|| '../.deploy-state-staging.json'`), 706 (`|| './httpdocs/'`) und
  709 (`|| '../.deploy-state-produktion.json'`). Das begründet den neuen
  Absatz in `docs/Technik.md` 6.5 und die Tabelle in 6a.
- **`FTP_STATE_PFAD` fehlte in der Variablentabelle von `Technik.md`.**
  Gefunden beim Gegenlesen, ergänzt. Das ist keine AP1-Erfindung, sondern
  eine Lücke, die AP1 auffiel, weil AP1 den *Ort der Zustandsdatei*
  dokumentieren soll.
- **Die Querverweise sind gegengelesen:** `CLAUDE.md` 3 → `Technik.md` 6.3a;
  `Technik.md` 5b → 6.3a; `Technik.md` 6.5 → Rahmenplan 6a; Rahmenplan 6a →
  `Technik.md` 6.3a; Rahmenplan-Zuarbeitszeile → 6a. Alle fünf Ziele
  existieren.

### 1.3 Nicht gefahren — und warum das hier steht

| Mittel | Warum nicht |
|---|---|
| `tools/vollstaendigkeit/` | misst `server/assets/style.css` gegen die Streichliste — AP1 fasst kein CSS an |
| `tools/screenshots/` und `kontrast.py` | fotografieren die Weboberfläche — AP1 ändert keine Seite |
| `tools/stilvergleich/` | wacht erst ab P4 und misst CSS |
| `./gradlew build`, Emulator, Uhr-Prüfstand | AP1 fasst weder `android/` noch `watch/` an |
| Browserprüfung | AP1 ändert nichts, was ein Browser zeigt |

**Das ist keine Nachlässigkeit, sondern die Zuordnung aus `CLAUDE.md` 6 und
9.** Ein Bilderlauf über ein Dokumentationspaket lieferte eine grüne Zahl
über etwas, das das Paket nicht angefasst hat — genau der Fall, vor dem
`CLAUDE.md` 6 warnt („eine grüne Zahl ist erst dann ein Beleg, wenn sie das
Gemessene benennt").

---

## 2. Funde aus der Umsetzung

**F-KH-U-01 — Alle Haken in Rahmenplan 6a galten der alten Anlage.**
Schritte 1 bis 6, 8 und 10 waren am 16./17.09.2026 abgehakt, für
`staging.nadoku.gen-em.org` im Produktiv-Webspace. Nach dem Hosterwechsel
sagt kein einziger davon etwas über lima-city — eine Liste, die acht Haken
zeigt, während nichts geprüft ist, ist schlimmer als eine leere.
*Behoben:* Alle Zeilen stehen wieder offen, **je mit Grund** („Stand nicht
gemeldet (Z3)", „Zuarbeit Z7", „das ist die Abnahme von AP1"), dazu ein
Kasten, der sagt, dass und warum die Haken verfallen sind, und wo der alte
Stand liegt (Git-Historie, Fassung 80).

**F-KH-U-02 — Die Schrittnummern sind gebunden, und kein Prüfmittel schützt
sie.** `auslieferung.yml` verweist an drei Stellen auf Nummern in
Rahmenplan 6a. Eine Umnummerierung macht aus einer hilfreichen Fehlermeldung
eine irreführende, **ohne dass etwas anschlägt**: `tools/kettenaufrufe/`
prüft Werkzeugschnittstellen, keine Textverweise.
*Behoben:* E-KH-21 — die Nummern behalten ihre Bedeutung; 6a sagt es im
Abschnitt selbst, damit die nächste Neufassung nicht darüber stolpert.
*Offen als Beobachtung:* Ein Prüfmittel, das Verweise von `.github/` in die
Dokumentation nachhält, gibt es nicht. Es ist keine AP1-Aufgabe; **ein
Backlog-Vorschlag steht in Abschnitt 4.**

**F-KH-U-03 — Der Rahmenplan-Kopf war zwei Tage überholt.** Er führte
Schritt 10b (P5b) als „fertig gebaut und liegt zum Merge bereit" und maß
`main` bei `676780d` / Web 20.16.4. Gemessen am 20.09.2026: `origin/main`
steht auf **`7150793`**, **Web 20.24.2**, Uhr 3.1.0, Android 0.15.0; P5b ist
seit dem 18.09.2026 gemergt (**PR #57**, `eec41e1`), gefolgt von PR #58 und
PR #59. Aufgefallen beim Nachmessen, das Rahmenplan Abschnitt 9 vor **jeder**
Fassung verlangt — derselbe Fall, den die Regel dort für die Fassungen 39,
41 und 42 beschreibt.
*Halb behoben, und das ist wörtlich gemeint:* Der **Stand** ist berichtigt
und der Fund im Kopf vermerkt. **Nicht geschrieben** sind die
**Erledigt-Zeile für P5b in Abschnitt 8** und die Nachzüge in den
Abschnitten 3, 5 und 6 — die gehören dem Abschluss von P5b, nicht diesem
Paket. **Prüfpunkt 6** trägt es der Betreiberin vor.

**F-KH-U-04 — Vier Stellen, die erst eine unabhängige Gegenlesung fand.**
Nach dem Bau sind die geänderten Dokumente von sieben getrennten Lesern
gegengelesen worden, jeder mit einem Dokument. Vier Befunde waren berechtigt
und sind behoben:
**(a)** `docs/Technik.md` trug im Kopf noch *Stand: 17.09.2026*, obwohl AP1
die Datei ändert — auf **20.09.2026** berichtigt.
**(b)** In derselben Datei stand die Zeile `| Repositorium | CIQ_GERAETE_URL |
WACHE_BASIS |` **hinter einer Leerzeile** und damit ohne Kopf: eine
Tabellenzeile, die als Text rendert. Sie ist in die Tabelle darüber
zurückgeholt, deren erste Spalte jetzt *Ort* heißt statt *Umgebung*, weil
das Repositorium keine Umgebung ist. **Der Schaden ist älter als AP1** — er
steht in der Tabelle, die AP1 um `FTP_STATE_PFAD` ergänzt hat, und wurde beim
Gegenlesen dieser Ergänzung sichtbar.
**(c)** In `Vorbereitung-P5-Plattformprofil.md` stand unkommentiert *„bis
dahin deployt `main` weiter auf Produktiv"* — seit Web 20.4.0 falsch, und
**genau der Satz, vor dem `CLAUDE.md` 3 warnt**. Vermerk gesetzt.
**(d)** Die Herkunftszeile in Abschnitt 5 (Nachweis) derselben Datei nannte
E-PP-09 ohne den Ersetzungsvermerk. Ergänzt.

*Nicht übernommen wurde ein fünfter Hinweis* — der Vermerk an E-PP-09 zähle
Festlegungen auf, die dort nicht stünden. Nachgesehen: Sie stehen dort
(Serverschlüssel und Server-Anteil im Einrichtungspunkt, Absender und
Betreff-Präfix im zweiten, SFTP-Ziel im dritten). Der Hinweis war falsch.

**Zwei weitere Befunde der Gegenlesung sind echt und bleiben liegen**, weil
sie nicht zu AP1 gehören: `docs/Technik.md` 6.3 spricht von **„zwei der
fünfzig Seiten"** des Bilderlaufs, während `CLAUDE.md` 6 **62** nennt; und
die Begründung für zwei Namen der Zustandsdatei (Abschnitt 4.97g: *„weil sich
Staging und Produktion einen FTP-Zugang teilen könnten"*) beschreibt seit
E-KH-04 nicht mehr diese Anlage. **Der zweite ist bewusst stehen geblieben:**
Die Begründung gilt weiterhin für den allgemeinen Fall — ein Selbsthoster
kann beides auf einen Webspace legen, und dann trennt allein der Name die
beiden Zustandsdateien.

**F-KH-U-05 — Die Statusseite meldet auf lima-city das Gegenteil dessen, was
läuft, und der Hosterwechsel hat es aufgedeckt.** `plattform_pruefen()` prüft
den OPcache so:

```php
$opAn = function_exists('opcache_get_status');
if ($opAn) { $st = @opcache_get_status(false); $opAn = is_array($st) && !empty($st['opcache_enabled']); }
```

Auf Staging steht **`disable_functions = dl, syslog, opcache_get_status`**.
Für eine so abgeschaltete Funktion antwortet `function_exists()` **`false`** —
die Statusseite wird dort **„OPcache: aus"** zeigen, während die `phpinfo()`
derselben Anlage **„Opcode Caching: Up and Running"** meldet (Dateicache,
`file_cache_only = On`, SHM und JIT aus).

**Der Schaden ist klein, der Fehler ist grundsätzlich.** Klein, weil OPcache
nur *Empfohlen* ist, keine Ampel färbt und die Einrichtung nicht aufhält —
und weil `opcache_invalidate()`, das die Anwendung nach jedem Schreiben in
`config.php` ruft (`serverkrypto_lib.php`:825), **nicht** abgeschaltet ist und
weiter wirkt. Grundsätzlich, weil `docs/Technik.md` 5b.1 genau das verbietet:
*„`ok` ist dreiwertig … **`null` nicht feststellbar**. Wer nichts gemessen
hat, darf nichts behaupten."* Hier hat die Anwendung nichts messen **können**
und behauptet trotzdem „aus". Die Zeile gehört auf `null`.

**Nicht behoben, und das ist die richtige Entscheidung für dieses Paket.**
Die Behebung liegt in `server/plattform_lib.php`, wäre also Web-Code, eine
Versionsstufe und ein Changelog-Eintrag — AP1 ist ein Dokumentationspaket
(E-KH-23). **Der Vorschlag steht in Abschnitt 4.**

**Der Fund selbst ist der erste Ertrag von E-KH-04.** Die Entscheidung
versprach, die Portabilitätszusage aus R81 werde von nun an *geprobt* statt
behauptet. Sechs Tage lang liefen beide Anlagen beim selben Hoster, und
dieser Zuschnitt fiel niemandem auf. Er fiel auf, sobald die zweite Plattform
danebenstand — **bevor** ein Selbsthoster ihn gefunden hat, und bevor die
Kette einmal gegen sie gelaufen ist.

---

## 3. Prüfliste für die Betreiberin

Was nur an der laufenden Anlage geht. Je Punkt: der Bedienweg, das erwartete
Ergebnis, und **woran ein Scheitern zu erkennen ist**.

- [ ] **1 — Die Abnahme von AP1: der erste Kettenlauf gegen lima-city.**
  Das ist der einzige Punkt, der AP1 abnimmt, und zugleich der erste Lauf der
  Kette gegen die neue Anlage überhaupt.
  *Weg:* Diesen Zweig nach `main` bringen (PR) und den Lauf **Auslieferung**
  unter *Actions* öffnen.
  *Erwartet:* Job `staging` grün (FTPS-Abgleich mit einer Zahl
  synchronisierter Einträge), danach Job `stufe2` grün — und dort **alle
  fünf Messschritte gemessen, keiner „ÜBERSPRUNGEN"**: Griff auf
  `login.php`, Punktdateien (vier × 403, `.well-known/` **404 und nicht
  403**), Kreislauf csv, Kreislauf edbak (je **0 unerklärt**), Bilderlauf
  (**0 Überlauf / 0 Konsolenfehler / 0 falsche Knopfhöhen**).
  *Scheitern erkennbar an:*
  **(a)** „ÜBERSPRUNGEN" an einem der fünf Schritte — dann fehlt eine
  Zuarbeit in der Umgebung `staging` (`STAGING_URL`, `STAGING_KONTO`,
  `STAGING_PASS`, `JOBS_TOKEN`); der Lauf ist **grün und hat nichts
  gemessen**, genau der Befund B5.
  **(b)** `login.php` antwortet nicht → Subdomain steht nicht (6a,
  Schritte 1–3). **404** → die Dateien liegen im falschen Verzeichnis
  (`FTP_ZIELPFAD`, Schritt 4). Weiterleitung auf `install.php` → Schritt 6
  fehlt. **200 ohne die Fußzeile dieser Anwendung** → eine fremde Seite, also
  eine leere Subdomain.
  **(c)** Der FTPS-Abgleich scheitert mit `ECONNRESET` an `ensureDir` →
  **das wäre F3 auch bei lima-city**, und es wäre ein wichtiger Befund für
  AP3: Dann liegt es nicht am Produktiv-Konto. **Sofort melden.**
  **(d)** Der Bilderlauf meldet viele Bilder und 0 Überlauf, aber alle Bilder
  zeigen die Anmeldeseite → das Demo-Konto fehlt (6a, Schritt 8). Die Zahl
  ist dann grün und wertlos (Fund F-P3-AQ).

- [x] **2a — Z3, Produktiv.** *Erledigt am 20.09.2026 von der Betreiberin*:
  Plattformauskunft aus *Betrieb → Status*, Jobwege aus *Betrieb →
  Hintergrundjobs*, Einrichtungswerte aus der GitHub-Umgebung `produktion`.

- [x] **2b — Z3, Staging, vorläufig.** *Erledigt am 20.09.2026*: PHP-Werte aus
  einer `phpinfo()`-Ausgabe, FTPS und Zielpfad aus der Auskunft der
  Betreiberin, `STAGING_URL` aus der GitHub-Umgebung `staging`.

- [ ] **2c — Z3, Staging, aus der Anwendung.** Erst möglich, wenn die
  Einrichtung durch ist (Rahmenplan 6a, Schritt 6 — **scheitert gerade**).
  *Weg:* Auf Staging *Betrieb → Status* öffnen, die Karte **„Plattform"**
  aufklappen und den Text abnehmen; dazu *Betrieb → Hintergrundjobs* für den
  **Cron-Weg**; aus dem Panel des Hosters die **Herkunft des Zertifikats**.
  *Erwartet:* Die fünf heute leeren Zeilen der Staging-Spalte in
  `docs/Technik.md` 6.3a füllen sich — Datenbankfassung,
  `max_user_connections`, Kontingent der Datenbank, freier Platz, Cron —
  und die **vorläufigen** PHP-Zeilen werden gegen die Statusseite
  gegengelesen.
  *Scheitern erkennbar an:* **Die Zeile „OPcache" wird „aus" sagen, und das
  ist falsch** — F-KH-U-05. Wer sie ungeprüft in die Tabelle übernimmt,
  schreibt den Fehlbefund fest. Ebenso: Eine Zelle, die niemand ablesen kann,
  bekommt **„unbekannt" und keinen Schätzwert** (5b.1). Und: **Erfüllte
  „Empfohlen"-Zeilen zeigt die Karte gar nicht an** — fehlt eine, heißt das
  *erfüllt*; die Schlusszeile „x von y erfüllt" löst es auf.

- [ ] **3 — `FTP_ZIELPFAD` und `FTP_STATE_PFAD` in beiden Umgebungen
  ausdrücklich setzen** (Zuarbeit Z4, vorgezogen — der Grund ist AP1).
  *Stand 20.09.2026, nachgesehen:* `FTP_ZIELPFAD` steht in **beiden**
  Umgebungen auf `/`; **`FTP_STATE_PFAD` fehlt in beiden**.
  *Weg:* GitHub → Settings → Environments → `staging` bzw. `produktion` →
  *Environment variables*.
  *Erwartet:* Beide Namen stehen in **beiden** Umgebungen mit einem Wert.
  *Scheitern erkennbar an:* Nichts fällt auf — und das ist der Punkt. Fehlt
  die Variable, greift der **Vorgabewert** (`./staging/`, `./httpdocs/`) und
  die Kette lädt in ein Verzeichnis, das vielleicht das falsche ist, **ohne
  eine Meldung**. Erst AP6 nimmt die Vorgaben weg (E-KH-07); bis dahin ist
  dieser Punkt die einzige Sicherung.

  > **Wichtig: „ausdrücklich" heißt hier nicht „anders".** Einzutragen ist
  > genau der Wert, den die Vorgabe heute erzeugt —
  > **`../.deploy-state-staging.json`** bzw.
  > **`../.deploy-state-produktion.json`**. Der Punkt macht den Wert sichtbar,
  > er ändert ihn nicht.
  >
  > **Nicht** vorab auf einen Pfad *innerhalb* des Webroots umstellen, so
  > naheliegend das bei zwei eingesperrten Konten aussieht. Ob die Server das
  > `../` vertragen, ist die offene Frage aus Rahmenplan 6a, und **der erste
  > Kettenlauf gegen lima-city (Punkt 1) beantwortet sie für Staging, AP3 mit
  > der Zielprobe für Produktiv.** Wer sie vorher „löst", verschiebt sie — und
  > legt die Zustandsdatei ohne Not in den Webroot, wo nur noch die
  > `.htaccess` zwischen ihr und der Öffentlichkeit steht.

- [x] **4 — Ist `staging-nadoku.gen-em.org` von außen erreichbar?**
  *Beantwortet am 20.09.2026:* **ja** — die Anlage antwortet über HTTPS
  (Apache 2.4, Port 443), `SERVER_NAME` stimmt, das Dokumentenwurzelverzeichnis
  ist ein eigenes. Belegt durch eine `phpinfo()`-Ausgabe, **nicht** von der
  Kette und **nicht** aus der Arbeitsumgebung heraus (Abschnitt 0, Punkt 3).
  Die Anwendung läuft dort noch nicht — das ist Schritt 6 in Rahmenplan 6a.

- [ ] **4a — `info.php` vom Staging-Server löschen. Sofort.**
  *Weg:* Per FTPS die Datei `info.php` aus dem Staging-Webroot entfernen,
  danach `https://staging-nadoku.gen-em.org/info.php` im Browser aufrufen.
  *Erwartet:* **404.**
  *Scheitern erkennbar an:* Die Seite kommt weiter. Eine `phpinfo()`-Ausgabe
  im Netz nennt jedem Besucher PHP-Fassung, geladene Erweiterungen, alle
  Pfade, `disable_functions`, die Sitzungsablage und die Kopfzeilen des
  Hosters — es ist die vollständige Bauanleitung der Anlage. **Sie war für
  diese Zuarbeit nützlich und ist danach nur noch ein Geschenk.**
  *Dazu:* Die für Z3 geteilte Ausgabe enthielt eine gültige `PHPSESSID` und
  die lima-city-Kennungen. Sie stehen **nicht** im Repositorium; die Sitzung
  gehört trotzdem verworfen (abmelden genügt).

- [ ] **5 — Drei Geheimnisse der Umgebung `staging` gehören noch der alten
  Anlage.**
  *Stand 20.09.2026, nachgesehen:* Die drei FTP-Angaben und `STAGING_URL`
  sind umgestellt (vor einer Stunde). **`JOBS_TOKEN` ist drei Tage alt**,
  **`STAGING_KONTO` und `STAGING_PASS` sind vier Tage alt** — alle drei
  stammen von der stillgelegten Anlage.
  *Weg:* Nach Schritt 6 der Einrichtung: `JOBS_TOKEN` auf der **neuen**
  Staging-Anlage unter *Betrieb → Hintergrundjobs* hinter `jobs.php?token=`
  ablesen und eintragen; Prüfkonto neu anlegen und `STAGING_KONTO` /
  `STAGING_PASS` darauf setzen.
  *Erwartet:* Alle drei Werte sind **andere** als die bisherigen.
  *Scheitern erkennbar an:* Der Lauf wird trotzdem grün — und das ist das
  Gefährliche. Mit altem `JOBS_TOKEN` laufen die Kreisläufe **ohne
  Job-Pause** und messen „hat der Verdichtungsjob dazwischen zugeschlagen"
  statt „kommt zurück, was hineinging" (gemessen: 125 verdichtete Spuren in
  einem Lauf ohne Pause). Mit altem Prüfkonto melden Kreisläufe und
  Bilderlauf **„ÜBERSPRUNGEN"** oder scheitern an der Anmeldung.

- [ ] **5a — Trennt lima-city die Sitzungsablage je Konto?**
  Die `phpinfo()` nennt `session.save_path = /home/webpages/tmp` — **über**
  dem eigenen Verzeichnis der Anlage.
  *Weg:* Beim Hoster erfragen, ob dieses Verzeichnis je Kunde getrennt ist
  (eigener Pfad, eigene Rechte) oder allen Konten desselben Systems offensteht.
  *Erwartet:* getrennt.
  *Scheitern erkennbar an:* Es ist geteilt. Dann läge dort für jede fremde
  PHP-Installation auf demselben System lesbar, wer auf Staging angemeldet
  ist — **und das ist derselbe Fehler, dessentwegen Staging gerade umgezogen
  ist** (B2: Staging-PHP konnte Produktivs `config.php` lesen). Es wäre
  weniger schlimm als B2, weil Staging keine echten Patientendaten führt und
  der Datenschlüssel ohnehin im Browser bleibt — aber es gehört gewusst,
  bevor dort Sitzungen laufen, und nicht danach.

- [ ] **6 — Fremdaufgabe, hier nur gemeldet: P5b hat keine Erledigt-Zeile.**
  Gemessen am 20.09.2026: PR #57 ist seit dem 18.09.2026 auf `main`
  (`eec41e1`), der Rahmenplan führte P5b bis Fassung 80 als „liegt zum Merge
  bereit".
  *Weg:* Entscheiden, wer den Abschluss von P5b schreibt.
  *Erwartet:* Erledigt-Zeile in Rahmenplan Abschnitt 8, Nachzüge in den
  Abschnitten 3, 5 und 6, Prüfdokument P5b abgearbeitet.
  *Scheitern erkennbar an:* Es fällt niemandem auf — bis die nächste Instanz
  den Kopf liest und einen Stand für bare Münze nimmt, den es seit zwei Tagen
  nicht mehr gibt. Genau so ist dieser Fund entstanden.

- [ ] **7 — Die Bedienregeln aus Konzept Abschnitt 5 gelten weiter.**
  Kein Hand-Backup in den Minuten vor einer Freigabe (bis AP3); nach jedem
  roten Produktivlauf *Betrieb → Updates* ansehen und die Wartung
  gegebenenfalls von Hand beenden (bis AP6); keine offene FTP-Sitzung auf dem
  Produktiv-Konto während eines Laufs (bis E-KH-09); **kein weiterer Tag-Lauf
  gegen Produktiv vor dem Ergebnis von AP3/AP4**.
  *Scheitern erkennbar an:* Ein Tag-Lauf schaltet die Wartung ein und lässt
  sie an — die Anlage ist dann für alle zu, und der Lauf sagt es nicht.

---

## 4. Vorschläge an den Backlog (Nummern vergibt die einspielende Instanz)

- **Verweise von `.github/` in die Dokumentation werden von keinem Prüfmittel
  nachgehalten.** Anlass: F-KH-U-02. `auslieferung.yml` verweist in
  Fehlermeldungen auf „Rahmenplan 6a, Schritte 1 bis 3" und „Schritt 4";
  wer 6a umnummeriert, macht daraus einen Irrweg, und `tools/kettenaufrufe/`
  schlägt nicht an — es prüft Werkzeugschnittstellen, keine Textverweise.
  Niedrig; Auslöser wäre eine weitere Neufassung von 6a.

- **`docs/Technik.md` 6.3 nennt „zwei der fünfzig Seiten" des Bilderlaufs,
  `CLAUDE.md` 6 nennt 62.** Gefunden bei der Gegenlesung zu AP1 (F-KH-U-04),
  nicht behoben, weil es weder Staging noch die Kette betrifft. Die Zahl im
  Bilderlauf wächst mit jeder neuen Seite und steht in
  `tools/screenshots/seiten.json` — eine Zahl im Fließtext veraltet dort
  planmäßig. Niedrig; zusammen mit der nächsten Pflege des Bilderlaufs.

- **`plattform_pruefen()` behauptet „aus", wo es „nicht feststellbar" heißen
  muss.** Anlass: F-KH-U-05. Steht eine geprüfte Funktion in
  `disable_functions`, antwortet `function_exists()` mit `false`, und der
  Befund wird zu einem Mangel statt zu einer Nichtmessung. Betroffen ist heute
  der **OPcache** (`opcache_get_status`, auf lima-city abgeschaltet); dieselbe
  Bauform steckt in jeder weiteren Prüfung, die über `function_exists()`
  geht. Abhilfe: Den Fall von „nicht vorhanden" trennen — `ini_get()` und
  `extension_loaded('Zend OPcache')` sagen, **dass** es ihn gibt, auch wenn
  der Zustand nicht abfragbar ist — und die Zeile dann auf **`null`** setzen
  (5b.1). Niedrig, aber **vor** der nächsten Plattformaussage: Solange sie
  steht, misst die Statusseite auf fremden Hostern falsch, und genau dort
  wird sie gebraucht.

*(Die Vorschläge aus Konzept Abschnitt 8 — atomare Auslieferung, die Grenze
der Wache, die Ablösung der Fremd-Aktion, der Vermerk an Nr. 234 — gehören
zu AP8 und stehen dort.)*

---

## 5. Grenzen der benutzten Prüfmittel

- **Die Wortliste sieht `docs/Rahmenplan.md`, `CLAUDE.md` und die
  Konzept- und Prüfdokumente NICHT an.** Bereich **c** ist eine feste Liste
  aus `README.md`, sechs `docs/`-Dateien, `docs/Design.md`,
  `docs/Lizenzen.md` und den drei Rechtstext-Entwürfen — **11 Dateien**. Von
  den sechs Dateien, die AP1 geändert hat, ist **genau eine** darin:
  `docs/Technik.md`. Das ist so gewollt (die Ausschlüsse stehen mit Begründung
  in `tools/wortliste/LIESMICH.md`), **aber es heißt: „0 Treffer" deckt AP1
  nur zu einem Sechstel.** Die übrigen fünf Dateien sind gelesen, nicht
  gemessen.
- **`tools/kettenaufrufe/` prüft Aufrufe, keine Texte.** Es hätte einen
  falschen Verweis auf Rahmenplan 6a nicht gefunden (F-KH-U-02).
- **`yaml.safe_load` prüft Syntax, nicht Sinn.** Ein gültiger Arbeitslauf
  kann trotzdem das Falsche tun — genau das war der Anlass von
  `tools/kettenaufrufe/` (drei Aufrufe, alle mit gültigem YAML, alle beim
  ersten echten Lauf gescheitert).
- **`grep` zählt Zeichenketten, nicht Bedeutungen.** Dass alle 13
  Fundstellen der alten Adresse Historie sind, ist **von Hand eingeordnet**
  (Tabelle in 1.1) und nicht gemessen.
- **Und `grep` zählt nur, was vollständig dasteht.** Eine **vierzehnte**
  Stelle hat das Muster nicht getroffen: `docs/Backlog.md`:2913 zitiert einen
  gemessenen Bildschirmtext, in dem die Adresse **abgeschnitten** ist
  (`datenschutzbeauftragte@staging.nadoku.g…`). Sie ist Protokoll einer
  Überlaufmessung und bleibt, wie sie ist — sie steht hier, weil eine Zahl,
  die ein Muster liefert, immer nur so weit reicht wie das Muster.
- **Kein Prüfmittel dieses Projekts fährt einen Arbeitslauf.** Was ein Job
  auf einem Läufer gegen eine echte Anlage tut, zeigt allein der Lauf. Das
  ist der Grund, warum Prüfpunkt 1 oben steht und nicht unten.
