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
