# Referenzdatensatz (Phase P1)

Ein vollständiger, erfundener Beispielbestand für die Einsatzdokumentation —
und die Werkzeuge, ihn zu erzeugen, einzuspielen, zu exportieren und gegen
einen Vergleichsstand zu halten.

Er hat zwei Aufgaben, und die zweite ist der Grund für die erste:

1. **Demo-Konto** auf der Produktivinstallation — vorzeigbar und
   ausprobierbar (`demo@gen-em.org`).
2. **Regressionsreferenz** — kanonische Exporte, gegen die sich jede spätere
   Änderung halten lässt. Ein Beispielbestand, den niemand prüft, veraltet;
   einer, der bei jedem Lauf verglichen wird, nicht.

Das Projekt hat **keine automatisierten Tests**. Was es stattdessen haben
kann, ist ein Referenzzustand und ein Werkzeug, das jede Abweichung davon mit
einer Zahl benennt.

---

## Was hier liegt

| Ordner | Inhalt | eigene Anleitung |
|---|---|---|
| `quelldaten/` | die Wahrheit: 16 Diensttage, 87 Einsätze als JSON, dazu Schema und Prüfung | `quelldaten/FORMAT.md` |
| `generator/` | erzeugt daraus Ingest-Payloads, Formulardaten, CSV und GPX | `generator/LIESMICH.md` |
| `einspielen/` | spielt alles über die **regulären** Wege ein (kein SQL) | `einspielen/LIESMICH.md` |
| `browser/` | was es nur im Browser gibt: CSV-Import, P-07, Exporte, Demo-Abnahme | `browser/LIESMICH.md` |
| `referenz/` | die eingecheckten Referenz-Exporte (CSV-Archiv und `.edbak`) | — |
| `vergleich/` | Vergleichswerkzeug und Kreislauftests | `vergleich/LIESMICH.md` |
| `fixture/` | erzeugt `server/demo/fixture.json.gz` für die Demo-Funktion | — |
| `docs/konzepte/erledigt/Konzept-P1.md` (seit Rahmenplan Fassung 16 dort, nicht mehr in diesem Ordner) | Konzept, Entscheidungen, Abdeckungsmatrix, Prüfprotokoll, Fehlerfunde | — |

**Nichts davon wird ausgeliefert.** `tools/` ist vom Deploy ausgenommen; nur
`server/demo/fixture.json.gz` geht mit, und die entsteht hier.

---

## Der Bestand in Zahlen

| | |
|---|---|
| Diensttage | 16 (15 aktiv, 1 im Papierkorb) |
| Einsätze | 87 (82 aktiv, 5 im Papierkorb) |
| Ruhesegmente | 100 (95 aktiv) |
| Spurpunkte | 55 861 |
| Stammdaten | 2 Standorte, 3 Rettungsmittel, 15 Besatzungs-Vorbelegungen, 8 Zielkliniken, 3 Bereitschaften, 8 weitere Rettungsmittel |
| Geräte | 2 — dazu entsteht beim Nachtragen und beim Import das virtuelle „Manuelle Einträge" (`manual-<konto>`), also 3 Zeilen in `devices` |

Die Verteilung ist ungleich, mit Häufungen — acht luft- und acht
bodengebundene Diensttage, im Schnitt gut fünf Einsätze je Tag.

**Alle Namen sind erfunden.** Keine realen Rufnamen, keine „Christoph"-Kennung,
keine echten Orte. `quelldaten/pruefen.py` prüft das bei jedem Lauf gegen eine
Liste — und zwar nur über die **Daten**, nicht über die Erklärtexte daneben.

---

## Die drei Läufe

### 1. Bestand erzeugen und prüfen

```
python3 quelldaten/pruefen.py       # Schema, Sachlogik, Abdeckungsmatrix
python3 generator/erzeugen.py       # Payloads, Formulardaten, CSV, GPX
python3 generator/pruefen.py        # Vertragsgrenzen, Folge, Krypto, Spur, CSV
```

Beide Prüfungen nennen ihre Zahl. Eine Prüfung ohne Zahl ist keine.

### 2. Einspielen

```
sh   einspielen/lokal_starten.sh                     # MariaDB, PHP, TLS davor
python3 einspielen/einspielen.py --stufen konto
node einspielen/passwort_setzen.mjs '<Link>' nadokudemo0815
python3 einspielen/einspielen.py --stufen stammdaten,geraet,ingest,zuordnen,nachtragen,manuell,papierkorb,sperrliste
node browser/csv_import.mjs                          # die vier CSV-Einsätze
```

Dauer rund vier Minuten. **Alles über die regulären Wege** — `ingest.php`,
`api/day.php`, `einsatz_form.php`, die Weboberfläche. Keine Zeile per SQL.

### 3. Exportieren und vergleichen

