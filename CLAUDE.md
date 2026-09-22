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

**Seit Web 20.4.0 gibt es zwei Wege** (P5a/AP1, R67; Einzelheiten in
`docs/Technik.md` 6). **Die Kette liegt seit Kette II/AP5 in zwei Dateien:**
`.github/workflows/auslieferung.yml` sagt, **wann** ausgeliefert wird (die
Auslöser, die vier Jobs, die Freigabe), und
`.github/workflows/ausliefern-lauf.yml` sagt, **was dabei geschieht** — die
Schrittfolge, einmal, für beide Umgebungen. Wer einen Schritt ändert, ändert
ihn dort und damit für beide; das ist der Zweck (E-KH-14, -17).

- **Push auf `main`** → per FTPS auf **Staging**
  (`staging-nadoku.gen-em.org`). Kein Produktivserver.
- **Tag `web-vX.Y.Z`** → nach **Pflichtfreigabe durch die Betreiberin**
  (GitHub-Umgebung `produktion`) und nach dem **Backup-Tor** auf Produktiv.

**Staging liegt seit dem 20.09.2026 bei einem anderen Hoster als Produktiv**
(lima-city, `staging-nadoku.gen-em.org`; E-KH-04). Vorher lag es im selben
Webspace wie Produktiv, und das war der Fehler: Staging-PHP konnte Produktivs
`config.php` lesen. Der Preis des Umzugs steht in `docs/Technik.md` 6.3a —
**Staging belegt kein Plattformverhalten von Produktiv mehr.** Eine Zahl, die
Stufe 2 auf Staging misst (Zeitgrenzen, Speicher, `max_user_connections`),
gilt für Staging und sonst nirgends. Wer eine ältere Quelle liest, findet
dort `staging.nadoku.gen-em.org` im selben Tarif — das ist der Stand bis zum
19.09.2026.

Davor stehen zwei Prüftore: Stufe 1 (`pruefung.yml`, ohne Installation) und
Stufe 2 (gegen Staging). **Der Produktionslauf verlangt einen grünen
Stufe-1-Lauf auf demselben Commit** — fehlt er, bricht er ab, statt ungeprüft
auszuliefern.

**Stufe 1 läuft seit dem 21.09.2026 auf Arbeitszweigen nur noch beim Pull
Request, auf `main` bei jedem Push** (Vorgriff auf PK-05). Bis dahin stand
dort `branches: ['**']`, und jeder Push löste **zwei** Läufe desselben Namens
aus: einen schnellen über das Ereignis `pull_request` (rund eine Minute, weil
er gegen den gemeinsamen Vorfahren vergleicht) und einen langsamen über
`push` (rund 56 Minuten, weil er ohne Vergleichsstand im Zweifel alles
misst). Der Zweigschutz wartet auf den Namen, also auf den langsameren. Wer
eine ältere Quelle liest, findet dort „jeder Push, jeder Zweig".

**Bis Web 20.3.0 stand hier das Gegenteil**, und es stimmte: Ein Push auf
`main` mit Änderungen unter `server/` lud sofort auf den Produktivserver, ohne
Zwischenstufe und ohne Testumgebung. Wer eine alte Sitzung, ein altes
Protokoll oder einen alten Kommentar liest, liest das noch.

- **Niemals ungefragt pushen.** Committen ja, wenn beauftragt; pushen nur auf
  ausdrückliche Anweisung. Das gilt weiter — ein Push auf `main` löst zwar
  keinen Produktiv-Deploy mehr aus, aber einen auf Staging.
- **Ein Tag ist die Auslieferung.** Er wird nie nebenbei gesetzt.
- Nach einem Deploy mit Schemaänderung muss eine Administratorin `update.php`
  aufrufen. Das steht sonst still und die Anwendung läuft ins Leere — beim
  Vorschlagen einer Migration ausdrücklich mit ansagen. Die Kette lässt in
  diesem Fall den **Wartungsmodus an** und sagt es im Lauf; ab P5a/AP3 tut es
  der Torwächter auch ohne Kette.
