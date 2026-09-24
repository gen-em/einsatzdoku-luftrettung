# Bedienprobe

Das einzige Prüfmittel, das Elemente **bedient** statt sie anzusehen.
**Anlass: Nr. 102** — bei gehaltener Maus kam der `click` nie, und auf jedem
Bild sah die Liste richtig aus.

## Aufruf

```bash
node tools/bedienprobe/probe.mjs            # --nur <weg> · --basis
```

Die Wege liegen in `wege/` und tragen heute noch die Namen der Arbeitspakete, aus denen sie stammen (`ap1`, `p5a-ap8`).
**E-PK-15 will sie nach Seiten geordnet** — das steht aus; solange die Zuordnung fehlt, kann `pruefablauf.json` nicht „diese Datei berührt, also diese Wege" sagen. Neue Wege heißen nach der Seite (`betrieb_server.mjs`).

## Was es misst

55 Bedienwege: klicken, tippen, aus einer Trefferliste wählen, eine
Rückfrage bestätigen — und danach nachsehen, was in der **Datenbank**
steht. Jeder Weg nennt ein Soll und ein Ist.

## Was es braucht

Eine laufende Installation und Chromium. Der Demo-Reset wird vor dem Lauf in die Zukunft gesetzt, damit er nicht mitten hinein fällt.
Wege, die weder das Prüfkonto noch das Demo-Konto brauchen können — den Zweitfaktor einrichten —, legen mit `probekonto.mjs` ein eigenes Konto an und räumen es wieder ab (das Modul liegt neben `wege/`, weil der Läufer dort jede Datei als Wegdatei lädt).
Braucht ein Weg einen Inhaltsschlüssel — das Paar des Rückwegs verpackt seinen privaten Teil damit (Konzept RW) —, legt `passwortSetzen()` das Konto über die Einladung an und setzt das Passwort im Browser auf `pw_handling.php`.
Den QR-Code liest `vendor/jsQR.js` (1.4.0, Apache-2.0, nur Prüfwerkzeug; Nr. 298).

## Erwartete Zahl

**55 von 55 Wegen erfüllt, 0 verfehlt** — gezählt aus `wege/` am 24.09.2026 (RW-02: `einstellungen-profil-rueckweg`); die Messung steht im Prüfbericht des Commits `RW-03`, der Bericht in `ausgabe/bericht.md`.

## Was es nicht kann

Nichts sehen — ob eine Seite richtig aussieht, sagt der Bilderlauf. Und es
fährt nur Chromium: Eine Bedienung, die in WebKit anders ausgeht, fällt
ihm nicht auf.
