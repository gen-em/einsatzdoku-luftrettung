# Arbeitsanweisung für Claude Code

Dieses Repositorium ist die **Einsatzdokumentation Notarzt**: eine
Garmin-Uhr-App (Monkey C) und seit S4 eine Android-App mit Wear-OS-Gegenstück
(Kotlin/Compose, `android/`) erfassen Dienste, GPS und Reanimations-Ereignisse,
eine PHP/MySQL-Weboberfläche zeigt und bearbeitet sie. Patientendaten sind
Ende-zu-Ende-verschlüsselt.

Einstieg in die Sache selbst: `README.md`, dann `docs/Technik.md` (Architektur,
Verzeichnisstruktur, Abläufe, Betrieb). Reihenfolge, Status und
Programmentscheidungen: `docs/Rahmenplan.md` (Steuerung) und
`docs/Rahmenplan-Archiv.md` (Werdegang bis Fassung 15).

---

## 1. Zusammenarbeit

- **Deutsch.** Antworten, Commit-Nachrichten, Code-Kommentare und Dokumentation
  sind deutsch. Bezeichner im Code sind es überwiegend auch
  (`ist_admin()`, `mf_tagesspalten()`) — dieser Linie folgen.
- **Klar und direkt.** Probleme, Widersprüche und Missverständnisse ansprechen,
  auch unaufgefordert. Kein Beschönigen.
- **Bei Unklarheit nachfragen statt annehmen.** Lieber eine Rückfrage zu viel.
- **Vor größeren Aufgaben** die Aufgabe zusammenfassen und bestätigen lassen,
  bevor Dateien geändert werden. Dafür den Plan-Modus nutzen.
- **Mehrschrittiges nummerieren.** Nach einem Schritt, dessen Ergebnis die
  weiteren Schritte bestimmt, anhalten und auf Rückmeldung warten. Nur
  tatsächlich unabhängige Schritte auf einmal ausgeben.

## 2. Was bei jeder Codeänderung mitläuft

Diese vier Punkte sind kein Nachklapp, sondern Teil der Änderung:

1. **`server/version.php` hochstufen.** Zählweise `Haupt.Neben.Korrektur`:
   Haupt = grundlegender Umbau (Datenmodell, Verschlüsselung, Migration — oder
   spürbar veränderte Wege durch die Anwendung), Neben = neue Funktionen und
   Felder, Korrektur = Fehlerbehebung und Feinschliff. Der Kopfkommentar der
   Datei erklärt zu jeder Hauptnummer, wofür sie steht — diese Erzählung
   fortschreiben. Die Garmin-Uhr zählt getrennt in `watch/source/Const.mc`,
   die Android-Apps in `android/version.properties`. **Drei Zählungen, drei
   Auslieferungen** — eine Änderung, die nur `tools/` oder `docs/` anfasst,
   stuft keine davon hoch.
2. **`docs/CHANGELOG.md` ergänzen.** Format nach *Keep a Changelog*, Präfix
   `Web` oder `Uhr`. Der bestehende Ton ist erklärende Prosa mit **Begründung**,
   nicht eine Liste von Stichpunkten: Was war das Problem, warum diese Lösung,
   was bleibt bewusst stehen. Diesen Ton halten.
3. **Dokumentation nachziehen** — `docs/Handbuch.md` (Bedienung),
   `docs/Technik.md` (Architektur, Verzeichnisstruktur, Runbook), bei
   Schnittstellenänderungen `docs/JSON-Vertrag.md`, `docs/Export-Format.md`,
   `docs/Backup-Format.md`. **Entfernte Funktionen werden ausgetragen**, nicht
   nur neue eingetragen. Danach die betroffenen Dokumente auf Konsistenz
   gegenlesen; sie verweisen aufeinander.
4. **Backlog pflegen** (`docs/Backlog.md`). Nummern sind dauerhaft. Erledigte
   Punkte werden nicht gelöscht, sondern nach *Erledigt* verschoben und behalten
   ihre Nummer. Neue Punkte hängen hinten an. Die Nummern 4, 6 und 7 bleiben
   dauerhaft frei.

Welches Dokument zu welcher Änderung gehört, steht in Abschnitt 9
(Pflegepflichten).

## 3. Deployment — Vorsicht

**Ein Push auf `main` mit Änderungen unter `server/` lädt sofort auf den
Produktivserver hoch** (GitHub Action, FTPS). Es gibt keine Zwischenstufe und
keine Testumgebung.

