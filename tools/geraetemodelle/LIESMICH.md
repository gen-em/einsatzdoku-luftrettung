# Gerätemodelle — Teilenummer auf Modellname

```
python3 tools/geraetemodelle/erzeugen.py ~/.Garmin/ConnectIQ/Devices
python3 tools/geraetemodelle/erzeugen.py --leer      # gültige, leere Tabelle
```

Schreibt `server/geraetemodelle.php`. Rückgabewert `0` = Datei geschrieben,
`1` = keine lesbaren Gerätedateien.

## Wozu

Die Garmin-Uhr **kennt ihren Modellnamen nicht**. `DeviceSettings` führt ihn
nicht, und eine Modelltabelle auf einem Gerät mit 128 kB wäre der falsche
Platz. Sie sendet beim Koppeln deshalb ihre **Teilenummer**
(`006-B4261-00`), und der Server löst sie auf — so steht es in
`docs/JSON-Vertrag.md`, Abschnitt 1a, und so hat R42 es entschieden.

Diese Zuordnung steht nirgends als fertige Liste. Sie steckt in den
**Gerätedateien der Uhr-Plattform**, je Gerät eine `compiler.json` mit drei
Angaben, die hier gebraucht werden:

| Feld | Beispiel | wird zu |
|---|---|---|
| `displayName` | `Venu 3S` | Modellname |
| `webDocDeviceGroup` | `Watches/Wearables` | Geräteart `uhr`, sonst `sonstiges` |
| `partNumbers[].number` | `006-B4261-00` | Schlüssel (mehrere je Gerät möglich) |

**„Handy" kann hier nie herauskommen.** Eine Connect-IQ-App läuft nicht auf
einem Handy; die Handy-Angabe kommt aus der Android-App und geht an dieser
Tabelle vorbei (E-S4-28).

## Woher die Gerätedateien kommen

Aus demselben Ort wie beim Uhr-Prüfstand, mit derselben Einschränkung: Sie
liefert nur der **SDK-Manager** aus, eine Fensteranwendung mit
Garmin-Anmeldung. Auf einem Rechner ohne Bildschirm ist er nicht zu bedienen.

Wer am Arbeitsplatz ein eingerichtetes SDK hat, stellt `~/.Garmin/ConnectIQ`
über HTTPS bereit. Die Adresse kommt als `CIQ_GERAETE_URL` herein und steht
**bewusst nicht im Repositorium** — es ist öffentlich, die Dateien gehören
Garmin (ausführliche Begründung in `tools/uhr-pruefstand/LIESMICH.md`). **Wer
hier neu anfängt, hat die Adresse nicht und kann sie sich nicht herleiten —
sie muss erfragt werden.**

```bash
export CIQ_GERAETE_URL=https://beispiel.invalid/ciq
tools/uhr-pruefstand/pruefstand.sh aufbau     # holt Devices/ und Fonts/
python3 tools/geraetemodelle/erzeugen.py ~/.Garmin/ConnectIQ/Devices
```

## Solange die Dateien fehlen

`--leer` schreibt eine gültige, leere Tabelle. Die Anwendung läuft damit
vollständig — sie löst nur nichts auf: Jede Teilenummer landet unverändert in
`devices.geraet_teil`, `devices.geraet_modell` bleibt leer, und die
Geräteliste zeigt statt „Uhr · Venu 3S" eben „Uhr · 006-B4261-00".

**Nichts geht dabei verloren.** Genau dafür steht die Rohangabe in einer
eigenen Spalte: Ein späterer Lauf des Erzeugers kann jede Zeile nachträglich
auflösen. Ohne sie fiele jedes Gerät, das die Tabelle nicht kennt, dauerhaft
und unwiederbringlich auf „unbekannt".

## Nach dem Lauf: die Bestandszeilen nachziehen

```
php tools/geraetemodelle/nachaufloesen.php               # nur zeigen
php tools/geraetemodelle/nachaufloesen.php --schreiben
```