- Ohne erhöhte `WEB_VERSION` sieht der Browser alte Dateien. Seit P5a
  verweigert der Produktionslauf außerdem, wenn Tag und `WEB_VERSION`
  auseinandergehen — **und seit Kette II/AP6 prüft er nach dem Abgleich
  nach**, ob die Anlage die neue Fassung auch wirklich meldet. Tut sie es
  nicht, bleibt die Wartung an.
- **Die Kette hat keine Vorgabewerte mehr** (E-KH-07, AP6). `FTP_ZIELPFAD`
  und `FTP_STATE_PFAD` müssen in **beiden** Umgebungen stehen, `WACHE_BASIS`
  als Repositoriums-Variable; fehlt eine, ist der Lauf **rot** — im ersten
  Schritt, vor jedem Zugriff auf den Server. Bis dahin sprang ein fest
  eingebauter Wert ein, und das ließ eine falsch eingerichtete Anlage nicht
  auffallen: Der Lauf war grün und synchronisierte in ein fremdes
  Verzeichnis. Wer eine Anlage neu einrichtet, trägt sie zuerst ein.
- **Überspringen ist rot** (E-KH-12, AP6). Die fünf Stellen in Stufe 2, die
  sich bei fehlender Zuarbeit selbst übersprangen und grün meldeten, brechen
  ab. Ein Prüfschritt, der sich selbst überspringt, meldet grün, ohne
  gemessen zu haben.
- **Jede fremde `uses:`-Zeile hängt an einer 40-stelligen Commit-SHA**
  (E-KH-10), die Version als Kommentar daneben — **elf sind es** (zehn bis
  AP7; der Job `Rückfallstand (Staging)` bringt einen weiteren
  `actions/checkout` mit). Die zwei **lokalen** (`./.github/workflows/…`)
  tragen keine und können es nicht: Ein lokaler Pfad nimmt keinen Ref und
  läuft immer auf dem Commit des Aufrufers. Wer eine Aktion aktualisiert,
  tauscht SHA **und** Kommentar. **Die Zahl ist kein Prüfwert, sondern eine
  Orientierung** — der Prüfwert ist „keine fremde Zeile ohne SHA", und den
  zählt man nach, statt ihn abzuschreiben:
  `grep -rhoE 'uses: [^ ]+@[0-9a-f]{40}' .github/workflows/ | wc -l` gegen
  `grep -rh 'uses:' .github/workflows/ | grep -vc 'uses: \./'`.
- `server/config.php`, `install.lock`, `server/wartung.lock`,
  `server/ueberlast.json` (der Zähler der Verbindungsgrenze, P5a/AP9),
  `server/sicherungen/`, `server/apk/` und — seit Web 20.26.0 —
  `server/.sitzungen/` (die PHP-Sitzungsdateien, Schritt 16, E-SA-05) liegen
  nur auf dem Server. Sie stehen in `.gitignore` **und** in der
  Ausnahmeliste des FTPS-Schritts — beides muss so bleiben.
  **Acht Pfade sind es, und die Zahl ist der Prüfwert.** Jeder steht dort
  zweimal, als Datei- und als Verzeichnismuster (`sicherungen/**` und
  `sicherungen/`), weil die Aktion beides getrennt prüft.
  **Seit Kette II/AP5 steht die Liste EINMAL**, in
  `.github/workflows/ausliefern-lauf.yml` (E-KH-20 (1)). Bis dahin stand sie
  zweimal, wortgleich, je einmal für Staging und Produktiv — und zwei
  wortgleiche Listen sind keine zwei Riegel, sondern einer und ein
  Versprechen: Wer die eine ergänzt und die andere vergisst, schützt eine
  Umgebung und die andere nicht, und merkt es erst, wenn eine Datei fehlt,
  die es nur auf dem Server gab. **Wer eine ältere Quelle liest, findet dort
  „beide FTPS-Schritte" — das ist der Stand bis zum 21.09.2026.**
  **Was passiert, wenn `.sitzungen/` fehlt:** beim heutigen Transport nichts —
  die Fremd-Aktion listet das Fernverzeichnis nie, sondern liest nur ihre
  eigene Zustandsdatei (nachgemessen im Quelltext von
  `@samkirkland/ftp-deploy` 1.2.3 bis 1.2.5). Bei einem Spiegel mit
  Löschabgleich **löscht jeder Deploy alle Sitzungen**, und alle Angemeldeten
  fliegen raus.