- **Niemals ungefragt pushen.** Committen ja, wenn beauftragt; pushen nur auf
  ausdrückliche Anweisung.
- Nach einem Deploy mit Schemaänderung muss eine Administratorin `update.php`
  aufrufen. Das steht sonst still und die Anwendung läuft ins Leere — beim
  Vorschlagen einer Migration ausdrücklich mit ansagen.
- Ohne erhöhte `WEB_VERSION` sieht der Browser alte Dateien.
- `server/config.php`, `install.lock` und `server/sicherungen/` liegen nur auf
  dem Server. Sie stehen in `.gitignore` **und** in der Ausnahmeliste des
  Deploys — beides muss so bleiben.

## 4. Feste Zusagen der Anwendung

Diese Eigenschaften sind das Versprechen des Projekts. Eine Änderung, die eine
davon aufweicht, wird nicht nebenbei gemacht, sondern angesprochen:

- **Ende-zu-Ende-Verschlüsselung.** Diagnose, Alter und Einsatzort werden im
  Browser ver- und entschlüsselt. Klartext dieser Felder geht nie an den Server,
  in ein Log oder in eine API-Antwort.
- **Keine fremde Quelle zur Laufzeit.** Kein CDN, keine Google Fonts, kein
  externes Skript. Schriften und Bibliotheken liegen unter
  `server/assets/fonts/` bzw. `server/assets/vendor/`, mit Herkunft und
  SHA-256 im Dateikopf. Eine neue Abhängigkeit wird lokal vendoriert und
  ebenso dokumentiert.
- **Gemeinsame Prüfschicht.** Einsatzdaten laufen über `validate_lib.php` —
  alle Schreibwege, ohne Ausnahme.
- **Feldkatalog statt Sonderfall.** Zusatzfelder werden in
  `mission_fields.php` beschrieben; Formular, Speichern, API und Anzeige ziehen
  von selbst nach. Ein neues Feld, das an fünf Stellen von Hand eingebaut wird,
  ist ein Fehler. Vorgehen: `docs/Technik.md`, Abschnitt 7 (Runbook).
- **Die Uhr kennt keine Zugangsdaten.** Die Wear-OS-App (`android/uhr/`) hat
  weder Serveradresse noch API-Schlüssel; sie schickt ihre Ereignisse an das
  Handy, und das Handy sendet (E-S4-11). Eine verlorene Uhr gibt keinen Zugang
  preis. Wer das aufweicht — und sei es „nur zum Prüfen" —, nimmt der Bauform
  ihre Sicherheitsaussage. Der Prüfstand zählt die Schlüssel des
  Nachrichtenformats nach.
- **Der Data Layer steckt hinter einer Schnittstelle.** `Nachrichtenweg`, eine
  Umsetzung (`WearNachrichtenweg.kt`). Alles darüber kennt nur die
  Schnittstelle — deshalb laufen die Prüffälle ohne Play-Dienste, und deshalb
  ist die eine proprietäre Bibliothek des Projekts auf eine Datei begrenzt
  (`docs/Lizenzen.md` 6a). Ein Aufruf am Weg vorbei ist ein Fehler.
- **Spuren nur über `spur_lib.php`.** Seit Web 10.0.0 liegen GPS-Punkte je
  nach Alter als Zeilen in `track_points` **oder** als Blob in `track_blobs`
  (Format SPUR1) — und während einer Nachlieferung als beides. Wer eine der
  beiden Tabellen unmittelbar per SQL liest, schreibt oder löscht, zeigt oder
  hinterlässt früher oder später eine halbe Spur, und zwar ohne
  Fehlermeldung. Es gibt genau einen Weg: `spur_lesen()`,
  `spur_lesen_viele()`, `spur_zahlen()`, `spur_naechste_seq()`,
  `spur_loeschen()`, `spur_zeit_verschieben()`. Ein neuer Verbraucher, der
  daran vorbeigeht, ist ein Fehler. Format und Begründung:
  `docs/Technik.md`, Abschnitt 4.97; Nachweis: `php tools/spurprobe/probe.php`.

## 5. Oberfläche

Die Gestaltungsrichtlinie ist **`docs/Design.md`** — Farben, Token, Schriften,
Schwellen, Symbole, Bausteine, Seitentypen. Sie ist verbindlich; wer eine
Oberflächenänderung anfängt, liest zuerst dort. Kurz:

