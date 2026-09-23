# Prüfdokument — Zentralisierung: eine Stelle je Sache (Schritt 15)

**Stand:** 22.09.2026, nach **AP1** bis **AP7** (zuletzt Web 20.32.0) · **Zweig:** `claude/eager-euler-jlfi9i`,
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
| **A-7 `install.php`** | Die ganze Einrichtung ist über diesen Weg gelaufen — Schema, Admin-Anlage, `config.php`. *Nachtrag 23.09.2026:* **AP5 hat diesen Weg danach gebrochen** (c3b5bff, 22.09.2026) — bis Web 20.37.2 scheiterte jede Neueinrichtung an „Call to undefined function db_transaktion()“ (Backlog Nr. 288, behoben mit 20.37.3). Die Messung hier lag vor AP5 oder auf einer schon eingerichteten Anlage; aus dem Dokument ist nicht zu sagen, welches von beiden |
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

---

# AP4 — Datenzugriff klein (Web 20.29.0, 21.09.2026)

**AP4 hat `server/` angefasst — 23 Dateien, davon eine neu**
(`server/einsatz_lib.php`). Es ist das erste Paket, das an Stellen arbeitet,
die in einer **offenen Transaktion** stehen (`demo_anlegen()`,
`demo_entfernen()`), und das erste, bei dem eine Stelle wegen des
**Gerätevertrags** ausdrücklich stehenbleibt (`jobs.php`).

## D0. Was nicht geprüft werden konnte — und warum

| # | Was | Warum nicht | Woran man ein Scheitern erkennt |
|---|---|---|---|
| **N4-1** | **Der Android- und der Uhr-Prüfstand** | Unverändert wie N3-2: kein `/opt/android-sdk` in diesem Container, `./gradlew test` bricht mit „SDK location not found" ab. AP4 fasst von den fünf Geräte-Dateien **nur `ingest.php`** an, und dort genau eine Zeile: `ingest_hat_spalte()` → `db_hat_spalte()`, gleiche Frage, gleiche Antwort, kein Fehlerschlüssel berührt | Ein Gerät bekommt eine Antwort, die es nicht kennt |
| **N4-2** | **Der punktweise GPX-Vergleich** | Unverändert wie N3-1 (Backlog Nr. 259) | siehe dort |
| **N4-3** | **`tools/schemaprobe/`** | Sie **löscht** das genannte Schema mehrfach und verlangt den Namen ausdrücklich; auf der Anlage, auf der auch die Kreisläufe laufen, wäre das der Demo-Bestand. **Sie ist für AP4 die wichtigste nicht gefahrene Probe**, weil sie die einzige ist, die Migrationen gegen eine **andere** Verbindung als `db()` laufen lässt — genau der Fall, für den `db_hat_*()` ein `PDO` nimmt (AP4-a). Belegt ist stattdessen durch Lesen: Die drei Helfer nehmen den übergebenen `$pdo` und holen sich nie selbst eine Verbindung | Ein Migrationslauf im Prüfschema meldet „Spalte fehlt" für eine Spalte, die dort steht — oder umgekehrt |
| **N4-4** | **Ein Kontolöschvorgang von Hand** | `konto_loeschen()` ist über die Wiederherstellungs-Probe (111/0) nur mittelbar berührt. Der Weg über die Oberfläche steht in der Prüfliste als **D-3** | Nach dem Löschen bleiben Zeilen `mengen:<id>` in `app_state` stehen |

## D1. Was maschinell geprüft wurde — mit Mittel **und** Zahl

### Gegen den Quelltext

| Mittel | Gemessen |
|---|---|
| `tools/zaehlung/zaehlen.php` | **38 Zeilen, 0 über der Decke.** Z10 **27 → 2** · Z11 **7 → 0** · Z12 **12 → 2** · Z13 **4 → 0** · Z14 **9 → 5** · Z15 **57 → 54** · Z38 **77 → 76** (Aufschlüsselung in AP4-g) |
| Z12 mit verschärfter Regel gegen den Stand **vor** AP4 | **genau 12 Stellen**, die `DELETE`-Zeile nicht darunter — der berichtigte Startwert ist gemessen, nicht gerechnet (AP4-d) |
| `tools/zaehlung/zaehlen.php --selbstprobe` | **34 von 34** |
| `php -l` über `server/` | **136 Dateien, 0 Fehler** (135 im Repositorium plus die lokale `config.php`) |
| `tools/wortliste/wortliste.py` | **0 Treffer ausserhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** |
| `tools/vollstaendigkeit/pruefen.py` | **398** — nach einer Berichtigung: Der erste Lauf stand auf **399**, und der 399. Befund war ein **U+2026** in einem neuen Kommentar in `db.php`. Dieselbe Falle, dieselbe Datei, zum **dritten** Mal (Schritt 16, AP2, AP4). Ersetzt durch drei Punkte |
| `tools/kettenaufrufe/pruefen.py` | **0 Befunde** |
| `tools/sitzungshaertung/pruefen.php` | **0 Befunde** · `tools/cspprobe/` **0 Befunde** · `tools/migrationsregister/` **0 ungenutzte Ausnahmen** |
| **E-ZE-17-Beleg je Stelle** | **7 Stellen in 5 Dateien nachgelesen**, alle holen `$pdo` unmittelbar aus `db()`; `db()` hält die Verbindung statisch (`db.php` Z. 42–43). **0 Stellen brauchen einen `?PDO $pdo`-Parameter.** Tabelle in AP4-i |

### Gegen die laufende Anlage

| Mittel | Gemessen |
|---|---|
| Kreislauf `edbak` | **328 771 Einzelvergleiche, 0 unerklärt, 21 erwartet** — wie vor dem Paket |
| Kreislauf `csv` | **10 922 Einzelvergleiche, 0 unerklärt, 1 271 erwartet** — wie vor dem Paket |
| `tools/ingestprobe/probe.php` | **83 Erwartungen, 0 nicht erfüllt** — fährt `db_hat_spalte()` auf `rest_segments.created_at` |
| `tools/kopplungsprobe/probe.php` | **76, 0 nicht erfüllt, 0 übergangen** |
| `tools/komplettprobe/probe.php` | **64, 0 nicht erfüllt** |
| `tools/spurprobe/probe.php` | **45, 0 nicht erfüllt** |
| `tools/jobprobe/probe.php` | **35, 0 nicht erfüllt** — `jobs_token()` und `jobs_pause()` laufen jetzt über die Helfer |
| `tools/ratenprobe/probe.php` | **50 Prüfungen, 0 Befunde** |
| `tools/wiederherstellungs-probe/probe.php` | **111, 0 nicht erfüllt** — prüft `EDBAK_MARKE_MAX` an drei Stellen (AP4-h) |
| `tools/anteilprobe/probe.php` | **55 von 55** — `schluessel_marke_lesen()`/`-setzen()` |
| `tools/versandprobe/probe.php` | **135, 0 nicht erfüllt** — `sz_tabelle_da()` und `sz_dateien_tabelle_da()` über `db_hat_tabelle()` |
| `jobs_pause()` / `jobs_pause_bis()` von Hand | gesetzt und zurückgelesen: **`2026-09-21 22:04:55`** — der Rundweg durch `app_state_setzen()` und `app_state_lesen()` an einem echten Wert |
| Demo-Reset | lief während der Prüfläufe **von selbst** und vollständig durch: Danach stehen **21 Diensttage und 106 Einsätze** im Demo-Konto, `demo_letzter_reset` und `demo_user_id` sind über die neuen Helfer geschrieben. Das ist der Beleg für `demo_entfernen()` und `demo_anlegen()` **innerhalb ihrer Transaktion** |

## D2. Was im Browser geprüft wurde

| Weg | Ergebnis |
|---|---|
| **Klickprobe** `node tools/klickprobe/probe.mjs` | **48 von 48 Wegen erfüllt, 0 verfehlt** — im **dritten** Lauf. Die beiden davor meldeten 42/48, und beide Male war der **Demo-Reset** die Ursache, nicht der Code (siehe unten) |
| **Bilderlauf** `node tools/screenshots/aufnehmen.mjs` | **496 Einzelbilder, 62 Kontaktbögen** · Überlauf **0** · Konsolenfehler **0** · Knöpfe falscher Höhe **0** · Karten im Seitengerüst **162 geprüft, 0 außerhalb von `main.inhalt`** — Zahl für Zahl wie nach AP2 und AP3 |
| **Helferprobe von Hand** gegen die laufende Datenbank | **13 Zellen, 13 erfüllt** — Einzelheiten im Konzept-Prüfprotokoll |

**Der Demo-Reset hat zwei Läufe der Klickprobe entwertet, und `jobs_pause()`
half nicht.** Das ist ein eigener Befund: Der Reset hängt **nicht** an der
Jobschlange, sondern an `auth_guard.php` Z. 482 —
`if (demo_ist_demo($userId)) { demo_reset_wenn_faellig(); }`. Er läuft bei
**jeder Anmeldung des Demo-Kontos**, sobald `DEMO_RESET_SEKUNDEN` (1800) um
sind; eine angehaltene Jobschlange ändert daran nichts. Das richtige Mittel
steht in `tools/klickprobe/LIESMICH.md`:

```sql
UPDATE app_state SET v = UNIX_TIMESTAMP() WHERE k = 'demo_letzter_reset';
```

Damit lief die Probe durch. Nachgetragen in Backlog **Nr. 259**.

## D3. Prüfliste — was **die Auftraggeberin** noch tun muss

| # | Weg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **D-1** | **Betrieb → Demo-Konto**, „Jetzt zurücksetzen" | Der Bericht nennt Diensttage und Einsätze wie bisher | Leerer Bestand oder eine Fehlermeldung → `demo_anlegen()` schreibt `app_state` außerhalb seiner Transaktion |
| **D-2** | **Betrieb → Jobs**, Pause setzen und wieder aufheben | Die Zeile „angehalten bis …" erscheint und verschwindet | Die Pause lässt sich nicht aufheben → `app_state_loeschen()` greift nicht |
| **D-3** | **Ein Konto löschen** (Verwaltung → Konto → löschen) | Läuft durch wie bisher | Danach stehen noch Zeilen `mengen:<id>` in `app_state` |
| **D-4** | **Einen Einsatz aus dem Papierkorb zurückholen** und einen **endgültig löschen** | Wie bisher; der Einsatz an einem gelöschten Diensttag wird weiterhin abgelehnt | „Einsatz nicht gefunden", wo er dasteht → `papierkorb`-Option falsch herum |
| **D-5** | **Einen Einsatz von Hand anlegen**, einen **CSV-Import** fahren, eine **GPX-Datei einlesen**, einen Einsatz **schneiden** | Alle vier hängen am selben Gerät „Manuelle Einträge"; in der Geräteliste taucht es **nicht** auf | Ein zweites Gerät „Manuelle Einträge" oder ein Eintrag in der Geräteliste |
| **D-6** | **Verwaltung → Konten**, eine Rolle auf „BetreiberIn" setzen und zurück | Wie bisher, einschließlich der Sperre „das letzte Konto mit der Rolle" | Die Sperre greift nicht mehr oder greift zu früh |
| **D-7** | Nach dem Ausrollen **`update.php`** aufrufen — **nicht nötig** (keine Migration), aber die Seite **Betrieb → Updates** einmal ansehen | „Keine offenen Migrationen" wie bisher | Eine Migration meldet sich, obwohl AP4 keine anlegt → `_hat_*` reichen falsch durch |

## D4. Grenzen — was sich mit diesem Paket NICHT beantworten lässt

- **Die Ausnahme `jobs.php` ist gelesen, nicht gemessen** (AP4-c). Belegt ist,
  dass der `catch` dort mit `500 datenbank` antwortet und `app_state_lesen()`
  stattdessen `null` liefern würde. Ein Gegentest hieße, die Datenbank
  mitten im Lauf unerreichbar zu machen; das leistet dieser Prüfstand nicht.
- **`db_hat_*()` gegen eine fremde Verbindung** ist der Fall, für den die
  Signatur gebaut ist — und genau der ist nicht gefahren (N4-3).
- **Die Import-Ausnahme (AP4-b) ist mit einer Zahl aus dem Code begründet,
  nicht mit einer Messung.** Dass 3 000 zusätzliche `prepare()` messbar
  kosten, ist plausibel und unbelegt; AP5 bringt für `ingest.php` einen
  Messstand mit, an dem sich so etwas künftig entscheiden lässt.
- **Die `NULL`-Kleinigkeit** (Problem 3 im Protokoll) ist durch Lesen aller
  Schreibwege ausgeschlossen, nicht durch eine Messung an Daten.

---

# AP5 — Transaktion und Kindtabellen (Web 20.30.0, 22.09.2026)

**Das heikelste Paket bisher.** Es fasst 24 Transaktionsrahmen und 30
Schreibanweisungen auf Kindtabellen an — darunter den Geräte-Eingang, das
Zurückspielen von Backups, den CSV-Import und alle Löschwege. Ein falsch
gesetztes `commit()` verliert Daten still.

