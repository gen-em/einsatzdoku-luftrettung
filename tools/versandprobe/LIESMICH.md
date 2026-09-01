# Versandprobe — die drei Sicherungsziel-Adapter gegen echte Server

Entstanden in S2/AP7. Sie prüft `server/sicherungsziel_lib.php` und
`server/serverkrypto_lib.php` — Verbindung, Übertragung, Fehlerfälle,
Versiegelung der Zugangsdaten.

## Was hier NICHT geprüft werden kann

**Kein echtes Ziel im Internet.** Aus dem Behälter, in dem diese Probe
entstanden ist, gehen nur Verbindungen auf Port 443 hinaus; 21, 22 und 990
laufen ins Leere. Nachgemessen mit `github.com:22` als Gegenkontrolle — ein
Port, der ganz sicher offen ist, und auch er wird abgewiesen. Es ist also eine
Portsperre und keine Eigenschaft des Ziels.

Die Probe stellt deshalb **eigene Gegenstellen** auf 127.0.0.1. Das prüft die
Adapter, nicht die Gegenstelle: Ein echter Server kann Eigenheiten haben, die
pyftpdlib und paramiko nicht nachbilden — andere Antworttexte, `MLSD` fehlt,
aktives statt passives FTP, ein Pfad mit Zeichen, die eine Anmeldung
überstehen und ein `STOR` nicht. **Die Abnahme gegen ein echtes Ziel je
Protokoll gehört auf die Maschine der Betreiberin oder auf den
Produktivserver** (Konzept-S2, Abschnitt 9, Zuarbeit).

**Keine Übertragung über eine langsame oder abreissende Leitung.** Alles
läuft über Loopback. Ein Abbruch mitten in der Datei ist deshalb nur
*nachgestellt* (eine gekürzte Datei am Ziel), nicht erlebt.

**Keine fremden Zertifikate ausser dem selbst ausgestellten.** Der Befund
„`ext/ftp` prüft das Zertifikat nicht" ist damit belegt; ob es bei einem
gültigen Zertifikat *zusätzlich* etwas prüfte, ist damit nicht widerlegt —
aber auch nicht die Frage, die zählt.

## Was sie braucht

```sh
pip install pyftpdlib paramiko pyopenssl
```

`pyopenssl` ist nicht optional: Ohne es lädt pyftpdlib den `TLS_FTPHandler`
gar nicht erst, und die FTPS-Gegenstelle fehlt still. `openssl` als Programm
wird für das Wegwerf-Zertifikat gebraucht.

## Aufruf

Zwei Schalen. In der ersten die Gegenstellen:

```sh
python3 tools/versandprobe/gegenstellen.py /tmp/versandprobe
```

Sie meldet den Fingerabdruck ihres Hostschlüssels und dann `BEREIT` — erst
danach horchen alle drei Ports (2121 FTP, 2122 FTPS, 2222 SFTP). In der
zweiten Schale:

```sh
php tools/versandprobe/probe.php /tmp/versandprobe
```

Erwartet: **106 Erwartungen, 0 nicht erfüllt.** Der Rückgabewert ist 0, wenn
alles hält, sonst 1.

## Was geprüft wird

| Teil | Worum es geht |
|---|---|
| 1 | Serverschlüssel: versiegeln, öffnen, Zweckbindung, fremder Schlüssel, Müll |
| 2 | Namen, Pfade, deutsche Fehlertexte |
| 3 | FTP: Ordner, senden, auflisten, zurückholen, vergleichen, löschen |
| 4 | FTPS: dasselbe — **und der Befund, dass ein selbst ausgestelltes Zertifikat angenommen wird** |
| 5 | SFTP: dasselbe, plus Fingerabdruck in OpenSSH-Schreibweise |
| 6 | Unerwarteter Hostschlüssel: Abbruch — **und die Gegenstelle sieht keinen Anmeldeversuch** |
| 7 | Privater Schlüssel: mit und ohne Passphrase, falsche Passphrase, kaputter Schlüssel |
| 8 | Fehlerfälle: falsches Passwort, falscher Port, fehlender Pfad, fehlende Datei |
| 9 | `sz_verbindung_pruefen()`: schreiben, zurücklesen, vergleichen, löschen, nichts liegenlassen |
| 10 | Datenbank: anlegen, ändern ohne Passwort, Fingerabdruck, Fehler merken, löschen |

**Teil 10 braucht eine Datenbank** mit der Tabelle `backup_targets` (Migration
`2026_09_01_sicherungsziele`). Fehlt sie, meldet die Probe genau diesen einen
Punkt als nicht erfüllt — sie schweigt nicht darüber. Die Teile 1 bis 9 laufen
auch ohne Installation.

**Die Probe ändert `server/config.php` nicht.** Sie liest sie, wenn es sie
gibt (für den Datenbankzugang), und legt sich für den Lauf einen eigenen
Serverschlüssel im Speicher an.

## Die zwei Messungen, die nicht in der Probe stehen

Der Versand selbst (`sz_versand_schub()`) braucht die Ablage unter
`server/sicherungen/` und lässt sich deshalb nicht als eigenständige Probe
fahren. Gemessen wurde er in S2/AP7 von Hand, gegen dieselben Gegenstellen,
mit 64 Paketen à zusammen 63,89 MB aus 33 Kontoordnern:

| | Dateien | Menge | Dauer | Speicherspitze |
|---|---|---|---|---|
| FTP | 64 | 63,89 MB | 0,13 s | 2,0 MB |
| FTPS | 64 | 63,89 MB | 0,68 s | 2,0 MB |
| SFTP | 64 | 63,89 MB | 3,08 s | 8,0 MB |

Alle 192 angekommenen Dateien wurden byteweise mit dem Original verglichen,
0 Abweichungen. Ein zweiter Lauf sendete 0 Dateien (0,19 s). Eine am Ziel auf
1 000 Byte gekürzte Datei wurde beim nächsten Lauf **einzeln** erneut
geschickt (1 von 64) und war danach wieder byteweise gleich.

Die Zahlen stehen über Loopback und sagen nichts über eine echte Leitung; was
sie sagen, ist: Der Speicher bleibt weit unter dem Z3-Budget von 64 MB, und
der Versand strömt die Dateien, statt sie in den Speicher zu laden.
