# Straßengeometrie der Bodeneinsätze

Ergebnis eines **einmaligen** Abrufs beim OSRM-Demoserver
(`router.project-osrm.org`, Profil `driving`), eingecheckt nach E-P1-03.

**Warum eingecheckt und nicht zur Laufzeit geholt.** Ein Referenzdatensatz,
dessen Erzeugung von einem fremden Dienst abhängt, ist genau dann nicht
mehr reproduzierbar, wenn dieser Dienst sich ändert oder verschwindet — und
das merkt niemand, bis der Vergleich in einer späteren Phase Abweichungen
meldet, die niemand erklären kann. Der Generator liest deshalb nur diese
Dateien und braucht kein Netz.

**Nur Boden.** Ein Hubschrauber folgt keiner Straße; Lufttracks entstehen
geometrisch im Generator.

## Was hier liegt

| Datei | Inhalt |
|---|---|
| `routen_soll.json` | welches Teilstück welches Einsatzes zu welcher Datei gehört |
| `strecke_<hash>.geojson` | eine Fahrstrecke als GeoJSON-Feature |

Der Dateiname ist der Hash des **Koordinatenpaares**, nicht die
Einsatzkennung. Viele Teilstücke wiederholen sich — derselbe Wagen fährt
dieselbe Strecke Wache → Klinik zwanzigmal. Über das Paar zu benennen
spart die Wiederholungen: 117 Teilstücke, 84 verschiedene Strecken.

Die Koordinaten sind auf **fünf Nachkommastellen** gerundet (rund ein
Meter). Das ist feiner als jede Straßenmitte und feiner als jedes GPS; die
sieben Stellen, die OSRM liefert, wären nur Ballast im Repo — sie hätten
die Dateien verdoppelt.

Jede Datei führt in `properties` mit, woher sie stammt, und dazu Distanz,
Fahrzeit und Punktzahl der Strecke. Der Generator benutzt Distanz und
Fahrzeit, um daraus ein plausibles Geschwindigkeitsprofil zu bauen.

## Erneut holen

```
python3 routen_holen.py         # holt, was fehlt
python3 routen_holen.py --neu   # holt alles erneut
```

Nötig ist das nur, wenn sich in den Quelldaten ein **Bodeneinsatz** oder
seine Koordinaten ändern. `routen_soll.json` wird bei jedem Lauf neu
geschrieben und zeigt dann, welche Datei fehlt.

Der Abruf braucht Netzzugang zu `router.project-osrm.org`. Ist er nicht
gegeben, meldet das Skript den blockierten Host — es weicht **nicht** auf
eine erfundene Geometrie aus. Eine erfundene Straße, die aussieht wie eine
echte, wäre schlimmer als eine fehlende Datei.
