# JSON-Vertrag Gerät → Server

**Version:** 2.2 — die Kopplung ist seit 2.0 umgekehrt (1a): Das **Gerät**
zeigt den Code, das Web nimmt ihn entgegen, das Gerät bestätigt das Konto.
`pair.php` kennt vier Anliegen statt zwei. Die Hauptnummer stieg auf 2.0, weil
kein Client der Fassung 1.x sich mehr koppeln kann — bestehende Kopplungen und
alles ab Abschnitt 2 sind unberührt. **2.1** trägt den Wartungsmodus nach
(Abschnitt 5): ein 503 mit `{"error":"maintenance"}`, das jeder Endpunkt
schicken kann. **2.2** vervollständigt die Präfix-Tabelle (Abschnitt 8) um
`cut-` und sagt, welche **Herkunft** der Server aus jedem Präfix ableitet
(R64). Beide Nebennummern, weil es für die Clients keine neue Regel ist: Den
Wartungsmodus behandeln sie als 5xx wie bisher, und die Herkunft leitet der
Server allein ab — kein Client schickt sie, keiner liest sie.
**Endpunkt:** `POST https://<host>/ingest.php`
**Content-Type:** `application/json`

> **Dieses Dokument ist die führende Quelle.** Wer einen neuen Client baut,
> implementiert gegen diesen Text. Wo Uhr-App und Server dieselbe Liste oder
> denselben Wertebereich doppelt führen, gilt im Zweifel, was hier steht — und
> die Abweichung ist ein Fehler in der Umsetzung, nicht im Vertrag.

> **Geltungsbereich:** Dieses Dokument beschreibt ausschließlich den Vertrag
> zwischen einem aufzeichnenden Gerät und dem Server (`ingest.php`, `pair.php`)
> — die Garmin-Uhr, seit S4 auch die Android-Handy-App. Die JSON-Endpunkte, die die
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
| Zufallsanteil in der Client-Kennung (8) | durchgesetzt seit Uhr 1.7.0 |
| Präfixe der Client-Kennung (8) | beschrieben, vom Server bewusst nicht **geprüft** — seit Web 14.0.0 aber **ausgewertet**: Er leitet die Herkunft daraus ab (8) |
| Kalendertag muss existieren (3.2) | durchgesetzt |
| Koordinatenbereiche, Mengenbegrenzungen (3.2) | durchgesetzt |
| Leere oder zu kurze Liste löscht nichts (3.1) | durchgesetzt |
| Antwortfeld `rejected` (5) | durchgesetzt |
| Antwortfelder `kept_*` (5) | durchgesetzt |
| Dienstkennung `day_ref` (2.1) | durchgesetzt seit Web 6.0.0, gesendet ab Uhr 1.8.0 |
| Rückfallebene über `(Konto, day)` (2.1) | durchgesetzt, **dauerhaft** |
| Antwortfeld `dropped_points` (5) | durchgesetzt seit Web 10.2.0 |
| Antwortfeld `cut_points` (5) | durchgesetzt seit Web 12.5.0 |
| Block `geraet` wird gespeichert (1a) | durchgesetzt seit Web 12.9.0; davor stillschweigend verworfen |
| Kopplung in drei Anliegen (1a) | durchgesetzt seit Web 13.0.0 — der alte Weg (Code aus dem Web, Uhr tippt ihn ein) ist ersatzlos entfallen |
| 503 `{"error":"maintenance"}` während der Wartung (5) | durchgesetzt seit Web 13.2.0. **Für die Clients keine neue Regel** — es ist ein 5xx und wird als solches behandelt; der Zusatz `Retry-After` ist ein Hinweis, kein Auftrag |
| 413 „Uhr halbiert die Chunk-Größe und wiederholt" (5) | **beschrieben, nicht umgesetzt** — `Uploader.mc` setzt bei jedem Fehlercode nur `lastError`, und `UPLOAD_CHUNK_POINTS` ist eine Konstante. Gefunden in S2/AP3; die Anwendung lehnt heute keine Chunk-Größe ab, die die Uhr sendet, deshalb tritt der Fall nicht auf |

Bis auf eine Zeile lauten alle „durchgesetzt" — die Tabelle beschreibt damit
im Wesentlichen den Stand und keinen Zielzustand mehr. Sie bleibt trotzdem stehen, solange der
Vertrag Regeln enthält, deren Durchsetzung nicht selbstverständlich ist: Ein
Client darf sich darauf verlassen, dass ein Verstoß gemeldet wird — und genau
das sagt diese Tabelle zu.

Eine Ausnahme steht ausdrücklich darin: Die **Präfixe** der Client-Kennung
(Abschnitt 8) prüft der Server bewusst nicht.

## 1. Authentifizierung (jede Anfrage)

| Header | Inhalt |
|---|---|
| `X-Device-Id` | Öffentliche Geräte-ID (vom Admin beim Anlegen des Geräts vergeben) |
| `X-Api-Key` | Geheimer Geräteschlüssel (Klartext nur auf der Uhr; Server speichert Hash) |

Antwort bei ungültigem Schlüssel: `401 {"error":"auth"}`.

## 1a. Kopplung (`pair.php`) — seit Uhr ‹Fassung› / Web 13.0.0

`pair.php` kennt **vier** Anliegen: `start`, `status` und `bestaetigen`
(dieser Abschnitt) und `trennen` (Abschnitt 1b). Alle vier gehen per `POST`
an denselben Endpunkt, mit `Content-Type: application/json` und einem
Pflichtfeld `aktion` im Rumpf. Eine andere HTTP-Methode bekommt
`405 {"error":"method"}`, und zwar bevor der Server den Rumpf überhaupt
liest.

Ein Rumpf **ohne** `aktion` oder mit einer unbekannten Aktion bekommt
`400 {"error":"aktion","meldung":"Uhr-App aktualisieren"}`. Die Meldung ist
für Clients der alten Fassung gedacht, die den Kopplungscode noch **senden**
statt ihn zu **zeigen** — sie ist der einzige Kanal, auf dem eine solche Uhr
erfährt, was zu tun ist (1a.6).