## E0. Was nicht geprüft werden konnte — und warum

| # | Was | Warum nicht | Woran man ein Scheitern erkennt |
|---|---|---|---|
| **N5-1** | **Ein echter Deadlock** | `db_transaktion()` rollt zurück und wirft weiter; ob sich das bei einem MySQL-Deadlock (Fehler 1213) genauso verhält wie vorher, ließe sich nur mit zwei gleichzeitigen Schreibern nachstellen. `ingest.php` — die Stelle, an der Deadlocks auftreten (Nr. 210) — **ist nicht angefasst** | Ein Upload der Uhr meldet einen Fehler, den der JSON-Vertrag nicht kennt |
| **N5-2** | **Der Fall „DDL bestätigt still"** | Der zweite Grund für die `inTransaction()`-Nachfrage in `db_transaktion()` ist gelesen, nicht gemessen: Kein Rumpf der 24 umgestellten Rahmen setzt ein `ALTER`/`CREATE` ab. Der **erste** Grund ist gemessen (28 von 42 `rollBack()` ohne Wache) | Im Protokoll steht „There is no active transaction" statt des eigentlichen Grundes |
| **N5-3** | **Der Android- und der Uhr-Prüfstand** | Unverändert wie N3-2/N4-1: kein `/opt/android-sdk`. `ingest.php` ist angefasst — **zwei** Stellen, beide Kindtabellen-Schreibwege, kein Fehlerschlüssel und keine Antwortform berührt; die Ingestprobe (83/0) fährt sie | Ein Android-`SenderTest` schlägt fehl |
| **N5-4** | **Der punktweise GPX-Vergleich** | Unverändert Nr. 259 (Demo-Reset) | siehe dort |
| **N5-5** | **`tools/schemaprobe/`** | Unverändert N4-3. Für AP5 ist sie doppelt relevant: `einsatz_anweisung()` hält Anweisungen **je Verbindung**, und die Schemaprobe ist die einzige Stelle mit mehreren Verbindungen nebeneinander. Belegt ist stattdessen durch Lesen und durch die gehaltene PDO-Referenz, die eine Wiederverwendung der `spl_object_id` ausschließt | Eine Migration im Prüfschema schreibt in die falsche Datenbank |

## E1. Was maschinell geprüft wurde — mit Mittel **und** Zahl

### Gegen den Quelltext

| Mittel | Gemessen |
|---|---|
| `tools/zaehlung/zaehlen.php` | **38 Zeilen, 0 über der Decke.** Z16 **33 → 9** · Z17 **30 → 0** |
| Bauform-Auszählung mit `token_get_all()` | **19 / 12 / 2** (weitergeben · schlucken · kein `try`); **42 `rollBack()`, 14 mit Wache, 28 ohne** |
| Gegenprobe „verirrtes `commit`/`rollBack` in einer Closure" | über **alle** `db_transaktion()`-Aufrufe: **0** (nach einem Fund, siehe Problem 1) |
| `tools/zaehlung/zaehlen.php --selbstprobe` | **34 von 34** |
| `php -l` über `server/` | **136 Dateien, 0 Fehler** |
| `tools/wortliste/wortliste.py` | **0 / 0 / 0** |
| `tools/vollstaendigkeit/pruefen.py` | **398** — unverändert |
| `tools/kettenaufrufe/pruefen.py` | **0 Befunde** |
| `tools/sitzungshaertung/pruefen.php` | **0 Befunde** |

### Gegen die laufende Anlage

| Mittel | Gemessen |
|---|---|
| **Probe für `db_transaktion()`** (Einmalprobe) | **10 Zellen, 10 erfüllt** — darunter die drei Verschachtelungsfälle: innerer Fehler wirft weiter, **äußere** Transaktion steht noch, äußeres `rollBack()` nimmt die innere Arbeit mit |
| **Messstand `api/import_commit.php`** (csv-Kreislauf, Wanduhr) | **davor 41,71 s und 41,47 s · danach 41,78 s und 41,31 s** — innerhalb der Streuung |
| Kreislauf `edbak` | **328 771 Einzelvergleiche, 0 unerklärt, 21 erwartet** |
| Kreislauf `csv` | **10 922 Einzelvergleiche, 0 unerklärt, 1 271 erwartet** |
| `tools/ingestprobe/probe.php` | **83 / 0** — der umgebaute Uhr-Eingang |
| `tools/kopplungsprobe/probe.php` | **76 / 0, 0 übergangen** |
| `tools/komplettprobe/probe.php` | **64 / 0** |
| `tools/spurprobe/probe.php` | **45 / 0** |
| `tools/jobprobe/probe.php` | **35 / 0** |
| `tools/wiederherstellungs-probe/probe.php` | **111 / 0** |
| `tools/ratenprobe/probe.php` | **50 Prüfungen, 0 Befunde** |
| `tools/gpxprobe/probe.php` | **95 / 4** — unverändert der Befund aus Nr. 259 |
| Demo-Reset von Hand | **106 Einsätze, 21 Diensttage** wie zuvor — `demo_anlegen()` und `demo_entfernen()` laufen jetzt über `db_transaktion()` |

## E2. Was im Browser geprüft wurde

| Weg | Ergebnis |
|---|---|
| **Klickprobe**, Transaktionshälfte | **48 von 48 Wegen erfüllt, 0 verfehlt** |
| **Klickprobe**, nach dem Kindtabellen-Umbau | **48 von 48 Wegen erfüllt, 0 verfehlt** — je Lauf mit vorgestellter `demo_letzter_reset`-Marke (Nr. 259) |

## E3. Prüfliste — was **die Auftraggeberin** noch tun muss

| # | Weg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **E-1** | **Einen Einsatz im Formular speichern** — mit Besatzung, Phasenzeiten, einer Reanimation und zwei weiteren Rettungsmitteln | Alles steht nach dem Speichern da | Eine der vier Gruppen fehlt oder ist doppelt |
| **E-2** | Denselben Einsatz **erneut speichern**, dabei eine Phase löschen und eine Besatzungszeile leeren | Der Satz wird **ersetzt**, nicht gemischt | Die gelöschte Phase steht noch da |
| **E-3** | **Einen CSV-Export zurückimportieren**, der Phasen und Reanimation enthält | Wie bisher; der Bericht nennt dieselben Zahlen | Weniger übernommene Einsätze, oder der Import läuft spürbar länger |
| **E-4** | **Ein Konto-Backup einspielen** | Wie bisher | Fehlende Phasen, Besatzung oder Rettungsmittel im wiederhergestellten Bestand |
| **E-5** | **Schneiden und Rückgängig** an einem Ruhesegment mit Spur | Der Einsatz entsteht mit Phasen und verschwindet restlos wieder | Ein Einsatz ohne Phasen, oder Phasenzeilen ohne Einsatz |
| **E-6** | **Einen Diensttag umdatieren** und einen **Einsatz verschieben** | Wie bisher | „Verschieben fehlgeschlagen", wo es klappen müsste |
| **E-7** | **Ein Passwort wechseln** (Einstellungen → Profil) | Wie bisher; andere Sitzungen enden | Der Wechsel meldet Erfolg, das alte Passwort gilt aber noch — dann wurde die Transaktion nicht bestätigt |
| **E-8** | **Einen Standort löschen**, an dem Rettungsmittel hängen | Wie bisher, mit derselben Zahl in der Rückfrage | Der Standort bleibt stehen, oder die Vorbelegungen bleiben liegen |
| **E-9** | **Ein Gerät koppeln und einen Upload fahren** (echte Uhr) | Unverändert | Die Uhr meldet einen unbekannten Fehler |

## E4. Grenzen — was sich mit diesem Paket NICHT beantworten lässt

- **Die neun Ausnahmen sind gelesen, nicht gemessen.** Belegt ist ihre
  Bauform (Tokenizer) und ihre Größe (Zeilen und Variablen). Dass eine
  Umstellung dort schadete, ist begründet und nicht vorgeführt.
- **`einsatz_anweisung()` gegen zwei gleichzeitige Verbindungen** ist der
  Fall, für den die gehaltene PDO-Referenz gebaut ist — und genau der ist
  nicht gefahren (N5-5).
- **Der Messstand misst den ganzen csv-Kreislauf, nicht `import_commit.php`
  allein.** 41,7 s enthalten Browser, Export, Vergleich. Eine Verschlechterung
  von einigen hundert Millisekunden im Import verschwände darin. Was die
  Messung ausschließt, ist die Größenordnung, um die es ging: 21 000
  zusätzliche Roundtrips wären Sekunden, nicht Millisekunden.
- **Der behobene Fehler in `einsatz_form.php` ist eine Verhaltensänderung**
  (Problem 3 im Protokoll). Sie tritt nur ein, wenn ohnehin schon etwas
  fehlgeschlagen ist — aber sie ist eine.

---

# AP6 — Spaltenregister `missions` (Web 20.31.0, 22.09.2026)

**Das Paket mit dem schärfsten Beleg und der größten stillen Gefahr.** Es
erzeugt neun SQL-Anweisungen, die bisher von Hand dastanden — darunter den
Export, das Zurückspielen eines Backups und beide Import-Anweisungen. Eine
Spalte, die dabei an die falsche Stelle rutscht, fällt nicht auf: Die Zeile
wird geschrieben, sie trägt nur die falschen Werte.

Deshalb ist der Beleg hier ein **Byte-Vergleich** und nicht ein Augenschein.

## F0. Was nicht geprüft werden konnte — und warum

**Diese Liste steht am Anfang und nicht in einer Fußnote.**

| # | Was | Warum nicht | Woran man ein Scheitern erkennt |
|---|---|---|---|
| **N6-1** | **Ein Backup, das eine Spalte NICHT führt** (Nutzlast ≤ 8) | Der Bestand hat keines. Der Zweig ist gelesen: `$extraCols` hängt nur an, was `array_key_exists()` in der Datei findet; die 15 Grundspalten kommen aus dem Register und sind immer da | Ein altes Backup spielt ein, aber `geraet_art` oder `geraet_modell` stehen auf einem Wert statt auf `NULL` |
| **N6-2** | **Eine Exportdatei, die jemand seit Monaten aufhebt** | Die Reihenfolge der Spalten im CSV ist eingefroren, und der Kreislauf vergleicht gegen die eingecheckte Referenz vom 15.09.2026 — nicht gegen eine Datei aus dem Frühjahr | Eine Tabellenkalkulation, die auf Spaltennummern zeigt, zeigt auf die falsche Spalte |
| **N6-3** | **Der Android- und der Uhr-Prüfstand** | Unverändert wie N3-2/N4-1/N5-3: kein `/opt/android-sdk`, keine `CIQ_GERAETE_URL` in dieser Umgebung. `ingest.php` **ist angefasst** — eine Stelle, die Spaltenliste des Einsatz-INSERT; kein Fehlerschlüssel, keine Antwortform. Die Ingestprobe (83/0) fährt sie | Ein Android-`SenderTest` schlägt fehl |
| **N6-4** | **Der punktweise GPX-Vergleich** | Unverändert Nr. 259 (Demo-Reset dieser Anlage). **95/4 vor und nach dem Paket** | siehe dort |
| **N6-5** | **`tools/schemaprobe/`** | Unverändert N4-3/N5-5 | Eine Migration im Prüfschema schreibt in die falsche Datenbank |
| **N6-6** | **Die Wegprobe gegen das Demo-Konto** | Sie **schreibt** (schneidet einen Einsatz, überschreibt einen zweiten zweimal). Gefahren ist sie gegen ein frisches Umlaufkonto des Kreislaufs; der Dateikopf und eine Sperre im Werkzeug halten sie vom Demo-Konto fern | Der Demo-Bestand trägt plötzlich 107 statt 106 Einsätze, und ein Einsatz heißt „Klinik Probe B" |

## F1. Was maschinell geprüft wurde — mit Mittel **und** Zahl

### Der Kernbeleg: der Byte-Vergleich

`git checkout 7a55192 -- server/` legt den Stand **vor** AP6 (Web 20.30.0) auf
dieselbe laufende Anlage mit demselben Bestand; abgezogen werden die
Serverantworten, die AP6 anfasst.

| Abzug | Bytes | gleich? |
|---|---|---|
| `api/export_data.php`, `action: meta`, **mit** personenbezogenen Angaben | 182 474 | **ja** |
| dieselbe Anfrage **ohne** personenbezogene Angaben | 146 895 | **ja** |
| `api/suchindex.php` | 105 442 | **ja** |
| `api/range.php` 2026-01 / 2026-05 / 2026-09 | 3 942 / 4 474 / 3 948 | **ja** |
| **zusammen** | **447 291** | **SHA-256 `fefb84e2…e40ced86` — identisch** |

**Warum gegen `7a55192` und nicht gegen den letzten Commit:** AP6 ist in zwei
Schritten entstanden; ein Vergleich gegen den Zwischenstand hätte nur die
halbe Änderung gesehen.

### Gegen den Quelltext

