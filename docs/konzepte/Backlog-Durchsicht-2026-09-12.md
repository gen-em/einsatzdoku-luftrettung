# Backlog-Durchsicht mit Entscheidungen

*Erstellt am 12.09.2026 gegen `gen-em/einsatzdoku-luftrettung`, Zweig `main`,
Stand **Web 19.3.0 · Uhr 3.1.0 · Android 0.15.0**. Ersetzt die erste Fassung
dieser Durchsicht: Die dort offenen elf Fragen sind in der Sitzung vom
12.09.2026 beantwortet; die Zahlen sind entsprechend fortgeschrieben.*

**Was dieses Dokument ist.** Die Einordnung aller offenen Backlog-Punkte gegen
den heutigen Code, das Protokoll der getroffenen Entscheidungen und die Liste
dessen, was daraus am Rahmenplan zu ändern ist. **Die Nummern bleiben
unverändert.**

**Was es nicht ist.** Keine Codeänderung und kein Konzept. Die Umsetzung der
Entscheidungen steht aus; dieses Dokument sagt nur, was gilt.

---

## 0. Stand nach der Entscheidungsrunde

| | vorher | nachher |
|---|---|---|
| Offene Punkte | 61 | **58** |
| davon Klärung nötig | 37 | **26** |
| davon umzusetzen | 23 | **30** |
| an den P6-Review übergeben | — | **1** |
| wartet auf Zuarbeit | — | **1** |
| Zugeordnet „nach v1.0" | 9 | **8** |

**Ausgetragen sind drei Punkte:** Nr. 87 (beantwortet), Nr. 81 (geschlossen),
Nr. 88 (verworfen). Die Selbstprüfzahl in Rahmenplan Abschnitt 5 lautet damit
**58 = 58** und ist im selben Zug nachzurechnen.

Kein Punkt hat sich unbemerkt selbst erledigt — der Backlog ist in der Sache
belastbar. Gealtert sind Zahlen, Zeilennummern und sechs Aussagen.

---

## 1. Entscheidungsprotokoll

Sechzehn Entscheidungen, getroffen am 12.09.2026 in der Konversation.

| # | Punkt | Entscheidung | Folge |
|---|---|---|---|
| 1 | **Nr. 87** Web-App-Manifest | **Austragen.** Die Erhebung ist mit R70 beantwortet, der Fahrplan trägt die Umsetzung als P7 allein | Eine Zeile weniger in Abschnitt 5 |
| 2 | **Nr. 150** Cron-Pfad (a) | **`docs/CHANGELOG.md` wird rückwirkend berichtigt**, alle vier Stellen | Vier statt drei Stellen |
| 3 | **Nr. 150** Cron-Pfad (b) | **Platzhalter ohne `server/`** plus ein Satz, dass der Kopier-Knopf im Betreiberbereich der verlässliche Weg ist | Beide Fragen des Punktes beantwortet |
| 4 | **Nr. 21** 43 Restfunde | **An den P6-Review übergeben** (R69). Die Quellliste existiert nicht mehr; der Review geht ohnehin alles durch | Zuordnung Backlog-Runde → **P6** |
| 5 | **Nr. 62** Logofarben | **Neue Vorlagen anfordern.** Auch die beigelegten PNG tragen die alten Werte — es gibt keine korrigierte Quelle | Zuordnung Backlog-Runde → **Zuarbeit** |
| 6 | **Nr. 117** Backup-Vermerk | **Nicht erheben.** Im Handbuch sagen, dass die Anwendung es nicht weiß | Aus einer Entscheidung wird ein Satz |
| 7 | **Nr. 41** Klassen ohne Regel | **`imp-warn` und `imp-daygroup` bekommen eine Regel**, die drei Reste werden gestrichen | Zwei neue Darstellungen → Mockup |
| 8 | **Nr. 42** Unicode-Zeichen | **Beide durch SVG ersetzen** | `.rmx` neu bemaßen, `⚠` im Fließtext gestalten → Mockup |
| 9 | **Nr. 45** Dritte Kartengröße | **Bauen, Mockup zuerst** | Von „nach v1.0" zurück in die Arbeit |
| 10 | **Nr. 57** Tabelle zweimal gebaut | **Das gemeinsame Modul gewinnt** — mit einer bindenden Nebenbedingung (siehe 3) | Beschriftung, Ausrichtung und Hakenreihenfolge folgen dem Modul |
| 11 | **Nr. 124** Aktionsblatt | **Weg (b):** Das „⋯" bleibt hervorgehoben, solange das Blatt offen ist, und das Blatt fährt sichtbar aus seiner Richtung auf | Neun Seiten, Mockup |
| 12 | **Nr. 169** Besatzung am Adhoc-Rettungsmittel | **Später mit P5 entscheiden.** Bis dahin gilt (c), und Hinweis und Handbuch sagen es zutreffend | Zuordnung → **P5**, vertagt statt entschieden |
| 13 | **Nr. 81** App-Symbol | **Ohne Beleg schließen** — die wahrscheinliche Ursache ist behoben | Mit Wiederöffnungsbedingung (siehe 2) |
| 14 | **Nr. 88** Kachel „Einsätze je Gerät" | **Verworfen und ausgetragen** | Verweis in Nr. 80 muss mit heraus |
| 15 | Zuschnitt Gestaltung | Die vier Gestaltungsaufgaben (Nr. 41, 42, 45, 124) laufen **gebündelt als eigene Mockup-Runde** | Eine Freigaberunde statt vier |
| 16 | Zuschnitt Textpflege | **Fable entscheidet beim Rahmenplan**, ob die Textpflege eine eigene Kleinstufe wird oder mitläuft | Arbeitsliste steht in Abschnitt 8 |

