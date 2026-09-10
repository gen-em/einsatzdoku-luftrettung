# Klickprobe — das Prüfmittel, das bedient

Entstanden in S9 (Konzept, E-S9-16). Sie fährt Bedienwege im Browser und
nennt je Weg eine **Zahl**.

## Warum es sie gibt

Vor S9 hatte das Projekt vier Prüfmittel im Browser, und **keines davon hat je
ein Element bedient**: Der Bilderlauf fotografiert, die Vollständigkeit liest
das Stylesheet, die Linkprobe folgt Adressen, die Wartungsprobe zählt
Erwartungen auf einer Seite. Zwei gemeldete Fehler sind genau dort
hindurchgelaufen:

- **PS-2** (Backlog 102): Die Trefferliste der weiteren Rettungsmittel
  übernahm auf `click`, das Feld versteckte die Liste 150 ms nach `blur`. Wer
  die Maus länger hält, bekommt kein `click` — die Liste schließt, übernommen
  wird nichts. Auf jedem Bild sah die Liste richtig aus.
- **Nr. 148**: Der Knopf „Diensttage zusammenführen" führte auf 404, weil er
  `?ziel=` schrieb, wo die Seite `?d=` liest. Gefunden hat es erst die
  Linkprobe, die eigens dafür gebaut wurde.

Beide waren ein Klick, den niemand getan hat.

> **`locator.click()` findet PS-2 nicht.** Playwright hält die Taste dabei rund
> 10 ms. Die Probe fährt die Folge deshalb von Hand — `mouse.move`,
> `mouse.down`, **300 ms warten**, `mouse.up`. Das ist der Unterschied
> zwischen „ein Klick" und „ein Klick, wie Menschen ihn machen".

## Voraussetzung

Eine laufende lokale Installation mit Referenzbestand und Demo-Konto:

```
sh tools/referenzdatensatz/einspielen/lokal_starten.sh     # hochfahren
sh tools/referenzdatensatz/einspielen/lokal_einrichten.sh  # von Null
```

## Aufruf

```
node tools/klickprobe/probe.mjs                          # alle Wege, 1280 px
node tools/klickprobe/probe.mjs --nur ap1                # nur ein Paket
node tools/klickprobe/probe.mjs --breiten 390,1280       # mehrere Breiten
node tools/klickprobe/probe.mjs --finger                 # als Fingergerät
node tools/klickprobe/probe.mjs --bilder                 # Bild je Weg und Breite
node tools/klickprobe/probe.mjs --behalten               # Ausgabe stehen lassen
node tools/klickprobe/probe.mjs --marke "vorher"         # Beschriftung im Bericht
```

**Breite und Eingabeart gehören zusammen** (R76): 44 px am Fingergerät und
unter 1024 px, 36 px am Zeigergerät ab 1024 px. Der Bericht nennt beides, und
der Dateiname jedes Bildes trägt es — `…-1280-zeiger-36px.png`. Ohne das ist
ein Bild kein Beleg: Unter 1024 px gilt 44 px für beide, und ein Zeiger- und
ein Fingerlauf schrieben sonst dieselbe Datei.

Ein voller Abnahmelauf sind damit **zwei** Aufrufe:

```
node tools/klickprobe/probe.mjs --breiten 390,1280 --bilder
node tools/klickprobe/probe.mjs --breiten 390,1280 --finger --bilder --behalten
```

Rückgabewert ≠ 0, sobald ein Weg sein Soll verfehlt **oder gar nicht gefahren
werden konnte**. Ein Weg, der nicht gefahren werden konnte, gilt als
**verfehlt**, nicht als übersprungen: Ein Prüfmittel, das sich selbst
überspringt, meldet Null, ohne gemessen zu haben (dieselbe Regel wie beim
Bilderlauf, F-P3-AH).

## Wie sie wächst

Jedes Arbeitspaket legt seine Wege in **eine eigene Datei** unter `wege/`
(`ap1.mjs`, `ap2.mjs`, …) und exportiert `wege`. Der Läufer kennt keinen
einzelnen Weg; er sammelt sie ein, fährt sie nacheinander und schreibt den
Bericht. Der Zuwachs je Paket ist damit eine Datei, und kein Paket muss
`probe.mjs` anfassen. Eine Datei ohne Wege ist ein **Abbruch** — sonst stünde
sie da und meldete stillschweigend „alles gefahren".

Ein Weg ist ein Objekt:

| Feld | Was |
|---|---|
| `name` | Kennung im Bericht, mit dem Paket als Präfix (`ap1-…`) |
| `paket`, `punkt` | Arbeitspaket und Prüfpunkt des Konzepts (`P-01`) |
| `rolle` | `demo` (Vorgabe) oder `admin` |
| `was`, `soll` | Klartext für den Bericht — `soll` ist die **Zahl**, nicht „richtig" |
| `fahren(k)` | fährt den Weg und liefert `{ist, ok, bemerkung}` |

Der Werkzeugkasten `k` bietet `seite` (Playwright-Page), `basis`, `kennung`
(aufgelöste Adressen des Bestands), `gehZu`, `tippe`, `haltenUndKlicken`,
`bild` und `attrappe`. Was drei Wege brauchen, gehört dorthin; was ein Weg
braucht, gehört in den Weg.

## Die Photon-Attrappe

`attrappe.mjs` beantwortet jede Anfrage an den Geocoder aus einem festen
Katalog erfundener Orte (Talwang, Westried, Steinach … — dieselben Namen wie
im Referenzbestand). Zwei Gründe, und beide wiegen:

1. **Der Prüfstand hat keinen Netzzugang** zu `photon.komoot.io`; die
   Egress-Sperre setzt Chromiums TLS-Handschlag zurück (F-P3-AC).