| Mittel | Gemessen |
|---|---|
| `tools/spaltenregister/pruefen.php` | Schema **41**, Register **41**, im Schema nicht im Register **0**, im Register nicht im Schema **0**, Spalten ohne Zweck **und** ohne Grund **0**; neun Zwecke mit **32 / 35 / 15 / 31 / 28 / 12 / 11 / 21 / 12** Spalten |
| dieselbe, Vollständigkeitsprobe | `export_data.php` **38 Schlüssel, 32 aus dem Register** · `import_commit.php` **22 / 22** · `suchindex.php` **30 / 18** — je **0 fehlend, 0 überzählig, 0 tote Ausnahmen**; Werteliste des Imports passt auf **beide** Anweisungen (**22 = 22**) |
| `tools/spaltenregister/pruefen.php --selbstprobe` | **16 von 16**, darunter **vier Gegenproben**, die keinen Befund ergeben dürfen |
| Die neun erzeugten Anweisungen gegen die alten | `ingest_neu`, `import_neu`: **Zeichen für Zeichen identisch**. `schnitt_neu`, `import_aendern`, `export` (mit und ohne Flag): **identisch nach Umbruchnormierung**. Platzhalter **29 = 29** (INSERT), **28 = 28** (UPDATE) |
| `tools/zaehlung/zaehlen.php` | **38 Zeilen, 0 über der Decke.** Z18 **12 → 3** |
| `tools/zaehlung/zaehlen.php --selbstprobe` | **34 von 34** |
| `php -l` über `server/` | **136 Dateien, 0 Fehler** |
| `tools/wortliste/wortliste.py` | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** (99 Regeln, 99 gegriffen) |
| `tools/vollstaendigkeit/pruefen.py` | **398** — unverändert (nach der Berichtigung, Problem 4) |
| `python3 tools/screenshots/kontrast.py` | **22 Paare gerechnet, 0 verfehlt** |
| `tools/kettenaufrufe/pruefen.py` | **45 Aufrufe, 0 Befunde, 0 ungeprüft** |
| `git diff 7a55192 -- docs/Export-Format.md docs/Backup-Format.md docs/JSON-Vertrag.md` | **0 geänderte Zeilen** |

### Gegen die laufende Anlage

| Mittel | Gemessen |
|---|---|
| Kreislauf `edbak` (zweimal) | **328 771 Einzelvergleiche, 0 unerklärt, 21 erwartet** — fährt die erzeugte Wiederherstellung (**106 Einsätze übernommen**) |
| Kreislauf **`edbak-alt`** — das **alte** Backup mit dem Schlüssel `manual` | **287 852 Einzelvergleiche, 0 unerklärt, 795 erwartet** |
| Kreislauf `csv` | **10 922 Einzelvergleiche, 0 unerklärt, 1 271 erwartet** — fährt den erzeugten INSERT (**101 angelegt**) und den erzeugten Export (**101 exportiert**) |
| `tools/spaltenregister/wegprobe.py` (frisches Umlaufkonto) | **34 Erwartungen, 0 nicht erfüllt** — 12 für den Schnitt, 22 für den UPDATE-Zweig des Imports |
| Export-Schranke, beide Fassungen | mit Flag **38 Schlüssel**, `site_ele_m` 85 / `bw_info` 10 / `other_ema` 6 / `pat_blob` 96 belegt · ohne Flag **38 Schlüssel**, alle vier **0**; außerhalb der Schranke unverändert (`transport_dest` 77, `manual` 101, `source` 101, `geraet_art` 90, `day` 101) |
| `tools/ingestprobe/probe.php` | **83 / 0** — der erzeugte `ingest_neu`-INSERT |
| `tools/kopplungsprobe/probe.php` | **76 / 0, 0 übergangen** |
| `tools/komplettprobe/probe.php` | **64 / 0** |
| `tools/spurprobe/probe.php` | **45 / 0** |
| `tools/jobprobe/probe.php` | **35 / 0** |
| `tools/wiederherstellungs-probe/probe.php` | **111 / 0** |
| `tools/ratenprobe/probe.php` | **50 Prüfungen, 0 Befunde** |
| `tools/gpxprobe/probe.php` | **95 / 4** — unverändert der Befund aus Nr. 259 |

## F2. Was im Browser geprüft wurde

| Weg | Ergebnis |
|---|---|
| **Klickprobe** (`node tools/klickprobe/probe.mjs`) | **48 von 48 Wegen erfüllt, 0 verfehlt** |
| **Bilderlauf** (`node tools/screenshots/aufnehmen.mjs`) | **496 Einzelbilder, 62 Kontaktbögen** · Überlauf **0** · Konsolenfehler **0** · Knöpfe falscher Höhe **0** (Zeiger, 44/36 px) · Karten im Seitengerüst **162 geprüft, 0 außerhalb von `main.inhalt`** |

**Was diese beiden Zahlen benennen** (CLAUDE.md 6): Der Bilderlauf misst 62
Seiten in acht Breiten — er sieht den Export, die Suche und die
Zeitraumansicht **als Seite**, nicht ihre Daten. Dass die Daten stimmen, sagt
der Byte-Vergleich, nicht das Bild.

## F3. Prüfliste — was **die Auftraggeberin** noch tun muss

| # | Weg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **F-1** | **CSV-Export mit personenbezogenen Angaben** herunterladen und die Kopfzeile von `einsaetze.csv` mit einer **älteren** Exportdatei vergleichen | Gleiche Spalten in gleicher Reihenfolge | Eine Spalte ist gewandert oder fehlt — dann zeigt jede Tabellenkalkulation, die auf Spaltennummern rechnet, auf die falsche Spalte (N6-2) |
| **F-2** | **CSV-Export OHNE personenbezogene Angaben** | Die Spalten `site_ele_m`, `bw_info`, `other_ema` und `pat_blob` sind **da, aber leer**; alles andere gefüllt | Eine der vier trägt Werte (Schranke undicht) oder eine fünfte ist leer (Schranke zu weit) |
| **F-3** | **Ein Konto-Backup schreiben und in ein leeres Konto einspielen** | Einsatzzahl, Papierkorb und Gerätemomentaufnahme wie im Ursprung | Ein Einsatz trägt die Geräteart im Löschdatum oder umgekehrt — das wäre der Fall, den AP6-d ausschließt |
| **F-4** | **Ein ALTES Backup einspielen** (eines aus dem Frühjahr, mit dem Schlüssel `manual`) | Läuft durch; „von Hand angelegt" bleibt gesetzt | Alle Einsätze kommen als „von der Uhr" zurück — dann sitzt `uhr_gesperrt`/`manual` falsch |
| **F-5** | **Einen CSV-Export zurückimportieren mit „überschreiben"** auf einen Einsatz, bei dem `bw_info` und `other_ema` im Bestand gefüllt sind, in der Datei aber leer | Beide Angaben **bleiben stehen**; `bw_unit` und `transport_dest` werden dagegen überschrieben | Eine der beiden Angaben ist fort — dann sitzt die Export-Schranke (`COALESCE`) eine Spalte daneben |
| **F-6** | **Aus einem Ruhesegment einen Einsatz schneiden** | Der Einsatz trägt „Schnitt" als Herkunft, gilt als abgeschlossen und als von Hand angelegt; Einsatzort, Alter und Diagnose sind leer | Der Einsatz steht als „von der Uhr" da oder ist nicht abgeschlossen |
| **F-7** | **Die Suche öffnen** und nach Zielklinik, Bergwacht-Bereitschaft und Schockraum filtern | Wie bisher, gleiche Trefferzahlen | Ein Filter findet nichts mehr — dann fehlt seine Spalte im Suchindex |
| **F-8** | **Die Zeitraumansicht** für einen Monat mit Windeneinsätzen öffnen | Karte, Statistik und Tabelle wie bisher | Windenzahl oder Höhe fehlen |
| **F-9** | **Ein Gerät koppeln und einen Upload fahren** (echte Uhr) | Unverändert | Die Uhr meldet einen unbekannten Fehler (N6-3) |

## F4. Grenzen — was sich mit diesem Paket NICHT beantworten lässt

- **Der Byte-Vergleich deckt drei Endpunkte ab, nicht neun Zwecke.** Export,
  Suchindex und Zeitraum liefern eine Serverantwort, die sich abziehen lässt.
  `backup_restore`, `import_neu`, `import_aendern`, `ingest_neu` und
  `schnitt_neu` **schreiben** — für sie ist der Beleg der Kreislauf (Feld für
  Feld gegen eine eingecheckte Referenz) und die Wegprobe, nicht eine
  Prüfsumme.
- **Die drei Abbildungen sind auf Vollständigkeit geprüft, nicht auf
  Richtigkeit.** Die Probe sagt, dass jeder Schlüssel da ist; ob
  `winch_cycles` auch wirklich `winch_cycles` liest und nicht
  `winch_cycles_pat`, sagt sie nicht. Das sagen die Kreisläufe.
- **Z18 = 3 belegt für `backup_lib.php` nicht, was es zu belegen scheint**
  (Problem 3 im Konzept). Die Stelle nennt weiterhin 15 Spalten — als
  Schlüssel einer Wertekarte, nicht als zweite Liste. Dass es nur noch **eine**
  Liste gibt, sagt der Quelltext, nicht die Zahl.
- **Eine Ausnahme in der Vollständigkeitsprobe ist eine Behauptung im Feld.**
  Die Probe prüft, dass sie **greift** — nicht, dass ihre Begründung stimmt.
  Wer eine Spalte mit einer erfundenen Begründung als `abgeleitet` einträgt,
  kommt damit durch.
- **N6-1 bleibt offen:** Ein Backup mit Nutzlast ≤ 8, dem Spalten fehlen, ist
  gelesen und nicht gefahren.

---

# AP7 — Zeit und Zahl in PHP (Web 20.32.0, 22.09.2026)

**Das Paket mit der größten Zahl an Fundstellen und der leisesten Gefahr.**
205 Ausdrücke in 38 Dateien wurden ersetzt, und keiner davon darf einen
Buchstaben ändern. Ein falsch gerundeter Prozentwert, eine Nachkommastelle
mehr, ein Komma statt eines Punktes — nichts davon wirft einen Fehler. Es
steht einfach anders da.

Deshalb ist der Beleg hier **ein Textvergleich und eine Rechnung je Funktion**,
nicht ein Augenschein.

## G0. Was nicht geprüft werden konnte — und warum

**Diese Liste steht am Anfang und nicht in einer Fußnote.**

| # | Was | Warum nicht | Woran man ein Scheitern erkennt |
|---|---|---|---|
| **N7-1** | **Der Android- und der Uhr-Prüfstand** | Unverändert wie N3-2/N4-1/N5-3/N6-3: kein `/opt/android-sdk`, keine `CIQ_GERAETE_URL`. `ingest.php` **ist angefasst** — zwei Zeilen, der Fülltext der 507-Antwort. Dafür gibt es einen eigenen Beleg (100 009 Fälle, 0 Abweichungen, G1) und die Ingestprobe (83/0) | Ein Gerät meldet beim vollen Konto einen anders geschriebenen Grund |
| **N7-2** | **Das volle Konto im Betrieb** | Die 507-Antwort entsteht nur, wenn ein Konto seine Grenze erreicht. Auf dieser Anlage ist kein Konto voll, und eines künstlich vollzuschreiben hieße, den Demo-Bestand zu zerstören. Belegt ist der **Text** (rechnerisch), nicht der **Weg** | Ein Gerät bekommt beim vollen Konto eine 500 statt einer 507 |
| **N7-3** | **Die drei Formularwerte auf Betrieb → Server** | Speichergrenze, Webspace und DB-Kontingent stehen als Formularwerte mit **Punkt** als Dezimaltrenner da und werden vom selben POST-Zweig zurückgelesen. Sie sind **nicht angefasst** (AP7-c) — es gibt also nichts zu prüfen, was sich geändert hätte. Dass sie weiter abzuschicken sind, ist im Browser **nicht** durchgefahren worden | „Bitte eine Zahl eingeben" beim Speichern, obwohl im Feld eine Zahl steht |
| **N7-4** | **Der Wartungsmodus** | `wartung_lib.php` ist **nicht angefasst** (AP7-e). Ihre drei Stellen bleiben, weil die Datei zusagt, nichts zu laden | Die Wartungsseite zeigt einen anderen Zeitpunkt als vorher |
| **N7-5** | **Der punktweise GPX-Vergleich** | Unverändert Nr. 259 (Demo-Reset). **95/4 vor und nach dem Paket** | siehe dort |
| **N7-6** | **`tools/schemaprobe/`** | Unverändert N4-3/N5-5/N6-5 | Eine Migration im Prüfschema schreibt in die falsche Datenbank |
| **N7-7** | ~~**Ein Backup unter 1 MiB herunterladen**~~ — **inzwischen gefahren, und die Lücke war keine theoretische** | Stand bis 20.32.0: belegt war, dass PHP und JavaScript dieselbe Regel rechnen (2 014 Werte, 0 Abweichungen), **nicht**, wie die Zeile im Browser erscheint. Der edbak-Kreislauf hat sie dann geschrieben: „… in 3 Teilen — **263 KB MB**." Das feste „ MB" hinter der Variablen war aus der alten Rechnung stehengeblieben. Behoben in **20.32.1**, nachgemessen im selben Lauf: „… in 3 Teilen — **263 KB**." | erledigt — der Prüfpunkt bleibt als **G-6** bestehen, weil der Kreislauf nur die eine Größenstufe geschrieben hat |