---

## 2. Ausgetragen — mit den Vermerken, die mitgehen

Drei Punkte verlassen *Offen*. Keiner davon wird wortlos gestrichen: Jeder
bekommt den Satz mit, der erklärt, warum er dort steht.

### Nr. 87 — Weboberfläche als installierbare Web-App

Der Eintrag formulierte eine **Erhebung** („prüfen, was es braucht"). Sie ist
am 03.09.2026 mit **R70** beantwortet: Manifest allein, kein Service Worker
(Chrome auf Android verlangt seit Version 108 keinen), Umsetzung in **P7** mit
der Umbenennung, Name „NAdoku Web", eigenes Symbol, Nachweis am S24 und an
einem iPhone.

Gegengeprüft: kein Web-App-Manifest, kein Service Worker, kein `theme-color`
im Code; `apple-touch-icon` an zwei Stellen (`db.php` je Logo-Wahl, `ui.php`
als Rückfall). Der Stand ist derselbe wie beschrieben — die Erhebung hat ihn
nur bereits bewertet.

**Vermerk:** *Erhebung mit R70 (03.09.2026) beantwortet; die Umsetzung führt
der Fahrplan als P7. Ausgetragen, weil derselbe Auftrag sonst an zwei Stellen
stünde — das Muster, das Rahmenplan-Fassung 45 als Ursache von sieben falschen
Zuordnungen benannt hat.*

### Nr. 81 — App-Symbol in der Benachrichtigung

Geschlossen **ohne Beleg**, weil die wahrscheinliche Ursache gefunden und
behoben ist: `android:roundIcon` zeigte auf dasselbe adaptive Symbol wie
`android:icon` — ein Kategorienfehler. Eine Oberfläche, die `roundIcon`
bevorzugt und als fertiges Bild behandelt, zeichnet den Vordergrund auf die
volle Fläche, und das ergibt genau das gemeldete Bild. Das Attribut ist seit
**Android 0.11.1 (04.09.2026)** ausgetragen, die Begründung steht ausführlich
im Manifest. „Themed Icons" waren aus, die `<monochrome>`-Ebene also gar nicht
im Spiel — ebenfalls am 04.09.2026 geklärt.

**Der Backlog-Eintrag kennt nichts davon** und führt bis heute „Ob ‚Themed
Icons' eingeschaltet sind, ist noch offen". Er war der am stärksten überholte
Eintrag der ganzen Durchsicht.

**Vermerk:** *Geschlossen am 12.09.2026 ohne Beleg am Gerät. Die Erklärung im
Manifest ist eine Erklärung, kein Beweis — der Emulator führt AOSP und
beantwortet nicht, wie One UI den Benachrichtigungskopf zeichnet. **Wieder zu
öffnen ist der Punkt durch genau einen Fund:** ein Bildschirmfoto, auf dem das
Symbol unter Android 0.11.1 oder neuer immer noch angeschnitten erscheint.*

### Nr. 88 — Kachel „Einsätze je Gerät"

**Verworfen.** Der Punkt steht unter *Erledigt* nach dem Muster von Nr. 71
(„Regionen mit Unteradmins — verworfen, festgehalten"): als **Entscheidung**,
nicht als Erledigung.

**Vermerk:** *Verworfen am 12.09.2026. Die Daten liegen seit dem S4-Rest vor
(R64); es fehlte allein die Darstellung, und sie wird nicht gebraucht. Steht
hier als Entscheidung, nicht als Erledigung.*

> **Folge, die leicht übersehen wird:** Rahmenplan Abschnitt 5 verweist in der
> Zeile zu **Nr. 80** auf „die NutzerInnen-Sicht ist Nr. 88". Dieser Verweis
> zeigt jetzt auf einen verworfenen Punkt und muss mit heraus.

---

## 3. Entschieden — umzusetzen

Sieben Punkte haben ihre Antwort und warten nur noch auf die Ausführung.

### Nr. 57 — Tagesübersicht baut ihre Einsatztabelle ein zweites Mal

**Entschieden: Das gemeinsame Modul (`assets/missiontable.js`) ist die
Vorlage.** Beschriftung der Spalte „Sekundärtransport", Ausrichtung von Alter
und Beginn und die Reihenfolge der Haken folgen ihm; `index.php` zieht nach.

> **Bindende Nebenbedingung — am Code belegt und wichtiger, als sie klingt.**
> Die bedingte Anzeige von Winde und Bergwacht läuft über `cap_gate` im
> Feldkatalog: Ein Feld erscheint nur, wenn der Diensttag die passende
> Fähigkeit trägt. `index.php` holt seine Spalten über `mf_tagesspalten()` und
> bekommt das Verhalten geschenkt. **Suche und Zeitraumübersicht tun das
> nicht** — `api/range.php` und `api/suchindex.php` führen `winch`,
> `bergwacht`, `secondary` und `false_alarm` hart im SELECT.
>
> Genau die Eigenschaft, die erhalten bleiben soll, sitzt heute auf der Seite,
> die weichen soll. Sie muss beim Zusammenführen **ausdrücklich** mitgenommen
> werden. Geht sie verloren, fällt das erst an einem NEF-Tag auf, an dem
> plötzlich Windenspalten stehen — und dann sieht es aus wie ein neuer Fehler,
> nicht wie ein verlorener Vertrag.

Weiterhin gilt, was die Backlog-Runde 2 nachgemessen hat: Es sind **zwei**
Erzeuger und **vier** Spaltenlisten (sechs mit den beiden API-Endpunkten), das
Sortierblatt ist dreifach vorhanden, und die beiden Sortierungen behandeln
Gleichstände verschieden — „nur den Erzeuger zusammenführen, 0 Pixel bewegen
sich" trifft nicht zu. Geschätzt zweieinhalb bis drei Tage.

### Nr. 41 — Klassen ohne Regel

**Entschieden:** `imp-warn` und `imp-daygroup` bekommen eine Regel — dort ist
tatsächlich etwas schief (ein Warnhinweis, der wie Fließtext aussieht, und eine
Gruppenüberschrift, die wie eine Datenzeile aussieht). `rea-kopf`,
`rea-beginn` und `phasen-name` werden gestrichen und **mit Begründung** in
`tools/vollstaendigkeit/streichliste.md` eingetragen.

**Korrektur am Eintrag:** Es sind **fünf**, nicht sechs. `rmneu` ist in S9/AP1
beantwortet und steht bereits auf der Streichliste („Ja, sie hebt sich ab —
sie ist eine Handlung, kein Datensatz").

Die zwei neuen Regeln sind zwei **neue Darstellungen** und gehen in die
Mockup-Runde (Abschnitt 4).

### Nr. 42 — Unicode-Zeichen im Markup

**Entschieden: beide ersetzen.**

- `assets/ortsfeld.js:244` — `×` am Koordinaten-Chip. Der Knopf `.rmx` ist
  textgroß gebaut und muss neu bemaßt werden.
- `assets/patient.js:133` — `⚠` für einen nicht entschlüsselbaren Datensatz.
  Es steht **mitten im Satz**; ein SVG im Fließtext braucht Grundlinie und
  eine Größe relativ zur Schrift. Der fummeligere der beiden Fälle.

Das dritte Zeichen (`einsatz_form.php:1617`, `✕` als Rückfall, wenn
`edSymbol()` beim synchronen Aufbau noch nicht geladen ist) **bleibt** und wird
als begründete Ausnahme dokumentiert.

**Zahl fortgeschrieben:** Die Prüfung meldet heute **255** Treffer statt 195 —
die echten sind weiterhin drei. Neu dazugekommen sind **8 Emoji**, alle im
Kommentar von `assets/pwquality.js`, der erklärt, warum `schriftzeichen()` in
Grapheme zerlegt. Dieselbe Sorte Rauschen: Die Zahl wächst mit dem Text, nicht
mit dem Problem.

### Nr. 45 — Dritte Kartengröße

**Entschieden: bauen, Mockup zuerst.** Eine mittlere Fassung über die volle
Breite des Diensttags, über der Liste. Geht in die Mockup-Runde.

**Folge für den Rahmenplan:** Der Punkt verlässt „nach v1.0"; die Zahl dort
geht von **9 auf 8**.

### Nr. 124 — Aktionsblatt öffnet weit weg von seinem Knopf

**Entschieden: Weg (b).** Das Blatt bleibt unten — es folgt der
Plattformkonvention und liegt im Daumenbereich —, aber der Zusammenhang wird
sichtbar: Das „⋯" bleibt hervorgehoben, solange das Blatt offen ist, und das
Blatt fährt erkennbar aus seiner Richtung auf. Betroffen sind **neun**
`ui_aktionen()`-Aufrufe (der Eintrag sagt zehn). Geht in die Mockup-Runde.

### Nr. 150 — Cron-Befehl mit dem Repositoriumspfad

**Beide Fragen beantwortet.** Alle vier Stellen werden berichtigt,
`docs/CHANGELOG.md` eingeschlossen. Künftig steht dort ein Platzhalter ohne
`server/` (etwa `php /pfad/zur/installation/jobs.php`) plus ein Satz, dass der
Kopier-Knopf auf Betrieb → Hintergrundjobs der verlässliche Weg ist.

**Fundstellen neu gemessen** (die im Eintrag genannten sind alle verschoben):

| Stelle | Eintrag nennt | steht heute in |
|---|---|---|
| `server/jobs.php` | 13 | **13** (unverändert) |
| `docs/Technik.md` | 2424 | **2674** |
| `docs/Technik.md` | 5220 | **6042** |
| `docs/CHANGELOG.md` | 6210 | **8722** |

Nicht betroffen und ausdrücklich richtig: `server/betrieb_jobs.php:293` baut
den Befehl über `__DIR__`. `docs/CHANGELOG.md:2522` beschreibt den Fund selbst
und ist **kein** fünfter Fall.

### Nr. 117 — Niemand weiß, ob eine NutzerIn je ein Backup gezogen hat

**Entschieden: nicht erheben.** Ein Zeitstempel wäre eine neue Erhebung über
eine Handlung der NutzerIn; bei einer Anwendung, deren Versprechen die
Ende-zu-Ende-Verschlüsselung ist, wäre das kein Nebenprodukt. Stattdessen sagt
das Handbuch, dass die Anwendung es nicht weiß und was die Kennzahlen
tatsächlich messen.

Gegengeprüft: `users` trägt keine solche Spalte; die Kennzahlen messen über
`edbak_konto_stand()` ausschließlich die Konto-Backups der Verwaltung, und S8
hat sie deshalb bereits ehrlich umbenannt („Konto-Backup überfällig", „nie
Konto-Backup"). **Damit ist der Punkt die billigste Umsetzung im ganzen
Backlog: ein Absatz.**

---

## 4. Neues Paket — Mockup-Runde

**Entschieden: gebündelt statt verteilt.** Vier Gestaltungsaufgaben, eine
Freigaberunde statt vier.

| Nr. | Was gestaltet wird | Umfang |
|---|---|---|
| **41** | Zwei Regeln für die Importvorschau: Warnhinweis „abweichende Crew" und Kopfzeile einer Tagesgruppe | zwei Bausteine, eine Seite |
| **42** | `×` am Koordinaten-Chip (Knopf `.rmx` neu bemaßen) und `⚠` im Fließtext | zwei Stellen, zwei verschiedene Fragen |
| **45** | Dritte Kartengröße zwischen klein und Vollbild | eine Seite |
| **124** | Aktionsblatt: Knopf hervorheben, Blatt sichtbar auffahren | ein Baustein, **neun** Seiten |

Alle vier ändern die Darstellung und brauchen nach `Design.md` 1 Mockup und
Freigabe. Wo das Paket im Fahrplan landet, entscheidet Fable — naheliegend ist
vor oder in **P7 (Gesicht v1.0)**, weil dort ohnehin an der Oberfläche
gearbeitet wird.

> **Ein Hinweis zum Zuschnitt.** Nr. 124 ist das einzige der vier, das aus
> einer Rückmeldung von außen kommt. Wenn die Runde als Ganzes ins Rutschen
> gerät, sollte dieser Punkt sie verlassen dürfen — eine gemeldete
> Bedienschwierigkeit sollte nicht auf drei Aufräumarbeiten warten.

---

## 5. Klärung weiterhin nötig — 26 Punkte

Die Entscheidungen liegen hier innerhalb der Entwicklung oder sind bewusst
vertagt. Alle sind gegen den Code geprüft und bestätigt.

| Nr. | Kurz | Was zu klären ist | Zuordnung |
|---|---|---|---|
| **17** | Mengenbremse `ingest.php` | Die Messgrundlage liegt (Spitze 14 Anfragen an einem Auslöser, 174 Abstände von 0 s, Median 1 020 s). Die **Grenze** ist festzulegen: Stoß zulassen, über die Zeit deckeln. Bestätigt: kein Topf `ingest`, kein `rate_erlaubt()`/`rate_misserfolg()`. **Korrektur:** Der Eintrag sagt „die drei übrigen offenen Endpunkte" und listet vier; `RATE_GRENZEN` führt heute **neun** Töpfe | P5 |
| **23** | Vertrag nennt `beginn` | Der Vertrag ist die führende Quelle; ihn zu ändern ist eine Entscheidung. Bestätigt: `JSON-Vertrag.md` 3.3 listet zehn Arten inkl. `beginn`, `ingest.php:623` speichert es still nicht, `einsatz_form.php:328` weist es ab | P7 |
| **46** | Altformat abschaffen | Offen: **Was geschieht mit einer alten Datei nach dem Stichtag?** Dazu der Ersatzweg für `tools/messstand/`. **Korrektur:** Die Fassungsweiche steht **nicht** in `vergleich/lesen.py`, sondern in `vergleich/kreislauf.py` (`--art edbak-alt`) samt `ausnahmen/edbak-alt_umlauf.json` | P7 |
| **49** | Aufbewahrung am Ziel | Nicht *ob*, sondern **wer haftet**: eigene Zahl je Ziel, die ausdrücklich eingeschaltet werden muss — oder bloße **Anzeige** des Belegten | P5 |
| **50** | Versand liest je Konto ein Verzeichnis | **Erst messen.** Bestätigt: `sz_versand_schub()` ruft `$weg->liste($kennung)` je Kontoordner je Ziel | nach v1.0 |
| **51** | Suche verarbeitet 5 000 für 200 | Wird die Entschlüsselung verschoben? Das ändert das Verhalten spürbar. **Vorher messen**, welcher der drei Posten vor der Tabelle wiegt. Bestätigt: `EdPat.entschluessleListe()` läuft über alle | nach v1.0 |
| **52** | WebDAV als viertes Ziel | Festlegung über die **Zertifikatsprüfung**. Bestätigt: `Zielweg` mit zwei Adaptern, `SZ_PORTS` kennt ftp/ftps/sftp | nach v1.0 |
| **54** | Migrationslauf nach Wiederherstellung | Eigene Datei für `$MIGRATIONS`, oder Weiterleitung auf `update.php` mit der Anmeldung als Bestätigung. Der zweite Weg ist billiger | P5 |
| **55** | Kein scharfer Schnappschuss | Lohnt es, den Lauf auf **eine** Anfrage zu zwingen, und wo läge die Grenze? `komplett_lib.php` nennt die Einschränkung selbst im Kopf | nach v1.0 |
| **65** | 14 Fassungshinweise, AGP 9 | Die Entscheidung ist **eine** und bewusst vertagt. Gemessen: AGP 8.13.2, Kotlin 2.1.21, Compose-BOM 2025.06.01, wear-compose 1.4.1; `abortOnError = true`, nichts stummgeschaltet. **Die Zahl 14 ist vier Android-Stufen alt** (0.11.x → 0.15.0) und hier nicht nachzählbar | Backlog-Runde |
| **76** | Demo-Reset alle 30 Minuten | **Erst messen**, dann entscheiden. Bestätigt: `demo_reset_wenn_faellig()` prüft nur die Zeit | Backlog-Runde |
| **80** | Auswertung der Gerätestatistik | **Die Datenschutzerklärung ist Vorbedingung.** Der Teil ohne diese Bedingung ist gebaut (Betrieb → Statistik). Offen: Herkunft je Einsatz und Betriebslage-Dashboard. **Der Verweis auf Nr. 88 muss heraus** | P5 |
| **90** | Simulator kann keinen Verbindungsabriss | Proxy vor dem Simulator, oder Prüf-Einstellung in der App (dann Fremdkörper im Auslieferungscode) | nach v1.0 |
| **96** | Uhr und Handy sagen nicht, dass gewartet wird | Bestätigt: **kein** `503`- oder `Retry-After`-Zweig in beiden Clients | nach v1.0 |
| **99** | Fassungsprüfung auf Klick | Nur, wenn Selbsthoster es verlangen. Bestätigt: keine GitHub-Releases-Abfrage | nach v1.0 |
| **100** | Play-API-Upload | Wenn die Releases häufiger werden. Bestätigt: `deploy.yml` kennt kein Play | nach v1.0 |
| **114** | Abgewiesene Pakete | **Ausleiten** (braucht ein Format) oder **Verwerfen mit Rückfrage** (Datenverlust auf Knopfdruck)? Räumteil und Anzeige stehen (`Puffer.abgewieseneRaeumen()` ab Zeile 566, `R.plurals.sync_abgewiesen`) | Backlog-Runde |
| **116** | Kontrastwerkzeug misst nur seine Paarliste | Paare aus dem Quelltext **ableiten** oder nur eine **Vollständigkeitsprüfung**? Das Zweite ist billiger und fängt denselben Fehler. Bestätigt: 24 feste Paare; `tools/screenshots/kontrast.py` hat dieselbe Bauart | Backlog-Runde |
| **121** | Vorschau der Rechtstexte beim Tippen | Ein zweiter Renderer im Browser ist ausgeschlossen (E-P3-38). Bliebe ein Abruf gegen den Server beim Innehalten. Bestätigt: die serverseitige Vorschau steht und zeigt den gespeicherten Stand | Backlog-Runde |
| **122** | Freie Zeiträume und Diagramme | Zwei neue Darstellungen **und** die Zusage „keine fremde Quelle zur Laufzeit" — eine Diagrammbibliothek müsste vendoriert werden. Bestätigt: `STAT_ZEITRAEUME` fest auf 7 / 30 / 180 | P5 |
| **140** | Push auf `main` ist Deploy | **Die Wache steht.** Offen: Branch-Schutz und 2FA-Zwang (Zuarbeit, nicht im Repositorium prüfbar) und das Deploy-Tor mit dem Staging-Aufbau | Zuarbeit / S10 |
| **146** | Fragen an das Bedrohungsmodell | Argon2id, nicht-extrahierbarer `CryptoKey`, Passkeys/PRF. Bestätigt: Schlüssel weiterhin als Hex im `sessionStorage`; der Schlüsselcache seit Web 12.1.0 ist **Vorarbeit, nicht Lösung** | P6 |
| **161** | Aus einer Aufzeichnung ein Stück löschen | Gehört mit Nr. 43 betrachtet. Bestätigt: `trash_delete_day()` nimmt die Ruhezeiten mit, `spur_lib.php` kennt **keine** Löschung eines Zeitraums | Backlog-Runde |
| **169** | Besatzung am Adhoc-Rettungsmittel | **Vertagt auf P5.** Bis dahin gilt (c) „so lassen und im Text sagen"; Hinweis und Handbuch sagen es seit Web 18.1.1 zutreffend. **Kein dritter Zustand** | P5 |
| **170** | Kein Prüfmittel misst die Kennzeichnung | **Das Sollmaß muss neu bestimmt werden** (siehe 8, Korrektur 5). Der Befund bleibt richtig, das Abnahmekriterium ebenfalls | Backlog-Runde |
| **172** | Erwartung der Wartungsprobe flackert | Mediane vergleichen, **oder** die Erwartung am Code stellen — der Satz ist strukturell wahr, nicht zeitlich. Bestätigt: `probe.php:452` vergleicht zwei Einzelmessungen ohne Spielraum | Backlog-Runde |

---

## 6. Umzusetzen — beschrieben, keine offene Frage

23 Punkte, unverändert gegenüber der ersten Durchsicht.

| Nr. | Kurz | Gegenprüfung | Zuordnung |
|---|---|---|---|
| **8** | Content-Security-Policy | Keine CSP, kein HSTS, kein `frame-ancestors`. `nosniff` nur an **drei Einzelstellen**, nicht global. Bauplan SP-5 liegt | P5 |
| **36** | Prüfmittel: Klassennamen, die nur JS sucht | `pruefen.py` kennt Markup und Stylesheet, **keine** JS-Selektoren. Die vier Plakettentöne stimmen | Backlog-Runde |
| **37** | Konto, das über Jahre wächst | Alle sechs Kappungen bestätigt (Zeilen verschoben). **Ein Teil ist überholt** — siehe 8, Korrektur 6. Offen: Zeitraumübersicht und Nachbearbeitung bei 5 000, Zielzahlen aus E-S2-24, `post_max_size` des Produktivservers | P5 |
| **40** | Altklassen austragen | **53** statt 55. Der Weg steht | Backlog-Runde |
| **43** | Ortsdaten verschlüsseln (Weg B) | Terminiert als S11. Umfang steht; Weg C ist als Nr. 138 erledigt | S11 |
| **47** | Natives `confirm()` fernhalten | **Zwei** berechtigte Ausnahmen, nicht eine (siehe 8, Korrektur 1) | Backlog-Runde |
| **48** | Aufbewahrung je Konto | Bestätigt: `edbak_aufbewahrung()` liest eine Marke für die ganze Installation. Der Weg steht | P5 |
| **53** | Konto-Schlüsselpaar | Die offenen Fragen sind mit R78 beantwortet. Kein Schlüsselpaar im Code | S11 |
| **58** | Prüfmittel: Seite ohne Gerüst | `tag_spuren.php` ruft `ui_geruest_start()` inzwischen. **Zusatz:** Eine naive Regel liefert heute **15 Treffer, alle berechtigt** — das Kriterium „gibt eigenes Markup aus" muss operationalisiert werden, sonst ist das Mittel beim ersten Lauf rot | Backlog-Runde |
| **67** | `csrf_check()` ohne API-Zweig | Zahlen neu: **17 / 12 / 5** statt 15 / 11 / 4. Die Invariante hält; `kopplung_stand.php` ist GET-only mit Begründung. **Der Unterpunkt steht unverändert:** `kdf_upgrade.php` steigt in Zeile 70 für das Demo-Konto aus, die CSRF-Prüfung folgt in Zeile 71 — die beiden gehören getauscht | P5 |
| **77** | `update.php` auflösen | Web-Teil ist 302, Notausgang bleibt. Offen ist allein, dass die Adresse noch existiert | P6 |
| **91** | Auswahl in `WatchUi.Confirmation` nicht sichtbar | **Die Lehre ist nirgends aufgeschrieben** — `tools/uhr-pruefstand/LIESMICH.md` erwähnt `Confirmation` nicht. **Der billigste offene Punkt im ganzen Backlog** | Backlog-Runde |
| **92** | `bildreihe` fotografiert nur den Start | `bildreihe()` nimmt `liste` und `ziel`. `taste` existiert, lässt sich aber nicht einspeisen | Backlog-Runde |
| **94** | „bitgleich" gegen „pixelgleich" | Beide Wörter stehen weiterhin (`erzeugen.sh` Kopf, `LIESMICH.md` Zeilen 27 und 35). **Zuspitzung:** Gemessen wurde beides mit `compare -metric AE` — das belegt Pixel-, nicht Bitgleichheit, und `LIESMICH.md` widerlegt „bitgleich" acht Zeilen später selbst. Es ist **ein Wort** | Backlog-Runde |
| **95** | Rundlauffälle lassen Daten zurück | Der Vorschlag trägt: Das `@After` trennt das Gerät, leert den Ratenschutz und löscht die lokale Datenbank — den **hochgeladenen** Bestand räumt es nicht ab | Backlog-Runde (Android) |
| **139** | Adminpakete unversiegelt, `ftp` | Kein `sk_versiegeln()` in `adminbackup_lib.php`; `ftp` steht weiterhin im ENUM (`schema.sql:536`) | S10 |
| **141** | Zweitfaktor für alle Konten | Kein TOTP, kein `otpauth` im Code. Umfang steht in R78 | P5 |
| **154** | Handy liest `kept_points`/`kept_meta` nicht | `Sendeantwort.kt:66` liest nur `kept_phases` und `kept_resus`. Vertrag und `ingest.php` führen alle vier | nächste Android-Stufe |
| **157** | Sackgasse „Schlüssel abgewiesen" / „trennen" | `Kopplungsdienst.kt:329` bricht bei Rückstand ab, **unabhängig vom Grund** | nächste Android-Stufe |
| **158** | `days` trägt kein `created_at` | Bestätigt; `ingest_tag_offen()` fragt zwei Tabellen über `MAX(created_at)` | Backlog-Runde |
| **168** | Zentrale Stammdaten zurückbauen | Die Tür ist zu. **106 Nennungen** von `user_bases` in `server/` und `docs/`, alle „eigen ODER zentral"-Abfragen unverändert | P5 |
| **173** | Umlaufprüfungen führen tote Regeln | Bestätigt, alle drei: `missions.notes` zweimal und `kopf.version` → `11` | Backlog-Runde |
| **174** | Referenzbestand ohne „Rettungsmittel ohne Standort" | Die **Quelldaten sind repariert** (`stammdaten.json`, beide mit `ohne_standort: true`). Die Referenzdatei ist verschlüsselt und nicht gegenprüfbar | Backlog-Runde |

**Übergeben:** **Nr. 21** (43 Restfunde) geht an den P6-Review. Die Quellliste
existierte laut Rahmenplan Abschnitt 8 nie im Repositorium — das P0-Konzept
steht dort ausdrücklich als „nicht im Repositorium".

**Wartet auf Zuarbeit:** **Nr. 62** (Logofarben). Bis neue Vorlagen vorliegen,
passiert nichts am Code.

---

## 7. Was Fable am Rahmenplan ändern muss

Nummeriert, damit einzeln darauf verwiesen werden kann.

1. **Drei Zeilen aus Abschnitt 5 entfernen:** Nr. 87, Nr. 81, Nr. 88.
2. **Selbstprüfzahl auf `58 = 58`** setzen und nachrechnen. Der Absatz darüber
   macht das Nachrechnen zur Pflicht jeder Zeilenänderung — hier ist es fällig.
3. **Zahl „nach v1.0" von 9 auf 8** (Nr. 45 verlässt die Gruppe). Die acht
   verbleibenden: 50, 51, 52, 55, 90, 96, 99, 100.
4. **Nr. 80:** Verweis „die NutzerInnen-Sicht ist Nr. 88" streichen.
5. **Nr. 21:** Zuordnung Backlog-Runde → **P6** (R69), mit dem Vermerk, dass
   die Quellliste nicht mehr existiert.
6. **Nr. 62:** Zuordnung Backlog-Runde → **Zuarbeit**; Zeile in Abschnitt 6
   anlegen („korrigierte Logovorlagen in den Markenfarben").
7. **Nr. 117:** Zuordnung Backlog-Runde → Handbuchsatz; die Bemerkung „Spalte
   an `users`, Zeile auf der Kontoseite" ist gegenstandslos und muss weg.
8. **Nr. 169:** Zuordnung „Entscheidung zuerst, danach Backlog-Runde oder P5"
   → **P5**, mit dem Vermerk, dass bis dahin (c) gilt.
9. **Nr. 57:** Nebenbedingung `cap_gate` in die Bemerkung aufnehmen — sonst
   geht sie beim Zusammenführen verloren.
10. **Nr. 150:** Bemerkung anpassen; beide Fragen sind beantwortet, die
    Fundstellen sind neu zu nennen.
11. **Neues Paket „Mockup-Runde"** im Fahrplan verorten (Nr. 41, 42, 45, 124).
12. **Nr. 81 aus Abschnitt 6** (Offene Abnahmen und Zuarbeiten) streichen —
    der Gerätetest am S24 entfällt mit dem Punkt.
13. **Nr. 170:** Bemerkung berichtigen — das Sollmaß `'store' => 'pat'` trägt
    heute **1 von 8** Fällen.
14. **Nr. 37:** Doppelte Wahrheit auflösen. Die Bemerkung in Abschnitt 5 ist
    knapper als der Backlog-Eintrag, der noch drei offene Messungen führt;
    eine der beiden Fassungen ist die führende.

---

## 8. Arbeitsliste Textpflege

Ob das eine eigene Kleinstufe wird oder mitläuft, entscheidet Fable
(Entscheidung 16). Die Liste steht unabhängig davon.

### 8.1 Sechs sachliche Korrekturen

1. **Nr. 47** — „die eine berechtigte Stelle" ist falsch. Es sind **zwei**:
   `assets/confirm.js:94` und `assets/forms.js:149`, beide Rückfälle für den
   Fall, dass `edConfirm` nicht geladen ist. Eine Ausnahmeliste mit einem
   Eintrag wäre beim ersten Lauf rot.
2. **Nr. 46** — falsche Datei. Die Fassungsweiche steht in
   `vergleich/kreislauf.py` (Zeilen 174, 184, 248), nicht in `vergleich/lesen.py`;
   dort kommt `version` kein einziges Mal vor.
3. **Nr. 62** — der Punkt ist größer, als er dasteht: Auch die beigelegten
   PNG-Vorlagen tragen die alten Werte (`#E32F2E`, `#5879BC`, `#F7941D`,
   `#1B0B0B`). **Zusätzlich und unabhängig von der Zulieferung:** `Design.md`
   2.5 behauptet „B1 erledigt, nachgemessen" — das stimmt nicht mehr und ist
   sofort zu berichtigen, sonst steht dort bis zur Lieferung eine falsche
   Zusage.
4. **Nr. 81** — der Text führt „Themed Icons noch offen"; das Manifest sagt
   seit dem 04.09.2026 das Gegenteil. Geht mit der Austragung auf.
5. **Nr. 170** — der Feldkatalog trägt **genau ein** Feld mit
   `'store' => 'pat'` (`notes`, aus S9/AP7) und drei mit `'hinweis'`. Die acht
   Schlösser der Karte „PatientIn" stehen als handgeschriebene
   `dtGeschuetzt()`-Aufrufe in `einsatz.php` und sind im Katalog **überhaupt
   nicht vorhanden**. Das vorgeschlagene Sollmaß deckte 1 von 8 ab.
6. **Nr. 37** — die Aussage, `admin_sicherungen.php` lese „weiterhin je Konto
   ein Verzeichnis **und** eine Begleitdatei" (F-P3-F), stimmt nicht mehr:
   `edbak_staende()` macht **einen** `scandir` der Wurzel und liest je Konto
   nur die kleine `konto.json` — genau die Bauform, die derselbe Eintrag ein
   paar Zeilen höher als gelöst beschreibt. Ein `scandir` je Konto bleibt nur
   für Ordner ohne lesbare Begleitdatei, und der Code sagt das dazu.

### 8.2 Gealterte Zahlen

| Nr. | Eintrag sagt | gemessen 12.09.2026 |
|---|---|---|
| 40 | 55 Altklassen | **53** |
| 41 | sechs Klassen `[offen]` | **fünf** |
| 42 | 195 Unicode-Treffer | **255** (drei echte unverändert) |
| 67 | 15 / 11 / 4 in `api/` | **17 / 12 / 5** |
| 124 | zehn `ui_aktionen()` | **neun** |
| 17 | „die drei übrigen" (vier genannt) | **neun Töpfe** in `RATE_GRENZEN` |
| 65 | 14 Fassungshinweise | vier Android-Stufen alt, nicht nachzählbar |

### 8.3 Verschobene Fundstellen

| Nr. | Eintrag nennt | steht heute in |
|---|---|---|
| 23 | `ingest.php:299`, `einsatz_form.php:317` | `:623`, `:328` |
| 37 | `ui.php:498`, `api/day.php:130`, `api/import_commit.php:93`, `api/export_data.php:182`, `demo_lib.php:52` | `:578`, `:227`, `:97`, `:197`, `:225` |
| 42 | `einsatz_form.php:1416`, `ortsfeld.js:197` | `:1617`, `:244` (`patient.js:133` stimmt) |
| 139 | `schema.sql:512` | `:536` |
| 150 | `Technik.md` 2424 / 5220, `CHANGELOG.md` 6210, `betrieb_jobs.php:186` | `:2674`, `:6042`, `:8722`, `:293` |
| 114 | `puffer/Puffer.kt:449-514` | Räumteil ab `:566` |

> **Vorschlag zur Vermeidung.** In Backlog-Einträgen **Funktionsnamen statt
> Zeilennummern** nennen (`ingest_tag_offen()` statt `ingest.php:38`).
> Funktionsnamen überleben Auslieferungen; Zeilennummern nicht — besonders
> nicht in `docs/CHANGELOG.md`, wo Einträge oben dazukommen und alles nach
> unten wandert. Wo die Zeile wirklich gebraucht wird, gehört das Messdatum
> daneben. Dasselbe gilt für Zahlen: „53, gemessen 12.09.2026" kostet eine
> Klammer und sagt der nächsten Instanz sofort, wie alt der Wert ist.

---

## 9. Vorgeschlagene Reihenfolge

Keine Entscheidung, ein Vorschlag.

1. **Textpflege und Rahmenplan-Anpassung** (Abschnitte 7 und 8). Ohne
   Codeänderung, eine Sitzung, und danach ist der Backlog wieder eine Quelle,
   der man glauben kann.
2. **Die billigen Umsetzungen** in dieser Reihenfolge: **Nr. 91** (eine Notiz
   in eine LIESMICH), **Nr. 94** (ein Wort), **Nr. 117** (ein Absatz im
   Handbuch), **Nr. 173** und **Nr. 174** (dieselben Dateien), **Nr. 67**
   Unterpunkt (zwei Zeilen tauschen).
3. **Die zwei kleinen Prüfmittel:** Nr. 47 und Nr. 58.
4. **Die Mockup-Runde** (Nr. 41, 42, 45, 124) — sie braucht eine Freigabe und
   sollte deshalb früh angestoßen werden, damit sie nicht am Ende wartet.
5. **Nr. 57** als eigenes Paket, zweieinhalb bis drei Tage, mit der
   `cap_gate`-Nebenbedingung als erster Prüffall.

---

## 10. Anhang — Prüfumgebung und Grenzen

**Geprüft gegen:** `gen-em/einsatzdoku-luftrettung`, `main`, als ZIP über
`codeload.github.com` gezogen am 12.09.2026.

**Womit:** Lesen der betroffenen Dateien, `grep`, und ein Lauf von
`tools/vollstaendigkeit/pruefen.py` für die Zahlen zu Nr. 40, 41 und 42.

**Was nicht lief:** kein PHP-Server, keine Datenbank, kein Browser, kein
Android-SDK, kein Connect-IQ-Simulator, kein Gerät. Alle Aussagen sind **am
Quelltext** getroffen, nicht am laufenden System.

**Sechs Punkte waren hier nicht nachprüfbar:** Nr. 65 (kein Android-SDK, kein
Netzzugang für Gradle), Nr. 90, 91 und 92 (Connect-IQ-Simulator), Nr. 140
(Branch-Schutz ist eine GitHub-Einstellung, kein Repositoriumsinhalt) und
Nr. 174 (die Referenzdatei ist verschlüsselt; die Quelldaten sind
gegengeprüft). Nr. 81 wäre ebenfalls nur am Gerät zu prüfen gewesen — der
Punkt ist mit Entscheidung 13 geschlossen.
