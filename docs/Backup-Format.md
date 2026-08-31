# Backup-Dateiformat (`.edbak`)

Der Export sichert **alle** Daten einer NutzerIn in einer
passwortverschlüsselten Datei. Seit **Version 2** passieren Ver- und
Entschlüsselung vollständig im Browser (`assets/crypto.js`) — der Server sieht
zu keinem Zeitpunkt Klartext. Weil die geschützten Angaben dabei entschlüsselt
in den Container wandern und beim Import mit dem Schlüssel des Zielkontos neu
verschlüsselt werden, lässt sich ein Backup **in jedes Konto** einspielen.

**Seit Web 11.0.0 ist die Datei mehrteilig** (Containerfassung 4): ein ZIP mit
versiegelten Teilen. Die einteiligen Fassungen 2 und 3 werden weiterhin
**gelesen** und nicht mehr geschrieben; mit NaDoku 1.0 fallen sie weg
(Entscheidung vom 31.08.2026, `docs/Backlog.md` Nr. 46).

| Fassung | Gestalt | geschrieben | gelesen |
|---|---|---|---|
| **4** | ZIP mit versiegelten Teilen (`PK`-Magie) | seit Web 11.0.0 | ja |
| 3 | eine versiegelte Datei (`EDBAK2`, Kopf mit Rundenzahl) | Web 5.0.0 – 10.3.0 | ja, bis NaDoku 1.0 |
| 2 | eine versiegelte Datei ohne Rundenzahl im Kopf | bis Web 4.7.0 | ja, bis NaDoku 1.0 |

---

## 1. Container, Fassung 4 (seit Web 11.0.0)

### 1.1 Warum mehrteilig

Eine Sicherung mit 5000 Einsätzen trägt rund drei Millionen Spurpunkte. Bis
Web 10.3.0 entstand sie als **eine** JSON-Zeichenkette im Browser und ging als
**ein** POST zurück. Beides sprengt jedes Budget, das ein Telefon oder ein
einfacher Webspace hat — und zwar an der Stelle, an der jemand ohnehin schon
beunruhigt ist.

Fassung 4 zerlegt sie deshalb. **Gemessen** am Referenzbestand (87 Einsätze,
100 Ruhesegmente, 48 981 Spurpunkte): 218 KB statt 739 KB, also 70 % weniger,
bei gleichem Inhalt.

### 1.2 Aufbau

Ein ZIP, **gespeichert und nicht gepackt** (`level: 0`) — die Teile sind
bereits gzip *und* verschlüsselt; ein zweiter Packlauf kostet Zeit und bringt
nichts:

| Eintrag | Inhalt |
|---|---|
| `manifest.edbak` | Teileliste mit SHA-256 je Teil, Sicherungskennung, Erzeugungszeit, Web-Version |
| `kern.edbak` | die Nutzlast (Abschnitt 2) **ohne** Punktlisten |
| `spuren/0001.edbak`, `spuren/0002.edbak`, … | je Teil eine Liste `{spur_ref, blob}` — SPUR1, Base64, Ziel 2 MB |

Das Manifest steht **physisch zuletzt** im Archiv: Es kennt erst dann alle
Prüfsummen. Gelesen wird ohnehin nach Namen, nicht nach Reihenfolge.

Das Manifest im Klartext:

```jsonc
{
  "format": "einsatzdoku-backup-manifest",
  "fassung": 4,
  "kennung": "9f3c…",              // 16 Byte Zufall, hex — bindet die Teile
  "erzeugt_am": "2026-08-31T12:00:00.000Z",
  "web_version": "11.0.0",
  "nutzlast": 8,                   // Fassung des Kerns, s. Abschnitt 2
  "teile": [
    { "name": "kern.edbak",        "art": "kern",   "sha256": "…" },
    { "name": "spuren/0001.edbak", "art": "spuren", "sha256": "…" }
  ],
  "spurteile": 1,
  "spuren": 181,                   // wie viele Spuren die Datei trägt
  "punkte": 48981,                 // und wie viele Punkte darin stecken
  "pat_key_check": "3f2a…"         // wie bisher, s. Abschnitt 2
}
```

Ein Spurteil im Klartext:

```jsonc
{
  "spuren": [
    { "spur_ref": 1, "blob": "<SPUR1, Base64>",
      "stufe": 2, "n_original": 443, "n": 443 }
  ]
}
```

**Geschnitten wird an Spurgrenzen.** Eine Spur liegt ganz in einem Teil; eine
über die Grenze gestückelte wäre nur mit beiden Teilen brauchbar, und dann
hätte die Teilung nichts gebracht. Die Einteilung steht **vor** dem ersten
Abruf fest (250 000 Punkte je Teil), weil die Zusatzdaten `<nr>/<gesamt>`
tragen — die Gesamtzahl muss bekannt sein, bevor das erste Teil versiegelt
wird.

### 1.3 Jedes Teil ist ein Container

| Bytes | Inhalt |
|---|---|
| 0–7 | Magie: ASCII `EDBAK2` + `0x00` + Fassung `0x04` |
| 8 | Flag: `1` = Inhalt gzip-komprimiert, `0` = roh |
| 9–12 | Rundenzahl der Schlüsselableitung (4 Bytes, big endian) |
| 13–28 | Salt (16 Bytes) — **in allen Teilen derselbe** |
| 29–40 | AES-GCM-Initialisierungsvektor (12 Bytes, je Teil neu) |
| ab 41 | Chiffretext, die letzten 16 Bytes sind das GCM-Auth-Tag |

Der Aufbau ist der der Fassung 3 — **bis auf die Fassungsnummer und die
Zusatzdaten.**

**Warum das Fassungsbyte trotzdem 0x04 ist.** Die Zusage aus Abschnitt 1a
lautet „AAD = die ersten 13 Bytes", und für ein Teil stimmt sie nicht mehr.
Wer ein Teil einzeln öffnet — von Hand, mit dem Rezept unten —, bekäme mit
`0x03` die Meldung für ein falsches Passwort und suchte den Fehler an der
falschen Stelle. Mit `0x04` kann jeder Leser sagen, was es wirklich ist.

### 1.4 Die Zusatzdaten binden den Platz des Teils

```
AAD = die ersten 13 Bytes  ‖  eine der beiden Zeichenketten (UTF-8):

  Manifest       EDBAK4|manifest
  jedes andere   EDBAK4|<kennung>|<name>|<nr>/<gesamt>
```

`<kennung>` ist die Sicherungskennung aus dem Manifest, `<name>` der
Archivname des Teils, `<nr>` seine Stellung in der Teileliste (1-basiert) und
`<gesamt>` deren Länge. Der Kopf bleibt gebunden wie bisher; der Platz kommt
dazu.

**Was das leistet:** Ein fehlendes, doppeltes, vertauschtes oder aus einer
**anderen** Sicherung stammendes Teil fällt beim Entsiegeln auf — nicht erst
beim Datenvergleich, und nicht gar nicht. Ohne diese Bindung ließe sich
`spuren/0003.edbak` einer fremden Sicherung unterschieben; sie entsiegelte
klaglos (dasselbe Passwort genügt) und brächte die Spuren eines fremden
Bestands mit. Das Muster ist von Cryptomator und age abgeschaut, wo der
Blockindex aus demselben Grund in die Zusatzdaten wandert.

**Zwei Sicherungen, und jede trägt für sich.** Die SHA-256 aus dem Manifest
fängt dieselben Fälle wie die Zusatzdaten — aber sie sagt Verschiedenes: „Teil
X ist nicht das, das hier stehen soll" gegen „ließ sich nicht öffnen". Für
wen eine Sicherung nicht aufgeht, ist das der Unterschied zwischen zehnmal
Passwort tippen und die richtige Datei suchen. `tools/containerprobe/` weist
beide **einzeln** nach.

### 1.5 Eine PBKDF2 je Vorgang

Salt und Rundenzahl stehen in **allen** Teilköpfen gleich; der Schlüssel wird
einmal abgeleitet und weitergereicht. Bei zwölf Teilen wären es sonst zwölf
Ableitungen zu je 320 000 Runden — auf einem gedrosselten Telefon eine knappe
Minute reines Warten, und zwar zweimal: beim Sichern und beim Einspielen.

