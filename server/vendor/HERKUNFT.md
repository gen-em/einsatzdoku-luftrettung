# Herkunft der fremden Bibliotheken unter `server/vendor/`

Diese Dateien stammen **nicht** aus diesem Projekt. Sie laufen auf dem Server
(nicht im Browser) und sind deshalb nicht unter `assets/vendor/` einsortiert,
sondern hier — mit einer `.htaccess`, die den Abruf über den Browser sperrt.

Die Lizenzen und die Begründung, warum es sie gibt, stehen in
`docs/Lizenzen.md`, Abschnitt 3.

| Verzeichnis | Bibliothek | Version | Commit | Lizenz |
|---|---|---|---|---|
| `phpseclib3/` | [phpseclib](https://github.com/phpseclib/phpseclib) | 3.0.57 | `d17e0ddaeaf6f22f7e007cbb437d78792fe2a0e4` | MIT (`LIZENZ-phpseclib.txt`) |
| `ParagonIE/ConstantTime/` | [constant_time_encoding](https://github.com/paragonie/constant_time_encoding) | 2.7.0 | `52a0d99e69f56b9ec27ace92ba56897fe6993105` | MIT (`LIZENZ-constant-time-encoding.txt`) |
| `Parsedown.php` | [Parsedown](https://github.com/erusev/parsedown) | 1.7.4 | Tag `1.7.4` | MIT (Kopfkommentar der Datei) |

**`Parsedown.php` ist eine einzelne Datei und folgt deshalb der anderen
Regel:** Herkunft, Fassung und SHA-256 stehen in ihrem Kopfkommentar, so wie
`docs/Lizenzen.md` es im Normalfall verlangt. Die Prüfsummenlisten daneben
gibt es nur, weil 349 Dateien von Hand nicht zu pflegen sind — bei einer ist
der Kopf die richtige Stelle. Der Kommentar ist **vor** den Originalinhalt
gesetzt, an der Datei selbst ist nichts geändert; nachzurechnen mit

```sh
N=$(grep -n '^ \*/$' server/vendor/Parsedown.php | head -1 | cut -d: -f1)
{ echo '<?php'; tail -n +$((N+1)) server/vendor/Parsedown.php; } | sha256sum
```

— das muss `af4a4b29f38b5a00b003a3b7a752282274c969e42dee88e55a427b2b61a2f38f`
ergeben, die Summe im Kopf der Datei und die der Ursprungsdatei. Der Befehl
schneidet den eigenen Kommentar heraus und setzt das `<?php` wieder davor, das
er verdrängt hat. **Die Zeilenzahl wird gesucht, nicht eingetippt:** Ein
`tail -n +43` stimmt genau so lange, bis jemand eine Zeile im Kommentar
ergänzt — und meldet dann eine falsche Summe, was schlimmer ist als keine
Prüfung.

`phpseclib3/` ist der Inhalt von `phpseclib/` aus dem Ursprungsarchiv,
`ParagonIE/ConstantTime/` der Inhalt von `src/`. Nichts daran ist geändert;
Tests, Build-Dateien und `composer.json` sind nicht mitgenommen.

## Nachrechnen

```sh
cd server/vendor
sha256sum -c phpseclib3.sha256
sha256sum -c ParagonIE-ConstantTime.sha256
```

338 bzw. 11 Zeilen, je eine Datei. Diese Listen ersetzen den Kopfkommentar mit
Herkunft und SHA-256, den `docs/Lizenzen.md` sonst je Datei verlangt — bei 349
Dateien wäre er von Hand nicht zu pflegen und beim ersten Austausch falsch.

## Wozu

`ParagonIE\ConstantTime` wird von phpseclib vorausgesetzt (genau eine Stelle:
`phpseclib3/Common/Functions/Strings.php`) und sonst nirgends benutzt.
phpseclib selbst wird nur für **einen** Zweck geladen: den SFTP-Adapter in
`sicherungsziel_lib.php`. Kein anderer Teil der Anwendung berührt es, und der
Lader `laden.php` wird auch nur dort eingebunden — eine Seite, die keine
SFTP-Verbindung aufbaut, lädt keine einzige dieser Dateien.

**`Parsedown.php` geht am Lader vorbei** und wird von `doku_lib.php` direkt
eingebunden (`require_once`). Grund: Die Klasse `Parsedown` trägt keinen
Namensraum, der PSR-4-Lader in `laden.php` hätte also nichts zu tun. Geladen
wird sie nur, wenn `hilfe.php` oder `ueber.php` eine Seite **neu rendern
müssen** — liegt das Ergebnis im Cache (`app_state`), bleibt auch diese Datei
ungelesen.

## Austausch bei einem Update

Verzeichnis löschen, neue Fassung hineinlegen, Prüfsummenlisten neu erzeugen,
Version hier **und** in `docs/Lizenzen.md` nachziehen. Nicht hineinpatchen.
