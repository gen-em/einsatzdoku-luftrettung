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
Nr. 100 einen Codeblock, Nr. 196). Vor jeder Kopfzeile stehen `<!-- -->`
und eine Leerzeile, sonst zählt GitHub die Nummern fort (Nr. 340,
E-R4-62). Ein gekürzter Eintrag endet mit
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
gestrichen; die nächste freie Nummer steht in der letzten Zeile. Seit R4-03
misst der Prüfstand es nach (`tools/steuerung/nummern.py`, Nr. 339): eine
neue Nummer, die `origin/main` oder ein anderer Remote-Zweig auch anlegt, ist rot.

| Spanne | Zweig | seit |
|---|---|---|
| 350 bis 359 | `claude/gallant-mccarthy-yacnzk` — Konzept 18, Sicherheitsrunde II (Kürzel SR); vergeben: 350, 351, 352 | 27.09.2026 |
| ab 360 | frei — höchste vergebene Nummer 352; 348 und 349 aus der Spanne von 17 blieben frei, 338 aus der von AR | 28.09.2026 |

---

## Offen

<!-- -->

21. **Die 43 weiteren Funde der A4-Nachlese sichten.** · gehört zu: 12b · Stand: offen · seit 23.08.2026
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

<!-- -->

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

<!-- -->

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
     Backup braucht (Begründung in Technik 4.97c). Denkbar ist stattdessen,
     die Liste je Ziel **einmal rekursiv** zu holen, wo das Protokoll es
     hergibt, und nur bei Zweifel nachzufragen. Erst messen, dann bauen.

<!-- -->

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

<!-- -->

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

<!-- -->

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

77. **Die Wartungsseite `update.php` in Unterseiten aufteilen.** · gehört zu: 12b · Stand: teilweise · seit 02.09.2026
     Befund: Die Seite trug Migrationsliste, Job-Einstieg, Speichergrenze und
     weitere Betriebsangaben auf einer Fläche.
     Entschieden 05.09.2026 (E-S8-05): nicht aufteilen, sondern **auflösen** —
     der Block Betrieb trägt sieben Seiten mit je einem Anliegen;
     Wartungsmodus und ausstehende Migrationen liegen zusammen auf Updates
     (R66). Gebaut in S8 AP2 bis AP4; `update.php` ist seit Web 15.2.0 eine
     302-Weiterleitung, der Notausgang `php update.php` bleibt.
     Weg: Offen ist allein, dass die Adresse noch existiert — das räumt P6.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 77.

<!-- -->

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

<!-- -->

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

<!-- -->

92. **`pruefstand.sh bildreihe` fotografiert nur den Startbildschirm.** · gehört zu: 12a · Stand: offen · seit 03.09.2026
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
     Ziel 12a seit 26.09.2026 (R4-01, Q-R4-12): Der erste Abnehmer einer
     Tastenfolge ist S11 (Uhr Haupt); dort entsteht die nächste Ansicht.

<!-- -->

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

<!-- -->

99. **Fassungsprüfung auf Klick.** · gehört zu: nach v1.0 · Stand: offen · seit 03.09.2026
     *Aufgenommen 03.09.2026 aus der Planung v1.0 (Rahmenplan R66, Option A2).*
     Ein Knopf „Auf neue Fassung prüfen" auf der Wartungsseite, der einmalig
     die GitHub-Releases-Schnittstelle fragt — kein Hintergrundlauf, kein
     Banner. Nur, wenn Selbsthoster es verlangen; die eigene Installation
     braucht es nicht, weil Betreiberin und Entwicklung dieselben sind.
     **Nach v1.0.**

<!-- -->

100. **Play-API-Upload aus der Auslieferungskette.** · gehört zu: nach v1.0 · Stand: offen · seit 03.09.2026
     *Aufgenommen 03.09.2026 aus der Planung v1.0 (Rahmenplan R67).*
     Upload-Schlüssel als GitHub-Secret plus Dienstkonto der Play-API; jeder
     grüne Tag landet von selbst auf dem internen Test-Track, die
     Produktionsfreigabe bleibt ein Klick in der Play Console. Vertretbar,
     weil nach Play App Signing der Upload-Schlüssel der zurücksetzbare ist;
     E-S4-16 dann um den Unterschied App-Signaturschlüssel / Upload-Schlüssel
     ergänzen. **Nach v1.0**, wenn die Releases häufiger werden.

<!-- -->

