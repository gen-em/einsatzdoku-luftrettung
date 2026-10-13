# Konzept S11 — Ortsdaten verschlüsselt (Weg B)

**Kürzel:** `S11`. Arbeitspakete `S11-NN`, Entscheidungen `E-S11-NN`,
Befunde `F-S11-NN`, Fragen an die Betreiberin `Q-S11-NN`, Prüfpunkte
`P-S11-NN` (im Prüfdokument). Commit-Nachrichten beginnen mit dem Paket
(`S11-03: …`). Benennung nach `docs/Pruefablauf.md` 7.
**Rahmenplan:** Schritt **12a** — „S11 — Ortsdaten verschlüsselt (Weg B)"
(R78, R86); Voraussetzung „12 abgeschlossen" erfüllt am 11.10.2026 (PR #103).
**Herkunft:** Konzeptsitzung ab dem 11.10.2026. Eingang: Skizze SP-9 in
`Vorbereitung-Sicherheitspaket.md`, Vorstudie `Konzept-V1-Ortsdaten.md`,
`docs/Bedrohungsmodell.md` 6 mit E-R17-09 und -10. Die Punkte liefert
`python3 tools/steuerung/uebersicht.py --ziel 12a`: **vier Einträge** am
11.10.2026 — Nr. 43 (Weg B), Nr. 53 (Konto-Schlüsselpaar), Nr. 92
(Tastenfolge im Uhr-Bilderlauf), Nr. 161 (ein Stück einer Aufzeichnung
löschen).
**Modell:** **Opus** für jedes Paket, auch für das Konzept selbst. Der
Rahmenplan sah das Konzept mit Fable vor; die Betreiberin hat am 11.10.2026
auf Opus umgestellt (E-S11-01). Eine Fable-Gegenlesung ist nicht
vorgesehen: Schritt 12b liest S11 mit Fable ohnehin vollständig (R86).
**Fächerung (`CLAUDE.md` 7):** je Paket in der Tabelle in Abschnitt 5. Das
Konzept selbst hatte eine Fächerung: drei lesende Leser (Opus) für Server,
Garmin-Uhr und Android, ohne Nebenwirkung; Zusammenführung seriell.
**Versionsstufe:** wird in der Umsetzung festgelegt (R78: Web, Uhr und
Android je **Hauptstufe**). Das Konzept nennt keine Fassungsnummern.
**Ablage:** dieses Dokument; `Pruefdokument-S11-Ortsdaten.md` daneben.
**Zweig:** `ccr-dd33b6e8-ismfm0`, aufgesetzt auf `origin/main` bei `dbca560`
(Web 21.14.0, Uhr 3.1.0, Android 0.17.0). Push nach jedem Paket (K7).
**Backlog-Spanne:** **380 bis 389** (E-SD-21, eingetragen mit S11-00 vor
jeder Vergabe).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **11.10.2026 — HALT nach S11-02.** Probe-App, Prüfvektoren und Browserprobe stehen; im Simulator auf allen drei Uhren 7 gut, 0 Fehler. **Wartet auf P-S11-02:** die Betreiberin fährt die Probe auf Fenix 6 Pro, Forerunner 945 und Venu 3s. **Fenix 6 Pro, erster Lauf 13.10.2026:** alle sechs Vektorprüfungen gut, ECDH little-endian wie im Simulator, kein `deflate`, ein Schlüsselpaar 54–73 ms, Verschließen eines Stücks zu 200 Punkten rund 141 ms; die übrigen Zeitzeilen waren abgeschnitten (F-S11-23), zweiter Lauf mit der neuen Anzeige steht aus. Danach werden Stückgröße, Innenform und „ein flüchtiges Paar je Hülle" festgelegt (Vorschlag in 3.2a) und S11-03 beginnt. |
> | Entschieden | E-S11-01 bis E-S11-29 (Abschnitt 2.1); E-S11-26 bis -29 am 11.10.2026 während des Halts nach S11-02. E-S11-02 bis -15 und -25 sind die Antworten der Betreiberin auf Q-S11-01 bis -15 vom 11.10.2026; die Folgerung in E-S11-09 (einmal neu koppeln) und E-S11-22 (Nr. 92 und 161 in S11) hat sie am selben Tag bestätigt. E-S11-17 bis -21 und -23 stehen ohne Widerspruch. |
> | Offen | **P-S11-02** (Probe auf den drei echten Uhren, Zuarbeit). **Q-S11-18** (was misst „Seitenaufbau“, für welche Seiten gilt die Grenze aus E-S11-29?) — gebraucht ab S11-04. Q-S11-16 und -17 beantwortet (E-S11-26, -29). |
> | Umsetzung | **S11-00 erledigt** 11.10.2026: Spanne 380–389, Fahrplanzeile, dieses Dokument, Prüfdokument; Fragen Q-S11-01 bis -15 beantwortet, Freigabe erteilt. **S11-01 erledigt** 11.10.2026: drei neue Schritte im Messstand (Einsatz, Diensttag mit Ruhe-Spuren, GPX über ein Jahr), das Backup aus dem Bestand des Messstands; Grundlinie bei Drossel 6× und ungedrosselt in `tools/messstand/ausgangsmessung.md`; ein Zählfehler des Prüfmittels behoben (F-S11-14). **Nachtrag während des Halts:** Zeitraumübersicht für einen Monat und GPX-Export in Abschnitten in die Grundlinie, Prozessor im Protokoll (E-S11-28); Streuung zwischen zwei Läufen F-S11-22. **S11-02 bis zum Halt** 11.10.2026: Hüllenformat Fassung 1 in `tools/proben/huelle/referenz.py` mit 6 Vektoren und 8 Störungen; WebCrypto-Umsetzung `huelle.js` (Vorlage für `crypto.js`) byte-gleich, 39/0; Probe-App `tools/uhr-pruefstand/krypto-probe/` im Simulator 3 von 3 Uhren 7/0, für 99 Geräte übersetzt; Befunde F-S11-18 bis -21. S11-03 bis S11-12 offen. |
> | Halt | **jetzt**, nach S11-02 (Ergebnis der Probe-App auf den drei Uhren bestimmt die Hüllenparameter); in S11-04 nach dem Mockup der Ladeanzeige (E-S11-27); vor S11-12 (das Einmalwerkzeug muss auf Produktiv gelaufen sein). |
> | Fable-Schritte | keine (E-S11-01). |
> | Fächerung | Konzept: drei lesende Leser (Opus), siehe Kopf. Umsetzung: nach Abschnitt 5. |
> | Nummern | 380 bis 389 reserviert; vergeben: keine. |

---

## 1. Auftrag

**Ziel:** Der Einsatzort ist nicht mehr aus der Datenbank rekonstruierbar.
Spur, Koordinate jeder Phase, Reanimationsverlauf und Transportziel liegen
auf dem Server nur noch als Chiffretext. Der Server kann sie nicht öffnen.

**Zweck „Server"** (E-R17-09): Uhr und Handy verschlüsseln **vor dem
Upload**, nicht beim Schreiben. Der Puffer auf dem Gerät bleibt Klartext;
das verlorene Gerät bleibt benanntes Restrisiko (Bedrohungsmodell R13, R14).
„Beim Schreiben verschlüsseln" ist ein eigener, späterer Schritt, falls er
gewünscht wird.

**Der Weg (SP-9, R78):** ein Schlüsselpaar je Konto. Der öffentliche Teil
geht aufs Gerät; das Gerät verschließt damit. Der private Teil liegt unter
dem Inhaltsschlüssel gehüllt; nur der Browser öffnet nach dem Entsperren.

**Was Klartext bleibt** (E-R17-10, präzisiert durch E-S11-02 bis -04):
Zeiten der Phasen, Zeitraum je Spurstück, `seq`, Kennungen (`client_ref`,
`device_id`), Strecke und Anstieg als Zahlen, Stammdaten (auch die Liste der
Zielkliniken), Besatzung, Einsatzmittel, Diensttag und Standort.

**Was verschlüsselt wird:**

| Angabe | heute | nach S11 | Schreiber |
|---|---|---|---|
| Spur: Breite, Länge, **Höhe**, Punktzeit | `track_points`, `track_blobs` (SPUR1) | Gerätehülle je Spurstück | Gerät; Browser beim Schneiden, Wartungslauf, Import |
| Ruhe-Spur zwischen den Einsätzen | wie Spur | wie Spur (E-S11-04) | wie Spur |
| Koordinate jeder Phase | `mission_phases.lat/lon` | Gerätehülle je Einsatz | Gerät; Browser beim Bearbeiten |
| Reanimation: Sitzungen, Ereignisse, **samt Zeiten** | `resus_sessions`, `resus_events` | Gerätehülle je Einsatz (E-S11-02) | Gerät (nur Garmin); Browser |
| Transportziel samt Koordinate | `missions.transport_dest`, `dest_lat`, `dest_lon` | `pat_blob` über den Feldkatalog | Browser |
| Ortshöhe | `missions.site_ele_m`, vom Server gerechnet | `pat_blob`, vom Browser gerechnet (E-S11-03) | Browser |

**Nicht Umfang:** Verschlüsselung beim Schreiben auf dem Gerät; neue
Statistiken (E-S11-07); Rotation des Inhaltsschlüssels (F-R17-10, 12b);
Passkeys, `CryptoKey` (Nr. 370, 12b).

