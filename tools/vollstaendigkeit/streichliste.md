# Streichliste — Klassen des alten Stylesheets, die es nicht mehr gibt

Die Vollständigkeitsprüfung verlangt für **jede** Klasse des alten
Stylesheets (`vorher-klassen.txt`, 220 Stück) entweder eine Regel im neuen
Stylesheet oder einen Eintrag hier. Ein Eintrag ohne Grund ist keiner: Die
Spalte „Grund" sagt, **warum** die Klasse verschwindet — ersetzt durch
welchen Baustein, oder ersatzlos, weil die Sache selbst entfällt.

Die Liste wächst mit den Arbeitspaketen. Wer eine Klasse streicht, trägt sie
im selben Paket hier ein, nicht später.

| Klasse | Grund | Paket |
|---|---|---|
| `topbar` | Kopfleiste, ersetzt durch `.kopf` mit `.kopf-innen`, `.kopf-marke`, `.kopf-punkt`, `.kopf-zahnrad`. Die alte Kopfleiste hatte keine mobile Navigation; bei 360 px stand der Markentext als „Einsatz…" neben drei Punkten nebeneinander. | O2 |
| `brand` | Marke in der Kopfleiste → `.kopf-marke`. | O2 |
| `mainnav` | Hauptpunkte der Kopfleiste → `.kopf-punkte`. Die Klasse hatte im alten Stylesheet ohnehin keine Regel (Bestandsfund F-P3-N). | O2 |
| `gearlink` | Zahnrad → `.kopf-zahnrad`, gebaut als Symbolknopf zu 44 px. Vorher 21 px. | O2 |
| `daylist` | Seitenleiste. Ersatzlos, und das ist der Kern des Umbaus: Sie trug `position:sticky; height:calc(100vh - 50px); overflow:hidden` — auf dem Handy anderthalb Bildschirme vor dem Inhalt, und die Tagesliste lief rechts aus dem Bild. Das **Einstellungsmenü trug dieselbe Klasse** und erbte jede dieser Regeln. Ersatz ist `.leiste`, die unter 1024 px als Schublade liegt. | O2 |
| `daylist-empty` | „noch keine" in der leeren Leiste → `.leiste-leer`. | O2 |
| `dayyears` | Behälter des Akkordeons → `.leiste-liste`. | O2 |
| `yearblock` | Jahr im Akkordeon → `.akkordeon`. | O2 |
| `monthblock` | Monat im Akkordeon → `.akkordeon.akkordeon-monat`. | O2 |
| `zeitlink` | Der Text der Jahres-/Monatszeile war der Link in die Zeitraumübersicht, das Dreieck daneben der Schalter — auf einem Touchgerät nicht zu unterscheiden. Jetzt klappt die **ganze Zeile**, und der Weg in die Übersicht ist ein eigenes Symbol rechts (`.akkordeon-uebersicht`, 44 px). | O2 |
| `dayadd` | „+ Diensttag anlegen" und „Zuordnung offen" im Fuß der Leiste → `.eintrag-anlegen` bzw. `.eintrag-offen`. | O2 |
| `trashlink` | Papierkorb im Fuß der Leiste → `.eintrag-leise`. | O2 |
| `nachbearbeitung` | Zusatzklasse an „Zuordnung offen" → `.eintrag-offen`. Die Bedingung bleibt: Der Eintrag erscheint nur, solange etwas offen ist (E24/A12). | O2 |
| `sidebar-subhead` | Zwischenüberschrift „Administration" in der Leiste → `.leiste-kopfzeile`, dieselbe Klasse wie die Hauptüberschrift. | O2 |
| `layout` | Raster aus Leiste und Inhalt → `.rahmen`. Neu daran: Leiste und Inhalt sind als **Einheit** zentriert und auf 1680 px begrenzt (E-P3-12). | O2 |
| `layout-suche` | Eigenes Raster der Suchseite. Ersatzlos — die Suche benutzt dieselbe `.leiste` wie alle anderen Seiten. | O2 |
| `filterspalte` | Eigene Leiste der Suchseite mit eigenen Regeln. Ersatzlos aus demselben Grund: Hing der Schubladenmechanismus an der Diensttagsfunktion statt an der Klasse, bliebe die Suche als einzige Seite ohne mobiles Menü (Konzept P0, 10.5). | O2 |
| `page` | Inhaltsbereich → `.inhalt`. | O2 |
| `sitefooter` | Fußzeile → `.fuss-seite`. Sie stand im `<main>` und fehlte auf jeder Seite ohne Inhalt; jetzt steht sie außerhalb und auf **jeder** Seite, auch vor der Anmeldung (R32, E-P3-14). | O2 |
| `ver` | Versionsnummer in der Fußzeile → `.fuss-version`. | O2 |
| `demobanner` | Demo-Banner → `.demo-hinweis`. Es stand zwischen Kopfleiste und Gerüst und verschob die klebende Leiste um seine eigene Höhe; im Demo-Konto rutschte sie unter der Kopfleiste hervor (F-P3-G). Jetzt steht der Hinweis innerhalb des Inhalts. | O2 |
| `login-body` | Körperklasse der Anmeldung → `.anmeldung-body`. | O2 |
| `login-card` | Karte der Anmeldung → `.anmeldung` mit `.anmeldung-karte`. | O2 |
| `login-logo` | Logo auf der Anmeldung → `.anmeldung-logo`. | O2 |
| `setup-card` | Breitere Karte der Passwortvergabe → `.anmeldung-breit`. Die Klasse hatte im alten Stylesheet keine Regel (Bestandsfund F-P3-N). | O2 |
| `btn-link` | Knopf des Einrichters → `.knopf`. Backlog Nr. 18: `.btn-link.danger` konnte nie greifen, weil `btn-link` nur in `install.php` vorkam und diese Seite `style.css` gar nicht lud. Seit O2 lädt sie es. | O2 |
| `danger` | Zusatzklasse zu `btn-link`, siehe dort → `.knopf-gefahr`. | O2 |
| `confirmbox` | Bestätigungsdialog aus `confirm.js` → Dialog-Baustein `.dialog`. O2-Umfang laut Konzept; in der ersten O2-Fassung übersehen, gefunden bei der Fable-Kontrolle (F-P3-S). | O2 |
| `confirmtext` | Textzeile des Bestätigungsdialogs → `.dialog-inhalt`. | O2 |
| `confirmbtns` | Knopfzeile des Bestätigungsdialogs → `.dialog-fuss`. | O2 |
| `unlockbox` | Entsperrdialog aus `unlock.js` → `.dialog`. | O2 |
| `unlocktitle` | Titel des Entsperrdialogs → `.dialog-kopf`. | O2 |
| `unlocktext` | Erklärtext → `.dialog-inhalt`. | O2 |
| `unlocklabel` | Passwortfeld → `.feld` mit `.feld-label` und `.feld-eingabe`. | O2 |
| `unlockmsg` | Meldungszeile → Meldungs-Baustein (`.meldung meldung-info/-fehler`), ohne Symbol: Sie wechselt zwischen „Schlüssel wird abgeleitet …" und dem Fehler, und das sagt hier Farbe samt Text. | O2 |
| `unlockbtns` | Knopfzeile → `.dialog-fuss`. | O2 |
| `err` | Zusatzklasse der alten `unlockmsg` für den Fehlerfall → `meldung-fehler`. | O2 |
| `imp-pw` | Passwortfeld der Archivabfrage im Import → `.feld-eingabe`; das `style="width:100%"` daneben ist mit dem Baustein entfallen. | O2 |
| `daymeta` | Diensttag-Daten unter dem Seitentitel → Karte `Diensttag-Daten` mit Leseansicht `.tag-lese` (E-P3-31). | O3 |
| `meta-form` | Formular der Diensttag-Daten, dauerhaft sichtbar → dasselbe Formular, aber zugeklappt hinter „Bearbeiten" in derselben Karte (`.tag-form`). | O3 |
| `metanotes` | Notizfeld der Diensttag-Daten → gewöhnliches `label`+`textarea` im Formular; die Leseansicht zeigt Notizen als `.tagfeld`. | O3 |
| `dayactions` | Knopfreihe (Datum ändern, Zusammenführen, Löschen …) unter den Tagesdaten → Aktionsblatt hinter „···" (`ui_aktionen`, E-P3-25): EINE sichtbare Handlung je Karte, der Rest im Blatt. | O3 |
| `geraetehinweis` | Kasten „neue Geräte verbunden" → Meldungs-Baustein `.meldung meldung-warn` mit Symbol und Quittungsknopf. (`geraetehinweis:` in `db.php` ist ein Speicherschlüssel, keine Klasse.) | O3 |
| `swatch` | Farbchip der Einsatztabelle → `.streifen` (voll hoher Farbstreifen am Zeilen- bzw. Kachelrand, Mockup 02/03). Stand zunächst als Übergangsausnahme im Stylesheet; seit O3 nirgends mehr im Markup. | O3 |
| `c-no` | Breitenklasse der Nr.-Spalte → `.zahl-spalte` (rechtsbündig, Mockup 03); eine eigene Breitenregel braucht die Spalte nicht mehr. | O3 |
| `c-km` | Breitenklasse der km-Spalte → `.zahl-spalte`. | O3 |
| `c-date` | Breitenklasse der Datumsspalte (Suche/Zeitraum) — ersatzlos, die Spalte kommt ohne Breitenregel aus. | O3 |
| `c-art` | Breitenklasse der Artspalte (Suche/Zeitraum) — ersatzlos wie `c-date`. | O3 |
| `c-winde` | Breitenklasse der Winde-Spalte → `.haken-spalte` (zentriert, Mockup 03). | O3 |
| `c-bw` | Breitenklasse der Bergwacht-Spalte → `.haken-spalte`. | O3 |
| `c-sek` | Breitenklasse der Sekundär-Spalte → `.haken-spalte`. | O3 |
| `c-fehl` | Breitenklasse der Fehleinsatz-Spalte → `.haken-spalte`. | O3 |
| `checkcol` | Zentrierung der Hakenspalten → `.haken-spalte`; der Haken selbst ist `edSymbol('haken', 'tabelle-haken')` statt Text-✓. | O3 |
| `c-dc` | Sammelklasse „Katalogspalte" — ersatzlos: Die Ausrichtung sagt jetzt `haken-spalte` (bzw. nichts für Text), die feldeigene Klasse `c-dc-<spalte>` aus `mission_fields_lib.php` bleibt als Anker des Feldkatalogs bestehen. | O3 |
| `pagehead` | Titelzeile der Einsatzansicht (und vormals der Startseite) → Baustein `.titelzeile` mit `.rueckweg`, `.titelzeile-haupt/-text/-unter/-aktionen`. | O4 |
| `pagehead-text` | Textteil der alten Titelzeile → `.titelzeile-text`. | O4 |
| `pagehead-actions` | Knopfbereich der alten Titelzeile → `.titelzeile-aktionen` (Primärknopf „Bearbeiten" + Aktionsblatt). | O4 |
| `aktionsmenu` | `<details>`-Aufklappmenü „Aktionen" → `ui_aktionen()` (Blatt mobil, Menü am Desktop; `assets/blatt.js`). Die Datei `assets/aktionsmenu.js` ist mit ihrem letzten Nutzer entfallen. | O4 |
| `aktionsliste` | Eintragsliste des alten Aktionsmenüs → `.blatt-liste` mit `.blatt-zeile`. | O4 |
| `btn-edit` | Knopfklasse des Menükopfs und der Nachtrag-Links → `.knopf knopf-primaer` („Bearbeiten") bzw. `.knopf knopf-neutral`. | O4 |
| `fieldlist` | Die EINE Feldliste der Einsatzansicht → vier Karten (Einsatz, PatientIn, Transport, Reanimation) mit der Leseansicht aus O3 (`.tag-lese`, `.tagfeld`); die Rangfolge (RANG) sortiert jetzt je Karte. | O4 |
| `badge-uhr` | Herkunftskennzeichen „Uhr" → `.plakette plakette-neutral` in der Unterzeile. | O4 |
| `badge-manuell` | Herkunftskennzeichen „manuell" → dieselbe Plakette. | O4 |
| `badge-import` | Herkunftskennzeichen „importiert" → dieselbe Plakette. | O4 |
| `badge-editiert` | Kennzeichen „editiert" → dieselbe Plakette. | O4 |
| `abw` | „(abw.)" an einer überschriebenen Besatzungsrolle → Kleinzeile `.lese-klein` mit ausgeschriebenem „(abweichend vom Diensttag)". | O4 |
| `locpin` | SVG-Pfad des Karten-Pins, wörtlich doppelt in `index.php` und `einsatz.php` → `EdGeo`-Marker-Satz (`assets/geo.js`, Zeichnung im Stylesheet, Abschnitt 21). | O4 |
| `fgruppe` | Rahmengruppe (`<fieldset>`) des Einsatzformulars → Karten (`ui_karte_start`, E-P3-34): PatientIn, Einsatz, Transport, Weitere Rettungsmittel, Abweichende Besatzung (zu), Notizen, Einsatzphasen, Reanimation (zu). | O5 |
| `fgruppe-hinweis` | „Ende-zu-Ende-verschlüsselt" neben der Gruppenüberschrift → `karte-zahl` im Kartenkopf. | O5 |
| `fld-check` | Rahmen einer Checkbox-Zeile → Schalter-Baustein `.schalter` (E-P3-28/34); die Klasse `parentcheck` für das Auf- und Zuklappen der Unterfelder bleibt. | O5 |
| `phase-row` | Phasenzeile des Formulars → `.phasen-eingabe` (Auswahl, 44-px-Zeitfeld zentriert, Entfernen als roter Symbolknopf); dieselbe Zeilenform tragen jetzt auch die Reanimationszeilen. | O5 |
| `rea-row` | Reanimationszeile → `.phasen-eingabe`, siehe `phase-row`. | O5 |
| `suchbox` | Behälter des Freitextfeldes auf der Suchseite → `.suchzeile`: dasselbe Feld, jetzt 48 px hoch mit Lupe links, Löschkreuz rechts und dem Filterknopf daneben (Mockup 26). Ein eigener Abstandsbehälter darum wird nicht mehr gebraucht — den Abstand setzt die Karte. | O6 |
| `suchfreitext` | Beschriftetes `label` um das Freitextfeld → `.suchfeld` mit `aria-label`; die sichtbare Beschriftung „Suchbegriff" ist entfallen, weil Lupe und Platzhalter dasselbe sagen und die Zeile sonst auf 360 px zwei Zeilen hoch wird. | O6 |
| `wtlabel` | Überschrift „Wochentag" über der Wochentagsauswahl → `.feld-label` im `.feldblock`, dieselbe Beschriftungsform wie bei jedem anderen Filterfeld. Die Wochentage selbst sind jetzt ein Mehrfach-Segment (`.segment-mehrfach`) statt sieben lose Kästchen. | O6 |
| `ergebniszeile` | Textzeile „n von m Einsätzen · x km" unter dem Suchfeld → `.karte-zahl` im Kopf der Trefferkarte (`#trefferzahl`), an derselben Stelle wie jede andere Bestandszahl der Anwendung. | O6 |
| `arttabs` | Behälter der Tableiste nach Art (Gemischt / Luftrettung / Bodengebundener Rettungsdienst) → Segmentwahl `.segment.segment-art` in den Aktionen der Titelzeile (E-P3-37). Die Leiste stand als eigenes Element über dem Inhalt und trug bei 360 px drei Beschriftungen nebeneinander, von denen die längste („Bodengebundener Rettungsdienst") allein breiter war als der Bildschirm. | O7 |
| `arttab` | Eine Taste der Tableiste → `.segment-taste`. Aus `<button role="tab">` ist ein Radio geworden: Der Wechsel mit den Pfeiltasten kommt damit vom Browser, und der eigene `keydown`-Handler ist entfallen. Die Beschriftungen sind auf „Gemischt / Luft / Boden" gekürzt (Mockup 29). | O7 |
| `statsgrid` | Kennzahlenraster der Zeitraumübersicht → `.kennzahl-raster` mit `-4` bzw. `-5` je nach Kachelsatz. Die Klasse hatte im alten Stylesheet ohnehin keine Regel (Bestandsfund F-P3-N); das Raster hing an `.stats-grid`. | O7 |
| `stat-tile` | Eine Kennzahlkachel → `.kennzahl` (Baustein aus O1, `ui_kennzahl`). Neu daran: Einheit kleiner gesetzt, Extremwerte mit Punkt oben rechts und dem Tag in der Beschriftung, aktive Kachel hell orange statt rot. | O7 |
| `stat-value` | Wert der Kachel → `.kennzahl-wert`; die Einheit steht seit O7 als eigenes `.kennzahl-einheit` daneben und wird kleiner gesetzt. | O7 |
| `stat-label` | Beschriftung der Kachel → `.kennzahl-label`, bei Extremwerten mit `.kennzahl-tag` in derselben Zeile. | O7 |
| `neutralhinweis` | Eigene Klasse des Hinweises auf neutrale Diensttage → Meldungs-Baustein (`.meldung meldung-warn`) mit Symbol und Knopf „Zuordnung nachtragen". Die Kennung `neutralhinweis` bleibt als `id` — sie ist der Anker des Skripts, nicht eine Gestaltungsangabe. | O7 |
| `badge-central` | Kennzeichen „systemweit" an Stammdatenzeilen → Plaketten-Baustein (`ui_plakette('systemweit')`). Es war ein eigenes Element mit eigener Regel, obwohl die Plakette seit O2 dasselbe kann. | O8b |
| `btn-stern` | „★ Standard" an einer Stammdatenzeile → `.knopf.knopf-leise.knopf-leise-orange` mit Sternsymbol, Text „Als Vorbelegung". Der Stern stand als Schriftzeichen im Knopf (E-P3-18: keine Unicode-Zeichen als Symbol). | O8b |
| `sternmarke` | Der Stern in der Zeile, der die Vorbelegung anzeigt → `ui_symbol('stern', 'zeile-stern')` in den Plaketten der Zeile. Ebenfalls vorher ein Schriftzeichen. | O8b |
| `c-stern` | Breitenklasse der Sternspalte in der Stammdatentabelle — ersatzlos: Es gibt keine Tabelle mehr, der Stern steht in der Zeile. | O8b |
| `paircode` | Zusatzklasse am Kasten des Kopplungscodes → `.codeblock`, der Baustein für Werte, die von einem Bildschirm auf eine Uhr abgetippt werden (Kopplungscode, Geräte-ID, API-Schlüssel). | O8b |
| `rolechecks` | Zeile mit Haken im Export (GPX, personenbezogene Angaben, Passwortschutz) → Schalter-Baustein (`ui_schalter`). Es waren `<label><input type="checkbox">`-Gruppen mit eigener Regel; der Schalter aus E-P3-28 sagt dasselbe in einer 44-px-Zeile. | O8c |
| `rolechecks-hint` | Vorspann einer solchen Zeile („Zeitraum:") → `.feld-label` über der Segmentwahl. | O8c |
| `imp-wrap` | Scrollbehälter um die Importtabelle → `.tabelle-scroll`, derselbe Behälter wie bei jeder anderen breiten Tabelle. Zwei Klassen für dieselbe Sache waren zwei Stellen, an denen die nächste Änderung ankommen musste. | O8c |
| `check` | Haken im Bestätigungsdialog („Ich entferne eine Sicherung ohne Konto") — ersatzlos. Die Klasse hatte im neuen Stylesheet keine Regel; die Gestaltung kommt aus `label:has(> input[type=checkbox])` (Abschnitt 15), die dem Muster `<label>Haken + Text</label>` eine 44-px-Zeile gibt. O9c hat die Klasse aus `admin_sicherungen.php` entfernt, statt eine Regel für sie zu erfinden. | O9c |
| `data` | Auskunftstabelle Schlüssel/Wert im Adminbereich → `ui_zeile()` in einer Karte, Zahlen als `ui_kennzahl()`. Auf der Demoseite abgelöst; die drei übrigen Vorkommen (`nachbearbeitung.php`, `update.php`, `diensttag_zusammenfuehren.php`) ziehen in O11 nach. | O9c |
| `mono` | Festbreitenschrift für den JSON-Bericht → keine Klasse nötig: `pre` trägt die Schrift und den Rahmen seit O2 selbst (Abschnitt 15). Auf der Demoseite entfernt; `nachbearbeitung.php` und `update.php` folgen in O11. | O9c |
| `rowactions` | Knopfzeile unter einer Auskunft → die Handlungen stehen in der Titelzeile (`ui_titelzeile` mit `ui_knopf` und `ui_aktionen`), wie auf jeder anderen umgebauten Seite. | O9c |
| `inline-form` | Formular um einen einzelnen Knopf, damit er nicht umbricht — ersatzlos. Die Formulare liegen jetzt versteckt im Seitenkopf, und der Knopf verweist über `form="…"` auf sie; damit steht kein Formular mehr im Weg des Layouts. | O9c |
| `ac-form` | Formular des Rettungsmittels — reiner **Skriptanker**. Das Skript sucht `form.ac-form`, um Rollen- und Fähigkeitshaken zur gewählten Art passend ein- und auszublenden; gestaltet wird nichts. Steht seit O8a in `einstellungen.php` und seit O9c auch in `admin_stammdaten.php`, beide aus derselben Vorlage. | O9c |
| `vehkind-radio` | Die beiden Radios „luftgebunden / bodengebunden" — Skriptanker desselben Skripts. | O9c |
| `rollehaken` | Ein einzelner Rollenhaken — Skriptanker (`lab.dataset.kind` entscheidet, ob er zur Art passt). Die 44-px-Zeile kommt aus `label:has(> input[type=checkbox])`. | O9c |
| `rollen-zeile` | Der `.feld`-Block um die Rollenhaken — Skriptanker, damit die ganze Zeile verschwindet, solange keine Art gewählt ist. Gestaltet wird er als `.feld`. | O9c |
| `vehcaps-zeile` | Der `.feld`-Block um die Fähigkeitshaken — Skriptanker aus demselben Grund (Fähigkeiten gibt es nur luftgebunden). | O9c |
| `acroles` | Behälter um eine Gruppe solcher Haken — ohne eigene Regel. Die Haken sind `<label>` mit Kästchen und tragen ihre Gestaltung selbst; ein Behälter, der nichts tut, braucht keine. | O9c |
| `vehkind` | Behälter um die beiden Art-Radios — aus demselben Grund ohne Regel. | O9c |
| `vehcaps` | Zusatzklasse an `.acroles` für die Fähigkeitsgruppe — ohne Regel, siehe `acroles`. | O9c |
| `form-spalte` | **Keine Altklasse, sondern neu aus P3.** Sie ist eine der beiden Spalten des `.form-raster`-Zweispalters ab 1200 px und trägt bewusst keine eigene Regel: Das Raster gestaltet über `grid-template-columns`, die Spalte ist nur ein Behälter, damit das Raster zwei Kinder hat. Steht hier, weil die Prüfung sonst jede Seite meldet, die den Zweispalter benutzt. | O9c |
| `login-wrap` | Hülle um die Anmeldekarte in `pw_handling.php` — ersatzlos, und ihr Verschwinden ist eine **sichtbare** Verbesserung: Das Element hatte im neuen Stylesheet keine Regel, war seit jeher **nicht geschlossen** (drei `<div>` gegen zwei `</div>` in derselben Datei) und stand zwischen `.anmeldung-body` und `<main class="anmeldung">`. Damit war `main` kein direktes Flex-Kind mehr, `flex:1 1 auto` griff nicht, und die Fußzeile klebte dicht unter der Karte statt am unteren Rand. | O10 |
| `keybox` | Kasten um den Wiederherstellungsschlüssel → `.codeblock`, der Baustein für Werte, die von einem Bildschirm abgeschrieben werden (Kopplungscode, Geräte-ID, jetzt auch dieser Schlüssel). Er bringt Festbreitenschrift, Größe und Sperrung mit; das Inline-`style` für die Schriftgröße entfällt damit ebenfalls. | O10 |
| `codebig` | Der Schlüssel selbst darin → `.codeblock-wert`. | O10 |
| `checklabel` | Das Kästchen „Ich habe den Schlüssel sicher notiert" → blankes `<label>`; die 44-px-Zeile kommt aus `label:has(> input[type=checkbox])`. | O10 |