Wer eine Fassung-4-Datei liest, nimmt Salt und Runden aus dem **Manifest** —
es ist das erste Teil, das er anfasst.

### 1.6 Von Hand öffnen (Python)

```python
import hashlib, gzip, json, struct, zipfile, base64
from cryptography.hazmat.primitives.ciphers.aead import AESGCM

passwort = '…'

def teil_oeffnen(roh, schluessel, aad_text):
    assert roh[:6] == b'EDBAK2' and roh[7] == 4, 'kein Teil der Fassung 4'
    kopf, flag = roh[:13], roh[8]
    aad = kopf + aad_text.encode('utf-8')
    koerper = AESGCM(schluessel).decrypt(roh[29:41], roh[41:], aad)
    return json.loads(gzip.decompress(koerper) if flag == 1 else koerper)

with zipfile.ZipFile('backup.edbak') as z:
    m = z.read('manifest.edbak')
    runden = struct.unpack('>I', m[9:13])[0]
    salz = m[13:29]
    key = hashlib.pbkdf2_hmac('sha256', passwort.encode(), salz, runden, 32)

    manifest = teil_oeffnen(m, key, 'EDBAK4|manifest')
    gesamt = len(manifest['teile'])
    for nr, t in enumerate(manifest['teile'], start=1):
        roh = z.read(t['name'])
        assert hashlib.sha256(roh).hexdigest() == t['sha256'], t['name']
        aad = f"EDBAK4|{manifest['kennung']}|{t['name']}|{nr}/{gesamt}"
        inhalt = teil_oeffnen(roh, key, aad)
        if t['art'] == 'kern':
            kern = inhalt
        else:
            for e in inhalt['spuren']:
                blob = base64.b64decode(e['blob'])   # SPUR1, s. Abschnitt 2
```

Die Spur aus dem Blob holt `spur1_lesen()` weiter unten. Dieselben Handgriffe
gehen in PHP mit `unpack('l*', gzuncompress(...))` und in JavaScript mit
`DataView`.

---

## 1a. Container, Fassung 3 (Web 5.0.0 bis 10.3.0) — wird weiterhin gelesen

| Bytes   | Inhalt                                                          |
|---------|-----------------------------------------------------------------|
| 0–7     | Magie: ASCII `EDBAK2` + `0x00` + Formatversion `0x03`           |
| 8       | Flag: `1` = Inhalt gzip-komprimiert, `0` = roh                  |
| 9–12    | **Rundenzahl der Schlüsselableitung** (4 Bytes, big endian)     |
| 13–28   | Salt für die Schlüsselableitung (16 Bytes, zufällig)            |
| 29–40   | AES-GCM-Initialisierungsvektor (12 Bytes, zufällig)             |
| ab 41   | Chiffretext, die letzten 16 Bytes sind das GCM-Auth-Tag         |

- **Schlüssel:** `PBKDF2-SHA256(Backup-Passwort, Salt, Runden aus dem Kopf, 32 Bytes)`
- **Verfahren:** AES-256-GCM; die ersten **13 Bytes** (Magie + Flag + Runden)
  sind als *additional authenticated data* gebunden. Jede Änderung am Kopf oder
  am Inhalt lässt die Entschlüsselung scheitern — kein stilles Korrumpieren.
  Insbesondere lässt sich die Rundenzahl nicht fälschen.
- **Klartext:** JSON (UTF-8), bei gesetztem Flag gzip-komprimiert.

**Warum die Rundenzahl in den Kopf gehört.** Sie stand bis Web 4.7.0 nur als
Konstante im Code. Wer sie anhebt, macht damit jede bereits erzeugte
Sicherungsdatei unlesbar — und zwar ohne Fehlermeldung, die den Grund nennt: Es
sieht aus wie ein falsches Passwort. Sicherungen werden aber gerade für den
Fall aufbewahrt, dass etwas schiefgeht; eine Datei, die genau dann nicht mehr
aufgeht, ist keine.

## 1b. Container, Fassung 2 (bis Web 4.7.0) — wird weiterhin gelesen

| Bytes   | Inhalt                                                          |
|---------|-----------------------------------------------------------------|
| 0–7     | Magie: ASCII `EDBAK2` + `0x00` + Formatversion `0x02`           |
| 8       | Flag: `1` = Inhalt gzip-komprimiert, `0` = roh                  |
| 9–24    | Salt für die Schlüsselableitung (16 Bytes, zufällig)            |
| 25–36   | AES-GCM-Initialisierungsvektor (12 Bytes, zufällig)             |
| ab 37   | Chiffretext, die letzten 16 Bytes sind das GCM-Auth-Tag         |

Rundenzahl: **immer 310 000**, nirgends vermerkt. AAD: die ersten 9 Bytes.
Die Fassungsnummer ersetzt die fehlende Angabe — deshalb bleiben diese Dateien
lesbar. Geschrieben werden sie nicht mehr.

Eine Datei mit einer Fassungsnummer, die diese Installation nicht kennt, wird
nicht als „Passwort falsch" gemeldet, sondern als „stammt aus einer neueren
Fassung" — sonst sucht die lesende Person den Fehler an der falschen Stelle.
Ein einzeln vorgelegtes **Teil** (Fassung 4) bekommt eine eigene Meldung: Es
ist kein Bestand, sondern ein Stück, und die Meldung sagt, wonach zu suchen
ist.

Entschlüsselung von Hand (Beispiel, Python; behandelt beide Fassungen):

```python
import hashlib, gzip, json, struct
from cryptography.hazmat.primitives.ciphers.aead import AESGCM

b = open('backup.edbak', 'rb').read()
assert b[0:6] == b'EDBAK2'
version = (b[6] << 8) | b[7]
if version == 3:
    kopf, runden = 13, struct.unpack('>I', b[9:13])[0]
elif version == 2:
    kopf, runden = 9, 310_000
else:
    raise SystemExit(f'Unbekannte Containerversion {version}')

key = hashlib.pbkdf2_hmac('sha256', passwort.encode(),
                          b[kopf:kopf+16], runden, 32)
roh = AESGCM(key).decrypt(b[kopf+16:kopf+28], b[kopf+28:], b[0:kopf])
daten = json.loads(gzip.decompress(roh) if b[8] == 1 else roh)
```

## 1c. Unlesbare Angaben (seit Web 4.1.0)

Der Export entschlüsselt die geschützten Angaben, bevor er die Datei
versiegelt — das ist der Grund, warum sich ein Backup in **jedes** Konto
einspielen lässt. Wenn ein Einsatz sich mit dem aktuellen Inhaltsschlüssel
*nicht* entschlüsseln lässt, gilt seit Web 4.1.0:

* Der **Chiffretext bleibt in der Datei** (Feld `pat_blob` neben dem
  Kennzeichen `pat_unreadable`).
* Der Export **nennt die Zahl** der betroffenen Einsätze in der Meldung.

Vorher wurde der Chiffretext auch im Fehlerfall entfernt und der Vorgang als
„Fertig" gemeldet. Das war die gefährlichste Stelle des ganzen Formats: In der
Datenbank lagen die Daten noch und wären mit dem richtigen Schlüssel lesbar
gewesen — in der Datei waren sie weg. Und wer den Verdacht hat, dass mit
seinen Daten etwas nicht stimmt, erstellt als Erstes eine Sicherung. Genau
diese Handlung vollendete den Verlust.

**Was mit dem mitgeführten Chiffretext beim Einspielen geschieht:** Er wird
unverändert übernommen — umschlüsseln ist unmöglich, der Klartext wurde nie
gesehen. Zurück in dasselbe Konto gespielt, sind die Angaben damit wieder
lesbar.

Ob es dasselbe Konto ist, entscheidet seit Web 4.1.2 das Feld `pat_key_check`
im Kopf der Datei (siehe Abschnitt 2): Es enthält die Prüfsumme des
Inhaltsschlüssels, mit dem die Sicherung erstellt wurde. Stimmt sie mit der des
Zielkontos überein, sind die Angaben dort lesbar. Stimmt sie nicht oder fehlt
sie (Dateien vor Web 4.1.2), fragt das Einspielen ausdrücklich nach und
übernimmt die Angaben nur nach Bestätigung — sie sind dann vorhanden, aber
nicht lesbar.

Die Prüfsumme verrät nichts über den Schlüssel: Er ist 256 Bit Zufall und aus
einem Hashwert nicht zurückrechenbar.