**Abnahme des Schritts:** In der Datenbank von Staging und Produktiv steht
keine Koordinate, keine Spurhöhe, kein Reanimationsereignis und kein
Transportziel mehr im Klartext — gemessen mit einer Probe, die jede Spalte
und Tabelle zählt, nicht mit einer Stichprobe. Alle heutigen Funktionen
rund um die Spur gehen weiter oder sind ausdrücklich entfallen (Abschnitt 3.6).
Die Leistungsgrenze aus E-S11-15 in der Fassung von E-S11-26 hält.

---

## 2. Entscheidungen, Fragen, Befunde

### 2.1 Entscheidungen (E)

| Nr. | Entscheidung | Wer, wann | Begründung, Preis |
|---|---|---|---|
| E-S11-01 | **Kürzel S11; Modell Opus für alle Pakete und das Konzept.** Ablage `docs/konzepte/`, Prüfdokument daneben; Spanne 380–389. | Betreiberin, 11.10.2026 („Opus auf High passt auch") | Der Rahmenplan sah das Konzept mit Fable vor. 12b liest S11 mit Fable vollständig (R86), damit hat der Krypto-Teil eine unabhängige Lesung. |
| E-S11-02 | **Die Reanimation geht ganz in die Hülle**, Sitzungen und Ereignisse samt ihrer Zeiten. Der Server sieht nicht, ob es eine Reanimation gab. | Betreiberin, 11.10.2026 (Q-S11-01) | E-R17-10 ließ „Zeiten" im Klartext; dann blieben Tatsache und Takt einer Reanimation lesbar, und das ist Gesundheitsdatum. Preis: Die Zeitverschiebung der Reanimation braucht relative Zeiten (E-S11-18); Zählungen im Papierkorb und beim Schneiden zählen Hüllen, nicht Sitzungen. |
| E-S11-03 | **Die Höhe jedes Spurpunkts wird verschlüsselt.** Die Ortshöhe rechnet der Browser und legt sie in den `pat_blob`. | Betreiberin, 11.10.2026 (Q-S11-02) | Höhe und Zeit grenzen im Bergland den Ort ein. Preis: Die Ortshöhe erscheint erst nach dem Entsperren; die Kachel „max. Höhe" der Zeitraumansicht rechnet aus dem entschlüsselten Block (sie läuft schon heute im Browser). |
| E-S11-04 | **Ruhe-Spuren werden genauso verschlüsselt** wie Einsatzspuren. | Betreiberin, 11.10.2026 (Q-S11-03) | Ein Format, ein Weg. Ruhe-Spuren zeigen Wohnort und Rückwege (Nr. 161). |
| E-S11-05 | **Verdichtung und Ausdünnung wandern in einen Wartungslauf im Browser.** Der Server kennt aus den Klartextzeiten, was fällig ist; der Browser holt, öffnet, packt oder dünnt, verschließt neu und gibt zurück; der Server tauscht nur, wenn sich das Stück inzwischen nicht geändert hat. **Zusammengefügt wird beim ersten Lauf, nachdem die Spur abgeschlossen ist**, ohne die bisherige Karenz von 14 Tagen. Ausgedünnt wird nach 31 Tagen (E-S11-25). | Betreiberin, 11.10.2026 (Q-S11-04) | Der Server kann ohne Koordinaten nicht ausdünnen; eine Ausdünnung auf dem Gerät verwirft Daten, bevor Korrekturen möglich sind. Die Karenz schützte Nachlieferungen in die Zeilentabelle; versiegelte Stücke lassen sich nach dem Zusammenfügen einfach anhängen. Preis: Wer sich nie anmeldet, belegt mehr Platz, verliert aber nichts. |
| E-S11-06 | **Nr. 53 wird mit S11 erledigt.** S11 baut das Schlüsselpaar; ein zweites Siegel über die Konto-Backups entfällt. | Betreiberin, 11.10.2026 (Q-S11-05) | Seit Web 12.0.0 sichert „Alle sichern" jedes Konto, seit S10 versiegelt mit dem Serverschlüssel. Nach S11 liegt alles Patientenbezogene darin Ende-zu-Ende verschlüsselt. Ein Siegel mit dem Kontoschlüssel verbärge nur, was der Server ohnehin im Klartext hat, und nähme der Verwaltung bei verlorenem Konto die Rückfallebene. |
| E-S11-07 | **S11 baut keine neuen Statistiken.** Die Annahme in F-SP-7 und E-R17-10, „Fahrten je Klinik" und „Reanimationen je Jahr" wanderten in den Browser, wird berichtigt: Es gibt sie nicht (F-S11-01). | Betreiberin, 11.10.2026 (Q-S11-06) | Nichts zu verlegen. Wer sie später will, baut sie im Browser. |
| E-S11-08 | **Das Einsatzformular behält die Phasenkoordinaten** beim Speichern (Behebung von F-S11-02). | Betreiberin, 11.10.2026 (Q-S11-07) | S11 baut genau diesen Code um; der Browser hält die Koordinaten nach dem Öffnen der Hülle ohnehin in der Hand. |
| E-S11-09 | **Kein Übergang zwischen alten und neuen Fassungen.** Die nächste Nutzung ist Ende November 2026; bis dahin sind Web, Uhr und Handy fertig. Der Server nimmt ab der Auslieferung **keine Klartext-Ortsdaten mehr an**. **Folgerung des Bearbeiters:** Der öffentliche Schlüssel kommt nur über die Kopplung (`bestaetigen` und `status` = `gekoppelt`); das eine laufende Gerät wird nach dem Aufspielen der neuen Fassung **einmal neu gekoppelt**. Ein Gerät ohne Schlüssel sendet nicht und sagt „Neu koppeln". | Betreiberin, 11.10.2026 (Q-S11-08); Folgerung bestätigt am selben Tag | Kein Klartextfenster, kein Schalter, kein Schlüsselweg über `ingest`. Bedrohungsmodell 6.3 nannte drei Antworten für den Schlüssel, darunter `ingest` für Bestandsgeräte; die dritte entfällt (F-S11-12). Preis: ein Handgriff an der Uhr; vorher muss die Uhr alles gesendet haben. |
| E-S11-10 | **Alte Sicherungen mit Klartext werden ersetzt.** Nach dem Einmalwerkzeug legt die Betreiberin neue Konto- und Komplett-Backups an; **ein** Komplett-Backup vom Stand vor S11 bewahrt sie 30 Tage offline auf; danach wird alles Alte gelöscht. Die externen Backup-Ziele und eigene `.edbak`-Dateien räumt sie von Hand (Zuarbeit). | Betreiberin, 11.10.2026 (Q-S11-09) | Sonst liegt der Einsatzort weiter in jeder älteren Sicherung. Die 30 Tage halten den Rückweg offen, falls die Umstellung etwas verliert. |
| E-S11-11 | **Geparkte Pakete auf der Garmin bekommen eine Frist von 30 Tagen** ab dem Parken, wie auf Android (AN-2). Die Parkmarke bekommt dafür einen Zeitstempel. | Betreiberin, 11.10.2026 (Q-S11-10) | F-R17-28; Lage H im Bedrohungsmodell. |
| E-S11-12 | **Gerätehülle für die Gerätedatenarten, `pat_blob` für die Webfelder, kein Anheben.** Spur, Phasenkoordinaten und Reanimation liegen immer in der Gerätehülle, gleich ob Gerät oder Browser sie schreibt (beide können mit dem öffentlichen Schlüssel verschließen). Transportziel und Ortshöhe gibt es nur im Web; sie gehen über den Feldkatalog (`store => 'pat'`) in den `pat_blob`. | Betreiberin, 11.10.2026 (Q-S11-11) | Das Gerät hat den Inhaltsschlüssel nicht und soll ihn nicht haben, kann den `pat_blob` also weder öffnen noch ergänzen (F-S11-03). Ein Anheben in den Block schüfe zwei Zustände je Einsatz, und jede Nachlieferung danach den ersten wieder. |
| E-S11-13 | **Die gemeinsame Prüfschicht prüft an der Gerätehülle Form und Größe**, wie heute `pruef_pat_blob()` am Patientenblock. Den Inhalt prüfen das Gerät beim Erzeugen und der Browser beim Öffnen; ein kaputter Wert erscheint als benannter Fehler am Einsatz. `CLAUDE.md` 4 bekommt diese Grenze in den Satz zur Prüfschicht. | Betreiberin, 11.10.2026 (Q-S11-12) | Was der Server nicht lesen kann, kann er nicht prüfen. Klartext bleiben Phasennummer und -zeit, Zeitraum und Punktzahl je Stück; diese prüft der Server weiter (3.4). |
| E-S11-14 | **Kein sichtbarer Prüfwert für den öffentlichen Schlüssel am Gerät.** Gegen eine Lage, die nur die Datenbank beschreibt, zwei Riegel: Der Server versiegelt den öffentlichen Schlüssel mit seinem Serverschlüssel und gibt ihn nur mit gültigem Siegel aus; der Browser vergleicht ihn bei jedem Entsperren mit dem privaten Teil und meldet eine Abweichung. | Betreiberin, 11.10.2026 (Q-S11-13) | Wer den Server übernimmt (C1), tauscht auch `crypto.js` und die Kopplungsmail; ein Prüfwert hülfe dort nicht (F-S11-04). Die Grenze wird wie bei `crypto.js` benannt. |
| E-S11-15 | **Leistungsgrenze:** Keine Einzelansicht (Einsatz, Diensttag) wird mehr als 1,5-mal langsamer als vor S11, und keine braucht über 1 s. Massenwege (Backup, GPX über einen Zeitraum, Wartungslauf, Einmalwerkzeug) werden gemessen und benannt, nicht begrenzt. Gemessen mit dem Messstand auf derselben Maschine vor und nach (S11-01, S11-11). | Betreiberin, 11.10.2026 (Q-S11-14) | Eine Spur hat im Mittel rund 330 Punkte (1 628 340 Punkte auf 4973 Spuren im Messstand); die Einzelansicht ist kein Risiko, die Massenwege sind es. Der Vergleich gilt nur auf derselben Maschine, nicht für ein echtes Telefon. |
| E-S11-16 | **Die Probe-App läuft auf den drei Uhren der Betreiberin** — Fenix 6 Pro, Forerunner 945, Venu 3s — im Simulator und auf der echten Uhr. Übersetzt wird sie mit, weil Stufe I des Uhr-Prüfstands bei jeder Änderung unter `watch/` für alle 99 Geräte übersetzt. Ob die Krypto auf den übrigen 96 Geräten **läuft**, bleibt ungeprüft und wird im Prüfdokument so benannt. | Betreiberin, 11.10.2026 | Die drei sind die Geräte im Einsatz; das Übersetzen der übrigen kostet nichts zusätzlich. |
| E-S11-17 | **Ein Hüllenformat für Garmin, Android und Browser:** ECDH auf P-256 mit flüchtigem Schlüsselpaar je Hülle, Schlüsselableitung HKDF-SHA256 (aus HMAC-SHA256 gebaut), AES-256-CBC mit Zufalls-IV, HMAC-SHA256 über Kopf, IV und Chiffretext („erst verschlüsseln, dann prüfen"), gebunden an Art und `client_ref` des Eigentümers und an den Platz des Stücks. Kein GCM, auch nicht auf Android. Endgültige Parameter mit Prüfvektoren in S11-02 und S11-03. | Bearbeiter, 11.10.2026; nicht widersprochen | Garmin kann kein GCM (SP-9). Zwei Formate hießen zwei Öffnungswege im Browser und doppelte Prüfvektoren. Die Bindung verhindert, dass jemand mit Schreibzugriff auf die Datenbank Hüllen zwischen Einsätzen vertauscht. Die `client_ref` übersteht Sicherung und Wiedereinspielen, die Datenbankkennung nicht. |
| E-S11-18 | **Zeiten:** Klartext bleibt der Zeitraum je Spurstück (`ts_von`, `ts_bis`), `seq_from` und die Punktzahl. Die Punktzeiten liegen in der Hülle **relativ zu `ts_von`**, die Reanimationszeiten **relativ zum Einsatzbeginn**. Ein Datumswechsel des Diensttags (`tageszuordnung_lib.php`) bleibt eine Serverfunktion: Er verschiebt die Klartextzeiten, die Hülle bleibt gültig. Die Bindung der Hülle schließt deshalb die Zeiten nicht ein. | Bearbeiter, 11.10.2026; nicht widersprochen | Strenger als SP-9 („Zeitstempel bleiben Klartext"), nicht schwächer: Die Punktzeiten selbst braucht der Server nicht (F-S11-09, -10). |
| E-S11-19 | **Zwei Webauslieferungen.** Die Hauptauslieferung bringt das neue Format und das Einmalwerkzeug. Nach dessen Lauf auf Produktiv folgt die **Aufräumauslieferung**: Werkzeug entfernt, Klartextspalten und -tabellen gelöscht. Ihre Migration verweigert, solange irgendwo noch Klartext-Ortsdaten stehen, und sagt, wo. | Bearbeiter, 11.10.2026; nicht widersprochen | Das Einmalwerkzeug muss mit dem Bestand laufen, bevor die Spalten fallen (F-SP-6); eine Migration, die Klartext löscht, darf nicht vor dem Werkzeug laufen. |
| E-S11-20 | **Der Demobestand wird beim Zurücksetzen vom Server versiegelt**, mit dem öffentlichen Schlüssel des Demokontos. | Bearbeiter, 11.10.2026; nicht widersprochen | Die Daten der Fixture sind erfunden und liegen im Repositorium; „Klartext geht nie an den Server" meint echte Patientendaten. Ein Demo-Reset im Browser wäre ein zweiter, unnötiger Weg. |
| E-S11-21 | **Keine neue Fremdbibliothek.** Krypto über WebCrypto, JCA und `Toybox.Cryptography`; Packen und Entpacken im Browser über `CompressionStream`/`DecompressionStream` (Format `deflate`, entspricht `gzcompress`). | Bearbeiter, 11.10.2026; nicht widersprochen | „Keine fremde Quelle zur Laufzeit"; `docs/Lizenzen.md` bleibt unberührt. Unterstützte Browser werden in S11-04 gegen die Liste in `docs/Technik.md` gehalten. |
| E-S11-22 | **Nr. 92 und Nr. 161 gehören in S11** (R4-01, Q-R4-12): Nr. 92 in S11-08 (Tastenfolge im Uhr-Bilderlauf, für die Ansicht „Neu koppeln"), Nr. 161 in S11-05 (ein Stück einer Spur löschen, im Browser, über `spur_lib.php`). | Rahmenplan-Zuordnung vom 26.09.2026; bestätigt von der Betreiberin am 11.10.2026 | Braucht Nr. 161 eine neue Darstellung, entsteht sie erst nach Freigabe mit Mockup (`CLAUDE.md` 5). |
| E-S11-23 | **Spuren weiter nur über `spur_lib.php`.** Die Bibliothek verwaltet Gerätehüllen statt Punkten; ihre Funktionen sprechen in Stücken und Zeiträumen. Ein Verbraucher, der an ihr vorbei liest, ist weiter ein Fehler. | Bearbeiter, 11.10.2026; nicht widersprochen | `CLAUDE.md` 4 („Spuren nur über `spur_lib.php`"). |
| E-S11-24 | **Nr. 236 (Ubuntu 26 am 19.10.2026) läuft parallel in einer eigenen Instanz**, auf einem eigenen Zweig. Gemeinsame Dateien sind höchstens `Backlog.md`, `Backlog-Erledigt.md` und `Rahmenplan-Verlauf.md`. | Betreiberin, 11.10.2026 | S11 ist vor dem 19.10. nicht fertig; der Wechsel kommt an dem Tag von selbst. Rahmenplan 4 verbietet Parallelität nur zwischen den Schritten 12a bis 14, Nr. 236 ist eine Pflegeaufgabe. |
| E-S11-25 | **Ausgedünnt wird 31 Tage nach dem Ende des Einsatzes oder Ruhe-Segments**, statt nach sechs Monaten (E-S2-03). Verfahren und Toleranzen bleiben (E-S2-05: Douglas-Peucker 3D, 2 m / 3 m, Phasenpunkte geschützt). | Betreiberin, 11.10.2026 (Q-S11-15) | Korrekturen sind im ersten Monat fertig; 31 Tage liegen hinter der Frist von 30 Tagen für geparkte Pakete (E-S11-11). Preis: Was `docs/Technik.md` 4.97 unter „Was nach der Ausdünnung nicht mehr gilt" nennt, gilt ab dem zweiten Monat — eine verschobene Phase findet ihre Ortshöhe nicht sicher, Reanimationszeitpunkte sind nicht geschützt, Nachlieferungen werden verworfen, GPX und Sicherung tragen die ausgedünnte Spur. |
| E-S11-26 | **Gemessen wird bei Drossel 6×, und dort darf eine Einzelansicht nach S11 höchstens doppelt so lange brauchen wie vorher** (Faktor 2 statt 1,5). Präzisiert E-S11-15 (Antwort auf Q-S11-16). Ob die absolute Grenze von 1 s daneben bestehen bleibt, ist Q-S11-17. | Betreiberin, 11.10.2026 (Q-S11-16) | Die 6× bilden das Referenzgerät aus S2 nach; ungedrosselt läge die Grenze auf einem Rechner, den niemand im Einsatz benutzt. Preis: Die Grenze ist großzügiger als 1,5. |
| E-S11-27 | **Wo der Browser spürbar rechnet, sieht man, dass etwas passiert** — eine Ladeanzeige an Einsatz, Diensttag, Export und Wartungslauf, solange Hüllen geöffnet oder verschlossen werden. Den Baustein gibt es nicht (`Design.md` 9 kennt nur die Textzeile `.zustandszeile`); er entsteht nach `CLAUDE.md` 5 **nur mit Mockup und Freigabe**. | Betreiberin, 11.10.2026 | Ohne Anzeige sieht eine Ansicht, die nach dem Entsperren eine Sekunde rechnet, kaputt aus. Folge: S11-04 beginnt mit dem Mockup und **hält an**, bis es freigegeben ist. |
| E-S11-28 | **Vorher und nachher nur auf demselben Prozessor.** Der Messstand schreibt seit S11-01 Prozessor, Kerne und Speicher in jedes Protokoll. Läuft S11-11 auf einem anderen Prozessor, wird zuerst die Grundlinie auf dem Code vor S11 (`dbca560`) auf derselben Maschine neu gemessen, dann der neue Stand. | Betreiberin, 11.10.2026 („aufpassen, falls andere Prozessoren ins Spiel kommen“) | Die Drossel bremst relativ zum Prozessor der Sandbox; die Sandboxen können wechseln. Preis: unter Umständen ein zusätzlicher Messlauf in S11-11. |
| E-S11-29 | **Neben dem Faktor 2 gilt eine feste Grenze für den Seitenaufbau: unter 3 s bei Drossel 6×, unter 1 s ungedrosselt** (Antwort auf Q-S11-17). Wie Verbesserungen aussehen, wird gemeinsam erarbeitet; was „Seitenaufbau“ misst und für welche Seiten die Grenze gilt, ist Q-S11-18. | Betreiberin, 11.10.2026 (Q-S11-17) | Die Grenze ist ein Ziel für die Bedienung, nicht nur ein Vergleich mit vorher. Preis: Bei Drossel 6× reißen sie heute schon Suche (3,96 s), Anmelden (4,60 s, absichtlich langsam wegen PBKDF2) und das „fertig“ der Zeitraumübersicht (Monat 4,04 s, Jahr 5,75 s); S11 darf nichts davon verschlechtern und muss Einsatz und Diensttag darunter halten. |

### 2.2 Fragen an die Betreiberin (Q)

| Nr. | Frage | Lage | Empfehlung | Preis | Antwort |
|---|---|---|---|---|---|
| Q-S11-01 | Reanimation ganz oder ohne Zeiten in die Hülle? | E-R17-10 ließ Zeiten im Klartext | ganz | relative Zeiten | **ganz** — E-S11-02 |
| Q-S11-02 | Höhe je Spurpunkt verschlüsseln? | in keiner Liste von E-R17-10 | ja, Ortshöhe in den `pat_blob` | Ortshöhe erst nach Entsperren | **wie empfohlen** — E-S11-03 |
| Q-S11-03 | Ruhe-Spuren verschlüsseln? | Ruhe-Spuren zeigen Wohnort und Wege | ja, gleich | keiner | **wie empfohlen** — E-S11-04 |
| Q-S11-04 | Ausdünnung nach sechs Monaten, wenn der Server sie nicht mehr rechnen kann? | erste Empfehlung „entfällt" abgelehnt: relevante Mengen | Wartungslauf im Browser für Zusammenfügen und Ausdünnen | Rechenzeit auf dem Gerät der NutzerIn | **Wartungslauf** — E-S11-05; Frist: Q-S11-15 |
| Q-S11-05 | Nr. 53 nur zur Hälfte bauen? | Konto-Backups gibt es, versiegelt seit S10 | Nr. 53 mit S11 erledigen | keiner | **wie empfohlen** — E-S11-06 |
| Q-S11-06 | Die zwei Statistiken bauen? | es gibt sie nicht | nein | keiner | **wie empfohlen** — E-S11-07 |
| Q-S11-07 | Verlust der Phasenkoordinaten im Formular in S11 beheben? | F-S11-02 | ja | keiner | **wie empfohlen** — E-S11-08 |
| Q-S11-08 | Übergang alter App-Fassungen? | erste Empfehlung: harter Schnitt mit Schalter | — | — | **kein Übergang nötig**: nächste Nutzung Ende November, beide Fassungen bis dahin fertig — E-S11-09 |
| Q-S11-09 | Alte Sicherungen mit Klartext? | Server, Ziele, eigene Dateien | neu anlegen, eines 30 Tage offline, dann löschen | Rückweg nur 30 Tage | **wie empfohlen** — E-S11-10 |
| Q-S11-10 | Frist für geparkte Pakete auf der Garmin? | F-R17-28 | 30 Tage ab Parken | Zeitstempel an der Marke | **wie empfohlen** — E-S11-11 |
| Q-S11-11 | Gerätedaten in eine Hülle neben dem `pat_blob`? | F-S11-03 | ja, ohne Anheben | zweite Chiffretextform | **ja** — E-S11-12 |
| Q-S11-12 | Was prüft die gemeinsame Prüfschicht? | `pruef_pat_blob()` prüft schon heute nur die Form | Server Form, Gerät und Browser Inhalt | Zusage in `CLAUDE.md` 4 wird eingegrenzt | **ja** — E-S11-13 |
| Q-S11-13 | Prüfwert für den öffentlichen Schlüssel? | F-S11-04 | kein sichtbarer; Server-Siegel und Abgleich | keiner | **wie empfohlen** — E-S11-14 |
| Q-S11-14 | Leistungsgrenze? | Messstand vorhanden | 1,5-mal, höchstens 1 s je Einzelansicht | Massenwege nur gemessen | **ja** — E-S11-15 |
| **Q-S11-15** | **Frist der Ausdünnung:** sechs Monate wie heute, oder 31 Tage? | Die sechs Monate sind ein Beschluss vom 30.08.2026 (E-S2-03): Vorhaltung des Originals als Korrekturfenster. Nach der Ausdünnung ist das Original weg: Eine später verschobene Phase findet ihre Ortshöhe nicht sicher, Reanimationszeitpunkte sind nicht geschützt, Nachlieferungen werden verworfen (E-S2-08), GPX und Sicherung haben nur noch die ausgedünnte Fassung. Die große Ersparnis bringt das **Zusammenfügen** (E-S11-05): Die Geräte schicken die Stücke ungepackt, gepackt ist eine Spur je nach Innenform etwa vier- bis zehnmal kleiner (geschätzt: binär rund 16, als JSON rund 40 Byte je Punkt gegen 3,9 in SPUR1; gemessen wird in S11-02). Die Ausdünnung behält danach 57 bis 74 % der Bytes (Technik 4.97), die frühere Frist spart nur bei den Spuren zwischen einem und sechs Monaten Alter. | **31 Tage, wenn Korrekturen erfahrungsgemäß im ersten Monat fertig sind**, sonst sechs Monate. 31 Tage liegen hinter der Frist von 30 Tagen für geparkte Pakete (E-S11-11). | bei 31 Tagen: Korrekturen nach dem ersten Monat arbeiten auf der ausgedünnten Spur | **31 Tage** — E-S11-25 |
| **Q-S11-16** | **Unter welcher Drossel gilt die Sekunde aus E-S11-15?** | Grundlinie 11.10.2026 (F-S11-15): Einsatz 1,28 s, Diensttag 1,71 s bei Drossel 6×; 0,45 und 0,47 s ungedrosselt. Mit 6× wäre die Sekunde schon vorher gerissen, und S11 hielte nur noch den Faktor 1,5. | 1,5-mal bei Drossel 6× (das Referenzgerät Z3), die Sekunde ungedrosselt. | bei 6× für beide: die Grenze ist heute nicht erfüllbar | **Drossel 6× bleibt, Faktor 2** — E-S11-26 |
| **Q-S11-17** | **Bleibt neben dem Faktor 2 eine absolute Grenze?** | E-S11-15 nannte „höchstens 1 s“; bei 6× liegt das Vorher schon darüber (1,28 und 1,71 s). | Keine absolute Grenze bei 6×; die Ladeanzeige (E-S11-27) deckt die Wartezeit ab. | ohne Deckel kann eine sehr große Spur lange brauchen | **doch: unter 3 s bei 6×, unter 1 s ungedrosselt** — E-S11-29 |
| **Q-S11-18** | **Was misst „Seitenaufbau“, und für welche Seiten gilt E-S11-29?** | Der Messstand kennt zwei Zahlen: „sichtbar“ (die Seite zeigt Inhalt) und „fertig“ (der Browser rechnet nicht mehr). Bei der Zeitraumübersicht liegen sie bei 6× um 1,7 bis 2,9 s auseinander. | **„sichtbar“ mit Ladeanzeige, wo noch gerechnet wird; gilt für jede Seite außer Anmelden** (PBKDF2 ist absichtlich langsam). S11 hält es für die Seiten, die es berührt; Suche und Zeitraumübersicht bekommen ein eigenes Paket oder einen Backlog-Punkt. | bei „fertig“ und allen Seiten: S11 bekommt zwei Baustellen, die es heute nicht verursacht | *offen* |

### 2.3 Befunde (F)

| Nr. | Gewicht | Fundstelle | Befund | Folge |
|---|---|---|---|---|
| F-S11-01 | Hinweis | ganzer Code; `Vorbereitung-Sicherheitspaket.md` F-SP-7; `Review-R17.md` E-R17-10 | Die Statistiken „Reanimationen je Jahr" und „Fahrten je Klinik" gibt es nicht. Die serverseitige Statistik (`statistik_lib.php`) zählt nur Einsätze, Herkunft, Konten und Geräte; die Zeitraumansicht rechnet im Browser und berührt nur `site_ele_m`. | E-S11-07; Berichtigung per Verlaufszeile. |
| F-S11-02 | mittel | `einsatz_form.php` (lädt die Phasen nur mit Nummer und Zeit); `einsatz_lib.php` `einsatz_phasen_ersetzen()` | Wer einen Einsatz von Uhr oder Handy im Formular speichert, löscht alle Koordinaten seiner Phasen: Das Formular lädt sie nicht und ersetzt die Phasen vollständig. Kein Hinweis, kein Protokoll. Der Rückimport erbt sie dagegen (`docs/Technik.md`, Import, `commit` mit `overwrite`). | E-S11-08, behoben in S11-05. |
| F-S11-03 | Hinweis | `Rahmenplan.md` Block 12a und Abschnitt 4 („baut auf `store => 'pat'` auf, nicht daneben") | Gerätedaten können nicht in den `pat_blob`: Er ist mit dem Inhaltsschlüssel verschlossen, den das Gerät nicht hat. Der Satz stimmt nur für die Webfelder. | E-S11-12; Rahmenplan berichtigt mit S11-00. |
| F-S11-04 | Hinweis | `Bedrohungsmodell.md` 6.3 („Prüfwert … in der Kopplungsmail genannt") | Die Kopplungsmail schreibt derselbe Server, der den Schlüssel tauschen würde; gegen C1 ist sie kein zweiter Kanal. | E-S11-14; das Modell wird mit S11-11 nachgezogen. |
| F-S11-05 | Hinweis | `Rahmenplan.md` Block 12a, SP-9 („Backup Fassung 4") | Containerfassung 4 gibt es seit Web 11.0.0. Gemeint ist die nächste **Nutzlastfassung** der `.edbak` (heute 12, `EDBAK_NUTZLAST`). | Rahmenplan berichtigt mit S11-00. |
| F-S11-06 | mittel | `watch/source/Uploader.mc` `_send()` | Die Garmin-Uhr schickt Phasen und Reanimation mit **jeder** Anfrage eines Einsatzes erneut, nicht nur mit der ersten. Mit der Hülle heißt das: eine Verschlüsselung je Anfrage, nicht je Einsatz. | Kosten in S11-02 messen; der Server ersetzt die Einsatzhülle je Anfrage wie heute die Phasen. |
| F-S11-07 | mittel | Connect IQ (`Toybox`) | Die Uhr kann nach Kenntnisstand nicht packen (kein `deflate`). Ihre Stücke kommen ungepackt; die Ersparnis von SPUR1 (3,9 Byte je Punkt) entsteht erst im Wartungslauf. | E-S11-05 (Zusammenfügen ohne Karenz); S11-02 bestätigt oder widerlegt. |
| F-S11-08 | niedrig | `watch/source/Uploader.mc` `istGeparkt()` | Die Parkmarke ist ein bloßes `true` ohne Zeitpunkt; eine Frist lässt sich daraus nicht rechnen. | E-S11-11, S11-08. |
| F-S11-09 | Hinweis | `spur_lib.php` `spur_kodieren()` | SPUR1 packt Zeiten und Koordinaten in **einen** gepackten Strom. „Zeiten bleiben Klartext" ist deshalb eine Formatänderung, nicht nur eine Verschlüsselung. | E-S11-18. |
| F-S11-10 | Hinweis | `tageszuordnung_lib.php` (Verschieben per SQL auf `resus_sessions`/`resus_events`) | Der Datumswechsel verschiebt Reanimationszeiten per SQL; mit E-S11-02 stehen sie in der Hülle. | E-S11-18 (relativ zum Einsatzbeginn). |
| F-S11-11 | Hinweis | `api/mission.php` (`start_src = 'prev_dest'`) | Der Server rechnet den Abfahrtort „letzte Zielklinik" aus `dest_lat`/`dest_lon` des Vorgängers. | In den Browser, wie schon `prev_site` (S11-05). |
| F-S11-12 | Hinweis | `Bedrohungsmodell.md` 6.3 | Das Modell nennt drei Antworten für den Schlüssel, darunter `ingest` für Bestandsgeräte. Mit E-S11-09 kommt er nur über die Kopplung. | Modell nachziehen in S11-11. |
| F-S11-13 | Hinweis | `android/handy/…/senden/Sendekoerper.kt` | Das Handy sendet keine Reanimation und kein Transportziel; die Wear-OS-Uhr hält keine Koordinaten. Auf Android sind nur Spur und Phasenkoordinaten zu verschließen. | S11-09 kleiner als S11-08. |
| F-S11-14 | niedrig | `tools/messstand/browserprobe.mjs` `messen()` | Die Zähler der Browserprobe (PBKDF2, JSON) leben je Dokument; ein Schritt, der navigiert, rechnete „nachher − vorher" über zwei Seiten. Gemessen in S11-01: **PBKDF2 = −1** im ersten Lauf von „Einsatz mit Spur öffnen". Frühere Zahlen anderer Schritte können ebenso danebenliegen (eine Ableitung zu wenig gezählt). | Behoben in S11-01: Hat das Dokument gewechselt, zählt allein die neue Seite. |
| F-S11-15 | mittel | E-S11-15; `tools/messstand/ausgangsmessung.md` (Grundlinie 11.10.2026) | **Die Sekunde aus E-S11-15 ist unter Drossel 6× schon vor S11 gerissen:** Einsatz 1,28 s, Diensttag 1,71 s (Median aus fünf). Ungedrosselt 0,45 und 0,47 s. Die Entscheidung sagt nicht, unter welcher Drossel gemessen wird; der Messstand drosselt in der Vorgabe 6× (Z3). | Q-S11-16. |
| F-S11-16 | Hinweis | Export Profil B mit GPX (`assets/export.js`) | Der GPX-Export über ein Jahr (4071 Einsätze, 8174 Spuren) braucht **389 MB Halde** — das Vierfache des Gerätebudgets Z3 (100 MB) — und 330 s unter Drossel 6×, schon vor S11. | Massenweg: gemessen und benannt (E-S11-15), keine Grenze. Nach S11 kommt das Öffnen der Hüllen dazu; S11-05 sieht nach, ob der Export in Portionen öffnen muss. |
| F-S11-17 | Hinweis | Abschnitt 5, S11-01 („vier neue Messungen … Backup erzeugen") | Das Backup misst der Messstand seit S2 („Backup erstellen" in der Browserprobe, `edbak_build()` in der Serverprobe). Ein vierter, neuer Schritt wäre eine zweite Stelle für dieselbe Prüfung. | S11-01 baut drei neue Schritte und nimmt das Backup aus dem Bestand in die Grundlinie. |
| F-S11-18 | mittel | `Toybox.Cryptography` (SDK 9.2.0); Probe-App, Simulator | **Garmin liefert das ECDH-Geheimnis little-endian**, WebCrypto und Python big-endian. Die Schlüssel sind je Koordinate byte-vertauscht und ohne 0x04 (so dokumentiert); für Z sagt die Dokumentation nichts. Gemessen im Simulator auf allen drei Uhren: Erst umgedreht stimmt Z mit dem Vektor. Ohne die Umdrehung leitet die Uhr andere Schlüssel ab, und keine ihrer Hüllen ginge im Browser auf. | Die Probe ermittelt die Ordnung und meldet sie (`ECDH gut (little-endian, umgedreht)`); **auf der echten Uhr zu bestätigen** (P-S11-02) — **bestätigt auf der Fenix 6 Pro am 13.10.2026**; FR 945 und Venu 3s stehen aus. S11-08 übernimmt die Umrechnung. |
| F-S11-19 | Hinweis | `api.mir` des SDK 9.2.0; Probe-App | **Kein `deflate` auf der Uhr** — bestätigt F-S11-07 am SDK (das Wort kommt nur bei Bildformaten vor) und im Simulator (`PACKEN nicht vorhanden`). | Stücke kommen ungepackt; gepackt wird im Wartungslauf (E-S11-05). |
| F-S11-20 | niedrig | Arbeitsumgebung; `docs/Sandbox-Setup.md` 1 | **Firefox und WebKit fehlen im Abbild vom 11.10.2026**: `/opt/pw-browsers` hält nur `chromium-1194`; `aufbauen.sh uhr` meldet beide als FEHLT, die Hüllenprobe bricht mit „Executable doesn't exist" ab (rc 2). `Sandbox-Setup.md` sagt, die Engine-Dateien lägen im Abbild. | Die Browserprobe ist nur in Chromium gelaufen; im Prüfdokument an erster Stelle. Ob WebCrypto in Firefox und WebKit dieselben Bytes schreibt, prüft S11-04 gegen die Browserliste. |
| F-S11-22 | mittel | `tools/messstand/ausgangsmessung.md`, zwei Läufe am 11.10.2026 | **Zwei Läufe desselben Codes auf derselben Maschine weichen um gut ein Drittel ab:** Diensttag bei Drossel 6× Median 1,71 s im ersten, 2,35 s im zweiten Lauf (je fünf Läufe). Gegen den Faktor 2 aus E-S11-26 ist das ein großer Teil des Spielraums. | S11-11 misst vorher und nachher je mindestens zweimal auf derselben Maschine (E-S11-28) und vergleicht die Spannen, nicht zwei Mediane. |
| F-S11-21 | Hinweis | `Toybox.Cryptography.createPublicKey()` (SDK 9.2.0) | Die Signatur nennt als ersten Parameter `HashAlgorithm`, gemeint ist `KeyPairAlgorithm` (die Beschreibung sagt es). | Im Code mit einem Cast und einem Kommentar; S11-08 übernimmt das. |
| F-S11-23 | niedrig | `tools/uhr-pruefstand/krypto-probe/source/KryptoApp.mc` (Fassung bis `e600735`) | **Die Zeitzeilen liefen auf dem runden Display über den Rand.** Auf dem Foto der Fenix 6 Pro vom 13.10.2026 sind die `T-…`-Zeilen links und rechts abgeschnitten, `ERGEBNIS` stand unter dem sichtbaren Bereich. | Behoben 13.10.2026: je Angabe eine Zeile, `ERGEBNIS` zusätzlich als erste Zeile; neue `.prg` an die Betreiberin. |
| F-S11-24 | Hinweis | `pruefstand.sh` (`simulator_starten`) | `kryptoprobe` mit der Ausgabe in eine Pipe (`… \| tail`) kehrt nach einem frischen Simulatorstart nicht zurück; mit der Ausgabe in eine Datei (wie im Prüfstand) und bei laufendem Simulator geht es, beides gemessen 13.10.2026 (rc 0). | Der Prüfstand schreibt in Dateien; von Hand die Ausgabe umleiten. Kein eigener Backlog-Punkt. |

---

## 3. Bauplan

Der Bauplan legt fest, **was** entsteht und **wo**. Die endgültigen Parameter
der Krypto stehen erst nach der Probe (S11-02) fest und werden hier
nachgetragen.

### 3.1 Konto-Schlüsselpaar

- **Erzeugt im Browser** (WebCrypto, ECDH P-256): bei der Erstvergabe des
  Passworts (`pw_handling.php`) und für Bestandskonten still beim nächsten
  Entsperren.
- **Privater Teil** unter dem Inhaltsschlüssel gehüllt, im selben Muster wie
  `rw_privat` (`docs/Technik.md` 4.99q) → neue Spalte an `users`. Damit gilt
  wie für alle Hüllen unter dem Inhaltsschlüssel: Der
  Wiederherstellungsschlüssel öffnet ihn ohne den Server-Anteil. Ein
  Passwortwechsel und `schluessel_erneuern` berühren ihn nicht.
- **Öffentlicher Teil** im Klartext an `users`, dazu ein Siegel mit dem
  Serverschlüssel (E-S11-14). Der Server gibt ihn nur mit gültigem Siegel aus.
- **Abgleich beim Entsperren:** Der Browser leitet aus dem privaten Teil den
  öffentlichen ab und vergleicht. Abweichung → Meldung an die NutzerIn und
  `system_melden()`, kein stilles Weiterarbeiten.
- **Prüfungen:** Format der Hülle wie `WRAP_RC_RE`; eine Hülle, die am
  Server-Anteil hinge, wird abgewiesen (`CLAUDE.md` 4).

### 3.2 Die Gerätehülle

- **Format** (E-S11-17): Kennbyte mit Fassung · flüchtiger öffentlicher
  Schlüssel (65 Byte, unkomprimierter Punkt) · IV (16 Byte) · Chiffretext ·
  HMAC (32 Byte). Gespeichert als Binärwert, übertragen als Base64.
- **Bindung** (Zusatzdaten in der HMAC): Art (`spur`, `ruhe`, `einsatz`),
  `client_ref` des Eigentümers, bei Spurstücken `seq_from` und Punktzahl.
- **Innen:** bei Spurstücken die Punkte (Breite, Länge, Höhe, Zeit relativ zu
  `ts_von`); bei der Einsatzhülle die Koordinaten je Phasennummer und die
  Reanimation (Zeiten relativ zum Einsatzbeginn). Die Innenform (JSON oder
  binär) legt S11-02 nach dem Speicher der Uhr fest.
- **Prüfvektoren:** ein Satz fester Eingaben mit erwarteten Hüllen unter
  `tools/proben/huelle/`, gegen den Browser, Android-Prüffälle und die
  Probe-App der Uhr laufen. **Anlass: Nr. 43.**

### 3.2a Ergebnis der Probe (S11-02) und Vorschlag

**Format Fassung 1** steht im Kopf von `tools/proben/huelle/referenz.py`, und
nur dort: Kennbyte 0x01 · EPK 65 Byte (SEC1) · IV 16 · AES-256-CBC mit PKCS#7
· HMAC-SHA256 über Kopf, Chiffretext und Bindung. Z ist die X-Koordinate
big-endian; HKDF-SHA256 mit `salt = EPK` und `info = "nadoku-huelle-1" ‖
Empfänger (SEC1)` liefert `k_enc ‖ k_mac`. Die Bindung ist je Feld zwei Byte
Länge plus UTF-8: Art, `client_ref`, Platz (`seq_from:punkte` oder leer) —
`client_ref` ist freier Text bis 64 Zeichen, ein Trennzeichen hätte nicht
getragen. Geprüft: Python und WebCrypto byte-gleich (39/0), die Uhr im
Simulator byte-gleich (3 × 7/0).

**Was die Uhr anders macht** (F-S11-18): Schlüssel je Koordinate
little-endian ohne 0x04, das Geheimnis Z little-endian. Die Uhr rechnet um;
Server und Browser sehen nur SEC1.

**Vorschlag zur Entscheidung nach P-S11-02** (Zahlen bisher nur aus dem
Simulator, also vom Rechner, nicht von der Uhr):

| Frage | Simulator (Fenix 6 Pro · FR 945 · Venu 3s) | Vorschlag |
|---|---|---|
| Innenform | binär 14 B/Punkt: 200 Punkte 2800 B, +5 kB, bauen 4–5 ms · JSON ~32 B/Punkt: 6385 B, +18 kB, bauen 10–14 ms; bei 500 Punkten 7000 gegen 16 285 B, +13 gegen +47 kB | **binär** — gut ein Drittel des Speichers und der Bytes, auf dem Gerät mit dem knappsten Speicher (128 kB) der Unterschied zwischen geht und geht nicht |
| ein flüchtiges Paar je Hülle | 0–2 ms je Paar | **ja**, wenn die echte Uhr unter rund 200 ms bleibt; sonst ein Paar je Anfrage mit getrenntem `info` je Hülle |
| Stückgröße | Verschließen 2–3 ms für 200 wie für 500 Punkte | nach den Zahlen der echten Uhr; Obergrenze ist der Speicher, nicht die Zeit |

### 3.3 Speicher (Server)

- **Spur:** eine neue Tabelle für Spurstücke je Eigentümer (`owner_type`,
  `owner_id`): `seq_from`, Punktzahl, `n_original`, `ts_von`, `ts_bis`, Stufe
  (1 Gerätestück ungepackt, 2 zusammengefügt und gepackt, 3 ausgedünnt), die
  Hülle. Das Format heißt **SPUR2**: eine Liste versiegelter Stücke. Die
  Fortsetzungsmarke `next_seq` rechnet der Server weiter aus den
  Klartextzahlen.
- **Einsatzhülle:** eine neue Spalte an `missions` (oder eine Tabelle 1:1 —
  Entscheidung in S11-04 nach dem Muster des Feldkatalogs). `mission_phases`
  behält Nummer und Zeit; `lat`/`lon` fallen mit der Aufräumauslieferung.
  `resus_sessions`/`resus_events` fallen ebenso.
- **Webfelder:** Transportziel samt Koordinate und Ortshöhe als
  Katalogeinträge mit `store => 'pat'`. Die Liste der Zielkliniken
  (`transport_dests`) bleibt Stammdatum im Klartext; die Vorschläge im
  Formular laufen weiter.
- **Wartungsmodus und `update.php`:** Die Hauptauslieferung bringt
  Migrationen (neue Spalten und Tabellen); nach dem Deploy muss eine
  Administratorin `update.php` aufrufen. Die Aufräumauslieferung bringt die
  zerstörende Migration mit Sperre (E-S11-19).

### 3.4 Eingang vom Gerät (`pair.php`, `ingest.php`)

- **Kopplung:** `bestaetigen` (Antwort `ok`) und `status` (`gekoppelt`)
  tragen den öffentlichen Schlüssel. Beide Geräte lesen ihn aus beiden
  Antworten, weil beide die Kopplung auch über `status` abschließen.
- **Nachricht `mission`/`rest_segment`:** `track` trägt `seq_from`,
  Punktzahl, `ts_von`, `ts_bis` und die Hülle; `phases` tragen Nummer und
  Zeit; dazu die Einsatzhülle. `distance_m`, `ascent_m` bleiben.
- **Abweisung:** Klartext-Koordinaten, -Höhen oder eine Reanimation im
  Klartext → `400` mit eigenem Fehlercode; das Gerät parkt. Ein Gerät ohne
  Schlüssel sendet gar nicht (E-S11-09).
- **Prüfschicht** (E-S11-13): `pruef_huelle()` neben `pruef_pat_blob()` —
  Zeichenvorrat, Mindest- und Höchstlänge, Kennbyte; Mengen über die
  Klartextzahlen (`LIMIT_TRACKPUNKTE_ANFRAGE`, `LIMIT_TRACKPUNKTE_SPUR`,
  `LIMIT_PHASEN`). Die Grenzen `LIMIT_REA_SESSION` und `LIMIT_REA_EREIGN`
  prüfen Gerät und Browser.
- **Ersetzfenster 72 h** und `kept_phases`/`kept_resus` bleiben in der
  Bedeutung; die Einsatzhülle wird als Ganzes ersetzt oder behalten.

### 3.5 Browser

- **Öffnen:** `crypto.js` bekommt Öffnen und Verschließen der Gerätehülle;
  der private Schlüssel wird nach dem Entsperren aus seiner Hülle geholt und
  lebt so lange wie der Inhaltsschlüssel.
- **Ansichten:** Einsatz (Karte, Phasen mit Nummern auf der Spur,
  Reanimation), Diensttag mit Ruhe-Spuren, Tagesspuren (heute
  `tag_spuren.php` serverseitig gezeichnet), Suche über das Transportziel.
  Ohne Entsperren: keine Karte, kein Klinik-Pin, keine Reanimation — mit
  dem Hinweis, warum.
- **Schreibwege:** Einsatzformular (Phasenkoordinaten bleiben, F-S11-02),
  Schneiden und Rücknahme, ein Stück löschen (Nr. 161), GPX-Abruf und
  GPX-Einlesen, CSV-Export und -Import, Abfahrt „letzte Zielklinik".
- **Wartungslauf** (E-S11-05): nach dem Entsperren, im Hintergrund, in
  kleinen Portionen: fällige Spuren zusammenfügen, nach 31 Tagen ausdünnen
  (E-S11-25; Douglas-Peucker 3D wie `spur_ausduennen()`, mit derselben
  Rundlaufprüfung), fehlende Ortshöhen nachtragen. Der Server liefert die
  Liste und tauscht nur bei unverändertem Stand.
- **Leistung:** Entpacken im Hintergrund-Thread, wo die Messung es verlangt.

### 3.6 Was aus jeder Serverfunktion wird

| Funktion heute | nach S11 |
|---|---|
| Verdichtung Stufe 1→2 (Job `verdichtung`) | Wartungslauf im Browser, ohne Karenz |
| Ausdünnung Stufe 2→3 (Job `ausduennen`) | Wartungslauf im Browser, nach 31 Tagen (E-S11-25) |
| Ortshöhe (`site_elevation_lib.php`) | Browser, Ergebnis im `pat_blob` |
| GPX-Abruf (`gpx_lib.php`, `gpx.php`) | Browser |
| GPX-Einlesen (`api/gpx_import.php`) | Browser liest und verschließt; Server speichert Hüllen |
| Schneiden und Rücknahme (`api/schneiden.php`, `spur_teilen()`) | Browser teilt und verschließt neu; Server tauscht beide Seiten in einer Transaktion und führt die Sperrvermerke |
| Zeitverschiebung (`spur_zeit_verschieben()`, Reanimation per SQL) | Server, über die Klartextzeiten (E-S11-18) |
| Zusammenführung von Diensttagen | unverändert; berührt keine Spur |
| Kartenzeichnung `tag_spuren.php` | Browser |
| Spurexport (`api/export_data.php`, `action=track`) | liefert Hüllen; Browser öffnet |
| Abfahrt „letzte Zielklinik" (`api/mission.php`) | Browser |
| Rundlauf- und Bereichsprüfung (`spur_blob_pruefen()`) | Form am Server, Inhalt im Browser |
| Backup `.edbak` (Browser) | nächste Nutzlastfassung: Spur, Phasenkoordinaten, Reanimation offen im passwortverschlossenen Container, wie die übrigen geschützten Angaben; beim Einspielen verschließt der Browser mit dem Schlüssel des Zielkontos. Ältere Nutzlasten bleiben lesbar (R11). |
| Konto-Backup und Komplett-Backup (Server) | tragen die Hüllen, wie sie liegen |
| Demo-Zurücksetzen | Server versiegelt die Fixture (E-S11-20) |

### 3.7 Geräte

- **Garmin:** Schlüssel aus der Kopplung in `Storage` neben `cred`;
  Verschließen je Anfrage (Spurstück und Einsatzhülle); Parkmarke mit
  Zeitstempel und Frist 30 Tage; ohne Schlüssel kein Senden, Anzeige „Neu
  koppeln". Die Anzeige auf der Uhr liest weiter den Klartextpuffer
  (E-R17-09).
- **Android:** Schlüssel aus der Kopplung in `tresor.bin` neben den
  Zugangsdaten; `Sendekoerper` verschließt Spur und Phasenkoordinaten;
  `Kopplungsdienst` liest den Schlüssel in `bestaetigen` und in `status`
  (F-R17-13). Die Wear-OS-Uhr bleibt unberührt; ihr Nachrichtenformat und
  dessen Zählung ändern sich nicht.

### 3.8 Einmalwerkzeug und Reihenfolge der Auslieferung

1. Uhr und Handy in der neuen Fassung bauen; Web-Hauptauslieferung auf
   Staging, Probe mit dem Referenzbestand.
2. Die Uhr sendet alles, was sie noch hat (Rückstand 0), mit der **alten**
   Fassung und dem **alten** Server.
3. Web-Hauptauslieferung auf Produktiv, `update.php`.
4. Neue Uhrfassung aufspielen, einmal neu koppeln (E-S11-09).
5. Das Einmalwerkzeug im Browser: erzeugt bei Bedarf das Schlüsselpaar,
   liest je Einsatz und Ruhe-Segment den Klartext, verschließt, schreibt
   zurück und löscht den Klartext in derselben Transaktion; setzt wieder
   auf, wenn es abbricht; zählt am Ende nach.
6. Neue Sicherungen, alte nach E-S11-10.
7. Aufräumauslieferung (E-S11-19), `update.php`.

---

## 4. Dokumente, die S11 fortschreibt

| Dokument | was |
|---|---|
| `CLAUDE.md` 4 | Klartextliste, Satz zur Prüfschicht (E-S11-13), Satz zu `spur_lib.php` (Hüllen statt Punkte), der Weg ins Blob (Gerätehülle neben `store => 'pat'`) |
| `docs/Bedrohungsmodell.md` | Abschnitt 3 und 6 neu nach S11 (vom Modell selbst so vorgesehen); W2, G3, R3, R13, R14; F-S11-04 und -12 |
| `docs/Technik.md` | 4.97 (SPUR2 statt SPUR1, Wartungslauf statt Jobs), 4.98 (Katalog der verschlüsselten Felder), 5a (Android), Runbook 6 und 7 |
| `docs/JSON-Vertrag.md` | Kopplungsantworten, Nachricht `mission` und `rest_segment`, Fehlercodes, Chunk-Größen |
| `docs/Backup-Format.md` | neue Nutzlastfassung, Spurteil, Rückweg älterer Nutzlasten |
| `docs/Export-Format.md` | Spur und Phasenkoordinaten im Export aus dem Browser |
| `docs/Handbuch.md` | Neu koppeln, Karte und Klinik-Pin erst nach Entsperren, GPX im Browser, Wartungslauf, ein Stück löschen (Nr. 161) |
| `android/LIESMICH.md`, `docs/Geraete-Eingabe.md` | Kopplung mit Schlüssel |
| `docs/rechtstexte/` | Hinweis für die zweite Runde der anwaltlichen Prüfung (Rahmenplan 6) |
| `docs/CHANGELOG.md`, `server/version.php`, `watch/source/Const.mc`, `android/version.properties` | je Auslieferung |
| `tools/messstand/`, `tools/proben/huelle/`, `tools/uhr-pruefstand/` | neue Messungen, Prüfvektoren, Tastenfolge (Nr. 92) |

---

## 5. Arbeitspakete

Eines nach dem anderen. Jedes Paket trägt seine Dokumente selbst nach
(`CLAUDE.md` 9); S11-11 liest am Ende gegen.

| Paket | Inhalt | Modell · Fächerung | Abnahme |
|---|---|---|---|
| **S11-00 Buchführung** | Spanne 380–389, Fahrplanzeile 12a auf „Konzept", Berichtigungen im Rahmenplan (F-S11-03, -05), dieses Dokument, Prüfdokument. | Opus · Konzeptlesung gefächert (drei Leser), Rest seriell | Werkzeuge der Steuerung grün; gepusht. **Erledigt 11.10.2026.** |
| **S11-01 Messstand: Grundlinie** | Vier neue Messungen im Messstand: Einsatz mit Spur öffnen, Diensttag mit Ruhe-Spuren öffnen, Backup erzeugen, GPX über einen Zeitraum. Grundlinie auf dem heutigen Code, **bevor** sich etwas ändert; Zahlen in `ausgangsmessung.md`. Keine Auslieferung. | Opus · seriell (Prüfarbeit) | Vier Zahlen je Messung mit Maschine und Datum; Messstand rc 0. **Erledigt 11.10.2026** — drei neue Schritte, das Backup aus dem Bestand (F-S11-17); Zahlen bei 6× und ungedrosselt. |
| **S11-02 Probe-App und Prüfvektoren** | Probe-App unter `tools/` (nicht ausgeliefert) mit genau den Aufrufen: Schlüsselpaar, ECDH, HKDF über HMAC, AES-256-CBC (Polsterung), HMAC; Packen vorhanden?; Laufzeit und Speicherspitze für 200 und 500 Punkte. Prüfvektoren in `tools/proben/huelle/` und eine Browserprobe dagegen. Stufe I übersetzt für alle 99. **Halt:** Die Betreiberin fährt die Probe auf den drei Uhren (Zuarbeit); das Ergebnis legt Stückgröße, Innenform und „ein flüchtiges Paar je Hülle" fest. | Opus · seriell | Probe auf drei Uhren im Simulator und echt, je mit Zahl; Vektoren stimmen im Browser; 99/0 übersetzt. **Bis zum Halt 11.10.2026:** Simulator 3 × 7/0, Browser 39/0, 99/0 übersetzt; echt steht aus (P-S11-02). |
| **S11-03 Schlüsselpaar und Hülle im Web** | Schlüsselpaar (3.1) mit Migration an `users`, Siegel, Abgleich beim Entsperren; Öffnen und Verschließen in `crypto.js`; Kopplungsantworten (3.4); `JSON-Vertrag.md` 1a; Bedrohungsmodell 4.1. | Opus · seriell | Prüfvektoren im Browser; Kopplung gibt den Schlüssel nur mit gültigem Siegel aus (Probe mit verfälschtem Wert); Abgleich meldet eine Abweichung. |
| **S11-04 Speicher, Eingang, Ansichten** | **Zuerst das Mockup der Ladeanzeige (E-S11-27), Halt bis zur Freigabe.** Tabelle der Spurstücke, Einsatzhülle, `spur_lib.php` auf Hüllen (E-S11-23), `pruef_huelle()`, `ingest.php` mit Abweisung von Klartext, `api/mission.php` und `api/day.php` liefern Hüllen, Browser zeichnet Karte, Phasen, Reanimation, Tagesspuren. Browserliste gegen `CompressionStream`. | Opus · seriell | Probe: Ingest mit Hülle gespeichert, mit Klartext abgewiesen; Einsatz und Diensttag zeigen dieselbe Karte wie vorher (Bilderlauf); Leistung gegen S11-01 (Faktor 2 bei Drossel 6×, E-S11-26). |
| **S11-05 Browserwege** | Einsatzformular mit Phasenkoordinaten (F-S11-02); Transportziel und Ortshöhe in den Feldkatalog; Schneiden und Rücknahme; ein Stück löschen (Nr. 161, Mockup falls neue Darstellung); GPX-Abruf und -Einlesen; CSV-Export und -Import; Suche; Abfahrt „letzte Zielklinik"; Zeitverschiebung über Klartextzeiten. | Opus · Umbau getrennter Dateien darf auf zwei Agenten gefächert werden (Formular und Feldkatalog · Schneiden und GPX), Doku seriell | Je Weg ein Durchgang im Browser mit Vorher-nachher-Vergleich; Kennzeichnung (`kennzeichnung-soll.md`) um die neuen Schlösser ergänzt. |
| **S11-06 Sicherung** | `.edbak` in neuer Nutzlastfassung; Rückweg älterer Nutzlasten; Konto- und Komplett-Backup mit Hüllen; Demo-Zurücksetzen versiegelt (E-S11-20). | Opus · seriell | Kreislauf `.edbak` je Paar der Plattformmatrix; Referenzdatei der 7.x/8.x-Linie liest sich (R11); Demo zeigt Karte nach Entsperren. |
| **S11-07 Wartungslauf** | Liste der fälligen Spuren am Server; Zusammenfügen und Ausdünnen im Browser mit Rundlaufprüfung; Tausch bei unverändertem Stand; fehlende Ortshöhen; Jobs `verdichtung` und `ausduennen` entfernt. | Opus · seriell | Frist 31 Tage (E-S11-25) an einer Spur knapp davor und knapp danach; Toleranzen 2 m / 3 m am Referenzbestand nachgemessen; Bytes je Punkt gegen Technik 4.97; ein gleichzeitig geändertes Stück wird nicht überschrieben. |
| **S11-08 Garmin-Uhr** | Schlüssel aus der Kopplung, Verschließen je Anfrage, Parkmarke mit Zeit und Frist, „Neu koppeln"; Tastenfolge für `bildreihe` (Nr. 92); `Const.mc`, Changelog Uhr. | Opus · seriell | Stufe I 99/0, Stufe II für die Klassenvertreter; Bilder der Ansicht „Neu koppeln"; Upload gegen die örtliche Anlage im Simulator. Gerätetest bei der Betreiberin. |
| **S11-09 Android** | Schlüssel aus `bestaetigen` und `status`; `Sendekoerper` verschließt; Prüffälle mit den Vektoren; `version.properties`, `android/LIESMICH.md`. | Opus · seriell | Prüffälle grün; Emulatorlauf nach `Pruefablauf.md` 6.9 oder Befund mit Zahl. |
| **S11-10 Einmalwerkzeug** | Werkzeug nach 3.8 Schritt 5, nach dem Muster `api/pat_anheben.php`; zählt vorher und nachher je Tabelle. Lauf auf Staging mit dem Referenzbestand. **Halt:** Lauf auf Produktiv durch die Betreiberin (Zuarbeit). | Opus · seriell | Staging: nach dem Lauf 0 Klartext-Ortsdaten, gezählt je Spalte; Karte vorher und nachher gleich. |
| **S11-11 Zusage, Gegenlesung, Abschluss der Hauptauslieferung** | `CLAUDE.md` 4, Bedrohungsmodell 3 und 6, Konsistenz der Dokumente aus Abschnitt 4; Messstand nach S11 gegen E-S11-15; Prüfdokument; Pull Request. | Opus · seriell (erzählender Text) | Leistungsgrenze gehalten oder als Befund benannt; Textprobe 0 neue Treffer; PR gestellt. |
| **S11-12 Aufräumauslieferung** | Nach dem Lauf auf Produktiv: Werkzeug entfernt, Klartextspalten und -tabellen per Migration gelöscht (mit Sperre, E-S11-19), SPUR1-Schreibwege entfernt (Lesen bleibt für ältere `.edbak`). | Opus · seriell | Migration verweigert auf einer Anlage mit Klartext-Rest (Probe); läuft auf einer sauberen; Stufe 2 grün. |

**Abhängigkeiten:** S11-01 vor allem anderen. S11-02 hält an. S11-03 vor
S11-04 bis -10. S11-08 und S11-09 brauchen S11-03 und den Eingang aus
S11-04. S11-10 braucht alles davor. S11-12 erst nach dem Lauf auf Produktiv.

---

## 6. Prüfprotokoll

Je Paket: was gemessen wurde, mit Mittel und Zahl. Die Prüfliste für die
Betreiberin steht im Prüfdokument.

| Paket | Mittel | Zahl | Datum |
|---|---|---|---|
| S11-00 | drei Leser (Opus, lesend) · Nachprüfung am Quelltext · `decken.py` · `uebersicht.py --ziel 12a` · `nummern.py` · `textprobe.py` · `bestand.py` · `linkprobe.py` · Prüfstand `klein` | 13 Befunde, davon F-S11-01 und -02 am Quelltext und F-S11-05 in `Backup-Format.md` nachgeprüft · 21/0 · 4 Einträge zu 12a · 0 Überschneidungen · 0 Treffer · 0 Befunde · 150/0 · Prüfstand 22 grün, Bericht in der Commit-Nachricht | 11.10.2026 |
| S11-01 | `messen.py --frisch` (Drossel 6×) · `messen.py --schritte browser --drossel 1` · Prüfstand `klein` | Einsatz 141 (1356 Punkte): Median 1,28 s / 0,45 s · Diensttag 30 (8 Ruhe-Segmente): 1,71 s / 0,47 s · Backup 58,54 s / 44,05 s · GPX Jahr 2026 (8174 Spuren): 329,6 s / 74,62 s, Halde 389 MB · 0 Konsolenfehler, rc 0 · Prüfstand erst 1 rot (`bestand`: Anleitung 42 statt 40 Zeilen), nach der Kürzung 22 grün, Bericht in der Commit-Nachricht | 11.10.2026 |
| S11-02 | `referenz.py --selbstprobe` und `pruefen` · `proben.sh huelle` (Chromium, Drossel 1 und 6) · `pruefstand.sh kryptoprobe` (Simulator) · `kryptoreihe` (99 Geräte) · Gegenprobe mit verfälschtem Vektor · `bestand` · `kettenaufrufe` · Prüfstand | 5/0 · 6 Fälle, 8 Störungen, 0 Abweichungen · 39 Prüfungen, 0 Abweichungen · 3 von 3 Uhren je 7 gut, 0 Fehler · 99 übersetzt, 0 fehlgeschlagen, 48 Warnungen (alle Launcher-Icon) · verfälschter Prüfwert: 5 gut, 2 Fehler, rc 1 · 0 Befunde · 0 Widersprüche · Bericht in der Commit-Nachricht · Firefox/WebKit nicht gelaufen (F-S11-20) | 11.10.2026 |
| S11-01 Nachtrag | `messen.py --schritte browser` (6×) und `--drossel 1` · Prüfstand | Monat 03/2026 (588): sichtbar 2,38 s / 0,45 s · GPX Jahr mit Passwort 329,6 s / 69,8 s, davon Packen 208,5 s / 47,9 s; ohne Passwort 270,2 s / 55,7 s · Diensttag 2,35 s statt 1,71 s im ersten Lauf (F-S11-22) · 0 Konsolenfehler, rc 0 · Bericht in der Commit-Nachricht | 11.10.2026 |

---

## 7. Quellen

- `docs/Rahmenplan.md` 3 (Schritt 12a, „Warum so"), 4, 7 (R78, R86)
- `docs/Bedrohungsmodell.md` 1, 2, 5, 6
- `docs/konzepte/Review-R17.md` 2.1 (E-R17-09, -10), 2.3 (F-R17-10, -13, -28)
- `docs/konzepte/Vorbereitung-Sicherheitspaket.md` SP-9, 6a (F-SP-5 bis -7)
- `docs/konzepte/Konzept-V1-Ortsdaten.md`
- `docs/konzepte/erledigt/Konzept-S2-Mengen-Spuren-Sicherung.md` (E-S2-03, -05, -06, -08, -19)
- `docs/Technik.md` 4.97, 4.98, 4.99q; `docs/JSON-Vertrag.md`; `docs/Backup-Format.md`
- `docs/Backlog.md` Nr. 43, 53, 92, 161, 236
