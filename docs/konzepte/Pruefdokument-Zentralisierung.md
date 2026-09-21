# Prüfdokument — Zentralisierung: eine Stelle je Sache (Schritt 15)

**Stand:** 21.09.2026, nach **AP1**, **AP2** (Web 20.27.0) und **AP3** (Web 20.28.0) · **Zweig:** `claude/eager-euler-jlfi9i`,
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

**Diese Liste ist mit dem 21.09.2026 kurz geworden.** In der Arbeitsumgebung
steht seither eine **vollständige lokale Anlage** (MariaDB, `php -S`, socat
für TLS, Demo-Bestand mit 106 Einsätzen und 21 Diensttagen), eingerichtet mit
`tools/referenzdatensatz/einspielen/lokal_einrichten.sh`. Damit ist die
Prüfliste A-1 bis A-12 **gefahren** und nicht mehr offen.

| # | Was | Warum nicht | Woran man ein Scheitern erkennt |
|---|---|---|---|
| **N2-1** | **Der Betrieb auf einer echten Anlage** — anderer Hoster, andere `php.ini`, echte Last | Die lokale Anlage ist ein `php -S` im Wegwerf-Container. Sie belegt das **Verhalten der Anwendung**, nicht das einer Hosterumgebung | Die Zeile „Sitzungsablage" auf Betrieb → Status beim ersten Ausrollen |
| **N2-2** | **`secure` am Cookie der Arten `lesend` und `einrichtung`** | socat terminiert TLS, PHP sieht eine HTTP-Anfrage — `!empty($_SERVER['HTTPS'])` ist dort **immer** falsch. Gemessen ist deshalb der HTTPS-freie Zweig; dass der andere `true` liefert, ist gelesen | Auf einer echten Anlage fehlt `secure` am Cookie von `install.php` oder `hilfe.php`, obwohl HTTPS anliegt |
| **N2-3** | **F-ZE-2 gegen echte Bots** | Nachgestellt mit 40 Abrufen ohne Cookie (bestanden, siehe A1). Ob ein echter Crawler kein Cookie mitbringt, misst der Betrieb | Die Zahl der Sitzungsdateien auf der Statusseite fällt **nicht** |
| **N2-4** | **Die Android- und Uhr-Clients** | AP2 fasst `server/` an; der Gerätevertrag ist davon nicht berührt (keine der fünf Geräte-Dateien geändert). Der Android-Prüfstand ist nicht gelaufen | Erst ab AP3 relevant, wo `ingest.php` und `pair.php` im Umfang stehen |

## A1. Was maschinell geprüft wurde — mit Mittel **und** Zahl

### Gegen den Quelltext

| Mittel | Zahl |
|---|---|
| **Registerzeilen des Pakets** | Z01 `session_start(` **9 → 1** · Z02 `sitzung_ablage(` **2 → 1** · Z03 `config.php` lesend **7 → 1** · Z04 `$CFG` **46 → 0** |
| **Register gesamt** | **38 Zeilen, 0 über der Decke**; Selbstprobe **34 von 34** |
| **`error_log(`** (E-ZE-05) | **77 in 32 Dateien — unverändert** |
| **Sitzungshärtung, umgestellt** | Lauf **0 Befunde** bei **1 Aufruf**; Selbstprobe **12 von 12** |
| `php -l` | `server/` **134 Dateien, 0 Fehler** · `tools/` **30 Dateien, 0 Fehler** |
| Wortliste · Vollständigkeit · Kontraste · Kettenaufrufe · CSP · Migrationsregister · Jobregister · Installweiche · Schemaprobe | **0/0** · **398** (Schwelle) · **22/0** · **0/0** · **0** · **0** · **0** · **0** · **4 Prüfungen, 0 Fehlschläge** |

### Gegen die laufende Anlage — neu am 21.09.2026