## 2. Inneres JSON

**Nutzlastversion 8 (seit Web 11.0.0).** Sie trägt **keine Punktlisten mehr**:
Jedes spurtragende Objekt hat statt `track` eine `spur_ref` und die Angaben
`stufe`, `n_original` und `n`; die Punkte stehen als SPUR1-Blobs in den
Spurteilen des Containers (Abschnitt 1.2).

> **Die Fassung entscheidet, welchen Weg der Rückweg nimmt** — nicht die
> Anwesenheit eines `track`-Feldes. Eine Spur ohne Punkte sähe genauso aus wie
> ein Verweis, und dann liefe eine Fassung-8-Datei still in den alten Zweig
> und verlöre alle Spuren. `api/backup_restore.php` prüft deshalb nach unten
> **und nach oben**: unter 6 abgelehnt (s. u.), über 8 ebenfalls, mit der
> Meldung „stammt aus einer neueren Fassung".

**Nutzlastversion 7 (Web 8.0.0 bis 10.3.0).** Der Container blieb Version 3,
die Signatur `EDBAK2` unverändert — geändert hatte sich allein der **Inhalt**:
Die Datei führt seither den **Papierkorb**. Gelöschte Einsätze, Ruhesegmente und Diensttage stehen
darin und kommen beim Einspielen als Papierkorbeinträge zurück. Bis Version 6
fehlten sie ganz; eine Wiederherstellung leerte den Papierkorb endgültig.

**Version 6 (seit Web 6.0.0)** war der Umbau auf Diensttage: Der Flugtag ist
zum Diensttag mit eigener Kennung geworden, die Besatzung ist normalisiert,
und der Standort ist der Anker der Stammdaten. Version-6-Dateien werden
**weiterhin gelesen** und vollständig eingespielt — ihnen fehlt nichts, was
sich erraten müsste, sie beschreiben schlicht einen Bestand ohne gelöschte
Einträge.

> **Der Sprung auf 7 kennzeichnet, er sperrt nicht.** Die Annahmeschranke in
> `api/backup_restore.php` steht unverändert bei „ab Version 6". Ein bereits
> **ausgelieferter** Stand (Web 7.3.1 und älter) hat dieselbe Schranke,
> wertet `deleted_at` aber nicht aus — er nimmt eine Version-7-Datei an und
> bringt deren Papierkorb als **aktiven Bestand** zurück. Das lässt sich
> nachträglich nicht verhindern: Eine Sperre hätte in jenen Ständen stehen
> müssen. Wer eine Version-7-Sicherung in eine ältere Installation einspielt,
> muss damit rechnen und den Papierkorb dort anschließend von Hand leeren.

**Nutzlasten der Version 5 und älter werden nicht mehr eingelesen.** Das ist
eine bewusste Entscheidung und kein Versäumnis: Einer alten Datei fehlen die
Kennung des Diensttags (der Kalendertag *war* sie), die Art des
Rettungsmittels, der Rollensatz, die Standortzuordnung der Stammdaten und die
Uhr-Kennungen. Jede dieser Lücken ließe sich nur mit einer Annahme füllen — und
eine Wiederherstellung ist der falsche Ort für Annahmen, weil wer sie startet
meist keinen zweiten Versuch hat.

Die Ablehnung ist deshalb **ausdrücklich und benannt** (`error: version_alt`,
HTTP 409) und nicht ein Fehler beim Einlesen. Sie kommt vom Server und nicht
schon im Browser: Der Container ist unverändert, die Datei ließ sich also
entsiegeln. Wer eine alte Datei hat, spielt sie in einer Installation vor Web
6.0.0 ein und sichert dort neu.

Im Kopf der Datei steht neben `format`, `version`, `created_at` und `user`
seit Web 4.1.2 auch:

```jsonc
  // Prüfsumme des Inhaltsschlüssels, mit dem diese Sicherung erstellt wurde.
  // Dient beim Einspielen der Frage, ob mitgeführter Chiffretext im Zielkonto
  // lesbar wäre. `null` bei Konten aus der Zeit vor Web 4.0.0.
  "pat_key_check": "3f2a…"   // 32 Hexzeichen oder null
```

