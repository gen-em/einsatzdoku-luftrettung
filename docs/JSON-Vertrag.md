# JSON-Vertrag Uhr → Server

**Version:** 1.2 — Phase 10 berichtigt, führende Listen und Grenzen festgelegt
**Endpunkt:** `POST https://<host>/ingest.php`
**Content-Type:** `application/json`

> **Dieses Dokument ist die führende Quelle.** Wer einen neuen Client baut,
> implementiert gegen diesen Text. Wo Uhr-App und Server dieselbe Liste oder
> denselben Wertebereich doppelt führen, gilt im Zweifel, was hier steht — und
> die Abweichung ist ein Fehler in der Umsetzung, nicht im Vertrag.

> **Geltungsbereich:** Dieses Dokument beschreibt ausschließlich den Vertrag
> zwischen Uhr und Server (`ingest.php`). Die JSON-Endpunkte, die die
> Weboberfläche im Browser benutzt (`server/api/*.php`, darunter
> `import_commit.php`), gehören nicht dazu und sind in `Technik.md`,
> Abschnitt 4 beschrieben.

## 0. Stand der Durchsetzung

Ein Vertrag, der etwas zusichert, was der Code nicht einhält, ist schlimmer als
gar keiner — wer danach implementiert, verlässt sich auf eine Zusage, die nicht
gilt. Deshalb steht hier offen, welche Regeln dieses Dokuments der Server heute
schon durchsetzt und welche noch nicht.

| Regel | Stand |
|---|---|
| Phasennummern 2–9 (Abschnitt 7) | durchgesetzt |
| Mehrfache Phaseneinträge bleiben erhalten (3) | durchgesetzt |
| Reanimationsarten gegen die Liste (3.3) | durchgesetzt |
| Idempotenz über Gerät + `client_ref` (2) | durchgesetzt |
| Präfixe der Client-Kennung (8) | beschrieben, vom Server bewusst nicht geprüft |
| Kalendertag muss existieren (3.2) | durchgesetzt |
| Koordinatenbereiche, Mengenbegrenzungen (3.2) | durchgesetzt |
| Leere oder zu kurze Liste löscht nichts (3.1) | durchgesetzt |
| Antwortfeld `rejected` (5) | durchgesetzt |
| Antwortfelder `kept_*` (5) | durchgesetzt |
| Zufallsanteil in der Client-Kennung (8) | **noch nicht** in der Uhr-App |

Die als „noch nicht" gekennzeichneten Punkte beschreiben den **Zielzustand**
und werden in den folgenden Auslieferungen eingelöst. Bis dahin gilt für einen
Client: Er darf sich auf die Regeln verlassen, wenn er sie **einhält**, aber
nicht darauf, dass ein Verstoß gemeldet wird.

Diese Tabelle verschwindet, sobald alle Zeilen „durchgesetzt" lauten.

## 1. Authentifizierung (jede Anfrage)

| Header | Inhalt |
|---|---|
| `X-Device-Id` | Öffentliche Geräte-ID (vom Admin beim Anlegen des Geräts vergeben) |
| `X-Api-Key` | Geheimer Geräteschlüssel (Klartext nur auf der Uhr; Server speichert Hash) |

Antwort bei ungültigem Schlüssel: `401 {"error":"auth"}`.

## 2. Grundprinzipien

- **Zeitstempel:** ISO 8601 in UTC mit `Z`-Suffix, Sekundenauflösung (`2026-07-16T08:31:05Z`). Track-Punkte nutzen kompakte Unix-Epochen (Sekunden, UTC).
- **Idempotenz:** Jeder Einsatz und jedes Ruhe-Segment trägt eine von der Uhr erzeugte `client_ref` (eindeutig pro Gerät). Wiederholtes Senden derselben Daten ist unschädlich.
- **Inkrementeller Track:** Track-Punkte werden mit fortlaufender Sequenznummer gesendet. Die Uhr sendet ab `seq_from`; der Server ignoriert bereits bekannte Sequenzen und antwortet mit `next_seq`, ab dem die Uhr weitersenden soll. Nach bestätigtem Empfang darf die Uhr ihren lokalen Puffer bis `next_seq` leeren.
- **Flugtag:** Feld `day` = Datum des Dienstbeginns (Format `YYYY-MM-DD`); die Uhr bestimmt es einmal bei „Dienst beginnen" und verwendet es für alle Uploads des Tages.
- **Nachzügler:** Bei fehlender Verbindung puffert die Uhr und sendet später identisch nach — keine Sonderfelder nötig.

## 3. Nachricht `mission` (Einsatz)

Gesendet beim **Abschluss des Einsatzes** (`final: true`) sowie optional
zwischendurch als Teil-Upload (`final: false`).

