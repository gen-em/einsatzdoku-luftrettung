# Bedienprobe

Das einzige Prüfmittel, das Elemente **bedient** statt sie anzusehen.
**Anlass: PS-2** — bei gehaltener Maus kam der `click` nie, und auf jedem
Bild sah die Liste richtig aus.

## Aufruf

```bash
node tools/bedienprobe/probe.mjs            # --nur <weg> · --basis
```

Die Wege liegen in `wege/` und tragen heute noch die Namen der
Arbeitspakete, aus denen sie stammen (`ap1`, `p5a-ap8`). **E-PK-15 will sie
nach Seiten geordnet** — das steht aus; solange die Zuordnung fehlt, kann
`pruefablauf.json` nicht „diese Datei berührt, also diese Wege" sagen.
**Neue Wege heißen seit P5c/AP1 nach der Seite** (`betrieb_server.mjs`
für `betrieb_server.php`), damit E-PK-15 sie nicht noch einmal umbenennen
muss. `kasten.neuAnmelden()` meldet ab und wieder an — für Wege, die etwas
über das Ende einer Sitzung hinaus prüfen.

## Was es misst

50 Bedienwege: klicken, tippen, aus einer Trefferliste wählen, eine
Rückfrage bestätigen — und danach nachsehen, was in der **Datenbank**
steht. Jeder Weg nennt ein Soll und ein Ist.

## Was es braucht

Eine laufende Installation und Chromium. Der Demo-Reset wird vor dem Lauf
in die Zukunft gesetzt, damit er nicht mitten hinein fällt.

## Erwartete Zahl

**50 von 50 Wegen erfüllt, 0 verfehlt** (gemessen 23.09.2026 im Prüfstand
zu P5c/AP1; davor 48 von 48 am 22.09.2026 — die zwei neuen sind
`betrieb-server-ankuendigung` und `betrieb-server-rundmail`). Der Bericht
steht in `ausgabe/bericht.md`.

## Was es nicht kann

Nichts sehen — ob eine Seite richtig aussieht, sagt der Bilderlauf. Und es
fährt nur Chromium: Eine Bedienung, die in WebKit anders ausgeht, fällt
ihm nicht auf.
