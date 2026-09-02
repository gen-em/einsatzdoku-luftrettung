# Umstellung „Sicherung" → „Backup"

**Stand:** 02.09.2026 · **Zustand:** in Umsetzung (Schritt 4 des Rahmenplans,
S7) · **Umsetzung:** in einem Zug, Zweig `claude/new-session-30byn3`

## Statusblock

| Arbeitspaket | Inhalt | Zustand |
|---|---|---|
| AP0 | Neuzählung gegen Web 12.9.2, Entscheidungen E-S7-1 bis E-S7-4 | **erledigt** |
| AP1 | Backup-Seite der NutzerIn (`einstellungen.php`, `assets/*.js`, `style.css`) | **erledigt** |
| AP2 | Adminbereich Konten (`admin_user.php`, `admin_users.php`, `admin_sicherungen.php`, `adminbackup_lib.php`, `backup_lib.php`, `api/`) | **erledigt** |
| AP3 | Komplett-Backup und Wiederanlauf (`komplett_lib.php`, `admin_komplettsicherung.php`, `wiederherstellen.php`) | **erledigt** |
| AP4 | Backup-Ziele, Jobs, Rahmen (`admin_sicherungsziele.php`, `sicherungsziel_lib.php`, `jobs_lib.php`, `ui.php`, `update.php`, `install.php`, Rest) | **erledigt** — `server/` ist durch |
| AP5 | Dokumentation (Handbuch, Technik, Backup-Format, Export-Format, Design, Lizenzen, README, Backlog) | **erledigt** |
| AP5a | Backlog Nr. 89 beheben (eigener Commit, eigene Korrekturstufe) | **erledigt** |
| AP6 | `tools/` | **erledigt** |
| AP7 | Buchführung (Version, Changelog), Prüfmittel, Prüfdokument | offen |
| AP8 | Rahmenplan, Löschung dieses Dokuments — **erst nach Freigabe** | offen |

