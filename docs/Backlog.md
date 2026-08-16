# Einsatzdoku — Backlog

Bewusst offene Punkte. 

**Nummern sind dauerhaft.** Verweise aus Code und Dokumentation nennen sie
(z. B. „Backlog Nr. 10"). Erledigte Punkte werden deshalb nicht gelöscht,
sondern nach unten in den Abschnitt *Erledigt* verschoben und behalten ihre
Nummer. Neue Punkte hängen sich hinten an.

**Zu den fehlenden Nummern 4, 6 und 7.** Sie waren vergeben und sind ohne
Eintrag verschwunden; ihr Inhalt ist nicht mehr rekonstruierbar. Sie bleiben
deshalb dauerhaft frei — weder werden sie neu vergeben noch nachgetragen. Diese
Notiz steht hier, damit die Frage nicht bei jedem Durchsehen erneut aufkommt.

---

## Offen

1. Reanimations-Zeiten im Nachtrage-/Bearbeitungsformular
2. Serverseitige Track-Vereinfachung (Douglas-Peucker) für die Web-Darstellung
3. GPX-Export (Datenmodell dafür vorbereitet: lat/lon/ele/ts je `seq`)
8. Content-Security-Policy als zusätzliche Verteidigungslinie.
   Seit Web 5.2.0 eng fassbar: Es wird keine fremde Quelle mehr geladen
   (Nr. 12), die Regel muss also nichts von außen erlauben.
11. **Sync-Seite meldet „Sync vollständig", obwohl die Uhr gar nicht senden
    kann.** Beobachtet ohne hinterlegte Server-Adresse: Die Seite zeigt
    gleichzeitig das grüne „Sync vollständig" mit Haken **und** unten den
    gelben Hinweis „Erst Server-Adresse setzen". Dasselbe tritt auf, wenn die
    Adresse gesetzt, das Gerät aber noch nicht gekoppelt ist.
    Ursache: `SyncView.onUpdate` wertet zwei voneinander unabhängige Größen
    aus und stellt sie unverbunden nebeneinander. `Model.backlogCount()`
    beantwortet ausschließlich die Frage „liegen abgeschlossene Pakete zum
    Senden bereit?" — vor dem ersten Dienst ist das zu Recht `0`. Daraus wird
    im Text aber „vollständig" und damit eine Aussage über den Übertragungsweg,
    den die Uhr zu diesem Zeitpunkt nie benutzt hat. `Uploader.lastError`
    bleibt dabei `null`, weil `SyncView.refresh()` `syncAll()` nur bei
    vorhandenem Rückstand anstößt — es gibt also nicht einmal eine Fehlerzeile,
    die den Widerspruch auflösen würde.
    Reine Anzeigefrage, kein Datenverlust: Wird ohne Einrichtung dokumentiert,
    puffert die Uhr korrekt und der Rückstand erscheint.
    Richtung der Auflösung: Der grüne Zustand setzt zusätzlich
    `Uploader.hasServer()` **und** `hasCredentials()` voraus. Fehlt eines von
    beidem, tritt an seine Stelle ein neutraler Einrichtungs-Zustand, und der
    heute unten stehende gelbe Hinweis wird zur Hauptaussage der Seite statt
    zur Fußnote. Betrifft nur `watch/source/SyncView.mc`; die Reihenfolge der
    Einrichtungsschritte (erst Adresse, dann Kopplung) ist dort bereits
    abgebildet und bleibt.
13. **Kosmetik Uhr-Code: Typprüfer-Warnungen („container access") auflösen.**
    Stand bis Web 5.4.0 irrtümlich als zweite Nummer 5 in dieser Liste — die
    5 gehört dem Geräte-Limit (siehe *Erledigt*). Inhalt unverändert, nur die
    Nummer ist neu vergeben; ältere Verweise auf „Nr. 5b" meinen diesen Punkt.
14. **Kopplungsablauf der Uhr: bestehende Kopplung vor einer Neukopplung
    abfragen und trennen.** Fall: eine geteilt genutzte Uhr. Wird sie neu
    gekoppelt und schlägt der Vorgang fehl, dokumentiert sie stillschweigend
    weiter auf das vorherige Konto. Gewünscht ist die ausdrückliche Reihenfolge
    abfragen → trennen → neu koppeln. Betrifft `watch/source/Pair.mc` und
    `server/pair.php`.

---

## Erledigt

Nummern bleiben vergeben (siehe Kopf). Die Einträge stehen hier in der
Reihenfolge ihrer Erledigung.

5. **Geräte-Limit pro NutzerIn.** War bereits vor dieser Verbesserungsrunde
   umgesetzt und stand nur noch versehentlich unter *Offen*; hier nachgetragen
   (Web 5.4.0, Entscheidung E10 der Verbesserungsrunde Web). Die Nummer 5 war
   doppelt vergeben — der zweite Eintrag (Typprüfer-Warnungen im Uhr-Code) hat
   die freie Nummer 13 bekommen.
12. **Schriften und Leaflet selbst ausliefern.** Erledigt in Web 5.2.0
    (Block A1.5). Bricolage Grotesque und Open Sans liegen als woff2 in
    `server/assets/fonts/` und werden per `@font-face` mit `font-display:swap`
    eingebunden (Schnitte 500/600 bzw. 400/600/700, Subsets latin und
    latin-ext, getrennt über `unicode-range`). Leaflet 1.9.4 liegt als lokale
    Kopie in `server/assets/vendor/leaflet/` — CSS, JS und die von der CSS
    referenzierten Bilder. Damit lädt keine Seite mehr etwas von
    `fonts.googleapis.com`, `fonts.gstatic.com` oder `unpkg.com`; die
    Ersatzschriftenliste bleibt als zweite Ebene bestehen. Nebeneffekt wie
    vorgesehen: Nr. 8 (Content-Security-Policy) ist jetzt eng fassbar, weil
    keine fremde Quelle mehr erlaubt werden muss.
9. **`asset()` auf Datei-Zeitstempel statt globale Version umstellen.**
   Erledigt in Web 5.4.0 (Block A3.2). `asset()` (`db.php`) hängt jetzt den
   Zeitstempel der jeweiligen Datei an, nicht mehr `WEB_VERSION`; eine
   Versionserhöhung ohne Änderung an einer Datei lässt sie damit nicht mehr
   neu laden. `WEB_VERSION` bleibt der Rückfall für den Fall, dass eine Datei
   nicht gefunden wird. Der FTP-Deploy überträgt nur inhaltlich geänderte
   Dateien (Zustandsdatei mit Prüfsummen auf dem Server), unveränderte behalten
   deshalb ihren Zeitstempel — Prüfschritt P8 der Verbesserungsrunde.
10. **`day_col` generisch auswerten.** Erledigt in Web 5.4.0 (Block A3.1).
    Der Schlüssel `day_col` in `mission_fields.php` war reine Dokumentation:
    Die Spalten der Tagestabelle standen an drei Stellen fest — `api/day.php`
    (SELECT + JSON), `index.php` (`<thead>`) und `index.php` (Zeilenrendering +
    `sortVal()`). Die Spalte „abw. Crew" (Crew-Override, Web 2.6.0) erschien
    dadurch nicht, obwohl sie definiert war. Jetzt wertet `mf_tagesspalten()`
    in der neuen Datei `server/mission_fields_lib.php` den Katalog als einzige
    Stelle aus; die CSS-Spaltenklassen heißen `c-dc-<spalte>` mit `.c-dc` als
    Vorgabe und sind nicht mehr an eine feste Spaltenfolge gebunden.
    Weiterhin **nicht** umgestellt ist die Tagestabelle auf
    `assets/missiontable.js`: Sie zeigt Tagesnummer und Farbmarkierung statt
    Datum und gehört zu einem anderen Zusammenhang. Diese Frage lässt sich
    jetzt beurteilen — beide Tabellen haben nun einen Spaltenkatalog, sie sind
    nur nicht derselbe.