## G1. Was maschinell geprüft wurde — mit Mittel **und** Zahl

### Der Kernbeleg: jede Funktion gegen ihre Vorgängerin

Die alten Rümpfe wurden wortgetreu daneben gestellt und beide über einen
Wertebereich gerechnet, der die Stufengrenzen einschließt.

| Funktion | gegen | gemessen |
|---|---|---|
| `groesse_text` | `edbak_groesse_text()` | **3 017 Byte-Werte, 0 Abweichungen** |
| `groesse_kurz_text` | `plattform_groesse()` | **3 017 Werte, 0** |
| `groesse_text` | `apk_groesse()` | 4 Abweichungen, **alle ≥ 1 GiB** — für eine APK unerreichbar; unterhalb 1 GiB **0** |
| `zahl_text` | `stat_zahl()` | **28 Fälle, 0** |
| `prozent_text` | `stat_anteil()` | **6 030 Fälle, 0** |
| `prozent_wert('kauf')` | `round(100·a / max(1,b))` | **200 004 Paare einschließlich Nullfall, 0** |
| `zeit_relativ` | `status_alter()` | **10 811 Zeitpunkte (0 bis 400 000 s), 0** |
| `iso_utc` / `iso_utc_lesen` | `gmdate(…)` / `strtotime(str_replace(…))` | **5 000 Zeitstempel hin und zurück, 0** |
| `groesse_paar_text` | der 507-Antworttext des Gerätevertrags | **100 009 Fälle, 0** |
| `EdFormat.groesse` (JS) | `groesse_text` (PHP) | **2 014 Werte, 0** |

**Dazu die unabhängigen Rechnungen der Gegenleser.** Einer allein fuhr
**4 420 679 Vergleiche** (jede Minute des Jahres 2026, je einmal als ISO-Marke
und einmal als MySQL-`DATETIME`, durch vier Umstellungsarten) und führte
zusätzlich eine **In-situ-Gegenprobe**: dieselbe Funktion in alter und neuer
Fassung gegen **dieselbe echte Datenbank**, je 367 Zeilen / 15 858 Bytes JSON,
`diff` = 0, `stderr` beidseitig 0 Bytes.

### Gegen den Quelltext

| Mittel | Gemessen |
|---|---|
| `tools/zaehlung/zaehlen.php` | **38 Zeilen, 0 über der Decke.** Z19 **42 → 0** · Z20 **2 → 1** · Z21 **27 → 0** · Z22 **18 → 5** · Z23 **10 → 2** · Z24 **20 → 2** · Z25 **9 → 2** · Z26 **67 → 3** · Z27 **4 → 0** |
| `tools/zaehlung/zaehlen.php --selbstprobe` | **34 von 34** |
| Umfang der Änderung | **205 Stellen umgestellt, 15 namentlich stehengelassen, 38 Dateien, 36 `require`-Zeilen ergänzt, 2 entfernt** |
| `php -l` über `server/` | **137 Dateien, 0 Fehler** |
| `tools/wortliste/wortliste.py` | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** |
| `tools/vollstaendigkeit/pruefen.py` | **398** — unverändert (nach der Berichtigung, Problem 7 im Protokoll) |
| `python3 tools/screenshots/kontrast.py` | **22 Paare, 0 verfehlt** |
| `tools/kettenaufrufe/pruefen.py` | **45 Aufrufe, 0 Befunde, 0 ungeprüft** |
| `tools/spaltenregister/pruefen.php` | Selbstprobe **16/16**, Lauf **0 Befunde** |

### Gegen die laufende Anlage

| Mittel | Gemessen |
|---|---|
| `tools/ingestprobe/probe.php` | **83 / 0** |
| `tools/kopplungsprobe/probe.php` | **76 / 0, 0 übergangen** |
| `tools/komplettprobe/probe.php` | **64 / 0** |
| `tools/spurprobe/probe.php` | **45 / 0** |
| `tools/jobprobe/probe.php` | **35 / 0** |
| `tools/ratenprobe/probe.php` | **50 Prüfungen, 0 Befunde** |
| `tools/wiederherstellungs-probe/probe.php` | **111 / 0** — sie war durch AP7 kaputt und ist es nicht mehr (Problem 1a) |
| `tools/gpxprobe/probe.php` | **95 / 4** — unverändert der Befund aus Nr. 259 |
| `node tools/klickprobe/probe.mjs` | **48 von 48 Wegen erfüllt, 0 verfehlt** |
| `kreislauf.py --art edbak --frisch` | **328 771 Einzelvergleiche, 0 unerklärte Abweichungen** (21 erwartete) — dieselbe Zahl wie vor dem Paket |
| `kreislauf.py --art edbak-alt --frisch` | **287 852 Einzelvergleiche, 0 unerklärte** (795 erwartete) — dieselbe Zahl wie vor dem Paket |
| `kreislauf.py --art csv --frisch` | **10 922 Einzelvergleiche, 0 unerklärte** (1 271 erwartete) — dieselbe Zahl wie vor dem Paket |
| `tools/screenshots/vergleichen.py` | **496 von 496 Seiten formgleich, 0 abweichende Schreibweisen** (acht Breiten; Ziffern zu `#` vereinheitlicht) |

## G3. Prüfliste — was **die Auftraggeberin** noch tun muss

| # | Weg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **G-1** | **Betrieb → Status** öffnen und die Altersangaben lesen („zuletzt gelaufen vor …") | Wie bisher: „gerade eben" unter 90 s, dann Minuten bis 90 Minuten, dann Stunden | Eine Angabe springt an einer anderen Stelle von Minuten auf Stunden, oder es steht „nie"/„unbekannt", wo ein Zeitpunkt vorliegt |
| **G-2** | **Betrieb → Updates** öffnen, wenn ein Komplett-Backup besteht | Das Alter des jüngsten Backups steht da — **und zwar nach der neuen Regel**: unter 90 s „gerade eben", zwischen 60 und 90 Minuten „vor 60 … 90 Minuten". Das ist die benannte Ausnahme E-ZE-23 | „vor 1 Minuten" bei einem frischen Backup — dann ist die alte Kopie noch da |
| **G-3** | **Betrieb → Server** öffnen, **Speichergrenze ändern und speichern** (etwa von 2 auf 3 GB und zurück) | Das Feld nimmt die Zahl an, das Speichern gelingt, die Zahl steht danach wieder da | „Bitte eine gültige Zahl" oder ein Komma im Feld — dann ist ein Formularwert doch umgestellt worden (N7-3) |
| **G-4** | **Betrieb → Statistik** öffnen, alle drei Zeiträume durchschalten | Zahlen mit Tausenderpunkt, Anteile als „42 %", leere Anteile **leer** statt „0 %" | Ein Anteil steht als „0 %" da, wo vorher nichts stand |
| **G-5** | **Statistik als CSV herunterladen** | Die Anteilsspalten enthalten **0**, wo es keine Bezugsgröße gibt — **nicht** leer | Eine leere Zelle, wo eine 0 stehen muss: in einer Tabelle heißt das etwas anderes |
| **G-6** | **Ein Konto-Backup herunterladen** — am besten aus einem **kleinen** Konto (unter 1 MB) | Die Erfolgsmeldung endet auf eine Größe mit **KB**, nicht „0,3 MB". Das ist die benannte Ausnahme E-ZE-26 | „NaN MB", gar keine Größe, oder ein Punkt statt eines Kommas |
| **G-7** | **Einstellungen → Sicherung**: die Speicherzeile lesen | „3 von 250 MB" mit **einer** gemeinsamen Einheit — genau wie bisher | „3,4 MB von 250,0 MB" — dann ist das Paar doch je Wert umgestellt |
| **G-8** | **Verwaltung → ein Konto öffnen** und dieselbe Speicherzeile lesen | **Wortgleich** mit G-7 | Zwei Seiten zeigen denselben Füllstand verschieden |
| **G-9** | **Einen Diensttag anlegen** — am besten **abends nach 23 Uhr** Ortszeit | Das Datumsfeld ist mit **heute** in deiner Zeitzone vorbelegt, nicht mit gestern. Das ist die benannte Ausnahme F-ZE-1 | Das Feld zeigt den Vortag |
| **G-10** | **Einen Einsatz löschen** und den Kartentitel lesen | „Einsatz vom 22.09.2026, 14:30 Uhr" — Datum, Komma, Zeit, „Uhr" | Ein fehlender Gedankenstrich, eine doppelte Zeit, ein verschobenes Komma |
| **G-11** | **Papierkorb** öffnen | Dieselbe Schreibweise wie in G-10, dazu „gelöscht am …" | wie G-10 |
| **G-12** | **Eine GPX-Datei herunterladen** und im Editor den Kopf ansehen | `<time>` trägt die Marke mit `T` und `Z`, **ohne** `+00:00` | Ein Zonenversatz statt des `Z` — dann liest keine Karten-App die Datei mehr wie bisher |
| **G-13** | **Ein Gerät koppeln** und den Zeitpunkt in der Bestätigung lesen | Wie bisher | Ein anderes Format |
| **G-14** | **Ein Gerät mit vollem Konto senden lassen** (nur wenn ohnehin ein Konto an der Grenze steht) | Die Antwort nennt „… Einsätzen, 250 von 250 MB." — mit Satzschlusspunkt | Der Punkt fehlt, oder die Größe steht anders da (N7-2) |

## G4. Grenzen — was sich mit diesem Paket NICHT beantworten lässt

- **Der Textvergleich misst, was auf einer Seite STEHT, nicht was nach einem
  Klick erscheint.** Meldungen nach dem Speichern, Mailtexte, die 507-Antwort
  und die Größenangabe nach einem Download sind darin nicht enthalten. Für
  sie steht die Rechnung je Funktion — und die Prüfliste unten.
  **Diese Grenze ist keine Vorsichtsformel: in ihr saß ein echter Fehler.**
  Die Fertigmeldung des Sicherns las sich „263 KB MB" (N7-7, Web 20.32.1).
  496 formgleiche Seiten und 328 771 grüne Einzelvergleiche standen daneben
  und konnten nichts dazu sagen — die einen sehen nur aufgerufene Seiten,
  die anderen nur den Inhalt der Datei. Gefunden hat ihn das **Protokoll**
  des Kreislaufs, weil es die Meldung mitschreibt. Wer also einen Text
  umbaut, der erst nach einer Aktion entsteht, hat dafür kein Werkzeug und
  muss ihn lesen.
- **Die 15 stehengelassenen Stellen sind begründet, nicht vorgeführt.** Dass
  das Formular auf Betrieb → Server mit einem Komma unabschickbar wäre, ist
  aus dem POST-Zweig gelesen und nicht ausprobiert worden (N7-3).
- **Eine Zeichengleichheit über Zufallswerte ist keine über alle Werte.**
  Wo es ging, sind die Stufengrenzen lückenlos abgedeckt (1 KiB, 1 MiB, 1 GiB
  je ±1024; die Zeitschwellen 90 / 5 400 / 172 800 s lückenlos). Wo nicht, ist
  es eine Stichprobe mit Zahl.
- **Der Textvergleich braucht einen Vorzustand, und den gibt es nur, weil er
  vorher weggesichert wurde.** Jeder Bilderlauf löscht den vorigen. Wer AP8
  ebenso belegen will, sichert **vor** der ersten Änderung.

---

## H0. AP8a — was nicht geprüft werden konnte, und warum

**Diese Liste steht am Anfang und nicht in einer Fußnote.**

| # | Was | Warum nicht | Woran man ein Scheitern erkennt |
|---|---|---|---|
| **N8a-1** | **Der Bildvergleich als Beleg** | Zwischen Grundlinie und Nachlauf ist die **Klickprobe** gelaufen und hat den Demo-Bestand verändert — Rettungsmittel, Besatzung, Zielklinik, ein gelöschter Standort, eine aufgehobene Sperre. 295 von 496 Bildern weichen deshalb ab, und keine dieser Abweichungen sagt etwas über AP8a. Dass das Werkzeug nicht *pauschal* rauscht, ist belegt: drei öffentliche Seiten in zwei Breiten sind **bitgleich**. Der Beleg ist der **Formvergleich** (Ziffern zu `#`) | Eine Seite weicht in der **Form** ab, nicht nur im Wert |
| **N8a-2** | **Der Vollbildmodus der Karte** | `attachFullscreenControl` ist nicht angefasst. Dass der Knopf **da** ist, ist gemessen (4 von 4); dass er **wirkt**, ist es nicht — der Bilderlauf klickt ihn nicht, und die Klickprobe hat keinen Weg dafür | Klick auf den Vollbildknopf ändert nichts, oder die Karte kommt grau zurück |
| **N8a-3** | **Der Ebenenumschalter** | Dass er da ist und vier Einträge trägt, kommt aus `attachBaseLayers` und ist nicht angefasst. Ein Wechsel auf Wanderkarte, Topo oder Satellit ist **nicht durchgefahren** | Ein Eintrag im Umschalter lädt keine Kacheln |
| **N8a-4** | **Die Größenüberwachung (`ResizeObserver`)** | Sie hängt an `attachBaseLayers()`, unverändert. Ob sie nach der Reihenfolgeänderung auf der Tagesübersicht noch greift, ist **rechnerisch** unbedenklich (sie beobachtet den Behälter, nicht den Ausschnitt), aber nicht im Browser bei 1920 px mit wachsender Tabelle nachgefahren | Die Karte der Tagesübersicht zeigt ab 1600 px unten graue Fläche statt Kacheln |
| **N8a-5** | **`ortswahl.js`** | Bleibt draußen (Modul, keine Seite). Der Ortswahl-Dialog ist **nicht** neu durchgefahren worden | Der Ortswahl-Dialog zeigt keine Karte mehr |