**Wo es hakt:** nichts. Ein **vorbestehender** Fehler ist beim Prüfen
aufgefallen (F-S7-06, Backlog Nr. 89: der Job „Komplett-Backup der
Installation" lief seit Web 12.2.0 nie) und auf Nachfrage des Auftraggebers
in einem eigenen Commit behoben. **Nicht prüfbar in dieser Umgebung:** nichts —
entgegen der Erwartung steht eine vollständige lokale Installation
(MariaDB nachinstalliert, Referenzdatensatz über die regulären Wege
eingespielt: 526 Ingest-Anfragen, 16 Diensttage, 87 Einsätze, 0 Fehler).
Bilderlauf und Browserprüfung laufen damit wirklich.

---

## 0. Die Neuzählung (AP0, 02.09.2026)

Die Zahlen der Vorlage stammten von Web 9.14.1/9.15.0. `main` steht auf
**Web 12.9.2** und hat S2, S4 und S6 aufgenommen. Neu gezählt
(`grep -o "Sicherung\|sicherung\|SICHERUNG"`, Vorkommen, nicht Zeilen):

| Bereich | jetzt | Vorlage |
|---|---|---|
| `server/` ohne `vendor/` | **643** | 272 |
| — davon in Kommentaren | 374 | — |
| — davon in Code und sichtbarem Text | 269 | — |
| `docs/Handbuch.md` | 78 | 48 |
| `docs/Technik.md` | 123 | 34 |
| `docs/Backup-Format.md` | 56 | 20 |
| `docs/Backlog.md` | 51 | 11 |
| übrige normative Doku | 15 | — |
| `tools/` | 188 | nicht erfasst |
| **Historie** (Changelog 236, Rahmenplan 47, Archiv 107, `konzepte/erledigt/` 344) | 734 | bleibt |

**Die Verdopplung hat einen Namen: S2.** `komplett_lib.php` (40),
`admin_komplettsicherung.php` (27), `wiederherstellen.php` (18),
`admin_sicherungsziele.php` (17), `sicherungsziel_lib.php` (15) und
`jobs_lib.php` (22) standen auf keiner Zeile der Arbeitsliste in Abschnitt 5 —
es gab sie zum Zeitpunkt der Vorlage noch nicht. Sie gehören nach deren
eigener Regel dazu; die Prüfmittel-Liste des Auftrags nennt zwei davon
ohnehin. Damit bestätigt sich, was Abschnitt 2 vorhergesagt hat: **Eine
Konfliktmessung gegen einen laufenden Zweig hat ein Verfallsdatum.**

## 0a. Entscheidungen dieser Umsetzung

Getroffen am 02.09.2026, vor der ersten Änderung. Sie ergänzen R56 (Verb
„sichern", Symbolname und `admin_sicherungen.php` bleiben).

- **E-S7-1 — Komposita mit Bindestrich.** „Komplett-Backup", „Backup-Ziel(e)",
  „Backup-Datei", „Backup-Regeln", „Backup-Stand", „Backup-Lauf",
  „Backup-Paket", „Backup-Kennung", „Backup-Job", „Backup-Bereich",
  „Backup-Container", „Backup-Vorgang", „Backup-Seite", „Konto-Backup",
  „Admin-Backup", „Sammel-Backup". Grund: Deutsche Typografie setzt bei
  Anglizismus-Komposita den Bindestrich, und die Menüpunkte bleiben kurz —
  „Backup-Ziele" ist zwei Zeichen kürzer als „Sicherungsziele".
  **Das Genus zieht nur mit, wo der Kopf „Sicherung" war:** „Komplett-Backup"
  ist sächlich, „Backup-Datei" bleibt weiblich, „Backup-Lauf" männlich,
  „Backup-Ziel" sächlich wie zuvor.
- **E-S7-2 — Kommentare gehen mit.** Damit gilt Abschnitt 5.1 dieser Vorlage
  und **nicht** der Prompt-Satz „Kommentare bleiben (R13)": R13 sagt nur
  versionshistorischen Kommentaren zu, und ein Kommentar, der „Sicherung"
  erklärt, während der Code daneben „Backup" sagt, ist genau die Drift, die
  diese Umstellung beseitigen soll. **Ausgenommen bleibt die Versionsgeschichte
  in `server/version.php`** (45 Treffer) — dieselbe Begründung wie beim
  Changelog in Abschnitt 3.2: sie ist Beleg, nicht Oberfläche.
- **E-S7-3 — `docs/Backlog.md`: offene Punkte ja, erledigte nein.** Wie in
  Abschnitt 5.2 vorgesehen. Erledigte Punkte sind Protokoll.
- **E-S7-4 — `tools/` zieht mit**, mit zwei Ausnahmen, die kein Begriff sind,
  sondern eine Messgrundlage: `tools/referenzdatensatz/quelldaten/` (drei
  Fundstellen in Freitextfeldern der Diensttage D12, D15, D16) und die
  eingecheckten Referenz-Exporte unter `tools/referenzdatensatz/referenz/`
  bleiben **unangetastet**. Eine Änderung dort verändert die erzeugten
  Nutzlasten, und der Kreislaufvergleich meldete danach Abweichungen, die
  keine sind. Ebenfalls unangetastet bleibt
  `.github/workflows/deploy.yml`: Der Kommentar dort erklärt die
  Ausnahmeliste, und an dieser Datei wird in diesem Paket nichts angefasst
  (`CLAUDE.md` 3). **Nachgetragen in AP6:** Auch die Seitennamen in
  `tools/screenshots/seiten.json` (`43-sicherungen`, `43b-sicherungsziele`,
  `43c-komplettsicherung`) bleiben — sie sind **Messidentitäten**, keine
  Begriffe. `docs/CHANGELOG.md` hält unter genau diesen Namen Prüfzahlen
  fest („Bilderlauf `43-sicherungen` | 8 Bilder, 8 verschiedene
  Prüfsummen …"), ebenso das P3-Konzept. Wer sie umbenennt, trennt das
  Protokoll von dem, was es misst.

## 0b. Funde während der Umsetzung

- **F-S7-01 (AP1) — „Sicherung" stand zweimal für *Absicherung*, nicht für
  ein Backup.** `assets/crypto.js` („Das ist die wichtigste Sicherung dieses
  Umbaus" — gemeint ist die Absicherung gegen einen stillen Vorgabewert) und
  `einsatz_loeschen.php` („so greift die Sicherung auch, wenn Dialoge
  blockiert sind"). Eine mechanische Ersetzung hätte beide zu Unsinn gemacht.
  Behoben, indem dort **„Absicherung"** steht — das trennt die beiden
  Bedeutungen dauerhaft. Repo-weit gegengesucht (`Sicherung gegen`,
  `als Sicherung`, `Sicherung, dass`, `wichtigste Sicherung`, …): keine
  weiteren Fälle.
- **F-S7-02 (AP1) — Der Platzhalter der Zusatzdaten hieß an zwei Stellen
  verschieden.** `crypto.js` schrieb `EDBAK4|<sicherungskennung>|…`,
  `docs/Backup-Format.md` `EDBAK4|<kennung>|…`; der Code selbst liest
  `manifest['kennung']`. Auf die normative Fassung angeglichen.
- **F-S7-03 (AP2) — Eine Wortgruppe kann über eine Zeichenketten-Grenze
  laufen, und dann sieht sie keine zeilenweise Regel.** In `admin_user.php`
  und `admin_sicherungen.php` stand
  `… sieht die '` **Zeilenumbruch** `. 'Sicherung jetzt im eigenen …`.
  Der Artikel steht in der einen Zeichenkette, das Nomen in der nächsten.
  Eine eigene Prüfung sucht seither genau dieses Muster über
  Zeilengrenzen hinweg; sie fand drei Fälle, darunter einen aus AP1
  (`einstellungen.php`, „passt nicht zu dieser Sicherung"), der der
  zeilenweisen Nachprüfung entgangen war. Alle drei behoben, samt der
  Pronomen im selben Satz („spielt **sie** dort ein" → „spielt **es**
  dort ein").
- **F-S7-04 (AP3) — Die Kopfzeile des SQL-Dumps ist zugleich Text und
  Erkennungsmarke, und eine Umbenennung hätte still eine Prüfung
  abgeschaltet.** `komplett_lib.php` schreibt
  `-- Einsatzdokumentation Notarzt — Komplettsicherung der Installation` in
  den Dump; `wiederherstellen.php` erkennt daran, dass der Dump **aus dieser
  Anwendung** stammt, und verlangt nur dann die Endmarke. Ein fremder Dump
  (`mysqldump`) hat keine Endmarke und wird deshalb ohne sie angenommen.
  Hätte die Umstellung nur die neue Schreibweise gesucht, gälte **jeder vor
  S7 erzeugte Dump als fremd** — die Vollständigkeitsprüfung wäre für genau
  die Dateien ausgefallen, die heute auf den Servern liegen, und ein
  abgebrochener Stand wäre klaglos eingespielt worden. Ohne Fehlermeldung.
  **Gelöst:** Die Kopfzeile trägt die neue Schreibweise, der Leser erkennt
  **beide**; die alte darf am v1.0-Schnitt weg (R60), nicht vorher.
  Derselbe Text steht in `tools/komplettprobe/probe.php` als Prüfbedingung —
  zieht in AP6 nach.
- **F-S7-05 (AP3) — dritter Fall von „Sicherung" im Sinne von Absicherung.**
  `wiederherstellen.php`: „Eine Seite ohne Anmeldung, die sie nebenbei
  mitlaufen liesse, nähme genau diese Sicherung heraus" — gemeint ist der
  Knopf zwischen Anzeigen und Ausführen, kein Backup. Ebenfalls zu
  „Absicherung". Der vierte und letzte Fall (`einsatz_loeschen.php`, „so
  greift die Sicherung auch, wenn Dialoge blockiert sind") ist in AP4
  erledigt; damit ist der Bestand durchgesehen.
- **F-S7-06 (AP4) — ein vorbestehender Fehler, gefunden beim Prüfen:
  Der Job „Komplett-Backup der Installation" läuft seit Web 12.2.0 nie.**
  `job_komplett()` nimmt `float $reserve = KOMP_RESERVE_S` als
  Parameter-Vorgabewert; die Konstante steht in `komplett_lib.php`, das
  erst **im Rumpf** geladen wird. PHP wertet Vorgabewerte beim Aufruf aus —
  also vorher. Der Aufruf aus `jobs.php` endet damit immer in
  `Error: Undefined constant "KOMP_RESERVE_S"`, und die Wartungsseite zeigt
  den Job als „Fehler". **Von S7 unberührt** (auf `main` identisch), deshalb
  nach K4 gesammelt statt behoben: **Backlog Nr. 89**.
- **F-S7-07 (AP4) — Versalien-Überschriften zogen zunächst nicht mit.**
  Das Projekt setzt Abschnittsüberschriften in Kommentaren in Großbuchstaben
  („SICHERUNGSZIELE — wohin die Sicherungen geschoben werden"). Alle Regeln
  waren auf gemischte Schreibung gebaut; 30 Stellen in `server/` wären
  stehen geblieben, und keine Prüfung hätte sie gemeldet, weil die
  Nachprüfung dieselbe Annahme teilte. Eigene Versalien-Liste ergänzt.
- **F-S7-08 (AP4) — Pronomen und Possessive ziehen nicht von selbst mit.**
  „Das Komplett-Backup ist vorgemerkt. **Sie** läuft mit dem nächsten
  Wartungslauf an." Eine Prüfung über Satzgrenzen hinweg (Nomen, dann
  `sie`/`ihr` innerhalb von 160 Zeichen ohne Satzende dazwischen) legte
  50 Stellen vor; 11 davon waren echte Fehlbezüge, 39 gingen auf ein
  anderes Wort. Alle 11 behoben. Ohne diese Prüfung wären sie in
  Meldungen stehen geblieben, die eine NutzerIn liest.
- **F-S7-09 (AP5) — fünfter Bedeutungsfall, diesmal in der
  Formatbeschreibung.** `docs/Backup-Format.md` 1.4 überschreibt einen
  Abschnitt mit „**Zwei Sicherungen, und jede trägt für sich**" — gemeint
  sind die Manifest-Prüfsumme und die Zusatzdaten, also zwei **Absicherungen**
  gegen vertauschte Teile. Steht jetzt so da.
- **F-S7-10 (AP5) — verwaiste weibliche Nominalisierungen.** „Je Konto
  liegen höchstens zwei Backups … **Die älteste** wird beim nächsten Sichern
  verdrängt." Das Bezugswort steht einen Satz vorher und ist weg; das
  Zahlwort bleibt. Eine dritte Prüfung sucht deshalb nach freistehenden
  weiblichen Formen (`die älteste`, `die jüngste`, `eine freigegebene`, …)
  im Umfeld eines Backups, mit einer Ausschlussliste für die Fälle, in denen
  sich die Form auf ein anderes Wort bezieht (Datei, Fassung, Spur …). Vier
  echte Fälle in Handbuch, Kontenliste und Adminübersicht.
- **F-S7-12 (AP6) — vier Komposita, die erst hier auftauchten.**
  „Sicherungsweg", „Sicherungsbau", „Sicherungsordner" und
  „Sicherungs&shy;kennung" (mit weichem Trennstrich, deshalb von jedem
  Wortmuster übersehen) kommen nur in den Prüfmitteln vor. Ergänzt.
- **F-S7-13 (AP6) — die zweite funktional gekoppelte Zeichenkette.**
  `tools/komplettprobe/probe.php` prüft mit
  `str_contains($anfang, 'Komplettsicherung der Installation')`, ob der
  Kopf nach einem verlorenen Baustand wieder am Anfang steht — dieselbe
  Zeichenkette wie in F-S7-04. Sie kennt jetzt ebenfalls **beide**
  Schreibweisen, mit demselben Kommentar und derselben Frist (v1.0, R60).
- **F-S7-11 (AP5) — zwei Regeln konnten einander im Weg stehen.**
  „solche" steht sowohl in der Liste der bestimmten (`die` → `das`) als auch
  der gemischten Artikelwörter. Bei „eine solche Sicherung" griff die erste
  Regel zuerst, machte „solches" daraus und liess „eine" stehen: „eine
  solches Backup". Die Nachprüfung sah es nicht, weil ihr Adjektivmuster nur
  auf `-e` endete. Beides berichtigt — ein Fall im Handbuch.

---

Dieses Dokument war die Vorlage für eine Umstellung, die noch nicht
stattgefunden hatte; seit dem 02.09.2026 ist es zugleich das **Konzept der
laufenden Umsetzung**. Es sammelt den Befund, die Grenzen und die
Arbeitsliste, damit die Umstellung an einem Stück läuft statt in Etappen, die
sich widersprechen. Abschnitte 1 bis 6 stehen unverändert als Befund; was die
Umsetzung daran berichtigt hat, steht oben in Abschnitt 0.

---

## 1. Die Entscheidung

Beschluss vom 31.08.2026: **Alles auf „Backup"**. Begründung: die klarere
Beschreibung.

Anlass war eine Rückmeldung zur Sicherungsseite, auf der beide Wörter
unmittelbar untereinander stehen — die Karten heißen „Backup erstellen" und
„Backup einspielen", die Knöpfe darin „Sicherung erstellen" und „Sicherung
einspielen". Dieselbe Handlung, zwei Wörter, ein Bildschirm.

**Die Richtung ist die größere, nicht die kleinere.** Gezählt am Stand
Web 9.14.1:

| | server/ (PHP + JS) | docs/ |
|---|---|---|
| „Sicherung" | 272 | 407 |
| „Backup" | 60 | — |

„Sicherung" ist heute die Hauptsprache, „Backup" die Minderheit. Die
Umstellung dreht das um. Eine dokumentierte Entscheidung **für** eines von
beiden gab es bisher nicht; die Terminologie-Phase P2 hat das Wortpaar nicht
angefasst.

## 2. Warum nach S3 und nicht jetzt

Die Umstellung liegt fast vollständig in Dateien, die der S2-Zweig
(`claude/s2-ansatz-vtnw53`) gleichzeitig hält. Gemessen gegen die Merge-Basis
862559e, Stand S2/AP5 vom 01.09.2026:

| Datei | Treffer | S2 |
|---|---|---|
| `docs/CHANGELOG.md` | 136 | **hält** |
| `docs/Handbuch.md` | 48 | **hält** |
| `server/admin_user.php` | 42 | **hält** |
| `docs/Technik.md` | 34 | **hält** |
| `server/backup_lib.php` | 20 | **hält** |
| `docs/Backup-Format.md` | 20 | **hält** |
| `server/version.php` | 17 | **hält** |
| `docs/Backlog.md` | 11 | **hält** |
| `server/update.php` | 10 | **hält** |
| `server/validate_lib.php` | 5 | **hält** |
| `server/einstellungen.php` | 34 | **hält** (AP5) |
| `server/assets/crypto.js` | 12 | **hält** |
| `server/ui.php` | 7 | **hält** |
| `docs/konzepte/erledigt/Konzept-P3-Oberflaeche.md` | 68 | frei |
| `server/adminbackup_lib.php` | 42 | frei |
| `docs/konzepte/erledigt/Konzept-S1-Sicherung-Import.md` | 37 | frei |
| `server/admin_sicherungen.php` | 31 | frei |
| `server/admin_users.php` | 18 | frei |

**Die untere Hälfte dieser Tabelle hat am 31.08.2026 anders ausgesehen.**
`einstellungen.php`, `crypto.js` und `Design.md` standen dort als frei — S2
war bei AP2, heute ist es bei AP5, und AP5 ist der Sicherungscontainer. Die
Messung war zum damaligen Zeitpunkt richtig und ist trotzdem überholt. Daraus
folgt eine Regel für dieses Dokument: **Eine Konfliktmessung gegen einen
laufenden Zweig hat ein Verfallsdatum.** Vor der Umsetzung wird neu gemessen,
nicht abgeschrieben.

**Der Grund gegen eine Teilumstellung ist nicht der Merge-Aufwand, sondern die
Halbheit.** Wer nur die freien Dateien umstellt, bekommt zwei Adminseiten, die
Verschiedenes sagen (`admin_sicherungen.php` frei, `admin_user.php` nicht), und
ein Handbuch, das zu keiner von beiden passt. Eine halb durchgeführte
Terminologie-Umstellung ist schlechter als gar keine: Vorher weiß man, dass zwei
Wörter dasselbe meinen; nachher muss man raten, ob der Unterschied Absicht ist.

## 3. Die Grenzen — was **nicht** umgestellt wird

Jede Grenze mit ihrem Grund. Wer eine davon aufhebt, hebt sie bewusst auf.

### 3.1 `server/sicherungen/` bleibt

Das Verzeichnis der Admin-Sicherungen auf dem Server. Es steht in `.gitignore`
**und** in der Ausnahmeliste des Deploys (`.github/workflows/`, Zeilen 47–48),
und der Kommentar darüber sagt, warum: *„ZWINGEND seit A8.2 … ohne diesen
Eintrag löscht der nächste Deploy"* die Sicherungen. Ein umbenanntes Verzeichnis
fiele aus der Ausnahmeliste — die vorhandenen Admin-Sicherungen wären beim
nächsten Deploy weg. CLAUDE.md Abschnitt 3 sagt zu diesem Eintrag ausdrücklich:
„beides muss so bleiben."

**Folge:** Der Pfad im Code und in `docs/Technik.md` bleibt `sicherungen/`.
Das ist kein Widerspruch zur Umstellung, sondern ein Speicherort.

### 3.2 `docs/CHANGELOG.md` wird nicht rückwirkend umgeschrieben

136 Treffer, und jeder einzelne gehört zu einem Eintrag, der beschreibt, was zu
einem bestimmten Zeitpunkt gebaut wurde. Ein Eintrag von Web 4.6.0, der
nachträglich „Backup" sagt, behauptet etwas, das damals nicht dastand. Der
Changelog ist Beleg, nicht Oberfläche.

**Was stattdessen geschieht:** Der **neue** Eintrag zur Umstellung benutzt
„Backup" und hält fest, dass ältere Einträge das alte Wort führen.

### 3.3 Abgeschlossene Phasendokumente bleiben

`Konzept-S1-Sicherung-Import.md` (37), `Pruefdokument-S1-Sicherung-Import.md`
(13), `Konzept-P2-Terminologie.md` (9), `Konzept-P3-Oberflaeche.md` (68),
`Pruefdokument-P3-Oberflaeche.md` (9) — samt ihrer Dateinamen.

Sie halten fest, was in einer abgeschlossenen Phase entschieden und geprüft
wurde. Wer sie umschreibt, ändert das Protokoll nachträglich. Aus demselben
Grund bleibt der Dateiname `Konzept-S1-Sicherung-Import.md`, obwohl er das Wort
trägt.

### 3.4 Fachbegriffe, die schon „Backup" heißen

Die Dateiendung `.edbak`, die Funktion `sealBackup()`, `adminbackup_lib.php`,
`api/adminbackup_freigabe.php`. Kein Handlungsbedarf.

### 3.5 Die Abgrenzung zum Export bleibt wörtlich

Der Satz *„Dies ist kein Backup. Ein Export ist zum Weiterverarbeiten in anderen
Programmen gedacht."* lebt davon, dass „Backup" als Wort für die vollständige,
wiederherstellbare Datei erkannt wird. Nach der Umstellung wird er **stärker**,
nicht schwächer — er ist zu erhalten, nicht zu glätten.

## 4. Offene Entscheidungen

Diese drei sind vor der Umsetzung zu klären; sie ändern den Umfang erheblich.

### 4.1 Das Verb „sichern" — mit oder ohne?

25 Vorkommen, darunter vier Knopfbeschriftungen: „Alle sichern", „Auswahl
sichern", „Jetzt sichern" (2×). Die Umstellung des Hauptworts zwingt nicht dazu,
auch das Verb zu ersetzen — „Backup erstellen" statt „Jetzt sichern" ist länger
und auf schmalen Knöpfen teurer.

**Empfehlung:** Verb stehen lassen. „Jetzt sichern" erzeugt ein Backup — das ist
verständlich, und Deutsch hat für „backuppen" kein brauchbares Wort.

### 4.2 Der Symbolname `sicherung`

`server/assets/images/symbole/sicherung.svg`, benutzt als `'symbol' =>
'sicherung'` an sechs Stellen (`admin_sicherungen.php`, `admin_user.php` 2×,
`admin_users.php`, `update.php`, dazu die Navigationstabellen in `ui.php`).

Ein Symbolname ist kein sichtbarer Text. Umbenennen hieße: Datei umbenennen,
sechs Verwendungen nachziehen, die **erzeugte** Symboltabelle in `docs/Design.md`
neu bauen (`python3 tools/design/tabellen.py alle`), und die
Vollständigkeitsprüfung meldet in der Zwischenzeit eine Symboldatei ohne
Verweis.

**Empfehlung:** stehen lassen. Der Gewinn ist null, das Risiko real.

### 4.3 Der Dateiname `admin_sicherungen.php`

35 Verweise über 14 Dateien, darunter `tools/screenshots/seiten.json` und
`tools/vollstaendigkeit/streichliste.md`. Dazu: Der FTPS-Deploy lädt hoch, er
räumt nicht auf — die alte Datei bliebe auf dem Server liegen und wäre weiter
erreichbar.

**Empfehlung:** stehen lassen, oder als eigenes Paket mit eigenem Prüfweg. Nicht
nebenbei in der Textumstellung.

## 5. Die Arbeitsliste

Reihenfolge nach CLAUDE.md Abschnitt 2 und 6: erst der Code, dann die
Dokumentation, **dann** die Prüfmittel.

### 5.1 Sichtbare Texte im Code

74 Fundstellen in Zeichenketten, dazu rund zehn im HTML-Text der Vorlagen.
Die dichtesten Stellen:

- **`server/einstellungen.php`** — die Backup-Seite selbst. Knöpfe „Sicherung
  erstellen" / „Sicherung einspielen" (3×), Feldbeschriftungen „Passwort für
  die Sicherung" und „Passwort der Sicherung", Kartentitel „Für dich
  freigegebene Sicherung", dazu die Meldungen des Einspielwegs. **Das ist die
  Stelle aus der Rückmeldung — sie gehört zuerst gelesen.**
- **`server/admin_sicherungen.php`** — Adminübersicht: „Sicherungen ohne Konto",
  „Sicherungen dieses Kontos", „Die Sicherung ließ sich nicht lesen/löschen",
  die Bestätigungstexte der Löschdialoge.
- **`server/admin_user.php`** — Kontoseite: der Sicherungsblock, „Letzte
  Sicherung", die Freigabe.
- **`server/admin_users.php`** — Kontenliste: Spaltenkopf „Sicherung",
  Kachel „Sicherung überfällig", der Sortierschlüssel `'sicherung'` (interner
  Schlüssel, bleibt).
- **`server/assets/crypto.js`**, **`server/ui.php`**, **`server/update.php`**,
  **`server/backup_lib.php`**, **`server/validate_lib.php`** — einzelne
  Meldungen und Navigationsbeschriftungen.

**Kommentare gehen mit.** Von den 272 Treffern in `server/` stehen viele in
Kommentaren (`backup_lib.php` 20 von 20, `validate_lib.php` 5 von 5,
`adminbackup_lib.php` 33 von 42, `crypto.js` 10 von 12). Sie zu übergehen wäre
bequem und falsch: Ein Kommentar, der „Sicherung" erklärt, während der Code
daneben „Backup" sagt, ist genau die Drift, die diese Umstellung beseitigen
soll.

### 5.2 Dokumentation

- **`docs/Handbuch.md`** (48) — die Bedienanleitung. **Muss mit**, sonst
  beschreibt sie eine Oberfläche, die es nicht mehr gibt.
- **`docs/Technik.md`** (34) — bis auf den Pfad `sicherungen/` (3.1).
- **`docs/Backup-Format.md`** (20) — heißt schon so; der Fließtext zieht nach.
- **`docs/Backlog.md`** (11) — offene Punkte ja, erledigte nein (dieselbe
  Begründung wie 3.2: erledigte Punkte sind Protokoll).
- **`docs/Design.md`** — nur, falls 4.2 anders entschieden wird.

### 5.3 Was nach CLAUDE.md Abschnitt 2 mitläuft

1. **`server/version.php`** hochstufen. Eine reine Umbenennung ohne
   Verhaltensänderung ist **Korrektur**, nicht Neben — es kommt keine Funktion
   und kein Feld hinzu. Kopfabsatz nicht vergessen; die Erzählung je
   Hauptnummer wird fortgeschrieben.
   **Die Nummer selbst steht erst bei der Umsetzung fest:** Der ausgelieferte
   Stand ist heute 9.15.0, S2 vergibt auf seinem Zweig bereits das 11er-Band,
   und S5 und S3 liegen dazwischen. Hier eine Zahl hineinzuschreiben, wäre
   geraten — sie wird an der dann ausgelieferten Fassung abgelesen.
2. **`docs/CHANGELOG.md`**: ein Eintrag, erklärende Prosa mit Begründung — was
   war das Problem (zwei Wörter für dieselbe Sache, auf einem Bildschirm
   nebeneinander), warum diese Lösung, was bleibt bewusst stehen (Abschnitt 3).
3. **Dokumentation** nach 5.2.
4. **Backlog**: kein neuer Punkt nötig, wenn die Umstellung in einem Zug läuft.

### 5.4 Prüfen

- **`tools/wortliste/`** ist Pflicht, nicht Kür — CLAUDE.md Abschnitt 6:
  „für jede Änderung an einem sichtbaren Text". Erwartet: null Treffer außerhalb
  der Ausnahmen, null ungenutzte Ausnahmen.

  **Sie misst diese Umstellung aber nicht.** Das Werkzeug fragt nach Land- und
  Luft-Neutralität, nicht nach „Backup" gegen „Sicherung" — es kann also nicht
  bestätigen, dass die Umstellung vollständig ist. Es beantwortet die andere
  Frage: ob beim Umschreiben von rund 700 Textstellen ein Luftwort neu
  hineingeraten ist. Der Vollständigkeitsbeleg ist die Gegenprobe unten.

  Geprüft wird dabei ein fester Satz Dateien (`wortliste.py`, `BEREICHE`):
  `server/*.php`, `server/api/*.php`, `server/assets/*.js` und acht normative
  Dokumente — darunter `Handbuch.md`, `Technik.md`, `Backup-Format.md` und
  `Design.md`. Dieses Dokument hier gehört **nicht** dazu und wird nicht
  geprüft; es ist Vorlage, nicht Norm.

  **Nachgesehen, weil es naheliegt und nicht stimmt:** `ausnahmen.json` enthält
  „Sicherung" an fünf Stellen, aber ausschließlich in den *Begründungstexten*
  von Ausnahmen für Luftwörter (`flugtage`, `hems`, „Flugtag", „Flugspur").
  Keine Ausnahme wird durch die Umstellung ungenutzt, es ist dort also nichts
  zu tun. Wer die Begründungen trotzdem mitzieht, tut es der Einheitlichkeit
  wegen — kein Werkzeug meldet es.
- **`tools/vollstaendigkeit/`** — nur, falls 4.2 anders entschieden wird
  (Symboldatei ohne Verweis).
- **Im Browser**: die Backup-Seite, die Adminübersicht, die Kontoseite und der
  Einspielweg. Ohne diese Prüfung ist die Umstellung nicht fertig — eine
  Zeichenkette, die im PHP-Zweig steckt, den niemand aufgerufen hat, fällt
  sonst nicht auf.
- **Gegenprobe zum Schluss:** `grep -rc "Sicherung" server/ docs/` und jede
  verbliebene Fundstelle gegen Abschnitt 3 halten. Was übrig bleibt, muss dort
  begründet stehen — sonst ist es vergessen worden, nicht entschieden.

## 6. Was diese Vorlage nicht leistet

Sie ist **nicht geprüft am laufenden System**: Die Zahlen stammen aus
`grep`-Läufen über den Stand Web 9.14.1, nicht aus einem Durchklicken der
Oberfläche. Eine Zeichenkette, die nur in einem selten erreichten Fehlerzweig
steht, zählt hier gleich viel wie eine Überschrift.

Sie **entscheidet die drei Punkte aus Abschnitt 4 nicht** — sie legt sie vor.

Und sie geht davon aus, dass S2 die genannten Dateien bis dahin nicht weiter
verändert. Der S2-Zweig hat acht von elf Arbeitspaketen vor sich; die
Trefferzahlen in `Handbuch.md`, `Technik.md` und `backup_lib.php` werden bis
zur Umsetzung eher wachsen. **Vor der Umstellung neu zählen.**
