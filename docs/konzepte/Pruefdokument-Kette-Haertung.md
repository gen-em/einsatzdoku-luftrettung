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
> | Stand | 20.09.2026 — **AP1 gebaut (Abnahme offen), AP2 gebaut, AP3 gebaut.** AP4 bis AP8 nicht begonnen |
> | Geprüft | Maschinell: Wortliste, Kettenaufrufe samt aller Selbstproben (Tor **19**, Zielprobe **67**, Wache **38**), YAML-Gültigkeit, Zählung der Fundstellen. Gefahren: **ein Kettenlauf gegen lima-city**, **acht Probeläufe gegen Produktiv**. Zahlen in Abschnitt 1 |
> | Nicht geprüft | **Der Staging-Lauf gegen lima-city** — die Abnahme von AP1; er bleibt am Botschutz hängen (F-KH-U-10). Dazu **die Mengenprobe gegen Produktiv** (Prüfpunkt 17) — sie ist gebaut und selbstgeprüft, aber nie gegen einen echten Server gelaufen. Abschnitt 0 |
> | Funde | **19** (Abschnitt 2): F-KH-U-01 bis F-KH-U-20 **ohne 07** — diese Nummer ist nie vergeben worden, die Lücke bleibt offen, weil Nummern dauerhaft sind. Zuletzt **F-KH-U-20: Auch der Weg zum Datenkanal ist ausgeschlossen; übrig bleibt die Sitzung selbst** |
> | F3 | **eingegrenzt, nicht benannt.** Sechs Vermutungen sind mit je einer Messung ausgeschlossen (Tabelle bei F-KH-U-20). **E-KH-09 (Pflichtstopp) steht** |
> | Prüfliste | 21 Punkte: **6 abgehakt**, 5 teilweise, **10 offen** |
> | Prüfumgebung | Wegwerf-Container ohne Netzzugang zu den Anlagen (Abschnitt 0, Punkt 3); Python 3 für die Prüfmittel; **keine** lokale Installation nötig, weil kein Paket Web-Code anfasst |

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

**1a — Der Handlauf der Wache gegen Produktiv läuft aus der Umsetzung
heraus nicht.** `https://nadoku.gen-em.org` antwortet dem Egress-Proxy des
Containers mit `403 Tunnel connection failed` — **gemessen an 128 von 128
Dateien**. Die Wache hat das korrekt gemeldet („128 nicht erreichbar",
Rückgabewert **1**) und nicht still grün. Die Abnahme von AP2 verlangt diesen
Handlauf; er steht als **Prüfpunkt 8**.

**1b — Und selbst wenn er liefe, belegte er heute nichts.** Die Abnahme von
AP2 will ihn *„während `main` dem Zeiger voraus ist — damit ist der tägliche
Falschalarm belegt beseitigt."* `main` **ist** voraus (12 Commits,
`7150793` → `862ca7f`), aber **in nichts, was die Wache misst**: Sie misst
`server/assets/` und `login.php`;
`git diff --name-only origin/produktion origin/main -- server/assets/` liefert
**0**, und `login.php` ist unverändert. Ein grüner Lauf wäre heute auch ohne
die Änderung grün. **An seine Stelle tritt die Nachrechnung aus den Ständen**
(Abschnitt 1.5) — sie misst genau den Fall, der B6 war, und braucht kein Netz.
Der Handlauf bleibt als Prüfpunkt stehen, fällig beim nächsten Stand mit einer
Änderung unter `assets/`.

**1c — Der Job `zeiger` ist gebaut und nicht gelaufen.** Er feuert nur nach
einem erfolgreichen `produktion`-Job, und den gab es noch nie (Abschnitt 1.2
des Konzepts). So steht es auch in der Abnahme von AP2: *„Der Zeiger-Job ist
gebaut, nicht gelaufen — gemessen wird er mit M1."* Was ohne ihn geprüft ist:
YAML-Gültigkeit, Abhängigkeit, Bedingung und Berechtigungen (Abschnitt 1.5).
Was nicht: dass der Push tatsächlich durchgeht.

**1d — Der Probelauf und der Trennversuch sind nicht gefahren.** Beide
brauchen die Umgebung `produktion` und damit die Pflichtfreigabe der
Betreiberin; der Trennversuch braucht zusätzlich, dass keine zweite
FTP-Sitzung offen ist. **Die Abnahme von AP3 steht damit aus** — Prüfpunkte
10 bis 12. Was aus der Umsetzung heraus messbar war, ist gemessen: die
Selbstproben (17 und 26 Lagen) und **Z5 Teil 1**, das Läuferabbild und
Node-Fassung als F3-Ursache ausschließt (Abschnitt 1.6).

**1e — Und der Probelauf gegen Staging hängt am selben Nagel wie AP1.** Die
Zielprobe liest ihre Probedatei über **HTTPS** zurück, und genau den Weg
weist lima-citys Botprüfung mit `403` ab. Ob sie auch eine statische Datei
abweist oder nur PHP-Seiten, **ist nicht gemessen** — das entscheidet der
erste Lauf. Trifft es zu, ist die Anfrage an lima-city nicht nur AP1s
Blocker, sondern auch AP3s.

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

### 1.4 Der erste Kettenlauf gegen lima-city (Lauf 21, 20.09.2026)

Handlauf (`workflow_dispatch`) auf `claude/fervent-dirac-xirsqw` (`c97c1cf`),
14:34:28–14:35:22 UTC. **Kein Push auf `main`** — die Kette lässt sich von
jedem Zweig auslösen, `produktion` kann dabei nicht anspringen
(`if: startsWith(github.ref, 'refs/tags/web-v')`, **gemessen: übersprungen**).

| Job / Schritt | Ergebnis |
|---|---|
| `staging` — Geheimnisse da? | **grün** |
| `staging` — `doku`-Kopie | **grün** |
| `staging` — **FTPS-Abgleich** | **grün**, 12 s |
| `stufe2` — `login.php` | **grün, gemessen** |
| `stufe2` — Punktdateien | **grün, gemessen** |
| `stufe2` — Kreisläufe csv/edbak | **ROT** |
| `stufe2` — Bilderlauf | nicht gelaufen (Vorgänger rot) |
| `stufe2` — Messstand | nicht gelaufen |
| `produktion` | **übersprungen**, wie vorgesehen |

**Die Zahlen des Abgleichs:** `Making changes to 12 files/folders` ·
**`Uploading: 0 B — Deleting: 0 B — Replacing: 1.13 MB`** · Verbindung 1,9 s ·
Übertragung 8,9 s (127 kB/s) · gesamt 12 s. Ersetzt wurden
`api/export_data.php`, `api/gpx_import.php`, `api/import_commit.php`,
`api/pat_anheben.php`, `api/schneiden.php`, `backup_lib.php`, `db.php`,
`einsatz_form.php`, `ingest.php`, `migration_lib.php`, `schema.sql`,
`version.php`.

**Drei Fragen sind damit beantwortet, die vorher offen standen:**

**(1) lima-city verträgt das `../` der Zustandsdatei.** Der Abgleich meldet
wörtlich `Saving current server state to "/../.deploy-state-staging.json"` und
endet grün. Die Frage aus Rahmenplan 6a ist für **Staging** erledigt.
**Für Produktiv bleibt sie offen** — dort hat noch kein Lauf so weit
gereicht. *Was dieser Lauf NICHT sagt:* ob die Datei wirklich über dem
Webroot liegt oder ob der Server `/..` auf `/` zurückfaltet. Der
Punktdatei-Schritt entscheidet das nicht, weil `RewriteRule [F]` **403
antwortet, ob die Datei da ist oder nicht**.

**(2) F3 ist eingegrenzt — und es liegt nicht an der Aktion.** Dieselbe
Fremd-Aktion, derselbe Tag-Commit, dasselbe Muster (Konto auf `/`
eingesperrt, `FTP_ZIELPFAD = /`, Zustandsdatei über die Vorgabe mit `../`)
läuft gegen lima-city **ohne `ECONNRESET`** durch. Der Abbruch bei
`ensureDir('api/')` am 20.09. gegen Produktiv ist damit **kein allgemeiner
Fehler der Bibliothek und keine Eigenschaft dieser Anordnung**, sondern
hängt am Produktiv-Server oder an dessen Konto. Das schärft den
Trennversuch in AP3 und ist eine Eingabe für **E-KH-09**.

**(3) „Übersprungen zählt als grün" (B5) ist für die zwei gelaufenen
Prüfschritte ausgeschlossen** — beide haben gemessen, mit Ausgabe:
`login.php: HTTP 200 · gelandet bei https://staging-nadoku.gen-em.org/login.php`
und `Staging liefert die Anmeldeseite der Anwendung aus.` (die Prüfung sucht
`AGPL-3.0` in der Fußzeile, eine Hoster-Standardseite fällt also auf);
`.ftp-deploy-sync-state.json` **403**, `.deploy-state-staging.json` **403**,
`.env` **403**, `.git/config` **403**,
`.well-known/acme-challenge/kettenpruefung` **404**.

