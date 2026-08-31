# Umstellung „Sicherung" → „Backup"

**Stand:** 31.08.2026 · **Zustand:** vorbereitet, nicht umgesetzt ·
**Umsetzung:** nach S2, in einem Zug

Dieses Dokument ist die Vorlage für eine Umstellung, die **noch nicht
stattgefunden hat**. Es sammelt den Befund, die Grenzen und die Arbeitsliste,
damit die Umstellung später an einem Stück laufen kann statt in Etappen, die
sich widersprechen.

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

## 2. Warum nach S2 und nicht jetzt

Die Umstellung liegt zur Hälfte in Dateien, die der S2-Zweig
(`claude/s2-ansatz-vtnw53`) gleichzeitig hält. Gemessen gegen die Merge-Basis
862559e:

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
| `docs/Konzept-P3-Oberflaeche.md` | 68 | frei |
| `server/adminbackup_lib.php` | 42 | frei |
| `docs/Konzept-S1-Sicherung-Import.md` | 37 | frei |
| `server/einstellungen.php` | 34 | frei |
| `server/admin_sicherungen.php` | 31 | frei |
| `server/admin_users.php` | 18 | frei |
| `server/assets/crypto.js` | 12 | frei |
| `server/ui.php` | 7 | frei |

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
