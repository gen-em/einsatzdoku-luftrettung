# Linkprobe — zeigt jeder Verweis auf einen Parameter, den die Zielseite liest?

```bash
python3 tools/linkprobe/probe.py
python3 tools/linkprobe/probe.py --ausfuehrlich   # auch die dynamisch gelesenen
```

Rückgabewert 0 = keine unbekannte Abweichung und keine tote Zeile in
`ausnahmen.md`, 1 = mindestens eines von beidem. Keine laufende
Installation nötig, kein PHP — nur Python 3 und der Quelltext.

## Wozu — der Fehler, den niemand misst

Ein Verweis `xyz.php?a=1` auf eine Seite, die `$_GET['b']` liest, fällt durch
jedes vorhandene Prüfmittel: Der Bilderlauf **fotografiert** Seiten und klickt
keine Knöpfe, die Wortliste zählt Wörter, die Vollständigkeitsprüfung sieht
das Stylesheet an, und automatisierte Tests gibt es für den Webteil nicht
(`CLAUDE.md` 6). Er fällt erst im Betrieb auf — und dort auch nur dann, wenn
jemand genau diesen Knopf drückt.

Genau so ist **Backlog Nr. 148** entstanden. Die Überschneidungswarnung der
Startseite (R57) verwies auf `diensttag_zusammenfuehren.php?ziel=`, die Seite
liest `$_GET['d']`. `$zielId` blieb 0, `dt_laden()` lieferte null, die Seite
endete auf `ui_abbruch(404, 'Diensttag nicht gefunden.')` — in genau dem Fall,
für den die Warnung gebaut ist. Zwei andere Verweise auf dieselbe Seite, keine
vierzig Zeilen entfernt, benutzten `?d=` richtig. Der Fund kam vom
Auftraggeber, nicht von einer Maschine.

Gegengeprobt: Mit dem alten `?ziel=` meldet die Probe

```
server/index.php:181  diensttag_zusammenfuehren.php?ziel=  [FEHLT] die Seite liest: d, q
```

## Was sie misst

Alle Zeichenketten der Form `<seite>.php?…` unter `server/` — in PHP **und**
JavaScript, auch in zusammengesetzten Adressen —, gehalten gegen die
Parameter, die die Zielseite tatsächlich liest: `$_GET[…]`, `$_REQUEST[…]`,
`filter_input(INPUT_GET, …)`.

**Jeder** Parameter der Adresse, nicht nur der erste: `?t=rettungsmittel&ev=`
sind zwei. Der erste Entwurf las nur bis zum ersten `=` und übersah damit acht
Verweise; er hätte trotzdem eine runde Zahl gemeldet.

| Ergebnis | Bedeutung |
|---|---|
| **FEHLT** | Die Zielseite liest diesen Namen nicht — ein Befund. Die Meldung nennt, welche Namen sie stattdessen liest. |
| **ZIEL WEG** | Die Zieldatei gibt es nicht — ein Befund. |
| *dynamisch* | Die Zielseite liest ihre Parameter über eine Variable (`$_GET[$name]`, wie `konten_param()` in `admin_users.php` oder `$pickIn()` in `einstellungen.php`). Statisch nicht entscheidbar; wird **gezählt und genannt**, nicht gemeldet. Heute zehn Verweise. |

Fremdes wird übergangen: `vendor`, `fonts`, `demo`. Zeilen, die mit `*`, `//`
oder `#` beginnen, ebenso — dort stehen Beispieladressen in Kommentaren, und
die sind Erklärung, kein Verweis. Ein Kommentar **am Ende** einer Codezeile
wird mitgelesen; das ist die sichere Richtung.

## Die zwei Listen in `ausnahmen.md`

**1. Ausnahmen** — der Abgleich ist aus einem benannten Grund nicht zu führen.
Heute leer, und das ist der gewünschte Zustand.

**2. Bekannte Abweichungen** — echte Fehler, die noch nicht behoben sind, je
mit **Backlog-Nummer**. Ohne Nummer gehört nichts hierher. Das ist der
Unterschied zum Ausblenden: Der Fund bleibt sichtbar, er wird bei jedem Lauf
genannt, und er hat einen Ort, an dem über ihn entschieden wird. Heute steht
dort **eine** Zeile: `index.php?day=` aus `import_ui.js` (Nr. 151).

**Eine tote Zeile macht den Lauf rot.** Steht in einer der beiden Tabellen ein
Verweis, den es nicht mehr gibt, meldet die Probe das und gibt 1 zurück. Bei
einer behobenen Abweichung ist genau das der Punkt, an dem ihre Zeile hier
verschwinden muss — dieselbe Regel wie die „null ungenutzten Ausnahmen" der
Wortliste.

## Was sie NICHT kann — und warum das zu sagen ist

- **Sie prüft Namen, nicht Werte.** Ein `?d=<Kalendertag>` an einer Seite, die
  unter `d` eine *Kennung* erwartet, ist für sie in Ordnung — er ist es nicht.
  Genau darin liegt der zweite Teil von Nr. 151: Dort stimmt nicht nur der
  Name nicht, sondern auch die Form des Werts.
- **Ein Verweis, dessen Ziel erst zur Laufzeit entsteht**, taucht hier nicht
  auf. Es gibt einen: `admin_stammdaten.php:571` baut
  `$seite . '&ev=' . $vid` — die Zielseite steht in einer Variablen, und die
  Probe weiß dann nicht, wogegen sie halten soll. Von Hand nachgesehen: Die
  Seite liest `ev` über `$pickNach()`, der Verweis stimmt. Käme ein solcher
  Fall neu dazu, fände die Probe ihn nicht — deshalb steht er hier und nicht
  in einer Ausnahmeliste, die ihn verschwinden ließe.
- **Sie ersetzt keinen Klick.** Ob der Knopf überhaupt sichtbar ist, ob die
  Seite mit dem richtigen Parameter auch das Richtige zeigt, und ob der Weg
  dahinter trägt, sagt der Browser.
- **Sie sieht nur `server/`.** Was die Uhr oder die Android-Apps aufrufen,
  steht im JSON-Vertrag und wird dort geprüft.

## Wann sie läuft

Bei jeder Änderung, die einen Verweis anfasst oder einen Parameter einer Seite
umbenennt — und im Prüflauf am Ende eines Arbeitspakets, zusammen mit
Wortliste, Vollständigkeit und Bilderlauf (`CLAUDE.md` 6). Sie ist billig:
ein Lauf über 99 Zielseiten und 132 Verweise dauert den Bruchteil einer
Sekunde.
