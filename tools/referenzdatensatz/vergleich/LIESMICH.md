# Vergleichswerkzeug (Arbeitspaket B5)

Beantwortet eine Frage mit einer Zahl statt mit einem Eindruck:

> Kommt derselbe Bestand nach einem Umlauf unverändert wieder heraus?

Das Projekt hat **keine automatisierten Tests**. Was es stattdessen haben kann,
ist ein Referenzzustand und ein Werkzeug, das jede Abweichung davon benennt.

## Die Teile

| Datei | Aufgabe |
|---|---|
| `lesen.py` | CSV-Archiv (`.zip`) und Sicherung (`.edbak`) einlesen |
| `normalisieren.py` | flüchtige Anteile durch Marken ersetzen |
| `vergleichen.py` | Feld für Feld vergleichen, Bericht schreiben |
| `kreislauf.py` | den ganzen Umlauf fahren: Konto → Einspielen → Export → Vergleich |
| `ausnahmen/` | erwartete Abweichungen, je Umlauf eine Liste |

## Was verglichen wird — und was nicht

**Verglichen wird Klartext.** Das CSV-Archiv trägt ihn ohnehin; bei der
Sicherung liegt das innere JSON im Klartext vor, weil der Browser vor dem
Versiegeln entschlüsselt (`docs/Backup-Format.md` 2). Chiffretext wird nie
verglichen — sein IV ist zufällig, ein Vergleich verglich also den Zufall.

**Normalisiert werden:** interne Kennungen, Erzeugungszeitpunkte
(`LIESMICH.txt`, GPX-Kopf, `created_at`), App-Version, die ID im
Trackdateinamen, das Herkunftskonto und der Prüfwert des Inhaltsschlüssels.
Die Diensttag-Kennung wird nicht verworfen, sondern durch ihre **Stelle** in
der Liste ersetzt — sonst ginge die Zuordnung Einsatz → Diensttag verloren,
und genau die soll ein Umlauf ja belegen.

**Verglichen wird über einen natürlichen Schlüssel**, nicht über die
Zeilennummer: In der Sicherung über `client_ref`, im CSV über Diensttag und
Beginn. Sonst verschöbe eine einzige fehlende Zeile alles dahinter, und der
Bericht meldete hundert Abweichungen, wo eine ist.

## Bedienung

```
# Nur zwei Dateien vergleichen
python3 vergleichen.py --art csv   referenz.zip   aktuell.zip
python3 vergleichen.py --art edbak referenz.edbak aktuell.edbak --passwort …

# Mit Ausnahmeliste und Bericht
python3 vergleichen.py --art csv a.zip b.zip \
    --ausnahmen ausnahmen/csv_umlauf.json --bericht /tmp/bericht

# Ganzen Umlauf fahren (legt ein frisches Konto an)
python3 kreislauf.py --art edbak --frisch
python3 kreislauf.py --art csv   --frisch
```

Rückgabewert: `0` = keine unerklärte Abweichung, `1` = Abweichungen,
`2` = Fehler oder eine nicht bestandene Probe.

## Die Probe aufs Exempel

```
python3 vergleichen.py --art csv --testabweichung a.zip a.zip
```

Ein Vergleich, der nichts meldet, ist **zweideutig**: Entweder ist alles
gleich, oder das Werkzeug schaut an der falschen Stelle hin. Die zweite Lesart
lässt sich nur ausschließen, indem man dem Werkzeug etwas hinlegt, das es
finden **muss** — und etwas, das es **nicht** melden darf.

Je Format zehn Proben: sechs Hinproben (geänderter Wert, fehlende Zeile,
zusätzliche Zeile, geänderter Trackpunkt, entfernte Phase, vertauschte
Diensttagzuordnung) und vier Gegenproben, die genau das ändern, was die
Normalisierung wegnehmen soll.

**Die Gegenproben greifen VOR der Normalisierung an.** Das ist der Punkt: Eine
Gegenprobe, die erst danach ansetzt, prüft die Normalisierung gar nicht — sie
würde in jedem Fall gemeldet und bewiese nur, dass der Vergleich Unterschiede
findet. Das sagen die Hinproben schon. Der erste Entwurf hatte genau diesen
Fehler und meldete 6/7 statt 10/10.

## Vor einem Regressionslauf gegen die Demo

Das Demo-Konto ist **veränderlich** — Besucherinnen und Besucher legen darin
Einsätze an. Vor einem Vergleichs-Export deshalb erst **zurücksetzen**: im
Adminbereich von Hand oder den automatischen 30-Minuten-Reset abwarten. Ohne
das misst der Vergleich fremde Änderungen und nennt sie Regression.

## Wenn der lokale Bestand verlorengeht

Er ist **reproduzierbar**, und zwar vollständig aus dem Repositorium:

```
sh   ../einspielen/lokal_starten.sh
python3 ../einspielen/einspielen.py --stufen konto
node ../einspielen/passwort_setzen.mjs '<Einrichtungslink>' nadokudemo0815
python3 ../einspielen/einspielen.py --stufen stammdaten,geraet,ingest,zuordnen,nachtragen,manuell,papierkorb,sperrliste
node ../browser/csv_import.mjs
node ../browser/referenz_export.mjs
```

Das ist keine Notfallanleitung, sondern der reguläre Weg — er wurde beim
Aufbau der Phase P1 zweimal gefahren, das zweite Mal ungeplant, nachdem ein
Fehler in `kreislauf.py` das Referenzkonto gelöscht hatte. Dauer: rund vier
Minuten für den Bestand, dazu je Export einige Minuten für die GPX-Dateien.

**Was dabei NICHT identisch wiederkommt**, weil es beim Anlegen entsteht:
interne Kennungen, `created_at` und die **Gerätekennungen** (`dev-…`). Die
ersten beiden nimmt die Normalisierung weg; die Gerätekennungen stehen in der
Sicherung unter `days[].refs[].device_id` und weichen deshalb ab. Ein
Vergleich zweier Referenzstände über einen Wiederaufbau hinweg zeigt sie als
Abweichung — richtig so: Es ist ein anderes Gerät.
