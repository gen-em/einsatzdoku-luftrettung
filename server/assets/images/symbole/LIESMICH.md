# Symbolvorrat Gen-EM NAdoku (P3, E-P3-18)

49 Zeichen, je eine Datei. Grundlage ist **Tabler Icons** (MIT-Lizenz,
Paweł Kuna; Lizenztext in `LICENSE-tabler-icons.txt`): 24 × 24, Strich 2 px,
runde Enden und Ecken, Farbe über `currentColor`. Ein Zeichen (Luftlinie)
ist ein eigener Entwurf im selben Stil. Jede Datei trägt im Kommentar den
Verwendungsort und die Quelle und ein `<g id="i">` als Anker für den Verweis.

Ablage im Repo: `server/assets/images/symbole/` samt Lizenzdatei.
Einbindung: `ui_symbol('haus')` in PHP, derselbe Aufruf in JS; kein Zeichen
als Inline-Pfad im Code (Vollständigkeitsprüfung).

Neue Zeichen: zuerst bei Tabler suchen (tabler.io/icons, gleiche Datei
unverändert übernehmen und umbenennen); nur wenn nichts passt, eigener
Entwurf im selben Stil — beides nach Freigabe (E-P3-06).

Gedrehte Winkel: `winkel.svg` zeigt nach unten; links/rechts/oben per
CSS-Drehung. Gefüllter Stern: per CSS `fill:currentColor`.

| Datei | Tabler-Name | Verwendung |
|---|---|---|
| `menu.svg` | menu-2 | Kopfleiste mobil: Schublade öffnen |
| `schliessen.svg` | x | Schublade, Blatt, Dialog, Filter-Plakette |
| `winkel.svg` | chevron-down | Akkordeon, Rückweg, Aufklapper (per CSS gedreht) |
| `punkte.svg` | dots | Aktionsmenü und Zeilenaktionen mobil |
| `zahnrad.svg` | settings | Einstellungen (Kopfleiste) |
| `kalender.svg` | calendar | Startseite; Datum ändern |
| `status.svg` | activity | Betrieb → Status (S8/AP5, Mockup 13) |
| `aktualisieren.svg` | refresh | Betrieb → Updates (S8/AP5, Mockup 13) |
| `uhrzeit.svg` | clock | Betrieb → Hintergrundjobs (S8/AP5, Mockup 13) |
| `server.svg` | server | Betrieb → Servereinstellungen (S8/AP5, Mockup 13) |
| `ziel-fern.svg` | cloud-upload | Betrieb → Backup-Ziele (S8/AP5, Mockup 13) |
| `lupe.svg` | search | Suche; Ortssuche |
| `plus.svg` | plus | Anlegen, Nachtragen, Hinzufügen |
| `stift.svg` | pencil | Bearbeiten |
| `korb.svg` | trash | Löschen, Papierkorb |
| `stern.svg` | star | Vorbelegung (gesetzt: fill per CSS) |
| `haken.svg` | check | Ja, Vollzug, Speichern |
| `warnung.svg` | alert-triangle | Warnung, Fehler, Zuordnung offen |
| `hinweis.svg` | info-circle | Hinweis |
| `schloss.svg` | lock | geschützte Angaben gesperrt |
| `schloss-offen.svg` | lock-open | geschützte Angaben entsperrt |
| `vollbild.svg` | maximize | Karte im Vollbild |
| `balken.svg` | chart-bar | Jahres-/Monatsübersicht; Betrieb → Statistik |
| `sortieren.svg` | arrows-sort | Sortierung (Kachelkopf mobil) |
| `pfeil-hoch.svg` | arrow-up | Sortierrichtung (Tabellenkopf) |
| `zurueck.svg` | arrow-left | Zurück zur Anmeldung (Rechtstexte) |
| `profil.svg` | user | Profil |
| `standort.svg` | map-pin | Standorte (Einstellungen) |
| `uhr.svg` | device-watch | Geräte |
| `sicherung.svg` | archive | Backup, Sicherungen |
| `tausch.svg` | arrows-exchange | Import/Export, Freigeben, Verschieben |
| `gruppe.svg` | users | NutzerInnen |
| `datenbank.svg` | database | Stammdaten systemweit; Betrieb → Komplett-Backup |
| `rechtstexte.svg` | file-text | Rechtstexte (Admin) |
| `kolben.svg` | flask | Demo-Konto, Demo-Hinweis |
| `werkzeug.svg` | tool | (frei, seit S8/AP5 — „Wartung“ als Seite gibt es nicht mehr) |
| `abmelden.svg` | logout | Abmelden |
| `ordner-plus.svg` | folder-plus | Anderen Diensttag aufnehmen |
| `geraet-entkoppeln.svg` | link-off | Gerät entkoppeln |
| `reanimation.svg` | activity | Reanimation |
| `einsatzort.svg` | map-pin-plus | Einsatzort (Karte, Formular) |
| `haus.svg` | home | Verwaltung → Installation (S8/AP5; bis dahin ungenutzt) |
| `klinik.svg` | building-hospital | Zielklinik auf der Karte |
| `position.svg` | current-location | Meine Position übernehmen |
| `karte.svg` | map-2 | Auf der Karte wählen |
| `hubschrauber.svg` | helicopter | Art luftgebunden |
| `fahrzeug.svg` | ambulance | Art bodengebunden |
| `ohne-zuordnung.svg` | circle-dashed | Diensttag ohne Rettungsmittel |
| `luftlinie.svg` | — (eigen) | Luftlinie |
