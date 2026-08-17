# Backup-Dateiformat (`.edbak`)

Der Export sichert **alle** Daten einer NutzerIn in einer einzelnen,
passwortverschlüsselten Datei. Seit **Version 2** passieren Ver- und
Entschlüsselung vollständig im Browser (`assets/crypto.js`) — der Server sieht
zu keinem Zeitpunkt Klartext. Weil die geschützten Angaben dabei entschlüsselt
in den Container wandern und beim Import mit dem Schlüssel des Zielkontos neu
verschlüsselt werden, lässt sich ein Backup **in jedes Konto** einspielen.

## 1. Container, Version 3 (seit Web 5.0.0)

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

## 1a. Container, Version 2 (bis Web 4.7.0) — wird weiterhin gelesen

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

Eine Datei mit einer **höheren** Fassungsnummer als 3 wird nicht als „Passwort
falsch" gemeldet, sondern als „stammt aus einer neueren Fassung" — sonst sucht
die lesende Person den Fehler an der falschen Stelle.

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

## 1a. Unlesbare Angaben (seit Web 4.1.0)

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
  "version": 5,
  "created_at": "2026-07-20T18:00:00+00:00",   // Export-Zeitpunkt (UTC)
  "app": "einsatzdoku-luftrettung",
  "user": { "email": "...", "name": "..." },   // Herkunftskonto, wird beim
                                               // Einspielen angezeigt

  "stammdaten": {
    "bases":        [ { "name": "Kempten", "is_default": 1 } ],
    "aircraft":     [ { "registration": "Christoph 17", "p1": 1, "p2": 0,
                        "hems": 1, "fr": 0, "other": 0, "is_default": 1 } ],
    "crew_presets": [ { "role": "p1|p2|hems|fr|other", "name": "…" } ],
    "bw_units":     [ { "name": "Bereitschaft Oberstdorf" } ],
    "resources":    [ { "name": "RTW Kempten 21/83" } ],
    "transport_dests": [ { "name": "Klinikum Kempten" } ]   // seit Version 3
  },

  // Flugtage; Maschinen-/Standort-Verweise sind als NAMEN aufgelöst
  // (aircraft_reg / base_name), damit das Backup portabel ist.
  "days": [ {
    "day": "2026-07-19",
    "aircraft_reg": "Christoph 17", "base_name": "Kempten",
    "crew_p1": "…", "crew_p2": null, "crew_hems": "…",
    "crew_fr": null, "crew_other": null, "notes": "…"
  } ],

  "missions": [ {
    "client_ref": "m-1721383200",       // eindeutige Referenz (Dubletten-Schutz)
    "day": "2026-07-19",                // lokales Datum des Einsatzbeginns
    "started_at": "2026-07-19 08:15:00",  // DATETIME, UTC
    "ended_at":   "2026-07-19 09:02:00",  // null = kein Abschluss
    "manual": 0, "final": 1,
    "origin": "watch", "edited": 0,        // seit Version 4 (Herkunft/Bearbeitungsstatus)
    "distance_m": 38400, "ascent_m": 550,
    "site_ele_m": 712,                    // NICHT uebernommen (s. Hinweis unten)
    "transport_dest": "…",
    "winch": 0, "winch_cycles": null, "winch_cycles_pat": null,
    "winch_airload": 0, "bergwacht": 0, "bw_unit": null, "bw_info": null,
    "other_ema": null, "notes": null,

    // Abweichende Besatzung (seit Web 2.6.0). crew_override = 0 -> die fünf
    // Felder sind null und der Einsatz erbt die Besatzung seines Flugtags
    // (siehe "days" oben). Nur belegte Rollen weichen ab.
    "crew_override": 0,
    "crew_p1": null, "crew_p2": null, "crew_hems": null,
    "crew_fr": null, "crew_other": null,

    // Geschützte Angaben — im Container KLARTEXT (der Container selbst ist
    // ja verschlüsselt). Beim Import werden sie mit dem Inhaltsschlüssel des
    // Zielkontos verschlüsselt und als `pat_blob` gespeichert.
    "pat": { "dx": "Polytrauma", "age": 41, "mission_no": "2026-0042",
             "loc": { "addr": "Ringstr. 18, 87439 Kempten",
                      "lat": 47.72, "lon": 10.31 },
             "site_desc": "Zufahrt über Forstweg, letzte 300 m zu Fuß" },
                                            // site_desc seit Version 5
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
    "track": [ [0, 47.72, 10.31, 712.5, 1721383200] ]
    //          seq  lat    lon    ele    ts(Unix-Sekunden UTC); ele kann null sein
  } ],

  "rest_segments": [ {
    "client_ref": "r-…", "day": "2026-07-19",
    "started_at": "…", "ended_at": "…", "final": 1,
    "track": [ [0, 47.72, 10.31, 712.5, 1721383200] ]
  } ]
}
```

### Feldkonventionen

- Zeitstempel `started_at`/`ended_at`/`occurred_at`: `YYYY-MM-DD HH:MM:SS` in
  **UTC**; Trackpunkt-`ts` ist Unix-Epoche in Sekunden (UTC).
- `day` ist das **lokale** Kalenderdatum des Beginns (Tageswechsel 0:00).
- Zusatzfelder der Einsätze folgen `server/mission_fields.php`; künftige
  Versionen können Felder ergänzen (Import ignoriert Unbekanntes).
- `is_default` bei `bases`/`aircraft`: intern seit Version 3 in einer
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

- Import immer in das **eigene, angemeldete** Konto; bestehende Daten werden
  nie überschrieben.
- Dubletten-Erkennung: Einsätze und Ruhesegmente über `client_ref`, Flugtage
  über das Datum, Stammdaten über ihre Namen — Vorhandenes wird übersprungen,
  nur Fehlendes ergänzt. Der Import ist damit gefahrlos wiederholbar.
- Die geschützten Angaben werden vor dem Senden im Browser mit dem
  Inhaltsschlüssel des Zielkontos verschlüsselt; der Server speichert nur
  Chiffretext.
- Standard-Markierungen (★) werden nur importiert, wenn noch kein Standard
  gesetzt ist (es bleibt bei genau einem).

## 4. Was NICHT in der Datei steht — und was nicht zurückkommt

Seit Web 4.5.2 ist das Format **aufgezählt** statt „alles, was in der Tabelle
steht". Vorher ergab es sich aus dem Datenbankschema: Jede neue Spalte war
automatisch in jeder Sicherung, ohne dass das jemand entschieden hätte.

**Nicht in der Datei:**

- `id`, `user_id`, `device_id` — interne Verweise. Sie gelten nur in der
  Datenbank, aus der die Sicherung stammt; eine Sicherung soll sich auch in
  ein anderes Konto und eine andere Installation einspielen lassen.
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

**Kommt eine Spalte hinzu, die mitgesichert werden soll**, ist sie in
`backup_lib.php` einzutragen (Liste `$missionSpalten` beziehungsweise die
Aufzählungen für `rest_segments` und `days`) und hier zu ergänzen. Das ist
Absicht: Es soll eine Entscheidung sein, keine Nebenwirkung.

---

## 5. Admin-Sicherung (seit Web 5.9.0)

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
  "umfang": { "einsaetze": 42, "flugtage": 12, "ruhezeiten": 3 },
  "daten":  { … das innere JSON aus Abschnitt 2, Formatversion 5 … }
}
```

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