- **`server/install.php` steht seit Web 20.15.2 ebenfalls in der
  Ausnahmeliste** (Nr. 214) — anders als die Zeile darüber aber **nicht** in
  `.gitignore`: Sie liegt im Repositorium, wird nur nicht ausgeliefert, weil
  das Runbook „danach löschen" sagt. **Preis:** Eine leere Anlage lässt sich
  nicht mehr allein über die Kette einrichten — die Datei muss einmal von Hand
  hinauf.
- **`integritaet.yml` hängt am Anzeigenamen des Auslieferungslaufs.** Er heißt
  jetzt „Auslieferung" und nicht mehr „Server per FTP hochladen". Wer ihn
  umbenennt, hängt die Wache ab, und zwar still.
- **Der Zweig `produktion` ist ein Zeiger, kein Arbeitszweig** (seit AP2 der
  Kettenhärtung, E-KH-13). Er zeigt auf den Commit, der auf dem
  Produktivserver liegt, und die Integritätswache vergleicht **gegen ihn**.
  Bewegt wird er **ausschließlich vom Job `zeiger`** in
  `.github/workflows/auslieferung.yml`, und nur nach einem erfolgreichen
  `produktion`-Job. Daraus folgt dreierlei: **Niemand entwickelt dort**, es
  gibt **keinen PR dorthin**, und er wird **nicht von Hand bewegt** — ein
  Zeiger, der auf etwas anderes zeigt als auf das Ausgelieferte, macht die
  Wache nicht blind, sondern zu einer Quelle von Falschmeldungen, und das ist
  schlimmer.
  **Rückwärts ist erlaubt und ausdrücklich vorgesehen:** Ein Zurücksetzen legt
  einen älteren Stand oben auf, und der Zeiger folgt dorthin. Deshalb schiebt
  der Job erzwungen.
  **Fehlt der Zweig, ist die Wache rot** und sagt, wie man ihn anlegt — sie
  läuft nie still grün weiter. Wer den Zweig löscht, schaltet damit keine
  Prüfung ab, sondern löst sie aus.

## 4. Feste Zusagen der Anwendung

Diese Eigenschaften sind das Versprechen des Projekts. Eine Änderung, die eine
davon aufweicht, wird nicht nebenbei gemacht, sondern angesprochen:

- **Ende-zu-Ende-Verschlüsselung — und zwar genau dieser Felder.** Name,
  Geburtsdatum, Alter, Diagnose, Einsatznummer, Adresse und Koordinate des
  Einsatzorts, dessen Beschreibung und — seit Web 19.0.0 — die **Notizen des
  Einsatzes** werden im Browser ver- und entschlüsselt (der Katalog steht in
  `docs/Technik.md` 4.98). Klartext dieser Felder geht nie an den Server, in
  ein Log oder in eine API-Antwort.
  **Im Klartext liegen dagegen:** die **Notizen des Diensttags**
  (`days.notes` — Betriebsnotizen, nicht die des Einsatzes), die GPS-Spur, die
  Koordinate **jeder Phase** (Phase 4 und 5 sind der Einsatzort),
  `site_ele_m`, das Transportziel samt Koordinate, Zeiten,
  Reanimationsverlauf und Besatzungsnamen. Aus Spur und
  Phasenkoordinaten **lässt sich der Einsatzort rekonstruieren** — die
  Verschlüsselung der Adresse verbirgt ihn nicht. Wer das aufweichen oder
  ausweiten will, findet den Weg in `docs/konzepte/Konzept-V1-Ortsdaten.md`
  (Weg B) und Backlog Nr. 43; wer die Zusage zitiert, zitiert diesen Absatz
  vollständig oder gar nicht.
  **Die Klartextliste ist ein Zustand, kein Ziel:** Spur,
  Phasenkoordinaten, Reanimationsverlauf **und das Transportziel samt
  Koordinate** sollen in **S11** (Schritt 12a, R78) verschlüsselt werden —
  das kehrt die Klartext-Entscheidung zur Zielklinik im Feldkatalog um.
  Bis dahin gilt der Absatz oben unverändert; einzeln vorgezogen wird
  nichts davon, weil die Phasenkoordinate „Ankunft Klinik" daneben stünde.
  **Der Weg ins Blob führt über den Feldkatalog** (`'store' => 'pat'` in
  `mission_fields.php`), nicht über handgeschriebenes Markup: `mf_ist_spalte()`
  nimmt ein solches Feld dann von selbst aus jedem `SELECT`, `INSERT` und
  `UPDATE` auf `missions`. Wer ein weiteres Feld verschlüsselt, ergänzt den
  Katalogeintrag, diesen Absatz **und** `docs/Technik.md` 4.98 — und schreibt
  einen Anhebelauf für den Altbestand (`api/pat_anheben.php` ist das Muster;
  der Server kann nicht verschlüsseln, nur der Browser kann es).
  **Seit S10 hängt der Datenschlüssel zusätzlich am Server-Anteil**
  (`kdf_anteil` in `config.php`, E-S10-17). Er wird je Konto per HMAC über die
  Kontonummer abgeleitet, geht per HKDF in den Datenschlüssel ein und wird nur
  an die angemeldete Sitzung ausgeliefert. **Der Server kann damit weiterhin
  nichts öffnen** — er kennt den Anteil, nicht die PBKDF2-Hälfte aus dem
  Passwort. Was sich ändert, ist die Rechnung des Angreifers: Ein
  Datenbankabzug allein reicht nicht mehr für einen Offline-Angriff auf das
  Passwort. **`config.php` ist damit Schlüsselträger aller Konten** — das
  Wiederanlaufpaket (vier Stücke) und das Schlüsselblatt sind Pflicht, nicht
  Empfehlung. Wer diesen Absatz zitiert, zitiert den nächsten Satz mit:
  **`pat_wrap_rc` hängt NICHT am Anteil.** Der Wiederherstellungsschlüssel
  öffnet ohne ihn, und deshalb ist der Verlust des Anteils **kein
  Datenverlust**, sondern ein Passwort-Reset für alle. Eine
  `edka1:`-Wiederherstellungshülle wäre der Verlust genau dieses Rückwegs;
  `WRAP_RC_RE` und `huelle_rc_pruefen()` lassen sie nicht zu.
  **Zwei Zeichen tragen die Zusage in die Oberfläche:** ein Schloss an jedem
  verschlüsselten Feld, die Kleinzeile „Klartext — keine Patientendaten" an
  jedem Klartext-Freitextfeld. Sie schließen einander aus; die Wahl steht an
  einer Stelle (`$feldKennzeichen()` in `einsatz_form.php`), der Satz einmal im
  Katalog. Das Schloss gilt für **beide** Ansichten — im Formular und in
  `einsatz.php` (`dtGeschuetzt()`); kann eine Karte es an keinem Feld tragen,
  weil deren Beschriftung ausgeblendet ist, trägt es der **Kartentitel**
  (`ui_karte_start(['geschuetzt' => true])`). **Und: Die Zahl belegt es
  nicht.** „8 Schlösser gezählt" sagt nichts darüber, ob eines fehlt — beide
  Lücken, die Web 19.1.1 geschlossen hat, standen neben einer richtigen Zahl
  (Backlog Nr. 170).
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
  `docs/Technik.md`, Abschnitt 4.97; Nachweis: `php tools/proben/spur/probe.php`.

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
- Bedienhöhe der **Weboberfläche**: **44 px** — und **36 px am Zeigergerät
  ab 1024 px** (`(hover:hover) and (pointer:fine) and (min-width:1024px)`,
  E-S8-09/R76, seit Web 15.5.0). Alle drei Bedingungen müssen gelten: Ein
  Touch-Laptop mit 1920 px ist ein Fingergerät. Der Bilderlauf misst gegen
  beide Sollwerte (`--finger` schaltet ihn auf Fingergerät um). Die
  Android-Apps folgen der Plattformvorgabe von **48 dp** (R58, 02.09.2026):
  Sie werden mit Handschuhen bedient, und dafür ist die Android-Zahl
  gemacht.
- Spaltenbreiten in Tabellen nie über `:nth-child` — sie zählen Spalten ab und
  rutschen beim Streichen einer Spalte still auf die falsche. Klassen benutzen.