**Das Gerät zeigt einen Code, das Web nimmt ihn entgegen, das Gerät bestätigt
das Konto.** Der Ablauf hat drei Schritte, und jeder ist ein Anliegen:

1. **`start`** — das Gerät bittet um eine Kopplungssitzung. Ohne Kopfzeilen:
   Es hat noch keine. Der Server antwortet mit einem **Anzeigecode** für den
   Menschen und mit den **Zugangsdaten**, die das Gerät ab jetzt trägt, aber
   erst nach Schritt 3 benutzen darf.
2. **`status`** — das Gerät fragt nach, ob jemand den Code in sein Konto
   eingegeben hat. Mit den Kopfzeilen aus Abschnitt 1, den Zugangsdaten aus
   Schritt 1.
3. **`bestaetigen`** — das Gerät sagt Ja oder Nein zu dem Konto, das der
   Server in Schritt 2 genannt hat. Mit Kopfzeilen. Nach Ja gibt es das
   Gerät; nach Nein gibt es die Sitzung nicht mehr.

**Bis Web 12.9.4 lief das andersherum:** Das Web erzeugte den Code, und die
Uhr tippte ihn am Handgelenk ein. Der Grund für die Umkehr ist nicht die
Bequemlichkeit, sondern das zweite Tor. Vorher entschied allein, wer den Code
kannte; jetzt gibt es zwei Prüfungen an zwei Geräten: Ein **fremdes Gerät im
eigenen Konto** scheitert an der Bestätigungsseite im Web, die Art und Modell
zeigt; das **eigene Gerät im fremden Konto** scheitert am Ja auf dem Gerät,
weil dort die maskierte Adresse des fremden Kontos steht und auffällt.

Der Code ist **nur für den Menschen**. Er weist das Gerät nirgends aus; wer
ihn abliest, kann am Gerät nichts auslösen. Was das Gerät ausweist, sind
Kennung und Schlüssel aus Schritt 1 — und die sind bis Schritt 3
**schwebend**: Der Server kennt sie, aber `ingest.php` weist sie mit `401`
ab, weil es das Gerät noch nicht gibt. Ein Gerät, das nie bestätigt, hat
damit einen Schlüssel, der zu nichts passt und nach zehn Minuten von selbst
wertlos wird.

**Zwei Bremsen, ein Fehlerschlüssel.** `start` wird je Absenderadresse
gezählt; `status`, `bestaetigen` und `trennen` zählen ihre Fehlversuche in
einem zweiten, davon unabhängigen Topf. Beide antworten
`429 {"error":"zu_viele_versuche"}`. Ein Client kann und muss sie nicht
auseinanderhalten: In beiden Fällen ist die Antwort dieselbe — später noch
einmal.

### 1a.1 `start` — Sitzung anlegen

Ohne Kopfzeilen. Kopfzeilen, die trotzdem mitkommen, liest der Server an
dieser Stelle nicht.

```json
{ "aktion": "start",
  "geraet": { "art": "uhr", "teil": "006-B4261-00", "br": 390, "ho": 390,
              "touch": true, "fw": 1140, "ciq": "5.2.0", "app": "2.1.0" } }
```

`geraet` ist **freiwillig** und kommt in zwei Formen (1a.4). Eine Kopplung
scheitert nie an einer Statistikangabe — aber Art und Modell sind das, was
die Kontoinhaberin auf der Bestätigungsseite **sieht**; ein Gerät, das nichts
über sich sagt, erscheint dort als „Gerät unbekannt".

Antwort `200`:

```json
{ "code": "AB3K7Q",
  "device_id": "dev-3f9a…",
  "api_key": "8c1e…",
  "frist_s": 600 }
```

