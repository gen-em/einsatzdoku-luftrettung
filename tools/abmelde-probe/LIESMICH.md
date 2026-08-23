# Abmeldeprobe — was bleibt im sessionStorage?

Beleg zu **Backlog Nr. 22, S22-3** und zu Punkt **V-10** des Prüfdokuments P0
(„weder Daten- noch Inhaltsschlüssel nach dem Abmelden").

Die Probe füllt alle sechs Fächer, die das Projekt im `sessionStorage` führt,
und lässt danach den Abmeldeweg darüberlaufen — einmal in der Fassung vor der
Änderung, einmal mit der echten `server/assets/crypto.js`.

## Aufruf

    cd tools/abmelde-probe
    node pruefe.mjs

Die Probe startet dafür kurz einen lokalen Webserver: `sessionStorage` braucht
eine echte Herkunft, `file://` genügt nicht.

## Ergebnis

| Fach | Inhalt | 7.2.0 | 7.2.1 |
|---|---|---|---|
| `edk` | Datenschlüssel | geräumt | geräumt |
| `pck` | Inhaltsschlüssel | geräumt | geräumt |
| `edkvor` | Vormerkfach der stillen Anhebung (enthält den Datenschlüssel) | geräumt | geräumt |
| `edk_neu` | Vormerkfach des Passwortwechsels (enthält den **neuen** Datenschlüssel) | **bleibt** | geräumt |
| `pckb` | Keyguard: Kennung der Hülle (Hash) | bleibt | bleibt |
| `pckt` | Keyguard: Zeitpunkt des Entpackens | bleibt | bleibt |

`pckb` und `pckt` bleiben **mit Absicht** liegen — sie tragen kein
Schlüsselmaterial. Die Begründung steht im Prüfdokument, Abschnitt 3.
