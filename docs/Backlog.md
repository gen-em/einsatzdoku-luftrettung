# Gen-EM NAdoku — Backlog

Bewusst offene Punkte, einer je Nummer, jeder mit Kopfzeile. Erledigtes steht
wörtlich in `docs/Backlog-Erledigt.md` (Konzept SD, E-SD-18); welche Punkte
zu welchem Schritt gehören, zeigt `python3 tools/steuerung/uebersicht.py` —
die Zuordnung steht nur hier, in den Kopfzeilen.

**Nummern sind dauerhaft.** Verweise aus Code und Dokumentation nennen sie
(„Backlog Nr. 10"). Ein erledigter Punkt wird nicht gelöscht, sondern mit
seinem Text nach `Backlog-Erledigt.md` verschoben und behält seine Nummer;
neue Punkte hängen hinten an. **4, 5, 6 und 7 bleiben dauerhaft frei:** 4, 6
und 7 waren vergeben und sind ohne Eintrag verschwunden; 5 hat in keiner
Fassung dieser Datei je einen Eintrag getragen (nachgesehen über die ganze
Historie, E-SD-25 — der Changelog zu Web 7.2.0 behauptet anderes). Den
Werdegang der Nummernvergabe hält `Backlog-Erledigt.md`.

**Die Kopfzeile ist Pflicht** (E-SD-16), in genau dieser Form: `NNN.
**Titel.** · gehört zu: ZIEL · Stand: STAND · seit DD.MM.YYYY`. ZIEL ist eine
Kennung aus der Fahrplan-Tabelle des Rahmenplans (`17`, `12a`, `PK`, `Kette
II` …, bei Bedarf mit Paket wie `10c AP7`) oder eines von `nächste
Backlog-Runde` · `Zuarbeit` · `Pflegeaufgabe` · `nach v1.0`. STAND ist `offen`
· `teilweise` · `zurückgestellt` · `nur auf Anlass`. Was entschieden nicht
gebaut wird, wandert mit `verschieben.py --stand verworfen` nach
`Backlog-Erledigt.md` (E-BE-01); die Nummer bleibt auflösbar. Ein Eintrag hat höchstens **20 Zeilen**, die Kopfzeile
eingeschlossen (E-SD-03); jede Folgezeile beginnt mit **fünf Leerzeichen**
(E-SD-17 — mit vier rendert GitHub ab Nr. 100 einen Codeblock, Nr. 196). Vor
jeder Kopfzeile stehen `<!-- -->` und eine Leerzeile, sonst zählt GitHub die
Nummern fort (Nr. 340, E-R4-62). Ein gekürzter Eintrag endet mit `Werdegang
bis DD.MM.YYYY: \`docs/Backlog.md@abc1234\`, Nr. NNN.`; dort steht die lange
Fassung. Die Decken misst `tools/steuerung/decken.py` im Prüfstand; reißt
eine, meldet er einen Hinweis, der nicht aufhält (seit PU-05).

**Fundstellen und Zahlen (Regel seit 13.09.2026).** Funktionsnamen statt
Zeilennummern (`ingest_tag_nachziehen()` statt `ingest.php:623`), in
Markdown-Dokumenten die Abschnittsüberschrift; wo eine Zeile oder ein
gezählter Wert wirklich gebraucht wird, steht das Messdatum daneben
(„53, gemessen 13.09.2026"). **Keine Zeile beginnt mit einer Zahl und einem
Punkt:** Der Bestandsriegel (`tools/quelltext/bestand.py`, Regel `backlog`)
liest dort eine Backlog-Nummer — auch ein Datum nach einem Zeilenumbruch.
Wer ein Datum schreibt, setzt den Umbruch davor.

**Nummernvergabe zwischen Zweigen (E-SD-21).** Ein Zweig, der Nummern
vergeben könnte, trägt beim Anlegen eine Zehnerspanne in die Tabelle unten
ein und pusht das **zuerst**; er vergibt keine Nummer außerhalb seiner
Spanne. Vorher sieht er nach — auf `origin/main` **und** in den offenen Pull
Requests: Zwei Zweige, die gleichzeitig „die nächste freie Nummer" nehmen,
nehmen dieselbe, und diese Datei meldet dabei keinen Konflikt. Eine Zahl
auf einem ungemergten Zweig ist vergeben. Gemergte Spannen werden
gestrichen; die nächste freie Nummer steht in der letzten Zeile. Seit R4-03
misst der Prüfstand es nach (`tools/steuerung/nummern.py`, Nr. 339): eine
neue Nummer, die `origin/main` oder ein anderer Remote-Zweig auch anlegt, ist rot.

| Spanne | Zweig | seit |
|---|---|---|
| 480 bis 489 | `claude/tender-bell-225ta2` (Schritt 22, BR5) | 03.11.2026 |
| 490 bis 499 | `claude/new-session-p1duc7` — Prüfdurchgang der Arbeitsumgebung (R91), Fortsetzung | 03.11.2026 |
| ab 500 | frei — höchste reservierte Nummer 499; frei blieben 472 bis 479 aus der Spanne von 21 (PR #136), 461 bis 469 aus der Spanne von PR (PR #133), 434 bis 439 aus der Spanne von PR #129, 441 bis 449 aus der von PR #130, 420 bis 429 aus der Spanne von 20 (PR #127), 412 bis 419 aus der von 12b (PR #125), 394 bis 399 aus der Spanne von 19 (PR #112 bis #114), 389 aus der von 12a (PR #116), 372 bis 379 aus der von 12 (PR #103), 362 bis 369 aus der von PK, 359 aus der von 18 (PR #101), 348 und 349 aus der von 17, 338 aus der von AR | 02.11.2026 |

---

## Offen

23. **`docs/JSON-Vertrag.md` 3.3 nennt eine Reanimationsart, die kein Schreibweg annimmt.** · gehört zu: 13 · Stand: offen · seit 23.08.2026
     Der Vertrag führt `beginn` unter den gültigen Werten
     von `events[].type`; `ingest_tag_nachziehen()` in `ingest.php` speichert das
     Ereignis **still nicht**, die Ereignisprüfung in `einsatz_form.php` weist
     es ab (bei Aufnahme `ingest.php:299` und `einsatz_form.php:317`, am
     13.09.2026 Zeilen 623 und 328). Beide begründen es gleich und
     richtig: Der Reanimationsbeginn steckt in `started_at` der Sitzung. Der
     Vertrag sagt von sich, eine Abweichung sei „ein Fehler in der Umsetzung,
     nicht im Vertrag" — hier spricht die Sache für den Code. **Vorschlag:
     Vertrag berichtigen** (3.3 nennt neun Ereignisarten, `beginn` steht als
     Sitzungsbeginn daneben). Gefunden in P1/B3 (dort F-P1-F); bewusst nicht
     nebenbei geändert, weil der Vertrag die führende Quelle ist und eine
     Änderung an ihm eine Entscheidung wäre, keine Korrektur.

<!-- -->

37. **Wie verhält sich die Anwendung, wenn ein Konto über Jahre wächst?** · gehört zu: nach v1.0 · Stand: teilweise · seit 30.08.2026
     Befund (Messstand, 5050 Einsätze, CPU sechsfach gedrosselt, 16.09.2026):
     Die Zeitraumübersicht war der Engpass (42,61 s für 3983 Einsätze im
     Jahr). Der Suchindex überträgt den gesamten Bestand (1097 Byte je
     Einsatz), obwohl rund dreißig Filter auf Klartextspalten serverseitig
     vorschneiden könnten. Fünf von sechs Zielzahlen aus E-S2-24 sind
     gehalten, Spuren 3,66 statt 3 MB je 1000 Einsätze knapp verfehlt.
     Erledigt: der Deckel je Konto (P5b/AP6), die Kontenachse (P3/O9b),
     Speicher und Wartung (S2); mit R4-17 (Web 21.4.0) die Seitengrenze 200
     der Zeitraumübersicht (4071 Einsätze: 88 s auf 9 s) und die drei
     stillen Kappungen der Tageslisten (500, 120, 400), die jetzt sagen,
     dass sie greifen; mit R4-27 (Web 21.6.1) das Zeichnen in Stücken — die
     Tabelle ist nach 3,7 s zu sehen, fertig ist die Seite nach 7,0 s.
     Weg (nach v1.0, E-R4-16): Vorschneiden im Suchindex; Monatsvorwahl der
     Zeitraumübersicht, falls die 7 s bis „fertig" stören — sie gehen auf
     Entschlüsseln und Layout über alle Einsätze des Jahres zurück.
     `post_max_size` der Zielanlage steht auf Betrieb → Status.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 37.

<!-- -->

46. **Das Altformat des Backups wird mit NaDoku 1.0 abgeschafft.** · gehört zu: 14 · Stand: teilweise · seit 31.08.2026
     **Mit S11-12 (Web 23) vorgezogen erledigt:** der Lesezweig in
     `crypto.js` (`openBackup`, `sealBackup`), die Annahme von Nutzlast 6
     bis 12 im Browser, am Endpunkt und in `edbak_restore()`
     (`409 version_alt`), der Punktlisten-Rückweg im Eintrag, die Weiche
     `--art edbak-alt` in `kreislauf.py` samt `referenz/altformat/`, und der
     Messstand schreibt Fassung 4 (`schreiben_edbak_v4()`, E-S11-85).
     **Offen bleibt:** die Adminpakete der Fassung 1 (JSON) und unversiegelte
     der Fassung 2 (`edbak_teil_oeffnen()` liest sie roh) sowie die
     unversiegelte Begleitdatei `konto.json` — Liste, Schlüsselwechsel
     (`tools/proben/schluesselwechsel/` prüft ein Fassung-2-Paket ohne
     Siegel byte-gleich) und das Aufräumen der Ablage lesen sie noch.
     Eingespielt werden sie seit Web 23 nicht mehr. Ausnahme mit Absicht:
     der Demo-Reset spielt seine Nutzlast-7-Fixture über `'server'` ein.
     *Weg:* zum Stichtag die drei Lesestellen schließen, die Probe des
     Schlüsselwechsels mitziehen. Werdegang bis 26.09.2026:
     `docs/Backlog.md@f5bddc2`, Nr. 46; bis 19.10.2026 in der Git-Historie.

<!-- -->

50. **Der Versand liest je Konto ein Verzeichnis.** · gehört zu: nach v1.0 · Stand: offen · seit 01.09.2026
     `sz_versand_schub()` fragt für jeden Kontoordner die Verzeichnisliste des
     Ziels ab, um zu erkennen, was dort fehlt. Bei 33 Ordnern ist das
     unauffällig (gemessen: SFTP 3,08 s für 64 Dateien einschliesslich aller
     Listen); bei dreihundert Konten sind es dreihundert Abfragen je Lauf und je
     Ziel, über eine Leitung, die kein Loopback ist.

     Der Ausweg ist **nicht** eine Merkliste in der Datenbank — die behauptet
     „schon versandt" auch dann noch, wenn die Datei am Ziel gelöscht oder das
     Ziel neu aufgesetzt wurde, und diese Art Lüge fällt erst auf, wenn man das
     Backup braucht (Begründung in Technik 4.9). Denkbar ist stattdessen,
     die Liste je Ziel **einmal rekursiv** zu holen, wo das Protokoll es
     hergibt, und nur bei Zweifel nachzufragen. Erst messen, dann bauen.

<!-- -->

55. **Das Komplett-Backup kennt keinen scharfen Schnappschuss.** · gehört zu: nach v1.0 · Stand: offen · seit 01.09.2026
     Aus S2/AP8. Der Dump entsteht über mehrere Anfragen; ein Lesestand über
     den ganzen Lauf (`--single-transaction`) ginge nur innerhalb EINER
     Verbindung. Eine Zeile, die währenddessen entsteht, kann enthalten sein
     oder nicht. Übersprungen wird nichts, was schon dastand — der Cursor läuft
     über den Primärschlüssel —, aber ein Einsatz, der während des Laufs
     angelegt wird, kann mit Phasen und ohne Spur in der Datei landen.

     In der Praxis ist das verschmerzbar: Wer nachts sichert, hat das Problem
     nicht, und die Uhr liefert Fehlendes idempotent nach. **Zu entscheiden:**
     ob es sich lohnt, den Lauf auf eine Anfrage zu zwingen, sobald die
     Datenbank klein genug ist (dann wäre er scharf), und wo diese Grenze
     läge — oder ob stattdessen die Reihenfolge der Tabellen so gewählt wird,
     dass wenigstens Einsatz und Phasen zusammen fallen.

     ---

<!-- -->

62. **Logodateien tragen teilweise wieder die alten Farbwerte.** · gehört zu: 13 · Stand: offen · seit 31.08.2026
     Befund (B-S4-01; bis 02.09.2026 Nr. 49): Der Commit „Update Logos" hat
     alte Werte zurückgebracht — `gen-em_logo_helicopter.svg` führt `#587abc`,
     `#e3322b`, `#f7941d`, Korpus `#1d0e0a` statt `#4280E5`, `#D63338`,
     `#FF8F1F`, `#1A0500`; die PNG ebenso (gemessen 13.09.2026). Richtig sind
     nur `gen-em_logo_nef_weiss.svg` und `gen-em_logo_nef.png`.
     Entschieden 12.09.2026: neue Vorlagen anfordern; bis dahin nichts am
     Code. `Design.md` 2.5 ist am 13.09.2026 berichtigt.
     Weg: Die Durchsicht vom 26.09.2026 nahm an, die Vorlagen lägen vor
     (E-SD-34); im Baum ist seit `6f316ee` vom 12.09.2026 keine Logodatei
     geändert (Konzept R4, F-R4-08). Mit den Vorlagen alle Fassungen samt
     Ableitungen (PNG, Favicons, Uhr-Bilder) nachziehen, nachmessen und
     `Design.md` 2.5 mitziehen — mit dem neuen NEF-Logo, vor P7.
     Ziel 13 seit 26.09.2026 (R4-01, E-R4-08); Schritt 17 fasst keine
     Bilddatei an.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 62.

<!-- -->

80. **Auswertung der Gerätestatistik — und die zweite Hälfte der Frage.** · gehört zu: Zuarbeit · Stand: teilweise · seit 02.09.2026
     Befund: Rest von Nr. 59 (Speicherung, Web 12.9.0). Gebaut ist
     inzwischen fast alles: die Modelltabelle auf Betrieb → Statistik (S8/AP4,
     Web 15.3.0; 325 Teilenummern auf 173 Modelle), der Nachlöse-Job
     (P5a/AP11, Web 20.15.0 — `geraetemodelle-nachaufloesen.php` ist nur noch
     Weg von Hand)
     und die Herkunft je Einsatz mit den drei Reitern der Statistik (P5c/AP7,
     Web 20.47.0, E-P5c-18, ohne Vorbedingung — E-P5c-45). Die User-Agent-
     Hälfte („Rechner zählen") ist gestrichen (R36: keine Neuerhebung).
     Eine Lücke bleibt Bauform: Eine Wear-OS-Uhr koppelt nicht (E-S4-11) und
     erscheint als `handy`; die Statistik sagt es dazu.
     Offen: die **Datenschutzerklärung** nennt die Gerätekennung im Entwurf
     (`docs/rechtstexte/Datenschutzerklaerung.md` 3, 19.10.2026); eingetragen
     wird er nach S11 (Rahmenplan 6.3); die Momentaufnahme am Einsatz (`missions.geraet_art`) ist nicht bestellt.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 80.

<!-- -->

187. **Alle „Anhebungs"-Wege werden mit NaDoku 1.0 abgeschafft.** · gehört zu: 14 · Stand: offen · seit 14.09.2026
     Entschieden 14.09.2026 (S10/AP3): Ab 1.0 gibt es nur neue Konten, also
     keinen Altbestand, der still gehoben werden müsste. Anhebung heißt: ein
     Weg, der Daten beim Anmelden oder Anzeigen still auf die aktuelle
     Fassung bringt. Drei gibt es, alle an `assets/unlock.js`: Rundenzahl
     (`rundenAnheben` → `api/kdf_upgrade.php`), Schlüsselhülle `edk1:` →
     `edka1:` (`huelleUmstellen`, derselbe Endpunkt), Einsatz-Notizen in den
     `pat_blob` (`api/pat_anheben.php`).
     Zu entfernen: beide Endpunkte samt Aufrufern, `KDF_ITER_LISTE` in
     `db.php` (damit auch das Vormerkfach der Anmeldung), die Statuszeile
     „Schlüsselableitung", die Lesetoleranz für `edk1:` (`WRAP_PRAEFIX_RE`)
     und die Prüfmittel dazu (`tools/proben/anteil/endpunkt.py` Teil E,
     `umstellungslauf.mjs`, `huelle_stellen.py`). Nicht mitgehen: die
     Formatkennung selbst und die Rotation des Server-Anteils (E-S10-11).
     Reihenfolge: in einem Paket mit Nr. 46, sobald keine Anlage mit
     Altbestand mehr herüberkommt (Nr. 324). Abnahme:
     `grep -rn "anheben\|kdf_upgrade\|pat_anheben" server/` ist leer, ein
     frisches Konto liest seine Daten, Wortliste und Vollständigkeit melden
     keine ungenutzten Ausnahmen.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 187.

<!-- -->

202. **Zentralisierung Web — eine Stelle je Sache (Sammelnummer, Schritt 15, R83).** · gehört zu: Pflegeaufgabe · Stand: nur auf Anlass · seit 16.09.2026
     Befund (16.09.2026, nachgemessen 20.09.2026 an `862ca7f`): Eine eigene
     Sitzung hat `server/` (ohne `assets/vendor/`) auf Code untersucht, der
     nach dem Vorbild von `mission_fields.php` an eine Stelle gehört — sechs
     Pakete: Marke/Mail/Link/Token, Datenzugriff, API-Eingang/Sitzung/Flash,
     JavaScript, Zeit/Zahl/Migration, Beifang. Regel R83: zentralisiert wird
     beim zweiten echten Verbraucher.
     Erledigt: Paket 1 in P5a/AP5 und P5b/AP2, der Log-Helfer in 10c AP3
     (Nr. 248), Pakete 2 bis 5 in Schritt 15 AP2 bis AP8 (Zahlen je Sache:
     `tools/zaehlung/register.php`); `post_ende()` bewusst nicht (F-ZE-4).
     Offen ist Paket 6, Beifang ohne Termin, nur zusammen mit Arbeit an der
     Datei (Stand 26.09.2026, Konzept R4, F-R4-13): Stammdaten-CRUD in
     `einstellungen.php` (sechs Speicher- und Löschpaare), Auftakt der
     Verwaltungsseiten (`ui_meldung` in 12 Dateien gleich komponiert),
     Umfangsliste mit Zahl-Plakette (3× wortgleich), Nachweisdatei-Mechanik
     (erledigt mit SR-04: `nachweis_lib.php`, Z44), Ablage mit Zeitstempel
     (dreimal `gmdate('Y-m-d\TH-i-s\Z')`). Ziel `Pflegeaufgabe` seit
     26.09.2026 (R4-01, Q-R4-12), bestätigt 02.11.2026 (E-BE-05): nur mit
     Arbeit an der Datei, kein eigener Schritt.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 202.

<!-- -->

213. **Die Zustandsdatei der Auslieferungskette lag im Webroot.** · gehört zu: 14 · Stand: teilweise · seit 16.09.2026
     Befund (Durchsicht des Auftraggebers; behoben am selben Tag, Web
     20.15.1): `SamKirkland/FTP-Deploy-Action` legte
     `.ftp-deploy-sync-state.json` in den Webroot, über HTTP abrufbar — je
     ausgelieferter Datei Pfad, Größe und Hash, dazu der Zeitpunkt der
     letzten Auslieferung. Kein Schlüsselleck (die Ausnahmeliste hält
     `config.php` und die anderen Serverdateien heraus), aber die Vorlage
     für einen Abgleich gegen bekannte Schwachstellen ohne eine Anfrage.
     Behoben mit zwei Schranken: `state-name` legt die Datei eine Ebene über
     den Webroot (`../.deploy-state-staging.json` bzw. `…-produktion.json`),
     `server/.htaccess` sperrt Punktdateien pauschal außer `.well-known/`.
     Der Prüfschritt in Stufe 2 misst 403 (nicht 404 — gegen ein leeres
     Staging gibt jeder Pfad 404) und als Gegenprobe 404 für
     `.well-known/acme-challenge/`.
     Offen: (1) die bereits abgelegte Datei auf jeder Anlage von Hand per
     FTP löschen; (2) ob `../` im Käfig des FTP-Zugangs erlaubt ist, war bei
     Aufnahme nicht gemessen — `FTP_STATE_PFAD` ist der Rückweg (Pflichtwert
     seit Kette II/AP6, E-KH-07); (3) die `.htaccess` gilt nur auf Apache
     (wie Nr. 129).
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 213.

<!-- -->

230. **Die Wegwerfdomain-Liste altert still und muss mit jeder Auslieferung nachgezogen werden.** · gehört zu: Pflegeaufgabe · Stand: teilweise · seit 17.09.2026
     Befund (Konzept P5b, E-P5b-23): `server/wegwerfdomains.txt` stammt aus
     `disposable-email-domains` (CC0 1.0; bei der Auswahl 8 870 Domains,
     8 von 8 Wegwerfanbietern getroffen, 0 von 10 Provider- und
     Klinikdomains). Geholt wird sie nie zur Laufzeit (Zusage „keine fremde
     Quelle"), also hält kein Automatismus sie aktuell; sie altert in eine
     Richtung — neue Anbieter kommen durch, und eine durchgelassene
     Registrierung sieht aus wie eine richtige (E-P5b-13). Gefährlicher ist
     die andere Zahl: Eine Klinikdomain auf der Liste erfährt nie, woran es
     lag. Jede Aktualisierung muss deshalb beide Messungen wiederholen.
     Erledigt (Web 20.22.0, AP3): Liste (8 883 Domains) und das Werkzeug
     (seit PK-04 `tools/erzeugen/wegwerfdomains.py`), das beide Richtungen
     misst und nicht schreibt, wenn eine echte Domain getroffen wird; die
     Zeile im Auslieferungs-Runbook (`Technik.md` 13).
     Offen: der Stand der Liste — Datum und Zahl — in Betrieb → Status nach
     dem Vorbild der Zeile Gerätemodelle (`status_lib.php`), damit eine
     veraltete Liste sichtbar ist statt still; und die Zahl steht doppelt
     (Datei und Kommentar in `betrieb_server.php`). Kein Prüfschritt, der
     die Quelle abruft — er machte jeden Lauf von einem fremden Host abhängig.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 230.

<!-- -->

232. **Die Fristen der Rückfragen sind nie im Betrieb abgelaufen.** · gehört zu: 14 · Stand: nur auf Anlass · seit 17.09.2026
     *Aufgenommen 17.09.2026 (P5b/AP9).* Die Konto-Rückfrage fragt nach 30
     Tagen, 6 Monaten und dann jährlich; die Betreiber-Rückfrage alle drei
     Monate. Geprüft wurde mit **gestelltem** `rueckfrage_naechste` — die
     Runden 0 → 1 → 2 → 2 sind in vier Durchgängen gemessen, der tatsächliche
     Halbjahresabstand nicht.

     **Was das offen lässt:** Ein Rechenfehler in `RUECKFRAGE_ABSTAENDE` oder
     in der Zeitzone (`UTC_DATE()` gegen `DateTimeImmutable('now', UTC)`)
     fiele im Prüflauf nicht auf, sondern erst, wenn die Frage im Betrieb um
     einen Tag daneben käme. Das ist ein kleiner Schaden, aber ein stiller.

     **Wie es zu schließen wäre:** ein Prüfschritt, der die Funktionen mit
     festgelegter „jetzt"-Zeit rechnen lässt, statt mit der Systemuhr — dafür
     müsste `einstieg_lib.php` eine Zeit hereingereicht bekommen, statt sie zu
     holen. Lohnt sich, wenn die nächste Frist dazukommt; für zwei Fristen ist
     der Umbau teurer als der Fehler.
     Ziel 18 vom 26.09. bis 05.10.2026 (R4-01, Q-R4-12); umgehängt mit SR-06
     (E-SR-25): SR-03 gab `einstieg_lib.php` einen Anlass, keine dritte Frist.

<!-- -->

234. **Kein Prüfmittel fährt den Weg, den eine frisch ausgelieferte Anlage geht — Deploy, Anmeldung, `update.php`.** · gehört zu: 14 · Stand: teilweise · seit 18.09.2026
     Befund (P5b-Deploy auf Staging; Anlass behoben in Web 20.24.1): Zwei
     SELECTs auf dem Anmeldeweg forderten Spalten an, die erst die Migration
     anlegt — HTTP 500 auf der Anmeldeseite, gefunden von der Betreiberin,
     nicht von einem Prüfmittel. Alle Mittel setzen eine eingerichtete
     Anlage mit gelaufenen Migrationen voraus; der Zustand „Code neu,
     Schema alt" kommt im Prüfstand nicht vor, tritt aber bei jeder
     Auslieferung mit Schemaänderung ein (wie Nr. 223).
     Beantwortet (Kette II/AP6, 21.09.2026): Die Kette erzwingt den
     Wartungsmodus; bei ausstehender Migration bleibt er an, der Lauf sagt
     es (belegt mit zwei provozierten Fehlschlägen, F-KH-U-39); die
     Anmeldung der Betreiberin trägt der Torwächter (P5a/AP3).
     Offen bleibt der Titel: ein Schritt in Stufe 2, der gegen Staging tut,
     was die Betreiberin tut — Migrationen zurücksetzen oder zweite Anlage,
     anmelden, `update.php`, wieder anmelden — vor dem Kreislauftest, dessen
     Meldung („Anmeldung gescheitert: unbekannt") das Schema nicht nennt.
     Kette II fuhr den Weg von Hand (`Technik.md` 12); das Prüfmittel
     fehlt, sein Platz steht seit PK-06 als Kommentar am Ende von `stufe2`.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 234.

<!-- -->

237. **Der Täter-Finder des Bilderlaufs findet den Täter nicht.** · gehört zu: 14 · Stand: offen · seit 18.09.2026
     Befund (beim Beheben von Nr. 221): Der Bericht trägt seit Web 20.16.2
     eine Spalte `Verursacher`, und beim ersten Fall, der sie gebraucht
     hätte, stand dort `—`. Gemessen örtlich (`05-datenschutz`, 360 px,
     Überlauf 127 px): Verursacher ist ein `<p>` in `.text` mit
     `scrollWidth` 350 gegen `clientWidth` 302 — ein Skript von zwanzig
     Zeilen findet ihn, der eingebaute Finder nicht.
     Verdacht, nicht belegt: Seit P5b/AP8 überspringt der Finder Elemente in
     scrollenden Vorfahren (damals richtig, ein `<code>` in einem
     scrollenden `<pre>`); `.karte` und `.karte-inhalt` melden hier
     ebenfalls `scrollWidth > clientWidth`, und gelten sie als scrollend,
     fällt alles darunter heraus.
     Weg und Abnahme: den Fall aus Nr. 221 als Prüffall nehmen — eine
     Mailadresse in einem Rechtstext bei 360 px, der Finder muss das `<p>`
     nennen. Ein Prüfmittel, das den Befund meldet und den Grund
     verschweigt, kostet die Stunde, die es sparen sollte.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 237.

<!-- -->

240. **Der Rundlauf-Prüffall des Handy-Moduls läuft in der Kette nie.** · gehört zu: 14 · Stand: offen · seit 20.09.2026
     Befund (beim Beheben des Robolectric-Downloads): `showStandardStreams`
     hängt an `rundlauf.isNotBlank()`, und in Stufe 1 ist `rundlauf` leer —
     `.github/` setzt `-Pnadoku.rundlauf` nirgends. Die drei
     Rundlaufklassen überspringen sich selbst (in den 15 „skipped" vom
     20.09.2026 enthalten). Heute richtig: Die Kette hat keine PHP-Anlage,
     und Staging ist keine Schreibprobe wert. Trotzdem eine Lücke mit
     Ansage — es sind die einzigen Prüffälle, die Handy und Server zusammen
     messen, und die einzigen, die die Kette nie fährt.
     Weg, beide ungemessen: ein PHP-Dienst im Prüfschritt selbst (`php -S`
     über `server/` plus Datenbank — das ist der Aufwand) oder ein
     Vertragsprüfstand, der die erwarteten Anfragen als Attrappe beantwortet
     und nur das Nachrichtenformat prüft (billig, misst den Server nicht
     mit). Zuerst entscheiden, welche der beiden Fragen beantwortet werden
     soll.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 240.

<!-- -->

252. **Gelaufene Migrationen fragen das Schema 57× von Hand.** · gehört zu: 14 · Stand: offen · seit 20.09.2026
     *Aufgenommen 20.09.2026 (Konzept Zentralisierung, E-ZE-04).* Zugeordnet:
     **P8** (R66, neues Migrationsregister).

     Jede Migration prüft selbst, ob ihre Spalte schon da ist. Das ist 57 Mal
     dieselbe Abfrage, und sie ist der Grund, warum eine gelaufene Migration
     nicht einfach umgeschrieben werden kann.

     **Schritt 15 fasst sie ausdrücklich nicht an** (E-ZE-04 ist die eine
     Ausnahme von „alles wird angegangen"): Eine gelaufene Migration
     umzuschreiben heißt, eine Anlage anders zu behandeln als die, auf der
     sie schon lief. Das Register in P8 löst es an der Wurzel; bis dahin
     steht im Zählmittel eine **Decke von 57** — sie darf nicht wachsen.

<!-- -->

261. **Staging bewahrt zwei Komplett-Stände auf — der Hotfix-Weg braucht mehr.** · gehört zu: Zuarbeit · Stand: offen · seit 21.09.2026
     Befund (Kette II, AP7): `KOMP_AUFBEWAHRUNG_VORGABE` steht auf 2
     (`komplett_lib.php`), einstellbar unter Betrieb → Komplettsicherung →
     „Stände aufbewahren" (1 bis 20). Seit AP7 legt die Kette bei jeder
     Produktiv-Auslieferung einen Komplett-Stand auf Staging an — den
     Rückfallstand für den Hotfix-Weg (E-KH-16). Bei 2 ist der Stand des
     vorletzten Tags bereits verdrängt, und der Sicherungsplan von Staging
     verdrängt zusätzlich; die vom Konzept verlangte Messung fällt negativ
     aus.
     Nicht im Code: Die Aufbewahrung ist eine Entscheidung über
     Speicherplatz auf einer konkreten Anlage; eine höhere Vorgabe änderte
     sie für jede Installation mit. Die Laufzusammenfassung nennt den
     Dateinamen und sagt, dass er verdrängt wird; das Runbook
     (`Technik.md` 12.12, Schritt 2) ebenso.
     Vorschlag an die Betreiberin (Prüfpunkt 28, vor M2): Aufbewahrung auf
     Staging auf 5 setzen; wie viele Tags man rückwirkend reparieren können
     will, weiß nur sie.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 261.
     Dazu (PK-07, F-PK-53): Der Job hängt nicht am Tor — ein neu
     gestarteter Tag legt zwei Stände an (M1: Versuch 1 und 2).

<!-- -->

264. **Die Fremd-Aktion des Transports ablösen.** · gehört zu: Pflegeaufgabe · Stand: nur auf Anlass · seit 21.09.2026
     Befund (Kette II, Einschub Abschnitt 8): Der Transport läuft über
     `SamKirkland/FTP-Deploy-Action`; Kette II/AP4 hat sich gegen eine
     Ablösung und für den kleinsten Eingriff entschieden (die Zustandsdatei
     hinlegen, bevor die Aktion läuft). Gegen sie spricht: Sie fängt jeden
     Fehler von `getServerFiles` ab und deutet ihn als „first publish",
     rechnet mit einem toten Client weiter und meldet die Stelle drei
     Schritte hinter der Ursache (acht Trennversuche lang stand der falsche
     Aufruf im Verdacht, F-KH-U-25); sie kennt keine Wiederaufnahme; ihr
     jüngstes Tag ist vom 19.04.2026. Für sie: Sie funktioniert, seit die
     Zustandsdatei liegt (688 Dateien ohne `ECONNRESET`, danach vier
     Staging-Läufe), und ein eigener Transport wäre neuer Code an der
     empfindlichsten Stelle der Kette, den niemand außer uns prüft.
     Auslöser, und erst dann: erneute Abbrüche, Bedarf an Wiederaufnahme
     oder ein Ende der Pflege der Aktion.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 264.

<!-- -->

265. **Verweise von `.github/` in die Dokumentation hält kein Prüfmittel nach.** · gehört zu: 14 · Stand: offen · seit 21.09.2026
     *Aufgenommen 21.09.2026 (Kette II, AP8a; Anlass F-KH-U-02).*
     Priorität: niedrig. Auslöser: eine weitere Neufassung von Rahmenplan 6a.

     `.github/workflows/auslieferung.yml` verweist in seinen Fehlermeldungen
     auf **„Rahmenplan 6a, Schritte 1 bis 3"** und „Schritt 4". Wer 6a
     umnummeriert — und das ist am 20.09.2026 beim Hosterwechsel beinahe
     passiert —, macht daraus einen Irrweg: Die Meldung schickt jemanden zu
     einem Schritt, der etwas anderes sagt als gemeint.

     **`tools/kettenaufrufe/` schlägt dabei nicht an**, und das ist kein
     Versäumnis: Es prüft **Werkzeugschnittstellen**, nicht Textverweise. Die
     Schrittnummern in 6a sind beim Umzug ausdrücklich beibehalten worden,
     **weil** die Kette sie nennt — der Verweis hält heute also, aber nur,
     weil jemand daran gedacht hat.

     **Zu tun:** ein Prüfschritt, der die in `.github/` genannten
     Dokumentstellen gegen die Überschriften hält, die es wirklich gibt.
     Auslöser da (PK-07, F-PK-45): Meldungen nennen „Schritte 6 bis 8" (8 ist
     das Demo-Konto) und Kette-II-Kennungen, deren Konzept gelöscht ist.

<!-- -->

276. **Die tote Spalte `missions.other_resources` löschen.** · gehört zu: 14 · Stand: offen · seit 22.09.2026
     Befund (Schritt 15 AP6): Seit der Migration `2026_07` liegen die
     weiteren Rettungsmittel als Zeilen in `mission_resources`; die Spalte
     wurde damals nur nicht gelöscht und wird von nichts mehr gefüllt oder
     gelesen. Bis das Backup seine Spaltenliste bekam (`SELECT *`), ging
     sie jahrelang in jede Sicherungsdatei und wurde beim Einspielen
     verworfen. Nicht verloren: Das Spaltenregister (`mf_missions_register()`,
     Web 20.31.0) führt sie mit genau dieser Begründung in
     `mf_missions_gruende()`, und `tools/spaltenregister/pruefen.php`
     schlägt an, wenn die Begründung ohne die Spalte verschwindet.
     Weg (P8, R66 — es ist eine Migration, Priorität niedrig): destruktive
     Migration mit `zerstoert`-Eintrag und `inhalt`-Eintrag, der blockiert,
     solange die Spalte Werte trägt (`migrationen_inhalt_zaehlen()`); der
     Registereintrag fällt mit der Spalte, die Zeile in
     `mf_missions_gruende()` wird gestrichen, nicht umgeschrieben.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 276.

<!-- -->

280. **Die Kartenquelle OpenHikingMap steht in keiner Lizenzliste.** · gehört zu: 13 · Stand: offen · seit 22.09.2026
     *Gefunden 22.09.2026 von der neuen Regelklasse `netz` (PK-04/1c);
     nicht behoben.*
     `server/assets/map_layers.js` lädt Kacheln von `tile.openmaps.fr`, und
     `server/kopfzeilen_lib.php` erlaubt den Host in der
     Content-Security-Policy. **In `docs/Lizenzen.md` steht er nicht** — die
     Kartentabelle dort nennt `tile.openstreetmap.org`,
     `tile.opentopomap.org` und `server.arcgisonline.com`, aber nicht diesen.

     Das ist genau die Lücke, gegen die die Zusage „keine fremde Quelle zur
     Laufzeit" geschrieben ist: Eine Quelle, die läuft und die niemand
     aufgeschrieben hat.

     **Nicht nebenbei eingetragen:** Welche Lizenz für die Kacheln gilt, sagt
     das Attributionsband im Code („© OpenHikingMap · © OpenStreetMap"), aber
     ein Eintrag in `Lizenzen.md` behauptet mehr als das — er nennt
     Rechteinhaber und Bedingungen. Das gehört nachgesehen, nicht
     abgeschrieben.

<!-- -->

290. **Keine Stufe der Kette richtet eine Anlage ein.** · gehört zu: 14 · Stand: offen · seit 23.09.2026
     *Aufgenommen 23.09.2026 mit Web 20.37.3 (Anlass: Nr. 288).* Nr. 288 hat
     elf Fassungen lang (20.30.0 bis 20.37.2) jede Neueinrichtung gebrochen,
     und keine Stufe hat es gesehen: Stufe 1 richtet keine Anlage ein, Stufe 2
     läuft gegen das eingerichtete Staging, und eine vorhandene örtliche
     Installation überspringt den Schritt. Bemerkt hat es `hochfahren.sh
     --neu`, und das fährt nur, wer es ausdrücklich will.

     *Weg (zu entscheiden):* ein Stufe-1-Schritt, der `install.php` gegen eine
     leere Datenbank laufen lässt und den Einrichtungslink verlangt — oder
     kleiner, ein Schritt, der `konto_lib.php` ohne `config.php` lädt und jede
     Funktion auf dem Weg des Einrichters als vorhanden verlangt. *Abnahme:*
     der Schritt wird rot, wenn man c3b5bff nachstellt (den Rahmen zurück nach
     `db.php`). *Fehlschlag:* grün auf diesem Stand. `docs/Pruefablauf.md`
     führt ihn mit „Anlass: Nr. 288".

<!-- -->

300. **Die Hauptstufe des Prüfstands fährt die Plattformmatrix nur zur Hälfte.** · gehört zu: 14 · Stand: offen · seit 24.09.2026
     Befund (Konzept P5c AP4, F-P5c-103): `Pruefablauf.md` 3 verspricht für
     `haupt` PHP 8.3 und 8.4, je Paar mit vier Datenbanken Schemaprobe und
     Kreislauf `edbak`, dazu Uhr-Stufe II. `pruefablauf.json` gibt `haupt`
     davon die Schemaprobe über vier Datenbanken unter dem PHP des
     Containers; `kreislauf.py` kennt keine zweite Datenbank, PHP 8.3 fährt
     keine Probe, die Uhr-Stufe II steht nirgends, und der Bilderlauf läuft
     auch in `haupt` nur in Chromium (`--stufe haupt` wählt nur die
     Breiten). Ein Bericht „haupt, grün" sagt über PHP 8.3 und MySQL 8.4 im
     Kreislauf nichts. Falle für den Bau (F-P5c-105): In WebKit setzt
     `page.screenshot()` ein eigenes Stylesheet, die CSP meldet es, auf den
     Wartungsseiten antwortet der Endpunkt 503 — 16 selbstverursachte
     „Konsolenfehler".
     Weg: `pruefen.sh` fährt den Bilderlauf bei `haupt` je Motor (drei
     Zahlen), einen zweiten Durchgang unter `hochfahren.sh --php 8.3`,
     `kreislauf.py` bekommt eine Datenbankwahl aus `plattform.sh`, der
     Bericht nennt je Paar eine Zahl — oder `Pruefablauf.md` 3 wird auf das
     Gebaute zurückgenommen, mit Begründung. Nicht beides offen lassen.
     PK-06 bis PK-08 haben es nicht angefasst; bis dahin je Paket von Hand.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 300.

<!-- -->

324. **Der eigene Bestand kommt einmal über ein Einmal-Skript in die 1.0.** · gehört zu: 14 · Stand: offen · seit 25.09.2026
     Entschieden (P5c/AP8, Auskunft der Betreiberin, E-P5c-127): Die
     frische Anlage bekommt einen Altbestand — den der Betreiberin — aus der
     Konto-Sicherung der letzten Fassung vor 1.0, einmal und nie wieder.
     Eine Komplett-Sicherung aus Altdaten wird nie eingespielt; die 1.0
     braucht weder für Konto- noch für Komplett-Sicherungen
     Rückwärtskompatibilität.
     Zu bauen: eine einzelne PHP-Datei für genau diesen Weg, nach dem
     Einspielen aus dem Repositorium gelöscht; kein Teil der Anwendung.
     Randbedingung: Die Konto-Sicherung trägt die geschützten Angaben im
     Klartext, in die Datenbank dürfen sie nur mit dem Datenschlüssel, den
     nur der Browser hat — naheliegend ist ein Umsetzer von Datei zu Datei
     (alte Sicherung hinein, Sicherung im Format der 1.0 heraus, gleiches
     Passwort), eingespielt über den gewöhnlichen Weg. Der Bestand trägt
     laut Betreiberin weder Tagesrettungsmittel noch Rettungsmittel außer
     Typ Standard; nachbearbeitet wird von Hand.
     Zuordnung P8 Schnitt. Nr. 46 und Nr. 187 fallen damit ohne
     Übergangsweg. Abnahme: Bestand in der frischen Anlage, Skript entfernt,
     die 1.0 liest keine Nutzlast von vor 1.0.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 324.

<!-- -->

342. **Die Einsatztabellen von Suche und Zeitraum rollen noch am Schreibtisch.** · gehört zu: 13 · Stand: offen · seit 26.09.2026
     *Aufgenommen 26.09.2026 mit R4-08 (F-R4-30),* gemessen vom Bilderlauf,
     der rollende Behälter seither nennt (Nr. 297). Die Tabelle in
     `.tabelle-scroll` rollt auf `suche.php` bei 1440 px noch um 176 px und
     bei 1600 px um 16, auf `zeitraum.php` bei 1440 px um 136 (Tagesliste)
     bzw. bei 1200 px um 52 (Monatsliste); die Kontenliste der Verwaltung
     bei 1024 px um 81 (BetreiberIn), 24 (Support) — dort ist die Spalte
     „Öffnen" angeschnitten. Unter 720 px zeigen alle drei Seiten Karten
     statt der Tabelle; betroffen ist also genau die Schreibtischbreite, für
     die die Tabelle da ist.
     *Weg:* je Seite messen, welche Spalten die Breite treiben, und
     entscheiden: Spalten erst ab einer Breite zeigen, die Kartenansicht bis
     zu einer höheren Schwelle, oder umbrechen lassen. Eine Gestaltungsfrage
     mit Mockup (`CLAUDE.md` 5), keine Korrektur.
     *Abnahme:* Bilderlauf, Spalte „Rollt bei" für die drei Seiten ab
     1280 px leer.

<!-- -->

343. **`plattform.sh` scheitert an gedrosseltem Docker Hub, obwohl ein Spiegel geht.** · gehört zu: 14 · Stand: offen · seit 26.09.2026
     *Aufgenommen 26.09.2026 mit R4-09 (F-R4-31).* Der erste Lauf von
     `plattform.sh alles` in dieser Sitzung holte `mariadb:10.6` und
     `mysql:8.4.0` nicht — „You have reached your unauthenticated pull rate
     limit" im Protokoll von `dockerd` —, meldete „4 Stück fehlen" und ließ
     die `schemaprobe` im Prüfstand „nicht gemessen", was ein Bericht im
     Tor als rot zählt. Von Hand ging es sofort über den Spiegel:
     `docker pull mirror.gcr.io/library/<abbild>`, dann `docker tag` auf den
     Namen, den das Skript erwartet; danach vier Fassungen, je 30 Prüfungen,
     0 Fehlschläge.
     *Weg:* `plattform.sh` versucht bei einem gescheiterten Abruf den Spiegel
     und sagt, welchen Weg es genommen hat; `docs/Sandbox-Setup.md` nennt
     ihn. *Abnahme:* ein Lauf mit gesperrtem `registry-1.docker.io` holt die
     Abbilder über den Spiegel und meldet es.

<!-- -->

346. **Zwei Meldungen im Browser noch von Hand: der Hinweis `patwarn` und der Papierkorb-Hinweis des Diensttags.** · gehört zu: 22 · Stand: offen · seit 27.09.2026
     *Aufgenommen 27.09.2026 mit R4-13 (F-R4-42).* Die Zählzeile Z37 sieht
     seit R4-13 auch die Zuweisung `className = '…meldung'` und fand damit
     zwei Nachbauten, die das alte Muster nicht sah: in `assets/patient.js`
     den Hinweis `patwarn` (ohne Symbol — der Kommentar dort sagt, weil
     `symbol.js` beim Aufbau noch fehlen kann) und in `index.php` den
     Hinweis „Dieser Diensttag liegt im Papierkorb" als `<p>` mit
     `meldung meldung-warn`. Beide sehen anders aus als jede andere Meldung.
     **Nicht in R4-13 umgestellt:** Sie liegen außerhalb von Nr. 271–273,
     beide wären eine sichtbare Änderung, und bei `patwarn` hängt es an der
     Ladereihenfolge von `edSymbol()`. *Weg:* über `EdHtml.meldung()`, für
     `patwarn` erst nachsehen, ob `symbol.js` zur Aufrufzeit steht (die
     Immer-Liste kommt am Seitenende). *Abnahme:* Z37 **2** (nur `html.js`),
     Bilderlauf ohne Konsolenfehler.

<!-- -->

347. **Zweimal an einem Tag standen zwei fremde Waisen in der örtlichen Anlage — woher, ist nicht geklärt.** · gehört zu: 14 · Stand: offen · seit 28.09.2026
     *Aufgenommen 28.09.2026 mit R4-27 (F-R4-72).* Die Jobprobe prüft,
     dass „sechs Zeilen und ein Blob" als 7 gemeldet werden, und war zweimal
     rot mit „vorher 2 fremde Waisen in der Anlage". Beim ersten Mal war
     die Kopplungsprobe mitten im Lauf abgebrochen worden — dort ist die
     Herkunft plausibel. Beim zweiten Mal nicht: davor lagen nur ein
     Demo-Reset mitten in einem Bedienprobe-Lauf, Messläufe der
     Browserprobe und ein Neustart des Containers. Die Jobprobe räumt die
     Waisen selbst ab (Job `waisen`), der Wiederholungslauf war grün —
     gesehen hat sie danach niemand mehr. *Weg:* vor dem Abräumen nennen,
     WELCHE Waisen es sind (Tabelle, Kennung, Konto), und dann die Probe
     oder den Weg suchen, der sie hinterlässt. *Abnahme:* die Jobprobe
     nennt fremde Waisen mit Tabelle und Kennung; die Quelle ist gefunden
     oder als Grenze benannt. *Drittes Mal 28.09.2026 (SR-01), wieder zwei:*
     davor frische Anlage, einzeln Zweitfaktor-, Rollen-, Wartungs-,
     Protokoll-, Rückweg-, Sitzungsprobe, dann `proben.sh alle` bis `ingest`.

<!-- -->

353. **Die Schemaprobe vergleicht eine migrierte Tabelle nicht mit der frisch angelegten.** · gehört zu: 14 · Stand: offen · seit 28.09.2026
     *Aufgenommen 28.09.2026 in SR-09 (Konzept SR, F-SR-28).* Die Kommentare
     der Migrationen `2026_09_28_vertraute_geraete` und `2026_09_28_passkeys`
     sagten, die Schemaprobe halte fest, dass `schema.sql` und die Migration
     dieselbe Tabelle bauen. Sie tut es nicht: Sie spielt `schema.sql` auf
     vier Fassungen ein und prüft die Vorabliste. Ob eine Migration auf einem
     Altbestand dieselben Spalten, Schlüssel, Zeichensätze und Fremdschlüssel
     erzeugt wie die frische Anlage, misst niemand — eine abweichende
     Sortierfolge oder ein vergessener Index fiele erst auf einer migrierten
     Anlage auf, und dort erst, wenn eine Abfrage daran scheitert. Die
     Kommentare sind berichtigt; für die zwei Tabellen dieser Runde ist es
     von Hand gemessen (`SHOW CREATE TABLE` ohne `AUTO_INCREMENT`,
     zeichengleich, Prüfdokument SR).
     *Weg:* Die Plattformmatrix spielt je Fassung zusätzlich einen älteren
     Stand von `schema.sql` ein, führt die Migrationen aus und vergleicht
     `SHOW CREATE TABLE` jeder Tabelle mit der frisch angelegten; eine
     Abweichung ist rot. *Abnahme:* eine absichtlich abweichende Spalte in
     einer Migration → rot.

<!-- -->

355. **Ein Formularfeld als Liste gibt eine PHP-Warnung — `(string)$_POST[…]` an 114 Stellen.** · gehört zu: 22 · Stand: offen · seit 29.09.2026
     *Aufgenommen 29.09.2026 mit der dritten Lesung von H-SR-08 (Konzept SR,
     F-SR-65).* Aus `code[]=x` statt `code=x` macht
     `(string)($_POST['code'] ?? '')` den Text „Array" mit der Warnung
     „Array to string conversion", und der Behandler schreibt sie in den
     Reiter System — höchstens eine Zeile je Stelle und Anfrage. Für
     `passkey_antwort` ist das seit Web 21.11.0 behoben (F-SR-47); das
     Nachbarfeld `code` in `login.php` und `zweitfaktor.php`, `signatur` und
     `tokens` in `login.php` haben es noch — `tokens` im ersten Schritt, ohne
     Passwort, hinter dem Anmelde-Topf. `grep -rn '(string)($_POST['` über
     `server/` zählt 114 Stellen. *Weg:* ein Helfer `post_text()` als die
     eine Stelle (R83) und eine Registerzeile gegen das Muster. *Abnahme:*
     jedes Feld der Anmeldeschritte als Liste → Formularfehler, keine Zeile
     im Reiter System. *Seit Web 21.14.0 (SR-08, F-SR-96)* nimmt
     `registrieren.php` seine Felder über eine lokale Hülle `$feld` — dort
     war `email[]` sogar eine 500; jetzt 109 Stellen, und `$feld` steht mit
     `zweitfaktor_notweg.php` zweimal: beide gehen in `post_text()` auf.

<!-- -->

356. **Zwei Abschnitte der Karte „Schlüssel des Servers" kleben ohne Abstand am Knopffuß.** · gehört zu: 22 · Stand: offen · seit 29.09.2026
     *Aufgenommen 29.09.2026 mit SR-03 (Konzept SR, F-SR-73; gefunden an den
     Bildern zu P-SR-05).* „Nachtragen vom Blatt" und „Neuanfang" stehen als
     `h3.listen-form-titel` unmittelbar hinter `.listen-form-fuss` — die
     Überschrift hat oben keinen Abstand, und die Knöpfe darüber gehören
     optisch zu ihr. Für den neuen Abschnitt „Serverschlüssel wechseln" ist
     es in SR-03 behoben: Er steht in `.listen-form` (Linie und Abstand
     darüber). Die zwei alten erscheinen nur in der Lage *abweichend* und
     sind nicht angefasst, weil kein Bild sie in dieser Lage zeigt. *Weg:*
     beide Formulare ebenso in `.listen-form`. *Abnahme:* Bild der Karte in
     der Lage *abweichend* (Bilderlauf oder Bedienweg mit verstellter
     Marke), 390 und 1280 px.

<!-- -->

357. **Der Fehlertext eines Jobs trägt den Klassennamen der Ausnahme.** · gehört zu: 22 · Stand: offen · seit 04.10.2026
     *Aufgenommen 04.10.2026 mit H-SR-06 (Konzept SR, F-SR-82; Nebenbefund
     der Gegenlesung zu O-7).* `jobs_einen_lauf()` schreibt
     `get_class($ex) . ': ' . $ex->getMessage()` nach `letzter_fehler` und in
     den Bericht; „Jetzt weiterarbeiten" (Karte „Schlüssel des Servers") und
     die Karte „Jobs" zeigen ihn so der BetreiberIn —
     „RuntimeException: …" ist Technik, keine Auskunft. Betrifft alle Jobs,
     nicht nur den Schlüsselwechsel; deshalb nicht in H-SR-06 geändert. Im
     Reiter System steht die Klasse ohnehin in den Daten (`klasse`). *Weg:*
     im Bericht und in `letzter_fehler` nur die Meldung, die Klasse bleibt
     im Reiter System. *Abnahme:* ein Job, der wirft (Probe mit
     verfälschtem Häppchen) — die Karte zeigt die Meldung ohne Klasse, der
     Reiter System die Klasse. *Dazu (Nachprüfung H-SR-06, F-SR-87):* der
     Grund in der Zeile „Ließen sich nicht umhüllen" ist ebenfalls der rohe
     Ausnahmetext; eine PDOException trüge den Datenbanknamen, ein
     TypeError Pfade. Dieselbe Kürzung an derselben Stelle.

<!-- -->

358. **Die zweite Passwortseite: Token-Ableitung an einer Stelle, und die Wache vergleicht sie mit.** · gehört zu: 22 · Stand: offen · seit 04.10.2026
     *Aufgenommen 04.10.2026 mit SR-04 (Konzept SR, F-SR-90, E-SR-84).*
     Der Notzugang `zweitfaktor_notweg.php` leitet das Passwort-Token ab wie
     `login.php` (Salz über `auth_salt.php`, je Rundenzahl
     `EdCrypto.deriveKeys()`) — mit einer zweiten Kopie der rund zwanzig
     Zeilen, weil `login.php` für Schritt 18 zu ist (E-SR-14) und dort auch
     die Prüfung (`login_zeile()`, Token je `kdf_iter`, Blindvergleich) als
     lokaler Code steht. Dazu: Die Integritätswache vergleicht die
     Inline-Skripte der Seiten, auf denen ein Passwort getippt wird
     (`tools/integritaetswache/wache.py`, `SEITEN`) — dort steht nur
     `login.php`. Die neue Seite vorher einzutragen, machte die Wache rot,
     solange die Anlage sie nicht hat. *Weg:* eine Funktion in `crypto.js`
     (Salz holen, Token je Rundenzahl) für beide Seiten, die Prüfung als
     Funktion neben `AUTH_VERGLEICHSWERT`; nach der Auslieferung von
     Web 21.13.0 `zweitfaktor_notweg.php` in `SEITEN`. *Abnahme:* ein
     `fetch('auth_salt.php'` im Quelltext; die Wache misst zwei Seiten.

<!-- -->

361. **Die APK-Karte unterscheidet Handy und Wear-OS-Uhr nicht.** · gehört zu: 22 · Stand: offen · seit 30.09.2026
     *Aufgenommen 30.09.2026 nach dem ersten Tag `android-v0.17.0`
     (F-PK-58, Prüfdokument PK 5u).* Seit PK-08 legt die Kette zwei
     APKs nach `server/apk/` (E-PK-58). Einstellungen → Geräte, „Ohne Play
     Store", zeigt beide als „NAdoku 0.17.0"; nur die Größe verrät, welche
     die Uhr-App ist, und die steht oben, weil `apk_liste()` nach
     Ablagezeit sortiert. Wer auf den ersten Knopf tippt, lädt aufs Handy
     die Wear-OS-App. Q-PK-11 hatte das vorhergesagt und bewusst nicht in
     PK-08 gelöst: Es ist eine Oberflächenänderung.
     *Weg:* `apk_liste()` liest das Gerät aus dem Namen (`nadoku-uhr-…` →
     Wear-OS-Uhr, sonst Handy) und sortiert Handy vor Uhr; die Karte
     nennt es („NAdoku 0.17.0 · Handy"). Dieselbe Liste liest
     `betrieb_updates.php` und `apk.php` — beide mitprüfen. Eine
     Web-Nebenstufe.
     *Abnahme:* zwei Dateien im Ordner → die Karte zeigt Handy zuerst,
     jede mit ihrem Gerät; eine Datei ohne Muster (Altbestand) steht
     unverändert da.

<!-- -->

383. **Komplett- und Schlüsselwechselprobe rot unter dem PHP 8.3.6 aus Ubuntu.** · gehört zu: 22 · Stand: offen · seit 13.10.2026
     *Aufgenommen 13.10.2026 mit S11-03 (F-S11-26, -28, -33).* Unter dem
     PHP **8.3.6** aus Ubuntu 24.04 (OpenSSL 3.0.13, zlib 1.3) scheitern
     drei Fälle, die alle ein Komplett-Siegel über mehrere Häppchen
     schreiben: Komplettprobe Teil 4 „über drei Häppchen versiegelt …
     öffnet" und „Zieldatei verschwindet" (73 von 75, „verändert oder
     unvollständig (Block 1)"), Schlüsselwechselprobe Teil 4 „Komplett-Stand
     über den Wechsel" (65 ok, 1 fehlt). Auf dem Stand vor S11-03 ebenso.
     **Grün** unter PHP 8.3.33 (`nadoku-php83`) und 8.4.19 (75/0, 66/0) —
     gegen dieselbe Datenbank. S11 ist es nicht; ein Fehler der Probe wohl
     auch nicht, sonst wäre er überall rot.
     **Warum es zählt:** 8.3.6 ist die Vorgabe von Ubuntu 24.04 LTS. Steht
     eine Anlage darauf, geht ihre Komplettsicherung vielleicht nicht auf.
     *Weg:* den Siegelschub (`komp_siegel_schub()`) unter 8.3.6 Block für
     Block mit 8.4 vergleichen; Ursache benennen (PHP-Fehler, der bis
     8.3.33 behoben ist, oder Bibliothek); dann entweder umgehen oder eine
     Mindestfassung in `Technik.md` und im Installer festschreiben.
     *Abnahme:* die Ursache benannt, und unter 8.3.6 grün oder abgewiesen.

<!-- -->

384. **Das Abbild bringt PHP 8.3.6 statt 8.4.19 — `aufbauen.sh` merkt es nicht.** · gehört zu: 14 · Stand: offen · seit 13.10.2026
     *Aufgenommen 13.10.2026 mit S11-03 (F-S11-32).* Nach einem Neustart
     des Containers war `php` das 8.3.6 aus Ubuntu; `Sandbox-Setup.md` 1
     nennt 8.4.19, und `aufbauen.sh web` prüft die Fassung nicht. Docker
     Hub antwortete mit 429; `mirror.gcr.io/library/…` lieferte dieselben
     offiziellen Abbilder, die PPA von ondrej sperrt der Proxy (403).
     Behelf in S11-03: PHP 8.4.19 aus `php:8.4.19-cli` plus 14
     Erweiterungen gebaut, nach `/opt/php84` gezogen, mit `patchelf` auf
     eigene Bibliotheken gestellt, als `/usr/local/bin/php` vorgeschaltet;
     damit Komplett-, Schlüsselwechsel- und Schemaprobe grün.
     *Weg:* den Behelf als Teil `php84` in `aufbauen.sh` übernehmen, die
     Fassung nach dem Aufbau prüfen (rot unter 8.4), den Spiegel als
     Rückfall in `plattform.sh`. *Abnahme:* ein frischer Container steht mit
     einem Befehl auf 8.4, und ein falsches PHP ist rot.

<!-- -->

388. **Die Demo-Fixture lässt sich seit S11 nicht mehr aus dem Referenzkonto erzeugen.** · gehört zu: 14 · Stand: offen · seit 19.10.2026
     *Aufgenommen 19.10.2026 in S11-12 (F-S11-95).*
     `tools/referenzdatensatz/fixture/erzeugen.php` las das Referenzkonto
     über `edbak_build()` am Stück, mit Punktlisten aus `track_points`.
     Seit Web 22 sieht der Server keine Punkte, Phasenorte und Reanimation
     mehr; das Skript hätte eine Fixture ohne Spur geschrieben, still. Seit
     Web 23 gibt es den Bau am Stück nicht mehr; das Skript bricht mit
     Rückgabe 2 ab, und der Positivfall von `fixture/riegelprobe.php` ist
     damit ebenfalls rot. Die eingecheckte Fixture (Nutzlast 7, Klartext
     aus dem Repositorium, E-S11-20) trägt den Demo-Reset weiter; sie
     altert nur, wenn sich der Referenzbestand ändert.
     *Weg:* die Fixture im Browser erzeugen (wie `referenz_export.mjs`:
     Sicherung des Kontos öffnen, Klartext ablegen) oder aus den
     Quelldaten des Generators bauen, ohne Umweg über ein Konto.
     *Abnahme:* eine neu erzeugte Fixture setzt das Demo-Konto zurück, und
     die Riegelprobe ist in beiden Richtungen grün.


<!-- -->

391. **Elf Browserwerkzeuge ruft kein Prüfstand.** · gehört zu: 14 · Stand: offen · seit 19.10.2026
     *Aufgenommen 19.10.2026 in DM-03.* Beim Anlegen des Registers
     `tools/pruefstand/motoren.md` fiel auf: Kopplungsrundlauf,
     Betriebs- und Umstellungslauf der Anteilprobe, Klickweg der
     Komplettprobe, sechs Werkzeuge unter `referenzdatensatz/browser/`
     (Angriffswerte, CSV-Import, Demo-Bremse, Demo-Prüfung,
     Papierkorb-Mischung, Referenzexport) und die Sichtprüfung des
     Einspielens laufen nur, wenn jemand sie von Hand startet. Seit DM
     können sie alle drei Engines, aber ob sie noch grün sind, weiß
     niemand. Die Angriffswerte (R20) sind dabei die einzige Messung, dass
     entschlüsselte Felder im Browser nicht als Markup gesetzt werden.
     *Weg:* je Werkzeug entscheiden — in eine Probe hängen (Muster und
     `"motoren": true`) oder löschen; „von Hand" bleibt nur mit Grund.
     *Abnahme:* keine Zeile „von Hand" im Register ohne eigenen Grund.
     *Ergänzt 30.10.2026 mit R17-28:* Die Sichtprüfung sucht die Diagnose
     über „🔒" — das Schloss ist seit S10 ein SVG, sie meldet falsch rot.
     Dazu `fixture/riegelprobe.php`: erwartet Rückgabe 0 von `erzeugen.php`,
     das seit Web 23 mit 2 abbricht (Nr. 388); Teil 1 und 2 dauerhaft rot.

<!-- -->

392. **Das Schlüsselpaar entsteht nur, wer auf der ersten Seite nach der Anmeldung bleibt.** · gehört zu: 22 · Stand: offen · seit 19.10.2026
     *Aufgenommen 19.10.2026 in DM-02 (F-DM-02).* `unlock.js` legt das Paar
     des Kontos (und den Rückweg) still an, sobald der Inhaltsschlüssel da
     ist — und nur mit dem Anmelde-Token, das es allein auf der ersten
     Seite nach der Anmeldung gibt. Die Freigabeprobe ging sofort auf
     `einstellungen.php` weiter: In Chromium kam das Paar trotzdem an, in
     Firefox und WebKit in keinem Lauf (2 von 2). Wartet sie auf der ersten
     Seite, steht es nach 1152 ms (Firefox), 596 ms (WebKit), 599 ms
     (Chromium). Wer die Seite schneller verlässt, hat bis zur nächsten
     Anmeldung kein Paar: Geräte können nicht senden, eine Freigabe weist
     mit „kein Schlüsselpaar" ab. Nicht gemessen: ob es ein Mensch je
     schafft, und ob der Rückweg dasselbe Los hat.
     *Weg:* das Token bis zum gelungenen Anlegen im Sitzungsspeicher
     halten, oder das Anlegen auf der zweiten Seite nachholen.
     *Abnahme:* die Freigabeprobe ohne Warten auf der ersten Seite grün,
     in drei Engines.

<!-- -->

400. **Die Rechtstexte auf den Stand nach S11 bringen.** · gehört zu: 14 · Stand: offen · seit 25.10.2026
     *Aufgenommen 25.10.2026 mit R17-18 (F-R17-58, -60; E-R17-29).* AVV
     und Nutzungsbedingungen sagen noch, GPS-Aufzeichnung,
     Phasenkoordinaten, Transportziel und Reanimation lägen im Klartext,
     und der Einsatzort sei daraus rekonstruierbar — seit Web 22 falsch.
     Die Betreiberin berichtigt sie selbst, direkt vor dem v1.0-Tag.
     *Stellen:* `AVV.md` Kopfkasten, A.2, Anlage II Nr. 2 (beide Listen,
     „Grenze … durchlässig", besondere Kategorien), Prüfhinweis (a) — der
     Schluss trägt weiter, die Begründung nicht; `Nutzungsbedingungen.md`
     4.4 und Hinweis (d); `Datenschutzerklaerung.md` Kopfkasten
     (einspielbar seit S11 auf Produktiv), Prüfhinweise 5 und 7 und der
     Verweis „Grenze 2" in 0 (d) (die Grenze ist mit Web 23 gefallen).
     *Preis:* AVV und Nutzungsbedingungen sind auf Produktiv eingespielt;
     eine neue Fassung sperrt den Login bis zur Annahme (`Technik.md`
     4.31) — vorher die Ankündigung setzen.
     *Abnahme:* die neun Ausnahmen der Klasse `Bestand` in
     `textprobe-ausnahmen.json` (`avv-…`, `nb-…`) greifen nicht mehr und
     sind gestrichen, die Textprobe ist grün; P-R17-13 auf Produktiv.

<!-- -->

409. **Code-Kommentare erzählen noch Geschichte, ohne Kennung.** · gehört zu: 13 · Stand: offen · seit 30.10.2026
     *Aufgenommen 30.10.2026 mit R17-28.* Der Kommentardurchgang hat
     Kennungen, Backlog-Nummern und eigene Fassungsangaben aus den
     Kommentaren genommen (E-R17-86). Stehen geblieben sind drei Gruppen:
     (1) erzählende Sätze ohne Kennung — „früher stand hier …", „bis
     dahin …", „seither …" —, rund 500 Stellen, gezählt 30.10.2026 über
     `server/` (290), `tools/` (198), `android/` (17), `watch/` und
     `.github/` (je 5 und 7); viele tragen den Grund einer Regel und
     brauchen eine Umformulierung in die Gegenwart, kein Streichen.
     (2) Kennungen in Zeichenketten — Meldungen, Prüfdaten der
     Android-Tests, die Spaltentitel `(E8)` im Export — und in
     `<!-- -->` und eingebetteten `<script>` der Seiten, 384 Stellen in
     `server/`. (3) `watch/source/HuelleVektor.mc` wird aus
     `tools/proben/huelle/referenz.py` erzeugt; seine Kennungen ändern sich
     nur dort.
     *Weg:* (1) Datei für Datei lesen, je Satz entscheiden; (2) nur, wo die
     Zeichenkette niemanden außerhalb erreicht. Gegenprobe wie in R17-28:
     Code ohne Kommentare vorher gegen nachher. *Abnahme:* (1) 0 Treffer
     der Wortliste außerhalb einer begründeten Ausnahme.

<!-- -->

410. **Der Eichlauf des Messstands misst die Arbeit des Renderers nicht.** · gehört zu: 14 · Stand: offen · seit 31.10.2026
     *Aufgenommen 31.10.2026 mit R17-29 (F-R17-87).* Bei gleich schnellem
     Eichlauf (861 gegen 856 bis 987 ms bei 6×) lagen die Einzelansichten
     am 31.10.2026 zwei- bis dreimal über dem 18.10.2026: Einsatz 4,15
     statt 1,39 s, Diensttag 7,95 statt 2,94 s. Die Zeit lag vor
     `DOMContentLoaded`, in Parsen, Stil, Layout und SVG-Dokumenten — das
     misst der Eichlauf nicht, er rechnet nur JavaScript. Chromium 153 war
     15 bis 25 % schneller als 156 (Playwright 1.64.1, die Grundlinie lief
     mit 1.56); ob der Rest am Wirt oder am Code liegt, ist offen.
     *Weg:* einen zweiten Eichlauf für den Renderer — eine feste Seite aus
     dem Repositorium, gemessen bis `DOMContentLoaded` — und die
     Browserfassung ins Protokoll. *Abnahme:* zwei Läufe auf verschiedenen
     Wirten trennen Maschine und Code in der Zerlegung.

<!-- -->

411. **Der Stilvergleich hat eine Liste geplanter Abweichungen für drei Engines.** · gehört zu: 14 · Stand: offen · seit 31.10.2026
     *Aufgenommen 31.10.2026 mit R17-29 (F-R17-91).* Grün ist der
     Stilvergleich, wenn die gemessenen Signaturen der Liste
     `tools/stilvergleich/geplant.txt` GLEICHEN. Seit R87 läuft er in drei
     Engines, und die benennen dieselbe Verschiebung verschieden: Chromium
     `bottom, inset, top`, Firefox `bottom, top`, WebKit mit `size`; WebKit
     misst dazu das `svg` im Schattenbaum eines `<use>`. Gemessen mit einer
     Liste aus Chromium: Firefox 5, WebKit 6 ungeplant. Eine gewollte
     Gestaltungsänderung, die etwas verschiebt, wird damit nirgends ganz
     grün; R17-29 ist ausgewichen (keine Stilregel nötig).
     *Weg:* je Engine eine Liste (`geplant-<motor>.txt`), `--schreiben`
     schreibt alle drei; oder die Signatur auf eine gemeinsame Menge von
     Eigenschaften abbilden (`inset` aus `top`/`bottom`, ohne `size`).
     *Abnahme:* eine Probe-Änderung an `style.css`, `--schreiben`, danach
     drei Engines grün; eine zweite, ungeplante Änderung rot in allen dreien.

<!-- -->

430. **Bitwarden zeigt beim Backup mit Kontopasswort den Eintrag nicht im Menü am Feld.** · gehört zu: 22 · Stand: offen · seit 02.11.2026
     *Aufgenommen 02.11.2026 aus P-R17-34 (Handprüfung auf Produktiv,
     Firefox mit Bitwarden).* Seit Web 23.4.0 bietet Bitwarden im Feld
     „Kontopasswort" (Einstellungen → Backup) kein neues Passwort mehr an —
     die Hälfte von Nr. 405 ist behoben. Das Menü am Feld zeigt aber „No
     items to show", obwohl der Eintrag für `nadoku.gen-em.org` (Erkennung
     „Host") auf `login.php` angeboten wird. Über das Symbol in der Leiste
     und mit Strg + Umschalt + L füllt Bitwarden das Feld richtig; das sind
     die Auswege bis dahin. *Vermutung, nicht geprüft:* Im selben Formular
     stehen die zwei Felder für ein eigenes Backup-Passwort
     (`autocomplete="new-password"`), und Bitwarden stuft das Formular als
     Registrierung oder Passwortwechsel ein. *Weg:* „Kontopasswort" in ein
     eigenes Formular ohne `new-password`-Felder setzen. *Abnahme:* in einem
     echten Browser mit Bitwarden bietet das Menü am Feld den Eintrag an;
     die Sandbox hat kein Bitwarden.

<!-- -->

432. **„App installieren" nennt beide APKs gleich.** · gehört zu: 22 · Stand: offen · seit 02.11.2026
     *Aufgenommen 02.11.2026 bei P-R17-05; Doppelung von Nr. 361, wird dort
     erledigt (E-BE-07).* Unter „Ohne Play Store: APK von
     Hand" stehen zwei Zeilen „NAdoku 0.18.2"; welche die Wear-OS-Uhr ist,
     zeigt erst der Dateiname nach dem Herunterladen
     (`nadoku-uhr-0.18.2.apk`). Die Zeile in `einstellungen.php` setzt nur
     „NAdoku" und die Fassung zusammen, `apk_liste()` liest das `-uhr-` gar
     nicht aus. *Weg:* `apk_liste()` gibt das Gerät mit (Name enthält
     `-uhr-` → Uhr, sonst Handy), die Zeile heißt „NAdoku für Android-Handy"
     bzw. „NAdoku für Wear-OS-Uhr"; dieselbe Benennung in Betrieb → Updates,
     wo `apk_liste()` auch gelesen wird. *Abnahme:* beide Zeilen tragen ihr
     Gerät, die Prüfsummen bleiben an ihrer Zeile.

<!-- -->

440. **Beim Löschen eines Geräts warnt das Web nicht vor ungesendeten Aufzeichnungen.** · gehört zu: 22 · Stand: offen · seit 02.11.2026
     *Aufgenommen 02.11.2026 mit E-BE-08 (aus der Frage in Nr. 431).* Wer
     ein Gerät unter Einstellungen → Geräte oder in der Verwaltung löscht,
     nimmt ihm den Schlüssel; was noch auf Uhr oder Handy wartet, kommt bei
     diesem Konto nie mehr an. Das Web sagt davon nichts, obwohl es die Zeit
     des letzten Kontakts kennt. Für eine verlorene Uhr ist Deaktivieren der
     richtige Griff (Runbook „Gerät verloren"): wirkt sofort, Daten bleiben,
     und die Uhr sendet nach dem Reaktivieren weiter. *Weg:* Die Rückfrage
     vor dem Löschen nennt den letzten Kontakt und rät bei längerer Pause
     zum Deaktivieren; derselbe Satz in Handbuch und Runbook. *Abnahme:*
     Löschen eines Geräts mit Kontakt vor mehr als einem Tag → Rückfrage
     mit Datum und Hinweis; ein Gerät mit Kontakt heute → Rückfrage ohne
     Hinweis; `tools/zaehlung/` ohne neue Stelle (Meldung über `ui_`).

<!-- -->

450. **Die Wegwerfkonten der Kette füllen auf Staging den Reiter System mit Mailfehlern.** · gehört zu: 22 · Stand: offen · seit 02.11.2026
     *Aufgenommen 02.11.2026 im ersten Prüfdurchgang der Arbeitsumgebung
     (R91, P-P5c-12).* Stufe 2 legt je Lauf und Engine Konten unter
     `…@example.invalid` an, meldet sie an und löscht sie wieder. Jede Mail an
     sie scheitert beim Mailserver von Staging mit „Server antwortete 450";
     an einem Tag standen so über 100 gleiche Einträge im Reiter System, die
     Statusseite zeigt dauerhaft „Letzter Versand fehlgeschlagen", und eine
     Einladung wartet 24 Stunden auf Wiederholung. Echte Fehler gehen darin
     unter. Mails an echte Adressen kommen an (Einladung, erster Versuch).
     *Weg:* Adressen unter `.invalid` gar nicht erst einreihen — die Endung
     ist nach RFC 2606 nie zustellbar —, mit einem Satz im Protokoll statt
     einer Störung; oder die Kette nimmt eine zustellbare Prüfadresse.
     *Abnahme:* nach einem Stufe-2-Lauf keine neue Zeile „smtp … 450" im
     Reiter System, Status „Letzter Versand" unverändert.

<!-- -->

454. **Der Mailjob versucht eine dauerhafte Absage 24 Stunden lang weiter.** · gehört zu: 22 · Stand: offen · seit 02.11.2026
     *Aufgenommen 02.11.2026 im ersten Prüfdurchgang der Arbeitsumgebung
     (R91, P-P5c-03).* Die Rundmail an 8 Staging-Konten: 5 zugestellt,
     zwei mit „Server antwortete 550" (das Postfach gibt es nicht), eine
     mit 450 (Nr. 450). Alle drei stehen danach auf „wartet" und laufen die
     ganze `MAIL_LEITER` (5 min bis 24 h) durch. Ein 5xx ist nach RFC 5321
     endgültig; jeder weitere Versuch ist eine Störung im Reiter System und
     verlängert „Letzter Versand fehlgeschlagen" auf der Statusseite.
     *Weg:* `mail_zeile_versuchen()` setzt bei einer 5xx-Antwort sofort
     `unzustellbar`, mit der Absage als Grund; 4xx und
     Verbindungsfehler bleiben auf der Leiter.
     *Abnahme:* eine Mail an eine Adresse ohne Postfach steht nach dem
     ersten Versuch unter „unzustellbar", ohne zweite Störung.

<!-- -->

470. **Auf der Venu 3s schneidet der Kreis die unterste Meldung der Sync-Seite ab.** · gehört zu: nächste Backlog-Runde · Stand: offen · seit 02.11.2026
     *Aufgenommen 02.11.2026 aus GR-06 (Konzept GR, F-GR-11).* Seit Uhr
     4.3.1 überlappen sich Mittelblock und Meldungen nicht mehr; bei sechs
     und sieben Meldungszeilen steht die unterste („Action halten: Gerät
     koppeln") aber so tief, dass die Sehne schmaler ist als die Zeile, und
     `Ui.fitFont()` hat keine kleinere Schrift als `FONT_XTINY` — beide Enden
     fehlen. Zu sehen auf den Bildern venu3s-6 und venu3s-7 der Bildprobe
     `tools/uhr-pruefstand/syncseite/`; die Probe misst nur Höhen, keine
     Breiten. *Weg:* die Zeile kürzen, wenn sie nicht passt („Koppeln:
     Action halten"), oder die Breite in die Probe aufnehmen. *Abnahme:*
     Bildprobe Venu 3s mit 6 und 7 Zeilen, jede Zeile ganz lesbar.

<!-- -->

471. **Ein abgemeldetes Handy hält seinen Klartextpuffer ohne Frist.** · gehört zu: nächste Backlog-Runde · Stand: offen · seit 02.11.2026
     *Aufgenommen 02.11.2026 aus GR-03 (Konzept GR, F-GR-07).* Die
     Garmin-Uhr verwirft ihren Puffer 30 Tage nach der ersten 401/403
     (Lage H im Bedrohungsmodell); das Handy hat dafür keine Frist. Es führt
     seit Android 0.20.0 einen dauerhaften Abmeldevermerk
     (`Rueckstandsbindung`), aber keinen Zeitpunkt. Ein im Web gelöschtes,
     verlorenes Handy hält Spur und Phasen damit unbegrenzt, auch einen
     gebundenen Rückstand. *Weg:* den Zeitpunkt der ersten Abmeldung im
     Vermerk ablegen und nach `Raeumung.FRIST_TAGE` alle Pakete der
     Kopplung räumen, wie die Uhr; Bedrohungsmodell Lage H nachziehen.
     *Abnahme:* Prüffall im `SenderTest` mit gestellter Uhr — 30 Tage nach
     der ersten 401 ist der Puffer leer.

<!-- -->

456. **Die Reanimationskarte sagt im gesperrten Formular „keine".** · gehört zu: 22 · Stand: offen · seit 03.11.2026
     *Aufgenommen 03.11.2026 im Prüfdurchgang der Arbeitsumgebung (R91,
     P-S11-18).* Ist der Inhaltsschlüssel gesperrt, öffnet
     `einsatz_form.php` die Einsatzhülle nicht, `#rearows` bleibt leer, und
     `reaVorschau()` schreibt „keine" in den Kopf der Karte — auch an einem
     Einsatz mit Reanimation. Gespeichert werden kann nichts (die Leiste
     verlangt zuerst das Entsperren, gemessen), und „Reanimation
     hinzufügen" tut nichts; die Angabe ist aber falsch, wo sie
     „unbekannt" heißen müsste. *Weg:* gesperrt „gesperrt" statt der Zahl,
     wie die Karte „Wartet auf dein Passwort" in der Ansicht.
     *Abnahme:* gesperrtes Formular eines Einsatzes mit Reanimation — der
     Kartenkopf sagt nicht „keine".

<!-- -->

458. **Auf MySQL 8 bricht der Wartungslauf der Spuren bei jedem Aufruf ab.** · gehört zu: nächste Backlog-Runde · Stand: offen · seit 03.11.2026
     *Aufgenommen 03.11.2026 im Prüfdurchgang der Arbeitsumgebung (R91,
     P-S11-14, -30).* `spur2_faellig_kandidaten()` in `spur_lib.php`
     gruppiert nach `o.id`, die Unterabfrage `geschnitten` (10. Ausdruck)
     bezieht sich aber auf `s.owner_type` und `s.owner_id`. MySQL 8.4 mit
     der Vorgabe `ONLY_FULL_GROUP_BY` lehnt das ab (SQLSTATE 42000/1055);
     `api/wartungslauf.php` antwortet 500, auf Staging bei jedem Seitenaufruf
     mit Spuren, seit dem 19.10.2026 55 Einträge im Reiter System. Folge:
     Auf Staging wird nichts verdichtet oder ausgedünnt („Fällig
     unbekannt"), P-S11-30 ist dort nicht prüfbar. Produktiv (MariaDB
     10.11) ist nicht betroffen. Die Plattformprüfungen gegen MySQL 8.0
     und 8.4 waren grün (50/0) — sie erfassen diese Abfrage nicht.
     *Weg:* `s.owner_type`/`s.owner_id` in der Unterabfrage durch den
     Parameter und `o.id` ersetzen (der Typ ist je Durchgang fest), und
     die Abfrage in eine Probe gegen MySQL 8 nehmen.
     *Abnahme:* auf Staging keine neue Zeile „wartungslauf … 1055",
     `server/api/wartungslauf.php` antwortet 200, „GPS-Daten verdichten" nennt eine Zahl.

<!-- -->

459. **Schon das Ansehen setzt das Demo-Konto auf „geändert".** · gehört zu: nächste Backlog-Runde · Stand: offen · seit 03.11.2026
     *Aufgenommen 03.11.2026 im Prüfdurchgang der Arbeitsumgebung (R91,
     P-R4-15).* Die Änderungsmarke des Demo-Kontos setzt `auth_guard.php`
     bei jedem POST (`demo_lib.php`, „Die Änderungsmarke"). Die Startseite
     schickt beim Laden zweimal `POST api/wartungslauf.php`. Gemessen auf
     Staging nach einem frischen Reset: nach der Anmeldung „Geändert:
     nein", nach dem Öffnen der Startseite „Geändert seit 09:57 · Nächster
     Reset in 30 Minuten", der Hinweis sagt „in etwa 30 Minuten" — ohne
     jede Änderung. Wer nur nachsehen will, trägt damit nach 30 Minuten
     den Reset und fliegt aus der Sitzung; genau das sollte die Marke
     vermeiden. Der Wartungslauf schickt seine POSTs auch auf Produktiv.
     *Weg:* den Wartungslauf für das Demo-Konto auslassen oder seine POSTs
     von der Marke ausnehmen; andere Hintergrund-POSTs mit prüfen.
     *Abnahme:* frischer Reset, Anmelden, drei Seiten ansehen —
     Verwaltung → Demo-Konto „Geändert: nein".