146. **Fragen an das Bedrohungsmodell P6 aus dem Krypto-Review.** · gehört zu: 12 · Stand: offen · seit 06.09.2026
     Zwei Fragen, keine Fehler (R78, 06.09.2026; bis 27.09.2026 drei):
     **Argon2id statt PBKDF2** (WASM-Fremdbestandteil gegen GPU-Resistenz)
     · **Inhaltsschlüssel als nicht-extrahierbarer `CryptoKey`** statt Hex
     im `sessionStorage` (ein XSS könnte dann entschlüsseln, den Schlüssel
     aber nicht mitnehmen; anderes Lebensdauermodell „ein Tab, ein
     Schlüssel"). Dazu die Design-Skizze für Weg B (Nr. 43, SP-9) zur
     Prüfung. Zuordnung R17 Stück 1.
     **Passkeys** stehen seit 27.09.2026 nicht mehr hier: als zweiter Faktor
     neben TOTP Nr. 350 (Schritt 18, SR-09); mit PRF als Ersatz der
     Passwortableitung von der Betreiberin nicht weiterverfolgt (E-SR-28).
     Stand seit S10 (Web 20.0.0, 14.09.2026): Der erste Punkt ist messbar
     kleiner — der Datenschlüssel hängt am Server-Anteil aus `config.php`,
     ein Datenbankabzug allein reicht für einen Offline-Angriff nicht mehr;
     Argon2id verteidigte gegen einen Angreifer, der ohnehin beides hat. Der
     zweite Punkt ist unberührt: Der Anteil schützt die Hülle, nicht den
     entpackten Schlüssel im `sessionStorage`.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 146.

<!-- -->

154. **Handy-App liest `kept_points` und `kept_meta` nicht.** · gehört zu: 13 · Stand: offen · seit 07.09.2026
     *Aufgenommen 07.09.2026 aus der Gegenprüfung des Sofortpakets (Nr. 134).*
     `Sendeantwort.kt` nimmt aus der Antwort von `ingest.php` nur
     `kept_phases` und `kept_resus` in den Sendebericht; ein Paket, dessen
     Punkte oder Metadaten der Server wegen des Ersetzfensters übergangen
     hat (`kept_points`, `kept_meta` — JSON-Vertrag 5), sieht am Handy wie
     ein Erfolg aus. Der Server sagt es; die App hört es nicht. Beide Felder
     in `Sendeantwort` aufnehmen und in der Ergebniszeile nennen; Prüffall in
     `SendeantwortTest`. Zuordnung: nächste Android-Stufe.

<!-- -->

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

<!-- -->

161. **Aus einer Aufzeichnung ein Stück löschen können.** · gehört zu: 12a · Stand: offen · seit 08.09.2026
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
     (CLAUDE.md 4), mit Rückfrage und einer Zeile im Protokoll.
     Ziel 12a seit 26.09.2026 (R4-01, Q-R4-12): S11 verlegt die
     Spurfunktionen in den Browser (Nr. 43); ein Schnitt über `spur_lib.php`
     davor wäre dort neu zu bauen.

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
     und die Prüfmittel dazu (`anteilprobe/endpunkt.py` Teil E,
     `umstellungslauf.mjs`, `huelle_stellen.py`). Nicht mitgehen: die
     Formatkennung selbst und die Rotation des Server-Anteils (E-S10-11).
     Reihenfolge: in einem Paket mit Nr. 46, sobald keine Anlage mit
     Altbestand mehr herüberkommt (Nr. 324). Abnahme:
     `grep -rn "anheben\|kdf_upgrade\|pat_anheben" server/` ist leer, ein
     frisches Konto liest seine Daten, Wortliste und Vollständigkeit melden
     keine ungenutzten Ausnahmen.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 187.

<!-- -->

188. **Eine Dokumentenprobe: kein Prüfmittel misst Verweise zwischen Dokumenten.** · gehört zu: 12b · Stand: teilweise · seit 14.09.2026
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

<!-- -->

198. **Die Zeitraumübersicht zählt Windendienste nur luftgebunden.** · gehört zu: nach v1.0 · Stand: nicht umsetzen · seit 14.09.2026
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
     Ziel `nach v1.0` seit 26.09.2026 (R4-01, Q-R4-12): kein „nie" im Vokabular.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 198.

<!-- -->

200. **Bounce-Postfach: Unzustellbares erkennen, nicht nur zählen.** · gehört zu: nach v1.0 · Stand: nicht umsetzen · seit 15.09.2026
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
     Ziel `nach v1.0` seit 26.09.2026 (R4-01, Q-R4-12): kein „nie" im Vokabular.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 200.

<!-- -->

201. **`Retry-After` in Uhr und Handy auswerten.** · gehört zu: 13 · Stand: offen · seit 15.09.2026
     *Aufgenommen 15.09.2026 (Konzept P5a, Befund 1.7).* Beide Clients
     behandeln jeden Antwortcode außer 200/400/401/403/413 als „später
     erneut" und wiederholen zum nächsten eigenen Anlass — die Kopfzeile
     `Retry-After`, die die Mengenbremse (E-P5a-02) mitschickt, liest keiner.
     Für Sperren von 10 bis 60 Minuten genügt das; sauberer wäre, die
     genannte Zeit abzuwarten statt bei jedem Auslöser anzuklopfen. Niedrig;
     Uhr-Stufe und Android-Stufe je eine Zeile in der Antwortauswertung
     (`Uploader.mc`, `Sendeantwort.kt`).

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
     in `install.php` und `wiederherstellen.php`, Ablage mit Zeitstempel
     (dreimal `gmdate('Y-m-d\TH-i-s\Z')`). Seit 26.09.2026 Ziel
     `Pflegeaufgabe` (R4-01, Q-R4-12): Jeder Anlass träfe Dateien von 18.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 202.

<!-- -->

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

<!-- -->

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

<!-- -->

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

<!-- -->

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

<!-- -->

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

<!-- -->

232. **Die Fristen der Rückfragen sind nie im Betrieb abgelaufen.** · gehört zu: 18 · Stand: nur auf Anlass · seit 17.09.2026
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
     Ziel 18 seit 26.09.2026 (R4-01, Q-R4-12): Alle Aufrufer von
     `einstieg_lib.php` liegen in Dateien, die Schritt 18 umbaut (Nr. 233).

<!-- -->

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

<!-- -->

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

<!-- -->

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

<!-- -->

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

<!-- -->

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

<!-- -->

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

<!-- -->

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

<!-- -->

250. **Umleiten nach POST auf den Admin-Seiten, die heute nicht umleiten.** · gehört zu: 18 · Stand: teilweise · seit 20.09.2026
     *Aufgenommen 20.09.2026 (Konzept Zentralisierung, F-ZE-4, aus Nr. 202 —
     `post_ende()`).* Zugeordnet: **Schritt 17**.

     Ein POST, der seine Seite selbst ausgibt statt umzuleiten, hinterlässt
     im Browser ein Formular, das sich beim Neuladen wiederholt. Ein Teil der
     Admin-Seiten macht es richtig, ein Teil nicht.

     **Nicht in Schritt 15**, obwohl der Befund dort entstanden ist: Schritt
     15 verschiebt Code an eine Stelle und ändert keine Wege durch die
     Anwendung. Umleiten nach POST ist ein geänderter Weg — er gehört in eine
     Runde, die Wege ändern darf.
     **Teilweise erledigt 27.09.2026 mit R4-11 (Web 21.1.9):** elf Seiten
     leiten um, `flash_setzen()` trägt Ort, Ton und Ergebnis (E-R4-33 bis
     -36). **Offen: `betrieb_server.php`** — auf der Liste von Schritt 18
     und deshalb dort (Konzept R4 2.3); der Weg ist derselbe.
     **Zuordnung in 18 (28.09.2026):** Paket SR-02, das `betrieb_server.php`
     ohnehin um eine Karte erweitert (Konzept SR, E-SR-37).

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
     (`Technik.md` 6.6b, Schritt 2) ebenso.
     Vorschlag an die Betreiberin (Prüfpunkt 28, vor M2): Aufbewahrung auf
     Staging auf 5 setzen; wie viele Tags man rückwirkend reparieren können
     will, weiß nur sie.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 261.

<!-- -->

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

<!-- -->

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

<!-- -->

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

<!-- -->

265. **Verweise von `.github/` in die Dokumentation hält kein Prüfmittel nach.** · gehört zu: PK · Stand: nur auf Anlass · seit 21.09.2026
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
     Ziel PK seit 26.09.2026 (R4-01, Q-R4-12): `auslieferung.yml` gehört der
     Kette (PK-06 bis PK-08).

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

<!-- -->

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

<!-- -->

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

341. **Sechs Stylesheet-Regeln ohne Verwender und ein Fokus-Zweig, den kein Element erfüllt.** · gehört zu: 12b · Stand: offen · seit 26.09.2026
     *Aufgenommen 26.09.2026 mit R4-05 (Konzept R4).* Die
     Vollständigkeitsprüfung meldet nach R4-05 noch 13 Hinweise „Regel im
     Stylesheet, im Markup nicht gefunden". Sieben haben einen Grund (vier
     `geo-ring-`, zwei `zaehler-`, deren Werte erst JavaScript bzw. ein
     Aufrufer wählt, und `leaflet-div-icon`, die Leaflet vergibt). Sechs
     haben keinen Verwender: `feld-reihe` (nur im Kopf von `ui_feld()`
     genannt), `karte-neben`, `kennzahl-raster-5`, `sd-titel`, `sd-zahl`,
     `symbol-gefuellt` (gemessen 26.09.2026). Dazu der Zweig
     `.focus-target[data-role=…]` in `fokus()` (`einstellungen.php`), den
     heute kein Element erfüllt (F-R4-25).
     *Weg:* im Aufräumpaket von P6 je Stelle nachsehen, ob ein Verwender
     vergessen wurde oder sie weg kann; `Design.md` mitziehen.
     *Abnahme:* Hinweise 13 → 7, jeder übrige mit Grund.