2. **Eine Prüfung braucht eine Zahl.** Der echte Dienst liefert je nach
   Bestand und Tagesform verschieden viele Treffer — damit wäre jeder Sollwert
   geraten. Die Attrappe hält dieselbe Obergrenze wie die echte Abfrage
   (`limit=6`).

Was sie **nicht** ersetzt: den Beweis, dass der echte Dienst antwortet. Der
steht auf der Prüfliste des Auftraggebers.

## Vier Fallen, die hier schon zugeschnappt sind

**Das Demo-Konto setzt sich alle 30 Minuten zurück — mitten im Lauf.**
`demo_zuruecksetzen()` erhöht dabei die Sitzungs-Epoche
(`DEMO_RESET_SEKUNDEN`, `server/demo_lib.php`), und `auth_guard.php` beendet
daraufhin jede offene Sitzung dieses Kontos, auch die der Probe. Ein voller
Lauf über zwei Breiten dauert länger als das Fenster. Am 09.09.2026 traf der
Reset einen Lauf in der Mitte: **64 von 76 Wegen erfüllt, 12 verfehlt** — mit
Meldungen wie „Kein Standort in der Liste" und „waitForSelector timeout", die
beide auf den Bestand zeigen. Der Bestand war in Ordnung.

Seit S9/AP6 hat `gehZu()` deshalb dieselbe **Sitzungswache** wie der
Bilderlauf: Landet die Seite auf `login.php`, ohne dass das gemeint war,
meldet sie sich **einmal** neu an und fährt die Adresse erneut an; gelingt auch
das nicht, wirft sie. Kontext und Seite bleiben stehen — Eingabeart, Attrappe
und Kachelsperre hängen daran.

Wer einen langen Lauf ganz aus dem Fenster halten will, stellt die Uhr vor dem
Start: `UPDATE app_state SET v = UNIX_TIMESTAMP() WHERE k =
'demo_letzter_reset';` — das verschiebt den nächsten Reset um volle 30 Minuten.


**Das Demo-Konto hat eine Mengenbremse, und Prüfläufe füllen sie.** 20
Anmeldungen je Fenster und Adresse, danach eine Stunde gesperrt (E-P1-20).
Vier Läufe mit je zwei Anmeldungen — `demo` und `admin` — haben sie voll
gemacht, und der fünfte endete mit „Anmeldung gescheitert": Das sieht aus wie
ein kaputter Prüfstand und war keiner. Deshalb meldet sich die Rolle jetzt
**erst an, wenn ein Weg sie verlangt** (AP1 braucht nur `demo`), und mehrere
Breiten laufen **in einem Prozess** — zwischen ihnen ändert sich nur die
Fenstergröße, wie im Bilderlauf. Der Fehlertext nennt die Bremse beim Namen.
Wer sie am Wegwerf-Prüfstand doch füllt: `DELETE FROM rate_limits WHERE topf
IN ('demo','demog','login','salt');`

**`fill('')` ist kein neutrales Leeren.** Playwright räumt ein Feld über die
Tastatur — an einem **bereits leeren** Feld kommt trotzdem ein `Delete` an.
Das Chipfeld der weiteren Rettungsmittel hört genau darauf: Entfernen im
leeren Feld nimmt den letzten Chip zurück (Web 7.0.0, gewollt). Der Lauf maß
daraufhin „1 → 1 Chips" und hielt die Übernahme für gescheitert — sie hatte
funktioniert, nur war vorher ein Chip verschwunden, den das Werkzeug selbst
gelöscht hatte. Geleert wird jetzt nur, was nicht schon leer ist.

**Ein Weg muss beide Fassungen kennen.** Die Aussage, um die es geht, heißt
„vorher 0 von 3, nachher 3 von 3". Ein Selektor, der nur die neue Fassung
findet, kann sie nicht belegen — er scheitert am alten Stand an sich selbst
und sagt nichts über die Anwendung. Die Wege sprechen deshalb alte **und**
neue Klassennamen an.

## Ausgabe

Unter `tools/klickprobe/ausgabe/` — steht in `.gitignore`.

| | |
|---|---|
| `bericht.md` | Zahlen und Befunde zum Mitnehmen ins Prüfdokument |
| `bericht.json` | dasselbe für Werkzeuge |
| `bild/<weg>.png` | nur mit `--bilder` |

## Grenzen

- **Nur Chromium.** WebKit (Safari, iOS) und Gecko stehen in dieser Umgebung
  nicht zur Verfügung; was nur dort aufﬁele, fällt hier nicht auf. Gerade
  `mousedown` ist auf iOS eine eigene Geschichte — er bleibt auf der
  Prüfliste des Auftraggebers.
- **Ein Zeiger ist kein Finger.** Die Probe fährt eine Maus. Ein Tipp mit
  Handschuhen an einem 390-px-Schirm ist etwas anderes und bleibt am Gerät zu
  prüfen.
- **Sie misst, was sie fährt.** Ein Weg, der nicht in `wege/` steht, ist nicht
  geprüft — auch wenn der Bericht am Ende „alle erfüllt" sagt. Die Zahl im
  Prüfdokument nennt deshalb immer **wie viele** Wege gefahren wurden.
- **`--knopf` ist eine Untergrenze, kein Sollmaß.** Eine Zeile mit zwei
  Textzeilen (Haupt- und Herkunftszeile) ist in beiden Bedienstufen rund
  51 px hoch — das Umschalten 44/36 ist an ihr nicht messbar. Wer die
  Bedienhöhe belegen will, misst eine **einzeilige** Zeile; die Wege tun das
  ausdrücklich.
