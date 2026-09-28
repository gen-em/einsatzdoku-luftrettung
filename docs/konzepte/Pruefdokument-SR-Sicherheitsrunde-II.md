# Prüfdokument SR — Sicherheitsrunde II (Schritt 18)

*Gehört zu `Konzept-SR-Sicherheitsrunde-II.md`. Beantwortet „was muss **ich**
noch tun?" — das Protokoll „ist es belegt?" steht im Statusblock des
Konzepts. Angelegt am 27.09.2026 mit dem Konzept (Fable); die Umsetzung
füllt es je Paket mit Mittel **und** Zahl. Stand: Konzeptphase — nichts
gebaut; geprüft ist die Buchführung (Spanne, Fahrplanzeile, Konzept) am
Stand R4-10 des 17er-Zweigs, dann mit der Nachfassung vom 27.09.2026
(SR-09 Passkeys, Nr. 350), zuletzt am 28.09.2026 mit dem Merge von `main`
(17 gemergt) und dem Konzept-PR. **Seit dem 28.09.2026 füllt es die
Umsetzung** (Zweig `claude/pr95-stufe-18-ztactt`, gestapelt auf PR #95,
E-SR-38): **SR-01 gebaut** (Web 21.7.0). Dieses Dokument bleibt, bis seine
Prüfliste abgehakt ist (K9); das Konzept wird nach der Freigabe des
Abschlusses gelöscht.*

---

## 1. Was nicht geprüft werden konnte — und warum

