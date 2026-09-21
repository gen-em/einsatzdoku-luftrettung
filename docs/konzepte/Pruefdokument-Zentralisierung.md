# Prüfdokument — Zentralisierung: eine Stelle je Sache (Schritt 15)

**Stand:** 21.09.2026, nach **AP1** und **AP2** (Web 20.27.0) · **Zweig:** `claude/eager-euler-jlfi9i`,
von `origin/main` `fd99989` (Web 20.26.2) · **Konzept:**
`Konzept-Zentralisierung.md`

Dieses Dokument beantwortet „was muss **ich** noch tun?". Was belegt ist,
steht in Abschnitt 2; was **nicht** geprüft werden konnte, steht zuerst. Es
wächst mit jedem Arbeitspaket.

**AP1 hat `server/` nicht angefasst.** Gebaut wurden `tools/zaehlung/`
(`zaehlen.php`, `register.php`, `LIESMICH.md`), dieses Dokument und das
Konzept unter `docs/konzepte/`; nachgezogen wurde `docs/Technik.md`
(Verzeichnisstruktur und die Stufe-1-Tabelle, mit dem Vermerk „noch nicht
eingehängt" wie bei `tools/jobregister/`). **Keine Versionsstufe und kein
CHANGELOG-Eintrag** — CLAUDE.md 2.1: eine Änderung nur an `tools/` oder
`docs/` stuft keine der drei Zählungen hoch, und ohne Version gibt es keinen
CHANGELOG-Abschnitt, in den der Eintrag gehörte. Vom Auftraggeber am
20.09.2026 so bestätigt.

---

## 0. Was nicht geprüft werden konnte — und warum

**Diese Liste steht am Anfang und nicht in einer Fußnote.**

| # | Was | Warum nicht | Woran man es später erkennt |
|---|---|---|---|
| **N-1** | **Die Anwendung im Browser** | In dieser Umgebung liegt **keine Installation**: `server/config.php` fehlt, es gibt keine Datenbank. AP1 ändert allerdings auch keine Zeile unter `server/` — es gibt nichts zu bedienen. **Ab AP2 ist das ein echter Mangel und kein Formalismus** | **eingetreten mit AP2** — siehe N2-1 und Prüfliste A-1 bis A-12 |
| **N-2** | **Der Stufe-1-Schritt „Zentralisierung"** | Er entsteht erst in **AP10** (`.github/workflows/pruefung.yml`, eingetragen in `tools/kettenaufrufe`). Bis dahin läuft die Zählung nur von Hand | Punkt P-1; der Kettenschritt selbst in AP10 |
| **N-3** | **Die Wortliste hat die meisten neuen Dateien nicht angesehen** | `tools/zaehlung/*` und `docs/konzepte/*` fallen in **keinen** der fünf Bereiche (a `server/*.php` · b `server/assets/*.js` · c **elf namentlich genannte** normative Dokumente · d Android · e Uhr). Gesehen hat der Lauf von AP1 allein den Nachtrag in `docs/Technik.md` (Bereich c). Für den Rest meldet er 0 Treffer, ohne eine Zeile davon gelesen zu haben — CLAUDE.md 6 in eigener Sache: „Ein Lauf, der einen Bereich übergeht, meldet keine Null — er meldet gar nichts" | Punkt P-6 |
| **N-4** | **Ob das Register die richtigen Muster beschreibt** | Das Werkzeug zählt Vorkommen; **ob zwei Stellen dieselbe Sache tun**, behauptet das Register. Die Prüfung dieser Behauptung ist Lesearbeit, keine Messung | Punkt P-5 |
| **N-5** | **Kette II bis M1** | Nachgeprüft 20.09.2026: `claude/fervent-dirac-xirsqw` steht bei `af866c1` (AP4 gebaut, Beweislauf offen), 35 Commits nicht in `main`. M1 folgt auf AP4, AP5 und AP6 und ist ein Schritt der Betreiberin | **Voraussetzung für AP2** — Konzept, Statusblock |
| **N-6** | **Ob die neuen Muster auch in einem Jahr noch dasselbe treffen** | Ein Muster misst den Code, den es sieht. Kommt eine Schreibweise hinzu, die es nicht kennt (ein `date` über einen Variablennamen, ein SQL aus drei Teilen), zählt die Zeile still zu wenig — und eine zu niedrige Zahl sieht aus wie Erfolg | Punkt P-3 einmal je Paket wiederholen |

**Drei Zahlen, die nicht das sind, wonach sie aussehen:**

- **„398 Befunde" der Vollständigkeit ist die Schwelle, nicht null.** Gemessen
  vor und nach AP1: **398**. Rückgabe 1 — das ist der Normalzustand dieses
  Werkzeugs auf diesem Stand und kein Befund von AP1.
- **„0 Treffer" der Wortliste** gilt für 133 PHP-, 40 JS-, 11 Dokument-, die
  Android- und die Uhr-Dateien. **Nicht** für die Dateien dieses Pakets (N-3).
- **„77 Aufrufe in 32 Dateien"** (`error_log(`) gilt für `server/` **ohne**
  `vendor/`. Fremdcode wird nicht zentralisiert und nicht gezählt.

---

## 1. Was maschinell geprüft wurde — mit Mittel **und** Zahl

| Mittel | Aufruf | Zahl | Rückgabe |
|---|---|---|---|
| **Selbstprobe des Zählmittels** | `php tools/zaehlung/zaehlen.php --selbstprobe` | **33 von 33** | 0 |
| **Register, voller Lauf** | `php tools/zaehlung/zaehlen.php` | **38 Zeilen gemessen, 0 über der Decke** | 0 |
| **Eichung** (zwei vorher bekannte Zahlen) | im Lauf enthalten | `error_log(` **77 in 32** · `session_start(` **9 in 9** — beide getroffen | — |
| **Bestand** | im Lauf enthalten | **133 PHP-Dateien, 40 JS-Dateien** (`server/`, ohne `vendor/`) | — |
| **Gegenprobe Paket 1 und 3** (Z30–Z33) | `--zeile=Z3x --stellen` | **4 Zeilen, je 0 Treffer** — `password_resets`, `base_url`, `mail_rahmen(`, `usleep(` | 0 |
| **Zeilentreue der drei Sichten** | in der Selbstprobe | **519 Sichten geprüft, 0 schief** | 0 |
| **Schlägt die Zählung überhaupt an?** | zweite Stelle eingebaut (`error_log(` und `session_start(` in `server/version.php`), Lauf, Rückbau | Z01 **9 → 10**, Z38 **77 → 78**, „2 über der Decke", **Rückgabe 1**; nach Rückbau wieder **0 über der Decke, Rückgabe 0** | 1 / 0 |
| **`php -l` über `server/`** | Schleife über alle Dateien ohne `vendor/` | **133 Dateien, 0 Fehler** | 0 |
| **`php -l` über das neue Werkzeug** | `php -l tools/zaehlung/*.php` | **2 Dateien, 0 Fehler** | 0 |
| **Wortliste** | `python3 tools/wortliste/wortliste.py` — **nach** dem Nachtrag in `docs/Technik.md` gefahren (CLAUDE.md 6: die Prüfmittel laufen zuletzt) | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen**; 99 Regeln, 99 gegriffen. **Bereiche a–e, nicht `tools/`** (N-3) | 0 |
| **Sperrliste von Hand gegen die neuen Dateien** (weil die Wortliste sie nicht sieht, N-3) | die 24 Muster aus `tools/wortliste/sperrliste.json` gegen `zaehlen.php`, `register.php`, `LIESMICH.md`, Konzept und dieses Dokument | **24 Muster × 5 Dateien: 2 Treffer**, beide `spur` („Spurprobe" in der Liste der nicht gefahrenen Prüfmittel). **Kein Befund:** „Spur" ist der Fachbegriff des Projekts (CLAUDE.md 4, E-S9-03) und in neun Regeln der Ausnahmeliste als solcher geführt | — |
| **Vollständigkeit** | `python3 tools/vollstaendigkeit/pruefen.py` | **398** — unverändert gegenüber dem Stand vor dem Paket | 1 (Schwelle) |
| **Kontraste** | `python3 tools/screenshots/kontrast.py` | **22 Paare gerechnet, 0 verfehlt** | 0 |
| **Kettenaufrufe** | `python3 tools/kettenaufrufe/pruefen.py` | **3 Arbeitsläufe, 30 Aufrufe geprüft, 0 Befunde, 0 ungeprüft** | 0 |
| **CSP** | `php tools/cspprobe/pruefen.php` | **0 Befunde** | 0 |
| **Migrationsregister** | `php tools/migrationsregister/pruefen.php` | **0 Befunde, 0 ungenutzte Ausnahmen** | 0 |
| **`server/` unverändert** | `git status --short` | nur `tools/zaehlung/` und `docs/konzepte/Konzept-Zentralisierung.md` neu — **keine Datei unter `server/` berührt** | — |

**Nicht gefahren, weil AP1 nichts berührt, was sie messen:** Bilderlauf,
Stilvergleich, Kreisläufe csv und edbak, Ingest-, Kopplungs-, Raten-,
Abmelde-, Mail-, Spur-, GPX-Probe, Messstand, Android- und Uhr-Prüfstand. Sie
stehen ab dem Paket an, das ihren Gegenstand anfasst (Konzept Abschnitt 5).

## 2. Was im Browser geprüft wurde

**Nichts — und das ist hier kein Mangel.** AP1 ändert keine Zeile unter
`server/`; es gibt keinen Bedienweg, der sich geändert hätte. Ab AP2 gilt
N-1.

## 3. Prüfliste — was **die Auftraggeberin** tun muss

Je Punkt: der Bedienweg, das erwartete Ergebnis, und **woran ein Scheitern zu
erkennen ist**.

| # | Weg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **P-1** | `php tools/zaehlung/zaehlen.php` im Repositoriumswurzelverzeichnis | Tabelle mit **38 Zeilen**, letzte Zeile „38 Zeilen gemessen, 0 über der Decke", Rückgabe **0** (`echo $?`) | Eine Zeile trägt „DRUEBER" und Rückgabe 1 — dann steht unter `server/` eine zweite Stelle, die dort nicht hingehört. `--zeile=Zxx --stellen` nennt Datei und Zeile |
| **P-2** | `php tools/zaehlung/zaehlen.php --selbstprobe` | **„Selbstprobe: 33 von 33"**, Rückgabe 0 | Eine Zeile beginnt mit `FEHL`. Dann misst das Werkzeug selbst falsch, und **jede** Zahl dieses Dokuments ist hinfällig — vor allem die grünen |
| **P-3** | **Einmal absichtlich rot sehen.** In eine beliebige Datei unter `server/` die Zeile `error_log('probe');` einfügen, `php tools/zaehlung/zaehlen.php`, Zeile wieder entfernen | Zeile **Z38** springt von 77 auf 78 und trägt „DRUEBER", Rückgabe **1**; nach dem Entfernen wieder 0 | Der Lauf bleibt grün. Dann zählt die Zeile nicht, was sie zu zählen vorgibt — der gefährlichere Fall, weil er wie Erfolg aussieht. (Von mir am 20.09.2026 gefahren, Ergebnis in Abschnitt 1 — **eine zweite, unabhängige Ausführung ist trotzdem sinnvoll**) |
| **P-4** | Die **Eichung** unabhängig nachrechnen: `grep -rc "error_log(" server --include=*.php \| grep -v vendor` und die Zahl gegen 77 halten | Der `grep` liefert **mehr** als 77 — er zählt Kommentare mit. Das Werkzeug zählt 77 echte Aufrufe | Der `grep` liefert **weniger** als 77. Dann zählt das Werkzeug etwas, das es nicht gibt |
| **P-5** | **Das Register gegenlesen** (`tools/zaehlung/register.php`) — **nicht jetzt, sondern je Paket.** 32 der 38 Zeilen enden auf Decke 0 oder 1; dort gibt es nichts zu beurteilen. Die sechs Zeilen darüber stehen unten in Abschnitt 5 | Jede der sechs Zahlen kommt aus einer Entscheidung des Konzepts oder ist bis zu dem Paket offen, das sie misst | Eine Decke, die zu hoch steht, macht die Zeile stumm — sie meldet nie etwas, und das sieht aus wie Erfolg. Deshalb wird jede Herunterschreibung vom messenden Paket **mit der gemessenen Zahl** vorgelegt, nicht nebenbei gesetzt |
| **P-6** | **Die neuen Texte gegenlesen** — `tools/zaehlung/LIESMICH.md`, die Kopfkommentare von `zaehlen.php` und `register.php`, dieses Dokument | Land und Luft neutral benannt (R28) | Die Wortliste sagt dazu nichts (N-3). Fällt ein Luftbegriff auf, gehört er in die Ausnahmeliste **mit Begründung** oder heraus |
| **P-6a** | **Die vier Abweichungen bestätigen** (Konzept 1.0a): Z04 46 statt 44, Z19 42 statt 43, Z21 27 statt 26, Z18 12 statt 11 in 6 | Die Begründungen tragen. Bei **Z04** trägt sie nur zur Hälfte: Der Mehrtreffer in `db.php` ist erklärt (Z. 9 legt `$CFG` an), der in `serverkrypto_lib.php` nicht — alle 18 sind echte Zugriffe | Wenn eine der vier Begründungen nicht überzeugt, gilt trotzdem die Zahl des Werkzeugs; dann gehört der Widerspruch ins Konzept, nicht in eine stille Korrektur |
| **P-7** | **Nachsehen, dass kein Paket doch in die Steuerungsdokumente geschrieben hat:** `git diff origin/main --stat -- docs/Rahmenplan.md docs/Backlog.md` | **leere Ausgabe** — beide Dateien unverändert gegenüber `main` (AP1-f) | Es erscheint eine Zeile. Dann trägt der Zweig eine Änderung an einer Datei, die auf dem Kette-II-Zweig parallel fortgeschrieben wurde — beim Merge droht eine **stille** Doppelvergabe, kein Konflikt |
| **P-8** | **Beim Einspielen nach dem Merge von Kette II:** die Einschübe aus Konzept Abschnitt 8 und 9 durchgehen und die Nummern aus der Spanne ab **250** vergeben | Jeder Eintrag bekommt genau eine Nummer; die Spanne wird im Backlog-Kopf eingetragen, **bevor** gepusht wird | Eine Nummer trägt zwei Punkte — der Fall, den der Backlog-Kopf für 215–225 und für 240 beschreibt. **Erst nach dem Merge von Kette II fällig**, nicht jetzt |

## 4. Grenzen der benutzten Prüfmittel

Ausdrücklich, damit eine grüne Zahl nicht mehr trägt, als sie kann:

- **Das Zählmittel sagt nicht, ob ein Treffer erreichbar ist.** Eine
  Anweisung in einem `if`, das nie zutrifft, zählt mit.
- **Es findet keinen neu erfundenen Namen.** **Z34** (JS-Formatierer) hält
  eine **Namensliste** von vierzehn Definitionen auf null — es gibt kein
  Muster, das `fmtTag`, `wertKmSumme` und `durationHHMM` trifft und
  `wertLesen()` in `suche.php` ausläßt. Ein fünfzehnter Formatierer unter
  neuem Namen fällt dieser Zeile nicht auf; dafür ist die Lesearbeit in AP8
  da. Dasselbe gilt abgeschwächt für jede Musterzeile (N-6).
- **Z18 zählt Nähe, nicht Sinn.** Eine Handliste ist eine Aufzählung von zehn
  verschiedenen `missions`-Spaltennamen mit höchstens 60 Zeichen Abstand,
  deren Tabellenangabe `missions` lautet. `rest_segments` teilt sich **genau
  zehn** Spaltennamen mit `missions` — ohne die Tabellenprüfung meldete die
  Zeile zwei Ruhesegment-Anweisungen mit. Beide Fälle stehen in der
  Selbstprobe; hineingelaufen bin ich zweimal.
- **Der JS-Zerleger ist eine Heuristik**, keine ECMAScript-Grammatik —
  Portierung von `js_bereiche()` aus `tools/wortliste/zerlegen.py` samt deren
  Grenzen. Im Zweifel bleibt stehen, was nicht sicher ein Kommentar ist: ein
  Treffer zu viel kostet eine Ausnahme, ein Treffer zu wenig die Aussage.
- **`vendor/` ist draußen.** Unter `server/vendor/phpseclib3/Crypt/Random.php`
  stehen zwei weitere echte `session_start()`. Wer „neun Sitzungsstarts"
  zitiert, zitiert eine Zahl mit dieser Bedingung.
- **Code, der zur Laufzeit entsteht** (`eval`, zusammengesetzte
  Funktionsnamen, JS aus einer PHP-Zeichenkette), ist unsichtbar. Kommt in
  `server/` nicht vor; sollte es einmal vorkommen, sieht die Zählung es nicht.
- **Die Vollständigkeit meldet 398 und Rückgabe 1.** Das ist die Schwelle
  dieses Stands, nicht ein Befund von AP1.

---

## 5. Die sechs Decken über 1 — woher ihre Zahl kommt

32 der 38 Registerzeilen enden auf **0 oder 1** — „eine Stelle" ist die
Aussage des ganzen Schritts, da ist nichts zu entscheiden. Diese sechs enden
höher, und **keine der Zahlen ist in der Umsetzung erfunden**:

| Zeile | Start → Decke | Woher die Zahl kommt | Wer sie festnagelt |
|---|---|---|---|
| **Z38** `error_log(` | 77 → **77** | **E-ZE-05**: Schritt 15 stellt keinen einzigen Aufruf um. Gehört nicht hierher | 10c AP3 setzt auf 2 (Nr. 248) |
| **Z15** `information_schema` in `migration_lib.php` | 57 → **57** | **E-ZE-04** (Auftraggeber, 20.09.2026): gelaufene Migrationen werden nicht umgebaut. Die Decke friert den Stand ein, damit er nicht **wächst** | erledigt sich mit dem neuen Migrationsregister in P8 (R66) |
| **Z22** Byte-Division für die Anzeige | 18 → **18** | **Platzhalter.** Von den 18 sind manche Anzeigen (müssen über `groesse_text()`) und manche Grenzwerte (bleiben). Welche welche sind, steht erst nach dem Auszählen fest — das Konzept sagt „AP7 nennt" | **AP7**, mit Zahl und namentlichen Ausnahmen |
| **Z16** `beginTransaction(` | 33 → **8** | **Haltepunkt H-ZE-4** des Konzepts: „mehr als acht Transaktions-Ausnahmen → melden vor dem Umbau" | **AP5** zählt zuerst die Bauformen aus und trägt sie ins Konzept nach |
| **Z14** `information_schema` außerhalb | 9 → **4** | Konzept AP4: „erwartet 3 bis 4" — was bleibt, fragt keine Existenz, sondern Spaltenlisten, Größen oder `is_nullable` | **AP4**, mit der Entscheidung, ob ein vierter Helfer lohnt (R83) |
| **Z18** Handlisten `missions` | 12 → **4** | **E-ZE-22**: die vier **Abbildungen** dürfen von Hand bleiben, wenn eine Vollständigkeitsprobe belegt, dass sie genau die Registerspalten ihres Zwecks führen. Die sieben **SQL-Listen** werden erzeugt | **AP6**, mit der Probe je verbleibender Liste |

**Der Umgang damit ist die Regel, nicht die Ausnahme:** Das Paket, das eine
Decke herunterschreibt, legt die **gemessene** Zahl vor. Eine Decke wird nicht
angehoben, ohne dass es im Konzept steht (E-ZE-24).

---

# AP2 — Konfiguration und Sitzung (Web 20.27.0, 21.09.2026)

**AP2 hat `server/` angefasst — 21 Dateien.** Damit gilt N-1 aus Abschnitt 0
zum ersten Mal wirklich, und das ist der wichtigste Satz dieses Abschnitts.

## A0. Was nicht geprüft werden konnte — und warum

| # | Was | Warum nicht | Woran man ein Scheitern erkennt |
|---|---|---|---|
| **N2-1** | **Jeder Weg durch die Anwendung im Browser** — Anmeldung bis Tagesübersicht, Abmelden, Passwort-Reset, Handbuch/Rechtstext/Notfallblatt angemeldet, `install.php`, `wiederherstellen.php` | In der Arbeitsumgebung liegt **keine Installation**: `server/config.php` fehlt, es gibt keine Datenbank. Gemessen: `php -r 'require "server/db.php";'` bricht mit „config.php fehlt" ab — das ist der erwartete Abbruch, aber eben auch das Ende jedes Browserwegs | **Prüfliste A-1 bis A-9.** Dieses Paket fasst JEDE Sitzung der Anwendung an; wenn etwas bricht, bricht es hier |
| **N2-2** | **Abmelde-, Raten-, Kopplungsprobe** (Konzept Abschnitt 5 verlangt sie für AP2–AP5) | Alle drei sprechen über echtes HTTP mit einer laufenden Anlage | Prüfliste A-10 |
| **N2-3** | **Ob `session.use_strict_mode` WIRKT** | `ini_set()` kann scheitern — manche Hoster sperren einzelne Direktiven. Dass die Zeile dasteht, ist gemessen; dass sie greift, misst nur eine laufende Anlage | Prüfliste A-11 |
| **N2-4** | **F-ZE-2 gegen echte Bots** | Nachgestellt mit einem Aufruf ohne Cookie (bestanden). Ob ein echter Crawler kein Cookie mitbringt, misst nur der Betrieb | Prüfliste A-6 — die Zahl in `.sitzungen/` muss **fallen** |
| **N2-5** | **Das Verhalten hinter einem Reverse Proxy** | `netz_proxys()` liest jetzt über `konfig()`. Die Umstellung ist eng und gelesen; eine Anlage mit eingetragenen Proxys stand nicht zur Verfügung | Prüfliste A-12 |

## A1. Was maschinell geprüft wurde — mit Mittel **und** Zahl

| Mittel | Zahl |
|---|---|
| **Registerzeilen des Pakets** | Z01 `session_start(` **9 → 1** · Z02 `sitzung_ablage(` **2 → 1** · Z03 `config.php` lesend **7 → 1** · Z04 `$CFG` **46 → 0** |
| **Register gesamt** | **38 Zeilen, 0 über der Decke**; Selbstprobe **33 von 33** |
| **`error_log(`** (E-ZE-05) | **77 in 32 Dateien — unverändert.** Kein Aufruf umgestellt, kein Aufruf verschoben |
| **`konfig_lib` — eigene Probe** | **13 von 13**: Punktpfad zwei Ebenen · oberste Ebene ohne Punkt · Feld als Wert · fehlender Schlüssel → Vorgabe · Pfad durch einen Nicht-Array → Vorgabe · fehlende `config.php` → Vorgabe · Merker hält · `konfig_verwerfen()` liest neu · gelöschte Datei → wieder Vorgabe |
| **Die vier Sitzungsarten** | **16 Zellen** (4 Arten × `secure`, `samesite`, `httponly`, Sitzungsname), **0 Abweichungen** gegen die Tabelle aus Konzept 1.3. `use_strict_mode` in allen vier auf `1`. Je Art ein eigener Prozess — nach dem ersten `session_start()` lassen sich die Parameter nicht mehr für eine andere Art messen |
| **F-ZE-2** | Art `lesend` **ohne** Cookie: `lief=false`, `session_status()` = keine · **mit** Cookie: `lief=true`, aktiv |
| **Ladezyklus** (die Falle aus Konzept 3.0) | **7 von 7**, über `get_included_files()`: `konfig_lib.php` zieht **0** Dateien nach · `sitzung_lib.php` beim Laden **0** · `plattform_lib.php` zieht `email_lib.php` und `php_mindest.php` nach und erreicht `db.php` **nicht** · `db.php` verlangt `config.php` hart · `db.php` richtet die Ablage **nicht mehr** ein (Lage `nicht_gelaufen`) · `sitzung_starten()` richtet sie ein · unbekannte Art **wirft** statt still zu starten |
| **Sitzungshärtung, umgestellt** | Lauf **0 Befunde** bei **1 Aufruf** (erwartet 1); Selbstprobe **12 von 12**, darunter „gehärtet, aber am falschen Ort" und „zwei Aufrufe — der zweite ist ein Befund" |
| `php -l` | `server/` **134 Dateien, 0 Fehler** · `tools/` **30 Dateien, 0 Fehler** |
| Wortliste | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** |
| Vollständigkeit | **398** — die Schwelle, unverändert. *Sprang zwischendurch auf 399, siehe unten* |
| Kontraste · Kettenaufrufe · CSP · Migrationsregister · Jobregister · Installweiche · Schemaprobe | **22/0** · **0 Befunde, 0 ungeprüft** · **0** · **0 Befunde, 0 ungenutzte Ausnahmen** · **0** · **0** · **4 Prüfungen, 0 Fehlschläge** |

**Zwei Zahlen, die etwas anderes heißen, als sie aussehen:**

- **„398" der Vollständigkeit ist die Schwelle, nicht null.** Der erste Lauf
  nach dem Paket meldete **399** — ein Auslassungszeichen (U+2026) in einem
  neuen Kommentar in `db.php`. Dieselbe Falle, dieselbe Datei, einen Tag nach
  Schritt 16, wo sie im Prüfdokument steht. Ersetzt durch drei Punkte.
- **„16 Zellen" statt der im Konzept genannten 12.** Das Konzept verlangt
  4 Arten × 3 Attribute; gemessen wurde zusätzlich der **Sitzungsname**,
  weil die Art `passwort` als einzige einen eigenen trägt (`EDPWSESS`) und
  ein falscher Name dort bedeutete, dass ein Passwort-Reset die
  Anmeldesitzung überschreibt.

## A2. Was im Browser geprüft wurde

**Nichts.** Siehe N2-1. Das ist bei diesem Paket kein Formalismus: AP2 fasst
jeden Sitzungsstart der Anwendung an.

## A3. Prüfliste AP2 — was **die Auftraggeberin** tun muss

Auf einer Anlage mit Web 20.27.0. Je Punkt: Weg, Erwartung, Scheiternsmerkmal.

| # | Weg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **A-1** | **Anmelden** und bis zur Tagesübersicht durchklicken | Anmeldung geht, Tagesübersicht lädt, keine Schleife | Endlose Weiterleitung zwischen `login.php` und `index.php`. Dann sieht `auth_guard.php` die Sitzung nicht, die `login.php` gesetzt hat — die beiden benutzen jetzt dieselbe Art `app`, also wäre die Ablage schuld |
| **A-2** | **Abmelden** | Rückkehr zur Anmeldung mit Grundmeldung; erneuter Aufruf einer angemeldeten Seite führt zur Anmeldung | Man bleibt angemeldet, oder die Abmeldeseite bricht ab |
| **A-3** | **Passwort vergessen** → Mail → Link → neues Passwort setzen | Der Weg geht durch; das Cookie heißt `EDPWSESS` (Entwicklerwerkzeuge → Anwendung → Cookies) | Heißt es `PHPSESSID`, ist die Art falsch gewählt — dann überschreibt ein Passwort-Reset die Anmeldesitzung |
| **A-4** | **Handbuch, „Was ist NAdoku", Rechtstexte, Notfallblatt — angemeldet** | Der **angemeldete Kopf** steht da wie vorher | Kopf fehlt oder zeigt „nicht angemeldet". Dann greift die Cookie-Bedingung zu streng |
| **A-5** | Dieselben Seiten **im privaten Fenster** (nicht angemeldet) | Seiten sind lesbar; **kein** `Set-Cookie` in den Antwortkopfzeilen | Ein `Set-Cookie` erscheint → F-ZE-2 wirkt nicht |
| **A-6** | **Betrieb → Status**, Zeile „Sitzungsablage" und die Zahl der Sitzungsdateien; nach ein paar Tagen erneut | Die Zeile sagt dasselbe wie vor dem Update; die **Zahl fällt** über die Tage, weil Bots keine Datei mehr anlegen | Die Zeile sagt „nicht gelaufen". Dann füllt `sitzung_starten()` den Stand nicht — die Statusseite läuft über `auth_guard.php`, also müsste er stehen |
| **A-7** | **`install.php`** auf einer leeren Anlage (oder im Prüfstand) | Startseite erscheint, Einrichtung läuft durch | Weiße Seite oder „Call to undefined function konfig" → `konfig_lib.php` fehlt in der Auslieferung |
| **A-8** | **`wiederherstellen.php`** aufrufen | Die Seite lädt und zeigt ihr Formular | Abbruch am Sitzungsstart |
| **A-9** | **Server­einstellungen → Schlüssel des Servers**: einen Schlüssel anlegen oder erneuern | Die Seite zeigt **sofort** den neuen Stand, nicht mehr „fehlt" | Sie zeigt weiter „fehlt" → `konfig_verwerfen()` greift nicht (E-ZE-14, derselbe Fehler wie S2/AP7) |
| **A-10** | `tools/abmelde-probe/`, `tools/ratenprobe/`, `tools/kopplungsprobe/` gegen die Anlage | grün wie vor dem Paket | Rot — dann hat die Sitzungsumstellung einen dieser Wege getroffen |
| **A-11** | **Wirkt die Härtung?** Mit einer selbst gesetzten Sitzungskennung anmelden (Befehl in `tools/sitzungshaertung/LIESMICH.md`) | Es kommt eine **andere** Kennung zurück | Dieselbe Kennung kommt zurück → `session.use_strict_mode` greift nicht, und dann hängt der Schutz weiter an der `php.ini` |
| **A-12** | Nur wenn `netz.vertrauenswuerdige_proxys` **eingetragen** ist: Betrieb → Status, Zeile „Vertrauenswürdige Proxys" | Dieselbe Zahl wie vorher | „keine eingetragen", obwohl welche dastehen → `konfig()` liest den Pfad falsch |

## A4. Grenzen — was sich mit diesem Paket NICHT beantworten lässt

- **Dass es genau einen `session_start()` gibt, heißt nicht, dass jede Seite
  die richtige ART wählt.** Das Werkzeug zählt Aufrufe, nicht Absichten. Die
  Zuordnung ist Lesearbeit; sie steht in der Tabelle `SITZUNG_ARTEN` und in
  Konzept 1.3. Prüfpunkte A-3 und A-4 sind der Gegentest von außen.
- **Die Messung der Cookie-Parameter lief auf der Kommandozeile**, wo
  `$_SERVER['HTTPS']` nicht gesetzt ist. „HTTPS-abhängig" war dort also
  immer `false`. Dass der Zweig mit HTTPS `true` liefert, ist gelesen und
  nicht gemessen.
- **`konfig()` kennt keinen Schlüssel mit einem Punkt im Namen.** Heute hat
  keiner einen; wer einen einführt, muss das wissen. Steht im Kopf von
  `konfig_lib.php`.