```jsonc
{
  "format": "einsatzdoku-backup",       // Kennung, immer dieser Wert
  "version": 8,                         // 8 = Verweise, 6/7 = Punktlisten
  "app": "einsatzdoku-notarzt",         // Kennung der Anwendung
  "created_at": "2026-07-20T18:00:00+00:00",   // Export-Zeitpunkt (UTC)
  "user": { "email": "...", "name": "..." },   // Herkunftskonto, wird beim
                                               // Einspielen angezeigt

  "stammdaten": {
    // Standorte tragen seit Version 6 optionale Koordinaten (Quelle des
    // Abfahrtorts "Standort"). Der NAME bleibt der portable Schlüssel, an dem
    // alle übrigen Stammdaten hängen.
    "bases":        [ { "name": "Kempten", "lat": 47.72, "lon": 10.31,
                        "is_default": 1 } ],

    // Rettungsmittel (bis Version 5: "aircraft" mit der Spalte "registration"
    // und fünf Rollen-Flags). Art, Rollensatz und Fähigkeiten gehören dazu;
    // der Standort steht als NAME, weil Kennungen nur in der Datenbank gelten,
    // aus der die Sicherung stammt. `kind` ist "air" oder "ground"; zwei
    // Beispiele, damit beide Arten zu sehen sind.
    //
    // ROLLENKENNUNGEN sind die des Katalogs (CREW_ROLES in server/db.php):
    // p1, p2, hems, fr (luftgebunden), driver, trainee (bodengebunden) und
    // other (beide). Ihre Beschriftungen stehen in docs/Export-Format.md 3.8.
    // Die Liste kommt SORTIERT aus der Datenbank — `ORDER BY role_code`, also
    // alphabetisch, nicht in der Reihenfolge des Katalogs. Sie bedeutet
    // nichts; wer sie ausliest, sortiert selbst.
    "vehicles":     [ { "name": "Christoph 17", "kind": "air",
                        "base_ref": "Kempten",
                        "roles": ["fr", "hems", "other", "p1", "p2"],
                        "capabilities": ["winch", "bergwacht"],
                        "is_default": 1 },
                      { "name": "NEF Kempten 1", "kind": "ground",
                        "base_ref": "Kempten",
                        "roles": ["driver", "other", "trainee"],
                        "capabilities": [],
                        "is_default": 0 } ],

    // Auswahl ZENTRALER Standorte dieser NutzerIn, als Namensliste. Zentrale
    // Standorte selbst gehören dem Konto nicht und werden nicht exportiert —
    // die Auswahl schon, sonst stünden nach dem Einspielen leere Listen da.
    "user_bases":   [ "Zentrale Wache Süd" ],

    // Alle übrigen Stammdaten tragen ihren Standort (base_ref). Ohne ihn ließe
    // sich nach dem Einspielen nicht entscheiden, zu welchem Standort eine
    // Zielklinik gehört.
    "crew_presets": [ { "role_code": "p1", "name": "…", "base_ref": "Kempten" } ],
    "bw_units":     [ { "name": "Bereitschaft Oberstdorf", "base_ref": "Kempten" } ],
    "resources":    [ { "name": "RTW Kempten 21/83", "base_ref": "Kempten" } ],
    "transport_dests": [ { "name": "Klinikum Kempten", "lat": 47.72, "lon": 10.31,
                           "base_ref": "Kempten" } ]
  },

  // Diensttage (bis Version 5: Flugtage, mit dem Datum als Schlüssel).
  //
  // ANGEZEIGT UND GESICHERT WERDEN DIE SNAPSHOT-SPALTEN: vehicle_name und
  // base_name stehen im Diensttag selbst und sind beim Anlegen eingefroren.
  // Die Verweise auf die Stammdaten laufen zusätzlich als NAMEN mit
  // (vehicle_ref, base_ref), damit das Einspielen sie wieder verknüpfen kann;
  // sie können leer sein, wenn der Stammdatensatz inzwischen fehlt.
  "days": [ {
    "day": "2026-07-19",                  // Datum des DIENSTBEGINNS, nur
                                          // Sortierung und Anzeige
    "started_at": "2026-07-19 05:00:00",  // DATETIME, UTC
    "ended_at":   "2026-07-19 17:30:00",
    "kind": "air",                        // null = neutral, noch nicht zugeordnet
    "vehicle_name": "Christoph 17",       // eingefroren
    "base_name": "Kempten",               // eingefroren
    "base_lat": 47.72, "base_lon": 10.31, // eingefroren
    "vehicle_ref": "Christoph 17", "base_ref": "Kempten",   // Stammdaten-Verweis
    "notes": "…",

    // PAPIERKORB (seit Version 7). null = aktiv; ein Zeitstempel = der Tag
    // liegt im Papierkorb. Der ZEITPUNKT wird beim Einspielen NICHT
    // übernommen — siehe „Der Papierkorb in der Datei" unten.
    "deleted_at": null,                   // DATETIME (UTC) oder null

    // Besatzung des Diensttags, je Rolle ein Eintrag. Die SCHLÜSSELMENGE ist
    // der eingefrorene Rollensatz — auch leere Rollen stehen darin, denn sie
    // sagen aus, welche Rollen dieser Dienst überhaupt anbot. Kennungen und
    // Sortierung wie bei "vehicles" oben.
    "crew": { "fr": "…", "hems": "…", "other": null, "p1": "…", "p2": null },

    // Eingefrorene Fähigkeiten des Rettungsmittels. Sie steuern, welche
    // Einsatzfelder der Diensttag zeigt; wird der Windenhaken Jahre später
    // entfernt, verlieren alte Einsätze ihre Windendokumentation nicht.
    "capabilities": ["winch"],

    // Uhr-Kennungen dieses Diensttags. MEHRERE sind zulässig — nach dem
    // Zusammenführen zweier Diensttage trägt der Zieltag die Kennungen beider.
    // Sie MÜSSEN in die Sicherung: Ohne sie legte ein später eintreffender
    // Upload derselben Uhr den Diensttag nach einer Wiederherstellung erneut
    // an. device_id ist die ÖFFENTLICHE Gerätekennung; null = Gerät gelöscht.
    "refs": [ { "day_ref": "d-41-0938175520", "device_id": "watch-001" } ],

    // Kennung INNERHALB DIESER DATEI. missions[].day_id und
    // rest_segments[].day_id verweisen darauf; beim Einspielen wird sie auf
    // die neu vergebene Kennung umgeschrieben. Siehe Abschnitt 4.
    "id": 17
  } ],

  "missions": [ {
    "client_ref": "m-42-1837704912",    // eindeutige Referenz (Dubletten-Schutz)
    "day_id": 17,                       // Verweis auf den Diensttag DIESER
                                        // Datei; beim Einspielen auf die neu
                                        // vergebene Kennung umgeschrieben
    "started_at": "2026-07-19 08:15:00",  // DATETIME, UTC
    "ended_at":   "2026-07-19 09:02:00",  // null = kein Abschluss
    "manual": 0, "final": 1,
    "origin": "watch", "edited": 0,        // seit Version 4 (Herkunft/Bearbeitungsstatus)
    "created_at": "2026-07-19 08:16:12",   // Anlegezeitpunkt der ZEILE (UTC)
    "distance_m": 38400, "ascent_m": 550,
    "site_ele_m": 712,                    // NICHT uebernommen (s. Hinweis unten)

    // PAPIERKORB (seit Version 7). deleted_with_day = 1 heisst: mit dem
    // ganzen Diensttag gelöscht, erscheint nicht einzeln im Papierkorb und
    // kehrt mit ihm gemeinsam zurück. Beide Felder standen schon vorher in
    // der Datei, waren aber immer null/0.
    "deleted_at": null, "deleted_with_day": 0,

    // Transport und Zielklinik (seit Version 6). transport_mode ist ein
    // ENUM('air','ground','ambulant'); bei "ambulant" entfallen Zielklinik,
    // Schockraum und NA-Begleitung.
    "transport_mode": "ground", "na_escort": 1, "false_alarm": 0,
    "transport_dest": "…", "dest_lat": 47.72, "dest_lon": 10.31,
    "schockraum": 0, "secondary": 0,

    // Regel, aus der der Abfahrtort abgeleitet wird — nicht die Koordinate:
    // "base" | "prev_site" | "prev_dest" | "manual" | null. Ein MANUELLER
    // Abfahrtort liegt verschlüsselt im pat-Block (siehe pat.start unten).
    "start_src": "base",

    "winch": 0, "winch_cycles": null, "winch_cycles_pat": null,
    "winch_airload": 0, "bergwacht": 0, "bw_unit": null, "bw_info": null,
    "other_ema": null, "notes": null,

    // Abweichende Besatzung (seit Version 6 als Objekt role_code => name; bis
    // Version 5 waren es fünf feste Spalten). crew_override = 0 -> das Objekt
    // ist leer und der Einsatz erbt die Besatzung seines Diensttags. Die
    // Rollenkennungen stammen aus dem festen Katalog im Code:
    // p1, p2, hems, fr, other (luftgebunden) und driver, trainee, other
    // (boden). Anders als bei "days" stehen hier NUR belegte Rollen — es sind
    // Abweichungen, keine Leerzeilen.
    "crew_override": 0,
    "crew": { },

    // Geschützte Angaben — im Container KLARTEXT (der Container selbst ist
    // ja verschlüsselt). Beim Import werden sie mit dem Inhaltsschlüssel des
    // Zielkontos verschlüsselt und als `pat_blob` gespeichert.
    "pat": { "dx": "Polytrauma", "age": 41, "mission_no": "2026-0042",
             "loc": { "addr": "Ringstr. 18, 87439 Kempten",
                      "lat": 47.72, "lon": 10.31 },
             "site_desc": "Zufahrt über Forstweg, letzte 300 m zu Fuß",
             "start": { "addr": "Wache Kempten", "lat": 47.72, "lon": 10.31 } },
                                            // site_desc seit Version 5,
                                            // start seit Version 6 (manueller
                                            // Abfahrtort, nur bei
                                            // start_src = "manual")
    // Ließ sich ein Einsatz beim Export NICHT entschlüsseln, steht statt
    // `pat` das Kennzeichen `pat_unreadable` und — seit Web 4.1.0 — der
    // unveränderte Chiffretext `pat_blob` in der Datei:
    // "pat_unreadable": true,
    // "pat_blob": "edk1:Base64 …"   -> unverändert übernommener Chiffretext
    //
    // Seit Web 5.1.0 trägt ein neu geschriebener Chiffretext die Formatkennung
    // `edk1:` (M2-10). Ältere tragen sie nicht — beide Formen sind gültig und
    // stehen dauerhaft nebeneinander, weil der Server sie nicht nachtragen
    // kann (er hat den Schlüssel nach Bauart nicht). Der Einspielweg nimmt
    // beide an.

    "phases": [ { "phase": 2, "occurred_at": "2026-07-19 08:15:00",
                  "lat": 47.72, "lon": 10.31 } ],
    "resus": [ { "started_at": "2026-07-19 08:40:00",
                 "events": [ { "type": "adrenalin",
                               "occurred_at": "2026-07-19 08:43:00" } ] } ],
    "resources": [ "RTW 1", "First Responder" ],   // Namen aus den Stammdaten

    // AB NUTZLAST 8: ein Verweis statt der Punkte. Die Spur selbst steht als
    // SPUR1-Blob im Spurteil (Abschnitt 1.2). `spur_ref` ist eine laufende
    // Nummer DIESES Exportvorgangs und sonst nichts — die Datenbankkennung
    // gälte nur in der Datenbank, aus der die Sicherung stammt (E9, E15).
    //
    // WER KEINE SPUR HAT, BEKOMMT KEINE `spur_ref`. Ein leeres Feld sähe aus
    // wie „hat keine Spur"; die Fassung sagt, dass die Punkte woanders stehen.
    "spur_ref": 1, "stufe": 2, "n_original": 443, "n": 443

    // BIS NUTZLAST 7 stand hier stattdessen die Punktliste:
    // "track": [ [0, 47.72, 10.31, 712.5, 1721383200] ]
    //             seq  lat    lon    ele    ts(Unix-Sekunden UTC); ele kann null sein
  } ],

  "rest_segments": [ {
    "client_ref": "r-…", "day_id": 17,
    "started_at": "…", "ended_at": "…", "final": 1,
    "deleted_at": null, "deleted_with_day": 0,   // Papierkorb, seit Version 7
    "spur_ref": 82, "stufe": 2, "n_original": 129, "n": 129
  } ]
}
```

