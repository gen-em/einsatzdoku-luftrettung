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

## Austausch bei einem Update

Verzeichnis löschen, neue Fassung hineinlegen, Prüfsummenlisten neu erzeugen,
Version hier **und** in `docs/Lizenzen.md` nachziehen. Nicht hineinpatchen.