- Die Tabellen in `Design.md` (Token, Schwellen, Symbole, Bausteine) sind
  **erzeugt**: `python3 tools/design/tabellen.py alle`. Wer eine davon von Hand
  ändert, ändert sie an der falschen Stelle.

## 6. Prüfen

Es gibt **keine automatisierten Tests** für Web und Uhr; die Android-Module
haben welche. Geprüft wird durch Lesen, im Browser, im Emulator und im
Simulator. **Wo welche Prüfung läuft, mit welchem Mittel und ab welcher
Stufe, steht in `docs/Pruefablauf.md`** — das ist die eine Stelle dafür. Die
Arbeitsumgebung, ihre Ausbaustufen, die sieben Umgebungswerte und was sie
**nicht** kann: `docs/Sandbox-Setup.md`.

**Die sieben Grundsätze** (ausgeführt in `docs/Pruefablauf.md` 1):

1. **Jede Prüfung hat genau eine Stelle.** Sie steht dort — nicht in einem
   Kommentar, nicht im Gedächtnis.
2. **Arbeit örtlich, Riegel in der Kette.** Was Fehler *findet*, läuft in der
   Arbeitsumgebung; was Fehler *aufhält*, im Tor. Die Anlage misst nur, was
   nur die Anlage zeigen kann.
3. **Zweimal nur die Gegenlesung.** Sonst wird nichts zweimal gemessen.
4. **Der Umfang folgt der Änderung, nicht dem Kalender.**
5. **Ein Prüfmittel braucht einen Fehler** — eine Zeile „Anlass: Nr. …".
   Ohne sie steht es auf der Streichliste.
6. **Geschichte steht im Commit, nicht im Werkzeug.**
7. **Kein stilles Überspringen, keine grüne Zahl ohne Gegenstand.**

Vier Sätze, die beim Arbeiten im Kopf sein müssen:

- **Die Prüfmittel laufen zuletzt**, nach der letzten Änderung — erst Code,
  dann Dokumentation, dann die Mittel. Ein Werkzeug, das davor lief, misst
  einen Stand, den es nicht mehr gibt.
- **Eine grüne Zahl ist erst dann ein Beleg, wenn sie das Gemessene
  benennt.** Dazusagen, **was** gemessen wurde, und im Zweifel eine
  unabhängige Gegenprobe fahren.
- **Was nicht geprüft werden konnte, wird gesagt**, statt als erledigt
  gemeldet zu werden — und im Prüfdokument an den Anfang, nicht in eine
  Fußnote.
- **Pflicht ist der Versuch, nicht der Erfolg.** Ein Prüfmittel, das in
  diesem Container nicht läuft, ist ein **Befund mit Zahl** — welches
  Abbild, welche Fassung, welche Meldung —, kein stillschweigend
  übersprungener Punkt.

Drei Regeln haben schon Schaden angerichtet, als sie nirgends standen. Sie
stehen ausgeführt in `docs/Pruefablauf.md` und **nur dort**; hier steht, dass
es sie gibt und wo:

- **Markup aus einer Quelldatei lesen** — welches Muster, warum die kurze
  Form dreimal Schaden angerichtet hat, und wann sie ausnahmsweise richtig
  ist: `Pruefablauf.md` 6.4.
- **Sichtbarer Text und die Wortliste** — welche Bereiche sie liest, was grün
  heißt, und warum ein neues Verzeichnis oder Dokument im selben Paket
  eingetragen wird: `Pruefablauf.md` 6.6.
- **Android-Änderung und der Emulator** — was er belegt, was der Bilderlauf
  stattdessen belegt, und was gilt, wenn er nicht startet:
  `Pruefablauf.md` 6.9.