<!-- -->

342. **Die Einsatztabellen von Suche und Zeitraum rollen noch am Schreibtisch.** · gehört zu: nächste Backlog-Runde · Stand: offen · seit 26.09.2026
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

343. **`plattform.sh` scheitert an gedrosseltem Docker Hub, obwohl ein Spiegel geht.** · gehört zu: nächste Backlog-Runde · Stand: offen · seit 26.09.2026
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

344. **„Freigabe widerrufen" mit unauflösbarem Handgriff schreibt eine `konto.json` in die Wurzel der Konto-Backups.** · gehört zu: 18 · Stand: offen · seit 27.09.2026
     *Aufgenommen 27.09.2026 mit R4-11 (gefunden vom Umbau der Seite
     Konto-Backups, F-R4-33).* `edbak_freigabe_widerrufen()` prüft die
     Kennung nicht. Lässt sich der Handgriff eines POST nicht auflösen, ist
     die Kennung leer, und `edbak_begleit_schreiben('')` legt eine
     versiegelte `konto.json` in der Wurzel der Ablage an und meldet Erfolg:
     „Freigabe widerrufen." Erreichbar ist das über `admin_sicherungen.php`
     (dort gibt es für den Zweig kein Formular mehr, nur ein handgebautes
     POST einer Administratorin) und über die Kontoseite. **Nicht in 17**,
     weil `adminbackup_lib.php` für Schritt 18 frei bleiben soll (Konzept R4
     2.3, E-R4-37). *Weg:* `edbak_freigabe_widerrufen()` und
     `edbak_begleit_schreiben()` verlangen `edbak_kennung_gueltig()`, wie es
     `edbak_ordner_loeschen()` schon tut; die Aufrufer melden dann den
     Fehlschlag. *Abnahme:* POST `widerrufen` mit einem Handgriff aus
     Nullen → Fehlermeldung, keine Datei in der Wurzel der Ablage.
     **Zuordnung in 18 (28.09.2026):** Paket SR-03, das `adminbackup_lib.php`
     ohnehin offen hat; die Gegenlesung liest es mit (Konzept SR, E-SR-37).

