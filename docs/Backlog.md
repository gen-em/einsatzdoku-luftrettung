# Einsatzdoku — Backlog

Bewusst offene Punkte. Ausgelagert aus `Technik.md`, damit die Liste wachsen
kann, ohne die technische Dokumentation zu überfrachten.

**Nummern sind dauerhaft.** Verweise aus Code und Dokumentation nennen sie
(z. B. „Backlog Nr. 10"). Erledigte Punkte werden deshalb nicht gelöscht,
sondern nach unten in den Abschnitt *Erledigt* verschoben und behalten ihre
Nummer. Neue Punkte hängen sich hinten an.

---

## Offen

1. Reanimations-Zeiten im Nachtrage-/Bearbeitungsformular
2. Serverseitige Track-Vereinfachung (Douglas-Peucker) für die Web-Darstellung
3. GPX-Export (Datenmodell dafür vorbereitet: lat/lon/ele/ts je `seq`)
4. Geteilte Flugtage (Crew-weit statt je NutzerIn)
5. Geräte-Limit pro NutzerIn
6. **Weitere Zielgeräte.** Fenix 7/8 — Uhren mit Touch **und** UP/DOWN. Der
    Schalter „Touchbedienung verwenden" und das Profil dafür sind in
    `Input.mc` vorbereitet, aber ohne Zielgerät ungetestet. Vor der Aufnahme
    das Eingabeverhalten mit `tools/eingabe-probe` messen und in
    `Geraete-Eingabe.md` festhalten. *(FR945 und Venu 3s sind mit Uhr 1.6.0
    erledigt.)*
7. Kosmetik Uhr-Code: Typprüfer-Warnungen („container access") auflösen
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

---

## Erledigt

*(Nummern bleiben erhalten, damit alte Verweise gültig bleiben.)*

12. **Langer Action-Druck auf der Venu 3s am echten Gerät prüfen.** Im
    Simulator wurde ein Halten über 4,6 s nicht abgefangen; das Handbuch der
    Venu 3 nennt ein Steuerungsmenü nach 2 s. Bis das an der Uhr geprüft ist,
    liegt SELECT_LONG zusätzlich auf dem langen Zurück-Druck. Bestätigt sich
    das Abfangen, gehört es nach `Geraete-Eingabe.md`; bleibt es aus, kann der
    zweite Weg bestehen bleiben — er schadet nicht.
