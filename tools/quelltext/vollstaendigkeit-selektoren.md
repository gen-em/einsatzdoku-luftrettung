# Klassen in Selektoren, die niemand im eigenen Code setzt

Die Vollständigkeitsprüfung meldet jede Klasse, die JavaScript in einem
Selektor sucht (`querySelector`, `closest`, `matches`,
`getElementsByClassName`) und die kein eigener Quelltext setzt — weder im
Markup noch an einer Klassen-Stelle noch zusammengesetzt aus einem
geschlossenen Vorrat (Backlog Nr. 36). Ein solcher Selektor greift ins Leere,
und JavaScript meldet das nicht: Das Ergebnis ist eine leere Liste. So wirkte
in P3/O6 drei Pakete lang kein Filter der Suchseite (F-P3-AG).

Hier steht, was mit Grund nicht im eigenen Code gesetzt wird. Ein Eintrag,
den keine Fundstelle mehr braucht, ist selbst ein Befund.

| Klasse | Grund |
|---|---|
| `leaflet-interactive` | Vergibt Leaflet an jedes klickbare Kartenelement (`server/assets/vendor/leaflet/`); `zeitraum.php` lässt einen Klick darauf die Fixierung einer Kachel nicht lösen. |
| `leaflet-marker-icon` | Vergibt Leaflet an das Bild jeder Stecknadel (`server/assets/vendor/leaflet/`); dieselbe Stelle in `zeitraum.php`. |
