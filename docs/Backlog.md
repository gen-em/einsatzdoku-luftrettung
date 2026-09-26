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

**Die Kopfzeile ist Pflicht** (E-SD-16), in genau dieser Form:
`NNN. **Titel.** · gehört zu: ZIEL · Stand: STAND · seit DD.MM.YYYY`.
ZIEL ist eine Kennung aus der Fahrplan-Tabelle des Rahmenplans (`17`, `12a`,
`PK`, `Kette II` …, bei Bedarf mit Paket wie `10c AP7`) oder eines von
`nächste Backlog-Runde` · `Zuarbeit` · `Pflegeaufgabe` · `nach v1.0`. STAND
ist `offen` · `teilweise` · `zurückgestellt` · `nur auf Anlass` ·
`nicht umsetzen`; das Letzte heißt: entschieden, nicht gebaut, und der Punkt
bleibt hier, weil nichts erledigt wurde. Ein Eintrag hat höchstens
**20 Zeilen**, die Kopfzeile eingeschlossen (E-SD-03); jede Folgezeile
beginnt mit **fünf Leerzeichen** (E-SD-17 — mit vier rendert GitHub ab
Nr. 100 einen Codeblock, Nr. 196). Ein gekürzter Eintrag endet mit
`Werdegang bis DD.MM.YYYY: \`docs/Backlog.md@abc1234\`, Nr. NNN.`; dort
steht die lange Fassung. Die Decken misst `tools/steuerung/decken.py`, und
Stufe 1 ist rot, wenn eine reißt.

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
gestrichen; die nächste freie Nummer steht in der letzten Zeile.

| Spanne | Zweig | seit |
|---|---|---|
| 339 | Abschluss BV auf `claude/schritt-17-hl9egt` — Q-BV-05, der Nummernriegel (E-BV-19) | 26.09.2026 |
| 340 bis 349 | `claude/schritt-17-hl9egt` — Konzept 17, Backlog-Runde 4 | 26.09.2026 |
| ab 350 | frei — höchste vergebene Nummer 339; 338 war für AR reserviert und blieb frei (`origin/main` `056781c`) | 26.09.2026 |

---

## Offen

21. **Die 43 weiteren Funde der A4-Nachlese sichten.** · gehört zu: 12 · Stand: offen · seit 23.08.2026
     Die Erhebung „toter Code" in P0/A4 hat mit einer zweiten, breiteren
     Methode 43 zusätzliche Kandidaten geliefert (Abschnitt 9.3 des
     P0-Konzepts). Sie sind **nicht** freigegeben und **nicht** angefasst: Ein
     großer Teil berührt Antwortverträge (`api/`, Export, Backup), und dort ist
     „niemand liest es" keine hinreichende Begründung — ein Feld kann für ein
     älteres Backup oder eine künftige Uhr-Fassung gebraucht werden. Eigenes
     Paket mit eigener Freigabe. **Entschieden am 12.09.2026: an den P6-Review
     übergeben** (R69). Die Quellliste — Abschnitt 9.3 des P0-Konzepts —
     existiert nicht mehr; das Konzept steht im Rahmenplan (Abschnitt 8)
     ausdrücklich als „nicht im Repositorium". Der Review geht ohnehin alles
     durch und findet die Kandidaten neu; ein eigenes Paket vorher wäre Arbeit
     ohne Liste. Zuordnung: **P6**.

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

36. **Ein Prüfmittel für Klassennamen, die JavaScript sucht und niemand mehr vergibt.** · gehört zu: 17 · Stand: teilweise · seit 30.08.2026
     Befund: Ein Selektor, der ins Leere greift, ist in JavaScript kein
     Fehler, sondern eine leere Liste — in P3/O6 wirkte deshalb drei Pakete
     lang kein Filter der Suchseite (`.filterspalte` war beim Umzug in die
     Leiste verschwunden, F-P3-AG). Dieselbe Lücke von der anderen Seite:
     Töne, die PHP zur Klasse zusammensetzt (`'plakette-' . $ton`), gab es
     viermal ohne Regel im Stylesheet (`warn`, zweimal `ok`, `info`).
     Wirkung: Die Seite sieht fast richtig aus; kein Mittel meldet es.
     Weg: Die billige Hälfte ist gebaut (P5b/AP9, Web 20.24.0):
     `tools/quelltext/vollstaendigkeit.py` hält die Werte von `ui_plakette`,
     `ui_knopf`, `ui_kennzahl` und `ui_meldung_markup` gegen das Stylesheet,
     Gegenprobe mit beiden Fehlern gefahren. Offen ist die andere Hälfte:
     Klassennamen, die JavaScript in Selektoren nennt, und Töne, die als
     Variable übergeben werden — dafür braucht es eine Ausnahmeliste mit
     Begründung (Klassen, die JS selbst vergibt; zusammengesetzte Selektoren
     sind statisch nicht auflösbar), keine Ja/Nein-Regel.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 36.

37. **Wie verhält sich die Anwendung, wenn ein Konto über Jahre wächst?** · gehört zu: 17 · Stand: teilweise · seit 30.08.2026
     Befund (Messstand, 5050 Einsätze, CPU sechsfach gedrosselt, 16.09.2026):
     Die **Zeitraumübersicht** ist der Engpass — `zeitraum.php` ruft
     `EdMissionTable.erzeuge` ohne `seite`, bei 3983 Einsätzen im Jahr
     42,61 s bis zur ersten Zeile und 3983 `<tr>` im DOM; die Suche über alle
     5050 braucht 3,18 s (Seitengrenze 200). Der Suchindex überträgt den
     gesamten Bestand (1097 Byte je Einsatz), obwohl rund dreißig Filter auf
     Klartextspalten serverseitig vorschneiden könnten. Sechs stille Kappungen
     sagen nichts (`dt_liste($userId, 500)`, 120 in `api/day.php`, 400 in
     `einsatz_verschieben.php`; laut sind nur Import 3000 und Export 5000).
     Fünf von sechs Zielzahlen aus E-S2-24 sind gehalten, Spuren 3,66 statt
     3 MB je 1000 Einsätze knapp verfehlt (an frisch eingespieltem Bestand).
     Erledigt: der Deckel je Konto (P5b/AP6, Web 20.21.0: 5000 Einsätze,
     250 MB, `507`), die Kontenachse (P3/O9b), Speicher und Wartung (S2).
     Weg: Seitengrenze oder Monatsvorwahl für die Zeitraumübersicht als
     eigenes Paket; Vorschneiden im Suchindex; die Kappungen benennen.
     `post_max_size` der Zielanlage steht auf Betrieb → Status (Plattform-
     karte) — seit Staging läuft, ist das ein Seitenaufruf, keine Messung.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 37.