| Feld | Bedeutung |
|---|---|
| `code` | sechs Zeichen aus dem Alphabet `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (ohne 0/O und 1/I — sie sind auf einem Uhrendisplay nicht zu unterscheiden). **Ohne Trennzeichen.** Das Gerät zeigt ihn in zwei Dreiergruppen („AB3 K7Q"); das Web nimmt ihn mit und ohne Leerzeichen, mit und ohne Bindestrich, in jeder Schreibung |
| `device_id` | die künftige Gerätekennung, `dev-` + 32 Hexzeichen (16 Zufallsbytes) |
| `api_key` | der Geräteschlüssel im Klartext, 48 Hexzeichen (24 Bytes). **Er wird genau einmal übertragen — hier.** Der Server speichert nur seinen Hash, und kein Protokoll sieht den Klartext |
| `frist_s` | die **volle** Gültigkeitsdauer der Sitzung in Sekunden (600) — nicht eine Restzeit |

**Die Frist läuft ab dem Anlegen der Sitzung**, nicht ab dem Empfang dieser
Antwort; die Laufzeit der Antwort geht dem Gerät verloren. Für die Anzeige
genügt `frist_s`; verlässlich ist `rest_s` aus `status` (1a.2), das der
Server aus derselben Uhr rechnet wie die Fristprüfung. Nach Ablauf sind Code
**und** Zugangsdaten wertlos, und das Gerät beginnt von vorn.

**`start` ist nicht idempotent.** Jeder Aufruf legt eine neue Sitzung mit
neuen Zugangsdaten an und zählt gegen die Adressgrenze. Ein bereits
gekoppeltes Gerät verliert dadurch nichts — seine `devices`-Zeile bleibt
stehen —, aber es hält dann zwei Schlüssel, von denen einer schwebt.

Fehler:

| Code | Rumpf | Bedeutung / Verhalten des Geräts |
|---|---|---|
| 429 | `{"error":"zu_viele_versuche","meldung":"…"}` | zu viele Sitzungen von dieser Adresse — später noch einmal |
| 429 | `{"error":"zu_viele_sitzungen","meldung":"…"}` | der Server hält gerade so viele offene Sitzungen, wie er höchstens hält — später noch einmal; der Zustand dauert Minuten, nicht Stunden |
| 405 | `{"error":"method"}` | falsche HTTP-Methode |
| 500 | `{"error":"server"}` | die Sitzung ließ sich nicht anlegen. Nichts ist entstanden; das Gerät darf wiederholen |

**Das Feld `meldung` ist ein Satz für den Menschen, kein Vertragsgegenstand.**
Es steht in einigen Fehlerantworten, sein Wortlaut kann sich ändern, und ein
Client entscheidet am Feld `error`, nicht an ihm. Sinnvoll ist es dort, wo
ein Gerät den Fall selbst nicht benennen kann — dann zeigt es die
Servermeldung als zweite Zeile.

### 1a.2 `status` — nachfragen

Mit den Kopfzeilen `X-Device-Id` und `X-Api-Key` aus Schritt 1:

```json
{ "aktion": "status" }
```

Antwort `200`, drei Zustände:

| Rumpf | Bedeutung |
|---|---|
| `{"zustand":"offen","rest_s":540}` | noch hat niemand den Code eingegeben; `rest_s` ist die Restgültigkeit in Sekunden |
| `{"zustand":"beansprucht","konto":"ph***@gen-em.org","rest_s":300}` | ein Konto hat den Code eingegeben. `konto` ist die **maskierte E-Mail-Adresse** dieses Kontos: die ersten zwei Zeichen des lokalen Teils, `***`, die volle Domain, alles klein geschrieben. Sie ist nur für den Dialog auf dem Gerät bestimmt und wird dort **nicht gespeichert** |
| `{"zustand":"gekoppelt"}` | zu diesen Kopfzeilen gibt es bereits ein Gerät — **ohne** `rest_s`, denn eine Sitzung gibt es nicht mehr. Das ist der Fall, wenn die Antwort auf `bestaetigen ja` verlorenging: Das Gerät darf die Zugangsdaten als gültig speichern |

**Warum die Adresse maskiert ist und die Domain trotzdem voll.** Der Zweck
des Feldes ist, dass die Trägerin ihr eigenes Konto **erkennt** — an einer
fremden Domain fällt das falsche Konto auf, und genau darauf beruht das
zweite Tor. Die volle Adresse dagegen gehört nicht auf ein Uhrendisplay, das
jeder ablesen kann, der danebensteht. Ein Client zeigt das Feld an und
zerlegt es nicht: Es ist eine Zeichenkette für Menschen, kein Adressformat.

Das Gerät fragt **höchstens alle fünf Sekunden** und nie, bevor die vorige
Antwort da ist. Ein Verbindungsfehler ist kein Grund aufzuhören — die Sitzung
lebt auf dem Server weiter, bis die Frist abläuft.

Fehler:

| Code | Rumpf | Bedeutung / Verhalten des Geräts |
|---|---|---|
| 401 | `{"error":"auth"}` | Kennung unbekannt **oder** Schlüssel falsch. Für das Gerät dasselbe wie 410: von vorn beginnen |
| 410 | `{"error":"abgelaufen"}` | die Sitzung ist verfallen. Code und Zugangsdaten wegwerfen, von vorn beginnen |
| 429 | `{"error":"zu_viele_versuche","meldung":"…"}` | Ratenschutz — gilt für alle vier Anliegen |

**`401` unterscheidet mit Absicht nicht, woran es lag.** Unbekannte Kennung,
falscher Sitzungsschlüssel, falscher Geräteschlüssel — der Rumpf ist in allen
Fällen byteweise derselbe, und die Antwort dauert in allen Fällen gleich
lang. Sonst beantwortete der Endpunkt einem Fremden die Frage, welche
Gerätekennungen es gibt; die Kennung ist die Hälfte dessen, was ein Upload
braucht.

**Eine verworfene Sitzung ergibt `401`, nicht `410`.** Wer Nein sagt oder im
Web abbricht, löscht die Zeile — danach ist die Kennung unbekannt wie jede
andere. Für das Gerät läuft beides auf dasselbe hinaus; wer den Endpunkt
nachbaut, muss den Unterschied kennen.

### 1a.3 `bestaetigen` — Ja oder Nein

Mit Kopfzeilen. `antwort` ist Pflicht und lautet `ja` oder `nein`:

```json
{ "aktion": "bestaetigen", "antwort": "ja" }
```

| Antwort | Bedeutung |
|---|---|
| `200 {"ok":true}` (nach `ja`) | **Das Gerät existiert jetzt** und ist aktiv; die Sitzung ist beendet. Das Gerät speichert Kennung und Schlüssel dauerhaft. Die Kontoinhaberin bekommt eine E-Mail — dieselbe wie bisher beim Koppeln. Eine Wiederholung derselben Anfrage antwortet ebenfalls `200`: Das Gerät gibt es schon |
| `200 {"ok":true}` (nach `nein`) | die Sitzung samt Zugangsdaten ist gelöscht. `nein` ist in **jedem** Zustand erlaubt, auch `offen` und auch nach Fristablauf — so bricht ein Gerät ab, das zurück auf seine Sync-Seite geht |
| `400 {"error":"payload"}` | `antwort` fehlt oder lautet etwas anderes als `ja` oder `nein`. Zählt **nicht** im Ratenschutz: Hier rät niemand, hier ist ein Client falsch gebaut |
| `401 {"error":"auth"}` | Kennung unbekannt oder Schlüssel falsch — wie in 1a.2 |
| `409 {"error":"nicht_beansprucht"}` | `ja`, aber noch hat kein Konto den Code eingegeben. **Die Sitzung bleibt bestehen**; ein Gerät, das sich an dieses Dokument hält, sendet `ja` nur nach `beansprucht` |
| `409 {"error":"device_limit","meldung":"…"}` | das Konto hat bereits so viele Geräte, wie es haben darf (zwischen der Eingabe im Web und dem Ja kann eines von Hand dazugekommen sein). **Die Sitzung ist gelöscht**; erst ein Gerät im Web löschen, dann von vorn |
| `410 {"error":"abgelaufen"}` | Frist vorbei, und die Antwort war `ja`. Von vorn |
| `429 {"error":"zu_viele_versuche","meldung":"…"}` | Ratenschutz |
| `500 {"error":"server"}` | das Anlegen scheiterte. Die Sitzung besteht weiter; das Gerät darf wiederholen |

**`410` und `409` kommen ohne Verzögerung und zählen nicht.** Sie setzen die
richtige Kennung **und** den richtigen Schlüssel voraus und sagen einem
Fremden deshalb nichts — der Code war richtig, hier ist niemand am Raten.
`401` dagegen zählt und wird künstlich verzögert.

**Was der Server nach `ja` in einem Zug tut:** die `devices`-Zeile anlegen —
mit Konto, Kennung, Schlüssel-Hash, einem Vorgabenamen nach der gemeldeten
Art und den drei Werten aus dem `geraet`-Block von Schritt 1 (1a.5) — und die
Sitzung löschen, beides in **einer** Transaktion. Scheitert das Anlegen,
bleibt die Sitzung bestehen und das Gerät bekommt `500 {"error":"server"}`;
es darf wiederholen. Ein zweites Ja zu derselben Sitzung wartet auf die erste
Transaktion, findet die Sitzung dann nicht mehr und bekommt `200` aus dem
Gerätezweig — die Kopplung hängt damit an keinem einzelnen Funkpaket.

**Zwei Fälle, die kein regelkonformer Client sendet, die aber eine Antwort
brauchen:**

**`nein` an einem bereits gekoppelten Gerät** ist ein Nichtstun:
`200 {"ok":true}`, und das Gerät bleibt. Der Fall entsteht, wenn das Ja das
Gerät angelegt hat, die Antwort verlorenging und jemand statt zu wiederholen
abbricht. Ein Nein, das ein fertiges Gerät löschte, wäre ein Trennen ohne
Trennen-Benachrichtigung: Das Konto verlöre ein Gerät, ohne davon zu
erfahren, und die Kopplungsmail von eben stünde falsch im Postfach. Bleibt
das Gerät stehen, ist der schlimmste Fall ein überflüssiger Eintrag — er ist
gemeldet, er trägt sieben Tage den Hinweis „neu", und ein Klick im Web
entfernt ihn.

**`trennen` mit schwebenden Zugangsdaten** wirkt wie `nein`: Die Sitzung wird
gelöscht, die Antwort ist `200 {"ok":true}`. Es gibt kein Gerät, das sich
trennen ließe, und die Sitzung braucht danach niemand mehr. Die Alternative —
ein Fehler — hinterließe eine Sitzung, die bis zum Fristende Platz belegt,
und zwar für einen Aufruf, der genau das Richtige wollte.

### 1a.4 Zwei Formen des Blocks `geraet` — Uhr und Handy

Der Block kommt in zwei Zuschnitten, weil die Geräte Verschiedenes über sich
wissen. Die **Garmin-Uhr kennt ihren Modellnamen nicht** und sendet ihre
Teilenummer; das **Handy kennt ihn** und sendet Hersteller und Modell
(E-S4-28, seit Android 0.2.0).

| Feld | Uhr | Handy |
|---|---|---|
| `art` | `"uhr"`, fest | `"handy"`, fest |
| `teil` | Teilenummer aus `System.getDeviceSettings().partNumber` — der eigentliche Schlüssel | `null` — ein Handy hat keine |
| `hersteller` | — | `Build.MANUFACTURER` |
| `modell` | — | `Build.MODEL` |
| `br`, `ho` | Displaybreite und -höhe in Pixeln | dito |
| `touch` | Touchscreen vorhanden | immer `true` |
| `fw` | Firmware-Stand des Geräts | Android-Fassung (`Build.VERSION.RELEASE`) |
| `ciq` | Fassung der Uhr-Plattform, `major.minor.patch` | **entfällt** |
| `sdk` | — | API-Stufe (`Build.VERSION.SDK_INT`) |
| `app` | `Const.APP_VERSION` der Uhr-App | Fassung der Handy-App |

Das Handy sendet die Handy-Form über **denselben Endpunkt** wie die Uhr; es
gibt keinen zweiten Kopplungsweg.

**`ciq` wird beim Handy weggelassen und nicht auf `null` gesetzt.** Ein Feld,
das es für diese Geräteart gar nicht gibt, ist etwas anderes als eines, das
das Gerät nicht beantworten kann. Der Vertrag stellt beides frei.

```json
{
  "aktion": "start",
  "geraet": {
    "art":        "handy",
    "teil":       null,
    "hersteller": "Google",
    "modell":     "Pixel 8",
    "br":         1080,
    "ho":         2400,
    "touch":      true,
    "fw":         "14",
    "sdk":        34,
    "app":        "0.7.7"
  }
}
```

### 1a.5 Was der Server davon speichert

**Drei Spalten an `devices`, nicht zehn** (Web 12.9.0, R42):

| Spalte | Inhalt |
|---|---|
| `geraet_art` | `uhr`, `handy` oder `sonstiges`; alles andere wird zu `NULL` |
| `geraet_modell` | aufgelöster Klarname (`Venu 3S`, `Google Pixel 8`) |
| `geraet_teil` | die **Rohangabe** des Geräts, unverändert |

**Displaymaße, Firmware, `ciq`/`sdk` und `app` werden nicht gespeichert**,
obwohl beide Clients sie senden. Der Grund steht in R36: Erhoben wird eine
**einmalige Geräteeigenschaft** — „welches Gerät", nicht „in welchem
Zustand". Die Felder bleiben trotzdem im Vertrag: Ein anderer Server darf sie
auswerten, und ein Client, der sie schon sendet, soll sie nicht wieder
ausbauen müssen.

**Die Rohangabe steht neben dem Modell und nicht statt seiner.** Der
Modellname entsteht aus einer erzeugten Tabelle (`server/geraetemodelle.php`,
siehe unten), und die kennt nur, was es beim Erzeugen schon gab. Ohne die
Rohangabe fiele ein künftiges Gerät dauerhaft und unwiederbringlich auf
„unbekannt"; mit ihr lässt sich jede Zeile später erneut auflösen.

**Fehlt der Block oder ist er unbrauchbar, bleiben alle drei Spalten leer.**
„Unbekannt" ist eine Sache der Anzeige, nicht der Spalte — vier Wege legen ein
Gerät an, und nur die Kopplung weiß etwas über es.

**Gelesen wird bei `start`, gespeichert bei `bestaetigen`.** Die drei Werte
entstehen, sobald das Gerät um eine Sitzung bittet, und liegen bis zum Ja in
der Sitzung — die Kontoinhaberin soll auf der Bestätigungsseite ja sehen,
**was** da koppeln will. Erst das Ja überträgt sie unverändert in die
`devices`-Zeile. Dieselben drei Spalten führt deshalb auch `pair_sessions`.
Ein späteres Nachauflösen des Modellnamens trifft nur `devices`; Sitzungen
leben zehn Minuten, und in zehn Minuten ändert sich keine Modelltabelle.

**Was ankommt, wird zugeschnitten und nicht geglaubt.** Der Block ist eine
Selbstauskunft eines Geräts, das sich erst vorstellt: Längen werden auf die
Spaltenbreite gekürzt, Steuerzeichen zu Leerzeichen, eine Geräteart außerhalb
der drei erlaubten Werte zu `NULL`. Ein Block, der gar keiner ist — eine
Zeichenkette, eine Zahl, `true` —, ergibt drei leere Werte und **keinen
Fehler**.

**Warum die Teilenummer und nicht der Modellname.** Die Uhr kennt ihren
Modellnamen nicht — `DeviceSettings` führt ihn nicht. Die Teilenummer ist
dagegen eindeutig und lässt sich gegen die Gerätedateien der Uhr-Plattform
auflösen: 325 Teilenummern führen auf 173 Modelle, samt Geräteart. Diese
Zuordnung gehört auf den Server; eine Uhr mit 128 kB ist der falsche Ort für
eine Modelltabelle. Sie liegt in `server/geraetemodelle.php` und ist
**erzeugt** (`tools/geraetemodelle/`).

**Bei der Geräteart schlägt die Tabelle die Selbstauskunft.** Die Uhr-App
sendet `art` fest als `"uhr"`, weil eine Connect-IQ-App nur auf Garmin-Geräten
läuft — Uhr und Radcomputer kann sie nicht unterscheiden. Die Gerätedateien
können es. Kennt die Tabelle die Teilenummer, gilt ihre Einstufung.

**Was bewusst fehlt: `uniqueIdentifier`** (Uhr) und `ANDROID_ID`, IMEI,
Seriennummer (Handy). Das sind dauerhafte, geräteweite Kennungen. Für eine
Stückzahl-Statistik werden sie nicht gebraucht, und in einer kleinen Gruppe
wären sie ein Personenbezug mehr, als die Frage rechtfertigt. Die Zuordnung
leistet die `device_id`, die der Server bei der Kopplung ohnehin vergibt.

### 1a.6 Was ein Client der alten Fassung sieht

Ein Gerät, das `{"code":"…"}` ohne `aktion` sendet, bekommt `400` mit
`error: aktion` und `meldung: "Uhr-App aktualisieren"`. Es gibt **keine**
Übergangszeit, in der beide Wege gehen: Der alte Weg setzte einen im Web
erzeugten Code voraus, und den gibt es nicht mehr. Eine Übergangszeit hätte
zwei Kopplungswege nebeneinander gebraucht, jeden mit eigener Bremse, für
einen Bestand von einer Uhr.

Der einzige Fall, in dem eine alte Uhr diese Meldung **nicht** sieht: Ist die
Absenderadresse gerade wegen zu vieler Fehlversuche gesperrt, antwortet der
Server `429`, bevor er die Aktion überhaupt ansieht.

**Bestehende Kopplungen sind von alldem nicht berührt** — `ingest.php` und
Abschnitt 1 ändern sich nicht. Was eine alte Uhr verliert, ist die
Möglichkeit, sich **neu** zu koppeln.

## 1b. Trennen (`pair.php`) — seit Uhr 1.11.0 / Web 9.15.0

Die Uhr gibt ihre Kopplung zurück. POST an **denselben** Endpunkt, diesmal
**mit** den Headern aus Abschnitt 1:

```json
{ "aktion": "trennen" }
```

| Antwort | Bedeutung |
|---|---|
| `200 {"ok":true}` | Das Gerät ist gelöscht |
| `401 {"error":"auth"}` | Kennung oder Schlüssel falsch — wie in Abschnitt 1 |
| `429 {"error":"zu_viele_versuche","meldung":"…"}` | Ratenschutz, gilt für **alle vier** Anliegen von `pair.php` (1a) |

**Ein `trennen` mit noch schwebenden Zugangsdaten** — aus einer Kopplung, die
das Ja noch nicht hinter sich hat — löscht die Sitzung und antwortet ebenfalls
`200 {"ok":true}`. Die Begründung steht in 1a.3.

**Wozu.** Der Fall ist die geteilt genutzte Uhr. Bis Uhr 1.10.3 gab es für den
Wechsel der Person nur „neuen Code eintippen"; gelang das nicht, dokumentierte
die Uhr stillschweigend weiter auf das vorherige Konto. Die Reihenfolge ist
jetzt ausdrücklich **abfragen → trennen → neu koppeln** (Backlog Nr. 14).

**Der Server löscht, er deaktiviert nicht.** Ein deaktiviertes Gerät belegt
weiter einen der `MAX_GERAETE` Plätze — und „zu viele Geräte" ist genau der
Fehler, in den eine geteilte Uhr sonst läuft. Der Fremdschlüssel setzt
`device_id` in Einsätzen und Segmenten auf `NULL`; **bereits hochgeladene
Daten bleiben vollständig erhalten.**

**Die Uhr trennt sich lokal auch ohne Antwort.** Erreicht sie den Server
nicht, löscht sie ihre Zugangsdaten trotzdem und sagt es
(„Nur auf der Uhr getrennt / Gerät im Web löschen"). Andernfalls bliebe eine
Uhr ohne Telefon in Reichweite dauerhaft an ein Konto gebunden, das sie nicht
mehr benutzen soll. Der Servereintrag steht dann noch und ist im Web mit einem
Klick zu entfernen.

**Vor dem Trennen muss der Rückstand leer sein.** Abgeschlossene, noch nicht
gesendete Pakete gehören dem bisherigen Konto; nach einer Neukopplung gingen
sie an das neue. Die Uhr verweigert das Trennen deshalb, solange
`Model.backlogCount() > 0`, und sagt „Erst N Pakete senden".

## 2. Grundprinzipien

- **Zeitstempel:** ISO 8601 in UTC mit `Z`-Suffix, Sekundenauflösung (`2026-07-16T08:31:05Z`). Track-Punkte nutzen kompakte Unix-Epochen (Sekunden, UTC).
- **Idempotenz:** Jeder Einsatz und jedes Ruhe-Segment trägt eine von der Uhr erzeugte `client_ref` (eindeutig pro Gerät). Wiederholtes Senden derselben Daten ist unschädlich — **auch in der falschen Reihenfolge** (seit Web 13.0.1): Ein Feld, das ein Paket nicht trägt (`ended_at`, `distance_m` und `ascent_m` sind `null`, solange der Einsatz läuft), löscht auf dem Server nichts. Ein Wert überschreibt, ein `null` lässt stehen. Eine Berichtigung bleibt damit möglich; ein einmal gesetztes Ende verschwindet nicht mehr, so wenig wie `final` zurückgeht.
- **Inkrementeller Track:** Track-Punkte werden mit fortlaufender Sequenznummer gesendet. Die Uhr sendet ab `seq_from`; der Server ignoriert bereits bekannte Sequenzen und antwortet mit `next_seq`, ab dem die Uhr weitersenden soll. Nach bestätigtem Empfang darf die Uhr ihren lokalen Puffer bis `next_seq` leeren.
- **Diensttag:** Feld `day` = Datum des Dienstbeginns (Format `YYYY-MM-DD`); die Uhr bestimmt es einmal bei „Einsatztag starten" und verwendet es für alle Uploads dieses Dienstes. Seit Vertrag 1.3 ist es **nicht mehr der Zuordnungsschlüssel**, sondern nur noch Sortier- und Anzeigedatum — die Zuordnung leistet `day_ref` (Abschnitt 2.1).
- **Nachzügler:** Bei fehlender Verbindung puffert die Uhr und sendet später identisch nach — keine Sonderfelder nötig.
- **Die Ablage auf dem Server geht die Uhr nichts an** (Nachtrag S2, ohne
  Vertragsänderung). Seit Web 10.0.0 liegen Spurpunkte je nach Alter als
  Zeilen in `track_points` **oder** als komprimierter Blob in `track_blobs`
  (Format SPUR1), und seit Web 10.2.0 werden sie sechs Monate nach Einsatzende
  ausgedünnt. **Am Vertrag ändert das nichts:** Die Uhr sendet unverändert
  Punkte mit `seq`, und der Server antwortet unverändert mit `next_seq`. Was
  sich geändert hat, ist die *Bedeutung* von `next_seq` — beschrieben in
  Abschnitt 5. Der Grund, es hier trotzdem zu nennen: Wer den Vertrag liest,
  um einen Fehler zu suchen, soll wissen, dass hinter „gespeichert" ab dieser
  Fassung mehr als eine Zeilentabelle steht (Einzelheiten in `docs/Technik.md`,
  Abschnitt 4.97).

### 2.1 Dienstkennung `day_ref`

Optionales Feld in `mission` **und** `rest_segment`, seit Vertrag 1.3. Die Uhr
erzeugt es bei „Einsatztag starten" und schickt es für **alle** Uploads dieses
Dienstes unverändert mit — gleiches Muster wie `client_ref`, gleiche
Idempotenz-Eigenschaft, dieselben Formatregeln (Abschnitt 8, Präfix `d-`).

**Wozu es da ist.** Bis Web 5.10.0 war ein Diensttag ein Kalendertag, und
`(Konto, day)` benannte ihn eindeutig. Seit Web 6.0.0 ist er eine eigene Zeile:
Zwei Dienste an einem Kalendertag sind der vorgesehene Fall — ein
Hubschrauberdienst am Tag, ein NEF-Nachtdienst am Abend. Aus dem Datum allein
lässt sich dann nicht mehr ableiten, welcher gemeint ist.

**Was der Server damit tut:**

| Fall | Verhalten |
|---|---|
| `day_ref` bekannt | Der zugehörige Diensttag wird verwendet |
| `day_ref` unbekannt, Datensatz hängt schon an einem Diensttag | Die Kennung wird an **diesen** gebunden |
| `day_ref` unbekannt, Datensatz ist neu | Neuer Diensttag, Kennung wird eingetragen |
| `day_ref` fehlt | Rückfallebene über `(Konto, day)` |

Der zweite Fall ist der Umstieg auf eine Uhr-Fassung **mit** Kennung mitten im
Dienst: Der laufende Dienst liegt bereits als Diensttag vor, angelegt über die
Rückfallebene. Ohne diese Bindung entstünden aus einem Dienst zwei.

**Mehrere Kennungen je Diensttag sind zulässig.** Werden zwei Diensttage in der
Weboberfläche zusammengeführt, wandern die Kennungen des aufgenommenen Tages
zum Zieltag. Ein späterer Upload mit einer von ihnen landet dadurch von selbst
richtig — es gibt keine Umleitung und keinen Sonderfall.

**Die Rückfallebene bleibt dauerhaft.** Sie ist kein Übergang: Ein Update des
Servers darf eine Uhr nicht außer Betrieb setzen, die niemand aktualisiert hat.
Liegen auf dem Datum mehrere Diensttage, entscheidet die **Zeit** des
Datensatzes — erst der Diensttag, dessen Zeitraum ihn umschließt, dann der
letzte, der vor ihm begonnen hat, dann der früheste des Datums.

**Die Uhr erfährt nichts über die Einsatzart.** Ein von ihr angelegter
Diensttag ist immer neutral: ohne Art, ohne Besatzungsrollen, ohne
artabhängige Felder. Standort und Rettungsmittel werden in der Weboberfläche
nachgetragen; Zeiten, Phasen, Track und Reanimation sind davon unberührt und
werden vollständig erfasst.

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
  "client_ref": "m-42-1837704912",
  "day": "2026-07-16",
  "day_ref": "d-41-0938175520",
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
| `day_ref` | höchstens 64 Zeichen; ein unbrauchbarer Wert verwirft die **Kennung**, nicht den Upload — ohne sie greift die Rückfallebene |
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
  "client_ref": "r-43-2094771830",
  "day": "2026-07-16",
  "day_ref": "d-41-0938175520",
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
- `next_seq`: die erste Sequenznummer, die der Server noch **erwartet** → Uhr
  sendet beim nächsten Mal `seq_from = next_seq` und darf lokal alles davor
  verwerfen.

  > **Seit Web 10.2.0 heißt das nicht mehr „noch nicht gespeichert".** Alles
  > unterhalb `next_seq` ist *erledigt* — gespeichert **oder** endgültig
  > verworfen. Zwei Fälle führen dazu: ein Punkt, den die Wertprüfung abgelehnt
  > hat (er stünde sonst dem Aufräumen der Uhr für immer im Weg), und Punkte zu
  > einer Spur, die der Server nach sechs Monaten ausgedünnt hat (S2, E-S2-08).
  > Für die Uhr ändert sich nichts: Sie leert ihren Puffer wie bisher.
  > `next_seq` ist mindestens `seq_from` + Zahl der gesendeten Punkte.

Zusätzlich können auftreten:

| Feld | Bedeutung |
|---|---|
| `rejected` | verworfene Einzelwerte, nach Ursache gezählt (z. B. `phases.phase: ausserhalb von 2…9` → 2) |
| `kept_phases` | die gesendete Phasenliste wurde übergangen (leer oder kürzer als der vorhandene Stand); der Wert nennt die **Anzahl der behaltenen** Einträge |
| `kept_resus` | dasselbe für die Reanimationssitzungen |
| `dropped_points` | Punkte, die der Server nach der **Ausdünnung** der Spur nicht mehr annimmt (S2, E-S2-08). Sie sind quittiert; die Uhr darf sie löschen. Erscheint nur, wenn tatsächlich verworfen wurde, und ist **kein** Datenfehler — deshalb steht es nicht in `rejected` |
| `cut_points` | Punkte, die in einen **herausgeschnittenen** Zeitraum fallen (S4, E-S4-53). Aus dieser Spur ist ein Einsatz geschnitten worden; die Punkte stehen dort bereits. Sie sind quittiert, die Uhr darf sie löschen. Wie `dropped_points` kein Datenfehler — und bewusst ein eigenes Feld: Ausdünnung und Schnitt sind verschiedene Vorgänge, und in der Fehlersuche will man sie unterscheiden |

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
| 503 | `{"error":"maintenance","meldung":"…"}` | **Wartungsmodus** — ein Sonderfall von 5xx, **kein neues Verhalten**: Der Server wird gerade aktualisiert und schließt sich für die Dauer. Behandlung genau wie 5xx, also Backoff und unverändert erneut. Die Antwort trägt zusätzlich `Retry-After` in Sekunden (heute 300) als Hinweis für Browser und Werkzeuge; **die Geräte müssen ihn nicht auswerten** und tun es heute nicht |

**Warum das eigens dasteht, obwohl sich nichts ändert.** Der Wartungsmodus
(Web 13.2.0) ist die einzige Lage, in der der Server ein 5xx **absichtlich**
und **für längere Zeit** schickt. Wer einen neuen Client schreibt, soll
wissen, dass diese Antwort erwartbar ist und keinen Fehlerzustand am Gerät
bedeutet: Der Puffer bleibt, nichts wird markiert, nichts wird bestätigt.
Genau das prüft das S4-Prüfprotokoll für die Android-App bereits nach
(„5xx / 503 → später erneut, nichts markiert, nichts bestätigt").

Alle Endpunkte antworten so — `ingest.php`, `pair.php` mit allen vier
Anliegen, und die Skript-Endpunkte unter `/api/`. Ausgenommen sind die
Skripte, die die Wartung selbst braucht (`update.php`,
`wiederherstellen.php`, `jobs.php`, `login.php`, `logout.php`,
`install.php`); sie antworten wie sonst. Einzelheiten und Betriebsablauf:
`docs/Technik.md`, Abschnitte 4.99c und 7.

## 6. Chunk-Größen

- Richtwert: **max. 500 Track-Punkte pro Anfrage** (Connect-IQ-Payload-Limit und Mobilfunk-Robustheit). Größere Bestände werden in mehreren aufeinanderfolgenden Anfragen gesendet (`seq_from` fortlaufend).
- Serverseitige Obergrenze: 512 KB Body (`413` bei Überschreitung).

## 7. Phasen-Nummern (Referenz)

`1` Frei · `2` Alarmierung · `3` Ausrücken · `4` Ankunft Einsatzort ·
`5` Ankunft PatientIn · `6` Transportbeginn · `7` Ankunft Klinik ·
`8` Übergabezeit · `9` Endzeit des Einsatzes.

**Übertragen werden ausschließlich die Nummern 2 bis 9.**

**Zwei Beschriftungen sind mit Vertrag 1.3 neutral geworden:** Phase 3 hieß
„Abflug", Phase 7 „Landung Krankenhaus". Die Anwendung dokumentiert seit Web
6.0.0 auch bodengebundene Notarzteinsätze, an denen weder das eine noch das
andere stattfindet.

Für einen Client ist das **folgenlos**: Übertragen werden Nummern, keine
Beschriftungen. Die Umbenennung betrifft allein, was Uhr und Weboberfläche
anzeigen — Nummerierung, Bedeutung und Reihenfolge der Phasen sind unverändert.

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

| Präfix | Erzeuger | Bedeutung | Herkunft (`origin`) |
|---|---|---|---|
| `m-` | Garmin-Uhr-App | Einsatz | `watch` |
| `r-` | Garmin-Uhr-App | Ruhe-Segment | — (Segmente tragen keine) |
| `d-` | Garmin-Uhr-App | **Dienst** (`day_ref`, Abschnitt 2.1) | — |
| `am-` | Android-Handy-App | Einsatz | `android` |
| `ar-` | Android-Handy-App | Ruhe-Segment | — |
| `ad-` | Android-Handy-App | **Dienst** | — |
| `wm-` | Wear-OS-App | Einsatz, an der Uhr begonnen — gesendet hat ihn das Handy | `wear` |
| `man-` | Weboberfläche, Einsatzformular | von Hand angelegt | `manual` |
| `imp-` | Import | aus einer Datei übernommen | `import` |
| `cut-` | Weboberfläche, Schneiden | aus einem Ruhe-Segment herausgeschnitten (`api/schneiden.php`, Web 12.5.0) | `schnitt` |
| `bak-` | Wiedereinspielen | aus einem Backup, ohne eigene Kennung | `watch` (Rückfall) |

**`cut-` fehlte in dieser Tabelle bis Fassung 2.2**, obwohl `api/schneiden.php`
es seit Web 12.5.0 vergibt. Der Vertrag nannte zehn Präfixe, der Server elf —
und weil an den Präfixen Verhalten hängt, war das keine Auslassung, sondern
eine falsche Zusage. Gefunden bei der Umsetzung von R64 (B-R64-01).

**Die vier Android-Präfixe stehen seit Fassung 1.4 hier** (nachgetragen mit
S6, weil sie an R42 hingen). Sie sind der Grund, warum die Präfixe überhaupt
im Vertrag stehen und nicht nur im Code: Zwei Clients derselben Person
schreiben in dasselbe Konto, und ohne getrennte Präfixe kollidierten ihre
Zähler. **`wm-` ist dabei kein eigener Client am Server** — die Wear-OS-App
hat weder Serveradresse noch Schlüssel (E-S4-11); sie schickt ihre Ereignisse
an das Handy, und das Handy sendet. Das Präfix sagt, *wo* der Einsatz begonnen
wurde, nicht *wer* ihn hochgeladen hat.

**Das Verhalten, das daran hängt:** Beim endgültigen Löschen wird die Kennung
auf eine Sperrliste gesetzt, damit eine Uhr mit gepufferten Daten den Datensatz
nicht wieder anlegt. Für `man-` geschieht das bewusst **nicht** — dort gibt es
keine Uhr, die etwas nachliefern könnte.

Regeln:

- höchstens 64 Zeichen, keine Leerzeichen
- innerhalb eines Geräts eindeutig und über die Lebensdauer des Datensatzes
  **unveränderlich** — sie ist der Anker, an dem die Idempotenz hängt
- die Uhr bildet sie seit **Uhr 1.7.0** aus Präfix, einem fortlaufenden Zähler
  im Gerätespeicher und einem Zufallsanteil — zum Beispiel `m-42-1837704912`.
  **Kein Zeitstempel mehr.** Bis Uhr 1.6.6 war es Präfix plus Sekunden seit
  1970 (`m-1785000000`); das hatte zwei Folgen:
  1. Springt die Uhrzeit zurück (Zurücksetzen des Geräts, Zeitzonenwechsel im
     Flugmodus), entstehen erneut Kennungen, die es schon gab — der Upload
     träfe dann einen fremden alten Einsatz **desselben** Geräts.
  2. Die Kennung verriet den Startzeitpunkt auf die Sekunde, auch wenn er
     später im Web korrigiert wurde.

  Der Zähler überlebt Neustarts und Zeitsprünge und ist die eigentliche
  Zusicherung; der Zufallsanteil verhindert, dass sich Reihenfolge oder
  Zeitpunkt ablesen lassen. Der Startzeitpunkt steht als `started_at` im
  Datensatz — dort gehört er hin, und dort ist er korrigierbar.

  **Kennungen der alten Form bleiben gültig.** Es gibt keine Umstellung: Der
  Server prüft das Format nicht, und die Idempotenz hängt allein an der
  Gleichheit der Zeichenkette. Eine Uhr, die beim Update noch ungesendete
  Daten im Puffer hat, liefert sie unverändert nach.
- der Server prüft das Präfix nicht; ein Client mit anderem Präfix
  funktioniert, bekommt aber die Sperrlisten-Sonderbehandlung von `man-` nicht

### Die Herkunft wird abgeleitet, nicht gesendet (seit Web 14.0.0, R64)

**Kein Client schickt eine Herkunft, und keiner liest sie.** Der Server leitet
sie beim Anlegen aus dem Präfix ab (`herkunft_ableiten()` in
`server/geraete_lib.php`) und ändert sie danach nie wieder. Für die Clients
ändert sich damit nichts — dieser Abschnitt beschreibt, was mit ihrer Kennung
geschieht, nicht was sie zu senden hätten.

**Der Rückfall für ein unbekanntes Präfix** ist die Geräteart aus der Kopplung
(1a): `handy` → `android`, sonst `watch`. Deshalb steht `bak-` oben auf
`watch` — es kommt aus dem Wiedereinspielen, wo es kein Gerät gibt, und eine
Sicherung trägt in diesem Fall ohnehin meist ihre eigene Herkunft mit.

**Ein Wert je Client-App, nicht je Hersteller** (E-R64-02). Das ist die Regel
für jeden künftigen Client, und sie hat einen Grund: `am-` und `wm-` kommen
vom **selben Gerät** — die Wear-OS-App hat weder Serveradresse noch Schlüssel
und schickt ihre Ereignisse an das Handy (E-S4-11). Nur das Präfix trennt sie.
Wer eine App für eine Uhr eines anderen Herstellers baut, trägt hier ein neues
Präfix ein und bekommt einen **eigenen** Herkunftswert; `watch` bleibt die
Garmin-Uhr-App. Würde er unter `watch` mitgeführt, wäre jede Auswertung, die
darauf filtert, rückwirkend mehrdeutig — und zwar ohne dass es den Daten
anzusehen wäre. Welcher Hersteller es war, steht ohnehin im Modell
(Abschnitt 1a, `geraet`).

Der Wertevorrat steht an **einer** Stelle im Code (`HERKUNFT_WERTE`); ein
neuer Client kostet drei Einträge — diese Tabelle, die Wertliste und die
Beschriftungen (`docs/Export-Format.md` 3.6) — und keine Schemaänderung.