**Keine Prüfzahl steht in dieser Datei.** Sie steht in der `LIESMICH.md` des
Werkzeugs und in `docs/Pruefablauf.md` 6.11. Eine Zahl an zwei Stellen altert
an einer davon unbemerkt: `docs/Technik.md` nannte bis Web 20.21.1 noch 366
Bilder, während die Kette längst mit 377 lief.

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
- **Benennung.** Jedes Konzept führt ein Kürzel; daraus leiten sich alle
  Nummern ab: `XX-NN Schlagwort` für Arbeitspakete, `XX-MN` für Meilensteine
  der Betreiberin, `E-XX-NN` für Entscheidungen, `F-XX-NN` für **Befunde**,
  `Q-XX-NN` für **Fragen an die Betreiberin**, `P-XX-NN` für Prüfpunkte.
  Nummern sind **zweistellig** und werden **nie wiederverwendet**; die
  Commit-Nachricht beginnt mit dem Paket (`PK-03: …`). **`F` und `Q` waren
  bis zum 21.09.2026 ein Kürzel** („Befunde und offene Fragen") — wer eine
  ältere Quelle liest, findet Fragen dort als `F-…`. Die Tabelle steht in
  `docs/Pruefablauf.md` 7.

**Modellwahl:** Standard für die Umsetzung ist **Opus**, ohne Nachfrage.
Sieht das Konzept für einen Schritt ausdrücklich **Fable** vor, vor Beginn
dieses Schritts darauf hinweisen und **pausieren**, bis das Modell
umgestellt oder anders entschieden ist.

## 8. Commits

Ein Commit je abgeschlossenem Arbeitspaket, deutsche Nachricht. Die Historie
nennt bislang nur die Version (`web v7.0.2`); besser ist Version **und** ein
Satz zur Sache — bei einem Konzept beginnt sie mit dem Paket (Abschnitt 7).
**Der Arbeitszweig wird nach jedem Arbeitspaket gepusht** (Rahmenplan K7,
R62) — das deployt nichts, solange es nicht `main` ist, und andere Instanzen
sehen Stand und laufendes Paket.

**Eine Instanz mergt nie.** Sie öffnet am Ende der Phase einen Pull Request;
die Betreiberin mergt ihn. Auf `main` kommt eine Phase einmal, am Ende, nach
ausdrücklicher Bestätigung — und ein Push dorthin ist seit dem 21.09.2026
ohnehin gesperrt (Zweigschutz, `docs/Pruefablauf.md` 2.3). Das gilt auch für
den Weg über ein Werkzeug: `mcp__github__merge_pull_request` und
`mcp__github__enable_pr_auto_merge` stehen in der Deny-Liste von
`.claude/settings.json`. Der Grund ist gemessen (F-PK-01): Die
GitHub-Werkzeuge handeln unter der Identität der Betreiberin, und ein Ruleset
kann sie deshalb nicht von ihr unterscheiden — ein Merge über die
Schnittstelle ging durch. **Wer die Deny-Liste ändert, hebt diesen Riegel
auf**; das fällt im Pull Request auf, weil die Datei dann in der Berührung
steht.

Nicht committen: `config.php`, Build-Ausgaben der Uhr (`watch/bin/`,
`*.prg`), Sicherungen.

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
- **Begriffe und Texte:** Handbuch an der betroffenen Stelle nachziehen.
  Die Textprobe **von Hand zu fahren ist seit PK-04/1c nicht mehr nötig** —
  sie läuft im Tor und meldet nur **neue** Treffer (E-PK-08).
- **Fremdbestandteile** (Bibliotheken, Schriften, Symbole, Dienste):
  `docs/Lizenzen.md`.
- **Android-App** (`android/`): `android/LIESMICH.md` (Bauanleitung,
  Entscheidungen, Prüfstand), `docs/Technik.md` 5a (wie es zusammenhängt),
  `docs/Lizenzen.md` 6a (Fremdbestandteile — die Liste selbst steht in
  `android/gradle/libs.versions.toml`), `docs/Geraete-Eingabe.md` (Wear-Teil).
- **Prüfmittel:** welches Mittel welche Berührung beantwortet, steht in
  `docs/Pruefablauf.md` — dort und nur dort. Hier stand bis PK-01 eine
  zweite, kürzere Liste; sie nannte den Stilvergleich als „ab P4 wieder",
  und P4 ist mit O12 vorbei.

**Stand der Umsetzung:** erledigt mit O12. `docs/Design.md` und
`docs/Lizenzen.md` stehen; `docs/Branding.md` ist damit abgelöst und aus dem
Repositorium entfernt. Was daraus noch galt, ist übernommen — die drei offenen
Punkte B1 bis B3 sind in P3 erledigt worden und in `Design.md` als solche
vermerkt.