`pair.php` löst die Teilenummer **im Moment der Kopplung** auf, und nur dann.
Ein Gerät, das damals auf eine leere oder ältere Tabelle traf, bleibt sonst
für immer, wie es war. Das Skript geht die Zeilen mit Rohangabe durch und
trägt nach, was die Tabelle jetzt kennt — es ändert nur diese und rührt die
Rohangabe selbst nie an.

**Es geht dabei um mehr als den Modellnamen.** Solange die Tabelle eine
Teilenummer nicht kennt, steht in `geraet_art` die **ungeprüfte
Selbstauskunft**: Die Garmin-App sendet dort fest `"uhr"`, weil sie Uhr und
Radcomputer nicht unterscheiden kann. Erst die Gerätedateien können es. Ein
Edge wäre bis zum Nachauflösen als Uhr gezählt — und das ist ein falscher Wert
in der Statistik, nicht nur ein fehlender.

**Braucht Shell-Zugriff.** Auf einem Webspace ohne SSH gibt es diesen Weg
nicht; dort holen die Geräte ihre Angabe bei der nächsten Kopplung nach.

## Eine Falle: die Wortliste

**Solange die Tabelle leer ist, darf keine Ausnahme dafür eingetragen
werden** — `tools/wortliste/` wertet eine ungenutzte Ausnahme als Fehlschlag,
genau wie einen unerklärten Treffer.

**Nach dem ersten echten Lauf ist eine nötig, und zwar dateiweit.** Die
erzeugte Datei liegt unter `server/*.php` und fällt damit in Bereich (a) der
Wortliste; ihre Werte sind Zeichenketten und keine Kommentare, der Zerleger
räumt sie nicht weg. Darin stehen dann `Venu`, `Forerunner`, `fēnix` — allesamt
Sperrwörter (`tools/wortliste/sperrliste.json`). Die Ausnahme gehört auf die
**Datei**, nicht auf einzelne Zeilen: Ein Muster auf „Venu 3S" wäre nach dem
nächsten Lauf mit anderem Gerätebestand entweder unvollständig oder ungenutzt,
und beides ist rot. Begründung der Klasse G: öffentliche Produktnamen sind hier
die Sache selbst und nicht eine Formulierung, die sich ersetzen ließe.

**Und die gemeldete Zahl wird kleiner sein, als sie sollte:** Garmin schreibt
„fēnix" mit Makron, das Sperrmuster lautet `\bfenix`. Ein Teil der Namen bleibt
dadurch unauffällig. Wer die Zahl liest, weiß das besser.

## Was der Lauf meldet

Nicht nur die Zahl, sondern auch, was ihm auffiel: Gerätverzeichnisse ohne
`displayName` oder ohne `partNumbers`, Teilenummern, die nicht ins Muster
passen, und — der wichtigste Fall — **dieselbe Teilenummer an zwei Geräten**.
Das wäre kein Schönheitsfehler: Die spätere Statistik zählte das Gerät dann
unter zwei Namen. Der Lauf behält den ersten und sagt es.

Dazu die Gerätegruppen mit ihren Häufigkeiten. Taucht dort eine Gruppe auf,
die es vorher nicht gab, ist zu entscheiden, ob sie „Uhr" ist oder
„Sonstiges" — die Regel steht als `UHR_GRUPPE` oben im Skript.

## Was NICHT in die erzeugte Datei geht

Die Gerätedateien gehören Garmin und werden nicht eingecheckt. Was hier
entsteht, ist etwas anderes: eine Zuordnung öffentlicher Teilenummern zu
öffentlichen Produktnamen — Sachangaben, keine Übernahme der Dateien. Alles
Übrige (Auflösungen, Speichergrenzen, Schriften, Bilder) bleibt draußen.
Einordnung: `docs/Lizenzen.md`.

## Wer die Tabelle benutzt

`server/geraete_lib.php` über `geraet_modell_aufloesen()`. Sonst niemand —
und wer sie von Hand ergänzt, ergänzt sie an der falschen Stelle: Der nächste
Lauf wirft es weg.