**(4) Nebenbefund zu E-KH-20:** `Deleting: 0 B`. Die Fremd-Aktion hat
nichts gelöscht — der Satz aus E-KH-20 („sie löscht allein, was sie selbst
früher hochgeladen hat") ist damit am laufenden Lauf belegt und nicht nur
gelesen.

**Der rote Schritt:** Kreislauf `edbak` bricht ab mit

```
RuntimeError: Anmeldung gescheitert: unbekannt
  kreislauf.py:113  konto_loeschen  →  Sitzung(basis).anmelden(*admin)
```

**`JOBS_TOKEN` stimmt** — die Job-Pause hat zweimal geantwortet
(`{"ok": true, "aktion": "pause", "sekunden": 1800, … "Jobs angehalten bis
2026-09-20 15:05:18 UTC."}` und `{"ok": true, … "Jobs laufen wieder."}`).
Der Wert gehört also der **neuen** Anlage. Es scheitert allein die Anmeldung
mit `STAGING_KONTO` / `STAGING_PASS`.

> **„unbekannt" ist hier eine Auskunft und keine Lücke.**
> `sitzung.py`:130 wirft den Fehler, wenn nach dem POST die Adresse noch
> `login.php` enthält **und** die Seite kein „Abmelden" trägt; das Wort
> `unbekannt` steht dort, weil `fehlertext()` **keine Fehlermeldung** auf der
> Seite gefunden hat. **Wären die Zugangsdaten schlicht falsch, stünde dort
> eine Meldung.** Die Anmeldeseite kam also *stumm* zurück. Das deutet eher
> auf eine Zwischenseite, ein verworfenes Formular-Token oder eine Sitzung,
> die nicht hält, als auf ein vertipptes Passwort — **belegt ist keines
> davon.** Prüfpunkt 5 nennt den Bedienweg, der es in einer Minute
> entscheidet.

### 1.5 AP2 — Zeiger und Wache

**Maschinell, ohne Netz:**

| Mittel | Soll | Ist |
|---|---|---|
| `wache.py --selbstprobe` | 0 nicht erfüllt | **38 Erwartungen, 0 nicht erfüllt** (vorher 32; sechs neue zum Vergleichsstand) |
| YAML der drei Arbeitsläufe | laden | **3 von 3**, Jobs `staging`/`stufe2`/`produktion`/**`zeiger`** |
| `tools/kettenaufrufe/pruefen.py` | 0 Befunde, 0 ungeprüft | **30 Aufrufe, 0, 0**; Selbstprobe **10/10** |
| `tools/wortliste/wortliste.py` | 0/0/0 | **0 Treffer außerhalb, 0 ungenutzte Ausnahmen, 0 Fallen** |
| `python3 -c ast.parse` auf `wache.py` | lädt | lädt |

**Die sechs neuen Fälle der Selbstprobe** (sie sind die Gegenprobe zur Zusage,
nicht ihre Wiederholung):

1. Ein Pfad, den es nicht gibt → `VergleichsstandFehlt`
2. Ein Verzeichnis ohne `server/` → `VergleichsstandFehlt`
3. Nach `stand_setzen()` zeigt `SERVER` tatsächlich dorthin
4. Und `assets()` liest dort — **1 Datei** statt der 128 des Repositoriums
5. Ein gekipptes Byte im Vergleichsstand ergibt eine andere SHA-256
6. Zurückgestellt misst sie wieder das Repositorium — **128 Dateien**

**Der Befund B6, aus den Ständen nachgerechnet** (ohne Netz; Produktiv am
18.09.2026 `14f99ac`, `main` `eec41e1`):

| | Dateien | gleich | abweichend | 404 |
|---|---|---|---|---|
| alt (Vergleichsstand `main`) | 128 | 121 | **1** (`assets/style.css`) | **6** |
| neu (Vergleichsstand Zeiger) | 122 | **122** | 0 | 0 |

Die sechs: `doku.js`, `rueckfrage.js`, `schluessel.js`, `schluesselblatt.js`
und zwei Symbole — Dateien aus P5b, damals noch nicht ausgeliefert.

> **Die protokollierte Zahl war „2 abweichend", die Nachrechnung ergibt 1.**
> Kein Widerspruch: Die zweite lag in **Teil 2**. `login.php` ist zwischen
> `14f99ac` und `eec41e1` um **128 Zeilen** gewachsen
> (`git diff --stat`), und Teil 2 vergleicht dessen Skript- und
> Formularmenge. Die Konzeptzahl fasste beide Teile zusammen.

**Der Kopf der Wache nennt jetzt den Stand.** Gemessen am ausgelegten Zeiger:

```
Integritaetswache gegen https://nadoku.gen-em.org
Vergleichsstand:  /tmp/…/zeiger — 7150793 (web-v20.24.2)
  Teil 1  128 Dateien unter assets/ — …
```

Commit, Tag und Dateizahl, wie E-KH-13 es verlangt. Die Kennung kommt aus
`git -C <stand>` und **nicht** aus einer Beschriftung des Aufrufers: Eine
Beschriftung wäre eine zweite Stelle, die veralten kann, und eine falsche
beglaubigte einen Vergleich, den es so nicht gab.

**Durch Lesen belegt** (nicht gelaufen):

- Der Job `zeiger` hängt an `needs: produktion` **und**
  `if: needs.produktion.result == 'success'`. Die zweite Zeile ist nicht
  überflüssig: Ein übersprungener Job gilt GitHub als erfüllte Abhängigkeit,
  und ohne sie bewegte **jeder Push auf `main`** den Zeiger.
- `contents: write` steht **nur** in diesem Job; `staging`, `stufe2` und
  `produktion` erben `read` aus dem Kopf der Datei. Nachgezählt: eine
  `permissions`-Angabe auf Jobebene im ganzen Lauf.
- `actions: read` ist in `integritaet.yml` ausgetragen, weil der einzige
  Verbraucher (der `gh api`-Aufruf des Riegels) weg ist.
- Im Job `zeiger` steht außer `actions/checkout` keine Fremd-Aktion.

### 1.6 AP3 — Tor, Zielprobe, Probelauf

**Maschinell, ohne Netz:**

| Mittel | Soll | Ist |
|---|---|---|
| `tor.py --selbstprobe` | mindestens 16 Lagen, 0 offen | **19 Lagen, 0 offen** (11 → 17 → 19; die zwei letzten zur zweiten Runde und zur Serverzeit) |
| `zielprobe.py --selbstprobe` | eigene Selbstprobe, 0 offen | **67 Lagen, 0 offen** (26 → 29 → 33 → 37 → 45 → 50 → 67; jede Stufe ist ein Fund, den sie selbst gefunden hat) |
| `tools/kettenaufrufe/pruefen.py` | 0 Befunde, 0 ungeprüft | **34 Aufrufe, 0, 0**; Selbstprobe 10/10 |
| YAML der drei Arbeitsläufe | laden | **3 von 3** |
| `wache.py --selbstprobe` | 0 offen | **38 Erwartungen, 0 offen** (AP2: 32 → 38) |
| `jobregister/pruefen.php` | 0 Befunde | **0 Befunde**; Selbstprobe **9 von 9** |
| `tools/wortliste/wortliste.py` | 0/0/0 | **0/0/0** (99 Ausnahmen, 99 gegriffen, 0 ungenutzt) |

**Die sechs neuen Lagen des Tors** (F1, E-KH-05/-19):

1. Fremder Auftrag offen → **zweite Runde** → Tor offen
2. Nach zwei Runden kein frischer Stand → Tor zu
3. Serveruhr 120 s **nach** → Tor offen
4. Serveruhr 120 s **vor** → Tor offen
5. Antwort ohne `Date`-Kopf → Abbruch (kein Rückfall auf die Läuferuhr)
6. Antwort ohne `fertig` und ohne `error` → definierter Abbruch —
   **und die Gegenprobe:** *eine* solche Antwort ist ein Schluckauf und
   tötet den Lauf nicht

3 und 4 sind der Kern von E-KH-05 (1): Beide Zeiten kommen jetzt aus
**derselben** Uhr, deshalb ändert ein Versatz nichts. Vorher wies Fall 3
einen gültigen Stand ab — mit zwei Zahlen, die beide richtig aussahen.

**Z5 Teil 1 — erledigt, und es ist ein Ausschluss.** Die Zuarbeit sah die
Betreiberin vor; die Angaben stehen im Kopf jedes Jobprotokolls und sind über
die API erreichbar.

| | grün, 18.09. (`35341712345`) | rot, 20.09. (`35499422433`) |
|---|---|---|
| Läuferfassung | 2.337.0 | **2.337.0** |
| Abbild | ubuntu-24.04 / 20260907.300.1 | **ubuntu-24.04 / 20260907.300.1** |
| Provisioner | 20260828.587 | **20260828.587** |

**Identisch — Läuferabbild und Node scheiden als F3-Ursache aus.**

**Dazu ein Befund, der die Frage verengt** (aus demselben Protokoll, wörtlich):

```
Making changes to 708 files/folders to sync server state
Uploading: 11.7 MB -- Deleting: 0 B -- Replacing: 0 B
creating folder "api/"
Error: Client is closed because read ECONNRESET (data socket)
    at Client.sendIgnoringError (…/index.js:4236:25)
    at Client._openDir (…/index.js:4763:20)
```

`_openDir` listet **auf dem Datenkanal**. Alles davor läuft über den
Steuerkanal und gelingt. **Der Steuerkanal steht; die erste Datenverbindung
wird abgeschnitten.** Das ist die Signatur von gesperrten Passiv-Ports oder
einer Forderung nach Wiederverwendung der TLS-Sitzung — genau die zwei Dinge,
die die Zielprobe in ihren zwei Betriebsarten trennt.

**Durch Lesen belegt** (nicht gelaufen):

- Im Probelauf sind **sechs** Schritte gesperrt (Tag, Tor der grünen Läufe,
  Backup-Tor, Wartung, `doku`-Kopie, Migrationsabfrage) und der Abgleich läuft
  als `dry-run`. Nachgezählt an der YAML-Struktur, nicht am Text.
- Die Zielprobe steht **vor** dem Backup-Tor und **ohne** `if` — sie läuft in
  beiden Fällen, Probelauf wie Auslieferung.
- Ihre Selbstprobe läuft im selben Schritt davor, jedes Mal.

---

## 2. Funde aus der Umsetzung

**F-KH-U-20 — Der Datenkanal ist sauber; übrig bleibt die Sitzung selbst.**
*Lauf 35538191205, 20.09.2026.*

```
Rundlauf 1: flach                        → Datenkanal: EPSV, Antwort 229, Port 50465 · gelungen
Rundlauf 2: DURCH EIN NEUES VERZEICHNIS  → Datenkanal: EPSV, Antwort 229, Port 63930 · gelungen
```

**Kein Rückfall auf `PASV`, keine gescheiterte `EPSV`-Aushandlung, zweimal
eine saubere 229 mit Port.** Damit fällt auch F-KH-U-19 als Erklärung: Der
Weg zum Datenkanal ist es nicht.

**Die Liste der ausgeschlossenen Ursachen ist jetzt vollständig genug, um
den Rest zu benennen.** Ausgeschlossen — jedes mit einer Messung, nicht mit
einem Argument:

| Vermutung | ausgeschlossen durch |
|---|---|
| Läuferabbild, Node-Fassung | Z5 Teil 1 — beide Läufe 2.337.0 / ubuntu-24.04 20260907.300.1 |
| FTPS-Zertifikat | F-KH-U-08, von der Betreiberin behoben; Verbindung kommt zustande |
| die Bibliothek als solche | lima-city läuft mit derselben Aktion grün |
| TLS-Sitzungswiederverwendung | F-KH-U-16 — vier Läufe, beide Betriebsarten gelingen |
| Anlegen und Auflisten eines Verzeichnisses (`ensureDir`/`_openDir`) | F-KH-U-18 — beide Rundläufe gelingen |
| Weg zum Datenkanal (`EPSV`/`PASV`) | dieser Fund — zweimal sauberes `EPSV` |

**Was übrig bleibt, ist die Bauform der Probe selbst.** Die Zielprobe ruft
`curl` **je Operation einmal** auf: jede Operation eine eigene
Steuerverbindung, eine eigene Anmeldung, ein eigener TLS-Aufbau. Die
Auslieferungsaktion hält **eine** Verbindung offen und fährt 688 Dateien und
62 Verzeichnisse darüber.

Das ist keine Feinheit. Ein Server, der die zweite oder dritte
Datenverbindung **einer** Sitzung abweist — eine Zeitgrenze, ein erschöpfter
Portbereich, `MaxConnectionsPerHost`, ein Ratenschutz —, **sieht in der
Zielprobe wie ein gesunder Server aus**. Sie fragt ihn ja jedes Mal neu. Ein
grüner Rundlauf hat über diese Klasse von Ursachen nie etwas gesagt, und das
war bisher nirgends aufgeschrieben.

*Gebaut:* `--mengenprobe N` (1–500) in derselben Datei. **Ein** `curl`-Aufruf
mit `N` Zielen, `--ftp-create-dirs`, eine Sitzung. Gemeldet werden der Weg
zum Datenkanal, die Zahl der abgeschlossenen Übertragungen gegen die
verlangte (**„2 von 5" ist das Ergebnis, auf das es ankommt**) und bei
Abbruch die letzten 40 Zeilen der Servermeldung wörtlich. Aufgeräumt wird im
`finally`; was übrigbleibt, wird gezählt und benannt.

*Warum sie in keinem Kettenschritt steht:* Sie legt bis zu 500 Verzeichnisse
auf einem echten Server an. Das ist ein Werkzeug der Fehlersuche, kein
Schritt der Auslieferung — es wird von Hand ausgelöst und nicht nebenbei.

*Zwei Lagen der Selbstprobe tragen sie:* dass **alle Ziele in EINEM
`curl`-Aufruf** stehen (zerfiele sie in viele, wäre sie eine teurere Fassung
des Rundlaufs und könnte den Unterschied nie zeigen), und dass ein Abbruch
mittendrin rot ist, die Zahl nennt, den Servertext zeigt **und trotzdem
aufräumt**. Dazu die Grenzprobe: `--mengenprobe 0`, `501` und `-1` werden
abgewiesen, nicht gefahren.

*Nebenbei behoben, von der Selbstprobe gefunden:* `--mengenprobe 0` hätte
still die Rundläufe gefahren statt abzuweisen (`default=0` ist von „nicht
angegeben" nicht zu unterscheiden — jetzt `default=None`), und ein Abbruch
schon beim Schreiben der Quelldatei hätte hinter dem `finally` einen
`NameError` gegeben statt eines Befunds.

Selbstprobe **50 → 67 Lagen**.

**E-KH-09 bleibt stehen.** F3 ist eingegrenzt, nicht benannt. Was die
Mengenprobe messen kann, muss sie erst gegen Produktiv gemessen haben —
**Prüfpunkt 17**.

**F-KH-U-18 — Auch `ensureDir` ist es nicht: Beide Rundläufe gelingen.**
*Lauf 35537674485, 20.09.2026, 21:07 UTC.*

```
Rundlauf 1: flach        → gelungen (TLS-Sitzung wiederverwendet: JA)
Rundlauf 2: DURCH EIN NEUES VERZEICHNIS (`ensureDir`-Nachstellung)
  Probedatei: zielprobe-31d74c4f9832abe0/probe.txt
  Neues Verzeichnis aufgelistet (die `_openDir`-Stelle).
  HTTPS-Abruf … → 200 · Inhalt stimmt überein · gelöscht · Verzeichnis entfernt · 404
BEIDE Rundläufe gelungen — flach UND durch ein neues Verzeichnis.
```

**Anlegen, Hineinschreiben und Auflisten eines neuen Verzeichnisses
funktionieren** — genau die Folge, an der die Auslieferungsaktion mit
`ECONNRESET` abbricht. Damit fällt auch diese Erklärung.

**F-KH-U-19 — Und die interessanteste Zeile stand nie im Protokoll.**
`curl --verbose` schreibt, **wie** die Datenverbindung zustande kam — `EPSV`,
`PASV`, oder `PASV` erst **nach** einem EPSV-Fehlschlag. Die Zielprobe gab
diese Ausgabe aber nur **im Fehlerfall** aus. Vier grüne Läufe haben die
Auskunft verschluckt, auf die es jetzt ankommt.

**Warum sie jetzt ankommt:** Ausgeschlossen sind Läuferabbild, Node-Fassung,
Zertifikat, TLS-Sitzungswiederverwendung und die Verzeichnisanlage. Was
zwischen `curl` und der Auslieferungsaktion noch verschieden sein **kann**,
ist der Weg zum Datenkanal. **`curl` versucht `EPSV` und fällt bei
Fehlschlag selbsttätig auf `PASV` zurück.** Eine Bibliothek, die das nicht
tut, bliebe an derselben Stelle hängen — und das sähe von außen aus wie ein
`ECONNRESET` auf der ersten Datenverbindung.

*Behoben:* `datenkanal()` liest die Auskunft aus der ausführlichen Ausgabe
und steht jetzt in **jedem** Lauf neben der Sitzungszeile — dreiwertig wie
diese, mit „nicht feststellbar" statt einer Vermutung. Fünf Lagen der
Selbstprobe halten die Fälle fest, darunter der Rückfall als eigener Fall.

Selbstprobe **45 → 50 Lagen**.


**F-KH-U-16 — Der Trennversuch ist gefahren: Die TLS-Sitzungswiederverwendung
ist NICHT die Ursache von F3.** *Vier Läufe am 20.09.2026, je zweimal.*

| Lauf | Betriebsart | Sitzung gemessen | Rundlauf |
|---|---|---|---|
| 35536468961 | MIT | **JA** | gelungen |
| 35536496276 | MIT | **JA** | gelungen |
| 35536520348 | OHNE (`--no-sessionid`) | nicht feststellbar | **gelungen** |
| 35536542649 | OHNE (`--no-sessionid`) | nicht feststellbar | **gelungen** |

Nach der Deutungstabelle von Prüfpunkt 12: *beide gelingen → die Forderung
des Servers ist es nicht.* **Die wahrscheinlichste Erklärung für F3 ist damit
ausgeschlossen.**

**Dass der Schalter gewirkt hat, zeigt der Vergleich, nicht die
Einzelmessung.** Bei MIT schreibt dasselbe `curl` gegen denselben Server
`SSL re-using session ID`; bei OHNE schweigt es. Deshalb dort „nicht
feststellbar" — es gibt nichts zu melden, wenn nichts wiederverwendet wird.
**Der Unterschied ist belastbar; „OHNE" für sich genommen belegt nichts.**

**F-KH-U-17 — Und die Erklärung, warum vier grüne Läufe nichts über F3 sagen:
Die Probe hat die kranke Stelle nie angefasst.**

Gemessen hat sie: `LIST` auf das **bestehende** Zielverzeichnis, `STOR` einer
48-Byte-Datei, `DELE`. Alles über den Datenkanal, alles grün, in beiden
Betriebsarten.

Die Auslieferungsaktion stirbt aber hier:

```
creating folder "api/"
  at Client._openDir → Client.ensureDir → ECONNRESET (data socket)
```

`ensureDir` legt ein Verzeichnis an, **das es noch nicht gibt**, und listet
es danach. Der flache Rundlauf schreibt in eines, das schon da ist. **Zwei
verschiedene Operationsfolgen — und die Probe maß die gesunde.**

*Behoben im selben Paket (auf Weisung des Auftraggebers vorgezogen):* Die
Zielprobe fährt jetzt **zwei** Rundläufe, immer beide. Der zweite legt ein
Verzeichnis an (`--ftp-create-dirs`), schreibt hinein, **listet es auf** —
das ist die `_openDir`-Stelle —, holt über HTTPS zurück und räumt Datei und
Verzeichnis weg. Scheitert das Auflisten, nennt die Meldung `_openDir` beim
Namen und sagt, dass der Befund damit auf diese eine Operation eingegrenzt
ist.

Dazu räumt der Anfangs-Aufräumlauf jetzt auch liegengebliebene
Probe**verzeichnisse** weg (erst die Datei darin, dann `RMD` — ein volles
Verzeichnis weist jeder Server ab). Der innere Dateiname ist fest
(`probe.txt`), genau damit das Aufräumen ihn kennt, ohne zu suchen.

Selbstprobe **37 → 45 Lagen**, darunter die Gegenprobe: Scheitert das
Auflisten, ist der Lauf rot **und** die Meldung nennt die Stelle.


**F-KH-U-14 — Der Zeiger ist auf einen Stand gewandert, der nie ausgeliefert
wurde. Das ist ein Fehler der Umsetzung, und zwar derselbe zum zweiten Mal.**
*Lauf 35534695781, 20.09.2026, 20:11 UTC.*

Der Job `zeiger` hing an `if: needs.produktion.result == 'success'`. **Ein
Probelauf ist erfolgreich — er soll ja gelingen.** Also wanderte der Zeiger
auf `9f62d55`, einen Commit, der nie auf dem Server lag.

**Das Bittere steht im Kommentar daneben.** Er sagt wörtlich, wogegen die
Bedingung gebaut ist: *„Ohne diese Zeile bewegte ein Lauf ohne Tag den Zeiger
auf einen Stand, der nie auf Produktiv war — und die Wache verglänge danach
gegen eine Lüge."* Gebaut war sie in AP2 gegen den **übersprungenen** Job; der
Probelauf kam in AP3 und ging durch die Tür daneben. **AP2 und AP3
widersprachen sich, und beim Bauen von AP3 ist es niemandem aufgefallen —
mir am wenigsten.**

**Die Folge, wäre es geblieben:** Die Integritätswache hätte ab 04:17 UTC
Produktiv gegen Web 20.26.0 gehalten, während dort 20.24.2 liegt. Täglicher
Falschalarm — genau der Zustand, den AP2 beseitigt hat.

*Behoben, zweifach:*
1. `if: needs.produktion.result == 'success' && !inputs.probelauf`.
2. **Ein zweiter Riegel an der Stelle, wo der Schaden entsteht:** Der Schritt
   selbst bricht rot ab, wenn er im Probelauf überhaupt anläuft. Wer die
   Bedingung künftig erweitert und die Zeile vergisst, bekommt einen roten
   Lauf statt eines falschen Zeigers.

*Behoben am selben Abend:* Der erzwungene Push auf `produktion` ist dem
Sandkasten dieser Sitzung gesperrt. Die Betreiberin hat den Zweig deshalb
über die Branches-Seite **gelöscht**, und die Umsetzung hat ihn aus einem
Hilfszweig neu angelegt — **nachgemessen auf `7150793`**. Prüfpunkt 14 ist
abgehakt.

**F-KH-U-15 — Der Trennversuch hat nichts gemessen: Den Schalter, den ich
benutzt habe, gibt es nicht.** *Lauf 35534784406, 20:12 UTC.*

```
curl: option --no-ssl-session-reuse: is unknown
```

**Frei erfunden.** `curl` kennt `--no-sessionid` („Disable SSL session-ID
reusing"); `--no-ssl-session-reuse` gibt es in keiner Fassung. Alle Läufe in
der Betriebsart „ohne" haben damit **nichts** gemessen.

**Warum die Selbstprobe es nicht gefunden hat — und das ist die Lehre:** Sie
prüfte, dass die Zeichenkette im **ausgeführten Befehl landet**. Das tat sie
brav. Ob `curl` sie **kennt**, hat niemand gefragt. Das ist genau die
Fehlerklasse, die `CLAUDE.md` 6 für die Kette beschreibt — „ein Schalter, der
still verworfen wird" —, nur war er hier wenigstens laut.

*Behoben, dreifach:*
1. `--no-sessionid` statt der Erfindung.
2. **`--ssl-reqd` steht jetzt in BEIDEN Betriebsarten** und ist richtig
   beschriftet: Es verlangt TLS und hat mit der Wiederverwendung nichts zu
   tun. Bis hierher stand es da, als wäre es der Schalter für „mit"; „mit"
   ist schlicht `curl`s Verhalten ohne Zutun.
3. **`curl_kennt()` fragt das Werkzeug selbst** (`curl --help all`) — in der
   Selbstprobe für beide Schalter, mit der Gegenprobe auf den erfundenen,
   **und vor jedem echten Lauf** in dieser Betriebsart. Kennt dieses `curl`
   den Schalter nicht, bricht die Probe ab, statt in der Vorgabe-Betriebsart
   zu laufen und ein Ergebnis zu melden, das keines ist.

Selbstprobe **33 → 37 Lagen**.

**Was damit für F3 steht:** Die Betriebsart *mit* ist gemessen und gelingt
(F-KH-U-12). Die Betriebsart *ohne* ist **ungemessen** — Prüfpunkt 12 ist
offen und diesmal wirklich fahrbar.


**F-KH-U-12 — Der Zertifikatsfehler ist behoben, und damit fällt F-KH-U-09.**
*Lauf 35532390449, Probelauf um 19:30 UTC, nach Umstellung von `FTP_SERVER`.*

```
Zielprobe gegen https://nadoku.gen-em.org
  Betriebsart:    MIT Wiederverwendung der TLS-Sitzung (--ssl-reqd)
  Reste weggeräumt: 0
  TLS-Sitzung wiederverwendet: JA (gemessen)
  … Probedatei gelöscht.
```

**Zwei Dinge auf einmal.** Erstens: Die FTPS-Verbindung kommt jetzt zustande,
die Probedatei ging hinauf und wurde wieder gelöscht — **Prüfpunkt 13 ist
damit erledigt.** Zweitens, und wichtiger:

**F-KH-U-09 war falsch, und zwar aus einem lehrreichen Grund.** Es hieß dort,
die `curl`-Fassung des Läufers sage nichts über die Wiederverwendung der
TLS-Sitzung. Jetzt sagt dieselbe Fassung **„JA (gemessen)"**. Der Unterschied:
Vorher brach `curl` am Zertifikat ab, **bevor je ein Datenkanal aufgebaut
wurde** — es gab nichts zu berichten. „Nicht feststellbar" war die richtige
Auskunft über einen Lauf, der die Frage nie erreicht hat, und wäre als
„NEIN" eine Lüge gewesen. **Die Dreiwertigkeit hat sich bezahlt gemacht:
Hätte die Probe hier `False` gemeldet, stünde jetzt eine falsche Messung im
Prüfdokument.**

**Folge: Der Trennversuch (Prüfpunkt 12) ist fahrbar.** Und er hat bereits
seine Hälfte: **MIT Wiederverwendung gelingt der Upload** — auf demselben
Server, auf dem die Auslieferungsaktion bei der **ersten** Datenverbindung
mit `ECONNRESET` abbricht (F-KH-U-08-Umfeld, Abschnitt 1.6). Fehlt noch der
Lauf **ohne** Wiederverwendung. Scheitert er, ist die Forderung des Servers
belegt — und damit die wahrscheinlichste Erklärung für F3.

**F-KH-U-13 — Die Zielprobe hat sich mit ihrem eigenen Dateinamen
ausgesperrt.** *Derselbe Lauf.*

```
FEHLGESCHLAGEN: Die Datei liegt im FTP-Ziel, ist aber unter
  https://nadoku.gen-em.org/.zielprobe-….txt nicht abrufbar (Status 403).
  Das FTP-Verzeichnis und die öffentliche Adresse zeigen nicht auf dasselbe.
```

**Die Diagnose war falsch.** FTP-Verzeichnis und öffentliche Adresse zeigen
sehr wohl auf dasselbe — die Datei war nur **gesperrt**. Ursache ist der
Präfix, den ich selbst gewählt habe: `.zielprobe-` beginnt mit einem Punkt,
und `.htaccess` (Z. 64) antwortet auf jeden Pfad mit führendem Punkt mit 403.

**Der Kommentar im Werkzeug hatte es vorhergesagt und trotzdem falsch
gebaut:** Dort stand, der Punkt sei „ein zweiter Riegel neben dem Löschen"
und ein 403 werde ja gemeldet, „statt es für einen fehlenden Upload zu
halten". Gemeldet wurde es — aber als **falscher Zielpfad**, also als Fehler
der Anlage statt als Regel des Servers. **Ein Prüfmittel, das eine richtige
Anlage für falsch erklärt, ist schlimmer als keines.**

*Behoben, zweifach:*
1. Der Präfix ist jetzt **`zielprobe-`** ohne Punkt. Was der Punkt schützen
   sollte, wiegt nichts: 48 Zeichen Zufall, im selben Schritt gelöscht, und
   jeder Lauf räumt Reste des vorigen weg. `PRAEFIX_ALT` nimmt die alten,
   punktierten Reste beim Aufräumen mit — sonst blieben sie unsichtbar
   liegen.
2. **403 wird als Sperre benannt, nicht als falsches Ziel**, samt dem Satz
   „Der Rundlauf ist damit NICHT belegt — aber auch nicht widerlegt".

Selbstprobe **29 → 33 Lagen**, darunter die Gegenprobe, dass bei 403 der Satz
„zeigen nicht auf dasselbe" **nicht** mehr fällt.


**F-KH-U-10 — Die Botprüfung von lima-city ist jetzt IN DER KETTE gemessen,
nicht mehr nur von Hand.** *Lauf 35532390449, 20.09.2026, 19:28 UTC.*

Der Lauf kam vom Arbeitszweig und fuhr damit erstmals die erweiterte
`sitzung.py` (`seitenkennung()`, Web-Werkzeugeintrag vom 20.09.). Statt des
nackten „Anmeldung gescheitert: unbekannt" steht jetzt im Protokoll:

```
Anmeldung gescheitert: kein Meldungstext auf der Seite
 — HTTP 403
 · Adresse https://staging-nadoku.gen-em.org/login.php
 · Titel Dein Browser wird geprüft · lima-city
 · Ueberschrift Dein Browser wird geprüft
 · Cookies der Antwort: keine
```

**Damit ist die Ursache belegt und nicht mehr vermutet.** `403`, eine
Abfangseite des Hosters, kein Sitzungscookie — die Anwendung hat die Anfrage
nie gesehen. Weder Zugangsdaten noch Kontostatus noch Wartungsmodus sind
daran beteiligt; die drei Ausgänge aus `login.php`, die derselbe Fehler hätte
sein können, sind ausgeschlossen.

**Das war der Zweck der Erweiterung, und er ist eingetreten:** Zwei Läufe
hatten zuvor „unbekannt" gemeldet — ein Wort, aus dem niemand etwas
schließen konnte. Der erste Lauf mit dem neuen Werkzeug nennt Status, Titel
und Cookie-Lage in einer Zeile.

**Offen bleibt die Abhilfe**, und sie liegt beim Hoster: Prüfpunkt 5.
**Dieser Befund macht die Anfrage an lima-city zum Engpass für AP1 UND AP3**
— beide brauchen einen HTTPS-Abruf gegen Staging, der durchkommt.

**F-KH-U-11 — Und ein Beleg nebenbei: Die Serveruhr geht durch.** Im selben
Lauf steht `{"ok": true, "aktion": "pause", …, "_serverzeit":
"2026-09-20T19:28:45Z"}`. Der `Date`-Kopf der echten Anlage wird gelesen und
geparst — die Grundlage der F1-Behebung (E-KH-05 (1)) steht damit nicht nur
gegen Attrappen, sondern gegen den Server.

*Dabei fiel ein kleiner Mangel auf und ist behoben:* `_serverzeit` ist **unser**
Feld, nicht das des Servers, und es stand in einer Ausgabe, die als „die
Antwort der Installation" dokumentiert ist. Wer es dort liest, sucht es in
`jobs.php`. `ohne_eigenes()` nimmt es vor jeder Ausgabe heraus; zwei Lagen der
Selbstprobe halten das fest (**17 → 19 Lagen**).


**F-KH-U-08 — Das FTPS-Zertifikat von Produktiv passt nicht zum Hostnamen,
und die Auslieferungsaktion merkt es nicht.** *Gemessen am 20.09.2026 im
ersten Probelauf (Lauf 35531806339).*

`curl` bricht beim Verbindungsaufbau ab:

```
< 220 ProFTPD Server (ProFTPD)
> AUTH SSL
< 234 AUTH SSL successful
* SSL connection using TLSv1.3 / TLS_AES_256_GCM_SHA384
*  subject: CN=<interner Knotenname des Hosters>
*  subjectAltName does not match <FTP_SERVER>
curl: (60) SSL: no alternative certificate subject name matches target host name
```

**Die Verbindung ist verschlüsselt, aber nicht beglaubigt.** Über sie gehen
die FTPS-Zugangsdaten und der vollständige Inhalt von `server/`.

**Die Auslieferungsaktion prüft das nicht** — sie kam mit denselben
Zugangsdaten bis `ensureDir('api/')`, also weit hinter den Punkt, an dem
`curl` abbricht. Das ist der erste Ertrag des **zweiten Clients** (E-KH-07),
und er kommt aus einer unerwarteten Richtung: Nicht die Bibliothek ist
auffällig, sondern das, was sie **nicht** prüft.

*Nicht behoben*, weil die Abhilfe an der Anlage liegt: **Prüfpunkt 13.**

*Was der Befund NICHT ist:* die Antwort auf F3. `curl` kam nicht bis zum
Datenkanal, wo der `ECONNRESET` sitzt. Der Trennversuch braucht erst eine
Verbindung, die zustande kommt — deshalb geht Prüfpunkt 13 dem Prüfpunkt 12
voraus.

**F-KH-U-09 — Auf diesem Läufer lässt sich die Wiederverwendung der
TLS-Sitzung nicht messen.** *Gemessen im selben Lauf.* Die Zielprobe meldete
**„NICHT FESTSTELLBAR"**: Die `curl`-Fassung des Läufers sagt in ihrer
ausführlichen Ausgabe nichts darüber.

**Folge für den Trennversuch:** Die zwei Betriebsarten lassen sich auf diesem
Läufer **nicht unterscheiden** — ein Lauf *ohne* Wiederverwendung belegt
nicht, dass sie unterblieb. Die Dreiwertigkeit hat das gesagt, statt es zu
behaupten; ein `False` hätte den ganzen Trennversuch auf eine Annahme
gestellt. Wer Prüfpunkt 12 fährt, liest diese Zeile zuerst.


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

**F-KH-U-06 — `sitzung.py` kann drei verschiedene Abweisungen nicht
auseinanderhalten, und deshalb heißt jede von ihnen „unbekannt".**
Gefunden beim Suchen nach der Ursache des roten Kreislaufs (Läufe 21 und 22,
20.09.2026).

`sitzung.py`:130 wirft `Anmeldung gescheitert: <fehlertext() oder
"unbekannt">`, wenn nach dem POST die Adresse noch `login.php` enthält und
die Seite kein „Abmelden" trägt. `fehlertext()` sucht die Klasse
**`meldung-fehler`** (und ersatzweise das alte `alert-danger`).

**`login.php` hat drei Ausgänge, die genau dieses Bild erzeugen — und
keiner davon trägt `meldung-fehler`:**

| Zeile | Fall | Was ausgegeben wird |
|---|---|---|
| 401 | **Kontostatus nicht aktiv** (gesperrt, in Karenz, wartend …) | `stoerung_seite_html('Kein Zugang — …')` |
| 417 | **Wartung an und die Rolle darf nicht verwalten** | `wartung_antwort_seite(false)` |
| — | dazu die Verlangsamung des Ratenschutzes, sofern sie dieselbe Bauform nutzt | `stoerung_seite_html()` |

**Gemessen:** `grep -c meldung-fehler server/wartung_lib.php` → **0**. Die
Störungs- und die Wartungsseite bauen auf `<h1>` und `<p class="text">`;
die Meldungsklasse gibt es dort nicht. Beide Seiten antworten außerdem
**auf `login.php` selbst**, ohne Umleitung — die zweite Bedingung des Checks
ist damit ebenfalls erfüllt.

**Das ist genau die Fehlerklasse, vor der `fehlertext()` im eigenen
Kopfkommentar warnt:** *„weil ein nicht gefundener Fehler wie ‚kein Fehler'
aussieht, liefen abgelehnte Formulare als Erfolg durch … die stille
Variante des Fundes F-S2-A, und sie ist die gefährlichere."* Hier läuft
nichts als Erfolg durch — aber das Werkzeug sagt „unbekannt", wo die Seite
im Klartext dasteht, was los ist.

**Behoben am 20.09.2026**, nachdem die drei naheliegenden Ursachen von Hand
ausgeschlossen waren (keine Migration ausstehend, Wartung aus, das Konto ist
**BetreiberIn** und aktiv) und damit feststand, dass Raten nicht weiterführt.
`fehlertext()` liest jetzt als zweiten Versuch `<h1>` samt folgendem
`<p class="text"`>; dazu die neue `seitenkennung()` mit Status, Adresse,
Umleitungskette, Titel, Überschrift und den **Namen** der Cookies — nie ihren
Werten. **Gegenproben, ohne Netz:** Störungsseite jetzt lesbar (vorher
`None`), `meldung-fehler` unverändert, Seite ohne beides unverändert `None`,
Kennung vollständig. Nur `tools/`, also keine Versionsstufe (E-KH-23);
Changelog als `Werkzeug:`-Eintrag nach der Hausform.

**Es ist nicht die Ursache des roten Laufs, sondern der Grund, warum wir sie
nicht sehen.** Der nächste Kettenlauf sagt sie.

---

## 3. Prüfliste für die Betreiberin

Was nur an der laufenden Anlage geht. Je Punkt: der Bedienweg, das erwartete
Ergebnis, und **woran ein Scheitern zu erkennen ist**.

- [~] **1 — Die Abnahme von AP1: der erste Kettenlauf gegen lima-city.**
  **Gefahren am 20.09.2026 als Handlauf** (Lauf 21, Abschnitt 1.4) — **nicht
  bestanden, aber weit gekommen.** `staging` **grün** (12 Dateien, 1,13 MB,
  12 s); in `stufe2` sind **zwei von fünf** Messschritten gelaufen und
  **gemessen grün** (`login.php`, Punktdateien), der dritte ist **rot**
  (Kreisläufe, siehe Punkt 5), die letzten zwei sind deshalb nicht gelaufen.
  **Offen bleibt: alle fünf grün.**
  *Weg für den nächsten Versuch:* GitHub → Actions → „Auslieferung" → **Run
  workflow** → Zweig `claude/fervent-dirac-xirsqw`. Ein Push auf `main` ist
  **nicht nötig** und wäre nach `CLAUDE.md` 8 verfrüht — der Handlauf fährt
  dieselben Jobs in derselben Umgebung; nur der Auslöser ist ein anderer, und
  dass ein `push` auf `main` sie auslöst, ist seit dem 18.09.2026 belegt.
  *Erwartet:* `staging` grün, danach **alle fünf** Messschritte gemessen —
  `login.php`, Punktdateien (4× 403, `.well-known/` 404), Kreislauf csv,
  Kreislauf edbak (je **0 unerklärt**), Bilderlauf (**0/0/0**).
  *Scheitern erkennbar an:*
  **(a)** „ÜBERSPRUNGEN" an einem Schritt — eine Zuarbeit fehlt; der Lauf
  wäre grün und hätte nichts gemessen (Befund B5). **Für die zwei gelaufenen
  Schritte ist das ausgeschlossen**, sie haben Zahlen ausgegeben.
  **(b)** Der Bilderlauf meldet viele Bilder und 0 Überlauf, aber alles zeigt
  die Anmeldeseite → das **Demo-Konto fehlt** (6a, Schritt 8). Grüne Zahl,
  wertlos.
  **(c)** Der FTPS-Abgleich bricht mit `ECONNRESET` bei `ensureDir` ab → das
  wäre F3 auch hier. **Am 20.09. ist er durchgelaufen** — der Fall ist damit
  unwahrscheinlich geworden, aber nicht ausgeschlossen.

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

- [ ] **5 — Warum weist `login.php` das Prüfkonto ab? Drei Kandidaten, eine
  Antwort.**
  *Gemessen:* Lauf 21 **und** Lauf 22 (nach Aktualisierung beider
  Geheimnisse) scheitern gleich — `Anmeldung gescheitert: unbekannt`. **Von
  Hand im Browser geht die Anmeldung mit denselben Daten problemlos**
  (Betreiberin, 20.09.2026). **Damit ist belegt: Es liegt nicht an den
  Zugangsdaten.** `JOBS_TOKEN` stimmt ebenfalls (die Job-Pause antwortet).
  *Warum die Meldung nichts sagt:* **F-KH-U-06** — `login.php` hat drei
  Ausgänge, die alle auf `login.php` selbst antworten und **keine**
  `meldung-fehler`-Klasse tragen. Das Werkzeug kann sie nicht unterscheiden.
  *Weg — drei Blicke auf Staging, jeder schließt einen Kandidaten aus:*
  1. **Steht eine Migration aus / ist die Wartung an?** *Betrieb → Updates*.
     **Das ist mein stärkster Verdacht:** Der Deploy hat unter anderem
     `schema.sql` und `migration_lib.php` ersetzt (Lauf 21, 12 Dateien), und
     der Torwächter schaltet bei ausstehender Migration `wartung.lock`.
     `login.php`:417 weist dann jedes Konto ab, **dessen Rolle nicht
     verwalten darf** — stumm, über die Wartungsseite.
     → Falls ja: `update.php` aufrufen, danach den Lauf wiederholen.
  2. **Welche Rolle trägt das Konto aus `STAGING_KONTO`?**
     *Einstellungen → NutzerInnen*. Kandidat 1 greift nur, wenn es **nicht**
     BetreiberIn oder Administratorin ist. Das würde auch erklären, warum
     Sie selbst hineinkommen und das Werkzeug nicht — **falls Sie sich mit
     einem anderen Konto anmelden als dem hinterlegten.**
  3. **Ist das Konto gesperrt oder in Karenz?** *Einstellungen →
     NutzerInnen*, Status. `login.php`:401 weist jeden Status außer „aktiv"
     mit der Störungsseite ab — ebenfalls stumm.
  *Und eine Bitte zur Gegenprobe:* Die Handanmeldung bitte einmal in einem
  **privaten Fenster** wiederholen. `login.php`:34 leitet eine bestehende
  Sitzung sofort auf `index.php` weiter — wer schon angemeldet ist, prüft
  die Anmeldung nicht.
  *Erwartet:* Einer der drei Blicke zeigt die Ursache.
  *Scheitern erkennbar an:* Alle drei sind unauffällig — dann ist es keiner
  der bekannten Ausgänge, und der nächste Schritt ist, `sitzung.py` die
  Überschrift der Seite ausgeben zu lassen (Abschnitt 4).

- [x] **5a — Liegt die Sitzungsablage von Staging in einem Verzeichnis, das
  andere lima-city-Kunden lesen können? — Gemessen am 20.09.2026: nein.**

  | | |
  |---|---|
  | Pfad | `/home/webpages/tmp` |
  | Auflistbar | **nein** (`scandir()` scheitert) |
  | Rechte | **0773** — `rwx` Eigentümer, `rwx` Gruppe, **`-wx` für alle anderen** |
  | Eigentümer | **UID 0** (root) · die Anlage läuft unter einer eigenen UID |

  **Die Zahl „0 Einträge" ist keine Aussage über das Verzeichnis**, sondern
  die Folge davon, dass `scandir()` gescheitert ist — genau der Fall, vor dem
  `CLAUDE.md` 6 warnt („eine grüne Zahl ist erst dann ein Beleg, wenn sie das
  Gemessene benennt"). Was zählt, sind die Rechte.

  **Es ist geteilt, aber gegen das Auflisten gesperrt — und das ist Absicht.**
  Ein root-eigenes `0773` ohne Leserecht für Fremde ist kein Zufall, sondern
  ein bewusster Zuschnitt des Hosters: *hineinschreiben ja, die eigene Datei
  bei Namen öffnen ja, fremde sehen nein.* Die Container-Anzeichen aus der
  `phpinfo()` (Hex-Hostname, Container-Netz) trugen also **nicht** — die
  Anlagen teilen sich das Verzeichnis sehr wohl. **Die Frage war richtig
  gestellt und die Antwort ist trotzdem beruhigend.**

  **Drei Nachsätze, damit sich niemand zu weit darauf beruft:**
  **(1)** Ohne Leserecht ist eine Sitzungsübernahme durch Auflisten
  ausgeschlossen; Sitzungs-IDs sind 128 Bit und nicht zu raten.
  **(2) Es fehlt das Sticky-Bit** (`0773`, nicht `1773`). Wer dort schreiben
  darf, darf auch Dateien **anlegen** — theoretisch eine untergeschobene
  Sitzungsdatei. **Und `session.use_strict_mode` schützt davor NICHT**: Es
  weist eine Sitzungs-ID ab, die es *nicht gibt* — eine untergeschobene gibt
  es. Praktisch ist der Weg zu, weil `session.use_only_cookies` an ist und
  ein fremder Kunde unter fremder Adresse **kein Cookie für diese Domain
  setzen** kann. Wer sich auf strict mode beruft, beruft sich auf das
  Falsche.
  **(3) Für Produktiv ist dieselbe Frage nicht erhoben**, und für
  Selbsthoster ist sie offen. Die Abhilfe, die das grundsätzlich löst, steht
  als Vorschlag in Abschnitt 4.

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

- [ ] **8 — Die Abnahme von AP2: die Wache gegen den Zeiger, an einem Stand,
  der es beweist.** *Fällig, sobald sich unter `server/assets/` etwas geändert
  hat, das noch nicht ausgeliefert ist* — heute ist der Unterschied zwischen
  Zeiger und `main` dort **0 Dateien**, ein grüner Lauf belegte also nichts.
  *Weg:* GitHub → Actions → **Integritaetswache** → *Run workflow*.
  *Erwartet:* grün, und in der Zusammenfassung stehen drei Dinge — der
  verglichene Commit **mit Tag** (`7150793 (web-v20.24.2)` oder neuer), die
  **Dateizahl** von Teil 1, und „Kein Unterschied".
  *Scheitern erkennt man daran:* Steht dort eine Abweichung, die genau die
  Dateien nennt, die seit der letzten Auslieferung dazugekommen sind, zeigt
  der Zeiger noch auf den falschen Stand — dann ist der Job `zeiger` nicht
  gelaufen. Steht „Der Zeigerzweig 'produktion' fehlt", ist der Zweig gelöscht
  worden; der Lauf sagt dann selbst, wie man ihn anlegt.

- [ ] **9 — Den Zweig `produktion` gegen Pushes von Hand schützen.**
  *Warum:* Er ist ab jetzt die Wahrheit darüber, was auf dem Server liegt. Wer
  ihn von Hand bewegt, macht die Wache nicht blind, sondern zu einer Quelle
  von Falschmeldungen — und das ist schlimmer, weil eine Wache mit
  Falschmeldungen abgeschaltet wird.
  *Weg:* GitHub → Settings → Branches → Add rule für `produktion`: Pushes nur
  für GitHub Actions zulassen, PRs sperren, Löschen sperren.
  *Wichtig:* Der Job `zeiger` schiebt **erzwungen** (ein Zurücksetzen legt
  einen älteren Stand oben auf). Eine Regel, die force-push generell verbietet,
  bricht ihn — die Ausnahme für Actions muss stehen.
  *Scheitern erkennt man daran:* Der nächste Produktivlauf wird im Job
  `zeiger` rot mit `protected branch hook declined`.
  *Warum es nicht gebaut ist:* Das sind Repositoriumseinstellungen, keine
  Datei — aus der Umsetzung heraus nicht setzbar.

- [~] **10 — Probelauf** (Abnahme von AP3, erster Teil). **Gefahren am
  20.09.2026 gegen PRODUKTIV** (Lauf 35531806339, von der Betreiberin) —
  **die Mechanik hält, die Zielprobe ist rot mit Befund.**
  *Gemessen:* „PROBELAUF — was dieser Lauf NICHT tut" gelaufen; Tag, Tor der
  grünen Läufe, Backup-Tor, Wartung, `doku`-Kopie, FTPS-Abgleich und
  Migrationsabfrage **übersprungen**; Job `zeiger` **übersprungen**, weil
  `produktion` rot war. **Kein Byte auf Produktiv.** Selbstprobe der
  Zielprobe im Lauf: **26 Lagen, 0 offen**.
  *Offen bleibt:* ein Probelauf, bei dem die Zielprobe **grün** wird — das
  hängt an Prüfpunkt 13. Und einer gegen **Staging**, der die Botprüfung von
  lima-city trifft (siehe unten).
  *Alter Text zum Weg:*
  *Weg:* GitHub → Actions → „Auslieferung" → **Run workflow** → Zweig wählen
  → **Häkchen bei `probelauf`** → starten. Die Umgebung `produktion` fragt
  nach deiner Freigabe; das ist richtig so.
  *Erwartet:* Die Zusammenfassung beginnt mit **„PROBELAUF — nichts
  ausgeliefert"**; die Zielprobe meldet „Rundlauf gelungen: geschrieben,
  abgerufen, verglichen, gelöscht, danach 404"; der Abgleich sagt, was er
  täte, und überträgt nichts.
  *Scheitern erkennt man daran:* Meldet die Zielprobe „Die Datei liegt im
  FTP-Ziel, ist aber unter … nicht abrufbar", zeigen FTP-Verzeichnis und
  öffentliche Adresse **nicht auf dasselbe** — das ist der Fund, für den es
  sie gibt. Ein `403` statt `404` beim Abruf ist etwas anderes: dann greift
  eine Sperre (bei Staging die Botprüfung von lima-city), und die Probe
  belegt nichts.
  **Warnung:** Genau das ist wahrscheinlich. Die Botprüfung weist HTTPS mit
  `403` ab; ob sie auch eine statische Datei abweist, weiß niemand. **Dieser
  Punkt hängt damit am selben Nagel wie Prüfpunkt 1.**

- [x] **13 — Das FTPS-Zertifikat von Produktiv in Ordnung bringen** — **ERLEDIGT am 20.09.2026:** `FTP_SERVER` umgestellt, die Verbindung kommt zustande, die Probedatei ging hinauf und wurde gelöscht (F-KH-U-12). Der Text unten bleibt als Beschreibung des Wegs stehen.
  **Ursprünglich:** (F-KH-U-08,
  gemessen im ersten Probelauf am 20.09.2026, Lauf 35531806339).
  *Befund:* Der FTPS-Server weist sich mit einem **anderen Namen** aus als
  dem, der in `FTP_SERVER` steht (`curl: (60) SSL: no alternative certificate
  subject name matches target host name`). Die Verbindung ist verschlüsselt,
  aber **nicht beglaubigt** — und über sie gehen die Zugangsdaten und der
  ganze Inhalt von `server/`.
  **NICHT VERWECHSELN — das ist PRODUKTIV, nicht das umgezogene Staging.**
  Die Zielprobe steht im Job `produktion` und benutzt dessen Geheimnisse. Der
  Knotenname aus dem Zertifikat gehört zum Produktiv-Hoster. Dass er einem
  auch vom alten Staging bekannt vorkommt, hat einen Grund: **Das alte
  Staging lag im selben Webspace wie Produktiv** — genau deshalb gab es
  E-KH-04. Umgezogen ist nur Staging.

  **Erneuern hilft nicht.** Das Zertifikat ist gültig (im gemessenen Lauf:
  23.07. bis 21.10.2026). Eine Erneuerung brächte denselben Namen. **Das
  Problem ist der Name, nicht das Alter.**

  *Zuerst nachsehen, welche Namen das Zertifikat überhaupt abdeckt:*

  ```
  openssl s_client -connect <FTP_SERVER>:21 -starttls ftp 2>/dev/null \
    | openssl x509 -noout -subject -ext subjectAltName
  ```

  *Drei Wege, in dieser Reihenfolge:*
  **(a)** Beim Hoster fragen, **welchen FTPS-Hostnamen er vorsieht** — viele
  dokumentieren genau einen, unter dem das Zertifikat passt, und der ist
  stabiler als eine Knotennummer. **Das ist der beste Weg, wenn es ihn gibt.**
  **(b)** `FTP_SERVER` auf den Namen aus dem Zertifikat setzen (im Protokoll
  unter „Server certificate: subject"). Wirkt sofort. **Der Preis:** Zieht der
  Hoster den Account auf einen anderen Knoten, bricht der Deploy — aber
  *laut*, mit genau dieser Meldung, und nicht still.
  **(c)** Den Hoster bitten, die eigene Domain ins Zertifikat aufzunehmen
  (SAN). Auf Shared Hosting meist abschlägig; die Frage kostet nichts.
  *Was KEINE Abhilfe ist:* die Prüfung abschalten. Die Zielprobe bietet dafür
  keinen Schalter, und das bleibt so.
  *Scheitern erkennt man daran:* Der nächste Probelauf meldet wieder
  `curl 60`. Kommt stattdessen ein anderer Fehler, ist das ein **Fortschritt**
  — dann ist die Verbindung beglaubigt und die Probe misst erstmals den Weg
  dahinter.
  *Wichtig für die Reihenfolge:* **Prüfpunkt 12 (der Trennversuch) braucht
  das zuerst.** Solange `curl` am Zertifikat abbricht, kommt er nie bis zum
  Datenkanal, und F3 lässt sich nicht messen.

- [x] **14 — Den Zeiger `produktion` zurücksetzen** (F-KH-U-14) —
  **ERLEDIGT am 20.09.2026, 20:4x UTC.** Die Betreiberin hat den Zweig über
  die Branches-Seite gelöscht, die Umsetzung hat ihn aus dem Hilfszweig neu
  angelegt. **Nachgemessen:** `git ls-remote --heads origin produktion` →
  `71507932006d1431abe3823b97cf877b18ccb055`, und das ist der Commit des Tags
  `web-v20.24.2`. Der drohende tägliche Falschalarm der Wache ist damit
  abgewendet.
  **Rest:** Der Hilfszweig `zeiger-wiederherstellung` steht noch — sein
  Löschen ist dem Sandkasten der Umsetzung ebenso gesperrt wie der
  erzwungene Push. Er zeigt auf denselben Commit wie `produktion` und
  schadet nichts; ein Papierkorb-Klick räumt ihn weg.
  **Der ursprüngliche Weg, als Beschreibung:**
  *Er steht auf `9f62d55`, einem Stand, der nie ausgeliefert wurde; richtig
  ist `7150793` (Tag `web-v20.24.2`).* Solange er falsch steht, wird die
  Integritätswache täglich um 04:17 UTC grundlos rot.
  *Vorbereitet ist alles:* Der Zweig **`zeiger-wiederherstellung`** zeigt
  bereits auf `7150793`.
  *Weg (ein Klick, kein Git nötig):* GitHub → **Branches** →
  bei `produktion` das **Papierkorbsymbol**. Danach lege ich ihn aus
  `zeiger-wiederherstellung` neu an und räume den Hilfszweig weg.
  *Solange er fehlt, ist die Wache rot mit Ansage* — das ist gewollt und
  dauert Sekunden.
  *Scheitern erkennt man daran:* `git ls-remote --heads origin produktion`
  zeigt weiter `9f62d55`. Oder im Browser: Branches → `produktion` →
  der jüngste Commit ist nicht „Merge pull request #59".

- [ ] **11 — Vor dem Trennversuch: keine zweite FTP-Sitzung offen.**
  *Weg:* WinSCP schließen, den Dateimanager im Plesk-Panel schließen, jeden
  anderen FTP-Zugang beenden.
  *Warum:* Manche Server begrenzen gleichzeitige Sitzungen je Konto und
  schneiden die zweite ab — das sähe aus wie F3 und wäre keines.

- [~] **12 — Der Trennversuch gegen Produktiv** — **GEFAHREN am 20.09.2026,
  vier Läufe, je zweimal. Ergebnis: die TLS-Sitzungswiederverwendung ist es
  NICHT** (F-KH-U-16). Beide Betriebsarten gelingen.
  **Aber er hat die falsche Stelle gemessen** (F-KH-U-17): Die Probe schrieb
  in ein bestehendes Verzeichnis, die Auslieferung stirbt beim Anlegen eines
  neuen. **Die Probe kann das jetzt** — Rundlauf 2.
  **Offen und neu: Prüfpunkt 15.**
  *Der ursprüngliche Weg, als Beschreibung:* (Abnahme von AP3, zweiter
  Teil; danach fällt **E-KH-09**).
  *Weg:* Probelauf gegen Produktiv, die Zielprobe in **beiden**
  Betriebsarten. Jedes Ergebnis **zweimal** — zweimal gleich ist belastbar.
  *Was welches Ergebnis bedeutet:*

  | mit Wiederverwendung | ohne | Schluss |
  |---|---|---|
  | gelingt | scheitert | **Der Server verlangt die Wiederverwendung** — Ursache belegt |
  | gelingt | gelingt | Die Forderung ist es nicht; weiter mit dem Trockenlauf der Aktion |
  | scheitert | scheitert | Konto, Passiv-Ports oder Verbindungsgrenze |

  *Woran man sieht, dass die Messung gar nichts belegt:* Die Probe meldet
  **„TLS-Sitzung wiederverwendet: NICHT FESTSTELLBAR"**. Dann sagt diese
  `curl`-Fassung nichts darüber, und beide Läufe messen dasselbe. Der Befund
  ist dann „nicht feststellbar", nicht „kein Unterschied".
  *Eingabe für die Deutung:* Läuferabbild und Node sind als Ursache
  ausgeschlossen (Abschnitt 1.5 … 1.6), und der Abbruch steht bei der
  **ersten Datenverbindung** des Laufs.

- [~] **15 — Der Probelauf mit dem VERZEICHNIS-Rundlauf** — **GEFAHREN am
  20.09.2026: BEIDE Rundläufe gelingen** (F-KH-U-18). Auch `ensureDir` ist
  nicht die Ursache. **Offen und neu: Prüfpunkt 16.**
  *Der ursprüngliche Text:* (die eigentliche
  F3-Messung; danach fällt **E-KH-09**).
  *Weg:* wie Prüfpunkt 10 — Branch `claude/fervent-dirac-xirsqw`, Häkchen
  `probelauf`. **Kein drittes Häkchen nötig:** Die Zielprobe fährt beide
  Rundläufe von selbst. Einmal ohne und einmal mit `probelauf_ohne_sitzung`
  genügt; **je zweimal**.
  *Erwartet — und das ist der interessante Fall:* Rundlauf 1 (flach) gelingt
  wie bisher, **Rundlauf 2 (durch ein neues Verzeichnis) scheitert**. Dann
  ist F3 auf `ensureDir` eingegrenzt, und AP4 weiß, was zu beheben ist.
  *Woran man es erkennt:* Die Meldung sagt entweder „FEHLGESCHLAGEN beim
  Hochladen" (dann scheitert schon das Anlegen) oder „FEHLGESCHLAGEN beim
  AUFLISTEN des neuen Verzeichnisses" — und nennt dann `_openDir` beim Namen.
  *Gelingen beide Rundläufe:* Dann ist auch das nicht die Ursache, und es
  bleiben Passiv-Ports unter bestimmten Bedingungen oder eine Mengengrenze
  des Servers (688 Dateien, 62 Verzeichnisse in einem Lauf). Das wäre ein
  Befund für die Betreiberin an den Hoster.
  *Wichtig:* Die Probe räumt hinter sich auf — Datei **und** Verzeichnis.
  Bleibt nach einem Abbruch ein `zielprobe-…`-Verzeichnis liegen, nimmt es
  der nächste Lauf mit.

- [~] **16 — Ein Probelauf, der den Weg zum Datenkanal nennt** — **GEFAHREN am
  20.09.2026: zweimal sauberes `EPSV` mit Port** (F-KH-U-20). Auch der Weg
  zum Datenkanal ist nicht die Ursache. **Offen und neu: Prüfpunkt 17.**
  *Der ursprüngliche Text:* (die nächste F3-Messung).
  *Weg:* wie Prüfpunkt 15, einmal genügt zunächst.
  *Neu im Protokoll:* eine Zeile **`Datenkanal:`** neben
  „TLS-Sitzung wiederverwendet", in **jedem** Rundlauf.
  *Was welche Antwort bedeutet:*

  | Zeile | Schluss |
  |---|---|
  | `PASV — NACH einem EPSV-Fehlschlag` | **Das ist die Erklärung.** `curl` fällt zurück, die Auslieferungsaktion tut es nicht — und bleibt hängen. AP4 stellt dann `EPSV` ab oder tauscht den Client. |
  | `EPSV, Antwort 229, Port …` | EPSV geht; dann ist es auch das nicht, und es bleibt die **Menge** (688 Dateien, 62 Verzeichnisse in einer Sitzung) oder eine Zeit-/Mengengrenze des Servers. Befund an den Hoster. |
  | `nicht feststellbar` | Die Probe hat es nicht gesehen — kein Ergebnis, und das sagt sie so. |

  *Woran man sieht, dass die Zeile überhaupt neu ist:* Sie steht seit dem
  20.09.2026 in **jedem** Lauf, nicht mehr nur im Fehlerfall.

- [ ] **17 — Die Mengenprobe gegen Produktiv** (die F3-Messung, die noch
  aussteht). **Das ist der Punkt, an dem AP3 hängt.**
  *Warum von Hand und nicht in der Kette:* Sie legt bis zu 500 Verzeichnisse
  auf dem Produktivserver an. Kein Kettenschritt ruft sie.
  *Weg:* In der Umgebung, in der die Kette läuft, oder auf einem Rechner mit
  `curl` und Zugang zu den drei Geheimnissen:

  ```
  python3 tools/kette/zielprobe.py \
      --basis "$PRODUKTION_URL" --ftp-server "$FTP_SERVER" \
      --ftp-konto "$FTP_USERNAME" --ftp-pass "$FTP_PASSWORD" \
      --ftp-pfad / --mengenprobe 80
  ```

  **Mit 80 anfangen** (die Auslieferung legt 62 Verzeichnisse an — 80 liegt
  knapp darüber). Gelingt das, mit 200 wiederholen; gelingt auch das, ist
  auch die Menge ausgeschlossen.
  *Erwartet — und das ist der interessante Fall:* **Abbruch mittendrin.** Die
  Zeile `Übertragungen abgeschlossen (226): N von 80` sagt dann, bei der
  wievielten Schluss war, und die letzten 40 Zeilen der Servermeldung stehen
  wörtlich darunter.
  *Woran man den Erfolg der Messung erkennt (nicht des Laufs!):* Die Zahl vor
  „von" ist **kleiner** als 80 und **größer** als 0. Dann geht einzeln jede
  dieser Operationen durch, in **einer** Sitzung nicht — und F3 hat einen
  Namen: eine Sitzungs- oder Mengengrenze des Servers. Das ist ein Befund für
  den Hoster, kein Codefehler, und AP4 baut dagegen (Wiederaufnahme, kleinere
  Bündel, oder ein Client, der die Sitzung erneuert).
  *Woran man sieht, dass die Messung nichts belegt:* `N von 80` mit N = 80 —
  dann ist auch die Menge nicht die Ursache. Oder `0 von 80`: Dann ist schon
  die **erste** Übertragung gescheitert, und das wäre etwas anderes als das,
  wonach hier gesucht wird (dann zuerst Prüfpunkt 16 wiederholen).
  *Was sie hinterlässt:* nichts. Aufgeräumt wird im `finally`, auch nach
  Abbruch. **Bleibt etwas liegen, sagt sie es mit Zahl** („WARNUNG: N
  Verzeichnisse konnten nicht entfernt werden") — dann liegen
  `zielprobe-…`-Verzeichnisse im Webroot, und der nächste Lauf der Zielprobe
  nimmt sie mit.
  *Grenze:* Sie legt die Verzeichnisse **flach** nebeneinander an, die
  Auslieferung einen **Baum**. Bleibt die Mengenprobe grün, ist die Tiefe das
  Letzte, was nicht gemessen wurde.


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

- **Die Anwendung überlässt ihre Sitzungsablage dem Hoster und prüft sie
  nicht.** Anlass: Prüfpunkt 5a. Auf lima-city liegt sie in einem **geteilten**
  Verzeichnis (`/home/webpages/tmp`, root, `0773`); dort fehlt Fremden das
  Leserecht, und damit ist es gutgegangen. **Auf Produktiv ist der Wert nicht
  erhoben, und für Selbsthoster ist er völlig offen** — das verträgt sich
  nicht mit R81 („die Anwendung ist nicht auf einen Hoster zugeschnitten").
  Eine Sitzungsdatei trägt zwar **kein Schlüsselmaterial**, aber ihr
  **Dateiname ist die Sitzungs-ID**; wer sie auflisten kann, ist angemeldet.
  **Entschieden am 20.09.2026: Stufe 2** (Auftraggeber). Stufe 3 ist geprüft
  und **verworfen** — sie koppelte die Anmeldung an die Datenbank, obwohl
  `update.php` für die Migration eine Sitzung braucht (`require_admin()`,
  Z. 35) und `session_start()` in `auth_guard.php`:31 **vor** dem Torwächter
  (Z. 72–74) steht; dazu nähme `komp_tabellen()` die Sitzungstabelle in jeden
  Komplett-Stand (`SHOW FULL TABLES`, keine Ausnahmeliste), und eine
  Wiederherstellung beliebte alte Sitzungen wieder. Ihr einziger echter
  Vorteil — mehrere Anwendungsserver — steht auf keinem Fahrplan. **Stufe 1
  bleibt als Empfehlung daneben.** Der ausführliche Auftrag ist an die
  Fable-Instanz übergeben, die als Nächstes den Rahmenplan anfasst
  (`Prompt-Sitzungsablage-2026-09-20.md`, außerhalb des Repositoriums).
  Drei Stufen, aufsteigend nach Aufwand:
  1. **Messen und sagen.** `plattform_pruefen()` bekommt einen Prüfpunkt in
     der Bauform der bestehenden Schreibrechte-Probe: *Ist die Sitzungsablage
     für Fremde auflistbar?* Antwort `false` = rot, `null` wo nicht
     feststellbar. Das beantwortet die Frage auf **jeder** Installation, ohne
     dass jemand den Hoster fragt. **Kleinster Eingriff, größter Ertrag.**
  2. **Selbst in die Hand nehmen.** `session_save_path()` auf ein eigenes
     Verzeichnis unter der Anwendungswurzel setzen, `0700`, Name mit
     führendem Punkt — dann greift die **bestehende** Punktdatei-Sperre der
     `.htaccess` (Zeile 64), und **Stufe 2 misst sie bereits**. Zu bedenken:
     **`session.gc_probability = 0`** auf lima-city, PHP räumt dort also nie
     selbst auf; ein eigenes Verzeichnis muss der **Aufräum-Job** (4.97a)
     mitnehmen, sonst wächst es unbegrenzt. Dazu ein Eintrag in der
     **Schutzliste** (E-KH-20) — ein achter nur-auf-dem-Server-Pfad. Auf
     nginx greift `.htaccess` nicht; dort zählt allein `0700`.
  3. **Grundsätzlich: Sitzungen in die Datenbank** (`session_set_save_handler`).
     Löst es für jeden Hoster und jedes Dateisystem, räumt über den
     bestehenden Job auf — kostet Schema, Migration und Sorgfalt beim Sperren.
  **Quer dazu, und unabhängig vom Speicherort: Sitzungsbindung.** Ein
  Zufallstoken, das **nur** im Cookie steht und dessen Hash in der Sitzung
  liegt, macht eine gelesene Sitzungsdatei wertlos — auch eine aus einem
  gefundenen Backup. Das wirkt gegen jeden Store-Leak und ändert den
  Speicherort nicht.
  **Gehört nicht zu Kette II** (das ist die Auslieferungskette, nicht die
  Anwendungssicherheit); Stufe 1 passt in die Nähe von R81, die übrigen in
  eine Sicherheitsrunde.

- ~~**`sitzung.py` liest die Störungs- und die Wartungsseite nicht.**~~
  **Erledigt am 20.09.2026** — die Abhilfe unten ist gebaut; der Eintrag
  bleibt als Herkunft stehen. Anlass: F-KH-U-06. `fehlertext()` sucht `meldung-fehler`; `stoerung_seite_html()`
  und `wartung_antwort_seite()` tragen die Klasse nicht (gemessen: 0 Treffer
  in `server/wartung_lib.php`). Drei verschiedene Abweisungen der Anmeldung
  sehen für jedes Prüfwerkzeug gleich aus und heißen „unbekannt". Abhilfe:
  `fehlertext()` liest zusätzlich die **Überschrift** der Störungsseite
  (`<h1>` samt folgendem `<p class="text">`) und den `<title>`; dann sagt der
  nächste Lauf selbst „Kein Zugang — …" oder „Wartung". Betrifft nur
  `tools/`, also keine Versionsstufe. **Mittel statt niedrig:** Solange das
  so ist, kostet jede Abweisung eine Runde Rätselraten — und genau darauf
  ist am 20.09.2026 die Abnahme von AP1 aufgelaufen.

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
