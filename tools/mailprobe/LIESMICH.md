# Mailprobe — die Warteschlange gegen einen Server, der auf Kommando scheitert

Entstanden in P5a/AP5. Sie prüft `server/mail_lib.php`, `server/smtp.php` und
`server/instanz_lib.php` — Katalog, Rahmen, Warteschlange, Wiederholungsleiter,
Frist und die Zusage, dass kein Empfänger ins Fehlerprotokoll gerät.

```
php tools/mailprobe/probe.php
```

Rückgabe 0 = keine Befunde, 1 = Befunde, 2 = die Probe selbst kam nicht los
(keine `config.php`, `openssl` fehlt, Gegenstelle kam nicht hoch).

## Warum eine eigene Gegenstelle

Die Warteschlange behauptet etwas über **Fehlerfälle**: Eine Nachricht, deren
Versand scheitert, ist nicht verloren, sondern steht da und wird
wiederholt. Gegen einen funktionierenden Mailserver lässt sich das nicht
messen — er scheitert ja nicht. `gegenstelle.py` ist deshalb ein SMTPS-Server
mit fünf Betriebsarten:

| `--art` | Verhalten | wofür |
|---|---|---|
| `ok` | nimmt alles an, antwortet sofort | Zustellung, Job |
| `ablehnen` | `550` auf `RCPT TO`, mit angehängter Adresse | Grund, Kennung, Leiter |
| `langsam` | jede Antwort nach `--verzug` | — (Vorrat) |
| `vielzeilig` | EHLO-Antwort in **12** Fortsetzungszeilen, je `--verzug` | die Rechnung aus dem Kopf von `smtp.php` |
| `stumm` | begrüßt und schweigt dann | die Frist |

Sie spricht SMTP **genau**, nicht ungefähr. Das ist keine Feinheit: Die erste
Fassung beantwortete den Dreischritt `AUTH LOGIN` (334 → 334 → **235**)
dreimal mit 334. `smtp_send()` brach daraufhin ab, **bevor** es je ein
`RCPT TO` schickte — und damit war `ablehnen` von `ok` nicht zu
unterscheiden. Die Probe meldete acht Befunde, von denen keiner die
Anwendung betraf. Ein Gegenpart, der das Protokoll nur ungefähr spricht,
misst nichts.

## Die Probe tauscht `server/config.php` aus

`smtp.php` liest die Zugangsdaten unmittelbar aus `server/config.php` —
`require __DIR__ . '/config.php'`, fest verdrahtet. Die Probe kann ihr also
nicht von außen eine andere Adresse geben; sie **schreibt die Datei um** und
stellt sie wieder her:

- Die Sicherung liegt während des Laufs als `server/config.php.mailprobe`.
- Wiederhergestellt wird beim regulären Ende, bei einer Ausnahme **und** bei
  einem `exit` (`register_shutdown_function`).
- **Was das nicht abfängt:** `kill -9` oder ein wegbrechender Behälter. Dann
  zeigt `config.php` auf `127.0.0.1:2465` und die Sicherung liegt daneben —
  `mv server/config.php.mailprobe server/config.php` stellt sie her. Liegt die
  Datei da, ist die Probe nicht sauber zu Ende gekommen.

Das Zertifikat der Gegenstelle ist selbst ausgestellt und liegt unter
`/tmp`. `smtp_send()` prüft mit `verify_peer => true`; die Probe hängt ihre
Wurzel über `SSL_CERT_FILE` an. Die eingebaute Prüfung bleibt damit scharf —
ein *falsches* Zertifikat würde weiterhin abgewiesen.

## Was hier nicht geprüft werden kann

- **Ob eine Mail ankommt.** Die Gegenstelle nimmt an und wirft weg. Ob ein
  Empfänger sie im Postfach findet, sagt nur ein Postfach.
- **Das Aussehen.** Der Rahmen wird auf seine Bestandteile geprüft, nicht auf
  seine Wirkung. Wie eine Einladung in Outlook aussieht, sagt nur Outlook.
- **Namensauflösung.** Alles läuft über 127.0.0.1. Ein hängender DNS-Server
  liegt außerhalb der Frist von `smtp_send()` — das steht dort ausgeschrieben
  und ist hier nicht nachstellbar.
- **Nebenläufigkeit.** Zwei Jobs, die dieselbe Zeile gleichzeitig greifen,
  sind nicht nachgestellt.
- **Echte Laufzeiten.** Loopback antwortet in Millisekunden. Die gemessenen
  Fristen (5,00 s / 5,01 s / 3,01 s) sind deshalb die **obere** Schranke des
  Codes, nicht die eines Netzes.

## Was sie gefunden hat

- **Die Leiter läuft bei kurzlebigen Nachrichten nicht zu Ende**, und das ist
  richtig so: `passwort_reset` hat eine Frist von 3600 s, die dritte Sprosse
  läge bei 9300 s. Die Zeile wird nach **drei** Versuchen `zu_spaet`, nicht
  nach fünf `unzustellbar`. Die Probe misst beides getrennt — die Leiter an
  `adresswechsel` (ohne Frist), die Abkürzung an `passwort_reset`.
- **`smtp_letzter_fehler()` konnte den Grund des vorigen Versuchs liefern.**
  Der Merker wurde erst *nach* der Adressprüfung geleert; eine abgewiesene
  Adresse ließ also die Kennung des letzten Fehlschlags stehen. Behoben —
  der Merker wird als Erstes geleert und die Abweisung setzt ihren eigenen
  Grund.