### Woher die Punkte kommen (seit Web 10.0.0, neu gefasst mit Web 11.0.0)

Serverseitig liegen die Punkte seit Web 10.0.0 nicht mehr nur als Zeilen in
`track_points`, sondern je nach Alter als **Blob** in `track_blobs` (Format
SPUR1, `docs/Technik.md` 4.97). **Seit Web 11.0.0 gibt die Sicherung sie
genauso weiter**, statt sie auszupacken: Der Spurteil trägt den Blob, wie er
liegt.

**Eine Zusage hat sich dabei geändert, und das gehört gesagt.** Bis Web 10.3.0
hieß es hier: „die Sicherung nimmt den Datenbankstand und kodiert nicht neu".
Das gilt so nicht mehr:

| Bestand | in der Datei |
|---|---|
| Stufe 1 (nur Zeilen) | **Stufe 2** — verlustfrei kodiert beim Sichern |
| Stufe 2, dazu nachgereichte Zeilen | **Stufe 2** — zusammengesetzt und neu kodiert |
| Stufe 2 ohne Nachzügler | Stufe 2 — der Blob, wie er liegt |
| Stufe 3 (ausgedünnt) | Stufe 3 — der Blob, wie er liegt |

Der **Bestand** bleibt dabei unangetastet; nur die Datei trägt die Spur
kodiert. Der Grund: Ohne das müsste die Datei zwei Spurformen führen und der
Rückweg zwei Wege haben, und der ganze Mengengewinn entfiele für frische
Bestände — gerade die mit den meisten Punkten.

**Eine Folge, die man kennen sollte:** Eine so kodierte Stufe-1-Spur kommt
beim Einspielen als Blob zurück und überspringt damit die Karenzfrist
(E-S2-06). Für die Spur ist das folgenlos — sie ist verlustfrei —, aber der
Bestand steht danach eine Stufe weiter, als er stand.

**Drei Fälle werden abgelehnt statt still halbiert** (`spur_lib.php`,
`spur_fuer_sicherung_viele()`): eine ausgedünnte Spur *mit* nachgereichten
Zeilen (die Nummern der beiden Teile meinen Verschiedenes), eine Lücke in den
Punktnummern (die Position *ist* die Nummer) und eine Spur über **50 000
Punkten** (die Grenze, die auch der Rückweg zieht — `LIMIT_TRACKPUNKTE_SPUR`
in `validate_lib.php`; abgelehnt wird die ganze Spur, nicht gekappt). Jeder
dieser Fälle wird beim Sichern **benannt**.

**Eine Folge davon gehört gesagt:** Die Blob-Stufen legen die Koordinaten mit
**10⁻⁶ Grad** (≈ 0,11 m) und die Höhe mit **0,1 m** ab. Eine Sicherung aus
einem verdichteten Bestand trägt also gerundete Werte — nicht, weil die
Sicherung rundet, sondern weil der Bestand es tut. Nachgemessen: Über den
Referenzdatensatz (55 861 Punkte) ist die Sicherung vor und nach der
Verdichtung **identisch**, weil die Uhr ohnehin nicht feiner liefert, als das
Format ablegt.

Ab sechs Monaten nach Einsatzende steht in der Datei die **ausgedünnte** Spur
(Stufe 3, E-S2-13) — die Sicherung nimmt den Datenbankstand und kodiert nicht
neu. Seit Web 10.2.0 tut sie das wirklich; bis dahin war es beschrieben, aber
es gab keinen Job, der ausdünnte.

**Was das für die Nummern bedeutet.** In einer ausgedünnten Spur sind die `seq`
der Datei **nicht mehr lückenlos gegen das Original**: Sie zählen die
gespeicherten Punkte durch, nicht die ursprünglichen. Der Rückweg setzt keine
bestimmte Nummernfolge voraus, sondern nur Lückenlosigkeit *innerhalb der
Datei* und keine Dubletten — beides gilt. Die Punktzahl in der Datei ist
entsprechend kleiner als die ursprünglich aufgezeichnete; gemessen bleiben rund
32 bis 41 % der Punkte, je nach Bestand.

**Nicht rückgängig zu machen.** Wer eine Sicherung aus einem ausgedünnten
Bestand einspielt, bekommt die ausgedünnte Spur — das Original ist auf dem
Server nicht mehr vorhanden. Wer den vollen Stand behalten will, sichert
**vor** Ablauf der sechs Monate.

**Ein wiederhergestellter Bestand wird von neuem verdichtet und ausgedünnt.**
Die eingespielten Einsätze tragen ihre alten Daten; der Verdichtungsjob hält
sie nach der Karenz für reif, und was älter als sechs Monate ist, dünnt der
Ausdünnungsjob aus. Das ist gewollt (E-S2-03), sollte aber wissen, wer eine
alte Sicherung zur Ansicht einspielt: `php jobs.php --pause 1800` hält die
Jobs so lange still (`docs/Technik.md`, 4.97a).

### SPUR1 von Hand lesen

Wer einen Blob außerhalb der Anwendung öffnen will — etwa aus einem
Komplettbackup der Datenbank —, braucht kein eigenes Werkzeug:

```python
import struct, zlib

def spur1_lesen(blob: bytes):
    assert blob[:2] == b"SP", "kein SPUR-Blob"
    fassung, stufe, aufl = blob[2], blob[3], blob[4]
    assert fassung == 1 and aufl == 1, f"Fassung {fassung}, Auflösung {aufl}"
    n_original, n = struct.unpack("<II", blob[5:13])
    roh = zlib.decompress(blob[13:])

    def spalte(pos, anzahl):
        werte, lauf = [], 0
        for d in struct.unpack(f"<{anzahl}i", roh[pos:pos + 4 * anzahl]):
            lauf += d
            werte.append(lauf)
        return werte, pos + 4 * anzahl

    lat, pos = spalte(0, n)
    lon, pos = spalte(pos, n)
    bits = roh[pos:pos + (n + 7) // 8]; pos += (n + 7) // 8
    hat = [bool(bits[i // 8] & (1 << (i % 8))) for i in range(n)]
    hoehen, pos = spalte(pos, sum(hat))
    ts, pos = spalte(pos, n)

    h = iter(hoehen)
    return [(i, lat[i] / 1e6, lon[i] / 1e6,
             next(h) / 10 if hat[i] else None, ts[i]) for i in range(n)]
```

Dieselben drei Zeilen gehen in PHP mit `unpack('l*', gzuncompress(...))` und
in JavaScript mit `DataView` — deshalb ist das Format so und nicht mit einer
Varint-Kodierung gebaut, die 0,2 Byte je Punkt gespart hätte (E-S2-04).

### Der Papierkorb in der Datei (seit Version 7)

Drei Felder tragen ihn: `days[].deleted_at`, `missions[].deleted_at` /
`missions[].deleted_with_day` und dieselben zwei an den Ruhesegmenten. Sie
standen schon in Version 6 in jeder Datei, waren dort aber ausnahmslos `null`
beziehungsweise `0` — die Abfragen filterten Gelöschtes vorher weg.

**Übernommen wird der Zustand, nicht der Zeitpunkt.** Beim Einspielen
entscheidet allein, *ob* `deleted_at` gesetzt ist; der Wert selbst wird
verworfen und durch den **Einspielzeitpunkt** ersetzt. Die 90-Tage-Frist
beginnt damit neu.

Das ist eine Entscheidung, keine Nachlässigkeit, und sie folgt derselben Linie
wie `origin`: Der Eintrag **entsteht in dieser Installation neu**. Die
Gegenrechnung wäre, den alten Zeitpunkt zu übernehmen — dann könnte eine
Sicherung Einträge mitbringen, deren Frist längst abgelaufen ist, und der
nächste Aufräumjob löschte sie endgültig, ohne dass jemand sie je gesehen
hätte. Eine Wiederherstellung, die Daten einspielt und wenige Stunden später
selbst wieder entfernt, wäre die schlechtere Bauart.