## H1. AP8a — was maschinell geprüft wurde, mit Mittel **und** Zahl

### Gegen den Quelltext

| Mittel | Gemessen |
|---|---|
| `php tools/zaehlung/zaehlen.php` | **38 Zeilen, 0 über der Decke.** Z35 **4 → 0** |
| `php tools/zaehlung/zaehlen.php --selbstprobe` | **34 von 34** |
| Gegenprobe `grep -rn "L\.map("` über `server/` ohne `vendor/` | **1 Fundstelle** — `assets/ortswahl.js:184`, das Modul, das das Register ausdrücklich ausnimmt. Dazu 5 Erwähnungen in Kommentaren |
| `node --check server/assets/map_layers.js` | **fehlerfrei** |
| `php -l` über die vier geänderten Seiten | **4 Dateien, 0 Fehler** |
| `tools/wortliste/wortliste.py` | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** (99 Regeln, 99 gegriffen) |
| `tools/vollstaendigkeit/pruefen.py` | **398** — unverändert |

### Gegen die laufende Anlage

| Mittel | Gemessen |
|---|---|
| `node tools/klickprobe/probe.mjs` | **48 von 48 Wegen erfüllt, 0 verfehlt** |
| `node tools/screenshots/aufnehmen.mjs` | **496 Einzelbilder, 62 Kontaktbögen · Überlauf 0 · Konsolenfehler 0 · Knöpfe falscher Höhe 0 · 162 Karten im Seitengerüst, 0 außerhalb `main.inhalt`** |
| `tools/screenshots/vergleichen.py --nur-text` | **496 Seiten verglichen, 41 Befunde — alle acht Breiten von `48-betrieb-server`** (Erklärung unten) |
| `tools/screenshots/vergleichen.py --selbstprobe` | **14 von 14** |
| eigene Kartenprobe im Browser (Chromium, 1440 px) | **4 von 4 Karten**: Behälter da, Kacheln geladen (6 bis 10), Ebenenumschalter da, Vollbildknopf da, Größenknopf **nur** auf der Tagesübersicht, **0 Konsolenfehler**, 0 unbehandelte Ausnahmen |

### Die 41 Befunde des Formvergleichs — nachgegangen, nicht abgetan

Alle 41 liegen auf `betrieb_server.php`, dem CSP-Verstoßprotokoll, in allen
acht Breiten. Die Zeilenformen, die verschwinden, und die, die neu auftauchen,
sind **dieselben drei** — `connect-src · data`, `connect-src ·
https://photon.komoot.io`, `script-src · wasm-eval` —, nur in anderer
Reihenfolge; dazu wechselt eine Quellseite von `blob` auf `einsatz_form.php`.

Nachgesehen in der Datenbank:

```
SELECT * FROM csp_berichte ORDER BY zuletzt DESC;
→ 3 Zeilen, erstellt 2026-09-21 19:48:03 / 19:48:03 / 20:37:16
```

**Drei Zeilen, alle einen Tag vor AP8a erstellt, keine neue.** Die Seite
sortiert nach dem letzten Auftreten; im Nachlauf ist der
`photon.komoot.io`-Verstoß erneut aufgetreten (`zuletzt` 11:28:39) und nach
oben gerutscht. Damit ist die Abweichung vollständig erklärt und **kein
Befund gegen AP8a**.

## H2. AP8a — Prüfliste: was **die Auftraggeberin** noch tun muss

| # | Weg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **H-1** | **Tagesübersicht** öffnen, einen Tag mit Spuren wählen | Die Karte steht, Pins und Linien sitzen richtig, der Ausschnitt springt einmal auf die Daten | Die Karte bleibt auf `[48.5, 10.5]` stehen, oder die Konsole zeigt „this._point is undefined" — dann hat die Reihenfolgeänderung (AP8a-d) doch etwas gekostet |
| **H-2** | **Tagesübersicht bei 1600 px oder breiter**, warten bis die Einsatztabelle daneben steht | Die Karte füllt ihre ganze Höhe mit Kacheln | Unten bleibt graue Fläche — dann greift die Größenüberwachung nach der Reihenfolgeänderung nicht mehr (N8a-4) |
| **H-3** | **Tagesübersicht:** den **dritten Kartenknopf** benutzen (Karte größer/kleiner) | Der Knopf ist da und wirkt; nach dem Umschalten sind die Kacheln vollständig | Der Knopf fehlt, oder die Karte kommt halb grau zurück |
| **H-4** | **Einsatzansicht, Tagesspuren und Zeitraum** öffnen | Dort gibt es **keinen** dritten Kartenknopf — nur Zoom, Vollbild und den Ebenenumschalter | Ein Größenknopf ist dort aufgetaucht: dann steht `groesse` fälschlich auf `true` |
| **H-5** | **Zeitraumansicht** (`zeitraum.php?y=2026`) öffnen und einen Monat wählen | Die Karte erscheint erst, wenn Pins da sind, und zeigt dann vollständige Kacheln | Die Karte bleibt grau oder zeigt nur ein Kachelquadrat — dann ist der ausdrückliche `invalidateSize()` verlorengegangen (AP8a-e) |
| **H-6** | Auf **einer** Karte den **Ebenenumschalter** durchgehen: Standard, Wanderkarte, Topographisch, Satellitenbild | Jede der vier lädt Kacheln, die Attributionszeile unten rechts wechselt mit | Eine Ebene bleibt leer (N8a-3) |
| **H-7** | Auf **einer** Karte den **Vollbildknopf** drücken und wieder verlassen | Vollbild geht auf, die Kacheln sind vollständig, ESC bringt zurück | Nach dem Umschalten fehlen Kacheln (N8a-2) |
| **H-8** | **Einsatzort wählen** (Ortswahl-Dialog in einem Einsatzformular) | Die Karte im Dialog steht wie bisher | Keine Karte im Dialog (N8a-5) |

## H3. AP8a — Grenzen

- **Der Formvergleich misst, was auf einer Seite steht, nicht wie eine Karte
  aussieht.** Eine Karte hat als Text nur die Attributionszeile und die
  Beschriftungen des Umschalters. Dass diese da sind, ist ein guter
  Anhaltspunkt — dass die Kacheln am richtigen Ort liegen, sagt er nicht.
  Dafür steht die Prüfliste oben.
- **Der Bildvergleich war in diesem Paket nicht benutzbar** (N8a-1). Wer AP8b
  ebenso belegen will, fährt den Bilderlauf **vor** der Klickprobe, nicht
  danach — oder nimmt eine zweite Grundlinie nach der Klickprobe auf.
- **Die Reihenfolgeänderung ist an 496 Seiten ohne Formbefund und an einer
  Kartenprobe ohne Konsolenfehler gemessen, nicht bewiesen.** Was sie
  betrifft — Pin-Positionen zum Zeitpunkt des ersten Zeichnens — ist genau
  das, was ein statischer Abzug schlecht zeigt. H-1 und H-2 sind deshalb die
  zwei Punkte der Liste, die wirklich zählen.

---

## I0. AP8b bis AP8f — was nicht geprüft werden konnte, und warum

**Diese Liste steht am Anfang und nicht in einer Fußnote.**

