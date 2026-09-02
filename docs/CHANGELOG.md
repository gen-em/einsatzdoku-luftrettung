# Changelog — Einsatzdoku

Format nach [Keep a Changelog](https://keepachangelog.com/de/).

**Weboberfläche** und **Uhr-App** werden getrennt gezählt, weil sie unabhängig
voneinander ausgeliefert werden: `server/version.php` bzw.
`watch/source/Const.mc`. Die Web-Version steht in der Fußzeile jeder Seite. Bis
Web 5.3.0 hing sie zusätzlich an allen Stylesheet- und Skript-Adressen; seit
Web 5.4.0 steht dort der Zeitstempel der jeweiligen Datei, damit nach einem
Update nur die tatsächlich geänderten Dateien neu geladen werden. Die
Uhr-Version steht auf der Sync-Seite. Die Stände 1.0 bis 1.2 unten sind die
frühen Spezifikations-Stände des Gesamtprojekts, vor der getrennten Zählung.

## [Werkzeug: Uhr-Prüfstand] — 2026-09-02

**Die Aufbauanleitung führte an zwei Stellen in die Irre.** Weder die
Weboberfläche noch die Uhr-App sind geändert, deshalb trägt dieser Eintrag
keine Versionsnummer — wie schon der Eintrag vom 30.08.2026.

Anlass war die Frage, ob das Wissen um diese Umgebung so festgehalten ist, dass
ein anderer sie nachbauen kann. Beim Nachlesen fielen zwei Fehler auf, die beide
erst beim Nachbauen weh getan hätten, und einer, der schon weh getan hat.

### Werkzeug — Die Beispieladresse hätte nicht funktioniert

`--cut-dirs=1` stand fest im Skript. Das trägt nur, wenn `Devices/` und
`Fonts/` in der **Wurzel** des Servers liegen. Die LIESMICH nannte als Beispiel
`https://beispiel.invalid/ciq` — eine Adresse mit Pfad, und damit landet der
Baum als `Devices/Devices/<gerät>/` eine Ebene zu tief. `monkeyc` findet dann
kein einziges Gerät, ohne dass ein Aufruf gescheitert wäre. Das Skript rechnet
den Wert jetzt aus der Adresse aus; nachgemessen gegen einen örtlichen
Testserver mit beiden Adressformen, beide legen den Baum richtig ab.

Dazu zwei Anforderungen, die vorher nirgends standen: Die Quelle braucht eine
eingeschaltete **Verzeichnisauflistung** (ohne sie holt `wget -r` nichts, meldet
aber nur „nicht abrufbar"), und `aufbau` holt nur die drei Zielgeräte. Für
Stufe I und `geraeteklassen.py` braucht es den ganzen Bestand — dafür jetzt
`CIQ_ZIELE=alle`, zum Stand 99 Geräte.

### Werkzeug — Die Dateinamen der Ablagen sagen nichts

Bisher stand hier, der Simulator lege die Einstellungen unter
`SETTINGS/<GERAET>.SET` ab. Das ist falsch, und der Irrtum hat am 31.08.2026
eine Messung gekostet: Es sollte festgestellt werden, ob eine geänderte
Anwendungs-ID einen eigenen Speicher bekommt, und die Antwort schien an den
Dateinamen ablesbar. Sie ist es nicht.

Gemessen am 02.09.2026 auf der fenix6pro, jeweils mit vorher geleerten Ablagen:
Zwei Kompilate aus **demselben** Quelltext (`zzprobe.prg`, `qq7.prg`) ergaben
beide `V2.SET` und `V2.DAT` — den Namen eines viel früher geladenen `v2.prg`.
Und ein Lauf von `fenix6pro.prg` legte `V2.SET` **und** `UUID_ALT.DAT` an: ein
Lauf, zwei Namen, keiner davon der des Kompilats. Der Simulator vergibt die
Namen offenbar aus einem Vorrat früher gesehener Dateien, und das Löschen der
Dateien räumt diesen Vorrat nicht mit ab.

Daraus folgt die Regel, die jetzt in der LIESMICH steht: Eine Ablage wird
**ganz** geleert, nie einzeln — ein `rm` auf den erwarteten Namen trifft
womöglich nichts und misst dann den Zustand des vorigen Laufs. Und die Frage
nach der Anwendungs-ID bleibt offen; sie ist von außen nicht zu beantworten,
sondern nur mit einem Zähler **innerhalb** der App.

### Werkzeug — Dazugekommen

`speicher-leeren` räumt `Application.Storage` und setzt die App damit auf
„frisch installiert" zurück — den Zustand, den ein Gerät nach der Installation
hat. Vorher gab es das nur für die Einstellungen, und jeder zweite Lauf maß
deshalb den Zustand des ersten. Belegt: nach dem Räumen zeigte die Sync-Seite
der fenix6pro „Nicht eingerichtet / Erst Server-Adresse setzen".

Der Hinweis in `starten` nennt jetzt jede vorhandene `.SET`-Datei, statt eine
nach dem Gerätenamen zu raten — sie hieß nur deshalb meist so, weil der
Prüfstand seine Kompilate nach dem Gerät benennt.

### Werkzeug — `git status` taugt nicht als Beleg für `uhr-bilder`

Beim Gegenprüfen der S3-Änderung an den Vektorvorlagen meldete Git nach einem
Lauf von `tools/uhr-bilder/erzeugen.sh` **17 geänderte PNG**. Kein einziger
Bildpunkt war anders: Das Werkzeug schreibt in jede Datei einen `tIME`-Block
mit der aktuellen Uhrzeit. Gemessen mit `compare -metric AE` gegen
`git show HEAD:<pfad>` — 17-mal 0. Der Hinweis steht jetzt in der `LIESMICH.md`
des Werkzeugs, samt dem Weg, wie man es richtig prüft.

### Werkzeug — Und die Anleitung war nicht auffindbar

`CLAUDE.md` Abschnitt 6 sagte „Es gibt keine automatisierten Tests. Geprüft
wird durch Lesen und im Browser." — und erwähnte den Uhr-Prüfstand mit keinem
Wort. Wer nur die Arbeitsanweisung liest, schließt daraus, am Uhr-Code sei
nichts prüfbar; der Weg über `docs/Technik.md` §5.2b muss erst gefunden werden.
Das war die eigentliche Lücke: Die Anleitung war vollständig, aber nicht
auffindbar. Abschnitt 6 nennt den Prüfstand jetzt, samt dem Hinweis, dass die
Adresse erfragt werden muss.

## [Web 12.9.0] — 2026-09-02

**Der Server nimmt endlich an, was die Geräte über sich sagen — und der
Entsperrdialog erscheint nicht mehr mitten in der Arbeit.** Das Zwischenpaket
S6: Serverseite von R42, Behebung von R44. **Mit Migration**
(`2026_09_02_geraetekennung`) — nach dem Deploy muss eine Administratorin
`update.php` aufrufen.

### Web — Was für ein Gerät hat sich da gekoppelt (R42)

Die Uhr schickt beim Koppeln seit **1.9.0** einen Block über sich selbst, die
Handy-App seit **0.2.0**. `pair.php` hat ihn bis hierher stillschweigend
verworfen — er lief ein Jahr lang ins Leere, und jede Kopplung in dieser Zeit
ist für die Statistik verloren. Das war so vorgesehen (R42 nennt es), macht es
aber nicht besser.

Er landet jetzt in drei Spalten an `devices`: **Art** (`uhr`, `handy`,
`sonstiges`), **Modell** und die **Rohangabe** des Geräts. Beide Geräteklassen
fallen auf dieselben drei Spalten, obwohl sie Verschiedenes senden: Die
Garmin-Uhr **kennt ihren Modellnamen nicht** — `DeviceSettings` führt ihn nicht
— und schickt ihre Teilenummer (`006-B4261-00`); das Handy kennt ihn und
schickt Hersteller und Modell (E-S4-28).

**Warum drei Spalten und nicht zwei.** R42 nennt zwei. Die dritte hält die
Rohangabe daneben, und das ist keine Bequemlichkeit: Der Modellname entsteht
aus einer erzeugten Tabelle, und die kann nur kennen, was es beim Erzeugen
schon gab. Eine Uhr, die nächstes Jahr erscheint, fiele sonst dauerhaft auf
„unbekannt" — und zwar unwiederbringlich, weil die Teilenummer dann nirgends
mehr stünde. Mit der Rohangabe lässt sich jede Zeile später erneut auflösen.
Sie ist außerdem die einzige Spalte, die die **Behauptung des Geräts**
unverändert festhält; das Modell ist bereits eine Auslegung des Servers.

**Die Auflösung liegt auf dem Server, nicht auf der Uhr** — eine Modelltabelle
auf einem Gerät mit 128 kB wäre der falsche Platz. Sie steht in
`server/geraetemodelle.php` und ist **erzeugt** (`tools/geraetemodelle/`, aus
den Gerätedateien der Uhr-Plattform). Wer sie von Hand ergänzt, ergänzt sie an
der falschen Stelle.

**Sie wird vorerst leer ausgeliefert.** Die Gerätedateien liefert nur der
SDK-Manager aus; die Adresse ihrer Bereitstellung steht bewusst nicht im
Repositorium und muss erfragt werden (Rahmenplan, Abschnitt 6). Bis dahin zeigt
jede Garmin-Kopplung die Teilenummer statt des Modellnamens.

**Und deshalb gibt es `nachaufloesen.php`.** `pair.php` löst die Teilenummer im
Moment der Kopplung auf, und nur dann — eine Zeile, die damals auf eine leere
Tabelle traf, bliebe sonst für immer, wie sie war. Genau das hätte die
Begründung für die dritte Spalte zur leeren Zusage gemacht. Das Skript geht die
Zeilen mit Rohangabe durch und trägt nach, was die Tabelle inzwischen kennt;
zeigen ist die Vorgabe, `--schreiben` trägt ein. **Es geht dabei um mehr als
den Namen:** Solange die Tabelle eine Teilenummer nicht kennt, steht in
`geraet_art` die ungeprüfte Selbstauskunft, und die Garmin-App sendet dort fest
`"uhr"` — ein Radcomputer wäre bis zum Nachauflösen falsch gezählt, und das ist
ein falscher Wert und nicht nur ein fehlender. Grenze: Das Skript braucht
Shell-Zugriff; ohne ihn holen die Geräte ihre Angabe bei der nächsten Kopplung
nach.

**Die Tabelle schlägt die Selbstauskunft — bei der Art.** Die Uhr-App sendet
`art` fest als `"uhr"`, weil eine Connect-IQ-App nur auf Garmin-Geräten läuft;
Uhr und Radcomputer kann sie nicht unterscheiden. Die Gerätedateien können es.
Ein Edge, der sich „uhr" nennt, hätte die Statistik sonst still verfälscht.

**Alle drei Spalten dürfen leer sein, und das bleibt so.** Vier Wege legen ein
Gerät an, und nur einer weiß etwas über es: die Kopplung. Manuelles Anlegen,
das virtuelle Gerät „Manuelle Einträge" und der Demo-Bestand haben nichts zu
melden. Ein `NOT NULL DEFAULT 'unbekannt'` hätte daraus eine Aussage gemacht,
wo keine ist — „unbekannt" ist eine Sache der **Anzeige**, nicht der Spalte.
Bestandsgeräte bleiben leer; die Angabe entsteht ausschließlich beim Koppeln,
und eine bereits gekoppelte Uhr wird nicht rückwirkend gefragt.

**Was bewusst nicht erhoben wird.** `uniqueIdentifier` (Uhr) und `ANDROID_ID`,
IMEI, Seriennummer (Handy) senden die Clients gar nicht erst. Und obwohl beide
Displaymaße, Firmware und Plattformfassung mitschicken, werden sie **nicht
gespeichert**: R36 lässt die Gerätekennung als die eine benannte Ausnahme zu —
„welches Gerät", nicht „in welchem Zustand". Backlog Nr. 59 hatte die weiteren
Felder vorgeschlagen; sie fallen damit weg.

**Eine Kopplung scheitert nie an einer Statistikangabe** (JSON-Vertrag 1a). Was
ankommt, wird zugeschnitten statt geglaubt: Längen auf die Spaltenbreite
(mit `mb_substr`, damit ein Umlaut an der Schnittkante die Spalte nicht
unlesbar macht), Steuerzeichen zu Leerzeichen, eine erfundene Geräteart zu
`NULL` statt in die Spalte. Ein Block, der gar keiner ist — eine Zeichenkette,
eine Zahl, `true` —, ergibt drei leere Werte und keinen Fehler.

### Web — Art und Modell in beiden Gerätelisten

Auf dem Geräte-Reiter (Einstellungen) und im Adminbereich, in der Kleinzeile,
die schon Zustand und letzten Kontakt trägt: „Uhr · Venu 3S · aktiv · zuletzt
gesehen: 02.09.2026 14:33". **Kein neuer Baustein** — dieselbe Zeile, ein Feld
mehr darin.

Ist das Modell nicht auflösbar, steht dort die **Rohangabe**: „Uhr ·
006-B4261-00". Das ist eine magere Auskunft, aber es ist eine — und sie sagt
zugleich, dass hier etwas gekoppelt hat, das die Modelltabelle noch nicht
kennt. „Modell unbekannt" hätte das verschwiegen.

### Web — Die Gerätekennung machte die Zeile daneben unlesbar

Im Bilderlauf zu diesem Paket gefunden, und zwar **auch am Stand davor**: Die
öffentliche Gerätekennung steht als Plakette in der Zeile, ist seit Web 4.5.1
36 Zeichen lang und hat keine Umbruchstelle. Sie setzte damit eine
Mindestbreite durch, die dem Text daneben nichts übrig ließ — bei einem kurz
benannten Gerät („Uhr", „Handy" — genau die Vorgabe nach dem Koppeln) fiel die
Kleinzeile auf **ein Wort je Zeile** zusammen, und bei 360 px lief die Seite um
1 px über.

Sie wird jetzt gekürzt: acht Zeichen, Auslassung, die letzten zwei. Der
Adminbereich macht das seit Web 9.7.2 so (Mockup 40); die Rechnung stand dort
im Seitenquelltext und steht jetzt an **einer** Stelle, damit beide Listen
dieselbe Kennung zeigen. Acht Zeichen sind 32 Bit — genug, um zwei Geräte
auseinanderzuhalten und eine Zeile in einer Rückfrage zu benennen; die letzten
beiden stehen dabei, damit ein Vergleich mit der Kopplungs-E-Mail an beiden
Enden anfassen kann.

**Die längere Kleinzeile hat es nur sichtbar gemacht, verursacht hat sie es
nicht.** Nachgemessen mit `tools/screenshots/` gegen den Stand vor der
Änderung: dieselben acht Breiten, derselbe Überlauf.

### Web — Ein frisch gekoppeltes Handy hieß „Uhr"

Ein Fehler aus S4, hier mitgenommen: Der Name eines neu gekoppelten Geräts
stand fest auf `'Uhr'`. Das war richtig, solange nur Garmin-Uhren koppeln
konnten — seit der Handy-App stand in der Geräteliste „Uhr", bis jemand es
umbenannte. Die Vorgabe folgt jetzt der gemeldeten Art („Uhr", „Handy",
„Gerät"). Wo keine Art gemeldet wird, bleibt es bei „Uhr": Ein Gerät ohne
Block ist eine Uhr-Fassung vor 1.9.0, und etwas anderes konnte damals nicht
koppeln.

### Web — Dieselbe Zahl war nicht dieselbe Frist (R44)

Zwei Fristen standen auf 30 Minuten, und der Kommentar in `keyguard.js` sagte
ausdrücklich, sie sollten gleich sein. Sie waren es nie — sie maßen
**Verschiedenes**:

- `auth_guard.php` schreibt `$_SESSION['last_seen']` bei **jeder** Anfrage.
  Das ist eine **Inaktivitätsfrist**: Sie erneuert sich mit jedem Klick.
- `keyguard.js` setzte seinen Zeitstempel nur beim **Entpacken**. Der Treffer
  im Zwischenspeicher gab den Schlüssel zurück, ohne ihn anzufassen. Das war
  eine **absolute Frist ab dem Entsperren**.

`contentKey()` erneuert den Zeitstempel jetzt bei jedem Treffer.

**Und jetzt der unangenehme Teil: Der R44-Eintrag hat die Folge falsch
beschrieben, und dieser Changelog-Eintrag hätte es fast wiederholt.** R44 sagt,
der Entsperrdialog erscheine deshalb mitten in der Arbeit. Das trifft nicht zu
— berichtigt im Rahmenplan-Archiv am 01.09.2026, hier am Code nachgelesen:
`verwerfeInhalt()` räumt `pck`, `pckb` und `pckt`, lässt den **Datenschlüssel
`edk` aber bewusst liegen**, und `EdCrypto.getContentKey()` entpackt den
Inhaltsschlüssel eine Zeile weiter unten **ohne Passwort** daraus neu. Der
Fristablauf kostete ein **stilles Neu-Entpacken**, keinen Dialog.

**Was er kostete, ist trotzdem zählbar** — und zwar mit dem neuen Prüfmittel
`tools/fristprobe/`: Acht Stunden Dienst, alle fünf Minuten eine Seite
aufgerufen, 97 Aufrufe ohne eine einzige Pause. **Vorher 17 Neu-Entpackungen,
nachher 1.** Die Gegenprobe im selben Lauf zeigt, dass die Frist weiterhin
greift: Leerlauf über 30 Minuten hinaus kostet in beiden Fassungen ein
Entpacken.

**Die Angleichung ist damit Aufräumen und kein Heilmittel.** Sie bleibt
richtig — zwei Uhren, die dieselbe Zahl tragen und Verschiedenes messen, sind
eine Falle für den nächsten, der sich auf den Kommentar verlässt — aber sie
verspricht nichts, was sie nicht hält. Die Gegenrichtung (die Sitzung ebenfalls
absolut befristen) hieße, aktive NutzerInnen mitten in der Arbeit abzumelden.

**Der Dialog kommt woanders her, und das steht jetzt im Handbuch:**
`sessionStorage` gilt je **Tab**. Wer die Anwendung in einem zweiten Tab
öffnet, hat dort keinen Datenschlüssel und bekommt den Dialog, obwohl die
Sitzung läuft. Dasselbe nach einem Browser-Neustart und nach einem
Passwort-Reset ohne Wiederherstellungsschlüssel. Das ist so gewollt — der
Schlüssel soll nicht über Tabs wandern — und war bislang nirgends erklärt,
sodass es wie ein Fehler aussah.

**Die in R44 vorgeschriebene Abnahme belegt das nicht.** „Eine Sitzung über 30
Minuten mit Bedienung bringt keinen Dialog" ist vor und nach der Änderung
grün. Deshalb die Fristprobe: Ein Prüfmittel, das in beiden Zuständen dasselbe
sagt, misst nicht die Änderung.

### Werkzeug — Zwei neue Prüfmittel

`tools/geraeteprobe/` hält das Auslesen des Blocks gegen das, was wirklich
ankommt: beide Geräteformen, die Abwehr von Unsinn, die Beschriftungen der
Geräteliste, die gekürzte Kennung. **39 Erwartungen, 0 Abweichungen.** Ohne
Datenbank, ohne Webserver, ohne Gerät — und mit einer eigenen kleinen
Modelltabelle, damit sie nicht davon abhängt, welche Geräte gerade ausgeliefert
sind.

`tools/fristprobe/` belegt R44 mit der Zahl, die die vorgeschriebene Abnahme
nicht liefern kann. Sie stellt die Uhr vor, statt zu warten, und spielt acht
Stunden Dienst durch: **97 Aufrufe, vorher 17 Neu-Entpackungen, nachher 1** —
bei gleichbleibender Gegenprobe, dass die Frist weiterhin greift. Die Fassung
von vorher steht wortgleich in der Probe, bis auf die eine fehlende Zeile.

`tools/geraetemodelle/` erzeugt die Modelltabelle und löst mit
`nachaufloesen.php` bestehende Zeilen nachträglich auf. Es braucht die
Gerätedateien der Uhr-Plattform; die liefert nur der SDK-Manager aus, und die
Adresse der Bereitstellung steht bewusst nicht im Repositorium (dieselbe
Begründung wie beim Uhr-Prüfstand).

## [Web 12.8.0] — 2026-09-02

**Die Android-App wird über die Anwendung selbst verteilt.** Halbes viertes
Paket von S4 Block A — der QR-Kopplungscode aus derselben Karte fehlt noch,
er hängt an S5 und R42. Keine Migration.

### Web — Die Karte „NAdoku für Android"

Auf dem Geräte-Reiter, unter der Geräteliste. Sie zeigt, was in
`server/apk/` **liegt**: Dateiname, Größe, Fassung (aus dem Dateinamen),
Datum und den vom Server gerechneten **SHA-256** in Vierergruppen — 64 Zeichen
am Stück sind nicht vergleichbar, wer nachrechnet verliert beim dritten
Blockwechsel die Stelle.

**Von Hand gepflegt wird nichts.** Eine Versionsangabe, die jemand eintippt,
stimmt am Tag des Eintippens und danach nie wieder. Die Prüfsumme wird bei
jedem Aufruf neu gerechnet (bei 7 MB wenige Millisekunden) — ein
zwischengespeicherter Wert wäre genau die Zahl, die nach einem Austausch der
Datei noch die alte nennt, und bei einer Prüfsumme ist das schlimmer als
keine.

**Liegt nichts, erscheint die Karte gar nicht.** Ein Leerzustand „noch keine
App" wäre auf jeder Installation zu sehen, die keine Android-App verteilt,
und sagte dort etwas Falsches.

Der Download ist eine **neutrale** Handlung, kein Primärknopf: Die eine
Haupthandlung des Reiters bleibt „Kopplungscode erzeugen".

### Web — Weder im Repositorium noch im Deploy

`server/apk/` steht in `.gitignore` **und** in der Ausnahmeliste des Deploys.
Beides ist nötig, und der zweite Eintrag ist der, den man vergisst: Die
Deploy-Action synchronisiert `server/` und **entfernt, was nicht ausgenommen
ist** — der nächste Push löschte die Dateien. Dasselbe Muster wie
`config.php` und `sicherungen/`, inklusive der doppelten Schreibweise
(`apk/**` und `apk/`), weil die Action Datei- und Verzeichnismuster getrennt
prüft.

Ein signiertes APK ist ein Erzeugnis, kein Quelltext; eingecheckt wäre es bei
jeder Fassung ein zweistelliges MB im Verlauf. Hochgeladen wird per FTPS
durch die Betreiberin.

### Web — Der Name wird nicht geprüft, sondern gesucht

`apk.php` liest den Ordner und wählt aus dem Gelesenen aus. Ein Pfad, den der
Aufrufer zusammensetzt, kommt damit **nie** an `fopen()` — auch keiner mit
`..`, keiner mit einem Nullbyte und keiner mit einem Zeilenumbruch für die
`Content-Disposition`-Kopfzeile. Der Unterschied zu „gefährliche Zeichen
entfernen" ist, dass hier nichts vergessen werden kann.

Der Abruf liegt **neben** den Seiten und nicht unter `api/`, aus demselben
Grund wie `gpx.php`: `ist_api_aufruf()` entscheidet am Pfad, und ein
`<a href>`, den jemand anklickt, bekäme dort nach einer Mittagspause
`{"error":"session_ende"}` im Browserfenster statt der Anmeldeseite.

### Web — Nachweis

**Im Browser: 10 Erwartungen, alle erfüllt** (gegen eine 7-MB-Attrappe).
Karte vorhanden, Datei mit **7,0 MB · Fassung 1.0.0 · Stand** genannt,
Prüfsumme in **16 Vierergruppen**, Download neutral (`knopf-neutral`), Datei
kommt in **7 340 032 Byte** an, `?d=../config.php` läuft ins Leere, eine
unbekannte Datei bekommt eine Seite statt eines leeren 404. Keine
unerwarteten Konsolenfehler.

`git check-ignore` bestätigt die `.gitignore`-Wirkung
(`.gitignore:28:server/apk/`).

**Nicht geprüft:** Die Deploy-Ausnahme ist am Workflow-Text abgeleitet, nicht
durchgespielt — ein Trockenlauf bräuchte FTP-Zugangsdaten. Das Muster ist
zeichengenau das von `sicherungen/`, das im Betrieb steht. Und es gab kein
echtes APK: geprüft wurde gegen eine Attrappe aus 7 MB Füllbytes.

## [Web 12.7.0] — 2026-09-02

**GPX kommt jetzt auch herein.** Drittes Paket von S4 Block A. Keine
Migration.

### Web — Das Gegenstück zum Abruf

Seit Web 10.3.0 gehen Spuren als GPX hinaus (S2/AP4). Der Weg zurück fehlte:
Eine Aufzeichnung, die auf einem anderen Gerät entstanden ist — ein
Wanderuhr-Track, ein Export aus einer Leitstellensoftware, die eigene
Sicherung —, kam nicht in die Anwendung.

Über **„···" → „GPX importieren"** in der Tagesansicht, als Dialog: Datei
wählen, Ziel wählen, fertig. Der Eintrag steht **neben** „Spuren als GPX" —
hinaus und herein sind dieselbe Sache in zwei Richtungen, und wer den einen
Weg sucht, sucht dort auch den anderen.

**Zwei Ziele, und die Wahl ist keine Kosmetik** (E-R45-4):

| Ziel | wofür |
|---|---|
| **Ruhesegment** | Die Datei ist die Aufzeichnung eines ganzen Dienstes. Die Einsätze schneidet man danach heraus (Web 12.6.0). Der Regelfall. |
| **Einsatz** | Die Datei *ist* genau ein Einsatz. Die Phasenzeiten trägt man danach im Formular nach. |

### Web — `time` ist Pflicht, und die Ablehnung ist der Punkt

Eine GPX-Datei ohne Zeitstempel wird **abgelehnt**, nicht still angenommen.
Ohne `<time>` gibt es keine Punktreihenfolge, kein Schneiden und keine
Phasenzeiten — eine Spur, an der die halbe Anwendung nicht arbeiten kann.

Die Meldung nennt den Grund und die Zahl: *„Kein einziger der 2 Punkte hat
einen Zeitstempel. Ohne `<time>` gibt es keine Reihenfolge, kein Schneiden
und keine Phasenzeiten — die Datei wird deshalb nicht angenommen."* Eine
Meldung wie „Import fehlgeschlagen" ließe jemanden dreimal dieselbe Datei
wählen, ohne je zu erfahren, woran es liegt.

Ebenso begründet abgelehnt: kaputtes XML (mit der Fehlerstelle des Parsers),
ein falsches Wurzelelement, eine Datei ohne Trackpunkte (Wegpunkte und Routen
liest dieser Import nicht), mehr als 50 000 Punkte (die Meldung nennt die
Grenze *und* rechnet sie in Stunden um) und mehr als 12 MB.

**Und eine Dokumenttyp-Deklaration.** Eine GPX-Datei braucht keinen
`<!DOCTYPE>`; wer einen mitschickt, bekommt eine Absage statt einer Auslegung.
Das ist die Abwehr gegen XXE, und sie steht **vor** dem Parser:
`libxml_disable_entity_loader()` gibt es seit PHP 8 nicht mehr, externe
Entitäten lädt libxml seither von sich aus nicht — aber *interne* expandiert
es weiterhin, und daraus baut man eine Entitätenbombe ohne eine einzige
externe Referenz.

### Web — Toleranz, wo sie richtig ist

Angenommen wird auch **GPX 1.0** und eine Datei **ohne Namensraum**: Die
Elemente, um die es geht (`trk`, `trkseg`, `trkpt`, `ele`, `time`), heißen in
beiden Fassungen gleich und bedeuten dasselbe. Auf 1.1 zu bestehen hieße,
Dateien abzulehnen, die inhaltlich in Ordnung sind.

Mehrere `<trkseg>` oder `<trk>` werden zu **einer** Spur zusammengeführt und
**nach Zeit sortiert** — die Dateireihenfolge muss nicht die zeitliche sein.
Einzelne unbrauchbare Punkte (Koordinate außerhalb, Zeit unlesbar) fallen
heraus, ohne die Datei zu verwerfen; ihre Zahl steht in der Rückmeldung.

### Web — Der Leser wohnt beim Schreiber

`gpx_lesen()` steht in `gpx_lib.php`, direkt unter `gpx_bauen()`. GPX hat
damit **genau eine Stelle** in dieser Anwendung, die es kennt. Ein Leser, der
woanders wohnt, läuft früher oder später mit anderen Annahmen als der
Schreiber — und das fällt erst auf, wenn eine Datei durch den einen Weg
hinaus und den anderen nicht wieder hinein kommt.

**Gelesen wird auf dem Server, nicht im Browser** — anders als beim
CSV-Import. Der Unterschied ist der Inhalt: Beim CSV stehen Patientendaten in
der Datei, die der Server nie sehen darf, also *muss* der Browser lesen. Eine
GPX-Datei enthält nichts Verschlüsseltes. Und die Ablehnungsregeln sind
verbindlich; eine verbindliche Regel im Browser ist keine.

Die Datei kommt als Zeichenkette im JSON-Körper, nicht als Dateiupload.
Diese Anwendung hat nirgends ein `$_FILES`; ein erster Upload-Weg brächte
`upload_max_filesize`, `post_max_size`, temporäre Verzeichnisse und deren
Rechte mit — vier Stellschrauben auf geteiltem Hosting für einen Vorgang, den
eine Zeichenkette genauso trägt.

### Web — Der Fund: ein leerer String statt eines Fehlers

Nach `children($ns)` schaltet SimpleXML die Namensraum-Umgebung eines Knotens
um — **auch für Attribute**. `$pt['lat']` sucht danach ein `lat` *im
GPX-Namensraum*, und ein unpräfigiertes Attribut liegt in **keinem**
(XML-Namens-Spezifikation 6.2).

Das Ergebnis war kein Fehler, sondern ein leerer String: Die Datei wurde
gelesen, jeder Punkt fiel durch die Koordinatenprüfung, und die Meldung
lautete *„enthält keinen einzigen Trackpunkt"* — bei 61 vorhandenen. Genau
die Art Fehler, die eine Prüfung braucht, um aufzufallen. Behoben über
`attributes()`; im Code steht die Begründung, damit die nächste Fassung nicht
zurückfällt.

### Web — Nachweis

**Der Leser: 17 Erwartungen, alle erfüllt.** Darunter der Rundlauf über den
Schreiber (61 Punkte hinaus, 61 zurück, **0 Abweichungen**), sieben
Ablehnungsfälle mit Prüfung der Meldung, GPX 1.0 und Namensraum-freie
Dateien, zeitliche Sortierung über zwei Segmente hinweg. **9 000 Punkte
(24-h-Dienst in 10-s-Auflösung, 0,78 MB) in 0,13 s.**

**Im Browser: 17 Erwartungen, alle erfüllt.** Import als Ruhesegment (6 → 7
Segmente, 54 Punkte) und als Einsatz (4 → 5 Einsätze), beide Ablehnungsfälle
mit sichtbarer Begründung im Dialog, keine unerwarteten Konsolenfehler.

**Der Rundlauf, den die Abnahme verlangt: 12 Erwartungen, alle erfüllt.**
Importierte Spur → GPX-Abruf → erneut gelesen: **54 Punkte, 0 Abweichungen**
gegen die Quelldatei, für Segment *und* Einsatz. Beide liegen als
verlustfreier Blob mit `n_original = 54`; der Einsatz trägt
`origin = import`, `manual = 1` und die `imp-`-Kennung, und der Diensttag
umschließt die Spur.

## [Web 12.6.0] — 2026-09-02

**Der vergessene Einsatz lässt sich jetzt aus dem Ruhesegment
herausschneiden.** Zweites Paket von S4 Block A: die Bedienung. Keine
Migration — die Tabelle steht seit Web 12.5.0.

### Web — Die Karte „Ruhesegmente"

Bis hierher lagen die Ruhesegmente **nur als graue Linie auf der Karte** und
waren nicht anfassbar. Dabei ist genau dort die Spur eines Einsatzes, bei dem
niemand einen Knopf gedrückt hat: Das Gerät hat durchgezeichnet, nur eben als
Ruhezeit.

Die Tagesansicht führt sie deshalb jetzt als eigene Karte, mit Zeitraum,
Dauer und Punktzahl. Wo Punkte da sind, steht **„Schneiden"** — wo keine
sind, nicht: Ein Segment ohne Spur hat nichts, was wandern könnte, und der
Knopf wäre ein Versprechen, das der Endpunkt nicht halten kann.

### Web — Der Schneide-Bereich

An der Zeile klappt er auf: **Zeitleiste**, Beginn und Ende (Pflicht), drei
Phasenzeiten (optional — Ausrücken, Ankunft Einsatzort, Ankunft Klinik). Die
vollständige Phasenliste hat ihr Zuhause im Einsatzformular; hier stehen die
drei, die man beim Schneiden ohnehin weiß.

**Vorbelegt ist das ganze Segment**, nicht ein geratener Ausschnitt. „Die
mittlere Stunde" wäre eine Behauptung über etwas, das nur die Bedienerin
weiß; das ganze Segment ist die ehrliche Vorbelegung — sichtbar falsch und in
zwei Feldern berichtigt.

**Die Zeitleiste ist eine Anzeige, keine Bedienung.** Kein Ziehen an den
Griffen: Das wäre eine zweite Eingabe für dieselbe Zahl — die eine auf die
Minute genau, die andere auf das Pixel —, und die beiden liefen auseinander.
Sie zeigt außerdem, was **früher schon** herausgeschnitten wurde; ohne diese
Fläche sähe man eine Lücke ohne Erklärung und schnitte ein zweites Mal an
derselben Stelle.

Unter den Feldern steht in Worten, was geschieht: welchen Zeitraum der
Einsatz bekommt, welche Reste als Ruhesegment stehen bleiben, und dass sich
der Schnitt rückgängig machen lässt.

### Web — Was der Endpunkt tut

`api/schneiden.php` legt den Einsatz auf dem **Bestandsweg** an: virtuelles
Gerät `manual-<userId>`, `origin = 'manual'`, `manual = 1`, `client_ref` mit
Präfix — wörtlich wie `einsatz_form.php`. Daran hängt, ob der Einsatz durch
Sicherung, Export und Papierkorb kommt.

Alles in **einer** Transaktion: Einsatz anlegen, Phasen schreiben, Punkte
verschieben, Sperrvermerk setzen, Diensttag-Zeitraum fortschreiben. Bricht
etwas ab, steht nichts davon.

**Einsatzort, Alter und Diagnose füllt er nicht.** Die sind
Ende-zu-Ende-verschlüsselt und entstehen im Browser; ein Endpunkt, der sie
annähme, bräuchte Klartext. Der geschnittene Einsatz ist ein leerer Einsatz
mit Zeiten, Phasen und Spur — den Rest trägt man im Formular nach.

**Ein Schnitt ohne Punkte wird abgelehnt.** Er entstünde beim zweiten Schnitt
über denselben Bereich oder über eine Aufzeichnungslücke. Herauskäme ein
leerer Einsatz — und einer, den das Rückgängig nicht anfassen kann: Ohne
gewanderte Punkte gibt es keinen Sperrvermerk, und ohne Vermerk findet es den
Weg zurück nicht. Also nichts anlegen und sagen, warum.

### Web — Rückgängig

Am Segment, mit der Plakette, die den Schnitt nennt. Es ist derselbe Aufruf
mit vertauschten Enden: Die Punkte wandern zurück, der Sperrvermerk fällt,
der Einsatz wird gelöscht.

**Was am Einsatz hängt, hält es auf.** Wer inzwischen Einsatzort, Diagnose
oder eine Reanimation eingetragen hat, verlöre das — auf einen Knopf, der
„rückgängig" heißt und nach der harmlosesten Handlung der Seite klingt. Ein
Einsatz mit Inhalt geht stattdessen über den Papierkorb, wo die Frist von
30 Tagen läuft.

*Abweichung vom freigegebenen Mockup, benannt:* Der Vorschlag sah das
Rückgängig als Zeilenaktion am **Einsatz** vor. Die Einsatzliste ist aber
eine sortierbare Tabelle mit Spaltenkatalog und hat keine Zeilenaktionen;
eine Aktionsspalte träfe alle drei Einsatztabellen und wäre eine neue
Darstellung. Am Segment ist die Handlung außerdem vollständig — dort steht,
was fehlt, und dorthin kommen die Punkte zurück.

### Web — Zwei Funde aus der Browserprüfung

Beide von derselben Art: **etwas, das auf einem Rechner richtig aussieht.**

**Die Zeiten gingen roh hinaus.** `api/day.php` lieferte `started_at` als
UTC-Zeichenkette, und der Browser rechnete mit `new Date(…)` in *seine* Zone
um. Auf einem Rechner in der Zone der Anwendung fällt das nie auf; im
Prüfcontainer ist sie UTC, und der Schnitt griff zwei Stunden daneben und
nahm **null Punkte** mit — mit Erfolgsmeldung. Jetzt geht `start_hhmm` fertig
formatiert hinaus, wie es die Einsatztabelle seit jeher bekommt; der Browser
rechnet nur noch in Minuten. Das ist die Linie der ganzen Anwendung, und
diese Datei war die eine Stelle, die sie verlassen hatte.

**Die Zeitleiste war ein SVG.** Ein `viewBox` skaliert seine Beschriftung mit
der Breite: auf 1280 px stand die Uhrzeit richtig, auf 390 px war sie **sechs
Pixel** hoch — dieselbe Zahl in zwei Größen, je nach Fenster, und die
Schriftskala galt für sie nicht mehr. Jetzt HTML mit Prozentbreiten: Der Text
bleibt Text, nur der Balken skaliert.

### Web — Nachweis

**Im Browser (Chromium, lokale Installation): 28 Erwartungen, alle erfüllt.**
Geschnitten, zurückgenommen, Grenzfälle durchgespielt — Unsinn im Feld,
Zeiten außerhalb des Segments, Ende vor Beginn, zweiter Schnitt über
denselben Bereich. **Keine unerwarteten Konsolenfehler** (die eine 409 ist
die Ablehnung des Leerschnitts, die der Browser protokolliert).

Die Kernzahlen des Rundlaufs: Segment 61 → 48 Punkte, Einsatzliste 3 → 4,
Sperrvermerk 07:01–07:03 sichtbar; nach dem Rückgängig 48 → 61, Einsatzliste
wieder 3, Vermerk weg. **390 px: waagerechter Überlauf 0, alle Bedienelemente
44 px.**

Die Logik des Endpunkts zusätzlich ohne HTTP: 17 Erwartungen, alle erfüllt —
darunter, dass eine Phase außerhalb des Schnitts abgelehnt wird und der
Diensttag den Einsatz umschließt.

## [Web 12.5.0] — 2026-09-02

**Ein Schnitt, der sich nicht von selbst wieder auflöst.** Erstes Paket von
S4 Block A: das Fundament des Schneidewerkzeugs.
**Migration erforderlich** (`2026_09_02_schnitte`) — nach dem Deploy muss
eine Administratorin `update.php` aufrufen.

### Web — Was das Schneiden ist und warum es einen zweiten Boden braucht

Wer einen vergessenen Einsatz nachträgt, hat sein Problem nicht mit dem
Formular gelöst: Die Spur des Einsatzes liegt im **Ruhesegment**, in dem
das Gerät zu der Zeit aufgezeichnet hat. `spur_teilen()` schneidet sie dort
heraus — der gewählte Zeitbereich wandert samt Punkten zum Einsatz.

**Die Punkte wandern, sie werden nicht kopiert** (E-S4-53). Kopieren wäre
einfacher gewesen und ist verworfen: Die Punkte lägen doppelt (bei rund
9 500 behaltenen Punkten je Zwölf-Stunden-Dienst spürbar), und das
Ruhesegment behielte die Einsatzfahrt in sich. Wer es später ansieht, sähe
eine Ruhezeit, in der jemand 40 km gefahren ist. Der Schnitt soll trennen.

**Damit fehlt aber etwas, das nachkommen kann.** Das Gerät weiß vom Schnitt
nichts. Hatte es die Punkte des geschnittenen Zeitraums noch im Puffer —
ein Funkloch reicht —, liefert es sie nach, und sie fänden in das Segment
zurück, aus dem sie eben genommen wurden. Der Schnitt löste sich still
wieder auf.

### Web — Warum `n_original` das nicht auffängt

Das war die erste Annahme dieses Pakets, und sie war falsch. Sie klingt
plausibel: `spur_lesen_viele()` übergeht seit Web 10.0.0 jede Zeile mit
`seq < n_original`, und der Schnitt könnte diese Grenze einfach hochsetzen.

`ingest.php` vergibt die Sequenznummern aber aus `seq_from` — der Marke,
die das Gerät zuletzt zurückbekommen hat. **Gepufferte Punkte kommen
deshalb oberhalb jeder Sperrgrenze an** und laufen glatt daran vorbei;
`n_original` fängt nur die *Wiederholung* schon gelieferter Punkte ab. Was
die Nachzügler kenntlich macht, ist ihre `ts` — und die bringen sie selbst
mit.

Deshalb hält der neue Sperrvermerk in `track_cuts` einen **Zeitraum** und
nicht, wie das Konzept es in Abschnitt 14 vorsah, einen Sequenzbereich: Den
gibt es beim Schnitt noch gar nicht, weil es die betreffenden Punkte noch
nicht gibt. Der Zeitraum steht dagegen fest, sobald die Bedienerin ihn
gewählt hat.

**Beide Böden bleiben nötig, und sie tun Verschiedenes:**

| | fängt ab | wäre sonst die Folge |
|---|---|---|
| `n_original` (Blob) | Wiederholung bereits gelieferter Punkte; hält die Fortsetzungsmarke | Das Gerät sendet den ganzen Dienst noch einmal — der Schnitt löscht Zeilen, und die Marke fiele mit ihnen zurück |
| `track_cuts` (Zeitraum) | Nachlieferung aus dem Puffer | Die geschnittenen Punkte kehren ins Segment zurück |

`ingest.php` liest die Vermerke **einmal je Upload**, nicht einmal je Punkt
— es ist der heißeste Schreibweg der Anwendung. Verworfene Punkte werden
genannt (`cut_points` in der Antwort), aber **quittiert**: Sonst liefert das
Gerät endlos nach, dieselbe Regel wie bei der Sperrliste `deleted_refs`.
Eine Vertragsänderung ist das nicht — der Client muss damit nichts tun.

### Web — Was beim Bauen auffiel

**Ein Schnitt darf die ganze Spur nehmen.** Bleibt dabei kein Punkt übrig,
schreibt `spur_teilen()` trotzdem einen Blob zurück — einen leeren, 21 Byte.
Ohne ihn fände `spur_naechste_seq()` weder Zeile noch Blob und antwortete 0.
Der Fall ist nicht selten: Wer einen Einsatz aus einem kurzen Segment
schneidet, nimmt häufig alles.

**Das Ziel wird ergänzt, nicht überschrieben.** Beim Schneiden selbst ist es
ein frisch angelegter Einsatz und leer; beim **Rückgängig** (E-S4-17) ist es
das Ruhesegment, das seit dem Schnitt weitergelaufen ist. Ein Blob-Schreiben,
das ersetzt statt zu mischen, hätte dessen neue Punkte gelöscht — ohne
Fehlermeldung. Das Rückgängig hebt den Sperrvermerk mit auf, sonst bliebe
ein Loch, das niemand mehr füllen kann.

**Die Löschwege räumen die Vermerke mit ab.** Sie hängen an keinem
Fremdschlüssel — wie `track_points` und `track_blobs`, aus demselben Grund
(polymorph) und mit demselben Preis. Papierkorb, Kontolöschung und der
Waisenjob tun es jetzt ausdrücklich; ein Vermerk nennt einen Zeitraum, in
dem sich jemand aufgehalten hat, und das ist ein Ortsdatum.

### Web — Nachweis

`tools/spurprobe/probe.php` hat einen **Teil 6** bekommen: Schnitt, dann
Nachlieferung, dann Rückgängig, auf einer eigens angelegten Kulisse in einer
zurückgerollten Transaktion. Der Bestand liefert diesen Fall nicht — er
braucht eine Spur, die beim Schnitt absichtlich nur zur Hälfte geliefert ist.

**20 Erwartungen, alle erfüllt.** Die Kernzahlen: von 250 nachgelieferten
Punkten werden **50 gesperrt und 200 angenommen**; das Segment trägt danach
500 Punkte (Ruhe vor *und* nach dem Einsatz), der Einsatz unverändert 50.
Nach dem Rückgängig sind es 550 im Segment und 0 im Einsatz, ohne einen
einzigen zeitlichen Rücksprung in der vereinigten Spur.

Die Bedienung — Schneide-Bereich in der Tagesansicht, `api/`-Endpunkt,
Rückgängig-Aktion — folgt in einem eigenen Paket.

## [Web 12.4.2] — 2026-09-02

**Das Bodenlogo war nie so klein, wie es aussah — es war gepolstert.** Elftes
Arbeitspaket von S3. Keine Migration.

### Web — Ein Zehntel der Höhe war leer

Das NEF-Logo wirkte neben dem Hubschrauber deutlich kleiner. Die Ursache
steckte in der Datei: `viewBox="0 0 420 420"`, die **Zeichnung darin aber
420 × 335 ab y = 42,5** — oben und unten je ein Zehntel leer, ein Artefakt
des Exports und keine Gestaltungsentscheidung.

Skaliert wird über die **Höhe** (`height: 34px; width: auto`). Ein Zehntel
dieser Höhe war damit Luft, und das Bild erschien **schmaler und niedriger
zugleich**:

| bei 34 px Höhe | vorher | nachher |
|---|---|---|
| Luftlogo | 54,5 × 34 px · 1 853 px² | 54,5 × 34 px · 1 853 px² |
| Bodenlogo | 34 × 27,1 px · **921 px²** | **42,6 × 34 px · 1 449 px²** |
| Flächenverhältnis | **2,01** | **1,28** |

**Eine Feinkorrektur braucht es danach nicht** (E-S3-12 b, am Bild
entschieden): Die Höhen sind gleich, und das ist es, was das Auge in einer
Zeile vergleicht. Die verbleibende Breitendifferenz ist der ehrliche
Unterschied zweier Motive — das eine liegt quer, das andere weniger. **An der Zeichnung ist nichts geändert, nur am Rahmen.**

### Web — Die Kopfleiste reservierte für beide Logos denselben Kasten

`width="54" height="34"` stand fest im Markup. 54 : 34 ist das Verhältnis des
**Luftlogos**; das Bodenlogo ist bei 34 px Höhe **43 px** breit. Der Browser
reservierte also einen Kasten, in den das Bild nicht passt, und rückte beim
Laden nach — ein Layoutsprung, den man nur sieht, wenn man darauf wartet. Die
Maße kommen jetzt aus `ui_logo_masse()`, je nach gewähltem Logo.

### Web — Eine Falle am Luftlogo, entschärft

Ein blauer Streifen der Zeichnung läuft bis x = 556,7 und damit rund **156
Einheiten über den Rahmen** (400,16) hinaus. Sichtbar war er nie — ein Clip
im Inneren schneidet ihn weg. Sichtbar **würde** er, sobald jemand den Rahmen
weitet, und daran denkt beim Weiten niemand. Ein zusätzlicher Rahmen-Clip
hält ihn jetzt unabhängig davon drinnen; am Bild ändert sich nichts, geprüft
durch Vergleich der abgeleiteten Favicons: **bitgleich**.

### Web — Favicons und Uhr-Kacheln neu abgeleitet

Beide Favicons sind neu erzeugt. Der **Inhalt** des Luftlogo-Favicons ist aus
alter und neuer SVG **identisch**; das Bodenlogo-Favicon ändert sich um 593
Bildpunkte — der Beschnitt. Dass beide Dateien trotzdem neue Bytes haben,
liegt am neueren Chromium, mit dem sie gerastert wurden.

Von den **17** Uhr-Bildern ändern sich genau die **vier `logo_boden.png`**;
die dreizehn aus dem Luftlogo abgeleiteten sind **bildgleich** und bleiben
unangetastet (ihre Bytes unterschieden sich nur im Zeitstempel-Block des
PNG). Der 78-%-Faktor für das NEF bleibt richtig — er war schon vorher das
Verhältnis der beiden Seitenverhältnisse, und daran ändert der Beschnitt
nichts. **Ausgeliefert werden die Kacheln mit der nächsten Uhr-Fassung
(S5, E-S3-04); die Uhr-Version steigt hier nicht.**

### Web — Ein Werkzeug, das bei einem Fehlschlag eine Datei schrieb

Beim ersten Anlauf enthielt ein neuer XML-Kommentar in den SVG einen
doppelten Bindestrich — **XML verbietet das**. Die Dateien waren damit
ungültig, der Browser zeigte sein Platzhalterbild („kaputtes Bild"), und
`tools/logos/erzeugen.mjs` fotografierte es, schrieb es als Favicon und
meldete zufrieden „64 × 64, 865 Bytes". Das Werkzeug sieht jetzt nach, ob das
Bild überhaupt geladen hat, und **bricht ab, ohne etwas zu schreiben**.

## [Web 12.4.1] — 2026-09-02

**Das Demo-Konto lässt sich auf der Kontoseite nicht mehr ändern.** Zehntes
Arbeitspaket von S3. **Funktionsänderung.** Keine Migration.

### Web — Änderungen am Demo-Konto verfielen lautlos

Das Demo-Konto wird zentral verwaltet: angelegt, zurückgesetzt und entfernt
über den Reiter **Demo-Konto**. Auf seiner Kontoseite standen trotzdem alle
Formulare der übrigen Konten — Name, Rolle, E-Mail, Sichern, Einspielen,
Freigeben, Löschen. Was man dort eintrug, war **spätestens nach dreißig
Minuten wieder weg**: Der Reset überschreibt Konto- und Schlüsselmaterial und
löscht den ganzen Bestand. Eine Änderung, die lautlos verfällt, ist schlimmer
als eine, die gar nicht erst geht.

**Gesichert wird das Konto ebenfalls nicht.** Sein Bestand ist erfunden, liegt
als Fixture im Repositorium und wird alle dreißig Minuten daraus neu
hergestellt — eine Sicherung davon wäre die Kopie einer Datei, die ohnehin im
Git liegt. Die Karte „Sicherungen" entfällt für dieses Konto.

### Web — Die Sperre sitzt im Schreibweg, nicht im Markup

Ein `disabled` am Formular ist **Kulisse**: Ein direkt abgesetzter POST geht
daran vorbei. Sieben Aktionen werden deshalb **serverseitig** abgewiesen —
`konto`, `sichern`, `einspielen`, `freigeben`, `widerrufen`,
`paket_loeschen`, `user_delete` — mit einer Meldung, die sagt, wo es
stattdessen geht. Die Anzeige graut zusätzlich aus, damit man es sieht, bevor
man es versucht.

Geprüft mit **direkten POSTs** an der Sitzung vorbei: alle drei getesteten
Aktionen abgewiesen, der Name des Demo-Kontos danach unverändert. Die
Gegenprobe an einem normalen Konto ging durch.

**Nicht gesperrt sind die Geräte-Aktionen.** Das Demo-Konto lädt ausdrücklich
zum Koppeln einer Uhr ein („Ausprobieren ist erwünscht"), und was dabei
entsteht, räumt der Reset selbst wieder ab.

### Web — „Demo NutzerIn"

Der Anzeigename kam aus der Fixture und war der Name des Referenzkontos, aus
dem sie erzeugt wurde; in der NutzerInnen-Liste las sich das wie ein
gewöhnliches Konto. Er heißt jetzt **„Demo NutzerIn"** — gesetzt beim Anlegen
**und** beim Zurücksetzen, sonst holte der nächste Reset den alten zurück.

## [Web 12.4.0] — 2026-09-01

**Filter erscheinen nur, wenn im Bestand etwas dahintersteht.** Neuntes
Arbeitspaket von S3. **Funktionsänderung.** Keine Migration, keine
Schnittstellenänderung.

### Web — Bisher galt die Regel für zwei Fälle, und sie stand zweimal im Code

Seit Web 5.10.0 verschwand der Block **Bergrettung**, wenn niemand windet;
seit Web 7.0.0 auch das Einzelfeld **Fehleinsatz**. Beides stand als
handgepflegte Liste im Code — `GRUPPE_NUR_WENN` mit einer Bedingung je Block,
`FELD_NUR_WENN` mit einer je Feld.

Das ist genau der Einzelfall-Wildwuchs, den der Feldkatalog abschaffen sollte:
Jedes neue Feld hätte einen dritten Eintrag gebraucht, **und wer ihn vergisst,
merkt es nie** — ein dauerhaft leerer Filter sieht aus wie ein Filter.

### Web — Jetzt entsteht die Regel aus dem Katalog

Jeder Filter, der zu einer Spalte des Feldkatalogs gehört, trägt sie in seiner
Definition; `KATALOG_ART` — **aus `mission_fields.php` erzeugt**, samt
Unterfeldern — sagt, welcher Art sie ist. Die Art entscheidet, was „leer"
heißt: Bei einem **Haken** zählt nur `wahr`, bei einer **Auswahl** ist auch
die Null eine Angabe („0 Cycles" heißt, dass jemand hingesehen hat).

**Ein Filter ohne Spalte bleibt immer stehen** — Zeitraum, Uhrzeit, Wochentag,
Strecke, Dauer, Alter, Standort, Rettungsmittel, Art, Besatzung, weitere
Rettungsmittel. Auf einem frisch angelegten Konto stehen genau diese elf in
der Leiste, und die Blöcke **Transport** und **Bergrettung** fehlen ganz: Ein
Block verschwindet, wenn alle seine Filter verschwunden sind, und braucht
dafür keine eigene Bedingung mehr. `GRUPPE_NUR_WENN` und `FELD_NUR_WENN` sind
ersatzlos entfallen.

**Gemessen an zwei echten Konten:**

| | Blöcke | ausgeblendete Filter |
|---|---|---|
| Konto mit vollem Bestand | alle fünf sichtbar | **0** |
| frisches Konto ohne Einsatz | Transport und Bergrettung fehlen | **12** |
| frisches Konto, geteilter Link `#wi=j` | Bergrettung kommt zurück | **11** — genau der gesetzte Filter erscheint |

**Die Gegenprobe in die andere Richtung**, über die Oberfläche gefahren: Auf
dem leeren Konto einen Diensttag und einen Einsatz **mit Fehleinsatz**
angelegt — danach steht **genau** der Filter „Fehleinsatz" da, und alle
übrigen bleiben ausgeblendet.

### Web — Ohne zusätzliche Serverabfrage

Der gesamte Bestand liegt seit Web 5.10.0 ohnehin im Browser
(`api/suchindex.php`, **einmal** je Seitenaufruf, fünf SQL-Abfragen
unabhängig von der Zahl der Einsätze). Die Sichtbarkeit entsteht daraus in
**einem** Durchgang — nicht in einem je Filter. Gemessen **0,06 ms** bei 82
Einsätzen; der Aufwand wächst linear mit dem Bestand.

## [Web 12.3.3] — 2026-09-01

**Formularbausteine und Ortssuche.** Achtes Arbeitspaket von S3. Eine
**Funktionsänderung** (Autosuche im Ortsfeld) mit einer Folge, die benannt
gehört. Keine Migration.

### Web — Das Ortsfeld sucht beim Tippen

Bei **Standort** und **Zielklinik** suchte bis hierher nur die Lupe; O5 hatte
das ausdrücklich so entschieden, weil in diesen Feldern ein **Name** steht und
keine Adresse. Für einen Weg, den man zwanzigmal am Tag geht, ist ein Klick
eine Handlung zu viel. Beide Felder suchen jetzt beim Tippen — **die Lupe
bleibt** als ausdrücklicher Auslöser, und was ein Treffer übernimmt, bleibt
unterschieden (bei Standort und Zielklinik nur die Koordinaten, nie der Name).

Drei Grenzen fassen das ein, und sie stehen als benannte Konstanten oben in
`assets/ortsfeld.js` statt verstreut im Code:

| Grenze | Wert | wozu |
|---|---|---|
| Entprellung | **400 ms** | beim flüssigen Tippen entsteht **eine** Anfrage, nicht eine je Buchstabe |
| Mindestlänge | **3 Zeichen** | unter drei Zeichen sucht niemand ernsthaft |
| offene Anfragen | **höchstens eine** | eine laufende wird abgebrochen; sonst überholen sich zwei Antworten und die Liste zeigt den vorletzten Stand |

Nachgemessen mit abgefangenen Anfragen an allen drei Ortsfeldern (Standort,
Zielklinik, Einsatzort): flüssiges Tippen von „Talwang" (sieben Zeichen à
80 ms) ergibt **eine** Anfrage, zwei Zeichen ergeben **keine**, und die Lupe
löst nach **unter 50 ms** aus.

### Web — Und das ändert eine Zusage in `docs/Lizenzen.md`

Dort stand, die Adresssuche laufe „nicht bei jedem Tastendruck" **und nur auf
ausdrückliches Auslösen". Der erste Teil stimmt weiter, der zweite nicht mehr.
Der Abschnitt 6.2 ist deshalb neu geschrieben: Er sagt jetzt, dass das Tippen
in einem Ortsfeld Suchanfragen an Photon auslöst, nennt die drei Grenzen und
sagt, warum es sie gibt — Photon ist ein frei betriebener
Gemeinschaftsdienst, und jede Anfrage trägt die eingetippten Buchstaben zu
einem Dritten.

**Die Ende-zu-Ende-Verschlüsselung ist davon nicht berührt.** Gesucht wird,
bevor aus der Eingabe ein gespeicherter — und damit verschlüsselter — Wert
wird; das war beim Klick auf die Lupe so und ist es beim Tippen. Wer
Koordinaten von Hand einträgt, einen Plus Code einfügt oder den Ort auf der
Karte wählt, löst **keine** Anfrage aus.

### Web — Platzhalter tragen jetzt Phantasienamen

„z. B. Standort Kempten" bevorzugte einen realen Ort. Ein Platzhalter ist ein
**Beispiel**, kein Vorschlag: Ein Teil der NutzerInnen liest ihn als die
erwartete Antwort, ein anderer als Aussage darüber, wer diese Anwendung
betreibt. Beides ist falsch.

**Elf Stellen getauscht**, mit Namen aus der Welt des Referenzdatensatzes —
Talwang, Westried, Sonnenau, Alpenfalke:

| vorher | jetzt |
|---|---|
| z. B. Standort Kempten | z. B. Standort Talwang |
| z. B. Christoph 17 oder NEF Kempten 1 | z. B. Alpenfalke 1 oder NEF Talwang 76/1 |
| z. B. Klinikum Kempten | z. B. Klinikum Westried |
| z. B. RTW Kempten | z. B. RTW Talwang 76/85 |
| z. B. Bereitschaft Oberstdorf | z. B. Bergwacht Sonnenau |

Die Regel steht als Pflegeregel in `docs/Design.md` 9.7 und gilt ab jetzt für
jede neue Stelle. **Ein Nebeneffekt:** Die Wortliste brauchte für den
Rettungsmittel-Platzhalter eine Ausnahme, weil „Christoph 17" ein
Luftbegriff ist. Mit „Alpenfalke 1 oder NEF Talwang 76/1" ist der Platzhalter
von sich aus neutral — die Ausnahme ist ersatzlos entfallen (66 statt 67
Regeln).

### Web — Die Wahlliste ist eine Liste, keine Kartensammlung

Jede Zeile trug einen eigenen Rahmen auf eigener Fläche und stand mit 8 px
Abstand zur nächsten: vier Kästchen untereinander, die aussahen wie vier
Karten und eine Wahl sind. Rahmen, Fläche und Zwischenraum entfallen; die
Auswahl zeigt sich weiterhin am gezeichneten Punkt und an der hell orangen
Fläche der gewählten Zeile. Die Zeilenhöhe bleibt bei 44 px.

Gemessen an der Logo-Wahl: **248 → 224 px** bei unveränderter Zeilenhöhe. Die
Änderung sitzt am Baustein und trifft damit auch die beiden Wahllisten des
Diensttag-Zusammenführens.

## [Web 12.3.2] — 2026-09-01

**Der Markerversatz — der eine echte Fehler der Rückmeldungsliste.** Siebtes
Arbeitspaket von S3. Keine Migration, keine Schnittstellenänderung.

### Web — Warum die Marker beim Herauszoomen nach Osten wanderten

Die Schilder von Standort und Zielklinik saßen umso weiter östlich, je weiter
herausgezoomt wurde. Die Ursache ist eine **Kette aus drei Gliedern, von
denen jedes für sich richtig aussah**:

1. `.geo-schild` ist eine Flex-**Spalte** mit `align-items: center`. Ihr
   Wurzelelement wird damit so breit wie ihr **breitestes** Kind.
2. Das breiteste Kind war das **Namensschild** (`white-space: nowrap`) —
   nicht der 44-px-Kasten. Bei „Klinikum Immenstadt" rund 150 px.
3. `iconSize: null` lässt Leaflet die Größe aus dem Markup nehmen, und
   `iconAnchor: [22, 22]` verankerte damit 22 px vom linken Rand **des
   Wurzelelements** — also rund 50 px links der Kastenmitte.

Das ergibt einen **konstanten Pixelversatz**, und genau das machte ihn so
schwer zu fassen: Herausgezoomt sind dieselben 50 px Kilometer,
hereingezoomt Meter. Er wuchs mit der Länge des Namens.

**Nachgemessen, nicht angesehen** — Abstand zwischen dem Ankerpunkt der
Koordinate und der Mitte des gezeichneten Kastens, über sechs Zoomstufen:

| | vorher | nachher |
|---|---|---|
| Versatz waagerecht | **51,7 px** | **0,0 px** |
| Versatz senkrecht | −4,0 px | **0,0 px** |
| über sechs Zoomstufen | unverändert | unverändert |

### Web — `iconSize` steht jetzt überall ausdrücklich

Mit dem Wegfall der Namensschilder wäre der Versatz von selbst verschwunden.
**Die Behebung setzt `iconSize` trotzdem** — an allen fünf Markerarten
(Schild, Einsatzort, Ring, Punkt, Richtungspfeil). Wer dem Marker künftig
etwas danebenstellt, eine Plakette oder eine Zahl, trüge den Fehler sonst
wieder ein, und zwar wieder ohne Fehlermeldung.

Die Maße stehen dafür an zwei Stellen — als Zahl in `geo.js` (Leaflet braucht
sie so) und als Token im Stylesheet. Ein Kommentar an beiden sagt, dass sie
zusammengehören.

### Web — Die Schilder tragen keinen Namen mehr

Auf einer Karte mit mehreren Markern standen die Namensschilder übereinander
und verdeckten die Spuren. Das Schild ist jetzt nur noch das Symbol; der Name
steht im `title` und erscheint als Kurzinfo. Das Kästchen wird dabei enger —
**36 statt 44 px**, bei gleich großem Symbol: Was verschwindet, ist Weißraum
zwischen Symbol und Rand (12 px ringsum vorher, 8 px jetzt). Die 44 kamen von
`--knopf`, und der Kasten ist kein Bedienelement, sondern eine Zeichnung.

### Web — Kein Zielklinik-Schild mehr auf der Tagesübersicht

Die Karte der Tagesübersicht zeigt den ganzen Tag. Bei acht Einsätzen standen
dort acht Klinik-Schilder zwischen acht Spuren — und die Frage, die diese
Karte beantwortet, ist „wo war das Rettungsmittel unterwegs", nicht „welche
Kliniken gibt es". Das Transportziel steht weiterhin in der **Einsatzansicht**,
wo es zu einem Einsatz gehört. Am Bestand ändert sich nichts.

### Web — Der Einsatzort-Kreis verliert die weiße Umrandung

Sie war ein Rest aus der Zeit vor dem Schatten und sollte den Kreis von der
Karte abheben; das tut der Schatten. Der Rand machte den Kreis zusätzlich um
4 px breiter, als er aussah. Jetzt **32 statt 36 px**, ohne Rand.

## [Web 12.3.1] — 2026-09-01

**Die Einsatzansicht.** Sechstes Arbeitspaket von S3. Eine
Funktionsänderung (Höhenanzeige), sonst Anzeige. Keine Migration.

### Web — „706 m" sagte nicht, was 706 Meter sind

Unter dem Einsatzort steht eine Kleinzeile mit Höhe und Strecke. Die Strecke
trug ihr Wort („Strecke 40,9 km"), die Höhe nicht — dort stand nur „706 m",
direkt daneben. Jetzt **„Höhe 706 m"**.

**Angezeigt wird sie weiterhin nur bei luftgebundenen Diensttagen**
(`kind = 'air'`). Bodengebunden ist es die Höhe der Straße, und die ist keine
Auskunft; die Zeile entfällt dort **ersatzlos** — eine leere beschriftete
Zeile wäre derselbe Fehler in neu. Gespeichert und exportiert wird die Höhe
unverändert in beiden Fällen; bedingt ist nur die Anzeige.

### Web — Schutz wird jetzt zweimal angezeigt, und das ist Absicht

P3 hatte sich entschieden (F-N1-B): **entweder** die Plakette „verschlüsselt"
am Kopf der Karte **oder** das Schloss an der einzelnen Zeile, nie beides.
Die Karte „PatientIn" bekam die Plakette, weil dort alles verschlüsselt ist;
die drei geschützten Felder der Karte „Einsatz" bekamen Schlösser, weil sie
zwischen Klartextfeldern stehen.

Die Rückmeldung vom 31.08.2026 dreht das um, und die Begründung trägt: **Die
Plakette sagt „hier stehen verschlüsselte Angaben", das Schloss sagt „diese
hier."** Das sind zwei verschiedene Auskünfte, und bei einer Schutzauskunft
ist Redundanz kein Lärm. Neu sind deshalb:

- die blaue Plakette **„verschlüsselt"** auch am Block **Einsatz**,
- das Schloss an **Name** und **Geburtsdatum** in der Karte PatientIn.

### Web — Der blaue Balken nach dem Entsperren entfällt

„Geschützte Angaben sind entsperrt, bis du dich abmeldest" stand nach dem
Entsperren auf **jedem** Einsatz, den man danach öffnete. Eine Bestätigung,
die von da an immer dasteht, sagt beim zwanzigsten Mal nichts mehr — und
sichtbar ist der Zustand ohnehin daran, dass die geschützten Angaben
dastehen.

**Was bleibt:** der Balken für den **gesperrten** Zustand samt
Entsperren-Knopf und die Fehlermeldung für Angaben, die sich mit dem
aktuellen Schlüssel nicht lesen lassen. Beide sagen etwas, das man nicht
sieht. Die Aussage „entsperrt bis zur Abmeldung" ist ins **Handbuch**
gewandert und dort ausgetragen worden, wo sie die alte Meldung beschrieb.

### Web — Das Schloss saß nicht auf der Höhe des Wortes

`.symbol-schutz` stand auf `vertical-align: baseline` — der Kasten des
16-px-Symbols auf der Schriftlinie des 15-px-Wortes, und damit sichtbar zu
hoch. Nachgemessen an einer echten Zeile, Symbolmitte gegen die Mitte des
Zeilenkastens: `baseline` **−2,0 px**, `middle` (wie auch `text-bottom` und
`sub`) **+2,0 px**, `-0.1em` **−0,5 px**. Es steht jetzt auf `-0.1em` — die
Angabe hängt an der Schriftgröße der Zeile und stimmt damit auch dort, wo das
Schloss in einer anderen Größe steht.

## [Web 12.3.0] — 2026-09-01

**Ein fünfter Meldungston, zwei aufgeräumte Tabellen und ein Gerüst, das
gefehlt hat.** Fünftes Arbeitspaket von S3, dazu zwei Funde aus der
Rückmeldung vom 01.09.2026. Keine Migration, keine Schnittstellenänderung.

### Web — Ein Ton, den es nicht gab, ergab einen weißen Kasten

`ui_meldung_markup()` baute die Klasse aus dem übergebenen Wort zusammen:
`'meldung-' . $ton`. Ein Ton, den es nicht gibt, führte damit zu einer Klasse
**ohne Regel im Stylesheet** — ein Kasten ohne Fläche, ohne Farbe, ohne
Fehlermeldung. Die Spurenseite des Diensttages trug so **zwei** Meldungen mit
dem Ton `hinweis`, den diese Funktion nie gekannt hat.

Die Vollständigkeitsprüfung kann das nicht finden: Sie sucht Klassen als
**Literale** im Quelltext, und diese hier entsteht erst zur Laufzeit. Prüfen
kann es nur die Funktion selbst — sie tut es jetzt und wirft bei einem
unbekannten Ton.

### Web — `schutz`: rot, und trotzdem kein Fehler

Der Hinweis auf der Spurenseite sagt, dass eine GPX-Datei den Weg bis zum
Einsatzort trägt und deshalb so zu behandeln ist wie die geschützten Angaben
selbst. Das ist eine **kritische Datenschutzinformation** an genau der Stelle,
an der jemand gleich herunterlädt — sie gehört in die Farbe des Ernstfalls.

`fehler` wäre falsch gewesen: Die Meldung steht **dauerhaft**, und
`role="alert"` ließe jeden Vorleser bei jedem Seitenaufruf unterbrechen. Der
neue Ton `schutz` benutzt deshalb Fläche und Schrift von `fehler` — **kein
neuer Farbwert** —, aber `role="status"`, und als Symbol das **Schloss** statt
der Warnung: Es geht um Schutzbedürftigkeit, nicht um einen Fehlgriff.

### Web — Der Spurenseite fehlte das Seitengerüst

Sie rief `ui_seite_start()` und schrieb ihren Inhalt danach unmittelbar in den
`<body>` — ohne `ui_geruest_start()`. Als einzige angemeldete Seite der
Anwendung hatte sie damit **keine Diensttag-Leiste und keinen
Inhaltsrahmen**: Titel, Karte und Kartenbaustein saßen am blanken Fensterrand.
Gemessen bei 412 px: linke Kante **0 px statt 12**.

Aufgefallen ist es mobil, wo diese 12 px der Unterschied zwischen „Rand" und
„kein Rand" sind; am Schreibtisch ging die fehlende Leiste als
Gestaltungswille durch. **Der Bilderlauf hat es nicht gefunden, und das ist
kein Versagen des Werkzeugs:** Es misst waagerechten Überlauf
(`scrollWidth > innerWidth`), und eine Seite ohne Innenabstand läuft nicht
über — sie ist nur randlos.

### Web — Spaltentitel standen über nichts

In der NutzerInnen-Liste sind die Spaltentitel seit P3 zentriert (F-N1-G),
die Inhalte von **Rolle, Seit, Zuletzt angemeldet, Geräte** und **Sicherung**
standen aber links oder rechts. Ein Titel mittig über einer linksbündigen
Spalte steht über nichts. Alle fünf stehen jetzt mittig — keiner dieser Werte
ist Fließtext oder eine Größe, die man an einer Kante vergleicht. **Konto
bleibt linksbündig:** Name und Adresse werden gelesen, nicht verglichen.

Dasselbe in der Tagesübersicht für **Nr., Beginn und Alter**. Gemacht ist es
über eine Klasse `mitte-spalte`, **nie über `:nth-child`** — die Tabelle der
Tagesübersicht bekommt ihre hinteren Spalten aus dem Feldkatalog, und eine
Zählung rutschte still auf die falsche Spalte.

### Web — Die Dauer brach nach der Stunde um

„1h 06min" passte nicht in die schmale Spalte und stand auf zwei Zeilen — was
sich wie zwei Angaben liest. `missiontable.js` setzt dagegen seit F-N1-G die
Klasse `zeit-spalte` (`white-space: nowrap`). **Die Tagesübersicht baut ihre
Zeilen aber selbst**, mit einem zweiten, älteren Aufbau derselben Tabelle —
und dort fehlte die Klasse. Sie steht jetzt da. Dass es zwei Aufbauten für
dieselbe Tabelle gibt, ist der eigentliche Fund und liegt als F-S3-A im
Konzept.

### Web — Kennzahl-Kacheln stehen senkrecht mittig

Die Kacheln der Zeitraumübersicht stehen in einem Raster und werden alle so
hoch wie die höchste — und die ist höher, sobald **eine** Beschriftung
umbricht („Längste Flugstrecke · 14.08."). Der Inhalt der übrigen klebte dann
oben. Gemessen in der schmalen Monatsansicht bei 360 px: Kacheln von 77 und
97 px Höhe nebeneinander, die niedrigeren vorher **13 px oben / 33 px unten**,
jetzt **23/23**.

## [Web 12.2.4] — 2026-09-01

**Navigation und Leistenüberschrift.** Viertes Arbeitspaket von S3. Keine
Migration, keine Schnittstellenänderung.

### Web — Wenn jede Zeile fett ist, hebt sich keine hervor

Die Menüpunkte der Seitenleiste standen durchgehend auf Schriftschnitt 600 —
Diensttage wie Einstellungen. Der aktive Punkt musste seine Erkennbarkeit
allein aus Fläche und orangem Randstrich holen, und der ganze Block wirkte
schwer. Jetzt steht der Grundschnitt auf 400 und **nur der ausgewählte Punkt
auf 600**: Die Auszeichnung wandert von „alle" zu „einer".

Dieselbe Überlegung hat P3 schon einmal für die Zeilen des Aktionsblattes
angestellt (F-N1-F). Die Leiste war damals übersehen worden.

Eine Regel ist dabei entfallen: `.eintrag-leise` setzte `font-weight: 400`,
solange die Grundform 600 war. Jetzt wäre es eine Dublette — und eine
Dublette ist nie harmlos, sobald sich die Grundform ändert.

### Web — „Diensttage" wirkte verloren, und es lag nicht an der Ausrichtung

Die Rückmeldung lautete, die Überschrift stehe verloren links oben. Sie bleibt
**linksbündig** — sie wird größer und dunkler: eine Stufe höher in der
geschlossenen Skala (15 statt 13 px) und `--asphalt` statt `--gedaempft`.
Gemessen 19,29:1 gegen die tatsächliche Fläche der Leiste, weit über AA.

**Versalien und Sperrung sind dabei entfallen — entschieden am Bild.** Bei
13 px trug die Sperrung, weil die Größe allein nicht reichte; sie war der
Ersatz für die Größe, die jetzt da ist. Bei 15 px in Bricolage 600 liest sich
„DIENSTTAGE" gesperrt als **Etikett** und steht dem Jahreseintrag „2026"
darunter an Lautstärke kaum nach — zwei Überschriften statt einer Ordnung.
Gemischt gesetzt bleibt es eine Überschrift: präsent, aber ersichtlich eine
Ebene über dem, was sie ordnet.

Der Baustein trägt **vier** Zeilen: Diensttage, Einstellungen, Administration
und „Filter" in der Suche. Alle vier ziehen mit — das ist beabsichtigt und
war der Grund, es am Baustein zu machen und nicht an der Diensttag-Leiste.

## [Web 12.2.3] — 2026-09-01

**Die Sammelleiste bekommt die Form der Karte.** Drittes Arbeitspaket von S3.
Keine Migration, keine Schnittstellenänderung.

### Web — Warum die Leiste „eckig und breiter" wirkte

Sie war es. `.speichern` trug `margin: … calc(var(--abstand-3) * -1)` und
brach damit seitlich aus dem Inhalt aus; einen Radius hatte sie nicht. Neben
einer Karte mit 12 px Radius stand also ein Kasten, der breiter war und
scharfe Ecken hatte — und der trotzdem zu dieser Karte gehört.

Jetzt hat sie **denselben Radius und dieselbe Breite**. Gemessen in acht
Breiten: linke Kante und Breite der Leiste decken sich mit dem Inhalt auf den
Pixel genau. **Was bleibt, ist alles, was die Funktion trägt** — der klebende
Sitz am unteren Rand, die Trennlinie nach oben, der Schatten. Die Leiste soll
auffallen, weil sie folgt, nicht weil sie anders geschnitten ist.

### Web — Der Knopf steht jetzt rechts

Im Markup stand er vorn und damit links, die Zählung („12 ausgewählt") rechts
davon — umgekehrt zu allem anderen in dieser Oberfläche, wo die Haupthandlung
rechts sitzt. Der Hinweis steht jetzt zuerst, der Knopf danach.

Die Ausrichtung läuft über `justify-content:flex-end` und **nicht über
`order`**: Ein `order` hätte die Seh- von der Vorlesereihenfolge getrennt.
Wer die Leiste vorgelesen bekommt, hört jetzt erst „12 ausgewählt" und dann
„Auswahl sichern" — die Zahl, bevor die Handlung kommt, auf die sie sich
bezieht.

**Der Baustein hat zwei Verwendungen, beide sind geprüft:** die Speichern-Leiste
eines schmutzigen Formulars (Einsatzformular, Rechtstexte) und die Sammelleiste
einer Auswahl (NutzerInnen-Liste, Spurenseite des Diensttages).

## [Web 12.2.2] — 2026-09-01

**Der vertikale Rhythmus bekommt eine Regel.** Erste zwei Arbeitspakete von
S3 (Oberflächen-Nacharbeit). Keine Migration, keine Schnittstellenänderung,
keine neue Funktion — was sich ändert, ist die Größe von Zwischenräumen, und
zwar auf jeder Seite.

### Web — Warum die Abstände nicht passten, und es lag an keiner Zahl

Die Rückmeldungsliste vom 31.08.2026 fragte: „Warum passen die Abstände
häufiger nicht? Gibt es dafür keine Definition?" Die Vermutung dahinter —
irgendwo stünden krumme Werte — ist **nachgemessen falsch**: Von **269**
Abstandsdeklarationen im Stylesheet zieht **jede einzelne** ihren Wert aus
`--abstand-1` bis `--abstand-5`; Rohwerte gibt es **null**.

Was fehlte, ist die Stufe darüber: eine Regel, die sagt, **welche** Stufe
**wo** gilt. Ohne sie wird die Wahl an jeder Stelle einzeln getroffen, und
das Ergebnis ist genau das, was die Liste bemängelt — Abstände, die alle aus
derselben Skala kommen und trotzdem nichts miteinander zu tun haben.

Die Regel steht jetzt in `docs/Design.md`, Kapitel 6, mit einer Zeile je
Beziehung und dem Leitgedanken **Bindung ist kleiner als Trennung**: Was
zusammengehört, steht enger als das, was sich voneinander absetzt.

### Web — Der Befund in einer Zeile: Karte und Feld standen gleich weit

`.karte` und `.feld` trugen beide `--abstand-4` (16 px). Der Abstand zwischen
zwei **Karten** war damit genauso groß wie der zwischen zwei **Feldern
innerhalb** einer Karte — die Fläche sagte nichts mehr darüber, was wozu
gehört. Das ist die Antwort auf die Frage der Liste, und sie ist genauer als
„die Abstände passen nicht": Sie passten zueinander, aber sie unterschieden
nichts.

**Die Anwendung widersprach sich dabei selbst.** Die Grundform `label` trägt
für dieselbe Beziehung — Feld → nächstes Feld — schon seit P3 12 px. Ein
Formular aus `<label>`-Grundformen stand also enger als eines aus
`.feld`-Bausteinen, und beide sahen aus wie ein Formular.

| Beziehung | vorher | nachher |
|---|---|---|
| Karte → Karte (`.karte`, `.meldung`, `.geo`, `.demo-hinweis`) | 16 px | **24 px** |
| Feld → Feld (`.feld`) | 16 px | **12 px** |
| Überschrift → ihr Inhalt (`.text h1`/`h2`, `.blatt-titel`, `.listen-form-titel`, `.vorschau > h4`) | 12–16 px | **8 px** |
| Kästchenreihe, Suchzeile, Phasenzeile | 4–8 px | **12 px** |

Insgesamt 13 Regeln. Von 74 Zwischenraum-Deklarationen waren **61 schon
richtig** — die Skala stand, sie wurde nur nicht durchgehalten.

### Web — Zwei Ausnahmen, beide begründet und beide in der Regel vermerkt

**Die Titelzeile bleibt bei 16 px.** Sie ist keine bloße Überschrift: Neben
dem Titel stehen Aktionsknöpfe von 44 px Höhe. Acht Pixel darunter stünde ein
Knopf fast auf der ersten Karte. Wo eine Überschrift Bedienelemente trägt,
ist die Beziehung nicht „Überschrift → Inhalt", sondern „Gruppe → nächste
Gruppe".

**Aufzählungen im Fließtext bleiben eng** (4 px). Ein `<li>` im Fließtext ist
eine **Zeile**, kein Element: Bekäme es den Arbeitsabstand, stünden die
Punkte einer Liste so weit auseinander wie zwei Absätze — und genau die
Bindung, die eine Liste zur Liste macht, wäre weg. Betrifft Handbuch,
Impressum, Datenschutz und die Suchsyntax-Hilfe.

Keine der beiden Ausnahmen ist eine sechste Stufe: Beide ordnen einen Fall
einer **vorhandenen** Zeile der Tabelle zu.

### Web — Zwölf Absendeknöpfe standen ohne Fuß im Formular

Der Fall, an dem die Liste es festmachte: „Profil speichern" in den
Einstellungen war ein nackter `ui_knopf()` zwischen `ui_karte_ende()` und
`</form>`. Der Abstand fehlte nicht, weil eine Zahl falsch war, sondern weil
an dieser Stelle **kein Baustein benutzt wurde** — obwohl es mit
`.listen-form-fuss` seit P3 einen Formularfuß gibt, den dreißig andere
Stellen benutzen.

Die Durchsicht aller Formularseiten fand **zwölf** solche Stellen und keine
weitere: Profil, Passwort ändern, Einrichter, Sicherungsregeln, Serverschlüssel,
Versand, Konto speichern, Konto löschen, Anmelden, Link anfordern und zweimal
Passwort setzen. Alle zwölf haben jetzt denselben Fuß. **Der Geräteabschnitt
der Einstellungen ist ausgenommen** — S5 ersetzt ihn vollständig, und die
Bausteinänderung wirkt dort von selbst, sobald S5 mit den Bausteinen baut.

### Web — Eine doppelte Regel weniger

`.feld-label` stand zweimal im Stylesheet, einmal im Formular- und einmal im
Suchabschnitt, mit demselben Abstand. Die zweite setzte nichts, was die erste
nicht schon gesetzt hätte — aber eine Änderung am Abstand wäre an einer der
beiden Stellen hängengeblieben. Sie ist entfallen; an ihrer Stelle steht der
Hinweis, warum es sie gab.

## [Web 12.2.1] — 2026-09-01

**Drei kleine Dinge, die täglich auffallen.** Zweite Rückmeldungsrunde nach
P3, an zwei Seiten. Keine Migration, keine Schnittstellenänderung.

Entstanden während S2 auf einem eigenen Zweig und bewusst auf Dateien
beschränkt, die S2 nicht hielt — die Buchführung ist deshalb erst jetzt
nachgezogen, nach dem Zusammenführen.

### Web — Der Auswählen-Knopf des Dateifelds klebte oben

`.feld-eingabe` gibt jedem Feld 44 px Höhe und nur waagerechte Polsterung.
Ein `input[type=file]` stellt seinen nativen Knopf auf die Textzeile, und die
steht damit am oberen Innenrand: **gemessen 0 px Luft darüber, 19 px
darunter** bei einem 23 px hohen Knopf. Sichtbar war das auf der
Backup-Seite, wo das Feld unter einer Beschriftung mit viel Luft steht.

**`display:flex; align-items:center` ändert daran nichts** — Chromium legt
den Schatteninhalt eines Eingabefeldes nicht in einen Flex-Fluss; nachgemessen
blieb es bei 0/19. Was wirkt, ist die Zeilenhöhe auf den Innenraum: jetzt
**10 px oben gegen 9 px unten**. Mittiger geht es nicht, der Innenraum ist mit
42 px ungerade. Die 44 px aus der Gestaltungsrichtlinie bleiben unberührt.

Der Attributselektor `input[type=file].feld-eingabe` ist **Abgrenzung, kein
Spezifitätsgewinn**: Es gibt keine Regel `input[type=file]`, gegen die er
gewinnen müsste. Getroffen werden genau die beiden Dateifelder der Anwendung
— Backup einspielen und Import.

| | vorher | nachher |
|---|---|---|
| Luft über dem Knopf | 0 px | **10 px** |
| Luft darunter | 19 px | **9 px** |
| Feldhöhe | 44 px | 44 px |

### Web — Die erzeugte Datei sagte nicht, dass es sie gibt

Sicherung und Datenexport zählten auf, was in der Datei steht, und schwiegen
darüber, dass ein Download gelaufen ist. Der läuft ohne Dialog und ohne Ton
durch; wer nicht gerade auf die Leiste des Browsers sieht, merkt nichts davon
und sucht anschließend eine Datei, deren Namen er nicht kennt.

Beide Meldungen nennen ihn jetzt, und zwar **aus einer Variablen**: Zwei
getrennte Ausdrücke für Download und Meldung liefen mit dem nächsten
Tageswechsel auseinander. **Wo** die Datei liegt, sagen sie bewusst nicht —
das entscheidet die Einstellung des Browsers, und eine Zusage „in deinem
Download-Ordner" wäre für jeden falsch, der sein Ziel selbst wählt.

Im Export sind es drei Wege mit drei Namen (Tabelle roh, Tabelle im
verschlüsselten Archiv, Archiv des Profils B); beim Archivweg ist die
heruntergeladene Datei das **Archiv**, nicht die Tabelle darin. Der Satz steht
am Ende der Meldung: Was davor steht, beschreibt den Inhalt der Datei, dieser
ihren Verbleib. In der Sicherung steht er **vor** den ACHTUNG-Blöcken, damit
eine Warnung das letzte bleibt, was gelesen wird.

### Web — Und `warn` trug das falsche Zeichen

Der `melde()`-Nachbau der Einstellungsseite führte
`ton === 'fehler' ? 'warnung' : (ton === 'ok' ? 'haken' : 'hinweis')`. Der Ton
`warn` fiel damit in den Sonst-Zweig und bekam das **Hinweiszeichen** —
entgegen `Design.md` 9.5 und entgegen `ui_meldung_markup()`, das die
vollständige Tabelle führt. Betroffen waren gerade die Meldungen, die
auffallen sollen: eine Sicherung mit unlesbaren geschützten Angaben oder nicht
mitgesicherten Spuren, abgelehnte Spuren beim Einspielen und auf dem
Freigabeweg. Der Nachbau führt jetzt dieselbe Tabelle wie der Baustein.

Der zweite Nachbau (`import_ui.js`) hat die Lücke nicht — er kennt den Ton
`ok` gar nicht und ist für seine Verwendung vollständig. Unangetastet.

### Web — Nebenbei

`ui_feld()` führte seine Feldarten zweimal auf, außen und innen, und die
Listen waren verschieden: außen fehlte `file` und stand `time`, das nirgends
benutzt wird; innen fehlten `password` und `file`, obwohl beide seit Langem
verwendet werden (14- bzw. 2-mal). Beide nennen jetzt dieselben Arten.

### Geprüft

| Mittel | Zahl |
|---|---|
| Stilvergleich | 35 763 Elementmessungen über 13 Breiten → **13 Abweichungen**: ein Element, eine Eigenschaft, keine darüber hinaus |
| Kaskadenvergleich | 637 → 638 Regeln, 0 entfallen, 0 mit anderem Endwert, 0 Reihenfolgetausche |
| Vollständigkeitsprüfung | 224 Befunde vorher wie nachher, Bericht byteweise gleich |
| Wortliste | 0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen |
| Feldmessung | 77 Eigenschaften über 7 Feldarten, genau **eine** Abweichung |
| Meldungstexte | beide Zweige der Sicherung und alle vier Export-Kombinationen in node durchgespielt |

**Nachgetragen am 01.09.2026: an der laufenden Anwendung geprüft.** Die
Begründung „ohne MySQL nicht aufrufbar" war falsch — das Projekt fährt lokal
seit P1 gegen **MariaDB 10.11**, und `tools/referenzdatensatz/einspielen/lokal_starten.sh`
setzt die Installation auf. Nachgeholt:

| Geprüft | Ergebnis |
|---|---|
| beide Dateifelder (Backup einspielen, Import) | je 44 px hoch, `line-height: 42px`, Knopf mittig |
| Sicherung im Demo-Konto | Meldung nennt `einsatzdoku-backup-2026-09-01.edbak` — **derselbe Name wie der tatsächliche Download** |
| Export, alle drei Downloadwege | Profil A (xlsx), Profil C (zip, verschlüsselt), Profil B (zip-Archiv) — jeder nennt den Namen der Datei, die wirklich ankommt, beim Archivweg den des **Archivs** |
| `warn` mit unlesbarem Blob | Meldung trägt das **Warndreieck**; Gegenprobe mit der alten Fassung zeigt an derselben Stelle das Hinweiszeichen |
| Bilderlauf | 304 Einzelbilder, 38 Kontaktbögen — Überlauf 0, Konsolenfehler 0, Knöpfe ≠ 44 px 0 |
| Kontraste | 21 Paare gerechnet, 0 verfehlt |

Die Reihenfolge stimmt auch im Fehlerfall: Der Download-Satz steht vor der
ACHTUNG-Passage, die Warnung bleibt das Letzte.

## [Web 12.2.0] — 2026-09-01

**Die ganze Installation in einer Datei — und ein Weg zurück.** Achtes
Arbeitspaket der Phase S2 (AP8, E-S2-19 bis E-S2-21). Keine Migration, keine
Schnittstellenänderung.

### Web — Wogegen das hilft

Bis hierher konnte diese Anwendung ein **Konto** sichern. Der Fall, gegen den
sie damit hilft, heisst „jemand hat sich vertan". Der andere Fall — „der
Webspace ist weg" — war nicht abgedeckt: Stammdaten, Geräte, Schlüsselhüllen,
der Migrationsstand und die 33 übrigen Tabellen standen in keiner Sicherung.
E-S2-19 hat für diesen Fall die Komplettsicherung beschlossen und nächtliche
Konto-Sicherungen ausdrücklich abgelehnt.

Sie umfasst **jede Tabelle dieser Datenbank**. Nicht enthalten ist
`config.php` — sie trägt das Datenbankpasswort und den Serverschlüssel, also
genau das, womit sich die Datei öffnen lässt. Beides in dieselbe Datei zu
legen hiesse, das Schloss an den Schlüssel zu binden. `config.php` gehört ins
getrennt aufbewahrte **Wiederanlaufpaket**; das Runbook (`docs/Technik.md`,
Abschnitt 7) sagt es jetzt ausdrücklich.

### Web — Warum ein eigener Dump und nicht `mysqldump`

Auf geteiltem Webspace gibt es keine Kommandozeile und kein `exec()`;
`mysqldump` ist dort nicht vorhanden und nicht nachrüstbar. Der Dump entsteht
deshalb in PHP, über genau die Verbindung, die die Anwendung ohnehin hat.

Seine Form ist absichtlich schlicht: **ein Statement je Zeile**, INSERT-Stapel
bis 1 MB, Tabellen in einspielbarer Reihenfolge (topologisch nach
Fremdschlüsseln), Binärspalten hexadezimal. Damit lässt er sich zeilenweise
abarbeiten — vom Rückweg dieser Anwendung genauso wie von `mysql` oder
phpMyAdmin. Ein mehrzeiliges Statement bräuchte einen SQL-Zerleger, und ein
selbstgebauter SQL-Zerleger ist die Sorte Code, die genau einmal falsch liegt:
wenn ein Semikolon in einer Zeichenkette steht.

### Web — Drei Schichten, und warum die mittlere so aussieht

SQL-Text → gzip → Siegel **EDKOMP1** (AES-256-GCM je 256-KB-Block).

Erzeugt wird in Häppchen über den Job-Einstieg, mit Fortsetzungszustand in
`jobs.zustand`. Das hat eine Folge, die man sehen muss: Ein
`deflate_init()`-Zustand lässt sich zwischen zwei Anfragen **nicht**
aufbewahren — er ist keine Zahl, sondern ein Fenster über die letzten 32 KB.
Deshalb schliesst jedes Häppchen sein gzip-Glied ab und das nächste hängt ein
neues an. Aneinandergehängte gzip-Glieder sind gültiges gzip; `gunzip`, `zcat`
und PHPs `gzopen()`/`gzgets()` lesen darüber hinweg. Der Preis sind einige
Promille Grösse (gemessen: 3 045 Byte auf 45,8 MB).

**Zwei PHP-Funktionen lesen allerdings nur das erste Glied**, und zwar ohne
Fehler: nachgemessen an einer Datei aus 15 Gliedern mit 122 469 394 Byte
Klartext liefern `gzdecode()` und `inflate_add()` je **13 573 234 Byte
(11 %)** — was dabei herauskommt, sieht aus wie eine ganze Datei. Nur
`gzopen()`/`gzread()` liefert alles; die Anwendung benutzt ausschliesslich
diesen Weg. (Python ist gutmütiger: `gzip.open()` und `gzip.decompress()`
lesen beide alle Glieder.)

Die erste Fassung des Rückwegs schob jeden entsiegelten Block durch
`inflate_add()` und brach mit „data error" ab; er entpackt jetzt über eine
Zwischendatei. Bei einem Dump, der in einem Zug entstanden ist, wäre der
Fehler nie aufgetreten — die Probe fährt deshalb ausdrücklich einen aus
vierzehn Häppchen.

Das Siegel trägt **Blockzähler und Endemarkierung in den Zusatzdaten** und
bindet den Dateikopf über seinen SHA-256 mit. Ohne den Zähler liessen sich
zwei Blöcke vertauschen; ohne die Endemarkierung liesse sich die Datei hinten
abschneiden, und was übrig bliebe, wäre eine gültige, kürzere Sicherung.

### Web — Zwei Wege heraus, und der Unterschied ist der Punkt

- **„Herunterladen"** gibt den Dump **unverschlüsselt** als `.sql.gz` — genau
  das, was `mysql` und phpMyAdmin einspielen. Er geht an die Administratorin,
  die sich eben angemeldet hat und ohnehin jede Zeile dieser Datenbank sehen
  kann.
- **„Versiegelt herunterladen"** liefert dieselbe Datei unter einer
  **Passphrase** (PBKDF2, 320 000 Runden — dieselbe Zahl wie im Browser). Sie
  wird nicht doppelt verschlüsselt, sondern block für block *umgesiegelt*;
  der Speicherbedarf bleibt bei einem halben Megabyte, gleich wie gross die
  Datei ist.
- Was **von selbst** hinausgeht — der Versand aufs Sicherungsziel — ist immer
  die versiegelte Fassung (E-S2-21).

Ohne Serverschlüssel wird gar nicht erst gesichert. Eine unversiegelte
Abschrift jeder Tabelle in `sicherungen/` liegen zu lassen unterliefe die
Ende-zu-Ende-Zusage an der Stelle, an der es am wenigsten auffiele.

### Web — Der Rückweg: `wiederherstellen.php`

Er füllt die Lücke zwischen `install.php` (verweigert sich, sobald es eine
`config.php` gibt) und `update.php` (verlangt eine Anmeldung, die es ohne
Konten nicht geben kann). Drei Schranken, jede mit ihrem Grund:

1. **Die Datenbank muss leer sein.** Steht auch nur ein Konto darin, ist diese
   Installation in Betrieb. Das gilt fürs *Anfangen*: Ab dem zweiten Durchgang
   ist die Datenbank nicht mehr leer, weil der erste sie füllt — die erste
   Fassung brach deshalb bei 91 % ab und meldete „Diese Installation ist in
   Betrieb". Wer einen Arbeitsstand hat, hat ihn auf einer leeren Datenbank
   begonnen; daran hängt der Unterschied.
2. **Ein Nachweis** wie beim Einrichter (M1-11): eine Datei mit zufälligem
   Namen im Anwendungsverzeichnis, deren Kennung einzutragen ist. Ohne ihn
   wäre die Seite im Zeitfenster zwischen „Datenbank leer" und „erstes Konto"
   für jeden im Netz offen.
3. **Die Datei kommt aus `sicherungen/eingang/`**, nicht aus einem Formular.
   Wer sie dort ablegen kann, hat ohnehin Dateizugriff.

**Der Migrationslauf läuft dort bewusst nicht mit.** `update.php` ist seit
M6-01 zweistufig, weil Migrationen Spalten löschen können und zwischen
Anzeigen und Ausführen ein Knopf und eine angemeldete Person gehören. Eine
Seite ohne Anmeldung, die sie nebenbei mitlaufen liesse, nähme genau diese
Sicherung heraus. Stattdessen sagt die Seite am Ende, ob der Dump aus einer
anderen Fassung stammt, und schickt zur Wartung.

### Web — Der Schnappschuss ist nicht scharf, und das steht dran

`mysqldump --single-transaction` hält einen Lesestand über den ganzen Lauf.
Das geht nur **innerhalb einer Verbindung**, und diese Sicherung läuft über
viele Anfragen. Eine Zeile, die währenddessen entsteht, kann enthalten sein
oder nicht. Was **nicht** passieren kann, ist eine übersprungene Altzeile: Der
Cursor läuft über den Primärschlüssel und nicht über `LIMIT/OFFSET`, ein
gelöschter Vorgänger verschiebt ihn also nicht.

Der Cursor ist dabei **aufgefächert** (`a > ? OR (a = ? AND b > ?)`) und kein
Zeilenkonstruktor — gemessen an `track_points` mit 917 331 Zeilen: `type=index`
und 0,1486 s gegen `type=range` und 0,0010 s. Eine Ausnahme: Steht eine
ENUM-Spalte **vorn** im Primärschlüssel, hilft auch das Auffächern nichts;
sie wird deshalb über ihre Werteliste festgenagelt.

### Web — Gemessen

Am Messbestand (5 000 Einsätze, 1 121 802 Zeilen, 34 Tabellen), ohne
Drosselung:

| | Wert |
|---|---|
| Erzeugen | **8,5 s** in **14 Häppchen** |
| Speicherspitze | **26 von 64 MB** (Z3) |
| SQL / versiegelt | 122,5 MB → **43,7 MB** |
| Längste Zeile | 1 048 566 Byte (Ziel: ≤ 1 MB je Stapel) |
| Einspielen | 784 Anweisungen in 6,0 s |
| Rundlauf | **34 von 34** Schemata zeichengleich, **34 von 34** Prüfsummen gleich (`CHECKSUM TABLE EXTENDED`) |

Die neue **Komplettprobe** (`tools/komplettprobe/`) fährt den ganzen Zyklus:
**76 Erwartungen, 0 nicht erfüllt** — einschliesslich Versand auf eine echte
FTP-Gegenstelle und dem Fall „halbe Datei liegt dort". Dazu ein Klickweg im
Browser (`klickweg.mjs`): **17 Prüfungen, 0 Befunde**, mit beiden Downloads
und Prüfung ihres Inhalts. Die Versandprobe aus AP7 bleibt bei **115
Erwartungen, 0 nicht erfüllt**; Wortliste **0/0/0**, Vollständigkeit **260**
(unverändert), Kontraste **21 Paare, 0 verfehlt**.

Der Bilderlauf über beide neuen Seiten in acht Breiten (**16 Bilder**) fand
einen Fehler und meldet ihn jetzt nicht mehr: Der Knopf „Versiegelt
herunterladen" stand in einer `.fld-reihe` statt im Fussbereich und schob die
Seite bei 360, 390 und 420 px auf (+74/+59/+44 px). In einer `.fld-reihe`
liegen Felder nebeneinander; ein Knopf darin bekommt keine Umbruchstelle.

### Web — Was die Probe gefunden hat

**Der Neuanlauf lief in ein `count(null)`.** Die Erstbelegung des
Fortsetzungszustands stand *vor* dem Zweig, der bei verschwundenem Baustand
ebendiese Marken löscht. Der Zweig ist der, der nach einer Wiederherstellung
greift: Die Sicherung schreibt ihren eigenen Fortschritt mit, die eingespielte
Datenbank trägt also den Stand „Dump läuft" samt einem Bauordner, den es auf
dem neuen Server nie gab. Ohne die Probe wäre das genau einmal aufgefallen —
beim ersten Wartungslauf nach dem ersten Wiederanlauf.

### Web — Sonst noch

- Der Job `komplett` steht im Katalog **nach** `versand`. Davor wäre er am
  rechten Platz — was entsteht, ginge im selben Lauf hinaus —, nur bekäme
  jeder Job hinter ihm nur noch, was die schwerste Arbeit der Anwendung übrig
  lässt. Der Preis ist ein Lauf Verzögerung. Zusätzlich begrenzt er sich auf
  zwei Minuten je Lauf.
- Die Speicherbuchführung zählt Komplettsicherungen **eigens** (`komplett`,
  `komplett_bytes`) statt sie unter „auffälliger Rest" zu führen. Sie ist die
  grösste Datei der Ablage; sie als Rest auszuweisen brächte die Speicherseite
  dazu, einen Fehler zu melden, den es nicht gibt.
- Neuer Menüpunkt **Komplettsicherung** unter Administration, direkt hinter
  Sicherungsziele. Symbol `datenbank` aus dem vorhandenen Vorrat.

## [Web 12.1.1] — 2026-09-01

**Die Suche, und ein Prüfmittel, das sich selbst gemessen hat.** Neuntes
Arbeitspaket der Phase S2 (AP9, E-S2-16). Keine Migration, keine
Schnittstellenänderung.

### Web — Was E-S2-16 verlangt

Zwei Mäßigungen, beide klein und beide belegt:

- **`EdCrypto` merkt sich den importierten Schlüssel.** Bis hierher rief jedes
  `encrypt()` und jedes `decrypt()` sein eigenes `crypto.subtle.importKey()`.
  Bei einem Konto mit 5 000 Einsätzen waren das **4 880 Importe für denselben
  Schlüssel**; jetzt ist es **einer**. Gemerkt wird die *Promise* des Imports
  und nicht der fertige Schlüssel — sonst starten 200 gleichzeitige Aufrufe
  200 eigene Importe, weil noch keiner fertig ist. Das Fach wird beim
  Abmelden mit geräumt.
- **`EdPat.entschluessleListe()` arbeitet in Stapeln zu 200** statt einzeln
  nacheinander. Die Schleife um die Entschlüsselung kostete mehr als die
  Entschlüsselung selbst: gemessen **387 ms** für 4 880 Entschlüsselungen und
  **1 954 ms** für die Runden durch die Ereigniswarteschlange darum herum.
  Jetzt sind es **958 ms**.

### Web — Was das bringt, und was nicht

| | vorher | nachher |
|---|---|---|
| `importKey`-Aufrufe | 4 880 | **1** |
| `entschluessleListe` | 1 954 ms | **958 ms** |
| erste Zeile im DOM | 4,02 s | 3,67 s |
| **geschützte Spalten lesbar** | **4,11 s** | **3,77 s** |

Drossel 6×, Median aus drei Läufen, beide Stände unmittelbar nacheinander
gemessen. Das Ziel aus E-S2-24 (≤ 5 s) ist gehalten.

**Der Löwenanteil der Zeit liegt aber nicht im Entschlüsseln.** Zwischen
„erste Zeile im DOM" und „lesbar" liegen 0,1 s — die Entschlüsselung der
angezeigten Zeilen ist also nicht das Problem. Die 3,67 s davor gehen für das
drauf, was vor der Tabelle passiert. Das ist **Backlog Nr. 51** und
ausdrücklich kein Nebenbei-Umbau.

Nebenbei belegt: Die Suche entschlüsselt **alle 5 002 Einträge**, nicht nur
die 200 angezeigten — die Ausgangsmessung von AP0 nahm das Gegenteil an. Ohne
Grund ist es nicht (die Freitextsuche sucht in Diagnose, Alter und Ort, und
die liegen im verschlüsselten Block), ohne Filter aber unnötig.

### Web — Der größere Fund steckte im Prüfmittel

`entsperren()` in `tools/messstand/browserprobe.mjs` wartete **vier Sekunden**
auf einen Entsperr-Dialog. Ist die Sitzung bereits entsperrt — der Regelfall
direkt nach dem Anmelden —, kommt der Dialog nie, und die vier Sekunden liefen
jedes Mal vollständig ab. Sie standen **mitten im gemessenen Abschnitt**.

Die Ausgangsmessung von AP0 nennt „Suche 4,53 s" und „Tagesansicht 4,81 s".
Beide liegen dicht über vier Sekunden, und das ist kein Zufall: Gemessen wurde
`max(4 s Wartezeit, tatsächliche Dauer)`. Der wahre Wert lag die ganze Zeit
darunter, und niemand konnte es sehen — die Zahl war plausibel, und eine
plausible Zahl wird nicht hinterfragt.

Das Warten rennt jetzt gegen die Abschlussbedingung des Schritts: Kommt der
Dialog zuerst, wird entsperrt; ist der Schritt zuerst fertig, war kein
Entsperren nötig und es wurde keine Sekunde dafür verbraucht. Der ganze Lauf
sieht damit anders aus:

| Schritt | AP0 (mit dem Messfehler) | jetzt | Ziel (E-S2-24) |
|---|---|---|---|
| Startseite, 500 Tagesverweise | 1,36 s | 1,39 s | — |
| **Tagesansicht bis zur gezeichneten Spur** | **4,81 s** | **1,17 s** | ≤ 3 s — **gehalten**, nicht 62 % darüber |
| Suche bis zur ersten Trefferanzeige | 4,53 s | **3,81 s** | ≤ 5 s |
| Sicherung erstellen | 109,8 s | **42,21 s** | ≤ 5 min |

**Die Tagesansicht war nie über dem Ziel.** Der Befund „62 % darüber" aus
der Ausgangsmessung löst sich vollständig auf — er war der Timeout. Bei der
Sicherung geht ein Teil der Verbesserung auf AP5b und AP6 zurück; bei
Tagesansicht und Suche ist es der Messfehler.

**Dieselbe Falle hat mich beim Nachmessen noch einmal erwischt** — mit acht
statt vier Sekunden, und ich habe daraufhin zwei Messungen veröffentlicht, die
nichts als meinen eigenen Timeout maßen. Die Regel aus `CLAUDE.md` Abschnitt 6
gilt gegen das Prüfmittel genauso wie gegen den Code: Eine Zahl ist erst dann
ein Beleg, wenn sie benennt, was sie gemessen hat.

### Web — Geprüft

Kreislauf `edbak` über den vollen Referenzbestand: **252 882 Einzelvergleiche,
0 unerklärte Abweichungen** (16 erwartete) — die Änderung an `crypto.js` und
`patient.js` berührt den Sicherungs- und Wiederherstellungsweg nicht.

## [Web 12.1.0] — 2026-09-01

**Sicherungsziele: die Sicherung verlässt das Haus.** Achtes Arbeitspaket der
Phase S2 (AP7, E-S2-21 und E-S2-22). **Migration zwingend**
(`2026_09_01_sicherungsziele`); nach dem Deploy muss eine Administratorin
`update.php` aufrufen, sonst zeigt die neue Seite nur einen Hinweis.

### Web — Warum

Bis hierher entstanden die Admin-Sicherungen unter `server/sicherungen/` und
blieben dort. Das ist die Rückfallebene für einen gelöschten Einsatz und für
ein verlorenes Passwort — aber nicht für den Fall, für den man Sicherungen
eigentlich macht: dass dieser Server weg ist. Eine Sicherung, die im selben
Behälter liegt wie das Gesicherte, ist keine.

### Web — Drei Protokolle, eine Schnittstelle

`sicherungsziel_lib.php` beschreibt mit `Zielweg`, was ein Ziel können muss
(verbinden, Ordner anlegen, senden, holen, auflisten, löschen), und setzt es
dreimal um. Wer eine Datei wegschiebt, sieht das Protokoll nicht — das ist
keine Ästhetik, sondern die Vorbereitung auf AP8: Das Komplettbackup benutzt
dieselbe Schnittstelle, und ein vierter Adapter (WebDAV, Backlog) soll sie
nicht anfassen.

Was die drei taugen, in der Reihenfolge der Empfehlung — und ohne
Beschönigung:

| | verschlüsselt | erkennt den Server wieder |
|---|---|---|
| **SFTP** (phpseclib) | ja | **ja**, am Fingerabdruck des Hostschlüssels |
| **FTPS** (`ext/ftp`) | ja | nein |
| **FTP** (`ext/ftp`) | **nein** | nein |

Der mittlere Fall ist der, den man leicht überschätzt: `ext/ftp` **prüft das
Zertifikat nicht**. Das ist keine Vermutung — `tools/versandprobe/` stellt eine
FTPS-Gegenstelle mit einem selbst ausgestellten Zertifikat ohne jede
Vertrauenskette hin, und die Verbindung kommt zustande. Schutz gegen Mitlesen
also ja, Schutz gegen einen untergeschobenen Server nein. Die Auswahl im
Formular sagt es dort, wo man wählt.

Bei SFTP wird der Fingerabdruck beim ersten Prüfen übernommen und danach bei
jeder Verbindung verglichen. Passt er nicht, bricht die Verbindung ab — und
zwar **vor** der Anmeldung. Gemessen wird das nicht an der Fehlermeldung,
sondern an der Gegenstelle: Sie schreibt jeden Anmeldeversuch mit, und bei
unerwartetem Hostschlüssel bleibt ihr Protokoll unverändert (3 Zeilen vorher,
3 Zeilen nachher). Wer sich bei einem untergeschobenen Server anmeldet, hat
sein Passwort schon abgegeben, auch wenn er danach abbricht.

### Web — Der Serverschlüssel, und warum er in `config.php` liegt

Die Zugangsdaten der Ziele stehen versiegelt in der Datenbank (`edsk1:`,
AES-256-GCM). Der Schlüssel dazu ist neu und steht in `config.php` — 32 Byte
Zufall, nicht in einer Tabelle. Der Zweck ist genau der Fall „jemand hat die
Datenbank": Ein Schlüssel neben dem Chiffretext hilft dann niemandem. Für das
Komplettbackup aus AP8 wird es zwingend, denn dessen Dump enthält jede
Tabelle.

Der Zweck (Zielkennung und Feldname) geht in die Zusatzdaten der
Verschlüsselung. Damit lässt sich das versiegelte Passwort von Ziel 3 nicht
als Passwort von Ziel 7 einsetzen, obwohl beide mit demselben Schlüssel
versiegelt sind.

Neue Installationen bekommen ihn vom Installer. Bestehende tragen ihn auf der
Seite „Sicherungsziele" nach: ein Knopf, wenn `config.php` beschreibbar ist,
sonst eine Zeile zum Einfügen. Der Knopf ergänzt und ersetzt nie, schreibt in
eine Nebendatei mit Endung `.php` (eine `config.php.tmp` läge im
Wurzelverzeichnis des Webservers als lesbarer Text mit dem
Datenbankpasswort), prüft sie durch Ausführen gegen die gerade geltende
Fassung und schiebt sie erst dann an ihren Platz.

**Er gehört ins Wiederanlaufpaket.** Ohne ihn sind die Zugangsdaten der Ziele
neu einzutragen — verschmerzbar — und ein versiegeltes Komplettbackup nicht
mehr zu öffnen. Das Runbook in `docs/Technik.md` sagt es, der Kopf der
erzeugten `config.php` sagt es auch: Wer die Datei zum ersten Mal öffnet,
liest sie und nicht das Runbook.

### Web — Der Versand

Ein Joblauf (`versand`) und ein Knopf. Was „neu" ist, wird **am Ziel**
abgelesen — Name und Größe — und nicht in einer Merkliste geführt. Eine
Merkliste behauptet „schon versandt" auch dann noch, wenn die Datei am Ziel
gelöscht, das Ziel neu aufgesetzt oder der Pfad geändert wurde; diese Art
Lüge fällt erst auf, wenn man die Sicherung braucht. Der Vergleich schliesst
die Größe ein, weil eine abgebrochene Übertragung sonst mit dem richtigen
Namen und der falschen Länge für immer als erledigt gälte.

**Es wird nur ergänzt.** Auf dem Ziel löscht diese Anwendung nie — auch nicht
im Sinne der Aufbewahrung „zwei je Konto", die für die Ablage auf diesem
Server gilt. Der Zweck eines auswärtigen Ziels ist, den Ausfall dieses Servers
zu überleben, samt eines Fehlers, der hier zu viel löscht. Wer dort aufräumen
will, tut es dort (Backlog Nr. 49).

Gemessen gegen örtliche Gegenstellen, 64 Pakete zu zusammen 63,89 MB aus 33
Kontoordnern:

| | Dateien | Dauer | PHP-Speicherspitze |
|---|---|---|---|
| FTP | 64 | 0,13 s | 2,0 MB von 64 |
| FTPS | 64 | 0,68 s | 2,0 MB von 64 |
| SFTP | 64 | 3,08 s | 8,0 MB von 64 |

Alle 192 angekommenen Dateien wurden byteweise mit dem Original verglichen: 0
Abweichungen. Ein zweiter Lauf sandte 0 Dateien (0,19 s). Eine am Ziel auf
1 000 Byte gekürzte Datei wurde beim nächsten Lauf einzeln erneut geschickt —
eine von 64 — und war danach wieder byteweise gleich. Mit einem Budget von 2 s
teilte sich derselbe Lauf in zwei Schübe (34 + 30 Dateien) und war danach
vollständig.

Der Schalter „automatisch versenden" sagt **ob**, nicht **wann**: Wann etwas
läuft, entscheidet der eingerichtete Auslöser auf der Wartungsseite. Eine
zweite Uhr in der Datenbank wäre eine zweite Wahrheit.

### Web — Was die Prüfung tatsächlich prüft

„Verbindung prüfen" meldet nicht, dass die Anmeldung geklappt hat. Es
schreibt eine Probedatei, liest sie zurück, vergleicht sie Byte für Byte und
löscht sie wieder — und zeigt jeden dieser Schritte einzeln an. Eine
Anmeldung, die klappt, sagt nichts über Schreibrechte; woran es scheitert,
erfährt man sonst nachts, ohne Zuschauer.

### Web — Was „Sicherungsziel" heisst und warum nicht „Transportziel"

Das Konzept nennt es Transportziel. Diesen Namen trägt seit Web 4 aber schon
`transport_dests` — die Zielklinik einer Patientin, gepflegt unter
Stammdaten. Zwei Dinge, zwei Klicks voneinander entfernt, unter einem Wort:
Das ist die Art Verwechslung, die man in einer Fehlermeldung nicht mehr
auflösen kann. Also Sicherungsziel (Konzept-S2, F-S2-G).

### Web — Fremdbestandteil

**phpseclib 3.0.57** (MIT, reines PHP) liegt unter `server/vendor/phpseclib3/`,
dazu **constant_time_encoding 2.7.0** (MIT), das phpseclib an genau einer
Stelle voraussetzt. Ein zwanzigzeiliger PSR-4-Lader statt Composer — auf einem
Webspace läuft kein `composer install`. Die Kopfzeile mit Herkunft und
SHA-256, die `docs/Lizenzen.md` sonst je Datei verlangt, ist hier durch zwei
Prüfsummenlisten ersetzt (`sha256sum -c`): Bei 349 Dateien wäre sie von Hand
nicht zu pflegen und beim ersten Austausch falsch.

**`ext/ssh2` wäre die Alternative gewesen** und ist es nicht: Auf geteiltem
Webspace ist die Erweiterung praktisch nie da und lässt sich dort nicht
nachinstallieren. phpseclib kostet dafür Rechenzeit — bei einer Handvoll
Dateien je Nacht fällt das nicht ins Gewicht (3,08 s für 64 MB, siehe oben).

### Web — Zwei Funde aus der eigenen Prüfung

**`SFTP::TYPE_REGULAR` gibt es nicht.** phpseclib definiert
`NET_SFTP_TYPE_REGULAR` als *globale* Konstante beim ersten Erzeugen eines
Objekts, nicht als Klassenkonstante. Der Fehler schlug erst beim Auflisten
zu, nicht beim Übertragen — eine Verzeichnisliste, die leer bleibt, hätte den
Versand still alles doppelt schicken lassen.

**Der Serverschlüssel war nach dem Schreiben eine Anfrage lang unsichtbar.**
OPcache merkt sich die übersetzte `config.php` und prüft ihren Zeitstempel
sekundengenau; wird sie in derselben Sekunde ersetzt, gilt sie als
unverändert. Die Seite meldete „steht jetzt in config.php", und der
unmittelbar folgende Aufruf zeigte wieder „Serverschlüssel fehlt". Behoben mit
`opcache_invalidate()`.

Dazu zwei Fehlermeldungen, die die Probe als unbrauchbar entlarvt hat: Ein
falsches FTP-Passwort meldete „Authentication failed" ohne das Wort Passwort
(das Muster kannte nur „Login incorrect"), und ein geschlossener Port meldete
„kam nicht zustande" ohne jeden nächsten Schritt — `ftp_connect()` gibt dort
`false` zurück und schweigt.

### Web — Zwei Sätze Gegenstellen, weil einer nicht reicht

`tools/versandprobe/` stellt die Server selbst hin, und zwar zweimal:
`gegenstellen.py` in Python (pyftpdlib, paramiko — portabel, ohne Rechte) und
`echte_gegenstellen.sh` mit **vsftpd und OpenSSH**, also dem, was auf einem
Webspace tatsächlich läuft.

Beide werden gebraucht, und nicht aus Gründlichkeit: **vsftpd kennt kein
MLSD.** Der Rückfall auf `NLST` + `SIZE` in `ZielFtp::liste()` ist dadurch
zum ersten Mal gefahren worden — gegen pyftpdlib allein wäre er nie an die
Reihe gekommen. Die Probe misst seither, welchen Weg sie genommen hat, statt
ihn zu vermuten.

Der zweite Satz hat gefunden, was der erste nicht sehen konnte: **zwei
Fehlermeldungen blieben halb englisch.** vsftpd sagt „Could not create file",
pyftpdlib sagt „Not enough privileges" — beide Wortlaute kannte die
Übersetzung nicht, und die Meldung für ein verweigertes Schreiben endete im
Original. Jede Umsetzung bringt ihr eigenes Vokabular mit.

Vier Nebenwege waren bis dahin unbelegt und sind es nicht mehr: der Listenweg
ohne MLSD, **aktives** FTP (der Schalter war nur in einer Stellung gefahren),
ein Grundpfad mit Unterordnern statt `/` — im Betrieb der Normalfall — und
ein verweigertes Schreiben.

Und der Hostschlüsselwechsel ist jetzt echt: OpenSSH startet mit einem
zweiten Schlüssel neu, statt dass ein erfundener Fingerabdruck eingesetzt
wird. Die Verbindung bricht ab, die Meldung nennt beide Abdrücke, und das
Anmeldeprotokoll des Servers steht vorher wie nachher auf 46 Zeilen. Nebenbei
eine unabhängige Gegenprobe: Der errechnete Fingerabdruck ist zeichengleich
mit dem von `ssh-keygen -lf`.

**Ein Unterschied, der im Betrieb Schaden anrichten kann**, ist dabei
aufgefallen: vsftpd sperrt den Nutzer in sein Heimverzeichnis, dort ist `/`
die Wurzel. OpenSSH tut das nicht — dort ist `/` die Wurzel des Dateisystems.
Derselbe Eintrag „Pfad = /" bedeutet je nach Protokoll etwas anderes; die
LIESMICH sagt es jetzt.

### Web — Geprüft

`tools/versandprobe/` gegen echte Server auf 127.0.0.1, in beiden Fassungen:
**115 Erwartungen, 0 nicht erfüllt** — gegen pyftpdlib/paramiko **und** gegen
vsftpd/OpenSSH. Im Browser: Serverschlüssel
anlegen, Ziel anlegen, prüfen (6 Schritte), falsches Passwort, versenden (64
Dateien, 63,9 MB), Rückstand danach 0 — 0 Konsolenfehler, 0 px waagerechter
Überlauf, Bedienelemente 44 px. Der Wartungslauf über die Befehlszeile führt
`versand` mit auf (`fertig · erledigt 64 · Rückstand 0`).

**Was nicht geprüft werden konnte:** ein echtes Ziel im Internet. Aus dem
Behälter, in dem gearbeitet wurde, gehen nur Verbindungen auf Port 443 hinaus
— nachgemessen mit `github.com:22` als Gegenkontrolle, einem sicher offenen
Port, der ebenso abgewiesen wird. Die Abnahme gegen ein echtes Ziel je
Protokoll steht aus und gehört auf die Maschine der Betreiberin oder auf den
Produktivserver.

## [Web 12.0.0] — 2026-09-01

**Die Admin-Sicherung wird mehrteilig.** Siebtes Arbeitspaket der Phase S2
(AP6, E-S2-13 bis E-S2-15). Hauptnummer, weil das Dateiformat der
Admin-Sicherung von 1 auf 2 wechselt **und** der erste Lauf danach die
einteiligen Pakete eines Kontos entfernt. **Keine Migration** — nur das
Dateiformat ändert sich, das Datenmodell nicht.

### Web — Warum

Die Admin-Sicherung war der letzte Weg, der das Budget sprengte, und zwar
nicht knapp. **Gemessen** am 5000er-Konto:

| | vorher | jetzt |
|---|---|---|
| Dauer | 19,81 s | **14,13 s** |
| Speicherspitze | **1077,6 MB** | **24,0 MB von 64** |
| mit `memory_limit=64M` (Z3) | **Abbruch** in `spur_lib.php` | läuft durch |
| Datei | 94,28 MB | **11,42 MB** |

Am Demokonto: 28,1 → 4,0 MB Spitze, 2,14 → 0,22 MB Datei.

Auf genau der Sorte Webspace, für die diese Anwendung gebaut ist, war die
Admin-Sicherung eines großen Kontos also **unmöglich** — kein theoretisches
Budget, ein Abbruch. Der Grund stand in einer Zeile:
`json_decode(edbak_build($userId), true)` — derselbe Bestand als Zeichenkette,
als Feld und beim Schreiben noch einmal als Zeichenkette.

### Web — Das Paket ist jetzt ein ZIP

Unversiegelt, denn es liegt serverseitig und trägt `pat_blob` ohnehin als
Chiffretext:

| Eintrag | Inhalt |
|---|---|
| `manifest.json` | Umfang, Schlüsselhüllen, Teileliste, **`geschuetzte`** |
| `kopf.json` | Stammdaten, Diensttage, Zahl der Einträge |
| `eintraege/0001.json` … | je 250 Einträge **ohne** Punktlisten |
| `spuren/0001.json` … | je Teil `{spur_ref, blob}` — SPUR1, Base64 |

Gepackt, anders als beim Nutzerformat: Dort sind die Teile bereits gzip *und*
verschlüsselt, hier ist es blankes JSON. Der Packlauf ist der Grund, aus dem
dieselben Daten in 11,42 statt 94,28 MB passen.

**Ein Umweg, den erst die Messung erzwungen hat.**
`ZipArchive::addFromString()` hält jede übergebene Zeichenkette bis zum
`close()` im Speicher — damit läge am Ende doch wieder alles gleichzeitig da.
Gemessen an 34,6 MB Inhalt, je eigener Prozess: `addFromString` **42,0 MB**
Spitze, `addFile` **2,0 MB**. Die Teile gehen deshalb einzeln in einen
Bauordner und von dort ins Archiv.

**`geschuetzte` im Manifest** ist keine Zierde. `edbak_paket_hat_geschuetzte()`
sah bis hierher in die Einsatzliste des Pakets; im gefensterten Kern steht sie
dort nicht mehr. Ohne die Zahl hätte die Funktion still `false` geliefert und
damit die Sperre aus E20 ausgehebelt: Ein Paket mit unlesbaren Angaben wäre
als „direkt einspielbar" durchgegangen.

**Und ein Fassung-2-Kern darf nicht durch den einteiligen Weg.** Er trägt
Nutzlast 8 — die Punkte stehen in eigenen Teilen. Wer ihn durch
`edbak_restore()` schickt, bekommt jeden Einsatz und keine einzige Spur.
Genau dieser Fall ist in F-S2-E einmal eingetreten und hat 91 208 Punkte
gekostet; deshalb gibt es `edbak_paket_einspielen()`, und
`edbak_paket_lesen()` verweigert Fassung 2 ausdrücklich.

### Web — Speichergrenze und Warnschwellen (E-S2-15)

Vorgabe **2 GB**, Warnschwellen **70 und 90 Prozent**, beides im Adminbereich
einstellbar. Geprüft wird **vor** dem Bau: abgelehnt mit Meldung, nie still
verdrängt (E-S2-14). Beim großen Konto spart das 14 Sekunden je abgelehntem
Lauf — bei „Alle sichern" 14 Sekunden je Konto.

**Die Zählung misst jetzt das ganze Verzeichnis.** Vorher zählte sie nur
Dateien, die das Paketnamensmuster bestanden; Begleitdateien, `.htaccess` und
liegengebliebene Reste eines abgebrochenen Laufs waren unsichtbar und
zählten trotzdem auf der Platte. Für eine Anzeige mag das angehen, für eine
Speichergrenze nicht. Was nicht in Paketen steckt, wird getrennt ausgewiesen,
damit ein auffälliger Rest auffällt statt in einer Summe unterzugehen.

**Neu: `smtp_eingerichtet()`.** E-S2-15 verlangt zwei verschiedene Wege — Mail
an die Admin-Adresse, „ohne eingerichtetes SMTP stattdessen dauerhafter
Hinweis im Admin-Bereich". Die Anwendung konnte diese beiden Fälle bis hierher
nicht unterscheiden: `smtp_send()` liefert `false` für „Host falsch",
„Passwort falsch", „Netz weg" **und** „gar nicht eingerichtet".

**Die Schwellen-Marke wird nach dem Versand gesetzt, nicht davor.** Das
einzige Vorbild im Haus — die wöchentliche Erinnerungsmail — macht es
umgekehrt und verwirft dabei den Rückgabewert. Für eine Erinnerung ist das
vertretbar (die nächste kommt in einer Woche); für eine Warnung, dass der
Speicher zuläuft, ist es falsch: Scheitert der Versand, stünde die Marke
trotzdem, und die Warnung käme **nie** — genau in dem Fall, in dem sie
gebraucht wird.

### Web — „Alle sichern" merkt sich, wie weit es ist

Der Auftrag läuft **in Schüben**: von der Schaltfläche, solange die Anfrage
Zeit hat, und vom neuen Wartungsjob `adminbackup` weiter. Solange er läuft,
steht sein Stand oben auf der Seite.

**Bis hierher gab es keinen Merkzettel.** Die Konten wurden nach dem Alter
ihrer letzten Sicherung sortiert, und der zweite Klick sollte deshalb von
selbst weitermachen — wer eben gesichert wurde, steht ja hinten. Das trug nur,
solange sich die Konten um mindestens einen ganzen Tag unterschieden:
Gerechnet wird in *Tagen*. Wer heute alle Konten sichert, hat danach lauter
Nullen, und bei Gleichstand ist die Reihenfolge beliebig — die letzten Konten
kamen unter Umständen nie dran.

Zugesagt ist jetzt etwas Belastbareres: **jedes Konto genau einmal**, und ein
Abbruch verliert höchstens das laufende (der Zeiger wird nach jedem Konto
fortgeschrieben).

**Die erste Fassung des Merkzettels ist an der Datenbank gescheitert, und
zwar lautlos.** Sie legte die Kennungen aller offenen Konten in
`app_state.adminbackup_auftrag`; die Spalte ist `varchar(190)`, bei 31 Konten
waren es 350 Zeichen. Das INSERT scheiterte, `edbak_marke_setzen()` schluckte
jeden Fehler, und die Schaltfläche meldete **„0 von 0 Konten gesichert"** —
eine Zahl ohne Bezug zur Wirklichkeit. Behoben in beide Richtungen: Der
Auftrag ist jetzt ein **Zeiger** (`{"cur":137,"ges":31,…}`, 64 Zeichen), und
`edbak_marke_setzen()` liefert `bool` und schreibt ins Fehlerprotokoll, statt
zu schweigen.

**Der Job-Rahmen misst jetzt auch den Speicher** (`jobs_speicher_knapp()`,
48 von 64 MB). Bis dahin zählte nur die Zeit; das reichte, solange jeder Job
in Blöcken über Zeilen lief. Der Sicherungsjob ist anders: Ein einzelnes Konto
kostet beim 5000er-Bestand 24 MB. Seine Reserve ist mit 15 s so groß, dass er
am Huckepack-Weg (3 s) gar nicht erst anfängt — eine Anfrage einer NutzerIn
soll keine fremde Sicherung mittragen.

**Automatisch entsteht nichts.** Der Job arbeitet nur auf Auftrag; nächtliche
Sicherungen je Konto sind ausdrücklich abgelehnt (E-S2-19).

### Web — Aufbewahrung 2 statt 3

Das Konzept nennt seit E-S2-14 die Zwei; Code und drei Dokumente standen auf
drei. Der Widerspruch ist zugunsten des Konzepts aufgelöst.

**Was das kostet:** Eine Installation, die die Einstellung nie angefasst hat,
verliert beim nächsten Sichern je Konto den ältesten von drei Ständen. Das
geschieht nicht still — die Rückmeldung nennt jede verdrängte Datei. Wer drei
behalten will, trägt drei ein; die Einstellung gibt es seit Web 9.8.0.

**Einteilige Pakete gehen beim ersten neuen Lauf mit** — aber erst, nachdem
das neue Paket geschrieben **und wieder gelesen** wurde. Ein ZIP, das sich
nicht öffnen lässt, ist genau der Fall, in dem man den alten Stand noch
braucht. Das jüngste Paket bleibt in jedem Fall stehen, auch wenn es Fassung 1
ist: eine Aufräumung, die aufräumt, bis nichts mehr da ist, wäre das Gegenteil
der Funktion.

### Web — Was der Umbau nebenbei repariert hat

- **`.tmp`-Reste blockierten dauerhaft die Ordnerlöschung.** Sie standen nicht
  auf der Weißliste von `edbak_ordner_loeschen()`, und damit ließ sich ein
  Konto nicht mehr vollständig löschen — an einem Rest, den die Anwendung
  selbst erzeugt hatte. Der Bauordner trägt jetzt ein erkennbares Präfix und
  wird vor dem Löschen mit aufgeräumt.
- **`edbak_ordner_loeschen()` löschte erst und meldete dann Fehlschlag.** Die
  Prüfung stand *in* der Schleife: Beim fünften Eintrag abzubrechen hieß, die
  ersten vier bereits gelöscht zu haben. Jetzt entscheidet ein erster
  Durchgang, ob überhaupt etwas gelöscht wird.
- **`edbak_groesse_text()` endete bei MB.** Die Grenze wird in GB angegeben;
  die Meldung hätte „2.048,0 MB von 2.048,0 MB" gelautet.
- **Der Rückgabewert von `edbak_begleit_schreiben()` wurde verworfen.** Die
  Sicherung lag dann zwar, aber das Verzeichnis kannte sie nicht — kein
  Datenverlust, aber eine Auskunft, die fehlt. Wird jetzt gemeldet.
- **`api/backup_spuren_restore.php` ist von 200 auf 96 Zeilen geschrumpft.**
  Sein Kern steht als `edbak_spuren_schreiben()` in `backup_lib.php`, weil die
  Admin-Sicherung ihn ebenfalls braucht. Ein zweiter Weg wäre ein zweiter Ort,
  an dem Eigentumsprüfung, Blobprüfung und das Überspringen vorhandener
  Spuren zu vergessen sind.
- **`install.php` prüft `ext/zip`** — die Sicherung ist jetzt ein ZIP; ohne
  die Erweiterung soll das vor der Einrichtung auffallen, nicht im ersten Lauf.

### Web — Die Freigabe war für niemanden zu sehen (F-S2-F)

Beim Prüfen des Freigabewegs kam heraus, dass er **nie funktioniert hat**. Der
Kasten „Für dich freigegebene Sicherung" blendet die Frage nach dem
Wiederherstellungsschlüssel aus, wenn das Paket keine geschützten Angaben
enthält — über eine Kennung `freigabecodelabel`, die es im Markup nicht gab.
`getElementById()` lieferte `null`, der Zugriff warf, und die Zeile darunter,
die den Kasten **sichtbar macht**, kam nie zur Ausführung. Der Fehler landete
im leeren `catch` von `freigabeLaden()`.

Auffallen konnte das nicht: Die Karte ist im Regelfall verborgen, und
*verborgen, weil es nichts gibt* sieht genauso aus wie *verborgen, weil das
Skript abgestürzt ist*.

Das wiegt schwer, weil der Freigabeweg der **einzige** ist, auf dem eine
Sicherung mit geschützten Angaben in ein neu aufgesetztes Konto kommt: Die
Administration darf es nicht (E20), nur die NutzerIn kann mit ihrem
Wiederherstellungsschlüssel umschlüsseln.

Behoben: Die Hülle trägt die Kennung, und der `catch` bleibt zur NutzerIn hin
still, schreibt aber in die Konsole — damit fällt ein solcher Fall dem
Bilderlauf auf, ohne dass jemand ohne Freigabe etwas merkt.

**Der Freigabeweg läuft jetzt auch in Fenstern.** Ein Fassung-2-Paket geht
nicht mehr in einer Antwort heraus; der Browser holt Kopf, Eintragsfenster und
Spurteile einzeln über `?teil=` und schickt sie über dieselben Endpunkte
zurück wie eine eigene Sicherung. Der Teilname wird gegen die Teileliste des
Manifests geprüft — was dort nicht steht, gibt es für diesen Weg nicht.

**Neu geprüft: der Zweig mit Wiederherstellungsschlüssel.**
`tools/freigabeprobe/` stellt sich ein Konto her, dessen Schlüssel sie kennt —
Hülle, Prüfsumme und Chiffretext entstehen dabei **im Browser** über
`assets/crypto.js`, PHP legt sie nur ab (ein zweiter Rechenweg wäre eine
zweite Umsetzung derselben Krypto, und die Probe prüfte dann sich selbst).
Belegt: Ein **falscher** Schlüssel wird abgewiesen und schreibt nichts; mit
dem richtigen kommen Einsatz und Spur an, der Chiffretext ist ein **anderer**
als in der Quelle, und er öffnet sich mit dem Schlüssel des **Zielkontos** zu
demselben Klartext.

**Eine Einschränkung, ausdrücklich:** Bei Fassung 1 wird vor dem ersten
Schreiben gefragt, wenn sich Einsätze mit dem angegebenen Schlüssel nicht
öffnen lassen. Bei Fassung 2 kann diese Zahl erst feststehen, wenn alle
Fenster geöffnet sind — also nach dem ersten Schreiben. Die schärfere Schranke
steht ohnehin davor: Der Wiederherstellungsschlüssel muss die Hülle
`pat_wrap_rc` öffnen. Einzelne Fehlschläge danach werden am Ende **genannt**,
mit Zahl und in Orange.

### Prüfstand

| Mittel | Ergebnis |
|---|---|
| `tools/wiederherstellungs-probe/` | **66** Erwartungen, 0 offen (vorher 44) — Teil 8 Rundlauf des Adminpakets, Teil 9 Speichergrenze und Schwellen |
| Freigabeweg im Browser | Fassung-2-Paket (600 Einträge, 3 Eintragsteile, 1 Spurteil) in ein frisches Konto: **600 Einsätze mit 600 Spuren**, 0 Konsolenfehler |
| „Alle sichern" über 31 Konten | **31 von 31** in 18,3 s, 0 Konsolenfehler |
| `tools/wiederherstellungs-probe/` nach AP6 | **76** Erwartungen, 0 offen (vor AP6: 44) |
| `spurprobe` · `gpxprobe` · `containerprobe` | 25 · 75 · 32 Erwartungen, je 0 offen |
| Wortliste · Vollständigkeit | 0 Treffer, **0 ungenutzte Ausnahmen** · 260 |
| Bilderlauf `34-`, `43-` | 16 Bilder, **16 verschiedene Prüfsummen**, 0 Überlauf, 0 Konsolenfehler |
| `tools/freigabeprobe/` **neu** | **14** Erwartungen, 0 offen — der Freigabeweg **mit** Wiederherstellungsschlüssel, samt Gegenprobe mit einem falschen |
| Speicher, 5000er-Konto | 1077,6 MB → **24,0 MB von 64**, mit Deckel geprüft |
| Bilderlauf `43-sicherungen` | 8 Bilder, **8 verschiedene Prüfsummen**, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px |

## [Web 11.1.1] — 2026-08-31

**Eine Datei mit Nutzlast 8 und Punktlisten verlor alle Spuren, ohne ein
Wort.** Fehlerbehebung, gefunden beim Aufbau des Prüfbestands für AP6
(F-S2-E). Keine Migration.

### Web — Was passiert ist

Seit Web 11.0.0 sagt Nutzlast 8 zu, dass die Spurpunkte **nicht** im Eintrag
stehen, sondern als eigene, versiegelte Teile nachkommen. `edbak_restore()`
entscheidet deshalb an der **Fassung**, welchen Weg es nimmt, und nicht am
Vorhandensein eines `track`-Feldes — mit gutem Grund: Eine Spur ohne Punkte
sähe genauso aus wie ein Verweis, und die umgekehrte Verwechslung brächte eine
echte Fassung-4-Sicherung um alle Spuren.

Die Kehrseite war nicht bedacht. Trägt eine Datei Fassung 8 **und**
Punktlisten, lief sie in den Verweisweg, fand keine `spur_ref` — und die
Punkte fielen weg. Der Eintrag entstand ohne Spur, die Meldung lautete
„fertig".

Solche Dateien schreibt die Anwendung nicht. Diese kam aus
`tools/messstand/vervielfaeltigen.py`: Das Werkzeug baut den 5000er-Bestand
aus der Referenz und hat die Fassungsnummer dabei **geerbt** — was stimmte,
solange die Referenz Nutzlast 7 war, und seit Web 11.0.0 nicht mehr stimmt.
**Gemessen an einem Lauf: 164 Einsätze angelegt, 91 208 Punkte verloren.**
Nach der Behebung derselbe Lauf: 66 848 Einsatzpunkte in der Datenbank.

### Web — Was geändert wurde

- **Die Anwendung sagt es.** Der Verweisweg meldet eine vorgefundene
  Punktliste über die gemeinsame Prüfschicht — dort, wo die Rückmeldung die
  abgelehnten Angaben ohnehin aufzählt. **Abgewiesen wird die Datei nicht:**
  Der übrige Bestand ist brauchbar, und ihn wegen der Spuren zu verweigern
  hieße, aus einem Teilverlust einen Totalverlust zu machen.
- **Das Werkzeug setzt die Fassung, statt sie zu erben.** Die erzeugte Datei
  ist einteilig und trägt ihre Punkte selbst — sie ist Nutzlast 7 und wird
  jetzt auch so ausgezeichnet. Mit NaDoku 1.0 fällt das Altformat weg; dann
  braucht der Vervielfältiger einen Container-Schreiber in Python
  (Backlog Nr. 46).

### Prüfstand

| Mittel | Ergebnis |
|---|---|
| `tools/wiederherstellungs-probe/`, **Teil 7 neu** | **44** Erwartungen, 0 offen (vorher 40) — darunter die Gegenprobe, dass eine richtige Fassung-8-Datei die Meldung *nicht* bekommt |
| Gegenprobe am Prüfkonto | vorher **0** Spurpunkte, nachher **66 848** |

## [Web 11.1.0] — 2026-08-31

**Auch der Kern wird mehrteilig.** Nachschlag zum sechsten Arbeitspaket der
Phase S2 (AP5b, E-S2-11, Z3). Nebennummer: Das Format bekommt Teile dazu, das
Datenmodell bleibt. **Keine Migration.**

### Web — Warum der Kern allein nicht reicht

Web 11.0.0 hatte die Punktlisten aus der Nutzlast geholt. Übrig blieb ein
`kern.edbak` — beim 5000er-Bestand 10,5 MB, und der ging auf dem Rückweg als
**ein** POST an den Server. Gegen ein Limit, das niemand kennt: nginx nimmt in
der Vorgabe 1 MB. Damit wäre die Sicherung genau dort nicht wieder
einzuspielen gewesen, wo sie herkam.

Der Speicher kommt dazu, wenn auch weniger dramatisch als gedacht.
**Gemessen** am 31.08.2026 (`memory_get_peak_usage(true)`, PHP-CLI):

| Bestand | Kern am Stück | in Fenstern zu 250 |
|---|---|---|
| Demo, 187 Einträge | 0,18 MB Text, **4,0 MB** Spitze | 0,17 MB größtes Fenster, **4,0 MB** |
| Messstand, 10 797 Einträge | 10,47 MB Text, **39,5 MB** Spitze | 0,44 MB größtes Fenster, **10,0 MB** |

Am Stück wächst die Spitze mit dem Bestand — rund 3,3 kB je Eintrag, aus den
zwei Messpunkten fortgeschrieben; das Budget von 64 MB (Z3) wäre bei etwa
18 000 Einträgen erreicht. In Fenstern wächst sie kaum.

**Eine Zahl war dabei falsch weitergetragen worden.** In der Begründung dieses
Pakets stand zunächst „92 MB gegen ein Budget von 64" — das ist die Zahl aus
Web 11.0.0, *vor* den Fenstern der Kindtabellen. Nachgemessen am
tatsächlichen Stand von 11.0.0 waren es 37,5 MB. Der Umbau ist trotzdem
richtig, aber aus dem POST-Grund und wegen des Wachstums, nicht wegen eines
Abbruchs, den es nicht gab.

### Web — Die neue Aufteilung

| Eintrag | Inhalt |
|---|---|
| `manifest.edbak` | Teileliste mit SHA-256 je Teil, Sicherungskennung, Erzeugungszeit, **`unlesbar`** |
| `kopf.edbak` | Stammdaten, Diensttage, `eintraege_gesamt` |
| `eintraege/0001.edbak` … | je 250 Einträge (Einsätze **und** Ruhesegmente) ohne Punktlisten |
| `spuren/0001.edbak` … | je Teil eine Liste `{spur_ref, blob}` — unverändert |

**250 Einträge je Fenster**, weil `client_max_body_size` bei nginx in der
Vorgabe auf 1 MB steht und der Rückweg genau diese Fenster als POST
zurückschickt: 500 ergäben ein größtes Fenster von 0,87 MB — unter der Grenze,
aber ohne Reserve; 250 ergeben 0,44 MB in 44 Anfragen.

`kern.edbak` gibt es nicht mehr. Eine solche Datei wird beim Öffnen mit Namen
abgewiesen; sie kann nur im Werkstattbestand liegen, denn Web 11.0.0 ist nie
ausgeliefert worden.

### Web — Die Rückfrage kam, wo es nichts zu fragen gab (F-S2-D)

Vor dem Einspielen warnt die Anwendung, wenn eine Sicherung Einsätze enthält,
deren geschützte Angaben beim *Erstellen* nicht zu entschlüsseln waren: Die
kommen als Chiffretext an und bleiben hier unlesbar. Beim Altformat wurden sie
gezählt. Bei Fassung 4 liegen die Einträge zum Zeitpunkt der Frage noch
versiegelt in ihren Teilen — die Frage steht aber vor dem ersten Schreiben und
muss dort bleiben. Sie kam deshalb, sobald die Datei aus einem *anderen* Konto
stammte, und das ist der Regelfall des Einspielens.

Der Erzeuger weiß die Zahl, er hat sie eben gezählt; sie steht jetzt als
`unlesbar` im Manifest. Fehlt sie, wird weiter gefragt — „nicht erhoben" ist
etwas anderes als „keine".

**Wie es aufgefallen ist:** Der Kreislauftest lief 300 Sekunden ins Leere. Sein
Browser verneinte die Frage stillschweigend, und das Werkzeug wartete auf
Wörter („fertig", „eingespielt"), die im Abbruchtext nicht vorkommen. Danach
hätte es ein leeres Konto exportiert und verglichen. Beides ist geändert: Der
Abbruch ist jetzt eine Meldung wie jede andere, und die Werkzeuge warten auf
**die Meldung**, nicht auf einen Wortlaut.

### Web — Zwei native `confirm()` sind gegangen

Die beiden Rückfragen des Sicherungsbereichs benutzten noch `window.confirm`.
Browser bieten dort „keine weiteren Dialoge dieser Seite anzeigen" an — genau
der Grund, aus dem es `assets/confirm.js` gibt. Sie laufen jetzt über
`window.edConfirm` wie alle anderen.

### Web — Eine Schranke gegen die stille Lücke

Die Exportschleife rückt um `FENSTER` weiter, gleichgültig wie viele Einträge
zurückkamen. Lieferte ein Fenster weniger, fehlten diese Einträge in der Datei
— und die Meldung am Ende lautete trotzdem „Fertig". Der Browser zählt jetzt
nach und bricht mit Namen und Zahl ab. Der Abrufendpunkt weist eine zu große
`anzahl` ohnehin mit 400 ab; die zweite Schranke hängt nicht davon ab, dass
die erste bleibt.

### Prüfstand

| Mittel | Ergebnis |
|---|---|
| Kreislauf `edbak` (Fassung 4 → frisches Konto → Fassung 4) | **252 882** Einzelvergleiche, **0** unerklärt, 16 erwartet |
| Kreislauf `edbak-alt` (Altformat → Fassung 4) | **287 282** Einzelvergleiche, **0** unerklärt, 560 erwartet |
| Kreislauf `csv` | **8 797** Einzelvergleiche, **0** unerklärt, 859 erwartet |
| `tools/containerprobe/` (PHP → Chromium → Python) | **32** Erwartungen, 0 offen; 9 000 Punktvergleiche; jetzt mit **zwei** Eintragsteilen |
| `tools/wiederherstellungs-probe/` | **40** Erwartungen, 0 offen |
| `tools/spurprobe/` | **25** Erwartungen, 0 offen |
| Speicher am Messstand | 39,5 MB am Stück → **10,0 MB** in Fenstern, von 64 |
| **Abnahme am 5000er-Bestand** (5002 Einsätze, 10 797 Einträge) | Sichern 44,8 s · Halde 45 MB · größte Zeichenkette **2,30 MB** (vorher 9,39) · PBKDF2 1 · Datei 10,48 MB |
| Rundlauf desselben Bestands | 10 431 Spuren mit **2 108 077 Punkten** in 54 Teilen · **10 991 557 Einzelvergleiche, 0 unerklärt** · 0 Konsolenfehler |

## [Web 11.0.0] — 2026-08-31

**Die Sicherung wird mehrteilig.** Sechstes Arbeitspaket der Phase S2
(E-S2-10 bis E-S2-12). Hauptnummer, weil das Dateiformat wechselt — der erste
Wechsel seit Web 5.0.0. **Keine Migration:** Das Format der *Datei* ändert
sich, das Datenmodell nicht.

### Web — Warum

Eine Sicherung mit 5000 Einsätzen trägt rund drei Millionen Spurpunkte. Bis
hierher entstand sie als **eine** Zeichenkette im Browser und ging als **ein**
POST zurück. Beides sprengt jedes Budget, das ein Telefon oder ein einfacher
Webspace hat — und zwar an der Stelle, an der jemand ohnehin schon beunruhigt
ist.

### Web — Containerfassung 4

Die `.edbak`-Datei ist jetzt ein ZIP (gespeichert, nicht gepackt — die Teile
sind bereits gzip und verschlüsselt):

| Eintrag | Inhalt |
|---|---|
| `manifest.edbak` | Teileliste mit SHA-256 je Teil, Sicherungskennung, Erzeugungszeit |
| `kern.edbak` | die Nutzlast **ohne** Punktlisten; je spurtragendem Objekt eine `spur_ref` samt Stufe, `n_original` und `n` |
| `spuren/0001.edbak` … | je Teil eine Liste `{spur_ref, blob}` — SPUR1, Base64, Ziel 2 MB |

**Jedes Teil kennt seinen Platz.** Die Zusatzdaten der Verschlüsselung (AAD)
binden Sicherungskennung, Teilname und Nummer:

```
Manifest   EDBAK4|manifest
jedes Teil EDBAK4|<sicherungskennung>|<name>|<nr>/<gesamt>
```

Sie stehen **hinter** dem Kopf, nicht an seiner Stelle — der Kopf bleibt
gebunden wie bisher, der Platz kommt dazu. Damit fällt ein fehlendes,
doppeltes, vertauschtes oder aus einer **anderen** Sicherung stammendes Teil
schon beim Öffnen auf. Ohne diese Bindung ließe sich ein fremdes Spurteil
unterschieben: Mit demselben Passwort ginge es klaglos auf und brächte den
Bestand eines anderen Kontos mit. Das Muster ist von Cryptomator und age
abgeschaut, wo der Blockindex aus demselben Grund in die Zusatzdaten wandert.

**Das Fassungsbyte eines Teils ist 0x04**, obwohl der Aufbau derselbe ist wie
bei Fassung 3. Grund: Die Zusage „AAD = die ersten 13 Bytes" stimmt für ein
Teil nicht mehr. Wer ein Teil einzeln öffnet — von Hand, mit dem
Python-Rezept —, bekäme mit 0x03 die Meldung für ein falsches Passwort und
suchte den Fehler an der falschen Stelle.

**Eine PBKDF2 je Vorgang.** Salz und Rundenzahl stehen in allen Teilen gleich;
abgeleitet wird einmal. Bei zwölf Teilen wären es sonst zwölf Ableitungen zu
je 320 000 Runden — auf einem gedrosselten Telefon eine knappe Minute reines
Warten, und zwar zweimal.

### Web — Sichern: Kern, Spurteile, ZIP

Der Sicherungslauf im Browser besteht jetzt aus fünf Schritten statt einem:

1. **Kern holen** — `api/backup_data.php?ohne_spuren=1` liefert dieselbe
   Nutzlast ohne Punktlisten. Gemessen am Demo-Bestand: **183 878 statt
   2 248 092 Byte, also 8,2 %**.
2. **Geschützte Angaben entschlüsseln** — unverändert.
3. **Teile planen.** 250 000 Punkte je Teil, geschnitten an Spurgrenzen. Die
   Einteilung steht *vor* dem ersten Abruf fest, weil die Zusatzdaten jedes
   Teils `<nr>/<gesamt>` tragen — die Gesamtzahl muss also bekannt sein, bevor
   das erste Teil versiegelt wird. Die Punktzahl je Spur steht im Kern; damit
   lässt sich das ausrechnen, ohne einen Blob geholt zu haben.
4. **Blobs holen** — `api/backup_spuren.php`, 25 Kennungen je Anfrage.
5. **Versiegeln und ins ZIP**, Manifest zuletzt (es kennt dann alle
   Prüfsummen). Fortschritt je Teil in der Zustandszeile.

**Der neue Endpunkt reicht durch, er packt nicht aus.** Die Fallunterscheidung
steht in `spur_lib.php` (`spur_fuer_sicherung_viele()`) und nur dort:

| Lage | was geschieht |
|---|---|
| kein Zeilen, Blob da | Blob durchreichen, so wie er liegt |
| Zeilen da (Stufe 1, oder Nachzügler zu Stufe 2) | zusammensetzen und **verlustfrei neu kodieren** |
| Stufe 3 **mit** Zeilen | abgelehnt und benannt — der ausgedünnte Blob nummeriert nach Position, die Nachzügler tragen Originalnummern; die Vereinigung ist keine Spur |
| Lücke in den Nummern | abgelehnt und benannt — `spur_kodieren()` speichert die Nummer nicht, die *Position* ist die Nummer |
| über 50 000 Punkte | abgelehnt und benannt — der Rückweg nähme sie nicht an |

Gelesen wird **Spur für Spur**, nicht der ganze Block auf einmal: 25 Spuren an
der Obergrenze wären rund 350 MB gegen ein Budget von 64 MB. Am
Referenzbestand mit höchstens 1133 Punkten je Spur fiele das nie auf.

**Die Zusage „die Sicherung kodiert nicht neu" gilt so nicht mehr**, und das
ist eine Entscheidung: Eine Spur, die noch als Zeilen liegt (Stufe 1), steht
in der Datei als Stufe-2-Blob. Ohne das müsste die Datei zwei Spurformen
führen und zwei Rückwege haben, und der ganze Mengengewinn entfiele für
frische Bestände — gerade die mit den meisten Punkten. Der Bestand selbst
bleibt unangetastet; `docs/Backup-Format.md` sagt das jetzt so.

### Web — Wiederherstellen: erst der Kern, dann die Spuren

Der Kern geht als ein POST zurück wie bisher; der Server liefert die
**Spurkarte** mit — `spur_ref` → angelegter Datensatz. Danach gehen die Blobs
in Häppchen von höchstens 1,5 MB an `api/backup_spuren_restore.php`.

Drei Dinge, die dabei nicht fehlen dürfen:

- **Eigentum wird geprüft**, obwohl die Kennungen aus der Antwort dieses
  Servers stammen. Wer sich darauf verlässt, dass der Browser nur zurückgibt,
  was er bekommen hat, hat eine Schnittstelle gebaut, die fremde Spuren
  überschreibt.
- **Der Blob wird geprüft, bevor er liegt** (`spur_blob_pruefen()`): Kopf,
  Auflösung, Punktzahl gegen die Grenze, Auspacken, Punktzahl gegen den Kopf,
  Wertebereiche. Für Punktlisten gibt es diese Schicht seit je; ohne sie wäre
  der Blobweg die einzige Stelle der Anwendung, an der ungeprüfter
  Binärinhalt in die Datenbank ginge (CLAUDE.md 4).
- **Vorhandene werden übersprungen.** `spur_blob_schreiben()` ist ein Upsert
  und überschreibt — an seiner Stelle richtig, hier falsch: Eine abgebrochene
  Wiederherstellung soll sich fortsetzen lassen.

**Ein Fehler, den der Kreislauf gefunden hat.** Die Höhe des Einsatzortes
(`site_ele_m`) wird nach dem Einspielen aus der Spur berechnet. Bei Nutzlast 7
lagen die Punkte da schon; bei Fassung 4 kommen sie erst danach — der
Kernlauf hatte eine Spur ohne Punkte vor sich und trug nichts ein. **79 von
87 Einsätzen** kamen ohne Höhe zurück, obwohl die Quelle sie hatte. Kein
Datenverlust (die Angabe ist abgeleitet), aber ein stiller Unterschied
zwischen Sicherung und Wiederherstellung — und genau die sucht ein Kreislauf.
Die Berechnung läuft jetzt, wo die Punkte ankommen.

### Web — Das Altformat wird gelesen, nicht mehr geschrieben

Fassung 2 und 3 bleiben lesbar; sie sind der Weg, auf dem ein vorhandener
Bestand einmal herüberkommt. **Mit NaDoku 1.0 wird das Altformat abgeschafft**
(Entscheidung vom 31.08.2026) — es steht als Backlog-Eintrag, damit es nicht
stillschweigend ewig mitläuft.

Umgekehrt gilt weiter, was E-S1-07 sagt: Eine ältere Installation liest eine
Fassung-4-Datei **nicht** — sie sieht ein ZIP, keine `EDBAK2`-Signatur, und
sagt das auch.

### Web — Drei Antworten statt einer

Beim Einspielen entscheidet jetzt `EdCrypto.dateiArt()`: `zip` (mehrteilig),
`edbak` (einteilig), `teil` (ein Stück, das jemand aus dem Archiv gelöst hat)
oder nichts davon. Ein einzeln gewähltes Teil bekommt deshalb den Satz, der
weiterhilft, statt „Passwort falsch oder Datei beschädigt".

Dazu ein Wandler für **große** Bytefolgen: Der vorhandene
(`String.fromCharCode(...bytes)`) breitet jedes Byte als eigenes Argument aus
und wirft ab einigen zehntausend „Maximum call stack size exceeded". Gemessen:
Bei 2 MB scheitert er, der neue trägt sie.

### Web — Der Kern hat es zuerst nicht geschafft

Die Spuren waren gelöst, der **Kern** nicht. Gemessen am 5000er-Bestand:

| | Kern | PHP-Speicherspitze |
|---|---|---|
| vorher (Punktlisten in der Nutzlast) | 94,3 MB | **1076 MB** |
| Fassung 4, erster Stand | 10,5 MB | **92 MB** |
| Fassung 4, nach dem Umbau | 10,5 MB | **37,5 MB** |

Mit `php -d memory_limit=64M` brach der erste Stand ab:

```
PHP Fatal error: Allowed memory size of 67108864 bytes exhausted
                 in server/backup_lib.php on line 82
```

Auf einem Webspace mit 64 MB — genau der Sorte, für die diese Anwendung
gebaut ist — wäre die Sicherung eines 5000er-Kontos gescheitert.

**Zwei Eingriffe, kein Formatwechsel:**

1. **Je Eintrag kodieren und freigeben.** Vorher lagen drei Kopien desselben
   Bestands im Speicher: die Zeilen aus der Datenbank, das zusammengesetzte
   Feld und die JSON-Ausgabe. Jetzt wird jeder Einsatz sofort zu JSON und
   seine Zeile weggeworfen. **92 → 75,5 MB.**
2. **In Fenstern zu 500 statt über das ganze Konto.** Die vier Abfragen für
   Phasen, Rettungsmittel, Reanimation und abweichende Besatzung liefen über
   *alle* Einsätze, und ihre Ergebnisse lagen gleichzeitig da. Sie bleiben
   gebündelt — das N+1 aus M5-12 kommt nicht zurück —, laufen aber je Fenster:
   elf Durchgänge zu vier Abfragen statt vier über alles. **75,5 → 37,5 MB.**

Am Dateiformat ändert sich dabei nichts; nur die Reihenfolge der Schlüssel im
Kopf ist eine andere, und JSON kennt keine Reihenfolge. Die drei Kreisläufe
messen es nach.

### Web — Zwei Fehler, die erst der 5000er-Bestand gezeigt hat

**Eine Mengengrenze, an zwei Orten, die nicht zusammenpassten.** Der Browser
bündelte die Spurteile für den Rückweg nach **Größe** (1,5 MB), der Endpunkt
deckelt nach **Anzahl** (500). Kurze Ruhespuren sind so klein, dass in 1,5 MB
weit mehr als 500 passen — das Einspielen brach ab mit „Höchstens 500 Spuren
je Anfrage". Die Meldung war richtig, das Bündeln war falsch. Jetzt bündelt
der Browser nach beidem; dass die zwei Zahlen gleich bleiben, hält die
Wiederherstellungsprobe fest.

**Und die Wiederaufnahme trug nicht.** Das ist der schwerere der beiden.
Bricht das Einspielen zwischen Kern und Spurteilen ab, ist beim zweiten
Anlauf jeder Eintrag „bereits vorhanden" — und die Spurkarte blieb dann
**leer**, weil sie nur beim Anlegen gefüllt wurde. Alle Spuren meldeten „ohne
zugehörigen Einsatz" und wären **nie mehr einzuspielen** gewesen, außer man
löscht den halben Bestand von Hand. Gesehen an 10 431 Spuren.

Die Karte wird jetzt auch für übersprungene Einträge gefüllt. Nachgewiesen,
indem ein Abbruch nachgestellt wurde: 4636 Spuren gelöscht, dieselbe Datei
erneut eingespielt → **4636 Spuren übernommen**, alles andere übersprungen.

### Web — Belegt

Neu: **`tools/containerprobe/`** — sie hält Fassung 4 gegen **drei
unabhängige Umsetzungen**, dieselbe Linie wie die GPX-Probe in AP4:

| Wer | Was |
|---|---|
| PHP | `spur_lib.php` kodiert echte SPUR1-Blobs |
| Browser | `crypto.js` + `zipjs.min.js` versiegeln und packen — im **echten Chromium** |
| Python | `vergleich/lesen.py` öffnet, entsiegelt und dekodiert wieder |

| Prüfung | Zahl |
|---|---|
| `containerprobe/probe.mjs` | **31 Erwartungen, 0 nicht erfüllt** |
| Punkt für Punkt PHP → Browser → Python | **9000 Einzelvergleiche, 0 Abweichungen** |
| Eine PBKDF2 je Vorgang | ein Salz, eine Rundenzahl über alle Teile; 51 ms für die eine Ableitung |
| Die Bindung der Teile | vertauscht · falsche Nummer · fremde Sicherung · verfälschtes Byte · falsches Passwort — **je abgewiesen** |
| Gegenprobe zur Bindung | dasselbe fremde Teil geht **mit seiner eigenen Kennung** auf — der Unterschied liegt an der Bindung, nicht am Schlüssel |
| Beide Sicherungen tragen einzeln | Prüfsumme allein: gefangen · Zusatzdaten allein (Manifest passend nachgezogen): gefangen |
| Schadensfälle am Archiv | fehlendes Teil · vertauschte Teile · fremdes Teil · verfälschtes Teil · überzählige Datei · kein Manifest — **je benannt** |
| ZIP ohne Kompression | Verfahren je Eintrag **0 (gespeichert)**, alle vier |
| Base64 für 2 MB | 2 796 204 Zeichen im Rundlauf; der alte Wandler scheitert daran |
| Altformat unverändert lesbar | Referenzdatei Fassung 3, **87 Einsätze, 443 Punkte im ersten** |
| **Kreislauf `edbak` in Fassung 4** (sichern → einspielen → sichern) | **252 882 Einzelvergleiche, 0 unerklärt** (16 erwartet) |
| Kreislauf `csv` | 8797 Einzelvergleiche, **0 unerklärt** (859 erwartet) |
| Der Rückweg schreibt Blobs, keine Zeilen | 181 Blobs mit **48 981 Punkten**, **0 Zeilen** in `track_points` |
| Neue Datei gegen die alte Nutzlast, Punkt für Punkt | **244 905 Einzelvergleiche, 0 Abweichungen** |
| Sicherung des Demo-Bestands im Browser | 87 Einsätze, 100 Ruhesegmente, 181 Spuren mit 48 981 Punkten — **212,9 kB in 0,2 s, 0 Konsolenfehler** |
| Kern ohne Spuren gegen Kern mit Spuren | **183 878 statt 2 248 092 Byte (8,2 %)** |
| spurprobe · jobprobe · ingestprobe · gpxprobe · Wiederherstellungsprobe | 25 · 24 · 24 · 75 · **38** Erwartungen, **je 0 nicht erfüllt** |
| **Der 5000er-Bestand, Drossel 6×** | | |
| Sicherung erstellen | **43,6 s** (vorher 109,8) · Halde **58 MB** (508) · größte JSON-Zeichenkette **9,39 MB** (138,25) · PBKDF2 **1** · Datei **10,42 MB** (40,5) |
| Alle vier Z3-Überschreitungen der Ausgangsmessung | **weg** — JSON ≤ 10 MB ✓, Halde ≤ 100 MB ✓, PBKDF2 = 1 ✓, Datei ≤ 25 MB ✓ |
| Serverseitige Spitze beim Kernbau | **37,5 MB von 64** (vorher 1076 MB) — scharf mit `memory_limit=64M` gefahren |
| Wiederherstellung des 5000er-Bestands | 5002 Einsätze, 5795 Ruhesegmente, 915 Diensttage, **10 431 Spuren mit 2 108 077 Punkten in 9 Teilen** |
| Vergleich danach | **10 991 557 Einzelvergleiche, 0 unerklärt** |
| Wiederaufnahme nach nachgestelltem Abbruch | 4636 Spuren gelöscht → **4636 wieder eingespielt**, alles andere übersprungen |

> **Eine Zahl, die etwas anderes maß, als ihre Beschriftung sagte** — und
> deshalb ersetzt wurde: Die erste Fassung der Prüfung „das ZIP packt nicht
> noch einmal" verglich Dateigrößen und schlug fehl (+57,7 %). Bei drei Teilen
> zu 500 Byte ist der ZIP-Rahmen größer als jede Ersparnis; gemessen war der
> Rahmen, nicht das Verfahren. Jetzt wird das Verfahren je Eintrag gelesen.

> **Zwei Fehler, die die Prüfmittel gefunden haben — beide meine:**
>
> - `spur_umriss()['gesamt']` meint „höchste Punktnummer + 1", nicht
>   „gespeicherte Punkte". Bei einer ausgedünnten Spur sind das 443 statt 148.
>   Der Kern hätte für jede ausgedünnte Spur eine Punktzahl genannt, die es in
>   ihr nicht gibt. Aufgefallen beim Nachmessen gegen den Demo-Bestand.
> - Die Höhe des Einsatzortes fiel beim Wiederherstellen weg (oben).
>   Aufgefallen im Kreislauf, an 79 Zeilen `site_ele_m: 901 → —`.

**Nicht geprüft:** andere Browser als Chromium (WebKit und Gecko stehen in
dieser Umgebung nicht zur Verfügung) · die Admin-Sicherungen, die noch das
alte Format schreiben (AP6) · ein Abbruch **mitten in einer Anfrage** (der
nachgestellte Abbruch löscht Spuren zwischen zwei vollständigen Läufen) ·
echte Hardware statt CPU-Drossel.

## [Web 10.3.0] — 2026-08-31

**Eine Spur lässt sich jetzt einzeln herunterladen — und mehrere ausgewählte
als eine Datei.** Fünftes Arbeitspaket der Phase S2, und damit ist Backlog
Nr. 3 erledigt. **Keine Migration.**

### Web — Drei Wege zur Datei

- **Je Einsatz:** ein Eintrag „Spur als GPX" im vorhandenen Aktionsmenü der
  Einsatzansicht — nur, wenn es eine Spur gibt.
- **Je Einsatz und je Ruhesegment:** die neue Seite **„Spuren des
  Diensttages"**, erreichbar aus dem Aktionsmenü des Tages. Sie zeigt die Karte
  des Tages und darunter jede Spur als eigene Zeile: nummeriert wie in der
  Tagesansicht, mit Stufe, Punktzahl und Abruf — **chronologisch**, wie der
  Tag verlaufen ist, und nicht nach Art gruppiert. Wer auf eine Zeile zeigt,
  sieht auf der Karte, welche Linie gemeint ist; ein Klick zoomt auf sie.
- **Mehrere auf einmal:** ein Kästchen je Zeile derselben Seite und eine
  Sammelleiste unten — die ausgewählten Spuren kommen als **eine** Datei.

**Die Seite war nötig, nicht schmückend.** Ruhesegmente hatten in der
Oberfläche bis hierher überhaupt keine Identität: nur eine schwarze Linie auf
der Tageskarte, ohne Zeile, ohne Popup — und `api/day.php` liefert nicht einmal
ihre Kennung. Ein Knopf je Ruhesegment hätte nirgendwo hingekonnt. Die
Abnahme verlangt den Abruf aber ausdrücklich „je Einsatz **und** je
Ruhesegment".

### Web — Mehrere Spuren, eine Datei

Wer eine ganze Schicht in ein Kartenprogramm ziehen will, lud bis hierher
zwölf Dateien einzeln herunter und sortierte sie dort wieder zusammen. Auf der
Spurenseite trägt jetzt jede Zeile ein Auswahlkästchen; die Sammelleiste
darunter sagt, wie viele ausgewählt sind, und lädt sie als eine Datei.

**Sie werden nicht zusammengeklebt.** Jede Spur bleibt in der Datei ein
eigenes `<trk>` — GPX 1.1 erlaubt das ausdrücklich (`maxOccurs="unbounded"`).
Zwei Spuren in *ein* `<trkseg>` geschrieben ergäbe eine Datei, die jedes
Kartenprogramm klaglos öffnet und in der es eine gerade Linie quer über das
Land zieht, vom Ende der einen zum Anfang der nächsten — einen Weg, den
niemand gefahren ist. Auch mehrere `<trkseg>` in *einem* `<trk>` wären falsch:
Die meinen Abschnitte **einer** Aufzeichnung mit einer Lücke dazwischen.

Drei Entscheidungen, die dabei fielen:

- **Kein neuer Baustein.** Das Kästchen sitzt in `ui_zeile(['vorn' => …])`,
  die Leiste ist `ui_speichern_leiste()` — dieselben zwei Bausteine, mit denen
  die NutzerInnen-Liste seit P3/O9b ihre Sammelaktion baut. Kein neues CSS.
- **Beide folgen der Zeit.** Die Liste stand zuerst nach Art gruppiert — erst
  alle Einsätze, dann alle Ruhezeiten, die Reihenfolge der beiden Abfragen im
  Code. So liest sich kein Diensttag: Er verläuft in *einer* Folge. Seite und
  Datei sortieren jetzt beide nach Beginn, Art und Kennung, also gleich; die
  laufende Nummer der Einsätze und die Farben auf der Karte zählen weiter nur
  die Einsätze durch.
- **Streng bei der Form, nachsichtig beim Bestand.** Was nicht genau
  `mission-<Zahl>` oder `rest-<Zahl>` ist, kommt nicht von dieser Seite: 400.
  Eine wohlgeformte Kennung dagegen, die zu diesem Tag und diesem Konto nicht
  gehört, fällt beim Lesen heraus, ohne dass die ganze Datei scheitert — sie
  kann aus einem Tab stammen, der seit einer Löschung offen steht. Wie viele
  Spuren wirklich drin sind, sagen der Dateiname
  (`diensttag_2026-05-10_2-spuren_original.gpx`) und das `<desc>` im Kopf.
  Bleibt nichts übrig, ist es doch ein Fehlgriff: 404, und er zählt.

**Eine Mengengrenze gibt es jetzt doch** — hundert Spuren je Abruf, und zwar
wegen des Speichers, nicht wegen der Rechte: Die Datei entsteht vollständig im
Arbeitsspeicher, weil ihre Länge in die Kopfzeile gehört. Gemessen mit der
größten Spur des Referenzbestands (1063 Punkte von 9581 Spuren, im Mittel 196)
kosten hundert Spuren **9,7 MB Datei bei 23,4 MB Spitze** — im Budget von
64 MB (Z3). Die *Punkte* hält dabei immer nur eine Spur: `gpx_bauen_viele()`
nimmt einen Generator und keine Liste, sonst lägen alle gleichzeitig im
Speicher (rund 4 MB je dekodierter Spur, S2/AP3).

### Web — Die erste Datei, die dieser Server ausliefert

Alle übrigen Downloads der Anwendung entstehen im Browser, aus einem Blob. Das
ist kein Zufall, sondern eine Folge der Ende-zu-Ende-Verschlüsselung: Ihr
Inhalt ist chiffriert, der Server **kann** ihn nicht zusammensetzen.

Für eine Spur gilt das nicht. Spurpunkte sind Klartext, und die Stufe, die
E-S2-09 sichtbar verlangt, kennt ohnehin nur der Server. Der Browser hätte
beides nicht: `api/mission.php` liefert die Spur als bloße Paare `[lat, lon]` —
ohne Höhe, ohne Zeit, ohne Stufe. Ein browsergebautes GPX bräuchte also einen
neuen, breiteren Abrufweg, nur um anschließend zusammenzusetzen, was auf dem
Server schon beieinander liegt.

Den Ausschlag gibt ein Sicherheitsargument: Der **Dateiname** landet im
Downloadordner, in einem Backup, vielleicht in einer Mail. Serverseitig gebaut
**kann** er keine geschützte Angabe tragen — der Server kann Diagnose, Alter
und Einsatzort nicht lesen. Browserseitig gebaut könnte er es, und das wäre ein
neuer Weg, auf dem Klartext das Haus verlässt.

### Web — Die Kennzeichnung steht an drei Stellen

E-S2-09 verlangt, dass sichtbar ist, ob die Datei die Originalspur trägt oder
die ausgedünnte. Sie steht deshalb:

- in der Datei, als `<desc>` in `<metadata>` **und** in `<trk>` — mit Zahl:
  „ausgedünnt — 113 von ursprünglich 443 Punkten (Douglas-Peucker, 2 m
  waagerecht / 3 m senkrecht)";
- im **Dateinamen** (`einsatz_000001_2026-01-17_0605_ausgeduennt.gpx`) — das
  ist die einzige Kennzeichnung, die das Verschieben in einen anderen Ordner
  überlebt;
- **auf der Seite**, vor dem Herunterladen. Eine Auszeichnung, die nur in der
  Datei steht, sieht erst, wer sie schon hat.

**Die Reihenfolge im GPX ist nicht frei.** GPX 1.1 beschreibt die
Kindelemente als `xsd:sequence`: `<desc>` steht in `<metadata>` **vor**
`<time>` und in `<trk>` zwischen `<name>` und `<trkseg>`. Wer sie hinten
anhängt, schreibt eine Datei, die manche Programme klaglos lesen und andere
ablehnen — und die gegen das Schema durchfällt.

### Web — Drei Schranken, die es nicht gibt, und eine, die dazukam

Beim gegnerischen Gegenlesen des Entwurfs kamen vier Fragen auf. Drei sind
begründet verneint, eine hat zu einer Änderung geführt:

- **Keine A9-Schranke wie im Export.** Der Export verweigert Spurpunkte, solange
  der Haken „personenbezogene Angaben" fehlt — weil ein Export *ohne* diese
  Angaben eine Datei zum Weitergeben ist. Beim Einzelabruf gibt es diese anonyme
  Fassung nicht; es gäbe keinen Haken zu umgehen.
- **Keine Sperre auf den Inhaltsschlüssel.** Die Einsatzansicht zeichnet
  dieselbe Spur bereits auf ihre Karte, ohne dass jemand entsperrt haben muss.
  Eine Sperre wäre Theater: Sie verweigerte die Datei und zeigte den Weg
  daneben weiter an. Dass die Spur überhaupt unverschlüsselt liegt, ist
  **Backlog Nr. 43** und gehört dorthin.
- **Keine Mengengrenze je Anfrage** — es ist eine Spur je Abruf. Dafür ein
  **Ratenschutz auf Fehlgriffe**: Ein gelungener Abruf geht nicht aufs
  Kontingent, sonst träfe die Bremse die Spurenseite eines Tages mit zwölf
  Einträgen.
- **Geändert: Der Abruf liegt nicht mehr unter `api/`.** `ist_api_aufruf()`
  entscheidet allein am Pfad — enthält er `/api/`, bekommt eine abgelaufene
  Sitzung JSON 401 statt der Anmeldeseite. Das stimmte, solange nichts in der
  Oberfläche nach `api/` **verlinkte**; der GPX-Abruf ist der erste `<a href>`,
  den eine Nutzerin selbst anklickt. Nach einer Mittagspause hätte sie
  `{"error":"session_ende"}` im Fenster gesehen.

Dazu zwei Nachbesserungen am Text: Der Eintrag in der Einsatzansicht **fragt
zurück**, bevor er herunterlädt — wie der große Export es tut; und der Hinweis
über der Liste nennt jetzt auch **Ruhespuren**, die den Aufenthalt der
Besatzung zwischen den Einsätzen zeigen.

### Web — Ein Fund beim Einchecken: Git hätte das Schema verändert

Die `.gitattributes` setzen `* text=auto eol=lf`. Das vendorierte GPX-XSD hat
**788 CRLF** — Git hätte sie beim Einchecken auf LF umgeschrieben, und die
Datei wäre statt 26 665 nur noch 25 877 Byte groß gewesen. Die Prüfsumme, die
die Probe bei jedem Lauf nachrechnet, hätte dann nicht mehr gestimmt.

**Die Stelle ist tückisch, weil sie hier nichts gemerkt hätte:** Die
Arbeitskopie bleibt, wie sie kam. Grün wäre es also weiter gewesen — und auf
jedem frisch geklonten Arbeitsplatz rot, an der ersten Erwartung der Probe.
`tools/gpxprobe/gpx11.xsd -text` hält die Datei unangetastet; nachgerechnet
mit `git checkout-index` in ein leeres Verzeichnis.

### Web — Ein Fehler aus AP2 und AP3, gefunden beim Lesen

**`.plakette-warn` gibt es im Stylesheet nicht.** Gültig sind `neutral`,
`orange`, `blau`, `rot`; der Ton `warn` wurde an drei Stellen benutzt — zwei
davon aus dieser Phase, für den Rückstand der Jobs und die Zählerlisten. Diese
Plaketten standen ohne Hintergrund da, als bloßer Text.

Der Grund, warum es niemandem auffiel, ist der eigentliche Befund: Der
Klassenname wird **zusammengesetzt** (`'plakette-' . $ton`) und taucht als
Literal nirgends auf. `tools/vollstaendigkeit/` kann ihn deshalb nicht finden —
es kennt Klassen im Markup und Klassen im Stylesheet, aber keine, die zur
Laufzeit entstehen. Das ist dieselbe Lücke, die Backlog **Nr. 36** seit P3/O6
beschreibt, nur von der anderen Seite; der Eintrag ist um diesen Fall
erweitert.

Und: Ich hatte das Bild der Wartungsseite angesehen und die farblose Plakette
übersehen. Ein Bild anzusehen ist nur dann eine Prüfung, wenn man weiß, wonach
man sieht.

### Web — Was sonst noch dazukam

`ui_zeile()` kennt jetzt `attr` — dieselbe Zusatzoption, die `ui_knopf()` und
`ui_aktionen()` schon haben, für `data-`-Attribute und `tabindex`. Kein neuer
Baustein.

Eine Zeile kann hervorgehoben werden (`.zeile-hervor`): dieselbe Rauchfläche
wie die neutrale Plakette, dazu ein orangener Balken in `--strich-stark`. Kein
neuer Farbwert, kein neues Maß.

### Web — Belegt

Neu: **`tools/gpxprobe/`**, mit dem **amtlichen GPX-1.1-Schema** von
TopoGrafix, vendoriert unter `tools/gpxprobe/gpx11.xsd` (26 665 Byte, SHA-256
`9e4d1988…`, Herkunft und Prüfsumme in `docs/Lizenzen.md` 7.1). Die Probe
rechnet die Summe bei **jedem** Lauf nach — ein Schemalauf gegen ein
verändertes Schema belegt nichts. Zur Laufzeit wird die Datei nie geladen; sie
liegt unter `tools/`, und der Deploy nimmt den Ordner aus.

| Prüfung | Zahl |
|---|---|
| `gpxprobe/probe.php` | **75 Erwartungen, 0 nicht erfüllt** |
| Gültig gegen das amtliche GPX-1.1-XSD | Stufe 2, Stufe 3 und Ruhesegment: **je gültig** |
| Punkt für Punkt gegen die browsergebauten Referenzdateien | **146 Dateien, 174 804 Einzelvergleiche, 0 Abweichungen** |
| Sind die Referenzdateien selbst schemagültig? | **171 Dateien, 0 ungültig** — der bestehende Export war also schon korrekt |
| Punktzahl entspricht der Stufe | 300 von 300 · **56 von 300** |
| Kennzeichnung in der Datei | je 2 von 2 (`<metadata>` und `<trk>`) |
| Kennzeichnung im Dateinamen | `…_original.gpx` / `…_ausgeduennt.gpx` |
| Kennzeichnung auf der Seite | Stufe und Punktzahl stimmen mit der Datei überein |
| Datentrennung | unangemeldet 302 · fremder Einsatz **404, nicht 403** · Papierkorb 404 · fremder Diensttag 404 |
| Grenzfälle | keine Spur → 404 statt leerem GPX · unbekannte Art 400 · Kennung 0 400 |
| Unter `api/` gibt es den Abruf nicht | **404** — sonst wäre Sitzungsende JSON |
| Ratenschutz | 12 Fehlgriffe → **429**; ein gelungener Abruf zählt nicht mit |
| Ruhesegment über die Spurenseite | eigener Abruf, schemagültig, `ruhezeit_…gpx` |
| Auswahl aus drei Spuren | schemagültig · **3 `<trk>`, je 1 `<trkseg>`** — nicht zusammengeklebt |
| Auswahl gegen die Einzelabrufe | **436 Punkte = 436 Punkte, 1744 Einzelvergleiche, 0 Abweichungen** |
| Kennzeichnung in der Auswahl | jede Spur nennt ihre Stufe (1× ausgedünnt, 2× Original); der Kopf nennt beide |
| Reihenfolge auf der Seite | chronologisch, gegen die Datenbank geprüft — eine Ruhezeit **vor** dem ersten Einsatz steht auch davor |
| Reihenfolge in der Auswahl | dieselbe wie auf der Seite, gegen die Datenbank geprüft |
| Grenzfälle der Auswahl | Eintrag ohne Spur → kein leeres `<trk>` · fremde Kennung fällt heraus · nur fremde → 404 · `mission-abc` → 400 · > 100 → 400 · fremder Diensttag → 404 |
| Speicher bei 100 Spuren der größten Art | Datei **9,7 MB**, Spitze **23,4 MB von 64 MB** |
| Bilderlauf, zwei Seiten × acht Breiten (360–1920 px) | 16 Bilder, **0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px**; 16 verschiedene Prüfsummen (Gegenprobe gegen F-P3-AQ) |
| Bedienzustand im Browser (Chromium, 1280 und 360 px) | Leiste erscheint ab dem ersten Haken, Text „2 Spuren als eine Datei", Download `diensttag_2026-05-10_2-spuren_original.gpx` mit **2 `<trk>`** |
| Vollständigkeit: Hexfarben, Pixelmaße, Schriftgrößen außerhalb der Token | **je 0** |
| Vendoriertes Schema nach simuliertem Klon | `git checkout-index` → **26 665 Byte, Summe `9e4d1988…`** |
| Wortliste (R28) | 0 / 0 / 0 |

**Der Punkt-für-Punkt-Vergleich belegt mehr als der Schemalauf.** Ein Schema
sagt, dass die Datei richtig *aufgebaut* ist; es sagt nichts darüber, ob die
richtigen Punkte darin stehen. Zwei unabhängige Umsetzungen — die eine in PHP
auf dem Server, die andere in JavaScript im Browser —, die auf denselben
Bestand dieselbe Datei schreiben, sagen genau das.

**Nicht geprüft:** fremde Kartenprogramme (dass eine schemagültige Datei in
QGIS oder BaseCamp so aussieht wie gemeint, sagt kein Schema) · eine Spur über
50 000 Punkten · Nebenläufigkeit zwischen Abruf und Ausdünnungsjob · andere
Browser als Chromium (WebKit und Gecko stehen in dieser Umgebung nicht zur
Verfügung) · die Auswahl ohne JavaScript (die Kästchen sind ein gewöhnliches
GET-Formular, aber die Leiste blendet das Skript ein — ohne Skript bleibt der
Einzelabruf).

**Was das Hervorheben angeht:** Es *ist* jetzt im Browser geprüft, samt der
Frage, an der es hätte scheitern können — Zeigen und Auswahl greifen beide auf
die Deckkraft der Linien zu. Gemessen wurden die Deckkräfte aller 15 Linien in
drei Zuständen: mit Auswahl (`0.95` für die zwei gewählten, `0.25` sonst),
beim Zeigen auf eine dritte Zeile (nur diese `0.95`) und danach wieder
(Auswahl unverändert sichtbar). Zwei Funktionen, die beide an derselben Linie
drehen, hätten einander überschrieben; deshalb zeichnet **eine** Funktion aus
beiden Zuständen.

## [Web 10.2.0] — 2026-08-31

**Die drei Stufen stehen jetzt wirklich.** Viertes Arbeitspaket der Phase S2.
Eine Migration ist zwingend (`2026_09_01_letzter_punkt_am`).

> **Nach dem Ausrollen `update.php` aufrufen.** Ohne die neue Spalte kann der
> Verdichtungsjob nicht entscheiden, wann eine Spur reif ist — er lässt dann
> alles liegen.

### Web — Was dazugekommen ist

Zwei Jobs im Katalog aus Web 10.1.0:

- **`verdichtung`** holt abgeschlossene Spuren aus den Zeilen in den
  verlustfreien Blob. Eine Transaktion je Spur, Rundlaufprüfung **vor** dem
  Löschen (E-S2-07).
- **`ausduennen`** ersetzt sechs Monate nach Einsatzende den verlustfreien
  durch einen ausgedünnten Blob: Douglas-Peucker dreidimensional, 2 m
  waagerecht und 3 m senkrecht als **getrennte** Toleranzen, und je
  Phasenzeitpunkt bleibt der zeitnächste Punkt erhalten (E-S2-05).

### Web — Die Karenz stand auf einer Größe, die es nicht gab

E-S2-06 sagt: verdichtet wird, was `final` trägt und **14 Tage** keinen neuen
Punkt mehr bekommen hat. Für diese Regel braucht es eine **Ankunftszeit** — und
im ganzen Schema gab es keine. `track_points.ts` ist die Aufzeichnungszeit,
`missions.created_at` die Anlagezeit der Zeile, `rest_segments` hatte gar
nichts, `devices.last_seen` gilt je Gerät.

Über `MAX(ts)` gerechnet wäre die Karenz **Zierrat gewesen, und zwar genau im
Fall, für den sie gebaut ist**: Die Uhr setzt `final = true` in *jedem*
Teilstück und räumt erst auf, wenn `next_seq >= pointCount` ist. Eine Uhr, die
drei Wochen ohne Empfang war, schickt Teilstück 1 mit `final = true` — und die
Aufzeichnungszeit ist dann schon drei Wochen alt. Der Job hätte zwischen
Teilstück 1 und 2 verdichtet.

Deshalb `letzter_punkt_am` auf `missions` und `rest_segments`, gesetzt von
`ingest.php`, wenn eine Anfrage **tatsächlich** Punkte einfügt (nicht bei einer
Wiederholung — sonst hielte eine Uhr, die im Kreis sendet, ihre Einsätze ewig
aus der Verdichtung). Der Altbestand wird **nicht** nachgefüllt: Das wäre ein
Vollscan über Millionen Zeilen in einer Seite, auf die jemand wartet. `NULL`
heißt „noch nie gemessen"; der Job trägt es beim ersten Hinsehen nach, aus dem
Umriss, den er ohnehin holt, und mit `LEAST(…, jetzt)` begrenzt — der Riegel
gegen eine Uhr, deren Zeit in der Zukunft steht.

### Web — Drei Fallen in der Ausdünnung, alle nachgemessen

**1. Zwei Toleranzen sind nicht zwei Läufe.** Naheliegend wäre: einmal
waagerecht ausdünnen, einmal senkrecht, die Behaltelisten vereinigen. Die
Vereinigung erzeugt aber einen **dritten** Streckenzug, für den keiner der
beiden Läufe etwas zugesagt hat. Gemessen am Referenzbestand: **8,62 m
waagerechte und 4,16 m senkrechte** Abweichung verworfener Punkte, bei
zugesagten 2 und 3. Sie behält dabei sogar mehr Punkte, sieht also nach der
sicheren Wahl aus. Richtig ist **ein** Lauf mit
`s = max(waagerecht / 2 m, senkrecht / 3 m)`.

Dass die zweite Toleranz überhaupt nötig ist, ist ebenfalls gemessen: Rein
zweidimensional ausgedünnt liegt der schlimmste verworfene Punkt **82,76 m**
neben dem Höhenprofil.

**2. Pflichtpunkte sind Abschnittsgrenzen, keine Nachträge.** Global ausdünnen
und die geschützten Punkte hinterher einfügen bricht die Zusage: Ein
nachträglich eingefügter Punkt knickt den Weg zu sich hin, und was 1,9 m auf
der anderen Seite lag, liegt danach fast doppelt so weit weg. Gemessen: **46
von 181** Referenzspuren bekommen überhaupt einen Punkt nachträglich,
**11 davon verletzen danach die Zusage**. Abschnittsweise gerechnet: **0
Verletzungen**.

**3. Fehlende Höhen dürfen den Höhentest nicht stilllegen.** Ein einzelner
höhenloser Punkt an einer waagerechten Ecke wird zum Teilungspunkt — und damit
zum Sehnenende beider Teilstücke. Wer dort den Höhentest ausfallen lässt,
verliert im Prüffall eine **150-m-Spitze** vollständig, und eine Prüfung, die
solche Abschnitte überspringt, meldet dafür **0,0 m Verlust**. Der Fehler
versteckt sich in genau der Lücke, die ihn erzeugt. Stattdessen eine
Ankerreihe, die Lücken über die Zeit füllt.

### Web — Douglas-Peucker ist quadratisch, und das trifft zu

Der schlechteste Fall ist nicht konstruiert: Die Uhr nimmt einen Punkt auf,
sobald 15 m **oder** 10 s vergangen sind. Ein längerer Schwebeflug mit
GPS-Rauschen über 2 m ergibt genau den Zickzack, in dem kein Punkt wegfallen
darf. Gemessen für **eine** solche Spur: 2 000 Punkte 0,198 s · 5 000 1,219 s ·
10 000 4,340 s · 20 000 18,658 s · **50 000 114,5 s**.

Die Häppchenbudgets sind 3, 20 und 300 s. Auf dem Token-Weg liefe das in
`max_execution_time` — und ein Zeitablauf ist **kein `Throwable`**: Der `catch`
im Job-Rahmen fängt ihn nicht, die Laufsperre bleibt stehen, der Job ist eine
Stunde tot und stirbt dann wieder. Dauerhafter, unsichtbarer Stillstand.

Zwei Gegenmittel, beide gemessen:

- **Iterativ statt rekursiv**, und immer die größere Hälfte auf den Stapel:
  Der Stapel hat nie mehr als ⌈log₂ n⌉ = 16 Einträge statt 50 000. (Rekursiv
  wären es 38 MB VM-Stapel bei 797 Byte je Rahmen — gegen ein Z3-Budget von
  64 MB.)
- **Ein Deckel auf die Abschnittslänge** (1000 Punkte): Derselbe Fall sinkt von
  114,5 s auf **2,40 s**. Am Normalfall kostet er nichts — eine glatte
  50 000-Punkte-Spur braucht *mit* Deckel 0,031 s und behält 786 Punkte, *ohne*
  0,161 s und 816. Am Referenzbestand greift er gar nicht.

Beides fällt an einer Prüfung am Referenzbestand **nicht** auf: Dort ist die
größte erreichte Rekursionstiefe 23.

### Web — Was die Ausdünnung wirklich spart

Weniger, als die Punktzahl vermuten lässt. Sie entfernt genau die
**vorhersagbaren** Punkte; die verbleibenden Differenzen sind größer und lassen
sich schlechter packen.

| Bestand | Punkte bleiben | Bytes bleiben |
|---|---|---|
| Referenzkonto (156 Spuren, 47 078 Punkte) | 40,7 % | **73,6 %** |
| Messstand (4973 Spuren, 1 628 340 Punkte) | 31,6 % | **57,4 %** |

Wer den Erfolg an der Punktzahl misst, misst das Falsche. Beide Stufen halten
E-S2-24 trotzdem mit Abstand: gemessen **1,60 MB je 1000 Einsätzen** gegen
3 MB Zielwert — Stufe 2 kostet 3,90 Byte je Punkt, Stufe 3 **2,24 Byte je
Originalpunkt**.

### Web — Für die Uhr ändert sich nichts, und das ist geprüft

Nach der Ausdünnung nimmt `ingest.php` eingehende Punkte nicht mehr an —
**quittiert sie aber**, damit die Uhr ihren Puffer leert (E-S2-08). Die Grenze
ist die **Stufe**, nicht die Punktzahl: Bei Stufe 2 werden Nachzügler weiter
angenommen und beim nächsten Verdichtungslauf eingearbeitet. Wer statt der
Stufe prüfte, ob überhaupt ein Blob dasteht, wirft genau diese Punkte weg — und
quittiert sie, so dass die Uhr sie löscht.

Der JSON-Vertrag bleibt **Fassung 1.3**; neu ist allein das zusätzliche
Antwortfeld `dropped_points`, das die Uhr nicht liest.

**Nebenbei behoben:** Scheiterte der *letzte* Punkt eines Teilstücks an der
Wertprüfung, meldete der Server `next_seq` kleiner als die Punktzahl des
Pakets. Die Uhr räumt aber erst bei `next_seq >= pointCount` auf — sie sandte
dasselbe Stück endlos. `next_seq` hat jetzt allgemein die Untergrenze
`seq_from + gesendete Punkte`.

### Web — Zwei Funde, beide behoben

**`spur_loeschen_nur_zeilen()` löschte zu viel.** Ohne `seq`-Obergrenze nahm
sie alle Zeilen eines Eigentümers — auch die, die während des Laufs eintrafen.
Der Job liest die Punkte, `ingest.php` committet dazwischen einen Upload, der
Job löscht: Ein `DELETE` ist ein *current read* und sieht auch das. Punkte, die
in keinem Blob stehen, verschwanden still und wurden mit „ok" quittiert. Die
Obergrenze ist jetzt **verpflichtend** — eine wahlweise ist eine, die vergessen
wird.

**Die Ortshöhe konnte still verschwinden.** `compute_site_elevation()` läuft bei
jedem Speichern und schreibt bedingungslos, auch `NULL`. Auf einer
ausgedünnten Spur wurden die behaltenen Punkte für die *damaligen* Phasenzeiten
geschützt; wer einen zwei Jahre alten Einsatz öffnet und eine Phase um zehn
Minuten verschiebt, findet im 300-Sekunden-Fenster womöglich keinen Punkt mehr.
Auf Stufe 3 wird ein vorhandener Wert deshalb nicht mehr durch `NULL` ersetzt.
Auf Stufe 1 und 2 bleibt es beim bisherigen Verhalten — dort trägt die Spur
alle Punkte, ein leeres Ergebnis ist die Wahrheit.

### Web — Die Wartungsseite benennt, was liegenbleibt

Nicht nur „3 Spuren mit Lücke", sondern `mission:412`. Die Listen fallen im
Lauf ohnehin an; ihre Anzeige kostet keine einzige zusätzliche Abfrage.
Angezeigt wird der Stand des letzten **vollständigen** Durchlaufs — sonst
stünde dort eine Mischung, in der behobene Fälle stehenbleiben.

Vier Gründe werden benannt: Lücke in der Nummernfolge, zu viele Punkte
(> 50 000, aus einer Sicherung nicht wiederherstellbar), Punkte auf einer
ausgedünnten Spur (Erwartungswert **0**), und eine nicht bestandene Prüfung.

### Werkzeug — Die Jobs lassen sich anhalten

`php jobs.php --pause <Sekunden>` (0 hebt auf). **Gefunden beim Messen:** Der
Kreislauf spielt eine Sicherung in ein frisches Konto und exportiert sie sofort
wieder; die wiederhergestellten Einsätze sind alt, der Verdichtungsjob hält sie
für reif, und was älter als sechs Monate ist, wird ausgedünnt. Der Vergleich
misst dann nicht mehr „kommt zurück, was hineinging", sondern „hat der Job
dazwischen zugeschlagen".

Beim ersten Lauf nach AP3 ging es gut — **aber nur zufällig**, weil der
Mindestabstand des Huckepack-Wegs gerade griff. Nachgemessen: ein Lauf ohne
Pause verdichtete **125 Spuren** des Umlaufkontos. Der Kreislauf hält die Jobs
jetzt ausdrücklich an; die Wartungsseite zeigt eine laufende Pause als eigene
Plakette, damit sie nicht wie ein arbeitender Job aussieht.

Dieselbe Erfahrung noch einmal, teurer: Der erste Lauf des Ausdünnungsjobs auf
dieser Entwicklungsinstallation hat **25 Spuren des Referenzkontos**
ausgedünnt — unwiederbringlich. Die Ausdünnung ist genau dafür gebaut; sie
unterscheidet nicht zwischen Bestand und Messinstrument. Die Spurprobe
überspringt seither ausgedrückt ausgedünnte Spuren und sagt, wie viele.

### Web — Belegt

Zwei neue Prüfmittel: **`tools/ingestprobe/`** (die Uhr-Schnittstelle über
echtes HTTP) und ein neuer Teil 4/5 in **`tools/spurprobe/`**.

| Prüfung | Zahl |
|---|---|
| `spurprobe/probe.php` | **25 Erwartungen, 0 nicht erfüllt** |
| Zusage der Ausdünnung am Referenzbestand | 156 Spuren, 47 078 Punkte, **0 Verletzungen** von 2,0 m / 3,0 m |
| Höhenermittlung nach der Ausdünnung | **527 von 528** Phasen mit Punkt im ±300-s-Fenster — vorher ebenso viele |
| Gegenprobe „global plus einfügen" | 46 Spuren betroffen, **11 Verletzungen**, bis 8,62 m / 4,16 m |
| Gegenprobe „nur zweidimensional" | größte senkrechte Abweichung **82,76 m** |
| Künstliche Prüffälle (Gleichstand, keine Höhe, Höhenfalle, Deckel, `n_original`) | 6 Erwartungen, 0 nicht erfüllt |
| `ingestprobe/probe.php` | **24 Erwartungen, 0 nicht erfüllt** |
| `jobprobe/probe.php` | 24 Erwartungen, 0 nicht erfüllt |
| Verdichtungslauf am Messstand | **9395 Spuren in 44,3 s**, 2 936 497 Zeilen entfernt, Spitze **4,0 MB** |
| Ausdünnungslauf am Messstand | **4973 Spuren in 15,2 s**, Spitze 4,0 MB, `n_original` unverändert |
| Blobgröße je 1000 Einsätze | **1,60 MB** (E-S2-24: ≤ 3 MB) |
| Kreislauf `edbak` (R24) | 286 739 Vergleiche, **0 unerklärt** |
| Kreislauf `csv` (R24) | 8797 Vergleiche, **0 unerklärt** |
| Wiederherstellungsprobe (R27) | 30 Erwartungen, 0 nicht erfüllt |
| Wartungsseite, acht Breiten | 8 Bilder, **0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px** |
| Vendoriertes Schema nach simuliertem Klon | `git checkout-index` → **26 665 Byte, Summe `9e4d1988…`** |
| Wortliste (R28) | 0 / 0 / 0 |

**Nicht geprüft:** ein Häppchen, das wirklich am Budget abbricht (die Läufe
gehen bei diesem Bestand durch) · echte Nebenläufigkeit zwischen Job und
Upload · ein echter Cron · eine Spur mit mehr als 50 000 Punkten (der Bestand
hat keine; das Verhalten ist über die Umriss-Prüfung belegt, nicht gefahren) ·
der volle Referenz-Sendeplan über 526 Anfragen gegen den geänderten
`ingest.php` — dafür fährt `tools/ingestprobe/` gezielte Grenzfälle über
denselben Endpunkt, die Mengenprobe steht aus.

Die Vollständigkeitsprüfung meldet **233 statt 232** Befunde. Der eine
Unterschied ist ein Auslassungszeichen in einem neuen Hinweissatz der
Wartungsseite — richtige Typografie, kein Gestaltungsbefund. Dass dieser
Zähler Prosa und Symbole nicht trennt, steht seit P3/O12 im Backlog (Nr. 42)
und ist dort mit dieser Runde fortgeschrieben.

## [Web 10.1.0] — 2026-08-31

**Die Wartung liegt nicht mehr auf dem Weg einer Anfrage.** Drittes
Arbeitspaket der Phase S2. Eine Migration ist zwingend
(`2026_08_31_jobs`, Tabelle `jobs`).

> **Nach dem Ausrollen `update.php` aufrufen.** Ohne die Tabelle `jobs` läuft
> kein Wartungsjob mehr — der Papierkorb bliebe voll, Kopplungscodes ewig
> gültig. Die Wartungsseite sagt das dann auch: Plakette
> „Migration ausstehend".

### Web — Warum

Der einzige Zeitgeber dieser Installation war `run_cleanup_if_due()` —
huckepack auf der Anfrage der ersten Nutzerin des Tages. Das trug, solange die
Arbeit klein war. Sie ist es nicht mehr: Die Waisenprüfung war ein Anti-Join
über die ganze Spurtabelle und kostete gemessen **4,07 s bei 9,46 Mio.
Zeilen**, bezahlt von genau der Person, die gerade eine Seite aufgerufen hatte.
Bei der Zielmenge Z2 (190 Mio. Zeilen) wären es Minuten. Und ab AP3 kommt
Arbeit dazu, die sich in einer Webanfrage gar nicht erledigen lässt.

### Web — Drei Auslöser, damit die Hosterwahl offen bleibt

Diese Anwendung setzt bewusst keinen Cron voraus; auf einfachem Webspace gibt
es oft keinen. Deshalb dieselbe Arbeit über drei Wege — **einer genügt**, und
eingerichtet werden muss keiner:

| Weg | Aufruf | Budget je Lauf |
|---|---|---|
| Kommandozeile (empfohlen) | `* * * * * php …/server/jobs.php` | 300 s |
| Adresse mit Token | `https://…/jobs.php?token=…` | 20 s |
| Huckepack auf einer Anfrage (Rückfall) | wie bisher, automatisch | 3 s |

Die Wartungsseite zeigt alle drei mit fertigem Befehl bzw. fertiger Adresse.
Das Token liegt in `app_state` und **nicht** in `config.php`: Die Anwendung
schreibt diese Datei genau einmal, bei der Einrichtung; sie danach anzufassen
hieße, auf jedem Webspace Schreibrecht auf die eigene Konfiguration zu
brauchen — und Bestandsinstallationen hätten kein Token, ohne dass jemand
sähe, warum. Es wird mit `hash_equals` geprüft, hinter dem Ratenschutz und mit
angeglichener Antwortzeit; „Token gibt es gar nicht" darf nicht schneller
kommen als „Token ist falsch".

`jobs.php` lädt **ausdrücklich nicht** `auth_guard.php` — der würde den
Huckepack-Weg auslösen und den Job aus dem Job heraus starten.

### Web — Der Rückfall ist ein Rückfall geworden

Die erste Fassung lief in eine selbstgestellte Falle: Der Job `waisen` ist
nicht täglich, sondern läuft, solange es Rückstand gibt — also lief er bei
**jeder** angemeldeten Anfrage, mit bis zu 18 s Budget. Eine Seite, die
zwanzig Sekunden braucht, weil sie nebenbei aufräumt, ist kaputt, auch wenn
kein Zeitlimit greift.

Jetzt trägt der Huckepack-Weg **3 s** und wiederholt sich je Job frühestens
nach **5 Minuten**. Gemessen: Die eine fällige Anfrage trägt **887 ms** (bei
diesem Bestand passt ein vollständiger Durchlauf ins Budget), jede weitere
innerhalb der fünf Minuten **0,5–1,3 ms**. Für `cli` und `token` gilt der
Abstand nicht: Dort bestimmt der Zeitplan die Häufigkeit, und wer jede Minute
aufruft, will das auch.

### Web — Häppchen statt Vollscan, und was das wirklich bringt

Jeder Job bekommt ein Zeitbudget, hört auf, wenn es zu Ende ist, und merkt
sich in `jobs.zustand`, wo er stehengeblieben ist. Die Waisensuche wandert
dafür bereichsweise über den Primärschlüssel statt als Anti-Join über alles.

**Bei 3,31 Mio. Zeilen ist der neue Weg nicht schneller**, je fünf Läufe:

| | Dauer |
|---|---|
| Anti-Join über alles (alt, nur lesend) | **0,78–0,90 s** |
| bereichsweise, ein vollständiger Durchlauf (neu) | **0,85–1,05 s** |

Das ist die ehrliche Zahl, und sie soll hier stehen: Bei dieser Menge kostet
der neue Weg eher etwas mehr. Der Gewinn liegt woanders — er ist **begrenzt**,
**fortsetzbar** und liegt **nicht auf dem Weg einer Anfrage**. Bei Z2 ist das
der Unterschied zwischen „läuft eben nebenher" und „die Seite hängt
minutenlang, und niemand weiß warum".

Die Sperre gegen zwei gleichzeitige Läufe ist ein bedingtes `UPDATE` statt
`SELECT`-dann-`UPDATE` — letzteres hat ein Zeitfenster, in dem zwei Anfragen
beide zu dem Schluss kommen, sie dürften. `laeuft_seit` ist ein Zeitstempel
und kein Flag: Ein Lauf, der mitten im Häppchen abstürzt, ließe ein Flag für
immer stehen, und der Job liefe nie wieder — stillschweigend, was der teuerste
Fall ist.

### Web — Der Rückstand ist der Fortschritt, nicht die Waisenzahl

Die naheliegende Anzeige wäre „wie viele Waisen gibt es" — und die kostet
genau den Vollscan, den dieser Job abschafft. Angezeigt wird deshalb, wie weit
die Marke noch zu laufen hat.

Zwei Fehler steckten hier, beide erst beim Messen aufgefallen und beide vom
selben Muster: Die Rückstandsfunktion las den Zustand **aus der Tabelle**,
während der frische noch gar nicht geschrieben war — direkt nach einem
vollständigen Durchlauf meldete der Job „Rückstand 33093", also die ganze
Tabelle als ausstehend. Und eine Marke von 0 war nicht von „noch nie gelaufen"
zu unterscheiden. Beides ist derselbe Reihenfolgefehler, der in AP0 schon
einmal auftrat (die Serverprobe schrieb ihre Datei, bevor die abgeleiteten
Werte entstanden waren).

### Web — Sichtbar, weil sie still ist

Die Wartung darf keine Seite kaputtmachen und schweigt deshalb gegenüber der
Anfrage. Genau darum muss sie woanders sichtbar sein: `update.php` zeigt je Job
letzten Lauf, Auslöser, Rückstand und letzten Fehler. `letzter_fehler` steht in
der Tabelle und nicht nur im Fehlerprotokoll des Webspace — an das kommt auf
geteiltem Hosting nicht jede Betreiberin heran.

Die Marken `last_cleanup` und `last_cleanup_ok` in `app_state` entfallen; ihre
Auskunft steht vollständiger in `jobs`.

### Web — Belegt

Neu dafür: **`tools/jobprobe/`** — die Probe legt eigene Waisen auf
Eigentümerkennungen an, die es garantiert nicht gibt, und räumt hinter sich
auf. Sie läuft nicht in einer zurückgerollten Transaktion, weil die Sperre auf
`COMMIT` angewiesen ist und ein Rollback über sie nichts beweisen würde.

| Prüfung | Zahl |
|---|---|
| `jobprobe/probe.php` | **24 Erwartungen, 0 nicht erfüllt** |
| Alle drei Auslöser tragen denselben Rückstand ab | je **10 Zeilen + 1 Blob → 0 + 0** |
| Genaue Zählung (6 Zeilen + 1 Blob gepflanzt) | gemeldet **„erledigt 7"** |
| Vollständiger Durchlauf, Z3-Rahmen (`memory_limit=64M`) | **0,85–1,05 s** über **3 313 246 Zeilen**, Spitze **2,0 MB** |
| Anti-Join über alles, dieselbe Tabelle, nur lesend | **0,78–0,90 s** |
| Huckepack: die eine fällige Anfrage / jede weitere in 5 min | **887 ms** / **0,5–1,3 ms** |
| HTTP-Weg ohne / mit falschem / mit richtigem Token | **403 / 403 / 200**, beide 403 in **0,351 s** |
| Ratenschutz am Token-Weg | ab dem **10.** Fehlversuch je IP **429** |
| Wartungsseite, acht Breiten (360–1920 px) | 8 Bilder, **0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px** |
| `spurprobe/probe.php` (AP1, unberührt) | 14 Erwartungen, 0 nicht erfüllt |
| Kreislauf `edbak` (R24) | 286 739 Vergleiche, **0 unerklärt** (16 erwartet) |
| Kreislauf `csv` (R24) | 8797 Vergleiche, **0 unerklärt** (859 erwartet) |
| Wiederherstellungsprobe (R27) | 30 Erwartungen, **0 nicht erfüllt** |
| Vendoriertes Schema nach simuliertem Klon | `git checkout-index` → **26 665 Byte, Summe `9e4d1988…`** |
| Wortliste (R28) | 0 / 0 / 0 |

Zum Bild der Wartungsseite: Es wurde **angesehen** und zeigt die Karte
„Hintergrundjobs" mit beiden Jobs, ihren Zeitstempeln und den Plaketten
`anfrage` und `cli` — nicht die Anmeldeseite (F-P3-AQ).

**Eine Grenze, die man kennen sollte:** Der Ratenschutz zählt je IP. Kommen
zehn Fehlversuche von derselben Adresse, ist der Token-Weg für diese IP zehn
Minuten gesperrt — auch für den richtigen Aufruf. Wer einen Zeitplan-Eintrag
mit falschem Token stehen hat, sperrt sich damit selbst aus; nach dem
Berichtigen dauert es zehn Minuten.

## [Web 10.0.0] — 2026-08-31

**GPS-Punkte liegen jetzt als Blob statt als Zeile — 62,4 Byte werden 3,58.**
Zweites Arbeitspaket der Phase S2. Die Hauptnummer steigt, weil sich das
Datenmodell ändert und eine Migration zwingend ist.

> **Nach dem Ausrollen `update.php` aufrufen.** Ohne die Migration gibt es die
> Tabelle `track_blobs` nicht, und jeder Spurzugriff scheitert.

### Web — Warum

Spurpunkte sind 93 % des Bestands. Gemessen am Referenzdatensatz kostet eine
Zeile in `track_points` **62,4 Byte**, derselbe Punkt als Blob **3,58** — ein
Siebzehntel. Bei 5000 Einsätzen sind das 194 statt 3300 MB, und damit steht
und fällt die Zusage, dass ein Konto dieser Größe auf geteiltem Webspace
trägt.

Die Punkte liegen deshalb künftig in drei Stufen: Zeilen als **Eingangspuffer
der Uhr** (der Upload kommt in Teilstücken, ist idempotent und wiederholbar —
dafür ist eine Zeilentabelle richtig), danach als verlustfreier Blob, und
sechs Monate nach Einsatzende ausgedünnt. **Dieses Paket baut das Format und
den Zugriffsweg; die Wanderung zwischen den Stufen kommt mit den Jobs.**

### Web — Ein Weg, nicht sechs

Sechs Stellen lasen `track_points` per SQL, jede mit einer eigenen Projektion.
Bliebe das so, müsste jede von ihnen die Stufen kennen — und die erste, die es
vergisst, zeigt eine leere Spur, ohne dass es auffällt. Alle sechs gehen jetzt
über `server/spur_lib.php`: Tagesansicht, Einsatzansicht, Export, Sicherung,
Einsatzort-Höhe und Umdatierung. `CLAUDE.md` trägt das als Pflegepflicht.

**Das Umdatieren eines Diensttags** war ein einziges
`UPDATE track_points SET ts = ts + ?`. An einem Blob geht das vorbei: Die
Zeilen wanderten, die Blobpunkte blieben stehen, und die Spur hätte danach
zwei Zeitrechnungen — sichtbar erst als durcheinandergeratene Phasenzuordnung.
Der Blob wird jetzt gelesen, verschoben und zurückgeschrieben.

**Die Fortsetzungsmarke der Uhr** kam aus `MAX(seq)+1` über die Zeilen. Sobald
die Punkte im Blob liegen, gibt es diese Zeilen nicht mehr — die Marke fiele
auf 0, und die Uhr sendete den ganzen Dienst noch einmal. Sie kommt jetzt aus
`n_original` des Blobs und der höchsten Zeilennummer. Für die Uhr ändert sich
nichts, der JSON-Vertrag bleibt.

### Web — Was „verlustfrei" heißt

Keine Festkomma-Kodierung ist bitgleich gegen einen beliebigen `DOUBLE`.
„Verlustfrei" heißt deshalb: innerhalb einer **festgeschriebenen** Auflösung —
10⁻⁶ Grad (≈ 0,11 m) und 0,1 m Höhe. Sie steht als Kennung im Blob-Kopf, und
ein Leser, der eine unbekannte Kennung findet, verweigert die Arbeit, statt
Zahlen mit dem falschen Faktor zu deuten.

Der Konzeptwortlaut sah „Höhe in Metern gerundet" vor. Das wäre nicht nur
ungenauer gewesen, sondern hätte den Mechanismus stillgelegt: **74,4 % der
Punkte tragen eine Nachkommastelle**, die Rundlaufprüfung hätte bei drei von
vier Spuren angeschlagen, und der Verdichtungsjob hätte nie eine Zeile
gelöscht — stillschweigend. Der Preis der Zehntelmeter sind 7 % Blobgröße.

### Web — Zwei Grenzen, weil es zwei Fragen sind

`LIMIT_TRACKPUNKTE` galt an zwei Stellen, die Verschiedenes meinen: beim
Upload je **Anfrage** (die Uhr sendet in Stücken zu 500, 2000 sind vierfache
Reserve), beim Zurückspielen je **ganzer Spur**. Dort war dieselbe Zahl ein
Datenverlust — was die Uhr über viele Anfragen aufbauen darf, wurde bei 2000
gekappt; die Datei trug die ganze Spur, zurück kam ihr Anfang. Erreichbar ist
das ohne weiteres: 2000 Punkte sind zwischen 33 Minuten und 5,5 Stunden
Aufzeichnung. Aufgefallen ist es nie, weil die längste Referenzspur 1133
Punkte hat.

Jetzt zwei Konstanten, und die Spurgrenze (50 000) **lehnt ab statt zu
kappen**: Eine halbe Spur sieht aus wie eine ganze, eine abgelehnte sieht man.

### Web — Ein Konto löschen räumt jetzt seine Spuren ab

`track_points` ist polymorph und trägt keinen Fremdschlüssel; die Kaskade von
`DELETE FROM users` nahm die Punkte **nicht** mit. Der Kommentar an der
Löschstelle behauptete das Gegenteil und ist der Grund, warum es niemandem
aufgefallen ist. Der Messstand hat es vorgeführt: zwei gelöschte Konten,
**6 202 931 verwaiste Spurpunkte**, rund 380 MB, die erst der nächste
Tagesjob abräumte. Jetzt gehen Zeilen und Blobs ausdrücklich mit, vor der
Kaskade; der Wartungsjob bleibt das Sicherheitsnetz und deckt beide Tabellen.

### Web — Belegt

| Prüfung | Zahl |
|---|---|
| Rundlauf Punkte → Blob → Punkte, ganzer Referenzbestand | 55 861 Punkte, **0 Abweichungen** |
| Blobgröße | **3,58 B/Punkt** (Zeilen: 62,4) |
| Tagesansicht und Einsatzansicht vor/nach Verdichtung | 8 HTTP-Antworten **byteweise gleich** |
| CSV- und GPX-Export aus Blobs gegen die Referenz | 9589 Vergleiche, **0 Abweichungen**, 171 GPX-Tracks |
| Sicherung aus vollständig verdichtetem Konto gegen die Referenz | 286 739 Vergleiche, **0 unerklärt** |
| `spurprobe/probe.php` | 14 Erwartungen, **0 nicht erfüllt** |
| Kreislauf `edbak` (R24) | 286 739 Vergleiche, 0 unerklärt |
| Vendoriertes Schema nach simuliertem Klon | `git checkout-index` → **26 665 Byte, Summe `9e4d1988…`** |
| Wortliste (R28) | 0 / 0 / 0 |

Das Rezept zum Öffnen eines Blobs von Hand steht in `docs/Backup-Format.md`
und wurde gegen einen echten Blob gefahren: 1133 Punkte, 0 Abweichungen
gegenüber dem, was die Anwendung liest.

**Am Dateiformat der Sicherung ändert sich nichts.** Die Spur steht weiterhin
als `[seq, lat, lon, ele, ts]` je Punkt; die Nutzlast-Fassung bleibt 7.

## [Werkzeug: Messstand] — 2026-08-31

**Die Anwendung lässt sich jetzt an 5000 Einsätzen messen — und der erste Lauf
sagt, wo sie heute bricht.** Weder die Weboberfläche noch die Uhr-App sind
geändert, deshalb trägt dieser Eintrag keine Versionsnummer (dasselbe Muster
wie beim Uhr-Prüfstand). Erstes Arbeitspaket der Phase S2.

### Werkzeug — Warum

S2 verspricht, dass 5000 Einsätze in einem Konto tragen. Ein solches
Versprechen lässt sich nicht durch Nachdenken einlösen: Es braucht einen
Bestand dieser Größe, und den muss jemand **herstellen** können. Und es braucht
einen Ausgangswert — „die Sicherung ist jetzt schneller" ist keine Aussage,
solange niemand weiß, wie langsam sie vorher war und wo genau sie aufgehört hat
zu funktionieren.

`tools/messstand/` stellt beides her. Der Vervielfältiger baut aus der
Referenzsicherung eine Folge `.edbak`-Dateien; eingespielt werden sie über den
**regulären** Wiederherstellungsweg im Browser, nicht per SQL. Das kostet
Zeit, und es ist der Punkt: Der Einspielweg ist selbst einer der Prüflinge.

### Werkzeug — Die Ausgangsmessung

5002 Einsätze, 3 201 524 Spurpunkte, hergestellt in 245 Sekunden. Gemessen mit
CPU-Drossel 6× (Referenzgerät nach Z3):

| | heute | Ziel |
|---|---|---|
| Speicherspitze `edbak_build()` | **1784 MB** | 64 MB |
| größte JSON-Zeichenkette im Browser | **138,25 MB** | 10 MB |
| Haldenspitze beim Sichern | **508 MB** | 100 MB |
| Spuren je 1000 Einsätze | **38,07 MB** | 3 MB |
| Sicherungsdatei | 40,5 MB | 25 MB |
| Tagesansicht bis zur Spur | 4,81 s | 3 s |
| Suche bis zur ersten Anzeige | 4,53 s | 5 s ✓ |
| Sicherung erstellen | 109,8 s | 300 s ✓ |

Die Speicherspitze ist die härteste Zahl: `edbak_build()` hält das
vollständige PHP-Array und die daraus erzeugte JSON-Zeichenkette gleichzeitig.
Auf geteiltem Webspace mit 128 MB `memory_limit` ist damit bei rund **360
Einsätzen** Schluss — und zwar als Fatal Error ohne JSON-Antwort, so dass der
Browser „HTTP 500" meldet und niemand erfährt, dass es an der Menge lag.

Zwei Zahlen des Befunds haben sich dabei **bestätigt**: 62,4 Byte je Zeile in
`track_points` (angenommen: 62) und 38,07 MB Spuren je 1000 Einsätzen
(angenommen: 40).

### Werkzeug — Was der Einspiellauf zutage gefördert hat

**Er war kaputt.** Seit P3/O11 (Web 9.12.0) hängen vier Stellen von
`einspielen.py` an Markup, das sich geändert hat: Der Baustein `ui_feld()`
rendert `<select>` mit `name` hinter `class` und `id`, Meldungen tragen
`meldung-fehler` statt `alert-danger`, die Geräteliste führt je Gerät ein
eigenes Formular, und die Zugangsdaten eines neuen Geräts stehen im Baustein
`codeblock` statt in `<code>`.

Zwei davon brachen laut ab. **Drei brachen still** — die Fehlerlesung fand
nichts mehr und meldete damit „kein Fehler", und das Aufräumen der Geräte tat
nichts, bis die Grenze von fünf Geräten je Konto erreicht war. Der
Referenzdatensatz war seither nicht mehr **herstellbar**, und weil er als
Datei ja dalag, ist es niemandem aufgefallen. Ein Bestand, den man nicht neu
bauen kann, ist ein Einzelstück und kein Prüfmittel.

Alle vier sind nachgezogen; die Fehlerlesung liegt jetzt an **einer** Stelle
(`sitzung.fehlertext()`) statt an vieren.

**Und derselbe Fund noch einmal im CSV-Kreislauf.** Der Regressionslauf nach
diesem Paket brachte es ans Licht: `ui_segment()` und `ui_schalter()` machen
das Kontrollkästchen unsichtbar und stellen ein `<label>` davor —
`page.check()` klickt aber das Feld und wartet, dass es sichtbar wird. Sieben
Aufrufe in zwei Werkzeugen liefen deshalb in einen Zeitablauf. Behoben über
eine gemeinsame Stelle (`browser/bedienen.mjs`), die die Beschriftung klickt
und danach **belegt**, dass sich der Zustand geändert hat.

Beide Kreisläufe laufen danach wieder: **edbak 286 739 Einzelvergleiche, CSV
8797 — je 0 unerklärte Abweichungen** (R24). Ebenso R27
(30 Erwartungen / 15 Einzelprüfungen, 0 Befunde) und R28 (Wortliste 0/0/0).

Wer die Bausteine in `ui.php` anfasst, ändert damit die Angriffsfläche jedes
Werkzeugs, das die Oberfläche liest oder bedient. Beide Kreisläufe gehören in
denselben Durchgang — sie sind schnell, und sie sind die einzige Stelle, an
der so ein Bruch auffällt.

### Werkzeug — Und dreimal dieselbe Falle im Messstand selbst

Der erste Lauf meldete drei Zahlen, die etwas anderes maßen, als sie
behaupteten. Sie stehen hier, weil sie zusammen die Lehre dieses Pakets sind:

**„5046 Einsätze eingespielt" — angelegt waren 4744.** Addiert worden war die
erwartete Zahl, nicht die gemeldete. Die Anwendung hatte korrekt berichtet
(„254 übernommen, 7 übersprungen — Diensttag liegt hier im Papierkorb"), nur
hat niemand hingesehen. Ursache: Die Referenz trägt einen Diensttag im
Papierkorb; 58-fach kopiert blockierte er spätere Runden (Regel D1). Der
Vervielfältiger lässt gelöschte Einträge jetzt draußen, und der Einspiellauf
liest die Rückmeldung.

**„167 MB Spuren je 1000 Einsätze" — richtig sind 38.** Die Tabelle aller
Konten, geteilt durch die Einsätze eines Kontos.

**„Startseite 25,6 s, Tagesansicht 30,7 s" — richtig sind 1,4 s und 4,8 s.**
`waitUntil: 'load'` wartete auf die gesperrten Kartenkacheln. Gemessen war die
Netzsperre der Umgebung, nicht die Anwendung. Dieselben zwanzig
Kachelmeldungen standen außerdem als „Konsolenfehler" im Protokoll, weil ihre
Meldung die Adresse nicht nennt und damit am Filter vorbeikam.

Ein Prüfmittel ist gegen diese Falle nicht sicherer als das, was es prüft.
Jede Zahl des Messstands benennt jetzt, **was** sie gemessen hat.

### Werkzeug — Zwei Fehlerfunde nebenbei

**Ein Konto löschen lässt seine Positionsdaten liegen.** `track_points` ist
polymorph und trägt keinen Fremdschlüssel; die Kaskade von `DELETE FROM users`
nimmt die Punkte nicht mit. Der Kommentar an der Löschstelle behauptet das
Gegenteil — und ist der Grund, warum es niemandem aufgefallen ist. Der
Messstand hat es prompt selbst vorgeführt: Zwei gelöschte Konten hinterließen
**6 202 931 verwaiste Spurpunkte**, rund 380 MB. Der Wartungsjob räumte sie in
15,18 s ab — beim nächsten Aufruf der Anwendung durch irgendjemanden.

**Dieselbe Zahl, zwei Bedeutungen: 2000 Punkte.** `LIMIT_TRACKPUNKTE` gilt
beim Upload **je Anfrage** (greift praktisch nie, die Spur wächst über viele
Pakete unbegrenzt), beim Zurückspielen aber **je Spur** — dort wird alles
jenseits des 2000. Punktes verworfen. Was die Uhr aufbauen darf, kann die
Wiederherstellung also nicht zurückbringen. Aufgefallen ist es nie, weil die
längste Spur des Referenzbestands 1133 Punkte hat.

Beide sind vermerkt und werden in den Paketen behoben, die die betroffenen
Wege ohnehin anfassen (`docs/Konzept-S2-Mengen-Spuren-Sicherung.md`,
Abschnitt 8).

## [Uhr 2.0.0] — 2026-08-31

**Die Uhr-App heißt „NAdoku" und hat eine echte Anwendungs-ID.** Der Rest der
Umbenennung aus R29, vorgezogen aus P6 — und die Beseitigung eines
Platzhalters, der nur teurer geworden wäre.

Die Hauptnummer steigt, weil die App danach eine **andere Identität** hat: Für
die Uhr ist sie eine neue Anwendung, mit eigenen Einstellungen und eigenem
Speicher. Wer 1.x gekoppelt hatte, koppelt einmal neu und löscht die alte
Fassung vom Gerät.

### Uhr — Die Anwendungs-ID war ein durchgezählter Platzhalter

Im Manifest stand `a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6` — von Hand durchgezählt,
kein Zufallswert; der Kommentar darüber sagte selbst, sie sei „beim ersten
Projekt-Setup durch eine eigene zu ersetzen". Die SDK-Beispiele tragen
durchweg echte UUID, und für den Connect-IQ-Store braucht es eine.

**Warum jetzt und nicht in P6:** Die ID ist die Identität der App auf dem
Gerät. Ein Wechsel kostet jede gekoppelte Uhr eine Neukopplung — heute ist das
**eine**, nach der Öffnung sind es alle. Der Kommentar im Manifest hält das
jetzt fest, samt der Ansage, dass hier nicht mehr geändert wird.

**Was dazu nicht behauptet wird:** Die Folge für den Gerätespeicher ist hier
**nicht gemessen**. Der Simulator legt seinen App-Speicher unter
`GARMIN/APPS/DATA/<DATEINAME>.DAT` ab — benannt nach der geladenen `.prg`, nicht
nach der Anwendungs-ID. Er kann die Frage also gar nicht beantworten, und die
Annahme oben folgt der Plattformdokumentation, nicht einer eigenen Messung.

### Uhr — Der Name

`@Strings.AppName`, der Titel auf dem Startbildschirm und die Beschriftung der
Server-Einstellung tragen jetzt **NAdoku**; die Einstiegsklasse heißt
`NAdokuApp` (`HemsApp.mc` → `NAdokuApp.mc`), und die Dateiköpfe aller 17
Quelldateien ziehen mit.

**Die Uneinheitlichkeit ist bewusst in Kauf genommen.** R29 hielt die
Umbenennung in P6, damit Uhr und Web zusammen umziehen; die Uhr geht jetzt
voran. Das Handbuch sagt es an der Stelle, an der jemand sonst vergeblich
sucht: In der Geräteliste heißt die App **NAdoku**, im Web steht weiter
„Einsatzdoku", bis der v1.0-Schnitt nachzieht.

Für das Gerätemenü ist es die **Kurzform** ohne „Gen-EM": Der Träger gehört in
den Store-Eintrag, nicht auf ein Uhrendisplay, und `nadoku.beispieldomain.de`
führt dieselbe Kurzform seit P2.

## [Uhr 1.11.1] — 2026-08-31

**Die Uhr trägt die Terminologie-Nachträge aus P2 nach.** Kein neues Verhalten
— vier Textstellen, die seit P2 auf die nächste Uhr-Auslieferung gewartet
haben.

R29 des Rahmenplans hält es so fest: Die **Umbenennung** der Uhr-Quelltexte
(Einstiegsklasse `HemsApp`, `@Strings.AppName`) bleibt P6, weil sie das
Manifest ändert. Aber: *„Steht vor P6 aus anderem Grund eine Uhr-Auslieferung
an (Backlog Nr. 11 oder 14 in P4), werden Beispieldomain und Kommentare dort
mitgenommen."* Genau das ist mit 1.10.1 bis 1.11.0 eingetreten.

| Stelle | vorher | jetzt |
|---|---|---|
| `settings.xml` (Garmin Connect, **sichtbar**) | `einsatz.beispiel.de` | `nadoku.beispieldomain.de` |
| `properties.xml` (Kommentar) | `einsatz.beispiel.de` | `nadoku.beispieldomain.de` |
| `Uploader.mc` (Kommentar) | `luftrettung.net` | `nadoku.beispieldomain.de` |
| `Model.mc` (Kommentar) | „Flugtag", „Hubschrauberdienst am Tag, NEF-Nachtdienst am Abend" | „Diensttag (damals noch „Flugtag")", „ein luftgebundener am Tag, ein bodengebundener am Abend" |

Die Beispieldomain ist die aus E-P2-03; dass sie den künftigen Namen trägt,
ist dort ausdrücklich beabsichtigt. Bei `Model.mc` bleibt der alte Begriff
**einmal als historische Angabe** stehen — der Satz erklärt, was sich mit
Web 6.0.0 geändert hat, und eine Geschichte umzuschreiben wäre keine
Terminologiepflege.

**Was bewusst stehen bleibt:** `Flugmodus` in `Util.mc` — das ist der
Betriebszustand eines Geräts und in der Wortliste als `flugmodus` ausdrücklich
ausgenommen. Und `HemsApp` samt `manifest.xml`: R29 legt die Umbenennung nach
P6, weil sie eine eigene Auslieferung nach sich zieht.

## [Uhr 1.11.0 · Web 9.15.0] — 2026-08-31

**Die Uhr trennt eine bestehende Kopplung ausdrücklich, bevor sie neu
koppelt.** Backlog Nr. 14. Und die strenge Typprüfung ist zum ersten Mal
vollständig sauber.

### Uhr — Der Fall ist die geteilt genutzte Uhr

Bis hierher führte „Gerät koppeln" direkt in die Code-Eingabe. Schlug das
Koppeln fehl — falscher Code, kein Telefon in Reichweite, Gerätegrenze
erreicht —, blieben die **alten** Zugangsdaten stehen. Die Uhr dokumentierte
stillschweigend weiter auf das vorherige Konto, und die Person davor bekam
Einsätze, die sie nicht gefahren ist. Niemand sah es der Uhr an.

Die Reihenfolge ist jetzt **abfragen → trennen → neu koppeln**. Scheitert das
Koppeln danach, steht die Uhr *sichtbar* ohne Kopplung da — die Sync-Seite
sagt seit 1.10.1 „Nicht eingerichtet". Die beiden Punkte greifen ineinander:
Ohne den dritten Zustand der Sync-Seite wäre der getrennte Zustand wieder
unsichtbar gewesen.

Die Rückfrage ist der vorhandene Baustein `WatchUi.Confirmation`, wie beim
Einsatzabschluss und beim Verlassen der App — kein neues Element.

### Uhr — Ein Rückstand verhindert das Trennen

Abgeschlossene, noch nicht gesendete Pakete gehören dem **bisherigen** Konto;
nach einer Neukopplung gingen sie an das neue. Das wäre kein Datenverlust,
sondern schlimmer — fremde Einsätze in einem fremden Konto. Die Uhr verweigert
das Trennen deshalb und sagt „Erst 3 Pakete senden / Sonst ans neue Konto".

### Uhr — Lokal wird immer getrennt

Auch wenn der Server nicht antwortet. Andernfalls bliebe eine Uhr ohne Telefon
in Reichweite dauerhaft an ein Konto gebunden, das sie nicht mehr benutzen
soll — genau der Zustand, den dieser Weg beseitigt. Der Servereintrag steht
dann noch und belegt einen Geräteplatz; das sagt die zweite Zeile
(„Nur auf der Uhr getrennt / Gerät im Web löschen"), weil es im Web mit einem
Klick zu beheben ist.

### Web — `pair.php` kennt zwei Anliegen

Neu `{"aktion":"trennen"}` mit den Kopfzeilen `X-Device-Id` und `X-Api-Key`;
Antwort `{"ok":true}`. JSON-Vertrag Abschnitt 1b.

**Kein eigener Endpunkt:** Die Adresse kennt die Uhr schon, und der
Ratenschutz von `pair.php` gilt damit für beide Zweige. Ein zweiter wäre eine
weitere anmeldungsfreie Tür, die dieselbe Bremse noch einmal bräuchte.

**Gelöscht, nicht deaktiviert:** Ein deaktiviertes Gerät belegt weiter einen
der `MAX_GERAETE` Plätze — „zu viele Geräte" ist genau der Fehler, in den eine
geteilte Uhr sonst läuft. Der Fremdschlüssel setzt `device_id` auf `NULL`,
hochgeladene Daten bleiben.

**E-Mail an den Kontoinhaber**, symmetrisch zum Koppeln: die eine Gelegenheit,
es zu erfahren, ohne sich zufällig anzumelden. Die Antwortzeit folgt
`ingest.php` — auch der unbekannte Zweig läuft gegen `AUTH_VERGLEICHSWERT`,
sonst wäre aus der Dauer ablesbar, welche Gerätekennungen es gibt.

### Uhr — Die letzten vier Meldungen der strengen Typprüfung

`-l 3` stand seit 1.8.2 bei vier Fundstellen, die als „nicht auflösbar"
notiert waren. Sie sind es doch. Alle vier haben dieselbe Ursache:
`Storage.setValue()` und `Communications.makeWebRequest()` nehmen einen
PolyType aus bis zu 16 Alternativen; ein Literal wie
`{"svc" => …, "day" => …}` hat aber einen **genauen** Typ, und die Prüfung
sieht nicht, dass der ein Sonderfall des allgemeinen ist. Ein Cast sagt ihr,
welche Alternative gemeint ist.

Eine der vier war eine echte Verwechslung: `Track.mc` **hatte** bereits einen
Cast — auf `Application.PropertyValueType`. `Storage.setValue()` verlangt
`Storage.ValueType`. Ein falscher Cast prüft nichts.

| | vorher | jetzt |
|---|---|---|
| Warnungen (`-w`) | 0 | 0 |
| Strenge Prüfung (`-l 3`) | 4 Fundstellen | **0** |
| Kosten | — | **0 Byte**, gemessen |

Damit ist auch der letzte Rest von Backlog Nr. 13 erledigt. Der Weg dorthin
war 226 Meldungen (77 Stellen) → 4 → 0.

### Uhr/Web — Was nicht geprüft werden konnte

**Die Rückfrage selbst ist nicht fotografiert.** Der Simulator beantwortet sie
im selben Augenblick, in dem sie erscheint: Der lange Druck wird durch eine
gehaltene Taste erzeugt, und deren Ereignisse treffen die eben erschienene
Ansicht. Belegt ist sie durch einen Konsolenmitschnitt eines Probekompilats —
`cred=da` → „Rückfrage" → „trennen" —, durch den Zustand danach („Nicht
eingerichtet" auf der Sync-Seite) und dadurch, dass es der Baustein ist, der
in `ClockView` viermal unverändert benutzt wird.

**Die Serverseite ist nicht gegen eine Datenbank gelaufen.** Geprüft sind
Syntax (`php -l`) und die Ableitung aus `ingest.php` und `einstellungen.php`,
deren Authentifizierung und Löschweg übernommen sind. Der Zweig braucht eine
Probe am Produktivstand, bevor man sich auf ihn verlässt.

## [Uhr 1.10.3] — 2026-08-31

**Die Bildmarke ist auf allen 99 Geräten gleich groß im Verhältnis zum
Display.** Zweite Hälfte von Backlog Nr. 61; damit ist der Punkt erledigt.

### Uhr — Zwei gleiche Displays, zwei Größen

Die Bildmarke wird mit `dc.drawBitmap` **1:1** gezeichnet — ein Bitmap folgt
`Ui.s()` nicht, egal wie das Layout gerechnet ist. Zugeordnet wurde sie über
den Ressourcenordner, und der hing an der **Launcher-Symbolgröße**. Mit der
Displayhöhe hat die aber nichts zu tun.

Das Ergebnis war über die 99 Geräte eine Spanne von **15 % bis 34 %** der
Displayhöhe, wo die Gestaltung 27 % vorsieht — und, am deutlichsten: Venu 3s
und Descent G2 haben **dasselbe** 390-px-Display und zeigten die Marke in
27 % gegen 18 %.

### Uhr — Vier Stufen, gerechnet statt geschätzt

| Kachel | Displayhöhen | Geräte | Anteil |
|---|---|---|---|
| 60 | 208–240 | 35 | 25–29 % |
| **73** | 260–280 | 19 | 26–28 % |
| 101 | 360–390 | 20 | 26–28 % |
| 118 | 416–466 | 25 | 25–28 % |

Zielwert sind 27 % — genauer 70/260, das Verhältnis des Bezugsgeräts
fenix6pro, dem `Ui.s()` ohnehin jede Länge folgt. Für jede Stufenzahl wurde
die Aufteilung gesucht, die die größte Abweichung klein hält; die Wahl fiel
auf vier:

| Stufen | Spanne | |
|---|---|---|
| vorher | 15–34 % | Zuordnung hing an der Symbolgröße |
| 3 | 23,6–30,4 % | oben und unten noch deutlich daneben |
| **4** | **25,0–28,8 %** | gewählt |
| 5 | 25,3–28,4 % | die fünfte Stufe trägt **ein** Gerät (FR 55) |
| 10 | 26,8–27,1 % | eine Kachel je Höhe |

### Uhr — Der Preis, ausdrücklich

Bei vier Stufen fällt das Bezugsgerät mit der 260/280-Gruppe zusammen: Die
Kachel der fenix6pro wächst von 70 auf **73** Pixel, die der Venu 3s schrumpft
von 105 auf 101. Damit hat das Abnahmekriterium aus dem Kopf von `Ui.mc` —
„damit bleibt die Fenix pixelgenau wie zuvor" — eine benannte Ausnahme, und sie
steht jetzt dort. Nur eine Kachel je Displayhöhe hätte sie vermieden, um den
Preis von acht Ordnern statt drei; die Entscheidung ist bewusst so gefallen.
Alles Übrige auf der Fenix ist unverändert.

### Uhr — Was es kostet

Die Kompilate ändern sich zwischen **−3 520 B** (fenix7s, Kachel 70 → 60) und
**+10 992 B** (vivoactive5, 70 → 101), im Mittel +2 613 B. Die kleinen Geräte
werden also leichter, die großen schwerer — was der Sache nach richtig ist.

Der Speicher zur Laufzeit trägt es mühelos. Auf den beiden knappsten Geräten,
gemessen im Simulator auf dem Startbildschirm (dort ist die Bildmarke
geladen — der ungünstigste Fall):

| Gerät | Kachel | belegt |
|---|---|---|
| fenix6 (260 px, 128 kB) | 73 | **55,9 / 123,8 kB** |
| FR 55 (208 px, 128 kB) | 60 | **52,3 / 123,8 kB** |

### Uhr — Nachgerechnet, nicht behauptet

Beim Schreiben der Begründung stellte sich ein Argument aus 1.10.2 als falsch
heraus: Die getrennten Ordner (`resources-icon<N>` und `resources-marke<K>`)
sollten Ordner sparen — „18 statt 14". Das galt für eine Kachel je Displayhöhe.
Bei vier Stufen kommen nur zwölf Paare aus Symbolgröße und Kachel vor, und
getrennt sind es ebenfalls 11 Ordner. Die Trennung bleibt trotzdem richtig,
aber aus einem anderen Grund: Zusammengelegt läge dieselbe 101er Kachel in
fünf Ordnern, und eine verschobene Stufengrenze schnitte den ganzen Satz neu.
Der Text sagt das jetzt so.

## [Uhr 1.10.2] — 2026-08-31

**Das Launcher-Symbol liegt in allen neun Größen vor, die die 99 Geräte
verlangen — und es kommt jetzt aus der Vektorvorlage.** Erste Hälfte von
Backlog Nr. 61; die Staffelung der Bildmarke steht noch aus.

### Uhr — 42 Warnungen, eine Ursache

Im Repositorium lagen zwei Symbolgrößen, 40 und 70 px. Verlangt werden neun:
35, 36, 40, 54, 56, 60, 61, 65, 70. Die Größe ist keine Wahl, sondern eine
Vorgabe des Geräts (`launcherIcon.width` in seiner `compiler.json`). Fehlt sie,
skaliert `monkeyc` und meldet es — **42 der 99 Geräte** bauten mit genau dieser
einen Warnung, und hochskaliert ist das Symbol unscharf.

Jetzt trägt jede Größe einen eigenen Ordner `resources-icon<N>`. Stufe I meldet
**0 Warnungen auf allen 99 Geräten**.

**Und es kostet nichts.** Kein einziges der 99 Kompilate ändert seine Größe —
kein Byte, in keiner Richtung. Garmin legt Bitmaps palettiert und in fester
Breite ab: Der Platzbedarf hängt an den Maßen, nicht am Inhalt. Ein scharfes
Symbol ist hier also gratis.

### Uhr — Die Bilder haben endlich ein Rezept

Die PNG unter `watch/resources*/drawables/` sind Ableitungen der beiden SVG in
`server/assets/images/`. Bis hierher lagen sie ohne Rezept da: Wer eine Größe
ergänzen wollte, musste raten, mit welcher Breite und welcher Ausrichtung die
vorhandenen entstanden waren. Genau davor warnt `tools/logos/LIESMICH.md` seit
P3 für das Favicon — „das PNG ist eine Ableitung und soll keine sein, die
jemand in einem Bildprogramm nachbaut".

Neues Werkzeug `tools/uhr-bilder/erzeugen.sh`. Sein Rezept ist aus den
vorhandenen Dateien **zurückgerechnet** und reproduziert sie bitgleich
(`compare -metric AE` = 0 für alle vier, die es vorher schon gab). Das ist der
Beleg, dass es das richtige ist und nicht bloß ein ähnliches — an einer Stelle
hing er an einem einzigen Pixel: Die NEF-Breite ist `int(Kachel × 0,78)`,
**abgerundet**. Kaufmännisch gerundet ergäbe 70 × 0,78 = 55 statt 54, und der
Altstand wäre nicht mehr reproduzierbar.

Der einzige beabsichtigte Unterschied betrifft die beiden **Launcher-Symbole**:
Sie stammten aus der Zeit vor der Vektorumstellung und waren aus einem
40-px-Bitmap hochskaliert. Sie kommen jetzt aus derselben Vorlage wie alles
andere (RMSE 5,9 % auf der 70er Fassung, gleiche Zeichnung).

### Uhr — Ordner nach Größe, nicht nach Gerät

`resources-icon70` trug bisher Symbol **und** Bildmarke. Das geht nicht
weiter: Ein Gerät mit 60-px-Symbol gibt es bei 360, 390, 416 und 454 Pixeln
Displayhöhe — Symbolgröße und Displayhöhe laufen nicht miteinander. In einem
Ordner zusammengefasst bräuchte es eine Kombination je Paar, 18 Ordner statt
14.

Deshalb getrennt: `resources-icon<N>` trägt nur das Symbol,
`resources-marke<K>` nur die Bildmarke. Der Umbau ist beweisbar folgenlos —
die 105er Kacheln wurden aus `resources-icon70` nach `resources-marke105`
verschoben und dort bitgleich neu erzeugt; die sechs betroffenen Geräte sehen
unverändert aus.

Die passenden Jungle-Zeilen erzeugt `geraeteklassen.py --bloecke`. Der
`resourcePath`-Block im Jungle ist damit nicht mehr handgeschrieben.

### Uhr — Nebenbei: `tools/logos/erzeugen.mjs` war kaputt

Es zeigte auf `gen-em_logo_fahrzeug.svg` und schrieb `favicon-fahrzeug.png` —
beide Namen gibt es seit dem Austausch des NEF-Platzhalters nicht mehr; jeder
Aufruf brach mit `ENOENT` ab. Namen berichtigt, Werkzeug gelaufen: Das Ergebnis
ist von den Dateien im Repositorium nicht zu unterscheiden (gleiche Zeichnung,
andere Kantenglättung). Die Dateien selbst bleiben deshalb, wie sie sind.

Der Platzhalter-Abschnitt in der dortigen `LIESMICH.md` beschrieb einen
Austausch „1:1, gleicher Name, gleicher viewBox". So ist es nicht gekommen —
der Name wurde ein anderer und der viewBox auch (420 × 420 statt
400,16 × 249,81). Der Abschnitt hält jetzt fest, was daraus folgte.

## [Uhr 1.10.1] — 2026-08-31

**Die Sync-Seite behauptet nicht mehr „Sync vollständig", wenn die Uhr gar
nicht senden kann.** Backlog Nr. 11.

### Uhr — Zwei Aussagen, die einander widersprachen

Ohne hinterlegte Server-Adresse zeigte dieselbe Anzeige gleichzeitig das grüne
„Sync vollständig" mit Haken **und**, drei Zeilen tiefer, „Erst Server-Adresse
setzen". Dasselbe bei gesetzter Adresse ohne Kopplung.

Die Ursache war keine Nachlässigkeit im Text, sondern eine verwechselte Frage.
`Model.backlogCount()` beantwortet ausschließlich „liegen abgeschlossene Pakete
zum Senden bereit?" — vor dem ersten Dienst zu Recht `0`. Die Seite machte
daraus eine Aussage über den **Übertragungsweg**, den die Uhr zu diesem
Zeitpunkt nie benutzt hatte. Eine Fehlerzeile, die den Widerspruch aufgelöst
hätte, gab es nicht: `SyncView.refresh()` stößt `syncAll()` nur bei
vorhandenem Rückstand an, `Uploader.lastError` blieb also `null`.

### Uhr — Ein dritter Zustand

Der grüne Zustand setzt jetzt **beides** voraus: eine Server-Adresse *und* eine
Kopplung. Fehlt eines davon und liegt kein Rückstand vor, tritt an seine Stelle
ein Einrichtungszustand — rot „Nicht eingerichtet", darunter gedämpft der
nächste Schritt („Erst Server-Adresse setzen" bzw. „START halten: Gerät
koppeln"). Der bisherige Fußzeilenhinweis **wird damit zur Hauptaussage** und
wird unten nicht wiederholt; zweimal dieselbe Zeile ist auf einem
Uhrendisplay verschenkter Platz.

Zwei Entscheidungen dabei, beide im Code vermerkt:

**Der Rückstand behält den Vortritt.** Liegen Pakete offen *und* fehlt die
Einrichtung, bleibt die Zahl die Hauptaussage und der Hinweis steht darunter.
Ein Widerspruch entsteht dort nicht — es sind Pakete offen, und daneben steht,
warum sie liegen bleiben. Der Einrichtungszustand ersetzt also nur den grünen
Fall, nicht die Zahl.

**Kein neuer Baustein.** Zwei Textzeilen — Zustand rot, Weg heraus in Hellgrau —
sind genau das Muster, das die Kopplungsmeldung auf derselben Seite seit jeher
benutzt (`Pair.status` / `Pair.statusHint`). Rot für „Einrichtung fehlt" ist
dieselbe Farbe wie auf dem Startbildschirm und in der bisherigen Fußzeile: ein
Zustand, eine Farbe (Uhr-Layoutregeln, Abschnitt 7). Kein Haken, kein Symbol —
der Zustand ist weder erledigt noch fehlgeschlagen.

### Uhr — Geprüft

Fünf Zustände im Simulator, jeder mit Bildabzug. Die Zustände 4 und 5 über ein
Probekompilat, dessen `backlogCount()` fest `3` liefert — ein Rückstand lässt
sich sonst nur über einen vollständigen Dienst herstellen:

| | Einrichtung | Rückstand | Anzeige |
|---|---|---|---|
| 1 | nichts | 0 | „Nicht eingerichtet" / „Erst Server-Adresse setzen" |
| 2 | nur Adresse | 0 | „Nicht eingerichtet" / „START halten: Gerät koppeln" |
| 3 | vollständig | 0 | grün „Sync vollständig" mit Haken |
| 4 | vollständig | 3 | „3 / Pakete offen", keine Fußzeile |
| 5 | nichts | 3 | „3 / Pakete offen" **plus** roter Hinweis unten |

Auf fenix6pro (260 px) alle fünf, auf der Venu 3s (390 px) die Zustände 1 und 3
— die beiden, deren Blockhöhe sich geändert hat. Die strenge Typprüfung `-l 3`
meldet unverändert dieselben vier bekannten Fundstellen, keine davon in
`SyncView.mc`.

## [Uhr 1.10.0] — 2026-08-31

**Die Bildmarke auf dem Startbildschirm ist wählbar: luft- oder bodengebunden.**
Backlog Nr. 60, entschieden zugunsten der App-Einstellung statt einer
Server-Übertragung — die Uhr kennt die Kontoeinstellung nicht, und eine
Einstellung, die man auf der Uhr sieht, gehört auch dorthin.

### Uhr — Warum überhaupt

Die Anwendung dokumentiert Einsätze luft- **wie** bodengebunden. Die
Weboberfläche trägt dem seit Web 9.9.0 Rechnung (`logo_wahl` je Konto,
`app_state.logo_standard` je Installation) und begründet es dort so:
„eine Installation, die überwiegend am Boden fährt, soll nicht dauerhaft einen
Hubschrauber im Kopf tragen." Die Uhr tat bis hierher genau das — bei jedem
Dienstbeginn, auch im Nachtdienst am Boden. Dieselbe Linie führt `Const.mc`
seit 1.8.0 bei den Phasenbeschriftungen.

### Uhr — Wie es eingestellt wird

Neue App-Einstellung **„Bildmarke auf dem Startbildschirm"** (Garmin Connect,
neben Server-Adresse und Touchbedienung) mit drei Werten: *Luftgebunden*
(Vorgabe), *Bodengebunden*, *Wechselnd*.

Zwei Eigenheiten, beide im Code vermerkt. **Die Property führt Zahlen, keine
Wörter**: `settingConfig type="list"` liest die Werte als Zahl ein und bricht
bei Text mit `For input string: "luft"` ab. Die Bedeutung tragen
`Const.LOGO_LUFT`, `LOGO_BODEN`, `LOGO_WECHSELND`. Und **„Wechselnd" würfelt
einmal je App-Start**, nicht bei jedem Zeichnen: `onUpdate` läuft ständig neu,
das Bild spränge sonst mitten im Dienst.

Ein Fehler beim Lesen der Einstellung kostet nichts — die Vorgabe bleibt
stehen. Die Bildmarke ist Zierde, kein Zugang.

Die Ressourcen heißen jetzt `LogoLuft` und `LogoBoden` (Dateien
`logo_luft.png`, `logo_boden.png`, je in 70 und 105 px). Das kostet ein
zweites Bild im Kompilat: **+5 888 Byte** auf fenix6pro, +12 864 auf venu3s.

### Uhr — Beide Motive aus der Vektorvorlage

Beide Bildmarken sind aus den SVGs der Weboberfläche gerastert
(`gen-em_logo_helicopter_weiss.svg`, `gen-em_logo_nef_weiss.svg`), nicht aus
den alten PNG hochskaliert.

Die Vorlagen haben unterschiedliche Seitenverhältnisse — der Hubschrauber liegt
quer (400×250), das NEF quadratisch (420×420). Blind in dieselbe Kachel
gesetzt, wäre das NEF mit 70×57 gegenüber 70×44 deutlich schwerer erschienen.
Es steht deshalb auf **78 % der Kachelbreite**, womit beide Motive praktisch
gleich hoch sind: 45 gegen 44 Pixel in der kleinen Stufe, je 66 in der großen.

Auf das Layout wirkt sich die Wahl ohnehin nicht aus: `logoH` liefert die
**Kachelhöhe**, nicht die Motivhöhe — beide Dateien sind 70×70 beziehungsweise
105×105, der Rest ist durchsichtig.

### Uhr — Im Simulator nachgesehen (nachgetragen)

Die Bildmarken waren zunächst nur übersetzt, nicht gesehen. Nachgeholt am
31.08.2026 mit `tools/uhr-pruefstand` auf zwei Geräten und drei Einstellungen —
**neun Simulatorläufe**, jeder mit Bildabzug:

| Einstellung | fenix6pro (260 px) | venu3s (390 px) |
|---|---|---|
| 0 Luftgebunden | Hubschrauber | Hubschrauber |
| 1 Bodengebunden | Fahrzeug | Fahrzeug |
| 2 Wechselnd | 4 Starts: 1× Luft, 3× Boden | — |

Dazu **Stufe I über alle 99 Geräte** noch einmal, weil 1.10.0 jedem Kompilat
ein zweites Bild mitgibt: 99 übersetzt, **0 Fehlschläge**, Größen zwischen
168 652 B (`fr255`) und 179 996 B (Venu-Reihe). Die 42 Geräte mit genau einer
Warnung tragen unverändert die Meldung zum hochskalierten Launcher-Symbol
(Backlog Nr. 61) — **keine neue Warnung** durch die zweite Bildmarke.

Dazu eine Probe auf den Fall, der bei einer **App-Aktualisierung** eintritt:
Der Schlüssel `logoWahl` steht noch nicht im Einstellungsspeicher. Ein Kompilat,
das absichtlich `"gibtsNicht"` liest, fällt sauber auf die Vorgabe zurück und
zeichnet weiter — der `catch (e)` in `Ui.logoRes()` trägt also.

Der Lauf hat **zwei Fallen im Prüfstand selbst** freigelegt, beide jetzt
behoben und in dessen `LIESMICH.md` festgehalten:

**Der Simulator merkt sich die App-Einstellungen** unter
`/tmp/com.garmin.connectiq/GARMIN/APPS/SETTINGS/<GERAET>.SET` und füllt die
Datei **nur beim ersten Laden** aus den Vorgaben des Kompilats. Zwei Läufe
zeigten deshalb dieselbe Bildmarke, obwohl das zweite Kompilat die andere trug —
ein falsches Negativ, das ohne Gegenprobe als „die Einstellung wirkt nicht"
durchgegangen wäre. Neuer Befehl `einstellungen-leeren`; `starten` weist auf
eine stehende Datei hin. Das **Verzeichnis** bleibt dabei stehen: Fehlt es ganz,
wirft `Properties.getValue()` einen Fehler, den kein `catch` fängt, und das
Display bleibt schwarz. Auf einem Gerät kann das nicht eintreten — dort legt
die Installation den Speicher an.

**Anzeige und Simulator brauchen `setsid`, nicht nur `nohup`.** Eine Umgebung,
die jeden Befehl in einer eigenen Shell ausführt, räumt beim Verlassen die
ganze Prozessgruppe ab; die Anzeige war fort, bevor der nächste Befehl sie
brauchte.

## [Web 9.14.1] — 2026-08-31

**Sieben Verweise zeigten auf gelöschte Bilddateien.** Der Logo-Wechsel
(Commit „Update Logos") hat die Dateien getauscht, ohne den Code
nachzuziehen — und weil ein Push auf `main` mit Änderungen unter `server/`
sofort deployt, war der Stand live.

### Web — Was kaputt war

Aus `gen-em_logo_fahrzeug*` wurde `gen-em_logo_nef*`, aus `favicon.png` und
`favicon-fahrzeug.png` wurden `favicon_helicopter.png` und `favicon_nef.png`
— mit Unterstrich statt Bindestrich. Der Code kannte nur die alten Namen:

| Stelle | zeigte auf | Folge |
|---|---|---|
| `ui.php` (2×) | `favicon.png` | **jede Seite** lud ein 404-Favicon |
| `db.php` | `favicon-fahrzeug.png`, `favicon.png` | Favicon fehlte in beiden Zweigen |
| `session_lib.php` | `gen-em_logo_fahrzeug` | wer „Fahrzeug" gewählt hatte, sah **kein Logo** |
| `db.php` (`logo_src`) | `gen-em_logo_fahrzeug` | ein eigenes Logo wurde fälschlich als mitgeliefertes erkannt |
| `update.php` | `gen-em_logo_fahrzeug*.svg` | die Platzhalter-Prüfung lief ins Leere |

### Web — Warum nur die Pfade

Der **Einstellungswert** heißt weiterhin `'fahrzeug'` — er steht so in
`users.logo_wahl` und `app_state.logo_standard`. Geändert hat sich allein der
**Dateistamm**. Beides auseinanderzuhalten spart eine Migration gespeicherter
Werte; `LOGO_WAHLEN` bleibt unverändert, und niemand muss `update.php`
aufrufen.

### Web — Eine Annahme, die nicht mehr trägt

`logo_platzhalter_liegt()` in `update.php` prüft, ob der NEF-Platzhalter noch
liegt, und der Kommentar sagte dazu: *„Die echte Datei ersetzt ihn 1:1 —
gleicher Name, gleicher viewBox, kein Eingriff im Code."* Genau das ist nicht
eingetreten: Der Ersatz kam unter neuem Namen und mit anderem viewBox
(400×250 quer wurde zu 420×420 quadratisch). Die Funktion prüft jetzt **beide**
Namen — eine ältere Installation, die den Platzhalter noch unter dem alten
Namen trägt, soll den Hinweis weiterhin bekommen.

## [Uhr 1.9.0] — 2026-08-30

**Die App unterstützt 99 statt drei Geräte, und sie sagt beim Koppeln, auf
welchem sie läuft.** Zwei Dinge, die zusammengehören: Wer die Geräteliste
öffnet, will hinterher wissen, welche davon tatsächlich getragen werden.

### Uhr — Von drei auf 99 Geräten

Die Auswahl folgt abgestimmten Regeln, die als Konstanten in
`tools/uhr-pruefstand/geraeteklassen.py` stehen: Uhren mit API ≥ 3.1
(das `minApiLevel` der App) und mindestens 128 kB Speicher, davon die vier
größten Geräteklassen, dazu alle Fenix ab Fenix 5 und alle Forerunner ab 2018.
Von 173 Gerätedateien bleiben so **122 taugliche Uhren**, davon sind **99
ausgewählt** — verteilt auf nur **vier Klassen**.

Bemerkenswert daran: Die Sonderregeln für Fenix und Forerunner bringen **keine
einzige zusätzliche Klasse**. Sie überlappen vollständig mit den vier größten;
die vier Klassen allein hätten dieselbe Liste ergeben. Herausgefallen sind 23
Geräte in acht kleinen Klassen, darunter die einzigen **eckigen** (`venusq`,
`venusq2`, `venux1`) und die Instinct-Reihe — genau die Formen, für die
`Ui.chordW()` mit seiner Kreissehnen-Formel nicht gebaut ist.

Die Geräteliste im Manifest ist nach Klassen gruppiert und kommentiert. Die
Regel darüber hat sich dabei geändert: Sie verlangte, **jedes** neue Gerät
einzeln auszumessen. Bei 96 neuen ist das weder zu leisten noch sinnvoll — ein
Gerät zeigt nichts, was sein Klassenvertreter nicht schon zeigt, und die Höhe
innerhalb einer Klasse skaliert stetig. Gemessen wird jetzt **je Klasse**. Eine
neue Klasse bleibt dagegen immer eine Messung wert.

### Uhr — Die stille Falle im Jungle

`base.sourcePath` steht auf dem Fünf-Tasten-Profil. Ein neu eingetragenes
Touch-Gerät ohne eigene UP/DOWN-Tasten erbt es stillschweigend, **übersetzt
sauber und ist auf dem Gerät unbedienbar** — der Compiler merkt davon nichts.
Für die 99er-Auswahl brauchen **13 Geräte** deshalb explizit `source-tasten3`:
die Venu-Reihe, `approachs50`, die Approach S70, `d2air`, `d2airx10` und
`vivoactive5`.

`resources-venu3s` heißt jetzt `resources-icon70`. Der Ordner trägt die
70×70-Fassung von Symbol und Bildmarke, und die brauchen sechs Geräte, nicht
nur die Venu 3s.

### Uhr — Was die Uhr beim Koppeln über sich meldet

`pair.php` bekommt neben dem Code einen Block `geraet`: Teilenummer,
Displaymaße, Touch, Firmware, Connect-IQ- und App-Fassung. Feldliste und
Begründungen stehen im JSON-Vertrag, Abschnitt 1a. **Der Server verwirft ihn
derzeit stillschweigend** — die Auswertung ist Backlog Nr. 59.

Warum überhaupt selbst zählen: Für die Frage, welche Uhren künftig unterstützt
werden sollen, gibt es keine brauchbare äußere Quelle. Garmin veröffentlicht
keine modellgenauen Verkaufszahlen, und der Connect-IQ-Store schlüsselt
Installationen nicht nach Gerät auf — Entwickler fragen das seit Jahren an.
Wer es wissen will, muss selbst zählen.

Gesendet wird die **Teilenummer**, nicht der Modellname: Den kennt die Uhr
nicht, `DeviceSettings` führt ihn nicht. Die Teilenummer ist eindeutig und
serverseitig auflösbar — 325 Teilenummern führen auf 173 Modelle, samt
Geräteart. Eine Modelltabelle auf einem Gerät mit 128 kB wäre der falsche Ort.

**Nicht gesendet wird `uniqueIdentifier`.** Das ist eine dauerhafte,
geräteweite Kennung; für eine Stückzahl-Statistik wird sie nicht gebraucht, und
in einer kleinen Gruppe wäre sie ein Personenbezug mehr, als die Frage
rechtfertigt. Die Zuordnung leistet die `device_id`. Aus demselben Grund hält
Nr. 59 fest, dass die Datenschutzerklärung die Erhebung benennen muss, bevor
ausgewertet wird.

### Uhr — Was geprüft ist

**Stufe I, alle 99 Geräte übersetzt: 99 erfolgreich, 0 fehlgeschlagen.** 57
davon ohne jede Warnung, 42 mit genau einer — durchweg die Skalierung des
Launcher-Symbols. Kein Gerät scheitert an einer fehlenden API-Funktion, keines
am Speicher. Die Kompilate liegen zwischen 162 940 B (`fr255`) und 179 980 B
(`approachs7047mm`), im Mittel bei 169 723 B.

**Stufe II, 20 Vertreter im Simulator (5 je Klasse): alle 20 starten und
rendern den Startbildschirm**, keine Absturzmeldung. Sichtbar bestätigt hat
sich dabei die Eingabe-Zuordnung: Die Venu-Reihe, `approachs50` und `d2airx10`
zeigen „Action drücken", die Tastengeräte „START drücken" — das
Drei-Tasten-Profil greift also dort, wo es soll.

### Uhr — Was dabei offen bleibt

Zwei Punkte, beide Gestaltungsarbeit und deshalb nicht nebenbei entschieden:

**Die Launcher-Symbole sind unvollständig.** Die 99 Geräte verlangen neun
Größen (35, 36, 40, 54, 56, 60, 61, 65, 70 px); im Repositorium liegen zwei.
`monkeyc` skaliert und meldet es als Warnung — hochskaliert wird das Symbol
unscharf. Die Kompilate wachsen davon übrigens nicht nennenswert: Gemessen
kostet der vollständige 70er-Satz gegenüber dem geerbten 40er **6 432 Byte**,
und zwar nur in den Geräten, die ihn bekommen. Jedes `.prg` trägt allein seine
eigenen Ressourcen.

**Die Bildmarke sitzt auf großen Displays zu klein.** Sie wird mit
`dc.drawBitmap` 1:1 gezeichnet und über die *Launcher-Symbolgröße* zugeordnet
statt über die Displayhöhe. Das passt bei der Venu 3s zufällig und geht sonst
auseinander: **37 Geräte** bekämen eine Bildmarke, die nur 15–18 % der
Displayhöhe einnimmt, wo die bestehende Gestaltung auf 25–27 % ausgelegt ist.
Auf einer Fenix 9 Pro 51 mm säße das Logo verloren in der Mitte. Die
Bildschirmfotos aus Stufe II zeigen genau das — auf 208 px sitzt die Bildmarke
satt, auf 466 px verliert sie sich. Die Lösung wäre eine Staffelung nach
Displayhöhe in drei Stufen.

Dazu kommt eine Frage, die dabei aufgefallen ist und über die Größen
hinausgeht: **Die Weboberfläche lässt zwischen Hubschrauber, Fahrzeug und
„wechselnd" wählen (`logo_wahl`, `app_state.logo_standard`) — die Uhr weiß
davon nichts.** Sie lädt eine feste `Rez.Drawables.Logo` und führt gar kein
zweites Motiv mit; keine Schnittstelle überträgt die Wahl. Eine Installation,
die überwiegend am Boden fährt, sieht auf der Uhr trotzdem bei jedem
Dienstbeginn einen Hubschrauber. Das widerspricht der Linie, die `Const.mc`
seit 1.8.0 bei den Phasenbeschriftungen verfolgt und die `session_lib.php` für
die Weboberfläche ausdrücklich begründet.
## [Uhr 1.8.2] — 2026-08-30

**Die strenge Typprüfung (`-l 3`) meldet statt 226 noch 4 Punkte.** Fortsetzung
von 1.8.1, das nur die Warnungen des normalen Baus behandelt hatte. Wieder ohne
Verhaltensänderung — aber diesmal nicht ohne Preis, siehe unten.

### Uhr — Die Zahl 226 war irreführend

Der erste Blick zählte Meldungen, und das führt in die Irre: Eine einzelne
Zeile wie `m["final"] == true` erzeugt bis zu **16 Meldungen**, weil der Prüfer
jeden denkbaren Typ des Sammeltyps einzeln durchgeht — von
`WatchFaceConfig.Id` über `ScanResult` bis `BitmapReference`. Gezählt nach
Fundstelle waren es **77 Stellen**, nicht 226. Davon sind jetzt **4** übrig.

### Uhr — Drei Muster, immer dieselben

Fast alles ließ sich auf drei Ursachen zurückführen.

**Erstens `Storage.getValue()`.** Es ist mit einem Sammeltyp über alles
Speicherbare deklariert. Jede Zuweisung daraus an ein konkretes Feld ist unter
`-l 3` ein Fehler, obwohl an der Stelle längst feststeht, was dort liegt —
geschrieben hat es das zugehörige `save()` wenige Zeilen weiter oben. Die
Zusicherungen benennen jetzt, was die Struktur ohnehin ist.

**Zweitens die Null-Flussanalyse.** Der Prüfer verfolgt eine Prüfung wie
`if (mission == null) { return; }` **nur über lokale Variablen**, nicht über
ein Modul-Feld hinweg. `info.position` blieb deshalb „möglicherweise null",
obwohl zwei Zeilen darüber genau das ausgeschlossen wurde. Betroffene Stellen
holen den Wert jetzt zuerst in eine lokale Variable. Das ist kein Trick,
sondern die Form, in der die Prüfung überhaupt greifen kann.

**Drittens fehlende Parametertypen.** `_haversine(lat1, lon1, lat2, lon2)` und
`_addDisplayPoint(lat, lon)` waren untypisiert, ebenso drei Member in `Track`.

Dazu einige Einzelfälle: `Gregorian.Info` führt seine Felder nominell nullbar,
bei `FORMAT_SHORT` sind es immer Zahlen; `u.substring(...).equals("/")` wurde zu
`"/".equals(u.substring(...))`, weil `substring` null liefern kann; aus
`x == true` wurde `true.equals(x)`.

### Uhr — Der Preis: 400 Byte

Anders als bei 1.8.1, wo die Kompilate **kleiner** wurden, kosten diese
Zusicherungen Platz: **+448 Byte** auf fenix6pro und fr945, **+480 Byte** auf
venu3s — rund 0,27 % des Kompilats. Das ist wenig, aber es ist nicht nichts,
und es geht in die andere Richtung als beim letzten Mal. Wer künftig abwägt, ob
sich weitere Typarbeit lohnt, sollte diese Zahl kennen: Warnungsfreiheit im
normalen Bau war gratis, `-l 3` ist es nicht.

### Uhr — Was bewusst stehen bleibt

Vier Stellen melden weiterhin denselben Fall: `Storage.setValue()` mit einem
Dictionary oder Array (`Model.mc:93`, `Pair.mc:113`, `Track.mc:162`,
`Uploader.mc:171`). Der erwartete Typ ist enger als das, was sich zusichern
lässt — beim Punktpuffer etwa, weil dort `null` vorkommen darf, wenn die Höhe
fehlt. Das aufzulösen hieße, die Datenstruktur zu ändern, und damit echtes
Verhalten. Für vier Meldungen ist das der falsche Preis.

### Uhr — Zwei Nebenfunde, mit erledigt

`Input.lPageDown()` und die Konstante `L_PAGE_DOWN` in beiden Geräteprofilen
waren toter Code: definiert, nirgends aufgerufen. Sie sind entfallen —
`lSelect()` und `lSelectHold()` bleiben, die werden benutzt.

Die Kommentare an `CprView.onPreviousPage/onNextPage` ordneten die
Wischrichtungen falsch zu. Gemessen im Simulator gilt: Wischen **runter** ist
`onPreviousPage`, Wischen **hoch** ist `onNextPage` — bei den Tasten ist es
UP beziehungsweise DOWN. Die Kommentare sagen das jetzt.

## [Uhr 1.8.1] — 2026-08-30

**Der Uhr-Code übersetzt ohne Warnung.** Backlog Nr. 13, seit Web 5.4.0 offen.
Reiner Feinschliff: kein Verhalten geändert, keine Funktion ergänzt, keine
Migration. Die Weboberfläche bleibt unberührt.

### Uhr — Warum das liegen blieb

Der Punkt stand als „Kosmetik" in der Liste, und das war er auch — nur ließ er
sich nicht abarbeiten, solange niemand die Warnungen erzeugen konnte. Der Bau
setzte einen eingerichteten Arbeitsplatz voraus; aus jeder anderen Umgebung war
der Uhr-Code blind. Mit `tools/uhr-pruefstand` ist das nicht mehr so, und damit
wurde aus „einige Warnungen" eine Zahl: **29**, davon 28 vom Typ „Cannot
determine if container access is using container type", verteilt auf
`ClockView.mc`, `CprView.mc`, `Model.mc`, `Track.mc` und `Uploader.mc`.

### Uhr — Was tatsächlich fehlte

Der Wortlaut der Warnung klingt nach einem Zugriffsproblem, gemeint ist aber
eine fehlende Typangabe. Die Arrays waren als `Lang.Array` **ohne Elementtyp**
deklariert. Bei `items[i][2]` wusste der Prüfer deshalb nicht, ob das innere
Ding überhaupt indizierbar ist — die Zusicherung auf den Einzelwert
(`as Lang.String`) stand längst da, es fehlte die Angabe am Behälter.

Ergänzt wurden `Lang.Array<Lang.Array>` für die Tupellisten — Menüeinträge
`[Label, Farbe, ID]`, Phasen, Reanimationsereignisse — und
`Lang.Array<Lang.Dictionary>` für die beiden Warteschlangen in `Model`.

Zwei Stellen waren mehr als eine Zeile. Der Punktpuffer in `Track` heißt jetzt
`Lang.Array<Lang.Numeric or Null>`; der naheliegende erste Versuch mit
`<Lang.Number>` erzeugte **drei Übersetzungsfehler**, denn dort liegen Breite
und Länge als `Double`, die Höhe als `Float` und der Zeitstempel als `Number`
nebeneinander — und die Höhe kann fehlen. Eine falsche Typbehauptung ist
schlechter als gar keine, deshalb der genaue Typ statt des bequemen. Die lokale
Variable `chunk` wiederum ließ sich nicht annotieren („Local variable types are
inferred"), weshalb die Zusicherung an die Zuweisung aus `Storage.getValue()`
wanderte.

Die 29. Warnung war eine nicht erreichbare Anweisung: ein `return true;` hinter
`System.exit()` in `StartView.actBack()`. Es ist entfallen. Ein Kommentar hält
fest, warum dort keines steht — sonst liest sich die Stelle wie ein Versehen.

### Uhr — Was das gebracht hat

**0 Warnungen, 0 Fehler** auf allen drei Zielgeräten. Die Kompilate sind dabei
**kleiner** geworden: fenix6pro und fr945 je 16 Byte, venu3s 32 Byte. Die
Typangaben kosten zur Laufzeit also nichts — auf einer Uhr keine
Selbstverständlichkeit, sondern der Grund, warum diese Art Aufräumen überhaupt
vertretbar ist. Alle drei Geräte starten im Simulator und rendern den
Startbildschirm unverändert.

### Uhr — Was bewusst offen bleibt

Die strenge Typprüfung `-l 3` ist **nicht** Teil dieser Änderung. Sie meldet
weiterhin **211 Fehler** (vor dieser Änderung 226), überwiegend
Rechenoperationen mit unklaren Typen. Das ließe sich nicht durch Typangaben
beheben, sondern nur durch Umbau an vielen Stellen — ein eigenes Vorhaben, kein
Feinschliff, und auf einem Gerät mit knappem Speicher nichts, was man nebenbei
macht.

## [Werkzeug: Uhr-Prüfstand] — 2026-08-30

**Der Uhr-Code lässt sich jetzt auch ohne eingerichteten Arbeitsplatz
übersetzen und im Simulator starten.** Weder die Weboberfläche noch die Uhr-App
sind geändert, deshalb trägt dieser Eintrag keine Versionsnummer — das weicht
vom bisherigen Schema ab, in dem Werkzeuge immer innerhalb einer
Anwendungsversion miterwähnt wurden.

### Werkzeug — Warum

Bisher galt für die Uhr: Build nur mit VS Code, Monkey-C-Erweiterung und
installiertem SDK. Jede Änderung am Monkey-C-Code außerhalb dieses
Arbeitsplatzes war damit blind — kein Kompilat, kein Simulatorlauf, nur Lesen.
Für einen Punkt wie Backlog Nr. 13 (Typprüfer-Warnungen) ist das zu wenig: Man
kann die Warnungen nicht zählen, die man nicht erzeugen kann.

### Werkzeug — Was der Prüfstand beschafft

`tools/uhr-pruefstand/pruefstand.sh` baut auf einem nackten Linux-Rechner auf,
was der Simulator braucht. Drei der vier Teile gehen von allein: das SDK von
`developer.garmin.com` (ohne Anmeldung abrufbar), die Systembibliotheken aus
den Ubuntu-Quellen, der Entwickler-Schlüssel per `openssl` — für den Simulator
genügt jeder gültige Schlüssel.

Der vierte Teil geht nicht von allein und bleibt es auch. **Gerätedateien und
Zeichensätze liefert nur der SDK-Manager**, eine Fensteranwendung mit
Garmin-Anmeldung, die sich ohne Bildschirm nicht bedienen lässt. Sie werden
deshalb von einer selbst bereitgestellten Quelle geholt; deren Adresse steht in
`CIQ_GERAETE_URL` und **bewusst nicht im Repositorium** — es ist öffentlich,
und die Dateien gehören Garmin. Aus demselben Grund werden sie nicht
eingecheckt.

Zwei Fallen kostete das. Der Simulator ist gegen `webkit2gtk 4.0` gebunden, das
Ubuntu 24.04 nicht mehr führt; die 22.04-Stände liegen jetzt **neben** dem
Simulator statt im System, damit alles unberührt bleibt, was 4.1 erwartet. Und
fehlen die Zeichensätze, übersetzt die App zwar, bricht aber beim ersten
Zeichnen mit `Invalid Font Specified` ab — die Meldung zeigt auf die eigene
Zeile und meint die Umgebung. Beides steht in der `LIESMICH.md`, damit es beim
nächsten Aufbau nicht erneut gesucht werden muss.

### Werkzeug — Was damit belegt ist

Alle drei Zielgeräte übersetzen (`fenix6pro` 165 KB, `fr945` 165 KB, `venu3s`
175 KB) und starten im Simulator. Der Startbildschirm rendert auf jedem Gerät
mit dem richtigen Bedienhinweis — „START drücken" auf den Tastengeräten,
„Action drücken" auf der Venu 3S; das Geräteprofil greift also nachweislich.
Der Bau meldet **29 Warnungen**, davon 28 „container access" und eine nicht
erreichbare Anweisung; die strenge Typprüfung `-l 3` meldet **226**. Das sind
die Zahlen zu Backlog Nr. 13, die bislang fehlten.

Bedienung ist simulierbar: Tipp, Langdruck und Wischgeste kommen als
X-Ereignisse bis in die App durch, gemessen mit der Eingabe-Probe auf der
Venu 3S.

### Werkzeug — Was bewusst nicht gelöst ist

Der Prüfstand ersetzt die echte Uhr nicht, und die Grenzen aus
`Geraete-Eingabe.md` gelten unverändert: keine Systemgesten, kein Halten über
4,6 s, keine Kopplung, kein Server. Die App meldet im Simulator „Server fehlt" —
richtiges Verhalten, kein Fehler. Und ein Lauf zeigt, dass es startet und wie
es aussieht, nicht dass es richtig ist.


## [Web 9.14.0] — 2026-08-30

**Die erste Rückmeldungsrunde nach P3.** Vierzehn Punkte aus einer Durchsicht
mit Bildschirmfotos — und vier Fehler, die dabei ans Licht kamen. Was alle
verbindet: Kein einziger hätte von einem Prüfmittel gefunden werden können.
Sie brechen nichts. Sie sehen nur falsch aus. Keine Migration.

### Die Seitenleiste lief über die Kopfleiste

Zwei Fehler in einer Regel, und der erste steckt in vier Zeichen:

```css
.leiste{ position:sticky; top:var(--kopf); inset:auto; }
```

`inset` ist die Kurzform für alle vier Seiten. Es setzt das `top` eine Zeile
davor wieder auf `auto` — die Leiste klebte also gar nicht, sondern scrollte
mit. Gemessen bei 600 px Scrollhöhe: Sie stand auf **−544 px**. Dazu blieb ihr
`z-index: 60` aus der Schubladen-Regel stehen, während die Kopfleiste auf 40
liegt; sie malte darüber statt dahinter. Jetzt steht `inset` **zuerst** und
`top` danach, und der z-index geht auf 1 zurück — ab 1024 px ist die Leiste
ein Teil des Rasters und braucht keine eigene Ebene.

### Ein toter Streifen unter jeder Segmentwahl

Die Segmenttaste ist ein `<label>`, und die Grundformen geben jedem `label`
12 px Abstand nach unten. Innerhalb des Segmentrahmens ist das kein Abstand,
sondern Leere im Kasten: **Rahmen 58 px, Tasten 44**. Betroffen war *jede*
Segmentwahl der Anwendung — Wochentage, Dreiwertfilter, Zeitraum-Reiter,
Logo-Wahl. Das erklärt zwei Rückmeldungen auf einmal („Wochentagauswahl sieht
komisch aus", „Tabelle passt irgendwie nicht"). Jetzt: **46 px**, also die
Tasten plus die beiden Haarlinien.

Die zweite Hälfte des Fundes ist die lehrreichere. `.segment-taste{margin:0}`
allein half nicht — in der Filterleiste stand:

```css
.filterfelder label{margin-bottom:var(--abstand-3)}
```

Diese Regel setzt **genau den Wert, den die Grundform `label` schon setzt**.
Sie tat nichts, außer mit ihrer höheren Spezifität (0,1,1) den Baustein
(0,1,0) zu schlagen. Eine Dublette ist nie harmlos: Sie tut nichts, bis sie
etwas verhindert.

### Welches Feld verschlüsselt ist, stand nicht mehr da

Bis O4 trug jedes geschützte Feld ein Schloss-Emoji. O4 hat sie durch **eine**
Meldung über den Karten ersetzt — und dabei die Auskunft *je Feld* verloren.
Sie kommt zurück, aber auf der richtigen Ebene:

- In der Karte **PatientIn** ist alles verschlüsselt. Das sagt jetzt eine
  Plakette am Kartenkopf, einmal.
- Die drei geschützten Felder der **Einsatz**-Karte (Einsatzort, Beschreibung
  Einsatzort, Diagnose) stehen zwischen Klartextfeldern und tragen ihr
  Schloss einzeln — mit `<title>`, damit es auch ein Bildschirmleser nennt.

### „Wechselnd" gibt es jetzt auch für die Installation

Die Wartung kannte zwei Werte. Der dritte war nicht einfach dazuzuschreiben:
`logo_stamm()` hätte „wechselnd" durchgereicht und wäre stumm beim
Hubschrauber gelandet — die Einstellung wäre da gewesen und hätte nichts
getan. Es gibt deshalb `logo_standard_aufgeloest()`, und der Würfel fällt **je
Sitzung**, nicht je Seitenaufruf; sonst spränge das Logo beim Blättern. Ein
Adminwechsel wirkt trotzdem sofort: Gemerkt wird nur das *Ergebnis* des
Würfelns.

### Kopfleiste

Das Wortzeichen heißt **„Gen-EM Einsatzdoku"** (vorher „Einsatzdoku"), das
Logo wächst von 26 auf **34 px**, und der Kontoname steht auf 15 statt 13 px.
Er war außerdem nicht wirklich lotrecht zentriert: Das Wortzeichen trug
`line-height: 1`, der Name die geerbte 1,55 — die höhere Zeilenbox schob seine
Grundlinie nach unten. Beide auf dieselbe Zeilenhöhe, und die Mitten liegen
jetzt gemessen **beide auf 28 px**.

Unter 480 px fällt das Wortzeichen auf 16 px: Bei 360 px Fensterbreite
bräuchte es 193 px und bekommt 187 — sechs zu wenig, und die Marke endete auf
einer Ellipse.

### Die übrigen Punkte

- **Besatzung und Notizen** laufen ab 720 px über *beide* Rasterspalten. Die
  Besatzung ist eine Aufzählung aus bis zu sieben Rollen und brach in der
  halben Breite um — neben einer leeren Spalte. Gemessen: 545 → **1114 px**,
  eine Zeile.
- **Das Aktionsmenü** steht auf Gewicht 400 statt 600. Nicht 500 als
  Mittelweg: Open Sans liegt in 400, 600 und 700 vor — die Angabe wäre still
  auf 400 gefallen, und im Stylesheet stünde eine Zahl ohne Bedeutung.
- **Die Einsatztabelle**: Spaltentitel zentriert (die Zellen behalten ihre
  Ausrichtung), „Dauer" ohne Umbruch, und „Sekundärtransport" und
  „Fehleinsatz" mit weichem Trennzeichen statt hartem `<br>` — beides ist
  *ein* Wort, und das `<br>` trennte es ohne Bindestrich.
- **Die Reanimations-Karte** erscheint nur noch, wenn es eine Sitzung gibt.
  Sie war die einzige Karte der Einsatzansicht, die leer stehen blieb und
  „keine" sagte, während jede andere verschwindet.
- **Im Einsatzformular** hatte das Ortsfeld 12 px zwischen Beschriftung und
  Feld, jedes andere Feld 4 — „Einsatzort" hing zwischen den Feldern, statt zu
  seinem zu gehören. Der Kleintext unter einem Feld rückt an dieses heran
  (vorher 16 px darunter und 4 px über dem nächsten: Er las sich als
  Überschrift des folgenden). Die Zustandszeile passt in eine Zeile —
  gemessen 480 von 532 px verfügbarer Breite.
- **In der Suche** tragen die von/bis-Paare ihren Namen jetzt *über* sich:
  „Strecke von (km)" brach in der 280 px schmalen Leiste um, „bis" daneben
  nicht, und die beiden Eingabefelder standen versetzt. Drei weitere Paare
  waren dabei zu finden, darunter „mit PatientIn von" mit demselben Fehler.
  Was eine Bildschirmleserin hört, bleibt vollständig — das steht im
  `aria-label`.
- **Die Überschrift „FILTER"** war mit 12 px das kleinste Element in der
  Leiste, die sie ordnet; jetzt 13 px. Die Gruppentitel gehen auf 16 px und
  stehen damit über den Feldtiteln (15 px), statt gleichauf.
- **In den Einstellungen** bekommt die erste Stammdatenliste Abstand nach
  oben (der Hinweis „[systemweit] …" klebte an „Rettungsmittel"),
  „luftgebunden" und „bodengebunden" stehen nebeneinander statt untereinander,
  und „Kopplungscode erzeugen" steht im `.listen-form-fuss` wie jeder andere
  Knopf am Ende eines Formulars.

### Ein neues Token

`--symbol-klein` (16 px) für das Zusatzzeichen an einer Beschriftung. Die
Symbolskala hieß 20 und 24; 16 setzt sie im selben 4-px-Schritt nach unten
fort. Nachgetragen in `docs/Design.md`, Kapitel 4.

## [Web 9.13.0] — 2026-08-30

**O12: die Gestaltungsrichtlinie.** Zwölf Arbeitspakete haben eine Oberfläche
gebaut, aber keine Regel hinterlassen, die man nachschlagen kann. Das Wissen
stand verteilt: die Token im Stylesheet, die Bausteine in `ui.php`, die
Begründungen in den Kopfkommentaren von `version.php`, die Entscheidungen im
Konzept. Wer eine neue Seite baut, findet dort alles — aber erst, nachdem er
alles gelesen hat. **`docs/Design.md`** ist die eine Stelle: Marke,
Farbrollen, Token, Schrift, Grundregeln, Schwellen, Symbole, Bausteine,
Seitentypen, Prüfmittel.

Keine Migration, keine Änderung am Datenmodell; am Server ändert sich eine
einzige Zeile (siehe F-P3-BC unten).

### Der Einstieg ist eine Tabelle, keine Einleitung

Kapitel 9 beginnt mit *„Wenn du X willst, nimm Y"* — 27 Zeilen von der
Absicht zum Baustein, samt der Spalte „nicht": *eine Liste von Einträgen →
`ui_zeile()` in einer Karte, **nicht** eine `<table>`.* Das ist die Frage, mit
der jemand das Dokument aufschlägt; alles andere ist die Antwort auf die
zweite Frage. Steht ein Fall nicht in der Tabelle, ist das der Moment für eine
Rückfrage — nicht für ein neues Element (CLAUDE.md §9).

Am Ende desselben Kapitels stehen die **Anti-Muster**: zehn Fallen, jede davon
in P3 tatsächlich hineingetreten. Eine Klasse auf einem Kästchen, die gegen
`input[type=checkbox]` verliert (F-P3-AP, F-P3-AZ). `knopf-gefahr` im
Aktionsblatt, wo `blatt-gefahr` hingehört (F-P3-AX). Die doppelte Rückfrage.
Ein Formular ohne `forms.js`. `:nth-child` für Spaltenbreiten. Ein
Unicode-Zeichen statt eines Symbols. Eine Tabelle mit erfundenen
Fehlernummern wäre wohlfeil gewesen; diese zehn haben je einen Fund als Beleg.

### Vier Tabellen werden erzeugt, nicht abgeschrieben

`tools/design/tabellen.py` liest **87 Token** aus `:root` (in den 15 Gruppen,
die das Stylesheet selbst überschreibt), **19 Medienblöcke** über fünf
Breiten, **44 Symboldateien** mit ihren Tabler-Namen und **32 Bausteine** aus
`ui.php` mit Klasse, Zeilennummer und Markup — und setzt daraus das Markup der
Kapitel 4, 7, 8 und 9. Eine abgeschriebene Tabelle ist ab dem ersten Tag
falsch; diese ist mit einem Aufruf wieder richtig.

### Die Lizenzen stehen jetzt zusammen

**`docs/Lizenzen.md`** nennt die drei Bibliotheken mit Version, Lizenz und
SHA-256 (Leaflet 1.9.4, SheetJS 0.18.5, zip.js 2.8.34), die zwei
Schriftfamilien, den Symbolvorrat — und, davon **getrennt**, die Dienste, die
zur Laufzeit angesprochen werden, wenn die Nutzerin eine Karte öffnet
(Kartenkacheln, Photon). Genau diese Trennung fehlte bisher: Die Zusage „keine
fremde Quelle zur Laufzeit" gilt für Code und Schriften, nicht für
Kartenkacheln — und das war nirgends gesagt.

### `docs/Branding.md` ist abgelöst

Sein Verbindliches steht in `Design.md`; die Datei ist entfernt. Ihre drei
offenen Punkte sind erledigt und dort als solche vermerkt:

- **B1** — die Logodateien trugen Näherungen der Markenfarben (Rot `#E3322B`
  statt `#D63338`). Berichtigt in O1.
- **B2** — keine geschlossene Größenskala. Es gibt jetzt eine: sieben
  Schriftgrößen (12/13/15/16/19/24/28 px) und drei Zeilenhöhen, je mit der
  Angabe, wofür sie gilt.
- **B3** — 78 Hexwerte verstreut im Stylesheet. Heute steht **kein**
  Farbwert mehr außerhalb von `:root` (gemessen: 0).

### F-P3-BC: zwei tote Token, und dahinter eine zu schmale Leiste

Die Vollständigkeitsprüfung meldete `--leiste-filter` und
`--leiste-filter-schmal` als unbenutzt. Sie waren es — und der Grund war kein
vergessenes Aufräumen, sondern ein Fehler: Die **Filterleiste der Suche** trug
seit O6 nur `.leiste` und war damit 220 px breit (bzw. 260 ab 1200 px) statt
der für sie vorgesehenen 240/280 px. Sie trägt mehr als eine Tagesliste —
Datum von/bis, drei Auswahlfelder, Freitext —, und dafür waren die 220 px zu
knapp. `ui_geruest_start()` vergibt jetzt zusätzlich `leiste-filter`.

Zwei Pakete lang unbemerkt, weil eine zu schmale Leiste nicht bricht, sondern
nur enger umbricht. Ein totes Token ist nicht immer Müll; manchmal ist es die
Quittung für eine Regel, die nie angekommen ist.

### Ein Prüfmittel, das wieder gelesen wird

Die Vollständigkeitsprüfung meldet Klassen, die im Markup stehen und im
Stylesheet keine Regel haben. Diese Gegenprobe hat in O11 den ungestalteten
Export-Knopf gefunden — 23 px hoch statt 44, weil er `btn-primary` trug, eine
Klasse ohne Regel. Genau **ein** echter Fund unter 29 Zeilen: Acht davon sind
Bruchstücke zusammengesetzter Klassennamen (`'plakette-' + ton` — das Werkzeug
liest Zeichenketten, nicht ausgeführten Code), fünfzehn sind Skriptanker ohne
eigenes Aussehen. Eine Liste in diesem Mischungsverhältnis wird nach dem
dritten Mal überflogen statt gelesen, und findet dann auch den nächsten echten
Fund nicht.

`tools/vollstaendigkeit/ohne-regel.md` funktioniert jetzt wie die
Streichliste: **`[bleibt]`** = begründet ohne Regel, verschwindet aus dem
Befund und wird nur gezählt. **`[offen]`** = die Frage ist offen, bleibt ein
Befund, aber unter eigener Überschrift. Alle 29 Namen sind am Fundort
nachgesehen und einzeln begründet. Ergebnis: **0 ohne eingetragenen Grund**
statt 29, **6 als `[offen]`** — und die Befunde gesamt fielen von 247 auf
**224**.

Damit die Liste nicht selbst verwahrlost, meldet die Prüfung ihre eigenen
toten Einträge: Wessen Klasse inzwischen eine Regel hat oder aus dem Markup
verschwunden ist, steht als „Eintrag ungenutzt" da — dieselbe Disziplin, die
die Wortliste seit P2 hat.

Die sechs offenen sind Entscheidungen, keine Reste: `imp-warn` ist ein
Warnhinweis, der aussieht wie Fließtext; `imp-daygroup` eine
Gruppenüberschrift, die aussieht wie eine Datenzeile. Sie stehen als Backlog
Nr. 41 — jede Antwort darauf ist eine neue Darstellung und braucht eine
Freigabe.

### Der Stilvergleich wacht wieder

Er ruhte während P3, weil er dort die falsche Frage stellte: Wenn jede
beabsichtigte Änderung ein Treffer ist, misst er nur noch die eigene
Arbeitsmenge. Für P4 ist er neu geeicht:

- **13 Fensterbreiten** von 360 bis 1920 px statt bisher neun. Die alten
  endeten bei 500 px und kannten die 390er-Klasse der Telefone nicht.
- Die Seitenproben lesen jetzt auch die **HTML-Schnipsel aus
  PHP-Zeichenketten** (`proben.py`, `php_zeichenketten()`). Das ist der blinde
  Fleck, vor dem seine `LIESMICH.md` seit P0 warnte: Markup, das aus `echo
  '<div class="…">'` stammt, war für ihn unsichtbar. Gemessen: **228 Klassen
  vorher, 253 nachher.**

### Das Handbuch bleibt stehen

Ausdrückliche Entscheidung: Das Handbuch beschreibt die *Bedienung*, und die
ändert sich bis 1.0 noch. Es einmal jetzt und einmal vor der Auslieferung zu
schreiben wäre dieselbe Arbeit zweimal. Angepasst wurde nur, was ohne Wert
veraltet ist: die 14 Unicode-Zeichen im Text (kein Bildschirmleser spricht
„✕" als „Schließen") und drei Bildschirmfotos.

### Das Prüfdokument ist abgeschlossen

`docs/Pruefdokument-P3-Oberflaeche.md` stand seit O8c auf demselben Stand. Es
ist jetzt vollständig — mit Mittel **und** Zahl zu jedem der zwölf Pakete —,
und in zwei Punkten anders als vorher.

**Was nicht erreicht wurde, steht vorn.** Abschnitt 1.3 nennt es mit Zahl und
Backlog-Nummer: 158 Unicode-Treffer, davon drei echte Symbole (Nr. 42); sechs
Klassen ohne Regel mit offener Frage (Nr. 41); 55 Altklassen ohne Begründung
(Nr. 40); das zurückgestellte Handbuch. Abschnitt 1.4 nennt die **drei
Migrationen** der Phase — ohne den Aufruf von `update.php` steht die Anwendung.

**Die Prüfliste hat einen kurzen Weg.** Zwölf Pakete ergeben eine Liste, die
niemand abarbeitet. Abschnitt 5.0 ist neu: vierzehn Punkte, die die Phase als
Ganzes abnehmen, in etwa einer Stunde. Die ausführliche Fassung je Paket
(5.1–5.16) steht daneben.

### Auch geändert

- `README.md` zeigt vier Bildschirmfotos („So sieht es aus") und verweist auf
  Design und Lizenzen statt auf Branding.
- `CLAUDE.md` §5 zeigt auf `docs/Design.md`, nennt die Freigaberegel für neue
  Bausteine und die 44-px-Regel.
- `docs/Technik.md`: Verzeichnisbaum um `tools/design/`, `tools/pruefkonten/`
  und `tools/rechtstexte/` ergänzt; der Stilvergleich steht nicht mehr auf
  „ruht".

## [Web 9.12.0] — 2026-08-30

**O11: die übrigen Seiten — und die Übergangsschicht fällt.** Neun Seiten sind
aus Bausteinen neu gebaut: Papierkorb, Zuordnung nachtragen, Diensttag anlegen
/ Datum ändern / löschen / zusammenführen, Einsatz verschieben / löschen und
die Wartungsseite. Damit ist keine Seite der Anwendung mehr im alten Zustand.

### Es gibt keine Verwaltungstabelle mehr

Sechs Tabellen sind zu Karten mit Zeilen geworden. Der Papierkorb hatte fünf
Spalten, die Wartungsseite vier, die Zusammenführung sechs — bei 360 px lief
jede von ihnen waagerecht aus dem Bild. Die Notbremse aus der
Übergangsschicht (`table{display:block; overflow-x:auto}`) hat das abgefangen,
aber abgefangen ist nicht gelöst: Eine Tabelle, in der man seitwärts schieben
muss, um die Aktionsspalte zu sehen, ist auf einem Telefon keine Liste,
sondern ein Hindernis.

Geblieben sind die drei **Einsatztabellen** (Tagesübersicht, Suche, Zeitraum),
die unter 720 px zur Kachel werden, und die **Importtabelle** — sie tragen
alle den Baustein `.tabelle`.

### Löschbestätigungen bleiben Seiten

Das Konzept sah für O11 „Bestätigungen als Aktionsblatt (mobil) bzw. Dialog
(Desktop)" vor. Für die Rückfragen, die sich in *einem Satz* beschreiben
lassen, gilt das auch — dafür ist `confirm.js` da. Die vier Löschseiten
bleiben aber Seiten, und der Grund steht auf ihnen: Was dort steht, ist eine
**Aufstellung** — Einsätze, Phasen, Reanimationen, Ruhesegmente, Trackpunkte.
Ein Dialog, der einen halben Bildschirm Text trägt, ist keiner mehr; und der
Weg dorthin hat eine eigene Adresse, die man zurückgehen kann.

Die Aufstellungen selbst haben sich geändert: aus Aufzählungen sind **Zeilen
mit Plakette** geworden. Die Zahl ist die Auskunft, und im Fließtext („6
Einsätze mit allen Angaben") war sie beim Überfliegen nicht zu finden.

### Keine Speichern-Leiste auf diesen Seiten

Die Leiste gehört zu Formularen, die man *bearbeitet* und deren Stand man
verlieren kann; sie erscheint mit der ersten Änderung und klebt unten fest.
Auf den O11-Seiten ist der Knopf das **Ziel des Weges** — „Diensttag anlegen",
„Einsatz verschieben", „Datum ändern" — und steht am Ende des Formulars, wo
man ihn sucht. `data-dirty-track` bleibt trotzdem: Es trägt die
Verlassen-Warnung und die bedingte Abbrechen-Rückfrage; die Leiste ist nur
einer seiner Verwender.

### Zwei Seiten, die dabei besser geworden sind

**Zuordnung nachtragen** hatte eine Tabelle mit fünf Spalten, von denen eine
zwei Auswahlfelder und einen Knopf enthielt. Bei 360 px war die Auswahl
praktisch nicht zu treffen. Jetzt steht je Diensttag ein Formularblock:
Überschrift, Kennzeile (Zeitraum · Einsätze · bisherige Zuordnung), zwei
Felder nebeneinander ab 720 px, ein Knopf.

**Zusammenführen** zeigte wählbare und nicht wählbare Diensttage in *einer*
Tabelle, die nicht wählbaren mit abgeschaltetem Radio und der Klasse
`zeile-aus` — die es im neuen Stylesheet gar nicht mehr gibt. Sie sahen also
aus wie die anderen, und ein abgeschaltetes Radio ist auf einem Telefon kaum
von einem leeren zu unterscheiden. Jetzt stehen die wählbaren in einer
Wahlliste mit 44-px-Zeilen und die übrigen darunter in einer eigenen,
zugeklappten Karte — mit dem Grund an jeder Zeile.

### Die Übergangsschicht ist aufgelöst

Abschnitt 17 des Stylesheets hieß **Rohschicht** und war ausdrücklich
befristet: „dieser Block stirbt mit O11." Er tut es. Weg sind

- die beiden Klassen-Ausnahmen **`.alert`** und **`.muted`** — zuletzt eine
  Stelle in PHP und eine in JS bzw. 16 Stellen in sechs Dateien. `.muted` trug
  dabei vier Rollen gleichzeitig; sie sind auf die vier Bausteine verteilt,
  die sie meinte: `.feld-hinweis` (Absatz), `.feld-klein` (Absatz unter einem
  Feld), `.feld-klein-inline` (Zusatz in einer Beschriftung), `.dash`
  (gedämpfte Tabellenzelle);
- die Elementregeln für **`table`/`th`/`td`** — die letzte Tabelle ohne
  eigene Regel war die des Imports (`.imp-table` hatte selbst nie eine), sie
  trägt jetzt `.tabelle`;
- die Elementregeln für **`fieldset`/`legend`** und **`hr`** — jeweils null
  Verwendungen in der ganzen Anwendung.

Der Abschnitt heißt jetzt **Grundformen** und trägt nur noch, worauf die
Bausteine aufsetzen: `input`/`select`/`textarea`, Kästchen und Radios, das
Muster `<label>Text <input></label>`, `summary` und `code`/`kbd`/`pre`. Der
Unterschied ist nicht bloß der Name: Eine Rohschicht ist ein Versprechen auf
später, eine Grundform ist eine Entscheidung.

**Die Label-Regeln bleiben** — abweichend vom ursprünglichen Plan. Das Muster
steht an 46 Stellen, darunter die Filterreihen der Suche (22) und das
Einsatzformular (8). Sie zu tilgen hieße, die beiden kompliziertesten Seiten
der Anwendung für eine Regel umzubauen, die nichts falsch macht: `.feld` ist
der *Baustein* für ein beschriftetes Feld, nicht das Gebot, dass jede
Beschriftung einer sein müsse.

### Vier Reparaturen an Bausteinen, zwei Funde beim Streichen

Die vier Bausteinreparaturen stehen in Web 9.11.1 (Vollbild der Karte,
„Löschen" im Blatt, doppelte Rückfrage, ausgeblendete Kästchen). Beim
Auflösen der Übergangsschicht kamen zwei weitere dazu:

**Der Export-Knopf war ungestaltet (F-P3-BA).** `import.php` trug an einer
Stelle noch `btn-primary` — eine Klasse ohne Regel seit Web 9.0.0. Gemessen:
23 px hoch, ohne Fläche, ohne Rahmen, ohne Radius, in der Textschrift; der
Nachbarknopf im selben Formular ist 44 px, orange, Bricolage. O8c hat die
Seite umgebaut und diesen einen Knopf übersehen.

**`kreislauf.py --frisch` konnte seit Web 9.9.0 kein Umlaufkonto mehr
löschen (F-P3-BB)** — aus zwei Gründen gleichzeitig: Sein Ausdruck suchte
`<a href="admin_user.php?id=N">adresse</a>` und fand nichts mehr (die
Kontenliste ist seit O9b eine Tabelle mit `data-ziel` bzw. eine `.zeile` mit
gewickeltem Text), und die Löschung liegt seit O9a auf der Kontoseite und
verlangt die abgetippte Adresse. Unbemerkt geblieben, weil der Weg nur
betreten wird, wenn das Konto schon besteht — beim ersten Lauf auf einer
frischen Datenbank endet die Funktion eine Zeile früher.

### Nebenbei

- Der **Papierkorb** holte sich für jede Löschbestätigung einen Umfang, den er
  nie ausgab (`trash_scope_day()`); das kostete je Einsatz drei weitere
  Abfragen. Ersatzlos gestrichen — die Funktion bleibt, `diensttag_loeschen.php`
  braucht sie wirklich.
- Die **Statusspalte der Migrationsliste** trug ✔ ● ! ✖ ⚠ — Schriftzeichen als
  Symbol, was E-P3-18 ausschließt. Der Status steht jetzt als Plakette mit Ton
  („erledigt" blau, „steht aus" orange, „blockiert" rot).
- `artzeichen` ist gestrichen: das Breitenkorsett des Art-**Emojis**, das seit
  O2 ein SVG ist.
- `papierkorb_misch.mjs` zählte 12 Konsolenfehler, die keine waren — sein
  Kachelfilter sah nur den Meldungstext an, und „Failed to load resource:
  net::ERR_CONNECTION_RESET" trägt keine URL darin. Er prüft jetzt auch die
  Adresse, wie die Bildaufnahme seit O3.

## [Web 9.11.1] — 2026-08-30

**Vier Reparaturen an geteilten Bausteinen.** Sie sind beim Aufräumen vor O11
aufgefallen und stehen vor dem Paket, nicht darin: Jede war schon vorher
kaputt, drei davon an Stellen, die O11 gar nicht anfasst. Was sie verbindet,
ist die Art des Fehlers — alle vier waren *lautlos*. Nichts brach, nichts
meldete sich; die Oberfläche sah nur an einer Stelle anders aus, als sie
sollte.

### Der Vollbildknopf der Karte tat auf iOS nichts (F-P3-AW)

`map_fullscreen.js` nimmt die Fullscreen-API, wo es sie gibt, und sonst einen
Rückfall über die Klassen `map-fs` und `map-fs-lock` — „relevant v. a. iOS
Safari, das `requestFullscreen()` für beliebige Elemente nicht unterstützt",
sagt der Dateikopf. Diese beiden Klassen haben seit dem Neubau des Stylesheets
(Web 9.0.0) **keine Regel mehr**. Der Rückfall war seit vier Monaten tot:
gemessen 366 × 160 px vor wie nach dem Druck, nur die Beschriftung wechselte
auf „Vollbild verlassen". Jetzt 390 × 800 px.

Die alten Zeilen zurückzukopieren hätte den Fehler stehen lassen — der
Kartenbehälter heißt seit O1 `.geo` und nicht mehr `.map`. Der `z-index` ist
bei der Gelegenheit von 2000 auf 70 gefallen, die Ebene des Blatts; höher
braucht die Anwendung nicht mehr, seit `.geo` einen eigenen Stapelkontext hat.
Dazu eine `:fullscreen`-Zeile, die auch am Schreibtisch nötig ist: Das
Browser-Stylesheet setzt Größe und Rand mit `!important`, den `border-radius`
aber nicht — die Karte hätte im Vollbild abgerundete Ecken auf schwarzem Grund
gehabt.

Unbemerkt geblieben ist es, weil der Weg nur auf iOS genommen wird und die
Bildaufnahme den Vollbildzustand nicht herstellt.

### „Löschen" war im Blatt nicht rot (F-P3-AX)

`ui_zeilenaktionen()` vergab die Klasse `knopf-gefahr` auch im Blatt. Dort
setzt aber `.blatt-zeile` seine Schriftfarbe selbst, mit gleicher Spezifität
und später in der Datei — also gewinnt sie. Gemessen an „Löschen" in der
Stammdatenliste: `rgb(26,5,0)`, dieselbe Farbe wie „Bearbeiten"; jetzt
`rgb(158,34,38)`, und das Symbol rot statt dunkelblau.

Betroffen waren sechs Aufrufstellen, darunter „Gerät entkoppeln" und „Konto
löschen". Am Schreibtisch stimmte alles, weil `.knopf` keine Farbe setzt —
**mobil** sah die unumkehrbarste Handlung der Anwendung harmlos aus.
Aufgefallen ist es niemandem, weil die Bildaufnahme kein Blatt öffnet.

Der Baustein kennt jetzt beide Vokabeln und wählt danach, wo er steht.

### Zwei Rückfragen hintereinander (F-P3-AY)

Ein Formular mit `data-confirm` **und** `data-dirty-track` fragte nach der
bestätigten Rückfrage ein zweites Mal, diesmal der Browser: „Änderungen werden
möglicherweise nicht gespeichert." Ursache ist das `stopPropagation()` der
Erfassungsphase in `confirm.js`: Der Zuhörer von `forms.js` hängt in der
Blasenphase am selben `document` und läuft deshalb nie, und `f.submit()` löst
gar kein `submit`-Ereignis aus. Das Formular blieb für `forms.js` bis zuletzt
„schmutzig".

Genau das, was `forms.js` für den Abbrechen-Weg ausdrücklich verhindert:
„zweimal dasselbe fragen heißt, die erste Frage nicht ernst zu nehmen."
`confirm.js` sagt jetzt ab. Betroffen war `diensttag_datum.php` — die einzige
Stelle mit beiden Attributen, und dort praktisch immer, weil man das Feld
ändern *muss*, um etwas zu tun.

### Das unsichtbare Kästchen lag nicht, wo es sollte (F-P3-AZ)

`.schalter-box` und `.wahl-box` haben Spezifität (0,1,0) und verlieren gegen
`input[type=checkbox]` aus der Rohschicht (0,1,1), die jedem Kästchen
20 × 20 px gibt. Gemessen: 20 × 20 statt 0 × 0 — und weil weder `.schalter`
noch `.wahlliste` `position:relative` trägt, saß das ausgeblendete Kästchen
auf seiner statischen Stelle über dem linken Rand der Beschriftung.

Dieselbe Falle wie F-P3-AP, zum dritten Mal. Sie verschwindet mit der
Rohschicht, die in O11 fällt; bis dahin steht der lange Selektor.

### Der Rückfragedialog hat einen Namen

Bis hierher hatte er keinen: kein Titel, kein `aria-label`, kein `role`.
Ausgerechnet die Rückfrage vor dem Löschen war damit die anonymste Stelle der
Oberfläche — ein Screenreader las den Text und zwei Knöpfe, ohne zu sagen,
*was* da fragt. Er trägt jetzt eine Überschrift („Bestätigen", je Aufrufstelle
über `data-confirm-titel` überschreibbar) und `role="alertdialog"`. Die Rolle
ist für genau diesen Fall da: eine Meldung, die eine Antwort verlangt und den
Ablauf anhält — der Text wird beim Öffnen vorgelesen, nicht erst, wenn der
Fokus ihn erreicht.

## [Web 9.11.0] — 2026-08-30

**O10: Anmeldung, öffentliche Seiten und Rechtstexte (R32).**

> **Diese Fassung braucht eine Migration.** Nach dem Aufspielen muss eine
> Administratorin **`update.php`** aufrufen. Ohne den Aufruf gibt es die
> Tabelle `rechtstexte` nicht; Impressum und Datenschutz zeigen dann ihren
> Leerzustand. Die Anwendung läuft weiter — die neue Funktion ist nur nicht da.

### Impressum und Datenschutzerklärung

Die Anwendung hat zum ersten Mal beides — und zwar **keine mitgelieferten**.
Was darin steht, ist Sache des Betreibers; wir stellen zwei öffentliche Seiten,
einen Editor unter Einstellungen → Rechtstexte und die Verweise in jeder
Fußzeile. Eine mitgelieferte Datenschutzerklärung wäre eine Rechtsauskunft, die
dieses Projekt nicht geben kann.

Der **Leerzustand ist die Auslieferung** und eine gültige Antwort: „Der
Betreiber dieser Installation hat noch kein Impressum hinterlegt." Für
angemeldete Admins steht der Weg zum Editor daneben.

Das **Standdatum wird von Hand gesetzt**. Automatisch wäre bequemer und an
einem Rechtstext falsch: Das Datum sagt, auf welchem Stand der Text
*inhaltlich* ist — eine Kommakorrektur soll ihn nicht neu datieren. Leer heißt:
keine Standzeile.

### Der Renderer maskiert zuerst und erkennt dann Struktur

`rt_html()` ist die einzige Stelle des Projekts, an der aus einer Eingabe HTML
wird. Sie schickt den **ganzen** Text durch `htmlspecialchars`, bevor der
Parser das erste Zeichen ansieht. Rohes HTML ist damit nicht gefiltert, sondern
unmöglich — wenn der Parser `<` sieht, ist es längst `&lt;`. Eine Sperrliste
von Tags wäre der falsche Ansatz: Sie ist immer unvollständig, und die Lücke
findet man erst, wenn sie jemand benutzt hat.

Erzeugt werden ausschließlich `h2`, `h3`, `p`, `br`, `ul`, `ol`, `li` und `a`
mit `href`. Linkziele stehen auf einer **Positivliste** (https, http, mailto,
eigene `.php`, Anker) — `javascript:`, `data:`, `vbscript:`, `blob:`, `file:`
und alles, was es morgen gibt, fallen ohne eigenen Eintrag durch. Ein
abgelehntes Ziel lässt die ganze Konstruktion **als Text** stehen: Stilles
Schlucken macht aus einem Fehler eine Unsichtbarkeit.

Nicht unterstützt, jeweils mit Grund: Bilder (holten eine fremde Quelle zur
Laufzeit und brächen eine feste Zusage des Projekts), Autolinks und
Referenzlinks (umgehen die Zielprüfung), fett und kursiv (E-P3-38 nennt sie
nicht — jede Erweiterung ist eine Vertragsänderung), `target="_blank"` (auf
einer Rechtstextseite ist der Zurück-Weg des Browsers die richtige Antwort).

**Neues Prüfmittel `tools/rechtstexte/`:** 81 Proben — rohes HTML, Linkziele in
allen Schreibweisen, Attribut-Ausbruch, Autolinks, Bidi-Steuerzeichen („Trojan
Source"), Kodierungstricks, Ränder. Dazu werden 65 Ausgaben gegen eine
Positivliste erlaubter Tags und Attribute gehalten. Das ist die eigentliche
Prüfung: Die Einzelproben sagen, was schiefgehen kann; die Schranke sagt, dass
nichts anderes herauskommt.

### Eine eigene Tabelle, nicht `app_state`

Dort ist der Wert `VARCHAR(190)`; eine Datenschutzerklärung hat 8 000 bis
20 000 Zeichen. Ohne strict mode kürzt MySQL **still** — ein Rechtstext, der ab
Zeichen 191 verschwindet, sieht in der Vorschau vollständig aus, solange
niemand ans Ende scrollt. Bei einem Dokument, das rechtlich vollständig sein
muss, ist das der schlechteste denkbare Ausgang.

### Die Fußzeile führt jetzt immer auf beide Seiten

Die `is_file()`-Prüfung aus O2 war richtig, solange es die Seiten nicht gab, und
danach tote Logik: zwei Dateisystemzugriffe je Seitenaufruf für eine Frage,
deren Antwort feststeht. Sie sagte auch die falsche Sache — „die Datei ist da"
heißt nicht „ein Text ist hinterlegt". Ausnahme bleibt der **Einrichter**: Er
läuft vor der Ersteinrichtung, die beiden Seiten brauchen aber eine Datenbank;
der Verweis wäre eine Schleife.

### Der Einrichter trägt die öffentliche Hülle

Er hatte die Anmeldehülle — dunkelblaue Fläche, 400-px-Karte — und half sich
mit `.anmeldung-breit`, was der Sache nach die Lesespalte ist, nur unter
falschem Namen. Dazu hatte seine Kopfleiste dieselbe Farbe wie die Fläche
darunter; das Logo schwebte ohne sichtbare Leiste.

Jetzt: helle Lesespalte wie Abbruchseite und Rechtstextseiten, **fünf Karten
statt fünf `<fieldset>`**, alle Felder über den Baustein. Die Elementregeln für
`fieldset`/`legend` stehen in der Übergangsschicht des Stylesheets, die mit O11
stirbt — danach hätte der Einrichter wieder ohne Gestaltung dagestanden.

Das Konzept widersprach sich an dieser Stelle (E-P3-38 sagt „dunkle Hülle",
Tabelle 5.4 führt ihn unter „Öffentlich"). Es gilt die Tabelle.

### Drei Seiten derselben Familie

Anmeldung, Passwort-vergessen und Passwort-setzen haben jetzt **dieselbe
Kartenbreite** (400 px), dasselbe Logo und dieselben Bausteine. Die
Passwortseite war 760 px breit, die Anmeldung daneben 400 — zwei Seiten, die
man unmittelbar nacheinander sieht, sprangen dabei in der Breite.
Passwort-vergessen bekommt zum ersten Mal ein Logo; sie war die einzige der
drei ohne Marke.

### Kein Demo-Hinweis auf der Anmeldeseite

E-P3-38 sieht ihn vor, Mockup 32 zeigt ihn mit Zugangsdaten. Entschieden wurde
dagegen: Die Anmeldeseite einer Anwendung mit Patientendaten ist nicht der Ort
für ein Werbefeld, und die Zugangsdaten des Demo-Kontos stehen ohnehin in
README und Handbuch. Im Konzept ausgetragen.

### Drei Funde

**F-P3-AS — `login-wrap` war nie geschlossen.** Drei `<div>` gegen zwei
`</div>` in `pw_handling.php`, dazu eine Klasse ohne Regel. Das Element stand
zwischen `.anmeldung-body` und `<main class="anmeldung">`; damit war `main` kein
direktes Flex-Kind mehr, `flex:1 1 auto` griff nicht, und die Fußzeile klebte
unter der Karte statt am unteren Rand.

**F-P3-AT — die Fußzeile zeigte im Einrichter „v" ohne Zahl.** `WEB_VERSION`
ist dort nicht definiert: `version.php` kommt über `db.php`, und das braucht die
`config.php`, die es zu dem Zeitpunkt noch nicht gibt.

**F-P3-AU — der Erklärabsatz klebte an der Überschrift.**
`.seiten-erklaerung` hat einen negativen Rand oben, abgestimmt auf die
Titelzeile. Unter einem blanken `<h1>` — öffentliche Seiten und Einrichter
haben kein Gerüst und damit keine Titelzeile — zog er den Text heran.

### Zwei freigegebene Änderungen an geteilten Bausteinen

Die **Versionsnummer** der Fußzeile steht in `--gedaempft` statt `--sand`: von
1,53:1 auf 5,30:1. Sie ist die Auskunft, mit der ein Fehlerbericht anfängt, also
ein zu *lesender* Text; die Kontrastprüfung führte sie als Ausnahme, begründet
aber nur den Akkordeon-Winkel. **„Passwort vergessen?"** steht linksbündig statt
zentriert — es sitzt unter einem 100 % breiten Knopf und über dem linken Rand
der Feldbeschriftungen.

## [Web 9.10.1] — 2026-08-30

**Drei Reparaturen, die vor O10 stehen mussten.** Keine Migration — aber
`schema.sql` ändert sich, und das betrifft **Neuinstallationen**.

### Der Einrichter war tot

`install.php` lud die Seitenhülle erst **innerhalb** von `render_page()`. Die
Aufrufer bauen ihr Argument aber mit `ui_meldung_markup()`, `ui_knopf()` und
`ui_symbol()` — und PHP wertet Argumente vor dem Aufruf aus. Alle drei Zweige
endeten in „Call to undefined function", seit Web 9.1.0.

Das traf **jede Neuinstallation**: `index.php` leitet ohne `config.php` genau
dorthin, und der Deploy liefert die Datei aus. Aufgefallen ist es niemandem,
weil der Einrichter genau einmal im Leben einer Installation läuft — und die
bestehende läuft längst. Gefunden bei der Bestandsaufnahme zu O10.

### `schema.sql` war zwei Migrationen im Rückstand

Die Spalte `users.last_login` (Web 9.8.0) fehlte, und die Kennungen der
Migrationen `2026_08_27_logo_wahl` und `2026_08_28_last_login` standen nicht
in der Erledigt-Liste. Eine frisch eingerichtete Anwendung hätte die Spalte
gar nicht gehabt; die Nachtragsmigrationen wären erneut angesetzt worden und
entweder hängengeblieben oder — schlimmer — still durchgelaufen, weil
`update.php` den MySQL-Fehler 1060 („Duplicate column") schluckt.

Geprüft: `schema.sql` in eine Wegwerfdatenbank eingespielt, genau so, wie
`install.php` es tut — 32 Anweisungen, 30 Tabellen, `users.last_login`
vorhanden, 32 Migrationskennungen.

### Die Bildaufnahme fotografierte die Anmeldeseite

Der schwerste der drei Funde, weil er ein **Prüfmittel** betrifft. Der Lauf
meldete „31 Seiten, 0 Überlauf, 0 Konsolenfehler" — und 22 dieser 31 Seiten
waren Bilder von `login.php`: 176 von 248 Einzelbildern, byteweise identisch.

Zwei unabhängige Ursachen:

**Die Sitzung starb mitten im Lauf.** Das Demo-Konto setzt sich alle 30
Minuten zurück, und `demo_zuruecksetzen()` erhöht dabei die Sitzungs-Epoche;
`auth_guard.php` beendet daraufhin jede offene Sitzung. Der Lauf braucht
Minuten und löst den fälligen Reset durch seine **eigenen** Anfragen aus. Die
alte Prüfung stand einmal, unmittelbar nach dem Anmelden — danach hat nichts
mehr hingesehen. Jetzt wird nach **jedem** Seitenaufruf geprüft, bei Bedarf
neu angemeldet und einmal wiederholt; hilft das nicht, entsteht **kein** Bild,
sondern ein Fehler. Ein fehlendes Bild ist eine Auskunft, ein falsches eine
Lüge, die durch jede weitere Prüfung durchmarschiert.

**Vier Platzhalter wurden nie aufgelöst.** Die Kennungen der Einsatzseiten
holt das Werkzeug aus der Tagesübersicht — und lief als erstes in denselben
Sitzungsverlust. Fehlte die Kennung, blieb das Verzeichnis leer, und die vier
Seiten wurden mit ihrem eigenen Platzhalter als Adresse aufgerufen; der Server
antwortet darauf mit **200** und der Startseite. Ein nicht aufgelöster
Platzhalter ist jetzt ausdrücklich `null` und führt dazu, dass die Seite nicht
fotografiert wird.

Nach der Reparatur: **248 Bilder, 248 verschiedene Prüfsummen**, alle sieben
Platzhalter aufgelöst, ein bemerkter und behobener Sitzungsverlust im Bericht.
Die Zahlen aus O9c sind im Konzept berichtigt (F-P3-AQ).

### Die Wortliste stand auf fünf Treffern

O9c hatte das Werkzeug **vor** dem Schreiben der Dokumentation laufen lassen
und „0 Treffer" gemeldet; die danach geschriebenen Logo-Abschnitte in Handbuch
und Technik-Doku brachten fünf. Vier Ausnahmen der Klasse *Homonym* sind
nachgetragen — sie benennen ein Bild, nicht die Einsatzart, wie die sechs
gleichartigen davor. Jetzt wieder 0 Treffer, 62 Regeln, 0 ungenutzt.

## [Web 9.10.0] — 2026-08-30

**O9c: die drei übrigen Adminseiten.** Keine Migration.

### Sicherungen — eine Seite über Regeln, keine Liste mehr

Die Seite listete bisher **alle Konten** und darunter **alle Pakete aller
Konten**, jedes mit eigenen Formularen. Beides steht seit Web 9.8.0/9.9.0
anderswo: die Konten in der NutzerInnen-Liste, die Pakete eines Kontos auf
dessen Kontoseite. Was hier bleibt, ist das, was für **alle** gilt.

Oben vier Zahlen (Konten, Pakete samt Größe der Ablage, überfällig, nie
gesichert — die letzten beiden führen in die gefilterte Liste). Darunter drei
Karten: **Regeln**, **Ablage**, **Sicherungen ohne Konto**.

Die Regeln standen vorher an drei Stellen mit drei Speichern-Knöpfen; jetzt
sind es ein Formular und ein Knopf: Erinnerungsintervall, Aufbewahrung je Konto
und der Schalter für die Erinnerungsmail. Die Aufbewahrung war bis hierher eine
Konstante im Code (`EDBAK_MAX_JE_KONTO = 3`) — eine Zahl, die entscheidet, wann
Sicherungen gelöscht werden, gehört nicht in eine Datei, die man nur mit einem
Deploy ändern kann.

„Alle sichern" arbeitet die fälligen Konten ab, **das älteste zuerst**, in
einem Zeitbudget von 20 Sekunden. Wer nicht mehr hineinpasst, ist beim nächsten
Klick der älteste und kommt zuerst — die Reihenfolge sorgt selbst dafür, dass
wiederholtes Klicken zum Ziel führt. Das ist die kleine Antwort auf F-P3-C;
echte Schübe mit Fortschrittsanzeige bleiben für P5 vorgemerkt.

### Die wöchentliche Erinnerung an die Administration

Neu, abschaltbar, standardmäßig aus. Sie nennt die überfälligen und die nie
gesicherten Konten mit Adresse und Alter — **keine Namen, keine Zahlen aus den
Konten**: Eine Mail liegt unverschlüsselt im Postfach und auf jedem Server
dazwischen.

**Es gibt keinen Cron.** Auf diesem Webspace läuft kein Zeitplan; was
regelmäßig geschieht, fährt auf dem täglichen Aufräumjob mit, und der startet
bei der ersten Anfrage des Tages. Die Erinnerung ist deshalb genau genommen
keine Wochenmail, sondern: höchstens einmal je Woche, und nur wenn die
Anwendung an dem Tag überhaupt benutzt wurde. Wird sie zwei Wochen nicht
angefasst, kommt die Mail zwei Wochen später. **Das steht so auf der Seite** —
eine Zusage, die an der Benutzung hängt, muss man als solche kennzeichnen.

Sie kommt nur, wenn es etwas zu melden gibt. Eine Wochenmail „0 Konten
überfällig" ist nach dem dritten Mal keine Meldung mehr, sondern etwas, das man
wegklickt — und dann geht auch die vierte unter, in der etwas steht. Verschickt
wird **nach** der Antwort (`register_shutdown_function`); der Aufräumjob läuft
vor der Seitenausgabe, und ein SMTP-Gespräch dort wäre eine messbare
Verzögerung für jemanden, der damit nichts zu tun hat.

### Stammdaten systemweit — ein Menüpunkt statt zweier

„Standorte systemweit" und „Rettungsmittel systemweit" zeigten auf dieselbe
Datei mit demselben Symbol und unterschieden sich nur im Reiter. Jetzt steht
dort ein Punkt, und der Reiter ist eine Segmentwahl in der Titelzeile — dasselbe
Muster wie die Artwahl in der Zeitraumübersicht.

Dabei ist das Markup der Zeilen und Formulare in eine eigene Datei gewandert
(`server/stammdaten_ui.php`). Es stand bis hierher zu großen Teilen
zeichengleich in `einstellungen.php` **und** `admin_stammdaten.php`; die
Schließungen aus O8b ein zweites Mal zu kopieren hieße, denselben Fehler eine
Ebene höher zu wiederholen.

### Demo-Konto

Die Seite war seit dem Redesign ungestaltet: `table.data`, `pre.mono`,
`div.rowactions`, `button.btn-primary` — Klassen, deren Regeln in den
Bausteinen aufgegangen sind. Jetzt vier Kacheln für den Bestand, die drei
Papierkorbzahlen als Kontrollzeilen, die Handlungen in der Titelzeile
(„Zurücksetzen" als Knopf, „Entfernen" im Aktionsmenü hinter einer Rückfrage).
Das Prüfwerkzeug `tools/referenzdatensatz/browser/demo_pruefen.mjs` las die
alte Tabelle und ist mitgezogen.

### Das Logo der Installation ist einstellbar

In der **Wartung**, nicht im Profil: Es ist eine Eigenschaft der Installation.
Die Anmeldeseite zeigt es, und jedes Konto ohne eigene Wahl folgt ihm. Bis
hierher war es eine Konstante im Code — eine Installation, die überwiegend am
Boden fährt, sollte nicht dauerhaft einen Hubschrauber im Kopf tragen.

Die Änderung wirkt **sofort**, auch für bereits angemeldete Konten: In der
Sitzung steht jetzt die *Wahl* und nicht mehr ihr Ergebnis. Nur „wechselnd"
wird bei der Anmeldung ausgewürfelt — sonst spränge das Logo beim Blättern.
Wer im Profil eine eigene Wahl getroffen hat, bleibt unberührt.

### Drei Funde

**F-P3-AN — die Anmeldeseite zeigte nie den Standard der Installation.**
`logo_src()` versorgt die beiden Seiten ohne Sitzung (Anmeldung, Passwort
setzen) und las `app.logo_path` aus der `config.php`. Der Einrichter schreibt
dort den Hubschrauber hinein; ein Wechsel des Standards wirkte damit überall
außer auf der einen Seite, die ihn zeigen soll. `logo_path` gilt jetzt nur noch
für eine **fremde** Datei.

**F-P3-AO — die Standorteliste warnte nicht vor Namensdubletten.** Sie war die
einzige der sechs Stammdatenlisten ohne den weichen Hinweis auf gleichnamige
eigene Einträge. Ein systemweiter Standort, den bereits ein Dutzend Konten
selbst angelegt hatte, entstand ohne jeden Hinweis — und stand danach zweimal
in deren Auswahlliste.

**F-P3-AP — die Radios der Segmentwahl fingen Klicks ab.** `.segment-box`
(Spezifität 0,1,0) verliert gegen `input[type=radio]` (0,1,1) weiter unten im
Stylesheet, die jedem Radio 20 × 20 px gibt. Absolut positioniert und
durchsichtig lagen die Kästchen damit über ihrer Umgebung. Das betraf **jede**
Segmentwahl der Anwendung — Zeitraum, Suchfilter, die neuen Reiter. Aufgefallen
beim Bedienen im Browser, nicht beim Lesen.

Dazu behoben: Die Sammelleiste der NutzerInnen-Liste zeigte ihre Zahl in jeder
Breite, aber der Knopf daneben war unter 720 px 100 % breit — die Zahl brach auf
zwei Zeilen (40,3 px statt 20,1 px). Die Breitenausnahme hängt jetzt an der
Zahl statt an der Schwelle.

## [Web 9.9.0] — 2026-08-27

**O9b: die NutzerInnen-Liste, ausgelegt auf mehrere hundert Konten.** Keine
Migration.

Vorher war das eine ungefilterte Tabelle über **alle** Konten mit vier Spalten,
darunter ein Anlegen-Formular und je Zeile ein Löschknopf. Bei dreißig Konten
geht das; bei dreihundert ist es eine Seite, die man durchscrollt, um eine Zeile
zu suchen.

Jetzt: vier **Statuskacheln** (Konten, Admins, Sicherung überfällig, nie
gesichert — jede ein Weg in die Liste, die sie meint), eine **Suche** nach Name
oder Adresse, fünf **Filterplaketten mit Zahl**, sechs **sortierbare Spalten**,
**fünfzig Konten je Seite** mit Seitenwechsel, Auswahlkästchen und eine klebende
**Sammelleiste**, deren Auswahl über Seiten hinweg gilt. Das Anlegen ist ein
Dialog hinter „+ Anlegen" im Kartenkopf; das Löschen eines Kontos steht nur noch
auf der Kontoseite, wo die Entscheidung über die Sicherungen dazugehört.

**Die Kacheln zählen den ganzen Bestand, die Filterzahlen die laufende Suche.**
Das ist Absicht und keine Ungenauigkeit: Die Kacheln sagen, wie es um die
Installation steht; die Zahl an einer Filterplakette beantwortet „was bringt mir
dieser Filter jetzt?".

**Wo die Arbeit liegt.** Der Sicherungsstand eines Kontos steht nicht in der
Datenbank, sondern im Dateisystem — daran hängen zwei Kacheln, zwei Filter und
eine Spalte. Ihn je Zeile zu holen wären bei 300 Konten 300
Verzeichnisdurchläufe, genau der Fehler, den die alte Sicherungsseite macht.
Stattdessen ein einziger Durchlauf der Ablagewurzel plus je Ordner eine kleine
JSON-Datei; wer nie gesichert wurde, hat gar keinen Ordner und kostet nichts.
Gemessen an 304 Konten: 3,2 ms für die Ablage, 3,3 ms für die Abfrage, 3,2 ms
fürs Werten, 103 ms für den ganzen Seitenaufruf.

Der Preis dieser Abkürzung steht im Code: Die Angabe stammt aus `konto.json`,
nicht aus den Paketdateien. Wer ein Paket von Hand aus einem Ordner entfernt,
ohne die Anwendung zu benutzen, sieht in der **Liste** einen Stand, den es nicht
mehr gibt — die **Kontoseite** zeigt dann das Richtige, weil sie die Dateien
zählt. Eine Liste, die bei jedem Aufruf hunderte Verzeichnisse durchgeht, um
einen Fall abzudecken, den die Anwendung selbst nie herstellt, wäre der
schlechtere Tausch.

Gesucht, gefiltert und sortiert wird im Speicher, nicht in SQL. Nicht aus
Bequemlichkeit: Zwei der fünf Filter und eine der sechs Sortierungen kennen kein
SQL, weil ihre Angabe im Dateisystem liegt. Eine halbe Filterung in SQL und eine
halbe in PHP wären zwei Wege für dieselbe Frage — und der zweite hätte die
falschen Zahlen. Was der Browser bekommt, sind in jedem Fall höchstens fünfzig
Zeilen.

**Dabei gemessen und behoben:** Das Erinnerungsintervall wurde je Zeile aus der
Datenbank geholt. Bei 304 Konten waren das 304 Abfragen und 27,7 ms für eine
Rechnung, die aus einer Subtraktion besteht; mit einem Zwischenspeicher je
Anfrage sind es 3,2 ms.

**Umlaute sortieren jetzt richtig.** Kleingeschrieben wird aus Ö ein ö, und ö
liegt in der Byte-Reihenfolge hinter z: „Ömer" stand an erster Stelle der
**absteigenden** Sortierung, also hinter allem anderen. Wer einen Namen mit
Umlaut sucht, fand ihn am Ende der Liste. Der Sortierschlüssel schreibt Umlaute
jetzt nach deutscher Lesart aus (ae/oe/ue/ss) — dieselbe Regel wie beim
Dateinamen eines Exports — und führt übrige Akzente auf den Grundbuchstaben
zurück. Bewusst ohne `Collator`: Die intl-Erweiterung ist auf geteiltem Webspace
nicht verlässlich da, und eine Sortierung, die je nach Installation anders
ausfällt, ist schlimmer als eine, die überall gleich näherungsweise ist.

**Ein Fund aus der Prüfung dieses Pakets (F-P3-AL).** Die Nachladeknöpfe der
gemeinsamen Einsatztabelle („Weitere 200 anzeigen", „Alle n anzeigen") trugen
noch `btn-plain` — eine Klasse, für die es im neuen Stylesheet keine Regel mehr
gibt. Sie standen seit dem Redesign in der Grundform des Browsers. Aufgefallen
ist es niemandem, weil sie erst ab 200 Treffern erscheinen und der
Referenzbestand 82 Einsätze hat.

**Zwei Klassenkollisionen, beide vor dem Festschreiben abgefangen (F-P3-AM)** —
und jede von einem anderen Prüfmittel. Die neue Filterreihe brauchte Namen, und
zwei der naheliegenden waren vergeben:

`.filterzahl` gehört seit O6 den Zählern der Filtergruppen auf der Suchseite.
Die neue Regel steht weiter unten im Stylesheet und hätte bei gleicher
Spezifität gewonnen — aus den blauen Zählern wären graue geworden. Gefunden
durch **Lesen**, bei der Bestandsaufnahme vor dem Bauen.

`.filterknopf` gehört seit O6 dem Knopf, der auf der Suchseite die
Filterschublade öffnet — und der ist **48 px** hoch, weil er neben dem
48-px-Suchfeld steht; es ist die einzige benannte Ausnahme von der 44-px-Regel.
Die neue Regel hätte ihn auf 44 px gesetzt. Gefunden vom **Bilderlauf**:
„15-suche · Filter 0 · 44 px (soll 48)", achtmal, in jeder Breite — und zwar
nur, weil der Lauf die Suchseite mitfotografierte, obwohl dieses Paket sie gar
nicht anfasst.

Die Lehre daraus ist nicht „vorher greppen", sondern: nach jedem Paket auch die
Seiten mitmessen, die es **nicht** anfasst. Die Vollständigkeitsprüfung hätte
beides nicht gemeldet — sie zählt Klassen **ohne** Regel, nicht zwei Regeln für
**eine** Klasse. Beide heißen jetzt nach ihrem Ort: `.listenfilter` und
`.listenfilter-zahl`.

**Neu als Prüfmittel:** `tools/pruefkonten/` legt 300 Konten mit gemischten
Sicherungsständen an und entfernt sie wieder — reproduzierbar, weil der Zufall
einen festen Startwert hat. Ohne so einen Bestand lässt sich weder ein
Seitenwechsel noch eine Auswahl über Seiten hinweg prüfen.

**Neun Funde aus der gegnerischen Prüfung**, alle behoben. Die beiden, die am
weitesten reichten, liegen in der Sicherungsbibliothek und betreffen auch die
Kontoseite aus O9a:

Die Verdrängung **schonte eine eingelöste Freigabe dauerhaft**. Die Ausnahme
war damit begründet, dass die NutzerIn das Paket in ihrem Backup-Bereich
angeboten bekommt — nach dem Einlösen stimmt das nicht mehr. Die eingestellte
Aufbewahrung wurde damit still überschritten, und zwar für immer.

Ein **fehlgeschlagener Sicherungslauf ließ einen leeren Ordner zurück**, weil
der Ordner vor dem Datenpaket angelegt wurde. Die Folge war eine widersprüchliche
Auskunft: Die Liste meldete für dieses Konto „Stand unbekannt", die Kontoseite
„nie gesichert" — zwei Seiten, zwei Antworten aus demselben Fehlschlag. Der
Ordner entsteht jetzt erst, wenn es etwas hineinzulegen gibt, und wird bei einem
Fehlschlag wieder entfernt.

Dazu in der Liste selbst: Die **Statuskacheln behielten beim Klick die Suche**
und führten dann auf weniger Konten, als sie nannten. Jedes Konto hatte **zwei
Auswahlkästchen** im Markup (Tabelle und Kachelzeile), von denen nur das
angeklickte nachgeführt wurde — nach einem Wechsel der Fensterbreite sah ein
Kästchen leer aus, obwohl das Konto ausgewählt war. Die **Auswahl im
`sessionStorage` überlebte den Wechsel der angemeldeten Person**. `?q[]=x`
erzeugte eine PHP-Warnung mitten in der Seite. Ein **kaputtes `konto.json`**
(eine Zahl statt einer Zeichenkette) hätte unter `strict_types` die ganze Liste
lahmgelegt, und ein unbrauchbarer Zeitwert hätte das betroffene Konto mit
zwanzigtausend Tagen als dringendsten Fall nach oben sortiert. Und das neue
Prüfwerkzeug hatte selbst zwei Fehler: sein eigener dokumentierter Aufruf brach
ab, und ein abgebrochener Lauf hinterließ Sicherungsordner, die `entfernen` nie
wiederfand.

Geprüft: 35 + 19 + 13 Bedienproben im Browser gegen 304 Konten, alle
erwartungsgemäß, keine Konsolenmeldung; 13 Bibliotheksproben zu den
Grenzfällen der Sicherungsbibliothek; 7 Proben zum Zeitbudget der
Sammelaktion. Vollständigkeitsprüfung, Wortliste, Kontraste und der volle
Bilderlauf (240 Bilder, 0/0/0).

## [Web 9.8.0] — 2026-08-27

**O9a: die Kontoseite wird zur Drehscheibe.** O9 (Administration) ist mit fünf
Seiten, drei Funktionsänderungen und einer Migration zu groß für einen Zug und
zerfällt in drei Teile: Kontoseite (O9a), NutzerInnen-Liste (O9b), Regeln,
Stammdaten, Demo und Wartung (O9c).

**Diese Fassung braucht eine Migration.** `2026_08_28_last_login` legt
`users.last_login` an. Ohne sie fehlt auf Kontoseite und Liste die Angabe
„zuletzt angemeldet"; die Anmeldung selbst läuft weiter, `login.php` fängt den
Fall. Nach dem Ausrollen also `update.php` aufrufen.

Diese Spalte stand nicht in der Migrationsliste des Konzepts, wird von E-P3-41
aber zweimal verlangt — in der Unterzeile der Kontoseite und als Spalte der
Liste. Eine Quelle dafür gab es bisher nicht: `devices.last_seen` ist der Stand
einer **Uhr**, nicht der einer Anmeldung. Der Bestand bekommt `NULL`, nicht
`NOW()`: Sonst sähe jedes Konto so aus, als hätte es sich am Tag der Migration
angemeldet — eine erfundene Angabe genau in der Spalte, die man liest, um
ungenutzte Konten zu finden. Angezeigt wird `NULL` als „—".

**Alles zu einem Konto liegt jetzt auf dessen Seite.** Vorher waren die
Kontodaten drei Formulare mit drei Speichern-Knöpfen (Rolle, E-Mail, Name) —
drei Absendevorgänge für eine Änderung, die man als eine denkt. Und die
**Sicherungen** eines Kontos standen woanders: auf `admin_sicherungen.php`, in
einer Tabelle über alle Konten, in der man seine Zeile suchen musste. Jetzt ein
Formular mit einem Speichern, dazu Karten für Geräte, Sicherungen, Abonnement
(reservierter Platz, kommt mit R33) und die Löschung als rote Gefahrenzone. Ab
1200 px zweispaltig, mobil untereinander.

Das ist auch eine Antwort auf die Menge. Die alte Übersicht las für **jedes**
Konto ein Verzeichnis und eine Begleitdatei, um eine einzige Zeile zu zeigen —
Arbeit, die mit der Zahl der Konten wächst, obwohl man immer nur ein Konto
ansieht. Die Kontoseite liest genau einen Ordner.

**Drei Handlungen brauchen mehr als eine Rückfrage** — eine Sicherung
einspielen, freigeben, löschen. Sie stehen jetzt in Dialogen, die im Markup
stehen und ihre Werte vom öffnenden Knopf bekommen; ein Dialog für alle Zeilen
statt eines je Zeile. Geprüft wird weiterhin serverseitig: Die abgetippte
E-Mail-Adresse muss stimmen, und ein Browser-Dialog ließe sich umgehen. Das
Einspielen zielt dabei auf **dieses** Konto — ein Auswahlfeld mit allen Konten
stünde für einen Fall, den es hier nicht gibt; wer eine Sicherung in ein fremdes
Konto bringen will, gibt sie frei.

**Die Aufbewahrung je Konto ist einstellbar geworden.** Bisher waren es fest
drei Pakete. Der Wert kommt jetzt aus den Regeln (die Einstellung dazu entsteht
in O9c; die Vorgabe bleibt drei, damit ein Bestand ohne Einstellung sich verhält
wie vorher). Zwei Pakete sind von der Verdrängung ausgenommen: das **jüngste** —
sonst räumte eine Aufbewahrung von 0 beim Sichern alles weg, und eine Sicherung,
die beim Sichern alles entfernt, ist das Gegenteil der Funktion — und ein
**freigegebenes**, weil die NutzerIn es im eigenen Backup-Bereich angeboten
bekommt und der Weg dorthin sonst ins Leere liefe.

**„Passwort zurücksetzen"** ist neu im Aktionsmenü der Kontoseite. Es setzt kein
Passwort — das kann diese Seite nicht, und das ist der Punkt: Die Daten sind mit
dem Passwort der Person Ende-zu-Ende-verschlüsselt. Verschickt wird derselbe
Link wie bei „Passwort vergessen", mit derselben Regel (der neue Token entwertet
alle offenen). Kommt die Mail nicht weg, steht der Link auf der Seite: Ein
gültiger Token in der Datenbank, von dem niemand weiß, ist die schlechteste
aller Lagen — dasselbe Muster wie beim Anlegen eines Kontos.

Bewusst gekürzt: Die Umfangszeile einer Sicherung nannte den Papierkorb bisher
nach Art aufgeteilt („davon im Papierkorb: 5 Einsätze, 1 Diensttag, 5
Ruhezeiten"). In der Zeile einer Karte, halb so breit wie die alte Tabelle,
waren das drei Zeilen Umbruch für eine Frage, die eine Zahl beantwortet: wie
viel davon ist gelöschter Bestand. Jetzt „davon 11 im Papierkorb"; das Paket
selbst führt die Zahlen weiter je Art.

Geprüft: 29 Bedienproben im Browser (Speichern, Dublette, Setz-Link,
Aufbewahrung, Einspielen mit falscher und richtiger Adresse, Freigabe und
Widerruf, Löschen des letzten Pakets, eigenes Konto, Kontolöschung) — alle
erwartungsgemäß, keine Konsolenmeldung. 14 Bibliotheksproben zur Verdrängung
und zum Kontostand. Vollständigkeitsprüfung, Wortliste (0 Treffer außerhalb der
Ausnahmen), Kontraste (21 Paare, 0 verfehlt) und der volle Bilderlauf.

## [Web 9.7.2] — 2026-08-27

**O8c: Backup und Import — und die Meldungen bekommen ihren Ton.** Damit ist
O8 vollständig. Beide Seiten sind lange Wege mit vielen Zwischenmeldungen, und
beide meldeten sie bis hierher in **einer grauen Zeile**: „Daten werden
geladen…", „Das ist nicht dein Kontopasswort", „Fertig: 82 Einsätze" — alles in
derselben Schrift, derselben Farbe, an derselben Stelle. Ein misslungener
Export sah aus wie ein Zwischenstand.

Jetzt tragen die Meldungen ihren Ton (E-P3-16): **rot** für einen Fehlschlag,
**blau mit Haken** für ein Ergebnis, **schlicht** für den laufenden
Fortschritt. Der Fortschrittstext bekommt bewusst kein Symbol — er ist kein
Ergebnis, und ein Haken daneben behauptete eines.

Ein Sonderfall bekam dabei eine eigene Antwort: Ein **Export mit unlesbaren
Blöcken ist kein reiner Erfolg**. Die Datei ist vollständig, aber ein Teil
ihrer Angaben lässt sich nur in diesem Konto wieder öffnen. Das meldet sich
jetzt als Warnung statt mit einem Haken — vorher stand die Warnung als
Nachsatz in derselben Erfolgsmeldung.

**Der Import zeigt seine drei Schritte als drei Karten** mit der Zahl im Kopf;
Schritt 2 und 3 bleiben verborgen, bis der vorige getan ist. Die Zeilenwahl
(Alle Zeilen / Nur Probleme / Nur Dubletten) war eine Reihe von drei Knöpfen,
bei der keiner zeigte, welcher gerade galt — jetzt eine Segmentwahl: drei
Zustände, von denen genau einer gilt, und die Pfeiltastenbedienung bringt der
Browser mit. Die Haken des Exports (GPX-Tracks, personenbezogene Angaben,
Passwortschutz) sind Schalter geworden (E-P3-28).

Der **Backup-Reiter** ist in drei Karten gefasst: erstellen, einspielen und
— nur wenn eine vorliegt — die von der Administration freigegebene Sicherung.
Die Warnung, was in der Datei steht, steht weiterhin **vor** der Passwortwahl,
jetzt als Meldungs-Baustein: Wer ein Passwort wählt, muss wissen, was er damit
schützt.

Belegt am Referenzarchiv: Datei einlesen → Schritt 2 und 3 erscheinen, Bilanz
„13 Diensttage, 82 Einsätze, 1 Hinweise, 0 Fehler, 82 Dubletten", 96
Tabellenzeilen, „Import ausführen" bleibt gesperrt (alles schon vorhanden —
richtig so). Die Filterwahl greift: 15 Zeilen bei „Nur Probleme", 96 bei
„Alle". 0 Konsolenfehler, 0 doppelte Element-Kennungen, kein waagerechter
Überlauf. Screenshots 240 Bilder — 0/0/0.

Keine Migration.

## [Web 9.7.1] — 2026-08-27

**O8b: Die übrigen Verwaltungslisten — und drei Fehler, von denen einer aus
O8a stammt.** Der Reiter „Rettungsmittel" führt je Standort fünf Listen
(Rettungsmittel, Besatzung, Zielkliniken, weitere Rettungsmittel, Bergwacht),
dazu kommt der Reiter „Geräte". Alle folgen jetzt dem Muster aus O8a: Karte
statt Tabelle, Zeilen mit Knöpfen am Schreibtisch und „⋯" auf dem Handy, das
Anlegen-Formular in derselben Karte. Ein Standort ist eine zugeklappte Karte;
die Listen darin sind Abschnitte, keine zweite Kartenebene — zwei Rahmen um
dieselbe Sache trennen, was zusammengehört.

**Das Muster stand fünfmal ausgeschrieben, und es war bereits
auseinandergelaufen:** Die Rettungsmittel trugen „★ Standard", die übrigen
nicht; die Löschrückfragen lauteten „Eintrag löschen?", „Zielklinik löschen?"
und „Bereitschaft löschen?" — ohne zu sagen, welcher. Zwei Schließungen
rendern es jetzt einmal, und jede Rückfrage nennt den Namen.

**Ein Fehler aus O8a, den erst dieser Umbau sichtbar machte:** Das
wiederhergestellte Lage-Feld trug dieselbe Kennung wie das Namensfeld
(`<praefix>addr`). `getElementById` findet das erste — also das Namensfeld;
das Lage-Feld hing an nichts und war Zierde. F-P3-AI war damit **nicht
behoben**, sondern nur bebildert. Die Kennung gehört jetzt dem Lage-Feld, der
Name hat eine eigene. Belegt: Der Vorschlag erscheint, die Übernahme setzt die
Koordinaten, und der Name bleibt unberührt.

**Zwei weitere Funde beim Prüfen:**

Der Lupen-Knopf nimmt dem Eingabefeld den Fokus. Der `blur`-Handler plant
150 ms später das Verstecken der Vorschlagsliste — damit ein Klick auf einen
Vorschlag noch durchkommt. Kommt die Antwort schneller als das, löscht dieser
Aufschub die eben gefüllte Liste wieder. Gemessen: bei sofortiger Antwort
stand sie nach 80 ms mit einem Eintrag da und nach 160 ms leer; bei 250 ms
Antwortzeit blieb sie. Gegen den echten Photon-Dienst verdeckt die Netzlatenz
das zuverlässig — hinter einem Zwischenspeicher oder im schnellen Netz nicht
(F-P3-AJ). Der Aufschub prüft jetzt, ob der Fokus inzwischen zurückgekehrt ist.

Und `ui_zeilenaktionen()` leitete die Kennung seines Aktionsblatts aus einem
**Hash über Titel und Aktionstexte** ab. Zwei Zeilen mit gleichem Namen und
gleichen Handlungen bekamen dieselbe Kennung — in einer Stammdatenliste der
Normalfall, nicht die Ausnahme (zwei Standorte mit einer gleichnamigen
Zielklinik). `data-blatt` öffnete dann beide oder keines. Jetzt eine laufende
Nummer. Dasselbe galt für die Feld-Kennung des Besatzungsformulars, das je
Rolle einmal steht.

Der **Kopplungscode** und die Zugangsdaten eines neuen Geräts stehen als
`.codeblock`: groß, in Festbreite, mit Sperrung. Sie werden von einem
Bildschirm auf eine Uhr abgetippt, und zwar unter Zeitdruck.

Screenshots 240 Bilder — 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe außerhalb des
Solls; **0 doppelte Element-Kennungen** auf allen umgebauten Reitern.
Wortliste 0/0/0, Kontraste 21/0. Sicherung und Import folgen als O8c.

## [Web 9.7.0] — 2026-08-27

> **Diese Fassung braucht eine Migration.** Nach dem Ausrollen muss eine
> Administratorin `update.php` aufrufen — `2026_08_27_logo_wahl` legt die
> Spalte `users.logo_wahl` an. Ohne sie scheitert **jede Anmeldung**, weil
> `login.php` die Spalte mitliest. Es ist die erste Schemaänderung dieser
> Phase.

**O8a: Profil, Logo-Wahl und die Verwaltungslisten am Muster der Standorte.**
O8 hat sich beim Bauen als zu groß für ein Paket erwiesen — fünf Reiter, der
Import und eine Migration. Es ist deshalb geteilt: Dieser Teil bringt Profil,
Logo-Wahl, Standorte und den Passwortstärke-Balken; Rettungsmittel, Geräte,
Sicherung und Import folgen als O8b.

**Die Logo-Wahl** (E-P3-20) steht im Profil und kennt vier Werte: Standard der
Installation, Hubschrauber (RTH), Fahrzeug (NEF), wechselnd. Aufgelöst wird sie
**einmal bei der Anmeldung**; in der Sitzung steht danach das Ergebnis, nicht
die Wahl. Der Unterschied ist die ganze Sache: Würde bei jedem Seitenaufruf
gewürfelt, spränge das Logo beim Blättern von Seite zu Seite — „wechselnd"
heißt je Anmeldung, nicht je Klick. Wer die Wahl im Profil ändert, muss sich
trotzdem nicht neu anmelden; dieselbe Auflösung läuft nach dem Speichern.

Kopfleiste und Browser-Symbol fragen **dieselbe Stelle** (`logo_stamm()` in
`session_lib.php`) und können deshalb nicht auseinanderlaufen — ein Konto mit
dem Fahrzeug in der Kopfleiste und dem Hubschrauber im Tab wäre ein
Widerspruch, den niemand erklären könnte. Die Anmeldeseite zeigt immer den
Standard: Dort ist noch niemand angemeldet, und die Wahl hängt am Konto. Als
Vorgabe steht **Leerstring** in der Spalte, nicht „Hubschrauber" — wer nie
gewählt hat, folgt dem Standard der Installation, und der kann sich ändern
(die Wahl dafür kommt in O9). Stünde dort ein fester Wert, hätten alle
bestehenden Konten eine ausdrückliche Wahl getroffen, die sie nie getroffen
haben.

**Die Verwaltungslisten** (E-P3-35) sind am Muster der Standorte umgebaut: der
Erklärtext auf drei Zeilen statt zweier Absätze, eine Karte mit Zeilen statt
einer Tabelle, das Anlegen-Formular **in** derselben Karte darunter, die
vordefinierten Einträge als zweite, zugeklappte Karte mit „n · m ausgewählt".
Die Zeilenaktionen sind am Schreibtisch Knöpfe und mobil ein „⋯", das ein
Blatt öffnet (neuer Baustein `ui_zeilenaktionen`). Die POST-Formulare stehen
dabei **einmal** im Markup: Löschen und „Als Vorbelegung" sind Formulare mit
Token, und sie zweimal auszugeben — einmal für den Knopf, einmal für das
Blatt — wäre dieselbe Handlung an zwei Stellen, von denen die nächste
Änderung nur eine erreicht. Stattdessen trägt der Knopf ein `form`-Attribut;
HTML erlaubt es ihm, ein Formular abzusenden, in dem er gar nicht steht.

**Die Passwortstärke** ist ein Balken aus vier Segmenten geworden (E-P3-16,
Mockup 11). Vorher war es eine Textzeile in fünf Farben, darunter Grün und
Gelb — zwei Töne, die es in der Marke nicht gibt. Der Balken sagt dasselbe
ohne fremde Farbe: Wie viele Segmente gefüllt sind, ist die Auskunft; rot,
orange oder dunkelblau verstärken sie nur.

**Beim Prüfen gefunden:** Seit Web 9.4.0 (O5) gab es **kein Eingabefeld für
die Lage** mehr. O5 hat das zweite Suchfeld am Ortsfeld ausgebaut — der
Lupen-Knopf am Namensfeld trat an seine Stelle —, und dabei ist die
Nur-Lage-Fassung von `ui_ortsfeld()` leer zurückgeblieben: Sie gab nur noch
Vorschlagsliste, Zustandszeile und die versteckten Koordinatenfelder aus. Die
Lage eines Standorts oder einer Zielklinik ließ sich seither **nicht mehr
eingeben, nur noch behalten** (F-P3-AI). Betroffen waren vier Stellen in
`einstellungen.php` und `admin_stammdaten.php`. Die Fassung hat jetzt wieder
ein Suchfeld mit Lupe; ein Treffer setzt weiterhin nur die Koordinaten, nie
den Namen.

Screenshots 240 Bilder — 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe außerhalb des
Solls. Logo-Wahl belegt: Standard und Hubschrauber liefern dasselbe Logo,
Fahrzeug wechselt Kopfleiste **und** Favicon, „wechselnd" bleibt über fünf
Seiten einer Sitzung stabil und ergab über zwanzig Anmeldungen 11 zu 9.

## [Web 9.6.0] — 2026-08-27

**O7: Die Zeitraumübersicht nach den Mockups 29/30/31 — und „Gemischt" zeigt
vier Kennzahlen statt acht.** Das ist die Funktionsänderung dieses Pakets, und
sie streicht etwas: Bisher teilte die gemischte Ansicht den Bodensatz mit acht
Kacheln, also auch Einsatzkilometer, längste Strecke, längste Dauer und
Fehleinsätze. Über beide Arten hinweg sind das Äpfel und Birnen — eine
Flugstrecke von 61 km und eine Fahrstrecke von 12 km stehen für ganz
verschiedene Einsätze, und ihre Summe beantwortet keine Frage, die jemand
stellt. Was über beide Arten trägt, sind Anzahl, Diensttage, ihr Verhältnis
und die Sekundärtransporte. Die übrigen Zahlen stehen unverändert in den
beiden Artenansichten: **Luft behält zehn Kacheln, Boden acht**, und ihre
Werte sind belegt dieselben geblieben (zehn Proben über fünf Zeiträume, 88
Kachelwerte verglichen, keine unerklärte Abweichung).

Die Tableiste nach Art ist eine **Segmentwahl in der Titelzeile** geworden —
„Gemischt / Luft / Boden" statt „Gemischt / Luftrettung / Bodengebundener
Rettungsdienst". Die lange Fassung war bei 360 px breiter als der Bildschirm.
Aus `<button role="tab">` sind Radios geworden; der Wechsel mit den
Pfeiltasten kommt damit vom Browser, und der eigene Tastatur-Handler ist
entfallen. Mobil steht die Wahl vollbreit unter dem Titel.

Unter 720 px sind je Satz **vier Kacheln sichtbar**, der Rest hinter
„Weitere Statistik (n)". Welche vier, sagt die Kachel selbst und nicht ihre
Position: Für die Luft sind es Einsätze, Flugtage, Flugkilometer und
Winden-Cycles, für den Boden Einsätze, Diensttage, Einsatzkilometer und die
längste Einsatzdauer. Fällt eine davon am Bestand weg — die Winden-Cycles
ohne einen einzigen Windeneinsatz —, rückt die nächste nach; vier Kacheln
füllen zwei Reihen zu zweit, drei ließen eine halbe Reihe leer. Diesen Fall
bedenkt das Konzept nicht, er ist hier entschieden worden.

Extremwerte tragen jetzt den **Tag in der Beschriftung** („Längste
Flugstrecke · 14.08.") — das beantwortet „welcher Einsatz war das?" schon vor
dem Klick. Die Hervorhebung der Trägerzeile ist **hell orange statt rot**:
Rot heißt in dieser Oberfläche „Aufmerksamkeit" (Fehler, Löschen), und ein
Höchstwert ist kein Fehler. Zwei Hexwerte, die dafür fest im Skript standen,
kommen jetzt aus den Token.

Auf der Karte steht das **Standort-Haus** (E-P3-40). `api/range.php` liefert
dafür neu die Standorte der Diensttage des Zeitraums, nach Koordinate
entdupliziert — ein Monat mit fünf Diensten derselben Wache hat einen
Standort, nicht fünf übereinander. Sie sind Klartext wie Art und
Rettungsmittel und brauchen deshalb keinen Inhaltsschlüssel: Die Karte zeigt
das Haus auch im gesperrten Zustand, nur die Einsatzorte bleiben dann aus.
In der Leiste ist der angezeigte Monat bzw. das Jahr markiert — bisher war
dort auf dieser Seite nichts aktiv, weil der aktive Eintrag stets ein
Diensttag war und den gibt es hier nicht.

**Beim Prüfen gefunden:** Das Screenshot-Werkzeug rief `zeitraum.php` **ohne**
`?y=` auf. Die Seite leitet dann auf die Startseite um — die
Zeitraumübersicht war damit seit O1 in keinem einzigen der bislang 232
Bilder, und der Kontaktbogen „14-zeitraum" zeigte in Wahrheit die
Tagesübersicht (F-P3-AH). Im Werkzeug behoben; sie steht jetzt mit zwei
Seiten darin, Jahr und Monat, weil nur die Monatsansicht Rückweg und
Monatsmarkierung zeigt.

Keine Migration; Kennzahlen, Endpunkte und Feldkatalog unverändert.

## [Web 9.5.0] — 2026-08-27

**O6: Die Suche nach den Mockups 27/28 — und ein Filter, der seit O2 nicht
mehr wirkte.** Die Suchseite hatte als einzige Seite eine **eigene**
Filterspalte (`layout-suche`, `filterspalte`). Das war schon am Schreibtisch
eine Sonderlocke; auf dem Handy war es ein Fehler: Der Schubladenmechanismus
aus O2 hing an der gemeinsamen `.leiste`, also stand hier die volle
Filterspalte als anderthalb Bildschirme **vor** dem Ergebnis. Die Filter
sind deshalb in die gemeinsame Leiste gezogen — dieselbe Schublade, derselbe
Filterknopf, dieselbe Kopfzeile wie überall.

Dabei fiel auf, dass **kein Filter der Seitenleiste mehr wirkte**: Der
Zuhörer horchte auf `.filterspalte input, .filterspalte select` — die Klasse
ist mit O2 verschwunden, der Selektor traf seither nichts. Gemessen an
„Datum von 01.12.2026": 82 von 82 Einsätzen blieben stehen. Der Zuhörer
hängt jetzt an der Leiste selbst und filtert nach dem Ereignisziel, nicht
nach einer Klasse am Behälter (F-P3-AG).

Der **Zuschnitt** der fünf Blöcke aus Web 7.0.0 bleibt (Einsatz, PatientIn,
Transport, Beteiligte, Bergrettung) — er schneidet nach dem Gegenstand, und
daran war nichts falsch. Sie sind jetzt dieselben **Akkordeons** wie die
Diensttage in der Leiste, jedes mit einer Plakette, die zählt, wie viele
Filter darin gesetzt sind: So ist ein vergessener Filter in einer
zugeklappten Gruppe sichtbar, ohne sie zu öffnen. Über der Trefferliste steht jeder gesetzte Filter noch einmal
als **Plakette mit ✕**, einzeln abwählbar; der Fuß der mobilen Schublade
trägt „Filter zurücksetzen" und „**n Treffer zeigen**" mit der Zahl aus der
laufenden Suche, damit man vor dem Schließen weiß, worauf man hinausläuft.
Ja/Nein/Egal-Filter sind Segmente statt Auswahllisten.

Das Freitextfeld ist 48 px hoch, mit Lupe und Löschkreuz; die Erklärung der
Suchsyntax steht nicht mehr dauerhaft darunter, sondern hinter
„Syntaxhilfe" — sie ist für den zweiten Besuch da, nicht für jeden.
**Trefferwörter werden hervorgehoben.** Das betrifft mit Einsatzort und
Diagnose ausgerechnet die beiden verschlüsselten Textspalten, deshalb
geschieht es erst **nach** dem Maskieren im Browser (`suchtext.js`:
`woerter()` liest die positiven Literale aus der Anfrage, `hervor()` setzt
`<mark>` in den bereits maskierten Text). Durchsucht wird weiterhin mehr,
als die Liste zeigt — Notizen, Besatzung, Rettungsmittel; dort ist nichts
hervorzuheben, weil nichts davon in der Liste steht. Die Suchlogik ist
unberührt: Verneinte Begriffe und Operatoren werden nicht hervorgehoben,
weil sie nichts bezeichnen, was im Text steht.

Unter 720 px zeigt die Suche **Kacheln** statt Tabelle (derselbe Erzeuger
wie auf der Startseite, `missiontable.js`), mit Artzeichen und Datum in der
Kopfzeile und einzeiliger Diagnose. Am Desktop bekommt die Tabelle eine
**Farbstreifen-Spalte**: die Spurfarbe des Einsatzes an seinem Diensttag —
dieselbe Farbe wie auf der Karte des Tages, nicht eine Nummer nach
Listenposition.

Bewusst anders als das Mockup: Die Bestandszahl nennt „n von m" nur, wenn
tatsächlich gefiltert ist (sonst genügt „m Einsätze"), und die Streckensumme
rundet auf ganze Kilometer.

Keine Migration; Suchlogik, Endpunkte und Feldkatalog unverändert. Acht
Proben (fünf Suchbegriffe, drei Filterkombinationen) gegen den Stand vor P3
liefern dieselben Treffer: 8 von 8 identisch, 143 Treffer verglichen.

## [Web 9.4.0] — 2026-08-27

**O5: Das Einsatzformular nach den Mockups 22/23/25 — mit zwei
Funktionsänderungen.** Die Rahmengruppen von Web 7.0.0 sind **Karten**
geworden, ab 1200 px in zwei Spalten (links PatientIn, Einsatz, Transport;
rechts der Rest). Der Einsatzort steht jetzt bei den übrigen verschlüsselten
Feldern in der Karte **PatientIn** — er gehörte immer zu ihnen, nur das
Formular behauptete etwas anderes. Ja/Nein-Felder sind **Schalter**
(derselbe Baustein wie überall seit O2); die Detailfelder eines
eingeschalteten Schalters rücken hinter einer orangen Linie ein. Die
zugeklappten Karten „Abweichende Besatzung" („vom Diensttag") und
„Reanimation" („keine") öffnen sich von selbst, wenn etwas gespeichert ist —
ein gesetzter Wert hinter zugeklapptem Deckel wäre verborgene Wahrheit.

Die erste Funktionsänderung: **Die Phasenzeilen sortieren sich sofort**,
sobald ein Zeitfeld verlassen wird — mit derselben Mitternachtsregel, die
beim Speichern gilt (Zeiten vor dem Dienstbeginn gehören zum Folgetag). Der
Hinweistext „in chronologischer Reihenfolge eintragen" entfällt: Was sich
selbst ordnet, muss keine Reihenfolge verlangen. Der Kartenkopf zählt mit
(„8 von 9"). Entfernen-Knöpfe sind rote 44-px-Symbolknöpfe; das Entfernen
einer Zeile meldet sich beim Dirty-Tracking, obwohl kein Feld ein Ereignis
feuert.

Die zweite: **das Ortsfeld.** Der Lupen-Knopf ersetzt das zweite Suchfeld
(„Lokalisation …") — gesucht wird mit dem, was im Feld steht, und bei
getrennter Suche (Transportziel) übernimmt ein Treffer weiterhin nur die
Koordinaten, nie den Namen. Der Pin-Knopf öffnet ein Blatt mit „**Meine
Position übernehmen**" (Geolocation) und „**Auf der Karte wählen**" — ein
Leaflet-Dialog mit Fadenkreuz in der Mitte: Karte verschieben, „Übernehmen".
Zur Koordinate holt die Photon-Umkehrsuche eine Adresse; sie füllt das Feld
nur, wenn es leer ist, und die Anfrage trägt ausschließlich die Koordinate
(neues `assets/ortswahl.js`). Gespeichert wird über die **Speichern-Leiste**
(E-P3-29), die mit der ersten Änderung am unteren Rand erscheint; der
Abbrechen-Link entfällt — der Rückweg oben genügt, und die
Verlassen-Rückfrage schützt ungespeicherte Eingaben auf jedem Weg hinaus.

Speicherlogik und Felder sind unverändert: Der Rundlauf über fünf Einsätze
(öffnen, unverändert speichern, vergleichen — einschließlich entschlüsselter
Angaben) ergab null Abweichungen, der Sicherungs-Kreislauf 286 739
Einzelvergleiche ohne unerklärte Abweichung. Beim CSV-Kreislauf fand sich
ein Werkzeugfehler: Ein POST an `einstellungen.php` **ohne `?t`** versandet
seit der Übersichts-Weiche aus O2 stillschweigend in der Übersichtsseite —
die Browser-Formulare tragen das `t`, das Einspielwerkzeug trug es nicht
(F-P3-AF, im Werkzeug behoben).

Keine Migration; Endpunkte und Feldkatalog unverändert.

## [Web 9.3.0] — 2026-08-27

**O4: Die Einsatzansicht nach den Mockups 19–21 und 26.** Die eine lange
Feldliste hatte ihre eigene Geschichte — erst Feldkatalog-Reihenfolge, seit
Web 7.0.0 die RANG-Ordnung —, aber sie blieb eine Liste, in der Transport
und Diagnose gleich aussahen. Jetzt sind es **vier Karten** (Einsatz,
PatientIn, Transport, Reanimation, dazu die Besatzung), und die RANG-Ordnung
sortiert innerhalb jeder Karte weiter: Ein neues Katalogfeld erscheint ohne
Änderung an der Seite am Ende der Einsatz-Karte. Winde, Bergwacht, Sekundär
und Fehleinsatz sind **Plaketten** am Fuß der Einsatz-Karte („Winde ·
2 Cycles, 1 mit PatientIn"); Höhe, Luftlinie und Strecke stehen klein unter
dem Einsatzort statt als eigene Zeilen. Der Kopf: Rückweg „‹ Sonntag,
27.12.2026", „Bearbeiten" als Primärknopf, Verschieben und Löschen im
Aktionsblatt — das alte `<details>`-Menü samt `aktionsmenu.js` ist damit
komplett ausgebaut. Der Zustand der geschützten Angaben steht als **eine
Meldung** über den Karten (gesperrt mit Entsperren-Knopf, entsperrt,
unlesbar); die neun Schloss-Emojis an den Zeilen sind fort, und mit ihnen
der letzte Emoji-Bestand des Markups.

Auf der Karte der Marker-Satz aus O3 statt des wörtlich doppelten SVG-Pfads:
Haus- und Klinik-Schild mit Namen, oranger Einsatzort-Kreis,
Richtungspfeile — und **Start und Ende der Aufzeichnung als blauer bzw.
roter Ring**, am Schild des Ortes, an dem die Spur beginnt oder endet,
sonst als eigener Ringpunkt (Mockup 26). Die Phasenliste zeigt je Zeile den
**Minutenabstand** zur vorigen Phase und im Kopf die Gesamtdauer; die
angetippte Phase färbt ihr **Teilstück der Spur** blau. Dafür liefert
`api/mission.php` je Phase den nächstliegenden Trackpunkt nach
**Zeitstempel** (`track_idx`) — die Phasen der Uhr tragen nur bei GPS-Fix
eigene Koordinaten, die Zeit tragen sie immer. Ebenfalls neu in der
Antwort: `base_lat/lon` des Tages (Klartext wie der Name), damit das
Haus-Schild auch hier stehen kann. Und `fitBounds` bekommt sein Padding nun
auch auf dieser Seite mit den richtigen Achsen (F-P3-Z war an zwei weiteren
Stellen).

Zwei Baustein-Funde nebenbei: Die Utility `nur-ab-720` stellte mit
`display:block` wieder her und machte den Titelzusatz „· 07:13 Uhr" zur
eigenen Zeile — jetzt `revert`, das die Grundform des Elements
wiederherstellt. Und die Unterzeile der Titelzeile ist aus dem Flex-Block
neben die Hauptzeile gerückt: Ihre Breite ließ sonst die Knöpfe unter einen
kurzen Titel brechen, obwohl daneben Platz war.

Keine Migration; Feldkatalog unverändert, `api/mission.php` nur erweitert.

## [Web 9.2.0] — 2026-08-27

**O3: Die Startseite nach den Mockups 02–05 und 10.** Der Befund zur alten
Tagesübersicht war der Ausgangspunkt des ganzen Redesigns: Bei 360 px
bekamen Einsatzort und Diagnose — die beiden Angaben, um die es geht — null
Pixel Breite und verschwanden ohne Hinweis, während sieben Zahlenspalten
stehen blieben. Deshalb gibt es unter 720 px jetzt **keine Tabelle mehr**,
sondern eine dreizeilige Kachel je Einsatz: Farbstreifen und Beginn, Ort
fett mit Kilometerzahl, Diagnose, darunter Dauer, Alter und die Plaketten
(Winde, Bergwacht, Sekundär, Fehleinsatz, „kein Ende"). Tabelle und Kachel
entstehen aus **demselben Zeilenbestand** — sortiert wird mobil über ein
Blatt mit denselben Spalten, und die Reihenfolge ist belegt dieselbe wie am
Desktop. Die Knopfreihe unter den Tagesdaten ist einem Aktionsblatt hinter
„···" gewichen (eine sichtbare Handlung je Karte, E-P3-25); die
Diensttag-Daten zeigen einen **Lesezustand** und klappen erst auf
„Bearbeiten" ins Formular.

Auf der Karte der neue Marker-Satz (E-P3-40): Standort als Haus-Schild,
Transportziel als Klinik-Schild, Einsatzort als oranger Kreis,
Dienstbeginn/-ende als Ringe, Richtungspfeile auf den Spuren. Die
Spurfarben sind **Token** (`--spur-1 … --spur-8`, `--spur-ruhe`) und werden
per `getComputedStyle` gelesen — die alte `COLORS`-Liste im Seitenskript
ist weg. Die Luftlinie bleibt gestrichelt, trägt aber die Farbe ihres
Einsatzes statt einheitlichem Grau: Bei acht Einsätzen am Tag wäre sonst
nicht erkennbar, welche Linie zu welchem gehört — die bewusste Abweichung
vom Konzepttext ist im Code begründet.

Zwei Funde nebenbei, beide älter als P3: `fitBounds` bekam sein Padding
seit jeher mit **vertauschten Achsen** (F-P3-Z) — bei einer Karte, die
breiter als hoch ist, fraß das fast die ganze Höhe, und die Tageskarte
blieb auf der Rückfall-Zoomstufe hängen, statt auf die Spuren zu zoomen.
Und das `hidden`-Attribut verlor gegen jede `display`-Angabe eines
Bausteins; ein globaler Wächter (`[hidden]{display:none !important}`)
ersetzt die vier verstreuten Einzelregeln — ohne ihn standen Lese- und
Formularzustand der Tagesdaten gleichzeitig auf der Seite. Die
Tabellenspalten richten sich jetzt nach Mockup 03 aus (Nr., Dauer, Alter,
km rechts; Haken zentriert; Ziffern in Tabellenbreite statt der alten
`mono`-Schreibmaschinenklasse), und sechzehn tote Klassen der alten
Startseite stehen mit Begründung auf der Streichliste.

Für die Prüfmittel: Der Prüf-Browser kommt in der Arbeitsumgebung nicht an
die OSM-Kachelserver (die Egress-Sperre setzt seinen TLS-Handschlag
zurück, mit wie ohne Proxy — per NetLog belegt); `aufnehmen.mjs` fängt
Kachelabrufe deshalb mit einer Playwright-Route ab und beantwortet sie aus
einem Node-Abruf mit Lager je URL. Nebeneffekt: deterministische Bilder,
und ohne Proxy läuft derselbe Weg unverändert direkt.

Keine Migration; Endpunkte und Feldkatalog unverändert.

## [Web 9.1.1] — 2026-08-26

**Die Fable-Kontrolle zu O1/O2 — neun Funde, alle behoben.** Nach dem Halt
am Ende von O2 wurde der Stand Mockup für Mockup gegen die Screenshots
gehalten und der Konzeptumfang gegen den Code. Anlass war der berechtigte
Einwand, das Ergebnis sehe nicht aus wie die Mockups. Die Antwort ist
zweigeteilt: Der größte Teil des Unterschieds ist **Inhalt der Pakete O3 bis
O11** — Titelzeilen, Karten, Kacheln und Kartenbild der Mockups 02/03 sind
noch nicht gebaut, und der Halt nach O2 zeigt absichtlich die neue Hülle um
alte Inhalte. Aber neun Punkte waren echte Mängel an O1/O2 (F-P3-Q bis
F-P3-Y im Konzept, Abschnitt 9.2). **Keine Migration.**

### Web — Was falsch war und jetzt stimmt

- **Die Winkel zeigten in die falsche Richtung.** Zugeklappt heißt in den
  Mockups „›", offen „⌄". Gebaut war die umgekehrte Konvention (offen = oben).
- **Der Weg in die Jahres- und Monatsübersicht war an zugeklappten Zeilen
  unsichtbar.** Der Balken-Link lag als Kind des `<details>` außerhalb des
  `<summary>` — und der Inhalt eines geschlossenen `<details>` wird nicht
  gerendert. Mockup 06 zeigt ihn an jeder Zeile. Er steht jetzt in der Zeile
  selbst; `daylist.js` fängt den Klick ab, damit er nicht zusätzlich auf- und
  zuklappt.
- **Der O2-Umfang „confirm.js und unlock.js auf `.dialog`" war schlicht
  vergessen.** Jede Rückfrage — auch das Abmelden — und der Entsperrdialog
  erschienen als unformatierte Kästen mit Klassen, für die es keine Regel
  mehr gab. Beide benutzen jetzt den Dialog-Baustein, ebenso die
  Archiv-Passwortabfrage des Imports (deren `style="width:100%"` dabei
  entfiel). Im Browser belegt: Bestätigungs- und Entsperrdialog erscheinen
  als Karte mit Fußzeile aus 44-px-Knöpfen.
- **Alt-Meldungen sahen aus wie Fließtext.** Die Meldung „2 neue Geräte
  wurden mit deinem Konto verbunden" und der Sperrhinweis der Suche standen
  ohne jede Auszeichnung da — eine Fehlermeldung, die aussieht wie Text,
  warnt niemanden. Die Übergangsschicht führt jetzt **eine eng begründete
  Klassen-Ausnahme**: die `.alert`-Familie in der Optik des
  Meldungs-Bausteins (ohne Symbol — das kommt mit `ui_meldung()` im
  jeweiligen Paket), `.muted` und der Farbchip `.swatch`, ohne den die
  Zuordnung Einsatz → Spurfarbe verloren war. Jede dieser Klassen stirbt in
  ihrem Paket.
- **„‹ Einstellungen" über den Unterseiten fehlte** (E-P3-11, Mockup 07). Es
  steht jetzt dort, sichtbar nur unter 1024 px — am Desktop steht das Menü
  daneben.
- **Das X der Schublade trug beim Öffnen einen Fokusring**, den niemand
  bestellt hat. Der Fokus liegt jetzt auf der Leiste selbst
  (`tabindex="-1"`); wer per Tab weitergeht, landet trotzdem als Erstes auf
  dem X.
- **„Administration" stand als Kartentitel statt als Blocküberschrift** über
  der zweiten Karte (Mockup 07).
- **Der Rückweg der öffentlichen Hülle war unter 1024 px unsichtbar** —
  `.kopf-punkt` ist mobil ausgeblendet, und der Rückweg der Abbruchseite
  hing daran.
- **Leaflet zeichnete über die Schublade.** Die Karte vergibt intern
  z-Indizes bis 1000; Zoomknöpfe und Kartenpin standen mitten im offenen
  Menü. Die Karte hat jetzt ihren eigenen Stapelkontext
  (`position:relative; z-index:0`) — kein Wettrüsten der z-Indizes.

Dazu Feinschliff: Der Hover der Kopfleisten-Knöpfe benutzt die gedämpfte
Weißfläche statt eines hellen Blocks auf Dunkelblau.

### Prüfstand

232 Bilder über 29 Seiten und acht Breiten: **0 Überlauf, 0 Konsolenfehler,
0 Knöpfe außerhalb der 44 px**. Kontraste 21 Paare, 0 verfehlt. Wortliste
0 / 0 / 0. `php -l` über alle 57 PHP-Dateien und `node --check` fehlerfrei.
Beide Dialoge im Browser bei 390 px fotografiert und gegen Mockup 11
gehalten. Die Streichliste wächst um elf Einträge (Dialog- und
Entsperrklassen samt Begründung).

## [Web 9.1.0] — 2026-08-26

**Die Oberfläche hat wieder eine Gestalt.** Zweites Arbeitspaket der Phase P3
(O2): Seitenhülle und Bausteine. Ab hier sieht die Anwendung aus wie das, was
sie werden soll; die Seiteninhalte folgen Paket für Paket (O3 bis O11).
**Keine Migration.** Verhalten, Endpunkte, Datenmodell und Feldkatalog sind
unverändert.

### Web — Ein Menü statt einer Leiste, die den Bildschirm frisst

Der Befund zu P3 hat es bei 360 × 780 gemessen: Die Seitenleiste trug
`position:sticky; height:calc(100vh − 50px); overflow:hidden`. Der erste
Bildschirm war vollständig Leiste — mit rund 60 % Leerfläche —, Titel, Karte
und Tabelle folgten erst nach etwa anderthalb Bildschirmen Scrollen, und die
Tagesliste lief rechts aus dem Bild: Der dritte Tag eines Monats war nicht
erreichbar.

Das galt für **alle zwanzig Inhaltsseiten**, denn das Einstellungsmenü trug
buchstäblich dieselbe Klasse und erbte jede ihrer Regeln.

Jetzt gibt es **eine** Leiste im Markup und zwei Formen im Stylesheet: Unter
1024 px liegt sie als Schublade von links über dem Inhalt, darüber steht sie
fest daneben. Geöffnet wird sie über den Menüknopf in der Kopfleiste,
geschlossen über das ×, die abgedunkelte Fläche oder die Esc-Taste; solange sie
offen ist, bleibt der Tastaturfokus darin.

Dass der Mechanismus an der **Klasse** hängt und nicht an der Funktion, die die
Diensttage ausgibt, ist kein Zufall: Genau davor warnt die Vormerkliste aus
Konzept P0 — sonst bliebe die Suchseite als einzige ohne mobiles Menü, weil sie
ihre Filterleiste selbst hält. Sie benutzt jetzt dieselbe Leiste wie alle
anderen Seiten.

### Web — Was sich sonst noch ändert

- **Die ganze Zeile klappt** das Akkordeon der Diensttage. Bis Web 8.0.1 war
  der Text der Link in die Zeitraumübersicht und nur das Dreieck davor der
  Schalter — mit dem Finger war beides nicht auseinanderzuhalten. Der Weg in
  die Übersicht ist jetzt ein eigenes Balkensymbol am rechten Rand derselben
  Zeile, mit eigener Trefferfläche von 44 px.
- **Das Zahnrad führt auf eine Übersicht** statt ungefragt auf „Profil". Auf
  dem Handy war der Menüpunkt sonst eine Sackgasse: Er landete auf einem
  beliebigen Unterpunkt und verschwieg die übrigen elf.
- **Eine Fußzeile auf jeder Seite**, auch vor der Anmeldung, und außerhalb von
  `<main>`. Sie stand bisher mitten im Inhalt und fehlte deshalb auf jeder
  Seite ohne Inhalt. Verweise auf Impressum und Datenschutz erscheinen, sobald
  es die Seiten gibt (O10) — ein Verweis ins Leere ist schlimmer als keiner.
- **Der Demo-Hinweis steht im Inhalt**, nicht mehr zwischen Kopfleiste und
  Gerüst. Vorher verschob er die klebende Leiste um seine eigene Höhe, und im
  Demo-Konto rutschte sie unter der Kopfleiste hervor (F-P3-G).
- **Die Artzeichen sind keine Emoji mehr.** 🚁 und 🚑 wurden je Betriebssystem
  in anderer Zeichnung, Farbe und Größe gerendert und ließen sich weder färben
  noch auf Kontrast prüfen — und in Tagesleiste, Tabellen und
  Rettungsmittel-Auswahl waren sie die einzige Artauskunft neben dem Tooltip.
  Jetzt kommen sie aus dem Symbolvorrat. Wo kein Bild hineinpasst — in einer
  Auswahlliste —, steht das **Wort**. Von 80 Emoji-Vorkommen sind 9 übrig, alle
  in der Einsatzansicht (O4).
- **Der Einrichter benutzt das gemeinsame Stylesheet** (Backlog Nr. 18). Er war
  die einzige Seite mit eigener Gestaltung im Kopf, eigenen Knopf- und
  Meldungsklassen und ohne Fußzeile — und die einzige, die bei einer
  Farbänderung nicht mitzog.
- **Meldungen tragen ein Symbol** und kommen in vier Tönen: Fehler (rosa,
  Dreieck), Hinweis (blau, Kreis-i), Vollzug (blau, **Haken**), Warnung
  (hellorange, Dreieck). Grün ist fort; Vollzug und Hinweis unterscheiden sich
  jetzt durch das Symbol statt durch eine markenfremde Farbe. Damit ist der
  Vorbehalt E-A6-02 aus Konzept P0 eingelöst, der die Tonart ausdrücklich P3
  überlassen hatte.

### Web — Die Bausteine

`ui.php` führt sie als Funktionen: Kopfleiste, Gerüst, Leiste (drei Inhalte),
Fußzeile, Demo-Hinweis, Meldung, Knopf, Plakette, Karte, Zeile, Titelzeile,
Aktionsmenü, Feld, Schalter, Segmentwahl, Speichern-Leiste, Kennzahl,
Abbruchseite, Einstellungs-Übersicht. Das Stylesheet beschreibt sie in den
Abschnitten 4 bis 16, die vier Schwellen stehen gesammelt in Abschnitt 18.

**Eine Knopfhöhe: 44 px**, mobil wie Desktop, auch für Zeilenaktionen. Der
Bestand hatte sechs Varianten und sechs ortsgebundene Größen; `.btn-primary`
trug global `width:100%` und wurde an zehn Stellen zurückgenommen. Gemessen
über alle 232 Bilder: **0 Knöpfe außerhalb der 44 px**.

**Eine Übergangsschicht** (Stylesheet, Abschnitt 17) gibt Elementen ohne
Baustein — `table`, `input`, `label`, `fieldset` — eine Grundform, damit die
noch nicht umgebauten Seiten lesbar und prüfbar bleiben. Sie greift nur an
Elementnamen, nie an Bestandsklassen; eine Klasse dort einzutragen hieße, das
Redesign zurückzunehmen.

### Web — Kein waagerechter Überlauf mehr, auf keiner Seite

Der Erstlauf gegen den Rohstand hatte **26 Fälle** gemeldet, alle zwischen 360
und 420 px. Nach O2 sind es **0** — bei 232 Bildern über 29 Seiten und acht
Breiten.

Die Messung nennt seit diesem Paket auch den **Verursacher**, und das war die
eigentliche Auskunft: In jedem einzelnen Fall war es eine `<table>` im
Seiteninhalt, keine Stelle des Gerüsts. Der Befund hatte 32 Tabellen gezählt
und genau eine mit Überlaufbehälter. Bis die Einsatztabellen unter 720 px zur
Kachel werden (O3) und die Verwaltungstabellen zu Zeilen stapeln (O8/O9),
tragen sie ihren Überlauf selbst, statt die Seite zu sprengen — mitsamt der
Kopfleiste.

### Repositorium

`tools/screenshots/` misst jetzt zusätzlich, **welches Element** überläuft, und
misst Knopfhöhen nur noch an sichtbaren Knöpfen. Beides sind Funde aus dem
eigenen Lauf: Der erste Bericht meldete Dutzende Knöpfe mit „Höhe 0" — den
X-Knopf der Schublade, der ab 1024 px ausgeblendet ist, und die Einträge in
einem geschlossenen Aktionsblatt.

`tools/vollstaendigkeit/` trennt Emoji und Unicode-Symbole sauber; vorher
zählte ⚠ in beiden Listen. Die Streichliste führt die **25 Klassen**, die mit
der Hülle entfallen sind, je mit Grund.

Handbuch 3 (Web-Überblick) und 4.4 (Diensttage-Leiste) sind nachgezogen,
`docs/Technik.md` ebenso.

## [Web 9.0.0] — 2026-08-26

**Die Weboberfläche wird neu gebaut, mobil zuerst — das hier ist das
Fundament, noch nicht das Haus.** Erstes Arbeitspaket der Phase P3
(Arbeitspaket O1): Token, Stylesheet-Gerüst, Symbolvorrat, Logos und zwei
neue Prüfmittel. **Keine Migration** — `update.php` muss nach diesem Deploy
nicht aufgerufen werden. Die Uhr-App bleibt unverändert.

**Wichtig für den Betrieb:** Dieser Stand allein sieht roh aus. Das
Stylesheet enthält bisher nur Token und Grundlagen; die Bausteine — Karte,
Zeile, Knopf, Meldung, Kopfleiste, Schublade — folgen in 9.1.0. Der
Zwischenstand ist eingeplant (Konzept P3, O2 Punkt 6) und deshalb **nicht
für den Produktivserver bestimmt**; er liegt auf dem Phasenzweig.

### Web — Warum das Stylesheet nicht repariert, sondern ersetzt wurde

Die alte Datei war über zwanzig Fassungen gewachsen und in P0 bereits
gegliedert und entdoppelt worden — sie war nicht schlecht gepflegt. Sie hatte
nur nie eine Stelle, an der ein Wert **einmal** steht. Nachgezählt zum Stand
Web 8.0.1:

| | |
|---|---|
| Hexfarben außerhalb von `:root` | **78** |
| verschiedene Schriftgrößen | 21, in 71 Regeln |
| Pixelmaße außerhalb der Token | 154 |
| die Kopfhöhe `50px`, fest verdrahtet | **5-mal** |
| Graufamilien nebeneinander | 2 — eine warme (`--muted`) und eine kühle |
| `style="…"`-Attribute in PHP und JS | 14 |

Die fünf verdrahteten `50px` sind das Beispiel, an dem sich zeigt, warum
Reparieren nicht gereicht hätte: Die tatsächliche Höhe der Kopfleiste hing
vom Umbruch des Markentexts ab, und das Demo-Banner verschob sie zusätzlich —
im Demo-Konto rutschte die Seitenleiste unter der Kopfleiste hervor. Man
kann das an fünf Stellen nachbessern; man kann auch **ein** Token setzen.

Jetzt: ein Token-Block mit allen Werten, eine Schriftskala (Major Third um
15/16 px, sieben Stufen statt 21 Größen), **eine** Graustufe, eine Kopfhöhe.
Grün und Gelb entfallen ersatzlos — beide waren markenfremd, und beide
trugen Bedeutung doppelt: Gelb hieß „Bearbeiten" in den Stammdaten, wo es
anderswo orange hieß.

### Web — Kontraste: drei Widersprüche im Konzept, aufgelöst

Die Kontrastwerte sind nicht übernommen, sondern nachgerechnet
(`tools/screenshots/kontrast.py`, 21 Paare, 0 verfehlt). Dabei sind drei
Stellen aufgefallen, an denen das Konzept sich selbst widerspricht:

1. **Ränder von Bedienelementen.** Prüfpunkt P-P3-05 verlangt 3:1 für Flächen
   und Ränder; die einzigen Linienfarben im Tokenvorrat erreichen 1,36:1
   (`--linie`) und 1,64:1 (`--sand`). Ein Eingabefeld hätte damit einen Rand
   gehabt, den gute Augen sehen und andere nicht. Aufgelöst mit **zwei**
   Linien: `--linie` bleibt für Trenner und Kartenränder — Zierrat, den
   WCAG 1.4.11 ausdrücklich ausnimmt —, `--linie-stark` begrenzt
   Bedienelemente und ist `--gedaempft` (5,66:1). Ein neuer Farbwert war
   dafür nicht nötig.
2. **Orange als Fläche** führt Anlage G mit 2,2:1, P-P3-05 verlangt 3:1.
   Beides stimmt und meint Verschiedenes: Anlage G misst die Farbe,
   P-P3-05 die Rolle. Orange trägt nirgends allein — der Primärknopf hat
   dunkelblaue Schrift darauf (5,97:1), der aktive Menüpunkt zusätzlich
   Fläche und Fettung. Wo ein oranger Strich doch allein stünde, tritt
   `--orange-tief` (4,32:1) an seine Stelle.
3. **Der Primärknopf** trägt dunkelblaue Schrift auf Orange, wie E-P3-15 es
   festlegt. Die Mockups zeigen an dieser Stelle Weiß (2,3:1) — der
   Entscheidungstext gilt, nicht die Skizze.

Vier weitere Werte in Anlage G lagen zu niedrig, alle zugunsten der
Sicherheit (Asphalt 19,3 statt 17,5; Blau tief 7,8 statt 7,2; Rot tief 7,6
statt 7,1; Primärknopf 6,0 statt 5,4). Die Tabelle in `docs/Design.md` wird
in O12 aus dem Stylesheet **erzeugt**, nicht abgeschrieben.

### Web — Symbole: eine Datei je Zeichen

Der Bestand trug seine Zeichen an vier Orten: fünf Inline-SVG mit Pfaddaten
mitten im PHP und JS (das Zahnrad zweimal, der Karten-Pin wortgleich in zwei
Dateien), 147 Unicode-Zeichen als Symbol (`▸ ▾ ✓ ⚠ ★ ◌ ← →`) und die Emoji
🚁 und 🚑 als Artkennzeichen.

Die Emoji waren dabei das eigentliche Problem: Sie werden je Betriebssystem
in anderer Zeichnung, Farbe und Größe gerendert, lassen sich weder färben
noch auf Kontrast prüfen — und in der Tagesleiste, den Tabellen und der
Rettungsmittel-Auswahl waren sie die einzige Artauskunft neben dem Tooltip.

Jetzt liegen **44 Zeichen als einzelne Dateien** unter
`assets/images/symbole/` (Tabler Icons, MIT-Lizenz, Lizenztext daneben; ein
Zeichen — die Luftlinie — ist ein eigener Entwurf im selben Stil). Eingebunden
werden sie per Verweis: `ui_symbol('haus')` in PHP, `edSymbol('haus')` in
`assets/symbol.js`, beide erzeugen dieselbe Zeichenkette. Farbe über
`currentColor`; ein Symbol in einem roten Knopf ist rot, ohne dass irgendwo
eine zweite Regel stünde.

**Eine Falle, die dabei zugeschnappt ist:** Der Verweis holt das `<g id="i">`
aus der Datei — nicht das `<svg>` darum. Die Attribute `fill="none"` und
`stroke="currentColor"` stehen aber am `<svg>`, damit die Datei sich einzeln
öffnen und ansehen lässt. Ohne Ersatz malte der Browser schwarze Klumpen
statt Strichzeichnungen. Sie stehen jetzt in der Regel `.symbol`, von wo aus
sie in den geklonten Baum vererben.

### Web — Logos in den Markenfarben, und ein Platzhalter fürs NEF

Die drei Logodateien trugen Näherungen statt der Markenwerte (Rot `#E3322B`
statt `#D63338`, Blau `#587ABC` statt `#4280E5`, Orange `#F7941D` statt
`#FF8F1F` — `docs/Branding.md` B1). Sieben Farbwerte sind berichtigt; das
Favicon entsteht seither aus der Logodatei statt daneben
(`tools/logos/erzeugen.mjs`), damit beide nicht wieder auseinanderlaufen.

Dazu ein **NEF-Platzhalter** in denselben Maßen und Fassungen (farbig, weiß,
Favicon). Er steht dort, damit die Logo-Wahl aus E-P3-20 vollständig gebaut
und geprüft werden kann, bevor die echte Datei vorliegt; erkennbar ist er am
gestrichelten Rahmen in Sand. Die echte Datei ersetzt ihn 1:1 — gleicher
Name, gleicher viewBox, kein Eingriff im Code.

### Repositorium — zwei neue Prüfmittel, und warum der Stilvergleich ruht

`tools/stilvergleich/` beantwortet die Frage „hat sich etwas geändert?". Das
ist die richtige Frage nach einer Aufräumrunde und die falsche in einem
Redesign: Dort ändert sich alles, und das Werkzeug liefert Tausende
Abweichungen, die niemand gegen einen Plan hält. Es **ruht während P3** (mit
Vermerk in seiner Anleitung) und wird in O12 neu geeicht, mit Messbreiten bis
hinunter zu 360 px. Ab P4 wacht es wieder.

An seine Stelle treten zwei Werkzeuge mit anderen Fragen:

- **`tools/vollstaendigkeit/`** — Ist etwas verlorengegangen, und steht jeder
  Wert an der einen Stelle? Sollmenge sind die **220 Klassen** aus den
  Selektoren des alten Stylesheets; jede braucht am Ende eine Regel im neuen
  oder einen Eintrag mit Begründung auf der Streichliste. Dazu Hexwerte,
  Schriftgrößen, Pixelmaße, `50px`-Reste, `style="…"`-Attribute, Inline-SVG,
  Unicode-Symbole, Emoji und die Knopfregel.
- **`tools/screenshots/`** — 29 Seiten in acht Breiten von 360 bis 1920 px,
  je Seite ein Kontaktbogen, dazu gemessener waagerechter Überlauf,
  Konsolenfehler und Knopfhöhen. 232 Bilder je Lauf.

Beide melden Zahlen, keine Urteile. Der erste Lauf gegen den Rohstand:
**26 Fälle waagerechten Überlaufs**, alle bei 360–420 px, alle in Paketen
O2 bis O11 zu beheben; **0 Konsolenfehler**.

Nebenbei hat die Vollständigkeitsprüfung einen Bestandsfund geliefert:
**22 Klassen stehen im Markup, für die es im alten Stylesheet keine Regel
gibt** (`card`, `crewrole`, `dreiwert`, `fld`, `focus-target`, `mainnav`,
`nb-veh`, `parentcheck`, `rollehaken`, `rollen-zeile`, `setup-card`, `small`,
`vehcaps`, `vehcaps-zeile` und die `imp-*`-Familie). Sie tun nichts — aber
sie sehen aus, als täten sie etwas, und beim nächsten Umbau richtet sich
jemand danach.

### Repositorium — Konzept, Mockups und die Pflegepflichten

Die Übergabeeinheit der Konzeptphase liegt jetzt im Repositorium:
`docs/Konzept-P3-Oberflaeche.md` und 39 Mockups unter `docs/konzept-p3/`.

Zwei P0-Dokumente sind zu Beginn der Umsetzung nachgereicht worden und haben
den Vorbehalt aus E-P3-03 eingelöst: Die Vormerkliste aus Konzept P0 (10.5)
bestätigt vier von fünf Punkten des eigenen Befunds, liefert zwei Zusätze
(„32 Tabellen, genau eine mit Überlaufbehälter"; `nurWenn` in
`missiontable.js` als vorhandene Vorlage für weglassbare Spalten) und ist in
einem Punkt überholt — die Kopfhöhe steht fünfmal verdrahtet, nicht dreimal.
Der E-A6-02-Vorbehalt zur Tonart der Vollzugsmeldung ist damit wörtlich
verfügbar und lautet genau so, wie E-P3-16 ihn einlöst.

`CLAUDE.md` bekommt Abschnitt 9 **Pflegepflichten**: welches Dokument zu
welcher Änderung gehört, und die Regel, dass ein neuer Baustein vorher
freigegeben wird.

## [Web 8.0.1] — 2026-08-24

**Die Anwendung spricht neutral von Land und Luft — vor dem Redesign, damit
das Redesign fertige Texte übernimmt und nicht selbst Wortwahl entscheidet.**
Geändert werden sichtbare Texte der Weboberfläche und die normative
Dokumentation. Dabei sind fünf Aussagen aufgefallen, die schlicht nicht mehr
stimmten. **Keine Migration** — `update.php` muss nach diesem Deploy nicht
aufgerufen werden. Die Uhr-App bleibt unverändert.

### Web — Was die Erhebung ergeben hat

Der Rahmenplan nannte „~290 Fundstellen". Nachgezählt auf Web 8.0.0 waren es
rund **330 echte Treffer** — und die Zahl war aus einem anderen Grund
irreführend, als sie aussah: Sie zählte Teilstrings. Von 65 Treffern für
`rth` waren **zwei** echt, die übrigen 90 hießen „dorthin", „northeast" oder
„earth"; von 13 Treffern für `heli` **keiner** („naheliegend", ein
Logo-Dateiname); „maschine" zur Hälfte („maschinell", „maschinenlesbar").
Und rund 80 % der echten Treffer sind Kommentare, Bezeichner, Formatfelder
oder ausdrückliche Historie, die bewusst stehen bleiben.

Der andere Befund war unangenehmer. Der Changelog zu Web 6.2.0 behauptet:
„Verwaiste Verweise auf „Flugtag", „Hubschrauber" und eine Phase 10 gibt es
in der Dokumentation nicht mehr." Das traf nicht zu. Das README war
vollständig alt, das Handbuch beschrieb eine Kopfleiste, die es so nicht
gibt, und die Excel-Spaltentabelle der Format-Doku beschrieb einen Export,
den es seit Web 6.0.0 nicht mehr gibt. Der alte Eintrag bleibt stehen — er
gehört zur Geschichte. Was fehlte, war keine Sorgfalt beim Ändern, sondern
eine **Zählung**: eine Behauptung ohne Zahl.

### Web — Was jetzt anders heißt

**Weboberfläche.** Die Kopplungsanleitung beschreibt den Ablauf gerätefrei
(„Sync-Seite → Gerät koppeln → Code eintippen"); der Tastenweg steht darunter
als eigener Absatz mit genannter Plattform und verweist für die einzelnen
Uhren auf das Handbuch. Der Grund ist kein sprachlicher: Der alte Satz nannte
den Weg von Fenix und Forerunner, als gälte er für jede Uhr — für die
Venu 3s war er falsch, seit sie dazukam. Sie hat weder START noch DOWN.

Dazu: „Connect-IQ-Einstellungen" → „Einstellungen der Uhr-App (bei Garmin:
Garmin Connect)"; die Produktivdomain als Beispiel → `nadoku.beispieldomain.de`
(jede Installation hat ihren eigenen Server); „eine Flugspur endet am
Einsatzort" → „ein Track" (ein Bodentrack endet ebenso dort); „RTW, NEF,
weitere Hubschrauber" → „RTW, NEF, RTH …" — „weitere" setzte voraus, dass das
eigene Rettungsmittel ein Hubschrauber ist; und beide Stammdatenseiten zeigen
jetzt denselben Platzhalter „z. B. Christoph 17 oder NEF Kempten 1" statt je
einer Art.

**Schwachwortliste.** Fünf bodengebundene Gegenstücke ergänzt
(`notarztwagen`, `rettungswagen`, `notfallsanitaeter`, `rettungsdienst`,
`einsatzdoku`) — und der Kommentar sagt jetzt, was die Liste eigentlich tut.
Der Vergleich lautet `k === h || (h.length >= 6 && k.includes(h))`: ein Wort
unter sechs Zeichen trifft nur als ganzes Passwort. Deshalb bleiben Kurzformen
wie `nef` oder `rth` draußen — sie träfen ohnehin nichts, was die
Mindestlänge nicht schon abweist.

**Und deshalb sind die fünf Ergänzungen wirkungslos**, was hier stehen soll,
statt beschönigt zu werden: `rettungswagen` und `rettungsdienst` enthalten
`rettung`, `notarztwagen` enthält `notarzt`, `notfallsanitaeter` enthält
`sanitaeter`, `einsatzdoku` enthält `einsatz` — alle fünf waren über den
Teilstring-Vergleich längst abgedeckt. 768 Passwortproben durch beide
Fassungen: **0 Abweichungen**. Die Wörter bleiben trotzdem in der Liste, weil
sie zeigen, was gemeint ist; als Sicherheitsgewinn zählen sie nicht.

**Ein sechstes Wort war vorgesehen und ist wieder heraus:** der künftige
Produktname. Mit ihm in der Liste ließe sich das Demo-Passwort
`nadokudemo0815` weder setzen noch als Backup- oder Exportpasswort verwenden —
es enthält den Namen. Aufgefallen ist das nicht beim Lesen, sondern daran,
dass der Kreislauftest in einen Zeitüberlauf lief: Die erneute Sicherung
wurde gar nicht erzeugt, weil das Passwortfeld sie ablehnte. Der Name gehört
in die Liste, wenn er kommt — zusammen mit einem neuen Demo-Passwort.

**Dokumentation.** README neu gefasst (beide Einsatzarten, die Uhr als
derzeitige Plattform mit allen drei Modellen, sieben fehlende Dokumente in
der Tabelle ergänzt); Handbuch in allen Kapiteln außer 2; Export-Format und
Technik-Doku in den berührten Abschnitten.

### Web — Fünf Aussagen, die nicht mehr stimmten

- **Der Warntext des Excel-Rückimports** nannte die Phasen „Abflug" und
  „Landung Krankenhaus" und die Spalte „Flugkilometer" — Namen, die es seit
  Web 6.0.0 nicht mehr gibt. Er lautet jetzt Ausrücken, Ankunft Klinik,
  Kilometer, und zwar zeichengleich in `import_profiles.js` und in
  `Export-Format.md` 5.2 (453 Zeichen, maschinell verglichen).
- **Die Excel-Spaltentabelle** listete 29 Spalten mit „7 geschützten"; der
  Export schreibt 31 mit 16 geschützten, und die Besatzungsspalten sind
  sieben aus dem Rollenkatalog, nicht fünf feste. An einer erzeugten Datei
  nachgemessen: 31 Spalten mit personenbezogenen Angaben, 15 ohne.
- **Die Kopfzeile der Einsatzansicht** zeigt seit Web 7.0.0 gar keine Strecke
  mehr. Das Handbuch versprach „Flugkilometer".
- **Die Kopfleiste** heißt seit Web 6.x „Einsatzdokumentation Notarzt"; das
  Handbuch schrieb „Luftrettung".
- **Ein Kommentar verwies auf `SPEC_Export.md` 7.2** — ein Dokument, das es
  nicht gibt. Gemeint ist `docs/Export-Format.md` 5.2.

Dazu drei Stellen, die kein Suchmuster findet, weil sie kein Luftwort
enthalten: die Feldgruppe „Weitere Rettungsmittel", mit „Fahrzeuge" erklärt,
obwohl für ein NEF das weitere Mittel oft gerade der Hubschrauber ist; die
Kachel „Höchster Einsatzort" als interaktiv in jedem Reiter beschrieben,
obwohl es sie nur in der Luftrettung gibt; und „Basis" bzw. „Station" für
den Standort.

### Web — Was bewusst luftsprachig bleibt

Der **Luftrettungs-Reiter** der Zeitraumübersicht behält seine zehn Kacheln
mit „Flugtage", „Ø Einsätze / Flugtag" und „Flugkilometer gesamt": Er ist per
Definition luftgebunden, und „Diensttage" wäre dort nicht neutraler, sondern
ungenauer. Ebenso bleiben die **Fachrollen** Pilot 1, Pilot 2, HEMS-TC und
Flugretter, die Fähigkeiten **Winde, Luftverladung, Bergwacht**, der
Fahrzeugtyp **RTH** — er steht jetzt neben NEF, NAW und RTW statt an ihrer
Stelle — und die Art **luftgebunden**.

Unangetastet bleiben außerdem alle **gespeicherten und vertraglichen Namen**:
DB-Spalten, Rollencodes (`p1`, `p2`, `hems`, `fr`), Spaltenbeschriftungen in
CSV, Excel und Sicherung, der JSON-Vertrag und die Kopfzeilen der
Import-Profile. Die Beschriftung einer Excel-Spalte ist ein Formatfeld — sie
wird beim Rückimport wiedererkannt. Wer sie umbenennt, macht jede vorhandene
Datei unlesbar, ohne dass jemand etwas davon hat.

**Sätze mit Versionsangabe bleiben ebenfalls.** „Hieß bis Web 5.10.0
`flugtage.csv`" erklärt eine Datei von 2025. Der alte Begriff steht nur dort,
wo ausdrücklich gesagt wird, dass er alt ist.

### Web — Ein neues Prüfmittel: `tools/wortliste/`

Damit die Behauptung dieses Eintrags nicht dasselbe Schicksal erleidet wie
die von Web 6.2.0, gibt es jetzt ein Werkzeug, das sie **zählt**. Es sucht
23 Muster in drei Bereichen (PHP des Servers, JavaScript ohne `vendor/`,
sieben Dokumente) und nennt je Bereich drei Zahlen: Treffer gesamt, Treffer
außerhalb der Ausnahmen, **ungenutzte Ausnahmen**. Die letzten beiden müssen
null sein.

Gegen den Stand vor dieser Fassung meldete es **53 Treffer außerhalb der
Ausnahmen in 44 Zeilen** und fiel damit durch — das war der Zweck des ersten
Laufs: Ein Prüfmittel, von dem niemand weiß, ob es scheitern kann, ist keines.
Gegen den Endstand: **0 / 0 / 0**. Die 44 Ausnahmeregeln tragen jede eine
Fundort-Klasse und einen Grund; eine Regel ohne Begründung weist das Werkzeug
beim Laden zurück.

Kommentare werden vorher entfernt, sonst füllte Klasse E den Befund. Dafür
gibt es einen eigenen Zerleger statt eines regulären Ausdrucks: Der
naheliegende Einzeiler `re.sub(r'//.*$', …)` löscht die Hälfte jeder Zeile
mit einer URL — also sichtbaren Text —, und ein so verschwundener Treffer
fällt niemandem auf. Sechzehn Proben mit Sollergebnis sichern ihn ab.

`tools/` wird nicht ausgeliefert; das Werkzeug kommt dem Produktivserver
nicht nahe. Es läuft in P3 (neue Oberfläche) und P6 (Umbenennung) mit.

### Web — Zwei Funde derselben Phase, die keine Terminologie sind

Beide wurden in P2 gefunden und dort nach der Regel „sammeln, nicht nebenbei
beheben" liegengelassen. Sie sind hier eingefaltet, weil 8.0.1 zu diesem
Zeitpunkt auf keinem Server stand: Eine 8.0.2, die eine 8.0.1 berichtigt, die
es nirgends gab, wäre eine Zahl ohne Gegenstück.

**Das Demo-Konto ließ sich auf einer Entwicklungsmaschine nicht anlegen.** Die
Administration bekam `SQLSTATE[23000] … Duplicate entry 'manual-2' for key
'device_id'` zu sehen und sonst nichts. Ursache: Die Fixture führt die Geräte
des Referenzkontos mit — darunter das **virtuelle** Gerät „Manuelle
Einträge". Dessen Kennung ist `manual-<Kontonummer>`, und `device_id` ist in
`devices` **global** eindeutig. Auf einer Installation, die auch den Bestand
führt, aus dem die Fixture stammt, kollidiert die Kennung zwangsläufig.

Das virtuelle Gerät hat in der Fixture nichts verloren, und zwar aus einem
zweiten Grund: Die Nummer darin ist die des **Quellkontos**. Im Demo-Konto
wäre sie ohnehin falsch — dort entstünde bei der ersten Handeingabe ein
zweites virtuelles Gerät mit der richtigen. Genau so ist es im Normalbetrieb
gedacht: `einsatz_form.php` und `api/import_commit.php` legen es bei Bedarf
an, dauerhaft deaktiviert. Verwiesen wird darauf nichts — `day_refs` nennen
nur echte Geräte, und `missions.device_id` steht gar nicht erst in der
Sicherung. In der ausgelieferten Fixture: **0 Vorkommen** von `manual-` in der
Nutzlast.

Es wird deshalb an zwei Stellen ausgeschlossen: beim **Einspielen**
(`demo_lib.php` — das wirkt auch für Fixtures, die schon irgendwo liegen) und
beim **Erzeugen** (`fixture/erzeugen.php` — damit es gar nicht erst
hineinkommt). Die ausgelieferte `fixture.json.gz` wurde **nicht** neu erzeugt;
sie braucht es nicht, und 745 KB Binärdatei wegen eines übersprungenen
Eintrags neu zu schreiben, verbessert nichts.

Zwei Dinge fielen dabei mit ab. Die Adminansicht zählte die Geräte des
Demo-Kontos als einzige Stelle **mit** dem virtuellen — sie meldete drei, wo
Geräteliste und Gerätegrenze zwei sehen; sie benutzt jetzt dieselbe Regel.
Und der rohe Datenbankfehler ist weg: `admin_demo.php` unterscheidet jetzt
zwischen den Meldungen, die `demo_lib.php` selbst **für** die Administration
schreibt (die bleiben wörtlich), einer erkannten Dublette und allem übrigen.
Die technische Ursache geht ins Fehlerprotokoll, in die Seite geht ein Satz
und eine Kennung, unter der sie dort steht — dasselbe Muster wie in
`admin_user.php` seit Web 5.x.

**Die Sicherungsbeschreibung nannte eine Rolle, die es nicht gibt.**
`docs/Backup-Format.md` führte an drei Stellen den Rollencode `tc`; die
Anwendung kennt `p1`, `p2`, **`hems`**, `fr`, `driver`, `trainee`, `other`.
`tc` kommt in keiner Quelldatei vor und hat nie existiert — gemeint war
offenbar „HEMS-**TC**". Wer ein Werkzeug gegen die Beschreibung baute, suchte
einen Schlüssel, den keine Datei führt.

Beim Berichtigen kam zweierlei dazu: Die Beispiele zeigten eine Reihenfolge,
die keine Datei hat — `backup_lib.php` liest `ORDER BY role_code`, die Listen
sind also **alphabetisch**, nicht in Katalogreihenfolge. Und es gab nur ein
luftgebundenes Rettungsmittel mit der Notation `"kind": "air|ground"`, die
sonst nirgends vorkommt; jetzt stehen dort zwei Beispiele, eines je Art.

### Werkzeuge — zwei Fragen der Repo-Hygiene

Kein Deploy-Inhalt: Beides liegt außerhalb von `server/` und ändert an der
Anwendung nichts.

`.gitignore` führte `tools/referenzdatensatz/einspielen/*_rc.json` — ein
Eintrag, der erkennbar für die Datei gedacht war, die `LIESMICH.md` und
`einspielen.py` anzulegen anweisen, und sie verfehlte: Sie heißt `rc.json`,
ohne Präfix. Das Muster lautet jetzt `*rc.json`. Erweitert wurde das Muster
und nicht der Dateiname in der Anleitung geändert, weil ein Dateiname erst
wirkt, wenn ihn jemand liest — auf Maschinen mit einer `rc.json` aus einem
früheren Lauf und bei jedem, der den Aufruf aus Gewohnheit kopiert, wirkt er
gar nicht.

Und zwei `.pyc` lagen im Repositorium, obwohl `.gitignore` ihr Verzeichnis
führt: Die Regel wirkt nicht auf bereits verfolgte Dateien. Sie sind
ausgetragen. Gegenprobe über alle verfolgten Dateien: keine weitere, die
ignoriert sein sollte.

### Web — Regression

Beide Kreisläufe unverändert auf null unerklärten Abweichungen,
`browser/angriffswerte.mjs` 42/0, `browser/demo_pruefen.mjs` 24/0. P2 berührt
keinen Schreibweg, kein Datenformat und kein CSS — ein Stilvergleich war
deshalb nicht nötig. Der Excel-Rückimport derselben Datei vor und nach der
Änderung meldet dieselbe Bilanz (14 Diensttage, 78 Einsätze, 20 Hinweise,
0 Fehler, 78 Dubletten).

Einzelheiten, Wortliste mit Grenzfällen, Fehlerfunde und Prüfstand:
`docs/Konzept-P2-Terminologie.md`. Was noch von Hand zu prüfen ist:
`docs/Pruefdokument-P2-Terminologie.md`.

## [Web 8.0.0] — 2026-08-24

**Die Sicherung wird vollständig: Der Papierkorb steht künftig darin und kommt
als Papierkorb zurück.** Dazu die beiden Importfehler, die der CSV-Kreislauf
der Phase P1 gemessen hatte, drei Stellen, an denen eine kaputte Datei bisher
nicht ihre Zeile, sondern den ganzen Einspielvorgang kostete, und die drei
Wege, auf denen ein Einsatz an einem gelöschten Diensttag landen konnte.
Nutzlastversion der Sicherung 6 → 7. **Keine Migration** — die Spalten liegen
seit jeher, sie standen nur leer in der Datei.

### Web — Der Papierkorb in der Sicherung (Backlog Nr. 30)

Bis hierher filterte `edbak_build()` an drei Stellen auf
`deleted_at IS NULL`. Die Begründung dafür stand im Kopfkommentar und klang
einleuchtend: „Wer eine Sicherung erstellt, sichert seinen Bestand, nicht
seinen Abfall."

Sie war falsch, und zwar an der Stelle, an der es teuer wird. Der Papierkorb
ist kein Abfall — er ist ein **wiederherstellbarer Zustand mit laufender
Frist** (90 Tage, `TRASH_DAYS`). Der praktische Fall: Jemand löscht
versehentlich einen Diensttag, sichert am selben Abend, merkt den Fehler erst
Wochen später und spielt die Sicherung zurück. Er verliert genau das, was er
zurückholen wollte — endgültig und ohne einen Hinweis, dass etwas fehlt.

**Was geändert wurde.** Der Filter ist weg, ebenso der Parameter
`$mitPapierkorb`, mit dem bis Web 7.3.0 nur die Demo-Fixture den Papierkorb
mitnahm. Jede Sicherung enthält ihn jetzt: die eigene, die der Administration
und die Fixture. Die Nutzlastversion steigt auf **7**.

**Keine Wahlmöglichkeit auf der Sicherungsseite**, und das ist Absicht: Ein
Haken „Papierkorb mitsichern" verschöbe die Entscheidung auf den Zeitpunkt, an
dem am wenigsten überlegt wird. Eine Sicherung ist ein Abbild. Sichtbar wird
der Anteil stattdessen dort, wo Zahlen ohnehin stehen — in der
Sicherungsübersicht der Administration („davon im Papierkorb: 5 Einsätze,
1 Diensttag, 5 Ruhezeiten"), im Hinweis auf eine freigegebene Sicherung und im
neuen Unterblock `umfang.papierkorb` der Admin-Sicherung. Der Unterblock ist
additiv; die Paketversion der Admin-Sicherung bleibt 1, und wo er fehlt
(Sicherungen von vorher), zeigt die Anzeige **nichts** statt einer Null —
„nicht erhoben" ist etwas anderes als „nichts drin".

**Der Sprung auf Nutzlast 7 kennzeichnet, er sperrt nicht.** Die
Annahmeschranke in `api/backup_restore.php` bleibt bei „ab Version 6": Eine
Version-6-Datei enthält keinen Papierkorb, ihr fehlt aber nichts, was sich
erraten müsste — sie bleibt vollständig einspielbar. Umgekehrt gilt: Ein
bereits **ausgelieferter** Stand hat dieselbe Schranke, wertet `deleted_at`
aber nicht aus und brächte den Papierkorb einer v7-Datei als aktiven Bestand
zurück. Das ließ sich nachträglich nicht verhindern — eine Sperre hätte in
jenen Ständen stehen müssen. Es steht deshalb als Warnung in
`docs/Backup-Format.md` 4, statt als Zusage behauptet zu werden, die niemand
prüfen kann.

**Nebenbei berichtigt:** `docs/Technik.md` nannte an einer Stelle eine
„30-Tage-Frist" für den Papierkorb. Es sind 90 (`TRASH_DAYS`), und alle
übrigen Stellen sagten das auch.

### Web — Der Rückweg: Papierkorb kommt als Papierkorb zurück

Die Datei zu füllen ist die halbe Arbeit. `edbak_restore()` schrieb keine der
drei Spalten (`days.deleted_at`, `missions.deleted_at`/`deleted_with_day`, und
dieselben zwei an den Ruhesegmenten) — ein bloßes Abschalten des Filters hätte
den Papierkorb als **aktiven** Bestand zurückgebracht, und das wäre schlimmer
als ihn zu verlieren: Was jemand gelöscht hat, stünde nach der
Wiederherstellung wieder in der Tagesliste.

**Der Zustand kommt aus der Datei, der Zeitpunkt aus dem Einspielvorgang.**
Alle Einträge eines Laufs tragen denselben `deleted_at`, und die 90 Tage
beginnen neu. Dieselbe Linie wie bei `origin`: Der Eintrag entsteht in dieser
Installation neu. Die Gegenrechnung wäre teuer — eine ältere Sicherung brächte
Einträge mit abgelaufener Frist mit, und der nächste Aufräumjob entfernte sie
endgültig, ohne dass jemand sie je gesehen hätte.

**Die Invariante, ohne die es einen Zombie gäbe.** `deleted_with_day = 1` wird
nur geschrieben, wenn der Eintrag **in der Datei** am Tag hing **und** der
**Zieltag** selbst im Papierkorb liegt — sonst `0`. Der Grund steht in zwei
Zeilen an anderer Stelle: `trash_list_missions()` zeigt nur
`deleted_with_day = 0`, `trash_restore_day()` holt nur zurück, was am
gelöschten Tag hängt. Beide Hälften sind nötig:

- Ein Eintrag mit `deleted_with_day = 1` an einem **aktiven** Tag wäre
  unsichtbar **und** unwiederbringlich. Ein in der Datei mitgelöschter
  Einsatz, dessen Zieltag hier aktiv ist, kommt deshalb einzeln gelöscht an.
- Ein in der Datei **einzeln** gelöschter Einsatz an einem hier ebenfalls
  gelöschten Tag bliebe umgekehrt nur dann auffindbar, wenn der Wert der Datei
  mitzählt. Er darf nicht zum Mitgelöschten werden: Er verschwände aus der
  Papierkorbliste und würde beim Wiederherstellen des Tages wieder aktiv,
  obwohl ihn jemand ausdrücklich gelöscht hatte.

Der Zieltag ist damit eine **notwendige, keine hinreichende** Bedingung.

**D1 hat jetzt zwei Hälften.** Die Datumsprüfung gegen den Papierkorb des
Zielkontos gilt weiter — aber nur für **aktive** Datei-Tage. Ein in der Datei
gelöschter Tag will gar nicht aktiv werden; ihn am Ziel-Papierkorb zu messen
ergäbe keinen Sinn. Er durchläuft die normale Wiedererkennung und entsteht,
wenn er fehlt, als Papierkorbeintrag. Wird er gefunden, bleibt der Zieltag
unangetastet — „Angaben werden nicht überschrieben" gilt für den Löschzustand
wie für alles andere.

**Und die Gegenrichtung: Ein aktiver Eintrag an einem gelöschten Zieltag wird
abgelehnt.** Landet ein in der Datei aktiver Einsatz oder ein Ruhesegment auf
einem Zieltag, der hier im Papierkorb liegt, stünde er an einem Tag, den die
Tagesübersicht nicht zeigt: in der Suche und auf der Einsatzseite sichtbar, in
Tagesliste, Zeitraum, Export, Nachbearbeitung und Papierkorb nicht — und beim
endgültigen Löschen des Tages bliebe er ohne Diensttag zurück. Halb sichtbar
ist schlechter als unsichtbar. Er wird deshalb übersprungen und unter
`tag_im_papierkorb` gezählt: dieselbe Regel wie D1, eine Ebene tiefer. Die
Datumsprüfung von D1 fängt den Fall nicht ab, denn die Wiedererkennung über
`client_ref` kann auf einen Zieltag anderen Datums führen.

**Die Rückmeldung war unvollständig und ist es nicht mehr.** Sie kannte drei
Überspringgründe von fünf; `tag_im_papierkorb` und `tag_unbrauchbar`
erschienen als roher Schlüssel. Einsätze und Ruhesegmente eines übersprungenen
Tages liefen unter „unbrauchbares Datum oder Zeit" — irreführend, denn an
ihrem Datum ist nichts auszusetzen; sie haben einen neuen, eigenen Grund
(`tag_uebersprungen`). Ruhesegmente zählten ihre Gründe **gar nicht** mit,
obwohl „bereits vorhanden" bei ihnen der häufigste Fall ist. Und die beiden
Einspielwege — eigene Datei und freigegebene Sicherung — hatten zwei getrennte
Textbausteine, die auseinandergelaufen waren; jetzt gibt es einen.

### Web — Eine kaputte Sicherungsdatei kostete den ganzen Lauf (Backlog Nr. 31 und 35)

Das Einspielen hängt an **einer** Transaktion. Das ist richtig so — ein halb
eingespielter Bestand wäre schlimmer als keiner —, hat aber eine Kehrseite:
Jede Datenbankausnahme reißt alles mit, auch die neunzig heilen Einsätze
daneben, und der Aufrufer sieht statt einer Bilanz nur eine Fehlermeldung.
Genau dafür gibt es die Prüfschicht: prüfen, im Zweifel die Zeile
überspringen, den Grund zählen. An drei Stellen fehlte sie.

**Die Ruhesegmente hatten gar keine.** `started_at` und `ended_at` gingen roh
gegen `DATETIME NOT NULL`, `client_ref` ohne Längengrenze gegen `VARCHAR(64)`,
und die Spur wurde ungeprüft und unbegrenzt geschrieben — `(float)"Unfug"` ist
`0.0`, aus einem unbrauchbaren Punkt wurde also still eine gültige Koordinate
im Golf von Guinea. Beim Einsatz war dieselbe Richtung längst eingebaut; die
Ruhesegmente sind damals übersehen worden. Das Schreiben der Spur ist jetzt
**eine** Funktion für beide Arten statt zweier auseinandergelaufener Kopien.

**Doppelte Spurnummern kippten den Lauf.** `track_points` hat den
Primärschlüssel `(owner_type, owner_id, seq)`. Der Wertebereich von `seq` war
geprüft, seine Eindeutigkeit nicht. Der zweite Punkt mit derselben Nummer wird
jetzt übersprungen und als `…track.seq: Nummer doppelt` gemeldet. Der kürzere
Weg wäre `INSERT IGNORE` gewesen — der stille: Die Datei behielte einen
Fehler, den niemand zu sehen bekommt.

Betroffen sind nur Dateien fremder oder von Hand bearbeiteter Herkunft; ein
eigener Export erzeugt weder unbrauchbare Zeiten noch Wiedergänger. Das ist
kein Grund, es stehen zu lassen: Eine Wiederherstellung ist der Moment, in dem
jemand ohnehin schon etwas verloren hat.

### Web — Der halb sichtbare Einsatz hat keinen Weg mehr (Backlog Nr. 33)

Ein **aktiver** Einsatz an einem **gelöschten** Diensttag ist der Zustand, den
das Einspielen einer Sicherung seit dieser Fassung ablehnt (E-S1-19). Die
Anwendung selbst konnte ihn herstellen — mit einem Klick.

**Der Papierkorb lehnt jetzt ab.** Ein einzeln gelöschter Einsatz steht in der
Liste des Papierkorbs, auch wenn sein Diensttag danach ebenfalls gelöscht
wurde. „Wiederherstellen" machte ihn aktiv — an einem Tag, den die
Tagesübersicht nicht zeigt. Jetzt sagt die Seite, was zu tun ist: erst den
Diensttag zurückholen. Ihn stillschweigend mitzurückzuholen wäre die falsche
Großzügigkeit — ein Klick auf **einen** Einsatz würde einen ganzen Dienst samt
aller übrigen Einsätze wiederbeleben.

**Die Uhr löst einen neuen Diensttag aus.** Zeigt eine Dienstkennung in
`day_refs` auf einen gelöschten Tag, entsteht ein neuer, und die Kennung wird
auf ihn umgebogen. Den Upload stattdessen zu verwerfen wäre die schlechtere
Wahl: Die Uhr hat den Dienst geflogen, und sie sendet ein Paket nur, bis der
Server es quittiert — verworfen ist fort. Ein zusätzlicher Diensttag dagegen
ist umkehrbar; stellt sich heraus, dass er doch zum alten gehört, führt
`diensttag_zusammenfuehren.php` beide zusammen. Der gelöschte Tag verliert
dabei seine Kennung, und das ist richtig so: Wird er später zurückgeholt,
gehört die weiterlaufende Uhr-Sitzung zum neuen Tag.

**Das endgültige Löschen lässt kein Waisenkind zurück.** `trash_purge_day()`
entfernte nur die **gelöschten** Einsätze des Tages und danach den Tag; ein
aktiver Einsatz daran überlebte den ersten Schritt und verlor im zweiten
seinen Diensttag (`ON DELETE SET NULL`). Danach war er in der Suche und auf
der Einsatzseite sichtbar, in Tagesübersicht, Zeitraum, Export und
Nachbearbeitung nicht, im Formular nicht mehr zu öffnen — und in der Sicherung
zwar enthalten, beim Einspielen aber übersprungen, weil ihm der Diensttag
fehlt. Ein Datensatz, der gerettet aussah und beim nächsten Umlauf still
verschwand.

Jetzt geht alles mit, und die Rückfrage **nennt es vorher einzeln** mit Datum,
Uhrzeit und einem Link zum Verschieben. *Ablehnen* wäre die scheinbar
vorsichtigere Wahl gewesen und ist eine Sackgasse: Diese Einsätze stehen in
keiner Liste, man kann sie also nicht wegräumen — der Diensttag wäre nie
loszuwerden.

**Was vorher entstanden ist, meldet die Wartungsseite.** `update.php` zählt
aktive Einsätze ohne Diensttag und listet sie mit Konto, Beginn und Kennung.
Sie ändert nichts: Welcher Diensttag der richtige ist, weiß eine
Wartungsseite nicht. Als Bericht und nicht als Migration, damit die Meldung
so lange stehen bleibt, wie es den Zustand gibt.

### Web — Die Diensttag-Wiedererkennung rät nicht mehr (Backlog Nr. 34)

Beim Einspielen erkennt `edbak_restore()` einen Diensttag zuerst über die
Einsatzkennungen (`client_ref`), ersatzweise über einen Fingerabdruck aus
Datum, Beginn, Ende, Art und Bezeichnungen. Schritt 1 nahm den **ersten**
gefundenen Einsatz und verhängte dessen Diensttag über **alle** Einsätze und
Ruhesegmente des Datei-Tags. Drei Dinge waren daran falsch: Hatte jemand im
Zielkonto einen dieser Einsätze auf einen anderen Tag verschoben, wanderte der
ganze Datei-Tag mit; führte der Treffer auf einen Tag im Papierkorb, wurden
seither alle aktiven Einträge des Datei-Tags abgelehnt (richtig gezählt — aber
angekommen war nichts); und `LIMIT 1` ohne `ORDER BY` bei einer Kennung, die
nur je Gerät eindeutig ist, ließ offen, welcher Treffer gewinnt.

Jetzt werden **alle** Kennungen des Datei-Tags nachgeschlagen, und nur auf
aktive Zieltage. Genau ein Ergebnis wird benutzt — das bisherige Verhalten,
jetzt belegt. Mehrere verschiedene heißen: Schritt 1 weiß es nicht. Dann
entscheidet der Fingerabdruck, und der Widerspruch erscheint als
`tag_mehrdeutig` in der Rückmeldung. Die richtige Antwort auf „raten" ist
nicht, anders zu raten, sondern zu merken, dass man es nicht weiß.

**Der Fingerabdruck bleibt Schritt 2**, obwohl er hier zuverlässiger gewesen
wäre. Er ist der sprödere Anker: Er bricht, sobald jemand am Zieltag Beginn,
Ende, Art, Rettungsmittel oder Station berichtigt hat — und das ist der
häufige Fall. `client_ref` ist stabil. Ihn zurückzustufen verschlechterte den
häufigen Fall zugunsten des seltenen.

### Web — `created_at` kommt zurück (Backlog Nr. 25)

Der Anlegezeitpunkt eines Einsatzes wurde immer gesichert und nie eingespielt.
Nach einer Wiederherstellung trugen alle Einsätze den Zeitpunkt des
Einspielens — am Referenzdatensatz gemessen 79 verschiedene Werte davor, 5
danach. Fachlich folgenlos (`started_at` ist die Zeit, die zählt), aber es war
ein stiller Verlust, und das Vergleichswerkzeug sah ihn nicht, weil es
`created_at` wegnormalisierte.

Er steht jetzt als benannte Ausnahmespalte neben `start_src` und `pat_blob`.
Ein unbrauchbarer Wert lässt die Spalte **weg** statt `NULL` zu schreiben —
dann greift die Vorgabe der Datenbank und die Zeile bleibt. Gemessen: 87 von
87 Einsätzen tragen nach dem Umlauf denselben Wert wie vorher.

### Web — Der Demo-Reset kommt ohne Nachlauf aus

Weil das Sicherungsformat keine gelöschten Einträge kannte, stellte der
Demo-Reset den Papierkorb bisher **nach** dem Einspielen nach: Ein Drehbuch in
der Fixture nannte Einsätze und Diensttage, die `demo_nachlauf()` anschließend
über die regulären Löschwege wieder löschte. Das musste nach dem Commit
laufen, weil `trash_delete_*()` je eine eigene Transaktion öffnen — der Reset
zerfiel damit in zwei Schritte, von denen der zweite fehlschlagen konnte
(sichtbar, harmlos, aber eben ein zweiter Schritt).

Der Grund ist weg, also ist das Drehbuch weg: `demo_nachlauf()`, der
Fixture-Block `nachlauf` und dessen Erzeugung sind entfallen. Der Reset ist
wieder **ein** Vorgang in **einer** Transaktion; die Papierkorbzahlen im
Bericht kommen aus den Zählern der Einspielroutine. Die Fixture zählt
deshalb auf **Format 2** hoch — die Nummer kennzeichnet den entfallenen Block,
sie sperrt nichts: `demo_fixture_laden()` bleibt tolerant, und eine Fixture
der Version 1 lässt sich weiterhin einspielen (ihr `daten`-Block trägt
`deleted_at` bereits, ihr `nachlauf` wird nur nicht mehr gelesen).

Nebenwirkung, die gut passt: Weil beim Einspielen ohnehin der
Einspielzeitpunkt gesetzt wird, stempelt **jeder** Reset die 90-Tage-Frist
frisch. Das Demo-Konto hält seinen Papierkorb damit von selbst am Leben.

### Web — Mehrzeilige Notizen verloren beim CSV-Rückimport ihre Umbrüche (Backlog Nr. 27)

Der Parser `trim` in `assets/import.js` zieht jede Leerraumfolge auf ein
Leerzeichen zusammen — und ein Zeilenumbruch ist Leerraum. Eine dreizeilige
Notiz kam damit einzeilig zurück: Der Text war vollständig, seine **Gliederung**
war weg, und niemand bekam davon etwas zu sehen. Gemessen im Kreislauf der
Phase P1 an vier Notizen (Fund F-P1-L).

Für die Notizspalten gilt jetzt `trimMehrzeilig`: zusammengezogen wird nur
**innerhalb** einer Zeile, Zeilenenden werden vorher vereinheitlicht (eine
CSV-Datei aus Excel bringt `\r\n` mit, und ein stehengebliebenes `\r` wäre ein
unsichtbares Zeichen im Bestand). Leerzeilen am Anfang und Ende fallen weg, die
in der Mitte bleiben — sie sind Gliederung, kein Rest. Bei einzeiligen Werten
ist das Ergebnis identisch zu `trim`, die Längengrenze bleibt 2000 Zeichen.

Betroffen sind alle drei Profile, die eine Notizspalte lesen: `export_csv_v1`
(`notizen`), `export_excel_v1` und das GuteSeele-Layout (je `Notizen`).
Diensttag-Notizen kommen über keinen Import zurück und waren nie betroffen.

### Web — „Nicht abgeschlossen" überstand den CSV-Rückimport nicht (Backlog Nr. 28)

`api/import_commit.php` schrieb `final` im INSERT als **Literal 1**, und das
UPDATE fasste die Spalte gar nicht an. Ein leeres Ende fiel auf den Beginn
zurück. Der einzige nicht abgeschlossene Einsatz des Referenzdatensatzes kam
deshalb als abgeschlossen zurück — im Überschreiben-Modus auch dann, wenn er im
Bestand richtig stand (Fund F-P1-M). Das trifft dieselbe Zusage wie Nr. 27:
`Export-Format.md` nennt `export_csv_v1` **verlustfrei**.

**Beides ist Zustand, nicht Entstehung**, und kommt jetzt aus der Datei — in
INSERT und UPDATE. Anders als `herkunft` und `edited` sagen `final` und `ende`
nichts über das Quellkonto aus, sondern über den Einsatz.

Der Punkt dabei ist eine Unterscheidung, die es vorher nicht gab: **eine
fehlende Spalte ist etwas anderes als eine leere Zelle.** Fehlt die Spalte im
Profil (Jahresliste, Excel), bleibt es beim bisherigen Verhalten — Ende =
Beginn, beim Anlegen `final = 1`, beim Überschreiben `final` unangetastet. Ist
die Zelle leer, ist das eine Aussage: Ende offen. Der Browser sendet
`ended_utc` und `final` deshalb nur noch, wenn das Profil die Spalte führt
(`import_ui.js`); `api/import_commit.php` unterscheidet „Feld fehlt" von „Feld
ist null". Das Profil `export_csv_v1` übernimmt `final` (stand bis hierher auf
`target: null`).

Damit steht der CSV-Kreislauf auf **0 unerklärten Abweichungen** (vorher 6:
vier Notizen, `final`, `ende`).

### Dokumentation — Die Ausnahmeliste des CSV-Rückwegs war unvollständig (Backlog Nr. 24 und 29)

`Export-Format.md` 5.1 nannte `export_csv_v1` verlustfrei und zählte **drei**
bewusste Ausnahmen auf. Gemessen sind es **sechs**. Die drei fehlenden:

- **Ruhesegmente kommen nicht zurück** (95 → 0). Es gibt keinen Importweg für
  sie; das Profil liest `einsaetze.csv`.
- **Der zweite Dienst eines Kalendertags geht verloren** (15 → 13 Diensttage;
  die zweite fehlende Zeile ist ein Diensttag ohne Einsatz). `gruppiere()`
  bündelt nach Kalendertag, obwohl seit Web 6.0.0 zwei Dienste an einem Datum
  zulässig sind.
- **Der Formelschutz-Apostroph bleibt im Wert stehen** (3 Zellen). Der Export
  stellt ihn Textwerten voran, die mit `=`, `+`, `-`, `@` beginnen; der Import
  entfernt ihn nicht. Nach einem Umlauf steht er im Bestand.

Die dritte ist die unauffälligste, denn ein Kreislauftest **sieht sie nicht**:
Der nächste Export fügt keinen zweiten Apostroph hinzu (`'` ist kein
Formel-Anfangszeichen), die Datei sieht unverändert aus — während der
gespeicherte Wert ein Zeichen länger geworden ist. Sie wird bewusst **nicht
behoben**: Ein Import, der einen führenden Apostroph entfernt, schafft den
nächsten stillen Verlust, denn ein echtes `'` am Textanfang verschwände.

Die Überschrift heißt jetzt „verlustfrei **für Einsätze**". Sechs Ausnahmen
sind kein Grund, das Wort zu streichen — aber einer, es einzugrenzen.

Dazu in Abschnitt 6: **Der Papierkorb ist in keinem Exportprofil enthalten**,
und seit dieser Fassung ist das ein Unterschied zur Sicherung, die ihn führt.
Wer gelöschte Einträge erhalten will, nimmt das Backup.

### Werkzeug — Das Vergleichswerkzeug sah an zwei Stellen weg

Nicht ausgeliefert (`tools/referenzdatensatz/`), aber Teil derselben Änderung:
Ein Kreislauftest ist nur so viel wert wie das, was er anschaut.

**`missions[].created_at` wird nicht mehr wegnormalisiert.** Es war als
flüchtiger Anteil eingetragen — und genau das hatte den Verlust jahrelang
verdeckt: Der Kreislauf sah ihn nicht, weil das Werkzeug wegsah. Jetzt wird der
Wert verglichen. Die Kopfangabe `created_at` der Datei (Zeitpunkt des Exports)
bleibt normalisiert; sie ist tatsächlich flüchtig.

**`deleted_at` wird normalisiert, aber nicht weggenommen.** Der Zeitpunkt
entsteht beim Einspielen neu und kann nicht überleben; die Unterscheidung
leer/gesetzt **muss** überleben, denn „Papierkorbeintrag kommt als
Papierkorbeintrag zurück" ist die Aussage, die der Kreislauf belegen soll. Ein
gesetzter Wert wird durch die Zeitmarke ersetzt, ein leerer bleibt leer. Eine
*Ausnahmeregel* dafür wäre ein Filter gewesen — sie hätte die Aussage
mitweggenommen.

Die Probe aufs Exempel (`--testabweichung`) hat für die Sicherung zwei neue
Paare: Papierkorb-Zustand geändert → muss gemeldet werden, Löschzeitpunkte
verschoben → darf nicht; `created_at` eines Einsatzes geändert → muss gemeldet
werden (bis hierher eine Gegenprobe mit umgekehrter Erwartung), Kopfangabe
geändert → darf nicht. **12/12 für die Sicherung, 10/10 für CSV.**

Die Ausnahmelisten tragen neu gemessene Zahlen. Für die behobenen Fehler Nr. 27
und Nr. 28 sind **keine** Regeln hinzugekommen — behebbare Abweichungen gehören
nicht in eine Ausnahmeliste, sondern behoben.

### Wer nachbessern muss

**Niemand — aber zwei Dinge sind zu wissen.**

Eine Sicherung, die vor dieser Fassung erstellt wurde, enthält den Papierkorb
nicht und kann ihn deshalb auch nicht zurückbringen. Wer gelöschte Einträge in
einer alten Sicherung vermisst: Sie waren nie darin. Erst nach diesem Update
erstellte Sicherungen führen sie.

Umgekehrt: Wer eine Sicherung **aus** dieser Fassung in eine **ältere**
Installation einspielt, bekommt ihren Papierkorb dort als **aktiven Bestand**
zurück. Die Annahmeschranke sitzt seit Web 6.0.0 bei Nutzlast 6 und lässt eine
Version-7-Datei durch; nachträglich lässt sich daran nichts ändern. Wer das
tut, sieht anschließend in der Tagesübersicht nach.

### Geprüft

Beide Kreisläufe gegen den kanonischen Referenzdatensatz, der dafür neu
gezogen wurde (87 Einsätze / 5 im Papierkorb, 100 Ruhesegmente / 5,
16 Diensttage / 1, 55 861 Spurpunkte):

| | vor S1 | mit 8.0.0 |
|---|---|---|
| Sicherung: Einzelvergleiche | 269 439 | **286 739** |
| Sicherung: erwartete Abweichungen | 15 | 16 |
| Sicherung: **unerklärte** | 0 | **0** |
| CSV: Einzelvergleiche | 8 797 | 8 797 |
| CSV: erwartete Abweichungen | 858 | 859 |
| CSV: **unerklärte** | **6** | **0** |

Der Sicherungs-Kreislauf stand vorher schon auf null — aber er verglich
weniger: Der Papierkorb war gar nicht in der Datei. Die 17 300 zusätzlichen
Einzelvergleiche sind der Zuwachs.

Dazu: Probe aufs Exempel **12/12** (Sicherung, vier Proben neu) und **10/10**
(CSV); die Invariante `deleted_with_day` über alle Konten der Prüfinstallation
**0 Verstöße**; `created_at` nach dem Umlauf **87 von 87 wörtlich gleich**;
Demo-Abnahme **24 Einzelprüfungen, 0 Befunde, 0 Konsolenfehler**;
Angriffswerte-Regression **42 Einzelprüfungen, 0 Befunde**.

Dazu zwei neue Prüfmittel für die Fälle, die der Kreislauf nicht herstellen
kann — sein Referenzbestand hat keinen Diensttag mit beiden Löscharten
nebeneinander:

- `tools/wiederherstellungs-probe/` misst sie in der Datenbank:
  **30 Erwartungen, 0 nicht erfüllt** über vier Teile (Papierkorb aus der
  Datei, kaputte Datei, die drei Wege zum halb sichtbaren Einsatz, die
  Wiedererkennung). Gegen den Stand vor der Korrektur fallen **11 von 30**
  durch, gegen den Stand vor der Nachlese **12 von 16** der damaligen
  Erwartungen.
- `browser/papierkorb_misch.mjs` misst dieselben Fälle durch den Browser —
  löschen, sichern, in ein leeres Konto einspielen, Papierkorbseite lesen,
  Zurückholen versuchen, Diensttag wiederherstellen: **14 Einzelprüfungen, 0
  Befunde, 0 Konsolenfehler**. Gegen den Stand vor der Korrektur **4 Befunde**;
  gegen den davor **2**, darunter: Das Zielkonto zeigte 1 statt 3 einzeln
  gelöschte Einsätze, und der gelöschte Diensttag nannte 6 Einsätze statt 5 —
  der einzeln gelöschte war in ihm verschwunden.

Was **nicht** geprüft werden konnte, steht in
`docs/Pruefdokument-S1-Sicherung-Import.md`, Abschnitt 1 — allen voran der
Absatz „Wer nachbessern muss" oben: Dass ein älterer Stand eine v7-Datei
annimmt, ist weder geprüft noch prüfbar.

## [Web 7.3.1] — 2026-08-23

**Einsätze nach Mitternacht landeten beim CSV-Rückimport 24 Stunden zu früh.**
Gefunden im Kreislauftest der Phase P1 (Fund F-P1-K). Keine Migration.

### Was passiert ist

Ein Dienst läuft über Mitternacht; ein Einsatz um 01:38 gehört zum Folgetag.
Die Exportdatei sagt das auch — sie führt `diensttag` (2026-03-28) **und**
`datum` (2026-03-29) getrennt, und `Export-Format.md` beschreibt den
Unterschied ausdrücklich. Der Import las nur die erste Spalte:

    pruef_ortszeit_zu_utc($tag, $hhmm, 0, 'started_local', $pruef)

`$tag` ist der Diensttag, `addDays` ist `0`. Damit wurde 01:38 auf den
28. gerechnet — der Einsatz lag danach **vor** dem Beginn des Dienstes, zu dem
er gehört. Das Formular macht es seit Web 7.0.0 richtig (`einsatz_form.php`,
Abschnitt „TAGESWECHSEL"): Liegt die erste Phase vor dem Dienstbeginn, kann
der Einsatz nur zum Folgetag gehören.

Die Angabe, die den Fehler behebt, lag die ganze Zeit in der Datei. Die Spalte
`datum` war in `assets/import_profiles.js` auf `target: null` gesetzt, mit
einem Kommentar, der es begründete: zwei Quellen für dieselbe Zuordnung wären
eine zu viel, „die Uhrzeit rechnet die Mitternachtslogik ohnehin dem Folgetag
zu". Der zweite Halbsatz war schlicht falsch — und er stand an genau der
Stelle, an der die Entscheidung fiel. Ein irreführender Kommentar ist an einer
solchen Stelle schlimmer als gar keiner: Er beantwortet die Frage, die man
sonst am Code geprüft hätte.

Das trifft eine ausdrückliche Zusage: `Export-Format.md` 5.1 nennt
`export_csv_v1` verlustfrei. Hier ging nichts verloren, es wurde etwas
**verändert** — der Einsatz ist nach dem Rückimport am falschen Tag
dokumentiert, ohne Hinweis in Datei, Prüftabelle oder Bilanz. Betroffen war
ausschließlich das Profil `export_csv_v1`; die Jahreslisten-Profile führen
keine getrennten Datumsspalten.

### Was geändert wurde

Die Spalte `datum` wird ausgewertet und als `date_local` mitgesendet;
`api/import_commit.php` nimmt sie als **Bezugstag der Alarmzeit**. Für die
Gruppierung bleibt es beim Diensttag: `day_id` hängt an ihm, nicht am
Einsatzdatum. Zwei Quellen für zwei verschiedene Aufgaben — der alte Kommentar
hatte recht darin, dass zwei Quellen für *dieselbe* Aufgabe ein Fehler wären.

Dazu eine Plausibilitätsschranke: Übernommen wird das Datum nur, wenn es der
Diensttag selbst ist oder der Tag darauf. Mehr kann es nicht sein — die
Anwendung kennt für den Tageswechsel genau einen Schritt (`local_to_utc` mit
`addDays` 0 oder 1, so auch im Formular). Eine Datei fremder Herkunft mit
unsinnigem `datum` verstreut damit keine Einsätze über den Kalender, sondern
fällt auf das bisherige Verhalten zurück. Eine Datei **ohne** die Spalte
ebenso.

Bewusst **nicht** übernommen wurde der zweite denkbare Weg, die Formularregel
(Uhrzeit vor Dienstbeginn heißt Folgetag). Sie braucht den Dienstbeginn — und
beim Import in ein leeres Konto entsteht der Diensttag erst aus den Einsätzen;
zum Zeitpunkt der Entscheidung steht er noch nicht fest. Die Datei weiß es
besser als jede Vermutung.

### Wer nachbessern muss

Wer eine CSV-Datei mit einem Dienst über Mitternacht zurückgespielt hat, hat
die betroffenen Einsätze 24 Stunden zu früh im Bestand — sie stehen dann unter
dem Vortag und liegen zeitlich vor dem Dienstbeginn. Ein erneuter Import
derselben Datei legt sie **neu** an, statt sie zu berichtigen: Die
Dublettenerkennung greift über die Einsatznummer beziehungsweise über Tag und
Alarmzeit, und beide sehen jetzt einen anderen Tag. Der saubere Weg ist, die
falsch liegenden Einsätze zu löschen und danach neu einzulesen. Betroffen sind
nur Einsätze, deren Alarmzeit nach Mitternacht liegt.

### Geprüft

Kreislauf CSV-Archiv → frisches Konto → CSV-Archiv, gegen den kanonischen
Referenzdatensatz (82 Einsätze, 15 Diensttage, 95 Ruhesegmente, 171 GPX):

| | bis 7.3.0 | mit 7.3.1 |
|---|---|---|
| Einzelvergleiche | 8 617 | 8 797 |
| erwartete Abweichungen | 844 | 858 |
| **unerklärte Abweichungen** | **9** | **6** |

Die vier Meldungen zu F-P1-K (je zweimal *fehlt* und *zusätzlich* für die
beiden Einsätze um 01:38 und 01:32) sind verschwunden; beide Einsätze werden
jetzt überhaupt erst verglichen — daher die um 180 gestiegene Zahl der
Einzelvergleiche. Der Einsatz vom 25.10. stimmt danach in **allen** Feldern
überein.

Nebenbefund: Von den sechs verbliebenen Abweichungen ist eine vorher gar nicht
sichtbar gewesen. F-P1-L (mehrzeilige Notizen verlieren ihre Zeilenumbrüche,
Backlog Nr. 27) war mit 3 Fällen gemessen; es sind **4**. Der vierte hing an
einem Einsatz, den F-P1-K aus dem Vergleich gehoben hatte — ein Fehler hatte
die Messung eines zweiten verdeckt. Alle vier verlieren genau einen Umbruch
bei unveränderter Zeichenzahl (164/253/119/150).

Ebenfalls beim Nachmessen aufgefallen: Drei Regeln der Ausnahmeliste
(`crew_p2` in Einsätzen, Tagesbesatzung und Diensttagen) haben nie gegriffen.
Sie waren in P1/B5 aus der Analogie zu `crew_p1` geschrieben, nicht gemessen —
die einzigen Regeln der Liste ohne Zahl in der Begründung. Der Grund ist
einfach: Alle sieben Zeilen mit belegtem `crew_p2` gehören zum Diensttag
2026-02-08, dessen Rettungsmittel dasselbe ist wie das auf der Importseite
gewählte; die Besatzung kommt unverändert zurück. Die drei Regeln sind
entfernt, der Kreislauf meldet jetzt keine ungenutzte Regel mehr.

### Ebenfalls in dieser Version: eine berichtigte Wegangabe

Beim Zusammenführen der beiden Arbeitslinien ist ein **Sachfehler in der
Beschreibung des Sofortpakets 7.2.1** aufgefallen. Er betrifft nicht die
Korrektur selbst — die ist richtig und wirkt —, sondern die Angabe, **wie** der
Angriffswert in das Altersfeld gelangt.

Dort stand: über den **Import**; `assets/import.js` übernehme `pat.age` als
rohen Zellenwert. Das trifft nicht zu. `import_profiles.js` bildet die Spalte
`pat_alter` mit `parse: ['alterJahre']` ab, und `PARSERS.ganzzahl` verlangt
`/^-?\d+$/`. Nachgemessen an neun Fällen: `47`, `0`, leer und `  12 ` kommen
durch; `<img src=x onerror=…>`, `47<img …>` und `<b>47</b>` werden **verworfen**
(„Alter: ganze Zahl erwartet"). Die Angabe stammt vermutlich aus dem Kommentar
bei der Excel-Spalte `Alter`, der die CSV-Spalte `pat_alter` als die nennt, die
„den Rohwert führt" — gemeint ist dort der *gespeicherte* Wert im Gegensatz zum
gerechneten, nicht ein ungeprüfter.

Der Weg hinein ist ein anderer, und er ist der unangenehmere: Das Feld `age`
liegt im `pat_blob`, freiem JSON, das der Server nie im Klartext sieht. Hinein
kommt es über die **Wiederherstellung einer Sicherung** — im Adminbereich sogar
die einer *fremden* — oder über jeden Zugang, der den Inhaltsschlüssel besitzt
und die Oberfläche umgeht. Genau deshalb lässt sich die Lücke serverseitig
grundsätzlich nicht wegprüfen, und genau deshalb war die Korrektur richtig.

Berichtigt an fünf Stellen: `docs/CHANGELOG.md` (Eintrag 7.2.1),
`server/version.php`, `server/assets/missiontable.js` (Kommentar),
`docs/Backlog.md` (Nr. 22) und `docs/Pruefung-Sofortpaket-22.md`.

**Am schwersten wog die letzte.** Der Prüflistenpunkt P-1 dort führte über den
CSV-Import und erwartete, dass der Angriffswert danach als Text in der Zelle
steht. Das kann er nicht — der Import verwirft ihn, das Feld bleibt leer, und
das Scheiternsmerkmal „ein leeres Feld ist auch ein Fehler" hätte bei
**korrektem** Verhalten angeschlagen. Wer die Liste abgehakt hätte, hätte einen
Fehler gemeldet, wo keiner ist. P-1 führt jetzt über das Demo-Konto, das einen
solchen Wert im Altersfeld mitbringt (21.11.2026, 09:21).

Dazu Kleinigkeiten aus demselben Durchgang: `README.md` führte ein
`docs/archiv/` auf, das es nicht gibt, und weder `Branding.md` noch
`Pruefung-Sofortpaket-22.md`; `Technik.md` nannte im Verzeichnisbaum eine
„Review-Umsetzung", die es ebenfalls nicht mehr gibt. Eingetragen und
ausgetragen.

## [Web 7.3.0] — 2026-08-23

**Ein Demo-Konto.** Adresse `demo@gen-em.org`, Passwort `nadokudemo0815`,
Daten frei erfunden, Änderungen erwünscht — und alle 30 Minuten wieder auf den
Ausgangsstand. Neue Funktion, **keine Migration**: `app_state` liegt seit
jeher, und der Bestand entsteht über die vorhandene Einspielroutine.

### Wozu

Wer die Anwendung ansehen will, brauchte bisher ein Konto, eine Einladung und
eigene Daten. Das ist eine hohe Schwelle für die Frage „wie sieht das
eigentlich aus?". Das Demo-Konto beantwortet sie ohne Vorbereitung — und
zwar mit einem Bestand, in dem **jede** Funktion vorkommt: Luft- und
Bodeneinsätze, Winde, Bergwacht, Reanimationen, ein Dienst über Mitternacht,
ein Diensttag ohne Einsatz, ein gefüllter Papierkorb.

Der Datensatz stammt aus Phase P1 und ist derselbe, gegen den die
Regressionsläufe vergleichen. Das ist kein Zufall, sondern der Grund, warum
es ihn gibt: Ein Beispielbestand, den niemand prüft, veraltet.

### Die Ausnahme, die dafür gemacht wird — und ihre Grenze

Das Projekt verspricht Ende-zu-Ende-Verschlüsselung: Der Server sieht die
geschützten Angaben nie im Klartext, und das Schlüsselmaterial hängt am
Passwort. **Für dieses eine Konto gilt das nicht.** Sein Schlüsselmaterial
liegt in einer Fixture auf dem Server, denn sonst könnte eine Rücksetzung die
Chiffretexte nicht wieder lesbar machen.

Das ist eine bewusste, eng gezogene Ausnahme und nur unter vier Bedingungen
vertretbar — alle vier werden **erzwungen**, nicht bloß zugesichert:

1. Das Konto trägt ausschließlich erfundene Daten.
2. Es hat die Rolle `user`. `demo_lib.php` schreibt sie bei jedem Anlegen und
   jedem Reset fest hin, statt sie zu übernehmen.
3. Jede Funktion arbeitet auf der Kennung aus `app_state.demo_user_id` und
   nimmt **keine** von außen entgegen. Sie kann kein anderes Konto treffen,
   auch nicht bei falschem Aufruf.
4. Zugangsdaten und Geräteschlüssel sind ohnehin öffentlich — es gibt nichts
   zu schützen, was nicht schon offenläge.

Ein Banner im Konto, ein Warnhinweis auf der Adminseite und ein Abschnitt im
Handbuch sagen dasselbe: **niemals echte Daten darin erfassen.**

### Kein zweiter Einspielweg

Der Bestand wird über `edbak_restore()` hergestellt — dieselbe Routine wie bei
der Wiederherstellung einer Sicherung, mit derselben Prüfung. Ein eigener Weg
hätte eigene Fehler, und ausgerechnet der Weg, der am häufigsten läuft, wäre
der ungeprüftere.

Zwei kleine Erweiterungen waren dafür nötig, beide in `backup_lib.php`:

- **`edbak_build()` kann den Papierkorb mitnehmen** (`$mitPapierkorb`). Die
  Fixture soll den Referenzzustand vollständig abbilden. Für eine
  Nutzer-Sicherung bleibt der Filter — wer sichert, sichert seinen Bestand,
  nicht seinen Abfall.
- **`edbak_restore()` ist verschachtelungsfähig.** Sie öffnet ihre Transaktion
  nur, wenn noch keine läuft. Der Demo-Reset muss mehr in dieselbe Klammer
  nehmen: Kontomaterial, Geräte, Bestand und Papierkorb-Nachlauf. Zerfiele das
  in mehrere Transaktionen, könnte ein Fehler in der Mitte ein Konto mit
  halbem Bestand hinterlassen — und der Reset läuft unbeaufsichtigt.

Beide Punkte kamen nicht aus dem Konzept, sondern aus dem ersten Anlauf: Der
warf „There is already an active transaction" und ließ danach einen leeren
Papierkorb zurück.

### Die Fixture

`server/demo/fixture.json.gz` — Konto- und Schlüsselmaterial, Geräte, der
Bestand als inneres Backup-JSON und ein Nachlauf-Drehbuch. Erzeugt von
`tools/referenzdatensatz/fixture/erzeugen.php`.

**Sie kann nicht aus einer `.edbak` kommen.** Die Sicherungsdatei trägt die
geschützten Angaben im *Klartext* — der Browser entschlüsselt vor dem
Versiegeln, damit sich eine Sicherung in jedes Konto einspielen lässt. Die
Fixture braucht das Gegenteil: den Chiffretext unverändert, daneben das
Schlüsselmaterial. Erst dadurch kann der Server das Konto **ohne jede
Entschlüsselung** zurücksetzen, und erst dadurch ist der Reset schnell genug,
um bei jeder Anfrage zu laufen. Der Erzeuger bricht ab, wenn er Klartext
findet.

Gepackt abgelegt: roh 2,3 MB, im Wesentlichen 52 484 Spurpunkte als
JSON-Zahlen; gepackt knapp 700 KB. Die Datei liegt unter `server/` und geht
bei jedem Deploy mit.

### Der Reset

Anfragegetrieben nach dem Muster der Tageswartung, mit einem Unterschied in
der Reihenfolge: **zuerst zurücksetzen, dann antworten.** Wer nach längerer
Ruhe kommt, sieht den Ausgangsstand und nicht die Hinterlassenschaft der
letzten Besucherin. Zwei Auslösepunkte — `auth_guard.php` für Web-Anfragen
und `ingest.php` für Uploads, dort **nach** der Geräteprüfung, damit die
Rücksetzung kein Hebel für jeden ist, der die Adresse kennt.

Der Reset überschreibt auch Konto- und Schlüsselmaterial und zählt
`session_epoch` hoch. Damit bliebe selbst eine unerwartet gelungene Änderung
der Konto-Identität folgenlos.

Zum Schluss legt das Nachlauf-Drehbuch benannte Einsätze und Diensttage über
die **regulären** Löschwege in den Papierkorb — sonst wäre er nach jedem Reset
leer, denn das Einspielen wertet `deleted_at` nicht aus. Die Diensttage werden
über ihre Dienstkennung angesprochen, nicht über das Datum: Seit E9 können
zwei Dienste auf einem Kalendertag liegen.

### Gesperrt ist ausschließlich die Identität

E-Mail-Änderung und Passwortänderung (`einstellungen.php`) werden mit einem
freundlichen Hinweis abgewiesen; `api/kdf_upgrade.php` antwortet mit stillem
Erfolg, weil der Browser es von sich aus aufruft und ein Fehler dort als
Störung stünde, wo es keine gibt; `reset_request.php` weist die Demo-Adresse
**still** ab — die Antwort dieser Seite ist für jede Adresse dieselbe, und
eine Sondermeldung wäre die einzige Stelle, an der sie verriete, welche
Adressen es gibt.

Alles Übrige bleibt offen, ausdrücklich auch Geräteverwaltung, Kopplung und
Uploads.

**Warum überhaupt sperren, wenn der Reset ohnehin alles zurückholt?** Weil
zwischen zwei Rücksetzungen bis zu dreißig Minuten liegen. Wer in dieser Zeit
das Passwort ändert, sperrt die nächste Besucherin aus — und die findet ein
Konto vor, dessen öffentliche Zugangsdaten nicht mehr stimmen, ohne zu
erfahren warum.

### Mengenbremse

Zwei neue Töpfe in `ratelimit_lib.php`, die **anders zählen** als die vier
bestehenden: nicht Fehlversuche, sondern gelungene Anmeldungen. `demo` fasst
20 je Stunde und IP-Adresse, `demog` 300 je Stunde insgesamt.

Ein Fehlversuchszähler liefe hier nie an — die Zugangsdaten sind öffentlich,
es gibt nichts zu erraten. Begrenzt werden soll die Menge der Nutzung: Das
Konto ist zum Ausprobieren da, nicht als Rechenzeit für Fremde. Die Prüfung
sitzt vor der teuren Ableitung, wie jede Bremse dort.

## [Web 7.2.3] — 2026-08-23

**Zwei Formatbeschreibungen sagten etwas anderes, als der Code tut.** Beides
beim Aufbau des Referenzdatensatzes aufgefallen (Phase P1, Paket B5), beides
gefunden, weil ein Werkzeug die Dateien gegen ihre Beschreibung gehalten hat.
Keine Migration, kein Datenmodell.

### `LIESMICH.txt` nannte eine Spalte, die es nicht gibt

Der CSV-Export legt seinem Archiv eine `LIESMICH.txt` bei, die das Format
erklärt. Darin stand weiterhin:

> hubschrauber, standort und die Tagesbesatzung stehen sowohl in
> einsaetze.csv als auch in diensttage.csv …

Die Spalte heißt seit Web 5.10.0 `rettungsmittel` — `hubschrauber` kommt in
keiner erzeugten Datei mehr vor. `docs/Export-Format.md` war bei der
Umbenennung mitgezogen worden, die ausgelieferte Datei nicht. Ausgerechnet die
Datei, deren einziger Zweck die Formatbeschreibung ist, beschrieb das Format
falsch: Wer sich danach richtet, sucht eine Spalte, die nicht da ist.

Der Anlass, es jetzt zu ändern, ist nicht die Größe des Fehlers, sondern der
Zeitpunkt. Die Referenz-Exporte der Phase P1 werden eingecheckt und sind ab
dann die Vergleichsgrundlage jedes Regressionslaufs. Ein falscher Satz darin
wäre nicht nur falsch, sondern **festgeschrieben**.

### `days[].id` stand unter „nicht in der Datei" — und steht doch darin

`docs/Backup-Format.md` 4 führte `id` neben `user_id` und `device_id` unter
den internen Verweisen, die eine Sicherung nicht enthält. Für `missions` und
`rest_segments` stimmt das. Für `days` nicht: Die Kennung steht in jeder
Sicherung, und sie **muss** darin stehen — `missions[].day_id` und
`rest_segments[].day_id` verweisen darauf. Ohne sie ließe sich nach dem
Einspielen nicht mehr sagen, welcher Einsatz zu welchem Dienst gehörte.

Das Beispiel im selben Dokument zeigte den Schlüssel ebenfalls nicht. Beides
ist ergänzt, mit der Klarstellung, worum es sich handelt: eine Kennung
*innerhalb dieser Datei*, keine Aussage über die Datenbank — beim Einspielen
wird sie auf die neu vergebene umgeschrieben.

### Vier Bereiche fehlten in „Was NICHT in der Datei steht"

Abschnitt 4 von `docs/Backup-Format.md` beansprucht seit Web 4.5.2
ausdrücklich, **aufzählend** zu sein: Das Format ist eine Entscheidung, keine
Nebenwirkung des Datenbankschemas. Der Abschnitt zählte Spalten auf und ließ
vier ganze Bereiche aus, die eine Wiederherstellung nicht zurückbringt.
Gemessen am Referenzdatensatz der Phase P1 (82 Einsätze):

| Was | vorher | nach dem Umlauf |
|---|---:|---:|
| Papierkorb — Einsätze / Ruhesegmente / Diensttage | 5 / 5 / 1 | 0 / 0 / 0 |
| Geräte | 3 | 0 |
| `created_at` der Einsätze (verschiedene Werte) | 79 | 5 |
| Kopplungscodes, Sperrliste (`deleted_refs`) | — | leer |

Der Papierkorb wiegt am schwersten: Eine Wiederherstellung in ein frisches
Konto leert ihn **endgültig**, und wer die Sicherung für vollständig hält,
verliert die Daten im Vertrauen auf eine Zusage, die niemand gegeben hat.
Beim Gerät ist das Fehlen dagegen richtig — es trägt einen API-Schlüssel, und
ein mitgesichertes Gerät wäre ein mitgesicherter Zugang. Nur stand nirgends,
dass danach jede Uhr neu zu koppeln ist.

`created_at` ist der unangenehmste Fall: Es **wird** gesichert und beim
Einspielen nicht geschrieben. Der Abschnitt führte `site_ele_m` als die
einzige Asymmetrie dieser Art. Ob das Feld künftig mitgeschrieben oder aus
der Sicherung gestrichen wird, ist offen — Backlog Nr. 25. Geändert wurde
hier nur die Beschreibung, nicht das Verhalten.

### Geprüft

Der Referenz-Export wurde nach der Änderung neu erzeugt und mit dem
Vergleichswerkzeug (`tools/referenzdatensatz/vergleich/`) gegen den Stand
davor gehalten: **9 589 Einzelvergleiche, genau eine Abweichung** — die Zeile
mit dem Spaltennamen in `LIESMICH.txt`.

Die Angaben zu Abschnitt 4 stammen aus einem tatsächlich gefahrenen Umlauf
(Sicherung → frisches Konto → Sicherung): 269 439 Einzelvergleiche, 15
erwartete Abweichungen (`days[].refs[].device_id` wird `null`), keine
unerklärte. Die vier fehlenden Bereiche zeigt dieser Vergleich **nicht** — sie
fehlen in beiden Dateien — und wurden deshalb getrennt in der Datenbank
gezählt. `days[].id` wurde gegen eine erzeugte Sicherung geprüft: vorhanden,
ganze Zahl.

## [Web 7.2.2] — 2026-08-23

**Ein stiller Datenverlust im CSV-Rückimport.** Aufgefallen beim Aufbau des
Referenzdatensatzes (Phase P1): Ein Einsatz, der über `import.php` aus einer
`einsaetze.csv` eingelesen wurde, kam ohne Transportart, ohne NA-Begleitung,
ohne Fehleinsatz-Kennzeichen, ohne Zielklinik-Koordinate und ohne
Abfahrtortregel im Bestand an. Keine Migration.

### Was passiert ist

`assets/import.js` führt zwei Feldlisten. `EINFACHE_ZIELE` sagt, welche Werte
beim Lesen der Datei unverändert nach `zeile.mission` wandern; `UEBERNAHME`
sagt, welche davon `gruppiere()` in das Objekt kopiert, aus dem
`import_ui.js` die Nutzlast für `api/import_commit.php` baut. Die zweite
Liste war eine von Hand geführte Abschrift der ersten — und bei der Etappe 2
(Web 6.1.0) war nur die erste ergänzt worden.

Die Folge ist der unangenehmste Zuschnitt, den ein solcher Fehler haben kann:
Die Werte werden korrekt gelesen, in der Prüftabelle korrekt **angezeigt**, die
Bilanz meldet „0 Fehler" — und danach fallen sechs Felder zwischen Anzeige und
Absenden heraus. Weder die Datei noch die Seite noch die Rückmeldung des
Servers deuten darauf hin. Betroffen war ausschließlich das Profil
`export_csv_v1`; das Excel-Profil kennt diese Spalten gar nicht.

Das trifft eine ausdrückliche Zusage: `docs/Export-Format.md` 5.1 nennt
`export_csv_v1` „verlustfrei" und zählt genau drei bewusste Ausnahmen auf
(`einsatz_id`, GPX-Dateien, Rettungsmittel/Standort) plus `herkunft` und
`edited`. Diese sechs Felder standen dort nicht — sie waren keine Entscheidung,
sondern ein Versehen.

### Was geändert wurde

`UEBERNAHME` wird nicht mehr abgeschrieben, sondern **abgeleitet**:

    var UEBERNAHME = EINFACHE_ZIELE
        .filter(function (f) { return f !== 'day' && f !== 'crew_override'; })
        .concat(['resources', 'phases', 'phasesLocal']);

Die vier Abweichungen sind an Ort und Stelle begründet: `day` ist der
Gruppenschlüssel und steht am Diensttag, `crew_override` wird bei
`explicitCrew` ausdrücklich gesetzt, und `resources`, `phases`, `phasesLocal`
sind Sonderfälle in `setzeZiel()` beziehungsweise `phasenFach()`. Ein neues
Feld gehört damit nur noch an eine Stelle.

Die einzelne Zeile hätte es auch getan. Sie wäre aber die dritte Abschrift
gewesen, die irgendwann wieder zurückbleibt — und der Fehler ist gerade
deshalb zwei Nebenversionen lang unbemerkt geblieben, weil nichts ihn zeigt.
Das entspricht dem Grundsatz „Feldkatalog statt Sonderfall" (CLAUDE.md 4).

### Wer nachbessern muss

Wer zwischen Web 6.1.0 und 7.2.1 eine CSV-Datei zurückgespielt hat, hat die
sechs Felder für die betroffenen Einsätze leer im Bestand. Ein erneuter Import
derselben Datei mit „überschreiben" trägt sie nach; die Felder stehen
außerhalb der COALESCE-Schranke von `api/import_commit.php`, werden also
tatsächlich gesetzt. Betroffen sind nur importierte Einsätze — der Weg über
die Uhr und das Formular schreibt diese Spalten seit jeher.

### Geprüft

Im Browser über `import.php` mit der Referenzdatei (vier Einsätze, 92 Spalten):
vorher fehlten in allen vier Zeilen `transport_mode` und `start_src`, in je
einer `na_escort` und `false_alarm`; nachher stimmen alle sechs Felder mit der
Datei überein. Zusätzlich prüft der Referenzdatensatz die Listendrift jetzt
dauerhaft maschinell (`tools/referenzdatensatz/generator/pruefen.py`, Prüfung 5):
Sie liest beide Listen aus `assets/import.js` und meldet jedes Feld, das
gelesen, aber nicht weitergereicht wird. Gegen den alten Stand gehalten meldet
sie genau die sechs Felder, gegen den neuen keines.
## [Web 7.2.1] — 2026-08-23

**Eine Sicherheitskorrektur, sonst nichts.** Sofortpaket zu Backlog Nr. 22,
vorgezogen vor Phase P1. Kein neues Feld, kein Datenmodell, **keine
Migration**, keine Handlung der Betreiberin außer dem Deploy.

Die Lücke bestand **seit Web 5.2.0** — seit die gemeinsame Einsatztabelle
(`assets/missiontable.js`) eingeführt wurde. Die Aufräumrunde P0 hat sie weder
verursacht noch verschärft; sie hat sie **gefunden** (Befund F-20).

### Das Alter ging unmaskiert in die Einsatztabellen

`zelleGeschuetzt()` maskierte Einsatzort und Diagnose über `esc()`, das Alter
aber nicht — dort stand `v => v`, weil ein Alter eine Zahl ist. Über das
Einsatzformular ist es das auch: `einsatz_form.php` schickt es durch
`parseInt()`. Das **Feld** ist es nicht: `age` liegt im `pat_blob`, und der ist
freies JSON.

Und die Zelle wird per `innerHTML` gesetzt. Markup darin führte Skript aus — in
genau dem Fenster, in dem der entschlüsselte Inhaltsschlüssel liegt. Der Server konnte davon nichts sehen: Er
bekommt nur Chiffretext, prüfen kann er ihn nicht. Das ist der Preis der
Ende-zu-Ende-Verschlüsselung, und er verlangt, dass der Browser seine Seite
hält.

**Maskiert wird jetzt in `zelleGeschuetzt()` selbst**, nicht mehr an der
Aufrufstelle. Der Grund für diese Wahl steckt im Fehler: Die Entscheidung, ob
eine Angabe maskiert wird, war an zwei von sechs Aufrufstellen falsch
getroffen — und die nächste neue Spalte hätte sie ein siebtes Mal treffen
müssen. `formatiere` bekommt den Wert jetzt bereits maskiert und darf ihn nur
noch umschichten. Damit sind alle drei Einsatztabellen — Tagesübersicht, Suche,
Zeitraum-Übersicht — an **einer** Stelle abgesichert.

**Für gültige Eingaben ändert sich nichts.** Das ist nicht behauptet, sondern
gemessen: Das Zellen-HTML ist für `47`, für den leeren Wert (Gedankenstrich),
für `0` und für den nicht lesbaren Fall (Warnzeichen) zeichengleich zum Stand
7.2.0. Der Angriffswert wurde vorher als Markup ausgeführt und erscheint jetzt
als Text (`tools/maskierungs-probe/`, Chromium).

### Der ganze Importpfad ist durchgesehen — ein Fund, sonst keiner

32 Ausgabestellen mit `innerHTML` und Verwandtem, in 23 eigenen Skriptdateien
und allen Seiten unter `server/`. Weitere Senken (`srcdoc`, `eval`,
`new Function`, `createContextualFragment`) kommen im Projekt nicht vor.

Die Abgleichs-/Vorschauansicht des Imports hält: Der rohe Zellenwert der Datei
steht dort in einer **Attributposition** (`<input value="…">`) und geht durch
`esc()`. Dass das trägt, ist keine Selbstverständlichkeit, sondern das Verdienst
der Zusammenführung aus Web 4.6.0 — `EdHtml.escape` maskiert fünf Zeichen, also
auch beide Anführungszeichen. Die früheren verstreuten Fassungen mit drei
Zeichen hätten hier nicht gereicht. Die vollständige Liste der geprüften
Stellen steht in `docs/Pruefung-Sofortpaket-22.md`, damit die Aussage „keine
weiteren Funde" nachprüfbar bleibt und nicht geglaubt werden muss.

### Der neue Datenschlüssel blieb nach dem Abmelden liegen

Nachgegangen wurde der Frage, ob die Keyguard-Einträge `pckb`/`pckt` beim
Abmelden geräumt werden müssen. **Sie müssen nicht:** `pckb` ist ein gekürzter
SHA-256 über die Schlüssel*hülle* — und die ist kein Geheimnis, der Server
schreibt sie jeder Seite mit; `pckt` ist ein Zeitstempel. Kein
Schlüsselmaterial, nichts davon Ableitbares. Sie bleiben deshalb bewusst
liegen, und die toten Exporte `EdKeyGuard.beenden()`/`raeumen()` bleiben
unangetastet (Backlog Nr. 21).

Die Frage hat aber etwas anderes ans Licht gebracht. `einstellungen.php` legt
beim Passwortwechsel den **neuen Datenschlüssel** unter `edk_neu` im
`sessionStorage` ab und löst das Fach beim nächsten Aufruf desselben Reiters
wieder auf. Kommt dieser Aufruf nie — die Übertragung bricht ab, die Nutzerin
geht zurück oder meldet sich ab —, blieb ein vollwertiger Datenschlüssel liegen,
und zwar über das Abmelden hinaus: `EdCrypto.clearSession()` kannte nur `edk`,
`pck` und `edkvor`. Das ist ein echter Schlüsselrest, und er widerspricht der
Zusage, dass nach dem Abmelden keiner bleibt.

Behoben mit **einer Zeile** in `clearSession()`. Auf dem auflösenden Weg ändert
sie nichts: Dort wird `edk_neu` ausgelesen und entfernt, bevor `clearSession()`
läuft. Belegt in Chromium: Alle sechs Fächer belegt, dann der Abmeldeweg
darüber — vorher blieb `edk_neu` übrig, jetzt nur noch `pckb` und `pckt`
(`tools/abmelde-probe/`).

### Berichtigt

Backlog Nr. 17 (Mengenbremse für `ingest.php`) war an „P1/P2" übergeben. Das
war überholt: Zuständig ist **P5** (Rahmenplan R19); P1 misst nur das
Aufrufverhalten und legt keine Grenze fest.

### Nachtrag: derselbe Befund ein zweites Mal, auf einem anderen Weg

Diese Lücke ist in **Phase P1** unabhängig noch einmal gefunden worden (dort
Fund F-P1-I), bevor beide Arbeitslinien voneinander wussten. Der Eintrag ist
nachträglich um das ergänzt, was die zweite Fassung mitbrachte und diese hier
nicht hatte.

**Ein zweiter Weg hinein: die Wiederherstellung einer Sicherung.** Oben steht
der Import als Vektor. Er ist nicht der einzige — `api/backup_restore.php`
übernimmt den inneren Chiffretext unverändert, wie es sein muss. Wer eine
Sicherung mit `<img src=x onerror="…">` im Altersfeld einspielt, führt das
Skript beim nächsten Blick in die Einsatzliste aus. Im **Adminbereich** wiegt
das schwerer als beim Import: Dort schreibt „Einspielen" eine *fremde*
Sicherung in ein Konto — die Person, die das Skript ausführt, ist dann nicht
die, von der die Datei stammt.

**Die Einsatzseite war nicht betroffen.** `EdPat.alterText()` gibt für einen
nicht in eine Zahl auflösbaren Wert `null` zurück, und was sie ausgibt,
maskiert sie. Betroffen waren genau die drei Tabellen.

**Wer nachbessern muss: niemand.** Der Fehler lag in der Anzeige, nicht im
Bestand. Wer eine Sicherung fremder Herkunft eingespielt hat, sollte den
Bestand einmal ansehen; ein manipulierter Wert steht danach weiterhin im
Altersfeld, ist aber inert.

**Zweite Messung, unabhängig von `tools/maskierungs-probe/`.** Der
Referenzdatensatz der Phase P1 trägt im Altersfeld eines Einsatzes absichtlich
`<img src=x onerror="alert('R20-alter')">`.
`tools/referenzdatensatz/browser/angriffswerte.mjs` ersetzt `window.alert`,
`confirm` und `prompt` **vor** dem ersten Seitenskript, protokolliert die
Aufrufe und zählt zusätzlich, ob Elemente aus der Nutzlast im Dokument stehen.

Gegen den Stand 7.2.0 gehalten: drei Seiten, je ein ausgelöster Dialog und ein
eingefügtes `<img>` — **sechs Befunde**. Gegen diese Fassung: **42
Einzelprüfungen über sechs Seiten**, kein Dialog, kein eingefügtes Element,
keine Konsolenmeldung. Diese zweite Zahl ist nach dem Zusammenführen der
beiden Arbeitslinien **noch einmal gefahren** worden, damit sie den
ausgelieferten Code belegt und nicht den verworfenen Entwurf. Die Gegenprobe läuft mit: Der Wert muss auf mindestens
einer Seite **sichtbar** sein, sonst hieße „kein Dialog" nur „nichts
gerendert".

Zwei Prüfmittel, zwei Wege, ein Ergebnis. Das ist mehr wert als eine Messung,
die man zweimal liest.

## [Web 7.2.0] — 2026-08-23

**Die Nacharbeit zu P0.** Die Befundpakete A4 (toter Code) und A6
(Strukturreview) haben Listen geliefert statt Änderungen — hier stehen die
Punkte, die daraufhin einzeln freigegeben wurden, dazu die Fehler, die beim
Suchen aufgefallen sind. Kein neues Feld, kein Datenmodell, **keine
Migration**.

**Sichtbar wird dreierlei**, alles drei beabsichtigt: Die Rückfragen auf der
Sicherungsseite erscheinen jetzt überhaupt (und die auf drei anderen Seiten
nicht mehr doppelt), die Tagesübersicht zeigt für verschlüsselte, aber nicht
lesbare Angaben dasselbe Warnzeichen wie Suche und Zeitraum-Übersicht, und ein
veralteter Link führt nicht mehr auf eine weiße Seite mit sechs Wörtern,
sondern auf eine Seite mit Kopfleiste und Rückweg. Alles Übrige ist
Innenarbeit — dass sie das Erscheinungsbild nicht anfasst, wurde nicht
behauptet, sondern gemessen (39 447 Elementmessungen über das echte
Seitenmarkup, keine Abweichung).

### Die Rückfragen auf der Sicherungsseite erscheinen wieder

Drei Schaltflächen in `admin_sicherungen.php` trugen eine Rückfrage —
„Alle sichern", „Einspielen" und „Für NutzerIn freigeben". Sie erschien
**nie**. `confirm.js` band nur an `<form>` und an `<a>`; an einen `<button>`
band es nichts. Die Attribute standen da und sahen nach Absicherung aus.

Das ist nicht nebensächlich: „Einspielen" schreibt eine fremde Sicherung in
ein Konto, „Freigeben" gibt sie einer anderen Person heraus. Beide standen
als gewöhnliche Schaltfläche neben einem Auswahlfeld — ein Fehlklick hatte
nichts vor sich.

`confirm.js` hört jetzt auch auf `button[data-confirm]`. Der Knopf wird nach
dem Ja **erneut geklickt** statt das Formular per `submit()` abzuschicken:
Nur der tatsächlich betätigte Absendeknopf schickt sein `name`/`value` mit,
und genau darin unterscheiden sich die drei (`action=einspielen` gegen
`action=freigeben`).

Am Markup ändert sich nichts. Die 22 Rückfragen am `<form>` und die eine am
`<a>` laufen unverändert.

### Rückfragen erschienen auf drei Seiten doppelt

`assets/confirm.js` ist eine IIFE ohne eigenen Namensraum: Eine zweite
Einbindung ist kein Fehler, sie meldet nur ein zweites Mal dieselben Zuhörer
an — und dann öffnen **zwei Dialoge übereinander**. Genau das war auf
`admin_sicherungen.php`, `admin_stammdaten.php` und `nachbearbeitung.php` der
Fall: Sie banden die Datei zusätzlich zu `ui_footer()` selbst ein.

Die drei Zeilen sind weg. Zusätzlich hat `confirm.js` jetzt eine Schranke am
Anfang — eine zweite Einbindung ist damit wirkungslos statt doppelt.

### Toter Code entfernt — nach Einzelfreigabe

Paket A4 hatte eine Befundliste vorgelegt statt zu löschen; jeder Punkt wurde
einzeln freigegeben. Entfernt sind jetzt:

**Sechs Funktionen und Methoden.** `iso_to_sql()` (`db.php`) — ihre einzigen
Aufrufer lagen in `ingest.php` und wurden mit Web 4.2.0 durch `pruef_utc()`
ersetzt. `Pruefliste::anzahl()`, `::eintraege()` und `::setBezug()`
(`validate_lib.php`) — die vier Nutzer der Klasse benutzen ausschließlich
`melde()`, `sauber()`, `nachUrsache()` und `text()`; die drei anderen hatte in
der gesamten Historie nie jemand aufgerufen. `chunkArr()` (`export.js`),
dateiprivat und ohne Aufruf. `EdSuchtext.mitOperatoren()` (`suchtext.js`),
exportiert und nie benutzt — der Hinweis auf die Suchoperatoren, für den die
Funktion laut Kommentar da war, ist statisches Markup.

Mit `setBezug()` fällt das **Merkmal `bezug`** der Klasse `Pruefliste` ganz
weg. Das ist kein Beifang: Ohne Aufrufer blieb `$bezug` immer leer, jeder
Prüfeintrag trug also ein leeres Feld, und gelesen hat es ohnehin niemand.

**Elf CSS-Klassen in 18 Regeln:** `.actionbar`, `.centercol`, `.chip-x`,
`.data-centered`, `.daydelete`, `.page-center`, `.page-narrow`, `.rolehead`,
`.trash`, `.trashactions`, `.zielinfo`. Keine kommt im Markup vor — weder
geschrieben noch zur Laufzeit zusammengesetzt. Nicht zu verwechseln mit
`.trashtable` und `.trashlink`, die benutzt werden und bleiben.

**Stehen geblieben ist `.c-dc-false_alarm`**, obwohl die Regel heute nicht
greift: Das Feld `false_alarm` trägt im Feldkatalog kein `day_col` und bekommt
deshalb keine Spalte in der Tagestabelle. Wer das ergänzt, braucht die Breite
im selben Augenblick wieder. Ein Kommentar sagt das jetzt an Ort und Stelle.

Nachgewiesen wurde die Wirkungslosigkeit nicht durch Zusehen: Der
Stilvergleich hat 29.376 Elementmessungen über drei Proben und neun
Fensterbreiten verglichen — **keine Abweichung** —, und eine Gegenprobe hat
bestätigt, dass keine der elf Klassen an einem Element des tatsächlichen
Markups steht, auch nicht in dem, das erst im Browser entsteht.

### Die Tagesübersicht zeigt wieder dasselbe wie Suche und Zeitraum

Die drei Einsatztabellen der Anwendung sollen dieselbe Liste zeigen. Zwei von
ihnen — Suche und Zeitraum-Übersicht — teilen sich dafür seit Web 5.2.0
`assets/missiontable.js`. Die Tagesübersicht baute ihre Zeile selbst und hatte
eigene Fassungen derselben Hilfsfunktionen. Die waren **auseinandergelaufen**,
und zwar in zwei Punkten, die beide etwas Falsches zeigten:

1. **Ortsangabe.** `extractOrt()` schneidet den Ortsteil aus einer Adresse.
   Der gemeinsamen Fassung wurde mit E11 eine Prüfung hinzugefügt: Enthält der
   letzte Teil nach dem Komma keine Buchstaben, wird der Text unverändert
   durchgereicht. Der Kopie in `index.php` fehlte sie. Ein Altdatensatz mit
   Koordinatentext im Ortsfeld — `47.72800, 10.31600` — zeigte auf der
   **Startseite** deshalb das Bruchstück `10.31600`, in Suche und
   Zeitraum-Übersicht die ganze Koordinate.
2. **Nicht lesbare Angaben.** Wo Suche und Zeitraum-Übersicht ein Warnzeichen
   zeigen, stand auf der Startseite ein Gedankenstrich — dasselbe Zeichen wie
   für „keine Angaben". Genau diese Verwechslung sollte das Warnzeichen
   verhindern: Wer den Unterschied nicht sieht, merkt nicht, dass sein
   Inhaltsschlüssel nicht mehr passt.

`index.php` holt die vier Bausteine jetzt aus `missiontable.js` — so, wie
`zeitraum.php` es seit jeher tut. **Sichtbar ändert sich dadurch etwas:** Auf
der Tagesübersicht erscheint für verschlüsselte, aber nicht lesbare Angaben
ein ⚠ statt eines Gedankenstrichs. Das ist der Zweck der Änderung.

Die Spaltenmechanik von `missiontable.js` übernimmt die Seite bewusst
**nicht**: Sie führt die Katalogspalten aus `DAY_COLS`, die die anderen beiden
Tabellen nicht haben.

### Drei wiederkehrende Blöcke sind jetzt Bausteine

Das Strukturreview A6 hat gezählt, was in dieser Anwendung mehrfach von Hand
geschrieben steht. Drei Befunde waren so gleichförmig, dass eine gemeinsame
Fassung sie vollständig aufnimmt — alle drei liegen jetzt in `ui.php`, bei der
Seitenhülle aus Web 7.1.0.

**`ui_krypto_bootstrap()` — das Rüstzeug der Verschlüsselung.** Acht Stellen in
sieben Dateien schrieben denselben Block: die Verweise auf `crypto.js`,
`keyguard.js` und `unlock.js`, dazu vier Konstanten mit Hülle, Salz und
Rundenzahl — und jedes Mal derselbe achtzeilige Kommentar. Zwei Folgen hatte
das bereits:

1. **Namensdrift.** Der Profilreiter der Einstellungen nannte die Hülle
   `WRAP_PW`, überall sonst heißt sie `PAT_WRAP`. Ein Baustein, der aus diesem
   Reiter etwas übernimmt, hätte ins Leere gegriffen. Jetzt heißt sie überall
   gleich.
2. **Doppelte Einbindung.** `einstellungen.php` band `crypto.js` zweimal ein
   und `pwquality.js` ebenfalls — einmal je Reiter. Beide Dateien deklarieren
   auf oberster Ebene ein `const`; eine zweite Deklaration im selben Dokument
   ist ein `SyntaxError`, der das **ganze** zweite Skript verwirft. Dass
   nichts geschah, hing allein daran, dass die beiden Reiter einander
   ausschließen — eine nirgends aufgeschriebene Bedingung in einer Datei mit
   über 2000 Zeilen.

Gegen den zweiten Punkt führt der Baustein einen Merkzettel: Ein zweiter
Aufruf im selben Seitenaufbau gibt **nichts** aus und schreibt eine Zeile ins
Fehlerlog. Aus der stillen Bedingung ist eine geworden, die sich meldet.

**`ui_meldung()` — Hinweis- und Fehlerzeile.** Dieselben zwei Zeilen standen in
13 Dateien, 21-mal derselbe Dreisatz aus Abfrage, Klasse und Maskierung.
Uneinheitlich war daran nur die Klasse der Hinweiszeile: elf Stellen schrieben
`alert-info`, zwei `alert-ok` — Stammdaten und Nachbearbeitung, die dort einen
Vollzug melden. Deshalb hat der Baustein einen Ton-Parameter; nicht als Vorrat
für künftige Töne, sondern weil der Bestand zwei kennt. Am Erscheinungsbild
ändert sich nichts.

**`ui_abbruch()` — die Sackgasse bekommt eine Tür.** An 16 Stellen stand für
den nicht gefundenen Datensatz `exit('Einsatz nicht gefunden.')`: nackter Text
ohne Zeichensatzangabe, ohne Kopfleiste, ohne Weg zurück. Wer einen veralteten
Link öffnete — ein Lesezeichen auf einen gelöschten Einsatz, eine Zeile aus
einer alten E-Mail —, landete auf einer weißen Seite mit sechs Wörtern und
musste die Zurück-Taste suchen. Der HTTP-Code stimmte, die Seite war trotzdem
eine Sackgasse.

Wortlaut und Statuscode bleiben unverändert; die Meldung steht jetzt in einer
richtigen Seite mit Kopfleiste und einem Rückweg. Zwei der 16 Stellen liegen
in `auth_guard.php` selbst (`require_admin()` und `csrf_check()`); der
API-Zweig von `require_admin()` antwortet weiterhin mit JSON und ist von der
Änderung nicht berührt.

### Der Baustein unter zwei Namen — zwölf Regelpaare zusammengeführt

`style.css` enthielt zwölf Gruppen, in denen **zeichengleiche Regelkörper unter
verschiedenen Selektoren** standen: die Überschrift der Einsatztage-Leiste und
die der Suchfilterspalte, Phasen- und Reanimationszeile im Einsatzformular, die
beiden Reihen umbrechender Kontrollkästchen, das Aufklappdreieck an zwei
Stellen, der Fokusring, die Knopfleiste des Bestätigungs- und die des
Entsperrdialogs. Zusammengehalten wurden sie bislang nur durch einen Kommentar
(„bewusst dasselbe Muster wie …") — und ein Kommentar hält nichts, er sagt nur
etwas.

Jetzt steht jede Gruppe an **einer** Stelle, an der ursprünglichen Position der
ersten; am zweiten Ort steht ein Verweis darauf. Bei den beiden Dialogkästen
war der Fall etwas anders: Sie unterscheiden sich in genau **einer** Angabe —
der Entsperrdialog darf 2 rem breiter werden, weil in ihm ein Passwortfeld
samt Erklärung steht. Diese eine Angabe steht weiterhin für sich, alles übrige
gemeinsam.

Der Gegenwert ist nicht die eingesparte Zeile, sondern dass die Paare nicht
mehr auseinanderlaufen **können**.

### Die Schaltflächen sind eine Familie geworden

Sechs Varianten — `.btn-primary`, `.btn-danger`, `.btn-yellow`, `.btn-red`,
`.btn-plain`, `.btn-edit` — standen in vier über 110 Zeilen verteilten Blöcken
und wiederholten einander: dreimal dieselben fünf Angaben zur kompakten
Aktionsgröße, sechsmal dieselbe abgeschaltete Unterstreichung. Diese Streuung
hat bereits zweimal etwas gekostet (Web 7.0.1 und 7.0.2).

Sie stehen jetzt beieinander, mit einer Sammelregel darüber. **Was in der
Sammelregel steht, ist bewusst wenig:** Die sechs teilen keine einzige Farb-
oder Maßangabe, und `font-family:var(--head)` gehört ausdrücklich **nicht**
hinein — bei `.btn-primary` und `.btn-danger` kommt die Schriftfamilie heute
aus `button{}` und damit nur am `<button>` an; `a.btn-primary` trägt Open Sans.
In die Sammelregel gezogen hätte die Schrift dort gewechselt, und das wäre
eine Gestaltungsentscheidung gewesen, keine Zusammenführung. Geblieben sind
`cursor` und `text-decoration` — beides an jeder der sechs Stellen ohne
Unterschied — dazu eine gemeinsame Regel für die kompakte Größe der drei
Zeilenaktionen.

Damit verschwinden **beide handgepflegten Aufzählungen**: Die Liste
`a.btn-red,a.btn-edit,a.btn-primary,a.btn-plain,a.btn-yellow` gegen die
Unterstreichung entfällt ganz, und die Größenregel der Zeilenaktionen nennt
die Familie jetzt über `:is(…)` statt fünf Varianten einzeln. Die Spezifität
bleibt dieselbe; `.btn-danger` kommt dabei neu hinzu und steht heute in keinem
`.rowactions`, die Regel trifft also kein Element anders als zuvor.

### Vier verschwundene Backlog-Nummern sind wieder da

`docs/Backlog.md` erklärte im Kopf, dass die Nummern 4, 6 und 7 ohne Eintrag
verschwunden und nicht mehr rekonstruierbar sind — und schwieg zu **1, 9, 10
und 12**, die ebenfalls fehlten. Wer die Liste durchsah, konnte nicht wissen,
ob dort etwas Offenes verlorengegangen war.

Diese vier sind belegbar: Code, Changelog und Technikdokumentation nennen sie
an neun Stellen namentlich („Backlog Nr. 10"), und daraus geht hervor, worum es
ging und womit es erledigt wurde — Reanimationen im Einsatzformular (Web
5.5.0), `asset()` mit Dateizeitstempel (5.4.0), Tagesspalten aus dem
Feldkatalog (5.4.0), Schriften und Leaflet lokal (5.2.0). Alle vier stehen
jetzt unter *Erledigt*, jeder mit dem Vermerk, dass er aus seinen Fundstellen
rekonstruiert wurde, und mit deren Aufzählung — damit die Rekonstruktion
nachprüfbar bleibt und nicht als Originaltext gelesen wird. Die Kopfnotiz
unterscheidet die beiden Fälle jetzt ausdrücklich.

**Fünf neue Punkte (17 bis 21)** halten fest, was P0 gefunden, aber bewusst
nicht angefasst hat: die fehlende Mengenbremse in `ingest.php`, die Regel
`.btn-link.danger`, die nie greifen kann, ein ungelesenes `$title`, die
Hexwerte in `style.css` und die 43 weiteren Kandidaten aus der Nachlese zum
toten Code. Ohne diese Einträge wären sie mit dem Konzeptdokument
verschwunden.

`docs/Branding.md` bekommt dazu einen offenen Punkt (B3), der einen bislang
unausgesprochenen Widerspruch benennt: Die Brand Guideline verlangt „kein
Hexwert direkt in einer Regel", der Bestand hält das an 78 von 93 Stellen
nicht ein. Für 13 davon gibt es bereits ein Token mit exakt demselben Wert;
sie sind dort einzeln aufgeführt. Die Regel gilt weiter — sie beschreibt das
Ziel, nicht den heutigen Stand.

### Kleinere Berichtigungen

* **`api/range.php` sendet `Cache-Control: no-store`.** Die Datei schrieb ihre
  Antworten selbst und ging damit an `json_out()` vorbei — obwohl der
  Kommentar dort „Zeitraum" ausdrücklich als einen der Endpunkte nennt, die
  den Kopf brauchen. Die Antwort trägt Datum, Uhrzeit, Einsatznummer und
  Koordinaten im Klartext; an einem gemeinsamen Rechner reicht die
  Zurück-Taste. Jetzt läuft der Endpunkt wie die übrigen neun über
  `json_out()`.
* **„Du kannst dich nicht selbst löschen."** stand in der
  Nutzerverwaltung als blauer Hinweis — dieselbe Farbe wie „Nutzer angelegt".
  Es ist eine abgelehnte Handlung und erscheint jetzt als Fehler.
* **`daylist.js` wird nur noch geladen, wo es etwas tut.** Das Skript belebt
  das Jahr/Monat-Akkordeon der Einsatztage-Leiste; `ui_footer()` gab es bis
  hierher auf **jeder** Seite aus. Auf acht Seiten ohne Leiste — Einstellungen,
  Import, Suche, Administration, Wartung — suchte es `.dayyears`, fand nichts
  und kehrte zurück.
* **Zwei Klassen ohne Wirkung entfernt:** `sorted` an den Tabellenköpfen der
  Tagesübersicht und `map-empty` am Kartencontainer wurden per JavaScript
  gesetzt, hatten aber keine einzige Regel im Stylesheet.

## [Web 7.1.0] — 2026-08-23

**Eine Aufräumrunde, bevor die Oberfläche umgebaut wird.** Das ist Paket P0 des
Programms Gen-EM NAdoku: toter Code weg, die Seitenhülle an eine Stelle, das
Stylesheet entdoppelt und gegliedert. Am Funktionsumfang ändert sich **nichts** —
keine neue Funktion, kein Feld, kein Datenmodell, **keine Migration**. Auch das
Erscheinungsbild ist unverändert; das wurde nicht behauptet, sondern gemessen
(siehe unten).

Warum die Nebenversion steigt, obwohl es keine neue Funktion gibt: Vier Dateien
müssen vom Webspace **verschwinden**, und jede Seite der Anwendung ist angefasst.
Das ist mehr, als eine Korrekturfassung ankündigen sollte.

### Vier Seiten, die niemand erreichen konnte, sind entfernt

`flugtag_neu.php`, `flugtag_datum.php`, `flugtag_loeschen.php` und
`geraete.php`. Auf keine von ihnen zeigte ein Verweis — nicht aus der
Navigation, nicht aus einer anderen PHP- oder JS-Datei, und auch kein Rewrite
in der `.htaccess`.

Die drei `flugtag_*`-Seiten legen Tage nach dem Modell **vor** der
Diensttag-Umstellung an (`INSERT IGNORE INTO days (user_id, day)`). Über ein
altes Lesezeichen aufgerufen, hätten sie die Schicht-Logik umgangen. Der
Changelog zu Web 6.2.0 meldet ihre Entfernung bereits — im Repository lagen sie
trotzdem weiter. Jetzt nicht mehr.

`geraete.php` war eine Weiterleitung aus Web 3.x auf `einstellungen.php?t=geraete`.
Ihr eigener Kopfkommentar nannte das Ablaufdatum: „ENTFERNEN AB: Web 5.0.0" —
zwei Hauptversionen überfällig. Sie ist **ersatzlos** entfallen; ein altes
Lesezeichen auf diese Adresse läuft jetzt in einen 404.

### Der Seitenkopf steht an einer Stelle statt an 25

Doctype, `<html>`, `<head>` und die Eröffnung des `<body>` baute jede Seite
selbst — 25-mal fast dasselbe, und doch nicht gleich: zwei Schreibweisen des
Viewports, zwei Titeltrenner, drei Einrückungen. Eine Änderung am Viewport, an
den Stylesheets oder ein künftiges Mobile-Menü war damit eine 25-fache Änderung.

`ui.php` hat dafür jetzt **`ui_seite_start()`** und **`ui_seite_ende()`**.
Vereinheitlicht wurde nach Auszählung, nicht nach Gefühl: Viewport ohne
Leerzeichen (15 Seiten gegen 10), Titeltrenner Gedankenstrich (ebenfalls 15
gegen 10).

Zwei Stellen brauchten Rücksicht. **`install.php`** läuft vor der
Ersteinrichtung — es gibt dort weder `config.php` noch `db.php` und damit weder
`asset()` noch `favicon_tags()`. `ui.php` hat auf oberster Ebene keine
Abhängigkeit und läuft deshalb auch dort; die Hülle fängt die fehlenden Helfer
ab. Der Einrichter behält seine eigene Gestaltung im Kopf und bekommt
nebenbei **erstmals ein Favicon**. **`session_lib.php`** wird von `login.php`
und `logout.php` eingebunden, die `ui.php` nicht kennen — es lädt die Hülle
jetzt selbst nach.

### `style.css` hat Abschnitte — und jeder Selektor steht nur noch einmal da

Die Datei war ohne Gliederung gewachsen: Was neu dazukam, landete unten. So
standen **siebzehn Regeln ein zweites Mal** in der Datei, bis zu 700 Zeilen von
der ersten entfernt. Wer eine änderte, konnte nicht wissen, ob es noch eine
zweite gibt.

Jetzt: **19 benannte Abschnitte** vom Allgemeinen zum Speziellen, mit
Inhaltsangabe im Dateikopf, und die **Media Queries gesammelt am Schluss**.
Wortgleiche Wiederholungen sind entfallen, Verfeinerungen mit ihrer Grundregel
zusammengeführt — jeweils so, dass der wirksame Wert derselbe bleibt.

Auch die **Doppeldefinition von `.btn-plain`** ist weg, und anders als in 7.0.2
angenommen kostet das nichts: Von der kompakten Fassung kam **keine einzige
Eigenschaft** an. Die spätere setzt `font:inherit`, und diese Kurzform nimmt
auch die Schriftgröße mit. Die kompakte Größe einer Zeilenaktion kommt seit
7.0.1 ohnehin nicht aus der Klasse, sondern aus dem Ort (`.rowactions .btn-*`)
— und der ist unberührt.

### Das Erscheinungsbild ist nachgerechnet, nicht in Augenschein genommen

Ein umsortiertes Stylesheet kann an jeder Stelle anders aussehen, an der zwei
gleich starke Regeln ihre Reihenfolge tauschen. Statt sich durch die Seiten zu
klicken, wurde gemessen: Dieselbe Seitenstruktur einmal mit dem alten und
einmal mit dem neuen Stylesheet in einen Browser geladen und für **jedes
Element 129 Eigenschaften** verglichen — bei neun Fensterbreiten von 1400 bis
500 Pixeln, damit auch die verschobenen Media Queries mitgeprüft sind.
Zusätzlich dieselbe Messung für die Hover- und Fokuszustände und ein
Härtetest, der jedes Selektorpaar mit getauschter Reihenfolge auf **ein**
Element zwingt.

Rund 41.000 Elementmessungen, **keine Abweichung**. Ein einziger echter Fall
kam dabei ans Licht und wurde vorher berichtigt: `.keybox` und `.paircode`
sitzen auf der Kopplungsseite am selben Element und setzen beide den Rahmen —
in der neuen Reihenfolge hätte der Kopplungscode seine orange Umrandung
verloren.

### Betreiberhinweis

Die vier entfernten Dateien müssen auch **auf dem Webspace verschwinden**. Ein
FTP-Abgleich entfernt nicht zwingend, was im neuen Paket fehlt:
`flugtag_neu.php`, `flugtag_datum.php`, `flugtag_loeschen.php`, `geraete.php`.

## [Web 7.0.2] — 2026-08-19

### Die Gruppenüberschriften im Einsatzformular setzen sich ab

Mit 7.0.1 saßen sie an der richtigen Stelle — auffällig genug waren sie damit
noch nicht: `.8rem` ist **kleiner** als die Feldbeschriftungen darunter
(`.92rem`), und ein Abstand fehlte ganz. „PATIENTINNENDATEN" las sich dadurch
wie eine Vorbemerkung zum Feld „Einsatznummer" und nicht wie der Titel der
Gruppe.

Jetzt ist die Überschrift **größer als der Feldtext** (`.95rem`, Versalien,
gesperrt, in Dunkelblau) und eine **Linie trennt sie vom Inhalt** — dieselbe
Ordnung wie im Tabellenkopf, nur leiser, weil auf einer Seite bis zu neun
Gruppen untereinander stehen. Der Zusatz „Ende-zu-Ende-verschlüsselt" daneben
bleibt Beiwerk: kleiner, ungesperrt, gedämpft.

### Die Schaltflächen einer Tabellenzeile sind wieder gleich groß

„Auswählen" war sichtbar größer als „Abwählen" eine Zeile darüber — und das war
kein Einzelfall, sondern das Ergebnis zweier unabhängiger Ursachen:

1. **`.btn-plain` ist in `style.css` zweimal definiert** — einmal mit
   `.25rem/.6rem` bei `.85rem`, weiter unten noch einmal mit `.45rem/1rem` bei
   voller Schriftgröße. Die spätere gewinnt. Jeder „einheitliche Aktions-Button"
   war damit in Wahrheit größer als die gelbe und die rote daneben.
2. **`.btn-primary` trägt die Formularmaße** (`button{padding:.5rem .9rem}`,
   volle Schriftgröße, `width:100%`). Neben einer roten Zeilenschaltfläche fiel
   das sofort auf.

Statt beides einzeln nachzubessern, gilt die Größe jetzt für den **Ort**: Was in
`.rowactions` steht, ist eine Zeilenaktion und wird so groß wie die übrigen —
gleich, welche Klasse es trägt. Damit kann auch die nächste Schaltflächenklasse
dort nicht mehr aus der Reihe fallen.

**Zwei Größen mit einer klaren Regel**, nicht fünf ohne: Zeilenaktionen sind
klein, die Absendeknöpfe der Eingabekästen behalten die Maße, die
Formularschaltflächen in der ganzen Anwendung haben. Der Papierkorb bleibt
unberührt — seine Tabelle bringt seit jeher eine eigene, engere Regel mit
(zwei gleich breite Schaltflächen).

Die Doppeldefinition von `.btn-plain` bleibt stehen: Sie versorgt ein Dutzend
eigenständiger Schaltflächen (Entsperren, Abbrechen, Filter zurücksetzen), für
die die größere Masse richtig ist. Sie zu entfernen hätte diese alle verkleinert
— eine Änderung, die weit über den Befund hinausginge.

## [Web 7.0.1] — 2026-08-19

Nachträge zur Runde davor: drei Stellen, an denen die neue Gestaltung nicht
aufging, und eine Bedingung, die zu eng gefasst war. Keine Änderung an Daten
oder Ablauf.

### Die Überschriften der Formulargruppen lagen halb auf dem Rahmen

Eine `<legend>` sitzt in der Voreinstellung der Browser **mittig auf der oberen
Rahmenlinie** und schneidet dort eine Lücke hinein. Mit der kleinen, gesperrten
Versalschrift und dem abgerundeten Rahmen ging das nicht auf: Die Schrift stand
halb über der Linie, halb darauf, und die Lücke saß auf der falschen Höhe.

Die Überschrift steht jetzt **im Kasten** statt auf seinem Rand, der Rahmen
läuft durchgehend — dieselbe Form wie die Überschrift der Eingabekästen in den
Einstellungen. Technisch: `float:left; width:100%` nimmt der `<legend>` die
Sonderbehandlung. Bewusst **kein** `overflow:hidden` zum Aufräumen des Floats —
die Vorschlagsliste des Ortsfelds ist absolut positioniert und würde am
Kastenrand abgeschnitten.

### Standort und Zielklinik anlegen: eigener Rahmen statt Zeilenformular

In Web 7.0.0 war die Überhöhe der Felder behoben, die **Ausrichtung** aber
nicht: Das Namensfeld trug keine Beschriftung, das Suchfeld daneben eine
(„Lage des Standorts") — und rutschte dadurch eine Zeile tiefer. Zwei
Eingabefelder derselben Zeile auf zwei Höhen.

Jetzt dieselbe Form wie beim Rettungsmittel: **Name und Schaltfläche in einer
Zeile**, die freiwillige Ortsangabe als eigener Block darunter, das Ganze in
einem Rahmen mit Überschrift. Damit stellt sich die Frage nach der Ausrichtung
nicht mehr. Gilt in der Kontoansicht **und** in der Administration, für
Standorte **und** Zielkliniken.

### Das ★ steht neben den Schaltflächen und ist größer

Seine Spalte teilte sich den freien Platz mit der Namensspalte und landete
dadurch mitten in der Zeile — weit weg von den Schaltflächen, auf die sie sich
bezieht. Sie schrumpft jetzt auf ihren Inhalt und rückt damit unmittelbar vor
die Aktionen; das Zeichen ist so groß, dass es als Zeichen gelesen wird, mit
Textalternative in `title`/`aria-label`.

### NA-Begleitung wurde beim Bearbeiten nicht vorbelegt

Die Vorbelegung griff nur beim **Nachtragen** — beim Bearbeiten eines
vorhandenen Einsatzes tat sich beim Umschalten auf „Luft" nichts. Das war eine
Bedingung im Formular (`!$editing`), gesetzt aus der Sorge, eine Vorbelegung
könnte einen gespeicherten Wert still überschreiben.

Die Sorge war unbegründet: Der Haken setzt sich **nur auf ein `change`-Ereignis
der Transportart hin**, also nur, wenn jemand sie gerade umstellt. Beim Laden
der Seite passiert nichts, ein gespeicherter Wert bleibt unangetastet. Und wer
die Transportart bewusst ändert, trifft ohnehin eine Entscheidung — ein
Vorschlag dazu ist Hilfe, keine Änderung hinter dem Rücken. Die Bedingung ist
weg; unverändert bleibt, dass ein eigener Handgriff am Haken die Vorbelegung
für dieses Formular dauerhaft abschaltet.

## [Web 7.0.0] — 2026-08-18

Eine Runde an der **Oberfläche**. Am Datenmodell ändert sich nichts, und eine
Migration gibt es **nicht** — die Hauptnummer steigt, weil sich die Wege durch
die Anwendung verschoben haben: Das Einsatzformular ist neu gegliedert,
„Standortdaten" ist in zwei Menüpunkte zerfallen, die Filterspalte der Suche ist
neu geschnitten, und ein Formularfeld ist ersatzlos entfallen. Wer die Anwendung
kennt, findet Dinge an neuer Stelle.

### Das Einsatzformular ist in benannte Gruppen zerlegt

Vorher: vier Überschriften und darunter eine lange Kette gleich aussehender
Felder. Dass Transportart, NA-Begleitung und Transportziel zusammengehören, war
nur zu erraten; „Weitere Rettungsmittel" hing zwischen Bergwacht und Besatzung.

Jetzt trägt jede Gruppe einen eigenen Rahmen mit Überschrift, in dieser Folge:

* **PatientInnendaten** — Einsatznummer, Nachname, Vorname, Geburtsdatum, Alter,
  Diagnose. Geburtsdatum und Alter stehen nebeneinander.
* **Einsatz** — Sekundärtransport und Fehleinsatz nebeneinander, darunter
  Einsatzort, Beschreibung und Abfahrtort.
* **Transport** — Transportart, NA-Begleitung, Transportziel, Schockraum.
* **Bergrettung** — Bergwacht und Winde. Fällt ganz weg, wenn der Diensttag
  keine der beiden Fähigkeiten mitbringt und nichts belegt ist.
* **Weitere Rettungsmittel** — Fahrzeuge und weiterer Notarzt.
* **Abweichende Besatzung**
* **Notizen**
* **Einsatzphasen** — nach unten gewandert, unmittelbar über die Reanimation.
  Beim Bearbeiten, dem häufigeren Fall, stehen sie meist schon vollständig da
  und schoben alles andere nach unten.
* **Reanimation**

Welches Feld in welche Gruppe gehört, steht im Feldkatalog
(`mission_fields.php`, neuer Schlüssel `gruppe`) — nicht als zweite Liste im
Formular. Ausgezeichnet ist es als `<fieldset>`/`<legend>`: Screenreader nennen
die Gruppe beim Betreten eines Feldes von selbst.

### Das Feld „Einsatzdatum" ist entfallen

Es stand direkt unter dem Diensttag und zeigte in aller Regel dasselbe Datum
noch einmal. Der eine Fall, für den es da war — der Einsatz **nach Mitternacht**
an einem Dienst, der am Vortag begann —, wird jetzt **erkannt statt eingetippt**:
Liegt die erste Phase vor dem Beginn des Dienstes, gehört der Einsatz dem
Folgetag. Der Dienst weiß, wann er angefangen hat; ein von Hand gesetztes Datum
war eine Fehlerquelle mehr.

Weicht das Einsatzdatum vom Datum des Dienstes ab, steht es ausdrücklich in der
Kopfzeile des Formulars. Beim **Bearbeiten** bleibt das gespeicherte Datum
unangetastet — verschoben wird ein Einsatz über „Aktionen → Verschieben".

### Der Abfahrtort erscheint nur ohne GPS-Aufzeichnung

Er ist ausschliesslich dazu da, ohne Track eine Linie auf die Karte zu bekommen.
Liegt ein Track vor, zeichnet die Karte den tatsächlich zurückgelegten Weg — die
Auswahl daneben war dann eine Frage ohne Wirkung. Die gespeicherte Regel bleibt
in der Datenbank unangetastet.

Die Auswahl „Standort des Diensttags" heißt jetzt schlicht **„Standort"**: Ein
anderer steht gar nicht zur Wahl, und für die Bodenrettung klang „Diensttag"
nach Flugbetrieb.

### Weitere Rettungsmittel: Merkfelder im Eingabefeld

Die bereits gewählten Rettungsmittel standen als eigene Zeile **über** dem
Eingabefeld — man tippte unten und sah oben, was schon da war. Jetzt sitzen sie
im Feld selbst, und die Eingabe läuft rechts daneben weiter, wie bei den
Empfängern eines Mailprogramms. Die Rücktaste im leeren Feld nimmt den letzten
Eintrag zurück.

### NA-Begleitung ist bei Lufttransport vorbelegt

Ein luftgebundener Transport ohne Notarzt an Bord ist die Ausnahme — der Haken
war damit der am häufigsten vergessene des Formulars. Er wird gesetzt, sobald
„Luft" gewählt ist, und **nur solange niemand ihn von Hand angefasst hat**: Eine
ausdrückliche Entscheidung schlägt die Vorbelegung, und zwar dauerhaft. Wirkt
ausschliesslich beim Nachtragen; ein bestehender Einsatz behält, was gespeichert
ist.

### Zwei Beschriftungen umbenannt

* „Transport" heißt **Transportart**. Das Wort stand zugleich für die Gruppe, in
  der das Feld sitzt, und im Altbestand für die Spalte der Zielklinik.
* „Anderer Notarzt" heißt **Weiterer Notarzt**. „Anderer" las sich, als sei der
  eigene ersetzt worden; gemeint ist ein zusätzlicher.
* Das Suchfeld an der Zielklinik heißt **Lokalisation Transportziel** statt
  „Koordinaten" — was es einsammelt, ist eine Adresse oder ein Plus Code; die
  Koordinate ist das Ergebnis. Sobald sie steht, verschwindet das Suchfeld und
  macht dem Merkfeld Platz (gilt für alle Ortsfelder mit getrennter Suche).

### Einsatzansicht: neue Reihenfolge im Inhaltskästchen

Das Kästchen las sich rückwärts: erst Transport und Winde, dann ganz unten, wer
behandelt wurde — die verschlüsselten Angaben kommen erst nach dem Entsperren an
und wurden einfach angehängt. Jetzt hat jede Zeile einen Rang und wird an ihrer
Stelle eingefügt, unabhängig davon, wann ihr Wert eintrifft: Einsatznummer,
Name, Geburtsdatum, Einsatzort, Beschreibung, Luftlinie, Diagnose, Notizen,
weitere Rettungsmittel, weiterer Notarzt, Sekundär/Fehleinsatz, Winde,
Bergwacht, Transportart, Transportziel, Schockraum.

Dabei zusammengelegt und aufgeräumt:

* **Geburtsdatum und Alter stehen in einer Zeile.** Das Alter folgt aus dem
  Geburtsdatum, es ist keine zweite Angabe. Die Einheit wechselt mit dem Alter:
  unter einem Monat Tage, unter zwei Jahren Monate, darüber Jahre — bei einem
  Säugling ist „0" keine Auskunft, „3 Monate" schon.
* **Die Höhe steht in der Zeile des Einsatzortes.** Sie ist eine Eigenschaft
  dieses Ortes und sonst nichts (weiterhin nur luftgebunden).
* **Die Steigung ist entfallen.** Sie war das Profil der geflogenen Strecke,
  nicht das des Einsatzes. In Spalte, Export, Import und Sicherung bleibt sie
  unverändert erhalten.
* **Die Kopfzeile ist gestrafft:** ohne Streckenangabe (sie gehört zur
  Auswertung und steht dort) und ohne das Wort „Diensttag" vor dem Datum.
  Weicht das Datum des Dienstes vom echten Einsatzdatum ab, wird er weiterhin
  ausdrücklich genannt.
* „Phasen" heißt **Einsatzphasen** — Überschrift wie Kartenschalter.

### Suche: Filterspalte neu geschnitten

Drei der sechs Blöcke liessen sich nicht erklären: „Zeit" enthielt Datum und
Uhrzeit, „Werte" Alter, Strecke und Dauer, „Einsatz" einen einzigen Haken. Wer
nach Einsätzen über 50 km suchte, musste raten, wo das steht.

* **Einsatz** — Datum, Alarmzeit, Wochentag, **Strecke**, **Einsatzdauer**,
  Fehleinsatz.
* **Patient** — Alter von/bis.
* **Transport** — unverändert.
* **Beteiligte** — unverändert.
* **Bergrettung** — Bergwacht **und** Winde in einem Block, sichtbar nur, wenn
  im Bestand etwas davon vorkommt (vorher zwei Blöcke mit derselben Regel).
* **Werte** — entfallen. Das war nie ein Gegenstand, sondern eine Datenart.

Der Fehleinsatz-Filter erscheint jetzt feldweise nur, wenn im Bestand einer
dokumentiert ist — sein Block muss ja bleiben.

**Die Kurznamen im URL-Fragment sind unverändert** (`kv`, `ab`, `lv` …):
Verschickte Links funktionieren weiter, nur die Gruppe, die bei einem geteilten
Link aufgeht, hat gewechselt.

### Freitextsuche mit Und / Oder / Nicht

Bisher: jedes Wort muss irgendwo vorkommen. Das trägt für den Regelfall und
scheitert an zwei Fragen, die im Betrieb immer wieder auftauchen — „alle
Reanimationen **oder** Polytraumata" und „Bergwacht, aber **keine** Winde".

    sturz fraktur                  beide Begriffe (Leerzeichen heißt UND)
    sturz ODER fraktur             mindestens einer (OR, | ebenso)
    bergwacht -winde               der erste ja, der zweite nicht
                                   (NICHT, NOT, ! ebenso)
    "zwei wörter"                  genau diese Folge
    (sturz ODER fraktur) oberstdorf   Klammern; sonst bindet UND stärker

Groß- und Kleinschreibung spielt keine Rolle. **Wer keine Operatoren benutzt,
merkt nichts:** Ohne sie verhält sich die Suche exakt wie bisher. Eine
halbfertige Eingabe wird nicht bemängelt — sie wird gedeutet, so gut es geht,
und im Zweifel auf die alte Regel zurückgeführt. Eine Fehlermeldung beim Tippen
wäre lauter als das Ergebnis. Die Kurzhilfe steht aufklappbar unter dem Suchfeld
(neuer Baustein `assets/suchtext.js`).

### Einstellungen: aus „Standortdaten" werden „Standorte" und „Rettungsmittel"

Ein Menüpunkt trug bisher alles: die Standorte selbst, Rettungsmittel,
Besatzungen, Zielkliniken, Bergwacht. Der Name passte auf keines davon.

* **Standorte** — eigene anlegen und bearbeiten, **vordefinierte** auswählen
  (vorher „zentrale Standorte auswählen"). Und sonst nichts.
* **Rettungsmittel** — was an den ausgewählten Standorten hängt, je Standort ein
  Block, darin je Datenart ein eigener aufklappbarer Abschnitt mit der Zahl der
  Einträge im Kopf.

Der alte Link `?t=stammdaten` führt weiterhin zum Reiter „Standorte" —
Lesezeichen und verschickte Links brechen nicht.

Dazu im selben Zug:

* **„Als Standard" geht jetzt auch für systemweite Standorte.** Die Serverseite
  liess es längst zu; es fehlte allein der Knopf. Ein Konto, das ausschliesslich
  mit vordefinierten Standorten arbeitet — der Regelfall an einer Station —
  konnte damit gar keine Vorbelegung setzen. Die Schaltfläche ist ausserdem so
  groß wie „Bearbeiten" und „Löschen" daneben (sie war es nicht).
* **Namensfeld und Schaltfläche sind nicht mehr überhoch.** Ursache war das
  Ortsfeld daneben: Ohne `align-items` streckt Flexbox alle Kinder auf die Höhe
  des größten. Betraf Standort und Zielklinik gleichermassen. (Die Ausrichtung
  dieser beiden Formulare ist damit noch nicht fertig — siehe Web 7.0.1.)
* **Die Art des Rettungsmittels ist nicht mehr vorbelegt.** „Luftgebunden" stand
  von selbst da, und an einem NEF-Standort war das die falsche Vorgabe, die
  niemand bemerkt — sie fiel erst auf, wenn im Einsatzformular Windenfelder
  erschienen. Ohne Auswahl wird jetzt abgewiesen und gesagt, was fehlt. Die
  Besatzung bleibt freiwillig.
* **Die Eingabe hat einen eigenen Rahmen**, die Schaltfläche steht in der
  Eingabezeile, Rollen und Fähigkeiten darunter mit Abstand und Beschriftung.
  Vorher klebte alles unter der Tabelle, und die Haken sahen aus wie Zubehör der
  letzten Tabellenzeile.
* **Die Art steht nicht mehr ausgeschrieben unter dem Rettungsmittel** — das
  Symbol davor sagt sie, mit Textalternative in `title`/`aria-label`. Übrig
  bleibt, was man dem Symbol nicht ansieht: Rollen und Fähigkeiten.
* **Besatzungsrollen erscheinen nur, wenn es sie am Standort gibt.** Vorher
  standen an einem reinen NEF-Standort vier leere Flugrollen mit vier
  Eingabezeilen. Eine bereits belegte Rolle bleibt sichtbar — sonst käme man an
  eigene Einträge nicht mehr heran.
* **Nach dem Speichern öffnen sich alle Ebenen bis zur Eingabestelle** und die
  Seite springt dorthin. Vorher wurde genau ein Aufklapp-Element geöffnet; mit
  der zweiten Ebene lag es unsichtbar in einem geschlossenen Block.

### Administration

* **„Zentrale Stammdaten" ist ebenso geteilt**: „Standorte systemweit" und
  „Rettungsmittel systemweit", gleicher Aufbau wie in der Kontoansicht.
* **Sicherungen einspielen ist eine Tabelle geworden.** Vorher stand je
  Sicherung ein Kasten mit vollständigem Formular; bei fünf Konten mit je drei
  Sicherungen waren das fünfzehn Kästen über mehrere Bildschirmseiten. Jetzt
  eine Zeile je Sicherung (Zeitpunkt, Herkunft, Umfang, Zustand), die Formulare
  aufklappbar dahinter — für die eine Sicherung, mit der man gerade etwas tun
  will. **An den Sicherheitsabfragen ändert sich nichts:** dieselbe Abtippregel
  für die Zielkonto-Adresse, dieselben Rückfragen vor dem Löschen.
* **Wartungsseite umgestellt.** Zustand zuerst (Schlüsselableitung, Umgebung,
  Aufräumjob), Updatetabelle danach — genau umgekehrt zu vorher. Das ist die
  Auskunft, wegen der man die Seite im Betrieb öffnet; die Tabelle liest man
  einmal vor einem Update.
  * Die **Updatetabelle steht auf dem Kopf**: neueste zuerst. Was ansteht, steht
    am Ende der Datei und war vorher hinter dreissig Zeilen „Bereits
    angewendet" versteckt. Die *Ausführung* bleibt in Katalogreihenfolge — sie
    muss es, weil Migrationen aufeinander aufbauen.
  * **Neue Spalte „Web"**: die Fassung, mit der die Migration ausgeliefert
    wurde. Die Kennung allein sagte das nicht — sie trägt ein Datum, und an
    drei Tagen sind mehrere Fassungen erschienen.
  * **Der Startknopf steht über der Tabelle**, nicht darunter.

### Karte: Satellitenbild als vierte Auswahl

Neben Standard, Wanderkarte und Topographisch jetzt ein Luftbild (Esri World
Imagery, mit der geforderten Quellenangabe in der Attribution). Es zeigt, was
Höhenlinien nicht leisten: ob der Einsatzort auf einer Wiese, im Wald oder auf
einem Parkplatz lag. **Nicht** der Standardlayer — er lädt deutlich größere
Kacheln, und die Karte soll beim Öffnen schnell dastehen. Wie bei den übrigen
Layern werden dabei nur Kartenkacheln zum sichtbaren Ausschnitt geladen, keine
Einsatz- oder Patientendaten.

### Die Null in den Tabellen hat keinen Schrägstrich mehr

Die Zahlenspalten liefen in einer Monospace-Familie. Die tat, was sie sollte —
gleich breite Ziffern —, brachte aber die durchgestrichene Null mit: Consolas,
Cascadia Mono und DejaVu Sans Mono zeichnen sie so, und abschalten lässt sie
sich dort nicht. In einer Uhrzeit sah das aus wie ein Sonderzeichen.

Ersatz ist die Schrift, die die Anwendung ohnehin ausliefert: **Open Sans mit
`tnum`**. Glatte Null, feste Ziffernbreiten — die Spaltenbündigkeit bleibt, sie
kam nie vom Monospace-Zeichen, sondern von der Ziffernbreite. Wo eine
Schreibmaschinenschrift die Aussage *ist* — Kopplungscode,
Wiederherstellungsschlüssel, Phasenkachel —, bleibt sie.

### Backlog Nr. 15 und Nr. 16 erledigt

* **Nr. 15:** `api/suchindex.php` liefert das Feld `edited` nicht mehr. Es war
  totes Nutzdatum — `suche.php` ist der einzige Abnehmer und hat es nie
  ausgewertet. Aus demselben Grund ist `ascent_m` aus `api/mission.php`
  entfallen, seit die Einsatzansicht die Steigung nicht mehr zeigt.
* **Nr. 16:** Die Zeilen der Tagesübersicht sind mit der Tastatur erreichbar
  (`tabIndex`, `role="link"`, Enter und Leertaste) — dieselben drei Zeilen, die
  Suche und Zeitraum-Übersicht seit Web 5.2.0 über `assets/missiontable.js`
  mitbringen. Damit sind alle drei Einsatztabellen ohne Maus bedienbar.

### Unter der Haube

* **Feldkatalog** (`mission_fields.php`): neue Schlüssel `gruppe`,
  `nebeneinander`, `vorbelegt_bei`, `such_label`; neue Reihenfolge entlang des
  Einsatzablaufs. `api/mission.php` liefert je Feld seine Spalte (`col`), damit
  die Anzeige selbst ordnen kann, ohne Felder über ihre Beschriftung
  zurückzuerkennen.
* Die **Tagesübersicht** zeigt ihre Katalogspalten dadurch in neuer Folge:
  Sekundärtransport, Bergwacht, Winde. Ebenso ändert sich die Spaltenfolge im
  Export. **Der Import ist unberührt** — er ordnet über Spaltennamen zu.
* Neuer Baustein `assets/suchtext.js` (boolesche Freitextsuche), neue Funktion
  `EdPat.alterText()` (Alter mit passender Einheit).
* **Keine Migration.** Schema und Daten sind unverändert.

## [Web 6.3.0 / Uhr 1.8.0] — 2026-08-18

**Die Notarzt-Erweiterung ist abgeschlossen.** Vierte und letzte Etappe
(Konzept: `docs/Konzept-Notarzt-Erweiterung.md`): Diensttage lassen sich
zusammenführen, die Uhr schickt eine Dienstkennung, und die Dokumentation ist
auf dem Stand des fertigen Umbaus.

**Keine Migration.** Wie die Etappen 2 und 3 arbeitet auch diese auf dem Schema
der 6.0.0 — `day_refs`, `mission_crew` und die Fremdschlüssel auf `days` liegen
seit damals. Wer auf 6.0.0, 6.1.0 oder 6.2.0 ist, spielt nur die Dateien ein.

### Zwei Diensttage zusammenführen

Wurde die App während **eines** Dienstes versehentlich mehrfach gestartet,
entstehen mehrere Diensttage für einen tatsächlichen Dienst. Sie lassen sich
wieder zu einem machen: **Aktionen → „Anderen Diensttag aufnehmen"** im
Diensttag, der bleiben soll.

Der Einstieg liegt bewusst **im Zieltag** und nicht in der Tagesliste. Damit ist
die Richtung eine Tatsache statt einer Lesart — wichtig, weil der Vorgang
**nicht umkehrbar** ist. Danach zwei Schritte: aus den zeitlich benachbarten
Diensttagen (drei Tage vor und nach diesem) den auszuwählen, der aufgenommen
wird, dann die Vorschau bestätigen. Zu jedem Kandidaten stehen Rettungsmittel,
Standort und die Zahl der Einsätze, Ruhesegmente und Uhr-Kennungen — daran
lassen sich zwei Bruchstücke desselben Dienstes auseinanderhalten.

Danach hängen Einsätze, Ruhesegmente und Uhr-Kennungen am Zieltag, sein Zeitraum
umschließt beide, und die Notizen sind aneinandergehängt. Widersprechen sich die
beiden Tage bei Rettungsmittel, Standort oder Besatzung, wird in der Vorschau
gewählt, was gilt; vorbelegt ist der Tag, der bleibt.

**Was nicht geht, und warum:**

- **Luftgebunden und bodengebunden schließen sich aus.** Ein Einsatz mit
  Windendokumentation verlöre an einem bodengebundenen Diensttag seine Felder.
  Unvereinbare Kandidaten stehen trotzdem in der Liste — gesperrt und mit
  Begründung. Eine fehlende Zeile sähe aus wie ein Fehler der Anwendung.
- **Ein noch nicht zugeordneter Diensttag passt zu beidem** und übernimmt die
  Art des anderen.
- **Kein Weg zurück, kein Papierkorb.** Dort läge ein leerer Tag, dessen
  Wiederherstellung die Einsätze nicht zurückholen könnte — sie hängen dann am
  aufnehmenden Tag.
- **Aufteilen gibt es nicht.**

**Uhr-Kennungen wandern mit.** Ein späterer Upload mit einer Kennung des
aufgenommenen Tages landet dadurch von selbst im Zieltag — ohne Umleitung und
ohne Sonderfall. Genau dafür liegen die Kennungen seit Web 6.0.0 in einer
eigenen Tabelle.

**Papierkorbeinträge wandern ebenfalls mit.** `missions.day_id` steht auf
`ON DELETE SET NULL`; ein zurückgelassener Einsatz verlöre beim Entfernen des
aufgenommenen Tags still seinen Diensttag und wäre verwaist.

### Uhr 1.8.0: Dienstkennung und neutrale Phasen

Die Uhr erzeugt bei „Einsatztag starten" eine **Dienstkennung** (`day_ref`) und
schickt sie an jedem Einsatz und Ruhesegment mit — gleiches Muster wie
`client_ref`, gleiche Idempotenz. Damit sagt sie, **welcher** Dienst gemeint
ist; aus dem Datum allein ließe sich das seit Web 6.0.0 nicht mehr ableiten, weil
mehrere Diensttage auf einem Kalendertag liegen können.

Die Uhr erfährt dabei weiterhin **nichts über die Einsatzart** — die Einordnung
geschieht ausschließlich im Web. Ein von ihr angelegter Diensttag ist neutral,
bis Standort und Rettungsmittel nachgetragen sind.

**Zwei Phasen heißen jetzt neutral:** Phase 3 „Ausrücken" statt „Abflug",
Phase 7 „Ankunft Klinik" statt „Landung KKH". Dieselbe Uhr läuft auch am NEF, wo
weder das eine noch das andere stattfindet. Der Server sagt das seit Web 6.0.0;
die Uhr zieht damit nach. Für die Übertragung ist es folgenlos — der Vertrag
kennt Nummern, keine Beschriftungen.

**Die Rückfallebene bleibt dauerhaft.** Eine Uhr ohne Kennung (1.7.0 und älter)
funktioniert unverändert weiter; ihre Uploads werden über `(Konto, Datum)`
zugeordnet. Ein Update des Webs darf keine Uhr außer Betrieb setzen, die niemand
aktualisiert hat. Auch der Umstieg **mitten im Dienst** ist abgefangen: Eine
unbekannte Kennung wird an den Diensttag gebunden, an dem der Datensatz schon
hängt, statt einen zweiten anzulegen.

Die Connect-IQ-App-ID ist unverändert — die Uhr aktualisiert sich, sie wird
nicht neu installiert.

### Fehleinsatz nicht mehr in der Tagestabelle

Die Spalte **Fehleinsatz** ist aus der Einsatztabelle der Tagesübersicht
entfallen. Der Haken steht im Einsatz selbst; auswerten lässt er sich
unverändert in der Zeitraum-Übersicht (Kachel „Fehleinsätze") und in der Suche.
Er ist selten gesetzt, und eine Spalte voller leerer Zellen kostet auf der
schmalsten Ansicht Breite, ohne etwas zu sagen.

### Dokumentation

Vier Dateien waren noch auf dem Stand vor 6.0.0 und sind es nicht mehr:

| Datei | Was |
|---|---|
| `JSON-Vertrag.md` | steigt auf **1.3**: `day_ref` samt Zuordnungsregeln und Rückfallebene, neutrale Phasenbeschriftungen, Präfix `d-` |
| `Backup-Format.md` | Nutzlastversion **6**: `day_refs`, `day_crew`, `mission_crew`, `day_capabilities`, `vehicles` samt Rollen und Fähigkeiten, `user_bases`, `base_id` der Stammdaten — und die benannte Ablehnung älterer Nutzlasten |
| `Export-Format.md` | **Berichtigung, nicht nur Ergänzung**: `phase_03_abflug` → `phase_03_ausruecken`, `phase_07_landung_krankenhaus` → `phase_07_ankunft_klinik`, dazu die neuen Spalten und die sieben Besatzungsspalten aus dem Rollenkatalog. Die Feldlisten sind jetzt aus `assets/export.js` erzeugt, damit sie nicht wieder abweichen |
| `Handbuch.md` | der fertige Stand: Diensttag statt Flugtag, Rettungsmittel statt Hubschrauber, Transport und Abfahrtort, die Tabs der Auswertung, das Zusammenführen — und der Unterschied zwischen Statistik (nach Diensttag) und Suche (nach echtem Einsatzdatum) |

Auch `Technik.md` ist nachgezogen: Die Schematabelle beschrieb noch `aircraft`,
`missions.day` und den Tagesschlüssel. **Verwaiste Verweise auf „Flugtag",
„Hubschrauber" und eine Phase 10 gibt es in der Dokumentation nicht mehr** —
mit zwei benannten Ausnahmen: den Kacheln des Luftrettungs-Tabs, die ihre
Flugterminologie behalten, und den Stellen, die ausdrücklich sagen, wie etwas
**früher** hieß.

### Betreiberhinweis

Die drei Dateien `flugtag_neu.php`, `flugtag_loeschen.php` und
`flugtag_datum.php` müssen auf dem Webspace **verschwinden**. Sie sind seit
6.0.0 durch die `diensttag_*.php` ersetzt, arbeiten auf dem alten Datenmodell
und waren über die Adresszeile erreichbar; ein FTP-Abgleich entfernt nicht
zwingend, was im Paket fehlt.

## [Web 6.2.0] — 2026-08-18

**Die Auswertung trennt jetzt nach Art — und die Suche kann danach filtern.**
Dritte von vier Etappen der Notarzt-Erweiterung (Konzept:
`docs/Konzept-Notarzt-Erweiterung.md`). Das Zusammenführen von Diensttagen, die
Uhr und die restliche Dokumentation folgen in Etappe 4.

**Keine Migration.** Wie Etappe 2 arbeitet diese auf dem Schema der 6.0.0. Wer
auf 6.0.0 oder 6.1.0 ist, spielt nur die Dateien ein.

### Zeitraum-Übersicht: drei Tabs, sobald beide Arten vorliegen

Monats- und Jahresübersicht teilen sich nach Art auf — aber nur, wenn es etwas
zu teilen gibt:

| Im Zeitraum | Anzeige |
|---|---|
| nur eine Art | keine Tableiste, Ansicht wie bisher |
| beide Arten | **Gemischt** (aktiv), Luftrettung, Bodengebundener Rettungsdienst |

Der Tab filtert die **ganze** Ansicht: Kacheln, Einsatztabelle und Karte. Er
steht im URL-Fragment hinter dem `#` und lässt sich damit verschicken; ein
Fragment wird nicht an den Server gesendet.

**Der Luftrettungs-Tab ist der heutige Bestand, unverändert** — dieselben zehn
Kacheln, dieselben Beschriftungen, dieselbe Flugterminologie. Für eine rein
luftgebundene Nutzung ändert sich an der Auswertung nichts. Die übrigen Tabs
sprechen neutral und führen acht Kacheln, darunter eine neue: **Fehleinsätze**.
Sie fehlt im Luftrettungs-Tab bewusst, obwohl der Haken auch luftgebunden zur
Verfügung steht; in „Gemischt" zählt sie luftgebundene Fehleinsätze mit, damit
die Zahl vollständig bleibt.

**„Gemischt" enthält auch die Diensttage ohne Zuordnung** und sagt das. Die
Summe der beiden Artentabs ist deshalb kleiner — ohne den Hinweis wäre die
Abweichung nicht erklärbar. Er verlinkt auf die Nachbearbeitung.

### Kacheln und Spalten nur bei passendem Bestand

Die **Windenkacheln** erscheinen ausschließlich, wenn im Zeitraum tatsächlich
Windeneinsätze dokumentiert sind — nicht schon, wenn das Rettungsmittel es
könnte. Damit lässt sich „null Windeneinsätze" nicht mehr von „Winde nicht
eingerichtet" unterscheiden; das ist beabsichtigt, weil eine Dauerkachel mit dem
Wert null nur Platz kostet.

Dieselbe Regel gilt jetzt für **Spalten der Einsatztabelle**: Winde, Bergwacht
und der neue Fehleinsatz erscheinen nur, wenn es sie im Bestand gibt. Für
Bergwacht gibt es weiterhin **keine** Kachel.

### Die Art als Symbol in der Einsatztabelle

🚁 luftgebunden, 🚑 bodengebunden, ◌ ohne Zuordnung — dieselben Zeichen wie in
der Tagesleiste, mit Textalternative. Die Spalte erscheint nur, wenn im Bestand
überhaupt mehr als eine Art vorkommt: Bei reiner Luftrettung stünde in jeder
Zeile dasselbe Zeichen.

### Suche: vier neue Filter

**Art** (bei Standort und Rettungsmittel), **Transportart** und
**NA-Begleitung** (bei Transport) sowie **Fehleinsatz** in einem neuen Block
„Einsatz". Der Block erscheint wie die Blöcke Winde und Bergwacht nur, wenn der
Bestand dazu etwas hergibt.

Die Fragment-Kurznamen der neuen Filter sind `art`, `ta`, `nb` und `fe`. Die
bestehenden sind unverändert — verschickte Links bleiben gültig.

### Export: die letzten zwei Spaltennamen aus der Luftrettung

Die Phasen 3 und 7 heißen serverseitig seit Web 6.0.0 „Ausrücken" und „Ankunft
Klinik". Im Export standen weiterhin `phase_03_abflug` und
`phase_07_landung_krankenhaus`; sie heißen jetzt `phase_03_ausruecken` und
`phase_07_ankunft_klinik`. **Der Rückweg bleibt offen:** Der Import erkennt
beide Schreibweisen, eine Exportdatei von gestern lässt sich unverändert wieder
einlesen.

Ebenfalls neutral: die Erläuterung zu `strecke_m` in `felder.csv` (war
„Flugstrecke") und der **Dateiname** des Exports — er beginnt mit
`einsatzdokumentation_export_` statt `luftrettungsdokumentation_export_`. Ein
Archiv mit bodengebundenen Einsätzen hieß sonst nach einer Rettungsart, die
darin nicht vorkommt.

### Entfernt: drei Seiten, die es seit Web 6.0.0 nicht mehr geben sollte

`flugtag_neu.php`, `flugtag_loeschen.php` und `flugtag_datum.php` wurden mit
Web 6.0.0 durch ihre `diensttag_*`-Nachfolger ersetzt — im Repository lagen sie
aber weiter und wurden mitgeliefert. Sie arbeiten auf dem **alten** Datenmodell
und wären beim Aufruf über die Adresszeile in einen Fehler gelaufen. Verlinkt
war keine von ihnen.

**Für die Betreiberin:** Sie müssen auch auf dem Webspace verschwinden. Ein
FTP-Abgleich entfernt nicht zwingend, was im neuen Paket fehlt.

### Unter der Haube

- `api/range.php` liefert je Einsatz die **Art des Diensttags** und den
  Fehleinsatz-Haken sowie die Diensttage **nach Art aufgeteilt**. Die Tabs
  rechnen damit ohne eine zweite Abfrage; der Divisor „Ø Einsätze / Flugtag"
  teilt im Luftrettungs-Tab nur durch luftgebundene Diensttage.
- Die **Artsymbole stehen an einer Stelle** (`dt_art_symbole()`) und gehen von
  dort an den Browser — wie der Rollenkatalog. Eine zweite Liste in JavaScript
  wäre die Stelle, an der beide beim nächsten Symbolwechsel auseinanderlaufen.
- Die **Kacheln entstehen im Browser** statt fest im HTML zu stehen: Welche es
  gibt und wie sie heißen, hängt am Tab und am Bestand.
- Der Suchindex führt die Klartextfelder der Etappe 2, soweit die Suche sie
  auswertet. Zielklinik-Koordinate und Abfahrtortregel bleiben draußen — nach
  ihnen wird nicht gefiltert.

---

## [Web 6.1.0] — 2026-08-18

**Die Einsatzfelder für beide Arten, und eine Karte auch ohne Uhr.** Zweite von
vier Etappen der Notarzt-Erweiterung (Konzept: `docs/Konzept-Notarzt-Erweiterung.md`).
Auswertung nach Art, Suche und das Zusammenführen von Diensttagen folgen in den
Etappen 3 und 4.

**Keine Migration.** Die Spalten dafür hat die Migration der Web 6.0.0 bereits
angelegt — bewusst in einem Zug, damit die späteren Etappen keine zweite
verlangen. Wer auf 6.0.0 ist, spielt nur die Dateien ein.

### Neue Felder am Einsatz

- **Transport** — Auswahl aus Luft, Boden, Ambulant. „Ambulant" heißt: nicht
  transportiert.
- **NA-Begleitung** — Haken unter der Transportart.
- **Fehleinsatz / Storno / Abbruch** — ein Haken, keine Unterauswahl. Er
  erscheint als eigene Spalte in der Tagestabelle.

**Zielklinik und Schockraum hängen jetzt an der Transportart** und entfallen bei
„Ambulant". Wer dort umschaltet, sieht die Felder verschwinden, **bevor**
gespeichert wird — und der Inhalt wird tatsächlich geleert, nicht nur verborgen.
Das ist der Unterschied zu den Feldern, die eine Art oder Fähigkeit ausblendet:
Die behalten ihren Inhalt, weil er gültig bleibt. Eine Zielklinik hinter
„Ambulant" wäre dagegen ein Widerspruch in den Daten.

### Windenfelder erscheinen nur, wo es eine Winde gibt

Ein bodengebundener Dienst zeigt keine Windenfelder mehr, ein Hubschrauber ohne
Bergwachtkooperation keine Bergwachtfelder. Maßgeblich sind die **eingefrorenen
Fähigkeiten des Diensttags**, nicht die heutigen Stammdaten: Wird der
Windenhaken Jahre später am Rettungsmittel entfernt, verlieren dokumentierte
Einsätze weder Daten noch Anzeige. Ein bereits belegtes Feld bleibt immer
sichtbar.

### Abfahrtort und Luftlinie — eine Karte auch ohne GPS-Aufzeichnung

Fällt die Uhr aus oder wird ohne sie gearbeitet, blieb die Karte bisher leer,
obwohl der Einsatzort bekannt war. Es fehlte der Gegenpunkt.

Zu jedem Einsatz lässt sich jetzt ein **Abfahrtort** bestimmen — als Auswahl,
nicht als Adresseingabe:

| Auswahl | Woher die Koordinate kommt |
|---|---|
| Standort des Diensttags | eingefrorene Standortkoordinate |
| Letzter Einsatzort | Einsatzort des vorherigen Einsatzes desselben Dienstes |
| Letzte Zielklinik | Zielklinik des vorherigen Einsatzes |
| Manueller Ort | eigene Adresssuche, wie beim Einsatzort |

Sind Abfahrtort und Einsatzort bekannt, zeichnet die Karte eine **gestrichelte
Luftlinie** mit Pin an jedem Ende und benannter Länge. Hat die Zielklinik
Koordinaten, verlängert sie sich um diesen dritten Punkt.

Drei Dinge, die sie bewusst **nicht** tut:

- **Ein aufgezeichneter Track hat immer Vorrang.** Trifft er später ein, bleibt
  die Abfahrtortangabe gespeichert und wird nur nicht mehr gezeichnet. Fällt er
  weg, erscheint die Linie wieder.
- **Ohne Einsatzort keine Linie** — auch dann nicht, wenn Abfahrtort und
  Zielklinik beide Koordinaten haben. Diese Verbindung hat nie stattgefunden.
- **Kein Ausweichen.** Fehlt die Koordinate der gewählten Quelle, entsteht keine
  Linie. Es wird nicht stillschweigend eine andere genommen.

**Die Luftlinienlänge fließt in keine Kachel und in keinen Filter.** Eine
Luftlinie und eine gefahrene Strecke sind nicht dieselbe Größe; beides in einer
Summe machte „Einsatzkilometer gesamt" unbrauchbar. Wer ohne Uhr dokumentiert,
hat damit weiterhin keine Kilometerzahlen in der Statistik.

**Sichtbarkeit:** Linie und Einsatzort-Pin erscheinen erst nach Freischalten des
Patientendatenschlüssels — ihr mittlerer Stützpunkt ist der Einsatzort. Der
**Zielklinik-Pin** ist davon ausgenommen: Der Klinikname steht ohnehin
unverschlüsselt am Einsatz, seine Koordinate folgt derselben Einstufung.

### Zielkliniken bekommen Koordinaten — auf drei Ebenen

Zentral durch die Administration, im eigenen Konto, und einmalig am Einsatz.
Wird ein Vorschlag übernommen, dessen Stammdatensatz Koordinaten führt, sind sie
vorbelegt und bleiben überschreibbar. Sie werden **am Einsatz eingefroren** und
nicht über den Namen aufgelöst: Ein umbenannter Stammdatensatz verlöre sie
sonst.

Koordinaten sind überall **freiwillig** (Einsatzort, Abfahrtort, Zielklinik).
Ohne sie entstehen lediglich kein Pin und keine Linie — kein Feld wird dadurch
unbrauchbar. Umgekehrt gilt überall dieselbe Regel: Koordinaten **ohne**
Bezeichnung werden abgewiesen, sonst stünde in den Listen ein Zahlenfragment.

### Adresssuche in der Standortpflege

Die Koordinatenfelder bei Standorten und Zielkliniken waren seit Web 6.0.0
zwei Zahlenfelder zum Eintippen. Jetzt steht dort dasselbe Ortsfeld wie am
Einsatz — mit Adresssuche, Plus-Code-Erkennung und Chip. Das Namensfeld bleibt
davon unberührt: Gesucht wird in einem eigenen Feld daneben, weil „Standort
Kempten" keine Adresse ist und die Suche den Namen sonst überschriebe.

### Export, Import und Sicherung

Die neuen Angaben gehen überall mit: `transport_art`, `na_begleitung`,
`fehleinsatz`, `ziel_lat`, `ziel_lon`, `abfahrt_regel` als Klartextspalten,
`pat_start_adresse`/`_lat`/`_lon` im verschlüsselten Teil. Der eigene
CSV-Export bleibt damit der verlustfreie Rückweg.

**Behoben (älter als diese Etappe):** Das Wiedereinspielen einer Sicherung
übernahm die Spalten aus dem Feldkatalog — Koordinatenspalten, die nicht so
heißen wie ihr Feld, und Spalten außerhalb des Katalogs fielen dabei heraus. In
6.0.0 betraf das nichts, weil es solche Spalten noch nicht gab; ab 6.1.0 wären
Zielklinik-Koordinate und Abfahrtortregel beim Rückweg verloren gegangen.
Zusätzlich stand die normalisierte Besatzung noch in dieser Liste: Eine Datei
mit einem Schlüssel `crew_p1` hätte nicht eine Zeile, sondern die **ganze**
Wiederherstellung scheitern lassen.

### Unter der Haube

- **Das Ortsfeld ist eine Komponente** (`assets/ortsfeld.js` + `ui_ortsfeld()`).
  Es war bis dahin über feste Element-Kennungen verdrahtet — rund 180 Zeilen in
  einer Datei. Sechs Verwendungen wären sechs Fassungen geworden.
- **Der Feldkatalog kann drei Dinge mehr:** ein Ortsfeld (`loc`) mit zwei
  Koordinatenspalten, wertabhängige Unterfelder unter einem Auswahlfeld
  (`show_if` — vorher gab es das nur unter Checkboxen), und Auswahlfelder, deren
  gespeicherter Wert nicht die Beschriftung ist.
- **Die Prüfung optionaler Koordinaten steht an einer Stelle**
  (`pruef_ortspaar`). Sie stand vorher zweimal ausgeschrieben und wäre mit
  dieser Etappe ein drittes und viertes Mal entstanden.

---

## [Web 6.0.0] — 2026-08-17

**Aus dem Flugtag wird der Diensttag.** Die Anwendung dokumentiert jetzt auch
bodengebundene Notarzteinsätze (NEF, NAW) — beides in einer Installation, ein
Konto kann beides mischen, auch am selben Kalendertag. Das Produkt heißt künftig
**Einsatzdokumentation Notarzt**.

Diese Auslieferung ist der **Umbau am Datenmodell samt Codeanpassung** (Etappen
1a und 1b des Konzepts). Einsatzfelder, Auswertung nach Art und das
Zusammenführen von Diensttagen folgen in den Etappen 2 bis 4; Grundlage und
Fortschritt stehen in `docs/Konzept-Notarzt-Erweiterung.md`.

### Vor dem Update lesen

- **Die Migration ist zwingend** (`2026_08_17_notarzt_erweiterung`, läuft über
  `server/update.php`). Vorher eine Sicherung ziehen.
- **Danach ein frisches Backup erstellen.** Sicherungen älterer Formatversionen
  werden nicht mehr eingelesen — die Nutzlast steigt von 5 auf 6, und einer
  alten Datei fehlen Angaben, die sich nicht erraten lassen (Kennung des
  Diensttags, Art, Rollensatz, Standortzuordnung, Uhr-Kennungen). Die Ablehnung
  ist ausdrücklich und benannt.
- **Drei Dateien sind umbenannt** und müssen auf dem Webspace verschwinden:
  `flugtag_neu.php`, `flugtag_loeschen.php`, `flugtag_datum.php` heißen jetzt
  `diensttag_*.php`.
- **Die Uhr-App bleibt unverändert** und funktioniert weiter. Sie kennt die
  Einsatzart nicht und braucht sie nicht zu kennen; ihre Uploads landen über die
  dauerhaft bestehende Rückfallebene `(Konto, Datum)` an einem Diensttag.
- **Blockiert die Migration**, steht in `days.crew` noch ein Freitext aus der
  Zeit vor den Rollenspalten. Sie verweigert dann und meldet es, statt zu
  entscheiden — der Inhalt gehört von Hand in die neue Struktur übertragen.

### Der Diensttag löst sich vom Kalendertag

Ein Diensttag hat jetzt eine eigene Kennung, echte Start- und Endzeiten und
**jeder Start erzeugt einen eigenen**. Ein Hubschrauberdienst am Tag und ein
NEF-Nachtdienst am Abend sind damit zwei Diensttage an einem Datum. Einsätze
hängen an der Kennung, nicht mehr am Datum.

Folgen im Alltag:

- Die Leiste links listet Diensttage. Liegen mehrere auf einem Kalendertag,
  steht die Uhrzeit des Dienstbeginns dabei; sonst nicht.
- Die Art erscheint als Symbol am Rettungsmittelnamen — 🚁 luftgebunden,
  🚑 bodengebunden, ◌ noch nicht zugeordnet. Jedes Symbol trägt eine
  Textalternative.
- Beim Umdatieren eines Diensttags gibt es **keine Kollisionsprüfung mehr**: Ein
  belegtes Zieldatum war die Folge des alten Tagesschlüssels und ist jetzt der
  vorgesehene Fall.
- „Einsatz verschieben" wählt einen **vorhandenen Diensttag** aus einer Liste,
  statt ein Datum entgegenzunehmen. Angelegt wird dort nichts mehr.
- **Statistik und Suche gehen bewusst auseinander:** Die Zeitraumübersicht rechnet
  nach Diensttag, die Einsatzsuche filtert nach dem echten Einsatzdatum. Ein
  Einsatz um 01:30 eines Dienstes vom Vortag zählt zum Vortag, ist in der Suche
  aber unter seinem eigenen Datum zu finden. Die Suche zeigt beide Daten.

### Der Standort ist der Anker der Stammdaten

Rettungsmittel, Besatzungs-Vorbelegungen, Zielkliniken, weitere Rettungsmittel
und Bergwacht-Bereitschaften gehören zu **genau einem Standort**. Eine
standortübergreifende Ebene gibt es nicht: Dieselbe Zielklinik an zwei
Standorten wird zweimal angelegt. Der Preis ist Doppelpflege, der Gewinn ein
Modell mit einer Regel statt mit zwei.

Die Standortdaten sind entsprechend gegliedert — erst die Standorte (eigene
anlegen, zentrale auswählen), dann je Standort ein Block mit seinen fünf
Datenarten. Zentrale Standorte erscheinen erst in den Auswahllisten, wenn man
sie auswählt.

Standorte und Zielkliniken haben jetzt optionale Koordinaten. Sie sind
freiwillig; ohne sie entsteht lediglich kein Pin. Die Adresssuche wie beim
Einsatzort folgt in einer späteren Etappe.

### Das Rettungsmittel entscheidet über Rollen und Felder

Aus dem Hubschrauber ist das **Rettungsmittel** geworden, mit einer Art
(luft- oder bodengebunden), angehakten Rollen und — nur luftgebunden — den
Fähigkeiten Winde und Bergwacht.

- Luftrollen unverändert: Pilot 1, Pilot 2, HEMS-TC, Flugretter, Sonstige.
- Bodenrollen: Fahrer, Praktikant, Sonstige. „Sonstige" ist dieselbe Rolle.
- Die Notärztin ist keine Rolle — sie ist die Nutzerin.

### Alles Abgeleitete wird beim Anlegen eingefroren

Art, Rollensatz, Fähigkeiten, Bezeichnungen und Standortkoordinaten werden beim
Zuordnen in den Diensttag **kopiert**. Wird ein Rettungsmittel später umbenannt,
bearbeitet oder gelöscht, ändert sich an bereits dokumentierten Diensttagen
**nichts** — auch nicht bei einem Tippfehler im Namen. Wer eine alte Bezeichnung
korrigieren will, tut das am Diensttag selbst.

Das gilt in beide Richtungen: Wird der Windenhaken Jahre später entfernt,
verlieren alte Einsätze ihre Windendokumentation nicht.

### Ein Diensttag ohne Zuordnung funktioniert

Ein von der Uhr angelegter Diensttag ist zunächst **neutral**: keine Art, keine
Rollen, keine artabhängigen Felder. Zeiten, Phasen, Track und
Reanimationsdokumentation werden trotzdem vollständig erfasst. Wird die
Zuordnung nachgetragen, erscheinen Rollen und Felder — ohne dass zuvor Erfasstes
verloren geht.

### Nachbearbeitung: die zwei Zuordnungen, die niemand erraten kann

Eine neue Seite (`nachbearbeitung.php`) zeigt, was die Migration nicht ableiten
konnte: Diensttage ohne Standort oder Rettungsmittel und Stammdatensätze ohne
Standort. Sie erscheint in der Leiste links, **solange etwas offen ist**, und
verschwindet danach von selbst. Erst wenn kein Stammdatensatz mehr offen ist —
in keinem Konto —, macht eine Administratorin den Standortbezug dort verbindlich
(`base_id` bekommt `NOT NULL`). Danach stimmen aktualisierte Installation und
Neuinstallation vollständig überein.

### Weiteres

- **Phasenbeschriftungen neutral:** Phase 3 heißt „Ausrücken" (war „Abflug"),
  Phase 7 „Ankunft Klinik" (war „Landung Krankenhaus"). Nummerierung und
  Bedeutung unverändert; die Uhr folgt in einer späteren Auslieferung und zeigt
  bis dahin die alten Wörter. Rein kosmetisch — übertragen werden Nummern.
- **Einsatztabelle, Suche und Export sprechen neutral.** „Flug km" heißt „km",
  „Hubschrauber" heißt „Rettungsmittel", „Flugtag" heißt „Diensttag". Die
  Kacheln der Zeitraumübersicht behalten die Flugterminologie — sie werden in
  Etappe 3 nach Art geteilt.
- **Exportformat:** Das Blatt „Flugtage" heißt „Diensttage" und führt Kennung,
  Dienstbeginn, Dienstende, Art und Fähigkeiten. Die Besatzungsspalten entstehen
  aus dem Rollenkatalog; die fünf Flugrollen behalten ihre Spaltennamen, damit
  der verlustfreie Rückweg über den CSV-Import bestehen bleibt. Alte Kopfzeilen
  („flugtag", „hubschrauber", „Flugkilometer") werden beim Import weiterhin
  erkannt.
- **Import:** Je Kalendertag wird höchstens ein Diensttag neu angelegt. Aus einer
  Tabelle lässt sich nicht ableiten, ob zwei Einsätze desselben Datums zu einem
  oder zu zwei Diensten gehören; wer sie trennen will, tut das danach über
  „Aktionen → Verschieben".
- **Uhr-Kennungen:** Der Diensttag kann mehrere tragen (`day_refs`). Damit findet
  ein späterer Upload auch einen Diensttag, der inzwischen in einen anderen
  aufgenommen wurde — und ein Backup bringt die Kennungen mit zurück, sodass
  nach einer Wiederherstellung kein Diensttag doppelt entsteht.
- **Zwei kleine Berichtigungen am Rand**, beide älter als diese Auslieferung:
  Die Adminsicherung zählte die Ruhesegmente unter dem falschen Schlüssel und
  meldete deshalb immer 0. Und die Wartungsseite schrieb bei jedem Aufruf zwei
  PHP-Warnungen je verbuchter Migration ins Fehlerprotokoll — angezeigt wurde
  trotzdem das Richtige, deshalb war es nie aufgefallen.

## [Web 5.10.0] — 2026-08-17

Feinschliff an der Oberfläche: **weniger Spalte, weniger Filter, weniger
Zeilen — und zwei Auskünfte, die es vorher erst nach dem Absenden gab.** Keine
Schemaänderung, keine Migration.

### Die Spalte „abw. Crew" ist wieder weg

Sie war seit Web 5.4.0 in der Tagesübersicht zu sehen. Im täglichen Gebrauch
trug sie nichts bei: An den allermeisten Tagen steht der Haken in keiner
einzigen Zeile, und Breite kostete sie trotzdem — in einer Tabelle, die auf
schmalen Geräten ohnehin knapp ist. Wo die abweichende Besatzung wirklich
interessiert, steht sie vollständig: in der Einsatzansicht unter „Besatzung",
mit „(abw.)" an der betroffenen Rolle. **Das Feld selbst bleibt unverändert**,
ebenso im Export und im Backup.

Umgesetzt durch das Streichen zweier Schlüssel in `mission_fields.php`.
Tabellenkopf, Zeilenaufbau, Sortierung und der `SELECT` in `api/day.php` zogen
von selbst nach — die Gegenprobe zu Backlog Nr. 10, diesmal rückwärts.

Bei der Gelegenheit entfernt: die Spaltenbreiten nach Position
(`#missions th:nth-child(…)`) im Stylesheet. Sie waren wirkungslos, weil die
Klassenregeln später stehen, aber sie zählten Spalten ab — genau die Sorte
Regel, die beim Streichen einer Spalte still auf die falsche rutscht.

### Flugtag-Aktionen stehen jetzt oben rechts

„Datum ändern" und „Tag löschen" standen als zwei Schaltflächen unter der
Tabelle, in einer Reihe mit „+ Einsatz nachtragen" — das Alltagsgeschäft neben
zwei Eingriffen in den Bestand. Auf der Einsatzseite ist diese Trennung seit
Web 5.6.0 gezogen; die Flugtagübersicht folgt ihr jetzt mit **demselben
Bauteil**: ein Menü **Aktionen** oben rechts, vollständig mit der Tastatur
bedienbar, Escape schliesst.

Das Verhalten (Schliessen daneben und mit Escape) steht dafür neu in
`assets/aktionsmenu.js` statt zweimal in je einem `<script>`. Es bindet jedes
`details.aktionsmenu` der Seite von selbst.

### Beim Verschieben steht jetzt da, wohin der Einsatz wandert

Unter dem Datumsfeld in `einsatz_verschieben.php` steht ab sofort, **welcher
Flugtag am gewählten Datum liegt** — Maschine, Standort und Zahl der Einsätze —
oder dass dort noch keiner angelegt ist und einer entsteht. Liegt am Zieldatum
ein Flugtag im **Papierkorb**, sagt die Seite das vorher: Er belegt sein Datum
weiterhin, und das Verschieben würde abgelehnt.

Ausdrücklich benannt ist dabei, was vorher offenblieb: Je Kalendertag gibt es
**genau einen** Flugtag (`days` trägt `UNIQUE KEY uq_user_day`). Das Datum
bestimmt den Zieltag also eindeutig; eine Auswahl zwischen mehreren Tagen
desselben Datums kann es nicht geben.

### Ein belegtes Zieldatum meldet sich vor der Rückfrage, nicht danach

Die Kollisionsprüfung der Umdatierung (E2) sass allein in
`tz_tag_datum_aendern()` — also hinter dem Absenden **und** hinter der
Rückfrage „Alle Zeitstempel wandern mit. Fortfahren?". Wer sie bejahte, bekam
als Antwort, dass gar nichts geschehen ist. `flugtag_datum.php` sagt jetzt
unter dem Feld, ob das gewählte Datum frei ist.

Beide Auskünfte sind **rein anzeigend**. Der Server bleibt massgeblich: Die
Listen sind gedeckelt und veralten in dem Augenblick, in dem in einem zweiten
Fenster etwas angelegt wird. Wo der Deckel gegriffen hat, sagt die Auskunft
nichts, statt etwas Falsches zu sagen.

### Winde und Bergwacht erscheinen nur, wenn es sie im Bestand gibt

Beides ist Sache eines Teils der Standorte. Wer nie windet, hatte trotzdem
sechs Winden-Felder in der Filterspalte stehen — Filter, die dauerhaft null
Treffer ergeben. Die beiden Blöcke fallen jetzt weg, wenn kein einziger Einsatz
des Bestandes sie trägt. Geprüft wird der **gesamte** Bestand, nicht die
aktuelle Trefferliste, damit die Spalte beim Tippen nicht hüpft. Setzt ein
geteilter Link einen Filter aus einem dieser Blöcke, bleibt er sichtbar — ein
gesetzter, aber unauffindbarer Filter wäre das schlechtere Ergebnis.

### Die Trefferliste der Suche zeigt 200 Zeilen auf einmal

Vorher gab es keine Grenze: Beim Öffnen stand der gesamte Bestand als Tabelle
da, und **jeder Tastendruck** im Suchfeld baute ihn neu auf. Bei einigen tausend
Einsätzen wurde daraus eine spürbare Pause zwischen Anschlag und Anzeige —
bezahlt für Zeilen, die niemand ansieht.

Begrenzt ist nur die **Anzeige**. Gefiltert, sortiert und gezählt wird
weiterhin über den vollständigen Bestand; die Zeile über der Tabelle nennt
unverändert die wahre Trefferzahl und dazu, wie viele davon gerade stehen.
Unter der Tabelle liegt das Nachladen („Weitere 200 anzeigen" / „Alle N
anzeigen") — sichtbar nur, wenn wirklich etwas fehlt. Ein Sortierwechsel nimmt
eine erweiterte Ansicht nicht zurück; ein neues Filterergebnis fängt wieder bei
der ersten Seite an.

Die Seitengrösse ist eine Option von `EdMissionTable.erzeuge()`
(`assets/missiontable.js`). Ohne sie zeichnet die Tabelle wie bisher jede Zeile
— die Zeitraum-Übersicht ist deshalb unverändert.

## [Web 5.9.0] — 2026-08-16

Block A8 der Verbesserungsrunde Web: **Admin-Sicherungen als Rückfallebene**.
Administration kann Konten sichern und wiederherstellen, ohne Einblick in die
Daten zu erhalten. **Schemaänderung mit Migration** — bitte nach dem Aufspielen
die Wartung aufrufen.

### Jedes Konto bekommt eine Kontokennung

Neue Spalte `users.account_key`, einmalig bei der Kontoanlage vergeben und
danach unveränderlich. Sie ist der Ordnername der Sicherung. Weder die
E-Mail-Adresse (sie ändert sich, sie ist personenbezogen, sie bringt
Zeichenprobleme mit) noch `users.id` (der Zähler kann nach einem Serverneustart
zurückfallen — ein neues Konto könnte den Ordner eines gelöschten erben) taugen
dafür. Die Migration trägt die Kennung für alle Bestandskonten nach; ein
zweiter Lauf ändert nichts.

### Sicherungen liegen im Dateisystem, nicht in der Datenbank

Unter `server/sicherungen/`, ein Ordner je Konto. Eine Sicherung, die im selben
Behälter liegt wie das Gesicherte, ist keine Rückfallebene — und ein Paket liegt
bei größeren Beständen im zweistelligen MB-Bereich, während `max_allowed_packet`
auf geteiltem Webspace oft bei 16 MB festliegt.

Zwei Schranken gegen den Abruf über den Browser: eine `.htaccess` mit
`Require all denied`, die bei jedem Schreibzugriff nachgelegt wird, und der
Ordnername selbst — die zufällige Kontokennung ist nicht zu erraten. **Der
Ordner steht in der `exclude`-Liste des Deploys**; ohne diesen Eintrag hätte die
nächste Auslieferung alle Sicherungen gelöscht.

### Auslösung ausschliesslich von Hand

Auf dieser Installation läuft kein Cron. Sichern lässt sich je Konto, für eine
Auswahl oder für alle in einem Zug. Je Konto bleiben höchstens **drei**
Sicherungen; die vierte verdrängt die älteste. **Keine Altersgrenze** — sie
würde genau die letzte vorhandene Sicherung entfernen, wenn lange keine neue
erzeugt wurde, also in der Lage, in der man sie braucht. Eine Erinnerung mit
einstellbarem Intervall erscheint in der Administration.

### Zurückspielen: der Vergleich der Kennungen entscheidet

- **Kennungen stimmen überein** — dasselbe Konto besteht weiter. Administration
  spielt unmittelbar ein, ohne Zutun der NutzerIn.
- **Kennungen weichen ab** — das Konto wurde neu aufgesetzt. Unmittelbares
  Einspielen ist **gesperrt**; die geschützten Angaben hängen an einem
  Inhaltsschlüssel, den nur der Wiederherstellungsschlüssel öffnet. Administration
  gibt die Sicherung stattdessen für ein Zielkonto frei, und die NutzerIn spielt
  sie im eigenen Backup-Bereich ein — ihr Browser schlüsselt dabei um. Eine noch
  nicht eingelöste Freigabe lässt sich widerrufen.

Vor jeder Rückspielung ist die E-Mail-Adresse des **Zielkontos** abzutippen,
serverseitig geprüft. Das Risiko ist nicht Datenverlust — eine Rückspielung
ergänzt und ersetzt nicht —, sondern das Einspielen fremder Daten in ein
falsches Konto. Die Rückmeldung nennt angelegte und übersprungene Einträge, die
übersprungenen nach Gründen getrennt.

### Verwaiste Sicherungen sind der eigentliche Anwendungsfall

Die Übersicht führt zwei Quellen zusammen: bestehende Konten aus der Datenbank
und **Ordner, zu deren Kennung kein Konto mehr existiert**. Genau diese wären in
einer Liste aus der Datenbank unsichtbar — dabei sind sie der Grund, aus dem es
die Funktion gibt. Name und Adresse stammen dort aus der Begleitdatei im Ordner.
Ein Ordner ohne lesbare Begleitdatei wird mit Hinweis aufgeführt, nicht
stillschweigend übergangen.

### Löschen, mit abgestufter Härte

Bleibt danach noch eine weitere Sicherung desselben Kontos, genügt die übliche
Rückfrage — die Löschung ist folgenlos. Ist es die letzte oder gehört sie zu
einem verwaisten Ordner, ist zusätzlich die E-Mail-Adresse abzutippen. Ist die
Begleitdatei unlesbar, tritt eine ausdrückliche Bestätigung an ihre Stelle.
Kein Papierkorb: Ein Papierkorb für Sicherungen wäre eine weitere Kopie
derselben Daten, die genau dann noch existiert, wenn jemand sie loswerden wollte.

### Die Kontolöschung entscheidet ausdrücklich über die Sicherungen

Die Löschmaske hat ein Auswahlfeld „Sicherungen dieses Kontos" bekommen,
vorbelegt mit **mitlöschen**. Der Warntext sagt nicht länger unbedingt zu, dass
nach der Löschung nichts mehr lesbar ist, sondern bindet diese Aussage an die
getroffene Wahl — sonst wäre sie unwahr geworden, sobald Sicherungen existieren.
Lassen sich die Sicherungen nicht entfernen, wird das Konto **nicht** gelöscht.

### Grenzen — ausdrücklich benannt

Das Verfahren ist eine Rückfallebene gegen selbstverschuldete Probleme im Konto,
**kein Schutz gegen Kontoverlust**. Ohne den Wiederherstellungsschlüssel ist ein
neu aufgesetztes Konto nicht wiederherstellbar. Beides steht im Handbuch.

### Kleinigkeiten

- Administration sieht zu keinem Zeitpunkt Klartext: In der Sicherung stecken
  die geschützten Angaben als Chiffretext, die Oberfläche zeigt nur Zeitpunkt,
  Anzahl und Größe.
- Die Kontokennung erscheint an keiner Stelle der Oberfläche — auch nicht in
  verborgenen Formularfeldern. Dort steht ein Handgriff, aus dem sich die
  Kennung nicht zurückrechnen lässt.
- `schema.sql` führte zwei Migrationen nicht in seiner Liste
  (`2026_08_13_zeitzonen_umstellung`, `2026_08_14_geraetename_ohne_datum`),
  obwohl der Kopf von `update.php` es verlangt. Nachgetragen.

## [Web 5.8.0] — 2026-08-16

Block A9 der Verbesserungsrunde Web. Die Einschränkung des Exports wird von den
Patientendaten auf **personenbezogene Angaben insgesamt** erweitert — und der
Rückweg über den Import so abgesichert, dass eine solche Datei im Bestand nichts
löscht. Keine Schemaänderung, keine Migration.

### Der Haken heißt jetzt „Personenbezogene Angaben einschließen"

Der Export kannte genau eine Schranke, und sie hieß „Patientendaten
einschließen". Alles andere ging immer mit — auch Angaben, die nicht dem
Patienten gehören, aber trotzdem einer Person. Ohne den Haken fehlen jetzt
zusätzlich:

- die **Besatzung**, die des Flugtags und die tatsächliche des Einsatzes,
  auch im Blatt *Flugtage*,
- **bw_info** („Bergwacht: Namen / Infos") und **other_ema** (anderer Notarzt),
- die **Notizen** von Einsatz und Flugtag,
- die **Koordinaten der Phasen**, die **Höhe des Einsatzortes** und die
  **GPX-Tracks**.

### Der Einsatzort stand bisher in einer anderen Spalte

Das war der Anlass. Phase 4 ist „Ankunft Einsatzort", Phase 5 „Ankunft
PatientIn" — diese Koordinaten *sind* der Einsatzort, und sie waren nicht
eingeschränkt, während `pat_ort_lat/lon` es war. Ein Export „ohne
Patientendaten" nannte den Ort also trotzdem. Dasselbe galt für die GPX-Spuren,
die dort enden, und für die aus dem Ort gerechnete Höhe.

### Drin bleiben, jeweils einzeln entschieden

Transportziel und Bergwacht-Einheit (Einrichtungen, keine Personen), weitere
Rettungsmittel (Organisationskennungen), der Verlauf einer Reanimation ohne
Angabe, wen sie betraf, die Zeitpunkte der Phasen (sie tragen Alarmzeit, Endzeit
und Dauer) und der Haken „abweichende Besatzung" — er sagt nur, *dass* sie
abwich. Die Entscheidungen stehen im Handbuch, damit sie nicht als Versehen
gelesen werden.

### Der Dateiname sagt, was drin ist

`mit-pers` bzw. `ohne-pers` statt `mit-pat`/`ohne-pat`. Der Dateiname ist die
Angabe, die man noch sieht, wenn die Datei längst in einem fremden Ordner liegt
— `ohne-pat` an einer Datei mit Besatzungsnamen wäre dort die falsche Auskunft.
Ältere Dateien behalten ihren Namen; für sie war er richtig.

### Ein Rückimport löscht keine Besatzung mehr

Beim Überschreiben eines vorhandenen Einsatzes setzte der Import bisher jede
Spalte unbedingt — ein leerer Wert aus der Datei kam als leeres Feld im Bestand
an. Wer eine Datei ohne personenbezogene Angaben zurückspielte und dabei
„überschreiben" wählte, hätte Besatzung, Notizen, Bergwacht-Infos und den
anderen Notarzt verloren. Diese Felder werden jetzt nur noch gesetzt, wenn die
Datei etwas liefert; die Phasenkoordinaten überleben das Ersetzen ebenfalls.
Der Preis: Ein solches Feld lässt sich per Import nicht mehr gezielt leeren —
das geht im Einsatzformular.

### Kleinigkeiten

- Die Schranke wirkt serverseitig: `api/export_data.php` liefert die
  betroffenen Felder gar nicht erst aus und weist Trackanfragen ohne den Haken
  ab. `assets/export.js` entfernt sie ein zweites Mal, bevor eines der Profile
  den Bestand sieht.
- `felder.csv` im CSV-Archiv hat eine Spalte `personenbezogen` bekommen. Damit
  ist am Archiv selbst ablesbar, welche leeren Zellen leer *gemacht* wurden.
- Die GPX-Wahl verschwindet ohne den Haken, mit Angabe des Grundes an ihrer
  Stelle.
- Der Hinweis unter dem Passwortkästchen (Web 5.7.0) sagte, die Datei sei ohne
  Patientendaten „trotzdem personenbezogen". Das stimmt nicht mehr; er benennt
  jetzt, was tatsächlich bleibt — Betriebsangaben wie Zeiten, Transportziele und
  Rettungsmittel. Vorbelegt bleibt der Schutz.

## [Web 5.7.0] — 2026-08-16

Sechster Block der Verbesserungsrunde Web (A6 „Einstellungen, Import, Export").
Vier kleinere Unstimmigkeiten auf den Nutzerseiten. Keine Schemaänderung, keine
Migration.

### Der Pfadverweis auf der Backup-Seite ist weg

Der Text zum Backup nannte `docs/Backup-Format.md` als Formatbeschreibung. Für
Nutzende ist dieser Pfad nicht erreichbar — er zeigt in das Quellverzeichnis
des Projekts, nicht auf den Server. Der Satz ist entfallen; die Datei und die
Verweise darauf im Code bleiben, sie richten sich an Entwicklerinnen.

### Kästchen und Text stehen auf einer Linie

Auf der Backup-Seite saß das Kästchen von „Mein Kontopasswort verwenden" höher
als der Text daneben. Die Ursache war nicht ein falscher Wert, sondern ein
fehlender: Die Klasse `.check` stand im Markup, hatte im Stylesheet aber
**überhaupt keine Regel**. Sie teilt sich jetzt eine Regel mit `.checklabel`,
die dasselbe schon richtig machte — beide stehen nebeneinander, damit sie nicht
wieder auseinanderlaufen. Wirkt überall, wo `.check` verwendet wird.

### Beim Export verschwinden die Zeitraumfelder, statt auszugrauen

Wählt man beim Export „Alles", waren die Felder „Von" und „Bis" bisher sichtbar
und ausgegraut. Ein Feld, das dasteht und sich nicht bedienen lässt, wirft die
Frage auf, was daran kaputt ist — beantwortet ist sie eine Zeile darüber, in
der Zeitraumwahl. Jetzt verschwindet die Zeile. Die Felder bleiben im Formular
und bleiben zusätzlich `disabled`, damit die Auswertung unverändert bleibt und
kein verborgenes Feld eine Browserprüfung auslösen kann, die niemand sieht.

### Der Passwortschutz ist vorbelegt

„Mit Passwort schützen" ist beim Öffnen der Exportseite angehakt, die
Passwortfelder sind sichtbar. In dieser Datei stehen die geschützten Angaben im
Klartext; der Schutz ist der Normalfall. Abwählen bleibt möglich — die
Vorbelegung dreht nur um, welche der beiden Entscheidungen man bewusst treffen
muss.

**Kein selbsttätiges Abschalten.** Wird „Patientendaten einschließen"
abgewählt, erscheint stattdessen ein Hinweis: Die Datei enthält dann keine
Patientendaten, ist aber weiterhin personenbezogen — Besatzungsnamen,
Bergwacht-Angaben, anderer Notarzt, Notizen und über die Phasen die Koordinaten
des Einsatzortes bleiben enthalten. Wer den Schutz weglässt, soll das
entscheiden und nicht als Nebenwirkung eines anderen Hakens erleben.

### Geändert

* `server/einstellungen.php` — Pfadverweis entfernt.
* `server/assets/style.css` — `.check` teilt sich die Regel mit `.checklabel`.
* `server/import.php` — Kennung für die Zeitraumzeile, Vorbelegung des
  Passwortschutzes, Hinweistext.
* `server/assets/export.js` — Zeitraumzeile ausblenden, Hinweis steuern.
* `server/version.php`, `docs/Handbuch.md`.

## [Web 5.6.0] — 2026-08-16

Fünfter Block der Verbesserungsrunde Web (A5 „Tages- und Einsatzzuordnung").
Keine Schemaänderung, keine Migration. Der Block macht Fehlzuordnungen
korrigierbar — und ist zugleich Vorarbeit für den Betrieb mit mehreren
Diensten je Kalendertag.

### Zwei Fehler, die bisher niemand beheben konnte

Bis hierher war die Tageszugehörigkeit endgültig. Das Datumsfeld im
Einsatzformular ist beim Bearbeiten `readonly`, und für das Datum eines ganzen
Flugtags gab es überhaupt keinen Weg. Wer sich beim Nachtragen vertan hatte
oder wessen Uhr falsch gestellt war, konnte den Fehler nur durch Löschen und
neu Erfassen beseitigen — samt GPS-Track, den es dann nicht mehr gibt.

Die beiden Fälle sehen sich ähnlich und sind es ausdrücklich nicht:

* **Ein einzelner Einsatz gehört zum falschen Tag** — seine Uhrzeiten stimmen.
  Typisch beim Nachtragen eines Dienstes über Mitternacht. Dafür gibt es jetzt
  auf der Einsatzseite **Aktionen → Verschieben**. Die Uhrzeiten bleiben
  unangetastet.
* **Die Uhr war falsch gestellt** — dann sind Datum *und* Uhrzeit falsch.
  Dafür gibt es in der Tagesübersicht **Datum ändern**. Hier wandern alle
  Zeitstempel mit.

Beide Seiten benennen den Unterschied und verweisen aufeinander. Wer den
falschen Weg öffnet, sieht das, bevor er handelt.

### Einsatz verschieben

Eine eigene Seite statt eines still freigeschalteten Datumsfeldes: Die
Nebenwirkung — der Einsatz wechselt die Tageszugehörigkeit — wäre an einem
plötzlich beschreibbaren Feld nicht zu sehen.

Existiert am Zieldatum noch kein Flugtag, wird einer angelegt, mit der
Standard-Vorbelegung für Standort und Maschine. Alles andere zwänge dazu, vor
dem Verschieben von Hand einen Tag anzulegen. Liegt der Zieltag im Papierkorb,
wird abgelehnt statt still wiederhergestellt — dieselbe Haltung wie beim
Speichern eines Flugtags.

Ein späterer Upload derselben Uhr zieht den Einsatz **nicht** zurück: Der
Upsert in `ingest.php` schreibt die Spalte `day` nicht mit.

### Datum eines Flugtags ändern

Verschoben werden `days`, `missions` und `rest_segments` samt allem, was daran
hängt: Phasenzeiten, Reanimationsprotokolle und die GPS-Spurpunkte — letztere
in der Unix-Epoche statt als Zeitstempel, und deshalb leicht zu übersehen.
Alles davon geschieht in **einer** Transaktion; ein Abbruch in der Mitte
hinterlässt den Tag unverändert am alten Datum.

Verschoben wird um den Abstand der beiden **Ortsmitternachte**, nicht um
`Tage × 86400` Sekunden. Der Unterschied wird genau dann sichtbar, wenn die
Verschiebung über eine Zeitumstellung läuft: Eine feste Sekundenzahl verschöbe
dann jede dokumentierte Uhrzeit um eine Stunde. So bleibt sie stehen — und die
abgelesene Uhrzeit ist das, was jemand dokumentiert hat.

Liegt am Zieldatum bereits ein Einsatztag, wird abgelehnt. Zusammengeführt wird
nicht: Das würfe Fragen zu widersprüchlichen Tagesangaben auf (Standort,
Maschine, Besatzung), die sich nicht automatisch beantworten lassen. Ein Tag im
**Papierkorb** belegt sein Datum dabei ebenso — `days` trägt einen eindeutigen
Schlüssel auf (Konto, Datum), und ein übergangener Papierkorb-Eintrag wäre ein
Datenbankfehler statt einer lesbaren Meldung.

Vor der Ausführung nennt die Seite, was betroffen ist: Zahl der Einsätze, der
Ruhesegmente, der Trackpunkte — Papierkorb-Einträge getrennt ausgewiesen, denn
sie wandern mit. Danach kommt die übliche Rückfrage.

### Ein Menü statt zweier Schaltflächen

Die Einsatzseite trug oben rechts **Bearbeiten** und **Löschen**. Mit
„Verschieben" wären es drei Schaltflächen nebeneinander geworden; stattdessen
gibt es jetzt ein Menü **Aktionen** mit diesen drei Einträgen. Gebaut aus
`<details>`/`<summary>`, damit die Tastaturbedienung vom Browser kommt und
nicht halb nachgebaut wird: Tabulator auf den Kopf, Enter oder Leertaste
öffnet, Tabulator läuft weiter durch die Einträge, Escape schließt und gibt den
Fokus zurück.

### Geändert

* `server/tageszuordnung_lib.php` — neu: beide Handlungen samt Prüfungen und
  Transaktion.
* `server/einsatz_verschieben.php`, `server/flugtag_datum.php` — neu.
* `server/einsatz.php` — Aktionsmenü, Rückmeldung nach dem Verschieben.
* `server/index.php` — „Datum ändern" in den Tagesaktionen, Rückmeldung nach
  dem Umdatieren.
* `server/assets/style.css` — Darstellung des Aktionsmenüs.
* `server/version.php`, `docs/Technik.md`, `docs/Handbuch.md`.

## [Web 5.5.0] — 2026-08-16

Vierter Block der Verbesserungsrunde Web (A4 „Einsatzformular"). Keine
Schemaänderung, keine Migration — `resus_sessions` und `resus_events` gibt es
seit Web 2.x, sie waren im Formular nur nicht erreichbar.

### Reanimationszeiten lassen sich nachtragen

Bis hierher konnten `resus_sessions` und `resus_events` **ausschließlich** von
der Uhr befüllt werden. Wer einen Einsatz von Hand nachtrug — weil die Uhr
nicht lief, weil sie ausfiel, weil der Einsatz aus einem Import stammt —,
konnte die Reanimation nicht dokumentieren. Genau die Einsätze, bei denen am
meisten passiert, waren die, bei denen am wenigsten zu erfassen war.

Das Formular hat jetzt einen Abschnitt **Reanimation**: Reanimationsbeginn und
darunter beliebig viele Ereignisse aus der bekannten Liste (Zugang,
Adrenalingabe, Rhythmuskontrolle, Defibrillation, Intubation, Amiodaron,
Sonographie, ROSC, Tod). Mehrere Reanimationen je Einsatz sind möglich — das
Datenmodell sah sie ohnehin vor. Jede Reanimation und jedes Ereignis lässt sich
einzeln wieder entfernen; beim Bearbeiten steht der vorhandene Bestand
vorbelegt da.

Zwei Regeln, beide von den Phasen übernommen: Eine Zeile ohne Uhrzeit ist kein
Ereignis und wird nicht gespeichert, und eine Zeit, die vor ihrer Bezugszeit
liegt, gehört dem Folgetag. Bezug ist beim Beginn die Alarmierung, bei jedem
Ereignis das vorhergehende — eine Reanimation von 23:50 bis 00:20 landet damit
richtig herum in der Datenbank. Gespeichert wird UTC, wie überall.

In der Einsatzansicht sind die so eingetragenen Zeiten von denen der Uhr nicht
zu unterscheiden: Es sind dieselben Tabellen und derselbe Endpunkt. Ein über
das Formular gespeicherter Einsatz trägt außerdem `manual = 1`; eine
nachliefernde Uhr überschreibt die Eingaben also nicht. (Backlog Nr. 1)

### Abweichende Besatzung nimmt jetzt Freitext an

Die fünf Felder unter „Abweichende Besatzung" waren reine Auswahlfelder über
die Vorbelegungen. Wer aushilft, steht dort aber oft nicht — und genau dieser
Fall ist der Anlass, überhaupt eine abweichende Besatzung einzutragen. Aus den
Auswahlfeldern sind Textfelder mit Vorschlagsliste geworden: Die Vorbelegungen
der jeweiligen Rolle erscheinen weiterhin, persönliche wie zentrale, aber jeder
andere Name lässt sich ebenfalls eintragen.

Ein Nebeneffekt, der vorher ein Ärgernis war: Ein gespeicherter Name, der
später aus den Stammdaten verschwindet, kann jetzt gar nicht mehr verloren
gehen — er steht einfach im Feld. Am Rollenfilter ändert sich nichts: Ein Feld,
das die Maschine des Flugtags nicht vorsieht, bleibt versteckt, ein bereits
belegtes bleibt sichtbar.

### Abbrechen — in beiden Formularen, in beiden Zuständen

Das Einsatzformular bot einen Abbrechen-Weg **nur** beim Bearbeiten. Wer einen
Einsatz nachtrug und es sich anders überlegte, kam nur über die Seitenleiste
oder den Zurück-Knopf des Browsers heraus. Beide Formulare — Einsatz und
Flugtag — haben den Weg jetzt in jedem Zustand.

Die Rückfrage erscheint **nur, wenn tatsächlich etwas eingegeben wurde**. Ein
unverändertes Formular zu verlassen fragt nichts; eine Rückfrage, die immer
kommt, wird weggeklickt und schützt dann auch dort nicht mehr, wo etwas zu
verlieren wäre. Woran „eingegeben" erkannt wird, ist dasselbe Kennzeichen, das
die Verlassen-Warnung des Browsers ohnehin führt. Das Ziel des Abbruchs ist
fest: beim Bearbeiten der Einsatz, beim Nachtragen die Tagesansicht, beim
Flugtag die Übersicht.

### Geändert

* `server/einsatz_form.php` — Abschnitt Reanimation (Anzeige, Einlesen,
  Speichern), Vorschlagsquellen `crew:<rolle>`, Abbrechen in beiden Zuständen.
* `server/mission_fields.php` — die fünf Crew-Felder von `select` auf `text`
  mit `suggest_src`; `suggest_src` im Kopfvertrag erweitert.
* `server/flugtag_neu.php` — Abbrechen mit Rückfrage.
* `server/assets/forms.js` — `data-cancel-form`, `window.EdForms`.
* `server/assets/style.css` — Darstellung der Reanimationszeilen.
* `server/version.php`, `docs/Backlog.md`, `docs/Technik.md`,
  `docs/Handbuch.md`.

## [Web 5.4.0] — 2026-08-16

Dritter Block der Verbesserungsrunde Web (A3 „Technische Schulden"). Keine
Schemaänderung, keine Migration. Der Block räumt zwei Altlasten aus, die den
kommenden Umbau für bodengebundene Einsätze sonst verteuert hätten, und bringt
das Backlog in einen widerspruchsfreien Zustand.

### Neu sichtbar: die Spalte „abw. Crew" in der Tagesübersicht

Die Tagesübersicht zeigt jetzt eine zehnte Spalte: **abw. Crew**. Sie setzt
einen Haken, wenn für den Einsatz eine vom Flugtag abweichende Besatzung
eingetragen ist — der Fall, für den es die Funktion seit Web 2.6.0 gibt
(Pilotenwechsel während eines Flugtags). Wer einen Tag nachträgt oder
durchsieht, erkennt so ohne Aufklappen, an welchen Einsätzen jemand anderes an
Bord war. Sortieren lässt sie sich wie jede andere Spalte.

Die Spalte war die ganze Zeit definiert, nur nicht angeschlossen — siehe unten.

### Ein Feldkatalog, eine Auswertung

`mission_fields.php` ist der zentrale Katalog der Zusatzfelder. Sein Schlüssel
`day_col` sollte sagen, welche Felder als Spalte in der Tagesübersicht
erscheinen; tatsächlich war er reine Dokumentation. Die Spalten standen an drei
Stellen fest verdrahtet: im SELECT und im JSON-Aufbau von `api/day.php` sowie
im Tabellenkopf und im Zeilenaufbau von `index.php`. Ein Eintrag im Katalog
allein bewirkte nichts — deshalb fehlte „abw. Crew".

Die neue Datei `server/mission_fields_lib.php` wertet den Katalog jetzt an
**einer** Stelle aus (`mf_tagesspalten()`); Tabellenkopf, Zeilen, Sortierung
und die Antwort von `api/day.php` leiten sich daraus ab. Ein neues Feld mit
`day_col` erscheint damit ohne weitere Codeänderung. Auch die Spaltenklassen im
Stylesheet sind nicht mehr an eine feste Reihenfolge gebunden: Sie heißen
`c-dc-<spalte>`, und `.c-dc` gibt eine Vorgabe vor, mit der eine neue Spalte
ohne eigenen Eintrag auskommt.

Am ausgelieferten JSON ändert sich für bestehende Felder nichts — `winch`,
`bergwacht` und `secondary` heißen weiterhin so; `crew_override` kommt hinzu.
(Backlog Nr. 10)

### Ein Update lädt nur noch, was sich geändert hat

`asset()` hängte bisher die globale Versionsnummer an jede Stylesheet- und
Skript-Adresse. Folge: Jede Versionserhöhung entwertete den Zwischenspeicher
**aller** Dateien. Eine Korrekturfassung, die eine Zeile im Stylesheet ändert,
ließ jeden Besucher sämtliche Skripte, die Schriften und Leaflet erneut laden —
nach der lokalen Auslieferung der Schriften (Web 5.2.0) sind das einige hundert
Kilobyte.

Jetzt steht dort der Zeitstempel der jeweiligen Datei. Unveränderte Dateien
behalten ihre Adresse und bleiben im Zwischenspeicher; geänderte bekommen eine
neue. Wird eine Datei nicht gefunden, tritt wie bisher die Versionsnummer an
ihre Stelle. Der Auslieferungsweg trägt das mit: Der FTP-Deploy überträgt nur
inhaltlich geänderte Dateien und führt dafür auf dem Server eine Zustandsdatei
mit Prüfsummen. Zweimal wird trotzdem einmalig alles neu geladen — bei dieser
Auslieferung selbst und bei einem Deploy, bei dem jene Zustandsdatei auf dem
Server fehlt. (Backlog Nr. 9)

**Für die Auslieferung unverändert:** Die Version in `server/version.php` wird
weiterhin bei jeder Auslieferung erhöht. Sie steht in der Fußzeile und ist die
Nummer, über die eine Meldung zugeordnet wird.

### Das Backlog widerspricht sich nicht mehr

`docs/Backlog.md` erklärt im Kopf, dass Nummern dauerhaft sind und Erledigtes
nach unten wandert — hielt sich aber selbst nicht daran. Aufgeräumt:

* Die **Nummer 5 war doppelt vergeben**. Sie bleibt beim Geräte-Limit; die
  Typprüfer-Warnungen im Uhr-Code haben die freie Nummer **13** bekommen.
* Das **Geräte-Limit (Nr. 5)** ist längst umgesetzt und steht jetzt unter
  *Erledigt*.
* Die **Nummern 4, 6 und 7** fehlen ersatzlos und sind nicht mehr
  rekonstruierbar. Statt sie stillschweigend zu übergehen, sagt eine Notiz im
  Kopf, dass sie dauerhaft frei bleiben.
* Neu aufgenommen als **Nr. 14**: der Kopplungsablauf der Uhr — vor einer
  Neukopplung die bestehende abfragen und trennen, damit bei einem Fehlschlag
  nicht stillschweigend weiter auf das vorherige Konto dokumentiert wird.
* Nach *Erledigt* verschoben: **Nr. 9** und **Nr. 10** aus diesem Block.

### Geändert

* `server/mission_fields_lib.php` — neu: abgeleitete Sichten auf den
  Feldkatalog, derzeit `mf_tagesspalten()`.
* `server/api/day.php` — Spaltenliste und Antwortaufbau aus dem Feldkatalog.
* `server/index.php` — Tabellenkopf, Zeilenaufbau und `sortVal()` aus dem
  Feldkatalog; neue Konstante `DAY_COLS`.
* `server/assets/style.css` — Spaltenklassen `c-dc`/`c-dc-<spalte>` statt
  `c-winde`/`c-bw`/`c-sek` für die Tagestabelle. Die gleichnamigen Klassen der
  Zeitraum- und Suchtabelle (`#rangetable`) sind unberührt.
* `server/db.php` — `asset()` auf Dateizeitstempel.
* `server/version.php`, `server/mission_fields.php` — Kommentare nachgezogen.
* `docs/Backlog.md`, `docs/Technik.md`, `docs/Handbuch.md`.

## [Web 5.3.0] — 2026-08-16

Zweiter Block der Verbesserungsrunde Web (A2 „Suche aufräumen"). Keine
Schemaänderung, keine Migration. **Keine Datenbankspalte entfernt** — alle
Angaben bleiben vollständig erhalten, sie sind nur nicht mehr Filterkriterium.

### Vier Filter entfallen

Aus der Suche entfernt: **Herkunft**, **Reanimation**,
**Reanimations-Ereignis** sowie **Höhe Einsatzort von/bis**. Sie waren im
Betrieb nie gesetzt worden und verlängerten die Filterspalte um Zeilen, an
denen der Blick jedes Mal vorbeimusste.

Die Angaben selbst sind unberührt: Herkunft und Bearbeitungsstand stehen
weiterhin in der Einsatzansicht, Reanimationszeiten ebenso, die Höhe des
Einsatzortes in der Feldliste und im Export. Entfallen ist ausschließlich die
Möglichkeit, danach zu filtern.

Ältere geteilte Links und Lesezeichen führen zu keinem Fehler. Der
Filterzustand steht im URL-Fragment, und beim Einlesen werden unbekannte
Parameter still verworfen — ein Link mit `hk=manual` setzt eben diesen einen
Filter nicht mehr, alle übrigen greifen wie bisher. Die Kurznamen `hk`, `re`,
`rt`, `hv` und `hb` bleiben deshalb dauerhaft gesperrt und werden nicht neu
vergeben.

### Die Filterspalte ist nach Themen geordnet

Der Block „Art des Einsatzes" war ein Sammelbecken: Windencycles standen neben
Bergwacht-Bereitschaft neben Schockraum. Er ist durch drei Blöcke ersetzt, die
je einen Zusammenhang tragen:

* **Winde** — Windeneinsatz, Cycles, Cycles mit Patient, Luftverladung
* **Bergwacht** — Bergwacht, Bereitschaft
* **Transport** — Transportziel, Sekundärtransport, Schockraum

Das **Transportziel** ist dabei aus „Beteiligte und Ziel" hierher gewandert;
jener Block heißt jetzt schlicht **Beteiligte**. Die Spalte hat damit sechs
Blöcke statt vier: Zeit, Winde, Bergwacht, Transport, Beteiligte, Werte. Am
Verhalten ändert sich nichts — jeder Block klappt einzeln auf, und bei einem
geteilten Link gehen genau die Blöcke auf, in denen etwas gesetzt ist. Weil
diese Zuordnung aus der Filterliste in `suche.php` abgeleitet wird, war dafür
kein zusätzlicher Code nötig.

### Der Suchindex trägt nur noch, was gebraucht wird

`api/suchindex.php` liefert den Bestand, über den der Browser lokal filtert.
Mit den vier Filtern sind auch die Felder `origin`, `site_ele_m`,
`resus_count` und `resus_types` aus diesem Paket entfallen — und damit zwei
Datenbankabfragen über `resus_sessions` und `resus_events`, die bei jedem
Öffnen der Suchseite liefen. Der Endpunkt kommt jetzt mit fünf Abfragen aus
statt mit sechs.

Die Spalten selbst bleiben unangetastet. Einsatzansicht, Zeitraum-Übersicht und
Export beziehen sie über eigene Endpunkte und sind nicht betroffen.

### Nebenbei aufgeräumt: tote CSS-Regel

Mit dem Filter „Reanimations-Ereignis" verliert die Klasse `.reatypen` ihr
letztes Element. Sie war in zwei Regeln mit `.wochentage` zusammengefasst; die
Selektoren sind entfernt, die Regeln für die Wochentage bleiben unverändert.

## [Web 5.2.0] — 2026-08-16

Erster Block der Verbesserungsrunde Web (A1 „Darstellung und Formate"): fünf
sichtbare Unstimmigkeiten der Oberfläche und die letzte externe Abhängigkeit im
laufenden Betrieb. Keine Schemaänderung, keine Migration.

### Kartenbedienung schob sich über die Kopfleiste

Beim Scrollen legten sich Zoom, Ebenenwahl und Quellenangabe der Karte über die
klebende Kopfleiste. Die Kartenflächen selbst verschwanden korrekt darunter —
das machte den Fehler erst auffällig.

Die Ursache steckte in Leaflets eigenem Stylesheet: `.leaflet-top` und
`.leaflet-bottom` stehen dort auf `z-index: 1000`, unsere Kopfleiste auf 900.
Die Bedienelemente liegen jetzt auf 800. Bewusst nicht niedriger: Der Wert muss
über der obersten Kartenebene (Popups, 700) bleiben, sonst verdeckt ein
geöffnetes Popup die Bedienung. Der Kartenvollbildmodus bleibt unberührt — er
bildet einen eigenen Stapelkontext, die Werte darin zählen nur untereinander.

### Uhrzeiten stehen jetzt überall im 24-Stunden-Format

Je nach Sprach- und Regionseinstellung des Betriebssystems zeigten die
Zeitfelder „01:30 PM" statt „13:30" — auch bei deutscher Oberfläche. Das ist
keine Einstellungssache der Anwendung: Das Anzeigeformat von
`<input type="time">` folgt ausschließlich dem System und lässt sich weder per
HTML noch per CSS oder JavaScript erzwingen. In einer Notfalldokumentation ist
eine Uhrzeit mit AM/PM eine Fehlerquelle.

Die Zeitfelder sind deshalb jetzt gewöhnliche Textfelder mit Maske. Die neue
Datei `assets/zeitfeld.js` setzt Format, Zifferntastatur auf dem Telefon und
Rückmeldung: Ziffern tippen genügt, der Doppelpunkt setzt sich selbst, aus
`930` wird beim Verlassen `09:30`. Was keine gültige Uhrzeit ergibt, färbt das
Feld rot und wird nicht gespeichert. Betroffen sind die Phasenzeiten im
Einsatzformular und die beiden Alarmzeit-Filter der Suche.

**Datumsfelder bleiben nativ.** Dort ist die Anzeige kosmetisch — der
übertragene Wert ist immer ISO —, und ein selbstgebauter Kalender wäre auf dem
Telefon deutlich schlechter zu bedienen.

Die verbindliche Prüfung lag und liegt auf dem Server: `local_to_utc()` prüft
Muster **und** Wertebereich („25:00" passt auf `\d{2}:\d{2}` und ergäbe sonst
stillschweigend den nächsten Tag). Die Maske im Browser ist Bequemlichkeit,
keine Sicherung.

### Der Hinweis „Koordinaten gesetzt …" bezog sich sichtbar auf nichts

Die Meldungszeile des Einsatzort-Feldes stand hinter dem Koordinaten-Chip und
in derselben Größe und Farbe wie jeder andere Nebentext. Beim Lesen war nicht
zu erkennen, worauf sie sich bezieht — sie sagt aber etwas über das Textfeld
darüber aus, nicht über den Chip.

Sie steht jetzt unmittelbar unter dem Feld, kleiner gesetzt und in Max Blau.
Genauer: in der eine Stufe dunkleren Variante, weil nur die bei dieser
Schriftgröße die Kontrastschwelle erreicht (4,6:1 statt 3,8:1). Der Fehlerfall
(„Bezeichnung fehlt") bleibt rot.

### Suchergebnisse sahen nicht anklickbar aus

Ein Klick auf eine Trefferzeile öffnete den Einsatz schon immer — nur sah man
es der Zeile nicht an. Der Grund war schlicht: `assets/missiontable.js` setzte
die Klasse `clickable`, und im Stylesheet gab es dazu keine einzige Regel.

Jetzt wechselt der Zeiger, und die Zeile hebt sich beim Überfahren hervor. Und
weil eine reine Mausfunktion keine Lösung ist: Die Zeilen sind mit der
Tabulatortaste erreichbar, Enter und Leertaste öffnen den Einsatz, der
Tastaturfokus ist sichtbar. Betrifft Suche und Zeitraum-Übersicht gemeinsam,
weil beide dieselbe Tabelle benutzen.

### Schriften und Leaflet kommen nicht mehr aus dem Netz

Bis hierher lud jede Seite zwei Dinge von fremden Servern: die Schriften von
Google, Leaflet von unpkg. Das hatte zwei Folgen, die im Code seit Längerem als
Warnung standen. Erstens meldete jeder Seitenaufruf die IP-Adresse an Google
beziehungsweise unpkg — in einer Anwendung, deren ganzer Zweck darin besteht,
dass Patientendaten den Browser nicht unverschlüsselt verlassen, war das der
letzte verbliebene Bruch in der Linie. Zweitens fiel bei blockiertem Abruf
(Werbeblocker, strenger Trackingschutz) die Karte vollständig aus.

Beides liegt jetzt lokal:

* **Schriften** als woff2 unter `assets/fonts/`, eingebunden per `@font-face`
  mit `font-display:swap`. Übernommen wurden nur die tatsächlich benutzten
  Schnitte — Bricolage Grotesque 500/600, Open Sans 400/600/700 —, je in den
  Subsets latin und latin-ext. `unicode-range` trennt die beiden: latin-ext
  lädt nur, wenn ein Zeichen daraus vorkommt, etwa in einem osteuropäischen
  Namen. Zusammen rund 170 KB, und der Browser holt davon im Regelfall nur
  einen Teil.
* **Leaflet 1.9.4** unter `assets/vendor/leaflet/` — CSS, JS und die von der
  CSS referenzierten Bilder. Nach demselben Muster wie SheetJS und zip.js:
  Herkunft und SHA-256 der Originaldatei stehen im Dateikopf.

Die Ersatzschriftenliste bleibt trotzdem bestehen und bleibt normal breit
(siehe Web 5.1.1). Sie trägt jetzt nur noch den Fall, dass eine Datei fehlt.

Nebeneffekt, der so vorgesehen war: Eine Content-Security-Policy (Backlog
Nr. 8) lässt sich erst jetzt eng formulieren, weil keine fremde Quelle mehr
erlaubt werden muss.

### Nebenbei aufgeräumt: totes Zahnradmenü

`style.css` trug noch die Regeln eines aufklappenden Zahnradmenüs in der
Kopfleiste, das es seit Längerem nicht mehr gibt — die Kopfleiste enthält
stattdessen einen einfachen Verweis auf die Einstellungen. Elf Zeilen ohne
zugehöriges Element sind entfallen. Aufgefallen ist das bei der Suche nach
Elementen, die über der Kopfleiste liegen (siehe oben).

## [Web 5.1.1] — 2026-08-14

Vier Rückmeldungen aus dem Betrieb. Keine Schemaänderung; **eine Migration**,
die Gerätenamen bereinigt.

### Die Oberfläche wirkte gedrungen — die Ersatzschrift war schuld

Kopfleiste, Knöpfe, Überschriften und Tabellenköpfe sahen zusammengeschoben
aus. Die Ursache stand in der Ersatzliste für die Überschriftenschrift:

```
--head:'Bricolage Grotesque','Arial Narrow',system-ui,sans-serif;
```

Bricolage Grotesque ist eine **normal breite** Grotesk, Arial Narrow eine
**schmale**. Solange die Webschrift geladen wurde, fiel das nicht auf. Kam sie
nicht durch — Werbeblocker, strenger Trackingschutz, Aussetzer bei
fonts.gstatic.com —, wurde die halbe Oberfläche schmal. Es sah nach einem
Gestaltungsfehler aus und war ein fehlgeschlagener Download.

Die Ersatzliste ist jetzt normal breit (Systemschrift, dann die üblichen je
Betriebssystem). Fällt die Webschrift aus, fällt es kaum noch auf.

**Der eigentliche Punkt bleibt bestehen und steht als Warnung im CSS:** Beide
Schriften kommen bei jedem Seitenaufruf von Google. Das meldet die IP-Adresse
jeder Nutzerin an Google — in einer Anwendung, deren ganzer Zweck darin
besteht, dass Patientendaten den Browser nicht unverschlüsselt verlassen, ist
das ein Bruch in der Linie. Behebung wäre, die vier woff2-Dateien selbst
auszuliefern.

### Gerätenamen: das Kopplungsdatum verschwindet auch aus dem Altbestand

Web 5.0.1 hatte nur die *Vergabe* geändert — neu gekoppelte Geräte heißen „Uhr“.
Bereits vorhandene trugen weiter „Uhr (gekoppelt 11.08.2026)“, und der Hinweis
auf der Startseite zeigte das Datum deshalb weiterhin zweimal.

Eine Migration bereinigt das. Geändert wird **nur**, was exakt dem automatisch
vergebenen Muster entspricht; ein selbst vergebener Name — „Uhr Philipp“,
„Christoph 17“, auch „Uhr (gekoppelt, alt)“ — bleibt unberührt. Es geht keine
Angabe verloren: Das Datum steht in `devices.created_at` und wird in der
Geräteliste als „seit …“ angezeigt.

Die Migration ist bewusst **nicht** als destruktiv gekennzeichnet: Hier wird
nichts vernichtet, sondern eine doppelt geführte Angabe auf ihre eine Quelle
zurückgeführt. Die rote Kennzeichnung bleibt den Fällen vorbehalten, in denen
wirklich etwas verlorengeht — sonst gewöhnt man sich an sie.

Beim Bauen ist der erste Entwurf gescheitert: Als SQL-`REGEXP` hätten Klammern
und Punkte doppelt maskiert werden müssen, einmal für PHP und einmal für die
Datenbank, und was am Ende bei MariaDB ankommt, war dem Quelltext nicht mehr
anzusehen. Die Migration läuft deshalb als Funktion, mit dem Muster als
gewöhnlichem regulärem Ausdruck an genau einer Stelle.

### Der Quittungsknopf steht jetzt im Rahmen des Hinweises

„Verstanden, das war ich“ stand als unterstrichener Text unter dem Absatz und
war kaum zu finden. Ein Hinweis, dessen Ausweg man nicht sieht, ist derselbe
Hinweis, der sich nicht wegklicken lässt. Er sitzt jetzt als Knopf im Rahmen,
rechts neben dem Text — gedeckt eingefärbt, damit er auffindbar ist, ohne mehr
Gewicht zu haben als die Warnung selbst.

### Die Wartungsseite meldet die Schlüsselableitung nur noch im Problemfall

Der Abschnitt zeigte auch eine Entwarnung („Alle Konten rechnen mit einer
Rundenzahl, die diese Fassung anbietet“). Sie ist entfallen: Eine Wartungsseite,
die Nicht-Probleme aufzählt, macht die echten Meldungen schwerer zu finden — und
wer sie liest, überfliegt beim nächsten Mal auch die Zeile, die zählt.

**Die Prüfung selbst bleibt.** Sie fängt den Fehler ab, den jemand macht, der
`KDF_ITER_ZIEL` anhebt und vergisst, den bisherigen Wert in `KDF_ITER_LISTE`
stehen zu lassen: Dann kann sich kein Bestandskonto mehr anmelden, und an der
Anmeldemaske ist die Ursache nicht zu erkennen.

### Nicht geändert: zwei Meldungen in der Browser-Konsole

Beide stammen **nicht** aus diesem Projekt und lassen sich hier nicht abstellen:

* `MouseEvent.mozPressure is deprecated` und `mozInputSource` beim Klick auf
  die Karte — aus `Util.js` von Leaflet 1.9.4. Ein Hinweis von Firefox an
  Leaflets Entwickler, keine Fehlfunktion. Er verschwindet mit einer künftigen
  Leaflet-Fassung.
* `Request for font "Cascadia Mono" blocked at visibility level 1` — Firefox
  verhindert, dass Webseiten die installierten Schriften auslesen. Die Zeile
  erscheint für jede in einer Schriftliste genannte Schrift, die nicht zum
  Grundbestand gehört. Auch das ist kein Fehler.

### Geprüft

16 automatische Prüfungen über echten HTTP-Verkehr, alle bestanden: Migration
(automatischer Name bereinigt, drei Varianten selbst vergebener Namen
unberührt, zweiter Lauf ohne Arbeit), Hinweis mit Knopf im Rahmen, Quittieren,
Wartungsseite mit und ohne Problem.

## [Web 5.1.0] — 2026-08-14

Paket P9c, Web-Teil: **M2-10, Formatkennung vor jedem Chiffretext.** Keine
Schemaänderung, keine Migration. Die beiden Uhr-Befunde aus P9c (M7-03 und die
409-Behandlung in `Pair.mc`) folgen als eigene Uhr-Auslieferung.

### Warum eine Kennung

Ein Chiffretext bestand aus Zufallswert und Nutzdaten — ohne jede Angabe
darüber, mit welchem Verfahren er entstanden ist. Wird das Verfahren je
gewechselt, und irgendwann wird es das, gibt es kein Merkmal, an dem sich alt
von neu unterscheiden ließe. Man müsste raten und am Fehlschlag erkennen, dass
man falsch geraten hat — nur sieht ein Fehlschlag beim Entschlüsseln genauso
aus wie ein falscher Schlüssel.

Der Sicherungscontainer macht es seit jeher richtig vor: Er trägt eine
Fassungsnummer im Kopf. Der Aufwand ist jetzt klein und wäre später groß.

### `edk1:` — ein Textpräfix, kein Kennungsbyte

Der Doppelpunkt gehört nicht zum base64-Zeichenvorrat. Die Kennung ist damit
auf den ersten Blick zu erkennen, auch in der Datenbankspalte, und ohne dass
irgendetwas entschlüsselt werden müsste. Ein Byte **innerhalb** der Daten wäre
von einem Zufallswert nur durch Ausprobieren zu unterscheiden — man müsste
beide Deutungen durchrechnen.

**Die Kennung gilt für jeden Chiffretext**, nicht nur für `pat_blob`: Auch die
beiden Schlüsselhüllen `pat_wrap_pw` und `pat_wrap_rc` kommen aus derselben
Funktion. Eine halb gekennzeichnete Verschlüsselung wäre genau die
Inkonsistenz, die der Befund abschaffen soll.

### Beim Lesen großzügig, ohne Umstellung des Bestands

Ein Chiffretext **ohne** Kennung ist die erste Fassung. Eine Umstellung des
Bestands gibt es nicht und kann es nicht geben: Der Server hat den Schlüssel
nach Bauart nicht und kann die Kennung deshalb nicht nachtragen. Beide Formen
stehen dauerhaft nebeneinander; ein Datensatz bekommt die Kennung, wenn er das
nächste Mal gespeichert wird.

Eine **unbekannte** Kennung meldet „wurde mit einer neueren Fassung des
Programms verschlüsselt" statt „Schlüssel passt nicht" — sonst sucht die
lesende Person den Fehler beim Schlüssel und findet ihn nie. Derselbe Gedanke
wie beim Sicherungscontainer in Web 5.0.0.

### Drei Kopien einer Prüfregel beseitigt

Das Muster für Schlüsselhüllen stand dreifach im Projekt: als Konstante
`WRAP_RE` in `pw_handling.php` und wortgleich als Zeichenkette in
`einstellungen.php` und `api/kdf_upgrade.php`. Mit der Kennung wären daraus
drei Stellen geworden, die einzeln nachzuziehen gewesen wären — und eine
vergessene hätte eine **gültige** Hülle abgewiesen, mit dem Ergebnis, dass ein
Passwortwechsel scheitert. `WRAP_RE` liegt jetzt neben `PAT_BLOB_RE` in
`validate_lib.php`.

### Zum Rückschritt

Ein Rückschritt auf 5.0.1 ist nur so lange gefahrlos, wie **kein
Patientendatensatz gespeichert und kein Passwort gewechselt** wurde. Danach
trägt der betroffene Chiffretext die Kennung, und die ältere Fassung liest sie
nicht. Bei einer Schlüsselhülle heißt das: kein Zugriff auf die geschützten
Angaben, bis das Passwort über den Wiederherstellungsschlüssel zurückgesetzt
wird.

### Behobene Review-Befunde

M2-10. Damit sind alle Web-Befunde des Reviews erledigt; offen bleiben nur
noch die beiden Uhr-Befunde M7-03 und die 409-Behandlung in `Pair.mc`.

### Geprüft

31 automatische Prüfungen, alle bestanden: 13 im Browser (Kennung schreiben,
beide Formen lesen, falscher Schlüssel bei beiden Formen, unbekannte Kennung
wird als solche gemeldet, Hüllen, Eindeutigkeit der Hüllen-Bindung), 18
serverseitig (beide Formen, Längengrenzen mit und ohne Kennung, fremde Kennung,
Doppelpunkt an falscher Stelle, Platz in der Spalte).

## [Web 5.0.1] — 2026-08-14

Vier Befunde aus dem Betrieb der Testinstallation. Keine Schemaänderung.

### Das Wiedereinspielen einer Sicherung brach hart ab

`api/backup_restore.php` antwortete mit 500, im Fehlerprotokoll stand
`Class "Pruefliste" not found`. `backup_lib.php` benutzt die Prüfschicht,
lud sie aber nie — sie stand nur dann zur Verfügung, wenn die aufrufende Seite
`validate_lib.php` zufällig schon eingebunden hatte. Der Sicherungs-Endpunkt
tat das nicht.

**Der Fehler bestand, seit die Prüfschicht eingeführt wurde (P3, Web 4.2.0).**
Aufgefallen ist er erst jetzt, weil er nur den Einspielweg trifft.

Bemerkenswert ist, warum ihn keine der 36 automatischen Prüfungen zum
Sicherungsweg gesehen hat: Das Prüfskript lud `validate_lib.php` selbst, bevor
es `backup_lib.php` einband — und verdeckte damit genau die Lücke, die es hätte
finden sollen. Die Prüfung lädt jetzt nur noch das, was auch
`api/backup_restore.php` lädt.

### Zeitraumansicht: Fehler in der Browser-Konsole beim Aufbau

`Uncaught TypeError: can't access property "subtract", this._point is
undefined` — bei jedem Aufbau der Seite. Die Karte bekam keinen
Ausgangsausschnitt. Ohne ihn nimmt Leaflet eine Ebene zwar entgegen, stellt sie
aber zurück und rechnet ihre Bildschirmposition nicht aus; das `setStyle()` der
Hervorhebung, die nach jedem Neuzeichnen der Tabelle über alle Pins läuft,
scheitert dann. `fitBounds()` kommt erst danach.

`index.php` löst das seit jeher mit einer Zeile. Sie fehlte auf der
Zeitraumansicht — und auf `einsatz.php` ebenfalls, dort bisher folgenlos, weil
`fitBounds()` rechtzeitig kommt. Beide haben sie jetzt.

### Der Gerätehinweis nannte das Datum zweimal und ließ sich nicht wegklicken

„Ein neues Gerät wurde mit deinem Konto verbunden: Uhr (gekoppelt 11.08.2026)
(11.08.2026 17:01)."

Der beim Koppeln vergebene Name enthielt das Datum, und der Hinweis gab
zusätzlich `created_at` aus. Der Name lautet jetzt nur noch „Uhr": Eine Angabe,
die an zwei Stellen geführt wird, läuft auseinander, sobald jemand das Gerät
umbenennt — dann steht ein Datum im Namen, das mit nichts mehr zusammenhängt.
Das Kopplungsdatum gehört der Zeile, nicht dem Namen; die Geräteliste zeigt es
ohnehin.

Der Hinweis stand außerdem sieben Tage lang auf der Startseite und war nicht
wegzuklicken. Eine Warnung, die man nicht loswird, wird nach dem dritten Mal
überlesen — und dann steht sie unbemerkt da, wenn sie einmal wirklich gemeint
ist. Es gibt jetzt „Verstanden, das war ich"; bestätigt wird je Zeitpunkt, ein
später gekoppeltes Gerät meldet sich erneut. Das Kennzeichen „neu" in der
Geräteliste bleibt unberührt — dort ist es keine Warnung, sondern eine Angabe.

### 310 000 ist aus der Rundenzahl-Liste entfernt

Alle Konten stehen auf 320 000 (`SELECT COUNT(*) FROM users WHERE kdf_iter =
310000` ergab 0). Die Anmeldung rechnet damit wieder nur einmal ab und ist so
schnell wie vor Web 5.0.0.

**Dazu neu auf der Wartungsseite: ein Abschnitt „Schlüsselableitung".** Er
meldet, wenn ein Konto eine Rundenzahl trägt, die `KDF_ITER_LISTE` nicht mehr
anbietet — solche Konten können sich nicht anmelden, und an der Anmeldemaske
ist die Ursache nicht zu erkennen. Genau das passiert, wenn jemand
`KDF_ITER_ZIEL` anhebt und vergisst, den bisherigen Wert in der Liste stehen zu
lassen. Der Abschnitt nennt die betroffene Zahl, die Anzahl der Konten und die
Behebung. In `db.php` steht die Anweisung jetzt als erstes im Kommentarblock,
nicht mehr am Ende.

### Behobene Befunde

Ohne Review-Kennung — alle vier stammen aus dem Betrieb.

### Geprüft

27 automatische Prüfungen, alle bestanden: 8 zum Wiedereinspielen über den
echten Ladeweg (ohne Vorladen der Prüfschicht), 11 zum Gerätehinweis,
8 zur Warnung der Wartungsseite. Dazu die 44 + 18 Prüfungen aus P9b erneut —
die stille Anhebung gegen eine Kopie des Servers mit zweielementiger Liste,
also gegen den Zustand nach einer künftigen Anhebung.

## [Web 5.0.0] — 2026-08-13

Paket P9b: die Rundenzahl der Schlüsselableitung wird änderbar (M2-01, Schritte
2 bis 4) und wandert in den Kopf der Sicherungsdatei (S7). Keine
Schemaänderung — die Spalte `users.kdf_iter` gibt es seit Web 4.0.0, sie wurde
bisher nur von keiner Zeile gelesen.

**Hauptversion, weil sich das Dateiformat der Sicherung ändert** und die
Anmeldung ein anderes Feld sendet. Beides ist rückwärtsverträglich; die Zählung
folgt dem Format, nicht dem Risiko.

### Warum das überhaupt nötig war

Die Rundenzahl stand als Konstante im Browser-Code. Sie anzuheben hätte
bedeutet: Aus demselben Passwort entsteht ein anderes Token, der gespeicherte
Hash passt nicht mehr — **alle Konten gleichzeitig ausgesperrt**, und zwar
unwiderruflich für die geschützten Angaben. Dieselbe Konstante steckte im
Schlüssel jeder Sicherungsdatei; eine Anhebung hätte auch jede bereits
erzeugte Datei unlesbar gemacht, ohne Fehlermeldung, die den Grund nennt.

Beides ist jetzt behoben. Der Zielwert steigt von 310 000 auf **320 000** —
absichtlich nur ein kleiner Schritt: Der Gewinn ist gering, aber der
Mechanismus läuft dadurch einmal für jedes Konto wirklich durch, unter
Beobachtung. Ein späterer Sprung auf einen deutlich höheren Wert ist danach
eine Zeile auf einem erprobten Weg statt ein Sprung ins Dunkle.

### Der Salz-Endpunkt nennt eine Liste, keinen Wert

`auth_salt.php` ist ohne Anmeldung erreichbar und muss für erfundene Adressen
genauso antworten wie für echte. Nennte er die Rundenzahl **des Kontos**, wäre
während einer Umstellung jede Adresse, die den alten Wert zurückliefert,
nachweislich ein echtes, seither nicht benutztes Konto — die Auskunftslücke,
die derselbe Endpunkt in Web 4.4.0 gerade geschlossen hatte, an neuer Stelle.

Er nennt deshalb **jeder Adresse dieselbe Liste**. Der Browser leitet für jeden
Eintrag ab und schickt alle Token; der Server nimmt das, das zur gespeicherten
Rundenzahl gehört. Es gibt weiterhin genau eine bcrypt-Prüfung — kein
Durchprobieren.

Der Preis steht im Handbuch: Solange die Liste zwei Einträge hat, dauert die
Anmeldung doppelt so lange. Das ist der Übergangszustand, nicht der
Dauerzustand. In `db.php` steht, wann ein Wert aus der Liste verschwinden darf
— nämlich erst, wenn `SELECT COUNT(*) FROM users WHERE kdf_iter = <Wert>` null
ergibt.

### Die Rundenzahl ist ein Pflichtparameter

`EdCrypto.deriveKeys(passwort, salt, runden)` hat **keinen Vorgabewert** und
wirft, wenn die Zahl fehlt. Das ist die wichtigste Sicherung des Umbaus: Ein
Vorgabewert ließe jede vergessene Aufrufstelle stillschweigend mit 310 000
rechnen — und weil heute noch alle Konten diesen Wert tragen, fiele das *nicht
auf*. Es fiele erst an dem Tag auf, an dem der Zielwert angehoben wird, und
dann als „Passwort falsch" bei Leuten, die richtig getippt haben.

Umgestellt sind sieben Ableitungen und zehn Aufrufe von `ensureContentKey()`.
`auth_guard.php` liefert `$kdfIter`, sieben Seiten geben ihn als `KDF_ITER`
aus. Erstvergabe, Zurücksetzen und Passwortwechsel bauen die Ableitung
vollständig neu auf und nehmen deshalb immer den Zielwert — der Passwortwechsel
ist damit die zweite Gelegenheit, bei der ein Konto die Anhebung mitnimmt.

### Die stille Anhebung

Steht ein Konto noch auf der alten Zahl, wird sie beim nächsten Anmelden im
Hintergrund angehoben. Der Weg führt über ein Vormerkfach, weil Passwort und
Schlüsselhülle nie gleichzeitig vorliegen: Bei der Anmeldung hat der Browser
das Passwort, aber nicht die Hülle; auf der ersten angemeldeten Seite ist es
umgekehrt. Dasselbe Verfahren benutzt der Passwortwechsel seit Web 4.5.0.

Der neue Endpunkt `api/kdf_upgrade.php` ist funktional eine Passwortänderung —
er setzt den Hash, gegen den sich das Konto anmeldet. Entsprechend abgesichert:

* **Das alte Token ist Pflicht.** Ohne Nachweis wäre der Endpunkt ein Weg, aus
  einer übernommenen Sitzung ein beliebiges Passwort zu setzen.
* **Nur Werte aus der Liste, nur nach oben.** Eine frei wählbare Rundenzahl
  wäre ein Weg, ein Konto auf einen absurd niedrigen Wert zu setzen, ohne dass
  jemand es merkt — die Anmeldung liefe weiter.
* **Die Prüfsumme des Inhaltsschlüssels muss gleich bleiben.** Es ist derselbe
  Schlüssel, nur anders verpackt.
* **`session_epoch` wird nicht erhöht.** Beim Passwortwechsel ist das
  Hinauswerfen anderer Sitzungen der Zweck; hier wäre es ein Fehler — das
  Passwort hat sich nicht geändert, und niemand verstünde, warum bei jeder
  Anmeldung stillschweigend alle anderen Fenster fliegen.

Ein Fehlschlag ändert nichts und meldet nichts: Das Konto behält seine
Rundenzahl und versucht es beim nächsten Anmelden erneut.

### Ein Fehler, den erst die Ende-zu-Ende-Prüfung gezeigt hat

Nach der Anhebung passte der neue Datenschlüssel nicht mehr zu der
Schlüsselhülle, die die Seite mitgebracht hatte — `PAT_WRAP` wird gerendert,
bevor die Anhebung läuft. Folge wäre gewesen: Der Entsperrdialog erscheint
unmittelbar nach jedem Anmelden und fragt nach dem Passwort. Also genau das
Gegenteil einer *stillen* Anhebung, und die Art Fehler, die im Betrieb niemand
meldet — man gewöhnt sich an die Abfrage.

Der Inhaltsschlüssel ist in dem Moment bekannt (er wurde eine Zeile vorher
entpackt, um ihn neu zu verpacken). Er wird jetzt abgelegt und an die Hülle der
laufenden Seite gebunden; beim nächsten Seitenaufbau verwirft `EdKeyGuard` ihn
wegen der abweichenden Bindung und entpackt ihn aus der neuen Hülle.

### Sicherungsdateien: Containerformat 3

Der Kopf wächst von 9 auf 13 Byte und trägt die Rundenzahl als 4 Byte, big
endian. Sie geht in die AAD ein und lässt sich damit nicht fälschen.

* **Fassung 2 wird weiterhin gelesen** — die Fassungsnummer ersetzt die
  fehlende Angabe (dort galt immer 310 000). Geschrieben wird sie nicht mehr.
* Eine Datei aus einer **neueren** Fassung meldet „stammt aus einer neueren
  Fassung, bitte die Anwendung aktualisieren" statt „Passwort falsch" — sonst
  sucht die lesende Person den Fehler an der falschen Stelle.
* `docs/Backup-Format.md` beschreibt beide Fassungen; das Python-Beispiel dort
  behandelt beide und wurde gegen eine echte Datei geprüft.

### Behobene Review-Befunde

M2-01 (Schritte 2–4), S7. Damit ist die Schemaänderung S1 aus P0 vollständig
eingelöst.

### Geprüft

79 automatische Prüfungen, alle bestanden: 43 zu Salz-Endpunkt, Anmeldung und
Anhebungs-Endpunkt (echter HTTP-Verkehr, echte Ableitung mit WebCrypto), 18 zum
vollständigen Browserweg von der Anmeldung bis zur abgeschlossenen Anhebung,
18 zum Containerformat.

Belegt sind unter anderem: identische Antwort des Salz-Endpunkts für bekannte
und erfundene Adressen; Anmeldung vor und nach der Anhebung; sechs Abwehrfälle,
nach denen Rundenzahl, Hülle und Sitzungszähler unverändert sind; dass die neue
Hülle denselben Inhaltsschlüssel enthält; dass eine von Hand gebaute
Fassung-2-Datei weiterhin aufgeht.

Nicht ohne Testinstallation prüfbar: die tatsächliche Dauer der Ableitung auf
den benutzten Geräten und das Verhalten in mehreren offenen Tabs.

## [Web 4.7.0] — 2026-08-13

Paket P9a der Review-Umsetzung: die vier Befunde aus P9, die weder die
Schlüsselableitung noch das Format des Chiffretexts berühren. Keine
Schemaänderung. P9b (Ableitungsrunden) und P9c (Formatkennung, Uhr-App)
folgen getrennt — die beiden gefährlichsten Änderungen des ganzen Reviews
sollen einzeln prüfbar bleiben.

### Migrationen löschen keine Spalte mehr, in der noch etwas steht

Vier Migrationen entfernen Spalten, und alle vier gehen davon aus, dass ihr
Inhalt anderswo gerettet wurde. Für die Betreiberinstallation stimmt das und
ist dokumentiert. Das Projekt liegt aber offen: Eine zweite Station verlor die
betroffenen Spalten in dem Moment, in dem jemand die Wartungsseite öffnete und
den Knopf drückte — ohne je gelesen zu haben, dass vorher etwas zu retten
gewesen wäre.

Die Migrationsliste kennt jetzt zwei zusätzliche Angaben. `zerstoert`
beschreibt in Klartext, was verlorenginge, und hebt die Zeile in der Vorschau
hervor. `inhalt` nennt die Spalten, deren Inhalt die Migration vernichten
würde — steht dort etwas, wird sie **nicht ausgeführt**, sondern mit Spalte und
Zeilenzahl gemeldet.

**Nicht jede destruktive Migration prüft den Inhalt**, und das ist Absicht. Bei
`phase10_entfernen` und der Zeitzonen-Migration ist das Löschen der Zweck; eine
Inhaltsprüfung hätte sie genau dort blockiert, wo sie gebraucht werden. Geprüft
wird nur, wo eine Spalte von Hand eingegebene Daten hielt: `loc_addr`,
`loc_lat`, `loc_lon`, `mission_no`, `site_desc`.

Zwei Punkte, die sich beim Bauen ergaben:

* Eine blockierte Migration **hält die Kette nicht an**, anders als ein Fehler.
  Sie hat nichts getan; die Datenbank steht exakt wie zuvor. Andernfalls käme
  auf einer Installation mit Altbestand in `site_desc` keine spätere Migration
  mehr durch — darunter die Sicherheitsbausteine aus `2026_08_08`. Ein
  Datenschutz, der die Sicherheitsupdates blockiert, wäre ein schlechter Tausch.
* Es gibt einen **Ausweg**: je blockierter Migration ein eigenes Häkchen
  „Daten sind gesichert — diese eine Migration trotzdem ausführen“. Ohne ihn
  säße der Betreiber fest. Auf der Kommandozeile gibt es die Stufe bewusst
  nicht; dort steht nur der Weg zur Wartungsseite. Ein `--force` wäre zu leicht
  aus einer Anleitung abgeschrieben.

Wurde eine Migration freigegeben, sagt die Ergebniszeile das ausdrücklich —
sie ist später der einzige Beleg dafür, dass jemand die Entscheidung bewusst
getroffen hat.

### Die Einrichtung kann keine Datenbank mehr leeren, und Fremde können sie nicht ausführen

Der Einrichtungs-Assistent hatte ein Häkchen „Vorhandene Tabellen vorher
löschen“. Es war die einzige Stelle im Projekt, an der ein **unangemeldeter**
Aufruf jede Tabelle der Datenbank hätte leeren können — abgesichert durch
nichts als die Annahme, dass diese Seite nur einmal und nur vom Betreiber
geöffnet wird. Es ist ersatzlos entfallen: Wer mit Altbestand neu einrichten
will, legt eine leere Datenbank an oder leert die vorhandene beim Hoster.
Beides sind bewusste Handlungen an der richtigen Stelle.

Dieselbe Annahme trug auch den Rest der Seite. Wer eine frisch hochgeladene
Installation vor ihrem Betreiber findet, richtete sie ein und war
Administrator. Deshalb legt die Seite jetzt eine Datei im Anwendungsverzeichnis
an und verlangt deren Kennung im Formular. Wer sie nennen kann, hat Zugriff
auf das Verzeichnis — und wer den hat, könnte die Anwendung ohnehin beliebig
verändern.

**Die Kennung steht im Dateinamen, nicht nur im Inhalt.** Bei Einfachhosting
liegt das Verzeichnis im Web-Wurzelverzeichnis; eine Datei mit festem Namen
wäre über die Adresszeile abrufbar, und der Nachweis wäre keiner. Einen Namen
aus 128 Bit Zufall kann nur nennen, wer das Verzeichnis sieht. Die `.htaccess`
sperrt die Datei zusätzlich — als zweite Schranke, nicht als erste.

Beim Prüfen zeigte sich ein Fehler im ersten Entwurf: Die Kennung hing an der
Sitzung, und **jeder** Aufruf der Seite ließ eine weitere Datei liegen. Wer
danach ins Verzeichnis sieht, findet mehrere und weiß nicht, welche seine ist.
Die Kennung hängt jetzt an der Datei; eine vorhandene wird übernommen statt
ersetzt. Das hält zugleich einen Ärger fern, den die erste Fassung geöffnet
hätte: Wer die Datei bei jedem Aufruf neu schreiben ließe, könnte einem
Betreiber mitten in der Einrichtung die Kennung unter den Händen wegziehen.

### Die Passwortprüfung wird endlich benutzt

Baustein B9 (`assets/pwquality.js`) entstand in P0 und lag seither an **keiner
einzigen Stelle** eingebunden da. Die Mindestlänge stand stattdessen als
HTML-Attribut und als Längenvergleich im Skript — ein Attribut hält niemanden
auf, der die Entwicklerwerkzeuge öffnet, und „zu kurz“ ist keine Begründung.

Jetzt an vier Stellen: Erstvergabe, Zurücksetzen, Passwortwechsel und die
beiden Dateipasswörter. Überall dieselbe Regel, mit Stärkeanzeige während der
Eingabe und einem Satz dazu, warum ein Passwort abgelehnt wird.

### Dateipasswörter: mindestens zehn Zeichen, und das Kontopasswort ist erlaubt

Sicherung (`.edbak`) und Export-Archiv verlangten acht Zeichen — weniger als
das Konto, obwohl in beiden Dateien dieselben Angaben stehen, im Export sogar
im Klartext. Beide liegen jetzt bei zehn und laufen über dieselbe Prüfung.

Beim **Backup** lässt sich zusätzlich das Kontopasswort verwenden. Ob es
stimmt, stellt der Browser selbst fest: Aus Passwort und Salz entsteht der
Datenschlüssel, und mit dem muss sich die gespeicherte Hülle öffnen lassen. Der
Server wird dafür nicht gefragt.

Beim **Export-Archiv** wird das bewusst **nicht** angeboten: Diese Datei ist
ausdrücklich zum Weitergeben gedacht. Wer sie mit seinem Kontopasswort
verschlüsselt, gibt es dem Empfänger mit.

Der Hinweis über dem Backup-Passwortfeld sagt außerdem jetzt, **was** in der
Datei steht — alle geschützten Angaben im Klartext — statt nur, dass die Datei
ohne Passwort wertlos sei. Das beantwortete die falsche Frage: Wer ein Passwort
wählt, muss wissen, was er damit schützt.

### Behobene Review-Befunde

M6-01, M1-11, M2-02, M2-03.

### Geprüft

74 automatische Prüfungen, alle bestanden: 24 zur Wartungsseite (Inhaltsprüfung
gegen eine Datenbank mit gefüllter `site_desc`-Spalte, über echten
HTTP-Verkehr), 23 zum Einrichter (eigener Webserver auf einer Kopie des
Verzeichnisses, echte Ersteinrichtung gegen eine Datenbank mit Fremdtabelle),
20 zur Passwortprüfung, 7 zur Inhaltszählung.

Nicht ohne Testinstallation prüfbar: Darstellung der Stärkeanzeige, das
Häkchen „Kontopasswort verwenden“ im Zusammenspiel mit dem Entsperrdialog und
das Verhalten der `.htaccess` auf einem echten Apache.

## [Web 4.6.0] — 2026-08-13

Paket P8c der Review-Umsetzung: Bündel 5 (Leistung) und Bündel 6. Zehn Befunde,
keine Schemaänderung. Damit ist P8 abgeschlossen; offen bleibt P9.

### Eine Sicherung braucht nicht mehr Tausende Abfragen

`edbak_build()` fragte je Einsatz die Phasen, die Rettungsmittel, die
Reanimationssitzungen und die Spurpunkte einzeln ab, dazu je Sitzung deren
Ereignisse. Gemessen an 43 Einsätzen mit je einer Reanimationssitzung:
**226 Abfragen vorher, 16 nachher** — und die 16 bleiben 16, egal wie groß der
Bestand ist. Auf die im Review genannten 1600 Einsätze hochgerechnet sind das
rund 8300 Abfragen gegen unverändert 16.

Dieselbe Sache in kleinerem Maßstab in der Tagesansicht: `api/day.php` holte
die Spurpunkte je Einsatz und je Ruhesegment einzeln, bei jedem Tageswechsel
neu.

Die chunkweise `IN(…)`-Abfrage gab es längst — sie stand in
`api/export_data.php`, mitsamt einem Kommentar, der genau diesen Weg
beschreibt. Sie stand eben **nur dort**, und deshalb ist ihr niemand gefolgt.
Jetzt liegt sie als `sql_in_bloecken()` in `db.php` und wird von allen drei
Stellen benutzt.

### Eine leere Phasenliste löscht keine Phasen mehr

Der Uhr-Weg ersetzte Phasen und Reanimationssitzungen, sobald der Schlüssel im
Datensatz vorhanden und ein Feld war. Eine **leere** Liste bestand beide
Prüfungen: Sie löschte den vorhandenen Stand und fügte nichts ein. Die Antwort
lautete „ok“.

Der Weg zu einer leeren Liste ist viel wahrscheinlicher ein Fehler beim Aufbau
der Nachricht als der Wunsch, eine dokumentierte Reanimation wieder
loszuwerden. Wer wirklich löschen will, tut das in der Weboberfläche.

**Die Regel geht bewusst weiter als der Befund.** Übergangen wird jede Liste,
die weniger gültige Einträge enthält als der gespeicherte Stand — eine halb
aufgebaute Nachricht ist derselbe Fehler wie eine leere, nur unauffällig: Sie
kommt mit drei Phasen an, wo acht stehen, und der Verlust fällt niemandem auf.
Für die Uhr ist das folgenlos: `Model.mc` fügt Phasen ausschließlich hinzu, ein
erneutes Setzen ist eine Korrektur und damit ein zusätzlicher Eintrag. Eine
kürzere Liste kann bei ihr gar nicht entstehen.

Gezählt wird **nach** der Prüfung: Zehn Einträge, von denen neun unbrauchbar
sind, sind ein Eintrag. Wird eine Liste übergangen, steht das als `kept_phases`
bzw. `kept_resus` in der Antwort — sonst wäre der übergangene Upload von einem
übernommenen nicht zu unterscheiden. `docs/JSON-Vertrag.md` beschreibt beide
Felder jetzt als durchgesetzt statt als Zielzustand.

### Ein Fehler in der Höhenberechnung kostet keine Wiederherstellung mehr

Die Einsatzort-Höhe wurde beim Wiedereinspielen **innerhalb** der Transaktion
und ohne eigenen Fehlerblock berechnet, je Einsatz in der Schleife. Ein Fehler
darin riss die gesamte Wiederherstellung mit sich — wegen eines Komfortwerts,
und ausgerechnet an der Stelle, an der die Eingangsdaten am wenigsten geprüft
sind. Auf dem Uhr-Weg stand derselbe Aufruf längst hinter dem Abschluss, mit
genau dieser Begründung.

Jetzt läuft er auch beim Wiedereinspielen nach dem Commit, je Einsatz
eingefasst. Anders als auf dem Uhr-Weg wird ein Fehlschlag aber **gezählt und
gemeldet** (`hoehe_fehler`): Die Uhr kann mit der Auskunft nichts anfangen,
eine Wiederherstellung wertet dagegen ein Mensch aus.

Nachgestellt und gemessen: Bei erzwungenem Fehler bleiben alle Einsätze
gespeichert, und die Meldung nennt die Zahl.

### CSV-Exporte führen keine Formeln mehr aus

Tabellenprogramme werten eine Zelle, die mit `=`, `+`, `-` oder `@` beginnt,
als Formel aus — auch in Anführungszeichen, denn das Quoting gehört zum
CSV-Format und nicht zum Zellinhalt. Fremder Text gelangt über zentrale
Stammdaten und über eingespielte Daten in die Textspalten, und Exportdateien
sind ausdrücklich zum Weitergeben gedacht.

Solche Werte tragen jetzt einen vorangestellten Apostroph. **Zahlen sind
ausgenommen**, damit eine negative Zahl eine Zahl bleibt — die Dateien sind
maschinenlesbar, und das ist keine Nebensache. `LIESMICH.txt` im Archiv und
`docs/Export-Format.md` beschreiben die Regel.

Der Weg über das Tabellenformat (Profile Standard und GuteSeele) ist **nicht**
betroffen: Dort entstehen echte Zellen vom Typ Zeichenkette, die nie als Formel
gelesen werden. Ein Apostroph wäre dort sichtbar. Dieser Unterschied steht als
Warnung im Code, damit ihn niemand der Einheitlichkeit halber einebnet.

### Der Wiederherstellungsschlüssel sagt jetzt, was an ihm nicht stimmt

Die Eingabe wurde normalisiert und gehasht, ohne zu prüfen, ob die Länge stimmt
oder die Zeichen überhaupt aus dem Alphabet stammen. Ein Tippfehler ergab
klaglos einen anderen Schlüssel, und die Meldung lautete „passt nicht“ — dieselbe
Meldung wie bei einem falschen Zettel. Das passiert in genau der Lage, in der
jemand ohnehin unter Druck steht.

Jetzt steht unter dem Feld sofort, welches Zeichen es im Schlüssel nicht gibt
oder wie viele Zeichen noch fehlen. Und ist der Schlüssel formal in Ordnung,
passt aber trotzdem nicht, sagt die Meldung genau das: kein Tippfehler,
sondern ein anderer Schlüssel.

Die Liste der nicht verwendeten Zeichen wird aus dem Alphabet **abgeleitet**
statt danebengeschrieben — sie lautet heute 0, 1, I, L, O und U. Beide Seiten
jeder klassischen Verwechslung fehlen also; deshalb sagt die Meldung
ausdrücklich **nicht**, welches Zeichen stattdessen gemeint sei.

Eine Streckung der Ableitung ist bewusst nicht dazugekommen: rund 98 Bit
Entropie, die Verzerrung durch die Restklassenbildung kostet davon 0,0 Bit.

### Die Sicherung nennt beim Einspielen ihr Herkunftskonto

Der Block mit Kontoname und Adresse steht seit dem ersten Dateiformat in jeder
Sicherung und wurde beim Einspielen nie angesehen. Wer zwei Konten betreut oder
eine Datei aus einer Übergabe bekommt, hatte keine Möglichkeit zu prüfen, ob es
die richtige ist — es blieb der Dateiname, und der nennt nur das Datum.

Die Angabe wird **angezeigt, nicht abgefragt**: Eine Sicherung in ein fremdes
Konto einzuspielen ist ein vorgesehener Vorgang, und eine Warnung vor etwas
Erlaubtem wird nach dem dritten Mal weggeklickt. Die Rückfrage bleibt dem Fall
vorbehalten, in dem tatsächlich etwas unlesbar bliebe — sie nennt jetzt
zusätzlich die Herkunftsadresse.

### Zwei Bausteine werden endlich benutzt

Beide entstanden in P0 und lagen seither ungenutzt neben ihren Kopien.

**Maskierung (B7).** Es gab vier Umsetzungen derselben Aufgabe in zwei
Bauarten, und alle vier maskierten drei Zeichen statt fünf — die
serverseitige Entsprechung `e()` maskiert beide Anführungszeichen mit. Für
Textpositionen reicht das, und Attributpositionen gibt es heute keine. Genau
deshalb war es gefährlich: Wer als Nächstes einen Wert in ein `title="…"`
schreibt, hat keinen Anhaltspunkt, dass die Fassung dafür nicht taugt.

Die kanonische Fassung stand in `missiontable.js` — einer Datei, die nur zwei
von fünf betroffenen Seiten laden. Sie ist deshalb nach `assets/html.js`
gewandert; `EdMissionTable.escape` und `.esc` bleiben als Weiterleitung.
`xmlEscape()` in `export.js` bleibt bewusst eigenständig: GPX ist XML und hat
eigene Regeln. Zwei Aufgaben, die sich ähneln, sind nicht dieselbe Aufgabe.

**Patientenanzeige (B8).** Fünf Seiten schrieben ihre Entschlüsselungsschleife
selbst aus und unterschieden sich dabei in Kleinigkeiten — welcher Zähler wann
hochgeht, ob `_pat` gesetzt wird. Genau solche Kleinigkeiten laufen beim
nächsten Mal auseinander. Alle benutzen jetzt `EdPat.entschluessleListe()`.

Dabei ist eine Lücke aufgefallen, die im Review nicht steht: **Der Export ließ
unlesbare Angaben stillschweigend leer.** Wer mit nicht passendem Schlüssel
exportiert, bekam eine Datei, die vollständig aussieht und es nicht ist. Jetzt
kommt vorher eine Rückfrage mit der Zahl der betroffenen Einsätze.

### Behobene Review-Befunde

M3-15, M5-12 (Bündel 5) · M5-04, M4-02, M5-05, M5-13, M6-03, M6-05, M6-06,
M2-06 (Bündel 6). Damit sind alle für P8 vorgesehenen Befunde erledigt.

### Geprüft

75 automatische Prüfungen gegen MariaDB 10.11 und echten HTTP-Verkehr, alle
bestanden: 36 zu `sql_in_bloecken`, Sicherungsaufbau und Wiedereinspielen,
25 zum Uhr-Weg, 14 zur Tagesansicht. Dazu die Maskierung des CSV-Exports und
die Prüfung des Wiederherstellungsschlüssels als reine Funktionsprüfungen.
Nicht ohne Testinstallation prüfbar: Darstellung, Karten-Popups, Dialoge und
das Zusammenspiel der Skripte im Browser.

## [Web 4.5.3] — 2026-08-13

Zwei Nachträge aus dem Betrieb, beide beim Durchprüfen von 4.5.1/4.5.2
gefunden.

### Die Anmeldesperre nannte 2 Stunden 15 Minuten statt 15 Minuten

Die Sperre selbst dauerte 15 Minuten — falsch war die **Meldung**. Sie kam
durch eine doppelte Umrechnung zustande: Der Endzeitpunkt wurde in der
Zeitzone des Datenbankservers geschrieben (Ortszeit), und die Anzeige las ihn
als Weltzeit und rechnete den Zonenversatz noch einmal drauf.

Dasselbe betraf vier weitere Anzeigen: „zuletzt gesehen" und „seit" im
Geräte-Reiter, dieselben Angaben auf der Admin-Nutzerseite, den Hinweis auf
neu gekoppelte Geräte auf der Startseite und die Spalte „angelegt" in der
Nutzerliste. Alle zeigten um den Zonenversatz zu späte Zeiten.

**Behoben ist das bereits mit 4.5.2**, wo die Zeitzone der Verbindung
ausdrücklich auf UTC gesetzt wurde. Was fehlte, war der Übergang: Zeilen aus
der Zeit davor tragen noch Ortszeit und wirken deshalb um den Zonenversatz in
der Zukunft — eine beim Umstieg laufende Anmeldesperre hielt entsprechend
länger. Eine Migration räumt diese Zeilen jetzt weg.

Betroffen war dabei nur ein Spaltentyp, und das ist der Punkt: `TIMESTAMP`
rechnet MySQL beim Schreiben selbst in Weltzeit um und beim Lesen zurück —
Kopplungscodes, Gerätezeiten und Anlegedaten waren also nie falsch
**gespeichert**, nur falsch **angezeigt**. `DATETIME` speichert dagegen, was
dasteht. Von den mit der Serverzeit gefüllten `DATETIME`-Spalten bleiben zwei:
die Ratenschutz-Zähler (werden geräumt) und die Gültigkeit offener
Passwort-Links. Letztere werden bewusst nicht angefasst — ein Einladungslink,
der jemandem unter den Händen ungültig wird, wäre der größere Schaden als
einer, der ein bis zwei Stunden zu lange lebt.

Die Einsatzzeiten und der Papierkorb waren nie betroffen; beide rechnen seit
jeher ausdrücklich in Weltzeit.

### Die Wartungsseite ist jetzt erreichbar

`update.php` war nur über die direkte Adresse aufzurufen — es gab keinen
Menüeintrag. Damit war die Auskunft aus 4.5.1 wertlos: Sie meldet, dass der
Aufräumjob dauerhaft scheitert, auf einer Seite, die niemand öffnet.

Unter **Administration** steht jetzt der Eintrag **Wartung**. Die Seite hat
außerdem die Seitenleiste bekommen, die ihr fehlte — wer dort landete, kam
vorher nur über den Zurück-Knopf wieder heraus. Am Verhalten ändert sich
nichts: Das bloße Aufrufen führt weiterhin keine Migration aus, es zeigt nur
an, was anstünde.

## [Web 4.5.2] — 2026-08-13

Zweiter Teil des Aufräumens: siebzehn Stellen ohne gemeinsames Thema außer
diesem — an jeder wich der Code von einer Regel ab, die er sonst überall
befolgt. Keine neuen Funktionen, keine Schemaänderung.

### Die Zeitzone der Datenbankverbindung hing am Hoster

Die Anwendung benutzt zwei Zeitfunktionen, und zwar mit Absicht: `UTC` für den
Papierkorb, dessen Frist über 30 Tage läuft, und die Serverzeit für alles
Kurzlebige — Ratenschutz-Fenster, Gültigkeit von Tokens und Kopplungscodes.

Welche Zeit die Serverzeit ist, war nirgends festgelegt. Sie kam aus der
Einstellung des Datenbankservers, also vom Hoster, und konnte sich bei einem
Serverumzug ändern, ohne dass hier jemand etwas tut. Steht sie auf einer
Ortszeit, laufen beide Zeitrechnungen um den Zonenversatz auseinander: Ein
Ratenschutz-Fenster, das in Ortszeit geschrieben und gegen UTC verglichen wird,
ist ein bis zwei Stunden zu früh oder zu spät abgelaufen.

Die Verbindung setzt jetzt ausdrücklich UTC. Der Unterschied im Code bleibt
stehen — er sagt, was gemeint ist. Die **Anzeige** ist davon unberührt; sie
rechnet weiter in die eingestellte Ortszeit um.

### Eine Notiz „0" verschwand beim Speichern

Beim Speichern der Flugtag-Angaben stand eine Prüfung auf Wahrheitswert. Die
Zeichenkette `0` ist in PHP unwahr, genau wie eine leere Eingabe — ein Feld,
in dem nur eine Null stand, wurde damit zu „nichts" und war nach dem Speichern
fort. Betroffen waren alle Felder des Flugtags, auch die Notiz. `00` kam
dagegen durch, was den Fehler schwer bemerkbar machte.

### Antworten der Oberfläche werden nicht mehr zwischengespeichert

Den Kopf gegen das Zwischenspeichern setzte genau ein Weg: die Sicherung. Vier
weitere liefern denselben verschlüsselten Inhalt aus — Tagesdaten, Zeitraum,
Suche, Einzeleinsatz. Der Inhalt ist verschlüsselt, die Hülle darum herum
(Datum, Uhrzeit, Einsatznummer, Koordinaten) nicht. An einem gemeinsam
genutzten Rechner reichte die Zurück-Taste, um eine Antwort aus dem Speicher
des Browsers zu holen, nachdem sich jemand abgemeldet hatte.

Der Kopf sitzt jetzt an der Stelle, durch die jede Antwort geht. Außerdem
weisen die nur lesenden Wege andere Anfragearten mit einer klaren Meldung ab,
statt sie wie eine Leseanfrage zu behandeln.

### Ein fehlgeschlagener Passwortwechsel hinterließ einen kaputten Zustand

Der Browser verwarf den alten Schlüssel und setzte den neuen, **bevor** der
Server überhaupt gefragt war. Lehnte der Server ab — falsches aktuelles
Passwort, abgelaufenes Formular, Ratenschutz —, lag im Tab danach ein
Schlüssel, der nicht zu den gespeicherten Angaben passte. Die geschützten
Angaben waren unlesbar, und zwar so, wie es aussieht, wenn es sie gar nicht
gäbe.

Der neue Schlüssel wandert jetzt in ein Vormerkfach und wird erst übernommen,
wenn der Server den Wechsel bestätigt hat. Bei einem Fehlschlag bleibt der
alte unberührt.

### Weitere Stellen

* **Der Einrichtungsassistent** sichert seine Sitzung jetzt wie die
  Anwendung — er führt das Datenbank-Passwort im Formular und lief bis eben
  ohne diese Vorkehrungen.
* **Zeilenumbrüche in einer Empfängeradresse** werden beim Mailversand
  abgewiesen. Eine solche Adresse hätte eigene Anweisungen und Kopfzeilen in
  die Nachricht einschleusen können. Die Konfiguration wird dabei einmal
  statt zweimal gelesen.
* **Eine gepackte Sicherung** auf einem Browser ohne Entpackfunktion meldete
  bisher „Passwort falsch oder Datei beschädigt" — die denkbar
  irreführendste Auskunft. Jetzt steht dort, woran es wirklich liegt.
* **Das Format der Sicherung ist jetzt aufgezählt** statt „alles, was in der
  Tabelle steht". Dabei fiel eine tote Altspalte auf: `other_resources` wurde
  seit der Umstellung auf einzeln entfernbare Rettungsmittel von niemandem
  mehr gefüllt, wanderte aber in jede Sicherung. Sie ist jetzt draußen; die
  Rettungsmittel selbst sind wie bisher enthalten.
* **Die Nutzerzeile** wird mit benannten Spalten gelesen. Der Hash des
  Anmeldetokens liegt damit nicht mehr bei jeder Anfrage im Speicher.
* **Vier Stellen mit Werten im SQL-Text** verwenden jetzt vorbereitete
  Anweisungen — es waren die einzigen Abweichungen von einer sonst lückenlosen
  Regel.
* **Der Sortierpfeil** erscheint auf allen Tabellen sofort. Die
  Zeitraumübersicht zeigte ihn erst nach dem ersten Klick, obwohl sie beim
  Öffnen sortiert ist.
* **Ein leeres `<section>`** in der Nutzerverwaltung, ein Kommentarkopf mit
  veralteter Seitenliste, eine befüllte aber nie gelesene Variable im Import
  und ein Platzhalterbau, der an einem literalen Prozentzeichen hart
  abgebrochen wäre: entfernt beziehungsweise berichtigt.
* **Die Weiterleitung von `geraete.php`** hat ein Ablaufdatum bekommen
  (Web 5.0.0) statt unbefristet liegen zu bleiben.
* **Höhenangaben** werden nur noch umgewandelt, wenn dabei auch eine Zahl
  herauskommt. Die Meereshöhe 0 ist ein gültiger Wert und war von einem
  Umwandlungsrest nicht zu unterscheiden.

### Bekannt und hier nicht geändert

Die Einsatzort-Höhe steht in der Sicherung, kommt beim Einspielen aber nicht
zurück — der Einspielweg kennt nur die eingebbaren Felder, und die Höhe wird
beim Uhr-Upload gerechnet. Das Aufzählen der Spalten hat diese Asymmetrie
sichtbar gemacht; sie zu beheben hieße, den Einspielweg zu ändern.

## [Web 4.5.1] — 2026-08-13

Aufräumen: fünfzehn Stellen, an denen die Fehlerbehandlung zu viel oder zu
wenig gefangen hat. Keine neue Funktion, keine Änderung am Schema.

### Der Aufräumjob hielt sich selbst auf

Die tägliche Wartung läuft huckepack auf Anfragen. Sie setzte ihre Tagesmarke,
bevor sie anfing (richtig — sonst räumen zwei gleichzeitige Anfragen doppelt
auf), und ihr Fehlerblock war leer. Zusammen ergab das eine Falle ohne
Ausgang: Scheiterte ein Schritt, brach der gemeinsame Block ab, alle folgenden
Schritte entfielen — und weil die Marke schon stand, lief an diesem Tag nichts
mehr. Am nächsten Tag begann es von vorn und scheiterte an derselben Stelle.
Dauerhaft, und nirgends stand etwas davon.

Am spürbarsten beim Papierkorb: Er stand als vorletzter Schritt. „Endgültig
nach 30 Tagen" wäre stillschweigend zu „nie" geworden.

Jetzt hat jeder der sieben Schritte seinen eigenen Fehlerblock, Fehler landen
im Fehlerprotokoll des Webspace, und eine zweite Marke hält fest, wann zuletzt
ein Lauf **vollständig** durchging. Die Wartungsseite zeigt beide an: Klaffen
sie auseinander, scheitert etwas dauerhaft.

### Ein Spurpunkt kann nicht mehr spurlos verschwinden

Beim Hochladen von der Uhr stand `INSERT IGNORE`. Gedacht war es für die
Wiederholung — lädt die Uhr dieselben Punkte erneut hoch, sollen bekannte
Sequenznummern übergangen werden. Unterdrückt hat es jeden Fehler.

Der Schaden war dauerhaft: Die Fortsetzungsmarke, die die Uhr zurückbekommt,
ist die höchste gespeicherte Nummer plus eins. Ein Punkt, der beim Einfügen
scheiterte, hinterließ eine Lücke — die Marke sprang darüber hinweg, die Uhr
setzte dahinter fort und sendete ihn **nie wieder**. Der Upload meldete dabei
Erfolg.

Jetzt wird nur noch der Schlüsselkonflikt übergangen. Jeder andere Fehler
bricht den Upload ab und rollt zurück; die Uhr versucht es beim nächsten Mal
mit derselben Marke erneut. Ein sichtbar gescheiterter Upload ist besser als
ein stillschweigend unvollständiger.

### Fehlermeldungen der Endpunkte nennen keine Interna mehr

Neun Endpunkte gaben den Text der Ausnahme unverändert nach außen — Tabellen-
und Spaltennamen, Teile der Abfrage, bei Verbindungsfehlern auch Hostnamen.
Das Skript zeigte den Text direkt an, er stand also auf dem Bildschirm und in
jedem Screenshot, den jemand zur Fehlersuche verschickte.

Für die Fehlersuche war er trotzdem unbrauchbar: Was auf dem Bildschirm stand,
stand nirgends sonst. Jetzt geht der volle Text ins Fehlerprotokoll, nach außen
geht eine achtstellige Kennung — kurz genug fürs Telefon, eindeutig genug fürs
Protokoll.

Der Einrichtungs-Assistent und die Wartungsseite zeigen ihre Fehler weiterhin
im Klartext. Beide laufen nur für Verwaltende, und bei der Ersteinrichtung gibt
es noch kein Protokoll, in dem man nachsehen könnte.

### Ein halber Zeitraum beim Export ergab den gesamten Bestand

Der Zeitraumfilter griff nur, wenn **beide** Grenzen gesetzt waren. Fehlte
eine, fiel die Bedingung stillschweigend weg. Wer „ab 01.01.2026" eingab, bekam
eine Datei mit allem seit Beginn — ohne Fehler, ohne Meldung, nur größer als
erwartet. Bei Patientendaten ist das keine Kleinigkeit. Beide Grenzen leer
heißt weiterhin „alles"; genau eine Grenze wird jetzt abgelehnt.

### Eine Monatsangabe außerhalb von 01–12 lieferte einen falschen Monat

Die Übersicht prüfte nur, dass zwei Ziffern kamen. `m=00` ergab den Dezember
des Vorjahres, weil die Datumsrechnung stillschweigend auf einen Ersatzwert
zurückfiel. Eine Übersicht, die einen anderen Monat zeigt als den angefragten,
ist schlimmer als eine, die sich weigert.

### Weitere Änderungen

* **Endgültiges Löschen im Papierkorb** prüft das Datumsformat jetzt genauso
  wie das Wiederherstellen. Vorher war ausgerechnet die unumkehrbare Handlung
  schwächer geprüft.
* **Gerätekennungen** entstehen aus 16 statt 4 Zufallsbytes. Vorhandene Geräte
  behalten ihre Kennung und müssen nicht neu gekoppelt werden.
* **Das virtuelle Gerät „Manuelle Einträge"** wird über Kennung *und*
  Nutzerkennung gesucht. Vorher trug allein der Name die Zugehörigkeit.
* **Kopplungscodes:** Ein neuer Versuch nur noch beim tatsächlichen
  Zusammentreffen zweier Codes. Vorher galt jeder Datenbankfehler als
  Kollision — bei fehlender Tabelle lief die Schleife fünfmal ins Leere und
  riet dann zum erneuten Versuch.
* **Der Migrationsbericht** unterscheidet ausgeführte von bereits erledigten
  Teilschritten. „Erfolgreich angewendet" stand vorher auch dort, wo nichts zu
  tun war.
* **Der Einrichtungs-Assistent** maskiert Fehlermeldungen an der Ausgabestelle
  statt an zwei von zehn Quellen.
* **Beim Passwortwechsel** entfällt ein Abschneiden der Schlüsselhülle, das nie
  greifen konnte — aber bei einer späteren Anhebung der Prüfgrenze
  stillschweigend Patientendaten unlesbar gemacht hätte.
* **Ein Zugriff auf die erste Phase** eines Einsatzes prüft selbst, ob es sie
  gibt.

### Technisch

* `fehler_kennung()` und `json_fehler()` in `db.php`.
* `ist_dublettenfehler()` (aus 4.5.0) trägt jetzt auch die Unterscheidung beim
  Spurpunkt-Upload und bei der Codeerzeugung.
* Zweiter Zustandsschlüssel `last_cleanup_ok` in `app_state` — kein
  Schemawechsel, die Tabelle nimmt beliebige Schlüssel auf.
* Bereits durch 4.2.0 erledigt und hier nur nachgewiesen: Spurpunkte müssen als
  Liste kommen (`ist_liste()`).

## [Web 4.5.0] — 2026-08-12

Bis hierher endete eine Sitzung nur durch Abmelden oder Zeitablauf. Weder ein
Rollenentzug noch ein gelöschtes Konto noch ein Passwortwechsel erreichten sie
— und das sind genau die drei Handgriffe, mit denen man jemandem den Zugang
nimmt.

### Rolle und Konto werden bei jeder Anfrage geprüft

Die Rolle wurde bei der Anmeldung **einmal** in die Sitzung geschrieben und nie
wieder nachgesehen. Wem die Administratorrolle entzogen wurde, behielt seine
Rechte, solange der Tab offen blieb. Wessen Konto gelöscht wurde, blieb
angemeldet und arbeitete weiter.

Beides kommt jetzt aus der Nutzerzeile — die bei jeder Anfrage ohnehin gelesen
wurde, nur eben erst weiter unten und nur für den Anzeigenamen. Der Rollenentzug
wirkt damit beim nächsten Klick. Ein gelöschtes Konto beendet die Sitzung mit
einer Meldung, statt sie stehen zu lassen.

### Ein Passwortwechsel beendet die anderen Sitzungen

Wer sein Passwort wechselt, **weil** er Missbrauch vermutet, will genau eines
erreichen: dass der andere draußen ist. Das erreichte er bisher nicht — eine
offene Sitzung hängt am Sitzungscookie, nicht am Passwort.

Jeder Passwortwechsel erhöht jetzt einen Zähler am Konto. Jede Anfrage
vergleicht ihren Stand dagegen; wer noch den alten trägt, wird abgemeldet und
bekommt den Grund genannt. Die Sitzung, die den Wechsel auslöst, zieht den
neuen Stand mit und bleibt bestehen — beim Weg über „Passwort vergessen" ist
ohnehin niemand angemeldet, dort fallen alle.

Gleichzeitig werden **alle** offenen Links zum Zurücksetzen entwertet, nicht
nur der gerade benutzte. Ein Einladungslink aus der Nutzerverwaltung ist 24
Stunden gültig und hätte den soeben gewählten Zustand sonst wieder
überschreiben können — mit einem Passwort, das jemand anders kennt.

### Der Zurücksetzen-Link steht nicht mehr in der Adresszeile

Der Token stand als Parameter in der Adresse und landete damit im Verlauf des
Browsers, im Zugriffsprotokoll des Webservers und in jedem Screenshot der
Seite. Wer ihn hat, kann das Passwort setzen.

Beim ersten Öffnen wandert er jetzt in eine eigene Sitzung, und die Seite ruft
sich ohne Parameter neu auf. Zusätzlich unterbindet die Seite das Mitsenden der
Herkunftsadresse und hält sich aus dem Zwischenspeicher.

Der dafür nötige Cookie trägt einen **eigenen Namen** und berührt die Sitzung
der Anwendung nicht. Wer Cookies für die Seite blockiert, bekommt das gesagt
(„Cookie nötig") statt eines irreführenden „Link ungültig".

### Nutzer anlegen sagt jetzt, was passiert ist

Drei Dinge an derselben Stelle:

* Eine bereits vorhandene Adresse führte zu einer ungefangenen Ausnahme — der
  Admin sah eine weiße Seite statt einer Auskunft.
* Konto und Setz-Token entstanden in zwei getrennten Schritten. Scheiterte der
  zweite, blieb ein Konto ohne jeden Weg zu einem Passwort zurück.
* Das Ergebnis des Mailversands wurde weggeworfen und in jedem Fall
  „verschickt" gemeldet. Bei einem Fehlschlag existierte das Konto, ein
  gültiger Token lag in der Datenbank — nur hatte niemand den Link.

Jetzt: Vorabprüfung mit verständlicher Meldung, Konto und Token in einer
Transaktion, und bei fehlgeschlagenem Versand wird der Link zur Weitergabe auf
anderem Weg angezeigt.

### „Adresse bereits verwendet" nur noch, wenn es stimmt

Beim Ändern einer E-Mail-Adresse wurde **jeder** Datenbankfehler als Dublette
gemeldet. Eine volle Platte, eine abgerissene Verbindung, ein Rechteproblem:
alles erschien als „diese Adresse wird bereits verwendet" und schickte die
Fehlersuche zuverlässig in die falsche Richtung. Geprüft wird jetzt der
tatsächliche Schlüsselkonflikt; alles andere bekommt eine ehrliche Meldung und
landet im Fehlerprotokoll.

### Eine Schreibweise für E-Mail-Adressen

Die Adresse ist die Kontokennung und wurde an acht Stellen unterschiedlich
behandelt — mal kleingeschrieben, mal nur von Leerzeichen befreit. Dass das
funktionierte, lag allein an der Sortierregel der Datenbank, nicht am Code.

Nebenbei behoben: Die Anmeldung meldete ihren Erfolg an den Zähler der
Salz-Abfrage mit der Adresse **wie getippt**, während die Salz-Abfrage
kleingeschrieben zählt. Wer „Max@…" tippte, gab seinen Versuch dort nie
zurück.

Bestehende Einträge werden nicht angefasst: Die Spalte trägt seit 4.0.0 eine
Sortierregel ohne Rücksicht auf Groß- und Kleinschreibung, der Vergleich trifft
also ohnehin.

### Sitzungsende bei Datenabrufen

Endet die Sitzung mitten in einem Abruf der Oberfläche, antwortet der Server
jetzt mit einem Fehlercode und JSON statt mit der HTML-Seite. Das Skript sah
vorher HTML, wo es JSON erwartete, und meldete irgendetwas Allgemeines statt
„die Sitzung ist beendet".

### Technisch

* Neu: `server/email_lib.php` — Normalisierung, Prüfung und Dublettenerkennung
  für E-Mail-Adressen, ohne Abhängigkeiten (auch von `install.php` nutzbar).
* `session_lib.php`: `session_verwerfen()` beendet eine Sitzung ohne Ausgabe.
* `auth_guard.php`: `ist_admin()` als einzige Rollenprüfung; `require_admin()`
  setzt darauf auf.
* Keine Migration nötig — `session_epoch` und die Sortierregel liegen seit
  4.0.0 im Schema.

## [Web 4.4.0] — 2026-08-12

Dieses Paket betrifft die Endpunkte, die **ohne Anmeldung** erreichbar sind:
Anmeldung, Salz-Abfrage, Passwort-Zurücksetzen, Kopplung und Upload der Uhr. Sie
alle sind Türen nach außen, und an allen fünf ließ sich bisher etwas ablesen
oder etwas beliebig oft wiederholen.

### Die Bremse bei der Anmeldung liegt nicht mehr im Browser des Aufrufers

Nach fünf Fehlversuchen kamen dreißig Sekunden Pause — gezählt in der Sitzung.
Wer das Cookie wegwarf, hatte wieder fünf Versuche frei; ein Programm, das gar
kein Cookie annimmt, verbrauchte nie eines. Das war keine Bremse, sondern eine
Bequemlichkeit gegen Vertippen.

Gezählt wird jetzt in der Datenbank, **je Kontokennung und je IP-Adresse**:
zehn Fehlversuche in fünfzehn Minuten, dann fünfzehn Minuten gesperrt. Die
Meldung nennt, ab wann es wieder geht. Eine erfolgreiche Anmeldung setzt die
Zähler zurück.

Bewusst in Kauf genommen: Wer eine E-Mail-Adresse kennt, kann das zugehörige
Konto durch Fehlversuche zeitweise sperren. Die Sperre ist kurz und ihr Ende
steht in der Meldung. Nur nach IP zu zählen hieße, ein über viele Rechner
verteiltes Durchprobieren einer einzelnen Adresse völlig ungebremst zu lassen.

### Ratenschutz auch auf Salz-Abfrage und Passwort-Zurücksetzen

Beide Endpunkte waren ohne Anmeldung und ohne jede Begrenzung erreichbar. Der
eine taugte damit als Adressenprüfer im Großen, der andere zusätzlich als
Mailschleuder auf fremde Postfächer. Gezählt wird jetzt jede Anfrage — beide
Endpunkte kennen kein Scheitern, begrenzt wird die Menge: dreißig Salz-Abfragen
je Viertelstunde, fünf Zurücksetzen-Anforderungen je Stunde.

### Beim Zurücksetzen gilt immer nur der zuletzt verschickte Link

Jede Anforderung legte bisher einen weiteren Token an, und alle blieben eine
Stunde gültig. Wer den Knopf zehnmal drückte, hatte zehn gültige Links in der
Welt, von denen jeder einzelne genügt. Jetzt entwertet ein neuer Link den
vorherigen — es gibt zu jedem Zeitpunkt höchstens einen. Die E-Mail sagt das
auch.

### Behoben — Die Antwortzeit verriet, welche Konten es gibt

Der Antworttext beim Zurücksetzen war für eine vorhandene und eine unbekannte
Adresse absichtlich derselbe. **Die Dauer war es nicht:** Bei einem vorhandenen
Konto lief ein vollständiges Mailgespräch, sonst kam die Antwort sofort. Eine
einzige Anfrage je Adresse genügte, um Konten zu finden — dieselbe Auskunft wie
eine unterschiedliche Meldung, nur leiser.

Die Antwort wird jetzt **abgeschlossen, bevor der Versand beginnt**. Gemessen
gegen einen Mailserver, der annimmt und nie antwortet (Zeitlimit fünfzehn
Sekunden): beide Zweige 0,51 Sekunden, Unterschied 0,0 %. Vorher wären es
15 Sekunden gegen 0,5 gewesen.

Wo die PHP-Anbindung des Webspace das nicht verbindlich zusagen kann, steht das
jetzt auf der Wartungsseite unter **Umgebung** — es ist die Eigenschaft, an der
die Gleichheit beider Zweige hängt, und sie ließ sich sonst nirgends ablesen.

### Behoben — Die Antwortzeit verriet auch, welche Geräte es gibt

Dieselbe Lücke beim Upload der Uhr und bei der Anmeldung: Bei unbekannter
Kennung kam die Abweisung sofort, bei bekannter lief erst eine
Passwortprüfung. Der Unterschied war ohne jede Zugangsdaten messbar — und eine
Gerätekennung ist die Hälfte dessen, was ein Upload braucht. Beide Wege prüfen
jetzt auch im unbekannten Fall gegen einen festen Vergleichswert. Gemessen:
Abweichung 1,1 % statt einer ganzen Passwortprüfung.

### Höchstens fünf Geräte je Konto, und ein Hinweis bei jedem neuen

Ein Gerät ist ein Satz Zugangsdaten. Ohne Obergrenze konnte ein Konto beliebig
viele davon ansammeln — ein eingeschleustes Gerät stünde einfach als weitere
Zeile in einer Liste, die niemand zählt.

- **Fünf Geräte je Konto**, geprüft beim Koppeln *und* beim manuellen Anlegen.
  Deaktivierte zählen mit, weil ihre Zugangsdaten bestehen bleiben; erst Löschen
  gibt einen Platz frei. Das virtuelle Gerät „Manuelle Einträge" zählt nicht mit
  — es steht schon in der Geräteliste nicht.
- Ist die Grenze erreicht, wird **gar kein Kopplungscode mehr erzeugt**. Sonst
  wäre der Code beim Einlösen verbraucht, ohne dass ein Gerät entsteht.
- **E-Mail an den Kontoinhaber**, sobald ein Gerät gekoppelt wurde, mit
  Gerätekennung, Zeitpunkt und dem Weg zum Entfernen. Sie erreicht die Person
  auch dann, wenn sie sich gerade nicht anmeldet — und genau das ist der Fall,
  um den es geht.
- **Hinweis auf der Startseite und im Geräte-Reiter** für Geräte, die in den
  letzten sieben Tagen hinzugekommen sind. Die zweite, langsamere Spur für alle,
  die ihre Post nicht lesen.

### Geändert

- `smtp_send()` nimmt ein Zeitlimit entgegen. Bei der Kopplung steht es auf
  fünf Sekunden: Die Uhr wartet auf die Antwort, und ihr Code ist bereits
  verbraucht — eine Kopplung darf nicht an einem langsamen Mailserver scheitern.
- Der Geräte-Reiter nennt den Zählstand („belegt: 3 von 5").
- Die Anmeldeseite meldet eine Sperre der Salz-Abfrage jetzt als solche. Vorher
  lief sie in den allgemeinen Fehlerzweig und behauptete, der Browser
  unterstütze die nötige Verschlüsselung nicht.

### Keine Datenbankänderung

Dieses Paket kommt ohne Migration aus.


### Ein Flugtag im Papierkorb wird nicht mehr stillschweigend übergangen

Drei Schreibwege führen zu einem Flugtag, und alle drei verhielten sich falsch,
wenn er im Papierkorb lag:

- **Formular:** Die Aktualisierung hatte keine Bedingung auf den Löschzustand.
  Sie überschrieb die Angaben und ließ den Tag gelöscht — die Eingabe verschwand
  spurlos, die Meldung lautete „Gespeichert." Jetzt wird abgelehnt und der Grund
  genannt.
- **Import:** Er holte den Tag stillschweigend aus dem Papierkorb zurück, samt
  alter Angaben. Jetzt wird er übersprungen und in der Meldung genannt.
- **Wiedereinspielen:** Es tat nichts — aber eben still, ohne Zählung und ohne
  Erwähnung. Jetzt wird der Fall benannt.

**Warum ablehnen und nicht zurückholen:** Das Löschen war eine bewusste
Handlung. Sie durch eine Nebenwirkung rückgängig zu machen, ist eine
Überraschung — und zwar eine, die niemand sieht. Der Papierkorb hat eine eigene
Wiederherstellungsfunktion.

Auch beim **Lesen** wird der Zustand jetzt gemeldet. Vorher lieferte die
Schnittstelle für einen gelöschten Tag schlicht nichts, nicht unterscheidbar
von „für diesen Tag wurde noch nichts eingetragen". Wer seine Angaben vermisste,
suchte den Fehler bei sich. Die Tagesansicht zeigt nun einen Hinweis.

### Behoben — Ein gelöschtes Ruhesegment kehrte immer wieder zurück

Die Sperrliste, die verhindert, dass die Uhr einen gelöschten Datensatz neu
anlegt, war an **beiden** Enden nur für Einsätze umgesetzt: Sie wurde nur für
Einsätze befüllt und nur im Einsatz-Zweig abgefragt. Ein endgültig gelöschtes
Ruhesegment wurde deshalb von der nächsten Nachlieferung wieder angelegt — und
beim erneuten Löschen wieder. Wer eine Uhr im Einsatz hat, kam aus dieser
Schleife nicht heraus.

Im selben Zweig fehlte auch die Prüfung auf „im Papierkorb", sodass ein
gelöschtes Ruhesegment weiter Spurpunkte sammelte.

Beide Prüfungen stehen jetzt **vor** der Fallunterscheidung und gelten damit für
beide Arten. Die Sperrliste unterscheidet über `owner_type` (seit Web 4.0.0),
welche Art gemeint ist — Einsätze und Ruhesegmente vergeben ihre Kennungen
unabhängig voneinander.

## [Web 4.3.0] — 2026-08-08

### Ein Flugtag im Papierkorb wird nicht mehr stillschweigend übergangen

Drei Schreibwege führen zu einem Flugtag, und alle drei verhielten sich falsch,
wenn er im Papierkorb lag:

- **Formular:** Die Aktualisierung hatte keine Bedingung auf den Löschzustand.
  Sie überschrieb die Angaben und ließ den Tag gelöscht — die Eingabe verschwand
  spurlos, die Meldung lautete „Gespeichert." Jetzt wird abgelehnt und der Grund
  genannt.
- **Import:** Er holte den Tag stillschweigend aus dem Papierkorb zurück, samt
  alter Angaben. Jetzt wird er übersprungen und in der Meldung genannt.
- **Wiedereinspielen:** Es tat nichts — aber eben still, ohne Zählung und ohne
  Erwähnung. Jetzt wird der Fall benannt.

**Warum ablehnen und nicht zurückholen:** Das Löschen war eine bewusste
Handlung. Sie durch eine Nebenwirkung rückgängig zu machen, ist eine
Überraschung — und zwar eine, die niemand sieht. Der Papierkorb hat eine eigene
Wiederherstellungsfunktion.

Auch beim **Lesen** wird der Zustand jetzt gemeldet. Vorher lieferte die
Schnittstelle für einen gelöschten Tag schlicht nichts, nicht unterscheidbar
von „für diesen Tag wurde noch nichts eingetragen". Wer seine Angaben vermisste,
suchte den Fehler bei sich. Die Tagesansicht zeigt nun einen Hinweis.

### Behoben — Ein gelöschtes Ruhesegment kehrte immer wieder zurück

Die Sperrliste, die verhindert, dass die Uhr einen gelöschten Datensatz neu
anlegt, war an **beiden** Enden nur für Einsätze umgesetzt: Sie wurde nur für
Einsätze befüllt und nur im Einsatz-Zweig abgefragt. Ein endgültig gelöschtes
Ruhesegment wurde deshalb von der nächsten Nachlieferung wieder angelegt — und
beim erneuten Löschen wieder. Wer eine Uhr im Einsatz hat, kam aus dieser
Schleife nicht heraus.

Im selben Zweig fehlte auch die Prüfung auf „im Papierkorb", sodass ein
gelöschtes Ruhesegment weiter Spurpunkte sammelte.

Beide Prüfungen stehen jetzt **vor** der Fallunterscheidung und gelten damit für
beide Arten. Die Sperrliste unterscheidet über `owner_type` (seit Web 4.0.0),
welche Art gemeint ist — Einsätze und Ruhesegmente vergeben ihre Kennungen
unabhängig voneinander.

## [Web 4.2.0] — 2026-08-08

### Alle vier Schreibwege prüfen jetzt gleich

Dieselben Tabellen werden über vier unabhängige Wege beschrieben: Formular,
Uhr, Import und Wiedereinspielen einer Sicherung. Jeder führte eigene
Prüfungen — und die Sorgfalt verlief **genau umgekehrt zur
Vertrauenswürdigkeit der Quelle**:

| | Formular | Import | Uhr | Sicherung |
|---|---|---|---|---|
| vorher | 5 von 9 | 8 von 9 | 5 von 9 | **0 von 9** |
| jetzt | alle | alle | alle | alle |

Ausgerechnet das Wiedereinspielen prüfte gar nichts — dabei kann die Datei aus
beliebiger Herkunft stammen, während der Uhr-Weg immerhin einen Schlüssel
verlangt. Seit dieser Auslieferung ruft jeder Weg dieselbe Prüfschicht auf
(`server/validate_lib.php`, seit Web 4.0.0 vorhanden).

### Behoben — Ein unmöglicher Kalendertag wurde stillschweigend verschoben

Die Datumsumwandlung liefert bei einem unmöglichen Tag kein Fehlerergebnis,
sondern rechnet weiter: Aus dem 30. Februar wird der 2. März. Sichtbar wird das
nur über die Warnungsabfrage der Datumsklasse, und die wurde nirgends
abgefragt. Ein Tippfehler in einer Importdatei verschob damit die Phasenzeiten
eines ganzen Einsatzes auf einen falschen Tag — ohne jede Meldung. Jetzt wird
ein solcher Tag abgelehnt, auf allen vier Wegen.

### Behoben — Das Wiedereinspielen brach beim ersten schlechten Wert komplett ab

Ein einziger ungültiger Wert ließ die **gesamte** Wiederherstellung scheitern,
statt die eine Zeile zu überspringen. Das ist die falsche Richtung: Wer eine
Wiederherstellung startet, hat meist keinen zweiten Versuch. Jetzt wird je
Datensatz übersprungen und am Ende gesagt, wie viele und warum.

Damit ist auch der letzte Weg geschlossen, über den **Phase 10** noch in die
Datenbank zurückkehren konnte.

### Behoben — Ein Import verlor genau die Korrektur, um die es ging

Beim Überschreiben eines vorhandenen Einsatzes wurde die Zugehörigkeit aus der
Zahl der geänderten Zeilen erschlossen. Die Datenbank liefert aber die Zahl der
**geänderten**, nicht der **getroffenen** Zeilen: Wer alle Werte auf das setzt,
was schon dasteht, bekommt null zurück. Daraus schloss der Code „gehört jemand
anderem" und übersprang den Einsatz — samt der danach folgenden Blöcke für
Phasen, Reanimation und Rettungsmittel.

Der praktisch wichtigste Fall ist zugleich der schlimmste: Jemand importiert
erneut, weil er **nur die Phasenzeiten korrigiert** hat. Die Kopfdaten sind
unverändert, also greift der Fehlschluss — und genau die Korrektur wird
verworfen. Gemeldet wurde „übersprungen", was nach „war schon da" klingt.
Jetzt wird die Zugehörigkeit direkt abgefragt.

### Behoben — Mehrfach gesetzte Phasen gingen beim Import verloren

Der Import verwarf die zweite Zeile mit derselben Phasennummer. Das
widerspricht dem JSON-Vertrag: Mehrfache Einträge sind ausdrücklich erlaubt,
weil eine erneut gesetzte Phase eine **Korrektur** ist und damit eine
Information. Der Uhr-Weg speicherte sie, der Import warf sie weg — dieselben
Daten ergaben je nach Weg einen anderen Bestand, und ein Rückimport der eigenen
Exporte verlor stillschweigend Zeilen.

Statt der Entdoppelung begrenzt jetzt eine Mengengrenze (500 Phasen je Einsatz).
Sie ist bewusst hoch: Sie schützt vor einer entgleisten Nutzlast und ist kein
Ersatz für die Entdoppelung.

### Behoben — Geschützte Angaben konnten unbemerkt ungespeichert bleiben

Passte der Chiffretext nicht zum erwarteten Muster, wurde die Spalte im
Formular einfach nicht in die Aktualisierung aufgenommen: kein Fehler, keine
Meldung, der bisherige Block blieb stehen. Wer eine Diagnose korrigierte und
„gespeichert" las, hatte danach die **alte** Diagnose in der Datenbank. Jetzt
wird gemeldet und nichts geändert.

Dieselbe Stelle war beim Passwortwechsel längst so gelöst — dort steht das
stille Übergehen sogar als früherer Fehler im Kommentar.

### Geändert — Eine Grenze für den Patientenblock statt dreier

40 bis 60000 Zeichen, auf allen vier Wegen. Vorher: 16…8000 im Formular,
20…60000 im Import, gar keine beim Wiedereinspielen. Die Untergrenze ist jetzt
hergeleitet statt geschätzt — AES-256-GCM legt 12 Byte Zufallswert davor und
hängt 16 Byte Prüfwert an, also mindestens 28 Byte oder 40 base64-Zeichen. Alle
drei alten Untergrenzen lagen darunter.

### Neu — Verworfene Werte werden genannt

Bisher verschwand ein verworfener Wert spurlos; der Upload meldete Erfolg, und
die Phase fehlte trotzdem.

- **Uhr:** Die Antwort enthält jetzt bei Bedarf `rejected` mit den Ursachen.
  `ok: true` zusammen mit `rejected` heißt: angekommen, aber nicht vollständig
  übernommen.
- **Import und Wiedereinspielen:** Die übersprungenen Datensätze werden nach
  Ursache aufgeschlüsselt. „40 übersprungen" war nicht deutbar — es konnte
  „alles war schon da" heißen (gut) oder „alles war kaputt" (schlecht). Vier
  verschiedene Gründe fielen in einen Zähler.

### Kleinigkeit

Die Meldung zu Koordinaten außerhalb des gültigen Bereichs lautete „außerhalb
von ±9" statt „±90" — nachlaufende Nullen wurden abgeschnitten. Eine
Fehlermeldung, die selbst falsch ist, kostet mehr Zeit als gar keine.

## [Web 4.1.2] — 2026-08-08

### Die Kette „unlesbarer Schlüssel" ist geschlossen

Fünf Befunde, die einzeln je harmlos aussehen und zusammen dazu führen können,
dass geschützte Angaben unbemerkt unlesbar werden und der Verlust erst auffällt,
wenn er nicht mehr rückgängig zu machen ist.

### Behoben — Ein Fehler beim Passwortwechsel konnte alle Angaben endgültig unlesbar machen

Beim Passwortwechsel wird der Inhaltsschlüssel im Browser aus der alten Hülle
geholt und in eine neue gepackt. Der Server kann keine der beiden öffnen — er
konnte deshalb **nicht erkennen, ob darin überhaupt derselbe Schlüssel steckt**.
Enthielte die neue Hülle einen anderen, wäre danach jeder vorhandene Datensatz
unlesbar, und zwar endgültig: Die alte Hülle ist dann überschrieben.

Jetzt sendet der Browser eine **Prüfsumme des Inhaltsschlüssels** mit
(`users.pat_key_check`, seit Web 4.0.0 vorhanden). Stimmt sie nicht mit der
gespeicherten überein, wird abgelehnt und **nichts geändert**. Der Server lernt
dadurch nichts über den Schlüssel — er vergleicht zwei Hashwerte, und der
Schlüssel selbst ist 256 Bit Zufall.

Konten aus der Zeit davor haben keine gespeicherte Prüfsumme. Sie werden weiter
angenommen und bekommen sie beim nächsten Setzen des Passworts — alles andere
sperrte sie aus, denn der Server kann sie nicht nachträglich berechnen. Die
Prüfung greift ebenso beim Zurücksetzen über den Wiederherstellungsschlüssel
und beim erstmaligen Einrichten.

### Behoben — Ein Kontowechsel im selben Tab konnte einen fremden Schlüssel durchreichen

Der Zwischenspeicher lieferte den Inhaltsschlüssel zurück, **ohne zu prüfen, ob
er zur übergebenen Hülle gehört**. Die Richtigkeit hing allein daran, dass jeder
Weg, auf dem das Konto wechseln kann, vorher aufräumt — vier Stellen taten das,
eine nicht. Ein Schlüssel aus Konto A entschlüsselt in Konto B nichts, und der
Fehlschlag sah aus wie „keine Angaben vorhanden".

Statt fünf Aufrufer zur Disziplin zu erziehen, korrigiert sich der
Zwischenspeicher jetzt selbst (`assets/keyguard.js`): Er merkt sich eine kurze
Kennung der Hülle, aus der der Schlüssel stammt, und verwirft ihn, wenn sie
nicht passt. Zusätzlich läuft er nach derselben Frist ab wie die Sitzung
(30 Minuten) — vorher hing er am Tab und überdauerte sie.

### Behoben — Unlesbare Einträge sahen aus wie leere

Fünf Stellen entschlüsselten je Datensatz und fingen den Fehlschlag ab, ohne
ihn nach außen sichtbar zu machen. Die Absicht ist richtig — ein unlesbarer
Datensatz darf die Liste nicht zerstören. Es fehlte die Unterscheidung:

| | vorher | jetzt |
|---|---|---|
| keine Angaben erfasst | `–` | `–` |
| vorhanden, nicht lesbar | `–` | **⚠** |

Dazu erscheint über der Liste ein Hinweis mit der Zahl der betroffenen Einträge.
Sind **alle** unlesbar, nennt er die wahrscheinliche Ursache (nicht passender
Schlüssel) und rät, den Wiederherstellungsschlüssel bereitzuhalten, bevor
weitere Schritte unternommen werden. In der Einzelansicht steht statt der
Angaben ein ausdrücklicher Absatz — dort sieht man nur einen Einsatz, und ein
stiller Fehlschlag wäre von „nichts erfasst" nicht zu unterscheiden.

Warum das zählt: Wer den Unterschied nicht sieht, merkt nicht, dass sein
Schlüssel nicht mehr passt.

### Behoben — Eine abgelaufene Sitzung ließ die Schlüssel im Browser liegen

Es gibt zwei Wege, auf denen eine Sitzung endet. Das Abmelden löste es richtig:
Eine reine Weiterleitung per Kopfzeile führt nie JavaScript aus, deshalb wurde
dort eine kurze Seite ausgeliefert, die die Schlüssel räumt. Der **Ablauf der
30-Minuten-Frist** tat genau das nicht — Daten- und Inhaltsschlüssel blieben im
Tab liegen, obwohl die Sitzung vorbei war. Wer seinen Rechner stehen lässt,
hatte eine abgelaufene Sitzung und einen liegengebliebenen Schlüssel.

Beide Wege laufen jetzt über dieselbe Funktion (`session_lib.php`), damit sie
nicht wieder auseinanderlaufen.

### Behoben — Nach Ablauf der Frist stand man ohne Erklärung auf der Anmeldeseite

Der Ablaufpfad hängte `?timeout=1` an die Adresse — einen Parameter, den die
Anmeldeseite gar nicht auswertete. Aus Sicht der NutzerIn verschwand die
Anwendung einfach. Jetzt steht dort, was passiert ist. Der alte Parametername
wird weiter erkannt, damit ein offener Tab mit alter Adresse nicht ins Leere
läuft.

### Geändert — Sicherungen tragen jetzt ihr Herkunftskonto

Seit Web 4.1.0 nimmt eine Sicherung den Chiffretext mit, wenn ein Einsatz sich
beim Erstellen nicht entschlüsseln ließ. Beim Einspielen war bisher nicht zu
entscheiden, ob diese Angaben im Zielkonto lesbar sein würden.

Der Dateikopf enthält deshalb jetzt `pat_key_check`, die Prüfsumme des
Inhaltsschlüssels der Herkunft. Stimmt sie mit dem Zielkonto überein, werden
die Angaben übernommen und sind wieder lesbar; die Meldung sagt es. Stimmt sie
nicht oder fehlt sie (ältere Dateien), fragt das Einspielen ausdrücklich nach
und nennt den Grund. Übernommen wird auch dann — die Angaben zu verwerfen wäre
schlechter —, aber nicht unbemerkt.

Damit ist das Kennzeichen `pat_unreadable` **benutzt** statt erzeugt, in die
Datei geschrieben und beim Einspielen weggeworfen. Der Zwischenzustand war der
schlechteste von dreien.

## [Web 4.1.1] — 2026-08-08

### Berichtigt — Der JSON-Vertrag beschrieb eine Phase, die es nicht gibt

Der Vertrag zwischen Uhr und Server (`docs/JSON-Vertrag.md`, jetzt Fassung 1.2)
führte an drei Stellen eine **Phase 10** auf: als Auslöser des Uploads, in der
Nummernreferenz und in der Regel, dass sie als letzter Eintrag mitgesendet
werde. Phase 10 wurde mit der Migration `2026_07_19_phase10_entfernen`
abgeschafft.

Das war nicht nur veraltet, sondern **irreführend**: Alle Schreibwege lehnten
Phasennummern außerhalb von 2 bis 9 bereits ab. Wer nach dem Vertrag
implementierte, sendete eine Phase 10, bekam keine Fehlermeldung und hatte
einen Eintrag weniger. Der Abschluss eines Einsatzes läuft über `final: true`
zusammen mit `ended_at` — beides zusammen, und keine Phase.

Ebenfalls entfernt: die Beschriftung „Beendigung Einsatz" für Phase 10 in
`db.php`. Sie ließ einen Altbestand als gültigen Zustand erscheinen; ohne sie
erscheint er als unbekannte Phase — und das ist er.

### Neu — Der Vertrag legt jetzt fest, was vorher offen war

- **Führende Listen.** Die Reanimationsarten liegen in Uhr-App und Server
  zusätzlich als Konstante vor. Welche Fassung gilt, stand nirgends. Jetzt
  gilt der Vertrag, und dort steht auch, dass eine neue Art an drei Stellen zu
  ergänzen ist.
- **Grenzen und Mengen** (Phasennummern, Koordinaten, Längen, Höchstzahlen je
  Einsatz) stehen erstmals an einer Stelle statt verteilt im Code.
- **Fehlende gegen leere Liste.** Ein fehlender Schlüssel heißt „dazu sage ich
  nichts", eine leere Liste „es gibt keine" — und löscht nichts. Der Grund ist
  der Weg dorthin: Eine leere Liste entsteht viel wahrscheinlicher durch einen
  Fehler beim Aufbau der Nachricht als durch die Absicht, eine dokumentierte
  Reanimation zu entfernen.
- **Format der Client-Kennung.** Sie wird von vier Stellen erzeugt (`m-`, `r-`,
  `man-`, `imp-`, `bak-`), und an ihrem Präfix hängt Verhalten: Beim endgültigen
  Löschen kommt die Kennung auf eine Sperrliste — für `man-` bewusst nicht,
  dort gibt es keine Uhr, die etwas nachliefern könnte. Das stand nur im Code.

**Neu ist außerdem ein Abschnitt „Stand der Durchsetzung".** Er sagt offen,
welche Regeln der Server heute schon durchsetzt und welche noch nicht. Ein
Vertrag, der etwas zusichert, was der Code nicht einhält, ist schlimmer als gar
keiner. Der Abschnitt verschwindet, sobald alle Zeilen „durchgesetzt" lauten.

### Neu — Wo die geschützten Angaben liegen, steht jetzt in der Technikdoku

`Technik.md` listet auf, **welche Felder im verschlüsselten Block liegen**
(Name, Geburtsdatum, Alter, Diagnose, Einsatznummer, Adresse, Koordinaten,
Ortsbeschreibung) und welche im Klartext in der Datenbank stehen. Der Server
kann den Block nicht lesen — genau deshalb muss die Liste woanders stehen, sonst
lässt sich weder eine Auskunft nach Datenschutzrecht beantworten noch
beurteilen, was ein Datenbankabzug preisgibt.

Festgehalten ist dort auch, dass die Klartextangaben für sich genommen nicht
personenbeziehbar sind, **in Verbindung mit Ort und Zeitpunkt eines Einsatzes
aber werden können**.

### Geändert — Die Zahlen in den Uhr-Dokumenten nennen ihr Gerät

`Uhr-Layout.md` beschreibt Regeln, die an Geräten beobachtet und nicht aus
einer Spezifikation abgeleitet wurden. Ohne die Angabe, an welchem Gerät, ist
eine solche Regel beim nächsten Zielgerät nicht bewertbar: Wer nicht weiß, ob
„85 %" auf einem runden 260er oder einem runden 390er Display gemessen wurde,
kann nicht entscheiden, ob die Zahl auf einem eckigen 416er noch gilt. Ein
neuer Abschnitt 0 nennt die drei Prüfgeräte samt Profil und macht die Angabe
zur Konvention.

### Kleinere Berichtigungen

- Handbuch: „Einsätze bei Phase 10" → beim Abschluss des Einsatzes.
- Handbuch: Die **Sperrliste gegen Nachlieferungen hält 90 Tage** — bisher nur
  die Papierkorbfrist genannt. Relevant für eine lange abgeschaltete Uhr.
- `schema.sql`: Der Kommentar zu den Kopplungscodes nannte weiterhin 60 Minuten
  und sicherte Einmaligkeit zu, die erst seit Web 4.1.0 durchgesetzt ist.
- `update.php`: Der Kommentar zur Migration `site_desc` verwies auf eine
  Rettungsseite, die es seit Web 3.4.0 nicht mehr gibt.

## [Web 4.1.0] — 2026-08-08

### Sofortmaßnahmen aus dem Code-Review

Sieben Änderungen, alle klein, die drei der vier gefundenen Befundketten an je
einer Stelle unterbrechen. Sie bauen auf den Bausteinen aus Web 4.0.0 auf.

### Geändert — Kopplung der Uhr: 6 Zeichen, 10 Minuten, wirklich einmalig

Der Kopplungscode ist jetzt **sechs Zeichen** lang (vorher fünf) und **10
Minuten** gültig (vorher 60). Je Konto gibt es höchstens **einen offenen
Code**; ein neu erzeugter macht den vorherigen ungültig. Wiederholte
Fehlversuche werden abgewiesen.

Der Grund in Zahlen: Fünf Zeichen aus einem Alphabet von 32 sind 25 Bit, also
33,5 Millionen Möglichkeiten. Die einzige Bremse war eine feste Verzögerung von
0,3 Sekunden je Anfrage — die verzögert die *einzelne* Anfrage, behindert
parallele aber überhaupt nicht. Mit 2000 gleichzeitigen Verbindungen war der
gesamte Coderaum in **rund 1,4 Stunden** durchlaufbar, und die Codes waren eine
Stunde gültig. Sechs Zeichen sind 30 Bit (1,07 Milliarden); zusammen mit dem
Ratenschutz und der kürzeren Gültigkeit liegt die Trefferchance je Code jetzt
unter einem Millionstel Prozent. **Der Ratenschutz trägt dabei die Hauptlast** —
die Codelänge allein täte es nicht.

Das Prüfmuster bildet außerdem das tatsächliche Alphabet ab. Vorher ließ es
vier bis acht Zeichen zu und ausdrücklich auch `0`, `O`, `1` und `I` — die im
Alphabet bewusst fehlen, weil sie auf einem Uhrendisplay nicht zu unterscheiden
sind. Ein Muster, das mehr erlaubt, als der Erzeuger je ausgibt, prüft nichts.

Die Uhr-App braucht dafür keine Änderung.

### Behoben — Ein Kopplungscode war nicht wirklich einmalig

Bisher suchte erst eine Abfrage den Code und entwertete ihn dann — das Ergebnis
der Entwertung wurde nicht ausgewertet. Zwei gleichzeitige Anfragen mit
demselben Code fanden ihn deshalb **beide** gültig und legten **beide** ein
Gerät an. Die Dokumentation sicherte die Einmaligkeit zu, der Code setzte sie
nicht durch. Jetzt entwertet die Anfrage zuerst und nimmt den Code erst über
das Ergebnis dieser Entwertung als gültig an: Die Datenbank entscheidet, und
genau eine Anfrage gewinnt.

### Behoben — Eine Sicherung konnte Daten vernichten statt sie zu sichern

Ließ sich ein Einsatz beim Erstellen einer Sicherung nicht entschlüsseln, wurde
sein Chiffretext **trotzdem entfernt** — das Entfernen stand hinter dem
Fehlerblock und lief deshalb auch im Fehlerfall. Gemeldet wurde „Fertig". In
der Datenbank lagen die Daten noch und wären mit dem richtigen Schlüssel lesbar
gewesen; in der Datei waren sie weg.

Das ist die gefährlichste der gefundenen Ketten, weil sie erwartbares Verhalten
bestraft: Wer merkt, dass mit seinen Daten etwas nicht stimmt, erstellt als
Erstes eine Sicherung. Jetzt bleibt der Chiffretext in der Datei (Format
`.edbak`, Feld `pat_blob` neben `pat_unreadable`), und die Meldung nennt die
Zahl der betroffenen Einsätze deutlich. Zurück in dasselbe Konto gespielt, sind
die Angaben wieder lesbar.

### Behoben — Ein Serverfehler erzeugte eine echte, aber leere Sicherungsdatei

Antwortete der Server beim Datenabruf mit einem Fehler — was er ausdrücklich
vorsieht —, liefen alle Schleifen über nichts, und es entstand eine echte
`.edbak`-Datei mit korrektem Kopf und richtigem Passwort, die ausschließlich
die Fehlermeldung enthielt. Sie ließ sich öffnen und wäre erst beim Einspielen
als leer aufgefallen, möglicherweise Monate später. Jetzt wird der
Antwortstatus geprüft und abgebrochen, **bevor** eine Datei entsteht. Eine
fehlende Einsatzliste gilt dabei als Fehler, nicht als leerer Bestand; bei
tatsächlich leerem Bestand erscheint ein Hinweis statt „Fertig".

### Behoben — Die Anmeldeseite verriet, welche Konten existieren

Zu einer unbekannten E-Mail-Adresse liefert der Server ein Pseudo-Salt, damit
die Antwort nicht von der eines echten Kontos zu unterscheiden ist. Sie war es
aber: Ein echtes Salt hat **32** Hexzeichen, das Pseudo-Salt hatte **64**. Die
bloße Länge der Antwort sagte damit, ob zu dieser Adresse ein eingerichtetes
Konto existiert — die gesamte Vorkehrung war wirkungslos. Behoben durch
Zuschnitt auf 32 Zeichen; Zeichenvorrat und Verteilung stimmten bereits überein.

### Geändert — Die Wartungsseite führt beim Aufrufen nichts mehr aus

`update.php` läuft zweistufig: Der **Aufruf zeigt an**, welche Migrationen
anstünden und ändert nichts; erst der Knopf **„Updates jetzt anwenden"** führt
sie aus, mit Formular-Token. Der Rat, vorher eine Sicherung zu erstellen, steht
jetzt **vor** dem Lauf statt danach.

Vorher war der Aufruf der Seite bereits die Ausführung — auch aus dem Verlauf
heraus oder durch einen Vorschau-Abruf des Browsers. Unter den Migrationen sind
solche, die Spalten samt Inhalt löschen. Eine unwiderrufliche Handlung auf
einen GET hin ist immer falsch. Der Notausgang über die Kommandozeile
(`php update.php`) bleibt einstufig und gibt sein Ergebnis jetzt als Text aus.

### Geändert — Klartext bei der Ersteinrichtung

Die bisherige Aussage („der Server kann die Angaben nicht lesen") war richtig
und unvollständig. Ergänzt ist jetzt, was daraus folgt: **Die Stärke des
Passworts ist unmittelbar die Stärke der Verschlüsselung.** Weil der Server das
Passwort nie sieht, kann er seine Güte auch nicht prüfen und ein schwaches
nicht ausgleichen — es gibt keine zweite Hürde dahinter.

## [Web 4.0.0] — 2026-08-08

### Neu — Gemeinsame Bausteine und Schemaänderungen für die Review-Umsetzung

Ein Code-Review in sieben Durchgängen hat 117 Befunde ergeben, keinen davon
kritisch. Diese Auslieferung ist der **erste von mehreren Schritten** ihrer
Behebung. Sie legt ausschließlich die Grundlagen: neun gemeinsame Bausteine
und sechs Schemaänderungen.

**Für den laufenden Betrieb ändert sich nichts.** Das ist beabsichtigt: Die
Bausteine existieren und sind einsatzbereit, werden aber noch nicht verwendet.
Einzige Ausnahme ist der Ratenschutz, der ab der nächsten Auslieferung
gebraucht wird und deshalb bereits vollständig funktioniert.

**Neue Bausteine (`server/`, `server/assets/`)**

| Datei | Aufgabe |
|---|---|
| `validate_lib.php` | Eine Prüfschicht für Einsatzdaten — Wertebereiche, Längen, Formate, Mengen. Die Regeln stammen aus `api/import_commit.php` und sind dorthin gehoben, nicht neu erfunden. Enthält auch die Kalendertagsprüfung. |
| `ratelimit_lib.php` | Ratenschutz je Kontokennung **und** IP-Adresse, in der Datenbank statt in der Sitzung. |
| `session_lib.php` | Ein Sitzungsende für beide Wege (Abmelden und Ablauf), das die Schlüssel im Browser räumt und den Grund nennt. |
| `assets/keyguard.js` | Bindet den zwischengespeicherten Inhaltsschlüssel an die Hülle, aus der er stammt, und lässt ihn mit der Sitzungsfrist ablaufen. |
| `assets/pwquality.js` | Passwortgüte: Mindestlänge im Skript statt nur als HTML-Attribut, Stärkeanzeige, Abgleich gegen häufige Passwörter. |

Dazu erweitert: `assets/crypto.js` (Prüfsumme des Inhaltsschlüssels,
Hüllenkennung), `assets/patient.js` (eine Entschlüsselungsschleife, die
zwischen „keine Angaben" und „nicht lesbar" unterscheidet),
`assets/missiontable.js` (eine Maskierungsfassung, die auch in
Attributpositionen sicher ist).

**Warum die Kalendertagsprüfung nötig war.** Die Datumsumwandlung liefert bei
einem unmöglichen Tag kein Fehlerergebnis, sondern rechnet weiter: Aus dem
30. Februar wird der 2. März. Sichtbar wird das ausschließlich über die
Warnungsabfrage der Datumsklasse — und die wurde nirgends abgefragt. Ein
Tippfehler in einer Importdatei wurde so zu einem stillen Datumssprung.

**Warum es zwei Grenzen für den verschlüsselten Patientenblock gibt.** Die
Untergrenze ist jetzt hergeleitet statt geschätzt: AES-256-GCM legt 12 Byte
Zufallswert davor und hängt 16 Byte Prüfwert an — auch bei leerem Klartext
sind das 28 Byte, in base64 also 40 Zeichen. Kürzer *kann* ein gültiger Block
nicht sein. Im Umlauf waren bisher drei verschiedene Untergrenzen (16, 20 und
gar keine), alle unterhalb des überhaupt Möglichen. Die Obergrenze bleibt bei
60000 Zeichen: Ohne sie entscheidet die Datenbank, und ihre Entscheidung ist
entweder ein Abbruch oder stilles Abschneiden — ein abgeschnittener
Chiffretext ist dauerhaft unlesbar.

### Datenbank — sechs Änderungen in einer Migration

Anzuwenden über **Verwaltung → Datenbank-Update**.

| | Änderung |
|---|---|
| `users.kdf_iter` | Rundenzahl der Schlüsselableitung, je Konto. Bestand auf den heutigen Wert (310000) gesetzt. |
| `users.pat_key_check` | Prüfsumme des Inhaltsschlüssels. Bleibt für Bestandskonten leer — der Server kann sie nicht berechnen, er kennt den Schlüssel nicht. |
| `users.session_epoch` | Zähler, mit dem ein Passwortwechsel offene Sitzungen beenden kann. |
| `rate_limits` | Neue Tabelle für den Ratenschutz. Wird vom Aufräumjob mitentsorgt. |
| `deleted_refs.owner_type` | Die Sperrliste gilt jetzt auch für Ruhe-Segmente. Schlüssel entsprechend erweitert. |
| `users.email` | Sortierregel ausdrücklich festgelegt (`utf8mb4_unicode_ci`). |

**Zur Rundenzahl, weil es die heikelste Änderung ist:** Sie wird hier **nur
angelegt und gefüllt**. Kein Code liest sie, der Salt-Endpunkt bleibt
unverändert. Der Grund ist Vorsicht — ein Fehler an der Schlüsselableitung
sperrt nicht ein Konto aus, sondern alle gleichzeitig. Die drei Folgeschritte
(Salt-Endpunkt liefert die Zahl mit, Browser rechnet damit, stille Anhebung
bei der nächsten Anmeldung) folgen einzeln und jeweils rückwärtsverträglich.

**Zur Sortierregel:** Dass die Anmeldung heute trotz uneinheitlicher
Normalisierung der E-Mail-Adresse funktioniert, lag allein an der
Standardsortierregel der Datenbank. Auf einer Installation mit
unterscheidender Sortierregel schlüge sie für jede Adresse fehl, die nicht
exakt wie beim Anlegen eingetippt wird — mit der Meldung „Anmeldung
fehlgeschlagen" und ohne Hinweis auf die Ursache. Das Projekt liegt offen;
diese Annahme sollte nicht ungeschrieben bleiben.

Nach der Migration melden sich bestehende Konten unverändert an, und
bestehende Sicherungsdateien lassen sich unverändert öffnen.

## [Web 3.6.0] — 2026-08-06

### Neu — Exportdateinamen sagen, was in der Datei steckt

Bisher hieß eine Exportdatei
`luftrettungsdokumentation_export_TT-MM-JJJJ_<profil>`. Ob darin Patientendaten
lagen und ob sie verschlüsselt war, ließ sich erst nach dem Öffnen sagen — in
einem Ordner mit mehreren Exporten die falsche Reihenfolge, weil genau diese
beiden Angaben darüber entscheiden, wie die Datei zu behandeln ist. Der Name
lautet jetzt:

```
luftrettungsdokumentation_export_TT-MM-JJJJ_<profil>_<inhalt>_<schutz>_<konto>.<endung>

luftrettungsdokumentation_export_06-08-2026_standard_ohne-pat_unverschl_philipp-mueller.xlsx
luftrettungsdokumentation_export_06-08-2026_csv_mit-pat_verschl_philipp-mueller.zip
```

`<inhalt>` ist `mit-pat` oder `ohne-pat`, `<schutz>` ist `verschl` oder
`unverschl`. **Beide Marker stehen immer da, auch im Negativfall** — fehlte der
Negativfall, wäre eine Datei ohne Patientendaten nicht von einer Datei aus
einem Stand vor dieser Regel zu unterscheiden.

`<schutz>` beschreibt die Datei, an der er steht, nicht den Vorgang: Bei den
Excel-Profilen mit Passwort liegt in einem Archiv `…_verschl.zip` eine Tabelle
`…_unverschl.xlsx`. Nach dem Entpacken ist sie offen, und das ist die Angabe,
auf die es beim Aufbewahren ankommt.

### Neu — Kontokennung im Exportdateinamen

`<konto>` benennt das Konto, aus dem der Export stammt: der Anzeigename aus den
Einstellungen, und wenn dort keiner hinterlegt ist, die E-Mail-Adresse. Beides
wird zu einem dateisystemsicheren Segment bereinigt — Kleinbuchstaben, Umlaute
nach deutscher Lesart ausgeschrieben (`Philipp Müller` → `philipp-mueller`),
übrige Akzente auf den Grundbuchstaben zurückgeführt, alles Weitere zu `-`
zusammengezogen, auf 40 Zeichen gekürzt. Bei der E-Mail-Adresse entfallen `@`
und Punkte (`max@gen-em.de` → `max-gen-em-de`); blieben die Punkte stehen, sähe
der Name nach mehrfacher Dateiendung aus. Bleibt von Name und Adresse nichts
übrig, steht `konto` da, damit das Segment nie leer ist.

Ein eigenes Nachnamenfeld gibt es nicht — `users` führt nur `email` und den
freien Anzeigenamen `name`. Deshalb wandert der vollständige Anzeigename in den
Namen und nicht sein letztes Wort: Eine Heuristik darauf bricht bei
Namenszusätzen („van der Berg\") und bei Konten, die gar keine Person benennen.

**Beim Weitergeben zu bedenken:** Der Dateiname nennt damit auch das Konto, im
Zweifel die E-Mail-Adresse. Einen Bezug auf eine bestimmte **Patientin oder
einen Patienten** enthält er weiterhin nicht — `mit-pat` sagt nur, *dass*
Patientendaten enthalten sind.

### Unverändert

- **Die Namen innerhalb der Archive.** `einsaetze.csv`, `felder.csv`,
  `LIESMICH.txt` und `tracks/` sind Teil des Formats; der Rückimport sucht im
  Archiv nach genau diesen Namen. Ein Marker daran hätte das Zurücklesen
  verschlüsselter CSV-Archive gebrochen.
- **Das Backup (`.edbak`).** Es ist immer verschlüsselt und enthält immer
  Patientendaten — Marker hätten dort keinen Informationswert.
- Alle Feldlisten und Spaltensätze der drei Profile.

### Dokumentation

- `Export-Format.md`: Namensschema als Tabelle mit allen Segmenten, Beispielen,
  der Bereinigungsregel und der Abgrenzung zwischen „Marker\" und
  „Patientenbezug\".
- `Handbuch.md` 7.1: neuer Absatz zum Dateinamen mit den beiden Feinheiten
  (Schutzmarker der inneren Datei, Umschreibung von Umlauten) und dem Hinweis
  zur Weitergabe.
- `Technik.md`: Stolperstein ergänzt, dass die Marker nur nach aussen gehören
  und die Archivnamen unberührt bleiben.

## [Web 3.5.0] — 2026-08-06

### Behoben — Alter fehlte im Excel-Export, wenn kein Geburtsdatum vorlag

Die Spalte „Alter" in Excel (Standard) las das Alter über `EdPat.alterAm()` und
damit ausschließlich aus dem Geburtsdatum. Bei unbekannten Personen — dem
Regelfall für ein von Hand eingetragenes Alter — stand dort ein Bindestrich,
obwohl die Einsatzansicht den Wert anzeigt. Sie nutzt `EdPat.alterAnzeige()`,
das nach dem Geburtsdatum auf den gespeicherten `age` zurückfällt; genau so
lesen es auch Tages- und Zeitraumübersicht sowie die Suche. `export.js` war die
einzige Stelle mit der falschen der beiden Funktionen und zieht nun nach.

### Neu — Spalte `pat_alter` im vollständigen CSV

`einsaetze.csv` führt das Alter jetzt als eigene Spalte, direkt hinter
`pat_geburtsdatum`. Sie trägt den **Rohwert** `pat_blob.age` und ist deshalb bei
einem Einsatz mit Geburtsdatum leer: Die Anwendung speichert das Alter nur,
wenn es sich nicht aus dem Geburtsdatum ergibt, und eine zweite, hineingerechnete
Quelle liefe auseinander, sobald jemand das Geburtsdatum korrigiert. Wer das
Alter auswerten will, rechnet es aus `pat_geburtsdatum` und `flugtag` und greift
auf `pat_alter` zurück, wenn das Geburtsdatum fehlt — dieselbe Reihenfolge wie in
der Anwendung. `Export-Format.md` hält das in einem eigenen Abschnitt (3.7) fest.

Der Rückimport übernimmt die Spalte (`pat_alter` → `pat_blob.age`). Beim Bauen
des verschlüsselten Blocks gilt dieselbe Regel wie im Formular: Ein Alter wird
nur gespeichert, wenn es nicht aus dem Geburtsdatum derselben Zeile folgt — das
fängt von Hand nachbearbeitete Dateien ab, in denen beides steht. Exportdateien
bis Web 3.4.0 ohne die Spalte lassen sich unverändert weiter einlesen; die
Formaterkennung zählt Treffer gegen 78 erwartete Spaltennamen bei einem
Schwellwert von 20.

Der CSV-Spaltensatz wächst damit von 76 auf 77 Spalten (`felder.csv` und
`einsaetze.csv` bleiben deckungsgleich). Excel (Standard) bleibt bei 29 Spalten
— das Alter stand dort schon, es war nur nicht immer gefüllt.

**Excel (GuteSeele) bleibt unverändert bei 13 Spalten.** Das Layout ist die
Absprache mit dem Empfänger und deckungsgleich mit dem Importprofil
`ch17_jahresliste`; eine zusätzliche Spalte würde beides verschieben. Das Feld
„Geb.dat" ist eine Datumsspalte und nimmt kein Alter auf.

**Grenze beim Rückweg über Excel (Standard):** Die dortige Spalte „Alter" führt
mal einen gerechneten, mal einen gespeicherten Wert und wird beim Import
weiterhin verworfen. Der Warnhinweis vor dem Import nennt sie jetzt ausdrücklich.
Für einen vollständigen Rückweg ist das CSV zuständig.

## [Web 3.4.0] — 2026-08-06

### Behoben — bearbeiteter Uhr-Einsatz stand im Export als „manuell"

Die Spalte `herkunft` im vollständigen CSV wurde bei jedem Export neu aus
`manual` und dem Präfix von `client_ref` berechnet — eine Regel aus der Zeit vor
der Spalte `missions.origin`. Wer einen von der Uhr aufgezeichneten Einsatz im
Formular korrigierte, bekam damit `manual = 1` und im Export „manuell", obwohl
`origin` korrekt auf `watch` stand. Von den vier möglichen Fällen war genau
dieser eine falsch.

Die Herkunft kommt jetzt aus `missions.origin`, der Ausgabewert entsteht über
eine feste Abbildung (`watch → uhr`, `manual → manuell`, `import → import`).
`client_ref` wird im Export nicht mehr gelesen. Die Einsatzansicht war schon
vorher richtig — der Export zieht damit nach, beide zeigen für denselben Einsatz
dasselbe.

Die gleichlautende Ableitungsregel in `backup_lib.php` bleibt bestehen: Backups
der Formatversion 3 und älter kennen `origin` und `edited` nicht, dort wird sie
weiterhin gebraucht.

### Neu — Spalte `edited` im vollständigen CSV

`einsaetze.csv` führt den Bearbeitungsstatus jetzt als eigene Spalte, direkt
hinter `manual`. Damit stehen drei Angaben nebeneinander, die drei verschiedene
Fragen beantworten: `herkunft` wie der Einsatz entstanden ist, `edited` ob er
danach verändert wurde, `manual` ob die Uhr ihn noch überschreiben darf.
`Export-Format.md` grenzt sie in einem eigenen Abschnitt (3.6) gegeneinander ab.

Beim Rückimport werden `herkunft` und `edited` **nicht** übernommen — beide
beschreiben, wie ein Datensatz in der Installation entstanden ist, aus der die
Datei stammt. Beim Einlesen entsteht er neu. Exportdateien bis Web 3.3.2 ohne
die Spalte lassen sich unverändert weiter einlesen; die Formaterkennung zählt
Treffer gegen 77 erwartete Spaltennamen bei einem Schwellwert von 20.

Die beiden Excelformate bleiben unverändert bei 29 Spalten und führen weder
Herkunft noch Bearbeitungsstatus. Die Übersichtstabelle ist zum Ansehen,
Sortieren und Filtern gedacht, und zusätzliche Spalten würden die
`expectedHeaders` des Importprofils `export_excel_v1` mitverändern.

**Grenze für Altbestand:** Für Einsätze von vor dem 30.07.2026 ließ sich
`edited` nur bei Uhr-Einsätzen zuverlässig herleiten. Von Hand angelegte und
importierte Einsätze starten mit `edited = 0`, auch wenn sie bearbeitet worden
sind — rückwirkend ist das nicht mehr feststellbar. In `Export-Format.md`
festgehalten, damit Auswertende die Spalte für diesen Bestand als „mindestens"
lesen und nicht als „genau".

### Geändert — Dokumentation der Exportformate

`Export-Format.md` benennt die drei Formate durchgängig so wie das Auswahlfeld:
CSV (Standard), Excel (Standard), Excel (GuteSeele). Die Bezeichnungen „Profil
A/B/C" stammten aus der ursprünglichen Spezifikation und tauchten in der
Anwendung nirgends auf; sie sind ersatzlos entfallen, im Export-Abschnitt von
`Technik.md` ebenso. Die Tastenprofile A/B/C der Uhr-App (Technik.md 5.1) sind
etwas anderes und bleiben.

Nebenbei behoben: Zwei Feldbeschreibungen enthielten ein unmaskiertes `|` und
sprengten damit die Tabellenspalte (`herkunft`, `weitere_rettungsmittel`), und
zwei Querverweise zeigten ins Leere (`rea_json` auf 4.4 statt 3.4, `crew_p1` auf
den Abschnitt zu `rea_json` statt auf die Regel zur effektiven Besatzung —
diese steht jetzt in 3.3).

## [Web 3.3.2] — 2026-08-05

### Behoben — Adresssuche überschrieb bestätigte Koordinaten

Nach dem Bestätigen von Koordinaten lief im Einsatzort-Feld beides weiter: die
Formaterkennung und die Adresssuche. Ein Klick auf einen Adressvorschlag setzte
`#loclat`/`#loclon` neu — die eben bestätigten Koordinaten waren damit
stillschweigend weg, obwohl der Chip nur die Bezeichnung erwarten ließ.

Solange Koordinaten gesetzt sind, ist das Textfeld jetzt reines
Bezeichnungsfeld: Der `input`-Zuhörer steigt früh aus, es gibt keine
Vorschlagsliste und keine Anfrage an Photon. Placeholder („Bezeichnung des
Einsatzortes") und Meldungszeile weisen darauf hin, damit das Feld nicht defekt
wirkt. Nach dem Entfernen der Koordinaten über das ✕ am Chip arbeitet die Suche
ab dem nächsten Tastenanschlag wieder unverändert.

## [Web 3.3.1] — 2026-08-05

### Entfernt — Klartextspalte `missions.site_desc` und die Seite „Beschreibungen sichern"

Der Altbestand von 13 Beschreibungen wurde über die Textdatei gesichert und von
Hand in den verschlüsselten Block nachgetragen. Damit hat die Spalte ihren
Zweck erfüllt: Die Migration `2026_08_05_site_desc_entfernt` entfernt sie
(`ALTER TABLE missions DROP COLUMN site_desc`), `schema.sql` führt sie nicht
mehr, und `site_desc_rettung.php`, der Leisteneintrag in `ui.php` sowie
`site_desc_rest_vorhanden()` sind weggefallen.

Der `pat_blob` ist davon **nicht** betroffen — sein Schlüssel `site_desc` trägt
die Beschreibung seit Web 3.3.0 und bleibt unverändert. Ebenso bleibt die
CSV-Kopfzeile `site_desc` beim Import erhalten, damit Exportdateien bis
Web 3.2.0 lesbar bleiben.

Ebenfalls entfallen: Die Zeile in `edbak_build()`, die die Spalte ausdrücklich
aus dem Backup entfernte. Sie war nur nötig, solange `SELECT *` sie noch
lieferte.

**Reihenfolge beim Einspielen:** erst die Dateien, dann `update.php` öffnen. Die
Migration läuft ohne Rückfrage, sobald die Seite aufgerufen wird.

## [Web 3.3.0] — 2026-08-05

### Neu — Bezeichnung zu Koordinaten

Wird der Einsatzort über Koordinaten oder einen Plus Code eingegeben, blieb
bisher kein lesbarer Ortsname übrig: Das Textfeld wurde beim Bestätigen mit der
normalisierten Zahlendarstellung überschrieben, und in allen Listen stand
danach ein Zahlenfragment.

Bestätigte Koordinaten erscheinen jetzt **unter** dem Textfeld als Chip mit
Kreuz zum Entfernen — dieselbe Darstellung wie bei den weiteren
Rettungsmitteln. Das Textfeld wird dabei geleert und gehört ab dann der
Bezeichnung. Damit die Zuordnung sichtbar bleibt, leert der `input`-Zuhörer die
versteckten Koordinatenfelder **nicht** mehr; eine getippte Bezeichnung
vernichtet die Koordinaten also nicht. Entfernt werden sie nur über das Kreuz
am Chip oder durch Auswahl eines anderen Adressvorschlags.

Sind Koordinaten gesetzt und das Textfeld leer, lässt sich der Einsatz nicht
speichern. Die Prüfung greift vor dem Verschlüsseln und nur bei entsperrter
Verschlüsselung — bei gesperrter bleibt der vorhandene Blob wie bisher
unangetastet. Ohne Koordinaten ist der Einsatzort weiterhin vollständig
freiwillig.

Bei einem gewählten Adressvorschlag ändert sich nichts am Ablauf: Das Label
steht im Textfeld und gilt als Bezeichnung; zusätzlich erscheint der Chip,
damit beide Wege gleich aussehen.

### Neu — Seite „Beschreibungen sichern"

`site_desc_rettung.php` gibt den verbliebenen Klartextbestand der Spalte
`missions.site_desc` als Textdatei aus: je Einsatz eine Zeile mit Datum, Beginn
in Ortszeit, interner Einsatznummer und Text. Damit lassen sich die alten Werte
von Hand nachtragen; ein automatischer Umzug ist nicht möglich, weil der
`pat_blob` ausschließlich im Browser entsteht.

Die Seite ist **vorübergehend**. Sie erscheint in der Einstellungsleiste nur,
solange überhaupt noch Werte vorhanden sind, und wird zusammen mit der Spalte
entfernt.

### Geändert — „Beschreibung Einsatzort" ist Ende-zu-Ende-verschlüsselt

Das Feld lag als Klartext in `missions.site_desc`. Es steht jetzt als eigener
Schlüssel `site_desc` auf oberster Ebene des `pat_blob` — nicht innerhalb von
`loc`, weil `loc` nur bei gefüllter Adresse entsteht und eine Beschreibung ohne
Ortsangabe sonst verloren ginge.

Im Formular steht das Feld nun im verschlüsselten Block direkt unter dem
Einsatzort; bei gesperrter Verschlüsselung ist es deaktiviert und wird beim
Speichern nicht verändert. In der Einsatzansicht erscheint es mit Schloss-Zeichen
unter dem Einsatzort statt in der generischen Zusatzfeldliste. **Die Suche
findet seinen Inhalt erst nach dem Entsperren** — dieselbe Bedingung, unter der
Diagnose und Einsatzort schon vorher standen.

Der Eintrag hat `mission_fields.php` verlassen; damit verschwindet das Feld
zugleich aus Formularausgabe, Formularauswertung, `api/mission.php` und der
Backup-Wiederherstellung, die alle generisch über `$FIELDS` laufen. Ebenfalls
entfernt: die Auswahl in `api/day.php`, `api/range.php`, `api/suchindex.php` und
`api/export_data.php` (in `day.php` und `range.php` wurde das Feld von keiner
Seite ausgewertet).

**Nichts wird gelöscht.** Die Spalte `missions.site_desc` bleibt bestehen, es
gibt keine Migration. Eine Löschmigration liefe beim Öffnen von `update.php`
sofort und würde den Klartext vor jeder Sicherung vernichten; das Entfernen der
Spalte ist eine eigene, spätere Auslieferung.

### Geändert — Export, Import, Backup

- CSV: `site_desc` entfällt aus dem ungeschützten Bereich; neu im geschützten
  Bereich hinter `pat_ort_lon` die Spalte **`pat_ort_beschreibung`**. Ohne den
  Haken „Patientendaten einschließen" ist sie vorhanden und leer.
- Rückimport: `pat_ort_beschreibung` wird dem verschlüsselten Block zugeordnet.
  Die alte Kopfzeile `site_desc` wird weiterhin angenommen und zeigt auf
  dasselbe Ziel, damit Exportdateien früherer Versionen lesbar bleiben.
- Backup-Format auf **Version 5**: Die Beschreibung steckt im Block `pat` und
  ist damit für den Server unsichtbar. `edbak_build()` liest die Einsätze mit
  `SELECT *` und entfernt die Klartextspalte deshalb ausdrücklich — sonst
  stünde sie weiterhin im Backup.
- Excel (Standard) und Excel (GuteSeele) führen die Beschreibung wie bisher
  nicht.

### Geändert — Beschriftungen im Formular

„Adresse Einsatzort" heißt jetzt **Einsatzort** mit dem Zusatz „Adresse,
Koordinaten oder Plus Code"; die Beschreibung trägt den Zusatz „Zufahrt,
Besonderheiten, Lage vor Ort". Ohne diese Zusätze waren die beiden
untereinanderstehenden Felder beim Ausfüllen nicht auseinanderzuhalten.

### Behoben — Import legte keine Einsätze mehr an

`api/import_commit.php` bereitete die INSERT-Anweisung für Einsätze mit **32
Spalten, aber nur 31 Werten** vor (`notes` hatte keinen Platzhalter). Da die
Datenbankverbindung ohne Prepare-Emulation und mit Ausnahmen arbeitet
(`db.php`), scheitert bereits das Vorbereiten der Anweisung — der gesamte
Import-Abschluss brach ab, für jedes Profil und unabhängig von der Datei. Der
Fehler bestand seit Web 2.10.0, als die zusätzlichen Felder angehängt wurden,
und fiel hier nur auf, weil dieselbe Anweisung für `site_desc` angefasst wurde.

### Behoben — Ortsspalte zeigte bei Koordinaten ein Fragment

`extractOrt()` (`assets/missiontable.js`) nahm den letzten Bestandteil nach dem
Komma und entfernte eine führende Postleitzahl. Aus `47.72800, 10.31600` wurde
damit `10.31600`. Die Zerlegung greift jetzt nur noch, wenn der letzte
Bestandteil überhaupt Buchstaben enthält — also nach einer Adresse mit Ortsteil
aussieht. Andernfalls wird der Text vollständig durchgereicht.

Wirkt gleichermaßen auf Einsatzliste, Zeitraum-Übersicht und Suche, weil alle
drei dieselbe Funktion verwenden. Altdatensätze mit Koordinatentext in `addr`
zeigen dadurch die vollständige Koordinate; ihre Bezeichnung tragen sie beim
nächsten Bearbeiten nach, eine Migration gibt es nicht.

## [Web 3.2.0] — 2026-08-05

### Behoben — „Export erstellen" reagierte überhaupt nicht mehr

Der Knopf hatte gar keinen Klick-Zuhörer. Der Fehler passierte schon beim Laden
der Seite, nicht beim Klick — deshalb blieb auch die Statuszeile darunter leer.

Mit Web 2.11.0 wurde die Formatauswahl in `import.php` von Optionsfeldern auf
ein Auswahlfeld (`<select id="exp_fmt">`) umgestellt. `assets/export.js` fragte
an drei Stellen weiterhin `input[name="exp_fmt"]:checked` ab. `querySelector`
liefert dafür `null`; `syncFormat()` warf beim `DOMContentLoaded` einen
`TypeError` und brach den Init-Block ab. Die Registrierung des Klick-Zuhörers
auf `#exp_go` ist dessen **letzte** Anweisung und kam damit nie zum Zug.
Mitbetroffen: die GPX-Zeile erschien beim Umschalten auf CSV nicht mehr, und der
Haken „Patientendaten einschließen" wurde bei gesperrter Verschlüsselung nicht
mehr gesperrt.

**Ursache im Repo:** Der Stand von `assets/export.js` aus der Auslieferung
Web 2.11.0 („Export-Fehlerbehebung", Commit `7237ee9`) wurde durch den
Folgecommit `1413ab5` mit einem älteren Arbeitsstand überschrieben —
`git diff c14caf7 1413ab5 -- server/assets/export.js` ergibt genau eine Zeile
Unterschied, die Datei entsprach also wieder Web 2.10.0. `import.php`,
`confirm.js` und `import_profiles.js` waren an diesem Commit nicht beteiligt und
behielten den korrigierten Stand — daher der Bruch. `docs/CHANGELOG.md`,
`docs/Export-Format.md` und `docs/Handbuch.md` wurden dabei ebenfalls
zurückgesetzt; der Changelog-Abschnitt zu Web 2.11.0 (Export) fehlte seitdem
vollständig. Die betroffenen Punkte sind unten unter „Geändert — Export"
aufgeführt, weil sie erst mit dieser Auslieferung tatsächlich im Repository
ankommen; der Eintrag zu Web 2.11.0 trägt einen entsprechenden Hinweis.

### Behoben — mit demselben Stand wiederhergestellt

- Die Rückfragen vor dem Export liegen wieder **innerhalb** der
  Fehlerbehandlung. Vorher stand ein Fehler im Dialog für einen völlig stummen
  Abbruch.
- `syncPasswordGate()` löschte mit `setState('')` bei jeder Umschaltung die
  Statuszeile — auch Erfolgs- und Fehlermeldungen des letzten Exports. Es räumt
  wieder nur die eigene Begründung weg.
- Fehlende Null-Absicherung beim Zusammenstellen der Tracks (`data.missions`
  bzw. `data.rests` ohne Inhalt) wieder eingesetzt.

### Behoben — Rückimport des eigenen Standard-Excel-Exports

Profil A schrieb die Spalte „Sekundäreinsatz" und drei Zusatzspalten, während
das Importprofil `export_excel_v1` bereits „Sekundärtransport" ohne diese
Spalten erwartete. Ein Standard-Excel-Export ließ sich dadurch nicht mehr sauber
zurücklesen: vier unbekannte Spalten, eine fehlende, und `secondary` ging still
verloren. Beide Listen stimmen wieder überein.

### Geändert — Export

- Dateiname einheitlich
  `luftrettungsdokumentation_export_TT-MM-JJJJ_<profil>.<endung>` mit
  `<profil>` = `standard`, `guteseele` oder `csv`. Das Datum ist der Tag der
  Erstellung; der ausgewählte Zeitraum steht in der Datei selbst.
- **Profil A** hat drei Spalten weniger: „davon an PatientIn", „Lastaufnahme"
  und „Bergwacht-Zusatz". In einer Übersichtstabelle sind sie entbehrlich; im
  vollständigen CSV bleiben sie erhalten. Damit hat Profil A 29 statt 32
  Spalten (davon 7 geschützte).
- Begriffe an `mission_fields.php` angeglichen: „Sekundäreinsatz" heißt wieder
  „Sekundärtransport". Das Feld `winch_airload` hieß im Export irrtümlich
  „Lastaufnahme" — es heißt im Formular **Luftverladung**; die Beschreibung in
  `felder.csv` ist entsprechend korrigiert, ebenso „Cycles mit Patient" und
  „Bergwacht: Namen / Infos".
- Die Abschlussmeldung nennt beim CSV wieder die Zahl der enthaltenen
  GPX-Tracks — einschließlich des Falls „im gewählten Zeitraum sind zu keinem
  Einsatz Trackpunkte gespeichert". Ob ein Archiv Tracks enthält, war sonst erst
  nach dem Entpacken zu sehen.
- Klarere Aussage zum Passwort im Rückfragedialog: Es lässt sich nicht
  zurücksetzen, und ohne es ist die Datei nicht mehr zu öffnen.

### Dokumentation

- `Export-Format.md`: Profiltabelle und Dateinamensschema aktualisiert; die
  Spaltentabelle zu Profil A stand seit Web 2.11.0 auf 32 Spalten, obwohl der
  Fließtext daneben bereits 29 beschrieb — jetzt durchgängig 29.
- `Handbuch.md`: Formatnamen und ihre Reihenfolge an das Auswahlfeld
  angeglichen (CSV (Standard), Excel (Standard), Excel (GuteSeele)); Passwort-
  und Dateinamenshinweis ergänzt.
- `Technik.md`: zwei Stolpersteine ergänzt — die Bindung von `#exp_fmt` an
  `gewaehltesFormat()` und die Kopplung von `SPALTEN_A` an die
  `expectedHeaders` von `export_excel_v1`.

## [Uhr 1.7.0] — 2026-08-14

Der Uhr-Teil von P9c und damit **der letzte Punkt des Reviews**. Zwei Befunde,
beide ohne Serveranteil: Die App lässt sich unabhängig von der Weboberfläche
einspielen.

### Die Client-Kennung enthält keinen Zeitstempel mehr (M7-03)

Sie bestand aus Präfix plus Sekunden seit 1970 (`m-1785000000`). Das hatte zwei
Folgen:

1. **Springt die Uhrzeit zurück** — nach einem Zurücksetzen des Geräts, nach
   einem Zeitzonenwechsel im Flugmodus —, entstehen erneut Kennungen, die es
   schon gab. Der nächste Upload trifft dann einen **fremden alten Einsatz
   desselben Geräts** und überschreibt ihn. Für einen Angriff taugt das nicht,
   weil der eindeutige Schlüssel die Gerätekennung enthält; als Datenverlust
   im eigenen Bestand taugt es sehr wohl.
2. Sie verriet den Startzeitpunkt **auf die Sekunde**, auch wenn er später im
   Web korrigiert wurde.

Neu: Präfix, ein fortlaufender Zähler im Gerätespeicher und zwei Zufallswerte,
zum Beispiel `m-42-1837704912`. Der Zähler überlebt Neustarts und Zeitsprünge
und ist die eigentliche Zusicherung — er wiederholt sich nicht, ganz gleich,
was die Uhrzeit tut. Der Zufallsanteil verhindert, dass sich Reihenfolge oder
Zeitpunkt ablesen lassen.

**Der Zeitstempel ist ganz entfallen, nicht bloß ergänzt worden.** Das Konzept
sah vor, einen Zufallsanteil anzuhängen; damit wäre Punkt 2 bestehen geblieben.
Der Startzeitpunkt steht ohnehin als `started_at` im Datensatz — dort gehört er
hin, und dort ist er korrigierbar.

**Kennungen der alten Form bleiben gültig.** Es gibt keine Umstellung: Der
Server prüft das Format nicht, die Idempotenz hängt allein an der Gleichheit der
Zeichenkette. Eine Uhr, die beim Update noch ungesendete Daten im Puffer hat,
liefert sie unverändert nach.

### Kopplung: Meldungen, die sagen, was zu tun ist

`Pair.mc` unterschied nur 200 und 404. Alles andere endete in „Kopplung
fehlgeschlagen (409)" — einer Meldung, die den Zahlencode nennt und sonst
nichts. Ausgerechnet die 409 ist aber der Fall, den man selbst beheben kann: Es
sind bereits fünf Geräte verbunden, eines muss weg. Wer stattdessen eine Zahl
liest, tippt den Code mehrmals neu ein und läuft am Ende noch in die Sperre —
die dann ebenfalls nur als Zahl erschien.

Unterschieden werden jetzt: zu viele Geräte, zu viele Versuche, ungültiger
Code und fehlende Telefonverbindung. Entschieden wird am Feld `error` der
Antwort, nicht am Zahlencode: Der Schlüssel benennt die Ursache, der Code nur
ihre Klasse.

**Zweizeilig, weil eine Zeile nicht reicht.** Die Meldungszeile wird ohne
Umbruch gezeichnet — was breiter ist als das Display, fällt weg, ohne dass man
es merkt. In der Hinweisschrift sind das rund 26 Zeichen; „Zu viele Geräte —
erst eines im Web löschen" wäre genau um den Teil gekürzt worden, der sagt, was
zu tun ist. Deshalb zwei kurze Zeilen: **was** los ist, und **was hilft**. Die
zweite Zeile erscheint nur, wenn es etwas zu tun gibt, und geht in die
Platzberechnung des Bildschirms ein — der Block darüber weicht von selbst aus.

Für unbekannte Fehler wird die Meldung des Servers als zweite Zeile
übernommen, gekürzt auf die Displaybreite. So erscheint ein künftiger Fehlerfall
nicht wieder als nackte Zahl, nur weil die Uhr ihn noch nicht kennt. Für die
bekannten Fälle stehen die Texte in der App: Die Servermeldungen sind für die
Weboberfläche geschrieben, ganze Sätze ohne Umlaute und zu lang für ein
Uhrendisplay.

### Behobene Review-Befunde

M7-03 sowie die in P4 nachgetragene 409-Behandlung. **Damit ist der Review
vollständig abgearbeitet.**

### Geprüft

Nur soweit ohne Gerät möglich: Die Erzeugung der Kennung wurde nachgebaut und
mit 6 Prüfungen belegt — Form, 5001 Kennungen ohne Dublette, Neustart nach
einem Zeitsprung auf dieselbe Sekunde (die alte Fassung erzeugt dort eine
Dublette, die neue nicht), Zählerüberlauf.

**Nicht geprüft und nur auf dem Gerät prüfbar:** Übersetzung mit dem
Connect-IQ-SDK, `Math.rand`/`Math.srand` und `Storage` im echten Lauf, die
Darstellung der zweiteiligen Meldung auf den verschiedenen Displaygrößen.

## [Uhr 1.6.6] — 2026-08-03

### Behoben — Einrichtungshinweis lief weiter über den Rand

Der Hinweis war in der unteren Zone des Startbildschirms zentriert. Dort läuft
der Kreis so weit zu, dass **keine** Schriftgröße mehr gepasst hätte — die
automatische Schriftwahl hatte keine Wahl, die hineingegangen wäre.

- Der Hinweis hängt jetzt **unter dem Hauptblock** statt in der unteren Zone.
  Dort ist die nutzbare Breite noch deutlich größer.
- Er ist **einzeilig und kurz**: „Server fehlt" bzw. „Nicht gekoppelt". Eine
  zweite Zeile säße zwangsläufig tiefer und passte auf keinem der drei Geräte
  zuverlässig hinein, auch nicht in der kleinsten Schrift. Was zu tun ist,
  steht ausführlich auf der Sync-Seite — einen Schritt nach unten.

## [Uhr 1.6.5] — 2026-08-03

### Behoben — Schriftwahl maß an der falschen Stelle

Die mit 1.6.4 eingeführte Anpassung an die Displayrundung prüfte die Breite in
der **Mitte** der Textzeile. Der Kreis läuft aber schon innerhalb einer
einzigen Zeile spürbar zu: Eine Zeile unterhalb der Displaymitte ist an ihrer
Unterkante deutlich schmaler als in ihrer Mitte. Lange Bezeichnungen wie
„Ankunft PatientIn" wurden dadurch weiterhin angeschnitten, obwohl die
Prüfung sie durchgehen ließ.

`Ui.fitFont()` bekommt jetzt Oberkante und Höhe der Zeile und misst an der
Kante, die weiter von der Displaymitte entfernt liegt — unterhalb der Mitte
also unten, oberhalb der Mitte oben. Der Sicherheitsrand wurde von 10 auf 16
Bezugspixel erhöht. Betrifft Hauptanzeige, Startbildschirm, Sync-Seite und die
Rea-Übersicht.

### Geändert — Rea-Übersicht: zweiter Trennbalken

Die Liste läuft um; hinter dem letzten Zeitstempel folgt wieder „Rea beenden".
Dort stießen Zeiten und Entscheidungen unvermittelt aneinander. Am Listenende
steht jetzt ein zweiter grauer Balken **„Aktionen"**, sodass beide Übergänge
markiert sind.

## [Uhr 1.6.4] — 2026-08-03

### Geändert — Rea-Übersicht selbst gezeichnet

Die Übersicht war das letzte Systemmenü der App und passte weder zum übrigen
Bild noch zur Venu 3s. Sie wird jetzt wie Schnellmenü und Rea-Untermenü selbst
gezeichnet: gleiche Zeilenhöhe, fünf sichtbare Zeilen, gefüllte Auswahl.

- Ist die Reanimation pausiert, stehen **Rea beenden** (rot) und **Rea
  fortsetzen** (grün) oben; ein **schmaler Trennbalken „Zeiten"** in halber
  Zeilenhöhe schneidet sie von den Zeitstempeln ab.
- Der Trennbalken ist nicht anwählbar — das Blättern überspringt ihn.
- Läuft die Reanimation normal, entfallen Entscheidungen und Trennbalken; es
  bleibt die reine Zeitenliste.

### Behoben — Texte liefen über den Displayrand

Auf einem runden Display läuft der Kreis oben und unten zu. Eine Zeile, die
in der Mitte passt, wird dort abgeschnitten — „Ankunft Einsatzort" auf der
Hauptanzeige und die Einrichtungshinweise des Startbildschirms waren betroffen,
auf Fenix 6 Pro und Venu 3s.

- `Ui.chordW()` berechnet die tatsächlich nutzbare Breite in der jeweiligen
  Höhe, `Ui.fitFont()` wählt die größte Schrift, die dort noch hineingeht.
  Angewandt auf die Phasenbezeichnung, die Hinweiszeilen beider Seiten und die
  Einträge der Rea-Übersicht.
- Die Hinweise des Startbildschirms sind kürzer gefasst: „Server-Adresse
  fehlt / in Garmin Connect" statt „Server in Garmin Connect / eintragen".

### Behoben — Hinweisschrift auf der Venu 3s zu klein

Schriftgrößen sind Gerätekonstanten und skalieren **nicht** mit der
Displayhöhe. Auf der Venu 3s war `FONT_XTINY` im Verhältnis zum Display
deutlich kleiner als auf der Fenix. `Ui.fontHint()` wählt ab 320 Pixeln
Displayhöhe eine Stufe größer; betroffen sind Startbildschirm und Sync-Seite.

## [Uhr 1.6.3] — 2026-08-03

### Geändert — Sync-Seite folgt der Farblogik

- **Warnungen in Rot** statt Gelb: „Erst Server-Adresse setzen", „Gerät
  koppeln" und der letzte Übertragungsfehler. Letzterer war bisher hellgrau
  und damit kaum als Fehler zu erkennen.
- **„REA pausiert" in Blau**, wie auf allen anderen Oberflächen.

Damit gilt durchgängig: **Rot** heißt laufende Reanimation oder Warnung,
**Blau** heißt pausiert, **Grün** heißt erledigt.

### Behoben — Kopplungsmeldungen waren teils falsch eingefärbt

Die Farbe der Kopplungsmeldung wurde aus den ersten drei Zeichen des Textes
abgeleitet. Damit galt alles außer „Gekoppelt" als Fehler — auch der
Zwischenstand „Kopple…", der noch gar nichts aussagt. Zudem hätte die Prüfung
„Kopple…" und „Kopplung fehlgeschlagen" nicht auseinanderhalten können, wenn
sie auf mehr Zeichen erweitert worden wäre.

`Pair.mc` führt jetzt neben dem Text eine Statusart (`:ok`, `:busy`,
`:error`). Die Oberfläche wählt die Farbe danach und muss den Text nicht mehr
auseinandernehmen: Grün bei Erfolg, Hellgrau während des Kopplungsversuchs,
Rot bei Fehlschlag.

## [Uhr 1.6.2] — 2026-08-03

### Geändert — Farbgebung nach Markenvorgabe

Der pausierte Zustand hat jetzt durchgängig eine eigene Farbe, und Warnungen
sind als solche erkennbar.

- **Pausierte Reanimation ist blau:** der Schriftzug „PAUSE" auf der
  Reanimationsseite, der Ring der Hauptanzeige und der Hinweis „REA pausiert"
  auf Tempo- und Statistikseite. Vorher gelb bzw. rot — Rot ist der laufenden
  Reanimation vorbehalten.
- **Fortschrittsbalken** unter dem 2:00-Countdown in Markenblau statt Orange.
- **Einrichtungshinweise auf dem Startbildschirm** („Server in Garmin Connect
  eintragen", „Nicht gekoppelt") in Rot statt Gelb. Es sind Warnungen: Ohne
  Server-Adresse kann die Uhr nichts senden.

## [Uhr 1.6.1] — 2026-08-03

Korrekturen aus dem ersten Simulatordurchlauf von 1.6.0. **1.6.0 wurde nie
verteilt** — die Trennung dient allein dazu, Bauten auseinanderhalten zu
können.

### Behoben

- **„PAUSE" erschien als fünf leere Kästchen.** Der Text wurde in
  `FONT_NUMBER_MILD` gezeichnet. Die Ziffernschriften von Connect IQ enthalten
  ausschließlich Zahlen, Doppelpunkt und Punkt — Buchstaben haben dort kein
  Zeichen. Jetzt `FONT_LARGE` und in Rot, damit der angehaltene Zustand auf
  der Reanimationsseite nicht zu übersehen ist.
- **Rahmen des Fortschrittsbalkens** auf der Reanimationsseite lag als
  einziger Wert noch absolut bei 2 Pixeln und wäre auf der Venu 3s zu dünn
  geraten. Er skaliert jetzt wie alles andere mit der Displayhöhe.
- **Sync-Seite:** Der Mittelblock wurde im ganzen Bildschirm zentriert, der
  untere Block vom Rand aus gesetzt — bei drei Meldungen überlappten sie sich.
  Jetzt wird zuerst der untere Block bestimmt und der Mittelblock im freien
  Raum darüber zentriert.

### Geändert — Feinschliff der Geometrie

Die Blockhöhen wurden mit der vollen Schriftbox gerechnet. Bei den
Ziffernschriften bleibt deren Unterlänge leer, wodurch unter jeder Zahl eine
Lücke entstand und die Blöcke zu hoch wirkten. `Ui.numH()` rechnet jetzt mit
der sichtbaren Höhe.

- **Hauptanzeige:** Uhrzeit und Datum enger, Block etwas tiefer.
- **Geschwindigkeit:** „km/h" rückt an die Zahl heran.
- **Statistik:** Die Zahl sitzt optisch mittig zwischen „Heute" und
  „Einsätze".
- **Reanimation:** Countdown und Fortschrittsbalken sitzen mittig im
  50-%-Feld, die Gesamtdauer etwas tiefer im Kopfbalken, die Uhrzeit etwas
  höher im Fußbereich. Die Trennlinie über der Uhrzeit entfällt — der
  Fortschrittsbalken trennt bereits genug.
- **Rea-Übersicht:** „Rea beenden" steht über „Rea fortsetzen", darunter
  trennt eine Zeile „— Zeiten —" die Entscheidungen von den Zeitstempeln.

### Hinweis für spätere Zielgeräte

`monkey.jungle` weist Quell- und Ressourcenpfade jetzt **vollständig** zu.
Die Schreibweise `$(<gerät>.sourcePath);…` sieht nach „Vorgabe erweitern" aus,
ist aber ein Selbstbezug und sammelt alle `source*`-Ordner ein — der Compiler
meldet dann `Redefinition of 'HAS_UP_DOWN'`. Festgehalten in `Technik.md`
Abschnitt 5.1b.

## [Uhr 1.6.0] — 2026-08-03

### Neu — Forerunner 945 und Venu 3s werden unterstützt

Die App lief bisher nur auf der Fenix 6 Pro. Dazu kommen zwei Geräte mit
anderen Voraussetzungen: die FR945 mit kleinerem Display (240×240) und die
Venu 3s mit größerem Display (390×390), Touchscreen und nur **zwei** für Apps
erreichbaren Tasten — die mittlere ist systemseitig belegt und erreicht
Connect-IQ-Apps nicht.

- **Gemeinsames Eingabemodell (`Input.mc`).** Die Langdruck-Erkennung, die
  bisher in drei Oberflächen einzeln stand, liegt jetzt an einer Stelle,
  ebenso die Tastensperre und die geräteabhängige Menü-Taste. Die Oberflächen
  beschreiben nur noch, was bei welcher *Aktion* passieren soll.
- **Bedienung der Venu 3s:** Wischen hoch und runter blättert, Wischen rechts
  wirkt wie Zurück. Der lange Druck liegt bewusst doppelt — auf der
  Action-Taste *und* auf der Zurück-Taste. Grund: Das Handbuch der Venu 3
  nennt ein Steuerungsmenü nach zwei Sekunden Halten der Action-Taste. Im
  Simulator trat es nicht auf, auf echter Hardware ist es ungeprüft. Fängt die
  Uhr den langen Action-Druck ab, bleibt die App über den langen
  Zurück-Druck vollständig bedienbar.
- **Tippen auf den Bildschirm bleibt auf den Hauptseiten wirkungslos.** In den
  Menüs kann es den markierten Eintrag auswählen — bewusst hingenommen, weil
  Tasten- und Bildschirmauswahl technisch nicht unterscheidbar sind.
- **Adrenalin und Rhythmuskontrolle** sind auf der Venu 3s nur über das
  Rea-Untermenü erreichbar; die langen UP/DOWN-Drücke gibt es dort nicht.
- **Neue App-Einstellung „Touchbedienung verwenden"** (Vorgabe: an). Sie
  greift erst bei Uhren, die Touch **und** UP/DOWN haben (Fenix 7 und neuer),
  und wird auf der Venu 3s ignoriert — ohne Touch wäre sie unbedienbar.
- **Bedienhinweise passen sich dem Gerät an:** „START halten" auf der Fenix,
  „Action halten" auf der Venu; „DOWN drücken" wird zu „nach unten wischen".
- Neues Dokument `docs/Geraete-Eingabe.md` mit dem gemessenen Eingabeverhalten
  je Uhr, neues Werkzeug `tools/eingabe-probe` zum Ausmessen weiterer Geräte.

### Geändert — Alle Oberflächen neu ausgemessen

Die Maße waren fest auf 260×260 verdrahtet. Sie werden jetzt relativ zur
Displayhöhe gerechnet und ergeben bei 260 **exakt** die bisherigen Pixelwerte;
auf der Fenix 6 Pro ändert sich dadurch nichts.

- **Startbildschirm:** Bildmarke (70×70, auf der Venu 105×105) über dem Titel.
  „Einsatzdoku" im Markenorange, „START drücken" kleiner und im Markenblau,
  enger an „Dienst beginnen?" gerückt. Der Block sitzt vertikal zentriert in
  den oberen 75 % der Höhe; die Einrichtungshinweise haben die unteren 25 %
  für sich und lassen ihn nicht mehr springen.
- **Hauptanzeige:** alles vertikal zentriert, größerer Abstand zwischen Datum
  und Phasennummer.
- **Geschwindigkeit:** „km/h" rückt an die Zahl heran, der Absatz zur Distanz
  bleibt; alles vertikal zentriert.
- **Statistik:** vertikal zentriert.
- **Sync:** Die Überschrift „Sync" entfällt. Die GPS-Güte steht jetzt über der
  Hauptaussage, diese sitzt vertikal in der Mitte. Fehlergrund,
  Kopplungsmeldung, Einrichtungshinweis und Version bilden unten einen Block
  mit gleichbleibendem Abstand zum Rand.
- **Reanimationsseite:** oberes und unteres Feld je 25 % der Displayhöhe, das
  mittlere 50 %; jedes Feld trägt seinen Inhalt vertikal zentriert.

### Geändert — Reanimation beenden ist jetzt zweistufig

Bisher fragte „Rea BEENDEN" einmal nach und schloss die Sitzung. Wer sich
vertippte, hatte die Dokumentation zu.

- **„Rea BEENDEN" hält die Reanimation an** und öffnet die Übersicht. Dort
  stehen ganz oben **Rea fortsetzen** und **Rea beenden** — die Entscheidung
  fällt also mit den dokumentierten Zeiten vor Augen. Der
  Bestätigungsdialog entfällt dafür.
- Ohne Entscheidung bleibt die Reanimation **pausiert**; die Zurück-Taste
  schließt nur die Liste. Der Pausenzustand übersteht einen Neustart der App.
- Während der Pause steht der 2:00-Countdown. **Die Gesamtdauer läuft
  weiter** — sie ist die tatsächlich verstrichene Reanimationszeit und darf
  nicht zu kurz dokumentiert werden.
- Uhr-, Tempo-, Statistik- und Sync-Seite zeigen „REA pausiert" statt „REA
  läuft"; der rote Ring der Hauptanzeige wird gelb.
- Wird der Einsatz abgeschlossen oder der Dienst beendet, während eine Rea
  pausiert ist, wird sie automatisch geschlossen — wie bisher eine laufende.
- **Im Rea-Untermenü** steht „Übersicht" jetzt hinter „Rea BEENDEN" und damit
  einen Schritt nach oben vom Öffnungspunkt. „Übersicht" ist im Markenblau
  gehalten, damit sie sich an der Umlaufgrenze von „Timer neu starten"
  unterscheidet.

Am Datenmodell, am JSON-Vertrag und am Server ändert sich nichts. Die Pause
ist ein reiner Bedienzustand und wird nicht übertragen.

## [Uhr 1.5.0] — 2026-08-02

### Geändert — Reanimations-Bedienung

Diese Umbauten standen bereits im Eintrag „Uhr 1.4.0", wurden dort aber **nicht
ausgeliefert**: Der Code blieb auf dem alten Stand, nur der Changelog-Text lief
voraus. Der Eintrag 1.4.0 ist entsprechend richtiggestellt; ausgeliefert wird
das Beschriebene jetzt mit 1.5.0.

- **Kurz START öffnet bei laufender Reanimation das Untermenü.** Bisher setzte
  der kurze Druck den 2:00-Countdown neu an. Der häufigste Griff unter
  Reanimationsbedingungen ist aber das Dokumentieren eines Ereignisses, nicht
  der Timer — der kürzeste Weg gehört deshalb dorthin. Läuft **keine**
  Reanimation, beginnt kurz START sie weiterhin.
- **Lang START startet den Countdown neu** (bisher öffnete der lange Druck das
  Untermenü). Läuft keine Reanimation, ist der lange Druck **ohne Funktion** —
  er startet insbesondere keine Reanimation, damit ein zu langes Drücken beim
  Beginnen nicht unbemerkt ins Leere läuft und auch nichts Falsches auslöst.
- **Neuer erster Menüpunkt „Timer neu starten"** (weiß). Er setzt den
  Countdown neu an, ohne einen Zeitstempel zu schreiben. Weiß, weil er kein
  dokumentiertes Ereignis ist — die Farben bleiben den Ereignissen vorbehalten.
- **Defibrillation setzt den Countdown neu an.** Wie die Rhythmuskontrolle
  markiert sie damit den Beginn eines neuen Zyklus. Bisher schrieb sie nur
  einen Zeitstempel: Die dafür vorgesehene Funktion `Cpr.markDefi()` existierte
  zwar, wurde aber von keiner Stelle aufgerufen — die im Eintrag 1.4.0
  angekündigte Kopplung war also nie wirksam.
- **Rea-Untermenü im Design des Schnellmenüs:** gleiche Zeilenhöhe und
  Darstellung wie auf der Hauptanzeige (fünf sichtbare Zeilen statt vier,
  gefüllte Auswahl). Die Ereignisfarben bleiben erhalten, die
  Gruppen-Trennlinien entfallen. Ein einheitliches Menübild spart im Einsatz
  Umdenken. Sehr lange Beschriftungen fallen in der Auswahlzeile eine
  Schriftstufe zurück, statt am Feldrand abgeschnitten zu werden.

Das Untermenü hat damit zwölf statt elf Einträge. An Datenmodell, JSON-Vertrag
und Server ändert sich nichts; die Defibrillation wird unverändert als
Ereignis `defibrillation` übertragen.

## [Web 3.1.1] — 2026-07-29

### Geändert
- **Suchseite: Filter in der linken Spalte.** Die rund 30 Filter standen bisher
  in einem einzigen Aufklappbereich über der Trefferliste. Sie sitzen jetzt in
  der linken Spalte, aufgeteilt in vier einzeln aufklappbare Blöcke (Zeit, Art
  des Einsatzes, Beteiligte und Ziel, Werte), die beim Öffnen der Seite alle
  zugeklappt sind. Öffnet man einen geteilten Link, gehen genau die Blöcke auf,
  in denen ein Filter gesetzt ist. Das Freitextfeld bleibt oben in der
  Hauptspalte, „Filter zurücksetzen" wandert an den Fuß der Filterspalte.
- Die Einsatztage-Leiste entfällt auf der Suchseite. Einzelne Flugtage sind bei
  einer Suche über den Gesamtbestand ohne Nutzen; die Fläche wird für die
  Filter gebraucht. Alle übrigen Seiten behalten sie unverändert.

## [Web 3.1.0] — 2026-07-29

### Neu
- **Suche über den gesamten Einsatzbestand** (neuer Menüpunkt „Suche"). Ein
  Freitextfeld durchsucht Einsatznummer, Name, Geburtsdatum, Diagnose,
  Einsatzort, Transportziel, Beschreibung, Bergwacht-Angaben, anderen Notarzt,
  weitere Rettungsmittel, Standort, Maschine, Besatzung und Notizen; mehrere
  Wörter werden UND-verknüpft, dürfen aber in verschiedenen Feldern stehen.
  Dazu rund 30 weitere Filter (Zeitraum, Alarmzeit auch über Mitternacht,
  Wochentag, Winde samt Cycles und Luftverladung, Bergwacht, Sekundärtransport,
  Schockraum, Reanimation und Ereignisarten, Herkunft, Standort, Maschine,
  Besatzung je Rolle, Rettungsmittel, Transportziel, Alter, Flugstrecke,
  Einsatzdauer, Höhe des Einsatzorts).
- Der Filterzustand steht vollständig im URL-Fragment. Die Adresse lässt sich
  als Lesezeichen speichern oder weitergeben und stellt dieselbe Suche wieder
  her. Fragmente werden nicht an den Server gesendet — der Suchbegriff taucht
  damit in keinem Zugriffsprotokoll auf.
- Die Suche läuft vollständig im Browser: Der Bestand wird einmal je Sitzung
  geladen (neuer Endpunkt `api/suchindex.php`), danach kostet kein Tastendruck
  eine Serveranfrage. Anders ginge es nicht — die geschützten Angaben liegen
  Ende-zu-Ende-verschlüsselt, der Server kann nicht in ihnen suchen.

### Geändert
- Trefferliste und Zeitraum-Übersicht teilen sich jetzt einen gemeinsamen
  Baustein (`assets/missiontable.js`): gleiche Spalten, gleiche Sortierung,
  gleicher Zeilenaufbau. Die Zeitraum-Übersicht verhält sich unverändert,
  inklusive der Kopplung zwischen Extremwert-Kacheln, Karten-Pin und
  Tabellenzeile.
- Die Kopfleiste enthält zwischen „Übersicht" und dem Zahnrad den neuen
  Menüpunkt „Suche".

### Hinweis zur Herkunft
Der Filter „Herkunft" wertet die Spalte `origin` aus (von der Uhr / von Hand /
importiert), nicht `manual`. `manual` bedeutet seit Web 2.11.0 ausschließlich
„die Uhr überschreibt diesen Einsatz nicht mehr" und sagt nichts darüber aus,
wie er entstanden ist.

## [Web 3.0.0] — 2026-07-29

Haupt-Sprung, weil der Umgang mit dem Inhaltsschlüssel selbst umgebaut wurde:
Er lässt sich ab sofort mitten in der Sitzung wiederherstellen, statt nur beim
Anmelden zu entstehen.

### Neu
- **Geschützte Angaben entsperren, ohne sich neu anzumelden.** Sind die
  Ende-zu-Ende-verschlüsselten Angaben in der Sitzung gesperrt — weil ein Link
  in einem neuen Tab geöffnet, der Browser neu gestartet oder das Passwort ohne
  Wiederherstellungsschlüssel zurückgesetzt wurde —, fragt jetzt ein Dialog
  direkt auf der Seite nach dem Kontopasswort und gibt den Inhaltsschlüssel
  wieder frei. Das Ab- und Neuanmelden entfällt. Das Passwort wird
  ausschließlich im Browser verwendet und verlässt ihn zu keinem Zeitpunkt.
  Betroffen sind Tagesübersicht, Einsatzansicht, Einsatzformular,
  Zeitraumübersicht, Import, Export und das Backup in den Einstellungen.
- Jeder Sperrhinweis trägt einen Knopf **„Entsperren"**. Damit lässt sich der
  Dialog nach einem Abbruch jederzeit erneut öffnen.

### Geändert
- Die Sperrhinweise auf allen betroffenen Seiten verweisen nicht mehr auf
  „bitte ab- und neu anmelden", sondern auf das Entsperren an Ort und Stelle.
- **Einsatzformular:** Nach erfolgreichem Entsperren werden die zuvor
  gesperrten Eingabefelder wieder freigegeben und vorhandene verschlüsselte
  Angaben nachgeladen — ohne die Seite neu zu laden.

### Behoben
- **Tagesübersicht:** Bei gesperrtem Schlüssel erschien kein Hinweis, warum
  Einsatzort, Alter und Diagnose leer blieben. Das Skript sprach ein Element
  `#lockbanner` an, das es im Seitenaufbau von `index.php` gar nicht gab —
  die Anzeige scheiterte still. Der Hinweis ist jetzt vorhanden.

## [Web 2.11.0] — 2026-07-29

> **Nachtrag (Web 3.2.0):** Zu dieser Auslieferung gehörte ein zweiter Teil
> („Export-Fehlerbehebung") mit Änderungen an `assets/export.js`,
> `assets/confirm.js`, `assets/import_profiles.js` und `import.php`. Nur die
> letzten drei Dateien sind im Repository angekommen; `export.js` wurde vom
> Folgecommit mit einem älteren Arbeitsstand überschrieben, ebenso dieser
> Changelog-Abschnitt. Die Export-Seite war dadurch ab Web 2.11.0 nicht mehr
> bedienbar. Nachgeholt mit Web 3.2.0 — siehe dort.

### Neu
- **Zeitraum-Übersicht:** Die drei Extremwert-Kacheln „Längste Flugstrecke",
  „Längste Einsatzdauer" und „Höchster Einsatzort" sind jetzt interaktiv.
  Hovern hebt den zugehörigen Karten-Pin (rot) und die Tabellenzeile (rosa)
  hervor; ein Klick fixiert die Hervorhebung und springt zur Tabellenzeile.
  Ein zweiter Klick auf dieselbe Kachel oder ein Klick auf freie Fläche löst
  die Fixierung wieder.
- **Einsatzansicht:** Kopfzeile zeigt jetzt ein Herkunftskennzeichen (Uhr /
  manuell / importiert) und — falls zutreffend — zusätzlich „editiert".

### Geändert
- **Zeitraum-Übersicht:** Kachelsatz auf zehn Kacheln (2×5) umgestellt:
  „Windeneinsätze" entfällt, „Anzahl Winden-Cycles" ist neu; „Einsätze" und
  „Flugtage" sind jetzt eigene Kacheln. Die bisherige Textzusammenfassung
  über der Karte entfällt ersatzlos.
- **Einsatztage-Leiste:** Trefferfläche des Aufklapp-Dreiecks in Jahres- und
  Monatszeile vergrößert (mind. 28 × 28 px) — mit dem Finger jetzt zuverlässig
  zu treffen.
- **Einsatzansicht — Kopfzeile:** Zeitangaben durch einen Halbgeviertstrich
  getrennt (statt Bindestrich ohne Abstand); die Kilometerangabe trägt jetzt
  die Beschriftung „Flugkilometer".
- **Datenmodell:** `missions.manual` bedeutet ab sofort ausschließlich „Uhr
  überschreibt Metadaten/Phasen/Rea nicht mehr". Herkunft (`origin`:
  Uhr/manuell/import) und Bearbeitungsstatus (`edited`) sind neue, eigene
  Spalten. Migration und Bestandsdaten-Backfill sind automatisch.
- **Backup-Format:** Version auf 4 angehoben (zwei neue Felder `origin` und
  `edited` je Einsatz). Backups der Version 3 lassen sich weiterhin
  einspielen; die Werte werden dabei abgeleitet.

### Behoben
- **Einsatzansicht:** Das Kennzeichen „manuell" erschien fälschlich auch nach
  jeder nachträglichen Bearbeitung eines von der Uhr aufgezeichneten
  Einsatzes. Ursache: Die Spalte `missions.manual` trug zwei Bedeutungen
  gleichzeitig (Herkunft und Schutz vor Uhr-Überschreiben). Behoben durch
  Auftrennung in `manual`, `origin` und `edited` (siehe Datenmodell oben).
- **Wartung:** In der Bootstrap-Liste der Migrationen (`schema.sql`) fehlte
  die ID `2026_07_28_kdf_ver_entfernt`. Betraf ausschließlich frische
  Neuinstallationen (überflüssige, aber folgenlose Prüfung beim ersten
  Aufruf von `update.php`); bestehende Installationen waren nicht betroffen.

## [Web 2.10.0] — 2026-07-28

### Neu — Export (Excel · vollständiges CSV · GuteSeele) und Rückimport
- Auf der Seite **Import / Export** gibt es unterhalb des Importbereichs einen
  Exportblock. Der Aufbau der Datei passiert vollständig im Browser: Der Server
  liefert nur Rohdaten, die geschützten Angaben werden lokal entschlüsselt.
  Ohne Haken „Patientendaten einschließen" sendet der Server den `pat_blob`
  gar nicht erst mit.
- **Profil A — Standard-Excel** (`.xlsx`, ein Blatt „Einsätze"): eine Zeile je
  Einsatz, alle Zeiten in Ortszeit, leere Werte als `-`. Ein Flugtag ohne
  Einsatz erscheint als eine Zeile mit Hubschrauber, Standort und Datum. Ohne
  Patientendaten entfallen die sechs geschützten Spalten ersatzlos.
- **Profil B — vollständiges CSV** (`.zip`): `einsaetze.csv`, `flugtage.csv`,
  `ruhezeiten.csv`, `felder.csv`, `LIESMICH.txt` und auf Wunsch GPX-Tracks.
  Verlustfrei und Grundlage des Rückimports. Semikolon, UTF-8 mit BOM, CRLF,
  Zeitstempel nach ISO 8601 mit Zonenversatz. Der Spaltensatz ist **immer
  gleich**: Ohne Patientendaten bleiben die `pat_`-Spalten vorhanden und leer,
  damit ein einlesendes Programm nicht zwei Fälle unterscheiden muss.
- **Profil C — GuteSeele-Layout** (`.xlsx`): erhält das bisherige Listenlayout
  für die Weitergabe an Dritte; bei mehreren Kalenderjahren ein Blatt je Jahr.
- **Passwortschutz** (optional, alle Profile): AES-256 nach WinZip-Standard über
  die neu mitgelieferte Bibliothek zip.js 2.8.34. ZipCrypto wird nicht
  verwendet. Zum Öffnen wird 7-Zip (Windows) oder Keka/The Unarchiver (macOS)
  gebraucht — der Windows-Explorer kann solche Archive nicht öffnen. Das
  Passwort wird nirgends gespeichert und nicht an den Server gesendet.
- **Rückimport** über zwei neue Formate: `export_csv_v1` liest `einsaetze.csv`
  (auch direkt aus dem `.zip`, bei Bedarf nach Passwortabfrage) und übernimmt
  Phasen, Koordinaten, Reanimationsdokumentation und alle Einsatzfelder.
  `export_excel_v1` liest den Standard-Excel-Export und zeigt vorher an,
  welche Felder danach leer bleiben.
- Neuer, ausschließlich lesender Endpunkt `api/export_data.php`.
- Neu: `docs/Export-Format.md` mit der vollständigen Feldliste je Profil.

### Geändert
- `api/import_commit.php` schreibt jetzt **alle** Phasen 2–9 samt Koordinaten
  sowie die Reanimationsdokumentation, nicht mehr nur Phase 2. Formate, die
  diese Angaben nicht liefern, verhalten sich unverändert: Es wird weiterhin
  nur Phase 2 angelegt, und eine vorhandene Reanimationsdokumentation bleibt
  unangetastet.
- Die Prüftabelle in Schritt 2 zeigt die Spalten, die das erkannte Format
  vorgibt — das vollständige CSV hat 75 Spalten und wäre sonst unlesbar.

### Bekannte Eigenheit
- Beim Rundlauf über das CSV wird eine abweichende Besatzung nach dem
  Rückimport **ausdrücklich** in allen Rollen gespeichert, während vorher
  einzelne Rollen vom Flugtag geerbt wurden. Der Export schreibt die
  *effektive* Besatzung; die erneut exportierte Datei ist deshalb identisch,
  nur die Speicherung ist expliziter.

## [Web 2.9.0] — 2026-07-28

### Geändert — Einsatznummer verschlüsselt
- Die Einsatznummer (Leitstellen-Nummer) ist ein Fallbezeichner, über den sich
  bei der Leitstelle die betroffene Person ermitteln lässt — sie gehört damit
  zu den geschützten Angaben. Sie liegt jetzt Ende-zu-Ende-verschlüsselt im
  `pat_blob` statt im Klartext in `missions.mission_no`; die Spalte entfällt.
- Migration `2026_07_29_einsatznummer_verschluesselt` entfernt die Spalte
  `missions.mission_no` ersatzlos. Vom Betreiber bestätigt: In der
  Produktivinstanz war zum Zeitpunkt der Migration keine einzige
  Einsatznummer belegt, eine Übernahme ist deshalb nicht nötig — der Server
  könnte bestehende Klartextwerte mangels Schlüssel ohnehin nicht selbst in
  den `pat_blob` überführen. Aus demselben Grund gilt: Backups, die vor
  Web 2.9.0 erstellt wurden, enthalten die Einsatznummer noch als Klartextfeld
  auf Einsatzebene statt im `pat_blob` — Backups zählen ab dieser Version neu,
  ältere werden nicht mehr unterstützt.
- Das Formularfeld ist ins Feld für PatientInnendaten gewandert (jetzt an
  erster Stelle, oberhalb von Nachname) und wird nur noch clientseitig
  gespeichert.
- **Import bestehender Einsatzlisten:** Der Abgleich mit dem Bestand
  (`api/import_commit.php`, `action=check`) bekommt seit dieser Version nur
  noch Datum und Uhrzeit zu sehen, nicht mehr die Einsatznummer. Für den
  Nummernabgleich liefert `check` stattdessen die `pat_blob`s vorhandener
  Einsätze mit; der Browser entschlüsselt sie lokal. Dadurch werden
  Nummerndubletten **nur noch innerhalb der Flugtage erkannt, die in der
  Importdatei vorkommen** — der Preis der Verschlüsselung. Tag und Alarmzeit
  bleiben als zweites, uneingeschränktes Merkmal wirksam.
- `docs/Backup-Format.md`, `docs/Technik.md` und `docs/Handbuch.md`
  entsprechend nachgezogen.

## [Web 2.8.0] — 2026-07-27

### Neu — Import bestehender Einsatzlisten (Excel/CSV)

Neuer Eintrag **Einstellungen → Import / Export**: Eine vorhandene
Einsatzliste — etwa eine über Jahre gepflegte Excel-Jahresliste — lässt sich
in einem Durchgang übernehmen. Bedienung: `docs/Handbuch.md`, Abschnitt 7.

- **Die Datei wird nicht hochgeladen.** Lesen, Prüfen und Verschlüsseln
  passieren vollständig im Browser; der Server erhält Name, Geburtsdatum,
  Diagnose und Einsatzort ausschließlich als Chiffretext. Das ist keine
  Bequemlichkeit, sondern die einzige mit der Ende-zu-Ende-Verschlüsselung
  vereinbare Bauweise. Ist die Verschlüsselung gesperrt, bleibt der Import
  gesperrt — unverschlüsselt wird nichts gesendet.
- **Formate sind deklarativ beschrieben** (`assets/import_profiles.js`):
  Blatt, Kopfzeile, erwartete Überschriften und je Spalte das Zielfeld samt
  Parserkette. Ein weiteres Dateiformat heißt künftig, dort einen Eintrag zu
  ergänzen — an der Verarbeitung ändert sich nichts. Mitgeliefert ist das
  Profil „Einsatzdoku Christoph 17 (Jahresliste)".
- **Review-Tabelle mit Korrektur:** Jede Zeile wird geprüft und nach Flugtag
  gruppiert angezeigt, Hinweise gelb, Fehler rot, jede Zelle direkt änderbar
  mit sofortiger Neuprüfung. Fehlerhafte Zeilen blockieren nur sich selbst und
  lassen sich einzeln überspringen.
- **Dubletten** werden über die Einsatznummer, ersatzweise über Tag und
  Alarmzeit erkannt; je Zeile wählbar zwischen überspringen, überschreiben
  und trotzdem anlegen. Der Abgleich mit dem Bestand kommt mit Datum, Uhrzeit
  und Einsatznummer aus — Patientendaten sind dafür nicht nötig und werden
  auch nicht gesendet.
- **Pilotenwechsel im laufenden Dienst** wird automatisch abgebildet: Als
  Besatzung des Flugtags gilt die des ersten Einsatzes; abweichende spätere
  Zeilen erhalten eine abweichende Besatzung am Einsatz (aus Web 2.6.0).
- **Alles oder nichts:** Die Übernahme läuft in einer einzigen Transaktion.
  Bricht sie ab, bleibt kein halb eingespielter Jahresbestand zurück.
- Neu vendoriert: `assets/vendor/xlsx.full.min.js` — SheetJS Community
  Edition 0.18.5, Apache-2.0, lokal im Repo statt von einem fremden Server.
  Ein CDN-Aufruf würde verraten, wann jemand Einsatzdaten verarbeitet.

### Behoben — Excel-Uhrzeiten wären um 53 Minuten verschoben gewesen

- **Root Cause:** Excel speichert Uhrzeiten als Bruchteil eines Tages, gezählt
  ab 1899. Lässt man die übliche Bibliotheksfunktion daraus ein
  JavaScript-Datum bauen, rechnet der Browser die *damalige* Zonenzeit ein —
  für Mitteleuropa 53 Minuten. Aus einer Alarmzeit 10:41 wäre lautlos 09:48
  geworden, in jeder importierten Zeile. Die Rohzahl wird deshalb selbst
  zerlegt, ohne jeden Zeitzonenbezug.
- Beim Zerlegen mehrfacher Rettungsmittel wird nur an Komma und Semikolon
  getrennt, nicht am Schrägstrich — sonst zerfiele der Funkrufname „KE 71/1"
  in zwei Einträge.

### Behoben — Unmögliche Uhrzeiten wurden zum stillen Datumssprung

- **Root Cause:** `local_to_utc()` prüfte die Uhrzeit nur gegen das Muster
  `\d{2}:\d{2}`. Eine Eingabe wie „25:00" passt darauf, und die
  Datumsrechnung machte daraus klaglos den nächsten Tag 00:00 — der Einsatz
  wäre stillschweigend einen Tag verrutscht statt als Fehler aufzufallen.
  Jetzt wird zusätzlich der Wertebereich geprüft. Betrifft neben dem Import
  auch das Einsatzformular, das den Fall bereits sauber meldet („Ungültige
  Uhrzeit in den Phasen") — die Meldung kam bisher nur nie.

### Geändert — Intern

- `local_to_utc()` ist von `einsatz_form.php` nach `db.php` gewandert und
  steht jetzt neben seinem Gegenstück `fmt_local()`. Mit dem Import gibt es
  einen zweiten Aufrufer; zwei Kopien derselben Zeitrechnung wären die
  sicherste Art, sich später eine Stunde Versatz einzuhandeln.
- Importierte Einsätze hängen am selben virtuellen Gerät `manual-<userId>`
  wie von Hand nachgetragene (`final=1`, `manual=1`) — die Uhr überschreibt
  sie dadurch nie, und in der Geräteliste tauchen sie nicht auf.
- Jeder importierte Einsatz erhält eine Phasenzeile (Phase 2, Alarmierung).
  Ohne sie ließe er sich nicht im Einsatzformular öffnen, weil das Formular
  Beginn und Ende aus den Phasen rekonstruiert.
- `docs/JSON-Vertrag.md` grenzt seinen Geltungsbereich jetzt ausdrücklich auf
  die Strecke Uhr → Server ab; die Browser-Endpunkte unter `server/api/`
  stehen in `docs/Technik.md`, Abschnitt 4.
- Handbuch: neuer Abschnitt 7, die folgenden Abschnitte sind auf 8–12
  gerückt. Ein Querverweis auf die Verschlüsselung zeigte bislang auf das
  Backup-Kapitel und ist berichtigt.

## [Web 2.7.1] — 2026-07-27

### Verbessert — Abweichende Besatzung zeigt nur die Rollen der Maschine
- Der Haken „Abweichende Besatzung" öffnete bisher immer alle fünf Rollen.
  Jetzt erscheinen nur die, die der **Hubschrauber des Flugtags** laut
  Stammdaten überhaupt vorsieht — bei einer Maschine mit Pilot 1 und HEMS-TC
  also auch nur diese beiden. Dieselbe Regel gilt im Flugtag-Formular schon
  länger; beide folgen jetzt denselben Häkchen am Hubschrauber.
- **Kein Datenverlust dabei:** Eine nicht vorgesehene Rolle wird trotzdem
  eingeblendet, sobald bereits ein Wert darin steht. Sonst käme man an einen
  Eintrag nicht mehr heran, wenn der Flugtag später auf eine andere Maschine
  umgestellt wird. Ist am Flugtag noch kein Hubschrauber hinterlegt, werden
  wie bisher alle fünf Rollen gezeigt — der Haken wäre sonst funktionslos.

### Verbessert — Abweichungen sind farblich erkennbar
- Die Markierung **„(abw.)"** im Block „Besatzung" steht jetzt in Max Blau
  und halbfett statt in Grau. Verwendet wird eine um eine Stufe abgedunkelte
  Variante (`--blau-dark`): Reines Max Blau erreicht auf dem hellen
  Hintergrund ein Kontrastverhältnis von 3,8:1 und liegt damit unter der
  Schwelle von 4,5:1 für kleine Schrift — die dunklere Stufe erreicht 4,6:1
  und bleibt bei Sonnenlicht lesbar.

## [Web 2.7.0] — 2026-07-27

### Behoben — Neu angelegte Zugänge konnten kein Passwort setzen
- **Root Cause:** Der Link aus der Einladungsmail führte auf `reset_confirm.php`.
  Diese Seite kannte nur den Fall „bestehendes Konto, Passwort vergessen" und
  verlangte deshalb bedingungslos den Wiederherstellungsschlüssel. Ein frisch
  angelegtes Konto hat noch keinen — das Formular brach ab, bevor überhaupt
  etwas abgesendet wurde. Neue NutzerInnen konnten sich dadurch **nie** anmelden.
- Passwortvergabe und Passwort-Reset liegen jetzt gemeinsam in der neuen Datei
  **`pw_handling.php`**. Der Server bestimmt die Betriebsart allein aus dem
  Kontostand, nie aus dem, was der Browser mitschickt:
  - **Erstvergabe** (noch kein Inhaltsschlüssel): nur Passwortfelder. Der
    Browser erzeugt Inhalts- und Wiederherstellungsschlüssel, zeigt letzteren
    **einmalig** an und lässt ihn per Haken bestätigen; die Passwortfelder
    werden dabei schreibgeschützt, damit die bereits berechnete Hülle zum
    Passwort passt. Erst danach werden Passwort-Hash, Salz und **beide** Hüllen
    gemeinsam in einer Transaktion gespeichert.
  - **Reset** (Inhaltsschlüssel vorhanden): verlangt wie bisher den
    Wiederherstellungsschlüssel; `pat_wrap_rc` bleibt unberührt, der bekannte
    Schlüssel gilt also weiter.
- `einrichtung.php` und `reset_confirm.php` sind **entfallen**. Die früher in
  `auth_guard.php` erzwungene Ersteinrichtung nach dem ersten Anmelden entfällt
  ersatzlos: Ein anmeldbares Konto ohne Hüllen kann es nicht mehr geben.

### Behoben — Der Installer legte einen Administrator an, der sich nicht anmelden konnte
- **Root Cause:** `install.php` speicherte den Hash des **Klartext-Passworts**,
  während `login.php` seit der Umstellung auf Browser-Schlüsselableitung
  ausschließlich gegen das abgeleitete Auth-Token prüft. Beides konnte nie
  zusammenpassen — eine Neuinstallation war ohne Umweg über „Passwort
  vergessen" nicht benutzbar.
- Der Installer fragt jetzt **kein** Passwort mehr ab. Er legt den Zugang ohne
  Passwort an und zeigt auf der Erfolgsseite einen 24 h gültigen Einmal-Link
  auf `pw_handling.php`. Das Passwort verlässt damit auch bei der Installation
  nie den Browser.

### Behoben — Passwortwechsel konnte die geschützten Angaben unlesbar machen
- **Root Cause:** In `einstellungen.php` wurden Passwort-Hash und Schlüssel-Hülle
  in zwei getrennten Anweisungen geschrieben, und die Hülle nur „falls
  vorhanden". Schlug das Umpacken im Browser fehl, fing ein leeres `catch` das
  ab und das Formular wurde trotzdem abgeschickt: Das neue Passwort galt, die
  Hülle hing noch am alten — die geschützten Angaben waren nicht mehr lesbar.
- Der Wechsel läuft jetzt **atomar**: Lässt sich der Inhaltsschlüssel nicht
  umpacken, bricht der Browser ab und der Server ändert nichts. Beide
  Schreibvorgänge liegen in einer Transaktion.

### Behoben — Löschen über die Nutzer-Detailseite funktionierte nie
- **Root Cause:** In `admin_user.php` verglich die Sicherheitsabfrage die
  eingetippte E-Mail-Adresse mit `$u['email']`, obwohl `$u` erst **nach** der
  POST-Verarbeitung geladen wurde. Der Vergleich lief immer gegen einen leeren
  String, die Meldung „stimmt nicht überein" erschien auch bei korrekter
  Eingabe. Der Datensatz wird jetzt vor der Verarbeitung geladen und danach für
  die Anzeige aufgefrischt. (Der Löschen-Knopf in der Liste war nicht betroffen.)

### Behoben — Schlüssel blieben nach dem Abmelden im Browser
- `logout.php` beendete nur die PHP-Sitzung; Daten- und Inhaltsschlüssel
  blieben im `sessionStorage` liegen, weil die Weiterleitung per HTTP-Header
  geschah und damit nie JavaScript lief. Die vorhandene Funktion
  `EdCrypto.clearSession()` wurde nirgends aufgerufen.
- Abmelden räumt die Schlüssel jetzt ab. Zusätzlich verwerfen `login.php` und
  der Passwortwechsel Reste einer früheren Sitzung, bevor sie neue Schlüssel
  setzen — wichtig beim Kontowechsel im selben Tab.

### Geändert — E-Mail-Texte und Anmeldeseite
- Einladungs- und Reset-Mail (`admin_users.php`, `reset_request.php`) sind
  ausführlicher und nennen jetzt „Gen-EM Einsatzdokumentation Luftrettung" als
  Absender sowie `philipp@gen-em.org` als Kontakt bei Fragen/Problemen.
- `login.php`: Link zu „Passwort vergessen oder erstmalig setzen" heißt jetzt
  schlicht „Passwort vergessen?" — beide Fälle laufen ohnehin über denselben
  Weg (`reset_request.php` → `pw_handling.php`).

### Entfernt
- Spalte `users.kdf_ver` (Migration `2026_07_28_kdf_ver_entfernt`). Sie wurde an
  drei Stellen geschrieben, aber nirgends gelesen — seit dem Wegfall des
  Klartext-Logins in Web 2.1.0 gibt es nur noch einen Anmeldeweg.
- Toter Übernahme-Zweig in `backup_lib.php`: Bis Backup-Formatversion 1 enthielt
  die Datei die Schlüssel-Hüllen des Ursprungskontos, die beim Restore
  übernommen wurden. Seit Version 2 liegen die geschützten Angaben im (selbst
  verschlüsselten) Container als Klartext und werden vom Browser mit dem
  Schlüssel des **Zielkontos** verschlüsselt. Der Zweig konnte nur noch fremde
  Hüllen in ein Konto schreiben.

## [Web 2.6.0] — 2026-07-27

### Neu — Abweichende Besatzung je Einsatz
- Ein einzelner Einsatz kann jetzt von der Besatzung des Flugtags abweichen —
  gedacht für den Fall, dass während des Dienstes jemand wechselt (typisch:
  Pilotenwechsel am Nachmittag). Im Einsatzformular öffnet der Haken
  **„Abweichende Besatzung"** fünf Auswahlfelder (Pilot 1, Pilot 2, HEMS-TC,
  Flugretter, Sonstige), gefüllt aus den persönlichen **und** den zentralen
  Besatzungs-Vorbelegungen.
- Es müssen nur die tatsächlich abweichenden Rollen gefüllt werden; alle
  übrigen erbt der Einsatz weiterhin vom Flugtag. Bewusst redundanzfrei: Ohne
  Abweichung bleiben die neuen Spalten leer, es entsteht keine Kopie der
  Tagescrew am Einsatz. Haken entfernen leert die Felder wieder, der Einsatz
  erbt dann vollständig.
- Die Einsatzansicht zeigt dafür den neuen Block **„Besatzung"** mit dem
  Ergebnis beider Ebenen; abweichende Rollen sind mit „(abw.)" markiert,
  unbelegte Rollen entfallen.
- Neue Spalten `missions.crew_override` und `missions.crew_p1`…`crew_other`
  (Migration `2026_07_27_crew_override`).
- Die Uhr-App ist davon nicht betroffen — sie kennt keine Besatzung.

### Behoben — Zentrale Maschine oder Basis ging beim Speichern des Flugtags verloren
- Root Cause gefunden: Seit den zentralen Stammdaten (Web 2.4.x) baut
  `index.php` die Flugtag-Dropdowns aus persönlichen **und** zentralen
  Einträgen (`user_id IS NULL`), die Prüfung beim Speichern in `api/day.php`
  akzeptierte aber weiterhin nur persönliche. Eine ausgewählte zentrale
  Maschine oder Basis wurde dadurch stillschweigend auf „–" zurückgesetzt —
  ohne Fehlermeldung. Die Prüfung folgt jetzt derselben Regel wie die Liste,
  aus der ausgewählt wird.

### Behoben — Ausgeschiedene Personen und Bereitschaften gingen still verloren
- Stand in einem Auswahlfeld mit Stammdaten-Herkunft (Bergwacht-Bereitschaft,
  ab sofort auch Besatzung) ein Wert, der inzwischen aus den Stammdaten
  entfernt worden war, blieb das Feld beim Öffnen des Formulars unmarkiert —
  beim nächsten Speichern war der Wert weg. Ein solcher Altwert wird jetzt
  der Liste vorangestellt und bleibt erhalten.

### Behoben — Fehlende Migrations-ID in `schema.sql`
- Die ID `2026_07_26_zentrale_stammdaten` fehlte in der `skipped`-Liste am
  Ende von `schema.sql`. Folgenlos, weil die Sprungprüfung der Migration
  ohnehin griff, aber ein Verstoß gegen die dort dokumentierte Regel —
  nachgetragen. Neuinstallation und migrierter Bestand liefern jetzt
  nachweislich identische Tabellendefinitionen.

### Aufgeräumt
- `index.php`: In der Sortierfunktion der Tagestabelle standen die Zweige
  `winch` und `bw` doppelt (toter Code seit Einführung der Spalte
  Sekundärtransport) — entfernt, Verhalten unverändert.

## [Web 2.5.1] — 2026-07-26

### Behoben — Layer-Umschalter zeigte verzerrte Radiobuttons
- Root Cause gefunden: Die globale Regel `input,select{width:100%;padding:…;
  border:…}` (für Formular-Textfelder gedacht) griff auch in den neuen
  Kartenlayer-Umschalter (2.5.0) und zog dessen Radiobuttons zu breiten,
  unsichtbaren Kästchen mit dem Kreis am rechten statt am linken Rand.
  Behoben über die von Leaflet vergebene Klasse
  `.leaflet-control-layers-selector` (`width:auto`, kein Padding/Rahmen) —
  derselbe Musterfehler wie beim dokumentierten `.btn-primary`-Fall, hier für
  `input` statt `button`. Control zusätzlich optisch an die App angeglichen
  (Rahmen, Abstände, Akzentfarbe der Radiobuttons).

## [Web 2.5.0] — 2026-07-26

### Neu — Vollbildmodus für alle Karten
- Jede Karte (Tagesübersicht, Einsatzansicht, Zeitraum-Übersicht) hat jetzt
  oben links ein Vollbild-Control. Nutzt primär die native Fullscreen-API des
  Browsers; wo diese nicht auf beliebige Elemente anwendbar ist (u. a. iOS
  Safari), greift automatisch ein CSS-Overlay-Fallback mit eigener
  ESC-Behandlung. Als gemeinsame, wiederverwendbare Komponente umgesetzt
  (`assets/map_fullscreen.js`), keine neue externe Abhängigkeit.

### Neu — Umschaltbarer Kartenlayer mit topographischen Varianten
- Alle Karten bieten jetzt oben rechts einen Layer-Umschalter (Leaflet-
  Standardcontrol) zwischen dem bisherigen Standard-OSM-Layer und zwei
  Varianten mit Höhenlinien: „Wanderkarte (OpenHikingMap)" und
  „Topographisch (OpenTopoMap)". Beide sind reine Kachel-Layer ohne
  Standort- oder Patientendatenübertragung, ebenso gemeinsam umgesetzt
  (`assets/map_layers.js`).

### Geändert — Phasenmarker in der Einsatzansicht: Standard „Aus"
- Die zuvor deaktivierten Phasenmarker auf der Karte sind wieder aktiv,
  starten aber bei jedem Seitenaufruf ausgeblendet (keine Persistenz). Der
  Toggle („Phasen anzeigen"/„Phasen ausblenden") ist von unterhalb der Karte
  auf die Karte selbst gewandert (eigenes Control, unterhalb des
  Vollbild-Controls) und bleibt dadurch auch im Vollbildmodus bedienbar.
  Hover-/Klick-Kopplung zur Phasentabelle unverändert; löst keinen Fehler
  aus, wenn Marker gerade ausgeblendet sind.

## [Web 2.4.4] — 2026-07-26

### Geändert — Rollenspezifischer Cursor-Fokus bei Besatzung
- Der Cursor springt nach Anlegen/Bearbeiten/Löschen eines Besatzungs-Eintrags
  jetzt gezielt in das Namensfeld der **richtigen Rolle** (z. B. HEMS), nicht
  mehr immer in das erste (Pilot 1). Umgesetzt über einen rollenspezifischen
  Anker (`#besatzung-hems` usw.), gilt für Standortdaten und zentrale
  Stammdaten gleichermaßen.
- Hubschrauber-Tabelle (Standortdaten): Inhalt der Spalte „Rollen" ist jetzt
  ebenfalls zentriert (bisher nur die Spaltenüberschrift).

## [Web 2.4.3] — 2026-07-26

### Neu — Cursor-Fokus nach dem Anlegen
- Nach dem Anlegen, Bearbeiten oder Löschen eines Stammdaten-Eintrags (alle
  sechs Typen, Standortdaten **und** zentrale Stammdaten) springt der Cursor
  automatisch ins Namensfeld des jeweiligen Abschnitts — der nächste Eintrag
  lässt sich ohne Klick direkt eintippen.

### Geändert — Besatzung im Admin-Bereich rollengetrennt
- Die Besatzungs-Vorbelegungen unter „Zentrale Stammdaten" sind jetzt wie bei
  den Standortdaten nach Rolle getrennt (eigene Tabelle/Eingabefeld je Pilot 1,
  Pilot 2, HEMS, Flugretter, Sonstige) statt einem gemeinsamen Formular mit
  Rollen-Dropdown.
- Das Kennzeichen „systemweit" steht in den Standortdaten-Tabellen jetzt
  rechtsbündig in der Aktionen-Spalte (dort, wo bei persönlichen Einträgen
  „Bearbeiten"/„Löschen" stünden), nicht mehr direkt neben dem Namen.

## [Web 2.4.2] — 2026-07-26

### Behoben — Abschnitt bleibt nach Löschen/Bearbeiten zugeklappt
- Root Cause gefunden: Das Skript, das einen Standortdaten-Abschnitt nach dem
  Speichern/Löschen wieder aufklappt, war versehentlich nur innerhalb des
  Backup-Tabs eingebunden und lief daher auf dem Standortdaten-Tab nie. Jetzt
  unabhängig vom aktiven Tab eingebunden.

### Geändert — Spaltenüberschriften zentriert
- In den Tabellen Standorte und Hubschrauber (Standortdaten **und** zentrale
  Stammdaten) sind die Spaltenüberschriften jetzt zentriert. Ursache für das
  vorherige Fehlschlagen einer einfachen CSS-Regel: `table.data th` (Linksbündig)
  hat höhere Spezifität als eine einfache Klassenregel — behoben über
  `table.data.data-centered th`.

## [Web 2.4.1] — 2026-07-26

### Geändert — Feinschliff an den zentralen Stammdaten (2.4.0)
- Formularfehler bei den Hubschrauber-Rollen-Häkchen im Admin-Bereich behoben
  (falsche CSS-Verschachtelung erzeugte großen Abstand zwischen Kästchen und
  Beschriftung).
- Einstellungsmenü: „Administration" ist jetzt eine eigene, abgesetzte
  Überschrift mit den Punkten „NutzerInnenverwaltung" und „Zentrale
  Stammdaten" darunter.
- `admin.php` in `admin_users.php` umbenannt (klarere Abgrenzung zu
  `admin_user.php`, der Detailseite einer einzelnen NutzerIn).
- Überflüssige „Aktionen"-Spaltenüberschrift bei Standorte/Hubschrauber
  entfernt (Standortdaten und zentrale Stammdaten).
- Nach Anlegen/Bearbeiten/Löschen eines Stammdaten-Eintrags kehrt die Seite
  jetzt auch bei einer Fehlermeldung (z. B. Namenskonflikt) zum bearbeiteten
  Abschnitt zurück und klappt ihn wieder auf — bisher galt das nur bei Erfolg.
- Die Kennzeichnung „zentral" heißt jetzt „systemweit" (Badge, Warnhinweise,
  Fehlermeldungen, Leerzustände) — klarer verständlich als isoliertes Wort.
- Fehlermeldung bei Namenskonflikt zeigte den eingegebenen Namen nicht an
  (Ursache: Sonderzeichen direkt nach der Variable in der Zeichenkette);
  behoben durch Verkettung statt Interpolation.

## [Web 2.4.0] — 2026-07-26

### Neu — Zentrale (globale) Stammdaten durch Admin, Transportziele als Stammdaten
- Transportziele lassen sich wie die anderen Rettungsmittel unter
  *Einstellungen → Standortdaten* als Vorbelegung pflegen. Im Einsatzformular
  bleibt das Feld „Transportziel“ Freitext, erhält aber Autocomplete-
  Vorschläge (natives `<datalist>`) aus der eigenen und der zentralen Liste.
- Der Admin kann auf einer neuen Seite „Zentrale Stammdaten“ alle sechs Typen
  (Standorte, Hubschrauber, Besatzungen, Rettungsmittel, Bergwacht-
  Bereitschaften, Transportziele) zentral hinterlegen. Diese Einträge stehen
  automatisch allen NutzerInnen als Vorbelegung zur Verfügung und erscheinen
  in der persönlichen Übersicht mit dem Kennzeichen „zentral“ (nicht editier-
  oder löschbar).
- Beim Anlegen oder Umbenennen eines persönlichen Eintrags wird case-
  insensitiv gegen die zentrale Liste geprüft; bei Treffer wird gespeichert
  abgelehnt mit dem Hinweis „… ist bereits zentral hinterlegt“. Legt der Admin
  nachträglich einen Namen zentral an, der bei einzelnen NutzerInnen bereits
  persönlich existiert, erhält deren Zeile stattdessen den Warnhinweis
  „identisch mit zentralem Eintrag — kann gelöscht werden“ (beide Zeilen
  bleiben sichtbar).
- Die Standard-Vorbelegung (★) für Standort und Hubschrauber ist jetzt
  nutzerbezogen (neue Tabelle `user_defaults`) und funktioniert dadurch auch
  für zentrale Einträge — jede NutzerIn kann unabhängig von den anderen einen
  persönlichen oder zentralen Eintrag als eigenen Standard markieren.
- Backup: Export bleibt nutzerbezogen (Transportziele neu enthalten,
  Formatversion 2 → 3); Import überspringt Einträge, die zentral bereits
  vorhanden sind, und zählt sie in der Ergebnismeldung. Alt-Backups (Version 2)
  bleiben importierbar.

## [Web 2.3.4] — 2026-07-26

### Geändert — Koordinaten/Plus Code jetzt als Vorschlag statt Direktumschreiben
- Erkannte Koordinaten (Dezimalgrad, GDM, DMS) und Plus-Code-Vollcodes
  schreiben das Einsatzort-Feld nicht mehr sofort um, sondern erscheinen —
  wie ein Adresstreffer — als anklickbarer Eintrag in derselben Vorschlags-
  liste (z. B. „Koordinaten übernehmen (Dezimalgrad): 47.72610, 10.31700"
  bzw. „Plus Code übernehmen: 8FWH4HJM+7Q"). Erst mit der Auswahl werden
  `lat`/`lon` gesetzt und das Feld auf die normalisierte Darstellung
  aktualisiert. Ablauf dadurch für Adresse, Koordinate und Plus Code
  identisch. Kurzform- und Bereichsfehler-Hinweise bleiben als reine
  Statuszeilen-Meldung bestehen (kein Vorschlag, da nichts zu übernehmen).
- Keine Netzwerk-Anfrage weiterhin für alle vier Fälle (Koordinate, DMS,
  Plus Code, Kurzform/ungültig) — nur bei Adresstext wird wie bisher Photon
  angefragt.

## [Web 2.3.3] — 2026-07-26

### Neu — Einsatzort erkennt zusätzlich Grad/Minuten/Sekunden (DMS)
- Nachtrag zu 2.3.2: Das Einsatzort-Feld erkennt jetzt auch das Format
  **Grad/Minuten/Sekunden** (z. B. `47°39'11.6"N 10°21'34.3"E`), das im
  ursprünglichen Konzept bewusst ausgeschlossen war, um den Umfang klein zu
  halten. Umrechnung wie bei den übrigen Formaten vollständig lokal im
  Browser, keine Server-Änderung.

## [Web 2.3.2] — 2026-07-26

### Neu — Einsatzort akzeptiert Koordinaten und Plus Codes
- Das Einsatzort-Feld erkennt beim Tippen automatisch drei zusätzliche
  Formate und wandelt sie **vollständig lokal im Browser** in Koordinaten
  um, ohne die bestehende Adresssuche (Photon) zu verändern:
  **Dezimalgrad** (`47.7261, 10.3170`), **Grad/Dezimalminuten**
  (`47°43.57'N 010°19.02'E`) und **Plus-Code-Vollcodes** (`8FWH4HJM+7Q`).
  Wird eines der Formate erkannt, entfällt die Photon-Anfrage; die
  Statuszeile meldet das erkannte Format, ungültige bzw. unvollständige
  Werte werden als solche kenntlich gemacht.
- Neue Datei `assets/locparse.js` (reine Formaterkennung/Parser, keine
  DOM-/Netzwerk-Zugriffe) sowie die gevendorte Bibliothek
  `assets/openlocationcode.js` (`google/open-location-code`,
  Apache-2.0) für die Plus-Code-Dekodierung.
- Bewusste Ausschlüsse: **kein What3Words** (proprietär, nur per externer
  API dekodierbar — Datenschutz-Veto), **keine Plus-Code-Kurzformen**
  (bräuchten Geocoding eines Referenzorts), **kein Reverse-Geocoding**
  erkannter Koordinaten (kein Serverkontakt bei Koordinaten-/Plus-Code-
  Eingabe), kein UTM/MGRS und keine Grad/Minuten/Sekunden-Formate.
- Datenmodell (`pat_blob` → `loc: {addr, lat?, lon?}`) und die Konsumenten
  `einsatz.php`, `index.php`, `zeitraum.php` bleiben unverändert.

## [Web 2.3.1] — 2026-07-26

### Geändert — Lesbare Fehlermeldungen statt leerem HTTP 500
- `api/range.php`, `api/day.php`, `api/mission.php` und `api/backup_data.php`
  kapseln ihre Datenbankzugriffe jetzt in try/catch (Muster wie bisher schon
  bei `api/backup_restore.php`) und antworten bei einer Ausnahme mit
  `{"error": "...", "meldung": "..."}` statt eines leeren HTTP 500. Anlass:
  Nach dem Ausliefern von 2.3.0, aber **vor** dem Aufruf von `/update.php`,
  fehlte die Spalte `site_ele_m` noch — `zeitraum.php` zeigte dadurch nur
  „HTTP 500" ohne jeden Hinweis auf die Ursache.
- `zeitraum.php` und `einsatz.php` zeigen das Feld `meldung` jetzt mit an
  (`index.php` tat das für `api/day.php` bereits vorher).

## [Web 2.3.0] — 2026-07-26

### Neu — Karte und Statistik in der Zeitraum-Übersicht
- **Karte mit Einsatzort-Pins:** Monats- und Jahresansicht zeigen jetzt eine
  Leaflet-Karte mit einem Pin (Max Blau, weißer Rand) je Einsatz mit
  gespeicherten Koordinaten. Popup zeigt Datum und Adresse. Keine
  Trackpunkte (unverändert bewusst nicht ausgeliefert) und kein Clustering.
  Karte bleibt ausgeblendet, wenn kein Einsatz Koordinaten hat oder der
  Inhaltsschlüssel gesperrt ist.
- **Statistiktabelle** oberhalb der Einsatzliste mit acht Kennzahlen:
  durchschnittliche Einsätze/Flugtag, durchschnittliche Windenzyklen/Flugtag,
  Anzahl Windeneinsätze, Anzahl Einsätze, Anzahl Sekundärtransporte, längste
  Flugstrecke, längste Einsatzdauer, höchster Einsatzort. Divisor der
  Durchschnittswerte sind alle im Zeitraum angelegten Flugtage, **auch ohne
  Einsatz** — eine bewusste Semantikänderung der Kopfzeile (vorher nur Tage
  mit dokumentiertem Einsatz).
- **Neues Feld „Einsatzort-Höhe" (`site_ele_m`):** Höhe am Patientenkontakt
  (Phase 5, Fallback Phase 6), aus dem GPS-Track berechnet und in der
  Einsatz-Detailansicht angezeigt. Neuberechnung bei jedem Uhr-Upload,
  jedem manuellen Speichern und jedem Backup-Restore — eine einzige
  Implementierung (`site_elevation_lib.php`). Migration mit Backfill für
  Bestandseinsätze.
- **Button „Weiteren Einsatz nachtragen"** auf der Einsatzansicht direkt nach
  Neuanlage eines manuellen Einsatzes — führt zur Neuanlage für denselben
  Flugtag. Erscheint nicht beim Bearbeiten bestehender Einsätze.

### Neu — Verlassen-Warnung und Strg-/Cmd-Enter
- Einsatz-Formular, Flugtag-Metadaten und Flugtag-Anlage fragen jetzt beim
  Verlassen mit ungespeicherten Änderungen nach (Browser-Dialog); das
  reguläre Absenden löst keine Abfrage aus. Gemeinsamer Helfer
  `assets/forms.js`.
- **Strg-Enter** (bzw. Cmd-Enter auf macOS) sendet dieselben Formulare ab;
  in Textareas bleibt einfaches Enter ein Zeilenumbruch, die
  Enter-Sonderbehandlung im Einsatzort-Autocomplete ist unberührt.

### Behoben
- **Schockraum-Haken beim Transportziel** wurde nie angezeigt: Der
  Formular-Renderer gab Unterfelder nur bei Checkbox-Elternfeldern aus,
  „Transportziel" ist aber ein Textfeld. Der Haken erscheint jetzt stets
  sichtbar unter dem Feld, unabhängig von dessen Inhalt.
- **Phasenzeilen wurden nicht zeitlich einsortiert:** Ein nachträglich am
  Listenende ergänzter, zeitlich früherer Eintrag führte zu einer falschen
  Tagesüberschritt-Erkennung (`$dayOffset`) und einem falschen `started_at`.
  Die Zeilen werden vor der Verarbeitung nach Phasennummer sortiert (Phasen
  2–9 sind fachlich chronologisch); nach dem Speichern erscheint die Liste
  beim erneuten Öffnen automatisch sortiert.

### Geändert
- **Zusatz „(bei Einsatz)" beim Alter entfernt** — die Detailansicht zeigt
  nur noch die Zahl (Berechnung zum Einsatztag bleibt unverändert).
- **Zweistellige Jahreszahlen beim Geburtsdatum** (z. B. „23.04.33") werden
  jetzt korrekt interpretiert: gleitende Fensterregel 2000+JJ, bei
  Zukunftsdatum stattdessen 1900+JJ.
- Platzhaltertext „kurze Beschreibung (Detailansicht)" beim Feld
  „Beschreibung Einsatzort" entfernt.

## [Uhr 1.4.0] — 2026-07-25

### Geändert — Schnellmenü umsortiert
- **Schnellmenü der Hauptseite:** Beim Öffnen (lang START) ist jetzt die
  **Einsatzübersicht** vorausgewählt; ein Schritt nach oben liegt „Einsatztag
  beenden", nach unten folgen die Phasen 2, 3, 4 … Das Endlos-Scrollen durch
  alle Punkte bleibt erhalten.

> **Richtigstellung (nachgetragen mit Uhr 1.5.0).** Dieser Eintrag nannte
> ursprünglich vier weitere Punkte zur Reanimations-Bedienung (kurz START
> öffnet das Untermenü, lang START startet den Countdown neu, neuer Menüpunkt
> „Timer neu starten", Countdown-Neustart bei Defibrillation, Untermenü im
> Design des Schnellmenüs). Diese Änderungen waren geplant, sind mit 1.4.0
> aber **nicht** ausgeliefert worden — der Code blieb unverändert. Sie stehen
> jetzt in [Uhr 1.5.0]. Der Eintrag ist deshalb auf das tatsächlich
> Ausgelieferte gekürzt.

## [Web 2.2.3] — 2026-07-23

### Geändert — Favicon robuster eingebunden
- Der Verweis ist jetzt **wurzelbezogen** (`/assets/images/favicon.png`) statt
  relativ, damit die Auflösung unabhängig von der aufgerufenen Adresse ist.
  Der Pfad wird aus `SCRIPT_NAME` abgeleitet und funktioniert daher auch in
  einem Unterordner.
- `sizes="any"` an der `.ico` entfernt: Diese Angabe steht für skalierbare
  Symbole; manche Browser hätten die `.ico` dadurch bevorzugt und bei ihrem
  Fehlen gar kein Symbol angezeigt. Das PNG steht jetzt an erster Stelle,
  ergänzt um `apple-touch-icon` für iOS.

## [Web 2.2.2] — 2026-07-23

### Behoben
- **Papierkorb: Symbol und Text standen untereinander.** Ursache war ein
  Spezifitäts-Konflikt — `.daylist a` setzt `display:block`, steht weiter unten
  im Stylesheet und hat mehr Gewicht als `.trashlink`. Das beabsichtigte
  `display:flex` hat deshalb **nie** gegriffen; die frühere „leichte
  Verschiebung" war in Wahrheit reine Grundlinien-Ausrichtung. Die Regeln
  lauten jetzt `.daylist a.trashlink` und stehen nach `.daylist a`.
- Das Papierkorb-Symbol ist rund 30 % höher als die Schrift. Die `viewBox` im
  Markup ist dafür auf die Zeichnung zugeschnitten (vorher rundum Leerraum),
  sodass die Höhenangabe im Stylesheet der sichtbaren Größe entspricht.

### Geändert — Favicon
- Zusätzlich `favicon.ico` im Wurzelverzeichnis. Browser fragen diese Adresse
  von sich aus ab, unabhängig vom Verweis im Seitenkopf — damit erscheint das
  Symbol auch dann, wenn der Kopf-Verweis einmal ins Leere läuft oder der
  Browser ein früheres Fehlen zwischengespeichert hat.
- Die Verweise stehen jetzt zentral in `favicon_tags()` (in `db.php`) statt
  einzeln in 16 Dateien.

## [Web 2.2.1] — 2026-07-23

### Behoben
- **Kein Logo auf der Anmeldeseite.** Die in 2.2.0 neu ausgewertete Einstellung
  `logo_path` steht in bestehenden Installationen noch auf dem alten Wert
  `assets/logo.svg` — eine Datei, die es nie gab. Der Rückfallwert griff nicht,
  weil der Eintrag ja vorhanden war. Neue Hilfsfunktion `logo_src()` prüft jetzt,
  ob die angegebene Datei wirklich existiert, und nimmt sonst die mitgelieferte
  Bildmarke. Das stille Ausblenden bei Ladefehlern (`onerror`) ist entfallen,
  da es genau solche Fehler verdeckt.
- **Aufzählungspunkte im Einstellungsmenü.** Beim Umbau der Einsatztage-Leiste
  auf das Jahr/Monat-Akkordeon war die Regel `.daylist ul` entfallen, die auch
  das Einstellungsmenü entpunktet hatte.
- **„Abmelden" und „Abbrechen" im Bestätigungsdialog unterschiedlich groß.**
  `.btn-primary` trägt global `width:100%` und einen oberen Abstand für
  Formulare; im Dialog wurde beides nicht zurückgenommen. Dieselbe Ursache wie
  zuvor bei „+ Einsatz nachtragen" — die übrigen Knopf-Kontexte sind jetzt
  durchgesehen und abgesichert.
- **Papierkorb-Symbol und Beschriftung** sind jetzt an dieselbe Schriftgröße
  gekoppelt (1,4 em). Zuvor war das Symbol mit 24 px fast doppelt so hoch wie
  die 13-px-Schrift und wirkte dadurch versetzt, obwohl beide Kästen mittig
  zueinander standen.

## [Web 2.2.0] — 2026-07-23

### Geändert — Logos als Vektorgrafik
- Die Bildmarke liegt jetzt als **SVG** unter `assets/images/` (farbige und
  weisse Fassung, Originale der Gestaltung). Sie ist damit in jeder Größe und
  auf hochauflösenden Bildschirmen scharf — die bisherige Bildmarke in der
  Kopfleiste hatte mit 96×61 Pixeln zu wenig Reserve und wirkte dort leicht
  unscharf. Nebenbei sinkt die Datenmenge von rund 184 KB auf 11 KB.
- Favicon bleibt PNG (breiteste Unterstützung, Schärfe bei 64×64 belanglos)
  und liegt ebenfalls unter `assets/images/`.
- Alle Einbindungen laufen über `asset()`, tragen also die Version — nach
  einem Logo-Wechsel lädt der Browser es von selbst neu. Das ist gerade beim
  Favicon nützlich, den Browser sonst sehr hartnäckig zwischenspeichern.
- **Nebenbefund behoben:** Die Einstellung `logo_path` war seit jeher wirkungslos
  — Anmelde- und Einrichtungsseite banden das Logo fest ein, und der
  Vorgabewert zeigte auf eine nie existierende Datei (`assets/logo.svg`). Beide
  Seiten werten die Einstellung jetzt aus, mit dem neuen SVG als Rückfallwert.

## [Web 2.1.0] — 2026-07-22

### Behoben — Passwort zurücksetzen
- **Ein Reset machte das Konto unbrauchbar.** `reset_confirm.php` speicherte
  den Hash des rohen Passworts, während die Anmeldung den Hash des im Browser
  abgeleiteten Tokens erwartet — eine Anmeldung war danach unmöglich. Zusätzlich
  wurde der Inhaltsschlüssel nicht neu verpackt, sodass auch alle
  verschlüsselten Angaben unlesbar geworden wären.
- Der Reset verlangt jetzt den **Wiederherstellungsschlüssel**: Der Browser
  entpackt damit den Inhaltsschlüssel, leitet aus dem neuen Passwort Salz und
  Token ab und verpackt den Schlüssel neu. Server speichert alles in einer
  Transaktion — passt der Wiederherstellungsschlüssel nicht, bleibt das Konto
  unverändert.
- Kein Datenleck: Wer nur Zugriff auf das Postfach hat, kommt weiterhin nicht
  an die verschlüsselten Angaben.

### Entfernt — Unterstützung unverschlüsselter Konten
- Anmeldung, Salt-Endpunkt, Passwortwechsel und Zugriffsschutz kannten je einen
  Sonderweg für Konten ohne Browser-Schlüsselableitung (`kdf_ver = 0`). Da alle
  Konten umgestellt sind, sind diese Pfade entfallen — inklusive der Stelle, an
  der das Passwort einmalig im Klartext zum Server ging.
- Browser ohne Web-Krypto erhalten jetzt eine klare Meldung statt eines stillen
  Rückfalls auf den alten Weg.

## [Web 2.0.0] — 2026-07-22

### Versionierung eingeführt
- Die Weboberfläche hat jetzt eine eigene Version (`server/version.php`). Sie
  erscheint in der Fußzeile und hängt an allen Stylesheet- und Skript-Adressen,
  wodurch der Browser nach einem Update automatisch die neuen Dateien lädt —
  das manuelle Leeren des Zwischenspeichers entfällt.
- **Behoben:** Auf `zeitraum.php`, `papierkorb.php`, `flugtag_neu.php`,
  `einsatz_loeschen.php` und `flugtag_loeschen.php` stand die Fußzeile
  außerhalb des Inhaltsbereichs und war dadurch nicht sichtbar — Copyright und
  Lizenzhinweis fehlten auf diesen Seiten.

### Web
- **Neue geschützte Felder:** Nachname, Vorname und Geburtsdatum — wie
  Diagnose und Einsatzort Ende-zu-Ende-verschlüsselt im selben Container, also
  ohne Datenbankänderung und automatisch im Backup enthalten. Sie erscheinen
  nur in der Einsatzansicht, nicht in den Tabellenübersichten.
- **Alter wird aus dem Geburtsdatum berechnet** — bezogen auf den Einsatztag,
  nicht auf heute, und bei jeder Anzeige neu (kein Nachziehen bei Korrekturen).
  Ohne Geburtsdatum bleibt das Feld wie bisher von Hand eintragbar; die Spalte
  „Alter" in den Übersichten bleibt erhalten. Gemeinsame Berechnung in
  `assets/patient.js`, genutzt von Formular, Einsatzansicht, Tages- und
  Zeitraumübersicht.
- Papierkorb-Symbol und Beschriftung sind vertikal exakt mittig zueinander
  ausgerichtet (feste Zeilenhöhe hatte den Text nach oben versetzt).

- **Jahres- und Monatsübersicht:** Klick auf Jahreszahl oder Monatsnamen in der
  Einsatztage-Leiste öffnet `zeitraum.php` mit allen Einsätzen des Zeitraums als
  Tabelle (Datum statt Nummer, keine Karte, sortierbar, Zeile führt zum Einsatz)
  samt Kennzahlen. Das Dreieck klappt weiterhin nur auf und zu.
  Neuer Endpunkt `api/range.php` — bewusst ohne Trackpunkte, da bei einem
  ganzen Jahr sonst hunderttausende Koordinaten übertragen würden.
- **Standortdaten:** Nach dem Speichern wird jetzt gezielt zum jeweiligen
  Abschnitt umgeleitet — er klappt dadurch wieder auf, die Seite springt an die
  richtige Stelle, und ein Neuladen sendet das Formular nicht erneut ab.
- **Behoben:** „+ Einsatz nachtragen" lief weiterhin über die volle Breite. Der
  Knopf erbt aus dem Formular-Stil `width:100%`; in der Aktionsleiste fehlte das
  ausdrückliche `width:auto`, weshalb frühere Anläufe (Ausrichtung, Höhe) nichts
  bewirkten.

- **Kein Rahmen mehr an Aufklapp-Überschriften:** Der blaue Fokusrahmen passte
  nicht zur übrigen Form und umschloss bei geöffnetem Abschnitt den gesamten
  Inhalt. Ersetzt durch dieselbe dezente Färbung wie beim Überfahren mit der
  Maus — für Tastaturbedienung weiterhin erkennbar, ohne aufzufallen.
- **Fokusring bleibt nicht mehr nach Mausklicks stehen:** Er erscheint jetzt
  nur noch bei Tastaturbedienung (`:focus-visible`). Bei aufklappbaren
  Abschnitten umschloss er zuvor den gesamten geöffneten Bereich statt nur der
  Kopfzeile — dadurch wirkte die Umrandung von Jahr und Monat unterbrochen und
  überlagerte die Markierung des ausgewählten Tages. Bei Tastaturbedienung
  liegt der Ring nun innerhalb der Zeile.

- **Behoben: Übersicht blieb komplett leer.** Beim Gruppieren der Einsatztage
  wandelt PHP numerische Array-Schlüssel automatisch in Integer um („2026" →
  2026). Unter `strict_types` brach `e()` damit mit einem TypeError ab —
  mitten im Rendern der Leiste, sodass weder Tage noch Karte oder Tabelle
  erschienen. Zusätzlich schlug der Monatsvergleich ab Oktober fehl („12"
  wird zu 12, „07" bleibt Text), wodurch dort nie ein Monat aufgeklappt wäre.
  Beide Stellen wandeln jetzt ausdrücklich nach String.

- **Einsatztage-Leiste nach Jahr und Monat gruppiert:** Es ist immer genau
  ein Jahr geöffnet (echtes Akkordeon — ein anderes Jahr anklicken schließt
  das vorherige automatisch), darin genau ein Monat, standardmäßig der
  jüngste mit Einträgen. Springt man auf einen Tag in einem anderen
  Jahr/Monat (z. B. über den Papierkorb oder eine alte Verlinkung), klappt
  die Leiste automatisch dorthin auf.

- **Aktionsleiste und Papierkorb aufgeräumt:** Über mehrere Runden hatten
  sich für `.dayactions` und `.trashlink` mehrere, teils widersprüchliche
  Regeln im Stylesheet angesammelt. Zu einem einzigen Block zusammengeführt —
  „+ Einsatz nachtragen" und „Tag löschen" haben dadurch garantiert dieselbe
  Höhe, Schrift und Grundlinie; Papierkorb-Symbol und -Text sind horizontal
  zentriert und zueinander vertikal mittig ausgerichtet.
- **Kartenzoom vereinheitlicht:** Tagesübersicht und Einsatzansicht zoomen
  jetzt nach derselben Regel automatisch auf die Tracks (Rand proportional zur
  Kartengröße statt fester Pixelwert) und mit einer gemeinsamen Obergrenze —
  ein einzelner kurzer Track zoomt nicht mehr bis auf Gebäude-Ebene heran.
- **Max Blau** (Markenfarbe) sichtbarer eingesetzt: Fokusringe, Sortierpfeile
  in der Tagesübersicht, Kontrollkästchen und der „Flugtag anlegen"-Link
  nutzen jetzt Blau statt Orange — als ruhiger Gegenpart zu den
  Haupt-Aktionen (Orange) und Löschen (Rot).

- **Andere Rettungsmittel:** neue Vorbelegungsliste in den Standortdaten und
  Eingabe mit Vorschlägen im Einsatzformular (Suche ab zwei Zeichen, Klick
  übernimmt, freie Eingaben möglich). Jedes Rettungsmittel wird als eigener
  Datensatz gespeichert und lässt sich einzeln wieder entfernen; bisherige
  Freitexte werden bei der Migration automatisch aufgeteilt.
- **Standortdaten aufgeräumt:** Die fünf Bereiche sind jetzt aufklappbare
  Abschnitte und starten zugeklappt. Wer über einen Anker hineinspringt — etwa
  nach dem Speichern —, landet in einem automatisch geöffneten Abschnitt.
- **Flugtag von Hand anlegen** über die Einsatztage-Spalte, für Tage ohne Uhr.
- Kopfleiste bleibt beim Scrollen stehen; der Papierkorb ist beschriftet;
  „+ Einsatz nachtragen" und „Tag löschen" sind gleich hoch und gleich gesetzt.
- Kartenlinien durchgehend eine Stufe dünner, Einsatz- und Tagesansicht nutzen
  jetzt dieselbe Staffelung.

- Tagesübersicht besser lesbar: Zeilen abwechselnd schattiert, alle Spalten
  mittig ausgerichtet, Dauer kompakt gesetzt („3h 33min" statt „3 h 33 min"),
  damit die Spalte einzeilig bleibt. Bergwacht, Sekundär/Transport und
  „Flug km" haben mehr Luft bekommen; die Seite ist dafür 1200 px breit.

- **Neues Logo** (Hubschrauber-Bildmarke) für Kopfleiste, Login-,
  Einrichtungsseite und Favicon. Die Vorlagen wurden freigestellt (weißer
  Hintergrund → transparent, Kantenglättung erhalten); die weiße Fassung
  übernimmt die Maske der farbigen, damit beide deckungsgleich sitzen. Das
  Favicon liegt quadratisch mit Rand vor, damit es im Browser-Tab nicht
  verzerrt.

- Tagesübersicht: Spaltenüberschriften werden nicht mehr silbengetrennt —
  Winde, Bergwacht und „Flug km" stehen einzeilig, „Sekundär/Transport" bricht
  genau zwischen den Wörtern um; Alter ist so breit wie Beginn. Seitenbreite
  1150 px; die festen Spalten belegen rund 600 px, der Rest bleibt für
  Einsatzort und Diagnose.
- **Menüspalte bleibt stehen:** Die Einsatztage-Leiste nimmt die volle
  Fensterhöhe ein und scrollt bei vielen Tagen intern; der Papierkorb sitzt in
  einem festen Streifen darunter und ist dadurch immer sichtbar, ohne die Seite
  scrollen zu müssen.

- **Tagesübersicht zeigt Ladefehler an,** statt still leer zu bleiben: Liefert
  die Tages-API kein JSON (z. B. weil eine Migration fehlt), erscheint jetzt
  eine Meldung mit dem Anfang der Serverantwort. Vorher brach das Skript
  wortlos ab — Titel, Tabelle, Karte und der Löschknopf blieben leer.
- „Tag löschen" wird serverseitig eingeblendet und hängt nicht mehr am
  erfolgreichen Laden der Tagesdaten.
- Papierkorb: Aufbewahrung von 30 auf **90 Tage** verlängert; die Aktionen
  „Wiederherstellen" und „Endgültig löschen" sind gleich groß und bündig.
- Tagesübersicht: feste Tabellenaufteilung, damit die Spaltenbreiten wirklich
  greifen; Seitenbreite auf 1240 px erhöht, sodass Flugtag-Kasten, Karte und
  Tabelle gleich breit sind. Papierkorb-Symbol in fester Größe am unteren Rand
  der Einsatztage-Spalte.

- **Neue Felder:** „Sekundärtransport" (Haken, eigene sortierbare Spalte in der
  Tagesübersicht neben Bergwacht) und „Schockraum" (Haken beim Transportziel).
- **Papierkorb ist eine eigene Seite** und über ein Symbol unten in der
  Einsatztage-Spalte erreichbar — ausgegraut, solange er leer ist. Die
  Aktionen „Wiederherstellen" und „Endgültig löschen" sind jetzt Schaltflächen.
- Tagesübersicht: Spaltenbreiten in vier Stufen über Klassen statt Positionen
  (Farbe/Nr. sehr schmal; Alter, Winde, Bergwacht, Sekundärtransport schmal;
  Beginn, Dauer, Flugkilometer mittig und mittelbreit; Einsatzort und Diagnose
  bekommen den Rest). Neue Spalten verschieben dadurch keine Breiten mehr.
- Aktionsleiste unter der Tabelle: Schaltflächen nur so breit wie nötig,
  „Flugtag löschen" heißt jetzt „Tag löschen".
- Einsatzansicht: „Bearbeiten" und „Löschen" stehen rechts neben Titel und
  Uhrzeit statt darunter; Schaltflächen werden nicht mehr unterstrichen.
- „Abbrechen" auf den Löschseiten ist eine Schaltfläche statt eines Textlinks.
- **Altes Backup-Format entfernt:** Der serverseitige `.edbak`-Weg (Version 1)
  ist raus — Container-Funktionen in `backup_lib.php`, die Versionsweiche in
  `crypto.js`, der Import-Zweig samt Datei-Upload in `einstellungen.php` und
  Kapitel 4 der Formatdoku. Der Import prüft jetzt strikt die Dateikennung und
  lehnt alles andere mit klarer Meldung ab; damit kann kein zweiter Importweg
  mehr dazwischenfunken.
- Unter der Tagestabelle stehen jetzt zwei Schaltflächen: links „+ Einsatz
  nachtragen", rechts „Flugtag löschen" (weiterhin mit serverseitiger
  Bestätigungsseite).
- **Behoben:** Die neuen Seiten `flugtag_loeschen.php`, `einsatz_loeschen.php`
  und `papierkorb.php` banden `ui.php` ein zweites Mal ein (ohne `_once`),
  obwohl `auth_guard.php` sie bereits lädt — PHP brach mit „Cannot redeclare"
  ab, im Browser als Fehler 500 sichtbar.
- **Rückfragen laufen nicht mehr über Browser-Dialoge:** `window.confirm()` bot
  die Option „keine weiteren Dialoge dieser Seite anzeigen" — danach wären
  Löschungen ohne jede Nachfrage durchgelaufen. Alle Bestätigungen nutzen jetzt
  ein Fenster im Seiteninhalt (`assets/confirm.js`, `data-confirm`), das sich
  nicht abschalten lässt; „Abbrechen" ist vorausgewählt, Escape bricht ab.
- **Backup-Import schlug scheinbar fehl, obwohl er lief:** Der Formular-Handler
  brach das normale Absenden erst nach dem Einlesen der Datei ab. Bis dahin
  hatte der Browser das Formular längst mitgeschickt, sodass parallel der alte
  serverseitige Import lief und mit „Keine gültige Backup-Datei" antwortete —
  während der Browser-Import im Hintergrund korrekt durchlief. Das Absenden
  wird jetzt sofort unterbunden; Altformat-Dateien werden gezielt an den Server
  weitergereicht.
- **Papierkorb für Einsätze und Flugtage:** Gelöschtes wird zunächst nur
  markiert und bleibt 30 Tage wiederherstellbar (Anzeige unten auf der
  Übersicht, je Tabelle für Flugtage und Einsätze mit „Wiederherstellen" und
  „Endgültig löschen"). Der Aufräumjob entfernt Abgelaufenes automatisch.
- **Flugtag löschen** entfernt den kompletten Tag (Einsätze, Ruhesegmente,
  Tracks, Reanimationen, Flugtag-Angaben) und stellt ihn geschlossen wieder her.
- Schwere Löschungen laufen über eine **serverseitige Zwischenseite mit
  Umfangs-Anzeige** (ohne JavaScript wirksam) statt über einen Browser-Dialog.
- **Nutzer löschen** verlangt jetzt zusätzlich das Abtippen der E-Mail-Adresse;
  geprüft wird serverseitig.
- Uploads der Uhr für Einsätze im Papierkorb werden quittiert, aber verworfen;
  erst das endgültige Löschen sperrt die Referenz dauerhaft.
- **Backup läuft jetzt im Browser** (Format 2): Beim Export werden die
  geschützten Angaben lokal entschlüsselt und mit dem Backup-Passwort
  versiegelt; beim Import öffnet der Browser die Datei und verschlüsselt sie
  mit dem Schlüssel des **Zielkontos** neu. Damit lässt sich ein Backup in
  jedes Konto einspielen — der Server sieht zu keinem Zeitpunkt Klartext.
  Container: AES-256-GCM, PBKDF2 310 000 Runden, gzip, Kopf per AAD gebunden.
- Alt-Dateien (Format 1) werden am Kopf erkannt und weiterhin serverseitig
  importiert; ihre geschützten Angaben bleiben kontogebunden.
- Neue Endpunkte `api/backup_data.php` und `api/backup_restore.php`;
  `export_backup.php` entfällt.
- Ruhesegment-Tracks (Phase 1) auf der Tageskarte deutlich sichtbarer:
  warmes Grau statt Fast-Schwarz, kräftigere Linie mit Zoom-Anpassung.
- **Verschlüsselung ist jetzt Pflicht:** kein Modul-Schalter, keine
  Feldauswahl mehr — der Einstellungs-Reiter „PatientInnendaten" entfällt.
  Beim ersten Anmelden erzwingt das System die **Ersteinrichtung** mit
  einmalig angezeigtem Wiederherstellungsschlüssel (einrichtung.php); dieselbe
  Seite entsperrt nach einem Passwort-Reset per Wiederherstellungsschlüssel.
- Verschlüsselte Felder sind **Diagnose und Alter** (Nachname, Vorname und
  Geburtsdatum entfallen); der **Einsatzort** (Adresse + Koordinaten) wandert
  ebenfalls in den verschlüsselten Block — Klartext-Altbestände wurden per
  Migration verworfen (Spalten entfernt).
- Tagesübersicht: Spalten Nr. · Beginn · Dauer · **Einsatzort (Ortschaft aus
  der Adresse)** · **Alter** · **Diagnose** · Winde · Bergwacht · Kilometer;
  sortierbar außer Winde/Bergwacht. Karten-Pins entstehen jetzt aus den lokal
  entschlüsselten Koordinaten; Sperr-Banner mit Entsperr-Link nach Reset.
- **Admin-Passwortvergabe entfernt** (würde verschlüsselte Daten unlesbar
  machen); Hinweis auf „Passwort vergessen" + Wiederherstellungsschlüssel.
- Backup: exportiert die Schlüssel-Hüllen ohne Modul-Schalter; Alt-Backups
  mit Klartext-Ort werden beim Import toleriert (Ort wird verworfen).
- **Einsatzansicht komplett neu gebaut:** Bearbeiten-Link führt wieder zum
  richtigen Einsatz (die Seite hatte die Einsatz-ID verloren), volle Breite
  wie die Flugtag-Übersicht, Aktionsleiste nebeneinander.
- **Karten:** Einsatzort-Pins in der Farbe des jeweiligen Einsatzes (Ring in
  Trackfarbe); Tracklinien werden beim Rauszoomen dicker und nicht mehr
  vereinfacht — kurze Tracks bleiben auf der Tagesübersicht sichtbar.
- Überall „Flugtag" statt „Betriebstag" (Titel, Formular, Doku).
- Flugtag-**Notizen** stehen sichtbar im zugeklappten „Flugtag-Daten"-Kästchen.
- Standortdaten (vorher „Stammdaten", umbenannt): Hinweis „Rollen auf dem
  Hubschrauber:" vor den Häkchen.
- **Geräte umbenennbar** (gelber Bearbeiten-Button je Zeile).
- Administration: Name als eigene Spalte, ganze Zeile reagiert auf
  Hover/Klick; Abmelden fragt nach Bestätigung.
- **Backup (Export/Import):** Einstellungs-Reiter „Backup" sichert alle
  eigenen Daten (Einsätze inkl. Phasen/Reanimationen/Tracks, Ruhesegmente,
  Flugtage, Stammdaten, verschlüsselte PatientInnendaten samt
  Schlüssel-Hüllen) in eine einzelne `.edbak`-Datei — verschlüsselt mit frei
  wählbarem Passwort (AES-256-GCM, PBKDF2 200 000 Runden, manipulationssicher
  per GCM-Tag). Import ergänzt nur Fehlendes (Dubletten-Schutz über interne
  Referenzen), überschreibt nie. Formatbeschreibung: `docs/Backup-Format.md`.
- **PatientInnendaten-Modul (Ende-zu-Ende-verschlüsselt):** Felder Nachname,
  Vorname, Diagnose, Geburtsdatum, Alter (Alter automatisch aus Geburtsdatum,
  Stichtag Einsatzdatum; auch allein ausfüllbar). Ver- und Entschlüsselung
  ausschließlich im Browser (AES-256-GCM); der Login wurde auf
  Browser-Schlüsselableitung umgestellt (PBKDF2, 310 000 Runden) — der Server
  sieht das Passwort nie mehr und speichert nur Chiffretext. Eigener
  Einstellungs-Reiter: Aktivierung mit einmalig angezeigtem
  **Wiederherstellungsschlüssel**, Feldauswahl (Abwählen blendet nur aus),
  Modul an/aus, Zugriff-Wiederherstellen nach Passwort-Reset. Nachname-Spalte
  in der Tagesübersicht (lokal entschlüsselt, sortierbar). Bestehende Konten
  werden beim ersten Login transparent umgestellt.
- **Geräte-Kopplung per Kurzcode:** Im Web (Einstellungen → Geräte) einen
  5-Zeichen-Code erzeugen (60 Minuten gültig, einmal verwendbar), auf der Uhr
  am Startbildschirm **UP halten** und den Code eintippen — die Uhr holt sich
  ihre Zugangsdaten selbst und speichert sie dauerhaft. Geräte-ID und
  API-Schlüssel müssen nie mehr abgetippt werden; als einzige Einstellung
  bleibt die Server-Domain. Der bisherige Weg (manuell anlegen) bleibt als
  Alternative bestehen.
- **Stammdaten vereinheitlicht:** Alle vier Bereiche als helle Tabellen mit
  Aktionen in einer Zeile — Bearbeiten (gelb) und Löschen (rot); auch
  Besatzungs-Einträge sind jetzt umbenennbar; alles alphabetisch sortiert.
- **Standard-Maschine und Standard-Standort** (★): per „Als Standard" gesetzt;
  Flugtage ohne gespeicherte Auswahl werden damit vorbelegt.
- Kopfleiste: ⚙ ist jetzt ein Direktlink zu den Einstellungen (kein
  Aufklappmenü); mehr Abstand um Logo und Titel.
- **Sicherheit:** Automatische Abmeldung nach 30 Minuten Inaktivität (mit
  Hinweis auf der Login-Seite).
- **Einsatzfelder-Ausbau:** Feldsystem mit neuen Typen (Checkbox, Dropdown,
  bedingte Unterfelder, Tagesspalten-Flag). Neue Felder: Transportziel,
  Beschreibung Einsatzort, Windeneinsatz (Cycles 0–8, Cycles mit Patient,
  Luftverladung), Bergwacht (Bereitschaft aus Stammdaten + Namen/Infos),
  Anderer Notarzt, Weitere Rettungsmittel — alle als echte DB-Spalten.
- **Tagestabelle:** Spalten Nr./Einsatzort/Winde/Bergwacht, klickbare
  Spaltensortierung (Standard: Alarmierungszeit); Dauer strikt aus Phase 9 —
  ohne Phase 9 steht dort „kein Ende". Einsatz-Titel „Einsatz N · Zeit"
  (N = Tagesnummer nach Alarmierungszeit).
- **Einsatz löschen:** Button mit Bestätigung in der Einsatzansicht; Sperrliste
  verhindert Wiederanlage durch gepufferte Uhr-Daten (Einträge verfallen nach
  90 Tagen über den Aufräumjob).
- **Einsatzort:** Adressfeld mit Photon-Autocomplete (OSM, kostenlos, ohne
  Schlüssel) im Formular — auch für Uhr-Einsätze; Pin auf Einsatz- und
  Tageskarte.
- **Phasen-Marker:** Phasennummern an der GPS-Position auf dem Einsatz-Track
  (Kachel-Design, zoomfest, gestapelte versetzt), Umschalter unter der Karte;
  Hover-/Tipp-Kopplung in beide Richtungen zwischen Phasen-Tabelle und Karte.
- CSS-Fix: `hidden`-Attribut greift jetzt überall (u. a. Rollenfelder am
  Flugtag verschwinden korrekt).
- **Administration:** Klick auf eine NutzerIn öffnet die Editierseite (Rolle,
  E-Mail, neues Passwort, verbundene Geräte mit Aktivieren/Deaktivieren und
  Löschen). Admin-Geräteanlage ersatzlos entfernt (Selbstverwaltung genügt).
- **Geräte löschen ohne Datenverlust:** Löschen (mit Bestätigung, in
  Einstellungen → Geräte und auf der Admin-Editierseite) entfernt nur den
  Zugang — Einsätze und Tracks bleiben erhalten (Migration entkoppelt die
  Datenbank-Kaskade). Deaktivieren bleibt als sanfte Option.
- **Stammdaten** (Einstellungen → Stammdaten): Standorte, Hubschrauber mit
  Kennung und Rollen-Häkchen (Pilot 1/2, HEMS, Flugretter, Sonstige),
  Besatzungs-Vorbelegungen je Rolle, Bergwacht-Bereitschaften.
- **Flugtag mit Dropdowns:** Maschine und Standort aus den Stammdaten; die
  beim Hubschrauber angehakten Rollen erscheinen als Besatzungs-Dropdowns
  (gespeist aus den Vorbelegungen). Freitextfeld „Besatzung" entfällt; alte
  Freitext-Werte bleiben lesbar („alt"-Hinweis).
- **Web-Navigation neu:** Kopfleiste mit Vogel-Icon und „Einsatzdokumentation
  Luftrettung – Name" (Name im neuen Profil setzbar, sonst E-Mail); Menüs
  Übersicht / Administration / ⚙ Einstellungen (Profil, Geräte, Abmelden);
  „Verwaltung" heißt jetzt Administration. Geräte sind in die Einstellungen
  umgezogen (alte Adresse leitet weiter).
- **Profil:** Name und E-Mail änderbar; Passwortwechsel nur mit korrektem
  aktuellen Passwort (Migration: Namensfeld).
- Einsatztage-Leiste auf allen Inhaltsseiten (auch Einsatzansicht und
  Formular); Tagesklick öffnet die Übersicht des Tages. Einsatzansicht
  mittig. Fußzeile „© Gen-EM – OpenSource Software – AGPL-3.0" im
  Dokumentfluss rechts unter dem Inhalt, auch mobil.
- **Uhr-Paket:** Kartenmodus-Fix (Tasten werden im Browse-Modus ans System
  durchgereicht — Garmins Zoom/Verschieben erscheint); 2× Vibration nach
  „Dienst beginnen"; Rea-Gesamtdauer in Ziffernschrift (~50 % größer); neue
  **Statistik-Ansicht** (Einsätze/Alarmierungen des Tages); Hauptanzeige mit
  größerer, mittigerer Uhrzeit und Phase im unteren Drittel;
  **„Einsatztag beenden" sendet, bestätigt und schließt die App** — bei
  Sendeproblemen Rückfrage „Trotzdem beenden?" mit Warten-Option.
- Reanimation: Display bleibt während laufender Rea dauerhaft hell;
  Rea-Start vibriert 2×, Zyklusende 5× (statt 2×), Ereignis-Bestätigung
  kräftiger.
- Long-Press-Aktionen (Menüs, Adrenalin, Rhythmuskontrolle) feuern nach 1 s
  Halten sofort — nicht mehr erst beim Loslassen.
- **Einsatz-Abschluss statt Phase 10:** Nach Phase 9 „Einsatzende" bleibt der
  Einsatz offen; kurz START (oder grüner Menüpunkt) fragt „Einsatz beenden &
  senden?" — erst dann wird geschlossen und hochgeladen. Einsatzende/Dauer =
  Zeit der Phase 9. Migration löscht alte Phase-10-Zeitstempel und korrigiert
  Einsatzenden; Ingest und Formular akzeptieren nur noch Phasen 2–9.
- Uhr-Schnellmenü farbcodiert mit Endlos-Scrollen: Phasen 2–9,
  Einsatzübersicht (gelb), Einsatz abschließen (grün), Einsatztag beenden
  (rot); kurze Phasennamen auf der Uhr (Landung KKH, Übergabe, Einsatzende).
- Rea-Menü: neue Reihenfolge mit Rhythmuskontrolle (gelb, inkl.
  Countdown-Reset) und Adrenalin (pink) als Menüpunkte, „ENDE" statt „Rea
  beenden"; Direktkürzel lang UP/DOWN bleiben. „REA läuft" zusätzlich auf der
  Tempo-Seite.
- Server-URL in den Uhr-Einstellungen tolerant: „luftrettung.net" genügt.
- Uhr: Uhrzeit auf der Hauptanzeige deutlich größer, Phasenanzeige kleiner;
  Rea-Gesamtdauer größer; Kartenseite mit interaktivem Modus (kurz START =
  Garmins Zoom/Verschieben, BACK zurück zur Vorschau).
- Kosmetik-Paket Web: Einsatzformular mittig; Notizfeld im Eingabefeld-Stil;
  „Bearbeiten" als Button; Feldliste vertikal zentriert; „+ Phase hinzufügen"
  fokussiert das neue Dropdown; Fußzeile mit © Gen-EM und AGPL-3.0 auf allen
  Seiten.
- Navigation: Auf der Geräte-Seite tauschten „Geräte" und „Verwaltung" beim
  Klick die Plätze (abweichende Link-Reihenfolge).
- Migration „Mehrere Reanimationen": Ersatzindex vor dem Entfernen des
  UNIQUE (MySQL 1553); Runner überspringt bereits erledigte Einzelschritte.

### Installation
- `schema.sql` legt die Migrations-Buchführung (`schema_migrations`) an und
  trägt alle bisherigen Migrationen als erledigt ein — eine frische
  Installation ist sofort auf Endstand.
- Der Installer löschte beim Zurücksetzen nur neun alte Tabellen; die Liste
  wird jetzt aus `schema.sql` gelesen und bleibt automatisch vollständig.
- Neue Migration „Papierkorb" (`deleted_at`, `deleted_with_day`).


## [Uhr 1.3.6] — 2026-07-22

- **Einrichtung in der richtigen Reihenfolge (v1.3.6):** Fehlt die
  Server-Adresse, weist die Uhr jetzt darauf hin, sie in Garmin Connect
  einzutragen — vorher kam zuerst „Nicht gekoppelt", und der Kopplungsversuch
  scheiterte anschließend mit „Erst Server-Domain setzen". Neue Prüfung
  `Uploader.hasServer()`.
- Einstellungstexte neutral gefasst (Beispiel `einsatz.beispiel.de` statt der
  eigenen Domain) und der Hinweis ergänzt, dass Geräte-ID und API-Schlüssel
  beim Koppeln automatisch gesetzt werden.
- **Kartenseite entfernt (v1.3.5):** Sie funktionierte auf dem Gerät nicht
  zuverlässig und wurde vollständig aus dem Code genommen (`MapPage.mc`
  gelöscht, kein Rest im Pager). Der Pager läuft jetzt Uhr → Tempo →
  Statistik → Sync → Rea. Eine spätere Kartenansicht wird neu aufgebaut; die
  alte Fassung steckt bei Bedarf in der Git-Historie.
- **Neues Launcher-Icon (v1.3.4):** Hubschrauber-Bildmarke in 40x40, aus der
  hellen Fassung erzeugt — auf dem schwarzen App-Menü der Fenix bleibt damit
  die ganze Silhouette sichtbar (die farbige Fassung ist zur Hälfte dunkel und
  wäre dort halb verschwunden). Motiv mittig auf transparenter Fläche, also
  ohne Verzerrung.
- **Tastensperre öffnet nicht mehr das Schnellmenü (v1.3.3):** Kommt während
  des langen START-Drucks eine beliebige weitere Taste dazu, wertet die App das
  als Sperr-Kombination der Uhr — das Menü bleibt zu, und auch die Seitenwahl
  springt nicht an. Der lange Druck allein öffnet das Menü unverändert. Gleiche
  Absicherung in der Reanimations-Ansicht, wo langes UP/DOWN Adrenalin und
  Rhythmus markiert.
- **Rea-Menü neu:** groß umrahmte Felder (~4 je Seite, größere Schrift),
  Gruppen mit dünnen Trennlinien (Rhythmuskontrolle/Defibrillation ·
  Adrenalin/Amiodaron · **Zugang** [neues Ereignis]/Intubation/Sonographie ·
  ROSC/Tod · Übersicht), dicke Linie vor **„Rea BEENDEN"** (vorher „ENDE").
  Server und Doku kennen den Ereignistyp `zugang`.
- **Einsatzzähler:** Die Statistik zählt nur noch abgeschlossene Einsätze
  (Alarmierung + dokumentiertes Ende); der laufende zählt nicht mehr mit.
- **Sync-Seite:** Grün „Sync vollständig ✓", sobald kein Rückstand besteht —
  das konstruktionsbedingt immer offene laufende Ruhesegment zählt nicht mehr
  als „offenes Paket". Der Koppel-Hinweis erscheint nur noch ungekoppelt.
- App-Version 1.2.0 (Sync-Seite).
- **Geräte-Kopplung umgezogen:** Die Code-Eingabe liegt jetzt auf der
  Sync-/Versionsseite und startet mit **START gedrückt halten** (1 s) — die
  frühere „UP halten"-Geste auf dem Startbildschirm löste auf dem Gerät nicht
  zuverlässig aus. Der Startbildschirm zeigt ungekoppelt den Hinweis
  „Nicht gekoppelt — DOWN drücken"; die Kopplungs-Rückmeldung („Gekoppelt ✓")
  erscheint auf der Sync-Seite.
- **Absturz bei Ablauf des 2:00-Timers:** Das 5×-Vibrationsmuster überschritt
  Garmins Hardware-Limit von 8 Vibrationsprofilen. Muster jetzt gesplittet
  (3 + 2 Pulse); alle Vibrationsaufrufe zusätzlich abgesichert.
- **Karte:** Eigene Zoom-Steuerung statt des unzuverlässigen System-Browse-
  Modus — kurz START = Zoom-Modus, UP/DOWN zoomen um die Position, BACK
  zurück zum Track-Fit.
- **Sync-Diagnose:** Startbildschirm und Statistik-Seite zeigen den konkreten
  Fehlergrund („Keine Server-URL", „Zugangsdaten fehlen", HTTP-Codes) statt
  nur „Sync ausstehend".
- Statistik-Seite zeigt nur noch die Einsätze des Tages (Alarmierungs-Zähler
  entfernt); Zahl deutlich größer.
- Jeder Neustart des 2:00-Zyklus (Rhythmuskontrolle, manuell, Rea-Start)
  bestätigt mit 2× Vibration.


## [1.2] — 2026-07-18

### Hinzugefügt
- **Geräte-Selbstverwaltung** („Geräte"-Seite): NutzerInnen legen eigene Uhren
  an (Schlüssel einmalig sichtbar) und (de)aktivieren sie selbst.
- **Manuelle Einsätze:** Formular für Nachtragen und Bearbeiten
  (`einsatz_form.php`) mit dynamischen Phasenzeilen, Mitternachts-Logik und
  Zusatzfeldern; „+ Einsatz nachtragen" in der Tagesübersicht, „Bearbeiten"
  und „manuell"-Badge in der Einsatzübersicht.
- **Erweiterbare Zusatzfelder** über zentrale Definition
  (`mission_fields.php`); Startbestand: Einsatznummer, Notizen.
- Virtuelles, dauerhaft deaktiviertes Gerät „Manuelle Einträge" je NutzerIn
  als Träger von Handeinträgen.

### Geändert
- **Geräte werden deaktiviert statt gelöscht** — Upload-Schlüssel sofort
  gesperrt, alle Daten bleiben, Reaktivierung möglich; Löschen aus der
  Oberfläche entfernt. Ingest antwortet deaktivierten Geräten mit `403`.
- Manuell bearbeitete Einsätze sind vor Überschreiben durch Uhr-Uploads
  geschützt (`manual`-Marker); GPS-Punkte werden weiterhin ergänzt.
- Dokumentation neu strukturiert: Handbuch / Technik / Changelog;
  Anforderungskatalog als `archiv/Anforderungen_v1.2.md` eingefroren.

### Behoben
- **Datenverlust-Bug** in der Track-Persistenz: Teil-Chunks wurden nach einem
  Neustart mitten im Einsatz vom nächsten vollen Chunk überschrieben;
  zusätzlich konnten Upload-Lesezugriffe Punkte übersehen. Chunk-Ausrichtung
  jetzt garantiert, Tail-Lesen eindeutig.
- Reanimations-Timer überlebt App-/Uhren-Neustart (persistierter Zustand,
  epochenbasierte Fortsetzung).
- Einsatz-Kilometer werden bei Einsatzende eingefroren — verzögerte Uploads
  erhalten nicht mehr die Werte des Folgeeinsatzes.
- Ingest validiert `seq_from ≥ 0`.

## [1.1] — 2026-07-17

### Hinzugefügt
- **Tempo-Oberfläche** (aktuelle km/h + Einsatzdistanz), Seitenreihenfolge
  Uhr → Karte → Tempo → Rea.
- **Mehrere Reanimationen pro Einsatz:** „Rea beenden" (rot, mit Bestätigung)
  schließt eine Sitzung, erneuter START eröffnet die nächste; im Web je
  Sitzung eine eigene Tabelle. JSON-Vertrag v1.1 (`resus_sessions`).
- **Flugtag-Daten** in der Tagesübersicht: editierbare Felder Maschine,
  Basis/Standort, Besatzung, Notizen; Verknüpfung über (user_id, Datum).
- **Automatischer Aufräumjob** (max. 1×/Tag, ohne Cron): Trackpunkt-Waisen und
  alte Reset-Tokens.
- Web-Installer (`install.php`) mit Selbst-Sperre; Migrations-Runner
  (`update.php`) mit Buchführung; FTPS-Deploy per GitHub Actions;
  `.gitignore`.
- **GenEM-Branding**: Farbwelt, Bricolage Grotesque/Open Sans, Logo in
  Kopfleiste und Login, Favicon; Uhr-Launcher-Icon aus der Bildmarke.

### Geändert
- Schnellmenü der Hauptanzeige auf **lang START** verlegt (vorher lang UP).
- Rea-Untermenü selbst gezeichnet: farbcodierte Kacheln, Endlos-Scrollen,
  exakt zentrierte Beschriftung; Gesamt-Rea-Zeit lila; Bedien-Hinweistexte
  entfernt.
- Referrer-Policy auf `strict-origin-when-cross-origin` (OSM-Kacheln luden
  nicht).
- Lösch-Schutz der Uhr räumt Bestätigungsmarken mit auf.

### Behoben
- Monkey-C-Erstübersetzung: Modul-Callbacks über Träger-Klassen,
  `makeWebRequest`-Signatur, MapView-Pflichtaufrufe
  (`setScreenVisibleArea`, initiale Kartenfläche, keine Null-Flächen).

## [1.0] — 2026-07-16

### Hinzugefügt
- Erstes Gesamtsystem nach eingefrorener Spezifikation v1.0:
  - **Uhr-App** (Fenix 6 Pro): Dienst-Klammer („Dienst beginnen" /
    „Einsatztag beenden"), 10 Einsatzphasen mit Zeitstempeln und Position,
    Schnellmenü, Karten-Oberfläche mit Einsatz-Track (Anzeige-Cap 1000,
    Dichte-Halbierung), Reanimationsmodus (2:00-Zyklus, Vibration,
    Ereignis-Zeitstempel), GPS-Ausdünnung 15 m/10 s/max 1 s,
    Flash-Persistenz in Chunks, Offline-Puffer mit bestätigtem Löschen.
  - **JSON-Vertrag v1.0**: idempotente, inkrementelle Uploads
    (`client_ref`, `seq_from`/`next_seq`, 500-Punkte-Chunks).
  - **Web-App**: Login/Reset per Mail (eigener SMTPS-Client), Admin-Bereich
    (NutzerInnen, Geräte mit einmalig sichtbarem Schlüssel), Tagesübersicht
    mit Leaflet-Karte (Einsätze farbig, Ruhe-Track schwarz, Auto-Zoom ~75 %),
    Einsatzübersicht mit Phasen- und Rea-Tabelle.