- **Ein neuer Baustein oder eine neue Darstellung entsteht nur nach
  ausdrücklicher Freigabe mit Mockup.** Bis dahin werden vorhandene Bausteine
  verwendet — der Vorrat steht in `Design.md`, Kapitel 9.
- Farben ausschließlich über die Token aus `:root` in
  `server/assets/style.css`. Kein Hexwert direkt in einer Regel.
- Ein neuer Farbwert oder eine neue Schriftgröße braucht eine Herkunft
  (Markenwert oder begründete Ableitung) und wird in `docs/Design.md`
  nachgetragen. Die Skala ist geschlossen.
- Kontrast gegen die tatsächliche Fläche prüfen (Schnee/Rauch, nicht Weiß),
  Zielwert AA. `python3 tools/screenshots/kontrast.py` rechnet ihn nach.
- Eine Höhe für Bedienelemente: **44 px**, mobil wie am Schreibtisch —
  das gilt für die **Weboberfläche**. Die Android-Apps folgen der
  Plattformvorgabe von **48 dp** (R58, 02.09.2026): Sie werden mit
  Handschuhen bedient, und dafür ist die Android-Zahl gemacht.
- Spaltenbreiten in Tabellen nie über `:nth-child` — sie zählen Spalten ab und
  rutschen beim Streichen einer Spalte still auf die falsche. Klassen benutzen.
- Die Tabellen in `Design.md` (Token, Schwellen, Symbole, Bausteine) sind
  **erzeugt**: `python3 tools/design/tabellen.py alle`. Wer eine davon von Hand
  ändert, ändert sie an der falschen Stelle.

## 6. Prüfen

Es gibt **keine automatisierten Tests**. Geprüft wird durch Lesen und im
Browser. Für Änderungen an der Oberfläche `/chrome` nutzen: Seite öffnen,
Konsole lesen, den Weg durchklicken, den die Änderung betrifft — und die
Fassungen mitprüfen, die dieselbe Regel benutzen (die Anwendung teilt sich
Bausteine; eine Änderung an `.btn-plain` trifft ein Dutzend Stellen).

Wenn eine Änderung nicht im Browser überprüft werden konnte, das **sagen**,
statt sie als erledigt zu melden.

**Uhr-Code** wird mit `tools/uhr-pruefstand/` geprüft: übersetzen für alle
Zielgeräte (Stufe I) und im Simulator starten (Stufe II). Der Aufbau braucht
eine Adresse in `CIQ_GERAETE_URL`, die nicht im Repositorium steht. Sie liegt
seit dem 03.09.2026 in den **Umgebungsvariablen der Arbeitsumgebung** und ist
in einer eingerichteten Umgebung bereits gesetzt — prüfen mit
`[ -n "$CIQ_GERAETE_URL" ]`, und nur erfragen, wenn sie fehlt. Anleitung in
der dortigen `LIESMICH.md`.

**Während P3 (Oberflächen-Redesign) treten zwei Werkzeuge an die Stelle des
Stilvergleichs**, weil er dort die falsche Frage stellt (Begründung in
`tools/stilvergleich/LIESMICH.md`):

- `tools/vollstaendigkeit/` — Ist etwas verlorengegangen (jede Klasse des
  alten Stylesheets hat eine Regel oder steht mit Begründung auf der
  Streichliste), und steht jeder Wert an der einen Stelle (`:root`)?
- `tools/screenshots/` — 30 Seiten in acht Breiten von 360 bis 1920 px, mit
  gemessenem waagerechtem Überlauf, Konsolenfehlern und Knopfhöhen; dazu
  `kontrast.py` für die Kontraste der Token.

Beide nach **jedem** Arbeitspaket, nicht erst am Ende. Ab P4 wacht der
Stilvergleich wieder.

**Stilvergleich bei CSS-Umbauten.** Für jede Änderung an
`server/assets/style.css`, die Regeln verschiebt, zusammenführt, entfernt
oder deren Reihenfolge berührt, wird der Stilvergleich angewendet
(`tools/stilvergleich/`, Anleitung in der dortigen `LIESMICH.md`):
Kaskadenvergleich plus berechnete Stile in Chromium über mehrere
Fensterbreiten, Vergleichsstand aus Git. Bei einer **beabsichtigten**
Gestaltungsänderung ist das Ergebnis keine Null, sondern eine Liste — sie
wird gegen die Liste der geplanten Änderungen gehalten; jede Abweichung
darüber hinaus ist unbeabsichtigt und wird geklärt, bevor committet wird.
Der Stilvergleich ersetzt die Browserprüfung nicht: Er misst statisches
Markup, keine Bedienzustände.

