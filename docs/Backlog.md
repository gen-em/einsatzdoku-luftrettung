# Einsatzdoku — Backlog

Bewusst offene Punkte. 

**Nummern sind dauerhaft.** Verweise aus Code und Dokumentation nennen sie
(z. B. „Backlog Nr. 10"). Erledigte Punkte werden deshalb nicht gelöscht,
sondern nach unten in den Abschnitt *Erledigt* verschoben und behalten ihre
Nummer. Neue Punkte hängen sich hinten an.

---

## Offen

1. Reanimations-Zeiten im Nachtrage-/Bearbeitungsformular
2. Serverseitige Track-Vereinfachung (Douglas-Peucker) für die Web-Darstellung
3. GPX-Export (Datenmodell dafür vorbereitet: lat/lon/ele/ts je `seq`)
5. Geräte-Limit pro NutzerIn
5. Kosmetik Uhr-Code: Typprüfer-Warnungen („container access") auflösen
8. Content-Security-Policy als zusätzliche Verteidigungslinie
9. `asset()` auf Datei-Zeitstempel statt globale Version umstellen
10. **`day_col` generisch auswerten.** Der Schlüssel `day_col` in
    `mission_fields.php` ist derzeit reine Dokumentation: Die Spalten der
    Tagestabelle sind an drei Stellen hartkodiert — `api/day.php` (SELECT +
    JSON), `index.php` (`<thead>`) und `index.php` (Zeilenrendering +
    `sortVal()`). Solange das so ist, erscheint die Spalte „abw. Crew"
    (Crew-Override, Web 2.6.0) nicht in der Tagesübersicht, obwohl sie
    definiert ist. Auflösung = einmalige generische Auswertung; berührt
    zusätzlich die CSS-Spaltenklassen (`c-winde`, `c-bw`, `c-sek`).
    Die Tagestabelle in `index.php` ist bewusst **nicht** auf
    `assets/missiontable.js` umgestellt: Sie zeigt Tagesnummer und Farbmarkierung
    statt Datum und gehört zu einem anderen Zusammenhang. Erst wenn `day_col`
    generisch ausgewertet wird, lohnt die Frage nach einer Zusammenführung.
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

12. **Schriften und Leaflet selbst ausliefern.** Beide werden zur Laufzeit aus
    dem Netz geladen: Bricolage Grotesque und Open Sans per `@import` von
    `fonts.googleapis.com`/`fonts.gstatic.com`, Leaflet von `unpkg.com`. Zwei
    Gründe, das zu ändern:
    * **Datenschutz.** Jeder Seitenaufruf meldet die IP-Adresse an Google
      beziehungsweise unpkg. In einer Anwendung, deren ganzer Zweck darin
      besteht, dass Patientendaten den Browser nicht unverschlüsselt verlassen,
      ist das ein Bruch in der Linie — und der einzige verbliebene.
    * **Ausfallsicherheit.** Wird der Abruf blockiert (Werbeblocker, strenger
      Trackingschutz), greift bei den Schriften die Ersatzliste; bei Leaflet
      fällt die Karte ganz aus. Der Schriftausfall ist seit Web 5.1.1
      unauffällig, weil die Ersatzliste normal breit ist — vorher wurde die
      halbe Oberfläche gedrungen (`'Arial Narrow'` als Ersatz für eine normal
      breite Grotesk).
      Umsetzung: die vier woff2-Dateien nach `assets/fonts/` und per
      `@font-face` mit `font-display:swap` einbinden; Leaflet-CSS und -JS als
      lokale Kopie mit fester Fassungsnummer. Berührt `style.css` und die
      `<head>`-Blöcke der Kartenseiten. Nebeneffekt: eine Content-Security-Policy
      (Nr. 8) wird damit überhaupt erst eng fassbar, weil keine fremden Quellen
      mehr erlaubt werden müssen.

