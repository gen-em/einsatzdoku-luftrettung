# sandbox — die Arbeitsumgebung herstellen und hochfahren

## Aufruf

```
bash tools/sandbox/aufbauen.sh   [web|android|uhr|plattform|alles]   # Vorgabe: web
bash tools/sandbox/hochfahren.sh [--neu] [--php 8.3]
bash tools/sandbox/plattform.sh  [php83|mariadb106|mysql80|mysql84|alles|--aus]
```

## Was es misst

`aufbauen.sh` beschafft, was der Container nicht mitbringt, und misst nach:
elf Stücke, die drei Engines **einzeln**, acht Umgebungswerte mit ihrer
Länge. `hochfahren.sh` richtet die örtliche Anlage ein oder startet sie.
`plattform.sh` stellt PHP 8.3.33 und drei Datenbankfassungen bereit und
fährt je eine Schemaprobe.

## Was es braucht

Netz (Ubuntu, Docker Hub, Debian über HTTPS), `dockerd` für `plattform.sh` —
**er läuft nicht von selbst**: `dockerd >/tmp/dockerd.log 2>&1 &`. Die acht
Umgebungswerte stehen in der Arbeitsumgebung, nicht im Repositorium; ihre
Namen und die Grenzen der Umgebung in `docs/Sandbox-Setup.md`.

## Erwartete Zahl

`aufbauen.sh web` → 11 von 11 Stücken, **3 von 3 Engines**, 8 von 8 Werten,
Rückgabewert 0. `hochfahren.sh` → HTTP **200** auf `login.php`, **0 Migrationen
offen**, zwei Prüfkonten „steht" (Nr. 297), Rückgabewert 0 (Nr. 332). `plattform.sh alles` → vier Fassungen bereit und **viermal
„30 Prüfungen, 0 Fehlschläge"** (19 bis P5c/AP8; 29,7 s am 21.09.2026).

## Was es nicht kann

Keinen Mailversand, kein IMAP (nur Port 443), keinen Apache des Hosters,
kein echtes Gerät, kein echtes Sicherungsziel. Es stellt **Fassungen** nach,
keine Anlagen — eine grüne Plattformmatrix sagt nichts darüber, wie sich
Staging oder Produktiv verhalten. `--aus` räumt die Behälter weg.

*Anlass: Nr. 183 (WebKit fehlte still), Nr. 267 (nur MySQL 8.4 scheiterte), Nr. 332 (altes Schema), Nr. 297 (Prüfkonten des Bilderlaufs).*
