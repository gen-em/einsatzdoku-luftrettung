"""Hilfe der Ortsdatenprobe (S11-05e): liest aus einem Export-Archiv (Profil B)
die Zeilen bestimmter Einsaetze und die Punktzahl je GPX-Datei.

Anlass: Nr. 43 — Export und Import tragen die Ortsdaten seit S11-05e
verschlossen; die Probe vergleicht die Datei vor und nach dem Rueckspielen.

Aufruf:  python3 csvlesen.py archiv.zip ID [ID …]
Ausgabe: JSON {"zeilen": {id: {spalte: wert}}, "tracks": {pfad: punktzahl},
         "anzahl": Zeilen der einsaetze.csv}
"""
import csv
import io
import json
import re
import sys
import zipfile

archiv, ids = sys.argv[1], set(sys.argv[2:])
z = zipfile.ZipFile(archiv)
name = next(n for n in z.namelist() if n.endswith('einsaetze.csv') and 'felder' not in n)
text = z.read(name).decode('utf-8-sig')
trenner = ';' if text.split('\n', 1)[0].count(';') > text.split('\n', 1)[0].count(',') else ','
zeilen = {}
alle = 0
for r in csv.DictReader(io.StringIO(text), delimiter=trenner):
    alle += 1
    if r.get('einsatz_id') in ids:
        zeilen[r['einsatz_id']] = r
tracks = {n: len(re.findall(r'<trkpt ', z.read(n).decode('utf-8')))
          for n in z.namelist() if n.endswith('.gpx')}
print(json.dumps({'zeilen': zeilen, 'tracks': tracks, 'anzahl': alle}, ensure_ascii=False))