Der Abschluss ist **keine Phase**. Er wird über das Kennzeichen `final: true`
und den Endzeitpunkt `ended_at` übertragen — beides zusammen. Frühere Fassungen
dieses Dokuments beschrieben dafür eine „Phase 10"; die gibt es nicht mehr
(siehe Abschnitt 7).

```json
{
  "kind": "mission",
  "client_ref": "m-20260716-0831-a3",
  "day": "2026-07-16",
  "started_at": "2026-07-16T08:31:05Z",
  "ended_at": "2026-07-16T09:12:40Z",
  "distance_m": 148230,
  "ascent_m": 410,
  "final": true,
  "phases": [
    { "phase": 2, "at": "2026-07-16T08:31:05Z", "lat": 47.7261, "lon": 10.3186 },
    { "phase": 3, "at": "2026-07-16T08:36:22Z", "lat": 47.7259, "lon": 10.3190 },
    { "phase": 4, "at": "2026-07-16T08:51:02Z", "lat": 47.5601, "lon": 10.7002 }
  ],
  "resus_sessions": [
    {
      "started_at": "2026-07-16T08:55:10Z",
      "events": [
        { "type": "rhythmuskontrolle", "at": "2026-07-16T08:57:10Z" },
        { "type": "adrenalin",         "at": "2026-07-16T08:58:02Z" },
        { "type": "defibrillation",    "at": "2026-07-16T08:59:15Z" },
        { "type": "rosc",              "at": "2026-07-16T09:06:40Z" }
      ]
    }
  ],
  "track": {
    "seq_from": 0,
    "points": [
      [47.72611, 10.31862, 712.0, 1784279465],
      [47.72640, 10.31901, 713.5, 1784279475]
    ]
  }
}
```

Regeln:

- `ended_at` ist `null`, solange `final: false`.
- `phases[]` enthält **alle bisher gesetzten** Phasen-Zeitstempel (vollständige Liste, kein Delta) — der Server ersetzt die Phasenliste des Einsatzes bei jedem Upload. **Mehrfache Einträge derselben Phasennummer sind erlaubt** und bleiben erhalten: Eine erneut gesetzte Phase ist eine Korrektur und damit eine Information. Kein Client und kein Schreibweg darf sie entdoppeln.
- `resus_sessions` ist eine **Liste** — jede Reanimation des Einsatzes ist ein Eintrag (mehrere pro Einsatz möglich; „Aufzeichnung beenden" auf der Uhr schließt eine Sitzung, ein erneuter Start eröffnet die nächste). Vollständige Liste, Server ersetzt. Das ältere Einzelobjekt `resus` wird aus Kompatibilität weiterhin akzeptiert.
- `track.points`: Array aus `[lat, lon, ele_m, epoch_s]`. `ele_m` darf `null` sein. Die Sequenznummer des i-ten Punkts ist `seq_from + i`. Muss eine **Liste** sein; ein Objekt mit den Schlüsseln `"0"`, `"1"` … wird abgelehnt.
- `distance_m` / `ascent_m` werden von der Uhr fortlaufend berechnet und beim `final`-Upload als verbindlich übernommen.

### 3.1 Fehlende und leere Listen — der Unterschied zählt

Für `phases[]` und `resus_sessions[]` gilt:

| Zustand | Bedeutung | Verhalten des Servers |
|---|---|---|
| Schlüssel **fehlt** | „dazu sage ich nichts" | Vorhandene Daten bleiben unverändert |
| Liste ist **leer** | „es gibt keine" | Vorhandene Daten bleiben erhalten; der Server vermerkt es in der Antwort |
| Liste ist **kürzer** als der vorhandene Stand | vermutlich unvollständig aufgebaut | Vorhandene Daten bleiben erhalten; der Server vermerkt es in der Antwort |
| Liste ist **gleich lang oder länger** | vollständiger Stand | Ersetzt den vorhandenen Stand |

Eine leere Liste löscht also **nichts**. Der Grund ist der Weg dorthin: Eine
leere Liste entsteht viel wahrscheinlicher durch einen Fehler beim Aufbau der
Nachricht als durch die Absicht, eine bereits dokumentierte Reanimation wieder
zu entfernen. Wer wirklich löschen will, tut das in der Weboberfläche.

**Dieselbe Überlegung gilt für eine zu kurze Liste.** Eine halb aufgebaute
Nachricht ist derselbe Fehler wie eine leere, nur unauffällig: Sie kommt mit
drei Phasen an, wo acht stehen, und der Verlust fällt niemandem auf. Der Server
zählt deshalb, was er hätte, und übergeht jede Liste, die weniger enthält.

Das ist für einen Client folgenlos, der sich an diesen Vertrag hält: Beide
Listen wachsen nur. Eine erneut gesetzte Phase ist eine Korrektur und damit ein
**zusätzlicher** Eintrag (Abschnitt 3), und eine abgeschlossene Reanimation
verschwindet nicht wieder. Wer eine kürzere Liste sendet, hat sie nicht
vollständig aufgebaut.

Gezählt wird **nach der Prüfung**: Zehn Einträge, von denen neun gegen
Abschnitt 3.2 verstoßen, sind ein Eintrag. Die verworfenen erscheinen wie
gewohnt in `rejected`.

`track.points[]` ist von dieser Regel nicht berührt: Spurpunkte werden
**angehängt**, nie ersetzt (Abschnitt 2). Eine leere Punktliste speichert
nichts und löscht auch nichts.

Der Server nennt einen übergangenen Fall in der Antwort (`kept_*`, siehe
Abschnitt 5), damit er auf der Uhr auffällt statt still zu verschwinden.

### 3.2 Grenzen und Mengen

Werte außerhalb dieser Grenzen werden **je Feld verworfen und gemeldet** — der
gesamte Upload scheitert daran nicht.

| Feld | Grenze |
|---|---|
| `phases[].phase` | ganze Zahl 2 bis 9 |
| `phases[]` | höchstens 500 Einträge je Einsatz |
| `lat` | −90 bis +90 |
| `lon` | −180 bis +180 |
| `resus_sessions[]` | höchstens 20 je Einsatz |
| `resus_sessions[].events[]` | höchstens 200 je Sitzung |
| `track.points[]` | höchstens 2000 je Anfrage (Richtwert 500, siehe Abschnitt 6) |
| `client_ref` | höchstens 64 Zeichen |
| `day` | `YYYY-MM-DD`, muss ein **existierender Kalendertag** sein |
| Zeitstempel | `YYYY-MM-DDThh:mm[:ss]Z`, Kalendertag muss existieren |

Zum Kalendertag: Ein unmöglicher Tag wie der 30. Februar wird abgelehnt, nicht
stillschweigend auf den 2. März verschoben.

### 3.3 Reanimationsarten

`events[].type` ∈ `zugang`, `beginn`, `adrenalin`, `rhythmuskontrolle`,
`defibrillation`, `intubation`, `amiodaron`, `sonographie`, `rosc`, `tod`.

**Diese Liste ist die führende.** Sie liegt zusätzlich als Konstante im Server
(`RESUS_LABELS` in `db.php`) und in der Uhr-App (`Const.mc`) vor und wird von
Hand synchron gehalten. Eine neue Art ist deshalb an *drei* Stellen zu
ergänzen — und diese hier zuerst, damit nachvollziehbar bleibt, was gilt.

Die Uhr-App führt bewusst eine **Teilmenge**: `beginn` kennt sie nicht, weil
der Beginn einer Reanimation dort über `resus_sessions[].started_at` übertragen
wird. Ein Client *darf* weniger Arten erzeugen als der Vertrag kennt; er darf
keine erzeugen, die nicht darin stehen.

Unbekannte Arten werden vom Server verworfen und gemeldet; ein freier Text
ließe sich in der Weboberfläche später nicht darstellen.

## 4. Nachricht `rest_segment` (Ruhe-Track-Segment)

Periodisch (z. B. stündlich bzw. bei Verbindung) und beim Beenden des Segments (Einsatzbeginn oder „Einsatztag beenden") mit `final: true`.

```json
{
  "kind": "rest_segment",
  "client_ref": "r-20260716-0700-01",
  "day": "2026-07-16",
  "started_at": "2026-07-16T05:02:11Z",
  "ended_at": null,
  "final": false,
  "track": {
    "seq_from": 240,
    "points": [
      [47.72611, 10.31862, 712.0, 1784275331]
    ]
  }
}
```

## 5. Antworten des Servers

Erfolg (`200`):

```json
{ "ok": true, "id": 17, "stored_points": 212, "next_seq": 452 }
```

- `id`: Server-ID des Einsatzes/Segments.
- `next_seq`: erste noch nicht gespeicherte Sequenznummer → Uhr sendet beim nächsten Mal `seq_from = next_seq` und darf lokal alles davor verwerfen.

Zusätzlich können auftreten:

| Feld | Bedeutung |
|---|---|
| `rejected` | verworfene Einzelwerte, nach Ursache gezählt (z. B. `phases.phase: ausserhalb von 2…9` → 2) |
| `kept_phases` | die gesendete Phasenliste wurde übergangen (leer oder kürzer als der vorhandene Stand); der Wert nennt die **Anzahl der behaltenen** Einträge |
| `kept_resus` | dasselbe für die Reanimationssitzungen |

Ein `ok: true` mit gefülltem `rejected` oder einem `kept_*` bedeutet: Der
Upload ist angekommen, aber **nicht vollständig übernommen**. Die Uhr sollte
das anzeigen und nicht als reinen Erfolg behandeln. Die beiden Fälle
unterscheiden sich: `rejected` nennt einzelne verworfene Werte, ein `kept_*`
sagt, dass eine ganze Liste übergangen wurde und der Serverstand unverändert
blieb.

Fehler:

| Code | Body | Bedeutung / Verhalten der Uhr |
|---|---|---|
| 400 | `{"error":"payload"}` | Nachricht fehlerhaft — nicht wiederholen, lokal als fehlerhaft markieren |
| 401 | `{"error":"auth"}` | Schlüssel ungültig — Upload pausieren, Hinweis anzeigen |
| 405 | `{"error":"method"}` | Falsche HTTP-Methode |
| 413 | `{"error":"too_large"}` | Chunk zu groß — Uhr halbiert die Chunk-Größe und wiederholt |
| 5xx | — | Später unverändert erneut versuchen (Backoff) |

## 6. Chunk-Größen

- Richtwert: **max. 500 Track-Punkte pro Anfrage** (Connect-IQ-Payload-Limit und Mobilfunk-Robustheit). Größere Bestände werden in mehreren aufeinanderfolgenden Anfragen gesendet (`seq_from` fortlaufend).
- Serverseitige Obergrenze: 512 KB Body (`413` bei Überschreitung).

## 7. Phasen-Nummern (Referenz)

`1` Frei · `2` Alarmierung · `3` Abflug · `4` Ankunft Einsatzort ·
`5` Ankunft PatientIn · `6` Transportbeginn · `7` Landung Krankenhaus ·
`8` Übergabezeit · `9` Endzeit des Einsatzes.

**Übertragen werden ausschließlich die Nummern 2 bis 9.**

- **Phase 1 („Frei")** ist ein Anzeigezustand der Uhr und erzeugt keinen
  Eintrag.
- **Eine Phase 10 gibt es nicht.** Sie wurde abgeschafft; der Abschluss eines
  Einsatzes läuft über `final: true` zusammen mit `ended_at` (Abschnitt 3).
  Frühere Fassungen dieses Dokuments beschrieben eine Phase 10 als übertragen —
  das war zu keinem Zeitpunkt mehr richtig: Die Schreibwege lehnten alles außer
  2 bis 9 bereits ab. Wer nach der alten Fassung implementierte, sendete eine
  Phase 10 und bekam keine Fehlermeldung, sondern einen Eintrag weniger.

## 8. Format der Client-Kennung (`client_ref`)

Die Kennung identifiziert einen Einsatz oder ein Ruhe-Segment eindeutig **je
Gerät**; zusammen mit der Gerätekennung bildet sie den Idempotenz-Anker. Sie
wird von vier Stellen erzeugt, und an ihrem **Präfix hängt Verhalten** —
deshalb gehört sie in den Vertrag und nicht nur in den Code.

| Präfix | Erzeuger | Bedeutung |
|---|---|---|
| `m-` | Uhr-App | Einsatz |
| `r-` | Uhr-App | Ruhe-Segment |
| `man-` | Weboberfläche, Einsatzformular | von Hand angelegt |
| `imp-` | Import | aus einer Datei übernommen |
| `bak-` | Wiedereinspielen | aus einer Sicherung, ohne eigene Kennung |

**Das Verhalten, das daran hängt:** Beim endgültigen Löschen wird die Kennung
auf eine Sperrliste gesetzt, damit eine Uhr mit gepufferten Daten den Datensatz
nicht wieder anlegt. Für `man-` geschieht das bewusst **nicht** — dort gibt es
keine Uhr, die etwas nachliefern könnte.

Regeln:

- höchstens 64 Zeichen, keine Leerzeichen
- innerhalb eines Geräts eindeutig und über die Lebensdauer des Datensatzes
  **unveränderlich** — sie ist der Anker, an dem die Idempotenz hängt
- die Uhr bildet sie aus Präfix, Zeitstempel und einem Zufallsanteil. Der
  Zufallsanteil ist nötig, weil eine allein aus der Uhrzeit gebildete Kennung
  nach einem Zurücksetzen der Uhr kollidieren kann — der Upload träfe dann
  einen fremden alten Einsatz **desselben** Geräts.
- der Server prüft das Präfix nicht; ein Client mit anderem Präfix
  funktioniert, bekommt aber die Sperrlisten-Sonderbehandlung von `man-` nicht
