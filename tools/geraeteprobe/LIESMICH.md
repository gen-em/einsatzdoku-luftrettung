# Geräteprobe — was ein Gerät beim Koppeln über sich sagt

```
php tools/geraeteprobe/probe.php
```

Rückgabewert `0` = alle Erwartungen erfüllt, `1` = mindestens eine nicht.
Braucht **keine Datenbank**, keinen Webserver und kein Gerät.

## Wozu

Die Kopplung nimmt seit Web 12.9.0 einen Block `geraet` entgegen
(`docs/JSON-Vertrag.md`, Abschnitt 1a; R42). Er ist der einzige Teil von S6
mit echter Logik, und er hat drei Eigenschaften, die zusammen unangenehm sind:

1. **Er ist freiwillig.** Fehlt er, ist er halb oder ist er Unsinn, muss die
   Kopplung trotzdem gelingen. Am anderen Ende steht jemand mit einer Uhr in
   der Hand; ein 500er wegen einer Statistikangabe wäre absurd.
2. **Er kommt in zwei Formen.** Die Garmin-Uhr sendet ihre Teilenummer
   (`006-B4261-00`) — sie kennt ihren Modellnamen nicht. Das Handy sendet
   Hersteller und Modell (E-S4-28). Beide fallen auf dieselben drei Spalten
   von `devices`.
3. **Er ist eine Selbstauskunft.** Was ankommt, ist ungeprüft: Es stammt von
   einem Gerät, das sich beim Server erst noch vorstellt.

Im Browser lässt sich das nicht prüfen — es gibt keine Oberfläche dafür, und
eine echte Kopplung braucht eine Uhr. Diese Probe prüft, was ohne Gerät
prüfbar ist.

## Was sie prüft

| Gruppe | Beispiele |
|---|---|
| Uhr-Form | aufgelöste und unbekannte Teilenummer, Kleinschreibung, fehlender Block |
| Handy-Form | E-S4-28 vollständig, Hersteller schon im Modellnamen (`Xiaomi 14`), nur Hersteller, gar nichts |
| Abwehr | erfundene Geräteart, verschachtelte Werte, `true`, Zahlen, leere Zeichenketten, Steuerzeichen, Überlänge, Umlaute an der Schnittkante |
| Vorrang | Ein Gerät, das beide Formen zugleich schickt |
| Anzeige | die sieben Fälle von `geraet_bezeichnung()` und die vier von `geraet_vorgabename()` |

**Die Tabelle schlägt die Selbstauskunft** — ein Fall, der leicht übersehen
wird: Die Uhr-App sendet `art` fest als `"uhr"`, weil eine Connect-IQ-App nur
auf Garmin-Geräten läuft; Uhr und Radcomputer kann sie nicht unterscheiden.
Die Gerätedateien können es. Ein Edge, der sich „uhr" nennt, würde die
Statistik sonst still verfälschen.

## Die eigene Modelltabelle

Die Probe **setzt `GERAETE_MODELLE` selbst**, bevor sie `geraete_lib.php`
lädt. Ohne das liefe sie gegen den jeweils ausgelieferten Bestand — und was
heute grün ist, wäre nach dem nächsten Lauf von `tools/geraetemodelle/` rot,
ohne dass sich am Code etwas geändert hätte.

Drei ihrer vier Einträge sind **belegt** (`docs/Geraete-Eingabe.md` nennt
Teilenummer und gemessene Displaymaße für fenix 6 Pro, Forerunner 945 und
Venu 3s). Der vierte ist ein **erfundener** Radcomputer und im Code als
solcher gekennzeichnet: Er prüft den Vorrang der Tabelle bei der Geräteart.
Die Teilenummer eines echten Edge ist nirgends im Repositorium belegt, und
eine erfundene als belegt auszugeben wäre schlechter, als sie zu benennen.

## Was sie NICHT prüft

- **Ob `pair.php` das Ergebnis in die Spalten schreibt.** Das braucht eine
  Datenbank. Weg: lokale Installation, koppeln, `SELECT geraet_art,
  geraet_modell, geraet_teil FROM devices`.
- **Ob die erzeugte Modelltabelle richtig ist.** Sie entsteht aus den
  Gerätedateien der Uhr-Plattform (`tools/geraetemodelle/`); ihre Richtigkeit
  hängt an denen, nicht an dieser Probe.
- **Ob eine echte Uhr sendet, was der Vertrag sagt.** Dafür gibt es nur die
  Uhr. Die Uhr-Seite steht in `watch/source/Pair.mc`, die Handy-Seite in
  `android/handy/src/main/java/org/genem/nadoku/handy/kopplung/Geraeteangabe.kt`
  — beide haben eigene Prüfwege.