**`deleted_with_day` ist eine UND-Verknüpfung aus Datei und Zieltag.**
Geschrieben wird `1` nur, wenn der Eintrag in der **Datei** am Tag hing **und**
der Diensttag, dem er nach der Zuordnung zufällt, selbst im Papierkorb liegt;
sonst `0` (einzeln gelöscht). Damit kann kein Eintrag mit
`deleted_with_day = 1` an einem aktiven Tag entstehen — der wäre im Papierkorb
unsichtbar (`trash_list_missions()` zeigt nur `deleted_with_day = 0`) und über
den Tag nicht wiederherstellbar (`trash_restore_day()` holt nur zurück, was am
gelöschten Tag hängt). Und ein einzeln gelöschter Eintrag wird nicht zum
mitgelöschten, nur weil sein Zieltag ebenfalls im Papierkorb liegt. Details
in Abschnitt 3.

### Feldkonventionen

- Zeitstempel `started_at`/`ended_at`/`occurred_at`: `YYYY-MM-DD HH:MM:SS` in
  **UTC**; Trackpunkt-`ts` ist Unix-Epoche in Sekunden (UTC).
- `day` ist das **lokale** Kalenderdatum des Beginns (Tageswechsel 0:00).
- Zusatzfelder der Einsätze folgen `server/mission_fields.php`; künftige
  Versionen können Felder ergänzen (Import ignoriert Unbekanntes).
- `is_default` bei `bases`/`vehicles` (bis Nutzlast 5 hieß der Block
  `aircraft`): intern seit Version 3 in einer
  nutzerbezogenen Tabelle (`user_defaults`) abgelegt, im Exportformat aber
  weiterhin als Flag je Zeile abgebildet (Abwärtskompatibilität).
- **Zentrale (globale) Stammdaten** (vom Admin gepflegt, seit Version 3)
  gehören nicht dem Konto und werden **nicht** exportiert. Beim Import werden
  Einträge, die zentral bereits (case-insensitiv) vorhanden sind, still
  übersprungen und in der Ergebnismeldung gezählt.
- **`origin`** (`watch`/`manual`/`import`, seit Version 4): Herkunft des
  Einsatzes, wird beim Anlegen einmalig gesetzt. **`edited`** (seit Version
  4): wurde der Einsatz nach dem Anlegen verändert. Wie bei Version 3 gilt:
  ältere Backups bleiben lesbar — fehlen die Felder (Version ≤ 3), werden sie
  beim Import aus `client_ref` abgeleitet: Präfix `man-` → `origin=manual`,
  `imp-` → `origin=import`, sonst `origin=watch`; `edited=1` nur, wenn
  `manual=1` und keines der beiden Präfixe zutrifft, sonst `edited=0`.

## 3. Import-Verhalten

### Zwei Schritte statt einem (seit Nutzlast 8)

Eine Fassung-4-Datei wird in zwei Zügen eingespielt, und zwar in dieser
Reihenfolge:

1. **Manifest öffnen und die Teileliste prüfen** — *bevor* irgendetwas
   angelegt wird. Ein fehlendes Teil soll auffallen, solange noch nichts
   geschehen ist, nicht auf halbem Weg.
2. **Kern senden** (`api/backup_restore.php`). Der Server legt an wie bisher
   und liefert die **Spurkarte** zurück: `spur_ref` → angelegter Datensatz.
   Sie steht getrennt von der Rückmeldung an die Nutzerin — sie ist eine
   Arbeitsangabe für den nächsten Zug, keine Auskunft.
3. **Spurteile senden** (`api/backup_spuren_restore.php`), in Häppchen von
   höchstens 1,5 MB. Der Server prüft Eigentum und Blob, schreibt über
   `spur_lib.php` und **überspringt Vorhandenes** — eine abgebrochene
   Wiederherstellung lässt sich damit fortsetzen.

**Eine `spur_ref` ohne Ziel ist kein Fehler**, sondern der Normalfall beim
zweiten Einspielen derselben Datei: Der Einsatz war schon da und wurde
übersprungen, also gibt es für seine Spur keine neue Kennung. Gezählt und
gemeldet wird es trotzdem.

**Die Höhe des Einsatzortes entsteht erst im dritten Zug**, weil sie aus der
Spur gerechnet wird und die dann erst da ist.

### Allgemein

- Import immer in das **eigene, angemeldete** Konto; bestehende Daten werden
  nie überschrieben.
- Dubletten-Erkennung: Einsätze und Ruhesegmente über `client_ref`, Diensttage
  über eine bereits vorhandene `client_ref` eines ihrer Einsätze und ersatzweise
  über einen Fingerabdruck aus Datum, Beginn, Ende, Art und den eingefrorenen
  Bezeichnungen, Stammdaten über ihre Namen — Vorhandenes wird übersprungen,
  nur Fehlendes ergänzt. Der Import ist damit gefahrlos wiederholbar.
- **Die Wiedererkennung eines Diensttags über die Einsatzkennungen verlangt
  Eindeutigkeit** (seit Web 8.0.0). Nachgeschlagen werden **alle**
  `client_ref` des Datei-Tags, und nur auf **aktive** Zieltage. Genau ein
  Ergebnis wird benutzt; führen sie auf **mehrere verschiedene** Diensttage —
  weil jemand einen der Einsätze verschoben hat —, gilt dieser Schritt als
  ergebnislos, der Fingerabdruck entscheidet, und der Widerspruch wird als
  `tag_mehrdeutig` gezählt. Vorher gewann der erste Treffer und verhängte
  seinen Diensttag über den ganzen Datei-Tag.
- Die geschützten Angaben werden vor dem Senden im Browser mit dem
  Inhaltsschlüssel des Zielkontos verschlüsselt; der Server speichert nur
  Chiffretext.
- Standard-Markierungen (★) werden nur importiert, wenn noch kein Standard
  gesetzt ist (es bleibt bei genau einem).

### Der Papierkorb beim Einspielen (seit Version 7)

Was in der Datei gelöscht ist, kommt **als Papierkorbeintrag** zurück — nicht
als aktiver Bestand. Fünf Regeln entscheiden das im Einzelnen:

