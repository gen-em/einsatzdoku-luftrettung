# Einspiellauf des Referenzdatensatzes

Spielt den erzeugten Datensatz über die **regulären Wege** in eine
Installation ein. Kein roher SQL-Weg (R4) — jede Zeile entsteht so, wie sie
im Betrieb entstünde.

## Die sechs Wege

| Weg | Was darüber hereinkommt |
|---|---|
| `pair.php` + Geräteseite | die **Kopplung** der zwei Geräte — und damit `geraet_art` und `geraet_modell` (seit R64/AP4) |
| `ingest.php` | Diensttage, Einsätze, Ruhe-Segmente, Phasen, Reanimationen, Spur |
| `api/day.php` | Zuordnung der neutralen Diensttage (Standort, Rettungsmittel, Besatzung) |
| `einsatz_form.php` | Nachtragen der Felder und der geschützten Angaben; `pat_blob` als `edk1:`-Chiffretext |
| `api/schneiden.php` | der **eine Schnitt**: aus einem Ruhesegment ein Einsatz, mit Sperrvermerk |
| Weboberfläche | Papierkorb und endgültiges Löschen |

### Die Kopplung — vier Schritte je Gerät

Bis R64/AP4 entstanden die zwei Geräte über `einstellungen.php action=add`:
Beschriftung und sonst nichts, `geraet_art` und `geraet_modell` NULL. Weil
`ingest.php` die **Momentaufnahme** beim Anlegen von dort kopiert, trug der
ganze Bestand eine leere — und der edbak-Kreislauf verglich NULL gegen NULL.

Jetzt geht die Stufe `geraet` den echten Weg:

1. `POST pair.php {"aktion":"start","geraet":{…}}`, **ohne** Kopfzeilen.
   Der Geräteblock kommt aus `quelldaten/geraete.json`. Die Antwort trägt
   Code, Kennung und Schlüssel als JSON — damit fällt das Abklauben des
   Markups weg, an dem der alte Weg zweimal zerbrochen ist (F-S2-A).
2. Zustand sichern, **sofort**. Der Schlüssel geht genau einmal über die
   Leitung.
3. `POST einstellungen.php?t=geraete action=koppeln_bestaetigen` mit dem Code.
4. `POST pair.php {"aktion":"bestaetigen","antwort":"ja"}` **mit**
   `X-Device-Id`/`X-Api-Key`. Erst hier entsteht die Zeile in `devices`.

Danach wird umbenannt: `pair.php` setzt beim Ja `label` auf „Uhr" bzw.
„Handy"; die sprechenden Namen kommen über `action=rename`.

> **Der Beleg für Schritt 3 ist Schritt 4, nicht die HTTP-Antwort.** Erfolg
> ist dort eine 302, Misserfolg eine 200 mit Fehlertext im HTML — nach dem
> Folgen der Umleitung sind beide 200. Bleibt Schritt 3 ohne Wirkung,
> antwortet Schritt 4 mit `409 nicht_beansprucht`. Genau das prüft die Stufe.

> **Ratenschutz:** `pair_start` lässt 20 Anfragen je 600 s und Adresse zu,
> und **nichts** setzt den Topf zurück — es gibt kein `rate_erfolg`
> dafür. Zwei Geräte je Lauf heißt **zehn Läufe je zehn Minuten**. Wer beim
> Entwickeln öfter fahren muss, räumt den Topf so ab, wie es
> `tools/kopplungsprobe/probe.php` tut; hier steht dafür kein SQL (R4).

> **Die Aufräumschleife darf nie nach dem Ingest laufen.** `devices` hängt an
> `missions`, `rest_segments` und `day_refs` mit `ON DELETE SET NULL`. Ein
> Aufräumen danach löschte nicht zwei Zeilen, sondern **trennte den ganzen
> Bestand von seinen Geräten** — ohne Fehlermeldung, sichtbar erst als leeres
> `days[].refs[].device_id` im Referenz-Export.

### Der Schnitt — warum er am **Ende** steht

Die Stufe `schneiden` ist die letzte, nicht — wie zuerst vorgesehen — die
zwischen `zuordnen` und `nachtragen`. Der Grund sind die drei Stufen
dazwischen: `nachtragen`, `papierkorb` und `sperrliste` suchen ihre Einsätze
über `start_hhmm`. Die erste bricht bei zwei Treffern ab, die zweite nimmt
still `treffer[0]`. Ein geschnittener Einsatz wäre ab der Stufe ein
zusätzlicher Einsatz in derselben Liste. Am Ende gibt es diese
Überschneidung nicht — und der geschnittene Einsatz braucht keine der drei:
Er bleibt bewusst leer.

Der **CSV-Import** läuft bewusst nicht hier, sondern im Browser (B4).

## Lokale Installation

```
sh lokal_einrichten.sh       # von Null: Datenbank, install.php, Admin, Demo-Konto
sh lokal_starten.sh          # nur hochfahren: MariaDB, PHP-Server, TLS davor
```

**Zwei Skripte, zwei Fragen.** `lokal_starten.sh` fährt hoch, was schon da
ist — das ist der Alltag. `lokal_einrichten.sh` baut von Null auf und ist
für die Wegwerf-Umgebung da, in der nach jedem Sitzungsende alles fort ist:
Es **löscht** die Datenbank und `server/config.php` und geht dann denselben
Weg wie eine Betreiberin — dieselbe Seite `install.php`, dasselbe Formular,
dieselben Prüfungen (Formular-Token, Nachweisdatei, Schema, Admin-Anlage).
Den Browserschritt baut es **nicht** nach, sondern ruft `passwort_setzen.mjs`
(E-P1-10); das Demo-Konto entsteht über `demo_anlegen()` — dieselbe Funktion,
die der Knopf im Adminbereich ruft.