43. **Ortsdaten: die GPS-Spur ist nicht verschlüsselt — und das Transportziel auch nicht.** · gehört zu: 12a · Stand: offen · seit 30.08.2026
     Befund: Einsatzort und Adresse sind Ende-zu-Ende verschlüsselt, die
     Spur dorthin, die Koordinate jeder Phase (Phase 7 „Ankunft Klinik") und
     `missions.transport_dest` mit `dest_lat`/`dest_lon` liegen im Klartext.
     Der Ort ist nominell geschützt und faktisch rekonstruierbar. Der Server
     rechnet mit den Koordinaten nicht; die Uhr hat keinen Schlüssel und kann
     nur Klartext liefern (`Konzept-V1-Ortsdaten.md`).
     Entschieden 06.09.2026 (R78): Weg C sofort (die Zusage eingegrenzt —
     erledigt mit Web 15.6.0, Nr. 138); **Weg B als Schritt 12a (S11)** nach
     P6 und vor der Öffnung: Konto-Schlüsselpaar (Nr. 53), Umfang Spur,
     Phasenkoordinaten, Reanimationsereignisse und Zielklinik samt Koordinate
     (ausdrücklich bestätigt 10.09.2026), Altbestand per Einmalwerkzeug im
     Browser. Die Uhr kann es: ECDH P-256, AES-256-CBC, HMAC-SHA256 ab
     Connect IQ 3.0.0. Skizze SP-9 in `Vorbereitung-Sicherheitspaket.md`.
     Nicht vorgezogen, weil das Transportziel allein nichts verbirgt, solange
     `mission_phases` die Koordinate der Ankunft trägt.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 43.

46. **Das Altformat des Backups wird mit NaDoku 1.0 abgeschafft.** · gehört zu: 14 · Stand: teilweise · seit 31.08.2026
     Befund: Seit Web 11.0.0 schreibt die Anwendung Containerfassung 4; die
     Fassungen 2 und 3, Nutzlast 6 bis 11, unversiegelte Adminpakete der
     Fassung 2 (`edbak_teil_oeffnen()`) und das Feld `stammdaten` werden nur
     noch gelesen. Zum Stichtag entfallen: der Lesezweig in `crypto.js`
     (`openBackup`), der Punktlisten-Rückweg in `backup_lib.php`, die Annahme
     alter Nutzlasten in `api/backup_restore.php`, die Weiche in
     `kreislauf.py` (`umlauf_edbak()`, `--art edbak-alt`) samt Referenz unter
     `referenz/altformat/`. Der `ENUM`-Wert `ftp` ist mit Nr. 168 gefallen
     (Web 21.0.0). **Der Messstand hängt am Altformat-Lesepfad**
     (`vervielfaeltigen.py` versiegelt einteilig) und braucht zum Stichtag
     einen Container-Schreiber oder den Weg über die Uhr-Schnittstelle.
     Wirkung: Ein Lesezweig, den niemand pflegt, wird mitgeschleppt — 2026
     hat er einmal 91 208 Punkte still verworfen (F-S2-E).
     Weg: ein Paket mit Nr. 187 (Anhebungswege); die Meldung nennt danach die
     letzte Fassung, die noch lesbar war. Der eigene Bestand kommt nach
     E-P5c-127 über ein Einmal-Skript herüber (Nr. 324) — die 1.0 liest
     keine alte Nutzlast, auch nicht die letzte.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 46.

50. **Der Versand liest je Konto ein Verzeichnis.** · gehört zu: nach v1.0 · Stand: offen · seit 01.09.2026
     `sz_versand_schub()` fragt für jeden Kontoordner die Verzeichnisliste des
     Ziels ab, um zu erkennen, was dort fehlt. Bei 33 Ordnern ist das
     unauffällig (gemessen: SFTP 3,08 s für 64 Dateien einschliesslich aller
     Listen); bei dreihundert Konten sind es dreihundert Abfragen je Lauf und je
     Ziel, über eine Leitung, die kein Loopback ist.

     Der Ausweg ist **nicht** eine Merkliste in der Datenbank — die behauptet
     „schon versandt" auch dann noch, wenn die Datei am Ziel gelöscht oder das
     Ziel neu aufgesetzt wurde, und diese Art Lüge fällt erst auf, wenn man das
     Backup braucht (Begründung in Technik 4.97c). Denkbar ist stattdessen,
     die Liste je Ziel **einmal rekursiv** zu holen, wo das Protokoll es
     hergibt, und nur bei Zweifel nachzufragen. Erst messen, dann bauen.

51. **Die Suchseite verarbeitet 5 000 Einträge, um 200 zu zeigen.** · gehört zu: nach v1.0 · Stand: offen · seit 01.09.2026
     Befund (S2/AP9, 5002 Einsätze, Drossel 6×): 3,77 s bis die geschützten
     Spalten lesbar sind, davon rund 0,1 s Entschlüsselung der 200 gezeigten
     Zeilen. Entschlüsselt werden alle 5002, weil die Freitextsuche auch in
     Diagnose, Alter und Einsatzort sucht; ohne Filter braucht es sie nicht.
     Weg: Entschlüsselung auf das Gebrauchte verschieben (angezeigte Zeilen
     zuerst, Rest im Hintergrund oder beim ersten Filter). Kein Umbau des
     Suchindex (E-S2-16). Vorher messen, welcher Posten vor der Tabelle
     wiegt — „es ist die Krypto" war in AP9 schon einmal falsch.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 51.

52. **WebDAV als viertes Backup-Ziel.** · gehört zu: nach v1.0 · Stand: offen · seit 01.09.2026
     Aus E-S2-22, dort ausdrücklich in den Backlog verwiesen. Die Schnittstelle
     `Zielweg` (`sicherungsziel_lib.php`) ist genau dafür gebaut: verbinden,
     Ordner anlegen, senden, holen, auflisten, löschen — ein vierter Adapter
     berührt weder den Versandjob noch das Komplettbackup.

     **Warum es trotzdem nicht nebenbei geht:** WebDAV ist HTTP, und die
     Anwendung hat für ausgehendes HTTP bisher keinen Weg. Nötig wären
     `ext/curl` (auf Webspace meist da, aber nicht sicher) und eine Entscheidung
     über die Zertifikatsprüfung — bei FTPS prüft `ext/ftp` nichts, bei WebDAV
     über curl **kann** man prüfen, und dann sollte man auch. Das ist eine
     Festlegung und kein Handgriff.

53. **Konto-Schlüsselpaar für versiegelte Serversicherungen.** · gehört zu: 12a · Stand: offen · seit 01.09.2026
     Befund (E-S2-19): Nächtliche Backups je Konto ohne Browser sind
     abgelehnt, weil der Server den Inhaltsschlüssel nicht hat und nicht
     bekommen soll. Ein öffentlicher Schlüssel je Konto schlösse die Lücke:
     Der Server versiegelt ohne Geheimnis, öffnen kann nur die NutzerIn.
     Entschieden 06.09.2026 (R78): Dasselbe Paar ist der Schlüssel auf der
     Uhr für Weg B (Nr. 43) — privater Teil unter dem Inhaltsschlüssel
     gehüllt wie `pat_wrap_rc`, öffentlicher Teil ans Gerät; ein
     Passwortwechsel berührt ihn nicht. Offen bleibt, ob ein Backup, das nur
     die NutzerIn öffnen kann, für die Verwaltung eine Rückfallebene ist —
     bei einem verlorenen Konto ist sie es nicht.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 53.

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

62. **Logodateien tragen teilweise wieder die alten Farbwerte.** · gehört zu: 17 · Stand: offen · seit 31.08.2026
     Befund (B-S4-01; bis 02.09.2026 Nr. 49): Der Commit „Update Logos" hat
     alte Werte zurückgebracht — `gen-em_logo_helicopter.svg` führt `#587abc`,
     `#e3322b`, `#f7941d`, Korpus `#1d0e0a` statt `#4280E5`, `#D63338`,
     `#FF8F1F`, `#1A0500`; die PNG ebenso (gemessen 13.09.2026). Richtig sind
     nur `gen-em_logo_nef_weiss.svg` und `gen-em_logo_nef.png`.
     Entschieden 12.09.2026: neue Vorlagen anfordern; bis dahin nichts am
     Code. `Design.md` 2.5 ist am 13.09.2026 berichtigt.
     Weg: Laut Durchsicht vom 26.09.2026 liegen die Vorlagen vor (E-SD-34);
     alle Fassungen samt Ableitungen (PNG, Favicons, Uhr-Bilder) nachziehen,
     nachmessen und `Design.md` 2.5 mitziehen — mit dem neuen NEF-Logo, vor
     P7.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 62.

76. **Der Demo-Reset läuft alle 30 Minuten, auch wenn sich nichts geändert hat.** · gehört zu: 17 · Stand: teilweise · seit 02.09.2026
     Befund: `demo_reset_wenn_faellig()` (`demo_lib.php`) setzt zeitgesteuert
     zurück, ohne zu prüfen, ob eine Besucherin etwas verändert hat.
     Gemessen 15.09.2026 (Prüfstand, drei Läufe): 5859 ms mit dem alten,
     6610 ms mit dem neuen Bestand (106 Einsätze, 63 752 Spurpunkte) —
     0,75 s mehr für 20 % mehr Einsätze.
     Wirkung: Der Reset läuft huckepack auf einer Anfrage; die Besucherin,
     die ihn auslöst, wartet sechseinhalb Sekunden. Das ist das Argument für
     eine Änderungsmarke, nicht die Last. Produktiv (Datenbank auf anderem
     Rechner) und gleichzeitige Zugriffe sind nicht gemessen.
     Weg: entscheiden — durchlaufen lassen oder Zähler im Schreibweg des
     Demo-Kontos, Reset nur bei gesetzter Marke.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 76.

77. **Die Wartungsseite `update.php` in Unterseiten aufteilen.** · gehört zu: 12 · Stand: teilweise · seit 02.09.2026
     Befund: Die Seite trug Migrationsliste, Job-Einstieg, Speichergrenze und
     weitere Betriebsangaben auf einer Fläche.
     Entschieden 05.09.2026 (E-S8-05): nicht aufteilen, sondern **auflösen** —
     der Block Betrieb trägt sieben Seiten mit je einem Anliegen;
     Wartungsmodus und ausstehende Migrationen liegen zusammen auf Updates
     (R66). Gebaut in S8 AP2 bis AP4; `update.php` ist seit Web 15.2.0 eine
     302-Weiterleitung, der Notausgang `php update.php` bleibt.
     Weg: Offen ist allein, dass die Adresse noch existiert — das räumt P6.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 77.

80. **Auswertung der Gerätestatistik — und die zweite Hälfte der Frage.** · gehört zu: Zuarbeit · Stand: teilweise · seit 02.09.2026
     Befund: Rest von Nr. 59 (Speicherung, Web 12.9.0). Gebaut ist
     inzwischen fast alles: die Modelltabelle auf Betrieb → Statistik (S8/AP4,
     Web 15.3.0; 325 Teilenummern auf 173 Modelle), der Nachlöse-Job
     (P5a/AP11, Web 20.15.0 — `nachaufloesen.php` ist nur noch Weg von Hand)
     und die Herkunft je Einsatz mit den drei Reitern der Statistik (P5c/AP7,
     Web 20.47.0, E-P5c-18, ohne Vorbedingung — E-P5c-45). Die User-Agent-
     Hälfte („Rechner zählen") ist gestrichen (R36: keine Neuerhebung).
     Eine Lücke bleibt Bauform: Eine Wear-OS-Uhr koppelt nicht (E-S4-11) und
     erscheint als `handy`; die Statistik sagt es dazu.
     Offen: die **Datenschutzerklärung** muss die Gerätekennung nennen — die
     Auswertung läuft seit Web 15.3.0, der Text steht aus (Rahmenplan 6.3);
     die Momentaufnahme am Einsatz (`missions.geraet_art`) ist nicht bestellt.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 80.

90. **Der Simulator kann keinen Verbindungsabriss herstellen.** · gehört zu: nach v1.0 · Stand: offen · seit 03.09.2026
     *Aufgenommen 03.09.2026 aus S5 Paket C.*
     Der Rundlauf der Uhr-Kopplung sollte sechs Fälle belegen; der sechste —
     „Telefon aus der Reichweite" — ist im Simulator **nicht** herstellbar. Wird
     der Server getötet, sieht die App **HTTP 404**, keinen negativen Code
     (dieselbe Eigenschaft, die `tools/netzprobe/` für den CA-Fehler gemessen
     hat: eine fehlgeschlagene TLS-Verbindung erscheint als 404). Der Zweig
     „`Keine Verbindung (n)`, Code bleibt stehen, Abfrage läuft weiter"
     (E-S5-25) bleibt damit ungeprüft, und er ist kein Randfall — er ist der
     häufigste Fehler im Betrieb.
     **Woran es hängt:** `makeWebRequest` liefert negative Codes nur bei
     Bluetooth-Fehlern, und der Simulator hat kein Bluetooth. Der einzige
     gemessene Weg zu einem negativen Code ist blankes `http://` (−1001,
     `SECURE_CONNECTION_REQUIRED`) — aber der trifft schon `start` und kommt
     nie bis zur Kopplungsansicht.
     **Mögliche Wege:** ein Proxy vor dem Simulator, der die Verbindung
     mittendrin abbricht, und die Frage, was `makeWebRequest` daraus macht;
     oder eine Prüf-Einstellung in der App, die einen negativen Code
     einspeist (dann aber als Fremdkörper im ausgelieferten Code).

92. **`pruefstand.sh bildreihe` fotografiert nur den Startbildschirm.** · gehört zu: 17 · Stand: offen · seit 03.09.2026
     *Aufgenommen 03.09.2026 aus S5 Paket C.*
     Für Stufe II verlangt die Abnahme „je Vertreter ein Bild der `PairView`".
     `bildreihe` lädt die App, wartet und fotografiert — es gibt keinen Weg,
     eine Tastenfolge mitzugeben. Paket C hat sich dafür eine eigene Schleife
     gebaut (zweimal `Down`, weil der erste Druck nach dem Laden regelmäßig
     verlorengeht, dann `keydown Return` / `sleep` / `keyup` für den Langdruck;
     `pruefstand.sh halten` kann nur Maus, nicht Taste — und die
     Drei-Tasten-Geräte brauchen stattdessen Wischen, gelesen aus
     `monkey.jungle`).
     **Vorschlag:** `bildreihe <liste> <ziel> [tastenfolge]`, wobei die
     Tastenfolge eine Zeichenkette wie `Down,Down,hold:Return,wait:8` ist. Dann
     braucht die nächste Ansicht keine eigene Schleife.

95. **Die Rundlauffälle der Android-App lassen Daten im Admin-Konto zurück.** · gehört zu: 17 · Stand: offen · seit 03.09.2026
     *Aufgenommen 03.09.2026 aus der S5-Vorbereitung, Abschnitt 8.2.*
     Gemessen: **9 Diensttage, 5 Einsätze und 14 439 Spurpunkte**, die kein
     Prüffall wieder abräumt. Sie fallen nicht auf, solange niemand das
     Admin-Konto ansieht — und verfälschen jede Zahl, die jemand daraus zieht.
     **Vorschlag:** Aufräumen im `@After` der betroffenen Fälle, oder ein
     eigenes Prüfkonto, das der Lauf am Ende löscht. Gehört zum S4-Rest, weil
     er dieselben Prüffälle anfasst.
     **Stand 06.09.2026 (Fassung 32):** Der S4-Rest ist gemergt und hat den
     Punkt nicht mitgenommen; Nr. 115 (aus Paket E) meldete denselben Fund und
     ist hier aufgegangen. Zuordnung jetzt: **Backlog-Runde (Android)**.

96. **Uhr und Handy sagen nicht, dass gewartet wird.** · gehört zu: nach v1.0 · Stand: offen · seit 03.09.2026
     *Aufgenommen 03.09.2026 aus S5, Paket W (E-S5W-08).*
     Der Wartungsmodus antwortet mit **503** und einem `Retry-After`. Die
     Clients behandeln das als gewöhnliches 5xx — sie puffern und liefern
     nach, und genau das ist die Zusage des Vertrags. Was sie **nicht** tun:
     es sagen. Auf der Uhr steht dann derselbe Rückstand wie bei einem
     Funkloch, und `Retry-After` wertet niemand aus.
     **Bewusst nicht in S5 gebaut:** Es hätte eine Uhr- und eine
     Android-Auslieferung gekostet, für eine Lage, die wenige Minuten dauert.
     **Nach v1.0** neu abwägen — dann gibt es mehr als eine Uhr.

99. **Fassungsprüfung auf Klick.** · gehört zu: nach v1.0 · Stand: offen · seit 03.09.2026
     *Aufgenommen 03.09.2026 aus der Planung v1.0 (Rahmenplan R66, Option A2).*
     Ein Knopf „Auf neue Fassung prüfen" auf der Wartungsseite, der einmalig
     die GitHub-Releases-Schnittstelle fragt — kein Hintergrundlauf, kein
     Banner. Nur, wenn Selbsthoster es verlangen; die eigene Installation
     braucht es nicht, weil Betreiberin und Entwicklung dieselben sind.
     **Nach v1.0.**

100. **Play-API-Upload aus der Auslieferungskette.** · gehört zu: nach v1.0 · Stand: offen · seit 03.09.2026
     *Aufgenommen 03.09.2026 aus der Planung v1.0 (Rahmenplan R67).*
     Upload-Schlüssel als GitHub-Secret plus Dienstkonto der Play-API; jeder
     grüne Tag landet von selbst auf dem internen Test-Track, die
     Produktionsfreigabe bleibt ein Klick in der Play Console. Vertretbar,
     weil nach Play App Signing der Upload-Schlüssel der zurücksetzbare ist;
     E-S4-16 dann um den Unterschied App-Signaturschlüssel / Upload-Schlüssel
     ergänzen. **Nach v1.0**, wenn die Releases häufiger werden.

114. **Abgewiesene Pakete sichtbar machen und ausräumen.** · gehört zu: 17 · Stand: teilweise · seit 03.09.2026
     Befund (S5 Paket E, B-S5Z-06): Antwortet der Server auf ein Paket mit
     400, markiert der Puffer es als `fehlerhaft = 1` und nimmt es aus
     Warteschlange und Anzeige — die App sagt „Alles gesendet", beim Server
     bleibt ein Segment offen. Paket E2 zeigt die Zahl („N Pakete vom Server
     abgewiesen"); die Pakete bleiben samt GPS-Spur dauerhaft liegen
     (Krypto-Review AN-2).
     Erledigt (Android 0.14.0, 07.09.2026): `Puffer.abgewieseneRaeumen()`
     löscht abgeschlossene abgewiesene Pakete samt Punkten und Phasen nach
     30 Tagen und beim Trennen sofort; beendete `dienst`-Zeilen ohne Pakete
     gehen mit, die laufende nie (`AbgewieseneTest`, 10 Fälle).
     Offen ist der Bedienweg: ansehen, was drinsteht, ausleiten oder
     verwerfen. Zu entscheiden, ob Ausleiten (als Datei zum Nachreichen von
     Hand — braucht ein Format) oder Verwerfen mit Rückfrage (Datenverlust
     auf Knopfdruck). Ohne Weg wird die Zahl zur Tapete.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 114.

116. **Das Kontrastwerkzeug misst nur, was in seiner Paarliste steht.** · gehört zu: 17 · Stand: teilweise · seit 03.09.2026
     Befund (S5 Paket E1, B-S5Z-13/-15): Ein Farbpaar, das nicht in der
     festen Liste steht, wird nicht gemessen und meldet keinen Fehler — so
     standen zwei Paare unter dem Zielwert, ohne dass ein grüner Lauf etwas
     sagte (Rückstand-Punkt 2,23 : 1, „wartet aufs Handy" 4,12 : 1).
     Erledigt für Android (Konzept AR, 0.16.0, E-AR-09, 24.09.2026):
     `android/werkzeuge/kontraste.py` prüft die Vollständigkeit je Modul und
     Rolle (Schrift, Zeichen, Linie, Fläche) mit Selbstprobe; der erste Lauf
     fand dieselben zwei Kontraste, beide behoben. Das Werkzeug hängt an
     keinem Lauf (Nr. 334).
     Offen für das Web: dieselbe Frage für `tools/screenshots/kontrast.py` —
     Paare aus dem Quelltext ableiten, oder eine Vollständigkeitsprüfung, die
     im Code vorkommende Token-Paare ohne Listeneintrag meldet (billiger,
     fängt denselben Fehler). Zurückgestellt war es, solange P5c die Datei
     änderte; P5c ist gemergt, der Weg ist frei.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 116.

122. **Freie Zeiträume und Diagramme in der Statistik.** · gehört zu: 17 · Stand: offen · seit 05.09.2026
     *Aufgenommen 05.09.2026 aus dem S8-Konzept (Mockup 04).* Die Seite
     Betrieb → Statistik (S8 AP4) rechnet feste Zeiträume — 7 Tage, 30 Tage,
     6 Monate — und zeigt Zahlen in Tabellen. Für den Blick auf einen
     bestimmten Monat oder auf eine Entwicklung über ein Jahr reicht das
     nicht. **Zu tun:** ein frei wählbarer Zeitraum (Von/Bis wie in der
     Einsatzsuche) und eine grafische Darstellung der Entwicklung. Beides
     sind **neue Darstellungen** und brauchen Mockup und Freigabe
     (`CLAUDE.md` 5); die Diagrammfrage berührt außerdem die Zusage „keine
     fremde Quelle zur Laufzeit" — eine Diagrammbibliothek müsste vendoriert
     werden. Zuordnung: Backlog-Runde oder P5 (Dashboard, R38).

     **Zuordnung (20.09.2026): Schritt 17** (Backlog-Runde 4) — nicht 10c. Entschieden mit der Freigabe des P5c-Konzepts (E-P5c-23, F-P5c-4).

140. **Push auf `main` ist Deploy — Zugang zum Repositorium ist Zugang zum Schlüssel.** · gehört zu: 17 · Stand: teilweise · seit 06.09.2026
     Befund (Krypto-Review K-16): Die FTPS-Action deployte jeden Push mit
     Zugangsdaten aus Secrets; jedes Konto mit Push-Recht konnte `crypto.js`
     ändern und Passwörter beim nächsten Anmelden abgreifen — der eine
     Angriff, gegen den keine Browser-Verschlüsselung hilft. Seit S10 wiegt
     er schwerer: Wer `server/` beschreiben kann, liefert den Server-Anteil
     aus `config.php` mit aus (R78 (1)).
     Entschieden (R78): Zweigschutz und 2FA sofort als Zuarbeit, das
     Deploy-Tor mit dem Staging-Aufbau (R40 (2), nicht S10), die
     Integritätswache im Sofortpaket.
     Erledigt: die Wache (Web 15.6.0, `tools/integritaetswache/`,
     `integritaet.yml`, täglich und nach jedem Deploy; 112 Dateien, ein
     Inline-Block, ohne eingecheckte Prüfsummen); das Deploy-Tor (Web 20.4.0,
     R67: Push auf `main` geht auf Staging, Produktiv nur über Tag und
     Pflichtfreigabe); der Zweigschutz für `main` (21.09.2026, Rahmenplan
     6b) und der 2FA-Zwang in der GitHub-Organisation (Durchsicht der
     Betreiberin 26.09.2026, E-SD-34).
     Offen ist damit nichts mehr als die Verschiebung nach Erledigt, die
     Konzept SD nicht vornimmt; die Backlog-Runde holt sie nach.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 140.

146. **Fragen an das Bedrohungsmodell P6 aus dem Krypto-Review.** · gehört zu: 12 · Stand: offen · seit 06.09.2026
     Drei Fragen, keine Fehler (R78, 06.09.2026): **Argon2id statt PBKDF2**
     (WASM-Fremdbestandteil gegen GPU-Resistenz) · **Inhaltsschlüssel als
     nicht-extrahierbarer `CryptoKey`** statt Hex im `sessionStorage` (ein XSS
     könnte dann entschlüsseln, den Schlüssel aber nicht mitnehmen; anderes
     Lebensdauermodell „ein Tab, ein Schlüssel") · **Passkeys** als
     Zweitfaktor (WebAuthn-Serverbibliothek) und Passkeys mit PRF als Ersatz
     der Passwortableitung. Dazu die Design-Skizze für Weg B (Nr. 43, SP-9)
     zur Prüfung. Zuordnung R17 Stück 1.
     Stand seit S10 (Web 20.0.0, 14.09.2026): Der erste Punkt ist messbar
     kleiner — der Datenschlüssel hängt am Server-Anteil aus `config.php`,
     ein Datenbankabzug allein reicht für einen Offline-Angriff nicht mehr;
     Argon2id verteidigte gegen einen Angreifer, der ohnehin beides hat. Der
     zweite Punkt ist unberührt: Der Anteil schützt die Hülle, nicht den
     entpackten Schlüssel im `sessionStorage`.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 146.

150. **Der Cron-Befehl für den Job-Einstieg steht mit dem Repositoriumspfad in der Dokumentation.** · gehört zu: 17 · Stand: teilweise · seit 06.09.2026
     Befund: Der Deploy legt den Inhalt von `server/` nach `httpdocs/`; einen
     Unterordner `server/` gibt es auf einer Anlage nicht. Wer den Befehl
     `php …/server/jobs.php` aus Docstring oder `Technik.md` abtippt, bekommt
     „Could not open input file" — so geschehen beim Plesk-Cron auf Produktiv.
     Richtig war und ist der Kopier-Knopf auf Betrieb → Hintergrundjobs
     (baut über `__DIR__`, E-S8-10).
     Entschieden 12.09.2026: alle Stellen berichtigen, auch rückwirkend im
     Changelog; künftig der Platzhalter `php /pfad/zur/installation/jobs.php`
     plus ein Satz zum Kopier-Knopf.
     Erledigt (Konzept BV, BV-04, 24.09.2026): `Technik.md` 4.97a und
     Runbook 7, Changelog Web 10.1.0, eine fünfte Stelle (inzwischen mit dem
     FTP-Rückbau gestrichen).
     Offen: der Kopfkommentar von `server/jobs.php` (Zeile 13) — eine Zeile
     unter `server/` verlangt eine Web-Stufe, und die gab es in BV nicht
     (E-BV-18, 26.09.2026). Geht mit der nächsten Web-Stufe, die die Datei
     ohnehin anfasst.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 150.

154. **Handy-App liest `kept_points` und `kept_meta` nicht.** · gehört zu: 13 · Stand: offen · seit 07.09.2026
     *Aufgenommen 07.09.2026 aus der Gegenprüfung des Sofortpakets (Nr. 134).*
     `Sendeantwort.kt` nimmt aus der Antwort von `ingest.php` nur
     `kept_phases` und `kept_resus` in den Sendebericht; ein Paket, dessen
     Punkte oder Metadaten der Server wegen des Ersetzfensters übergangen
     hat (`kept_points`, `kept_meta` — JSON-Vertrag 5), sieht am Handy wie
     ein Erfolg aus. Der Server sagt es; die App hört es nicht. Beide Felder
     in `Sendeantwort` aufnehmen und in der Ergebniszeile nennen; Prüffall in
     `SendeantwortTest`. Zuordnung: nächste Android-Stufe.

157. **Handy-App: Sackgasse zwischen „Schlüssel abgewiesen" und „Gerät trennen".** · gehört zu: 13 · Stand: offen · seit 07.09.2026
     *Aufgenommen 07.09.2026 aus dem Emulatorlauf zu Android 0.14.1.* Wird
     das Gerät serverseitig gelöscht, während ein Paket noch aussteht (hier:
     der Demo-Reset nahm das Gerät mit, das Dienstende-Paket bekam `401`),
     zeigt die App „Rückstand 1 Paket" und „Schlüssel abgewiesen · Gerät neu
     koppeln" — und **verweigert das Trennen**, weil `trennen()` bei
     Rückstand abbricht (`Trennergebnis.Rueckstand`, Hinweis „Sie gehören dem
     bisherigen Konto"). Das Paket kann aber nie mehr gehen: Der Schlüssel
     ist weg. Senden geht nicht, Trennen geht nicht, Neukoppeln setzt Trennen
     voraus; der einzige Ausweg ist das Löschen der App-Daten. Behebung:
     Bei `Abweisung.SITZUNG_UNGUELTIG` (401 `auth`) das Trennen trotz
     Rückstand zulassen — mit dem Hinweis, dass die ausstehenden Pakete mit
     dem alten Schlüssel nicht mehr zustellbar sind und verworfen werden —
     oder den Rückstand beim 401 als „abgewiesen" führen, damit der Räumteil
     aus Nr. 114 ihn beim Trennen mitnimmt. Prüffall in `KopplungTest`.
     Zuordnung: nächste Android-Stufe.

158. **`days` trägt kein `created_at`.** · gehört zu: 17 · Stand: offen · seit 08.09.2026
     *Aufgenommen 08.09.2026 bei der Neufassung der Tagesregel (Nr. 134).* Für
     Einsätze und Ruhesegmente ist der Anker des Ersetzfensters die Serverzeit
     des Anlegens — genau deshalb kann eine falsch gestellte Geräteuhr das
     Fenster nicht steuern. Für den **Diensttag** gibt es diese Spalte nicht.
     Die Frage „wird an diesem Tag noch gearbeitet?" wird deshalb über das
     jüngste `created_at` seiner Datensätze beantwortet (`ingest_tag_offen()`
     in `ingest.php`). Das ist ein ehrlicher Ersatz und in der Sache meist
     dasselbe, aber es ist eine Abfrage über zwei Tabellen statt eines
     Spaltenwerts, und ein Tag, dessen Datensätze alle gelöscht wurden, hat gar
     keinen Anker mehr. Behebung: `days.created_at` mit Migration (Rückfall auf
     `started_at`, gekappt wie bei `rest_segments`), danach
     `ingest_tag_offen()` auf einen Wert zurückführen. Kein Fehler, eine
     Vereinfachung — und die Voraussetzung dafür, die Regel in einem Satz
     erklären zu können. Zuordnung: Backlog-Runde.

161. **Aus einer Aufzeichnung ein Stück löschen können.** · gehört zu: 17 · Stand: offen · seit 08.09.2026
     *Aufgenommen 08.09.2026 beim Bauen von Nr. 160.* Ein vergessener Dienst
     zeichnet weiter auf — auch das Wochenende, auch den Weg nach Hause. Was
     dabei hochgeladen wurde, lässt sich heute nur **ganz oder gar nicht**
     loswerden: `trash_delete_day()` legt den Diensttag in den Papierkorb und
     nimmt seine Ruhezeiten mit (`deleted_with_day = 1`), also auch den echten
     Dienst, den man behalten will. Das Schneidewerkzeug (`api/schneiden.php`)
     macht aus einem Stück Spur einen **Einsatz**; es löscht keines. Für
     GPS-Daten, die im Klartext liegen und den Wohnort zeigen, ist das zu grob.
     Behebung: In der Ansicht der Ruhezeiten einen Zeitraum wählen und dessen
     Punkte löschen können — über `spur_lib.php`, nie unmittelbar per SQL
     (CLAUDE.md 4), mit Rückfrage und einer Zeile im Protokoll. Zuordnung:
     Backlog-Runde, gemeinsam mit Nr. 43 (Ortsdaten) zu betrachten.

170. **Kein Prüfmittel misst, ob die Kennzeichnung vollständig ist.** · gehört zu: 17 · Stand: offen · seit 10.09.2026
     Befund (zwei Rückmeldungen zu Web 19.1.0, behoben mit 19.1.1): AP7
     zählte 8 Schlösser und 9 Kleinzeilen, und die Zahlen stimmten — das
     Schloss fehlte trotzdem an der Einsatznummer (Leseansicht) und an der
     Karte „Notizen" (Formular). Eine Zählung ohne Sollmaß bestätigt ihre
     eigene Liste und findet keine fehlende Zeile darin.
     Weg: eine Probe, die Formular und Leseansicht aufruft und für jedes
     Feld prüft, ob das erwartete Zeichen an der erwarteten Stelle steht —
     „8 von 8" statt „8". Das Sollmaß ist noch zu bestimmen (13.09.2026):
     Der Feldkatalog trägt nur ein Feld mit `'store' => 'pat'` (`notes`) und
     drei mit `'hinweis'`; die acht Schlösser der Karte „PatientIn" sind
     handgeschriebene `dtGeschuetzt()`-Aufrufe in `einsatz.php` und stehen in
     keinem Katalog.
     Abnahme: Die Probe wird rot, wenn ein `dtGeschuetzt()` in `einsatz.php`
     durch einen nackten String ersetzt wird.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 170.

172. **Eine Erwartung der Wartungsprobe flackert.** · gehört zu: 17 · Stand: offen · seit 12.09.2026
     Befund (Web 19.1.2): Erwartung 15 („das 503 kommt schneller als die
     Antwort ohne Wartung") vergleicht zwei Einzelmessungen mit `<` ohne
     Spielraum; örtlich liegen beide bei rund 71 ms, und drei Läufe ergaben
     0, 1, 0 nicht erfüllte Erwartungen (71,7 gegen 71,6 ms). Eine Probe,
     die jeden dritten Lauf grundlos rot wird, liest nach dem dritten Mal
     niemand mehr — und sie ist die einzige rote Zahl eines grünen Laufs.
     Weg: mehrfach messen und Mediane vergleichen, oder die Erwartung
     strukturell stellen — `wartung_tor()` steht in `db.php` vor jedem
     Verbindungsaufbau, und das prüft man am Code, nicht mit der Stoppuhr.
     Abnahme: zehn Läufe hintereinander, zehnmal dieselbe Zahl.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 172.

175. **`edbak_uebersicht()` hat keinen Aufrufer mehr.** · gehört zu: 17 · Stand: offen · seit 13.09.2026
     *Aufgenommen 13.09.2026 als Nebenfund der Gegenprüfung zu Nr. 37;
     angelegt auf Anweisung des Auftraggebers.* Die Funktion in
     `adminbackup_lib.php` liest für **jedes** Konto eine Begleitdatei und
     ein Verzeichnis — die Bauform, die E-P3-41 mit `edbak_konto_stand()` für
     die Kontoseite und O9c mit `edbak_staende()` und `edbak_verwaiste()` für
     die Zähler und die verwaisten Ordner abgelöst hat. Am 13.09.2026 ruft
     sie **niemand** mehr auf, weder in `server/` noch in `tools/`; genannt
     wird sie nur noch in `docs/Technik.md` („Die Kontoseite (E-P3-41, seit
     Web 9.8.0)") als Begründung, warum die Kontoseite anders liest.
     **Zu tun:** die Funktion austragen und den Satz in `Technik.md` so
     fassen, dass er die abgelöste Bauform als Vergangenheit beschreibt.
     *Abnahme:* `grep -rn edbak_uebersicht server/ tools/ docs/` ist leer,
     bis auf den Changelog. Zuordnung: Backlog-Runde.

177. **Der Änderungsverlauf des Rahmenplans führt sechs Fassungsnummern doppelt.** · gehört zu: SD · Stand: offen · seit 13.09.2026
     Befund (Backlog-Runde 3, AP10): In Abschnitt 10 stehen die Nummern 35,
     36, 37, 39, 38, 37 zwischen „30" und „47" ein zweites Mal mit anderem
     Inhalt — mehrere Sitzungen schrieben am selben Tag. Ein Verweis auf
     „Fassung 38" ist nicht auflösbar; zwei Konzeptdokumente zitieren
     betroffene Nummern (Sofortpaket „Fassung 36", S9 „Fassung 38").
     Zurückgestellt 13.09.2026 auf Anweisung („nur historisch"): keine
     Wirkung auf Code, Daten oder Oberfläche.
     Erledigt sich mit Konzept SD (Sammelnummer 294): Der alte Verlauf liegt
     eingefroren in `Rahmenplan-Archiv-2.md`, der neue
     (`Rahmenplan-Verlauf.md`) beginnt bei Fassung 125 mit eindeutigen
     Nummern; die Doppelungen werden nicht umnummeriert. Abnahme: jede
     Fassung im neuen Verlauf genau einmal.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 177.

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
     und die Prüfmittel dazu (`anteilprobe/endpunkt.py` Teil E,
     `umstellungslauf.mjs`, `huelle_stellen.py`). Nicht mitgehen: die
     Formatkennung selbst und die Rotation des Server-Anteils (E-S10-11).
     Reihenfolge: in einem Paket mit Nr. 46, sobald keine Anlage mit
     Altbestand mehr herüberkommt (Nr. 324). Abnahme:
     `grep -rn "anheben\|kdf_upgrade\|pat_anheben" server/` ist leer, ein
     frisches Konto liest seine Daten, Wortliste und Vollständigkeit melden
     keine ungenutzten Ausnahmen.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 187.

188. **Eine Dokumentenprobe: kein Prüfmittel misst Verweise zwischen Dokumenten.** · gehört zu: 12 · Stand: teilweise · seit 14.09.2026
     Befund (S10-Nachlauf): „Linkprobe 117 Verweise, 0 Abweichungen" belegt
     nichts über `docs/` — die Linkprobe liest `<seite>.php?…` in `server/`.
     Die Fahrplanzeile zu Schritt 7 nannte acht Tage lang einen Konzeptpfad,
     den es nicht mehr gab; die 34 Löschungen von Fassung 110 (24.09.2026)
     wurden mit `grep` von Hand nachgezogen — zweiter Fall dieses Punkts.
     Weg: eine Probe nach dem Muster von `tools/linkprobe/`, die jeden Pfad-
     und Dateinamensverweis in `docs/`, `server/`, `tools/`, `android/`,
     `watch/` gegen `git ls-files` hält. Sie muss relative Pfade auflösen
     (`api/day.php` = `server/api/day.php`, sonst über 200 Fehltreffer),
     eingefrorene Dokumente auslassen (Archive, Changelog, `erledigt/`) und
     eine begründete Ausnahmeliste führen (`config.php`, `wartung.lock`,
     Ausgabeordner, geplante Dokumente).
     Erledigt ist ein Teil: die Ankerprüfung `hilfe.php#…` gegen die
     Handbuch-Überschriften (`tools/quelltext/anker.php`, Web 21.1.0, P5c
     E-P5c-86) führt diese Nummer als Anlass.
     Abnahme: 0 Treffer außerhalb der Ausnahmeliste, 0 ungenutzte Ausnahmen,
     und die Zahl der geprüften Verweise steht dabei. Zuordnung P6 (R69),
     früher, wenn vorher ein weiteres Konzept gelöscht wird.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 188.

193. **Register und Doku führen die R42-Auswertung als offen, obwohl sie seit Web 15.3.0 läuft.** · gehört zu: SD · Stand: teilweise · seit 14.09.2026
     Befund (Bestandsaufnahme zu R42, 14.09.2026): R42 verlangt eine
     Geräteverteilung je Kategorie und Bezeichnung ohne `manual-%` und ohne
     Demo-Konto; sie steht seit S8/AP4 in `betrieb_statistik.php`. Fünf
     Stellen sagten das Gegenteil: die Registerzeilen R42 und R64 des
     Rahmenplans, `Technik.md` („Die Auswertung ist P5"), `Handbuch.md` 10
     („Bevor eine Auswertung entsteht, wird sie in der Datenschutzerklärung
     benannt") und die Kopfzeile von Nr. 80. Der Handbuchsatz ist eine
     Zusage an die NutzerIn und steht zwei Kapitel vor 12.2, das die
     Statistik mitsamt Gerätemodell-Tabelle beschreibt. Für die
     Momentaufnahme am Einsatz (`missions.geraet_art`) bleibt er wahr; die
     Datenschutz-Frage steht bei Nr. 80. Zweiter Beleg für Nr. 188.
     Erledigt mit Konzept SD (E-SD-26): R42 und R64 tragen seit Fassung 125
     ihren Statussatz, Nr. 80 trägt seit SD-02 eine Kopfzeile ohne
     „ausgewertet ist nichts". Offen sind die zwei Sätze in `Technik.md`
     und `Handbuch.md` 10 (26.09.2026 nachgemessen: beide stehen noch);
     SD-04 zieht sie nach. Abnahme: Die fünf Stellen sagen dasselbe wie
     die Registerzeile R42.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 193.

196. **68 von 195 Backlog-Einträgen rendern auf GitHub als grauer Kasten.** · gehört zu: SD · Stand: offen · seit 15.09.2026
     Befund (15.09.2026, cmark-gfm): Ab Nr. 100 ist der Listenmarker fünf
     Zeichen breit, die Datei rückte mit vier ein — bei dreistelligen Nummern
     endet der Listenpunkt nach dem ersten Absatz, der Rest wird zum
     eingerückten Codeblock. 195 Einträge, 68 als Codeblock, vier davon mit
     Tabelle (123, 187, 192, 193). Der Rahmenplan war schlimmer dran:
     Abschnitt 10 brach als Tabelle nach 8 `<tr>` ab (Leerzeile in der Zeile
     zu Fassung 61), die Fahrplanzeile 9c hatte neun Zellen durch drei
     ungeschützte Pipes in einem Code-Span.
     Weg: ein Leerzeichen mehr bei allen Fortsetzungszeilen, in einem Zug —
     nicht in Teilen.
     Erledigt sich mit Konzept SD (Sammelnummer 294): SD-02 rückt jeden
     Eintrag mit fünf Leerzeichen ein und misst mit cmark-gfm (`<pre>` 0,
     `<li>` = Einträge); der alte Rahmenplan liegt eingefroren im Archiv-2,
     der neue Verlauf ist eine Tabelle ohne Leerzeilen; SD-03 hängt die
     Probe an Stufe 1. Abnahme: 0 Einträge mit `<pre>`, Tabellen als
     `<table>`, jede Verlaufszeile ein `<tr>`.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 196.

198. **Die Zeitraumübersicht zählt Windendienste nur luftgebunden.** · gehört zu: 17 · Stand: nicht umsetzen · seit 14.09.2026
     Befund (Demo-Ausbau AP0): Seit Web 20.3.0 darf ein Rettungsmittel des
     Typs Bergwacht Winde und Bergwacht auch bodengebunden führen; das
     Einsatzformular zeigt die Windenfelder (`cap_gate` fragt ohne
     Artfilter), die Zeitraumübersicht übergeht ihn — `api/range.php`
     beantwortet `faehigkeiten` nur über `d.kind = 'air'`, die Windenkacheln
     stehen nur in `KACHELN_LUFT`.
     Entschieden 20.09.2026 (E-P5c-27, Mockup-Runde M-P5c-01): wird nicht
     umgesetzt; geschlossen, aber nicht nach Erledigt, weil nichts erledigt
     wurde. Bestätigt 22.09.2026 (Schritt 15 AP9a, E-ZE-31): Die Auswertung
     — Kacheln und Tabellenspalten in Tages- und Zeitraumübersicht — folgt
     Betriebsart und Fähigkeit, die Bearbeitung der Fähigkeit allein; die
     Tagesübersicht wertet `cap_gate` seit Web 20.35.0 nach derselben Regel
     aus, die Suche folgt der Fähigkeit über Luft und Boden.
     Folge: Winden-Cycles bodengebundener Bergwacht-Diensttage erscheinen in
     keiner Tages- oder Zeitraumansicht (vier solche Tage im Bestand, zwei
     mit Windeneinsatz); erfasst und in der Einsatzbearbeitung sichtbar
     bleiben sie. Wer das ändert, findet die beiden Stellen oben.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 198.

199. **Die Nummer 5 fehlt im Backlog, obwohl der Changelog sie unter Erledigt verortet.** · gehört zu: SD · Stand: offen · seit 15.09.2026
     Befund (Gegenlesen des Merges von PR #47): `grep -cE '^5\. '` liefert
     0 — weder unter Offen noch unter Erledigt gibt es die Nummer, und die
     Kopfnotiz nennt als frei nur 4, 6 und 7. Der Changelog zu Web 7.2.0
     sagt zweimal das Gegenteil („Das Geräte-Limit (Nr. 5) … steht jetzt
     unter Erledigt"). Ein Verweis auf „Nr. 5" löst ins Leere; dritter
     Beleg für Nr. 188.
     Zwei Wege, nur einer richtig: den Eintrag aus der Historie unter
     Erledigt wiederherstellen, oder die 5 als dauerhaft frei führen und den
     Changelog-Satz als überholt kennzeichnen.
     Erledigt sich mit Konzept SD (Sammelnummer 294): Die Nummer steht in
     keiner Fassung von `docs/Backlog.md` (gemessen 26.09.2026 über die
     ganze Historie ab `7154ec5`) — der Changelog-Satz beschreibt einen
     Stand, der nie im Repositorium lag. E-SD-25: 5 ist dauerhaft frei und
     steht so in der Reservierungstabelle des Kopfes.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 199.

200. **Bounce-Postfach: Unzustellbares erkennen, nicht nur zählen.** · gehört zu: 17 · Stand: nicht umsetzen · seit 15.09.2026
     Befund (Konzept P5a, E-P5a-14; Plattformprofil PP-7): Die
     Mail-Warteschlange zählt Zustellversuche und führt eine
     Unzustellbar-Liste auf der Statusseite — was der SMTP-Server annimmt
     und die Gegenstelle später zurückschickt, sieht sie nicht. Fehlen würde
     ein Postfach, das die Anwendung per IMAP liest, mit Vermerk am Konto
     und Rückfrage beim nächsten Anmelden.
     Entschieden 23.09.2026 (E-P5c-51): wird nicht umgesetzt; 10c AP10
     entfällt. Die Anwendung versendet nur, „zugestellt" heißt „vom
     SMTP-Server des Hosters angenommen"; IMAP ist seit PHP 8.4 nicht mehr
     im Kern, ein eigener Client wäre ein Paket für sich. Der Nutzen ist
     klein: Jede Adresse ist über einen Link bestätigt, und bei wenigen
     Konten sieht die Betreiberin Rückläufer im Postfach der Absenderadresse
     (Handbuch 12.8). Wie Nr. 198: geschlossen, aber nicht nach Erledigt,
     weil nichts erledigt wurde.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 200.

201. **`Retry-After` in Uhr und Handy auswerten.** · gehört zu: 13 · Stand: offen · seit 15.09.2026
     *Aufgenommen 15.09.2026 (Konzept P5a, Befund 1.7).* Beide Clients
     behandeln jeden Antwortcode außer 200/400/401/403/413 als „später
     erneut" und wiederholen zum nächsten eigenen Anlass — die Kopfzeile
     `Retry-After`, die die Mengenbremse (E-P5a-02) mitschickt, liest keiner.
     Für Sperren von 10 bis 60 Minuten genügt das; sauberer wäre, die
     genannte Zeit abzuwarten statt bei jedem Auslöser anzuklopfen. Niedrig;
     Uhr-Stufe und Android-Stufe je eine Zeile in der Antwortauswertung
     (`Uploader.mc`, `Sendeantwort.kt`).

202. **Zentralisierung Web — eine Stelle je Sache (Sammelnummer, Schritt 15, R83).** · gehört zu: 17 · Stand: nur auf Anlass · seit 16.09.2026
     Befund (16.09.2026, nachgemessen 20.09.2026 an `862ca7f`): Eine eigene
     Sitzung hat `server/` (ohne `assets/vendor/`) auf Code untersucht, der
     nach dem Vorbild von `mission_fields.php` an eine Stelle gehört — sechs
     Pakete: Marke/Mail/Link/Token, Datenzugriff, API-Eingang/Sitzung/Flash,
     JavaScript, Zeit/Zahl/Migration, Beifang. Regel R83: zentralisiert wird
     beim zweiten echten Verbraucher.
     Erledigt: Paket 1 in P5a/AP5 und P5b/AP2 (`mail_rahmen()`, `app_url()`,
     `konto_lib.php`), der Log-Helfer in 10c AP3 (`system_melden()`,
     Nr. 248), Pakete 2 bis 5 in Schritt 15 AP2 bis AP8 (Rahmenplan
     Abschnitt 8; die Vorher/Nachher-Zahlen je Sache führt
     `tools/zaehlung/register.php`). `post_ende()` wurde bewusst nicht
     gebaut (F-ZE-4, Nr. 250).
     Offen ist Paket 6, Beifang ohne Termin, nur zusammen mit Arbeit an der
     Datei: Stammdaten-CRUD in `einstellungen.php` (vier Speicher-, vier
     Löschzweige), Verwaltungsseiten-Auftakt (`ui_meldung` in 12 Dateien
     gleich komponiert), Umfangsliste mit Zahl-Plakette (3× wortgleich),
     Nachweisdatei-Mechanik in `install.php` und `wiederherstellen.php`,
     Ablage mit Zeitstempel in drei Bibliotheken.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 202.

207. **`gen-em.org` steht 96× in `tools/` und `.github/`.** · gehört zu: 17 · Stand: offen · seit 16.09.2026
     Befund (P5a/AP7; `grep -rn "gen-em\.org" tools/ .github/ | wc -l` →
     96, `server/` → 0 seit Web 20.9.0, Nr. 203): In den Prüfmitteln stehen
     Prüfkonten (`ingestprobe@gen-em.org`, `demo@gen-em.org`,
     `messstand@gen-em.org` …), Schema-`$id`s (`https://gen-em.org/nadoku/…`)
     und Anleitungen in den `LIESMICH.md`. Kein Fehler — ein Prüfkonto ist
     eine erfundene Adresse —, aber der Name einer realen Domain in einem
     Repositorium, das weitergegeben werden soll.
     Weg: `.invalid` (RFC 2606) für alle Prüfkonten — die Mailprobe benutzt
     es bereits —, eine `urn:`-Kennung oder `example.org` für die
     Schema-`$id`s. Mechanisch, berührt aber Bestandsdaten: Eine örtliche
     Anlage mit `demo@gen-em.org` muss neu aufgesetzt werden, sonst greift
     kein Kreislauf mehr. Deshalb ein Paket mit Ansage, nicht nebenbei.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 207.

209. **`docs/Design.md` führt die erzeugte Bausteintabelle mit falschen Zeilennummern.** · gehört zu: 17 · Stand: offen · seit 16.09.2026
     Die Tabelle trägt den Vermerk „ERZEUGT von `tools/design/tabellen.py` —
     nicht von Hand ändern", und ihre Spalte `ui.php` nennt zu jeder Funktion
     eine Zeilennummer. Diese Nummern liegen durchgängig **rund 26 Zeilen zu
     niedrig**: `ui_seite_start()` steht dort mit 54 und im Code bei 80.
     Ursache ist schlicht, dass das Werkzeug seit einigen Paketen nicht
     gelaufen ist.

     **Das ist kein Schönheitsfehler:** Eine erzeugte Tabelle, die nicht mehr
     zu ihrer Quelle passt, ist schlechter als keine — wer ihr folgt, landet
     mitten in einer anderen Funktion und hält das für den Baustein. Abhilfe
     ist ein Aufruf (`python3 tools/design/tabellen.py alle`); der Punkt steht
     hier, weil dabei **alle vier** erzeugten Tabellen neu entstehen und das
     Ergebnis gegengelesen werden will.

     *Aufgenommen 16.09.2026 in P5a/AP8, gefunden bei der Bestandsaufnahme der
     Bausteine.*

210. **`ingest.php` läuft bei gleichzeitigen Uploads auf denselben Diensttag in einen Deadlock.** · gehört zu: 18 · Stand: teilweise · seit 16.09.2026
     Befund (P5a/AP9, `tools/verbindungsprobe/`, 16.09.2026): Zwanzig
     Pakete desselben Geräts gleichzeitig ergaben zwölf `SQLSTATE[40001]
     1213 Deadlock`. Ursache ist die gemeinsame Zeile: Jeder Upload schreibt
     `days.started_at`/`ended_at` in derselben Transaktion fort, in der er
     seinen Einsatz anlegt (`dt_zeitraum_fortschreiben()` und der
     `INSERT … ON DUPLICATE KEY` auf `missions`); zwei Uploads halten Sperren
     in umgekehrter Reihenfolge.
     Erledigt ist die halbe Miete (E-P5a-52): Die Antwort ist 503
     `ausgelastet` statt 500, alle zwanzig Pakete kommen in der Probe an.
     Offen ist die Vermeidung: den Transaktionsrumpf in eine Schleife mit
     zwei bis drei Anläufen fassen und klären, was dazwischen neu gelesen
     werden muss (Umriss der Spur, Fortsetzungsmarke); prüfen, ob die
     idempotente `days`-Fortschreibung hinter den Commit kann. Nicht Teil
     von Schritt 15 (E-ZE-20): `db_transaktion()` ändert, wie Transaktionen
     geschrieben werden, nicht, was bei einem Deadlock geschieht.
     Abnahme: `php tools/verbindungsprobe/probe.php --frei 20` meldet 0 × 503
     und 0 Gedrängel im Fehlerprotokoll.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 210.

213. **Die Zustandsdatei der Auslieferungskette lag im Webroot.** · gehört zu: PK · Stand: teilweise · seit 16.09.2026
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

216. **Zwei Trennlinien hintereinander an vier Stellen des P5a-Prüfdokuments.** · gehört zu: 17 · Stand: offen · seit 16.09.2026
     *Aufgenommen 16.09.2026, gleiche Durchsicht.*
     Rein kosmetisch: `---` gefolgt von `---` erzeugt in manchen
     Markdown-Darstellungen eine doppelte Linie, in anderen eine Überschrift.
     Beim Abhaken der Prüfliste mit wegräumen, nicht dafür eigens anfassen — das
     Dokument verschwindet ohnehin, sobald seine 33 Punkte abgehakt sind.

227. **Die Symbolregel zählt Typografie und findet deshalb keine Symbole mehr.** · gehört zu: PK · Stand: teilweise · seit 17.09.2026
     Befund (P5b-Zweig, 17.09.2026): `tools/vollstaendigkeit/` prüft
     „Unicode-Zeichen als Symbol im Markup", zählte aber `…` und `→` mit —
     in diesem Projekt Satzzeichen („Betrieb → Servereinstellungen"). Von
     319 Befunden waren 299 Hausstil und 20 tatsächlich Zeichen statt
     Symbol; die 20 echten fielen zwischen den 299 niemandem auf, und die
     Schwelle in `pruefung.yml` wuchs mit jeder Phase (366 in `Technik.md`,
     377, 387 in der Kette — eine Zahl an zwei Stellen, Nebenbefund).
     Vorschlag: Zeichenliste in Ikonenzeichen (0 geduldet) und Typografie
     (nicht gezählt) teilen.
     Umgesetzt 22.09.2026 mit PK-04/1b: Die Typografie ist aus der Liste,
     von 330 Treffern blieben 14 (sie stehen als Nr. 279); Symbol- und
     Emoji-Zählung sind Hinweis statt Befund, weil die Prüfung Kommentare
     nicht trennen kann, solange Nr. 184 offen ist. Der Eintrag bleibt nach
     Konzept SD (4.7, Schritt 6) mit `Stand: teilweise` stehen, bis die
     Prüfliste abgehakt ist; nach Erledigt verschiebt ihn die Backlog-Runde.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 227.

228. **Proof-of-Work im Browser als dritte Stufe gegen Registrierungs-Spam.** · gehört zu: 18 · Stand: nur auf Anlass · seit 17.09.2026
     Befund (Konzept P5b, R37 (4) „notfalls"): R37 schließt ein CAPTCHA aus
     (fremde Quelle zur Laufzeit) und setzt zwei billige Mittel — Honeypot-
     Feld und Mindestausfülldauer vier Sekunden — neben drei
     Ratenschutz-Töpfe (`reg` je IP 10/h, `regg` global 100/h mit
     Verlangsamung, `regz` je Zieladresse 3/24 h). Reicht das nicht, bliebe
     eine Rechenaufgabe im Browser; gebaut ist sie mit Absicht nicht.
     Warum niedrig: Ein Proof-of-Work kostet am meisten auf dem alten
     Diensthandy und bremst jede ehrliche Registrierung. Die drei Mittel
     sind ungemessen; erst bauen, dann messen. Kein Ausschluss ohne
     JavaScript, weil die Registrierung den Schlüssel ohnehin im Browser
     ableitet (E-P5b-13).
     Auslöser: der Zähler der je Woche über `konto_verfall` verfallenen,
     nie bestätigten Konten. Bleibt er klein, ist der Eintrag erledigt, ohne
     dass etwas gebaut wurde. Abnahme, falls doch: SHA-256 über WebCrypto in
     einem Worker, ohne Fremdbestandteil, und die Antwortzeit der
     Registrierung bleibt unabhängig davon, ob die Adresse frei, bekannt
     oder Wegwerf ist (Enumerationsschutz E-P5b-13, Δ < 50 ms).
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 228.

229. **Die Uhr sagt „abgemeldet", wo „gesperrt" steht — und der Ausweg, den sie nennt, ist versperrt.** · gehört zu: 13 · Stand: zurückgestellt · seit 17.09.2026
     Befund (Konzept P5b, E-P5b-12): `ingest.php` antwortet 403 bei
     abgeschaltetem Gerät (`device_disabled`) und seit Web 20.17.0 bei
     einem Konto, das nicht `aktiv` ist (`{"error":"konto","grund":…}`,
     Grund `gesperrt`, `wartet`, `unbestaetigt`). `Uploader.mc` behandelt
     401 und 403 in einem Zweig: `abgemeldet`, Senden hält an, `SyncView.mc`
     rät „Neu koppeln". Der Rat führt im Kreis: `Pair.start()` verweigert das
     Trennen bei Rückstand („Erst N Pakete senden"), senden geht nicht, und
     die Zeile „verwerfen" wird bei `abgemeldet` gar nicht gezeichnet. Nr.
     159 hat das nicht geschlossen (geparkt wird nur im 400-Zweig);
     `JSON-Vertrag.md` behauptet zu 401/403 das Gegenteil und ist mit zu
     berichtigen. Verloren geht nichts — der Rückstand kommt nach dem
     Entsperren idempotent an; der Schaden ist die falsche Auskunft.
     Weg: `grund` im selben Zweig lesen, drei Texte in `SyncView.mc` ohne
     Hinweis auf Neukoppeln; mit Nr. 201 bei der nächsten Uhr-Stufe.
     Nebenbefunde: Das Handy behandelt 403 gar nicht (`Sendeantwort.lese()`
     kennt 200, 400, 401, 413; ein gesperrtes Konto versucht unbegrenzt
     weiter) — Android-Zeile in dasselbe Paket. Die Fehlertabelle zu
     `ingest.php` im Vertrag kennt weder 403 noch 507.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 229.

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
     Erledigt (Web 20.22.0, AP3): Liste (8 883 Domains) und
     `tools/wegwerfdomains/aktualisieren.py`, das beide Richtungen misst
     und nicht schreibt, wenn eine echte Domain getroffen wird; die Zeile im
     Auslieferungs-Runbook (`Technik.md` 7).
     Offen: der Stand der Liste — Datum und Zahl — in Betrieb → Status nach
     dem Vorbild der Zeile Gerätemodelle (`status_lib.php`), damit eine
     veraltete Liste sichtbar ist statt still; und die Zahl steht doppelt
     (Datei und Kommentar in `betrieb_server.php`). Kein Prüfschritt, der
     die Quelle abruft — er machte jeden Lauf von einem fremden Host abhängig.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 230.

232. **Die Fristen der Rückfragen sind nie im Betrieb abgelaufen.** · gehört zu: 17 · Stand: nur auf Anlass · seit 17.09.2026
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

233. **Die Betreiber-Rückfrage fragt nie nach dem bisherigen Server-Anteil.** · gehört zu: 18 · Stand: offen · seit 17.09.2026
     *Aufgenommen 17.09.2026 (P5b/AP9).* Während einer Anteilsrotation steht
     `kdf_anteil_alt` mit auf dem Schlüsselblatt. Die Rückfrage fragt ihn
     nicht ab — eine Frage, die je nach Betriebslage vier oder sechs Felder
     hat, verwirrt mehr, als sie prüft.

     **Was das offen lässt:** Wer sein Blatt nach einer Rotation neu druckt
     und den alten Wert nicht mit abschreibt, merkt es nicht, solange die
     Rückfrage schweigt. Der Wert wird aber gebraucht, bis das letzte Konto
     sich angemeldet hat.

     **Wie es zu schließen wäre:** Der Rotationsvorgang selbst sollte sagen,
     dass das Blatt neu gedruckt gehört — er ist die Stelle, an der es auffällt,
     und er weiß, ob ein alter Wert noch gebraucht wird. Das gehört zu S10c.

234. **Kein Prüfmittel fährt den Weg, den eine frisch ausgelieferte Anlage geht — Deploy, Anmeldung, `update.php`.** · gehört zu: PK · Stand: teilweise · seit 18.09.2026
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
     Kette II hat den Weg von Hand gefahren (`Technik.md` 6); das
     Prüfmittel fehlt.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 234.

236. **`ubuntu-latest` wandert am 19.10.2026 auf Ubuntu 26.** · gehört zu: PK · Stand: offen · seit 18.09.2026
     *Aufgenommen 18.09.2026, Merkposten mit Datum.*

     GitHub meldet als Hinweis: *„The `ubuntu-latest` label will migrate to
     Ubuntu 26 beginning October 19, 2026."* Betrifft **alle fünf Jobs**.

     An der Läufer-Umgebung hängen die **PHP-Fassung** (das Plattformprofil
     verlangt ≥ 8.2), das **vorinstallierte Android-SDK** und die
     **Bibliotheken, die der Uhr-Prüfstand nachlädt**. Der Wechsel passiert
     **still**, an einem Tag, an dem niemand etwas geändert hat — und dann
     sucht man den Fehler im eigenen Code.

     Zu tun: **vor dem Datum** einmal gegen ein `ubuntu-26`-Label gegenprüfen,
     solange es beide gibt.

237. **Der Täter-Finder des Bilderlaufs findet den Täter nicht.** · gehört zu: PK · Stand: offen · seit 18.09.2026
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

239. **`backup_lib.php` baut sein `INSERT` ohne Backticks, `komplett_lib.php` mit.** · gehört zu: 17 · Stand: offen · seit 20.09.2026
     Befund (beim Beheben von Nr. 238): `komplett_lib.php` schickt jeden
     Tabellen- und Spaltennamen durch eine Quotierungsfunktion;
     `backup_lib.php` setzt die Spaltenliste mit `implode(',', $cols)`
     ungequotet zusammen — und in `$cols` fließen über `$extraCols` die
     Namen aus dem Feldkatalog, also Namen, die noch dazukommen. Heute
     ungefährlich, weil nach Nr. 238 kein Name reserviert ist; die Bauform
     ist der Punkt: Genau diese Stelle hätte auf MySQL 8.4.0–8.4.10 das
     Einspielen einer Sicherung unmöglich gemacht.
     Weg: dieselbe Quotierung an beiden Stellen der Sicherung, und die
     Frage, ob eine gemeinsame Helferfunktion sinnvoller ist als zwei
     Kopien — sie wäre der Ort, an dem der nächste Schreibweg sie findet.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 239.

240. **Der Rundlauf-Prüffall des Handy-Moduls läuft in der Kette nie.** · gehört zu: PK · Stand: offen · seit 20.09.2026
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

242. **Sitzungsbindung per Cookie-Token — benannt, nicht mitgenommen.** · gehört zu: 18 · Stand: offen · seit 20.09.2026
     *Aufgenommen 20.09.2026 (E-SA-09 des Konzepts Sitzungsablage).*
     Zugeordnet: **Schritt 18** (Sicherheitsrunde II).

     Ein Zufallstoken nur im Cookie, dessen Hash in der Sitzung liegt, macht
     eine gelesene Sitzungsdatei wertlos — auch eine aus einem gefundenen
     Backup. Das ist der Schutz, den Nr. 241 **nicht** leistet: 241 verlegt
     den Ort, 242 entwertet die Datei.

     **Warum getrennt:** Das ist ein Sicherheitsumbau mit eigener Prüfung
     (Cookie-Handling — Uhr und Handy sind nicht betroffen, nur der Browser;
     Reset-Fluss; Wechselwirkung mit `users.session_epoch`) und gehört nicht
     in einen Verzeichniswechsel.

247. **Serverschlüssel wechseln — als Vorgang, nicht von Hand.** · gehört zu: 18 · Stand: offen · seit 20.09.2026
     Befund (V4 der P5c-Vorbereitung): Es gibt keinen Wechsel. Wer
     `server_key` von Hand ändert, macht alles Versiegelte stumm und merkt
     es erst, wenn er es braucht.
     Weg: ein Vorgang unter Betrieb — neuen Schlüssel erzeugen, alles
     Versiegelte umhüllen (Adminpakete, Zugänge der Sicherungsziele,
     Protokoll-Archive, Wiederanlaufpaket, die Zweitfaktor-Geheimnisse
     `users.totp_geheimnis` mit Zweck `totp|<Konto>` aus P5c/AP5, Web
     20.42.0), neues Schlüsselblatt, Protokolleintrag und der Nachweis der
     Öffenbarkeit vor dem Verwerfen des alten Schlüssels — der Schritt,
     dessen Fehlen den Vorgang gefährlich macht. Ein Wechsel, der die
     Zweitfaktor-Geheimnisse nicht umhüllt, lässt jede Code-Anmeldung
     scheitern (Notweg: Wiederherstellungscodes; Runbook `Technik.md` 7).
     Auslöser: Verdacht, dass das Blatt in falsche Hände kam. Eigenes Paket
     mit eigener Prüfung; bis dahin gilt im Betreiberhandbuch: Der Schlüssel
     wird nicht gewechselt, das Blatt gehütet (Quartalsrückfrage E-P5b-10).
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 247.

249. **TOTP-Reset, wenn die einzige BetreiberIn Zweitgerät und Codes verliert.** · gehört zu: 18 · Stand: teilweise · seit 20.09.2026
     *Aufgenommen 20.09.2026 (Konzept P5c, Abschnitt 8).*
     Zugeordnet: **Schritt 18** (Sicherheitsrunde II).

     10c macht den Zweitfaktor für Admin, BetreiberIn und Support zur
     Pflicht. Der Reset durch eine **zweite** BetreiberIn ist damit gelöst —
     der Fall „es gibt nur eine, und sie hat beides verloren" ist es nicht.

     Das ist ein **Wiederanlauf-Fall** und gehört zum S10-Runbook, nicht in
     10c: Er wird nicht über die Oberfläche gelöst, sondern über das
     Wiederanlaufpaket. Hier nur benannt, damit er nicht erst auffällt, wenn
     er eintritt.

     **Teilweise gelöst mit Konzept RW (E-RW-08, 24.09.2026; gebaut als 10c
     AP5b, Web 20.43.0 bis 20.45.0):** Hat die einzige BetreiberIn Passwort
     und Notfallblatt, setzt sie den Zweitfaktor am Code-Schritt selbst
     zurück. Der Wiederanlauf-Fall bleibt für den Rest: ohne Zettel, ohne
     Passwort, oder wenn der Rückweg ausgeschaltet ist (Statuszeile
     „Rückweg-Prüfung" orange).

250. **Umleiten nach POST auf den Admin-Seiten, die heute nicht umleiten.** · gehört zu: 17 · Stand: offen · seit 20.09.2026
     *Aufgenommen 20.09.2026 (Konzept Zentralisierung, F-ZE-4, aus Nr. 202 —
     `post_ende()`).* Zugeordnet: **Schritt 17**.

     Ein POST, der seine Seite selbst ausgibt statt umzuleiten, hinterlässt
     im Browser ein Formular, das sich beim Neuladen wiederholt. Ein Teil der
     Admin-Seiten macht es richtig, ein Teil nicht.

     **Nicht in Schritt 15**, obwohl der Befund dort entstanden ist: Schritt
     15 verschiebt Code an eine Stelle und ändert keine Wege durch die
     Anwendung. Umleiten nach POST ist ein geänderter Weg — er gehört in eine
     Runde, die Wege ändern darf.

251. **Cookie-Attribut `secure` der Sitzung ist in zwei Arten HTTPS-abhängig, in zwei fest.** · gehört zu: 18 · Stand: teilweise · seit 20.09.2026
     *Aufgenommen 20.09.2026 (Konzept Zentralisierung, E-ZE-12).* Zugeordnet:
     **Schritt 18**, zusammen mit der Sitzungsbindung (Nr. 242).

     Vier Stellen setzen das Attribut, und sie setzen es verschieden: zweimal
     abhängig davon, ob die Anfrage über HTTPS kam, zweimal fest. Nach
     Schritt 15 AP2 stehen sie alle in `sitzung_lib.php` — dann ist es eine
     Tabelle und keine Suche, und dann lässt sich entscheiden, welche der
     vier Arten die richtige ist.

     **Eingetreten am 21.09.2026 (Web 20.27.0, Schritt 15 AP2).** Die Tabelle
     heißt `SITZUNG_ARTEN` und steht in `sitzung_lib.php` neben
     `sitzung_starten()`. Fest auf `true`: `app` und `passwort`. Von HTTPS
     abhängig: `lesend` und `einrichtung` — also die beiden Arten, die auf
     einer Anlage laufen können, deren HTTPS-Lage die Einrichterin erst
     herstellt. Die Entscheidung für Schritt 18 ist damit **eine Zeile in
     einer Tabelle**, nicht mehr eine Suche über neun Dateien.

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

258. **Drei Dateien unter `api/` antworten am `json_out()` vorbei.** · gehört zu: 17 · Stand: teilweise · seit 21.09.2026
     Befund (Schritt 15 AP3): `rueckfrage.php`, `schluessel_erneuern.php`
     und `schluesselblatt_pruefen.php` schreiben ihre Antworten mit
     `header('Content-Type: …')` und `echo json_encode(...); exit;` — 19
     Stellen. `json_out()` setzt über `json_kopf()` die Kopfzeilen
     (`nosniff`, `Referrer-Policy`, HSTS, `Cache-Control: no-store`); die
     19 Stellen setzen nichts davon. Derselbe Mangel ist mit Web 20.9.1 an
     sieben anderen Stellen behoben (Nr. 203); diese drei arbeiten über
     `$_POST` und fielen als JSON-Endpunkte nicht auf. Praktische Folge
     gering (Quittungen, Fehlerkennungen, ein Prüfergebnis), aber `nosniff`
     ist bei `application/json` die Zeile, auf die es ankommt.
     Erledigt: die Methodenprüfung auf `api_methode()` (AP3, Nr. 256).
     Offen: der Umbau auf `json_out()` — nicht in AP3, weil der den Eingang
     zentralisiert, nicht den Ausgang, und die Kopfzeilen eine sichtbare
     Änderung sind (E-ZE-10).
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 258.

259. **Die GPX-Probe wird durch den Demo-Reset blind — 4 von 95 Erwartungen fallen, ihr Kernvergleich läuft gar nicht.** · gehört zu: 17 · Stand: offen · seit 21.09.2026
     Befund (Schritt 15 AP3): `tools/gpxprobe/probe.php` hält den
     Referenzexport vom 15.09.2026 gegen die GPX-Dateien des Demo-Kontos.
     Nach einem Demo-Reset haben die Einsätze neue Kennungen: „190 von 204
     ohne Gegenstück", drei Folgefehler — und der punktweise Vergleich
     meldet „0 von 204 Dateien verglichen (0 Abweichungen)": eine Null, die
     nichts gemessen hat, neben Nullen, die etwas gemessen haben. 95/4 vor
     und nach dem Paket identisch — der Befund liegt nicht am Code.
     Der Reset hängt nicht an der Jobschlange, sondern an `auth_guard.php`
     (`demo_reset_wenn_faellig()` bei jeder Anmeldung des Demo-Kontos nach
     `DEMO_RESET_SEKUNDEN` = 1800); `jobs_pause()` hilft nicht. Das
     richtige Mittel steht in `tools/klickprobe/LIESMICH.md`:
     `UPDATE app_state SET v = UNIX_TIMESTAMP() WHERE k = 'demo_letzter_reset';`
     verschiebt den nächsten Reset um 30 Minuten.
     Weg: Die Probe hält den Reset selbst auf und setzt das Demo-Konto aus
     der Fixture zurück, oder sie legt sich ein eigenes Konto an; jedenfalls
     sagt sie, wenn ihr Hauptteil nichts geprüft hat. Vorbild ist die
     Klickprobe, die einen Reset mitten im Lauf meldet (42 von 48, mit
     Pause 48 von 48).
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 259.

260. **Zwei Code-Kommentare in `server/` sagen „beider FTPS-Schritte" — seit Kette II/AP5 ist es einer.** · gehört zu: 17 · Stand: offen · seit 21.09.2026
     Befund (Kette II, AP5): `server/wartung_lib.php` (Kopfkommentar zu
     `ueberlast.json`) und `server/adminbackup_lib.php` (der
     `ZWINGEND`-Absatz zu `sicherungen/`) behaupten im Präsens, die
     Ausnahmeliste stehe in beiden FTPS-Schritten von `auslieferung.yml`.
     Seit AP5 steht sie einmal, in `ausliefern-lauf.yml`.
     Nicht im selben Paket erledigt, weil die Sätze die ausgelieferte Kette
     beschreiben und erst falsch werden, wenn AP5 auf `main` ankommt — und
     eine Änderung unter `server/` eine Versionsstufe verlangt, die zu dem
     Paket gehört, das ohnehin Code bewegt. Inzwischen liegt AP5 auf `main`
     (`ausliefern-lauf.yml` steht neben `auslieferung.yml`), und beide
     Kommentare stehen noch (nachgesehen 26.09.2026) — sie gehen mit der
     nächsten Web-Stufe, die die Dateien anfasst (wie Nr. 150).
     Nicht betroffen: die zwei Stellen in `server/version.php` (Werdegang
     der Fassungen 20.15.2 und 20.16.x — dort ist „zwei" richtig) sowie
     Changelog, Backlog und Prüfdokumente (Protokolle werden nicht
     rückwirkend umgeschrieben).
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 260.

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
     (`Technik.md` 6.6b, Schritt 2) ebenso.
     Vorschlag an die Betreiberin (Prüfpunkt 28, vor M2): Aufbewahrung auf
     Staging auf 5 setzen; wie viele Tags man rückwirkend reparieren können
     will, weiß nur sie.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 261.

262. **Atomare Auslieferung — umschalten statt überschreiben.** · gehört zu: 14 · Stand: offen · seit 21.09.2026
     *Aufgenommen 21.09.2026 (Kette II, Einschub Abschnitt 8).* Auslöser:
     **P8 oder ein Hosterwechsel.** Priorität: niedrig.

     Die Kette überträgt heute **in das laufende Verzeichnis**. Zwischen der
     ersten und der letzten Datei liegt ein Zeitfenster, in dem die Anwendung
     halb alt und halb neu ist — der Wartungsmodus verdeckt es, beseitigt es
     aber nicht. Der Schlussschritt sagt bei einem Abbruch deshalb
     „Dateistand: **unbekannt**", und das ist keine Schwäche der Meldung,
     sondern eine ehrliche Auskunft über die Bauform.

     **Abhilfe wäre ein Release-Verzeichnis:** hochladen nach
     `releases/<tag>/`, prüfen, dann einen Symlink umlegen. Der Umschaltpunkt
     ist dann **eine** Operation statt 688.

     **Warum nicht jetzt:** Der heutige Hoster gibt über FTPS keine Symlinks
     her, und ohne sie wäre das Umschalten ein Verzeichnis-Umbenennen —
     schneller als 688 Dateien, aber nicht atomar. Der Gewinn hinge am Hoster,
     und genau deshalb hängt der Punkt an P8 oder einem Wechsel.

263. **Die Integritätswache sieht nur, was öffentlich abrufbar ist.** · gehört zu: 12 · Stand: offen · seit 21.09.2026
     *Aufgenommen 21.09.2026 (Kette II, Einschub Abschnitt 8).* Priorität:
     niedrig. **Abhilfe offen — hier wird die Grenze benannt, nicht
     geschlossen.**

     `tools/integritaetswache/wache.py` vergleicht den Produktivserver gegen
     den Zeiger `produktion`. Sie holt sich die Dateien **über HTTPS**, sieht
     also `assets/`, die Anmeldeseite und was sonst ausgeliefert wird —
     **keinen PHP-Quelltext**. Eine untergeschobene Zeile in `db.php` oder
     `login.php` bemerkt sie nicht.

     **Was sie trotzdem leistet, und es ist nicht wenig:** Der häufigste
     Angriff auf eine solche Anlage ist ein untergeschobenes **Skript** im
     Frontend — und genau das ist öffentlich abrufbar und wird verglichen.

     **Warum die Abhilfe offen bleibt:** Sie hieße, dem Server eine Schnittstelle
     zu geben, die eigenen Quelldateien auszuliefern oder zu hashen. Das ist
     ein neuer Angriffsweg für ein Problem, das der Vergleich nur teilweise
     löst — die Entscheidung gehört in einen eigenen Durchgang, nicht in einen
     Nachtrag.

264. **Die Fremd-Aktion des Transports ablösen.** · gehört zu: PK · Stand: nur auf Anlass · seit 21.09.2026
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

265. **Verweise von `.github/` in die Dokumentation hält kein Prüfmittel nach.** · gehört zu: 17 · Stand: nur auf Anlass · seit 21.09.2026
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

266. **`plattform_pruefen()` sagt „aus", wo „nicht feststellbar" stehen müsste.** · gehört zu: 17 · Stand: offen · seit 21.09.2026
     *Aufgenommen 21.09.2026 (Kette II, AP8a; Anlass F-KH-U-05).*
     Priorität: niedrig.

     Steht eine geprüfte Funktion in `disable_functions`, antwortet
     `function_exists()` mit `false` — und der Befund wird zu einem **Mangel**
     statt zu einer **Nichtmessung**. Betroffen ist heute der **OPcache**
     (`opcache_get_status`, auf lima-city abgeschaltet): Die Statusseite meldet
     ihn als „aus", obwohl niemand weiß, ob er läuft.

     **Die Bauform steckt in jeder weiteren Prüfung, die über
     `function_exists()` geht**, nicht nur in dieser einen — das ist der Grund,
     warum der Punkt aufgeschrieben wird und nicht nur der OPcache-Fall.

     **Warum das mehr als Kosmetik ist:** Dreiwertigkeit ist im Projekt schon
     einmal teuer erkauft worden. Die Zielprobe unterscheidet ausdrücklich
     **LIEGT / FEHLT / NICHT FESTSTELLBAR** (`tools/kette/zielprobe.py`), weil
     ein „fehlt", das in Wahrheit ein „ich konnte nicht nachsehen" war, die
     Kette zu falschen Schlüssen brachte. Hier gilt dasselbe, nur auf der
     Statusseite.

271. **Die leere Meldungshülle im Schnittblock trägt kein Symbol — und ihr Ton bleibt „info", auch wenn ein Fehler darin steht.** · gehört zu: 17 · Stand: offen · seit 22.09.2026
     Gefunden bei der AP8-Vermessung (Schritt 15, 22.09.2026) in
     `assets/schneiden.js`. Die Stelle erzeugt keine Meldung, sondern einen
     *Platz* für eine: `<div class="meldung meldung-info" role="status"
     data-vorher><p></p></div>`, später dreimal per `textContent` befüllt. Zwei
     Abweichungen vom Baustein: **kein Symbol** (`EdHtml.meldung()` setzt eines
     ein — das wäre eine sichtbare Änderung im Schnittblock), und **der Ton
     wechselt nie**, so dass Sätze wie „Das Ende liegt vor dem Beginn." in
     blauer Hinweisfläche stehen statt in roter. Das zweite ist fachlich
     falsch. **Nicht in Schritt 15 behoben** (E-ZE-10: das Paket ändert kein
     Verhalten); die Zählzeile Z37 endet deshalb bei 4 statt 0. Beim Anfassen
     mitzudenken: Der Anker `data-vorher` hängt am Wrapper, den
     `EdHtml.meldung()` nicht mit Attributen versieht — entweder bekommt die
     Funktion einen Weg dafür, oder der Anker wandert nach innen.

272. **`<p class="meldung">` im Entsperrdialog ist gar keine Meldung.** · gehört zu: 17 · Stand: offen · seit 22.09.2026
     Gefunden bei derselben Vermessung, in `assets/unlock.js`. Dort steht ein
     Absatz mit der Klasse `meldung` — **ohne** Tonklasse, **ohne** Symbol,
     **ohne** `role`. Er trägt den Namen des Bausteins, ist aber keiner; das
     Zählmuster von Z37 hält ihn trotzdem für einen. Zwei Folgen: Die Zeile
     zählt einen Nachbau, den es nicht gibt (Z37 endet bei 4), und der Absatz
     bekommt aus `style.css` Regeln, die für einen Kasten gedacht sind.
     **Nicht in Schritt 15 behoben:** Ihn auf den Baustein umzustellen gäbe
     ihm einen farbigen Kasten mit Symbol — eine sichtbare Änderung im
     Entsperrdialog, und die war nicht beauftragt.

273. **Eine dritte Schreibweise für Dauern, die kein Zählmittel sieht.** · gehört zu: 17 · Stand: offen · seit 22.09.2026
     Gefunden in Schritt 15 AP8d, aber **nicht** von der Zählzeile Z34: Die
     misst über eine Namensliste und kennt `dauer()` in `assets/schneiden.js`
     nicht. Gefunden hat sie erst eine Gegenprobe über das Muster der
     *Rechnung* (`Math.floor(s / 3600)`). Die Funktion schreibt
     **„1 h 6 min" mit Leerzeichen**, während der Rest der Anwendung seit
     AP8d durchgängig „1h 06min" schreibt; dazu trägt sie denselben
     Rundungsfehler, den AP8d in `EdFormat.dauer()` behoben hat (getrennte
     Rechnung von Stunden und Minuten erzeugt bei 3599 s ein „60min").
     **Nicht umgestellt**, weil es eine sichtbare Änderung im Schnittblock
     wäre und die drei sichtbaren Änderungen von AP8d einzeln freigegeben
     wurden — diese war nicht darunter. Beim Anfassen: `EdFormat.dauer(s)`
     genügt, der Leerwert ist dort nicht erreichbar (`Math.max(0, …)`).

275. **Der Referenzdatensatz kennt keinen Dienst über Mitternacht — laut Handbuch „der klassische Fall".** · gehört zu: 17 · Stand: offen · seit 22.09.2026
     Befund (Schritt 15 AP9b, über `CONVERT_TZ` in Ortszeit): 0 von 20
     aktiven Diensttagen des Demo-Kontos haben Einsätze auf zwei
     Kalendertagen, über alle fünf Konten ebenso. In AP9b sortierte das
     Einsatztabellen-Modul „Beginn" über die Zeichenkette `start_hhmm`
     (01:10 vor 23:50) — jahrealt, von keinem Mittel zu finden, weil es
     nichts zu messen gab; belegt mit einem im Browser gebauten Nachtdienst.
     Ob die drei APIs `start_sort` für einen echten Nachtdienst richtig
     rechnen, ist gelesen, nicht gefahren.
     Weg: dem Demo-Konto zehn Einsätze über Nacht geben, auf mindestens zwei
     Diensttage verteilt (einer luft-, einer bodengebunden), jeder mit
     Einsätzen vor und nach Mitternacht; `days.day` bleibt der Dienstbeginn.
     Über den normalen Einspielweg (`tools/referenzdatensatz/`), `started_at`
     in UTC gerechnet. Danach zeigen Tages-, Zeitraum- und Suchtabelle die
     drei Datumsbegriffe getrennt (`day` gegen `dienst_day`), und der
     Bilderlauf bekommt die Seite dazu.
     Abnahme: Diensttage mit Einsätzen auf mehr als einem Kalendertag
     (`COUNT(DISTINCT DATE(CONVERT_TZ(...)))` > 1) mindestens 2; 0 heißt
     Fehlschlag.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 275.

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

277. **`einstellungen.php` — ein `await fetch` ohne eigenes `catch` steht vor der Erfolgsmeldung.** · gehört zu: 17 · Stand: offen · seit 22.09.2026
     Gefunden bei der AP8-Vermessung am 22.09.2026 (Schritt 15), in der
     Funktion, die den Wiederherstellungsschlüssel abschließt (bei Aufnahme
     Zeile 4158). Der Aufruf liegt im großen `try` des Knopfes; bricht das Netz
     genau dort, springt der Ablauf in den äußeren `catch`, und die Person
     liest eine Fehlermeldung zu einem Vorgang, der auf dem Server bereits
     durchgelaufen sein kann. **Nicht in Schritt 15 behoben** (E-ZE-10: das
     Paket ändert kein Verhalten). Beim Anfassen mitzudenken: Der Satz muss
     sagen, dass der Zustand unklar ist, nicht dass es fehlgeschlagen ist.

280. **Die Kartenquelle OpenHikingMap steht in keiner Lizenzliste.** · gehört zu: Pflegeaufgabe · Stand: offen · seit 22.09.2026
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

283. **Die Textprobe liest die Kommentare in `server/` nicht — und dort standen reale Ortsnamen.** · gehört zu: 17 · Stand: teilweise · seit 23.09.2026
     Befund (Gegenlesung PK-04/5c): Bereich `a` der Textprobe ist
     „`server/*.php`, `server/api/*.php` (sichtbare Texte, ohne
     Kommentare)" — für vier der fünf Regelklassen richtig, für `namen`
     falsch: E-P1-02 richtet sich gegen das öffentliche Repositorium, und
     ein Kommentar steht darin. Mit `grep` gemessen: acht Stellen in
     `server/` trugen „Kempten", „Christoph 17" oder eine Ortskennung, alle
     in Kommentaren — die schärfste sechs Zeilen unter dem Platzhalter, den
     E-S3-13 auf „Standort Talwang" berichtigt hatte. Die acht Stellen sind
     bereinigt, die Lücke nicht.
     Zu entscheiden: Bereich `a` um Kommentare erweitern (dann messen die
     anderen vier Klassen Kommentare mit, und ihre Null ist keine mehr —
     vermutlich ein Bereich je Klasse) oder eine eigene schmale Prüfung nur
     für `namen` über den ganzen Baum. Bis dahin heißt `namen = 0` „null im
     sichtbaren Text", nicht „null im Repositorium" (`Pruefablauf.md` 6.6).
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 283.

287. **Die Karten „Was hier gilt" außerhalb von Verwaltung und Betrieb.** · gehört zu: 17 · Stand: offen · seit 23.09.2026
     *Aufgenommen 23.09.2026 (Konzept P5c, E-P5c-49).* R74 (5) schrieb
     Erklärtext „einheitlich als EINE zugeklappte Karte ‚Was hier gilt' am
     Seitenende" vor. E-P5c-06 (jünger) sagt: je Karte höchstens ein Satz,
     alles Erklärende ins Handbuch. 10c AP9 räumt die acht Karten unter
     Verwaltung und Betrieb ab. **Drei Seiten außerhalb tragen die Karte
     ebenfalls:** `import.php`, `einsatz_form.php`, `wiederherstellen.php`.
     Sie liegen nicht im Umfang von 10c, und bis zu ihrer Umstellung gelten
     dort zwei Regeln nebeneinander.

     *Abnahme:* 0 Karten „Was hier gilt" in `server/`, der Inhalt im Handbuch,
     jede Karte der drei Seiten mit Verweis auf ihre Sprungmarke. *Fehlschlag:*
     `grep -l "Was hier gilt" server/*.php` findet eine Seite (außer
     Kommentaren in `version.php`). **Zuordnung: Schritt 17.**

290. **Keine Stufe der Kette richtet eine Anlage ein.** · gehört zu: PK · Stand: offen · seit 23.09.2026
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

291. **Eine gescheiterte Einrichtung hinterlässt ein halbes Schema.** · gehört zu: 17 · Stand: offen · seit 23.09.2026
     *Aufgenommen 23.09.2026 mit Web 20.37.3, gemessen.* `install.php` spielt
     `schema.sql` ein (Z. 352) und legt danach das Konto an. Scheitert
     danach etwas, stehen die Tabellen — `schema.sql` legt 41 von 42 ohne
     `IF NOT EXISTS` an —, und der nächste Versuch auf derselben Datenbank
     bricht an ihnen ab. Die Meldung rät dabei in jedem Fall „eine leere
     Datenbank verwenden", auch beim ersten Fehlschlag, der mit der Datenbank
     nichts zu tun hatte (so bei Nr. 288). Eine Transaktion hilft hier nicht:
     DDL bestätigt in MySQL still.

     *Weg (zu entscheiden):* entweder den Rat nur geben, wenn der Fehler vom
     Schema kommt, oder das Konto vor dem Schema prüfen lassen, was geht. Klein,
     kein Datenrisiko — die Anlage ist in diesem Zustand noch leer.
     **Zuordnung: Backlog-Runde.**

294. **Steuerungsdokumente schneiden (Konzept SD).** · gehört zu: SD · Stand: offen · seit 24.09.2026
     *Aufgenommen 24.09.2026 mit SD-00 (Rahmenplan Fassung 111).* Anlass:
     Rahmenplan und Backlog sind auf 3 700 bzw. 10 000 Zeilen gewachsen,
     weil Steuerung, Register und Protokoll in denselben Dateien stehen und
     jede Fassung Erzähltext nachzieht. Konzept:
     `docs/konzepte/Konzept-SD-Steuerungsdokumente.md` (SD-00 bis SD-04; die
     Schreibregeln aus SD-00 gelten seit Fassung 111). Zeitpunkt: SD-01 bis
     SD-04 nach dem Merge des 10c-PR, auf eigenem Zweig von `main`.
     **Zuordnung: SD.** Mit ihm erledigen sich Nr. 177, 193, 196 und 199.
     *Stand 26.09.2026:* **SD-01 erledigt** — der Rahmenplan ist geschnitten
     (Fassungen 125 bis 127; Fassung 124 wörtlich in
     `docs/Rahmenplan-Archiv-2.md`, Verlauf in `docs/Rahmenplan-Verlauf.md`),
     Zweig `claude/serene-tesla-sqeno2`. **SD-M1 und SD-02 erledigt** (26.09.2026):
     Durchsicht der Betreiberin (E-SD-34 bis E-SD-36), dieser Backlog geschnitten
     (Kopfzeilen, `docs/Backlog-Erledigt.md`). Als Nächstes SD-03, das Werkzeug
     `tools/steuerung/`.

295. **Der Messstand hat keinen Schritt für die Statistik.** · gehört zu: 17 · Stand: teilweise · seit 24.09.2026
     *Aufgenommen 24.09.2026 aus Konzept P5c (F-P5c-40), als Anlass nach der
     Zuarbeit von Konzept BR (E-BR-07).* `tools/messstand/` misst die Seiten,
     die es kennt; die Statistik ist keine davon, und das Konto `messstand@…`
     fehlte auf der örtlichen Anlage (seit AP4 legt der Prüfstand es über
     `messen.py --frisch` selbst an, F-P5c-104). 10c AP7 baut die Statistik auf eine
     Zählung mit Obergrenze und einen neuen Index `missions(started_at)` um —
     ohne Messstand-Schritt gäbe es für die Zeiten keinen Beleg, nur ein
     `EXPLAIN` von Hand. *Weg:* Schritt `statistik` im Messstand (drei Reiter
     und `EXPLAIN`, Sitzung der BetreiberIn; fehlt das Konto, legt AP7 es nach
     E-PK-27 an). **Zuordnung: 10c AP7.**

297. **Der Bilderlauf lässt Breiten, die Admin-Rolle und Rollbehälter aus.** · gehört zu: 17 · Stand: offen · seit 24.09.2026
     *Aufgenommen 24.09.2026 aus Konzept P5c (F-P5c-41), als Anlass nach der
     Zuarbeit von Konzept BR (E-BR-07).* Es fehlen die Breiten 400, 1200 und
     1366; Seiten mit `rolle: admin` meldet er als BetreiberIn an, eine reine
     Admin-Sicht nimmt er nie auf; die Übersicht der Einstellungen steht nicht
     in `seiten.json`; Überlauf in einem Behälter, der selbst rollt, und der
     Vergleich mit einem früheren Stand misst er nicht. *Weg in 10c:* eigene
     Messungen über `tools/motor.mjs`, wo eine Abnahme sie braucht (AP2
     Zeilenhöhe bei 1440 und 390 px, AP7, AP9) — die Messungen führen diese
     Nummer als Anlass. Der Umbau des Bilderlaufs selbst gehört nicht zu 10c.
     **Seit Web 20.41.0 gilt dasselbe für die Rolle Support** (P5c/AP4): Ihre
     Sicht — drei Kacheln über der Liste, eine einspaltige Kontoseite ohne
     die Knöpfe, die der Support nicht darf, zwei Protokollreiter — nimmt der
     Bilderlauf nicht auf; belegt ist sie nur über die Rollenprobe (Menü,
     Liste, Reiter) und im Prüfdokument von Hand.
     **Zuordnung: 10c (Messungen); Umbau: nächste Backlog-Runde.**

299. **Die Kontoseite löscht ein Konto an `konto_loeschen()` vorbei.** · gehört zu: 17 · Stand: offen · seit 24.09.2026
     *Aufgenommen 24.09.2026 aus Konzept P5c (AP4, F-P5c-99).* Es gibt zwei
     Wege, ein Konto zu löschen: `konto_loeschen()` in `konto_lib.php` (die
     Selbstlöschung und der Verfall gehen darüber) und den Zweig
     `user_delete` in `admin_user.php`. Beide räumen dasselbe ab — die
     Konto-Backups nach der Wahl, die Spuren ausdrücklich vor der Kaskade,
     die Sperrvermerke — und jeder schreibt es selbst. Dass es zwei sind, hat
     schon einmal geschadet: Die Löschung durch die Verwaltung schrieb bis
     Web 20.40.0 keinen Eintrag `konto_geloescht`, weil nur der eine Weg ihn
     kannte. AP4 hat den Eintrag nachgetragen und den Weg stehen lassen — ein
     Umbau des Löschens gehört nicht in das Paket einer Rolle. *Weg:* Der
     Zweig ruft `konto_loeschen($uid, $mitSicherungen)`; was die Seite zusätzlich
     prüft (eigenes Konto, letzte BetreiberIn, abgetippte Adresse), bleibt
     davor. Nachweis: Rollenprobe (Konto löschen 403 beim Support) und ein
     Löschfall mit Spuren gegen `spur_zahlen()`. **Zuordnung: Backlog-Runde.**

300. **Die Hauptstufe des Prüfstands fährt die Plattformmatrix nur zur Hälfte.** · gehört zu: PK · Stand: offen · seit 24.09.2026
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
     Zuordnung PK-06 ff.; bis dahin je Paket von Hand.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 300.

301. **`Sandbox-Setup.md` 1 sagt, Firefox und WebKit starten im Container nicht — sie starten.** · gehört zu: 17 · Stand: teilweise · seit 24.09.2026
     Befund (Konzept RW, F-RW-02; Playwright 1.56): Firefox 142 und WebKit
     26 starten, laden eine Seite über `localhost` und rechnen WebCrypto;
     RW-04 hat die Rückwegprobe in beiden gefahren. Die Tabelle „Nicht im
     Abbild" nennt ihre Systembibliotheken als fehlend (Nr. 183); das gilt
     nicht mehr. Dabei gemessen (F-RW-23): Gegen `php -S` mit einem Arbeiter
     blieb WebKit in 2 von 4 Läufen beim zweiten Anmelden 90 s ohne
     Navigation, mit `PHP_CLI_SERVER_WORKERS=4` 3 von 3 grün.
     Erledigt (P5c/AP11, 25.09.2026): `hochfahren.sh` startet den Server
     mit vier Arbeitern (`lokal_starten.sh`, `lokal_einrichten.sh`, auch im
     Behälter für PHP 8.3) — Anlass war die Bedienprobe
     (`net::ERR_TOO_MANY_RETRIES`), nicht WebKit.
     Offen: die Zeile in `Sandbox-Setup.md` 1 berichtigen und datieren,
     Nr. 183 nachsehen; ob der Bilderlauf in `haupt` alle drei Motoren
     fahren kann, gehört zu Nr. 300.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 301.

318. **`pysyntax` sieht ungültige Escape-Folgen nicht, und zwei Werkzeuge tragen welche.** · gehört zu: 17 · Stand: offen · seit 24.09.2026
     *Aufgenommen 24.09.2026 mit dem Abschluss von Konzept BR,
     gefunden beim Belegen von P-BR-01 im Log des PR-Laufs 36037964804.* Im Tor
     meldet Python (ab 3.12) `SyntaxWarning: invalid escape sequence` für
     `tools/kettenaufrufe/pruefen.py:247` (`` \` `` im Docstring von
     `pruefe_block()`) und `tools/referenzdatensatz/generator/erzeugen.py:94`
     (`\d`), und `pysyntax` zählt trotzdem „57 Python-Werkzeuge geprüft, 0 mit
     Syntaxfehler". Örtlich (Python 3.11) ist es nur eine unterdrückte
     `DeprecationWarning`. Gemessen mit `python3 -W error`: **2 von 60**
     versionierten Python-Dateien. Beide Stellen sind älter als BR (Nr. 217
     und S4). Eine künftige Python-Fassung macht daraus einen
     `SyntaxError`, und dann bricht das Werkzeug ab, statt zu prüfen.

     *Weg:* beide Zeichenketten roh schreiben oder den Rückstrich
     verdoppeln; `pysyntax` übersetzt mit Warnungen als Fehler
     (`SyntaxWarning` und `DeprecationWarning`) und bekommt dafür einen Fall
     in seiner Selbstprobe. *Abnahme:* die Selbstprobe rot mit einer Datei,
     die `"\d"` enthält; `pysyntax` im Tor ohne Warnung. Klein, kein Risiko
     für die Anwendung. **Zuordnung: Backlog-Runde** (Vorschlag; oder das
     nächste Paket, das `tools/quelltext/` anfasst).

321. **Der Stilvergleich meldet unveränderte Stile als ungeplant, sobald eine andere Seite Elemente dazubekommt.** · gehört zu: 17 · Stand: offen · seit 24.09.2026
     Befund (P5c/AP7, F-P5c-126, gemessen): AP7 baute die Statistikseite um
     und ergänzte eine CSS-Regel; `gegen.sh` meldete 38 ungeplante
     Signaturen und 31 geplante, nicht gemessene — 23 davon an den
     Druckblättern aus AP5, die AP7 nicht anfasst. Gegenproben: neue Regel
     mit alter Statistikseite genau eine ungeplante, sauberer Stand 0 / 0.
     Die Signaturen hängen an der Nachbarschaft eines Elements in
     `seiten.html`: Eine wachsende Seite verschiebt die Stückelung
     (`chunks.py`), und dasselbe Element bekommt eine andere
     Eigenschaftsliste. AP7 ist über `--schreiben` gelöst (139 / 139), die
     Ursache nicht — jede Seite mit mehr Markup kann `geplant.txt` für
     Stellen umwerfen, die sie nicht berührt.
     Weg: jede Seite bekommt ein eigenes Stück, oder die Signatur entsteht
     ohne geerbte Eigenschaften aus dem Stückkontext; dazu ein Fall in der
     Selbstprobe (eine Seite um hundert Elemente verlängert, übrige
     Signaturen unverändert). Abnahme: eine fremde Seite wächst, der
     Vergleich bleibt 0 / 0. Anlass für `tools/stilvergleich/`.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 321.

322. **Welche Probe der Demo-Reset trifft, entscheidet die Reihenfolge der Muster.** · gehört zu: 17 · Stand: offen · seit 24.09.2026
     *Aufgenommen 24.09.2026 in P5c/AP7 (F-P5c-127), gemessen.* Der
     Demo-Bestand wird alle 30 Minuten neu eingespielt, und seine Einsätze
     bekommen neue Kennungen. Ein Prüfstand der Hauptstufe dauert rund 39
     Minuten; der Reset fällt also in jeden Lauf. Wen er trifft, hängt davon
     ab, welche Probe um diese Zeit läuft — und die Reihenfolge der Proben ist
     die ihres ersten Auftretens in den Mustern von `pruefablauf.json`. Ein
     neues Muster weit vorn mit Messstand und Bilderlauf (AP7) schob die
     Bedienprobe (51 / 55) und die GPX-Probe (204 von 204 ohne Gegenstück)
     hinter die Marke; am Ende eingereiht, liefen beide grün. **Die
     Reihenfolge ist damit eine Voraussetzung, die nirgends steht**, und wer
     ein Muster ergänzt, verschiebt sie, ohne es zu merken.

     *Weg:* entweder der Prüfstand hält den Demo-Reset für die Dauer des
     Laufs an (eine Marke, die der Job liest, wie die Sperre des Demo-Resets
     in F-P5c-117), oder `pruefablauf.json` bekommt eine ausdrückliche
     Reihenfolge der demo-empfindlichen Proben (`nach`), und `auswahl.py
     --selbstprobe` prüft sie. *Abnahme:* ein Muster mit Messstand ganz vorn,
     der Prüfstand bleibt grün. **Zuordnung: Backlog-Runde** (Prüfmittel).

323. **Referenzbestand und Demo-Fixture tragen noch Nutzlast 11.** · gehört zu: 17 · Stand: offen · seit 25.09.2026
     *Aufgenommen 25.09.2026 (P5c/AP8).* Die Referenz
     `referenz/einsatzdoku-backup-2026-09-15.edbak` und
     `server/demo/fixture.json.gz` stammen aus der Zeit vor Nutzlast 12; beide
     führen das leere Feld der Standortauswahl. Das ist **kein Fehler** — der
     Rückweg überliest es, und genau das zeigt der Kreislauf. Es kostet aber
     **zwei Übergangsregeln** in `vergleich/ausnahmen/edbak_umlauf.json`
     (`kopf.version` 11 → 12, das fehlende Feld), und die Fixture trägt eine
     Angabe, die keine Fassung mehr schreibt. *Zu tun:* Referenz und Fixture
     neu erzeugen, wie mit Nr. 173; danach werden die beiden Regeln
     ungenutzt und fallen, mit einem Satz im Änderungsverlauf. Die Regeln in
     `edbak-alt_umlauf.json` bleiben — die Altformat-Referenz ist eingefroren
     (Nr. 46). *Abnahme:* edbak-Kreislauf mit 0 unerklärten Abweichungen **und**
     0 ungenutzten Regeln.

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

327. **Die Rollenmatrix führt die Handlungen der BetreiberIn-Seiten nicht einzeln.** · gehört zu: 17 · Stand: offen · seit 25.09.2026
     *Aufgenommen 25.09.2026 in P5c/AP11 (Gegenlesung AP4).*
     `docs/Technik.md` 4.99p hat eine Zeile je Handlung auf den Seiten, die
     mehr als eine Rolle erreicht; die 25 POST-Handlungen und 7
     Seitenaufrufe hinter `require_betreiberin()` (`betrieb_server.php`,
     `betrieb_jobs.php`, `betrieb_updates.php`, `betrieb_status.php`,
     `betrieb_sicherheit.php`, `api/schluesselblatt_pruefen.php`) misst die
     Probe nur über das Tor der Seite. Eine Handlung, die dort **vor** dem
     Tor stünde, fände sie nicht. Dazu fehlt ein Platzhalter `{support}`: dass
     der Support andere Support-Konten nicht betreut (E-P5c-99), steht im
     Code, gemessen ist es nicht. *Zu tun:* die Zeilen nachtragen (Admin und
     Support 403, BetreiberIn `durch`) und den Platzhalter anlegen. *Abnahme:*
     Rollenprobe grün mit den neuen Zeilen; Gegenprobe: eine Handlung vor das
     Tor gezogen → rot. *Zuordnung:* Backlog-Runde (Schritt 17).

329. **Der Prüfstand fährt die Quelltextprüfungen mit eingerichteter Anlage, Stufe 1 ohne.** · gehört zu: 17 · Stand: teilweise · seit 25.09.2026
     Befund (P5c/AP11, F-P5c-171): Die Ankerprüfung lud `doku_lib.php`, die
     lud `db.php`, das ohne `config.php` abbricht — im Prüfstand grün, im
     ersten Lauf auf dem Pull Request rot, nach sechs Läufen des
     Prüfstands. Zweiter Fall derselben Lücke (F-P5c-172): `anker` stand
     als Riegel in `pruefablauf.json`, aber nicht als `--riegel` im Schritt
     „Prüfbericht gegenlesen" von `pruefung.yml`; `--alle-riegel` war im Tor
     rot, örtlich fährt den Schritt niemand.
     Erledigt: die eine Stelle (`doku_lib.php` braucht `db.php` nicht,
     Web 21.1.2). Offen ist die Lücke: Jedes künftige Quelltextwerkzeug,
     das eine Serverbibliothek mit `db.php` lädt, ist örtlich grün und im
     Tor rot.
     Weg: Der Prüfstand fährt `quelltext pruefen.sh --selbstprobe` und
     `alle` mit beiseitegelegter `config.php` — so, wie das Tor sie sieht —
     und `bericht.py lesen --alle-riegel` mit genau den `--riegel`, die
     `pruefung.yml` übergibt. Abnahme: mit dem Stand vor 21.1.2 zweimal rot
     (`anker`, fehlende Übergabe), mit dem heutigen grün.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 329.

331. **Zusammengesetzte Klassen der übrigen Bausteine sieht die Vollständigkeitsprüfung nicht.** · gehört zu: 17 · Stand: offen · seit 24.09.2026
     *Aufgenommen 24.09.2026 in Konzept BV (BV-03, F-BV-11), notiert und nicht
     mitgemacht.* BV-02 hat die Meldungstöne aufgelöst (Nr. 274): Ihre Liste
     ist geschlossen und steht in zwei Bausteinen. Dieselbe Grenze trifft
     andere Klassen, die zur Laufzeit entstehen. *(Hier stand als Beispiel
     `c-dc-<spalte>` — die Gegenprüfung P-BV-02 hat gezeigt, dass diese Klasse
     zwar erzeugt, aber an kein Element gesetzt wird; ein Mittel müsste genau
     das unterscheiden.)* Von den 62 Hinweisen „Regel im Stylesheet, im Markup
     nicht gefunden" (gemessen 24.09.2026) tragen **28** das Präfix eines
     Bausteins (`symbol-` 9, `pwq-` 5, `kennzahl-` 4, `karte-` 3, `blatt-` 2,
     `knopf-` 2, `zaehler-` 2, `plakette-` 1) — vermutlich zusammengesetzt,
     nicht einzeln geprüft. Verschwände eine dieser Regeln, meldete es nur die
     Tonprüfung am Aufruf — und die kennt vier Bausteine, und auch die nur
     dort, wo der Wert als Literal übergeben wird. *Weg:* je Baustein prüfen,
     ob sein Wertevorrat geschlossen ist; wo ja, wie in BV-02 lesen, wo nein,
     bleibt es beim Hinweis. **Zuordnung: Backlog-Runde** (Prüfmittel).

332. **Nach dem Aufnehmen fremder Migrationen misst der Prüfstand gegen das alte Schema.** · gehört zu: 17 · Stand: offen · seit 26.09.2026
     Befund (Konzept BV, F-BV-17, beim Aufnehmen von `main` nach P5c):
     `Pruefablauf.md` 5.3 misst nur, was die Arbeit gegen `main` ändert,
     nicht, was `main` mitbringt (F-PK-39); ohne eigene Migration ist die
     Stufe `klein`, und die Anlage wird nur gestartet, nicht nachgezogen.
     Mit P5c kamen Migrationen aus fünf Paketen, die Anlage stand auf dem
     Schema davor; `login.php` antwortete 200 mit „Fassung v21.1.2", der
     Nachweis sah nichts. Gemessen: Rollenprobe rot mit `Data truncated for
     column 'role'` (Rolle `support` fehlte im `ENUM`), nach
     `hochfahren.sh --neu` 20 grün, 0 rot. Diesmal laut — eine Probe, die
     die neuen Spalten nicht berührt, misst still gegen einen Stand, den es
     nirgends gibt, und ein grüner Bericht trägt einen Baum, den die Anlage
     nie hatte.
     Weg: `pruefen.sh` fragt vor den Proben, ob die Anlage offene
     Migrationen hat (dieselbe Frage wie der Torwächter), und ist bei
     Rückstand rot mit dem Weg (`hochfahren.sh --neu`) — oder richtet neu
     ein; dazu ein Satz in `Pruefablauf.md` 5.3, Schritt 2. Abnahme: Anlage
     auf `ba2ec57`, Baum mit `main` von `29cf394` → rot vor der ersten
     Probe, mit Ansage; nach `--neu` grün.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 332.

333. **Ein Kommentar in `style.css` nennt für die Umbenennung `.map` → `.geo` das falsche Paket.** · gehört zu: 17 · Stand: offen · seit 25.09.2026
     *Aufgenommen 25.09.2026 in Konzept BV, gefunden von
     der Gegenprüfung P-BV-02.* Der Kommentar über `.geo.map-fs` sagt „Der
     Kartenbehaelter wurde in O1 umbenannt". Gemessen: Das Stylesheet aus O1
     (`ecd5ff98`) enthält weder `.map` noch `.geo`; `.geo` kommt mit O2
     (`5436e854`), zugleich mit dem Markup der drei Karten. Harmlos, aber ein
     Satz, der eine Herkunft falsch nennt, schickt die nächste Suche in das
     falsche Paket. **Kein eigenes Paket** — Beifang für das nächste, das
     `server/assets/style.css` ohnehin anfasst (eine Zeile unter `server/`
     braucht eine Web-Stufe).

334. **Die Prüfwerkzeuge der Android-App hängen an keinem Lauf.** · gehört zu: 17 · Stand: offen · seit 24.09.2026
     *Aufgenommen 24.09.2026 mit Konzept AR (F-AR-05, E-AR-12).*
     `android/werkzeuge/kontraste.py`, `farbabgleich.py`, `bildmarken.sh` und
     `stroeme.py` werden von keinem Workflow, keinem Aufruf in
     `tools/pruefstand/pruefen.sh` und keiner Zeile in
     `tools/pruefstand/pruefablauf.json` gerufen — gezählt am 24.09.2026:
     0 Treffer außerhalb von `android/werkzeuge/`. Der Riegel `kontraste` im
     Tor ist das Web-Werkzeug `tools/screenshots/kontrast.py`. Was Nr. 116 an
     `kontraste.py` verbessert hat (Vollständigkeit je Modul und Rolle, mit
     Selbstprobe), läuft also nur, wenn jemand daran denkt.
     *Weg:* die vier unter das Muster `android/**` in `pruefablauf.json`
     hängen (sie brauchen nur Python und die Quellen, `braucht: nichts`),
     `kontraste.py --selbstprobe` dazu. Nicht in AR, weil
     `tools/pruefstand/` während P5c dessen Gebiet ist.
     *Abnahme:* eine Berührung unter `android/` wählt die vier im Prüfstand
     aus, der Bericht nennt ihre Zahlen. **Zuordnung: nach dem Merge von P5c**,
     von der Instanz, die `main` aufnimmt, oder der nächsten Backlog-Runde.

335. **Der Prüfstand erkennt die Ausbaustufe `android` an der falschen Plattform.** · gehört zu: 17 · Stand: offen · seit 24.09.2026
     *Aufgenommen 24.09.2026 mit Konzept AR (F-AR-07, E-AR-12).*
     `tools/pruefstand/pruefen.sh` prüft vor dem `android-bau`, ob
     `platforms/android-36` liegt. Seit Android 0.16.0 baut die App gegen
     **37.0**; die Zeile ist grün, wenn 36 liegt und 37.0 fehlt, und rot, wenn
     es umgekehrt ist. `tools/sandbox/aufbauen.sh android` installiert
     deshalb beide Plattformen, damit die Erkennung nicht fehlschlägt — das
     ist eine Krücke, keine Messung.
     *Weg:* die Erkennung an die Plattform hängen, die der Bau braucht (am
     besten aus `compileSdk` gelesen statt fest geschrieben), dann Plattform
     36 aus `aufbauen.sh` streichen. *Abnahme:* ohne `android-37.0` meldet
     der Prüfstand „Ausbaustufe android fehlt". **Zuordnung: nach dem Merge
     von P5c**, zusammen mit Nr. 334.

336. **Der Baustein `Eingabefeld` des Handy-Moduls wird nirgends aufgerufen.** · gehört zu: 17 · Stand: offen · seit 24.09.2026
     *Aufgenommen 24.09.2026 mit Konzept AR (AR-05).* `Eingabefeld()` in
     `handy/.../Bausteine.kt` hat seit R63 (Android 0.11.0, feste
     Serveradresse, Backlog Nr. 84) keinen Aufrufer mehr — das Adressfeld der
     Kopplung war sein einziger. Aufgefallen, als AR-04 die Farbe seines
     Cursors behob (Orange auf Schnee, 2,23 : 1) und der Emulatorlauf den
     Cursor zeigen sollte: Es gibt ihn auf keinem Bildschirm. Lint meldet es
     nicht, weil die Funktion öffentlich ist. Die Behebung bleibt richtig, hat
     aber keine sichtbare Wirkung.
     *Weg:* den Baustein austragen, oder ihn stehen lassen, wenn ein
     Eingabefeld absehbar wiederkommt — dann mit einem Satz, warum.
     *Abnahme:* kein unbenutzter öffentlicher Baustein in `Bausteine.kt`.
     **Zuordnung: nächste Android-Runde.**