1. **Der Zustand kommt aus der Datei, der Zeitpunkt aus diesem Lauf.** Alle
   Einträge eines Einspielvorgangs tragen denselben `deleted_at` — den des
   Vorgangs. Die Frist beginnt neu (Abschnitt 2, „Der Papierkorb in der
   Datei"). Die Rückmeldung nennt die Zahlen und sagt den Fristbeginn
   ausdrücklich.

2. **`deleted_with_day` ist eine UND-Verknüpfung** aus dem Wert der Datei und
   dem Zustand des Zieltags: `1` nur, wenn der Eintrag **in der Datei** am Tag
   hing **und** der Diensttag, dem er hier zufällt, selbst im Papierkorb liegt;
   sonst `0`. Beide Hälften sind nötig, und keine reicht allein:

   - Ein in der Datei **mitgelöschter** Einsatz, dessen Zieltag hier **aktiv**
     ist, kommt **einzeln gelöscht** an — sichtbar im Papierkorb und von dort
     wiederherstellbar. Die Gegenrechnung wäre ein Eintrag, den niemand mehr
     sieht und niemand mehr zurückholt.
   - Ein in der Datei **einzeln** gelöschter Einsatz an einem hier **ebenfalls
     gelöschten** Tag bleibt **einzeln gelöscht**. Er wird nicht zum
     Mitgelöschten, nur weil sein Tag zufällig auch im Papierkorb liegt: Er
     stünde sonst nicht mehr in der Papierkorbliste (die zeigt nur
     `deleted_with_day = 0`) und würde beim Wiederherstellen des Tages
     ungewollt wieder aktiv — er war ja vorher schon gelöscht.

   Kurz: Der Wert aus der Datei sagt, ob der Eintrag am Tag hing; der Zieltag
   sagt, ob das hier gelten kann. `deleted_with_day = 1` **setzt** einen
   gelöschten Zieltag voraus, folgt aber nicht aus ihm.

3. **Ein Diensttag im Papierkorb DES ZIELKONTOS blockiert weiterhin** — aber
   nur gegen **aktive** Datei-Tage desselben Datums. Sie werden übersprungen
   und gezählt (Grund `tag_im_papierkorb`); ihre Einsätze und Ruhesegmente
   ebenfalls (Grund `tag_uebersprungen`). Grund: Das Löschen war eine bewusste
   Handlung, und ein Einspielen soll sie nicht nebenbei rückgängig machen.

4. **Ein in der Datei gelöschter Tag** wird nicht am Ziel-Papierkorb gemessen —
   er will ja gar nicht aktiv werden. Er durchläuft die normale
   Wiedererkennung; wird er nicht gefunden, entsteht er als Papierkorbeintrag
   samt seinen mitgelöschten Einsätzen und Ruhesegmenten. Wird er gefunden,
   bleibt der Zieltag **unangetastet** — auch wenn er dort aktiv ist. „Angaben
   werden nicht überschrieben" gilt für den Löschzustand genauso wie für
   Rettungsmittel und Besatzung.

   Zwei gelöschte Tage desselben Datums (einer aus der Datei, einer im Ziel,
   verschiedener Fingerabdruck) dürfen nebeneinander bestehen. Der Papierkorb
   kennt keine Eindeutigkeit je Datum, und seit Web 6.0.0 gibt es sie auch bei
   den aktiven Tagen nicht mehr.

5. **Ein in der Datei AKTIVER Einsatz oder Ruhesegment, dessen Zieltag hier im
   Papierkorb liegt, wird abgelehnt** — übersprungen und gezählt (Grund
   `tag_im_papierkorb`). Das ist die Gegenrichtung zu Regel 2 und dieselbe
   Regel wie Nummer 3, eine Ebene tiefer: Was hier im Papierkorb liegt, nimmt
   nichts Neues auf.

   Ohne sie stünde der Eintrag an einem Tag, den die Tagesübersicht nicht
   zeigt — in der Suche und auf der Einsatzseite sichtbar, in Tagesliste,
   Zeitraum, Export, Nachbearbeitung und Papierkorb nicht; beim endgültigen
   Löschen des Tages bliebe er ohne Diensttag zurück. Halb sichtbar ist
   schlechter als unsichtbar.

   Die Datumsprüfung aus Regel 3 fängt den Fall **nicht** ab: Sie vergleicht
   Kalenderdaten, und die Wiedererkennung über `client_ref` kann auf einen
   Zieltag anderen Datums führen.

**Überspringgründe in der Rückmeldung:** `bereits_vorhanden`,
`datum_oder_zeit`, `aufbau`, `tag_im_papierkorb`, `tag_unbrauchbar`,
`tag_uebersprungen`, `tag_mehrdeutig`. Alle sieben haben eine Beschriftung; ein
roher Schlüssel erscheint nicht mehr. `tag_mehrdeutig` zählt **Diensttage**,
nicht Einsätze, und es ist kein Übersprungvorgang, sondern ein Hinweis: Der
Tag wurde eingespielt, nur eben über den Fingerabdruck statt über die
Einsatzkennungen. Ruhesegmente zählen ihre Gründe seit S1 mit — vorher
fielen sie unter den Tisch, obwohl „bereits vorhanden" bei ihnen die häufigste
Ursache überhaupt ist.

### Eine unbrauchbare Angabe kostet ihre Zeile, nicht den Lauf (seit Web 8.0.0)

Das Einspielen hängt an **einer** Transaktion: Was eine Datenbankausnahme
auslöst, reißt alles mit — auch die neunzig heilen Einsätze daneben, und der
Aufrufer sieht statt einer Bilanz nur eine Fehlermeldung. Jede Angabe aus der
Datei läuft deshalb durch die Prüfschicht, und was sie nicht passiert, kostet
seine Zeile beziehungsweise seinen Punkt und erscheint gezählt unter
`rejected`. Drei Stellen taten das bis Web 7.3.1 nicht:

- **Ruhesegmente hatten gar keine Prüfschicht.** `started_at` und `ended_at`
  gingen roh gegen `DATETIME NOT NULL`, `client_ref` ohne Längengrenze gegen
  `VARCHAR(64)`.
- **Die Spur eines Ruhesegments** wurde ungeprüft und unbegrenzt geschrieben;
  `(float)"Unfug"` ist `0.0`, aus einem unbrauchbaren Punkt wurde also still
  eine Koordinate im Golf von Guinea.
- **Doppelte Spurnummern.** `track_points` hat den Primärschlüssel
  `(owner_type, owner_id, seq)`. Zwei Punkte mit derselben Nummer lösen einen
  Schlüsselkonflikt aus; der zweite wird jetzt übersprungen und als
  `…track.seq: Nummer doppelt` gemeldet. Ein eigener Export erzeugt keine
  Wiedergänger — eine von Hand bearbeitete oder fremde Datei kann es.

## 4. Was NICHT in der Datei steht — und was nicht zurückkommt

Seit Web 4.5.2 ist das Format **aufgezählt** statt „alles, was in der Tabelle
steht". Vorher ergab es sich aus dem Datenbankschema: Jede neue Spalte war
automatisch in jeder Sicherung, ohne dass das jemand entschieden hätte.

**Nicht in der Datei:**

- `user_id`, `device_id` sowie die `id` von `missions` und `rest_segments` —
  interne Verweise. Sie gelten nur in der Datenbank, aus der die Sicherung
  stammt; eine Sicherung soll sich auch in ein anderes Konto und eine andere
  Installation einspielen lassen.

  **Ausnahme: `days[].id` steht sehr wohl in der Datei** (`backup_lib.php`,
  Abfrage der Diensttage). Sie muss darin stehen, denn `missions[].day_id`
  und `rest_segments[].day_id` verweisen darauf — ohne sie ließe sich nach
  dem Einspielen nicht sagen, welcher Einsatz zu welchem Dienst gehörte. Es
  ist eine Kennung **innerhalb dieser Datei**, keine Aussage über die
  Datenbank: Beim Einspielen wird sie auf die neu vergebene Kennung
  umgeschrieben. Bis Web 7.2.3 behauptete dieser Abschnitt das Gegenteil,
  und das Beispiel weiter oben zeigte den Schlüssel nicht.

- **`_spur_index`** (seit Web 11.0.0). Der Kern trägt beim Abruf ein
  Arbeitsfeld mit den Datenbankkennungen der Spuren — der Browser braucht sie,
  um die Blobs zu holen (`api/backup_spuren.php`). Es wird **entfernt, bevor
  versiegelt wird**, und trägt den Unterstrich, den dieses Projekt für solche
  Felder benutzt (`_pat`, `_patState`). In der Datei steht statt dessen die
  laufende `spur_ref`.

  Dass es wirklich draußen bleibt, ist geprüft: `tools/containerprobe/` sieht
  nach, ob im entsiegelten Kern noch ein Feld mit Unterstrich steht.
- `other_resources` (in `missions`) — tote Altspalte. Die weiteren
  Rettungsmittel liegen seit der Migration `2026_07` als einzelne Zeilen in
  `mission_resources` und stehen in der Datei unter `resources`. Die Spalte
  wurde damals nur nicht gelöscht und wanderte bis Web 4.5.1 leer mit.

**In der Datei, kommt beim Einspielen aber nicht zurück:**

- `site_ele_m` (Einsatzort-Höhe). Der Einspielweg schreibt die Felder aus
  `mission_fields.php` plus `pat_blob`; die Höhe steht dort nicht, weil sie
  beim Uhr-Upload aus dem Track gerechnet und nicht eingegeben wird. Sie bleibt
  in der Datei, damit diese den Bestand vollständig abbildet — der Wert aus der
  Datei wird aber **nicht übernommen**, sondern nach dem Einspielen aus den
  gerade eingespielten Phasen und Spurpunkten neu berechnet
  (`site_elevation_lib.php`).

  Diese Berechnung läuft seit Web 4.6.0 **nach** dem Abschluss der Transaktion
  und je Einsatz eingefasst: Ein Fehler darin darf die Wiederherstellung nicht
  kosten. Scheitert sie, bleibt das Feld leer und die Antwort nennt die Zahl
  der betroffenen Einsätze als `hoehe_fehler`.

**`created_at` kommt seit Version 7 zurück** und steht deshalb nicht mehr in
dieser Liste. Bis dahin galt: gesichert ja, eingespielt nein — nach einer
Wiederherstellung trugen alle Einsätze den Zeitpunkt des Einspielens (am
Referenzdatensatz der Phase P1 gemessen: 79 verschiedene Werte davor, 5
danach). Der Verlust war folgenlos für die Dokumentation selbst —
`started_at` ist die fachliche Zeit —, aber er war ein Verlust, und eine
Sicherung, die eine Angabe stillschweigend fallenlässt, ist keine
(Backlog Nr. 25).

Jetzt steht `created_at` als benannte Ausnahmespalte neben `start_src` und
`pat_blob` in der Einspielroutine. Der Wert läuft durch `pruef_utc_oder_sql`;
ist er unbrauchbar, wird die Spalte **weggelassen** statt auf `NULL` gesetzt —
dann greift die Vorgabe der Datenbank, und die Zeile bleibt. Ein Komfortwert
darf eine Wiederherstellung nicht kosten.

**Was in der Sicherung gar nicht vorkommt — und deshalb nach einer
Wiederherstellung fehlt:**

Der Abschnitt oben zählt Spalten auf. Diese drei sind ganze Bereiche, und ihr
Fehlen fällt erst auf, wenn man danach sucht:

- **Geräte.** Eine Uhr trägt einen API-Schlüssel; ein mitgesichertes Gerät
  wäre ein mitgesicherter Zugang. Nach einer Wiederherstellung muss deshalb
  **jede Uhr neu gekoppelt werden**. Die Dienstkennungen (`day_refs`) bleiben
  dagegen erhalten — sie verhindern, dass ein später eintreffender Upload
  einen Diensttag ein zweites Mal anlegt; ihr Feld `device_id` steht danach
  auf `null`.
- **Kopplungscodes** (`pair_codes`). Sie sind kurzlebig und haben außerhalb
  ihres Zeitfensters keine Bedeutung.
- **Die Sperrliste** (`deleted_refs`). Sie merkt sich, welche Uhr-Referenzen
  endgültig gelöscht wurden, damit ein Nachzügler-Upload sie nicht wieder
  anlegt. Nach einer Wiederherstellung ist sie leer — ein Upload einer noch
  gekoppelten Uhr könnte einen endgültig gelöschten Einsatz also erneut
  anlegen. In der Praxis entschärft sich das dadurch, dass die Kopplung
  ebenfalls weg ist.

  **Die Sperrliste ist nicht der Papierkorb**, auch wenn beide mit Löschen zu
  tun haben. Der Papierkorb gehört dem Konto und steht seit Version 7 in jeder
  Sicherung; die Sperrliste hängt an einer **Gerätekennung**, und Geräte
  stehen aus dem Grund darüber in keiner Sicherung. Sie bleibt deshalb
  ausdrücklich draußen. Eine Folge davon gehört dazu: Wiederhergestellte
  Einträge tragen `device_id = NULL`, und wer sie später endgültig löscht,
  füllt damit die Sperrliste nicht (`trash_block_ref()` verlangt eine
  Gerätekennung). Das ist dasselbe „Geräte weg → Sperrliste leer" wie oben,
  keine zusätzliche Lücke.

**Der Papierkorb steht seit Version 7 in der Datei** und stand bis Version 6
in keiner. Der Absatz, der ihn hier als fehlend führte, ist damit gegenstandslos;
was stattdessen gilt, steht in Abschnitt 2 („Der Papierkorb in der Datei") und
Abschnitt 3. Für **Version-6-Dateien** gilt die alte Aussage weiter: Sie
enthalten keinen Papierkorb, und eine Wiederherstellung aus ihnen leert ihn.

**Kommt eine Spalte hinzu, die mitgesichert werden soll**, ist sie in
`backup_lib.php` einzutragen (Liste `$missionSpalten` beziehungsweise die
Aufzählungen für `rest_segments` und `days`) und hier zu ergänzen. Das ist
Absicht: Es soll eine Entscheidung sein, keine Nebenwirkung.

---

## 5. Admin-Sicherung (seit Web 5.9.0)

> **Sie schreibt weiterhin das einteilige Format.** Containerfassung 4 gilt
> seit Web 11.0.0 für die Sicherung, die eine NutzerIn selbst erstellt; die
> Serversicherung zieht in AP6 nach (Konzept S2, 3.3). Bis dahin gilt für sie
> unverändert, was hier steht.

Ein anderes Format als die `.edbak`-Datei — es umschliesst sie. Erzeugt von
`adminbackup_lib.php`, abgelegt unter `server/sicherungen/<kontokennung>/` als
unverschlüsseltes JSON. **Warum unverschlüsselt:** Der Server hat keinen
Schlüssel, mit dem er es versiegeln könnte, ohne ihn ebenfalls zu speichern —
das wäre ein Schloss mit dem Schlüssel daneben. Geschützt ist die Datei durch
den Ort (`Require all denied` und der nicht erratbare Ordnername), und die
empfindlichen Angaben darin stecken ohnehin verschlüsselt.

```json
{
  "format":      "einsatzdoku-adminsicherung",
  "version":     1,
  "erzeugt":     "2026-08-16T18:22:31Z",
  "web_version": "5.9.0",
  "konto":  { "account_key": "…16 Hexziffern…",
              "email": "…", "name": "…" },
  "schluessel": { "pat_wrap_rc": "…", "pat_key_check": "…" },
  "umfang": { "einsaetze": 42, "diensttage": 12, "ruhezeiten": 3,
              "papierkorb": { "einsaetze": 5, "diensttage": 1,
                              "ruhezeiten": 5 } },
  "daten":  { … das innere JSON aus Abschnitt 2 … }
}
```

**`umfang.papierkorb` seit S1**, additiv — die Paketversion bleibt deshalb 1.
Die drei Zahlen darüber zählen den Papierkorb **mit**; ohne den Unterblock
wäre aus „42 Einsätze" nicht zu erkennen, dass fünf davon gelöscht sind. Bei
Sicherungen aus der Zeit davor fehlt der Block, und die Anzeige lässt ihn dann
**weg** statt eine Null zu zeigen: „nicht erhoben" ist etwas anderes als
„nichts drin".

**`daten` ist unverändert das Backup-JSON** — mit einem Unterschied zur
`.edbak`-Datei: Dort ersetzt der Browser `pat_blob` vor dem Versiegeln durch
Klartext. Hier bleibt der **Chiffretext** stehen, genau wie in der Datenbank.
Der Server sieht also nie etwas, was er nicht ohnehin sieht.

**`schluessel` ist der Grund, warum das Format überhaupt existiert.**
`pat_wrap_rc` enthält den Inhaltsschlüssel, verpackt mit dem
Wiederherstellungsschlüssel — und bleibt bei Passwortwechseln unberührt, ist
also über die Lebensdauer des Kontos stabil. Ohne diesen Wert liesse sich das
Paket nur in dasselbe Konto zurückspielen; der Hauptanwendungsfall ist aber das
neu aufgesetzte Konto, und dort ist er der einzige Weg vom
Wiederherstellungsschlüssel zum alten Inhaltsschlüssel.

`pat_key_check` ist die Prüfsumme des Inhaltsschlüssels und darf `null` sein
(Konten aus der Zeit vor Web 4.0.0). `pat_wrap_rc` darf ebenfalls `null` sein —
bei Konten, die zwischen Einladung und erster Passwortvergabe gesichert wurden.
Sie haben dann auch keine geschützten Angaben.

**`konto.json` im selben Ordner** ist Begleitdatei und Verzeichnis in einem:

```json
{
  "account_key":      "…",
  "email":            "…",
  "name":             "…",
  "letzte_sicherung": "2026-08-16T18:22:31Z",
  "sicherungen":      [ { "datei": "…", "erzeugt": "…", "umfang": { … } } ],
  "freigabe":         { "datei": "…", "ziel_user": 7,
                        "erstellt": "…", "eingeloest": null }
}
```

Sie hält Name und Adresse fest, **damit die Zuordnung eine Kontolöschung
überlebt** — genau dafür gibt es den Abschnitt „verwaiste Sicherungen" in der
Übersicht. Die Liste der Pakete entsteht bei der Anzeige trotzdem aus dem
Verzeichnis und nicht aus dieser Datei: Ein Eintrag ohne Datei darf keine
Sicherung vortäuschen, und eine vorhandene Datei darf nicht unsichtbar bleiben,
weil sie hier fehlt. Ist `konto.json` unlesbar, wird der Ordner mit Hinweis
aufgeführt statt übergangen.