| # | Was | Warum nicht | Woran man ein Scheitern erkennt |
|---|---|---|---|
| **N8-1** | **Der Netzausfall im echten Betrieb** | `EdApi` liefert bei Netzausfall `status: 0` und einen deutschen Satz. Gemessen ist das gegen einen toten Port (`https://127.0.0.1:9/`), **nicht** gegen ein abreißendes Mobilfunknetz mitten in einem Upload. Der Unterschied: Dort kann ein Teil bereits angekommen sein | Nach einem Abbruch steht „Die Verbindung zum Server ist abgebrochen", obwohl der Server geschrieben hat |
| **N8-2** | **Der Satz zu `post_max_size`** | Er entsteht nur, wenn ein POST die Servergrenze übersteigt. Dass `EdApi` das Feld `hinweis` jetzt **überall** liest, ist im Quelltext belegt und an einer künstlichen Antwort nachgerechnet — **nicht** durch einen echten zu großen Upload | Ein zu großes Backup zeigt weiter „HTTP 400" statt des Hinweises |
| **N8-3** | **Die Schlüsselblatt-Rückfrage** | Die Array-Regel von `postForm` ist Zeichen für Zeichen gegen die alte Fassung gehalten und an einem Rumpf nachgerechnet (`gruppen[]=a&gruppen[]=b…`) — **aber die Rückfrage selbst ist nicht durchgefahren**: Sie zählt Fehlversuche und sperrt, und ein Fehlversuch auf dem Prüfstand wäre einer zu viel | Beim Prüfen wird ein Fehlversuch gezählt, obwohl die Gruppen stimmen (siehe I3, Punkt I-6) |
| **N8-4** | **Der Vollbildmodus und der Ebenenumschalter der Karten** | Unverändert N8a-2/N8a-3 aus AP8a | siehe dort |
| **N8-5** | **Die KDF-Anhebung** | Sie läuft still und nur, wenn ein Konto auf einer alten Rundenzahl steht. Dass der Weg nach dem Umbau trägt, ist **rechnerisch** belegt (`api.js` steht im `<head>`, auf 47 Dateien statt 32; die Ladereihenfolge im Browser nachgemessen: Position 0 auf allen sieben geprüften Seiten) — **ein echtes Konto mit alter Rundenzahl wurde nicht angehoben** | Ein Bestandskonto bleibt nach der Anmeldung auf der alten Rundenzahl (Betrieb → Status, Zeile „Schlüsselableitung") |
| **N8-6** | **Der Bildvergleich** | Unverändert N8a-1: Die Klickprobe lief zwischen Grundlinie und Nachlauf und hat den Demo-Bestand verändert. Der Beleg ist der **Formvergleich** | Eine Seite weicht in der **Form** ab, die nicht in der Liste der drei benannten Änderungen steht |
| **N8-7** | **Die dritte Dauer-Schreibweise im Schnittblock** | `schneiden.js` schreibt weiter „1 h 6 min" mit Leerzeichen und trägt denselben Rundungsfehler, den AP8d behoben hat. **Absichtlich nicht angefasst** — es wäre eine vierte sichtbare Änderung, und die drei sind einzeln freigegeben worden (Backlog Nr. 273) | — (bekannt und gewollt) |

## I1. AP8b bis AP8f — was maschinell geprüft wurde, mit Mittel **und** Zahl

### Der Kernbeleg: die Zentralen gegen ihre Vorgängerinnen

| Zentrale | gegen | gemessen |
|---|---|---|
| `EdHtml.meldung()` | `ui_meldung_markup()` (PHP, im Browser gegen den echten Server) | **200 von 200 Fällen gleicher DOM-Baum und gleicher Text**; 160 davon auch quellgleich. Die 40 Abweichungen liegen **nur** in der Entität des Hochkommas (`&#39;` gegen `&#039;`) — die erzeugt `EdHtml.escape` seit Baustein B7 so, nicht dieses Paket |
| `EdFormat.tag` | `fmtTag` | **1116 Fälle** (alle Kalendertage 2024–2026 plus 20 Randwerte), **0 Abweichungen bei gültigen Datumsstrings**; alle 16 Abweichungen liegen außerhalb der Form `JJJJ-MM-TT`, acht davon warf die alte Fassung |
| `EdFormat.dauer` | `fmtDur` | **86 442 Fälle**, im gültigen Bereich **720 Abweichungen = 0,83 %** — und **jede einzelne** endet in der alten Fassung auf „60min". Keine andere Art ist darunter (Gegenprobe gefahren) |
| `EdFormat.km` | `fmtKmZahl` | **1496 Fälle, 3 Abweichungen**, alle bei nicht-numerischer Eingabe (`NaN`, `'abc'`, `{}`) |
| `EdFormat.km` | `luftlinie.js` | **57 143 Meterwerte, 0 Abweichungen** |
| `EdApi.postForm` Array-Regel | die alte Handschleife | Rumpf Zeichen für Zeichen: `csrf=X&gruppen[]=a&gruppen[]=b&gruppen[]=c&gruppen[]=d&aktion=pruefen` |

### Gegen den Quelltext

| Mittel | Gemessen |
|---|---|
| `php tools/zaehlung/zaehlen.php` | **38 Zeilen, 0 über der Decke.** Z28 **15 → 1** · Z29 **5 → 2** · Z34 **14 → 5** · Z35 **4 → 0** · Z36 **4 → 1** · Z37 **8 → 4** |
| `--selbstprobe` | **34 von 34** |
| `node --check` über `server/assets/` | **42 Dateien, 0 Fehler** |
| `php -l` über `server/` | **116 Dateien, 0 Fehler** |
| `tools/wortliste/wortliste.py` | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** |
| `tools/vollstaendigkeit/pruefen.py` | **397** (vorher 398) — beide Bewegungen erklärt, siehe unten |
| `tools/kettenaufrufe/pruefen.py` | **0 Befunde, 0 ungeprüft** |
| `php tools/cspprobe/pruefen.php` | **117 `<script>`-Stellen, 5 Regeln, 0 Befunde**; Selbstprobe **10 von 10** (zwei Fälle neu) |
| Gegenprobe über das Muster der **Rechnung** | `(m/1000).toFixed` außerhalb `format.js`: **2 Treffer**, beide geprüft (einer ist eine Zahl für eine Tabellenzelle, keine Anzeige; einer war die sechste km-Fassung und ist umgestellt). `Math.floor(s/3600)`: **1 Treffer** — die dritte Dauer-Schreibweise, Backlog Nr. 273 |

### Gegen die laufende Anlage

| Mittel | Gemessen |
|---|---|
| `node tools/klickprobe/probe.mjs` | **48 von 48 Wegen erfüllt, 0 verfehlt** (dreimal gefahren: nach AP8b/c/e, nach AP8d, nach AP8f) |
| `kreislauf.py --art edbak --frisch` | **328 771 Einzelvergleiche, 0 unerklärte** — dieselbe Zahl wie vor dem Paket |
| `kreislauf.py --art edbak-alt --frisch` | **287 852 Einzelvergleiche, 0 unerklärte** — dieselbe Zahl |
| `kreislauf.py --art csv --frisch` | **10 922 Einzelvergleiche, 0 unerklärte** — dieselbe Zahl |
| eigene `EdApi`-Probe im Browser | **7 Seiten**: `EdApi` auf allen, **an Position 0, im `<head>`**, 0 Konsolenfehler. Fünf Fehlerfälle gegen den echten Server, alle im vorgeschriebenen Satzbau |
| eigene `EdHtml`-Probe | **5 von 5 Tönen** mit richtiger Klasse, Rolle und Symbol; Wurf beim sechsten; `roh` und Maskierung je belegt |
| eigene `EdPat`-Probe | **6/6, 96/96, 96/96 Einträge entschlüsselt**, 0 unlesbar, `_dx`/`_ort`/`_age` gefüllt, Banner richtig, Altersfilter der Suche frei, **0 Konsolenfehler** |

**Dass die drei Kreisläufe dieselbe Zahl liefern, ist der stärkste Einzelbeleg
dieses Pakets:** `export.js`, `import_ui.js` und das Inline-JavaScript von
`einstellungen.php` sind an zwölf Sendestellen umgebaut worden, und der
vollständige Weg Sicherung → Einspielen → erneute Sicherung liefert Byte für
Byte dasselbe.

### Warum die Vollständigkeit von 398 auf 397 gegangen ist

Zwei Bewegungen, beide nachgesehen:

- **Eine Klasse weniger als Literal, eine Regel mehr ohne Markup:**
  `.meldung-ok`. Seit AP8e setzt `EdHtml.meldung()` die Tonklasse **zusammen**
  (`'meldung meldung-' + ton`), wie `ui_meldung_markup()` in PHP seit jeher.
  Das Werkzeug kann eine zusammengesetzte Klasse nicht sehen — `.meldung-schutz`
  steht aus demselben Grund schon länger in der Liste. Kein Befund, aber ein
  blinder Fleck (Backlog Nr. 274).
- **Ein Unicode-Symbol weniger:** der Gedankenstrich im gelöschten `fmtKm`.

### Der Formvergleich, und warum seine Zahl allein irrefuehrt

**488 von 496 Seiten formgleich.** Die acht Abweichungen liegen alle auf
`betrieb_server.php`, dem CSP-Verstoßprotokoll — und die Ursache ist diesmal
**die eigene Prüfung**: In der Datenbank steht seit 13:15:17 eine Zeile
`connect-src · https://127.0.0.1 · index.php`, entstanden, als die
`EdApi`-Probe absichtlich einen toten Port anrief, um den Netzfehler zu
messen. Kein Befund gegen den Umbau.

**Diese Zahl ist trotzdem kein Beleg dafür, dass sich keine Schreibweise
geändert hat** — und das ist der wichtigere Satz. Der Formvergleich ersetzt
jede Ziffernfolge durch **ein** `#`. Aus „5" und aus „05" wird dasselbe
`#h #min`. **Eine führende Null ist für ihn unsichtbar.**

Die drei benannten Änderungen wurden deshalb einzeln nachgezählt:

| Änderung | Im Bilderlauf sichtbar? | Gemessen |
|---|---|---|
| **(a) zweistellige Minute** | **Ja, aber nicht für den Formvergleich** | Verschiedene Dauern über alle 496 Abzüge: **33 vorher, 32 nachher.** Verschwunden ist `2h 5min`; an seiner Stelle steht `2h 05min` — auf `12a-einsatzansicht-winde` in **allen acht Breiten**. Genau die Seite, auf der `fmtDauer` saß |
| **(b) kein „60min" mehr** | **Nein** | `60min` kommt in **0 von 496** Abzügen vor, vorher wie nachher. Der Fall braucht eine Dauer in den letzten 30 Sekunden vor einer vollen Stunde; der Demo-Bestand hat keine. Belegt ist er rechnerisch: **720 von 86 401 Sekundenwerten**, jede einzelne endete alt auf „60min" |
| **(c) Tausenderpunkt** | **Nein** | Die einzige Summe über 999 km in den Abzügen ist `2.431 km` — und die kommt aus Suche und Zeitraum, wo der Punkt schon stand. Die Startseite, die ihn nicht hatte, zeigt im Demo-Bestand **59 km** |

**Daraus folgt für die Prüfliste:** (b) und (c) sind im Demo-Bestand nicht
erreichbar. Wer sie sehen will, braucht andere Daten — deshalb stehen sie
unten als I-4 und I-5 mit einem Bedienweg, nicht als Häkchen.

## I2. Grenzen

- **Der Formvergleich misst, was auf einer Seite STEHT.** Die drei
  Satzbau-Änderungen von AP8b erscheinen erst **nach** einem Fehler, und den
  erzeugt keine Aufnahme. Dafür stehen die Rechnung je Funktion und die
  Prüfliste unten. Das ist dieselbe Grenze, in der in AP7 ein echter Fehler
  saß („263 KB MB") — sie ist keine Vorsichtsformel.
- **Die Gegenprobe über das Muster der Rechnung ist nicht vollständig.** Sie
  sucht drei Muster (`/1000).toFixed`, `split('-')`, `Math.floor(…/3600)`).
  Ein vierter Weg, dieselbe Sache auszurechnen, fiele ihr nicht auf. Die
  Zählzeile Z34 sucht über eine **Namensliste** und sieht noch weniger — sie
  hätte `luftlinie.js` nie gefunden.
- **Der Formvergleich ist blind für führende Nullen.** `\d+` ist gierig; „5"
  und „05" ergeben dasselbe `#`. Seine Zahl beantwortet „hat sich eine
  Schreibweise verschoben?", nicht „hat sich eine Stellenzahl geändert?".
  Der Docstring der Funktion sagt das jetzt mit dem gemessenen Fall — vorher
  stand dort nur „eine andere Rundung bei gleicher Stellenzahl", und das war
  zu eng.
- **Zwanzig Gegenleser sind keine Garantie.** Sie haben 41 Mängel gefunden,
  zwei davon schwer, und beide waren echt. Sie haben auch Dinge behauptet, die
  nicht stimmten — eine „schwere" Meldung betraf eine Datei, die schlicht noch
  nicht committet war. Jeder Befund ist einzeln nachgemessen worden, bevor er
  zu einer Änderung wurde.

## I3. Prüfliste — was **die Auftraggeberin** noch tun muss

| # | Weg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **I-1** | **Einen Fehler provozieren:** Backup einspielen mit einer Datei, die keine Backup-Datei ist | „Das Einspielen ist fehlgeschlagen: …" — **ein** „fehlgeschlagen" im Satz, der Vorgangsname **einmal** | Zwei „fehlgeschlagen" hintereinander, oder ein Maschinenwort wie `format` als Satz |
| **I-2** | **Dasselbe im Export:** Einstellungen → Backup, Export mit falschem Passwort | „Der Export ist fehlgeschlagen: Das ist nicht dein Kontopasswort. …" | wie I-1 |
| **I-3** | **Netz trennen und speichern:** Tagesübersicht, Tagesdaten ändern, WLAN aus, speichern | „Das Speichern ist fehlgeschlagen: Die Verbindung zum Server ist abgebrochen." | „Failed to fetch" (englisch), oder die Zeile bleibt auf „Speichern…" hängen |
| **I-4** | **Einsatzansicht öffnen** und die Phasendauern lesen | „1h 06min" — **zweistellige** Minute. Das ist die benannte Änderung | „1h 6min" — dann ist die Vereinheitlichung nicht angekommen |
| **I-5** | **Startseite:** die Streckensumme über der Einsatzliste bei mehr als 999 km | „1.633 km" mit Tausenderpunkt — wie auf Suche und Zeitraum | „1633 km" |
| **I-6** | **Schlüsselblatt-Rückfrage** (Betrieb → Schlüsselblatt), alle vier Gruppen **richtig** eintragen und prüfen | Die Rückfrage geht durch. **Das ist der wichtigste Punkt der Liste** (N8-3) | Ein Fehlversuch wird gezählt, obwohl die Gruppen stimmen — dann ist die Array-Regel verlorengegangen, und weitere Versuche verlängern die Sperre |
| **I-7** | **Schlüssel erneuern** (Einstellungen → Profil) mit **falschem** Passwort | „Die Erneuerung ist fehlgeschlagen: Das Passwort ist nicht korrekt." | Nur „Das Passwort ist nicht korrekt." ohne Vorgangsnamen, oder ein Maschinenwort in Klammern |
| **I-8** | **Import durchführen** und die Erfolgsmeldung lesen | Haken-Symbol, Zahlen, und der Link „Ersten Tag öffnen" ist **klickbar** | Der Link steht als Quelltext da (`<a href=…>`) — dann ist die Rohmarkup-Ausnahme verlorengegangen |
| **I-9** | **Suche öffnen, ohne zu entsperren** | Trefferliste da, Sperrbanner sichtbar, **Altersfilter gesperrt** und grau | Der Altersfilter sieht benutzbar aus und liefert nichts |
| **I-10** | **Tagesübersicht und Zeitraum ohne Entsperren** | Sperrbanner sichtbar, Tabelle ohne geschützte Angaben | Kein Banner, oder ein Banner auf einem Tag **ohne** geschützte Angaben |
| **I-11** | **Einen Tag mit Spuren öffnen und schneiden** | Der Schnittblock arbeitet wie bisher; im Fehlerfall „Das Schneiden ist fehlgeschlagen: …" | Der Hinweiskasten bleibt leer, oder der Knopf bleibt gesperrt |
| **I-12** | **GPX-Datei importieren**, absichtlich eine kaputte | „Der Import der GPX-Datei ist fehlgeschlagen: …" im roten Kasten | Kasten ohne Symbol, oder Text außerhalb |
| **I-13** | **Abmelden, neu anmelden** und Betrieb → Status öffnen | Zeile „Schlüsselableitung" grün; die stille KDF-Anhebung läuft (N8-5) | Ein Bestandskonto bleibt auf alter Rundenzahl |
| **I-14** | **Statistik als CSV herunterladen** und die Dauerspalten ansehen | `HH:MM` wie bisher, leere Zellen wo bisher leer | Eine `0` wo eine leere Zelle stand — in einer Tabelle heißt das etwas anderes |

---

## J0. AP9a — was **nicht** geprüft werden konnte, und warum

Das steht hier vorn, nicht in einer Fußnote.

- **Kein Diensttag ohne jede Fähigkeit auf einem *gewachsenen* Bestand.** Der
  Demo-Bestand hat alle vier Fälle (Luft/Boden × Fähigkeit ja/nein), aber er
  ist frisch eingespielt. Auf einer Anlage, die die Migration
  `2026_08_17_notarzt_erweiterung` durchlaufen hat, trägt **jeder** damals
  bestehende Diensttag beide Fähigkeiten — auch ein NEF-Tag von 2025. Dort
  sieht die Änderung an Alttagen **nichts**: Sie tragen die Fähigkeit, und
  luftgebunden sind sie auch. Das ist kein Fehler, aber es heißt, dass die
  Wirkung auf der Produktivanlage kleiner ausfällt als im Demo-Bestand.
- **Der Bildvergleich kann die Frage nicht beantworten.** 56 von 56 Bildern
  weichen ab — Ursache ist der Countdown im Demo-Banner, der auf jeder Seite
  des Demo-Kontos steht (die LIESMICH des Werkzeugs nennt 303 von 496 auf
  **unverändertem** Code). Was trägt, ist der **Formvergleich**; seine Zahl
  steht unten.
- **Die Kacheln unter 720 px sind nur an einem Tag nachgemessen** (Boden-Tag
  367, Plaketten „Bergwacht" und „Winde" stehen trotz fehlender Spalten). Die
  Regel ist einfach genug, dass ein Fall genügt — aber es ist einer.

## J1. AP9a — was maschinell geprüft wurde, mit Mittel **und** Zahl

### Gegen die laufende Anlage (Playwright, Demo-Konto)

**Fünf Diensttage, je vier Zahlen.** Gemessen wurden sichtbare `<th>`,
versteckte `<th>`, Zellen der ersten `<tr>` und Einträge des Sortierblatts:

| Tag | Art / Fähigkeit | vorher | nachher |
|---|---|---|---|
| 364 | Luft, **mit** | 11 Köpfe | **11 / 0 / 11 / 10** |
| 372 | Luft, **ohne** | 11 Köpfe | **9 / 2 / 9 / 8** |
| 367 | Boden, **mit**, 1 Windeneinsatz | 11 Köpfe | **9 / 2 / 9 / 8** |
| 355 | Boden, **mit**, 0 Windeneinsätze | 11 Köpfe | **9 / 2 / 9 / 8** |
| 357 | Boden, **ohne** | 11 Köpfe | **9 / 2 / 9 / 8** |

Zellen = Köpfe an allen fünf Tagen, vorher wie nachher. **0 Konsolenfehler.**

**Zeitraumübersicht:** Bodentab **10 → 8** Spalten (`winch`, `bw` weg), Luft-
und Mischtab unverändert bei 11 bzw. 12.

**Gegenprobe zur Suche — der eigentliche Beleg.** Der Demo-Bestand kann die
Regel auf Bestandsebene nicht unterscheiden (Fähigkeit und Haken fallen
zusammen). Also wurde die Antwort von `api/suchindex.php` im Browser
abgefangen und `faehigkeiten` auf `{winch: false, bergwacht: false}` gesetzt:
**beide Spalten verschwinden**, obwohl im Bestand **7 Einsätze mit Winden-
und 15 mit Bergwachthaken** stehen. Datengetrieben wären sie stehen
geblieben. Damit ist belegt, dass die **Fähigkeit** entscheidet und nicht das
Datum.

### Formvergleich (`tools/screenshots/vergleichen.py`)

56 Seiten (6 Seiten × 8 Breiten, plus die Mehrfachfassungen): **41 formgleich,
15 mit abweichender Form, 105 Zeilen.** Die 15 sind die drei
Tagesübersichtsseiten (`10-`, `11-`, `12-tagesuebersicht-adhoc`) in den fünf
Breiten ab 768 px — unter 720 px zeigt die Seite keine Tabelle, also auch
keine Spalten.

**Jede gedruckte Diff-Zeile wurde einzeln klassifiziert**, nicht überschlagen:
120 Zeilen, davon **15 alte Kopfzeilen** (`… Transport Bergwacht Winde km`),
**15 neue Kopfzeilen** (`… Transport km`), **45 alte Datenzeilen** (mit zwei
leeren Tabulatorfeldern vor der km-Zahl) und **45 neue Datenzeilen** (ohne).
**0 unerklärte Zeilen.**

### Gegen den Quelltext und die Prüfmittel

| Mittel | Zahl |
|---|---|
| `php -l` über die berührten Dateien | **0 Fehler** |
| `node --check assets/missiontable.js` | **ok** |
| Register (`tools/zaehlung/`) | **38 Zeilen, 0 über der Decke**, Rückgabe 0 |
| Wortliste | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** |
| Vollständigkeit | **397** — Zeile für Zeile gleich dem Stand davor, bis auf **eine** verschobene Zeilennummer |
| CSP-Probe | **0 Befunde** (vorher wie nachher) |
| Kettenaufrufe | **47 Aufrufe, 0 Befunde, 0 ungeprüft** |
| Bilderlauf | **56 Bilder, 0 Überlauf, 0 Konsolenfehler, 0 falsche Knopfhöhen** |

### Zwei Funde an den Werkzeugen selbst

- **Die Wortlisten-Ausnahme `basis-als-bezeichner` hing an der Stelligkeit
  eines Aufrufs.** Sie stand auf `nurWenn\(basis\)` — mit schließender
  Klammer. Sobald `nurWenn` ein zweites Argument bekam, griff sie nicht mehr,
  und der Lauf meldete einen Treffer auf einen Bezeichner. Steht jetzt auf
  `nurWenn\(basis\b`. Eine Ausnahme, die an der Stelligkeit hängt, misst den
  Zufall und nicht den Begriff.
- **`--erwartet` nimmt EINEN Namen je Flag**, keine Kommaliste
  (`action="append"`). Eine Kommaliste wird als ein Name gelesen, trifft
  nichts und meldet „ERWARTUNG OHNE TREFFER" — die Befundzahl steigt dabei,
  statt zu fallen. Das sieht aus wie eine Verschlechterung durch die eigene
  Erklärung.

## J2. AP9a — Grenzen der benutzten Prüfmittel

- **Der Formvergleich ist blind für führende Nullen** (`\d+` → `#`, gierig).
  Das ist hier ohne Belang, weil keine Zahl geändert wurde — es gilt aber
  weiter und steht hier, damit es nicht vergessen wird.
- **Der Bildvergleich ist auf dem Demo-Konto unbrauchbar** (siehe J0).
- **Das Register zählt keine Fähigkeitsregel.** Es hat keine Zeile dafür und
  bekommt auch keine: Die Regel ist keine „Stelle je Sache", sondern eine
  fachliche Entscheidung. Wer sie prüfen will, prüft sie im Browser.
- **Playwright misst das DOM, nicht die Wahrnehmung.** Dass ein `<th hidden>`
  keine Spalte erzeugt, ist gemessen; dass die Tabelle **gut aussieht**,
  ohne die beiden Spalten, ist eine Sichtprüfung und steht in der Liste unten.

## J3. AP9a — Prüfliste: was **die Auftraggeberin** noch tun muss

| # | Weg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **J-1** | **Einen luftgebundenen Diensttag mit Windenfähigkeit öffnen** (Tagesübersicht, ab 768 px) | Spalten „Bergwacht" und „Winde" stehen wie bisher | Sie fehlen — dann greift die Regel an der falschen Stelle |
| **J-2** | **Einen luftgebundenen Diensttag OHNE Windenfähigkeit öffnen** | Beide Spalten **fehlen**; die Tabelle hat neun Spalten statt elf | Die Spalten stehen leer da — dann ist `cap_gate` wieder nicht angekommen |
| **J-3** | **Einen bodengebundenen Bergwacht-Diensttag öffnen** (mit Fähigkeit) | Beide Spalten **fehlen** — auch wenn ein Einsatz des Tages den Haken trägt. **Das ist die entschiedene Folge** | Die Spalten stehen — dann folgt die Anzeige der Fähigkeit statt der Betriebsart |
| **J-4** | **Denselben Tag am Handy ansehen** (unter 720 px) | Die Kachel des Einsatzes trägt weiterhin die Plakette „Winde" bzw. „Bergwacht" | Die Plakette fehlt — dann ist die gezogene Grenze verlorengegangen und es werden **Daten** verborgen, nicht nur Platz |
| **J-5** | **Denselben Einsatz zur Bearbeitung öffnen** (`einsatz_form.php`) | Windenfelder und Bergwachtfelder sind **da** und bedienbar — die Bearbeitung folgt der Fähigkeit allein | Sie fehlen — dann ist die Regel in die Bearbeitung durchgeschlagen, und dokumentierte Daten sind nicht mehr änderbar |
| **J-6** | **Zeitraumübersicht, Tab „bodengebunden"** | Keine Spalte „Winde"/„Bergwacht" | Sie stehen — dann greift der Bodentab die Luftfähigkeiten ab |
| **J-7** | **Zeitraumübersicht, Tab „luftgebunden"** | Beide Spalten stehen, auch wenn im Zeitraum niemand gewindet hat | Sie fehlen an einem Zeitraum ohne Windeneinsatz — dann entscheidet noch der Bestand |
| **J-8** | **Suche öffnen** | Beide Spalten stehen, solange **irgendein** Diensttag die Fähigkeit führt — auch ein bodengebundener | Sie fehlen, obwohl ein Bergwacht-Rettungsmittel eingerichtet ist |
| **J-9** | **Auf einem Tag ohne Windenspalte das Sortierblatt öffnen** (unter 1024 px, Knopf „Sortieren") | Acht Einträge, „Winde" und „Bergwacht" sind **nicht** dabei | Sie stehen in der Liste und lassen sich antippen — dann sortiert die Tabelle nach einer Spalte, die es nicht gibt |
| **J-10** | **Auf einem Lufttag nach „Winde" sortieren, dann die Tagesdaten speichern** mit einem bodengebundenen Rettungsmittel | Die Tabelle fällt auf „Beginn" zurück, der Pfeil steht dort | Der Pfeil verschwindet ganz, oder die Reihenfolge bleibt ohne sichtbaren Grund die alte |

---

## J4. AP9a Nachtrag (Web 20.36.0) — die Suchfilter folgen der Fähigkeit

**Die zweite Grenze aus J0 ist aufgehoben** (E-ZE-35). Sie stand dort als
benannte Grenze, wurde vorgelegt, und der Auftraggeber hat entschieden:
„Suche immer möglich, sobald Fähigkeiten vorkommen."

### Gemessen — drei Zustände, je acht Filter und der Block „Bergrettung"

Gezählt wurden die sichtbaren Kästen von `f-wi`, `f-cv`, `f-cb`, `f-pv`,
`f-pb`, `f-lv`, `f-bw`, `f-bu` sowie der umschließende Block:

| Zustand | vorher | nachher |
|---|---|---|
| Fähigkeit ja, Haken ja (der Demo-Bestand) | 8, Block da | **8, Block da** |
| Fähigkeit **nein**, Haken ja — Antwort abgefangen, `faehigkeiten={false,false}` | 8, Block da | **0, Block weg** |
| Fähigkeit ja, Haken **nirgends** — Antwort abgefangen, alle Haken auf `false`/`null` | **0, Block weg** | **8, Block da** |

**0 Konsolenfehler.** Der mittlere Zustand ist die Gegenprobe: Er zeigt, dass
die **Fähigkeit** entscheidet und nicht das Datum — die Haken liegen dort
unverändert im Bestand.

### Warum die beiden abgefangenen Zustände nötig waren

Der Demo-Bestand kann die Regel nicht unterscheiden: Wo eine Fähigkeit
eingerichtet ist, trägt auch mindestens ein Einsatz den Haken. Der Fall
„Fähigkeit ja, Einsatz nein" existiert nur **je Diensttag** (12 Tage für
`winch`, 9 für `bergwacht`), nicht auf Bestandsebene. Ihn zu bauen hieße, den
Referenzdatensatz zu ändern; ihn abzufangen misst dieselbe Verzweigung, ohne
den Bestand anzufassen. **Was damit NICHT gemessen ist:** ob der Server den
Fall richtig beantwortet — nur, dass der Browser ihn richtig verarbeitet. Die
Serverseite ist eine `GROUP BY`-Abfrage ohne Artfilter und wurde gelesen,
nicht gefahren.

### Prüfliste — Ergänzung

| # | Weg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **J-11** | **Ein Rettungsmittel mit Windenfähigkeit einrichten, aber keinen Windeneinsatz dokumentieren**, dann die Suche öffnen | Der Block **„Bergrettung"** steht in der Filterleiste, mit allen acht Feldern; „Windeneinsatz: nein" ist auswählbar und liefert alle Einsätze | Der Block fehlt — dann folgt die Sichtbarkeit noch dem Bestand, und eine Null lässt sich nicht nachweisen |
| **J-12** | **Kein Rettungsmittel mit Fähigkeit** (oder alle Fähigkeiten abwählen), Suche öffnen | Der Block „Bergrettung" **fehlt**, und die Tabelle hat keine Winden-/Bergwachtspalte | Der Block steht mit acht dauerhaft wirkungslosen Feldern |
| **J-13** | **Einen geteilten Suchlink öffnen, der `wi=ja` setzt**, auf einem Bestand ohne Windenfähigkeit | Der Filter ist **sichtbar** und gesetzt — die Ausnahme für geteilte Links gilt weiter | Der Filter ist unsichtbar, hält die Trefferliste aber leer: ein gesetzter Filter, den man nicht findet |

### Eine Frage aus derselben Runde, beantwortet statt umgesetzt

*„In der gemeinsamen Übersicht — ist da bisher eine Windenkachel?"* **Nein.**
`KACHELN_GEMISCHT` sind die **ersten vier** des Bodensatzes: Einsätze,
Diensttage, Ø Einsätze/Diensttag, Sekundärtransporte. Die beiden
Windenkacheln (`winchcycles`, `avgwinch`) stehen ausschließlich im Luftsatz.
Der Grund ist älter als dieses Paket und steht im Quelltext daneben: Über
beide Arten hinweg sind Kilometer, Dauern, Fehleinsätze und Winden-Cycles
nicht sinnvoll addierbar.

**Ein Unterschied, der daraus folgt und festgehalten gehört:** Die gemischte
Ansicht hat seit Web 20.35.0 die **Spalten** Winde und Bergwacht, wenn ein
Luft-Diensttag des Zeitraums die Fähigkeit führt — aber keine Windenkachel.
Das ist kein Widerspruch (eine Spalte zeigt den einzelnen Einsatz, eine
Kachel eine Summe über beide Arten), sieht aber wie einer aus. Wer die
Kacheln im Mischsatz haben will, braucht eine Gestaltungsentscheidung mit
Mockup (`CLAUDE.md` 5) — sie steht heute nicht an.

---

## J5. AP9b (Web 20.37.0) — der Generatorzusammenzug

### Was **nicht** geprüft werden konnte, und warum

- **Der Nachtdienst existiert im Bestand nicht.** Kein Diensttag des
  Demo-Kontos hat Einsätze auf zwei Kalendertagen (nachgezählt per SQL über
  `CONVERT_TZ`: 0 von 20 Tagen). Der Fall wurde **gebaut** — die Antwort von
  `api/day.php` abgefangen und sechs Einsätze auf 21:10 bis 02:40 gesetzt.
  Das misst die Sortierung im Browser; es misst **nicht**, ob der Server
  `start_sort` für einen echten Nachtdienst richtig rechnet. Die drei Zeilen
  sind `fmt_local($m['started_at'], 'Y-m-d H:i')` und wurden gelesen, nicht
  gefahren.
- **Der Bildvergleich ist auf dem Demo-Konto unbrauchbar** (Countdown im
  Banner; siehe J0). Was trägt, ist der Formvergleich und die
  DOM-Messung.
- **Die Kachelform wurde an zwei Seiten geprüft, nicht an allen.**
  Zeitraumübersicht und Suche unter 420 px; die Tagesübersicht ebenso. Die
  Kachel selbst hat sich nicht geändert — `EdMissionTable.kachel()` baute sie
  schon vorher auf allen drei Seiten.

### Was maschinell geprüft wurde, mit Mittel **und** Zahl

**Die Abnahmezahlen, gezählt über den Quelltext (`grep -c`, vorher gegen
`HEAD`):**

| | vorher | nachher |
|---|---|---|
| `tr.innerHTML` in `index.php` | 1 | **0** |
| Spaltenlisten für Einsatztabellen | 4 | **1** |
| Sortierblatt-Erzeuger (`blatt-zeile'`) | 3 (je eine in index/suche/zeitraum) | **1** (im Modul) |

**Formvergleich, 56 Seiten** (6 Seitentypen × 8 Breiten): roh **15
abweichend, 615 Zeilen**. Die Zahl ist irreführend — der Tabellenkopf ging
von zwei Zeilen auf eine, und der Zeilenvergleich zählt alles darunter mit.
Deshalb **zeilenweise klassifiziert**: nach Abzug der zeitabhängigen Zeilen
(Demo-Countdown, Versionszeile) und der einen beabsichtigten Kopfzeile sind
**0 von 56** Seiten noch abweichend. Suche und Zeitraumübersicht:
**0 / 0 / 0**.

**Nachtdienst** (gebaut, s. o.):

| | Reihenfolge |
|---|---|
| **mit** `start_sort` | 21:10 · 22:30 · 23:50 · 00:20 · 01:10 · 02:40 |
| **ohne** (das Verhalten vor diesem Paket) | 00:20 · 01:10 · 02:40 · 21:10 · 22:30 · 23:50 |

**Gleichstände** (Tag 364, zwei Einsätze mit 51 min): aufsteigend
`3:51min 4:51min`, absteigend ebenfalls `3:51min 4:51min` — die
Einsatznummer ordnet den Gleichstand und kehrt ihn **nicht** um.
Zurück auf „Beginn" ergibt wieder 1–6.

**Ausrichtung**, über `getComputedStyle` abgelesen statt aus dem Bild
geschätzt: `no` center (unverändert), `start` center → **left**, `age`
center → **right**, `dur` right, Haken center, Reihenfolge
**`winch bw sec`**.

**Mobiles Sortierblatt**, 420 px: Auf Zeitraumübersicht und Suche ändert ein
Klick jetzt Beschriftung **und** Kachelreihenfolge (vorher nur die
Beschriftung — und auch die nur, weil die Seite sie selbst schrieb). Auf der
Tagesübersicht: Kacheln `07:52 10:11 12:16 14:58 16:02 19:40` → nach „Dauer"
`19:40 12:16 14:58 07:52 16:02 10:11`, was der Dauerreihenfolge 6·3·4·1·5·2
entspricht.

**Die übrigen Prüfmittel:** `php -l` **0 Fehler**, `node --check` ok,
Register **38 Zeilen / 0 über der Decke**, Wortliste **0/0/0**, CSP **0**,
Kettenaufrufe **47/0/0**, Bilderlauf **56 Bilder, 0 Überlauf,
0 Konsolenfehler, 0 falsche Knopfhöhen**.

**Vollständigkeit 397 → 396, und die eine Zahl ist nachgegangen worden.**
Eine Zahl, die sich ändert, ist erst dann in Ordnung, wenn man weiß, warum.
Die Differenz liegt in der Zeile „Unicode-Zeichen als Symbol im Markup"
(329 → 328), und zwar in `index.php` (16 → 15): Mit der alten
Zeilenerzeugung ist ein **Auslassungszeichen aus einem Kommentar**
verschwunden (`el.style.x = …`). Keine Regel ist verlorengegangen, keine
Klasse ohne Regel entstanden — `missiontable.js` steht unverändert bei 2,
und der Rest des Berichts ist Zeile für Zeile gleich.

### Ein Befund aus dem Prüflauf selbst

**Eine Zwischenfassung hätte still geschadet, und der Bildvergleich hat sie
gefunden.** Die Datumsspalte bekam im Modul ein `nurWenn` — „nur zeigen,
wenn es mehrere Tage gibt" —, damit sie auf der Tagesübersicht von selbst
wegfällt. Im Januar des Referenzbestands liegen beide Einsätze auf
**demselben** Tag: Die **Zeitraumübersicht** verlor dort ihre Datumsspalte,
während sie weiter nach ihr sortierte, und die Beschriftung las sich
„älteste zuerst" ohne Spaltennamen. Die Spalte steht jetzt in `opts.ohne`
von `index.php`.

**Die Lehre gehört ins Dokument, nicht nur in den Kommentar:** Eine Seite,
die eine Spalte nicht will, sagt es. Das Modul rät es nicht aus dem Bestand
— außer, wo das Raten harmlos ist (ein Artzeichen, das sich nie ändert, sagt
nichts; ein Datum sagt, *welcher* Tag).

### Prüfliste — was **die Auftraggeberin** noch tun muss

| # | Weg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **J-14** | **Tagesübersicht ab 768 px öffnen** und den Tabellenkopf lesen | „Sekundärtransport" steht als **ein** Wort in einer Zeile (trennt nur bei Platzmangel, dann mit Bindestrich) | „Sekundär" und „Transport" untereinander ohne Bindestrich — dann greift der alte Kopf noch |
| **J-15** | **Dieselbe Tabelle:** Spalte „Alter" ansehen | Die Zahlen stehen **rechtsbündig**, wie Dauer und km | Sie stehen mittig — dann kommt die Zeile nicht aus dem Modul |
| **J-16** | **Einen Lufttag mit Windenfähigkeit öffnen** | Die Haken stehen in der Reihenfolge **Winde, Bergwacht, Sekundärtransport** | Umgekehrte Reihenfolge |
| **J-17** | **Auf einen Spaltenkopf klicken**, dann auf denselben noch einmal | Die Richtung kehrt sich um, der Pfeil dreht sich; bei zwei Einsätzen **gleicher Dauer** bleibt der mit der kleineren Nr. oben — in **beiden** Richtungen | Die beiden tauschen beim zweiten Klick die Plätze |
| **J-18** | **Unter 720 px** (Handy) auf „Sortieren" tippen und „Dauer" wählen | Das Blatt schließt sich, und die **Kacheln ordnen sich um** | Das Blatt schließt sich, die Kacheln bleiben, wie sie waren — dann zeichnet `setSort()` nicht |
| **J-19** | **Dasselbe in Suche und Zeitraumübersicht** | Ebenso — und die Beschriftung neben dem Symbol nennt die neue Spalte („Dauer, aufsteigend") | Nur die Beschriftung ändert sich |
| **J-20** | **Sortierblatt öffnen** und die Einträge lesen | „Sekundärtransport" und „Fehleinsatz" als Wort | `Sekundär&shy;transport` mit sichtbarem `&shy;` |
| **J-21** | **Einen Dienst über Mitternacht anlegen** (ein Einsatz um 23:50, einer um 01:10) und nach „Beginn" aufsteigend sortieren | 23:50 steht **vor** 01:10 | 01:10 steht oben — dann fehlt `start_sort` in der Antwort |
| **J-22** | **Zeitraumübersicht, ein Monat mit Einsätzen an nur einem Tag** | Die Spalte **„Datum"** steht trotzdem da | Sie fehlt, und die Beschriftung sagt nur „älteste zuerst" |
| **J-23** | **Tagesübersicht:** Prüfen, dass **kein** „Fehleinsatz" und **keine** „Art"- und **keine** „Datum"-Spalte erscheint | Neun bzw. elf Spalten, keine davon | Eine der drei taucht auf — dann greift `ohne` nicht oder eine Seite setzt den Vorspann nicht |


---

## K. Abschluss (AP10, 22.09.2026)

**Dieses Dokument bleibt.** Das Konzept `Konzept-Zentralisierung.md` ist mit
dem Abschluss gelöscht (`CLAUDE.md` 7, K9) — die Git-Historie behält es, und
die Zusammenfassung steht im **Rahmenplan Abschnitt 8**. Das Prüfdokument
bleibt, bis seine Prüfliste abgehakt ist, und wird dann ebenso gelöscht.

**Die Prüfliste dieses Dokuments ist die offene Arbeit.** Sie umfasst
A-1 bis I-14 aus den früheren Paketen und **J-1 bis J-23** aus AP9:
J-1 bis J-10 zur Fähigkeitsregel, J-11 bis J-13 zu den Suchfiltern,
J-14 bis J-23 zum Generatorzusammenzug. Keiner der Punkte ist maschinell
nachholbar — sie alle verlangen einen Blick in den Browser oder einen
Bestand, den der Referenzdatensatz nicht hat.

**Zwei Punkte hängen an Daten, die es noch nicht gibt**, und das steht hier,
damit niemand sie für „geht nicht" hält:

- **J-11** (Fähigkeit eingerichtet, nie benutzt) verlangt ein
  Rettungsmittel mit Windenfähigkeit ohne dokumentierten Windeneinsatz.
- **J-21** (Nachtdienst) verlangt einen Dienst über Mitternacht. Der
  Referenzdatensatz hat keinen — **Backlog Nr. 275** schließt die Lücke mit
  zehn Einsätzen über Nacht im Demo-Konto. Bis dahin ist der Fall nur über
  eine abgefangene Antwort zu prüfen, und das misst den Browser, nicht die
  Anlage.

**Was der Abschluss an Steuerungsdokumenten nachgezogen hat:**
Rahmenplan **Fassung 103** (Erledigt-Zeile in Abschnitt 8, Fahrplanzeile 15
auf „erledigt", Abschnitt 3 als Protokoll gekennzeichnet, Nr. 57 und Nr. 202
in Abschnitt 5); Backlog **Nr. 57 nach *Erledigt***, **Nr. 198** mit dem
Nachtrag zu E-ZE-31, **Nr. 275** neu; die **Andockstellen für 10c** aus dem
gelöschten Konzept in `Konzept-P5c-Rollen-Sicherheit-Betriebslage.md`
übernommen und gegen den gebauten Stand nachgezogen.

**Prüfzahlen des Abschlusspakets** (nur `docs/` und `tools/` — keine
Versionsstufe, `CLAUDE.md` 2.1): Backlog „keine Nummer zweimal"
**0 doppelte bei 271 Nummernzeilen**, Wortliste **0/0/0**, Kettenaufrufe
**47/0/0**, Register **38 Zeilen / 0 über der Decke**, Vollständigkeit
**396**.
