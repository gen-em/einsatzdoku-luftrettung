# Versandprobe — die drei Backup-Ziel-Adapter gegen echte Server

Entstanden in S2/AP7. Sie prüft `server/sicherungsziel_lib.php` und
`server/serverkrypto_lib.php` — Verbindung, Übertragung, Fehlerfälle,
Versiegelung der Zugangsdaten.

## Was hier NICHT geprüft werden kann

**Kein echtes Ziel im Internet.** Aus dem Behälter, in dem diese Probe
entstanden ist, gehen nur Verbindungen auf Port 443 hinaus; 21, 22 und 990
laufen ins Leere. Nachgemessen mit `github.com:22` als Gegenkontrolle — ein
Port, der ganz sicher offen ist, und auch er wird abgewiesen. Es ist also eine
Portsperre und keine Eigenschaft des Ziels. **Die Abnahme gegen das echte Ziel
der Betreiberin gehört deshalb auf deren Maschine oder auf den
Produktivserver** (Konzept-S2, Abschnitt 9, Zuarbeit) — und sie ist dort ein
Klick: „Verbindung prüfen" auf der Seite Backup-Ziele.

**Keine Übertragung über eine langsame oder abreissende Leitung.** Alles
läuft über Loopback. Ein Abbruch mitten in der Datei ist deshalb nur
*nachgestellt* (eine gekürzte Datei am Ziel), nicht erlebt.

**Keine volle Platte.** Geprüft ist der *nächstliegende* Fall: ein
Verzeichnis, in das nicht geschrieben werden darf. Der Weg durch den Adapter
ist derselbe (der Server lehnt das `STOR` ab), die Ursache eine andere.

**Keine fremden Zertifikate ausser dem selbst ausgestellten.** Der Befund
„`ext/ftp` prüft das Zertifikat nicht" ist damit belegt; ob es bei einem
gültigen Zertifikat *zusätzlich* etwas prüfte, ist damit nicht widerlegt —
aber auch nicht die Frage, die zählt.

## Zwei Sätze Gegenstellen, ein Satz Erwartungen

Was die Probe prüft, sind die **Adapter**. Ob sie damit fertig ist, hängt
davon ab, gegen *was* sie läuft — deshalb gibt es zwei Gegenstellen, und
beide werden gebraucht.

| | `gegenstellen.py` | `echte_gegenstellen.sh` |
|---|---|---|
| FTP / FTPS | pyftpdlib | **vsftpd** |
| SFTP | paramiko | **OpenSSH** |
| Ports | 2121 / 2122 / 2222 | 2131 / 2132 / 2232 |
| braucht | nur pip | root, Debian/Ubuntu, `apt-get install vsftpd openssh-server` |
| `MLSD` | ja → der Hauptweg | **nein** → der Rückfall auf `NLST`+`SIZE` |
| chroot | nein | ja (vsftpd), nein (OpenSSH) |

**Erst beide Läufe zusammen decken beide Zweige von `ZielFtp::liste()` ab.**
vsftpd kennt kein `MLSD`; der Rückfallzweig wäre gegen pyftpdlib allein nie
gefahren worden. Die Probe *misst* deshalb, welchen Weg sie genommen hat, und
schreibt es hin — statt es zu vermuten.

Was der zweite Satz gefunden hat, obwohl der erste grün war: **zwei
Fehlermeldungen blieben halb englisch.** vsftpd sagt „Could not create file",
pyftpdlib sagt „Not enough privileges" — die Übersetzung in
`sicherungsziel_lib.php` kannte beide nicht. Jede Umsetzung bringt ihr eigenes
Vokabular mit, und genau das ist der Grund für den zweiten Satz.

**Der Grundpfad ist je Protokoll ein anderer, und das ist kein Detail:**
vsftpd sperrt den Nutzer in sein Heimverzeichnis, dort ist `/` die Wurzel.
OpenSSH tut das nicht — dort ist `/` die Wurzel des Dateisystems. Wer das
verwechselt, schreibt seine Backups nach `/`.

