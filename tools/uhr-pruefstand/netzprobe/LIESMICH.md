# Netzprobe

Kommt der Connect-IQ-Simulator an einen Server auf `127.0.0.1` heran?
Wegwerfprojekt mit eigener Anwendungs-ID — **kein Bestandteil der App**.

## Aufruf

```bash
sh tools/referenzdatensatz/einspielen/lokal_starten.sh          # Server und TLS davor
tools/uhr-pruefstand/pruefstand.sh bauen fenix6pro tools/uhr-pruefstand/netzprobe/monkey.jungle
tools/uhr-pruefstand/pruefstand.sh speicher-leeren
tools/uhr-pruefstand/pruefstand.sh starten fenix6pro 40
tools/uhr-pruefstand/pruefstand.sh konsole | grep NETZPROBE
```

Das Ziel steht als `gZiel` in `source/ProbeApp.mc`, absichtlich ohne Einstellung.

## Was es misst

**Eine** Anfrage an `pair.php` und ihren Rücklaufcode. Der Beleg ist das
**Zugriffsprotokoll des Servers** (`/tmp/php-server.log`): Was dort
ankommt, hat den Simulator verlassen. `pair.php` lehnt `GET` mit 405 ab —
diese Antwort kann nur vom Endpunkt selbst kommen, und nichts ändert sich.

## Was es braucht

Den Uhr-Prüfstand (`pruefstand.sh aufbau`) und die örtliche Anlage mit TLS
aus einer eigenen CA (`lokal_starten.sh`).

## Erwartete Zahl

Konsole **405**, im Protokoll `[405]: GET /pair.php`. `−1001` heißt „kein
TLS" — und die Anfrage ist **trotzdem hinaus**; die Messung steht in
`docs/Technik.md` 5.2b. Wieder fahren nach einem SDK-Wechsel, auf einem
neuen Zielgerät und wenn ein Rundlauf ohne Grund nichts empfängt.

## Was es nicht kann

Mehr als den Weg hinaus und zurück belegen — was die App mit der Antwort
tut, zeigt erst ein Rundlauf.