<!-- -->

345. **Die Installationsseite meldet Erfolg, ohne zu wissen, ob gespeichert wurde.** · gehört zu: nächste Backlog-Runde · Stand: offen · seit 27.09.2026
     *Aufgenommen 27.09.2026 mit R4-11 (F-R4-34).* Zwei kleine Lücken auf
     `admin_installation.php`, beide älter als die Umleitung nach POST:
     „Logo-Standard" verwirft den Rückgabewert von `app_state_setzen()` und
     meldet „Standard der Installation: …" auch, wenn das Schreiben
     scheiterte. Und `instanz_namen_setzen()` / `instanz_adressen_setzen()`
     schreiben zwei Werte nacheinander und liefern nur `[bool, Text]`:
     Scheitert der zweite, ist der erste geschrieben, und die Seite hält das
     für einen Fehlschlag ohne Änderung (sie bleibt ohne Umleitung stehen,
     E-R4-35). *Weg:* den Rückgabewert prüfen; die zwei Setzfunktionen
     schreiben beide Werte in einer Transaktion oder sagen, was geschrieben
     ist. *Abnahme:* ein gescheitertes `app_state_setzen()` (Probe mit
     gesperrter Tabelle) ergibt eine Fehlermeldung statt „gespeichert".

<!-- -->

346. **Zwei Meldungen im Browser noch von Hand: der Hinweis `patwarn` und der Papierkorb-Hinweis des Diensttags.** · gehört zu: nächste Backlog-Runde · Stand: offen · seit 27.09.2026
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

347. **Zweimal an einem Tag standen zwei fremde Waisen in der örtlichen Anlage — woher, ist nicht geklärt.** · gehört zu: nächste Backlog-Runde · Stand: offen · seit 28.09.2026
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