**Wortliste bei jeder Textänderung.** Für jede Änderung an einem sichtbaren
Text — der Weboberfläche, **der Android-Apps** oder der normativen
Dokumentation — wird `tools/wortliste/` gefahren (Anleitung in der dortigen
`LIESMICH.md`): Es zählt nach, ob Land und Luft neutral benannt sind. Erwartet
werden null Treffer außerhalb der Ausnahmeliste und null ungenutzte Ausnahmen;
ein Luftbegriff, der bleiben soll, braucht einen Eintrag mit Begründung — kein
Ausblenden.

> **Jeder sichtbare Text der Anwendung läuft durch die Wortliste — gleich, in
> welchem Client er steht.** Ein Bereich fehlt nicht, weil ein Verzeichnis
> jung ist; er fehlt, weil ihn niemand eingetragen hat. Wer einen Client
> hinzufügt, trägt seine Textdateien im selben Paket ein, in dem der Client
> entsteht. **Ein Lauf, der einen Client übergeht, meldet keine Null — er
> meldet gar nichts.**
>
> Aufgestellt in S4 (B-S4-06), nachdem der Lauf nach dem letzten Android-Paket
> 0 Treffer meldete, ohne eine Zeile der App angesehen zu haben. Bereich `d`
> (Android) steht seither in der Liste, Bereich `e` (`watch/` — Ressourcen
> **und** Monkey C) seit S5/C. **Damit läuft jeder Client durch die Liste**;
> die Regel hat keine offene Stelle mehr, an der sie nur ein Vorsatz wäre.

**Die Android-Apps prüfen sich selbst — `./gradlew build` im Ordner
`android/`** (mit `ANDROID_HOME=/opt/android-sdk`). Anders als Web und
Garmin-Uhr haben sie automatisierte Prüffälle (JUnit/Robolectric), und die
laufen ohne Gerät: gegen ein echtes SQLite, gegen eine Attrappe des Data
Layer und, wo eine lokale Installation steht, gegen `ingest.php` selbst.

**Was nur auf einem Android-System geht, steht in `src/androidTest/`** — der
echte `AndroidKeyStore` und die Erreichbarkeit der Wearable-API. Diese Fälle
gehen **an Gradle vorbei** (`adb shell am instrument`, Befehlsfolge in
`android/LIESMICH.md`): `connectedAndroidTest` scheitert auf einem
softwareemulierten Gerät an einer ddmlib-Zeitgrenze. Und **Backtick-Namen mit
Leerzeichen** sind dort verboten — D8 lehnt sie unterhalb von DEX 040 ab, das
Modul steht auf `minSdk = 26`. Die Bilder des Prüflaufs entstehen über
Robolectric im NATIVE-Modus, nicht über `captureToImage()` (das hängt sich
auf) — sie zeigen das **gerechnete** Bild und sind damit etwas anderes als
die Abzüge vom Emulator (nächster Absatz). Erwartet werden **0 Lint-Fehler**
und **0 Fehlschläge**; Warnungen werden gezählt und nicht stummgeschaltet.
Was das alles NICHT ersetzt, steht in `android/LIESMICH.md` und beginnt mit
dem echten Data Layer.

**Der Emulator läuft mit — wie der Simulator bei der Garmin-Uhr.**
Angewiesen am 03.09.2026. Die Uhr hat seit jeher zwei Stufen: übersetzen
(Stufe I) und im Simulator starten (Stufe II). Für Android galt bislang nur
Stufe I. Das ändert sich: Bei **jeder** Änderung an einem der beiden
Android-Module wird der Emulator gestartet, die Änderung darin **angesehen
und bedient**, und beides mit **Bildern belegt**. Werkzeug:
`android/werkzeuge/emulator.sh` (`aufbauen`, `start`, `legen`, `bild`, `aus`),
Anleitung und Zahlen in `android/LIESMICH.md`.

Drei Sätze, damit die Regel nicht ins Leere greift:

- **Der Bilderlauf ersetzt ihn nicht, und er ersetzt den Bilderlauf nicht.**
  Robolectric zeichnet das gerechnete Bild: deterministisch, Dutzende Abzüge
  in Sekunden, aber ohne laufendes Programm. Der Emulator zeigt das
  gelaufene: Systemleisten, echte Schriftrasterung, rundes Glas,
  Bedienzustände, und was nach einem Druck auf einen Knopf passiert. Was der
  eine sieht, sieht der andere nicht — deshalb laufen beide.
