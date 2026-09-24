# Straßengeometrie der Bodeneinsätze

Fahrstrecken und Fahrzeiten aus einem **einmaligen** Abruf beim
OSRM-Demoserver (Profil `driving`), eingecheckt nach E-P1-03: Ein Bestand,
dessen Erzeugung an einem fremden Dienst hängt, ist nicht reproduzierbar.

## Aufruf

```bash
python3 routen_holen.py          # holt, was fehlt · --neu: alles erneut
python3 fahrzeiten_holen.py      # Fahrzeiten-Tafel für aufbauen.py · --neu
```

## Was es misst

Nichts — es holt. `routen_soll.json` sagt, welches Teilstück welches
Einsatzes zu welcher `strecke_<hash>.geojson` gehört; der Name ist der Hash
des Koordinatenpaares, auf fünf Nachkommastellen (rund ein Meter), weil
derselbe Wagen dieselbe Strecke oft fährt. `properties` trägt Herkunft,
Distanz, Fahrzeit und Punktzahl; daraus baut der Generator das
Geschwindigkeitsprofil. `fahrzeiten.json` hält die echte Fahrzeit zwischen
Standort, Ort und Zielklinik — die Luftlinie ergab Fahrten mit 205 km/h.

## Was es braucht

Netz zu `router.project-osrm.org` — und nur dann, wenn sich ein
Bodeneinsatz oder seine Koordinaten ändern. Der Generator selbst braucht
kein Netz.

## Erwartete Zahl

`routen_holen.py` nennt am Ende die Teilstücke und die verschiedenen
Strecken daraus. `routen_soll.json` wird bei jedem Lauf neu geschrieben und
zeigt, welche Datei fehlt — erwartet: keine.

## Was es nicht kann

Luft und Fußweg: Ein Hubschrauber folgt keiner Straße, und für
`zustieg → ort` holt es keine (E-DA-13, `wegpunkte.ist_fussweg()`). Ist der
Host gesperrt, meldet es das und erfindet **keine** Geometrie.