Die Vorgaben sind die, die die Prüfmittel ohne Schalter erwarten:
`admin@gen-em.org` / `adminlokal2026` (`kreislauf.py`, `aufnehmen.mjs`) und
`demo@gen-em.org` / `nadokudemo0815`.

**Warum TLS.** Die Anwendung setzt ihr Sitzungs-Cookie mit `secure`
(`login.php`, `auth_guard.php`) — richtig so, sie gehört hinter HTTPS. Über
blankes HTTP schickt kein Client das Cookie zurück, und jede angemeldete
Seite leitet zur Anmeldung um. Der eingebaute PHP-Server kann kein TLS;
`socat` terminiert es davor, mit einem selbstsignierten Zertifikat für
127.0.0.1. Die Skripte prüfen dieses Zertifikat nicht — für die eigene
Maschine vertretbar, gegen eine echte Adresse bleibt die Prüfung an.

Eingerichtet wird **einmal** über `install.php` — am Arbeitsplatz im Browser,
in einer Wegwerf-Umgebung über `lokal_einrichten.sh` (oben). `lokal_starten.sh`
selbst richtet nichts ein.

## Ablauf

```
python3 einspielen.py --stufen konto
node passwort_setzen.mjs '<Einrichtungslink>' 'nadokudemo0815' rc.json
python3 einspielen.py --stufen stammdaten,geraet,ingest,zuordnen,nachtragen,manuell,papierkorb,sperrliste,schneiden
python3 messprotokoll.py
node sichtpruefung.mjs
```

Die Stufen sind einzeln aufrufbar und merken sich ihren Fortschritt in
`lauf.json`. Ein abgebrochener Ingest-Lauf setzt dort fort, wo er stand.

**Der Browserschritt dazwischen ist keine Bequemlichkeit.** Passwort,
Salz, Inhaltsschlüssel, beide Schlüsselhüllen und der
Wiederherstellungsschlüssel entstehen ausschließlich mit der WebCrypto des
Browsers (`assets/crypto.js`). Das ist die Zusage des Projekts: Der Server
sieht das Passwort nie. Ein Skript, das diesen Schritt nachbaut, prüfte den
Weg nicht mehr, den eine NutzerIn geht (E-P1-10).

## Was das Skript **nicht** tut

- **Kein SQL.** Auch nicht zum Lesen von Kennungen: Standort- und
  Rettungsmittel-Kennungen kommen aus den Auswahllisten von
  `diensttag_neu.php`, Einsatz-Kennungen aus `api/day.php`.
- **Kein Sonderendpunkt.** Die Anmeldung leitet PBKDF2 selbst ab und
  schickt das **Token**, nicht das Passwort — wie der Browser. Den
  Inhaltsschlüssel packt es aus `PAT_WRAP` aus, das jede angemeldete Seite
  ohnehin mitgibt.

## Messprotokoll (E-P1-14, Vorarbeit R19)

`messprotokoll.py` wertet den Lauf aus und schreibt `messprotokoll.json`
und `messprotokoll.md`. Gemessen wird das **Sendeverhalten einer Uhr**
anhand der Soll-Zeitpunkte, nicht die Geschwindigkeit des Skripts — der
Lauf schaufelt in Minuten, was im Betrieb über Tage anfällt.

## `lauf.json` gehört nicht ins Repositorium

Sie hält den Zustand **einer** Installation: Einrichtungslink,
Geräteschlüssel, vergebene Kennungen, Fortschritt. Für eine andere
Installation ist davon nichts gültig. Sie steht deshalb in `.gitignore`.

`rc.json` — der Wiederherstellungsschlüssel aus `passwort_setzen.mjs` —
steht ebenfalls in `.gitignore`, seit Web 8.0.1 unter dem Muster `*rc.json`.
Vorher lautete es `*_rc.json` und verfehlte damit genau die Datei, die diese
Anleitung anzulegen anweist.

Im dokumentierten Ablauf ist das keine Gefahr: Die Konten dieser Ablage sind
Wegwerfkonten mit öffentlichem Passwort, und ihr Schlüsselmaterial liegt
ohnehin offen in `server/demo/fixture.json.gz`. Der Schlüssel ist aber
**passwortäquivalent** — er packt den Inhaltsschlüssel ohne Passwort aus —,
und `passwort_setzen.mjs` nimmt **jede** URL, nicht nur `127.0.0.1`. Wer das
Skript gegen eine echte Installation richtet, hat den Schlüssel eines echten
Kontos in der Datei.

## Grenzen dieser Prüfmittel

- **Kartenkacheln laden hier nicht.** Sie kommen von
  `tile.openstreetmap.org` und Nachbarn (`assets/map_layers.js`, mit
  Herkunft und Lizenz). In einer Umgebung ohne Netzzugang dorthin scheitern
  sie. Geprüft ist deshalb, dass die **Spur** gezeichnet wird — nicht, dass
  der Kartenhintergrund erscheint.
- **Kein SMTP.** Der Einrichtungslink wird von der Seite abgelesen, nicht
  aus einer Mail. Der Mailversand ist damit nicht geprüft.
- Der **Sperrlisten-Prüfschritt ist einmalig je Installation**: Nach dem
  endgültigen Löschen steht die `client_ref` auf der Sperrliste und
  verfällt erst nach 90 Tagen. Ein zweiter Durchlauf braucht ein frisches
  Konto.
