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
> | Nicht geprüft | **Der Staging-Lauf gegen lima-city** — die Abnahme von AP1. Dazu die beiden Tabellen, die auf **Z3** warten, und die Erreichbarkeit der neuen Anlage. Abschnitt 0 |
> | Funde | **vier** (Abschnitt 2): drei aus der Umsetzung, einer aus einer unabhängigen Gegenlesung durch sieben getrennte Leser. Alle behandelt — einer davon nur halb, und das ist gesagt; zwei Befunde der Gegenlesung bleiben bewusst liegen |
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

**2 — Zwei Tabellen stehen leer: es fehlt Z3.** Der Plattformvergleich
(`docs/Technik.md` 6.3a) und die Variablenwerte (`docs/Rahmenplan.md` 6a)
sind gebaut, aber ohne Zahlen. Die Auskunft beider Anlagen (Betrieb → Status)
und die nicht-geheimen Einrichtungswerte der neuen Anlage (FTP-Wurzel,
`FTP_ZIELPFAD`, `FTP_STATE_PFAD`) sind die Zuarbeit **Z3** und lagen nicht
vor. **Sie sind nicht geschätzt worden**, und das ist eine Entscheidung: Der
Zweck des Vergleichs ist, dass man sich auf ihn berufen kann. Die Zellen
tragen `⬚ Z3`; **Prüfpunkt 2** holt sie ein.

**3 — Ob `staging-nadoku.gen-em.org` antwortet, ist von hier aus nicht
messbar.** Versucht, zweimal, um 09:41:59 UTC: `curl` auf `/login.php` und
auf `/`. Beide Male **`curl: (56) CONNECT tunnel failed, response 403`**,
**0 Byte übertragen**. Die Ursache liegt **nicht** bei der Anlage, sondern an
der Netzpolitik dieser Arbeitsumgebung — der Statusbericht des Vermittlers
nennt beide Versuche wörtlich als `connect_rejected`, *„gateway answered 403
to CONNECT (policy denial or upstream failure)"* für
`staging-nadoku.gen-em.org:443`. **Daraus folgt nichts über den Server:**
weder dass er steht, noch dass er fehlt. Die Schritte 1, 2 und 6 in
Rahmenplan 6a stehen deshalb offen mit dem Vermerk „Stand nicht gemeldet",
und nicht etwa rot.

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

- [ ] **2 — Z3: die zwei leeren Tabellen füllen.**
  *Weg:* **(i)** Auf **beiden** Anlagen *Betrieb → Status* öffnen und die
  Plattformauskunft ablesen: PHP-Fassung, `memory_limit`,
  `max_execution_time`, `post_max_size`/`upload_max_filesize`, OPcache,
  Datenbankfassung, `max_user_connections`, freier Platz, Cron, FTPS, Herkunft
  des Zertifikats. **(ii)** Für Staging (lima-city) und Produktiv je nennen:
  welche Wurzel das FTP-Konto sieht, welchen Wert `FTP_ZIELPFAD` trägt und
  welchen `FTP_STATE_PFAD`.
  *Erwartet:* Zwei ausgefüllte Tabellen — `docs/Technik.md` 6.3a und
  `docs/Rahmenplan.md` 6a. **Keine Zugangsdaten**, nur Variablenwerte.
  *Scheitern erkennbar an:* Eine Zelle, die niemand ablesen kann. Dann gehört
  **„unbekannt" hinein und nicht ein Schätzwert** — `plattform_pruefen()`
  kennt dafür ausdrücklich den dritten Wert *nicht feststellbar* (Technik 5b.1).

- [ ] **3 — `FTP_ZIELPFAD` und `FTP_STATE_PFAD` in beiden Umgebungen
  ausdrücklich setzen** (Zuarbeit Z4, vorgezogen — der Grund ist AP1).
  *Weg:* GitHub → Settings → Environments → `staging` bzw. `produktion` →
  *Environment variables*.
  *Erwartet:* Beide Namen stehen in **beiden** Umgebungen mit einem Wert.
  *Scheitern erkennbar an:* Nichts fällt auf — und das ist der Punkt. Fehlt
  die Variable, greift der **Vorgabewert** (`./staging/`, `./httpdocs/`) und
  die Kette lädt in ein Verzeichnis, das vielleicht das falsche ist, **ohne
  eine Meldung**. Erst AP6 nimmt die Vorgaben weg (E-KH-07); bis dahin ist
  dieser Punkt die einzige Sicherung.

- [ ] **4 — Ist `staging-nadoku.gen-em.org` von außen erreichbar?**
  Aus der Arbeitsumgebung heraus nicht messbar (Abschnitt 0, Punkt 3).
  *Weg:* Im Browser `https://staging-nadoku.gen-em.org/login.php` öffnen.
  *Erwartet:* Die Anmeldeseite dieser Anwendung, mit gültigem Zertifikat.
  *Scheitern erkennbar an:* Eine Standardseite des Hosters mit **HTTP 200**
  ist der gefährliche Fall — sie sieht nach „läuft" aus und ist leer.
  Kennzeichen: **die Fußzeile mit der Versionsnummer fehlt**. Zertifikatsfehler
  heißt, HTTPS beim neuen Hoster ist nicht eingerichtet (6a, Schritt 1).

- [ ] **5 — Der alte `JOBS_TOKEN` in der Umgebung `staging` gehört der alten
  Anlage.**
  *Weg:* Auf der **neuen** Staging-Anlage *Betrieb → Hintergrundjobs* öffnen,
  den Wert hinter `jobs.php?token=` ablesen und in der GitHub-Umgebung
  `staging` als `JOBS_TOKEN` eintragen.
  *Erwartet:* Der Wert ist ein **anderer** als der bisher eingetragene.
  *Scheitern erkennbar an:* Ist er gleich, ist etwas falsch — das Token gehört
  der Installation. Bleibt der alte stehen, laufen die Kreisläufe **ohne
  Job-Pause** und messen „hat der Verdichtungsjob dazwischen zugeschlagen"
  statt „kommt zurück, was hineinging" (gemessen: 125 verdichtete Spuren in
  einem Lauf ohne Pause).

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