- **Pflicht ist der Versuch, nicht der Erfolg.** Der Emulator ist eine
  Eigenschaft des Wegwerf-Containers. Läuft er nicht, ist das ein **Befund
  mit Zahl** — welches Abbild, welche Fassung, welche Meldung —, kein
  stillschweigend übersprungener Punkt; er gehört dann im Prüfdokument an den
  Anfang, unter „was nicht geprüft werden konnte und warum". Und **bevor**
  man ihn abschreibt, wird `-accel off` versucht: Am 03.09.2026 stand in
  `android/LIESMICH.md`, das x86_64-Abbild brauche KVM — es braucht es nicht,
  ohne KVM übersetzt QEMU selbst. Der Satz verwechselte „startet nicht ohne
  Weiteres" mit „geht nicht" und kostete ein ganzes Paket ohne Stufe II.
- **Er läuft am Ende des Arbeitspakets**, mit den übrigen Prüfmitteln. Ohne
  KVM rechnet **ein** Kern; Boot und Aufspielen liegen in Minuten, nicht
  Sekunden. Wer ihn nach jeder Datei anwirft, verbraucht die Zeit, die die
  Änderung selbst gebraucht hätte.

**Die Prüfmittel laufen zuletzt, nicht zwischendurch.** Erst der Code, dann
die Dokumentation, **dann** Wortliste, Vollständigkeit, Kontraste und
Bilderlauf. Ein Werkzeug, das vor der letzten Änderung lief, misst einen Stand,
den es nicht mehr gibt — in O9c stand die Wortliste dadurch auf fünf Treffern,
gemeldet worden waren null (Web 9.10.1).

**Eine grüne Zahl ist erst dann ein Beleg, wenn sie das Gemessene benennt.**
Der Bilderlauf meldete nach O9c „248 Bilder, 0 Überlauf" — 176 davon zeigten
die Anmeldeseite (F-P3-AQ). Bei jedem Prüfmittel dazusagen, **was** es
gemessen hat, und im Zweifel eine unabhängige Gegenprobe fahren; für den
Bilderlauf steht sie in seiner `LIESMICH.md`.

## 7. Konzept und Umsetzung

Konzeptarbeit findet in einer getrennten Sitzung statt und mündet in ein
Konzeptdokument. **Ablage: `docs/konzepte/`** — das Konzept, das Prüfdokument
daneben, Mockups in einem Unterordner. Der abgeschlossene Bestand bis S3 liegt
in `docs/konzepte/erledigt/` und wird nicht mehr fortgeschrieben (Rahmenplan
R62). Innerhalb von Claude Code gilt:

- Die Aufgabe ist in Arbeitspakete gegliedert; **eines nach dem anderen**.
- Nach jedem Arbeitspaket: Konzeptdokument fortschreiben — was ist erledigt,
  welche Probleme sind aufgetreten, wie wurden sie gelöst, welche Entscheidungen
  sind dabei gefallen. Dazu ein Prüfstand: was wurde geprüft und wie, was steht
  noch aus und auf welchem Weg. **Ein Statusblock am Kopf des Konzepts** sagt,
  welches Paket in Arbeit ist, welche erledigt sind und wo es hakt; danach
  wird der Arbeitszweig **gepusht**, damit andere Instanzen den Stand sehen
  (Abschnitt 8).
- Erst dann zum nächsten Paket.
- **Nach der Freigabe des Abschlusses:** Erledigt-Zeile in
  `docs/Rahmenplan.md` Abschnitt 8 (Versionen, Datum, wesentliche Änderungen,
  Prüfzahlen, letzter Commit des Konzepts), Reste nach Abschnitt 6, Backlog
  nach Abschnitt 5, eine Zeile nach Abschnitt 10 — und **das Konzept wird
  gelöscht**; die Git-Historie behält es. Das **Prüfdokument bleibt, bis
  seine Prüfliste abgehakt ist**, und wird dann ebenso gelöscht. Wer ein
  Konzept löscht, ohne die Erledigt-Zeile zu schreiben, hat die Phase nicht
  abgeschlossen.
