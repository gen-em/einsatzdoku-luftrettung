# Netzprobe — kommt der Simulator an einen lokalen Server heran?

```bash
sh tools/referenzdatensatz/einspielen/lokal_starten.sh          # Server und TLS davor
tools/uhr-pruefstand/pruefstand.sh bauen fenix6pro tools/netzprobe/monkey.jungle
tools/uhr-pruefstand/pruefstand.sh speicher-leeren
tools/uhr-pruefstand/pruefstand.sh starten fenix6pro 40
tools/uhr-pruefstand/pruefstand.sh konsole | grep NETZPROBE
```

Das Ziel steht als `gZiel` in `source/ProbeApp.mc` — eine Zeile, absichtlich
ohne Einstellung: Die Probe soll das messen, was dasteht, und nicht das, was
irgendwo gespeichert ist.

## Wozu

Der Simulator-Rundlauf der Uhr — App startet die Kopplung, ein lokaler Server
antwortet — steht und fällt damit, ob der Simulator überhaupt an einen Server
auf `127.0.0.1` herankommt. Die Frage klingt nach „ja, natürlich" und ist
dreimal mit Nein zu beantworten, bevor sie einmal mit Ja ausgeht. Diese Probe
beantwortet sie in fünf Minuten, statt einen halben Umbau darauf zu setzen.

Sie stellt **eine** Anfrage und zeigt den Rücklaufcode groß an. Der eigentliche
Beleg steht aber nicht auf dem Display, sondern im **Zugriffsprotokoll des
Servers** (`/tmp/php-server.log`): Was dort ankommt, hat den Simulator
verlassen. Beides zusammen unterscheidet die Fälle, die einzeln gleich
aussehen.

`pair.php` als Ziel ist mit Absicht gewählt: Es lehnt `GET` mit **405** ab.
Eine 405 in der Konsole ist damit der Volltreffer — sie kann nur aus dem
Endpunkt selbst kommen. Und die Probe verändert dabei nichts.

## Was am 03.09.2026 gemessen wurde (SDK 9.2.0, fenix6pro)

| Weg | Konsole der App | Zugriffsprotokoll | socat |
|---|---|---|---|
| `http://127.0.0.1:8080` | **−1001** (`SECURE_CONNECTION_REQUIRED`) | **`[405]: GET /pair.php`** | — |
| `https://127.0.0.1:8443`, **selbstsigniert** | 404 | nichts | `tlsv1 alert unknown ca` |
| `https://127.0.0.1:8443`, Zertifikat aus **eigener CA im Systemspeicher** | **405** | **`[405]: GET /pair.php`** | still |

**Die erste Zeile ist die unangenehme.** Über blankes HTTP lässt der Simulator
die Anfrage *hinaus* — der Server sieht sie und **führt sie aus** —, gibt der
App die Antwort aber nicht, sondern `−1001`. Wer nur auf den Rücklaufcode
sieht, hält den Weg für tot und übersieht, dass die Gegenseite bereits
gehandelt hat. Bei einem `POST` auf einen schreibenden Endpunkt ist das kein
Schönheitsfehler.

**Die dritte Zeile ist das Rezept**, und es steckt seit dem 03.09.2026 in
`tools/referenzdatensatz/einspielen/lokal_starten.sh`: eine eigene CA, ein
davon unterschriebenes Serverzertifikat mit `subjectAltName=IP:127.0.0.1`,
und die CA unter `/usr/local/share/ca-certificates/` mit
`update-ca-certificates`. Danach nimmt der Simulator die Verbindung an.

Die CA entsteht auf der Maschine und verlässt sie nicht; sie ist damit nicht
weitreichender als das selbstsignierte Zertifikat, das vorher dort stand.

## Wann sie wieder laufen sollte

- Nach jedem **SDK-Wechsel** (`CIQ_SDK_VERSION`) — die Prüfung des Ausstellers
  ist Sache des Simulators, und die ändert sich mit ihm.
- Wenn ein Rundlauf **ohne erkennbaren Grund** nichts empfängt: Sie trennt in
  einem Lauf „kommt nicht raus" von „kommt raus, Antwort wird verworfen".
- Auf einem **anderen Zielgerät**, wenn dort etwas anders aussieht als auf der
  fenix6pro.

## Was sie nicht ist

Kein Prüfmittel im Sinn von `CLAUDE.md` 6 und kein Bestandteil der App. Sie
trägt eine **eigene Anwendungs-ID**, damit die Einstellungen und der Speicher
der echten Uhr-App unberührt bleiben — dieselbe Überlegung wie bei
`tools/eingabe-probe/`, aus der sie hervorgegangen ist.