## Was sie braucht

Für die Nachbauten:

```sh
pip install pyftpdlib paramiko pyopenssl
```

`pyopenssl` ist nicht optional: Ohne es lädt pyftpdlib den `TLS_FTPHandler`
gar nicht erst, und die FTPS-Gegenstelle fehlt still. `openssl` als Programm
wird für das Wegwerf-Zertifikat gebraucht.

Für die echten Server: root und

```sh
apt-get install -y vsftpd openssh-server
```

(nicht zusammen mit `proftpd-basic` — beide belegen `ftp-server` und schliessen
einander aus).

## Aufruf

Zwei Schalen. In der ersten die Gegenstellen, in der zweiten die Probe.

**Nachbauten:**

```sh
python3 tools/versandprobe/gegenstellen.py /tmp/versandprobe
php     tools/versandprobe/probe.php       /tmp/versandprobe
```

**Echte Server** — das Wurzelverzeichnis muss für den FTP-Nutzer
**durchschreitbar** sein (vsftpd wechselt nach der Anmeldung dorthin; liegt
ein Verzeichnis auf dem Weg auf 0700 root, meldet es „500 OOPS: cannot change
directory", was wie ein Anmeldefehler aussieht und keiner ist). `/srv/...` ist
die sichere Wahl:

```sh
sh  tools/versandprobe/echte_gegenstellen.sh /srv/versandprobe
php tools/versandprobe/probe.php             /srv/versandprobe --echt
sh  tools/versandprobe/echte_gegenstellen.sh --stop
```

Erwartet in **beiden** Fällen: **115 Erwartungen, 0 nicht erfüllt.** Der
Rückgabewert ist 0, wenn alles hält, sonst 1.

### Der Hostschlüsselwechsel

Er lässt sich mit den echten Servern *wirklich* herstellen statt mit einem
erfundenen Fingerabdruck:

```sh
sh tools/versandprobe/echte_gegenstellen.sh --wechsel  /srv/versandprobe
# ... Verbindung mit dem ALTEN Fingerabdruck versuchen: muss abbrechen
sh tools/versandprobe/echte_gegenstellen.sh --zurueck  /srv/versandprobe
```

Gemessen: Die Verbindung bricht ab, die Meldung nennt beide Fingerabdrücke,
und das Protokoll von OpenSSH steht **vorher wie nachher auf 46
Anmeldezeilen** — es ging kein Passwort hinaus. Nebenbei eine unabhängige
Gegenprobe der eigenen Rechnung: Der von `sz_fingerabdruck()` errechnete Wert
ist zeichengleich mit dem, den `ssh-keygen -lf` ausgibt.

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
| 11 | Nebenwege: **gemessener** Listenweg (MLSD oder Rückfall), **aktives** FTP, ein Grundpfad mit Unterordnern, verweigertes Schreiben |

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

| | Nachbauten | echte Server | Speicherspitze |
|---|---|---|---|
| FTP | 0,13 s | **0,35 s** | 2,0 MB |
| FTPS | 0,68 s | **1,85 s** | 2,0 MB |
| SFTP | 3,08 s | **0,68 s** | 8,0 MB |

Je 64 Dateien zu zusammen 63,9 MB, Speicherspitze gegen ein Budget von 64 MB.
Alle angekommenen Dateien wurden byteweise mit dem Original verglichen,
0 Abweichungen (192 gegen die Nachbauten, 64 gegen OpenSSH). Ein zweiter Lauf sendete 0 Dateien (0,19 s). Eine am Ziel auf
1 000 Byte gekürzte Datei wurde beim nächsten Lauf **einzeln** erneut
geschickt (1 von 64) und war danach wieder byteweise gleich.

Die Zahlen stehen über Loopback und sagen nichts über eine echte Leitung; was
sie sagen, ist: Der Speicher bleibt weit unter dem Z3-Budget von 64 MB, und
der Versand strömt die Dateien, statt sie in den Speicher zu laden.
