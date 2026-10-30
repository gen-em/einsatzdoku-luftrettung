# Generator des Referenzdatensatzes

Macht aus den Quelldaten (`../quelldaten/`) alles, was der Einspiellauf
braucht. **Deterministisch:** Zweimal ausgeführt entsteht dasselbe, Byte für
Byte.

## Aufruf

```bash
python3 erzeugen.py     # nach ausgabe/ (steht in .gitignore)
python3 pruefen.py      # Vertrag, Folge, Krypto, Spuren
```

## Was es misst

`erzeugen.py` schreibt Ingest-Anfragen samt Sendeplan (Ortsdaten im
**Klartext** — das Einspielskript verschließt sie vor dem Senden),
Formulardaten, eine Importdatei, GPX und die Fußwege; was wohin, steht in
seinem Kopf. Spuren: fliegen, fahren aus `routen/`, gehen ab dem `zustieg`.
`pruefen.py` hält **jede** Anfrage gegen jede Grenze des JSON-Vertrags, die
für das Klartext-Format gilt, dazu
lückenlose Teilstücke, den Krypto-Rundlauf, Tempo und Höhe je Spur und die
Gehgeschwindigkeit je Fußweg (1,5–6,0 km/h).

## Was es braucht

Python 3 und `cryptography`; kein Netz. Die Zeitfenster je Teilstück und die
Frage „wird hier gegangen?" stehen **einmal**, in `quelldaten/wegpunkte.py`.

## Erwartete Zahl

`pruefen.py` ohne Befund; die Zahl der geprüften Anfragen steht im
Prüfdokument des Pakets und wächst mit dem Bestand. Die alten Diensttage
behalten bei einer Erweiterung byteweise dieselben Spuren (`diff -r`).

## Was es nicht kann

Verschlüsseln: Den Inhaltsschlüssel gibt es erst, wenn das Konto existiert —
das tut das Einspielskript mit `krypto.py`. Und ein Punkt im gültigen Bereich
ist noch keine plausible Spur; darum misst `pruefen.py` das Tempo.
