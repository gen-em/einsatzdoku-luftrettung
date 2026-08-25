# Einspiellauf des Referenzdatensatzes

Spielt den erzeugten Datensatz über die **regulären Wege** in eine
Installation ein. Kein roher SQL-Weg (R4) — jede Zeile entsteht so, wie sie
im Betrieb entstünde.

## Die vier Wege

| Weg | Was darüber hereinkommt |
|---|---|
| `ingest.php` | Diensttage, Einsätze, Ruhe-Segmente, Phasen, Reanimationen, Spur |
| `api/day.php` | Zuordnung der neutralen Diensttage (Standort, Rettungsmittel, Besatzung) |
| `einsatz_form.php` | Nachtragen der Felder und der geschützten Angaben; `pat_blob` als `edk1:`-Chiffretext |
| Weboberfläche | Papierkorb und endgültiges Löschen |

Der **CSV-Import** läuft bewusst nicht hier, sondern im Browser (B4).

## Lokale Installation

```
sh lokal_starten.sh          # MariaDB, PHP-Server, TLS davor
```

**Warum TLS.** Die Anwendung setzt ihr Sitzungs-Cookie mit `secure`
(`login.php`, `auth_guard.php`) — richtig so, sie gehört hinter HTTPS. Über
blankes HTTP schickt kein Client das Cookie zurück, und jede angemeldete
Seite leitet zur Anmeldung um. Der eingebaute PHP-Server kann kein TLS;
`socat` terminiert es davor, mit einem selbstsignierten Zertifikat für
127.0.0.1. Die Skripte prüfen dieses Zertifikat nicht — für die eigene
Maschine vertretbar, gegen eine echte Adresse bleibt die Prüfung an.

Eingerichtet wird **einmal** über `install.php` im Browser; das macht dieses
Skript nicht.

## Ablauf

```
python3 einspielen.py --stufen konto
node passwort_setzen.mjs '<Einrichtungslink>' 'nadokudemo0815' rc.json
python3 einspielen.py --stufen stammdaten,geraet,ingest,zuordnen,nachtragen,manuell,papierkorb,sperrliste
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