```
node browser/referenz_export.mjs                     # beide Referenzdateien
python3 vergleich/vergleichen.py --art csv   a.zip b.zip
python3 vergleich/vergleichen.py --art edbak a.edbak b.edbak --passwort …
python3 vergleich/kreislauf.py --art edbak --frisch  # ganzer Umlauf
```

---

## Regressionslauf — die Kurzform

Nach einer Änderung an der Anwendung:

1. `python3 vergleich/kreislauf.py --art edbak --frisch`
2. `python3 vergleich/kreislauf.py --art csv --frisch`
3. Bericht lesen: **unerklärte Abweichungen müssen null sein**; erwartete
   stehen mit Begründung daneben.

Läuft der Vergleich gegen die **Demo-Installation**, vorher zurücksetzen
(Adminbereich oder den 30-Minuten-Reset abwarten). Sonst misst der Vergleich
Besucheränderungen und nennt sie Regression.

---

## Wenn der lokale Bestand verlorengeht

Er ist vollständig reproduzierbar — Abschnitt „Die drei Läufe" von vorn. Das
ist keine Notfallanleitung, sondern der reguläre Weg; er wurde in dieser Phase
dreimal gefahren, zweimal davon ungeplant.

**Was dabei nicht identisch wiederkommt:** interne Kennungen, `created_at` und
die **Gerätekennungen** (`dev-…`). Nur die internen Kennungen nimmt die
Normalisierung weg; `created_at` wird seit Web 8.0.0 verglichen (es kommt beim
Einspielen wieder zurück), und die Gerätekennungen stehen im Backup
unter `days[].refs[].device_id`. Wer den Referenzstand neu aufbaut, erzeugt
deshalb auch die Referenz-Exporte und die Fixture neu.

**Und die Reihenfolge zählt:** Wer eine Quelldatei ändert, fährt die betroffene
Einspielstufe erneut, **bevor** er exportiert. Der Datensatz ist
deterministisch — aber nur, wenn man ihn auch erzeugt. In dieser Phase ist
genau das einmal schiefgegangen (Fund F-P1-J).

---

## Demo-Fixture

```
php fixture/erzeugen.php [email] [ziel.json.gz]
```

Erzeugt `server/demo/fixture.json.gz` aus dem Referenzkonto: Konto- und
Schlüsselmaterial, die **echten** Geräte und den Bestand **mit** Papierkorb
(Format 2). Das virtuelle Gerät „Manuelle Einträge" bleibt draußen — es
trägt die Kontonummer im Namen und entsteht im Zielkonto bei Bedarf von
selbst (seit Web 8.0.1; vorher brach das Anlegen des Demo-Kontos ab, sobald
eine Installation beide Bestände führte). Das
Nachlauf-Drehbuch ist mit Web 8.0.0 entfallen — das Backup führt gelöschte
Einträge jetzt selbst, und das Einspielen bringt sie als Papierkorb zurück.
Die Mechanik steht in `docs/Technik.md` 4.99a.

Danach im Adminbereich unter **Demo-Konto** anlegen oder zurücksetzen.

---

## Zugangsdaten

| | |
|---|---|
| Demo-Konto | `demo@gen-em.org` / `nadokudemo0815` |
| Backup-Passwort der Referenz-`.edbak` | `nadokudemo0815` |

Beide sind **planmäßig öffentlich** und stehen auch im Handbuch. Sie schützen
nichts: Der Bestand ist erfunden, und das Konto setzt sich alle 30 Minuten
zurück.

---

## Was dieser Datensatz absichtlich enthält

Er soll nicht schön sein, sondern **vollständig**. Die Abdeckungsmatrix in
`docs/konzepte/erledigt/Konzept-P1.md` Abschnitt 5 führt 78 Zeilen; jede ist mindestens einem Einsatz
zugewiesen. Darunter:

- drei Herkünfte (Uhr, Formular, Import)
- ein Dienst über Mitternacht, zwei Dienste an einem Kalendertag, ein
  Diensttag ohne Einsatz
- alle Phasen 2–9, Mehrfachphasen, unvollständige Phasen, ein nicht
  abgeschlossener Einsatz
- alle **neun** speicherbaren Reanimations-Ereignisarten (nicht zehn — siehe
  Fund F-P1-F)
- alle vier Abfahrtortregeln, Winde nur am windenfähigen Rettungsmittel
- MEZ **und** MESZ einschließlich der Zeitumstellungen 2026
- Sonderzeichen, Formel-Anfangszeichen und **Angriffswerte** in geschützten
  Freitextfeldern (R20)

Die Angriffswerte sind kein Scherz: Sie haben in dieser Phase ein echtes
Cross-Site-Scripting gefunden (F-P1-I, ausgeliefert als Web 7.2.1).