- **Am Ende der Phase ein Prüfdokument** — eine eigene Datei neben dem
  Konzept, nicht ein Abschnitt darin. Das Prüfprotokoll im Konzept beantwortet
  „ist es belegt?“; das Prüfdokument beantwortet „was muss **ich** noch tun?“.
  Es enthält: was maschinell geprüft wurde (mit Mittel **und** Zahl), was im
  Browser geprüft wurde, **was nicht geprüft werden konnte und warum**, und
  als Kernstück eine abhakbare Prüfliste — je Punkt der konkrete Bedienweg,
  das erwartete Ergebnis und **woran ein Scheitern zu erkennen ist**. Dazu die
  Grenzen der benutzten Prüfmittel.
  Zwei Regeln dabei: **Eine Prüfung ohne Zahl ist keine Prüfung**
  („39 447 Elementmessungen, keine Abweichung“ statt „unverändert“), und
  **was nicht geprüft werden konnte, wird gesagt, nicht weggelassen** — an den
  Anfang, nicht in eine Fußnote.

**Modellwahl:** Standard für die Umsetzung ist **Opus**, ohne Nachfrage.
Sieht das Konzept für einen Schritt ausdrücklich **Fable** vor, vor Beginn
dieses Schritts darauf hinweisen und **pausieren**, bis das Modell
umgestellt oder anders entschieden ist.

## 8. Commits

Ein Commit je abgeschlossenem Arbeitspaket, deutsche Nachricht. Die Historie
nennt bislang nur die Version (`web v7.0.2`); besser ist Version **und** ein
Satz zur Sache. **Der Arbeitszweig wird nach jedem Arbeitspaket gepusht**
(Rahmenplan K7, R62) — das deployt nichts, solange es nicht `main` ist, und
andere Instanzen sehen Stand und laufendes Paket. **Auf `main` kommt eine
Phase einmal, am Ende, nach ausdrücklicher Bestätigung** — ein Push dorthin
deployt sofort (Abschnitt 3). Nicht committen: `config.php`, Build-Ausgaben
der Uhr (`watch/bin/`, `*.prg`), Sicherungen.

## 9. Pflegepflichten

Beschlossen in P3 (E-P3-06). Abschnitt 2 sagt, **was** bei jeder Änderung
mitläuft; dieser Abschnitt sagt, **wohin** es gehört.

Wer etwas ändert, pflegt das zugehörige Dokument im selben Paket nach —
nicht später, nicht „in P6":

- **Gestaltung** (Stylesheet, Bausteine in `ui.php`, Symbole, Token,
  Schwellen): `docs/Design.md`. Ein neuer Baustein oder eine neue
  Darstellung entsteht nur nach ausdrücklicher Freigabe mit Mockup; bis
  dahin werden vorhandene Bausteine verwendet.
- **Sicherung und Import** (`backup_*`, `adminbackup_*`, `import*`,
  Formate): `docs/Backup-Format.md`, `docs/Technik.md`; der Werdegang liegt
  in `docs/konzepte/erledigt/Konzept-S1-Sicherung-Import.md` und
  `…/Konzept-S2-Mengen-Spuren-Sicherung.md` (Protokoll, nicht mehr
  fortgeschrieben).
- **Begriffe und Texte:** `tools/wortliste/` laufen lassen; Handbuch an
  der betroffenen Stelle nachziehen.
- **Fremdbestandteile** (Bibliotheken, Schriften, Symbole, Dienste):
  `docs/Lizenzen.md`.
- **Android-App** (`android/`): `android/LIESMICH.md` (Bauanleitung,
  Entscheidungen, Prüfstand), `docs/Technik.md` 5a (wie es zusammenhängt),
  `docs/Lizenzen.md` 6a (Fremdbestandteile — die Liste selbst steht in
  `android/gradle/libs.versions.toml`), `docs/Geraete-Eingabe.md` (Wear-Teil).
- **Prüfmittel:** nach jedem Paket `tools/vollstaendigkeit/`,
  `tools/screenshots/` (berührte Seiten) und `tools/wortliste/`; der
  Stilvergleich wacht ab P4 wieder.

**Stand der Umsetzung:** erledigt mit O12. `docs/Design.md` und
`docs/Lizenzen.md` stehen; `docs/Branding.md` ist damit abgelöst und aus dem
Repositorium entfernt. Was daraus noch galt, ist übernommen — die drei offenen
Punkte B1 bis B3 sind in P3 erledigt worden und in `Design.md` als solche
vermerkt.
