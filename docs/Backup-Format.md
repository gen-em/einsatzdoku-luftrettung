# Backup-Dateiformat (`.edbak`)

Der Export sichert **alle** Daten einer NutzerIn in einer einzelnen,
passwortverschlüsselten Datei. Seit **Version 2** passieren Ver- und
Entschlüsselung vollständig im Browser (`assets/crypto.js`) — der Server sieht
zu keinem Zeitpunkt Klartext. Weil die geschützten Angaben dabei entschlüsselt
in den Container wandern und beim Import mit dem Schlüssel des Zielkontos neu
verschlüsselt werden, lässt sich ein Backup **in jedes Konto** einspielen.

## 1. Container, Version 2

| Bytes   | Inhalt                                                          |
|---------|-----------------------------------------------------------------|
| 0–7     | Magie: ASCII `EDBAK2` + `0x00` + Formatversion `0x02`           |
| 8       | Flag: `1` = Inhalt gzip-komprimiert, `0` = roh                  |
| 9–24    | Salt für die Schlüsselableitung (16 Bytes, zufällig)            |
| 25–36   | AES-GCM-Initialisierungsvektor (12 Bytes, zufällig)             |
| ab 37   | Chiffretext, die letzten 16 Bytes sind das GCM-Auth-Tag         |

- **Schlüssel:** `PBKDF2-SHA256(Backup-Passwort, Salt, 310 000 Runden, 32 Bytes)`
- **Verfahren:** AES-256-GCM; die ersten **9 Bytes** (Magie + Flag) sind als
  *additional authenticated data* gebunden. Jede Änderung am Kopf oder am
  Inhalt lässt die Entschlüsselung scheitern — kein stilles Korrumpieren.
- **Klartext:** JSON (UTF-8), bei gesetztem Flag gzip-komprimiert.

Entschlüsselung von Hand (Beispiel, Python):

```python
import hashlib, gzip, json
from cryptography.hazmat.primitives.ciphers.aead import AESGCM

b = open('backup.edbak', 'rb').read()
key = hashlib.pbkdf2_hmac('sha256', passwort.encode(), b[9:25], 310_000, 32)
roh = AESGCM(key).decrypt(b[25:37], b[37:], b[0:9])
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
  "user": { "email": "...", "name": "..." },   // informativ

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
    "site_ele_m": 712,                    // wird beim Restore neu berechnet, nicht uebernommen
    "transport_dest": "…",
    "winch": 0, "winch_cycles": null, "winch_cycles_pat": null,
    "winch_airload": 0, "bergwacht": 0, "bw_unit": null, "bw_info": null,
    "other_ema": null, "other_resources": null, "notes": null,

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
    // "pat_blob": "Base64 …"   -> unverändert übernommener Chiffretext

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