| Mittel | Zahl |
|---|---|
| **Bilderlauf, 62 Seiten** | **496 Einzelbilder, 62 Kontaktbögen** · Überlauf **0** · **Konsolenfehler 0** · Knöpfe falscher Höhe **0** · Karten außerhalb `main.inhalt` **0 von 162** · Rückgabe **0** |
| **Die vier Sitzungsarten, live über HTTPS** | `app` (`login.php`): `PHPSESSID`, **secure**, **Strict** · `passwort` (`pw_handling.php`): **`EDPWSESS`**, **secure**, **Lax** · `einrichtung` (`install.php`, `wiederherstellen.php`): `PHPSESSID`, **Lax** · alle vier **HttpOnly** |
| **F-ZE-2 — der eigentliche Beleg** | **40 anonyme Abrufe** von `hilfe.php` und `impressum.php` → **0 neue Sitzungsdateien** (vorher 7, nachher 7). Vier öffentliche Seiten: **0 `Set-Cookie`** bei HTTP 200 |
| **Härtung wirkt** (war als „nicht messbar" geführt) | Untergeschobenes `PHPSESSID=abc123…` → zurück kam **eine andere Kennung**; **keine** Sitzungsdatei unter der vorgegebenen |
| **`konfig_verwerfen()` greift** (A-9) | **8 von 8**: `config_eintrag_schreiben()` schreibt, `konfig()` und `kdf_anteil_alt()` sehen den neuen Wert **in derselben Anfrage**, der Nachbarschlüssel bleibt, Entfernen wirkt sofort |
| **Proxys über `konfig()`** (A-12) | **7 von 7**: frische Anlage hat keinen `netz`-Abschnitt → Vorgabe; mit zwei Einträgen liest `netz_proxys()` beide; Statuszeile sagt „2 eingetragen"; `config.php` danach **bytegleich** |
| **Ladezyklus** | **7 von 7** über `get_included_files()` |
| **`konfig_lib` einzeln** | **13 von 13** |
| **Abmelde-Probe** | V-10 **erfüllt**, kein Schlüsselmaterial nach dem Abmelden, **keine Seitenfehler** |
| **Kopplungsprobe** | **76 Erwartungen, 0 nicht erfüllt, 0 übergangen** |
| **Ratenprobe** | **50 Prüfungen, 0 Befunde** — der eine Befund war **nicht von AP2** und ist am selben Tag behoben (Backlog **Nr. 254**) |
| **Ingest-, Anteil-, Versand-, Wiederherstellungs- und Komplettprobe** | **448 Erwartungen, 0 nicht erfüllt.** Zuvor **30 offen** — nicht wegen eines Fehlers der Anwendung, sondern weil diese fünf Werkzeuge an der mit AP2 entfallenen globalen `$CFG` hingen (Backlog **Nr. 257**, am selben Tag behoben). Einzeln: 83/0 · 55/0 · 135/0 · 111/0 · 64/0 |

**Drei Zahlen, die etwas anderes heißen, als sie aussehen:**

- **„398" der Vollständigkeit ist die Schwelle, nicht null.** Der erste Lauf
  nach dem Paket meldete **399** — ein Auslassungszeichen (U+2026) in einem
  neuen Kommentar in `db.php`. Dieselbe Falle, dieselbe Datei, einen Tag nach
  Schritt 16, wo sie im Prüfdokument steht.
- **„0 Konsolenfehler" gilt erst seit dem Kopierschritt.** Der erste
  Bilderlauf meldete **48** — drei fehlende Handbuch-Bilder mal 16 Breiten,
  weil `lokal_einrichten.sh` den Kopierschritt der Auslieferungskette nicht
  hatte. **Kein Fehler der Anwendung**, sondern des Prüfstands: Backlog
  **Nr. 255**, am selben Tag behoben.
- **„134 PHP-Dateien" gilt seit dem 21.09.2026 auch mit eingerichteter
  Anlage.** Vorher zählten `tools/zaehlung/` und `tools/sitzungshaertung/`
  **135** — sie lasen `server/config.php` mit, die nur dort liegt, wo eine
  Anlage steht. Zeile **Z31** ging dadurch über ihre Decke (`'base_url'` in
  `config.php`), ohne dass sich eine Zeile Code geändert hätte. Beide
  Werkzeuge nehmen die Datei jetzt aus, mit derselben Begründung wie
  `tools/wortliste/` seit jeher; ein Selbstprobenfall hält es fest.
- **„448 Erwartungen, 0 offen" ist erst seit dem Nachtrag zu AP2 wahr.** Die
  Zählung meldete für `$CFG` **46 → 0** und AP2 galt als erledigt — Zeile Z04
  mass aber nur `server/`. Fünf Prüfwerkzeuge unter `tools/` lasen oder
  setzten dieselbe Globale weiter; **30 Erwartungen standen still auf „nicht
  erfüllt"**, und die Wiederherstellungsprobe übersprang sieben weitere ganz.
  Z04 misst seit dem 21.09.2026 `server/` **und** `tools/`.
- **Die Ratenprobe stand einen Monat auf einem Befund, den niemand sah.**
  Sie zählte sechs Töpfe gegen eine von Hand geführte Fünf; `blatt` kam mit
  Web 20.24.0 dazu. Der Grund für den Monat: Sie braucht eine laufende Anlage
  und hängt nicht in Stufe 1. Behoben am 21.09.2026 (Nr. 254) — die Liste ist
  jetzt der Sollwert, und der Satz rechnet seine Zahl aus ihr.

## A2. Was im Browser geprüft wurde

**Der ganze Weg, in Chromium, angemeldet und abgemeldet.**

| Punkt | Gemessen |
|---|---|
| **A-1 Anmeldung** | `login.php` → `index.php`, **keine Schleife**; Cookie `PHPSESSID` mit **secure, SameSite=Strict, HttpOnly** |
| **A-2 Abmelden** | Danach führt `index.php` auf `login.php`; Abmelde-Probe: V-10 erfüllt |
| **A-3 Passwort-Weg** | Cookie heißt **`EDPWSESS`** (nicht `PHPSESSID`), secure, Lax. Zusätzlich: `lokal_einrichten.sh` setzt das Admin-Passwort **über genau diesen Weg** — er ist bei jeder Einrichtung gelaufen |
| **A-4 Die fünf öffentlichen Seiten angemeldet** | **5 von 5 zeigen angemeldet etwas anderes als abgemeldet.** `ueber.php`: „Startseite, Suche" statt „Zur Anmeldung" · `impressum.php`: „**Du bist mit Verwaltungsrechten angemeldet**" — also wird die Rolle weiterhin **aus der Datenbank** gelesen · `notfallblatt.php`: die **Kontoadresse** steht auf dem Blatt · `hilfe.php` und `datenschutz.php` ebenso |
| **A-5 Dieselben Seiten anonym** | HTTP 200, **0 `Set-Cookie`**, **0 neue Sitzungsdateien** nach 40 Abrufen |
| **A-6 Betrieb → Status** | Zeile „Sitzungsablage" steht da und sagt **nicht** „nicht gelaufen" |
| **A-7 `install.php`** | Die ganze Einrichtung ist über diesen Weg gelaufen — Schema, Admin-Anlage, `config.php` |
| **A-8 `wiederherstellen.php`** | HTTP 200, Cookie mit SameSite=Lax (Art `einrichtung`) |
| **Konsolenfehler im Klickweg** | **keine** |

**Offen bleibt allein, was eine echte Anlage braucht** — siehe A0.

## A3. Prüfliste — was **die Auftraggeberin** noch tun muss

Deutlich kürzer als gestern. Die zwölf Punkte sind gefahren; was bleibt, ist
das, was ein Wegwerf-Container nicht beantworten kann.

| # | Weg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **B-1** | Nach dem Ausrollen: **Betrieb → Status**, Zeile „Sitzungsablage" | Dasselbe wie vor dem Update | „nicht gelaufen" → dann füllt `sitzung_starten()` den Stand auf dieser Anlage nicht |
| **B-2** | Dieselbe Seite, **Zahl der Sitzungsdateien**, nach ein paar Tagen erneut | Die Zahl **fällt**, weil Bots keine Datei mehr anlegen | Sie steigt weiter wie bisher → F-ZE-2 greift auf dieser Anlage nicht |
| **B-3** | In den Entwicklerwerkzeugen das Cookie von `hilfe.php` und `install.php` ansehen, **auf der echten Anlage mit HTTPS** | Beide tragen **`secure`** | `secure` fehlt → dann sieht PHP die Anfrage nicht als HTTPS (Proxy-Kopfzeile, N2-2) |

## A4. Grenzen — was sich mit diesem Paket NICHT beantworten lässt

- **Dass es genau einen `session_start()` gibt, heißt nicht, dass jede Seite
  die richtige ART wählt.** Das Werkzeug zählt Aufrufe, nicht Absichten. Der
  Gegentest von außen ist A-3 und A-4, und der ist gefahren: Das
  Passwort-Cookie heißt `EDPWSESS`, die fünf öffentlichen Seiten erkennen die
  Anmeldung.
- **Die lokale Anlage ist ein `php -S`.** Sie belegt das Verhalten der
  Anwendung, nicht das eines Hosters. `secure` bei den HTTPS-abhängigen Arten
  bleibt ungemessen (N2-2).
- **`konfig()` kennt keinen Schlüssel mit einem Punkt im Namen.** Heute hat
  keiner einen; wer einen einführt, muss das wissen.

---

# AP3 — API-Eingang und Flash (Web 20.28.0, 21.09.2026)

**AP3 hat `server/` angefasst — 25 Dateien** (20 unter `api/`, dazu `db.php`,
`session_lib.php`, `einstellungen.php`, `nachbearbeitung.php`,
`papierkorb.php`). Die lokale Anlage stand zur Verfügung; fast alles ist
gegen sie gefahren worden.

## C0. Was nicht geprüft werden konnte — und warum

**Diese Liste steht am Anfang.**

| # | Was | Warum nicht | Woran man ein Scheitern erkennt |
|---|---|---|---|
| **N3-1** | **Der punktweise GPX-Vergleich** (`tools/gpxprobe`, Teil „Jeder Punkt stimmt mit der Browserfassung überein") | Das Demo-Konto dieser Anlage ist heute zurückgesetzt worden; die Einsatz-Kennungen passen nicht mehr zum Referenzexport vom 15.09.2026. Die Probe meldet „190 von 204 ohne Gegenstück" und vergleicht dann **0 von 204 Dateien**. **Gegengemessen: 95/4 vor und nach dem Paket identisch** (`git stash`), der Befund liegt also nicht am Code. Backlog **Nr. 259** | Nach einem Lauf mit frischer Fixture bleiben Abweichungen stehen, die nicht in der Ausnahmeliste der Probe stehen |
| **N3-2** | **Der Android-Prüfstand** (`SenderTest`, `SendeantwortTest` — Abschnitt 5 des Konzepts verlangt ihn für AP3) | **Versucht, mit Befund und Zahl:** `./gradlew test` bricht ab mit „SDK location not found"; in diesem Container gibt es **kein** `/opt/android-sdk` und `ANDROID_HOME` ist leer (`ls -d /opt/android-sdk` → „No such file or directory"). Ein Lauf mit `--offline` scheitert davor am nicht zwischengespeicherten Plugin `com.android.application:8.13.2`. **Was stattdessen belegt ist:** AP3 fasst **keine** der fünf Geräte-Dateien an (`ingest.php`, `pair.php`, `auth_salt.php`, `jobs.php`, `gpx.php` stehen nicht in `git diff --name-only`), und der Gerätevertrag ist mittelbar über Ingestprobe **83/0** und Kopplungsprobe **76/0** gemessen | Ein Android-`SenderTest` schlägt fehl mit einem Fehlerschlüssel, den er nicht kennt |
| **N3-2a** | **Der Uhr-Prüfstand** (`tools/uhr-pruefstand/`) | Dasselbe: AP3 ändert keine Zeile unter `watch/` und keinen Geräte-Endpunkt | Die Uhr meldet beim Hochladen einen unbekannten Fehlerschlüssel |
| **N3-3** | **Die `schemaprobe`** | Sie **löscht** das genannte Schema mehrfach und verlangt den Namen ausdrücklich. Auf der Anlage, auf der auch die Kreisläufe laufen, wäre das der Demo-Bestand | Nicht von AP3 berührt: AP3 ändert kein Schema und keine Migration |
| **N3-4** | **Die `eingabe-probe`** | Sie ist ein Monkey-C-Projekt (Garmin), kein PHP-Werkzeug | Nicht von AP3 berührt |

## C1. Was maschinell geprüft wurde — mit Mittel **und** Zahl

### Gegen den Quelltext

| Mittel | Gemessen |
|---|---|
| `tools/zaehlung/zaehlen.php` | **38 Zeilen, 0 über der Decke.** Z05 **12 → 1** · Z06 **17 → 0** · Z07 **11 → 0** · Z08 **3 → 0** · Z09 **22 → 0** · **Z38 `error_log(` unverändert 77** (E-ZE-05) |
| `tools/zaehlung/zaehlen.php --selbstprobe` | **34 von 34**, darunter „Zeilentreue über 522 Sichten: 0 Abweichungen" |
| `php -l` über `server/` | **135 Dateien, 0 Fehler** (134 im Repositorium plus die lokale `config.php`) |
| `tools/wortliste/wortliste.py` | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** (99 Regeln, 99 gegriffen) |
| `tools/vollstaendigkeit/pruefen.py` | **398 Befunde** — unverändert gegenüber AP1 und AP2 |
| `tools/kettenaufrufe/pruefen.py` | **0 Befunde, 0 ungeprüft** |
| `tools/sitzungshaertung/pruefen.php` | **1 echter `session_start()`, 0 Befunde** |
| `tools/cspprobe/pruefen.php` | **0 Inline-Skripte ohne Nonce** |
| `tools/migrationsregister/pruefen.php` | **0 ungenutzte Ausnahmen** |
| JavaScript gegen die Fehlerschlüssel | Über alle **40** Skripte unter `server/assets/`: **0** Vergleiche auf `method`, `methode`, `payload`, `format` oder `leer`; der einzige Vergleich auf `error` gilt `maintenance`. Das ist die Voraussetzung, unter der F-ZE-5 entschieden wurde — sie gilt auch nach dem Paket |

### Gegen die laufende Anlage

| Mittel | Gemessen |
|---|---|
| **Eingangsprobe** (Einmalprobe, Befehl unten) | **46 Zellen, 46 erfüllt, 0 offen** |
| **Dieselbe gegen den Stand VOR AP3** (`git stash`) | **46 Zellen, 27 erfüllt, 19 offen** — die 19 sind genau die in F-ZE-5 entschiedene Änderung: **8** `payload` → `format`, **6** `payload` → `leer`, **2** `format` → `leer`, **3** `methode` → `method` |
| **Die zwei Reihenfolge-Zellen** in beiden Läufen | **grün vor und nach dem Paket**: POST ohne Token mit leerem Rumpf → `403 csrf` (nicht `400 leer`); GET ohne Token → `405 method` (nicht `403 csrf`). Das ist der Beleg dafür, dass die Aufteilung in `api_methode()` und `api_rumpf()` die Reihenfolge wirklich erhält (AP3-a) |
| **Flash-Probe** (Einmalprobe, Befehl unten) | **11 Zellen, 11 erfüllt** |
| Kreislauf `edbak` (`vergleich/kreislauf.py --art edbak --frisch`) | **328 771 Einzelvergleiche, 0 unerklärt, 21 erwartet** — Zahl für Zahl wie vor dem Paket |
| Kreislauf `csv` | **10 922 Einzelvergleiche, 0 unerklärt, 1 271 erwartet** — ebenso |
| `tools/ingestprobe/probe.php` | **83 Erwartungen, 0 nicht erfüllt** |
| `tools/kopplungsprobe/probe.php` | **76 Erwartungen, 0 nicht erfüllt, 0 übergangen** |
| `tools/komplettprobe/probe.php` | **64 Erwartungen, 0 nicht erfüllt** |
| `tools/spurprobe/probe.php` | **45 Erwartungen, 0 nicht erfüllt** |
| `tools/jobprobe/probe.php` | **35 Erwartungen, 0 nicht erfüllt** |
| `tools/ratenprobe/probe.php` | **50 Prüfungen, 0 Befunde** |
| `tools/gpxprobe/probe.php` | **95 Erwartungen, 4 nicht erfüllt** — **vor und nach dem Paket gleich**, Ursache in N3-1 |
| `tools/mailprobe/probe.php` | **41 Prüfungen, 1 Befund** — **vor und nach dem Paket gleich** (`git stash`); der Befund betrifft Pflichtwerte im Beispielsatz dreier Mailvorlagen |
| `node tools/klickprobe/probe.mjs` | **48 von 48 Wegen erfüllt, 0 verfehlt** — im **zweiten** Lauf. Der erste meldete 42/48 **und sagte selbst, warum**: „Der Demo-Reset lief um 20:36:04 UTC mitten in diesem Lauf. Verfehlte Wege sind verdächtig — bitte wiederholen." Mit `jobs_pause(3000)` davor ist der Lauf sauber |

**Die beiden Einmalproben sind kein Werkzeug im Repositorium.** Sie liegen im
Arbeitsverzeichnis der Sitzung und sind hier beschrieben, damit sie sich
wiederholen lassen:

- **Eingangsprobe:** legt per SQL ein Konto mit der Rolle `betreiberin` an
  (wie `tools/gpxprobe` eines mit `user` anlegt), meldet sich über
  `login.php` an, holt das Token aus `const CSRF` und ruft dann je Datei
  unter `server/api/` auf: einmal mit einer **nicht** erlaubten Methode, und
  bei den elf Dateien mit Rumpf zusätzlich mit leerem und mit
  Nicht-JSON-Rumpf. Dazu `csp_bericht.php` zweimal (muss **204** ohne Rumpf
  liefern) und die zwei Reihenfolge-Zellen ohne Token.
  **Die Rolle `betreiberin` ist nötig**, sonst antwortet
  `api/schluesselblatt_pruefen.php` mit `403 forbidden` aus
  `require_betreiberin()`, bevor die Methodenprüfung überhaupt läuft — mit
  der Rolle `admin` fehlt genau diese eine Zelle.
- **Flash-Probe:** dasselbe Konto (Rolle `admin` genügt), dann je Seite eine
  Handlung, die umleitet — `einstellungen.php` `koppeln_abbrechen`,
  `papierkorb.php` `restore_mission` auf einen Einsatz, dessen Diensttag
  ebenfalls im Papierkorb liegt, `nachbearbeitung.php` `notnull` —, danach
  zwei GET auf dieselbe Seite: beim ersten muss die Meldung dastehen, beim
  zweiten nicht mehr.
  **`nachbearbeitung.php` liefert auf einer fertig eingerichteten Anlage kein
  Formular aus** (`nb_moeglich()` ist falsch, `base_id` ist längst
  `NOT NULL`) und damit kein `csrf_field()`. Das Token hängt aber an der
  **Sitzung**, nicht an der Seite — es kommt deshalb von `einstellungen.php`.

## C2. Was im Browser geprüft wurde

| Weg | Ergebnis |
|---|---|
| **Bilderlauf** `node tools/screenshots/aufnehmen.mjs` | **496 Einzelbilder, 62 Kontaktbögen** · Überlauf **0** · Konsolenfehler **0** · Knöpfe falscher Höhe **0** (Zeiger, 44/36 px) · Karten im Seitengerüst **162 geprüft, 0 außerhalb von `main.inhalt`** — Zahl für Zahl wie nach AP2 |
| **Meldung nach der Umleitung**, drei Seiten | **11 von 11 Zellen**, siehe C1. `papierkorb.php` zusätzlich: die Meldung trägt die Klasse `meldung-fehler` |
| **Sechs der elf umgebauten Eingänge** unter echter Last | über die Kreisläufe: Export, Import, Backup zurückspielen, Einträge zurückspielen, Spuren sichern und zurückspielen, Tagesdaten — **0 unerklärte Abweichungen** in 339 693 Einzelvergleichen |

## C3. Prüfliste — was **die Auftraggeberin** noch tun muss

| # | Weg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **C-1** | Nach dem Ausrollen **ein Konto-Backup einspielen** (Einstellungen → Sicherung), eine Datei, die vor dem Update entstanden ist | Läuft durch wie bisher | Meldung `format` oder `leer`, wo vorher eine inhaltliche Meldung stand → dann greift eine Prüfung des Eingangs zu früh |
| **C-2** | **Einen Export** über einen Zeitraum ziehen, **CSV und JSON** | Beide Dateien wie bisher | Ein leerer Download oder eine JSON-Fehlermeldung im Browser |
| **C-3** | **Einstellungen → Geräte:** einen Kopplungscode eingeben und dann **abbrechen** | Die Meldung „Die Kopplung ist abgebrochen …" steht **einmal** da; nach `F5` ist sie fort | Sie bleibt nach dem Neuladen stehen → `flash_holen()` löscht nicht; oder sie kommt gar nicht → der Schlüssel `flash` wird nicht gelesen |
| **C-4** | **Papierkorb:** einen Einsatz zurückholen, dessen **Diensttag ebenfalls im Papierkorb liegt** | Roter Kasten „Der Diensttag dieses Einsatzes liegt ebenfalls im Papierkorb …", **einmal** | Der Kasten ist grün statt rot → der Ton wird nicht mitgeführt |
| **C-5** | **Einstellungen → Standorte:** einen Standort speichern und einen löschen | Je eine grüne Meldung nach der Umleitung, **einmal** | Zwei Meldungen gleichzeitig, oder die falsche → die Annahme aus AP3-f (Hinweis und Fehler schließen einander aus) trägt auf dieser Anlage nicht |
| **C-6** | **Ein Gerät koppeln** (echte Uhr oder Handy) | Unverändert | Die Uhr meldet einen Fehler, den sie nicht kennt → ein Geräte-Endpunkt wurde doch berührt (er sollte nicht) |

## C4. Grenzen — was sich mit diesem Paket NICHT beantworten lässt

- **Die Eingangsprobe misst den Eingang, nicht den Endpunkt.** Sie stellt je
  Datei drei Fragen an die ersten Zeilen. Dass der Endpunkt danach dasselbe
  tut wie vorher, belegen die Kreisläufe und die Proben — für die fünf
  Dateien, die in keinem von beiden vorkommen
  (`adminbackup_freigabe.php`, `kdf_upgrade.php`, `rueckfrage.php`,
  `schluessel_erneuern.php`, `schluesselblatt_pruefen.php`), belegt es
  **nichts** außer dem Lesen. Deshalb C-3 und C-6.
- **`api_rumpf()` hat keine Größengrenze, und das ist Absicht** (AP3-b). Wer
  eine will, führt sie ein — und das ist dann eine Verhaltensänderung mit
  eigener Entscheidung, kein Nachziehen.
- **Die 19 geänderten Zellen sind gemessen, die Folgenlosigkeit ist
  gefolgert.** Gemessen ist, dass kein JavaScript die Schlüssel vergleicht.
  Ein Aufrufer außerhalb dieses Repositoriums — ein Skript, ein Test, eine
  fremde Integration — ist damit nicht ausgeschlossen. Für die
  **Geräte**-Endpunkte ist er ausgeschlossen, weil sie nicht angefasst sind.
- **Der Flash trägt einen Ton, kein Markup.** `ui_meldung()` entscheidet
  weiterhin über das Aussehen; `flash_setzen('warn', …)` gibt es nicht, weil
  die drei Seiten nur `notice` und `error` kennen. Wer einen dritten Ton
  braucht, ergänzt `FLASH_TOENE`.