350. **Passkeys als zweiter Faktor neben TOTP.** · gehört zu: 18 · Stand: offen · seit 27.09.2026
     *Aufgenommen 27.09.2026 in der Nachfassung des Konzepts SR (Paket
     SR-09, E-SR-29); herausgelöst aus Nr. 146, dessen Passkey-Frage damit
     beantwortet ist.* Ein TOTP-Code lässt sich auf einer gefälschten Seite
     abgreifen und weiterreichen; eine WebAuthn-Signatur ist an den Ursprung
     gebunden — der Code-Schritt wird phishingfest. **Bauform:** ohne
     Fremdbestandteil — `rw_pruefen()` prüft schon ECDSA P-256 mit phpseclib,
     `Crypt/RSA` liegt für RS256 daneben, es fehlt ein kleiner CBOR-Leser
     (`passkey_lib.php`, E-SR-30); Tabelle `passkeys` (Migration); die Karte
     „Zweitfaktor" bekommt den Abschnitt, der Code-Schritt den Knopf „Mit
     Passkey bestätigen"; Codes und Rückweg bleiben der Notweg; Anlegen und
     Entfernen verlangen einen frischen Code. **Prüfmittel:** Bedienweg mit
     dem virtuellen Authenticator Chromiums (CDP `WebAuthn`), Probe mit
     selbst erzeugten Vektoren (ES256, RS256, jede Ablehnung). **Nicht
     dabei:** Passkeys mit PRF als Ersatz der Passwortableitung (E-SR-28).
     *Abnahme:* Konzept SR, Paket SR-09; Prüfdokument P-SR-16.

<!-- -->

351. **Passkey als einziger Zweitfaktor, ohne Authenticator-App.** · gehört zu: nach v1.0 · Stand: zurückgestellt · seit 27.09.2026
     *Aufgenommen 27.09.2026 mit Q-SR-12 (E-SR-35).* Schritt 18 baut den
     Passkey als weiteres Verfahren neben dem eingeschalteten TOTP (Nr. 350);
     wer keine App will, hat damit keinen Zweitfaktor. Allein tragen dürfte
     der Passkey den Faktor erst, wenn drei Dinge umgebaut sind: die
     Wiederherstellungscodes entstehen ohne TOTP-Geheimnis (`totp_codes_*`
     hängen am Einrichten der App), das Einrichtungstor nimmt einen Passkey
     als Erfüllung der Pflicht an (`totp_an()` wird ein Faktor-Begriff), und
     der Reset-Weg (Verwaltung, Notzugang) kennt Konten ohne App.
     **Anlass:** eine NutzerIn, die nach einem Passkey ohne App fragt — bis
     dahin zurückgestellt, weil Support, Admin und BetreiberIn die App
     ohnehin haben. *Abnahme:* Zweitfaktor mit Passkey allein einschaltbar,
     Codes entstehen dabei, Rückweg und Notzugang gelten unverändert.

<!-- -->

352. **`nummern.py` meldet während eines offenen Merges die Nummern von `main` als Kollision.** · gehört zu: nächste Backlog-Runde · Stand: offen · seit 28.09.2026
     *Aufgenommen 28.09.2026 beim Aufnehmen von `main` in den Konzeptzweig
     zu 18 (Konzept SR, F-SR-14).* `Pruefablauf.md` 5.3 sagt: erst
     `git merge --no-commit`, dann der Prüfstand über den Arbeitsbaum, dann
     der Merge-Commit mit dem Bericht. Das Werkzeug misst den Arbeitsbaum
     aber gegen `merge-base(HEAD, origin/main)` — vor dem Commit ist das der
     alte Abzweigpunkt, und jede Nummer, die `main` seither vergeben hat
     (hier vier aus Schritt 17), steht im Baum als „neu" und in
     `origin/main` als „neu": Scheinüberschneidungen, der Bericht des
     Merge-Commits wäre rot. Nach dem Commit ist die Basis `origin/main`,
     und es sind null.
     *Weg:* Liegt `MERGE_HEAD` vor, die Nummern des Arbeitsbaums zusätzlich
     um die von `MERGE_HEAD` bereinigen (was der aufgenommene Zweig schon
     trägt, ist nicht neu); die Selbstprobe bekommt den Fall; 5.3 verliert
     den Satz zur Umgehung. Bis dahin: Merge-Commit ohne Bericht, der Bericht
     im Folge-Commit über denselben Baum samt Prüfdokument — so gegangen am
     28.09.2026. *Abnahme:* ein offener Merge mit einer Nummer aus `main`
     → 0 Überschneidungen.