Steht vor allem anderen. Was dazukommt, gehört hierher — an den Anfang.

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **SR-01: Die lesenden Seiten einzeln.** | Die Sitzungsprobe misst F-SR-15 am **Notfallblatt** (Kontoadresse da / nicht da). Handbuch, Rechtstexte und Codeblatt gehen durch denselben Aufruf `sitzung_starten('lesend')`, sind aber nicht einzeln angefragt — ihr Merkmal „angemeldet" ist der Kopf, und den misst keine Probe am Text. | P-SR-12 (erweitert um das Handbuch). |
| **SR-01: Das einmalige Neuanmelden nach dem Ausrollen.** | Örtlich ist jede Sitzung jünger als der Umbau; der Fall „Sitzung von vor 21.7.0" ist nachgestellt (Sitzungsprobe Teil 4: Datei ohne Hash), nicht auf einer Anlage mit echten offenen Sitzungen erlebt. | P-SR-04 auf Staging. |
| **Der Befund ist eine Lesung, keine Messung an der Anlage.** | Keine Probe gefahren, kein Prüfstand für den Befund (Konzept 2.1); die örtliche Anlage lief nur für den Prüfbericht dieses Commits. Jede Trefferzahl ist ein `grep` am 27.09.2026; Aussagen wie „`anteil_wechseln()` fasst die Blatt-Marke nicht an" oder „`user_id` wird an einer Stelle gesetzt" stammen aus dem Code, nicht aus einem Lauf. | Die Umsetzung misst je Paket zuerst das Ausgangsmaß und trägt die Zahl hier ein; weicht sie vom Konzept ab, ist das ein Befund, kein Fehler des Konzepts. |
| ~~**Der Baum ist der 17er-Zweig, nicht `main`.**~~ **Erledigt 28.09.2026:** 17 ist gemergt (PR #94, `f4ac705`), dieser Zweig hat `main` aufgenommen. | Gelesen wurde `26b4761` (R4-10). Gemessen danach: `git log 26b4761..f4ac705` über die SR-Dateien — **acht bewegt** (Konzept 2.4), darunter R4-14 (Demo-Änderungsmarke in `ingest.php`, `auth_guard.php`) und R4-15 (`days.created_at`, Migration). | Der Befund (Konzept 2.3) ist eine Lesung des Stands `26b4761`; die Umsetzung liest die acht Dateien vor SR-01 noch einmal. |
| ~~**Der Nummernriegel ist auf diesem Zweig rot — mit fremden Nummern.**~~ **Erledigt 28.09.2026:** nach dem Merge von 17 meldet `nummern.py` 0 (Abschnitt 2), der Prüfbericht des Merge-Commits trägt die Zeile grün. | Bis dahin „legte" der gestapelte Zweig die Nummern 340 bis 343 von 17 mit an (F-SR-12). | Stufe 1 am Kopf des Konzept-PR: `nummern` grün. |
| **Zwei Browser-Cookies in echten Browsern.** | Bindungs- und Gerätecookie lassen sich örtlich nur in Chromium (Bilderlauf, Bedienprobe) messen; Firefox und WebKit nur von Hand mit `--motor` (Nr. 300). Wie ein Browser zwei `SameSite=Strict`-Cookies bei einer Weiterleitung nach 303 behandelt, entscheidet der Browser. | P-SR-07 in Firefox; Bedienweg `zweitfaktor.mjs` mit `--motor firefox` vor dem PR (SR-02). |
| **30 Tage.** | Der Ablauf des Gerätecookies lässt sich nicht abwarten; die Probe stellt `gueltig_bis` und misst den Ablauf am Server, nicht am Browser. | Zweitfaktorprobe (SR-02) mit gestelltem Datum; die Betreiberin sieht den echten Ablauf frühestens am 31. Tag. |
| **Ein echtes Sicherungsziel während des Schlüsselwechsels.** | Die Arbeitsumgebung fährt kein Ziel an, das außer Haus schreibt (`Sandbox-Setup.md` 6); die Versandprobe hat Gegenstellen im Container. Ob die alte Kopie auf dem Ziel bleibt und die neue dazukommt, zeigt nur eine Anlage mit Ziel. | P-SR-08 auf Staging (eigenes SFTP-Ziel, 6a Schritt 9). |
| **Das Webspace-Backup des Hosters.** | Der Fall, gegen den Nr. 242 schützt — eine Sitzungsdatei aus einem fremden Backup —, lässt sich nur nachstellen (eine Datei ohne Bindung), nicht herstellen. | Sitzungsprobe, Fall „Datei ohne Bindung" (SR-01). |
| **Der Deadlock unter Produktivlast.** | Die Verbindungsprobe stellt zwanzig gleichzeitige Pakete gegen den eingebauten PHP-Server mit Arbeitern her (`tools/proben/verbindung/`); auf Staging fehlt der Wurzelzugang zur Datenbank, Stufe 2 misst es nicht. | P-SR-11: die Zahl aus der örtlichen Probe; auf Produktiv zeigt es der Reiter System (`gedraengel_vermerken()` schreibt ins Fehlerprotokoll des Webspace, Nr. 248). |
| **Der Notzugang von der Hoster-Seite aus.** | Die Nachweisdatei per FTP anlegen, den Wert aus dem Datenbankwerkzeug abschreiben, das Passwort eingeben — das ist Bedienung an einer echten Anlage mit zwei Hoster-Werkzeugen. | P-SR-10 auf Staging. |
| **Die Dauer des Proof-of-Work auf echten Handys.** | Der Prüfstand misst Chromium, ungedrosselt und mit vierfacher CPU-Drosselung; ein altes Diensthandy ist ein anderer Prozessor mit anderem Browser. Die Konstante `POW_BITS` wird nach der Drosselung gewählt und ist eine Näherung. | P-SR-14 auf dem alten Diensthandy; ist es dort spürbar länger als drei Sekunden, ist die Bitzahl um eins zu senken (Halbierung), und der Wert steht dann hier. |
| **Echte Authenticatoren.** | Der Bedienweg zu SR-09 fährt den virtuellen Authenticator Chromiums (CDP, CTAP2-Modell); ein Handy mit Biometrie, Windows Hello, ein Hardware-Schlüssel, der iCloud-Schlüsselbund sowie Firefox und Safari lassen sich örtlich nicht fahren — Playwright hat den virtuellen Authenticator nur in Chromium. RS256 misst allein die Probe mit einem selbst erzeugten Schlüssel; einen echten RS256-Authenticator (ältere Windows-Hello-Anmeldungen) gibt es im Container nicht. | P-SR-16 auf Staging mit je einem echten Gerät der Arten, die die Betreiberin hat — iPhone/Safari ausdrücklich. |
| **Synchronisierte Passkeys.** | Ob ein bei Apple oder Google synchronisierter Passkey auf einem zweiten Gerät anmeldet und dabei den Zähler 0 meldet (E-SR-33), zeigt nur ein zweites echtes Gerät. | P-SR-16, zweites Gerät. |

## 2. Maschinell geprüft — Konzeptphase (SR-00)

| Schritt | Mittel | Gegenstand | Zahl |
|---|---|---|---|
| SR-00 | `python3 tools/steuerung/decken.py` | 20 Decken der Steuerungsdokumente, nach dem Schreiben der Fahrplanzeile und der Spanne | **20 Decken, 0 gerissen** |
| SR-00 | `python3 tools/steuerung/uebersicht.py --pruefen`, `--ziel 18` | Kopfzeilen des Backlogs; die Punkte mit Ziel 18 | **77 offene Einträge, 0 ohne Grammatik, 0 ohne gültiges Ziel; 8 mit Ziel 18** (210, 228, 232, 233, 242, 247, 249, 251) |
| SR-00 | `python3 tools/steuerung/nummern.py --ohne-holen` | neue Nummern gegen `origin/main` und die Remote-Zweige | **4 Überschneidungen — 340 bis 343, alle aus 17** (F-SR-12); SR vergibt im Konzept keine Nummer, die Spanne 350–359 steht im Kopf. Grün erst nach dem Merge von 17 |
| SR-00 | `grep -rn "E-SR-\|Konzept SR" docs/` vor dem Anlegen | Ist das Kürzel frei? | **0 Treffer** (27.09.2026) |
| Befund | `grep -rn "sk_versiegeln(" server/ --include=*.php` | Zwecke des Serverschlüssels gegen `Technik.md` 4.97c | **6 Zwecke in 5 Dateien** (`adminbackup_lib`, `sicherungsziel_lib`, `totp_lib`, `protokoll_archiv_lib` je `sk_versiegeln()`; `komplett_lib` mit dem rohen Schlüssel) gegen **4** in der Tabelle — F-SR-01 |
| Befund | `grep -rn "\$_SESSION\['user_id'\] *=" server/` | Wo eine Sitzung angemeldet wird | **1** (`anmeldung_vollenden()`, `login.php`) |
| Befund | `grep -rn "session_regenerate_id\|setcookie(" server/ --include=*.php` | Stellen, die SR-01 kennen muss | **2 + 2** (`login.php`, `pw_handling.php`; `session_lib.php` beide Enden) |
| Befund | `grep -rn "install-nachweis-\|nachweis" server/install.php server/wiederherstellen.php` | Verbraucher der Nachweis-Hilfe (R83) | **2 Dateien** — SR-04 wäre der dritte |
| SR-00 | `bash tools/pruefstand/pruefen.sh --basis origin/claude/schritt-17-konzept-mockups-q0yjcm` (Stufe klein: Riegel, `steuerung`, `nummern`) | der Baum des Konzept-Commits gegen die Basis, auf der er sitzt | **21 grün, 1 rot (`nummern`, F-SR-12), 0 nicht gemessen, 39 s**; Bericht in der Commit-Nachricht; nichts in den Baum geschrieben |
| SR-00 Nachfassung | `python3 tools/steuerung/decken.py` | 20 Decken nach der Fahrplanzeile (Passkeys), der Verlaufszeile 143 (damals 141), Nr. 146 und 350 | **20 Decken, 0 gerissen** (nach einem ersten Lauf mit 1 gerissen: die Status-Zelle der Fahrplanzeile 18 lag über 240 Zeichen — gekürzt) |
| SR-00 Nachfassung | `python3 tools/steuerung/uebersicht.py --pruefen`, `--ziel 18` | Kopfzeilen; die Punkte mit Ziel 18 nach Nr. 350 | **78 offene Einträge, 0 ohne Grammatik, 0 ohne gültiges Ziel; 9 mit Ziel 18** (210, 228, 232, 233, 242, 247, 249, 251, 350) |
| SR-00 Nachfassung | `python3 tools/steuerung/nummern.py --ohne-holen` | Nr. 350 gegen `origin/main` und die Remote-Zweige | **5 neue Nummern (340 bis 343 aus 17, 350), 4 Überschneidungen — alle 17** (F-SR-12); **350 ohne Kollision** |
| SR-00 Nachfassung | `bash tools/pruefstand/pruefen.sh --basis origin/claude/schritt-17-konzept-mockups-q0yjcm` | der Baum des Nachfassungs-Commits gegen die Basis, auf der er sitzt | **21 grün, 1 rot (`nummern`, F-SR-12), 0 nicht gemessen, 46 s**; Bericht in der Commit-Nachricht; nichts in den Baum geschrieben (ein zweiter Lauf über den Baum mit dieser Zeile liefert den Bericht des Commits) |
| Befund (Nachfassung) | `ls server/vendor/phpseclib3/Crypt/`; `grep -n "PublicKeyLoader" server/rueckweg_lib.php` | Bausteine für WebAuthn im Haus (F-SR-13) | **`EC/`, `EC.php`, `RSA/`, `RSA.php`, `PublicKeyLoader.php` vendoriert; `rw_pruefen()` lädt EC-Schlüssel über `PublicKeyLoader`** — kein Fremdbestandteil nötig |
| SR-00 Antworten | `python3 tools/steuerung/decken.py`; `uebersicht.py --pruefen`, `--ziel 18`; `nummern.py --ohne-holen` | nach E-SR-35, -36, Nr. 351, Rahmenplan 144 (bis zum Merge von 17 als 142 gezählt) | **20 Decken, 0 gerissen**; **79 offene Einträge, 0 Kopfzeilen ohne Grammatik, 0 ohne gültiges Ziel; 9 mit Ziel 18** (351 hängt an `nach v1.0`); **6 (340, 341, 342, 343, 350, 351) neue Nummern, 4 Überschneidungen — alle 17** (F-SR-12); 350 und 351 ohne Kollision |
| SR-00 Antworten | `bash tools/pruefstand/pruefen.sh --basis origin/claude/schritt-17-konzept-mockups-q0yjcm` | der Baum des Antwort-Commits | **21 grün, 1 rot (`nummern`, F-SR-12), 0 nicht gemessen, 43 s**; Bericht in der Commit-Nachricht (zweiter Lauf über den Baum mit dieser Zeile); beschriftet „neben", weil der 17er-Zweig inzwischen Web 21.4.1 trägt und die Stufenregel die Fassungen vergleicht — gemessen wurde dasselbe wie in klein |
| SR-00 Merge | `git merge --no-commit origin/main` (`Pruefablauf.md` 5.3), dann `decken.py`, `uebersicht.py --pruefen`, `--ziel 18`, `nummern.py --ohne-holen` | drei Konflikte (Rahmenplan, Verlauf, Backlog) gelöst; die Steuerungsdokumente danach | **21 Decken, 0 gerissen** (eine neue Decke seit 17: `backlog-listenstart`); **66 offene Einträge, 0 Kopfzeilen ohne Grammatik, 0 ohne gültiges Ziel; 11 mit Ziel 18** (210, 228, 232, 233, 242, 247, 249, 250, 251, 344, 350); vor dem Merge-Commit **4 Scheinüberschneidungen** — 344 bis 347, die Nummern, die `main` mitbrachte (F-SR-14, Nr. 352); nach dem Commit **3 neue Nummern (350, 351, 352), 0 Überschneidungen** |
| SR-00 Merge | `git log 26b4761..f4ac705 -- server/<SR-Dateien>` | was 17 nach R4-10 an den Dateien der Sperrliste geändert hat | **5 Commits (R4-11 bis R4-15), 8 Dateien, 257 Zeilen dazu, 84 weg** — Konzept 2.4 |
| SR-00 Merge | `bash tools/pruefstand/pruefen.sh` (Basis `origin/main`, nach `hochfahren.sh --neu` wegen der Migration aus 17) | der Baum des Merge-Commits | **22 grün, 0 rot, 0 nicht gemessen, 71 s** (Stufe klein, kein Versionssprung; `nummern` grün); Bericht in der Nachricht des Folge-Commits — der Merge-Commit trägt keinen (F-SR-14); die Zeile hier stammt aus dem ersten Lauf, der Bericht aus dem zweiten über den Baum mit dieser Zeile |
| SR-00 | `bash tools/pruefstand/pruefen.sh` (Basis `origin/main`) | derselbe Baum — misst den 17er-Unterschied mit (R4-01 bis R4-10) | **35 grün, 2 rot, 2 nicht gemessen, 883 s**: rot `nummern` (F-SR-12) und `stilvergleich` (eine ungeplante Signatur an `login.php`, `input <label>`: `outline` — aus 17, nicht aus SR); nicht gemessen `android-bau` (Ausbaustufe `android` fehlt) und `schemaprobe` (Modul `plattform` fehlt). Die Bedienprobe schrieb `tools/bedienprobe/ausgabe/` neu; zurückgesetzt vor dem Commit |
| SR-01 Ausgang | `tools/sandbox/aufbauen.sh web` + `hochfahren.sh --neu` | örtliche Anlage vor dem ersten Paket | **Web 21.6.1, HTTP 200, 0 Migrationen offen, zwei Prüfkonten mit Zweitfaktor**; nach dem Code **Web 21.7.0** (dieselbe Anlage) |
| SR-01 | `php tools/proben/sitzung/probe.php` (neu) | halber Stand und Anmeldung gebunden, Kennung ohne / mit falschem / mit formlosem Cookie, Datei ohne Hash, Notfallblatt, Abmelden, Text zum Grund | **25 ok, 0 fehlen** |
| SR-01 Gegenprobe | dieselbe Probe mit herausgenommenen Prüfungen in `auth_guard.php`, `login.php` und `sitzung_starten('lesend')` (`if (false && …)`), danach zurückgestellt | findet die Probe den Fehler? | **14 ok, 11 fehlen**; nach dem Zurückstellen `cmp` gleich, 0 Rest |
| SR-01 | `php tools/quelltext/sitzungshaertung.php`, `--selbstprobe` | ein `session_start()`, gehärtet; **neu (e):** `setcookie()` nur in `sitzung_lib.php` und `session_lib.php` | **153 Dateien, 1 Aufruf, 4 `setcookie()`, 0 Befunde; Selbstprobe 18 von 18** (11 + 6 neue + „kein Aufruf") |
| SR-01 Gegenprobe | eine Zeile `setcookie("PROBE", "x");` in den PHP-Teil von `login.php`, danach zurückgestellt | greift (e) am echten Baum? | **1 Befund** mit Ort `server/login.php:3`; danach 0. *Der erste Versuch hing die Zeile hinter `?>` an und maß nichts — HTML, kein Token.* |
| SR-01 | `grep -rn "setcookie(" server/ --include=*.php` | Abnahme des Konzepts | **4 Aufrufe, alle in den zwei Dateien** (`sitzung_lib.php` 2, `session_lib.php` 2) |
| SR-01 | `php tools/proben/zweitfaktor/probe.php` (Behälter statt eines Cookies) | Code-Schritt über HTTP mit Bindung | **63 ok, 0 fehlen** |
| SR-01 | `bash tools/proben/rueckweg/probe.sh` (gefälschter halber Stand mit Hash und Cookie) | Rückweg über HTTP und im Browser | **50 ok, 0 fehlen** (Server) · **23 ok, 0 fehlen** (Chromium, zwei echte Cookies) |
| SR-01 | `php tools/proben/rollen/probe.php` (gebundene Sitzungen) | Matrix `Technik.md` 4.99p | **480 Erwartungen, 0 nicht erfüllt** |
| SR-01 | `php tools/proben/wartung/probe.php` (gebundene Sitzungen) | Wartungsmodus und Torwächter | **69 Erwartungen, 0 nicht erfüllt** |
| SR-01 | `php tools/proben/protokoll/probe.php` (gebundene Sitzung; Bindungswert unter den Marken) | Archiv, Fehlerprotokoll | **36 Erwartungen, 0 nicht erfüllt**; Marken im Fehlerprotokoll **0 gefunden**, jetzt mit dem Bindungswert |
| SR-01 | `bash tools/quelltext/pruefen.sh bestand`; `python3 tools/kettenaufrufe/pruefen.py` | Form der neuen Probe (Ordner, Anlass, `RUF`, `pruefablauf.json`, Tabelle in `Pruefablauf.md` 4) | **0 Befunde**; kein Aufruf widerspricht seiner Schnittstelle (die bekannte Zeile `android-stroeme` „ungeprüft" steht unverändert) |
| SR-01 | `python3 tools/pruefstand/auswahl.py --selbstprobe` | `pruefablauf.json` nach dem neuen Muster `sitzungsbindung` | **39 Lagen, 0 Fehlschläge** |
| SR-01 | `bash tools/proben/proben.sh alle` (nach Code und Doku) | alle 25 Proben der Sammlung, nacheinander gegen die örtliche Anlage | **24 von 25 grün.** Rot: die **Jobprobe**, ein Fall — „vorher 2 fremde Waisen in der Anlage" (**Nr. 347**, bekannt seit R4-27, drittes Auftreten; die Probenfolge davor steht jetzt im Eintrag). Einzeln danach **36 Erwartungen, 0 nicht erfüllt**; in der Anlage 0 Waisen (der Job hatte sie im Lauf abgeräumt). Nicht SR-01: Die Bindung berührt weder Spuren noch den Waisenjob. |
| SR-01 | `decken.py`; `uebersicht.py --pruefen`, `--ziel 18`; `nummern.py --ohne-holen` | Steuerung nach Nr. 242, 251 und Fassung 146 | **21 Decken, 0 gerissen; 64 offen, 0 ohne Grammatik, 0 ohne Ziel; Ziel 18: 9** (vorher 11); **3 neue Nummern (350–352), 0 Überschneidungen** — das Stapeln auf #95 misst keine Scheinkollision (E-SR-38) |

## 3. Im Browser geprüft

**Konzeptphase:** nichts — sie ändert keine Seite. Die Anlage lief (HTTP 200
auf `login.php`, Fassung 21.1.8, 0 Migrationen offen, zwei Prüfkonten) nur
für den Prüfstand.

**SR-01:** Der Browserteil der Rückwegprobe (`probe.mjs`, Chromium über
Playwright) meldet sich mit echten Cookies an — Passwort, halber Stand,
Code-Schritt, Schlüsselschritt, Einrichtungstor — und ist mit beiden
Cookies grün (23 ok). Firefox und WebKit nicht gefahren (Abschnitt 1,
„Zwei Browser-Cookies in echten Browsern"). Neue Oberfläche gibt es in
SR-01 nicht: nur ein Text auf der Anmeldeseite und einer auf der
Abmeldeseite.

## 4. Prüfliste für die Betreiberin

Je Punkt: Bedienweg, erwartetes Ergebnis, woran ein Scheitern zu erkennen
ist. Die Umsetzung hängt je Paket ihre Punkte an (P-SR-13 ff.).

| Nr. | Bedienweg | Erwartung | Scheitern |
|---|---|---|---|
| P-SR-01 | ~~Konzept Abschnitt 3.2 lesen; Q-SR-01 bis -09 beantworten.~~ **Erledigt 27.09.2026:** Q-SR-01 bis -11 in der Konzeptsitzung beantwortet (Klickrunde), festgehalten als E-SR-17 bis -26. | — | — |
| P-SR-02 | ~~Freigabe des Konzepts.~~ **Erteilt 27.09.2026** (E-SR-27). ~~Der Weg auf `main`~~ **Erledigt 28.09.2026:** `main` nach `Pruefablauf.md` 5.3 aufgenommen (Merge-Commit mit Bericht), Konzept-PR gestellt. **Offen:** Stufe 1 am PR ansehen und mergen. | Stufe 1 grün am Kopf-Commit, auch `nummern`; nach dem Merge auf `main`: Fahrplanzeile 18 „freigegeben", `uebersicht.py --ziel 18` 11. | Stufe 1 rot am Kopf-Commit (Bericht fehlt, falscher Baum) — kein Merge, Instanz beauftragen. |
| P-SR-03 | Nach beiden Merges: Umsetzungsinstanz mit Opus auf eigenem Zweig von `main` starten (Reihenfolge E-SR-14: SR-01, -02, -07, -09, -05, -03, -04, -08, -06). Nach SR-03 hält sie an (H-SR-06): dann eine Fable-Instanz zur Gegenlesung von SR-03 starten, danach Opus weiter; nach SR-09 ebenso (H-SR-08, E-SR-36). | Erster Commit `SR-01: …`; Zweig nach jedem Paket gepusht; Statusblock fortgeschrieben; nach SR-03 (und SR-09) eine Meldung „warte auf Gegenlesung". | Ein Paket ohne Push, ein Statusblock ohne Fortschreibung; SR-04 (oder SR-05) beginnt ohne Gegenlesung. |
| P-SR-04 | **Vor dem Deploy von SR-01** eine Ankündigung setzen (Betrieb → Servereinstellungen, Karte „Ankündigung"): „Nach dem Update einmal neu anmelden." | Nach dem Deploy landet jede Angemeldete auf der Anmeldeseite mit dem Satz zum Grund; Uhr und Handy puffern. | Die Anmeldeseite ohne Erklärung; oder eine Sitzung von vorher läuft weiter (dann ist E-SR-04 nicht gebaut). |
| P-SR-05 | Die Bilder des Bilderlaufs ansehen, die die Umsetzung hier verlinkt (Code-Schritt mit Haken, Karte „Zweitfaktor" mit Zeile, Karte „Schlüssel des Servers" in drei Lagen, Seite des Notzugangs) — statt Mockups (Q-SR-08). | Vorhandene Bausteine, nichts Neues; sonst vor dem Paket sagen. | Ein Element, das in `Design.md` 9 nicht steht. |
| P-SR-06 | Nach dem Deploy von SR-02 (Migration `vertraute_geraete`): als Administratorin `update.php` aufrufen, Betrieb → Updates. | Migration gelaufen; Wartungsmodus geht aus. | Wartungsmodus bleibt an; Statuszeile nennt eine ausstehende Migration. |
| P-SR-07 | Auf Staging, in Chromium **und** Firefox: anmelden mit Code, Haken „Dieses Gerät n Tage merken" (n ist die Dauer deiner Rollengruppe aus Betrieb → Servereinstellungen, Karte „Anmeldung"); abmelden; anmelden. Dann Einstellungen → Profil, Karte „Zweitfaktor": Zahl, „Alle vergessen"; anmelden. Dann Passwort wechseln; anmelden. Zuletzt die Dauer der Verwaltungsrollen auf „aus" stellen; abmelden; anmelden. | Zweite Anmeldung ohne Code; nach „Alle vergessen" und nach dem Passwortwechsel wieder mit Code; mit „aus" kein Haken und Code bei jeder Anmeldung; Protokoll (Verwaltung) zeigt `zweitfaktor_geraet_gemerkt` und `zweitfaktor_geraete_vergessen`. | Ein Code, wo keiner sein sollte — oder keiner, wo einer sein muss (nach „Alle vergessen", nach dem Passwortwechsel, bei „aus"). |
| P-SR-08 | Auf Staging (eigenes SFTP-Ziel, mindestens ein Adminpaket, ein Protokoll-Archiv und ein Komplett-Stand vorhanden): Betrieb → Servereinstellungen, „Serverschlüssel wechseln". Schlüsselblatt drucken. Abmelden, anmelden. „Jetzt weiterarbeiten" bis „0 von n offen". „Jetzt sichern" (Komplett). Dann „Alten Schlüssel entfernen". | Blatt mit **drei** Kacheln (Serverschlüssel, bisheriger, Anteil); nach dem Anmelden die Rückfrage mit dem Satz zur Rotation; Karte „noch n von n" → „0"; „Alten Schlüssel entfernen" verweigert **vor** dem frischen Stand mit Grund, danach erlaubt; Protokoll drei Arten; Mail bei Beginn und Abschluss; auf dem Ziel: alte Dateien unverändert, neue dazu (Archiv unter neuem Namen, frischer Stand). | Ein Stück, das sich danach nicht öffnen lässt (Zweitfaktor-Anmeldung, Ziel-Zugang „Verbindung prüfen", Konto-Backup einspielen, Archiv herunterladen); „Alten Schlüssel entfernen" ohne frischen Stand möglich; ein altes Blatt vernichtet, obwohl das Ziel noch Stände unter dem alten Schlüssel trägt (E-SR-10). |
| P-SR-09 | Nach P-SR-08 (freiwillig, mit der halbjährlichen Probe-Wiederherstellung, 6.3): einen Komplett-Stand **von vor** dem Wechsel vom Ziel holen und mit dem alten `server_key` vom alten Blatt in eine Wegwerf-Anlage einspielen. | Öffnet mit dem alten, nicht mit dem neuen Schlüssel — so sagt es das Runbook. | Öffnet mit keinem (dann ist der alte Wert falsch abgeschrieben — Kennung vergleichen). |
| P-SR-10 | Auf Staging (genau eine BetreiberIn mit Zweitfaktor, Codeblatt beiseite): `zweitfaktor_notweg.php` aufrufen, Dateinamen ablesen, Datei per FTP anlegen, im Datenbankwerkzeug des Hosters den Wert lesen (`SELECT v FROM app_state WHERE k = 'notzugang_geheim'`, so steht es im Runbook), Adresse, Wert und Passwort eingeben. Danach dasselbe noch einmal mit dem **alten** Wert, und dasselbe mit zwei BetreiberInnen. | Zweitfaktor aus, Mail im Postfach, Protokoll `totp_zurueckgesetzt` mit `weg = notweg`, Datei weg, in `app_state` ein neuer Wert, Anmeldung führt ins Einrichtungstor. Mit dem alten Wert und mit zwei BetreiberInnen: dieselbe Meldung wie ohne Datei, nichts geändert. | Eine Auskunft, ob es genau eine BetreiberIn gibt oder ob der Wert stimmte; eine Antwort, die ohne Datei oder mit falschem Wert anders aussieht oder länger dauert als mit. |
| P-SR-11 | Die Zahl aus SR-05 hier lesen: `php tools/proben/verbindung/probe.php --frei 20`. | **0 × 503, 20 von 20 Paketen, 0 Gedrängel** im Fehlerprotokoll (Sollwert Nr. 210). | Eine 503 oder ein `1213` im Protokoll — dann ist die Schleife zu kurz oder die `days`-Fortschreibung nicht hinter dem Commit. |
| P-SR-12 | Freiwillig, nach SR-01 auf Staging: angemeldet bleiben, in den Entwicklerwerkzeugen des Browsers das Bindungscookie (`EDBIND`) löschen, **zuerst das Handbuch** neu laden, dann eine andere Seite. | Das Handbuch zeigt den Kopf **ohne** Anmeldung (F-SR-15); die andere Seite führt auf die Anmeldeseite mit dem Satz zum Grund `bindung`; nach dem Anmelden läuft alles. | Das Handbuch zeigt noch den angemeldeten Kopf, oder die Seite lädt weiter, als wäre nichts. |
| P-SR-13 | Nach SR-07 auf Staging: über ein gemerktes Gerät anmelden (kein Code), dann Betrieb → Servereinstellungen → „Schlüsselblatt drucken". Code eingeben. Blatt ansehen, zurück, „Server-Anteil wechseln" **nicht** ausführen, nur die Karte ansehen. Dann in einem zweiten Reiter Verwaltung → ein Konto → Rolle ändern. | Vor dem Blatt erscheint der Code-Schritt („Code bestätigen"), danach das Blatt; die Rollenänderung im zweiten Reiter geht ohne neuen Code durch (Frist 15 Minuten läuft). Nach 15 Minuten fragt die nächste Handlung der Liste wieder. | Das Blatt erscheint ohne Code nach einer gemerkten Anmeldung; oder der Code wird bei jeder Handlung verlangt, obwohl der letzte keine 15 Minuten alt ist. |
| P-SR-14 | Nach SR-08 auf Staging, auf dem **alten Diensthandy** und am Rechner: Registrierungsseite öffnen (Betriebsart auf „Selbstregistrierung" oder „nach Prüfung" stellen, danach zurück), Adresse und Passwort tippen, absenden. Stoppuhr nur, falls der Knopf „Sicherheitsprüfung läuft …" zeigt. | Am Rechner keine spürbare Wartezeit; am alten Handy höchstens ein paar Sekunden, dann geht es weiter. Die Zahlen aus dem Prüfstand (Median, Höchstwert, `POW_BITS`) stehen in Abschnitt 2. | Der Knopf wartet am Handy länger als drei Sekunden (dann `POW_BITS` um eins senken, SR-08 sagt, wo), oder die Registrierung geht ohne die Kleinzeile durch, obwohl JavaScript aus ist (dann fehlt die Serverprüfung). |
| P-SR-15 | ~~Konzept 3.2 lesen; Q-SR-12 und Q-SR-13 beantworten (Klickrunde) — vor SR-09 (H-SR-07).~~ **Erledigt 27.09.2026:** beide wie empfohlen — Passkey nur zusätzlich (E-SR-35, dazu Nr. 351), Fable-Gegenlesung für SR-09 (E-SR-36). | — | — |
| P-SR-16 | Nach SR-09 auf Staging, mit einem echten Gerät je Art (Handy mit Biometrie, Rechner mit Windows Hello oder Touch ID, Hardware-Schlüssel — was da ist; iPhone/Safari ausdrücklich): Einstellungen → Profil, Karte „Zweitfaktor" → „Passkey hinzufügen" (Code bestätigen, Bezeichnung eingeben, Plattform-Dialog) → abmelden → anmelden → „Mit Passkey bestätigen". Dann ein zweites Gerät, das denselben synchronisierten Passkey hat. Dann „Entfernen"; anmelden. Zuletzt den Zweitfaktor ausschalten und wieder einschalten. | Der Passkey erscheint in der Liste mit Bezeichnung und Datum; die Anmeldung geht ohne Code durch; das zweite Gerät meldet an, ohne Protokolleintrag `passkey_zaehler`; nach „Entfernen" wieder Code; nach dem Ausschalten ist die Liste leer; Mail bei Anlegen und Entfernen; Protokoll zwei Arten. Ein Passkey von Staging tut auf Produktiv nichts — so soll es sein. | Der Knopf fehlt, obwohl ein Passkey da ist; die Anmeldung mit Passkey verlangt danach noch den Code; das zweite Gerät wird abgewiesen (Zähler — dann E-SR-33 falsch gebaut); nach dem Ausschalten steht noch ein Passkey in der Liste. |

## 5. Grenzen der benutzten Prüfmittel

- `decken.py`, `uebersicht.py` und `nummern.py` messen Form (Decken,
  Grammatik, Ziel, Kollision), nicht Inhalt: Eine Fahrplanzeile mit
  richtigem Vokabular und falschem Satz ist für sie grün.
- Der Prüfstand der Stufe klein fährt bei einer reinen `docs/`-Berührung
  nur die Riegel; er belegt, dass die Buchführung hält, nicht, dass der
  Befund stimmt.
- `grep` zählt Schreibweisen: Die Zahl der `sk_versiegeln()`-Aufrufer
  findet den rohen Schlüssel in `komplett_lib.php` nicht — er steht in der
  Tabelle, weil die Doku (`Backup-Format.md` 6.3, `kdf: null`) ihn nennt.
  Die Umsetzung baut das Inventar aus beidem und zählt am Ende unabhängig
  nach (F-SR-01).
- Die Sitzungsprobe (SR-01) misst den eingebauten PHP-Server; ob ein Hoster
  `session.save_path` oder Cookie-Direktiven übersteuert, zeigt nur Betrieb
  → Status (Zeile „Sitzungsablage") auf der Anlage selbst.
- Der virtuelle Authenticator Chromiums (SR-09) ist ein CTAP2-Modell im
  Browser: Er belegt die beiden Zeremonien und die Bindung an den Ursprung,
  nicht die Plattform-Dialoge, nicht die Synchronisation und nicht, was ein
  echter Authenticator in `authData` schreibt. Die Passkeyprobe misst
  `passkey_lib.php` mit selbst gebauten Vektoren — sie findet nur die
  Fälle, die jemand vorhergesehen hat (Q-SR-13).

## 6. Was aus der Runde offen bleibt

*(wird von der Umsetzung gefüllt: Punkte, die nach 18 ein neues Ziel
tragen — nach E-SR-25 nur Nr. 232; Nr. 228 wird gebaut (E-SR-26), Nr. 350
mit SR-09 (E-SR-29) —, und die Reste je Paket; Nr. 351 „Passkey als
einziger Zweitfaktor" steht schon mit `nach v1.0` da, E-SR-35)*
