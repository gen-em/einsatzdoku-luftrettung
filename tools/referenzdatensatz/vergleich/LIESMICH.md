# Vergleichswerkzeug (Arbeitspaket B5)

Beantwortet eine Frage mit einer Zahl statt mit einem Eindruck:

> Kommt derselbe Bestand nach einem Umlauf unverändert wieder heraus?

Das Projekt hat **keine automatisierten Tests**. Was es stattdessen haben kann,
ist ein Referenzzustand und ein Werkzeug, das jede Abweichung davon benennt.

## Die Teile

| Datei | Aufgabe |
|---|---|
| `lesen.py` | CSV-Archiv (`.zip`) und Backup (`.edbak`) einlesen |
| `normalisieren.py` | flüchtige Anteile durch Marken ersetzen |
| `vergleichen.py` | Feld für Feld vergleichen, Bericht schreiben |
| `kreislauf.py` | den ganzen Umlauf fahren: Konto → Einspielen → Export → Vergleich |
| `ausnahmen/` | erwartete Abweichungen, je Umlauf eine Liste |

## Was verglichen wird — und was nicht

**Verglichen wird Klartext.** Das CSV-Archiv trägt ihn ohnehin; beim
Backup liegt das innere JSON im Klartext vor, weil der Browser vor dem
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
Zeilennummer: Im Backup über `client_ref`, im CSV über Diensttag und
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
python3 kreislauf.py --art edbak     --frisch
python3 kreislauf.py --art edbak-alt --frisch
python3 kreislauf.py --art csv       --frisch
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

**Backup: zwölf Proben.** Seit Web 8.0.0 kommen zwei Paare dazu, die die
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
python3 ../einspielen/einspielen.py --stufen stammdaten,geraet,ingest,zuordnen,nachtragen,manuell,papierkorb,sperrliste,schneiden
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
dem Backup unter `days[].refs[].device_id` und weichen deshalb ab. Ein
Vergleich zweier Referenzstände über einen Wiederaufbau hinweg zeigt sie als
Abweichung — richtig so: Es ist ein anderes Gerät.

`created_at` **wird seit Web 8.0.0 nicht mehr wegnormalisiert** und weicht nach
einem Wiederaufbau ebenfalls ab. Auch das ist richtig: Die Zeilen sind neu
angelegt worden. Innerhalb eines Umlaufs (dieselbe Referenzdatei hinein, wieder
heraus) muss der Wert dagegen **wörtlich** stimmen — dafür ist er da.

## Drei Läufe, zwei Referenzen (seit S2/AP5)

Seit Web 11.0.0 schreibt die Anwendung **Containerfassung 4** — ein ZIP mit
versiegelten Teilen (`manifest.edbak`, `kopf.edbak`, `eintraege/NNNN.edbak`,
`spuren/NNNN.edbak`; seit Web 11.1.0 auch die Einträge in Fenstern). Damit
gibt es zwei Fragen statt einer, und deshalb zwei Referenzdateien:

| Lauf | Referenz | Frage |
|---|---|---|
| `--art edbak` | `referenz/*.edbak` (Fassung 4) | Kommt derselbe Bestand nach einem Umlauf unverändert wieder heraus? |
| `--art edbak-alt` | `referenz/altformat/*.edbak` (einteilig, Nutzlast 7) | Kommt ein **vorhandener** Bestand einmal herüber? (R11) |
| `--art csv` | `referenz/*.zip` | unverändert |

**Der zweite Lauf ist formatübergreifend**, und das schlägt sich in seiner
Ausnahmeliste nieder: Der Kern der Fassung 4 trägt `stufe`, `n_original` und
`n`, die es in Nutzlast 7 nicht gab — 543 Abweichungen, die keine sind. Sie
stehen einzeln mit ihrer Zahl in `ausnahmen/edbak-alt_umlauf.json`. **Kein
einziger Spurpunkt ist darunter**; genau das soll der Lauf belegen.

> **Er fällt mit NaDoku 1.0 weg**, zusammen mit dem Altformat (Backlog
> Nr. 46). Bis dahin gehört er in jeden Regressionsdurchgang: Solange die
> Anwendung verspricht, alte Backups zu lesen, muss das jemand nachmessen.

### Die Läufe warten auf die Meldung, nicht auf einen Wortlaut

`kreislauf_edbak.mjs` wartete auf `/fertig|eingespielt|fehlgeschlagen|Fehler|
falsch/` im Text von `#impstate`. In S2/AP5b kam ein Ergebnis dazu, das keines
dieser Wörter enthält — „Abgebrochen — es wurde nichts übernommen." —, und der
Lauf wartete die vollen 300 Sekunden auf einen Zustand, den es schon gab.
Danach hätte er ein **leeres** Konto exportiert und verglichen.

Die Anwendung unterscheidet selbst: Ein Zwischenstand wird als reiner Text
gesetzt, ein Ergebnis über `melde(el, text, ton)` als
`<div class="meldung meldung-ok|warn|fehler">`. Die Werkzeuge warten deshalb
auf **dieses Element** und lesen seinen Ton; nur `meldung-ok` gilt als
bestanden. Ein künftiger Ergebnistext, an den hier niemand gedacht hat, wird
damit von selbst erkannt.

Wer ein neues Prüfmittel schreibt, das auf ein Ergebnis der Oberfläche wartet:
**auf `.meldung` warten, nicht auf Wörter.**

**Warum zwei Referenzordner und nicht zwei Dateien nebeneinander.**
`neueste()` verweigert die Arbeit, wenn im Ordner mehr als eine Datei je
Format liegt — mit Absicht: „sortiert und nimmt die erste" war der Fehler,
den S1/C7 behoben hat. Der Altformatstand liegt deshalb in einem eigenen
Unterordner statt in derselben Ablage.

**Die Zahl der Einzelvergleiche ist mit Fassung 4 gefallen** (286 739 →
252 882), und das ist kein Verlust an Prüfschärfe: Die Punkte werden nicht
mehr als Fünftupel je Punkt gezählt, sondern als dekodierte Spur — dieselben
48 981 Punkte, anders gezählt. Wer die Zahl als Fortschrittsmaß liest, liest
sie falsch.

## Die Werkzeuge brauchen HTTPS

`kreislauf.py` spricht mit `https://127.0.0.1:8443` und nicht mit
`http://127.0.0.1:8080` — das ist kein Geschmack, sondern Notwendigkeit:

> Das Sitzungs-Cookie trägt `Secure` (`auth_guard.php`). Chromium schickt ein
> solches Cookie auch über `http://127.0.0.1`, weil es localhost als
> vertrauenswürdig behandelt; Pythons `requests` hält sich an die Regel und
> schickt es nicht. Die Anmeldung geht dann durch, index.php wirft die Sitzung
> gleich wieder weg, und die Meldung lautet **„Anmeldung gescheitert:
> unbekannt"** — im Browser funktioniert dieselbe Anmeldung.

Wer also eine Anmeldung sieht, die nur in Python scheitert: die Adresse
prüfen, nicht das Passwort. `einspielen/lokal_starten.sh` fährt beide Ports
hoch (8080 ohne, 8443 mit TLS).
