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
(`LIESMICH.txt`, GPX-Kopf, `created_at` **der Datei**), App-Version, die ID im
Trackdateinamen, das Herkunftskonto und der Prüfwert des Inhaltsschlüssels.
Die Diensttag-Kennung wird nicht verworfen, sondern durch ihre **Stelle** in
der Liste ersetzt — sonst ginge die Zuordnung Einsatz → Diensttag verloren,
und genau die soll ein Umlauf ja belegen.

**Zwei Felder mit eigener Regel** (seit Web 8.0.0):

- **`missions[].created_at` wird NICHT mehr normalisiert.** Der
  Anlegezeitpunkt eines Einsatzes kommt seit Web 8.0.0 beim Einspielen wieder
  zurück; er ist damit eine Angabe wie jede andere. Die alte Normalisierung
  hatte den Verlust jahrelang verdeckt — der Kreislauf sah ihn nicht, weil das
  Werkzeug wegsah. (Die Kopfangabe `created_at` der Datei bleibt normalisiert:
  Sie ist der Zeitpunkt des Exports und tatsächlich flüchtig.)
- **`deleted_at` wird normalisiert, aber nicht weggenommen.** Beim Einspielen
  entsteht der Papierkorbeintrag neu und bekommt den Einspielzeitpunkt, der
  Zeitwert kann also gar nicht überleben. Was überleben **muss**, ist die
  Unterscheidung leer/gesetzt. Ein gesetzter Wert wird deshalb durch die
  Zeitmarke ersetzt, ein leerer bleibt leer — der **Zustand** wird verglichen,
  nicht der Zeitpunkt. `deleted_with_day` wird gar nicht angefasst.

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
python3 vergleichen.py --art csv   --testabweichung a.zip a.zip
python3 vergleichen.py --art edbak --testabweichung a.edbak a.edbak --passwort …
```

**Dieselbe Datei auf beiden Seiten, und OHNE `--ausnahmen`.** Beides gehört zum
Aufruf: Die Proben sollen zeigen, dass das Werkzeug Unterschiede findet, nicht
mit echten Abweichungen vermischt werden. Mit geladener Ausnahmeliste schlägt
eine Hinprobe scheinbar fehl — „Zeile in `diensttage.csv` entfernt" trifft die
Regel `diensttage/* fehlt`, die es aus gutem Grund gibt, und wird dann als
*erwartet* gezählt statt als Meldung.

Ein Vergleich, der nichts meldet, ist **zweideutig**: Entweder ist alles
gleich, oder das Werkzeug schaut an der falschen Stelle hin. Die zweite Lesart
lässt sich nur ausschließen, indem man dem Werkzeug etwas hinlegt, das es
finden **muss** — und etwas, das es **nicht** melden darf.

**CSV: zehn Proben** — sechs Hinproben (geänderter Wert, fehlende Zeile,
zusätzliche Zeile, geänderter Trackpunkt, geänderte Feldbeschreibung,
geänderter Zeitraum) und vier Gegenproben, die genau das ändern, was die
Normalisierung wegnehmen soll.

**Sicherung: zwölf Proben.** Seit Web 8.0.0 kommen zwei Paare dazu, die die
neuen Regeln oben absichern:

| Probe | erwartet | prüft |
|---|---|---|
| Papierkorb-Zustand eines Einsatzes geändert | Meldung | leer ↔ gesetzt bleibt sichtbar |
| GEGENPROBE: Löschzeitpunkte verschoben | keine Meldung | der Zeitwert wird normalisiert |
| `created_at` eines Einsatzes geändert | Meldung | die Normalisierung ist aufgehoben |
| GEGENPROBE: `created_at` der Datei geändert | keine Meldung | die Kopfangabe bleibt normalisiert |

Die dritte Zeile war bis Web 7.3.1 eine **Gegen**probe mit umgekehrter
Erwartung. Dass sie umschlägt, ist der Beleg für E-S1-06.

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
internen Kennungen nimmt die Normalisierung weg; die Gerätekennungen stehen in
der Sicherung unter `days[].refs[].device_id` und weichen deshalb ab. Ein
Vergleich zweier Referenzstände über einen Wiederaufbau hinweg zeigt sie als
Abweichung — richtig so: Es ist ein anderes Gerät.

`created_at` **wird seit Web 8.0.0 nicht mehr wegnormalisiert** und weicht nach
einem Wiederaufbau ebenfalls ab. Auch das ist richtig: Die Zeilen sind neu
angelegt worden. Innerhalb eines Umlaufs (dieselbe Referenzdatei hinein, wieder
heraus) muss der Wert dagegen **wörtlich** stimmen — dafür ist er da.
